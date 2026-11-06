<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\ContractDocuments;
use App\Support\ContractTemplates;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_contrato')]
#[Description('Detalle de un contrato: estado, cliente, valor, datos pendientes, firmas, enlace para descargar el PDF y enlace de firma del cliente si ya se publicó. Con incluir_texto=true devuelve también el texto.')]
#[IsReadOnly]
class VerContrato extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'contrato_id' => $schema->string()->description('Usa buscar_contratos.')->required(),
            'incluir_texto' => $schema->boolean(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.read');
        $d = $request->validate(['contrato_id' => 'required|string', 'incluir_texto' => 'nullable|boolean']);
        $c = $this->store('contratos.json')->find($d['contrato_id']);
        abort_if(!$c, 404);
        $estado = $c['estado'] ?? 'Borrador';
        $pending = ContractTemplates::pending((string) ($c['contenido'] ?? ''));
        $firmas = collect($c['firmas'] ?? [])->map(fn ($f) => '- ' . ($f['rol'] ?? '') . ': ' . ($f['nombre'] ?? '') . (!empty($f['documento']) ? ' · doc. ' . $f['documento'] : '') . ' · ' . ContractDocuments::formatDate($f['fecha'] ?? null))->implode("\n");

        $lines = [
            'Contrato ' . ContractDocuments::number($c) . ': ' . ($c['titulo'] ?? '') . " (contrato_id: {$c['id']})",
            'Estado: ' . $estado . ' · Cliente: ' . ($c['cliente_nombre'] ?? '') . (($valor = ContractDocuments::money($c)) ? ' · Valor: ' . $valor : ''),
            'Datos pendientes: ' . ($pending ? implode(', ', $pending) : 'ninguno'),
            'Firmas:' . ($firmas !== '' ? "\n" . $firmas : ' ninguna'),
            'Ver en Infocus: ' . route('contratos.show', $c['id']),
            'Descargar PDF (requiere sesión en Infocus): ' . route('contratos.pdf', $c['id']),
        ];
        if (!empty($c['public_token']) && $estado !== 'Borrador') {
            $lines[] = 'Enlace del cliente para leer y firmar: ' . ContractDocuments::publicUrl($c);
        }
        if (!empty($d['incluir_texto'])) {
            $lines[] = "\nTexto del contrato:\n" . Str::limit(trim(html_entity_decode(strip_tags(str_replace(['</p>', '</h2>', '</li>'], "\n", (string) $c['contenido'])))), 12000);
        }

        return Response::text(implode("\n", $lines));
    }
}
