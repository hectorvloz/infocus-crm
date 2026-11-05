<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CorreoController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use App\Support\SafeRichText;
use App\Support\TemplateMail;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_correo')]
#[Description('Prepara un correo con el diseño de Indigo y devuelve la vista previa y un código. NO lo envía: muéstrale la vista previa al usuario y, solo si la aprueba, llama a confirmar_accion con el código. Escribe en tono cercano y claro, saluda con {cliente}. Formato permitido: <h1>, <h2>, <p>, <strong>, <em>, <hr>, <ul>, <li>, <a>. O usa plantilla_id de ver_plantillas_correo.')]
class PrepararCorreo extends InfocusTool
{
    public const MAX_RECIPIENTS = 10;

    public function schema(JsonSchema $schema): array
    {
        return [
            'para' => $schema->array()->items($schema->string())->description('Correos destinatarios. También puedes usar cliente_id o lead_id.'),
            'cliente_id' => $schema->string()->description('Envía al correo de contacto de este cliente.'),
            'lead_id' => $schema->string()->description('Envía al correo de este lead.'),
            'asunto' => $schema->string()->description('Obligatorio si no usas plantilla.'),
            'mensaje' => $schema->string()->description('Cuerpo en HTML simple o texto con párrafos separados por línea en blanco.'),
            'plantilla_id' => $schema->string()->description('Usa una plantilla guardada en lugar de asunto y mensaje.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('correo.create', write: true);
        $d = $request->validate([
            'para' => 'nullable|array|max:' . self::MAX_RECIPIENTS, 'para.*' => 'email', 'cliente_id' => 'nullable|string', 'lead_id' => 'nullable|string',
            'asunto' => 'nullable|string|max:180', 'mensaje' => 'nullable|string|max:20000', 'plantilla_id' => 'nullable|string',
        ]);

        $to = collect($d['para'] ?? []);
        if (!empty($d['cliente_id'])) {
            $client = $this->store('clientes.json')->find($d['cliente_id']);
            abort_if(!$client, 404);
            $to->push($client['contacto_email'] ?? null);
        }
        if (!empty($d['lead_id'])) {
            $lead = $this->store('leads.json')->find($d['lead_id']);
            abort_if(!$lead, 404);
            $to->push($lead['email'] ?? null);
        }
        $to = $to->map(fn ($m) => strtolower(trim((string) $m)))->filter(fn ($m) => filter_var($m, FILTER_VALIDATE_EMAIL))->unique()->values()->all();
        if (!$to || count($to) > self::MAX_RECIPIENTS) {
            return Response::error('Necesito entre 1 y ' . self::MAX_RECIPIENTS . ' destinatarios con correo válido.');
        }

        [$subject, $body] = [$d['asunto'] ?? '', $d['mensaje'] ?? ''];
        if (!empty($d['plantilla_id'])) {
            $tpl = $this->store('email_templates.json')->find($d['plantilla_id']);
            abort_if(!$tpl, 404);
            $subject = $subject ?: (string) ($tpl['subject'] ?? '');
            $body = $body ?: (string) ($tpl['body'] ?? '');
        }
        if (trim($subject) === '' || trim(strip_tags($body)) === '') {
            return Response::error('Falta el asunto o el mensaje.');
        }

        $vars = CorreoController::templateVars($to);
        $subject = trim(preg_replace('/[\r\n\t]+/', ' ', TemplateMail::tokenReplace(strip_tags($subject), $vars)));
        $html = TemplateMail::tokenReplace(SafeRichText::sanitize(self::toHtml($body)), $vars);

        $code = (new PendingActions())->create('enviar_correo', ['to' => $to, 'subject' => $subject, 'body' => $html], $ctx->connection);

        return Response::text(sprintf(
            "VISTA PREVIA (no enviado)\nPara: %s\nAsunto: %s\n\n%s\n\nPara enviarlo, el usuario debe aprobarlo y tú llamas a confirmar_accion con el código %s (vence en %d minutos).",
            implode(', ', $to), $subject, trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</h1>', '</h2>', '<hr>'], ["\n\n", "\n", "\n\n", "\n\n", "\n———\n"], $html)))),
            $code, PendingActions::TTL_MINUTES
        ));
    }

    /** Convierte texto plano en párrafos; el HTML se deja tal cual para limpiarlo después. */
    public static function toHtml(string $body): string
    {
        if ($body !== strip_tags($body)) {
            return $body;
        }

        return collect(preg_split('/\n\s*\n/', trim($body)))
            ->map(fn ($p) => '<p>' . nl2br(e(trim($p)), false) . '</p>')
            ->implode('');
    }
}
