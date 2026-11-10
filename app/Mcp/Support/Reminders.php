<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use App\Support\ProjectBoard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Recordatorios del encabezado ("campanita") del usuario actual, con el mismo formato que guarda la app. */
class Reminders
{
    public const FILE = 'header_reminders.json';

    public static function key(): string
    {
        $user = Auth::user();

        return 'user:' . Str::slug(Str::lower(trim((string) ($user->email ?: $user->id))), '-');
    }

    public static function row(): ?array
    {
        return (new FileStore(self::FILE))->find(self::key());
    }

    public static function payload(): array
    {
        $payload = self::row()['payload'] ?? null;

        return is_array($payload) ? $payload : ['categories' => [], 'sections' => [], 'items' => []];
    }

    public static function projectLink(McpContext $ctx, string $projectId, ?string $taskId, ?string $column): array
    {
        $ctx->allow('proyectos.read');
        $project = (new FileStore('proyectos.json'))->find($projectId);
        abort_if(!$project || !empty($project['archived']), 404);
        $projectTitle = (string) ($project['titulo'] ?? 'Proyecto');

        if ($taskId !== null && $taskId !== '') {
            $task = collect($project['tareas'] ?? [])->firstWhere('id', $taskId);
            abort_if(!$task, 404);

            return ['type' => 'task', 'id' => $taskId, 'projectId' => $projectId, 'taskId' => $taskId,
                'title' => (string) ($task['texto'] ?? 'Tarea'), 'subtitle' => $projectTitle];
        }

        if ($column !== null && trim($column) !== '') {
            $stage = collect(ProjectBoard::stages($project))->first(fn ($name) => $name === trim($column));
            abort_if($stage === null, 404);

            return ['type' => 'list', 'id' => $stage, 'projectId' => $projectId, 'title' => $stage, 'subtitle' => $projectTitle];
        }

        return ['type' => 'project', 'id' => $projectId, 'projectId' => $projectId, 'title' => $projectTitle, 'subtitle' => 'Proyecto'];
    }

    public static function linkUrl(array $link): string
    {
        $id = (string) ($link['id'] ?? '');
        $projectId = (string) ($link['projectId'] ?? '');
        $query = match ($link['type'] ?? '') {
            'task' => ['view' => 'kanban', 'open_project' => $projectId, 'open_task' => $id ?: ($link['taskId'] ?? '')],
            'list' => ['open_project' => $projectId, 'open_list' => $id],
            default => ['open_project' => $projectId ?: $id],
        };

        return url('/proyectos') . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private static function same(string $a, string $b): bool
    {
        return Str::lower(Str::ascii(trim($a))) === Str::lower(Str::ascii(trim($b)));
    }

    public static function findCategory(array $payload, string $name): ?array
    {
        return collect($payload['categories'] ?? [])->first(fn ($c) => self::same((string) ($c['title'] ?? ''), $name) || (string) ($c['id'] ?? '') === $name);
    }

    public static function findSection(array $payload, string $name, ?string $categoryId = null): ?array
    {
        return collect($payload['sections'] ?? [])->first(fn ($s) => (self::same((string) ($s['title'] ?? ''), $name) || (string) ($s['id'] ?? '') === $name)
            && ($categoryId === null || (string) ($s['categoryId'] ?? '') === $categoryId));
    }

    /**
     * Devuelve la lista (section) donde guardar un recordatorio: la pedida, la de la categoría pedida
     * o la primera. Crea categoría o lista si se nombran y no existen.
     */
    public static function targetSection(array &$payload, ?string $category, ?string $list): string
    {
        $payload['categories'] = array_values($payload['categories'] ?? []);
        $payload['sections'] = array_values($payload['sections'] ?? []);
        $cat = null;
        if ($category !== null && trim($category) !== '') {
            $cat = self::findCategory($payload, $category);
            if (!$cat) {
                $cat = ['id' => (string) Str::ulid(), 'title' => Str::limit(trim($category), 120, ''), 'collapsed' => false];
                $payload['categories'][] = $cat;
            }
        }
        if ($list !== null && trim($list) !== '') {
            $section = self::findSection($payload, $list, $cat['id'] ?? null);
            if (!$section) {
                $cat ??= $payload['categories'][0] ?? null;
                if (!$cat) {
                    $cat = ['id' => (string) Str::ulid(), 'title' => 'Recordatorios', 'collapsed' => false];
                    $payload['categories'][] = $cat;
                }
                $section = ['id' => (string) Str::ulid(), 'categoryId' => $cat['id'], 'title' => Str::limit(trim($list), 120, ''), 'collapsed' => false];
                $payload['sections'][] = $section;
            }

            return $section['id'];
        }
        if ($cat) {
            $section = collect($payload['sections'])->firstWhere('categoryId', $cat['id']);
            if (!$section) {
                $section = ['id' => (string) Str::ulid(), 'categoryId' => $cat['id'], 'title' => $cat['title'], 'collapsed' => false];
                $payload['sections'][] = $section;
            }

            return $section['id'];
        }

        return (string) ($payload['sections'][0]['id'] ?? 'default');
    }

    /** "Categoría › Lista" de un recordatorio. */
    public static function placeOf(array $payload, array $item): string
    {
        $section = collect($payload['sections'] ?? [])->firstWhere('id', $item['sectionId'] ?? null);
        $cat = $section ? collect($payload['categories'] ?? [])->firstWhere('id', $section['categoryId'] ?? null) : null;
        if (!$section) {
            return 'Sin lista';
        }
        $catTitle = (string) ($cat['title'] ?? '');

        return $catTitle !== '' && !self::same($catTitle, (string) $section['title']) ? "{$catTitle} › {$section['title']}" : (string) $section['title'];
    }
}
