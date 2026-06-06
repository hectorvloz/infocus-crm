<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedFlag = (bool) config('app.installed', false);
        $isInstalled = $installedFlag;
        if (!$isInstalled) {
            config(['session.driver' => 'file']);
        }
        if ($isInstalled) {
            try {
                DB::connection()->getPdo();
            } catch (\Throwable $e) {
                $isInstalled = false;
                config(['session.driver' => 'file']);
            }
        }
        $isInstallRoute = $request->is('install*');

        if (!$isInstalled && !$isInstallRoute) {
            return redirect()->route('install.show');
        }

        if ($isInstalled && $isInstallRoute) {
            return redirect()->route('login.show');
        }

        return $next($request);
    }
}
