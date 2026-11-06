<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\RoleAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('resumen_semanal')]
#[Description('Resumen operativo: tareas vencidas y por vencer en los próximos 7 días, proyectos atrasados, leads sin seguimiento y reuniones de la semana. Úsalo para planear la semana.')]
#[IsReadOnly]
class ResumenSemanal extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['dias_sin_respuesta' => $schema->integer()->description('Días sin actividad para considerar un lead olvidado. Por defecto 7.')];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read');
        $idle = (int) ($request->validate(['dias_sin_respuesta' => 'nullable|integer|min:1|max:90'])['dias_sin_respuesta'] ?? 7);
        $user = auth()->user();
        $today = now()->startOfDay();
        $weekEnd = $today->copy()->addDays(7)->endOfDay();
        $date = fn ($v) => $v ? rescue(fn () => Carbon::parse($v), null, false) : null;
        $out = ['Semana del ' . $today->format('Y-m-d') . ' al ' . $weekEnd->format('Y-m-d')];

        if (RoleAccess::can($user, 'proyectos.read')) {
            $projects = collect($this->store('proyectos.json')->all())->reject(fn ($p) => !empty($p['archived']));
            $tasks = $projects->flatMap(fn ($p) => collect($p['tareas'] ?? [])->reject(fn ($t) => !empty($t['done']) || ($t['task_type'] ?? '') === 'card')
                ->map(fn ($t) => $t + ['_p' => $p['titulo'] ?? 'Proyecto', '_due' => $date($t['due_date'] ?? null)]))->filter(fn ($t) => $t['_due']);
            $line = fn ($t) => "- {$t['texto']} · {$t['_p']} · vence {$t['_due']->format('Y-m-d')}" . (!empty($t['owners']) ? ' · ' . implode(', ', $t['owners']) : '') . " (tarea_id: {$t['id']})";
            $overdue = $tasks->filter(fn ($t) => $t['_due']->lt($today))->sortBy('_due');
            $soon = $tasks->filter(fn ($t) => $t['_due']->between($today, $weekEnd))->sortBy('_due');
            $late = $projects->filter(fn ($p) => ($d = $date($p['vencimiento'] ?? null)) && $d->lt($today) && (int) ($p['progreso'] ?? 0) < 100);

            $out[] = "\nTareas vencidas ({$overdue->count()}):";
            $out[] = $overdue->isEmpty() ? 'Ninguna.' : $overdue->take(25)->map($line)->implode("\n");
            $out[] = "\nTareas que vencen esta semana ({$soon->count()}):";
            $out[] = $soon->isEmpty() ? 'Ninguna.' : $soon->take(25)->map($line)->implode("\n");
            $out[] = "\nProyectos atrasados ({$late->count()}):";
            $out[] = $late->isEmpty() ? 'Ninguno.' : $late->map(fn ($p) => "- {$p['titulo']} · entrega {$p['vencimiento']} · progreso " . (int) ($p['progreso'] ?? 0) . "% (proyecto_id: {$p['id']})")->implode("\n");
        }

        if (RoleAccess::can($user, 'leads.read')) {
            $limit = $today->copy()->subDays($idle);
            $leads = collect($this->store('leads.json')->all())->reject(fn ($l) => ($l['etapa'] ?? '') === 'Cliente')
                ->map(function ($l) use ($date) {
                    $dates = collect([$l['updated_at'] ?? null, $l['created_at'] ?? null])
                        ->merge(collect($l['notas_lista'] ?? [])->pluck('fecha'))->merge(collect($l['agenda'] ?? [])->pluck('creado_en'))
                        ->map($date)->filter();

                    return $l + ['_last' => $dates->max()];
                })
                ->filter(fn ($l) => !$l['_last'] || $l['_last']->lt($limit))->sortBy('_last');
            $out[] = "\nLeads sin seguimiento hace más de {$idle} días ({$leads->count()}):";
            $out[] = $leads->isEmpty() ? 'Ninguno.' : $leads->take(25)->map(fn ($l) => "- {$l['nombre']} · {$l['etapa']} · última actividad " . ($l['_last']?->format('Y-m-d') ?? 'sin registro') . " (lead_id: {$l['id']})")->implode("\n");
        }

        if (RoleAccess::can($user, 'reuniones.read')) {
            $meetings = collect($this->store('reuniones.json')->all())
                ->filter(fn ($m) => ($d = $date($m['fecha'] ?? null)) && $d->between($today, $weekEnd))->sortBy(fn ($m) => ($m['fecha'] ?? '') . ($m['hora_inicio'] ?? ''));
            $leadAgenda = RoleAccess::can($user, 'leads.read') ? collect($this->store('leads.json')->all())
                ->flatMap(fn ($l) => collect($l['agenda'] ?? [])->map(fn ($a) => $a + ['_lead' => $l['nombre'] ?? 'Lead']))
                ->filter(fn ($a) => ($a['estado'] ?? '') === 'programado' && ($d = $date($a['fecha_hora'] ?? null)) && $d->between($today, $weekEnd)) : collect();
            $rows = $meetings->map(fn ($m) => "- {$m['fecha']} {$m['hora_inicio']} · {$m['titulo']} · " . ($m['cliente'] ?? ''))
                ->merge($leadAgenda->map(fn ($a) => '- ' . Carbon::parse($a['fecha_hora'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i') . " · {$a['titulo']} · lead {$a['_lead']}"))->sort()->values();
            $out[] = "\nReuniones de la semana ({$rows->count()}):";
            $out[] = $rows->isEmpty() ? 'Ninguna.' : $rows->implode("\n");
        }

        return Response::text(implode("\n", $out));
    }
}
