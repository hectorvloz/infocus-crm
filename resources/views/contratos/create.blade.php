@extends('layouts.app')

@section('title', 'Nuevo Contrato')

@section('content')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<form method="POST" action="{{ route('contratos.store') }}" id="contractForm" class="max-w-6xl mx-auto space-y-6 pb-20">
    @csrf

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Nuevo contrato</h1>
            <p class="text-slate-500 mt-1">Escoge una plantilla, ajústala y guárdala como borrador o finalízala.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('contratos.index') }}" class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-medium transition-colors">Cancelar</a>
            <button type="submit" name="estado" value="Borrador" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">Guardar borrador</button>
            <button type="submit" name="estado" value="Enviado" data-publish class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-5 py-2 rounded-xl font-bold shadow-sm transition-colors">Publicar y enviar</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100">
                <label for="contractTitle" class="block text-sm font-bold text-slate-700 mb-2">Título del contrato</label>
                <input id="contractTitle" type="text" name="titulo" required placeholder="Ej: Contrato de diseño web · Café Laurel" class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300 transition-shadow text-lg">
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100">
                <div class="mb-4">
                    <h2 class="font-bold text-slate-900">Plantillas</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Modelos basados en la normativa colombiana. Para contratos de alto valor, revísalos con un abogado.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($templates as $key => $tpl)
                        <button type="button" data-template="{{ $key }}" class="contract-template text-left rounded-xl border border-slate-200 bg-white px-4 py-3 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                            <span class="block text-sm font-bold text-slate-900">{{ $tpl['titulo'] }}</span>
                            <span class="block mt-1 text-xs leading-snug text-slate-500">{{ $tpl['descripcion'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="contract-editor bg-white rounded-2xl shadow-soft border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <h2 class="font-bold text-slate-900">Contenido</h2>
                    <p class="text-xs text-slate-500">Los textos entre <strong class="text-slate-700">[CORCHETES]</strong> son datos por completar.</p>
                </div>
                <div id="editor-container"></div>
                <input type="hidden" name="contenido" id="hiddenContent">
            </div>
        </div>

        <div class="space-y-6 lg:sticky lg:top-4">
            <div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100 space-y-4">
                <h3 class="font-bold text-slate-900">Detalles generales</h3>
                <div>
                    <label for="contractClient" class="block text-sm font-medium text-slate-600 mb-1">Cliente</label>
                    <select id="contractClient" name="cliente_id" required class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                        <option value="">Seleccionar cliente…</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c['id'] }}">{{ $c['empresa'] ?? ($c['nombre'] ?? ($c['contacto_nombre'] ?? 'Cliente')) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Sus datos llenan [CLIENTE], [NIT CLIENTE] y [REPRESENTANTE CLIENTE].</p>
                </div>
                <div>
                    <label for="contractProject" class="block text-sm font-medium text-slate-600 mb-1">Proyecto (opcional)</label>
                    <select id="contractProject" name="proyecto_id" class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
                        <option value="">Sin proyecto asociado</option>
                        @foreach($proyectos as $p)
                            <option value="{{ $p['id'] }}">{{ $p['titulo'] ?? ($p['nombre'] ?? 'Proyecto') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @include('contratos._valor', ['moneda' => old('moneda'), 'monto' => old('monto')])
        </div>
    </div>
</form>

@include('contratos._editor')
<script>
    document.querySelector('[data-publish]')?.addEventListener('click', (event) => {
        if (!confirm('Se guardará el contrato y se enviará al correo del cliente con el PDF adjunto y un enlace para firmarlo. ¿Continuar?')) event.preventDefault();
    });
</script>
<script id="templatesData" type="application/json">{!! json_encode($templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
<script id="clientsData" type="application/json">{!! json_encode($clientesData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
<script>
    (function () {
        const quill = window.contractQuill;
        const templates = JSON.parse(document.getElementById('templatesData').textContent || '{}');
        const clients = JSON.parse(document.getElementById('clientsData').textContent || '{}');
        const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

        // Reemplaza los datos conocidos; los que no se tengan quedan entre corchetes para llenarlos a mano.
        function fillTokens(html) {
            const client = clients[document.getElementById('contractClient').value] || {};
            const values = {
                '[CLIENTE]': client.nombre,
                '[NIT CLIENTE]': client.nit,
                '[REPRESENTANTE CLIENTE]': client.representante,
                '[CIUDAD CLIENTE]': client.ciudad,
                '[CORREO CLIENTE]': client.email,
                '[VALOR]': window.formatContractMoney(),
                '[FECHA]': new Date().toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' }),
            };
            Object.entries(values).forEach(([token, value]) => {
                if (value) html = html.split(token).join(escapeHtml(value));
            });
            return html;
        }

        function loadHtml(html) {
            quill.setContents([]);
            quill.clipboard.dangerouslyPasteHTML(0, html);
        }

        document.querySelectorAll('.contract-template').forEach((button) => {
            button.addEventListener('click', () => {
                const tpl = templates[button.dataset.template];
                if (!tpl) return;
                if (quill.getText().trim() && !confirm('¿Cargar esta plantilla? Se reemplazará el contenido actual.')) return;
                loadHtml(fillTokens(tpl.contenido));
                quill.setSelection(0, 0);
                const client = clients[document.getElementById('contractClient').value];
                document.getElementById('contractTitle').value = client && client.nombre ? `${tpl.titulo} · ${client.nombre}` : tpl.titulo;
                document.querySelectorAll('.contract-template').forEach((b) => b.classList.remove('ring-2', 'ring-lime-300', 'border-lime-300'));
                button.classList.add('ring-2', 'ring-lime-300', 'border-lime-300');
            });
        });

        // Si el cliente o el valor se eligen después de cargar la plantilla, completa los campos pendientes.
        const refill = () => {
            const html = quill.root.innerHTML;
            const filled = fillTokens(html);
            if (filled !== html) loadHtml(filled);
        };
        ['contractClient', 'contractCurrency', 'contractAmount'].forEach((id) => document.getElementById(id).addEventListener('change', refill));
    })();
</script>
@endsection
