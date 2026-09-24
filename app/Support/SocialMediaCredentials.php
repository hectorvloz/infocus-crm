<?php

namespace App\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Crypt;

class SocialMediaCredentials
{
    public static function metaAppId(): ?string
    {
        return self::plain('services.meta.app_id', 'meta_app_id');
    }

    public static function metaAppSecret(): ?string
    {
        return self::secret('services.meta.app_secret', 'meta_app_secret');
    }

    public static function metaRedirectUri(): ?string
    {
        return self::plain('services.meta.redirect', 'meta_redirect_uri');
    }

    public static function metaGraphVersion(): string
    {
        return self::plain('services.meta.graph_version', 'meta_graph_version') ?: 'v20.0';
    }

    public static function tiktokClientKey(): ?string
    {
        return self::plain('services.tiktok.client_key', 'tiktok_client_key');
    }

    public static function tiktokClientSecret(): ?string
    {
        return self::secret('services.tiktok.client_secret', 'tiktok_client_secret');
    }

    public static function tiktokRedirectUri(): ?string
    {
        return self::plain('services.tiktok.redirect', 'tiktok_redirect_uri');
    }

    public static function metaConfigured(): bool
    {
        return filled(self::metaAppId()) && filled(self::metaAppSecret());
    }

    public static function tiktokConfigured(): bool
    {
        return filled(self::tiktokClientKey()) && filled(self::tiktokClientSecret());
    }

    public static function status(): array
    {
        return [
            'meta' => [
                'configured' => self::metaConfigured(),
                'app_id_preview' => self::preview(self::metaAppId()),
                'app_secret_preview' => self::preview(self::metaAppSecret()),
                'redirect_uri' => self::metaRedirectUri(),
                'graph_version' => self::metaGraphVersion(),
                'source' => [
                    'app_id' => filled(config('services.meta.app_id')) ? '.env' : (filled(self::setting('meta_app_id')) ? 'Ajustes' : null),
                    'app_secret' => filled(config('services.meta.app_secret')) ? '.env' : (filled(self::setting('meta_app_secret')) ? 'Ajustes' : null),
                ],
            ],
            'tiktok' => [
                'configured' => self::tiktokConfigured(),
                'client_key_preview' => self::preview(self::tiktokClientKey()),
                'client_secret_preview' => self::preview(self::tiktokClientSecret()),
                'redirect_uri' => self::tiktokRedirectUri(),
                'source' => [
                    'client_key' => filled(config('services.tiktok.client_key')) ? '.env' : (filled(self::setting('tiktok_client_key')) ? 'Ajustes' : null),
                    'client_secret' => filled(config('services.tiktok.client_secret')) ? '.env' : (filled(self::setting('tiktok_client_secret')) ? 'Ajustes' : null),
                ],
            ],
        ];
    }

    private static function plain(string $configKey, string $settingsKey): ?string
    {
        $value = config($configKey);
        if (filled($value)) {
            return (string) $value;
        }

        $setting = self::setting($settingsKey);
        return filled($setting) ? (string) $setting : null;
    }

    private static function secret(string $configKey, string $settingsKey): ?string
    {
        $value = config($configKey);
        if (filled($value)) {
            return (string) $value;
        }

        $setting = self::setting($settingsKey);
        if (!filled($setting)) {
            return null;
        }

        $setting = (string) $setting;
        if (!str_starts_with($setting, 'ENC:')) {
            return $setting;
        }

        try {
            return Crypt::decryptString(substr($setting, 4));
        } catch (\Throwable) {
            return null;
        }
    }

    private static function setting(string $key): mixed
    {
        static $settings = null;
        if ($settings === null) {
            $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        }

        return $settings[$key] ?? null;
    }

    private static function preview(?string $value): string
    {
        if (!filled($value)) {
            return 'Sin configurar';
        }

        $value = (string) $value;
        if (strlen($value) <= 8) {
            return '••••' . substr($value, -2);
        }

        return substr($value, 0, 4) . '••••' . substr($value, -4);
    }
}
