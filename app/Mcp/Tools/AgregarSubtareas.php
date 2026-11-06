<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('agregar_subtareas')]
#[Description('Agrega una o varias subtareas (checklist) a una tarea de proyecto. Usa ver_proyecto para obtener el tarea_id.')]
class AgregarSubtareas extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'tarea_id' => $schema->string()->required(),
            'subtareas' => $schema->array()->items($schema->string())->description('Textos de las subtareas, en orden.')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.create', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'tarea_id' => 'required|string',
            'subtareas' => 'required|array|min:1|max:30', 'subtareas.*' => 'required|string|max:500',
        ]);
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);
        $task = collect($project['tareas'] ?? [])->firstWhere('id', $d['tarea_id']);
        abort_if(!$task, 404);

        $ok = $this->tracked('proyectos.json', $project['id'], 'Agregó subtareas a "' . ($task['texto'] ?? '') . '"', function () use ($d, $project, $task) {
            foreach ($d['subtareas'] as $text) {
                if ($this->callController(ProyectosController::class, 'subtareaAgregar', ['id' => $project['id'], 'tarea_id' => $task['id'], 'texto' => trim($text)])['status'] >= 300) {
                    return false;
                }
            }

            return true;
        });
        if (!$ok) {
            return Response::error('No se pudieron agregar todas las subtareas.');
        }

        return Response::text(count($d['subtareas']) . ' subtarea(s) agregada(s) a "' . ($task['texto'] ?? 'Tarea') . '".');
    }
}
