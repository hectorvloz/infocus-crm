<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Support\LeadTags;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('actualizar_lead')]
#[Description('Mueve un lead de etapa (incluida "No interesado", la zona roja), le agrega una nota de seguimiento, le agrega o quita etiquetas (nichos) y/o le agenda una llamada o reunión. Al agendar, si no indicas etapa, el lead pasa a "Volver a llamar".')]
class ActualizarLead extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'lead_id' => $schema->string()->required(),
            'etapa' => $schema->string()->enum(ListarLeads::ETAPAS),
            'nota' => $schema->string(),
            'agregar_etiquetas' => $schema->array()->items($schema->string())->description('Etiquetas a sumar, ej. ["RentaCar"]. Reutiliza las existentes.'),
            'quitar_etiquetas' => $schema->array()->items($schema->string())->description('Etiquetas a quitar.'),
            'agendar_fecha' => $schema->string()->description('Fecha y hora de la llamada o reunión acordada, hora de Colombia: AAAA-MM-DD HH:MM.'),
            'agendar_titulo' => $schema->string()->description('Título de la actividad, ej. "Asesoría gratuita con Héctor".'),
            'agendar_tipo' => $schema->string()->enum(['llamada', 'reunion_meet'])->description('llamada (por defecto) o reunion_meet.'),
            'agendar_duracion_min' => $schema->integer()->description('Duración en minutos (por defecto 30).'),
            'archivar' => $schema->boolean()->description('true manda el lead a la papelera (sale del tablero, se puede restaurar). No lo borra.'),
            'restaurar' => $schema->boolean()->description('true saca el lead de la papelera y lo devuelve al tablero.'),
            'agendar_meet_url' => $schema->string()->description('Enlace de Google Meet, si la reunión es por Meet. Si no lo das se usa el enlace por defecto de Integraciones.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate([
            'lead_id' => 'required|string', 'etapa' => 'nullable|in:' . implode(',', ListarLeads::ETAPAS), 'nota' => 'nullable|string|max:4000',
            'agregar_etiquetas' => 'nullable|array|max:' . LeadTags::MAX_POR_LEAD, 'agregar_etiquetas.*' => 'string|max:' . LeadTags::MAX_LARGO,
            'quitar_etiquetas' => 'nullable|array', 'quitar_etiquetas.*' => 'string|max:' . LeadTags::MAX_LARGO,
            'agendar_fecha' => 'nullable|date_format:Y-m-d H:i', 'agendar_titulo' => 'nullable|string|max:150',
            'agendar_tipo' => 'nullable|in:llamada,reunion_meet', 'agendar_duracion_min' => 'nullable|integer|min:5|max:480',
            'agendar_meet_url' => 'nullable|url|max:500',
            'archivar' => 'nullable|boolean', 'restaurar' => 'nullable|boolean',
        ]);
        $tocaEtiquetas = !empty($d['agregar_etiquetas']) || !empty($d['quitar_etiquetas']);
        $agenda = !empty($d['agendar_fecha']);
        $archivo = !empty($d['archivar']) ? 'archivar' : (!empty($d['restaurar']) ? 'restaurar' : null);
        if (empty($d['etapa']) && empty($d['nota']) && !$tocaEtiquetas && !$agenda && !$archivo) {
            return Response::error('Indica una etapa nueva, una nota, etiquetas, una fecha para agendar, archivar o restaurar.');
        }
        if ($agenda && empty($d['etapa'])) {
            $d['etapa'] = 'Volver a llamar';
        }
        $ctx->allow(!empty($d['etapa']) || $tocaEtiquetas || $archivo ? 'leads.update' : 'leads.create', write: true);
        $store = $this->store('leads.json');
        $lead = $store->find($d['lead_id']);
        abort_if(!$lead, 404);

        $changes = [];
        if (!empty($d['etapa'])) {
            $changes['etapa'] = $d['etapa'];
        }
        if (!empty($d['nota'])) {
            $changes['notas_lista'] = array_merge($lead['notas_lista'] ?? [], [['id' => (string) Str::ulid(), 'texto' => $d['nota'], 'fecha' => now()->toISOString()]]);
            $changes['notas'] = $d['nota'];
        }
        if ($tocaEtiquetas) {
            $quitar = collect(LeadTags::normalize($d['quitar_etiquetas'] ?? []))->map(fn ($t) => LeadTags::key($t))->all();
            $actuales = collect(LeadTags::of($lead))->reject(fn ($t) => in_array(LeadTags::key($t), $quitar, true))->all();
            $changes['etiquetas'] = LeadTags::normalize(array_merge($actuales, $d['agregar_etiquetas'] ?? []));
        }
        if ($archivo === 'archivar') {
            $changes['archivado_en'] = now()->toISOString();
        } elseif ($archivo === 'restaurar') {
            $changes['archivado_en'] = null;
        }
        $cuando = null;
        if ($agenda) {
            $tz = 'America/Bogota';
            $cuando = Carbon::createFromFormat('Y-m-d H:i', $d['agendar_fecha'], $tz);
            if ($cuando->isPast()) {
                return Response::error('La fecha para agendar ya pasó. Usa una fecha futura (hora de Colombia).');
            }
            $autor = (string) (auth()->user()->name ?? 'Asistente');
            $changes['agenda'] = array_merge(is_array($lead['agenda'] ?? null) ? $lead['agenda'] : [], [[
                'id' => (string) Str::ulid(),
                'tipo' => $d['agendar_tipo'] ?? 'llamada',
                'titulo' => $d['agendar_titulo'] ?? 'Asesoría gratuita',
                'fecha_hora' => $cuando->copy()->utc()->toISOString(),
                'duracion_min' => (int) ($d['agendar_duracion_min'] ?? 30),
                'descripcion' => (string) ($d['nota'] ?? ''),
                'estado' => 'programado',
                'creado_en' => now()->toISOString(),
                'creado_por' => $autor,
                'creado_por_iniciales' => Str::upper(Str::of($autor)->split('/\s+/')->filter()->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode('')),
                'google_event_id' => null,
                'meet_link' => ($d['agendar_tipo'] ?? '') === 'reunion_meet'
                    ? (($d['agendar_meet_url'] ?? null) ?: (($this->store('settings.json')->find('settings') ?: [])['google_meet_fallback_url'] ?? null) ?: null)
                    : null,
                'meet_error' => null,
            ]]);
            $changes['recordatorio'] = $cuando->format('Y-m-d H:i');
        }
        $this->tracked('leads.json', $lead['id'], "Actualizó el lead {$lead['nombre']}", fn () => $store->update($lead['id'], $changes));

        return Response::text("Lead {$lead['nombre']} actualizado" . (!empty($d['etapa']) ? " · etapa: {$d['etapa']}" : '') . (!empty($d['nota']) ? ' · nota agregada' : '') . ($tocaEtiquetas ? ' · etiquetas: ' . (implode(', ', $changes['etiquetas']) ?: 'ninguna') : '') . ($archivo === 'archivar' ? ' · enviado a la papelera' : '') . ($archivo === 'restaurar' ? ' · restaurado al tablero' : '') . ($cuando ? ' · agendado: ' . $cuando->format('Y-m-d H:i') . ' (hora de Colombia), ' . (($d['agendar_tipo'] ?? 'llamada') === 'reunion_meet' ? 'reunión por Meet' . (end($changes['agenda'])['meet_link'] ? ' ' . end($changes['agenda'])['meet_link'] : ' sin enlace todavía') : 'llamada') : '') . '.');
    }
}
