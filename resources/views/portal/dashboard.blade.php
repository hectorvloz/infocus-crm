@extends('layouts.guest')
@section('title','Portal de Cliente')
@section('content')
@php
  $companyWhatsappRaw = trim((string) ($settings['whatsapp_number'] ?? ''));
  $companyWhatsappDigits = preg_replace('/\D+/', '', $companyWhatsappRaw);
  $companyWhatsappMessage = trim('Hola, soy ' . ($client['empresa'] ?? $client['contacto_nombre'] ?? 'cliente') . '. Quiero comunicarme con ' . ($settings['company_name'] ?? 'la empresa') . '.');
  $companyWhatsappUrl = $companyWhatsappDigits
    ? 'https://wa.me/' . $companyWhatsappDigits . '?text=' . rawurlencode($companyWhatsappMessage)
    : null;
@endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<style>
  .portal-date-input {
    min-height: 2.65rem;
    width: 8.8rem;
    border-radius: 0.85rem;
    border: 1px solid #dbe4f0;
    background: #fff;
    padding: 0.55rem 0.85rem 0.55rem 2.45rem;
    color: #334155;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
  }
  .portal-date-input::placeholder {
    color: #94a3b8;
    font-weight: 600;
  }
  .portal-date-wrap {
    position: relative;
  }
  .portal-date-wrap svg {
    position: absolute;
    left: 0.82rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #94a3b8;
    pointer-events: none;
  }
  .flatpickr-calendar.portal-calendar {
    width: 18rem !important;
    padding: 0.85rem;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
    font-family: inherit;
  }
  .flatpickr-calendar.portal-calendar:before,
  .flatpickr-calendar.portal-calendar:after {
    display: none;
  }
  .portal-calendar .flatpickr-months {
    align-items: center;
    margin-bottom: 0.55rem;
  }
  .portal-calendar .flatpickr-current-month {
    height: auto;
    padding: 0;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 900;
  }
  .portal-calendar .flatpickr-monthDropdown-months,
  .portal-calendar .numInputWrapper {
    font-weight: 900;
  }
  .portal-calendar .flatpickr-weekday {
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
  }
  .portal-calendar .dayContainer {
    gap: 0.18rem;
  }
  .portal-calendar .flatpickr-day {
    height: 2.15rem;
    max-width: 2.15rem;
    line-height: 2.15rem;
    border-radius: 0.8rem;
    color: #334155;
    font-weight: 700;
  }
  .portal-calendar .flatpickr-day.today {
    border-color: #d9f99d;
  }
  .portal-calendar .flatpickr-day.selected,
  .portal-calendar .flatpickr-day.startRange,
  .portal-calendar .flatpickr-day.endRange {
    background: #d9f99d;
    border-color: #d9f99d;
    color: #0f172a;
  }
  .portal-calendar .flatpickr-day:hover {
    background: #f1f5f9;
    border-color: #f1f5f9;
  }
  .portal-whatsapp-fab {
    position: fixed;
    right: clamp(1rem, 3vw, 2rem);
    bottom: clamp(1rem, 3vw, 2rem);
    z-index: 60;
    width: 4rem;
    height: 4rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #25d366;
    color: white;
    box-shadow: 0 18px 35px rgba(37, 211, 102, 0.32), 0 8px 18px rgba(15, 23, 42, 0.16);
    border: 4px solid rgba(255, 255, 255, 0.9);
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
  }
  .portal-whatsapp-fab:hover {
    transform: translateY(-2px) scale(1.03);
    background: #1ebe5d;
    box-shadow: 0 22px 40px rgba(37, 211, 102, 0.4), 0 10px 22px rgba(15, 23, 42, 0.18);
  }
  .portal-whatsapp-fab i {
    font-size: 2.15rem;
    line-height: 1;
  }
  @media (max-width: 640px) {
    .portal-whatsapp-fab {
      width: 3.5rem;
      height: 3.5rem;
      right: 1rem;
      bottom: 1rem;
      border-width: 3px;
    }
    .portal-whatsapp-fab i {
      font-size: 1.85rem;
    }
  }
