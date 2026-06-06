@extends('layouts.settings')

@section('title', 'Formas de Pago')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Formas de Pago</h2>
    <p class="text-slate-500">Configura los métodos de pago disponibles para tus facturas.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
    <form action="{{ route('settings.payment_methods.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div id="methods-container" class="space-y-4">
            @foreach($methods as $index => $method)
                <div class="method-row bg-slate-50 p-4 rounded-xl border border-slate-200 relative group">
                    <input type="hidden" name="methods[{{ $index }}][id]" value="{{ $method['id'] }}">
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre</label>
                            <input type="text" name="methods[{{ $index }}][name]" value="{{ $method['name'] }}" class="block w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Ej. Transferencia Bancaria" required>
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Instrucciones / Detalles</label>
                            <textarea name="methods[{{ $index }}][details]" rows="2" class="block w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Ej. Banco X, Cuenta Y...">{{ $method['details'] ?? '' }}</textarea>
                        </div>
                        <div class="md:col-span-2 flex items-center justify-between md:justify-center gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="methods[{{ $index }}][active]" value="1" {{ ($method['active'] ?? false) ? 'checked' : '' }} class="h-4 w-4 text-lime-600 focus:ring-lime-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-slate-700 md:hidden">Activo</label>
                            </div>
                            <button type="button" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors remove-btn">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button type="button" id="add-btn" class="flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Agregar Método
            </button>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<template id="method-template">
    <div class="method-row bg-slate-50 p-4 rounded-xl border border-slate-200 relative group">
        <input type="hidden" name="methods[INDEX][id]" value="">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre</label>
                <input type="text" name="methods[INDEX][name]" class="block w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Ej. Paypal" required>
            </div>
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Instrucciones / Detalles</label>
                <textarea name="methods[INDEX][details]" rows="2" class="block w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Ej. Link de pago..."></textarea>
            </div>
            <div class="md:col-span-2 flex items-center justify-between md:justify-center gap-4">
                <div class="flex items-center">
                    <input type="checkbox" name="methods[INDEX][active]" value="1" checked class="h-4 w-4 text-lime-600 focus:ring-lime-500 border-gray-300 rounded">
                    <label class="ml-2 block text-sm text-slate-700 md:hidden">Activo</label>
                </div>
                <button type="button" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors remove-btn">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
    const container = document.getElementById('methods-container');
    const addBtn = document.getElementById('add-btn');
    const template = document.getElementById('method-template');

    addBtn.addEventListener('click', () => {
        const index = document.querySelectorAll('.method-row').length;
        const clone = template.content.cloneNode(true);
        
        // Generate pseudo-unique ID for new items
        const newId = 'new_' + Date.now();
        clone.querySelector('input[name$="[id]"]').value = newId;

        // Replace INDEX placeholder
        clone.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('INDEX', index);
        });

        container.appendChild(clone);
        bindEvents(container.lastElementChild);
    });

    function bindEvents(row) {
        row.querySelector('.remove-btn').addEventListener('click', () => {
            row.remove();
            // Optional: re-index or just leave it, Laravel handles array keys fine usually if they are not sequential but here we used explicit indices.
            // Actually, if we delete a row, the indices will have gaps. Laravel validation 'methods.*.name' handles this fine.
        });
    }

    document.querySelectorAll('.method-row').forEach(bindEvents);
</script>
@endsection