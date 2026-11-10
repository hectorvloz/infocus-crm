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
#[Description('Crea, renombra, reordena o elimina categorías (listas principales) y listas (sublistas) de recordatorios. Para eliminar el contenido, confirma con el usuario que quiere borrar todos los recordatorios afectados. Los cambios se pueden deshacer con restaurar_cambio.')]
class OrganizarRecordatorios extends InfocusTool
{
    private const ACTIONS = [
        'crear_categoria', 'renombrar_categoria', 'crear_lista', 'renombrar_lista',
        'eliminar_categoria', 'eliminar_lista', 'reordenar_categoria', 'reordenar_lista',
    ];

    public function schema(JsonSchema $schema): array
    {
        return [
            'accion' => $schema->string()->enum(self::ACTIONS)->required(),
            'nombre' => $schema->string()->description('Nombre de la categoría o lista nueva, o la actual si vas a renombrar.')->required(),
            'nuevo_nombre' => $schema->string()->description('Solo para renombrar.'),
            'categoria' => $schema->string()->description('Lista principal de la sublista. Obligatoria para eliminar o reordenar sublistas. Se crea si hace falta al crear una sublista.'),
            'posicion' => $schema->integer()->description('Para reordenar. Posición final, empezando en 1.'),
            'contenido' => $schema->string()->enum(['mover_a_raiz', 'eliminar'])->description('Para eliminar. mover_a_raiz conserva los recordatorios en Sin lista. eliminar borra todos los recordatorios afectados.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'accion' => 'required|in:' . implode(',', self::ACTIONS),
            'posicion' => 'required_if:accion,reordenar_categoria,reordenar_lista|nullable|integer|min:1|max:160',
            'contenido' => 'required_if:accion,eliminar_categoria,eliminar_lista|nullable|in:mover_a_raiz,eliminar',
            'nombre' => 'required|string|max:120', 'nuevo_nombre' => 'nullable|string|max:120',
            'categoria' => 'required_if:accion,eliminar_lista,reordenar_lista|nullable|string|max:120',
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
                [$action, $kind] = explode('_', $d['accion'], 2);
                $isCat = $kind === 'categoria';
                $parent = isset($d['categoria']) ? Reminders::findCategory($payload, $d['categoria']) : null;
                if (!$isCat && isset($d['categoria']) && !$parent) {
                    return Response::error('No encontré la lista principal indicada.');
                }
                $target = $isCat ? Reminders::findCategory($payload, $name) : Reminders::findSection($payload, $name, $parent['id'] ?? null);
                if (!$target) {
                    return Response::error('No encontré «' . $name . '».');
                }
                if ($target['id'] === Reminders::ROOT_CATEGORY || (!$isCat && trim((string) $target['title']) === '')) {
                    return Response::error('La raíz de los recordatorios está reservada.');
                }
                $key = $isCat ? 'categories' : 'sections';
                $label = $isCat ? 'Lista' : 'Sublista';

                if ($action === 'eliminar') {
                    $this->remove($payload, $target, $isCat, $d['contenido'] === 'mover_a_raiz');
                    $msg = "{$label} «{$name}» eliminada. " . ($d['contenido'] === 'mover_a_raiz'
                        ? 'Los recordatorios quedaron en Sin lista.'
                        : 'Se eliminaron sus recordatorios.');
                } elseif ($action === 'reordenar') {
                    $peers = $isCat
                        ? fn ($row) => $row['id'] !== Reminders::ROOT_CATEGORY
                        : fn ($row) => $row['categoryId'] === $target['categoryId'] && trim((string) $row['title']) !== '';
                    $siblings = array_filter($payload[$key], $peers);
                    $positions = array_keys($siblings);
                    $rows = array_values(array_filter($siblings, fn ($row) => $row['id'] !== $target['id']));
                    $position = min((int) $d['posicion'], count($positions));
                    array_splice($rows, $position - 1, 0, [$target]);
                    foreach ($positions as $index => $offset) {
                        $payload[$key][$offset] = $rows[$index];
                    }
                    $msg = "{$label} «{$name}» movida a la posición {$position}.";
                } else {
                    if ($new === '') {
                        return Response::error('Falta nuevo_nombre.');
                    }
                    $payload[$key] = array_map(fn ($row) => $row['id'] === $target['id'] ? array_merge($row, ['title' => $new]) : $row, $payload[$key]);
                    $msg = "{$label} «{$name}» renombrada a «{$new}».";
                }
        }

        $save = fn () => $this->callController(ProfileController::class, 'saveHeaderReminders', ['payload' => $payload]);
        Reminders::row() ? $this->tracked(Reminders::FILE, Reminders::key(), $msg, $save) : $save();

        return Response::text($msg);
    }

    private function remove(array &$payload, array $target, bool $isCat, bool $moveItems): void
    {
        $matches = fn ($section) => ($isCat ? $section['categoryId'] : $section['id']) === $target['id'];
        $removed = collect($payload['sections'])->filter($matches)->pluck('id')->flip();
        $payload['sections'] = array_values(array_filter($payload['sections'], fn ($section) => !$matches($section)));
        if ($isCat) {
            $payload['categories'] = array_values(array_filter($payload['categories'], fn ($category) => $category['id'] !== $target['id']));
        }

        $items = collect($payload['items'] ?? []);
        $affected = fn ($item) => $removed->has($item['sectionId'] ?? '');
        if ($moveItems) {
            $root = Reminders::rootSection($payload);
            $now = now()->valueOf();
            $items = $items->map(fn ($item) => $affected($item) ? array_merge($item, ['sectionId' => $root, 'updatedAt' => $now]) : $item);
        } else {
            $items = $items->reject($affected);
        }
        $payload['items'] = $items->values()->all();
    }
}
