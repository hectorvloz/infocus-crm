@extends('layouts.app')
@section('title','Clientes')
@section('content')

  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-slate-900">Clientes</h1>
      <p class="text-slate-500 mt-1">Directorio de clientes y empresas</p>
    </div>
    <div>
      <a href="{{ route('clientes.create') }}" class="primary-add-btn">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nuevo cliente
      </a>
    </div>
  </div>

  <div>
    <div class="bg-white rounded-2xl shadow-soft border p-4 md:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <form method="GET" action="{{ route('clientes.index') }}" class="filter-bar w-full sm:w-auto">
          <div class="filter-pill w-full sm:w-64">
            <svg class="h-4 w-4 filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar cliente..." class="w-full">
          </div>
        </form>
        <div class="flex gap-2 sm:ml-auto">
          <button id="btnLista" class="p-2 rounded-lg bg-slate-900 text-white transition-colors shadow-lg shadow-slate-900/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg></button>
          <button id="btnGrid" class="p-2 rounded-lg bg-neutral-100 text-slate-400 hover:text-slate-600 transition-colors"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></button>
        </div>
      </div>
    <div id="listaView">
      <div class="overflow-x-auto">
      <table class="w-full text-sm table-auto">
        <thead>
          <tr>
            @php $toggle = ($dir ?? 'asc')==='asc' ? 'desc' : 'asc'; @endphp
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'id','dir'=>$toggle]) }}">ID</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap min-w-[20rem]"><a href="{{ request()->fullUrlWithQuery(['sort'=>'empresa','dir'=>$toggle]) }}">Empresa</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap min-w-[12rem]">NIT</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap min-w-[14rem]"><a href="{{ request()->fullUrlWithQuery(['sort'=>'propietario','dir'=>$toggle]) }}">Propietario</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'proyectos','dir'=>$toggle]) }}">Proyectos</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'facturas_total','dir'=>$toggle]) }}">Facturas</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Etiquetas</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Categoría</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'estado','dir'=>$toggle]) }}">Estado</a></th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($clientes as $c)
          <tr>
            <td class="py-3 px-4 font-extrabold text-slate-700 whitespace-nowrap">{{ substr($c['id'], -6) }}</td>
            <td class="py-3 px-4 min-w-[20rem]">
              <div class="flex items-center gap-3 whitespace-nowrap">
                @if(!empty($c['avatar_thumb']))
                  <img src="{{ $c['avatar_thumb'] }}" class="h-8 w-8 rounded-full object-cover border flex-shrink-0">
                @else
                  <div class="h-8 w-8 rounded-full bg-slate-900 text-white text-xs font-semibold grid place-content-center flex-shrink-0">{{ strtoupper(substr($c['empresa'],0,1)) }}</div>
                @endif
                <a class="no-underline whitespace-nowrap text-base font-normal text-lime-600 hover:text-lime-700" href="{{ route('clientes.show',$c['id']) }}">{{ $c['empresa'] }}</a>
              </div>
            </td>
            <td class="py-3 px-4 min-w-[12rem] whitespace-nowrap">{{ $c['nit'] ?? '—' }}</td>
            <td class="py-3 px-4 min-w-[14rem] whitespace-nowrap">{{ $c['propietario'] ?? '—' }}</td>
            <td class="py-3 px-4">{{ $c['proyectos'] ?? 0 }}</td>
            <td class="py-3 px-4">${{ number_format($c['facturas_total'] ?? 0, 2) }}</td>
            <td class="py-3 px-4">{{ $c['etiquetas'] ?? '—' }}</td>
            <td class="py-3 px-4"><span class="px-3 py-1 text-xs rounded-full bg-neutral-100 border">{{ $c['categoria'] ?? 'Default' }}</span></td>
            <td class="py-3 px-4">
              <span class="px-3 py-1 text-xs rounded-full {{ ($c['estado'] ?? '')==='Activo' ? 'bg-blue-100 text-blue-800' : 'bg-neutral-100 text-slate-600' }}">{{ $c['estado'] ?? 'Activo' }}</span>
            </td>
            <td class="py-3 px-4 text-right whitespace-nowrap">
              <div class="inline-flex items-center gap-2">
                <x-action-button :href="route('clientes.edit',$c['id'])" icon="edit" title="Editar" />
                <form action="{{ route('clientes.destroy',$c['id']) }}" method="POST" class="inline-flex" onsubmit="return confirm('¿Eliminar este cliente?')">
                  @csrf @method('DELETE')
                  <x-action-button type="submit" icon="delete" color="rose" title="Eliminar" />
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td class="py-6 text-slate-500">Aún no hay clientes.</td></tr>
          @endforelse
        </tbody>
      </table>
      </div>
      <div id="clientesListPagination" class="hidden px-4 py-3 border-t border-slate-200 bg-white"></div>
    </div>
    <div id="gridView" class="hidden">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
        @foreach($clientes as $c)
        <div class="group rounded-2xl border hover:border-slate-300 hover:shadow-md transition cursor-pointer p-4 bg-white" data-open-modal="{{ $c['id'] }}">
          {{-- Fila superior: avatar + nombre + estado --}}
          <div class="flex items-center gap-3">
            @if(!empty($c['avatar_thumb']))
              <img src="{{ $c['avatar_thumb'] }}" class="h-9 w-9 rounded-full object-cover border flex-shrink-0">
            @else
              <div class="h-9 w-9 rounded-full bg-slate-900 text-white text-sm font-bold grid place-content-center flex-shrink-0">{{ strtoupper(substr($c['empresa'],0,1)) }}</div>
            @endif
            <div class="min-w-0 flex-1">
              <div class="font-semibold text-sm leading-tight truncate">{{ $c['empresa'] }}</div>
              <div class="text-xs text-slate-400 truncate">{{ $c['propietario'] ?? '—' }}</div>
            </div>
            <span class="flex-shrink-0 px-2 py-0.5 text-[10px] font-medium rounded-full {{ ($c['estado'] ?? '')==='Activo' ? 'bg-blue-100 text-blue-700' : 'bg-neutral-100 text-slate-500' }}">{{ $c['estado'] ?? 'Activo' }}</span>
          </div>
          {{-- Separador --}}
          <div class="my-3 border-t border-slate-100"></div>
          {{-- Fila facturado --}}
          <div class="flex items-center gap-1.5 text-xs mb-1.5">
            <svg class="h-3.5 w-3.5 flex-shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="text-slate-500">Facturado</span>
            <span class="font-bold text-slate-800">{{ $baseCurrency }} {{ number_format($c['facturas_total_base'] ?? 0, 0) }}</span>
            @foreach($c['facturas_desglose'] ?? [] as $mon => $sub)
              <span class="text-slate-400 text-[10px]">· {{ $mon }} {{ number_format($sub, 0) }}</span>
            @endforeach
          </div>
          {{-- Fila proy / pend --}}
          <div class="flex items-center gap-3 text-xs">
              <div class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 21V9"/></svg>
                <span class="text-slate-500">Proy.</span>
                <span class="font-bold text-slate-800">{{ $c['proyectos'] ?? 0 }}</span>
              </div>
              <div class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="text-slate-500">Pend.</span>
                <span class="font-bold {{ ($c['pendientes'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $c['pendientes'] ?? 0 }}</span>
              </div>
          </div>
          <div id="content-{{ $c['id'] }}" class="hidden">
            <div class="flex items-center gap-3 mb-3">
              @if(!empty($c['avatar_thumb']))<img src="{{ $c['avatar_thumb'] }}" class="h-12 w-12 rounded-full object-cover border">@else
              <div class="h-12 w-12 rounded-full bg-slate-900 text-white grid place-content-center">{{ strtoupper(substr($c['empresa'],0,1)) }}</div>
              @endif
              <div>
                <div class="text-lg font-extrabold">{{ $c['empresa'] }}</div>
                <div class="text-sm text-slate-500">NIT: {{ $c['nit'] ?? '—' }} • Propietario: {{ $c['propietario'] ?? '—' }}</div>
              </div>
            </div>
            <div class="grid grid-cols-3 gap-2 mb-3">
              <div class="rounded-xl bg-neutral-50 border p-2 text-center">
                <div class="text-[10px] text-slate-500">Facturado</div>
                <div class="font-bold text-sm">{{ $baseCurrency }} {{ number_format($c['facturas_total_base'] ?? 0, 0) }}</div>
                @foreach($c['facturas_desglose'] ?? [] as $mon => $sub)
                  <div class="text-[10px] text-slate-400">{{ $mon }} {{ number_format($sub, 0) }}</div>
                @endforeach
              </div>
              <div class="rounded-xl bg-neutral-50 border p-2 text-center"><div class="text-[10px] text-slate-500">Proyectos</div><div class="font-bold">{{ $c['proyectos'] ?? 0 }}</div></div>
              <div class="rounded-xl bg-neutral-50 border p-2 text-center"><div class="text-[10px] text-slate-500">Pendientes</div><div class="font-bold">{{ $c['pendientes'] ?? 0 }}</div></div>
            </div>
            <div class="text-sm font-semibold mb-1">Últimas facturas</div>
            <div class="space-y-1">
              @forelse(($c['recent'] ?? []) as $f)
              @php $fMon = $f['moneda'] ?? $baseCurrency; $fBase = $f['total_base'] ?? null; @endphp
              <div class="flex items-center justify-between text-sm">
                <div>#{{ $f['numero'] }} • {{ \Illuminate\Support\Carbon::parse($f['fecha'])->format('d/m/Y') }}</div>
                <div class="text-right">
                  <div>{{ $fMon }} {{ number_format($f['total'],2) }}</div>
                  @if($fMon !== $baseCurrency && $fBase)
                    <div class="text-[10px] text-slate-400">≈ {{ $baseCurrency }} {{ number_format($fBase,2) }}</div>
                  @endif
                </div>
              </div>
              @empty
              <div class="text-slate-500 text-sm">Sin facturas.</div>
              @endforelse
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  <div id="clienteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-3 sm:p-4 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full p-4 md:p-6 relative max-h-[85vh] overflow-y-auto">
      <button id="closeModal" class="absolute right-3 top-3 text-slate-500">✕</button>
      <div id="modalBody"></div>
      <div class="mt-4 flex justify-end gap-2 sticky bottom-0 bg-white pt-3">
        <a id="btnVerCliente" href="#" class="px-3 py-2 rounded-full border text-sm">Ver ficha</a>
        <a id="btnCrearFactura" href="#" class="px-3 py-2 rounded-full bg-lime-300 text-slate-900 text-sm font-semibold">+ Crear factura</a>
      </div>
    </div>
  </div>
  <script>
    const listaBtn = document.getElementById('btnLista');
    const gridBtn = document.getElementById('btnGrid');
    const lista = document.getElementById('listaView');
    const grid = document.getElementById('gridView');
    function applyView(v){
      if(v==='grid'){ 
        lista.classList.add('hidden'); 
        grid.classList.remove('hidden');
        gridBtn.classList.remove('bg-neutral-100','text-slate-400');
        gridBtn.classList.add('bg-slate-900','text-white','shadow-lg');
        listaBtn.classList.remove('bg-slate-900','text-white','shadow-lg');
        listaBtn.classList.add('bg-neutral-100','text-slate-400');
      } else { 
        grid.classList.add('hidden'); 
        lista.classList.remove('hidden'); 
        listaBtn.classList.remove('bg-neutral-100','text-slate-400');
        listaBtn.classList.add('bg-slate-900','text-white','shadow-lg');
        gridBtn.classList.remove('bg-slate-900','text-white','shadow-lg');
        gridBtn.classList.add('bg-neutral-100','text-slate-400');
      } 
      localStorage.setItem('clientesView', v); 
    }
    if (listaBtn && gridBtn) {
      listaBtn.addEventListener('click', ()=>applyView('list'));
      gridBtn.addEventListener('click', ()=>applyView('grid'));
      // Siempre abrir Clientes en vista lista para que el estado visual coincida.
      applyView('list');
    }

    const CLIENTES_LIST_PAGE_SIZE = 20;
    let clientesListCurrentPage = 1;

    function renderClientesListPagination() {
      const tbody = lista?.querySelector('tbody');
      const pagination = document.getElementById('clientesListPagination');
      if (!tbody || !pagination) return;

      const rows = Array.from(tbody.querySelectorAll('tr'));
      const isEmptyState = rows.length === 1 && rows[0].children.length === 1;

      if (!rows.length || isEmptyState) {
        pagination.classList.add('hidden');
        pagination.innerHTML = '';
        rows.forEach((row) => { row.style.display = ''; });
        return;
      }

      const totalItems = rows.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / CLIENTES_LIST_PAGE_SIZE));
      if (clientesListCurrentPage > totalPages) clientesListCurrentPage = totalPages;

      const start = (clientesListCurrentPage - 1) * CLIENTES_LIST_PAGE_SIZE;
      const end = start + CLIENTES_LIST_PAGE_SIZE;
      rows.forEach((row, index) => {
        row.style.display = (index >= start && index < end) ? '' : 'none';
      });

      const from = start + 1;
      const to = Math.min(end, totalItems);
      pagination.classList.remove('hidden');
      pagination.innerHTML = `<div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="text-sm font-semibold text-slate-600">Clientes ${from}-${to} de ${totalItems}</div>
        <div class="flex items-center gap-2">
          <button type="button" data-clientes-page="${clientesListCurrentPage - 1}" ${clientesListCurrentPage <= 1 ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Anterior</button>
          <span class="text-sm font-bold text-slate-700">${clientesListCurrentPage}/${totalPages}</span>
          <button type="button" data-clientes-page="${clientesListCurrentPage + 1}" ${clientesListCurrentPage >= totalPages ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Siguiente</button>
        </div>
      </div>`;

      pagination.querySelectorAll('[data-clientes-page]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const page = Number(btn.getAttribute('data-clientes-page')) || 1;
          clientesListCurrentPage = Math.max(1, page);
          renderClientesListPagination();
        });
      });
    }

    if ((localStorage.getItem('clientesView') ?? 'list') === 'list') {
      renderClientesListPagination();
    }

    if (listaBtn && gridBtn) {
      listaBtn.addEventListener('click', () => {
        setTimeout(() => renderClientesListPagination(), 0);
      });
    }

    const modal = document.getElementById('clienteModal');
    const modalBody = document.getElementById('modalBody');
    const closeModal = document.getElementById('closeModal');
    const btnVer = document.getElementById('btnVerCliente');
    const btnFactura = document.getElementById('btnCrearFactura');
    let currentClienteId = null;
    document.querySelectorAll('[data-open-modal]').forEach(card=>{
      card.addEventListener('click', ()=>{
        const id = card.getAttribute('data-open-modal');
        currentClienteId = id;
        const content = document.getElementById('content-'+id);
        if(content){ 
          modalBody.innerHTML = content.innerHTML; 
          btnVer.href = '/clientes/'+id; 
          const name = content.querySelector('.text-lg')?.textContent || ''; 
          btnFactura.href = '/facturas/crear?cliente='+encodeURIComponent(name); 
        }
        modal.classList.remove('hidden'); modal.classList.add('flex');
      });
    });
    if (closeModal){ closeModal.addEventListener('click', ()=>{ modal.classList.add('hidden'); modal.classList.remove('flex'); }); }
    modal?.addEventListener('click', (e)=>{ if(e.target===modal){ modal.classList.add('hidden'); modal.classList.remove('flex'); }});
  </script>
@endsection
