<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nueva contraseña | Infocus CRM</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
  <div class="w-full max-w-md bg-white rounded-2xl shadow p-6 md:p-8">
    <h1 class="text-xl font-bold mb-1">Establecer nueva contraseña</h1>
    <p class="text-slate-600 text-sm mb-6">Crea una contraseña segura para tu cuenta.</p>

    @if ($errors->any())
      <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm mb-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input name="email" type="email" value="{{ old('email', $email ?? '') }}" class="w-full rounded-lg border p-2.5" required autofocus>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Nueva contraseña</label>
        <input name="password" type="password" class="w-full rounded-lg border p-2.5" required>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Confirmar contraseña</label>
        <input name="password_confirmation" type="password" class="w-full rounded-lg border p-2.5" required>
      </div>

      <button class="w-full px-5 py-2.5 rounded-full bg-lime-300 font-semibold text-slate-900 hover:bg-lime-400 transition">Guardar nueva contraseña</button>
    </form>
  </div>
</body>
</html>
