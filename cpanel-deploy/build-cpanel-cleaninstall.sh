#!/usr/bin/env bash
# InFocus CRM - paquete limpio para instalar desde cero en cPanel.
# Extraer directo en la raiz publica del subdominio.

set -e

APP_NAME="infocus_crm_root_CLEANINSTALL"
OUTPUT="deploy_${APP_NAME}_$(date +%Y%m%d_%H%M%S).zip"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "================================================="
echo "  InFocus CRM - Generando paquete limpio cPanel"
echo "================================================="
echo "Proyecto : $PROJECT_DIR"
echo "Salida   : $OUTPUT"
echo ""

cd "$PROJECT_DIR"
PHP_BIN="$(command -v php || echo '')"
COMPOSER_BIN="$(command -v composer || command -v composer.phar || echo '')"

if [ -z "$PHP_BIN" ]; then
  echo "ERROR: No se encontro php en PATH." >&2
  exit 1
fi

if [ -n "$COMPOSER_BIN" ]; then
  echo "[1/5] Preparando dependencias composer..."
  "$COMPOSER_BIN" install --no-dev --optimize-autoloader --quiet
elif [ -d "$PROJECT_DIR/vendor" ]; then
  echo "[1/5] composer no encontrado - usando vendor/ existente."
else
  echo "ERROR: No se encontro composer ni vendor/." >&2
  exit 1
fi

echo "[2/5] Limpiando caches locales..."
"$PHP_BIN" artisan optimize:clear 2>/dev/null || true

echo "[3/5] Armando copia limpia..."
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
  --exclude="storage/app/private/*.bak*" \
  --exclude="storage/app/private/*.zip" \
  --exclude="storage/app/private/*.pdf" \
  --exclude="storage/app/private/*.png" \
  --exclude="storage/app/private/*.jpg" \
  --exclude="storage/app/private/*.jpeg" \
  --exclude="storage/app/private/*.webp" \
  --exclude="storage/app/public/*" \
  --exclude="storage/framework/cache/data/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  --exclude="storage/logs/*" \
  --exclude="bootstrap/cache/*.php" \
  --exclude="tests/" \
  --exclude=".phpunit.cache" \
  "$PROJECT_DIR/" "$ROOT_DIR/"

rsync -a \
  --exclude="index.php" \
  --exclude=".htaccess" \
  --exclude="storage" \
  --exclude="uploads/*" \
  "$PROJECT_DIR/public/" "$ROOT_DIR/"

cp "$SCRIPT_DIR/root_index.php" "$ROOT_DIR/index.php"
cp "$SCRIPT_DIR/root_htaccess.txt" "$ROOT_DIR/.htaccess"

mkdir -p \
  "$ROOT_DIR/storage/app/private" \
  "$ROOT_DIR/storage/app/dompdf-temp" \
  "$ROOT_DIR/storage/app/public" \
  "$ROOT_DIR/storage/framework/cache/data" \
  "$ROOT_DIR/storage/framework/sessions" \
  "$ROOT_DIR/storage/framework/views" \
  "$ROOT_DIR/storage/logs" \
  "$ROOT_DIR/storage/fonts" \
  "$ROOT_DIR/bootstrap/cache"

find "$ROOT_DIR/storage/app/private" -type f -name "*.json" -exec sh -c 'printf "[]" > "$1"' sh {} \;

cat > "$ROOT_DIR/storage/app/private/settings.json" <<'JSON'
[{"id":"settings","company_name":"Infocus CRM","base_currency":"USD","show_decimals":false,"company_timezone":"America/Bogota"}]
JSON

cat > "$ROOT_DIR/storage/app/private/roles.json" <<'JSON'
[{"id":"admin","name":"Admin","description":"Acceso total","permissions":["*"],"created_at":null,"updated_at":null}]
JSON

find "$ROOT_DIR/storage/framework/cache/data" "$ROOT_DIR/storage/framework/sessions" "$ROOT_DIR/storage/framework/views" "$ROOT_DIR/storage/logs" -type f ! -name ".gitignore" -delete

if [ ! -f "$ROOT_DIR/.env" ]; then
  cp "$ROOT_DIR/.env.example" "$ROOT_DIR/.env" 2>/dev/null || touch "$ROOT_DIR/.env"
fi

APP_KEY="$("$PHP_BIN" -r 'echo "base64:".base64_encode(random_bytes(32));')"
"$PHP_BIN" -r '
$path = $argv[1];
$values = [
    "APP_NAME" => "\"Infocus CRM\"",
    "APP_ENV" => "production",
    "APP_KEY" => $argv[2],
    "APP_DEBUG" => "false",
    "APP_URL" => "https://tudominio.com",
    "APP_INSTALLED" => "false",
    "APP_LOCALE" => "es",
    "APP_FALLBACK_LOCALE" => "es",
    "APP_FAKER_LOCALE" => "es_CO",
    "LOG_CHANNEL" => "stack",
    "DB_CONNECTION" => "mysql",
    "DB_HOST" => "localhost",
    "DB_PORT" => "3306",
    "DB_DATABASE" => "infocus_crm",
    "DB_USERNAME" => "infocus_user",
    "DB_PASSWORD" => "",
    "SESSION_DRIVER" => "file",
    "CACHE_STORE" => "file",
    "QUEUE_CONNECTION" => "sync",
    "MAIL_MAILER" => "log",
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

cat > "$ROOT_DIR/INSTALACION_CPANEL.txt" <<'TXT'
InFocus CRM - Instalacion limpia en cPanel

1. Sube este ZIP a la raiz publica del dominio o subdominio.
2. Extraelo alli mismo. Debe quedar index.php en esa raiz.
3. Asegura permisos de escritura en storage/ y bootstrap/cache/.
4. Abre tu dominio. El instalador debe abrir /install.
5. Completa la base de datos, empresa y usuario administrador.
6. Configura cron en cPanel:
   * * * * * /usr/local/bin/php /RUTA/A/TU/CRM/artisan schedule:run >> /dev/null 2>&1

Este paquete va sin clientes, facturas, proyectos, reuniones, notas, sesiones, logs ni archivos subidos.
TXT

echo "[4/5] Comprimiendo ZIP..."
cd "$ROOT_DIR"
zip -r "$PROJECT_DIR/$OUTPUT" . -x "*.DS_Store" -x "__MACOSX/*" -q
rm -rf "$TMP_DIR"

echo "[5/5] Manteniendo cache local limpia..."
cd "$PROJECT_DIR"
"$PHP_BIN" artisan optimize:clear 2>/dev/null || true

echo ""
echo "================================================="
echo "  Paquete generado: $OUTPUT"
echo "================================================="
