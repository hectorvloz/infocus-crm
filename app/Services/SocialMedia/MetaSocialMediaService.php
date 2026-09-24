<?php

namespace App\Services\SocialMedia;

use App\Support\SocialMediaCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaSocialMediaService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Config helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function graphVersion(): string
    {
        $version = SocialMediaCredentials::metaGraphVersion();
        return Str::startsWith($version, 'v') ? $version : 'v' . $version;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OAuth
    // ─────────────────────────────────────────────────────────────────────────

    public function authUrl(string $state, string $redirectUri): string
    {
        $scopes = [
            // Read
            'public_profile',
            'email',
            'pages_show_list',
            'pages_read_engagement',
            'pages_read_user_content',
            'instagram_basic',
            'instagram_manage_insights',
            'ads_read',
            'leads_retrieval',
            // Publish (Meta app must have these approved in App Review for production)
            'pages_manage_posts',
            'pages_manage_metadata',
            'instagram_content_publish',
        ];

        return 'https://www.facebook.com/' . $this->graphVersion() . '/dialog/oauth?' . http_build_query([
            'client_id'     => SocialMediaCredentials::metaAppId(),
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => implode(',', $scopes),
            'state'         => $state,
        ]);
    }

    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::timeout(20)->get($this->graphBaseUrl() . '/oauth/access_token', [
            'client_id'     => SocialMediaCredentials::metaAppId(),
            'client_secret' => SocialMediaCredentials::metaAppSecret(),
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if (!$response->ok()) {
            throw new \RuntimeException($this->errorMessage($response->json()) ?: 'No se pudo conectar con Meta.');
        }

        $payload = $response->json();
        $token   = (string) ($payload['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Meta no devolvió un token válido.');
        }

        return $payload;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLISHING — Facebook Page
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish a post to a Facebook Page feed.
     *
     * @param  string  $pageToken   Page-scoped access token
     * @param  string  $pageId      Facebook Page ID
     * @param  array   $post        Calendar item data
     * @return array                ['external_post_id' => string]
     */
    public function publishToFacebookFeed(string $pageToken, string $pageId, array $post): array
    {
        $contentType = (string) ($post['content_type'] ?? 'post');
        $caption     = (string) ($post['caption'] ?? $post['title'] ?? '');
        $mediaUrl    = $this->resolveMediaUrl($post);

        if ($contentType === 'story' && $mediaUrl !== '') {
            return $this->publishFacebookStory($pageToken, $pageId, $mediaUrl, $caption);
        }

        // Standard photo/link post
        $params = ['message' => $caption];
        if ($mediaUrl !== '') {
            $params['url'] = $mediaUrl;
        }

        $response = Http::timeout(30)
            ->withToken($pageToken)
            ->post($this->graphBaseUrl() . '/' . $pageId . '/feed', $params);

        if (!$response->ok()) {
            throw new \RuntimeException(
                'Facebook feed: ' . ($this->errorMessage($response->json()) ?: 'Error desconocido.')
            );
        }

        return ['external_post_id' => (string) ($response->json()['id'] ?? '')];
    }

    /**
     * Publish a photo story to a Facebook Page.
     * NOTE: Requires pages_manage_posts + pages_manage_metadata.
     */
    public function publishFacebookStory(string $pageToken, string $pageId, string $photoUrl, string $caption = ''): array
    {
        // Step 1: Upload photo container
        $uploadResponse = Http::timeout(30)
            ->withToken($pageToken)
            ->post($this->graphBaseUrl() . '/' . $pageId . '/photos', [
                'url'         => $photoUrl,
                'published'   => false,
                'temporary'   => true,
            ]);

        if (!$uploadResponse->ok()) {
            throw new \RuntimeException(
                'Facebook story upload: ' . ($this->errorMessage($uploadResponse->json()) ?: 'Error al subir foto.')
            );
        }

        $photoId = (string) ($uploadResponse->json()['id'] ?? '');
        if ($photoId === '') {
            throw new \RuntimeException('Facebook story: No se obtuvo ID del contenido subido.');
        }

        // Step 2: Publish as story
        $storyResponse = Http::timeout(30)
            ->withToken($pageToken)
            ->post($this->graphBaseUrl() . '/' . $pageId . '/photo_stories', [
                'photo_id' => $photoId,
            ]);

        if (!$storyResponse->ok()) {
            throw new \RuntimeException(
                'Facebook story publish: ' . ($this->errorMessage($storyResponse->json()) ?: 'Error al publicar historia.')
            );
        }

        return ['external_post_id' => (string) ($storyResponse->json()['id'] ?? $photoId)];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLISHING — Instagram
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Publish to Instagram (post, reel, or story).
     * Uses the 2-step Instagram Content Publishing API:
     *   1. Create media container → returns container_id
     *   2. Publish container      → returns media_id
     *
     * @param  string  $pageToken  Page-scoped access token (same page that owns the IG account)
     * @param  string  $igId       Instagram Business Account ID
     * @param  array   $post       Calendar item data
     * @return array               ['external_post_id' => string]
     */
    public function publishToInstagram(string $pageToken, string $igId, array $post): array
    {
        $contentType = (string) ($post['content_type'] ?? 'post');
        $caption     = (string) ($post['caption'] ?? $post['title'] ?? '');
        $mediaUrl    = $this->resolveMediaUrl($post);
        $imageUrl    = (string) ($post['image_url'] ?? '');

        return match ($contentType) {
            'reel'  => $this->publishInstagramReel($pageToken, $igId, $mediaUrl, $caption),
            'story' => $this->publishInstagramStory($pageToken, $igId, $mediaUrl ?: $imageUrl),
            default => $this->publishInstagramPhoto($pageToken, $igId, $imageUrl ?: $mediaUrl, $caption),
        };
    }

    /** Publish a photo post to Instagram. */
    private function publishInstagramPhoto(string $token, string $igId, string $imageUrl, string $caption): array
    {
        if ($imageUrl === '') {
            throw new \RuntimeException('Instagram photo: Se requiere una URL de imagen pública.');
        }

        $containerId = $this->createInstagramContainer($token, $igId, [
            'image_url' => $imageUrl,
            'caption'   => $caption,
            'media_type' => 'IMAGE',
        ]);

        return $this->publishInstagramContainer($token, $igId, $containerId);
    }

    /** Publish a reel to Instagram. */
    private function publishInstagramReel(string $token, string $igId, string $videoUrl, string $caption): array
    {
        if ($videoUrl === '') {
            throw new \RuntimeException('Instagram Reel: Se requiere una URL de video pública (Cloudinary u otro CDN).');
        }

        $containerId = $this->createInstagramContainer($token, $igId, [
            'video_url'  => $videoUrl,
            'caption'    => $caption,
            'media_type' => 'REELS',
        ]);

        // Reels require processing time — poll until ready (max 60s)
        $this->waitForInstagramContainerReady($token, $containerId);

        return $this->publishInstagramContainer($token, $igId, $containerId);
    }

    /** Publish a story to Instagram. */
    private function publishInstagramStory(string $token, string $igId, string $mediaUrl): array
    {
        if ($mediaUrl === '') {
            throw new \RuntimeException('Instagram Story: Se requiere una URL de imagen o video pública.');
        }

        // Detect whether it's a video or image story by extension
        $isVideo = $this->isVideoUrl($mediaUrl);

        $params = $isVideo
            ? ['video_url' => $mediaUrl, 'media_type' => 'STORIES']
            : ['image_url' => $mediaUrl, 'media_type' => 'STORIES'];

        $containerId = $this->createInstagramContainer($token, $igId, $params);

        if ($isVideo) {
            $this->waitForInstagramContainerReady($token, $containerId);
        }

        return $this->publishInstagramContainer($token, $igId, $containerId);
    }

    /** Step 1: Create an Instagram media container. Returns container ID. */
    private function createInstagramContainer(string $token, string $igId, array $params): string
    {
        $response = Http::timeout(30)
            ->withToken($token)
            ->post($this->graphBaseUrl() . '/' . $igId . '/media', $params);

        if (!$response->ok()) {
            throw new \RuntimeException(
                'Instagram container: ' . ($this->errorMessage($response->json()) ?: 'Error creando contenedor.')
            );
        }

        $id = (string) ($response->json()['id'] ?? '');
        if ($id === '') {
            throw new \RuntimeException('Instagram: No se pudo crear el contenedor de media.');
        }

        return $id;
    }

    /** Step 2: Publish an Instagram container. Returns ['external_post_id' => ...]. */
    private function publishInstagramContainer(string $token, string $igId, string $containerId): array
    {
        $response = Http::timeout(30)
            ->withToken($token)
            ->post($this->graphBaseUrl() . '/' . $igId . '/media_publish', [
                'creation_id' => $containerId,
            ]);

        if (!$response->ok()) {
            throw new \RuntimeException(
                'Instagram publish: ' . ($this->errorMessage($response->json()) ?: 'Error publicando contenido.')
            );
        }

        return ['external_post_id' => (string) ($response->json()['id'] ?? $containerId)];
    }

    /**
     * Poll Instagram container status until FINISHED or timeout.
     * Required for reels and video stories.
     */
    private function waitForInstagramContainerReady(string $token, string $containerId, int $maxWaitSeconds = 60): void
    {
        $deadline = time() + $maxWaitSeconds;
        while (time() < $deadline) {
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($this->graphBaseUrl() . '/' . $containerId, [
                    'fields' => 'status_code,status',
                ]);

            $statusCode = (string) ($response->json()['status_code'] ?? 'IN_PROGRESS');

            if ($statusCode === 'FINISHED') {
                return;
            }

            if ($statusCode === 'ERROR') {
                throw new \RuntimeException(
                    'Instagram media processing failed: ' . ($response->json()['status'] ?? 'unknown error')
                );
            }

            sleep(4);
        }

        throw new \RuntimeException('Instagram: El procesamiento del media tardó demasiado. Intenta de nuevo.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cloudinary helper (ready for when you configure it)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the Cloudinary public URL for a given public_id.
     * Set CLOUDINARY_CLOUD_NAME in your .env to enable.
     *
     * @param  string  $publicId    e.g. "posts/my-image-xyz"
     * @param  string  $resourceType 'image' or 'video'
     * @param  array   $transforms  Optional Cloudinary transformation params (e.g. ['w' => 1080, 'q' => 'auto'])
     */
    public function cloudinaryUrl(string $publicId, string $resourceType = 'image', array $transforms = []): string
    {
        $cloudName = (string) config('services.cloudinary.cloud_name', '');
        if ($cloudName === '') {
            return '';
        }

        $base = "https://res.cloudinary.com/{$cloudName}/{$resourceType}/upload";

        if (!empty($transforms)) {
            $transformStr = collect($transforms)
                ->map(fn ($v, $k) => "{$k}_{$v}")
                ->implode(',');
            $base .= '/' . $transformStr;
        }

        return $base . '/' . ltrim($publicId, '/');
    }

    /**
     * Resolve the best public media URL for a post.
     * Priority: cloudinary_public_id (if Cloudinary is configured) → media_url → image_url
     */
    public function resolveMediaUrl(array $post): string
    {
        $cloudPublicId   = (string) ($post['cloudinary_public_id'] ?? '');
        $cloudResourceType = (string) ($post['cloudinary_resource_type'] ?? 'image');

        if ($cloudPublicId !== '' && config('services.cloudinary.cloud_name')) {
            return $this->cloudinaryUrl($cloudPublicId, $cloudResourceType, ['q' => 'auto', 'f' => 'auto']);
        }

        $mediaUrl = (string) ($post['media_url'] ?? '');
        if ($mediaUrl !== '') {
            return $mediaUrl;
        }

        return (string) ($post['image_url'] ?? '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SYNC (existing, unchanged)
    // ─────────────────────────────────────────────────────────────────────────

    public function sync(string $accessToken): array
    {
        $accounts = [];
        $ads      = [];
        $leads    = [];
        $insights = [];
        $errors   = [];

        $me          = $this->safeGet('/me', $accessToken, ['fields' => 'id,name,email'], $errors);
        $metaUserId  = (string) ($me['id'] ?? 'meta-user');
        $metaUserName = (string) ($me['name'] ?? 'Meta');

        $accounts[] = [
            'external_id' => $metaUserId,
            'platform'    => 'meta',
            'type'        => 'connection',
            'name'        => $metaUserName,
            'username'    => (string) ($me['email'] ?? ''),
            'status'      => 'connected',
            'raw'         => $this->withoutSensitiveFields($me),
        ];

        $pagesPayload = $this->safeGet('/me/accounts', $accessToken, [
            'fields' => 'id,name,category,fan_count,followers_count,picture{url},access_token,instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}',
            'limit'  => 100,
        ], $errors);

        foreach (($pagesPayload['data'] ?? []) as $page) {
            $pageId = (string) ($page['id'] ?? '');
            if ($pageId === '') {
                continue;
            }

            $pageToken  = (string) ($page['access_token'] ?? $accessToken);
            $accounts[] = [
                'external_id'       => $pageId,
                'platform'          => 'facebook',
                'type'              => 'page',
                'name'              => (string) ($page['name'] ?? 'Página de Facebook'),
                'username'          => '',
                'avatar_url'         => (string) data_get($page, 'picture.data.url', ''),
                'fan_count'          => (int) ($page['fan_count'] ?? 0),
                'followers_count'    => (int) ($page['followers_count'] ?? $page['fan_count'] ?? 0),
                'status'            => 'connected',
                'parent_external_id' => $metaUserId,
                'raw'               => $this->withoutSensitiveFields($page),
                // Store page token encrypted separately via the controller — here we just flag it
                'has_page_token'    => true,
            ];

            $pageInsights = $this->safeGet('/' . $pageId . '/insights', $pageToken, [
                'metric' => 'page_impressions,page_post_engagements,page_fans',
                'period' => 'day',
            ], $errors, false);
            $insights = array_merge($insights, $this->normalizeInsights($pageInsights, 'facebook', $pageId));

            $leadForms = $this->safeGet('/' . $pageId . '/leadgen_forms', $pageToken, [
                'fields' => 'id,name,status,leads.limit(25){id,created_time,field_data}',
                'limit'  => 25,
            ], $errors, false);
            $leads = array_merge($leads, $this->normalizeLeadForms($leadForms, $pageId, (string) ($page['name'] ?? 'Facebook')));

            $ig = $page['instagram_business_account'] ?? null;
            if (is_array($ig) && !empty($ig['id'])) {
                $igId       = (string) $ig['id'];
                $accounts[] = [
                    'external_id'       => $igId,
                    'platform'          => 'instagram',
                    'type'              => 'business_account',
                    'name'              => (string) ($ig['name'] ?? $ig['username'] ?? 'Instagram'),
                    'username'          => (string) ($ig['username'] ?? ''),
                    'avatar_url'        => (string) ($ig['profile_picture_url'] ?? ''),
                    'followers_count'   => (int) ($ig['followers_count'] ?? 0),
                    'media_count'        => (int) ($ig['media_count'] ?? 0),
                    'status'            => 'connected',
                    'parent_external_id' => $pageId,
                    'raw'               => $this->withoutSensitiveFields($ig),
                ];

                $igInsights = $this->safeGet('/' . $igId . '/insights', $pageToken, [
                    'metric' => 'reach,profile_views,website_clicks',
                    'period' => 'day',
                ], $errors, false);
                $insights = array_merge($insights, $this->normalizeInsights($igInsights, 'instagram', $igId));
            }
        }

        $adAccounts = $this->safeGet('/me/adaccounts', $accessToken, [
            'fields' => 'id,name,account_status,currency,amount_spent,campaigns.limit(20){id,name,status,objective,daily_budget,lifetime_budget,insights.date_preset(last_30d){impressions,reach,clicks,spend}}',
            'limit'  => 50,
        ], $errors, false);

        foreach (($adAccounts['data'] ?? []) as $adAccount) {
            $accountId = (string) ($adAccount['id'] ?? '');
            if ($accountId !== '') {
                $accounts[] = [
                    'external_id' => $accountId,
                    'platform'    => 'meta_ads',
                    'type'        => 'ad_account',
                    'name'        => (string) ($adAccount['name'] ?? 'Cuenta publicitaria'),
                    'username'    => (string) ($adAccount['currency'] ?? ''),
                    'status'      => ((string) ($adAccount['account_status'] ?? '1')) === '1' ? 'connected' : 'needs_reconnect',
                    'raw'         => $this->withoutSensitiveFields($adAccount),
                ];
            }

            foreach (($adAccount['campaigns']['data'] ?? []) as $campaign) {
                $campaignId = (string) ($campaign['id'] ?? '');
                if ($campaignId === '') {
                    continue;
                }
                $metric = $campaign['insights']['data'][0] ?? [];
                $ads[]  = [
                    'external_id'              => $campaignId,
                    'ad_account_external_id'   => $accountId,
                    'platform'                 => 'meta',
                    'name'                     => (string) ($campaign['name'] ?? 'Campaña'),
                    'status'                   => (string) ($campaign['status'] ?? 'UNKNOWN'),
                    'objective'                => (string) ($campaign['objective'] ?? ''),
                    'budget'                   => (float) (($campaign['daily_budget'] ?? $campaign['lifetime_budget'] ?? 0) / 100),
                    'spend'                    => (float) ($metric['spend'] ?? 0),
                    'reach'                    => (int) ($metric['reach'] ?? 0),
                    'impressions'              => (int) ($metric['impressions'] ?? 0),
                    'clicks'                   => (int) ($metric['clicks'] ?? 0),
                ];
            }
        }

        return [
            'accounts' => $accounts,
            'insights' => $insights,
            'ads'      => $ads,
            'leads'    => $leads,
            'errors'   => $errors,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function graphBaseUrl(): string
    {
        return 'https://graph.facebook.com/' . $this->graphVersion();
    }

    private function isVideoUrl(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'], true);
    }

    private function safeGet(string $path, string $accessToken, array $query, array &$errors, bool $required = true): array
    {
        $response = Http::timeout(25)
            ->withToken($accessToken)
            ->get($this->graphBaseUrl() . $path, $query);

        if ($response->ok()) {
            return $response->json() ?: [];
        }

        $message  = $this->errorMessage($response->json()) ?: 'Meta API no respondió correctamente.';
        $errors[] = [
            'path'     => $path,
            'required' => $required,
            'message'  => $message,
        ];

        if ($required) {
            throw new \RuntimeException($message);
        }

        return [];
    }

    private function normalizeInsights(array $payload, string $platform, string $accountId): array
    {
        $rows = [];

        foreach (($payload['data'] ?? []) as $metric) {
            $metricName = (string) ($metric['name'] ?? '');
            if ($metricName === '') {
                continue;
            }

            foreach (($metric['values'] ?? []) as $valueRow) {
                $value = $valueRow['value'] ?? 0;
                $rawValue = $value;
                if (is_array($value)) {
                    $value = collect($value)->flatten()->sum(fn ($item) => is_numeric($item) ? (float) $item : 0);
                }

                $rows[] = [
                    'external_account_id' => $accountId,
                    'platform'            => $platform,
                    'metric'              => $metricName,
                    'title'               => (string) ($metric['title'] ?? $metric['name'] ?? ''),
                    'value'               => (float) $value,
                    'raw_value'           => is_array($rawValue) ? $rawValue : null,
                    'period'              => (string) ($metric['period'] ?? 'day'),
                    'captured_at'         => (string) ($valueRow['end_time'] ?? now()->toISOString()),
                ];
            }
        }

        return $rows;
    }

    private function normalizeLeadForms(array $payload, string $pageId, string $pageName): array
    {
        $rows = [];
        foreach (($payload['data'] ?? []) as $form) {
            foreach (($form['leads']['data'] ?? []) as $lead) {
                $fields = collect($lead['field_data'] ?? [])
                    ->mapWithKeys(fn ($field) => [
                        Str::snake((string) ($field['name'] ?? 'campo')) => (string) collect($field['values'] ?? [])->first(),
                    ])
                    ->all();

                $rows[] = [
                    'external_id'      => (string) ($lead['id'] ?? Str::ulid()),
                    'source'           => 'meta_lead_form',
                    'platform'         => 'facebook',
                    'page_external_id' => $pageId,
                    'page_name'        => $pageName,
                    'form_external_id' => (string) ($form['id'] ?? ''),
                    'form_name'        => (string) ($form['name'] ?? 'Formulario'),
                    'name'             => (string) ($fields['full_name'] ?? $fields['name'] ?? $fields['nombre'] ?? 'Lead de Meta'),
                    'email'            => (string) ($fields['email'] ?? ''),
                    'phone'            => (string) ($fields['phone_number'] ?? $fields['phone'] ?? $fields['telefono'] ?? ''),
                    'status'           => 'new',
                    'created_time'     => (string) ($lead['created_time'] ?? now()->toISOString()),
                    'raw_fields'       => $fields,
                ];
            }
        }

        return $rows;
    }

    private function withoutSensitiveFields(array $payload): array
    {
        unset($payload['access_token'], $payload['token'], $payload['client_secret']);
        return $payload;
    }

    private function errorMessage(?array $payload): ?string
    {
        return $payload['error']['message'] ?? $payload['message'] ?? null;
    }
}
