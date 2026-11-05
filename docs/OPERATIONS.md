# Operación y Despliegue

## Entornos

### Local

Ruta típica:

```text
/Users/hector/Proyectos/Infocus/crm oficial codigo
```

Arranque local:

```bash
php artisan serve
```

URL usual:

```text
http://127.0.0.1:8000
```

### Producción cPanel

La aplicación está preparada para vivir en la raíz pública del dominio/subdominio con:

```text
index.php
.htaccess
app/
bootstrap/
config/
resources/
routes/
storage/
vendor/
```

## Deploy por GitHub

Repositorio:

```text
https://github.com/hectorvloz/infocus-crm
```

Flujo local:

```bash
git status
git add .
git commit -m "Descripcion del cambio"
git push
```

Flujo en cPanel si se usa Git Version Control:

```bash
git pull
php artisan optimize:clear
php artisan view:cache
```

Si Composer está disponible:

```bash
composer install --no-dev --optimize-autoloader
```

Si Composer no está disponible, mantener `vendor/` versionado o subirlo con el paquete.

## Deploy por ZIP Limpio

Para instalación desde cero:

```bash
bash cpanel-deploy/build-cpanel-cleaninstall.sh
```

El ZIP generado:

- excluye `.env` real
- excluye datos reales
- excluye logs/cachés
- incluye `vendor/`
- crea estructura mínima de `storage`
- prepara `.env` de instalación

Advertencia: este paquete es para instalación limpia. Si ya existe producción con datos reales, no se debe sobreescribir `storage/app/private` sin respaldo.

## Deploy Parcial en Producción

Cuando solo se corrige código, subir/reemplazar archivos puntuales y limpiar caché:

```bash
php artisan optimize:clear
php artisan view:cache
```

Ejemplo para fixes de PDF:

```text
app/Helpers/CurrencyHelper.php
app/Http/Controllers/FacturasController.php
app/Http/Controllers/PortalController.php
resources/views/ventas/facturas_print.blade.php
```

## Permisos Requeridos

En producción deben ser escribibles:

```text
storage/
storage/app/private/
storage/app/public/
storage/app/dompdf-temp/
storage/fonts/
storage/framework/cache/data/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/
```

## Cron

Configurar en cPanel cada minuto:

```bash
* * * * * /usr/local/bin/php /RUTA/DEL/CRM/artisan schedule:run >> /dev/null 2>&1
```

Ejemplo:

```bash
* * * * * /usr/local/bin/php /home/usuario/proyecto.estudioindigo.com.co/artisan schedule:run >> /dev/null 2>&1
```

## Backups

El módulo de respaldos está en:

```text
/ajustes/respaldos
```

Incluye datos JSON y manifiesto de conteos. Debe usarse antes de:

- actualizaciones grandes
- cambios de estructura
- restauraciones
- migraciones de hosting

Archivos críticos:

```text
storage/app/private/*.json
public/uploads/
```

## PDF de Facturas

El PDF de facturas usa:

```text
resources/views/ventas/facturas_print.blade.php
barryvdh/laravel-dompdf
public/fonts/Poppins-*.ttf
storage/fonts
storage/app/dompdf-temp
```

Si falla con error 500:

1. Revisar permisos de `storage/fonts` y `storage/app/dompdf-temp`.
2. Ejecutar `php artisan optimize:clear`.
3. Confirmar que `public/fonts/Poppins-*.ttf` exista.
4. Revisar `storage/logs/laravel.log`.

## Correo SMTP

Hay dos niveles:

- SMTP principal de empresa.
- SMTP exclusivo de facturas opcional.

Regla de facturas:

- siempre intenta enviar PDF adjunto
- si falla SMTP de facturas, intenta SMTP principal con el mismo adjunto
- si no puede generar/adjuntar PDF, no envía correo incompleto

## Comandos de Mantenimiento

```bash
php artisan optimize:clear
php artisan view:cache
php artisan route:list
php artisan schedule:run
```

## Checklist Antes de Producción

- `.env` configurado correctamente.
- `APP_DEBUG=false`.
- `APP_URL` con dominio real.
- SMTP probado.
- Cron activo.
- Permisos de `storage` y `bootstrap/cache`.
- Backup tomado.
- PDF de factura probado.
- Correo de factura probado.
- Portal de cliente probado.

## MCP para asistentes de IA (Claude, Codex, Gemini, ChatGPT)

Infocus expone un servidor MCP en `https://proyecto.estudioindigo.com.co/mcp`. Solo lo pueden usar los administradores.

### Activación en el servidor (una sola vez)

Desde la Terminal de cPanel, en la carpeta del CRM:

```bash
php artisan package:discover         # registra los paquetes nuevos (laravel/mcp, passport)
php artisan migrate --force          # crea las tablas oauth_* de Passport
php artisan passport:keys            # crea storage/oauth-private.key y oauth-public.key
chmod 600 storage/oauth-*.key
php artisan optimize:clear
```

Las llaves `storage/oauth-*.key` no se suben a git y no se deben compartir.

### Uso

- **Configuración → MCP · Asistentes IA:** crea un token por cada herramienta (Claude Code, Codex, Gemini CLI o Claude Desktop). La pantalla trae las instrucciones listas para copiar. El token se muestra una sola vez, y cada conexión se puede revocar.
- **claude.ai y ChatGPT:** agrega un conector personalizado con la URL `/mcp`. Se autoriza iniciando sesión en Infocus (OAuth); no necesita token.
- **Acciones en dos pasos:** enviar correos, enviar facturas y registrar pagos devuelven primero una vista previa y un código. Solo se ejecutan con `confirmar_accion`, el código sirve una vez y vence en 5 minutos.
- **Historial:** cada llamada queda en `storage/app/private/mcp_activity.json` (últimas 2.000) y se ve en la misma pantalla.
- **Sin borrado:** desde el MCP no se puede borrar nada.
