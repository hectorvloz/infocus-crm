<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('convertir_lead_cliente')]
#[Description('Convierte un lead en cliente: crea el cliente con sus datos (o lo vincula si ya existe uno con el mismo nombre o correo) y pasa el lead a la etapa Cliente.')]
class ConvertirLeadCliente extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->string()->required(),
            'empresa' => $schema->string()->description('Nombre de la empresa. Por defecto, la empresa o el nombre del lead.'),
            'nit' => $schema->string(),
            'ciudad' => $schema->string(),
            'moneda' => $schema->string()->description('Código ISO, ej. COP.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('leads.update', write: true);
        $ctx->allow('clientes.create', write: true);
        $d = $request->validate([
            'lead_id' => 'required|string', 'empresa' => 'nullable|string|max:160', 'nit' => 'nullable|string|max:40',
            'ciudad' => 'nullable|string|max:80', 'moneda' => 'nullable|string|size:3',
        ]);
        $leads = $this->store('leads.json');
        $lead = $leads->find($d['lead_id']);
        abort_if(!$lead, 404);
        if (!empty($lead['cliente_id']) && $this->store('clientes.json')->find($lead['cliente_id'])) {
            return Response::error("Este lead ya está vinculado al cliente id {$lead['cliente_id']}.");
        }

        $empresa = trim((string) ($d['empresa'] ?? $lead['empresa'] ?? '')) ?: (string) $lead['nombre'];
        $email = Str::lower(trim((string) ($lead['email'] ?? '')));
        $clients = $this->store('clientes.json');
        $existing = collect($clients->all())->first(fn ($c) => Str::lower(trim((string) ($c['empresa'] ?? ''))) === Str::lower($empresa)
            || ($email !== '' && Str::lower((string) ($c['contacto_email'] ?? '')) === $email));

        $client = $existing ?: $clients->create(array_filter([
            'empresa' => $empresa,
            'contacto_nombre' => (string) $lead['nombre'],
            'contacto_email' => $email ?: null,
            'contacto_telefono' => $lead['telefono'] ?? null,
            'nit' => $d['nit'] ?? null,
            'ciudad' => $d['ciudad'] ?? null,
            'estado' => 'Activo',
            'moneda' => strtoupper($d['moneda'] ?? (($this->store('settings.json')->find('settings') ?: [])['base_currency'] ?? 'USD')),
            'proyectos' => 0,
            'invoice_fields' => ['nit' => true, 'direccion' => true, 'telefono' => true, 'email' => true],
            'origen_lead_id' => $lead['id'],
            'creado_por' => 'MCP · ' . $ctx->connection,
        ], fn ($v) => $v !== null));

        $this->tracked('leads.json', $lead['id'], "Convirtió el lead {$lead['nombre']} en cliente", fn () => $leads->update($lead['id'], [
            'etapa' => 'Cliente',
            'cliente_id' => $client['id'],
            'notas_lista' => array_merge($lead['notas_lista'] ?? [], [['id' => (string) Str::ulid(), 'texto' => "Convertido en cliente: {$client['empresa']}", 'fecha' => now()->toISOString()]]),
        ]));

        return Response::text(($existing ? "Vinculé el lead al cliente existente {$client['empresa']}" : "Cliente creado: {$client['empresa']}")
            . " (cliente_id: {$client['id']}). El lead {$lead['nombre']} quedó en la etapa Cliente.");
    }
}
