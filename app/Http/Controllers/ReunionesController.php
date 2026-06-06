<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReunionesController extends Controller
{
    protected FileStore $store;
    protected FileStore $clientes;
    protected FileStore $settings;
    protected FileStore $users;
    protected FileStore $leads;

    public function __construct()
    {
        $this->store = new FileStore('reuniones.json');
        $this->clientes = new FileStore('clientes.json');
        $this->settings = new FileStore('settings.json');
        $this->users = new FileStore('users.json');
        $this->leads = new FileStore('leads.json');
    }

    public function index(Request $request)
    {
        $timezone = $this->timezone();
        $focusDate = $request->query('week')
            ? Carbon::parse($request->query('week'), $timezone)
            : now($timezone);
        $viewMode = $request->query('vista') === 'dia' ? 'dia' : 'semana';
        $weekStart = $focusDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $rangeStart = $viewMode === 'dia' ? $focusDate->copy()->startOfDay() : $weekStart->copy();
        $rangeEnd = $viewMode === 'dia' ? $focusDate->copy()->endOfDay() : $weekEnd->copy();

        $clientes = collect($this->clientes->all())
            ->sortBy(fn ($cliente) => Str::lower((string) ($cliente['empresa'] ?? '')))
            ->values()
            ->all();

        $reuniones = collect($this->store->all())
            ->map(fn ($meeting) => $this->normalizeMeeting($meeting, $timezone))
            ->merge($this->leadMeetings($timezone))
            ->filter(fn ($meeting) => !empty($meeting['inicio_at']))
            ->filter(fn ($meeting) => $this->canCurrentUserSeeMeeting($meeting))
            ->sortBy('inicio_at')
            ->values()
            ->all();

        $weekMeetings = collect($reuniones)
            ->filter(function ($meeting) use ($rangeStart, $rangeEnd, $timezone) {
                $start = Carbon::parse($meeting['inicio_at'])->setTimezone($timezone);
                return $start->betweenIncluded($rangeStart, $rangeEnd);
            })
            ->groupBy(fn ($meeting) => Carbon::parse($meeting['inicio_at'])->setTimezone($timezone)->toDateString())
            ->all();

        $upcoming = collect($reuniones)
            ->filter(fn ($meeting) => Carbon::parse($meeting['fin_at'] ?? $meeting['inicio_at'])->setTimezone($timezone)->gte(now($timezone)))
            ->take(5)
            ->values()
            ->all();

        $days = $viewMode === 'dia'
            ? [$focusDate->copy()->startOfDay()]
            : collect(range(0, 6))->map(fn ($offset) => $weekStart->copy()->addDays($offset))->all();

        $monthCursor = $focusDate->copy()->startOfMonth();
        $monthDays = $this->monthDays($monthCursor, $timezone, $reuniones);

        return view('reuniones.index', [
            'clientes' => $clientes,
            'days' => $days,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'focusDate' => $focusDate,
            'viewMode' => $viewMode,
            'weekMeetings' => $weekMeetings,
            'upcoming' => $upcoming,
            'monthCursor' => $monthCursor,
            'monthDays' => $monthDays,
            'settings' => $this->settings->find('settings') ?: [],
            'teamUsers' => $this->teamUsers(),
            'hours' => range(8, 22),
            'timezone' => $timezone,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:160',
            'cliente_id' => 'nullable|string',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'ubicacion' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:1500',
            'invitados' => 'nullable|string|max:2000',
            'responsable_ids' => 'nullable|array',
            'responsable_ids.*' => 'nullable|string|max:160',
            'crear_meet' => 'nullable|boolean',
            'meet_url' => 'nullable|url|max:500',
        ]);

        $timezone = $this->timezone();
        $settings = $this->settings->find('settings') ?: [];
        $cliente = !empty($data['cliente_id']) ? $this->clientes->find($data['cliente_id']) : null;
        $start = Carbon::parse($data['fecha'] . ' ' . $data['hora_inicio'], $timezone);
        $end = Carbon::parse($data['fecha'] . ' ' . $data['hora_fin'], $timezone);
        $manualMeetUrl = trim((string) ($data['meet_url'] ?? ''));
        $invitedEmails = $this->parseEmailList((string) ($data['invitados'] ?? ''));
        $assignees = $this->resolveAssignees((array) ($data['responsable_ids'] ?? []));
        $allowedColors = ['emerald', 'sky', 'violet', 'rose', 'amber', 'cyan', 'slate'];
        $color = in_array(($data['color'] ?? ''), $allowedColors, true) ? $data['color'] : 'emerald';

        $meeting = [
            'titulo' => trim((string) $data['titulo']),
            'cliente_id' => $cliente['id'] ?? null,
            'cliente' => $cliente['empresa'] ?? 'Sin cliente',
            'cliente_email' => $cliente['contacto_email'] ?? $cliente['email'] ?? null,
            'invitados' => $invitedEmails,
            'responsables' => collect($assignees)->pluck('name')->filter()->values()->all(),
            'responsable_ids' => collect($assignees)->pluck('id')->filter()->values()->all(),
            'responsable_emails' => collect($assignees)->pluck('email')->filter()->values()->all(),
            'fecha' => $start->toDateString(),
            'hora_inicio' => $start->format('H:i'),
            'hora_fin' => $end->format('H:i'),
            'ubicacion' => trim((string) ($data['ubicacion'] ?? '')),
            'color' => $color,
            'inicio_at' => $start->toISOString(),
            'fin_at' => $end->toISOString(),
            'notas' => trim((string) ($data['notas'] ?? '')),
            'estado' => 'programada',
            'creado_por' => auth()->user()->name ?? session('user.name') ?? 'Sistema',
            'google_event_id' => null,
            'meet_link' => null,
            'meet_error' => null,
        ];

        $wantsMeet = $request->boolean('crear_meet') || $manualMeetUrl !== '';
        if ($wantsMeet) {
            if ($manualMeetUrl !== '') {
                $meeting['meet_link'] = $manualMeetUrl;
            } elseif (empty($settings['google_meet_enabled'])) {
                $meeting['meet_error'] = 'Google Meet esta desactivado en Ajustes > Integraciones.';
            } elseif (empty($settings['google_calendar_enabled']) || (empty($settings['google_calendar_access_token']) && empty($settings['google_calendar_refresh_token']))) {
                $meeting['meet_error'] = 'Google Calendar no esta conectado por OAuth. Conectalo en Ajustes > Integraciones.';
            } else {
                $calendarData = $this->createGoogleCalendarEventWithMeet(
                    $this->buildCalendarPayload($meeting, $settings),
                    $settings
                );
                $meeting['google_event_id'] = $calendarData['event_id'] ?? null;
                $meeting['meet_link'] = $calendarData['meet_link'] ?? null;
                $meeting['calendar_link'] = $calendarData['calendar_link'] ?? null;
                $meeting['meet_error'] = $calendarData['error'] ?? null;
            }

            if (empty($meeting['meet_link']) && !empty($settings['google_meet_fallback_url'])) {
                $meeting['meet_link'] = $settings['google_meet_fallback_url'];
                $meeting['meet_error'] = trim((string) ($meeting['meet_error'] ?? '') . ' Se uso la URL fallback.');
            }
        }

        $saved = $this->store->create($meeting);
        $mailErrors = $this->sendInvitations($saved, array_merge($invitedEmails, $meeting['responsable_emails'] ?? []), $timezone);
        $successMessage = empty($invitedEmails)
            ? 'Reunion creada.'
            : 'Reunion creada e invitaciones enviadas.';

        return redirect()
            ->route('reuniones.index', ['week' => $start->toDateString()])
            ->with(($saved['meet_error'] || $mailErrors) ? 'warning' : 'success', $saved['meet_error'] ?: ($mailErrors ?: $successMessage));
    }

    public function destroy(string $id)
    {
        $this->store->delete($id);
        return back()->with('success', 'Reunion eliminada.');
    }

    public function update(Request $request, string $id)
    {
        $meeting = $this->store->find($id);
        abort_if(!$meeting, 404);

        $data = $request->validate([
            'titulo' => 'required|string|max:160',
            'cliente_id' => 'nullable|string',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'ubicacion' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'notas' => 'nullable|string|max:1500',
            'invitados' => 'nullable|string|max:2000',
            'responsable_ids' => 'nullable|array',
            'responsable_ids.*' => 'nullable|string|max:160',
            'crear_meet' => 'nullable|boolean',
            'meet_url' => 'nullable|url|max:500',
        ]);

        $timezone = $this->timezone();
        $cliente = !empty($data['cliente_id']) ? $this->clientes->find($data['cliente_id']) : null;
        $start = Carbon::parse($data['fecha'] . ' ' . $data['hora_inicio'], $timezone);
        $end = Carbon::parse($data['fecha'] . ' ' . $data['hora_fin'], $timezone);
        $allowedColors = ['emerald', 'sky', 'violet', 'rose', 'amber', 'cyan', 'slate'];
        $color = in_array(($data['color'] ?? ''), $allowedColors, true) ? $data['color'] : ($meeting['color'] ?? 'emerald');
        $manualMeetUrl = trim((string) ($data['meet_url'] ?? ''));
        $assignees = $this->resolveAssignees((array) ($data['responsable_ids'] ?? []));

        $updated = $this->store->update($id, [
            'titulo' => trim((string) $data['titulo']),
            'cliente_id' => $cliente['id'] ?? null,
            'cliente' => $cliente['empresa'] ?? 'Sin cliente',
            'cliente_email' => $cliente['contacto_email'] ?? $cliente['email'] ?? null,
            'invitados' => $this->parseEmailList((string) ($data['invitados'] ?? '')),
            'responsables' => collect($assignees)->pluck('name')->filter()->values()->all(),
            'responsable_ids' => collect($assignees)->pluck('id')->filter()->values()->all(),
            'responsable_emails' => collect($assignees)->pluck('email')->filter()->values()->all(),
            'fecha' => $start->toDateString(),
            'hora_inicio' => $start->format('H:i'),
            'hora_fin' => $end->format('H:i'),
            'ubicacion' => trim((string) ($data['ubicacion'] ?? '')),
            'color' => $color,
            'inicio_at' => $start->toISOString(),
            'fin_at' => $end->toISOString(),
            'notas' => trim((string) ($data['notas'] ?? '')),
            'meet_link' => $manualMeetUrl !== '' ? $manualMeetUrl : ($meeting['meet_link'] ?? null),
        ]);

        return redirect()
            ->route('reuniones.index', ['week' => $start->toDateString()])
            ->with('success', 'Reunion actualizada.');
    }

    protected function timezone(): string
    {
        $settings = $this->settings->find('settings') ?: [];
        return $settings['google_meet_timezone'] ?? config('app.timezone', 'America/Bogota');
    }

    protected function normalizeMeeting(array $meeting, string $timezone): array
    {
        if (empty($meeting['inicio_at']) && !empty($meeting['fecha']) && !empty($meeting['hora_inicio'])) {
            $meeting['inicio_at'] = Carbon::parse($meeting['fecha'] . ' ' . $meeting['hora_inicio'], $timezone)->toISOString();
        }
        if (empty($meeting['fin_at']) && !empty($meeting['fecha']) && !empty($meeting['hora_fin'])) {
            $meeting['fin_at'] = Carbon::parse($meeting['fecha'] . ' ' . $meeting['hora_fin'], $timezone)->toISOString();
        }
        return $meeting;
    }

    protected function leadMeetings(string $timezone): \Illuminate\Support\Collection
    {
        $teamUsers = collect($this->teamUsers());

        return collect($this->leads->all())
            ->flatMap(function (array $lead) use ($timezone, $teamUsers) {
                $agenda = is_array($lead['agenda'] ?? null) ? $lead['agenda'] : [];
                $leadAssignees = collect($lead['encargados'] ?? [])
                    ->map(fn ($id) => trim((string) $id))
                    ->filter()
                    ->unique()
                    ->values();

                $assignees = $leadAssignees
                    ->map(function (string $id) use ($teamUsers) {
                        return $teamUsers->first(function ($user) use ($id) {
                            $userId = (string) ($user['id'] ?? '');
                            return $userId === $id || $userId === 'team:' . $id || Str::after($userId, 'team:') === $id;
                        });
                    })
                    ->filter()
                    ->values();

                return collect($agenda)
                    ->filter(fn ($item) => ($item['tipo'] ?? '') === 'reunion_meet')
                    ->map(function (array $item) use ($lead, $timezone, $leadAssignees, $assignees) {
                        try {
                            if (empty($item['fecha_hora'])) {
                                return null;
                            }
                            $start = Carbon::parse((string) $item['fecha_hora'])->setTimezone($timezone);
                            $duration = max(5, (int) ($item['duracion_min'] ?? 30));
                            $end = $start->copy()->addMinutes($duration);
                        } catch (\Throwable) {
                            return null;
                        }

                        $leadName = trim((string) ($lead['nombre'] ?? 'Lead'));
                        $title = trim((string) ($item['titulo'] ?? 'Reunion'));
                        $notes = trim((string) ($item['descripcion'] ?? ''));

                        return [
                            'id' => 'lead:' . (string) ($lead['id'] ?? '') . ':' . (string) ($item['id'] ?? Str::ulid()),
                            'source' => 'lead',
                            'titulo' => $title !== '' ? $title : 'Reunion con lead',
                            'cliente_id' => null,
                            'cliente' => 'Lead: ' . ($leadName !== '' ? $leadName : 'Sin nombre'),
                            'cliente_email' => $lead['email'] ?? null,
                            'invitados' => array_values(array_filter([(string) ($lead['email'] ?? '')])),
                            'responsables' => $assignees->pluck('name')->filter()->values()->all(),
                            'responsable_ids' => $leadAssignees
                                ->flatMap(fn ($id) => [$id, 'team:' . $id])
                                ->unique()
                                ->values()
                                ->all(),
                            'responsable_emails' => $assignees->pluck('email')->filter()->values()->all(),
                            'fecha' => $start->toDateString(),
                            'hora_inicio' => $start->format('H:i'),
                            'hora_fin' => $end->format('H:i'),
                            'ubicacion' => !empty($item['meet_link']) ? 'Google Meet' : '',
                            'color' => 'sky',
                            'inicio_at' => $start->toISOString(),
                            'fin_at' => $end->toISOString(),
                            'notas' => $notes !== '' ? $notes : 'Reunion programada desde Leads.',
                            'estado' => $item['estado'] ?? 'programado',
                            'creado_por' => $item['creado_por'] ?? $lead['creado_por'] ?? '',
                            'meet_link' => $item['meet_link'] ?? null,
                            'meet_error' => $item['meet_error'] ?? null,
                        ];
                    })
                    ->filter();
            })
            ->values();
    }

    protected function currentUserIdentity(): array
    {
        $user = auth()->user();
        $dbId = $user?->id ? 'db:' . $user->id : '';
        $sessionId = session('user.id') ? 'team:' . session('user.id') : '';

        return [
            'ids' => array_values(array_filter([
                $dbId,
                $sessionId,
                (string) ($user?->id ?? ''),
                (string) session('user.id', ''),
            ])),
            'email' => Str::lower(trim((string) ($user?->email ?? session('user.email', '')))),
            'name' => Str::lower(trim((string) ($user?->name ?? session('user.name', '')))),
            'role' => Str::lower(trim((string) ($user?->role ?? session('user.role', '')))),
        ];
    }

    protected function canCurrentUserSeeMeeting(array $meeting): bool
    {
        $identity = $this->currentUserIdentity();
        if (in_array($identity['role'], ['admin', 'administrador', 'super_admin', 'superadmin'], true)) {
            return true;
        }

        $responsableIds = collect($meeting['responsable_ids'] ?? [])->map(fn ($id) => (string) $id)->all();
        if ($responsableIds && array_intersect($identity['ids'], $responsableIds)) {
            return true;
        }

        $responsableEmails = collect($meeting['responsable_emails'] ?? [])->map(fn ($email) => Str::lower(trim((string) $email)))->filter()->all();
        if ($identity['email'] !== '' && in_array($identity['email'], $responsableEmails, true)) {
            return true;
        }

        $responsables = collect($meeting['responsables'] ?? [])->map(fn ($name) => Str::lower(trim((string) $name)))->filter()->all();
        if ($identity['name'] !== '' && in_array($identity['name'], $responsables, true)) {
            return true;
        }

        $creator = Str::lower(trim((string) ($meeting['creado_por'] ?? '')));
        return $identity['name'] !== '' && $creator !== '' && $identity['name'] === $creator;
    }

    protected function teamUsers(): array
    {
        $dbUsers = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->where(function ($query) {
                $query->whereNull('role')->orWhere('role', '!=', 'client');
            })
            ->get()
            ->map(fn ($user) => [
                'id' => 'db:' . $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) ($user->role ?? 'admin'),
            ]);

        $teamUsers = collect($this->users->all())
            ->filter(fn ($user) => ($user['active'] ?? true) && (($user['role'] ?? '') !== 'client'))
            ->map(fn ($user) => [
                'id' => 'team:' . (string) ($user['id'] ?? ''),
                'name' => (string) ($user['name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'role' => (string) ($user['role'] ?? 'employee'),
            ]);

        return $dbUsers
            ->merge($teamUsers)
            ->filter(fn ($user) => trim((string) ($user['name'] ?? '')) !== '')
            ->unique(fn ($user) => Str::lower((string) ($user['email'] ?: $user['id'])))
            ->sortBy(fn ($user) => Str::lower((string) $user['name']))
            ->values()
            ->all();
    }

    protected function resolveAssignees(array $ids): array
    {
        $wanted = collect($ids)->map(fn ($id) => trim((string) $id))->filter()->unique()->values()->all();
        if (empty($wanted)) {
            return [];
        }

        return collect($this->teamUsers())
            ->filter(fn ($user) => in_array((string) ($user['id'] ?? ''), $wanted, true))
            ->map(fn ($user) => [
                'id' => (string) ($user['id'] ?? ''),
                'name' => (string) ($user['name'] ?? ''),
                'email' => Str::lower(trim((string) ($user['email'] ?? ''))),
            ])
            ->values()
            ->all();
    }

    protected function monthDays(Carbon $monthCursor, string $timezone, array $reuniones): array
    {
        $meetingDates = collect($reuniones)
            ->map(fn ($meeting) => !empty($meeting['inicio_at']) ? Carbon::parse($meeting['inicio_at'])->setTimezone($timezone)->toDateString() : null)
            ->filter()
            ->flip()
            ->all();
        $gridStart = $monthCursor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        return collect(range(0, 41))->map(function ($offset) use ($gridStart, $monthCursor, $meetingDates) {
            $date = $gridStart->copy()->addDays($offset);
            return [
                'date' => $date,
                'in_month' => $date->month === $monthCursor->month,
                'has_meeting' => array_key_exists($date->toDateString(), $meetingDates),
            ];
        })->all();
    }

    protected function createGoogleCalendarEventWithMeet(array $payload, array $settings): array
    {
        $token = $this->getGoogleCalendarAccessToken($settings);
        if (!$token) {
            return ['error' => 'No hay token valido de Google Calendar. Vuelve a conectar OAuth.'];
        }

        $calendarId = $settings['google_calendar_id'] ?? 'primary';
        $calendarPath = rawurlencode($calendarId);
        $response = Http::withToken($token)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarPath}/events?conferenceDataVersion=1&sendUpdates=all", $payload);

        if (!$response->ok()) {
            $status = $response->status();
            $message = (string) ($response->json('error.message') ?? '');
            return ['error' => 'Google Calendar respondio ' . $status . ($message ? (': ' . $message) : '. Verifica Calendar ID y permisos OAuth.')];
        }

        $json = $response->json();
        return [
            'event_id' => $json['id'] ?? null,
            'meet_link' => $json['hangoutLink'] ?? ($json['conferenceData']['entryPoints'][0]['uri'] ?? null),
            'calendar_link' => $json['htmlLink'] ?? null,
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

    protected function parseEmailList(string $raw): array
    {
        return collect(preg_split('/[,;\s]+/', $raw) ?: [])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    protected function sendInvitations(array $meeting, array $emails, string $timezone): ?string
    {
        $ownerEmail = strtolower(trim((string) (auth()->user()->email ?? session('user.email') ?? '')));
        if (filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $ownerEmail;
        }
        $emails = collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            return null;
        }

        $start = Carbon::parse($meeting['inicio_at'])->setTimezone($timezone);
        $end = Carbon::parse($meeting['fin_at'])->setTimezone($timezone);
        $meetLink = trim((string) ($meeting['meet_link'] ?? ''));
        $calendarLink = trim((string) ($meeting['calendar_link'] ?? ''));
        if ($calendarLink === '') {
            $calendarLink = $this->buildGoogleCalendarTemplateUrl($meeting, $timezone);
        }
        $meetButton = $meetLink !== ''
            ? '<p><a href="' . e($meetLink) . '" target="_blank" rel="noopener" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#84cc16;color:#0f172a;font-weight:800;text-decoration:none;">Unirme a Google Meet</a></p>'
            : '';
        $calendarButton = $calendarLink !== ''
            ? '<p><a href="' . e($calendarLink) . '" target="_blank" rel="noopener" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#0f172a;color:#ffffff;font-weight:800;text-decoration:none;">Añadir a calendario</a></p>'
            : '';
        $settings = TemplateMail::settings();
        $appName = trim((string) ($settings['company_name'] ?? $settings['app_name'] ?? config('app.name', 'Infocus CRM'))) ?: 'Infocus CRM';
        $duration = max(1, $start->diffInMinutes($end, false));
        [$subject, $body] = TemplateMail::render(
            $settings,
            'template_meeting_scheduled_subject',
            'template_meeting_scheduled_body',
            'Invitacion: {reunion_titulo}',
            "<p>Hola,</p><p>Has sido invitado(a) a una reunion desde {empresa}.</p><p><strong>Titulo:</strong> {reunion_titulo}<br><strong>Cliente:</strong> {cliente}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}</p><p>{descripcion}</p><p>{meet_button}</p><p>{calendar_button}</p>",
            [
                'destinatario_nombre' => 'invitado/a',
                'empresa' => $appName,
                'reunion_titulo' => (string) ($meeting['titulo'] ?? 'Reunion'),
                'cliente' => (string) ($meeting['cliente'] ?? 'Sin cliente'),
                'fecha_inicio' => $start->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y · h:i A'),
                'fecha_fin' => $end->locale('es')->translatedFormat('h:i A'),
                'duracion_min' => (string) $duration,
                'ubicacion' => (string) (($meeting['ubicacion'] ?? '') !== '' ? $meeting['ubicacion'] : ($meetLink !== '' ? 'Google Meet' : 'Por definir')),
                'descripcion' => trim((string) ($meeting['notas'] ?? '')) ?: 'Sin notas.',
                'meet_url' => $meetLink,
                'meet_button' => $meetButton,
                'calendar_url' => $calendarLink,
                'calendar_button' => $calendarButton,
            ]
        );

        try {
            foreach ($emails as $email) {
                TemplateMail::send($email, $subject, $body, ['source' => 'meeting_scheduled']);
            }
        } catch (\Throwable $e) {
            return 'Reunion creada, pero no se pudieron enviar todas las invitaciones por correo.';
        }

        return null;
    }

    protected function buildGoogleCalendarTemplateUrl(array $meeting, string $timezone): string
    {
        $start = Carbon::parse($meeting['inicio_at'])->setTimezone($timezone)->utc()->format('Ymd\THis\Z');
        $end = Carbon::parse($meeting['fin_at'])->setTimezone($timezone)->utc()->format('Ymd\THis\Z');
        $details = trim((string) ($meeting['notas'] ?? ''));
        if (!empty($meeting['meet_link'])) {
            $details = trim($details . "\n\nMeet: " . $meeting['meet_link']);
        }

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => $meeting['titulo'] ?? 'Reunion',
            'dates' => "{$start}/{$end}",
            'details' => $details,
            'location' => ($meeting['ubicacion'] ?? '') !== '' ? $meeting['ubicacion'] : (!empty($meeting['meet_link']) ? 'Google Meet' : ''),
        ]);
    }

    protected function buildCalendarPayload(array $meeting, array $settings): array
    {
        $timezone = $settings['google_meet_timezone'] ?? $this->timezone();
        $description = trim((string) ($meeting['notas'] ?? ''));
        $clientLine = 'Cliente: ' . ($meeting['cliente'] ?? 'Sin cliente');
        $description = trim($description . "\n\n" . $clientLine);

        $payload = [
            'summary' => $meeting['titulo'] ?? 'Reunion',
            'description' => $description,
            'start' => [
                'dateTime' => Carbon::parse($meeting['inicio_at'])->setTimezone($timezone)->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => Carbon::parse($meeting['fin_at'])->setTimezone($timezone)->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'location' => (string) (($meeting['ubicacion'] ?? '') !== '' ? $meeting['ubicacion'] : (!empty($meeting['meet_link']) ? 'Google Meet' : '')),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ];

        $attendees = collect($meeting['invitados'] ?? [])
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => ['email' => $email])
            ->values()
            ->all();

        if (!empty($attendees)) {
            $payload['attendees'] = $attendees;
        }

        return $payload;
    }
}
