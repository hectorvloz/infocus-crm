@extends('layouts.guest')
@section('title','Detalle de Factura')
@section('content')
@php
  $isPublicInvoice = $isPublicInvoice ?? false;
  $invoiceTotal = (float) ($invoice['total'] ?? 0);
  $pagos = $invoice['pagos'] ?? [];
  $totalPagado = round(collect($pagos)->sum(fn($p) => (float)($p['monto'] ?? 0)), 2);
  $saldoPendiente = max(0, round($invoiceTotal - $totalPagado, 2));
  $isFullyPaid = ($invoice['estado'] ?? '') === 'Pagada' || $saldoPendiente <= 0.01;
  $isPartial = !$isFullyPaid && $totalPagado > 0.01;
  $isOverdue = ($invoice['estado'] ?? '') === 'Vencida';

  if ($isFullyPaid) {
    $statusText = 'Pagada';
    $statusClass = 'bg-emerald-100 text-emerald-700';
  } elseif ($isOverdue) {
    $statusText = 'Vencida';
    $statusClass = 'bg-rose-100 text-rose-700';
  } elseif ($isPartial) {
    $statusText = 'Parcial';
    $statusClass = 'bg-amber-100 text-amber-700';
  } else {
    $statusText = 'Pendiente';
    $statusClass = 'bg-sky-100 text-sky-700';
  }

  $downloadUrl = $isPublicInvoice
    ? ($publicPdfUrl ?? route('facturas.public.pdf', $invoice['id']))
    : (!empty($useTokenLinks)
      ? route('portal.invoice.pdf', ['id' => $client['id'], 'token' => $token, 'invoiceId' => $invoice['id']])
      : route('portal.auth.invoice.pdf', ['invoiceId' => $invoice['id']]));

  $payUrl = $isPublicInvoice
    ? ($publicPayUrl ?? null)
    : (!empty($useTokenLinks)
      ? route('portal.pay.checkout', ['id' => $client['id'], 'token' => $token, 'invoiceId' => $invoice['id']])
      : route('portal.auth.pay.checkout', ['invoiceId' => $invoice['id']]));

  $clientName = $client['empresa'] ?? $client['nombre'] ?? $invoice['cliente'] ?? 'Cliente';
