@extends('layouts.app')
@section('title','Pagos')
@section('content')

<style>
  /* Custom Scrollbar for dropdowns */
  .custom-scroll::-webkit-scrollbar { width: 6px; }
  .custom-scroll::-webkit-scrollbar-track { background: transparent; }
  .custom-scroll::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 20px; }
  
  /* Calendar Styles */
  .calendar-day { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; border-radius: 999px; cursor: pointer; transition: all 0.2s; position: relative; z-index: 1; }
  .calendar-day:hover { background-color: #f1f5f9; }
  .calendar-day.is-today { font-weight: bold; color: #65a30d; }
  .calendar-day.is-selected { background-color: #1a202c; color: white; }
  .calendar-day.is-range { background-color: #ecfe88; color: #1a202c; border-radius: 0; }
  .calendar-day.is-range-start { background-color: #1a202c; color: white; border-radius: 50% 0 0 50%; }
  .calendar-day.is-range-end { background-color: #1a202c; color: white; border-radius: 0 50% 50% 0; }
  .calendar-day.is-range-start.is-range-end { border-radius: 50%; }
  
  /* Dropdown Animation */
  .dropdown-enter { opacity: 0; transform: translateY(-10px) scale(0.95); pointer-events: none; }
  .dropdown-active { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
</style>

<div class="max-w-7xl mx-auto space-y-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Pagos</h1>
            <p class="text-slate-500 mt-1">Gestión y control de ingresos</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-xl border shadow-sm flex items-center gap-3">
            <div class="text-right">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Total Recaudado</div>
                <div class="text-lg font-bold text-slate-900">{{ $baseCurrency ?? '' }} {{ number_format($totalPagos ?? 0, 2) }}</div>
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div class="text-right">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider">Transacciones</div>
                <div class="text-lg font-bold text-slate-900">{{ $countPagos ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Filter Bar (Custom UI) -->
    <div class="bg-white p-2 rounded-2xl shadow-soft border flex flex-col md:flex-row items-center gap-2 z-30 relative">
        <form id="filterForm" method="GET" action="{{ route('pagos.index') }}" class="w-full filter-bar">
            
            <!-- Custom Client Select -->
            <div class="relative w-full md:w-72" id="clientSelectWrapper">
                <input type="hidden" name="cliente_id" id="inputClientId" value="{{ $clienteId ?? '' }}">
                <button type="button" onclick="toggleDropdown('clientDropdown')" class="filter-pill filter-pill-lg w-full justify-between">
                    <span class="truncate" id="clientSelectLabel">
                        {{ $clienteId ? (collect($clientes)->firstWhere('id', $clienteId)['empresa'] ?? 'Todos los clientes') : 'Todos los clientes' }}
                    </span>
                    <svg class="w-4 h-4 filter-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="clientDropdown" class="dropdown-enter absolute top-full left-0 mt-2 w-full bg-white border border-slate-100 rounded-xl shadow-xl z-50 transition-all duration-200 origin-top-left overflow-hidden">
                    <div class="p-2 border-b border-slate-50">
                        <input type="text" placeholder="Buscar cliente..." class="w-full px-3 py-2 bg-slate-50 border-none rounded-lg text-sm focus:ring-0" onkeyup="filterClients(this.value)">
                    </div>
                    <div class="max-h-60 overflow-y-auto custom-scroll p-1" id="clientList">
                        <div class="client-option px-3 py-2 rounded-lg hover:bg-[#ecfe88] cursor-pointer text-sm transition-colors flex items-center justify-between group" onclick="selectClient('', 'Todos los clientes')">
                            <span>Todos los clientes</span>
                            @if(!$clienteId) <svg class="w-4 h-4 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> @endif
                        </div>
                        @foreach(($clientes ?? []) as $c)
                        <div class="client-option px-3 py-2 rounded-lg hover:bg-[#ecfe88] cursor-pointer text-sm transition-colors flex items-center justify-between group" data-name="{{ strtolower($c['empresa']) }}" onclick="selectClient('{{ $c['id'] }}', '{{ $c['empresa'] }}')">
                            <span>{{ $c['empresa'] }}</span>
                            @if(($clienteId ?? '') == $c['id']) <svg class="w-4 h-4 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Custom Date Range Picker -->
            <div class="relative w-full md:w-auto flex-1" id="datePickerWrapper">
                <input type="hidden" name="desde" id="inputDesde" value="{{ $desde ?? '' }}">
                <input type="hidden" name="hasta" id="inputHasta" value="{{ $hasta ?? '' }}">
                
                <button type="button" onclick="toggleDropdown('dateDropdown')" class="filter-pill filter-pill-lg w-full gap-3">
                    <svg class="w-5 h-5 filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span id="dateRangeLabel">
                        @if($desde && $hasta)
                            {{ \Carbon\Carbon::parse($desde)->format('d M') }} - {{ \Carbon\Carbon::parse($hasta)->format('d M') }}
                        @else
                            Seleccionar fechas
                        @endif
                    </span>
                </button>

                <!-- Airline Style Calendar Popover -->
                <div id="dateDropdown" class="dropdown-enter absolute top-full right-0 left-auto mt-2 bg-white border border-slate-100 rounded-2xl shadow-2xl z-50 transition-all duration-200 origin-top-right overflow-hidden" style="width:min(640px,calc(100vw - 1.5rem))">
                    <div class="flex flex-col sm:flex-row">
                        <!-- Left Panel: Quick Select -->
                        <div class="w-full sm:w-44 bg-slate-50 p-3 border-b sm:border-b-0 sm:border-r border-slate-100 flex flex-row sm:flex-col gap-1 overflow-x-auto sm:overflow-visible flex-shrink-0">
                            <button type="button" onclick="quickDate('today')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Hoy</button>
                            <button type="button" onclick="quickDate('week')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Esta semana</button>
                            <button type="button" onclick="quickDate('month')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Este mes</button>
                            <button type="button" onclick="quickDate('last_month')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Mes pasado</button>
                            <button type="button" onclick="quickDate('year')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Este año</button>
                            <button type="button" onclick="quickDate('last_year')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Año anterior</button>
                            <button type="button" onclick="quickDate('3y')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Últimos 3 años</button>
                            <button type="button" onclick="quickDate('5y')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Últimos 5 años</button>
                            <button type="button" onclick="quickDate('all')" class="whitespace-nowrap px-3 py-2 text-left text-sm rounded-lg hover:bg-white hover:shadow-sm transition-all text-slate-600">Todo el tiempo</button>
                        </div>

                        <!-- Right Panel: Single/Dual Calendar -->
                        <div class="flex-1 p-4 min-w-0">
                            <div class="flex items-center justify-between mb-3">
                                <button type="button" onclick="changeMonth(-1)" class="p-1 hover:bg-slate-100 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <div class="font-bold text-slate-800 text-sm" id="calendarMonthLabel">Mes Año</div>
                                <button type="button" onclick="changeMonth(1)" class="p-1 hover:bg-slate-100 rounded-full"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="calendarContainer">
                                <!-- Calendar 1 -->
                                <div>
                                    <div class="grid grid-cols-7 text-center mb-1">
                                        <div class="text-[10px] font-bold text-slate-400">D</div>
                                        <div class="text-[10px] font-bold text-slate-400">L</div>
                                        <div class="text-[10px] font-bold text-slate-400">M</div>
                                        <div class="text-[10px] font-bold text-slate-400">M</div>
                                        <div class="text-[10px] font-bold text-slate-400">J</div>
                                        <div class="text-[10px] font-bold text-slate-400">V</div>
                                        <div class="text-[10px] font-bold text-slate-400">S</div>
                                    </div>
                                    <div class="grid grid-cols-7 gap-0.5" id="calendarGrid1"></div>
                                </div>
                                <!-- Calendar 2 (hidden on small screens) -->
                                <div class="hidden sm:block">
                                    <div class="grid grid-cols-7 text-center mb-1">
                                        <div class="text-[10px] font-bold text-slate-400">D</div>
                                        <div class="text-[10px] font-bold text-slate-400">L</div>
                                        <div class="text-[10px] font-bold text-slate-400">M</div>
                                        <div class="text-[10px] font-bold text-slate-400">M</div>
                                        <div class="text-[10px] font-bold text-slate-400">J</div>
                                        <div class="text-[10px] font-bold text-slate-400">V</div>
                                        <div class="text-[10px] font-bold text-slate-400">S</div>
                                    </div>
                                    <div class="grid grid-cols-7 gap-0.5" id="calendarGrid2"></div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <div class="text-xs text-slate-500">
                                    <span id="selectionText">Selecciona un rango</span>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="resetDates()" class="px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Borrar</button>
                                    <button type="button" onclick="applyFilters()" class="px-5 py-1.5 text-sm font-bold bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 rounded-lg shadow-sm transition-colors">Aplicar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button (Hidden mainly, triggered by UI) -->
            <button type="submit" class="hidden">Filtrar</button>
        </form>
    </div>

    <!-- Payments List -->
    <div class="bg-white rounded-2xl shadow-soft border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-medium">
                    <tr>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Factura</th>
                        <th class="px-6 py-4">Método</th>
                        <th class="px-6 py-4 text-right" style="min-width:220px">Monto</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($pagos ?? []) as $p)
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                            {{ !empty($p['fecha']) ? \Illuminate\Support\Carbon::parse($p['fecha'])->format('d M, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900">{{ $p['cliente'] }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('facturas.show', $p['factura_id']) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 hover:bg-lime-200 hover:text-slate-900 transition-colors text-xs font-bold">
                                #{{ $p['numero'] }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-600 capitalize">
                            {{ $p['metodo'] ?: 'Efectivo' }}
                        </td>
                        <td class="px-6 py-4 text-right" style="min-width:220px">
                            <div class="flex items-center justify-end gap-3">
                                @if(!empty($p['es_extranjera']) && $p['es_extranjera'])
                                <span class="text-sm text-slate-400 font-mono whitespace-nowrap">{{ $p['moneda'] }} {{ number_format($p['monto'] ?? 0, 2) }}</span>
                                @endif
                                <span class="font-mono font-semibold text-slate-800 whitespace-nowrap">{{ $baseCurrency }} {{ number_format($p['monto_base'] ?? $p['monto'] ?? 0, 2) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(!empty($p['es_abono']) && $p['es_abono'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                Abonado
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-lime-100 text-lime-800">
                                Completado
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-action-button :href="route('facturas.print', $p['factura_id'])" icon="pdf" title="Ver Factura" />
                                <form method="POST" action="{{ route('pagos.destroy', ['facturaId' => $p['factura_id'], 'pagoIndex' => $p['pago_index']]) }}" onsubmit="return confirm('Eliminar este pago?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-9 h-9 inline-flex items-center justify-center rounded-full border border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition" title="Eliminar pago">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-lg font-medium text-slate-900">No se encontraron pagos</p>
                                <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginador --}}
        @if(($lastPage ?? 1) > 1)
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <div class="text-sm text-slate-500">
                Página {{ $page ?? 1 }} de {{ $lastPage ?? 1 }} &nbsp;·&nbsp; {{ $countPagos ?? 0 }} transacciones
            </div>
            <div class="flex items-center gap-1">
                @if(($page ?? 1) > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => ($page - 1)]) }}" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700">← Anterior</a>
                @endif
                @for($i = max(1, ($page??1)-2); $i <= min($lastPage??1, ($page??1)+2); $i++)
                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                   class="px-3 py-1.5 text-sm rounded-lg border {{ $i == ($page??1) ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 hover:bg-slate-50 text-slate-700' }}">
                    {{ $i }}
                </a>
                @endfor
                @if(($page ?? 1) < ($lastPage ?? 1))
                <a href="{{ request()->fullUrlWithQuery(['page' => ($page + 1)]) }}" class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700">Siguiente →</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // --- State ---
    let state = {
        dropdowns: { clientDropdown: false, dateDropdown: false },
        date: {
            start: document.getElementById('inputDesde').value ? new Date(document.getElementById('inputDesde').value + 'T12:00:00') : null,
            end: document.getElementById('inputHasta').value ? new Date(document.getElementById('inputHasta').value + 'T12:00:00') : null,
            viewMonth: new Date() // The month shown in the left calendar
        }
    };

    // Initialize viewMonth to start date if exists, else current month
    if (state.date.start) {
        state.date.viewMonth = new Date(state.date.start);
    }
    // Default: select current month on first open if no filter active
    if (!state.date.start && !state.date.end) {
        const today = new Date();
        state.date.start = new Date(today.getFullYear(), today.getMonth(), 1);
        state.date.end   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        state.date.viewMonth = new Date(state.date.start);
    }

    // --- Dropdown Logic ---
    function toggleDropdown(id) {
        const el = document.getElementById(id);
        const isOpen = el.classList.contains('dropdown-active');
        
        // Close all others
        document.querySelectorAll('.dropdown-active').forEach(d => {
            if(d.id !== id) closeDropdown(d.id);
        });

        if (isOpen) closeDropdown(id);
        else openDropdown(id);
    }

    function openDropdown(id) {
        const el = document.getElementById(id);
        el.classList.remove('dropdown-enter');
        el.classList.add('dropdown-active');
        if(id === 'dateDropdown') renderCalendar();
    }

    function closeDropdown(id) {
        const el = document.getElementById(id);
        el.classList.remove('dropdown-active');
        el.classList.add('dropdown-enter');
    }

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#clientSelectWrapper')) closeDropdown('clientDropdown');
        if (!e.target.closest('#datePickerWrapper')) closeDropdown('dateDropdown');
    });

    // --- Client Select Logic ---
    function selectClient(id, name) {
        document.getElementById('inputClientId').value = id;
        document.getElementById('clientSelectLabel').innerText = name;
        closeDropdown('clientDropdown');
        // Optional: auto submit
        // document.getElementById('filterForm').submit();
    }

    function filterClients(query) {
        const items = document.querySelectorAll('.client-option');
        query = query.toLowerCase();
        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            if (name.includes(query) || item.innerText.toLowerCase().includes('todos')) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }

    // --- Calendar Logic ---
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    function changeMonth(delta) {
        state.date.viewMonth.setMonth(state.date.viewMonth.getMonth() + delta);
        renderCalendar();
    }

    function renderCalendar() {
        // Render Month 1 (View Month)
        renderMonthGrid(state.date.viewMonth, document.getElementById('calendarGrid1'));
        
        // Render Month 2 (Next Month)
        const nextMonth = new Date(state.date.viewMonth);
        nextMonth.setMonth(nextMonth.getMonth() + 1);
        renderMonthGrid(nextMonth, document.getElementById('calendarGrid2'));

        // Update Label
        const m1 = months[state.date.viewMonth.getMonth()];
        const y1 = state.date.viewMonth.getFullYear();
        const m2 = months[nextMonth.getMonth()];
        const y2 = nextMonth.getFullYear();
        document.getElementById('calendarMonthLabel').innerText = `${m1} ${y1} - ${m2} ${y2}`;
        
        updateSelectionText();
    }

    function renderMonthGrid(date, container) {
        container.innerHTML = '';
        const year = date.getFullYear();
        const month = date.getMonth();
        
        const firstDay = new Date(year, month, 1).getDay(); // 0 = Sunday
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Empty slots for days before 1st
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            container.appendChild(empty);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const cellDate = new Date(year, month, d);
            const cell = document.createElement('div');
            cell.className = 'calendar-day';
            cell.innerText = d;
            
            // Check logic
            const time = cellDate.getTime();
            const sTime = state.date.start ? state.date.start.getTime() : null;
            const eTime = state.date.end ? state.date.end.getTime() : null;
            
            // Is Today?
            const today = new Date();
            if (cellDate.getDate() === today.getDate() && cellDate.getMonth() === today.getMonth() && cellDate.getFullYear() === today.getFullYear()) {
                cell.classList.add('is-today');
            }

            // Selection Logic
            if (sTime && time === sTime) {
                cell.classList.add('is-range-start');
                if (!eTime) cell.classList.add('is-selected'); // Single selection
            }
            if (eTime && time === eTime) cell.classList.add('is-range-end');
            if (sTime && eTime && time > sTime && time < eTime) cell.classList.add('is-range');
            if (sTime && eTime && time === sTime && time === eTime) cell.classList.add('is-range-start', 'is-range-end'); // One day range

            cell.onclick = () => onDateClick(cellDate);
            container.appendChild(cell);
        }
    }

    function onDateClick(date) {
        // Logic:
        // 1. If nothing selected -> Select Start
        // 2. If Start selected but no End -> Select End (swap if needed)
        // 3. If both selected -> Reset and Select Start
        
        if (!state.date.start || (state.date.start && state.date.end)) {
            state.date.start = date;
            state.date.end = null;
        } else if (state.date.start && !state.date.end) {
            if (date < state.date.start) {
                state.date.end = state.date.start;
                state.date.start = date;
            } else {
                state.date.end = date;
            }
        }
        renderCalendar();
    }

    function updateSelectionText() {
        const label = document.getElementById('selectionText');
        if (state.date.start && state.date.end) {
            label.innerText = `Del ${formatDate(state.date.start)} al ${formatDate(state.date.end)}`;
        } else if (state.date.start) {
            label.innerText = `Desde ${formatDate(state.date.start)}`;
        } else {
            label.innerText = 'Selecciona un rango';
        }
    }

    function formatDate(date) {
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }
    
    function formatDateISO(date) {
        if (!date) return '';
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function quickDate(type) {
        const today = new Date();
        const start = new Date();
        const end = new Date();

        if (type === 'today') {
            // start/end = today
        } else if (type === 'week') {
            const day = today.getDay();
            const diff = today.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
            start.setDate(diff);
            end.setDate(start.getDate() + 6);
        } else if (type === 'month') {
            start.setDate(1);
            end.setDate(new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate());
        } else if (type === 'last_month') {
            start.setMonth(start.getMonth() - 1);
            start.setDate(1);
            end.setMonth(end.getMonth() - 1); // go to last month
            end.setDate(new Date(end.getFullYear(), end.getMonth() + 1, 0).getDate());
        } else if (type === 'year') {
            start.setMonth(0); start.setDate(1);
            end.setMonth(11); end.setDate(31);
        } else if (type === 'last_year') {
            const y = today.getFullYear() - 1;
            start.setFullYear(y); start.setMonth(0); start.setDate(1);
            end.setFullYear(y); end.setMonth(11); end.setDate(31);
        } else if (type === '3y') {
            start.setFullYear(today.getFullYear() - 2); start.setMonth(0); start.setDate(1);
            end.setFullYear(today.getFullYear()); end.setMonth(11); end.setDate(31);
        } else if (type === '5y') {
            start.setFullYear(today.getFullYear() - 4); start.setMonth(0); start.setDate(1);
            end.setFullYear(today.getFullYear()); end.setMonth(11); end.setDate(31);
        } else if (type === 'all') {
            start.setFullYear(2000); start.setMonth(0); start.setDate(1);
            end.setFullYear(today.getFullYear() + 1); end.setMonth(11); end.setDate(31);
        }

        state.date.start = start;
        state.date.end = end;
        state.date.viewMonth = new Date(start);
        renderCalendar();
    }

    function resetDates() {
        state.date.start = null;
        state.date.end = null;
        renderCalendar();
    }

    function applyFilters() {
        // Update hidden inputs
        document.getElementById('inputDesde').value = formatDateISO(state.date.start);
        document.getElementById('inputHasta').value = formatDateISO(state.date.end);
        
        // Submit form
        document.getElementById('filterForm').submit();
    }
</script>
@endsection

