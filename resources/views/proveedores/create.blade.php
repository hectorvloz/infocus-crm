@extends('layouts.app')

@section('title', 'Nuevo Proveedor')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('gastos.index', ['tab' => 'proveedores']) }}" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900">Registrar Nuevo Proveedor</h1>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
        <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Nombre -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre / Empresa</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <input type="text" name="nombre" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Nombre del proveedor">
                </div>
            </div>

            <!-- RFC -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">RFC / Tax ID</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .884-.896 1.75-2 2.167m2-2.167c0 .884.896 1.75 2 2.167m0 0a5 5 0 100 10m-2-5a2 2 0 110-4m-1 2a1 1 0 112 0"/></svg>
                    </div>
                    <input type="text" name="rfc" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Identificación fiscal">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contacto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Persona de Contacto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <input type="text" name="contacto" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Nombre de contacto">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </div>
                        <input type="email" name="email" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="correo@ejemplo.com">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Telefono -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <input type="text" name="telefono" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="+52 ...">
                    </div>
                </div>

                <!-- Categoria -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoria del proveedor</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <select name="categoria" data-native-select="1" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm bg-white">
                            <option value="">Sin rubro (opcional)</option>
                            @foreach(($categoryOptions ?? []) as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Esta categoria se usa para clasificar proveedores y no reemplaza la categoria de cada gasto.</p>
                    <a href="{{ route('gastos.index', ['tab' => 'proveedores']) }}" class="mt-1 inline-flex text-xs font-semibold text-blue-600 hover:text-blue-800">Administrar categorias de proveedor</a>
                </div>
            </div>

            <!-- Direccion -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                <div class="relative">
                    <div class="absolute top-3 left-3 pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <textarea name="direccion" rows="2" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Calle, Número, Colonia, Ciudad..."></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('gastos.index', ['tab' => 'proveedores']) }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Cancelar</a>
                <button type="submit" class="px-6 py-2 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                    Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
