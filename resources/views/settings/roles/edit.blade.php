@extends('layouts.settings')

@section('title', 'Editar Rol')

@section('content')
@php
$allModules = [
    ['key' => 'panel',      'label' => 'Panel',              'perms' => ['read' => 'Ver']],
    ['key' => 'mis-notas',  'label' => 'Mis Notas',          'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'clientes',   'label' => 'Clientes',           'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'documentos', 'label' => 'Documentos',         'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'proyectos',  'label' => 'Proyectos',          'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'reuniones',  'label' => 'Reuniones',          'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'leads',      'label' => 'Leads',              'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'ventas',     'label' => 'Ventas',             'perms' => [], 'children' => [
        ['key' => 'facturas',    'label' => 'Facturas',              'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
        ['key' => 'pagos',       'label' => 'Pagos',                 'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
        ['key' => 'cotizaciones','label' => 'Cotizaciones',          'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
        ['key' => 'productos',   'label' => 'Productos y Servicios', 'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ]],
    ['key' => 'gastos',     'label' => 'Gastos',             'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'contratos',  'label' => 'Contratos',          'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'correo',     'label' => 'Correo',             'perms' => ['read' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar']],
    ['key' => 'reportes',   'label' => 'Reportes',           'perms' => ['read' => 'Ver']],
    ['key' => 'ajustes',    'label' => 'Ajustes',            'perms' => ['read' => 'Ver', 'update' => 'Editar']],
];
$rolePerms = $role['permissions'] ?? [];
$isFullAccess = in_array('*', $rolePerms);
$has = fn(string $p) => $isFullAccess || in_array($p, $rolePerms);
$dashboardTabs = [
    'resumen' => $has('dashboard.resumen'),
    'proyectos' => $has('dashboard.proyectos'),
    'ventas' => $has('dashboard.ventas'),
];
@endphp
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900">Editar Rol: {{ $role['name'] }}</h2>
        <a href="{{ route('settings.roles.index') }}" class="px-3 py-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-200 rounded-full hover:bg-white transition-colors">Cancelar</a>
    </div>

    <form action="{{ route('settings.roles.update', $role['id']) }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Rol</label>
            <input type="text" name="name" value="{{ $role['name'] }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
            <textarea name="description" rows="2" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">{{ $role['description'] ?? '' }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-3">Permisos</label>

            @if($isFullAccess)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded-xl border border-yellow-100 text-sm mb-4">
                    Este rol tiene acceso total (Administrador). Los permisos individuales no aplican.
                    <input type="hidden" name="permissions[]" value="*">
                </div>
            @else
                <div class="space-y-3">
                    @foreach($allModules as $module)
                        @php $hasChildren = !empty($module['children']); @endphp
                        <div class="border border-slate-100 rounded-xl p-4 {{ $hasChildren ? 'bg-slate-50/50' : '' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-900">{{ $module['label'] }}</span>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                                    <input type="checkbox" class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500" onchange="toggleGroup('{{ $module['key'] }}', this.checked)">
                                    <span class="text-xs text-slate-500">Seleccionar todo</span>
                                </label>
                            </div>
                            @if(!empty($module['perms']))
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($module['perms'] as $perm => $permLabel)
                                        @php $val = $module['key'].'.'.$perm; @endphp
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="permissions[]" value="{{ $val }}" {{ $has($val) ? 'checked' : '' }} class="perm-{{ $module['key'] }} form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                                            <span class="text-sm text-slate-600">{{ $permLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            @if($hasChildren)
                                <div class="mt-3 space-y-3 pl-4 border-l-2 border-slate-200">
                                    @foreach($module['children'] as $child)
                                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-semibold text-slate-700">{{ $child['label'] }}</span>
                                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500" onchange="toggleGroup('{{ $child['key'] }}', this.checked)">
                                                    <span class="text-xs text-slate-400">Seleccionar todo</span>
                                                </label>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach($child['perms'] as $perm => $permLabel)
                                                    @php $val = $child['key'].'.'.$perm; @endphp
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" name="permissions[]" value="{{ $val }}" {{ $has($val) ? 'checked' : '' }} class="perm-{{ $child['key'] }} perm-ventas form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                                                        <span class="text-sm text-slate-600">{{ $permLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Temporizador --}}
                    <div class="border-2 border-lime-200 rounded-xl p-4 bg-lime-50/40">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <span class="font-bold text-slate-900">Temporizador</span>
                                <p class="text-xs text-slate-500 mt-0.5">Define a qué temporizadores tiene acceso este rol.</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="permissions[]" value="timer.proyectos" {{ $has('timer.proyectos') ? 'checked' : '' }} class="perm-timer mt-0.5 form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Temporizador de Proyectos</div>
                                    <div class="text-xs text-slate-500">Puede iniciar timers vinculados a tareas de proyectos.</div>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="permissions[]" value="timer.leads" {{ $has('timer.leads') ? 'checked' : '' }} class="perm-timer mt-0.5 form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Temporizador de Leads</div>
                                    <div class="text-xs text-slate-500">Puede iniciar timers vinculados a leads comerciales.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="border-2 border-lime-200 rounded-xl p-4 bg-lime-50/40">
            <div class="font-bold text-slate-900">Dashboard visible</div>
            <p class="text-xs text-slate-500 mt-0.5 mb-3">Define qué pestañas verá este rol en el dashboard principal.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="dashboard_tabs[]" value="resumen" {{ $dashboardTabs['resumen'] ? 'checked' : '' }} class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                    <span class="text-sm text-slate-700 font-semibold">Resumen</span>
                </label>
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="dashboard_tabs[]" value="proyectos" {{ $dashboardTabs['proyectos'] ? 'checked' : '' }} class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                    <span class="text-sm text-slate-700 font-semibold">Proyectos</span>
                </label>
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="dashboard_tabs[]" value="ventas" {{ $dashboardTabs['ventas'] ? 'checked' : '' }} class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500">
                    <span class="text-sm text-slate-700 font-semibold">Ventas</span>
                </label>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
function toggleGroup(group, checked) {
    document.querySelectorAll('.perm-' + group).forEach(el => el.checked = checked);
}
</script>
@endsection
