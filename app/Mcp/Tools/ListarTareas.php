<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_tareas')]
#[Description('Lista tareas de todos los proyectos activos (o de uno) con filtros: pendientes, vencidas, que vencen en N días, de un responsable, de una columna o con un texto. Devuelve proyecto_id y tarea_id para editarlas con actualizar_tarea.')]
#[IsReadOnly]
class ListarTareas extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string(),
            'estado' => $schema->string()->enum(['pendientes', 'vencidas', 'hechas', 'todas'])->description('Por defecto: pendientes.'),
            'vencen_en_dias' => $schema->integer()->description('Solo las que vencen en los próximos N días.'),
            'responsable' => $schema->string()->description('Nombre o parte del nombre.'),
            'columna' => $schema->string(),
            'texto' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.read');
        $d = $request->validate([
            'proyecto_id' => 'nullable|string', 'estado' => 'nullable|in:pendientes,vencidas,hechas,todas', 'vencen_en_dias' => 'nullable|integer|min:0|max:365',
            'responsable' => 'nullable|string|max:120', 'columna' => 'nullable|string|max:60', 'texto' => 'nullable|string|max:120',
        ]);
        $estado = $d['estado'] ?? 'pendientes';
        $today = now()->toDateString();
        $limit = isset($d['vencen_en_dias']) ? now()->addDays((int) $d['vencen_en_dias'])->toDateString() : null;

        $rows = collect($this->store('proyectos.json')->all())
            ->filter(fn ($p) => empty($p['archived']) && (empty($d['proyecto_id']) || ($p['id'] ?? '') === $d['proyecto_id']))
            ->flatMap(fn ($p) => collect($p['tareas'] ?? [])->map(fn ($t) => $t + ['_p' => $p]))
            ->filter(function ($t) use ($d, $estado, $today, $limit) {
                $due = (string) ($t['due_date'] ?? '');
                $done = !empty($t['done']);
                if ($estado === 'pendientes' && $done) return false;
                if ($estado === 'hechas' && !$done) return false;
                if ($estado === 'vencidas' && ($done || $due === '' || $due >= $today)) return false;
                if ($limit && ($done || $due === '' || $due > $limit)) return false;
                if (!empty($d['responsable']) && !$this->matches(implode(' ', (array) ($t['owners'] ?? [])), $d['responsable'])) return false;
                if (!empty($d['columna']) && !$this->matches((string) ($t['board_stage'] ?? ''), $d['columna'])) return false;
                if (!empty($d['texto']) && !$this->matches(($t['texto'] ?? '') . ' ' . strip_tags((string) ($t['descripcion'] ?? '')), $d['texto'])) return false;

                return true;
            })
            ->sortBy(fn ($t) => ($t['due_date'] ?? '') ?: '9999')
            ->take(60)
            ->map(fn ($t) => sprintf('- %s%s · %s · %s%s%s (proyecto_id: %s, tarea_id: %s)',
                !empty($t['done']) ? '[hecha] ' : '', $t['texto'] ?? 'Tarea', $t['_p']['titulo'] ?? 'Proyecto', $t['board_stage'] ?? 'Por hacer',
                !empty($t['due_date']) ? ' · vence ' . $t['due_date'] . (!empty($t['done']) || $t['due_date'] >= $today ? '' : ' (vencida)') : '',
                !empty($t['owners']) ? ' · ' . implode(', ', (array) $t['owners']) : '', $t['_p']['id'] ?? '', $t['id'] ?? ''));

        return Response::text($rows->isEmpty() ? 'No encontré tareas con esos filtros.' : $rows->implode("\n"));
    }
}
