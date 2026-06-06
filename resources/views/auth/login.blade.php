<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión | Infocus CRM</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
  <div class="w-full max-w-md flex flex-col items-center gap-6">
    <img src="/uploads/branding/logo-infocus.svg" alt="Infocus CRM" class="w-56">
    <div class="w-full bg-white rounded-2xl shadow p-6 md:p-8">
    <h1 class="text-xl font-bold mb-1">Bienvenido</h1>
    <p class="text-slate-600 text-sm mb-6">Ingresa con tu cuenta. Si eres cliente, entrarás automáticamente a tu portal.</p>
    @if (session('google_error'))
      <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm mb-4">{{ session('google_error') }}</div>
    @endif
    @if ($errors->has('email') && !str_contains(strtolower((string) $errors->first('email')), 'google'))
      <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm mb-4">{{ $errors->first('email') }}</div>
    @endif
    @if (session('status'))
      <div class="bg-green-50 text-green-700 rounded-lg p-3 text-sm mb-4">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('login.perform') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border p-2.5" required autofocus>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <input name="password" type="password" class="w-full rounded-lg border p-2.5" required>
      </div>
      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" name="remember" class="rounded">
          Recordarme
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-slate-500 hover:text-slate-900">Olvidé contraseña</a>
        <button class="px-5 py-2.5 rounded-full bg-lime-300 font-semibold text-slate-900 hover:bg-lime-400 transition">Entrar</button>
      </div>
    </form>

    <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
      <div class="h-px flex-1 bg-slate-200"></div>
      <span>o</span>
      <div class="h-px flex-1 bg-slate-200"></div>
    </div>

    <a href="{{ route('login.google') }}" class="w-full inline-flex items-center justify-center gap-2.5 rounded-full border border-slate-200 bg-white px-5 py-3 font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 transition">
      <iconify-icon icon="logos:google-icon" width="20" height="20" aria-hidden="true"></iconify-icon>
      <span>Continuar con Google</span>
    </a>

      <!-- Magic Link for portal clients -->
      <div class="mt-3 text-center">
        <a href="{{ route('portal.magic-link') }}" class="text-sm text-slate-500 hover:text-slate-900 transition-colors inline-flex items-center gap-1.5">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          ¿Eres cliente? Accede por correo
        </a>
      </div>
  </div>
    </div>
