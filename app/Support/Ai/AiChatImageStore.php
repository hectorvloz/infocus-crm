<?php

namespace App\Support\Ai;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiChatImageStore
{
    public const RETENTION_DAYS = 7;

    public function save(UploadedFile $file, string $chatId): array
    {
        $id = (string) Str::ulid();
        $mime = (string) $file->getMimeType();
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
        $path = "ai-chat-cache/{$chatId}/{$id}.{$extension}";
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return [
            'id' => $id,
            'name' => Str::limit(basename($file->getClientOriginalName()), 120, ''),
            'mime' => $mime,
            'path' => $path,
            'expires_at' => now()->addDays(self::RETENTION_DAYS)->toISOString(),
        ];
    }

    public function available(array $image): bool
    {
        $path = (string) ($image['path'] ?? '');
        return $path !== ''
            && !empty($image['expires_at'])
            && now()->lessThan($image['expires_at'])
            && Storage::disk('local')->exists($path);
    }

    public function dataUrl(array $image): ?string
    {
        if (!$this->available($image)) return null;
        return 'data:' . $image['mime'] . ';base64,' . base64_encode(Storage::disk('local')->get($image['path']));
    }

    public function publicMetadata(array $image, string $chatId): array
    {
        $available = $this->available($image);
        return [
            'id' => (string) ($image['id'] ?? ''),
            'name' => (string) ($image['name'] ?? 'Imagen'),
            'url' => $available ? route('api.ai.chats.image', [$chatId, $image['id']]) : null,
            'expired' => !$available,
        ];
    }

    public function deleteChatImages(array $chat): void
    {
        foreach ($chat['messages'] ?? [] as $message) {
            foreach ($message['images'] ?? [] as $image) {
                if (!empty($image['path'])) Storage::disk('local')->delete($image['path']);
            }
        }
        Storage::disk('local')->deleteDirectory('ai-chat-cache/' . ($chat['id'] ?? ''));
    }

    public function pruneExpired(): int
    {
        $disk = Storage::disk('local');
        $removed = 0;
        foreach ($disk->allFiles('ai-chat-cache') as $path) {
            if ($disk->lastModified($path) < now()->subDays(self::RETENTION_DAYS)->timestamp) {
                $disk->delete($path);
                $removed++;
            }
        }
        return $removed;
    }
}
