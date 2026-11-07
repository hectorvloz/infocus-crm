<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\ScheduledTasks;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('editar_tarea_programada')]
#[Description('Pausa, reanuda o cambia una tarea programada (usa ver_tareas_programadas para el id). Para "cancela lo de los lunes" ponla en activa=false: no se borra.')]
class EditarTareaProgramada extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'tarea_programada_id' => $schema->string()->required(),
            'activa' => $schema->boolean(),
            'texto' => $schema->string(),
            'hora' => $schema->string()->description('HH:MM, hora de Colombia.'),
            'dias' => $schema->array()->items($schema->string())->description('Para semanal.'),
            'canal' => $schema->string()->enum(['whatsapp', 'correo']),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'tarea_programada_id' => 'required|string',
            'activa' => 'nullable|boolean',
            'texto' => 'nullable|string|max:1000',
            'hora' => ['nullable', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'dias' => 'nullable|array',
            'dias.*' => 'string|max:20',
            'canal' => 'nullable|in:whatsapp,correo',
        ]);

        $task = collect(ScheduledTasks::forUser())->firstWhere('id', $d['tarea_programada_id']);
        abort_if(!$task, 404);

        foreach (['texto', 'canal'] as $field) {
            if (isset($d[$field])) {
                $task[$field] = trim((string) $d[$field]);
            }
        }
        if (isset($d['hora'])) {
            [$h, $m] = explode(':', $d['hora']);
            $task['hora'] = sprintf('%02d:%02d', (int) $h, (int) $m);
        }
        if (isset($d['dias'])) {
            $task['dias'] = ScheduledTasks::parseDays($d['dias']);
        }
        if (array_key_exists('activa', $d) && $d['activa'] !== null) {
            $task['activa'] = (bool) $d['activa'];
        }

        if (!empty($task['activa'])) {
            $next = ScheduledTasks::nextRun($task, CarbonImmutable::now(ScheduledTasks::TZ));
            if (!$next) {
                return Response::error('Con esos datos ya no queda ninguna fecha futura.');
            }
            $task['proxima'] = $next->toIso8601String();
        }

        ScheduledTasks::store()->update($task['id'], $task);

        return Response::text("Actualizado:\n" . ScheduledTasks::describe($task));
    }
}
