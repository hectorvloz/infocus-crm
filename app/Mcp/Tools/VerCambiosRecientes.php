<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\McpHistory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_cambios_recientes')]
#[Description('Lista las ediciones que hizo el MCP (notas, proyectos, tareas, subtareas, leads, recordatorios) en los últimos 30 días, con su cambio_id para restaurarlas con restaurar_cambio.')]
#[IsReadOnly]
class VerCambiosRecientes extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['texto' => $schema->string()->description('Filtra por nombre de la nota, proyecto, tarea o lead.')];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $q = (string) ($request->validate(['texto' => 'nullable|string|max:120'])['texto'] ?? '');
        $rows = collect((new McpHistory())->recent(200))
            ->filter(fn ($e) => $this->matches($e['label'] ?? '', $q))
            ->take(20)
            ->map(fn ($e) => sprintf('- %s · %s (cambio_id: %s)%s',
                Carbon::parse($e['created_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i'),
                $e['label'], $e['id'], !empty($e['restored_at']) ? ' · ya restaurado' : ''));

        return Response::text($rows->isEmpty() ? 'No hay ediciones del MCP para restaurar.' : "Del más reciente al más antiguo:\n" . $rows->implode("\n"));
    }
}
