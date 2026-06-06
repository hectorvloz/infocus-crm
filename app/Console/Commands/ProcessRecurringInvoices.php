<?php

namespace App\Console\Commands;

use App\Http\Controllers\FacturasController;
use App\Mail\GenericMail;
use App\Repositories\FileStore;
use App\Repositories\TimelineStore;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'facturas:procesar-recurrentes';

    protected $description = 'Genera y envia automaticamente facturas recurrentes en su fecha programada';

    public function handle(): int
    {
        $facturasStore = new FileStore('facturas.json');
        $clientesStore = new FileStore('clientes.json');
        $productosStore = new FileStore('productos.json');
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $timezone = (string) ($settings['timezone'] ?? $settings['app_timezone'] ?? config('app.timezone', 'America/Bogota'));
        $now = Carbon::now($timezone);
        $sendTime = $this->recurringSendTime($settings);
        $sendCutoff = $now->copy()->startOfDay()->setTimeFromTimeString($sendTime);
        $dueThrough = $now->gte($sendCutoff)
            ? $now->copy()->startOfDay()
            : $now->copy()->subDay()->startOfDay();
        $productsByName = collect($productosStore->all() ?: [])
            ->filter(fn ($p) => !empty($p['nombre']))
            ->keyBy(fn ($p) => mb_strtolower(trim((string) $p['nombre'])))
            ->all();

        $allInvoices = $facturasStore->all();
        $today = $now->copy()->startOfDay();

        $templates = collect($allInvoices)->filter(function ($invoice) {
            return (bool) data_get($invoice, 'recurrencia.enabled', false)
                && !empty(data_get($invoice, 'recurrencia.next_send'));
        })->values();

        $createdCount = 0;
        $sentCount = 0;
        $failedCount = 0;
        $runEvents = [];
        $runStartedAt = now()->toIso8601String();

        foreach ($templates as $template) {
            $rec = (array) ($template['recurrencia'] ?? []);
            $dayOfMonth = max(1, min(31, (int) ($rec['day_of_month'] ?? 1)));
            $everyMonths = max(1, min(12, (int) ($rec['every_months'] ?? 1)));

            try {
                $nextSend = Carbon::parse((string) $rec['next_send'])->startOfDay();
            } catch (\Throwable $e) {
                $rec['last_checked_at'] = now()->toIso8601String();
                $rec['last_run_result'] = 'invalid_next_send';
                $rec['last_error'] = 'Fecha next_send invalida: ' . (string) ($rec['next_send'] ?? '');
                $facturasStore->update((string) ($template['id'] ?? ''), ['recurrencia' => $rec]);
                $runEvents[] = $this->buildRunEvent($template, null, 'invalid_next_send', $rec['last_error']);
                continue;
            }

            $rec['last_checked_at'] = now()->toIso8601String();

            $this->processServiceExpiryReminders(
                $facturasStore,
                $clientesStore,
                $settings,
                $template,
                $rec,
                $nextSend,
                $today,
                $productsByName,
                $allInvoices
            );

            $safety = 0;
            while ($nextSend->lte($dueThrough) && $safety < 24) {
                $safety++;
                $issueDate = $nextSend->format('Y-m-d');
                $newInvoice = $this->findRecurringCycleInvoice($allInvoices, $template, $issueDate);
                $createdNow = false;
                if (!$newInvoice) {
                    $newInvoice = $this->createRecurringInvoice($facturasStore, $allInvoices, $template, $issueDate);
                    $createdCount++;
                    $createdNow = true;
                }

                $alreadySent = !empty($newInvoice['sent_at']);
                $alreadyPaid = $this->isInvoicePaid($newInvoice);
                $sendOk = true;
                $sendError = null;
                $clientEmail = null;
                if (!$alreadySent && !$alreadyPaid) {
                    [$sendOk, $sendError, $clientEmail] = $this->sendInvoiceToClient($newInvoice, $clientesStore, 'recurrente');
                }

                if ($alreadySent || $alreadyPaid || $sendOk) {
                    $sentCount++;
                    $facturasStore->update($newInvoice['id'], [
                        'estado' => 'Pendiente',
                        'sent_at' => $alreadySent ? ($newInvoice['sent_at'] ?? now()->toISOString()) : now()->toISOString(),
                        'recurring_send_status' => $alreadyPaid ? 'paid' : 'sent',
                        'recurring_send_error' => null,
                    ]);
                    $rec['last_sent_at'] = now()->toISOString();
                    $rec['last_run_result'] = $createdNow ? 'created_and_sent' : 'existing_sent';
                    $rec['last_error'] = null;
                    $runEvents[] = $this->buildRunEvent($template, $newInvoice, $rec['last_run_result'], null, $clientEmail);
                } else {
                    $failedCount++;
                    $facturasStore->update($newInvoice['id'], [
                        'estado' => 'Pendiente',
                        'recurring_send_status' => 'failed',
                        'recurring_send_error' => $sendError,
                        'recurring_send_failed_at' => now()->toISOString(),
                    ]);
                    $rec['last_run_result'] = $createdNow ? 'created_email_failed' : 'existing_email_failed';
                    $rec['last_error'] = $sendError ?: 'No se pudo enviar la factura recurrente';
                    $runEvents[] = $this->buildRunEvent($template, $newInvoice, $rec['last_run_result'], $rec['last_error'], $clientEmail);
                }

                $this->notifyOwner($settings, $template, $newInvoice, $clientEmail, $sendOk, $sendError);

                $nextSend = $this->calculateNextSend($nextSend, $dayOfMonth, $everyMonths);
                $rec['next_send'] = $nextSend->format('Y-m-d');
                $facturasStore->update($template['id'], ['recurrencia' => $rec]);
            }

            if ($safety === 0) {
                $rec['last_run_result'] = $nextSend->isSameDay($today) && $today->gt($dueThrough)
                    ? 'waiting_send_time'
                    : 'not_due';
                $rec['last_error'] = null;
                $facturasStore->update((string) ($template['id'] ?? ''), ['recurrencia' => $rec]);
            }
        }

        $this->appendRunLog([
            'started_at' => $runStartedAt,
            'finished_at' => now()->toIso8601String(),
            'today' => $today->format('Y-m-d'),
            'due_through' => $dueThrough->format('Y-m-d'),
            'send_time' => $sendTime,
            'templates_checked' => $templates->count(),
            'created' => $createdCount,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'events' => $runEvents,
        ]);

        $this->info("Facturas recurrentes procesadas. Creadas: {$createdCount} | Enviadas: {$sentCount} | Fallidas: {$failedCount}");

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processServiceExpiryReminders(
        FileStore $facturasStore,
        FileStore $clientesStore,
        array $settings,
        array $template,
        array &$rec,
        Carbon $nextSend,
        Carbon $today,
        array $productsByName,
        array &$allInvoices
    ): void {
        $clientEmail = $this->resolveClientEmail($template, $clientesStore);
        if (!$clientEmail || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $items = collect($template['items'] ?? [])->filter(fn ($it) => !empty($it['descripcion']))->values();
        if ($items->isEmpty()) {
            return;
        }

        $sentMap = (array) ($rec['service_reminder_sent'] ?? []);
        $nextSendDate = $nextSend->format('Y-m-d');
        $cycleKey = 'invoice|' . ($template['id'] ?? 'sin-id') . '|' . $nextSendDate;
        if (!empty($sentMap[$cycleKey])) {
            return;
        }

        $matches = [];

        foreach ($items as $item) {
            $productName = trim((string) ($item['descripcion'] ?? ''));
            $productKey = mb_strtolower($productName);
            $product = $productsByName[$productKey] ?? null;
            if (!$product || !($product['service_expiry_reminder_enabled'] ?? false)) {
                continue;
            }

            $daysBefore = max(1, min(90, (int) ($product['service_expiry_reminder_days_before'] ?? 7)));
            $reminderDate = $nextSend->copy()->subDays($daysBefore);
            if (!$today->gte($reminderDate) || !$today->lt($nextSend)) {
                continue;
            }
            $matches[] = [
                'product' => $product,
                'item' => $item,
                'days_before' => $daysBefore,
            ];
        }

        if (empty($matches)) {
            return;
        }

        $issueDate = $nextSend->format('Y-m-d');
        $invoiceForCycle = $this->findRecurringCycleInvoice($allInvoices, $template, $issueDate);
        if (!$invoiceForCycle) {
            $invoiceForCycle = $this->createRecurringInvoice($facturasStore, $allInvoices, $template, $issueDate);
            $facturasStore->update($invoiceForCycle['id'], [
                'origen' => 'recurrente_preemitida',
                'estado' => 'Pendiente',
            ]);
            $invoiceForCycle['origen'] = 'recurrente_preemitida';
            $invoiceForCycle['estado'] = 'Pendiente';
        }

        $ok = $this->sendServiceExpiryReminder(
            $invoiceForCycle,
            $clientEmail,
            $template,
            $matches,
            $nextSend
        );

        if ($ok) {
            $sentMap[$cycleKey] = now()->toIso8601String();
            $rec['service_reminder_sent'] = $sentMap;
            $facturasStore->update((string) ($template['id'] ?? ''), ['recurrencia' => $rec]);
            $this->notifyOwnerServiceReminder($settings, $template, $invoiceForCycle, $clientEmail, $matches, $nextSend);
        }
    }

    private function sendServiceExpiryReminder(
        array $invoiceForCycle,
        string $to,
        array $invoiceTemplate,
        array $matches,
        Carbon $nextSend
    ): bool {
        try {
            $serviceItems = collect($matches)->map(function ($m) {
                return [
                    'title' => (string) (($m['product']['nombre'] ?? $m['item']['descripcion'] ?? '')),
                    'description' => (string) (($m['product']['descripcion'] ?? '')),
                ];
            })->values()->all();

            [$ok] = $this->sendInvoiceToClient(
                $invoiceForCycle,
                new FileStore('clientes.json'),
                'service_due',
                $serviceItems
            );
            return $ok;
        } catch (\Throwable $e) {
            $this->warn('No se pudo enviar recordatorio de servicio: ' . $e->getMessage());
            return false;
        }
    }

    private function findRecurringCycleInvoice(array $allInvoices, array $template, string $issueDate): ?array
    {
        $templateId = (string) ($template['id'] ?? '');
        if ($templateId === '') {
            return null;
        }

        foreach ($allInvoices as $inv) {
            if ((string) ($inv['recurrencia_origen_id'] ?? '') !== $templateId) {
                continue;
            }
            if ((string) ($inv['fecha'] ?? '') !== $issueDate) {
                continue;
            }
            return $inv;
        }

        return null;
    }

    private function resolveClientEmail(array $invoice, FileStore $clientesStore): ?string
    {
        if (!empty($invoice['cliente_id'])) {
            $client = $clientesStore->find((string) $invoice['cliente_id']);
            if ($client) {
                $email = $client['contacto_email'] ?? $client['email'] ?? null;
                if (is_string($email) && $email !== '') {
                    return $email;
                }
            }
        }

        return null;
    }

    private function createRecurringInvoice(FileStore $store, array &$allInvoices, array $template, string $issueDate): array
    {
        $new = $template;
        unset(
            $new['id'],
            $new['created_at'],
            $new['updated_at'],
            $new['pagos'],
            $new['saldo'],
            $new['saldo_base'],
            $new['sent_at'],
            $new['recurrencia']
        );

        $new['numero'] = $this->nextInvoiceNumber($allInvoices);
        $new['fecha'] = $issueDate;
        $new['estado'] = 'Pendiente';
        $new['origen'] = 'recurrente';
        $new['recurrencia_origen_id'] = $template['id'] ?? null;
        $new['recurring_send_status'] = 'pending';
        $new['recurring_generated_at'] = now()->toISOString();

        if (!empty($template['vencimiento']) && !empty($template['fecha'])) {
            $creditoDias = max(0, (int) floor((strtotime($template['vencimiento']) - strtotime($template['fecha'])) / 86400));
            $new['vencimiento'] = date('Y-m-d', strtotime($issueDate . " +{$creditoDias} days"));
        }

        $created = $store->create($new);
        $allInvoices[] = $created;
        $this->addInvoiceTimelineEvent($created, 'Creada');

        return $created;
    }

    private function addInvoiceTimelineEvent(array $invoice, ?string $estado = null): void
    {
        if (empty($invoice['cliente_id'])) {
            return;
        }

        (new TimelineStore())->add((string) $invoice['cliente_id'], 'factura', [
            'numero' => $invoice['numero'] ?? '',
            'total' => $invoice['total'] ?? 0,
            'total_base' => $invoice['total_base'] ?? null,
            'moneda' => $invoice['moneda'] ?? null,
            'estado' => $estado ?? ($invoice['estado'] ?? ''),
            'factura_id' => $invoice['id'] ?? null,
        ]);
    }

    private function buildRunEvent(array $template, ?array $invoice, string $result, ?string $error = null, ?string $clientEmail = null): array
    {
        return [
            'at' => now()->toIso8601String(),
            'template_id' => (string) ($template['id'] ?? ''),
            'template_number' => (string) ($template['numero'] ?? ''),
            'client' => (string) ($template['cliente'] ?? ''),
            'invoice_id' => $invoice ? (string) ($invoice['id'] ?? '') : null,
            'invoice_number' => $invoice ? (string) ($invoice['numero'] ?? '') : null,
            'issue_date' => $invoice ? (string) ($invoice['fecha'] ?? '') : null,
            'result' => $result,
            'client_email' => $clientEmail,
            'error' => $error,
        ];
    }

    private function appendRunLog(array $entry): void
    {
        try {
            $path = 'recurring_invoice_runs.json';
            $rows = Storage::exists($path) ? (json_decode((string) Storage::get($path), true) ?: []) : [];
            $rows[] = $entry;
            $rows = array_slice($rows, -200);
            Storage::put($path, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $this->warn('No se pudo guardar log de recurrencias: ' . $e->getMessage());
        }
    }

    private function recurringSendTime(array $settings): string
    {
        $raw = trim((string) ($settings['recurring_send_time'] ?? '08:00'));
        if (preg_match('/^\d{1,2}:\d{2}$/', $raw)) {
            [$hour, $minute] = array_map('intval', explode(':', $raw, 2));
            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return '08:00';
    }

    private function sendInvoiceToClient(
        array $invoice,
        FileStore $clientesStore,
        string $mailMode = 'invoice',
        array $serviceItems = []
    ): array
    {
        $clientEmail = null;

        if (!empty($invoice['cliente_id'])) {
            $client = $clientesStore->find((string) $invoice['cliente_id']);
            if ($client) {
                $clientEmail = $client['contacto_email'] ?? $client['email'] ?? null;
            }
        }

        if (!$clientEmail || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return [false, 'No hay correo del cliente configurado', null];
        }

        try {
            $controller = app(FacturasController::class);
            $request = Request::create('/api/facturas/enviar', 'POST', [
                'id' => $invoice['id'],
                'to' => $clientEmail,
                'mail_mode' => $mailMode,
                'service_items' => $serviceItems,
            ]);
            $response = $controller->enviarEmail($request);
            $payload = method_exists($response, 'getData') ? $response->getData(true) : [];

            if (!($payload['ok'] ?? false)) {
                $error = (string) ($payload['error'] ?? 'Error al enviar la factura recurrente');
                return [false, $error, $clientEmail];
            }

            return [true, null, $clientEmail];
        } catch (\Throwable $e) {
            return [false, $e->getMessage(), $clientEmail];
        }
    }

    private function isInvoicePaid(array $invoice): bool
    {
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return true;
        }

        $items = (array) ($invoice['items'] ?? []);
        $subtotal = collect($items)->sum(fn ($i) => (float) ($i['cantidad'] ?? 0) * (float) ($i['precio'] ?? 0));
        $taxRate = (float) ($invoice['tax_rate'] ?? 0);
        $total = round($subtotal + ($subtotal * ($taxRate / 100)), 2);
        $paid = round(collect((array) ($invoice['pagos'] ?? []))->sum(fn ($p) => (float) ($p['monto'] ?? 0)), 2);
        return $paid >= max(0, $total - 0.01);
    }

    private function notifyOwnerServiceReminder(
        array $settings,
        array $template,
        array $invoiceForCycle,
        string $clientEmail,
        array $matches,
        Carbon $nextSend
    ): void {
        $ownerEmail = $settings['recurring_notify_email']
            ?? $settings['mail_from_address']
            ?? $settings['email_from']
            ?? $settings['smtp_username']
            ?? null;

        if (!$ownerEmail || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $cliente = (string) ($template['cliente'] ?? 'Cliente');
        $numero = (string) ($invoiceForCycle['numero'] ?? ($invoiceForCycle['id'] ?? '---'));
        $products = collect($matches)
            ->map(fn ($m) => (string) (($m['product']['nombre'] ?? $m['item']['descripcion'] ?? '')))
            ->filter()
            ->unique()
            ->implode(', ');

        $linkView = route('facturas.public', (string) ($invoiceForCycle['id'] ?? ''));
        $linkPdf = route('facturas.public.pdf', (string) ($invoiceForCycle['id'] ?? ''));

        $subject = 'Recordatorio de vencimiento para cliente: ' . $cliente;
        $body = '<h3 style="margin:0 0 10px;color:#0f172a;">Recordatorio de vencimiento enviado</h3>'
            . '<p style="margin:0 0 10px;color:#334155;">Se envió al cliente <strong>' . e($cliente) . '</strong> un recordatorio previo de vencimiento.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:8px 0 14px;">'
            . '<tr><td style="padding:5px 0;color:#64748b;">Correo cliente</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($clientEmail) . '</td></tr>'
            . '<tr><td style="padding:5px 0;color:#64748b;">Factura</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($numero) . '</td></tr>'
            . '<tr><td style="padding:5px 0;color:#64748b;">Próximo envío recurrente</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($nextSend->format('d/m/Y')) . '</td></tr>'
            . '<tr><td style="padding:5px 0;color:#64748b;">Servicios/Productos</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($products !== '' ? $products : '—') . '</td></tr>'
            . '</table>'
            . '<div style="text-align:center;margin-top:10px;">'
            . '<a href="' . e($linkView) . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;border:1px solid #e2e8f0;margin-right:8px;">Ver factura</a>'
            . '<a href="' . e($linkPdf) . '" style="display:inline-block;background:#1e293b;color:#f8fafc;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;">Descargar factura</a>'
            . '</div>';

        try {
            Mail::to($ownerEmail)->send(new GenericMail($subject, $body));
        } catch (\Throwable $e) {
            $this->warn('No se pudo notificar recordatorio al propietario: ' . $e->getMessage());
        }
    }

    private function notifyOwner(array $settings, array $template, array $invoice, ?string $clientEmail, bool $ok, ?string $error): void
    {
        $ownerEmail = $settings['recurring_notify_email']
            ?? $settings['mail_from_address']
            ?? $settings['email_from']
            ?? $settings['smtp_username']
            ?? null;

        if (!$ownerEmail || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $statusLabel = $ok ? 'Enviada' : 'Con error';
        $statusColor = $ok ? '#16a34a' : '#dc2626';
        $numero = $invoice['numero'] ?? ($invoice['id'] ?? '');
        $cliente = $invoice['cliente'] ?? 'Cliente';
        $subject = ($ok ? 'OK' : 'Error') . " · Factura recurrente {$numero}";

        $body = '
            <h3 style="margin:0 0 8px;font-size:18px;color:#0f172a;">Notificacion de factura recurrente</h3>
            <p style="margin:0 0 12px;color:#475569;">Se proceso una factura recurrente programada.</p>
            <table style="width:100%;border-collapse:collapse;">
                <tr><td style="padding:6px 0;color:#64748b;">Estado</td><td style="padding:6px 0;text-align:right;color:' . $statusColor . ';font-weight:700;">' . $statusLabel . '</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Factura</td><td style="padding:6px 0;text-align:right;color:#0f172a;font-weight:600;">' . e($numero) . '</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Cliente</td><td style="padding:6px 0;text-align:right;color:#0f172a;">' . e($cliente) . '</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Correo cliente</td><td style="padding:6px 0;text-align:right;color:#0f172a;">' . e($clientEmail ?: 'No definido') . '</td></tr>
                <tr><td style="padding:6px 0;color:#64748b;">Plantilla origen</td><td style="padding:6px 0;text-align:right;color:#0f172a;">' . e($template['numero'] ?? ($template['id'] ?? '')) . '</td></tr>
            </table>';

        if (!$ok && $error) {
            $body .= '<p style="margin-top:14px;padding:10px 12px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">' . e($error) . '</p>';
        }

        try {
            Mail::to($ownerEmail)->send(new GenericMail($subject, $body));
        } catch (\Throwable $e) {
            $this->warn('No se pudo notificar al administrador: ' . $e->getMessage());
        }
    }

    private function calculateNextSend(Carbon $current, int $dayOfMonth, int $everyMonths): Carbon
    {
        $next = $current->copy()->addMonthsNoOverflow($everyMonths);
        $next->day(min($dayOfMonth, $next->daysInMonth));

        return $next->startOfDay();
    }

    private function nextInvoiceNumber(array $rows, string $prefix = 'INV'): string
    {
        $max = 0;

        foreach ($rows as $f) {
            $numero = strtoupper((string) ($f['numero'] ?? ''));
            if ($numero === '') {
                continue;
            }

            if (preg_match('/^' . preg_quote(strtoupper($prefix), '/') . '-(\d+)/', $numero, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return strtoupper($prefix) . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
