<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_lead')]
#[Description('Crea un lead (posible cliente) en el embudo. Si ya existe un lead con ese correo, no lo duplica.')]
class CrearLead extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()->required(),
            'email' => $schema->string(),
            'telefono' => $schema->string(),
            'origen' => $schema->string()->description('Ej. Instagram, referido, feria.'),
            'etapa' => $schema->string()->enum(ListarLeads::ETAPAS)->description('Por defecto: Posible cliente.'),
            'valor' => $schema->number()->description('Valor estimado del negocio.'),
            'nota' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('leads.create', write: true);
        $d = $request->validate([
            'nombre' => 'required|string|max:120', 'email' => 'nullable|email|max:160', 'telefono' => 'nullable|string|max:40',
            'origen' => 'nullable|string|max:80', 'etapa' => 'nullable|in:' . implode(',', ListarLeads::ETAPAS),
            'valor' => 'nullable|numeric|min:0', 'nota' => 'nullable|string|max:4000',
        ]);
        $store = $this->store('leads.json');
        if (!empty($d['email'])) {
            $dup = collect($store->all())->first(fn ($l) => Str::lower((string) ($l['email'] ?? '')) === Str::lower($d['email']));
            if ($dup) {
                return Response::error("Ya existe un lead con ese correo: {$dup['nombre']} (id: {$dup['id']}). Usa actualizar_lead.");
            }
        }
        $nota = !empty($d['nota']) ? [['id' => (string) Str::ulid(), 'texto' => $d['nota'], 'fecha' => now()->toISOString()]] : [];
        $lead = $store->create([
            'nombre' => $d['nombre'], 'email' => $d['email'] ?? null, 'telefono' => $d['telefono'] ?? null,
            'etapa' => $d['etapa'] ?? 'Posible cliente', 'origen' => $d['origen'] ?? 'MCP',
            'valor' => $d['valor'] ?? null, 'presupuesto_estimado' => $d['valor'] ?? null,
            'encargados' => [], 'agenda' => [], 'notas' => $d['nota'] ?? '', 'notas_lista' => $nota,
        ]);

        return Response::text("Lead creado: {$lead['nombre']} (id: {$lead['id']}) en la etapa {$lead['etapa']}.");
    }
}
