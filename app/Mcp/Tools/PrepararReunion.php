<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PendingActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('preparar_reunion')]
#[Description('Prepara una reunión (Google Meet o llamada) con un lead o un cliente. No la crea: devuelve la vista previa y un código. Muestra la vista previa al usuario y, solo si la aprueba, llama a confirmar_accion. Al confirmar se crea el evento y se envía la invitación por correo.')]
class PrepararReunion extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->string()->description('Usa lead_id o cliente_id.'),
            'cliente_id' => $schema->string(),
            'titulo' => $schema->string()->required(),
            'fecha' => $schema->string()->description('AAAA-MM-DD.')->required(),
            'hora' => $schema->string()->description('HH:MM en 24 horas, hora de Colombia.')->required(),
            'duracion_min' => $schema->integer()->description('Por defecto 30.'),
            'tipo' => $schema->string()->enum(['meet', 'llamada'])->description('Por defecto meet.'),
            'descripcion' => $schema->string(),
            'invitados' => $schema->array()->items($schema->string())->description('Solo para clientes: correos adicionales.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate([
            'lead_id' => 'nullable|string|required_without:cliente_id', 'cliente_id' => 'nullable|string',
            'titulo' => 'required|string|max:160', 'fecha' => 'required|date_format:Y-m-d', 'hora' => 'required|date_format:H:i',
            'duracion_min' => 'nullable|integer|min:5|max:240', 'tipo' => 'nullable|in:meet,llamada',
            'descripcion' => 'nullable|string|max:1000', 'invitados' => 'nullable|array|max:10', 'invitados.*' => 'email',
        ]);
        $settings = $this->store('settings.json')->find('settings') ?: [];
        $tz = $settings['google_meet_timezone'] ?? config('app.timezone', 'America/Bogota');
        $start = Carbon::parse("{$d['fecha']} {$d['hora']}", $tz);
        if ($start->isPast()) {
            return Response::error('Esa fecha y hora ya pasaron.');
        }
        $minutes = (int) ($d['duracion_min'] ?? 30);
        $meet = ($d['tipo'] ?? 'meet') === 'meet';

        if (!empty($d['lead_id'])) {
            $ctx->allow('leads.create', write: true);
            $lead = $this->store('leads.json')->find($d['lead_id']);
            abort_if(!$lead, 404);
            $who = "Lead: {$lead['nombre']}";
            $recipients = array_filter([(string) ($lead['email'] ?? '')]);
            $payload = ['kind' => 'lead', 'id' => $lead['id'], 'tipo' => $meet ? 'reunion_meet' : 'llamada', 'titulo' => $d['titulo'],
                'fecha_hora' => $start->toIso8601String(), 'duracion_min' => $minutes, 'descripcion' => $d['descripcion'] ?? ''];
        } else {
            $ctx->allow('reuniones.create', write: true);
            $client = $this->store('clientes.json')->find($d['cliente_id']);
            abort_if(!$client, 404);
            $who = "Cliente: {$client['empresa']}";
            $recipients = array_values(array_unique(array_filter(array_merge([(string) ($client['contacto_email'] ?? '')], $d['invitados'] ?? []))));
            $payload = ['kind' => 'cliente', 'cliente_id' => $client['id'], 'titulo' => $d['titulo'], 'fecha' => $start->toDateString(),
                'hora_inicio' => $start->format('H:i'), 'hora_fin' => $start->copy()->addMinutes($minutes)->format('H:i'),
                'notas' => $d['descripcion'] ?? '', 'invitados' => implode(', ', $recipients), 'crear_meet' => $meet];
            if ($start->copy()->addMinutes($minutes)->toDateString() !== $start->toDateString()) {
                return Response::error('La reunión debe terminar el mismo día.');
            }
        }

        $code = (new PendingActions())->create('programar_reunion', $payload, $ctx->connection);

        return Response::text(implode("\n", [
            'Vista previa de la reunión (todavía no se ha creado):',
            $who,
            "Título: {$d['titulo']}",
            'Cuándo: ' . $start->locale('es')->translatedFormat('l j \d\e F Y, H:i') . " ({$tz}) · {$minutes} min",
            'Tipo: ' . ($meet ? 'Google Meet (enlace automático si Google Calendar está conectado)' : 'Llamada'),
            'Invitación por correo a: ' . ($recipients ? implode(', ', $recipients) : 'nadie (no hay correo registrado)'),
            '',
            "Código de confirmación: {$code} (vence en " . PendingActions::TTL_MINUTES . ' minutos).',
            'Pide al usuario que apruebe antes de llamar a confirmar_accion.',
        ]));
    }
}
