<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SendWeeklyHoursSummary extends Command
{
    protected $signature = 'mail:weekly-hours-summary {--date=}';

    protected $description = 'Envia resumen semanal de horas por usuario con desglose por proyecto';

    public function handle(): int
    {
        $settings = TemplateMail::settings();
        if (!($settings['team_notify_weekly_hours'] ?? true)) {
            $this->info('Resumen semanal desactivado en ajustes de equipo.');
            return self::SUCCESS;
        }

        $users = collect((new FileStore('users.json'))->all())
            ->filter(fn ($u) => ($u['active'] ?? true) && filter_var($u['email'] ?? '', FILTER_VALIDATE_EMAIL))
            ->values();
        $projects = collect((new FileStore('proyectos.json'))->all());

        $baseDate = $this->option('date') ? Carbon::parse((string) $this->option('date')) : now();
        $weekEnd = $baseDate->copy()->startOfWeek()->subDay();
        $weekStart = $weekEnd->copy()->startOfWeek();

        $sent = 0;

        foreach ($users as $user) {
            $summary = $this->buildSummaryForUser($projects, (string) ($user['name'] ?? ''), $weekStart, $weekEnd);

            $vars = [
                'usuario_nombre' => $user['name'] ?? 'Usuario',
                'semana_inicio' => $weekStart->format('d/m/Y'),
                'semana_fin' => $weekEnd->format('d/m/Y'),
                'semana_rango' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m/Y'),
                'total_horas_semana' => number_format($summary['total_hours'], 2),
                'total_horas_facturables' => number_format($summary['total_hours'], 2),
                'total_horas_no_facturables' => '0.00',
                'proyectos_desglose_html' => $summary['table_html'],
                'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
            ];

            [$subject, $body] = TemplateMail::render(
                $settings,
                'template_weekly_hours_user_subject',
                'template_weekly_hours_user_body',
                'Resumen semanal de horas - {semana_rango}',
                "Hola {usuario_nombre},\n\nTotal de horas esta semana: {total_horas_semana}\n\n{proyectos_desglose_html}",
                $vars,
                [
                    ['label' => 'Entrar al dashboard', 'url' => route('dashboard'), 'kind' => 'primary'],
                    ['label' => 'Ver proyectos', 'url' => route('proyectos.index'), 'kind' => 'secondary'],
                ]
            );

            try {
                TemplateMail::send((string) $user['email'], $subject, $body);
                $sent++;
            } catch (\Throwable $e) {
                $this->warn('No se pudo enviar a ' . ($user['email'] ?? 'sin-email') . ': ' . $e->getMessage());
            }
        }

        $this->info('Resumen semanal enviado a ' . $sent . ' usuarios.');

        return self::SUCCESS;
    }

    private function buildSummaryForUser($projects, string $userName, Carbon $start, Carbon $end): array
    {
        $byProject = [];
        $needle = Str::lower(trim($userName));

        foreach ($projects as $project) {
            $seconds = 0;
            foreach (($project['time_logs'] ?? []) as $log) {
                $actor = Str::lower(trim((string) ($log['user'] ?? '')));
                if ($actor === '' || $actor !== $needle) {
                    continue;
                }

                $startTs = (int) ($log['start'] ?? 0);
                $endTs = (int) ($log['end'] ?? 0);
                if ($startTs <= 0 || $endTs <= 0 || $endTs < $startTs) {
                    continue;
                }

                $dt = Carbon::createFromTimestamp($startTs);
                if ($dt->lt($start) || $dt->gt($end->copy()->endOfDay())) {
                    continue;
                }

                $seconds += ($endTs - $startTs);
            }

            if ($seconds > 0) {
                $byProject[] = [
                    'project' => (string) ($project['titulo'] ?? 'Proyecto'),
                    'hours' => round($seconds / 3600, 2),
                ];
            }
        }

        usort($byProject, fn ($a, $b) => ($b['hours'] <=> $a['hours']));

        $rows = '';
        $total = 0.0;
        foreach ($byProject as $item) {
            $total += (float) $item['hours'];
            $rows .= '<tr>'
                . '<td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#0f172a;">' . e($item['project']) . '</td>'
                . '<td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#0f172a;text-align:right;font-weight:700;">' . number_format((float) $item['hours'], 2) . ' h</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="2" style="padding:10px;color:#64748b;">No registraste horas esta semana.</td></tr>';
        }

        $table = '<table style="width:100%;border-collapse:collapse;margin:10px 0 0;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:12px;">Proyecto</th>'
            . '<th style="text-align:right;padding:8px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:12px;">Horas</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';

        return [
            'total_hours' => round($total, 2),
            'table_html' => $table,
        ];
    }
}
