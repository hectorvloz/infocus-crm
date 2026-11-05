<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('buscar_facturas')]
#[Description('Busca facturas por cliente, número, estado o rango de fechas. Para "¿quién me debe?" usa estado Pendiente o Vencida.')]
#[IsReadOnly]
class BuscarFacturas extends InfocusTool
{
    public const ESTADOS = ['En borrador', 'Pendiente', 'Enviada', 'Vencida', 'Pagada'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Número de factura o nombre del cliente.'),
            'cliente_id' => $schema->string()->description('Id del cliente.'),
            'estado' => $schema->string()->enum(self::ESTADOS),
            'desde' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'hasta' => $schema->string()->description('Fecha AAAA-MM-DD.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.read');
        $d = $request->validate([
            'texto' => 'nullable|string|max:120', 'cliente_id' => 'nullable|string', 'estado' => 'nullable|in:' . implode(',', self::ESTADOS),
            'desde' => 'nullable|date', 'hasta' => 'nullable|date',
        ]);
        $rows = collect($this->store('facturas.json')->all())
            ->when(!empty($d['cliente_id']), fn ($c) => $c->where('cliente_id', $d['cliente_id']))
            ->when(!empty($d['estado']), fn ($c) => $c->where('estado', $d['estado']))
            ->when(!empty($d['desde']), fn ($c) => $c->filter(fn ($f) => ($f['fecha'] ?? '') >= $d['desde']))
            ->when(!empty($d['hasta']), fn ($c) => $c->filter(fn ($f) => ($f['fecha'] ?? '') <= $d['hasta']))
            ->filter(fn ($f) => $this->matches(($f['numero'] ?? '') . ' ' . ($f['cliente'] ?? ''), (string) ($d['texto'] ?? '')))
            ->sortByDesc('fecha')
            ->take(30)
            ->map(fn ($f) => sprintf('- %s · %s · %s · total %s · saldo %s · %s · vence %s (id: %s)',
                $f['numero'] ?? '-', $f['cliente'] ?? '-', $f['fecha'] ?? '-', $this->money($f['total'] ?? 0, $f['moneda'] ?? null),
                $this->money($f['saldo'] ?? $f['total'] ?? 0, $f['moneda'] ?? null), $f['estado'] ?? '-', $f['vencimiento'] ?? '-', $f['id']));

        return Response::text($rows->isEmpty() ? 'No encontré facturas con ese criterio.' : $rows->implode("\n"));
    }
}
