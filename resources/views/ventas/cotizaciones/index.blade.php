@extends('layouts.app')
@section('title','Cotizaciones')
@section('content')

  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-slate-900">Cotizaciones</h1>
      <p class="text-slate-500 mt-1">Gestión de propuestas comerciales</p>
    </div>
    <div>
      <a href="{{ route('cotizaciones.create') }}" class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-6 py-3 rounded-full font-bold transition-colors shadow-sm flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nueva cotización
      </a>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-soft border overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-medium">
          <tr>
            <th class="px-6 py-4">Número</th>
            <th class="px-6 py-4">Cliente (Prospecto)</th>
            <th class="px-6 py-4">Fecha</th>
            <th class="px-6 py-4 text-right">Total</th>
            <th class="px-6 py-4 text-center">Estado</th>
            <th class="px-6 py-4 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($cotizaciones as $c)
          <tr class="hover:bg-slate-50 transition-colors group">
            <td class="px-6 py-4">
              <a href="{{ route('cotizaciones.show', $c['id']) }}" class="font-bold text-slate-900 hover:text-lime-600 transition-colors">
                {{ $c['numero'] }}
              </a>
            </td>
            <td class="px-6 py-4">
              <div class="font-medium text-slate-900">{{ $c['cliente'] }}</div>
            </td>
            <td class="px-6 py-4 text-slate-600">
              {{ \Illuminate\Support\Carbon::parse($c['fecha'])->format('d M, Y') }}
            </td>
            <td class="px-6 py-4 text-right font-mono text-slate-700">
              ${{ number_format($c['total'], 2) }}
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($c['estado'] === 'Publicada' || $c['estado'] === 'Enviada') ? 'bg-lime-100 text-lime-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $c['estado'] }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1">
                <x-action-button :href="route('cotizaciones.edit', $c['id'])" icon="edit" title="Editar" />
                <x-action-button :href="route('cotizaciones.print', $c['id'])" icon="pdf" title="PDF" />
                <x-action-button href="#" icon="whatsapp" title="Enviar por WhatsApp" />
                <form method="POST" action="{{ route('cotizaciones.destroy', $c['id']) }}" class="inline" onsubmit="return confirm('¿Eliminar esta cotización?');">
                  @csrf
                  @method('DELETE')
                  <x-action-button type="submit" icon="delete" color="rose" title="Eliminar" class="ml-1" />
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center justify-center text-slate-400">
                <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-lg font-medium text-slate-900">No hay cotizaciones</p>
                <p class="text-sm">Crea una nueva cotización para comenzar</p>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
