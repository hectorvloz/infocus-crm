<?php

namespace App\Support;

use App\Repositories\FileStore;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class DocumentStorage
{
    public const DISK = 'local';
    public const LEGACY_DISK = 'public';

    public static function put(string $path, string $contents): bool
    {
        return Storage::disk(self::DISK)->put($path, $contents);
    }

    public static function disk(string $path, bool $migrateLegacy = true): ?FilesystemAdapter
    {
        $private = Storage::disk(self::DISK);
        if ($private->exists($path)) {
            return $private;
        }

        $legacy = Storage::disk(self::LEGACY_DISK);
        if (!$legacy->exists($path)) {
            return null;
        }

        if ($migrateLegacy && self::migrate($path)) {
            return $private;
        }

        return $legacy;
    }

    public static function migrate(string $path): bool
    {
        $legacy = Storage::disk(self::LEGACY_DISK);
        if (!$legacy->exists($path)) {
            return Storage::disk(self::DISK)->exists($path);
        }

        $stream = $legacy->readStream($path);
        if (!is_resource($stream)) {
            return false;
        }

        try {
            $stored = Storage::disk(self::DISK)->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        if (!$stored) {
            return false;
        }

        $legacy->delete($path);
        return true;
    }

    public static function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }

    public static function allowedUploadRules(int $maxKilobytes): array
    {
        return [
            'file',
            'max:'.$maxKilobytes,
            'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp3,m4a,wav,mp4,mov',
        ];
    }

    public static function isInlineSafe(string $mime): bool
    {
        return in_array(strtolower($mime), [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf',
            'text/plain', 'text/csv',
        ], true);
    }

    public static function migrateRegisteredDocuments(): array
    {
        $migrated = 0;
        $failed = 0;

        foreach ((new FileStore('documentos.json'))->all() as $document) {
            if (($document['storage'] ?? 'local') !== 'local') {
                continue;
            }

            $path = trim((string) ($document['path'] ?? ''));
            if ($path === '' || !Storage::disk(self::LEGACY_DISK)->exists($path)) {
                continue;
            }

            if (self::migrate($path)) {
                $migrated++;
                $thumbnail = 'document-thumbnails/'.hash('sha256', (string) ($document['id'] ?? '').'|'.$path).'.webp';
                Storage::disk(self::LEGACY_DISK)->delete($thumbnail);
            } else {
                $failed++;
            }
        }

        return compact('migrated', 'failed');
    }
}
