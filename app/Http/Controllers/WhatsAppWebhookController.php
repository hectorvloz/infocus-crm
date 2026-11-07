<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\WhatsApp\TwilioWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Recibe los WhatsApp que Twilio reenvía y los contesta con el asistente de Infocus.
 * Solo atiende al número del dueño configurado en Configuración > IA > WhatsApp,
 * y solo si la firma de Twilio es válida. Responde a Twilio de inmediato y trabaja
 * después, porque Twilio corta a los 15 segundos.
 */
class WhatsAppWebhookController extends Controller
{
    private const HISTORY = 20;

    private const SEEN = 200;

    public function handle(Request $request, TwilioWhatsApp $whatsapp)
    {
        $settings = $whatsapp->settings();
        if (!$settings['enabled'] || !$whatsapp->configured($settings)) {
            return response('WhatsApp no está activo.', 404);
        }

        $signature = (string) $request->header('X-Twilio-Signature', '');
        $params = $request->post();
        $urls = array_unique([$request->fullUrl(), preg_replace('/^http:/', 'https:', $request->fullUrl()), url('/webhooks/whatsapp')]);
        $valid = collect($urls)->contains(fn ($url) => TwilioWhatsApp::validSignature($settings['token'], $url, $params, $signature));
        if (!$valid) {
            return response('Firma inválida.', 403);
        }

        $from = TwilioWhatsApp::normalizeNumber((string) $request->post('From', ''));
        $body = trim((string) $request->post('Body', ''));
        $sid = (string) $request->post('MessageSid', '');
        if ($from !== $settings['owner'] || $sid === '' || !$this->markSeen($sid)) {
            return $this->twiml();
        }

        $media = (int) $request->post('NumMedia', 0);
        $done = false;
        app()->terminating(function () use (&$done, $whatsapp, $settings, $body, $media) {
            if ($done) {
                return;
            }
            $done = true;
            $this->answer($whatsapp, $settings, $body, $media);
        });

        return $this->twiml();
    }

    private function answer(TwilioWhatsApp $whatsapp, array $settings, string $body, int $media): void
    {
        ignore_user_abort(true);
        @set_time_limit(180);

        $owner = $settings['owner'];
        if ($body === '') {
            $whatsapp->send($owner, $media > 0 ? 'Por ahora solo leo texto. Escríbeme lo que necesitas.' : 'No recibí texto.', $settings);

            return;
        }

        $user = User::find($settings['user_id']);
        if (!$user) {
            $whatsapp->send($owner, 'Falta elegir en Infocus el usuario con el que respondo (Configuración > IA > WhatsApp).', $settings);

            return;
        }

        $aiSettings = (new FileStore('settings.json'))->find('settings') ?: [];
        if (empty($aiSettings['ai_tools_enabled'])) {
            $whatsapp->send($owner, 'Activa "Asistente con herramientas" en Configuración > IA para que pueda trabajar desde WhatsApp.', $settings);

            return;
        }

        Auth::setUser($user);
        $store = new FileStore('whatsapp_chats.json');
        $chat = $store->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $history = array_values((array) ($chat['messages'] ?? []));

        try {
            $result = app(AiService::class)->reply($body, $history, ['channel' => 'whatsapp']);
            $content = (string) ($result['content'] ?? '');
        } catch (Throwable $e) {
            report($e);
            $content = 'No pude procesar tu mensaje. Intenta de nuevo en un momento.';
            $result = [];
        }

        $error = $whatsapp->send($owner, TwilioWhatsApp::toWhatsAppText($content), $settings);
        if ($error !== null) {
            report(new \RuntimeException('WhatsApp: ' . $error));
        }

        $chat = $store->find('owner') ?: $chat;
        $chat['messages'] = array_values(array_slice([
            ...$history,
            ['role' => 'user', 'content' => $body, 'created_at' => now()->toISOString()],
            ['role' => 'assistant', 'content' => $content, 'created_at' => now()->toISOString(), 'tools_used' => $result['tools_used'] ?? []],
        ], -self::HISTORY));
        $this->saveChat($store, $chat);
    }

    /** Twilio reintenta si no responde a tiempo: un mismo MessageSid se atiende una sola vez. */
    private function markSeen(string $sid): bool
    {
        $store = new FileStore('whatsapp_chats.json');
        $chat = $store->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $seen = array_values((array) ($chat['seen'] ?? []));
        if (in_array($sid, $seen, true)) {
            return false;
        }
        $chat['seen'] = array_slice([...$seen, $sid], -self::SEEN);
        // Abre la ventana de 24 horas en la que se puede escribir sin plantilla.
        $chat['last_inbound_at'] = now()->toISOString();
        $this->saveChat($store, $chat);

        return true;
    }

    private function saveChat(FileStore $store, array $chat): void
    {
        $store->find('owner') ? $store->update('owner', $chat) : $store->create($chat);
    }

    private function twiml(): Response
    {
        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200, ['Content-Type' => 'text/xml']);
    }
}
