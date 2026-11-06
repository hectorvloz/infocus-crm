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

#[Name('crear_contrato_desde_plantilla')]
#[Description('Crea un contrato en borrador desde una plantilla basada en normativa colombiana, con los datos de Indigo y del cliente ya llenos. Plantillas: servicios (prestación de servicios), sitio_web, branding, mantenimiento, nda, cesion (derechos de autor). Devuelve los datos que faltan entre [CORCHETES] para completarlos con completar_contrato.')]
class CrearContratoPlantilla extends InfocusTool
{
    public const TEMPLATES = ['servicios', 'sitio_web', 'branding', 'mantenimiento', 'nda', 'cesion'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'plantilla' => $schema->string()->enum(self::TEMPLATES)->required(),
            'cliente_id' => $schema->string()->description('Usa buscar_clientes.')->required(),
            'monto' => $schema->number()->description('Valor total del contrato.'),
            'moneda' => $schema->string()->enum(['COP', 'USD', 'EUR', 'MXN'])->description('Por defecto COP.'),
            'proyecto_id' => $schema->string(),
            'titulo' => $schema->string()->description('Opcional. Por defecto: nombre de la plantilla · cliente.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.create', write: true);
        $d = $request->validate([
            'plantilla' => 'required|in:' . implode(',', self::TEMPLATES), 'cliente_id' => 'required|string',
            'monto' => 'nullable|numeric|min:0', 'moneda' => 'nullable|in:COP,USD,EUR,MXN', 'proyecto_id' => 'nullable|string', 'titulo' => 'nullable|string|max:255',
        ]);
        $client = $this->store('clientes.json')->find($d['cliente_id']);
        abort_if(!$client, 404);
        $project = !empty($d['proyecto_id']) ? $this->store('proyectos.json')->find($d['proyecto_id']) : null;
        $settings = ContractDocuments::settings();
        $template = ContractTemplates::all($settings)[$d['plantilla']];
        $draft = ['monto' => $d['monto'] ?? null, 'moneda' => $d['moneda'] ?? 'COP'];
        $html = ContractTemplates::fill($template['contenido'], $client, ContractDocuments::money($draft));
        $clientName = (string) ($client['empresa'] ?? 'Cliente');

        $contract = $this->store('contratos.json')->create([
            'id' => (string) Str::uuid(),
            'titulo' => $d['titulo'] ?? ($template['titulo'] . ' · ' . $clientName),
            'cliente_id' => $client['id'],
            'cliente_nombre' => $clientName,
            'proyecto_id' => $project['id'] ?? null,
            'proyecto_nombre' => $project['titulo'] ?? null,
            'contenido' => $html,
            'monto' => $d['monto'] ?? null,
            'moneda' => $d['moneda'] ?? 'COP',
            'estado' => 'Borrador',
            'firmas' => [],
            'plantilla' => $d['plantilla'],
            'creado_por' => 'MCP · ' . $ctx->connection,
        ]);
        $pending = ContractTemplates::pending($html);

        return Response::text("Contrato en borrador creado: \"{$contract['titulo']}\" (contrato_id: {$contract['id']}).\n"
            . ($pending ? 'Datos por completar: ' . implode(', ', $pending) . ". Pregúntale al usuario estos datos y usa completar_contrato.\n" : "No quedan datos por completar.\n")
            . 'Revísalo en Infocus: ' . route('contratos.show', $contract['id']));
    }
}
