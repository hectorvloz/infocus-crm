<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Tokens personales para conectar asistentes de IA (Claude Code, Codex, Gemini CLI) al MCP.
 * Solo se guarda el hash SHA-256: el token en claro se muestra una única vez al crearlo.
 */
class McpTokens
{
    public const PREFIX = 'ifc_';
    public const ACCESS_LEVELS = ['lectura', 'completo'];

    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('mcp_tokens.json');
    }

    /** @return array{token: string, record: array} */
    public function create(int|string $userId, string $name, string $access, ?int $days): array
    {
        $plain = self::PREFIX . Str::random(48);
        $record = $this->store->create([
            'user_id' => (string) $userId,
            'name' => Str::limit(trim($name) ?: 'Asistente', 60, ''),
            'access' => in_array($access, self::ACCESS_LEVELS, true) ? $access : 'lectura',
            'token_hash' => hash('sha256', $plain),
            'token_hint' => substr($plain, -4),
            'expires_at' => $days ? now()->addDays($days)->toISOString() : null,
            'last_used_at' => null,
            'revoked_at' => null,
        ]);

        return ['token' => $plain, 'record' => $record];
    }

    /** Devuelve el registro vigente para el token o null si no existe, venció o fue revocado. */
    public function findValid(string $plain): ?array
    {
        if (!str_starts_with($plain, self::PREFIX)) {
            return null;
        }

        $hash = hash('sha256', $plain);
        $record = collect($this->store->all())->first(fn ($t) => hash_equals((string) ($t['token_hash'] ?? ''), $hash));
        if (!$record || !empty($record['revoked_at'])) {
            return null;
        }
        if (!empty($record['expires_at']) && Carbon::parse($record['expires_at'])->isPast()) {
            return null;
        }

        return $record;
    }

    public function touch(array $record): void
    {
        $last = $record['last_used_at'] ?? null;
        if (!$last || Carbon::parse($last)->lt(now()->subMinutes(5))) {
            $this->store->update($record['id'], ['last_used_at' => now()->toISOString()]);
        }
    }

    public function revoke(string $id, int|string $userId): bool
    {
        $record = $this->store->find($id);
        if (!$record || (string) $record['user_id'] !== (string) $userId) {
            return false;
        }
        $this->store->update($id, ['revoked_at' => now()->toISOString()]);

        return true;
    }

    public function forUser(int|string $userId): array
    {
        return collect($this->store->all())
            ->filter(fn ($t) => (string) ($t['user_id'] ?? '') === (string) $userId && empty($t['revoked_at']))
            ->map(fn ($t) => collect($t)->except('token_hash')->all())
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }
}
