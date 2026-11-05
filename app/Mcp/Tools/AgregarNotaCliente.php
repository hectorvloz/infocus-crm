<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Repositories\TimelineStore;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('agregar_nota_cliente')]
#[Description('Agrega una nota a la línea de tiempo de un cliente.')]
class AgregarNotaCliente extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'cliente_id' => $schema->string()->required(),
            'nota' => $schema->string()->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('clientes.update', write: true);
        $d = $request->validate(['cliente_id' => 'required|string', 'nota' => 'required|string|max:4000']);
        $client = $this->store('clientes.json')->find($d['cliente_id']);
        abort_if(!$client, 404);
        (new TimelineStore())->add($d['cliente_id'], 'nota', ['texto' => $d['nota'], 'fuente' => 'MCP']);

        return Response::text("Nota agregada a {$client['empresa']}.");
    }
}
