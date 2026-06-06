#!/usr/bin/env bash
# InFocus CRM - paquete plano para descomprimir en la raiz del subdominio.

set -e

APP_NAME="infocus_crm_root"
OUTPUT="deploy_${APP_NAME}_$(date +%Y%m%d_%H%M%S).zip"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "================================================="
echo "  InFocus CRM - Generando paquete raiz cPanel"
echo "================================================="
echo "Proyecto : $PROJECT_DIR"
echo "Salida   : $OUTPUT"
echo ""

echo "[1/4] Verificando dependencias..."
cd "$PROJECT_DIR"
COMPOSER_BIN=$(command -v composer || command -v composer.phar || echo "")
PHP_BIN=$(command -v php || echo "")
if [ -n "$COMPOSER_BIN" ]; then
  echo "     Ejecutando composer install --no-dev..."
  $COMPOSER_BIN install --no-dev --optimize-autoloader --quiet
elif [ -d "$PROJECT_DIR/vendor" ]; then
  echo "     composer no encontrado - usando vendor/ existente."
else
  echo "     ERROR: No se encontro composer ni la carpeta vendor/." >&2
  exit 1
fi
if [ -z "$PHP_BIN" ]; then
  echo "     ERROR: No se encontro php en PATH." >&2
  exit 1
fi

echo "[2/4] Limpiando caches locales..."
$PHP_BIN artisan optimize:clear 2>/dev/null || true

echo "[3/4] Armando paquete plano..."
TMP_DIR="$(mktemp -d)"
ROOT_DIR="$TMP_DIR/root"
mkdir -p "$ROOT_DIR"

rsync -a \
  --exclude=".git" \
  --exclude=".env" \
  --exclude=".DS_Store" \
  --exclude="node_modules" \
  --exclude="cpanel-deploy" \
  --exclude="deploy_*" \
  --exclude="*.zip" \
  --exclude="public" \
  --exclude="storage/app/installed.lock" \
  --exclude="storage/framework/cache/data/*" \
  --exclude="storage/logs/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  --exclude="bootstrap/cache/*.php" \
  --exclude="tests/" \
  --exclude=".phpunit.cache" \
  "$PROJECT_DIR/" "$ROOT_DIR/"

rsync -a \
  --exclude="index.php" \
  "$PROJECT_DIR/public/" "$ROOT_DIR/"

cp "$SCRIPT_DIR/root_index.php" "$ROOT_DIR/index.php"
cp "$SCRIPT_DIR/root_htaccess.txt" "$ROOT_DIR/.htaccess"

if [ ! -f "$ROOT_DIR/.env" ]; then
  cp "$ROOT_DIR/.env.example" "$ROOT_DIR/.env" 2>/dev/null || true
fi

APP_KEY="$($PHP_BIN -r 'echo "base64:".base64_encode(random_bytes(32));')"
$PHP_BIN -r '
$path = $argv[1];
$values = [
    "APP_NAME" => "InFocus CRM",
    "APP_ENV" => "production",
    "APP_KEY" => $argv[2],
    "APP_DEBUG" => "true",
    "APP_URL" => "http://proyecto.estudioindigo.com.co",
    "APP_INSTALLED" => "false",
    "SESSION_DRIVER" => "file",
    "CACHE_STORE" => "file",
    "QUEUE_CONNECTION" => "sync",
];
$env = file_exists($path) ? file_get_contents($path) : "";
foreach ($values as $key => $value) {
    $line = $key."=".$value;
    if (preg_match("/^".preg_quote($key, "/")."=.*/m", $env)) {
        $env = preg_replace("/^".preg_quote($key, "/")."=.*/m", $line, $env);
    } else {
        $env .= (str_ends_with($env, "\n") || $env === "" ? "" : "\n").$line."\n";
    }
}
file_put_contents($path, $env);
' "$ROOT_DIR/.env" "$APP_KEY"

cd "$ROOT_DIR"
zip -r "$PROJECT_DIR/$OUTPUT" . -x "*.DS_Store" -x "__MACOSX/*" -q

rm -rf "$TMP_DIR"

echo "[4/4] Manteniendo cache local limpia..."
cd "$PROJECT_DIR"
$PHP_BIN artisan optimize:clear 2>/dev/null || true

echo ""
echo "================================================="
echo "  Paquete generado: $OUTPUT"
echo "================================================="
echo ""
echo "Sube este ZIP a la raiz del subdominio y extraelo alli."
echo "No crea carpeta public_html ni infocus_crm."
