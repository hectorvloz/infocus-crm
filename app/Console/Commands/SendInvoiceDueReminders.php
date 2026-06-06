<?php

namespace App\Console\Commands;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendInvoiceDueReminders extends Command
{
    protected $signature = 'facturas:send-due-reminders {--days=}';

    protected $description = 'Envia recordatorios de factura por vencer al cliente';

    public function handle(): int
    {
        $settings = TemplateMail::settings();
        $days = $this->option('days') !== null
            ? max(0, (int) $this->option('days'))
            : max(0, (int) ($settings['invoice_due_days'] ?? 3));

        $facturasStore = new FileStore('facturas.json');
        $clientesStore = new FileStore('clientes.json');
        $rows = $facturasStore->all();
        $today = Carbon::today();
        $sent = 0;

        foreach ($rows as $factura) {
            $estado = (string) ($factura['estado'] ?? '');
            if (in_array($estado, ['Pagada', 'Cancelada'], true)) {
                continue;
            }
            if (empty($factura['vencimiento']) || empty($factura['id'])) {
                continue;
            }

            try {
                $dueDate = Carbon::parse((string) $factura['vencimiento'])->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            $remaining = $today->diffInDays($dueDate, false);
            if ($remaining !== $days) {
                continue;
            }

            $already = collect($factura['due_reminders_sent'] ?? [])->map(fn ($x) => (string) $x)->all();
            $token = $today->toDateString() . ':' . $days;
            if (in_array($token, $already, true)) {
                continue;
            }

            $client = !empty($factura['cliente_id']) ? $clientesStore->find((string) $factura['cliente_id']) : null;
            $to = $client['contacto_email'] ?? $client['email'] ?? null;
            if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $linkView = route('facturas.public', (string) $factura['id']);
            $linkPay = route('public.pay.checkout', ['invoiceId' => $factura['id']]);

            $vars = [
                'cliente' => $factura['cliente'] ?? 'Cliente',
                'folio' => $factura['numero'] ?? '---',
                'total' => (string) ($factura['total'] ?? 0),
                'vencimiento' => $dueDate->format('d/m/Y'),
                'dias_restantes' => (string) $remaining,
                'link_pago' => $linkPay,
                'empresa' => $settings['company_name'] ?? config('app.name', 'Infocus CRM'),
            ];

            [$subject, $body] = TemplateMail::render(
                $settings,
                'template_invoice_due_subject',
                'template_invoice_due_body',
                'Tu factura {folio} vence en {dias_restantes} dias',
                "Hola {cliente},\n\nTu factura {folio} vence el {vencimiento}.\n\nTotal: {total}",
                $vars,
                [
                    ['label' => 'Pagar ahora', 'url' => $linkPay, 'kind' => 'primary'],
                    ['label' => 'Ver factura', 'url' => $linkView, 'kind' => 'secondary'],
                ]
            );

            try {
                TemplateMail::send((string) $to, $subject, $body);
                $already[] = $token;
                $facturasStore->update((string) $factura['id'], ['due_reminders_sent' => array_values(array_unique($already))]);
                $sent++;
            } catch (\Throwable $e) {
                $this->warn('Error enviando recordatorio de factura ' . ($factura['numero'] ?? $factura['id']) . ': ' . $e->getMessage());
            }
        }

        $this->info('Recordatorios por vencer enviados: ' . $sent);

        return self::SUCCESS;
    }
}
