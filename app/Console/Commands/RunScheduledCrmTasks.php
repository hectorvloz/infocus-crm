<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class RunScheduledCrmTasks extends Command
{
    protected $signature = 'crm:run-scheduled';

    protected $description = 'Ejecuta tareas programadas del CRM con tolerancia a retrasos del cron';

    public function handle(): int
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $timezone = (string) ($settings['timezone'] ?? $settings['app_timezone'] ?? config('app.timezone', 'America/Bogota'));
        $now = Carbon::now($timezone);
        $startedAt = $now->toIso8601String();
        $results = [];
        $errors = [];

        Storage::put('cron_status.json', json_encode([
            'last_run_at' => $now->toDateTimeString(),
            'last_started_at' => $startedAt,
            'timezone' => $timezone,
            'status' => 'running',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        foreach ([
            'facturas:procesar-recurrentes' => [],
            'facturas:send-scheduled-issue' => [],
            'leads:send-agenda-reminders' => ['--minutes' => 30],
            'reuniones:send-reminders' => ['--minutes' => 30],
        ] as $command => $params) {
            $results[$command] = $this->runScheduledCommand($command, $params, $errors);
        }

        if ($this->shouldRunDailyInvoiceReminders($settings, $timezone, $now)) {
            $results['facturas:send-due-reminders'] = $this->runScheduledCommand('facturas:send-due-reminders', [], $errors);
            $this->markDailyTaskRan('invoice_due_reminders', $now, $timezone, $results['facturas:send-due-reminders']);
        }

        $finishedAt = Carbon::now($timezone);
        $status = empty($errors) ? 'ok' : 'completed_with_errors';
        $payload = [
            'last_run_at' => $finishedAt->toDateTimeString(),
            'last_started_at' => $startedAt,
            'last_finished_at' => $finishedAt->toIso8601String(),
            'timezone' => $timezone,
            'status' => $status,
            'results' => $results,
            'errors' => $errors,
        ];
        Storage::put('cron_status.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->appendLog($payload);

        $this->info('Tareas programadas procesadas: ' . $status);
        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    private function runScheduledCommand(string $command, array $params, array &$errors): array
    {
        try {
            $code = Artisan::call($command, $params);
            $output = trim(Artisan::output());
            if ($code !== self::SUCCESS) {
                $errors[] = [
                    'command' => $command,
                    'message' => $output !== '' ? $output : 'El comando termino con codigo ' . $code,
                    'at' => now()->toIso8601String(),
                ];
            }

            return [
                'ok' => $code === self::SUCCESS,
                'exit_code' => $code,
                'output' => $output,
                'ran_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $errors[] = [
                'command' => $command,
                'message' => $e->getMessage(),
                'at' => now()->toIso8601String(),
            ];

            return [
                'ok' => false,
                'exit_code' => 1,
                'output' => $e->getMessage(),
                'ran_at' => now()->toIso8601String(),
            ];
        }
    }

    private function shouldRunDailyInvoiceReminders(array $settings, string $timezone, Carbon $now): bool
    {
        if (empty($settings['invoice_due_reminders_enabled']) && array_key_exists('invoice_due_reminders_enabled', $settings)) {
            return false;
        }

        $time = (string) ($settings['invoice_due_reminder_time'] ?? '08:10');
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time = '08:10';
        }

        $cutoff = $now->copy()->startOfDay()->setTimeFromTimeString($time);
        if ($now->lt($cutoff)) {
            return false;
        }

        $state = $this->schedulerState();
        $lastDate = (string) data_get($state, 'daily.invoice_due_reminders.date', '');

        return $lastDate !== $now->toDateString();
    }

    private function markDailyTaskRan(string $key, Carbon $now, string $timezone, array $result): void
    {
        $state = $this->schedulerState();
        $state['daily'][$key] = [
            'date' => $now->toDateString(),
            'ran_at' => $now->toIso8601String(),
            'timezone' => $timezone,
            'ok' => (bool) ($result['ok'] ?? false),
        ];
        Storage::put('scheduler_state.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function schedulerState(): array
    {
        if (!Storage::exists('scheduler_state.json')) {
            return [];
        }

        return json_decode(Storage::get('scheduler_state.json'), true) ?: [];
    }

    private function appendLog(array $payload): void
    {
        $log = [];
        if (Storage::exists('cron_runs.json')) {
            $log = json_decode(Storage::get('cron_runs.json'), true) ?: [];
        }

        $log[] = $payload;
        $log = array_slice($log, -120);
        Storage::put('cron_runs.json', json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
