@extends('layouts.app')
@section('title','Nueva factura')
@section('content')
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow border p-6">
    @php
      $s = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
      $clientes = collect((new \App\Repositories\FileStore('clientes.json'))->all())
        ->reject(fn($c) => mb_strtolower(trim((string) ($c['empresa'] ?? ''))) === 'sin cliente')
        ->sortBy(fn($c) => mb_strtolower((string) ($c['empresa'] ?? '')))
        ->values()
        ->all();
      $base = $s['base_currency'] ?? 'USD';
      $allowedCurrencies = ['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY'];
      $clientCurrencyById = collect($clientes)
        ->filter(fn($c) => !empty($c['id']))
        ->mapWithKeys(fn($c) => [(string) $c['id'] => strtoupper((string) ($c['moneda'] ?? $base))])
        ->all();
      $clientCurrencyByName = collect($clientes)
        ->filter(fn($c) => !empty($c['empresa']))
        ->mapWithKeys(fn($c) => [mb_strtolower(trim((string) $c['empresa'])) => strtoupper((string) ($c['moneda'] ?? $base))])
        ->all();
      $initialCurrency = old('moneda');
      if (!$initialCurrency && !empty($prefill_id ?? '')) {
        $initialCurrency = $clientCurrencyById[(string) $prefill_id] ?? null;
      }
      if (!$initialCurrency && !empty($prefill ?? '')) {
        $initialCurrency = $clientCurrencyByName[mb_strtolower(trim((string) $prefill))] ?? null;
      }
      $initialCurrency = strtoupper((string) ($initialCurrency ?: $base));
      if (!in_array($initialCurrency, $allowedCurrencies, true)) {
        $initialCurrency = strtoupper((string) $base);
      }
      $defaultTaxRate = (int) round((float) ($s['tax_rate'] ?? 16));
      $oldItems = old('items', [[
        'producto_id' => '',
        'detalle' => '',
        'descripcion' => '',
        'cantidad' => '',
        'precio' => '',
      ]]);
      $oldItems = collect(is_array($oldItems) ? $oldItems : [])->values();
      if ($oldItems->isEmpty()) {
        $oldItems = collect([[
          'producto_id' => '',
          'detalle' => '',
          'descripcion' => '',
          'cantidad' => '',
          'precio' => '',
        ]]);
      }
    @endphp
    <div class="flex items-center justify-between mb-4">
      <div class="text-xl font-bold">Nueva factura</div>
      <a href="{{ route('facturas.index') }}" class="px-3 py-2 rounded-full border text-sm">Cancelar</a>
    </div>
    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2">
      <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Número de factura</div>
      <div class="text-lg font-bold text-slate-900">{{ $nextNumber }}</div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="rounded-2xl border p-4 bg-neutral-50">
        <div class="font-semibold text-lg text-slate-900">{{ $s['company_name'] ?? 'Mi empresa' }}</div>
        @php
          $companyLocation = implode(', ', array_filter([
            $s['company_city'] ?? null,
            $s['company_state'] ?? null,
            $s['company_country'] ?? null,
            $s['company_zip'] ?? null,
          ]));
        @endphp
        <div class="mt-2 text-sm text-slate-600 space-y-1">
          @if(!empty($s['company_address']))
            <p class="whitespace-pre-line">{{ $s['company_address'] }}</p>
          @endif
          @if($companyLocation !== '')
            <p>{{ $companyLocation }}</p>
          @endif
          @if(!empty($s['email_from']))
            <p>{{ $s['email_from'] }}</p>
          @endif
          @if(!empty($s['company_tax_id']))
            <p>{{ $s['company_tax_label'] ?? 'NIT' }}: {{ $s['company_tax_id'] }}</p>
          @endif
        </div>
      </div>
      <div class="rounded-2xl border p-4 bg-neutral-50">
        <div class="font-semibold mb-1">Cliente</div>
        <!-- Custom Dropdown for Client -->
        <div class="dropdown-container" id="clienteDropdown">
            <div class="dropdown-trigger @error('cliente') border-red-500 @enderror">
                <input type="text" id="clienteSearch" placeholder="Escribe y selecciona..." value="{{ old('cliente', $prefill ?? '') }}" autocomplete="off">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div class="dropdown-menu custom-scroll">
                <div class="dropdown-item"
                     data-value="Sin Cliente"
                     data-id=""
                     data-currency="{{ strtoupper((string) $base) }}"
                     data-meta="">
                    Sin Cliente
                </div>
                @foreach($clientes as $c)
                    <div class="dropdown-item"
                         data-value="{{ $c['empresa'] }}"
                         data-id="{{ $c['id'] }}"
                         data-currency="{{ strtoupper((string) ($c['moneda'] ?? $base)) }}"
                         data-meta="
                            <div><span class='font-semibold text-slate-800'>{{ $c['contacto_nombre'] ?? $c['empresa'] }}</span></div>
                            <div class='text-slate-500'>{{ $c['contacto_email'] ?? '' }}</div>
                            <div class='text-slate-500'>{{ $c['contacto_telefono'] ?? '' }}</div>
                            <div class='text-slate-500'>{{ $c['direccion'] ?? '' }} {{ $c['ciudad'] ?? '' }} {{ $c['pais'] ?? '' }}</div>
                         ">
                        {{ $c['empresa'] }}
                    </div>
                @endforeach
                <div class="dropdown-item border-t border-slate-200 font-semibold text-lime-700" data-create-client="1">
                    + Crear cliente
                </div>
            </div>
        </div>
        <input type="hidden" name="cliente" id="clienteField" value="{{ old('cliente', $prefill ?? '') }}" form="formFactura">
        <input type="hidden" name="cliente_id" id="clienteIdField" value="{{ old('cliente_id', $prefill_id ?? '') }}" form="formFactura">
        @error('cliente') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
        <div id="clienteInfo" class="text-xs text-slate-500 mt-2 space-y-1"></div>
      </div>
    </div>
    <form method="POST" action="{{ route('facturas.store') }}" id="formFactura" class="space-y-4">
      @csrf
      <input type="hidden" name="numero" value="{{ $nextNumber }}">
      <input type="hidden" name="estado" value="En borrador">

      {{-- Fechas (selectores independientes) --}}
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-bold text-slate-700 uppercase tracking-widest mb-3">Fechas</div>
        <div class="form-input bg-white p-3 md:p-4">
          <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
            <div class="min-w-0">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Emisión</div>
              <div class="relative">
                <input type="text" id="fechaDisplay" placeholder="dd/mm/aaaa" class="form-input w-full pr-10 bg-white">
                <button type="button" id="issueCalendarBtn" class="absolute inset-y-0 right-2 my-auto h-7 w-7 inline-flex items-center justify-center text-slate-500 hover:text-slate-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="hidden md:block w-px h-10 bg-slate-200"></div>
            <div class="md:hidden h-px w-full bg-slate-200"></div>

            <div class="min-w-0">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Vencimiento</div>
              <div class="relative">
                <input type="text" id="vencDisplay" placeholder="dd/mm/aaaa" class="form-input w-full pr-10 bg-white">
                <button type="button" id="dueCalendarBtn" class="absolute inset-y-0 right-2 my-auto h-7 w-7 inline-flex items-center justify-center text-slate-500 hover:text-slate-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        <input id="issueDateInput" type="text" class="sr-only" readonly>
        <input id="dueDateInput" type="text" class="sr-only" readonly>
        <input type="hidden" name="fecha" id="fecha" value="{{ old('fecha', date('Y-m-d')) }}">
        <input type="hidden" name="vencimiento" id="vencimiento" value="{{ old('vencimiento', '') }}">
        @error('fecha') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
        @error('vencimiento') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
      </div>

      {{-- Factura recurrente --}}
      <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
          <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" id="recurrenceEnabled" name="recurrence_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-lime-500 focus:ring-lime-400" {{ old('recurrence_enabled') ? 'checked' : '' }}>
            <span class="text-sm font-bold text-slate-900">Factura recurrente</span>
          </label>
          <div id="recurrenceConfig" class="flex flex-wrap items-center gap-x-4 gap-y-2 {{ old('recurrence_enabled') ? '' : 'hidden' }}">
            <div class="flex items-center gap-2">
              <label for="recurrenceDay" class="text-sm text-slate-600 whitespace-nowrap">Día de envío</label>
              <input type="number" min="1" max="31" name="recurrence_day" id="recurrenceDay" class="form-input w-16 text-center py-1.5 px-2 text-sm" value="{{ old('recurrence_day', date('j')) }}" placeholder="5">
            </div>
            <div class="flex items-center gap-2">
              <label for="recurrenceEveryMonths" class="text-sm text-slate-600 whitespace-nowrap">Cada</label>
              <input type="number" min="1" max="12" name="recurrence_every_months" id="recurrenceEveryMonths" class="form-input w-16 text-center py-1.5 px-2 text-sm" value="{{ old('recurrence_every_months', 1) }}" placeholder="1">
              <span class="text-sm text-slate-500">mes(es)</span>
            </div>
          </div>
        </div>
        <div id="recurrencePreview" class="mt-1.5 text-xs text-slate-500"></div>
      </div>

      {{-- Proyecto --}}
      <div>
        <label class="text-sm font-medium">Proyecto (opcional)</label>
        <div class="dropdown-container mt-1" id="proyectoDropdown">
          <div class="dropdown-trigger">
            <input type="text" id="proyectoSearch" class="dropdown-value-input" placeholder="Seleccionar proyecto" autocomplete="off" readonly>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <div class="dropdown-menu custom-scroll" id="proyectosList">
            <div class="p-2 border-b border-slate-100 mb-1">
              <input type="text" id="proyectoSearchFilter" class="form-input dropdown-search-input" placeholder="Buscar..." autocomplete="off">
            </div>
            <div id="proyectosItems">
              <div class="dropdown-item text-slate-500 italic">Selecciona un cliente primero</div>
            </div>
          </div>
        </div>
        <input type="hidden" name="proyecto_id" id="proyectoIdField" value="{{ old('proyecto_id', '') }}" form="formFactura">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_minmax(0,0.85fr)] gap-4">
        <div>
          <label class="text-sm font-medium">Moneda</label>
          <select name="moneda" id="monedaField" class="form-select mt-1">
            @foreach($allowedCurrencies as $m)
              <option @selected($initialCurrency===$m)>{{ $m }}</option>
            @endforeach
          </select>
        </div>
        <div id="tasaWrap" class="hidden">
          <label class="text-sm font-medium">Tasa vs {{ $base }}</label>
          <div class="flex items-center gap-2 mt-1">
            <input name="tasa" id="tasaField" class="form-input flex-1" placeholder="Ej. 4000" value="{{ old('tasa', '') }}">
            <button type="button" id="btnActualizarTasa" class="px-3 py-2 rounded-xl border text-xs font-bold text-slate-700 hover:bg-slate-50 whitespace-nowrap">
              ↻ Tasa
            </button>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium">Impuesto (%)</label>
          <input name="tax_rate" id="taxRateField" type="number" step="1" min="0" max="100" inputmode="numeric" value="{{ old('tax_rate', $defaultTaxRate) }}" class="form-input mt-1 max-w-[220px]" placeholder="0">
        </div>
      </div>
      <div>
        <h3 class="text-lg font-bold text-slate-900 mb-4">Items</h3>
        
        <!-- Headers -->
        <div class="grid grid-cols-12 gap-4 mb-2 text-sm font-medium text-slate-500 px-1">
          <div class="col-span-5">Descripción</div>
          <div class="col-span-2 text-center">Cantidad</div>
          <div class="col-span-2 text-center">Precio</div>
          <div class="col-span-2 text-right">Importe</div>
          <div class="col-span-1"></div>
        </div>

        <div id="items" class="space-y-2">
          @foreach($oldItems as $i => $oldItem)
            @php
              $itemProductoId = (string) ($oldItem['producto_id'] ?? '');
              $itemDetalle = (string) ($oldItem['detalle'] ?? '');
              $itemDescripcion = (string) ($oldItem['descripcion'] ?? '');
              $itemCantidad = $oldItem['cantidad'] ?? '';
              $itemPrecio = $oldItem['precio'] ?? '';
              $itemTotal = (float) ($itemCantidad ?: 0) * (float) ($itemPrecio ?: 0);
            @endphp
          <div class="grid grid-cols-12 gap-4 items-center item group">
            <div class="col-span-5">
                <input type="hidden" name="items[{{ $i }}][producto_id]" class="product-id-input" value="{{ $itemProductoId }}">
                <input type="hidden" name="items[{{ $i }}][detalle]" class="product-detail-input" value="{{ $itemDetalle }}">
                <textarea name="items[{{ $i }}][descripcion]" rows="1" class="form-input item-desc-input desc-input resize-none overflow-hidden leading-6 h-[54px]" placeholder="Descripción del servicio o producto" required>{{ $itemDescripcion }}</textarea>
            </div>
            <div class="col-span-2">
                <input name="items[{{ $i }}][cantidad]" type="number" step="0.01" class="form-input qty-input text-center" placeholder="0" value="{{ $itemCantidad }}">
            </div>
            <div class="col-span-2">
                <input name="items[{{ $i }}][precio]" type="number" step="0.01" class="form-input price-input text-center" placeholder="0" value="{{ $itemPrecio }}">
            </div>
            <div class="col-span-2 text-right">
                <div class="total-display text-sm font-medium text-slate-700 py-2">{{ format_currency($itemTotal, old('moneda', $base)) }}</div>
            </div>
            <div class="col-span-1 text-center">
                <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
          </div>
          @endforeach
        </div>
        <datalist id="productsList"></datalist>

        <div class="mt-4 flex flex-wrap items-center gap-4">
          <button type="button" id="addItem" class="text-sm font-bold text-lime-600 hover:text-lime-700 flex items-center gap-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Agregar item
          </button>
          <button type="button" id="addProductItem" class="text-sm font-bold text-lime-600 hover:text-lime-700 flex items-center gap-1 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Agregar Prod./Serv.
          </button>
        </div>
      </div>
      <div class="flex justify-end gap-3">
        <a href="{{ route('facturas.index') }}" class="px-4 py-2 rounded-full border">Cancelar</a>
        <input type="hidden" name="publicar" id="publicarField" value="">
        <button type="button" class="px-4 py-2 rounded-full border" id="btnGuardar">Guardar</button>
        <button type="button" class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold" id="btnPublicar">Publicar</button>
      </div>
    </form>
    <div class="mt-6 flex justify-end">
      <div class="w-60">
        <div class="flex justify-between py-1"><span>Subtotal</span><span id="subTotal">$0.00</span></div>
        <div class="flex justify-between py-1"><span id="taxLabel">Impuestos ({{ $defaultTaxRate }}%)</span><span id="taxTotal">$0.00</span></div>
        <div class="flex justify-between py-2 font-bold text-lg"><span>Total</span><span id="grandTotal">$0.00</span></div>
      </div>
    </div>
  </div>
  <div id="quickClientModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
        <div class="text-lg font-bold text-slate-900">Crear cliente rápido</div>
        <button type="button" id="closeQuickClientModal" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="md:col-span-2">
          <label class="text-sm font-medium">Empresa</label>
          <input id="qcEmpresa" class="form-input" placeholder="Nombre de la empresa">
        </div>
        <div>
          <label class="text-sm font-medium">Contacto</label>
          <input id="qcNombre" class="form-input" placeholder="Nombre de contacto">
        </div>
        <div>
          <label class="text-sm font-medium">Email</label>
          <input id="qcEmail" type="email" class="form-input" placeholder="correo@cliente.com">
        </div>
        <div>
          <label class="text-sm font-medium">Teléfono</label>
          <input id="qcTelefono" class="form-input" placeholder="Teléfono">
        </div>
        <div>
          <label class="text-sm font-medium">NIT</label>
          <input id="qcNit" class="form-input" placeholder="NIT">
        </div>
      </div>
      <div id="quickClientError" class="px-5 pb-2 text-sm text-rose-600 hidden"></div>
      <div class="px-5 pb-5 flex justify-end gap-2">
        <button type="button" id="cancelQuickClient" class="px-4 py-2 rounded-full border">Cancelar</button>
        <button type="button" id="saveQuickClient" class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Guardar cliente</button>
      </div>
    </div>
  </div>
  <script>
    // Setup Client Dropdown
    const clientCurrenciesById = @json($clientCurrencyById);
    const clientCurrenciesByName = @json($clientCurrencyByName);

    function normalizedClientName(value) {
      return String(value || '').trim().toLocaleLowerCase('es');
    }

    function resolveClientCurrency(itemOrData) {
      if (!itemOrData) return '';
      const id = String(itemOrData.dataset?.id || itemOrData.id || '').trim();
      const name = String(itemOrData.dataset?.value || itemOrData.value || itemOrData.name || '').trim();
      return String(
        itemOrData.dataset?.currency
        || (id ? clientCurrenciesById[id] : '')
        || (name ? clientCurrenciesByName[normalizedClientName(name)] : '')
        || ''
      ).toUpperCase();
    }

    function setClientInfoFromItem(item) {
        const info = document.getElementById('clienteInfo');
        if (!info) return;
        info.innerHTML = item?.dataset?.meta || '';
    }

    function applyClientCurrency(currencyCode) {
      const monedaField = document.getElementById('monedaField');
      if (!monedaField || !currencyCode) return;
      const normalized = String(currencyCode).trim().toUpperCase();
      const optionExists = Array.from(monedaField.options || []).some((opt) => opt.value === normalized);
      if (!optionExists || monedaField.value === normalized) return;
      monedaField.value = normalized;
      monedaField.dispatchEvent(new Event('input', { bubbles: true }));
      monedaField.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function selectClientItem(item, updateSearch = false) {
        if (!item || item.dataset.createClient === '1') return;
        const id = item.dataset.id || '';
        const name = item.dataset.value || item.innerText.trim();
        document.getElementById('clienteField').value = name;
        document.getElementById('clienteIdField').value = id;
        if (updateSearch) {
          const searchInput = document.getElementById('clienteSearch');
          if (searchInput) searchInput.value = name;
        }
        setClientInfoFromItem(item);
        applyClientCurrency(resolveClientCurrency(item));
        loadProyectosByCliente(id || null);
    }

    function setClientInfoById(id) {
        const menu = document.querySelector('#clienteDropdown .dropdown-menu');
        if (!menu) return;
        const match = menu.querySelector(`.dropdown-item[data-id="${id}"]`);
        if (match) {
          selectClientItem(match, true);
        }
    }

    setupDropdown('clienteDropdown', (item) => {
        selectClientItem(item, false);
    });

    const quickClientModal = document.getElementById('quickClientModal');
    const closeQuickClientModal = document.getElementById('closeQuickClientModal');
    const cancelQuickClient = document.getElementById('cancelQuickClient');
    const saveQuickClient = document.getElementById('saveQuickClient');
    const quickClientError = document.getElementById('quickClientError');

    function toggleQuickClientModal(open) {
      if (!quickClientModal) return;
      quickClientModal.classList.toggle('hidden', !open);
      quickClientModal.classList.toggle('flex', open);
      if (!open) return;
      setTimeout(() => document.getElementById('qcEmpresa')?.focus(), 0);
    }

    function resetQuickClientForm() {
      ['qcEmpresa','qcNombre','qcEmail','qcTelefono','qcNit'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      quickClientError?.classList.add('hidden');
      if (quickClientError) quickClientError.textContent = '';
    }

    function buildClientMeta(cliente) {
      const name = (cliente.contacto_nombre || cliente.empresa || '').trim();
      const email = (cliente.contacto_email || '').trim();
      const phone = (cliente.contacto_telefono || '').trim();
      const location = [cliente.direccion || '', cliente.ciudad || '', cliente.pais || ''].join(' ').trim();
      return `
        <div><span class='font-semibold text-slate-800'>${name}</span></div>
        <div class='text-slate-500'>${email}</div>
        <div class='text-slate-500'>${phone}</div>
        <div class='text-slate-500'>${location}</div>
      `;
    }

    function appendClientToDropdown(cliente) {
      const menu = document.querySelector('#clienteDropdown .dropdown-menu');
      if (!menu || !cliente?.id) return null;
      const createItem = menu.querySelector('[data-create-client="1"]');
      const item = document.createElement('div');
      item.className = 'dropdown-item';
      item.dataset.value = cliente.empresa;
      item.dataset.id = cliente.id;
      item.dataset.currency = String(cliente.moneda || '').toUpperCase();
      item.dataset.meta = buildClientMeta(cliente);
      item.textContent = cliente.empresa;
      if (createItem) {
        const normalizedName = String(cliente.empresa || '').trim().toLocaleLowerCase('es');
        const options = Array.from(menu.querySelectorAll('.dropdown-item[data-id]:not([data-create-client="1"])'))
          .filter((option) => option.dataset.id);
        const before = options.find((option) => String(option.dataset.value || option.textContent || '').trim().toLocaleLowerCase('es') > normalizedName);
        menu.insertBefore(item, before || createItem);
      } else {
        menu.appendChild(item);
      }
      return item;
    }

    async function saveQuickClientHandler() {
      if (!saveQuickClient) return;
      const empresa = (document.getElementById('qcEmpresa')?.value || '').trim();
      if (!empresa) {
        quickClientError.textContent = 'La empresa es obligatoria.';
        quickClientError.classList.remove('hidden');
        return;
      }

      const payload = {
        empresa,
        contacto_nombre: (document.getElementById('qcNombre')?.value || '').trim(),
        contacto_email: (document.getElementById('qcEmail')?.value || '').trim(),
        contacto_telefono: (document.getElementById('qcTelefono')?.value || '').trim(),
        nit: (document.getElementById('qcNit')?.value || '').trim(),
        moneda: (document.getElementById('monedaField')?.value || '').trim(),
      };

      saveQuickClient.disabled = true;
      saveQuickClient.textContent = 'Guardando...';
      quickClientError.classList.add('hidden');

      try {
        const res = await fetch('/api/clientes/quick', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data?.ok || !data?.cliente) {
          throw new Error(data?.message || 'No se pudo crear el cliente');
        }

        const created = data.cliente;
        appendClientToDropdown(created);

        document.getElementById('clienteField').value = created.empresa || '';
        document.getElementById('clienteIdField').value = created.id || '';
        const searchInput = document.getElementById('clienteSearch');
        if (searchInput) searchInput.value = created.empresa || '';
        document.getElementById('clienteInfo').innerHTML = buildClientMeta(created);
        applyClientCurrency(created.moneda || '');
        loadProyectosByCliente(created.id || null);

        toggleQuickClientModal(false);
        resetQuickClientForm();
      } catch (err) {
        quickClientError.textContent = err?.message || 'Error al crear el cliente.';
        quickClientError.classList.remove('hidden');
      } finally {
        saveQuickClient.disabled = false;
        saveQuickClient.textContent = 'Guardar cliente';
      }
    }

    document.querySelector('#clienteDropdown .dropdown-menu')?.addEventListener('click', (e) => {
      const createItem = e.target.closest('[data-create-client="1"]');
      if (!createItem) return;
      e.preventDefault();
      e.stopPropagation();
      document.querySelector('#clienteDropdown .dropdown-menu')?.classList.remove('show');
      toggleQuickClientModal(true);
    });
    closeQuickClientModal?.addEventListener('click', () => { toggleQuickClientModal(false); resetQuickClientForm(); });
    cancelQuickClient?.addEventListener('click', () => { toggleQuickClientModal(false); resetQuickClientForm(); });
    quickClientModal?.addEventListener('click', (e) => {
      if (e.target === quickClientModal) {
        toggleQuickClientModal(false);
        resetQuickClientForm();
      }
    });
    saveQuickClient?.addEventListener('click', saveQuickClientHandler);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && quickClientModal?.classList.contains('flex')) {
        toggleQuickClientModal(false);
        resetQuickClientForm();
      }
    });
    
    // Clear Client ID on manual input to ensure valid selection
    document.getElementById('clienteSearch').addEventListener('input', (e) => {
        document.getElementById('clienteIdField').value = '';
        document.getElementById('clienteField').value = e.target.value; // Sync name for validation/saving
        document.getElementById('clienteInfo').textContent = '';
        document.getElementById('proyectoSearch').value = '';
        document.getElementById('proyectoIdField').value = '';
        loadProyectosByCliente(null);

        const typed = normalizedClientName(e.target.value);
        if (!typed) return;
        const menu = document.querySelector('#clienteDropdown .dropdown-menu');
        const exactMatch = Array.from(menu?.querySelectorAll('.dropdown-item[data-id]') || [])
          .find((item) => normalizedClientName(item.dataset.value || item.innerText) === typed);
        if (exactMatch) {
          selectClientItem(exactMatch, false);
        }
    });

    // Setup Project Dropdown (Dynamic)
    const proyectosList = document.getElementById('proyectosItems');
    const proyectoSearch = document.getElementById('proyectoSearch');
    const proyectoIdField = document.getElementById('proyectoIdField');
    
    // Capture instance to update items later
    let projectDropdown = setupDropdown('proyectoDropdown', (item) => {
        proyectoIdField.value = item.dataset.id;
    });
    
    // Project value is selected from dropdown list
    
    async function loadProyectosByCliente(clientId) {
        proyectosList.innerHTML = '<div class="dropdown-item text-slate-400">Cargando...</div>';
        if (!clientId) {
            proyectosList.innerHTML = '<div class="dropdown-item text-slate-500 italic">Selecciona un cliente primero</div>';
            return;
        }
        
        try {
            const res = await fetch('/api/proyectos?cliente_id=' + encodeURIComponent(clientId));
            const js = await res.json();
            
            if ((js.data || []).length > 0) {
                proyectosList.innerHTML = '';
                js.data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'dropdown-item';
                    div.dataset.value = p.titulo;
                    div.dataset.id = p.id;
                    div.innerText = p.titulo;
                    proyectosList.appendChild(div);
                });
                
                // Update items listeners without adding new input listeners
                if (projectDropdown && projectDropdown.updateItems) {
                    projectDropdown.updateItems();
                } else {
                    // Fallback if global setupDropdown doesn't return object (older version cache?)
                    // But we just updated app.blade.php, so it should work.
                    // If not, we could re-call setupDropdown but that adds listeners.
                }

            } else {
                proyectosList.innerHTML = '<div class="dropdown-item text-slate-500 italic">Sin proyectos activos</div>';
            }
        } catch(e) {
            proyectosList.innerHTML = '<div class="dropdown-item text-red-400">Error al cargar</div>';
        }
    }

    const items = document.getElementById('items');
    const add = document.getElementById('addItem');
    const addProductItem = document.getElementById('addProductItem');
    let idx = {{ $oldItems->count() }};
    
    const decimalPlaces = parseInt(document.body.dataset.decimals || '2', 10);

    const recurrenceEnabled = document.getElementById('recurrenceEnabled');
    const recurrenceConfig = document.getElementById('recurrenceConfig');
    const recurrenceDay = document.getElementById('recurrenceDay');
    const recurrenceEveryMonths = document.getElementById('recurrenceEveryMonths');
    const recurrencePreview = document.getElementById('recurrencePreview');
    let availableProducts = [];

    function formatDateEs(dateObj) {
      return dateObj.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function computeNextRecurringDate(baseDate, day, everyMonths) {
      const safeDay = Math.max(1, Math.min(31, Number(day) || 1));
      const safeMonths = Math.max(1, Math.min(12, Number(everyMonths) || 1));

      const base = new Date(baseDate + 'T12:00:00');
      if (Number.isNaN(base.getTime())) return null;

      let candidate = new Date(base.getTime());
      const lastDay = new Date(candidate.getFullYear(), candidate.getMonth() + 1, 0).getDate();
      candidate.setDate(Math.min(safeDay, lastDay));

      if (candidate <= base) {
        candidate = new Date(candidate.getFullYear(), candidate.getMonth() + safeMonths, 1);
        const lastDay2 = new Date(candidate.getFullYear(), candidate.getMonth() + 1, 0).getDate();
        candidate.setDate(Math.min(safeDay, lastDay2));
      }

      return candidate;
    }

    function advanceRecurringDate(dateObj, day, everyMonths) {
      const safeDay = Math.max(1, Math.min(31, Number(day) || 1));
      const safeMonths = Math.max(1, Math.min(12, Number(everyMonths) || 1));
      const next = new Date(dateObj.getFullYear(), dateObj.getMonth() + safeMonths, 1, 12, 0, 0);
      const lastDay = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
      next.setDate(Math.min(safeDay, lastDay));
      return next;
    }

    function selectedRecurringLeadDays() {
      if (!Array.isArray(availableProducts) || !availableProducts.length) return 0;
      let leadDays = 0;
      document.querySelectorAll('#items .item').forEach((row) => {
        const productId = String(row.querySelector('.product-id-input')?.value || '').trim();
        const desc = String(row.querySelector('.desc-input')?.value || '').trim();
        const product = availableProducts.find((p) => (
          (productId && String(p.id || '') === productId)
          || (!productId && desc && String(p.nombre || '') === desc)
        ));
        if (product?.service_expiry_reminder_enabled) {
          leadDays = Math.max(leadDays, Math.max(1, Math.min(90, Number(product.service_expiry_reminder_days_before) || 7)));
        }
      });
      return leadDays;
    }

    function refreshRecurrenceUI() {
      if (!recurrenceEnabled || !recurrenceConfig || !recurrencePreview) return;
      const active = recurrenceEnabled.checked;
      recurrenceConfig.classList.toggle('hidden', !active);

      if (!active) {
        recurrencePreview.textContent = 'La factura se enviará manualmente.';
        return;
      }

      const issueDate = document.getElementById('fecha')?.value || new Date().toISOString().slice(0, 10);
      const leadDays = selectedRecurringLeadDays();
      let nextDueDate = computeNextRecurringDate(issueDate, recurrenceDay?.value, recurrenceEveryMonths?.value);
      let nextDate = nextDueDate ? new Date(nextDueDate.getTime()) : null;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (nextDate && leadDays > 0) {
        nextDate.setDate(nextDate.getDate() - leadDays);
        while (nextDate < today) {
          nextDueDate = advanceRecurringDate(nextDueDate, recurrenceDay?.value, recurrenceEveryMonths?.value);
          nextDate = new Date(nextDueDate.getTime());
          nextDate.setDate(nextDate.getDate() - leadDays);
        }
      } else if (nextDate) {
        while (nextDate < today) {
          nextDueDate = advanceRecurringDate(nextDueDate, recurrenceDay?.value, recurrenceEveryMonths?.value);
          nextDate = new Date(nextDueDate.getTime());
        }
      }
      if (!nextDate) {
        recurrencePreview.textContent = 'Configura la fecha de emisión para calcular el próximo envío.';
        return;
      }

      recurrencePreview.textContent = leadDays > 0
        ? `Próximo envío automático: ${formatDateEs(nextDate)}. Vence: ${formatDateEs(nextDueDate)}.`
        : `Próximo envío automático: ${formatDateEs(nextDate)}.`;
    }

    recurrenceEnabled?.addEventListener('change', refreshRecurrenceUI);
    recurrenceDay?.addEventListener('input', refreshRecurrenceUI);
    recurrenceEveryMonths?.addEventListener('input', refreshRecurrenceUI);
    
    // Currency Map
    const currencyMap = {
      'USD': '$', 'EUR': '€', 'MXN': '$', 'COP': '$', 'ARS': '$', 'CLP': '$',
      'PEN': 'S/', 'GBP': '£', 'CAD': '$', 'JPY': '¥', 'AUD': '$', 'CNY': '¥',
      'CHF': 'Fr', 'HKD': '$', 'NZD': '$', 'SEK': 'kr', 'KRW': '₩', 'SGD': '$',
      'INR': '₹', 'BRL': 'R$', 'RUB': '₽', 'ZAR': 'R', 'TRY': '₺'
    };

    function getCurrencySymbol() {
        const val = document.getElementById('monedaField').value;
        return currencyMap[val] || '$';
    }

    function appendItemRow(useProductSearch = false) {
      const row = document.createElement('div');
      row.className = 'grid grid-cols-12 gap-4 items-center item group';
      const descriptionInput = useProductSearch
        ? `<input type="hidden" name="items[${idx}][producto_id]" class="product-id-input" value=""><input type="hidden" name="items[${idx}][detalle]" class="product-detail-input" value=""><div class="product-selector relative"><input name="items[${idx}][descripcion]" class="form-input item-desc-input product-desc-input desc-input cursor-pointer" placeholder="Selecciona producto o servicio" autocomplete="off" readonly required><div class="product-options hidden absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden"><div class="p-2 border-b border-slate-100"><input type="text" class="form-input product-search-input" placeholder="Buscar..." autocomplete="off"></div><div class="product-options-list max-h-56 overflow-auto"></div></div></div><div class="product-detail-preview mt-1 text-xs text-slate-500 hidden whitespace-pre-line"></div>`
        : `<input type="hidden" name="items[${idx}][producto_id]" class="product-id-input" value=""><input type="hidden" name="items[${idx}][detalle]" class="product-detail-input" value=""><textarea name="items[${idx}][descripcion]" rows="1" class="form-input item-desc-input desc-input resize-none overflow-hidden leading-6 h-[54px]" placeholder="Descripción del servicio o producto" required></textarea>`;
      row.innerHTML = `
        <div class="col-span-5">
            ${descriptionInput}
        </div>
        <div class="col-span-2">
            <input name="items[${idx}][cantidad]" type="number" step="0.01" class="form-input qty-input text-center" placeholder="0" value="">
        </div>
        <div class="col-span-2">
            <input name="items[${idx}][precio]" type="number" step="0.01" class="form-input price-input text-center" placeholder="0">
        </div>
        <div class="col-span-2 text-right">
            <div class="total-display text-sm font-medium text-slate-700 py-2">${formatMoney(0)}</div>
        </div>
        <div class="col-span-1 text-center">
            <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors remove">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
      `;
      items.appendChild(row);
      idx++;
      bindRemove(row.querySelector('.remove'));
      if (useProductSearch) attachProductSelector(row);
      const manualDesc = row.querySelector('textarea.desc-input');
      if (manualDesc) autoResizeDesc(manualDesc);
    }

    add.addEventListener('click', () => appendItemRow(false));
    addProductItem?.addEventListener('click', () => appendItemRow(true));
    
    function bindRemove(btn){
      btn.addEventListener('click', () => {
          if (document.querySelectorAll('.item').length > 1) {
              btn.closest('.item').remove();
              recalc();
          }
      });
    }
    document.querySelectorAll('.remove').forEach(b => bindRemove(b));

    function autoResizeDesc(el) {
      if (!el) return;
      const resize = () => {
        el.style.height = 'auto';
        el.style.height = `${Math.max(el.scrollHeight, 54)}px`;
      };
      el.addEventListener('input', resize);
      resize();
    }
    document.querySelectorAll('textarea.desc-input').forEach(autoResizeDesc);
    
    // Nueva factura debe iniciar limpia: sin recuperación/autoguardado de borrador previo.
    const form = document.getElementById('formFactura');
    try {
      localStorage.removeItem('factura_draft');
    } catch(e){}
    
    const moneda = document.getElementById('monedaField');
    const tasaWrap = document.getElementById('tasaWrap');
    const tasaField = document.getElementById('tasaField');
    const btnActualizarTasa = document.getElementById('btnActualizarTasa');
    const taxRateField = document.getElementById('taxRateField');
    const taxLabel = document.getElementById('taxLabel');
    const baseCurrency = '{{ $base }}';
    const numberFormatter = new Intl.NumberFormat('es-ES', {
      minimumFractionDigits: decimalPlaces,
      maximumFractionDigits: decimalPlaces
    });
    function formatMoney(value){
      return getCurrencySymbol() + numberFormatter.format(value);
    }
    function getTaxRate(){
      if (!taxRateField) return 0;
      const rawValue = String(taxRateField.value || '').trim().replace(',', '.');
      if (!rawValue) {
        taxRateField.value = '';
        return 0;
      }
      const parsed = parseFloat(rawValue);
      const value = Number.isFinite(parsed) ? Math.max(0, Math.min(100, Math.round(parsed))) : 0;
      taxRateField.value = String(value);
      return value;
    }
    function updateTaxLabel(){
      if (!taxLabel) return;
      taxLabel.textContent = `Impuestos (${getTaxRate()}%)`;
    }
    function setRateLoading(isLoading) {
      if (!btnActualizarTasa) return;
      btnActualizarTasa.disabled = isLoading;
      btnActualizarTasa.textContent = isLoading ? 'Actualizando...' : 'Actualizar';
    }
    function parseRateToBase() {
      const raw = String(tasaField?.value || '').trim().replace(',', '.');
      const rate = parseFloat(raw);
      return Number.isFinite(rate) && rate > 0 ? rate : null;
    }
    function convertBaseToInvoice(basePrice) {
      if (moneda.value === baseCurrency) return basePrice;
      const rate = parseRateToBase();
      if (!rate) return basePrice;
      return basePrice / rate;
    }
    function setRowProductPrice(row, productBasePrice) {
      const priceInput = row.querySelector('.price-input');
      if (!priceInput) return;
      row.dataset.productPriceBase = String(productBasePrice);
      priceInput.value = convertBaseToInvoice(productBasePrice).toFixed(decimalPlaces);
    }
    function applyProductPriceToRow(row, product) {
      const priceInput = row.querySelector('.price-input');
      if (!priceInput) return;
      const invoiceCurr = moneda.value;
      const precios = product.precios || {};
      row.dataset.productPriceBase = String(parseFloat(product.precio || 0));
      row.dataset.productPrecios = JSON.stringify(precios);
      if (invoiceCurr !== baseCurrency && precios[invoiceCurr] !== undefined) {
        const v = parseFloat(precios[invoiceCurr]);
        priceInput.value = (Number.isFinite(v) ? v : 0).toFixed(decimalPlaces);
      } else {
        setRowProductPrice(row, parseFloat(product.precio || 0));
      }
    }
    function syncProductPricesFromBase() {
      document.querySelectorAll('#items .item').forEach((row) => {
        const invoiceCurr = moneda.value;
        const rawPrecios = row.dataset.productPrecios;
        if (rawPrecios) {
          try {
            const precios = JSON.parse(rawPrecios);
            if (invoiceCurr !== baseCurrency && precios[invoiceCurr] !== undefined) {
              const priceInput = row.querySelector('.price-input');
              if (priceInput) priceInput.value = parseFloat(precios[invoiceCurr]).toFixed(decimalPlaces);
              return;
            }
          } catch(e) {}
        }
        const rawBase = row.dataset.productPriceBase;
        if (!rawBase) return;
        const basePrice = parseFloat(rawBase);
        if (!Number.isFinite(basePrice)) return;
        const priceInput = row.querySelector('.price-input');
        if (!priceInput) return;
        priceInput.value = convertBaseToInvoice(basePrice).toFixed(decimalPlaces);
      });
    }
    async function syncRate(showToast = false){
      if (!tasaField || moneda.value === baseCurrency) return null;
      if (!showToast && String(tasaField.value || '').trim() !== '') {
        return parseRateToBase();
      }
      setRateLoading(true);
      try {
        const url = `/api/tasa?from=${encodeURIComponent(moneda.value)}&to=${encodeURIComponent(baseCurrency)}`;
        const res = await fetch(url);
        const data = await res.json();
        const rate = data?.rate;
        if (data?.ok && rate) {
          tasaField.value = Number(rate).toFixed(4);
          if (showToast && window.showNotification) {
            window.showNotification('Tasa actualizada', 'success');
          }
          return Number(rate);
        } else if (showToast && window.showNotification) {
          window.showNotification('No se pudo obtener la tasa', 'error');
        }
      } catch(e) {
        if (showToast && window.showNotification) {
          window.showNotification('No se pudo obtener la tasa', 'error');
        }
      } finally {
        setRateLoading(false);
      }
      return null;
    }
    moneda.addEventListener('change', async ()=>{
      tasaWrap.classList.toggle('hidden', moneda.value===baseCurrency);
      if (tasaField) tasaField.value = '';
      await syncRate(false);
      syncProductPricesFromBase();
      recalc();
    });
    tasaWrap.classList.toggle('hidden', moneda.value===baseCurrency);
    syncRate(false);
    if (btnActualizarTasa) {
      btnActualizarTasa.addEventListener('click', async () => {
        if (tasaField) tasaField.value = '';
        await syncRate(true);
        syncProductPricesFromBase();
        recalc();
      });
    }
    tasaField?.addEventListener('input', () => {
      syncProductPricesFromBase();
      recalc();
    });

    if (document.getElementById('clienteIdField').value) {
        setClientInfoById(document.getElementById('clienteIdField').value);
    }
    
    function recalc(){
      let sub=0;
      const sym = getCurrencySymbol();
      
      document.querySelectorAll('#items .item').forEach(row=>{
        const q = parseFloat(row.querySelector('input[name$="[cantidad]"]').value||'0');
        const p = parseFloat(row.querySelector('input[name$="[precio]"]').value||'0');
        const rowTot = q*p;
        sub += rowTot;
        
        // Update row display
        const disp = row.querySelector('.total-display');
        if(disp) disp.textContent = formatMoney(rowTot);
      });
      
      const tax = sub * (getTaxRate() / 100);
      const tot = sub+tax;
      updateTaxLabel();
      document.getElementById('subTotal').textContent = formatMoney(sub);
      document.getElementById('taxTotal').textContent = formatMoney(tax);
      document.getElementById('grandTotal').textContent = formatMoney(tot);
    }
    
    // Use delegation for better performance on dynamic items
    document.getElementById('items').addEventListener('input', (e) => {
        if (e.target.matches('.qty-input, .price-input')) {
            recalc();
        }
    });
    taxRateField?.addEventListener('input', recalc);
    recalc();
    
    document.addEventListener("DOMContentLoaded", function() {
      const fechaInput = document.getElementById('fecha');
      const vencInput  = document.getElementById('vencimiento');
      const fechaDisplay = document.getElementById('fechaDisplay');
      const vencDisplay = document.getElementById('vencDisplay');
      const issueCalendarBtn = document.getElementById('issueCalendarBtn');
      const dueCalendarBtn = document.getElementById('dueCalendarBtn');
      const toDate = (iso) => {
        if (!iso) return null;
        const d = new Date(iso + 'T12:00:00');
        return Number.isNaN(d.getTime()) ? null : d;
      };
      const isoToDisplay = (iso) => {
        const d = toDate(iso);
        if (!d) return '';
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
      };
      const displayToIso = (value) => {
        const raw = String(value || '').trim();
        if (!raw) return '';
        const m1 = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (m1) {
          const dd = m1[1].padStart(2, '0');
          const mm = m1[2].padStart(2, '0');
          return `${m1[3]}-${mm}-${dd}`;
        }
        const m2 = raw.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        if (m2) {
          return `${m2[1]}-${m2[2].padStart(2, '0')}-${m2[3].padStart(2, '0')}`;
        }
        return '';
      };
      const formatDateTyping = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        if (digits.length <= 2) return digits;
        if (digits.length <= 4) return `${digits.slice(0,2)}/${digits.slice(2)}`;
        return `${digits.slice(0,2)}/${digits.slice(2,4)}/${digits.slice(4)}`;
      };

      const fpIssue = flatpickr("#issueDateInput", {
        dateFormat: "Y-m-d",
        locale: "es",
        position: "auto center",
        static: false,
        positionElement: issueCalendarBtn || fechaDisplay,
        defaultDate: fechaInput.value || null,
        onChange: function(selectedDates) {
          if (!selectedDates[0]) return;
          const iso = selectedDates[0].toISOString().slice(0,10);
          fechaInput.value = iso;
          if (fechaDisplay) fechaDisplay.value = isoToDisplay(iso);
          refreshRecurrenceUI();
        }
      });

      const fpDue = flatpickr("#dueDateInput", {
        dateFormat: "Y-m-d",
        locale: "es",
        position: "auto center",
        static: false,
        positionElement: dueCalendarBtn || vencDisplay,
        allowInput: false,
        defaultDate: vencInput.value || null,
        onChange: function(selectedDates) {
          const iso = selectedDates[0] ? selectedDates[0].toISOString().slice(0,10) : '';
          vencInput.value = iso;
          if (vencDisplay) vencDisplay.value = isoToDisplay(iso);
        }
      });

      issueCalendarBtn?.addEventListener('click', () => fpIssue.open());
      dueCalendarBtn?.addEventListener('click', () => fpDue.open());

      if (!fechaInput.value) {
        fechaInput.value = new Date().toISOString().slice(0,10);
      }
      if (fechaDisplay) fechaDisplay.value = isoToDisplay(fechaInput.value || '');
      if (vencDisplay) vencDisplay.value = isoToDisplay(vencInput.value || '');

      const syncManualDate = (displayEl, hiddenEl, picker, refreshRec = false) => {
        const iso = displayToIso(displayEl?.value || '');
        if (!displayEl || !hiddenEl) return;
        if (!iso) {
          displayEl.value = isoToDisplay(hiddenEl.value || '');
          return;
        }
        hiddenEl.value = iso;
        displayEl.value = isoToDisplay(iso);
        picker.setDate(iso, false, "Y-m-d");
        if (refreshRec) refreshRecurrenceUI();
      };

      fechaDisplay?.addEventListener('blur', () => syncManualDate(fechaDisplay, fechaInput, fpIssue, true));
      fechaDisplay?.addEventListener('change', () => syncManualDate(fechaDisplay, fechaInput, fpIssue, true));
      vencDisplay?.addEventListener('blur', () => syncManualDate(vencDisplay, vencInput, fpDue, false));
      vencDisplay?.addEventListener('change', () => syncManualDate(vencDisplay, vencInput, fpDue, false));
      fechaDisplay?.addEventListener('input', () => {
        fechaDisplay.value = formatDateTyping(fechaDisplay.value);
      });
      vencDisplay?.addEventListener('input', () => {
        vencDisplay.value = formatDateTyping(vencDisplay.value);
      });

      refreshRecurrenceUI();
    });
    
    if (document.getElementById('clienteIdField').value) loadProyectosByCliente(document.getElementById('clienteIdField').value);
    let invoiceSubmitting = false;
    const btnPublicar = document.getElementById('btnPublicar');
    const btnGuardar = document.getElementById('btnGuardar');

    function lockInvoiceSubmitButtons() {
      [btnPublicar, btnGuardar].forEach((btn) => {
        if (!btn) return;
        btn.disabled = true;
        btn.classList.add('opacity-60', 'cursor-not-allowed');
      });
    }

    function submitInvoiceForm(publish) {
      if (invoiceSubmitting) return;
      invoiceSubmitting = true;
      document.getElementById('publicarField').value = publish ? '1' : '';
      lockInvoiceSubmitButtons();
      form.submit();
    }

    btnPublicar?.addEventListener('click', () => submitInvoiceForm(true));
    btnGuardar?.addEventListener('click', () => submitInvoiceForm(false));

    // Productos Integration
    const productsList = document.getElementById('productsList');

    function renderProductMenu(menu, inputValue = '') {
      const listHost = menu.querySelector('.product-options-list') || menu;
      listHost.innerHTML = '';
      const query = inputValue.trim().toLowerCase();

      if (!availableProducts.length) {
        const empty = document.createElement('div');
        empty.className = 'px-3 py-2 text-sm text-slate-500 italic';
        empty.textContent = 'No hay productos agregados';
        listHost.appendChild(empty);
        return;
      }

      const filtered = availableProducts.filter((p) => String(p.nombre || '').toLowerCase().includes(query));
      if (!filtered.length) {
        const empty = document.createElement('div');
        empty.className = 'px-3 py-2 text-sm text-slate-500 italic';
        empty.textContent = 'Sin coincidencias';
        listHost.appendChild(empty);
        return;
      }

      filtered.slice(0, 30).forEach((p) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-b-0';
        btn.textContent = p.nombre;
        btn.addEventListener('click', () => {
          const row = menu.closest('.item');
          const input = menu.parentElement?.querySelector('.product-desc-input');
          if (!input || !row) return;
          const idInput = row.querySelector('.product-id-input');
          const detailInput = row.querySelector('.product-detail-input');
          const detailPreview = row.querySelector('.product-detail-preview');
          input.value = p.nombre;
          if (idInput) idInput.value = String(p.id || '');
          const detailText = String(p.descripcion || '').trim();
          if (detailInput) detailInput.value = detailText;
          if (detailPreview) {
            detailPreview.textContent = detailText;
            detailPreview.classList.toggle('hidden', detailText === '');
          }
          input.dispatchEvent(new Event('input', { bubbles: true }));
          menu.classList.add('hidden');
        });
        listHost.appendChild(btn);
      });
    }

    function attachProductSelector(row) {
      const container = row.querySelector('.product-selector');
      const input = row.querySelector('.product-desc-input');
      const menu = row.querySelector('.product-options');
      const search = row.querySelector('.product-search-input');
      if (!container || !input || !menu) return;

      const openMenu = () => {
        if (search) search.value = '';
        renderProductMenu(menu, '');
        menu.classList.remove('hidden');
        if (window.adjustDropPosition) window.adjustDropPosition(input, menu);
        search?.focus();
      };

      input.addEventListener('focus', openMenu);
      input.addEventListener('click', openMenu);
      search?.addEventListener('input', () => renderProductMenu(menu, search.value));
      input.addEventListener('input', () => {
        const row = input.closest('.item');
        const idInput = row?.querySelector('.product-id-input');
        const detailInput = row?.querySelector('.product-detail-input');
        const detailPreview = row?.querySelector('.product-detail-preview');
        if (!row || !idInput) return;
        const matched = availableProducts.find((p) => String(p.nombre || '') === String(input.value || ''));
        if (!matched) {
          idInput.value = '';
          if (detailInput) detailInput.value = '';
          if (detailPreview) {
            detailPreview.textContent = '';
            detailPreview.classList.add('hidden');
          }
        } else if (detailInput) {
          const detailText = String(matched.descripcion || '').trim();
          detailInput.value = detailText;
          if (detailPreview) {
            detailPreview.textContent = detailText;
            detailPreview.classList.toggle('hidden', detailText === '');
          }
        }
      });
    }

    function initExistingProductSelectors() {
      document.querySelectorAll('#items .item').forEach((row) => attachProductSelector(row));
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
        initExistingProductSelectors();
        refreshRecurrenceUI();
      } catch(e) { console.error('Error loading products', e); }
    }
    loadProducts();

    document.getElementById('items').addEventListener('input', (e) => {
      if (e.target.classList.contains('product-desc-input')) {
        const val = e.target.value;
        const product = availableProducts.find(p => p.nombre === val);
        const row = e.target.closest('.item');
        if (!row) return;
        if (product) {
          applyProductPriceToRow(row, product);
          const idInput = row.querySelector('.product-id-input');
          const detailInput = row.querySelector('.product-detail-input');
          const detailPreview = row.querySelector('.product-detail-preview');
          if (idInput) idInput.value = String(product.id || '');
          const detailText = String(product.descripcion || '').trim();
          if (detailInput) detailInput.value = detailText;
          if (detailPreview) {
            detailPreview.textContent = detailText;
            detailPreview.classList.toggle('hidden', detailText === '');
          }
          recalc();
          refreshRecurrenceUI();
        } else {
          delete row.dataset.productPriceBase;
          const idInput = row.querySelector('.product-id-input');
          const detailInput = row.querySelector('.product-detail-input');
          const detailPreview = row.querySelector('.product-detail-preview');
          if (idInput) idInput.value = '';
          if (detailInput) detailInput.value = '';
          if (detailPreview) {
            detailPreview.textContent = '';
            detailPreview.classList.add('hidden');
          }
          refreshRecurrenceUI();
        }
      }
    });
  </script>
@endsection
