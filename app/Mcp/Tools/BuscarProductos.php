<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProductosController;
use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('buscar_productos')]
#[Description('Busca productos o servicios por ID, SKU, nombre o descripción. Devuelve los campos de edición. Usa el ID para preparar cambios con preparar_producto.')]
#[IsReadOnly]
class BuscarProductos extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Nombre, SKU o parte de la descripción.'),
            'producto_id' => $schema->string()->description('ID exacto del producto o servicio.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('productos.read');
        $data = $request->validate(['texto' => 'nullable|string|max:200', 'producto_id' => 'nullable|string|max:100']);
        $store = $this->store('productos.json');
        $products = isset($data['producto_id'])
            ? array_filter([$store->find($data['producto_id'])])
            : $store->all();
        $text = trim($data['texto'] ?? '');
        $fields = array_fill_keys(['id', ...array_keys(ProductosController::rules())], null);
        $matches = collect($products)
            ->filter(fn ($row) => $this->matches(implode(' ', [$row['nombre'] ?? '', $row['sku'] ?? '', $row['descripcion'] ?? '']), $text))
            ->sortBy('nombre')->take(20)
            ->map(function ($row) use ($fields) {
                $product = array_intersect_key($row, $fields);
                foreach (['nombre', 'descripcion', 'sku'] as $field) {
                    $product[$field] = $this->external($product[$field] ?? null);
                }

                return $product;
            })->values();

        return Response::json(['moneda_base' => ProductosController::baseCurrency(), 'productos' => $matches->all()]);
    }
}
