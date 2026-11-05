<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_cliente')]
#[Description('Crea un cliente nuevo. Antes usa buscar_clientes para no duplicarlo.')]
class CrearCliente extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'empresa' => $schema->string()->description('Nombre de la empresa o del cliente.')->required(),
            'contacto_nombre' => $schema->string(),
            'contacto_email' => $schema->string(),
            'contacto_telefono' => $schema->string(),
            'nit' => $schema->string(),
            'ciudad' => $schema->string(),
            'pais' => $schema->string(),
            'moneda' => $schema->string()->description('Código ISO, ej. COP. Por defecto la moneda base.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('clientes.create', write: true);
        $data = $request->validate([
            'empresa' => 'required|string|max:160', 'contacto_nombre' => 'nullable|string|max:120', 'contacto_email' => 'nullable|email|max:160',
            'contacto_telefono' => 'nullable|string|max:40', 'nit' => 'nullable|string|max:40', 'ciudad' => 'nullable|string|max:80',
            'pais' => 'nullable|string|max:80', 'moneda' => 'nullable|string|size:3',
        ]);
        $store = $this->store('clientes.json');
        $dup = collect($store->all())->first(fn ($c) => Str::lower(trim((string) ($c['empresa'] ?? ''))) === Str::lower(trim($data['empresa']))
            || (!empty($data['contacto_email']) && Str::lower((string) ($c['contacto_email'] ?? '')) === Str::lower($data['contacto_email'])));
        if ($dup) {
            return Response::error("Ya existe un cliente parecido: {$dup['empresa']} (id: {$dup['id']}). Usa ese id.");
        }
        $settings = $this->store('settings.json')->find('settings') ?: [];
        $client = $store->create(array_merge($data, [
            'estado' => 'Activo',
            'moneda' => strtoupper($data['moneda'] ?? ($settings['base_currency'] ?? 'USD')),
            'proyectos' => 0,
            'invoice_fields' => ['nit' => true, 'direccion' => true, 'telefono' => true, 'email' => true],
            'creado_por' => 'MCP · ' . $ctx->connection,
        ]));

        return Response::text("Cliente creado: {$client['empresa']} (id: {$client['id']}).");
    }
}
