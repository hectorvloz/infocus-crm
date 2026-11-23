<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_tarea')]
#[Description('Agrega una tarea a un proyecto (usa listar_proyectos para el id). Con "columna" la pone en esa columna del tablero; si la columna no existe, créala antes con gestionar_columnas. Las subtareas se agregan después con agregar_subtareas.')]
class CrearTarea extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'titulo' => $schema->string()->required(),
            'descripcion' => $schema->string(),
            'vence' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente']),
            'columna' => $schema->string()->description('Columna del tablero donde va la tarea (debe existir). Por defecto, la primera.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.create', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'titulo' => 'required|string|max:255', 'descripcion' => 'nullable|string|max:4000',
            'vence' => 'nullable|date_format:Y-m-d', 'prioridad' => 'nullable|in:Con calma,Atención,Urgente',
            'columna' => 'nullable|string|max:60',
        ]);
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);
        $stages = \App\Support\ProjectBoard::stages($project);
        $columna = $stages[0];
        if (!empty($d['columna'])) {
            $columna = GestionarColumnas::buscar($stages, $d['columna']);
            if ($columna === null) {
                return Response::error("No existe la columna \"{$d['columna']}\" en \"{$project['titulo']}\". Créala con gestionar_columnas. Columnas: " . implode(', ', $stages) . '.');
            }
        }
        $result = $this->callController(ProyectosController::class, 'tareaAgregar', array_filter([
            'id' => $d['proyecto_id'], 'texto' => $d['titulo'], 'descripcion' => $d['descripcion'] ?? null,
            'due_date' => $d['vence'] ?? null, 'priority' => $d['prioridad'] ?? null, 'board_stage' => $columna,
        ]));
        if (empty($result['data']['ok'])) {
            return Response::error('No se pudo crear la tarea.');
        }

        $nueva = collect($result['data']['item']['tareas'] ?? [])->last();
        $tareaId = is_array($nueva) ? ($nueva['id'] ?? null) : null;

        return Response::text("Tarea \"{$d['titulo']}\" agregada al proyecto {$result['data']['item']['titulo']} en la columna \"{$columna}\"" . ($tareaId ? " (tarea_id: {$tareaId})" : '') . '.');
    }
}
