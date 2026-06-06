<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación | Infocus CRM</title>
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-neutral-50 flex items-center justify-center p-6" data-initial-step="{{ $initialStep ?? 1 }}">
  <div class="w-full max-w-3xl bg-white rounded-2xl shadow">
    <div class="p-6 md:p-8 border-b">
      <h1 class="text-2xl font-bold">Instalación</h1>
      <p class="text-sm text-slate-600 mt-1">Completa los datos para configurar MySQL y crear el administrador.</p>
    </div>
    <form method="POST" action="{{ route('install.store') }}" class="p-6 md:p-8 grid grid-cols-1 gap-6" id="install-form">
      @csrf
      <div>
        @if (!empty($errorMessage))
          <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm">{{ $errorMessage }}</div>
        @elseif ($errors->any())
          <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
          <div class="bg-green-50 text-green-700 rounded-lg p-3 text-sm">{{ session('status') }}</div>
        @endif
      </div>
      <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
        <div class="flex items-center justify-between mb-3">
          <div class="text-sm font-bold text-slate-700">Progreso</div>
          <div class="text-xs text-slate-500"><span id="step-label">Paso 1</span> de 4</div>
        </div>
        <div class="h-2 bg-white rounded-full border overflow-hidden">
          <div id="step-bar" class="h-full bg-slate-900 w-1/4"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 text-xs font-semibold text-slate-600">
          <div class="flex items-center gap-2"><span id="step-dot-1" class="w-2 h-2 rounded-full bg-slate-900"></span>Requisitos</div>
          <div class="flex items-center gap-2"><span id="step-dot-2" class="w-2 h-2 rounded-full bg-slate-200"></span>Base de datos</div>
          <div class="flex items-center gap-2"><span id="step-dot-3" class="w-2 h-2 rounded-full bg-slate-200"></span>Aplicación</div>
          <div class="flex items-center gap-2"><span id="step-dot-4" class="w-2 h-2 rounded-full bg-slate-200"></span>Instalar</div>
        </div>
      </div>
      <div data-step="1" class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div class="flex items-center justify-between bg-white rounded-lg border p-3">
          <span>PHP ≥ 8.2 ({{ $requirements['php']['current'] ?? '' }})</span>
          <span class="w-2 h-2 rounded-full {{ ($requirements['php']['ok'] ?? false) ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
        </div>
        @foreach(($requirements['extensions'] ?? []) as $ext)
          <div class="flex items-center justify-between bg-white rounded-lg border p-3">
            <span>{{ $ext['label'] }}</span>
            <span class="w-2 h-2 rounded-full {{ $ext['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
          </div>
        @endforeach
        @foreach(($requirements['permissions'] ?? []) as $perm)
          <div class="flex items-center justify-between bg-white rounded-lg border p-3">
            <span>{{ $perm['label'] }} writable</span>
            <span class="w-2 h-2 rounded-full {{ $perm['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
          </div>
        @endforeach
      </div>
      <div data-step="2" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium mb-1">DB Host</label>
          <input name="db_host" value="{{ $formData['db_host'] ?? old('db_host','localhost') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">DB Puerto</label>
          <input name="db_port" value="{{ $formData['db_port'] ?? old('db_port','3306') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">DB Nombre</label>
          <input name="db_name" value="{{ $formData['db_name'] ?? old('db_name') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">DB Usuario</label>
          <input name="db_user" value="{{ $formData['db_user'] ?? old('db_user') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">DB Password</label>
          <input name="db_pass" type="password" class="w-full rounded-lg border p-2.5">
        </div>
        <div class="md:col-span-2 flex items-center gap-3">
          <button type="button" id="test-db" class="px-4 py-2 rounded-full border text-sm font-semibold">Probar conexión</button>
          <span id="test-db-result" class="text-sm"></span>
        </div>
      </div>
      <div data-step="3" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">APP URL</label>
          <input name="app_url" value="{{ $formData['app_url'] ?? old('app_url', url('/')) }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Nombre de la empresa</label>
          <input name="company_name" value="{{ $formData['company_name'] ?? old('company_name', 'Infocus CRM') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Admin Nombre</label>
          <input name="admin_name" value="{{ $formData['admin_name'] ?? old('admin_name','Administrador') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Admin Email</label>
          <input name="admin_email" type="email" value="{{ $formData['admin_email'] ?? old('admin_email') }}" class="w-full rounded-lg border p-2.5" required>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Admin Password</label>
          <input name="admin_password" type="password" class="w-full rounded-lg border p-2.5" required>
        </div>
      </div>
      <div data-step="4" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2 bg-slate-50 rounded-xl border border-slate-100 p-4">
          <div class="text-sm font-bold text-slate-700 mb-3">Resumen</div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="rounded-lg border bg-white p-3"><div class="text-xs text-slate-500">Empresa</div><div id="sum-company" class="font-semibold text-slate-800"></div></div>
            <div class="rounded-lg border bg-white p-3"><div class="text-xs text-slate-500">URL</div><div id="sum-url" class="font-semibold text-slate-800"></div></div>
            <div class="rounded-lg border bg-white p-3"><div class="text-xs text-slate-500">DB Host</div><div id="sum-db-host" class="font-semibold text-slate-800"></div></div>
            <div class="rounded-lg border bg-white p-3"><div class="text-xs text-slate-500">DB Nombre</div><div id="sum-db-name" class="font-semibold text-slate-800"></div></div>
            <div class="rounded-lg border bg-white p-3"><div class="text-xs text-slate-500">Admin</div><div id="sum-admin" class="font-semibold text-slate-800"></div></div>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-between">
        <button type="button" id="prev-step" class="px-4 py-2 rounded-full border text-sm font-semibold">Atrás</button>
        <div class="flex items-center gap-3">
          <button type="button" id="next-step" class="px-5 py-2.5 rounded-full bg-slate-900 text-white font-semibold">Siguiente</button>
          <button type="submit" id="submit-install" class="hidden px-5 py-2.5 rounded-full bg-lime-300 font-semibold text-slate-900 hover:bg-lime-400 transition">Instalar</button>
        </div>
      </div>
    </form>
  </div>
  <script>
    const TEST_DB_URL = "{{ route('install.test-db') }}";
    const totalSteps = 4;
    const initialStep = Number(document.body.dataset.initialStep || 1);
    let currentStep = initialStep;
    const stepLabel = document.getElementById('step-label');
    const stepBar = document.getElementById('step-bar');
    const prevBtn = document.getElementById('prev-step');
    const nextBtn = document.getElementById('next-step');
    const submitBtn = document.getElementById('submit-install');

    function updateSteps(){
      document.querySelectorAll('[data-step]').forEach(section=>{
        section.classList.toggle('hidden', Number(section.dataset.step) !== currentStep);
      });
      stepLabel.textContent = `Paso ${currentStep}`;
      stepBar.style.width = `${(currentStep/totalSteps)*100}%`;
      for(let i=1;i<=totalSteps;i++){
        const dot = document.getElementById(`step-dot-${i}`);
        if (dot) dot.className = `w-2 h-2 rounded-full ${i<=currentStep ? 'bg-slate-900' : 'bg-slate-200'}`;
      }
      prevBtn.disabled = currentStep === 1;
      prevBtn.classList.toggle('opacity-50', currentStep === 1);
      nextBtn.classList.toggle('hidden', currentStep === totalSteps);
      submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
      if (currentStep === totalSteps) fillSummary();
    }

    function fillSummary(){
      const form = document.getElementById('install-form');
      const fd = new FormData(form);
      document.getElementById('sum-company').textContent = fd.get('company_name') || '—';
      document.getElementById('sum-url').textContent = fd.get('app_url') || '—';
      document.getElementById('sum-db-host').textContent = fd.get('db_host') || '—';
      document.getElementById('sum-db-name').textContent = fd.get('db_name') || '—';
      document.getElementById('sum-admin').textContent = fd.get('admin_name') ? `${fd.get('admin_name')} (${fd.get('admin_email')||''})` : '—';
    }

    prevBtn.addEventListener('click', ()=>{
      if (currentStep > 1) { currentStep--; updateSteps(); }
    });
    nextBtn.addEventListener('click', ()=>{
      if (currentStep < totalSteps) { currentStep++; updateSteps(); }
    });
    updateSteps();

    document.getElementById('test-db')?.addEventListener('click', async ()=>{
      const form = document.getElementById('install-form');
      const fd = new FormData(form);
      const res = await fetch(TEST_DB_URL, {
        method:'POST',
        credentials: 'same-origin',
        headers:{
          'X-CSRF-TOKEN':'{{ csrf_token() }}',
          'X-Requested-With':'XMLHttpRequest'
        },
        body: new URLSearchParams({
          db_host: fd.get('db_host'),
          db_port: fd.get('db_port'),
          db_name: fd.get('db_name'),
          db_user: fd.get('db_user'),
          db_pass: fd.get('db_pass') || ''
        })
      });
      const out = document.getElementById('test-db-result');
      if(res.ok){
        const js = await res.json().catch(()=>({ok:false,message:'Respuesta inválida'}));
        if(js.ok){ out.textContent = 'Conexión exitosa'; out.className='text-emerald-600 text-sm ml-2'; }
        else { out.textContent = 'Error: ' + (js.message||''); out.className='text-rose-600 text-sm ml-2'; }
      } else {
        const js = await res.json().catch(()=>({message:`HTTP ${res.status}`})); 
        out.textContent = 'Error: ' + (js.message||''); out.className='text-rose-600 text-sm ml-2';
      }
    });
  </script>
</body>
</html>
