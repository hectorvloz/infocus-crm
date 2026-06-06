<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SendMonthlyHoursSummary extends Command
{
    protected $signature = 'mail:monthly-hours-summary {--date=}';

    protected $description = 'Envia resumen mensual de horas por usuario con desglose por proyecto';

    public function handle(): int
    {
        $settings = TemplateMail::settings();
        if (!($settings['team_notify_monthly_hours'] ?? true)) {
            $this->info('Resumen mensual desactivado en ajustes de equipo.');
            return self::SUCCESS;
        }

        $users = collect((new FileStore('users.json'))->all())
            ->filter(fn ($u) => ($u['active'] ?? true) && filter_var($u['email'] ?? '', FILTER_VALIDATE_EMAIL))
            ->values();
        $projects = collect((new FileStore('proyectos.json'))->all());

        $baseDate = $this->option('date') ? Carbon::parse((string) $this->option('date')) : now();
        $monthStart = $baseDate->copy()->subMonthNoOverflow()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $sent = 0;

        foreach ($users as $user) {
            $summary = $this->buildSummaryForUser($projects, (string) ($user['name'] ?? ''), $monthStart, $monthEnd);

            $vars = [
                'usuario_nombre' => $user['name'] ?? 'Usuario',
                'mes_nombre' => $monthStart->translatedFormat('F Y'),
                'total_horas_mes' => number_format($summary['total_hours'], 2),
                'total_proyectos' => (string) $summary['projects_count'],
                'proyectos_desglose_html' => $summary['table_html'],
                'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
            ];

            [$subject, $body] = TemplateMail::render(
                $settings,
                'template_monthly_hours_user_subject',
                'template_monthly_hours_user_body',
                'Resumen mensual de horas - {mes_nombre}',
                "Hola {usuario_nombre},\n\nTotal de horas del mes: {total_horas_mes}\n\n{proyectos_desglose_html}",
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

        $this->info('Resumen mensual enviado a ' . $sent . ' usuarios.');

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
            $rows = '<tr><td colspan="2" style="padding:10px;color:#64748b;">No registraste horas este mes.</td></tr>';
        }

        $table = '<table style="width:100%;border-collapse:collapse;margin:10px 0 0;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:8px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:12px;">Proyecto</th>'
            . '<th style="text-align:right;padding:8px;border-bottom:1px solid #cbd5e1;color:#64748b;font-size:12px;">Horas</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';

        return [
            'total_hours' => round($total, 2),
            'projects_count' => count($byProject),
            'table_html' => $table,
        ];
    }
}
