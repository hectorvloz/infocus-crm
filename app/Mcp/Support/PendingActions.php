<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Acciones irreversibles en dos pasos: la herramienta prepara y devuelve un código;
 * solo confirmar_accion con ese código (una vez, antes de 5 minutos) la ejecuta.
 */
class PendingActions
{
    public const TTL_MINUTES = 5;

    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('mcp_pending_actions.json');
    }

    public function create(string $action, array $payload, string $connection): string
    {
        $this->purge();
        $code = Str::upper(Str::random(3) . '-' . Str::random(3));
        $this->store->create([
            'code_hash' => hash('sha256', $code),
            'action' => $action,
            'payload' => $payload,
            'user_id' => (string) Auth::id(),
            'connection' => $connection,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->toISOString(),
        ]);

        return $code;
    }

    /** Consume el código: devuelve la acción si es válida para este usuario y la elimina. */
    public function consume(string $code): ?array
    {
        $hash = hash('sha256', Str::upper(trim($code)));
        $item = collect($this->store->all())->first(fn ($a) => hash_equals((string) ($a['code_hash'] ?? ''), $hash));
        if (!$item) {
            return null;
        }
        $this->store->delete($item['id']);
        if ((string) $item['user_id'] !== (string) Auth::id() || Carbon::parse($item['expires_at'])->isPast()) {
            return null;
        }

        return $item;
    }

    private function purge(): void
    {
        $all = $this->store->all();
        $fresh = array_values(array_filter($all, fn ($a) => Carbon::parse($a['expires_at'] ?? 'now')->isFuture()));
        if (count($fresh) !== count($all)) {
            $this->store->save($fresh);
        }
    }
}
