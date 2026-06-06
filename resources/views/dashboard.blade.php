@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php
  $allowedDashboardTabs = ['resumen', 'proyectos', 'ventas'];
  try {
      $dashboardRole = strtolower((string) (auth()->user()->role ?? session('user.role', 'admin')));
      if ($dashboardRole !== 'admin') {
          $rolesStore = new \App\Repositories\FileStore('roles.json');
          $roleData = $rolesStore->find($dashboardRole);
          $perms = collect($roleData['permissions'] ?? []);
          if ($perms->contains('*')) {
              $allowedDashboardTabs = ['resumen', 'proyectos', 'ventas'];
          } else {
              $allowedDashboardTabs = [];
              foreach (['resumen', 'proyectos', 'ventas'] as $tab) {
                  if ($perms->contains('dashboard.'.$tab)) {
                      $allowedDashboardTabs[] = $tab;
                  }
              }
              if (empty($allowedDashboardTabs)) {
                  $allowedDashboardTabs = ['resumen'];
              }
          }
      }
  } catch (\Throwable) {
      $allowedDashboardTabs = ['resumen', 'proyectos', 'ventas'];
  }
  $defaultDashboardTab = in_array('resumen', $allowedDashboardTabs, true)
      ? 'resumen'
      : ($allowedDashboardTabs[0] ?? 'resumen');
