@extends('layouts.app')
@section('title','Nuevo Producto')
@section('content')
@php
  $allCurrencies = ['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','BRL','CHF'];
  $otherCurrencies = array_values(array_filter($allCurrencies, fn($c) => $c !== $base));
@endphp

<form method="POST" action="{{ route('productos.store') }}" class="max-w-2xl mx-auto space-y-5">
  @csrf
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-900">Nuevo Producto / Servicio</h1>
    <div class="flex gap-2">
      <a href="{{ route('productos.index') }}" class="px-4 py-2 rounded-full border text-sm text-slate-600 hover:bg-slate-50">Cancelar</a>
      <button type="submit" class="px-4 py-2 rounded-full bg-[#ecfe88] text-slate-900 text-sm font-bold shadow-sm hover:bg-[#d9ef60]">Guardar</button>
    </div>
  </div>

  {{-- Bloque principal --}}
  <div class="bg-white rounded-2xl shadow-soft border p-6 space-y-5">

    {{-- Nombre --}}
    <div>
      <label class="block text-sm font-medium text-slate-600 mb-1">Nombre del Producto / Servicio</label>
      <input type="text" name="nombre" required placeholder="Ej. Diseño Web Corporativo"
        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
    </div>

    {{-- Descripción --}}
    <div>
      <label class="block text-sm font-medium text-slate-600 mb-1">Descripción</label>
      <textarea name="descripcion" rows="6" placeholder="Detalles del producto o servicio..."
        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow resize-none"></textarea>
    </div>

    {{-- Precio base + Tipo --}}
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
          Precio Unitario
          <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 uppercase">{{ $base }}</span>
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
          <input type="number" name="precio" required step="0.01" min="0" placeholder="0.00"
            class="w-full pl-8 rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Tipo</label>
        <select name="tipo"
          class="w-full rounded-xl border border-slate-200 px-4 py-2.5 bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
          <option value="Producto">Producto</option>
          <option value="Servicio" selected>Servicio</option>
        </select>
      </div>
    </div>

    {{-- SKU + Stock --}}
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">SKU / Código <span class="text-slate-400 font-normal">(Opcional)</span></label>
        <input type="text" name="sku" placeholder="Ej. WEB-001"
          class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Stock Inicial <span class="text-slate-400 font-normal">(Opcional)</span></label>
        <input type="number" name="stock" placeholder="Solo para productos físicos"
          class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
      </div>
    </div>

    {{-- Recordatorio de vencimiento de servicio --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <label class="inline-flex items-center gap-2 cursor-pointer select-none">
        <input type="checkbox" name="service_expiry_reminder_enabled" id="serviceReminderEnabled" value="1"
          class="rounded border-slate-300 text-slate-900 focus:ring-[#ecfe88]">
        <span class="text-sm font-semibold text-slate-800">Enviar recordatorio de vencimiento de servicio</span>
      </label>
      <p class="text-xs text-slate-500 mt-1">Solo aplica cuando este servicio esté incluido en una factura recurrente.</p>
      <div id="serviceReminderDaysWrap" class="mt-3">
        <label class="block text-sm font-medium text-slate-600 mb-1">Días antes del envío de factura recurrente</label>
        <input type="number" name="service_expiry_reminder_days_before" id="serviceReminderDays" min="1" max="90" value="7"
          class="w-full md:w-56 rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
      </div>
    </div>
  </div>

  {{-- Precios en otras monedas --}}
  <div class="bg-white rounded-2xl shadow-soft border p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-800">Precios en otras monedas</h3>
        <p class="text-xs text-slate-400 mt-0.5">Al facturar en esa moneda se usará este precio exacto en vez de convertir.</p>
      </div>
      <button type="button" id="btnAddCurrency"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Agregar moneda
      </button>
    </div>
    <div id="preciosExtras" class="space-y-3">
      {{-- rows added by JS --}}
    </div>
    <p id="noCurrenciesMsg" class="text-xs text-slate-400 text-center py-2">No hay precios adicionales</p>
  </div>
</form>

<script>
(function(){
  const currencies = @json($otherCurrencies);
  const container = document.getElementById('preciosExtras');
  const noMsg = document.getElementById('noCurrenciesMsg');
  let rowCount = 0;

  function updateNoMsg() {
    noMsg.style.display = container.children.length === 0 ? '' : 'none';
  }

  document.getElementById('btnAddCurrency').addEventListener('click', () => {
    const usedCurrencies = [...container.querySelectorAll('select[name^="precios"]')].map(s => s.value);
    const available = currencies.filter(c => !usedCurrencies.includes(c));
    if (!available.length) return;

    const idx = rowCount++;
    const row = document.createElement('div');
    row.className = 'flex items-center gap-3';
    row.innerHTML = `
      <select name="precios[${available[0]}]" data-idx="${idx}"
        class="w-28 rounded-xl border border-slate-200 px-3 py-2 bg-white text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 currency-select">
        ${currencies.map(c => `<option value="${c}" ${c === available[0] ? 'selected' : ''}>${c}</option>`).join('')}
      </select>
      <div class="relative flex-1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
        <input type="number" name="precios[${available[0]}]" step="0.01" min="0" placeholder="0.00"
          class="w-full pl-8 rounded-xl border border-slate-200 px-4 py-2 text-slate-900 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow currency-price-input">
      </div>
      <button type="button" class="p-1.5 text-slate-400 hover:text-rose-500 remove-currency-row">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>`;

    // Sync select name and input name when currency changes
    row.querySelector('.currency-select').addEventListener('change', function() {
      const cur = this.value;
      this.name = `precios[${cur}]`;
      row.querySelector('.currency-price-input').name = `precios[${cur}]`;
    });

    row.querySelector('.remove-currency-row').addEventListener('click', () => {
      row.remove();
      updateNoMsg();
    });

    container.appendChild(row);
    updateNoMsg();
  });

  updateNoMsg();

  const reminderEnabled = document.getElementById('serviceReminderEnabled');
  const reminderDaysWrap = document.getElementById('serviceReminderDaysWrap');
  const reminderDays = document.getElementById('serviceReminderDays');
  function syncReminderUI() {
    const on = reminderEnabled?.checked;
    reminderDaysWrap?.classList.toggle('opacity-50', !on);
    reminderDaysWrap?.classList.toggle('pointer-events-none', !on);
    if (reminderDays) reminderDays.disabled = !on;
  }
  reminderEnabled?.addEventListener('change', syncReminderUI);
  syncReminderUI();
})();
</script>
@endsection
