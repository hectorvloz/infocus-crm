<?php

namespace App\Support\WhatsApp;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Canal de WhatsApp por Twilio: enviar mensajes, validar la firma de los webhooks
 * y adaptar el texto del asistente al formato de WhatsApp.
 */
class TwilioWhatsApp
{
    private const CHUNK = 1500;

    /** @return array{enabled:bool, sid:string, token:string, from:string, owner:string, user_id:string, template_sid:string} */
    public function settings(): array
    {
        $s = (new FileStore('settings.json'))->find('settings') ?: [];

        return [
            'enabled' => (bool) ($s['whatsapp_enabled'] ?? false),
            'sid' => trim((string) ($s['whatsapp_twilio_sid'] ?? '')),
            'token' => $this->decrypt((string) ($s['whatsapp_twilio_token'] ?? '')),
            'from' => self::normalizeNumber((string) ($s['whatsapp_from'] ?? '')),
            'owner' => self::normalizeNumber((string) ($s['whatsapp_owner'] ?? '')),
            'user_id' => (string) ($s['whatsapp_user_id'] ?? ''),
            'template_sid' => trim((string) ($s['whatsapp_template_sid'] ?? '')),
        ];
    }

    /**
     * Fuera de las 24 horas desde el último mensaje del dueño, WhatsApp solo deja
     * escribir con una plantilla aprobada. La plantilla de Infocus tiene una variable
     * {{1}} con el aviso en una sola línea.
     */
    public function sendTemplate(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s) || $s['template_sid'] === '') {
            return 'No hay plantilla de WhatsApp configurada.';
        }

        $line = trim(preg_replace('/\s+/u', ' ', strip_tags(self::toWhatsAppText($text))) ?? '');
        $line = mb_strlen($line) > 900 ? mb_substr($line, 0, 897) . '...' : $line;

        try {
            $response = Http::asForm()
                ->withBasicAuth($s['sid'], $s['token'])
                ->timeout(20)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$s['sid']}/Messages.json", [
                    'From' => 'whatsapp:' . $s['from'],
                    'To' => 'whatsapp:' . self::normalizeNumber($to),
                    'ContentSid' => $s['template_sid'],
                    'ContentVariables' => json_encode(['1' => $line], JSON_UNESCAPED_UNICODE),
                ]);
        } catch (Throwable $e) {
            report($e);

            return 'No se pudo conectar con Twilio.';
        }

        return $response->successful() ? null : (string) (data_get($response->json(), 'message') ?: 'Twilio respondió ' . $response->status());
    }

    public function configured(?array $settings = null): bool
    {
        $s = $settings ?? $this->settings();

        return $s['sid'] !== '' && $s['token'] !== '' && $s['from'] !== '' && $s['owner'] !== '';
    }

    /** Envía el texto en uno o varios mensajes. Devuelve el error de Twilio o null si salió. */
    public function send(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar Twilio (SID, token y números).';
        }

        try {
            foreach (self::chunks($text) as $chunk) {
                $response = Http::asForm()
                    ->withBasicAuth($s['sid'], $s['token'])
                    ->timeout(20)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$s['sid']}/Messages.json", [
                        'From' => 'whatsapp:' . $s['from'],
                        'To' => 'whatsapp:' . self::normalizeNumber($to),
                        'Body' => $chunk,
                    ]);
                if (!$response->successful()) {
                    return (string) (data_get($response->json(), 'message') ?: 'Twilio respondió ' . $response->status());
                }
            }
        } catch (Throwable $e) {
            report($e);

            return 'No se pudo conectar con Twilio.';
        }

        return null;
    }

    /** Firma X-Twilio-Signature: HMAC-SHA1 de la URL más los parámetros ordenados, en base64. */
    public static function validSignature(string $token, string $url, array $params, string $signature): bool
    {
        if ($token === '' || $signature === '') {
            return false;
        }
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . (is_array($value) ? implode('', $value) : $value);
        }

        return hash_equals(base64_encode(hash_hmac('sha1', $data, $token, true)), $signature);
    }

    public static function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', str_ireplace('whatsapp:', '', $number)) ?? '';

        return $digits === '' ? '' : '+' . $digits;
    }

    /** Markdown del asistente a formato de WhatsApp. */
    public static function toWhatsAppText(string $markdown): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) use ($base) {
            $url = str_starts_with($m[2], '/') ? $base . $m[2] : $m[2];

            return $m[1] . ' (' . $url . ')';
        }, $markdown) ?? $markdown;
        $text = preg_replace('/^#{1,6}\s*(.+)$/mu', '*$1*', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/u', '*$1*', $text) ?? $text;
        $text = preg_replace('/^[ \t]*[-*][ \t]+/mu', '• ', $text) ?? $text;
        $text = preg_replace('/^---+$/mu', '———', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/u', '$1', $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    /** @return array<int, string> */
    public static function chunks(string $text): array
    {
        $text = trim($text);
        if (mb_strlen($text) <= self::CHUNK) {
            return [$text];
        }

        $chunks = [];
        $current = '';
        foreach (preg_split("/(\n\n)/", $text) as $paragraph) {
            $candidate = $current === '' ? $paragraph : $current . "\n\n" . $paragraph;
            if (mb_strlen($candidate) <= self::CHUNK) {
                $current = $candidate;
                continue;
            }
            if ($current !== '') {
                $chunks[] = $current;
            }
            while (mb_strlen($paragraph) > self::CHUNK) {
                $chunks[] = mb_substr($paragraph, 0, self::CHUNK);
                $paragraph = mb_substr($paragraph, self::CHUNK);
            }
            $current = $paragraph;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function decrypt(string $value): string
    {
        if (!str_starts_with($value, 'ENC:')) {
            return $value;
        }
        try {
            return Crypt::decryptString(substr($value, 4));
        } catch (Throwable) {
            return '';
        }
    }
}
