<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\ScheduledTasks;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('programar_tarea')]
#[Description('Programa algo para que Infocus lo haga solo a una hora (hora de Colombia) y te lo mande por WhatsApp o correo. tipo "aviso": manda el texto tal cual (para "recuérdame el viernes a las 3 llamar a Laura"; crea además el recordatorio con crear_recordatorio). tipo "consulta": el asistente ejecuta la instrucción con herramientas de solo lectura y manda lo que encontró (para "todos los lunes a las 8 revisa quién me debe y me dices"). Frecuencia: una_vez (con fecha), diaria, semanal (con dias) o mensual (con dia_mes). Si no dicen hora, usa 08:00.')]
class ProgramarTarea extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'tipo' => $schema->string()->enum(['aviso', 'consulta'])->required(),
            'texto' => $schema->string()->description('aviso: el mensaje a enviar. consulta: la instrucción para el asistente.')->required(),
            'frecuencia' => $schema->string()->enum(['una_vez', 'diaria', 'semanal', 'mensual'])->required(),
            'hora' => $schema->string()->description('HH:MM en 24 horas, hora de Colombia.')->required(),
            'fecha' => $schema->string()->description('AAAA-MM-DD. Obligatoria para una_vez; en las demás, desde cuándo empieza.'),
            'dias' => $schema->array()->items($schema->string())->description('Para semanal: lunes, martes, miércoles, jueves, viernes, sábado, domingo.'),
            'dia_mes' => $schema->integer()->description('Para mensual: día del mes (1 a 31).'),
            'canal' => $schema->string()->enum(['whatsapp', 'correo'])->description('Por defecto whatsapp.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate([
            'tipo' => 'required|in:aviso,consulta',
            'texto' => 'required|string|max:1000',
            'frecuencia' => 'required|in:una_vez,diaria,semanal,mensual',
            'hora' => ['required', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'fecha' => 'nullable|date_format:Y-m-d|required_if:frecuencia,una_vez',
            'dias' => 'nullable|array|required_if:frecuencia,semanal',
            'dias.*' => 'string|max:20',
            'dia_mes' => 'nullable|integer|min:1|max:31|required_if:frecuencia,mensual',
            'canal' => 'nullable|in:whatsapp,correo',
        ]);

        $active = array_filter(ScheduledTasks::forUser(), fn ($t) => !empty($t['activa']));
        if (count($active) >= 30) {
            return Response::error('Ya tienes 30 tareas programadas activas. Pausa alguna con editar_tarea_programada antes de crear otra.');
        }

        [$h, $m] = explode(':', $d['hora']);
        $task = [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Auth::id(),
            'tipo' => $d['tipo'],
            'texto' => trim($d['texto']),
            'frecuencia' => $d['frecuencia'],
            'hora' => sprintf('%02d:%02d', (int) $h, (int) $m),
            'fecha' => $d['fecha'] ?? null,
            'dias' => $d['frecuencia'] === 'semanal' ? ScheduledTasks::parseDays($d['dias'] ?? []) : [],
            'dia_mes' => $d['frecuencia'] === 'mensual' ? (int) $d['dia_mes'] : null,
            'canal' => $d['canal'] ?? 'whatsapp',
            'activa' => true,
            'origen' => $ctx->connection,
        ];
        if ($d['frecuencia'] === 'semanal' && $task['dias'] === []) {
            return Response::error('No entendí los días. Usa nombres como lunes, miércoles, viernes.');
        }

        $next = ScheduledTasks::nextRun($task, CarbonImmutable::now(ScheduledTasks::TZ));
        if (!$next) {
            return Response::error('Esa fecha y hora ya pasaron. Elige una fecha futura.');
        }
        $task['proxima'] = $next->toIso8601String();
        ScheduledTasks::store()->create($task);

        return Response::text("Programado:\n" . ScheduledTasks::describe($task));
    }
}
