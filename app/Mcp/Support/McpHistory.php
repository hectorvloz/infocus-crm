<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Copia de cómo estaba un registro antes de que el MCP lo editara, para poder restaurarlo.
 * Solo cubre ediciones de registros que ya existían; lo que el MCP crea no se borra desde aquí.
 */
class McpHistory
{
    public const KEEP_DAYS = 30;
    public const MAX_ENTRIES = 500;

    /** Archivo => [tipo legible, permiso para restaurar]. */
    public const FILES = [
        'mis_notas.json' => ['nota', 'mis-notas.update'],
        'proyectos.json' => ['proyecto', 'proyectos.update'],
        'leads.json' => ['lead', 'leads.update'],
        'header_reminders.json' => ['recordatorios', null],
    ];

    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('mcp_history.json');
    }

    /** Ejecuta $write y, si el registro cambió, guarda cómo estaba antes. */
    public function track(string $file, string $recordId, string $label, callable $write, string $connection = ''): mixed
    {
        $records = new FileStore($file);
        $before = $records->find($recordId);
        $result = $write();
        $after = $records->find($recordId);

        if ($before !== null && $after !== null && $before !== $after) {
            $all = $this->fresh();
            $all[] = [
                'id' => (string) Str::ulid(),
                'user_id' => (string) Auth::id(),
                'connection' => $connection,
                'file' => $file,
                'record_id' => $recordId,
                'label' => Str::limit($label, 200, '…'),
                'before' => $before,
                'after_hash' => self::hash($after),
                'restored_at' => null,
                'created_at' => now()->toISOString(),
            ];
            $this->store->save(array_slice($all, -self::MAX_ENTRIES));
        }

        return $result;
    }

    /** Cambios recientes del usuario actual, del más nuevo al más viejo. */
    public function recent(int $limit = 20): array
    {
        return collect($this->fresh())
            ->where('user_id', (string) Auth::id())
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    public function find(string $id): ?array
    {
        $entry = collect($this->fresh())->firstWhere('id', $id);

        return $entry && (string) $entry['user_id'] === (string) Auth::id() ? $entry : null;
    }

    /** ¿Alguien modificó el registro después del cambio del MCP? */
    public function changedSince(array $entry): bool
    {
        $current = (new FileStore($entry['file']))->find($entry['record_id']);

        return $current === null || self::hash($current) !== $entry['after_hash'];
    }

    /** Devuelve el registro exactamente a como estaba (también queda en el historial, así se puede deshacer). */
    public function restore(array $entry, string $connection = ''): void
    {
        $file = $entry['file'];
        $this->track($file, $entry['record_id'], 'Restauración de: ' . $entry['label'], function () use ($entry, $file) {
            $records = new FileStore($file);
            $all = $records->all();
            foreach ($all as $i => $row) {
                if ((string) ($row['id'] ?? '') === (string) $entry['record_id']) {
                    $all[$i] = $entry['before'];
                }
            }
            $records->save(array_values($all));
        }, $connection);

        $all = $this->store->all();
        foreach ($all as &$row) {
            if (($row['id'] ?? null) === $entry['id']) {
                $row['restored_at'] = now()->toISOString();
            }
        }
        unset($row);
        $this->store->save($all);
    }

    private function fresh(): array
    {
        $limit = now()->subDays(self::KEEP_DAYS);

        return array_values(array_filter($this->store->all(), fn ($e) => Carbon::parse($e['created_at'] ?? 'now')->greaterThan($limit)));
    }

    private static function hash(array $record): string
    {
        return hash('sha256', json_encode($record));
    }
}
