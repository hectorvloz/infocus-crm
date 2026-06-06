<?php

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/core';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);
$app->useStoragePath($appPath.'/storage');

$app->handleRequest(\Illuminate\Http\Request::capture());
