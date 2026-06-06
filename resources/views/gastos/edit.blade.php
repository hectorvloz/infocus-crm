@extends('layouts.app')

@section('title', 'Editar Gasto')

@section('content')
@php
    $settings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
    $base = $settings['base_currency'] ?? 'USD';
    $currencies = ['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY'];
@endphp
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('gastos.index') }}" class="p-2 rounded-full hover:bg-slate-100 text-slate-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900">Editar Gasto</h1>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
        <form action="{{ route('gastos.update', $gasto['id']) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Concepto -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Concepto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                    <input type="text" name="concepto" required value="{{ $gasto['concepto'] }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Monto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Monto</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <input type="number" step="0.01" name="monto" required value="{{ $gasto['monto'] }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                    </div>
                </div>
                
                <!-- Moneda -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Moneda</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <select name="moneda" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            @foreach($currencies as $c)
                                <option value="{{ $c }}" @selected(($gasto['moneda'] ?? $base)===$c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fecha -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <input type="text" id="fecha" name="fecha" required value="{{ $gasto['fecha'] }}" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm bg-white cursor-pointer">
                    </div>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cliente (opcional)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <select name="cliente_id" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="">-- Sin Cliente --</option>
                            @foreach(($clientes ?? []) as $c)
                                <option value="{{ $c['id'] }}" @selected(($gasto['cliente_id'] ?? '')===$c['id'])>{{ $c['empresa'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Proveedor -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <select name="proveedor_id" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="">-- Sin Proveedor --</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov['id'] }}" {{ isset($gasto['proveedor_id']) && $gasto['proveedor_id'] == $prov['id'] ? 'selected' : '' }}>{{ $prov['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                <div class="dropdown-container" id="categoriaDropdown" data-categories='@json($categories ?? [])'>
                    <div class="dropdown-trigger">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <input type="text" id="categoriaSearch" value="{{ $gasto['categoria'] ?? '' }}" placeholder="Oficina, Software, Viajes..." autocomplete="off">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <div class="dropdown-menu custom-scroll" id="categoriaMenu">
                        <div class="px-3 py-2 text-xs text-slate-400">Escribe para buscar</div>
                        <div class="border-t border-slate-100"></div>
                        <div class="p-2">
                            <div class="flex items-center gap-2">
                                <input type="text" id="categoriaNew" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Crear categoría">
                                <button type="button" id="categoriaAdd" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-bold">Crear</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="categoria" id="categoriaField" value="{{ $gasto['categoria'] ?? '' }}">
            </div>

            <!-- Recurrencia -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center h-5">
                            <input id="es_recurrente" name="es_recurrente" type="checkbox" value="1" {{ ($gasto['es_recurrente'] ?? false) ? 'checked' : '' }} class="focus:ring-[#ecfe88] h-4 w-4 text-slate-900 border-gray-300 rounded">
                        </div>
                        <div class="ml-2 text-sm">
                            <label for="es_recurrente" class="font-medium text-slate-700">Gasto Recurrente</label>
                            <p class="text-slate-500 text-xs">Se repite periódicamente</p>
                        </div>
                    </div>
                    <div id="frecuencia-container" class="{{ ($gasto['es_recurrente'] ?? false) ? '' : 'hidden' }}">
                        <select name="frecuencia" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm rounded-md">
                            <option value="Mensual" {{ ($gasto['frecuencia'] ?? '') == 'Mensual' ? 'selected' : '' }}>Mensual</option>
                            <option value="Anual" {{ ($gasto['frecuencia'] ?? '') == 'Anual' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('es_recurrente').addEventListener('change', function() {
                    const container = document.getElementById('frecuencia-container');
                    if (this.checked) {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                    }
                });
            </script>

            <!-- Comprobante -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Comprobante / Factura (Imagen o PDF)</label>
                @if(!empty($gasto['comprobante_path']))
                    <div class="mb-3 flex items-center gap-3">
                        <a href="{{ asset('storage/' . $gasto['comprobante_path']) }}" target="_blank" class="flex items-center gap-2 text-blue-600 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Ver comprobante actual
                        </a>
                        <span class="text-xs text-slate-400">(Subir uno nuevo reemplazará el actual)</span>
                    </div>
                @endif
                <div class="flex items-center justify-center w-full">
                    <label for="comprobante-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="mb-2 text-sm text-slate-500"><span class="font-semibold">Click para cambiar</span> o arrastra y suelta</p>
                            <p class="text-xs text-slate-500">PNG, JPG, GIF, PDF (MAX. 10MB)</p>
                        </div>
                        <input id="comprobante-file" type="file" name="comprobante" class="hidden" accept="image/*,application/pdf" />
                    </label>
                </div>
                <div class="mt-3">
                    <button type="button" id="btnCamera" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h4l2-3h6l2 3h4v12H3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17a4 4 0 100-8 4 4 0 000 8z"/></svg>
                        Tomar foto
                    </button>
                    <input id="comprobante-camera" type="file" name="comprobante_camera" class="hidden" accept="image/*" capture="environment" />
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notas Adicionales</label>
                <div class="relative">
                    <div class="absolute top-3 left-3 pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <textarea name="notas" rows="3" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">{{ $gasto['notas'] ?? '' }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('gastos.index') }}" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">Cancelar</a>
                <button type="submit" class="px-6 py-2 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                    Actualizar Gasto
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr("#fecha", {
            altInput: true,
            altFormat: "d M, Y",
            dateFormat: "Y-m-d",
            defaultDate: "{{ $gasto['fecha'] }}",
            locale: "es",
            onOpen: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add("airline-calendar");
            }
        });
    });

    document.getElementById('btnCamera')?.addEventListener('click', () => {
        document.getElementById('comprobante-camera')?.click();
    });

    (function initCategoriaPicker(){
        const defaultCats = ['Oficina','Software','Servicios','Viajes','Nomina','Marketing'];
        const menu = document.getElementById('categoriaMenu');
        const search = document.getElementById('categoriaSearch');
        const field = document.getElementById('categoriaField');
        const inputNew = document.getElementById('categoriaNew');
        const btnAdd = document.getElementById('categoriaAdd');
        const wrap = document.getElementById('categoriaDropdown');
        if (!menu || !search || !field || !wrap) return;
        let categories = [];
        try {
            const serverCats = JSON.parse(wrap.dataset.categories || '[]');
            categories = Array.from(new Set(defaultCats.concat(serverCats))).sort();
        } catch(e) {
            categories = defaultCats.slice();
        }

        function renderList(query) {
            const normalized = (query || '').trim().toLowerCase();
            const items = categories.filter(c => c.toLowerCase().includes(normalized));
            const list = document.createElement('div');
            list.className = 'max-h-48 overflow-auto custom-scroll';
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 text-sm text-slate-500 italic';
                empty.textContent = 'Sin coincidencias';
                list.appendChild(empty);
            } else {
                items.forEach((c) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-b-0';
                    btn.textContent = c;
                    btn.addEventListener('click', () => {
                        search.value = c;
                        field.value = c;
                        menu.classList.remove('dropdown-active');
                        menu.classList.add('dropdown-enter');
                    });
                    list.appendChild(btn);
                });
            }
            menu.querySelectorAll('.categoria-list').forEach(el => el.remove());
            list.classList.add('categoria-list');
            menu.insertBefore(list, menu.children[1]);
        }


        function openMenu() {
            renderList(search.value);
            menu.classList.remove('dropdown-enter');
            menu.classList.add('dropdown-active');
        }

        search.addEventListener('focus', openMenu);
        search.addEventListener('input', () => {
            field.value = search.value;
            renderList(search.value);
        });
        wrap.addEventListener('click', () => openMenu());
        document.addEventListener('click', (e) => {
            if (e.target.closest('#categoriaDropdown')) return;
            menu.classList.remove('dropdown-active');
            menu.classList.add('dropdown-enter');
        });

        btnAdd?.addEventListener('click', () => {
            const val = (inputNew?.value || '').trim();
            if (!val) return;
            if (!categories.includes(val)) {
                categories.push(val);
                categories.sort();
            }
            search.value = val;
            field.value = val;
            inputNew.value = '';
            renderList('');
        });
    })();
</script>
@endsection
