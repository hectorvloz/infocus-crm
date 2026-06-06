<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;

class DashboardController extends Controller
{
    public function index()
    {
        // Data Stores
        $clientsStore = new FileStore('clientes.json');
        $invoicesStore = new FileStore('facturas.json');
        $expensesStore = new FileStore('gastos.json');
        $tasksStore = new FileStore('tareas.json');
        $projectsStore = new FileStore('proyectos.json');
        $leadsStore = new FileStore('leads.json');
        $productsStore = new FileStore('items.json');
        $settingsStore = new FileStore('settings.json');

        // 1. Stats
        $clients = $clientsStore->all() ?: [];
        $totalClients = count($clients);

        $invoices = $invoicesStore->all() ?: [];
        $currentMonth = date('Y-m');
        $appSettings = $settingsStore->find('settings') ?: [];
        $baseCurrency = strtoupper($appSettings['base_currency'] ?? 'USD');
        
        $summaryRange = (string) request()->query('summary_range', 'month');
        if (!in_array($summaryRange, ['month', 'semester', 'year', 'all'], true)) {
            $summaryRange = 'month';
        }

        $monthlyIncome = $this->calcIncomeByRange($invoices, $summaryRange, $baseCurrency);
        $lastMonthIncome = $this->calcIncomeByRange($invoices, $this->previousRange($summaryRange), $baseCurrency);
        $growth = $lastMonthIncome > 0 ? (($monthlyIncome - $lastMonthIncome) / $lastMonthIncome) * 100 : ($monthlyIncome > 0 ? 100 : 0);

        $totalInvoices = collect($invoices)->filter(fn($i) => ($i['estado'] ?? '') !== 'En borrador')->count();

        $expenses = $expensesStore->all() ?: [];
        $monthlyExpenses = $this->calcExpensesByRange($expenses, $summaryRange);
        $freeMoney = round($monthlyIncome - $monthlyExpenses, 2);

        $tasks = $tasksStore->all() ?: [];

        // Chart histórico desde el primer registro disponible
        $advancedChartData = [];
        $invoiceMonths = [];
        foreach ($invoices as $inv) {
            $f = (string) ($inv['fecha'] ?? '');
            if ($f !== '' && strtotime($f) !== false) {
                $invoiceMonths[] = date('Y-m', strtotime($f));
            }
        }
        $expenseMonths = [];
        foreach ($expenses as $expItem) {
            $f = (string) ($expItem['fecha'] ?? '');
            if ($f !== '' && strtotime($f) !== false) {
                $expenseMonths[] = date('Y-m', strtotime($f));
            }
        }

        if (!empty($invoiceMonths)) {
            $startMonth = min($invoiceMonths);
        } elseif (!empty($expenseMonths)) {
            $startMonth = min($expenseMonths);
        } else {
            $startMonth = date('Y-m', strtotime('-6 month'));
        }
        $cursor = strtotime($startMonth . '-01');
        $endMonth = strtotime($currentMonth . '-01');

        while ($cursor <= $endMonth) {
            $m = date('Y-m', $cursor);
            
            $inc = $this->calcIncome($invoices, $m, $baseCurrency);
            $exp = collect($expenses)->filter(fn($e) => str_starts_with($e['fecha'], $m))->sum('monto');
            
            $invCount = collect($invoices)->filter(fn($x) => str_starts_with($x['fecha'], $m) && ($x['estado']??'') !== 'En borrador')->count();
            $expCount = collect($expenses)->filter(fn($x) => str_starts_with($x['fecha'], $m))->count();

            $advancedChartData[] = [
                'month' => date('M y', strtotime($m)),
                'ym' => $m,
                'year' => (int) date('Y', strtotime($m)),
                'income' => $inc,
                'expenses' => $exp,
                'invoices_count' => $invCount,
                'expenses_count' => $expCount
            ];

            $cursor = strtotime('+1 month', $cursor);
        }

        // Mini chart data (solo 6 meses para mantener proporción visual)
        $chartData = collect($advancedChartData)->slice(-6)->values()->all();

        // Recent Activity Feed
        $activities = [];
        // Invoices
        foreach($invoices as $inv) {
            $activities[] = [
                'type' => 'invoice',
                'title' => 'Creó una nueva factura',
                'description' => 'Factura #'.($inv['numero']??'???'),
                'date' => $inv['fecha'] ?? date('Y-m-d'),
                'user' => 'Sistema', // Ideally from user_id if stored
                'initials' => 'SYS',
                'timestamp' => strtotime($inv['fecha'] ?? date('Y-m-d')),
                'link' => route('facturas.edit', $inv['id'])
            ];
        }
        // Tasks
        foreach($tasks as $t) {
            $activities[] = [
                'type' => 'task',
                'title' => 'Nueva tarea asignada',
                'description' => $t['title'] ?? 'Tarea sin título',
                'date' => $t['created_at'] ?? date('Y-m-d'),
                'user' => 'Sistema',
                'initials' => 'SYS',
                'timestamp' => strtotime($t['created_at'] ?? date('Y-m-d')),
                'link' => route('proyectos.index') // Tasks don't have direct link yet
            ];
        }
        
        // Sort by date desc
        usort($activities, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        $activities = array_slice($activities, 0, 5);
        $projects = collect($projectsStore->all() ?: [])->keyBy('id');
        $allClients = collect($clients)->keyBy('id'); // Use clients loaded earlier

        $tasksList = collect($tasks)->map(function($t) use ($projects, $allClients) {
            $proj = $projects[$t['proyecto_id'] ?? ''] ?? null;
            $clientName = 'Sin Cliente';
            if ($proj) {
                $c = $allClients[$proj['cliente_id']] ?? null;
                $clientName = $c['empresa'] ?? $c['nombre'] ?? $c['contacto_nombre'] ?? 'Sin Cliente';
            }
            $t['client_name'] = $clientName;
            $t['project_name'] = $proj['nombre'] ?? 'General';
            return $t;
        })->filter(fn($t) => ($t['status']??'') !== 'completed')->groupBy('client_name');

        // 3. Products
        $products = $productsStore->all() ?: [];

        // 4. Extra Widgets Data
        
        // Top Clients (Revenue YTD)
        $currentYear = date('Y');
        $topClients = collect($invoices)
            ->filter(fn($i) => str_starts_with($i['fecha'], $currentYear) && ($i['estado'] ?? '') !== 'En borrador')
            ->groupBy('cliente_id')
            ->map(function ($clientInvoices) use ($allClients) {
                $total = $clientInvoices->sum(function($i) {
                    return (float) ($i['total_base'] ?? $i['total'] ?? 0);
                });
                $clientId = $clientInvoices[0]['cliente_id'] ?? '';
                $client = $allClients[$clientId] ?? null;
                $invoiceClientName = $clientInvoices[0]['cliente'] ?? null;
                return [
                    'id' => $clientId,
                    'name' => $client['empresa'] ?? $client['nombre'] ?? $client['contacto_nombre'] ?? $invoiceClientName ?? 'Cliente Desconocido',
                    'total' => $total,
                    'count' => $clientInvoices->count()
                ];
            })
            ->sortByDesc('total')
            ->take(5);

        // Sales Goal
        $settings = $settingsStore->find('dashboard') ?: [];
        $legacyGoal = (float) ($settings['sales_goal'] ?? 50000);
        $goalsByRange = (array) ($settings['sales_goal_by_range'] ?? []);
        $salesGoalMonth = (float) ($goalsByRange['month'] ?? $legacyGoal);
        $salesGoalSemester = (float) ($goalsByRange['semester'] ?? $legacyGoal);
        $salesGoalYear = (float) ($goalsByRange['year'] ?? $legacyGoal);
        $salesGoalTotal = round(max(0, $salesGoalMonth) + max(0, $salesGoalSemester) + max(0, $salesGoalYear), 2);
        $salesGoal = match ($summaryRange) {
            'semester' => $salesGoalSemester,
            'year' => $salesGoalYear,
            'all' => $salesGoalTotal,
            default => $salesGoalMonth,
        };
        $salesProgress = $salesGoal > 0 ? min(100, ($monthlyIncome / $salesGoal) * 100) : 0;
        $summaryRangeLabel = match ($summaryRange) {
            'semester' => 'Semestre',
            'year' => 'Año',
            'all' => 'Todo',
            default => 'Mes',
        };
        $summaryCompareLabel = match ($summaryRange) {
            'semester' => 'vs semestre anterior',
            'year' => 'vs año anterior',
            'all' => 'vs período anterior',
            default => 'vs mes anterior',
        };

        // Upcoming Invoices (Next 7 days)
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $upcomingInvoices = collect($invoices)
            ->filter(function($i) use ($today, $nextWeek) {
                $due = $i['vencimiento'] ?? null;
                return $due && $due >= $today && $due <= $nextWeek && ($i['estado']??'') !== 'Pagada';
            })
            ->sortBy('vencimiento')
            ->take(5);

        $currentUser = auth()->user();
        $currentRole = strtolower((string) ($currentUser->role ?? session('user.role') ?? ''));
        $currentUserName = strtolower(trim((string) ($currentUser->name ?? '')));
        $currentUserEmail = strtolower(trim((string) ($currentUser->email ?? '')));

        // Projects Dashboard (Roadmap)
        $projectStages = collect($appSettings['project_stages'] ?? ['NUEVA', 'EN CURSO', 'FINALIZADO'])
            ->map(fn($s) => strtoupper(trim((string) $s)))
            ->filter(fn($s) => $s !== '')
            ->values()
            ->all();
        if (empty($projectStages)) {
            $projectStages = ['NUEVA', 'EN CURSO', 'FINALIZADO'];
        }

        $allProjects = collect($projectsStore->all() ?: []);
        $activeProjects = $allProjects
            ->filter(fn($p) => !($p['archived'] ?? false))
            ->values();
        if ($activeProjects->isEmpty()) {
            $activeProjects = $allProjects->values();
        }
        $isAdminRole = in_array($currentRole, ['admin', 'administrador', 'superadmin'], true);
        if (!$isAdminRole) {
            $currentUserId = (string) ($currentUser->id ?? '');
            $currentUserDbId = $currentUserId !== '' ? 'db:' . $currentUserId : '';
            $activeProjects = $activeProjects->filter(function ($project) use ($currentUserId, $currentUserDbId, $currentUserName, $currentUserEmail) {
                $names = collect($project['responsables'] ?? [])
                    ->merge([$project['miembro'] ?? null, $project['responsable'] ?? null])
                    ->map(fn($value) => strtolower(trim((string) $value)))
                    ->filter()
                    ->values();

                $ids = collect($project['responsable_ids'] ?? [])
                    ->merge($project['responsables_ids'] ?? [])
                    ->map(fn($value) => strtolower(trim((string) $value)))
                    ->filter()
                    ->values();

                $taskPeople = collect($project['tareas'] ?? $project['tasks'] ?? [])->flatMap(function ($task) {
                    return collect($task['owners'] ?? [])
                        ->merge($task['owner_ids'] ?? []);
                })->map(fn($value) => strtolower(trim((string) $value)))->filter()->values();

                return ($currentUserName !== '' && ($names->contains($currentUserName) || $taskPeople->contains($currentUserName)))
                    || ($currentUserEmail !== '' && ($names->contains($currentUserEmail) || $taskPeople->contains($currentUserEmail)))
                    || ($currentUserId !== '' && ($ids->contains(strtolower($currentUserId)) || $taskPeople->contains(strtolower($currentUserId))))
                    || ($currentUserDbId !== '' && ($ids->contains(strtolower($currentUserDbId)) || $taskPeople->contains(strtolower($currentUserDbId))));
            })->values();
        }

        $projectsDashboard = $activeProjects->map(function ($p) use ($allClients, $projectStages) {
            $client = $allClients[$p['cliente_id'] ?? ''] ?? null;
            $clientName = $client['empresa'] ?? $client['nombre'] ?? $client['contacto_nombre'] ?? ($p['cliente'] ?? 'Sin cliente');

            $tasks = collect($p['tareas'] ?? $p['tasks'] ?? [])->map(function ($t) {
                $text = $t['texto'] ?? $t['text'] ?? 'Tarea sin título';
                $done = (bool) ($t['done'] ?? $t['completed'] ?? false);
                return [
                    'id' => (string) ($t['id'] ?? ''),
                    'text' => $text,
                    'done' => $done,
                    'due_date' => (string) ($t['due_date'] ?? $t['end_date'] ?? ''),
                    'priority' => (string) ($t['priority'] ?? ''),
                    'owners' => array_values((array) ($t['owners'] ?? [])),
                    'owner_ids' => array_values((array) ($t['owner_ids'] ?? [])),
                    'total_seconds' => (int) ($t['total_seconds'] ?? 0),
                ];
            })->values();

            $tasksTotal = $tasks->count();
            $tasksDone = $tasks->where('done', true)->count();

            $progress = isset($p['progreso']) && is_numeric($p['progreso'])
                ? (float) $p['progreso']
                : ($tasksTotal > 0 ? ($tasksDone / max(1, $tasksTotal)) * 100 : 0);
            $progress = max(0, min(100, round($progress, 0)));

            $stageRaw = strtoupper(trim((string) ($p['etapa'] ?? 'NUEVA')));
            $stage = in_array($stageRaw, $projectStages, true) ? $stageRaw : $projectStages[0];

            return [
                'id' => (string) ($p['id'] ?? ''),
                'title' => (string) ($p['titulo'] ?? $p['nombre'] ?? 'Proyecto sin nombre'),
                'client_name' => (string) $clientName,
                'stage' => $stage,
                'priority' => (string) ($p['prioridad'] ?? 'Atención'),
                'fecha_inicio' => (string) ($p['fecha_inicio'] ?? $p['inicio'] ?? ''),
                'due_date' => (string) ($p['vencimiento'] ?? ''),
                'progress' => $progress,
                'tasks_total' => $tasksTotal,
                'tasks_done' => $tasksDone,
                'tasks' => $tasks->all(),
                'responsables' => array_values((array) ($p['responsables'] ?? [])),
                'responsable_ids' => array_values((array) ($p['responsable_ids'] ?? [])),
                'time_logs' => count($p['time_logs'] ?? []),
                'time_seconds' => (int) collect($p['time_logs'] ?? [])->sum(function ($log) {
                    $start = (int) ($log['start'] ?? 0);
                    if ($start <= 0) {
                        return 0;
                    }
                    $end = (int) ($log['end'] ?? now()->timestamp);
                    return max(0, $end - $start);
                }),
                'updated_at' => (string) ($p['updated_at'] ?? $p['created_at'] ?? now()->toISOString()),
            ];
        })->sortByDesc('updated_at')->values();

        $todayDate = date('Y-m-d');
        $projectStats = [
            'total' => $projectsDashboard->count(),
            'completed' => $projectsDashboard->filter(fn($p) => $p['progress'] >= 100 || in_array($p['stage'], ['FINALIZADO', 'ENTREGADO'], true))->count(),
            'running' => $projectsDashboard->filter(fn($p) => $p['progress'] > 0 && $p['progress'] < 100)->count(),
            'overdue' => $projectsDashboard->filter(fn($p) => !empty($p['due_date']) && $p['due_date'] < $todayDate && $p['progress'] < 100)->count(),
        ];

        $projectStageSummary = collect($projectStages)->map(function ($stage) use ($projectsDashboard) {
            return [
                'stage' => $stage,
                'count' => $projectsDashboard->where('stage', $stage)->count(),
            ];
        })->values()->all();

        $projectDashboard = [
            'stages' => $projectStages,
            'stats' => $projectStats,
            'projects' => $projectsDashboard->all(),
            'stage_summary' => $projectStageSummary,
        ];

        // Sales dashboard (focused data for sellers)
        $isSellerRole = in_array($currentRole, ['employee', 'vendedor'], true);

        $normalizeLeadStage = function (string $stage): string {
            $map = [
                'Nuevo' => 'Posible cliente',
                'Calificado' => 'Posible cliente',
                'Propuesta' => 'Contactado',
                'Llamar' => 'Volver a llamar',
                'Seguimiento' => 'Volver a llamar',
                'Ganado' => 'Cliente',
                'Perdido' => 'Posible cliente',
            ];
            $allowed = ['Posible cliente', 'Contactado', 'Volver a llamar', 'Cliente'];
            if (in_array($stage, $allowed, true)) {
                return $stage;
            }
            return $map[$stage] ?? 'Posible cliente';
        };

        $leads = collect($leadsStore->all() ?: [])->map(function ($lead) use ($normalizeLeadStage) {
            $lead['etapa'] = $normalizeLeadStage((string) ($lead['etapa'] ?? 'Posible cliente'));
            return $lead;
        });

        if ($isSellerRole) {
            $leads = $leads->filter(function ($lead) use ($currentUserName, $currentUserEmail) {
                $assignees = collect($lead['encargados'] ?? [])->map(fn($a) => strtolower(trim((string) $a)))->filter();
                if ($assignees->isEmpty()) {
                    return false;
                }
                return ($currentUserName !== '' && $assignees->contains($currentUserName))
                    || ($currentUserEmail !== '' && $assignees->contains($currentUserEmail));
            })->values();
        }

        $nowTs = now()->timestamp;
        $nextWeekTs = now()->addDays(7)->endOfDay()->timestamp;
        $salesStageCounts = [
            'Posible cliente' => 0,
            'Contactado' => 0,
            'Volver a llamar' => 0,
            'Cliente' => 0,
        ];

        foreach ($leads as $lead) {
            $stage = (string) ($lead['etapa'] ?? 'Posible cliente');
            $salesStageCounts[$stage] = ($salesStageCounts[$stage] ?? 0) + 1;
        }

        $salesTotal = $leads->count();
        $salesWon = (int) ($salesStageCounts['Cliente'] ?? 0);
        $salesContacted = (int) ($salesStageCounts['Contactado'] ?? 0);
        $salesPendingCalls = (int) ($salesStageCounts['Volver a llamar'] ?? 0);
        $salesPipelineValue = (float) $leads->sum(function ($lead) {
            return (float) ($lead['presupuesto_estimado'] ?? $lead['valor'] ?? 0);
        });
        $salesConversionRate = $salesTotal > 0 ? round(($salesWon / $salesTotal) * 100, 1) : 0;

        $upcomingLeadReminders = $leads
            ->filter(function ($lead) use ($nowTs, $nextWeekTs) {
                $reminderRaw = trim((string) ($lead['recordatorio'] ?? ''));
                if ($reminderRaw === '') {
                    return false;
                }
                $ts = strtotime($reminderRaw);
                if ($ts === false) {
                    return false;
                }
                return $ts >= $nowTs && $ts <= $nextWeekTs;
            })
            ->sortBy(function ($lead) {
                return strtotime((string) ($lead['recordatorio'] ?? '')) ?: PHP_INT_MAX;
            })
            ->take(6)
            ->values()
            ->all();

        $staleLeadsCount = $leads->filter(function ($lead) {
            $stage = (string) ($lead['etapa'] ?? 'Posible cliente');
            if ($stage === 'Cliente') {
                return false;
            }
            $baseDate = (string) ($lead['updated_at'] ?? $lead['created_at'] ?? '');
            $ts = strtotime($baseDate);
            if ($ts === false) {
                return false;
            }
            return $ts < now()->subDays(7)->timestamp;
        })->count();

        $recentLeads = $leads
            ->sortByDesc(function ($lead) {
                $baseDate = (string) ($lead['updated_at'] ?? $lead['created_at'] ?? '');
                return strtotime($baseDate) ?: 0;
            })
            ->take(8)
            ->values()
            ->all();

        $isTsInRange = function (?string $date, int $startTs, int $endTs): bool {
            $ts = strtotime((string) $date);
            if ($ts === false) {
                return false;
            }
            return $ts >= $startTs && $ts <= $endTs;
        };

        $calcTrend = function (float $current, float $previous): array {
            if ($previous <= 0) {
                if ($current <= 0) {
                    return ['value' => 0.0, 'direction' => 'neutral'];
                }
                return ['value' => 100.0, 'direction' => 'up'];
            }
            $delta = (($current - $previous) / $previous) * 100;
            if ($delta > 0.01) {
                return ['value' => round($delta, 1), 'direction' => 'up'];
            }
            if ($delta < -0.01) {
                return ['value' => round(abs($delta), 1), 'direction' => 'down'];
            }
            return ['value' => 0.0, 'direction' => 'neutral'];
        };

        $sumAgendaMinutesInRange = function ($lead, int $startTs, int $endTs): int {
            $agenda = collect($lead['agenda'] ?? []);
            return (int) $agenda->sum(function ($item) use ($startTs, $endTs) {
                $ts = strtotime((string) ($item['fecha_hora'] ?? ''));
                if ($ts === false || $ts < $startTs || $ts > $endTs) {
                    return 0;
                }
                return (int) ($item['duracion_min'] ?? 0);
            });
        };

        $sumManualMinutesInRange = function ($lead, int $startTs, int $endTs): int {
            $entries = collect($lead['tiempo_trabajado'] ?? []);
            return (int) $entries->sum(function ($item) use ($startTs, $endTs) {
                if (($item['source'] ?? '') !== 'manual') {
                    return 0;
                }
                $ts = strtotime((string) ($item['fecha'] ?? ''));
                if ($ts === false || $ts < $startTs || $ts > $endTs) {
                    return 0;
                }
                return (int) ($item['duracion_min'] ?? 0);
            });
        };

        $now = now();
        $thisWeekStart = $now->copy()->startOfWeek()->timestamp;
        $thisWeekEnd = $now->copy()->endOfWeek()->timestamp;
        $prevWeekStart = $now->copy()->subWeek()->startOfWeek()->timestamp;
        $prevWeekEnd = $now->copy()->subWeek()->endOfWeek()->timestamp;
        $thisMonthStart = $now->copy()->startOfMonth()->timestamp;
        $thisMonthEnd = $now->copy()->endOfMonth()->timestamp;
        $prevMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->timestamp;
        $prevMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth()->timestamp;

        $createdThisWeek = $leads->filter(fn($lead) => $isTsInRange((string) ($lead['created_at'] ?? ''), $thisWeekStart, $thisWeekEnd))->count();
        $createdPrevWeek = $leads->filter(fn($lead) => $isTsInRange((string) ($lead['created_at'] ?? ''), $prevWeekStart, $prevWeekEnd))->count();

        $wonThisMonth = $leads->filter(function ($lead) use ($isTsInRange, $thisMonthStart, $thisMonthEnd) {
            return (($lead['etapa'] ?? '') === 'Cliente')
                && $isTsInRange((string) ($lead['updated_at'] ?? $lead['created_at'] ?? ''), $thisMonthStart, $thisMonthEnd);
        })->count();
        $wonPrevMonth = $leads->filter(function ($lead) use ($isTsInRange, $prevMonthStart, $prevMonthEnd) {
            return (($lead['etapa'] ?? '') === 'Cliente')
                && $isTsInRange((string) ($lead['updated_at'] ?? $lead['created_at'] ?? ''), $prevMonthStart, $prevMonthEnd);
        })->count();

        $avgBudgetThisWeek = round((float) $leads
            ->filter(fn($lead) => $isTsInRange((string) ($lead['created_at'] ?? ''), $thisWeekStart, $thisWeekEnd))
            ->avg(fn($lead) => (float) ($lead['presupuesto_estimado'] ?? $lead['valor'] ?? 0)), 2);
        $avgBudgetPrevWeek = round((float) $leads
            ->filter(fn($lead) => $isTsInRange((string) ($lead['created_at'] ?? ''), $prevWeekStart, $prevWeekEnd))
            ->avg(fn($lead) => (float) ($lead['presupuesto_estimado'] ?? $lead['valor'] ?? 0)), 2);

        $prePrevWeekStart = $now->copy()->subWeeks(2)->startOfWeek()->timestamp;
        $prePrevWeekEnd = $now->copy()->subWeeks(2)->endOfWeek()->timestamp;

        $workedMinutesThisWeek = (int) $leads->sum(fn($lead) => $sumAgendaMinutesInRange($lead, $thisWeekStart, $thisWeekEnd));
        $workedMinutesPrevWeek = (int) $leads->sum(fn($lead) => $sumAgendaMinutesInRange($lead, $prevWeekStart, $prevWeekEnd));
        $workedMinutesPrePrevWeek = (int) $leads->sum(fn($lead) => $sumAgendaMinutesInRange($lead, $prePrevWeekStart, $prePrevWeekEnd));
        $workedMinutesThisMonth = (int) $leads->sum(fn($lead) => $sumAgendaMinutesInRange($lead, $thisMonthStart, $thisMonthEnd));
        $workedMinutesPrevMonth = (int) $leads->sum(fn($lead) => $sumAgendaMinutesInRange($lead, $prevMonthStart, $prevMonthEnd));

        $workedHoursThisWeek = round($workedMinutesThisWeek / 60, 1);
        $workedHoursPrevWeek = round($workedMinutesPrevWeek / 60, 1);
        $workedHoursPrePrevWeek = round($workedMinutesPrePrevWeek / 60, 1);
        $workedHoursThisMonth = round($workedMinutesThisMonth / 60, 1);
        $workedHoursPrevMonth = round($workedMinutesPrevMonth / 60, 1);

        $hoursPeriod = (string) request()->query('hours_period', 'this_week');
        if (!in_array($hoursPeriod, ['this_week', 'last_week', 'this_month'], true)) {
            $hoursPeriod = 'this_week';
        }

        $hoursManualStore = $settingsStore->find('dashboard_sales_hours') ?: [];
        $hoursUserKey = (string) ($currentUser->id ?? ($currentUser->email ?? 'global'));
        $hoursManualByUser = (array) ($hoursManualStore[$hoursUserKey] ?? []);
        $hoursManual = [
            'this_week' => (float) ($hoursManualByUser['this_week'] ?? 0),
            'last_week' => (float) ($hoursManualByUser['last_week'] ?? 0),
            'this_month' => (float) ($hoursManualByUser['this_month'] ?? 0),
        ];

        $leadManualHours = [
            'this_week' => round(((int) $leads->sum(fn($lead) => $sumManualMinutesInRange($lead, $thisWeekStart, $thisWeekEnd))) / 60, 1),
            'last_week' => round(((int) $leads->sum(fn($lead) => $sumManualMinutesInRange($lead, $prevWeekStart, $prevWeekEnd))) / 60, 1),
            'this_month' => round(((int) $leads->sum(fn($lead) => $sumManualMinutesInRange($lead, $thisMonthStart, $thisMonthEnd))) / 60, 1),
        ];

        $hoursManual = [
            'this_week' => round((float) $hoursManual['this_week'] + (float) $leadManualHours['this_week'], 1),
            'last_week' => round((float) $hoursManual['last_week'] + (float) $leadManualHours['last_week'], 1),
            'this_month' => round((float) $hoursManual['this_month'] + (float) $leadManualHours['this_month'], 1),
        ];

        $hoursAgenda = [
            'this_week' => $workedHoursThisWeek,
            'last_week' => $workedHoursPrevWeek,
            'this_month' => $workedHoursThisMonth,
        ];

        $hoursTotals = [
            'this_week' => round($hoursAgenda['this_week'] + $hoursManual['this_week'], 1),
            'last_week' => round($hoursAgenda['last_week'] + $hoursManual['last_week'], 1),
            'this_month' => round($hoursAgenda['this_month'] + $hoursManual['this_month'], 1),
        ];

        $hoursTrends = [
            'this_week' => $calcTrend((float) $hoursTotals['this_week'], (float) $hoursTotals['last_week']),
            'last_week' => $calcTrend((float) $hoursTotals['last_week'], (float) $workedHoursPrePrevWeek),
            'this_month' => $calcTrend((float) $hoursTotals['this_month'], (float) $workedHoursPrevMonth),
        ];

        $sumProjectSecondsInRange = function (int $startTs, int $endTs, ?bool $manualOnly = null) use ($activeProjects, $currentUser, $currentRole, $currentUserName, $currentUserEmail): int {
            $isAdmin = in_array($currentRole, ['admin', 'administrador'], true);
            return (int) $activeProjects->sum(function ($project) use ($startTs, $endTs, $manualOnly, $isAdmin, $currentUser, $currentUserName, $currentUserEmail) {
                return collect($project['time_logs'] ?? [])->sum(function ($log) use ($startTs, $endTs, $manualOnly, $isAdmin, $currentUser, $currentUserName, $currentUserEmail) {
                    $start = (int) ($log['start'] ?? 0);
                    $end = (int) ($log['end'] ?? now()->timestamp);
                    if ($start <= 0 || $end <= 0 || $end < $start || $end < $startTs || $start > $endTs) {
                        return 0;
                    }

                    if ($manualOnly !== null && (bool) ($log['manual'] ?? false) !== $manualOnly) {
                        return 0;
                    }

                    if (!$isAdmin) {
                        $logUser = strtolower(trim((string) ($log['user'] ?? '')));
                        $logEmail = strtolower(trim((string) ($log['user_email'] ?? '')));
                        $userId = (string) ($currentUser->id ?? '');
                        $logUserId = (string) ($log['user_id'] ?? '');
                        $matchesUser = ($userId !== '' && $logUserId !== '' && $userId === $logUserId)
                            || ($currentUserEmail !== '' && $logEmail !== '' && $currentUserEmail === $logEmail)
                            || ($currentUserName !== '' && $logUser !== '' && $currentUserName === $logUser);
                        if (!$matchesUser) {
                            return 0;
                        }
                    }

                    $overlapStart = max($start, $startTs);
                    $overlapEnd = min($end, $endTs);
                    return max(0, $overlapEnd - $overlapStart);
                });
            });
        };

        $projectTimerHours = [
            'this_week' => round($sumProjectSecondsInRange($thisWeekStart, $thisWeekEnd, false) / 3600, 1),
            'last_week' => round($sumProjectSecondsInRange($prevWeekStart, $prevWeekEnd, false) / 3600, 1),
            'this_month' => round($sumProjectSecondsInRange($thisMonthStart, $thisMonthEnd, false) / 3600, 1),
        ];
        $projectManualHours = [
            'this_week' => round($sumProjectSecondsInRange($thisWeekStart, $thisWeekEnd, true) / 3600, 1),
            'last_week' => round($sumProjectSecondsInRange($prevWeekStart, $prevWeekEnd, true) / 3600, 1),
            'this_month' => round($sumProjectSecondsInRange($thisMonthStart, $thisMonthEnd, true) / 3600, 1),
        ];
        $projectTotals = [
            'this_week' => round($projectTimerHours['this_week'] + $projectManualHours['this_week'], 1),
            'last_week' => round($projectTimerHours['last_week'] + $projectManualHours['last_week'], 1),
            'this_month' => round($projectTimerHours['this_month'] + $projectManualHours['this_month'], 1),
        ];
        $projectPrePrevWeekTotal = round($sumProjectSecondsInRange($prePrevWeekStart, $prePrevWeekEnd, null) / 3600, 1);
        $projectPrevMonthTotal = round($sumProjectSecondsInRange($prevMonthStart, $prevMonthEnd, null) / 3600, 1);
        $projectHoursTrends = [
            'this_week' => $calcTrend((float) $projectTotals['this_week'], (float) $projectTotals['last_week']),
            'last_week' => $calcTrend((float) $projectTotals['last_week'], (float) $projectPrePrevWeekTotal),
            'this_month' => $calcTrend((float) $projectTotals['this_month'], (float) $projectPrevMonthTotal),
        ];
        $projectDashboard['hours'] = [
            'selected_period' => 'this_week',
            'period_labels' => [
                'this_week' => 'Esta semana',
                'last_week' => 'Semana pasada',
                'this_month' => 'Este mes',
            ],
            'totals' => $projectTotals,
            'agenda' => $projectTimerHours,
            'manual' => $projectManualHours,
            'trends' => $projectHoursTrends,
        ];

        $followupsThisWeek = count($upcomingLeadReminders);
        $followupsPrevWeek = $leads->filter(function ($lead) use ($isTsInRange, $prevWeekStart, $prevWeekEnd) {
            return $isTsInRange((string) ($lead['recordatorio'] ?? ''), $prevWeekStart, $prevWeekEnd);
        })->count();

        $salesTrends = [
            'created_week' => $calcTrend((float) $createdThisWeek, (float) $createdPrevWeek),
            'won_month' => $calcTrend((float) $wonThisMonth, (float) $wonPrevMonth),
            'avg_budget_week' => $calcTrend((float) $avgBudgetThisWeek, (float) $avgBudgetPrevWeek),
            'followups_week' => $calcTrend((float) $followupsThisWeek, (float) $followupsPrevWeek),
            'worked_hours_week' => $hoursTrends['this_week'],
        ];

        $salesVelocity = min(100, max(0, (int) round(
            (($salesConversionRate * 0.45)
            + (min(100, $salesContacted * 10) * 0.25)
            + (min(100, max(0, 100 - ($staleLeadsCount * 10))) * 0.30))
        )));

        $allLeadsAssignedToMe = collect($leadsStore->all() ?: [])->filter(function ($lead) use ($currentUserName, $currentUserEmail) {
            $assignees = collect($lead['encargados'] ?? [])->map(fn($a) => strtolower(trim((string) $a)))->filter();
            if ($assignees->isEmpty()) {
                return false;
            }
            return ($currentUserName !== '' && $assignees->contains($currentUserName))
                || ($currentUserEmail !== '' && $assignees->contains($currentUserEmail));
        })->count();

        $salesCloseCapacity = min(100, max(0, (int) round(
            ($salesConversionRate * 0.55)
            + (min(100, ($salesWon * 25)) * 0.25)
            + (min(100, max(0, 100 - ($salesPendingCalls * 8))) * 0.20)
        )));

        $salesDashboard = [
            'is_seller_role' => $isSellerRole,
            'total' => $salesTotal,
            'won' => $salesWon,
            'contacted' => $salesContacted,
            'pending_calls' => $salesPendingCalls,
            'pipeline_value' => $salesPipelineValue,
            'conversion_rate' => $salesConversionRate,
            'stage_counts' => $salesStageCounts,
            'upcoming_reminders' => $upcomingLeadReminders,
            'stale_count' => $staleLeadsCount,
            'recent_leads' => $recentLeads,
            'created_this_week' => $createdThisWeek,
            'won_this_month' => $wonThisMonth,
            'avg_budget_this_week' => $avgBudgetThisWeek,
            'followups_this_week' => $followupsThisWeek,
            'worked_hours_this_week' => $hoursTotals['this_week'],
            'velocity' => $salesVelocity,
            'my_leads' => $isSellerRole ? $salesTotal : $allLeadsAssignedToMe,
            'close_capacity' => $salesCloseCapacity,
            'trends' => $salesTrends,
            'hours' => [
                'selected_period' => $hoursPeriod,
                'period_labels' => [
                    'this_week' => 'Esta semana',
                    'last_week' => 'Semana pasada',
                    'this_month' => 'Este mes',
                ],
                'totals' => $hoursTotals,
                'agenda' => $hoursAgenda,
                'manual' => $hoursManual,
                'trends' => $hoursTrends,
            ],
            'all_leads' => $leads->map(function ($lead) {
                return [
                    'id' => (string) ($lead['id'] ?? ''),
                    'nombre' => (string) ($lead['nombre'] ?? 'Lead'),
                    'etapa' => (string) ($lead['etapa'] ?? ''),
                ];
            })->filter(fn($lead) => $lead['id'] !== '')->values()->all(),
        ];

        return view('dashboard', compact(
            'totalClients', 
            'monthlyIncome', 
            'lastMonthIncome',
            'growth',
            'chartData',
            'advancedChartData',
            'activities',
            'totalInvoices', 
            'monthlyExpenses',
            'tasksList',
            'products',
            'topClients',
            'salesGoal',
            'salesProgress',
            'upcomingInvoices',
            'baseCurrency',
            'projectDashboard',
            'salesDashboard',
            'summaryRange',
            'summaryRangeLabel',
            'summaryCompareLabel',
            'salesGoalMonth',
            'salesGoalSemester',
            'salesGoalYear',
            'salesGoalTotal',
            'freeMoney'
        ));
    }
    
    public function updateGoal(\Illuminate\Http\Request $request)
    {
        $goal = (float) $request->input('goal', 50000);
        $goalRange = (string) $request->input('goal_range', 'month');
        if (!in_array($goalRange, ['month', 'semester', 'year'], true)) {
            $goalRange = 'month';
        }
        $store = new FileStore('settings.json');
        $settings = $store->find('dashboard') ?: [];
        $settings['sales_goal'] = $goal;
        $goalsByRange = (array) ($settings['sales_goal_by_range'] ?? []);
        $goalsByRange[$goalRange] = round(max(0, $goal), 2);
        $settings['sales_goal_by_range'] = $goalsByRange;
        $store->update('dashboard', $settings);
        
        return response()->json(['success' => true]);
    }

    public function updateSalesHours(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'period' => 'required|in:this_week,last_week,this_month',
            'hours' => 'required|numeric|min:0|max:744',
        ]);