</style>
<div class="min-h-screen bg-slate-50 flex flex-col">
  <!-- Header verde -->
  <!-- Header oscuro -->
  <header class="bg-[#182031] border-b border-white/10 sticky top-0 z-20 shadow-md">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        {{-- Logo empresa + separador + Portal Cliente --}}
        <div class="flex items-center gap-3">
          @if(!empty($settings['logo']))
            <img src="{{ $settings['logo'] }}" class="h-9 w-9 object-contain rounded-xl flex-shrink-0" style="background:rgba(255,255,255,.1);padding:2px">
          @else
            <div class="h-9 w-9 rounded-xl bg-white/10 flex items-center justify-center text-white font-extrabold text-sm flex-shrink-0">
              {{ strtoupper(substr($settings['company_name'] ?? 'E', 0, 1)) }}
            </div>
          @endif
          <span class="font-bold text-base text-white hidden sm:block">{{ $settings['company_name'] ?? 'Mi Empresa' }}</span>
          <div class="w-px h-6 bg-white/20 hidden sm:block mx-1"></div>
          <span class="text-sm text-white/60 hidden sm:block font-medium">Portal Cliente</span>
        </div>
        {{-- Derecha: info cliente + avatar/menu --}}
        <div class="flex items-center gap-3">
          <div class="text-right hidden sm:block">
            <div class="text-sm font-semibold text-white">{{ $client['empresa'] }}</div>
            <div class="text-xs text-white/50">{{ $client['contacto_nombre'] ?? 'Cliente' }}</div>
          </div>
          <div class="relative" id="avatar-menu-wrap">
            <button onclick="document.getElementById('avatar-dd').classList.toggle('hidden')"
              class="h-9 w-9 rounded-full bg-white/15 border border-white/20 text-white flex items-center justify-center font-bold text-xs shadow hover:bg-white/25 transition-all">
              {{ strtoupper(substr($client['empresa'], 0, 2)) }}
            </button>
            <div id="avatar-dd" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50">
              <div class="px-4 py-2 text-xs text-slate-400 border-b border-slate-100">{{ $client['empresa'] }}</div>
              @if(empty($useTokenLinks))
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button class="w-full text-left px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Cerrar sesión
                  </button>
                </form>
              @else
                <div class="px-4 py-2 text-xs text-slate-400">Acceso por enlace</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="flex-1 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

      @if(session('pw_changed'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm font-medium">✅ Contraseña actualizada. ¡Bienvenido al portal!</div>
      @endif
      @if(session('msg_ok'))
        <div class="bg-sky-50 border border-sky-200 rounded-xl px-4 py-3 text-sky-700 text-sm font-medium">✉️ {{ session('msg_ok') }}</div>
      @endif
      @if($errors->has('pago'))
        <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-rose-700 text-sm font-medium">{{ $errors->first('pago') }}</div>
      @endif

      {{-- ONBOARDING --}}
      <div id="onboarding-card" class="bg-gradient-to-r from-lime-50 to-emerald-50 border border-lime-200 rounded-2xl p-5 hidden">
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="font-bold text-slate-800 mb-1">👋 Primeros pasos en tu portal</div>
            <div class="text-sm text-slate-500">Completa estas acciones para sacarle el máximo provecho.</div>
          </div>
          <button onclick="dismissOnboarding()" class="text-slate-400 hover:text-slate-600 text-xs underline">Cerrar</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div id="ob-inv" class="flex flex-col items-center bg-white rounded-xl p-3 border border-slate-100 cursor-pointer hover:border-lime-300 transition-colors" onclick="scrollToId('invoices');markOnboarding('inv')">
            <div class="w-10 h-10 mb-2 rounded-xl bg-sky-50 flex items-center justify-center"><svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div><div class="text-xs font-semibold text-slate-700 text-center">Ver una factura</div>
          </div>
          <div id="ob-doc" class="flex flex-col items-center bg-white rounded-xl p-3 border border-slate-100 cursor-pointer hover:border-lime-300 transition-colors" onclick="scrollToId('documents');markOnboarding('doc')">
            <div class="w-10 h-10 mb-2 rounded-xl bg-amber-50 flex items-center justify-center"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg></div><div class="text-xs font-semibold text-slate-700 text-center">Ver documentos</div>
          </div>
          @if($companyWhatsappUrl)
            <a id="ob-msg" href="{{ $companyWhatsappUrl }}" target="_blank" rel="noopener" class="flex flex-col items-center bg-white rounded-xl p-3 border border-slate-100 cursor-pointer hover:border-lime-300 transition-colors no-underline" onclick="markOnboarding('msg')">
              <div class="w-10 h-10 mb-2 rounded-xl bg-emerald-50 flex items-center justify-center"><i class="fa-brands fa-whatsapp text-xl text-emerald-500"></i></div><div class="text-xs font-semibold text-slate-700 text-center">Enviar mensaje</div>
            </a>
          @else
            <div id="ob-msg" class="flex flex-col items-center bg-white/60 rounded-xl p-3 border border-slate-100 text-slate-400">
              <div class="w-10 h-10 mb-2 rounded-xl bg-slate-50 flex items-center justify-center"><i class="fa-brands fa-whatsapp text-xl text-slate-300"></i></div><div class="text-xs font-semibold text-center">Enviar mensaje</div>
            </div>
          @endif
          <div id="ob-proj" class="flex flex-col items-center bg-white rounded-xl p-3 border border-slate-100 cursor-pointer hover:border-lime-300 transition-colors" onclick="scrollToId('projects');markOnboarding('proj')">
            <div class="w-10 h-10 mb-2 rounded-xl bg-emerald-50 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></div><div class="text-xs font-semibold text-slate-700 text-center">Ver proyectos</div>
          </div>
        </div>
      </div>

      {{-- HERO + SALDO --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-lg">
          <h1 class="text-2xl font-bold mb-2">Bienvenido al Portal</h1>
          <p class="text-slate-300 mb-5 max-w-lg text-sm">Revisa el estado de tus proyectos, descarga facturas, envía mensajes y gestiona pagos de forma segura.</p>
          <div class="flex flex-wrap gap-2">
            <button onclick="scrollToId('invoices')" class="px-3 py-1.5 bg-lime-400 text-slate-900 font-bold rounded-lg hover:bg-lime-300 transition-colors text-xs">Facturas</button>
            <button onclick="scrollToId('payments')" class="px-3 py-1.5 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition-colors text-xs">Pagos</button>
            <button onclick="scrollToId('projects')" class="px-3 py-1.5 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition-colors text-xs">Proyectos</button>
            <button onclick="scrollToId('messages')" class="px-3 py-1.5 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition-colors text-xs">Mensajes</button>
            <button onclick="scrollToId('documents')" class="px-3 py-1.5 bg-white/10 text-white font-medium rounded-lg hover:bg-white/20 transition-colors text-xs">Documentos</button>
          </div>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-center">
          <div class="text-sm text-slate-500 font-medium mb-1">Saldo Pendiente</div>
          <div class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ format_currency($dueAmount, $clientCurrency ?? ($client['moneda'] ?? null)) }}</div>
          <div class="mt-4 pt-4 border-t border-slate-50 flex justify-between items-center">
            <span class="text-xs text-slate-400">Total Pagado</span>
            <span class="text-sm font-bold text-emerald-600">{{ format_currency($paidAmount, $clientCurrency ?? ($client['moneda'] ?? null)) }}</span>
          </div>
          @if($overdueAmount > 0)
          <div class="mt-2 flex justify-between items-center">
            <span class="text-xs text-rose-500">Vencido</span>
            <span class="text-sm font-bold text-rose-600">{{ format_currency($overdueAmount, $clientCurrency ?? ($client['moneda'] ?? null)) }}</span>
          </div>
          @endif
        </div>
      </div>

      {{-- ESTADO FINANCIERO VISUAL --}}
      <div>
        <h2 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          Estado Financiero
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total Facturado</div>
            <div class="text-2xl font-extrabold text-slate-900">{{ format_currency($totalInvoiced, $clientCurrency ?? ($client['moneda'] ?? null)) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $invoices->count() }} factura(s)</div>
          </div>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-emerald-100">
            <div class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-2">Total Pagado</div>
            <div class="text-2xl font-extrabold text-emerald-700">{{ format_currency($paidAmount, $clientCurrency ?? ($client['moneda'] ?? null)) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $invoices->where('estado','Pagada')->count() }} factura(s)</div>
          </div>
          <div class="bg-white rounded-2xl p-5 shadow-sm border {{ $overdueAmount > 0 ? 'border-rose-100' : 'border-slate-100' }}">
            <div class="text-xs font-bold {{ $overdueAmount > 0 ? 'text-rose-500' : 'text-slate-400' }} uppercase tracking-wider mb-2">Vencido</div>
            <div class="text-2xl font-extrabold {{ $overdueAmount > 0 ? 'text-rose-600' : 'text-slate-300' }}">{{ format_currency($overdueAmount, $clientCurrency ?? ($client['moneda'] ?? null)) }}</div>
            <div class="text-xs text-slate-400 mt-1">sin pagar después del vencimiento</div>
          </div>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-amber-100">
            <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-2">Próximos Vencimientos</div>
            <div class="space-y-1 mt-1">
              <div class="flex justify-between"><span class="text-slate-500 text-xs">7 días</span><span class="font-bold text-slate-800 text-sm">{{ format_currency($upcoming7, $clientCurrency ?? ($client['moneda'] ?? null)) }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500 text-xs">15 días</span><span class="font-bold text-slate-800 text-sm">{{ format_currency($upcoming15, $clientCurrency ?? ($client['moneda'] ?? null)) }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500 text-xs">30 días</span><span class="font-bold text-slate-800 text-sm">{{ format_currency($upcoming30, $clientCurrency ?? ($client['moneda'] ?? null)) }}</span></div>
            </div>
          </div>
        </div>
      </div>

      {{-- FACTURAS --}}
      <div id="invoices">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
          <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Facturas
          </h2>
	          <form method="GET" action="{{ !empty($useTokenLinks) ? route('portal.zip-facturas', ['id'=>$client['id'], 'token'=>$token]) : route('portal.auth.zip-facturas') }}" class="flex flex-wrap items-center gap-2">
	            <label class="portal-date-wrap">
	              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
	              <input type="text" name="desde" class="portal-date-input js-portal-date" placeholder="Desde" autocomplete="off">
	            </label>
	            <label class="portal-date-wrap">
	              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
	              <input type="text" name="hasta" class="portal-date-input js-portal-date" placeholder="Hasta" autocomplete="off">
	            </label>
	            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-3 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
	              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
	              ZIP
	            </button>
          </form>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-100">
                <tr>
                  <th class="px-5 py-3">Número</th><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Vencimiento</th>
                  <th class="px-5 py-3 text-right">Monto</th><th class="px-5 py-3 text-center">Estado</th><th class="px-5 py-3 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                @forelse($invoices as $inv)
                  @php
                    $isPending = $inv['estado'] !== 'Pagada';
                    $isOver = $isPending && !empty($inv['vencimiento']) && \Carbon\Carbon::parse($inv['vencimiento'])->isPast();
                    $estadoInv = ($inv['estado'] ?? '') === 'Enviada' ? 'Pendiente' : ($inv['estado'] ?? '');
                    $sc = ['Pendiente'=>'bg-amber-100 text-amber-700','Pagada'=>'bg-emerald-100 text-emerald-700','Vencida'=>'bg-rose-100 text-rose-700','En borrador'=>'bg-slate-100 text-slate-600'];
                  @endphp
                  <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-4 font-medium text-slate-900">{{ $inv['numero'] }}</td>
                    <td class="px-5 py-4 text-slate-500">{{ \Carbon\Carbon::parse($inv['fecha'])->format('d M, Y') }}</td>
                    <td class="px-5 py-4">@if(!empty($inv['vencimiento']))<span class="{{ $isOver ? 'text-rose-500 font-bold' : 'text-slate-500' }}">{{ \Carbon\Carbon::parse($inv['vencimiento'])->format('d M, Y') }}</span>@else<span class="text-slate-300">—</span>@endif</td>
                    <td class="px-5 py-4 text-right font-bold text-slate-800">{{ format_currency($inv['total'], $inv['moneda'] ?? null) }}</td>
	                    <td class="px-5 py-4 text-center"><span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $sc[$estadoInv] ?? 'bg-slate-100 text-slate-600' }}">{{ $estadoInv }}</span></td>
	                    <td class="px-5 py-4 text-right">
	                      <div class="flex items-center justify-end gap-2">
		                        @php
		                          $viewInvoiceUrl = !empty($useTokenLinks)
		                            ? route('portal.invoice', ['id'=>$client['id'],'token'=>$token,'invoiceId'=>$inv['id']])
		                            : route('portal.auth.invoice', $inv['id']);
		                          $downloadInvoiceUrl = !empty($useTokenLinks)
		                            ? route('portal.invoice.pdf', ['id'=>$client['id'],'token'=>$token,'invoiceId'=>$inv['id']])
		                            : route('portal.auth.invoice.pdf', ['invoiceId'=>$inv['id']]);
		                        @endphp
	                          @if($isPending)
	                            @php
	                              $stripePayUrl = !empty($useTokenLinks)
                                ? route('portal.pay.checkout', ['id'=>$client['id'],'token'=>$token,'invoiceId'=>$inv['id']])
                                : route('portal.auth.pay.checkout', $inv['id']);
                            @endphp
                            <a href="{{ $stripePayUrl }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-lime-400 text-slate-900 text-xs font-bold hover:bg-lime-300 transition-colors">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
	                              Pagar
	                            </a>
	                          @endif
	                        <a href="{{ $viewInvoiceUrl }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-colors">
	                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
	                          Ver
	                        </a>
	                        <a href="{{ $downloadInvoiceUrl }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-colors">
	                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 19h16"/></svg>
	                          Descargar
	                        </a>
	                      </div>
	                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No hay facturas disponibles.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- CENTRO DE PAGOS --}}
      <div id="payments">
        <h2 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          Historial de Pagos
        </h2>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-100">
                <tr><th class="px-5 py-3">Factura</th><th class="px-5 py-3">Fecha Pago</th><th class="px-5 py-3">Método</th><th class="px-5 py-3 text-right">Monto</th><th class="px-5 py-3">Nota</th></tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                @forelse($pagosHistory as $pago)
                  <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-4 font-medium text-slate-900">{{ $pago['numero'] }}</td>
                    <td class="px-5 py-4 text-slate-500">{{ !empty($pago['fecha']) ? \Carbon\Carbon::parse($pago['fecha'])->format('d M, Y') : '—' }}</td>
                    <td class="px-5 py-4 text-slate-500">{{ $pago['metodo'] ?? '—' }}</td>
                    <td class="px-5 py-4 text-right font-bold text-emerald-700">{{ format_currency($pago['monto'] ?? 0, $pago['moneda'] ?? ($client['moneda'] ?? null)) }}</td>
                    <td class="px-5 py-4 text-slate-400 text-xs">{{ $pago['nota'] ?? '' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Sin registros de pagos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- PROYECTOS --}}
      <div id="projects">
        <h2 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
          <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Proyectos Activos
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @forelse($projects as $p)
            @php
              $riesgo = $p['riesgo'] ?? 'verde';
              $riesgoColor = ['verde'=>'bg-emerald-500','amarillo'=>'bg-amber-400','rojo'=>'bg-rose-500'][$riesgo] ?? 'bg-slate-300';
              $riesgoLabel = ['verde'=>'En curso','amarillo'=>'Atención','rojo'=>'En riesgo'][$riesgo] ?? 'Normal';
              $hitos = $p['hitos'] ?? [];
              $entregables = $p['entregables'] ?? [];
              $pct = (int)($p['progreso'] ?? 0);
              $barColor = $pct >= 75 ? 'bg-emerald-500' : ($pct >= 40 ? 'bg-amber-400' : 'bg-rose-400');
            @endphp
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h3 class="font-bold text-slate-900">{{ $p['titulo'] }}</h3>
                  @if(!empty($p['miembro']))<div class="text-xs text-slate-400 mt-0.5">Responsable: {{ $p['miembro'] }}</div>@endif
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-600 uppercase">{{ $p['etapa'] }}</span>
                  <div class="flex items-center gap-1"><div class="w-2.5 h-2.5 rounded-full {{ $riesgoColor }}"></div><span class="text-xs text-slate-400">{{ $riesgoLabel }}</span></div>
                </div>
              </div>
              <div class="mb-3">
                <div class="flex justify-between text-xs text-slate-500 mb-1"><span>Progreso</span><span class="font-bold">{{ $pct }}%</span></div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden"><div class="progress-fill-anim {{ $barColor }} h-2 rounded-full" style="width:{{ $pct }}%"></div></div>
              </div>
              <div class="flex gap-4 text-xs text-slate-500 mb-3">
                @if(!empty($p['inicio']))<span>Inicio: <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($p['inicio'])->format('d M, Y') }}</span></span>@endif
                @if(!empty($p['vencimiento']))<span>Vence: <span class="font-medium {{ \Carbon\Carbon::parse($p['vencimiento'])->isPast() ? 'text-rose-600' : 'text-slate-700' }}">{{ \Carbon\Carbon::parse($p['vencimiento'])->format('d M, Y') }}</span></span>@endif
              </div>
              @if(!empty($hitos))
                <div class="mb-3">
                  <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Hitos</div>
                  <div class="space-y-1">
                    @foreach($hitos as $hito)
                      @php $hitoDone = !empty($hito['done'] ?? $hito['completado'] ?? false); @endphp
                      <div class="flex items-center gap-2 text-sm">
                        <div class="w-4 h-4 rounded-full border-2 flex-shrink-0 {{ $hitoDone ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300' }} flex items-center justify-center">
                          @if($hitoDone)<svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>@endif
                        </div>
                        <span class="{{ $hitoDone ? 'line-through text-slate-400' : 'text-slate-700' }}">{{ is_array($hito) ? ($hito['titulo'] ?? '') : $hito }}</span>
                        @if(is_array($hito) && !empty($hito['fecha']))<span class="text-xs text-slate-400 ml-auto">{{ \Carbon\Carbon::parse($hito['fecha'])->format('d M') }}</span>@endif
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
              @if(!empty($entregables))
                <div>
                  <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Entregables</div>
                  <div class="flex flex-wrap gap-1.5">
                    @foreach($entregables as $ent)
                      @php $entDone = !empty($ent['done'] ?? $ent['completado'] ?? false); @endphp
                      <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $entDone ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ is_array($ent) ? ($ent['titulo'] ?? '') : $ent }}</span>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          @empty
            <div class="col-span-full bg-white rounded-2xl p-8 text-center text-slate-400 border border-dashed border-slate-200">No hay proyectos activos.</div>
          @endforelse
        </div>
      </div>

      {{-- TIMELINE --}}

      {{-- DOCUMENTOS --}}
      <div id="documents">
        @php
          $portalDocIcons = [
            'pdf'  => ['bg'=>'bg-rose-50','color'=>'text-rose-500'],
            'drive'=> ['bg'=>'bg-sky-50','color'=>'text-sky-500'],
            'jpg'  => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
            'jpeg' => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
            'png'  => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
            'xlsx' => ['bg'=>'bg-emerald-50','color'=>'text-emerald-500'],
            'xls'  => ['bg'=>'bg-emerald-50','color'=>'text-emerald-500'],
            'docx' => ['bg'=>'bg-indigo-50','color'=>'text-indigo-500'],
            'doc'  => ['bg'=>'bg-indigo-50','color'=>'text-indigo-500'],
            'mp3'  => ['bg'=>'bg-violet-50','color'=>'text-violet-500'],
            'mp4'  => ['bg'=>'bg-purple-50','color'=>'text-purple-500'],
            'zip'  => ['bg'=>'bg-orange-50','color'=>'text-orange-500'],
          ];
          $normalizeFolderPath = function (string $folder): string {
            return collect(explode('/', str_replace('\\', '/', $folder)))
              ->map(fn($p) => trim((string) $p))
              ->filter(fn($p) => $p !== '')
              ->implode(' / ');
          };
          $folderLabel = function (string $path): string {
            $parts = array_values(array_filter(array_map('trim', explode('/', str_replace(' / ', '/', $path)))));
            if (count($parts) >= 3 && strtolower((string) ($parts[0] ?? '')) === 'facturas') {
              $monthPart = (string) end($parts);
              if (preg_match('/^(0[1-9]|1[0-2])$/', $monthPart)) {
                $monthNames = [
                  '01' => 'Enero',
                  '02' => 'Febrero',
                  '03' => 'Marzo',
                  '04' => 'Abril',
                  '05' => 'Mayo',
                  '06' => 'Junio',
                  '07' => 'Julio',
                  '08' => 'Agosto',
                  '09' => 'Septiembre',
                  '10' => 'Octubre',
                  '11' => 'Noviembre',
                  '12' => 'Diciembre',
                ];
                return $monthNames[$monthPart] ?? $monthPart;
              }
            }
            return !empty($parts) ? end($parts) : $path;
          };
          $folderParent = function (string $path): string {
            $parts = array_values(array_filter(array_map('trim', explode('/', str_replace(' / ', '/', $path)))));
            array_pop($parts);
            return implode(' / ', $parts);
          };

          $documents = collect($documents)->map(function ($doc) use ($normalizeFolderPath) {
            $doc['_folder_path'] = $normalizeFolderPath((string) ($doc['folder'] ?? ''));
            return $doc;
          })->values();

          $docsByFolder = $documents
            ->groupBy('_folder_path')
            ->reject(fn($rows, $path) => trim((string) $path) === '');

          $leafFolderNames = $docsByFolder->keys()->values();
          $allFolderNames = $leafFolderNames
            ->flatMap(function ($path) {
              $parts = array_values(array_filter(array_map('trim', explode('/', str_replace(' / ', '/', (string) $path)))));
              $acc = [];
              $run = [];
              foreach ($parts as $part) {
                $run[] = $part;
                $acc[] = implode(' / ', $run);
              }
              return $acc;
            })
            ->unique(fn($path) => strtolower((string) $path))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

          $folderCount = function (string $path) use ($docsByFolder): int {
            return (int) $docsByFolder
              ->filter(fn($rows, $folderPath) => (string) $folderPath === $path || str_starts_with((string) $folderPath, $path.' / '))
              ->flatten(1)
              ->count();
          };

          $folderNodes = $allFolderNames->map(function ($path) use ($folderParent, $folderLabel, $folderCount, $allFolderNames) {
            $parent = $folderParent((string) $path);
            $hasChildren = $allFolderNames->contains(fn($candidate) => $folderParent((string) $candidate) === (string) $path);
            return [
              'path' => (string) $path,
              'parent' => $parent,
              'depth' => $parent === '' ? 0 : substr_count($path, ' / '),
              'label' => $folderLabel((string) $path),
              'count' => $folderCount((string) $path),
              'has_children' => $hasChildren,
            ];
          })->values();

          $displayFolderPath = function (string $path) use ($folderLabel): string {
            $parts = array_values(array_filter(array_map('trim', explode('/', str_replace(' / ', '/', $path)))));
            if (empty($parts)) return $path;
            if (count($parts) >= 3 && strtolower((string) ($parts[0] ?? '')) === 'facturas') {
              $parts[count($parts)-1] = $folderLabel($path);
            }
            return implode(' / ', $parts);
          };

          $activeDocFolder = $normalizeFolderPath((string) request('doc_folder', ''));
        @endphp
        <div class="bg-white/80 backdrop-blur rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
          {{-- Toolbar --}}
          <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3 flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2 text-slate-700 font-semibold text-sm">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
              Documentos
            </div>
            <div class="flex-1"></div>
            <form method="GET" action="{{ !empty($useTokenLinks) ? route('portal.dashboard', ['id'=>$client['id'],'token'=>$token]) : route('portal.auth.dashboard') }}" class="flex items-center gap-2">
              <input name="doc_q" value="{{ $docQ??'' }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:ring-2 focus:ring-lime-300 outline-none w-40 sm:w-56" placeholder="Buscar...">
              <select name="doc_folder" class="rounded-xl border border-slate-200 px-3 py-1.5 text-sm">
                <option value="">Todas las carpetas</option>
                @foreach($allFolderNames as $fn)<option value="{{ $fn }}" @selected($activeDocFolder===$fn)>{{ $displayFolderPath((string)$fn) }}</option>@endforeach
              </select>
              <button class="px-3 py-1.5 rounded-xl bg-lime-400 text-slate-900 text-sm font-semibold hover:bg-lime-300 transition-colors">Buscar</button>
            </form>
          </div>

          <div class="flex min-h-[320px]">
            {{-- Sidebar carpetas --}}
            <aside class="w-48 border-r border-slate-100 bg-slate-50/50 p-3 hidden sm:block flex-shrink-0">
              <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">Carpetas</div>
              <a href="{{ !empty($useTokenLinks) ? route('portal.dashboard', ['id'=>$client['id'],'token'=>$token,'doc_folder'=>'']) : route('portal.auth.dashboard') }}#documents"
                data-folder=""
                class="js-doc-folder-link flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm mb-0.5 transition-colors {{ $activeDocFolder==='' ? 'bg-lime-100 text-lime-800 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                Todos
              </a>
              @foreach($folderNodes as $node)
                <div
                  data-folder-row="{{ $node['path'] }}"
                  data-parent-folder="{{ $node['parent'] }}"
                  data-folder-depth="{{ $node['depth'] }}"
                  class="js-doc-folder-row flex items-center gap-1 mb-0.5"
                  style="padding-left: {{ $node['depth'] * 14 }}px;">
                  @if($node['has_children'])
                    <button type="button"
                      class="js-doc-folder-toggle w-5 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition-colors"
                      data-toggle-folder="{{ $node['path'] }}"
                      aria-label="Desplegar carpeta {{ $node['label'] }}"
                      aria-expanded="false">
                      <svg class="js-doc-folder-chevron w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
                    </button>
                  @else
                    <span class="w-5 h-8 shrink-0"></span>
                  @endif
                  <a href="{{ !empty($useTokenLinks) ? route('portal.dashboard', ['id'=>$client['id'],'token'=>$token,'doc_folder'=>$node['path']]) : route('portal.auth.dashboard',['doc_folder'=>$node['path']]) }}#documents"
                    data-folder="{{ $node['path'] }}"
                    class="js-doc-folder-link min-w-0 flex-1 flex items-center gap-2 px-2 py-1.5 rounded-lg text-sm transition-colors {{ $activeDocFolder===$node['path'] ? 'bg-lime-100 text-lime-800 font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    <span class="truncate">{{ $node['label'] }}</span>
                    <span class="ml-auto text-xs text-slate-400">{{ $node['count'] }}</span>
                  </a>
                </div>
              @endforeach
            </aside>

            {{-- Contenido archivos --}}
            <div class="flex-1 p-4">
              @if($documents->isEmpty())
                <div class="flex flex-col items-center justify-center h-48 text-slate-400">
                  <svg class="w-12 h-12 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  <p class="text-sm">No hay documentos disponibles.</p>
                </div>
              @else
                <div id="portal-folder-toolbar" class="hidden mb-3">
                  <button id="portal-folder-up" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Subir un nivel
                  </button>
                </div>

                <div id="portal-folder-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-3">
                  @foreach($folderNodes as $node)
                    <a href="{{ !empty($useTokenLinks) ? route('portal.dashboard', ['id'=>$client['id'],'token'=>$token,'doc_folder'=>$node['path']]) : route('portal.auth.dashboard',['doc_folder'=>$node['path']]) }}#documents"
                      data-folder-card="1"
                      data-folder-path="{{ $node['path'] }}"
                      data-parent-folder="{{ $node['parent'] }}"
                      title="Clic para abrir"
                      class="js-doc-folder-card group flex flex-col p-3 bg-white rounded-xl border border-slate-100 hover:shadow-md hover:border-lime-300 transition-all hidden cursor-pointer">
                      <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                      </div>
                      <div class="text-xs font-semibold text-slate-800 leading-tight line-clamp-2 mb-1">{{ $node['label'] }}</div>
                      <div class="mt-auto text-[10px] text-slate-400">{{ $node['count'] }} archivo(s)</div>
                    </a>
                  @endforeach
                </div>

                <div id="portal-doc-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                  @foreach($documents as $doc)
                    @php
                      $ext = strtolower($doc['ext'] ?? ($doc['storage']==='drive' ? 'drive' : 'file'));
                      $di = $portalDocIcons[$ext] ?? ['bg'=>'bg-slate-50','color'=>'text-slate-500'];
                      if (($doc['storage'] ?? '') === 'factura' && !empty($doc['source_id'])) {
                        $dlUrl = !empty($useTokenLinks)
                          ? route('portal.invoice', ['id'=>$client['id'],'token'=>$token,'invoiceId'=>$doc['source_id']])
                          : route('portal.auth.invoice', ['invoiceId'=>$doc['source_id']]);
                      } else {
                        $dlUrl = !empty($useTokenLinks)
                          ? route('portal.document.download',['id'=>$client['id'],'token'=>$token,'docId'=>$doc['id']])
                          : route('portal.auth.document.download',$doc['id']);
                      }
                      $fmtSize = function($bytes){ if(!is_numeric($bytes)||$bytes<=0) return ''; $u=['B','KB','MB','GB']; $i=0; $v=(float)$bytes; while($v>=1024&&$i<3){$v/=1024;$i++;} return number_format($v,$i===0?0:1).' '.$u[$i]; };
                    @endphp
                    <a href="{{ $dlUrl }}" @if(($doc['storage']??'local')==='drive') target="_blank" @endif
                      data-folder="{{ $doc['_folder_path'] ?? ($doc['folder'] ?? '') }}"
                      data-doc-card="1"
                      class="group flex flex-col p-3 bg-white rounded-xl border border-slate-100 hover:shadow-md hover:border-lime-300 transition-all">
                      <div class="w-10 h-10 rounded-xl {{ $di['bg'] }} flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                        @if($ext==='pdf')
                          <svg class="w-5 h-5 {{ $di['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        @elseif(in_array($ext,['jpg','jpeg','png','gif','webp']))
                          <svg class="w-5 h-5 {{ $di['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @elseif(in_array($ext,['xlsx','xls','csv']))
                          <svg class="w-5 h-5 {{ $di['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        @elseif($ext==='drive')
                          <svg class="w-5 h-5 {{ $di['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        @else
                          <svg class="w-5 h-5 {{ $di['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @endif
                      </div>
                      <div class="text-xs font-semibold text-slate-800 leading-tight line-clamp-2 mb-1">{{ $doc['name'] ?? ($doc['original_name'] ?? 'Documento') }}</div>
                      <div class="mt-auto flex items-center justify-between gap-1">
                        <span class="text-[10px] font-bold uppercase {{ $di['color'] }}">{{ strtoupper($ext) }}</span>
                        @if(!empty($doc['size']))<span class="text-[10px] text-slate-400">{{ $fmtSize($doc['size']) }}</span>@endif
                      </div>
                    </a>
                  @endforeach
                </div>
                <div id="portal-doc-empty-filter" class="hidden flex flex-col items-center justify-center h-48 text-slate-400">
                  <svg class="w-12 h-12 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  <p id="portal-doc-empty-text" class="text-sm">No hay documentos en esta carpeta.</p>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
    <div class="max-w-5xl mx-auto px-4 text-center text-sm text-slate-400">
      &copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'Mi Empresa' }}. Todos los derechos reservados.
    </div>
  </footer>
  @if($companyWhatsappUrl)
    <a href="{{ $companyWhatsappUrl }}" target="_blank" rel="noopener" class="portal-whatsapp-fab" aria-label="Contactar por WhatsApp" title="Contactar por WhatsApp">
      <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
    </a>
  @endif
