<?php

namespace App\Support\Ai;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\TemplateMail;
use App\Support\WhatsApp\WhatsAppChannel;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Entrega lo que el asistente hace solo (avisos y consultas programadas).
 * WhatsApp: texto libre si el dueño escribió en las últimas 24 horas; si no, la
 * plantilla aprobada de Twilio (si está configurada). Si WhatsApp no sirve, correo.
 */
class AssistantNotifier
{
    public function __construct(private readonly ?WhatsAppChannel $whatsapp = null)
    {
    }

    /** @return string canal usado: whatsapp, whatsapp_plantilla, correo o error: ... */
    public function deliver(User $user, string $channel, string $subject, string $text): string
    {
        if ($channel === 'whatsapp') {
            $result = $this->viaWhatsApp($user, $text);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->viaEmail($user, $subject, $text);
    }

    private function viaWhatsApp(User $user, string $text): ?string
    {
        $channel = $this->whatsapp ?? WhatsAppChannel::current();
        $settings = $channel->settings();
        if (!$settings['enabled'] || !$channel->configured($settings) || $settings['user_id'] !== (string) $user->id) {
            return null;
        }

        $chatStore = new FileStore('whatsapp_chats.json');
        $chat = $chatStore->find('owner') ?: ['id' => 'owner', 'messages' => [], 'seen' => []];
        $lastInbound = !empty($chat['last_inbound_at']) ? CarbonImmutable::parse($chat['last_inbound_at']) : null;
        $windowOpen = $lastInbound && $lastInbound->greaterThan(now()->subHours(23)->subMinutes(50));

        $error = $windowOpen
            ? $channel->send($settings['owner'], WhatsAppChannel::toWhatsAppText($text), $settings)
            : $channel->sendTemplate($settings['owner'], $text, $settings);
        if ($error !== null) {
            if ($windowOpen || $channel->hasTemplate($settings)) {
                report(new \RuntimeException('Aviso por WhatsApp: ' . $error));
            }

            return null;
        }

        // Queda en el historial para que el dueño pueda responder sobre el aviso.
        $chat['messages'] = array_values(array_slice([...((array) ($chat['messages'] ?? [])), [
            'role' => 'assistant', 'content' => $text, 'created_at' => now()->toISOString(), 'programado' => true,
        ]], -20));
        $chatStore->find('owner') ? $chatStore->update('owner', $chat) : $chatStore->create($chat);

        return $windowOpen ? 'whatsapp' : 'whatsapp_plantilla';
    }

    private function viaEmail(User $user, string $subject, string $text): string
    {
        if (trim((string) $user->email) === '') {
            return 'error: el usuario no tiene correo';
        }
        try {
            $html = nl2br(e($text));
            TemplateMail::send((string) $user->email, $subject, $html);
        } catch (Throwable $e) {
            report($e);

            return 'error: no se pudo enviar el correo';
        }

        return 'correo';
    }
}
