@extends('layouts.app')

@section('title', 'Social Media')

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $tabLabels = [
        'resumen' => 'Resumen',
        'cuentas' => 'Cuentas',
        'estadisticas' => 'Estadísticas',
        'anuncios' => 'Anuncios',
        'leads' => 'Leads',
        'calendario' => 'Calendario',
    ];
    $mobileTabLabels = [
        'resumen' => 'Resumen',
        'cuentas' => 'Cuentas',
        'estadisticas' => 'Estad.',
        'anuncios' => 'Anuncios',
        'leads' => 'Leads',
        'calendario' => 'Calend.',
    ];
    $platformLabels = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'meta' => 'Meta',
        'meta_ads' => 'Meta Ads',
    ];
    $statusLabels = [
        'connected' => 'Conectado',
        'needs_reconnect' => 'Reconectar',
        'disconnected' => 'Desconectado',
        'error' => 'Error',
        'idea' => 'Idea',
        'borrador' => 'Borrador',
        'aprobado' => 'Aprobado',
        'programado' => 'Programado',
        'publicado' => 'Publicado',
        'cancelado' => 'Cancelado',
        'new' => 'Nuevo',
        'converted' => 'Convertido',
    ];
    $clientsById = collect($clients)->keyBy('id');
    $usersById = collect($users)->keyBy('id');
    $accountsById = collect($accounts)->keyBy('id');
    $connectedPlatforms = collect($accounts)->pluck('platform')->unique()->values()->all();
    $period = $period ?? ['key' => '30d', 'label' => 'Últimos 30 días', 'date_from' => now()->subDays(29)->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')];
    $periodOptions = [
        'today' => 'Día actual',
        '7d' => 'Últimos 7 días',
        '30d' => 'Últimos 30 días',
        'total' => 'Total',
        'custom' => 'Fecha personalizada',
    ];
    $periodQuery = [
        'period' => $period['key'] ?? '30d',
        'date_from' => $period['date_from'] ?? null,
        'date_to' => $period['date_to'] ?? null,
    ];
    $periodQuery = array_filter($periodQuery, fn ($value) => filled($value));
    $routeWithClient = fn (string $targetTab, array $extra = []) => route('social-media.index', array_merge([
        'tab' => $targetTab,
        'client_id' => $selectedClientId ?? 'all',
    ], $periodQuery, $extra));
    $clientLabel = ($selectedClientId ?? 'all') === 'all'
        ? 'Todos los clientes'
        : (string) (($selectedClient['name'] ?? null) ?: 'Cliente seleccionado');

    $formatCompact = function ($value) {
        $value = (float) $value;
        if ($value >= 1000000) return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
        if ($value >= 1000) return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
        return number_format($value);
    };
    $periodFilterForm = function (string $classes = '') use ($period, $periodOptions, $selectedClientId, $tab) {
        ob_start();
        ?>
        <form method="GET" action="<?= e(route('social-media.index')) ?>" class="<?= e($classes) ?>" data-social-period-form>
          <input type="hidden" name="tab" value="<?= e($tab) ?>">
          <input type="hidden" name="client_id" value="<?= e($selectedClientId ?? 'all') ?>">
          <select name="period" class="form-select sm-input !min-h-10" data-sm-select-size="period" data-social-period-select aria-label="Filtrar periodo">
            <?php foreach ($periodOptions as $key => $label): ?>
              <option value="<?= e($key) ?>" <?= (($period['key'] ?? '30d') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="<?= (($period['key'] ?? '30d') === 'custom') ? 'flex' : 'hidden' ?> flex-wrap items-center gap-2" data-custom-period-fields>
            <input type="date" name="date_from" value="<?= e($period['date_from'] ?? '') ?>" class="sm-input !min-h-10 !w-[150px]" aria-label="Fecha inicial">
            <input type="date" name="date_to" value="<?= e($period['date_to'] ?? '') ?>" class="sm-input !min-h-10 !w-[150px]" aria-label="Fecha final">
            <button type="submit" class="sm-btn sm-btn-soft !min-h-10 !px-4 text-xs">Aplicar</button>
          </div>
        </form>
        <?php
        return ob_get_clean();
    };

    $accountMetricTotal = function (array $account, array $needles) use ($insights) {
        $externalId = (string) ($account['external_id'] ?? '');
        return collect($insights)
            ->filter(function ($row) use ($externalId, $needles) {
                $metric = strtolower((string) ($row['metric'] ?? ''));
                return (string) ($row['external_account_id'] ?? '') === $externalId
                    && collect($needles)->contains(fn ($needle) => str_contains($metric, $needle));
            })
            ->sum(fn ($row) => (float) ($row['value'] ?? 0));
    };

    $visualAccounts = collect($accounts)->take(3)->map(function ($account) use ($platformLabels, $clientsById, $formatCompact, $accountMetricTotal) {
            $followers = $account['followers_count'] ?? $account['fan_count'] ?? null;
            $reachValue = $accountMetricTotal($account, ['reach', 'impressions', 'views']);
            return [
                'id' => $account['id'] ?? '',
                'platform' => $account['platform'] ?? 'meta',
                'type' => $account['type'] ?? 'cuenta',
                'name' => $platformLabels[$account['platform'] ?? ''] ?? ($account['name'] ?? 'Cuenta social'),
                'username' => $account['username'] ?? $account['external_id'] ?? '',
                'client_name' => $clientsById[$account['client_id'] ?? '']['name'] ?? ($account['name'] ?? 'Sin cliente'),
                'status' => $account['status'] ?? 'connected',
                'followers' => is_numeric($followers) && (float) $followers > 0 ? $formatCompact($followers) : 'Sin datos',
                'metric_label' => 'Alcance',
                'metric_value' => $reachValue > 0 ? $formatCompact($reachValue) : '0',
                'avatar_url' => $account['avatar_url'] ?? '',
            ];
        })->values()->all();

    $reach = (int) ($metrics['reach'] ?? 0);
    $interactions = (int) ($metrics['interactions'] ?? 0);
    $leadsCount = (int) ($metrics['leads'] ?? 0);
    $adSpend = (float) ($metrics['ad_spend'] ?? 0);
    $chartData = collect($performanceSeries ?? [])->values();
    $hasPerformanceData = $chartData->contains(fn ($row) => ((int) ($row['reach'] ?? 0) + (int) ($row['interactions'] ?? 0) + (int) ($row['leads'] ?? 0)) > 0);

    $activeAds = collect($ads)
        ->filter(fn ($ad) => in_array(strtoupper((string) ($ad['status'] ?? '')), ['ACTIVE', 'ENABLED'], true))
        ->take(5)
        ->values();

    $modeParam = request('mode', 'week');
    $dateParam = request('date');
    $baseDate = $dateParam ? Carbon::parse($dateParam) : Carbon::now();

    $weekStart = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
    $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

    $monthStart = $baseDate->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
    $monthDays = collect(range(0, 41))->map(fn ($i) => $monthStart->copy()->addDays($i));

    $prevWeekDate = $baseDate->copy()->subWeek()->format('Y-m-d');
    $nextWeekDate = $baseDate->copy()->addWeek()->format('Y-m-d');
    $prevMonthDate = $baseDate->copy()->subMonth()->format('Y-m-d');
    $nextMonthDate = $baseDate->copy()->addMonth()->format('Y-m-d');
    $calendarItemsByDate = collect($calendarItems)->filter(fn ($item) => !empty($item['scheduled_at']))->groupBy(fn ($item) => Carbon::parse($item['scheduled_at'])->format('Y-m-d'));
    $calendarPreview = collect($calendarItems)->filter(fn ($item) => !empty($item['scheduled_at']))->take(8)->values();

    $activityItems = collect($syncLogs)->take(4)->map(fn ($log) => [
        'platform' => $log['provider'] ?? 'meta',
        'title' => $log['message'] ?? 'Datos sincronizados',
        'subtitle' => ucfirst($log['status'] ?? 'social') . ' · ' . (!empty($log['created_at']) ? Carbon::parse($log['created_at'])->diffForHumans() : 'Ahora'),
    ]);
@endphp

<style>
  .sm-shell { --sm-lime:#ecfe88; --sm-lime-2:#f3fea4; --sm-ink:#0f172a; --sm-muted:#64748b; color:var(--sm-ink); }
  .sm-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 10px 28px rgba(15,23,42,.045); }
  .sm-card-tight { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 8px 22px rgba(15,23,42,.04); }
  .sm-btn { display:inline-flex; align-items:center; justify-content:center; gap:.55rem; min-height:42px; padding:.72rem 1.12rem; border-radius:999px; font-size:.86rem; line-height:1; font-weight:900; transition:transform .15s ease, box-shadow .15s ease, background .15s ease; white-space:nowrap; }
  .sm-btn:hover { transform:translateY(-1px); }
  .sm-btn-primary { background:var(--sm-lime); color:#0f172a; box-shadow:0 12px 24px rgba(132,204,22,.2); }
  .sm-btn-soft { background:#fff; color:#0f172a; border:1px solid #dbe3ef; box-shadow:0 8px 18px rgba(15,23,42,.045); }
  .sm-btn-dark { background:#0f172a; color:#fff; }
  .sm-nav-shell { background:#0f172a; border-color:rgba(255,255,255,.1); border-radius:24px; box-shadow:0 18px 40px rgba(15,23,42,.16); }
  .sm-side-link { display:flex; align-items:center; gap:.85rem; width:100%; padding:.88rem 1rem; border-radius:16px; color:rgba(255,255,255,.7); font-size:.9rem; font-weight:750; border:1px solid transparent; transition:background-color .18s ease,color .18s ease,border-color .18s ease; }
  .sm-side-link:hover { color:#fff; background:rgba(255,255,255,.08); }
  .sm-side-link.is-active { background:rgba(201,249,107,.18); color:#c9f96b; border-color:rgba(201,249,107,.3); }
  .sm-side-link svg { width:1.45rem; height:1.45rem; flex:0 0 auto; stroke-width:2; transition:transform .18s ease; }
  .sm-side-link:hover svg { transform:scale(1.08); }
  .sm-pill { display:inline-flex; align-items:center; gap:.35rem; border-radius:999px; padding:.25rem .55rem; font-size:.66rem; font-weight:950; background:#f8fafc; border:1px solid #e2e8f0; color:#475569; }
  .sm-pill-green { background:#ecfccb; border-color:#d9f99d; color:#3f6212; }
  .sm-pill-red { background:#ffe4e6; border-color:#fecdd3; color:#be123c; }
  .sm-pill-amber { background:#fef3c7; border-color:#fde68a; color:#92400e; }
  .sm-input { width:100%; min-height:44px; border:1px solid #dbe3ef; border-radius:14px; background:#fff; padding:.75rem .9rem; font-size:.9rem; font-weight:700; color:#334155; }
  .sm-input:focus { outline:none; border-color:#d9f99d; box-shadow:0 0 0 4px rgba(236,254,136,.5); }
  .sm-shell select.sm-input,
  .sm-shell select {
    -webkit-appearance:none;
    appearance:none;
    background-color:#fff;
    background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.4' d='m6 9 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat:no-repeat;
    background-position:right .9rem center;
    background-size:1rem 1rem;
    padding-right:2.6rem;
  }
  .sm-platform { width:38px; height:38px; border-radius:12px; display:grid; place-items:center; color:#fff; font-weight:950; box-shadow:inset 0 0 0 1px rgba(255,255,255,.35); flex:0 0 auto; }
  .sm-platform.instagram { background:linear-gradient(135deg,#f97316,#ec4899,#7c3aed); }
  .sm-platform.facebook { background:#1877f2; }
  .sm-platform.tiktok { background:#020617; box-shadow:4px 0 0 #22d3ee,-4px 0 0 #fb7185; }
  .sm-platform.meta,.sm-platform.meta_ads { background:#2563eb; }
  .sm-platform.small { width:26px; height:26px; border-radius:999px; font-size:.72rem; }
  .sm-kpi-icon { width:48px; height:48px; display:grid; place-items:center; border-radius:999px; background:#ecfccb; color:#365314; font-size:1.15rem; }
  .sm-kpi-icon.red { background:#ffe4e6; color:#be123c; }
  .sm-chart-wrap { height:308px; position:relative; }
  .sm-ad-thumb { width:36px; height:36px; border-radius:8px; object-fit:cover; background:#e2e8f0; }
  .sm-calendar-grid { display:grid; grid-template-columns:56px repeat(7,minmax(130px,1fr)); min-width:980px; }
  .sm-cal-cell { min-height:96px; border-right:1px solid #e2e8f0; border-bottom:1px solid #e2e8f0; padding:.7rem; }
  .sm-cal-event { border:1px solid #dbe3ef; background:#f8fafc; border-radius:10px; padding:.55rem; font-size:.72rem; font-weight:850; color:#0f172a; box-shadow:0 5px 14px rgba(15,23,42,.035); }
  .sm-section-title { font-size:1.25rem; line-height:1.2; font-weight:950; color:#020617; }
  .sm-stat-bar { height:9px; border-radius:999px; background:#eef2f7; overflow:hidden; }
  .sm-stat-bar > span { display:block; height:100%; border-radius:999px; background:#c9f96b; }
  .sm-filter-card { background:#fff; border:1px solid #dbe3ef; border-radius:18px; padding:.75rem; box-shadow:0 8px 18px rgba(15,23,42,.045); }
  .sm-social-preview { border-radius:18px; border:1px solid #e2e8f0; background:linear-gradient(180deg,#fff,#f8fafc); overflow:hidden; }
  .sm-social-preview-media { min-height:150px; background:linear-gradient(135deg,#0f172a,#334155 45%,#ecfe88); display:flex; align-items:center; justify-content:center; color:white; font-weight:950; }
  .sm-connect-card { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border:1px solid #e2e8f0; background:#fff; border-radius:18px; padding:1rem; transition:border-color .15s ease, transform .15s ease, box-shadow .15s ease; }
  .sm-connect-card:hover { border-color:#d9f99d; transform:translateY(-1px); box-shadow:0 12px 28px rgba(15,23,42,.06); }
  .sm-step-dot { width:28px; height:28px; border-radius:999px; display:grid; place-items:center; background:#ecfe88; color:#0f172a; font-size:.8rem; font-weight:950; flex:0 0 auto; }
  .sm-shell select.form-select:not([data-app-select-enhanced]) {
    position:absolute !important;
    width:1px !important;
    height:1px !important;
    padding:0 !important;
    margin:-1px !important;
    overflow:hidden !important;
    clip:rect(0, 0, 0, 0) !important;
    white-space:nowrap !important;
    border:0 !important;
    opacity:0 !important;
    pointer-events:none !important;
  }
  .sm-shell .app-select-trigger { min-height:42px; border-radius:12px; padding:.58rem .8rem; font-size:.85rem; font-weight:650; color:#334155; box-shadow:0 8px 18px rgba(15,23,42,.04); }
  .sm-shell .app-select-trigger.compact { min-height:40px; border-radius:12px; padding:.52rem .75rem; font-size:.82rem; font-weight:650; }
  .sm-shell .app-select-label { font-weight:650; }
  .sm-shell .app-select-option { font-size:.84rem; font-weight:650; }
  .sm-shell .app-select-option.is-selected { background:#ecfe88; color:#0f172a; }
  .sm-shell .app-select-wrap:has(select[data-sm-select-size="client"]) { width:min(100%, 300px); }
  .sm-shell .app-select-wrap:has(select[data-sm-select-size="account"]) { width:min(100%, 210px); }
  .sm-shell .app-select-wrap:has(select[data-sm-select-size="period"]) { width:min(100%, 175px); }
  .sm-planner-grid { display:grid; grid-template-columns:repeat(7,minmax(100px,1fr)); min-width:700px; background-color:#e2e8f0; gap:1px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
  .sm-planner-head { min-height:48px; background:#f8fafc; padding:.5rem; text-align:center; }
  .sm-planner-day { display:flex; flex-direction:column; justify-content:flex-start; align-items:stretch; min-height:100px; width:100%; background:#fff; padding:.5rem; text-align:left; transition:background .15s ease; position:relative; }
  .sm-planner-day:hover { background:#f8fafc; }
  .sm-content-event { display:flex; flex-direction:column; gap:.25rem; width:100%; border:1px solid #e2e8f0; border-radius:6px; background:#fff; box-shadow:0 1px 2px rgba(15,23,42,.03); text-align:left; padding:.4rem; transition:border-color .15s ease, box-shadow .15s ease; cursor:pointer; z-index:10; }
  .sm-content-event:hover { border-color:#cbd5e1; box-shadow:0 4px 6px -1px rgba(15,23,42,.05); }
  .sm-content-event-media { height:80px; border-radius:4px; background-color:#f1f5f9; background-size:cover; background-position:center; margin-bottom:.25rem; }
  .sm-content-event-body { display:block; width:100%; overflow:hidden; }
  .sm-mobile-tabs { scrollbar-width:none; }
  .sm-mobile-tabs::-webkit-scrollbar { display:none; }
  .sm-responsive-tabs { display:none; }
  .sm-bottom-tabs { display:none; }
  @media (min-width: 768px) and (max-width: 1279px) {
    .sm-side-link { padding:.8rem .85rem; border-radius:14px; font-size:.84rem; }
    .sm-side-link svg { width:1.3rem; height:1.3rem; }
  }
  @media (max-width: 767px) {
    .sm-shell { margin-inline:-.25rem; padding-bottom:6.25rem; }
    .sm-card { border-radius:16px; }
    .sm-chart-wrap { height:240px; }
    .sm-btn { width:100%; min-height:44px; }
    .sm-bottom-tabs {
      display:block;
      position:fixed;
      left:.75rem;
      right:.75rem;
      bottom:calc(.75rem + env(safe-area-inset-bottom));
      z-index:90;
    }
    .sm-bottom-tabs-inner {
      display:grid;
      grid-template-columns:repeat(6,minmax(0,1fr));
      gap:.2rem;
      padding:.5rem;
      border:1px solid rgba(255,255,255,.1);
      border-radius:1.35rem;
      background:#0f172a;
      color:#fff;
      box-shadow:0 18px 44px rgba(15,23,42,.28);
    }
    .sm-bottom-link {
      min-width:0;
      min-height:3.15rem;
      border-radius:.95rem;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:.16rem;
      color:rgba(255,255,255,.68);
      font-size:.56rem;
      line-height:1;
      font-weight:850;
      -webkit-tap-highlight-color:transparent;
    }
    .sm-bottom-link svg {
      width:1.24rem;
      height:1.24rem;
      stroke-width:2;
      flex:0 0 auto;
    }
    .sm-bottom-link span {
      display:block;
      width:100%;
      overflow:hidden;
      text-align:center;
      text-overflow:ellipsis;
      white-space:nowrap;
    }
    .sm-bottom-link.is-active {
      background:rgba(236,254,136,.12);
      color:#c9f96b;
    }
    .sm-bottom-link:focus {
      outline:none;
      box-shadow:none;
    }
    .sm-bottom-link:focus-visible {
      outline:none;
      box-shadow:0 0 0 2px rgba(236,254,136,.5);
    }
  }

  /* Meta-style Modal Specific Styles (smp-) */
  .smp-section { padding-bottom:1.25rem; border-bottom:1px solid #f1f5f9; margin-bottom:1.25rem; }
  .smp-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
  .smp-section-title { font-size:.85rem; font-weight:900; color:#0f172a; margin-bottom:.75rem; text-transform:uppercase; tracking:widest; }
  .smp-ctype-radio:checked + .smp-ctype-card { border-color:#2563eb; background:#eff6ff; box-shadow:inset 0 0 0 1px #2563eb; }
  .smp-toggle-track { background:#e2e8f0; }
  input:checked + .smp-toggle-track { background:#2563eb; }
  input:checked + .smp-toggle-track + .smp-toggle-thumb { transform:translateX(16px); }
  .smp-privacy-active { border-color:#2563eb; background:#eff6ff; }
  .smp-toolbar-btn { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:8px; color:#64748b; transition:all .15s; }
  .smp-toolbar-btn:hover { background:#f1f5f9; color:#0f172a; }
  .sm-input { width:100%; min-height:42px; border:1px solid #dbe3ef; border-radius:12px; background:#fff; padding:.65rem .85rem; font-size:.85rem; font-weight:600; color:#334155; }
  .sm-input:focus { outline:none; border-color:#60a5fa; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
</style>

@php
  $renderSocialTabIcon = function (string $key): string {
      return match ($key) {
          'resumen' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
          'cuentas' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 21a7 7 0 0 1 14 0"/></svg>',
          'estadisticas' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19V10m7 9V5m7 14v-7"/></svg>',
          'anuncios' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h3l9 5V6l-9 5H4v2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 13v5a2 2 0 0 0 2 2h1"/></svg>',
          'leads' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 21a6.5 6.5 0 0 1 13 0M19 8v6m3-3h-6"/></svg>',
          'calendario' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 15h.01M12 15h.01M16 15h.01"/></svg>',
          'configuracion' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
          default => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>',
      };
  };
@endphp

<div class="sm-shell max-w-[1500px] mx-auto space-y-5">

  <div class="sm-responsive-tabs xl:hidden -mx-1 overflow-x-auto sm-mobile-tabs pb-1">
    <div class="flex gap-2 px-1 min-w-max">
      @foreach($tabLabels as $key => $label)
        <a href="{{ $routeWithClient($key) }}" class="sm-side-link {{ $tab === $key ? 'is-active' : '' }} bg-slate-900">
          {!! $renderSocialTabIcon($key) !!}<span>{{ $label }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <nav class="sm-bottom-tabs" aria-label="Secciones de Social Manager">
    <div class="sm-bottom-tabs-inner">
      @foreach($tabLabels as $key => $label)
        <a href="{{ $routeWithClient($key) }}" class="sm-bottom-link {{ $tab === $key ? 'is-active' : '' }}" aria-label="{{ $label }}">
          {!! $renderSocialTabIcon($key) !!}
          <span>{{ $mobileTabLabels[$key] ?? $label }}</span>
        </a>
      @endforeach
    </div>
  </nav>

  @if(session('success') || session('error'))
    <div class="sm-card px-4 py-3 border-l-4 {{ session('error') ? 'border-rose-400 bg-rose-50 text-rose-700' : 'border-lime-300 bg-lime-50 text-lime-800' }}">
      <div class="text-sm font-black">{{ session('error') ?: session('success') }}</div>
    </div>
  @endif
  <div id="socialApiMessage" class="hidden sm-card px-4 py-3 text-sm font-black"></div>

  <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] lg:grid-cols-[240px_1fr] xl:grid-cols-[270px_1fr] gap-5">
    <aside class="hidden md:block space-y-4">
      <nav class="sm-card sm-nav-shell p-3">
        @foreach($tabLabels as $key => $label)
          <a href="{{ $routeWithClient($key) }}" class="sm-side-link {{ $tab === $key ? 'is-active' : '' }}">
            {!! $renderSocialTabIcon($key) !!}
            <span>{{ $label }}</span>
          </a>
        @endforeach
      </nav>

      <section class="sm-card p-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-black text-slate-950">Actividad reciente</h2>
          <a href="{{ $routeWithClient('configuracion') }}" class="text-xs font-black text-lime-700">Ver todo</a>
        </div>
        <div class="mt-4 space-y-4">
          @forelse($activityItems as $item)
            <div class="flex items-start gap-3">
              <span class="sm-platform small {{ $item['platform'] }}"><i class="fa-brands {{ ($item['platform'] ?? '') === 'instagram' ? 'fa-instagram' : (($item['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : (($item['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span>
              <div class="min-w-0">
                <div class="text-xs font-black text-slate-950 leading-tight">{{ $item['title'] }}</div>
                <div class="mt-1 text-[11px] font-semibold text-slate-400 leading-tight">{{ $item['subtitle'] }}</div>
              </div>
            </div>
          @empty
            <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs font-bold text-slate-400">Sin actividad real todavía.</p>
          @endforelse
        </div>
      </section>
    </aside>

    <main class="min-w-0 space-y-5">
      @if($tab === 'resumen')
        <section class="sm-card p-4 md:p-5">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-black text-slate-950">Cuentas conectadas</h2>
            <a href="{{ $routeWithClient('cuentas') }}" class="text-xs font-black text-lime-700">Gestionar cuentas</a>
          </div>
          <div class="mt-3 grid grid-cols-1 lg:grid-cols-3 gap-4">
            @forelse($visualAccounts as $account)
              <article class="sm-card-tight p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0">
                    @if(!empty($account['avatar_url']))
                      <img src="{{ $account['avatar_url'] }}" class="h-[38px] w-[38px] rounded-xl object-cover border border-slate-200" alt="">
                    @else
                      <span class="sm-platform {{ $account['platform'] }}"><i class="fa-brands {{ $account['platform'] === 'instagram' ? 'fa-instagram' : ($account['platform'] === 'facebook' ? 'fa-facebook-f' : ($account['platform'] === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span>
                    @endif
                    <div class="min-w-0">
                      <h3 class="text-sm font-black text-slate-950 truncate">{{ $account['name'] }}</h3>
                      <p class="text-xs font-bold text-slate-500 truncate">{{ $account['client_name'] }}</p>
                      <p class="text-[11px] font-bold text-slate-400 truncate">{{ $account['username'] ?: ucfirst($account['type']) }}</p>
                    </div>
                  </div>
                  <span class="sm-pill {{ ($account['status'] ?? '') === 'connected' ? 'sm-pill-green' : 'sm-pill-amber' }}">{{ $statusLabels[$account['status'] ?? 'connected'] ?? 'Estado' }}</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                  <div>
                    <div class="text-[11px] font-black text-slate-400">Seguidores</div>
                    <div class="mt-1 text-sm font-black text-slate-950">{{ $account['followers'] }}</div>
                  </div>
                  <div>
                    <div class="text-[11px] font-black text-slate-400">{{ $account['metric_label'] }}</div>
                    <div class="mt-1 text-sm font-black text-slate-950">{{ $account['metric_value'] }}</div>
                  </div>
                </div>
              </article>
            @empty
              <div class="lg:col-span-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center">
                <p class="text-sm font-black text-slate-600">No hay cuentas conectadas para esta vista.</p>
                <p class="mt-1 text-xs font-semibold text-slate-400">Conecta una cuenta real desde Cuentas para ver seguidores, alcance y métricas importadas.</p>
              </div>
            @endforelse
          </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <article class="sm-card p-5 flex items-start justify-between gap-4">
            <div><div class="text-xs font-black text-slate-500">Alcance (30 días)</div><div class="mt-5 text-2xl font-black">{{ $formatCompact($reach) }}</div><div class="mt-2 text-xs font-black text-slate-400">Datos sincronizados</div></div>
            <span class="sm-kpi-icon"><i class="fa-solid fa-users"></i></span>
          </article>
          <article class="sm-card p-5 flex items-start justify-between gap-4">
            <div><div class="text-xs font-black text-slate-500">Interacciones (30 días)</div><div class="mt-5 text-2xl font-black">{{ $formatCompact($interactions) }}</div><div class="mt-2 text-xs font-black text-slate-400">Datos sincronizados</div></div>
            <span class="sm-kpi-icon"><i class="fa-regular fa-heart"></i></span>
          </article>
          <article class="sm-card p-5 flex items-start justify-between gap-4">
            <div><div class="text-xs font-black text-slate-500">Leads generados</div><div class="mt-5 text-2xl font-black">{{ $formatCompact($leadsCount) }}</div><div class="mt-2 text-xs font-black text-slate-400">Meta Lead Ads</div></div>
            <span class="sm-kpi-icon"><i class="fa-solid fa-user-plus"></i></span>
          </article>
          <article class="sm-card p-5 flex items-start justify-between gap-4">
            <div><div class="text-xs font-black text-slate-500">Gasto en anuncios</div><div class="mt-5 text-2xl font-black">${{ number_format($adSpend, 2) }}</div><div class="mt-2 text-xs font-black text-slate-400">Campañas importadas</div></div>
            <span class="sm-kpi-icon red"><i class="fa-solid fa-dollar-sign"></i></span>
          </article>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-5">
          <div class="sm-card p-4 md:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div class="flex items-center gap-2">
                <h2 class="text-sm font-black text-slate-950">Rendimiento general</h2>
                <i class="fa-regular fa-circle-question text-xs text-slate-400"></i>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-[11px] font-bold text-slate-500">
                <span><i class="fa-solid fa-circle text-[#b7ee51] text-[8px]"></i> Alcance</span>
                <span><i class="fa-solid fa-circle text-[#2f8cff] text-[8px]"></i> Interacciones</span>
                <span><i class="fa-solid fa-circle text-[#a855f7] text-[8px]"></i> Leads</span>
                {!! $periodFilterForm('flex flex-wrap items-center gap-2') !!}
              </div>
            </div>
            <div class="sm-chart-wrap mt-4">
              <canvas id="socialPerformanceChart"></canvas>
              @if(!$hasPerformanceData)
                <div class="absolute inset-0 grid place-items-center pointer-events-none">
                  <div class="rounded-2xl border border-dashed border-slate-200 bg-white/90 px-5 py-4 text-center shadow-sm">
                    <p class="text-sm font-black text-slate-600">Aún no hay métricas reales sincronizadas.</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">Sincroniza Meta con permisos de insights para llenar esta gráfica.</p>
                  </div>
                </div>
              @endif
            </div>
          </div>

          <div class="sm-card p-4 md:p-5">
            <div class="flex items-center justify-between gap-3">
              <h2 class="text-sm font-black text-slate-950">Anuncios activos</h2>
              <a href="{{ $routeWithClient('anuncios') }}" class="text-xs font-black text-lime-700">Ver todos</a>
            </div>
            <div class="mt-4 overflow-x-auto">
              <table class="w-full min-w-[450px] text-left">
                <thead class="text-[11px] font-black text-slate-400">
                  <tr><th class="pb-3">Campaña</th><th class="pb-3">Plataforma</th><th class="pb-3">Estado</th><th class="pb-3 text-right">Gasto</th><th class="pb-3 text-right">ROAS</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @forelse($activeAds as $ad)
                    <tr class="text-xs font-bold text-slate-700">
                      <td class="py-3">
                        <div class="flex items-center gap-3">
                          @if(!empty($ad['thumbnail']))
                            <img src="{{ $ad['thumbnail'] }}" class="sm-ad-thumb" alt="">
                          @else
                            <span class="sm-ad-thumb grid place-items-center text-slate-400"><i class="fa-regular fa-image"></i></span>
                          @endif
                          <div><div class="font-black text-slate-950">{{ Str::limit($ad['name'] ?? 'Campaña', 24) }}</div><div class="text-[11px] text-slate-400">{{ $ad['objective'] ?? 'Campaña' }}</div></div>
                        </div>
                      </td>
                      <td class="py-3"><span class="sm-platform small {{ $ad['platform'] ?? 'meta_ads' }}"><i class="fa-brands {{ ($ad['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : (($ad['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : 'fa-meta') }}"></i></span></td>
                      <td class="py-3"><span class="sm-pill {{ strtoupper($ad['status'] ?? '') === 'ACTIVE' ? 'sm-pill-green' : 'sm-pill-amber' }}">{{ strtoupper($ad['status'] ?? 'ACTIVA') === 'ACTIVE' ? 'Activa' : 'Pausada' }}</span></td>
                      <td class="py-3 text-right font-black">${{ number_format((float) ($ad['spend'] ?? 0), 2) }}</td>
                      <td class="py-3 text-right font-black">{{ number_format((float) ($ad['roas'] ?? 0), 1) }}x</td>
                      <td class="py-3 text-right"><i class="fa-solid fa-ellipsis-vertical text-slate-400"></i></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="py-8 text-center text-sm font-bold text-slate-400">
                        No hay campañas reales importadas todavía.
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="sm-card overflow-hidden">
          <div class="p-4 md:p-5 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
              <h2 class="text-sm font-black text-slate-950">Calendario de contenido</h2>
              <button class="sm-btn sm-btn-soft !min-h-8 !py-1 !px-3 text-xs">Hoy</button>
              <span class="text-sm font-black text-slate-700">{{ $weekStart->format('d') }} – {{ $weekStart->copy()->addDays(6)->format('d M Y') }}</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <select class="form-select sm-input !min-h-10 !w-auto"><option>Semana</option></select>
              <select class="form-select sm-input !min-h-10 !w-auto"><option>Todas las cuentas</option></select>
              @if($canCreate)<a href="{{ $routeWithClient('calendario') }}#nuevo-contenido" class="sm-btn sm-btn-primary !min-h-10 text-xs w-auto">Crear publicación</a>@endif
            </div>
          </div>
          <div class="overflow-x-auto">
            <div class="sm-calendar-grid">
              <div class="border-r border-b border-slate-200 bg-slate-50"></div>
              @foreach($weekDays as $day)
                <div class="px-3 py-3 text-center border-r border-b border-slate-200 bg-slate-50">
                  <div class="text-xs font-bold text-slate-500">{{ ucfirst($day->locale('es')->isoFormat('ddd')) }}</div>
                  <div class="mx-auto mt-1 grid h-7 w-7 place-items-center rounded-full text-xs font-black {{ $day->isToday() ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-500' }}">{{ $day->format('d') }}</div>
                </div>
              @endforeach
              @foreach(['09:00', '12:00'] as $hourIndex => $hour)
                <div class="sm-cal-cell bg-slate-50 text-xs font-black text-slate-500">{{ $hour }}</div>
                @foreach($weekDays as $day)
                  <div class="sm-cal-cell">
                    @foreach($calendarPreview->filter(fn($item) => !empty($item['scheduled_at']) && Carbon::parse($item['scheduled_at'])->isSameDay($day) && (($hourIndex === 0 && Carbon::parse($item['scheduled_at'])->hour < 12) || ($hourIndex === 1 && Carbon::parse($item['scheduled_at'])->hour >= 12))) as $item)
                      <div class="sm-cal-event mb-2">
                        <div class="flex items-center gap-1.5"><span class="sm-platform small {{ $item['platform'] ?? 'meta' }}"><i class="fa-brands {{ ($item['platform'] ?? '') === 'instagram' ? 'fa-instagram' : (($item['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : (($item['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span><span class="truncate">{{ Str::limit($item['title'] ?? 'Contenido', 22) }}</span></div>
                        <div class="mt-1 text-[11px] text-slate-500">{{ Carbon::parse($item['scheduled_at'])->format('H:i') }}</div>
                        <span class="sm-pill sm-pill-green mt-2">{{ $statusLabels[$item['status'] ?? 'programado'] ?? 'Programado' }}</span>
                      </div>
                    @endforeach
                  </div>
                @endforeach
              @endforeach
            </div>
          </div>
        </section>
      @endif

      @if($tab === 'cuentas')
        @if($canCreate)
          <section class="sm-card p-5 md:p-6">
            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
              <div>
                <h2 class="sm-section-title">Conectar redes del cliente</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Primero elige el cliente del CRM. Después selecciona la red social y entra con el proveedor para importar sus cuentas reales.</p>
              </div>
              <span class="sm-pill {{ ($selectedClientId ?? 'all') === 'all' ? 'sm-pill-amber' : 'sm-pill-green' }}">Cliente actual: {{ $clientLabel }}</span>
            </div>

            <div class="mt-5 space-y-4">
              <form method="GET" action="{{ route('social-media.index') }}" class="sm-card-tight p-4">
                <input type="hidden" name="tab" value="cuentas">
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-4 lg:items-center">
                  <div class="flex items-center gap-3">
                    <span class="sm-step-dot">1</span>
                    <div>
                      <h3 class="font-black text-slate-950">Selecciona el cliente</h3>
                      <p class="text-xs font-semibold text-slate-500">Las cuentas que vincules quedarán guardadas dentro de este cliente.</p>
                    </div>
                  </div>
                  @php
                    $socialSelectableClients = collect($clients)->filter(fn($c) => in_array($c['id'], $socialClientIds ?? []))->values();
                  @endphp
                  <div class="flex items-center justify-start lg:justify-end">
                    <select name="client_id" class="form-select sm-input text-sm" data-sm-select-size="client" data-add-clients-action="1" onchange="this.form.submit()" required>
                      <option value="all">Seleccionar cliente</option>
                      @foreach($socialSelectableClients as $client)
                        <option value="{{ $client['id'] }}" @selected(($selectedClientId ?? 'all') === $client['id'])>{{ $client['name'] }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="mt-3">
                  @if(($selectedClientId ?? 'all') === 'all')
                    <p class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">Elige un cliente para activar los inicios de sesión por red social.</p>
                  @else
                    <p class="rounded-2xl border border-lime-200 bg-lime-50 px-3 py-2 text-xs font-bold text-lime-800">Las cuentas conectadas quedarán asignadas a {{ $clientLabel }}.</p>
                  @endif
                </div>
              </form>

              <div class="sm-card-tight p-4">
                <div class="flex items-center gap-3">
                  <span class="sm-step-dot">2</span>
                  <div>
                    <h3 class="font-black text-slate-950">Cuentas vinculadas</h3>
                    <p class="text-xs font-semibold text-slate-500">Cada fila muestra el estado de conexión por red. Si ya existe una cuenta, verás su perfil conectado.</p>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                  @php
                    $providerDisabled = ($selectedClientId ?? 'all') === 'all';
                    $selectedClientAccounts = collect($allAccounts ?? $accounts)->filter(fn ($account) => ($selectedClientId ?? 'all') !== 'all' && (string) ($account['client_id'] ?? '') === (string) ($selectedClientId ?? 'all'))->values();
                    $unassignedAccounts = collect($allAccounts ?? $accounts)->filter(fn ($account) => empty($account['client_id']))->values();
                    $accountsByPlatform = $selectedClientAccounts->groupBy(fn ($account) => (string) ($account['platform'] ?? ''));
                    $providerCards = [
                      ['platform' => 'instagram', 'title' => 'Instagram', 'description' => 'Entrar con Meta para importar cuentas Instagram Business vinculadas a páginas del cliente.', 'icon' => 'fa-instagram', 'route' => route('social-media.meta.connect', ['client_id' => $selectedClientId, 'platform' => 'instagram']), 'meta_provider' => true],
                      ['platform' => 'facebook', 'title' => 'Facebook', 'description' => 'Entrar con Facebook para importar páginas, formularios de leads y métricas del cliente.', 'icon' => 'fa-facebook-f', 'route' => route('social-media.meta.connect', ['client_id' => $selectedClientId, 'platform' => 'facebook']), 'meta_provider' => true],
                      ['platform' => 'meta_ads', 'title' => 'Meta Ads', 'description' => 'Entrar con Meta para importar cuentas publicitarias, campañas activas, inversión y rendimiento.', 'icon' => 'fa-meta', 'route' => route('social-media.meta.connect', ['client_id' => $selectedClientId, 'platform' => 'meta_ads']), 'meta_provider' => true],
                      ['platform' => 'tiktok', 'title' => 'TikTok', 'description' => 'Entrar con TikTok Login Kit para preparar la vinculación de la cuenta del cliente.', 'icon' => 'fa-tiktok', 'route' => route('social-media.tiktok.connect', ['client_id' => $selectedClientId]), 'meta_provider' => false],
                    ];
                  @endphp
                  @foreach($providerCards as $provider)
                    @php
                      $linkedAccount = ($accountsByPlatform->get($provider['platform']) ?? collect())->first();
                      $avatar = (string) ($linkedAccount['avatar_url'] ?? $linkedAccount['profile_picture_url'] ?? '');
                    @endphp
                    <article class="sm-connect-card !items-center">
                      <div class="flex min-w-0 flex-1 gap-3">
                        @if($linkedAccount && $avatar !== '')
                          <img src="{{ $avatar }}" alt="{{ $linkedAccount['name'] ?? $provider['title'] }}" class="h-[38px] w-[38px] shrink-0 rounded-xl object-cover ring-2 ring-lime-200">
                        @else
                          <span class="sm-platform {{ $provider['platform'] }}"><i class="fa-brands {{ $provider['icon'] }}"></i></span>
                        @endif
                        <div class="min-w-0">
                          <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-black text-slate-950">{{ $linkedAccount['name'] ?? $provider['title'] }}</h4>
                            @if($linkedAccount)
                              <span class="sm-pill sm-pill-green">{{ $statusLabels[$linkedAccount['status'] ?? 'connected'] ?? 'Conectado' }}</span>
                            @elseif($provider['meta_provider'] && !$metaConfigured)
                              <span class="sm-pill sm-pill-amber">App Meta pendiente</span>
                            @elseif($provider['platform'] === 'tiktok' && !$tiktokStatus['configured'])
                              <span class="sm-pill sm-pill-amber">App TikTok pendiente</span>
                            @else
                              <span class="sm-pill sm-pill-green">Listo para login</span>
                            @endif
                          </div>
                          @if($linkedAccount)
                            <p class="mt-1 text-sm font-semibold leading-relaxed text-slate-500">{{ $platformLabels[$linkedAccount['platform'] ?? ''] ?? $provider['title'] }} · {{ $linkedAccount['username'] ?? $linkedAccount['type'] ?? 'Cuenta conectada' }}</p>
                          @else
                            <p class="mt-1 text-sm font-semibold leading-relaxed text-slate-500">{{ $provider['description'] }}</p>
                          @endif
                        </div>
                      </div>
                      @if($providerDisabled)
                        <button type="button" class="sm-btn sm-btn-soft !min-h-10 !px-4 text-xs opacity-50 cursor-not-allowed" disabled>Elige cliente</button>
                      @elseif($linkedAccount)
                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                          <a href="{{ $provider['route'] }}" class="sm-btn sm-btn-soft !min-h-10 !px-4 text-xs sm:!w-auto">
                            <i class="fa-solid fa-rotate"></i>
                            Cambiar
                          </a>
                          <form method="POST" action="{{ route('social-media.accounts.update', $linkedAccount['id']) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="client_id" value="">
                            <input type="hidden" name="responsible_id" value="{{ $linkedAccount['responsible_id'] ?? '' }}">
                            <input type="hidden" name="redirect_client_id" value="{{ $selectedClientId }}">
                            <button type="submit" class="sm-btn sm-btn-soft !min-h-10 !px-4 text-xs text-rose-700 sm:!w-auto">
                              <i class="fa-solid fa-link-slash"></i>
                              Desvincular
                            </button>
                          </form>
                        </div>
                      @else
                        <a href="{{ $provider['route'] }}" class="sm-btn sm-btn-soft !min-h-10 !px-4 text-xs">
                          <i class="fa-solid fa-right-to-bracket"></i>
                          Iniciar sesión
                        </a>
                      @endif
                    </article>
                  @endforeach
                </div>

                @if(($selectedClientId ?? 'all') !== 'all')
                  <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                      <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-black text-slate-950">Vinculadas a {{ $clientLabel }}</h4>
                        <span class="sm-pill">{{ $selectedClientAccounts->count() }}</span>
                      </div>
                      <div class="mt-3 space-y-2">
                        @forelse($selectedClientAccounts as $account)
                          <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3">
                            <div class="min-w-0">
                              <div class="truncate text-sm font-black text-slate-950">{{ $account['name'] ?? 'Cuenta social' }}</div>
                              <div class="truncate text-xs font-bold text-slate-500">{{ $platformLabels[$account['platform'] ?? ''] ?? ucfirst($account['platform'] ?? 'Social') }} · {{ $account['username'] ?? $account['type'] ?? 'Cuenta' }}</div>
                            </div>
                            <form method="POST" action="{{ route('social-media.accounts.update', $account['id']) }}" class="shrink-0">
                              @csrf
                              @method('PUT')
                              <input type="hidden" name="client_id" value="">
                              <input type="hidden" name="responsible_id" value="{{ $account['responsible_id'] ?? '' }}">
                              <input type="hidden" name="redirect_client_id" value="{{ $selectedClientId }}">
                              <button type="submit" class="text-xs font-black text-rose-600 hover:text-rose-700">Desvincular</button>
                            </form>
                          </div>
                        @empty
                          <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-3 py-4 text-center text-xs font-bold text-slate-400">Este cliente aún no tiene cuentas vinculadas.</p>
                        @endforelse
                      </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                      <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-black text-slate-950">Cuentas sin cliente</h4>
                        <span class="sm-pill">{{ $unassignedAccounts->count() }}</span>
                      </div>
                      <div class="mt-3 space-y-2">
                        @forelse($unassignedAccounts as $account)
                          <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3">
                            <div class="min-w-0">
                              <div class="truncate text-sm font-black text-slate-950">{{ $account['name'] ?? 'Cuenta social' }}</div>
                              <div class="truncate text-xs font-bold text-slate-500">{{ $platformLabels[$account['platform'] ?? ''] ?? ucfirst($account['platform'] ?? 'Social') }} · {{ $account['username'] ?? $account['type'] ?? 'Cuenta' }}</div>
                            </div>
                            <form method="POST" action="{{ route('social-media.accounts.update', $account['id']) }}" class="shrink-0">
                              @csrf
                              @method('PUT')
                              <input type="hidden" name="client_id" value="{{ $selectedClientId }}">
                              <input type="hidden" name="responsible_id" value="{{ $account['responsible_id'] ?? '' }}">
                              <input type="hidden" name="redirect_client_id" value="{{ $selectedClientId }}">
                              <button type="submit" class="text-xs font-black text-lime-700 hover:text-lime-800">Vincular</button>
                            </form>
                          </div>
                        @empty
                          <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-3 py-4 text-center text-xs font-bold text-slate-400">No hay cuentas pendientes por asignar.</p>
                        @endforelse
                      </div>
                    </section>
                  </div>
                @endif
              </div>
            </div>
          </section>
        @endif
      @endif

      @if($tab === 'estadisticas')
        <section class="sm-card p-5 md:p-6">
          <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
            <div>
              <h2 class="text-2xl font-black text-slate-950">Estadísticas de {{ $clientLabel }}</h2>
              <p class="mt-1 text-sm font-semibold text-slate-500">Métricas por cuenta, audiencia, ciudades e inversión. Cambia el cliente desde el selector superior.</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:ml-auto sm:w-auto sm:flex-row sm:items-center sm:justify-end">
              <select class="form-select sm-input !min-h-10" data-sm-select-size="account">
                <option>Todas las cuentas</option>
                @foreach($accounts as $account)
                  <option>{{ $platformLabels[$account['platform'] ?? ''] ?? 'Social' }} · {{ $account['name'] ?? 'Cuenta' }}</option>
                @endforeach
              </select>
              {!! $periodFilterForm('flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end') !!}
            </div>
          </div>
          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <article class="sm-card-tight p-4"><div class="text-xs font-black text-slate-400 uppercase">Cuentas activas</div><div class="mt-2 text-3xl font-black">{{ count($accounts) }}</div><p class="mt-1 text-xs font-bold text-slate-500">Filtradas por cliente</p></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black text-slate-400 uppercase">Alcance</div><div class="mt-2 text-3xl font-black">{{ $formatCompact($reach) }}</div><p class="mt-1 text-xs font-bold text-slate-500">{{ $period['label'] ?? 'Periodo actual' }}</p></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black text-slate-400 uppercase">Inversión</div><div class="mt-2 text-3xl font-black">${{ number_format($adSpend, 2) }}</div><p class="mt-1 text-xs font-bold text-slate-500">Meta Ads / campañas activas</p></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black text-slate-400 uppercase">Costo por lead</div><div class="mt-2 text-3xl font-black">${{ number_format($leadsCount > 0 ? $adSpend / $leadsCount : 0, 2) }}</div><p class="mt-1 text-xs font-bold text-slate-500">{{ $leadsCount }} leads atribuidos</p></article>
          </div>
          <div class="mt-5 sm-card-tight p-4">
            <div class="sm-chart-wrap">
              <canvas id="socialPerformanceChart"></canvas>
              @if(!$hasPerformanceData)
                <div class="absolute inset-0 grid place-items-center pointer-events-none">
                  <div class="rounded-2xl border border-dashed border-slate-200 bg-white/90 px-5 py-4 text-center shadow-sm">
                    <p class="text-sm font-black text-slate-600">Aún no hay métricas reales sincronizadas.</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">Elige otro periodo o sincroniza Meta con permisos de insights.</p>
                  </div>
                </div>
              @endif
            </div>
          </div>

          <div class="mt-5 grid grid-cols-1 xl:grid-cols-3 gap-4">
            <article class="sm-card-tight p-5">
              <h3 class="text-sm font-black text-slate-950">Audiencia por género</h3>
              <div class="mt-4 space-y-4">
                @forelse($metrics['demographics'] ?? [] as $row)
                  <div>
                    <div class="flex items-center justify-between text-xs font-black text-slate-600"><span>{{ $row['label'] }}</span><span>{{ $row['value'] }}%</span></div>
                    <div class="sm-stat-bar mt-2"><span style="width:{{ $row['value'] }}%;background:{{ $row['color'] }}"></span></div>
                  </div>
                @empty
                  <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm font-bold text-slate-400">Meta todavía no devolvió datos reales de género para este periodo.</p>
                @endforelse
              </div>
            </article>
            <article class="sm-card-tight p-5">
              <h3 class="text-sm font-black text-slate-950">Ciudades principales</h3>
              <div class="mt-4 space-y-3">
                @forelse($metrics['city_breakdown'] ?? [] as $row)
                  @php $cityMax = max(1, collect($metrics['city_breakdown'] ?? [])->max('value')); @endphp
                  <div>
                    <div class="flex items-center justify-between text-xs font-black text-slate-600"><span>{{ $row['city'] }}</span><span>{{ $formatCompact($row['value']) }}</span></div>
                    <div class="sm-stat-bar mt-2"><span style="width:{{ min(100, round(($row['value'] / $cityMax) * 100)) }}%"></span></div>
                  </div>
                @empty
                  <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm font-bold text-slate-400">Meta todavía no devolvió ciudades reales para este periodo.</p>
                @endforelse
              </div>
            </article>
            <article class="sm-card-tight p-5">
              <h3 class="text-sm font-black text-slate-950">Inversión por plataforma</h3>
              <div class="mt-4 space-y-3">
                @forelse($metrics['investment_by_platform'] ?? [] as $platform => $value)
                  @php $investmentMax = max(1, collect($metrics['investment_by_platform'] ?? [])->max()); @endphp
                  <div>
                    <div class="flex items-center justify-between text-xs font-black text-slate-600"><span>{{ $platformLabels[$platform] ?? ucfirst($platform) }}</span><span>${{ number_format($value, 2) }}</span></div>
                    <div class="sm-stat-bar mt-2"><span style="width:{{ min(100, round(($value / $investmentMax) * 100)) }}%;background:#0f172a"></span></div>
                  </div>
                @empty
                  <p class="text-sm font-semibold text-slate-500">Sin inversión registrada todavía.</p>
                @endforelse
              </div>
            </article>
          </div>
          <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left">
              <thead class="text-xs uppercase tracking-[.14em] text-slate-400"><tr><th class="py-3 pr-4">Plataforma</th><th class="py-3 pr-4">Métrica</th><th class="py-3 pr-4">Valor</th><th class="py-3 pr-4">Periodo</th><th class="py-3 pr-4">Capturado</th></tr></thead>
              <tbody class="divide-y divide-slate-100">
                @forelse($insights as $row)
                  <tr class="text-sm font-semibold text-slate-700"><td class="py-3 pr-4">{{ $platformLabels[$row['platform'] ?? ''] ?? ucfirst($row['platform'] ?? 'Social') }}</td><td class="py-3 pr-4">{{ $row['title'] ?? $row['metric'] ?? 'Métrica' }}</td><td class="py-3 pr-4 font-black text-slate-950">{{ number_format((float) ($row['value'] ?? 0)) }}</td><td class="py-3 pr-4">{{ $row['period'] ?? '-' }}</td><td class="py-3 pr-4">{{ !empty($row['captured_at']) ? Carbon::parse($row['captured_at'])->format('d/m/Y') : '-' }}</td></tr>
                @empty
                  <tr><td colspan="5" class="py-10 text-center font-semibold text-slate-500">Sin métricas todavía. Sincroniza Meta cuando tengas permisos activos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if($tab === 'anuncios')
        <section class="sm-card p-5 md:p-6">
          <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
            <div>
              <h2 class="text-2xl font-black text-slate-950">Anuncios de {{ $clientLabel }}</h2>
              <p class="mt-1 text-sm font-semibold text-slate-500">Campañas importadas desde Meta Marketing API con inversión, alcance, clicks y ROAS.</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <select class="form-select sm-input !min-h-10 !w-auto"><option>Todas las plataformas</option><option>Facebook</option><option>Instagram</option><option>TikTok</option></select>
            </div>
          </div>
          <div class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-4">
            <article class="sm-card-tight p-4"><div class="text-xs font-black uppercase text-slate-400">Campañas</div><div class="mt-2 text-3xl font-black">{{ count($ads) }}</div></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black uppercase text-slate-400">Activas</div><div class="mt-2 text-3xl font-black">{{ collect($ads)->filter(fn($ad) => strtoupper($ad['status'] ?? '') === 'ACTIVE')->count() }}</div></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black uppercase text-slate-400">Inversión</div><div class="mt-2 text-3xl font-black">${{ number_format($adSpend, 2) }}</div></article>
            <article class="sm-card-tight p-4"><div class="text-xs font-black uppercase text-slate-400">Clicks</div><div class="mt-2 text-3xl font-black">{{ $formatCompact(collect($ads)->sum(fn($ad) => (int)($ad['clicks'] ?? 0))) }}</div></article>
          </div>
          <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($ads as $ad)
              <article class="sm-card-tight p-5">
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-black text-slate-950">{{ $ad['name'] ?? 'Campaña' }}</h3><p class="text-sm font-semibold text-slate-500">{{ $ad['objective'] ?? 'Objetivo sin definir' }}</p></div><span class="sm-pill {{ strtoupper($ad['status'] ?? '') === 'ACTIVE' ? 'sm-pill-green' : 'sm-pill-amber' }}">{{ $ad['status'] ?? 'Estado' }}</span></div>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3 text-sm"><div><div class="text-xs font-black text-slate-400 uppercase">Gasto</div><div class="font-black">${{ number_format((float)($ad['spend'] ?? 0), 2) }}</div></div><div><div class="text-xs font-black text-slate-400 uppercase">Alcance</div><div class="font-black">{{ number_format((int)($ad['reach'] ?? 0)) }}</div></div><div><div class="text-xs font-black text-slate-400 uppercase">Imp.</div><div class="font-black">{{ number_format((int)($ad['impressions'] ?? 0)) }}</div></div><div><div class="text-xs font-black text-slate-400 uppercase">Clicks</div><div class="font-black">{{ number_format((int)($ad['clicks'] ?? 0)) }}</div></div><div><div class="text-xs font-black text-slate-400 uppercase">CPC</div><div class="font-black">${{ number_format(((int)($ad['clicks'] ?? 0)) > 0 ? ((float)($ad['spend'] ?? 0) / (int)($ad['clicks'] ?? 1)) : 0, 2) }}</div></div></div>
              </article>
            @empty
              <div class="lg:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                <div class="text-lg font-black text-slate-950">No hay anuncios importados para {{ $clientLabel }}.</div>
                <p class="mt-2 text-sm font-semibold text-slate-500">Conecta Meta Ads y asigna la cuenta publicitaria al cliente para ver campañas, inversión y resultados.</p>
              </div>
            @endforelse
          </div>
        </section>
      @endif

      @if($tab === 'leads')
        <section class="sm-card p-5 md:p-6">
          <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
            <div class="min-w-0">
              <h2 class="text-2xl font-black text-slate-950">Leads de {{ $clientLabel }}</h2>
              <p class="mt-1 text-sm font-semibold text-slate-500">Leads capturados desde formularios sociales, con origen y conversión al CRM.</p>
            </div>
            <div class="grid w-full grid-cols-1 gap-2 sm:ml-auto sm:w-auto sm:grid-cols-[auto_220px] sm:justify-end xl:grid-cols-[auto_230px]">
              <button type="button" class="sm-btn sm-btn-primary sm-sync-btn shadow-sm hover:scale-105 transition-transform sm:!w-auto"><i class="fa-solid fa-arrows-rotate text-slate-950"></i><span>Recargar formulario</span></button>
              <select class="form-select sm-input !min-h-10 !w-full"><option>Todos los formularios</option></select>
            </div>
          </div>

          <div class="mt-5 space-y-4">
            @php
              $crmLeadsStore = new \App\Repositories\FileStore('leads.json');
              $crmLeadsData = collect($crmLeadsStore->all())->keyBy('id');

              $leadsByForm = collect($socialLeads)->map(function($lead) use ($crmLeadsData) {
                  if (($lead['status'] ?? '') === 'converted' && !empty($lead['crm_lead_id'])) {
                      $crm = $crmLeadsData->get($lead['crm_lead_id']);
                      $lead['crm_etapa'] = $crm['etapa'] ?? 'Posible cliente';
                  }
                  return $lead;
              })->groupBy(fn($lead) => $lead['form_name'] ?? 'Formulario General');
            @endphp

            @forelse($leadsByForm as $formName => $leadsInForm)
              @php
                $newLeadsCount = $leadsInForm->where('status', 'new')->count();
                $totalLeads = $leadsInForm->count();
                $formId = Str::slug($formName) . '-' . Str::random(4);
              @endphp
              <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all shadow-sm">
                <!-- Header / Toggle -->
                <button type="button" class="w-full flex items-center justify-between p-5 bg-slate-50 hover:bg-blue-50/50 transition-colors text-left group" onclick="document.getElementById('form_{{ $formId }}').classList.toggle('hidden'); this.querySelector('.fa-chevron-down').classList.toggle('rotate-180')">
                  <div>
                    <div class="flex items-center gap-3">
                      <h3 class="text-lg font-black text-slate-900 group-hover:text-blue-700 transition-colors">{{ $formName }}</h3>
                      @if($newLeadsCount > 0)
                        <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest shadow-sm">{{ $newLeadsCount }} Nuevos</span>
                      @endif
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $leadsInForm->first()['page_name'] ?? 'Página Meta' }} · {{ $totalLeads }} cliente(s) potencial(es)</p>
                  </div>
                  <div class="h-8 w-8 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 group-hover:border-blue-200 border border-transparent transition-all">
                    <i class="fa-solid fa-chevron-down transition-transform duration-300"></i>
                  </div>
                </button>

                <!-- Body / Leads List -->
                <div id="form_{{ $formId }}" class="{{ $newLeadsCount > 0 ? '' : 'hidden' }} border-t border-slate-200 divide-y divide-slate-100 bg-white">
                  @foreach($leadsInForm as $lead)
                    <div class="p-6 flex flex-col lg:flex-row lg:items-start gap-6 hover:bg-slate-50/30 transition-colors">
                      <!-- Lead Info -->
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                          <h4 class="font-black text-slate-950 text-base">{{ $lead['name'] ?? 'Lead social' }}</h4>
                          <span class="sm-pill {{ ($lead['status'] ?? '') === 'converted' ? 'sm-pill-green' : 'sm-pill-amber' }}">{{ $statusLabels[$lead['status'] ?? 'new'] ?? 'Nuevo' }}</span>
                        </div>
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                          <div class="flex items-center gap-2 text-sm font-semibold text-slate-600 bg-slate-50 rounded-lg px-3 py-2 border border-slate-100">
                            <i class="fa-regular fa-envelope text-slate-400"></i> <span class="truncate">{{ $lead['email'] ?? 'Sin email' }}</span>
                          </div>
                          <div class="flex items-center gap-2 text-sm font-semibold text-slate-600 bg-slate-50 rounded-lg px-3 py-2 border border-slate-100">
                            <i class="fa-solid fa-phone text-slate-400"></i> <span>{{ $lead['phone'] ?? 'Sin teléfono' }}</span>
                          </div>
                          <div class="flex items-center gap-2 text-sm font-semibold text-slate-600 bg-slate-50 rounded-lg px-3 py-2 border border-slate-100">
                            <i class="fa-regular fa-clock text-slate-400"></i> <span>{{ !empty($lead['created_time']) ? Carbon::parse($lead['created_time'])->timezone('America/Bogota')->format('d M Y, H:i') : 'Fecha desconocida' }}</span>
                          </div>
                        </div>

                        <!-- Raw Fields (Questions and Answers) -->
                        @if(!empty($lead['raw_fields']) && is_array($lead['raw_fields']))
                          @php
                            $filteredFields = collect($lead['raw_fields'])->except(['full_name', 'name', 'nombre', 'email', 'phone_number', 'phone', 'telefono']);
                          @endphp
                          @if($filteredFields->isNotEmpty())
                            <div class="mt-4 bg-[#f8fafc] rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                              <div class="absolute top-0 left-0 w-1 h-full bg-blue-400"></div>
                              <h5 class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2"><i class="fa-solid fa-clipboard-question text-blue-400"></i> Respuestas del formulario</h5>
                              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                @foreach($filteredFields as $key => $value)
                                  <div>
                                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">{{ str_replace('_', ' ', $key) }}</div>
                                    <div class="text-sm font-black text-slate-800 mt-1">{{ is_string($value) ? $value : json_encode($value) }}</div>
                                  </div>
                                @endforeach
                              </div>
                            </div>
                          @endif
                        @endif
                      </div>

                      <!-- Action -->
                      <div class="shrink-0 flex items-center justify-end lg:mt-0 mt-4">
                        @if($canUpdate && ($lead['status'] ?? '') !== 'converted')
                          <form method="POST" action="{{ route('api.social-media.leads.convert', $lead['id'] ?? '') }}">
                            @csrf
                            <button type="submit" class="sm-btn sm-btn-primary shadow-sm hover:scale-105 transition-transform"><i class="fa-solid fa-bolt mr-2 text-[#ecfe88]"></i> Convertir en CRM</button>
                          </form>
                        @else
                          <div class="flex items-center gap-2">
                            <span class="sm-pill sm-pill-green shadow-sm"><i class="fa-solid fa-check mr-1.5"></i> Vinculado</span>
                            <span class="sm-pill shadow-sm border border-slate-200 bg-white text-slate-700 font-bold"><i class="fa-solid fa-bars-progress mr-1.5 text-blue-500"></i> Etapa: {{ $lead['crm_etapa'] ?? 'Posible cliente' }}</span>
                          </div>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @empty
              <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                <div class="text-lg font-black text-slate-950">No hay leads sociales para {{ $clientLabel }}.</div>
                <p class="mt-2 text-sm font-semibold text-slate-500 mb-6">Cuando Meta Lead Ads sincronice formularios, aparecerán aquí listos para convertir al CRM.</p>
                <button type="button" class="sm-btn sm-btn-primary mx-auto sm-sync-btn shadow-sm hover:scale-105 transition-transform"><i class="fa-solid fa-arrows-rotate text-slate-950"></i><span>Recargar formulario</span></button>
              </div>
            @endforelse
          </div>
        </section>
      @endif

      @if($tab === 'calendario')
        <section id="nuevo-contenido" class="space-y-5">
          <div class="sm-card overflow-hidden">
            <div class="grid grid-cols-1 gap-4 border-b border-slate-200 p-4 md:p-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start xl:justify-between">
              <div class="min-w-0">
                <h2 class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-xl font-black leading-tight text-slate-950 md:text-2xl 2xl:flex-nowrap">
                  <span>Calendario</span>
                  <span class="text-slate-300">/</span>
                  <span class="text-slate-600 capitalize" id="socialCalendarTitle">
                    <span id="calTitleWeek" class="{{ $modeParam === 'month' ? 'hidden' : '' }}">
                      {{ $weekStart->locale('es')->isoFormat('D MMM') }} - {{ $weekStart->copy()->endOfWeek()->locale('es')->isoFormat('D MMM YYYY') }}
                    </span>
                    <span id="calTitleMonth" class="{{ $modeParam === 'week' ? 'hidden' : '' }}">
                      {{ $baseDate->locale('es')->isoFormat('MMMM YYYY') }}
                    </span>
                  </span>
                </h2>
                <p class="mt-1 max-w-2xl text-sm font-semibold text-slate-500 md:text-base">Cliente: <strong class="text-slate-700">{{ $clientLabel }}</strong>. Pulsa cualquier día para planificar.</p>
              </div>
              <div class="flex flex-wrap items-center justify-end gap-3 xl:pt-1">
                <div class="flex shrink-0 items-center gap-1 rounded-xl bg-slate-100 p-1">
                  <a href="{{ request()->fullUrlWithQuery(['date' => $modeParam === 'week' ? $prevWeekDate : $prevMonthDate, 'mode' => $modeParam, 'tab' => 'calendario']) }}"
                     id="calPrevBtn"
                     data-week-url="{{ request()->fullUrlWithQuery(['date' => $prevWeekDate, 'mode' => 'week', 'tab' => 'calendario']) }}"
                     data-month-url="{{ request()->fullUrlWithQuery(['date' => $prevMonthDate, 'mode' => 'month', 'tab' => 'calendario']) }}"
                     class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-900 shadow-sm transition-all" title="Anterior">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                  </a>
                  <a href="{{ request()->fullUrlWithQuery(['date' => Carbon::now()->format('Y-m-d'), 'mode' => $modeParam, 'tab' => 'calendario']) }}"
                     id="calTodayBtn"
                     data-week-url="{{ request()->fullUrlWithQuery(['date' => Carbon::now()->format('Y-m-d'), 'mode' => 'week', 'tab' => 'calendario']) }}"
                     data-month-url="{{ request()->fullUrlWithQuery(['date' => Carbon::now()->format('Y-m-d'), 'mode' => 'month', 'tab' => 'calendario']) }}"
                     class="px-3 h-8 flex items-center justify-center rounded-lg text-xs font-black text-slate-700 hover:bg-white shadow-sm transition-all">Hoy</a>
                  <a href="{{ request()->fullUrlWithQuery(['date' => $modeParam === 'week' ? $nextWeekDate : $nextMonthDate, 'mode' => $modeParam, 'tab' => 'calendario']) }}"
                     id="calNextBtn"
                     data-week-url="{{ request()->fullUrlWithQuery(['date' => $nextWeekDate, 'mode' => 'week', 'tab' => 'calendario']) }}"
                     data-month-url="{{ request()->fullUrlWithQuery(['date' => $nextMonthDate, 'mode' => 'month', 'tab' => 'calendario']) }}"
                     class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-900 shadow-sm transition-all" title="Siguiente">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                  </a>
                </div>
                <select id="socialCalendarMode" class="hidden" aria-hidden="true" tabindex="-1">
                  <option value="week" {{ $modeParam === 'week' ? 'selected' : '' }}>Semana</option>
                  <option value="month" {{ $modeParam === 'month' ? 'selected' : '' }}>Mes</option>
                </select>
                <div class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm" aria-label="Vista del calendario">
                  <a href="{{ request()->fullUrlWithQuery(['mode' => 'week', 'tab' => 'calendario']) }}"
                     class="rounded-lg px-3 py-2 text-xs font-black transition {{ $modeParam === 'week' ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}"
                     @if($modeParam === 'week') aria-current="page" @endif>Semana</a>
                  <a href="{{ request()->fullUrlWithQuery(['mode' => 'month', 'tab' => 'calendario']) }}"
                     class="rounded-lg px-3 py-2 text-xs font-black transition {{ $modeParam === 'month' ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}"
                     @if($modeParam === 'month') aria-current="page" @endif>Mes</a>
                </div>
                @if($canCreate)
                  <button type="button" class="sm-btn sm-btn-primary !min-h-10 w-auto shrink-0 text-xs" data-open-content-modal data-date="{{ Carbon::now()->format('Y-m-d') }}">Crear publicación</button>
                @endif
              </div>
            </div>

            <div id="socialCalendarWeek" class="overflow-x-auto custom-scroll {{ $modeParam === 'month' ? 'hidden' : '' }}">
              <div class="sm-calendar-grid bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="border-r border-b border-slate-200 bg-slate-50"></div>
                @foreach($weekDays as $day)
                  <div class="px-3 py-3 text-center border-r border-b border-slate-200 bg-slate-50">
                    <div class="text-xs font-black text-slate-500">{{ ucfirst($day->locale('es')->isoFormat('ddd')) }}</div>
                    <div class="mx-auto mt-1 grid h-8 w-8 place-items-center rounded-full text-sm font-black {{ $day->isToday() ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-600' }}">{{ $day->format('d') }}</div>
                  </div>
                @endforeach

                @php
                  // Generar slots de 0 a 23 horas
                  $timeSlots = collect(range(0, 23))->map(function($hour) {
                      return [
                          'label' => sprintf('%02d:00', $hour),
                          'start' => $hour,
                          'end' => $hour + 1
                      ];
                  })->all();
                @endphp

                @foreach($timeSlots as $slot)
                  <div class="sm-cal-cell bg-slate-50 flex items-start justify-end border-r border-b border-slate-200 !min-h-[70px] !p-2">
                    <span class="text-[10px] font-black text-slate-400 mt-1">{{ $slot['label'] }}</span>
                  </div>
                  @foreach($weekDays as $day)
                    @php
                      $dateKey = $day->format('Y-m-d');
                      $slotItems = $calendarItemsByDate->get($dateKey, collect())->filter(function($item) use ($slot) {
                        if (empty($item['scheduled_at'])) return false; // Ignorar items sin hora en vista horaria, o ponerlos en 0:00
                        $hour = Carbon::parse($item['scheduled_at'])->hour;
                        return $hour >= $slot['start'] && $hour < $slot['end'];
                      });
                    @endphp
                    <div class="sm-cal-cell border-r border-b border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors relative !min-h-[70px] !p-1.5" data-open-content-modal data-date="{{ $dateKey }}T{{ sprintf('%02d:00', $slot['start']) }}">
                      <div class="space-y-1">
                        @foreach($slotItems as $item)
                          @php
                            $pubStatus = $item['status'] ?? 'programado';
                            $isPublished = $pubStatus === 'publicado';
                            $isError = $pubStatus === 'error_publicacion';
                            $canPublishNow = in_array($pubStatus, ['programado', 'aprobado', 'error_publicacion']);
                          @endphp
                          <span class="sm-content-event" onclick="event.stopPropagation()">
                            @if(!empty($item['image_url']))
                              <span class="sm-content-event-media" style="background-image:url('{{ $item['image_url'] }}'); height: 60px;"></span>
                            @endif
                            <span class="sm-content-event-body">
                              <span class="flex items-center gap-1.5 mb-1">
                                <span class="sm-platform small {{ $item['platform'] ?? 'meta' }}" style="width:18px;height:18px;font-size:0.6rem;"><i class="fa-brands {{ ($item['platform'] ?? '') === 'instagram' ? 'fa-instagram' : (($item['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : (($item['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span>
                                <span class="truncate text-[11px] font-black text-slate-950">{{ Str::limit($item['title'] ?? 'Contenido', 18) }}</span>
                              </span>
                              <span class="flex items-center justify-between mt-1">
                                <span class="text-[10px] font-bold text-slate-500">{{ !empty($item['scheduled_at']) ? Carbon::parse($item['scheduled_at'])->format('H:i') : 'Sin hora' }}</span>
                                <span class="flex items-center gap-1">
                                  @if($isPublished)
                                    <span class="sm-pub-dot h-2 w-2 rounded-full bg-emerald-500" title="Publicado {{ !empty($item['published_at']) ? Carbon::parse($item['published_at'])->format('H:i') : '' }}"></span>
                                  @elseif($isError)
                                    <span class="sm-pub-dot h-2 w-2 rounded-full bg-rose-500" title="Error: {{ Str::limit($item['publish_error'] ?? '', 60) }}"></span>
                                  @else
                                    <span class="sm-pub-dot h-2 w-2 rounded-full bg-lime-400" title="{{ $statusLabels[$pubStatus] ?? $pubStatus }}"></span>
                                  @endif
                                  @if($canPublishNow && $canCreate)
                                    <button type="button" data-publish-now data-post-id="{{ $item['id'] }}" class="rounded px-1 py-0.5 text-[9px] font-black bg-lime-100 text-lime-700 hover:bg-lime-200 transition-colors" title="Publicar ahora"><i class="fa-solid fa-bolt"></i></button>
                                  @endif
                                </span>
                              </span>
                            </span>
                          </span>
                        @endforeach
                      </div>
                    </div>
                  @endforeach
                @endforeach
              </div>
            </div>

            <div id="socialCalendarMonth" class="overflow-x-auto custom-scroll {{ $modeParam === 'week' ? 'hidden' : '' }}">
              <div class="sm-planner-grid">
                @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $label)
                  <div class="sm-planner-head !min-h-12"><div class="text-xs font-black text-slate-500">{{ $label }}</div></div>
                @endforeach
                @foreach($monthDays as $day)
                  @php $dateKey = $day->format('Y-m-d'); $dayItems = $calendarItemsByDate->get($dateKey, collect()); @endphp
                  <button type="button" class="sm-planner-day {{ !$day->isSameMonth(Carbon::now()) ? 'bg-slate-50 text-slate-400' : '' }}" data-open-content-modal data-date="{{ $dateKey }}" aria-label="Añadir contenido el {{ $day->format('d/m/Y') }}">
                    <div class="mb-2 flex items-center justify-between">
                      @if($day->isSameMonth($baseDate))
                        <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-black {{ $day->isToday() ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-600 hover:bg-slate-100' }} transition-colors">{{ $day->format('d') }}</span>
                      @else
                        <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-black text-slate-300">{{ $day->format('d') }}</span>
                      @endif
                      @if(count($dayItems) > 0)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-500">{{ count($dayItems) }}</span>
                      @endif
                    </div>
                    <div class="space-y-2">
                      @foreach($dayItems->take(3) as $item)
                        @php
                          $pubStatus = $item['status'] ?? 'programado';
                          $isPublished = $pubStatus === 'publicado';
                          $isError = $pubStatus === 'error_publicacion';
                          $canPublishNow = in_array($pubStatus, ['programado', 'aprobado', 'error_publicacion']);
                        @endphp
                        <span class="sm-content-event" onclick="event.stopPropagation()">
                          <span class="sm-content-event-body">
                            <span class="flex items-center gap-1.5 mb-1">
                              <span class="sm-platform small {{ $item['platform'] ?? 'meta' }}" style="width:16px;height:16px;font-size:0.5rem;"><i class="fa-brands {{ ($item['platform'] ?? '') === 'instagram' ? 'fa-instagram' : (($item['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : (($item['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span>
                              <span class="truncate text-[10px] font-bold text-slate-950">{{ Str::limit($item['title'] ?? 'Contenido', 22) }}</span>
                            </span>
                            <span class="flex items-center justify-between">
                              <span class="text-[9px] font-bold text-slate-500">{{ !empty($item['scheduled_at']) ? Carbon::parse($item['scheduled_at'])->format('H:i') : '' }}</span>
                              <span class="flex items-center gap-1">
                                @if($isPublished)
                                  <span class="sm-pub-dot h-1.5 w-1.5 rounded-full bg-emerald-500" title="Publicado"></span>
                                @elseif($isError)
                                  <span class="sm-pub-dot h-1.5 w-1.5 rounded-full bg-rose-500" title="Error al publicar"></span>
                                @else
                                  <span class="sm-pub-dot h-1.5 w-1.5 rounded-full bg-lime-400" title="{{ $statusLabels[$pubStatus] ?? $pubStatus }}"></span>
                                @endif
                                @if($canPublishNow && $canCreate)
                                  <button type="button" data-publish-now data-post-id="{{ $item['id'] }}" class="rounded px-1 py-0.5 text-[8px] font-black bg-lime-100 text-lime-700 hover:bg-lime-200 transition-colors" title="Publicar ahora"><i class="fa-solid fa-bolt"></i></button>
                                @endif
                              </span>
                            </span>
                          </span>
                        </span>
                      @endforeach
                    </div>
                  </button>
                @endforeach
              </div>
            </div>
          </div>

          @if($calendarItems)
            <div class="sm-card p-5 md:p-6">
              <h3 class="text-xl font-black text-slate-950">Contenido planificado</h3>
              <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($calendarItems as $item)
                  @php
                    $pubStatus = $item['status'] ?? 'programado';
                    $isPublished = $pubStatus === 'publicado';
                    $isError = $pubStatus === 'error_publicacion';
                    $canPublishNow = in_array($pubStatus, ['programado', 'aprobado', 'error_publicacion']);
                    $statusPillClass = match($pubStatus) {
                      'publicado'          => 'sm-pill-green',
                      'aprobado'           => 'sm-pill-green',
                      'programado'         => 'sm-pill-amber',
                      'error_publicacion'  => 'sm-pill-red',
                      'cancelado'          => 'sm-pill-red',
                      default              => 'sm-pill-amber',
                    };
                    $statusIcon = match($pubStatus) {
                      'publicado'         => 'fa-check-circle',
                      'programado'        => 'fa-clock',
                      'aprobado'          => 'fa-circle-check',
                      'error_publicacion' => 'fa-circle-exclamation',
                      'cancelado'         => 'fa-ban',
                      'borrador'          => 'fa-pencil',
                      default             => 'fa-lightbulb',
                    };
                  @endphp
                  <article class="sm-content-event relative group">
                    <div class="sm-content-event-media !h-36" @if(!empty($item['image_url'])) style="background-image:url('{{ $item['image_url'] }}')"
                    @elseif(!empty($item['media_url'])) style="background-image:url('{{ $item['media_url'] }}')" @endif>
                      @if(!empty($item['content_type']) && $item['content_type'] !== 'post')
                        <span class="absolute top-2 left-2 rounded-lg bg-black/60 px-2 py-1 text-[10px] font-black text-white">
                          {{ $item['content_type'] === 'reel' ? '🎬 Reel' : '📱 Story' }}
                        </span>
                      @endif
                    </div>
                    <div class="p-4">
                      <div class="flex items-center justify-between gap-3">
                        <h4 class="truncate font-black text-slate-950">{{ $item['title'] ?? 'Contenido' }}</h4>
                        <span class="sm-pill {{ $statusPillClass }} flex items-center gap-1">
                          <i class="fa-solid {{ $statusIcon }} text-[10px]"></i>
                          {{ $statusLabels[$pubStatus] ?? $pubStatus }}
                        </span>
                      </div>
                      @if($isError && !empty($item['publish_error']))
                        <p class="mt-2 rounded-lg bg-rose-50 px-2 py-1.5 text-xs font-semibold text-rose-700"><i class="fa-solid fa-triangle-exclamation mr-1"></i>{{ Str::limit($item['publish_error'], 100) }}</p>
                      @endif
                      @if($isPublished && !empty($item['published_at']))
                        <p class="mt-2 text-xs font-semibold text-emerald-600"><i class="fa-solid fa-check-circle mr-1"></i>Publicado el {{ Carbon::parse($item['published_at'])->format('d/m/Y H:i') }}</p>
                      @endif
                      @if(!empty($item['caption']))<p class="mt-2 text-sm font-semibold text-slate-600">{{ Str::limit($item['caption'], 100) }}</p>@endif
                      <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2 text-xs font-bold text-slate-400">
                          <span><i class="fa-regular fa-clock mr-0.5"></i>{{ !empty($item['scheduled_at']) ? Carbon::parse($item['scheduled_at'])->format('d/m/Y H:i') : 'Sin fecha' }}</span>
                          <span class="sm-platform small {{ $item['platform'] ?? 'meta' }}" style="width:20px;height:20px;font-size:0.65rem;"><i class="fa-brands {{ ($item['platform'] ?? '') === 'instagram' ? 'fa-instagram' : (($item['platform'] ?? '') === 'facebook' ? 'fa-facebook-f' : (($item['platform'] ?? '') === 'tiktok' ? 'fa-tiktok' : 'fa-meta')) }}"></i></span>
                        </div>
                        @if($canPublishNow && $canCreate)
                          <button type="button" data-publish-now data-post-id="{{ $item['id'] }}" class="sm-btn sm-btn-soft !min-h-8 !px-3 !py-1 text-xs font-black text-lime-700 border-lime-200 hover:bg-lime-50">
                            <i class="fa-solid fa-bolt mr-1"></i> Publicar ahora
                          </button>
                        @endif
                        @if($isPublished && !empty($item['external_post_id']))
                          <span class="text-[10px] font-bold text-slate-400">ID: {{ $item['external_post_id'] }}</span>
                        @endif
                      </div>
                    </div>
                  </article>
                @endforeach
              </div>
            </div>
          @endif

          @if($canCreate)
            {{-- ═══════════════════════════════════════════════════════════════
                 MODAL: CREAR PUBLICACIÓN  —  estilo Meta Business Suite
            ═══════════════════════════════════════════════════════════════ --}}
            <div id="socialContentModal"
                 class="fixed inset-0 hidden items-start justify-center bg-slate-950/60 backdrop-blur-sm"
                 style="z-index:2147483500;padding-top:clamp(1rem,3vh,4rem)"
                 role="dialog" aria-modal="true" aria-labelledby="socialContentModalTitle">

              {{-- Shell: left panel + right preview --}}
              <div class="relative flex w-full max-w-[960px] mx-4 flex-col overflow-hidden rounded-[20px] bg-white shadow-2xl"
                   style="max-height:92vh" role="document">

                {{-- ── Header ─────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between border-b border-slate-100 bg-white px-6 py-4 shrink-0">
                  <h3 id="socialContentModalTitle" class="text-[1.1rem] font-black text-slate-950">Crear publicación</h3>
                  <button type="button" id="socialContentModalClose"
                          class="grid h-9 w-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100 transition-colors"
                          aria-label="Cerrar">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4l10 10M14 4L4 14"/></svg>
                  </button>
                </div>

                {{-- ── Body: two-column layout ──────────────────────────────── --}}
                <form id="smCreatePostForm"
                      method="POST" action="{{ route('social-media.calendar.store') }}"
                      class="flex flex-col md:flex-row flex-1 min-h-0 overflow-hidden">
                  @csrf

                  {{-- LEFT: scrollable form --}}
                  <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    {{-- ①  PUBLICAR EN ──────────────────────────────────── --}}
                    <div class="smp-section">
                      <div class="smp-section-title">Publicar en</div>

                      {{-- Trigger button --}}
                      <button type="button" id="smAccountDropBtn"
                              class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold text-slate-800 hover:border-slate-300 transition-colors">
                        <span id="smAccountDropLabel" class="flex items-center gap-2">
                          <span class="text-slate-400">Seleccionar cuentas</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
                      </button>

                      {{-- Dropdown panel (hidden by default) --}}
                      <div id="smAccountDropPanel"
                           class="hidden mt-1 rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                        @php
                          $publishableAccounts = collect($accounts)->filter(fn($a) =>
                            in_array($a['platform'] ?? '', ['facebook', 'instagram'], true)
                            && ($a['status'] ?? '') === 'connected'
                          );
                        @endphp
                        @if($publishableAccounts->isEmpty())
                          <div class="px-4 py-3 text-sm font-semibold text-slate-500">
                            <i class="fa-solid fa-triangle-exclamation text-amber-400 mr-1"></i>
                            No hay cuentas de Facebook/Instagram conectadas.
                            <a href="{{ route('settings.social_media') }}" class="text-blue-600 hover:underline ml-1">Configurar Meta</a>
                          </div>
                        @else
                          <div class="px-4 py-2 border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Publicar en Facebook e Instagram
                          </div>
                          @foreach($publishableAccounts as $acc)
                            <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors border-b border-slate-50">
                              <input type="checkbox" name="account_ids[]"
                                     value="{{ $acc['id'] }}"
                                     data-platform="{{ $acc['platform'] }}"
                                     data-name="{{ $acc['name'] ?? $acc['username'] ?? 'Cuenta' }}"
                                     class="smp-account-check w-4 h-4 rounded border-slate-300 accent-blue-600">
                              <div class="flex items-center gap-2 flex-1 min-w-0">
                                {{-- avatar placeholder with platform ring --}}
                                <div class="relative">
                                  <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-slate-600 font-black text-sm shrink-0">
                                    {{ strtoupper(substr($acc['name'] ?? 'C', 0, 1)) }}
                                  </div>
                                  <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full flex items-center justify-center text-white text-[8px]
                                    {{ ($acc['platform'] ?? '') === 'instagram' ? 'bg-gradient-to-br from-orange-500 via-pink-500 to-purple-600' : 'bg-[#1877f2]' }}">
                                    <i class="fa-brands {{ ($acc['platform'] ?? '') === 'instagram' ? 'fa-instagram' : 'fa-facebook-f' }}"></i>
                                  </span>
                                </div>
                                <div class="min-w-0">
                                  <div class="text-sm font-black text-slate-900 truncate">{{ $acc['name'] ?? 'Cuenta' }}</div>
                                  <div class="text-[11px] font-semibold text-slate-500">
                                    {{ ucfirst($acc['platform'] ?? '') }} · {{ ($acc['status'] ?? '') === 'connected' ? 'Conectado' : 'Sin conexión' }}
                                  </div>
                                </div>
                              </div>
                            </label>
                          @endforeach
                        @endif

                        {{-- TikTok: coming soon --}}
                        @if(collect($accounts)->where('platform', 'tiktok')->isNotEmpty())
                          <div class="flex items-center gap-3 px-4 py-3 opacity-50">
                            <div class="relative">
                              <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm shrink-0">TK</div>
                              <span class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-slate-900 flex items-center justify-center text-white text-[8px]"><i class="fa-brands fa-tiktok"></i></span>
                            </div>
                            <div>
                              <div class="text-sm font-black text-slate-600">TikTok</div>
                              <div class="text-[11px] text-slate-400 font-semibold">Publicación automática próximamente</div>
                            </div>
                          </div>
                        @endif
                      </div>

                      {{-- Hidden: primary account_id for the controller --}}
                      <input type="hidden" name="account_id" id="smPrimaryAccountId">
                      {{-- Hidden: primary platform (derived from first checked) --}}
                      <input type="hidden" name="platform" id="smPrimaryPlatform" value="instagram">
                    </div>

                    {{-- ②  TIPO DE CONTENIDO ────────────────────────────── --}}
                    <div class="smp-section">
                      <div class="smp-section-title">Tipo de contenido</div>
                      <div class="flex gap-2" role="group" aria-label="Tipo de contenido">
                        @foreach([
                          ['post','<i class="fa-solid fa-camera"></i>','Post','Imagen / foto'],
                          ['reel','<i class="fa-solid fa-clapperboard"></i>','Reel','Video corto'],
                          ['story','<i class="fa-solid fa-mobile-screen"></i>','Historia','Story 24h']
                        ] as [$val,$icon,$label,$sub])
                          <label class="smp-ctype-tab flex-1 cursor-pointer">
                            <input type="radio" name="content_type" value="{{ $val }}" {{ $val === 'post' ? 'checked' : '' }} class="sr-only smp-ctype-radio">
                            <div class="smp-ctype-card rounded-xl border-2 border-slate-200 bg-white p-2.5 text-center transition-all hover:border-slate-300">
                              <div class="text-xl mb-0.5 text-slate-700">{!! $icon !!}</div>
                              <div class="text-[11px] font-black text-slate-800">{{ $label }}</div>
                              <div class="text-[9px] font-semibold text-slate-400">{{ $sub }}</div>
                            </div>
                          </label>
                        @endforeach
                      </div>
                    </div>

                    {{-- ③  CONTENIDO MULTIMEDIA ────────────────────────── --}}
                    <div class="smp-section">
                      <div class="smp-section-title">Contenido multimedia</div>
                      <p class="text-[12px] font-semibold text-slate-500 mb-3">
                        Comparte fotos y vídeos. Las publicaciones de Instagram no pueden incluir más de diez fotos.
                      </p>

                      {{-- Drop zone (visual only — uploads via URL for now) --}}
                      <div id="smMediaDropZone"
                           class="relative rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-5 text-center hover:border-blue-300 hover:bg-blue-50/30 transition-colors cursor-pointer">
                        <div id="smMediaDropContent">
                          <i class="fa-regular fa-image text-3xl text-slate-300 mb-2 block"></i>
                          <p class="text-sm font-bold text-slate-500">Arrastra una imagen o vídeo aquí</p>
                          <p class="text-[11px] font-semibold text-slate-400 mt-0.5">JPG, PNG, GIF, MP4, MOV — máx. 4 GB</p>
                        </div>
                        {{-- Preview when URL provided --}}
                        <div id="smMediaPreviewZone" class="hidden">
                          <img id="smMediaPreviewImg" src="" alt="" class="max-h-48 mx-auto rounded-lg object-cover shadow-sm">
                          <video id="smMediaPreviewVid" src="" class="hidden max-h-48 mx-auto rounded-lg shadow-sm" controls></video>
                          <button type="button" id="smMediaClearBtn"
                                  class="mt-2 text-[11px] font-black text-rose-500 hover:text-rose-700">
                            <i class="fa-solid fa-trash-can mr-1"></i>Eliminar
                          </button>
                        </div>
                      </div>

                      {{-- URL inputs (hidden, now handled by Media Library) --}}
                      <div class="mt-3 space-y-2 hidden">
                        <div>
                          <label class="block text-[11px] font-black uppercase tracking-[.1em] text-slate-400 mb-1">URL imagen / portada</label>
                          <input name="image_url" id="smModalImageUrl" type="url" class="sm-input text-sm"
                                 placeholder="https://... (imagen pública accesible por Meta)">
                        </div>
                        <div id="smVideoUrlWrap">
                          <label class="block text-[11px] font-black uppercase tracking-[.1em] text-slate-400 mb-1">
                            URL vídeo <span class="text-slate-300 font-semibold normal-case">(reels y stories de vídeo)</span>
                          </label>
                          <input name="media_url" id="smModalMediaUrl" type="url" class="sm-input text-sm"
                                 placeholder="https://res.cloudinary.com/... o URL pública de vídeo">
                        </div>
                        {{-- Cloudinary (ready for activation) --}}
                        <input type="hidden" name="cloudinary_public_id" value="">
                        <input type="hidden" name="cloudinary_resource_type" value="image">
                      </div>
                    </div>

                    {{-- ④  TEXTO / COPY ────────────────────────────────── --}}
                    <div class="smp-section">
                      <div class="smp-section-title">Texto</div>
                      {{-- Caption textarea with toolbar --}}
                      <div class="rounded-xl border border-slate-200 bg-white overflow-hidden focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                        <textarea id="smModalCaption" name="caption" rows="5"
                                  class="w-full resize-none border-0 bg-transparent px-4 pt-3 pb-2 text-sm font-semibold text-slate-800 placeholder:text-slate-400 focus:outline-none"
                                  placeholder="¿Qué quieres publicar? Añade tu copy, hashtags, emojis..."></textarea>
                        {{-- Toolbar --}}
                        <div class="flex items-center justify-between border-t border-slate-100 px-3 py-2">
                          <div class="flex items-center gap-1">
                            <button type="button" class="smp-toolbar-btn" title="Emojis" onclick="smInsertEmoji()">😊</button>
                            <button type="button" class="smp-toolbar-btn" title="Hashtag" onclick="smInsertText('#')"><span class="font-black text-slate-600">#</span></button>
                            <button type="button" class="smp-toolbar-btn" title="Mención" onclick="smInsertText('@')"><span class="font-black text-slate-600">@</span></button>
                            <div class="w-px h-4 bg-slate-200 mx-1"></div>
                            <button type="button" class="smp-toolbar-btn text-[13px] font-black text-slate-500" title="Negrita" onclick="smInsertFormat('**')"><b>B</b></button>
                          </div>
                          <span id="smCaptionCounter" class="text-[11px] font-bold text-slate-400">0 / 2200</span>
                        </div>
                      </div>

                      {{-- Personalize per platform toggle --}}
                      <label class="flex items-center gap-3 mt-3 cursor-pointer">
                        <div class="relative">
                          <input type="checkbox" id="smCustomizePerPlatform" class="sr-only">
                          <div class="w-10 h-6 rounded-full bg-slate-200 transition-colors smp-toggle-track"></div>
                          <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform smp-toggle-thumb"></div>
                        </div>
                        <span class="text-sm font-bold text-slate-700">Personaliza el texto para Facebook e Instagram</span>
                      </label>

                      {{-- Per-platform captions (hidden unless toggle on) --}}
                      <div id="smPerPlatformCaptions" class="hidden mt-3 space-y-3">
                        <div class="rounded-xl border border-[#1877f2]/20 bg-blue-50/40 p-3">
                          <div class="flex items-center gap-2 mb-2">
                            <span class="w-5 h-5 rounded-full bg-[#1877f2] grid place-items-center text-white text-[9px]"><i class="fa-brands fa-facebook-f"></i></span>
                            <span class="text-[11px] font-black text-blue-800">Texto para Facebook</span>
                          </div>
                          <textarea name="caption_facebook" rows="3" class="w-full text-sm font-semibold text-slate-800 bg-transparent border-0 resize-none focus:outline-none placeholder:text-slate-400" placeholder="Texto específico para Facebook (deja vacío para usar el texto principal)"></textarea>
                        </div>
                        <div class="rounded-xl border border-pink-200/60 bg-pink-50/30 p-3">
                          <div class="flex items-center gap-2 mb-2">
                            <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-500 via-pink-500 to-purple-600 grid place-items-center text-white text-[9px]"><i class="fa-brands fa-instagram"></i></span>
                            <span class="text-[11px] font-black text-pink-800">Texto para Instagram</span>
                          </div>
                          <textarea name="caption_instagram" rows="3" class="w-full text-sm font-semibold text-slate-800 bg-transparent border-0 resize-none focus:outline-none placeholder:text-slate-400" placeholder="Texto específico para Instagram (deja vacío para usar el texto principal)"></textarea>
                        </div>
                      </div>
                    </div>

                    {{-- ⑤  PROGRAMAR ────────────────────────────────────── --}}
                    <div class="smp-section">
                      <div class="flex items-center justify-between">
                        <span class="smp-section-title">Programar</span>
                        <label class="flex items-center gap-2 cursor-pointer">
                          <span class="text-sm font-bold text-slate-600">Definir fecha y hora</span>
                          <div class="relative">
                            <input type="checkbox" id="smScheduleToggle" class="sr-only">
                            <div class="w-10 h-6 rounded-full bg-slate-200 transition-colors smp-toggle-track"></div>
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform smp-toggle-thumb"></div>
                          </div>
                        </label>
                      </div>
                      <div id="smScheduleDateWrap" class="hidden mt-3">
                        <input id="socialContentScheduledAt" type="datetime-local" name="scheduled_at"
                               class="sm-input text-sm"
                               min="{{ now()->addMinutes(10)->format('Y-m-d\TH:i') }}">
                        <p class="mt-1 text-[11px] font-semibold text-slate-400">
                          <i class="fa-solid fa-circle-info mr-1"></i>
                          El sistema publicará automáticamente a la hora indicada.
                        </p>
                      </div>
                    </div>

                    {{-- ⑥  PRIVACIDAD ──────────────────────────────────── --}}
                    <div class="smp-section">
                      <div class="smp-section-title">Configuración de privacidad</div>
                      <p class="text-[12px] font-semibold text-slate-500 mb-3">
                        Controla quién puede ver tu publicación en el feed y en el perfil.
                      </p>
                      @foreach([['public','Pública','Todo el mundo, dentro y fuera de Facebook.'],['restricted','Restringida','Elige a personas específicas para que puedan verla.']] as [$val,$label,$desc])
                        <label class="flex items-center gap-3 rounded-xl border-2 border-slate-200 bg-white px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors mb-2 {{ $val === 'public' ? 'smp-privacy-active' : '' }}">
                          <input type="radio" name="privacy" value="{{ $val }}" {{ $val === 'public' ? 'checked' : '' }}
                                 class="w-4 h-4 accent-blue-600 smp-privacy-radio">
                          <div>
                            <div class="text-sm font-black text-slate-900">{{ $label }}</div>
                            <div class="text-[11px] font-semibold text-slate-500">{{ $desc }}</div>
                          </div>
                        </label>
                      @endforeach
                    </div>

                    {{-- ⑦  CRM: campos internos ──────────────────────────── --}}
                    <details class="smp-section">
                      <summary class="cursor-pointer text-[11px] font-black uppercase tracking-[.1em] text-slate-400 hover:text-slate-600 list-none flex items-center gap-1">
                        <i class="fa-solid fa-sliders text-xs"></i> Campos internos CRM
                        <svg class="w-3 h-3 ml-auto" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 3 3 3-3"/></svg>
                      </summary>
                      <div class="mt-3 space-y-2">
                        <input name="title" id="smModalTitle" class="sm-input text-sm" placeholder="Título interno (identificador en el CRM)" required>
                        <div class="grid grid-cols-2 gap-2">
                          <select name="client_id" class="form-select sm-input text-sm" required>
                            <option value="">Cliente</option>
                            @foreach(collect($clients)->filter(fn($c) => in_array($c['id'], $socialClientIds ?? [])) as $client)
                              <option value="{{ $client['id'] }}" @selected(($selectedClientId ?? 'all') === $client['id'])>{{ $client['name'] }}</option>
                            @endforeach
                          </select>
                          <select name="responsible_id" class="form-select sm-input text-sm">
                            <option value="">Responsable</option>
                            @foreach($users as $user)
                              <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    </details>

                    {{-- Hidden: status (set by footer buttons) --}}
                    <input type="hidden" name="status" id="smModalStatus" value="programado">
                  </div>

                  {{-- RIGHT: live preview ──────────────────────────────── --}}
                  <div class="hidden md:flex w-[380px] shrink-0 flex-col bg-slate-50 border-l border-slate-100">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                      <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Vista previa</span>
                      <div class="flex items-center gap-1 hidden" id="smPreviewNav">
                        <button type="button" id="smPreviewPrev" class="w-6 h-6 rounded flex items-center justify-center bg-slate-200 hover:bg-slate-300 text-slate-600 transition-colors"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <span id="smPreviewCounter" class="text-[10px] font-bold text-slate-500 w-8 text-center">1/2</span>
                        <button type="button" id="smPreviewNext" class="w-6 h-6 rounded flex items-center justify-center bg-slate-200 hover:bg-slate-300 text-slate-600 transition-colors"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                      </div>
                    </div>
                    {{-- Phone mockup --}}
                    <div class="flex-1 overflow-y-auto p-3">
                      <div class="mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" style="max-width:220px">
                        {{-- Post header --}}
                        <div class="flex items-center gap-2 p-3">
                          <div id="smPreviewAvatar" class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 via-pink-500 to-purple-600 flex items-center justify-center text-white text-xs font-black shrink-0">
                            <i class="fa-brands fa-instagram"></i>
                          </div>
                          <div>
                            <div id="smPreviewAccountName" class="text-[11px] font-black text-slate-900">Tu cuenta</div>
                            <div id="smPreviewContentType" class="text-[9px] font-semibold text-slate-400"><i class="fa-solid fa-camera"></i> Post · Público</div>
                          </div>
                          <div class="ml-auto text-slate-300 text-xs">···</div>
                        </div>
                        {{-- Media --}}
                        <div id="smPreviewMedia"
                             class="w-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center"
                             style="aspect-ratio:1;background-size:cover;background-position:center">
                          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </div>
                        {{-- Caption preview --}}
                        <div class="p-3">
                          <div class="flex items-center gap-3 text-slate-400 text-base mb-2">
                            <i class="fa-regular fa-heart"></i>
                            <i class="fa-regular fa-comment"></i>
                            <i class="fa-regular fa-paper-plane"></i>
                            <i class="fa-regular fa-bookmark ml-auto"></i>
                          </div>
                          <p id="smPreviewCaption" class="text-[10px] font-semibold text-slate-700 line-clamp-3 break-words">
                            Tu texto aparecerá aquí...
                          </p>
                          <div id="smPreviewDate" class="mt-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-wider">Ahora</div>
                        </div>
                      </div>
                    </div>
                    {{-- Preview platform badge --}}
                    <div class="p-3 border-t border-slate-100 text-center">
                      <span id="smPreviewPlatformBadge" class="inline-flex items-center gap-1.5 text-[11px] font-black text-slate-500">
                        <span class="sm-platform small instagram text-[9px]"><i class="fa-brands fa-instagram"></i></span>
                        Instagram
                      </span>
                    </div>
                  </div>

                </form>

                {{-- ── Footer ──────────────────────────────────────────────── --}}
                <div class="flex items-center gap-2 border-t border-slate-100 bg-white px-5 py-3 shrink-0">
                  <button type="button" id="smCancelBtn"
                          class="text-sm font-black text-slate-600 hover:text-slate-900 px-4 py-2 rounded-xl hover:bg-slate-100 transition-colors">
                    Cancelar
                  </button>
                  <div class="flex items-center gap-2 ml-auto">
                    <button type="button" id="smSaveDraftBtn"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50 transition-colors">
                      Finalizar más tarde
                    </button>
                    <button type="button" id="smScheduleSubmitBtn"
                            class="hidden rounded-xl border border-blue-200 bg-blue-600 px-4 py-2 text-sm font-black text-white hover:bg-blue-700 transition-colors">
                      <i class="fa-solid fa-calendar-check mr-1"></i> Programar
                    </button>
                    <button type="button" id="smPublishNowSubmitBtn"
                            class="rounded-xl bg-[#ecfe88] px-5 py-2 text-sm font-black text-slate-950 hover:bg-[#d9f96b] transition-colors shadow-sm">
                      <i class="fa-solid fa-bolt mr-1"></i> Publicar ahora
                    </button>
                  </div>
                </div>

              </div>{{-- /shell --}}
            </div>{{-- /modal backdrop --}}
          @endif
        </section>

      @endif

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  {{-- MEDIA LIBRARY MODAL ──────────────────────────────────────────────────── --}}
  <div id="smMediaLibraryModal" class="fixed inset-0 hidden items-center justify-center bg-slate-950/60 backdrop-blur-sm z-[2147483600] p-4" role="dialog" aria-modal="true">
    <div class="relative w-full max-w-5xl max-h-[85vh] h-[800px] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden" onclick="event.stopPropagation()" role="document">
      {{-- Header --}}
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white shrink-0">
        <h3 class="text-base font-black text-slate-800"><i class="fa-regular fa-images text-blue-500 mr-2"></i>Biblioteca Multimedia</h3>
        <button type="button" id="smMediaLibraryClose" class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition-colors">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      {{-- Body with Sidebar --}}
      <div class="flex flex-1 overflow-hidden">

        {{-- Sidebar --}}
        <div class="w-64 bg-slate-50 border-r border-slate-100 p-4 flex flex-col gap-1 shrink-0 overflow-y-auto">
          <button type="button" class="sm-media-filter w-full flex items-center justify-between px-4 py-3 rounded-xl font-black text-sm transition-colors bg-blue-100 text-blue-700" data-filter="all">
            <span class="flex items-center gap-3"><i class="fa-solid fa-border-all w-4 text-center"></i>Todos</span>
            <span class="text-xs font-bold bg-white text-blue-600 px-2 py-0.5 rounded-full shadow-sm">0</span>
          </button>
          <button type="button" class="sm-media-filter w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition-colors" data-filter="image">
            <span class="flex items-center gap-3"><i class="fa-regular fa-image w-4 text-center"></i>Fotos</span>
            <span class="text-xs font-bold text-slate-400">0</span>
          </button>
          <button type="button" class="sm-media-filter w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-200 transition-colors" data-filter="video">
            <span class="flex items-center gap-3"><i class="fa-solid fa-video w-4 text-center"></i>Videos</span>
            <span class="text-xs font-bold text-slate-400">0</span>
          </button>
        </div>

        {{-- Main Area --}}
        <div class="flex-1 overflow-y-auto p-6 bg-white">
          {{-- Upload Action --}}
          <div class="mb-6 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-6 text-center hover:border-blue-400 hover:bg-blue-50/50 transition-colors cursor-pointer group" onclick="document.getElementById('smMediaUploadInput').click()">
            <i class="fa-solid fa-cloud-arrow-up text-3xl text-blue-400 group-hover:-translate-y-1 transition-transform mb-2 block"></i>
            <p class="text-sm font-black text-slate-700">Subir a Cloudinary</p>
            <p class="text-[11px] font-semibold text-slate-400 mt-1">Soporta JPG, PNG, MP4 (Máx. 4GB)</p>
            <input type="file" id="smMediaUploadInput" class="hidden" accept="image/*,video/*">
          </div>

          {{-- Gallery Grid --}}
          <h4 class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-3">Tus archivos</h4>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3" id="smMediaGalleryGrid">
            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center">
              <i class="fa-regular fa-folder-open text-2xl text-slate-300"></i>
              <p class="mt-3 text-sm font-black text-slate-600">No hay archivos cargados todavía.</p>
              <p class="mt-1 text-xs font-semibold text-slate-400">Sube contenido real para usarlo en tus publicaciones.</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="px-6 py-4 border-t border-slate-100 bg-white flex justify-end gap-2">
        <button type="button" id="smMediaLibraryCancel" class="px-5 py-2.5 rounded-xl text-sm font-black text-slate-600 hover:bg-slate-100 transition-colors">Cancelar</button>
        <button type="button" id="smMediaLibraryInsert" disabled class="px-5 py-2.5 rounded-xl text-sm font-black text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">Insertar archivo</button>
      </div>
    </div>
  </div>

<script>
(() => {
  document.querySelectorAll('[data-social-period-form]').forEach((form) => {
    const select = form.querySelector('[data-social-period-select]');
    const customFields = form.querySelector('[data-custom-period-fields]');
    if (!select) return;

    select.addEventListener('change', () => {
      if (select.value === 'custom') {
        customFields?.classList.remove('hidden');
        customFields?.classList.add('flex');
        customFields?.querySelector('input[name="date_from"]')?.focus();
        return;
      }

      customFields?.classList.add('hidden');
      customFields?.classList.remove('flex');
      form.submit();
    });
  });

  const chartEl = document.getElementById('socialPerformanceChart');
  const chartRows = @json($chartData);
  if (chartEl && window.Chart) {
    const ctx = chartEl.getContext('2d');
    const reachGradient = ctx.createLinearGradient(0, 0, 0, 320);
    reachGradient.addColorStop(0, 'rgba(183, 238, 81, .34)');
    reachGradient.addColorStop(1, 'rgba(183, 238, 81, 0)');
    const interactionGradient = ctx.createLinearGradient(0, 0, 0, 320);
    interactionGradient.addColorStop(0, 'rgba(47, 140, 255, .2)');
    interactionGradient.addColorStop(1, 'rgba(47, 140, 255, 0)');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartRows.map(row => row.label),
        datasets: [
          { label: 'Alcance', data: chartRows.map(row => row.reach), tension:.42, borderColor:'#b7ee51', borderWidth:3, backgroundColor:reachGradient, pointRadius:3, pointHoverRadius:6, pointBackgroundColor:'#fff', pointBorderColor:'#a3e635', pointBorderWidth:2, fill:true },
          { label: 'Interacciones', data: chartRows.map(row => row.interactions), tension:.42, borderColor:'#2f8cff', borderWidth:2.5, backgroundColor:interactionGradient, pointRadius:3, pointHoverRadius:6, pointBackgroundColor:'#fff', pointBorderColor:'#2f8cff', pointBorderWidth:2, fill:false, yAxisID:'y1' },
          { label: 'Leads', data: chartRows.map(row => row.leads), tension:.42, borderColor:'#a855f7', borderWidth:2.5, pointRadius:3, pointHoverRadius:6, pointBackgroundColor:'#fff', pointBorderColor:'#a855f7', pointBorderWidth:2, fill:false, yAxisID:'y1' },
        ]
      },
      options: {
        responsive:true,
        maintainAspectRatio:false,
        interaction:{ intersect:false, mode:'index' },
        plugins:{
          legend:{ display:false },
          tooltip:{ backgroundColor:'#fff', titleColor:'#64748b', bodyColor:'#0f172a', borderColor:'#e2e8f0', borderWidth:1, padding:12, displayColors:true, bodyFont:{weight:'800'}, titleFont:{weight:'800'} }
        },
        scales:{
          x:{ grid:{ color:'#f1f5f9' }, ticks:{ color:'#64748b', maxTicksLimit:8, font:{ size:11, weight:'700' } } },
          y:{ beginAtZero:true, grid:{ color:'#eef2f7' }, ticks:{ color:'#64748b', font:{ size:11, weight:'700' }, callback:value => Intl.NumberFormat('es-CO', { notation:'compact' }).format(value) } },
          y1:{ beginAtZero:true, position:'right', grid:{ display:false }, ticks:{ color:'#64748b', font:{ size:11, weight:'700' }, callback:value => Intl.NumberFormat('es-CO', { notation:'compact' }).format(value) } }
        }
      }
    });
  }

  const syncButtons = Array.from(document.querySelectorAll('#socialSyncBtn, #socialSyncBtnHeader, .sm-sync-btn'));
  const syncBtn = syncButtons[0] || null;
  const msg = document.getElementById('socialApiMessage');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  function showMessage(type, text) {
    if (!msg) return;
    msg.className = 'sm-card px-4 py-3 text-sm font-black border-l-4 ' + (type === 'error' ? 'border-rose-400 bg-rose-50 text-rose-700' : 'border-lime-300 bg-lime-50 text-lime-800');
    msg.textContent = text;
    msg.classList.remove('hidden');
  }
  syncButtons.forEach((button) => button.addEventListener('click', async () => {
    const previous = button.innerHTML;
    syncButtons.forEach((node) => {
      node.disabled = true;
      node.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando...';
    });
    try {
      const res = await fetch(@json(route('api.social-media.sync')), {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf },
        body:'{}',
      });
      const json = await res.json();
      if (!res.ok || !json.ok) throw new Error(json.message || 'No se pudo sincronizar.');
      showMessage('success', json.message || 'Sincronización completada.');
      setTimeout(() => window.location.reload(), 800);
    } catch (error) {
      showMessage('error', error.message || 'No se pudo sincronizar.');
      syncButtons.forEach((node) => {
        node.disabled = false;
        node.innerHTML = previous;
      });
    }
  }));

  const calendarMode = document.getElementById('socialCalendarMode');
  const weekView = document.getElementById('socialCalendarWeek');
  const monthView = document.getElementById('socialCalendarMonth');

  const calTitleWeek = document.getElementById('calTitleWeek');
  const calTitleMonth = document.getElementById('calTitleMonth');
  const calPrevBtn = document.getElementById('calPrevBtn');
  const calTodayBtn = document.getElementById('calTodayBtn');
  const calNextBtn = document.getElementById('calNextBtn');

  function updateCalendarMode() {
    if (!calendarMode) return;
    const showMonth = calendarMode.value === 'month';

    // Update Grids
    weekView?.classList.toggle('hidden', showMonth);
    monthView?.classList.toggle('hidden', !showMonth);

    // Update Title
    calTitleWeek?.classList.toggle('hidden', showMonth);
    calTitleMonth?.classList.toggle('hidden', !showMonth);

    // Update Nav Button URLs
    if (calPrevBtn) calPrevBtn.href = showMonth ? calPrevBtn.getAttribute('data-month-url') : calPrevBtn.getAttribute('data-week-url');
    if (calTodayBtn) calTodayBtn.href = showMonth ? calTodayBtn.getAttribute('data-month-url') : calTodayBtn.getAttribute('data-week-url');
    if (calNextBtn) calNextBtn.href = showMonth ? calNextBtn.getAttribute('data-month-url') : calNextBtn.getAttribute('data-week-url');
  }

  if (calendarMode) {
    updateCalendarMode();
    calendarMode.addEventListener('change', updateCalendarMode);
  }

  // ── META-STYLE MODAL LOGIC ──────────────────────────────────────────────────
  const contentModal = document.getElementById('socialContentModal');
  const contentModalClose = document.getElementById('socialContentModalClose');
  const smCancelBtn = document.getElementById('smCancelBtn');
  const smForm = document.getElementById('smCreatePostForm');

  // Elements
  const smAccountDropBtn = document.getElementById('smAccountDropBtn');
  const smAccountDropPanel = document.getElementById('smAccountDropPanel');
  const smAccountDropLabel = document.getElementById('smAccountDropLabel');
  const smAccountChecks = document.querySelectorAll('.smp-account-check');
  const smPrimaryAccountId = document.getElementById('smPrimaryAccountId');
  const smPrimaryPlatform = document.getElementById('smPrimaryPlatform');

  const smCtypeRadios = document.querySelectorAll('input[name="content_type"]');
  const smVideoUrlWrap = document.getElementById('smVideoUrlWrap');

  const smModalImageUrl = document.getElementById('smModalImageUrl');
  const smModalMediaUrl = document.getElementById('smModalMediaUrl');
  const smMediaPreviewZone = document.getElementById('smMediaPreviewZone');
  const smMediaDropContent = document.getElementById('smMediaDropContent');
  const smMediaPreviewImg = document.getElementById('smMediaPreviewImg');
  const smMediaPreviewVid = document.getElementById('smMediaPreviewVid');
  const smMediaClearBtn = document.getElementById('smMediaClearBtn');

  const smModalCaption = document.getElementById('smModalCaption');
  const smCaptionCounter = document.getElementById('smCaptionCounter');
  const smCustomizePerPlatform = document.getElementById('smCustomizePerPlatform');
  const smPerPlatformCaptions = document.getElementById('smPerPlatformCaptions');

  const smScheduleToggle = document.getElementById('smScheduleToggle');
  const smScheduleDateWrap = document.getElementById('smScheduleDateWrap');
  const socialContentScheduledAt = document.getElementById('socialContentScheduledAt');

  const smModalStatus = document.getElementById('smModalStatus');
  const smSaveDraftBtn = document.getElementById('smSaveDraftBtn');
  const smScheduleSubmitBtn = document.getElementById('smScheduleSubmitBtn');
  const smPublishNowSubmitBtn = document.getElementById('smPublishNowSubmitBtn');

  // Preview Elements
  const smPreviewAvatar = document.getElementById('smPreviewAvatar');
  const smPreviewAccountName = document.getElementById('smPreviewAccountName');
  const smPreviewContentType = document.getElementById('smPreviewContentType');
  const smPreviewMedia = document.getElementById('smPreviewMedia');
  const smPreviewCaption = document.getElementById('smPreviewCaption');
  const smPreviewDate = document.getElementById('smPreviewDate');
  const smPreviewPlatformBadge = document.getElementById('smPreviewPlatformBadge');

  // 1. Account Dropdown
  smAccountDropBtn?.addEventListener('click', () => {
    smAccountDropPanel?.classList.toggle('hidden');
  });

  let smPreviewAccounts = [];
  let smPreviewActiveIndex = 0;

  const smPreviewNav = document.getElementById('smPreviewNav');
  const smPreviewPrev = document.getElementById('smPreviewPrev');
  const smPreviewNext = document.getElementById('smPreviewNext');
  const smPreviewCounter = document.getElementById('smPreviewCounter');

  smPreviewPrev?.addEventListener('click', () => {
    if (smPreviewAccounts.length <= 1) return;
    smPreviewActiveIndex = (smPreviewActiveIndex - 1 + smPreviewAccounts.length) % smPreviewAccounts.length;
    renderPreview();
  });

  smPreviewNext?.addEventListener('click', () => {
    if (smPreviewAccounts.length <= 1) return;
    smPreviewActiveIndex = (smPreviewActiveIndex + 1) % smPreviewAccounts.length;
    renderPreview();
  });

  function renderPreview() {
    if (smPreviewAccounts.length === 0) {
      smPreviewAccountName.textContent = 'Tu cuenta';
      smPreviewNav?.classList.add('hidden');
      return;
    }

    if (smPreviewActiveIndex >= smPreviewAccounts.length) smPreviewActiveIndex = 0;

    const acc = smPreviewAccounts[smPreviewActiveIndex];
    const isIg = acc.platform === 'instagram';

    smPreviewAccountName.textContent = acc.name;
    smPreviewAvatar.className = `w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-black shrink-0 ${isIg ? 'bg-gradient-to-br from-orange-400 via-pink-500 to-purple-600' : 'bg-[#1877f2]'}`;
    smPreviewAvatar.innerHTML = `<i class="fa-brands ${isIg ? 'fa-instagram' : 'fa-facebook-f'}"></i>`;

    smPreviewPlatformBadge.innerHTML = `
      <span class="sm-platform small ${isIg ? 'instagram' : 'facebook'} text-[9px]">
        <i class="fa-brands ${isIg ? 'fa-instagram' : 'fa-facebook-f'}"></i>
      </span>
      ${isIg ? 'Instagram' : 'Facebook'}
    `;

    if (smPreviewAccounts.length > 1) {
      smPreviewNav?.classList.remove('hidden');
      if(smPreviewCounter) smPreviewCounter.textContent = `${smPreviewActiveIndex + 1}/${smPreviewAccounts.length}`;
    } else {
      smPreviewNav?.classList.add('hidden');
    }
  }

  function updateAccountSelection() {
    const checked = Array.from(smAccountChecks).filter(cb => cb.checked);
    smPreviewAccounts = checked.map(cb => ({
      id: cb.value,
      platform: cb.getAttribute('data-platform'),
      name: cb.getAttribute('data-name')
    }));

    if (checked.length === 0) {
      smAccountDropLabel.innerHTML = '<span class="text-slate-400">Seleccionar cuentas</span>';
      smPrimaryAccountId.value = '';
    } else {
      const first = checked[0];
      smPrimaryPlatform.value = first.getAttribute('data-platform');
      smPrimaryAccountId.value = first.value;

      if (checked.length === 1) {
        smAccountDropLabel.innerHTML = `<span class="font-black text-slate-800">${first.getAttribute('data-name')}</span>`;
      } else {
        smAccountDropLabel.innerHTML = `<span class="font-black text-slate-800">${checked.length} cuentas seleccionadas</span>`;
      }
    }

    smPreviewActiveIndex = 0;
    renderPreview();
    updatePreviewContext();
  }

  smAccountChecks.forEach(cb => cb.addEventListener('change', updateAccountSelection));

  // 2. Content Type
  function updatePreviewContext() {
    const ctype = document.querySelector('input[name="content_type"]:checked')?.value || 'post';
    const ctypeLabels = { post: '<i class="fa-solid fa-camera"></i> Post', reel: '<i class="fa-solid fa-clapperboard"></i> Reel', story: '<i class="fa-solid fa-mobile-screen"></i> Historia' };
    if (smPreviewContentType) {
      smPreviewContentType.innerHTML = `${ctypeLabels[ctype]} · Público`;
    }
    if (smPreviewMedia) {
      smPreviewMedia.style.aspectRatio = ctype === 'post' ? '1 / 1' : '9 / 16';
    }

    // Video field visibility
    if (smVideoUrlWrap) {
      smVideoUrlWrap.classList.toggle('hidden', ctype === 'post');
    }
  }
  smCtypeRadios.forEach(r => r.addEventListener('change', updatePreviewContext));

  // 3. Media Preview
  function updateMediaPreview() {
    const imgUrl = smModalImageUrl?.value.trim();
    const vidUrl = smModalMediaUrl?.value.trim();
    const ctype = document.querySelector('input[name="content_type"]:checked')?.value || 'post';

    if (imgUrl || vidUrl) {
      smMediaDropContent?.classList.add('hidden');
      smMediaPreviewZone?.classList.remove('hidden');

      if (ctype === 'post' || !vidUrl) {
        smMediaPreviewImg.src = imgUrl || vidUrl;
        smMediaPreviewImg.classList.remove('hidden');
        smMediaPreviewVid.classList.add('hidden');
        smPreviewMedia.style.backgroundImage = `url('${imgUrl || vidUrl}')`;
        smPreviewMedia.innerHTML = '';
      } else {
        smMediaPreviewVid.src = vidUrl;
        smMediaPreviewVid.classList.remove('hidden');
        smMediaPreviewImg.classList.add('hidden');
        smPreviewMedia.style.backgroundImage = 'none';
        smPreviewMedia.innerHTML = '<i class="fa-solid fa-play text-3xl text-white drop-shadow-md"></i>';
      }
    } else {
      smMediaDropContent?.classList.remove('hidden');
      smMediaPreviewZone?.classList.add('hidden');
      smPreviewMedia.style.backgroundImage = 'none';
      smPreviewMedia.innerHTML = '<svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>';
    }
  }
  smModalImageUrl?.addEventListener('input', updateMediaPreview);
  smModalMediaUrl?.addEventListener('input', updateMediaPreview);
  smMediaClearBtn?.addEventListener('click', () => {
    if(smModalImageUrl) smModalImageUrl.value = '';
    if(smModalMediaUrl) smModalMediaUrl.value = '';
    updateMediaPreview();
  });

  // 4. Caption & Toolbar
  smModalCaption?.addEventListener('input', () => {
    const len = smModalCaption.value.length;
    if(smCaptionCounter) smCaptionCounter.textContent = `${len} / 2200`;
    if(smPreviewCaption) {
      smPreviewCaption.textContent = smModalCaption.value || 'Tu texto aparecerá aquí...';
    }
  });

  window.smInsertEmoji = () => smInsertText(' 😊');
  window.smInsertText = (txt) => {
    if(!smModalCaption) return;
    const start = smModalCaption.selectionStart;
    const end = smModalCaption.selectionEnd;
    smModalCaption.value = smModalCaption.value.substring(0, start) + txt + smModalCaption.value.substring(end);
    smModalCaption.focus();
    smModalCaption.selectionEnd = start + txt.length;
    smModalCaption.dispatchEvent(new Event('input'));
  };
  window.smInsertFormat = (fmt) => smInsertText(fmt + 'texto' + fmt);

  smCustomizePerPlatform?.addEventListener('change', (e) => {
    smPerPlatformCaptions?.classList.toggle('hidden', !e.target.checked);
  });

  // 5. Schedule Toggle
  smScheduleToggle?.addEventListener('change', (e) => {
    smScheduleDateWrap?.classList.toggle('hidden', !e.target.checked);
    smScheduleSubmitBtn?.classList.toggle('hidden', !e.target.checked);
    smPublishNowSubmitBtn?.classList.toggle('hidden', e.target.checked);
    if(e.target.checked && socialContentScheduledAt && !socialContentScheduledAt.value) {
      const now = new Date();
      now.setMinutes(now.getMinutes() + 15);
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      socialContentScheduledAt.value = now.toISOString().slice(0,16);
    }
    if(smPreviewDate) {
      smPreviewDate.textContent = e.target.checked ? 'Programado' : 'Ahora';
    }
  });

  // 6. Submit Buttons
  smSaveDraftBtn?.addEventListener('click', () => { smModalStatus.value = 'borrador'; smForm?.submit(); });
  smScheduleSubmitBtn?.addEventListener('click', () => {
    smModalStatus.value = 'programado';
    if(!socialContentScheduledAt?.value) { alert('Selecciona una fecha'); return; }
    smForm?.submit();
  });
  smPublishNowSubmitBtn?.addEventListener('click', () => {
    smModalStatus.value = 'programado';
    // Set time to now so cron picks it up immediately
    if(socialContentScheduledAt) {
      const now = new Date();
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
      socialContentScheduledAt.value = now.toISOString().slice(0,16);
    }
    smForm?.submit();
  });

  // 7. Open / Close
  function openContentModal(date) {
    if (!contentModal) return;
    if (socialContentScheduledAt && date) {
      socialContentScheduledAt.value = date.includes('T') ? date : `${date}T09:00`;
      smScheduleToggle.checked = true;
      smScheduleToggle.dispatchEvent(new Event('change'));
    }
    contentModal.classList.remove('hidden');
    contentModal.classList.add('flex');
  }
  function closeContentModal() {
    if (!contentModal) return;
    contentModal.classList.add('hidden');
    contentModal.classList.remove('flex');
  }
  document.querySelectorAll('[data-open-content-modal]').forEach((node) => {
    node.addEventListener('click', (event) => {
      event.preventDefault();
      openContentModal(node.getAttribute('data-date') || '');
    });
  });
  contentModalClose?.addEventListener('click', closeContentModal);
  smCancelBtn?.addEventListener('click', closeContentModal);
  contentModal?.addEventListener('click', (event) => {
    // Only close if clicking exactly on the backdrop (with id socialContentModal)
    if (event.target.id === 'socialContentModal') {
      closeContentModal();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && contentModal && !contentModal.classList.contains('hidden')) {
      closeContentModal();
    }
  });


  // ── MEDIA LIBRARY LOGIC ───────────────────────────────────────────────────
  const smMediaDropZone = document.getElementById('smMediaDropZone');
  const smMediaLibraryModal = document.getElementById('smMediaLibraryModal');
  const smMediaLibraryClose = document.getElementById('smMediaLibraryClose');
  const smMediaLibraryCancel = document.getElementById('smMediaLibraryCancel');
  const smMediaLibraryInsert = document.getElementById('smMediaLibraryInsert');
  const smMediaItems = document.querySelectorAll('.sm-media-item');
  let selectedMediaUrl = null;
  let selectedMediaType = null;

  function openMediaLibrary() {
    smMediaLibraryModal?.classList.remove('hidden');
    smMediaLibraryModal?.classList.add('flex');
    // Reset selection
    selectedMediaUrl = null;
    selectedMediaType = null;
    smMediaItems.forEach(i => i.classList.remove('border-blue-500', '.selected'));
    if(smMediaLibraryInsert) smMediaLibraryInsert.disabled = true;
  }

  function closeMediaLibrary() {
    smMediaLibraryModal?.classList.add('hidden');
    smMediaLibraryModal?.classList.remove('flex');
  }

  smMediaDropZone?.addEventListener('click', openMediaLibrary);
  smMediaLibraryClose?.addEventListener('click', closeMediaLibrary);
  smMediaLibraryCancel?.addEventListener('click', closeMediaLibrary);

  // Close media library on backdrop click
  smMediaLibraryModal?.addEventListener('click', (e) => {
    if (e.target.id === 'smMediaLibraryModal') {
      closeMediaLibrary();
    }
  });

  // Filtering Logic
  const smMediaFilters = document.querySelectorAll('.sm-media-filter');
  smMediaFilters.forEach(filterBtn => {
    filterBtn.addEventListener('click', () => {
      // Update styling
      smMediaFilters.forEach(btn => {
        btn.classList.remove('bg-blue-100', 'text-blue-700', 'font-black');
        btn.classList.add('font-bold', 'text-slate-600', 'hover:bg-slate-200');
        const badge = btn.querySelector('span:last-child');
        badge.className = 'text-xs font-bold text-slate-400';
      });
      filterBtn.classList.remove('font-bold', 'text-slate-600', 'hover:bg-slate-200');
      filterBtn.classList.add('bg-blue-100', 'text-blue-700', 'font-black');
      const activeBadge = filterBtn.querySelector('span:last-child');
      activeBadge.className = 'text-xs font-bold bg-white text-blue-600 px-2 py-0.5 rounded-full shadow-sm';

      const filterValue = filterBtn.getAttribute('data-filter');

      smMediaItems.forEach(item => {
        if (filterValue === 'all' || item.getAttribute('data-type') === filterValue) {
          item.classList.remove('hidden');
        } else {
          item.classList.add('hidden');
        }
      });
    });
  });

  smMediaItems.forEach(item => {
    item.addEventListener('click', () => {
      // Unselect all
      smMediaItems.forEach(i => {
        i.classList.remove('border-blue-500');
        i.classList.remove('.selected');
      });
      // Select clicked
      item.classList.add('border-blue-500', '.selected');
      selectedMediaUrl = item.getAttribute('data-url');
      selectedMediaType = item.getAttribute('data-type');
      if(smMediaLibraryInsert) smMediaLibraryInsert.disabled = false;
    });
  });

  smMediaLibraryInsert?.addEventListener('click', () => {
    if (!selectedMediaUrl) return;

    // Set URLs based on type
    if (selectedMediaType === 'video') {
      if(smModalMediaUrl) { smModalMediaUrl.value = selectedMediaUrl; smModalMediaUrl.dispatchEvent(new Event('input')); }
      if(smModalImageUrl) { smModalImageUrl.value = ''; smModalImageUrl.dispatchEvent(new Event('input')); }
    } else {
      if(smModalImageUrl) { smModalImageUrl.value = selectedMediaUrl; smModalImageUrl.dispatchEvent(new Event('input')); }
      if(smModalMediaUrl) { smModalMediaUrl.value = ''; smModalMediaUrl.dispatchEvent(new Event('input')); }
    }

    closeMediaLibrary();
  });

  // ── Publish Now buttons ───────────────────────────────────────────────────
  document.querySelectorAll('[data-publish-now]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const postId = btn.getAttribute('data-post-id');
      if (!postId) return;
      const original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      try {
        const res = await fetch(`/api/social-media/calendario/${postId}/publish-now`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: '{}',
        });
        const json = await res.json();
        if (!res.ok || !json.ok) throw new Error(json.message || 'Error al publicar.');
        // Update UI
        const card = btn.closest('.sm-content-event');
        if (card) {
          const dot = card.querySelector('.sm-pub-dot');
          if (dot) { dot.className = 'sm-pub-dot h-2 w-2 rounded-full bg-emerald-500'; dot.title = 'Publicado'; }
          btn.innerHTML = '<i class="fa-solid fa-check"></i>';
          btn.className = btn.className.replace('bg-lime-100 text-lime-700', 'bg-emerald-100 text-emerald-700');
        } else {
          btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        }
        if (msg) { msg.className = 'sm-card px-4 py-3 text-sm font-black border-l-4 border-lime-300 bg-lime-50 text-lime-800'; msg.textContent = json.message; msg.classList.remove('hidden'); }
      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = original;
        if (msg) { msg.className = 'sm-card px-4 py-3 text-sm font-black border-l-4 border-rose-400 bg-rose-50 text-rose-700'; msg.textContent = err.message; msg.classList.remove('hidden'); }
      }
    });
  });

})();
</script>


  <div id="addSocialClientsModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[2000] hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
      <div class="flex items-center justify-between p-5 border-b border-slate-100">
        <h3 class="text-xl font-black text-slate-900">Añadir Clientes a Social Media</h3>
        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors" onclick="document.getElementById('addSocialClientsModal').classList.add('hidden'); document.getElementById('addSocialClientsModal').classList.remove('flex');">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form action="{{ route('social-media.clients.update') }}" method="POST" class="flex flex-col min-h-0">
        @csrf
        <div class="p-5 overflow-y-auto bg-slate-50 flex-1">
          <p class="text-sm font-medium text-slate-500 mb-4">Selecciona los clientes del CRM que deseas gestionar en este módulo. Solo los clientes seleccionados aparecerán en el selector principal.</p>
          <div class="space-y-2">
            @foreach($clients as $c)
              <label class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-lime-300 transition-colors">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-black text-slate-600">{{ strtoupper(substr($c['name'],0,1)) }}</div>
                  <span class="font-bold text-slate-700">{{ $c['name'] }}</span>
                </div>
                <input type="checkbox" name="client_ids[]" value="{{ $c['id'] }}" class="w-5 h-5 rounded text-lime-600 bg-slate-100 border-slate-300 focus:ring-lime-500 focus:ring-2" @checked(in_array($c['id'], $socialClientIds ?? []))>
              </label>
            @endforeach
            @if(empty($clients))
              <div class="text-center p-6 text-slate-500 font-semibold">No hay clientes en el CRM.</div>
            @endif
          </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-white flex justify-end gap-3 shrink-0">
          <button type="button" class="px-5 py-2.5 rounded-2xl font-bold text-slate-600 hover:bg-slate-100 transition-colors" onclick="document.getElementById('addSocialClientsModal').classList.add('hidden'); document.getElementById('addSocialClientsModal').classList.remove('flex');">Cancelar</button>
          <button type="submit" class="px-5 py-2.5 rounded-2xl font-black bg-[#ecfe88] text-slate-900 hover:bg-[#dff36f] transition-colors shadow-sm">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

@endsection
