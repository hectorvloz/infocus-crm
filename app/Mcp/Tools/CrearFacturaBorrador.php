<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\InvoiceDraft;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_factura_borrador')]
#[Description('Crea una factura EN BORRADOR para un cliente existente. No se envía al cliente: para enviarla usa preparar_envio_factura. Confirma con el usuario moneda, vencimiento e impuesto antes de crearla.')]
class CrearFacturaBorrador extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'cliente_id' => $schema->string()->description('Id del cliente (usa buscar_clientes).')->required(),
            'moneda' => $schema->string()->description('Código ISO, ej. COP.')->required(),
            'vencimiento' => $schema->string()->description('Fecha AAAA-MM-DD, hoy o posterior.')->required(),
            'impuesto' => $schema->integer()->description('Porcentaje de impuesto, 0 a 100 (ej. 19).')->required(),
            'items' => $schema->array()->items($schema->object([
                'descripcion' => $schema->string()->required(),
                'cantidad' => $schema->number()->required(),
                'precio' => $schema->number()->description('Precio unitario sin impuesto.')->required(),
            ]))->description('Entre 1 y 30 conceptos.')->required(),
            'proyecto_id' => $schema->string()->description('Opcional: proyecto del mismo cliente.'),
            'tasa' => $schema->number()->description('Tasa de cambio a la moneda base. Obligatoria si la moneda no es la base de Infocus.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.create', write: true);
        $ctx->allow('facturas.read');
        $d = $request->validate([
            'cliente_id' => 'required|string', 'moneda' => 'required|string|size:3', 'vencimiento' => 'required|date_format:Y-m-d',
            'impuesto' => 'required|integer|min:0|max:100', 'items' => 'required|array|min:1|max:30', 'proyecto_id' => 'nullable|string', 'tasa' => 'nullable|numeric|gt:0',
        ]);
        try {
            $draft = (new InvoiceDraft())->prepare([
                'client_id' => $d['cliente_id'], 'currency' => $d['moneda'], 'due_date' => $d['vencimiento'],
                'tax_rate' => $d['impuesto'], 'items' => $d['items'], 'project_id' => $d['proyecto_id'] ?? '', 'rate' => $d['tasa'] ?? null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        $store = $this->store('facturas.json');
        $draft['numero'] = self::nextNumber($store->all());
        $draft['creado_por'] = 'MCP · ' . $ctx->connection;
        $invoice = $store->create($draft);

        return Response::text(sprintf("Borrador creado: %s para %s · total %s · estado: %s (id: %s).\nNo se ha enviado al cliente.",
            $invoice['numero'], $invoice['cliente'], $this->money($invoice['total'] ?? 0, $invoice['moneda'] ?? null), $invoice['estado'], $invoice['id']));
    }

    /** Misma numeración que el asistente de IA de Infocus: INV-0001, INV-0002… */
    public static function nextNumber(array $invoices): string
    {
        $max = 0;
        foreach ($invoices as $invoice) {
            if (preg_match('/^INV-(\d+)/', strtoupper((string) ($invoice['numero'] ?? '')), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'INV-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
