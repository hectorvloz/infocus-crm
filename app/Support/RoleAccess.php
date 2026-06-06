<?php

namespace App\Support;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleAccess
{
    private const ALWAYS_ALLOWED_ROUTE_PATTERNS = [
        'logout',
        'profile.*',
        'api.header.*',
        'branding.asset',
    ];

    private const ROUTE_PERMISSIONS = [
        'dashboard*' => 'panel.read',
        'api.ai.*' => 'panel.read',
        'mis-notas.*' => 'mis-notas.read',
        'api.mis-notas.*' => 'mis-notas.read',

        'clientes.index' => 'clientes.read',
        'clientes.show' => 'clientes.read',
        'clientes.export' => 'clientes.read',
        'clientes.mensajes.index' => 'clientes.read',
        'api.clientes.index' => 'clientes.read',
        'clientes.create' => 'clientes.create',
        'clientes.store' => 'clientes.create',
        'api.clientes.quick.store' => 'clientes.create',
        'clientes.edit' => 'clientes.update',
        'clientes.update' => 'clientes.update',
        'clientes.addNota' => 'clientes.update',
        'clientes.mensajes.reply' => 'clientes.update',
        'clientes.destroy' => 'clientes.delete',

        'reuniones.index' => 'reuniones.read',
        'reuniones.store' => 'reuniones.create',
        'reuniones.update' => 'reuniones.update',
        'reuniones.destroy' => 'reuniones.delete',

        'documentos.index' => 'documentos.read',
        'documentos.preview' => 'documentos.read',
        'documentos.download' => 'documentos.read',
        'documentos.folders.download' => 'documentos.read',
        'documentos.folders.store' => 'documentos.create',
        'documentos.upload' => 'documentos.create',
        'documentos.folders.update' => 'documentos.update',
        'documentos.folders.visibility' => 'documentos.update',
        'documentos.update' => 'documentos.update',
        'documentos.move' => 'documentos.update',
        'documentos.folders.destroy' => 'documentos.delete',
        'documentos.destroy' => 'documentos.delete',

        'proyectos.index' => 'proyectos.read',
        'api.proyectos.index' => 'proyectos.read',
        'api.proyectos.show' => 'proyectos.read',
        'api.proyectos.archivados' => 'proyectos.read',
        'api.proyectos.responsables.search' => 'proyectos.read',
        'api.proyectos.active-timer' => 'proyectos.read',
        'api.proyectos.crear' => 'proyectos.create',
        'api.proyectos.tareas.agregar' => 'proyectos.create',
        'api.proyectos.tareas.notas.agregar' => 'proyectos.create',
        'api.proyectos.tareas.subtareas.agregar' => 'proyectos.create',
        'api.proyectos.actualizar' => 'proyectos.update',
        'api.proyectos.mover' => 'proyectos.update',
        'api.proyectos.tareas.toggle' => 'proyectos.update',
        'api.proyectos.tareas.actualizar' => 'proyectos.update',
        'api.proyectos.tareas.notas.actualizar' => 'proyectos.update',
        'api.proyectos.tareas.subtareas.toggle' => 'proyectos.update',
        'api.proyectos.tiempo.manual' => 'proyectos.update',
        'api.proyectos.archivo' => 'proyectos.update',
        'api.proyectos.stages.update' => 'proyectos.update',
        'api.proyectos.timer' => 'timer.proyectos',
        'api.proyectos.eliminar' => 'proyectos.delete',
        'api.proyectos.tareas.eliminar' => 'proyectos.delete',
        'api.proyectos.tareas.notas.eliminar' => 'proyectos.delete',
        'api.proyectos.tareas.subtareas.eliminar' => 'proyectos.delete',
        'api.proyectos.timer.eliminar' => 'proyectos.delete',
        'api.proyectos.archivo.eliminar' => 'proyectos.delete',

        'leads.index' => 'leads.read',
        'leads.export' => 'leads.read',
        'leads.import.form' => 'leads.read',
        'api.leads.show' => 'leads.read',
        'api.leads.timer.activo' => 'leads.read',
        'leads.create' => 'leads.create',
        'leads.store' => 'leads.create',
        'leads.import.store' => 'leads.create',
        'api.leads.notas.agregar' => 'leads.create',
        'api.leads.email.enviar' => 'leads.create',
        'api.leads.agenda.programar' => 'leads.create',
        'leads.edit' => 'leads.update',
        'leads.update' => 'leads.update',
        'api.leads.mover' => 'leads.update',
        'api.leads.tiempo.manual' => 'leads.update',
        'api.leads.timer.iniciar' => 'timer.leads',
        'api.leads.timer.pausar' => 'timer.leads',
        'api.leads.timer.reanudar' => 'timer.leads',
        'api.leads.timer.detener' => 'timer.leads',
        'api.leads.timer.eliminar' => 'timer.leads',
        'leads.destroy' => 'leads.delete',

        'facturas.index' => 'facturas.read',
        'facturas.show' => 'facturas.read',
        'facturas.print' => 'facturas.read',
        'facturas.download' => 'facturas.read',
        'api.tasa' => 'facturas.read',
        'facturas.create' => 'facturas.create',
        'facturas.store' => 'facturas.create',
        'api.facturas.duplicar' => 'facturas.create',
        'api.facturas.recurrencia' => 'facturas.create',
        'facturas.edit' => 'facturas.update',
        'facturas.update' => 'facturas.update',
        'api.facturas.pagar' => 'facturas.update',
        'api.facturas.draft' => 'facturas.update',
        'api.facturas.enviar' => 'facturas.read',
        'api.facturas.estado' => 'facturas.update',
        'facturas.destroy' => 'facturas.delete',

        'pagos.index' => 'pagos.read',
        'pagos.destroy' => 'pagos.delete',

        'cotizaciones.index' => 'cotizaciones.read',
        'cotizaciones.show' => 'cotizaciones.read',
        'cotizaciones.print' => 'cotizaciones.read',
        'cotizaciones.create' => 'cotizaciones.create',
        'cotizaciones.store' => 'cotizaciones.create',
        'cotizaciones.edit' => 'cotizaciones.update',
        'cotizaciones.update' => 'cotizaciones.update',
        'cotizaciones.destroy' => 'cotizaciones.delete',

        'productos.index' => 'productos.read',
        'api.productos.index' => 'productos.read',
        'productos.create' => 'productos.create',
        'productos.store' => 'productos.create',
        'productos.edit' => 'productos.update',
        'productos.update' => 'productos.update',
        'productos.destroy' => 'productos.delete',

        'contratos.index' => 'contratos.read',
        'contratos.show' => 'contratos.read',
        'contratos.pdf' => 'contratos.read',
        'contratos.create' => 'contratos.create',
        'contratos.store' => 'contratos.create',
        'contratos.edit' => 'contratos.update',
        'contratos.update' => 'contratos.update',
        'contratos.firmar' => 'contratos.update',
        'contratos.destroy' => 'contratos.delete',

        'correo.index' => 'correo.read',
        'correo.send' => 'correo.create',
        'correo.verify_cron' => 'correo.read',
        'correo.templates.store' => 'correo.create',
        'correo.templates.update' => 'correo.update',
        'correo.templates.destroy' => 'correo.delete',

        'gastos.index' => 'gastos.read',
        'gastos.export' => 'gastos.read',
        'proveedores.index' => 'gastos.read',
        'gastos.create' => 'gastos.create',
        'gastos.store' => 'gastos.create',
        'proveedores.create' => 'gastos.create',
        'proveedores.store' => 'gastos.create',
        'gastos.edit' => 'gastos.update',
        'gastos.update' => 'gastos.update',
        'gastos.budgets' => 'gastos.update',
        'proveedores.edit' => 'gastos.update',
        'proveedores.update' => 'gastos.update',
        'proveedores.categories.update' => 'gastos.update',
        'gastos.destroy' => 'gastos.delete',
        'proveedores.destroy' => 'gastos.delete',

        'reportes.index' => 'reportes.read',
        'reportes.export' => 'reportes.read',

        'settings.update' => 'ajustes.update',
        'settings.smtp.update' => 'ajustes.update',
        'settings.smtp.test' => 'ajustes.update',
        'settings.invoice.update' => 'ajustes.update',
        'settings.payment_methods.update' => 'ajustes.update',
        'settings.templates.update' => 'ajustes.update',
        'settings.roles.store' => 'ajustes.update',
        'settings.roles.update' => 'ajustes.update',
        'settings.roles.destroy' => 'ajustes.update',
        'settings.team.store' => 'ajustes.update',
        'settings.team.update' => 'ajustes.update',
        'settings.team.destroy' => 'ajustes.update',
        'settings.team.reminders.update' => 'ajustes.update',
        'settings.backup.restore' => 'ajustes.update',
        'settings.integrations.update' => 'ajustes.update',
        'settings.integrations.google.connect' => 'ajustes.update',
        'settings.integrations.google.callback' => 'ajustes.update',
        'settings.integrations.google.disconnect' => 'ajustes.update',
        'settings.*' => 'ajustes.read',
    ];

