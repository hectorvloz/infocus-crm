<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Archivos que el usuario le pasa al asistente (chat de Infocus, WhatsApp o Claude
 * por el MCP) para usarlos después: adjuntarlos a un correo o como comprobante de
 * un gasto. Viven 7 días en storage privado y solo los ve quien los subió.
 */
class AiAttachments
{
    public const FILE = 'ai_attachments.json';

    public const DIR = 'ai-attachments';

    public const RETENTION_DAYS = 7;

    public const MAX_BYTES = 10 * 1024 * 1024;

    public const MIMES = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'text/csv' => 'csv', 'text/plain' => 'txt',
    ];

    /** @return array<string, mixed>|null registro guardado, o null si el tipo o el tamaño no se aceptan */
    public function save(string $contents, string $name, string $mime, string $source): ?array
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));
        if (!isset(self::MIMES[$mime]) || $contents === '' || strlen($contents) > self::MAX_BYTES) {
            return null;
        }

        $this->prune();
        $id = 'ADJ' . strtoupper(Str::random(8));
        $name = Str::limit(trim(basename(str_replace('\\', '/', $name))) ?: ('archivo.' . self::MIMES[$mime]), 120, '');
        $path = self::DIR . '/' . $id . '.' . self::MIMES[$mime];
        Storage::disk('local')->put($path, $contents);

        return (new FileStore(self::FILE))->create([
            'id' => $id,
            'user_id' => (string) Auth::id(),
            'name' => $name,
            'mime' => $mime,
            'size' => strlen($contents),
            'path' => $path,
            'source' => $source,
            'expires_at' => now()->addDays(self::RETENTION_DAYS)->toISOString(),
        ]);
    }

    /** Solo devuelve adjuntos del usuario actual que sigan vigentes. */
    public function find(string $id): ?array
    {
        $item = (new FileStore(self::FILE))->find(strtoupper(trim($id)));
        if (!$item || (string) ($item['user_id'] ?? '') !== (string) Auth::id() || now()->greaterThan($item['expires_at'] ?? 'now')) {
            return null;
        }

        return Storage::disk('local')->exists((string) $item['path']) ? $item : null;
    }

    public function contents(array $item): string
    {
        return (string) Storage::disk('local')->get((string) $item['path']);
    }

    public static function isImage(array $item): bool
    {
        return str_starts_with((string) ($item['mime'] ?? ''), 'image/');
    }

    public static function describe(array $item): string
    {
        return sprintf('%s «%s» (%s, %s KB)', $item['id'], $item['name'], self::MIMES[$item['mime']] ?? $item['mime'], max(1, (int) round(($item['size'] ?? 0) / 1024)));
    }

    /** Nota para el modelo con los adjuntos que llegaron en este mensaje. */
    public static function note(array $items, string $where): string
    {
        if ($items === []) {
            return '';
        }

        return "\n\n[Adjuntos recibidos por {$where}: " . implode('; ', array_map([self::class, 'describe'], $items))
            . '. Puedes usarlos con preparar_correo (adjuntos) o registrar_gasto (comprobante_adjunto_id).]';
    }

    public function prune(): int
    {
        $store = new FileStore(self::FILE);
        $all = $store->all();
        $fresh = [];
        $removed = 0;
        foreach ($all as $item) {
            if (now()->greaterThan($item['expires_at'] ?? 'now')) {
                Storage::disk('local')->delete((string) ($item['path'] ?? ''));
                $removed++;
            } else {
                $fresh[] = $item;
            }
        }
        if ($removed > 0) {
            $store->save($fresh);
        }

        return $removed;
    }
}
