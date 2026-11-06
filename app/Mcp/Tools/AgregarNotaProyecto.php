<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('agregar_nota_proyecto')]
#[Description('Agrega un comentario (nota) a una tarea de un proyecto, firmado con tu nombre. Sin tarea_id, lo deja en la tarjeta "Notas generales" del proyecto (la crea si no existe).')]
class AgregarNotaProyecto extends InfocusTool
{
    public const GENERAL = 'Notas generales';

    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'texto' => $schema->string()->description('Texto del comentario.')->required(),
            'tarea_id' => $schema->string()->description('Opcional: tarea donde dejar la nota.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.create', write: true);
        $d = $request->validate(['proyecto_id' => 'required|string', 'texto' => 'required|string|max:10000', 'tarea_id' => 'nullable|string']);
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);

        $taskId = $d['tarea_id'] ?? null;
        if ($taskId) {
            abort_if(!collect($project['tareas'] ?? [])->firstWhere('id', $taskId), 404);
        } else {
            $general = collect($project['tareas'] ?? [])->first(fn ($t) => ($t['texto'] ?? '') === self::GENERAL);
            if (!$general) {
                $created = $this->callController(ProyectosController::class, 'tareaAgregar', ['id' => $project['id'], 'texto' => self::GENERAL, 'task_type' => 'card']);
                $general = collect($created['data']['item']['tareas'] ?? [])->last();
            }
            $taskId = $general['id'] ?? null;
        }

        $result = $this->callController(ProyectosController::class, 'tareaNotaAgregar', ['id' => $project['id'], 'tarea_id' => $taskId, 'texto' => $d['texto']]);
        if ($result['status'] >= 300) {
            return Response::error('No se pudo agregar la nota.');
        }

        return Response::text("Comentario agregado en el proyecto \"{$project['titulo']}\".");
    }
}