@endphp
<div class="min-h-screen bg-slate-50 flex flex-col">
  <header class="bg-[#101729] border-b border-[#101729] sticky top-0 z-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 gap-3">
        @if(!$isPublicInvoice)
        <a href="{{ !empty($useTokenLinks) ? route('portal.dashboard', ['id'=>$client['id'], 'token'=>$token]) : route('portal.auth.dashboard') }}" class="flex items-center gap-2 text-white/80 hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          <span class="text-sm font-bold">Volver</span>
        </a>
        @else
        <div class="text-sm font-bold text-white/80">Factura en linea</div>
        @endif

        <div class="font-bold text-white">Factura {{ $invoice['numero'] }}</div>

        <div class="flex items-center gap-2">
          <a href="{{ $downloadUrl }}" class="px-4 py-2 rounded-full bg-white/10 border border-white/20 text-white text-xs sm:text-sm font-bold hover:bg-white/20 transition-colors">Descargar factura</a>
          @if(!$isFullyPaid && $payUrl)
            <a href="{{ $payUrl }}" class="px-4 py-2 rounded-full bg-[#f0fe97] text-slate-900 text-xs sm:text-sm font-bold hover:bg-[#e7fb73] transition-colors">Pagar factura</a>
          @else
            <button type="button" disabled class="px-4 py-2 rounded-full bg-slate-200 text-slate-400 text-xs sm:text-sm font-bold cursor-not-allowed">Pagar factura</button>
          @endif
        </div>
      </div>
    </div>
  </header>

  <main class="flex-1 py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      @if(session('msg_ok'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
          {{ session('msg_ok') }}
        </div>
      @endif

      @if($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
          {{ $errors->first('pago') ?: $errors->first() }}
        </div>
      @endif
      
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
        <!-- Invoice Header -->
        <div class="p-8 border-b border-[#1f2a44] bg-[#101729] flex justify-between items-start">
           <div>
             @if(!empty($settings['logo']))
               <img src="{{ $settings['logo'] }}" class="h-12 object-contain mb-4 rounded">
             @else
               <div class="text-2xl font-extrabold text-white mb-4">{{ $settings['company_name'] ?? 'Mi Empresa' }}</div>
             @endif
             <div class="text-sm text-white/75 whitespace-pre-line">{{ $settings['company_address'] ?? '' }}</div>
           </div>
           <div class="text-right">
             <div class="text-sm text-white/80 font-bold uppercase tracking-wider mb-1">Total a Pagar</div>
             <div class="text-4xl font-extrabold text-white tracking-tight">{{ format_currency($invoiceTotal, $invoice['moneda'] ?? null) }}</div>
             <div class="mt-2">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide {{ $statusClass }}">{{ $statusText }}</span>
             </div>
           </div>
        </div>

        <!-- Meta -->
        <div class="grid grid-cols-2 gap-8 p-8 border-b border-slate-100">
           <div>
             <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Facturar a</div>
             <div class="font-bold text-slate-900">{{ $clientName }}</div>
             <div class="text-sm text-slate-500 mt-1">{{ $client['direccion'] ?? '' }}</div>
             <div class="text-sm text-slate-500">{{ $client['ciudad'] ?? '' }} {{ $client['pais'] ?? '' }}</div>
           </div>
           <div class="grid grid-cols-2 gap-4">
             <div>
               <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Fecha</div>
               <div class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($invoice['fecha'])->format('d M, Y') }}</div>
             </div>
             <div>
               <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Vence</div>
               <div class="font-medium text-slate-800">{{ $invoice['vencimiento'] ? \Carbon\Carbon::parse($invoice['vencimiento'])->format('d M, Y') : '—' }}</div>
             </div>
           </div>
        </div>

        <!-- Items -->
        <div class="p-8">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-slate-400 border-b border-slate-100">
                <th class="pb-3 font-bold uppercase text-xs w-1/2">Descripción</th>
                <th class="pb-3 font-bold uppercase text-xs text-center">Cant.</th>
                <th class="pb-3 font-bold uppercase text-xs text-right">Precio</th>
                <th class="pb-3 font-bold uppercase text-xs text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              @foreach($invoice['items'] as $item)
                @php
                  $hasQty = array_key_exists('cantidad', $item) && $item['cantidad'] !== null && $item['cantidad'] !== '';
                  $hasPrice = array_key_exists('precio', $item) && $item['precio'] !== null && $item['precio'] !== '';
                  $lineTotal = (float) ($item['cantidad'] ?? 0) * (float) ($item['precio'] ?? 0);
                @endphp
                <tr>
                  <td class="py-4 font-medium text-slate-800">{{ $item['descripcion'] }}</td>
                  <td class="py-4 text-center text-slate-500">{{ $hasQty ? $item['cantidad'] : '' }}</td>
                  <td class="py-4 text-right text-slate-500">{{ $hasPrice ? format_currency((float) $item['precio'], $invoice['moneda'] ?? null) : '' }}</td>
                  <td class="py-4 text-right font-bold text-slate-800">{{ ($hasQty && $hasPrice) ? format_currency($lineTotal, $invoice['moneda'] ?? null) : '' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
          
          <div class="flex justify-end mt-6">
            <div class="w-1/2 space-y-2">
              <div class="flex justify-between text-sm text-slate-500">
                <span>Subtotal</span>
                <span>{{ format_currency($invoice['subtotal'] ?? 0, $invoice['moneda'] ?? null) }}</span>
              </div>
              <div class="flex justify-between text-sm text-slate-500">
                <span>Impuestos (16%)</span>
                <span>{{ format_currency($invoice['impuestos'] ?? 0, $invoice['moneda'] ?? null) }}</span>
              </div>
              <div class="flex justify-between text-lg font-extrabold text-slate-900 pt-4 border-t border-slate-100">
                <span>Total</span>
                <span>{{ format_currency($invoiceTotal, $invoice['moneda'] ?? null) }}</span>
              </div>
              @if($totalPagado > 0)
              <div class="flex justify-between text-sm text-emerald-700">
                <span>Pagado</span>
                <span>- {{ format_currency($totalPagado, $invoice['moneda'] ?? null) }}</span>
              </div>
              @endif
              @if(!$isFullyPaid)
              <div class="flex justify-between text-sm font-bold text-rose-700">
                <span>Saldo pendiente</span>
                <span>{{ format_currency($saldoPendiente, $invoice['moneda'] ?? null) }}</span>
              </div>
              @endif
            </div>
          </div>
        </div>
        
        <!-- Footer / Payment Info -->
        @if(!empty($settings['invoice_footer']))
          <div class="bg-[#f0fe97] p-8 border-t border-[#e4f18d] text-sm text-[#101729]">
            <div class="font-bold text-[#101729] mb-2">Información de Pago / Notas</div>
            <div class="whitespace-pre-line">{{ $settings['invoice_footer'] }}</div>
        </div>
        @endif
      </div>
      
      

    </div>
  </main>
</div>
@endsection
