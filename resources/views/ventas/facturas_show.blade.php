@extends('layouts.app')
@section('title','Factura')
@section('content')
  <div class="max-w-4xl mx-auto">
  <div class="mb-3 flex items-center justify-between gap-2 text-xs flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
    <a href="{{ route('facturas.print',$factura['id']) }}" title="PDF" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-lime-300 text-slate-900 shadow">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v10"/><path d="M8 9l4 4 4-4"/><path d="M4 17v3h16v-3"/></svg>
      <span class="hidden sm:inline">PDF</span>
    </a>
    <div class="relative">
      <button id="sendBtn" type="button" title="Enviar" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
        <span class="hidden sm:inline">Enviar</span>
      </button>
      <div id="sendMenu" class="absolute left-0 mt-2 w-44 rounded-2xl border bg-white shadow p-2 hidden z-10">
        <a href="javascript:void(0)" id="sendEmailBtn" class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-neutral-50 text-sm">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18v14H3z"/><path d="m3 7 9 6 9-6"/></svg>
          <span>Correo</span>
        </a>
        <a href="{{ $waTo }}" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-neutral-50 text-sm">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
          <span>WhatsApp</span>
        </a>
      </div>
    </div>
    <button id="payBtn" title="Registrar pago" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
      <span class="hidden sm:inline">Pagar</span>
    </button>
    <button id="dupFromShow" title="Duplicar factura" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 8h12v12H8z"/><path d="M4 4h12v12H4z"/></svg>
      <span class="hidden sm:inline">Duplicar</span>
    </button>
    <a href="{{ route('facturas.edit',$factura['id']) }}" id="editBtn" title="Editar factura" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
      <span class="hidden sm:inline">Editar</span>
    </a>
    </div>
    <form method="POST" action="{{ route('facturas.destroy',$factura['id']) }}" class="inline">
      @csrf
      @method('DELETE')
      <button onclick="return confirm('¿Eliminar esta factura?')" title="Eliminar" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-rose-200 bg-white text-rose-700 hover:bg-rose-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/></svg>
        <span class="hidden sm:inline">Eliminar</span>
      </button>
    </form>
  </div>
  <div class="bg-white rounded-2xl shadow border p-6">
    <div class="flex items-start justify-between">
      <div class="text-sm md:text-base">
        <div class="text-2xl font-extrabold text-lime-600">Factura {{ $factura['numero'] }}</div>
        <div class="text-slate-600 flex items-center gap-2 flex-wrap">
          <span class="font-bold text-slate-900">Cliente:</span>
          @if(!empty($factura['cliente_id']))
            <a class="no-underline font-extrabold text-lime-600" href="{{ route('clientes.show',$factura['cliente_id']) }}">{{ $factura['cliente'] }}</a>
          @else
            <span class="font-extrabold text-lime-600">{{ $factura['cliente'] }}</span>
          @endif
          @if(!empty($factura['cliente_id']))
            <a href="{{ route('clientes.edit',$factura['cliente_id']) }}" class="px-2 py-1 rounded-full border text-[11px]">Editar cliente</a>
          @endif
        </div>
        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-xs md:text-sm text-slate-600">
          @if(($invoiceFields['nit'] ?? true) && !empty($cliente['nit']))<div><span class="font-bold text-slate-900">NIT:</span> {{ $cliente['nit'] }}</div>@endif
          @if(($invoiceFields['telefono'] ?? true) && !empty($cliente['contacto_telefono']))<div><span class="font-bold text-slate-900">Teléfono:</span> {{ $cliente['contacto_telefono'] }}</div>@endif
          @if(($invoiceFields['email'] ?? true) && !empty($cliente['contacto_email']))<div><span class="font-bold text-slate-900">Email:</span> {{ $cliente['contacto_email'] }}</div>@endif
          @if(($invoiceFields['direccion'] ?? true) && !empty($cliente['direccion']))<div><span class="font-bold text-slate-900">Dirección:</span> {{ $cliente['direccion'] }}</div>@endif
        </div>
        @if(!empty($factura['proyecto_id']) && !empty($factura['cliente_id']))
        <div class="text-slate-600 mt-2"><span class="font-bold text-slate-900">Proyecto:</span> <a class="no-underline" href="/proyectos?cliente_id={{ $factura['cliente_id'] }}">{{ $factura['proyecto'] ?? '—' }}</a></div>
        @endif
        <div class="text-slate-600"><span class="font-bold text-slate-900">Fecha:</span> {{ \Illuminate\Support\Carbon::parse($factura['fecha'])->format('d/m/Y') }}</div>
        @if(!empty($factura['vencimiento']))<div class="text-slate-600"><span class="font-bold text-slate-900">Vence:</span> {{ \Illuminate\Support\Carbon::parse($factura['vencimiento'])->format('d/m/Y') }}</div>@endif
      </div>
      <div class="hidden md:block text-xs text-slate-400">Acciones</div>
    </div>
    <div class="mt-6 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-2 pr-4">Descripción</th>
            <th class="py-2 pr-4 text-right">Cantidad</th>
            <th class="py-2 pr-4 text-right">Precio</th>
            <th class="py-2 pr-4 text-right">Importe</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200">
          @foreach(($factura['items'] ?? []) as $it)
          @php
            $hasQty = array_key_exists('cantidad', $it) && $it['cantidad'] !== null && $it['cantidad'] !== '';
            $hasPrice = array_key_exists('precio', $it) && $it['precio'] !== null && $it['precio'] !== '';
            $lineTotal = (float) ($it['cantidad'] ?? 0) * (float) ($it['precio'] ?? 0);
          @endphp
          <tr>
            <td class="py-2 pr-4">{{ $it['descripcion'] }}</td>
            <td class="py-2 pr-4 text-right">{{ $hasQty ? $it['cantidad'] : '' }}</td>
            <td class="py-2 pr-4 text-right tabular-nums">{{ $hasPrice ? '$'.number_format((float) $it['precio'], 2) : '' }}</td>
            <td class="py-2 pr-4 text-right tabular-nums">{{ ($hasQty && $hasPrice) ? '$'.number_format($lineTotal, 2) : '' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @php
      $items = $factura['items'] ?? [];
      $subtotalCalc = collect($items)->sum(fn($i)=> ($i['cantidad'] ?? 0) * ($i['precio'] ?? 0));
      $subtotal = $factura['subtotal'] ?? $subtotalCalc;
      $taxRate = (float) ($factura['tax_rate'] ?? 0);
      $impuestos = round($subtotal * ($taxRate / 100), 2);
      $total = round($subtotal + $impuestos, 2);
      $totalBase = $factura['total_base'] ?? null;
      $monedaCliente = $factura['moneda'] ?? null;
      $settingsBase = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
      $baseCurrency = $settingsBase['base_currency'] ?? 'USD';
      $tieneConversion = $totalBase && $monedaCliente && $monedaCliente !== $baseCurrency;
    @endphp
    <div class="mt-6 flex justify-end pr-4">
      <div class="w-72">
        <div class="grid grid-cols-2 gap-y-2 text-sm">
          <div class="text-slate-600">Subtotal</div>
          <div class="text-right tabular-nums">{{ format_currency($subtotal, $monedaCliente) }}</div>
          <div class="text-slate-600">Impuestos ({{ rtrim(rtrim(number_format($taxRate, 2, ',', '.'), '0'), ',') }}%)</div>
          <div class="text-right tabular-nums">{{ format_currency($impuestos, $monedaCliente) }}</div>
        </div>
        <div class="mt-2 flex items-center justify-between font-bold text-lg">
          <span>Total{{ $tieneConversion ? ' cliente' : '' }}</span>
          <span class="px-3 py-1 rounded-lg tabular-nums" style="background:#ecfe88">{{ format_currency($total, $monedaCliente) }}</span>
        </div>
        @if($tieneConversion)
        <div class="mt-1 flex items-center justify-between font-bold text-lg border-t pt-2">
          <span>Total</span>
          <span class="px-3 py-1 rounded-lg tabular-nums bg-lime-200">{{ format_currency($totalBase, $baseCurrency) }}</span>
        </div>
        @endif
      </div>
    </div>
    <div class="mt-6">
      @php
        $pagos = collect($factura['pagos'] ?? [])->sortBy('fecha')->values();
        $abonado = (float) $pagos->sum(fn($p) => (float)($p['monto'] ?? 0));
        $saldo = max(0, round((float)($factura['total'] ?? 0) - $abonado, 2));
      @endphp
      <div class="flex items-center justify-between mb-3">
        <div class="font-semibold">Pipeline de pagos</div>
        <div class="text-xs text-slate-500">{{ $pagos->count() }} movimientos</div>
      </div>
      <div class="grid grid-cols-2 gap-3 mb-3">
        <div class="rounded-xl border bg-emerald-50 p-3">
          <div class="text-xs text-emerald-700">Abonado</div>
          <div class="font-bold text-emerald-800">{{ format_currency($abonado, $factura['moneda'] ?? null) }}</div>
        </div>
        <div class="rounded-xl border bg-rose-50 p-3">
          <div class="text-xs text-rose-700">Debe</div>
          <div class="font-bold text-rose-700">{{ format_currency($saldo, $factura['moneda'] ?? null) }}</div>
        </div>
      </div>

      <div class="rounded-2xl border bg-neutral-50 p-4">
        @if($pagos->isEmpty())
          <div class="text-sm text-slate-500">Sin abonos registrados.</div>
        @else
          <div class="space-y-1">
            @foreach($pagos as $idx => $p)
              <div class="relative pl-8 pb-4 {{ $loop->last ? 'pb-0' : '' }}">
                @if(!$loop->last)
                  <span class="absolute left-[9px] top-5 h-[calc(100%-6px)] w-px bg-slate-200"></span>
                @endif
                <span class="absolute left-0 top-1.5 inline-block h-5 w-5 rounded-full border-2 border-emerald-500 bg-emerald-100"></span>
                <div class="flex items-start justify-between gap-4 rounded-xl bg-white border p-3">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">Abono {{ $idx + 1 }}</div>
                    <div class="text-xs text-slate-500">
                      {{ !empty($p['fecha']) ? \Illuminate\Support\Carbon::parse($p['fecha'])->format('d/m/Y') : 'Sin fecha' }}
                      • {{ $p['metodo'] ?? '—' }}
                    </div>
                    @if(!empty($p['nota']))
                      <div class="text-xs text-slate-400 mt-1">{{ $p['nota'] }}</div>
                    @endif
                  </div>
                  <div class="text-sm font-bold text-emerald-700 whitespace-nowrap">{{ format_currency($p['monto'] ?? 0, $factura['moneda'] ?? null) }}</div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
  </div>
  <div id="facturaMeta" class="hidden"
    data-id="{{ $factura['id'] }}"
    data-index="{{ route('facturas.index') }}"
    data-email="{{ $clienteEmail }}"
    data-estado="{{ $factura['estado'] ?? '' }}"></div>
  <!-- Modal enviar por correo -->
  <div id="sendEmailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-5 relative">
      <button id="closeSendEmail" class="absolute right-3 top-3 text-slate-500">✕</button>
      <div class="text-lg font-bold mb-1">Enviar factura por correo</div>
      <div class="text-xs text-slate-500 mb-4">Se adjuntará el PDF de la factura automáticamente.</div>
      <div id="sendEmailError" class="hidden mb-3 px-3 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm"></div>
      <div id="sendEmailSuccess" class="hidden mb-3 px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">¡Correo enviado correctamente!</div>
      <div class="grid gap-3">
        <div>
          <label class="text-sm font-semibold text-slate-700">Destinatario</label>
          <input id="sendEmailTo" type="email" class="form-input w-full mt-1" placeholder="correo@ejemplo.com">
        </div>
        <div class="flex justify-end gap-2 mt-1">
          <button id="doSendEmail" class="px-4 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition-colors">Enviar</button>
        </div>
      </div>
    </div>
  </div>
  <div id="payModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-5 relative">
      <button id="closePay" class="absolute right-3 top-3 text-slate-500">✕</button>
      <div class="text-lg font-bold mb-3">Registrar pago</div>
      <div class="grid gap-3">
        <div>
          <label class="text-sm font-semibold text-slate-700">Monto</label>
          <div class="relative">
            <span id="payCurrencySymbol" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">{{ currency_symbol($factura['moneda'] ?? null) }}</span>
            <input id="payAmount" type="text" value="{{ number_format(($factura['total'] ?? 0),2,',','.') }}" class="form-input w-full pl-8" placeholder="Ej. 100.00">
          </div>
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Método</label>
          <select id="payMethod" class="form-select w-full">
            @foreach(($paymentMethods ?? ['Transferencia', 'Efectivo', 'Tarjeta', 'Otro']) as $method)
              <option value="{{ $method }}">{{ $method }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Fecha de pago</label>
          <input id="payDate" type="text" value="{{ date('Y-m-d') }}" class="form-input w-full">
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700">Nota</label>
          <input id="payNote" class="form-input w-full" placeholder="Referencia o comentario">
        </div>
        <div class="flex justify-end gap-2 mt-2">
          <button id="savePay" class="px-4 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold shadow-sm hover:bg-lime-400 transition-colors">Guardar Pago</button>
        </div>
      </div>
    </div>
  </div>
  <script>
    (function(){
      var meta = document.getElementById('facturaMeta');
      var facturaId = meta ? meta.getAttribute('data-id') : '';
      var csrf = window.csrfToken;
      var indexUrl = meta ? meta.getAttribute('data-index') : '';
      var isDraft = (meta ? meta.getAttribute('data-estado') : '') === 'En borrador';
      var payModal = document.getElementById('payModal');

      var dup = document.getElementById('dupFromShow');
      if (dup) {
        dup.addEventListener('click', function(){
          if (dup.disabled) return;
          dup.disabled = true;
          fetch('/api/facturas/duplicar/' + facturaId, { method:'POST', headers:{'X-CSRF-TOKEN': csrf }})
            .then(function(r){ return r.json().catch(function(){ return null; }); })
            .then(function(js){
              if (js && js.edit_url) {
                location.href = js.edit_url;
              } else if (js && js.item && js.item.id) {
                location.href = '/facturas/' + js.item.id + '/editar';
              } else {
                dup.disabled = false;
              }
            })
            .catch(function(){ dup.disabled = false; });
        });
      }

      var sendBtn = document.getElementById('sendBtn');
      var sendMenu = document.getElementById('sendMenu');
      if (sendBtn && sendMenu) {
        sendBtn.addEventListener('click', function(e){
          e.stopPropagation();
          sendMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', function(e){
          if (!sendMenu.contains(e.target) && !sendBtn.contains(e.target)) sendMenu.classList.add('hidden');
        });
      }

      // Modal enviar correo
      var sendEmailModal = document.getElementById('sendEmailModal');
      var sendEmailBtn = document.getElementById('sendEmailBtn');
      var closeSendEmail = document.getElementById('closeSendEmail');
      var sendEmailTo = document.getElementById('sendEmailTo');
      var doSendEmail = document.getElementById('doSendEmail');
      var sendEmailError = document.getElementById('sendEmailError');
      var sendEmailSuccess = document.getElementById('sendEmailSuccess');
      var clientEmail = meta ? meta.getAttribute('data-email') : '';

      if (sendEmailBtn && sendEmailModal) {
        sendEmailBtn.addEventListener('click', function(){
          sendMenu.classList.add('hidden');
          sendEmailTo.value = clientEmail || '';
          sendEmailError.classList.add('hidden');
          sendEmailSuccess.classList.add('hidden');
          sendEmailModal.classList.remove('hidden');
          sendEmailModal.classList.add('flex');
        });
        closeSendEmail.addEventListener('click', function(){
          sendEmailModal.classList.add('hidden');
          sendEmailModal.classList.remove('flex');
        });
        sendEmailModal.addEventListener('click', function(e){
          if (e.target === sendEmailModal) {
            sendEmailModal.classList.add('hidden');
            sendEmailModal.classList.remove('flex');
          }
        });
        doSendEmail.addEventListener('click', function(){
          var email = sendEmailTo.value.trim();
          if (!email) { sendEmailError.textContent = 'Ingresa un correo válido.'; sendEmailError.classList.remove('hidden'); return; }
          doSendEmail.disabled = true;
          doSendEmail.textContent = 'Enviando…';
          sendEmailError.classList.add('hidden');
          sendEmailSuccess.classList.add('hidden');
          fetch('/api/facturas/enviar', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ id: facturaId, to: email })
          })
          .then(function(r){
            return r.text().then(function(text){
              var payload = null;
              try { payload = text ? JSON.parse(text) : null; } catch(e) {}
              if (!r.ok || (payload && payload.ok === false)) {
                var message = (payload && (payload.error || payload.message))
                  ? (payload.error || payload.message)
                  : (text ? text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 240) : 'Error al enviar.');
                throw new Error(message || 'Error al enviar.');
              }
              return payload || {};
            });
          })
          .then(function(js){
            doSendEmail.disabled = false;
            doSendEmail.textContent = 'Enviar';
            if (js && js.ok) {
              sendEmailSuccess.textContent = js.message || '¡Correo enviado correctamente!';
              sendEmailSuccess.classList.remove('hidden');
            } else {
              sendEmailError.textContent = (js && js.error) ? js.error : 'Error al enviar.';
              sendEmailError.classList.remove('hidden');
            }
          })
          .catch(function(error){
            doSendEmail.disabled = false;
            doSendEmail.textContent = 'Enviar';
            sendEmailError.textContent = error && error.message ? error.message : 'Error de conexión.';
            sendEmailError.classList.remove('hidden');
          });
        });
      }

      var payBtn = document.getElementById('payBtn');
      if (payBtn && payModal) {
        payBtn.addEventListener('click', function(){
          payModal.classList.remove('hidden');
          payModal.classList.add('flex');
        });
      }
      document.getElementById('closePay')?.addEventListener('click', function(){ payModal.classList.add('hidden'); payModal.classList.remove('flex'); });
      payModal?.addEventListener('click', function(e){ if(e.target===payModal){ payModal.classList.add('hidden'); payModal.classList.remove('flex'); }});
      document.getElementById('savePay')?.addEventListener('click', function(){
        var btn = document.getElementById('savePay');
        if (btn.disabled) return;
        btn.disabled = true;
        var montoRaw = (document.getElementById('payAmount').value || '').replace(/\./g,'').replace(',','.');
        var body = {
          id: facturaId,
          monto: montoRaw,
          metodo: document.getElementById('payMethod').value,
          fecha_pago: document.getElementById('payDate').value,
          nota: document.getElementById('payNote').value
        };
        fetch('/api/facturas/pagar', { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN': window.csrfToken }, body: JSON.stringify(body) })
          .then(function(){ location.reload(); })
          .catch(function(){ btn.disabled = false; });
      });

      if (isDraft) {
        // acciones principales siguen disponibles en borrador
      }

      if (window.flatpickr) {
        flatpickr("#payDate", {
          altInput: true,
          altFormat: "d/m/Y",
          dateFormat: "Y-m-d",
          locale: "es",
          onOpen: function(selectedDates, dateStr, instance) {
            instance.calendarContainer.classList.add("airline-calendar");
          }
        });
      }
    })();
  </script>
@endsection
