# Mapa del Proyecto

## Estructura Principal

```text
.
├── app/
│   ├── Console/Commands/       # Tareas programadas y automatizaciones
│   ├── Helpers/                # Helpers globales
│   ├── Http/
│   │   ├── Controllers/        # Controladores por módulo
│   │   └── Middleware/         # Middlewares de sesión, instalación y portal
│   ├── Mail/                   # Mailable genérico
│   ├── Models/                 # Modelo User
│   ├── Providers/              # Service providers
│   ├── Repositories/           # FileStore y TimelineStore
│   └── Support/                # Servicios: IA, correo, roles
├── bootstrap/                  # Bootstrap Laravel
├── config/                     # Configuración Laravel
├── cpanel-deploy/              # Scripts y plantillas para paquetes cPanel
├── database/                   # Migraciones, factories, seeders
├── public/                     # Web root, fuentes, favicon y assets públicos
├── resources/views/            # Blade templates
├── routes/                     # Rutas web y consola
├── storage/                    # Datos, cachés, sesiones, logs
└── vendor/                     # Dependencias PHP incluidas para cPanel
```

## Controladores por Módulo

```text
AuthController          Login, logout, password reset, Google OAuth
DashboardController     Dashboard, metas e indicadores
ClientesController      Clientes, notas, exportación y perfil cliente
LeadsController         Leads, kanban comercial, actividades, timer y email
ProyectosController     Proyectos, kanban, tareas, tiempos y archivos
FacturasController      Facturas, pagos, recurrencias, PDF y envío por email
PagosController         Vista/gestión de pagos
CotizacionesController  Cotizaciones y PDF
ContratosController     Contratos y PDF/firma
GastosController        Gastos, presupuestos y exportación
ProveedoresController   Proveedores y categorías
ReunionesController     Reuniones, invitados y calendario
DocumentosController    Documentos, carpetas, preview y descarga
CorreoController        Correo manual, plantillas e historial
PortalController        Portal cliente, pagos, documentos, mensajes y facturas
SettingsController      Ajustes, SMTP, backups, integraciones, IA
RolesController         Roles y permisos
TeamController          Usuarios/equipo y recordatorios
AiController            Chat IA y acciones
MisNotasController      Notas personales
ReportesController      Reportes y exportaciones
InstallController       Instalador inicial
```

## Repositorios y Datos JSON

Los datos operativos se almacenan en `storage/app/private`.

Archivos frecuentes:

```text
settings.json                  Configuración global
users.json                     Usuarios del sistema
roles.json                     Roles y permisos
clientes.json                  Clientes
leads.json                     Leads
proyectos.json                 Proyectos
tareas.json                    Tareas
facturas.json                  Facturas
pagos.json                     Pagos
cotizaciones.json              Cotizaciones
contratos.json                 Contratos
gastos.json                    Gastos
proveedores.json               Proveedores
reuniones.json                 Reuniones
documentos.json                Documentos
document_folders.json          Carpetas documentales
email_history.json             Historial de correos
email_templates.json           Plantillas
ai_chats.json                  Conversaciones IA
ai_action_logs.json            Logs de acciones IA
ai_memories.json               Memorias IA
recurring_invoice_runs.json    Ejecuciones de recurrencias
cron_status.json               Estado de cron
scheduler_state.json           Estado programador
portal_access_logs.json        Accesos al portal
```

## Vistas Principales

```text
layouts/app.blade.php          Layout principal CRM, header, IA, recordatorios
layouts/settings.blade.php     Layout ajustes
dashboard.blade.php            Dashboard
ventas/facturas_*.blade.php    Facturas: index/create/edit/show/print
portal/*.blade.php             Portal cliente
proyectos/index.blade.php      Vista principal de proyectos
documentos/index.blade.php     Gestor documental
correo/index.blade.php         Centro de correo
settings/*.blade.php           Ajustes del sistema
```

## Rutas Relevantes

### CRM autenticado

```text
/dashboard
/clientes
/leads
/proyectos
/facturas
/cotizaciones
/contratos
/gastos
/proveedores
/reuniones
/documentos
/correo
/reportes
/configuracion
/ajustes/*
```

### Facturas

```text
/facturas/{id}
/facturas/{id}/imprimir
/facturas/{id}/descargar
/api/facturas/enviar
/f/{id}
/f/{id}/pdf
/f/{invoiceId}/pagar
```

### Portal Cliente

```text
/portal
/portal/factura/{invoiceId}
/portal/factura/{invoiceId}/pdf
/portal/{id}/{token}
/portal/{id}/{token}/factura/{invoiceId}
/portal/{id}/{token}/factura/{invoiceId}/pdf
/portal-acceso
```

### Instalador

```text
/install
/install/test-db
```

## Comandos Programados

```text
RunScheduledCrmTasks
ProcessRecurringInvoices
SendScheduledIssueInvoices
SendInvoiceDueReminders
SendLeadAgendaReminders
SendMeetingReminders
SendWeeklyHoursSummary
SendMonthlyHoursSummary
SendSystemCriticalAlerts
BuildForProduction
```

## Archivos de Despliegue

```text
cpanel-deploy/build-cpanel-cleaninstall.sh
cpanel-deploy/build-cpanel-root.sh
cpanel-deploy/build-cpanel-safe.sh
cpanel-deploy/root_index.php
cpanel-deploy/root_htaccess.txt
```

## Archivos que no se deben versionar

```text
.env
storage/app/private/*
storage/logs/*
storage/framework/views/*
storage/framework/sessions/*
storage/fonts/*
storage/app/dompdf-temp/*
public/uploads/* excepto public/uploads/branding
deploy_*
*.zip
```
