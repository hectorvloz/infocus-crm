<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_tarea')]
#[Description('Edita una tarea de un proyecto (título, descripción, vencimiento, prioridad o columna del tablero) o la marca como hecha o pendiente. Usa ver_proyecto para obtener el tarea_id.')]
class ActualizarTarea extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'tarea_id' => $schema->string()->required(),
            'titulo' => $schema->string(),
            'descripcion' => $schema->string(),
            'vence' => $schema->string()->description('AAAA-MM-DD.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente']),
            'columna' => $schema->string()->description('Columna del tablero, ej. Por hacer, En progreso, Hecho.'),
            'hecha' => $schema->boolean()->description('true para completarla, false para reabrirla.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.update', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'tarea_id' => 'required|string', 'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:20000', 'vence' => 'nullable|date_format:Y-m-d',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente', 'columna' => 'nullable|string|max:80', 'hecha' => 'nullable|boolean',
        ]);
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);
        $task = collect($project['tareas'] ?? [])->firstWhere('id', $d['tarea_id']);
        abort_if(!$task, 404);

        $edits = array_filter([
            'texto' => $d['titulo'] ?? null, 'descripcion' => $d['descripcion'] ?? null, 'due_date' => $d['vence'] ?? null,
            'priority' => $d['prioridad'] ?? null, 'board_stage' => $d['columna'] ?? null,
        ], fn ($v) => $v !== null);
        if (!$edits && !isset($d['hecha'])) {
            return Response::error('Indica qué quieres cambiar en la tarea.');
        }

        $done = [];
        if ($edits) {
            $result = $this->callController(ProyectosController::class, 'tareaActualizar', ['id' => $project['id'], 'tarea_id' => $task['id'], 'texto' => $task['texto'] ?? 'Tarea'] + $edits);
            if ($result['status'] >= 300) {
                return Response::error('No se pudo actualizar la tarea.');
            }
            $done[] = 'editada';
        }
        if (isset($d['hecha']) && (bool) ($task['done'] ?? false) !== (bool) $d['hecha']) {
            $this->callController(ProyectosController::class, 'tareaToggle', ['id' => $project['id'], 'tarea_id' => $task['id']]);
            $done[] = $d['hecha'] ? 'marcada como hecha' : 'reabierta';
        }

        return Response::text('Tarea "' . ($d['titulo'] ?? $task['texto']) . '" ' . ($done ? implode(' y ', $done) : 'sin cambios (ya estaba así)') . '.');
    }
}
