<?php

namespace App\Support\WhatsApp;

use App\Repositories\FileStore;
use Throwable;

/**
 * Avisos al WhatsApp del dueño cuando pasa algo en Infocus (pago recibido, lead
 * nuevo, reunión por empezar, facturas que vencen). Cada tipo se activa o apaga en
 * Configuración > IA > WhatsApp. Se envían después de responder la petición, para no
 * demorar a quien la hizo, y nunca rompen el flujo que los dispara.
 */
class WhatsAppAlerts
{
    public const TYPES = [
        'pago' => 'Pago recibido por Wompi',
        'lead' => 'Lead nuevo desde la web',
        'reunion' => 'Reunión en 30 minutos',
        'facturas' => 'Facturas que vencen hoy o vencidas (cada mañana)',
    ];

    public static function enabled(string $type): bool
    {
        $s = (new FileStore('settings.json'))->find('settings') ?: [];
        $alerts = (array) ($s['whatsapp_alerts'] ?? []);

        return !empty($s['whatsapp_enabled']) && (bool) ($alerts[$type] ?? true);
    }

    public static function send(string $type, string $text): void
    {
        if (!isset(self::TYPES[$type]) || !self::enabled($type)) {
            return;
        }

        $done = false;
        app()->terminating(function () use (&$done, $type, $text) {
            if ($done) {
                return;
            }
            $done = true;
            self::deliver($type, $text);
        });
    }

    /** Envío inmediato (lo usan los comandos programados y los tests). */
    public static function deliver(string $type, string $text): void
    {
        if (!isset(self::TYPES[$type]) || !self::enabled($type)) {
            return;
        }
        try {
            $channel = WhatsAppChannel::current();
            $settings = $channel->settings();
            if (!$settings['enabled'] || !$channel->configured($settings)) {
                return;
            }
            if ($channel->needsWindow() && !WhatsAppChannel::windowOpen()) {
                $error = $channel->hasTemplate($settings) ? $channel->sendTemplate($settings['owner'], $text, $settings) : 'fuera de las 24 horas y sin plantilla';
            } else {
                $error = $channel->send($settings['owner'], $text, $settings);
            }
            WhatsAppEvents::record($channel->provider(), $error === null ? 'aviso enviado' : 'aviso no enviado', '', self::TYPES[$type] . ($error ? ': ' . $error : ''));
            if ($error === null) {
                self::remember($text);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Queda en el historial del chat para que el dueño pueda responder sobre el aviso. */
    public static function remember(string $text): void
    {
        $store = new FileStore('whatsapp_chats.json');
        $chat = $store->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $chat['messages'] = array_values(array_slice([...((array) ($chat['messages'] ?? [])), [
            'role' => 'assistant', 'content' => $text, 'created_at' => now()->toISOString(), 'aviso' => true,
        ]], -20));
        $store->find('owner') ? $store->update('owner', $chat) : $store->create($chat);
    }
}
