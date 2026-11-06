<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use App\Support\SafeRichText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Acceso nota por nota a "Mis notas" con las mismas reglas de la app: el dueño ve y edita,
 * los colaboradores ven y editan solo si tienen modo "edit". El guardado de la pantalla
 * reemplaza la lista completa del usuario, por eso aquí se escribe cada nota por separado.
 */
class PersonalNotes
{
    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('mis_notas.json');
    }

    public static function userKey(): string
    {
        return (string) Auth::id();
    }

    public function visible(): array
    {
        $key = self::userKey();

        return collect($this->store->all())
            ->filter(fn ($n) => $this->isOwner($n, $key) || collect($n['collaborators'] ?? [])->contains(fn ($c) => (string) ($c['userKey'] ?? '') === $key))
            ->sortByDesc('updatedAt')
            ->values()
            ->all();
    }

    public function find(string $id): ?array
    {
        return collect($this->visible())->firstWhere('id', $id);
    }

    public function canEdit(array $note): bool
    {
        $key = self::userKey();

        return $this->isOwner($note, $key)
            || collect($note['collaborators'] ?? [])->contains(fn ($c) => (string) ($c['userKey'] ?? '') === $key && ($c['mode'] ?? 'view') === 'edit');
    }

    public function isOwner(array $note, ?string $key = null): bool
    {
        return (string) ($note['ownerKey'] ?? '') === ($key ?? self::userKey());
    }

    public function create(string $title, string $html, string $color, ?array $client): array
    {
        $now = now()->valueOf();
        $clean = SafeRichText::sanitize($html);

        return $this->store->create([
            'id' => (string) Str::ulid(),
            'ownerKey' => self::userKey(),
            'ownerName' => (string) (Auth::user()->name ?? 'Usuario'),
            'title' => $title,
            'html' => $clean,
            'plainText' => self::plain($clean),
            'color' => $color,
            'linkedClient' => (string) ($client['empresa'] ?? ''),
            'clientId' => (string) ($client['id'] ?? ''),
            'collaborators' => [],
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    public function update(array $note, ?string $title, ?string $html): array
    {
        $changes = ['updatedAt' => now()->valueOf()];
        if ($title !== null) {
            $changes['title'] = $title;
        }
        if ($html !== null) {
            $changes['html'] = SafeRichText::sanitize($html);
            $changes['plainText'] = self::plain($changes['html']);
        }

        return $this->store->update($note['id'], $changes);
    }

    public static function plain(string $html): string
    {
        $text = preg_replace(['/<(br|\/p|\/h[1-6]|\/li|hr)[^>]*>/i'], "\n", $html);

        return trim(html_entity_decode(strip_tags((string) $text)));
    }
}
