<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Support\Ai\AiActionExecutor;
use App\Support\Ai\AiService;
use App\Support\Ai\SensitiveDataFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiController extends Controller
{
    private FileStore $chats;
    private FileStore $memories;
    private SensitiveDataFilter $filter;

    public function __construct(
        private readonly AiService $ai,
        private readonly AiActionExecutor $actions,
    )
    {
        $this->chats = new FileStore('ai_chats.json');
        $this->memories = new FileStore('ai_memories.json');
        $this->filter = new SensitiveDataFilter();
    }

    public function index(): JsonResponse
    {
        $userId = (string) Auth::id();
        $items = collect($this->chats->all())
            ->filter(fn ($chat) => (string) ($chat['user_id'] ?? '') === $userId)
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
                'messages' => $chat['messages'] ?? [],
            ],
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => 'nullable|string',
            'message' => 'required|string|max:6000',
            'context' => 'nullable|array',
        ]);

        $userId = (string) Auth::id();
        $chat = null;

        if (!empty($data['chat_id'])) {
            $chat = $this->ownedChat((string) $data['chat_id'], false);
        }

        if (!$chat) {
            $chat = [
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'title' => $this->ai->makeTitle((string) $data['message']),
                'messages' => [],
                'created_at' => now()->toISOString(),
            ];
        }

        $history = is_array($chat['messages'] ?? null) ? $chat['messages'] : [];
        $userMessage = [
            'role' => 'user',
            'content' => (string) $data['message'],
            'created_at' => now()->toISOString(),
        ];

        $preflight = $this->preflightAssistantReply((string) $data['message'], $data['context'] ?? []);
        if ($preflight !== null) {
            $result = ['content' => $preflight, 'provider' => 'crm'];
        } else {
            $result = $this->ai->reply((string) $data['message'], $history, $data['context'] ?? []);
            $this->rememberRelevantPreference((string) $data['message']);
        }

        $assistantMessage = [
            'role' => 'assistant',
            'content' => (string) ($result['content'] ?? ''),
            'created_at' => now()->toISOString(),
            'provider' => $result['provider'] ?? null,
        ];

        $chat['messages'] = array_values(array_slice([...$history, $userMessage, $assistantMessage], -60));
        $chat['updated_at'] = now()->toISOString();

        $this->saveChat($chat);

        return response()->json([
            'chat_id' => $chat['id'],
            'title' => $chat['title'],
            'message' => $assistantMessage,
        ]);
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

        if ($mentionsCurrentNote && $mentionsProject && $createProjectIntent) {
            return 'Antes de continuar: ¿te refieres a actualizar la nota que tienes abierta o quieres crear un proyecto basado en esa nota?';
        }

        return null;
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ownedChat($id);
        $this->chats->delete($id);

        return response()->json(['ok' => true]);
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

        if (!$chat) {
            $chat = [
                'id' => (string) Str::ulid(),
                'user_id' => (string) Auth::id(),
                'title' => 'Acción IA',
                'messages' => [],
                'created_at' => now()->toISOString(),
            ];
        }

        $context = $data['context'] ?? [];
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
            'note_update' => $result['note_update'] ?? null,
            'reminder_action' => $result['reminder_action'] ?? null,
        ], ($result['ok'] ?? false) ? 200 : 422);
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

    private function rememberRelevantPreference(string $message): void
    {
        $clean = $this->filter->cleanText($message);
        if ($clean === '' || mb_strlen($clean) < 12 || mb_strlen($clean) > 1200) {
            return;
        }

        $normalized = Str::lower(Str::ascii($clean));
        if ($this->looksSensitive($normalized) || ! $this->looksMemorable($normalized)) {
            return;
        }

        $user = Auth::user();
        $userId = (string) ($user?->id ?? Auth::id());
        $fingerprint = md5(preg_replace('/\s+/', ' ', $normalized) ?? $normalized);
        $all = $this->memories->all();

        foreach ($all as &$memory) {
            if ((string) ($memory['user_id'] ?? '') === $userId && (string) ($memory['fingerprint'] ?? '') === $fingerprint) {
                $memory['updated_at'] = now()->toISOString();
                $this->memories->save($all);
                return;
            }
        }
        unset($memory);

        $all[] = [
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'user_name' => (string) ($user?->name ?? 'Usuario'),
            'text' => Str::limit($clean, 500, ''),
            'fingerprint' => $fingerprint,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $otherUsers = array_values(array_filter(
            $all,
            fn ($memory) => (string) ($memory['user_id'] ?? '') !== $userId
        ));
        $currentUser = collect($all)
            ->filter(fn ($memory) => (string) ($memory['user_id'] ?? '') === $userId)
            ->sortByDesc(fn ($memory) => (string) ($memory['updated_at'] ?? $memory['created_at'] ?? ''))
            ->take(30)
            ->values()
            ->all();

        $this->memories->save(array_values([...$otherUsers, ...$currentUser]));
    }

    private function looksMemorable(string $normalized): bool
    {
        $markers = [
            'recuerda',
            'acuerdate',
            'ten en cuenta',
            'prefiero',
            'me gusta',
            'no me gusta',
            'cuando te pida',
            'cuando diga',
            'si te digo',
            'para mi',
            'mi forma',
            'directriz',
            'regla',
            'siempre',
            'nunca',
            'evita',
            'usa ',
            'quiero que',
            'mejor ',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function looksSensitive(string $normalized): bool
    {
        $blocked = [
            'api key',
            'apikey',
            'api_key',
            'clave api',
            'contrasena',
            'contraseña',
            'password',
            'passwd',
            'token',
            'secret',
            'client secret',
            'smtp',
            '.env',
            'google_client_secret',
            'stripe_secret',
        ];

        foreach ($blocked as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
