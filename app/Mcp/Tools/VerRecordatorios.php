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
#[Description('Muestra tus recordatorios pendientes de Infocus ordenados por fecha, con su recordatorio_id.')]
#[IsReadOnly]
class VerRecordatorios extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['incluir_hechos' => $schema->boolean()];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read');
        $all = (bool) ($request->validate(['incluir_hechos' => 'nullable|boolean'])['incluir_hechos'] ?? false);
        $today = now()->toDateString();
        $rows = collect(Reminders::payload()['items'] ?? [])
            ->filter(fn ($i) => $all || empty($i['done']))
            ->sortBy(fn ($i) => ($i['dueDate'] ?? '') ?: '9999')
            ->take(50)
            ->map(fn ($i) => sprintf('- [%s] %s%s%s%s (recordatorio_id: %s)', !empty($i['done']) ? 'x' : ' ', $i['text'] ?? '',
                !empty($i['dueDate']) ? ' · ' . $i['dueDate'] . ($i['dueDate'] < $today && empty($i['done']) ? ' (vencido)' : '') : '',
                !empty($i['priority']) ? " · {$i['priority']}" : '',
                !empty($i['link']['title']) ? " · {$i['link']['title']}" : '', $i['id'] ?? '-'));

        return Response::text($rows->isEmpty() ? 'No tienes recordatorios pendientes.' : $rows->implode("\n"));
    }
}
