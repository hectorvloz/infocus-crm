#!/usr/bin/env bash
# ══════════════════════════════════════════════════════════════════════════
#  InFocus CRM — Script de empaque para despliegue en cPanel
#  Uso: bash build-cpanel.sh
#  Requisitos: zip, composer (en PATH)
# ══════════════════════════════════════════════════════════════════════════

set -e

APP_NAME="infocus_crm"
OUTPUT="deploy_${APP_NAME}_$(date +%Y%m%d_%H%M%S).zip"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"  # sube un nivel desde cpanel-deploy/

echo "================================================="
echo "  InFocus CRM — Generando paquete para cPanel"
echo "================================================="
echo "Proyecto : $PROJECT_DIR"
echo "Salida   : $OUTPUT"
echo ""

# ── 1. Instalar/actualizar dependencias de producción ─────────────────────
echo "[1/4] Verificando dependencias..."
cd "$PROJECT_DIR"
COMPOSER_BIN=$(command -v composer || command -v composer.phar || echo "")
if [ -n "$COMPOSER_BIN" ]; then
  echo "     Ejecutando composer install --no-dev..."
  $COMPOSER_BIN install --no-dev --optimize-autoloader --quiet
elif [ -d "$PROJECT_DIR/vendor" ]; then
  echo "     composer no encontrado — usando vendor/ existente (OK para cPanel)."
else
  echo "     ERROR: No se encontró composer ni la carpeta vendor/. Instala composer primero." >&2
  exit 1
fi

# ── 2. Limpiar cachés locales antes de empaquetar ─────────────────────────
# Los archivos en bootstrap/cache guardan rutas absolutas de esta máquina.
# En cPanel esas rutas no existen y Laravel puede fallar al compilar vistas.
echo "[2/4] Limpiando cachés locales para un paquete portable..."
php artisan optimize:clear 2>/dev/null || true

# ── 3. Crear el ZIP ───────────────────────────────────────────────────────
echo "[3/4] Creando archivo ZIP..."

# Carpeta temporal para el paquete
TMP_DIR="$(mktemp -d)"
APP_DIR="$TMP_DIR/$APP_NAME"
PUB_DIR="$TMP_DIR/public_html"
mkdir -p "$APP_DIR" "$PUB_DIR"

# Copiar toda la app excepto lo innecesario en producción
rsync -a \
  --exclude=".git" \
  --exclude=".env" \
  --exclude="node_modules" \
  --exclude="cpanel-deploy" \
  --exclude="*.zip" \
  --exclude="storage/app/installed.lock" \
  --exclude="storage/framework/cache/data/*" \
  --exclude="storage/logs/*" \
  --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" \
  --exclude="bootstrap/cache/*.php" \
  --exclude="tests/" \
  --exclude=".phpunit.cache" \
  "$PROJECT_DIR/" "$APP_DIR/"

# Crear .env de ejemplo si no existe
if [ ! -f "$APP_DIR/.env" ]; then
  cp "$APP_DIR/.env.example" "$APP_DIR/.env" 2>/dev/null || true
fi

# ── Preparar public_html ─────────────────────────────────────────────────
# index.php adaptado para cPanel
cp "$SCRIPT_DIR/public_html_index.php" "$PUB_DIR/index.php"

# .htaccess
cp "$SCRIPT_DIR/public_html_htaccess.txt" "$PUB_DIR/.htaccess"

# Copiar assets estáticos de public/ a public_html/
rsync -a \
  --exclude="index.php" \
  "$APP_DIR/public/" "$PUB_DIR/"

# ── Crear ZIP final ───────────────────────────────────────────────────────
cd "$TMP_DIR"
zip -r "$PROJECT_DIR/$OUTPUT" . -x "*.DS_Store" -x "__MACOSX/*" -q

# Limpiar temporal
rm -rf "$TMP_DIR"

# ── 4. Mantener el entorno local limpio ───────────────────────────────────
echo "[4/4] Manteniendo caché local limpia..."
cd "$PROJECT_DIR"
php artisan optimize:clear 2>/dev/null || true

echo ""
echo "================================================="
echo "  ✓ Paquete generado: $OUTPUT"
echo "================================================="
echo ""
echo "INSTRUCCIONES DE DESPLIEGUE EN CPANEL:"
echo ""
echo "  1. Sube '$OUTPUT' a tu servidor mediante el Administrador de Archivos"
echo "     o FTP/SFTP."
echo ""
echo "  2. Extrae el ZIP. Obtendrás dos carpetas:"
echo "     ├── $APP_NAME/     → sube a /home/USUARIO/$APP_NAME/"
echo "     └── public_html/   → sube CONTENIDO a /home/USUARIO/public_html/"
echo "         (o a la carpeta del dominio/subdominio en cPanel)"
echo ""
echo "  3. Crea el archivo .env en /home/USUARIO/$APP_NAME/.env"
echo "     con tus datos reales (DB, APP_KEY, APP_URL, etc.)"
echo "     Puedes usar el asistente /install desde el navegador."
echo ""
echo "  4. Genera APP_KEY si no la tienes:"
echo "     php /home/USUARIO/$APP_NAME/artisan key:generate"
echo ""
echo "  5. Ajusta permisos de storage y bootstrap/cache:"
echo "     chmod -R 775 /home/USUARIO/$APP_NAME/storage"
echo "     chmod -R 775 /home/USUARIO/$APP_NAME/bootstrap/cache"
echo ""
echo "  6. Abre tu dominio en el navegador. Si la app no está instalada"
echo "     se redirigirá a /install automáticamente."
echo ""
