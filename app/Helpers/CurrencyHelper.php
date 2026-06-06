<?php

if (!function_exists('currency_symbol')) {
    function currency_symbol($currencyCode = null) {
        // If a specific currency code is provided (e.g. from an invoice), use it directly.
        // Otherwise, fallback to the system base currency.
        if (!$currencyCode) {
            try {
                $store = new \App\Repositories\FileStore('settings.json');
                $settings = $store->find('settings');
                $currencyCode = $settings['base_currency'] ?? 'USD';
            } catch (\Exception $e) {
                $currencyCode = 'USD';
            }
        }

        $symbols = [
            'USD' => '$',
            'MXN' => '$',
            'COP' => '$',
            'ARS' => '$',
            'CLP' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'RUB' => '₽',
            'KRW' => '₩',
            'BRL' => 'R$',
            'PEN' => 'S/',
            'TRY' => '₺',
            'CAD' => '$',
            'AUD' => '$',
            'NZD' => '$',
            'SGD' => '$',
            'HKD' => '$',
            'CHF' => 'Fr',
            'SEK' => 'kr',
            'ZAR' => 'R',
        ];

        return $symbols[strtoupper($currencyCode)] ?? '$';
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = null) {
        $symbol = currency_symbol($currency);
        $decimals = 2;
        try {
            $store = new \App\Repositories\FileStore('settings.json');
            $settings = $store->find('settings') ?: [];
            if (!empty($settings['show_decimals'])) {
                $decimals = isset($settings['decimal_places']) ? (int) $settings['decimal_places'] : 2;
            } else {
                $decimals = 0;
            }
        } catch (\Exception $e) {
            $decimals = 2;
        }
        return $symbol . number_format($amount, $decimals, ',', '.');
    }
}

if (!function_exists('format_compact_number')) {
    function format_compact_number($n, ?int $forcedDecimals = null) {
        $n = (float) $n;
        $abs = abs($n);

        $formatShort = function (float $value) use ($forcedDecimals): string {
            $decimals = $forcedDecimals !== null
                ? max(0, min(2, (int) $forcedDecimals))
                : (($value < 10 && floor($value) !== $value) ? 1 : 0);
            $factor = 10 ** $decimals;
            $truncated = floor($value * $factor) / $factor;
            return rtrim(rtrim(number_format($truncated, $decimals, '.', ''), '0'), '.');
        };

        if ($abs >= 1000000000) {
            $formatted = $formatShort($abs / 1000000000);
            return ($n < 0 ? '-' : '') . $formatted . 'B';
        }
        if ($abs >= 1000000) {
            $formatted = $formatShort($abs / 1000000);
            return ($n < 0 ? '-' : '') . $formatted . 'M';
        }
        if ($abs >= 1000) {
            $formatted = $formatShort($abs / 1000);
            return ($n < 0 ? '-' : '') . $formatted . 'K';
        }

        return (string) (int) round($n);
    }
}

if (!function_exists('app_public_file_path')) {
    function app_public_file_path(?string $path): ?string {
        $raw = trim((string) $path);
        if ($raw === '' || preg_match('/^https?:\/\//i', $raw)) {
            return null;
        }

        $relative = ltrim(parse_url($raw, PHP_URL_PATH) ?: $raw, '/');
        $candidates = [
            public_path($relative),
            base_path('public/' . $relative),
            base_path($relative),
        ];

        foreach (array_unique($candidates) as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('app_public_asset_url')) {
    function app_public_asset_url(?string $path): string {
        $raw = trim((string) $path);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $raw)) {
            return $raw;
        }

        $relative = ltrim(parse_url($raw, PHP_URL_PATH) ?: $raw, '/');
        return asset($relative);
    }
}

if (!function_exists('app_public_asset_data_uri')) {
    function app_public_asset_data_uri(?string $path): ?string {
        $fullPath = app_public_file_path($path);
        if (!$fullPath) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: null;
        if (!$mime) {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                default => 'image/png',
            };
        }

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fullPath));
    }
}
