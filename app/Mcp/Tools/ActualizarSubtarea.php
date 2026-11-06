<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_subtarea')]
#[Description('Marca una subtarea como hecha o pendiente, o cambia su texto, vencimiento o prioridad. Usa ver_tarea para obtener el subtarea_id.')]
class ActualizarSubtarea extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'tarea_id' => $schema->string()->required(),
            'subtarea_id' => $schema->string()->required(),
            'hecha' => $schema->boolean(),
            'texto' => $schema->string(),
            'vence' => $schema->string()->description('AAAA-MM-DD.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente']),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.update', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'tarea_id' => 'required|string', 'subtarea_id' => 'required|string',
            'hecha' => 'nullable|boolean', 'texto' => 'nullable|string|max:500', 'vence' => 'nullable|date_format:Y-m-d',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente',
        ]);
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);
        $task = collect($project['tareas'] ?? [])->firstWhere('id', $d['tarea_id']);
        abort_if(!$task, 404);
        $sub = collect($task['subtasks'] ?? [])->firstWhere('id', $d['subtarea_id']);
        abort_if(!$sub, 404);

        $ids = ['id' => $project['id'], 'tarea_id' => $task['id'], 'subtarea_id' => $sub['id']];
        $edits = array_filter(['texto' => $d['texto'] ?? null, 'due_date' => $d['vence'] ?? null, 'priority' => $d['prioridad'] ?? null], fn ($v) => $v !== null);
        if (!$edits && !isset($d['hecha'])) {
            return Response::error('Indica qué quieres cambiar en la subtarea.');
        }

        $done = [];
        if ($edits) {
            $this->callController(ProyectosController::class, 'subtareaActualizar', $ids + $edits);
            $done[] = 'editada';
        }
        if (isset($d['hecha']) && (bool) ($sub['done'] ?? false) !== (bool) $d['hecha']) {
            $this->callController(ProyectosController::class, 'subtareaToggle', $ids);
            $done[] = $d['hecha'] ? 'marcada como hecha' : 'reabierta';
        }

        return Response::text('Subtarea "' . ($d['texto'] ?? $sub['texto']) . '" ' . ($done ? implode(' y ', $done) : 'sin cambios (ya estaba así)') . '.');
    }
}
