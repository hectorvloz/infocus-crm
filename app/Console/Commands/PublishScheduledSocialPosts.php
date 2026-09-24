<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Services\SocialMedia\MetaSocialMediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PublishScheduledSocialPosts extends Command
{
    protected $signature   = 'social:publish-scheduled {--dry-run : Preview posts without publishing}';
    protected $description = 'Publica en Meta (Facebook/Instagram) los posts del calendario con scheduled_at <= ahora';

    public function __construct(private MetaSocialMediaService $meta)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $calendar = new FileStore('social_content_calendar.json');
        $accounts = new FileStore('social_accounts.json');
        $syncLogs = new FileStore('social_sync_logs.json');

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $timezone = (string) ($settings['timezone'] ?? config('app.timezone', 'America/Bogota'));
        $now      = Carbon::now($timezone);

        // Find posts due to be published
        $due = collect($calendar->all())->filter(function ($item) use ($now) {
            if (($item['status'] ?? '') !== 'programado') {
                return false;
            }
            if (empty($item['scheduled_at'])) {
                return false;
            }
            // Only consider posts not already attempted
            if (!empty($item['published_at']) || !empty($item['publish_error'])) {
                return false;
            }
            return Carbon::parse($item['scheduled_at'])->lte($now);
        });

        if ($due->isEmpty()) {
            $this->info('[social:publish-scheduled] No hay posts pendientes de publicar.');
            return self::SUCCESS;
        }

        $this->info("[social:publish-scheduled] {$due->count()} post(s) para publicar.");

        $allAccounts = collect($accounts->all());
        $published   = 0;
        $failed      = 0;

        foreach ($due as $post) {
            $postId   = (string) ($post['id'] ?? '');
            $platform = (string) ($post['platform'] ?? '');
            $title    = (string) ($post['title'] ?? 'Sin título');

            if ($dryRun) {
                $this->line("  [DRY-RUN] {$platform} | {$title} | scheduled_at: " . ($post['scheduled_at'] ?? ''));
                continue;
            }

            try {
                $result = $this->publishPost($post, $allAccounts);

                $calendar->update($postId, [
                    'status'           => 'publicado',
                    'published_at'     => $now->toISOString(),
                    'external_post_id' => $result['external_post_id'] ?? '',
                    'publish_error'    => null,
                ]);

                $this->logSync($syncLogs, $platform, 'published', "Publicado: {$title}", [
                    'post_id'          => $postId,
                    'platform'         => $platform,
                    'external_post_id' => $result['external_post_id'] ?? '',
                ]);

                $this->info("  ✓ Publicado [{$platform}] {$title}");
                $published++;

            } catch (\Throwable $e) {
                $errorMsg = Str::limit($e->getMessage(), 400);

                $calendar->update($postId, [
                    'status'        => 'error_publicacion',
                    'publish_error' => $errorMsg,
                    'published_at'  => null,
                ]);

                $this->logSync($syncLogs, $platform, 'publish_error', "Error publicando: {$title}", [
                    'post_id' => $postId,
                    'error'   => $errorMsg,
                ]);

                $this->error("  ✗ Error [{$platform}] {$title}: {$errorMsg}");
                $failed++;
            }
        }

        $this->info("[social:publish-scheduled] Completado — {$published} publicados, {$failed} errores.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Publishing dispatcher
    // ─────────────────────────────────────────────────────────────────────────

    private function publishPost(array $post, \Illuminate\Support\Collection $allAccounts): array
    {
        $platform    = (string) ($post['platform'] ?? '');
        $accountId   = (string) ($post['account_id'] ?? '');
        $contentType = (string) ($post['content_type'] ?? 'post');

        // Resolve the linked account record
        $account = $accountId !== ''
            ? $allAccounts->firstWhere('id', $accountId)
            : null;

        // Fallback: find any connected account matching the platform
        if (!$account) {
            $account = $allAccounts->first(function ($a) use ($platform) {
                return in_array($a['platform'] ?? '', [$platform, 'facebook', 'instagram'], true)
                    && ($a['status'] ?? '') === 'connected';
            });
        }

        if (!$account) {
            throw new \RuntimeException("No se encontró una cuenta conectada para la plataforma '{$platform}'.");
        }

        $externalId = (string) ($account['external_id'] ?? '');
        $accountPlatform = (string) ($account['platform'] ?? '');

        // Get the OAuth token (either from the account directly or from its parent Meta connection)
        $token = $this->resolvePublishToken($account, $allAccounts);

        return match ($accountPlatform) {
            'instagram' => $this->meta->publishToInstagram($token, $externalId, $post),
            'facebook'  => $this->meta->publishToFacebookFeed($token, $externalId, $post),
            default     => throw new \RuntimeException("Plataforma '{$accountPlatform}' no soportada para publicación automática aún."),
        };
    }

    /**
     * Resolve the publish access token for an account.
     * Checks the account itself, then walks up to its parent Meta OAuth connection.
     */
    private function resolvePublishToken(array $account, \Illuminate\Support\Collection $allAccounts): string
    {
        // Some accounts store a page-scoped token directly (encrypted)
        if (!empty($account['page_token_encrypted'])) {
            try {
                return Crypt::decryptString((string) $account['page_token_encrypted']);
            } catch (\Throwable) {
            }
        }

        // Walk up the hierarchy: instagram → facebook page → meta oauth connection
        $parentExternalId = (string) ($account['parent_external_id'] ?? '');

        if ($parentExternalId !== '') {
            $parent = $allAccounts->firstWhere('external_id', $parentExternalId);
            if ($parent) {
                return $this->resolvePublishToken($parent, $allAccounts);
            }
        }

        // Last resort: use the active Meta OAuth connection token
        $metaConnection = $allAccounts->first(function ($a) {
            return ($a['platform'] ?? '') === 'meta'
                && ($a['type'] ?? '') === 'oauth_connection'
                && ($a['status'] ?? '') === 'connected'
                && !empty($a['token_encrypted']);
        });

        if ($metaConnection && !empty($metaConnection['token_encrypted'])) {
            try {
                return Crypt::decryptString((string) $metaConnection['token_encrypted']);
            } catch (\Throwable) {
            }
        }

        throw new \RuntimeException('No se encontró un token de acceso válido. Reconecta Meta en Configuración.');
    }

    private function logSync(FileStore $syncLogs, string $provider, string $status, string $message, array $context = []): void
    {
        $syncLogs->create([
            'provider' => $provider,
            'status'   => $status,
            'message'  => Str::limit($message, 500, ''),
            'context'  => $context,
            'user_id'  => null,
        ]);
    }
}
