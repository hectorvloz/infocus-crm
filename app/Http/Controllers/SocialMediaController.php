<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Services\SocialMedia\MetaSocialMediaService;
use App\Services\SocialMedia\TikTokSocialMediaService;
use App\Support\RoleAccess;
use App\Support\SocialMediaCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SocialMediaController extends Controller
{
    private FileStore $accounts;
    private FileStore $insights;
    private FileStore $ads;
    private FileStore $socialLeads;
    private FileStore $calendar;
    private FileStore $syncLogs;
    private FileStore $clients;
    private FileStore $users;
    private FileStore $crmLeads;

    public function __construct(
        private MetaSocialMediaService $meta,
        private TikTokSocialMediaService $tiktok,
    ) {
        $this->accounts = new FileStore('social_accounts.json');
        $this->insights = new FileStore('social_insights.json');
        $this->ads = new FileStore('social_ads.json');
        $this->socialLeads = new FileStore('social_leads.json');
        $this->calendar = new FileStore('social_content_calendar.json');
        $this->syncLogs = new FileStore('social_sync_logs.json');
        $this->clients = new FileStore('clientes.json');
        $this->users = new FileStore('users.json');
        $this->crmLeads = new FileStore('leads.json');
    }

    public function updateClients(Request $request)
    {
        abort_unless(\App\Support\RoleAccess::can(\Illuminate\Support\Facades\Auth::user(), 'social-media.update'), 403);
        $clientIds = $request->input('client_ids', []);
        $store = new FileStore('social_clients.json');
        $dataToSave = [];
        foreach($clientIds as $id) {
            $dataToSave[] = ['id' => $id];
        }
        $store->save($dataToSave);
        return redirect()->back()->with('success', 'Clientes actualizados correctamente en Social Media.');
    }

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'resumen');
        if ($tab === 'configuracion') {
            return redirect()->route('settings.social_media');
        }
        $allowedTabs = ['resumen', 'cuentas', 'estadisticas', 'anuncios', 'leads', 'calendario'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'resumen';
        }

        $socialClientIds = collect((new FileStore('social_clients.json'))->all())->pluck('id')->filter()->all();

        $clients = collect($this->clients->all())->map(fn ($client) => [
            'id' => (string) ($client['id'] ?? ''),
            'name' => (string) ($client['nombre'] ?? $client['name'] ?? $client['empresa'] ?? 'Sin Nombre'),
        ])->filter(fn ($client) => $client['id'] !== '')->values()->all();
        $users = collect($this->users->all())->map(fn ($user) => [
            'id' => (string) ($user['id'] ?? ''),
            'name' => (string) ($user['name'] ?? $user['email'] ?? 'Usuario'),
        ])->filter(fn ($user) => $user['id'] !== '')->values()->all();

        $selectedClientId = (string) $request->query('client_id', 'all');
        if ($selectedClientId !== 'all' && !collect($clients)->contains('id', $selectedClientId)) {
            $selectedClientId = 'all';
        }

        $period = $this->resolvePeriod($request);
        $allAccounts = $this->publicAccounts();
        $accountScope = $this->accountScope($allAccounts, $selectedClientId);
        $accounts = $accountScope['accounts'];
        $accountIds = $accountScope['account_ids'];
        $externalAccountIds = $accountScope['external_account_ids'];

        $insights = collect($this->insights->all())
            ->filter(fn ($row) => $selectedClientId === 'all' || in_array((string) ($row['external_account_id'] ?? ''), $externalAccountIds, true))
            ->filter(fn ($row) => $this->withinPeriod($row['captured_at'] ?? $row['created_at'] ?? null, $period))
            ->sortByDesc('updated_at')
            ->values()
            ->all();

        $ads = collect($this->ads->all())
            ->filter(fn ($ad) => $selectedClientId === 'all'
                || in_array((string) ($ad['account_id'] ?? ''), $accountIds, true)
                || in_array((string) ($ad['ad_account_external_id'] ?? ''), $externalAccountIds, true)
                || (($ad['client_id'] ?? null) === $selectedClientId))
            ->filter(fn ($ad) => $this->withinPeriod($ad['captured_at'] ?? $ad['updated_at'] ?? $ad['created_at'] ?? null, $period))
            ->sortByDesc('updated_at')
            ->values()
            ->all();

        $socialLeads = collect($this->socialLeads->all())
            ->filter(fn ($lead) => $selectedClientId === 'all'
                || (($lead['client_id'] ?? null) === $selectedClientId)
                || in_array((string) ($lead['account_id'] ?? ''), $accountIds, true)
                || in_array((string) ($lead['page_external_id'] ?? ''), $externalAccountIds, true))
            ->filter(fn ($lead) => $this->withinPeriod($lead['created_time'] ?? $lead['created_at'] ?? null, $period))
            ->sortByDesc(fn ($lead) => $lead['created_time'] ?? $lead['created_at'] ?? '')
            ->values()
            ->all();

        $calendarItems = collect($this->calendar->all())
            ->filter(fn ($item) => $selectedClientId === 'all'
                || (($item['client_id'] ?? null) === $selectedClientId)
                || in_array((string) ($item['account_id'] ?? ''), $accountIds, true))
            ->sortBy('scheduled_at')
            ->values()
            ->all();

        $syncLogs = collect($this->syncLogs->all())->sortByDesc('created_at')->take(12)->values()->all();
        $metrics = $this->summaryMetrics($accounts, $insights, $ads, $socialLeads, $calendarItems);
        $performanceSeries = $this->performanceSeries($insights, $socialLeads, $period);

        return view('social-media.index', [
            'tab' => $tab,
            'tabs' => $allowedTabs,
            'accounts' => $accounts,
            'allAccounts' => $allAccounts,
            'insights' => $insights,
            'ads' => $ads,
            'socialLeads' => $socialLeads,
            'calendarItems' => $calendarItems,
            'syncLogs' => $syncLogs,
            'clients' => $clients,
            'users' => $users,
            'metrics' => $metrics,
            'performanceSeries' => $performanceSeries,
            'period' => $period,
            'selectedClientId' => $selectedClientId,
            'selectedClient' => $selectedClientId === 'all' ? null : collect($clients)->firstWhere('id', $selectedClientId),
            'accountIds' => $accountIds,
            'externalAccountIds' => $externalAccountIds,
            'metaConfigured' => $this->metaConfigured(),
            'tiktokStatus' => $this->tiktok->status(),
            'socialClientIds' => $socialClientIds,
            'canSync' => RoleAccess::can(Auth::user(), 'social-media.sync'),
            'canCreate' => RoleAccess::can(Auth::user(), 'social-media.create'),
            'canUpdate' => RoleAccess::can(Auth::user(), 'social-media.update'),
            'canDelete' => RoleAccess::can(Auth::user(), 'social-media.delete'),
        ]);
    }

    public function storeAccount(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.create'), 403);

        $data = $request->validate([
            'client_id' => 'required|string|max:120',
            'platform' => 'required|string|in:facebook,instagram,tiktok,meta,meta_ads',
            'type' => 'required|string|in:page,profile,business_account,ad_account,manual',
            'name' => 'required|string|max:180',
            'username' => 'nullable|string|max:180',
            'responsible_id' => 'nullable|string|max:120',
            'external_url' => 'nullable|url|max:500',
        ]);

        $clientExists = collect($this->clients->all())->contains(fn ($client) => (string) ($client['id'] ?? '') === $data['client_id']);
        if (!$clientExists) {
            return back()->withInput()->with('error', 'Selecciona un cliente válido antes de agregar la cuenta.');
        }

        $now = now()->toISOString();
        $this->accounts->create([
            'external_id' => 'manual:' . $data['platform'] . ':' . Str::ulid(),
            'platform' => $data['platform'],
            'type' => $data['type'],
            'name' => $data['name'],
            'username' => $data['username'] ?? '',
            'external_url' => $data['external_url'] ?? '',
            'status' => 'connected',
            'client_id' => $data['client_id'],
            'responsible_id' => ($data['responsible_id'] ?? null) ?: null,
            'source' => 'manual',
            'created_by' => Auth::id(),
            'last_connected_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()->route('social-media.index', [
            'tab' => 'cuentas',
            'client_id' => $data['client_id'],
        ])->with('success', 'Cuenta agregada al cliente.');
    }

    public function metaConnect(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $clientId = (string) $request->query('client_id', '');
        $platform = (string) $request->query('platform', 'meta');
        if (!$this->clientExists($clientId)) {
            return redirect()->route('social-media.index', ['tab' => 'cuentas'])
                ->with('error', 'Selecciona primero el cliente al que vas a vincular la cuenta.');
        }
        if (!in_array($platform, ['facebook', 'instagram', 'meta_ads', 'meta'], true)) {
            $platform = 'meta';
        }

        if (!$this->metaConfigured()) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion', 'client_id' => $clientId])
                ->with('error', 'Configura META_APP_ID y META_APP_SECRET en el .env antes de conectar Meta.');
        }

        $state = Str::random(40);
        $request->session()->put('meta_oauth_state', $state);
        $request->session()->put('meta_oauth_context', [
            'client_id' => $clientId,
            'platform' => $platform,
        ]);

        return redirect()->away($this->meta->authUrl($state, $this->metaRedirectUri()));
    }

    public function metaCallback(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $state = (string) $request->query('state', '');
        $context = $request->session()->pull('meta_oauth_context', []);
        if ($state === '' || $state !== $request->session()->pull('meta_oauth_state')) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion'])
                ->with('error', 'Estado OAuth inválido. Intenta conectar Meta de nuevo.');
        }

        if ($request->query('error')) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion'])
                ->with('error', 'Meta rechazó la conexión: ' . (string) $request->query('error_description', $request->query('error')));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('social-media.index', ['tab' => 'configuracion'])
                ->with('error', 'Meta no devolvió código de autorización.');
        }

        try {
            $tokenPayload = $this->meta->exchangeCode($code, $this->metaRedirectUri());
            $token = (string) $tokenPayload['access_token'];
            $connection = $this->upsertAccount([
                'external_id' => 'meta-oauth',
                'platform' => 'meta',
                'type' => 'oauth_connection',
                'name' => 'Conexión Meta',
                'status' => 'connected',
                'token_encrypted' => Crypt::encryptString($token),
                'token_expires_at' => !empty($tokenPayload['expires_in']) ? now()->addSeconds((int) $tokenPayload['expires_in'])->toISOString() : null,
                'connected_by' => Auth::id(),
                'last_connected_at' => now()->toISOString(),
            ]);

            $result = $this->syncWithToken($token, is_array($context) ? $context : []);
            $this->logSync('meta', 'success', 'Meta conectado correctamente.', [
                'connection_id' => $connection['id'] ?? null,
                'client_id' => $context['client_id'] ?? null,
                'platform' => $context['platform'] ?? null,
                'accounts' => count($result['accounts'] ?? []),
                'ads' => count($result['ads'] ?? []),
                'leads' => count($result['leads'] ?? []),
            ]);

            return redirect()->route('social-media.index', [
                'tab' => 'cuentas',
                'client_id' => $context['client_id'] ?? 'all',
            ])
                ->with('success', 'Meta conectado y sincronizado correctamente.');
        } catch (\Throwable $e) {
            $this->logSync('meta', 'error', $e->getMessage());
            return redirect()->route('social-media.index', ['tab' => 'configuracion'])
                ->with('error', 'No se pudo conectar Meta: ' . $e->getMessage());
        }
    }

    public function tiktokConnect(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $clientId = (string) $request->query('client_id', '');
        if (!$this->clientExists($clientId)) {
            return redirect()->route('social-media.index', ['tab' => 'cuentas'])
                ->with('error', 'Selecciona primero el cliente al que vas a vincular TikTok.');
        }

        if (!$this->tiktok->isConfigured()) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion', 'client_id' => $clientId])
                ->with('error', 'Configura TIKTOK_CLIENT_KEY y TIKTOK_CLIENT_SECRET antes de conectar TikTok.');
        }

        $state = Str::random(40);
        $request->session()->put('tiktok_oauth_state', $state);
        $request->session()->put('tiktok_oauth_context', ['client_id' => $clientId]);

        return redirect()->away($this->tiktok->authUrl($state, $this->tiktokRedirectUri()));
    }

    public function tiktokCallback(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $state = (string) $request->query('state', '');
        $context = $request->session()->pull('tiktok_oauth_context', []);
        if ($state === '' || $state !== $request->session()->pull('tiktok_oauth_state')) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion'])
                ->with('error', 'Estado OAuth de TikTok inválido. Intenta conectar de nuevo.');
        }

        if ($request->query('error')) {
            return redirect()->route('social-media.index', ['tab' => 'configuracion', 'client_id' => $context['client_id'] ?? 'all'])
                ->with('error', 'TikTok rechazó la conexión: ' . (string) $request->query('error_description', $request->query('error')));
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('social-media.index', ['tab' => 'configuracion', 'client_id' => $context['client_id'] ?? 'all'])
                ->with('error', 'TikTok no devolvió código de autorización.');
        }

        $this->upsertAccount([
            'external_id' => 'tiktok-pending:' . Str::ulid(),
            'platform' => 'tiktok',
            'type' => 'profile',
            'name' => 'TikTok conectado',
            'username' => '',
            'status' => 'needs_reconnect',
            'client_id' => $context['client_id'] ?? null,
            'connected_by' => Auth::id(),
            'last_connected_at' => now()->toISOString(),
            'raw' => ['note' => 'OAuth autorizado. Falta intercambio/API según scopes aprobados de TikTok.'],
        ]);
        $this->logSync('tiktok', 'warning', 'TikTok autorizó el flujo inicial. Falta completar intercambio de token según aprobación/scopes.', [
            'client_id' => $context['client_id'] ?? null,
        ]);

        return redirect()->route('social-media.index', ['tab' => 'cuentas', 'client_id' => $context['client_id'] ?? 'all'])
            ->with('success', 'TikTok quedó preparado para este cliente. Completa aprobación/scopes para sincronizar datos reales.');
    }

    public function metaDisconnect()
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $rows = collect($this->accounts->all())
            ->map(function ($account) {
                if (($account['platform'] ?? '') === 'meta' && ($account['type'] ?? '') === 'oauth_connection') {
                    $account['status'] = 'disconnected';
                    $account['token_encrypted'] = null;
                    $account['updated_at'] = now()->toISOString();
                }
                return $account;
            })
            ->values()
            ->all();

        $this->accounts->save($rows);
        $this->logSync('meta', 'disconnected', 'Meta desconectado por el usuario.');

        return redirect()->route('social-media.index', ['tab' => 'configuracion'])
            ->with('success', 'Meta desconectado.');
    }

    public function sync()
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.sync'), 403);

        $token = $this->activeMetaToken();
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'Conecta Meta antes de sincronizar.'], 422);
        }

        try {
            $result = $this->syncWithToken($token);
            $this->logSync('meta', 'success', 'Sincronización completada.', [
                'accounts' => count($result['accounts'] ?? []),
                'ads' => count($result['ads'] ?? []),
                'leads' => count($result['leads'] ?? []),
                'errors' => count($result['errors'] ?? []),
            ]);
            return response()->json(['ok' => true, 'message' => 'Social Media sincronizado.', 'result' => $result]);
        } catch (\Throwable $e) {
            $this->logSync('meta', 'error', $e->getMessage());
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeCalendar(Request $request)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.create'), 403);

        $data = $request->validate([
            'title'                    => 'required|string|max:180',
            'caption'                  => 'nullable|string|max:5000',
            'platform'                 => 'required|string|in:facebook,instagram,tiktok,meta',
            'content_type'             => 'nullable|string|in:post,reel,story',
            'status'                   => 'required|string|in:idea,borrador,aprobado,programado,publicado,cancelado',
            'scheduled_at'             => 'nullable|date',
            'image_url'                => 'nullable|url|max:1000',
            'media_url'                => 'nullable|url|max:1000',
            'cloudinary_public_id'     => 'nullable|string|max:500',
            'cloudinary_resource_type' => 'nullable|string|in:image,video',
            'client_id'                => 'required|string|max:120',
            'account_id'               => 'nullable|string|max:120',
            'responsible_id'           => 'nullable|string|max:120',
        ]);

        $data['content_type'] = $data['content_type'] ?? 'post';

        if (!collect($this->clients->all())->contains(fn ($client) => (string) ($client['id'] ?? '') === $data['client_id'])) {
            return back()->withInput()->with('error', 'Selecciona un cliente válido para el contenido.');
        }

        $this->calendar->create($data + [
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('social-media.index', ['tab' => 'calendario', 'client_id' => $data['client_id'] ?: 'all'])
            ->with('success', 'Contenido agregado al calendario.');
    }

    public function updateAccount(Request $request, string $id)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.update'), 403);

        $account = $this->accounts->find($id);
        abort_unless($account, 404);

        $data = $request->validate([
            'client_id' => 'nullable|string|max:120',
            'responsible_id' => 'nullable|string|max:120',
            'redirect_client_id' => 'nullable|string|max:120',
        ]);

        $this->accounts->update($id, [
            'client_id' => $data['client_id'] ?: null,
            'responsible_id' => $data['responsible_id'] ?: null,
        ]);

        $redirectClientId = ($data['redirect_client_id'] ?? null) ?: ($data['client_id'] ?: 'all');

        return redirect()->route('social-media.index', ['tab' => 'cuentas', 'client_id' => $redirectClientId])
            ->with('success', 'Cuenta actualizada.');
    }

    public function updateCalendar(Request $request, string $id)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.update'), 403);

        $data = $request->validate([
            'title'                    => 'required|string|max:180',
            'caption'                  => 'nullable|string|max:5000',
            'platform'                 => 'required|string|in:facebook,instagram,tiktok,meta',
            'content_type'             => 'nullable|string|in:post,reel,story',
            'status'                   => 'required|string|in:idea,borrador,aprobado,programado,publicado,cancelado,error_publicacion',
            'scheduled_at'             => 'nullable|date',
            'image_url'                => 'nullable|url|max:1000',
            'media_url'                => 'nullable|url|max:1000',
            'cloudinary_public_id'     => 'nullable|string|max:500',
            'cloudinary_resource_type' => 'nullable|string|in:image,video',
            'client_id'                => 'required|string|max:120',
            'account_id'               => 'nullable|string|max:120',
            'responsible_id'           => 'nullable|string|max:120',
        ]);

        if (!collect($this->clients->all())->contains(fn ($client) => (string) ($client['id'] ?? '') === $data['client_id'])) {
            return back()->withInput()->with('error', 'Selecciona un cliente válido para el contenido.');
        }

        abort_unless($this->calendar->find($id), 404);
        $this->calendar->update($id, $data);

        return redirect()->route('social-media.index', ['tab' => 'calendario', 'client_id' => $data['client_id'] ?: 'all'])
            ->with('success', 'Contenido actualizado.');
    }

    public function deleteCalendar(string $id)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.delete'), 403);
        $this->calendar->delete($id);

        return redirect()->route('social-media.index', ['tab' => 'calendario'])
            ->with('success', 'Contenido eliminado.');
    }

    /**
     * Publish a single calendar item immediately via the Meta API.
     * Called from the "Publicar ahora" button in the calendar UI.
     */
    public function publishNow(string $id)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.update'), 403);

        $post = $this->calendar->find($id);
        abort_unless($post, 404);

        $token = $this->activeMetaToken();
        if (!$token) {
            return response()->json(['ok' => false, 'message' => 'Reconecta Meta en Configuración antes de publicar.'], 422);
        }

        try {
            $allAccounts = collect($this->accounts->all());
            $accountId   = (string) ($post['account_id'] ?? '');
            $platform    = (string) ($post['platform'] ?? 'instagram');

            // Find the account to publish to
            $account = $accountId !== ''
                ? $allAccounts->firstWhere('id', $accountId)
                : $allAccounts->first(fn ($a) => in_array($a['platform'] ?? '', [$platform, 'facebook', 'instagram'], true) && ($a['status'] ?? '') === 'connected');

            if (!$account) {
                throw new \RuntimeException("No se encontró una cuenta conectada para '{$platform}'.");
            }

            $externalId      = (string) ($account['external_id'] ?? '');
            $accountPlatform = (string) ($account['platform'] ?? '');

            $result = match ($accountPlatform) {
                'instagram' => $this->meta->publishToInstagram($token, $externalId, $post),
                'facebook'  => $this->meta->publishToFacebookFeed($token, $externalId, $post),
                default     => throw new \RuntimeException("La plataforma '{$accountPlatform}' no soporta publicación directa aún."),
            };

            $this->calendar->update($id, [
                'status'           => 'publicado',
                'published_at'     => now()->toISOString(),
                'external_post_id' => $result['external_post_id'] ?? '',
                'publish_error'    => null,
            ]);

            $this->logSync($platform, 'published', 'Publicado manualmente: ' . ($post['title'] ?? ''), [
                'post_id'          => $id,
                'external_post_id' => $result['external_post_id'] ?? '',
            ]);

            return response()->json(['ok' => true, 'message' => 'Publicado correctamente en ' . ucfirst($accountPlatform) . '.']);

        } catch (\Throwable $e) {
            $errorMsg = \Illuminate\Support\Str::limit($e->getMessage(), 400);

            $this->calendar->update($id, [
                'status'        => 'error_publicacion',
                'publish_error' => $errorMsg,
            ]);

            $this->logSync($post['platform'] ?? 'meta', 'publish_error', 'Error manual: ' . ($post['title'] ?? ''), [
                'post_id' => $id,
                'error'   => $errorMsg,
            ]);

            return response()->json(['ok' => false, 'message' => $errorMsg], 422);
        }
    }

    public function convertLead(string $id)
    {
        abort_unless(RoleAccess::can(Auth::user(), 'social-media.update'), 403);

        $lead = $this->socialLeads->find($id);
        abort_unless($lead, 404);

        $email = strtolower(trim((string) ($lead['email'] ?? '')));
        $phone = preg_replace('/\D+/', '', (string) ($lead['phone'] ?? ''));

        $existing = collect($this->crmLeads->all())->first(function ($crmLead) use ($email, $phone) {
            $crmEmail = strtolower(trim((string) ($crmLead['email'] ?? '')));
            $crmPhone = preg_replace('/\D+/', '', (string) ($crmLead['telefono'] ?? ''));
            return ($email !== '' && $crmEmail === $email) || ($phone !== '' && $crmPhone === $phone);
        });

        if ($existing) {
            $this->socialLeads->update($id, [
                'status' => 'converted',
                'crm_lead_id' => $existing['id'] ?? null,
                'converted_at' => now()->toISOString(),
            ]);
            return redirect()->route('social-media.index', ['tab' => 'leads'])
                ->with('success', 'Este contacto ya existía en Leads. Quedó vinculado.');
        }

        $created = $this->crmLeads->create([
            'nombre' => (string) ($lead['name'] ?? 'Lead social'),
            'email' => $email,
            'telefono' => (string) ($lead['phone'] ?? ''),
            'etapa' => 'Posible cliente',
            'origen' => (string) ($lead['source'] ?? 'social_media'),
            'notas' => 'Lead importado desde Social Media: ' . (string) ($lead['form_name'] ?? $lead['page_name'] ?? $lead['platform'] ?? ''),
            'presupuesto_estimado' => null,
            'valor' => null,
            'encargados' => [],
        ]);

        $this->socialLeads->update($id, [
            'status' => 'converted',
            'crm_lead_id' => $created['id'] ?? null,
            'converted_at' => now()->toISOString(),
        ]);

        return redirect()->route('social-media.index', ['tab' => 'leads'])
            ->with('success', 'Lead convertido al CRM.');
    }

    private function syncWithToken(string $token, array $context = []): array
    {
        $result = $this->meta->sync($token);
        $clientId = (string) ($context['client_id'] ?? '');
        $targetPlatform = (string) ($context['platform'] ?? 'meta');

        foreach ($result['accounts'] ?? [] as $account) {
            if ($clientId !== '' && $this->shouldAssignImportedAccount($account, $targetPlatform)) {
                $account['client_id'] = $clientId;
            }
            $this->upsertAccount($account);
        }
        $this->upsertRows($this->insights, $result['insights'] ?? [], fn ($row) => implode(':', [
            $row['external_account_id'] ?? '',
            $row['metric'] ?? '',
            $row['captured_at'] ?? '',
        ]));
        $this->upsertRows($this->ads, $result['ads'] ?? [], fn ($row) => (string) ($row['external_id'] ?? Str::ulid()));
        $this->upsertRows($this->socialLeads, $result['leads'] ?? [], fn ($row) => (string) ($row['external_id'] ?? Str::ulid()));

        foreach (($result['errors'] ?? []) as $error) {
            $this->logSync('meta', 'warning', (string) ($error['message'] ?? 'Permiso no disponible.'), $error);
        }

        return $result;
    }

    private function upsertAccount(array $incoming): array
    {
        $rows = $this->accounts->all();
        $externalId = (string) ($incoming['external_id'] ?? '');
        $platform = (string) ($incoming['platform'] ?? '');
        $type = (string) ($incoming['type'] ?? '');
        $now = now()->toISOString();
        $updated = null;

        foreach ($rows as &$row) {
            if (($row['external_id'] ?? '') === $externalId && ($row['platform'] ?? '') === $platform && ($row['type'] ?? '') === $type) {
                $row = array_merge($row, $incoming, ['updated_at' => $now]);
                $updated = $row;
                break;
            }
        }

        if (!$updated) {
            $updated = array_merge([
                'id' => (string) Str::ulid(),
                'client_id' => null,
                'responsible_id' => null,
                'created_at' => $now,
            ], $incoming, ['updated_at' => $now]);
            $rows[] = $updated;
        }

        $this->accounts->save(array_values($rows));
        return $updated;
    }

    private function upsertRows(FileStore $store, array $incomingRows, callable $keyResolver): void
    {
        $rows = $store->all();
        $index = [];
        foreach ($rows as $i => $row) {
            $index[(string) ($row['sync_key'] ?? $row['external_id'] ?? $row['id'] ?? $i)] = $i;
        }

        foreach ($incomingRows as $incoming) {
            $key = (string) $keyResolver($incoming);
            $now = now()->toISOString();
            $incoming['sync_key'] = $key;
            if (isset($index[$key])) {
                $rows[$index[$key]] = array_merge($rows[$index[$key]], $incoming, ['updated_at' => $now]);
            } else {
                $rows[] = array_merge(['id' => (string) Str::ulid(), 'created_at' => $now], $incoming, ['updated_at' => $now]);
            }
        }

        $store->save(array_values($rows));
    }

    private function publicAccounts(): array
    {
        return collect($this->accounts->all())
            ->reject(fn ($account) => !empty($account['token_encrypted']))
            ->map(function ($account) {
                unset($account['token_encrypted']);
                return $account;
            })
            ->sortByDesc('updated_at')
            ->values()
            ->all();
    }

    private function accountScope(array $accounts, string $clientId): array
    {
        $scoped = collect($accounts)
            ->filter(fn ($account) => $clientId === 'all' || (($account['client_id'] ?? null) === $clientId))
            ->values()
            ->all();

        return [
            'accounts' => $scoped,
            'account_ids' => collect($scoped)->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all(),
            'external_account_ids' => collect($scoped)->pluck('external_id')->filter()->map(fn ($id) => (string) $id)->values()->all(),
        ];
    }

    private function activeMetaToken(): ?string
    {
        $connection = collect($this->accounts->all())
            ->first(fn ($account) => ($account['platform'] ?? '') === 'meta'
                && ($account['type'] ?? '') === 'oauth_connection'
                && ($account['status'] ?? '') === 'connected'
                && !empty($account['token_encrypted']));

        if (!$connection) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $connection['token_encrypted']);
        } catch (\Throwable) {
            return null;
        }
    }

    private function metaConfigured(): bool
    {
        return SocialMediaCredentials::metaConfigured();
    }

    private function metaRedirectUri(): string
    {
        return SocialMediaCredentials::metaRedirectUri() ?: route('social-media.meta.callback');
    }

    private function tiktokRedirectUri(): string
    {
        return SocialMediaCredentials::tiktokRedirectUri() ?: route('social-media.tiktok.callback');
    }

    private function clientExists(string $clientId): bool
    {
        return $clientId !== '' && $clientId !== 'all'
            && collect($this->clients->all())->contains(fn ($client) => (string) ($client['id'] ?? '') === $clientId);
    }

    private function shouldAssignImportedAccount(array $account, string $targetPlatform): bool
    {
        $platform = (string) ($account['platform'] ?? '');
        $type = (string) ($account['type'] ?? '');

        if ($targetPlatform === 'meta') {
            return $type !== 'oauth_connection' && $type !== 'connection';
        }

        return $platform === $targetPlatform;
    }

    private function summaryMetrics(array $accounts, array $insights, array $ads, array $leads, array $calendar): array
    {
        return [
            'accounts' => count($accounts),
            'reach' => (int) $this->metricBucketTotal($insights, 'reach'),
            'interactions' => (int) $this->metricBucketTotal($insights, 'interactions'),
            'leads' => count($leads),
            'ad_spend' => collect($ads)->sum(fn ($ad) => (float) ($ad['spend'] ?? 0)),
            'active_ads' => collect($ads)->filter(fn ($ad) => in_array(strtoupper((string) ($ad['status'] ?? '')), ['ACTIVE', 'ENABLED'], true))->count(),
            'scheduled_posts' => collect($calendar)->filter(fn ($item) => ($item['status'] ?? '') === 'programado')->count(),
            'demographics' => $this->audienceBreakdown($insights, ['gender', 'age_gender', 'audience_gender']),
            'city_breakdown' => $this->audienceBreakdown($insights, ['city', 'audience_city']),
            'investment_by_platform' => collect($ads)
                ->groupBy(fn ($ad) => (string) ($ad['platform'] ?? 'meta'))
                ->map(fn ($rows) => round($rows->sum(fn ($ad) => (float) ($ad['spend'] ?? 0)), 2))
                ->all(),
        ];
    }

    private function performanceSeries(array $insights, array $leads, array $period): array
    {
        $start = $period['start'] instanceof Carbon ? $period['start']->copy()->startOfDay() : Carbon::now()->startOfDay();
        $end = $period['end'] instanceof Carbon ? $period['end']->copy()->startOfDay() : Carbon::now()->startOfDay();
        $days = (int) max(1, min(370, $start->diffInDays($end) + 1));
        $insightsByDate = collect($insights)->groupBy(function ($row) {
            return $this->dateKey($row['captured_at'] ?? null);
        });
        $leadsByDate = collect($leads)->groupBy(function ($lead) {
            return $this->dateKey($lead['created_time'] ?? $lead['created_at'] ?? null);
        });

        return collect(range(0, $days - 1))
            ->map(function ($offset) use ($start, $insightsByDate, $leadsByDate) {
                $date = $start->copy()->addDays($offset);
                $key = $date->format('Y-m-d');
                $rows = $insightsByDate->get($key, []);

                return [
                    'date' => $key,
                    'label' => $date->format('d M'),
                    'reach' => (int) $this->metricBucketTotal($rows, 'reach'),
                    'interactions' => (int) $this->metricBucketTotal($rows, 'interactions'),
                    'leads' => collect($leadsByDate->get($key, []))->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolvePeriod(Request $request): array
    {
        $key = (string) $request->query('period', '30d');
        if (!in_array($key, ['today', '7d', '30d', 'total', 'custom'], true)) {
            $key = '30d';
        }

        $now = Carbon::now();
        $start = match ($key) {
            'today' => $now->copy()->startOfDay(),
            '7d' => $now->copy()->subDays(6)->startOfDay(),
            'total' => $this->firstSocialDataDate(),
            'custom' => $this->parsePeriodDate($request->query('date_from'))?->startOfDay() ?? $now->copy()->subDays(29)->startOfDay(),
            default => $now->copy()->subDays(29)->startOfDay(),
        };
        $end = match ($key) {
            'custom' => $this->parsePeriodDate($request->query('date_to'))?->endOfDay() ?? $now->copy()->endOfDay(),
            default => $now->copy()->endOfDay(),
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $labels = [
            'today' => 'Día actual',
            '7d' => 'Últimos 7 días',
            '30d' => 'Últimos 30 días',
            'total' => 'Total',
            'custom' => 'Fecha personalizada',
        ];

        return [
            'key' => $key,
            'label' => $labels[$key],
            'start' => $start,
            'end' => $end,
            'date_from' => $start->format('Y-m-d'),
            'date_to' => $end->format('Y-m-d'),
        ];
    }

    private function parsePeriodDate(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function firstSocialDataDate(): Carbon
    {
        $dates = collect()
            ->merge(collect($this->insights->all())->pluck('captured_at'))
            ->merge(collect($this->ads->all())->pluck('created_at'))
            ->merge(collect($this->ads->all())->pluck('updated_at'))
            ->merge(collect($this->socialLeads->all())->pluck('created_time'))
            ->merge(collect($this->socialLeads->all())->pluck('created_at'))
            ->filter()
            ->map(fn ($date) => $this->parsePeriodDate($date))
            ->filter();

        return ($dates->sort()->first() ?: Carbon::now())->copy()->startOfDay();
    }

    private function withinPeriod(mixed $value, array $period): bool
    {
        $date = $this->parsePeriodDate($value);
        if (!$date) {
            return false;
        }

        return $date->betweenIncluded($period['start'], $period['end']);
    }

    private function metricBucketTotal(iterable $rows, string $bucket): float
    {
        $needles = match ($bucket) {
            'reach' => ['reach', 'impressions', 'views'],
            'interactions' => ['engagement', 'post_engagement', 'clicks', 'profile_views', 'website_clicks', 'likes', 'comments', 'shares', 'saves'],
            default => [$bucket],
        };

        return collect($rows)
            ->filter(function ($row) use ($needles) {
                $metric = strtolower((string) ($row['metric'] ?? ''));
                return collect($needles)->contains(fn ($needle) => str_contains($metric, $needle));
            })
            ->sum(fn ($row) => (float) ($row['value'] ?? 0));
    }

    private function audienceBreakdown(array $insights, array $metricNeedles): array
    {
        $rows = collect($insights)->filter(function ($row) use ($metricNeedles) {
            $metric = strtolower((string) ($row['metric'] ?? ''));
            return collect($metricNeedles)->contains(fn ($needle) => str_contains($metric, $needle));
        });

        return $rows
            ->flatMap(function ($row) {
                $raw = $row['raw_value'] ?? $row['value'] ?? null;
                if (!is_array($raw)) {
                    return [];
                }

                return collect($raw)->map(fn ($value, $label) => [
                    'label' => (string) $label,
                    'city' => (string) $label,
                    'value' => (float) $value,
                    'color' => '#84cc16',
                ]);
            })
            ->values()
            ->all();
    }

    private function dateKey(mixed $value): string
    {
        try {
            return Carbon::parse($value ?: now())->format('Y-m-d');
        } catch (\Throwable) {
            return Carbon::now()->format('Y-m-d');
        }
    }

    private function logSync(string $provider, string $status, string $message, array $context = []): void
    {
        $this->syncLogs->create([
            'provider' => $provider,
            'status' => $status,
            'message' => Str::limit($message, 500, ''),
            'context' => $this->scrubContext($context),
            'user_id' => Auth::id(),
        ]);
    }

    private function scrubContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (str_contains(strtolower((string) $key), 'token') || str_contains(strtolower((string) $key), 'secret')) {
                $context[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $context[$key] = $this->scrubContext($value);
            }
        }
        return $context;
    }
}
