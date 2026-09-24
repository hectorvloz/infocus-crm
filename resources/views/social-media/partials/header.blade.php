@php
  $socialClientsFile = (new \App\Repositories\FileStore('social_clients.json'))->all();
  $socialClientIds = collect($socialClientsFile)->pluck('id')->filter()->all();

  // Si social_clients está completamente vacío, asumimos que todos los clientes están permitidos por ahora (opcional)
  // Pero como el usuario pidió que solo aparezcan algunos, forzaremos la validación.

  $smAllClients = collect((new \App\Repositories\FileStore('clientes.json'))->all())
    ->map(fn ($client) => [
      'id' => (string) ($client['id'] ?? ''),
      'name' => (string) ($client['nombre'] ?? $client['name'] ?? $client['empresa'] ?? 'Sin Nombre'),
    ])
    ->filter(fn ($client) => $client['id'] !== '')
    ->values()
    ->all();

  $smClients = collect($smAllClients)
    ->filter(fn ($client) => in_array($client['id'], $socialClientIds, true))
    ->values()
    ->all();

  $smSelectedClientId = (string) request('client_id', 'all');
  if ($smSelectedClientId !== 'all' && !collect($smClients)->contains('id', $smSelectedClientId)) {
      $smSelectedClientId = 'all';
  }

  $smTab = (string) request('tab', 'resumen');
@endphp

<style>
  [data-social-media-header] .sm-header-select {
    -webkit-appearance: none;
    appearance: none;
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.4' d='m6 9 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1rem 1rem;
  }
  [data-social-media-header] select.form-select:not([data-app-select-enhanced]) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
    opacity: 0 !important;
    pointer-events: none !important;
  }
  [data-social-media-header] .app-select-trigger {
    min-height: 2.5rem;
    border-radius: 0.9rem;
    padding: 0.55rem 0.8rem;
    font-size: 0.82rem;
    font-weight: 650;
    color: #334155;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
  }
  [data-social-media-header] .app-select-label {
    font-weight: 650;
  }
  [data-social-media-header] .app-select-option {
    font-size: 0.84rem;
    font-weight: 650;
  }
  [data-social-media-header] .app-select-option.is-selected {
    background: #ecfe88;
    color: #0f172a;
  }
</style>

<div data-social-media-header class="flex w-full min-w-0 flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
  <div class="flex min-w-0 items-center gap-3">
    <a href="{{ route('dashboard') }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#ecfe88] text-slate-950 shadow-sm border border-lime-200 hover:bg-[#dff36f]" title="Volver al CRM" aria-label="Volver al CRM">
      <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
    </a>
    <div class="min-w-0">
      <h1 class="truncate text-xl md:text-2xl font-black tracking-tight text-slate-950">Social Media</h1>
      <p class="hidden md:block truncate text-xs md:text-sm font-semibold text-slate-500">Gestiona cuentas, anuncios, leads y contenido por cliente.</p>
    </div>
  </div>

  <div class="flex min-w-0 flex-col gap-2 md:flex-row md:items-center xl:justify-end">
    <form method="GET" action="{{ route('social-media.index') }}" class="relative w-full md:w-[260px]">
      <input type="hidden" name="tab" value="{{ $smTab }}">
      <select name="client_id" class="form-select sm-header-select h-12 w-full rounded-2xl border border-slate-200 px-4 pr-11 text-sm font-black text-slate-700 shadow-sm outline-none transition focus:border-lime-300 focus:ring-4 focus:ring-lime-100" data-add-clients-action="1" onchange="this.form.submit()" aria-label="Seleccionar cliente Social Media">
        <option value="all" @selected($smSelectedClientId === 'all')>Todos los Clientes</option>
        @foreach($smClients as $client)
          <option value="{{ $client['id'] }}" @selected($smSelectedClientId === $client['id'])>{{ $client['name'] }}</option>
        @endforeach
      </select>
    </form>
  </div>
</div>
