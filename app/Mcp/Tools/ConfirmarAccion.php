<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\FacturasController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ReunionesController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use App\Support\TemplateMail;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('confirmar_accion')]
#[Description('Ejecuta una acción preparada (enviar correo, enviar factura, registrar pago o programar reunión) con su código. Úsala SOLO después de que el usuario haya aprobado explícitamente la vista previa en esta conversación. El código sirve una vez y vence en 5 minutos.')]
#[IsDestructive]
class ConfirmarAccion extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['codigo' => $schema->string()->description('Código devuelto por la herramienta de preparación, ej. A1B-2C3.')->required()];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $code = (string) $request->validate(['codigo' => 'required|string|max:20'])['codigo'];
        $action = (new PendingActions())->consume($code);
        if (!$action) {
            return Response::error('Código inválido, vencido o ya usado. Prepara la acción de nuevo.');
        }
        $p = $action['payload'];

        return match ($action['action']) {
            'enviar_correo' => $this->sendEmail($p, $ctx),
            'enviar_factura' => $this->sendInvoice($p, $ctx),
            'registrar_pago' => $this->registerPayment($p, $ctx),
            'programar_reunion' => $this->scheduleMeeting($p, $ctx),
            default => Response::error('Acción desconocida.'),
        };
    }

    private function sendEmail(array $p, McpContext $ctx): Response
    {
        $ctx->allow('correo.create', write: true);
        TemplateMail::send($p['to'], $p['subject'], $p['body'], [
            'source' => 'mcp',
            'sent_by' => (string) (auth()->user()->email ?? 'sistema'),
            'sent_by_name' => (string) (auth()->user()->name ?? 'Sistema') . ' (MCP · ' . $ctx->connection . ')',
        ]);

        return Response::text('Correo enviado a ' . implode(', ', $p['to']) . '. Quedó en el historial de correos.');
    }

    private function sendInvoice(array $p, McpContext $ctx): Response
    {
        $ctx->allow('facturas.read', write: true);
        $result = $this->callController(FacturasController::class, 'enviarEmail', ['id' => $p['id'], 'to' => $p['to']]);

        return ($result['status'] < 300 && ($result['data']['ok'] ?? true))
            ? Response::text("Factura enviada a {$p['to']}.")
            : Response::error('No se pudo enviar la factura: ' . ($result['data']['error'] ?? $result['data']['message'] ?? 'error desconocido'));
    }

    private function registerPayment(array $p, McpContext $ctx): Response
    {
        $ctx->allow('facturas.update', write: true);
        $result = $this->callController(FacturasController::class, 'pagar', $p);
        $item = $result['data']['item'] ?? null;

        return $result['status'] < 300
            ? Response::text('Pago registrado.' . ($item ? " Factura {$item['numero']} · estado: {$item['estado']}." : '') . (!empty($result['data']['message']) ? ' ' . $result['data']['message'] : ''))
            : Response::error('No se pudo registrar el pago.');
    }

    private function scheduleMeeting(array $p, McpContext $ctx): Response
    {
        if ($p['kind'] === 'lead') {
            $ctx->allow('leads.create', write: true);
            $result = $this->callController(LeadsController::class, 'agendaProgramar', collect($p)->except('kind')->all());
            if ($result['status'] >= 300 || !($result['data']['ok'] ?? false)) {
                return Response::error('No se pudo programar: ' . ($result['data']['message'] ?? 'error desconocido'));
            }
            $item = $result['data']['agenda_item'] ?? [];
        } else {
            $ctx->allow('reuniones.create', write: true);
            $result = $this->callController(ReunionesController::class, 'store', collect($p)->except('kind')->all());
            if ($result['status'] >= 400) {
                return Response::error('No se pudo crear la reunión.');
            }
            $item = collect($this->store('reuniones.json')->all())
                ->where('cliente_id', $p['cliente_id'])->where('titulo', $p['titulo'])->where('fecha', $p['fecha'])->last() ?? [];
        }

        return Response::text('Reunión programada: ' . $p['titulo']
            . (!empty($item['meet_link']) ? " · Meet: {$item['meet_link']}" : '')
            . (!empty($item['meet_error']) ? " · Aviso: {$item['meet_error']}" : '')
            . '. Se enviaron las invitaciones a los correos de la vista previa.');
    }
}
