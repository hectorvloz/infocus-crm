@extends('layouts.guest')
@section('title', 'Pagar con Wompi')
@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-xl bg-white border border-slate-200 rounded-3xl shadow-xl p-8 space-y-6">
    <h1 class="text-2xl font-extrabold text-slate-900">Pagar con Wompi</h1>
    <p class="text-slate-600">Factura {{ $invoice['numero'] ?? $invoice['id'] }}</p>
    <dl class="space-y-4">
      <div><dt class="text-sm text-slate-500">Saldo de tu factura</dt><dd class="text-xl font-bold">USD {{ number_format($quote['balance_cents'] / 100, 2) }}</dd></div>
      <div><dt class="text-sm text-slate-500">Importe que se cobrará</dt><dd class="text-2xl font-bold">COP {{ number_format($quote['amount_in_cents'] / 100, 2) }}</dd></div>
      <div><dt class="text-sm text-slate-500">Tasa aplicada</dt><dd>1 USD = {{ number_format($quote['rate'], 4) }} COP</dd></div>
    </dl>
    <p class="text-sm text-slate-600">Tu factura y su pago se conservarán en USD. Wompi procesa el cobro en pesos colombianos. La visualización en dólares de Wompi es aproximada y puede diferir de este saldo.</p>
    <p class="text-sm text-slate-500">Esta conversión permite iniciar el pago durante 30 minutos. Si el enlace vence, vuelve a abrir el pago desde la factura.</p>
    <a href="{{ $gatewayUrl }}" class="flex justify-center px-5 py-3 rounded-2xl bg-[#f0fe97] text-slate-900 font-bold">Continuar a Wompi</a>
  </div>
</div>
@endsection
