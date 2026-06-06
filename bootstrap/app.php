<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$basePath = dirname(__DIR__);
$installed = file_exists(__DIR__.'/../storage/app/installed.lock');
$appKey = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? getenv('APP_KEY');
if (!$installed && !$appKey) {
    $tempKey = 'base64:'.base64_encode(random_bytes(32));
    putenv("APP_KEY={$tempKey}");
    $_ENV['APP_KEY'] = $tempKey;
    $_SERVER['APP_KEY'] = $tempKey;
    
    // Force debug mode if not installed to see errors
    putenv("APP_DEBUG=true");
    $_ENV['APP_DEBUG'] = true;
    $_SERVER['APP_DEBUG'] = true;
}

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/install',
            '/install/*',
            '/webhooks/wompi',
        ]);
        $middleware->alias([
            'installer.guard' => \App\Http\Middleware\InstallerGuard::class,
            'install.check' => \App\Http\Middleware\CheckInstallation::class,
            'auth.session' => \App\Http\Middleware\AuthSession::class,
            'portal.client' => \App\Http\Middleware\ClientPortalAccess::class,
        ]);
        $middleware->prependToGroup('web', \App\Http\Middleware\CheckInstallation::class);
        $middleware->prependToPriorityList(
            \App\Http\Middleware\CheckInstallation::class,
            \Illuminate\Session\Middleware\StartSession::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

// Shared-hosting SAFE package support:
// If Laravel lives in /core and the public web root is the parent folder,
// force public_path() to that parent so uploads/assets/PDF fonts resolve correctly.
$parentPath = dirname($basePath);
$isCoreLayout = basename($basePath) === 'core' && is_file($parentPath . '/index.php');
if ($isCoreLayout) {
    $app->usePublicPath($parentPath);
}

return $app;
