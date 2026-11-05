<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_factura')]
#[Description('Detalle de una factura: conceptos, impuestos, pagos y saldo. Acepta el id o el número (ej. INV-0045).')]
#[IsReadOnly]
class VerFactura extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['factura' => $schema->string()->description('Id o número de la factura.')->required()];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.read');
        $key = (string) $request->validate(['factura' => 'required|string|max:120'])['factura'];
        $f = $this->find($key);
        abort_if(!$f, 404);
        $cur = $f['moneda'] ?? null;

        $out = [
            "Factura {$f['numero']} (id: {$f['id']}) · {$f['cliente']} · estado: " . ($f['estado'] ?? '-'),
            'Fecha: ' . ($f['fecha'] ?? '-') . ' · Vence: ' . ($f['vencimiento'] ?? '-') . " · Moneda: {$cur}",
            "\nConceptos:",
            collect($f['items'] ?? [])->map(fn ($i) => sprintf('- %s × %s a %s', $i['descripcion'] ?? '-', $i['cantidad'] ?? 1, $this->money($i['precio'] ?? 0, $cur)))->implode("\n") ?: '- ninguno',
            "\nImpuesto: " . ($f['tax_rate'] ?? 0) . '% · Total: ' . $this->money($f['total'] ?? 0, $cur) . ' · Saldo: ' . $this->money($f['saldo'] ?? $f['total'] ?? 0, $cur),
            "\nPagos:",
            collect($f['pagos'] ?? [])->map(fn ($p) => sprintf('- %s · %s · %s', $p['fecha'] ?? '-', $this->money($p['monto'] ?? 0, $cur), $p['metodo'] ?? '-'))->implode("\n") ?: '- ninguno',
        ];

        return Response::text(implode("\n", $out));
    }

    public function find(string $key): ?array
    {
        $all = collect($this->store('facturas.json')->all());

        return $all->firstWhere('id', $key) ?? $all->first(fn ($f) => strcasecmp((string) ($f['numero'] ?? ''), $key) === 0);
    }
}
