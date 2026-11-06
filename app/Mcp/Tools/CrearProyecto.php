<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use App\Support\SafeRichText;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_proyecto')]
#[Description('Crea un proyecto (tablero Kanban) completo: columnas, tarjetas con descripción y subtareas. Antes usa listar_proyectos: si el proyecto ya existe, agrégale tareas con crear_tarea en vez de crear otro. Consulta ver_guia("planear-proyecto") para armar las fases.')]
class CrearProyecto extends InfocusTool
{
    public const PRIORITIES = ['Con calma', 'Atención', 'Urgente'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'titulo' => $schema->string()->required(),
            'cliente_id' => $schema->string()->description('Opcional. Usa buscar_clientes para obtenerlo.'),
            'descripcion' => $schema->string()->description('Objetivo y alcance del proyecto (texto o HTML simple).'),
            'prioridad' => $schema->string()->enum(self::PRIORITIES),
            'inicio' => $schema->string()->description('AAAA-MM-DD. Por defecto hoy.'),
            'vencimiento' => $schema->string()->description('AAAA-MM-DD.'),
            'valor' => $schema->number(),
            'columnas' => $schema->array()->items($schema->string())->description('Por defecto: Por hacer, En proceso, Revisión, Terminado.'),
            'tareas' => $schema->array()->items($schema->object([
                'titulo' => $schema->string()->required(),
                'columna' => $schema->string(),
                'descripcion' => $schema->string(),
                'prioridad' => $schema->string()->enum(self::PRIORITIES),
                'vence' => $schema->string(),
                'subtareas' => $schema->array()->items($schema->string()),
            ]))->description('Tarjetas iniciales (máximo 40).'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.create', write: true);
        $d = $request->validate([
            'titulo' => 'required|string|max:180', 'cliente_id' => 'nullable|string', 'descripcion' => 'nullable|string|max:20000',
            'prioridad' => 'nullable|in:' . implode(',', self::PRIORITIES), 'inicio' => 'nullable|date_format:Y-m-d',
            'vencimiento' => 'nullable|date_format:Y-m-d', 'valor' => 'nullable|numeric|min:0',
            'columnas' => 'nullable|array|max:8', 'columnas.*' => 'string|max:60',
            'tareas' => 'nullable|array|max:40', 'tareas.*.titulo' => 'required|string|max:180', 'tareas.*.columna' => 'nullable|string|max:60',
            'tareas.*.descripcion' => 'nullable|string|max:8000', 'tareas.*.prioridad' => 'nullable|in:' . implode(',', self::PRIORITIES),
            'tareas.*.vence' => 'nullable|date_format:Y-m-d', 'tareas.*.subtareas' => 'nullable|array|max:20', 'tareas.*.subtareas.*' => 'string|max:180',
        ]);

        $existing = collect($this->store('proyectos.json')->all())
            ->first(fn ($p) => empty($p['archived']) && Str::lower(Str::ascii(trim((string) ($p['titulo'] ?? '')))) === Str::lower(Str::ascii(trim($d['titulo']))));
        if ($existing) {
            return Response::error("Ya existe el proyecto \"{$existing['titulo']}\" (proyecto_id: {$existing['id']}). Agrégale tareas con crear_tarea en vez de crear otro.");
        }
        $clientId = trim((string) ($d['cliente_id'] ?? ''));
        if ($clientId !== '' && !$this->store('clientes.json')->find($clientId)) {
            return Response::error('No encontré ese cliente. Usa buscar_clientes.');
        }

        $result = $this->callController(ProyectosController::class, 'crear', array_filter([
            'cliente_id' => $clientId !== '' ? $clientId : 'general',
            'titulo' => $d['titulo'],
            'descripcion' => isset($d['descripcion']) ? SafeRichText::sanitize(PrepararCorreo::toHtml($d['descripcion'])) : null,
            'prioridad' => $d['prioridad'] ?? 'Con calma',
            'inicio' => $d['inicio'] ?? now()->toDateString(),
            'vencimiento' => $d['vencimiento'] ?? null,
            'valor' => $d['valor'] ?? null,
            'etapa' => 'En curso',
        ], fn ($v) => $v !== null));
        $project = $result['data']['item'] ?? null;
        if ($result['status'] >= 300 || !$project) {
            return Response::error('No se pudo crear el proyecto.');
        }

        $stages = array_values(array_filter(array_map('trim', $d['columnas'] ?? []))) ?: ['Por hacer', 'En proceso', 'Revisión', 'Terminado'];
        $orders = [];
        $tasks = [];
        foreach ($d['tareas'] ?? [] as $t) {
            $stage = collect($stages)->first(fn ($s) => Str::lower(Str::ascii($s)) === Str::lower(Str::ascii(trim((string) ($t['columna'] ?? ''))))) ?? $stages[0];
            $orders[$stage] = ($orders[$stage] ?? -1) + 1;
            $tasks[] = [
                'id' => (string) Str::ulid(),
                'texto' => Str::limit(trim($t['titulo']), 180, ''),
                'descripcion' => !empty($t['descripcion']) ? SafeRichText::sanitize(PrepararCorreo::toHtml($t['descripcion'])) : '',
                'done' => false,
                'start_date' => null,
                'due_date' => $t['vence'] ?? null,
                'end_date' => $t['vence'] ?? null,
                'priority' => $t['prioridad'] ?? 'Con calma',
                'owners' => [],
                'owner_ids' => [],
                'board_stage' => $stage,
                'board_order' => $orders[$stage],
                'task_type' => 'task',
                'total_seconds' => 0,
                'subtasks' => array_values(array_map(fn ($s) => ['id' => (string) Str::ulid(), 'texto' => Str::limit(trim($s), 180, ''), 'done' => false, 'priority' => 'Con calma'], array_filter($t['subtareas'] ?? [], fn ($s) => trim((string) $s) !== ''))),
                'notes' => [],
            ];
        }
        $this->store('proyectos.json')->update($project['id'], ['task_stages' => $stages, 'tareas' => $tasks]);

        $subtasks = array_sum(array_map(fn ($t) => count($t['subtasks']), $tasks));

        return Response::text("Proyecto creado: \"{$project['titulo']}\" (proyecto_id: {$project['id']}) con " . count($tasks) . " tarjetas y {$subtasks} subtareas en las columnas " . implode(', ', $stages) . '. Ábrelo en Infocus → Proyectos.');
    }
}
