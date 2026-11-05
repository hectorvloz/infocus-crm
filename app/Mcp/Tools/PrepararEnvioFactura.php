<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_envio_factura')]
#[Description('Prepara el envío de una factura por correo (con su PDF, igual que desde la app) y devuelve un código. NO la envía: confirma con el usuario y luego llama a confirmar_accion.')]
class PrepararEnvioFactura extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'factura' => $schema->string()->description('Id o número de la factura.')->required(),
            'para' => $schema->string()->description('Correo destinatario. Por defecto, el del cliente.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.read', write: true);
        $d = $request->validate(['factura' => 'required|string|max:120', 'para' => 'nullable|email']);
        $f = (new VerFactura())->find($d['factura']);
        abort_if(!$f, 404);
        if (in_array($f['estado'] ?? '', ['En borrador', 'Borrador'], true)) {
            return Response::error("La factura {$f['numero']} está en borrador. Revísala y emítela en Infocus antes de enviarla.");
        }
        $client = !empty($f['cliente_id']) ? $this->store('clientes.json')->find($f['cliente_id']) : null;
        $to = $d['para'] ?? ($client['contacto_email'] ?? null);
        if (!$to) {
            return Response::error('El cliente no tiene correo de contacto. Indica el destinatario en "para".');
        }

        $code = (new PendingActions())->create('enviar_factura', ['id' => $f['id'], 'to' => $to], $ctx->connection);

        return Response::text(sprintf("VISTA PREVIA (no enviada)\nFactura %s · %s · total %s · saldo %s · estado %s\nPara: %s\n\nSi el usuario aprueba, llama a confirmar_accion con el código %s (vence en %d minutos).",
            $f['numero'], $f['cliente'] ?? '-', $this->money($f['total'] ?? 0, $f['moneda'] ?? null), $this->money($f['saldo'] ?? $f['total'] ?? 0, $f['moneda'] ?? null),
            $f['estado'] ?? '-', $to, $code, PendingActions::TTL_MINUTES));
    }
}
