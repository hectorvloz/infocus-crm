@extends('layouts.settings')

@section('title', 'Integraciones')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Integraciones</h2>
    <p class="text-slate-500">Organiza y conecta tu CRM por categorias: pagos, Google, redes y otras herramientas.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form action="{{ route('settings.integrations.update') }}" method="POST" class="space-y-6" id="integrations-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" id="active_tab" value="{{ old('active_tab', 'pagos') }}">

        <div class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
            <button type="button" data-tab="pagos" class="integration-tab-btn px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-white">Pagos</button>
            <button type="button" data-tab="google" class="integration-tab-btn px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-white">Google</button>
            <button type="button" data-tab="redes" class="integration-tab-btn px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-white">Redes</button>
            <button type="button" data-tab="otras" class="integration-tab-btn px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-white">Otras</button>
        </div>

        <div data-panel="pagos" class="integration-panel space-y-8">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-bold text-slate-800 mb-3">Pasarela activa para el boton "Pagar" del portal cliente</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer">
                        <input type="radio" name="payment_gateway" value="stripe" class="text-lime-500 focus:ring-lime-300" {{ ($settings['payment_gateway'] ?? 'stripe') === 'stripe' ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Stripe</div>
                            <div class="text-xs text-slate-500">Checkout con tarjeta</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer">
                        <input type="radio" name="payment_gateway" value="paypal" class="text-lime-500 focus:ring-lime-300" {{ ($settings['payment_gateway'] ?? '') === 'paypal' ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">PayPal</div>
                            <div class="text-xs text-slate-500">Checkout de PayPal</div>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 cursor-pointer">
                        <input type="radio" name="payment_gateway" value="wompi" class="text-lime-500 focus:ring-lime-300" {{ ($settings['payment_gateway'] ?? '') === 'wompi' ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Wompi</div>
                            <div class="text-xs text-slate-500">Widget Checkout Web</div>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Stripe (Pagos con tarjeta)</h3>
                        <p class="text-xs text-slate-500">Permite cobros en el portal cliente con tarjeta.</p>
                    </div>
                    @if(!empty($settings['stripe_secret']))
                        <span class="ml-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">Configurado</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:pl-13">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Modo</label>
                        <select name="stripe_mode" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="test" {{ ($settings['stripe_mode'] ?? 'test') == 'test' ? 'selected' : '' }}>Test</option>
                            <option value="live" {{ ($settings['stripe_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Publishable Key <span class="text-slate-400 font-normal">(pk_live_... o pk_test_...)</span></label>
                        <input type="text" name="stripe_key" value="{{ $settings['stripe_key'] ?? '' }}" placeholder="pk_live_..." class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Secret Key <span class="text-slate-400 font-normal">(sk_live_... o sk_test_...)</span></label>
                        <input type="password" name="stripe_secret" placeholder="{{ !empty($settings['stripe_secret']) ? 'Dejar vacio para mantener la actual' : 'sk_live_...' }}" autocomplete="new-password" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                        @if(!empty($settings['stripe_secret']))
                            <p class="mt-1 text-xs text-slate-400">La key actual esta guardada cifrada. Solo escribe aqui si deseas cambiarla.</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Moneda para cobros <span class="text-slate-400 font-normal">(ej: usd, cop, eur)</span></label>
                        <input type="text" name="stripe_currency" value="{{ $settings['stripe_currency'] ?? 'usd' }}" maxlength="3" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs uppercase" placeholder="usd">
                        <p class="mt-1 text-xs text-slate-400">Codigo ISO 4217 en minusculas.</p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">PayPal</h3>
                        <p class="text-xs text-slate-500">Procesamiento de pagos online.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:pl-13">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client ID</label>
                        <input type="text" name="paypal_client_id" value="{{ $settings['paypal_client_id'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Secret</label>
                        <input type="password" name="paypal_secret" placeholder="{{ !empty($settings['paypal_secret']) ? 'Dejar vacio para mantener la actual' : 'Ingresa tu secret de PayPal' }}" autocomplete="new-password" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                        @if(!empty($settings['paypal_secret']))
                            <p class="mt-1 text-xs text-emerald-700">Secret guardado. Escribe uno nuevo solo si quieres reemplazarlo.</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Modo</label>
                        <select name="paypal_mode" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="sandbox" {{ ($settings['paypal_mode'] ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Test (Sandbox)</option>
                            <option value="live" {{ ($settings['paypal_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live (Produccion)</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 .896-4 2s1.79 2 4 2 4 .896 4 2-1.79 2-4 2m0-10V6m0 12v-2M4 12a8 8 0 1116 0 8 8 0 01-16 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Wompi</h3>
                        <p class="text-xs text-slate-500">Checkout Web para Colombia.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:pl-13">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Modo</label>
                        <select name="wompi_mode" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="test" {{ ($settings['wompi_mode'] ?? 'test') == 'test' ? 'selected' : '' }}>Test</option>
                            <option value="live" {{ ($settings['wompi_mode'] ?? '') == 'live' ? 'selected' : '' }}>Live</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Public Key</label>
                        <input type="text" name="wompi_public_key" value="{{ $settings['wompi_public_key'] ?? '' }}" placeholder="pub_prod_..." class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Integrity Secret</label>
                        <input type="password" name="wompi_integrity_secret" placeholder="{{ !empty($settings['wompi_integrity_secret']) ? '•••••••••• (guardado) - escribe solo para cambiar' : 'integrity_secret' }}" autocomplete="new-password" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs">
                        @if(!empty($settings['wompi_integrity_secret']))
                            <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-semibold">Integrity Secret guardado</div>
                        @else
                            <div class="mt-2 text-[11px] text-amber-600 font-semibold">Aun no hay Integrity Secret guardado</div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Moneda</label>
                        <input type="text" name="wompi_currency" value="{{ $settings['wompi_currency'] ?? 'COP' }}" maxlength="3" placeholder="COP" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs uppercase">
                    </div>
                </div>
                <div class="md:pl-13 mt-2 text-xs text-slate-500 space-y-1">
                    <div><strong>Si debes poner aqui:</strong> Llave publica en <strong>Public Key</strong> y Secreto de Integridad en <strong>Integrity Secret</strong>.</div>
                    <div><strong>No debes poner aqui:</strong> Llave privada ni Secreto de eventos.</div>
                    <div>Motivo: esos dos datos se usan para acciones de servidor/webhooks y no para abrir el checkout del cliente.</div>
                    <div>Wompi solo se usa para facturas en <strong>COP</strong>. Para otras monedas se usara Stripe o PayPal.</div>
                </div>
            </div>
        </div>

        <div data-panel="google" class="integration-panel hidden space-y-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Google Calendar</h3>
                        <p class="text-xs text-slate-500">Sincroniza entregas y vencimientos desde Proyectos.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 md:pl-13">
                    <input type="checkbox" id="google_calendar_enabled" name="google_calendar_enabled" class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-200" {{ !empty($settings['google_calendar_enabled']) ? 'checked' : '' }}>
                    <label for="google_calendar_enabled" class="text-sm text-slate-700">Activar integracion</label>
                    <div class="text-[10px] font-semibold {{ !empty($settings['google_calendar_access_token']) ? 'text-emerald-600 bg-emerald-100' : 'text-slate-400 bg-slate-200' }} px-2 py-0.5 rounded-full">
                        {{ !empty($settings['google_calendar_access_token']) ? 'Conectado' : 'Sin conectar' }}
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:pl-13 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Calendar ID</label>
                        <input type="text" name="google_calendar_id" value="{{ $settings['google_calendar_id'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs" placeholder="tu_calendario@group.calendar.google.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Embed URL</label>
                        <input type="text" name="google_calendar_embed_url" value="{{ $settings['google_calendar_embed_url'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs" placeholder="https://calendar.google.com/calendar/embed?...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client ID</label>
                        <input type="text" name="google_calendar_client_id" value="{{ $settings['google_calendar_client_id'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs" placeholder="xxxx.apps.googleusercontent.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client Secret</label>
                        <input type="password" name="google_calendar_client_secret" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm font-mono text-xs" placeholder="{{ !empty($settings['google_calendar_client_secret']) ? 'Dejar vacio para mantener la actual' : '••••••••••' }}">
                        @if(!empty($settings['google_calendar_client_secret']))
                            <p class="mt-1 text-[11px] text-emerald-600">Secret guardado. Escribe uno nuevo solo si deseas reemplazarlo.</p>
                        @endif
                    </div>
                </div>
                <div class="md:pl-13 mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('settings.integrations.google.connect') }}" class="px-4 py-2 rounded-full bg-emerald-500 text-white text-xs font-bold hover:bg-emerald-600">Conectar OAuth</a>
                    <button type="button" onclick="document.getElementById('google-disconnect-form').submit()" class="px-4 py-2 rounded-full border text-xs font-semibold text-slate-600 hover:bg-white">Desconectar</button>
                </div>
                <div class="md:pl-13 mt-3 text-xs text-slate-500">Redirect URI OAuth: <span class="font-mono">{{ route('settings.integrations.google.callback') }}</span></div>
            </div>

            <hr class="border-slate-100">

            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 19h8a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900">Google Meet (Leads)</h3>
                        <p class="text-xs text-slate-500">Configuracion para crear reuniones desde Leads.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 md:pl-13">
                    <input type="checkbox" id="google_meet_enabled" name="google_meet_enabled" class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-200" {{ !empty($settings['google_meet_enabled']) ? 'checked' : '' }}>
                    <label for="google_meet_enabled" class="text-sm text-slate-700">Activar reuniones por Meet en Leads</label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:pl-13 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Zona horaria</label>
                        <input type="text" name="google_meet_timezone" value="{{ $settings['google_meet_timezone'] ?? 'America/Bogota' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="America/Bogota">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Duracion por defecto (min)</label>
                        <input type="number" min="5" max="240" name="google_meet_default_duration" value="{{ $settings['google_meet_default_duration'] ?? 30 }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="30">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">URL fallback de Meet (opcional)</label>
                        <input type="url" name="google_meet_fallback_url" value="{{ $settings['google_meet_fallback_url'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="https://meet.google.com/...">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas internas para reuniones</label>
                        <textarea name="google_meet_notes" rows="2" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Mensaje interno para equipo comercial">{{ $settings['google_meet_notes'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div data-panel="redes" class="integration-panel hidden">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Redes sociales</h3>
                <p class="text-sm text-slate-600">Aqui agruparemos futuras integraciones de redes (Meta, LinkedIn, TikTok, etc.).</p>
                <p class="text-xs text-slate-500 mt-2">Por ahora no hay campos guardables activos para redes en tu CRM.</p>
            </div>
        </div>

        <div data-panel="otras" class="integration-panel hidden">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Otras integraciones</h3>
                <p class="text-sm text-slate-600">Esta sección queda libre por ahora para futuras integraciones del equipo.</p>
            </div>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Integraciones
            </button>
        </div>
    </form>

    <form id="google-disconnect-form" action="{{ route('settings.integrations.google.disconnect') }}" method="POST" class="hidden">
        @csrf
    </form>
</div>

<script>
  (function() {
    const buttons = Array.from(document.querySelectorAll('.integration-tab-btn'));
    const panels = Array.from(document.querySelectorAll('.integration-panel'));
    const input = document.getElementById('active_tab');

    function setActiveTab(tab) {
      buttons.forEach((btn) => {
        const active = btn.dataset.tab === tab;
        btn.classList.toggle('bg-white', active);
        btn.classList.toggle('text-slate-900', active);
        btn.classList.toggle('shadow-sm', active);
        btn.classList.toggle('text-slate-600', !active);
      });

      panels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.panel !== tab);
      });

      if (input) input.value = tab;
    }

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
    });

    const fromHash = (window.location.hash || '').replace('#', '');
    const initial = ['pagos', 'google', 'redes', 'otras'].includes(fromHash)
      ? fromHash
      : (input && input.value ? input.value : 'pagos');
    setActiveTab(initial);
  })();
</script>
@endsection