    private const FIRST_ALLOWED_ROUTES = [
        'panel.read' => 'dashboard',
        'mis-notas.read' => 'mis-notas.index',
        'clientes.read' => 'clientes.index',
        'reuniones.read' => 'reuniones.index',
        'documentos.read' => 'documentos.index',
        'proyectos.read' => 'proyectos.index',
        'leads.read' => 'leads.index',
        'facturas.read' => 'facturas.index',
        'pagos.read' => 'pagos.index',
        'cotizaciones.read' => 'cotizaciones.index',
        'productos.read' => 'productos.index',
        'contratos.read' => 'contratos.index',
        'correo.read' => 'correo.index',
        'gastos.read' => 'gastos.index',
        'reportes.read' => 'reportes.index',
        'ajustes.read' => 'settings.edit',
    ];

    public static function can(?object $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $roleId = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($roleId, ['admin', 'super_admin'], true)) {
            return true;
        }

        if ($roleId === 'client') {
            return false;
        }

        $permissions = self::permissionsForRole($roleId);

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }

    public static function canAny(?object $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public static function userCanAccessRoute(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName === '') {
            return true;
        }

        foreach (self::ALWAYS_ALLOWED_ROUTE_PATTERNS as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        $permission = self::permissionForRoute($routeName);
        if ($permission === null) {
            return true;
        }

        return self::can(Auth::user(), $permission);
    }

    public static function firstAllowedRoute(?object $user): string
    {
        foreach (self::FIRST_ALLOWED_ROUTES as $permission => $routeName) {
            if (self::can($user, $permission)) {
                return $routeName;
            }
        }

        return 'profile.show';
    }

    private static function permissionForRoute(string $routeName): ?string
    {
        foreach (self::ROUTE_PERMISSIONS as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    private static function permissionsForRole(string $roleId): array
    {
        static $cache = [];

        if (array_key_exists($roleId, $cache)) {
            return $cache[$roleId];
        }

        $role = (new FileStore('roles.json'))->find($roleId);
        $permissions = collect($role['permissions'] ?? [])
            ->filter()
            ->map(fn ($permission) => (string) $permission)
            ->values()
            ->all();

        return $cache[$roleId] = $permissions;
    }
}
