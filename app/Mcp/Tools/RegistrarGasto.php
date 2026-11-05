<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('registrar_gasto')]
#[Description('Registra un gasto. El comprobante se adjunta después desde la app.')]
class RegistrarGasto extends InfocusTool
{
    private const CURRENCIES = ['USD', 'EUR', 'MXN', 'COP', 'ARS', 'CLP', 'PEN', 'GBP', 'CAD', 'BRL'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'concepto' => $schema->string()->required(),
            'monto' => $schema->number()->required(),
            'moneda' => $schema->string()->enum(self::CURRENCIES)->required(),
            'fecha' => $schema->string()->description('AAAA-MM-DD. Por defecto hoy.'),
            'categoria' => $schema->string(),
            'cliente_id' => $schema->string()->description('Si el gasto es de un cliente.'),
            'notas' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('gastos.create', write: true);
        $d = $request->validate([
            'concepto' => 'required|string|max:255', 'monto' => 'required|numeric|gt:0', 'moneda' => 'required|in:' . implode(',', self::CURRENCIES),
            'fecha' => 'nullable|date', 'categoria' => 'nullable|string|max:80', 'cliente_id' => 'nullable|string', 'notas' => 'nullable|string|max:2000',
        ]);
        $gasto = $this->store('gastos.json')->create([
            'id' => (string) Str::uuid(),
            'concepto' => $d['concepto'], 'monto' => (float) $d['monto'], 'moneda' => $d['moneda'],
            'fecha' => $d['fecha'] ?? now()->toDateString(), 'categoria' => $d['categoria'] ?? null,
            'cliente_id' => $d['cliente_id'] ?? null, 'proveedor_id' => null, 'notas' => $d['notas'] ?? null,
            'es_recurrente' => false,
        ]);

        return Response::text("Gasto registrado: {$gasto['concepto']} · " . $this->money($gasto['monto'], $gasto['moneda']) . " · {$gasto['fecha']}.");
    }
}
