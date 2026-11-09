<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Acciones irreversibles en dos pasos: la herramienta prepara y devuelve un código;
 * solo confirmar_accion con ese código (una vez, antes de 5 minutos) la ejecuta.
 */
class PendingActions
{
    public const TTL_MINUTES = 5;

    public function create(string $action, array $payload, string $connection, int $minutes = self::TTL_MINUTES): string
    {
        return Cache::store('file')->lock('mcp:pending-actions', 30)->block(5, function () use ($action, $payload, $connection, $minutes) {
            $store = new FileStore('mcp_pending_actions.json');
            $this->purge($store);
            $code = Str::upper(Str::random(3) . '-' . Str::random(3));
            $store->create([
                'code_hash' => hash('sha256', $code),
                'action' => $action,
                'payload' => $payload,
                'user_id' => (string) Auth::id(),
                'connection' => $connection,
                'expires_at' => now()->addMinutes($minutes)->toISOString(),
            ]);

            return $code;
        });
    }

    /** Consume el código: devuelve la acción si es válida para este usuario y la elimina. */
    public function consume(string $code): ?array
    {
        return Cache::store('file')->lock('mcp:pending-actions', 30)->block(5, function () use ($code) {
            $store = new FileStore('mcp_pending_actions.json');
            $hash = hash('sha256', Str::upper(trim($code)));
            $item = collect($store->all())->first(fn ($a) => hash_equals((string) ($a['code_hash'] ?? ''), $hash));
            if (!$item || (string) $item['user_id'] !== (string) Auth::id() || Carbon::parse($item['expires_at'])->isPast()) {
                return null;
            }

            $store->delete($item['id']);

            return $item;
        });
    }

    private function purge(FileStore $store): void
    {
        $all = $store->all();
        $fresh = array_values(array_filter($all, fn ($a) => Carbon::parse($a['expires_at'] ?? 'now')->isFuture()));
        if (count($fresh) !== count($all)) {
            $store->save($fresh);
        }
    }
}
