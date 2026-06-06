@extends('layouts.app')

@section('title', 'Editar Contrato')

@section('content')
<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<form method="POST" action="{{ route('contratos.update', $contrato['id']) }}" id="contractForm" class="max-w-5xl mx-auto space-y-6 pb-20">
    @csrf
    @method('PUT')
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Editar Contrato</h1>
            <p class="text-slate-500 mt-1">Modificando acuerdo: {{ $contrato['titulo'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('contratos.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium transition-colors">
                Cancelar
            </a>
            <button type="submit" name="estado" value="Borrador" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">
                Guardar Borrador
            </button>
            <button type="submit" name="estado" value="Enviado" class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-5 py-2 rounded-xl font-bold shadow-sm transition-colors">
                Guardar y Finalizar
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title -->
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100">
                <label class="block text-sm font-bold text-slate-700 mb-2">Título del Contrato</label>
                <input type="text" name="titulo" value="{{ $contrato['titulo'] }}" required class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow text-lg">
            </div>

            <!-- Editor -->
            <div class="bg-white rounded-2xl shadow-soft border border-slate-100 overflow-hidden flex flex-col min-h-[600px]">
                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-sm font-bold text-slate-700">Contenido del Contrato</span>
                </div>
                <!-- Quill Container -->
                <div id="editor-container" class="flex-1 text-base text-slate-700">{!! $contrato['contenido'] !!}</div>
                <input type="hidden" name="contenido" id="hiddenContent">
            </div>
        </div>

        <!-- Sidebar Options -->
        <div class="space-y-6">
            <!-- Relations -->
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100 space-y-4">
                <h3 class="font-bold text-slate-900 border-b border-slate-100 pb-2 mb-2">Detalles Generales</h3>
                
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Cliente</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 20a6 6 0 0 1 12 0"/></svg>
                        </span>
                        <select name="cliente_id" required class="w-full pl-9 rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                            <option value="">Seleccionar Cliente...</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c['id'] }}" {{ $c['id'] == $contrato['cliente_id'] ? 'selected' : '' }}>{{ $c['empresa'] ?? ($c['nombre'] ?? ($c['contacto_nombre'] ?? 'Cliente')) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Proyecto (Opcional)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <select name="proyecto_id" class="w-full pl-9 rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                            <option value="">Sin proyecto asociado</option>
                            @foreach($proyectos as $p)
                                <option value="{{ $p['id'] }}" {{ isset($contrato['proyecto_id']) && $p['id'] == $contrato['proyecto_id'] ? 'selected' : '' }}>{{ $p['titulo'] ?? ($p['nombre'] ?? 'Proyecto') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Financials -->
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100 space-y-4">
                <h3 class="font-bold text-slate-900 border-b border-slate-100 pb-2 mb-2">Desglose Financiero</h3>
                
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Monto Total</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                        <input type="number" step="0.01" name="monto" value="{{ $contrato['monto'] ?? '' }}" placeholder="0.00" class="w-full pl-7 rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Moneda</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <select name="moneda" class="w-full pl-9 rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                            <option value="MXN" {{ ($contrato['moneda'] ?? '') == 'MXN' ? 'selected' : '' }}>Pesos Mexicanos (MXN)</option>
                            <option value="USD" {{ ($contrato['moneda'] ?? '') == 'USD' ? 'selected' : '' }}>Dólares Americanos (USD)</option>
                            <option value="EUR" {{ ($contrato['moneda'] ?? '') == 'EUR' ? 'selected' : '' }}>Euros (EUR)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Escribe el contenido del contrato aquí...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    // Sync content on submit
    document.getElementById('contractForm').onsubmit = function() {
        var content = document.querySelector('input[name=contenido]');
        content.value = quill.root.innerHTML;
    };
</script>

<style>
    /* Quill Tweaks */
    .ql-toolbar { border-top: 0 !important; border-left: 0 !important; border-right: 0 !important; border-bottom: 1px solid #f1f5f9 !important; background: #f8fafc; }
    .ql-container { border: 0 !important; font-size: 16px; }
    .ql-editor { min-height: 500px; padding: 24px; }
</style>
@endsection
