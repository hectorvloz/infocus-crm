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
