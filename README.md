# Infocus CRM

Infocus CRM es una aplicación Laravel orientada a operación comercial y administrativa: clientes, leads, proyectos, facturación, pagos, reuniones, documentos, correo transaccional, portal de clientes, automatizaciones e IA asistida.

El proyecto está preparado para funcionar en hosting compartido tipo cPanel. Usa Laravel como capa de aplicación, Blade para la interfaz, almacenamiento local en archivos JSON para la mayoría de datos operativos y DomPDF para la generación de PDFs.

## Documentación

- [Arquitectura del sistema](docs/ARCHITECTURE.md)
- [Mapa del proyecto](docs/PROJECT_MAP.md)
- [Operación y despliegue](docs/OPERATIONS.md)
- [Seguridad y datos sensibles](docs/SECURITY.md)

## Stack Técnico

- PHP / Laravel
- Blade + Tailwind CDN + JavaScript embebido en vistas
- Repositorios JSON mediante `App\Repositories\FileStore`
- DomPDF para facturas/reportes PDF
- Laravel Mail / Symfony Mailer para SMTP
- Integraciones: Google OAuth/Calendar, Stripe, PayPal, Wompi, proveedores IA
- Hosting objetivo: cPanel con cron cada minuto

## Módulos Principales

- Dashboard operativo
- Clientes y timeline
- Leads y actividades comerciales
- Proyectos, tareas, tiempos y archivos
- Facturación, recurrencias, pagos y PDFs
- Gastos, proveedores y reportes
- Reuniones y recordatorios
- Documentos y carpetas
- Correo, plantillas e historial
- Portal de clientes por login o token
- Ajustes, roles, equipo, SMTP, respaldos e integraciones
- Asistente IA con acciones sobre el CRM

## Flujo de Desarrollo

```bash
git status
git add .
git commit -m "Descripcion del cambio"
git push
```

Antes de subir cambios a producción, limpiar/compilar vistas:

```bash
php artisan optimize:clear
php artisan view:cache
```

## Despliegue Rápido en cPanel

El proyecto incluye scripts en `cpanel-deploy/` para construir paquetes ZIP limpios. Para una instalación limpia:

```bash
bash cpanel-deploy/build-cpanel-cleaninstall.sh
```

El ZIP generado se extrae en la raíz pública del dominio/subdominio. Para producción con datos existentes, no usar un paquete limpio sin respaldo previo; en ese caso se actualizan solo archivos de código y se preserva `storage/app/private`.

## Cron Requerido

En cPanel configurar el cron cada minuto:

```bash
* * * * * /usr/local/bin/php /RUTA/DEL/CRM/artisan schedule:run >> /dev/null 2>&1
```

## Datos Sensibles

No se versionan:

- `.env`
- datos reales en `storage/app/private`
- sesiones, logs, cachés y zips de deploy
- archivos subidos por usuarios fuera de assets de branding

Ver [Seguridad y datos sensibles](docs/SECURITY.md).
