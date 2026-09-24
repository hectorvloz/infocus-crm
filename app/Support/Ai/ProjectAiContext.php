<?php

namespace App\Support\Ai;

use Illuminate\Support\Str;

class ProjectAiContext
{
    public function __construct(private readonly SensitiveDataFilter $filter = new SensitiveDataFilter())
    {
    }

    public function summarize(array $project, ?string $selectedTaskId = null): string
    {
        $tasks = array_values(array_filter((array) ($project['tareas'] ?? []), 'is_array'));
        $selected = null;
        foreach ($tasks as $task) {
            if ($selectedTaskId !== null && (string) ($task['id'] ?? '') === $selectedTaskId) {
                $selected = $task;
                break;
            }
        }

        $lines = [
            'Proyecto abierto: ' . $this->text($project['titulo'] ?? 'Proyecto', 180),
            'Cliente: ' . $this->text($project['cliente'] ?? 'Sin cliente', 120),
            'Estado: ' . $this->text($project['etapa'] ?? 'Sin estado', 80),
            'Prioridad: ' . $this->text($project['prioridad'] ?? 'Sin prioridad', 80),
            'Vencimiento: ' . $this->text($project['vencimiento'] ?? 'Sin fecha', 40),
            'Columnas: ' . $this->text(implode(', ', (array) ($project['task_stages'] ?? [])), 500),
            'Descripción del proyecto: ' . $this->text($project['descripcion'] ?? '', 2800),
        ];

        if ($selected) {
            $lines[] = 'Tarjeta abierta: ' . $this->text($selected['texto'] ?? 'Tarjeta', 180);
            $lines[] = 'Contenido de la tarjeta abierta: ' . $this->taskDetails($selected, 3000);
        }

        $lines[] = 'Otras tarjetas y tareas del proyecto (' . count($tasks) . ' en total):';
        foreach (array_slice($tasks, 0, 40) as $task) {
            if ($selected && (string) ($task['id'] ?? '') === (string) ($selected['id'] ?? '')) {
                continue;
            }
            $lines[] = '- ' . $this->text($task['texto'] ?? 'Tarjeta', 180)
                . ' | ' . $this->taskDetails($task, 550);
        }

        return mb_substr(implode("\n", $lines), 0, 14000);
    }

    public function findMentionedProject(array $projects, string $message): ?array
    {
        $normalized = Str::lower(Str::ascii($message));
        $matches = array_filter($projects, function ($project) use ($normalized) {
            if (!is_array($project)) return false;
            $title = Str::lower(Str::ascii(trim((string) ($project['titulo'] ?? ''))));
            return mb_strlen($title) >= 4
                && preg_match('/(?<![a-z0-9])' . preg_quote($title, '/') . '(?![a-z0-9])/u', $normalized) === 1;
        });
        usort($matches, fn ($a, $b) => mb_strlen((string) ($b['titulo'] ?? '')) <=> mb_strlen((string) ($a['titulo'] ?? '')));
        return $matches[0] ?? null;
    }

    public function clientContext(array $project, array $client, array $projects): string
    {
        $clientId = trim((string) ($project['cliente_id'] ?? ''));
        if ($clientId === '' || $clientId !== trim((string) ($client['id'] ?? ''))) {
            return '';
        }

        $lines = ['Cliente asociado al proyecto: ' . $this->text($client['empresa'] ?? 'Cliente', 180)];
        foreach ([
            'categoria' => 'Categoría',
            'etiquetas' => 'Etiquetas',
            'direccion' => 'Dirección',
            'ciudad' => 'Ciudad',
            'pais' => 'País',
            'website' => 'Sitio web',
        ] as $field => $label) {
            if (!empty($client[$field])) {
                $lines[] = "{$label}: " . $this->text($client[$field], 300);
            }
        }

        $related = array_values(array_filter($projects, fn ($item) => is_array($item)
            && (string) ($item['cliente_id'] ?? '') === $clientId
            && (string) ($item['id'] ?? '') !== (string) ($project['id'] ?? '')));
        usort($related, fn ($a, $b) => strcmp(
            (string) ($b['updated_at'] ?? $b['created_at'] ?? ''),
            (string) ($a['updated_at'] ?? $a['created_at'] ?? '')
        ));
        $lines[] = 'Proyectos anteriores de este mismo cliente:';
        foreach (array_slice($related, 0, 6) as $item) {
            $taskSummaries = array_map(fn ($task) => $this->text($task['texto'] ?? '', 100)
                . ' [' . $this->taskDetails($task, 320) . ']',
                array_slice(array_filter((array) ($item['tareas'] ?? []), 'is_array'), 0, 5));
            $lines[] = '- ' . $this->text($item['titulo'] ?? 'Proyecto', 180)
                . ' | Descripción: ' . $this->text($item['descripcion'] ?? '', 650)
                . ($taskSummaries ? ' | Tarjetas: ' . implode('; ', $taskSummaries) : '');
        }

        return mb_substr(implode("\n", $lines), 0, 6500);
    }

