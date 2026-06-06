<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\RoleAccess;
use Symfony\Component\HttpFoundation\Response;

class AuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('user') && !Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401, 'Tu sesion expiro. Inicia sesion de nuevo.');
            }

            return redirect()->route('login.show');
        }

        if (Auth::check() && ($request->route()?->getName() !== 'logout')) {
            $user = Auth::user();
            if (($user->role ?? null) === 'client') {
                return redirect()->route('portal.auth.dashboard');
            }

            if (!RoleAccess::userCanAccessRoute($request)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    abort(403, 'No tienes permisos para esta accion.');
                }

                return redirect()->route(RoleAccess::firstAllowedRoute($user));
            }
        }

        return $next($request);
    }
}
