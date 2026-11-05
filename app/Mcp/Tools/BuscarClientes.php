<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('buscar_clientes')]
#[Description('Busca clientes por empresa, contacto, correo o NIT. Devuelve el id que usan las demás herramientas.')]
#[IsReadOnly]
class BuscarClientes extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Parte del nombre, correo o NIT. Vacío lista los primeros clientes.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('clientes.read');
        $q = trim((string) $request->get('texto', ''));
        $rows = collect($this->store('clientes.json')->all())
            ->filter(fn ($c) => $this->matches(implode(' ', [$c['empresa'] ?? '', $c['contacto_nombre'] ?? '', $c['contacto_email'] ?? '', $c['nit'] ?? '']), $q))
            ->take(25)
            ->map(fn ($c) => sprintf('- %s (id: %s) · contacto: %s · %s · estado: %s',
                $c['empresa'] ?? 'Sin nombre', $c['id'], $c['contacto_nombre'] ?? '-', $c['contacto_email'] ?? 'sin correo', $c['estado'] ?? '-'));

        return Response::text($rows->isEmpty() ? 'No encontré clientes con ese criterio.' : $rows->implode("\n"));
    }
}
