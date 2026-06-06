@extends('layouts.app')
@section('title','Cotización')
@section('content')
  <div class="mb-3 flex items-center justify-between gap-2 text-xs flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('cotizaciones.print',$cotizacion['id']) }}" title="PDF" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-lime-300 text-slate-900 shadow">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18h12v4H6z"/><path d="M6 14h12"/></svg>
        <span class="hidden sm:inline">PDF</span>
      </a>
      <a href="{{ route('cotizaciones.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white hover:bg-slate-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        <span>Volver</span>
      </a>
      <a href="{{ route('cotizaciones.edit',$cotizacion['id']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white hover:bg-slate-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        <span>Editar</span>
      </a>
      <form method="POST" action="{{ route('cotizaciones.destroy',$cotizacion['id']) }}" class="inline">
        @csrf
        @method('DELETE')
        <button onclick="return confirm('¿Eliminar esta cotización?')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-rose-200 bg-white text-rose-700 hover:bg-rose-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/></svg>
          <span>Eliminar</span>
        </button>
      </form>
    </div>
  </div>

  <div class="max-w-4xl bg-white rounded-2xl shadow border p-6">
    <div class="flex items-start justify-between">
      <div class="text-sm md:text-base">
        <div class="text-2xl font-extrabold text-lime-600">Cotización {{ $cotizacion['numero'] }}</div>
        <div class="text-slate-600 mt-1">
          <span class="font-bold text-slate-900">Cliente (Prospecto):</span>
          <span class="font-medium text-slate-800">{{ $cotizacion['cliente'] }}</span>
        </div>
        <div class="text-slate-600 mt-1">
          <span class="font-bold text-slate-900">Fecha:</span> {{ \Illuminate\Support\Carbon::parse($cotizacion['fecha'])->format('d/m/Y') }}
        </div>
        @if(!empty($cotizacion['vencimiento']))
        <div class="text-slate-600 mt-1">
          <span class="font-bold text-slate-900">Vence:</span> {{ \Illuminate\Support\Carbon::parse($cotizacion['vencimiento'])->format('d/m/Y') }}
        </div>
        @endif
        <div class="mt-2">
          <span class="px-2 py-1 rounded-full text-xs font-bold {{ ($cotizacion['estado'] === 'Publicada' || $cotizacion['estado'] === 'Enviada') ? 'bg-lime-100 text-lime-800' : 'bg-slate-100 text-slate-600' }}">
            {{ $cotizacion['estado'] }}
          </span>
        </div>
      </div>
    </div>

    <div class="mt-8 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-100">
            <th class="py-2 pr-4 font-medium">Descripción</th>
            <th class="py-2 pr-4 text-right font-medium">Cantidad</th>
            <th class="py-2 pr-4 text-right font-medium">Precio</th>
            <th class="py-2 pr-4 text-right font-medium">Importe</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          @foreach(($cotizacion['items'] ?? []) as $it)
          <tr>
            <td class="py-3 pr-4 text-slate-700">{{ $it['descripcion'] }}</td>
            <td class="py-3 pr-4 text-right text-slate-600">{{ $it['cantidad'] }}</td>
            <td class="py-3 pr-4 text-right tabular-nums text-slate-600">${{ number_format($it['precio'], 2) }}</td>
            <td class="py-3 pr-4 text-right tabular-nums font-medium text-slate-900">${{ number_format($it['cantidad'] * $it['precio'], 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-6 flex justify-end pr-4">
      <div class="w-72">
        <div class="grid grid-cols-2 gap-y-2 text-sm border-t border-slate-100 pt-4">
          <div class="text-slate-600">Subtotal</div>
          <div class="text-right tabular-nums text-slate-900">${{ number_format($cotizacion['subtotal'] ?? 0, 2) }}</div>
          <div class="text-slate-600">Impuestos (16%)</div>
          <div class="text-right tabular-nums text-slate-900">${{ number_format($cotizacion['impuestos'] ?? 0, 2) }}</div>
        </div>
        <div class="mt-3 flex items-center justify-between font-bold text-lg">
          <span>Total</span>
          <span class="px-3 py-1 rounded-lg tabular-nums bg-[#ecfe88] text-slate-900 shadow-sm">${{ number_format($cotizacion['total'] ?? 0, 2) }}</span>
        </div>
      </div>
    </div>
  </div>
@endsection
