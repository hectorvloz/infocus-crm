<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('temporizador')]
#[Description('Maneja el temporizador de horas en proyectos, igual que el botón de la app: iniciar (en un proyecto y opcionalmente una tarea; usa listar_proyectos o ver_proyecto para los ids), pausar, terminar (guarda el tiempo) o estado. Para "empecé con Somos Domus" usa iniciar; para "terminé" usa terminar.')]
class Temporizador extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'accion' => $schema->string()->enum(['iniciar', 'pausar', 'terminar', 'estado'])->required(),
            'proyecto_id' => $schema->string()->description('Obligatorio para iniciar.'),
            'tarea_id' => $schema->string()->description('Opcional: la tarea en la que trabajas.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate(['accion' => 'required|in:iniciar,pausar,terminar,estado', 'proyecto_id' => 'nullable|string', 'tarea_id' => 'nullable|string']);
        $ctx->allow('proyectos.update', write: $d['accion'] !== 'estado');

        $current = $this->current();
        if ($d['accion'] === 'estado') {
            return Response::text($current ? $this->describe($current) : 'No tienes ningún temporizador corriendo ni en pausa.');
        }

        if ($d['accion'] === 'iniciar') {
            abort_if(empty($d['proyecto_id']), 422, 'Falta el proyecto.');
            $result = $this->callController(ProyectosController::class, 'timerAccion', array_filter(['id' => $d['proyecto_id'], 'action' => 'start', 'tarea_id' => $d['tarea_id'] ?? null]));
            if ($result['status'] >= 300) {
                return Response::error('No se pudo iniciar el temporizador en ese proyecto.');
            }
            $now = $this->current();

            return Response::text('Temporizador iniciado. ' . ($now ? $this->describe($now) : ''));
        }

        if (!$current) {
            return Response::error('No hay ningún temporizador activo para ' . ($d['accion'] === 'pausar' ? 'pausar.' : 'terminar.'));
        }
        if ($d['accion'] === 'pausar' && !$current['running']) {
            return Response::text('Ya estaba en pausa. ' . $this->describe($current));
        }

        $result = $this->callController(ProyectosController::class, 'timerAccion', array_filter([
            'id' => $current['project_id'], 'action' => 'stop', 'tarea_id' => $current['task_id'] ?: null, 'close' => $d['accion'] === 'terminar' ? true : null,
        ], fn ($v) => $v !== null));
        if ($result['status'] >= 300) {
            return Response::error('No se pudo ' . $d['accion'] . ' el temporizador.');
        }

        return Response::text(($d['accion'] === 'terminar' ? 'Tiempo guardado' : 'En pausa') . ': ' . $this->duration((int) $current['current_seconds']) . ' en ' . $current['project_title'] . ($current['task_id'] ? ' · ' . $current['task_name'] : '') . '.');
    }

    private function current(): ?array
    {
        $result = $this->callController(ProyectosController::class, 'activeTimer', []);
        $item = $result['data']['item'] ?? null;

        return is_array($item) ? $item : null;
    }

    private function describe(array $item): string
    {
        return sprintf('%s: %s%s · %s (%s).', $item['running'] ? 'Corriendo' : 'En pausa', $item['project_title'], $item['task_id'] ? ' · ' . $item['task_name'] : '', $this->duration((int) $item['current_seconds']), $item['client']);
    }

    private function duration(int $seconds): string
    {
        return sprintf('%d h %02d min', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }
}
