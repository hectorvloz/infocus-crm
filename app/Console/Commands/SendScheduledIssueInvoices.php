<?php

namespace App\Console\Commands;

use App\Http\Controllers\FacturasController;
use App\Repositories\FileStore;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SendScheduledIssueInvoices extends Command
{
    protected $signature = 'facturas:send-scheduled-issue';

    protected $description = 'Envia facturas publicadas con fecha de emision futura cuando llega su fecha';

    public function handle(): int
    {
        $facturasStore = new FileStore('facturas.json');
        $clientesStore = new FileStore('clientes.json');
        $rows = $facturasStore->all();
        $today = Carbon::today();
        $sent = 0;

        $controller = app(FacturasController::class);

        foreach ($rows as $factura) {
            if (empty($factura['auto_send_on_issue_date'])) {
                continue;
            }
            if (!empty($factura['sent_at'])) {
                continue;
            }

            $estado = (string) ($factura['estado'] ?? '');
            if (!in_array($estado, ['Pendiente', 'Enviada', 'Vencida'], true)) {
                continue;
            }

            try {
                $issueDate = Carbon::parse((string) ($factura['fecha'] ?? ''))->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            if ($issueDate->gt($today)) {
                continue;
            }

            $cliente = !empty($factura['cliente_id']) ? $clientesStore->find((string) $factura['cliente_id']) : null;
            $to = trim((string) ($cliente['contacto_email'] ?? $cliente['email'] ?? ''));
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $facturasStore->update((string) $factura['id'], [
                    'auto_send_on_issue_date' => false,
                    'auto_send_error' => 'cliente sin email valido',
                    'auto_send_processed_at' => now()->toIso8601String(),
                ]);
                continue;
            }

            try {
                $response = $controller->enviarEmail(new Request([
                    'id' => (string) $factura['id'],
                    'to' => $to,
                ]));
                $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
                $ok = (bool) ($payload['ok'] ?? false);

                $patch = [
                    'auto_send_on_issue_date' => false,
                    'auto_send_processed_at' => now()->toIso8601String(),
                ];
                if (!$ok) {
                    $patch['auto_send_error'] = (string) ($payload['error'] ?? 'error desconocido');
                }
                $facturasStore->update((string) $factura['id'], $patch);

                if ($ok) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $facturasStore->update((string) $factura['id'], [
                    'auto_send_on_issue_date' => false,
                    'auto_send_error' => $e->getMessage(),
                    'auto_send_processed_at' => now()->toIso8601String(),
                ]);
            }
        }

        $this->info('Facturas publicadas con envío diferido procesadas: ' . $sent);
        return self::SUCCESS;
    }
}

