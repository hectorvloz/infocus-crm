# InFocus CRM — Guía de despliegue en cPanel
## Archivos generados

| Archivo | Destino en servidor | Propósito |
|---|---|---|
| `build-cpanel.sh` | Ejecutar en local | Genera el ZIP de despliegue |
| `public_html_index.php` | `/public_html/index.php` | Bootstrap de Laravel para cPanel |
| `public_html_htaccess.txt` | `/public_html/.htaccess` | Rewrite rules y seguridad |

---

## Estructura en el servidor

```
/home/USUARIO/
├── public_html/            ← raíz web del dominio
│   ├── index.php           ← copia de public_html_index.php
│   ├── .htaccess           ← copia de public_html_htaccess.txt
│   └── uploads/            ← assets de la app
│
└── infocus_crm/            ← la app completa (fuera de la web)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── public/
    ├── storage/
    ├── vendor/
    ├── .env                ← ¡configurar antes de usar!
    └── ...
```

---

## Pasos rápidos

```bash
# 1. Desde la raíz del proyecto, ejecuta:
bash cpanel-deploy/build-cpanel.sh

# 2. Sube el ZIP generado a tu cPanel
# 3. Extrae y coloca los archivos según la estructura de arriba
# 4. Configura .env con DB, APP_KEY y APP_URL
# 5. Abre el dominio → redirige a /install automáticamente
```

---

## Variables .env mínimas para producción

```env
APP_NAME="InFocus CRM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
APP_KEY=base64:...   # genera con: php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nombre_bd
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_bd

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

## Si usas subdominio en cPanel

Cambia la ruta `APP_PATH` en `public_html/index.php`:

```php
// Para subdominio: /home/USUARIO/subdominio.tudominio.com
define('APP_PATH', dirname(__DIR__) . '/infocus_crm');
// La ruta siempre es relativa al public_html del dominio
```
