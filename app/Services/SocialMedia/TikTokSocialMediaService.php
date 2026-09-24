<?php

namespace App\Services\SocialMedia;

use App\Support\SocialMediaCredentials;

class TikTokSocialMediaService
{
    public function isConfigured(): bool
    {
        return SocialMediaCredentials::tiktokConfigured();
    }

    public function status(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'state' => $this->isConfigured() ? 'ready_for_oauth' : 'missing_credentials',
            'message' => $this->isConfigured()
                ? 'TikTok está preparado para implementar OAuth cuando la app tenga scopes aprobados.'
                : 'Configura TIKTOK_CLIENT_KEY y TIKTOK_CLIENT_SECRET para preparar la conexión.',
        ];
    }

    public function authUrl(string $state, string $redirectUri): string
    {
        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query([
            'client_key' => SocialMediaCredentials::tiktokClientKey(),
            'scope' => 'user.info.basic,video.list',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }
}
