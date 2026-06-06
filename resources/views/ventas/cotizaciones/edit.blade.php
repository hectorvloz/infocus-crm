@extends('layouts.app')
@section('title','Editar Cotización')
@section('content')
@php
  $settings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
  $base = $settings['base_currency'] ?? 'USD';
@endphp
<form method="POST" action="{{ route('cotizaciones.update', $cotizacion['id']) }}" class="max-w-4xl mx-auto space-y-6">
  @csrf
  @method('PUT')
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-900">Editar Cotización</h1>
    <div class="flex items-center gap-3">
      <a href="{{ route('cotizaciones.print', $cotizacion['id']) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-medium shadow-sm hover:bg-slate-800 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        PDF
      </a>
      <a href="{{ route('cotizaciones.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
        Cancelar
      </a>
      <button type="submit" name="estado" value="Borrador" class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-slate-200 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
        Guardar Borrador
      </button>
      <button type="submit" name="estado" value="Publicada" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-[#ecfe88] text-slate-900 text-sm font-bold shadow-sm hover:bg-[#d9ef60] transition-colors">
        Publicar & Crear Lead
      </button>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-soft border p-6 space-y-6">
    <!-- Header Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Cliente (Prospecto)</label>
        <input type="text" name="cliente" value="{{ $cotizacion['cliente'] }}" required placeholder="Nombre del cliente o empresa" class="w-full rounded-xl border-slate-200 px-4 py-2 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Número</label>
        <input type="text" name="numero" value="{{ $cotizacion['numero'] }}" readonly class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2 text-slate-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Moneda</label>
        <select name="moneda" id="monedaField" class="form-select">
          @foreach(['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY'] as $m)
            <option @selected(($cotizacion['moneda'] ?? $base)===$m)>{{ $m }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Emisión</label>
        <div class="relative">
          <input name="fecha" id="fecha" type="text" value="{{ $cotizacion['fecha'] }}" required class="w-full rounded-xl border-slate-200 px-4 py-2 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow bg-white cursor-pointer" placeholder="Selecciona fechas">
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Vencimiento</label>
        <div class="relative">
          <input name="vencimiento" id="vencimiento" type="text" value="{{ $cotizacion['vencimiento'] ?? '' }}" class="w-full rounded-xl border-slate-200 px-4 py-2 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow bg-white cursor-pointer" placeholder="Calculando...">
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="border-t border-slate-100 pt-6">
      <h3 class="text-lg font-bold text-slate-900 mb-4">Items</h3>
      
      <!-- Headers -->
      <div class="grid grid-cols-12 gap-4 mb-2 text-sm font-medium text-slate-500 px-1">
        <div class="col-span-5">Descripción</div>
        <div class="col-span-2 text-right">Cantidad</div>
        <div class="col-span-2 text-right">Precio</div>
        <div class="col-span-2 text-right">Importe</div>
        <div class="col-span-1"></div>
      </div>

      <div id="itemsContainer" class="space-y-2">
        @foreach($cotizacion['items'] as $index => $item)
        <div class="item-row grid grid-cols-12 gap-4 items-center">
          <div class="col-span-5">
            <input list="productsList" type="text" name="items[{{ $index }}][descripcion]" value="{{ $item['descripcion'] }}" placeholder="Descripción del servicio o producto" required class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-lime-300 desc-input">
          </div>
          <div class="col-span-2">
            <input type="number" name="items[{{ $index }}][cantidad]" value="{{ $item['cantidad'] }}" placeholder="1" min="1" required class="qty-input w-full rounded-lg border-slate-200 px-3 py-2 text-sm text-right focus:ring-2 focus:ring-lime-300" oninput="calcRow(this)">
          </div>
          <div class="col-span-2">
            <input type="number" name="items[{{ $index }}][precio]" value="{{ $item['precio'] }}" placeholder="0.00" step="0.01" required class="price-input w-full rounded-lg border-slate-200 px-3 py-2 text-sm text-right focus:ring-2 focus:ring-lime-300" oninput="calcRow(this)">
          </div>
          <div class="col-span-2 text-right">
            <div class="total-display text-sm font-medium text-slate-700 py-2">${{ number_format($item['cantidad'] * $item['precio'], 2) }}</div>
          </div>
          <div class="col-span-1 text-center">
            <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors" onclick="removeRow(this)">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
        @endforeach
        <datalist id="productsList"></datalist>
      </div>
      
      <div class="mt-4 flex flex-wrap items-center gap-4">
        <button type="button" onclick="addItem(false)" class="text-sm font-bold text-lime-600 hover:text-lime-700 flex items-center gap-1 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Agregar item
        </button>
        <button type="button" onclick="addItem(true)" class="text-sm font-bold text-lime-600 hover:text-lime-700 flex items-center gap-1 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Agregar Prod./Serv.
        </button>
      </div>
    </div>

    <!-- Totals -->
    <div class="border-t border-slate-100 pt-6 flex justify-end">
      <div class="w-64 space-y-2">
        <div class="flex justify-between text-sm text-slate-600">
          <span>Subtotal</span>
          <span id="subtotalDisplay">${{ number_format($cotizacion['subtotal'] ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between text-sm text-slate-600">
          <span>Impuestos (16%)</span>
          <span id="taxDisplay">${{ number_format($cotizacion['impuestos'] ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between text-lg font-bold text-slate-900 pt-2 border-t border-slate-100">
          <span>Total</span>
          <span id="totalDisplay" class="bg-[#ecfe88] px-2 rounded">${{ number_format($cotizacion['total'] ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
  let itemCount = {{ count($cotizacion['items'] ?? []) }};
  const monedaField = document.getElementById('monedaField');
  const decimalPlaces = parseInt(document.body.dataset.decimals || '2', 10);

  const currencyMap = {
    'USD': '$', 'EUR': '€', 'MXN': '$', 'COP': '$', 'ARS': '$', 'CLP': '$',
    'PEN': 'S/', 'GBP': '£', 'CAD': '$', 'JPY': '¥', 'AUD': '$', 'CNY': '¥',
    'CHF': 'Fr', 'HKD': '$', 'NZD': '$', 'SEK': 'kr', 'KRW': '₩', 'SGD': '$',
    'INR': '₹', 'BRL': 'R$', 'RUB': '₽', 'ZAR': 'R', 'TRY': '₺'
  };

  const numberFormatter = new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: decimalPlaces,
    maximumFractionDigits: decimalPlaces
  });

  function getCurrencySymbol() {
    const val = monedaField?.value || 'USD';
    return currencyMap[val] || '$';
  }

  function formatMoney(value) {
    return getCurrencySymbol() + numberFormatter.format(value);
  }

  function addItem(useProductSearch = false) {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row grid grid-cols-12 gap-4 items-center';
    const descriptionInput = useProductSearch
      ? `<div class="product-selector relative"><input name="items[${itemCount}][descripcion]" class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-lime-300 product-desc-input" placeholder="Buscar producto o servicio" autocomplete="off" required><div class="product-options hidden absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-56 overflow-auto"></div></div>`
      : `<input list="productsList" type="text" name="items[${itemCount}][descripcion]" placeholder="Descripción del servicio o producto" required class="w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-lime-300 desc-input">`;
    row.innerHTML = `
      <div class="col-span-5">
        ${descriptionInput}
      </div>
      <div class="col-span-2">
        <input type="number" name="items[${itemCount}][cantidad]" placeholder="1" value="1" min="1" required class="qty-input w-full rounded-lg border-slate-200 px-3 py-2 text-sm text-right focus:ring-2 focus:ring-lime-300" oninput="calcRow(this)">
      </div>
      <div class="col-span-2">
        <input type="number" name="items[${itemCount}][precio]" placeholder="0.00" step="0.01" required class="price-input w-full rounded-lg border-slate-200 px-3 py-2 text-sm text-right focus:ring-2 focus:ring-lime-300" oninput="calcRow(this)">
      </div>
      <div class="col-span-2 text-right">
        <div class="total-display text-sm font-medium text-slate-700 py-2">${formatMoney(0)}</div>
      </div>
      <div class="col-span-1 text-center">
        <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors" onclick="removeRow(this)">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    `;
    container.appendChild(row);
    itemCount++;
    if (useProductSearch) attachProductSelector(row);
  }

  function removeRow(btn) {
    if (document.querySelectorAll('.item-row').length > 1) {
      btn.closest('.item-row').remove();
      calcTotals();
    }
  }

  function calcRow(input) {
    const row = input.closest('.item-row');
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const total = qty * price;
    row.querySelector('.total-display').textContent = formatMoney(total);
    calcTotals();
  }

  function calcTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
      const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
      const price = parseFloat(row.querySelector('.price-input').value) || 0;
      const rowTotal = qty * price;
      subtotal += rowTotal;
      const disp = row.querySelector('.total-display');
      if (disp) disp.textContent = formatMoney(rowTotal);
    });

    const tax = subtotal * 0.16;
    const total = subtotal + tax;

    document.getElementById('subtotalDisplay').textContent = formatMoney(subtotal);
    document.getElementById('taxDisplay').textContent = formatMoney(tax);
    document.getElementById('totalDisplay').textContent = formatMoney(total);
  }

  // Productos Integration
  let availableProducts = [];
  const productsList = document.getElementById('productsList');
  
  function renderProductMenu(menu, inputValue = '') {
    menu.innerHTML = '';
    const query = inputValue.trim().toLowerCase();

    if (!availableProducts.length) {
      const empty = document.createElement('div');
      empty.className = 'px-3 py-2 text-sm text-slate-500 italic';
      empty.textContent = 'No hay productos agregados';
      menu.appendChild(empty);
      return;
    }

    const filtered = availableProducts.filter((p) => String(p.nombre || '').toLowerCase().includes(query));
    if (!filtered.length) {
      const empty = document.createElement('div');
      empty.className = 'px-3 py-2 text-sm text-slate-500 italic';
      empty.textContent = 'Sin coincidencias';
      menu.appendChild(empty);
      return;
    }

    filtered.slice(0, 30).forEach((p) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-b-0';
      btn.textContent = p.nombre;
      btn.addEventListener('click', () => {
        const input = menu.parentElement?.querySelector('.product-desc-input');
        if (!input) return;
        input.value = p.nombre;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        menu.classList.add('hidden');
      });
      menu.appendChild(btn);
    });
  }

  function attachProductSelector(row) {
    const container = row.querySelector('.product-selector');
    const input = row.querySelector('.product-desc-input');
    const menu = row.querySelector('.product-options');
    if (!container || !input || !menu) return;

    const openMenu = () => {
      renderProductMenu(menu, input.value);
      menu.classList.remove('hidden');
    };

    input.addEventListener('focus', openMenu);
    input.addEventListener('click', openMenu);
    input.addEventListener('input', openMenu);
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('.product-selector')) return;
    document.querySelectorAll('.product-options').forEach((menu) => menu.classList.add('hidden'));
  });

  async function loadProducts() {
    try {
      const res = await fetch('/api/productos');
      availableProducts = await res.json();
      productsList.innerHTML = availableProducts.map(p => `<option value="${p.nombre}">`).join('');
    } catch(e) { console.error('Error loading products', e); }
  }
  loadProducts();

  document.getElementById('itemsContainer').addEventListener('input', (e) => {
    if (e.target.classList.contains('desc-input') || e.target.classList.contains('product-desc-input')) {
      const val = e.target.value;
      const product = availableProducts.find(p => p.nombre === val);
      if (product) {
        const row = e.target.closest('.item-row');
        const priceInput = row.querySelector('.price-input');
        if (priceInput) {
          priceInput.value = product.precio;
          calcRow(priceInput);
        }
      }
    }
  });

  monedaField?.addEventListener('change', calcTotals);
  calcTotals();

  // Initialize Flatpickr
  document.addEventListener("DOMContentLoaded", function() {
      flatpickr("#fecha", {
          "plugins": [new rangePlugin({ input: "#vencimiento" })],
          mode: "range",
          altInput: true,
          altFormat: "d M, Y", 
          dateFormat: "Y-m-d",
          defaultDate: [
              "{{ $cotizacion['fecha'] ?? date('Y-m-d') }}", 
              "{{ $cotizacion['vencimiento'] ?? date('Y-m-d', strtotime('+15 days')) }}"
          ],
          minDate: "today",
          locale: "es", // Spanish
          onOpen: function(selectedDates, dateStr, instance) {
              instance.calendarContainer.classList.add("airline-calendar");
          }
      });
  });
</script>
@endsection
