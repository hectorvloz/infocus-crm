@extends('layouts.guest')

@section('title', 'Autorizar asistente · Infocus')

@section('content')
@php($isAdmin = in_array(strtolower((string) (auth()->user()->role ?? '')), ['admin', 'super_admin'], true))
<main class="min-h-screen flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Conectar asistente de IA</p>
    <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $client->name }} quiere acceder a tu Infocus</h1>

    @if ($isAdmin)
      <p class="mt-4 text-sm text-slate-600">Si lo autorizas, podrá con tu usuario ({{ auth()->user()->email }}):</p>
      <ul class="mt-3 space-y-2 text-sm text-slate-700">
        <li>• Consultar clientes, leads, facturas, gastos, proyectos, reuniones y contratos.</li>
        <li>• Crear clientes, leads, tareas, gastos y borradores de facturas y contratos.</li>
        <li>• Preparar correos, envíos de facturas y pagos. <strong>Nada de esto se ejecuta sin que tú lo apruebes en el chat.</strong></li>
        <li>• No podrá borrar nada.</li>
      </ul>
      <p class="mt-4 text-xs text-slate-500">Puedes revocar el acceso cuando quieras en Configuración → MCP.</p>

      <div class="mt-6 flex gap-3">
        <form method="post" action="{{ route('passport.authorizations.approve') }}" class="flex-1">
          @csrf
          <input type="hidden" name="state" value="{{ $request->state }}">
          <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
          <input type="hidden" name="auth_token" value="{{ $authToken }}">
          <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-700">Autorizar</button>
        </form>
        <form method="post" action="{{ route('passport.authorizations.deny') }}" class="flex-1">
          @csrf
          @method('DELETE')
          <input type="hidden" name="state" value="{{ $request->state }}">
          <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
          <input type="hidden" name="auth_token" value="{{ $authToken }}">
          <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
        </form>
      </div>
    @else
      <p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">El MCP de Infocus es solo para administradores. Inicia sesión con una cuenta de administrador.</p>
      <form method="post" action="{{ route('passport.authorizations.deny') }}" class="mt-6">
        @csrf
        @method('DELETE')
        <input type="hidden" name="state" value="{{ $request->state }}">
        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Volver</button>
      </form>
    @endif
  </div>
</main>
@endsection
