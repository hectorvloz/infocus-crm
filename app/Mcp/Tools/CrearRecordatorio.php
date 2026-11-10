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
#[Description('Crea un recordatorio en tu lista de recordatorios de Infocus (ícono del encabezado), con fecha y prioridad opcionales. Puede vincularse a un proyecto, tarea o columna del tablero. Convierte fechas relativas ("el viernes") a AAAA-MM-DD usando la fecha de hoy.')]
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
            'columna' => $schema->string()->description('Opcional (con proyecto_id): nombre exacto de la columna del tablero. Consulta ver_proyecto.'),
            'categoria' => $schema->string()->description('Opcional: categoría (ej. Espadas). Se crea si no existe.'),
            'lista' => $schema->string()->description('Opcional: lista dentro de la categoría. Se crea si no existe.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'texto' => 'required|string|max:500', 'fecha' => 'nullable|date_format:Y-m-d',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente', 'proyecto_id' => 'required_with:tarea_id,columna|nullable|string',
            'tarea_id' => 'nullable|string|prohibits:columna', 'columna' => 'nullable|string',
            'categoria' => 'nullable|string|max:120', 'lista' => 'nullable|string|max:120',
        ]);

        $link = empty($d['proyecto_id'])
            ? null
            : Reminders::projectLink($ctx, $d['proyecto_id'], $d['tarea_id'] ?? null, $d['columna'] ?? null);

        $payload = Reminders::payload();
        $now = now()->valueOf();
        $payload['items'] = array_values($payload['items'] ?? []);
        $sectionId = Reminders::targetSection($payload, $d['categoria'] ?? null, $d['lista'] ?? null);
        array_unshift($payload['items'], [
            'id' => (string) Str::ulid(),
            'sectionId' => $sectionId,
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

        $place = Reminders::placeOf($payload, ['sectionId' => $sectionId]);

        return Response::text('Recordatorio creado en ' . $place . (!empty($d['fecha']) ? " para el {$d['fecha']}" : '') . ': ' . $d['texto'] . '. Si tienes Infocus abierto, aparece al volver a la pestaña.');
    }
}
