@extends('layouts.guest')
@section('title', 'Redirigiendo a pago')
@section('content')
@php
  $gatewayName = $gateway ?? 'la pasarela';
  $targetUrl = $gatewayUrl ?? '#';
  $backUrl = $cancelUrl ?? url()->previous();
  $invoiceNumber = $invoice['numero'] ?? $invoice['id'] ?? '';
@endphp

<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-xl bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden">
    <div class="bg-[#101729] px-8 py-7 text-white">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-xs font-bold uppercase tracking-[0.2em] text-white/75">
        Pago seguro
      </div>
      <h1 class="mt-5 text-3xl font-extrabold tracking-tight">Abriendo {{ $gatewayName }}</h1>
      <p class="mt-2 text-sm text-white/70">
        Estamos preparando el checkout para pagar la factura{{ $invoiceNumber !== '' ? ' '.$invoiceNumber : '' }}.
      </p>
    </div>

    <div class="px-8 py-8 space-y-6">
      <div class="flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-[#f0fe97] flex items-center justify-center text-[#101729]">
          <svg class="w-6 h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"></path>
            <path d="m13 6 6 6-6 6"></path>
          </svg>
        </div>
        <div>
          <div class="font-bold text-slate-900">Redirigiendo a {{ $gatewayName }}</div>
          <div class="text-sm text-slate-500">Si no se abre en unos segundos, usa el boton de abajo.</div>
        </div>
      </div>

      <div class="grid sm:grid-cols-[1fr_auto] gap-3">
        <a href="{{ $targetUrl }}" rel="noopener noreferrer" class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-[#f0fe97] text-[#101729] font-extrabold hover:bg-[#e7fb73] transition-colors">
          Continuar a {{ $gatewayName }}
        </a>
        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-slate-200 bg-white text-slate-600 font-bold hover:bg-slate-50 transition-colors">
          Volver
        </a>
      </div>
    </div>
  </div>
</div>

<script>
  window.addEventListener('load', () => {
    window.setTimeout(() => {
      window.location.replace(@json($targetUrl));
    }, 500);
  });
</script>
@endsection
