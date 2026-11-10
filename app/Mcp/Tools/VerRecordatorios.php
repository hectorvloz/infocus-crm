<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\Reminders;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_recordatorios')]
#[Description('Muestra tus recordatorios de Infocus agrupados por categoría y lista (ej. Espadas, Variado), con su recordatorio_id, fecha, prioridad y enlaces a proyectos, tareas o columnas. También lista las categorías y listas existentes.')]
#[IsReadOnly]
class VerRecordatorios extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'categoria' => $schema->string()->description('Opcional: solo esta categoría o lista.'),
            'incluir_hechos' => $schema->boolean(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read');
        $d = $request->validate(['categoria' => 'nullable|string|max:120', 'incluir_hechos' => 'nullable|boolean']);
        $payload = Reminders::payload();
        $today = now()->toDateString();
        $filter = trim((string) ($d['categoria'] ?? ''));

        $groups = collect($payload['items'] ?? [])
            ->filter(fn ($i) => !empty($d['incluir_hechos']) || empty($i['done']))
            ->filter(fn ($i) => $filter === '' || $this->matches(Reminders::placeOf($payload, $i), $filter))
            ->sortBy(fn ($i) => ($i['dueDate'] ?? '') ?: '9999')
            ->groupBy(fn ($i) => Reminders::placeOf($payload, $i));

        $lines = [];
        foreach ($groups as $place => $items) {
            $lines[] = "\n{$place} (" . count($items) . '):';
            foreach ($items->take(40) as $i) {
                $lines[] = sprintf('- [%s] %s%s%s%s (recordatorio_id: %s)', !empty($i['done']) ? 'x' : ' ', $i['text'] ?? '',
                    !empty($i['dueDate']) ? ' · ' . $i['dueDate'] . ($i['dueDate'] < $today && empty($i['done']) ? ' (vencido)' : '') : '',
                    !empty($i['priority']) ? " · {$i['priority']}" : '',
                    !empty($i['link']['title']) ? ' · vinculado a ' . $this->external($i['link']['title']) . ' (' . Reminders::linkUrl($i['link']) . ')' : '',
                    $i['id'] ?? '-');
            }
        }
        $structure = collect($payload['categories'] ?? [])->map(function ($c) use ($payload) {
            $lists = collect($payload['sections'] ?? [])->where('categoryId', $c['id'])->pluck('title')->implode(', ');

            return "- {$c['title']}" . ($lists !== '' ? " (listas: {$lists})" : '');
        })->implode("\n");

        return Response::text(($lines ? ltrim(implode("\n", $lines)) : 'No hay recordatorios pendientes' . ($filter !== '' ? " en «{$filter}»" : '') . '.')
            . ($structure !== '' ? "\n\nCategorías:\n{$structure}" : ''));
    }
}