</div>

<script>
if (window.flatpickr) {
  flatpickr.localize(flatpickr.l10ns.es);
  flatpickr('.js-portal-date', {
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    allowInput: true,
    disableMobile: true,
    monthSelectorType: 'static',
    onReady: function (_, __, instance) {
      instance.calendarContainer.classList.add('portal-calendar');
      if (instance.altInput) {
        instance.altInput.classList.add('portal-date-input');
        instance.altInput.addEventListener('focus', () => instance.open());
        instance.altInput.addEventListener('click', () => instance.open());
      }
    }
  });
}
function scrollToId(id) { const el = document.getElementById(id); if(el) el.scrollIntoView({behavior:'smooth'}); }
// Close avatar dropdown on outside click
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('avatar-menu-wrap');
  const dd = document.getElementById('avatar-dd');
  if (wrap && dd && !wrap.contains(e.target)) dd.classList.add('hidden');
});
// Onboarding
(function() {
  const done = JSON.parse(localStorage.getItem('ob_done') || '{}');
  const keys = ['inv','doc','msg','proj'];
  if (!keys.every(k => done[k])) {
    const card = document.getElementById('onboarding-card');
    if (card) card.classList.remove('hidden');
    keys.forEach(k => { if(done[k]) { const el = document.getElementById('ob-'+k); if(el) el.classList.add('opacity-40','pointer-events-none'); } });
  }
})();
function markOnboarding(k) { const d=JSON.parse(localStorage.getItem('ob_done')||'{}'); d[k]=true; localStorage.setItem('ob_done',JSON.stringify(d)); }
function dismissOnboarding() { ['inv','doc','msg','proj'].forEach(k=>markOnboarding(k)); const c=document.getElementById('onboarding-card'); if(c) c.classList.add('hidden'); }
// Scroll chat to bottom
(function(){ const b=document.getElementById('chat-box'); if(b) b.scrollTop=b.scrollHeight; })();

