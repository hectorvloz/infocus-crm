<?php

namespace App\Support\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * WhatsApp por Meta (WhatsApp Cloud API, Graph API): envía desde el Phone Number ID,
 * valida X-Hub-Signature-256 con el App Secret y descarga los archivos con el token.
 */
class MetaWhatsApp extends WhatsAppChannel
{
    public const DEFAULT_VERSION = 'v23.0';

    private const GRAPH = 'https://graph.facebook.com';

    public function provider(): string
    {
        return 'meta';
    }

    /** @return array{provider:string, enabled:bool, owner:string, user_id:string, phone_id:string, token:string, app_secret:string, verify_token:string, version:string, template_name:string, template_lang:string} */
    public function settings(): array
    {
        $s = $this->stored();
        $version = trim((string) ($s['whatsapp_meta_version'] ?? ''));

        return $this->baseSettings($s) + [
            'phone_id' => trim((string) ($s['whatsapp_meta_phone_id'] ?? '')),
            'token' => $this->decrypt((string) ($s['whatsapp_meta_token'] ?? '')),
            'app_secret' => $this->decrypt((string) ($s['whatsapp_meta_app_secret'] ?? '')),
            'verify_token' => trim((string) ($s['whatsapp_meta_verify_token'] ?? '')),
            'version' => preg_match('/^v\d{1,3}\.\d$/', $version) ? $version : self::DEFAULT_VERSION,
            'template_name' => trim((string) ($s['whatsapp_meta_template_name'] ?? '')),
            'template_lang' => trim((string) ($s['whatsapp_meta_template_lang'] ?? '')) ?: 'es',
        ];
    }

    public function configured(?array $settings = null): bool
    {
        $s = $settings ?? $this->settings();

        return $s['phone_id'] !== '' && $s['token'] !== '' && $s['app_secret'] !== '' && $s['owner'] !== '';
    }

    public function hasTemplate(array $settings): bool
    {
        return ($settings['template_name'] ?? '') !== '';
    }

    public function send(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s)) {
            return 'Falta configurar Meta (Phone Number ID, token, App Secret y tu número).';
        }

        foreach (self::chunks($text) as $chunk) {
            $error = $this->post($s, $to, ['type' => 'text', 'text' => ['body' => $chunk, 'preview_url' => false]]);
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
            return 'Falta configurar Meta (Phone Number ID, token, App Secret y tu número).';
        }
        $media = str_starts_with($mime, 'image/')
            ? ['type' => 'image', 'image' => ['link' => $url, 'caption' => $caption]]
            : ['type' => 'document', 'document' => ['link' => $url, 'filename' => $name, 'caption' => $caption]];

        return $this->post($s, $to, $media);
    }

    /** Plantilla aprobada en WhatsApp Manager con una variable {{1}} en el cuerpo. */
    public function sendTemplate(string $to, string $text, ?array $settings = null): ?string
    {
        $s = $settings ?? $this->settings();
        if (!$this->configured($s) || !$this->hasTemplate($s)) {
            return 'No hay plantilla de WhatsApp configurada.';
        }

        return $this->post($s, $to, ['type' => 'template', 'template' => [
            'name' => $s['template_name'],
            'language' => ['code' => $s['template_lang']],
            'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => self::templateLine($text)]]]],
        ]]);
    }

    /**
     * Los archivos de Meta llegan con un id: primero se pide su URL y luego se descarga,
     * siempre con el token y solo desde dominios de Meta.
     *
     * @param  array{id:string, mime:string, name?:string}  $media
     */
    public function download(array $media, array $settings): ?array
    {
        $id = (string) ($media['id'] ?? '');
        if (!preg_match('/^\d{1,40}$/', $id)) {
            return null;
        }
        try {
            $info = Http::withToken($settings['token'])->timeout(20)->get(self::GRAPH . "/{$settings['version']}/{$id}");
            $url = (string) data_get($info->json(), 'url', '');
            $host = (string) parse_url($url, PHP_URL_HOST);
            if (!$info->successful() || parse_url($url, PHP_URL_SCHEME) !== 'https' || !($host === 'lookaside.fbsbx.com' || str_ends_with($host, '.fbsbx.com') || str_ends_with($host, '.facebook.com') || str_ends_with($host, '.whatsapp.net'))) {
                return null;
            }
            $file = Http::withToken($settings['token'])->timeout(30)->get($url);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $file->successful()
            ? ['contents' => $file->body(), 'mime' => (string) (data_get($info->json(), 'mime_type') ?: ($media['mime'] ?? '')), 'name' => (string) ($media['name'] ?? '')]
            : null;
    }

    /** X-Hub-Signature-256: "sha256=" + HMAC-SHA256 del cuerpo crudo con el App Secret. */
    public static function validSignature(string $appSecret, string $rawBody, string $header): bool
    {
        if ($appSecret === '' || !str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $appSecret), substr($header, 7));
    }

    private function post(array $s, string $to, array $message): ?string
    {
        try {
            $response = Http::withToken($s['token'])
                ->timeout(20)
                ->post(self::GRAPH . "/{$s['version']}/{$s['phone_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => ltrim(self::normalizeNumber($to), '+'),
                ] + $message);
        } catch (Throwable $e) {
            report($e);

            return 'No se pudo conectar con Meta.';
        }

        return $response->successful() ? null : (string) (data_get($response->json(), 'error.message') ?: 'Meta respondió ' . $response->status());
    }
}
