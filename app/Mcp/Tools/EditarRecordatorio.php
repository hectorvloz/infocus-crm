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

#[Name('editar_recordatorio')]
#[Description('Edita un recordatorio: texto, fecha (o quitarla con fecha=""), prioridad, moverlo a otra categoría o lista, marcarlo hecho/pendiente o cambiar el vínculo a un proyecto, tarea o columna. Usa ver_recordatorios para el recordatorio_id. Se puede deshacer con restaurar_cambio.')]
class EditarRecordatorio extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'recordatorio_id' => $schema->string()->required(),
            'texto' => $schema->string(),
            'fecha' => $schema->string()->description('AAAA-MM-DD, o "" para quitarla.'),
            'prioridad' => $schema->string()->enum(['Con calma', 'Atención', 'Urgente', 'Sin prioridad']),
            'categoria' => $schema->string()->description('Mover a esta categoría (se crea si no existe).'),
            'lista' => $schema->string()->description('Mover a esta lista (se crea si no existe).'),
            'hecho' => $schema->boolean(),
            'proyecto_id' => $schema->string()->description('Proyecto que se quiere vincular, o "" para quitar el vínculo.'),
            'tarea_id' => $schema->string()->description('Opcional (con proyecto_id): vincula a esta tarea.'),
            'columna' => $schema->string()->description('Opcional (con proyecto_id): nombre exacto de la columna del tablero. Consulta ver_proyecto.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'recordatorio_id' => 'required|string', 'texto' => 'nullable|string|max:500', 'fecha' => 'nullable|string|max:10',
            'prioridad' => 'nullable|in:Con calma,Atención,Urgente,Sin prioridad', 'categoria' => 'nullable|string|max:120',
            'lista' => 'nullable|string|max:120', 'hecho' => 'nullable|boolean',
            'proyecto_id' => 'required_with:tarea_id,columna|nullable|string',
            'tarea_id' => 'nullable|string|prohibits:columna', 'columna' => 'nullable|string',
        ]);
        if (isset($d['fecha']) && $d['fecha'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) {
            return Response::error('La fecha debe ser AAAA-MM-DD.');
        }
        $payload = Reminders::payload();
        $item = collect($payload['items'] ?? [])->firstWhere('id', $d['recordatorio_id']);
        abort_if(!$item, 404);

        $changes = [];
        $labels = [];
        if (!empty($d['texto'])) { $changes['text'] = $d['texto']; $labels[] = 'texto'; }
        if (array_key_exists('fecha', $request->all())) { $changes['dueDate'] = (string) ($d['fecha'] ?? ''); $labels[] = $changes['dueDate'] === '' ? 'sin fecha' : 'fecha ' . $changes['dueDate']; }
        if (!empty($d['prioridad'])) { $changes['priority'] = $d['prioridad'] === 'Sin prioridad' ? '' : $d['prioridad']; $labels[] = 'prioridad'; }
        if (isset($d['hecho'])) { $changes['done'] = (bool) $d['hecho']; $labels[] = $d['hecho'] ? 'hecho' : 'pendiente'; }
        if (!empty($d['categoria']) || !empty($d['lista'])) {
            $changes['sectionId'] = Reminders::targetSection($payload, $d['categoria'] ?? null, $d['lista'] ?? null);
            $labels[] = 'movido a ' . Reminders::placeOf($payload, ['sectionId' => $changes['sectionId']]);
        }
        if (array_key_exists('proyecto_id', $request->all())) {
            $changes['link'] = empty($d['proyecto_id'])
                ? null
                : Reminders::projectLink($ctx, $d['proyecto_id'], $d['tarea_id'] ?? null, $d['columna'] ?? null);
            $labels[] = $changes['link'] ? 'vínculo actualizado' : 'vínculo quitado';
        }
        if (!$changes) {
            return Response::error('Indica qué cambiar del recordatorio.');
        }
        $changes['updatedAt'] = now()->valueOf();
        $payload['items'] = collect($payload['items'])->map(fn ($i) => ($i['id'] ?? null) === $item['id'] ? array_merge($i, $changes) : $i)->values()->all();

        $result = $this->tracked(Reminders::FILE, Reminders::key(), 'Editó el recordatorio "' . Str::limit((string) $item['text'], 60) . '"',
            fn () => $this->callController(ProfileController::class, 'saveHeaderReminders', ['payload' => $payload]));

        if ($result['status'] >= 300) {
            return Response::error('No se pudo guardar el recordatorio.');
        }

        return Response::text('Recordatorio "' . ($changes['text'] ?? $item['text']) . '" actualizado: ' . implode(', ', $labels) . '.');
    }
}
