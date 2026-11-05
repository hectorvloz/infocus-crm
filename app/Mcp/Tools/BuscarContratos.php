<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('buscar_contratos')]
#[Description('Busca contratos por título o cliente. Con contrato_id devuelve el contenido completo.')]
#[IsReadOnly]
class BuscarContratos extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Parte del título o del cliente.'),
            'contrato_id' => $schema->string()->description('Para ver un contrato completo.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.read');
        $d = $request->validate(['texto' => 'nullable|string|max:120', 'contrato_id' => 'nullable|string']);
        $all = collect($this->store('contratos.json')->all());

        if (!empty($d['contrato_id'])) {
            $c = $all->firstWhere('id', $d['contrato_id']);
            abort_if(!$c, 404);

            return Response::text(sprintf("Contrato: %s (id: %s)\nCliente: %s · Estado: %s · Monto: %s\n\n%s",
                $c['titulo'] ?? '-', $c['id'], $c['cliente_nombre'] ?? '-', $c['estado'] ?? '-', $this->money($c['monto'] ?? 0, $c['moneda'] ?? null),
                Str::limit(trim(strip_tags((string) ($c['contenido'] ?? ''))), 12000)));
        }

        $rows = $all->filter(fn ($c) => $this->matches(($c['titulo'] ?? '') . ' ' . ($c['cliente_nombre'] ?? ''), (string) ($d['texto'] ?? '')))
            ->sortByDesc('created_at')->take(30)
            ->map(fn ($c) => sprintf('- %s · %s · %s (id: %s)', $c['titulo'] ?? '-', $c['cliente_nombre'] ?? '-', $c['estado'] ?? '-', $c['id']));

        return Response::text($rows->isEmpty() ? 'No encontré contratos.' : $rows->implode("\n"));
    }
}
