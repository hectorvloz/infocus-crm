@extends('layouts.app')

@section('title', $contrato['titulo'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('contratos.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors flex items-center gap-1 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver a Contratos
                </a>
                <span class="text-slate-300">|</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ 
                    match($contrato['estado'] ?? 'Borrador') {
                        'Firmado' => 'bg-lime-100 text-lime-700',
                        'Enviado' => 'bg-blue-100 text-blue-700',
                        'Rechazado' => 'bg-red-100 text-red-700',
                        default => 'bg-slate-100 text-slate-600'
                    }
                }}">
                    {{ $contrato['estado'] ?? 'Borrador' }}
                </span>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $contrato['titulo'] }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('contratos.edit', $contrato['id']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">
                Editar
            </a>
            <a href="{{ route('contratos.pdf', $contrato['id']) }}" target="_blank" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir / PDF
            </a>
            @if(($contrato['estado'] ?? '') !== 'Firmado')
                <form action="{{ route('contratos.firmar', $contrato['id']) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="rol" value="Cliente">
                    <button type="submit" onclick="return confirm('¿Confirmas la firma digital de este documento?')" class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-5 py-2 rounded-xl font-bold shadow-sm transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Firmar Digitalmente
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Contract Content -->
    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-soft border border-slate-100 min-h-[800px] relative">
        <!-- Header Info -->
        <div class="border-b border-slate-100 pb-6 mb-8 flex justify-between items-start">
            <div>
                <div class="text-sm text-slate-400 uppercase tracking-wider font-bold mb-1">Contrato</div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $contrato['titulo'] }}</h2>
                <div class="mt-2 text-slate-500 text-sm">
                    <span class="font-medium text-slate-700">Cliente:</span> {{ $contrato['cliente_nombre'] }}
                    @if(!empty($contrato['proyecto_nombre']))
                        <span class="mx-2">•</span>
                        <span class="font-medium text-slate-700">Proyecto:</span> {{ $contrato['proyecto_nombre'] }}
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-slate-400 uppercase tracking-wider font-bold mb-1">Fecha</div>
                <div class="font-mono text-slate-700">{{ \Carbon\Carbon::parse($contrato['created_at'])->format('d M, Y') }}</div>
                @if(!empty($contrato['monto']))
                    <div class="mt-2 text-xl font-bold text-slate-900">
                        ${{ number_format($contrato['monto'], 2) }} <span class="text-sm text-slate-500 font-normal">{{ $contrato['moneda'] ?? 'MXN' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Body -->
        <div class="prose prose-slate max-w-none mb-12">
            {!! $contrato['contenido'] !!}
        </div>

        <!-- Signatures -->
        <div class="border-t border-slate-100 pt-8 mt-12">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">Firmas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Company Signature -->
                <div>
                    <div class="h-24 border-b-2 border-slate-200 mb-2 flex flex-col justify-end pb-2">
                        @php $empresaSigned = collect($contrato['firmas'] ?? [])->firstWhere('rol', 'Empresa'); @endphp
                        @if($empresaSigned)
                            <div class="font-dancing text-3xl text-blue-600 transform -rotate-2">Firmado Digitalmente</div>
                            <div class="text-xs text-blue-400 font-mono mt-1">{{ $empresaSigned['fecha'] }} - IP: {{ $empresaSigned['ip'] }}</div>
                        @else
                            <form action="{{ route('contratos.firmar', $contrato['id']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="rol" value="Empresa">
                                <button type="submit" class="text-sm text-slate-400 hover:text-slate-600 underline">Firmar como Empresa</button>
                            </form>
                        @endif
                    </div>
                    <div class="font-bold text-slate-900">INFOCUS</div>
                    <div class="text-sm text-slate-500">Representante Legal</div>
                </div>

                <!-- Client Signature -->
                <div>
                    <div class="h-24 border-b-2 border-slate-200 mb-2 flex flex-col justify-end pb-2">
                         @php $clienteSigned = collect($contrato['firmas'] ?? [])->firstWhere('rol', 'Cliente'); @endphp
                        @if($clienteSigned)
                            <div class="font-dancing text-3xl text-lime-600 transform -rotate-1">Firmado Digitalmente</div>
                            <div class="text-xs text-lime-600 font-mono mt-1">{{ $clienteSigned['fecha'] }} - IP: {{ $clienteSigned['ip'] }}</div>
                        @else
                            <div class="text-slate-300 italic">Pendiente de firma</div>
                        @endif
                    </div>
                    <div class="font-bold text-slate-900">{{ $contrato['cliente_nombre'] }}</div>
                    <div class="text-sm text-slate-500">Cliente</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap');
.font-dancing { font-family: 'Dancing Script', cursive; }
</style>
@endsection
