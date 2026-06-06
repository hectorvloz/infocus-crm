@extends('layouts.settings')

@section('title', 'Roles y Permisos')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Roles y Permisos</h2>
        <p class="text-slate-500">Define qué acciones pueden realizar los usuarios en el sistema.</p>
    </div>
    <a href="{{ route('settings.roles.create') }}" class="px-4 py-2 bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 rounded-full font-bold transition-colors shadow-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nuevo Rol
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($roles as $role)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex flex-col h-full relative group hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="h-10 w-10 rounded-xl bg-lime-100 flex items-center justify-center text-lime-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ route('settings.roles.edit', $role['id']) }}" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    @if($role['id'] !== 'admin')
                    <form action="{{ route('settings.roles.destroy', $role['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar este rol?');">
                        @csrf @method('DELETE')
                        <button class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            
            <h3 class="font-bold text-lg text-slate-900 mb-1">{{ $role['name'] }}</h3>
            <p class="text-sm text-slate-500 mb-4 flex-1">{{ $role['description'] ?? 'Sin descripción' }}</p>
            
            <div class="border-t border-slate-100 pt-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Permisos</span>
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach(array_slice($role['permissions'] ?? [], 0, 3) as $p)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600">
                            {{ $p === '*' ? 'Acceso Total' : str_replace(['.*','.create','.read','.update','.delete'], [' (Todo)',' (Crear)',' (Ver)',' (Editar)',' (Borrar)'], $p) }}
                        </span>
                    @endforeach
                    @if(count($role['permissions'] ?? []) > 3)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-slate-50 text-slate-400">+{{ count($role['permissions']) - 3 }} más</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
