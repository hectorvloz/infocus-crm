<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiMemoryService
{
    private FileStore $memories;
    private FileStore $clients;

    public function __construct(private readonly SensitiveDataFilter $filter = new SensitiveDataFilter())
    {
        $this->memories = new FileStore('ai_memories.json');
        $this->clients = new FileStore('clientes.json');
    }

    public function rememberFromMessage(string $message, array $context = []): void
    {
        $clean = $this->filter->cleanText($message);
        if ($clean === '' || mb_strlen($clean) < 12 || mb_strlen($clean) > 1200) {
            return;
        }

        $normalized = Str::lower(Str::ascii($clean));
        if ($this->looksSensitive($normalized) || ! $this->looksMemorable($normalized)) {
            return;
        }

        [$scope, $entityId, $entityName] = $this->resolveScope($clean, $context);
        $this->upsert($clean, $scope, $entityId, $entityName, 'chat');
    }

    public function rememberAiCandidate(?string $candidate, array $context = []): void
    {
        $clean = $this->filter->cleanText((string) $candidate);
        if ($clean === '' || mb_strlen($clean) < 8 || mb_strlen($clean) > 700) {
            return;
        }

        if ($this->looksSensitive(Str::lower(Str::ascii($clean)))) {
            return;
        }

        [$scope, $entityId, $entityName] = $this->resolveScope($clean, $context);
        $this->upsert($clean, $scope, $entityId, $entityName, 'ai');
    }

    public function relevantContext(string $message = '', array $context = [], int $limit = 12): string
    {
        $userId = (string) Auth::id();
        if ($userId === '') {
            return '';
        }

        [$scope, $entityId] = $this->resolveScope($message, $context);
        $items = collect($this->memories->all())
            ->filter(fn ($memory) => (string) ($memory['user_id'] ?? '') === $userId)
            ->filter(function ($memory) use ($scope, $entityId) {
                $memoryScope = (string) ($memory['scope'] ?? 'user');
                if ($memoryScope === 'user' || $memoryScope === 'company') {
                    return true;
                }
                return $scope === 'client'
                    && $memoryScope === 'client'
                    && (string) ($memory['entity_id'] ?? '') === (string) $entityId;
            })
            ->sortByDesc(fn ($memory) => (string) ($memory['updated_at'] ?? $memory['created_at'] ?? ''))
            ->take($limit)
            ->map(function ($memory) {
                $scope = (string) ($memory['scope'] ?? 'user');
                $name = trim((string) ($memory['entity_name'] ?? ''));
                $prefix = match ($scope) {
                    'client' => $name !== '' ? "Cliente {$name}" : 'Cliente',
                    'company' => 'Empresa',
                    default => 'Usuario',
                };
                return "- {$prefix}: " . $this->filter->cleanText((string) ($memory['text'] ?? ''));
            })
            ->filter()
            ->values()
            ->all();

        return empty($items)
            ? ''
            : "Memoria útil:\n" . implode("\n", $items) . "\nUsa estas memorias solo si son relevantes. Si contradicen el mensaje actual, obedece el mensaje actual.";
    }

    public function groupedForUser(): array
    {
        $userId = (string) Auth::id();
        $items = collect($this->memories->all())
            ->filter(fn ($memory) => (string) ($memory['user_id'] ?? '') === $userId)
            ->sortByDesc(fn ($memory) => (string) ($memory['updated_at'] ?? $memory['created_at'] ?? ''))
            ->values();

        return [
            'client' => $items->where('scope', 'client')->values()->all(),
            'user' => $items->filter(fn ($memory) => ($memory['scope'] ?? 'user') === 'user')->values()->all(),
            'company' => $items->where('scope', 'company')->values()->all(),
        ];
    }

    public function update(string $id, string $text): ?array
    {
        $memory = $this->owned($id);
        if (! $memory) {
            return null;
        }

        $clean = Str::limit($this->filter->cleanText($text), 700, '');
        if ($clean === '' || $this->looksSensitive(Str::lower(Str::ascii($clean)))) {
            return null;
        }

        return $this->memories->update($id, [
            'text' => $clean,
            'fingerprint' => $this->fingerprint($clean, (string) ($memory['scope'] ?? 'user'), (string) ($memory['entity_id'] ?? '')),
            'source' => 'manual',
        ]);
    }

    public function delete(string $id): void
    {
        if ($this->owned($id)) {
            $this->memories->delete($id);
        }
    }

    private function upsert(string $text, string $scope, string $entityId, string $entityName, string $source): void
    {
        $user = Auth::user();
        $userId = (string) ($user?->id ?? Auth::id());
        if ($userId === '') {
            return;
        }

        $text = Str::limit($text, 700, '');
        $fingerprint = $this->fingerprint($text, $scope, $entityId);
        $all = $this->memories->all();

        foreach ($all as &$memory) {
            if ((string) ($memory['user_id'] ?? '') === $userId && (string) ($memory['fingerprint'] ?? '') === $fingerprint) {
                $memory['updated_at'] = now()->toISOString();
                $memory['scope'] = $memory['scope'] ?? $scope;
                $memory['entity_id'] = $memory['entity_id'] ?? $entityId;
                $memory['entity_name'] = $memory['entity_name'] ?? $entityName;
                $this->memories->save($all);
                return;
            }
        }
        unset($memory);

        $all[] = [
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'user_name' => (string) ($user?->name ?? 'Usuario'),
            'scope' => $scope,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'text' => $text,
            'source' => $source,
            'fingerprint' => $fingerprint,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $this->memories->save($this->limitMemories($all, $userId));
    }

    private function resolveScope(string $message = '', array $context = []): array
    {
        $companyMarkers = ['mi empresa', 'nuestra empresa', 'la empresa', 'infocus', 'marca'];
        $normalized = Str::lower(Str::ascii($message));
        foreach ($companyMarkers as $marker) {
            if (str_contains($normalized, $marker)) {
                return ['company', 'company', 'Mi empresa'];
            }
        }

        $clientId = trim((string) data_get($context, 'current_client.id', ''));
        $clientName = trim((string) data_get($context, 'current_client.name', ''));

        if ($clientId === '') {
            $clientId = trim((string) data_get($context, 'current_project.client_id', ''));
            $clientName = $clientName ?: trim((string) data_get($context, 'current_project.client_name', ''));
        }
        if ($clientName === '') {
            $clientName = trim((string) data_get($context, 'current_note.client_name', ''));
        }

        $client = $this->findClient($clientId, $clientName);
        if (! $client) {
            $client = $this->findClientMentionedIn($message);
        }
        if ($client) {
            return ['client', (string) ($client['id'] ?? $clientId), (string) ($client['empresa'] ?? $clientName)];
        }

        return ['user', 'user', 'Usuario'];
    }

    private function findClient(string $id = '', string $name = ''): ?array
    {
        if ($id !== '' && !in_array($id, ['general', '__personal__'], true)) {
            $client = $this->clients->find($id);
            if ($client) {
                return $client;
            }
        }

        $needle = Str::lower(Str::ascii(trim($name)));
        if ($needle === '' || in_array($needle, ['sin cliente', 'general'], true)) {
            return null;
        }

        return collect($this->clients->all())->first(function ($client) use ($needle) {
            return Str::lower(Str::ascii((string) ($client['empresa'] ?? ''))) === $needle;
        });
    }

    private function findClientMentionedIn(string $message): ?array
    {
        $haystack = Str::lower(Str::ascii($message));
        if ($haystack === '') {
            return null;
        }

        return collect($this->clients->all())
            ->filter(fn ($client) => mb_strlen(trim((string) ($client['empresa'] ?? ''))) >= 3)
            ->sortByDesc(fn ($client) => mb_strlen((string) ($client['empresa'] ?? '')))
            ->first(function ($client) use ($haystack) {
                $name = Str::lower(Str::ascii((string) ($client['empresa'] ?? '')));
                return $name !== '' && str_contains($haystack, $name);
            });
    }

    private function owned(string $id): ?array
    {
        $memory = $this->memories->find($id);
        return $memory && (string) ($memory['user_id'] ?? '') === (string) Auth::id() ? $memory : null;
    }

    private function fingerprint(string $text, string $scope, string $entityId): string
    {
        $normalized = preg_replace('/\s+/', ' ', Str::lower(Str::ascii($text))) ?? $text;
        return md5($scope . '|' . $entityId . '|' . $normalized);
    }

    private function limitMemories(array $all, string $userId): array
    {
        $otherUsers = array_values(array_filter($all, fn ($memory) => (string) ($memory['user_id'] ?? '') !== $userId));
        $currentUser = collect($all)
            ->filter(fn ($memory) => (string) ($memory['user_id'] ?? '') === $userId)
            ->sortByDesc(fn ($memory) => (string) ($memory['updated_at'] ?? $memory['created_at'] ?? ''))
            ->take(120)
            ->values()
            ->all();

        return array_values([...$otherUsers, ...$currentUser]);
    }

    private function looksMemorable(string $normalized): bool
    {
        $markers = [
            'recuerda', 'acuerdate', 'acuérdate', 'ten en cuenta', 'prefiero', 'me gusta', 'no me gusta',
            'cuando te pida', 'cuando diga', 'si te digo', 'para mi', 'para este cliente', 'este cliente',
            'mi forma', 'directriz', 'regla', 'siempre', 'nunca', 'evita', 'usa ', 'quiero que', 'mejor ',
            'normalmente', 'suele', 'suelen', 'le gusta', 'no le gusta',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, Str::lower(Str::ascii($marker)))) {
                return true;
            }
        }

        return false;
    }

    private function looksSensitive(string $normalized): bool
    {
        foreach (['api key', 'apikey', 'api_key', 'clave api', 'contrasena', 'contraseña', 'password', 'passwd', 'token', 'secret', 'client secret', 'smtp', '.env', 'stripe_secret'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
