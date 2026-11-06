@extends('layouts.app')

@section('title', $contrato['titulo'])

@section('content')
@php
    $settingsDoc = \App\Support\ContractDocuments::settings();
    $company = trim((string) ($settingsDoc['company_name'] ?? '')) ?: 'Estudio Indigo';
    $estado = $contrato['estado'] ?? 'Borrador';
    $firmaEmpresa = \App\Support\ContractDocuments::signature($contrato, 'Empresa');
    $firmaCliente = \App\Support\ContractDocuments::signature($contrato, 'Cliente');
    $valor = \App\Support\ContractDocuments::money($contrato);
    $clientEmail = \App\Support\ContractDocuments::clientEmail($contrato);
    $publicUrl = !empty($contrato['public_token']) && $estado !== 'Borrador' ? \App\Support\ContractDocuments::publicUrl($contrato) : null;
    $estadoLabel = match ($estado) { 'Firmado' => 'Firmado', 'Enviado' => 'Enviado · pendiente de firma', 'Rechazado' => 'Rechazado', default => 'Borrador' };
    $estadoClass = match ($estado) { 'Firmado' => 'bg-emerald-100 text-emerald-700', 'Enviado' => 'bg-blue-100 text-blue-700', 'Rechazado' => 'bg-rose-100 text-rose-700', default => 'bg-slate-100 text-slate-600' };
    $sendConfirm = $clientEmail
        ? 'Se enviará el contrato con el PDF y el enlace de firma a ' . $clientEmail . '. ¿Continuar?'
        : 'El cliente no tiene correo de contacto. Agrégalo en su ficha para poder enviarlo.';
@endphp
<div class="max-w-4xl mx-auto space-y-6 pb-20">
    <div class="flex flex-col gap-4">
        <a href="{{ route('contratos.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors inline-flex items-center gap-1 text-sm font-medium w-fit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Contratos
        </a>
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ \App\Support\ContractDocuments::number($contrato) }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $estadoClass }}">{{ $estadoLabel }}</span>
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $contrato['titulo'] }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($estado !== 'Firmado')
                    <a href="{{ route('contratos.edit', $contrato['id']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors">Editar</a>
                @endif
                <a href="{{ route('contratos.pdf', $contrato['id']) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-medium shadow-sm transition-colors inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14"/></svg>
                    Descargar PDF
                </a>
                @if($estado !== 'Firmado')
                    <form action="{{ route('contratos.enviar', $contrato['id']) }}" method="POST" data-confirm="{{ $sendConfirm }}" onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        <button type="submit" class="bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 px-5 py-2 rounded-xl font-bold shadow-sm transition-colors inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $estado === 'Enviado' ? 'Reenviar al cliente' : 'Publicar y enviar' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if($publicUrl)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-soft px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-slate-900">Enlace del cliente</div>
                <div class="text-xs text-slate-500">
                    @if(!empty($contrato['enviado_a']))Enviado a {{ $contrato['enviado_a'] }} el {{ \App\Support\ContractDocuments::formatDate($contrato['enviado_at'] ?? null) }}. @endif
                    Con este enlace el cliente lee, descarga y firma el contrato. También lo ve en su portal.
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input id="contractPublicUrl" readonly value="{{ $publicUrl }}" class="w-56 sm:w-72 rounded-xl border-slate-200 bg-slate-50 text-xs text-slate-600" onclick="this.select()">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('contractPublicUrl').value).then(() => { this.textContent = 'Copiado'; setTimeout(() => this.textContent = 'Copiar', 1500); })" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Copiar</button>
            </div>
        </div>
    @endif

    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-soft border border-slate-100">
        <div class="border-b border-slate-100 pb-6 mb-8 flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="text-sm text-slate-500 space-y-1">
                <div><span class="font-semibold text-slate-700">Cliente:</span> {{ $contrato['cliente_nombre'] }}</div>
                @if(!empty($contrato['proyecto_nombre']))<div><span class="font-semibold text-slate-700">Proyecto:</span> {{ $contrato['proyecto_nombre'] }}</div>@endif
                <div><span class="font-semibold text-slate-700">Creado:</span> {{ \App\Support\ContractDocuments::formatDate($contrato['created_at'] ?? null) }}</div>
            </div>
            @if($valor)
                <div class="sm:text-right">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Valor</div>
                    <div class="text-xl font-bold text-slate-900">{{ $valor }}</div>
                </div>
            @endif
        </div>

        <div class="contract-doc max-w-none text-slate-700 leading-7">
            {!! $contrato['contenido'] !!}
        </div>

        <div class="border-t border-slate-100 pt-8 mt-12">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">Firmas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                    <div class="h-20 border-b-2 border-slate-200 mb-2 flex flex-col justify-end pb-2">
                        @if($firmaEmpresa)
                            <div class="text-2xl italic text-blue-900">{{ $firmaEmpresa['nombre'] ?? $company }}</div>
                        @elseif($estado !== 'Rechazado')
                            <form action="{{ route('contratos.firmar', $contrato['id']) }}" method="POST" data-confirm="¿Firmar este contrato en nombre de {{ $company }}?" onsubmit="return confirm(this.dataset.confirm)">
                                @csrf
                                <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-slate-800 underline">Firmar como {{ $company }}</button>
                            </form>
                        @endif
                    </div>
                    <div class="font-bold text-slate-900">{{ $company }}</div>
                    <div class="text-sm text-slate-500">El contratista</div>
                    @if($firmaEmpresa)<div class="mt-1 text-xs text-slate-400">Firmado por {{ $firmaEmpresa['nombre'] ?? '' }} el {{ \App\Support\ContractDocuments::formatDate($firmaEmpresa['fecha'] ?? null) }}</div>@endif
                </div>
                <div>
                    <div class="h-20 border-b-2 border-slate-200 mb-2 flex flex-col justify-end pb-2">
                        @if($firmaCliente)
                            <div class="text-2xl italic text-blue-900">{{ $firmaCliente['nombre'] ?? '' }}</div>
                        @else
                            <div class="text-sm text-slate-400">Pendiente de firma{{ $publicUrl ? ' · el cliente firma desde su enlace o su portal' : '' }}</div>
                        @endif
                    </div>
                    <div class="font-bold text-slate-900">{{ $contrato['cliente_nombre'] }}</div>
                    <div class="text-sm text-slate-500">El contratante</div>
                    @if($firmaCliente)
                        <div class="mt-1 text-xs text-slate-400">
                            {{ $firmaCliente['nombre'] ?? '' }}@if(!empty($firmaCliente['documento'])) · Doc. {{ $firmaCliente['documento'] }}@endif · {{ \App\Support\ContractDocuments::formatDate($firmaCliente['fecha'] ?? null) }}@if(!empty($firmaCliente['ip'])) · IP {{ $firmaCliente['ip'] }}@endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .contract-doc h1 { font-size: 1.5rem; font-weight: 800; text-align: center; color: #0f172a; margin: 0 0 1.5rem; }
    .contract-doc h2 { font-size: .9rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #0f172a; margin: 1.5rem 0 .35rem; }
    .contract-doc h3 { font-weight: 700; color: #0f172a; margin: 1.1rem 0 .3rem; }
    .contract-doc p { margin: 0 0 .65rem; text-align: justify; }
    .contract-doc ol { list-style: decimal; padding-left: 1.25rem; margin: 0 0 .75rem; }
    .contract-doc ul { list-style: disc; padding-left: 1.25rem; margin: 0 0 .75rem; }
</style>
@endsection
