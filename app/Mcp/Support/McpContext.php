<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Conexión MCP en curso: quién es, qué nivel de acceso tiene y registro de lo que hace.
 * Las herramientas llaman a allow() antes de leer o escribir.
 */
class McpContext
{
    private const MAX_LOG = 2000;

    public function __construct(
        public readonly string $connection = 'desconocida',
        public readonly string $access = 'lectura',
    ) {
    }

    public static function current(): self
    {
        return app()->bound(self::class) ? app(self::class) : new self();
    }

    public function canWrite(): bool
    {
        return $this->access === 'completo';
    }

    /** Lanza un error legible si la conexión o el rol no permiten la acción. */
    public function allow(string $permission, bool $write = false): void
    {
        if ($write && !$this->canWrite()) {
            throw new McpDenied('Esta conexión es de solo lectura. Crea una conexión con acceso completo en Configuración → MCP.');
        }
        if (!RoleAccess::can(Auth::user(), $permission)) {
            throw new McpDenied("Tu usuario no tiene el permiso {$permission}.");
        }
    }

    public function log(string $tool, string $result, array $detail = []): void
    {
        $store = new FileStore('mcp_activity.json');
        $all = $store->all();
        $all[] = [
            'id' => (string) Str::ulid(),
            'at' => now()->toISOString(),
            'user_id' => (string) Auth::id(),
            'connection' => $this->connection,
            'tool' => $tool,
            'result' => Str::limit($result, 300, '…'),
            'detail' => $detail,
        ];
        $store->save(array_slice($all, -self::MAX_LOG));
    }
}
