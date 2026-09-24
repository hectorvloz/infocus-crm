<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Illuminate\Support\Str;

class NoteClient
{
    private ?array $clients = null;

    public function resolve(array $note): ?array
    {
        $clients = $this->clients ??= (new FileStore('clientes.json'))->all();
        $id = trim((string) ($note['clientId'] ?? ''));
        if ($id !== '') {
            return collect($clients)->first(fn ($client) => (string) ($client['id'] ?? '') === $id);
        }

        $legacy = trim((string) ($note['linkedClient'] ?? ''));
        if ($legacy === '') {
            return null;
        }
        $byId = collect($clients)->first(fn ($client) => (string) ($client['id'] ?? '') === $legacy);
        if ($byId) {
            return $byId;
        }
        $name = Str::lower(Str::ascii($legacy));
        $matches = collect($clients)->filter(fn ($client) => Str::lower(Str::ascii(trim((string) ($client['empresa'] ?? '')))) === $name);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function belongsTo(array $note, string $clientId): bool
    {
        return $clientId !== '' && (string) ($this->resolve($note)['id'] ?? '') === $clientId;
    }
}
