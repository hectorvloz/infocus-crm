<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\GastosController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ReunionesController;
use App\Http\Controllers\ProyectosController;
use App\Http\Controllers\CotizacionesController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ContratosController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MisNotasController;
use App\Http\Controllers\CorreoController;
use App\Http\Controllers\AiController;

use App\Http\Controllers\SettingsController;

Route::get('/uploads/branding/{file}', function (string $file) {
    abort_if(str_contains($file, '/') || str_contains($file, '\\'), 404);

    $candidates = [
        public_path('uploads/branding/' . $file),
        base_path('public/uploads/branding/' . $file),
    ];

    foreach (array_unique($candidates) as $path) {
        if (is_file($path)) {
            return response()->file($path);
        }
    }

    abort(404);
})->where('file', '[A-Za-z0-9._-]+')->name('branding.asset');

Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth.session');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alias')->middleware('auth.session');
Route::middleware('auth.session')->group(function () {
    Route::view('/mis-notas', 'mis-notas.index')->name('mis-notas.index');
    Route::get('/api/mis-notas', [MisNotasController::class, 'index'])->name('api.mis-notas.index');
    Route::post('/api/mis-notas', [MisNotasController::class, 'save'])->name('api.mis-notas.save');
    Route::get('/api/mis-notas/colaboradores', [MisNotasController::class, 'collaborators'])->name('api.mis-notas.collaborators');
    Route::get('/api/ia/chats', [AiController::class, 'index'])->name('api.ai.chats.index');
    Route::get('/api/ia/chats/{id}', [AiController::class, 'show'])->name('api.ai.chats.show');
    Route::post('/api/ia/chat', [AiController::class, 'chat'])->name('api.ai.chat');
    Route::post('/api/ia/actions/execute', [AiController::class, 'executeAction'])->name('api.ai.actions.execute');
    Route::post('/api/ia/actions/undo', [AiController::class, 'undoAction'])->name('api.ai.actions.undo');
    Route::delete('/api/ia/chats/{id}', [AiController::class, 'destroy'])->name('api.ai.chats.destroy');
    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/export', [ClientesController::class, 'export'])->name('clientes.export');
    Route::get('/clientes/crear', [ClientesController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [ClientesController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{id}', [ClientesController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{id}/editar', [ClientesController::class, 'edit'])->name('clientes.edit');
    Route::post('/clientes/{id}', [ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [ClientesController::class, 'destroy'])->name('clientes.destroy');
    Route::post('/clientes/{id}/notas', [ClientesController::class, 'addNota'])->name('clientes.addNota');
    Route::get('/reuniones', [ReunionesController::class, 'index'])->name('reuniones.index');
    Route::post('/reuniones', [ReunionesController::class, 'store'])->name('reuniones.store');
    Route::post('/reuniones/{id}', [ReunionesController::class, 'update'])->name('reuniones.update');
    Route::delete('/reuniones/{id}', [ReunionesController::class, 'destroy'])->name('reuniones.destroy');
    Route::get('/documentos', [DocumentosController::class, 'index'])->name('documentos.index');
    Route::post('/documentos/carpetas', [DocumentosController::class, 'storeFolder'])->name('documentos.folders.store');
    Route::put('/documentos/carpetas', [DocumentosController::class, 'updateFolder'])->name('documentos.folders.update');
    Route::patch('/documentos/carpetas/visibilidad', [DocumentosController::class, 'updateFolderVisibility'])->name('documentos.folders.visibility');
    Route::delete('/documentos/carpetas', [DocumentosController::class, 'destroyFolder'])->name('documentos.folders.destroy');
    Route::get('/documentos/carpetas/download', [DocumentosController::class, 'downloadFolder'])->name('documentos.folders.download');
    Route::post('/documentos/subir', [DocumentosController::class, 'upload'])->name('documentos.upload');
    Route::post('/documentos/mover', [DocumentosController::class, 'move'])->name('documentos.move');
    Route::put('/documentos/{id}', [DocumentosController::class, 'update'])->name('documentos.update');
    Route::get('/documentos/{id}/preview', [DocumentosController::class, 'preview'])->name('documentos.preview');
    Route::get('/documentos/{id}/download', [DocumentosController::class, 'download'])->name('documentos.download');
    Route::delete('/documentos/{id}', [DocumentosController::class, 'destroy'])->name('documentos.destroy');
    Route::get('/api/clientes', [ClientesController::class, 'apiIndex'])->name('api.clientes.index');
    Route::post('/api/clientes/quick', [ClientesController::class, 'apiQuickStore'])->name('api.clientes.quick.store');
    Route::get('/api/proyectos', [ProyectosController::class, 'index'])->name('api.proyectos.index');
    Route::get('/api/proyectos/archivados', [ProyectosController::class, 'archivados'])->name('api.proyectos.archivados');
    Route::post('/api/proyectos/mover', [ProyectosController::class, 'mover'])->name('api.proyectos.mover');
    Route::post('/api/proyectos/crear', [ProyectosController::class, 'crear'])->name('api.proyectos.crear');
    Route::post('/api/proyectos/actualizar', [ProyectosController::class, 'actualizar'])->name('api.proyectos.actualizar');
    Route::post('/api/proyectos/eliminar', [ProyectosController::class, 'eliminar'])->name('api.proyectos.eliminar');
    Route::get('/api/proyectos/{id}', [ProyectosController::class, 'show'])->name('api.proyectos.show');
    Route::get('/api/proyectos/responsables/search', [ProyectosController::class, 'responsables'])->name('api.proyectos.responsables.search');
    Route::post('/api/proyectos/tareas/agregar', [ProyectosController::class, 'tareaAgregar'])->name('api.proyectos.tareas.agregar');
    Route::post('/api/proyectos/tareas/toggle', [ProyectosController::class, 'tareaToggle'])->name('api.proyectos.tareas.toggle');
    Route::post('/api/proyectos/tareas/actualizar', [ProyectosController::class, 'tareaActualizar'])->name('api.proyectos.tareas.actualizar');
    Route::post('/api/proyectos/tareas/ia-apoyo', [ProyectosController::class, 'tareaIaApoyo'])->name('api.proyectos.tareas.ia-apoyo');
    Route::post('/api/proyectos/tareas/mover', [ProyectosController::class, 'tareaMover'])->name('api.proyectos.tareas.mover');
    Route::post('/api/proyectos/tareas/eliminar', [ProyectosController::class, 'tareaEliminar'])->name('api.proyectos.tareas.eliminar');
    Route::post('/api/proyectos/tareas/restaurar', [ProyectosController::class, 'tareaRestaurar'])->name('api.proyectos.tareas.restaurar');
    Route::post('/api/proyectos/tareas/eliminar-archivada', [ProyectosController::class, 'tareaEliminarArchivada'])->name('api.proyectos.tareas.eliminar-archivada');
    Route::post('/api/proyectos/tareas/notas/agregar', [ProyectosController::class, 'tareaNotaAgregar'])->name('api.proyectos.tareas.notas.agregar');
    Route::post('/api/proyectos/tareas/notas/actualizar', [ProyectosController::class, 'tareaNotaActualizar'])->name('api.proyectos.tareas.notas.actualizar');
    Route::post('/api/proyectos/tareas/notas/eliminar', [ProyectosController::class, 'tareaNotaEliminar'])->name('api.proyectos.tareas.notas.eliminar');
    Route::post('/api/proyectos/tareas/subtareas/agregar', [ProyectosController::class, 'subtareaAgregar'])->name('api.proyectos.tareas.subtareas.agregar');
    Route::post('/api/proyectos/tareas/subtareas/actualizar', [ProyectosController::class, 'subtareaActualizar'])->name('api.proyectos.tareas.subtareas.actualizar');
    Route::post('/api/proyectos/tareas/subtareas/toggle', [ProyectosController::class, 'subtareaToggle'])->name('api.proyectos.tareas.subtareas.toggle');
    Route::post('/api/proyectos/tareas/subtareas/eliminar', [ProyectosController::class, 'subtareaEliminar'])->name('api.proyectos.tareas.subtareas.eliminar');
    Route::post('/api/proyectos/timer', [ProyectosController::class, 'timerAccion'])->name('api.proyectos.timer');
    Route::post('/api/proyectos/timer/eliminar', [ProyectosController::class, 'timerEliminar'])->name('api.proyectos.timer.eliminar');
    Route::post('/api/proyectos/tiempo/manual', [ProyectosController::class, 'tiempoManual'])->name('api.proyectos.tiempo.manual');
    Route::post('/api/proyectos/archivo', [ProyectosController::class, 'uploadArchivo'])->name('api.proyectos.archivo');
    Route::post('/api/proyectos/archivo/eliminar', [ProyectosController::class, 'eliminarArchivo'])->name('api.proyectos.archivo.eliminar');
    Route::post('/api/proyectos/stages', [ProyectosController::class, 'updateStages'])->name('api.proyectos.stages.update');
    Route::get('/proyectos', [ProyectosController::class, 'page'])->name('proyectos.index');
    Route::get('/proyectos/{boardSlug}', [ProyectosController::class, 'page'])->where('boardSlug', '[A-Za-z0-9-]+')->name('proyectos.board');
    Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/api/mi-perfil/horas', [ProfileController::class, 'workedHours'])->name('profile.worked-hours');
    Route::get('/api/header/notifications', [ProfileController::class, 'notifications'])->name('api.header.notifications');
    Route::get('/api/header/active-timer', [ProyectosController::class, 'activeTimer'])->name('api.header.active-timer');
    Route::post('/api/header/notifications/read-one', [ProfileController::class, 'markNotificationRead'])->name('api.header.notifications.read-one');
    Route::post('/api/header/notifications/read-all', [ProfileController::class, 'markAllNotificationsRead'])->name('api.header.notifications.read-all');
    Route::get('/leads', [LeadsController::class, 'index'])->name('leads.index');
    Route::get('/leads/crear', [LeadsController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadsController::class, 'store'])->name('leads.store');
    Route::get('/leads/{id}/editar', [LeadsController::class, 'edit'])->name('leads.edit');
    Route::post('/leads/{id}', [LeadsController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{id}', [LeadsController::class, 'destroy'])->name('leads.destroy');
    Route::post('/api/leads/mover', [LeadsController::class, 'mover'])->name('api.leads.mover');
    Route::get('/api/leads/{id}', [LeadsController::class, 'showJson'])->name('api.leads.show');
    Route::post('/api/leads/notas/agregar', [LeadsController::class, 'notaAgregar'])->name('api.leads.notas.agregar');
    Route::post('/api/leads/email/enviar', [LeadsController::class, 'emailEnviar'])->name('api.leads.email.enviar');
    Route::post('/api/leads/agenda/programar', [LeadsController::class, 'agendaProgramar'])->name('api.leads.agenda.programar');
    Route::post('/api/leads/timer/iniciar', [LeadsController::class, 'timerIniciar'])->name('api.leads.timer.iniciar');
    Route::post('/api/leads/timer/pausar', [LeadsController::class, 'timerPausar'])->name('api.leads.timer.pausar');
    Route::post('/api/leads/timer/reanudar', [LeadsController::class, 'timerReanudar'])->name('api.leads.timer.reanudar');
    Route::post('/api/leads/timer/detener', [LeadsController::class, 'timerDetener'])->name('api.leads.timer.detener');
    Route::post('/api/leads/timer/eliminar', [LeadsController::class, 'timerEliminar'])->name('api.leads.timer.eliminar');
    Route::get('/api/leads/timer/activo', [LeadsController::class, 'timerActivo'])->name('api.leads.timer.activo');
    Route::post('/api/leads/tiempo/manual', [LeadsController::class, 'tiempoManual'])->name('api.leads.tiempo.manual');
    Route::get('/leads/export', [LeadsController::class, 'export'])->name('leads.export');
    Route::get('/leads/import', [LeadsController::class, 'importForm'])->name('leads.import.form');
    Route::post('/leads/import', [LeadsController::class, 'importStore'])->name('leads.import.store');
    Route::get('/facturas', [FacturasController::class, 'index'])->name('facturas.index');
    Route::get('/facturas/crear', [FacturasController::class, 'create'])->name('facturas.create');
    Route::post('/facturas', [FacturasController::class, 'store'])->name('facturas.store');
    Route::get('/facturas/{id}', [FacturasController::class, 'show'])->name('facturas.show');
    Route::get('/facturas/{id}/editar', [FacturasController::class, 'edit'])->name('facturas.edit');
    Route::post('/facturas/{id}', [FacturasController::class, 'update'])->name('facturas.update');
    Route::delete('/facturas/{id}', [FacturasController::class, 'destroy'])->name('facturas.destroy');
    Route::get('/facturas/{id}/imprimir', [FacturasController::class, 'imprimir'])->name('facturas.print');
    Route::get('/facturas/{id}/descargar', [FacturasController::class, 'descargarPdf'])->name('facturas.download');
    Route::post('/api/facturas/pagar', [FacturasController::class, 'pagar'])->name('api.facturas.pagar');
    Route::patch('/api/facturas/{id}/borrador', [FacturasController::class, 'saveDraft'])->name('api.facturas.draft');
    Route::get('/api/tasa', [FacturasController::class, 'tasa'])->name('api.tasa');

    // Cotizaciones
    Route::get('/cotizaciones', [CotizacionesController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/crear', [CotizacionesController::class, 'create'])->name('cotizaciones.create');
    Route::post('/cotizaciones', [CotizacionesController::class, 'store'])->name('cotizaciones.store');
    Route::get('/cotizaciones/{id}', [CotizacionesController::class, 'show'])->name('cotizaciones.show');
    Route::get('/cotizaciones/{id}/editar', [CotizacionesController::class, 'edit'])->name('cotizaciones.edit');
    Route::put('/cotizaciones/{id}', [CotizacionesController::class, 'update'])->name('cotizaciones.update');
    Route::delete('/cotizaciones/{id}', [CotizacionesController::class, 'destroy'])->name('cotizaciones.destroy');
    Route::get('/cotizaciones/{id}/imprimir', [CotizacionesController::class, 'imprimir'])->name('cotizaciones.print');

    // Contratos
    Route::get('/contratos', [ContratosController::class, 'index'])->name('contratos.index');
    Route::get('/contratos/crear', [ContratosController::class, 'create'])->name('contratos.create');
    Route::post('/contratos', [ContratosController::class, 'store'])->name('contratos.store');
    Route::get('/contratos/{id}', [ContratosController::class, 'show'])->name('contratos.show');
    Route::get('/contratos/{id}/editar', [ContratosController::class, 'edit'])->name('contratos.edit');
    Route::put('/contratos/{id}', [ContratosController::class, 'update'])->name('contratos.update');
    Route::delete('/contratos/{id}', [ContratosController::class, 'destroy'])->name('contratos.destroy');
    Route::post('/contratos/{id}/firmar', [ContratosController::class, 'firmar'])->name('contratos.firmar');
    Route::get('/contratos/{id}/pdf', [ContratosController::class, 'pdf'])->name('contratos.pdf');

    // Correo
    Route::get('/correo', [CorreoController::class, 'index'])->name('correo.index');
    Route::post('/correo/enviar', [CorreoController::class, 'send'])->name('correo.send');
    Route::post('/correo/verificar-cron', [CorreoController::class, 'verifyCron'])->name('correo.verify_cron');
    Route::post('/correo/plantillas', [CorreoController::class, 'storeTemplate'])->name('correo.templates.store');
    Route::put('/correo/plantillas/{id}', [CorreoController::class, 'updateTemplate'])->name('correo.templates.update');
    Route::delete('/correo/plantillas/{id}', [CorreoController::class, 'destroyTemplate'])->name('correo.templates.destroy');

    // Productos
    Route::get('/productos', [ProductosController::class, 'index'])->name('productos.index');
    Route::get('/productos/crear', [ProductosController::class, 'create'])->name('productos.create');
    Route::post('/productos', [ProductosController::class, 'store'])->name('productos.store');
    Route::get('/productos/{id}/editar', [ProductosController::class, 'edit'])->name('productos.edit');
    Route::put('/productos/{id}', [ProductosController::class, 'update'])->name('productos.update');
    Route::delete('/productos/{id}', [ProductosController::class, 'destroy'])->name('productos.destroy');
    Route::get('/api/productos', [ProductosController::class, 'apiIndex'])->name('api.productos.index');
    
    Route::post('/api/facturas/duplicar/{id}', [FacturasController::class, 'duplicar'])->name('api.facturas.duplicar');
    Route::post('/api/facturas/recurrencia', [FacturasController::class, 'programarRecurrencia'])->name('api.facturas.recurrencia');
    Route::post('/api/facturas/enviar', [FacturasController::class, 'enviarEmail'])->name('api.facturas.enviar');
    Route::post('/api/facturas/estado', [FacturasController::class, 'cambiarEstado'])->name('api.facturas.estado');
    Route::get('/configuracion', [\App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/configuracion', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    Route::get('/ajustes/smtp', [SettingsController::class, 'smtp'])->name('settings.smtp');
    Route::put('/ajustes/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
    Route::post('/ajustes/smtp/test', [SettingsController::class, 'testSmtp'])->name('settings.smtp.test');

    Route::get('/ajustes/facturacion', [SettingsController::class, 'invoice'])->name('settings.invoice');
    Route::put('/ajustes/facturacion', [SettingsController::class, 'updateInvoice'])->name('settings.invoice.update');

    Route::get('/ajustes/pagos', [SettingsController::class, 'paymentMethods'])->name('settings.payment_methods');
    Route::put('/ajustes/pagos', [SettingsController::class, 'updatePaymentMethods'])->name('settings.payment_methods.update');

    Route::get('/ajustes/plantillas', [SettingsController::class, 'templates'])->name('settings.templates');
    Route::put('/ajustes/plantillas', [SettingsController::class, 'updateTemplates'])->name('settings.templates.update');

    Route::resource('/ajustes/roles', \App\Http\Controllers\RolesController::class)->names('settings.roles');
    Route::resource('/ajustes/equipo', \App\Http\Controllers\TeamController::class)->names('settings.team');
    Route::put('/ajustes/equipo-recordatorios', [\App\Http\Controllers\TeamController::class, 'updateReminders'])->name('settings.team.reminders.update');

    Route::get('/ajustes/respaldos', [SettingsController::class, 'backup'])->name('settings.backup');
    Route::get('/ajustes/respaldos/descargar', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::post('/ajustes/respaldos/restaurar', [SettingsController::class, 'restoreBackup'])->name('settings.backup.restore');

    Route::get('/ajustes/integraciones', [SettingsController::class, 'integrations'])->name('settings.integrations');
    Route::put('/ajustes/integraciones', [SettingsController::class, 'updateIntegrations'])->name('settings.integrations.update');
    Route::get('/ajustes/integraciones/google-calendar/conectar', [SettingsController::class, 'googleCalendarConnect'])->name('settings.integrations.google.connect');
    Route::get('/ajustes/integraciones/google-calendar/callback', [SettingsController::class, 'googleCalendarCallback'])->name('settings.integrations.google.callback');
    Route::post('/ajustes/integraciones/google-calendar/desconectar', [SettingsController::class, 'googleCalendarDisconnect'])->name('settings.integrations.google.disconnect');
    Route::get('/ajustes/ia', [SettingsController::class, 'ai'])->name('settings.ai');
    Route::put('/ajustes/ia', [SettingsController::class, 'updateAi'])->name('settings.ai.update');
    Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
    Route::delete('/pagos/{facturaId}/{pagoIndex}', [PagosController::class, 'destroy'])->name('pagos.destroy');

    // Gastos y Proveedores
    Route::get('/gastos', [GastosController::class, 'index'])->name('gastos.index');
    
    // Dashboard Actions
    Route::post('/dashboard/update-goal', [DashboardController::class, 'updateGoal'])->name('dashboard.updateGoal');
    Route::post('/dashboard/update-sales-hours', [DashboardController::class, 'updateSalesHours'])->name('dashboard.updateSalesHours');
    Route::get('/gastos/crear', [GastosController::class, 'create'])->name('gastos.create');
    Route::post('/gastos', [GastosController::class, 'store'])->name('gastos.store');
    Route::get('/gastos/{id}/editar', [GastosController::class, 'edit'])->name('gastos.edit');
    Route::put('/gastos/{id}', [GastosController::class, 'update'])->name('gastos.update');
    Route::delete('/gastos/{id}', [GastosController::class, 'destroy'])->name('gastos.destroy');
    Route::get('/gastos/exportar/csv', [GastosController::class, 'export'])->name('gastos.export');
    Route::post('/gastos/presupuestos', [GastosController::class, 'updateBudgets'])->name('gastos.budgets');

    Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/crear', [ProveedoresController::class, 'create'])->name('proveedores.create');
    Route::post('/proveedores', [ProveedoresController::class, 'store'])->name('proveedores.store');
    Route::post('/proveedores/categorias', [ProveedoresController::class, 'updateCategories'])->name('proveedores.categories.update');
    Route::get('/proveedores/{id}/editar', [ProveedoresController::class, 'edit'])->name('proveedores.edit');
    Route::put('/proveedores/{id}', [ProveedoresController::class, 'update'])->name('proveedores.update');
    Route::delete('/proveedores/{id}', [ProveedoresController::class, 'destroy'])->name('proveedores.destroy');

    Route::get('/reportes', [ReportesController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [ReportesController::class, 'export'])->name('reportes.export');
});

Route::middleware('installer.guard')->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install.show');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
    Route::post('/install/test-db', [InstallController::class, 'testDb'])->name('install.test-db');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('login.google.callback');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth.session');

// Authenticated Client Portal
Route::middleware(['auth', 'portal.client'])->prefix('portal')->name('portal.auth.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboardAuth'])->name('dashboard');
    Route::get('/factura/{invoiceId}', [PortalController::class, 'invoiceAuth'])->name('invoice');
    Route::get('/factura/{invoiceId}/pdf', [PortalController::class, 'invoicePdfAuth'])->name('invoice.pdf');
    Route::get('/documento/{docId}/download', [PortalController::class, 'downloadDocumentAuth'])->name('document.download');
    Route::post('/mensajes', [PortalController::class, 'storeMessageAuth'])->name('mensajes.store');
    Route::post('/proyectos/tareas/notas', [PortalController::class, 'storeProjectTaskNoteAuth'])->name('proyectos.tareas.notas.store');
    Route::get('/cambiar-clave', [PortalController::class, 'showChangePassword'])->name('change-password');
    Route::post('/cambiar-clave', [PortalController::class, 'storeChangePassword'])->name('change-password.store');
    Route::get('/facturas/zip', [PortalController::class, 'zipFacturasAuth'])->name('zip-facturas');
    Route::get('/pay/{invoiceId}', [PortalController::class, 'payCheckoutAuth'])->name('pay.checkout');
    Route::get('/stripe/{invoiceId}', [PortalController::class, 'stripeCheckoutAuth'])->name('stripe.checkout');
    Route::get('/stripe-success', [PortalController::class, 'stripeSuccessAuth'])->name('stripe.success');
    Route::get('/paypal-success', [PortalController::class, 'paypalSuccessAuth'])->name('paypal.success');
    Route::get('/wompi-success', [PortalController::class, 'wompiSuccessAuth'])->name('wompi.success');
});

// Client Portal Routes (token-based)
Route::prefix('portal/{id}/{token}')->name('portal.')->group(function() {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/factura/{invoiceId}/pdf', [PortalController::class, 'invoicePdf'])->name('invoice.pdf');
    Route::get('/factura/{invoiceId}', [PortalController::class, 'invoice'])->name('invoice');
    Route::get('/documento/{docId}/download', [PortalController::class, 'downloadDocument'])->name('document.download');
    Route::post('/mensajes', [PortalController::class, 'storeMessageToken'])->name('mensajes.store');
    Route::post('/proyectos/tareas/notas', [PortalController::class, 'storeProjectTaskNoteToken'])->name('proyectos.tareas.notas.store');
    Route::get('/facturas/zip', [PortalController::class, 'zipFacturasToken'])->name('zip-facturas');
    Route::get('/pay/{invoiceId}', [PortalController::class, 'payCheckout'])->name('pay.checkout');
    Route::get('/stripe/{invoiceId}', [PortalController::class, 'stripeCheckout'])->name('stripe.checkout');
    Route::get('/stripe-success', [PortalController::class, 'stripeSuccess'])->name('stripe.success');
    Route::get('/paypal-success', [PortalController::class, 'paypalSuccess'])->name('paypal.success');
    Route::get('/wompi-success', [PortalController::class, 'wompiSuccess'])->name('wompi.success');
});

// Factura pública (sin login)
Route::get('/f/{id}', [FacturasController::class, 'publico'])->name('facturas.public');
Route::get('/f/{id}/pdf', [FacturasController::class, 'publicoPdf'])->name('facturas.public.pdf');
Route::get('/f/{invoiceId}/pagar', [PortalController::class, 'publicPayCheckout'])->name('public.pay.checkout');
Route::get('/f/{invoiceId}/stripe-success', [PortalController::class, 'publicStripeSuccess'])->name('public.stripe.success');
Route::get('/f/{invoiceId}/paypal-success', [PortalController::class, 'publicPaypalSuccess'])->name('public.paypal.success');
Route::get('/f/{invoiceId}/wompi-success', [PortalController::class, 'publicWompiSuccess'])->name('public.wompi.success');

// Magic Link para portal de cliente
Route::get('/portal-acceso', [PortalController::class, 'showMagicLink'])->name('portal.magic-link');
Route::post('/portal-acceso', [PortalController::class, 'sendMagicLink'])->name('portal.magic-link.send');
Route::get('/portal-acceso/google', [PortalController::class, 'redirectToGoogleForPortal'])->name('portal.magic-link.google');
Route::get('/portal-acceso/google/callback', [PortalController::class, 'handleGooglePortalCallback'])->name('portal.magic-link.google.callback');

// Webhooks publicos
Route::post('/webhooks/wompi', [PortalController::class, 'wompiWebhook'])->name('webhooks.wompi');

// Admin: mensajes del portal
Route::middleware('auth.session')->prefix('clientes/{id}/mensajes')->name('clientes.mensajes.')->group(function () {
    Route::get('/', [PortalController::class, 'adminMensajes'])->name('index');
    Route::post('/responder', [PortalController::class, 'adminReply'])->name('reply');
});
