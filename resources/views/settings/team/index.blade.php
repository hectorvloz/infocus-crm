@extends('layouts.settings')

@section('title', 'Equipo y Usuarios')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Equipo</h2>
    <p class="text-slate-500">Gestiona los usuarios que tienen acceso al CRM.</p>
</div>

<div class="mb-4 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 w-full md:w-auto">
            <button type="button" data-team-tab-btn="users" class="team-tab-btn flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-semibold bg-lime-200 text-slate-900">Usuarios</button>
            <button type="button" data-team-tab-btn="reminders" class="team-tab-btn flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-semibold text-slate-600">Recordatorios</button>
            <a href="{{ route('settings.roles.index') }}" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 text-center hover:bg-slate-50">Gestionar Roles</a>
        </div>
        <a href="{{ route('settings.team.create') }}" class="w-full md:w-auto px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm inline-flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Usuario
        </a>
    </div>
</div>

<div id="team-tab-users" class="team-tab-panel bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[600px] text-left text-sm text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-bold text-slate-400">
                <tr>
                    <th class="px-6 py-4">Usuario</th>
                    <th class="px-6 py-4">Rol</th>
                    <th class="px-6 py-4">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-lg uppercase">
                                    {{ substr($user['name'] ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">{{ $user['name'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $user['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                {{ $roles[$user['role']] ?? ucfirst($user['role']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user['active'] ?? true)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('settings.team.edit', $user['id']) }}" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form action="{{ route('settings.team.destroy', $user['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?');">
                                    @csrf @method('DELETE')
                                    <button class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="team-tab-reminders" class="team-tab-panel hidden bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <form method="POST" action="{{ route('settings.team.reminders.update') }}" class="p-6 md:p-8 space-y-5">
        @csrf
        @method('PUT')
        <div>
            <h3 class="text-lg font-bold text-slate-900">Recordatorios al equipo</h3>
            <p class="text-sm text-slate-500">Activa o desactiva los correos automáticos internos para tu equipo de trabajo.</p>
        </div>

        @php
            $items = [
                'team_notify_weekly_hours' => ['Resumen semanal de horas', 'Envía cada semana al equipo su resumen de horas por proyecto.'],
                'team_notify_monthly_hours' => ['Resumen mensual de horas', 'Envía al cierre de mes un resumen consolidado de horas trabajadas.'],
                'team_notify_system_alerts' => ['Alertas críticas del sistema', 'Notifica al equipo administrador cuando falten datos críticos en SMTP o pasarelas.'],
                'team_notify_team_welcome' => ['Correo de bienvenida al crear usuario', 'Envía un correo de bienvenida al nuevo miembro cuando se crea su cuenta.'],
                'team_notify_role_changes' => ['Correo por cambio de rol/permisos', 'Notifica al usuario cuando se actualiza su rol o sus permisos.'],
            ];
        @endphp

        @foreach($items as $key => [$title, $hint])
            <label class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition-colors">
                <div>
                    <p class="font-semibold text-slate-900">{{ $title }}</p>
                    <p class="text-xs text-slate-500">{{ $hint }}</p>
                </div>
                <span class="relative inline-flex h-7 w-12 items-center">
                    <input type="checkbox" name="{{ $key }}" value="1" class="peer sr-only" {{ !empty($reminders[$key]) ? 'checked' : '' }}>
                    <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-lime-400"></span>
                    <span class="absolute left-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                </span>
            </label>
        @endforeach

        <div class="pt-2">
            <button type="submit" class="px-6 py-2.5 rounded-full bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 font-bold">Guardar recordatorios</button>
        </div>
    </form>
</div>

<script>
(() => {
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    const initial = urlTab === 'reminders' ? 'reminders' : 'users';

    function switchTab(tab) {
        document.querySelectorAll('.team-tab-btn').forEach((btn) => {
            const on = btn.dataset.teamTabBtn === tab;
            btn.classList.toggle('bg-lime-200', on);
            btn.classList.toggle('text-slate-900', on);
            btn.classList.toggle('text-slate-600', !on);
        });
        document.getElementById('team-tab-users').classList.toggle('hidden', tab !== 'users');
        document.getElementById('team-tab-reminders').classList.toggle('hidden', tab !== 'reminders');
    }

    document.querySelectorAll('.team-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => switchTab(btn.dataset.teamTabBtn));
    });

    switchTab(initial);
})();
</script>
@endsection
