@extends('layouts.app')
@section('title','Importar Leads')
@section('content')
  <div class="max-w-2xl bg-white rounded-2xl shadow border p-6">
    <div class="text-xl font-bold mb-4">Importar Leads (CSV)</div>
    <p class="text-sm text-slate-600 mb-4">Formato de columnas sugerido: Nombre, Email, Telefono, Etapa, Valor, Origen, Notas, Recordatorio</p>
    <form method="POST" action="{{ route('leads.import.store') }}" enctype="multipart/form-data" class="flex items-center gap-3">
      @csrf
      <input type="file" name="csv" accept=".csv" class="rounded-full border px-3 py-2" required>
      <button class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Importar</button>
      <a href="{{ route('leads.export') }}" class="px-4 py-2 rounded-full border font-semibold">Descargar ejemplo</a>
    </form>
  </div>
@endsection
