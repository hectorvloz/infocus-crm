<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_plantillas_correo')]
#[Description('Lista las plantillas de correo de Infocus (nombre, asunto e inicio del mensaje). Úsalas en preparar_correo con plantilla_id para mantener el estilo de Indigo.')]
#[IsReadOnly]
class VerPlantillasCorreo extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('correo.read');
        $rows = collect($this->store('email_templates.json')->all())
            ->map(fn ($t) => sprintf("- %s (plantilla_id: %s)\n  Asunto: %s\n  Mensaje: %s",
                $t['name'] ?? 'Sin nombre', $t['id'], $t['subject'] ?? '-', Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($t['body'] ?? '')))), 220)));

        return Response::text($rows->isEmpty() ? 'No hay plantillas guardadas.' : "Variables disponibles: {cliente}, {empresa}, {fecha}, {usuario}, {cliente_email}, {cliente_telefono}, {cliente_nit}, {cliente_direccion}\n\n" . $rows->implode("\n"));
    }
}