// Folder filter without page reload (keeps current scroll position)
(function () {
  const links = Array.from(document.querySelectorAll('.js-doc-folder-link'));
  const folderRows = Array.from(document.querySelectorAll('.js-doc-folder-row'));
  const folderToggles = Array.from(document.querySelectorAll('.js-doc-folder-toggle'));
  const folderCardLinks = Array.from(document.querySelectorAll('.js-doc-folder-card'));
  const cards = Array.from(document.querySelectorAll('[data-doc-card="1"]'));
  const folderCards = Array.from(document.querySelectorAll('[data-folder-card="1"]'));
  const folderToolbar = document.getElementById('portal-folder-toolbar');
  const upBtn = document.getElementById('portal-folder-up');
  const emptyState = document.getElementById('portal-doc-empty-filter');
  const emptyText = document.getElementById('portal-doc-empty-text');
  if (!links.length || !cards.length) return;

  const activeClass = ['bg-lime-100', 'text-lime-800', 'font-semibold'];
  const inactiveClass = ['text-slate-600'];
  const openFoldersKey = 'portal_doc_open_folders';
  let openFolders = new Set(JSON.parse(localStorage.getItem(openFoldersKey) || '[]'));

  const parentPath = (path) => {
    if (!path) return '';
    const parts = String(path).split(' / ').filter(Boolean);
    parts.pop();
    return parts.join(' / ');
  };

  const ancestorPaths = (path) => {
    const parts = String(path || '').split(' / ').filter(Boolean);
    const ancestors = [];
    while (parts.length > 1) {
      parts.pop();
      ancestors.push(parts.join(' / '));
    }
    return ancestors;
  };

  const persistOpenFolders = () => {
    localStorage.setItem(openFoldersKey, JSON.stringify(Array.from(openFolders)));
  };

  const updateFolderTreeVisibility = () => {
    folderRows.forEach((row) => {
      const path = row.dataset.folderRow || '';
      const parent = row.dataset.parentFolder || '';
      const visible = parent === '' || ancestorPaths(path).every((ancestor) => openFolders.has(ancestor));
      row.classList.toggle('hidden', !visible);
    });

    folderToggles.forEach((toggle) => {
      const path = toggle.dataset.toggleFolder || '';
      const isOpen = openFolders.has(path);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      const chevron = toggle.querySelector('.js-doc-folder-chevron');
      if (chevron) chevron.classList.toggle('rotate-90', isOpen);
    });
  };

  const normalizeInitialFolder = (path) => {
    const raw = String(path || '').trim();
    const m = raw.match(/^Facturas\s*\/\s*(\d{4})-(\d{2})$/i);
    if (m) return `Facturas / ${m[1]} / ${m[2]}`;
    return raw;
  };

  const navigateToFolder = (selectedFolder) => {
    ancestorPaths(selectedFolder).forEach((ancestor) => openFolders.add(ancestor));
    persistOpenFolders();
    updateFolderTreeVisibility();
    applyFolderFilter(selectedFolder);
    const url = new URL(window.location.href);
    if (selectedFolder) url.searchParams.set('doc_folder', selectedFolder);
    else url.searchParams.delete('doc_folder');
    url.hash = 'documents';
    window.history.replaceState({}, '', url.toString());
  };

  const setActiveLink = (selectedFolder) => {
    links.forEach((link) => {
      const isActive = (link.dataset.folder || '') === selectedFolder;
      if (isActive) {
        activeClass.forEach(c => link.classList.add(c));
        link.classList.remove('hover:bg-slate-100');
      } else {
        activeClass.forEach(c => link.classList.remove(c));
        inactiveClass.forEach(c => link.classList.add(c));
        link.classList.add('hover:bg-slate-100');
      }
    });
  };

  const applyFolderFilter = (selectedFolder) => {
    let visibleFolders = 0;
    folderCards.forEach((card) => {
      const parentFolder = card.dataset.parentFolder || '';
      const show = parentFolder === selectedFolder;
      card.classList.toggle('hidden', !show);
      if (show) visibleFolders += 1;
    });

    let visibleDocs = 0;
    cards.forEach((card) => {
      const cardFolder = card.dataset.folder || '';
      const show = selectedFolder !== '' && visibleFolders === 0 && cardFolder === selectedFolder;
      card.classList.toggle('hidden', !show);
      if (show) visibleDocs += 1;
    });

    if (folderToolbar) {
      folderToolbar.classList.toggle('hidden', selectedFolder === '');
    }

    if (emptyState) {
      const shouldShowEmpty = visibleFolders === 0 && visibleDocs === 0;
      emptyState.classList.toggle('hidden', !shouldShowEmpty);
      if (shouldShowEmpty && emptyText) {
        emptyText.textContent = selectedFolder === ''
          ? 'No hay carpetas disponibles.'
          : 'No hay documentos en esta carpeta.';
      }
    }

    setActiveLink(selectedFolder);

    const folderSelect = document.querySelector('select[name="doc_folder"]');
    if (folderSelect) {
      folderSelect.value = selectedFolder;
    }

    if (upBtn) {
      upBtn.dataset.parentFolder = parentPath(selectedFolder);
    }
  };

  links.forEach((link) => {
    link.addEventListener('click', (ev) => {
      ev.preventDefault();
      const selectedFolder = link.dataset.folder || link.dataset.folderPath || '';
      navigateToFolder(selectedFolder);
    });
  });

  folderCardLinks.forEach((link) => {
    link.addEventListener('click', (ev) => {
      ev.preventDefault();
      const selectedFolder = link.dataset.folderPath || '';
      navigateToFolder(selectedFolder);
    });
  });

  folderToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const path = toggle.dataset.toggleFolder || '';
      if (!path) return;
      if (openFolders.has(path)) openFolders.delete(path);
      else openFolders.add(path);
      persistOpenFolders();
      updateFolderTreeVisibility();
    });
  });

  if (upBtn) {
    upBtn.addEventListener('click', () => {
      const selectedFolder = upBtn.dataset.parentFolder || '';
      navigateToFolder(selectedFolder);
    });
  }

  const initialFolder = normalizeInitialFolder(new URLSearchParams(window.location.search).get('doc_folder') || '');
  ancestorPaths(initialFolder).forEach((ancestor) => openFolders.add(ancestor));
  updateFolderTreeVisibility();
  applyFolderFilter(initialFolder);
})();
</script>
@endsection
