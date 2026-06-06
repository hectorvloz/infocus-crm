@extends('layouts.app')

@section('title', 'Contratos')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Contratos</h1>
            <p class="text-slate-500 mt-1">Gestión de documentos legales y acuerdos</p>
        </div>
        <a href="{{ route('contratos.create') }}" class="primary-add-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Contrato
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
        <form action="{{ route('contratos.index') }}" method="GET" class="filter-bar">
            <div class="filter-pill w-full">
                <svg class="h-5 w-5 filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por título o cliente..." class="w-full">
            </div>
        </form>
    </div>

    <!-- Contracts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($contratos as $c)
            <div class="bg-white rounded-2xl shadow-soft border border-slate-100 hover:shadow-md transition-all p-5 flex flex-col h-full group relative">
                <div class="flex justify-between items-start mb-4">
                    <div class="bg-slate-50 p-3 rounded-xl">
                        <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ 
                        match($c['estado'] ?? 'Borrador') {
                            'Firmado' => 'bg-lime-100 text-lime-700',
                            'Enviado' => 'bg-blue-100 text-blue-700',
                            'Rechazado' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-600'
                        }
                    }}">
                        {{ $c['estado'] ?? 'Borrador' }}
                    </span>
                </div>

                <h3 class="text-lg font-bold text-slate-900 mb-1 group-hover:text-lime-600 transition-colors line-clamp-2">
                    <a href="{{ route('contratos.show', $c['id']) }}">{{ $c['titulo'] }}</a>
                </h3>
                <p class="text-slate-500 text-sm mb-4">{{ $c['cliente_nombre'] }}</p>

                <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between text-sm text-slate-400">
                    <span>{{ \Carbon\Carbon::parse($c['updated_at'])->format('d M, Y') }}</span>
                    <div class="flex items-center gap-2">
                        <x-action-button :href="route('contratos.edit', $c['id'])" icon="edit" title="Editar" />
                        <x-action-button :href="route('contratos.pdf', $c['id'])" icon="pdf" title="Descargar" />
                        <form action="{{ route('contratos.destroy', $c['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar contrato?');" class="inline">
                            @csrf @method('DELETE')
                            <x-action-button type="submit" icon="delete" color="rose" title="Eliminar" />
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-dashed border-slate-300">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900">No hay contratos creados</h3>
                <p class="text-slate-500 mt-1 max-w-sm mx-auto">Comienza creando tu primer acuerdo o contrato legal para un cliente.</p>
                <div class="mt-6">
                    <a href="{{ route('contratos.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-slate-900 bg-[#ecfe88] hover:bg-[#d9ef60] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Crear Contrato
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
