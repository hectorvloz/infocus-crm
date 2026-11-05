<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_gastos')]
#[Description('Lista gastos por rango de fechas o categoría, con el total por categoría.')]
#[IsReadOnly]
class ListarGastos extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'desde' => $schema->string()->description('Fecha AAAA-MM-DD. Por defecto, inicio del mes actual.'),
            'hasta' => $schema->string()->description('Fecha AAAA-MM-DD. Por defecto, hoy.'),
            'categoria' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('gastos.read');
        $d = $request->validate(['desde' => 'nullable|date', 'hasta' => 'nullable|date', 'categoria' => 'nullable|string|max:80']);
        $desde = $d['desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $d['hasta'] ?? now()->toDateString();
        $gastos = collect($this->store('gastos.json')->all())
            ->filter(fn ($g) => ($g['fecha'] ?? '') >= $desde && substr((string) ($g['fecha'] ?? ''), 0, 10) <= $hasta)
            ->filter(fn ($g) => $this->matches($g['categoria'] ?? '', (string) ($d['categoria'] ?? '')))
            ->sortByDesc('fecha');

        $porCategoria = $gastos->groupBy(fn ($g) => $g['categoria'] ?: 'Sin categoría')
            ->map(fn ($grupo, $cat) => "- {$cat}: " . $this->money($grupo->sum(fn ($g) => (float) $g['monto']), $grupo->first()['moneda'] ?? null))
            ->implode("\n");
        $detalle = $gastos->take(40)->map(fn ($g) => sprintf('- %s · %s · %s · %s', $g['fecha'] ?? '-', $g['concepto'] ?? '-', $this->money($g['monto'] ?? 0, $g['moneda'] ?? null), $g['categoria'] ?? '-'))->implode("\n");

        return Response::text($gastos->isEmpty()
            ? "No hay gastos entre {$desde} y {$hasta}."
            : "Gastos entre {$desde} y {$hasta}\n\nPor categoría:\n{$porCategoria}\n\nDetalle:\n{$detalle}");
    }
}
