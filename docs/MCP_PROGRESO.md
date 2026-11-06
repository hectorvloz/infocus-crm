# Avance del MCP de Infocus y pendientes

Última actualización: 5 de noviembre de 2026.

## Qué está hecho

### Conexión
- Servidor MCP en `/mcp` funcionando con Claude (token u OAuth) y Gemini (OAuth). Se configura en Configuración → MCP (botón "Activar MCP" crea tablas y llaves de Passport sin terminal).
- Dominios de redirección OAuth permitidos en `config/mcp.php` (Claude, ChatGPT, Google/Gemini incluidos sus hosts sandbox y test, localhost).
- Si una app es rechazada al registrarse por OAuth, `LogRejectedOAuthRegistration` deja el motivo en `storage/logs/laravel.log`.

### Herramientas (43, contando confirmar_accion)
- Lectura: `resumen_negocio`, `resumen_semanal`, `buscar_clientes`, `ver_cliente`, `listar_leads`, `ver_lead`, `buscar_facturas`, `ver_factura`, `listar_gastos` (solo con permiso de gastos), `listar_proyectos`, `ver_proyecto`, `ver_tarea`, `agenda_reuniones`, `buscar_contratos`, `ver_plantillas_correo`, `listar_notas`, `ver_nota`, `ver_recordatorios`, `ver_cambios_recientes`.
- Escritura directa: `crear_cliente`, `agregar_nota_cliente`, `crear_lead`, `actualizar_lead`, `convertir_lead_cliente`, `crear_factura_borrador`, `registrar_gasto`, `crear_tarea`, `crear_contrato_borrador`, `actualizar_proyecto`, `actualizar_tarea` (incluye mover de columna y asignar responsables), `agregar_nota_proyecto` (comentarios en tareas), `agregar_subtareas`, `actualizar_subtarea`, `crear_nota`, `editar_nota`, `crear_recordatorio`, `completar_recordatorio`, `restaurar_cambio`.
- Dos pasos (vista previa + código + `confirmar_accion`): `preparar_correo`, `preparar_envio_factura`, `preparar_pago`, `preparar_reunion` (Meet o llamada con lead o cliente; envía invitación).

### Restaurar ediciones de la IA
- Cada edición del MCP sobre notas, proyectos/tareas/subtareas, leads y recordatorios guarda la versión anterior en `mcp_history.json` (30 días, máx. 500 entradas). Implementación: `app/Mcp/Support/McpHistory.php`.
- `ver_cambios_recientes` lista los cambios con `cambio_id`; `restaurar_cambio` deja el registro como estaba. Si alguien lo editó después, avisa y exige `forzar=true`. La restauración también queda en el historial (se puede deshacer).
- No cubre registros creados por el MCP (restaurarlos sería borrarlos) ni notas de cliente (timeline).

### Recordatorios
- Se guardan en `header_reminders.json` con la clave `user:` + slug del correo (igual que `ProfileController`). La pantalla ahora recarga los recordatorios al volver a la pestaña para no pisar los creados por el MCP.

## Pendiente en este repo
- Hacer "Update from Remote" en cPanel después del último push (Héctor).
- Probar en producción: crear/editar/restaurar una nota, `preparar_reunion` con Meet automático (requiere Google Calendar conectado en Integraciones).
- Reconectar Google Calendar en Infocus y revocar el token viejo en myaccount.google.com/permissions (el refresh token estuvo expuesto antes de la corrección de seguridad).
- Actualizar dependencias de Symfony con avisos de seguridad (correr `composer update` local, commitear `vendor/`).
- Ideas siguientes: registrar tiempo en tareas (`ProyectosController::tiempoManual`), mover leads de etapa con orden (`LeadsController::mover`), búsqueda de cotizaciones.

## Pendiente fuera de este repo (sitio Indigo Web, `/Volumes/Dev/Proyectos/Indigo/Indigo Web`)
- Llenar en el servidor `/home/indigo/indigo-config/asesoria.php` los valores `PEGAR_CLIENT_ID`, `PEGAR_CLIENT_SECRET`, `PEGAR_REFRESH_TOKEN` (obtener el refresh token con `python3 scripts/google_oauth.py CLIENT_ID CLIENT_SECRET`). Luego probar una reserva completa en `/asesoria/`.
- Cuando ya no se necesite SFTP: borrar la llave SSH `mcp_Infocus` en cPanel y localmente (`ssh-add -d ~/.ssh/mcp_infocus && rm ~/.ssh/mcp_infocus`).
