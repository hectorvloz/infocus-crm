<?php

namespace App\Console\Commands;

use App\Mail\GenericMail;
use App\Repositories\FileStore;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLeadAgendaReminders extends Command
{
    protected $signature = 'leads:send-agenda-reminders {--minutes=30}';

    protected $description = 'Envia recordatorios de agenda de leads antes de la actividad';

    public function handle(): int
    {
        $minutesBefore = max(1, (int) $this->option('minutes'));
        $leadsStore = new FileStore('leads.json');
        $usersStore = new FileStore('users.json');
        $settingsStore = new FileStore('settings.json');
        $settings = $settingsStore->find('settings') ?: [];
        $timezone = (string) ($settings['timezone'] ?? $settings['app_timezone'] ?? config('app.timezone', 'America/Bogota'));
        $now = Carbon::now($timezone);
        $windowStart = $now->copy();
        $windowEnd = $now->copy()->addMinutes($minutesBefore);
        $appName = trim((string) ($settings['app_name'] ?? config('app.name', 'Infocus CRM')));
        if ($appName === '') {
            $appName = 'Infocus CRM';
        }

        $users = collect($usersStore->all());
        $sent = 0;

        foreach ($leadsStore->all() as $lead) {
            $leadId = (string) ($lead['id'] ?? '');
            if ($leadId === '') {
                continue;
            }

            $agenda = is_array($lead['agenda'] ?? null) ? $lead['agenda'] : [];
            $agendaChanged = false;

            foreach ($agenda as $i => $item) {
                if (($item['estado'] ?? 'programado') !== 'programado') {
                    continue;
                }

                try {
                    $eventAt = Carbon::parse((string) ($item['fecha_hora'] ?? ''), $timezone)->setTimezone($timezone);
                } catch (\Throwable $e) {
                    continue;
                }

                if ($eventAt->lte($windowStart) || $eventAt->gt($windowEnd)) {
                    continue;
                }

                $leadAlreadySent = !empty($item['reminder_30m_lead_sent_at']);
                $ownerAlreadySent = !empty($item['reminder_30m_owner_sent_at']);

                $subject = 'Recordatorio: ' . ($item['titulo'] ?? 'Actividad') . ' en ' . $minutesBefore . ' minutos';
                $isMeet = (($item['tipo'] ?? '') === 'reunion_meet');
                $meetUrl = (string) ($item['meet_link'] ?? '');
                $startText = $eventAt->locale('es')->translatedFormat('d \\d\\e F \\d\\e Y · h:i A');
                $endText = (clone $eventAt)->addMinutes((int) ($item['duracion_min'] ?? 30))->locale('es')->translatedFormat('h:i A');
                $meetButton = ($isMeet && $meetUrl !== '')
                    ? '<a href="' . e($meetUrl) . '" target="_blank" rel="noopener" style="display:inline-block;padding:14px 24px;border-radius:9999px;background:#10b981;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;">Entrar a Google Meet</a>'
                    : '';

                $leadEmail = trim((string) ($lead['email'] ?? ''));
                if (!$leadAlreadySent && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $vars = [
                            '{destinatario_nombre}' => (string) ($lead['nombre'] ?? 'Cliente'),
                            '{lead_nombre}' => (string) ($lead['nombre'] ?? 'Cliente'),
                            '{empresa}' => $appName,
                            '{actividad_titulo}' => (string) ($item['titulo'] ?? 'Actividad programada'),
                            '{fecha_inicio}' => $startText,
                            '{fecha_fin}' => $endText,
                            '{duracion_min}' => (string) ((int) ($item['duracion_min'] ?? 30)),
                            '{minutos_antes}' => (string) $minutesBefore,
                            '{descripcion}' => trim((string) ($item['descripcion'] ?? '')) ?: 'Sin descripción.',
                            '{meet_url}' => $meetUrl,
                            '{meet_button}' => $meetButton,
                        ];
                        $subjectTpl = (string) ($settings['template_lead_meet_reminder_subject'] ?? 'Recordatorio: {actividad_titulo} en {minutos_antes} min');
                        $bodyTpl = (string) ($settings['template_lead_meet_reminder_body'] ?? '<p>Hola <strong>{destinatario_nombre}</strong>,</p><p>Este es un recordatorio de tu reunión en {minutos_antes} minutos.</p><p><strong>Título:</strong> {actividad_titulo}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>');
                        Mail::to($leadEmail)->send(new GenericMail(strtr($subjectTpl, $vars), strtr($bodyTpl, $vars)));
                        $agenda[$i]['reminder_30m_lead_sent_at'] = now()->toISOString();
                        $agendaChanged = true;
                        $sent++;
                    } catch (\Throwable $e) {
                        $this->warn('Error recordatorio lead [' . $leadId . ']: ' . $e->getMessage());
                    }
                }

                $ownerEmail = $this->resolveOwnerEmail($item, $users);
                if (!$ownerAlreadySent && filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $ownerName = $this->resolveOwnerName($item, $users);
                        $vars = [
                            '{destinatario_nombre}' => $ownerName,
                            '{lead_nombre}' => (string) ($lead['nombre'] ?? 'Lead'),
                            '{empresa}' => $appName,
                            '{actividad_titulo}' => (string) ($item['titulo'] ?? 'Actividad programada'),
                            '{fecha_inicio}' => $startText,
                            '{fecha_fin}' => $endText,
                            '{duracion_min}' => (string) ((int) ($item['duracion_min'] ?? 30)),
                            '{minutos_antes}' => (string) $minutesBefore,
                            '{descripcion}' => trim((string) ($item['descripcion'] ?? '')) ?: 'Sin descripción.',
                            '{meet_url}' => $meetUrl,
                            '{meet_button}' => $meetButton,
                        ];
                        $subjectTpl = (string) ($settings['template_lead_meet_reminder_subject'] ?? 'Recordatorio: {actividad_titulo} en {minutos_antes} min');
                        $bodyTpl = (string) ($settings['template_lead_meet_reminder_body'] ?? '<p>Hola <strong>{destinatario_nombre}</strong>,</p><p>Este es un recordatorio de tu reunión en {minutos_antes} minutos.</p><p><strong>Título:</strong> {actividad_titulo}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>');
                        Mail::to($ownerEmail)->send(new GenericMail(strtr($subjectTpl, $vars), strtr($bodyTpl, $vars)));
                        $agenda[$i]['reminder_30m_owner_sent_at'] = now()->toISOString();
                        $agendaChanged = true;
                        $sent++;
                    } catch (\Throwable $e) {
                        $this->warn('Error recordatorio usuario [' . $leadId . ']: ' . $e->getMessage());
                    }
                }
            }

            if ($agendaChanged) {
                $leadsStore->update($leadId, ['agenda' => $agenda]);
            }
        }

        $this->info('Recordatorios de agenda enviados: ' . $sent);
        return self::SUCCESS;
    }

    protected function resolveOwnerEmail(array $agendaItem, $users): string
    {
        $createdBy = trim((string) ($agendaItem['creado_por'] ?? ''));
        if ($createdBy === '') {
            return '';
        }

        $matchByEmail = $users->first(function ($u) use ($createdBy) {
            return strtolower(trim((string) ($u['email'] ?? ''))) === strtolower($createdBy);
        });
        if ($matchByEmail && !empty($matchByEmail['email'])) {
            return trim((string) $matchByEmail['email']);
        }

        $matchByName = $users->first(function ($u) use ($createdBy) {
            return strtolower(trim((string) ($u['name'] ?? ''))) === strtolower($createdBy);
        });
        if ($matchByName && !empty($matchByName['email'])) {
            return trim((string) $matchByName['email']);
        }

        return '';
    }

    protected function resolveOwnerName(array $agendaItem, $users): string
    {
        $createdBy = trim((string) ($agendaItem['creado_por'] ?? 'Usuario'));
        if ($createdBy === '') {
            return 'Usuario';
        }

        $matchByEmail = $users->first(function ($u) use ($createdBy) {
            return strtolower(trim((string) ($u['email'] ?? ''))) === strtolower($createdBy);
        });
        if ($matchByEmail && !empty($matchByEmail['name'])) {
            return trim((string) $matchByEmail['name']);
        }

        return $createdBy;
    }
}
