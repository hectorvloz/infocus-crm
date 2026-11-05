<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recibe leads de formularios externos (landing de asesoría) y los deja en el CRM
 * con su reunión en la agenda, para que aparezcan en Leads y en Reuniones.
 */
class LeadsWebhookController extends Controller
{
    public function store(Request $request)
    {
        $token = (string) config('services.leads_webhook.token');
        if ($token === '' || !hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['ok' => false, 'message' => 'No autorizado.'], 401);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'telefono' => 'nullable|string|max:40',
            'marca' => 'nullable|string|max:120',
            'tipo_web' => 'nullable|string|max:60',
            'origen' => 'nullable|string|max:80',
            'utm' => 'nullable|array',
            'utm.*' => 'nullable|string|max:160',
            'reunion' => 'nullable|array',
            'reunion.inicio' => 'required_with:reunion|date',
            'reunion.duracion_min' => 'nullable|integer|min:5|max:240',
            'reunion.meet_link' => 'nullable|url|max:500',
            'reunion.google_event_id' => 'nullable|string|max:200',
        ]);

        $store = new FileStore('leads.json');
        $email = Str::lower(trim($data['email']));
        $lead = collect($store->all())->first(fn ($l) => Str::lower(trim((string) ($l['email'] ?? ''))) === $email);

        $agendaItem = null;
        if (!empty($data['reunion'])) {
            $eventId = trim((string) ($data['reunion']['google_event_id'] ?? ''));
            $already = $lead && $eventId !== '' && collect($lead['agenda'] ?? [])->contains(fn ($i) => ($i['google_event_id'] ?? '') === $eventId);
            if ($already) {
                return response()->json(['ok' => true, 'lead_id' => $lead['id'], 'duplicado' => true]);
            }
            $agendaItem = [
                'id' => (string) Str::ulid(),
                'tipo' => 'reunion_meet',
                'titulo' => 'Asesoría web gratis' . (!empty($data['marca']) ? ' · ' . $data['marca'] : ''),
                'fecha_hora' => Carbon::parse($data['reunion']['inicio'])->toISOString(),
                'duracion_min' => (int) ($data['reunion']['duracion_min'] ?? 30),
                'descripcion' => $this->summary($data),
                'estado' => 'programado',
                'creado_en' => now()->toISOString(),
                'creado_por' => 'Landing asesoría',
                'creado_por_iniciales' => 'LA',
                'google_event_id' => $eventId !== '' ? $eventId : null,
                'meet_link' => $data['reunion']['meet_link'] ?? null,
                'meet_error' => null,
            ];
        }

        $nota = ['id' => (string) Str::ulid(), 'texto' => $this->summary($data), 'fecha' => now()->toISOString()];

        if ($lead) {
            $updated = $store->update($lead['id'], array_filter([
                'telefono' => !empty($lead['telefono']) ? null : ($data['telefono'] ?? null),
                'agenda' => $agendaItem ? array_merge($lead['agenda'] ?? [], [$agendaItem]) : null,
                'notas_lista' => array_merge($lead['notas_lista'] ?? [], [$nota]),
                'notas' => $nota['texto'],
            ], fn ($v) => $v !== null));

            return response()->json(['ok' => true, 'lead_id' => $updated['id'] ?? $lead['id'], 'nuevo' => false]);
        }

        $created = $store->create([
            'nombre' => trim($data['nombre']) . (!empty($data['marca']) ? ' · ' . trim($data['marca']) : ''),
            'email' => $email,
            'telefono' => $data['telefono'] ?? null,
            'etapa' => 'Posible cliente',
            'origen' => $data['origen'] ?? 'Landing asesoría',
            'presupuesto_estimado' => null,
            'valor' => null,
            'encargados' => [],
            'notas' => $nota['texto'],
            'notas_lista' => [$nota],
            'agenda' => $agendaItem ? [$agendaItem] : [],
        ]);

        return response()->json(['ok' => true, 'lead_id' => $created['id'], 'nuevo' => true], 201);
    }

    private function summary(array $data): string
    {
        $lines = array_filter([
            !empty($data['marca']) ? 'Marca: ' . $data['marca'] : null,
            !empty($data['tipo_web']) ? 'Busca: ' . $data['tipo_web'] : null,
            !empty($data['telefono']) ? 'WhatsApp: ' . $data['telefono'] : null,
            !empty($data['utm']) ? 'Origen: ' . collect($data['utm'])->map(fn ($v, $k) => "$k=$v")->implode(' · ') : null,
        ]);

        return "Lead de la landing de asesoría.\n" . implode("\n", $lines);
    }
}
