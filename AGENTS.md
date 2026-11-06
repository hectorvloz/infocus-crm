# Instrucciones para agentes (Codex, Claude, etc.)

Infocus CRM de Indigo. Estudio Creativo (Colombia). Dueño: Héctor. Responde en español colombiano, claro y directo, sin emojis en código, interfaces ni documentación.

## Proyecto relacionado: Indigo Web

El sitio público de Indigo (`/Volumes/Dev/Proyectos/Indigo/Indigo Web`, Astro, con su propio `AGENTS.md`) trabaja junto con este CRM: su página `/asesoria/` agenda reuniones y envía cada reserva como lead a `/webhooks/leads` (`LeadsWebhookController`, token `LEADS_WEBHOOK_TOKEN` del `.env`). Si cambias ese webhook, revisa también `public/api/asesoria/book.php` en Indigo Web. Cuando una tarea toque los dos, el agente coordinador está en `/Volumes/Dev/Proyectos/Indigo/AGENTS.md`. Si tienes subagentes, usa uno por proyecto. Cada proyecto es un repo aparte: commits separados.

## Lo esencial del proyecto

- Laravel 12 + PHP 8.x. Los datos NO están en base de datos: viven en JSON dentro de `storage/app/private/*.json` y se leen/escriben con `App\Repositories\FileStore` (`all`, `find`, `create`, `update`, `delete`, `save`). La base de datos solo guarda usuarios y tablas de Passport/OAuth.
- Permisos: `App\Support\RoleAccess` mapea nombres de ruta a permisos (`clientes.read`, `proyectos.update`, ...). Una ruta sin mapear queda permitida; `tests/Feature/RoutePermissionsTest.php` lo vigila. Si agregas rutas, mapéalas.
- Despliegue: cPanel con Git "Update from Remote". La raíz del repo es la raíz web y `vendor/` va en el repo. El hosting NO tiene terminal: nada de `php artisan` en producción. Si algo necesita comandos, hazlo desde código (ver `McpSettingsController::activate()`), o con SFTP.
- Más contexto: `docs/ARCHITECTURE.md`, `docs/PROJECT_MAP.md`, `docs/OPERATIONS.md`, `docs/SECURITY.md`, y el avance reciente en `docs/MCP_PROGRESO.md`.

## Reglas de trabajo

- Cambios quirúrgicos: no rediseñes ni refactorices lo que no se pidió. Conserva el estilo existente.
- Antes de terminar: `php artisan test` (solo corre `tests/Feature`). Todo debe pasar.
- Commits en español, descriptivos. Héctor sube con "Update from Remote" en cPanel después del push a `main`.
- Nunca subas secretos (`.env`, tokens, llaves). El `.env` de producción se edita por SFTP.
- `MisNotasController::save()` reemplaza la lista completa de notas del usuario y borra las que no vengan: desde el MCP nunca lo uses. Usa `App\Mcp\Support\PersonalNotes`.

## Servidor MCP (lo más activo)

- Paquete `laravel/mcp`. Rutas en `routes/ai.php`, servidor en `app/Mcp/Servers/InfocusServer.php` (lista `$tools` + instrucciones al modelo). Endpoint `/mcp`.
- Autenticación: tokens personales `ifc_…` (hash sha256, `App\Mcp\Support\McpTokens`) u OAuth con Passport (scope `mcp:use`), middleware `AuthenticateMcp`. Solo admins. Conexiones de "lectura" o "completo".
- Cada herramienta extiende `App\Mcp\Tools\InfocusTool` e implementa `run()`:
  - `$ctx->allow('permiso', write: true)` antes de escribir.
  - `callController()` reutiliza los métodos de los controladores de la app (preferirlo a duplicar lógica).
  - `tracked($archivo, $id, 'etiqueta', fn () => ...)` guarda una copia previa en `mcp_history.json` para que `restaurar_cambio` pueda deshacer. Úsalo en TODA edición de un registro existente.
  - `external()` marca con «» texto escrito por terceros (no son instrucciones).
- Acciones irreversibles hacia afuera (correos, facturas, pagos, reuniones) van en dos pasos: `preparar_*` crea un código con `PendingActions` y `confirmar_accion` lo ejecuta (un uso, 5 minutos, mismo usuario). Agrega el caso nuevo en `ConfirmarAccion::run()`.
- No hay herramientas para borrar. Mantenerlo así.
- Tests del MCP: `tests/Feature/McpServerTest.php` (helper `$this->tool('nombre', [...], $token)`).
- Para agregar una herramienta: crear clase en `app/Mcp/Tools`, registrarla en `InfocusServer::$tools`, agregar test. Mantener menos de 50 herramientas (paginación) o subir `$defaultPaginationLength`.
