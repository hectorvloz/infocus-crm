<?php

namespace App\Mcp\Tools;

use App\Mail\GenericMail;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('enviarme_correo')]
#[Description('Le manda al usuario un aviso o recordatorio por correo, solo a su propio correo de Infocus (nunca a terceros). Sirve para avisos internos que el usuario pidió, por ejemplo cuando un lead pasa a "Volver a llamar". Para escribirle a un cliente usa preparar_correo.')]
class EnviarmeCorreo extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'asunto' => $schema->string()->description('Asunto corto del correo.')->required(),
            'mensaje' => $schema->string()->description('Texto del aviso. Los saltos de línea se respetan.')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'asunto' => 'required|string|max:150',
            'mensaje' => 'required|string|max:6000',
        ]);

        $correo = trim((string) (Auth::user()->email ?? ''));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return Response::error('Tu usuario de Infocus no tiene un correo válido. Agrégalo en tu perfil.');
        }

        try {
            Mail::to($correo)->send(new GenericMail($d['asunto'], nl2br(e($d['mensaje']))));
        } catch (\Throwable $ex) {
            return Response::error('No se pudo enviar el correo: ' . $ex->getMessage());
        }

        return Response::text("Te envié el aviso a tu correo ({$correo}).");
    }
}
