<?php

namespace App\Support\Ai;

use App\Http\Controllers\FacturasController;
use App\Repositories\FileStore;
use App\Repositories\TimelineStore;
use App\Support\RoleAccess;
use App\Support\TemplateMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiActionExecutor
{
    private FileStore $projects;
    private FileStore $clients;
    private FileStore $users;
    private FileStore $settings;
    private FileStore $meetings;
    private FileStore $quotes;
    private FileStore $contracts;
    private FileStore $personalNotes;
    private FileStore $actionLogs;
    private FileStore $undoActions;
    private TimelineStore $timeline;
    private array $context = [];

    public function __construct()
    {
        $this->projects = new FileStore('proyectos.json');
        $this->clients = new FileStore('clientes.json');
        $this->users = new FileStore('users.json');
        $this->settings = new FileStore('settings.json');
        $this->meetings = new FileStore('reuniones.json');
        $this->quotes = new FileStore('cotizaciones.json');
        $this->contracts = new FileStore('contratos.json');
        $this->personalNotes = new FileStore('mis_notas.json');
        $this->actionLogs = new FileStore('ai_action_logs.json');
        $this->undoActions = new FileStore('ai_undo_actions.json');
        $this->timeline = new TimelineStore();
    }

    public function execute(string $proposal, array $context = []): array
    {
        $this->context = $context;
        $text = trim($proposal);
        if ($text === '') {
            return $this->failure('No encontré una propuesta para ejecutar. Pídeme que prepare primero el proyecto con sus tareas.');
        }

        $normalized = Str::lower(Str::ascii($text));
        $intent = $this->resolveProposalIntent($text, $normalized);

        if ($intent === 'note_update') {
            return $this->withActionLog($this->updatePersonalNote($text), 'nota personal');
        }

        if ($intent === 'note_create') {
            return $this->withActionLog($this->createPersonalNote($text), 'nota personal');
        }

        if ($intent === 'email') {
            return $this->withActionLog($this->sendEmail($text), 'correo');
        }

        if ($intent === 'reminder') {
            return $this->withActionLog($this->createReminderAction($text), 'recordatorio');
        }

        if ($intent === 'meeting') {
            return $this->withActionLog($this->createMeeting($text), 'reunión');
        }

        if ($intent === 'quote') {
            return $this->withActionLog($this->createQuote($text), 'cotización');
        }

        if ($intent === 'contract') {
            return $this->withActionLog($this->createContract($text), 'contrato');
        }

        if ($intent === 'recurring_invoice_advance') {
            return $this->withActionLog($this->sendRecurringInvoiceEarly($text), 'factura recurrente');
        }

        if ($intent === 'clarify_note_project') {
            return $this->failure('Antes de continuar: ¿te refieres a actualizar la nota que tienes abierta o quieres crear un proyecto basado en esa nota?');
        }

        if ($intent === 'project_create') {
            return $this->withActionLog($this->createProjectWithTasks($text), 'proyecto');
        }

        if ($intent === 'project_update') {
            return $this->withActionLog($this->updateProject($text), 'proyecto');
        }

        if ($intent === 'project_note') {
            return $this->withActionLog($this->addProjectNote($text), 'nota de proyecto');
        }

        if ($intent === 'subtask') {
            return $this->withActionLog($this->addProjectSubtask($text), 'subtarea');
        }

        if ($intent === 'task') {
            return $this->withActionLog($this->addProjectTask($text), 'tarea');
        }

        if ((str_contains($normalized, 'proyecto') || str_contains($normalized, 'tablero')) && $this->isProjectCreateIntent($normalized)) {
            return $this->withActionLog($this->createProjectWithTasks($text), 'proyecto');
        }

        if ((str_contains($normalized, 'proyecto') || str_contains($normalized, 'tablero') || str_contains($normalized, 'columna')) && $this->isProjectUpdateIntent($normalized)) {
            return $this->withActionLog($this->updateProject($text), 'proyecto');
        }

        if ((str_contains($normalized, 'proyecto') || str_contains($normalized, 'tablero')) && (
            str_contains($normalized, 'nuevo proyecto')
            || str_contains($normalized, 'nuevo tablero')
            || str_contains($normalized, 'proyecto propuesto')
            || str_contains($normalized, 'tablero propuesto')
            || str_contains($normalized, 'tareas sugeridas')
            || str_contains($normalized, 'lista de tareas')
            || str_contains($normalized, 'crear proyecto')
            || str_contains($normalized, 'crear tablero')
        )) {
            return $this->withActionLog($this->createProjectWithTasks($text), 'proyecto');
        }

        if (str_contains($normalized, 'subtarea')) {
            return $this->withActionLog($this->addProjectSubtask($text), 'subtarea');
        }

        if (str_contains($normalized, 'nota')) {
            return $this->withActionLog($this->addProjectNote($text), 'nota de proyecto');
        }

        if (str_contains($normalized, 'tarea')) {
            return $this->withActionLog($this->addProjectTask($text), 'tarea');
        }

        if (str_contains($normalized, 'recordatorio') || str_contains($normalized, 'recordar')) {
            return $this->withActionLog($this->createReminderAction($text), 'recordatorio');
        }

        if (str_contains($normalized, 'proyecto') || str_contains($normalized, 'tablero')) {
            return $this->withActionLog($this->createProjectWithTasks($text), 'proyecto');
        }

        if (str_contains($normalized, 'factura') && str_contains($normalized, 'recurrent')) {
            return $this->withActionLog($this->sendRecurringInvoiceEarly($text), 'factura recurrente');
        }

        if (str_contains($normalized, 'factura')) {
            return $this->failure('Puedo ayudarte a preparar una factura, pero todavía no la crearé automáticamente sin cliente, moneda, items, cantidades, precios y vencimiento confirmados.');
        }

        if (str_contains($normalized, 'gasto')) {
            return $this->failure('Puedo preparar el gasto, pero necesito dejar cerrado el formulario automático de gastos antes de guardarlo en el CRM.');
        }

        return $this->failure('Por ahora puedo ejecutar tableros/proyectos, columnas, tareas, notas, subtareas, recordatorios, reuniones, cotizaciones, contratos y correos desde el botón de confirmación.');
    }

    private function resolveProposalIntent(string $text, string $normalized): ?string
    {
        $forcedIntent = (string) data_get($this->context, 'forced_intent', '');
        if ($forcedIntent === 'note_update' && trim((string) data_get($this->context, 'current_note.id', '')) !== '') {
            return 'note_update';
        }

        $userIntent = $this->resolveUserDirectiveIntent((string) data_get($this->context, 'last_user_message', ''), $normalized);
        if ($userIntent !== null) {
            return $userIntent;
        }

        $hasField = fn (array $labels): bool => trim($this->field($text, $labels)) !== '';
        $hasHeader = function (array $headers) use ($text): bool {
            foreach ($headers as $header) {
                if (preg_match('/(?:^|\R)\s*(?:[-*•]\s*)?\**' . preg_quote($header, '/') . '\**\s*:?\s*(?:\R|$)/iu', $text)) {
                    return true;
                }
            }

            return false;
        };

        $hasNoteUpdateHeader = $hasHeader([
            'Actualizar nota personal',
            'Editar nota personal',
            'Reescribir nota personal',
        ]);

        if (
            $hasNoteUpdateHeader
            || ($hasField(['Nota ID', 'ID']) && $hasField(['Contenido', 'Texto', 'Nueva versión', 'Nueva version', 'Mensaje']))
            || (trim((string) data_get($this->context, 'current_note.id', '')) !== '' && (
                str_contains($normalized, 'aplicar cambios')
                || str_contains($normalized, 'reemplazar la nota')
                || str_contains($normalized, 'actualizar nota')
                || str_contains($normalized, 'editar nota')
                || str_contains($normalized, 'reescribir nota')
            ))
            || (str_contains($normalized, 'mis notas') && (str_contains($normalized, 'aplicar cambios') || str_contains($normalized, 'actualizar nota') || str_contains($normalized, 'editar nota') || str_contains($normalized, 'reescribir nota')))
        ) {
            return 'note_update';
        }

        if (
            $hasHeader(['Nota personal', 'Nota personal propuesta', 'Nueva nota'])
            || (str_contains($normalized, 'mis notas') && $hasField(['Título', 'Titulo', 'Contenido', 'Texto']))
            || str_contains($normalized, 'nota privada')
        ) {
            return 'note_create';
        }

        if (
            $hasHeader(['Correo propuesto', 'Email propuesto', 'E-mail propuesto'])
            || ($hasField(['Para', 'Destinatario']) && $hasField(['Asunto', 'Subject']) && $hasField(['Mensaje', 'Contenido', 'Cuerpo']))
            || (str_contains($normalized, 'listo para enviar') && (str_contains($normalized, 'correo') || str_contains($normalized, 'email') || str_contains($normalized, 'e-mail')))
        ) {
            return 'email';
        }

        if (
            $hasHeader(['Recordatorio', 'Recordatorio propuesto', 'Nuevo recordatorio'])
            || $hasField(['Recordatorio', 'Texto']) && (str_contains($normalized, 'recordatorio') || str_contains($normalized, 'recordar'))
            || str_contains($normalized, 'crear recordatorio')
        ) {
            return 'reminder';
        }

        if ($hasHeader(['Reunión', 'Reunion', 'Reunión propuesta', 'Reunion propuesta']) || (str_contains($normalized, 'programar ahora') && str_contains($normalized, 'reunion'))) {
            return 'meeting';
        }

        if (
            $hasHeader(['Factura recurrente adelantada', 'Enviar factura recurrente', 'Factura recurrente para enviar'])
            || (str_contains($normalized, 'factura recurrente') && (
                str_contains($normalized, 'adelant')
                || str_contains($normalized, 'antes de tiempo')
                || str_contains($normalized, 'enviar hoy')
                || str_contains($normalized, 'enviarla hoy')
            ))
        ) {
            return 'recurring_invoice_advance';
        }

        if ($hasHeader(['Cotización propuesta', 'Cotizacion propuesta']) || str_contains($normalized, 'cotizacion propuesta')) {
            return 'quote';
        }

        if ($hasHeader(['Contrato', 'Contrato propuesto']) || str_contains($normalized, 'contrato propuesto')) {
            return 'contract';
        }

        return null;
    }

    private function resolveUserDirectiveIntent(string $userText, string $proposalNormalized): ?string
    {
        $directive = Str::lower(Str::ascii(trim($userText)));
        if ($directive === '') {
            return null;
        }

        $hasCurrentNote = trim((string) data_get($this->context, 'current_note.id', '')) !== '';
        $hasCurrentProject = trim((string) data_get($this->context, 'current_project.id', '')) !== '';

        $hasNote = preg_match('/\bnota(s)?\b/u', $directive) === 1;
        $hasProject = preg_match('/\b(proyecto(s)?|tablero(s)?|kanban)\b/u', $directive) === 1;
        $hasColumn = preg_match('/\b(columna(s)?|lista(s)?)\b/u', $directive) === 1;
        $hasTask = preg_match('/\btarea(s)?\b/u', $directive) === 1;
        $hasReminder = preg_match('/\b(recordatorio(s)?|recordar|recuerdame|recu[eé]rdame|reminder)\b/u', $directive) === 1;
        $hasEmail = preg_match('/\b(correo|email|e-mail)\b/u', $directive) === 1;
        $hasInvoice = preg_match('/\b(factura|facturas|invoice)\b/u', $directive) === 1;
        $hasRecurringInvoice = $hasInvoice && preg_match('/\b(recurrente|recurrentes|recurrencia|recurring)\b/u', $directive) === 1;
        $hasMeeting = preg_match('/\b(reunion|meeting|reuniones)\b/u', $directive) === 1;
        $hasQuote = preg_match('/\b(cotizacion|quote)\b/u', $directive) === 1;
        $hasContract = preg_match('/\b(contrato|contract)\b/u', $directive) === 1;
        $hasCreate = preg_match('/\b(crea|crear|creame|haz|hacer|genera|generar|prepara|preparar|nuevo|nueva)\b/u', $directive) === 1;
        $hasUpdate = preg_match('/\b(actualiza|actualizar|edita|editar|cambia|cambiar|modifica|modificar|agrega|agregar|anade|anadir|añade|añadir|pon|poner|vacia|vaciar|borra|borrar|reemplaza|reemplazar)\b/u', $directive) === 1;
        $hasSend = preg_match('/\b(envia|enviar|manda|mandar|redacta|redactar|escribe|escribir)\b/u', $directive) === 1;
        $hasCurrentNoteReference = str_contains($directive, 'esta nota')
            || str_contains($directive, 'la nota')
            || str_contains($directive, 'nota abierta')
            || str_contains($directive, 'nota que tengo abierta')
            || str_contains($directive, 'nota que tienes abierta');
        $hasProjectNotePhrase = preg_match('/\bnota(s)?\s+(en|al|del|para)\s+(este\s+|esta\s+|el\s+|la\s+|un\s+|una\s+)?(proyecto|tarea)\b/u', $directive) === 1
            || preg_match('/\b(en|al|del|para)\s+(este\s+|esta\s+|el\s+|la\s+|un\s+|una\s+)?(proyecto|tarea)\b.*\bnota(s)?\b/u', $directive) === 1
            || preg_match('/\bnota(s)?\s+de\s+proyecto\b/u', $directive) === 1;
        $firstPosition = function (array $needles) use ($directive): ?int {
            $positions = [];
            foreach ($needles as $needle) {
                $pos = mb_strpos($directive, $needle);
                if ($pos !== false) {
                    $positions[] = $pos;
                }
            }

            return $positions ? min($positions) : null;
        };
        $notePos = $firstPosition(['nota', 'notas']);
        $emailPos = $firstPosition(['correo', 'email', 'e-mail']);
        $projectPos = $firstPosition(['proyecto', 'proyectos', 'tablero', 'tableros', 'kanban']);
        $taskPos = $firstPosition(['tarea', 'tareas', 'subtarea', 'subtareas']);
        $meetingPos = $firstPosition(['reunion', 'reuniones', 'meeting']);

        if ($hasCurrentNote && $hasCurrentNoteReference && $hasProject && ($hasCreate || str_contains($directive, 'proyecto'))) {
            return 'clarify_note_project';
        }

        if ($hasRecurringInvoice && ($hasSend || str_contains($directive, 'adelant') || str_contains($directive, 'antes de tiempo') || str_contains($directive, 'hoy'))) {
            return 'recurring_invoice_advance';
        }

        if ($hasEmail && $hasSend && ($notePos === null || ($emailPos !== null && $emailPos < $notePos))) {
            return 'email';
        }

        if ($hasMeeting && ($hasCreate || preg_match('/\b(programa|programar|agenda|agendar)\b/u', $directive) === 1) && ($notePos === null || ($meetingPos !== null && $meetingPos < $notePos))) {
            return 'meeting';
        }

        if ($hasReminder && ($hasCreate || $hasUpdate || preg_match('/\b(recordar|recuerdame|recu[eé]rdame)\b/u', $directive) === 1)) {
            return 'reminder';
        }

        if ($hasProject && $hasCreate && ! $hasProjectNotePhrase && ($notePos === null || ($projectPos !== null && $projectPos < $notePos))) {
            return 'project_create';
        }

        if ($hasColumn && ($hasCreate || $hasUpdate) && $hasCurrentProject) {
            return 'project_update';
        }

        if (preg_match('/\bsubtarea(s)?\b/u', $directive) === 1 && ($hasCreate || $hasUpdate) && ! $hasProjectNotePhrase && ($notePos === null || ($taskPos !== null && $taskPos < $notePos))) {
            return 'subtask';
        }

        if ($hasTask && ($hasCreate || $hasUpdate) && ! $hasProjectNotePhrase && ($notePos === null || ($taskPos !== null && $taskPos < $notePos))) {
            return 'task';
        }

        if ($hasNote && $hasProjectNotePhrase) {
            return 'project_note';
        }

        if ($hasNote) {
            if ($hasCurrentNote && ($hasUpdate || str_contains($directive, 'esta nota') || str_contains($directive, 'la nota'))) {
                return 'note_update';
            }

            if (str_contains($directive, 'mis notas') || str_contains($directive, 'nota personal') || $hasCreate || $hasUpdate) {
                return $hasCurrentNote && $hasUpdate ? 'note_update' : 'note_create';
            }

            return 'note_create';
        }

        if ($hasEmail && ($hasSend || str_contains($directive, 'correo para') || str_contains($directive, 'email para'))) {
            return 'email';
        }

        if ($hasQuote && $hasCreate) {
            return 'quote';
        }

        if ($hasContract && $hasCreate) {
            return 'contract';
        }

        if ($hasProject) {
            if ($hasCreate || preg_match('/\bnuevo\s+(proyecto|tablero)\b/u', $directive) === 1) {
                return 'project_create';
            }

            if ($hasUpdate || $hasCurrentProject) {
                return 'project_update';
            }
        }

        return null;
    }

    private function createReminderAction(string $proposal): array
    {
        $text = $this->stripMarkdown($this->field($proposal, ['Texto', 'Recordatorio', 'Título', 'Titulo', 'Descripción', 'Descripcion']));
        if ($text === '') {
            if (preg_match('/(?:^|\R)\s*(?:[-*•]\s*)?\**(?:Recordatorio propuesto|Nuevo recordatorio|Recordatorio)\**\s*:?\s*(.+?)(?=\R|$)/iu', $proposal, $match)) {
                $text = $this->stripMarkdown((string) ($match[1] ?? ''));
            }
        }
        if ($text === '') {
            $body = $this->extractBody($proposal);
            $text = trim(preg_split('/\R/u', $body)[0] ?? '');
        }
        if ($text === '') {
            return $this->failure('No encontré el texto del recordatorio. Incluye una línea "Texto: ..." para crearlo.');
        }

        $project = $this->projectFromProposalOrContext($proposal, ['Proyecto', 'En proyecto']);
        $taskName = $this->stripMarkdown($this->field($proposal, ['Tarea', 'Enlazar tarea', 'Tarea vinculada']));
        $task = null;

        if ($project && $taskName !== '') {
            $task = $this->matchTask($project, $taskName);
        }

        if (!$task && $project) {
            $contextTaskId = trim((string) data_get($this->context, 'task_id', ''));
            if ($contextTaskId !== '') {
                $task = collect($project['tareas'] ?? [])->first(fn ($item) => (string) ($item['id'] ?? $item['uid'] ?? '') === $contextTaskId);
            }
        }

        if (!$task && $taskName !== '') {
            foreach ($this->projects->all() as $candidateProject) {
                if (! $this->canUseProject($candidateProject)) {
                    continue;
                }
                $candidateTask = $this->matchTask($candidateProject, $taskName);
                if ($candidateTask) {
                    $project = $candidateProject;
                    $task = $candidateTask;
                    break;
                }
            }
        }

        $dateRaw = $this->stripMarkdown($this->field($proposal, ['Fecha', 'Vencimiento', 'Fecha de vencimiento', 'Para fecha']));
        $date = $this->parseDate($dateRaw);
        $dateWantsDue = $dateRaw === '' || str_contains(Str::lower(Str::ascii($dateRaw)), 'vencimiento');
        if (!$date && $dateWantsDue) {
            $date = $this->taskDueDate($task) ?: $this->parseDate((string) ($project['vencimiento'] ?? ''));
        }

        $priorityRaw = $this->stripMarkdown($this->field($proposal, ['Prioridad']));
        $priority = $priorityRaw !== '' ? $this->normalizePriority($priorityRaw) : '';
        $sectionTitle = $this->stripMarkdown($this->field($proposal, ['Lista', 'Sección', 'Seccion', 'Categoría', 'Categoria']));

        $link = null;
        if ($task && $project) {
            $taskId = (string) ($task['id'] ?? $task['uid'] ?? '');
            if ($taskId !== '') {
                $link = [
                    'type' => 'task',
                    'id' => $taskId,
                    'title' => (string) ($task['title'] ?? $task['texto'] ?? $task['nombre'] ?? 'Tarea'),
                    'subtitle' => (string) ($project['titulo'] ?? 'Proyecto'),
                    'projectId' => (string) ($project['id'] ?? ''),
                ];
            }
        }

        if (!$link && $project) {
            $projectId = (string) ($project['id'] ?? '');
            if ($projectId !== '') {
                $link = [
                    'type' => 'project',
                    'id' => $projectId,
                    'title' => (string) ($project['titulo'] ?? 'Proyecto'),
                    'subtitle' => 'Proyecto',
                    'projectId' => $projectId,
                ];
            }
        }

        $details = [
            '- **Recordatorio:** ' . $text,
        ];
        if ($priority !== '') $details[] = '- **Prioridad:** ' . $priority;
        if ($date) $details[] = '- **Fecha:** ' . $date;
        if ($task) $details[] = '- **Tarea:** ' . (string) ($task['texto'] ?? $task['title'] ?? 'Tarea');
        if ($project) $details[] = '- **Proyecto:** ' . (string) ($project['titulo'] ?? 'Proyecto');

        return [
            'ok' => true,
            'content' => "✅ **Recordatorio creado en tu modal**\n\n" . implode("\n", $details),
            'url' => null,
            'project_id' => $project['id'] ?? null,
            'reminder_action' => [
                'text' => Str::limit($text, 220, ''),
                'priority' => $priority,
                'dueDate' => $date,
                'sectionTitle' => $sectionTitle,
                'project' => $project ? [
                    'id' => (string) ($project['id'] ?? ''),
                    'title' => (string) ($project['titulo'] ?? 'Proyecto'),
                ] : null,
                'task' => $task ? [
                    'id' => (string) ($task['id'] ?? $task['uid'] ?? ''),
                    'title' => (string) ($task['title'] ?? $task['texto'] ?? $task['nombre'] ?? 'Tarea'),
                    'projectId' => (string) ($project['id'] ?? ''),
                ] : null,
                'link' => $link,
            ],
        ];
    }

    private function sendEmail(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'correo.create')) {
            return $this->failure('Tu usuario no tiene permiso para enviar correos. Pide a un administrador activar Correo > Crear en tu rol.');
        }

        $draft = $this->parseEmailDraft($proposal);
        if (empty($draft['to'])) {
            return $this->failure('No encontré un correo válido de destino. Indícame el cliente o el email exacto antes de enviarlo.');
        }
        if ($draft['subject'] === '') {
            return $this->failure('Falta el asunto del correo. Escríbeme el asunto o pídeme generar uno.');
        }
        if ($draft['body'] === '') {
            return $this->failure('Falta el cuerpo del correo. Escríbeme el mensaje que quieres enviar.');
        }

        try {
            TemplateMail::send($draft['to'], $draft['subject'], $draft['body'], [
                'source' => 'ai_email',
                'sent_by' => (string) (Auth::user()->email ?? session('user.email') ?? 'sistema'),
                'sent_by_name' => (string) (Auth::user()->name ?? session('user.name') ?? 'Sistema'),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return $this->failure('Intenté enviar el correo, pero falló el SMTP. Revisa la configuración de correo o intenta de nuevo.');
        }

        $toText = implode(', ', $draft['to']);
        return [
            'ok' => true,
            'content' => "✅ **Correo enviado correctamente**\n\n- **Para:** {$toText}\n- **Asunto:** {$draft['subject']}\n\nQuedó registrado en el historial de correos.",
            'url' => '/correo',
        ];
    }

    private function createProjectWithTasks(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.create')) {
            return $this->failure('Tu usuario no tiene permiso para crear proyectos. Pide a un administrador activar Proyectos > Crear en tu rol.');
        }

        $draft = $this->parseProjectDraft($proposal);
        if ($draft['title'] === '') {
            return $this->failure('No pude identificar el nombre del tablero/proyecto. Escríbeme el nombre y las tareas para crearlo.');
        }

        $settings = $this->settings->find('settings') ?: [];
        $stages = array_values(array_filter($settings['project_stages'] ?? []));
        $fallbackStage = (string) ($stages[0] ?? 'INICIO');
        $stage = $this->normalizeStage($draft['stage'], $stages, $fallbackStage);
        $taskStages = $draft['columns'];
        if (empty($taskStages)) {
            $taskStages = ['Por hacer', 'En proceso', 'Revisión', 'Terminado'];
        }
        $priority = $this->normalizePriority($draft['priority']);
        $client = $this->matchClient($draft['client']);
        $responsibles = $this->matchUsers($draft['responsibles']);
        $start = $this->parseDate($draft['start_date']) ?: now(config('app.timezone'))->toDateString();
        $due = $this->parseDate($draft['due_date']);

        $project = $this->projects->create([
            'cliente_id' => $client['id'] ?? 'general',
            'cliente' => $client['name'] ?? 'Sin Cliente',
            'titulo' => $draft['title'],
            'etapa' => $stage,
            'prioridad' => $priority,
            'valor' => 0,
            'progreso' => 0,
            'planned_seconds' => 0,
            'vencimiento' => $due,
            'inicio' => $start,
            'miembro' => $responsibles['names'][0] ?? null,
            'responsables' => $responsibles['names'],
            'responsable_ids' => $responsibles['ids'],
            'siguiente' => null,
            'descripcion' => $draft['description'] ?: null,
            'task_stages' => $taskStages,
            'tareas' => [],
            'time_logs' => [],
        ]);

        $tasks = [];
        foreach ($draft['tasks'] as $taskDraft) {
            $taskText = is_array($taskDraft) ? (string) ($taskDraft['text'] ?? '') : (string) $taskDraft;
            $taskNote = is_array($taskDraft) ? trim((string) ($taskDraft['note'] ?? '')) : '';
            $taskStage = is_array($taskDraft) ? trim((string) ($taskDraft['stage'] ?? '')) : '';
            $taskText = $this->normalizeTaskTitle($taskText);
            if ($taskText === '') {
                continue;
            }

            $task = $this->taskPayload($taskText, $start, $due, $priority, $responsibles, $this->normalizeTaskStage($taskStage, $taskStages), count($tasks));
            if ($taskNote !== '') {
                $task['notes'][] = $this->notePayload($taskNote);
            }
            $tasks[] = $task;
        }

        $project = $this->projects->update((string) $project['id'], ['tareas' => $tasks]) ?: $project;

        if (!empty($project['cliente_id']) && $project['cliente_id'] !== 'general') {
            $this->timeline->add((string) $project['cliente_id'], 'proyecto', [
                'id' => $project['id'],
                'titulo' => $project['titulo'],
                'etapa' => $project['etapa'],
            ]);
        }

        $taskLine = count($tasks) === 1 ? '1 tarea agregada' : count($tasks) . ' tareas agregadas';
        $columnLine = count($taskStages) === 1 ? '1 columna' : count($taskStages) . ' columnas';
        $url = '/proyectos?open_project=' . rawurlencode((string) $project['id']);

        return [
            'ok' => true,
            'content' => "✅ **Tablero creado realmente en el CRM:**\n\n- **Nombre:** {$project['titulo']}\n- **Estado:** {$project['etapa']}\n- **Prioridad:** {$project['prioridad']}\n- **Columnas Kanban:** {$columnLine}\n- **Tareas:** {$taskLine}\n\n[Abrir tablero]({$url})",
            'url' => $url,
            'project_id' => $project['id'],
            'undo_action' => $this->deleteUndo('proyectos', (string) $project['id'], 'Deshacer creación del proyecto'),
        ];
    }

    private function updateProject(string $proposal): array
    {
        $canUpdate = RoleAccess::can(Auth::user(), 'proyectos.update');
        $canCreate = RoleAccess::can(Auth::user(), 'proyectos.create');
        if (! $canUpdate && ! $canCreate) {
            return $this->failure('Tu usuario no tiene permiso para modificar proyectos.');
        }

        $project = $this->projectFromProposalOrContext($proposal, ['Proyecto', 'Nombre']);
        if (!$project) {
            return $this->failure('No encontré el proyecto exacto para actualizar. Incluye una línea "Proyecto: nombre".');
        }
        $projectBefore = $project;

        $updates = [];
        $title = $this->stripMarkdown($this->field($proposal, ['Nuevo nombre', 'Titulo nuevo', 'Título nuevo']));
        $description = $this->stripMarkdown($this->field($proposal, ['Descripción', 'Descripcion', 'Nueva descripción', 'Nueva descripcion']));
        $stage = $this->stripMarkdown($this->field($proposal, ['Estado', 'Etapa']));
        $priority = $this->stripMarkdown($this->field($proposal, ['Prioridad']));
        $due = $this->parseDate($this->field($proposal, ['Vencimiento', 'Fecha fin', 'Fecha de entrega']));
        $taskStages = $this->projectTaskStages($project);
        $columns = $this->extractColumns($proposal);
        $renameColumnFrom = $this->stripMarkdown($this->field($proposal, ['Columna actual', 'Columna anterior', 'Renombrar columna', 'Lista actual']));
        $renameColumnTo = $this->stripMarkdown($this->field($proposal, ['Nueva columna', 'Nuevo nombre de columna', 'Nuevo nombre', 'Lista nueva']));

        if ($title !== '') $updates['titulo'] = Str::limit($title, 140, '');
        if ($description !== '') $updates['descripcion'] = $description;
        if ($stage !== '') $updates['etapa'] = $stage;
        if ($priority !== '') $updates['prioridad'] = $this->normalizePriority($priority);
        if ($due) $updates['vencimiento'] = $due;
        if ($renameColumnFrom !== '' && $renameColumnTo !== '') {
            $nextStages = array_map(
                fn ($item) => Str::lower(Str::ascii($item)) === Str::lower(Str::ascii($renameColumnFrom))
                    ? Str::limit($renameColumnTo, 60, '')
                    : $item,
                $taskStages
            );
            $updates['task_stages'] = array_values(array_unique(array_filter($nextStages)));
        } elseif (!empty($columns)) {
            $updates['task_stages'] = array_values(array_unique(array_merge($taskStages, $columns)));
        }

        $responsibles = $this->matchUsers($this->splitPeople($this->field($proposal, ['Responsables', 'Asignados', 'Encargados'])));
        if (!empty($responsibles['names'])) {
            $updates['responsables'] = $responsibles['names'];
            $updates['responsable_ids'] = $responsibles['ids'];
            $updates['miembro'] = $responsibles['names'][0] ?? null;
        }

        if (!$canUpdate && !empty($updates)) {
            return $this->failure('Tu usuario puede agregar contenido al proyecto, pero no actualizar descripción, estado, prioridad, vencimiento o responsables.');
        }

        $tasks = array_values($project['tareas'] ?? []);
        if ($renameColumnFrom !== '' && $renameColumnTo !== '') {
            foreach ($tasks as &$task) {
                if (Str::lower(Str::ascii((string) ($task['board_stage'] ?? ''))) === Str::lower(Str::ascii($renameColumnFrom))) {
                    $task['board_stage'] = Str::limit($renameColumnTo, 60, '');
                }
            }
            unset($task);
        }
        $addedTasks = 0;
        $addedNotes = 0;
        $addedSubtasks = 0;

        $taskItems = $this->extractListAfter($proposal, ['Tareas a agregar', 'Tareas sugeridas', 'Tareas', 'Lista de tareas']);
        $singleTask = $this->stripMarkdown($this->field($proposal, ['Tarea nueva', 'Nueva tarea']));
        if ($singleTask !== '') {
            array_unshift($taskItems, $singleTask);
        }

        if (!empty($taskItems)) {
            if (!$canCreate) {
                return $this->failure('Tu usuario no tiene permiso para agregar tareas al proyecto.');
            }

            $taskDue = $this->parseDate($this->field($proposal, ['Vencimiento', 'Fecha fin', 'Fecha de entrega']));
            $taskStart = $this->parseDate($this->field($proposal, ['Fecha inicio', 'Inicio']));
            $taskPriority = $priority !== '' ? $this->normalizePriority($priority) : (string) ($project['prioridad'] ?? 'Atención');
            $explicitTaskStage = $this->stripMarkdown($this->field($proposal, ['Columna', 'Lista', 'Estado de tarea']));
            $activeTaskStages = $updates['task_stages'] ?? $taskStages;
            foreach (array_values(array_unique(array_filter($taskItems))) as $taskText) {
                $taskText = trim((string) $taskText);
                if ($taskText === '') {
                    continue;
                }

                $parsedTask = $this->taskLineWithStage($taskText, $activeTaskStages);
                $taskStage = $parsedTask['stage'] ?: $explicitTaskStage;
                $tasks[] = $this->taskPayload($parsedTask['text'], $taskStart, $taskDue, $taskPriority, $responsibles, $this->normalizeTaskStage($taskStage, $activeTaskStages), count($tasks));
                $addedTasks++;
            }
        }

        $taskName = $this->field($proposal, ['Tarea', 'Tarea principal']);
        $targetTask = $this->matchTask(['tareas' => $tasks], $taskName);
        $noteText = $this->stripMarkdown($this->field($proposal, ['Nota', 'Nota a agregar', 'Contenido']));
        if ($noteText !== '') {
            if (!$canCreate) {
                return $this->failure('Tu usuario no tiene permiso para agregar notas al proyecto.');
            }
            if (!$targetTask) {
                $tasks[] = $this->taskPayload('Notas generales', null, null, (string) ($project['prioridad'] ?? 'Atención'), $responsibles);
                $targetTask = end($tasks);
            }
            foreach ($tasks as &$task) {
                if ((string) ($task['id'] ?? '') !== (string) ($targetTask['id'] ?? '')) {
                    continue;
                }
                $notes = array_values($task['notes'] ?? []);
                $notes[] = [
                    'id' => (string) Str::ulid(),
                    'texto' => $noteText,
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => null,
                    'author_name' => (string) (Auth::user()->name ?? 'Infocus AI'),
                    'author_id' => Auth::user()?->id,
                ];
                $task['notes'] = $notes;
                $addedNotes++;
                break;
            }
            unset($task);
        }

        $subtaskItems = $this->extractListAfter($proposal, ['Subtareas a agregar', 'Subtareas']);
        $singleSubtask = $this->stripMarkdown($this->field($proposal, ['Subtarea', 'Nueva subtarea']));
        if ($singleSubtask !== '') {
            array_unshift($subtaskItems, $singleSubtask);
        }
        if (!empty($subtaskItems)) {
            if (!$canCreate) {
                return $this->failure('Tu usuario no tiene permiso para agregar subtareas.');
            }
            if (!$targetTask) {
                return $this->failure('Para agregar subtareas necesito identificar la tarea principal. Incluye una línea "Tarea: nombre".');
            }
            foreach ($tasks as &$task) {
                if ((string) ($task['id'] ?? '') !== (string) ($targetTask['id'] ?? '')) {
                    continue;
                }
                $subtasks = array_values($task['subtasks'] ?? []);
                foreach (array_values(array_unique(array_filter($subtaskItems))) as $subtaskText) {
                    $subtasks[] = [
                        'id' => (string) Str::ulid(),
                        'texto' => Str::limit(trim((string) $subtaskText), 180, ''),
                        'done' => false,
                    ];
                    $addedSubtasks++;
                }
                $task['subtasks'] = $subtasks;
                break;
            }
            unset($task);
        }

        if ($addedTasks || $addedNotes || $addedSubtasks || ($renameColumnFrom !== '' && $renameColumnTo !== '')) {
            $updates['tareas'] = $tasks;
        }

        if (empty($updates)) {
            return $this->failure('No encontré cambios para aplicar. Puedes indicar descripción, responsables, tareas, notas o subtareas.');
        }

        $updated = $this->projects->update((string) $project['id'], $updates) ?: $project;
        $url = '/proyectos?open_project=' . rawurlencode((string) $updated['id']);
        $changes = [];
        if ($description !== '') $changes[] = 'descripción actualizada';
        if (!empty($responsibles['names'])) $changes[] = 'responsables actualizados';
        if ($stage !== '' || $priority !== '' || $due) $changes[] = 'datos del proyecto actualizados';
        if ($renameColumnFrom !== '' && $renameColumnTo !== '') $changes[] = 'columna renombrada';
        elseif (!empty($columns)) $changes[] = count($columns) === 1 ? '1 columna agregada' : count($columns) . ' columnas agregadas';
        if ($addedTasks) $changes[] = $addedTasks === 1 ? '1 tarea agregada' : "{$addedTasks} tareas agregadas";
        if ($addedNotes) $changes[] = $addedNotes === 1 ? '1 nota agregada' : "{$addedNotes} notas agregadas";
        if ($addedSubtasks) $changes[] = $addedSubtasks === 1 ? '1 subtarea agregada' : "{$addedSubtasks} subtareas agregadas";

        return [
            'ok' => true,
            'content' => "✅ **Cambios aplicados al proyecto**\n\n- **Proyecto:** " . ($updated['titulo'] ?? 'Proyecto') . "\n- **Cambios:** " . implode(', ', $changes) . "\n\n[Abrir proyecto]({$url})",
            'url' => $url,
            'project_id' => $updated['id'] ?? null,
            'undo_action' => $this->restoreUndo('proyectos', (string) ($updated['id'] ?? $project['id']), $projectBefore, 'Deshacer cambios del proyecto'),
        ];
    }

    private function addProjectTask(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.create')) {
            return $this->failure('Tu usuario no tiene permiso para agregar tareas.');
        }

        $project = $this->projectFromProposalOrContext($proposal);
        if (!$project) {
            return $this->failure('No encontré el proyecto. Incluye una línea "Proyecto: nombre".');
        }
        $projectBefore = $project;

        $taskItems = $this->extractListAfter($proposal, ['Tareas a agregar', 'Tareas sugeridas', 'Tareas', 'Lista de tareas']);
        $taskText = $this->stripMarkdown($this->field($proposal, ['Tarea', 'Nombre', 'Texto']));
        if ($taskText !== '') {
            array_unshift($taskItems, $taskText);
        }
        $taskItems = array_values(array_unique(array_filter(array_map(fn ($item) => $this->normalizeTaskTitle((string) $item), $taskItems))));
        if (empty($taskItems)) {
            return $this->failure('No encontré el texto de la tarea para agregar.');
        }

        $responsibles = $this->matchUsers($this->splitPeople($this->field($proposal, ['Responsables', 'Asignados', 'Encargados'])));
        $due = $this->parseDate($this->field($proposal, ['Vencimiento', 'Fecha fin', 'Fecha de entrega']));
        $start = $this->parseDate($this->field($proposal, ['Fecha inicio', 'Inicio']));
        $priority = $this->normalizePriority($this->field($proposal, ['Prioridad']));
        $taskStages = $this->projectTaskStages($project);
        $explicitTaskStage = $this->stripMarkdown($this->field($proposal, ['Columna', 'Lista', 'Estado de tarea']));
        $tasks = array_values($project['tareas'] ?? []);
        foreach ($taskItems as $item) {
            $parsedTask = $this->taskLineWithStage($item, $taskStages);
            $taskStage = $parsedTask['stage'] ?: $explicitTaskStage;
            $tasks[] = $this->taskPayload($parsedTask['text'], $start, $due, $priority, $responsibles, $this->normalizeTaskStage($taskStage, $taskStages), count($tasks));
        }

        $updated = $this->projects->update((string) $project['id'], ['tareas' => $tasks]) ?: $project;
        $url = '/proyectos?open_project=' . rawurlencode((string) $project['id']) . '&view=tareas';
        $taskLine = count($taskItems) === 1 ? $taskItems[0] : count($taskItems) . ' tareas';

        return [
            'ok' => true,
            'content' => "✅ **Tareas agregadas**\n\n- **Proyecto:** " . ($updated['titulo'] ?? 'Proyecto') . "\n- **Tareas:** {$taskLine}\n\n[Abrir proyecto]({$url})",
            'url' => $url,
            'project_id' => $project['id'] ?? null,
            'undo_action' => $this->restoreUndo('proyectos', (string) ($project['id'] ?? ''), $projectBefore, 'Deshacer tareas agregadas'),
        ];
    }

    private function addProjectNote(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.create')) {
            return $this->failure('Tu usuario no tiene permiso para agregar notas.');
        }

        $project = $this->projectFromProposalOrContext($proposal);
        if (!$project) {
            return $this->failure('No encontré el proyecto. Incluye una línea "Proyecto: nombre".');
        }
        $projectBefore = $project;

        $noteText = $this->stripMarkdown($this->field($proposal, ['Nota', 'Texto', 'Contenido']));
        if ($noteText === '') {
            return $this->failure('No encontré el contenido de la nota.');
        }

        $task = $this->matchTask($project, $this->field($proposal, ['Tarea']));
        $tasks = array_values($project['tareas'] ?? []);
        if (!$task && empty($tasks)) {
            $tasks[] = [
                'id' => (string) Str::ulid(),
                'texto' => 'Notas generales',
                'done' => false,
                'subtasks' => [],
                'notes' => [],
                'total_seconds' => 0,
            ];
            $task = $tasks[0];
        }

        foreach ($tasks as &$item) {
            if ((string) ($item['id'] ?? '') !== (string) ($task['id'] ?? '')) continue;
            $notes = array_values($item['notes'] ?? []);
            $notes[] = [
                'id' => (string) Str::ulid(),
                'texto' => $noteText,
                'created_at' => now()->toISOString(),
                'created_by' => (string) (Auth::user()->name ?? 'Usuario'),
            ];
            $item['notes'] = $notes;
            break;
        }
        unset($item);

        $this->projects->update((string) $project['id'], ['tareas' => $tasks]);
        $url = '/proyectos?open_project=' . rawurlencode((string) $project['id']);

        return [
            'ok' => true,
            'content' => "✅ **Nota agregada**\n\n- **Proyecto:** " . ($project['titulo'] ?? 'Proyecto') . "\n- **Nota:** {$noteText}\n\n[Abrir proyecto]({$url})",
            'url' => $url,
            'project_id' => $project['id'] ?? null,
            'undo_action' => $this->restoreUndo('proyectos', (string) ($project['id'] ?? ''), $projectBefore, 'Deshacer nota agregada'),
        ];
    }

    private function addProjectSubtask(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.create')) {
            return $this->failure('Tu usuario no tiene permiso para agregar subtareas.');
        }

        $project = $this->projectFromProposalOrContext($proposal);
        if (!$project) {
            return $this->failure('No encontré el proyecto. Incluye una línea "Proyecto: nombre".');
        }
        $projectBefore = $project;

        $task = $this->matchTask($project, $this->field($proposal, ['Tarea']));
        if (!$task) {
            return $this->failure('No encontré la tarea principal. Incluye una línea "Tarea: nombre".');
        }

        $subtaskText = $this->stripMarkdown($this->field($proposal, ['Subtarea', 'Nombre', 'Texto']));
        if ($subtaskText === '') {
            return $this->failure('No encontré el texto de la subtarea.');
        }

        $tasks = array_values($project['tareas'] ?? []);
        foreach ($tasks as &$item) {
            if ((string) ($item['id'] ?? '') !== (string) ($task['id'] ?? '')) continue;
            $subtasks = array_values($item['subtasks'] ?? []);
            $subtasks[] = ['id' => (string) Str::ulid(), 'texto' => $subtaskText, 'done' => false];
            $item['subtasks'] = $subtasks;
            break;
        }
        unset($item);

        $this->projects->update((string) $project['id'], ['tareas' => $tasks]);
        $url = '/proyectos?open_project=' . rawurlencode((string) $project['id']);

        return [
            'ok' => true,
            'content' => "✅ **Subtarea agregada**\n\n- **Proyecto:** " . ($project['titulo'] ?? 'Proyecto') . "\n- **Subtarea:** {$subtaskText}\n\n[Abrir proyecto]({$url})",
            'url' => $url,
            'project_id' => $project['id'] ?? null,
            'undo_action' => $this->restoreUndo('proyectos', (string) ($project['id'] ?? ''), $projectBefore, 'Deshacer subtarea agregada'),
        ];
    }

    private function createPersonalNote(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'mis-notas.read')) {
            return $this->failure('Tu usuario no tiene permiso para usar Mis Notas.');
        }

        $normalized = Str::lower(Str::ascii($proposal));
        if (
            trim((string) data_get($this->context, 'current_note.id', '')) !== ''
            && (
                (string) data_get($this->context, 'forced_intent', '') === 'note_update'
                || str_contains($normalized, 'aplicar cambios')
                || str_contains($normalized, 'reemplazar la nota')
                || str_contains($normalized, 'actualizar nota')
                || str_contains($normalized, 'editar nota')
                || str_contains($normalized, 'reescribir nota')
            )
        ) {
            return $this->updatePersonalNote($proposal);
        }

        $title = $this->stripMarkdown($this->field($proposal, ['Título', 'Titulo', 'Nota', 'Nombre']));
        $plain = $this->extractBody($proposal);
        if ($plain === '') {
            $plain = $this->stripMarkdown($this->field($proposal, ['Contenido', 'Texto', 'Descripción', 'Descripcion']));
        }
        if ($plain === '' && $title !== '') {
            $plain = $title;
        }
        $plain = $this->cleanPersonalNotePlain($plain);
        if ($plain === '') {
            return $this->failure('No encontré el contenido de la nota personal.');
        }

        if ($title === '') {
            $title = Str::limit(trim((string) preg_split('/\R/', $plain)[0]), 80, '');
        }
        if ($title === '') {
            $title = 'Nota sin titulo';
        }

        $color = $this->normalizeNoteColor($this->field($proposal, ['Color']));
        $client = $this->clientFromPersonalNoteProposal($proposal) ?? $this->matchClient('');
        $nowMs = (int) floor(microtime(true) * 1000);
        $ownerKey = (string) (Auth::id() ?? Auth::user()?->email ?? 'anon');
        $ownerName = (string) (Auth::user()?->name ?? 'Usuario');

        $note = $this->personalNotes->create([
            'id' => (string) Str::ulid(),
            'ownerKey' => $ownerKey,
            'ownerName' => $ownerName,
            'title' => Str::limit($title, 160, ''),
            'html' => $this->noteHtmlFromPlain($title, $plain),
            'plainText' => $plain,
            'color' => $color,
            'linkedClient' => ($client['id'] ?? 'general') !== 'general' ? (string) $client['id'] : '',
            'collaborators' => [],
            'createdAt' => $nowMs,
            'updatedAt' => $nowMs,
        ]);

        return [
            'ok' => true,
            'content' => "✅ **Nota creada en Mis Notas**\n\n- **Título:** {$note['title']}\n- **Color:** {$note['color']}\n\n[Abrir Mis Notas](/mis-notas)",
            'url' => '/mis-notas',
            'undo_action' => $this->deleteUndo('mis_notas', (string) $note['id'], 'Deshacer creación de nota'),
        ];
    }

    private function updatePersonalNote(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'mis-notas.read')) {
            return $this->failure('Tu usuario no tiene permiso para usar Mis Notas.');
        }

        $noteId = trim($this->stripMarkdown($this->field($proposal, ['Nota ID', 'ID', 'Nota'])));
        if ($noteId === '' || $this->looksLikeCurrentNotePlaceholder($noteId)) {
            $noteId = trim((string) data_get($this->context, 'current_note.id', ''));
        }
        if ($noteId === '') {
            return $this->failure('No encontré la nota abierta para actualizar. Abre una nota y vuelve a pedirme el cambio.');
        }

        $all = $this->personalNotes->all();
        $index = null;
        foreach ($all as $i => $note) {
            if ((string) ($note['id'] ?? '') === $noteId) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return $this->failure('No encontré esa nota en Mis Notas.');
        }

        $ownerKey = (string) (Auth::id() ?? Auth::user()?->email ?? 'anon');
        if (! $this->canEditPersonalNoteRecord($all[$index], $ownerKey)) {
            return $this->failure('Esta nota es de solo lectura para tu usuario. Puedo sugerir una versión nueva, pero no reemplazarla.');
        }
        $noteBefore = $all[$index];

        $title = $this->stripMarkdown($this->field($proposal, ['Título', 'Titulo', 'Nuevo título', 'Nuevo titulo']));
        $plain = $this->extractBody($proposal);
        if ($plain === '') {
            $plain = $this->stripMarkdown($this->field($proposal, ['Contenido', 'Texto', 'Nueva versión', 'Nueva version', 'Mensaje']));
        }
        if ($plain === '') {
            return $this->failure('No encontré el contenido final para reemplazar la nota.');
        }
        $plain = $this->cleanPersonalNotePlain($plain);
        if ($plain === '') {
            return $this->failure('No encontré el contenido final para reemplazar la nota.');
        }

        if ($title === '') {
            $title = Str::limit(trim((string) preg_split('/\R/', $plain)[0]), 100, '');
        }
        if ($title === '') {
            $title = (string) ($all[$index]['title'] ?? 'Nota sin titulo');
        }

        $html = $this->noteHtmlFromPlain($title, $plain);
        $nowMs = (int) floor(microtime(true) * 1000);
        $client = $this->clientFromPersonalNoteProposal($proposal);
        $all[$index] = [
            ...$all[$index],
            'title' => Str::limit($title, 160, ''),
            'html' => $html,
            'plainText' => $plain,
            'updatedAt' => $nowMs,
        ];
        if ($client !== null) {
            $all[$index]['linkedClient'] = ($client['id'] ?? 'general') !== 'general' ? (string) $client['id'] : '';
        }
        $this->personalNotes->save($all);

        return [
            'ok' => true,
            'content' => "✅ **Nota actualizada en Mis Notas**\n\n- **Título:** " . $all[$index]['title'] . "\n- **Cambios:** contenido reescrito por Infocus AI.",
            'url' => '/mis-notas',
            'note_update' => [
                'id' => (string) $all[$index]['id'],
                'title' => (string) $all[$index]['title'],
                'html' => $html,
                'plainText' => $plain,
                'updatedAt' => $nowMs,
            ],
            'undo_action' => $this->restoreUndo('mis_notas', (string) $all[$index]['id'], $noteBefore, 'Deshacer edición de nota'),
        ];
    }

    private function looksLikeCurrentNotePlaceholder(string $value): bool
    {
        $normalized = Str::lower(Str::ascii(trim($value)));

        return $normalized === ''
            || str_contains($normalized, 'abierta')
            || str_contains($normalized, 'pantalla')
            || str_contains($normalized, 'viendo')
            || str_contains($normalized, 'actual')
            || str_contains($normalized, 'esta nota')
            || str_contains($normalized, 'la que');
    }

    private function clientFromPersonalNoteProposal(string $proposal): ?array
    {
        $clientRaw = $this->field($proposal, ['Cliente', 'Empresa']);
        if (trim($clientRaw) === '') {
            $clientRaw = (string) data_get($this->context, 'last_user_message', '');
        }

        if (trim($clientRaw) === '') {
            return null;
        }

        $client = $this->matchClient($clientRaw);

        return ($client['id'] ?? 'general') !== 'general' || Str::lower(Str::ascii($clientRaw)) === 'sin cliente'
            ? $client
            : null;
    }

    private function sendRecurringInvoiceEarly(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'facturas.create') || ! RoleAccess::can(Auth::user(), 'facturas.read')) {
            return $this->failure('Tu usuario no tiene permiso para crear o enviar facturas recurrentes.');
        }

        $store = new FileStore('facturas.json');
        $clientsStore = new FileStore('clientes.json');
        $allInvoices = $store->all();
        $template = $this->matchRecurringInvoiceTemplate($proposal, $allInvoices);

        if (!$template) {
            return $this->failure('No encontré una factura recurrente exacta para adelantar. Indica el número de factura o el cliente.');
        }

        $rec = (array) ($template['recurrencia'] ?? []);
        if (empty($rec['enabled']) || empty($rec['next_send'])) {
            return $this->failure('La factura encontrada no tiene una próxima recurrencia activa para adelantar.');
        }

        try {
            $issueDate = Carbon::parse((string) $rec['next_send'], config('app.timezone'))->startOfDay()->format('Y-m-d');
        } catch (\Throwable) {
            return $this->failure('La próxima fecha de envío de esa recurrencia no es válida.');
        }

        $cycleDueDate = $this->recurringCycleDueDate($template, $issueDate, $rec);
        $invoice = $this->findRecurringCycleInvoice($allInvoices, $template, $issueDate);
        $createdNow = false;

        if (!$invoice) {
            $invoice = $this->createRecurringCycleInvoice($store, $allInvoices, $template, $issueDate, $cycleDueDate);
            $createdNow = true;
        }

        if (!empty($invoice['sent_at'])) {
            $url = '/facturas/' . rawurlencode((string) ($invoice['id'] ?? ''));
            return [
                'ok' => true,
                'content' => "✅ **La factura recurrente ya estaba enviada**\n\n- **Factura:** {$invoice['numero']}\n- **Cliente:** {$invoice['cliente']}\n- **Fecha de emisión:** {$invoice['fecha']}\n- **Vencimiento:** " . ($invoice['vencimiento'] ?? 'Sin vencimiento') . "\n\n[Abrir factura]({$url})",
                'url' => $url,
            ];
        }

        $clientEmail = $this->resolveInvoiceClientEmail($invoice, $clientsStore);
        if (!$clientEmail) {
            return $this->failure('No pude enviar la factura recurrente porque el cliente no tiene un correo válido configurado.');
        }

        try {
            $response = app(FacturasController::class)->enviarEmail(new Request([
                'id' => (string) ($invoice['id'] ?? ''),
                'to' => $clientEmail,
                'mail_mode' => 'recurrente',
            ]));
            $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
            $ok = (bool) ($payload['ok'] ?? false);
            if (!$ok) {
                return $this->failure('No se pudo enviar la factura recurrente adelantada: ' . (string) ($payload['error'] ?? 'error desconocido'));
            }
        } catch (\Throwable $e) {
            return $this->failure('No se pudo enviar la factura recurrente adelantada: ' . $e->getMessage());
        }

        $invoice = $store->update((string) ($invoice['id'] ?? ''), [
            'estado' => 'Pendiente',
            'sent_at' => now()->toISOString(),
            'recurring_send_status' => 'sent_early',
            'recurring_send_error' => null,
            'recurring_sent_early_at' => now()->toISOString(),
            'recurring_sent_early_by' => (string) (Auth::user()?->email ?? 'IA'),
        ]) ?: $invoice;

        $url = '/facturas/' . rawurlencode((string) ($invoice['id'] ?? ''));
        $createdText = $createdNow ? 'creada y enviada antes de tiempo' : 'enviada antes de tiempo';

        return [
            'ok' => true,
            'content' => "✅ **Factura recurrente {$createdText}**\n\n- **Factura:** {$invoice['numero']}\n- **Cliente:** {$invoice['cliente']}\n- **Fecha de emisión conservada:** {$invoice['fecha']}\n- **Vencimiento:** " . ($invoice['vencimiento'] ?? 'Sin vencimiento') . "\n- **Enviada a:** {$clientEmail}\n\n[Abrir factura]({$url})",
            'url' => $url,
        ];
    }

    private function matchRecurringInvoiceTemplate(string $proposal, array $allInvoices): ?array
    {
        $number = $this->stripMarkdown($this->field($proposal, ['Factura', 'Número', 'Numero', 'Folio']));
        if ($number !== '' && preg_match('/\b(?:INV|FAC)-\d+(?:-[A-Z0-9]+)?\b/iu', $number, $match)) {
            $number = (string) $match[0];
        }
        if ($number === '' && preg_match('/\b(?:INV|FAC)-\d+(?:-[A-Z0-9]+)?\b/iu', $proposal, $match)) {
            $number = (string) $match[0];
        }
        $client = $this->stripMarkdown($this->field($proposal, ['Cliente', 'Empresa']));
        $directive = (string) data_get($this->context, 'last_user_message', '');
        if ($number === '' && preg_match('/\b(?:INV|FAC)-\d+(?:-[A-Z0-9]+)?\b/iu', $directive, $match)) {
            $number = (string) $match[0];
        }

        $templates = collect($allInvoices)
            ->filter(fn ($invoice) => (bool) data_get($invoice, 'recurrencia.enabled', false) && !empty(data_get($invoice, 'recurrencia.next_send')))
            ->values();

        $normalize = fn (string $value): string => Str::lower(Str::ascii(trim($value)));

        if ($number !== '') {
            $needle = $normalize($number);
            $matched = $templates->first(fn ($invoice) => $normalize((string) ($invoice['numero'] ?? '')) === $needle);
            if ($matched) {
                return $matched;
            }

            $child = collect($allInvoices)->first(fn ($invoice) => $normalize((string) ($invoice['numero'] ?? '')) === $needle && !empty($invoice['recurrencia_origen_id']));
            if ($child) {
                $templateId = (string) ($child['recurrencia_origen_id'] ?? '');
                return $templates->first(fn ($invoice) => (string) ($invoice['id'] ?? '') === $templateId) ?: null;
            }
        }

        if ($client !== '') {
            $needle = $normalize($client);
            $matches = $templates->filter(function ($invoice) use ($needle, $normalize) {
                $haystack = $normalize((string) ($invoice['cliente'] ?? ''));
                return $haystack !== '' && (str_contains($haystack, $needle) || str_contains($needle, $haystack));
            })->values();

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        return $templates->count() === 1 ? $templates->first() : null;
    }

    private function findRecurringCycleInvoice(array $allInvoices, array $template, string $issueDate): ?array
    {
        $templateId = (string) ($template['id'] ?? '');
        if ($templateId === '') {
            return null;
        }

        return collect($allInvoices)->first(function ($invoice) use ($templateId, $issueDate) {
            return (string) ($invoice['recurrencia_origen_id'] ?? '') === $templateId
                && (string) ($invoice['fecha'] ?? '') === $issueDate;
        });
    }

    private function createRecurringCycleInvoice(FileStore $store, array &$allInvoices, array $template, string $issueDate, ?string $cycleDueDate): array
    {
        $new = $template;
        unset(
            $new['id'],
            $new['created_at'],
            $new['updated_at'],
            $new['pagos'],
            $new['saldo'],
            $new['saldo_base'],
            $new['sent_at'],
            $new['recurrencia']
        );

        $new['numero'] = $this->nextInvoiceNumber($allInvoices);
        $new['fecha'] = $issueDate;
        $new['estado'] = 'Pendiente';
        $new['origen'] = 'recurrente';
        $new['recurrencia_origen_id'] = $template['id'] ?? null;
        $new['recurring_send_status'] = 'pending';
        $new['recurring_generated_at'] = now()->toISOString();

        if ($cycleDueDate !== null) {
            $new['vencimiento'] = $cycleDueDate;
        } elseif (!empty($template['vencimiento']) && !empty($template['fecha'])) {
            $creditDays = max(0, (int) floor((strtotime((string) $template['vencimiento']) - strtotime((string) $template['fecha'])) / 86400));
            $new['vencimiento'] = date('Y-m-d', strtotime($issueDate . " +{$creditDays} days"));
        }

        $created = $store->create($new);
        $allInvoices[] = $created;

        if (!empty($created['cliente_id'])) {
            $this->timeline->add((string) $created['cliente_id'], 'factura', [
                'numero' => $created['numero'] ?? '',
                'total' => $created['total'] ?? 0,
                'total_base' => $created['total_base'] ?? null,
                'moneda' => $created['moneda'] ?? null,
                'estado' => 'Creada',
                'factura_id' => $created['id'] ?? null,
            ]);
        }

        return $created;
    }

    private function recurringCycleDueDate(array $template, string $issueDate, array $rec): ?string
    {
        $leadDays = max(0, (int) ($rec['lead_days_before'] ?? 0));
        if ($leadDays > 0) {
            return Carbon::parse($issueDate, config('app.timezone'))->addDays($leadDays)->format('Y-m-d');
        }

        if (!empty($template['vencimiento']) && !empty($template['fecha'])) {
            $creditDays = max(0, (int) floor((strtotime((string) $template['vencimiento']) - strtotime((string) $template['fecha'])) / 86400));
            return date('Y-m-d', strtotime($issueDate . " +{$creditDays} days"));
        }

        return null;
    }

    private function resolveInvoiceClientEmail(array $invoice, FileStore $clientsStore): ?string
    {
        if (empty($invoice['cliente_id'])) {
            return null;
        }

        $client = $clientsStore->find((string) $invoice['cliente_id']);
        $email = trim((string) ($client['contacto_email'] ?? $client['email'] ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function nextInvoiceNumber(array $source, string $prefix = 'INV'): string
    {
        $max = 0;
        foreach ($source as $invoice) {
            $number = strtoupper((string) ($invoice['numero'] ?? ''));
            if ($number !== '' && preg_match('/^' . preg_quote(strtoupper($prefix), '/') . '-(\d+)/', $number, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        return strtoupper($prefix) . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function createMeeting(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'reuniones.create')) {
            return $this->failure('Tu usuario no tiene permiso para crear reuniones.');
        }

        $title = $this->stripMarkdown($this->field($proposal, ['Título', 'Titulo', 'Reunión', 'Reunion', 'Nombre']));
        if ($title === '') $title = 'Reunión';
        $date = $this->parseDate($this->field($proposal, ['Fecha']));
        $startTime = $this->parseTime($this->field($proposal, ['Hora inicio', 'Inicio', 'Hora']));
        $endTime = $this->parseTime($this->field($proposal, ['Hora fin', 'Fin']));
        if (!$date || !$startTime) {
            return $this->failure('Para crear la reunión necesito fecha y hora de inicio confirmadas.');
        }
        if (!$endTime) {
            $endTime = Carbon::parse($date . ' ' . $startTime)->addHour()->format('H:i');
        }

        $client = $this->matchClient($this->field($proposal, ['Cliente', 'Empresa']));
        $clientData = ($client['id'] ?? 'general') !== 'general' ? ($this->clients->find((string) $client['id']) ?: []) : [];
        $responsibles = $this->matchUsers($this->splitPeople($this->field($proposal, ['Responsables', 'Encargados', 'Asignados'])));
        $inviteEmails = $this->extractEmails($this->field($proposal, ['Invitados', 'Correos', 'Emails']));
        $notes = $this->stripMarkdown($this->field($proposal, ['Notas', 'Descripción', 'Descripcion']));
        $location = $this->stripMarkdown($this->field($proposal, ['Ubicación', 'Ubicacion', 'Lugar']));

        $meeting = $this->meetings->create([
            'titulo' => Str::limit($title, 160, ''),
            'cliente_id' => ($client['id'] ?? 'general') !== 'general' ? $client['id'] : null,
            'cliente' => $client['name'] ?? 'Sin cliente',
            'cliente_email' => $clientData['contacto_email'] ?? $clientData['email'] ?? null,
            'invitados' => $inviteEmails,
            'responsables' => $responsibles['names'],
            'responsable_ids' => $responsibles['ids'],
            'responsable_emails' => [],
            'fecha' => $date,
            'hora_inicio' => $startTime,
            'hora_fin' => $endTime,
            'ubicacion' => $location,
            'color' => 'emerald',
            'inicio_at' => Carbon::parse($date . ' ' . $startTime, config('app.timezone'))->toISOString(),
            'fin_at' => Carbon::parse($date . ' ' . $endTime, config('app.timezone'))->toISOString(),
            'notas' => $notes,
            'estado' => 'programada',
            'creado_por' => (string) (Auth::user()->name ?? 'IA'),
            'google_event_id' => null,
            'meet_link' => $this->stripMarkdown($this->field($proposal, ['Meet', 'Meet URL', 'Link'])),
            'meet_error' => null,
        ]);

        $url = '/reuniones?week=' . rawurlencode($date);
        return [
            'ok' => true,
            'content' => "✅ **Reunión creada**\n\n- **Título:** {$meeting['titulo']}\n- **Fecha:** {$date} {$startTime}-{$endTime}\n\n[Abrir reuniones]({$url})",
            'url' => $url,
        ];
    }

    private function createQuote(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'cotizaciones.create')) {
            return $this->failure('Tu usuario no tiene permiso para crear cotizaciones.');
        }

        $client = $this->stripMarkdown($this->field($proposal, ['Cliente', 'Empresa']));
        $items = $this->parseLineItems($proposal);
        if ($client === '' || empty($items)) {
            return $this->failure('Para crear la cotización necesito cliente e items con cantidad y precio.');
        }

        $number = $this->stripMarkdown($this->field($proposal, ['Número', 'Numero'])) ?: $this->nextQuoteNumber();
        $currency = Str::upper($this->stripMarkdown($this->field($proposal, ['Moneda'])) ?: 'COP');
        $subtotal = collect($items)->sum(fn ($item) => ((float) $item['cantidad']) * ((float) $item['precio']));
        $tax = round($subtotal * 0.16, 2);
        $quote = $this->quotes->create([
            'numero' => $number,
            'cliente' => $client,
            'fecha' => $this->parseDate($this->field($proposal, ['Fecha'])) ?: now()->toDateString(),
            'vencimiento' => $this->parseDate($this->field($proposal, ['Vencimiento', 'Fecha de entrega'])),
            'moneda' => $currency,
            'items' => $items,
            'estado' => $this->stripMarkdown($this->field($proposal, ['Estado'])) ?: 'Borrador',
            'subtotal' => round($subtotal, 2),
            'impuestos' => $tax,
            'total' => round($subtotal + $tax, 2),
        ]);

        $url = '/cotizaciones/' . rawurlencode((string) $quote['id']);
        return [
            'ok' => true,
            'content' => "✅ **Cotización creada**\n\n- **Número:** {$quote['numero']}\n- **Cliente:** {$quote['cliente']}\n- **Total:** " . $this->formatAmount((float) $quote['total'], (string) $quote['moneda']) . "\n\n[Abrir cotización]({$url})",
            'url' => $url,
        ];
    }

    private function createContract(string $proposal): array
    {
        if (! RoleAccess::can(Auth::user(), 'contratos.create')) {
            return $this->failure('Tu usuario no tiene permiso para crear contratos.');
        }

        $title = $this->stripMarkdown($this->field($proposal, ['Título', 'Titulo', 'Contrato', 'Nombre']));
        $client = $this->matchClient($this->field($proposal, ['Cliente', 'Empresa']));
        if ($title === '' || ($client['id'] ?? 'general') === 'general') {
            return $this->failure('Para crear el contrato necesito título y cliente existente.');
        }

        $project = $this->matchProject($this->field($proposal, ['Proyecto']));
        $content = $this->extractBody($proposal);
        if ($content === '') {
            $content = $this->stripMarkdown($this->field($proposal, ['Contenido', 'Cláusulas', 'Clausulas', 'Objeto']));
        }
        if ($content === '') {
            $content = '<h1>' . e($title) . '</h1><p>Contrato preparado desde Infocus AI. Revisa y completa las cláusulas antes de enviarlo.</p>';
        }

        $contract = $this->contracts->create([
            'id' => (string) Str::uuid(),
            'titulo' => Str::limit($title, 255, ''),
            'cliente_id' => $client['id'],
            'proyecto_id' => $project['id'] ?? null,
            'contenido' => $content,
            'monto' => (float) ($this->numberFromText($this->field($proposal, ['Monto', 'Valor'])) ?? 0),
            'moneda' => $this->normalizeContractCurrency($this->field($proposal, ['Moneda'])),
            'estado' => $this->normalizeContractStatus($this->field($proposal, ['Estado'])),
            'cliente_nombre' => $client['name'],
            'proyecto_nombre' => $project['titulo'] ?? null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'firmas' => [],
        ]);

        $url = '/contratos/' . rawurlencode((string) $contract['id']);
        return [
            'ok' => true,
            'content' => "✅ **Contrato creado**\n\n- **Título:** {$contract['titulo']}\n- **Cliente:** {$contract['cliente_nombre']}\n- **Estado:** {$contract['estado']}\n\n[Abrir contrato]({$url})",
            'url' => $url,
        ];
    }

    private function isProjectUpdateIntent(string $normalized): bool
    {
        if ($this->isProjectCreateIntent($normalized)) {
            return false;
        }

        foreach ([
            'actualizar',
            'cambiar',
            'modificar',
            'agregar',
            'anadir',
            'añadir',
            'responsable',
            'responsables',
            'encargado',
            'encargados',
            'descripcion',
            'descripción',
            'subtarea',
            'nota',
            'tarea nueva',
            'tareas a agregar',
            'tablero',
            'kanban',
            'columna',
            'columnas',
            'lista',
            'listas',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isProjectCreateIntent(string $normalized): bool
    {
        if (! str_contains($normalized, 'proyecto') && ! str_contains($normalized, 'tablero') && ! str_contains($normalized, 'kanban')) {
            return false;
        }

        foreach ([
            'nuevo proyecto',
            'nuevo tablero',
            'proyecto propuesto',
            'tablero propuesto',
            'crear proyecto',
            'crear tablero',
            'crea proyecto',
            'crea tablero',
            'creame un proyecto',
            'creame un tablero',
            'créame un proyecto',
            'créame un tablero',
            'listo para crear',
            'crear ahora este proyecto',
            'crear ahora este tablero',
            'crear ahora el proyecto',
            'crear ahora el tablero',
            'confirmas la creacion',
            'confirmas crear',
            'tareas sugeridas',
            'lista de tareas',
            'columnas kanban',
        ] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function taskPayload(string $taskText, ?string $start, ?string $due, string $priority, array $responsibles, string $boardStage = 'Por hacer', int $boardOrder = 0): array
    {
        return [
            'id' => (string) Str::ulid(),
            'texto' => Str::limit($this->normalizeTaskTitle($taskText), 180, ''),
            'done' => false,
            'start_date' => $start,
            'end_date' => $due,
            'due_date' => $due,
            'priority' => $this->normalizePriority($priority),
            'owners' => $responsibles['names'] ?? [],
            'owner_ids' => $responsibles['ids'] ?? [],
            'board_stage' => trim($boardStage) !== '' ? Str::limit(trim($boardStage), 60, '') : 'Por hacer',
            'board_order' => $boardOrder,
            'total_seconds' => 0,
            'subtasks' => [],
            'notes' => [],
        ];
    }

    private function notePayload(string $text): array
    {
        return [
            'id' => (string) Str::ulid(),
            'texto' => trim($text),
            'created_at' => now()->toIso8601String(),
            'updated_at' => null,
            'author_name' => (string) (Auth::user()->name ?? 'Infocus AI'),
            'author_id' => Auth::user()?->id,
        ];
    }

    private function normalizeTaskTitle(string $taskText): string
    {
        $taskText = $this->stripMarkdown($taskText);
        $taskText = preg_replace('/\s*\([^)]{5,}\)\s*$/u', '', $taskText) ?? $taskText;
        $taskText = preg_replace('/^(crear|hacer|realizar|desarrollar|generar|preparar)\s+/iu', '', $taskText) ?? $taskText;
        $taskText = preg_replace('/\s+[-–—]\s+tipo\s*:\s*/iu', ' ', $taskText) ?? $taskText;
        return Str::ucfirst(trim($taskText));
    }

    private function parseProjectDraft(string $proposal): array
    {
        $lines = preg_split('/\R+/', $proposal) ?: [];
        $tasks = [];
        $inTasks = false;
        $currentTaskIndex = null;
        $columns = $this->extractColumns($proposal);

        foreach ($lines as $line) {
            $clean = trim(preg_replace('/^\s*[-*•]\s*/u', '', $line) ?? $line);
            $clean = trim(preg_replace('/^\d+[\.)]\s*/', '', $clean) ?? $clean);
            if ($clean === '') {
                continue;
            }

            if (preg_match('/tareas?\s+(sugeridas|agregadas|relacionadas)|lista\s+de\s+tareas/i', $clean)) {
                $inTasks = true;
                continue;
            }

            if ($inTasks) {
                if (preg_match('/^(¿?listo|listo|¿?deseas|deseas|prefieres|puedes tocar|confirmas|puedes|necesitas|opci[oó]n|cliente|estado|prioridad|fecha|responsables?|asignados?|nombre)\b/i', $clean)) {
                    $inTasks = false;
                    $currentTaskIndex = null;
                } else {
                    if (preg_match('/^(nota|informaci[oó]n|detalle)\s*:\s*(.+)$/iu', $clean, $match) && $currentTaskIndex !== null) {
                        $tasks[$currentTaskIndex]['note'] = trim(($tasks[$currentTaskIndex]['note'] ?? '') . "\n" . $this->stripMarkdown((string) $match[2]));
                        continue;
                    }

                    $taskText = $clean;
                    $taskNote = '';
                    if (preg_match('/^(.+?)\s*(?:\||[-–—])\s*(?:nota|informaci[oó]n|detalle)\s*:\s*(.+)$/iu', $clean, $match)) {
                        $taskText = trim((string) $match[1]);
                        $taskNote = trim((string) $match[2]);
                    }

                    $parsedTask = $this->taskLineWithStage($taskText, $columns);
                    $tasks[] = [
                        'text' => $this->normalizeTaskTitle($parsedTask['text']),
                        'note' => $this->stripMarkdown($taskNote),
                        'stage' => $parsedTask['stage'],
                    ];
                    $currentTaskIndex = array_key_last($tasks);
                    continue;
                }
            }
        }

        $title = $this->field($proposal, ['Nombre', 'Proyecto', 'Tablero', 'Título', 'Titulo']);
        if ($title === '') {
            $title = $this->fallbackTitle($proposal);
        }

        return [
            'title' => Str::limit($this->stripMarkdown($title), 140, ''),
            'client' => $this->stripMarkdown($this->field($proposal, ['Cliente', 'Empresa'])),
            'stage' => $this->stripMarkdown($this->field($proposal, ['Estado', 'Etapa'])),
            'priority' => $this->stripMarkdown($this->field($proposal, ['Prioridad'])),
            'start_date' => $this->stripMarkdown($this->field($proposal, ['Fecha inicio', 'Inicio'])),
            'due_date' => $this->stripMarkdown($this->field($proposal, ['Fecha fin', 'Vencimiento', 'Vence', 'Fecha de entrega'])),
            'responsibles' => $this->splitPeople($this->field($proposal, ['Responsables', 'Asignados', 'Encargados'])),
            'description' => $this->stripMarkdown($this->field($proposal, ['Descripción', 'Descripcion'])),
            'columns' => $columns,
            'tasks' => $this->uniqueTaskDrafts($tasks),
        ];
    }

    private function uniqueTaskDrafts(array $tasks): array
    {
        $seen = [];
        $result = [];
        foreach ($tasks as $task) {
            $text = is_array($task) ? trim((string) ($task['text'] ?? '')) : trim((string) $task);
            if ($text === '') {
                continue;
            }

            $key = Str::lower(Str::ascii($text));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = is_array($task) ? $task : ['text' => $text, 'note' => ''];
        }

        return $result;
    }

    private function extractColumns(string $proposal): array
    {
        $labels = ['Columnas', 'Columnas Kanban', 'Listas', 'Estados del tablero'];
        $stopLabels = ['Tareas sugeridas', 'Tareas a agregar', 'Tareas', 'Lista de tareas', 'Descripción', 'Descripcion', 'Responsables', 'Vencimiento', 'Fecha inicio', 'Prioridad', 'Estado', 'Cliente'];
        $items = [];
        $capturing = false;

        foreach (preg_split('/\R+/', $proposal) ?: [] as $line) {
            $clean = trim((string) $line);
            if ($clean === '') {
                continue;
            }

            if ($capturing) {
                foreach ($stopLabels as $stopLabel) {
                    if (preg_match('/^\s*(?:[-*•]\s*)?\**' . preg_quote($stopLabel, '/') . '\**\s*:/iu', $clean)) {
                        $capturing = false;
                        continue 2;
                    }
                }

                $items[] = $clean;
                continue;
            }

            foreach ($labels as $label) {
                if (preg_match('/^\s*(?:[-*•]\s*)?\**' . preg_quote($label, '/') . '\**\s*:\s*(.*)$/iu', $clean, $match)) {
                    $capturing = true;
                    $inline = trim((string) ($match[1] ?? ''));
                    if ($inline !== '') {
                        $items = array_merge($items, preg_split('/[,;|]/u', $inline) ?: []);
                    }
                    continue 2;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map(function ($item) {
            $item = trim($this->stripMarkdown((string) $item));
            $item = preg_replace('/^\d+[\.)]\s*/u', '', $item) ?? $item;
            return $item !== '' ? Str::limit($item, 60, '') : '';
        }, $items))));
    }

    private function projectTaskStages(array $project): array
    {
        $stages = array_values(array_filter(array_map(
            fn ($stage) => trim((string) $stage),
            (array) ($project['task_stages'] ?? [])
        )));

        if (!empty($stages)) {
            return $stages;
        }

        $fromTasks = collect($project['tareas'] ?? [])
            ->map(fn ($task) => trim((string) ($task['board_stage'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return !empty($fromTasks) ? $fromTasks : ['Por hacer', 'En proceso', 'Revisión', 'Terminado'];
    }

    private function normalizeTaskStage(string $value, array $stages): string
    {
        $value = trim($this->stripMarkdown($value));
        if ($value === '') {
            return $stages[0] ?? 'Por hacer';
        }

        $needle = Str::lower(Str::ascii($value));
        foreach ($stages as $stage) {
            $stageNeedle = Str::lower(Str::ascii((string) $stage));
            if ($stageNeedle === $needle || str_contains($stageNeedle, $needle) || str_contains($needle, $stageNeedle)) {
                return (string) $stage;
            }
        }

        return Str::limit($value, 60, '');
    }

    private function taskLineWithStage(string $text, array $stages): array
    {
        $clean = trim($this->stripMarkdown($text));
        $stage = '';

        if (preg_match('/^\[([^\]]+)\]\s*(.+)$/u', $clean, $match)) {
            return [
                'stage' => $this->normalizeTaskStage((string) $match[1], $stages),
                'text' => trim((string) $match[2]),
            ];
        }

        if (preg_match('/^([^:]{2,60})\s*:\s*(.+)$/u', $clean, $match)) {
            $candidate = trim((string) $match[1]);
            $normalized = $this->normalizeTaskStage($candidate, $stages);
            if ($candidate !== '' && in_array($normalized, $stages, true)) {
                $stage = $normalized;
                $clean = trim((string) $match[2]);
            }
        } elseif (preg_match('/^(.{2,60}?)\s+[-–—]\s+(.+)$/u', $clean, $match)) {
            $candidate = trim((string) $match[1]);
            $normalized = $this->normalizeTaskStage($candidate, $stages);
            if (in_array($normalized, $stages, true)) {
                $stage = $normalized;
                $clean = trim((string) $match[2]);
            }
        }

        return ['stage' => $stage, 'text' => $clean];
    }

    private function parseEmailDraft(string $proposal): array
    {
        $recipientRaw = $this->field($proposal, ['Para', 'Destinatario', 'Destinatarios', 'Cliente', 'Correo']);
        $subject = $this->stripMarkdown($this->field($proposal, ['Asunto', 'Subject']));
        $body = $this->extractBody($proposal);

        $emails = $this->extractEmails($recipientRaw);
        if (empty($emails)) {
            $emails = $this->extractEmails($proposal);
        }

        if (empty($emails) && $recipientRaw !== '') {
            $client = $this->matchClient($recipientRaw);
            if (($client['id'] ?? 'general') !== 'general') {
                $clientData = $this->clients->find((string) $client['id']) ?: [];
                $email = trim((string) ($clientData['contacto_email'] ?? $clientData['email'] ?? $clientData['portal_email'] ?? ''));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            }
        }

        return [
            'to' => array_values(array_unique($emails)),
            'subject' => Str::limit($subject, 180, ''),
            'body' => $body,
        ];
    }

    private function extractListAfter(string $proposal, array $labels): array
    {
        $lines = preg_split('/\R+/', $proposal) ?: [];
        $items = [];
        $capturing = false;

        foreach ($lines as $line) {
            $clean = trim((string) $line);
            if ($clean === '') {
                continue;
            }

            foreach ($labels as $label) {
                if (preg_match('/^\s*(?:[-*•]\s*)?\**' . preg_quote($label, '/') . '\**\s*:/iu', $clean)) {
                    $capturing = true;
                    $after = trim((string) preg_replace('/^.*?:/u', '', $clean));
                    if ($after !== '') {
                        $items[] = $this->stripMarkdown($after);
                    }
                    continue 2;
                }
            }

            if (!$capturing) {
                continue;
            }

            if (preg_match('/^\s*(?:¿?listo|listo|¿?deseas|deseas|prefieres|puedes tocar|opci[oó]n|cliente|estado|prioridad|fecha|responsables?|asignados?|encargados?|confirmas|crear|enviar|actualizar)\b/iu', $clean)) {
                break;
            }

            $clean = trim(preg_replace('/^\s*[-*•]\s*/u', '', $clean) ?? $clean);
            $clean = trim(preg_replace('/^\d+[\.)]\s*/', '', $clean) ?? $clean);
            if ($clean !== '') {
                $items[] = $this->stripMarkdown($clean);
            }
        }

        return array_values(array_filter($items));
    }

    private function parseLineItems(string $proposal): array
    {
        $lines = $this->extractListAfter($proposal, ['Items', 'Ítems', 'Productos', 'Servicios', 'Conceptos']);

        return collect($lines)->map(function ($line) {
            $line = $this->stripMarkdown($line);
            $quantity = 1.0;
            $price = null;
            $description = $line;

            if (preg_match('/^(.+?)\s*(?:\||-|:)\s*(\d+(?:[.,]\d+)?)\s*x\s*([\d.,]+)/iu', $line, $match)) {
                $description = trim((string) $match[1]);
                $quantity = (float) str_replace(',', '.', (string) $match[2]);
                $price = $this->numberFromText((string) $match[3]);
            } elseif (preg_match('/^(.+?)\s*x\s*(\d+(?:[.,]\d+)?)\s*(?:a|por|@)?\s*([\d.,]+)/iu', $line, $match)) {
                $description = trim((string) $match[1]);
                $quantity = (float) str_replace(',', '.', (string) $match[2]);
                $price = $this->numberFromText((string) $match[3]);
            } elseif (preg_match('/^(.+?)\s+(?:por|precio)\s+([\d.,]+)/iu', $line, $match)) {
                $description = trim((string) $match[1]);
                $price = $this->numberFromText((string) $match[2]);
            }

            if (!$price || $description === '') {
                return null;
            }

            return [
                'descripcion' => Str::limit($description, 500, ''),
                'cantidad' => max(0.01, $quantity),
                'precio' => max(0, (float) $price),
            ];
        })->filter()->values()->all();
    }

    private function numberFromText(string $value): ?float
    {
        $value = preg_replace('/[^\d.,-]/', '', $value) ?? '';
        if ($value === '') return null;
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nextQuoteNumber(): string
    {
        $max = 0;
        foreach ($this->quotes->all() as $quote) {
            if (!empty($quote['numero']) && preg_match('/(\d+)/', (string) $quote['numero'], $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        return 'COT-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private function parseTime(string $value): ?string
    {
        $value = trim($this->stripMarkdown($value));
        if ($value === '') return null;

        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b/iu', $value, $match)) {
            $hour = (int) $match[1];
            $minute = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 0;
            $ampm = Str::lower((string) ($match[3] ?? ''));
            if ($ampm === 'pm' && $hour < 12) $hour += 12;
            if ($ampm === 'am' && $hour === 12) $hour = 0;
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $minute, 2, '0', STR_PAD_LEFT);
            }
        }

        try {
            return Carbon::parse($value, config('app.timezone'))->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchProject(string $name): ?array
    {
        $name = trim($this->stripMarkdown($name));
        if ($name === '') return null;
        $needle = Str::lower(Str::ascii($name));
        $projects = collect($this->projects->all())
            ->filter(fn ($project) => $this->canUseProject($project))
            ->values();

        $exact = $projects->first(fn ($project) => Str::lower(Str::ascii((string) ($project['titulo'] ?? ''))) === $needle);
        if ($exact) return $exact;

        return $projects->first(function ($project) use ($needle) {
            $title = Str::lower(Str::ascii((string) ($project['titulo'] ?? '')));
            return $title !== '' && (str_contains($title, $needle) || str_contains($needle, $title));
        });
    }

    private function projectFromProposalOrContext(string $proposal, array $labels = ['Proyecto', 'Tablero']): ?array
    {
        $name = $this->field($proposal, $labels);
        if ($name !== '') {
            return $this->matchProject($name);
        }

        $contextProjectId = trim((string) data_get($this->context, 'current_project.id', ''));
        if ($contextProjectId === '') {
            $contextProjectId = trim((string) data_get($this->context, 'project_id', ''));
        }

        if ($contextProjectId !== '') {
            $project = $this->projects->find($contextProjectId);
            if ($project && $this->canUseProject($project)) {
                return $project;
            }
        }

        $contextProjectTitle = trim((string) data_get($this->context, 'current_project.title', ''));
        if ($contextProjectTitle !== '') {
            return $this->matchProject($contextProjectTitle);
        }

        return null;
    }

    private function canUseProject(array $project): bool
    {
        if (! RoleAccess::can(Auth::user(), 'proyectos.read')) {
            return false;
        }

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

    private function matchTask(array $project, string $name): ?array
    {
        $tasks = collect($project['tareas'] ?? []);
        if ($tasks->isEmpty()) return null;
        $name = trim($this->stripMarkdown($name));
        if ($name === '') return $tasks->first();

        $needle = Str::lower(Str::ascii($name));
        $exact = $tasks->first(fn ($task) => Str::lower(Str::ascii((string) ($task['texto'] ?? ''))) === $needle);
        if ($exact) return $exact;

        return $tasks->first(function ($task) use ($needle) {
            $text = Str::lower(Str::ascii((string) ($task['texto'] ?? '')));
            return $text !== '' && (str_contains($text, $needle) || str_contains($needle, $text));
        });
    }

    private function taskDueDate(?array $task): ?string
    {
        if (!$task) {
            return null;
        }

        foreach (['vencimiento', 'due_date', 'fecha_fin', 'fecha_entrega', 'deadline'] as $key) {
            $date = $this->parseDate((string) ($task[$key] ?? ''));
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    private function normalizeContractStatus(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        if (str_contains($value, 'enviado')) return 'Enviado';
        if (str_contains($value, 'firmado')) return 'Firmado';
        if (str_contains($value, 'rechaz')) return 'Rechazado';
        return 'Borrador';
    }

    private function normalizeContractCurrency(string $value): string
    {
        $currency = Str::upper(trim($value));
        return in_array($currency, ['MXN', 'USD', 'EUR'], true) ? $currency : 'USD';
    }

    private function normalizeNoteColor(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        return match (true) {
            str_contains($value, 'verde') || str_contains($value, 'green') => 'green',
            str_contains($value, 'azul') || str_contains($value, 'blue') => 'blue',
            str_contains($value, 'rosa') || str_contains($value, 'pink') => 'pink',
            str_contains($value, 'morado') || str_contains($value, 'purp') || str_contains($value, 'violet') => 'purple',
            str_contains($value, 'blanco') || str_contains($value, 'white') => 'white',
            default => 'yellow',
        };
    }

    private function canEditPersonalNoteRecord(array $note, string $ownerKey): bool
    {
        if ((string) ($note['ownerKey'] ?? '') === $ownerKey) {
            return true;
        }

        return collect($note['collaborators'] ?? [])->contains(function ($item) use ($ownerKey) {
            return (string) ($item['userKey'] ?? '') === $ownerKey
                && (string) ($item['mode'] ?? 'view') === 'edit';
        });
    }

    private function noteHtmlFromPlain(string $title, string $plain): string
    {
        $plain = $this->cleanPersonalNotePlain($plain);
        $lines = collect(preg_split('/\R+/', $plain) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        $html = '<h1>' . e($title) . '</h1>';
        foreach ($lines as $index => $line) {
            if ($index === 0 && Str::lower($line) === Str::lower($title)) {
                continue;
            }

            if (preg_match('/^\[\s?[xX]\s?\]\s*(.+)$/u', $line, $match)) {
                $html .= '<div class="note-checkline is-checked"><input type="checkbox" class="note-checkbox" contenteditable="false" checked="checked"> <span>' . e($match[1]) . '</span></div>';
                continue;
            }

            if (preg_match('/^\[\s?\]\s*(.+)$/u', $line, $match)) {
                $html .= '<div class="note-checkline"><input type="checkbox" class="note-checkbox" contenteditable="false"> <span>' . e($match[1]) . '</span></div>';
                continue;
            }

            if (preg_match('/^(\d+)[\.)]\s+(.+)$/u', $line, $match)) {
                $html .= '<div class="note-numberline" data-note-number="' . e($match[1]) . '"><span class="note-number-marker" contenteditable="false">' . e($match[1]) . '.</span><span class="note-number-content">' . e($match[2]) . '</span></div>';
                continue;
            }

            if (Str::length($line) <= 90 && str_ends_with($line, ':') && ! str_contains($line, '.')) {
                $html .= '<h2>' . e(rtrim($line, ':')) . '</h2>';
                continue;
            }

            $html .= '<p>' . e($line) . '</p>';
        }

        return $html;
    }

    private function cleanPersonalNotePlain(string $plain): string
    {
        $plain = $this->stripMarkdown($plain);
        $plain = preg_split('/\R?\s*(?:Siguiente paso|¿?Confirmas|Puedes tocar|Crear ahora|Enviar ahora|Aplicar cambios|No crear|No enviar)\b/iu', $plain)[0] ?? $plain;
        $plain = preg_replace('/(?:^|\R)\s*(?:[-*•]\s*)?(?:Color|Cliente|Nota ID|ID)\s*:\s*[^\r\n]*/iu', "\n", $plain) ?? $plain;
        $plain = preg_replace('/(?:^|\R)\s*(?:Actualizar nota personal|Editar nota personal|Reescribir nota personal|Nota personal propuesta|Nota personal)\s*:\s*/iu', "\n", $plain) ?? $plain;
        $plain = preg_replace('/(?<!^)(?<!\R)\s+(\d{1,2})[\.)]\s+/u', "\n$1. ", $plain) ?? $plain;
        $plain = preg_replace("/[ \t]+\R/u", "\n", $plain) ?? $plain;
        $plain = preg_replace("/\R{3,}/u", "\n\n", $plain) ?? $plain;

        return trim($plain);
    }

    private function extractBody(string $proposal): string
    {
        $body = '';
        if (preg_match('/(?:^|\R)\s*(?:[-*•]\s*)?\**(?:Contenido|Mensaje|Cuerpo|Body)\**\s*:\s*(.+)$/isu', $proposal, $match)) {
            $body = trim((string) ($match[1] ?? ''));
        }

        if ($body === '') {
            return '';
        }

        $body = preg_split('/\R\s*(?:¿?Confirmas|Puedes tocar|Siguiente paso|Crear ahora|Enviar ahora|Aplicar cambios|No crear|No enviar)\b/iu', $body)[0] ?? $body;
        $body = preg_replace('/\[(?:Abrir|Enviar|Crear)[^\]]*\]\([^)]+\)/iu', '', $body) ?? $body;
        return trim($this->stripMarkdown($body));
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $text, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($mail) => strtolower(trim((string) $mail)))
            ->filter(fn ($mail) => filter_var($mail, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    private function field(string $text, array $labels): string
    {
        foreach ($labels as $label) {
            $pattern = '/(?:^|\R)\s*(?:[-*•]\s*)?\**' . preg_quote($label, '/') . '\**\s*:\s*(.+?)(?=\R|$)/iu';
            if (preg_match($pattern, $text, $match)) {
                return trim((string) ($match[1] ?? ''));
            }
        }

        return '';
    }

    private function fallbackTitle(string $proposal): string
    {
        if (preg_match('/(?:crear|crea|nuevo)\s+(?:un\s+)?(?:proyecto|tablero)\s+(?:llamado|para|de)?\s*["“]?([^"”\n.]+)/iu', $proposal, $match)) {
            return trim((string) ($match[1] ?? ''));
        }

        return '';
    }

    private function stripMarkdown(string $value): string
    {
        $value = preg_replace('/\*\*(.*?)\*\*/s', '$1', $value) ?? $value;
        $value = preg_replace('/\*(.*?)\*/s', '$1', $value) ?? $value;
        $value = preg_replace('/\s*\([^)]*sugerid[^)]*\)/iu', '', $value) ?? $value;
        $value = preg_replace('/\s*\([^)]*cliente activo[^)]*\)/iu', '', $value) ?? $value;
        return trim($value, " \t\n\r\0\x0B-•*");
    }

    private function normalizePriority(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        if (str_contains($value, 'urgente') || str_contains($value, 'alta')) {
            return 'Urgente';
        }
        if (str_contains($value, 'calma') || str_contains($value, 'baja')) {
            return 'Con calma';
        }
        return 'Atención';
    }

    private function normalizeStage(string $value, array $stages, string $fallback): string
    {
        $value = trim($value);
        if ($value === '' || Str::lower(Str::ascii($value)) === 'sin estado') {
            return $fallback;
        }

        foreach ($stages as $stage) {
            if (Str::lower(Str::ascii((string) $stage)) === Str::lower(Str::ascii($value))) {
                return (string) $stage;
            }
        }

        foreach ($stages as $stage) {
            if (str_contains(Str::lower(Str::ascii((string) $stage)), Str::lower(Str::ascii($value)))) {
                return (string) $stage;
            }
        }

        return $fallback;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || in_array(Str::lower(Str::ascii($value)), ['sin fecha', 'no definida', 'pendiente'], true)) {
            return null;
        }

        try {
            return Carbon::parse($value, config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchClient(string $name): array
    {
        $name = trim($name);
        if ($name === '' || Str::lower(Str::ascii($name)) === 'sin cliente') {
            return ['id' => 'general', 'name' => 'Sin Cliente'];
        }

        $needle = Str::lower(Str::ascii($name));
        $looseNeedle = $this->looseSearchText($needle);
        foreach ($this->clients->all() as $client) {
            $company = (string) ($client['empresa'] ?? '');
            if ($company !== '' && Str::lower(Str::ascii($company)) === $needle) {
                return ['id' => $client['id'] ?? 'general', 'name' => $company];
            }
        }

        foreach ($this->clients->all() as $client) {
            $company = (string) ($client['empresa'] ?? '');
            $normalizedCompany = Str::lower(Str::ascii($company));
            $looseCompany = $this->looseSearchText($normalizedCompany);
            if ($company !== '' && (
                str_contains($normalizedCompany, $needle)
                || str_contains($needle, $normalizedCompany)
                || str_contains($looseCompany, $looseNeedle)
                || str_contains($looseNeedle, $looseCompany)
                || $this->textMentionsClientName($looseNeedle, $looseCompany)
            )) {
                return ['id' => $client['id'] ?? 'general', 'name' => $company];
            }
        }

        return ['id' => 'general', 'name' => 'Sin Cliente'];
    }

    private function looseSearchText(string $value): string
    {
        $value = preg_replace('/(.)\1+/u', '$1', $value) ?? $value;

        return preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;
    }

    private function textMentionsClientName(string $text, string $clientName): bool
    {
        $words = collect(preg_split('/\s+/', $clientName) ?: [])
            ->map(fn ($word) => trim((string) $word))
            ->filter(fn ($word) => Str::length($word) >= 4)
            ->values();

        if ($words->isEmpty()) {
            return false;
        }

        return $words->contains(fn ($word) => str_contains($text, $word));
    }

    private function splitPeople(string $value): array
    {
        return collect(preg_split('/[,;]+|\sy\s/iu', $value) ?: [])
            ->map(fn ($item) => trim($this->stripMarkdown((string) $item)))
            ->filter()
            ->values()
            ->all();
    }

    private function matchUsers(array $names): array
    {
        $allUsers = collect($this->users->all())->filter(fn ($user) => (bool) ($user['active'] ?? true))->values();
        $matchedNames = [];
        $matchedIds = [];

        foreach ($names as $name) {
            $needle = Str::lower(Str::ascii((string) $name));
            $user = $allUsers->first(function ($candidate) use ($needle) {
                $candidateName = Str::lower(Str::ascii((string) ($candidate['name'] ?? '')));
                $candidateEmail = Str::lower(Str::ascii((string) ($candidate['email'] ?? '')));
                return $candidateName === $needle
                    || $candidateEmail === $needle
                    || ($needle !== '' && str_contains($candidateName, $needle));
            });

            if ($user) {
                $matchedNames[] = (string) ($user['name'] ?? $name);
                $matchedIds[] = 'team:' . (string) ($user['id'] ?? '');
            }
        }

        if (empty($matchedNames) && Auth::user()) {
            $matchedNames[] = (string) (Auth::user()->name ?? 'Usuario');
            $authId = (string) (Auth::user()->id ?? '');
            if ($authId !== '') {
                $matchedIds[] = 'db:' . $authId;
            }
        }

        return [
            'names' => array_values(array_unique($matchedNames)),
            'ids' => array_values(array_unique(array_filter($matchedIds))),
        ];
    }

    private function failure(string $message): array
    {
        return [
            'ok' => false,
            'content' => $message,
        ];
    }

    private function restoreUndo(string $store, string $id, array $before, string $label): array
    {
        return [
            'scope' => 'server',
            'operation' => 'restore',
            'store' => $store,
            'id' => $id,
            'before' => $before,
            'label' => $label,
        ];
    }

    private function deleteUndo(string $store, string $id, string $label): array
    {
        return [
            'scope' => 'server',
            'operation' => 'delete',
            'store' => $store,
            'id' => $id,
            'label' => $label,
        ];
    }

    private function withActionLog(array $result, string $type): array
    {
        if (! (bool) ($result['ok'] ?? false)) {
            return $result;
        }

        $title = $this->actionTitle($result, $type);
        $this->actionLogs->create([
            'user_id' => Auth::id(),
            'user_name' => (string) (Auth::user()?->name ?? 'Usuario'),
            'type' => $type,
            'title' => $title,
            'content' => Str::limit($this->stripMarkdown((string) ($result['content'] ?? '')), 500, '...'),
            'url' => $result['url'] ?? null,
            'entity_id' => $result['project_id'] ?? null,
        ]);

        $result['action_log_title'] = $title;

        if (!empty($result['undo_action']) && is_array($result['undo_action'])) {
            $undo = $this->undoActions->create([
                'user_id' => (string) Auth::id(),
                'status' => 'pending',
                'label' => (string) ($result['undo_action']['label'] ?? 'Deshacer acción'),
                'action' => $result['undo_action'],
                'created_at' => now()->toISOString(),
            ]);
            $result['undo_action'] = [
                'scope' => 'server',
                'token' => (string) ($undo['id'] ?? ''),
                'label' => (string) ($undo['label'] ?? 'Deshacer'),
            ];
        }

        return $result;
    }

    private function actionTitle(array $result, string $type): string
    {
        $content = $this->stripMarkdown((string) ($result['content'] ?? ''));
        if ($type === 'tarea' && preg_match('/Tareas:\s*(\d+)\s+tareas/iu', $content, $countMatch)) {
            return 'Infocus AI agregó ' . (string) $countMatch[1] . ' tareas';
        }
        if (preg_match('/(?:Nombre|Proyecto|Título|Titulo|Número|Numero|Para|Recordatorio):\s*([^\n]+)/iu', $content, $match)) {
            return 'Infocus AI ' . $this->pastVerb($type) . ' ' . trim((string) $match[1]);
        }

        return 'Infocus AI ' . $this->pastVerb($type);
    }

    private function pastVerb(string $type): string
    {
        return match ($type) {
            'correo' => 'envió un correo',
            'reunión' => 'programó una reunión',
            'cotización' => 'creó una cotización',
            'contrato' => 'creó un contrato',
            'factura recurrente' => 'envió una factura recurrente',
            'tarea' => 'agregó una tarea',
            'subtarea' => 'agregó una subtarea',
            'nota de proyecto', 'nota personal' => 'agregó una nota',
            'recordatorio' => 'creó un recordatorio',
            default => 'creó un proyecto',
        };
    }
}
