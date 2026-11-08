<?php

namespace App\Support\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
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

        foreach (self::chunks($text, self::CHUNK) as $chunk) {
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

    /** Pausa antes de reintentar: TextMeBot rechaza mensajes enviados muy seguidos. */
    public const RETRY_SECONDS = 7;

    private const ATTEMPTS = 3;

    /** Recomendaciones de TextMeBot para no ser bloqueado: mínimo 5 segundos entre mensajes y nada de ráfagas. */
    public const MIN_GAP_SECONDS = 6;

    public const MAX_PER_HOUR = 30;

    /** WhatsApp admite unos 4.000 caracteres: con TextMeBot se parte menos para mandar menos mensajes. */
    private const CHUNK = 3500;

    private const RATE_FILE = 'whatsapp_textmebot_rate.json';

    /**
     * Espera lo necesario para dejar MIN_GAP_SECONDS desde el último envío y cuenta el
     * mensaje. Si ya se mandaron MAX_PER_HOUR en esta hora, no deja enviar más.
     */
    private function pace(): ?string
    {
        $store = new \App\Repositories\FileStore(self::RATE_FILE);
        $rate = $store->find('rate') ?: ['id' => 'rate', 'hour' => '', 'count' => 0, 'last_at' => null];
        $hour = now()->format('YmdH');
        $count = ($rate['hour'] ?? '') === $hour ? (int) ($rate['count'] ?? 0) : 0;
        if ($count >= self::MAX_PER_HOUR) {
            return 'Tope de seguridad: ya se enviaron ' . self::MAX_PER_HOUR . ' mensajes por TextMeBot en esta hora. Se frena para que WhatsApp no bloquee el número.';
        }

        if (!empty($rate['last_at'])) {
            $wait = self::MIN_GAP_SECONDS - (int) floor(\Illuminate\Support\Carbon::parse($rate['last_at'])->diffInSeconds(now(), true));
            if ($wait > 0) {
                Sleep::for($wait)->seconds();
            }
        }

        $data = ['hour' => $hour, 'count' => $count + 1, 'last_at' => now()->toISOString()];
        $store->find('rate') ? $store->update('rate', $data) : $store->create(['id' => 'rate'] + $data);

        return null;
    }

    private function request(array $params): ?string
    {
        $error = null;
        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            if ($attempt > 1) {
                Sleep::for(self::RETRY_SECONDS)->seconds();
            }
            $blocked = $this->pace();
            if ($blocked !== null) {
                return $blocked;
            }
            try {
                $response = Http::timeout(30)->get('https://api.textmebot.com/send.php', $params);
            } catch (Throwable $e) {
                report($e);
                $error = 'No se pudo conectar con TextMeBot.';
                continue;
            }
            $error = $this->errorFrom($response->status(), $response->body());
            if ($error === null) {
                return null;
            }
        }

        return $error;
    }

    /**
     * Su API no documenta las respuestas: un estado no 2xx o un texto con "error" se
     * toma como fallo, y se devuelve la respuesta completa para ver la causa.
     */
    private function errorFrom(int $status, string $body): ?string
    {
        $json = json_decode($body, true);
        $text = is_array($json) ? (string) ($json['message'] ?? $json['error'] ?? $json['status'] ?? '') : $body;
        $failed = $status < 200 || $status >= 300
            || (is_array($json) && !empty($json['error']))
            || (is_array($json) && in_array(strtolower((string) ($json['status'] ?? '')), ['error', 'failed', 'fail'], true))
            || (!is_array($json) && stripos($body, 'error') !== false);
        if (!$failed) {
            return null;
        }
        $detail = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        return 'TextMeBot respondió (' . $status . '): ' . mb_substr($detail !== '' ? $detail : $text, 0, 250);
    }
}
