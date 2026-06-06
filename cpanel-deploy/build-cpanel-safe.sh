#!/usr/bin/env bash
# InFocus CRM - paquete cPanel robusto para extraer directo en la raiz del subdominio.
# Estructura final:
#   / (raiz publica)
#   |- index.php
#   |- .htaccess
#   |- uploads, favicon, robots...
#   |- core/   (aplicacion Laravel completa)

set -e

OUTPUT="deploy_infocus_crm_SAFE_$(date +%Y%m%d_%H%M%S).zip"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "================================================="
echo "  InFocus CRM - Generando paquete SAFE cPanel"
echo "================================================="
echo "Proyecto : $PROJECT_DIR"
echo "Salida   : $OUTPUT"
echo ""

cd "$PROJECT_DIR"
PHP_BIN="$(command -v php || echo '')"
if [ -z "$PHP_BIN" ]; then
  echo "ERROR: No se encontro php en PATH." >&2
  exit 1
fi

echo "[1/4] Limpiando caches locales..."
$PHP_BIN artisan optimize:clear 2>/dev/null || true

echo "[2/4] Armando estructura SAFE..."
TMP_DIR="$(mktemp -d)"
ROOT_DIR="$TMP_DIR/root"
APP_DIR="$ROOT_DIR/core"
mkdir -p "$APP_DIR"

# Copiar app Laravel a core/
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
  "$PROJECT_DIR/" "$APP_DIR/"

# Copiar solo assets publicos a la raiz publica
rsync -a \
  --exclude="index.php" \
  --exclude=".htaccess" \
  "$PROJECT_DIR/public/" "$ROOT_DIR/"

cp "$SCRIPT_DIR/safe_root_index.php" "$ROOT_DIR/index.php"
cp "$SCRIPT_DIR/safe_root_htaccess.txt" "$ROOT_DIR/.htaccess"

APP_KEY="$($PHP_BIN -r 'echo "base64:".base64_encode(random_bytes(32));')"
cat > "$APP_DIR/.env" <<EOF
APP_NAME="Infocus CRM"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=true
APP_URL=http://proyecto.estudioindigo.com.co
APP_INSTALLED=false

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=infocus_crm
DB_USERNAME=infocus_user
DB_PASSWORD=secret

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GOOGLE_ADMIN_EMAILS=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"
EOF

echo "[3/4] Comprimiendo ZIP..."
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
echo "Extrae este ZIP directo en la raiz del subdominio."
