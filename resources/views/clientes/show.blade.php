@extends('layouts.app')
@section('title','Cliente')
@section('content')
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-2xl shadow border p-6">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-3">
              @if(!empty($cliente['avatar']))
                <img src="{{ $cliente['avatar'] }}" class="h-12 w-12 rounded-full object-cover border">
              @else
                <div class="h-12 w-12 rounded-full bg-slate-900 text-white grid place-content-center">{{ strtoupper(substr($cliente['empresa'],0,1)) }}</div>
              @endif
              <div class="text-2xl font-extrabold">{{ $cliente['empresa'] }}</div>
            </div>
            <div class="text-slate-500">Propietario: {{ $cliente['propietario'] ?? '—' }}</div>
            <div class="text-slate-500">Estado: <span class="px-2 py-0.5 rounded-full text-xs {{ ($cliente['estado'] ?? '')==='Activo' ? 'bg-blue-100 text-blue-800' : 'bg-neutral-100 text-slate-600' }}">{{ $cliente['estado'] ?? 'Activo' }}</span></div>
          </div>
		          <div class="flex gap-2">
		            <a href="{{ route('clientes.edit',$cliente['id']) }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50 whitespace-nowrap">Editar cliente</a>
		          </div>
        </div>
        <div class="space-y-3 mt-6">
          <div class="bg-neutral-50 border rounded-xl p-4 min-w-0">
            <div class="text-sm text-slate-500">Total facturado</div>
            <div class="text-3xl font-extrabold mt-1 whitespace-nowrap leading-none">{{ $baseCurrency }} {{ number_format($total,2) }}</div>
            @if(!empty($totalesPorMoneda))
              <div class="mt-1 space-y-0.5">
                @foreach($totalesPorMoneda as $mon => $subtotal)
                  <div class="text-xs text-slate-400">{{ $mon }} {{ number_format($subtotal,2) }}</div>
                @endforeach
              </div>
            @endif
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-neutral-50 border rounded-xl p-4">
              <div class="text-sm text-slate-500">Facturas</div>
              <div class="text-2xl font-extrabold mt-1">{{ $facturas->count() }}</div>
            </div>
            <div class="bg-neutral-50 border rounded-xl p-4">
              <div class="text-sm text-slate-500">Pendientes</div>
              <div class="text-2xl font-extrabold mt-1">{{ $pendientes }}</div>
            </div>
            <div class="bg-neutral-50 border rounded-xl p-4 min-w-0 col-span-2">
              <div class="text-sm text-slate-500">Gastos</div>
              <div class="text-2xl font-extrabold mt-1 whitespace-nowrap">${{ number_format($totalGastos,2) }}</div>
            </div>
          </div>
        </div>
	      </div>
	      <div class="bg-white rounded-2xl shadow border p-6">
	        <div class="text-lg font-bold mb-4">Información de contacto</div>
	        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
	          <div><span class="text-slate-500">NIT:</span> {{ $cliente['nit'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Nombre:</span> {{ $cliente['contacto_nombre'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Email:</span> {{ $cliente['contacto_email'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Teléfono:</span> {{ $cliente['contacto_telefono'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Dirección:</span> {{ $cliente['direccion'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Ciudad:</span> {{ $cliente['ciudad'] ?? '—' }}</div>
	          <div><span class="text-slate-500">País:</span> {{ $cliente['pais'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Código postal:</span> {{ $cliente['codigo_postal'] ?? '—' }}</div>
	          <div><span class="text-slate-500">Website:</span> {{ $cliente['website'] ?? '—' }}</div>
	        </div>
	        @php $cf = $cliente['custom_fields'] ?? []; @endphp
	        <div class="mt-5 border-t border-slate-100 pt-5">
	          <div class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 mb-3">Campos personalizados</div>
	          @if($cf && is_array($cf))
	            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
	              @foreach($cf as $k=>$v)
	                <div class="flex justify-between gap-3 rounded-xl bg-neutral-50 px-3 py-2">
	                  <span class="text-slate-500">{{ $k }}</span>
	                  <span class="font-medium text-slate-900 text-right">{{ $v }}</span>
	                </div>
	              @endforeach
	            </div>
	          @else
	            <div class="text-slate-500 text-sm">Sin campos personalizados.</div>
	          @endif
	        </div>
	      </div>
	      <div class="bg-white rounded-2xl shadow border p-6">
	        <div class="flex items-center justify-between mb-3">
	          <div class="text-lg font-bold">Timeline</div>
          <form method="POST" action="{{ route('clientes.addNota',$cliente['id']) }}" class="flex items-center gap-2">
            @csrf
            <input name="nota" class="rounded-full border px-3 py-2 text-sm" placeholder="Agregar nota">
            <button class="px-3 py-2 rounded-full bg-lime-300 text-slate-900 text-sm font-semibold">Añadir</button>
          </form>
        </div>
        <div class="space-y-3">
          @foreach($timeline as $e)
            <div class="flex items-start gap-3">
              <div class="h-8 w-8 rounded-full bg-slate-900 text-white grid place-content-center text-xs">{{ strtoupper(substr($e['tipo'],0,1)) }}</div>
              <div>
                <div class="text-sm">
                  @if($e['tipo']==='nota')
                    <span class="font-semibold">Nota:</span> {{ $e['payload']['texto'] }}
                  @elseif($e['tipo']==='factura')
                    <span class="font-semibold">Factura:</span> {{ $e['payload']['numero'] }} • ${{ number_format($e['payload']['total'],2) }} • {{ $e['payload']['estado'] }}
                  @elseif($e['tipo']==='proyecto')
                    <span class="font-semibold">Proyecto:</span> {{ $e['payload']['titulo'] ?? '—' }} • {{ $e['payload']['etapa'] ?? '—' }}
                  @endif
                </div>
                <div class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($e['created_at'])->diffForHumans() }}</div>
              </div>
            </div>
          @endforeach
          @if($timeline===[])
            <div class="text-slate-500 text-sm">Sin eventos todavía.</div>
          @endif
        </div>
      </div>
    </div>
    <div class="space-y-4">
      <div class="bg-white rounded-2xl shadow border p-6">
        <div class="text-lg font-bold mb-3">Facturas recientes</div>
        <div class="space-y-2">
          @forelse($facturas->sortByDesc('fecha')->take(6) as $f)
          @php
            $fMon = $f['moneda'] ?? $baseCurrency;
            $fBase = $f['total_base'] ?? null;
          @endphp
          <a href="{{ route('facturas.show',$f['id']) }}" class="flex items-center justify-between rounded-xl border px-3 py-2 hover:bg-neutral-50">
            <div class="min-w-0 flex-1 pr-2">
              <div class="text-sm font-semibold">#{{ $f['numero'] }} • {{ $fMon }} {{ number_format($f['total'],2) }}</div>
              @if($fMon !== $baseCurrency && $fBase)
                <div class="text-xs text-slate-400">≈ {{ $baseCurrency }} {{ number_format($fBase,2) }}</div>
              @endif
              <div class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($f['fecha'])->format('d/m/Y') }}</div>
            </div>
            <span class="px-2 py-0.5 text-xs rounded-full flex-shrink-0 {{ $f['estado']==='Pagada' ? 'bg-emerald-100 text-emerald-800' : 'bg-neutral-100 text-slate-600' }}">{{ $f['estado'] }}</span>
          </a>
          @empty
          <div class="text-slate-500 text-sm">Sin facturas.</div>
          @endforelse
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow border p-6">
        <div class="text-lg font-bold mb-3">Acciones rápidas</div>
        <div class="grid gap-2">
          <a href="{{ route('facturas.create',['cliente'=>$cliente['empresa']]) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-lime-300 text-slate-900 font-semibold px-4 py-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            <span>Crear Factura</span>
          </a>
	          <a href="{{ route('proyectos.index', ['cliente_id' => $cliente['id'], 'new_project' => 1]) }}" class="inline-flex items-center justify-center rounded-full border px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">Crear proyecto</a>
	        </div>
      </div>
      <div class="bg-white rounded-2xl shadow border p-6">
        <div class="text-lg font-bold mb-3">Proyectos recientes</div>
        <div class="space-y-2">
          @php
            $projs = collect((new \App\Repositories\FileStore('proyectos.json'))->all())
              ->where('cliente_id', $cliente['id'] ?? '')
              ->reject(function ($p) {
                $archived = (bool) ($p['archived'] ?? false);
                $deleted = (bool) ($p['deleted'] ?? false);
                $hasDeletedAt = !empty($p['deleted_at'] ?? null);
                $etapa = strtolower((string) ($p['etapa'] ?? ''));
                $esInactivoPorEtapa = in_array($etapa, ['archivado', 'archivado(s)', 'eliminado', 'eliminada'], true);

                return $archived || $deleted || $hasDeletedAt || $esInactivoPorEtapa;
              });
          @endphp
          @forelse($projs->sortByDesc('updated_at')->take(6) as $p)
            <div class="flex items-center justify-between rounded-xl border px-3 py-2">
              <div>
                <div class="text-sm font-semibold">{{ $p['titulo'] ?? 'Proyecto' }}</div>
                <div class="text-xs text-slate-500">{{ $p['etapa'] ?? '—' }} • {{ $p['prioridad'] ?? 'Media' }}</div>
              </div>
              <div class="h-1.5 w-24 bg-neutral-100 rounded overflow-hidden"><div class="progress-fill-anim h-1.5 bg-slate-900" style="width:{{ max(0,min(100,$p['progreso'] ?? 0)) }}%"></div></div>
            </div>
          @empty
          <div class="text-slate-500 text-sm">Sin proyectos.</div>
          @endforelse
        </div>
      </div>
      <div class="bg-white rounded-2xl shadow border p-6">
        <div class="text-lg font-bold mb-3">Gastos relacionados</div>
        @php
          $gastosCliente = collect((new \App\Repositories\FileStore('gastos.json'))->all())
            ->where('cliente_id', $cliente['id'] ?? '')
            ->sortByDesc('fecha')
            ->take(6);
        @endphp
        <div class="space-y-2">
          @forelse($gastosCliente as $g)
            <div class="flex items-center justify-between rounded-xl border px-3 py-2">
              <div>
                <div class="text-sm font-semibold">{{ $g['concepto'] ?? 'Gasto' }}</div>
                <div class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($g['fecha'])->format('d/m/Y') }}</div>
              </div>
              <div class="text-sm font-semibold">{{ ($g['moneda'] ?? '$') }} {{ number_format($g['monto'] ?? 0, 2) }}</div>
            </div>
          @empty
            <div class="text-slate-500 text-sm">Sin gastos relacionados.</div>
          @endforelse
        </div>
      </div>
	    </div>
  </div>
@endsection
