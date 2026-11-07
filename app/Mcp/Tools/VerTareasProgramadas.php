<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\ScheduledTasks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_tareas_programadas')]
#[Description('Lista tus avisos y consultas programadas (programar_tarea): qué hacen, cuándo, por qué canal, si están activas, la próxima vez y el último resultado.')]
#[IsReadOnly]
class VerTareasProgramadas extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'incluir_pausadas' => $schema->boolean(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read');
        $withPaused = (bool) $request->get('incluir_pausadas', false);
        $tasks = collect(ScheduledTasks::forUser())
            ->filter(fn ($t) => $withPaused || !empty($t['activa']))
            ->sortBy(fn ($t) => $t['proxima'] ?? '9999');

        if ($tasks->isEmpty()) {
            return Response::text('No tienes tareas programadas' . ($withPaused ? '.' : ' activas.'));
        }

        return Response::text($tasks->map(function ($t) {
            $line = ScheduledTasks::describe($t);
            if (!empty($t['ultima'])) {
                $line .= ' · última: ' . $t['ultima'] . (!empty($t['ultimo_estado']) ? ' (' . $t['ultimo_estado'] . ')' : '');
            }

            return $line;
        })->implode("\n"));
    }
}
