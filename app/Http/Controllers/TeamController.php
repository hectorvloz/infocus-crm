<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\FileStore;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\TemplateMail;

class TeamController extends Controller
{
    protected FileStore $store;
    protected FileStore $rolesStore;
    protected FileStore $settingsStore;

    public function __construct()
    {
        $this->store = new FileStore('users.json');
        $this->rolesStore = new FileStore('roles.json');
        $this->settingsStore = new FileStore('settings.json');
    }

    public function index()
    {
        $users = $this->store->all();
        $roles = collect($this->rolesStore->all())->pluck('name', 'id');
        
        // Seed default admin if empty
        if (empty($users)) {
            $admin = [
                'id' => (string) Str::uuid(),
                'name' => 'Admin User',
                'email' => 'admin@example.com', // Default
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
                'created_at' => now()->toISOString()
            ];
            $this->store->save([$admin]);
            $users = [$admin];
        }

        $settings = $this->settingsStore->find('settings') ?: [];
        $reminders = $this->buildReminderSettings($settings);

        return view('settings.team.index', compact('users', 'roles', 'reminders'));
    }

    public function updateReminders(Request $request)
    {
        $data = $request->validate([
            'team_notify_weekly_hours' => 'nullable|boolean',
            'team_notify_monthly_hours' => 'nullable|boolean',
            'team_notify_system_alerts' => 'nullable|boolean',
            'team_notify_team_welcome' => 'nullable|boolean',
            'team_notify_role_changes' => 'nullable|boolean',
        ]);

        $settings = $this->settingsStore->find('settings') ?: [];
        $settings['team_notify_weekly_hours'] = $request->boolean('team_notify_weekly_hours');
        $settings['team_notify_monthly_hours'] = $request->boolean('team_notify_monthly_hours');
        $settings['team_notify_system_alerts'] = $request->boolean('team_notify_system_alerts');
        $settings['team_notify_team_welcome'] = $request->boolean('team_notify_team_welcome');
        $settings['team_notify_role_changes'] = $request->boolean('team_notify_role_changes');

        $this->settingsStore->update('settings', $settings);

        return redirect()->route('settings.team.index', ['tab' => 'reminders'])
            ->with('success', 'Recordatorios del equipo actualizados.');
    }

    public function create()
    {
        $roles = $this->rolesStore->all();
        return view('settings.team.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email', // Ideally unique check
            'password' => 'required|min:6',
            'role' => 'required|string',
        ]);
        $plainPassword = (string) $data['password'];
        $data['email'] = strtolower(trim((string) $data['email']));

        // Check unique email manually since no DB
        $all = $this->store->all();
        foreach($all as $u){
            if(($u['email']??'') === $data['email']){
                return back()->withErrors(['email'=>'Este correo ya está registrado.']);
            }
        }

        $data['password'] = Hash::make($data['password']);
        $data['active'] = true;
        $created = $this->store->create($data);
        $this->syncLoginUser($created, $plainPassword);

        try {
            $settings = TemplateMail::settings();
            if (!($settings['team_notify_team_welcome'] ?? true)) {
                return redirect()->route('settings.team.index')->with('success', 'Usuario creado exitosamente.');
            }
            [$subject, $body] = TemplateMail::render(
                $settings,
                'template_team_welcome_subject',
                'template_team_welcome_body',
                'Bienvenido(a) al equipo de {empresa}',
                "Hola {nombre},\n\nTe damos la bienvenida a {empresa}.\nRol asignado: {rol}",
                [
                    'nombre' => $created['name'] ?? $data['name'],
                    'rol' => $this->resolveRoleName($created['role'] ?? $data['role']),
                    'email' => $created['email'] ?? $data['email'],
                    'password' => $plainPassword,
                    'login_url' => route('login.show'),
                    'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
                ],
                [
                    ['label' => 'Entrar al dashboard', 'url' => route('dashboard'), 'kind' => 'primary'],
                ]
            );
            TemplateMail::send((string) ($created['email'] ?? $data['email']), $subject, $body);
        } catch (\Throwable $e) {
            // No interrumpir flujo de creacion por fallo de correo
        }

