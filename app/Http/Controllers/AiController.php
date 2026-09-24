<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Support\Ai\AiActionExecutor;
use App\Support\Ai\AiMemoryService;
use App\Support\Ai\AiService;
use App\Support\Ai\AiChatImageStore;
use App\Support\Ai\AiSupportConversationStore;
use App\Support\Ai\SensitiveDataFilter;
use App\Support\Ai\ProjectAiContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiController extends Controller
{
    private FileStore $chats;
    private SensitiveDataFilter $filter;
    private AiMemoryService $memoryService;
    private AiSupportConversationStore $supportConversations;

    public function __construct(
        private readonly AiService $ai,
        private readonly AiActionExecutor $actions,
    )
    {
        $this->chats = new FileStore('ai_chats.json');
        $this->filter = new SensitiveDataFilter();
        $this->memoryService = new AiMemoryService($this->filter);
        $this->supportConversations = new AiSupportConversationStore();
    }

    public function index(): JsonResponse
    {
        $userId = (string) Auth::id();
        $items = collect($this->chats->all())
            ->filter(fn ($chat) => (string) ($chat['user_id'] ?? '') === $userId)
            ->filter(fn ($chat) => empty($chat['support_scope']))
            ->sortByDesc(fn ($chat) => (string) ($chat['updated_at'] ?? $chat['created_at'] ?? ''))
            ->values()
            ->map(fn ($chat) => [
                'id' => $chat['id'] ?? '',
                'title' => $chat['title'] ?? 'Nuevo chat',
                'created_at' => $chat['created_at'] ?? null,
                'updated_at' => $chat['updated_at'] ?? null,
            ])
            ->all();

        return response()->json(['items' => $items]);
    }

    public function show(string $id): JsonResponse
    {
        $chat = $this->ownedChat($id);

        return response()->json([
            'item' => [
                'id' => $chat['id'],
                'title' => $chat['title'] ?? 'Nuevo chat',
                'messages' => collect($chat['messages'] ?? [])->map(function ($message) use ($chat) {
                    $message['images'] = collect($message['images'] ?? [])
                        ->map(fn ($image) => (new AiChatImageStore())->publicMetadata($image, (string) $chat['id']))
                        ->all();
                    return $message;
                })->all(),
            ],
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'nullable|string',
            'message' => 'nullable|string|max:6000',
            'context' => 'nullable|array',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|mimes:jpeg,png,webp,gif|max:2048',
            'support_scope' => 'nullable|in:note',
            'support_entity_id' => 'nullable|string|max:100',
        ]);

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' && !$request->hasFile('images')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['message' => 'Escribe un mensaje o adjunta una imagen.']);
        }
        if ($message === '') $message = 'Analiza estas imágenes y dime qué observas.';

        $userId = (string) Auth::id();
        $supportScope = (string) ($data['support_scope'] ?? '');
        $supportEntityId = trim((string) ($data['support_entity_id'] ?? ''));
        if ($supportScope !== '') {
            abort_if($supportEntityId === '', 422);
            $this->authorizeSupportEntity($supportScope, $supportEntityId);
        }
        $chat = null;

        if (!empty($data['chat_id'])) {
            $chat = $this->ownedChat((string) $data['chat_id'], false);
        } elseif ($supportScope !== '') {
            $chat = $this->findSupportChat($userId, $supportScope, $supportEntityId);
        }

        $scopeKey = $this->contextScopeKey($data['context'] ?? [], $message);
        if ($chat && (string) ($chat['scope_key'] ?? '') !== $scopeKey) {
            $chat = null;
        }

        if (!$chat) {
            $chat = [
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'title' => $this->ai->makeTitle($message),
                'messages' => [],
                'scope_key' => $scopeKey,
                'support_scope' => $supportScope ?: null,
                'support_entity_id' => $supportEntityId ?: null,
                'created_at' => now()->toISOString(),
            ];
        }

        $history = $supportScope !== ''
            ? $this->supportConversations->history($userId, $supportScope, $supportEntityId)
            : (is_array($chat['messages'] ?? null) ? $chat['messages'] : []);
        $imageStore = new AiChatImageStore();
        $images = [];
        foreach ($request->file('images', []) as $file) {
            $images[] = $imageStore->save($file, (string) $chat['id']);
        }
        $userMessage = [
            'role' => 'user',
            'content' => $message,
            'images' => $images,
            'created_at' => now()->toISOString(),
        ];

        $preflight = $images ? null : $this->preflightAssistantReply($message, $data['context'] ?? []);
        if ($preflight !== null) {
            $result = ['content' => $preflight, 'provider' => 'crm'];
        } else {
            $result = $this->ai->reply($message, $history, $data['context'] ?? [], $images);
            $memoryContext = $data['context'] ?? [];
            $memoryContext['last_user_message'] = $message;
            $this->memoryService->rememberAiCandidate(
                $this->ai->extractMemoryCandidate($message, $memoryContext),
                $memoryContext
            );
        }

        $assistantMessage = [
            'role' => 'assistant',
            'content' => (string) ($result['content'] ?? ''),
            'created_at' => now()->toISOString(),
            'provider' => $result['provider'] ?? null,
            'actions' => $this->normalizeResponseActions($result['actions'] ?? []),
        ];

        if ($supportScope !== '') {
            $visibleUserMessage = trim((string) data_get($data, 'context.last_user_message', $message)) ?: $message;
            $this->supportConversations->recordExchange(
                $userId,
                $supportScope,
                $supportEntityId,
                $visibleUserMessage,
                (string) ($assistantMessage['content'] ?? ''),
            );
        }

        $chat['messages'] = array_values(array_slice([...$history, $userMessage, $assistantMessage], -60));
        $chat['updated_at'] = now()->toISOString();

        $this->saveChat($chat);

        return response()->json([
            'chat_id' => $chat['id'],
            'title' => $chat['title'],
            'message' => $assistantMessage,
        ]);
    }

    public function supportHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => 'required|in:project,task,note',
            'entity_id' => 'required|string|max:100',
            'project_id' => 'nullable|string|max:100',
        ]);
        $this->authorizeSupportEntity((string) $data['scope'], (string) $data['entity_id'], (string) ($data['project_id'] ?? ''));

        return response()->json([
            'messages' => $this->supportConversations->messages(
                (string) Auth::id(),
                (string) $data['scope'],
                (string) $data['entity_id'],
            ),
            'expires_in_hours' => 24,
        ]);
    }

    private function authorizeSupportEntity(string $scope, string $entityId, string $projectId = ''): array
    {
        if ($scope === 'project') {
            $project = (new FileStore('proyectos.json'))->find($entityId);
            abort_if(!$project, 404);
            return $project;
        }

        if ($scope === 'task') {
            $projects = (new FileStore('proyectos.json'))->all();
            foreach ($projects as $project) {
                if ($projectId !== '' && (string) ($project['id'] ?? '') !== $projectId) continue;
                foreach (($project['tareas'] ?? []) as $task) {
                    if ((string) ($task['id'] ?? '') === $entityId) return $task;
                }
            }
            abort(404);
        }

        $note = (new FileStore('mis_notas.json'))->find($entityId);
        $userId = (string) Auth::id();
        $canSee = $note && ((string) ($note['ownerKey'] ?? '') === $userId
            || collect($note['collaborators'] ?? [])->contains(fn ($item) => (string) ($item['userKey'] ?? '') === $userId));
        abort_unless($canSee, 404);
        return $note;
    }

    private function findSupportChat(string $userId, string $scope, string $entityId): ?array
    {
        return collect($this->chats->all())
            ->filter(fn ($chat) => (string) ($chat['user_id'] ?? '') === $userId)
            ->filter(fn ($chat) => (string) ($chat['support_scope'] ?? '') === $scope)
            ->filter(fn ($chat) => (string) ($chat['support_entity_id'] ?? '') === $entityId)
            ->filter(function ($chat) {
                try {
                    return Carbon::parse($chat['updated_at'] ?? $chat['created_at'] ?? null)->greaterThan(now()->subDay());
                } catch (\Throwable) {
                    return false;
                }
            })
            ->sortByDesc(fn ($chat) => (string) ($chat['updated_at'] ?? $chat['created_at'] ?? ''))
            ->first();
    }

    private function contextScopeKey(array $context, string $message = ''): string
    {
        $mentioned = (new ProjectAiContext())->findMentionedProject((new FileStore('proyectos.json'))->all(), $message);
        if ($mentioned) {
            return 'project:' . (string) $mentioned['id'];
        }
        $namedClients = collect((new FileStore('clientes.json'))->all())->filter(function ($client) use ($message) {
            $name = trim((string) ($client['empresa'] ?? ''));
            return mb_strlen($name) >= 3 && preg_match('/(?<![\pL\pN])' . preg_quote($name, '/') . '(?![\pL\pN])/iu', $message) === 1;
        });
        if ($namedClients->count() === 1) {
            return 'client:' . (string) ($namedClients->first()['id'] ?? '');
        }
        $projectId = trim((string) data_get($context, 'current_project.id', ''));
        if ($projectId !== '' && (new FileStore('proyectos.json'))->find($projectId)) {
            return 'project:' . $projectId;
        }
        $noteId = trim((string) data_get($context, 'current_note.id', ''));
        if ($noteId !== '') {
            $note = (new FileStore('mis_notas.json'))->find($noteId);
            $userId = (string) (Auth::id() ?? Auth::user()?->email ?? '');
            $canSee = $note && ((string) ($note['ownerKey'] ?? '') === $userId
                || collect($note['collaborators'] ?? [])->contains(fn ($item) => (string) ($item['userKey'] ?? '') === $userId));
            if ($canSee) {
                return 'note:' . $noteId;
            }
        }
        return '';
    }

    private function normalizeResponseActions(mixed $actions): array
    {
        if (!is_array($actions)) {
            return [];
        }

        return collect($actions)
            ->filter(fn ($action) => is_array($action) && in_array((string) ($action['type'] ?? ''), $this->allowedResponseActionTypes(), true))
            ->map(function ($action) {
                $type = (string) ($action['type'] ?? '');
                $normalized = [
                    'type' => $type,
                    'label' => $this->responseActionLabel($type),
                    'fields' => $this->sanitizeResponseActionFields((array) ($action['fields'] ?? [])),
                    'requires_confirmation' => true,
                ];

                if ($type === 'start_pomodoro') {
                    $minutes = (int) ($action['minutes'] ?? data_get($normalized, 'fields.minutes', 25));
                    if (!in_array($minutes, [25, 30, 60], true)) {
                        $minutes = 25;
                    }

                    $task = Str::limit($this->filter->cleanText((string) ($action['task'] ?? data_get($normalized, 'fields.task', 'Bloque de foco guiado por IA'))), 160, '');
                    $normalized['minutes'] = $minutes;
                    $normalized['task'] = $task !== '' ? $task : 'Bloque de foco guiado por IA';
                    $normalized['open_pip'] = (bool) ($action['open_pip'] ?? data_get($normalized, 'fields.open_pip', false));
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function allowedResponseActionTypes(): array
    {
        return [
            'start_pomodoro',
            'create_project',
            'update_project',
            'add_project_task',
            'add_project_subtask',
            'add_project_note',
            'create_personal_note',
            'update_personal_note',
            'create_reminder',
            'create_meeting',
            'create_quote',
            'create_contract',
            'send_email',
            'send_recurring_invoice_early',
            'create_invoice_draft',
        ];
    }

    private function responseActionLabel(string $type): string
    {
        return match ($type) {
            'start_pomodoro' => 'Activar pomodoro',
            'create_project' => 'Crear proyecto',
            'update_project' => 'Aplicar cambios',
            'add_project_task' => 'Agregar tarea',
            'add_project_subtask' => 'Agregar subtarea',
            'add_project_note' => 'Agregar nota',
            'create_personal_note' => 'Crear nota',
            'update_personal_note' => 'Actualizar nota',
            'create_reminder' => 'Crear recordatorio',
            'create_meeting' => 'Programar reunión',
            'create_quote' => 'Crear cotización',
            'create_contract' => 'Crear contrato',
            'send_email' => 'Enviar correo',
            'send_recurring_invoice_early' => 'Enviar factura',
            'create_invoice_draft' => 'Crear borrador',
            default => 'Ejecutar acción',
        };
    }

    private function sanitizeResponseActionFields(array $fields, int $depth = 0): array
    {
        if ($depth > 3) {
            return [];
        }

        $clean = [];
        foreach ($fields as $key => $value) {
            $safeKey = Str::snake(Str::limit(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key) ?: '', 60, ''));
            if ($safeKey === '' || in_array($safeKey, ['type', 'requires_confirmation'], true)) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$safeKey] = $value;
                continue;
            }

            if (is_string($value)) {
                $clean[$safeKey] = Str::limit($this->filter->cleanText($value), 4000, '');
                continue;
            }

            if (is_array($value)) {
                $clean[$safeKey] = array_is_list($value)
                    ? array_slice(array_map(fn ($item) => is_array($item)
                        ? $this->sanitizeResponseActionFields($item, $depth + 1)
                        : (is_scalar($item) ? Str::limit($this->filter->cleanText((string) $item), 1000, '') : null), $value), 0, 40)
                    : $this->sanitizeResponseActionFields($value, $depth + 1);
            }
        }

        return $clean;
    }

    private function preflightAssistantReply(string $message, array $context): ?string
    {
        $text = Str::lower(Str::ascii(trim($message)));
        if ($text === '' || trim((string) data_get($context, 'current_note.id', '')) === '') {
            return null;
        }

        $mentionsCurrentNote = str_contains($text, 'esta nota')
            || str_contains($text, 'la nota')
            || str_contains($text, 'nota abierta')
            || str_contains($text, 'nota que tengo abierta')
            || str_contains($text, 'nota que tienes abierta');
        $mentionsProject = preg_match('/\bproyecto(s)?\b/u', $text) === 1;
        $createProjectIntent = preg_match('/\b(crea|crear|creame|haz|hacer|genera|generar|prepara|preparar)\b/u', $text) === 1;

        $explicitConversion = preg_match('/\b(basad[oa]|a partir de|desde|con (?:el )?contenido de|convierte|convertir|transforma|transformar)\b/u', $text) === 1
            || preg_match('/\b(?:de|con)\s+(?:esta|la)\s+nota\s+(?:crea|crear|haz|hacer|genera|generar|prepara|preparar)\b/u', $text) === 1;
        if ($mentionsCurrentNote && $mentionsProject && $createProjectIntent && !$explicitConversion) {
            return 'Antes de continuar: ¿te refieres a actualizar la nota que tienes abierta o quieres crear un proyecto basado en esa nota?';
        }

        return null;
    }

    public function destroy(string $id): JsonResponse
    {
        $chat = $this->ownedChat($id);
        (new AiChatImageStore())->deleteChatImages($chat);
        $this->chats->delete($id);

        return response()->json(['ok' => true]);
    }

    public function image(string $id, string $imageId): \Illuminate\Http\Response
    {
        $chat = $this->ownedChat($id);
        $image = collect($chat['messages'] ?? [])
            ->flatMap(fn ($message) => $message['images'] ?? [])
            ->first(fn ($item) => (string) ($item['id'] ?? '') === $imageId);
        abort_if(!$image || !(new AiChatImageStore())->available($image), 404);

        return response(Storage::disk('local')->get($image['path']), 200, [
            'Content-Type' => $image['mime'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function executeAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'nullable|string',
            'proposal' => 'required|string|max:12000',
            'context' => 'nullable|array',
        ]);

        $chat = null;
        if (!empty($data['chat_id'])) {
            $chat = $this->ownedChat((string) $data['chat_id'], false);
        }

        $lastMessage = $chat ? $this->lastUserMessage((array) ($chat['messages'] ?? [])) : '';
        if ($chat && (string) ($chat['scope_key'] ?? '') !== $this->contextScopeKey($data['context'] ?? [], $lastMessage)) {
            return response()->json(['ok' => false, 'message' => ['content' => 'Este chat pertenece a otro proyecto o cliente. Vuelve a pedir la acción desde el contexto actual.']], 409);
        }

        if (!$chat) {
            $chat = [
                'id' => (string) Str::ulid(),
                'user_id' => (string) Auth::id(),
                'title' => 'Acción IA',
                'messages' => [],
                'scope_key' => $this->contextScopeKey($data['context'] ?? []),
                'created_at' => now()->toISOString(),
            ];
        }

        $context = $data['context'] ?? [];
        $context['chat_id'] = $chat['id'];
        $history = is_array($chat['messages'] ?? null) ? $chat['messages'] : [];
        $lastUserMessage = $this->lastUserMessage($history);
        if ($lastUserMessage !== '') {
            $context['last_user_message'] = $lastUserMessage;
        }

        $result = $this->actions->execute((string) $data['proposal'], $context);
        $assistantMessage = [
            'role' => 'assistant',
            'content' => (string) ($result['content'] ?? 'Acción procesada.'),
            'created_at' => now()->toISOString(),
            'provider' => 'crm',
        ];

        $chat['messages'] = array_values(array_slice([...$history, $assistantMessage], -60));
        $chat['updated_at'] = now()->toISOString();
        $this->saveChat($chat);

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'chat_id' => $chat['id'],
            'message' => $assistantMessage,
            'url' => $result['url'] ?? null,
            'project_id' => $result['project_id'] ?? null,
            'project_item' => $result['project_item'] ?? null,
            'project_action' => $result['project_action'] ?? null,
            'note_update' => $result['note_update'] ?? null,
            'reminder_action' => $result['reminder_action'] ?? null,
            'undo_action' => $result['undo_action'] ?? null,
        ], ($result['ok'] ?? false) ? 200 : 422);
    }

    public function undoAction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $undoStore = new FileStore('ai_undo_actions.json');
        $undo = $undoStore->find((string) $data['token']);
        if (!$undo || (string) ($undo['user_id'] ?? '') !== (string) Auth::id()) {
            return response()->json(['ok' => false, 'message' => 'No encontré esa acción para deshacer.'], 404);
        }

        if ((string) ($undo['status'] ?? 'pending') !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'Esta acción ya fue deshecha.'], 422);
        }

        $action = is_array($undo['action'] ?? null) ? $undo['action'] : [];
        $storeName = (string) ($action['store'] ?? '');
        $operation = (string) ($action['operation'] ?? '');
        $id = (string) ($action['id'] ?? '');
        $allowedStores = [
            'proyectos' => 'proyectos.json',
            'mis_notas' => 'mis_notas.json',
        ];

        if ($id === '' || !isset($allowedStores[$storeName]) || !in_array($operation, ['restore', 'delete'], true)) {
            return response()->json(['ok' => false, 'message' => 'Esta acción no se puede deshacer automáticamente.'], 422);
        }

        $targetStore = new FileStore($allowedStores[$storeName]);
        $restoredRecord = null;
        if ($operation === 'delete') {
            $targetStore->delete($id);
        } else {
            $before = is_array($action['before'] ?? null) ? $action['before'] : [];
            if (empty($before)) {
                return response()->json(['ok' => false, 'message' => 'No hay copia anterior para restaurar.'], 422);
            }

            $restoredRecord = $targetStore->update($id, $before);
            if (!$restoredRecord) {
                $restoredRecord = $targetStore->create(['id' => $id, ...$before]);
            }
        }

        $undoStore->update((string) $undo['id'], [
            'status' => 'undone',
            'undone_at' => now()->toISOString(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Acción deshecha.',
            'store' => $storeName,
            'id' => $id,
            'operation' => $operation,
            'record' => $restoredRecord,
        ]);
    }

    private function lastUserMessage(array $history): string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') === 'user') {
                return trim((string) ($history[$i]['content'] ?? ''));
            }
        }

        return '';
    }

    private function ownedChat(string $id, bool $abort = true): ?array
    {
        $chat = $this->chats->find($id);
        if (!$chat || (string) ($chat['user_id'] ?? '') !== (string) Auth::id()) {
            if ($abort) {
                abort(404);
            }

            return null;
        }

        return $chat;
    }

    private function saveChat(array $chat): void
    {
        $existing = $this->chats->find((string) $chat['id']);
        if ($existing) {
            $this->chats->update((string) $chat['id'], $chat);
        } else {
            $this->chats->create($chat);
        }
    }

}
