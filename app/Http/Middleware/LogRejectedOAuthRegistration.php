<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja en el log por qué se rechazó el registro OAuth de un asistente (ej. Gemini):
 * las URLs de redirección y el error. Estos datos no son secretos.
 */
class LogRejectedOAuthRegistration
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            Log::warning('Registro OAuth del MCP rechazado', [
                'user_agent' => (string) $request->userAgent(),
                'client_name' => $request->input('client_name'),
                'redirect_uris' => $request->input('redirect_uris'),
                'fields' => array_keys($request->all()),
                'error' => mb_substr((string) $response->getContent(), 0, 500),
            ]);
        }

        return $response;
    }
}
