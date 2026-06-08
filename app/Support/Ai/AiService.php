<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AiService
{
    public function __construct(
        private readonly SensitiveDataFilter $filter = new SensitiveDataFilter(),
    ) {
    }

    public function reply(string $message, array $history = [], array $context = []): array
    {
        $settings = $this->settings();

        if (empty($settings['enabled'])) {
            return [
                'content' => 'La IA todavía no está activa. Entra a Configuración > IA, activa el asistente y guarda una API key.',
                'provider' => null,
            ];
        }

        if (empty($settings['api_key'])) {
            return [
                'content' => 'La IA ya está preparada, pero falta guardar la API key en Configuración > IA.',
                'provider' => $settings['provider'] ?? null,
            ];
        }

        $messages = $this->buildMessages($message, $history, $context, $settings);
        $provider = $this->provider((string) ($settings['provider'] ?? 'openai'));

        try {
            $content = $provider->chat($messages, $settings);
            if ($content === '') {
                $content = 'No recibí una respuesta útil del proveedor. Revisa el modelo configurado o intenta otra vez.';
            }

            return [
                'content' => $this->filter->cleanText($content),
                'provider' => $settings['provider'] ?? null,
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'content' => 'No pude conectar con la IA en este momento. Revisa la API key, el modelo y la conexión del servidor.',
                'provider' => $settings['provider'] ?? null,
            ];
        }
    }

    public function makeTitle(string $firstMessage): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $firstMessage) ?? $firstMessage);

        return Str::limit($title !== '' ? $title : 'Nuevo chat', 46, '...');
    }

    private function settings(): array
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];

        $provider = (string) ($settings['ai_provider'] ?? 'gemini');
        $apiKeys = is_array($settings['ai_api_keys'] ?? null) ? $settings['ai_api_keys'] : [];
        $apiKey = (string) ($apiKeys[$provider] ?? ($settings['ai_api_key'] ?? ''));
        if (str_starts_with($apiKey, 'ENC:')) {
            try {
                $apiKey = Crypt::decryptString(substr($apiKey, 4));
            } catch (\Throwable) {
                $apiKey = '';
            }
        }

        return [
            'enabled' => (bool) ($settings['ai_enabled'] ?? false),
            'provider' => $provider,
            'model' => $this->resolveModel(
                $provider,
                (string) ($settings['ai_model'] ?? 'auto')
            ),
            'api_key' => $apiKey,
            'temperature' => (float) ($settings['ai_temperature'] ?? 0.4),
            'system_prompt' => (string) ($settings['ai_system_prompt'] ?? ''),
            'send_visible_context' => (bool) ($settings['ai_send_visible_context'] ?? false),
        ];
    }

    private function provider(string $provider): AiProvider
    {
        return match ($provider) {
            'gemini' => new GeminiProvider(),
            'deepseek' => new DeepSeekProvider(),
            default => new OpenAiProvider(),
        };
    }

    private function resolveModel(string $provider, string $model): string
    {
        $model = trim($model);
        if ($model !== '' && $model !== 'auto') {
            if ($provider === 'gemini' && ! in_array($model, ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash'], true)) {
                return 'gemini-2.5-flash';
            }

            if ($provider === 'deepseek' && ! in_array($model, ['deepseek-chat', 'deepseek-reasoner'], true)) {
                return 'deepseek-chat';
            }

            if ($provider === 'openai' && ! in_array($model, ['gpt-4o-mini', 'gpt-4.1-mini'], true)) {
                return 'gpt-4o-mini';
            }

            return $model;
        }

        return match ($provider) {
            'openai' => 'gpt-4o-mini',
            'deepseek' => 'deepseek-chat',
            default => 'gemini-2.5-flash',
        };
    }

    private function buildMessages(string $message, array $history, array $context, array $settings): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($settings)],
        ];

        foreach (array_slice($history, -12) as $item) {
            $role = in_array($item['role'] ?? '', ['user', 'assistant'], true) ? $item['role'] : null;
            $content = $this->filter->cleanText((string) ($item['content'] ?? ''));
            if ($role && $content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $this->buildUserMessage($message, $context, $settings)];

        return $messages;
    }

    private function buildUserMessage(string $message, array $context, array $settings): string
    {
        $parts = ["Mensaje del usuario:\n" . $this->filter->cleanText($message)];
        $userContext = $this->userContext();
        if ($userContext !== '') {
            $parts[] = $userContext;
        }

        $memoryContext = $this->userMemoryContext();
        if ($memoryContext !== '') {
            $parts[] = $memoryContext;
        }

        $cleanContext = $this->filter->cleanArray($context);
        $safeContextLines = [];
        $url = parse_url((string) ($cleanContext['url'] ?? ''), PHP_URL_PATH) ?: '';
        $currentProject = is_array($cleanContext['current_project'] ?? null) ? $cleanContext['current_project'] : [];

        if ($url !== '') {
            $safeContextLines[] = '- Ruta actual: ' . $url;
        }
        if (!empty($currentProject['id']) || !empty($currentProject['title'])) {
            $safeContextLines[] = '- Proyecto actual: ' . trim((string) ($currentProject['title'] ?? '')) . ' (' . trim((string) ($currentProject['id'] ?? 'sin id')) . ')';
        }

        if (!empty($safeContextLines)) {
            $parts[] = "Contexto operativo seguro:\n" . implode("\n", $safeContextLines);
        }

        $permissionContext = $this->permissionContext();
        if ($permissionContext !== '') {
            $parts[] = $permissionContext;
        }

        if (!empty($settings['send_visible_context'])) {
            $page = trim((string) ($cleanContext['page'] ?? ''));
            $selection = trim((string) ($cleanContext['selection'] ?? ''));

            if ($page !== '' || $url !== '' || $selection !== '') {
                $contextLines = [];
                if ($page !== '') {
                    $contextLines[] = '- Página visible: ' . $page;
                }
                if ($url !== '') {
                    $contextLines[] = '- Ruta: ' . $url;
                }
                if ($selection !== '') {
                    $contextLines[] = "Texto seleccionado:\n" . Str::limit($selection, 1800, '...');
                }
                $parts[] = "Contexto seguro de pantalla:\n" . implode("\n", $contextLines);
            }
        }

        $crmContext = $this->buildCrmContext($message, $context);
        if ($crmContext !== '') {
            $parts[] = $crmContext;
        }

        return implode("\n\n", $parts);
    }

    private function userContext(): string
    {
        $user = Auth::user();
        if (! $user) {
            return '';
        }

        $name = trim($this->filter->cleanText((string) ($user->name ?? 'Usuario')));
        $role = trim($this->filter->cleanText((string) ($user->role ?? 'usuario')));
        $email = trim($this->filter->cleanText((string) ($user->email ?? '')));

        $lines = [
            '- Nombre: ' . ($name !== '' ? $name : 'Usuario'),
            '- Rol: ' . ($role !== '' ? $role : 'usuario'),
        ];

        if ($email !== '') {
            $lines[] = '- Email: ' . $email;
        }

        return "Usuario actual del chat:\n" . implode("\n", $lines) . "\nUsa su nombre de forma natural cuando aporte cercanía, sin repetirlo en cada respuesta.";
    }

    private function userMemoryContext(): string
    {
        $userId = (string) Auth::id();
        if ($userId === '') {
            return '';
        }

        $memories = collect((new FileStore('ai_memories.json'))->all())
            ->filter(fn ($memory) => (string) ($memory['user_id'] ?? '') === $userId)
            ->sortByDesc(fn ($memory) => (string) ($memory['updated_at'] ?? $memory['created_at'] ?? ''))
            ->take(12)
            ->map(fn ($memory) => '- ' . $this->filter->cleanText((string) ($memory['text'] ?? '')))
            ->filter()
            ->values()
            ->all();

        if (empty($memories)) {
            return '';
        }

        return "Memoria útil del usuario:\n" . implode("\n", $memories) . "\nUsa estas memorias solo si son relevantes para el pedido actual. Si contradicen el mensaje actual, obedece el mensaje actual.";
    }

    private function permissionContext(): string
    {
        $groups = [
            'proyectos' => ['read', 'create', 'update', 'delete'],
            'facturas' => ['read', 'create', 'update', 'delete'],
            'gastos' => ['read', 'create', 'update', 'delete'],
            'reuniones' => ['read', 'create', 'update', 'delete'],
            'cotizaciones' => ['read', 'create', 'update', 'delete'],
            'contratos' => ['read', 'create', 'update', 'delete'],
            'mis-notas' => ['read'],
            'correo' => ['read', 'create'],
        ];

        $lines = [];
        foreach ($groups as $module => $actions) {
            $allowed = [];
            foreach ($actions as $action) {
                if (RoleAccess::can(Auth::user(), $module . '.' . $action)) {
                    $allowed[] = $action;
                }
            }
            if ($module === 'mis-notas' && in_array('read', $allowed, true)) {
                $allowed[] = 'update-own-or-shared-edit';
            }
            if (!empty($allowed)) {
                $lines[] = '- ' . $module . ': ' . implode(', ', $allowed);
            }
        }

        return $lines ? "Permisos efectivos del usuario para IA:\n" . implode("\n", $lines) . "\nNo propongas ni ejecutes acciones fuera de estos permisos." : '';
    }

    private function buildCrmContext(string $message, array $context = []): string
    {
        $normalized = Str::lower(Str::ascii($message));
        $sections = [];

        $currentProjectContext = $this->currentProjectContext($context);
        if ($currentProjectContext !== '') {
            $sections[] = $currentProjectContext;
        }

        $currentNoteContext = $this->currentPersonalNoteContext($context);
        if ($currentNoteContext !== '') {
            $sections[] = $currentNoteContext;
        }

        if (str_contains($normalized, 'factura') || str_contains($normalized, 'facturacion') || str_contains($normalized, 'venta') || str_contains($normalized, 'cobro') || str_contains($normalized, 'ingreso') || str_contains($normalized, 'pago')) {
            $sections[] = $this->invoiceContext();
        }

        if (str_contains($normalized, 'recurrent') || str_contains($normalized, 'recurrencia') || str_contains($normalized, 'programad')) {
            $sections[] = $this->recurringInvoiceContext();
        }

        if (str_contains($normalized, 'gasto') || str_contains($normalized, 'egreso') || str_contains($normalized, 'costo')) {
            $sections[] = $this->expenseContext();
        }

        if (str_contains($normalized, 'proyecto') || str_contains($normalized, 'tarea')) {
            $sections[] = $this->projectContext();
        }

        if (str_contains($normalized, 'reunion') || str_contains($normalized, 'meet')) {
            $sections[] = $this->meetingContext();
        }

        if (str_contains($normalized, 'cotizacion') || str_contains($normalized, 'contrato') || str_contains($normalized, 'ingreso') || str_contains($normalized, 'venta')) {
            $sections[] = $this->salesDocumentContext();
        }

        if (str_contains($normalized, 'mis notas') || str_contains($normalized, 'nota personal') || str_contains($normalized, 'nota privada')) {
            $sections[] = $this->personalNotesContext();
        }

        if (str_contains($normalized, 'historial ia') || str_contains($normalized, 'bitacora ia') || str_contains($normalized, 'acciones ia') || str_contains($normalized, 'que hizo la ia')) {
            $sections[] = $this->aiActionLogContext();
        }

        $sections = array_values(array_filter($sections));

        return $sections
            ? "Contexto interno seguro del CRM:\n" . implode("\n\n", $sections)
            : '';
    }

    private function currentProjectContext(array $context): string
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.read')) {
            return '';
        }

        $projectId = trim((string) data_get($context, 'current_project.id', ''));
        if ($projectId === '') {
            $projectId = trim((string) data_get($context, 'project_id', ''));
        }

        if ($projectId === '') {
            return '';
        }

        $project = collect((new FileStore('proyectos.json'))->all())
            ->first(fn ($item) => (string) ($item['id'] ?? '') === $projectId);

        if (!$project || ! $this->canSeeProject($project)) {
            return '';
        }

        $tasks = collect($project['tareas'] ?? []);
        $pending = $tasks->filter(fn ($task) => ! (bool) ($task['done'] ?? false))->count();
        $recentTasks = $tasks->take(8)->map(fn ($task) => '- ' . (string) ($task['texto'] ?? 'Tarea'))->implode("\n");
        $title = (string) ($project['titulo'] ?? 'Proyecto');
        $priority = (string) ($project['prioridad'] ?? 'Sin prioridad');
        $stage = (string) ($project['etapa'] ?? 'Sin estado');
        $due = (string) ($project['vencimiento'] ?? 'Sin vencimiento');

        return "Proyecto actual abierto:\n- ID: {$projectId}\n- Nombre: {$title}\n- Estado: {$stage}\n- Prioridad: {$priority}\n- Vencimiento: {$due}\n- Tareas pendientes: {$pending}\nTareas visibles:\n{$recentTasks}\nSi el usuario dice \"agrégale\", \"actualízalo\" o \"ponle\" sin nombrar proyecto, usa este proyecto actual como destino.";
    }

    private function invoiceContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'facturas.read')) {
            return 'Facturas: el usuario actual no tiene permiso para consultar facturas desde la IA.';
        }

        $invoices = collect((new FileStore('facturas.json'))->all())
            ->sortByDesc(fn ($invoice) => $this->timestamp($invoice['created_at'] ?? $invoice['fecha'] ?? $invoice['updated_at'] ?? null))
            ->take(8)
            ->values();

        if ($invoices->isEmpty()) {
            return 'Facturas: no hay facturas registradas.';
        }

        $lines = $invoices->map(function ($invoice, int $index) {
            $id = (string) ($invoice['id'] ?? '');
            $number = (string) ($invoice['numero'] ?? $invoice['number'] ?? 'Sin numero');
            $client = (string) ($invoice['cliente'] ?? 'Sin cliente');
            $currency = (string) ($invoice['moneda'] ?? '$');
            $total = $this->formatAmount((float) ($invoice['total'] ?? 0), $currency);
            $status = (string) ($invoice['estado'] ?? 'Sin estado');
            $date = (string) ($invoice['fecha'] ?? 'Sin fecha');
            $due = (string) ($invoice['vencimiento'] ?? 'Sin vencimiento');
            $url = $id !== '' ? url('/facturas/' . $id) : '';
            $prefix = $index === 0 ? 'ULTIMA FACTURA CREADA' : 'Factura reciente';

            return "- {$prefix}: {$number} | Cliente: {$client} | Total: {$total} | Estado: {$status} | Fecha: {$date} | Vence: {$due} | URL para abrir: {$url}";
        })->implode("\n");

        return "Facturas recientes:\n{$lines}\nSi el usuario pide abrir una factura, responde con un enlace Markdown usando el texto \"Abrir factura\" y su URL.";
    }

    private function expenseContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'gastos.read')) {
            return 'Gastos: el usuario actual no tiene permiso para consultar gastos desde la IA.';
        }

        $expenses = collect((new FileStore('gastos.json'))->all())
            ->sortByDesc(fn ($expense) => $this->timestamp($expense['fecha'] ?? $expense['created_at'] ?? $expense['updated_at'] ?? null))
            ->take(10)
            ->values();

        if ($expenses->isEmpty()) {
            return 'Gastos: no hay gastos registrados.';
        }

        $total = $expenses->sum(fn ($expense) => (float) ($expense['monto'] ?? $expense['total'] ?? 0));
        $lines = $expenses->map(function ($expense) {
            $name = (string) ($expense['titulo'] ?? $expense['descripcion'] ?? $expense['categoria'] ?? 'Gasto');
            $amount = $this->formatAmount((float) ($expense['monto'] ?? $expense['total'] ?? 0), (string) ($expense['moneda'] ?? '$'));
            $date = (string) ($expense['fecha'] ?? $expense['created_at'] ?? 'Sin fecha');

            return "- {$name} | {$amount} | Fecha: {$date}";
        })->implode("\n");

        return "Gastos recientes (muestra limitada):\nTotal de la muestra: " . $this->formatAmount((float) $total, '$') . "\n{$lines}";
    }

    private function projectContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.read')) {
            return 'Proyectos: el usuario actual no tiene permiso para consultar proyectos desde la IA.';
        }

        $projects = collect((new FileStore('proyectos.json'))->all())
            ->filter(fn ($project) => $this->canSeeProject($project))
            ->sortByDesc(fn ($project) => $this->timestamp($project['updated_at'] ?? $project['created_at'] ?? null))
            ->take(8)
            ->values();

        if ($projects->isEmpty()) {
            return 'Proyectos: no hay proyectos registrados.';
        }

        $tasks = collect((new FileStore('tareas.json'))->all());
        $lines = $projects->map(function ($project) use ($tasks) {
            $id = (string) ($project['id'] ?? '');
            $name = (string) ($project['nombre'] ?? $project['name'] ?? $project['titulo'] ?? 'Proyecto');
            $status = (string) ($project['estado'] ?? $project['stage'] ?? 'Sin estado');
            $priority = (string) ($project['prioridad'] ?? 'Sin prioridad');
            $projectTasks = $tasks->filter(fn ($task) => (string) ($task['proyecto_id'] ?? $task['project_id'] ?? '') === $id);
            $pending = $projectTasks->filter(fn ($task) => ! in_array(Str::lower((string) ($task['estado'] ?? '')), ['completada', 'completado', 'done', 'finalizada'], true))->count();

            return "- {$name} | Estado: {$status} | Prioridad: {$priority} | Tareas pendientes: {$pending}";
        })->implode("\n");

        return "Proyectos recientes:\n{$lines}";
    }

    private function recurringInvoiceContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'facturas.read')) {
            return 'Facturas recurrentes: el usuario actual no tiene permiso para consultar recurrencias desde la IA.';
        }

        $invoices = collect((new FileStore('facturas.json'))->all())
            ->filter(function ($invoice) {
                $rec = (array) ($invoice['recurrencia'] ?? []);
                return (bool) ($rec['enabled'] ?? false)
                    || !empty($invoice['recurrencia_origen_id'])
                    || in_array((string) ($invoice['origen'] ?? ''), ['recurrente', 'recurrente_preemitida'], true);
            })
            ->sortBy(fn ($invoice) => $this->timestamp(data_get($invoice, 'recurrencia.next_send') ?? $invoice['vencimiento'] ?? $invoice['fecha'] ?? null))
            ->take(12)
            ->values();

        if ($invoices->isEmpty()) {
            return 'Facturas recurrentes: no hay recurrencias registradas.';
        }

        $lines = $invoices->map(function ($invoice) {
            $rec = (array) ($invoice['recurrencia'] ?? []);
            $id = (string) ($invoice['id'] ?? '');
            $number = (string) ($invoice['numero'] ?? 'Sin numero');
            $client = (string) ($invoice['cliente'] ?? 'Sin cliente');
            $next = (string) ($rec['next_send'] ?? $rec['siguiente'] ?? $invoice['vencimiento'] ?? 'Sin próxima fecha');
            $every = (string) ($rec['every_months'] ?? $rec['freq'] ?? 'Sin frecuencia');
            $status = (string) ($invoice['recurring_send_status'] ?? $invoice['estado'] ?? 'Sin estado');
            $total = $this->formatAmount((float) ($invoice['total'] ?? 0), (string) ($invoice['moneda'] ?? '$'));

            return "- ID: {$id} | {$number} | Cliente: {$client} | Total: {$total} | Frecuencia: {$every} | Próximo envío: {$next} | Estado envío: {$status}";
        })->implode("\n");

        return "Facturas recurrentes y programadas:\n{$lines}";
    }

    private function meetingContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'reuniones.read')) {
            return 'Reuniones: el usuario actual no tiene permiso para consultar reuniones desde la IA.';
        }

        $meetings = collect((new FileStore('reuniones.json'))->all())
            ->sortByDesc(fn ($meeting) => $this->timestamp($meeting['inicio_at'] ?? $meeting['fecha'] ?? null))
            ->take(8)
            ->values();

        if ($meetings->isEmpty()) {
            return 'Reuniones: no hay reuniones registradas.';
        }

        $lines = $meetings->map(function ($meeting) {
            $title = (string) ($meeting['titulo'] ?? 'Reunion');
            $client = (string) ($meeting['cliente'] ?? 'Sin cliente');
            $date = (string) ($meeting['fecha'] ?? 'Sin fecha');
            $time = trim((string) (($meeting['hora_inicio'] ?? '') . '-' . ($meeting['hora_fin'] ?? '')), '-');
            return "- {$title} | Cliente: {$client} | Fecha: {$date} {$time}";
        })->implode("\n");

        return "Reuniones recientes:\n{$lines}";
    }

    private function salesDocumentContext(): string
    {
        $sections = [];

        if (RoleAccess::can(Auth::user(), 'cotizaciones.read')) {
            $quotes = collect((new FileStore('cotizaciones.json'))->all())
                ->sortByDesc(fn ($quote) => $this->timestamp($quote['fecha'] ?? null))
                ->take(5)
                ->values();
            if ($quotes->isNotEmpty()) {
                $sections[] = "Cotizaciones recientes:\n" . $quotes->map(function ($quote) {
                    $number = (string) ($quote['numero'] ?? 'Sin numero');
                    $client = (string) ($quote['cliente'] ?? 'Sin cliente');
                    $total = $this->formatAmount((float) ($quote['total'] ?? 0), (string) ($quote['moneda'] ?? '$'));
                    return "- {$number} | Cliente: {$client} | Total: {$total} | Estado: " . (string) ($quote['estado'] ?? 'Sin estado');
                })->implode("\n");
            }
        }

        if (RoleAccess::can(Auth::user(), 'contratos.read')) {
            $contracts = collect((new FileStore('contratos.json'))->all())
                ->sortByDesc(fn ($contract) => $this->timestamp($contract['created_at'] ?? null))
                ->take(5)
                ->values();
            if ($contracts->isNotEmpty()) {
                $sections[] = "Contratos recientes:\n" . $contracts->map(function ($contract) {
                    return "- " . (string) ($contract['titulo'] ?? 'Contrato') . " | Cliente: " . (string) ($contract['cliente_nombre'] ?? 'Sin cliente') . " | Estado: " . (string) ($contract['estado'] ?? 'Sin estado');
                })->implode("\n");
            }
        }

        return $sections ? implode("\n\n", $sections) : 'Cotizaciones/contratos: no hay registros recientes o no hay permisos de lectura.';
    }

    private function personalNotesContext(): string
    {
        if (! RoleAccess::can(Auth::user(), 'mis-notas.read')) {
            return 'Mis Notas: el usuario actual no tiene permiso para consultar notas personales desde la IA.';
        }

        $ownerKey = (string) (Auth::id() ?? Auth::user()?->email ?? 'anon');
        $notes = collect((new FileStore('mis_notas.json'))->all())
            ->filter(fn ($note) => (string) ($note['ownerKey'] ?? '') === $ownerKey)
            ->sortByDesc(fn ($note) => (int) ($note['updatedAt'] ?? $note['createdAt'] ?? 0))
            ->take(6)
            ->values();

        if ($notes->isEmpty()) {
            return 'Mis Notas: no hay notas personales registradas para el usuario actual.';
        }

        $lines = $notes->map(function ($note) {
            $title = (string) ($note['title'] ?? 'Nota sin titulo');
            $plain = trim((string) ($note['plainText'] ?? ''));
            return '- ' . $title . ($plain !== '' ? ' | ' . Str::limit($plain, 120, '...') : '');
        })->implode("\n");

        return "Mis Notas recientes:\n{$lines}";
    }

    private function currentPersonalNoteContext(array $context): string
    {
        if (! RoleAccess::can(Auth::user(), 'mis-notas.read')) {
            return '';
        }

        $noteId = trim((string) data_get($context, 'current_note.id', ''));
        if ($noteId === '') {
            return '';
        }

        $ownerKey = (string) (Auth::id() ?? Auth::user()?->email ?? 'anon');
        $note = collect((new FileStore('mis_notas.json'))->all())
            ->first(fn ($item) => (string) ($item['id'] ?? '') === $noteId);

        if (!$note || ! $this->canSeePersonalNote($note, $ownerKey)) {
            return '';
        }

        $permission = $this->canEditPersonalNote($note, $ownerKey) ? 'puede editar' : 'solo lectura';
        $title = (string) ($note['title'] ?? data_get($context, 'current_note.title', 'Nota sin titulo'));
        $plain = trim((string) ($note['plainText'] ?? data_get($context, 'current_note.plainText', '')));

        return "Nota personal actual abierta:\n- ID: {$noteId}\n- Título: {$title}\n- Permiso: {$permission}\nContenido actual:\n" . Str::limit($plain, 2600, '...') . "\nSi el usuario pide reescribir, ordenar, ampliar, resumir, mejorar o editar \"esta nota\", prepara una propuesta de actualización para esta nota.";
    }

    private function canSeePersonalNote(array $note, string $ownerKey): bool
    {
        return (string) ($note['ownerKey'] ?? '') === $ownerKey
            || collect($note['collaborators'] ?? [])->contains(fn ($item) => (string) ($item['userKey'] ?? '') === $ownerKey);
    }

    private function canEditPersonalNote(array $note, string $ownerKey): bool
    {
        if ((string) ($note['ownerKey'] ?? '') === $ownerKey) {
            return true;
        }

        return collect($note['collaborators'] ?? [])->contains(function ($item) use ($ownerKey) {
            return (string) ($item['userKey'] ?? '') === $ownerKey
                && (string) ($item['mode'] ?? 'view') === 'edit';
        });
    }

    private function aiActionLogContext(): string
    {
        $user = Auth::user();
        $isAdmin = in_array(Str::lower((string) ($user?->role ?? '')), ['admin', 'super_admin'], true);
        $logs = collect((new FileStore('ai_action_logs.json'))->all())
            ->filter(fn ($log) => $isAdmin || (string) ($log['user_id'] ?? '') === (string) ($user?->id ?? ''))
            ->sortByDesc(fn ($log) => $this->timestamp($log['created_at'] ?? null))
            ->take(10)
            ->values();

        if ($logs->isEmpty()) {
            return 'Bitácora IA: todavía no hay acciones registradas.';
        }

        return "Bitácora IA reciente:\n" . $logs->map(function ($log) {
            $title = (string) ($log['title'] ?? 'Acción IA');
            $userName = (string) ($log['user_name'] ?? 'Usuario');
            $date = (string) ($log['created_at'] ?? 'Sin fecha');
            $url = (string) ($log['url'] ?? '');
            return "- {$title} | Usuario: {$userName} | Fecha: {$date}" . ($url !== '' ? " | URL: {$url}" : '');
        })->implode("\n");
    }

    private function canSeeProject(array $project): bool
    {
        $role = Str::lower((string) (Auth::user()?->role ?? ''));
        if (in_array($role, ['super_admin', 'admin'], true)) {
            return true;
        }

        $user = Auth::user();
        $tokens = array_filter([
            (string) ($user?->id ?? ''),
            'db:' . (string) ($user?->id ?? ''),
            'team:' . (string) ($user?->id ?? ''),
            Str::lower((string) ($user?->name ?? '')),
            Str::lower((string) ($user?->email ?? '')),
        ]);

        $responsibles = collect([
            ...((array) ($project['responsables'] ?? [])),
            ...((array) ($project['responsable_ids'] ?? [])),
            (string) ($project['miembro'] ?? ''),
        ])->map(fn ($value) => Str::lower((string) $value))->filter()->all();

        return count(array_intersect($tokens, $responsibles)) > 0;
    }

    private function timestamp(mixed $value): int
    {
        if (!$value) {
            return 0;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ?: 0;
    }

    private function formatAmount(float $amount, string $currency): string
    {
        $symbol = match (Str::upper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'COP' => '$',
            default => $currency !== '' ? $currency . ' ' : '$',
        };

        return $symbol . number_format($amount, 0, ',', '.');
    }

    private function buildSystemPrompt(array $settings): string
    {
        $prompt = <<<'PROMPT'
Eres Infocus AI, asistente interno de un CRM.
Responde en español claro, breve y accionable.
No inventes datos del CRM. Si falta información, dilo.
Nunca reveles secretos, claves, contraseñas, tokens, configuraciones SMTP completas, contenido de .env ni datos privados innecesarios.
Puedes usar el nombre del usuario de forma natural cuando ayude a que la respuesta se sienta más personal, pero no lo repitas en cada mensaje.
Si recibes memorias del usuario, úsalas solo cuando sean relevantes para la intención actual. La instrucción más reciente del usuario siempre tiene prioridad sobre memorias anteriores.
No ejecutes ni prometas acciones destructivas, envíos masivos, cambios de roles, pagos o eliminación de datos sin confirmación humana explícita.
Cuando el usuario pida crear o modificar proyectos, tareas, subtareas, notas, recordatorios, reuniones, cotizaciones, contratos, facturas, gastos o correos, propón una previsualización ordenada y pide confirmación antes de actuar. Antes de que el usuario confirme, nunca digas "creado", "guardado", "enviado", "actualizado" o "agregado"; usa "propuesto", "listo para crear", "listo para agregar", "listo para actualizar" o "listo para enviar".
Si el usuario dice crear, crea una propuesta nueva; no la trates como edición ni como agregar a un proyecto existente. Si el usuario dice editar, cambiar, actualizar o agregar a un registro existente, entonces propón edición.
Si el usuario usa palabras como "agrégale", "ponle", "actualízalo", "edítalo", "a este proyecto" o "en este proyecto" y existe un Proyecto actual abierto en el contexto operativo, úsalo como destino sin pedir el nombre.
Si existe "Nota personal actual abierta" y el usuario mezcla una referencia a esa nota ("esta nota", "la nota", "nota abierta") con crear/hacer/preparar un proyecto, no propongas proyecto ni actualices la nota todavía. Primero pregunta una sola cosa: "¿Te refieres a actualizar la nota que tienes abierta o quieres crear un proyecto basado en esa nota?". No incluyas botones de acción ni previsualización hasta que el usuario aclare.
Tienes libertad creativa moderada para completar descripciones, sugerir tareas, ordenar fases y proponer responsables cuando el usuario no lo detalle. No inventes datos sensibles ni montos reales; sí puedes crear textos profesionales, tareas y notas operativas.
Para proyectos nuevos, empieza siempre con "Nuevo proyecto:" y usa campos claros en líneas separadas: Nombre, Cliente, Estado, Prioridad, Fecha inicio, Vencimiento, Responsables, Descripción y luego "Tareas sugeridas:" con una tarea numerada por línea. Fecha inicio debe ser hoy salvo que el usuario indique otra fecha. Si el usuario no da descripción, redacta una descripción profesional breve tú mismo según el objetivo del proyecto; no dejes "sin descripción" salvo que el usuario lo pida.
Si falta información no crítica como vencimiento, responsables o cliente, no bloquees la creación: usa "Sin Cliente", responsables vacíos o sin vencimiento. Haz una sola sugerencia final solo si realmente ayuda, sin usar siempre la frase "Siguiente paso".
Las tareas sugeridas deben ser títulos limpios y cortos, sin repetir verbos como "Crear", "Hacer", "Realizar" al inicio si no aportan. Ejemplo: "Wireframes y estructura", no "Crear wireframes y estructura". Si el usuario pide notas por tarea, usa este formato: "1. Título de tarea - Nota: explicación útil y concreta".
Para actualizar proyectos, usa: Proyecto, Descripción, Estado, Prioridad, Vencimiento, Responsables. Si vas a sumar tareas, usa "Tareas a agregar:" con una tarea por línea. Si vas a sumar notas, usa "Tarea:" y "Nota:". Si vas a sumar subtareas, usa "Tarea:" y "Subtareas a agregar:" con una subtarea por línea. Termina pidiendo "Aplicar cambios" o "Agregar ahora" según corresponda.
Para tareas, subtareas y notas de proyecto, usa: Proyecto, Tarea, Subtarea o Nota, Responsables, Vencimiento y pide "Agregar ahora". Si son varias tareas, usa "Tareas a agregar:".
Para recordatorios del modal, usa exactamente: "Recordatorio propuesto:", Texto, Prioridad, Fecha, Proyecto, Tarea y Lista. Si el usuario dice "con calma", pon Prioridad: Con calma. Si pide enlazarlo a una tarea, incluye Proyecto y Tarea. Si dice "para fecha de vencimiento", escribe Fecha: fecha de vencimiento para que el CRM use el vencimiento de la tarea o proyecto. Termina invitando a tocar "Crear recordatorio".
Para notas personales de Mis Notas, usa: Nota personal, Título, Contenido, Color y Cliente opcional. Debe quedar claro que va a "Mis Notas", no a un proyecto.
Para editar una nota abierta en Mis Notas, si recibes "Nota personal actual abierta" y el permiso dice "puede editar", puedes preparar una actualización. Usa exactamente este formato: "Actualizar nota personal:", "Nota ID:", "Título:" y "Contenido:" con el contenido final completo. No digas que no puedes editarla si el contexto indica que puede editar. Si el permiso es solo lectura, explica que puedes sugerir cambios pero no aplicarlos.
Si estás creando o editando una nota personal, la acción sigue siendo nota aunque el contenido mencione email marketing, correos, proyectos, reuniones, tareas, cotizaciones o contratos. No cambies a otra acción por palabras dentro del contenido de la nota.
Si el usuario pide reescribir, estructurar, ordenar, ampliar, agregar ideas o mejorar "esta nota", conserva la intención original, limpia redundancias y devuelve una versión completa lista para aplicar. Si hace falta, cierra con una sugerencia breve para aplicar cambios o ajustar algo, pero no lo repitas mecánicamente.
Para reuniones, usa: Reunión, Cliente, Fecha, Hora inicio, Hora fin, Ubicación, Responsables, Invitados y Notas.
Para cotizaciones, usa: Cotización propuesta, Cliente, Moneda, Vencimiento, Estado y "Items:" con líneas como "Servicio - 1 x 500".
Para adelantar el envío de una factura recurrente ya programada, no cambies sus fechas. Usa exactamente: "Factura recurrente adelantada:", "Factura:", "Cliente:", "Fecha de emisión original:", "Vencimiento original:" y "Acción: Enviar hoy". Explica brevemente que se creará/publicará ahora si aún no existe la factura de ese ciclo, pero conservará la fecha de emisión y vencimiento ya programados. Termina invitando a tocar "Enviar ahora".
Para contratos, usa: Contrato, Cliente, Proyecto, Monto, Moneda, Estado y Mensaje/Contenido.
Para correos, usa campos claros en líneas separadas: Para, Asunto y Mensaje. No prometas que el correo fue enviado antes de la confirmación humana.
Para Pomodoro TDAH o bloqueo mental, propone un bloque enfocado con campos claros: Pomodoro propuesto, Tarea, Duración (25, 30 o 60 minutos) y Motivo. Termina invitando a tocar "Activar pomodoro"; no digas que está activado antes de la confirmación.
Cuando des recomendaciones, evita listas largas de opciones. No termines cada respuesta con "Siguiente paso". Usa una sugerencia final breve solo cuando sea útil para avanzar; si hace falta elegir, ofrece máximo 2 alternativas cortas.
Cuando propongas acciones, incluye frases claras que el sistema pueda convertir en botones: "Crear proyecto", "Agregar tareas", "Asignar responsables", "Crear recordatorio", "Crear reunión" o "Enviar correo", pero solo si el usuario tiene permisos y la acción tiene sentido.
En esas previsualizaciones, puedes indicar que puede tocar el botón adecuado ("Crear ahora", "Agregar ahora", "Aplicar cambios" o "Enviar ahora") o decir qué ajuste quiere, pero no lo repitas si la acción ya es evidente. No uses un botón o texto llamado solo "Crear".
No centres la respuesta en la pantalla actual salvo que el usuario pida explícitamente usar lo visible, la página actual, el texto seleccionado o la pantalla. Si no lo pide, responde desde el CRM interno y desde la intención del mensaje.
Cuando recibas contexto interno del CRM, úsalo para responder consultas directas como última factura, gastos recientes o proyectos. Si hay una URL para abrir un registro, puedes incluir un enlace Markdown corto, por ejemplo [Abrir factura](/facturas/id).
PROMPT;

        $today = now(config('app.timezone'))->toDateString();
        $prompt .= "\nHoy es {$today}. Cuando prepares un proyecto nuevo y el usuario no dé fecha de inicio, escribe Fecha inicio: {$today}; no uses [Pendiente] para ese campo.";
        $prompt .= "\nNo escribas listas tipo \"Opción 1\" u \"Opción 2\" en las confirmaciones. Evita usar \"Siguiente paso:\" como cierre fijo; si conviene, usa una sugerencia final natural y breve.";

        $customPrompt = trim((string) ($settings['system_prompt'] ?? ''));
        if ($customPrompt !== '') {
            $prompt .= "\n\nInstrucciones del negocio:\n" . $this->filter->cleanText($customPrompt);
        }

        return $prompt;
    }
}