@endphp
      <style>
        .dashboard-card-rail {
          scrollbar-width: thin;
          scrollbar-color: #cbd5e1 #f1f5f9;
          scroll-padding-inline: 0.25rem;
        }
        .dashboard-card-rail::-webkit-scrollbar {
          height: 10px;
        }
        .dashboard-card-rail::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 999px;
        }
        .dashboard-card-rail::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border: 2px solid #f1f5f9;
          border-radius: 999px;
        }
        .dashboard-card-rail::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
      </style>

      <!-- Tabs de Navegación -->
      <div class="mt-6 flex gap-2 overflow-x-auto overflow-y-visible text-sm font-semibold scrollbar-hide px-1 py-1">
        @if(in_array('resumen', $allowedDashboardTabs, true))
          <button onclick="switchTab('resumen')" id="tab-resumen" class="shrink-0 px-5 py-2 rounded-full bg-slate-900 text-white transition-all">Resumen</button>
        @endif
        @if(in_array('proyectos', $allowedDashboardTabs, true))
          <button onclick="switchTab('proyectos')" id="tab-proyectos" class="shrink-0 px-5 py-2 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">Proyectos</button>
        @endif
        @if(in_array('ventas', $allowedDashboardTabs, true))
          <button onclick="switchTab('ventas')" id="tab-ventas" class="shrink-0 px-5 py-2 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">Ventas</button>
        @endif
      </div>

      <!-- SECCIÓN: RESUMEN -->
      <div id="view-resumen" class="dashboard-view mt-6">
          <div class="grid grid-cols-12 gap-6">
              
              <!-- TOP ROW: Stats (Asymmetrical Layout) -->
              
              <!-- 1. Ingresos por período (Large - 4 Cols) -->
              <div class="col-span-12 md:col-span-6 lg:col-span-4 bg-[#ecfe88] rounded-2xl shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden min-h-[230px]">
                  <div class="relative z-10">
                      <div class="flex items-center justify-between mb-2">
                          <div class="text-slate-800 text-sm font-bold">Ingresos del {{ $summaryRangeLabel }}</div>
                          <div class="relative">
                            <button type="button" id="summaryRangeMenuBtn" class="p-1 rounded-full bg-black/5 hover:bg-black/10 transition-colors cursor-pointer">
                            <svg class="w-4 h-4 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                            </button>
                            <div id="summaryRangeMenu" class="hidden absolute right-0 mt-2 w-44 rounded-xl border border-slate-200 bg-white shadow-xl z-20 overflow-hidden">
                              @php $q = request()->query(); @endphp
                              @foreach(['month' => 'Mes', 'semester' => 'Semestre', 'year' => 'Año', 'all' => 'Todo'] as $rangeKey => $rangeLabel)
                                <a href="{{ route('dashboard', array_merge($q, ['summary_range' => $rangeKey])) }}"
                                   class="block px-4 py-2 text-sm font-semibold {{ $summaryRange === $rangeKey ? 'bg-[#f3fea4] text-slate-900' : 'text-slate-700 hover:bg-slate-50' }}">
                                  {{ $rangeLabel }}
                                </a>
                              @endforeach
                            </div>
                          </div>
                      </div>
                      @php $compactIncome = currency_symbol() . format_compact_number($monthlyIncome, 1); @endphp
                      <div class="flex items-center gap-3 group relative cursor-help">
                        @if(abs($monthlyIncome) >= 1000)
                            <div class="text-5xl font-extrabold text-slate-900 tracking-tight">{{ $compactIncome }}</div>
                        @else
                            <div class="text-5xl font-extrabold text-slate-900 tracking-tight">{{ format_currency($monthlyIncome) }}</div>
                        @endif
                        
                        <!-- Custom Tooltip on Hover -->
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-slate-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20 shadow-lg">
                            {{ format_currency($monthlyIncome) }}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-slate-800"></div>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 mt-2">
                          <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-900 text-white text-[10px] font-bold shadow-sm">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $growth >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' }}"/></svg>
                              {{ $growth >= 0 ? '+' : '' }}{{ number_format($growth, 0) }}%
                          </div>
                          <span class="text-xs text-slate-600 font-medium">{{ $summaryCompareLabel }}</span>
                      </div>
                      <div class="mt-1 text-[11px] font-semibold text-slate-700">
                        Libre: {{ format_currency($freeMoney) }}
                      </div>
                  </div>
                  @php
                    $sparkIncomes = collect($chartData)->map(fn($d) => max(0, (float) ($d['income'] ?? 0)));
                    $sparkMax = max(1, (float) $sparkIncomes->max());
                  @endphp
                  <div class="absolute bottom-0 right-0 w-full h-24 flex items-end justify-end gap-1 px-6 pb-6 pointer-events-none opacity-50">
                      @foreach($chartData as $d)
                          @php
                            $incomeVal = max(0, (float) ($d['income'] ?? 0));
                            $ratio = $sparkMax > 0 ? ($incomeVal / $sparkMax) : 0;
                            $scaled = pow($ratio, 0.72);
                            $h = max(8, min(100, 8 + ($scaled * 92)));
                          @endphp
                          <div class="w-1/6 bg-slate-900/20 rounded-t-sm" style="height: {{ number_format($h, 2, '.', '') }}%"></div>
                      @endforeach
                  </div>
              </div>

              <!-- 2. Meta de Ventas (Large - 4 Cols) -->
              <div class="col-span-12 md:col-span-6 lg:col-span-4 bg-slate-900 rounded-2xl shadow-sm p-6 flex flex-col hover:shadow-md transition-shadow text-white min-h-[230px]">
                  <div>
                      <div class="flex justify-between items-start gap-3 mb-5">
                          <div class="text-slate-400 text-sm font-bold leading-snug">Meta de Ventas ({{ $summaryRangeLabel }})</div>
                          <span class="shrink-0 whitespace-nowrap text-[11px] font-extrabold text-lime-400 bg-lime-400/10 px-3 py-1.5 rounded-full">{{ number_format($salesProgress, 0) }}% completado</span>
                      </div>
                      <div class="flex items-baseline gap-2 mb-2">
                          <div class="text-4xl font-extrabold tracking-tight">{{ format_compact_number($monthlyIncome, 1) }}</div> <!-- Current Progress -->
                          <span class="text-sm text-slate-500 font-medium">/ {{ format_compact_number($salesGoal, 1) }}</span>
                      </div>
                      
                      <!-- Progress Bar -->
                      <div class="w-full bg-slate-800 rounded-full h-3 mt-4 overflow-hidden relative">
                          <div class="progress-fill-anim bg-gradient-to-r from-lime-400 to-green-500 h-3 rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(163,230,53,0.5)]" style="width: {{ $salesProgress }}%"></div>
                      </div>
                  </div>
                  <div class="mt-6">
                      <button onclick="openGoalModal()" class="w-full py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-slate-300 transition-colors border border-white/5">Ajustar Objetivo</button>
                  </div>
              </div>

              <!-- 4. Gastos del Mes (Small - 2 Cols) -->
              <div class="col-span-6 md:col-span-6 lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow h-[200px]">
                  <div>
                      <div class="w-10 h-10 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-4">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg> <!-- Trend Down Icon for expenses? No, expenses usually imply money out. -->
                      </div>
                      <div class="text-3xl font-extrabold text-slate-900 tracking-tight group relative cursor-help">
                        {{ currency_symbol() . format_compact_number($monthlyExpenses, 1) }}
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-slate-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20 shadow-lg">
                            {{ format_currency($monthlyExpenses) }}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-slate-800"></div>
                        </div>
                      </div>
                      <div class="text-slate-500 text-xs font-bold mt-1">Gastos Totales ({{ $summaryRangeLabel }})</div>
                  </div>
                  <div class="mt-auto">
                      <span class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded-md">Salidas</span>
                  </div>
              </div>

              <!-- 3. Clientes Totales (Small - 2 Cols) -->
              <div class="col-span-6 md:col-span-6 lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow h-[200px]">
                  <div>
                      <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                      </div>
                      <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ format_compact_number($totalClients) }}</div>
                      <div class="text-slate-500 text-xs font-bold mt-1">Clientes Activos</div>
                  </div>
                  <div class="mt-auto">
                      <span class="text-[10px] text-blue-500 font-bold bg-blue-50 px-2 py-1 rounded-md">+{{ count($topClients) }} Recientes</span>
                  </div>
              </div>

              <!-- MIDDLE ROW -->
              
              <!-- Main Chart (8 Cols) -->
              <div class="col-span-12 lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col min-h-[400px]">
                                    <div class="mb-6">
                                        <h3 class="font-bold text-lg text-slate-900 whitespace-nowrap">Análisis Financiero</h3>
                                        <div class="mt-3">
                                            <div class="flex flex-wrap items-start gap-2 text-xs font-bold">
                        <span class="px-3 py-1 rounded-full bg-slate-900 text-white whitespace-nowrap">Ingresos</span>
                        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 whitespace-nowrap">Egresos</span>
                                                <div id="chart-period-tags" class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:ml-0">
                            <button type="button" data-period="6m" onclick="setChartPeriod('6m', this)" class="px-3 py-1 rounded-full bg-slate-900 text-white whitespace-nowrap">6 meses</button>
                            <button type="button" data-period="this_year" onclick="setChartPeriod('this_year', this)" class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors whitespace-nowrap">Este año</button>
                            <button type="button" data-period="last_year" onclick="setChartPeriod('last_year', this)" class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors whitespace-nowrap">Año pasado</button>
                                                        <button type="button" data-period="all" onclick="setChartPeriod('all', this)" class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors whitespace-nowrap">Todos</button>
                        </div>
                                            </div>
                    </div>
                  </div>
                  <div class="flex-1 relative w-full h-full">
                      <canvas id="advancedChart"></canvas>
                  </div>
              </div>

              <!-- Upcoming Invoices (4 Cols - Vertical List) -->
              <div class="col-span-12 lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col min-h-[400px]">
                  <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                      <h3 class="font-bold text-lg text-slate-900">Vencimientos</h3>
                      <span class="text-[10px] font-bold bg-orange-100 text-orange-600 px-2 py-1 rounded-full uppercase tracking-wide">Próximos 7 Días</span>
                  </div>
                  <div class="p-4 space-y-3 overflow-y-auto flex-1 custom-scroll">
                      @forelse($upcomingInvoices as $inv)
                          <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-orange-200 hover:bg-orange-50/50 transition-all group cursor-pointer">
                              <div class="flex items-center gap-3">
                                  <div class="flex flex-col items-center justify-center w-12 h-12 bg-white border border-slate-100 text-slate-700 rounded-xl shadow-sm group-hover:shadow-md transition-shadow">
                                      <span class="text-[10px] font-bold uppercase text-slate-400">{{ \Carbon\Carbon::parse($inv['vencimiento'])->format('M') }}</span>
                                      <span class="text-lg font-extrabold leading-none text-slate-900">{{ \Carbon\Carbon::parse($inv['vencimiento'])->format('d') }}</span>
                                  </div>
                                  <div>
                                      <div class="font-bold text-slate-900 text-sm group-hover:text-orange-600 transition-colors">Factura #{{ $inv['numero'] }}</div>
                                      <div class="text-xs text-slate-500 truncate max-w-[120px]">{{ $inv['cliente'] ?? 'Cliente' }}</div>
                                  </div>
                              </div>
                              <div class="text-right">
                                  <div class="font-bold text-slate-900 text-sm">{{ format_currency(collect($inv['items']??[])->sum(fn($x)=>($x['cantidad']??0)*($x['precio']??0))*1.16) }}</div>
                                  <div class="text-[10px] text-orange-500 font-bold bg-orange-50 px-2 py-0.5 rounded-full inline-block mt-1">Pendiente</div>
                              </div>
                          </div>
                      @empty
                          <div class="flex flex-col items-center justify-center py-12 text-center h-full">
                              <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center mb-4 ring-8 ring-green-50/50">
                                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                              </div>
                              <p class="text-slate-900 font-bold">¡Sin vencimientos!</p>
                              <p class="text-xs text-slate-400 mt-1 max-w-[150px]">No tienes facturas por vencer en los próximos 7 días.</p>
                          </div>
                      @endforelse
                  </div>
                  <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                      <a href="{{ route('facturas.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">Ver todas las facturas &rarr;</a>
                  </div>
              </div>

              <!-- BOTTOM ROW -->

              <!-- Top Clients (8 Cols) -->
              <div class="col-span-12 lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                  <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                      <h3 class="font-bold text-lg text-slate-900">Mejores Clientes</h3>
                      <span class="text-xs font-medium bg-lime-100 text-lime-700 px-2 py-1 rounded-full">Top 5 del Año</span>
                  </div>
                  <div class="overflow-x-auto">
                      <table class="w-full text-left text-sm">
                          <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-400 border-b border-slate-100">
                              <tr>
                                  <th class="px-6 py-3 font-semibold">Cliente</th>
                                  <th class="px-6 py-3 font-semibold text-center">Facturas</th>
                                  <th class="px-6 py-3 font-semibold text-right">Total Facturado</th>
                                  <th class="px-6 py-3 font-semibold text-right">Acción</th>
                              </tr>
                          </thead>
                          <tbody class="divide-y divide-slate-50">
                              @forelse($topClients as $client)
                                  <tr class="group hover:bg-slate-50 transition-colors cursor-pointer" onclick="window.location.href='{{ route('clientes.show', $client['id']) }}'">
                                      <td class="px-6 py-4">
                                          <div class="flex items-center gap-3">
                                              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold shadow-sm border border-white">
                                                  {{ substr($client['name'], 0, 1) }}
                                              </div>
                                              <div>
                                                  <div class="font-bold text-slate-700 group-hover:text-slate-900">{{ $client['name'] }}</div>
                                                  <div class="text-[10px] text-slate-400">ID: #{{ $client['id'] }}</div>
                                              </div>
                                          </div>
                                      </td>
                                      <td class="px-6 py-4 text-center">
                                          <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                              {{ $client['count'] }}
                                          </span>
                                      </td>
                                      <td class="px-6 py-4 text-right">
                                          <div class="font-bold text-slate-900">{{ format_currency($client['total']) }}</div>
                                          <div class="w-full bg-slate-100 rounded-full h-1 mt-2 max-w-[100px] ml-auto overflow-hidden">
                                              <div class="progress-fill-anim bg-lime-500 h-1 rounded-full" style="width: {{ min(100, ($client['total'] / ($monthlyIncome > 0 ? $monthlyIncome : 1)) * 50) }}%"></div>
                                          </div>
                                      </td>
                                      <td class="px-6 py-4 text-right">
                                          <button class="text-slate-400 hover:text-slate-600 transition-colors">
                                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                          </button>
                                      </td>
                                  </tr>
                              @empty
                                  <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">No hay datos suficientes</td></tr>
                              @endforelse
                          </tbody>
                      </table>
                  </div>
              </div>

              <!-- Activity Feed (4 Cols) -->
              <div class="col-span-12 lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col h-full">
                  <div class="flex justify-between items-center mb-6">
                      <h3 class="font-bold text-lg text-slate-900">Actividad Reciente</h3>
                      <button id="recent-activity-toggle" type="button" onclick="toggleRecentActivity()" class="text-xs font-bold text-blue-500 hover:text-blue-700 transition-colors">Ver todo</button>
                  </div>
                  <div id="recent-activity-list" class="flex-1 overflow-y-auto max-h-[300px] space-y-6 custom-scroll pr-2 relative">
                      <!-- Timeline Line -->
                      <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-slate-100"></div>

                      @forelse($activities as $act)
                          <div class="flex gap-4 group relative z-10 cursor-pointer hover:bg-slate-50 p-2 rounded-lg transition-colors" onclick="window.location.href='{{ $act['link'] ?? '#' }}'">
                              <div class="flex-shrink-0 mt-0.5">
                                  <div class="w-10 h-10 rounded-full bg-white border-2 border-slate-100 flex items-center justify-center text-xs font-bold text-slate-500 shadow-sm group-hover:border-blue-500 group-hover:text-blue-500 transition-colors">
                                      {{ $act['initials'] }}
                                  </div>
                              </div>
                              <div class="flex-1 pb-4 border-b border-slate-50 last:border-0">
                                  <div class="flex items-baseline justify-between mb-1">
                                      <span class="text-xs font-bold text-slate-900">{{ $act['user'] }}</span>
                                      <span class="text-[10px] text-slate-400 font-medium">{{ \Carbon\Carbon::parse($act['date'])->diffForHumans() }}</span>
                                  </div>
                                  <p class="text-sm font-medium text-slate-600 group-hover:text-slate-800 transition-colors">{{ $act['title'] }}</p>
                                  <div class="mt-1 text-xs text-slate-400 truncate max-w-[200px]">
                                      {{ $act['description'] }}
                                  </div>
                              </div>
                          </div>
                      @empty
                          <div class="text-center py-10 text-slate-400 text-sm">
                              Sin actividad reciente.
                          </div>
                      @endforelse
                  </div>
              </div>

          </div>
      </div>

      <!-- SECCIÓN: PROYECTOS -->
      <div id="view-proyectos" class="dashboard-view mt-6 hidden">
          @php
              $projectStats = $projectDashboard['stats'] ?? ['total' => 0, 'completed' => 0, 'running' => 0, 'overdue' => 0];
              $projectList  = $projectDashboard['projects'] ?? [];

              // Horas trabajadas de proyectos: salen de time_logs, independientes de ventas/leads.
              $_pH          = $projectDashboard['hours'] ?? [];
              $_pPeriod     = (string) ($_pH['selected_period'] ?? 'this_week');
              $_pTotals     = (array)  ($_pH['totals']  ?? []);
              $_pAgenda     = (array)  ($_pH['agenda']  ?? []);
              $_pManual     = (array)  ($_pH['manual']  ?? []);
              $_pTrends     = (array)  ($_pH['trends']  ?? []);
              $_pTotal      = (float)  ($_pTotals[$_pPeriod] ?? 0);
              $_pAgendaVal  = (float)  ($_pAgenda[$_pPeriod] ?? 0);
              $_pManualVal  = (float)  ($_pManual[$_pPeriod] ?? 0);
              $_pTrend      = (array)  ($_pTrends[$_pPeriod] ?? ['value' => 0, 'direction' => 'neutral']);
              $_tArrow      = fn($d) => $d === 'up' ? '↑' : ($d === 'down' ? '↓' : '→');
              $_tClass      = fn($d) => $d === 'up' ? 'bg-emerald-100 text-emerald-700' : ($d === 'down' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600');
              $_priorityMeta = function ($priority) {
                  $value = strtolower(trim((string) $priority));
                  if (in_array($value, ['urgente', 'alta'], true)) {
                      return [
                          'label' => 'Urgente',
                          'bg' => '#fff1f2',
                          'border' => '#fecdd3',
                          'text' => '#e11d48',
                          'strip' => '#ef4444',
                          'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.4"/><path stroke-linecap="round" stroke-width="2.4" d="M12 7v6"/><path stroke-linecap="round" stroke-width="2.8" d="M12 17h.01"/></svg>',
                      ];
                  }
                  if (in_array($value, ['con calma', 'baja'], true)) {
                      return [
                          'label' => 'Con calma',
                          'bg' => '#ecfdf5',
                          'border' => '#86efac',
                          'text' => '#047857',
                          'strip' => '#16a34a',
                          'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2.4"/><path stroke-linecap="round" stroke-width="2.4" d="M8 14s1.2 2 4 2 4-2 4-2"/><path stroke-linecap="round" stroke-width="2.8" d="M9 9h.01M15 9h.01"/></svg>',
                      ];
                  }
                  return [
                      'label' => 'Atención',
                      'bg' => '#fffbeb',
                      'border' => '#facc15',
                      'text' => '#c2410c',
                      'strip' => '#f59e0b',
                      'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linejoin="round" stroke-width="2.4" d="M12 3.5 21 19H3l9-15.5Z"/><path stroke-linecap="round" stroke-width="2.4" d="M12 9v4"/><path stroke-linecap="round" stroke-width="2.8" d="M12 16.5h.01"/></svg>',
                  ];
              };

              // Agregar todas las tareas para el carrusel
              $_allTasks = [];
              foreach ($projectList as $_p) {
                  foreach (($_p['tasks'] ?? []) as $_t) {
                      $_t['_proj'] = $_p['title'] ?? '';
                      $_t['_project_id'] = $_p['id'] ?? '';
                      $_t['_project_priority'] = $_p['priority'] ?? 'Atención';
                      $_allTasks[] = $_t;
                  }
              }
              usort($_allTasks, function($a, $b) {
                  $da = !empty($a['done']) ? 1 : 0;
                  $db = !empty($b['done']) ? 1 : 0;
                  if ($da !== $db) return $da - $db;
                  $ea = !empty($a['due_date']) ? strtotime($a['due_date']) : PHP_INT_MAX;
                  $eb = !empty($b['due_date']) ? strtotime($b['due_date']) : PHP_INT_MAX;
                  return $ea - $eb;
              });
          @endphp

          {{-- KPI Stats --}}
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Proyectos Totales</div>
                  <div class="mt-1 text-4xl font-extrabold text-slate-900">{{ $projectStats['total'] }}</div>
              </div>
              <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                  <div class="text-xs uppercase tracking-wide text-slate-500">En Ejecución</div>
                  <div class="mt-1 text-4xl font-extrabold text-slate-900">{{ $projectStats['running'] }}</div>
              </div>
              <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Finalizados</div>
                  <div class="mt-1 text-4xl font-extrabold text-emerald-600">{{ $projectStats['completed'] }}</div>
              </div>
              <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Atrasados</div>
                  <div class="mt-1 text-4xl font-extrabold text-rose-600">{{ $projectStats['overdue'] }}</div>
              </div>
          </div>

          {{-- Fila: Mis Proyectos + Horas Trabajadas --}}
          <div class="mt-6 grid grid-cols-12 gap-6 items-start">
              <div class="col-span-12 lg:col-span-8">
                  <div class="flex items-center justify-between mb-3">
                      <h3 class="text-lg font-extrabold text-slate-900">Mis Proyectos</h3>
                      <a href="{{ route('proyectos.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">Ver todos &rarr;</a>
                  </div>
                  <div class="dashboard-card-rail flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory">
                      @forelse($projectList as $proj)
                          @php
                              $_due   = !empty($proj['due_date'])     ? \Carbon\Carbon::parse($proj['due_date'])     : null;
                              $_start = !empty($proj['fecha_inicio']) ? \Carbon\Carbon::parse($proj['fecha_inicio']) : null;
                              $_dl    = $_due ? (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($_due->copy()->startOfDay(), false) : null;

                              if ($_dl === null)       { $_lbl = 'Sin fecha';          $_bg = '#94a3b8'; }
                              elseif ($_dl < 0)        { $_lbl = 'Vencido';            $_bg = '#ef4444'; }
                              elseif ($_dl <= 7)       { $_lbl = $_dl.' día'.($_dl!==1?'s':'').' restante'.($_dl!==1?'s':''); $_bg = '#ef4444'; }
                              elseif ($_dl <= 30)      { $_w = (int)ceil($_dl/7);  $_lbl = $_w.' semana'.($_w!==1?'s':'');  $_bg = '#f59e0b'; }
                              elseif ($_dl <= 365)     { $_m = (int)ceil($_dl/30); $_lbl = $_m.' mes'.($_m!==1?'es':'');    $_bg = '#0d9488'; }
                              else                     { $_y = (int)ceil($_dl/365);$_lbl = $_y.' año'.($_y!==1?'s':'');     $_bg = '#16a34a'; }

                              $_prog    = (int) ($proj['progress'] ?? 0);
                              $_owners  = collect($proj['responsables'] ?? [])->merge(collect($proj['tasks'] ?? [])->flatMap(fn($t) => $t['owners'] ?? []))->filter()->unique()->values()->take(3)->all();
                              $_tasksTotal = (int) ($proj['tasks_total'] ?? 0);
                              $_seconds = (int) ($proj['time_seconds'] ?? 0);
                              $_hours = intdiv($_seconds, 3600);
                              $_mins = intdiv($_seconds % 3600, 60);
                              $_timeLabel = $_hours . 'h ' . $_mins . 'm invertidas';
                              $_meta = $_priorityMeta($proj['priority'] ?? 'Atención');
                              $_isOverdue = $_dl !== null && $_dl < 0;
                              $_strip = $_isOverdue ? '#ef4444' : $_meta['strip'];
                          @endphp
                          <button type="button" onclick="openDashboardProjectModal('{{ $proj['id'] }}')" class="group relative flex-shrink-0 w-[21rem] bg-white rounded-2xl border border-slate-200 shadow-sm snap-start overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all focus:outline-none focus:ring-4 focus:ring-lime-200 text-left">
                              <div class="absolute inset-x-0 top-0 h-1" style="background:{{ $_strip }};"></div>
                              <div class="p-5 pt-6 flex min-h-[220px] flex-col gap-3">
                                  <div class="flex items-start justify-between gap-3">
                                      <div class="text-[11px] font-semibold text-slate-400">
                                          {{ $_start ? $_start->format('M d, Y') : '—' }} &ndash; {{ $_due ? $_due->format('M d, Y') : '—' }}
                                      </div>
                                      <span class="text-slate-300 opacity-0 transition-opacity group-hover:opacity-100">•••</span>
                                  </div>

                                  <div>
                                      <div class="font-extrabold text-slate-900 text-lg leading-tight line-clamp-2">{{ $proj['title'] }}</div>
                                      <div class="text-sm text-slate-500 mt-0.5">{{ $proj['stage'] ?? 'Sin etapa' }}</div>
                                  </div>

                                  <div>
                                      <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
                                          <span>Progreso</span><span>{{ $_prog }}%</span>
                                      </div>
                                      <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                          <div class="h-full rounded-full transition-all" style="width:{{ $_prog }}%;background:{{ $_bg }};"></div>
                                      </div>
                                  </div>

                                  <div class="flex items-center justify-between text-[11px] text-slate-500 font-semibold">
                                      <span>{{ $_tasksTotal }} tarea{{ $_tasksTotal === 1 ? '' : 's' }}</span>
                                      <span>{{ $_timeLabel }}</span>
                                  </div>

                                  <div class="mt-auto flex items-center justify-between gap-3">
                                      <div class="flex -space-x-2">
                                          @forelse($_owners as $_o)
                                              @php $_ini = strtoupper(collect(explode(' ', (string) $_o))->map(fn($w)=>substr($w,0,1))->implode('')); @endphp
                                              <div class="w-8 h-8 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-600" title="{{ $_o }}">{{ substr($_ini,0,2) }}</div>
                                          @empty
                                              <div class="w-8 h-8 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-500">SR</div>
                                          @endforelse
                                      </div>

                                      <div class="flex flex-wrap items-center justify-end gap-2">
                                          @if($_isOverdue)
                                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-extrabold text-white" style="background:#ef4444;">Vencido</span>
                                          @else
                                              <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-extrabold" style="background:{{ $_meta['bg'] }};border-color:{{ $_meta['border'] }};color:{{ $_meta['text'] }};">
                                                  {!! $_meta['icon'] !!}
                                                  <span>{{ $_meta['label'] }}</span>
                                              </span>
                                              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-extrabold text-white" style="background:{{ $_bg }};">{{ $_lbl }}</span>
                                          @endif
                                      </div>
                                  </div>
                              </div>
                          </button>
                      @empty
                          <div class="flex-shrink-0 w-full py-10 text-center text-slate-400 text-sm">No hay proyectos asignados.</div>
                      @endforelse
                  </div>

                  <div class="mt-4">
                      <div class="flex items-center justify-between mb-3">
                          <h3 class="text-lg font-extrabold text-slate-900">Tareas</h3>
                          <a href="{{ route('proyectos.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">Ver proyectos &rarr;</a>
                      </div>
                      <div class="dashboard-card-rail flex gap-3 overflow-x-auto pb-4 snap-x">
                          @forelse($_allTasks as $_task)
                              @php
                                  $_td   = !empty($_task['done']);
                                  $_tDue = !empty($_task['due_date']) ? \Carbon\Carbon::parse($_task['due_date']) : null;
                                  $_tDiff = $_tDue ? (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($_tDue->copy()->startOfDay(), false) : null;
                                  $_tOv  = !$_td && $_tDiff !== null && $_tDiff < 0;
                                  $_tMins = isset($_task['total_seconds']) ? (int)round($_task['total_seconds']/60) : 0;
                                  $_tOwners = array_slice($_task['owners'] ?? [], 0, 3);
                                  $_tPriority = $_task['priority'] ?: ($_task['_project_priority'] ?? 'Atención');
                                  if (!$_td && $_tDiff !== null && $_tDiff <= 7 && $_tDiff >= 0) {
                                      $_tPriority = 'Atención';
                                  }
                                  $_tMeta = $_priorityMeta($_tPriority);
                                  $_taskUrl = route('proyectos.index', ['open_project' => $_task['_project_id'] ?? '', 'open_task' => $_task['id'] ?? '']);
                                  if ($_td) {
                                      $_tBg='#f0fdf4'; $_tBdr='#bbf7d0'; $_tBadgeBg='#dcfce7'; $_tBadgeFg='#16a34a'; $_tLbl='Completada'; $_tIcon='<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="m5 13 4 4L19 7"/></svg>';
                                  } elseif ($_tOv) {
                                      $_tBg='#fff1f2'; $_tBdr='#fecdd3'; $_tBadgeBg='#f1f5f9'; $_tBadgeFg='#334155'; $_tLbl='Vencido'; $_tIcon='<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2.4"/><path stroke-linecap="round" stroke-width="2.4" d="m9 9 6 6m0-6-6 6"/></svg>';
                                  } else {
                                      $_tBg='#fff'; $_tBdr='#e2e8f0'; $_tBadgeBg=$_tMeta['bg']; $_tBadgeFg=$_tMeta['text']; $_tLbl=$_tMeta['label']; $_tIcon=$_tMeta['icon'];
                                  }
                              @endphp
                              <button type="button" onclick="openDashboardTaskModal('{{ $_task['_project_id'] ?? '' }}', '{{ $_task['id'] ?? '' }}')" class="flex-shrink-0 w-60 min-h-[165px] rounded-2xl border p-4 snap-start flex flex-col gap-2 hover:-translate-y-0.5 hover:shadow-md transition-all focus:outline-none focus:ring-4 focus:ring-lime-200 text-left" style="background:{{ $_tBg }};border-color:{{ $_tBdr }};">
                                  <div class="flex items-start justify-between gap-1">
                                      <span class="text-sm font-bold text-slate-900 leading-tight pr-1" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $_task['text'] ?? 'Tarea' }}</span>
                                      <span class="flex-shrink-0 inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-extrabold" style="background:{{ $_tBadgeBg }};color:{{ $_tBadgeFg }};border-color:{{ $_td ? '#bbf7d0' : ($_tOv ? '#cbd5e1' : $_tMeta['border']) }};">
                                          {!! $_tIcon !!}
                                          <span>{{ $_tLbl }}</span>
                                      </span>
                                  </div>
                                  <div class="text-[11px] text-slate-500 font-semibold truncate">{{ $_task['_proj'] }}</div>
                                  @if($_tDue)
                                      <div class="text-[11px] font-semibold {{ $_tOv ? 'text-rose-500' : 'text-slate-400' }}">
                                          Vence: {{ $_tDue->format('d M Y') }}
                                      </div>
                                  @endif
                                  <div class="flex items-center justify-between mt-auto pt-1">
                                      <div class="flex -space-x-1">
                                          @foreach($_tOwners as $_o)
                                              <div class="w-6 h-6 rounded-full bg-slate-200 border border-white flex items-center justify-center text-[9px] font-bold text-slate-600" title="{{ $_o }}">{{ strtoupper(substr($_o,0,2)) }}</div>
                                          @endforeach
                                      </div>
                                      @if($_tMins > 0)
                                          <span class="text-[10px] text-slate-400 font-semibold">{{ $_tMins }}min</span>
                                      @endif
                                  </div>
                              </button>
                          @empty
                              <div class="flex-shrink-0 py-10 text-center text-slate-400 text-sm w-full">No hay tareas disponibles.</div>
                          @endforelse
                      </div>
                  </div>
              </div>

              <div class="col-span-12 lg:col-span-4">
                  <section class="rounded-2xl border border-[#26324d] p-5 shadow-sm shadow-slate-900/20" style="background:#111728;">
                      <div class="flex items-center justify-between gap-2 mb-3">
                          <h3 class="text-lg font-extrabold text-white">Horas Trabajadas</h3>
                          <span id="project-hours-trend" class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-bold text-[#f0fe97]">{{ $_tArrow($_pTrend['direction'] ?? 'neutral') }} {{ number_format((float)($_pTrend['value'] ?? 0), 1) }}%</span>
                      </div>
                      <div id="project-hours-period-tabs" class="flex flex-wrap items-center gap-1.5 mb-3 text-[11px] font-bold">
                          @foreach(['this_week' => 'Esta semana', 'last_week' => 'Semana pasada', 'this_month' => 'Este mes'] as $_pk => $_pl)
                              <button type="button" data-hours-group="project" data-period="{{ $_pk }}" class="px-2.5 py-0.5 rounded-full whitespace-nowrap transition-colors {{ $_pPeriod === $_pk ? 'bg-[#f0fe97] text-slate-950' : 'bg-white/10 text-slate-200 hover:bg-white/15' }}">{{ $_pl }}</button>
                          @endforeach
                      </div>
                      <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4">
                          <div class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color:#f0fe97;opacity:.6;">Total del período</div>
                          <div id="project-hours-total" class="text-5xl font-extrabold leading-none tracking-tight" style="color:#f0fe97;">{{ number_format($_pTotal, 1) }}h</div>
                          <div id="project-hours-breakdown" class="text-xs font-semibold mt-2" style="color:#f0fe97;opacity:.7;">Agenda: {{ number_format($_pAgendaVal, 1) }}h &nbsp;·&nbsp; Manual: {{ number_format($_pManualVal, 1) }}h</div>
                      </div>
                      <div class="mt-3">
                          <label class="text-xs font-semibold uppercase tracking-wide text-slate-300">Añadir tiempo</label>
                          <button type="button" onclick="openDashboardAddTimeModal()" class="mt-1 w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-lime-300 text-slate-900 text-sm font-bold hover:bg-lime-200 transition-colors">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                              <span>Añadir tiempo</span>
                          </button>
                      </div>
                  </section>
              </div>
          </div>

          {{-- Roadmap Gantt (al final) --}}
          <div class="mt-6 rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden">
              <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-slate-100 bg-slate-50/60">
                  <div class="flex flex-wrap items-center gap-3 min-w-0">
                      <select id="projectSelector" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 max-w-xs">
                          @forelse($projectList as $proj)
                              <option value="{{ $proj['id'] }}">{{ $proj['title'] }} · {{ $proj['client_name'] }}</option>
                          @empty
                              <option value="">Sin proyectos disponibles</option>
                          @endforelse
                      </select>
                      <div id="ganttMeta" class="hidden md:flex items-center gap-2 text-xs text-slate-500"></div>
                  </div>
                  <div class="flex items-center gap-2">
                      <a href="{{ route('proyectos.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-300 transition-colors">Ver módulo &rarr;</a>
                      <button id="ganttPrev" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors font-bold">&larr;</button>
                      <span id="ganttLabel" class="text-sm font-extrabold text-slate-900 min-w-[130px] text-center"></span>
                      <button id="ganttNext" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors font-bold">&rarr;</button>
                  </div>
              </div>
              <div id="ganttWrap" style="overflow:auto; max-height:560px;"></div>
          </div>
      </div>

      <!-- SECCIÓN: VENTAS (Foco comercial para vendedores) -->
      <div id="view-ventas" class="dashboard-view mt-6 hidden">
          @php
              $sales = $salesDashboard ?? [];
              $stageCounts = $sales['stage_counts'] ?? [];
              $maxStageCount = max(1, ...array_values($stageCounts ?: ['Posible cliente' => 0]));
              $trendUpClass = 'bg-emerald-100 text-emerald-700';
              $trendDownClass = 'bg-rose-100 text-rose-700';
              $trendNeutralClass = 'bg-slate-100 text-slate-600';
              $trendArrow = function ($direction) {
                  return $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→');
              };
              $trendClass = function ($direction) use ($trendUpClass, $trendDownClass, $trendNeutralClass) {
                  return $direction === 'up' ? $trendUpClass : ($direction === 'down' ? $trendDownClass : $trendNeutralClass);
              };
              $trendCreated = $sales['trends']['created_week'] ?? ['value' => 0, 'direction' => 'neutral'];
              $trendWon = $sales['trends']['won_month'] ?? ['value' => 0, 'direction' => 'neutral'];
              $trendBudget = $sales['trends']['avg_budget_week'] ?? ['value' => 0, 'direction' => 'neutral'];
              $trendFollowups = $sales['trends']['followups_week'] ?? ['value' => 0, 'direction' => 'neutral'];
              $hoursData = $sales['hours'] ?? [];
              $hoursSelectedPeriod = (string) ($hoursData['selected_period'] ?? 'this_week');
              $hoursLabels = (array) ($hoursData['period_labels'] ?? []);
              $hoursTotals = (array) ($hoursData['totals'] ?? []);
              $hoursAgenda = (array) ($hoursData['agenda'] ?? []);
              $hoursManual = (array) ($hoursData['manual'] ?? []);
              $hoursTrends = (array) ($hoursData['trends'] ?? []);
              $hoursSelectedTotal = (float) ($hoursTotals[$hoursSelectedPeriod] ?? 0);
              $hoursSelectedAgenda = (float) ($hoursAgenda[$hoursSelectedPeriod] ?? 0);
              $hoursSelectedManual = (float) ($hoursManual[$hoursSelectedPeriod] ?? 0);
              $hoursSelectedTrend = (array) ($hoursTrends[$hoursSelectedPeriod] ?? ['value' => 0, 'direction' => 'neutral']);
          @endphp

          <div class="grid grid-cols-12 gap-6">
              <article class="col-span-12 sm:col-span-6 lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm min-h-[132px]">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div class="text-[11px] uppercase tracking-wide text-slate-500 leading-4 pr-2">Leads creados (semana)</div>
                      <span class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $trendClass($trendCreated['direction']) }}">{{ $trendArrow($trendCreated['direction']) }} {{ number_format((float) $trendCreated['value'], 1) }}%</span>
                  </div>
                  <div class="mt-2 text-4xl font-extrabold text-slate-900">{{ $sales['created_this_week'] ?? 0 }}</div>
              </article>

              <article class="col-span-12 sm:col-span-6 lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm min-h-[132px]">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div class="text-[11px] uppercase tracking-wide text-slate-500 leading-4 pr-2">Cierres del mes</div>
                      <span class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $trendClass($trendWon['direction']) }}">{{ $trendArrow($trendWon['direction']) }} {{ number_format((float) $trendWon['value'], 1) }}%</span>
                  </div>
                  <div class="mt-2 text-4xl font-extrabold text-slate-900">{{ $sales['won_this_month'] ?? 0 }}</div>
              </article>

              <article class="col-span-12 sm:col-span-6 lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm min-h-[132px]">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div class="text-[11px] uppercase tracking-wide text-slate-500 leading-4 pr-2">Presupuesto promedio (semana)</div>
                      <span class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $trendClass($trendBudget['direction']) }}">{{ $trendArrow($trendBudget['direction']) }} {{ number_format((float) $trendBudget['value'], 1) }}%</span>
                  </div>
                  <div class="mt-2 text-4xl font-extrabold text-slate-900">{{ format_compact_number($sales['avg_budget_this_week'] ?? 0) }}</div>
              </article>

              <article class="col-span-12 sm:col-span-6 lg:col-span-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm min-h-[132px]">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <div class="text-[11px] uppercase tracking-wide text-slate-500 leading-4 pr-2">Seguimientos (7 dias)</div>
                      <span class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $trendClass($trendFollowups['direction']) }}">{{ $trendArrow($trendFollowups['direction']) }} {{ number_format((float) $trendFollowups['value'], 1) }}%</span>
                  </div>
                  <div class="mt-2 text-4xl font-extrabold text-slate-900">{{ $sales['followups_this_week'] ?? 0 }}</div>
              </article>

              <div class="col-span-12 lg:col-span-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                  <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-extrabold text-slate-900">Embudo Comercial</h3>
                      <a href="{{ route('leads.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">Ver Kanban &rarr;</a>
                  </div>
                  <div class="space-y-3">
                      @foreach(['Posible cliente','Contactado','Volver a llamar','Cliente'] as $stage)
                          @php
                              $count = (int) ($stageCounts[$stage] ?? 0);
                              $width = (int) round(($count / $maxStageCount) * 100);
                          @endphp
                          <div>
                              <div class="flex items-center justify-between text-sm font-semibold text-slate-700 mb-1">
                                  <span>{{ $stage }}</span>
                                  <span>{{ $count }}</span>
                              </div>
                              <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                  <div class="h-full rounded-full bg-slate-900" style="width: {{ $width }}%"></div>
                              </div>
                          </div>
                      @endforeach
                  </div>
                  <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                      <a href="{{ route('leads.create') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold hover:bg-lime-200 transition-colors">Nuevo lead</a>
                      <a href="{{ route('cotizaciones.index') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition-colors">Crear cotizacion</a>
                  </div>

                  <section class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                      <h3 class="text-lg font-extrabold text-slate-900">Mis Leads</h3>
                      <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                          <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                              <div class="text-xs uppercase tracking-wide text-slate-500">Mis leads</div>
                              <div class="text-2xl font-extrabold text-slate-900">{{ $sales['my_leads'] ?? 0 }}</div>
                          </div>
                          <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                              <div class="text-xs uppercase tracking-wide text-slate-500">Capacidad de cierre</div>
                              <div class="text-2xl font-extrabold text-emerald-600">{{ $sales['close_capacity'] ?? 0 }}%</div>
                          </div>
                          <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                              <div class="text-xs uppercase tracking-wide text-slate-500">Presupuesto en pipeline</div>
                              <div class="text-2xl font-extrabold text-slate-900">{{ format_compact_number($sales['pipeline_value'] ?? 0) }}</div>
                          </div>
                          <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                              <div class="text-xs uppercase tracking-wide text-slate-500">Velocidad comercial</div>
                              <div class="text-2xl font-extrabold text-slate-900">{{ $sales['velocity'] ?? 0 }}%</div>
                          </div>
                      </div>
                  </section>
              </div>

              <div class="col-span-12 lg:col-span-4 space-y-4">
                  <section class="rounded-2xl border border-[#26324d] p-5 shadow-sm shadow-slate-900/20" style="background:#111728;">
                      <div class="flex items-center justify-between gap-2 mb-3">
                          <h3 class="text-lg font-extrabold text-white">Horas Trabajadas</h3>
                          <span id="sales-hours-trend" class="inline-flex w-fit shrink-0 items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-bold text-[#f0fe97]">{{ $trendArrow($hoursSelectedTrend['direction'] ?? 'neutral') }} {{ number_format((float) ($hoursSelectedTrend['value'] ?? 0), 1) }}%</span>
                      </div>

                      <div id="sales-hours-period-tabs" class="flex flex-wrap items-center gap-2 mb-3 text-xs font-bold">
                          @foreach(['this_week' => 'Esta semana', 'last_week' => 'Semana pasada', 'this_month' => 'Este mes'] as $periodKey => $periodLabel)
                              @php $isActivePeriod = $hoursSelectedPeriod === $periodKey; @endphp
                              <button type="button" data-hours-group="sales" data-period="{{ $periodKey }}" class="px-3 py-1 rounded-full whitespace-nowrap transition-colors {{ $isActivePeriod ? 'bg-[#f0fe97] text-slate-950' : 'bg-white/10 text-slate-200 hover:bg-white/15' }}">{{ $periodLabel }}</button>
                          @endforeach
                      </div>

                      <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4">
                          <div class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color:#f0fe97;opacity:0.6;">Total del periodo</div>
                          <div id="sales-hours-total" class="text-5xl font-extrabold leading-none tracking-tight" style="color:#f0fe97;">{{ number_format($hoursSelectedTotal, 1) }}h</div>
                          <div id="sales-hours-breakdown" class="text-xs font-semibold mt-2" style="color:#f0fe97;opacity:0.7;">Agenda: {{ number_format($hoursSelectedAgenda, 1) }}h &nbsp;·&nbsp; Manual: {{ number_format($hoursSelectedManual, 1) }}h</div>
                      </div>

                      <div class="mt-3">
                          <label class="text-xs font-semibold uppercase tracking-wide text-slate-300">Añadir tiempo</label>
                          <button type="button" onclick="openSalesAddTimeModal()" class="mt-1 w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-lime-300 text-slate-900 text-sm font-bold hover:bg-lime-200 transition-colors">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                              <span>Añadir tiempo</span>
                          </button>
                      </div>
                  </section>

              </div>

              <div class="col-span-12 lg:col-span-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                  <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-extrabold text-slate-900">Recordatorios (7 dias)</h3>
                      <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-bold">{{ count($sales['upcoming_reminders'] ?? []) }}</span>
                  </div>
                  <div class="space-y-2 max-h-[340px] overflow-auto custom-scroll">
                      @forelse(($sales['upcoming_reminders'] ?? []) as $lead)
                          @php $reminderTs = !empty($lead['recordatorio']) ? strtotime((string) $lead['recordatorio']) : false; @endphp
                          <a href="{{ route('leads.index') }}" class="block rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50 transition-colors">
                              <div class="text-sm font-bold text-slate-900">{{ $lead['nombre'] ?? 'Lead' }}</div>
                              <div class="text-xs text-slate-500">{{ $lead['telefono'] ?? 'Sin telefono' }} · {{ $lead['etapa'] ?? 'Posible cliente' }}</div>
                              <div class="mt-1 text-xs font-semibold text-amber-700">{{ $reminderTs ? date('d/m/Y H:i', $reminderTs) : 'Sin fecha' }}</div>
                          </a>
                      @empty
                          <div class="text-sm text-slate-400">No hay recordatorios comerciales para esta semana.</div>
                      @endforelse
                  </div>
              </div>

              <div class="col-span-12 lg:col-span-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                  <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-extrabold text-slate-900">Ultimos Leads Actualizados</h3>
                      <a href="{{ route('leads.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">Abrir tablero &rarr;</a>
                  </div>
                  <div class="overflow-x-auto">
                      <table class="w-full text-left text-sm">
                          <thead>
                              <tr class="text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                                  <th class="px-3 py-2 font-semibold">Lead</th>
                                  <th class="px-3 py-2 font-semibold">Etapa</th>
                                  <th class="px-3 py-2 font-semibold text-right">Presupuesto</th>
                                  <th class="px-3 py-2 font-semibold">Encargados</th>
                              </tr>
                          </thead>
                          <tbody>
                              @forelse(($sales['recent_leads'] ?? []) as $lead)
                                  <tr class="border-b border-slate-100">
                                      <td class="px-3 py-3">
                                          <div class="font-bold text-slate-900">{{ $lead['nombre'] ?? 'Lead' }}</div>
                                          <div class="text-xs text-slate-500">{{ $lead['email'] ?? ($lead['telefono'] ?? '-') }}</div>
                                      </td>
                                      <td class="px-3 py-3"><span class="inline-flex px-2 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">{{ $lead['etapa'] ?? 'Posible cliente' }}</span></td>
                                      <td class="px-3 py-3 text-right font-bold text-slate-900">{{ format_currency((float) ($lead['presupuesto_estimado'] ?? $lead['valor'] ?? 0)) }}</td>
                                      <td class="px-3 py-3 text-xs text-slate-600">{{ collect($lead['encargados'] ?? [])->filter()->implode(', ') ?: 'Sin encargado' }}</td>
                                  </tr>
                              @empty
                                  <tr><td class="px-3 py-6 text-sm text-slate-400" colspan="4">No hay leads recientes para mostrar.</td></tr>
                              @endforelse
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>

      <!-- Goal Modal -->
      <div id="goal-modal" class="fixed inset-0 z-[160] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeGoalModal()"></div>
          <div class="fixed inset-0 z-10 overflow-y-auto">
              <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                  <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100">
                      <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                          <div class="sm:flex sm:items-start">
                              <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-lime-100 sm:mx-0 sm:h-10 sm:w-10">
                                  <svg class="h-6 w-6 text-lime-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                  </svg>
                              </div>
                              <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                  <h3 class="text-base font-bold leading-6 text-slate-900" id="modal-title">Ajustar Meta</h3>
                                  <div class="mt-2">
                                      <p class="text-sm text-slate-500 mb-4" id="goalModalHelp">Define el objetivo para el período seleccionado.</p>
                                      <div class="relative rounded-md shadow-sm">
                                          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                              <span class="text-slate-500 sm:text-sm font-bold">{{ currency_symbol() }}</span>
                                          </div>
                                          <input type="number" name="goal_amount" id="goal_amount" class="block w-full rounded-xl border-0 py-3 pl-8 pr-12 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-lime-600 sm:text-sm sm:leading-6 font-bold" placeholder="0.00" value="{{ $salesGoal }}">
                                          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                              <span class="text-slate-500 sm:text-sm">{{ $baseCurrency }}</span>
                                          </div>
                                      </div>
                                      <input type="hidden" id="goal_range" value="{{ in_array($summaryRange, ['month','semester','year'], true) ? $summaryRange : 'year' }}">
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                          <button type="button" onclick="saveGoal()" class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 sm:ml-3 sm:w-auto transition-colors">Guardar Meta</button>
                          <button type="button" onclick="closeGoalModal()" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition-colors">Cancelar</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <!-- Modal: Añadir tiempo manual (Dashboard Proyectos) -->
      <div id="dashboard-add-time-modal" class="fixed inset-0 z-[160] hidden" aria-modal="true" role="dialog">
          <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeDashboardAddTimeModal()"></div>
          <div class="fixed inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
                  <div class="px-5 py-4 border-b border-slate-100">
                      <div class="text-lg font-extrabold text-slate-900">Agregar tiempo manualmente</div>
                      <div class="text-xs text-slate-500 mt-1">Selecciona proyecto y tarea para vincular correctamente el tiempo.</div>
                  </div>
                  <div class="p-5 space-y-4">
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Proyecto</label>
                          <select id="dashboardAddTimeProjectSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700"></select>
                      </div>
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tarea</label>
                          <select id="dashboardAddTimeTaskSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700">
                              <option value="">Sin tarea vinculada</option>
                          </select>
                      </div>
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tiempo a agregar</label>
                          <div class="grid grid-cols-2 gap-2">
                              <div class="relative">
                                  <input id="dashboardAddTimeHours" type="number" min="0" max="23" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">h</span>
                              </div>
                              <div class="relative">
                                  <input id="dashboardAddTimeMinutes" type="number" min="0" max="59" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">m</span>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
                      <button type="button" onclick="closeDashboardAddTimeModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                      <button type="button" onclick="saveDashboardAddedTime()" class="px-4 py-2 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-sm font-bold">Guardar tiempo</button>
                  </div>
              </div>
          </div>
      </div>

      <div id="sales-add-time-modal" class="fixed inset-0 z-[160] hidden" aria-modal="true" role="dialog">
          <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeSalesAddTimeModal()"></div>
          <div class="fixed inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
                  <div class="px-5 py-4 border-b border-slate-100">
                      <div class="text-lg font-extrabold text-slate-900">Agregar tiempo a lead</div>
                      <div class="text-xs text-slate-500 mt-1">Selecciona un lead y agrega horas/minutos.</div>
                  </div>
                  <div class="p-5 space-y-4">
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Lead</label>
                          <select id="salesAddTimeLeadSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700"></select>
                      </div>
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tiempo a agregar</label>
                          <div class="grid grid-cols-2 gap-2">
                              <div class="relative">
                                  <input id="salesAddTimeHours" type="number" min="0" max="23" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">h</span>
                              </div>
                              <div class="relative">
                                  <input id="salesAddTimeMinutes" type="number" min="0" max="59" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">m</span>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
                      <button type="button" onclick="closeSalesAddTimeModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                      <button type="button" onclick="saveSalesAddedTime()" class="px-4 py-2 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-sm font-bold">Guardar tiempo</button>
                  </div>
              </div>
          </div>
      </div>

      <div id="dashboard-lead-timer-modal" class="fixed inset-0 z-[160] hidden" aria-modal="true" role="dialog">
          <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeDashboardLeadTimerModal()"></div>
          <div class="fixed inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
                  <div class="px-5 py-4 border-b border-slate-100">
                      <div class="text-lg font-extrabold text-slate-900">¿En qué trabajas hoy?</div>
                      <div class="text-xs text-slate-500 mt-1">Selecciona un lead para iniciar el temporizador.</div>
                  </div>
                  <div class="p-5 space-y-4">
                      <div>
                          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Lead</label>
                          <select id="dashboardLeadTimerSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700"></select>
                      </div>
                  </div>
                  <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
                      <button type="button" onclick="closeDashboardLeadTimerModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                      <button type="button" onclick="startDashboardLeadTimer()" class="px-4 py-2 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-sm font-bold">Iniciar temporizador</button>
                  </div>
              </div>
          </div>
      </div>

      <div id="dashboard-project-modal" class="fixed inset-0 z-[160] hidden" aria-modal="true" role="dialog">
          <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-sm" onclick="closeDashboardProjectModal()"></div>
          <div class="fixed inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-2xl rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                  <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                      <div class="min-w-0">
                          <div id="dashboardProjectModalDates" class="text-xs font-bold text-slate-400"></div>
                          <h3 id="dashboardProjectModalTitle" class="mt-1 text-2xl font-extrabold text-slate-950 leading-tight"></h3>
                          <div id="dashboardProjectModalStage" class="mt-1 text-sm font-semibold text-slate-500"></div>
                      </div>
                      <button type="button" onclick="closeDashboardProjectModal()" class="h-10 w-10 shrink-0 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Cerrar">✕</button>
                  </div>
                  <div class="p-5 space-y-4">
                      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                              <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Progreso</div>
                              <div id="dashboardProjectModalProgress" class="mt-1 text-2xl font-extrabold text-slate-900">0%</div>
                          </div>
                          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                              <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Tareas</div>
                              <div id="dashboardProjectModalTasksCount" class="mt-1 text-2xl font-extrabold text-slate-900">0</div>
                          </div>
                          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                              <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Tiempo</div>
                              <div id="dashboardProjectModalTime" class="mt-1 text-2xl font-extrabold text-slate-900">0h 0m</div>
                          </div>
                      </div>
                      <div class="flex flex-wrap items-center gap-2" id="dashboardProjectModalTags"></div>
                      <div>
                          <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Tareas</div>
                          <div id="dashboardProjectModalTaskList" class="max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
                      </div>
                  </div>
                  <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                      <a id="dashboardProjectModalOpenLink" href="{{ route('proyectos.index') }}" class="px-4 py-2 rounded-full bg-lime-300 hover:bg-lime-400 text-slate-950 text-sm font-extrabold">Abrir en Proyectos</a>
                  </div>
              </div>
          </div>
      </div>

      <div id="dashboard-task-modal" class="fixed inset-0 z-[170] hidden" aria-modal="true" role="dialog">
          <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-sm" onclick="closeDashboardTaskModal()"></div>
          <div class="fixed inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                  <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                      <div class="min-w-0">
                          <div id="dashboardTaskModalProject" class="text-xs font-bold text-slate-400 truncate"></div>
                          <h3 id="dashboardTaskModalTitle" class="mt-1 text-2xl font-extrabold text-slate-950 leading-tight"></h3>
                      </div>
                      <button type="button" onclick="closeDashboardTaskModal()" class="h-10 w-10 shrink-0 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Cerrar">✕</button>
                  </div>
                  <div class="p-5 space-y-4">
                      <div id="dashboardTaskModalTags" class="flex flex-wrap items-center gap-2"></div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                              <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Vencimiento</div>
                              <div id="dashboardTaskModalDue" class="mt-1 text-base font-extrabold text-slate-800">Sin fecha</div>
                          </div>
                          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                              <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Tiempo</div>
                              <div id="dashboardTaskModalTime" class="mt-1 text-base font-extrabold text-slate-800">0min</div>
                          </div>
                      </div>
                      <div>
                          <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Responsables</div>
                          <div id="dashboardTaskModalOwners" class="flex flex-wrap gap-2"></div>
                      </div>
                  </div>
                  <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
                      <a id="dashboardTaskModalOpenLink" href="{{ route('proyectos.index') }}" class="px-4 py-2 rounded-full bg-lime-300 hover:bg-lime-400 text-slate-950 text-sm font-extrabold">Abrir en Proyectos</a>
                  </div>
              </div>
          </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
      <script>
        const ALLOWED_DASHBOARD_TABS = @json($allowedDashboardTabs);
        const DEFAULT_DASHBOARD_TAB = @json($defaultDashboardTab);

        // Tab Logic
        function switchTab(tabId) {
            if (!ALLOWED_DASHBOARD_TABS.includes(tabId)) {
                tabId = DEFAULT_DASHBOARD_TAB;
            }
            document.querySelectorAll('.dashboard-view').forEach(el => el.classList.add('hidden'));
            const targetView = document.getElementById('view-' + tabId);
            if (targetView) targetView.classList.remove('hidden');
            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.className = "px-4 py-2 rounded-full bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all";
            });
            const activeBtn = document.getElementById('tab-' + tabId);
            if (activeBtn) {
                activeBtn.className = "px-4 py-2 rounded-full bg-slate-900 text-white transition-all shadow-md transform scale-105";
            }
            if (tabId === 'proyectos') ganttInit();
        }

        // Modal Logic
        function openGoalModal() {
            const range = '{{ $summaryRange }}';
            const title = document.getElementById('modal-title');
            const help = document.getElementById('goalModalHelp');
            const goalInput = document.getElementById('goal_amount');
            const goalRange = document.getElementById('goal_range');
            const goals = {
              month: {{ (float) $salesGoalMonth }},
              semester: {{ (float) $salesGoalSemester }},
              year: {{ (float) $salesGoalYear }},
            };
            const labels = { month: 'Mes', semester: 'Semestre', year: 'Año', all: 'Total' };
            const effectiveRange = (range === 'all') ? 'year' : range;
            if (title) title.textContent = `Ajustar Meta (${labels[range] || 'Mes'})`;
            if (help) {
              help.textContent = range === 'all'
                ? 'En vista Total se muestra la suma de metas (mes + semestre + año). Puedes editar mes, semestre o año.'
                : `Define el objetivo de facturación para ${labels[effectiveRange] || 'Mes'}.`;
            }
            if (goalInput && goals[effectiveRange] !== undefined) goalInput.value = goals[effectiveRange];
            if (goalRange) goalRange.value = effectiveRange;
            document.getElementById('goal-modal').classList.remove('hidden');
        }

        function closeGoalModal() {
            document.getElementById('goal-modal').classList.add('hidden');
        }

        function toggleRecentActivity() {
            const list = document.getElementById('recent-activity-list');
            const button = document.getElementById('recent-activity-toggle');
            if (!list || !button) return;

            const expanded = list.classList.contains('max-h-none');
            if (expanded) {
                list.classList.remove('max-h-none');
                list.classList.add('max-h-[300px]');
                button.textContent = 'Ver todo';
            } else {
                list.classList.remove('max-h-[300px]');
                list.classList.add('max-h-none');
                button.textContent = 'Ver menos';
            }
        }

        function saveGoal() {
            const amount = document.getElementById('goal_amount').value;
            const goalRange = document.getElementById('goal_range')?.value || 'month';
            fetch('{{ route("dashboard.updateGoal") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ goal: amount, goal_range: goalRange })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        (function initSummaryRangeMenu() {
          const btn = document.getElementById('summaryRangeMenuBtn');
          const menu = document.getElementById('summaryRangeMenu');
          if (!btn || !menu) return;
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
          });
          document.addEventListener('click', function (e) {
            if (e.target.closest('#summaryRangeMenu') || e.target.closest('#summaryRangeMenuBtn')) return;
            menu.classList.add('hidden');
          });
        })();

        function dashboardEscapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        @php
            $dashboardProjectHoursJson = $projectDashboard['hours'] ?? ['totals' => [], 'agenda' => [], 'manual' => [], 'trends' => []];
            $dashboardSalesHoursJson = $salesDashboard['hours'] ?? ['totals' => [], 'agenda' => [], 'manual' => [], 'trends' => []];
        @endphp
        const dashboardHoursData = {
            project: {{ \Illuminate\Support\Js::from($dashboardProjectHoursJson) }},
            sales: {{ \Illuminate\Support\Js::from($dashboardSalesHoursJson) }},
        };

        function dashboardTrendArrow(direction) {
            return direction === 'up' ? '↑' : (direction === 'down' ? '↓' : '→');
        }

        function dashboardFormatHours(value) {
            const number = Number(value || 0);
            return `${number.toFixed(1)}h`;
        }

        function setDashboardHoursPeriod(group, period) {
            const data = dashboardHoursData[group] || {};
            const totals = data.totals || {};
            const agenda = data.agenda || {};
            const manual = data.manual || {};
            const trends = data.trends || {};
            const trend = trends[period] || { value: 0, direction: 'neutral' };

            const totalEl = document.getElementById(`${group}-hours-total`);
            const breakdownEl = document.getElementById(`${group}-hours-breakdown`);
            const trendEl = document.getElementById(`${group}-hours-trend`);
            if (totalEl) totalEl.textContent = dashboardFormatHours(totals[period]);
            if (breakdownEl) breakdownEl.textContent = `Agenda: ${dashboardFormatHours(agenda[period])}  ·  Manual: ${dashboardFormatHours(manual[period])}`;
            if (trendEl) trendEl.textContent = `${dashboardTrendArrow(trend.direction || 'neutral')} ${Number(trend.value || 0).toFixed(1)}%`;

            document.querySelectorAll(`[data-hours-group="${group}"]`).forEach((button) => {
                const active = button.dataset.period === period;
                button.classList.toggle('bg-[#f0fe97]', active);
                button.classList.toggle('text-slate-950', active);
                button.classList.toggle('bg-white/10', !active);
                button.classList.toggle('text-slate-200', !active);
                button.classList.toggle('hover:bg-white/15', !active);
            });
        }

        document.querySelectorAll('[data-hours-group][data-period]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                setDashboardHoursPeriod(button.dataset.hoursGroup, button.dataset.period);
            });
        });

        function setDashboardAddTimeTaskOptions(projectId) {
            const taskSelect = document.getElementById('dashboardAddTimeTaskSelect');
            if (!taskSelect) return;
            const projects = Array.isArray(projectData?.projects) ? projectData.projects : [];
            const project = projects.find((p) => String(p.id) === String(projectId));
            const tasks = Array.isArray(project?.tasks) ? project.tasks : [];
            const base = '<option value="">Sin tarea vinculada</option>';
            const taskOptions = tasks.map((task) => `<option value="${dashboardEscapeHtml(task.id || '')}">${dashboardEscapeHtml(task.text || task.texto || 'Tarea sin nombre')}</option>`).join('');
            taskSelect.innerHTML = base + taskOptions;
        }

        function openDashboardAddTimeModal() {
            const modal = document.getElementById('dashboard-add-time-modal');
            const projectSelect = document.getElementById('dashboardAddTimeProjectSelect');
            if (!modal || !projectSelect) return;

            const projects = Array.isArray(projectData?.projects) ? projectData.projects : [];
            if (!projects.length) {
                if (window.showNotification) window.showNotification('No hay proyectos disponibles para agregar tiempo.', 'error');
                return;
            }

            projectSelect.innerHTML = projects
                .map((project) => `<option value="${dashboardEscapeHtml(project.id || '')}">${dashboardEscapeHtml(project.title || project.titulo || 'Proyecto')}</option>`)
                .join('');

            const selectedId = projectSelect.value || String(projects[0].id || '');
            projectSelect.value = selectedId;
            setDashboardAddTimeTaskOptions(selectedId);

            projectSelect.onchange = (event) => setDashboardAddTimeTaskOptions(event.target.value);

            document.getElementById('dashboardAddTimeHours').value = '0';
            document.getElementById('dashboardAddTimeMinutes').value = '0';

            modal.classList.remove('hidden');
        }

        function closeDashboardAddTimeModal() {
            const modal = document.getElementById('dashboard-add-time-modal');
            if (modal) modal.classList.add('hidden');
        }

        async function saveDashboardAddedTime() {
            const projectId = document.getElementById('dashboardAddTimeProjectSelect')?.value || '';
            const taskId = document.getElementById('dashboardAddTimeTaskSelect')?.value || null;
            const hours = parseInt(document.getElementById('dashboardAddTimeHours')?.value || '0', 10);
            const minutes = parseInt(document.getElementById('dashboardAddTimeMinutes')?.value || '0', 10);

            if (!projectId) {
                if (window.showNotification) window.showNotification('Selecciona un proyecto.', 'error');
                return;
            }

            if (hours === 0 && minutes === 0) {
                if (window.showNotification) window.showNotification('Ingresa un tiempo válido.', 'error');
                return;
            }

            try {
                const response = await fetch('/api/proyectos/tiempo/manual', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: projectId,
                        tarea_id: taskId,
                        horas: hours,
                        minutos: minutes
                    })
                });

                if (!response.ok) {
                    throw new Error('No se pudo guardar el tiempo');
                }

                closeDashboardAddTimeModal();
                window.location.reload();
            } catch (error) {
                console.error(error);
                if (window.showNotification) window.showNotification('Error al guardar el tiempo.', 'error');
            }
        }

        function openSalesAddTimeModal() {
            const modal = document.getElementById('sales-add-time-modal');
            const leadSelect = document.getElementById('salesAddTimeLeadSelect');
            if (!modal || !leadSelect) return;

            const leads = @json($sales['all_leads'] ?? []);
            if (!Array.isArray(leads) || !leads.length) {
                if (window.showNotification) window.showNotification('No hay leads disponibles.', 'error');
                return;
            }

            leadSelect.innerHTML = leads.map((lead) => {
                const stage = lead?.etapa ? ` · ${dashboardEscapeHtml(lead.etapa)}` : '';
                return `<option value="${dashboardEscapeHtml(lead.id || '')}">${dashboardEscapeHtml(lead.nombre || 'Lead')}${stage}</option>`;
            }).join('');

            document.getElementById('salesAddTimeHours').value = '0';
            document.getElementById('salesAddTimeMinutes').value = '0';
            modal.classList.remove('hidden');
        }

        function openDashboardLeadTimerModal() {
            const modal = document.getElementById('dashboard-lead-timer-modal');
            const leadSelect = document.getElementById('dashboardLeadTimerSelect');
            if (!modal || !leadSelect) return;

            const leads = @json($sales['all_leads'] ?? []);
            if (!Array.isArray(leads) || !leads.length) {
                if (window.showNotification) window.showNotification('No hay leads disponibles.', 'error');
                return;
            }

            leadSelect.innerHTML = leads.map((lead) => {
                const stage = lead?.etapa ? ` · ${dashboardEscapeHtml(lead.etapa)}` : '';
                return `<option value="${dashboardEscapeHtml(lead.id || '')}">${dashboardEscapeHtml(lead.nombre || 'Lead')}${stage}</option>`;
            }).join('');

            modal.classList.remove('hidden');
        }

        function closeDashboardLeadTimerModal() {
            const modal = document.getElementById('dashboard-lead-timer-modal');
            if (modal) modal.classList.add('hidden');
        }

        async function startDashboardLeadTimer() {
            const leadId = document.getElementById('dashboardLeadTimerSelect')?.value || '';
            if (!leadId) {
                if (window.showNotification) window.showNotification('Selecciona un lead.', 'error');
                return;
            }
            const leads = @json($sales['all_leads'] ?? []);
            const lead = Array.isArray(leads) ? leads.find((item) => String(item.id) === String(leadId)) : null;
            const leadNombre = lead?.nombre || 'Lead';
            try {
                const res = await fetch('{{ route('api.leads.timer.iniciar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: leadId })
                });
                const json = await res.json();
                if (!res.ok || !json.ok) throw new Error('lead_timer_start_failed');
                closeDashboardLeadTimerModal();
                if (window.showNotification) window.showNotification(`Temporizador iniciado: ${leadNombre}`, 'success');
                await syncDashboardLeadTimer();
            } catch (error) {
                console.error(error);
                if (window.showNotification) window.showNotification('No se pudo iniciar el temporizador del lead.', 'error');
            }
        }

        window.openLeadTimerModal = openDashboardLeadTimerModal;
        window.startDashboardLeadTimer = startDashboardLeadTimer;
        window.closeDashboardLeadTimerModal = closeDashboardLeadTimerModal;

        let dashboardLeadTimerState = null;
        let dashboardLeadTimerInterval = null;

        function fmtLeadTimer(seconds) {
            const s = Math.max(0, Math.floor(Number(seconds || 0)));
            const hh = String(Math.floor(s / 3600)).padStart(2, '0');
            const mm = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
            const ss = String(s % 60).padStart(2, '0');
            return `${hh}:${mm}:${ss}`;
        }

        function dashboardLeadElapsed() {
            if (!dashboardLeadTimerState) return 0;
            const base = Number(dashboardLeadTimerState.elapsed_seconds || 0);
            if (dashboardLeadTimerState.is_paused) return base;
            const syncedAt = Number(dashboardLeadTimerState.synced_at_ms || 0);
            const delta = Math.max(0, Math.floor((Date.now() - syncedAt) / 1000));
            return base + delta;
        }

        function renderDashboardLeadTimerHeader() {
            const host = document.getElementById('headerLeadTimerHost');
            if (!host) return;
            if (!dashboardLeadTimerState) {
                host.classList.add('hidden');
                host.innerHTML = '';
                if (window.updateHeaderTimerButtonVisibility) window.updateHeaderTimerButtonVisibility(true);
                return;
            }
            host.classList.remove('hidden');
            if (window.updateHeaderTimerButtonVisibility) window.updateHeaderTimerButtonVisibility(false);

            const display = fmtLeadTimer(dashboardLeadElapsed());
            const paused = !!dashboardLeadTimerState.is_paused;
            host.innerHTML = `
              <div id="leadHeaderTimerCard" class="group relative rounded-2xl border border-[#2b3658] bg-[#101729] px-2 py-1.5 shadow-[0_10px_22px_rgba(16,23,41,0.32)] min-w-0">
                <div class="flex items-center gap-2 min-w-0">
                  <button id="leadTimerPauseResumeBtn" type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#f0fe97] text-[#101729]" title="${paused ? 'Continuar temporizador' : 'Pausar temporizador'}">
                    ${paused
                      ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>'
                      : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'}
                  </button>
                  <div class="min-w-0">
                    <button type="button" class="truncate text-left text-xs lg:text-sm font-extrabold text-[#f0fe97]">Temporizador activo</button>
                    <div class="truncate text-[10px] lg:text-[11px] font-semibold text-[#f0fe97]/60">${dashboardEscapeHtml(dashboardLeadTimerState.lead_nombre || 'Lead')}</div>
                  </div>
                  <div class="ml-auto flex items-end gap-2 pl-2">
                    <div class="text-2xl lg:text-[30px] font-mono font-extrabold tracking-tight text-[#f0fe97] leading-none tabular-nums">${display}</div>
                    <div class="hidden sm:flex items-center gap-2 pb-[2px]">
                      <button id="leadTimerSaveBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-[#f0fe97]/75 hover:text-[#f0fe97]">Guardar</button>
                      <button id="leadTimerDeleteBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-rose-300/90 hover:text-rose-200">Eliminar</button>
                    </div>
                  </div>
                </div>
                <div class="sm:hidden mt-1.5 flex items-center justify-end gap-3">
                  <button id="leadTimerSaveBtnMobile" type="button" class="text-[10px] font-bold text-[#f0fe97]/75 hover:text-[#f0fe97]">Guardar</button>
                  <button id="leadTimerDeleteBtnMobile" type="button" class="text-[10px] font-bold text-rose-300/90 hover:text-rose-200">Eliminar</button>
                </div>
              </div>`;

            document.getElementById('leadTimerPauseResumeBtn')?.addEventListener('click', async () => {
                const route = paused ? '{{ route('api.leads.timer.reanudar') }}' : '{{ route('api.leads.timer.pausar') }}';
                try {
                    const res = await fetch(route, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: '{}' });
                    const json = await res.json();
                    if (!res.ok || !json.ok) throw new Error('lead_timer_toggle_failed');
                    await syncDashboardLeadTimer();
                } catch (_) {
                    if (window.showNotification) window.showNotification('No se pudo actualizar el temporizador del lead.', 'error');
                }
            });

            const saveFn = async () => {
                try {
                    const res = await fetch('{{ route('api.leads.timer.detener') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: '{}' });
                    const json = await res.json();
                    if (!res.ok || !json.ok) throw new Error('lead_timer_save_failed');
                    await syncDashboardLeadTimer();
                    if (window.showNotification) window.showNotification('Tiempo del lead guardado.', 'success');
                } catch (_) {
                    if (window.showNotification) window.showNotification('No se pudo guardar el temporizador del lead.', 'error');
                }
            };
            document.getElementById('leadTimerSaveBtn')?.addEventListener('click', saveFn);
            document.getElementById('leadTimerSaveBtnMobile')?.addEventListener('click', saveFn);

            const deleteFn = async () => {
                try {
                    const res = await fetch('{{ route('api.leads.timer.eliminar') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: '{}' });
                    const json = await res.json();
                    if (!res.ok || !json.ok) throw new Error('lead_timer_delete_failed');
                    await syncDashboardLeadTimer();
                    if (window.showNotification) window.showNotification('Temporizador eliminado.', 'success');
                } catch (_) {
                    if (window.showNotification) window.showNotification('No se pudo eliminar el temporizador del lead.', 'error');
                }
            };
            document.getElementById('leadTimerDeleteBtn')?.addEventListener('click', deleteFn);
            document.getElementById('leadTimerDeleteBtnMobile')?.addEventListener('click', deleteFn);
        }

        async function syncDashboardLeadTimer() {
            try {
                const res = await fetch('{{ route('api.leads.timer.activo') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                if (!res.ok || !json.ok || !json.active) {
                    dashboardLeadTimerState = null;
                    clearInterval(dashboardLeadTimerInterval);
                    dashboardLeadTimerInterval = null;
                    renderDashboardLeadTimerHeader();
                    return;
                }
                dashboardLeadTimerState = {
                    lead_id: json.timer?.lead_id || '',
                    lead_nombre: json.timer?.lead_nombre || 'Lead',
                    elapsed_seconds: Number(json.elapsed_seconds || 0),
                    is_paused: !!json.is_paused,
                    synced_at_ms: Date.now(),
                };
                renderDashboardLeadTimerHeader();
                if (!dashboardLeadTimerInterval) {
                    dashboardLeadTimerInterval = setInterval(renderDashboardLeadTimerHeader, 1000);
                }
            } catch (_) {
                dashboardLeadTimerState = null;
                renderDashboardLeadTimerHeader();
            }
        }

        syncDashboardLeadTimer();

        function closeSalesAddTimeModal() {
            const modal = document.getElementById('sales-add-time-modal');
            if (modal) modal.classList.add('hidden');
        }

        async function saveSalesAddedTime() {
            const leadId = document.getElementById('salesAddTimeLeadSelect')?.value || '';
            const hours = parseInt(document.getElementById('salesAddTimeHours')?.value || '0', 10);
            const minutes = parseInt(document.getElementById('salesAddTimeMinutes')?.value || '0', 10);

            if (!leadId) {
                if (window.showNotification) window.showNotification('Selecciona un lead.', 'error');
                return;
            }

            if (hours === 0 && minutes === 0) {
                if (window.showNotification) window.showNotification('Ingresa un tiempo válido.', 'error');
                return;
            }

            try {
                const response = await fetch('/api/leads/tiempo/manual', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: leadId,
                        horas: hours,
                        minutos: minutes
                    })
                });

                if (!response.ok) {
                    throw new Error('No se pudo guardar el tiempo en lead');
                }

                closeSalesAddTimeModal();
                window.location.reload();
            } catch (error) {
                console.error(error);
                if (window.showNotification) window.showNotification('Error al guardar el tiempo en lead.', 'error');
            }
        }

        // Advanced Chart Logic
        const ctx = document.getElementById('advancedChart').getContext('2d');
        let chartInstance = null;
        
        // Data from Controller
        const rawData = @json($advancedChartData);
        let activeChartData = rawData.slice(-6);
        const baseCurrency = @json($baseCurrency ?? 'USD');
        const currencySymbol = @json(currency_symbol());
        const monthMapEs = {
            jan: 'Ene', feb: 'Feb', mar: 'Mar', apr: 'Abr', may: 'May', jun: 'Jun',
            jul: 'Jul', aug: 'Ago', sep: 'Sep', oct: 'Oct', nov: 'Nov', dec: 'Dic'
        };

        function toSpanishMonthLabel(label) {
            const txt = String(label || '').trim();
            const m = txt.match(/^([A-Za-z]{3})\s+(\d{2,4})$/);
            if (!m) return txt;
            const es = monthMapEs[m[1].toLowerCase()] || m[1];
            return `${es} ${m[2]}`;
        }

        function initChart(dataSet = activeChartData) {
            if (chartInstance) chartInstance.destroy();
            activeChartData = dataSet;

            const incomeGradient = ctx.createLinearGradient(0, 0, 0, 400);
            incomeGradient.addColorStop(0, 'rgba(236, 254, 136, 0.35)');
            incomeGradient.addColorStop(1, 'rgba(236, 254, 136, 0)');

            const expenseGradient = ctx.createLinearGradient(0, 0, 0, 400);
            expenseGradient.addColorStop(0, 'rgba(248, 113, 113, 0.25)');
            expenseGradient.addColorStop(1, 'rgba(248, 113, 113, 0)');

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataSet.map(d => toSpanishMonthLabel(d.month)),
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: dataSet.map(d => d.income),
                            tension: 0.4,
                            borderColor: '#0f172a',
                            borderWidth: 3,
                            backgroundColor: incomeGradient,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHitRadius: 22,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#0f172a',
                            pointBorderWidth: 2,
                            fill: true
                        },
                        {
                            label: 'Egresos',
                            data: dataSet.map(d => d.expenses),
                            tension: 0.4,
                            borderColor: '#ef4444',
                            borderWidth: 3,
                            backgroundColor: expenseGradient,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointHitRadius: 22,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#ef4444',
                            pointBorderWidth: 2,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                color: '#64748b',
                                font: { size: 12, weight: '700' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#64748b',
                            bodyColor: '#0f172a',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 14, weight: 'bold', family: 'sans-serif' },
                            padding: 12,
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                title: (items) => items[0].label,
                                label: (context) => {
                                    const isIncomeSeries = context.dataset.label === 'Ingresos';
                                    const rawVal = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(Math.abs(context.raw));
                                    const val = isIncomeSeries ? `${currencySymbol}${rawVal}` : `-${currencySymbol}${rawVal}`;
                                    const point = activeChartData[context.dataIndex] || {};
                                    const count = isIncomeSeries ? (point.invoices_count || 0) : (point.expenses_count || 0);
                                    const unit = isIncomeSeries ? 'Facturas' : 'Gastos';
                                    return `${context.dataset.label}: ${val} · ${count} ${unit}`;
                                },
                                labelTextColor: (context) => context.dataset.label === 'Egresos' ? '#dc2626' : '#0f172a'
                            }
                        }
                    },
                    scales: {
                        y: {
                            display: false,
                            beginAtZero: true
                        },
                        x: { 
                            grid: { display: false }, 
                            ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8' } 
                        }
                    },
                    interaction: {
                        intersect: true,
                        mode: 'nearest',
                    },
                }
            });
        }

        // Init combined monthly chart
        initChart();
        const defaultTag = document.querySelector('#chart-period-tags button[data-period="6m"]');
        if (defaultTag) setChartPeriod('6m', defaultTag);

        function monthRange(startDate, endDate) {
            const out = [];
            const cursor = new Date(startDate.getFullYear(), startDate.getMonth(), 1);
            const end = new Date(endDate.getFullYear(), endDate.getMonth(), 1);
            while (cursor <= end) {
                out.push(`${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}`);
                cursor.setMonth(cursor.getMonth() + 1);
            }
            return out;
        }

        function monthLabel(ym) {
            const [y, m] = ym.split('-').map(Number);
            const names = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            return `${names[(m || 1) - 1]} ${String(y).slice(-2)}`;
        }

        function buildPeriodData(period) {
            const map = new Map(rawData.map(item => [item.ym, item]));
            const now = new Date();
            let months = [];

            if (period === 'this_year') {
                months = monthRange(new Date(now.getFullYear(), 0, 1), now);
            } else if (period === 'last_year') {
                months = monthRange(new Date(now.getFullYear() - 1, 0, 1), new Date(now.getFullYear() - 1, 11, 1));
            } else if (period === 'all') {
                return rawData;
            } else {
                months = monthRange(new Date(now.getFullYear(), now.getMonth() - 5, 1), now);
            }

            return months.map((ym) => {
                const row = map.get(ym);
                if (row) return row;
                return {
                    ym,
                    month: monthLabel(ym),
                    year: Number(ym.slice(0, 4)),
                    income: 0,
                    expenses: 0,
                    invoices_count: 0,
                    expenses_count: 0,
                };
            });
        }

        function setChartPeriod(period, triggerEl) {
            const filtered = buildPeriodData(period);

            activeChartData = filtered;
            chartInstance.data.labels = filtered.map(d => d.month);
            chartInstance.data.datasets[0].data = filtered.map(d => d.income);
            chartInstance.data.datasets[1].data = filtered.map(d => d.expenses);
            chartInstance.update();

            const tags = document.querySelectorAll('#chart-period-tags button');
            tags.forEach(btn => {
                btn.className = 'px-3 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors whitespace-nowrap';
            });
            if (triggerEl) {
                triggerEl.className = 'px-3 py-1 rounded-full bg-slate-900 text-white whitespace-nowrap';
            }
        }

        // Abrir tab comercial si el usuario es vendedor o si se navega con filtro de horas
        const hoursPeriodParam = new URLSearchParams(window.location.search).get('hours_period');
        if (hoursPeriodParam && ALLOWED_DASHBOARD_TABS.includes('ventas')) {
            switchTab('ventas');
        } else {
            switchTab(DEFAULT_DASHBOARD_TAB);
        }
        @if(($salesDashboard['is_seller_role'] ?? false) === true)
        if (ALLOWED_DASHBOARD_TABS.includes('ventas')) {
            switchTab('ventas');
        }
        @endif

        // ===== Gantt Roadmap =====
        const projectData = @json($projectDashboard ?? ['stages' => [], 'projects' => []]);
        const MONTH_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        const DAY_W = 40, LEFT_W = 224, ROW_H = 48;
        let ganttYear = new Date().getFullYear();
        let ganttMonth = new Date().getMonth();
        let ganttProjId = null;
        let ganttInited = false;

        function dashboardFindProject(projectId) {
            const projects = Array.isArray(projectData?.projects) ? projectData.projects : [];
            return projects.find((project) => String(project.id || '') === String(projectId || '')) || null;
        }

        function dashboardFindTask(project, taskId) {
            const tasks = Array.isArray(project?.tasks) ? project.tasks : [];
            return tasks.find((task) => String(task.id || '') === String(taskId || '')) || null;
        }

        function dashboardFormatProjectDate(raw) {
            if (!raw) return '—';
            const date = new Date(/^\d{4}-\d{2}-\d{2}$/.test(String(raw)) ? `${raw}T12:00:00` : raw);
            if (Number.isNaN(date.getTime())) return '—';
            return date.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function dashboardMinutesLabel(seconds) {
            const total = Math.max(0, Math.floor(Number(seconds || 0)));
            const hours = Math.floor(total / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}min`;
        }

        function dashboardPriorityMeta(priority, isOverdue = false, isDone = false) {
            if (isDone) return { label: 'Completada', bg: '#dcfce7', border: '#bbf7d0', text: '#16a34a', icon: '✓' };
            if (isOverdue) return { label: 'Vencido', bg: '#f1f5f9', border: '#cbd5e1', text: '#334155', icon: '×' };
            const normalized = String(priority || '').trim().toLowerCase();
            if (normalized === 'urgente' || normalized === 'alta') return { label: 'Urgente', bg: '#fff1f2', border: '#fecdd3', text: '#e11d48', icon: '!' };
            if (normalized === 'con calma' || normalized === 'baja') return { label: 'Con calma', bg: '#ecfdf5', border: '#86efac', text: '#047857', icon: '☻' };
            return { label: 'Atención', bg: '#fffbeb', border: '#facc15', text: '#c2410c', icon: '!' };
        }

        function dashboardTagHtml(meta) {
            return `<span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-extrabold" style="background:${meta.bg};border-color:${meta.border};color:${meta.text};"><span class="inline-flex h-4 w-4 items-center justify-center rounded-full border text-[10px]" style="border-color:${meta.text};">${meta.icon}</span>${dashboardEscapeHtml(meta.label)}</span>`;
        }

        function closeDashboardProjectModal() {
            document.getElementById('dashboard-project-modal')?.classList.add('hidden');
        }

        function closeDashboardTaskModal() {
            document.getElementById('dashboard-task-modal')?.classList.add('hidden');
        }

        function openDashboardProjectModal(projectId) {
            const project = dashboardFindProject(projectId);
            if (!project) return;
            const tasks = Array.isArray(project.tasks) ? project.tasks : [];
            const taskDone = tasks.filter((task) => !!task.done).length;
            const dueDate = project.due_date ? new Date(/^\d{4}-\d{2}-\d{2}$/.test(String(project.due_date)) ? `${project.due_date}T12:00:00` : project.due_date) : null;
            const today = new Date();
            const isOverdue = dueDate && new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate()) < new Date(today.getFullYear(), today.getMonth(), today.getDate()) && Number(project.progress || 0) < 100;
            const meta = dashboardPriorityMeta(project.priority || 'Atención', isOverdue, Number(project.progress || 0) >= 100);
            const seconds = Number(project.time_seconds || 0);

            document.getElementById('dashboardProjectModalDates').textContent = `${dashboardFormatProjectDate(project.fecha_inicio)} – ${dashboardFormatProjectDate(project.due_date)}`;
            document.getElementById('dashboardProjectModalTitle').textContent = project.title || 'Proyecto';
            document.getElementById('dashboardProjectModalStage').textContent = `${project.client_name || 'Sin cliente'} · ${project.stage || 'Sin etapa'}`;
            document.getElementById('dashboardProjectModalProgress').textContent = `${Number(project.progress || 0)}%`;
            document.getElementById('dashboardProjectModalTasksCount').textContent = `${tasks.length} (${taskDone} listas)`;
            document.getElementById('dashboardProjectModalTime').textContent = dashboardMinutesLabel(seconds);
            document.getElementById('dashboardProjectModalTags').innerHTML = dashboardTagHtml(meta);
            document.getElementById('dashboardProjectModalOpenLink').href = `/proyectos?open_project=${encodeURIComponent(project.id || '')}`;

            const taskList = document.getElementById('dashboardProjectModalTaskList');
            if (taskList) {
                taskList.innerHTML = tasks.length
                    ? tasks.map((task) => {
                        const taskDue = task.due_date ? new Date(/^\d{4}-\d{2}-\d{2}$/.test(String(task.due_date)) ? `${task.due_date}T12:00:00` : task.due_date) : null;
                        const taskOverdue = taskDue && new Date(taskDue.getFullYear(), taskDue.getMonth(), taskDue.getDate()) < new Date(today.getFullYear(), today.getMonth(), today.getDate()) && !task.done;
                        const taskMeta = dashboardPriorityMeta(task.priority || project.priority || 'Atención', taskOverdue, !!task.done);
                        return `<button type="button" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left hover:bg-slate-50" onclick="openDashboardTaskModal('${dashboardEscapeHtml(project.id || '')}', '${dashboardEscapeHtml(task.id || '')}')">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-extrabold text-slate-900">${dashboardEscapeHtml(task.text || 'Tarea')}</div>
                                    <div class="mt-0.5 text-[11px] font-semibold text-slate-400">${taskDue ? `Vence: ${dashboardFormatProjectDate(task.due_date)}` : 'Sin fecha'}</div>
                                </div>
                                ${dashboardTagHtml(taskMeta)}
                            </div>
                        </button>`;
                    }).join('')
                    : '<div class="px-3 py-8 text-center text-sm font-semibold text-slate-400">Este proyecto no tiene tareas.</div>';
            }
            document.getElementById('dashboard-project-modal')?.classList.remove('hidden');
        }

        function openDashboardTaskModal(projectId, taskId) {
            const project = dashboardFindProject(projectId);
            const task = dashboardFindTask(project, taskId);
            if (!project || !task) return;
            const dueDate = task.due_date ? new Date(/^\d{4}-\d{2}-\d{2}$/.test(String(task.due_date)) ? `${task.due_date}T12:00:00` : task.due_date) : null;
            const today = new Date();
            const isOverdue = dueDate && new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate()) < new Date(today.getFullYear(), today.getMonth(), today.getDate()) && !task.done;
            const meta = dashboardPriorityMeta(task.priority || project.priority || 'Atención', isOverdue, !!task.done);
            const owners = Array.isArray(task.owners) ? task.owners : [];

            document.getElementById('dashboardTaskModalProject').textContent = project.title || 'Proyecto';
            document.getElementById('dashboardTaskModalTitle').textContent = task.text || 'Tarea';
            document.getElementById('dashboardTaskModalTags').innerHTML = dashboardTagHtml(meta);
            document.getElementById('dashboardTaskModalDue').textContent = task.due_date ? dashboardFormatProjectDate(task.due_date) : 'Sin fecha';
            document.getElementById('dashboardTaskModalTime').textContent = dashboardMinutesLabel(task.total_seconds || 0);
            document.getElementById('dashboardTaskModalOpenLink').href = `/proyectos?open_project=${encodeURIComponent(project.id || '')}&open_task=${encodeURIComponent(task.id || '')}`;
            document.getElementById('dashboardTaskModalOwners').innerHTML = owners.length
                ? owners.map((owner) => `<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">${dashboardEscapeHtml(owner)}</span>`).join('')
                : '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Sin responsables</span>';

            closeDashboardProjectModal();
            document.getElementById('dashboard-task-modal')?.classList.remove('hidden');
        }

        function ganttRender() {
            const wrap = document.getElementById('ganttWrap');
            const metaEl = document.getElementById('ganttMeta');
            const labelEl = document.getElementById('ganttLabel');
            if (!wrap) return;
            if (labelEl) labelEl.textContent = MONTH_ES[ganttMonth] + ' ' + ganttYear;

            const projs = (projectData && projectData.projects) ? projectData.projects : [];
            if (!ganttProjId && projs.length > 0) ganttProjId = projs[0].id;
            const proj = projs.find(p => p.id === ganttProjId) || projs[0];

            if (!proj) {
                wrap.innerHTML = '<div style="padding:48px;text-align:center;color:#94a3b8;font-size:14px;">No hay proyectos disponibles.</div>';
                return;
            }

            if (metaEl) {
                const stageColors = {'RECURSOS':'#e0f2fe;color:#0284c7','NUEVA':'#f0fdf4;color:#16a34a','FASE 1':'#fef9c3;color:#ca8a04','FASE 2':'#ffe4e6;color:#e11d48','FINALIZADO':'#d1fae5;color:#059669','EN CURSO':'#dbeafe;color:#2563eb','PROSPECTO':'#f3e8ff;color:#7c3aed','ENTREGADO':'#d1fae5;color:#059669'};
                const sc = stageColors[proj.stage] || 'background:#f1f5f9;color:#475569';
                metaEl.innerHTML = `<span style="font-weight:700;color:#0f172a;font-size:13px;">${proj.client_name}</span> <span style="background:${sc};padding:2px 10px;border-radius:999px;font-size:10px;font-weight:700;">${proj.stage}</span> <span style="color:#64748b;">${proj.progress}% completado</span>`;
            }

            const daysInMonth = new Date(ganttYear, ganttMonth + 1, 0).getDate();
            const today = new Date();
            const isNow = today.getFullYear() === ganttYear && today.getMonth() === ganttMonth;
            const todayD = today.getDate();
            const monthStart = new Date(ganttYear, ganttMonth, 1);
            const monthEnd = new Date(ganttYear, ganttMonth, daysInMonth);

            const projStart = proj.fecha_inicio ? new Date(proj.fecha_inicio.replace(/-/g,'/')) : monthStart;
            const projEnd = proj.due_date ? new Date(proj.due_date.replace(/-/g,'/')) : monthEnd;

            const tasks = (proj.tasks || []);
            const DAYS = ['D','L','M','X','J','V','S'];

            let h = `<table style="border-collapse:collapse;table-layout:fixed;min-width:${LEFT_W + DAY_W * daysInMonth}px;width:100%;"><thead><tr>`;
            h += `<th style="width:${LEFT_W}px;min-width:${LEFT_W}px;position:sticky;left:0;top:0;z-index:20;background:#f8fafc;padding:10px 14px;text-align:left;border-bottom:2px solid #e2e8f0;border-right:1px solid #e2e8f0;"><span style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em;">Tarea / Asignado</span></th>`;
            for (let d = 1; d <= daysInMonth; d++) {
                const dt = new Date(ganttYear, ganttMonth, d);
                const dow = dt.getDay();
                const isWe = dow === 0 || dow === 6;
                const isTod = isNow && d === todayD;
                h += `<th style="width:${DAY_W}px;min-width:${DAY_W}px;position:sticky;top:0;z-index:15;padding:4px 0;text-align:center;background:${isTod?'#f3fea4':(isWe?'#f8fafc':'#f8fafc')};border-bottom:2px solid #e2e8f0;border-left:1px solid #e2e8f0;"><div style="font-size:9px;font-weight:700;color:${isTod?'#0f172a':'#94a3b8'};line-height:1.2;">${DAYS[dow]}</div><div style="font-size:12px;font-weight:${isTod?800:600};color:${isTod?'#0f172a':(isWe?'#94a3b8':'#475569')};">${d}</div></th>`;
            }
            h += '</tr></thead><tbody>';

            const renderRow = (task, idx) => {
                const isDone = !!task.done;
                const rowBg = idx % 2 === 0 ? '#fff' : '#fafafa';
                let tStart = task.start_date ? new Date(task.start_date.replace(/-/g,'/')) : projStart;
                let tEnd = task.end_date ? new Date(task.end_date.replace(/-/g,'/')) : (task.due_date ? new Date(task.due_date.replace(/-/g,'/')) : projEnd);
                if (tEnd < tStart) tEnd = new Date(tStart);
                const bStart = tStart > monthEnd ? null : (tStart < monthStart ? monthStart : tStart);
                const bEnd = tEnd < monthStart ? null : (tEnd > monthEnd ? monthEnd : tEnd);
                const hasBar = bStart && bEnd;
                const bSD = hasBar ? bStart.getDate() : null;
                const bED = hasBar ? bEnd.getDate() : null;
                const owners = Array.isArray(task.owners) ? task.owners : [];
                const avatars = owners.slice(0,3).map(o => {
                    const ini = (o||'').split(' ').map(w=>w[0]||'').join('').slice(0,2).toUpperCase();
                    return `<span title="${o}" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#f1f5f9;color:#334155;font-size:8px;font-weight:800;border:1.5px solid #e2e8f0;flex-shrink:0;">${ini}</span>`;
                }).join('');
                const mins = task.total_seconds ? Math.round(task.total_seconds / 60) : 0;
                const isOverdue = !isDone && task.due_date && new Date(task.due_date.replace(/-/g,'/')) < today;
                const barColor = isDone ? '#10b981' : (isOverdue ? '#f43f5e' : '#e2e8f0');
                const pct = isDone ? 100 : (task.total_seconds ? Math.min(100, Math.round((task.total_seconds / 3600) * 15)) : 0);

                let row = `<tr><td style="width:${LEFT_W}px;min-width:${LEFT_W}px;position:sticky;left:0;z-index:5;background:${rowBg};padding:8px 14px;border-bottom:1px solid #f1f5f9;border-right:1px solid #e2e8f0;"><div style="display:flex;align-items:center;gap:6px;"><div style="flex:1;min-width:0;"><div style="font-size:11px;font-weight:700;color:${isDone?'#10b981':(isOverdue?'#f43f5e':'#0f172a')};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:155px;" title="${(task.text||'').replace(/"/g,'&quot;')}">${isDone?'✓ ':''}${task.text||'Tarea'}</div><div style="display:flex;align-items:center;gap:3px;margin-top:3px;">${avatars}${mins>0?`<span style="font-size:9px;color:#94a3b8;margin-left:3px;">${mins}min</span>`:''}</div></div></div></td>`;

                for (let d = 1; d <= daysInMonth; d++) {
                    const dt = new Date(ganttYear, ganttMonth, d);
                    const dow = dt.getDay();
                    const isWe = dow === 0 || dow === 6;
                    const isTod = isNow && d === todayD;
                    const inBar = hasBar && d >= bSD && d <= bED;
                    const isS = inBar && d === bSD, isE = inBar && d === bED, isSE = isS && isE;
                    const br = isSE ? '8px' : isS ? '8px 0 0 8px' : isE ? '0 8px 8px 0' : '0';
                    let barHtml = '';
                    if (inBar) {
                        barHtml = `<div style="position:absolute;top:13px;bottom:13px;left:${isS?3:0}px;right:${isE?3:0}px;background:${barColor};border-radius:${br};overflow:hidden;"><div style="position:absolute;inset:0;width:${pct}%;background:${isDone?'#34d399':'#ecfe88'};opacity:.35;"></div></div>`;
                    }
                    row += `<td style="width:${DAY_W}px;position:relative;height:${ROW_H}px;border-bottom:1px solid #f1f5f9;border-left:1px solid ${isTod?'rgba(243,254,164,.8)':'#f8fafc'};background:${isTod?'rgba(243,254,164,.25)':(isWe?'#fafafa':'#fff')};">${barHtml}${isTod?'<div style="position:absolute;inset:0;border-left:2px solid #f3fea4;pointer-events:none;"></div>':''}</td>`;
                }
                row += '</tr>';
                return row;
            };

            if (tasks.length === 0) {
                const pS = projStart > monthEnd ? null : (projStart < monthStart ? monthStart : projStart);
                const pE = projEnd < monthStart ? null : (projEnd > monthEnd ? monthEnd : projEnd);
                const pSD = pS ? pS.getDate() : null, pED = pE ? pE.getDate() : null;
                h += `<tr><td style="width:${LEFT_W}px;min-width:${LEFT_W}px;position:sticky;left:0;z-index:5;background:#fff;padding:10px 14px;border-bottom:1px solid #f1f5f9;border-right:1px solid #e2e8f0;"><div style="font-size:12px;font-weight:700;color:#0f172a;">${proj.title}</div><div style="font-size:10px;color:#94a3b8;margin-top:2px;">Sin tareas — ${proj.progress}% completado</div></td>`;
                for (let d = 1; d <= daysInMonth; d++) {
                    const dt = new Date(ganttYear, ganttMonth, d);
                    const dow = dt.getDay();
                    const isWe = dow === 0 || dow === 6;
                    const isTod = isNow && d === todayD;
                    const inBar = pSD && pED && d >= pSD && d <= pED;
                    const isS = inBar && d === pSD, isE = inBar && d === pED, isSE = isS && isE;
                    const br = isSE?'8px':isS?'8px 0 0 8px':isE?'0 8px 8px 0':'0';
                    h += `<td style="width:${DAY_W}px;position:relative;height:${ROW_H}px;border-bottom:1px solid #f1f5f9;border-left:1px solid ${isTod?'rgba(243,254,164,.8)':'#f8fafc'};background:${isTod?'rgba(243,254,164,.25)':(isWe?'#fafafa':'#fff')};">${inBar?`<div style="position:absolute;top:13px;bottom:13px;left:${isS?3:0}px;right:${isE?3:0}px;background:#e2e8f0;border-radius:${br};"></div>`:''}</td>`;
                }
                h += '</tr>';
            } else {
                tasks.forEach((t, i) => { h += renderRow(t, i); });
            }

            h += '</tbody></table>';
            wrap.innerHTML = h;
            if (isNow) setTimeout(() => { wrap.scrollLeft = Math.max(0, (todayD - 4) * DAY_W); }, 30);
        }

        function ganttInit() {
            if (ganttInited) { ganttRender(); return; }
            ganttInited = true;
            const sel = document.getElementById('projectSelector');
            const prev = document.getElementById('ganttPrev');
            const next = document.getElementById('ganttNext');
            if (!sel) return;
            const projs = projectData.projects || [];
            ganttProjId = projs.length > 0 ? projs[0].id : null;
            if (ganttProjId) sel.value = ganttProjId;
            sel.addEventListener('change', e => { ganttProjId = e.target.value; ganttRender(); });
            if (prev) prev.addEventListener('click', () => { ganttMonth === 0 ? (ganttMonth=11, ganttYear--) : ganttMonth--; ganttRender(); });
            if (next) next.addEventListener('click', () => { ganttMonth === 11 ? (ganttMonth=0, ganttYear++) : ganttMonth++; ganttRender(); });
            ganttRender();
        }
      </script>
@endsection
