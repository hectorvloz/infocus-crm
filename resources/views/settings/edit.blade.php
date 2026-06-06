@extends('layouts.settings')

@section('title', 'Ajustes de Empresa')

@section('content')
<style>
    .social-select-wrap .app-select-trigger {
        padding-left: 2.35rem;
    }
</style>
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Perfil de la Empresa</h2>
    <p class="text-slate-500">Información general que aparecerá en tus documentos.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @php
            $logoLargeUrl = app_public_asset_url($settings['logo_large'] ?? ($settings['logo'] ?? ''));
            $currentFavicon = $settings['favicon'] ?? ($settings['logo_small'] ?? null);
            $faviconUrl = app_public_asset_url($currentFavicon);
        @endphp

        <!-- Logos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Logo Grande -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col gap-3">
                <label class="block text-sm font-semibold text-slate-700">Logotipo Grande</label>
                <div id="preview-logo-large-wrap" class="w-full h-20 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden">
                    @if($logoLargeUrl)
                        <img id="preview-logo-large" src="{{ $logoLargeUrl }}" alt="Logo Grande" class="w-full h-full object-contain p-3">
                    @else
                        <span id="preview-logo-large-placeholder" class="text-xs text-slate-400">Sin logo</span>
                        <img id="preview-logo-large" src="" alt="Logo Grande" class="w-full h-full object-contain p-3 hidden">
                    @endif
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-slate-500 leading-tight">Facturas y menú expandido.<br>Rec: 185×45 px</p>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Cambiar
                        <input type="file" name="logo_large" id="input-logo-large" class="hidden" accept="image/*">
                    </label>
                </div>
            </div>

            <!-- Favicon -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col gap-3">
                <label class="block text-sm font-semibold text-slate-700">Favicon</label>
                <div class="flex items-center gap-4">
                    <div id="preview-logo-small-wrap" class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                        @if($faviconUrl)
                            <img id="preview-logo-small" src="{{ $faviconUrl }}" alt="Favicon" class="w-full h-full object-contain p-2">
                        @else
                            <span id="preview-logo-small-placeholder" class="text-xs text-slate-400 text-center leading-tight">Sin<br>favicon</span>
                            <img id="preview-logo-small" src="" alt="Favicon" class="w-full h-full object-contain p-2 hidden">
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <p class="text-xs text-slate-500 leading-tight">Icono del navegador y menú contraído.<br>Rec: 32×32 px o 64×64 px</p>
                        <label class="cursor-pointer inline-flex items-center gap-1.5 bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 transition-colors shadow-sm self-start">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Cambiar
                            <input type="file" name="logo_small" id="input-logo-small" class="hidden" accept="image/*">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
            <label class="inline-flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="sidebar_use_company_logo"
                    value="1"
                    class="h-5 w-5 rounded border-slate-300 text-slate-900 focus:ring-[#ecfe88]"
                    {{ !empty($settings['sidebar_use_company_logo']) ? 'checked' : '' }}
                >
                <span class="text-sm text-slate-700">
                    Usar mi logo en el sidebar
                </span>
            </label>
            <p class="text-xs text-slate-500 mt-2">
                Si está desactivado, el sidebar mostrará el logo oficial de Infocus CRM.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Empresa</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email de Contacto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <input type="email" name="email_from" value="{{ $settings['email_from'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Dirección -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Dirección (Calle y Número)</label>
            <div class="relative">
                <div class="absolute top-3 left-3 pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <textarea name="company_address" rows="2" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">{{ $settings['company_address'] ?? '' }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Ciudad -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ciudad</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <input type="text" name="company_city" value="{{ $settings['company_city'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estado / Provincia</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <input type="text" name="company_state" value="{{ $settings['company_state'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- CP -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Código Postal</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <input type="text" name="company_zip" value="{{ $settings['company_zip'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>

            <!-- Pais -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">País</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <input type="text" name="company_country" value="{{ $settings['company_country'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sitio Web</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.6 9h16.8M3.6 15h16.8M12 3a14.5 14.5 0 010 18M12 3a14.5 14.5 0 000 18"/></svg>
                    </div>
                    <input type="text" name="company_website" value="{{ $settings['company_website'] ?? ($settings['invoice_website'] ?? '') }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="ejemplo.com">
                </div>
                <p class="text-xs text-slate-400 mt-1">Se usa en el footer de tus correos.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Moneda -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Moneda Base</label>
                @php
                $currencyNames = [
                  'USD'=>'Dólar estadounidense','EUR'=>'Euro','MXN'=>'Peso mexicano','COP'=>'Peso colombiano',
                  'ARS'=>'Peso argentino','CLP'=>'Peso chileno','PEN'=>'Sol peruano','GBP'=>'Libra esterlina',
                  'CAD'=>'Dólar canadiense','JPY'=>'Yen japonés','AUD'=>'Dólar australiano','CNY'=>'Yuan chino',
                  'CHF'=>'Franco suizo','HKD'=>'Dólar de Hong Kong','NZD'=>'Dólar neozelandés','SEK'=>'Corona sueca',
                  'KRW'=>'Won surcoreano','SGD'=>'Dólar de Singapur','INR'=>'Rupia india','BRL'=>'Real brasileño',
                  'RUB'=>'Rublo ruso','ZAR'=>'Rand sudafricano','TRY'=>'Lira turca','DOP'=>'Peso dominicano',
                  'GTQ'=>'Quetzal guatemalteco','HNL'=>'Lempira hondureño','NIO'=>'Córdoba nicaragüense',
                  'CRC'=>'Colón costarricense','PAB'=>'Balboa panameño','BOB'=>'Boliviano','PYG'=>'Guaraní paraguayo',
                  'UYU'=>'Peso uruguayo','VES'=>'Bolívar venezolano',
                ];
                @endphp
                <select name="base_currency">
                    @foreach($currencyNames as $cur => $name)
                        <option value="{{ $cur }}" {{ ($settings['base_currency'] ?? '') == $cur ? 'selected' : '' }}>{{ $cur }} — {{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Zona Horaria -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Zona horaria</label>
                @php
                    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
                    $selectedTimezone = $settings['company_timezone'] ?? 'America/Bogota';
                @endphp
                <select name="company_timezone">
                    @foreach($timezones as $tz)
                        <option value="{{ $tz }}" {{ $selectedTimezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Se usa para fechas y horas del sistema.</p>
            </div>

            <!-- Whatsapp -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono / WhatsApp</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <input type="text" name="whatsapp_number" value="{{ $settings['whatsapp_number'] ?? '' }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="+52 ...">
                </div>
            </div>
        </div>

        @php
            $networkOptions = [
                'instagram' => 'Instagram',
                'facebook' => 'Facebook',
                'x' => 'X / Twitter',
                'linkedin' => 'LinkedIn',
                'tiktok' => 'TikTok',
                'youtube' => 'YouTube',
                'reddit' => 'Reddit',
                'whatsapp' => 'WhatsApp',
                'telegram' => 'Telegram',
            ];
            $hasStructuredSocialLinks = array_key_exists('social_links', $settings);
            $socialRows = $settings['social_links'] ?? [];
            if (!$hasStructuredSocialLinks && empty($socialRows)) {
                if (!empty($settings['social_instagram'])) $socialRows[] = ['network' => 'instagram', 'value' => $settings['social_instagram']];
                if (!empty($settings['social_facebook'])) $socialRows[] = ['network' => 'facebook', 'value' => $settings['social_facebook']];
                if (!empty($settings['social_x'])) $socialRows[] = ['network' => 'x', 'value' => $settings['social_x']];
                if (!empty($settings['social_linkedin'])) $socialRows[] = ['network' => 'linkedin', 'value' => $settings['social_linkedin']];
                if (!empty($settings['social_tiktok'])) $socialRows[] = ['network' => 'tiktok', 'value' => $settings['social_tiktok']];
            }
            if (empty($socialRows)) $socialRows[] = ['network' => 'instagram', 'value' => ''];
        @endphp

        <div class="pt-2 border-t border-slate-100">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Redes Sociales</h3>
                    <p class="text-sm text-slate-500">Selecciona la red y pega usuario o URL. Esto se muestra en el footer del correo.</p>
                </div>
                <button type="button" id="add-social-row" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-800 bg-slate-100 hover:bg-slate-200">+ Agregar red</button>
            </div>

            <div id="social-rows" class="space-y-2">
                @foreach($socialRows as $row)
                    <div class="social-row grid grid-cols-1 md:grid-cols-[220px_1fr_auto] gap-2 items-center">
                        <div class="relative social-select-wrap">
                            <span class="social-icon-preview absolute left-3 top-1/2 -translate-y-1/2 text-base leading-none">🌐</span>
                            <select name="social_networks[]" class="social-network-select w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm">
                                @foreach($networkOptions as $k => $label)
                                    <option value="{{ $k }}" {{ (($row['network'] ?? '') === $k) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="social_values[]" value="{{ $row['value'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm" placeholder="usuario o URL completa">
                        <button type="button" class="remove-social-row px-3 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">Quitar</button>
                    </div>
                @endforeach
            </div>
        </div>

        @php
            $showDecimals = $settings['show_decimals'] ?? true;
            $decimalPlaces = $settings['decimal_places'] ?? 2;
        @endphp

        <div class="pt-2 border-t border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Preferencias numéricas</h3>
            <p class="text-sm text-slate-500 mb-4">Controla cómo se muestran los montos en el sistema.</p>

            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4 max-w-sm">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <div>
                        <span class="block text-sm font-semibold text-slate-800">Mostrar decimales en montos</span>
                        <span class="block text-xs text-slate-500 mt-0.5">Aplica a facturas, cotizaciones y reportes</span>
                    </div>
                    <div class="relative inline-block shrink-0">
                        <input type="checkbox" name="show_decimals" id="show_decimals" value="1" class="sr-only peer" {{ $showDecimals ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-300 peer-checked:bg-[#101729] rounded-full transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                    </div>
                </label>

                <div id="decimal-places-row" class="flex items-center gap-3">
                    <label class="text-sm text-slate-600 shrink-0">Cantidad de decimales</label>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" id="dec-minus" class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-lg leading-none">−</button>
                        <input type="number" min="0" max="4" name="decimal_places" id="decimal_places" value="{{ $decimalPlaces }}" class="w-12 text-center py-1.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-800 focus:ring-[#ecfe88] focus:border-[#ecfe88]">
                        <button type="button" id="dec-plus" class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-lg leading-none">+</button>
                    </div>
                </div>
                <p class="text-xs text-slate-400 -mt-1">Valor entre 0 y 4</p>
            </div>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
<script>
    (function(){
        const checkbox = document.getElementById('show_decimals');
        const input = document.getElementById('decimal_places');
        const row = document.getElementById('decimal-places-row');
        if (checkbox && input && row) {
            const sync = () => {
                input.disabled = !checkbox.checked;
                row.classList.toggle('opacity-40', !checkbox.checked);
                row.classList.toggle('pointer-events-none', !checkbox.checked);
            };
            checkbox.addEventListener('change', sync);
            sync();
        }
        const minus = document.getElementById('dec-minus');
        const plus = document.getElementById('dec-plus');
        if (minus && plus && input) {
            minus.addEventListener('click', () => { const v = parseInt(input.value) || 0; if (v > 0) input.value = v - 1; });
            plus.addEventListener('click', () => { const v = parseInt(input.value) || 0; if (v < 4) input.value = v + 1; });
        }

        // Preview de logos al seleccionar archivo
        function setupLogoPreview(inputId, imgId, placeholderId) {
            const inp = document.getElementById(inputId);
            const img = document.getElementById(imgId);
            if (!inp || !img) return;
            inp.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                    const ph = placeholderId ? document.getElementById(placeholderId) : null;
                    if (ph) ph.classList.add('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            });
        }
        setupLogoPreview('input-logo-large', 'preview-logo-large', 'preview-logo-large-placeholder');
        setupLogoPreview('input-logo-small', 'preview-logo-small', 'preview-logo-small-placeholder');

        // Repeater de redes sociales
        const socialRows = document.getElementById('social-rows');
        const addSocialBtn = document.getElementById('add-social-row');
        if (socialRows && addSocialBtn) {
            const iconMap = {
                instagram: '📸',
                facebook: '📘',
                x: '✖️',
                linkedin: '💼',
                tiktok: '🎵',
                youtube: '▶️',
                reddit: '👽',
                whatsapp: '💬',
                telegram: '✈️',
            };

            const networkOptionsHtml = `
                <option value="instagram">Instagram</option>
                <option value="facebook">Facebook</option>
                <option value="x">X / Twitter</option>
                <option value="linkedin">LinkedIn</option>
                <option value="tiktok">TikTok</option>
                <option value="youtube">YouTube</option>
                <option value="reddit">Reddit</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="telegram">Telegram</option>
            `;

            function paintRowIcon(row) {
                const select = row.querySelector('.social-network-select');
                const icon = row.querySelector('.social-icon-preview');
                if (!select || !icon) return;
                icon.textContent = iconMap[select.value] || '🌐';
            }

            function bindNetworkIcons() {
                socialRows.querySelectorAll('.social-row').forEach((row) => {
                    const select = row.querySelector('.social-network-select');
                    if (!select) return;
                    select.onchange = () => paintRowIcon(row);
                    paintRowIcon(row);
                });
            }

            function bindRemoveButtons() {
                socialRows.querySelectorAll('.remove-social-row').forEach(btn => {
                    btn.onclick = function () {
                        const rows = socialRows.querySelectorAll('.social-row');
                        if (rows.length <= 1) {
                            rows[0].querySelector('input[name="social_values[]"]').value = '';
                            return;
                        }
                        this.closest('.social-row')?.remove();
                    };
                });
            }

            addSocialBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'social-row grid grid-cols-1 md:grid-cols-[220px_1fr_auto] gap-2 items-center';
                row.innerHTML = `
                    <div class="relative social-select-wrap">
                        <span class="social-icon-preview absolute left-3 top-1/2 -translate-y-1/2 text-base leading-none">📸</span>
                        <select name="social_networks[]" class="social-network-select w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm">${networkOptionsHtml}</select>
                    </div>
                    <input type="text" name="social_values[]" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm" placeholder="usuario o URL completa">
                    <button type="button" class="remove-social-row px-3 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100">Quitar</button>
                `;
                socialRows.appendChild(row);
                bindRemoveButtons();
                bindNetworkIcons();
                if (window.enhanceNativeSelects) {
                    window.enhanceNativeSelects(row);
                }
            });

            bindRemoveButtons();
            bindNetworkIcons();
        }
    })();
</script>
@endsection
