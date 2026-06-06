@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-6 gap-4">
    <h1 class="text-2xl font-extrabold text-slate-900">Gestión de Gastos</h1>
    
    <!-- Segmented Control (Estilo Proyecto) -->
    <div class="segmented-control flex w-full lg:w-auto">
        <button id="tab-gastos-btn" class="seg-btn is-active flex-1 lg:flex-none text-center justify-center" onclick="switchTab('gastos')">Gastos</button>
        <button id="tab-proveedores-btn" class="seg-btn flex-1 lg:flex-none text-center justify-center" onclick="switchTab('proveedores')">Proveedores</button>
        <span class="segmented-highlight"></span>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-end">
        <div id="actions-gastos" class="flex flex-wrap gap-2 w-full lg:w-auto justify-end">
            <button type="button" onclick="openBudgetModal()" class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-colors shadow-sm text-sm font-semibold flex-1 lg:flex-none justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <span class="hidden sm:inline">Presupuestos</span>
            </button>
            <a href="{{ route('gastos.export') }}" class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-colors shadow-sm text-sm font-semibold flex-1 lg:flex-none justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>CSV</span>
            </a>
            <a href="{{ route('gastos.create') }}" class="primary-add-btn w-full sm:w-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Nuevo Gasto</span>
            </a>
        </div>
        <div id="actions-proveedores" class="hidden w-full lg:w-auto">
            <a href="{{ route('proveedores.create') }}" class="primary-add-btn w-full sm:w-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Nuevo Proveedor</span>
            </a>
        </div>
    </div>
</div>

