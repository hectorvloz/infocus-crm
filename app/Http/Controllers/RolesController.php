<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\FileStore;
use Illuminate\Support\Str;
use App\Support\TemplateMail;

class RolesController extends Controller
{
    protected FileStore $store;
    protected FileStore $usersStore;

    public function __construct()
    {
        $this->store = new FileStore('roles.json');
        $this->usersStore = new FileStore('users.json');
    }

    public function index()
    {
        $this->ensureSystemRoles();
        $roles = $this->store->all();

        return view('settings.roles.index', compact('roles'));
    }

    private function ensureSystemRoles(): void
    {
        $roles = collect($this->store->all() ?: []);

        $defaults = [
            [
                'id' => 'admin',
                'name' => 'Administrador',
                'description' => 'Acceso total a todas las funciones.',
                'permissions' => ['*'],
            ],
            [
                'id' => 'manager',
                'name' => 'Gerente',
                'description' => 'Acceso total a todas las funciones, excepto configuraciones.',
                'permissions' => ['*'],
            ],
            [
                'id' => 'employee',
                'name' => 'Vendedor',
                'description' => 'Puede gestionar leads, contratos, productos y servicios, cotizaciones y sus propias notas.',
                'permissions' => ['leads.read', 'contratos.read', 'productos.read', 'cotizaciones.read', 'mis-notas.read'],
            ],
        ];

        foreach ($defaults as $roleDef) {
            $existing = $roles->firstWhere('id', $roleDef['id']);
            if (!$existing) {
                $roles->push($roleDef);
                continue;
            }

            $roles = $roles->map(function ($role) use ($roleDef) {
                if (($role['id'] ?? '') !== $roleDef['id']) {
                    return $role;
                }

                $role['name'] = $roleDef['name'];
                $role['description'] = $roleDef['description'];
                $role['permissions'] = $roleDef['permissions'];

                return $role;
            });
        }

        $this->store->save($roles->values()->all());
    }

    public function create()
    {
        return view('settings.roles.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'dashboard_tabs' => 'nullable|array',
            'dashboard_tabs.*' => 'in:resumen,proyectos,ventas',
        ]);

        $permissions = collect($data['permissions'] ?? [])->filter()->values();
        $permissions = $permissions->reject(fn ($p) => str_starts_with((string) $p, 'dashboard.'));
        foreach (($data['dashboard_tabs'] ?? []) as $tab) {
            $permissions->push('dashboard.'.$tab);
        }
        $data['permissions'] = $permissions->unique()->values()->all();
        unset($data['dashboard_tabs']);
        
        $data['id'] = Str::slug($data['name']); // Use slug as ID
        
        // Ensure unique ID
        $existing = $this->store->find($data['id']);
        if ($existing) {
            $data['id'] = $data['id'] . '-' . uniqid();
        }

        $this->store->create($data);
        return redirect()->route('settings.roles.index')->with('success', 'Rol creado exitosamente.');
    }

    public function edit($id)
    {
        $role = $this->store->find($id);
        abort_if(!$role, 404);
        return view('settings.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $current = $this->store->find($id);
        abort_if(!$current, 404);

        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'dashboard_tabs' => 'nullable|array',
            'dashboard_tabs.*' => 'in:resumen,proyectos,ventas',
        ]);

        $permissions = collect($data['permissions'] ?? [])->filter()->values();
        $permissions = $permissions->reject(fn ($p) => str_starts_with((string) $p, 'dashboard.'));
        foreach (($data['dashboard_tabs'] ?? []) as $tab) {
            $permissions->push('dashboard.'.$tab);
        }
        $data['permissions'] = $permissions->unique()->values()->all();
        unset($data['dashboard_tabs']);

        $this->store->update($id, $data);

        try {
            $users = collect($this->usersStore->all())
                ->filter(fn ($u) => ($u['active'] ?? true) && (string) ($u['role'] ?? '') === (string) $id)
                ->filter(fn ($u) => filter_var($u['email'] ?? '', FILTER_VALIDATE_EMAIL))
                ->values();

            if ($users->isNotEmpty()) {
                $settings = TemplateMail::settings();
                $oldPerms = collect($current['permissions'] ?? [])->filter()->values()->all();
                $newPerms = collect($data['permissions'] ?? [])->filter()->values()->all();

                $permsSummary = 'Antes: ' . (empty($oldPerms) ? 'Sin permisos' : implode(', ', $oldPerms))
                    . ' | Ahora: ' . (empty($newPerms) ? 'Sin permisos' : implode(', ', $newPerms));

                foreach ($users as $user) {
                    [$subject, $body] = TemplateMail::render(
                        $settings,
                        'template_role_permissions_changed_subject',
                        'template_role_permissions_changed_body',
                        'Actualizacion de rol/permisos en {empresa}',
                        "Hola {nombre},\n\nTu rol/permisos fueron actualizados.",
                        [
                            'nombre' => $user['name'] ?? 'Usuario',
                            'rol_anterior' => $current['name'] ?? (string) $id,
                            'rol_nuevo' => $data['name'] ?? ($current['name'] ?? (string) $id),
                            'permisos_resumen' => $permsSummary,
                            'fecha_cambio' => now()->format('d/m/Y H:i'),
                            'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
                        ],
                        [
                            ['label' => 'Entrar al dashboard', 'url' => route('dashboard'), 'kind' => 'primary'],
                        ]
                    );
                    TemplateMail::send((string) $user['email'], $subject, $body);
                }
            }
        } catch (\Throwable $e) {
            // No interrumpir actualizacion de rol por error de correo
        }

        return redirect()->route('settings.roles.index')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy($id)
    {
        if ($id === 'admin') {
            return back()->with('error', 'No puedes eliminar el rol de Administrador.');
        }
        $this->store->delete($id);
        return redirect()->route('settings.roles.index')->with('success', 'Rol eliminado.');
    }
}
