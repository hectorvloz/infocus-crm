<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\RoleAccess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_cliente')]
#[Description('Ficha de un cliente: datos de contacto, facturas y proyectos asociados.')]
#[IsReadOnly]
class VerCliente extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['cliente_id' => $schema->string()->description('Id del cliente (usa buscar_clientes).')->required()];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('clientes.read');
        $id = (string) $request->validate(['cliente_id' => 'required|string'])['cliente_id'];
        $c = $this->store('clientes.json')->find($id);
        abort_if(!$c, 404);

        $out = [
            "Cliente: {$c['empresa']} (id: {$c['id']})",
            'Contacto: ' . ($c['contacto_nombre'] ?? '-') . ' · ' . ($c['contacto_email'] ?? '-') . ' · ' . ($c['contacto_telefono'] ?? '-'),
            'NIT: ' . ($c['nit'] ?? '-') . ' · Ciudad: ' . ($c['ciudad'] ?? '-') . ' · Estado: ' . ($c['estado'] ?? '-') . ' · Moneda: ' . ($c['moneda'] ?? '-'),
        ];
        if (RoleAccess::can(auth()->user(), 'facturas.read')) {
            $facturas = collect($this->store('facturas.json')->all())->where('cliente_id', $id)->sortByDesc('fecha')->take(10);
            $out[] = "\nFacturas recientes:";
            $out[] = $facturas->isEmpty() ? '- ninguna' : $facturas->map(fn ($f) => sprintf('- %s · %s · %s · %s (id: %s)', $f['numero'] ?? '-', $f['fecha'] ?? '-', $this->money($f['total'] ?? 0, $f['moneda'] ?? null), $f['estado'] ?? '-', $f['id']))->implode("\n");
        }
        if (RoleAccess::can(auth()->user(), 'proyectos.read')) {
            $proyectos = collect($this->store('proyectos.json')->all())->where('cliente_id', $id)->take(10);
            $out[] = "\nProyectos:";
            $out[] = $proyectos->isEmpty() ? '- ninguno' : $proyectos->map(fn ($p) => sprintf('- %s · etapa: %s (id: %s)', $p['titulo'] ?? 'Sin título', $p['etapa'] ?? '-', $p['id']))->implode("\n");
        }

        return Response::text(implode("\n", $out));
    }
}
