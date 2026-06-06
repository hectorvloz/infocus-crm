<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Mail\GenericMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LeadsController extends Controller
{
    protected FileStore $store;
    protected FileStore $settings;
    protected FileStore $users;

    public function __construct()
    {
        $this->store = new FileStore('leads.json');
        $this->settings = new FileStore('settings.json');
        $this->users = new FileStore('users.json');
    }

    protected function assignableUsers(): array
    {
        $users = $this->users->all();
        return collect($users)
            ->map(function ($user) {
                $name = trim((string) ($user['name'] ?? ''));
                $email = trim((string) ($user['email'] ?? ''));
                if ($name === '' && $email === '') {
                    return null;
                }
                return [
                    'id' => (string) ($user['id'] ?? ''),
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeStage(string $etapa): string
    {
        $map = [
            'Nuevo' => 'Posible cliente',
            'Calificado' => 'Posible cliente',
            'Propuesta' => 'Contactado',
            'Llamar' => 'Volver a llamar',
            'Seguimiento' => 'Volver a llamar',
            'Ganado' => 'Cliente',
            'Perdido' => 'Posible cliente',
        ];
        $allowed = ['Posible cliente','Contactado','Volver a llamar','Cliente'];
        if (in_array($etapa, $allowed)) return $etapa;
        return $map[$etapa] ?? 'Posible cliente';
    }

    public function index()
    {
        $leads = $this->store->all();
        return view('leads.kanban', [
            'leads' => $leads,
            'assignableUsers' => $this->assignableUsers(),
        ]);
    }

    public function create()
    {
        return view('leads.create', [
            'assignableUsers' => $this->assignableUsers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'etapa' => 'required|string',
            'presupuesto_estimado' => 'nullable|numeric',
            'valor' => 'nullable|numeric',
            'origen' => 'nullable|string',
            'notas' => 'nullable|string',
            'recordatorio' => 'nullable|string',
            'encargados' => 'nullable|array',
            'encargados.*' => 'nullable|string|max:160',
        ]);
        $budget = $data['presupuesto_estimado'] ?? $data['valor'] ?? null;
        $data['presupuesto_estimado'] = $budget;
        $data['valor'] = $budget;
        $data['etapa'] = $this->normalizeStage($data['etapa']);
        $data['encargados'] = collect($data['encargados'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->store->create($data);
        return redirect()->route('leads.index')->with('success', 'Lead creado correctamente.');
    }

    public function edit(string $id)
    {
        $lead = $this->store->find($id);
        abort_if(!$lead, 404);
        return view('leads.edit', [
            'lead' => $lead,
            'assignableUsers' => $this->assignableUsers(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'etapa' => 'required|string',
            'presupuesto_estimado' => 'nullable|numeric',
            'valor' => 'nullable|numeric',
            'origen' => 'nullable|string',
            'notas' => 'nullable|string',
            'recordatorio' => 'nullable|string',
            'encargados' => 'nullable|array',
            'encargados.*' => 'nullable|string|max:160',
        ]);
        $budget = $data['presupuesto_estimado'] ?? $data['valor'] ?? null;
        $data['presupuesto_estimado'] = $budget;
        $data['valor'] = $budget;
        $data['etapa'] = $this->normalizeStage($data['etapa']);
        $data['encargados'] = collect($data['encargados'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $this->store->update($id, $data);
        return redirect()->route('leads.index');
    }

    public function destroy(string $id)
    {
        $this->store->delete($id);
        return redirect()->route('leads.index');
    }

    public function mover(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'etapa' => 'required|string',
        ]);
        $updated = $this->store->update($data['id'], ['etapa'=>$this->normalizeStage($data['etapa'])]);
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function showJson(string $id)
    {
        $lead = $this->store->find($id);
        abort_if(!$lead, 404);
        $lead['encargados'] = array_values(array_filter((array) ($lead['encargados'] ?? [])));
        return response()->json(['ok' => true, 'item' => $lead]);
    }

    public function notaAgregar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'texto' => 'required|string',
        ]);
        $lead = $this->store->find($data['id']);
        abort_if(!$lead, 404);
        $notas = $lead['notas_lista'] ?? [];
        $nota = [
            'id' => (string) Str::ulid(),
            'texto' => $data['texto'],
            'fecha' => now()->toISOString(),
        ];
        $notas[] = $nota;
        $updated = $this->store->update($data['id'], [
            'notas_lista' => $notas,
            'notas' => $data['texto'],
        ]);
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function emailEnviar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'subject' => 'required|string|max:190',
            'body' => 'required|string|max:8000',
        ]);

        $lead = $this->store->find($data['id']);
        abort_if(!$lead, 404);

        $to = strtolower(trim((string) ($lead['email'] ?? '')));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'message' => 'Este lead no tiene un email válido.'], 422);
        }

        $settings = $this->settings->find('settings') ?: [];
        $sender = $this->currentSenderIdentity();
        $mailerName = $this->configureLeadMailer($settings, $sender);
        $fromAddress = $mailerName === 'lead_user'
            ? (string) ($sender['smtp_username'] ?? $sender['email'])
            : (string) ($settings['mail_from_address'] ?? $settings['email_from'] ?? $settings['smtp_username'] ?? '');
        $fromName = $mailerName === 'lead_user'
            ? (string) ($sender['name'] ?: $sender['email'])
            : (string) ($settings['mail_from_name'] ?? $settings['company_name'] ?? config('app.name'));

        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'message' => 'No hay remitente SMTP válido configurado.'], 422);
        }

        try {
            $message = (new GenericMail((string) $data['subject'], (string) $data['body']))->from($fromAddress, $fromName);
            if (strtolower($to) !== strtolower($fromAddress)) {
                $message->bcc($fromAddress);
            }
            Mail::mailer($mailerName)
                ->to($to)
                ->send($message);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'No se pudo enviar el correo: ' . $e->getMessage()], 500);
        }

        $emails = is_array($lead['emails_enviados'] ?? null) ? $lead['emails_enviados'] : [];
        $emails[] = [
            'id' => (string) Str::ulid(),
            'subject' => (string) $data['subject'],
            'body' => (string) $data['body'],
            'to' => $to,
            'from' => $fromAddress,
            'from_name' => $fromName,
            'fecha' => now()->toISOString(),
            'creado_por' => $sender['name'] ?: $sender['email'] ?: 'Sistema',
        ];

        $updated = $this->store->update((string) $data['id'], ['emails_enviados' => $emails]);

        try {
            (new FileStore('email_history.json'))->create([
                'to' => $to,
                'subject' => (string) $data['subject'],
                'body' => (string) $data['body'],
                'sent_by' => $fromAddress,
                'source' => 'lead_manual',
                'lead_id' => (string) ($lead['id'] ?? ''),
                'created_at' => now()->toISOString(),
            ]);
        } catch (\Throwable) {
            // El historial no debe bloquear el envio.
        }

        return response()->json(['ok' => true, 'item' => $updated]);
    }

    protected function currentSenderIdentity(): array
    {
        $email = strtolower(trim((string) (auth()->user()->email ?? session('user.email', ''))));
        $name = trim((string) (auth()->user()->name ?? session('user.name', '')));
        $settings = $this->settings->find('settings') ?: [];
        $userSmtp = is_array($settings['user_smtp'] ?? null) ? $settings['user_smtp'] : [];
        $smtp = $email !== '' ? ($userSmtp[$email] ?? []) : [];

        return [
            'email' => $email,
            'name' => $name,
            'smtp_username' => trim((string) ($smtp['username'] ?? '')),
            'smtp_password' => (string) ($smtp['password'] ?? ''),
        ];
    }

    protected function configureLeadMailer(array $settings, array $sender): string
    {
        $host = trim((string) ($settings['smtp_host'] ?? ''));
        $port = (int) ($settings['smtp_port'] ?? 587);
        $encryption = (string) ($settings['smtp_encryption'] ?? 'tls');
        $username = trim((string) ($sender['smtp_username'] ?? ''));
        $password = $this->decryptSetting((string) ($sender['smtp_password'] ?? ''));

        if ($host !== '' && $username !== '' && $password !== '') {
            Config::set('mail.mailers.lead_user', [
                'transport' => 'smtp',
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption === 'none' ? null : $encryption,
                'username' => $username,
                'password' => $password,
                'timeout' => null,
                'local_domain' => config('mail.mailers.smtp.local_domain'),
            ]);
            Mail::purge('lead_user');
            return 'lead_user';
        }

        return config('mail.default', 'smtp');
    }

    public function agendaProgramar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tipo' => 'required|in:llamada,reunion_meet',
            'titulo' => 'required|string|max:160',
            'fecha_hora' => 'required|date',
            'duracion_min' => 'required|integer|min:5|max:240',
            'descripcion' => 'nullable|string|max:1000',
            'meet_url' => 'nullable|url|max:500',
        ]);

        $lead = $this->store->find($data['id']);
        abort_if(!$lead, 404);

        $settings = $this->settings->find('settings') ?: [];
        $manualMeetUrl = trim((string) ($data['meet_url'] ?? ''));
        $isMeetAuto = ($data['tipo'] === 'reunion_meet' && $manualMeetUrl === '');
        if ($isMeetAuto) {
            $calendarLinked = !empty($settings['google_calendar_access_token']) || !empty($settings['google_calendar_refresh_token']);
            if (empty($settings['google_meet_enabled'])) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No puedes crear Meet automático porque está desactivado en Integraciones > Google Meet.',
                ], 422);
            }
            if (empty($settings['google_calendar_enabled']) || !$calendarLinked) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No puedes crear Meet automático: Google Calendar no está vinculado. Conéctalo por OAuth o usa URL manual.',
                ], 422);
            }
        }

        $agenda = $lead['agenda'] ?? [];

        $item = [
            'id' => (string) Str::ulid(),
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'fecha_hora' => Carbon::parse($data['fecha_hora'])->toISOString(),
            'duracion_min' => (int) ($data['duracion_min'] ?? ($settings['google_meet_default_duration'] ?? 30)),
            'descripcion' => trim((string) ($data['descripcion'] ?? '')),
            'estado' => 'programado',
            'creado_en' => now()->toISOString(),
            'creado_por' => auth()->user()->name ?? 'Sistema',
            'creado_por_iniciales' => Str::upper(Str::of((string) (auth()->user()->name ?? 'SI'))->split('/\s+/')->filter()->take(2)->map(fn($p) => Str::substr($p, 0, 1))->implode('')),
            'google_event_id' => null,
            'meet_link' => null,
            'meet_error' => null,
        ];

        if ($item['tipo'] === 'reunion_meet') {
            if ($manualMeetUrl !== '') {
                $item['meet_link'] = $manualMeetUrl;
                $item['meet_error'] = null;
            } elseif (!empty($settings['google_meet_enabled'])) {
                if (empty($settings['google_calendar_access_token']) && empty($settings['google_calendar_refresh_token'])) {
                    $item['meet_error'] = 'No está enlazado Google Calendar. Conéctalo en Ajustes > Integraciones > Google Calendar (OAuth).';
                } else {
                    $calendarPayload = $this->buildLeadCalendarPayload($lead, $item, $settings);
                    if ($calendarPayload) {
                        $calendarData = $this->createGoogleCalendarEventWithMeet($calendarPayload, $settings);
                        if ($calendarData) {
                            $item['google_event_id'] = $calendarData['event_id'] ?? null;
                            $item['meet_link'] = $calendarData['meet_link'] ?? null;
                            $item['meet_error'] = $calendarData['error'] ?? null;
                        } else {
                            $item['meet_error'] = 'No se pudo crear la reunión automática. Revisa permisos OAuth y conexión de Google Calendar.';
                        }
                    }
                }
            } else {
                $item['meet_error'] = 'La creación automática de Meet está desactivada en Integraciones.';
            }

            if (empty($item['meet_link']) && !empty($settings['google_meet_fallback_url'])) {
                $item['meet_link'] = $settings['google_meet_fallback_url'];
                if (!empty($item['meet_error'])) {
                    $item['meet_error'] .= ' Se usó URL fallback.';
                }
            }
        }

        $agenda[] = $item;

        $recordatorio = Carbon::parse($data['fecha_hora'])->format('Y-m-d H:i');
        $updated = $this->store->update($data['id'], [
            'agenda' => $agenda,
            'recordatorio' => $recordatorio,
        ]);

        $this->sendLeadActivityEmail($updated ?: $lead, $item, $settings);

        return response()->json(['ok' => true, 'item' => $updated, 'agenda_item' => $item]);
    }

    protected function sendLeadActivityEmail(array $lead, array $agendaItem, array $settings): void
    {
        $to = trim((string) ($lead['email'] ?? ''));
        if ($to === '') {
            return;
        }

        try {
            $start = Carbon::parse((string) ($agendaItem['fecha_hora'] ?? now()->toISOString()));
            $end = (clone $start)->addMinutes(max(5, (int) ($agendaItem['duracion_min'] ?? 30)));
            $isMeet = ($agendaItem['tipo'] ?? '') === 'reunion_meet';
            $appName = trim((string) ($settings['app_name'] ?? config('app.name', 'Infocus CRM')));
            if ($appName === '') {
                $appName = 'Infocus CRM';
            }

            $title = (string) ($agendaItem['titulo'] ?? 'Actividad programada');
            $startAt = $start->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y · h:i A');
            $endAt = $end->locale('es')->translatedFormat('h:i A');
            $durationMin = (int) ($agendaItem['duracion_min'] ?? 30);
            $description = trim((string) ($agendaItem['descripcion'] ?? ''));
            $meetUrl = (string) ($agendaItem['meet_link'] ?? '');
            $meetButton = ($isMeet && $meetUrl !== '')
                ? '<a href="' . e($meetUrl) . '" target="_blank" rel="noopener" style="display:inline-block;padding:14px 24px;border-radius:9999px;background:#10b981;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;">Unirme a Google Meet</a>'
                : '';

            $vars = [
                '{lead_nombre}' => (string) ($lead['nombre'] ?? 'Cliente'),
                '{empresa}' => $appName,
                '{actividad_titulo}' => $title,
                '{fecha_inicio}' => $startAt,
                '{fecha_fin}' => $endAt,
                '{duracion_min}' => (string) $durationMin,
                '{descripcion}' => $description !== '' ? e($description) : 'Sin descripción.',
                '{meet_url}' => $meetUrl,
                '{meet_button}' => $meetButton,
            ];

            $subjectTpl = (string) ($settings['template_lead_meet_scheduled_subject'] ?? 'Reunión programada: {actividad_titulo}');
            $bodyTpl = (string) ($settings['template_lead_meet_scheduled_body'] ?? '<p>Hola <strong>{lead_nombre}</strong>,</p><p>Tu reunión fue programada desde <strong>{empresa}</strong>.</p><p><strong>Título:</strong> {actividad_titulo}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>');
            $subject = strtr($subjectTpl, $vars);
            $body = strtr($bodyTpl, $vars);

            Mail::to($to)->send(new GenericMail($subject, $body));
        } catch (\Throwable $e) {
            // No interrumpir la programación por errores de correo.
        }
    }

    public function timerIniciar(Request $request)
    {
        $data = $request->validate(['id' => 'required|string']);
        $lead = $this->store->find($data['id']);
        abort_if(!$lead, 404);

        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;

        $existing = $this->settings->find($timerId);
        if ($existing) {
            $this->settings->delete($timerId);
        }

        $this->settings->create([
            'id' => $timerId,
            'lead_id' => $data['id'],
            'lead_nombre' => $lead['nombre'] ?? 'Lead',
            'started_at' => now()->toISOString(),
            'accumulated_seconds' => 0,
            'is_paused' => false,
            'paused_at' => null,
            'user_id' => $userId,
            'user_name' => auth()->user()->name ?? 'Usuario',
        ]);

        return response()->json(['ok' => true, 'lead_nombre' => $lead['nombre'] ?? 'Lead']);
    }

    public function timerDetener(Request $request)
    {
        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;
        $timer = $this->settings->find($timerId);

        if (!$timer || (empty($timer['started_at']) && empty($timer['is_paused']))) {
            return response()->json(['ok' => false, 'message' => 'No hay temporizador activo']);
        }
        $acc = (int) ($timer['accumulated_seconds'] ?? 0);
        $elapsed = $acc;
        if (empty($timer['is_paused']) && !empty($timer['started_at'])) {
            $elapsed += Carbon::parse($timer['started_at'])->diffInSeconds(now());
        }
        $duracion_min = max(1, (int) round($elapsed / 60));
        $lead = $this->store->find($timer['lead_id']);
        abort_if(!$lead, 404);

        $tiempo = $lead['tiempo_trabajado'] ?? [];
        $tiempo[] = [
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'user_name' => auth()->user()->name ?? 'Usuario',
            'duracion_min' => $duracion_min,
            'fecha' => now()->toISOString(),
        ];
        $tiempo_total_min = array_sum(array_column($tiempo, 'duracion_min'));

        $this->store->update($timer['lead_id'], [
            'tiempo_trabajado' => $tiempo,
            'tiempo_total_min' => $tiempo_total_min,
        ]);

        $this->settings->delete($timerId);

        return response()->json(['ok' => true, 'duracion_min' => $duracion_min, 'lead_id' => $timer['lead_id'], 'tiempo_total_min' => $tiempo_total_min]);
    }

    public function timerPausar(Request $request)
    {
        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;
        $timer = $this->settings->find($timerId);
        if (!$timer || !empty($timer['is_paused']) || empty($timer['started_at'])) {
            return response()->json(['ok' => false, 'message' => 'No hay temporizador activo en ejecución'], 422);
        }
        $acc = (int) ($timer['accumulated_seconds'] ?? 0);
        $acc += Carbon::parse($timer['started_at'])->diffInSeconds(now());
        $this->settings->update($timerId, [
            'accumulated_seconds' => max(0, $acc),
            'is_paused' => true,
            'paused_at' => now()->toISOString(),
            'started_at' => null,
        ]);
        return response()->json(['ok' => true]);
    }

    public function timerReanudar(Request $request)
    {
        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;
        $timer = $this->settings->find($timerId);
        if (!$timer || empty($timer['is_paused'])) {
            return response()->json(['ok' => false, 'message' => 'No hay temporizador pausado'], 422);
        }
        $this->settings->update($timerId, [
            'is_paused' => false,
            'paused_at' => null,
            'started_at' => now()->toISOString(),
        ]);
        return response()->json(['ok' => true]);
    }

    public function timerEliminar(Request $request)
    {
        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;
        $timer = $this->settings->find($timerId);
        if ($timer) {
            $this->settings->delete($timerId);
        }
        return response()->json(['ok' => true]);
    }

    public function timerActivo()
    {
        $userId = (string) auth()->id();
        $timerId = 'lead_timer_' . $userId;
        $timer = $this->settings->find($timerId);

        if (!$timer || (empty($timer['started_at']) && empty($timer['is_paused']))) {
            return response()->json(['ok' => true, 'active' => false]);
        }
        $acc = (int) ($timer['accumulated_seconds'] ?? 0);
        $elapsed = $acc;
        if (empty($timer['is_paused']) && !empty($timer['started_at'])) {
            $elapsed += Carbon::parse($timer['started_at'])->diffInSeconds(now());
        }
        return response()->json([
            'ok' => true,
            'active' => true,
            'timer' => $timer,
            'elapsed_seconds' => max(0, $elapsed),
            'is_paused' => !empty($timer['is_paused']),
        ]);
    }

    public function tiempoManual(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'horas' => 'nullable|integer|min:0|max:999',
            'minutos' => 'nullable|integer|min:0|max:59',
        ]);

        $lead = $this->store->find($data['id']);
        abort_if(!$lead, 404);

        $hours = (int) ($data['horas'] ?? 0);
        $minutes = (int) ($data['minutos'] ?? 0);
        $duracionMin = ($hours * 60) + $minutes;
        if ($duracionMin <= 0) {
            return response()->json(['ok' => false, 'message' => 'Tiempo invalido'], 422);
        }

        $tiempo = $lead['tiempo_trabajado'] ?? [];
        $tiempo[] = [
            'id' => (string) Str::ulid(),
            'user_id' => (string) auth()->id(),
            'user_name' => auth()->user()->name ?? 'Usuario',
            'duracion_min' => $duracionMin,
            'fecha' => now()->toISOString(),
            'source' => 'manual',
        ];

        $tiempoTotalMin = array_sum(array_map(fn($x) => (int) ($x['duracion_min'] ?? 0), $tiempo));

        $updated = $this->store->update($data['id'], [
            'tiempo_trabajado' => $tiempo,
            'tiempo_total_min' => $tiempoTotalMin,
        ]);

        return response()->json([
            'ok' => true,
            'item' => $updated,
            'duracion_min' => $duracionMin,
            'tiempo_total_min' => $tiempoTotalMin,
        ]);
    }

    protected function createGoogleCalendarEventWithMeet(array $payload, array $settings): ?array
    {
        $token = $this->getGoogleCalendarAccessToken($settings);
        if (!$token) {
            return ['error' => 'No hay token válido de Google Calendar. Vuelve a conectar OAuth.'];
        }

        $calendarId = $settings['google_calendar_id'] ?? 'primary';
        $calendarPath = rawurlencode($calendarId);
        $response = Http::withToken($token)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarPath}/events?conferenceDataVersion=1", $payload);

        if (!$response->ok()) {
            $status = $response->status();
            $message = (string) ($response->json('error.message') ?? '');
            return ['error' => 'Google Calendar respondió ' . $status . ($message ? (': ' . $message) : '. Verifica Calendar ID y permisos OAuth.')];
        }
        $json = $response->json();

        return [
            'event_id' => $json['id'] ?? null,
            'meet_link' => $json['hangoutLink']
                ?? ($json['conferenceData']['entryPoints'][0]['uri'] ?? null),
            'error' => null,
        ];
    }

    protected function getGoogleCalendarAccessToken(array $settings): ?string
    {
        $token = $settings['google_calendar_access_token'] ?? null;
        $expiresAt = $settings['google_calendar_expires_at'] ?? null;

        if ($token && $expiresAt && Carbon::parse($expiresAt)->subSeconds(60)->isFuture()) {
            return $token;
        }

        $refreshToken = $settings['google_calendar_refresh_token'] ?? null;
        $clientId = $settings['google_calendar_client_id'] ?? null;
        $clientSecret = $this->decryptSetting($settings['google_calendar_client_secret'] ?? null);
        if (!$refreshToken || !$clientId || !$clientSecret) return $token;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->ok()) return $token;

        $payload = $response->json();
        $settings['google_calendar_access_token'] = $payload['access_token'] ?? $token;
        $settings['google_calendar_expires_at'] = now()->addSeconds((int) ($payload['expires_in'] ?? 0))->toISOString();
        $this->settings->update('settings', $settings);
        return $settings['google_calendar_access_token'] ?? $token;
    }

    protected function decryptSetting(?string $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        if (str_starts_with($value, 'ENC:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (\Throwable) {
                return '';
            }
        }
        return $value;
    }

    protected function buildLeadCalendarPayload(array $lead, array $agendaItem, array $settings): ?array
    {
        $start = Carbon::parse($agendaItem['fecha_hora']);
        $end = (clone $start)->addMinutes(max(5, (int) ($agendaItem['duracion_min'] ?? 30)));
        $timezone = $settings['google_meet_timezone'] ?? 'America/Bogota';
        $summaryPrefix = $agendaItem['tipo'] === 'llamada' ? 'Llamada' : 'Reunion';

        return [
            'summary' => $summaryPrefix . ': ' . ($lead['nombre'] ?? 'Lead'),
            'description' => trim((string) (($agendaItem['descripcion'] ?? '') . "\n\nContacto: " . ($lead['email'] ?? 'Sin email') . " | " . ($lead['telefono'] ?? 'Sin telefono'))),
            'start' => [
                'dateTime' => $start->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $end->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ];
    }

    public function export()
    {
        $rows = $this->store->all();
        $headers = ['ID','Nombre','Email','Telefono','Etapa','Valor','Origen','Notas','Recordatorio','Creado','Actualizado'];
        $filename = 'leads_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function() use ($rows,$headers){
            $out = fopen('php://output','w');
            fputcsv($out,$headers);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['id'] ?? '', $r['nombre'] ?? '', $r['email'] ?? '', $r['telefono'] ?? '',
                    $r['etapa'] ?? '', $r['valor'] ?? '', $r['origen'] ?? '', $r['notas'] ?? '',
                    $r['recordatorio'] ?? '', $r['created_at'] ?? '', $r['updated_at'] ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type'=>'text/csv']);
    }

    public function importForm()
    {
        return view('leads.import');
    }

    public function importStore(Request $request)
    {
        $request->validate(['csv'=>'required|file']);
        $path = $request->file('csv')->getRealPath();
        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (!$data) continue;
                $lead = [
                    'nombre' => $data['Nombre'] ?? ($data['nombre'] ?? ''),
                    'email' => $data['Email'] ?? '',
                    'telefono' => $data['Telefono'] ?? '',
                    'etapa' => $this->normalizeStage($data['Etapa'] ?? 'Posible cliente'),
                    'valor' => is_numeric($data['Valor'] ?? null) ? (float)$data['Valor'] : 0,
                    'origen' => $data['Origen'] ?? '',
                    'notas' => $data['Notas'] ?? '',
                    'recordatorio' => $data['Recordatorio'] ?? '',
                ];
                if (!empty($lead['nombre'])) $this->store->create($lead);
            }
            fclose($handle);
        }
        return redirect()->route('leads.index');
    }
}
