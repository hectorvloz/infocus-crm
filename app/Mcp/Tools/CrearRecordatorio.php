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

#[Name('crear_recordatorio')]
#[Description('Crea un recordatorio en tu lista de recordatorios de Infocus (ícono del encabezado), con fecha y prioridad opcionales. Puede vincularse a un proyecto o tarea. Convierte fechas relativas ("el viernes") a AAAA-MM-DD usando la fecha de hoy.')]
class CrearRecordatorio extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->required(),
            'fecha' => $schema->string()->description('AAAA-MM-DD.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente']),
            'proyecto_id' => $schema->string()->description('Opcional: vincula el recordatorio a un proyecto.'),
            'tarea_id' => $schema->string()->description('Opcional (con proyecto_id): vincula a una tarea.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'texto' => 'required|string|max:500', 'fecha' => 'nullable|date_format:Y-m-d',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente', 'proyecto_id' => 'nullable|string', 'tarea_id' => 'nullable|string',
        ]);

        $link = null;
        if (!empty($d['proyecto_id'])) {
            $project = $this->store('proyectos.json')->find($d['proyecto_id']);
            abort_if(!$project, 404);
            $task = !empty($d['tarea_id']) ? collect($project['tareas'] ?? [])->firstWhere('id', $d['tarea_id']) : null;
            abort_if(!empty($d['tarea_id']) && !$task, 404);
            $link = $task
                ? ['type' => 'task', 'id' => $task['id'], 'projectId' => $project['id'], 'taskId' => $task['id'], 'title' => (string) ($task['texto'] ?? 'Tarea'), 'subtitle' => (string) $project['titulo']]
                : ['type' => 'project', 'id' => $project['id'], 'projectId' => $project['id'], 'taskId' => '', 'title' => (string) $project['titulo'], 'subtitle' => 'Proyecto'];
        }

        $payload = Reminders::payload();
        $now = now()->valueOf();
        $payload['items'] = array_values($payload['items'] ?? []);
        array_unshift($payload['items'], [
            'id' => (string) Str::ulid(),
            'sectionId' => $payload['sections'][0]['id'] ?? 'default',
            'text' => $d['texto'],
            'done' => false,
            'priority' => $d['prioridad'] ?? '',
            'dueDate' => $d['fecha'] ?? '',
            'createdAt' => $now,
            'updatedAt' => $now,
            'link' => $link,
        ]);

        $save = fn () => $this->callController(ProfileController::class, 'saveHeaderReminders', ['payload' => $payload]);
        $result = Reminders::row() ? $this->tracked(Reminders::FILE, Reminders::key(), 'Agregó el recordatorio "' . Str::limit($d['texto'], 60) . '"', $save) : $save();
        if ($result['status'] >= 300) {
            return Response::error('No se pudo guardar el recordatorio.');
        }

        return Response::text('Recordatorio creado' . (!empty($d['fecha']) ? " para el {$d['fecha']}" : '') . ': ' . $d['texto'] . '. Si tienes Infocus abierto, aparece al volver a la pestaña.');
    }
}
