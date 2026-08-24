@extends('layouts.app')
@section('title','Facturas')
@section('content')

  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-slate-900">Facturas</h1>
      <p class="text-slate-500 mt-1">Control de facturación y cobros</p>
    </div>
    <div class="flex justify-end w-full md:w-auto">
      <a href="{{ route('facturas.create') }}" class="inline-flex items-center gap-2 bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-6 py-3 rounded-full font-bold transition-colors shadow-sm whitespace-nowrap">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nueva factura
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 gap-4">
    <div class="bg-white rounded-2xl shadow-soft border p-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
          <div class="text-xs font-bold text-lime-700 bg-lime-100 px-2 py-0.5 rounded-full inline-block">Resumen</div>
          <div class="text-sm font-semibold text-slate-700 mt-1">{{ $rangeLabel ?? 'Mes actual' }}</div>
        </div>
        <div class="flex flex-wrap gap-2">
          <a href="{{ request()->fullUrlWithQuery(['range'=>'all']) }}" class="px-3 py-2 rounded-full text-xs font-bold {{ ($range ?? '')==='all' ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Todos</a>
          <a href="{{ request()->fullUrlWithQuery(['range'=>'month']) }}" class="px-3 py-2 rounded-full text-xs font-bold {{ ($range ?? 'month')==='month' ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Mes actual</a>
          <a href="{{ request()->fullUrlWithQuery(['range'=>'prev']) }}" class="px-3 py-2 rounded-full text-xs font-bold {{ ($range ?? '')==='prev' ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Mes anterior</a>
          <a href="{{ request()->fullUrlWithQuery(['range'=>'6m']) }}" class="px-3 py-2 rounded-full text-xs font-bold {{ ($range ?? '')==='6m' ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Últimos 6 meses</a>
          <a href="{{ request()->fullUrlWithQuery(['range'=>'year']) }}" class="px-3 py-2 rounded-full text-xs font-bold {{ ($range ?? '')==='year' ? 'bg-slate-900 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' }}">Año</a>
        </div>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-xl border p-3 md:p-4 text-white bg-gradient-to-br from-lime-500 to-emerald-500 min-w-0">
          <div class="text-xs/4 opacity-90">Facturado</div>
          <div class="mt-1 text-base sm:text-lg md:text-xl font-extrabold tracking-tight tabular-nums truncate">{{ format_currency($total ?? 0) }}</div>
        </div>
        <div class="rounded-xl bg-neutral-50 border p-3 md:p-4 min-w-0">
          <div class="text-xs text-slate-500">Pagos</div>
          <div class="mt-1 text-base sm:text-lg md:text-xl font-extrabold tracking-tight tabular-nums truncate">{{ format_currency($pagos ?? 0) }}</div>
        </div>
        <button type="button" id="openDueModal" class="rounded-xl bg-neutral-50 border p-3 md:p-4 min-w-0 text-left transition hover:border-slate-300 hover:bg-slate-50">
          <div class="text-xs text-slate-500">Por cobrar</div>
          <div class="mt-1 text-base sm:text-lg md:text-xl font-extrabold tracking-tight tabular-nums truncate">{{ format_currency($dueTotalGlobal ?? $due ?? 0) }}</div>
        </button>
        <button type="button" id="openOverdueModal" class="rounded-xl bg-neutral-50 border p-3 md:p-4 min-w-0 text-left transition hover:border-rose-300 hover:bg-rose-50">
          <div class="text-xs text-slate-500">Vencido</div>
          <div class="mt-1 text-base sm:text-lg md:text-xl font-extrabold tracking-tight tabular-nums truncate {{ ($overdueTotalGlobal ?? 0) > 0 ? 'text-rose-600' : '' }}">{{ format_currency($overdueTotalGlobal ?? 0) }}</div>
        </button>
      </div>
      @php
        $activeMonth = request('month', $month ?? date('Y-m'));
        try {
          $monthDate = \Illuminate\Support\Carbon::createFromFormat('Y-m', $activeMonth);
        } catch (\Exception $e) {
          $monthDate = \Illuminate\Support\Carbon::now();
        }
        $prevMonth = $monthDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthDate->copy()->addMonth()->format('Y-m');
        $monthNames = [
          '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
          '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
          '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];
        $monthLabel = $navMonthLabel ?? (($monthNames[$monthDate->format('m')] ?? $monthDate->format('F')) . ' ' . $monthDate->format('Y'));
      @endphp
      <div class="border-t border-slate-200 mt-6 pt-6"></div>
    </div>
    <div id="facturas-top" class="bg-white rounded-2xl shadow border p-6">
      <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
        <div class="text-xl font-bold shrink-0">Facturas</div>
        <a href="{{ route('facturas.create') }}" class="primary-add-btn shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span class="hidden sm:inline">Crear Factura</span>
          <span class="sm:hidden">Crear</span>
        </a>
      </div>
      <form id="facturasAutoFilterForm" method="GET" class="flex flex-wrap items-center gap-2 mb-4">
        <div class="filter-pill flex-1 min-w-[160px]">
          <svg class="h-4 w-4 filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input name="q" value="{{ $q ?? '' }}" class="w-full" placeholder="Buscar por cliente o número">
        </div>
        <div class="min-w-[160px]">
          <select name="estado" class="w-full">
            <option value="">Todos los estados</option>
            @foreach(['En borrador','Pendiente','Pagada','Vencida','Recurrente'] as $e)
              <option value="{{ $e }}" @selected(($estado ?? '')===$e)>{{ $e }} ({{ $counts[$e] ?? 0 }})</option>
            @endforeach
          </select>
        </div>
        <div class="flex items-center gap-1.5 text-xs shrink-0">
          <a href="{{ request()->fullUrlWithQuery(['range'=>'month','month'=>$prevMonth]) }}" class="rounded-full border bg-white w-8 h-8 flex items-center justify-center text-slate-700 hover:bg-slate-100" title="Mes anterior">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          </a>
          <span class="font-bold text-slate-700 whitespace-nowrap">{{ $monthLabel }}</span>
          <a href="{{ request()->fullUrlWithQuery(['range'=>'month','month'=>$nextMonth]) }}" class="rounded-full border bg-white w-8 h-8 flex items-center justify-center text-slate-700 hover:bg-slate-100" title="Mes siguiente">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
        <input type="hidden" name="range" value="{{ $range ?? 'month' }}">
        <input type="hidden" name="month" value="{{ request('month', $month ?? '') }}">
      </form>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm md:text-sm">
          @php $hayConversion = collect($facturas)->contains(fn($f) => !empty($f['total_base']) && ($f['moneda'] ?? '') !== $base); @endphp
          <thead>
            <tr class="text-left text-slate-500 text-sm md:text-sm">
              <th class="py-2 pr-6 md:pr-10">Cliente</th>
              <th class="py-2 pr-6 md:pr-8">Fecha</th>
              <th class="py-2 pr-6 md:pr-8">Factura</th>
              <th class="py-2 pr-6 md:pr-8">Proyecto</th>
              <th class="py-2 pr-6 md:pr-8">Estado</th>
              <th class="py-2 pr-6 md:pr-8 whitespace-nowrap">Recurrente</th>
              @if($hayConversion)
              <th class="py-2 pr-6 md:pr-8 whitespace-nowrap">Total cliente</th>
              @endif
              <th class="py-2 pr-6 md:pr-8">Total</th>
              <th class="py-2 pr-6 md:pr-8">Pagado</th>
              <th class="py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @forelse($facturasPorMes as $grupo)
            {{-- Subtítulo de mes --}}
            <tr class="month-row">
              <td colspan="{{ $hayConversion ? 10 : 9 }}" class="pt-5 pb-1 px-0">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $grupo['label'] }}</span>
              </td>
            </tr>
            @foreach($grupo['facturas'] as $f)
            @php
              $filaVencida = ($f['estado'] ?? '')==='Vencida' || (in_array(($f['estado'] ?? ''), ['Pendiente','Enviada'], true) && !empty($f['vencimiento']) && $f['vencimiento'] < date('Y-m-d'));
              $estadoMostrar = $f['estado'] ?? '—';
              if ($estadoMostrar === 'Enviada') $estadoMostrar = 'Pendiente';
              $esVencidaVisual = $estadoMostrar === 'Pendiente'
                && !empty($f['vencimiento'])
                && $f['vencimiento'] < date('Y-m-d');
              if ($esVencidaVisual) $estadoMostrar = 'Vencida';
            @endphp
            <tr
              class="invoice-row {{ $filaVencida ? 'bg-rose-50' : '' }}"
              data-search="{{ strtolower(trim(($f['numero'] ?? '') . ' ' . ($f['cliente'] ?? '') . ' ' . ($f['proyecto'] ?? ''))) }}"
              data-estado="{{ strtolower($estadoMostrar) }}"
              data-recurrente="{{ ($f['_is_recurrente'] ?? false) ? '1' : '0' }}"
            >
              <td class="py-3 pr-6 md:pr-10">
                @if(!empty($f['cliente_id']))
                  <a class="no-underline font-medium text-lime-600 whitespace-nowrap text-base md:text-base" href="{{ route('clientes.show',$f['cliente_id']) }}">{{ $f['cliente'] ?? '—' }}</a>
                @else
                  <span class="font-medium text-lime-600 whitespace-nowrap text-base md:text-base">{{ $f['cliente'] ?? '—' }}</span>
                @endif
              </td>
              <td class="py-3 pr-6 md:pr-8 text-base md:text-base whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($f['fecha'])->format('d/m/Y') }}</td>
              <td class="py-3 pr-6 md:pr-8 font-extrabold text-lime-600 whitespace-nowrap text-base md:text-base"><a class="no-underline" href="{{ route('facturas.show',$f['id']) }}">{{ $f['numero'] }}</a></td>
              <td class="py-3 pr-6 md:pr-8 text-base md:text-base whitespace-nowrap">
                @if(!empty($f['proyecto_id']) && !empty($f['cliente_id']))
                  <a class="no-underline" href="/proyectos?cliente_id={{ $f['cliente_id'] }}">{{ $f['proyecto'] ?? '—' }}</a>
                @else
                  {{ $f['proyecto'] ?? '—' }}
                @endif
              </td>
              <td class="py-3 pr-6 md:pr-8 whitespace-nowrap">
                <span class="px-3 py-1 text-sm md:text-sm rounded-full
                  {{ $estadoMostrar==='Pagada' ? 'bg-emerald-100 text-emerald-800' : ($estadoMostrar==='Vencida' ? 'bg-rose-100 text-rose-800' : ($estadoMostrar==='Pendiente' ? 'bg-amber-100 text-amber-800' : 'bg-neutral-100 text-slate-600')) }}">
                  {{ $estadoMostrar }}
                </span>
              </td>
              <td class="py-3 pr-6 md:pr-8 whitespace-nowrap">
                @if(($f['_is_recurrente'] ?? false))
                  @php
                    $recurrenceEnabled = (bool) ($f['_recurrencia_enabled'] ?? false);
                    $recurrenceLabel = $f['_recurrencia_label'] ?? 'Recurrente';
                    $recurrenceTargetId = $f['_recurrencia_target_id'] ?? null;
                  @endphp
                  <div class="inline-flex items-center gap-2">
                    <span
                      data-recurrence-badge="{{ $recurrenceTargetId }}"
                      class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium {{ $recurrenceEnabled ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-50 text-slate-500 border-slate-200' }}"
                    >
                      {{ $recurrenceLabel }}
                    </span>
                    @if(($f['_recurrencia_toggleable'] ?? false))
                      <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $recurrenceEnabled ? 'true' : 'false' }}"
                        aria-label="{{ $recurrenceEnabled ? 'Desactivar' : 'Activar' }} recurrencia {{ $recurrenceLabel }}"
                        title="{{ $recurrenceEnabled ? 'Desactivar próximas facturas' : 'Activar próximas facturas' }}"
                        data-recurrence-toggle="{{ $recurrenceTargetId }}"
                        data-recurrence-url="{{ route('api.facturas.recurrencia.toggle', $f['id']) }}"
                        data-recurrence-enabled="{{ $recurrenceEnabled ? '1' : '0' }}"
                        data-recurrence-frequency="{{ $recurrenceLabel }}"
                        class="group inline-flex shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                      >
                        <span data-recurrence-track class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $recurrenceEnabled ? 'bg-sky-500' : 'bg-slate-300' }}">
                          <span data-recurrence-knob class="inline-block h-4 w-4 rounded-full bg-white shadow-sm transition-transform {{ $recurrenceEnabled ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                        </span>
                      </button>
                    @endif
                  </div>
                @else
                  <span class="text-slate-400">—</span>
                @endif
              </td>
              @if($hayConversion)
              <td class="py-3 pr-6 md:pr-8 whitespace-nowrap text-slate-900 font-medium text-base md:text-base">
                @if(!empty($f['total_base']) && ($f['moneda'] ?? '') !== $base)
                  {{ format_currency($f['total'] ?? 0, $f['moneda'] ?? null) }}
                @endif
              </td>
              @endif
              <td class="py-3 pr-6 md:pr-8 whitespace-nowrap font-extrabold text-slate-900 text-base md:text-base">
                {{ format_currency($f['_total_base'] ?? $f['total_base'] ?? $f['total'] ?? 0, $base) }}
              </td>
              <td class="py-3 pr-6 md:pr-8 whitespace-nowrap">
                <div class="font-semibold text-slate-900 text-base md:text-base">{{ format_currency($f['_paid_base'] ?? 0, $base) }}</div>
                @if(($f['_due_base'] ?? 0) > 0)
                  <div class="text-xs md:text-xs text-rose-600">Debe {{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
                @else
                  <div class="text-xs md:text-xs text-emerald-600">Saldada</div>
                @endif
              </td>
              <td class="py-2 text-right">
                <div class="inline-flex items-center gap-1">
                  <x-action-button :href="route('facturas.edit',$f['id'])" icon="edit" title="Editar" />
                  @if(($f['_due_base'] ?? 0) > 0)
                  <x-action-button
                    type="button"
                    data-pay="{{ $f['id'] }}"
                    data-pay-amount="{{ number_format(($f['total'] ?? 0), 2, ',', '.') }}"
                    data-pay-symbol="{{ currency_symbol($f['moneda'] ?? null) }}"
                    icon="pay"
                    title="Pagar"
                  />
                  @else
                  <span class="inline-grid place-content-center w-9 h-9 rounded-full border border-slate-100 text-slate-300 cursor-not-allowed opacity-40" title="Factura saldada">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v22"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                  </span>
                  @endif
                  <x-action-button type="button" data-dup="{{ $f['id'] }}" icon="duplicate" title="Duplicar" />
                  <x-action-button :href="route('facturas.print',$f['id'])" icon="pdf" title="PDF" />
                  <form method="POST" action="{{ route('facturas.destroy',$f['id']) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="estado" value="{{ request('estado') }}">
                    <input type="hidden" name="range" value="{{ request('range') }}">
                    <x-action-button type="submit" icon="delete" color="rose" title="Eliminar" class="ml-2" onclick="return confirm('¿Eliminar esta factura?')" />
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
            @empty
            <tr>
              <td colspan="{{ $hayConversion ? 10 : 9 }}" class="py-10 text-center text-slate-500 font-medium">Aún no hay facturas.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div id="dueModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <div>
          <div class="text-lg font-bold text-slate-900">Por cobrar</div>
          <div class="text-xs text-slate-500">Facturas pendientes por cobrar</div>
        </div>
        <button id="closeDueModal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">✕</button>
      </div>
      <div class="px-5 pt-4">
        <div class="inline-flex rounded-full bg-slate-100 p-1 gap-1">
          <button type="button" class="due-tab px-3 py-1.5 rounded-full text-sm font-semibold bg-slate-900 text-white" data-target="dueAllTab">Todos</button>
          <button type="button" class="due-tab px-3 py-1.5 rounded-full text-sm font-semibold text-slate-600" data-target="dueCurrentTab">Este mes</button>
          <button type="button" class="due-tab px-3 py-1.5 rounded-full text-sm font-semibold text-slate-600" data-target="duePreviousTab">Meses anteriores</button>
        </div>
      </div>
      <div class="p-5 overflow-y-auto max-h-[65vh] custom-scroll">
        <div id="dueAllTab" class="space-y-4">
          @forelse($dueAllByMonth ?? [] as $grupo)
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $grupo['label'] }}</div>
            @foreach(($grupo['facturas'] ?? []) as $f)
              <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border p-3 no-underline hover:bg-slate-50">
                <div>
                  <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                  <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                  <div class="text-xs text-slate-400">Vence {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
                </div>
                <div class="font-bold text-slate-900 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
              </a>
            @endforeach
          @empty
            <div class="text-sm text-slate-500">No hay facturas por cobrar.</div>
          @endforelse
        </div>
        <div id="dueCurrentTab" class="space-y-3 hidden">
          @forelse($dueEsteMes as $f)
            <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border p-3 no-underline hover:bg-slate-50">
              <div>
                <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                <div class="text-xs text-slate-400">Vence {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
              </div>
              <div class="font-bold text-slate-900 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
            </a>
          @empty
            <div class="text-sm text-slate-500">No hay facturas por cobrar este mes.</div>
          @endforelse
        </div>
        <div id="duePreviousTab" class="space-y-3 hidden">
          @forelse($duePasados as $f)
            <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border p-3 no-underline hover:bg-slate-50">
              <div>
                <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                <div class="text-xs text-slate-400">Vence {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
              </div>
              <div class="font-bold text-slate-900 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
            </a>
          @empty
            <div class="text-sm text-slate-500">No hay facturas pendientes de meses anteriores.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
  <div id="overdueModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <div>
          <div class="text-lg font-bold text-rose-600">Vencido</div>
          <div class="text-xs text-slate-500">Facturas vencidas</div>
        </div>
        <button id="closeOverdueModal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">✕</button>
      </div>
      <div class="px-5 pt-4">
        <div class="inline-flex rounded-full bg-slate-100 p-1 gap-1">
          <button type="button" class="overdue-tab px-3 py-1.5 rounded-full text-sm font-semibold bg-slate-900 text-white" data-target="overdueAllTab">Todos</button>
          <button type="button" class="overdue-tab px-3 py-1.5 rounded-full text-sm font-semibold text-slate-600" data-target="overdueCurrentTab">Este mes</button>
          <button type="button" class="overdue-tab px-3 py-1.5 rounded-full text-sm font-semibold text-slate-600" data-target="overduePreviousTab">Meses anteriores</button>
        </div>
      </div>
      <div class="p-5 overflow-y-auto max-h-[65vh] custom-scroll">
        <div id="overdueAllTab" class="space-y-4">
          @forelse($overdueAllByMonth ?? [] as $grupo)
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $grupo['label'] }}</div>
            @foreach(($grupo['facturas'] ?? []) as $f)
              <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border border-rose-200 p-3 no-underline hover:bg-rose-50">
                <div>
                  <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                  <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                  <div class="text-xs text-slate-400">Venció {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
                </div>
                <div class="font-bold text-rose-600 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
              </a>
            @endforeach
          @empty
            <div class="text-sm text-slate-500">No hay facturas vencidas.</div>
          @endforelse
        </div>
        <div id="overdueCurrentTab" class="space-y-3 hidden">
          @forelse($overdueEsteMes as $f)
            <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border border-rose-200 p-3 no-underline hover:bg-rose-50">
              <div>
                <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                <div class="text-xs text-slate-400">Venció {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
              </div>
              <div class="font-bold text-rose-600 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
            </a>
          @empty
            <div class="text-sm text-slate-500">No hay facturas vencidas este mes.</div>
          @endforelse
        </div>
        <div id="overduePreviousTab" class="space-y-3 hidden">
          @forelse($overdueMesPasado as $f)
            <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border border-rose-200 p-3 no-underline hover:bg-rose-50">
              <div>
                <div class="font-bold text-slate-900">{{ $f['numero'] }}</div>
                <div class="text-sm text-slate-500">{{ $f['cliente'] ?? '—' }}</div>
                <div class="text-xs text-slate-400">Venció {{ !empty($f['vencimiento']) ? \Illuminate\Support\Carbon::parse($f['vencimiento'])->format('d/m/Y') : 'sin fecha' }}</div>
              </div>
              <div class="font-bold text-rose-600 whitespace-nowrap">{{ format_currency($f['_due_base'] ?? 0, $base) }}</div>
            </a>
          @empty
            <div class="text-sm text-slate-500">No hay facturas vencidas de meses anteriores.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
  <div id="payModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-5 relative">
      <button id="closePay" class="absolute right-3 top-3 text-slate-500">✕</button>
      <div class="text-lg font-bold mb-3">Registrar pago</div>
      <div class="grid gap-3">
        <div>
          <label class="text-sm font-semibold text-slate-700">Monto</label>
          <div class="relative">
            <span id="payCurrencySymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
            <input id="payAmount" type="text" class="form-input w-full pl-8" placeholder="Ej. 100.00">
          </div>
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Método</label>
          <select id="payMethod" class="form-select w-full">
            @foreach(($paymentMethods ?? ['Transferencia', 'Efectivo', 'Tarjeta', 'Otro']) as $method)
              <option value="{{ $method }}">{{ $method }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Fecha de pago</label>
          <input id="payDate" type="text" value="{{ date('Y-m-d') }}" class="form-input w-full">
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Nota</label>
          <input id="payNote" class="form-input w-full" placeholder="Referencia o comentario">
        </div>
        <div class="flex justify-end gap-2 mt-2">
          <button id="savePay" class="px-4 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition-colors">Guardar Pago</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    function toggleModal(modal, open) {
      if (!modal) return;
      modal.classList.toggle('hidden', !open);
      modal.classList.toggle('flex', open);
    }

    function setupTabs(buttonSelector, activeClasses) {
      const buttons = document.querySelectorAll(buttonSelector);
      buttons.forEach((button) => {
        button.addEventListener('click', () => {
          const targetId = button.getAttribute('data-target');
          buttons.forEach((other) => {
            other.classList.remove(...activeClasses);
            other.classList.add('text-slate-600');
          });
          button.classList.add(...activeClasses);
          button.classList.remove('text-slate-600');

          buttons.forEach((other) => {
            const otherTarget = other.getAttribute('data-target');
            document.getElementById(otherTarget)?.classList.add('hidden');
          });
          document.getElementById(targetId)?.classList.remove('hidden');
        });
      });
    }

    const dueModal = document.getElementById('dueModal');
    const overdueModal = document.getElementById('overdueModal');
    document.getElementById('openDueModal')?.addEventListener('click', ()=> toggleModal(dueModal, true));
    document.getElementById('openOverdueModal')?.addEventListener('click', ()=> toggleModal(overdueModal, true));
    document.getElementById('closeDueModal')?.addEventListener('click', ()=> toggleModal(dueModal, false));
    document.getElementById('closeOverdueModal')?.addEventListener('click', ()=> toggleModal(overdueModal, false));
    dueModal?.addEventListener('click', (e)=>{ if(e.target===dueModal){ toggleModal(dueModal, false); }});
    overdueModal?.addEventListener('click', (e)=>{ if(e.target===overdueModal){ toggleModal(overdueModal, false); }});
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      if (dueModal?.classList.contains('flex')) toggleModal(dueModal, false);
      if (overdueModal?.classList.contains('flex')) toggleModal(overdueModal, false);
      if (payModal?.classList.contains('flex')) {
        payModal.classList.add('hidden');
        payModal.classList.remove('flex');
      }
    });
    setupTabs('.due-tab', ['bg-slate-900','text-white']);
    setupTabs('.overdue-tab', ['bg-slate-900','text-white']);

    const payModal = document.getElementById('payModal');
    const closePay = document.getElementById('closePay');
    const payDate = document.getElementById('payDate');
    const payNote = document.getElementById('payNote');
    let currentInvoice = null;
    document.querySelectorAll('[data-pay]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        currentInvoice = btn.getAttribute('data-pay');
        const amount = btn.getAttribute('data-pay-amount') || '';
        const symbol = btn.getAttribute('data-pay-symbol') || '$';
        const payAmount = document.getElementById('payAmount');
        const paySymbol = document.getElementById('payCurrencySymbol');
        if (paySymbol) paySymbol.textContent = symbol;
        if (payAmount && amount) payAmount.value = amount;
        payModal.classList.remove('hidden');
        payModal.classList.add('flex');
      });
    });
    document.querySelectorAll('[data-dup]').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        if (btn.disabled) return;
        btn.disabled = true;
        const id = btn.getAttribute('data-dup');
        try {
          const res = await fetch('/api/facturas/duplicar/'+id, { method:'POST', headers:{'X-CSRF-TOKEN': window.csrfToken }});
          const js = await res.json().catch(()=>null);
          if (js && js.edit_url) {
            location.href = js.edit_url;
          } else {
            btn.disabled = false;
          }
        } catch(e) {
          btn.disabled = false;
        }
      });
    });

    function paintRecurrenceToggle(button, enabled) {
      const frequency = button.dataset.recurrenceFrequency || 'Recurrente';
      const track = button.querySelector('[data-recurrence-track]');
      const knob = button.querySelector('[data-recurrence-knob]');
      const badge = button.closest('div')?.querySelector('[data-recurrence-badge]');

      button.dataset.recurrenceEnabled = enabled ? '1' : '0';
      button.setAttribute('aria-checked', enabled ? 'true' : 'false');
      button.setAttribute('aria-label', `${enabled ? 'Desactivar' : 'Activar'} recurrencia ${frequency}`);
      button.title = enabled ? 'Desactivar próximas facturas' : 'Activar próximas facturas';
      track?.classList.toggle('bg-sky-500', enabled);
      track?.classList.toggle('bg-slate-300', !enabled);
      knob?.classList.toggle('translate-x-4', enabled);
      knob?.classList.toggle('translate-x-0.5', !enabled);
      badge?.classList.toggle('bg-sky-50', enabled);
      badge?.classList.toggle('text-sky-700', enabled);
      badge?.classList.toggle('border-sky-200', enabled);
      badge?.classList.toggle('bg-slate-50', !enabled);
      badge?.classList.toggle('text-slate-500', !enabled);
      badge?.classList.toggle('border-slate-200', !enabled);
    }

    document.querySelectorAll('[data-recurrence-toggle]').forEach((button) => {
      button.addEventListener('click', async () => {
        if (button.disabled) return;

        const recurrenceId = button.dataset.recurrenceToggle;
        const enabled = button.dataset.recurrenceEnabled !== '1';
        const relatedButtons = Array.from(document.querySelectorAll('[data-recurrence-toggle]'))
          .filter((candidate) => candidate.dataset.recurrenceToggle === recurrenceId);

        relatedButtons.forEach((candidate) => {
          candidate.disabled = true;
          candidate.setAttribute('aria-busy', 'true');
          candidate.classList.add('opacity-60');
        });

        try {
          const response = await fetch(button.dataset.recurrenceUrl, {
            method: 'PATCH',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ enabled }),
          });
          const json = await response.json().catch(() => null);
          if (!response.ok || !json?.ok) {
            throw new Error(json?.message || 'No se pudo actualizar la recurrencia');
          }

          relatedButtons.forEach((candidate) => paintRecurrenceToggle(candidate, json.enabled));
          window.showNotification?.(json.message, 'success');
        } catch (error) {
          window.showNotification?.(error?.message || 'No se pudo actualizar la recurrencia', 'error');
        } finally {
          relatedButtons.forEach((candidate) => {
            candidate.disabled = false;
            candidate.removeAttribute('aria-busy');
            candidate.classList.remove('opacity-60');
          });
        }
      });
    });
    closePay?.addEventListener('click', ()=>{ payModal.classList.add('hidden'); payModal.classList.remove('flex'); });
    payModal?.addEventListener('click', (e)=>{ if(e.target===payModal){ payModal.classList.add('hidden'); payModal.classList.remove('flex'); }});
    document.getElementById('savePay')?.addEventListener('click', async ()=>{
      const saveBtn = document.getElementById('savePay');
      if (!currentInvoice || saveBtn.disabled) return;
      saveBtn.disabled = true;
      const montoRaw = (document.getElementById('payAmount').value || '').replace(/\./g,'').replace(',','.');
      const body = { id: currentInvoice, monto: montoRaw, metodo: document.getElementById('payMethod').value, fecha_pago: payDate.value, nota: payNote.value };
      try {
        const res = await fetch('/api/facturas/pagar', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN': window.csrfToken }, body: JSON.stringify(body) });
        const json = await res.json().catch(()=>null);
        if (!res.ok || !json?.ok) {
          throw new Error(json?.message || 'No se pudo registrar el pago');
        }
        if (window.showNotification && json.message) {
          window.showNotification(json.message, json.mail_sent ? 'success' : 'warning');
          setTimeout(()=>location.reload(), 900);
        } else {
          location.reload();
        }
      } catch(e) {
        saveBtn.disabled = false;
        if (window.showNotification) {
          window.showNotification(e?.message || 'No se pudo registrar el pago', 'error');
        }
      }
    });

    if (window.flatpickr) {
      flatpickr("#payDate", {
        altInput: true,
        altFormat: "d/m/Y",
        dateFormat: "Y-m-d",
        locale: "es",
        onOpen: function(selectedDates, dateStr, instance) {
          instance.calendarContainer.classList.add("airline-calendar");
        }
      });
    }

    // Filtro local sin recarga (buscador + estado)
    const facturasFilterForm = document.getElementById('facturasAutoFilterForm');
    const facturasSearchInput = facturasFilterForm?.querySelector('input[name="q"]');
    const facturasStatusSelect = facturasFilterForm?.querySelector('select[name="estado"]');
    const tbody = document.querySelector('#facturas-top table tbody');
    const invoiceRows = Array.from(tbody?.querySelectorAll('tr.invoice-row') || []);
    const monthRows = Array.from(tbody?.querySelectorAll('tr.month-row') || []);

    function normalizeText(value) {
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    }

    function applyLocalInvoiceFilters() {
      const q = normalizeText(facturasSearchInput?.value || '');
      const estadoValue = String(facturasStatusSelect?.value || '').trim();
      const estadoNorm = normalizeText(estadoValue);

      invoiceRows.forEach((row) => {
        const rowSearch = normalizeText(row.dataset.search || row.textContent || '');
        const rowEstado = normalizeText(row.dataset.estado || '');
        const isRecurrente = row.dataset.recurrente === '1';

        const matchesQ = q === '' || rowSearch.includes(q);
        let matchesEstado = true;

        if (estadoNorm !== '') {
          if (estadoNorm === 'recurrente') {
            matchesEstado = isRecurrente;
          } else {
            matchesEstado = rowEstado === estadoNorm;
          }
        }

        row.classList.toggle('hidden', !(matchesQ && matchesEstado));
      });

      monthRows.forEach((monthRow) => {
        let next = monthRow.nextElementSibling;
        let hasVisibleInvoice = false;
        while (next && !next.classList.contains('month-row')) {
          if (next.classList.contains('invoice-row') && !next.classList.contains('hidden')) {
            hasVisibleInvoice = true;
            break;
          }
          next = next.nextElementSibling;
        }
        monthRow.classList.toggle('hidden', !hasVisibleInvoice);
      });
    }

    if (facturasFilterForm) {
      facturasFilterForm.addEventListener('submit', (e) => e.preventDefault());
    }
    facturasSearchInput?.addEventListener('input', applyLocalInvoiceFilters);
    facturasStatusSelect?.addEventListener('change', applyLocalInvoiceFilters);
    applyLocalInvoiceFilters();
  </script>
@endsection
