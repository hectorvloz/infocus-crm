# Arquitectura del Sistema

## Resumen

Infocus CRM está construido como una aplicación Laravel monolítica. La interfaz se renderiza principalmente con Blade y JavaScript local por vista. La persistencia operativa se maneja en archivos JSON dentro de `storage/app/private` mediante repositorios propios, lo que reduce dependencia de base de datos para entidades del negocio y facilita respaldos portables en cPanel.

## Vista General

```text
Usuario / Cliente
      |
      v
Rutas Laravel (routes/web.php)
      |
      v
Controladores HTTP
      |
      +--> Repositorios JSON (FileStore / TimelineStore)
      +--> Servicios de soporte (TemplateMail, IA, roles)
      +--> DomPDF para PDFs
      +--> Mailer SMTP
      +--> Integraciones externas
      |
      v
Vistas Blade + JS + CSS
```

## Capas

### 1. Entrada HTTP

Archivo principal: `routes/web.php`.

Agrupa:

- rutas autenticadas del CRM con `auth.session`
- instalador con `installer.guard`
- autenticación de usuarios y Google OAuth
- portal de cliente autenticado
- portal por token
- facturas públicas
- webhooks públicos

### 2. Controladores

Ubicación: `app/Http/Controllers`.

Responsabilidades:

- recibir y validar requests
- coordinar repositorios y servicios
- construir respuestas Blade, JSON, descargas o redirects
- ejecutar acciones de negocio de cada módulo

Controladores destacados:

- `FacturasController`: facturas, pagos, recurrencias, emails, PDF público/interno.
- `PortalController`: portal cliente, pagos externos, documentos, mensajes, ZIP de facturas.
- `SettingsController`: configuración, SMTP, respaldos, roles, integraciones e IA.
- `ProyectosController`: kanban, tareas, tiempos y archivos.
- `AiController`: chat IA y ejecución de acciones.
- `CorreoController`: envío manual, plantillas e historial.

### 3. Persistencia

El núcleo usa JSON en `storage/app/private`.

Repositorios:

- `App\Repositories\FileStore`: CRUD básico sobre archivos JSON.
- `App\Repositories\TimelineStore`: timeline por cliente.

Ventajas:

- backups simples
- despliegue ligero en cPanel
- datos portables
- menor dependencia de migraciones para módulos operativos

Precauciones:

- `storage/app/private` contiene datos reales y no se versiona.
- Antes de actualizaciones grandes se debe generar respaldo.
- En producción requiere permisos de escritura.

### 4. Servicios de Soporte

Ubicación: `app/Support`.

- `TemplateMail`: render y envío de correos transaccionales, SMTP principal y SMTP de facturas.
- `RoleAccess`: mapa de permisos por rol/ruta.
- `Ai/*`: proveedores IA, filtro de datos sensibles y ejecución de acciones.

### 5. Vistas

Ubicación: `resources/views`.

Características:

- Blade server-rendered.
- Tailwind desde CDN.
- Interacciones complejas con JavaScript dentro de vistas grandes.
- Layout global en `resources/views/layouts/app.blade.php`.
- Layout de configuración en `resources/views/layouts/settings.blade.php`.

### 6. PDFs

Motor: `barryvdh/laravel-dompdf`.

Vistas PDF principales:

- `resources/views/ventas/facturas_print.blade.php`
- `resources/views/reportes/export_pdf.blade.php`
- `resources/views/contratos/print.blade.php`

Factura PDF:

- usa Poppins si están disponibles las fuentes en `public/fonts`
- evita depender de rutas públicas no resueltas en cPanel
- usa carpetas runtime `storage/fonts` y `storage/app/dompdf-temp`

### 7. Correo

El correo se centraliza en `TemplateMail` y configuraciones de `settings.json`.

Flujos:

- SMTP principal de empresa.
- SMTP exclusivo para facturas opcional.
- Fallback de facturas al SMTP principal si falla el SMTP de facturas.
- Copia al remitente para no enviar a ciegas.
- Historial en `email_history.json`.

### 8. Cron y Automatizaciones

Comandos en `app/Console/Commands`.

Procesos principales:

- facturas recurrentes
- emisión programada de facturas
- recordatorios de vencimiento
- recordatorios de reuniones
- recordatorios de leads
- resúmenes de horas
- alertas críticas

Entrada recomendada:

```bash
php artisan schedule:run
```

Cada minuto desde cPanel.

## Integraciones

### Google

- Login OAuth de administradores.
- Google Calendar para reuniones.
- Portal por magic link con Google.

### Pagos

- Stripe
- PayPal
- Wompi

### IA

Proveedores soportados desde `app/Support/Ai`:

- OpenAI
- Gemini
- DeepSeek

La IA puede responder, proponer acciones y ejecutar cambios controlados mediante `AiActionExecutor`.

## Principios de Diseño Técnico

- Mantener datos reales fuera de Git.
- Mantener el CRM funcional en hosting compartido.
- Evitar dependencias de build frontend.
- Priorizar respaldos simples y recuperables.
- Centralizar correo y PDF para evitar flujos inconsistentes.
- Mantener compatibilidad con cPanel aun cuando Composer/SSH sean limitados.
