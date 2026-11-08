<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
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
            $this->notify($updated ?: $lead, $data, $agendaItem, false);
            $this->confirmToLead($data, $agendaItem);

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

        $this->notify($created, $data, $agendaItem, true);
        $this->confirmToLead($data, $agendaItem);
        \App\Support\WhatsApp\WhatsAppAlerts::send('lead', trim(sprintf("Lead nuevo: %s (%s%s), desde %s.%s\nSi quieres, pídeme que le redacte la primera respuesta.",
            (string) $created['nombre'], $email, !empty($data['telefono']) ? ', ' . $data['telefono'] : '', (string) ($created['origen'] ?? 'la web'),
            $agendaItem ? ' Agendó reunión: ' . trim((string) (($agendaItem['fecha'] ?? '') . ' ' . ($agendaItem['hora'] ?? ''))) . '.' : '')));

        return response()->json(['ok' => true, 'lead_id' => $created['id'], 'nuevo' => true], 201);
    }

    /**
     * Confirmación con la marca de Indigo para quien agendó (además de la invitación de Google Calendar).
     * El asunto y el cuerpo se pueden personalizar en settings con template_lead_asesoria_subject/body.
     */
    private function confirmToLead(array $data, ?array $meeting): void
    {
        if (!$meeting) {
            return;
        }
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $start = Carbon::parse($meeting['fecha_hora'])->setTimezone('America/Bogota')->locale('es');
        $end = $start->copy()->addMinutes((int) $meeting['duracion_min']);
        $meet = (string) ($meeting['meet_link'] ?? '');
        $vars = [
            '{nombre}' => e(Str::of($data['nombre'])->trim()->explode(' ')->first()),
            '{marca}' => e((string) ($data['marca'] ?? '')),
            '{fecha}' => $start->translatedFormat('l j \\d\\e F'),
            '{hora}' => $start->format('g:i') . ' ' . ($start->format('A') === 'AM' ? 'a. m.' : 'p. m.') . ' – ' . $end->format('g:i') . ' ' . ($end->format('A') === 'AM' ? 'a. m.' : 'p. m.'),
            '{duracion}' => (string) $meeting['duracion_min'],
            '{meet_url}' => e($meet),
            '{meet_button}' => $meet !== '' ? '<a href="' . e($meet) . '" target="_blank" rel="noopener" style="display:inline-block;padding:14px 26px;border-radius:9999px;background:#F1C844;color:#050505;font-size:15px;font-weight:800;text-decoration:none;">Entrar a la reunión</a>' : '',
        ];
        $subject = (string) ($settings['template_lead_asesoria_subject'] ?? 'Tu asesoría con Indigo quedó agendada · {fecha}, {hora}');
        $body = (string) ($settings['template_lead_asesoria_body'] ?? '<p>Hola <strong>{nombre}</strong>,</p>'
            . '<p>Qué bueno que agendaste tu asesoría web gratis con Indigo. Ya te estamos esperando.</p>'
            . '<p><strong>Cuándo:</strong> {fecha}<br><strong>Hora:</strong> {hora} (hora de Colombia)<br><strong>Duración:</strong> {duracion} minutos por Google Meet</p>'
            . '<p>{meet_button}</p>'
            . '<p>En la reunión te contamos qué tipo de web le conviene a tu marca, cuánto cuesta y por dónde empezar. No tienes que preparar nada; si tienes referencias o ideas, tráelas.</p>'
            . '<p>También te llegó la invitación de Google Calendar: acéptala para tenerla en tu agenda. Si necesitas cambiar la hora, responde este correo.</p>'
            . '<p>Nos vemos pronto,<br><strong>Equipo Indigo</strong></p>');

        TemplateMail::send(Str::lower(trim($data['email'])), strtr($subject, array_map('strip_tags', $vars)), strtr($body, $vars), [
            'source' => 'webhook_leads_confirmacion',
            'sent_by_name' => 'Infocus (confirmación de asesoría)',
        ]);
    }

    /** Aviso por correo al equipo. Si el correo falla, el lead igual queda guardado. */
    private function notify(array $lead, array $data, ?array $meeting, bool $isNew): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('services.leads_webhook.notify')))));
        if (!$to) {
            return;
        }

        $when = $meeting ? Carbon::parse($meeting['fecha_hora'])->setTimezone('America/Bogota')->locale('es')->translatedFormat('l j \\d\\e F, g:i a') : null;
        $rows = array_filter([
            'Nombre' => $data['nombre'],
            'Marca' => $data['marca'] ?? null,
            'Correo' => $data['email'],
            'WhatsApp' => $data['telefono'] ?? null,
            'Busca' => $data['tipo_web'] ?? null,
            'Origen' => $data['origen'] ?? 'Landing asesoría',
            'Reunión' => $when,
            'Meet' => $meeting['meet_link'] ?? null,
            'Campaña' => !empty($data['utm']) ? collect($data['utm'])->map(fn ($v, $k) => "$k=$v")->implode(' · ') : null,
        ]);
        $body = '<p>' . ($isNew ? 'Llegó un lead nuevo a Infocus.' : 'Un lead que ya estaba en Infocus volvió a escribir.') . '</p><table cellpadding="6">'
            . collect($rows)->map(fn ($v, $k) => '<tr><td><strong>' . e($k) . '</strong></td><td>' . e($v) . '</td></tr>')->implode('')
            . '</table><p><a href="' . e(url('/leads')) . '">Ver leads en Infocus</a></p>';

        TemplateMail::send($to, ($isNew ? 'Nuevo lead: ' : 'Lead de nuevo: ') . $data['nombre'] . (!empty($data['marca']) ? ' · ' . $data['marca'] : ''), $body, [
            'source' => 'webhook_leads',
            'sent_by_name' => 'Infocus (aviso de leads)',
            'lead_id' => $lead['id'] ?? null,
        ]);
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
