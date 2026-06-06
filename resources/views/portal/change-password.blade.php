<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambiar contraseña</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col">

  {{-- Header verde --}}
  <div class="bg-gradient-to-r from-lime-400 to-lime-500 px-6 py-4 shadow">
    <div class="max-w-lg mx-auto flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center font-bold text-slate-800 text-sm">
        {{ strtoupper(substr(Auth::user()->name ?? 'C', 0, 1)) }}
      </div>
      <div class="font-bold text-slate-900">Portal del Cliente</div>
    </div>
  </div>

  <div class="flex-1 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg border w-full max-w-md p-8">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-lime-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
        <div class="text-xl font-extrabold text-slate-900">Establece tu contraseña</div>
        <div class="text-sm text-slate-500 mt-1">Debes crear una contraseña segura antes de continuar.</div>
      </div>

      @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-rose-700 text-sm mb-5">
          {{ $errors->first() }}
        </div>
      @endif

      @if(session('pw_changed'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm mb-5">
          ✅ {{ session('pw_changed') }}
        </div>
      @endif

      <form method="POST" action="{{ route('portal.auth.change-password.store') }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Nueva contraseña</label>
          <input type="password" name="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-lime-300 outline-none transition-all" placeholder="Mínimo 8 caracteres" required minlength="8">
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">Confirmar contraseña</label>
          <input type="password" name="password_confirmation" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:ring-2 focus:ring-lime-300 outline-none transition-all" placeholder="Repite la contraseña" required minlength="8">
        </div>
        <button type="submit" class="w-full px-4 py-3 rounded-xl bg-lime-300 text-slate-900 font-bold text-sm hover:bg-lime-200 transition-colors mt-2">
          Guardar contraseña y continuar →
        </button>
      </form>
    </div>
  </div>

</body>
</html>
