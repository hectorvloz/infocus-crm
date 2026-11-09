<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProductosController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_producto')]
#[Description('Prepara crear o editar un producto o servicio. Sin producto_id es un alta y requiere nombre, tipo y precio base. Pregunta por los datos obligatorios que falten. Muestra la ficha, indica los campos vacíos o valores por defecto y pregunta si desea completarla o guardarla así. Solo después de aprobación explícita llama a confirmar_accion.')]
class PrepararProducto extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'producto_id' => $schema->string()->description('ID devuelto por buscar_productos. Omítelo al crear.'),
            'nombre' => $schema->string(),
            'descripcion' => $schema->string()->nullable(),
            'precio' => $schema->number()->min(0)->description('Precio en la moneda base del catálogo.'),
            'sku' => $schema->string()->nullable(),
            'tipo' => $schema->string()->enum(['Producto', 'Servicio']),
            'stock' => $schema->number()->nullable(),
            'service_expiry_reminder_enabled' => $schema->boolean()->description('Activa avisos de vencimiento del servicio.'),
            'service_expiry_reminder_days_before' => $schema->integer()->min(1)->max(90)->description('Días de anticipación. Si se activa sin indicar días, se usan 7.'),
            'precios' => $schema->object(array_fill_keys(ProductosController::CURRENCIES, $schema->number()->min(0)->nullable()))
                ->description('Precios por moneda, ej. {"COP": 500000}. En edición se conservan las monedas omitidas. Usa null en una moneda para quitar su precio.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $id = $request->validate(['producto_id' => 'sometimes|required|string|max:100'])['producto_id'] ?? null;
        $ctx->allow($id === null ? 'productos.create' : 'productos.update', write: true);
        $rules = ProductosController::rules();
        if ($id !== null) {
            foreach (['nombre', 'precio', 'tipo'] as $field) {
                $rules[$field] = 'sometimes|' . $rules[$field];
            }
        }
        // El objeto vacío es válido y representa que no se propusieron monedas adicionales.
        $rules['precios'] = 'sometimes|array:' . implode(',', ProductosController::CURRENCIES);
        $fields = $request->validate($rules);
        $store = $this->store('productos.json');
        $current = $id === null ? null : $store->find($id);
        abort_if($id !== null && $current === null, 404);
        $base = ProductosController::baseCurrency();
        if (isset($fields['precios'][$base])) {
            return Response::error("Indica el precio en {$base} en el campo precio. Los precios adicionales son para otras monedas.");
        }
        if ($fields === []) {
            return Response::error('Indica al menos un campo que quieras editar.');
        }

        $existing = array_intersect_key($current ?? [], $rules);
        $data = array_replace($existing, $fields);
        if (isset($fields['precios'])) {
            $data['precios'] = array_replace($existing['precios'] ?? [], $fields['precios']);
        }
        if (isset($fields['service_expiry_reminder_days_before']) && empty($data['service_expiry_reminder_enabled'])) {
            return Response::error('Para indicar días de anticipación, activa el recordatorio de vencimiento.');
        }
        $data = ProductosController::normalizeFields($data);
        $data['precios'] ??= [];
        if ($current === null && self::duplicate($store->all(), $data)) {
            return Response::error('Ya hay un registro con ese SKU o nombre y tipo. Usa buscar_productos para revisarlo antes de crear otro.');
        }

        $code = (new PendingActions())->create('guardar_producto', [
            'id' => $id,
            'before' => $current,
            'data' => $data,
            'moneda' => $base,
        ], $ctx->connection);
        $prices = collect($data['precios'])->map(fn ($price, $currency) => "{$currency} {$price}")->implode(', ');
        $reminder = $data['service_expiry_reminder_enabled']
            ? "Activado, {$data['service_expiry_reminder_days_before']} días antes"
            : 'Desactivado';
        $action = $id === null ? 'Crear' : 'Editar';

        return Response::text(implode("\n", [
            "Vista previa · {$action} producto o servicio",
            'Nombre: ' . $this->external($data['nombre'] ?? null),
            'Tipo: ' . ($data['tipo'] ?? 'sin indicar'),
            'Descripción: ' . ($this->external($data['descripcion'] ?? null) ?: 'sin indicar'),
            "Precio base ({$base}): " . ($data['precio'] ?? 'sin indicar'),
            'SKU: ' . ($this->external($data['sku'] ?? null) ?: 'sin indicar'),
            'Stock: ' . ($data['stock'] ?? 'sin indicar'),
            'Precios adicionales: ' . ($prices ?: 'sin indicar'),
            'Aviso de vencimiento: ' . $reminder,
            '¿Quieres completar algún dato o guardar así?',
            "Código de confirmación: {$code} (vence en " . PendingActions::TTL_MINUTES . ' minutos).',
        ]));
    }

    public static function duplicate(array $products, array $data): bool
    {
        $normalize = fn ($value) => Str::lower(Str::squish((string) $value));
        $sku = $normalize($data['sku'] ?? '');
        $name = $normalize($data['nombre']);

        return collect($products)->contains(fn ($row) =>
            ($sku !== '' && $normalize($row['sku'] ?? '') === $sku)
            || (($row['tipo'] ?? '') === $data['tipo'] && $normalize($row['nombre'] ?? '') === $name));
    }
}
