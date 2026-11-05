<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_proyectos')]
#[Description('Lista proyectos activos con su cliente, etapa y avance de tareas. Incluye archivados solo si se pide.')]
#[IsReadOnly]
class ListarProyectos extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Parte del título o del cliente.'),
            'incluir_archivados' => $schema->boolean(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.read');
        $d = $request->validate(['texto' => 'nullable|string|max:120', 'incluir_archivados' => 'nullable|boolean']);
        $rows = collect($this->store('proyectos.json')->all())
            ->when(empty($d['incluir_archivados']), fn ($c) => $c->reject(fn ($p) => !empty($p['archived'])))
            ->filter(fn ($p) => $this->matches(($p['titulo'] ?? '') . ' ' . ($p['cliente'] ?? ''), (string) ($d['texto'] ?? '')))
            ->take(30)
            ->map(function ($p) {
                $tareas = collect($p['tareas'] ?? []);

                return sprintf('- %s (id: %s) · cliente: %s · etapa: %s · tareas %d/%d hechas · vence %s',
                    $p['titulo'] ?? 'Sin título', $p['id'], $p['cliente'] ?? '-', $p['etapa'] ?? '-',
                    $tareas->where('done', true)->count(), $tareas->count(), $p['vencimiento'] ?? '-');
            });

        return Response::text($rows->isEmpty() ? 'No encontré proyectos con ese criterio.' : $rows->implode("\n"));
    }
}
