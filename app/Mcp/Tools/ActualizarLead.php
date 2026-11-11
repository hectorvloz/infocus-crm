<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Support\LeadTags;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_lead')]
#[Description('Mueve un lead de etapa, le agrega una nota de seguimiento y/o le agrega o quita etiquetas (nichos).')]
class ActualizarLead extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->string()->required(),
            'etapa' => $schema->string()->enum(ListarLeads::ETAPAS),
            'nota' => $schema->string(),
            'agregar_etiquetas' => $schema->array()->items($schema->string())->description('Etiquetas a sumar, ej. ["RentaCar"]. Reutiliza las existentes.'),
            'quitar_etiquetas' => $schema->array()->items($schema->string())->description('Etiquetas a quitar.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate([
            'lead_id' => 'required|string', 'etapa' => 'nullable|in:' . implode(',', ListarLeads::ETAPAS), 'nota' => 'nullable|string|max:4000',
            'agregar_etiquetas' => 'nullable|array|max:' . LeadTags::MAX_POR_LEAD, 'agregar_etiquetas.*' => 'string|max:' . LeadTags::MAX_LARGO,
            'quitar_etiquetas' => 'nullable|array', 'quitar_etiquetas.*' => 'string|max:' . LeadTags::MAX_LARGO,
        ]);
        $tocaEtiquetas = !empty($d['agregar_etiquetas']) || !empty($d['quitar_etiquetas']);
        if (empty($d['etapa']) && empty($d['nota']) && !$tocaEtiquetas) {
            return Response::error('Indica una etapa nueva, una nota o etiquetas para agregar o quitar.');
        }
        $ctx->allow(!empty($d['etapa']) || $tocaEtiquetas ? 'leads.update' : 'leads.create', write: true);
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
        if ($tocaEtiquetas) {
            $quitar = collect(LeadTags::normalize($d['quitar_etiquetas'] ?? []))->map(fn ($t) => LeadTags::key($t))->all();
            $actuales = collect(LeadTags::of($lead))->reject(fn ($t) => in_array(LeadTags::key($t), $quitar, true))->all();
            $changes['etiquetas'] = LeadTags::normalize(array_merge($actuales, $d['agregar_etiquetas'] ?? []));
        }
        $this->tracked('leads.json', $lead['id'], "Actualizó el lead {$lead['nombre']}", fn () => $store->update($lead['id'], $changes));

        return Response::text("Lead {$lead['nombre']} actualizado" . (!empty($d['etapa']) ? " · etapa: {$d['etapa']}" : '') . (!empty($d['nota']) ? ' · nota agregada' : '') . ($tocaEtiquetas ? ' · etiquetas: ' . (implode(', ', $changes['etiquetas']) ?: 'ninguna') : '') . '.');
    }
}
