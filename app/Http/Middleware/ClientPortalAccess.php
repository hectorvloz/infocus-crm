<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login.show');
        }

        if (($user->role ?? null) !== 'client' || empty($user->cliente_id)) {
            return redirect()->route('dashboard');
        }

        if (!empty($user->must_change_password) && !$request->routeIs('portal.auth.change-password*')) {
            return redirect()->route('portal.auth.change-password');
        }

        return $next($request);
    }
}
