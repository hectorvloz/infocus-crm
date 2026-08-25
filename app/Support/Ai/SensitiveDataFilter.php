<?php

namespace App\Support\Ai;

class SensitiveDataFilter
{
    private const BLOCKED_KEYS = [
        'password',
        'passwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'client_secret',
        'smtp_password',
        'google_client_secret',
        'stripe_secret',
        'paypal_secret',
        'wompi_integrity_secret',
        'wompi_event_secret',
    ];

    public function cleanText(?string $text): string
    {
        $text = (string) $text;

        $patterns = [
            '/(sk-[A-Za-z0-9_\-]{20,})/' => '[API_KEY_OCULTA]',
            '/(AIza[0-9A-Za-z_\-]{20,})/' => '[API_KEY_OCULTA]',
            '/(GOCSPX-[A-Za-z0-9_\-]+)/' => '[SECRETO_GOOGLE_OCULTO]',
            '/((password|passwd|secret|token|api[_-]?key)\s*[:=]\s*)([^\s"\']+)/i' => '$1[OCULTO]',
            '/([A-Z0-9_]*SECRET[A-Z0-9_]*\s*=\s*)(.+)/i' => '$1[OCULTO]',
            '/([A-Z0-9_]*TOKEN[A-Z0-9_]*\s*=\s*)(.+)/i' => '$1[OCULTO]',
            '/([A-Z0-9_]*PASSWORD[A-Z0-9_]*\s*=\s*)(.+)/i' => '$1[OCULTO]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return trim($text);
    }

    public function cleanArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if ($this->isSensitiveKey($normalizedKey)) {
                $clean[$key] = '[OCULTO]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->cleanArray($value);
                continue;
            }

            if (is_string($value)) {
                $clean[$key] = $this->cleanText($value);
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::BLOCKED_KEYS as $blocked) {
            if (str_contains($key, $blocked)) {
                return true;
            }
        }

        return false;
    }
}
