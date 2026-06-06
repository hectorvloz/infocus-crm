<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato - {{ $contrato['titulo'] }}</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap');
        body { background: white; color: #1e293b; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .font-dancing { font-family: 'Dancing Script', cursive; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="p-12 max-w-4xl mx-auto">
    <div class="mb-8 flex justify-between items-end border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $contrato['titulo'] }}</h1>
            <p class="text-slate-500 mt-1">Cliente: {{ $contrato['cliente_nombre'] }}</p>
        </div>
        <div class="text-right">
            <div class="font-bold text-slate-900">INFOCUS</div>
            <div class="text-sm text-slate-500">{{ \Carbon\Carbon::parse($contrato['created_at'])->format('d M, Y') }}</div>
        </div>
    </div>

    <div class="prose max-w-none mb-16 text-justify">
        {!! $contrato['contenido'] !!}
    </div>

    <div class="grid grid-cols-2 gap-12 mt-12 page-break-inside-avoid">
        <div>
            <div class="h-24 border-b border-slate-900 mb-2 flex flex-col justify-end pb-2">
                @php $empresaSigned = collect($contrato['firmas'] ?? [])->firstWhere('rol', 'Empresa'); @endphp
                @if($empresaSigned)
                    <div class="font-dancing text-2xl text-blue-600 transform -rotate-2">Firmado Digitalmente</div>
                    <div class="text-xs text-slate-400 font-mono mt-1">{{ $empresaSigned['fecha'] }}</div>
                @endif
            </div>
            <div class="font-bold text-slate-900">INFOCUS</div>
            <div class="text-sm text-slate-500">Representante Legal</div>
        </div>
        <div>
            <div class="h-24 border-b border-slate-900 mb-2 flex flex-col justify-end pb-2">
                @php $clienteSigned = collect($contrato['firmas'] ?? [])->firstWhere('rol', 'Cliente'); @endphp
                @if($clienteSigned)
                    <div class="font-dancing text-2xl text-lime-600 transform -rotate-1">Firmado Digitalmente</div>
                    <div class="text-xs text-slate-400 font-mono mt-1">{{ $clienteSigned['fecha'] }}</div>
                @endif
            </div>
            <div class="font-bold text-slate-900">{{ $contrato['cliente_nombre'] }}</div>
            <div class="text-sm text-slate-500">Cliente</div>
        </div>
    </div>

    <div class="fixed bottom-0 left-0 w-full bg-white border-t p-4 text-center no-print">
        <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2 rounded-lg font-bold hover:bg-slate-800 transition-colors">
            Imprimir / Guardar como PDF
        </button>
    </div>
</body>
</html>