<!-- Tab Content: GASTOS -->
<div id="tab-gastos" class="space-y-6">
    
    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Total Mes -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity pointer-events-none">
                <svg class="w-16 h-16 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-slate-500 mb-1 truncate">Total Gastado (Este Mes)</p>
                <h3 class="text-2xl font-extrabold text-slate-900">{{ format_currency($stats['total_mes']) }}</h3>
                <div class="mt-2 flex items-center text-xs font-medium flex-wrap gap-1">
                    @if($stats['diff_percent'] > 0)
                        <span class="text-red-500 bg-red-50 px-2 py-0.5 rounded-full flex items-center gap-1">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            {{ $stats['diff_percent'] }}% vs mes anterior
                        </span>
                    @elseif($stats['diff_percent'] < 0)
                        <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded-full flex items-center gap-1">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                            {{ abs($stats['diff_percent']) }}% vs mes anterior
                        </span>
                    @else
                        <span class="text-slate-400">Sin cambios vs mes anterior</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Categoría -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity pointer-events-none">
                <svg class="w-16 h-16 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div class="relative z-10">
                <p class="text-sm font-medium text-slate-500 mb-1 truncate">Mayor Gasto en</p>
                <h3 class="text-2xl font-extrabold text-slate-900 truncate" title="{{ $stats['top_categoria'] }}">{{ $stats['top_categoria'] }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-600">{{ format_currency($stats['top_categoria_monto']) }}</p>
            </div>
        </div>

        <!-- Presupuestos Alerta -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col justify-center">
            <div class="relative z-10">
                <p class="text-sm font-medium text-slate-500 mb-3 truncate">Estado de Presupuestos</p>
                @php $overBudgetCount = collect($stats['budgets'])->where('over_budget', true)->count(); @endphp
                @if($overBudgetCount > 0)
                    <div class="flex items-center gap-3 text-red-600">
                        <div class="p-2 bg-red-100 rounded-full flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="overflow-hidden">
                            <span class="block font-bold text-lg truncate">{{ $overBudgetCount }} Categoría(s)</span>
                            <span class="text-xs opacity-80 truncate block">Excediendo límite mensual</span>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 text-green-600">
                        <div class="p-2 bg-green-100 rounded-full flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="overflow-hidden">
                            <span class="block font-bold text-lg truncate">Todo en orden</span>
                            <span class="text-xs opacity-80 truncate block">Dentro de los límites</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @php
        $budgetCollection = collect($stats['budgets'] ?? []);
        $budgetOver = $budgetCollection->where('over_budget', true)->count();
        $budgetTotal = $budgetCollection->count();
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Control Presupuestal del Mes</h3>
                <p class="text-sm text-slate-500">
                    @if($budgetTotal > 0)
                        {{ $budgetOver }} de {{ $budgetTotal }} categoría(s) excedidas.
                    @else
                        Aun no has definido presupuestos por categoría.
                    @endif
                </p>
            </div>
            <button type="button" onclick="openBudgetModal()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition-colors w-full md:w-auto justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                Gestionar presupuestos
            </button>
        </div>

        @if($budgetTotal > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach(($stats['budgets'] ?? []) as $cat => $budget)
                    @php
                        $limit = (float) ($budget['limit'] ?? 0);
                        $spent = (float) ($budget['spent'] ?? 0);
                        $percent = $limit > 0 ? ($spent / $limit) * 100 : 0;
                        $trackPercent = min(100, max(0, $percent));
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-3 bg-slate-50/60">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="text-sm font-semibold text-slate-800 truncate">{{ $cat }}</div>
                            @if(($budget['over_budget'] ?? false) === true)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">Excedido</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">OK</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 mb-2">{{ format_currency($spent) }} / {{ $limit > 0 ? format_currency($limit) : 'Sin limite' }}</div>
                        <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full progress-fill-anim {{ ($budget['over_budget'] ?? false) ? 'bg-red-400' : 'bg-emerald-500' }}" style="width: {{ $trackPercent }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                Crea tus categorias y limites mensuales para empezar a monitorear desviaciones de gasto.
            </div>
        @endif
    </div>

    <!-- Lista de Gastos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-sm text-left min-w-[800px]">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3">Fecha</th>
                        <th class="px-6 py-3">Concepto</th>
                        <th class="px-6 py-3">Categoría</th>
                        <th class="px-6 py-3">Proveedor</th>
                        <th class="px-6 py-3">Monto</th>
                        <th class="px-6 py-3 text-center">Info</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($gastos as $gasto)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-slate-500">{{ \Carbon\Carbon::parse($gasto['fecha'])->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $gasto['concepto'] }}</td>
                        <td class="px-6 py-4">
                            @if(!empty($gasto['categoria']))
                                <span class="px-2 py-1 rounded-md bg-slate-100 text-xs font-semibold text-slate-600">{{ $gasto['categoria'] }}</span>
                            @else - @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            @if($gasto['proveedor_nombre'])
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                    {{ $gasto['proveedor_nombre'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                    Sin proveedor
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-900">
                            {{ number_format($gasto['monto'], 2) }} <span class="text-xs font-normal text-slate-500">{{ $gasto['moneda'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if(($gasto['es_recurrente'] ?? false))
                                    <span class="text-purple-500" title="Gasto Recurrente">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </span>
                                @endif
                                @if(!empty($gasto['comprobante_path']))
                                    <a href="{{ asset('storage/' . $gasto['comprobante_path']) }}" target="_blank" class="text-slate-400 hover:text-slate-900" title="Ver Comprobante">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('gastos.edit', $gasto['id']) }}" class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('gastos.destroy', $gasto['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar este gasto?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <p>No hay gastos registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab Content: PROVEEDORES -->
<div id="tab-proveedores" class="hidden space-y-4">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
        <form method="GET" action="{{ route('gastos.index') }}" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
            <input type="hidden" name="tab" value="proveedores">
            <div class="w-full sm:w-72">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Mes</label>
                <select data-native-select="1" name="proveedor_mes" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:ring-[#ecfe88] focus:border-[#ecfe88]">
                    <option value="all" {{ ($proveedorMes ?? '') === 'all' ? 'selected' : '' }}>Todos los meses</option>
                    @foreach(($mesesProveedor ?? []) as $mes)
                        <option value="{{ $mes['value'] }}" {{ ($proveedorMes ?? '') === $mes['value'] ? 'selected' : '' }}>{{ $mes['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <button class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-[#ecfe88] text-slate-900 font-bold hover:bg-[#d9ea76] transition-colors">Aplicar</button>
        </form>
        <button type="button" onclick="openProviderCategoriesModal()" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50">
            Editar categorias de proveedor
        </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-sm text-left min-w-[900px]">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3">Proveedor</th>
                        <th class="px-6 py-3">Contacto</th>
                        <th class="px-6 py-3">RFC/Tax ID</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3 text-right"># Gastos</th>
                        <th class="px-6 py-3 text-right">Total Gastado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($supplierStats ?? []) as $prov)
                    <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer proveedor-row {{ ($prov['is_unassigned'] ?? false) ? 'bg-red-50/40' : '' }}" data-proveedor-row="{{ $prov['id'] }}">
                        <td class="px-6 py-4 font-medium {{ ($prov['is_unassigned'] ?? false) ? 'text-red-700' : 'text-slate-900' }}">{{ $prov['nombre'] }}</td>
                        <td class="px-6 py-4 {{ ($prov['is_unassigned'] ?? false) ? 'text-red-500' : 'text-slate-500' }}">{{ $prov['contacto'] ?? '-' }}</td>
                        <td class="px-6 py-4 {{ ($prov['is_unassigned'] ?? false) ? 'text-red-500' : 'text-slate-500' }}">{{ $prov['rfc'] ?? '-' }}</td>
                        <td class="px-6 py-4 {{ ($prov['is_unassigned'] ?? false) ? 'text-red-500' : 'text-slate-500' }}">{{ $prov['email'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold {{ ($prov['is_unassigned'] ?? false) ? 'text-red-700' : 'text-slate-700' }}">{{ $prov['items_count'] ?? 0 }}</td>
                        <td class="px-6 py-4 text-right">
                            @php $totalesMoneda = $prov['totales_por_moneda'] ?? []; @endphp
                            @if(!empty($totalesMoneda))
                                <div class="font-bold {{ ($prov['is_unassigned'] ?? false) ? 'text-red-700' : 'text-slate-900' }}">
                                    @foreach($totalesMoneda as $mon => $totalMon)
                                        <div>{{ format_currency($totalMon, $mon) }}</div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(($prov['is_unassigned'] ?? false) === true)
                                <span class="text-xs font-semibold text-red-500">Asigna proveedor en gastos</span>
                            @else
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('proveedores.edit', $prov['id']) }}" onclick="event.stopPropagation()" class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('proveedores.destroy', $prov['id']) }}" method="POST" onsubmit="event.stopPropagation(); return confirm('¿Eliminar proveedor?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr id="proveedor-detail-{{ $prov['id'] }}" class="hidden bg-slate-50/70">
                        <td colspan="7" class="px-6 py-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Detalle de gastos del proveedor</div>
                            @if(!empty($prov['expenses']))
                                <div class="overflow-x-auto custom-scroll">
                                    <table class="w-full text-xs text-left min-w-[920px]">
                                        <thead>
                                            <tr class="text-slate-500 border-b border-slate-200">
                                                <th class="py-2 pr-4">Fecha</th>
                                                <th class="py-2 pr-4">Concepto</th>
                                                <th class="py-2 pr-4">Cliente</th>
                                                <th class="py-2 pr-4">Proyecto / Lista</th>
                                                <th class="py-2 pr-4">Categoría</th>
                                                <th class="py-2 pr-4 text-right">Monto</th>
                                                <th class="py-2">Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($prov['expenses'] as $eg)
                                            <tr>
                                                <td class="py-2 pr-4 text-slate-500">{{ !empty($eg['fecha']) ? \Carbon\Carbon::parse($eg['fecha'])->format('d/m/Y') : '-' }}</td>
                                                <td class="py-2 pr-4 text-slate-800 font-medium">{{ $eg['concepto'] ?? '-' }}</td>
                                                <td class="py-2 pr-4 {{ str_starts_with((string) ($eg['cliente'] ?? ''), 'Sin ') ? 'text-red-600 font-semibold' : 'text-slate-500' }}">{{ $eg['cliente'] ?? '-' }}</td>
                                                <td class="py-2 pr-4 {{ str_starts_with((string) ($eg['proyecto'] ?? ''), 'Sin ') ? 'text-red-600 font-semibold' : 'text-slate-500' }}">{{ $eg['proyecto'] ?? '-' }}</td>
                                                <td class="py-2 pr-4 text-slate-500">{{ $eg['categoria'] ?? '-' }}</td>
                                                <td class="py-2 pr-4 text-right font-semibold text-slate-900">{{ format_currency($eg['monto'] ?? 0, $eg['moneda'] ?? null) }}</td>
                                                <td class="py-2 text-slate-500">{{ $eg['notas'] ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-sm text-slate-500">No hay gastos para este proveedor en el periodo seleccionado.</div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <p>No hay proveedores registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Presupuestos -->
<div id="budgetModal" class="fixed inset-0 bg-slate-900/55 hidden z-50 items-center justify-center p-4">
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[92vh]">
        <form action="{{ route('gastos.budgets') }}" method="POST" class="flex flex-col h-full">
            @csrf
            <div class="p-6 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-slate-50">
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Presupuestos Mensuales</h3>
                    <p class="text-sm text-slate-500">Define limites por categoria y controla la ejecucion de gastos.</p>
                </div>
                <button type="button" onclick="closeBudgetModal()" class="h-9 w-9 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-slate-800 hover:border-slate-300">✕</button>
            </div>
            
            <div class="p-6 space-y-4 overflow-y-auto custom-scroll flex-1">
                @php
                    $defaultCats = ['Oficina', 'Software', 'Servicios', 'Viajes', 'Nomina', 'Marketing'];
                    $existingCats = array_keys($stats['budgets'] ?? []);
                    $allCats = array_values(array_unique(array_merge($defaultCats, $existingCats)));
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Categorias</div>
                        <div class="text-2xl font-extrabold text-slate-900">{{ count($allCats) }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Con limite</div>
                        <div class="text-2xl font-extrabold text-slate-900">{{ collect($stats['budgets'] ?? [])->filter(fn($b) => (float)($b['limit'] ?? 0) > 0)->count() }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Excedidas</div>
                        <div class="text-2xl font-extrabold text-red-600">{{ collect($stats['budgets'] ?? [])->where('over_budget', true)->count() }}</div>
                    </div>
                </div>
                
                <div id="budgets-container" class="space-y-3 max-h-[48vh] overflow-y-auto custom-scroll pr-1">
                    @foreach($allCats as $index => $cat)
                    <div class="budget-row rounded-xl border border-slate-200 p-3 bg-white">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">
                            <div class="md:col-span-4">
                                <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Categoria</label>
                                <input type="text" name="budgets[{{ $index }}][categoria]" value="{{ $cat }}"
                                       class="w-full text-sm font-semibold text-slate-700 border border-slate-200 rounded-lg px-3 py-2"
                                       placeholder="Categoria">
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Limite mensual</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-slate-400 text-sm">{{ currency_symbol() }}</span>
                            <input type="number" name="budgets[{{ $index }}][limite]" 
                                   value="{{ $stats['budgets'][$cat]['limit'] ?? '' }}" 
                                           class="w-full pl-6 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-[#ecfe88] focus:border-[#ecfe88]"
                                           placeholder="Sin limite">
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Ejecucion</label>
                                @php
                                    $spent = (float) ($stats['budgets'][$cat]['spent'] ?? 0);
                                    $limit = (float) ($stats['budgets'][$cat]['limit'] ?? 0);
                                    $pct = $limit > 0 ? min(100, ($spent / $limit) * 100) : 0;
                                @endphp
                                <div class="text-xs text-slate-600 mb-1">{{ format_currency($spent) }}</div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ ($stats['budgets'][$cat]['over_budget'] ?? false) ? 'bg-red-400' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            <div class="md:col-span-1 flex md:justify-end">
                                <button type="button" class="text-slate-400 hover:text-red-500 p-1" onclick="removeBudgetRow(this)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" onclick="addBudgetRow()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar nueva categoría
                </button>
            </div>

            <div class="p-5 bg-slate-50 flex justify-end gap-2 flex-shrink-0 rounded-b-2xl border-t border-slate-100">
                <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-200">Cerrar</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76]">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Categorias Proveedor -->
<div id="providerCategoriesModal" class="fixed inset-0 bg-slate-900/55 hidden z-50 items-center justify-center p-4">
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <form action="{{ route('proveedores.categories.update') }}" method="POST" class="flex flex-col h-full">
            @csrf
            <div class="p-6 border-b border-slate-100 flex items-center justify-between flex-shrink-0 bg-slate-50">
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Categorias de proveedor</h3>
                    <p class="text-sm text-slate-500">Define aqui la lista visible para crear y editar proveedores.</p>
                </div>
                <button type="button" onclick="closeProviderCategoriesModal()" class="h-9 w-9 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-slate-800 hover:border-slate-300">✕</button>
            </div>

            <div class="p-6 space-y-3 overflow-y-auto custom-scroll flex-1">
                <div id="provider-categories-container" class="space-y-2">
                    @foreach(($providerCategoryOptions ?? []) as $cat)
                    <div class="provider-category-row flex items-center gap-2">
                        <input type="text" name="categories[]" value="{{ $cat }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" placeholder="Categoria">
                        <button type="button" onclick="removeProviderCategoryRow(this)" class="p-2 text-slate-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addProviderCategoryRow()" class="text-sm font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar categoria
                </button>
            </div>

            <div class="p-5 bg-slate-50 flex justify-end gap-2 border-t border-slate-100">
                <button type="button" onclick="closeProviderCategoriesModal()" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-200">Cerrar</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76]">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBudgetModal() {
        const modal = document.getElementById('budgetModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeBudgetModal() {
        const modal = document.getElementById('budgetModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function openProviderCategoriesModal() {
        const modal = document.getElementById('providerCategoriesModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeProviderCategoriesModal() {
        const modal = document.getElementById('providerCategoriesModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function bindModalBackdropClose() {
        const ids = ['budgetModal', 'providerCategoriesModal'];
        ids.forEach((id) => {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.addEventListener('click', (e) => {
                if (e.target !== modal) return;
                if (id === 'budgetModal') closeBudgetModal();
                if (id === 'providerCategoriesModal') closeProviderCategoriesModal();
            });
        });
    }

    function addProviderCategoryRow() {
        const container = document.getElementById('provider-categories-container');
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'provider-category-row flex items-center gap-2';
        row.innerHTML = `
            <input type="text" name="categories[]" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" placeholder="Categoria" required>
            <button type="button" onclick="removeProviderCategoryRow(this)" class="p-2 text-slate-400 hover:text-red-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        `;
        container.appendChild(row);
    }

    function removeProviderCategoryRow(btn) {
        const row = btn.closest('.provider-category-row');
        if (row) row.remove();
    }

    function addBudgetRow() {
        const container = document.getElementById('budgets-container');
        const index = container.children.length;
        const div = document.createElement('div');
        div.className = 'budget-row rounded-xl border border-slate-200 p-3 bg-white';
        div.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">
                <div class="md:col-span-4">
                    <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Categoria</label>
                    <input type="text" name="budgets[${index}][categoria]" class="w-full text-sm font-semibold text-slate-700 border border-slate-200 rounded-lg px-3 py-2" placeholder="Nueva categoria" required>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Limite mensual</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 text-sm">{{ currency_symbol() }}</span>
                        <input type="number" name="budgets[${index}][limite]" class="w-full pl-6 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-[#ecfe88] focus:border-[#ecfe88]" placeholder="Sin limite">
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] uppercase tracking-wide font-semibold text-slate-500 mb-1">Ejecucion</label>
                    <div class="text-xs text-slate-400 mb-1">Sin datos aun</div>
                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-slate-300" style="width: 0%"></div>
                    </div>
                </div>
                <div class="md:col-span-1 flex md:justify-end">
                    <button type="button" class="text-slate-400 hover:text-red-500 p-1" onclick="removeBudgetRow(this)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(div);
    }

    function removeBudgetRow(btn) {
        if(confirm('¿Quitar esta categoría del presupuesto?')) {
            btn.closest('.budget-row').remove();
        }
    }

    function switchTab(tab) {
        // Toggle Content
        const gastosContent = document.getElementById('tab-gastos');
        const proveedoresContent = document.getElementById('tab-proveedores');
        const gastosActions = document.getElementById('actions-gastos');
        const proveedoresActions = document.getElementById('actions-proveedores');

        if (tab === 'gastos') {
            gastosContent.classList.remove('hidden');
            proveedoresContent.classList.add('hidden');
            gastosActions.classList.remove('hidden');
            proveedoresActions.classList.add('hidden');
        } else {
            proveedoresContent.classList.remove('hidden');
            gastosContent.classList.add('hidden');
            proveedoresActions.classList.remove('hidden');
            gastosActions.classList.add('hidden');
        }

        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.pushState({}, '', url);
    }

    // Init tab from URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'proveedores') {
            // Trigger click on the proveedores button to handle both visual (via app.js) and content toggle
            const btn = document.getElementById('tab-proveedores-btn');
            if(btn) btn.click(); 
            // Also call switchTab to be sure content toggles if app.js only handles visuals
            switchTab('proveedores');
        }

        document.querySelectorAll('[data-proveedor-row]').forEach((row) => {
            row.addEventListener('click', () => {
                const id = row.getAttribute('data-proveedor-row');
                const detailRow = document.getElementById('proveedor-detail-' + id);
                if (!detailRow) return;

                const willOpen = detailRow.classList.contains('hidden');
                document.querySelectorAll('[id^="proveedor-detail-"]').forEach((r) => r.classList.add('hidden'));
                if (willOpen) detailRow.classList.remove('hidden');
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeBudgetModal();
                closeProviderCategoriesModal();
            }
        });

        bindModalBackdropClose();
    });
</script>
@endsection
