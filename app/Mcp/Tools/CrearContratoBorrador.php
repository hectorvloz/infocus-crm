<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\SafeRichText;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_contrato_borrador')]
#[Description('Crea un contrato en estado Borrador para un cliente. El texto puede usar títulos, párrafos, negritas y listas en HTML simple. Se revisa y envía desde la app.')]
class CrearContratoBorrador extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'cliente_id' => $schema->string()->required(),
            'titulo' => $schema->string()->required(),
            'contenido' => $schema->string()->description('Cuerpo del contrato en HTML simple (h2, p, strong, ul, li).')->required(),
            'monto' => $schema->number(),
            'moneda' => $schema->string()->enum(['COP', 'USD', 'EUR', 'MXN']),
            'proyecto_id' => $schema->string(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.create', write: true);
        $d = $request->validate([
            'cliente_id' => 'required|string', 'titulo' => 'required|string|max:255', 'contenido' => 'required|string|max:60000',
            'monto' => 'nullable|numeric|min:0', 'moneda' => 'nullable|in:COP,USD,EUR,MXN', 'proyecto_id' => 'nullable|string',
        ]);
        $client = $this->store('clientes.json')->find($d['cliente_id']);
        abort_if(!$client, 404);
        $project = !empty($d['proyecto_id']) ? $this->store('proyectos.json')->find($d['proyecto_id']) : null;

        $contract = $this->store('contratos.json')->create([
            'id' => (string) Str::uuid(),
            'titulo' => $d['titulo'], 'cliente_id' => $client['id'], 'proyecto_id' => $project['id'] ?? null,
            'contenido' => SafeRichText::sanitize($d['contenido']),
            'monto' => $d['monto'] ?? null, 'moneda' => $d['moneda'] ?? null, 'estado' => 'Borrador',
            'cliente_nombre' => $client['empresa'] ?? 'Cliente', 'proyecto_nombre' => $project['titulo'] ?? null,
            'firmas' => [],
        ]);

        return Response::text("Contrato en borrador creado: {$contract['titulo']} para {$contract['cliente_nombre']} (id: {$contract['id']}). Revísalo en Infocus → Contratos antes de enviarlo.");
    }
}