    public function clientNotesContext(array $project, array $notes, string $userKey): string
    {
        $clientId = trim((string) ($project['cliente_id'] ?? ''));
        if ($clientId === '' || $userKey === '') {
            return '';
        }

        $noteClient = new NoteClient();
        $visible = array_values(array_filter($notes, function ($note) use ($clientId, $userKey, $noteClient) {
            if (!is_array($note) || !$noteClient->belongsTo($note, $clientId)) {
                return false;
            }

            return (string) ($note['ownerKey'] ?? '') === $userKey
                || collect($note['collaborators'] ?? [])->contains(
                    fn ($collaborator) => (string) ($collaborator['userKey'] ?? '') === $userKey
                );
        }));

        usort($visible, function ($a, $b) {
            $timestamp = static function (array $note): int {
                $value = $note['updatedAt'] ?? $note['createdAt'] ?? 0;
                if (is_numeric($value)) return (int) $value;
                return strtotime((string) $value) ?: 0;
            };
            return $timestamp($b) <=> $timestamp($a);
        });

        if ($visible === []) {
            return '';
        }

        $lines = ['Notas visibles de Mis Notas vinculadas a este cliente:'];
        foreach (array_slice($visible, 0, 6) as $note) {
            $content = trim((string) ($note['plainText'] ?? ''));
            if ($content === '') {
                $content = (string) ($note['html'] ?? '');
            }
            $lines[] = '- ' . $this->text($note['title'] ?? 'Nota sin título', 180)
                . ' | ' . $this->text($content, 700);
        }

        return mb_substr(implode("\n", $lines), 0, 5000);
    }

    private function taskDetails(array $task, int $descriptionLimit): string
    {
        $subtasks = array_slice(array_filter((array) ($task['subtasks'] ?? []), 'is_array'), 0, 10);
        $parts = [
            'Columna: ' . $this->text($task['board_stage'] ?? 'Sin columna', 80),
            'Estado: ' . (!empty($task['done']) ? 'terminada' : 'pendiente'),
            'Descripción: ' . $this->text($task['descripcion'] ?? '', $descriptionLimit),
        ];
        if ($subtasks) {
            $parts[] = 'Checklist: ' . implode('; ', array_map(
                fn ($item) => $this->text($item['texto'] ?? '', 120),
                $subtasks
            ));
        }
        $notes = array_slice(array_filter((array) ($task['notes'] ?? []), 'is_array'), -3);
        if ($notes) {
            $parts[] = 'Notas: ' . implode('; ', array_map(
                fn ($item) => $this->text($item['texto'] ?? '', 220),
                $notes
            ));
        }
        return implode(' | ', $parts);
    }

    private function text(mixed $value, int $limit): string
    {
        $withSpaces = preg_replace('/<\/(?:p|div|h[1-6]|li)>/i', ' ', (string) $value) ?? (string) $value;
        $plain = html_entity_decode(strip_tags($withSpaces), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
        return mb_substr($this->filter->cleanText($plain), 0, $limit);
    }
}
