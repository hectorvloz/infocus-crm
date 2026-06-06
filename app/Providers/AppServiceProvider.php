<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Mail\Events\MessageSent;
use App\Console\Commands\BuildForProduction;
use App\Console\Commands\ProcessRecurringInvoices;
use App\Console\Commands\SendWeeklyHoursSummary;
use App\Console\Commands\SendMonthlyHoursSummary;
use App\Console\Commands\SendInvoiceDueReminders;
use App\Console\Commands\SendScheduledIssueInvoices;
use App\Console\Commands\SendSystemCriticalAlerts;
use App\Repositories\FileStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildForProduction::class,
                ProcessRecurringInvoices::class,
                SendWeeklyHoursSummary::class,
                SendMonthlyHoursSummary::class,
                SendInvoiceDueReminders::class,
                SendScheduledIssueInvoices::class,
                SendSystemCriticalAlerts::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureRuntimeDirectories();

        // Dynamic Mail Configuration from settings.json
        try {
            $store = new FileStore('settings.json');
            $settings = $store->find('settings');

            $timezone = $settings['company_timezone'] ?? 'America/Bogota';
            if (is_string($timezone) && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone);
            }

            if ($settings && !empty($settings['smtp_host'])) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', $settings['smtp_host']);
                Config::set('mail.mailers.smtp.port', $settings['smtp_port'] ?? 587);
                Config::set('mail.mailers.smtp.username', $settings['smtp_username'] ?? '');
                $smtpPassword = (string) ($settings['smtp_password'] ?? '');
                if ($smtpPassword !== '' && str_starts_with($smtpPassword, 'ENC:')) {
                    try {
                        $smtpPassword = Crypt::decryptString(substr($smtpPassword, 4));
                    } catch (\Throwable) {
                        $smtpPassword = '';
                    }
                }
                Config::set('mail.mailers.smtp.password', $smtpPassword);
                Config::set('mail.mailers.smtp.encryption', $settings['smtp_encryption'] === 'none' ? null : ($settings['smtp_encryption'] ?? 'tls'));
                Config::set('mail.from.address', $settings['mail_from_address'] ?? 'noreply@infocus.app');
                Config::set('mail.from.name', $settings['mail_from_name'] ?? 'Infocus CRM');
            }
        } catch (\Exception $e) {
            // Fallback to default config if file error
        }

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            try {
                $message = $event->message;
                $to = [];
                foreach ((array) $message->getTo() as $addr) {
                    $email = trim((string) ($addr?->getAddress() ?? ''));
                    if ($email !== '') {
                        $to[] = $email;
                    }
                }
                $to = array_values(array_unique($to));
                if (empty($to)) {
                    return;
                }

                $subject = trim((string) ($message->getSubject() ?? '')) ?: 'Sin asunto';
                $htmlBody = method_exists($message, 'getHtmlBody') ? (string) ($message->getHtmlBody() ?? '') : '';
                $textBody = method_exists($message, 'getTextBody') ? (string) ($message->getTextBody() ?? '') : '';
                $body = $htmlBody !== '' ? strip_tags($htmlBody) : $textBody;

                (new FileStore('email_history.json'))->create([
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => 'enviado',
                    'sent_by' => (string) (auth()->user()->email ?? session('user.email') ?? 'sistema'),
                    'sent_by_name' => (string) (auth()->user()->name ?? session('user.name') ?? 'Sistema'),
                    'source' => 'mail_event',
                    'sent_at' => now()->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                // No bloquear la app por errores del historial de correos.
            }
        });
    }

    /**
     * Ensure Laravel runtime directories exist and are writable in shared hosting.
     */
    private function ensureRuntimeDirectories(): void
    {
        $paths = [
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/testing'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($paths as $path) {
            try {
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0775, true, true);
                }
                if (!is_writable($path)) {
                    @chmod($path, 0775);
                }
            } catch (\Throwable $e) {
                // Ignore here; Laravel will surface a concrete filesystem error later if needed.
            }
        }
    }
}
