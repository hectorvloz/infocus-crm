<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiAttachments;
use App\Support\Ai\AiService;
use App\Support\WhatsApp\MetaWhatsApp;
use App\Support\WhatsApp\TextMeBotWhatsApp;
use App\Support\WhatsApp\TwilioWhatsApp;
use App\Support\WhatsApp\WhatsAppChannel;
use App\Support\WhatsApp\WhatsAppEvents;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Recibe los WhatsApp (por Twilio o por Meta, según Configuración > IA > WhatsApp)
 * y los contesta con el asistente de Infocus. Solo atiende al número del dueño y
 * solo si la firma del proveedor es válida. Responde al proveedor de inmediato y
 * trabaja después, porque los dos cortan si la respuesta tarda.
 */
class WhatsAppWebhookController extends Controller
{
    private const HISTORY = 20;

    private const SEEN = 200;

    /** POST /webhooks/whatsapp (Twilio). */
    public function handle(Request $request)
    {
        $channel = WhatsAppChannel::current();
        $settings = $channel->settings();
        if (!$channel instanceof TwilioWhatsApp || !$settings['enabled'] || !$channel->configured($settings)) {
            return response('WhatsApp por Twilio no está activo.', 404);
        }

        $signature = (string) $request->header('X-Twilio-Signature', '');
        $params = $request->post();
        $urls = array_unique([$request->fullUrl(), preg_replace('/^http:/', 'https:', $request->fullUrl()), url('/webhooks/whatsapp')]);
        if (!collect($urls)->contains(fn ($url) => TwilioWhatsApp::validSignature($settings['token'], $url, $params, $signature))) {
            WhatsAppEvents::record('twilio', 'rechazado: firma inválida', (string) $request->post('From', ''), 'Revisa que la URL en Twilio sea exactamente ' . url('/webhooks/whatsapp'));

            return response('Firma inválida.', 403);
        }

        $media = [];
        for ($i = 0; $i < min(3, (int) $request->post('NumMedia', 0)); $i++) {
            $media[] = ['url' => (string) $request->post("MediaUrl{$i}", ''), 'mime' => (string) $request->post("MediaContentType{$i}", '')];
        }
        $this->accept($channel, $settings, (string) $request->post('From', ''), trim((string) $request->post('Body', '')), (string) $request->post('MessageSid', ''), $media);

        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200, ['Content-Type' => 'text/xml']);
    }

    /** GET /webhooks/whatsapp/meta: verificación que hace Meta al guardar el webhook. */
    public function metaVerify(Request $request): Response
    {
        $settings = (new MetaWhatsApp())->settings();
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        if ($request->query('hub_mode', $request->query('hub.mode')) !== 'subscribe' || $settings['verify_token'] === '' || !hash_equals($settings['verify_token'], $token)) {
            return response('Token de verificación inválido.', 403);
        }

        return response((string) $request->query('hub_challenge', $request->query('hub.challenge', '')), 200, ['Content-Type' => 'text/plain']);
    }

    /** POST /webhooks/whatsapp/meta (Meta WhatsApp Cloud API). */
    public function meta(Request $request): Response
    {
        $channel = WhatsAppChannel::current();
        $settings = $channel->settings();
        if (!$channel instanceof MetaWhatsApp || !$settings['enabled'] || !$channel->configured($settings)) {
            return response('WhatsApp por Meta no está activo.', 404);
        }
        if (!MetaWhatsApp::validSignature($settings['app_secret'], $request->getContent(), (string) $request->header('X-Hub-Signature-256', ''))) {
            WhatsAppEvents::record('meta', 'rechazado: firma inválida', '', 'Revisa el App Secret guardado en Infocus.');

            return response('Firma inválida.', 403);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                if ((string) data_get($value, 'metadata.phone_number_id', '') !== $settings['phone_id']) {
                    continue;
                }
                // Los avisos de entregado o leído también llegan aquí: solo interesan los mensajes.
                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $type = (string) ($message['type'] ?? '');
                    $file = in_array($type, ['image', 'document', 'audio', 'video', 'sticker'], true) ? (array) ($message[$type] ?? []) : [];
                    $body = $type === 'text' ? (string) data_get($message, 'text.body', '') : (string) ($file['caption'] ?? '');
                    $media = $file !== [] ? [['id' => (string) ($file['id'] ?? ''), 'mime' => (string) ($file['mime_type'] ?? ''), 'name' => (string) ($file['filename'] ?? '')]] : [];
                    $this->accept($channel, $settings, (string) ($message['from'] ?? ''), trim($body), (string) ($message['id'] ?? ''), $media);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * POST /webhooks/whatsapp/textmebot/{key} (TextMeBot). TextMeBot no firma sus envíos:
     * la clave secreta de la URL, que genera Infocus, es la que autentica.
     */
    public function textmebot(Request $request, string $key): Response
    {
        $channel = WhatsAppChannel::current();
        $settings = $channel->settings();
        if (!$channel instanceof TextMeBotWhatsApp || !$settings['enabled'] || !$channel->configured($settings)) {
            WhatsAppEvents::record('textmebot', 'rechazado: TextMeBot no está activo en Infocus', '', 'Revisa que el proveedor sea TextMeBot, que WhatsApp esté activado y que tengan API key y tu número.');

            return response('WhatsApp por TextMeBot no está activo.', 404);
        }
        // Su ejemplo lee el JSON del cuerpo crudo: no siempre manda Content-Type de JSON.
        $data = $request->all() ?: (json_decode($request->getContent(), true) ?: []);
        if (!hash_equals($settings['webhook_key'], $key)) {
            WhatsAppEvents::record('textmebot', 'rechazado: clave de la URL inválida', (string) ($data['from'] ?? ''), 'La URL del webhook en TextMeBot no coincide con la de Infocus. Cópiala de nuevo.');

            return response('Clave inválida.', 403);
        }
        WhatsAppEvents::record('textmebot', 'llegó', (string) ($data['from'] ?? ''), 'Campos: ' . implode(', ', array_keys((array) $data)) . ' · tipo: ' . (string) ($data['type'] ?? '?'));
        $type = (string) ($data['type'] ?? 'text');
        $text = trim((string) ($data['message'] ?? ''));
        $file = (string) ($data['file'] ?? '');
        $media = ($type !== 'text' && $file !== '' && $file !== 'null') ? [['url' => $file, 'mime' => '']] : [];
        // Sin id de mensaje: un reenvío con el mismo contenido en 2 minutos se ignora.
        $id = 'tmb:' . sha1(implode('|', [$data['from'] ?? '', $type, $text, $file, intdiv(time(), 120)]));
        $this->accept($channel, $settings, (string) ($data['from'] ?? ''), $type === 'text' || $media !== [] ? $text : '', $id, $media);

        return response('OK', 200);
    }

    private function accept(WhatsAppChannel $channel, array $settings, string $from, string $body, string $messageId, array $media): void
    {
        if (WhatsAppChannel::normalizeNumber($from) !== $settings['owner']) {
            WhatsAppEvents::record($channel->provider(), 'ignorado: no es tu número', $from, 'En Infocus está guardado ' . $settings['owner'] . ' y llegó ' . (WhatsAppChannel::normalizeNumber($from) ?: '(vacío)') . '.');

            return;
        }
        if ($messageId === '' || !$this->markSeen($messageId)) {
            WhatsAppEvents::record($channel->provider(), 'ignorado: repetido', $from);

            return;
        }
        WhatsAppEvents::record($channel->provider(), 'aceptado', $from, $media !== [] ? count($media) . ' archivo(s)' : '');

        $done = false;
        app()->terminating(function () use (&$done, $channel, $settings, $body, $media) {
            if ($done) {
                return;
            }
            $done = true;
            $this->answer($channel, $settings, $body, $media);
        });
    }

    private function answer(WhatsAppChannel $channel, array $settings, string $body, array $media): void
    {
        ignore_user_abort(true);
        @set_time_limit(180);

        $owner = $settings['owner'];
        if ($body === '' && $media === []) {
            $channel->send($owner, 'No recibí texto.', $settings);

            return;
        }

        $user = User::find($settings['user_id']);
        if (!$user) {
            WhatsAppEvents::record($channel->provider(), 'error: falta el usuario', '');
            $channel->send($owner, 'Falta elegir en Infocus el usuario con el que respondo (Configuración > IA > WhatsApp).', $settings);

            return;
        }

        $aiSettings = (new FileStore('settings.json'))->find('settings') ?: [];
        if (empty($aiSettings['ai_tools_enabled'])) {
            WhatsAppEvents::record($channel->provider(), 'error: asistente con herramientas apagado', '');
            $channel->send($owner, 'Activa "Asistente con herramientas" en Configuración > IA para que pueda trabajar desde WhatsApp.', $settings);

            return;
        }

        Auth::setUser($user);
        [$attachments, $skipped] = $this->downloadMedia($channel, $media, $settings);
        if ($attachments === [] && $body === '') {
            $channel->send($owner, $skipped > 0 ? 'Por ahora leo texto, fotos y PDF (hasta 10 MB). Ese archivo no lo pude abrir.' : 'No recibí texto.', $settings);

            return;
        }
        $visible = array_values(array_filter($attachments, fn ($a) => AiAttachments::isImage($a) || $a['mime'] === 'application/pdf'));
        $prompt = $body !== '' ? $body : 'Te envío este archivo sin texto. Dime qué es y qué puedo hacer con él en Infocus.';
        $store = new FileStore('whatsapp_chats.json');
        $chat = $store->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $history = array_values((array) ($chat['messages'] ?? []));

        try {
            $result = app(AiService::class)->reply($prompt, $history, ['channel' => 'whatsapp', 'attachments' => $attachments, 'attachments_from' => 'WhatsApp'], $visible);
            $content = (string) ($result['content'] ?? '');
        } catch (Throwable $e) {
            report($e);
            WhatsAppEvents::record($channel->provider(), 'error de la IA', '', $e->getMessage());
            $content = 'No pude procesar tu mensaje. Intenta de nuevo en un momento.';
            $result = [];
        }

        if ($skipped > 0) {
            $content .= "\n\n(Uno de los archivos no lo pude abrir: por ahora leo fotos y PDF de hasta 10 MB.)";
        }
        $error = $channel->send($owner, WhatsAppChannel::toWhatsAppText($content), $settings);
        if ($error !== null) {
            report(new \RuntimeException('WhatsApp: ' . $error));
        }
        WhatsAppEvents::record($channel->provider(), $error === null ? 'respondido' : 'error al responder', '', $error ?? implode(', ', (array) ($result['tools_used'] ?? [])));

        $chat = $store->find('owner') ?: $chat;
        $chat['messages'] = array_values(array_slice([
            ...$history,
            ['role' => 'user', 'content' => $prompt, 'created_at' => now()->toISOString(), 'attachments' => $attachments],
            ['role' => 'assistant', 'content' => $content, 'created_at' => now()->toISOString(), 'tools_used' => $result['tools_used'] ?? []],
        ], -self::HISTORY));
        $this->saveChat($store, $chat);
    }

    /**
     * Descarga los archivos del mensaje con el proveedor y los guarda como adjuntos
     * del usuario. Devuelve [adjuntos, cantidad que no se pudo].
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function downloadMedia(WhatsAppChannel $channel, array $media, array $settings): array
    {
        $saved = [];
        $skipped = 0;
        foreach ($media as $index => $item) {
            $mime = strtolower(trim(explode(';', (string) ($item['mime'] ?? ''))[0]));
            // Si el proveedor no dice el tipo (TextMeBot), se toma el del archivo descargado.
            $file = ($mime === '' || isset(AiAttachments::MIMES[$mime])) ? $channel->download($item, $settings) : null;
            $mime = $file ? (strtolower(trim(explode(';', (string) $file['mime'])[0])) ?: $mime) : $mime;
            $record = $file && isset(AiAttachments::MIMES[$mime])
                ? (new AiAttachments())->save($file['contents'], $file['name'] !== '' ? $file['name'] : 'whatsapp-' . now()->format('Ymd-His') . '-' . ($index + 1) . '.' . AiAttachments::MIMES[$mime], $mime, 'whatsapp')
                : null;
            if ($record) {
                $saved[] = $record;
            } else {
                $skipped++;
            }
        }

        return [$saved, $skipped];
    }

    /** Los proveedores reintentan si no responden a tiempo: un mismo mensaje se atiende una sola vez. */
    private function markSeen(string $messageId): bool
    {
        $store = new FileStore('whatsapp_chats.json');
        $chat = $store->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $seen = array_values((array) ($chat['seen'] ?? []));
        if (in_array($messageId, $seen, true)) {
            return false;
        }
        $chat['seen'] = array_slice([...$seen, $messageId], -self::SEEN);
        // Abre la ventana de 24 horas en la que se puede escribir sin plantilla.
        $chat['last_inbound_at'] = now()->toISOString();
        $this->saveChat($store, $chat);

        return true;
    }

    private function saveChat(FileStore $store, array $chat): void
    {
        $store->find('owner') ? $store->update('owner', $chat) : $store->create($chat);
    }
}
