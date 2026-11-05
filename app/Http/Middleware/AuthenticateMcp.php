<?php

namespace App\Http\Middleware;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\McpTokens;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server\Registrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege /mcp. Acepta un token personal (ifc_...) creado en Configuración → MCP
 * o un token OAuth de Passport con el scope mcp:use. Solo administradores.
 */
class AuthenticateMcp
{
    private const ADMIN_ROLES = ['admin', 'super_admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();
        [$user, $context] = str_starts_with($bearer, McpTokens::PREFIX)
            ? $this->fromPersonalToken($bearer)
            : $this->fromOAuth();

        if (!$user) {
            return response()->json(['error' => 'unauthorized', 'message' => 'Token inválido, vencido o revocado.'], 401);
        }
        if (!in_array(strtolower((string) $user->role), self::ADMIN_ROLES, true)) {
            return response()->json(['error' => 'forbidden', 'message' => 'El MCP de Infocus es solo para administradores.'], 403);
        }

        Auth::setUser($user);
        app()->instance(McpContext::class, $context);

        return $next($request);
    }

    /** @return array{0: ?User, 1: ?McpContext} */
    private function fromPersonalToken(string $bearer): array
    {
        $tokens = new McpTokens();
        $record = $tokens->findValid($bearer);
        $user = $record ? User::find($record['user_id']) : null;
        if (!$user) {
            return [null, null];
        }
        $tokens->touch($record);

        return [$user, new McpContext('token:' . $record['name'], $record['access'])];
    }

    /** @return array{0: ?User, 1: ?McpContext} */
    private function fromOAuth(): array
    {
        try {
            $user = Auth::guard('api')->user();
        } catch (\Throwable $e) {
            // Sin llaves de Passport (php artisan passport:keys) OAuth no está disponible: se trata como no autenticado.
            report($e);
            $user = null;
        }
        if (!$user || !$user->tokenCan(Registrar::OAUTH_SCOPE)) {
            return [null, null];
        }
        $clientId = (string) ($user->token()?->oauth_client_id ?? '');
        $client = $clientId !== '' ? (\Laravel\Passport\Passport::client()->newQuery()->find($clientId)?->name ?? 'OAuth') : 'OAuth';

        return [$user, new McpContext('oauth:' . $client, 'completo')];
    }
}
