<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use App\Support\ContractDocuments;
use App\Support\ContractTemplates;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_envio_contrato')]
#[Description('Prepara la publicación y el envío de un contrato al cliente (correo con el PDF y el enlace para firmar). No lo envía: devuelve la vista previa y un código. Solo si el usuario aprueba, llama a confirmar_accion.')]
class PrepararEnvioContrato extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['contrato_id' => $schema->string()->required()];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.update', write: true);
        $id = (string) $request->validate(['contrato_id' => 'required|string'])['contrato_id'];
        $c = $this->store('contratos.json')->find($id);
        abort_if(!$c, 404);
        if (($c['estado'] ?? '') === 'Firmado') {
            return Response::error('Ese contrato ya está firmado.');
        }
        $email = ContractDocuments::clientEmail($c);
        if ($email === '') {
            return Response::error('El cliente no tiene correo de contacto. Agrégalo en su ficha de Infocus antes de enviar.');
        }
        $pending = ContractTemplates::pending((string) ($c['contenido'] ?? ''));
        $code = (new PendingActions())->create('enviar_contrato', ['contrato_id' => $id], $ctx->connection);

        return Response::text(implode("\n", array_filter([
            'Vista previa del envío (todavía no se ha enviado):',
            'Contrato: ' . ($c['titulo'] ?? '') . ' · ' . ContractDocuments::number($c),
            'Para: ' . $email,
            'Incluye: el PDF adjunto y un enlace privado para revisarlo y firmarlo.',
            $pending ? 'ATENCIÓN: el contrato todavía tiene datos sin llenar: ' . implode(', ', $pending) . '. Recomienda completarlos antes de enviar.' : null,
            '',
            "Código de confirmación: {$code} (vence en " . PendingActions::TTL_MINUTES . ' minutos). Pide al usuario que apruebe antes de llamar a confirmar_accion.',
        ], fn ($l) => $l !== null)));
    }
}
