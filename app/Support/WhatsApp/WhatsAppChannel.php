<?php

namespace App\Support\WhatsApp;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Proveedor de WhatsApp para el asistente (Twilio o Meta Cloud API).
 * Todos comparten: número del dueño, usuario con el que responde, formato del
 * texto y troceo de mensajes largos. Cada proveedor envía y descarga a su manera.
 */
abstract class WhatsAppChannel
{
    public const PROVIDERS = ['twilio' => 'Twilio', 'meta' => 'Meta (WhatsApp Cloud API)', 'textmebot' => 'TextMeBot (no oficial)'];

    private const CHUNK = 1500;

    /** Proveedor elegido en Configuración > IA > WhatsApp. */
    public static function current(): self
    {
        $s = (new FileStore('settings.json'))->find('settings') ?: [];

        return match ($s['whatsapp_provider'] ?? 'twilio') {
            'meta' => new MetaWhatsApp(),
            'textmebot' => new TextMeBotWhatsApp(),
            default => new TwilioWhatsApp(),
        };
    }

    abstract public function provider(): string;

    /** @return array<string, mixed> incluye siempre provider, enabled, owner y user_id */
    abstract public function settings(): array;

    abstract public function configured(?array $settings = null): bool;

    /** Envía texto libre (dentro de las 24 horas). Devuelve el error o null si salió. */
    abstract public function send(string $to, string $text, ?array $settings = null): ?string;

    /** Envía la plantilla aprobada con el aviso en su variable. Devuelve el error o null. */
    abstract public function sendTemplate(string $to, string $text, ?array $settings = null): ?string;

    /** Tiene plantilla configurada para escribir fuera de las 24 horas. */
    abstract public function hasTemplate(array $settings): bool;

    /**
     * Descarga un archivo recibido.
     *
     * @return array{contents:string, mime:string, name:string}|null
     */
    abstract public function download(array $media, array $settings): ?array;

    /** @return array{provider:string, enabled:bool, owner:string, user_id:string} */
    protected function baseSettings(array $s): array
    {
        return [
            'provider' => $this->provider(),
            'enabled' => (bool) ($s['whatsapp_enabled'] ?? false),
            'owner' => self::normalizeNumber((string) ($s['whatsapp_owner'] ?? '')),
            'user_id' => (string) ($s['whatsapp_user_id'] ?? ''),
        ];
    }

    protected function stored(): array
    {
        return (new FileStore('settings.json'))->find('settings') ?: [];
    }

    protected function decrypt(string $value): string
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

    /** Texto de una línea para la variable de una plantilla. */
    protected static function templateLine(string $text): string
    {
        $line = trim(preg_replace('/\s+/u', ' ', strip_tags(self::toWhatsAppText($text))) ?? '');

        return mb_strlen($line) > 900 ? mb_substr($line, 0, 897) . '...' : $line;
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
}
