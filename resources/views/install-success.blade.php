<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación completa | Infocus CRM</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
  <div class="w-full max-w-3xl bg-white rounded-2xl shadow">
    <div class="p-6 md:p-8 border-b">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold">Instalación completa</h1>
          <p class="text-sm text-slate-600 mt-1">Tu CRM ya está listo para usarse.</p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold">OK</div>
      </div>
    </div>
    <div class="p-6 md:p-8 grid grid-cols-1 gap-6">
      <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
        <div class="text-sm font-bold text-slate-700 mb-3">Resumen</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg border bg-white p-3">
            <div class="text-xs text-slate-500">Empresa</div>
            <div class="font-semibold text-slate-800">{{ $company_name }}</div>
          </div>
          <div class="rounded-lg border bg-white p-3">
            <div class="text-xs text-slate-500">URL</div>
            <div class="font-semibold text-slate-800">{{ $app_url }}</div>
          </div>
          <div class="rounded-lg border bg-white p-3">
            <div class="text-xs text-slate-500">DB Host</div>
            <div class="font-semibold text-slate-800">{{ $db_host }}</div>
          </div>
          <div class="rounded-lg border bg-white p-3">
            <div class="text-xs text-slate-500">DB Nombre</div>
            <div class="font-semibold text-slate-800">{{ $db_name }}</div>
          </div>
          <div class="rounded-lg border bg-white p-3 md:col-span-2">
            <div class="text-xs text-slate-500">Administrador</div>
            <div class="font-semibold text-slate-800">{{ $admin_email }}</div>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-between">
        <div class="text-xs text-slate-500">El instalador queda bloqueado automáticamente.</div>
        <a href="{{ route('login.show') }}" class="px-5 py-2.5 rounded-full bg-lime-300 font-semibold text-slate-900 hover:bg-lime-400 transition">Ir al Login</a>
      </div>
    </div>
  </div>
</body>
</html>
