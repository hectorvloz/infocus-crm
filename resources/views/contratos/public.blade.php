@php
    $company = trim((string) ($settings['company_name'] ?? '')) ?: 'Estudio Indigo';
    $signed = (bool) $firmaCliente;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $contrato['titulo'] }} · {{ $company }}</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .doc h1 { display: none; }
        .doc h2 { font-size: .9rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #0f172a; margin: 1.5rem 0 .35rem; }
        .doc h3 { font-weight: 700; color: #0f172a; margin: 1.1rem 0 .3rem; }
        .doc p { margin: 0 0 .65rem; text-align: justify; }
        .doc ol { list-style: decimal; padding-left: 1.25rem; margin: 0 0 .75rem; }
        .doc ul { list-style: disc; padding-left: 1.25rem; margin: 0 0 .75rem; }
        .doc li { margin-bottom: .25rem; }
        .doc .ql-align-center { text-align: center; }
        .doc .ql-align-right { text-align: right; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $company }}" class="h-8 w-auto">
            @else
                <span class="text-lg font-extrabold text-slate-900">{{ $company }}</span>
            @endif
            <a href="{{ route('contratos.public.pdf', ['id' => $contrato['id'], 'token' => $token]) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14"/></svg>
                Descargar PDF
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-6">
        @if(session('signed'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                <strong>¡Listo! Firmaste el contrato.</strong> Te enviamos una copia firmada a tu correo. También puedes descargarla arriba.
            </div>
        @endif

        <div>
            <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-500">Contrato {{ $numero }}</p>
            <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">{{ $contrato['titulo'] }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                Entre <strong class="text-slate-700">{{ $company }}</strong> y <strong class="text-slate-700">{{ $contrato['cliente_nombre'] ?? '' }}</strong>
                @if($valor) · Valor: <strong class="text-slate-700">{{ $valor }}</strong>@endif
            </p>
        </div>

        <article class="doc bg-white rounded-2xl border border-slate-200 shadow-sm px-6 sm:px-12 py-8 sm:py-12 text-[15px] leading-7">
            {!! $contrato['contenido'] !!}
        </article>

        @if($signed)
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-emerald-700">Firmado</p>
                <p class="mt-1 text-slate-700">
                    Firmado electrónicamente por <strong>{{ $firmaCliente['nombre'] ?? '' }}</strong>
                    @if(!empty($firmaCliente['documento'])) (documento {{ $firmaCliente['documento'] }})@endif
                    el {{ \App\Support\ContractDocuments::formatDate($firmaCliente['fecha'] ?? null) }}.
                </p>
            </section>
        @else
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-extrabold text-slate-900">Firmar el contrato</h2>
                <p class="mt-1 text-sm text-slate-500">Escribe tus datos tal como aparecen en tu documento. Tu firma electrónica es válida conforme a la Ley 527 de 1999.</p>

                @if($errors->any())
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('contratos.public.sign', ['id' => $contrato['id'], 'token' => $token]) }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Nombre completo</span>
                            <input name="nombre" value="{{ old('nombre') }}" required autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-slate-900 focus:ring-2 focus:ring-slate-200 outline-none">
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Cédula o NIT</span>
                            <input name="documento" value="{{ old('documento') }}" required inputmode="numeric" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-slate-900 focus:ring-2 focus:ring-slate-200 outline-none">
                        </label>
                    </div>
                    <label class="flex items-start gap-3 text-sm text-slate-600">
                        <input type="checkbox" name="acepto" value="1" required class="mt-1 h-4 w-4 rounded border-slate-300">
                        <span>Leí el contrato completo, acepto sus términos y autorizo que esta firma electrónica tenga los mismos efectos que mi firma manuscrita.</span>
                    </label>
                    <button type="submit" class="w-full sm:w-auto rounded-full bg-slate-900 px-6 py-3 text-sm font-bold text-white hover:bg-slate-800">Firmar contrato</button>
                    <p class="text-xs text-slate-400">Registraremos la fecha, la hora y la dirección IP desde la que firmas como evidencia de la firma.</p>
                </form>
            </section>
        @endif

        <p class="text-center text-xs text-slate-400">{{ $company }}@if(!empty($settings['company_website'])) · {{ $settings['company_website'] }}@endif</p>
    </main>
</body>
</html>
