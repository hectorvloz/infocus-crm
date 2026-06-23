<?php

namespace App\Support\Notifications;

use App\Http\Controllers\ProfileController;
use App\Repositories\FileStore;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

class WebPushNotificationService
{
    private FileStore $subscriptions;
    private FileStore $states;
    private FileStore $vapidKeys;
    private FileStore $notificationState;

    public function __construct()
    {
        $this->subscriptions = new FileStore('push_subscriptions.json');
        $this->states = new FileStore('push_notification_states.json');
        $this->vapidKeys = new FileStore('push_vapid_keys.json');
        $this->notificationState = new FileStore('notification_states.json');
    }

    public function publicKey(): string
    {
        return (string) ($this->keys()['publicKey'] ?? '');
    }

    public function saveSubscription(array $identity, array $subscription, string $userAgent = ''): array
    {
        $normalized = $this->normalizeSubscription($subscription);
        $id = $this->subscriptionId($normalized['endpoint']);
        $payload = [
            'id' => $id,
            'identity' => $this->safeIdentity($identity),
            'identity_key' => $this->identityKey($identity),
            'subscription' => $normalized,
            'user_agent' => Str::limit($userAgent, 500, ''),
            'active' => true,
            'last_seen_at' => now()->toIso8601String(),
        ];

        if ($this->subscriptions->find($id)) {
            $this->subscriptions->update($id, $payload);
        } else {
            $this->subscriptions->create($payload);
        }

        return ['id' => $id, 'public_key' => $this->publicKey()];
    }

    public function deleteSubscription(string $endpoint): void
    {
        $endpoint = trim($endpoint);
        if ($endpoint === '') {
            return;
        }

        $this->subscriptions->delete($this->subscriptionId($endpoint));
    }

    public function sendPending(): array
    {
        $profile = app(ProfileController::class);
        $sent = 0;
        $failed = 0;
        $expired = 0;
        $checked = 0;

        foreach ($this->subscriptions->all() as $row) {
            if (!($row['active'] ?? true)) {
                continue;
            }

            $identity = is_array($row['identity'] ?? null) ? $row['identity'] : [];
            $subscriptionPayload = is_array($row['subscription'] ?? null) ? $row['subscription'] : [];
            $subscriptionId = (string) ($row['id'] ?? '');
            if (!$identity || !$subscriptionPayload || $subscriptionId === '') {
                continue;
            }

            $checked++;
            $items = $this->pendingNotificationsForIdentity($profile, $identity, $subscriptionId);
            if (!$items) {
                continue;
            }

            foreach ($items as $item) {
                $result = $this->sendOne($subscriptionPayload, $item);
                if (($result['expired'] ?? false) === true) {
                    $expired++;
                    $this->subscriptions->delete($subscriptionId);
                    break;
                }
                if (($result['ok'] ?? false) === true) {
                    $sent++;
                    $this->markSent($subscriptionId, (string) $item['id']);
                } else {
                    $failed++;
                }
            }
        }

        return compact('checked', 'sent', 'failed', 'expired');
    }

    private function pendingNotificationsForIdentity(ProfileController $profile, array $identity, string $subscriptionId): array
    {
        $readIds = collect($this->notificationState->find($this->identityKey($identity))['read_ids'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->all();
        $sentIds = collect($this->states->find($subscriptionId)['sent_ids'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->all();

        return collect($profile->buildNotifications($identity))
            ->reject(fn ($item) => in_array((string) ($item['id'] ?? ''), $readIds, true))
            ->reject(fn ($item) => in_array((string) ($item['id'] ?? ''), $sentIds, true))
            ->take(5)
            ->values()
            ->all();
    }

    private function sendOne(array $subscriptionPayload, array $item): array
    {
        $keys = $this->keys();
        $auth = [
            'VAPID' => [
                'subject' => config('app.url') ?: 'mailto:admin@localhost',
                'publicKey' => $keys['publicKey'],
                'privateKey' => $keys['privateKey'],
            ],
        ];

        $webPush = new WebPush($auth);
        $payload = json_encode([
            'id' => (string) ($item['id'] ?? ''),
            'title' => (string) ($item['title'] ?? 'Nueva notificación'),
            'body' => (string) ($item['message'] ?? ''),
            'url' => (string) ($item['url'] ?? '/'),
            'date' => (string) ($item['date'] ?? now()->toIso8601String()),
            'tag' => 'infocus-' . (string) ($item['id'] ?? Str::ulid()),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $report = $webPush->sendOneNotification(Subscription::create($subscriptionPayload), $payload);
            return [
                'ok' => $report->isSuccess(),
                'expired' => $report->isSubscriptionExpired(),
                'reason' => $report->getReason(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'expired' => false, 'reason' => $e->getMessage()];
        }
    }

    private function markSent(string $subscriptionId, string $notificationId): void
    {
        $state = $this->states->find($subscriptionId) ?: ['id' => $subscriptionId, 'sent_ids' => []];
        $sentIds = collect($state['sent_ids'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->push($notificationId)
            ->unique()
            ->take(-300)
            ->values()
            ->all();

        $payload = ['id' => $subscriptionId, 'sent_ids' => $sentIds, 'updated_at' => now()->toIso8601String()];
        if ($this->states->find($subscriptionId)) {
            $this->states->update($subscriptionId, $payload);
        } else {
            $this->states->create($payload);
        }
    }

    private function normalizeSubscription(array $subscription): array
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $keys = is_array($subscription['keys'] ?? null) ? $subscription['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));

        if ($endpoint === '' || strlen($endpoint) > 2048 || !str_starts_with($endpoint, 'https://')) {
            throw new \InvalidArgumentException('Suscripción push inválida.');
        }
        if ($p256dh === '' || $auth === '') {
            throw new \InvalidArgumentException('Claves push incompletas.');
        }

        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => $p256dh,
                'auth' => $auth,
            ],
            'contentEncoding' => (string) ($subscription['contentEncoding'] ?? 'aes128gcm'),
        ];
    }

    private function keys(): array
    {
        $row = $this->vapidKeys->find('default');
        if (is_array($row) && !empty($row['publicKey']) && !empty($row['privateKey'])) {
            return $row;
        }

        $keys = VAPID::createVapidKeys();
        $payload = [
            'id' => 'default',
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
            'created_at' => now()->toIso8601String(),
        ];
        $this->vapidKeys->create($payload);

        return $payload;
    }

    private function subscriptionId(string $endpoint): string
    {
        return 'push:' . hash('sha256', $endpoint);
    }

    private function identityKey(array $identity): string
    {
        $base = trim((string) (($identity['email'] ?? '') ?: ($identity['id'] ?? 'session-user')));
        return 'user:' . Str::slug(Str::lower($base), '-');
    }

    private function safeIdentity(array $identity): array
    {
        return [
            'id' => (string) ($identity['id'] ?? 'session-user'),
            'name' => (string) ($identity['name'] ?? 'Usuario'),
            'email' => (string) ($identity['email'] ?? ''),
            'role' => (string) ($identity['role'] ?? 'admin'),
            'phone' => '',
            'profile_info' => '',
            'profile_photo' => '',
        ];
    }
}
