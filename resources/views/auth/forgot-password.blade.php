<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar contraseña | Infocus CRM</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6 md:p-8">
    <h1 class="text-xl font-bold mb-1">Recuperar contraseña</h1>
    <p class="text-slate-600 text-sm mb-6">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

    @if ($errors->any())
      <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm mb-4">{{ $errors->first() }}</div>
    @endif
    @if (session('status'))
      <div class="bg-green-50 text-green-700 rounded-lg p-3 text-sm mb-4">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border p-2.5" required autofocus>
      </div>
      <button class="w-full px-5 py-2.5 rounded-full bg-lime-300 font-semibold text-slate-900 hover:bg-lime-400 transition">Enviar enlace de recuperación</button>
    </form>

    <div class="mt-4 text-center">
      <a href="{{ route('login.show') }}" class="text-sm text-slate-500 hover:text-slate-900">Volver a iniciar sesión</a>
    </div>
  </div>
</body>
</html>
