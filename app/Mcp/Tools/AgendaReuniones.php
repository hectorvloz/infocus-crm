<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('agenda_reuniones')]
#[Description('Reuniones del CRM y de leads en los próximos días (no incluye eventos personales de Google Calendar).')]
#[IsReadOnly]
class AgendaReuniones extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'desde' => $schema->string()->description('Fecha AAAA-MM-DD. Por defecto, hoy.'),
            'dias' => $schema->integer()->description('Cuántos días mostrar (1 a 31). Por defecto 7.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('reuniones.read');
        $d = $request->validate(['desde' => 'nullable|date', 'dias' => 'nullable|integer|min:1|max:31']);
        $tz = config('app.timezone');
        $start = Carbon::parse($d['desde'] ?? 'today', $tz)->startOfDay();
        $end = $start->copy()->addDays((int) ($d['dias'] ?? 7));

        $crm = collect($this->store('reuniones.json')->all())->map(fn ($m) => [
            'inicio' => $m['inicio_at'] ?? null, 'titulo' => $m['titulo'] ?? 'Reunión', 'con' => $m['cliente'] ?? '-', 'meet' => $m['meet_link'] ?? null,
        ]);
        $leads = collect($this->store('leads.json')->all())->flatMap(fn ($l) => collect($l['agenda'] ?? [])
            ->where('tipo', 'reunion_meet')
            ->map(fn ($a) => ['inicio' => $a['fecha_hora'] ?? null, 'titulo' => $a['titulo'] ?? 'Reunión', 'con' => 'Lead: ' . ($l['nombre'] ?? '-'), 'meet' => $a['meet_link'] ?? null]));

        $rows = $crm->merge($leads)
            ->filter(fn ($m) => $m['inicio'] && Carbon::parse($m['inicio'])->between($start, $end))
            ->sortBy('inicio')
            ->map(fn ($m) => sprintf('- %s · %s · %s%s', Carbon::parse($m['inicio'])->setTimezone($tz)->format('D Y-m-d H:i'), $m['titulo'], $m['con'], $m['meet'] ? ' · ' . $m['meet'] : ''));

        return Response::text($rows->isEmpty() ? 'No hay reuniones en ese rango.' : $rows->implode("\n"));
    }
}
