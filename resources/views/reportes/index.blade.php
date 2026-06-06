@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="relative overflow-hidden rounded-3xl p-6 md:p-8 border border-slate-200 shadow-sm mb-6 bg-white">
    <div class="absolute -top-16 -right-12 w-60 h-60 rounded-full" style="background: radial-gradient(circle, rgba(var(--accent-ring), 0.35) 0%, rgba(255,255,255,0) 70%);"></div>
    <div class="absolute -bottom-20 -left-10 w-56 h-56 rounded-full" style="background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, rgba(255,255,255,0) 70%);"></div>

    <div class="relative flex flex-col gap-5">
        <div>
            <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500 mb-3">
                <span class="w-2 h-2 rounded-full" style="background-color: var(--accent-500);"></span>
                Centro de reportes
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900">Reportes con datos reales</h1>
            <p class="text-slate-600 mt-2">Vista consolidada de ingresos, gastos por proveedor, clientes y operación para {{ $periodLabel }}.</p>
        </div>

        {{-- Selector de periodo --}}
        <div class="bg-white/90 rounded-2xl border border-slate-200 p-4 shadow-sm">
            <div class="flex flex-wrap items-start gap-6">
                {{-- Año --}}
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 mb-2">Año</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($years as $year)
                            <a href="{{ route('reportes.index', ['year' => $year, 'month' => $selectedMonth]) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold border transition-all
                               {{ (string)$year === (string)$selectedYear
                                  ? 'border-[#d4ed5c] text-slate-900 shadow-sm'
                                  : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                               style="{{ (string)$year === (string)$selectedYear ? 'background:#ecfe88;' : '' }}">
                                {{ $year }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="w-px self-stretch bg-slate-200 hidden sm:block"></div>

                {{-- Mes --}}
                <div class="flex-1 min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 mb-2">Mes</div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('reportes.index', ['year' => $selectedYear, 'month' => 'all']) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold border transition-all
                           {{ $selectedMonth === 'all'
                              ? 'border-[#d4ed5c] text-slate-900 shadow-sm'
                              : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                           style="{{ $selectedMonth === 'all' ? 'background:#ecfe88;' : '' }}">
                            Todo el año
                        </a>
                        @foreach($monthOptions as $m)
                            <a href="{{ route('reportes.index', ['year' => $selectedYear, 'month' => $m['value']]) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-semibold border transition-all
                               {{ $selectedMonth === $m['value']
                                  ? 'border-[#d4ed5c] text-slate-900 shadow-sm'
                                  : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700' }}"
                               style="{{ $selectedMonth === $m['value'] ? 'background:#ecfe88;' : '' }}">
                                {{ $m['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Exportar --}}
        <div class="flex flex-wrap gap-2 mt-1">
            <span class="self-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Exportar:</span>
            @foreach([['financiero','Financiero'],['proveedores','Proveedores'],['proyectos','Proyectos']] as [$tp,$label])
            <div class="inline-flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-2 py-1 shadow-sm">
                <span class="text-xs font-semibold text-slate-600 mr-1">{{ $label }}</span>
                <a href="{{ route('reportes.export', ['type'=>$tp,'format'=>'excel','year'=>$selectedYear,'month'=>$selectedMonth]) }}"
                   class="px-2 py-0.5 rounded-lg bg-slate-900 text-white text-xs font-bold hover:bg-slate-700 transition-colors">Excel</a>
                <a href="{{ route('reportes.export', ['type'=>$tp,'format'=>'pdf','year'=>$selectedYear,'month'=>$selectedMonth]) }}"
                   class="px-2 py-0.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">PDF</a>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 uppercase tracking-[0.12em] font-semibold mb-1">Ingresos pagados</div>
        <div class="text-3xl font-extrabold text-slate-900">{{ format_currency($totalIngresos, $baseCurrency) }}</div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 uppercase tracking-[0.12em] font-semibold mb-1">Gastos ejecutados</div>
        <div class="text-3xl font-extrabold text-slate-900">{{ format_currency($totalGastos, $baseCurrency) }}</div>
    </div>
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs text-slate-500 uppercase tracking-[0.12em] font-semibold mb-1">Balance neto</div>
        <div class="text-3xl font-extrabold {{ $balance >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_currency($balance, $baseCurrency) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-6">
    <div class="xl:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-extrabold text-slate-900 text-lg">Tendencia mensual</h3>
                <p class="text-sm text-slate-600">Comparativo de ingresos y gastos por mes en {{ $selectedYear }}.</p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full font-semibold" style="background-color: rgba(var(--accent-ring),0.18); color: var(--accent-700);">Base {{ $baseCurrency }}</span>
        </div>
        <div class="h-[320px]">
            <canvas id="incomeExpenseChart"></canvas>
        </div>
    </div>

    <div class="xl:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="mb-4">
            <h3 class="font-extrabold text-slate-900 text-lg">Estructura del flujo</h3>
            <p class="text-sm text-slate-600">Peso de ingresos y gastos del periodo seleccionado.</p>
        </div>
        <div class="h-[260px]">
            <canvas id="financeDonutChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-6">
    <div class="xl:col-span-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="mb-4">
            <h3 class="font-extrabold text-slate-900 text-lg">Top clientes (pagos)</h3>
            <p class="text-sm text-slate-600">Clientes que más aportan ingresos reales en el periodo.</p>
        </div>
        <div class="h-[300px]">
            <canvas id="topClientsChart"></canvas>
        </div>
    </div>

    <div class="xl:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="mb-4">
            <h3 class="font-extrabold text-slate-900 text-lg">Gasto por proveedor</h3>
            <p class="text-sm text-slate-600">Distribucion de costos sobre el total de gastos del periodo.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-4 items-center">
            <div class="h-[250px]">
                <canvas id="expenseSupplierDonutChart"></canvas>
            </div>
            <div class="space-y-3">
                @forelse($gastosPorProveedor as $prov => $monto)
                    @php $pct = $totalGastos > 0 ? ($monto / $totalGastos) * 100 : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-semibold text-slate-700">{{ $prov }}</span>
                            <span class="font-bold text-slate-900">{{ format_currency($monto, $baseCurrency) }}</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-rose-50">
                            <div class="h-2 rounded-full progress-fill-anim" style="width: {{ min(100, $pct) }}%; background: linear-gradient(90deg, #b91c1c, #fb7185);"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">No hay gastos para el periodo seleccionado.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
    <div class="xl:col-span-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="mb-4">
            <h3 class="font-extrabold text-slate-900 text-lg">Estado de proyectos</h3>
            <p class="text-sm text-slate-600">Concentracion de proyectos por etapa.</p>
        </div>
        <div class="h-[260px]">
            <canvas id="projectStatusChart"></canvas>
        </div>
    </div>

    <div class="xl:col-span-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
        <div class="mb-4">
            <h3 class="font-extrabold text-slate-900 text-lg">Pipeline de leads</h3>
            <p class="text-sm text-slate-600">Estado actual de prospectos en el periodo.</p>
        </div>
        <div class="space-y-3 max-h-[260px] overflow-auto pr-2">
            @forelse($leadsStatus as $status => $count)
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50/70">
                    <span class="font-semibold text-slate-700">{{ $status }}</span>
                    <span class="inline-flex items-center justify-center min-w-[34px] px-2 py-1 rounded-lg text-sm font-bold text-slate-900" style="background-color: var(--accent-300);">{{ $count }}</span>
                </div>
            @empty
                <div class="text-sm text-slate-500">No hay leads en este periodo.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
(function () {
    const styles = getComputedStyle(document.body);
    const accent500 = (styles.getPropertyValue('--accent-500') || '#84cc16').trim();
    const accent700 = (styles.getPropertyValue('--accent-700') || '#4d7c0f').trim();
    const slate700 = '#334155';
    const slate400 = '#94a3b8';

    const chartIncomeExpense = @json($chartIncomeExpense);
    const chartExpenseSupplier = @json($chartExpenseSupplier);
    const chartTopClients = @json($chartTopClients);
    const chartProjectStatus = @json($chartProjectStatus);
    const totalIngresos = {{ (float) $totalIngresos }};
    const totalGastos = {{ (float) $totalGastos }};
    const expenseSupplierColors = ['#b91c1c', '#dc2626', '#ef4444', '#f97316', '#fb7185', '#991b1b'];

    const formatMoney = (n) => {
        try {
            return new Intl.NumberFormat('es-CO', { style: 'currency', currency: '{{ $baseCurrency }}', maximumFractionDigits: 0 }).format(n || 0);
        } catch (e) {
            return '$' + Number(n || 0).toLocaleString('es-CO');
        }
    };

    const lineCtx = document.getElementById('incomeExpenseChart');
    if (lineCtx && window.Chart) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: chartIncomeExpense.labels || [],
                datasets: [
                    {
                        label: 'Ingresos',
                        data: chartIncomeExpense.income || [],
                        borderColor: accent500,
                        backgroundColor: 'rgba(132,204,22,0.2)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3
                    },
                    {
                        label: 'Gastos',
                        data: chartIncomeExpense.expenses || [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: slate700 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${formatMoney(ctx.raw)}` } }
                },
                scales: {
                    x: { ticks: { color: slate400 }, grid: { color: 'rgba(148,163,184,0.16)' } },
                    y: { ticks: { color: slate400, callback: (v) => formatMoney(v) }, grid: { color: 'rgba(148,163,184,0.16)' } }
                }
            }
        });
    }

    const financeCtx = document.getElementById('financeDonutChart');
    if (financeCtx && window.Chart) {
        new Chart(financeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Gastos'],
                datasets: [{ data: [Math.max(totalIngresos, 0), Math.max(totalGastos, 0)], backgroundColor: [accent500, '#ef4444'], borderWidth: 3, borderColor: '#fff' }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: slate700 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatMoney(ctx.raw)}` } }
                }
            }
        });
    }

    const clientsCtx = document.getElementById('topClientsChart');
    if (clientsCtx && window.Chart) {
        new Chart(clientsCtx, {
            type: 'bar',
            data: {
                labels: chartTopClients.labels || [],
                datasets: [{ data: chartTopClients.values || [], backgroundColor: 'rgba(132,204,22,0.75)', borderColor: accent700, borderWidth: 1.2, borderRadius: 10 }]
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => formatMoney(ctx.raw) } } },
                scales: {
                    x: { ticks: { color: slate400, callback: (v) => formatMoney(v) }, grid: { color: 'rgba(148,163,184,0.16)' } },
                    y: { ticks: { color: slate700 }, grid: { display: false } }
                }
            }
        });
    }

    const supplierCtx = document.getElementById('expenseSupplierDonutChart');
    if (supplierCtx && window.Chart) {
        new Chart(supplierCtx, {
            type: 'doughnut',
            data: {
                labels: chartExpenseSupplier.labels || [],
                datasets: [{ data: chartExpenseSupplier.values || [], backgroundColor: expenseSupplierColors, borderColor: '#fff', borderWidth: 3 }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: slate700 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatMoney(ctx.raw)}` } }
                }
            }
        });
    }

    const projectCtx = document.getElementById('projectStatusChart');
    if (projectCtx && window.Chart) {
        new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: chartProjectStatus.labels || [],
                datasets: [{ data: chartProjectStatus.values || [], backgroundColor: [accent500, '#3b82f6', '#f59e0b', '#ef4444', '#14b8a6', '#6366f1'], borderRadius: 10 }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `${ctx.raw} proyectos` } } },
                scales: {
                    x: { ticks: { color: slate400 }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { color: slate400, precision: 0 }, grid: { color: 'rgba(148,163,184,0.16)' } }
                }
            }
        });
    }
})();
</script>
@endsection
