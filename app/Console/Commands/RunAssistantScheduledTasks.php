<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiToolBridge;
use App\Support\Ai\AssistantNotifier;
use App\Support\Ai\ScheduledTasks;
use App\Support\Ai\SensitiveDataFilter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ejecuta los avisos y consultas programadas del asistente que ya tocan.
 * Lo llama crm:run-scheduled cada minuto. Cada tarea se reprograma antes de
 * ejecutarse para que un cron lento o repetido no la mande dos veces.
 */
class RunAssistantScheduledTasks extends Command
{
    protected $signature = 'asistente:run-scheduled';

    protected $description = 'Ejecuta los avisos y consultas programadas del asistente';

    private const MAX_PER_RUN = 10;

    public function handle(AssistantNotifier $notifier): int
    {
        $store = ScheduledTasks::store();
        $now = CarbonImmutable::now(ScheduledTasks::TZ);
        $due = collect($store->all())
            ->filter(fn ($t) => !empty($t['activa']) && !empty($t['proxima']) && CarbonImmutable::parse($t['proxima'])->lessThanOrEqualTo($now))
            ->sortBy('proxima')
            ->take(self::MAX_PER_RUN);

        $ran = 0;
        foreach ($due as $task) {
            $next = ScheduledTasks::nextRun($task, $now);
            $store->update($task['id'], [
                'proxima' => $next?->toIso8601String(),
                'activa' => $next !== null,
            ]);

            $state = $this->runTask($task, $notifier);
            $store->update($task['id'], ['ultima' => $now->format('Y-m-d H:i'), 'ultimo_estado' => $state]);
            $ran++;
        }

        $this->dailyInvoiceAlert($now);

        $this->info("Tareas del asistente ejecutadas: {$ran}");

        return self::SUCCESS;
    }

    /** Una vez al día, desde las 8:00, avisa por WhatsApp las facturas que vencen hoy o ya vencieron. */
    private function dailyInvoiceAlert(CarbonImmutable $now): void
    {
        if ((int) $now->format('H') < 8 || !\App\Support\WhatsApp\WhatsAppAlerts::enabled('facturas')) {
            return;
        }
        $state = new FileStore('whatsapp_alerts_state.json');
        $today = $now->toDateString();
        if ((($state->find('state') ?: [])['facturas_date'] ?? '') === $today) {
            return;
        }
        $state->find('state') ? $state->update('state', ['facturas_date' => $today]) : $state->create(['id' => 'state', 'facturas_date' => $today]);

        $open = collect((new FileStore('facturas.json'))->all())
            ->filter(fn ($f) => !in_array((string) ($f['estado'] ?? ''), ['Pagada', 'En borrador', 'Anulada'], true) && !empty($f['vencimiento']) && (string) $f['vencimiento'] <= $today)
            ->sortBy('vencimiento');
        if ($open->isEmpty()) {
            return;
        }
        $line = fn ($f) => sprintf('- %s · %s · %s %s · %s', $f['numero'] ?? $f['id'], $f['cliente'] ?? 'Sin cliente', $f['moneda'] ?? '', number_format((float) ($f['saldo'] ?? $f['total'] ?? 0), 0, ',', '.'),
            (string) $f['vencimiento'] === $today ? 'vence hoy' : 'vencida desde ' . $f['vencimiento']);
        $text = 'Facturas por cobrar (' . $open->count() . "):\n" . $open->take(8)->map($line)->implode("\n")
            . ($open->count() > 8 ? "\n- y " . ($open->count() - 8) . ' más en Infocus.' : '')
            . "\nSi quieres, pídeme los recordatorios de cobro.";
        \App\Support\WhatsApp\WhatsAppAlerts::deliver('facturas', $text);
    }

    private function runTask(array $task, AssistantNotifier $notifier): string
    {
        $user = User::find($task['user_id'] ?? null);
        if (!$user) {
            return 'error: el usuario ya no existe';
        }
        Auth::setUser($user);

        try {
            if (($task['tipo'] ?? 'aviso') === 'aviso') {
                return $notifier->deliver($user, (string) ($task['canal'] ?? 'whatsapp'), 'Recordatorio de Infocus', 'Recordatorio: ' . $task['texto']);
            }

            $settings = (new FileStore('settings.json'))->find('settings') ?: [];
            if (empty($settings['ai_enabled']) || empty($settings['ai_tools_enabled'])) {
                $text = 'No pude hacer tu consulta programada («' . Str::limit((string) $task['texto'], 80) . '»): activa el asistente con herramientas en Configuración > IA.';
            } else {
                $ai = new AiService(new SensitiveDataFilter(), new AiToolBridge(readOnly: true));
                $result = $ai->reply((string) $task['texto'], [], ['channel' => 'programada']);
                $text = 'Consulta programada: «' . Str::limit((string) $task['texto'], 120) . "»\n\n" . trim((string) ($result['content'] ?? ''));
            }

            return $notifier->deliver($user, (string) ($task['canal'] ?? 'whatsapp'), 'Consulta programada de Infocus', $text);
        } catch (Throwable $e) {
            report($e);

            return 'error: ' . Str::limit($e->getMessage(), 120);
        } finally {
            Auth::forgetUser();
        }
    }
}
