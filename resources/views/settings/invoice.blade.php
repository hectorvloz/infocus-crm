@extends('layouts.settings')

@section('title', 'Ajustes de Facturación')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Facturación</h2>
    <p class="text-slate-500">Personaliza los prefijos, impuestos y términos de tus facturas.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form action="{{ route('settings.invoice.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Prefix -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Prefijo de Factura</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold">#</span>
                    <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'INV-' }}" class="block w-full pl-8 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm uppercase" placeholder="INV-">
                </div>
                <p class="mt-1 text-xs text-slate-400">Ejemplo: INV-001</p>
            </div>

            <!-- Start Number -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Número de Inicio</label>
                <input type="number" name="invoice_start_number" value="{{ $settings['invoice_start_number'] ?? '1' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
            </div>
        </div>

        <!-- Logo en Factura -->
        @php
            $invoiceLogo = $settings['invoice_logo'] ?? null;
            $logoLarge   = $settings['logo_large'] ?? $settings['logo'] ?? null;
            $currentInvoiceLogo = $invoiceLogo ?: $logoLarge;
            $currentInvoiceLogoUrl = app_public_asset_url($currentInvoiceLogo);
            $logoLargeUrl = app_public_asset_url($logoLarge);
        @endphp
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Logo en la Factura</label>
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-4">
                <!-- Vista previa -->
                <div class="w-full h-20 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shadow-sm" id="invoice-logo-preview-wrap">
                    @if($currentInvoiceLogoUrl)
                        <img id="invoice-logo-preview" src="{{ $currentInvoiceLogoUrl }}" alt="Logo factura" class="w-full h-full object-contain p-3">
                    @else
                        <span id="invoice-logo-preview-placeholder" class="text-xs text-slate-400">Sin logo</span>
                        <img id="invoice-logo-preview" src="" alt="Logo factura" class="w-full h-full object-contain p-3 hidden">
                    @endif
                </div>
                <p class="text-xs text-slate-400 text-center -mt-2">Vista previa</p>
                <!-- Opciones -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-3 rounded-xl border bg-white cursor-pointer hover:border-slate-400 transition-colors {{ (!$invoiceLogo || $invoiceLogo === $logoLarge) ? 'border-slate-900' : 'border-slate-200' }}">
                        <input type="radio" name="invoice_logo_source" value="company" class="mt-0.5 text-slate-900 focus:ring-slate-900" {{ (!$invoiceLogo || $invoiceLogo === $logoLarge) ? 'checked' : '' }}>
                        <div class="flex-1 min-w-0">
                            <span class="block text-sm font-medium text-slate-800">Logo de la empresa</span>
                            <span class="block text-xs text-slate-400 mt-0.5">El configurado en Ajustes generales</span>
                            @if($logoLargeUrl)
                                <img src="{{ $logoLargeUrl }}" class="h-5 mt-2 object-contain object-left max-w-full" alt="">
                            @endif
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 rounded-xl border bg-white cursor-pointer hover:border-slate-400 transition-colors {{ ($invoiceLogo && $invoiceLogo !== $logoLarge) ? 'border-slate-900' : 'border-slate-200' }}">
                        <input type="radio" name="invoice_logo_source" value="custom" class="mt-0.5 text-slate-900 focus:ring-slate-900" {{ ($invoiceLogo && $invoiceLogo !== $logoLarge) ? 'checked' : '' }}>
                        <div class="flex-1">
                            <span class="block text-sm font-medium text-slate-800">Logo personalizado</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Solo para facturas</span>
                        </div>
                    </label>
                </div>
                <div id="invoice-logo-upload-area" class="{{ ($invoiceLogo && $invoiceLogo !== $logoLarge) ? '' : 'hidden' }}">
                    <label class="cursor-pointer inline-flex items-center gap-2 bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Seleccionar imagen
                        <input type="file" name="invoice_logo" id="input-invoice-logo" class="hidden" accept="image/*">
                    </label>
                        <input type="hidden" name="invoice_logo_current" value="{{ $invoiceLogo ?? '' }}">
                    </div>

                <!-- Tamaño del logotipo -->
                <div class="border-t border-slate-200 pt-4">
                    <div class="flex items-center justify-between mb-1 gap-4">
                        <label class="block text-sm font-medium text-slate-700">Tamaño en el PDF</label>
                        <span id="invoiceLogoSizeValue" class="text-sm font-semibold text-slate-600">{{ (int) ($settings['invoice_logo_size'] ?? 52) }} mm</span>
                    </div>
                    <input
                        type="range"
                        name="invoice_logo_size"
                        id="invoiceLogoSize"
                        min="24"
                        max="90"
                        step="1"
                        value="{{ (int) ($settings['invoice_logo_size'] ?? 52) }}"
                        class="block w-full accent-[#d9ea76]"
                    >
                    <div class="mt-1.5 flex justify-between text-xs text-slate-400">
                        <span>Pequeño</span>
                        <span>Mediano</span>
                        <span>Grande</span>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tax Name -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Impuesto</label>
                <input type="text" name="tax_name" value="{{ $settings['tax_name'] ?? 'IVA' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
            </div>

            <!-- Tax Rate -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tasa de Impuesto (%)</label>
                <div class="relative">
                    <input type="number" step="0.01" name="tax_rate" value="{{ $settings['tax_rate'] ?? '16' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400">%</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Color cabecera factura</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="invoice_color_header" value="{{ $settings['invoice_color_header'] ?? '#101729' }}" class="h-11 w-16 p-1 rounded-xl border border-slate-300 bg-white">
                    <input type="text" name="invoice_color_header_text" value="{{ $settings['invoice_color_header'] ?? '#101729' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl sm:text-sm bg-slate-50 text-slate-500" readonly>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Color bloque notas</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="invoice_color_footer" value="{{ $settings['invoice_color_footer'] ?? '#f0fe97' }}" class="h-11 w-16 p-1 rounded-xl border border-slate-300 bg-white">
                    <input type="text" name="invoice_color_footer_text" value="{{ $settings['invoice_color_footer'] ?? '#f0fe97' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl sm:text-sm bg-slate-50 text-slate-500" readonly>
                </div>
            </div>
        </div>

        <!-- Terms -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Términos y Condiciones (Por defecto)</label>
            <textarea name="invoice_terms" rows="3" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="El pago debe realizarse dentro de los 30 días...">{{ $settings['invoice_terms'] ?? '' }}</textarea>
        </div>

        <!-- Footer -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Pie de Página de Factura</label>
            <textarea name="invoice_footer" rows="2" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Gracias por su preferencia.">{{ $settings['invoice_footer'] ?? '' }}</textarea>
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Ajustes
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const range = document.getElementById('invoiceLogoSize');
    const output = document.getElementById('invoiceLogoSizeValue');
    if (range && output) {
        const sync = () => { output.textContent = `${range.value} mm`; };
        range.addEventListener('input', sync);
        sync();
    }

    // Logo factura: toggle área upload y preview
    const companyLogo = @json($logoLargeUrl ?? '');
    const customLogo = @json(($invoiceLogo && $invoiceLogo !== $logoLarge) ? app_public_asset_url($invoiceLogo) : '');
    const radios = document.querySelectorAll('input[name="invoice_logo_source"]');
    const uploadArea = document.getElementById('invoice-logo-upload-area');
    const previewImg = document.getElementById('invoice-logo-preview');
    const previewPh  = document.getElementById('invoice-logo-preview-placeholder');
    const fileInput  = document.getElementById('input-invoice-logo');

    function syncLogoSource() {
        const val = document.querySelector('input[name="invoice_logo_source"]:checked')?.value;
        if (uploadArea) uploadArea.classList.toggle('hidden', val !== 'custom');
        if (!previewImg) return;
        if (val === 'company') {
            if (companyLogo) {
                previewImg.src = companyLogo;
                previewImg.classList.remove('hidden');
                if (previewPh) previewPh.classList.add('hidden');
            } else {
                previewImg.src = '';
                previewImg.classList.add('hidden');
                if (previewPh) previewPh.classList.remove('hidden');
            }
        } else if (val === 'custom') {
            if (customLogo) {
                previewImg.src = customLogo;
                previewImg.classList.remove('hidden');
                if (previewPh) previewPh.classList.add('hidden');
            } else {
                previewImg.src = '';
                previewImg.classList.add('hidden');
                if (previewPh) previewPh.classList.remove('hidden');
            }
        }
    }
    radios.forEach(r => r.addEventListener('change', syncLogoSource));
    syncLogoSource();

    if (fileInput && previewImg) {
        fileInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                if (previewPh) previewPh.classList.add('hidden');
            };
            reader.readAsDataURL(this.files[0]);
        });
    }

    const colorHeader = document.querySelector('input[name="invoice_color_header"]');
    const colorFooter = document.querySelector('input[name="invoice_color_footer"]');
    const colorHeaderText = document.querySelector('input[name="invoice_color_header_text"]');
    const colorFooterText = document.querySelector('input[name="invoice_color_footer_text"]');
    if (colorHeader && colorHeaderText) {
        const syncHeader = () => colorHeaderText.value = colorHeader.value;
        colorHeader.addEventListener('input', syncHeader);
        syncHeader();
    }
    if (colorFooter && colorFooterText) {
        const syncFooter = () => colorFooterText.value = colorFooter.value;
        colorFooter.addEventListener('input', syncFooter);
        syncFooter();
    }
});
</script>
@endsection
