@extends('layouts.app')
@section('title','Editar factura')
@section('content')
  @php $currentTaxRate = (int) round((float) ($factura['tax_rate'] ?? ($settings['tax_rate'] ?? 16))); @endphp
  <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow border p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <div class="text-xl font-bold">Editar factura</div>
        <div class="text-sm text-slate-500">{{ $factura['numero'] ?? '' }}</div>
      </div>
      <a href="{{ route('facturas.show',$factura['id']) }}" class="px-3 py-2 rounded-full border text-sm">Volver</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="rounded-2xl border p-4 bg-neutral-50">
        <div class="font-semibold text-lg text-slate-900">{{ $settings['company_name'] ?? 'Mi empresa' }}</div>
        @php
          $companyLocation = implode(', ', array_filter([
            $settings['company_city'] ?? null,
            $settings['company_state'] ?? null,
            $settings['company_country'] ?? null,
            $settings['company_zip'] ?? null,
          ]));
        @endphp
        <div class="mt-2 text-sm text-slate-600 space-y-1">
          @if(!empty($settings['company_address']))
            <p class="whitespace-pre-line">{{ $settings['company_address'] }}</p>
          @endif
          @if($companyLocation !== '')
            <p>{{ $companyLocation }}</p>
          @endif
          @if(!empty($settings['email_from']))
            <p>{{ $settings['email_from'] }}</p>
          @endif
          @if(!empty($settings['company_tax_id']))
            <p>{{ $settings['company_tax_label'] ?? 'NIT' }}: {{ $settings['company_tax_id'] }}</p>
          @endif
        </div>
      </div>
      <div class="rounded-2xl border p-4 bg-neutral-50">
        <div class="font-semibold mb-1">Cliente</div>
        
        <!-- Custom Dropdown for Client -->
        <div class="dropdown-container" id="clienteDropdown">
            <div class="dropdown-trigger @error('cliente') border-red-500 @enderror">
                <input type="text" id="clienteSearch" placeholder="Escribe y selecciona..." value="{{ $factura['cliente'] ?? '' }}" autocomplete="off">
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
            </div>
        </div>
        <input type="hidden" name="cliente" id="clienteField" value="{{ $factura['cliente'] ?? '' }}" form="formFactura">
        <input type="hidden" name="cliente_id" id="clienteIdField" value="{{ $factura['cliente_id'] ?? '' }}" form="formFactura">
        @error('cliente') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
        <div id="clienteInfo" class="text-xs text-slate-500 mt-2 space-y-1"></div>
      </div>
    </div>

    <form method="POST" action="{{ route('facturas.update',$factura['id']) }}" id="formFactura" class="space-y-4">
      @csrf
      <!-- Estado se mantiene oculto -->
      <input type="hidden" name="estado" value="{{ $factura['estado'] ?? 'En borrador' }}">

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
        <input type="hidden" name="fecha" id="fecha" value="{{ old('fecha', $factura['fecha'] ?? '') }}">
        <input type="hidden" name="vencimiento" id="vencimiento" value="{{ old('vencimiento', $factura['vencimiento'] ?? '') }}">
        @error('fecha') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
        @error('vencimiento') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
      </div>

      @php
        $rec = (array)($factura['recurrencia'] ?? []);
        $recEnabled = old('recurrence_enabled', !empty($rec['enabled']));
        $recDay = old('recurrence_day', $rec['day_of_month'] ?? date('j'));
        $recEveryMonths = old('recurrence_every_months', $rec['every_months'] ?? 1);
      @endphp
      <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
          <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" id="recurrenceEnabled" name="recurrence_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-lime-500 focus:ring-lime-400" {{ $recEnabled ? 'checked' : '' }}>
            <span class="text-sm font-bold text-slate-900">Factura recurrente</span>
          </label>
          <div id="recurrenceConfig" class="flex flex-wrap items-center gap-x-4 gap-y-2 {{ $recEnabled ? '' : 'hidden' }}">
            <div class="flex items-center gap-2">
              <label for="recurrenceDay" class="text-sm text-slate-600 whitespace-nowrap">Día de envío</label>
              <input type="number" min="1" max="31" name="recurrence_day" id="recurrenceDay" class="form-input w-16 text-center py-1.5 px-2 text-sm" value="{{ $recDay }}" placeholder="5">
            </div>
            <div class="flex items-center gap-2">
              <label for="recurrenceEveryMonths" class="text-sm text-slate-600 whitespace-nowrap">Cada</label>
              <input type="number" min="1" max="12" name="recurrence_every_months" id="recurrenceEveryMonths" class="form-input w-16 text-center py-1.5 px-2 text-sm" value="{{ $recEveryMonths }}" placeholder="1">
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
              <div class="dropdown-item text-slate-500 italic">Cargando...</div>
            </div>
          </div>
        </div>
        <input type="hidden" name="proyecto_id" id="proyectoIdField" value="{{ $factura['proyecto_id'] ?? '' }}" form="formFactura">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_minmax(0,0.85fr)] gap-4">
        <div>
          <label class="text-sm font-medium">Moneda</label>
          <select name="moneda" id="monedaField" class="form-select mt-1">
            @foreach(['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY'] as $m)
              <option @selected(($factura['moneda'] ?? $base)===$m)>{{ $m }}</option>
            @endforeach
          </select>
        </div>
        <div id="tasaWrap" class="hidden">
          <label class="text-sm font-medium">Tasa vs {{ $base }}</label>
          <div class="flex items-center gap-2 mt-1">
            <input name="tasa" id="tasaField" value="{{ $factura['tasa'] ?? '' }}" class="form-input flex-1" placeholder="Ej. 4000">
            <button type="button" id="btnActualizarTasa" class="px-3 py-2 rounded-xl border text-xs font-bold text-slate-700 hover:bg-slate-50 whitespace-nowrap">
              ↻ Tasa
            </button>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium">Impuesto (%)</label>
          <input name="tax_rate" id="taxRateField" type="number" step="1" min="0" max="100" inputmode="numeric" value="{{ $currentTaxRate }}" class="form-input mt-1 max-w-[220px]" placeholder="0">
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
          @php $items = $factura['items'] ?? []; @endphp
          @if(empty($items))
            <div class="grid grid-cols-12 gap-4 items-center item group">
              <div class="col-span-5">
                <input type="hidden" name="items[0][producto_id]" class="product-id-input" value="">
                <input type="hidden" name="items[0][detalle]" class="product-detail-input" value="">
                <textarea name="items[0][descripcion]" rows="1" class="form-input item-desc-input desc-input resize-none overflow-hidden leading-6 h-[54px]" placeholder="Descripción del servicio o producto" required></textarea>
              </div>
              <div class="col-span-2">
                <input name="items[0][cantidad]" type="number" step="0.01" class="form-input qty-input text-center" placeholder="0" value="">
              </div>
              <div class="col-span-2">
                <input name="items[0][precio]" type="number" step="0.01" class="form-input price-input text-center" placeholder="0">
              </div>
              <div class="col-span-2 text-right">
                <div class="total-display text-sm font-medium text-slate-700 py-2">{{ format_currency(0, $factura['moneda'] ?? $base) }}</div>
              </div>
              <div class="col-span-1 text-center">
                <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors remove">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </div>
          @else
            @foreach($items as $i=>$it)
              <div class="grid grid-cols-12 gap-4 items-center item group">
                <div class="col-span-5">
                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $it['id'] ?? '' }}">
                    <input type="hidden" name="items[{{ $i }}][producto_id]" class="product-id-input" value="{{ $it['producto_id'] ?? '' }}">
                    <input type="hidden" name="items[{{ $i }}][detalle]" class="product-detail-input" value="{{ $it['detalle'] ?? '' }}">
                    <textarea name="items[{{ $i }}][descripcion]" rows="1" class="form-input item-desc-input desc-input resize-none overflow-hidden leading-6 h-[54px]" placeholder="Descripción del servicio o producto" required>{{ $it['descripcion'] ?? '' }}</textarea>
                </div>
                <div class="col-span-2">
                    <input name="items[{{ $i }}][cantidad]" type="number" step="0.01" class="form-input qty-input text-center" value="{{ $it['cantidad'] ?? '' }}" placeholder="0">
                </div>
                <div class="col-span-2">
                    <input name="items[{{ $i }}][precio]" type="number" step="0.01" class="form-input price-input text-center" value="{{ $it['precio'] ?? '' }}" placeholder="0">
                </div>
                <div class="col-span-2 text-right">
                    <div class="total-display text-sm font-medium text-slate-700 py-2">{{ format_currency(0, $factura['moneda'] ?? $base) }}</div>
                </div>
                <div class="col-span-1 text-center">
                    <button type="button" class="text-slate-400 hover:text-rose-500 p-2 transition-colors remove">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
              </div>
            @endforeach
          @endif
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
        <a href="{{ route('facturas.show',$factura['id']) }}" class="px-4 py-2 rounded-full border">Cancelar</a>
        <input type="hidden" name="publicar" id="publicarField" value="">
        <button class="px-4 py-2 rounded-full border" id="btnGuardar">Guardar cambios</button>
        <button class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold" id="btnPublicar">Publicar</button>
      </div>
    </form>

    <div id="editMeta" class="hidden" data-proyecto="{{ $factura['proyecto_id'] ?? '' }}"></div>
    <div class="mt-6 flex justify-end">
      <div class="w-60">
        <div class="flex justify-between py-1"><span>Subtotal</span><span id="subTotal">$0.00</span></div>
        <div class="flex justify-between py-1"><span id="taxLabel">Impuestos ({{ $currentTaxRate }}%)</span><span id="taxTotal">$0.00</span></div>
        <div class="flex justify-between py-2 font-bold text-lg"><span>Total</span><span id="grandTotal">$0.00</span></div>
      </div>
    </div>
  </div>

  <script>
    const form = document.getElementById('formFactura');
    
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
        monedaField.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setClientInfoById(id) {
        const menu = document.querySelector('#clienteDropdown .dropdown-menu');
        if (!menu) return;
        const match = menu.querySelector(`.dropdown-item[data-id="${id}"]`);
        if (match) {
            setClientInfoFromItem(match);
            applyClientCurrency(match.dataset.currency || '');
        }
    }

    // Setup Client Dropdown
    setupDropdown('clienteDropdown', (item) => {
        const id = item.dataset.id || '';
        const name = item.dataset.value || '';
        document.getElementById('clienteField').value = name;
        document.getElementById('clienteIdField').value = id;
        setClientInfoFromItem(item);
        applyClientCurrency(item.dataset.currency || '');
        loadProyectosByCliente(id || null);
    });
    
    // Clear Client ID on manual input to ensure valid selection
    document.getElementById('clienteSearch').addEventListener('input', (e) => {
        document.getElementById('clienteIdField').value = '';
        document.getElementById('clienteField').value = e.target.value;
        document.getElementById('clienteInfo').textContent = '';
        document.getElementById('proyectoSearch').value = '';
        document.getElementById('proyectoIdField').value = '';
        loadProyectosByCliente(null);
    });

    // Setup Project Dropdown (Dynamic)
    const proyectosList = document.getElementById('proyectosItems');
    const proyectoSearch = document.getElementById('proyectoSearch');
    const proyectoIdField = document.getElementById('proyectoIdField');
    
    // Project Dropdown instance
    let projectDropdown = setupDropdown('proyectoDropdown', (item) => {
        proyectoIdField.value = item.dataset.id;
    });

    // Project value is selected from dropdown list
    
    async function loadProyectosByCliente(clientId, preselectId = null) {
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
                let foundTitle = '';
                js.data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'dropdown-item';
                    div.dataset.value = p.titulo;
                    div.dataset.id = p.id;
                    div.innerText = p.titulo;
                    proyectosList.appendChild(div);
                    
                    if (preselectId && String(p.id) === String(preselectId)) {
                        foundTitle = p.titulo;
                    }
                });
                
                // If we need to pre-select visually
                if (foundTitle) {
                    proyectoSearch.value = foundTitle;
                }
                
                // Re-bind click events for new items
                if (projectDropdown && projectDropdown.updateItems) {
                    projectDropdown.updateItems();
                }
            } else {
                proyectosList.innerHTML = '<div class="dropdown-item text-slate-500 italic">Sin proyectos activos</div>';
            }
        } catch(e) {
            proyectosList.innerHTML = '<div class="dropdown-item text-red-400">Error al cargar</div>';
        }
    }

    // Initial Load
    (async function(){
        const currentClientId = document.getElementById('clienteIdField').value;
        const currentProyectoId = document.getElementById('proyectoIdField').value;
        if (currentClientId) {
            await loadProyectosByCliente(currentClientId, currentProyectoId);
        }
        if (currentClientId) {
            setClientInfoById(currentClientId);
        }
        
        // If we have a project ID but no title (e.g. if loadProyectos failed or if project is archived), 
        // we might want to fetch it specifically? 
        // The previous code did this via editMeta. Let's keep a fallback.
        if (currentProyectoId && !proyectoSearch.value) {
             try {
                const res = await fetch('/api/proyectos/'+encodeURIComponent(currentProyectoId));
                const js = await res.json();
                if (js?.data?.titulo) {
                  proyectoSearch.value = js.data.titulo;
                }
             } catch(e){}
        }
    })();


    const moneda = document.getElementById('monedaField');
    const tasaWrap = document.getElementById('tasaWrap');
    const tasaField = document.getElementById('tasaField');
    const btnActualizarTasa = document.getElementById('btnActualizarTasa');
    const taxRateField = document.getElementById('taxRateField');
    const taxLabel = document.getElementById('taxLabel');
    const baseCurrency = '{{ $base }}';
    const decimalPlaces = parseInt(document.body.dataset.decimals || '2', 10);
    const recurrenceEnabled = document.getElementById('recurrenceEnabled');
    const recurrenceConfig = document.getElementById('recurrenceConfig');
    const recurrenceDay = document.getElementById('recurrenceDay');
    const recurrenceEveryMonths = document.getElementById('recurrenceEveryMonths');
    const recurrencePreview = document.getElementById('recurrencePreview');

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

      if (candidate < base) {
        candidate = new Date(candidate.getFullYear(), candidate.getMonth() + safeMonths, 1);
        const lastDay2 = new Date(candidate.getFullYear(), candidate.getMonth() + 1, 0).getDate();
        candidate.setDate(Math.min(safeDay, lastDay2));
      }

      return candidate;
    }

    function refreshRecurrenceUI() {
      if (!recurrenceEnabled || !recurrenceConfig || !recurrencePreview) return;
      const active = recurrenceEnabled.checked;
      recurrenceConfig.classList.toggle('hidden', !active);

      if (!active) {
        recurrencePreview.textContent = 'La factura se enviará manualmente.';
        return;
      }

      const baseDate = document.getElementById('fecha')?.value || new Date().toISOString().slice(0, 10);
      const nextDate = computeNextRecurringDate(baseDate, recurrenceDay?.value, recurrenceEveryMonths?.value);
      if (!nextDate) {
        recurrencePreview.textContent = 'Configura la fecha de emisión para calcular el próximo envío.';
        return;
      }

      recurrencePreview.textContent = `Próximo envío automático: ${formatDateEs(nextDate)}.`;
    }

    recurrenceEnabled?.addEventListener('change', refreshRecurrenceUI);
    recurrenceDay?.addEventListener('input', refreshRecurrenceUI);
    recurrenceEveryMonths?.addEventListener('input', refreshRecurrenceUI);
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
      btnActualizarTasa.innerHTML = isLoading 
        ? '<svg class="animate-spin h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>' 
        : 'Actualizar';
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
      setRateLoading(true);
      try {
        const url = `/api/tasa?from=${encodeURIComponent(moneda.value)}&to=${encodeURIComponent(baseCurrency)}`;
        const res = await fetch(url);
        const data = await res.json();
        const rate = data?.rate;
        if (data?.ok && rate) {
          tasaField.value = Number(rate).toFixed(4);
          if (showToast && window.showNotification) {
            window.showNotification(`Tasa actualizada: ${rate}`, 'success');
          }
          return Number(rate);
        } else if (showToast && window.showNotification) {
          window.showNotification('No se pudo obtener la tasa', 'error');
        }
      } catch(e) {
        console.error(e);
        if (showToast && window.showNotification) {
          window.showNotification('Error de conexión', 'error');
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

    // Items Logic
    const items = document.getElementById('items');
    const add = document.getElementById('addItem');
    const addProductItem = document.getElementById('addProductItem');
    let idx = document.querySelectorAll('#items .item').length || 1;
    
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
    
    function bindRemove(btn){
      btn.addEventListener('click', () => {
          if (document.querySelectorAll('.item').length > 1) {
              btn.closest('.item').remove();
              recalc();
          }
      });
    }
    document.querySelectorAll('.remove').forEach(b=>bindRemove(b));
    function autoResizeDesc(el) {
      if (!el) return;
      const resize = () => {
        const val = String(el.value || '');
        if (!val.includes('\n')) {
          el.style.height = '54px';
          return;
        }
        el.style.height = 'auto';
        el.style.height = `${Math.max(el.scrollHeight, 54)}px`;
      };
      el.addEventListener('input', resize);
      resize();
    }
    document.querySelectorAll('textarea.desc-input').forEach(autoResizeDesc);
    
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

    function recalc(){
      let sub=0;
      document.querySelectorAll('#items .item').forEach(row=>{
        const q = parseFloat(row.querySelector('input[name$="[cantidad]"]').value||'0');
        const p = parseFloat(row.querySelector('input[name$="[precio]"]').value||'0');
        const rowTot = q*p;
        sub += rowTot;
        
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
    // Removed old items listener logic as delegation covers it
    recalc(); // Initial calc

    document.getElementById('btnPublicar')?.addEventListener('click', ()=>{
      document.getElementById('publicarField').value = '1';
      form.submit();
    });
    document.getElementById('btnGuardar')?.addEventListener('click', ()=>{
      document.getElementById('publicarField').value = '';
    });

    // Productos Integration (Datalist)
    let availableProducts = [];
    const productsListHtml = document.getElementById('productsList');

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
        const data = await res.json();
        availableProducts = Array.isArray(data) ? data : (data.data || []);
        
        productsListHtml.innerHTML = availableProducts.map(p => `<option value="${p.nombre}">`).join('');
        initExistingProductSelectors();
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
        }
      }
    });

    document.addEventListener("DOMContentLoaded", function() {
      const fechaInput    = document.getElementById('fecha');
      const vencInput     = document.getElementById('vencimiento');
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
        defaultDate: vencInput.value || null,
        onChange: function(selectedDates) {
          const iso = selectedDates[0] ? selectedDates[0].toISOString().slice(0,10) : '';
          vencInput.value = iso;
          if (vencDisplay) vencDisplay.value = isoToDisplay(iso);
        }
      });

      issueCalendarBtn?.addEventListener('click', () => fpIssue.open());
      dueCalendarBtn?.addEventListener('click', () => fpDue.open());
      fechaDisplay?.addEventListener('focus', () => fpIssue.open());
      vencDisplay?.addEventListener('focus', () => fpDue.open());

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

      refreshRecurrenceUI();
    });

    // ── Auto-guardado de borrador ──────────────────────────────────────
    (function() {
      var facturaId = '{{ $factura['id'] }}';
      if ('{{ $factura['estado'] ?? '' }}' !== 'En borrador') return;

      var indicator = document.createElement('div');
      indicator.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;opacity:0;transition:opacity 0.4s;pointer-events:none;z-index:9999';
      document.body.appendChild(indicator);

      function showIndicator(text, ok) {
        indicator.textContent = text;
        indicator.style.background = ok ? '#f0fdf4' : '#fff1f2';
        indicator.style.color = ok ? '#166534' : '#be123c';
        indicator.style.border = ok ? '1px solid #bbf7d0' : '1px solid #fecdd3';
        indicator.style.opacity = '1';
        clearTimeout(indicator._t);
        indicator._t = setTimeout(function(){ indicator.style.opacity = '0'; }, 2500);
      }

      function collectItems() {
        var items = [];
        document.querySelectorAll('#items .item').forEach(function(row) {
          var desc = row.querySelector('.desc-input');
          var qty  = row.querySelector('.qty-input');
          var price = row.querySelector('.price-input');
          var productId = row.querySelector('.product-id-input');
          if (desc && desc.value.trim()) {
            items.push({
              descripcion: desc.value.trim(),
              producto_id: productId?.value || '',
              detalle: row.querySelector('.product-detail-input')?.value || '',
              cantidad: qty?.value || '',
              precio: price?.value || ''
            });
          }
        });
        return items;
      }

      async function autoGuardar() {
        var payload = {
          cliente:     document.getElementById('clienteField')?.value || '',
          cliente_id:  document.getElementById('clienteIdField')?.value || '',
          proyecto_id: document.getElementById('proyectoIdField')?.value || '',
          fecha:       document.getElementById('fecha')?.value || '',
          vencimiento: document.getElementById('vencimiento')?.value || '',
          moneda:      document.getElementById('monedaField')?.value || 'USD',
          tasa:        document.getElementById('tasaField')?.value || null,
          items:       collectItems(),
        };
        showIndicator('Guardando…', true);
        try {
          var res = await fetch('/api/facturas/' + facturaId + '/borrador', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
            body: JSON.stringify(payload)
          });
          var js = await res.json().catch(function(){ return null; });
          if (js && js.ok) {
            showIndicator('✓ Guardado ' + (js.saved_at || ''), true);
          } else {
            showIndicator('No se pudo guardar', false);
          }
        } catch(e) {
          showIndicator('Sin conexión', false);
        }
      }

      setInterval(autoGuardar, 30000);
    })();
  </script>
@endsection
