@extends('layouts.settings')

@section('title', 'Social Media - Ajustes')

@section('content')
@php
  use Illuminate\Support\Carbon;

  $meta = $credentialStatus['meta'] ?? [];
  $tiktok = $credentialStatus['tiktok'] ?? [];
  $metaRedirect = $settings['meta_redirect_uri'] ?? ($meta['redirect_uri'] ?? route('social-media.meta.callback'));
  $tiktokRedirect = $settings['tiktok_redirect_uri'] ?? ($tiktok['redirect_uri'] ?? route('social-media.tiktok.callback'));
@endphp

<div class="space-y-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div>
      <h2 class="text-2xl font-black text-slate-950">Social Media</h2>
      <p class="mt-1 text-sm font-semibold text-slate-500">Credenciales, OAuth y estado técnico para Meta, Instagram, Facebook Ads y TikTok.</p>
    </div>
    <a href="{{ route('social-media.index') }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
      Abrir Social Manager
    </a>
  </div>

  <form method="POST" action="{{ route('settings.social_media.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h3 class="text-xl font-black text-slate-950">Meta / Facebook / Instagram</h3>
          <p class="mt-1 text-sm font-semibold text-slate-500">Usado para conectar páginas, cuentas Instagram Business, campañas y formularios Lead Ads.</p>
        </div>
        <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-black {{ !empty($meta['configured']) ? 'border-lime-200 bg-lime-50 text-lime-800' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
          {{ !empty($meta['configured']) ? 'Credenciales listas' : 'Faltan credenciales' }}
        </span>
      </div>

      <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">META_APP_ID</label>
          <input name="meta_app_id" value="{{ old('meta_app_id', $settings['meta_app_id'] ?? '') }}" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]" placeholder="App ID de Meta">
          <p class="mt-1 text-xs font-semibold text-slate-400">Actual: {{ $meta['app_id_preview'] ?? 'Sin configurar' }} @if(!empty($meta['source']['app_id'])) · {{ $meta['source']['app_id'] }} @endif</p>
        </div>
        <div>
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">META_APP_SECRET</label>
          <input type="password" name="meta_app_secret" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]" placeholder="{{ !empty($meta['configured']) ? 'Dejar vacío para mantener' : 'App Secret de Meta' }}">
          <p class="mt-1 text-xs font-semibold text-slate-400">Actual: {{ $meta['app_secret_preview'] ?? 'Sin configurar' }} @if(!empty($meta['source']['app_secret'])) · {{ $meta['source']['app_secret'] }} @endif</p>
        </div>
        <div class="md:col-span-2">
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">META_REDIRECT_URI</label>
          <input name="meta_redirect_uri" value="{{ old('meta_redirect_uri', $metaRedirect) }}" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]">
          <p class="mt-1 text-xs font-semibold text-slate-400">Esta misma URL debe estar en “Valid OAuth Redirect URIs” dentro de Meta Developers.</p>
        </div>
        <div>
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">META_GRAPH_VERSION</label>
          <input name="meta_graph_version" value="{{ old('meta_graph_version', $settings['meta_graph_version'] ?? ($meta['graph_version'] ?? 'v20.0')) }}" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]" placeholder="v20.0">
        </div>
      </div>

      <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">
        <div class="font-black text-slate-800">Scopes usados por el CRM</div>
        <p class="mt-2 leading-7">public_profile, email, pages_show_list, pages_read_engagement, pages_read_user_content, instagram_basic, instagram_manage_insights, ads_read, leads_retrieval, pages_manage_posts, pages_manage_metadata, instagram_content_publish.</p>
      </div>

      <div class="mt-5 flex flex-col gap-2 sm:flex-row">
        <button class="inline-flex items-center justify-center rounded-full bg-[#ecfe88] px-5 py-3 text-sm font-black text-slate-950 shadow-sm transition hover:bg-[#d9ef60]">
          Guardar Social Media
        </button>
        <a href="{{ route('social-media.meta.connect') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50">
          Conectar Meta
        </a>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h3 class="text-xl font-black text-slate-950">TikTok</h3>
          <p class="mt-1 text-sm font-semibold text-slate-500">Preparado para Login Kit/API cuando tu app tenga aprobación y scopes disponibles.</p>
        </div>
        <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-black {{ !empty($tiktok['configured']) ? 'border-lime-200 bg-lime-50 text-lime-800' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
          {{ !empty($tiktok['configured']) ? 'Preparado' : 'Pendiente' }}
        </span>
      </div>

      <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">TIKTOK_CLIENT_KEY</label>
          <input name="tiktok_client_key" value="{{ old('tiktok_client_key', $settings['tiktok_client_key'] ?? '') }}" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]" placeholder="Client Key de TikTok">
          <p class="mt-1 text-xs font-semibold text-slate-400">Actual: {{ $tiktok['client_key_preview'] ?? 'Sin configurar' }} @if(!empty($tiktok['source']['client_key'])) · {{ $tiktok['source']['client_key'] }} @endif</p>
        </div>
        <div>
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">TIKTOK_CLIENT_SECRET</label>
          <input type="password" name="tiktok_client_secret" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]" placeholder="{{ !empty($tiktok['configured']) ? 'Dejar vacío para mantener' : 'Client Secret de TikTok' }}">
          <p class="mt-1 text-xs font-semibold text-slate-400">Actual: {{ $tiktok['client_secret_preview'] ?? 'Sin configurar' }} @if(!empty($tiktok['source']['client_secret'])) · {{ $tiktok['source']['client_secret'] }} @endif</p>
        </div>
        <div class="md:col-span-2">
          <label class="text-xs font-black uppercase tracking-wider text-slate-400">TIKTOK_REDIRECT_URI</label>
          <input name="tiktok_redirect_uri" value="{{ old('tiktok_redirect_uri', $tiktokRedirect) }}" class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-[#ecfe88] focus:ring-[#ecfe88]">
        </div>
      </div>

      <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
        TikTok queda preparado. La publicación automática o lectura avanzada depende de aprobación de app/scopes en TikTok Developers.
      </div>
    </section>
  </form>

  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h3 class="text-xl font-black text-slate-950">Registro de sincronización</h3>
        <p class="mt-1 text-sm font-semibold text-slate-500">Últimos eventos de conexión, sincronización y errores de APIs sociales.</p>
      </div>
    </div>
    <div class="mt-5 space-y-3">
      @forelse($syncLogs as $log)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border px-2.5 py-1 text-xs font-black {{ ($log['status'] ?? '') === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : (($log['status'] ?? '') === 'success' ? 'border-lime-200 bg-lime-50 text-lime-800' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
              {{ $log['status'] ?? 'log' }}
            </span>
            <span class="font-black text-slate-900">{{ $log['message'] ?? 'Evento' }}</span>
          </div>
          <div class="mt-1 text-xs font-bold uppercase tracking-[.12em] text-slate-400">
            {{ $log['provider'] ?? 'social' }} · {{ isset($log['created_at']) ? Carbon::parse($log['created_at'])->format('d/m/Y H:i') : '' }}
          </div>
        </div>
      @empty
        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-500">Sin registros aún.</p>
      @endforelse
    </div>
  </section>
</div>
@endsection
