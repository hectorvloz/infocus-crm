<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AiSupportConversationStore
{
    private const TTL_HOURS = 24;
    private const MAX_MESSAGES = 24;

    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('ai_support_conversations.json');
    }

    public function messages(string $userId, string $scope, string $entityId): array
    {
        $conversation = $this->conversation($userId, $scope, $entityId);

        return array_values(array_map(fn (array $message) => [
            'role' => (string) ($message['role'] ?? 'assistant'),
            'content' => (string) ($message['display_content'] ?? $message['content'] ?? ''),
            'created_at' => $message['created_at'] ?? null,
        ], $conversation['messages'] ?? []));
    }

    public function history(string $userId, string $scope, string $entityId): array
    {
        $conversation = $this->conversation($userId, $scope, $entityId);

        return array_values(array_map(fn (array $message) => [
            'role' => (string) ($message['role'] ?? 'assistant'),
            'content' => (string) ($message['content'] ?? ''),
        ], $conversation['messages'] ?? []));
    }

    public function recordExchange(
        string $userId,
        string $scope,
        string $entityId,
        string $userContent,
        string $assistantContent,
        ?string $assistantDisplayContent = null,
    ): void {
        $all = $this->pruned();
        $key = $this->key($userId, $scope, $entityId);
        $now = now();
        $index = collect($all)->search(fn ($item) => (string) ($item['key'] ?? '') === $key);
        $conversation = $index === false ? [
            'id' => (string) Str::ulid(),
            'key' => $key,
            'user_id' => $userId,
            'scope' => $scope,
            'entity_id' => $entityId,
            'messages' => [],
            'created_at' => $now->toISOString(),
        ] : $all[$index];

        $messages = is_array($conversation['messages'] ?? null) ? $conversation['messages'] : [];
        $messages[] = ['role' => 'user', 'content' => $userContent, 'created_at' => $now->toISOString()];
        $messages[] = [
            'role' => 'assistant',
            'content' => $assistantContent,
            'display_content' => $assistantDisplayContent ?? $assistantContent,
            'created_at' => $now->toISOString(),
        ];
        $conversation['messages'] = array_values(array_slice($messages, -self::MAX_MESSAGES));
        $conversation['updated_at'] = $now->toISOString();
        $conversation['expires_at'] = $now->copy()->addHours(self::TTL_HOURS)->toISOString();

        if ($index === false) {
            $all[] = $conversation;
        } else {
            $all[$index] = $conversation;
        }
        $this->store->save(array_values($all));
    }

    private function conversation(string $userId, string $scope, string $entityId): array
    {
        $all = $this->pruned();
        $key = $this->key($userId, $scope, $entityId);

        return collect($all)->first(fn ($item) => (string) ($item['key'] ?? '') === $key) ?? [];
    }

    private function pruned(): array
    {
        $all = $this->store->all();
        $now = now();
        $active = array_values(array_filter($all, function ($item) use ($now) {
            try {
                return !empty($item['expires_at']) && Carbon::parse($item['expires_at'])->isFuture();
            } catch (\Throwable) {
                return false;
            }
        }));
        if (count($active) !== count($all)) {
            $this->store->save($active);
        }
        return $active;
    }

    private function key(string $userId, string $scope, string $entityId): string
    {
        return hash('sha256', implode('|', [$userId, $scope, $entityId]));
    }
}
