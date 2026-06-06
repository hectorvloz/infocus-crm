<?php
/**
 * ══════════════════════════════════════════════════════════
 *  InFocus CRM — Punto de entrada para cPanel
 * ══════════════════════════════════════════════════════════
 *
 *  INSTRUCCIONES:
 *  1. Sube toda la carpeta del proyecto a: /home/USUARIO/infocus_crm/
 *  2. Copia ESTE ARCHIVO (renombrado a index.php) en:  public_html/
 *     (o en la carpeta raíz del dominio/subdominio en cPanel)
 *  3. Copia también el archivo .htaccess a esa misma carpeta public_html/
 *  4. Ajusta la constante APP_PATH de abajo si tu ruta es diferente.
 *
 *  Estructura esperada en el servidor:
 *
 *  /home/USUARIO/
 *  ├── public_html/
 *  │   ├── index.php          ← este archivo
 *  │   └── .htaccess          ← el .htaccess de abajo
 *  └── infocus_crm/           ← toda la app aquí
 *      ├── app/
 *      ├── bootstrap/
 *      ├── config/
 *      ├── public/            ← assets, uploads, etc.
 *      ├── vendor/
 *      └── ...
 *
 *  IMPORTANTE: Ajusta la ruta APP_PATH si la carpeta no se llama
 *  "infocus_crm" o está en otra ubicación.
 */

define('LARAVEL_START', microtime(true));

// ── Ruta absoluta a la carpeta raíz de la app (arriba de public_html) ──
// __DIR__ apunta a public_html, entonces subimos un nivel con dirname()
define('APP_PATH', dirname(__DIR__) . '/infocus_crm');

// Bootstrap Laravel
require APP_PATH . '/vendor/autoload.php';

$app = require_once APP_PATH . '/bootstrap/app.php';

// Apuntar storage al directorio correcto
$app->useStoragePath(APP_PATH . '/storage');

// El kernel HTTP procesa la petición desde public_html
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