        $period = (string) $data['period'];
        $hours = round((float) $data['hours'], 1);

        $settingsStore = new FileStore('settings.json');
        $current = $settingsStore->find('dashboard_sales_hours') ?: [];

        $user = auth()->user();
        $userKey = (string) ($user->id ?? ($user->email ?? 'global'));

        $userHours = (array) ($current[$userKey] ?? []);
        $userHours[$period] = $hours;
        $current[$userKey] = $userHours;

        if ($settingsStore->find('dashboard_sales_hours')) {
            $settingsStore->update('dashboard_sales_hours', $current);
        } else {
            $settingsStore->create(['id' => 'dashboard_sales_hours'] + $current);
        }

        return redirect()->route('dashboard', ['hours_period' => $period])->with('success', 'Horas comerciales actualizadas.');
    }

    protected function calcIncome($invoices, $month, $baseCurrency = 'USD')
    {
        return round(collect($invoices)
            ->filter(fn($i) => ($i['estado'] ?? '') !== 'En borrador')
            ->sum(function ($i) use ($month, $baseCurrency) {
                $invoiceTotal = (float) ($i['total'] ?? 0);
                $baseTotal = (float) ($i['total_base'] ?? $invoiceTotal);
                $isForeign = (($i['moneda'] ?? $baseCurrency) !== $baseCurrency) && $invoiceTotal > 0 && $baseTotal > 0;
                $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

                $sumPaymentsInMonth = round(collect($i['pagos'] ?? [])
                    ->filter(fn($p) => !empty($p['fecha']) && str_starts_with($p['fecha'], $month))
                    ->sum(function ($p) use ($isForeign, $factor, $invoiceTotal) {
                        if (isset($p['monto_base'])) {
                            return (float) $p['monto_base'];
                        }
                        $monto = (float) ($p['monto'] ?? 0);
                        if (!$isForeign) {
                            return $monto;
                        }
                        return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
                    }), 2);

                // Compatibilidad histórica para facturas antiguas sin registros de pago.
                if ($sumPaymentsInMonth <= 0 && empty($i['pagos']) && ($i['estado'] ?? '') === 'Pagada' && !empty($i['fecha']) && str_starts_with($i['fecha'], $month)) {
                    return $baseTotal;
                }

                return $sumPaymentsInMonth;
            }), 2);
    }

    protected function periodBounds(string $range): array
    {
        $now = now();
        return match ($range) {
            'semester' => $now->month <= 6
                ? [$now->copy()->startOfYear()->startOfDay()->timestamp, $now->copy()->startOfYear()->addMonths(5)->endOfMonth()->endOfDay()->timestamp]
                : [$now->copy()->startOfYear()->addMonths(6)->startOfMonth()->startOfDay()->timestamp, $now->copy()->endOfYear()->endOfDay()->timestamp],
            'year' => [$now->copy()->startOfYear()->startOfDay()->timestamp, $now->copy()->endOfYear()->endOfDay()->timestamp],
            'all' => [0, 4102444799],
            default => [$now->copy()->startOfMonth()->startOfDay()->timestamp, $now->copy()->endOfMonth()->endOfDay()->timestamp],
        };
    }

    protected function previousRange(string $range): string
    {
        return match ($range) {
            'semester' => 'prev_semester',
            'year' => 'prev_year',
            'all' => 'prev_all',
            default => 'prev_month',
        };
    }

    protected function previousBounds(string $range): array
    {
        $now = now();
        return match ($range) {
            'prev_semester' => $now->month <= 6
                ? [$now->copy()->subYear()->startOfYear()->addMonths(6)->startOfMonth()->startOfDay()->timestamp, $now->copy()->subYear()->endOfYear()->endOfDay()->timestamp]
                : [$now->copy()->startOfYear()->startOfDay()->timestamp, $now->copy()->startOfYear()->addMonths(5)->endOfMonth()->endOfDay()->timestamp],
            'prev_year' => [$now->copy()->subYear()->startOfYear()->startOfDay()->timestamp, $now->copy()->subYear()->endOfYear()->endOfDay()->timestamp],
            'prev_all' => [0, $now->copy()->subYear()->endOfYear()->endOfDay()->timestamp],
            default => [$now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay()->timestamp, $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay()->timestamp],
        };
    }

    protected function calcIncomeByRange(array $invoices, string $range, string $baseCurrency = 'USD'): float
    {
        [$startTs, $endTs] = in_array($range, ['prev_month', 'prev_semester', 'prev_year', 'prev_all'], true)
            ? $this->previousBounds($range)
            : $this->periodBounds($range);

        return round(collect($invoices)
            ->filter(fn($i) => ($i['estado'] ?? '') !== 'En borrador')
            ->sum(function ($i) use ($startTs, $endTs, $baseCurrency) {
                $invoiceTotal = (float) ($i['total'] ?? 0);
                $baseTotal = (float) ($i['total_base'] ?? $invoiceTotal);
                $isForeign = (($i['moneda'] ?? $baseCurrency) !== $baseCurrency) && $invoiceTotal > 0 && $baseTotal > 0;
                $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

                $sumPaymentsInRange = round(collect($i['pagos'] ?? [])
                    ->filter(function ($p) use ($startTs, $endTs) {
                        $ts = strtotime((string) ($p['fecha'] ?? ''));
                        return $ts !== false && $ts >= $startTs && $ts <= $endTs;
                    })
                    ->sum(function ($p) use ($isForeign, $factor, $invoiceTotal) {
                        if (isset($p['monto_base'])) return (float) $p['monto_base'];
                        $monto = (float) ($p['monto'] ?? 0);
                        if (!$isForeign) return $monto;
                        return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
                    }), 2);

                if ($sumPaymentsInRange <= 0 && empty($i['pagos']) && ($i['estado'] ?? '') === 'Pagada' && !empty($i['fecha'])) {
                    $issuedTs = strtotime((string) $i['fecha']);
                    if ($issuedTs !== false && $issuedTs >= $startTs && $issuedTs <= $endTs) return $baseTotal;
                }
                return $sumPaymentsInRange;
            }), 2);
    }

    protected function calcExpensesByRange(array $expenses, string $range): float
    {
        [$startTs, $endTs] = $this->periodBounds($range);
        return round((float) collect($expenses)
            ->filter(function ($e) use ($startTs, $endTs) {
                $ts = strtotime((string) ($e['fecha'] ?? ''));
                return $ts !== false && $ts >= $startTs && $ts <= $endTs;
            })
            ->sum(fn($e) => (float) ($e['monto'] ?? 0)), 2);
    }
}
