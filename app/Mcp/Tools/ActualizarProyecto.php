<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_proyecto')]
#[Description('Cambia datos de un proyecto: título, etapa, descripción, vencimiento, prioridad, avance o valor. Solo cambia los campos que envíes.')]
class ActualizarProyecto extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'titulo' => $schema->string(),
            'etapa' => $schema->string()->description('Etapa del tablero de proyectos (usa una existente).'),
            'descripcion' => $schema->string(),
            'vencimiento' => $schema->string()->description('AAAA-MM-DD.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente']),
            'progreso' => $schema->integer()->description('Avance de 0 a 100.'),
            'valor' => $schema->number(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.update', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'titulo' => 'nullable|string|max:255', 'etapa' => 'nullable|string|max:80',
            'descripcion' => 'nullable|string|max:20000', 'vencimiento' => 'nullable|date_format:Y-m-d',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente', 'progreso' => 'nullable|integer|min:0|max:100', 'valor' => 'nullable|numeric|min:0',
        ]);
        $changes = array_filter(collect($d)->except('proyecto_id')->all(), fn ($v) => $v !== null);
        if (!$changes) {
            return Response::error('Indica al menos un campo para cambiar.');
        }
        $project = $this->store('proyectos.json')->find($d['proyecto_id']);
        abort_if(!$project, 404);

        $result = $this->callController(ProyectosController::class, 'actualizar', ['id' => $d['proyecto_id']] + $changes);
        if ($result['status'] >= 300 || empty($result['data']['ok'] ?? true)) {
            return Response::error('No se pudo actualizar el proyecto.');
        }

        return Response::text("Proyecto \"{$project['titulo']}\" actualizado: " . implode(', ', array_keys($changes)) . '.');
    }
}
