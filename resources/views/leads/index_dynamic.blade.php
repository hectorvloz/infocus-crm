@extends('layouts.app')
@section('title','Leads')
@section('content')
  <div class="bg-white rounded-2xl shadow border p-6">
    <div class="flex items-center justify-between mb-4">
      <div class="text-xl font-bold">Leads</div>
      <a href="{{ route('leads.create') }}" class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Nuevo lead</a>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="py-2 pr-4">Nombre</th>
            <th class="py-2 pr-4">Etapa</th>
            <th class="py-2 pr-4">Valor</th>
            <th class="py-2 pr-4">Origen</th>
            <th class="py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($leads as $lead)
          <tr>
            <td class="py-2 pr-4 font-medium">{{ $lead['nombre'] }}</td>
            <td class="py-2 pr-4">{{ $lead['etapa'] }}</td>
            <td class="py-2 pr-4">${{ number_format($lead['valor'] ?? 0, 2) }}</td>
            <td class="py-2 pr-4">{{ $lead['origen'] ?? '—' }}</td>
            <td class="py-2 text-right">
              <a href="{{ route('leads.edit',$lead['id']) }}" class="inline-grid place-content-center w-9 h-9 rounded-full border hover:bg-neutral-50 mr-1" title="Editar">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                <span class="sr-only">Editar</span>
              </a>
              <form action="{{ route('leads.destroy',$lead['id']) }}" method="POST" class="inline">
                @csrf @method('DELETE')
                <button class="inline-grid place-content-center w-9 h-9 rounded-full border text-rose-700 hover:bg-rose-50" title="Eliminar">
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6l-1 14H6L5 6"/><path d="M8 6V4h8v2"/></svg>
                  <span class="sr-only">Eliminar</span>
                </button>
              </form>
            </td>
          </tr>
          @empty
          <tr><td class="py-6 text-slate-500">Aún no hay leads.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