        return redirect()->route('settings.team.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit($id)
    {
        $user = $this->store->find($id);
        abort_if(!$user, 404);
        $roles = $this->rolesStore->all();
        return view('settings.team.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $current = $this->store->find($id);
        abort_if(!$current, 404);

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|string',
            'password' => 'nullable|min:6',
            'active' => 'nullable|boolean'
        ]);
        $plainPassword = !empty($data['password']) ? (string) $data['password'] : null;
        $data['email'] = strtolower(trim((string) $data['email']));

        // Check unique email
        $all = $this->store->all();
        foreach($all as $u){
            if(($u['email']??'') === $data['email'] && $u['id'] !== $id){
                return back()->withErrors(['email'=>'Este correo ya está registrado.']);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        $data['active'] = $request->has('active');

        $updated = $this->store->update($id, $data);
        if ($updated) {
            $this->syncLoginUser($updated, $plainPassword, (string) ($current['email'] ?? ''));
        }

        $oldRole = (string) ($current['role'] ?? '');
        $newRole = (string) ($updated['role'] ?? $data['role']);
        if ($oldRole !== '' && $newRole !== '' && $oldRole !== $newRole) {
            try {
                $settings = TemplateMail::settings();
                if (!($settings['team_notify_role_changes'] ?? true)) {
                    return redirect()->route('settings.team.index')->with('success', 'Usuario actualizado exitosamente.');
                }
                [$subject, $body] = TemplateMail::render(
                    $settings,
                    'template_role_permissions_changed_subject',
                    'template_role_permissions_changed_body',
                    'Actualizacion de rol/permisos en {empresa}',
                    "Hola {nombre},\n\nTu rol cambio de {rol_anterior} a {rol_nuevo}.",
                    [
                        'nombre' => $updated['name'] ?? $data['name'],
                        'rol_anterior' => $this->resolveRoleName($oldRole),
                        'rol_nuevo' => $this->resolveRoleName($newRole),
                        'permisos_resumen' => 'Consulta con tu administrador',
                        'fecha_cambio' => now()->format('d/m/Y H:i'),
                        'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
                    ],
                    [
                        ['label' => 'Entrar al dashboard', 'url' => route('dashboard'), 'kind' => 'primary'],
                    ]
                );
                TemplateMail::send((string) ($updated['email'] ?? $data['email']), $subject, $body);
            } catch (\Throwable $e) {
                // No interrumpir flujo de actualizacion por fallo de correo
            }
        }

        return redirect()->route('settings.team.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy($id)
    {
        // Prevent deleting self?
        // if (auth()->id() === $id) return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        
        $current = $this->store->find($id);
        $this->store->delete($id);
        if ($current && !empty($current['email'])) {
            User::where('email', strtolower(trim((string) $current['email'])))
                ->where('role', '!=', 'client')
                ->delete();
        }
        return redirect()->route('settings.team.index')->with('success', 'Usuario eliminado.');
    }

    private function syncLoginUser(array $teamUser, ?string $plainPassword = null, string $previousEmail = ''): void
    {
        $email = strtolower(trim((string) ($teamUser['email'] ?? '')));
        if ($email === '') {
            return;
        }

        $queryEmail = strtolower(trim($previousEmail)) ?: $email;
        $user = User::where('email', $queryEmail)->first() ?: User::where('email', $email)->first() ?: new User();
        $user->name = (string) ($teamUser['name'] ?? $email);
        $user->email = $email;
        $user->role = (string) ($teamUser['role'] ?? 'employee');
        $user->cliente_id = null;
        $user->must_change_password = false;

        if ($plainPassword !== null && $plainPassword !== '') {
            $user->password = Hash::make($plainPassword);
        } elseif (!$user->exists && !empty($teamUser['password'])) {
            $user->password = (string) $teamUser['password'];
        }

        $user->save();
    }

    private function resolveRoleName(string $roleId): string
    {
        $role = $this->rolesStore->find($roleId);
        return (string) ($role['name'] ?? $roleId);
    }

    private function buildReminderSettings(array $settings): array
    {
        return [
            'team_notify_weekly_hours' => (bool) ($settings['team_notify_weekly_hours'] ?? true),
            'team_notify_monthly_hours' => (bool) ($settings['team_notify_monthly_hours'] ?? true),
            'team_notify_system_alerts' => (bool) ($settings['team_notify_system_alerts'] ?? true),
            'team_notify_team_welcome' => (bool) ($settings['team_notify_team_welcome'] ?? true),
            'team_notify_role_changes' => (bool) ($settings['team_notify_role_changes'] ?? true),
        ];
    }
}
