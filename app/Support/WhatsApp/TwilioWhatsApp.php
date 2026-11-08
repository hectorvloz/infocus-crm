<?php

namespace App\Support\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WhatsApp por Twilio: envía con la API de mensajes, valida X-Twilio-Signature
 * y descarga los archivos solo desde api.twilio.com.
 */
class TwilioWhatsApp extends WhatsAppChannel
{
    public function provider(): string
    {
        return 'twilio';
    }

    /** @return array{provider:string, enabled:bool, owner:string, user_id:string, sid:string, token:string, from:string, template_sid:string} */
    public function settings(): array
    {
        $s = $this->stored();

        return $this->baseSettings($s) + [
            'sid' => trim((string) ($s['whatsapp_twilio_sid'] ?? '')),
            'token' => $this->decrypt((string) ($s['whatsapp_twilio_token'] ?? '')),
            'from' => self::normalizeNumber((string) ($s['whatsapp_from'] ?? '')),
            'template_sid' => trim((string) ($s['whatsapp_template_sid'] ?? '')),
        ];
    }

    public function configured(?array $settings = null): bool
    {
        $s = $settings ?? $this->settings();

        return $s['sid'] !== '' && $s['token'] !== '' && $s['from'] !== '' && $s['owner'] !== '';
    }

    public function hasTemplate(array $settings): bool
    {
        return ($settings['template_sid'] ?? '') !== '';
    }

    public function send(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar Twilio (SID, token y números).';
        }

        foreach (self::chunks($text) as $chunk) {
            $error = $this->post($s, ['To' => 'whatsapp:' . self::normalizeNumber($to), 'Body' => $chunk]);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    public function sendFile(string $to, string $url, string $name, string $mime, string $caption, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar Twilio (SID, token y números).';
        }

        return $this->post($s, ['To' => 'whatsapp:' . self::normalizeNumber($to), 'Body' => $caption !== '' ? $caption : $name, 'MediaUrl' => $url]);
    }

    /** Plantilla de Twilio (Content SID HX...) con una variable {{1}}. */
    public function sendTemplate(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s) || !$this->hasTemplate($s)) {
            return 'No hay plantilla de WhatsApp configurada.';
        }

        return $this->post($s, [
            'To' => 'whatsapp:' . self::normalizeNumber($to),
            'ContentSid' => $s['template_sid'],
            'ContentVariables' => json_encode(['1' => self::templateLine($text)], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** @param  array{url:string, mime:string}  $media */
    public function download(array $media, array $settings): ?array
    {
        $url = (string) ($media['url'] ?? '');
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'api.twilio.com') {
            return null;
        }
        try {
            $response = Http::withBasicAuth($settings['sid'], $settings['token'])->timeout(30)->get($url);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $response->successful() ? ['contents' => $response->body(), 'mime' => (string) ($media['mime'] ?? ''), 'name' => ''] : null;
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

    private function post(array $s, array $fields): ?string
    {
        try {
            $response = Http::asForm()
                ->withBasicAuth($s['sid'], $s['token'])
                ->timeout(20)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$s['sid']}/Messages.json", ['From' => 'whatsapp:' . $s['from']] + $fields);
        } catch (Throwable $e) {
            report($e);

            return 'No se pudo conectar con Twilio.';
        }

        return $response->successful() ? null : (string) (data_get($response->json(), 'message') ?: 'Twilio respondió ' . $response->status());
    }
}
