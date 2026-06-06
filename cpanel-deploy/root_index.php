<?php

define('LARAVEL_START', microtime(true));

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

$diagLog = __DIR__.'/storage/logs/bootstrap-fatal.log';

$writeDiag = static function ($title, $message, $file = null, $line = null) use ($diagLog): void {
    $out = '['.date('Y-m-d H:i:s')."] ".$title."\n".$message;
    if ($file !== null) {
        $out .= "\nFile: ".$file;
    }
    if ($line !== null) {
        $out .= "\nLine: ".$line;
    }
    $out .= "\n------------------------------\n";
    @file_put_contents($diagLog, $out, FILE_APPEND);
};

register_shutdown_function(static function () use ($writeDiag): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $writeDiag('PHP fatal before Laravel bootstrap', $error['message'], $error['file'], $error['line']);
    }
});

try {
    @file_put_contents($diagLog, '['.date('Y-m-d H:i:s')."] index.php reached\n", FILE_APPEND);

    if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require __DIR__.'/vendor/autoload.php';

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once __DIR__.'/bootstrap/app.php';

    $app->usePublicPath(__DIR__);
    $app->useStoragePath(__DIR__.'/storage');

    $app->handleRequest(\Illuminate\Http\Request::capture());
} catch (Throwable $e) {
    $writeDiag('Throwable during bootstrap', $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    echo 'Bootstrap error. Check storage/logs/bootstrap-fatal.log';
}
