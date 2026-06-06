<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    protected FileStore $projects;
    protected FileStore $notificationState;
    protected FileStore $facturas;
    protected FileStore $leads;
    protected FileStore $settings;
    protected FileStore $clientes;
    protected FileStore $roles;
    protected FileStore $portalAccess;
    protected FileStore $meetingReminders;

    public function __construct()
    {
        $this->projects = new FileStore('proyectos.json');
        $this->notificationState = new FileStore('notification_states.json');
        $this->facturas = new FileStore('facturas.json');
        $this->leads = new FileStore('leads.json');
        $this->settings = new FileStore('settings.json');
        $this->clientes = new FileStore('clientes.json');
        $this->roles = new FileStore('roles.json');
        $this->portalAccess = new FileStore('portal_access_logs.json');
        $this->meetingReminders = new FileStore('meeting_reminders.json');
    }

    public function show(Request $request)
    {
        $identity = $this->resolveIdentity($request);
        $currentMonth = now();
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();
        $projects = collect($this->projects->all())
            ->reject(fn ($project) => (bool) ($project['archived'] ?? false))
            ->values();

        $assignedProjects = [];
        $assignedTasks = [];
        $workedSeconds = 0;
        $workedByDay = [];

        foreach ($projects as $project) {
            $projectTasks = collect($project['tareas'] ?? []);
            $hasAssignedTask = false;

            foreach ($projectTasks as $task) {
                if ($this->isTaskAssignedToIdentity($task, $project, $identity)) {
                    $hasAssignedTask = true;
                    $assignedTasks[] = [
                        'project_id' => (string) ($project['id'] ?? ''),
                        'project_title' => (string) ($project['titulo'] ?? 'Proyecto'),
                        'task_id' => (string) ($task['id'] ?? ''),
                        'task_text' => (string) ($task['texto'] ?? 'Tarea'),
                        'priority' => (string) ($task['priority'] ?? $project['prioridad'] ?? 'Atención'),
                        'done' => (bool) ($task['done'] ?? false),
                        'due_date' => (string) ($task['end_date'] ?? $task['due_date'] ?? ''),
                        'total_seconds' => (int) ($task['total_seconds'] ?? 0),
                    ];
                }
            }

            if ($hasAssignedTask) {
                $assignedProjects[] = [
                    'id' => (string) ($project['id'] ?? ''),
                    'title' => (string) ($project['titulo'] ?? 'Proyecto'),
                    'stage' => (string) ($project['etapa'] ?? 'Sin etapa'),
                    'priority' => (string) ($project['prioridad'] ?? 'Atención'),
                    'client' => (string) ($project['cliente'] ?? 'Sin cliente'),
                ];
            }

            foreach (($project['time_logs'] ?? []) as $log) {
                $actor = Str::lower(trim((string) ($log['user'] ?? '')));
                if ($actor === '' || $actor !== Str::lower($identity['name'])) {
                    continue;
                }
                $start = (int) ($log['start'] ?? 0);
                $end = (int) ($log['end'] ?? 0);
                if ($start <= 0 || $end <= 0 || $end < $start) {
                    continue;
                }

                $startAt = Carbon::createFromTimestamp($start);
                if ($startAt->lt($monthStart) || $startAt->gt($monthEnd)) {
                    continue;
                }

                $duration = ($end - $start);
                $workedSeconds += $duration;

                $dayKey = $startAt->toDateString();
                $workedByDay[$dayKey] = ($workedByDay[$dayKey] ?? 0) + $duration;
            }
        }

        usort($assignedTasks, function ($a, $b) {
            return strcmp(($a['due_date'] ?? ''), ($b['due_date'] ?? ''));
        });

        $metrics = [
            'worked_seconds' => $workedSeconds,
            'worked_label' => $this->formatDuration($workedSeconds),
            'worked_month_label' => $monthStart->translatedFormat('F Y'),
            'projects_count' => count($assignedProjects),
            'tasks_count' => count($assignedTasks),
            'tasks_pending' => count(array_filter($assignedTasks, fn ($task) => !$task['done'])),
            'tasks_done' => count(array_filter($assignedTasks, fn ($task) => $task['done'])),
        ];

        $workedCalendar = [
            'month' => $monthStart->format('Y-m'),
            'days' => collect($workedByDay)->map(fn ($seconds) => [
                'seconds' => (int) $seconds,
                'label' => $this->formatDuration((int) $seconds),
            ])->all(),
            'total_seconds' => $workedSeconds,
            'total_label' => $this->formatDuration($workedSeconds),
        ];

        return view('profile.show', [
            'identity' => $identity,
            'metrics' => $metrics,
            'assignedProjects' => $assignedProjects,
            'assignedTasks' => $assignedTasks,
            'workedCalendar' => $workedCalendar,
        ]);
    }

    public function workedHours(Request $request)
    {
        $identity = $this->resolveIdentity($request);
        $monthInput = trim((string) $request->query('month', now()->format('Y-m')));

        try {
            $monthStart = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = now()->startOfMonth();
        }
        $monthEnd = $monthStart->copy()->endOfMonth();

        $projects = collect($this->projects->all())
            ->reject(fn ($project) => (bool) ($project['archived'] ?? false))
            ->values();

        $totalSeconds = 0;
        $days = [];

        foreach ($projects as $project) {
            foreach (($project['time_logs'] ?? []) as $log) {
                $actor = Str::lower(trim((string) ($log['user'] ?? '')));
                if ($actor === '' || $actor !== Str::lower($identity['name'])) {
                    continue;
                }

                $start = (int) ($log['start'] ?? 0);
                $end = (int) ($log['end'] ?? 0);
                if ($start <= 0 || $end <= 0 || $end < $start) {
                    continue;
                }

                $startAt = Carbon::createFromTimestamp($start);
                if ($startAt->lt($monthStart) || $startAt->gt($monthEnd)) {
                    continue;
                }

                $duration = $end - $start;
                $totalSeconds += $duration;
                $dayKey = $startAt->toDateString();
                $days[$dayKey] = ($days[$dayKey] ?? 0) + $duration;
            }
        }

        return response()->json([
            'ok' => true,
            'month' => $monthStart->format('Y-m'),
            'days' => collect($days)->map(fn ($seconds) => [
                'seconds' => (int) $seconds,
                'label' => $this->formatDuration((int) $seconds),
            ])->all(),
            'total_seconds' => $totalSeconds,
            'total_label' => $this->formatDuration($totalSeconds),
        ]);
    }

    public function update(Request $request)
    {
        if (!Auth::check()) {
            return back()->withErrors([
                'profile' => 'Debes iniciar sesion para editar tu perfil.',
            ]);
        }

        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:40',
            'profile_info' => 'nullable|string|max:500',
            'profile_photo' => 'nullable|image|max:2048',
            'remove_profile_photo' => 'nullable|boolean',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($data['new_password'])) {
            $user->password = Hash::make((string) $data['new_password']);
        }

        if ($request->boolean('remove_profile_photo')) {
            $user->profile_photo_path = null;
        }

        if ($request->hasFile('profile_photo')) {
            $folder = public_path('uploads/profile');
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            $file = $request->file('profile_photo');
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $filename = 'profile_'.$user->id.'_'.time().'.'.$extension;
            $file->move($folder, $filename);
            $user->profile_photo_path = '/uploads/profile/'.$filename;
        }

        $user->name = (string) $data['name'];
        $user->email = (string) $data['email'];
        $user->phone = (string) ($data['phone'] ?? '');
        $user->profile_info = (string) ($data['profile_info'] ?? '');
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Perfil actualizado correctamente.');
    }

    public function notifications(Request $request)
    {
        $identity = $this->resolveIdentity($request);
        $notifications = $this->buildNotifications($identity);

        $stateKey = $this->notificationStateKey($identity);
        $state = $this->notificationState->find($stateKey) ?: ['read_ids' => []];
        $readIds = collect($state['read_ids'] ?? [])->map(fn ($id) => (string) $id)->values()->all();

        $items = collect($notifications)->map(function ($item) use ($readIds) {
            $item['read'] = in_array((string) $item['id'], $readIds, true);
            return $item;
        })->values()->all();

        $unreadCount = collect($items)->filter(fn ($item) => !$item['read'])->count();

        return response()->json([
            'ok' => true,
            'items' => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markNotificationRead(Request $request)
    {
        $data = $request->validate([
            'notification_id' => 'required|string',
        ]);

        $identity = $this->resolveIdentity($request);
        $stateKey = $this->notificationStateKey($identity);
        $state = $this->notificationState->find($stateKey) ?: ['read_ids' => []];

        $readIds = collect($state['read_ids'] ?? [])->map(fn ($id) => (string) $id)->push((string) $data['notification_id'])->unique()->values()->all();

        $this->notificationState->update($stateKey, [
            'read_ids' => $readIds,
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $identity = $this->resolveIdentity($request);
        $notifications = $this->buildNotifications($identity);
        $ids = collect($notifications)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();

        $stateKey = $this->notificationStateKey($identity);
        $this->notificationState->update($stateKey, [
            'read_ids' => $ids,
            'updated_at' => now()->toIso8601String(),
        ]);

        return response()->json(['ok' => true]);
    }

    protected function resolveIdentity(Request $request): array
    {
        if (Auth::check()) {
            $user = Auth::user();
            return [
                'id' => (string) $user->id,
                'name' => trim((string) ($user->name ?? 'Usuario')),
                'email' => trim((string) ($user->email ?? '')),
                'role' => trim((string) ($user->role ?? 'admin')),
                'phone' => trim((string) ($user->phone ?? '')),
                'profile_info' => trim((string) ($user->profile_info ?? '')),
                'profile_photo' => trim((string) ($user->profile_photo_path ?? '')),
            ];
        }

        return [
            'id' => 'session-user',
            'name' => trim((string) $request->session()->get('user.name', 'Usuario')),
            'email' => trim((string) $request->session()->get('user.email', '')),
            'role' => trim((string) $request->session()->get('user.role', 'admin')),
            'phone' => '',
            'profile_info' => '',
            'profile_photo' => '',
        ];
    }

    protected function notificationStateKey(array $identity): string
    {
        $base = trim((string) ($identity['email'] ?: $identity['id']));
        return 'user:'.Str::slug(Str::lower($base), '-');
    }

    protected function isTaskAssignedToIdentity(array $task, array $project, array $identity): bool
    {
        $identityName = Str::lower(trim((string) ($identity['name'] ?? '')));
        $identityDbId = trim((string) ($identity['id'] ?? ''));

        $ownerIds = collect($task['owner_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
        if ($identityDbId !== '' && in_array('db:'.$identityDbId, $ownerIds, true)) {
            return true;
        }

        $owners = collect($task['owners'] ?? [])->map(fn ($name) => Str::lower(trim((string) $name)))->all();
        if ($identityName !== '' && in_array($identityName, $owners, true)) {
            return true;
        }

        $projectMembers = collect($project['responsables'] ?? [])->map(fn ($name) => Str::lower(trim((string) $name)))->all();
        if ($identityName !== '' && in_array($identityName, $projectMembers, true)) {
            return true;
        }

        return false;
    }

    protected function buildNotifications(array $identity): array
    {
        $access = $this->resolveNotificationAccess($identity);
        $items = [];

        if (!empty($access['proyectos'])) {
            $items = array_merge($items, $this->buildProjectNotifications($identity));
        }

        if (!empty($access['leads'])) {
            $items = array_merge($items, $this->buildLeadNotifications());
        }

        if (!empty($access['ventas'])) {
            $items = array_merge($items, $this->buildSalesNotifications());
            $items = array_merge($items, $this->buildPortalAccessNotifications());
        }

        if (!empty($access['reuniones'])) {
            $items = array_merge($items, $this->buildMeetingNotifications($identity));
        }

        usort($items, function ($a, $b) {
            $priority = [
                'payment' => 0,
                'invoice_sent' => 1,
                'portal_access' => 2,
                'meeting_reminder' => 3,
                'timer_started' => 4,
                'overdue' => 5,
                'upcoming' => 6,
                'due_soon' => 7,
                'lead_reminder' => 8,
                'progress' => 9,
            ];
            $pa = $priority[$a['kind']] ?? 99;
            $pb = $priority[$b['kind']] ?? 99;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        });

        return array_values(array_slice($items, 0, 80));
    }

    protected function buildProjectNotifications(array $identity): array
    {
        $today = now()->startOfDay();
        $soon = now()->addDays(3)->endOfDay();
        $projects = collect($this->projects->all())
            ->reject(fn ($project) => (bool) ($project['archived'] ?? false))
            ->values();

        $items = [];

        foreach ($projects as $project) {
            // Timer activo de proyecto (cualquier usuario del equipo)
            $running = collect($project['time_logs'] ?? [])->last(function ($log) {
                return empty($log['end']) && !empty($log['start']);
            });
            if ($running) {
                $startedAt = Carbon::createFromTimestamp((int) ($running['start'] ?? now()->timestamp));
                $taskName = (string) ($running['task_name'] ?? 'Tarea sin nombre');
                $userName = (string) ($running['user'] ?? 'Usuario');
                $projectName = (string) ($project['titulo'] ?? 'Proyecto');
                $projectId = (string) ($project['id'] ?? '');
                $taskId = (string) ($running['task_id'] ?? 'timer');
                $items[] = [
                    'id' => 'timer_started:project:' . $projectId . ':' . $taskId . ':' . $startedAt->timestamp,
                    'kind' => 'timer_started',
                    'title' => 'Temporizador activo en proyecto',
                    'message' => $userName . ' inició temporizador en "' . $taskName . '" (' . $projectName . ').',
                    'date' => $startedAt->toDateTimeString(),
                    'url' => route('proyectos.index') . '?view=tareas',
                ];
            }

            foreach (($project['tareas'] ?? []) as $task) {
                if (!$this->isTaskAssignedToIdentity($task, $project, $identity)) {
                    continue;
                }
                if ((bool) ($task['done'] ?? false)) {
                    continue;
                }

                $dueRaw = (string) ($task['end_date'] ?? $task['due_date'] ?? '');
                $due = null;
                if ($dueRaw !== '') {
                    try {
                        $due = Carbon::parse($dueRaw)->endOfDay();
                    } catch (\Throwable $e) {
                        $due = null;
                    }
                }
                $baseId = (string) ($project['id'] ?? '').':'.(string) ($task['id'] ?? '');
                $taskName = (string) ($task['texto'] ?? 'Tarea');
                $projectName = (string) ($project['titulo'] ?? 'Proyecto');

                if ($due && $due->lt($today)) {
                    $items[] = [
                        'id' => 'overdue:'.$baseId,
                        'kind' => 'overdue',
                        'title' => 'Tarea vencida',
                        'message' => '"'.$taskName.'" en '.$projectName.' venció el '.$due->format('d/m/Y').'.',
                        'date' => $due->toDateString(),
                        'url' => route('proyectos.index').'?view=tareas',
                    ];
                    continue;
                }

                if ($due && $due->lte($soon)) {
                    $items[] = [
                        'id' => 'upcoming:'.$baseId,
                        'kind' => 'upcoming',
                        'title' => 'Tarea próxima a vencer',
                        'message' => '"'.$taskName.'" en '.$projectName.' vence el '.$due->format('d/m/Y').'.',
                        'date' => $due->toDateString(),
                        'url' => route('proyectos.index').'?view=tareas',
                    ];
                    continue;
                }

                if ((int) ($task['total_seconds'] ?? 0) > 0) {
                    $items[] = [
                        'id' => 'progress:'.$baseId,
                        'kind' => 'progress',
                        'title' => 'Tarea en progreso',
                        'message' => '"'.$taskName.'" en '.$projectName.' ya tiene tiempo registrado.',
                        'date' => now()->toDateString(),
                        'url' => route('proyectos.index').'?view=tareas',
                    ];
                }
            }
        }

        return array_values($items);
    }

    protected function buildMeetingNotifications(array $identity): array
    {
        $items = [];
        $limitDate = now()->subDays(3);

        foreach (collect($this->meetingReminders->all())->sortByDesc('date') as $event) {
            $at = $this->parseDate((string) ($event['date'] ?? $event['created_at'] ?? ''));
            if (!$at || $at->lt($limitDate)) {
                continue;
            }
            if (!$this->canIdentitySeeMeetingReminder($event, $identity)) {
                continue;
            }

            $items[] = [
                'id' => (string) ($event['id'] ?? 'meeting_reminder:' . Str::ulid()),
                'kind' => 'meeting_reminder',
                'title' => (string) ($event['title'] ?? 'Recordatorio de reunión'),
                'message' => (string) ($event['message'] ?? 'Tienes una reunión próxima.'),
                'date' => $at->toDateTimeString(),
                'url' => (string) ($event['url'] ?? route('reuniones.index')),
            ];
        }

        return $items;
    }

    protected function canIdentitySeeMeetingReminder(array $event, array $identity): bool
    {
        $role = Str::lower(trim((string) ($identity['role'] ?? '')));
        if (in_array($role, ['admin', 'super_admin'], true)) {
            return true;
        }

        $identityId = trim((string) ($identity['id'] ?? ''));
        $identityIds = array_values(array_filter([
            $identityId,
            $identityId !== '' ? 'db:' . $identityId : '',
            (string) session('user.id', ''),
            session('user.id') ? 'team:' . session('user.id') : '',
        ]));

        $targetIds = collect($event['target_ids'] ?? [])->map(fn ($id) => (string) $id)->filter()->values()->all();
        if ($targetIds && array_intersect($identityIds, $targetIds)) {
            return true;
        }

        $email = Str::lower(trim((string) ($identity['email'] ?? '')));
        $targetEmails = collect($event['target_emails'] ?? [])->map(fn ($item) => Str::lower(trim((string) $item)))->filter()->values()->all();
        if ($email !== '' && in_array($email, $targetEmails, true)) {
            return true;
        }

        $name = Str::lower(trim((string) ($identity['name'] ?? '')));
        $targetNames = collect($event['target_names'] ?? [])->map(fn ($item) => Str::lower(trim((string) $item)))->filter()->values()->all();
        return $name !== '' && in_array($name, $targetNames, true);
    }

    protected function buildLeadNotifications(): array
    {
        $items = [];

        // Timers de leads activos (se guardan en settings con id lead_timer_<user>)
        foreach (collect($this->settings->all()) as $row) {
            $id = (string) ($row['id'] ?? '');
            if (!str_starts_with($id, 'lead_timer_')) {
                continue;
            }
            $startedRaw = (string) ($row['started_at'] ?? '');
            if ($startedRaw === '') {
                continue;
            }
            try {
                $startedAt = Carbon::parse($startedRaw);
            } catch (\Throwable $e) {
                continue;
            }

            $leadName = (string) ($row['lead_nombre'] ?? 'Lead');
            $userName = (string) ($row['user_name'] ?? 'Usuario');
            $leadId = (string) ($row['lead_id'] ?? 'lead');
            $items[] = [
                'id' => 'timer_started:lead:' . $leadId . ':' . $startedAt->timestamp,
                'kind' => 'timer_started',
                'title' => 'Temporizador activo en lead',
                'message' => $userName . ' inició temporizador en "' . $leadName . '".',
                'date' => $startedAt->toDateTimeString(),
                'url' => route('leads.index'),
            ];
        }

        // Recordatorios de leads próximos
        $now = now();
        $soon = now()->copy()->addDays(2)->endOfDay();
        foreach (collect($this->leads->all()) as $lead) {
            $reminderRaw = trim((string) ($lead['recordatorio'] ?? ''));
            if ($reminderRaw === '') {
                continue;
            }
            try {
                $rem = Carbon::parse($reminderRaw);
            } catch (\Throwable $e) {
                continue;
            }
            if ($rem->lt($now->copy()->subDays(1)) || $rem->gt($soon)) {
                continue;
            }
            $leadId = (string) ($lead['id'] ?? 'lead');
            $leadName = (string) ($lead['nombre'] ?? 'Lead');
            $items[] = [
                'id' => 'lead_reminder:' . $leadId . ':' . $rem->timestamp,
                'kind' => 'lead_reminder',
                'title' => 'Recordatorio de lead',
                'message' => '"' . $leadName . '" tiene seguimiento ' . $rem->format('d/m/Y H:i') . '.',
                'date' => $rem->toDateTimeString(),
                'url' => route('leads.index'),
            ];
        }

        return $items;
    }

    protected function buildSalesNotifications(): array
    {
        $items = [];
        $limitDate = now()->subDays(10);
        $now = now()->startOfDay();
        $dueSoon = now()->copy()->addDays(3)->endOfDay();

        foreach (collect($this->facturas->all()) as $invoice) {
            $invoiceId = (string) ($invoice['id'] ?? '');
            $number = (string) ($invoice['numero'] ?? '---');
            $client = (string) ($invoice['cliente'] ?? 'Cliente');

            // Factura enviada (nuevo campo)
            $sentAt = $this->parseDate((string) ($invoice['last_sent_at'] ?? ''));
            if ($sentAt && $sentAt->gte($limitDate)) {
                $items[] = [
                    'id' => 'invoice_sent:' . $invoiceId . ':' . $sentAt->timestamp,
                    'kind' => 'invoice_sent',
                    'title' => 'Factura enviada',
                    'message' => 'Se envió la factura ' . $number . ' a ' . $client . '.',
                    'date' => $sentAt->toDateTimeString(),
                    'url' => route('facturas.show', ['id' => $invoiceId]),
                ];
            }

            // Pago recibido (toma el último pago)
            $payments = collect((array) ($invoice['pagos'] ?? []))
                ->map(function ($p) {
                    $date = $this->parseDate((string) ($p['created_at'] ?? $p['fecha'] ?? ''));
                    if (!$date) return null;
                    $p['_at'] = $date;
                    return $p;
                })
                ->filter()
                ->sortByDesc(fn ($p) => $p['_at']->timestamp)
                ->values();

            $lastPayment = $payments->first();
            if ($lastPayment && $lastPayment['_at']->gte($limitDate)) {
                $items[] = [
                    'id' => 'payment:' . $invoiceId . ':' . $lastPayment['_at']->timestamp,
                    'kind' => 'payment',
                    'title' => 'Pago registrado',
                    'message' => 'Se registró pago en factura ' . $number . ' (' . $client . ').',
                    'date' => $lastPayment['_at']->toDateTimeString(),
                    'url' => route('facturas.show', ['id' => $invoiceId]),
                ];
            }

            // Factura por vencer
            $estado = (string) ($invoice['estado'] ?? '');
            if (!in_array($estado, ['Pendiente', 'Enviada', 'Vencida'], true)) {
                continue;
            }
            $due = $this->parseDate((string) ($invoice['vencimiento'] ?? ''));
            if (!$due) {
                continue;
            }
            if ($due->gte($now) && $due->lte($dueSoon)) {
                $items[] = [
                    'id' => 'due_soon:' . $invoiceId . ':' . $due->timestamp,
                    'kind' => 'due_soon',
                    'title' => 'Factura próxima a vencer',
                    'message' => $number . ' de ' . $client . ' vence el ' . $due->format('d/m/Y') . '.',
                    'date' => $due->toDateTimeString(),
                    'url' => route('facturas.show', ['id' => $invoiceId]),
                ];
            }
        }

        return $items;
    }

    protected function buildPortalAccessNotifications(): array
    {
        $items = [];
        $limitDate = now()->subDays(7);
        foreach (collect($this->portalAccess->all())->sortByDesc('created_at') as $event) {
            $at = $this->parseDate((string) ($event['created_at'] ?? ''));
            if (!$at || $at->lt($limitDate)) {
                continue;
            }
            $clientName = (string) ($event['client_name'] ?? 'Cliente');
            $clientId = (string) ($event['client_id'] ?? '');
            $invoiceHint = trim((string) ($event['invoice_hint'] ?? ''));
            $extra = $invoiceHint !== '' ? ' Revisó ' . $invoiceHint . '.' : '';
            $items[] = [
                'id' => 'portal_access:' . (string) ($event['id'] ?? Str::ulid()),
                'kind' => 'portal_access',
                'title' => 'Acceso al portal de clientes',
                'message' => $clientName . ' ingresó al portal.' . $extra,
                'date' => $at->toDateTimeString(),
                'url' => $clientId !== '' ? route('clientes.show', ['id' => $clientId]) : route('clientes.index'),
            ];
        }

        return $items;
    }

    protected function resolveNotificationAccess(array $identity): array
    {
        $role = Str::lower(trim((string) ($identity['role'] ?? '')));
        if (in_array($role, ['admin', 'super_admin', 'manager'], true)) {
            return ['proyectos' => true, 'ventas' => true, 'leads' => true, 'reuniones' => true];
        }

        $permissions = $this->resolveRolePermissions($role);

        $hasPrefix = function (array $prefixes) use ($permissions): bool {
            foreach ($permissions as $perm) {
                if ($perm === '*') {
                    return true;
                }
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($perm, $prefix)) {
                        return true;
                    }
                }
            }
            return false;
        };

        return [
            'proyectos' => $hasPrefix(['proyectos.', 'timer.proyectos', 'panel.']),
            'ventas' => $hasPrefix(['ventas.', 'facturas.', 'pagos.', 'cotizaciones.', 'productos.', 'correo.']),
            'leads' => $hasPrefix(['leads.']),
            'reuniones' => $hasPrefix(['reuniones.']),
        ];
    }

    protected function resolveRolePermissions(string $role): array
    {
        if ($role === '') {
            return [];
        }
        $row = collect($this->roles->all())->first(fn ($r) => Str::lower((string) ($r['id'] ?? '')) === $role);
        return collect($row['permissions'] ?? [])->map(fn ($p) => (string) $p)->filter()->values()->all();
    }

    protected function parseDate(string $raw): ?Carbon
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%dH:%dM', $hours, $minutes);
    }
}
