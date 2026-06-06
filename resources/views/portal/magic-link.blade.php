<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceder al Portal</title>
    @include('partials.favicon')
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-[#182031] to-slate-800 flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    <!-- Logo / Branding -->
    <div class="flex flex-col items-center mb-8">
        @php $portalLogoUrl = app_public_asset_url($settings['logo_small'] ?? ($settings['logo_large'] ?? ($settings['logo'] ?? ''))); @endphp
        @if($portalLogoUrl)
            <img src="{{ $portalLogoUrl }}" alt="Logo empresa" class="h-14 w-auto mb-3 object-contain">
        @else
            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
        <h1 class="text-xl font-bold text-white">Portal Cliente</h1>
        <p class="text-sm text-white/50 mt-1">Acceso por enlace mágico</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8">

        @if(session('magic_sent'))
            <!-- Success state -->
            <div class="text-center py-4">
                <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900 mb-2">¡Revisa tu correo!</h2>
                <p class="text-sm text-slate-500">{{ session('magic_sent') }}</p>
                <p class="text-xs text-slate-400 mt-3">El enlace es de un solo uso y puede tardar unos minutos en llegar.</p>
            </div>
        @else
            <h2 class="text-lg font-bold text-slate-900 mb-1">Enviar enlace de acceso</h2>
            <p class="text-sm text-slate-500 mb-6">Ingresa tu correo y te enviaremos un enlace directo a tu portal.</p>

            <form method="POST" action="{{ route('portal.magic-link.send') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="tu@correo.com"
                        class="block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-lime-400 focus:border-lime-400 transition"
                    >
                    @if($errors->has('email') && !str_contains(strtolower((string) $errors->first('email')), 'google'))
                        <p class="mt-2 text-xs text-red-600">{{ $errors->first('email') }}</p>
                    @endif
                </div>

                <a href="{{ route('portal.magic-link.google') }}"
                   class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-800 font-semibold text-sm hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.5 3.9-5.4 3.9-3.2 0-5.9-2.7-5.9-6s2.7-6 5.9-6c1.8 0 3 .8 3.7 1.4l2.5-2.4C16.7 3.5 14.5 2.6 12 2.6 6.9 2.6 2.8 6.8 2.8 12s4.1 9.4 9.2 9.4c5.3 0 8.8-3.7 8.8-9 0-.6-.1-1.1-.2-1.6H12z"/>
                    </svg>
                    Iniciar con Google
                </a>

                <button type="submit"
                        class="w-full py-2.5 rounded-xl bg-[#182031] text-white font-semibold text-sm hover:bg-slate-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-400">
                    Enviar enlace
                </button>
            </form>
        @endif

        <!-- Back to login -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <a href="{{ route('login.show') }}" class="text-sm text-slate-500 hover:text-slate-900 transition-colors inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al inicio de sesión
            </a>
        </div>
    </div>
</div>

</body>
</html>
