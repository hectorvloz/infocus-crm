<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use App\Support\WhatsApp\WhatsAppChannel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_whatsapp_cliente')]
#[Description('Prepara un WhatsApp corto para un cliente o lead (por ejemplo avisar que se le envió una factura) y devuelve la vista previa y un código. NO lo envía: solo confirmar_accion con el código, después de que el usuario lo apruebe. Sale del número de WhatsApp conectado a Infocus; usa el teléfono guardado del cliente o lead.')]
class PrepararWhatsappCliente extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'cliente_id' => $schema->string(),
            'lead_id' => $schema->string(),
            'mensaje' => $schema->string()->description('Texto corto, cercano y claro. Sin enlaces a menos que el usuario los pida.')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('correo.create', write: true);
        $d = $request->validate(['cliente_id' => 'nullable|string', 'lead_id' => 'nullable|string', 'mensaje' => 'required|string|max:1000']);

        if (!empty($d['cliente_id'])) {
            $record = $this->store('clientes.json')->find($d['cliente_id']);
            abort_if(!$record, 404);
            [$name, $phone] = [(string) (($record['contacto_nombre'] ?? '') ?: ($record['empresa'] ?? 'Cliente')), (string) ($record['contacto_telefono'] ?? $record['telefono'] ?? '')];
        } elseif (!empty($d['lead_id'])) {
            $record = $this->store('leads.json')->find($d['lead_id']);
            abort_if(!$record, 404);
            [$name, $phone] = [(string) ($record['nombre'] ?? 'Lead'), (string) ($record['telefono'] ?? '')];
        } else {
            return Response::error('Indica cliente_id o lead_id.');
        }

        $phone = WhatsAppChannel::clientPhone($phone);
        if ($phone === '') {
            return Response::error("{$name} no tiene un teléfono válido guardado en Infocus.");
        }

        return Response::text(self::preview($name, $phone, trim($d['mensaje']), (new PendingActions())->create('whatsapp_cliente', ['phone' => $phone, 'name' => $name, 'text' => trim($d['mensaje'])], $ctx->connection)));
    }

    public static function preview(string $name, string $phone, string $text, string $code, int $minutes = PendingActions::TTL_MINUTES): string
    {
        return sprintf("VISTA PREVIA (no enviado)\nWhatsApp para: %s (%s)\n\n%s\n\nPara enviarlo, el usuario debe aprobarlo y tú llamas a confirmar_accion con el código %s (vence en %d minutos).", $name, $phone, $text, $code, $minutes);
    }
}
