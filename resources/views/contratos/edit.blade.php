@extends('layouts.app')

@section('title', 'Editar Contrato')

@section('content')
<!-- Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<form method="POST" action="{{ route('contratos.update', $contrato['id']) }}" id="contractForm" class="max-w-6xl mx-auto space-y-6 pb-20">
    @csrf
    @method('PUT')
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Editar Contrato</h1>
            <p class="text-slate-500 mt-1">Modificando acuerdo: {{ $contrato['titulo'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('contratos.show', $contrato['id']) }}" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium transition-colors">Cancelar</a>
            <a href="{{ route('contratos.pdf', $contrato['id']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">Descargar PDF</a>
            <button type="submit" name="estado" value="Borrador" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">Guardar borrador</button>
            <button type="submit" name="estado" value="Enviado" data-publish class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-5 py-2 rounded-xl font-bold shadow-sm transition-colors">Publicar y enviar</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title -->
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100">
                <label class="block text-sm font-bold text-slate-700 mb-2">Título del Contrato</label>
                <input type="text" name="titulo" value="{{ $contrato['titulo'] }}" required class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow text-lg">
            </div>

            <div class="contract-editor bg-white rounded-2xl shadow-soft border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <h2 class="font-bold text-slate-900">Contenido</h2>
                    <p class="text-xs text-slate-500">Los textos entre <strong class="text-slate-700">[CORCHETES]</strong> son datos por completar.</p>
                </div>
                <div id="editor-container">{!! $contrato['contenido'] !!}</div>
                <input type="hidden" name="contenido" id="hiddenContent">
            </div>
        </div>

        <!-- Sidebar Options -->
        <div class="space-y-6 lg:sticky lg:top-4">
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

            @include('contratos._valor', ['moneda' => old('moneda', $contrato['moneda'] ?? null), 'monto' => old('monto', $contrato['monto'] ?? '')])
        </div>
    </div>
</form>

@include('contratos._editor')
<script>
    document.querySelector('[data-publish]')?.addEventListener('click', (event) => {
        if (!confirm('Se guardará el contrato y se enviará al correo del cliente con el PDF adjunto y un enlace para firmarlo. ¿Continuar?')) event.preventDefault();
    });
</script>
@endsection
