<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\AiAttachments;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('subir_adjunto')]
#[Description('Sube a Infocus un archivo que el usuario te compartió (PDF, imagen, Word, Excel, CSV o texto; máximo 10 MB) para enviarlo en un correo (preparar_correo con adjuntos), usarlo como comprobante (registrar_gasto con comprobante_adjunto_id) o como portada de proyecto (gestionar_portada_proyecto con acción adjunto). Devuelve un id ADJ... que dura 7 días.')]
class SubirAdjunto extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()->description('Nombre del archivo con extensión, ej. cotizacion.pdf.')->required(),
            'tipo' => $schema->string()->enum(array_keys(AiAttachments::MIMES))->required(),
            'contenido_base64' => $schema->string()->description('Contenido del archivo en base64.')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('correo.create', write: true);
        $d = $request->validate([
            'nombre' => 'required|string|max:160',
            'tipo' => 'required|string|in:' . implode(',', array_keys(AiAttachments::MIMES)),
            'contenido_base64' => 'required|string|max:' . (int) ceil(AiAttachments::MAX_BYTES * 4 / 3 + 16),
        ]);

        $raw = preg_replace('/^data:[^;]+;base64,/', '', trim($d['contenido_base64'])) ?? '';
        $contents = base64_decode($raw, true);
        if ($contents === false || $contents === '') {
            return Response::error('El contenido no es base64 válido.');
        }
        if (finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $contents) === 'application/x-dosexec') {
            return Response::error('Ese tipo de archivo no se acepta.');
        }

        $file = (new AiAttachments())->save($contents, $d['nombre'], $d['tipo'], 'mcp:' . $ctx->connection);
        if (!$file) {
            return Response::error('No se aceptó el archivo: revisa el tipo y que pese menos de 10 MB.');
        }

        return Response::text('Adjunto guardado: ' . AiAttachments::describe($file) . '. Úsalo con preparar_correo (adjuntos), registrar_gasto (comprobante_adjunto_id) o gestionar_portada_proyecto (acción adjunto para imágenes). Vence en ' . AiAttachments::RETENTION_DAYS . ' días.');
    }
}
