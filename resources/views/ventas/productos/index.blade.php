@extends('layouts.app')
@section('title','Productos y Servicios')
@section('content')

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Rendimiento <span class="text-slate-400">del negocio</span></h1>
        <span class="bg-[#ecfe88] text-slate-900 text-xs font-bold px-2 py-0.5 rounded-md">BETA</span>
      </div>
    </div>
    <div>
      <a href="{{ route('productos.create') }}" class="primary-add-btn">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nuevo
      </a>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-soft border p-6 min-h-[600px]">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-slate-900">Productos</h2>
      <form method="GET" class="filter-bar">
        <div class="filter-pill w-64">
          <svg class="h-4 w-4 filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input type="text" name="q" value="{{ $q }}" placeholder="Buscar productos..." class="w-full">
        </div>
      </form>
    </div>

    @if(count($productos) > 0)
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($productos as $p)
        <div class="group relative bg-white border border-slate-100 rounded-2xl p-4 hover:shadow-md transition-all hover:border-lime-200">
          <div class="flex justify-between items-start mb-2">
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider {{ ($p['tipo'] ?? 'Producto') === 'Servicio' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                  {{ $p['tipo'] ?? 'Producto' }}
                </span>
                @if(!empty($p['service_expiry_reminder_enabled']))
                <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-lime-100 text-lime-700">
                  Recordatorio {{ (int)($p['service_expiry_reminder_days_before'] ?? 7) }}d
                </span>
                @endif
              </div>
            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a href="{{ route('productos.edit', $p['id']) }}" class="p-1 text-slate-400 hover:text-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
              </a>
              <form action="{{ route('productos.destroy', $p['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar?')">
                @csrf @method('DELETE')
                <button class="p-1 text-slate-400 hover:text-rose-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </form>
            </div>
          </div>
          
          <h3 class="font-bold text-slate-900 mb-1 truncate" title="{{ $p['nombre'] }}">{{ $p['nombre'] }}</h3>
          <p class="text-xs text-slate-500 mb-3 line-clamp-2 h-8">{{ $p['descripcion'] ?? 'Sin descripción' }}</p>

          <div class="mb-3 flex flex-wrap gap-2">
            @if(!empty($p['service_expiry_reminder_enabled']))
              <span class="inline-flex items-center rounded-full border border-lime-200 bg-lime-50 px-2.5 py-1 text-[11px] font-semibold text-lime-700">
                Activos: {{ (int) ($p['_active_reminder_count'] ?? 0) }}
              </span>
            @else
              <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                Vendidos: {{ rtrim(rtrim(number_format((float) ($p['_sold_count'] ?? 0), 2, '.', ''), '0'), '.') }}
              </span>
            @endif
          </div>
          
          <div class="border-t border-slate-100 pt-3 mt-1 space-y-1">
            @php $precios = $p['precios'] ?? []; @endphp
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $base }}</span>
              <span class="font-mono font-bold text-base text-slate-900">{{ number_format($p['precio'], 2) }}</span>
            </div>
            @foreach($precios as $cur => $val)
              @if($val !== null && $val !== '')
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ strtoupper($cur) }}</span>
                <span class="font-mono text-sm text-slate-600">{{ number_format((float)$val, 2) }}</span>
              </div>
              @endif
            @endforeach
            @if($p['sku'] ?? '')
              <div class="text-[10px] text-slate-400 pt-1">SKU: {{ $p['sku'] }}</div>
            @endif
          </div>
        </div>
        @endforeach
      </div>
    @else
      <div class="flex flex-col items-center justify-center h-96 text-slate-400 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4 shadow-sm">
          <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <p class="text-lg font-medium text-slate-900">No hay productos registrados</p>
        <p class="text-sm mb-6">Comienza agregando tus productos o servicios</p>
        <a href="{{ route('productos.create') }}" class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-6 py-2 rounded-full font-bold transition-colors shadow-sm">
          Crear primer producto
        </a>
      </div>
    @endif
  </div>
@endsection
