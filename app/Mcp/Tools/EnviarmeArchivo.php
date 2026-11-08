<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\FacturasController;
use App\Mcp\Support\McpContext;
use App\Support\Ai\AiAttachments;
use App\Support\ContractDocuments;
use App\Support\WhatsApp\WhatsAppChannel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('enviarme_archivo')]
#[Description('Le manda al usuario, a su propio WhatsApp (el configurado en Infocus), un archivo: el PDF de una factura (id o número, ej. INV-0064), el comprobante de un gasto (id), el PDF de un contrato (id) o un adjunto subido antes (ADJ...). Solo llega al WhatsApp del dueño, nunca a terceros: para enviarle algo a un cliente usa preparar_correo o preparar_envio_factura.')]
class EnviarmeArchivo extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'tipo' => $schema->string()->enum(['factura', 'gasto', 'contrato', 'adjunto'])->required(),
            'id' => $schema->string()->description('Id del registro; para facturas también sirve el número (INV-0064).')->required(),
            'mensaje' => $schema->string()->description('Texto corto que acompaña el archivo.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'tipo' => 'required|in:factura,gasto,contrato,adjunto',
            'id' => 'required|string|max:80',
            'mensaje' => 'nullable|string|max:500',
        ]);

        $channel = WhatsAppChannel::current();
        $settings = $channel->settings();
        if (!$settings['enabled'] || !$channel->configured($settings)) {
            return Response::error('WhatsApp no está activo en Infocus (Configuración > IA > WhatsApp).');
        }
        if ($settings['user_id'] !== (string) Auth::id()) {
            return Response::error('Solo el usuario conectado a WhatsApp en Infocus puede recibir archivos por WhatsApp.');
        }
        if ($channel->needsWindow() && !WhatsAppChannel::windowOpen()) {
            return Response::error('WhatsApp solo deja mandar archivos dentro de las 24 horas después de tu último mensaje. Escríbele algo al número de Infocus por WhatsApp y vuelve a pedirlo.');
        }

        $file = match ($d['tipo']) {
            'factura' => $this->invoice($ctx, $d['id']),
            'gasto' => $this->receipt($ctx, $d['id']),
            'contrato' => $this->contract($ctx, $d['id']),
            'adjunto' => $this->attachment($d['id']),
        };
        if (is_string($file)) {
            return Response::error($file);
        }

        $attachments = new AiAttachments();
        $saved = $file['saved'] ?? $attachments->save($file['contents'], $file['name'], $file['mime'], 'envio');
        if (!$saved) {
            return Response::error('No pude preparar el archivo para enviarlo (tipo o tamaño no admitido).');
        }

        $caption = trim((string) ($d['mensaje'] ?? '')) ?: $saved['name'];
        $error = $channel->sendFile($settings['owner'], AiAttachments::temporaryUrl($saved), $saved['name'], $saved['mime'], $caption, $settings);
        if ($error !== null) {
            return Response::error('No se pudo enviar por WhatsApp: ' . $error);
        }

        return Response::text("Te envié «{$saved['name']}» a tu WhatsApp ({$settings['owner']}).");
    }

    /** @return array{contents:string, name:string, mime:string}|string */
    private function invoice(McpContext $ctx, string $idOrNumber): array|string
    {
        $ctx->allow('facturas.read');
        $key = Str::upper(trim($idOrNumber));
        $invoice = collect($this->store('facturas.json')->all())
            ->first(fn ($f) => (string) ($f['id'] ?? '') === trim($idOrNumber) || Str::upper((string) ($f['numero'] ?? '')) === $key);
        if (!$invoice) {
            return "No encontré la factura {$idOrNumber}.";
        }
        $response = app(FacturasController::class)->descargarPdf((string) $invoice['id']);
        $contents = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        if (!str_starts_with($contents, '%PDF')) {
            return 'No pude generar el PDF de la factura.';
        }

        return ['contents' => $contents, 'name' => 'factura_' . (preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) ($invoice['numero'] ?? $invoice['id'])) ?: 'factura') . '.pdf', 'mime' => 'application/pdf'];
    }

    /** @return array{contents:string, name:string, mime:string}|string */
    private function receipt(McpContext $ctx, string $id): array|string
    {
        $ctx->allow('gastos.read');
        $gasto = $this->store('gastos.json')->find(trim($id));
        $path = (string) ($gasto['comprobante_path'] ?? '');
        if (!$gasto) {
            return "No encontré el gasto {$id}.";
        }
        if ($path === '' || !str_starts_with($path, 'comprobantes/') || str_contains($path, '..') || !Storage::disk('public')->exists($path)) {
            return 'Ese gasto no tiene comprobante adjunto.';
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = array_search($extension === 'jpeg' ? 'jpg' : $extension, AiAttachments::MIMES, true) ?: 'application/pdf';

        return ['contents' => (string) Storage::disk('public')->get($path), 'name' => 'comprobante-' . Str::slug(Str::limit((string) ($gasto['concepto'] ?? 'gasto'), 40, '')) . '.' . $extension, 'mime' => $mime];
    }

    /** @return array{contents:string, name:string, mime:string}|string */
    private function contract(McpContext $ctx, string $id): array|string
    {
        $ctx->allow('contratos.read');
        $contract = $this->store('contratos.json')->find(trim($id));
        if (!$contract) {
            return "No encontré el contrato {$id}.";
        }

        return ['contents' => (string) (new ContractDocuments())->pdf($contract)->output(), 'name' => ContractDocuments::fileName($contract), 'mime' => 'application/pdf'];
    }

    /** @return array{saved:array<string, mixed>}|string */
    private function attachment(string $id): array|string
    {
        $item = (new AiAttachments())->find($id);

        return $item ? ['saved' => $item] : "No encontré el adjunto {$id}, o ya venció (duran 7 días).";
    }
}
