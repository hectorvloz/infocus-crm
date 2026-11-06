<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProfileController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\Reminders;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('organizar_recordatorios')]
#[Description('Crea o renombra categorías (ej. Espadas, Variado) y listas de recordatorios. No borra nada: para quitar una categoría o lista, el usuario lo hace en Infocus.')]
class OrganizarRecordatorios extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'accion' => $schema->string()->enum(['crear_categoria', 'renombrar_categoria', 'crear_lista', 'renombrar_lista'])->required(),
            'nombre' => $schema->string()->description('Nombre de la categoría o lista nueva, o la actual si vas a renombrar.')->required(),
            'nuevo_nombre' => $schema->string()->description('Solo para renombrar.'),
            'categoria' => $schema->string()->description('Para listas: categoría donde va (se crea si no existe).'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'accion' => 'required|in:crear_categoria,renombrar_categoria,crear_lista,renombrar_lista',
            'nombre' => 'required|string|max:120', 'nuevo_nombre' => 'nullable|string|max:120', 'categoria' => 'nullable|string|max:120',
        ]);
        $payload = Reminders::payload();
        $payload['categories'] = array_values($payload['categories'] ?? []);
        $payload['sections'] = array_values($payload['sections'] ?? []);
        $name = trim($d['nombre']);
        $new = trim((string) ($d['nuevo_nombre'] ?? ''));

        switch ($d['accion']) {
            case 'crear_categoria':
                if (Reminders::findCategory($payload, $name)) {
                    return Response::error("La categoría «{$name}» ya existe.");
                }
                Reminders::targetSection($payload, $name, null);
                $msg = "Categoría «{$name}» creada.";
                break;
            case 'crear_lista':
                $catId = !empty($d['categoria']) ? (Reminders::findCategory($payload, $d['categoria'])['id'] ?? null) : null;
                if (Reminders::findSection($payload, $name, $catId)) {
                    return Response::error("La lista «{$name}» ya existe.");
                }
                Reminders::targetSection($payload, $d['categoria'] ?? null, $name);
                $msg = "Lista «{$name}» creada" . (!empty($d['categoria']) ? " en {$d['categoria']}" : '') . '.';
                break;
            default:
                if ($new === '') {
                    return Response::error('Falta nuevo_nombre.');
                }
                $isCat = $d['accion'] === 'renombrar_categoria';
                $target = $isCat ? Reminders::findCategory($payload, $name) : Reminders::findSection($payload, $name);
                if (!$target) {
                    return Response::error('No encontré «' . $name . '».');
                }
                $key = $isCat ? 'categories' : 'sections';
                $payload[$key] = array_map(fn ($x) => ($x['id'] ?? null) === $target['id'] ? array_merge($x, ['title' => Str::limit($new, 120, '')]) : $x, $payload[$key]);
                $msg = ($isCat ? 'Categoría' : 'Lista') . " «{$name}» renombrada a «{$new}».";
        }

        $save = fn () => $this->callController(ProfileController::class, 'saveHeaderReminders', ['payload' => $payload]);
        Reminders::row() ? $this->tracked(Reminders::FILE, Reminders::key(), $msg, $save) : $save();

        return Response::text($msg);
    }
}
