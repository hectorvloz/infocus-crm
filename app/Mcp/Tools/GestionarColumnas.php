<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\ProjectBoard;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('gestionar_columnas')]
#[Description('Crea o renombra columnas del tablero Kanban de un proyecto. Al renombrar, sus tareas se mueven con la columna. Después usa crear_tarea con "columna" para poner tareas en ella. No borra columnas.')]
class GestionarColumnas extends InfocusTool
{
    public const MAX_COLUMNAS = 12;

    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'accion' => $schema->string()->enum(['crear', 'renombrar'])->required(),
            'nombre' => $schema->string()->required()->description('Columna a crear, o la columna actual al renombrar.'),
            'nuevo_nombre' => $schema->string()->description('Solo para renombrar.'),
            'posicion' => $schema->integer()->description('Solo para crear: lugar de la columna contando desde 1 (izquierda). Por defecto queda al final.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.update', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string', 'accion' => 'required|in:crear,renombrar',
            'nombre' => 'required|string|max:60', 'nuevo_nombre' => 'nullable|string|max:60', 'posicion' => 'nullable|integer|min:1|max:' . self::MAX_COLUMNAS,
        ]);
        $store = $this->store('proyectos.json');
        $project = $store->find($d['proyecto_id']);
        abort_if(!$project, 404);

        $stages = ProjectBoard::stages($project);
        $nombre = trim($d['nombre']);
        $actual = self::buscar($stages, $nombre);

        if ($d['accion'] === 'crear') {
            if ($actual !== null) {
                return Response::text("La columna \"{$actual}\" ya existe en \"{$project['titulo']}\". Columnas: " . implode(', ', $stages) . '.');
            }
            if (count($stages) >= self::MAX_COLUMNAS) {
                return Response::error('El tablero ya tiene ' . self::MAX_COLUMNAS . ' columnas.');
            }
            $pos = min(max(1, (int) ($d['posicion'] ?? count($stages) + 1)), count($stages) + 1);
            array_splice($stages, $pos - 1, 0, [$nombre]);
            $this->tracked('proyectos.json', $project['id'], "Creó la columna \"{$nombre}\" en el proyecto \"{$project['titulo']}\"",
                fn () => $store->update($project['id'], ['task_stages' => $stages]));

            return Response::text("Columna \"{$nombre}\" creada en \"{$project['titulo']}\". Columnas: " . implode(', ', $stages) . '.');
        }

        $nuevo = trim((string) ($d['nuevo_nombre'] ?? ''));
        if ($nuevo === '') {
            return Response::error('Indica nuevo_nombre para renombrar la columna.');
        }
        if ($actual === null) {
            return Response::error("No existe la columna \"{$nombre}\". Columnas: " . implode(', ', $stages) . '.');
        }
        $choque = self::buscar($stages, $nuevo);
        if ($choque !== null && $choque !== $actual) {
            return Response::error("Ya existe una columna \"{$choque}\".");
        }
        $stages = array_map(fn ($s) => $s === $actual ? $nuevo : $s, $stages);
        $tareas = array_map(function ($t) use ($actual, $nuevo) {
            if (is_array($t) && self::igual((string) ($t['board_stage'] ?? ''), $actual)) {
                $t['board_stage'] = $nuevo;
            }

            return $t;
        }, (array) ($project['tareas'] ?? []));
        $this->tracked('proyectos.json', $project['id'], "Renombró la columna \"{$actual}\" a \"{$nuevo}\" en el proyecto \"{$project['titulo']}\"",
            fn () => $store->update($project['id'], ['task_stages' => $stages, 'tareas' => $tareas]));

        return Response::text("Columna \"{$actual}\" renombrada a \"{$nuevo}\" en \"{$project['titulo']}\". Columnas: " . implode(', ', $stages) . '.');
    }

    public static function buscar(array $stages, string $nombre): ?string
    {
        foreach ($stages as $s) {
            if (self::igual($s, $nombre)) {
                return $s;
            }
        }

        return null;
    }

    private static function igual(string $a, string $b): bool
    {
        return Str::lower(Str::ascii(trim($a))) === Str::lower(Str::ascii(trim($b)));
    }
}
