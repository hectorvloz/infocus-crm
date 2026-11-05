<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_lead')]
#[Description('Mueve un lead de etapa y/o le agrega una nota de seguimiento.')]
class ActualizarLead extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->string()->required(),
            'etapa' => $schema->string()->enum(ListarLeads::ETAPAS),
            'nota' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate(['lead_id' => 'required|string', 'etapa' => 'nullable|in:' . implode(',', ListarLeads::ETAPAS), 'nota' => 'nullable|string|max:4000']);
        if (empty($d['etapa']) && empty($d['nota'])) {
            return Response::error('Indica una etapa nueva o una nota.');
        }
        $ctx->allow(!empty($d['etapa']) ? 'leads.update' : 'leads.create', write: true);
        $store = $this->store('leads.json');
        $lead = $store->find($d['lead_id']);
        abort_if(!$lead, 404);

        $changes = [];
        if (!empty($d['etapa'])) {
            $changes['etapa'] = $d['etapa'];
        }
        if (!empty($d['nota'])) {
            $changes['notas_lista'] = array_merge($lead['notas_lista'] ?? [], [['id' => (string) Str::ulid(), 'texto' => $d['nota'], 'fecha' => now()->toISOString()]]);
            $changes['notas'] = $d['nota'];
        }
        $store->update($lead['id'], $changes);

        return Response::text("Lead {$lead['nombre']} actualizado" . (!empty($d['etapa']) ? " · etapa: {$d['etapa']}" : '') . (!empty($d['nota']) ? ' · nota agregada' : '') . '.');
    }
}
