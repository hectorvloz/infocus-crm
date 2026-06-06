# Seguridad y Datos Sensibles

## Principio General

El repositorio debe contener código, assets de interfaz y documentación. No debe contener secretos, credenciales, datos reales de clientes, facturas, usuarios, correos, documentos privados ni logs.

## Nunca Versionar

```text
.env
storage/app/private/*
storage/logs/*
storage/framework/sessions/*
storage/framework/views/*
storage/framework/cache/data/*
storage/fonts/*
storage/app/dompdf-temp/*
public/uploads/profile/*
public/uploads/proyectos/*
public/uploads/documentos/*
deploy_*
*.zip
```

## Permitido en Git

```text
.env.example
public/fonts/
public/favicon.ico
public/uploads/branding/
app/
config/
resources/
routes/
cpanel-deploy/
docs/
```

## Secretos Relevantes

Variables sensibles del `.env`:

```text
APP_KEY
DB_DATABASE
DB_USERNAME
DB_PASSWORD
MAIL_HOST
MAIL_USERNAME
MAIL_PASSWORD
GOOGLE_CLIENT_SECRET
OPENAI_API_KEY
GEMINI_API_KEY
DEEPSEEK_API_KEY
STRIPE_SECRET
PAYPAL_SECRET
WOMPI_PRIVATE_KEY
```

Si un secreto se comparte accidentalmente, se debe rotar desde el proveedor correspondiente.

## Google OAuth

Las credenciales de Google se configuran en `.env`:

```text
GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI
GOOGLE_ADMIN_EMAILS
```

Recomendaciones:

- mantener `GOOGLE_CLIENT_SECRET` fuera de Git
- restringir URIs autorizadas
- rotar el secreto si fue expuesto
- limitar administradores en `GOOGLE_ADMIN_EMAILS`

## Datos de Clientes

Los datos reales viven principalmente en:

```text
storage/app/private
public/uploads
```

Estos datos deben respaldarse, pero no subirse al repositorio.

## Roles y Acceso

El control de acceso se apoya en:

```text
app/Support/RoleAccess.php
app/Http/Middleware/AuthSession.php
app/Http/Middleware/ClientPortalAccess.php
```

Las rutas administrativas usan `auth.session`. El portal cliente usa autenticación normal o token de acceso.

## Portal de Clientes

Tipos de acceso:

- portal autenticado con usuario cliente
- portal por token
- factura pública por link

Buenas prácticas:

- tokens no deben compartirse públicamente fuera del cliente
- revisar logs de acceso del portal
- evitar exponer documentos sin validar cliente/token

## PDFs y Archivos

El PDF de factura no debe depender de recursos remotos para evitar fugas y errores en cPanel. Las fuentes se cargan localmente desde `public/fonts`.

Archivos privados deben descargarse mediante controladores, no exponerse directamente si pertenecen a clientes.

## GitHub

Repositorio actual:

```text
hectorvloz/infocus-crm
```

Visibilidad:

```text
PRIVATE
```

Antes de cada push:

```bash
git status
git diff --cached --name-only
git grep -n --cached "SECRET\\|PASSWORD\\|TOKEN\\|PRIVATE_KEY"
```

## Respuesta a Incidentes

Si se subió un secreto:

1. Revocar/rotar el secreto en el proveedor.
2. Actualizar `.env` en producción.
3. Revisar logs de acceso.
4. Eliminarlo del historial Git si aplica.
5. Forzar nuevas credenciales para integraciones críticas.
