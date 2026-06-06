<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendMeetingReminders extends Command
{
    protected $signature = 'reuniones:send-reminders {--minutes=30}';

    protected $description = 'Envia recordatorios de reuniones y crea notificaciones internas antes del inicio';

    public function handle(): int
    {
        $minutesBefore = max(1, (int) $this->option('minutes'));
        $settingsStore = new FileStore('settings.json');
        $meetingsStore = new FileStore('reuniones.json');
        $remindersStore = new FileStore('meeting_reminders.json');
        $settings = $settingsStore->find('settings') ?: [];
        $timezone = (string) ($settings['google_meet_timezone'] ?? config('app.timezone', 'America/Bogota'));
        $now = Carbon::now($timezone);
        $windowStart = $now->copy();
        $windowEnd = $now->copy()->addMinutes($minutesBefore);
        $appName = trim((string) ($settings['app_name'] ?? config('app.name', 'Infocus CRM'))) ?: 'Infocus CRM';
        $sent = 0;
        $notified = 0;

        foreach ($meetingsStore->all() as $meeting) {
            $meetingId = (string) ($meeting['id'] ?? '');
            if ($meetingId === '' || !empty($meeting['reminder_30m_sent_at'])) {
                continue;
            }

            try {
                $startAt = !empty($meeting['inicio_at'])
                    ? Carbon::parse((string) $meeting['inicio_at'])->setTimezone($timezone)
                    : Carbon::parse((string) ($meeting['fecha'] ?? '') . ' ' . (string) ($meeting['hora_inicio'] ?? ''), $timezone);
            } catch (\Throwable) {
                continue;
            }

            if ($startAt->lte($windowStart) || $startAt->gt($windowEnd)) {
                continue;
            }

            $endAt = $this->resolveEndAt($meeting, $startAt, $timezone);
            $emails = $this->recipients($meeting);
            [$subject, $body] = $this->emailContent($meeting, $startAt, $endAt, $minutesBefore, $appName, $settings);
            $meetingSent = 0;

            foreach ($emails as $email) {
                try {
                    TemplateMail::send($email, $subject, $body, ['source' => 'meeting_reminder']);
                    $meetingSent++;
                    $sent++;
                } catch (\Throwable $e) {
                    $this->warn('No se pudo enviar recordatorio a ' . $email . ': ' . $e->getMessage());
                }
            }

            $reminderId = 'meeting_reminder:' . $meetingId . ':30m:' . $startAt->timestamp;
            if (!$this->reminderExists($remindersStore, $reminderId)) {
                $remindersStore->create([
                    'id' => $reminderId,
                    'meeting_id' => $meetingId,
                    'kind' => 'meeting_reminder',
                    'title' => 'Reunión en ' . $minutesBefore . ' minutos',
                    'message' => '"' . (string) ($meeting['titulo'] ?? 'Reunión') . '" inicia a las ' . $startAt->format('H:i') . '.',
                    'date' => $now->toDateTimeString(),
                    'meeting_start_at' => $startAt->toIso8601String(),
                    'url' => route('reuniones.index', ['week' => $startAt->toDateString(), 'vista' => 'dia']),
                    'target_ids' => collect($meeting['responsable_ids'] ?? [])->map(fn ($id) => (string) $id)->filter()->values()->all(),
                    'target_emails' => collect($meeting['responsable_emails'] ?? [])->map(fn ($email) => Str::lower(trim((string) $email)))->filter()->values()->all(),
                    'target_names' => collect($meeting['responsables'] ?? [])->map(fn ($name) => Str::lower(trim((string) $name)))->filter()->values()->all(),
                ]);
                $notified++;
            }

            $meetingsStore->update($meetingId, [
                'reminder_30m_sent_at' => $now->toIso8601String(),
                'reminder_30m_email_count' => $meetingSent,
            ]);
        }

        $this->info('Recordatorios de reuniones enviados: ' . $sent . '. Notificaciones creadas: ' . $notified . '.');
        return self::SUCCESS;
    }

    protected function recipients(array $meeting): array
    {
        $emails = array_merge(
            (array) ($meeting['responsable_emails'] ?? []),
            (array) ($meeting['invitados'] ?? [])
        );

        $clientEmail = trim((string) ($meeting['cliente_email'] ?? ''));
        if ($clientEmail !== '') {
            $emails[] = $clientEmail;
        }

        return collect($emails)
            ->map(fn ($email) => Str::lower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveEndAt(array $meeting, Carbon $startAt, string $timezone): Carbon
    {
        try {
            if (!empty($meeting['fin_at'])) {
                return Carbon::parse((string) $meeting['fin_at'])->setTimezone($timezone);
            }
            if (!empty($meeting['fecha']) && !empty($meeting['hora_fin'])) {
                return Carbon::parse((string) $meeting['fecha'] . ' ' . (string) $meeting['hora_fin'], $timezone);
            }
        } catch (\Throwable) {
            //
        }

        return $startAt->copy()->addMinutes(30);
    }

    protected function emailContent(array $meeting, Carbon $startAt, Carbon $endAt, int $minutesBefore, string $appName, array $settings): array
    {
        $notes = trim((string) ($meeting['notas'] ?? ''));
        $meetLink = trim((string) ($meeting['meet_link'] ?? ''));
        $location = trim((string) ($meeting['ubicacion'] ?? ''));
        $meetButton = $meetLink !== ''
            ? '<p><a href="' . e($meetLink) . '" target="_blank" rel="noopener" style="display:inline-block;padding:12px 22px;border-radius:999px;background:#d9f99d;color:#0f172a;font-weight:800;text-decoration:none;">Entrar a la reunión</a></p>'
            : '';
        $duration = max(1, $startAt->diffInMinutes($endAt, false));

        return TemplateMail::render(
            $settings,
            'template_meeting_reminder_subject',
            'template_meeting_reminder_body',
            'Recordatorio: {reunion_titulo} en {minutos_antes} min',
            "<p>Hola,</p><p>Este es un recordatorio de tu reunion en <strong>{minutos_antes} minutos</strong>.</p><p><strong>Titulo:</strong> {reunion_titulo}<br><strong>Cliente:</strong> {cliente}<br><strong>Inicio:</strong> {fecha_inicio}<br><strong>Fin:</strong> {fecha_fin}</p><p>{descripcion}</p><p>{meet_button}</p>",
            [
                'destinatario_nombre' => 'invitado/a',
                'empresa' => $appName,
                'reunion_titulo' => (string) ($meeting['titulo'] ?? 'Reunion'),
                'cliente' => (string) ($meeting['cliente'] ?? 'Sin cliente'),
                'fecha_inicio' => $startAt->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y · h:i A'),
                'fecha_fin' => $endAt->locale('es')->translatedFormat('h:i A'),
                'duracion_min' => (string) $duration,
                'minutos_antes' => (string) $minutesBefore,
                'ubicacion' => $location !== '' ? $location : ($meetLink !== '' ? 'Google Meet' : 'Por definir'),
                'descripcion' => $notes !== '' ? $notes : 'Sin notas.',
                'meet_url' => $meetLink,
                'meet_button' => $meetButton,
            ]
        );
    }

    protected function reminderExists(FileStore $store, string $id): bool
    {
        return collect($store->all())->contains(fn ($item) => (string) ($item['id'] ?? '') === $id);
    }
}
