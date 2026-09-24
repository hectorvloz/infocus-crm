<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Support\Ai\NoteClient;
use App\Support\SafeRichText;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MisNotasController extends Controller
{
    protected FileStore $store;
    protected FileStore $users;

    public function __construct()
    {
        $this->store = new FileStore('mis_notas.json');
        $this->users = new FileStore('users.json');
    }

    public function index(Request $request)
    {
        $key = $this->resolveUserKey($request);
        $records = $this->store->all();
        $sanitized = array_map(fn ($item) => $this->sanitizeRecord($item), $records);
        if ($sanitized !== $records) {
            $this->store->save($sanitized);
        }
        $all = collect($sanitized);
        $notes = $all
            ->filter(fn ($item) => $this->canSee($item, $key))
            ->map(function ($item) use ($key) {
                return $this->toClientNote($item, $key);
            })
            ->values()
            ->all();

        return response()->json(['data' => $notes]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'notes' => 'required|array',
            'notes.*.id' => 'required|string',
            'notes.*.title' => 'nullable|string|max:500',
            'notes.*.html' => 'nullable|string|max:5242880',
            'notes.*.plainText' => 'nullable|string|max:1048576',
            'notes.*.color' => 'nullable|string',
            'notes.*.linkedClient' => 'nullable|string',
            'notes.*.clientId' => 'nullable|string',
            'notes.*.createdAt' => 'nullable',
            'notes.*.updatedAt' => 'nullable',
            'notes.*.ownerKey' => 'nullable|string',
            'notes.*.ownerName' => 'nullable|string',
            'notes.*.collaborators' => 'nullable|array',
            'notes.*.collaborators.*.userKey' => 'required_with:notes.*.collaborators|string',
            'notes.*.collaborators.*.userName' => 'nullable|string',
            'notes.*.collaborators.*.mode' => 'nullable|in:view,edit',
        ]);

        $key = $this->resolveUserKey($request);
        $name = $this->resolveUserName($request);
        $existing = collect($this->store->all())->keyBy(fn ($item) => (string) ($item['id'] ?? ''));
        $incoming = collect($data['notes'] ?? [])->keyBy(fn ($item) => (string) ($item['id'] ?? ''));

        // Create/update incoming notes with ownership rules.
        foreach ($incoming as $noteId => $payload) {
            if ($noteId === '') {
                continue;
            }

            $record = $existing->get($noteId);
            if (!$record) {
                $this->authorizePermission($request, 'mis-notas.create');
                $existing->put($noteId, $this->buildNewRecord($payload, $key, $name));
                continue;
            }

            if ($this->isOwner($record, $key)) {
                $this->authorizePermission($request, 'mis-notas.update');
                $existing->put($noteId, $this->mergeOwnerUpdate($record, $payload, $key, $name));
                continue;
            }

            if ($this->canEdit($record, $key)) {
                $this->authorizePermission($request, 'mis-notas.update');
                $existing->put($noteId, $this->mergeCollaboratorUpdate($record, $payload));
            }
        }

        // Owner deletions: if owner no longer sent a note, delete it (and shared copies).
        foreach ($existing as $noteId => $record) {
            if (!$this->isOwner($record, $key)) {
                continue;
            }
            if ($incoming->has($noteId)) {
                continue;
            }
            $this->authorizePermission($request, 'mis-notas.delete');
            $existing->forget($noteId);
        }

        $this->store->save($existing->values()->all());

        return response()->json(['ok' => true]);
    }

    public function collaborators(Request $request)
    {
        $currentKey = $this->resolveUserKey($request);
        $users = collect($this->users->all())
            ->map(function ($user) {
                $key = (string) ($user['id'] ?? $user['email'] ?? '');
                $name = trim((string) ($user['name'] ?? ''));
                if ($key === '' || $name === '') {
                    return null;
                }
                return [
                    'userKey' => $key,
                    'userName' => $name,
                    'email' => (string) ($user['email'] ?? ''),
                ];
            })
            ->filter()
            ->reject(fn ($user) => (string) ($user['userKey'] ?? '') === $currentKey)
            ->sortBy('userName')
            ->values()
            ->all();

        return response()->json(['data' => $users]);
    }

    protected function buildNewRecord(array $payload, string $key, string $name): array
    {
        $now = now()->valueOf();
        return [
            'id' => (string) ($payload['id'] ?? Str::ulid()),
            'ownerKey' => $key,
            'ownerName' => $name,
            'title' => (string) ($payload['title'] ?? 'Nota sin titulo'),
            'html' => SafeRichText::sanitize((string) ($payload['html'] ?? '')),
            'plainText' => (string) ($payload['plainText'] ?? ''),
            'color' => (string) ($payload['color'] ?? 'yellow'),
            'linkedClient' => isset($payload['linkedClient']) ? (string) $payload['linkedClient'] : '',
            'clientId' => $this->clientIdFor($payload),
            'collaborators' => $this->normalizeCollaborators($payload['collaborators'] ?? []),
            'createdAt' => $payload['createdAt'] ?? $now,
            'updatedAt' => $payload['updatedAt'] ?? $now,
        ];
    }

    protected function mergeOwnerUpdate(array $record, array $payload, string $key, string $name): array
    {
        return [
            ...$record,
            'ownerKey' => $key,
            'ownerName' => $name,
            'title' => (string) ($payload['title'] ?? ($record['title'] ?? 'Nota sin titulo')),
            'html' => SafeRichText::sanitize((string) ($payload['html'] ?? ($record['html'] ?? ''))),
            'plainText' => (string) ($payload['plainText'] ?? ($record['plainText'] ?? '')),
            'color' => (string) ($payload['color'] ?? ($record['color'] ?? 'yellow')),
            'linkedClient' => isset($payload['linkedClient']) ? (string) $payload['linkedClient'] : (string) ($record['linkedClient'] ?? ''),
            'clientId' => $this->clientIdFor($payload, $record),
            'collaborators' => $this->normalizeCollaborators($payload['collaborators'] ?? ($record['collaborators'] ?? [])),
            'createdAt' => $record['createdAt'] ?? ($payload['createdAt'] ?? now()->valueOf()),
            'updatedAt' => $payload['updatedAt'] ?? now()->valueOf(),
        ];
    }

    protected function mergeCollaboratorUpdate(array $record, array $payload): array
    {
        return [
            ...$record,
            'title' => (string) ($payload['title'] ?? ($record['title'] ?? 'Nota sin titulo')),
            'html' => SafeRichText::sanitize((string) ($payload['html'] ?? ($record['html'] ?? ''))),
            'plainText' => (string) ($payload['plainText'] ?? ($record['plainText'] ?? '')),
            'color' => (string) ($payload['color'] ?? ($record['color'] ?? 'yellow')),
            'linkedClient' => isset($payload['linkedClient']) ? (string) $payload['linkedClient'] : (string) ($record['linkedClient'] ?? ''),
            'clientId' => $this->clientIdFor($payload, $record),
            'updatedAt' => now()->valueOf(),
        ];
    }

    protected function normalizeCollaborators(array $list): array
    {
        return collect($list)
            ->map(function ($item) {
                $userKey = (string) ($item['userKey'] ?? '');
                if ($userKey === '') {
                    return null;
                }
                return [
                    'userKey' => $userKey,
                    'userName' => trim((string) ($item['userName'] ?? '')),
                    'mode' => (string) (($item['mode'] ?? 'view') === 'edit' ? 'edit' : 'view'),
                ];
            })
            ->filter()
            ->unique('userKey')
            ->values()
            ->all();
    }

    private function sanitizeRecord(array $record): array
    {
        $record['html'] = SafeRichText::sanitize((string) ($record['html'] ?? ''));
        return $record;
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if (Auth::check()) {
            abort_unless(RoleAccess::can(Auth::user(), $permission), 403);
            return;
        }

        abort_unless(in_array((string) $request->session()->get('user.role'), ['admin', 'super_admin'], true), 403);
    }

    protected function toClientNote(array $record, string $currentKey): array
    {
        $permission = $this->isOwner($record, $currentKey)
            ? 'owner'
            : ($this->canEdit($record, $currentKey) ? 'edit' : 'view');

        return [
            'id' => (string) ($record['id'] ?? ''),
            'title' => (string) ($record['title'] ?? 'Nota sin titulo'),
            'html' => (string) ($record['html'] ?? ''),
            'plainText' => (string) ($record['plainText'] ?? ''),
            'color' => (string) ($record['color'] ?? 'yellow'),
            'linkedClient' => (string) ($record['linkedClient'] ?? ''),
            'clientId' => (string) ((new NoteClient())->resolve($record)['id'] ?? ''),
            'createdAt' => $record['createdAt'] ?? now()->valueOf(),
            'updatedAt' => $record['updatedAt'] ?? now()->valueOf(),
            'ownerKey' => (string) ($record['ownerKey'] ?? ''),
            'ownerName' => (string) ($record['ownerName'] ?? 'Usuario'),
            'collaborators' => $this->normalizeCollaborators($record['collaborators'] ?? []),
            'permission' => $permission,
            'isShared' => !$this->isOwner($record, $currentKey),
        ];
    }

    protected function canSee(array $record, string $currentKey): bool
    {
        return $this->isOwner($record, $currentKey)
            || collect($record['collaborators'] ?? [])->contains(fn ($c) => (string) ($c['userKey'] ?? '') === $currentKey);
    }

    private function clientIdFor(array $payload, array $record = []): string
    {
        $candidate = [
            'clientId' => $payload['clientId'] ?? $record['clientId'] ?? '',
            'linkedClient' => $payload['linkedClient'] ?? $record['linkedClient'] ?? '',
        ];
        if (array_key_exists('linkedClient', $payload)
            && trim((string) $payload['linkedClient']) !== trim((string) ($record['linkedClient'] ?? ''))) {
            $selected = (new NoteClient())->resolve(['clientId' => $payload['clientId'] ?? '']);
            $label = trim((string) ($payload['linkedClient'] ?? ''));
            $candidate['clientId'] = $selected && ($label === (string) ($selected['empresa'] ?? '') || $label === (string) ($selected['id'] ?? ''))
                ? (string) $selected['id']
                : '';
        }

        return (string) ((new NoteClient())->resolve($candidate)['id'] ?? '');
    }

    protected function canEdit(array $record, string $currentKey): bool
    {
        if ($this->isOwner($record, $currentKey)) {
            return true;
        }

        return collect($record['collaborators'] ?? [])->contains(function ($c) use ($currentKey) {
            return (string) ($c['userKey'] ?? '') === $currentKey
                && (string) ($c['mode'] ?? 'view') === 'edit';
        });
    }

    protected function isOwner(array $record, string $currentKey): bool
    {
        return (string) ($record['ownerKey'] ?? '') === $currentKey;
    }

    protected function resolveUserKey(Request $request): string
    {
        return (string) (
            optional(auth()->user())->id
            ?? $request->session()->get('user.id')
            ?? $request->session()->get('user.email')
            ?? 'anon'
        );
    }

    protected function resolveUserName(Request $request): string
    {
        return (string) (
            optional(auth()->user())->name
            ?? $request->session()->get('user.name')
            ?? 'Usuario'
        );
    }
}
