<?php

namespace App\Support\WhatsApp;

use App\Mcp\Support\PendingActions;
use App\Mcp\Tools\PrepararWhatsappCliente;
use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Después de enviar una factura por correo, avisa al cliente por WhatsApp según
 * Configuración > IA > WhatsApp: apagado, preguntar (te llega la propuesta a tu
 * WhatsApp y respondes "sí") o automático.
 */
class ClientInvoiceWhatsApp
{
    public const MODES = ['off' => 'No avisar', 'ask' => 'Preguntarme por WhatsApp antes de avisar', 'auto' => 'Avisar automáticamente'];

    /** La propuesta queda abierta un día para que alcances a responder. */
    private const ASK_MINUTES = 1440;

    public static function mode(): string
    {
        $s = (new FileStore('settings.json'))->find('settings') ?: [];
        $mode = (string) ($s['whatsapp_client_invoice'] ?? 'off');

        return isset(self::MODES[$mode]) && !empty($s['whatsapp_enabled']) ? $mode : 'off';
    }

    public static function afterEmailed(array $invoice, string $email): void
    {
        $mode = self::mode();
        if ($mode === 'off') {
            return;
        }

        $done = false;
        app()->terminating(function () use (&$done, $invoice, $email, $mode) {
            if ($done) {
                return;
            }
            $done = true;
            self::handle($invoice, $email, $mode);
        });
    }

    public static function handle(array $invoice, string $email, string $mode): void
    {
        try {
            $client = !empty($invoice['cliente_id']) ? (new FileStore('clientes.json'))->find((string) $invoice['cliente_id']) : null;
            $name = (string) (($client['contacto_nombre'] ?? '') ?: ($client['empresa'] ?? $invoice['cliente'] ?? 'cliente'));
            $phone = WhatsAppChannel::clientPhone((string) ($client['contacto_telefono'] ?? $client['telefono'] ?? ''));
            $channel = WhatsAppChannel::current();
            $settings = $channel->settings();
            if (!$settings['enabled'] || !$channel->configured($settings)) {
                return;
            }
            if ($phone === '') {
                WhatsAppEvents::record($channel->provider(), 'factura: cliente sin teléfono', '', ($invoice['numero'] ?? '') . ' · ' . $name);

                return;
            }

            $first = trim(explode(' ', $name)[0]);
            $text = sprintf('Hola %s, te acabamos de enviar a %s la factura %s por %s %s. Cualquier duda nos escribes por aquí. Indigo. Estudio Creativo',
                $first, $email, $invoice['numero'] ?? '', $invoice['moneda'] ?? '', number_format((float) ($invoice['total'] ?? 0), 0, ',', '.'));

            if ($mode === 'auto') {
                $error = $channel->send($phone, $text, $settings);
                WhatsAppEvents::record($channel->provider(), $error === null ? 'WhatsApp a cliente enviado' : 'WhatsApp a cliente no enviado', $phone, $error ?? ('Factura ' . ($invoice['numero'] ?? '')));

                return;
            }

            // Preguntar: el código queda a nombre del usuario de WhatsApp, que es quien responderá "sí".
            $owner = User::find($settings['user_id']);
            if (!$owner) {
                return;
            }
            $previous = Auth::user();
            Auth::setUser($owner);
            try {
                $code = (new PendingActions())->create('whatsapp_cliente', ['phone' => $phone, 'name' => $name, 'text' => $text], 'whatsapp', self::ASK_MINUTES);
            } finally {
                $previous ? Auth::setUser($previous) : Auth::forgetUser();
            }

            $proposal = "Le envié a {$name} la factura " . ($invoice['numero'] ?? '') . " por correo. ¿Le aviso también por WhatsApp?\n\n---\n\n"
                . str_replace("Para enviarlo, el usuario debe aprobarlo y tú llamas a confirmar_accion con el código {$code} (vence en " . self::ASK_MINUTES . ' minutos).',
                    "Código de confirmación: {$code}. Responde \"sí\" para enviarlo (vence en 24 horas).",
                    PrepararWhatsappCliente::preview($name, $phone, $text, $code, self::ASK_MINUTES));
            $error = ($channel->needsWindow() && !WhatsAppChannel::windowOpen())
                ? 'fuera de las 24 horas: escríbele algo al número de Infocus para recibir las propuestas'
                : $channel->send($settings['owner'], WhatsAppChannel::toWhatsAppText($proposal), $settings);
            WhatsAppEvents::record($channel->provider(), $error === null ? 'propuesta de aviso enviada' : 'propuesta de aviso no enviada', '', $error ?? ('Factura ' . ($invoice['numero'] ?? '')));
            if ($error === null) {
                WhatsAppAlerts::remember($proposal);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
