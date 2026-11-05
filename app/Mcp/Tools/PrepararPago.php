<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_pago')]
#[Description('Prepara el registro de un pago recibido en una factura y devuelve un código. NO lo registra: confirma monto y método con el usuario y luego llama a confirmar_accion.')]
class PrepararPago extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'factura' => $schema->string()->description('Id o número de la factura.')->required(),
            'monto' => $schema->number()->description('Monto recibido en la moneda de la factura. Vacío = pago total del saldo.'),
            'metodo' => $schema->string()->description('Ej. Transferencia, Nequi, Efectivo, Wompi.'),
            'fecha' => $schema->string()->description('AAAA-MM-DD. Por defecto hoy.'),
            'nota' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.update', write: true);
        $d = $request->validate([
            'factura' => 'required|string|max:120', 'monto' => 'nullable|numeric|gt:0', 'metodo' => 'nullable|string|max:60',
            'fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today', 'nota' => 'nullable|string|max:500',
        ]);
        $f = (new VerFactura())->find($d['factura']);
        abort_if(!$f, 404);
        if (($f['estado'] ?? '') === 'Pagada') {
            return Response::error("La factura {$f['numero']} ya está pagada.");
        }
        $saldo = (float) ($f['saldo'] ?? $f['total'] ?? 0);
        $monto = isset($d['monto']) ? (float) $d['monto'] : $saldo;

        $code = (new PendingActions())->create('registrar_pago', array_filter([
            'id' => $f['id'], 'monto' => $monto, 'metodo' => $d['metodo'] ?? null, 'fecha_pago' => $d['fecha'] ?? now()->toDateString(), 'nota' => $d['nota'] ?? null,
        ], fn ($v) => $v !== null), $ctx->connection);

        return Response::text(sprintf("VISTA PREVIA (no registrado)\nFactura %s · %s · saldo actual %s\nPago: %s · %s · %s%s\nAl registrarlo, Infocus le envía al cliente el recibo por correo si esa opción está activa.\n\nSi el usuario aprueba, llama a confirmar_accion con el código %s (vence en %d minutos).",
            $f['numero'], $f['cliente'] ?? '-', $this->money($saldo, $f['moneda'] ?? null), $this->money($monto, $f['moneda'] ?? null),
            $d['metodo'] ?? 'método por defecto', $d['fecha'] ?? now()->toDateString(), $monto > $saldo + 0.01 ? "\n⚠ El monto supera el saldo pendiente." : '',
            $code, PendingActions::TTL_MINUTES));
    }
}
