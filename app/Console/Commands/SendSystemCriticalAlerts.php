<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Illuminate\Console\Command;

class SendSystemCriticalAlerts extends Command
{
    protected $signature = 'system:send-critical-alerts';

    protected $description = 'Envia alertas criticas de sistema (SMTP/pasarelas) a administradores';

    public function handle(): int
    {
        $settings = TemplateMail::settings();
        if (!($settings['team_notify_system_alerts'] ?? true)) {
            $this->info('Alertas criticas desactivadas en ajustes de equipo.');
            return self::SUCCESS;
        }

        $issues = $this->collectIssues($settings);

        if (empty($issues)) {
            $this->info('Sin alertas criticas.');
            return self::SUCCESS;
        }

        $admins = collect((new FileStore('users.json'))->all())
            ->filter(fn ($u) => ($u['active'] ?? true) && in_array(strtolower((string) ($u['role'] ?? '')), ['admin', 'super_admin'], true))
            ->pluck('email')
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if (empty($admins)) {
            $fallback = $settings['mail_from_address'] ?? $settings['email_from'] ?? null;
            if ($fallback && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
                $admins = [$fallback];
            }
        }

        if (empty($admins)) {
            $this->warn('No hay destinatarios administradores para alertas.');
            return self::SUCCESS;
        }

        $listHtml = '<ul style="margin:0;padding-left:18px;">';
        foreach ($issues as $i) {
            $listHtml .= '<li style="margin:6px 0;"><strong>' . e($i['title']) . ':</strong> ' . e($i['detail']) . '</li>';
        }
        $listHtml .= '</ul>';

        $vars = [
            'nombre_admin' => 'Administrador',
            'tipo_alerta' => 'Configuracion critica',
            'servicio' => 'SMTP / Pasarelas de pago',
            'detalle_error' => $listHtml,
            'fecha_hora' => now()->format('d/m/Y H:i'),
            'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
        ];

        [$subject, $body] = TemplateMail::render(
            $settings,
            'template_system_critical_alert_subject',
            'template_system_critical_alert_body',
            'Alerta critica: {tipo_alerta}',
            "Hola {nombre_admin},\n\nSe detectaron alertas criticas:\n{detalle_error}",
            $vars,
            [
                ['label' => 'Ver SMTP', 'url' => route('settings.smtp'), 'kind' => 'secondary'],
                ['label' => 'Ver integraciones', 'url' => route('settings.integrations'), 'kind' => 'primary'],
            ]
        );

        try {
            TemplateMail::send($admins, $subject, $body);
            $this->info('Alerta critica enviada a ' . count($admins) . ' administradores.');
        } catch (\Throwable $e) {
            $this->error('Error enviando alerta critica: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }

    private function collectIssues(array $settings): array
    {
        $issues = [];

        if (empty($settings['smtp_host']) || empty($settings['smtp_username']) || empty($settings['smtp_password']) || empty($settings['mail_from_address'])) {
            $issues[] = [
                'title' => 'SMTP incompleto',
                'detail' => 'Faltan campos obligatorios para envio de correos.',
            ];
        }

        $gateway = strtolower((string) ($settings['payment_gateway'] ?? ''));
        if ($gateway === 'wompi') {
            if (empty($settings['wompi_public_key']) || empty($settings['wompi_integrity_secret'])) {
                $issues[] = [
                    'title' => 'Wompi incompleto',
                    'detail' => 'Falta llave publica o secreto de integridad.',
                ];
            }
        }

        if ($gateway === 'stripe') {
            if (empty($settings['stripe_key']) || empty($settings['stripe_secret'])) {
                $issues[] = [
                    'title' => 'Stripe incompleto',
                    'detail' => 'Falta key o secret de Stripe.',
                ];
            }
        }

        if ($gateway === 'paypal') {
            if (empty($settings['paypal_client_id']) || empty($settings['paypal_secret'])) {
                $issues[] = [
                    'title' => 'PayPal incompleto',
                    'detail' => 'Falta client_id o secret de PayPal.',
                ];
            }
        }

        return $issues;
    }
}
