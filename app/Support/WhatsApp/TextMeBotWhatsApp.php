<?php

namespace App\Support\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WhatsApp por TextMeBot (servicio no oficial que vincula un número de WhatsApp).
 * Envía con api.textmebot.com/send.php. TextMeBot no firma sus webhooks: Infocus
 * exige una clave secreta propia en la URL del webhook y solo atiende al dueño.
 * Como no es la API oficial, no aplica la ventana de 24 horas ni hay plantillas.
 */
class TextMeBotWhatsApp extends WhatsAppChannel
{
    public function provider(): string
    {
        return 'textmebot';
    }

    /** @return array{provider:string, enabled:bool, owner:string, user_id:string, apikey:string, webhook_key:string} */
    public function settings(): array
    {
        $s = $this->stored();

        return $this->baseSettings($s) + [
            'apikey' => $this->decrypt((string) ($s['whatsapp_textmebot_apikey'] ?? '')),
            'webhook_key' => (string) ($s['whatsapp_textmebot_webhook_key'] ?? ''),
        ];
    }

    public function configured(?array $settings = null): bool
    {
        $s = $settings ?? $this->settings();

        return $s['apikey'] !== '' && $s['webhook_key'] !== '' && $s['owner'] !== '';
    }

    /** Sin ventana de 24 horas: los avisos programados salen como texto normal. */
    public function hasTemplate(array $settings): bool
    {
        return true;
    }

    public function sendTemplate(string $to, string $text, ?array $settings = null): ?string
    {
        return $this->send($to, self::toWhatsAppText($text), $settings);
    }

    public function needsWindow(): bool
    {
        return false;
    }

    /** Imágenes con file=, documentos con document= y filename=, como en su documentación. */
    public function sendFile(string $to, string $url, string $name, string $mime, string $caption, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar TextMeBot (API key y tu número).';
        }
        $params = ['recipient' => self::normalizeNumber($to), 'apikey' => $s['apikey'], 'json' => 'yes'];
        $params += str_starts_with($mime, 'image/')
            ? ['text' => $caption !== '' ? $caption : $name, 'file' => $url]
            : ['document' => $url, 'filename' => $name, 'text' => $caption];

        return $this->request($params);
    }

    public function send(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar TextMeBot (API key y tu número).';
        }

        foreach (self::chunks($text) as $chunk) {
            // Como en su ejemplo: con el + y el indicativo del país.
            $error = $this->request(['recipient' => self::normalizeNumber($to), 'apikey' => $s['apikey'], 'text' => $chunk, 'json' => 'yes']);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * TextMeBot no documenta de dónde vienen sus archivos: solo se descargan por https
     * y nunca desde direcciones internas o privadas.
     *
     * @param  array{url:string, mime:string}  $media
     */
    public function download(array $media, array $settings): ?array
    {
        $url = (string) ($media['url'] ?? '');
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || $host === '' || !self::publicHost($host)) {
            return null;
        }
        try {
            $response = Http::timeout(30)->withOptions(['allow_redirects' => false])->get($url);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0])) ?: (string) ($media['mime'] ?? '');

        return $response->successful() ? ['contents' => $response->body(), 'mime' => $mime, 'name' => basename((string) parse_url($url, PHP_URL_PATH))] : null;
    }

    public static function publicHost(string $host): bool
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if ($ips === []) {
            return false;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function request(array $params): ?string
    {
        try {
            $response = Http::timeout(30)->get('https://api.textmebot.com/send.php', $params);
        } catch (Throwable $e) {
            report($e);

            return 'No se pudo conectar con TextMeBot.';
        }

        return $this->errorFrom($response->status(), $response->body());
    }

    /** Su API no documenta las respuestas: un estado no 2xx o un texto con "error" se toma como fallo. */
    private function errorFrom(int $status, string $body): ?string
    {
        $json = json_decode($body, true);
        $text = is_array($json) ? (string) ($json['message'] ?? $json['error'] ?? $json['status'] ?? '') : $body;
        if ($status < 200 || $status >= 300 || (is_array($json) && isset($json['error'])) || stripos($text, 'error') !== false) {
            return 'TextMeBot respondió: ' . mb_substr(trim(strip_tags($text)) ?: (string) $status, 0, 200);
        }

        return null;
    }
}
