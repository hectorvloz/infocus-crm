@extends('layouts.app')
@section('title', 'Mi perfil')

@section('content')
@php
  $name = $identity['name'] ?? 'Usuario';
  $email = $identity['email'] ?? 'Sin email';
  $phone = $identity['phone'] ?? '';
  $profileInfo = $identity['profile_info'] ?? '';
  $profilePhoto = $identity['profile_photo'] ?? '';
  $role = $identity['role'] ?? 'admin';
  $initials = strtoupper(substr($name, 0, 2));
  $workedCalendarJson = json_encode($workedCalendar ?? ['month' => now()->format('Y-m'), 'days' => [], 'total_label' => '0H:0M'], JSON_UNESCAPED_UNICODE);
@endphp

<div class="space-y-6">
  @if(session('success'))
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
      {{ session('success') }}
    </div>
  @endif

  <div class="rounded-3xl border border-slate-200 bg-white p-5 md:p-7 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-center gap-5">
      <div class="w-20 h-20 rounded-full overflow-hidden border border-slate-200 bg-slate-900 text-white flex items-center justify-center text-2xl font-extrabold">
        @if($profilePhoto !== '')
          <img src="{{ $profilePhoto }}" alt="Foto de perfil" class="w-full h-full object-cover">
        @else
          {{ $initials }}
        @endif
      </div>
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-extrabold text-slate-900 truncate">{{ $name }}</h2>
        <p class="text-sm text-slate-500 truncate mt-1">{{ $email }}</p>
        <div class="mt-2 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 uppercase tracking-wider">{{ $role }}</div>
      </div>
      <button type="button" id="openProfileEditBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
        <span>Editar datos de contacto</span>
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <button type="button" id="openWorkedHoursCalendar" class="rounded-2xl border p-4 text-center transition-colors" style="background:#f3fea4;border-color:#d6e57a;">
      <div class="text-xs uppercase tracking-wider font-bold" style="color:#101729;">Horas trabajadas (mensual)</div>
      <div class="mt-2 text-4xl font-black tracking-tight font-mono text-center" style="color:#101729;">{{ $metrics['worked_label'] }}</div>
      <div class="mt-1 text-[11px] font-semibold text-center" style="color:#101729;">Pulsa para ver el calendario de horas</div>
    </button>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Proyectos vinculados</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900">{{ $metrics['projects_count'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <div class="text-xs uppercase tracking-wider font-bold text-slate-500">Tareas asignadas</div>
      <div class="mt-2 text-2xl font-extrabold text-slate-900">{{ $metrics['tasks_count'] }}</div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
    <section class="rounded-2xl border border-slate-200 bg-white p-5">
      <h3 class="text-lg font-extrabold text-slate-900">Proyectos donde eres responsable</h3>
      <div class="mt-4 space-y-2 max-h-[420px] overflow-y-auto custom-scroll pr-1">
        @forelse($assignedProjects as $project)
          <a href="{{ route('proyectos.index') }}?view=kanban&open_project={{ urlencode($project['id']) }}" class="block rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 hover:bg-white transition-colors">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="font-bold text-slate-900 truncate">{{ $project['title'] }}</div>
                <div class="text-xs text-slate-500 mt-1 truncate">{{ $project['client'] }} · {{ $project['stage'] }}</div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex w-8 h-8 rounded-full border border-slate-200 bg-white items-center justify-center text-slate-600">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h8l-1 8 10-12h-8z"/></svg>
                </span>
                <span class="text-xs font-bold px-2 py-1 rounded-full border border-slate-200 bg-white text-slate-600">{{ $project['priority'] }}</span>
              </div>
            </div>
          </a>
        @empty
          <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-sm text-slate-500 text-center">No hay proyectos asignados para este perfil.</div>
        @endforelse
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
      <h3 class="text-lg font-extrabold text-slate-900">Tareas anexadas a tu perfil</h3>
      <div class="mt-4 space-y-2 max-h-[420px] overflow-y-auto custom-scroll pr-1">
        @forelse($assignedTasks as $task)
          <a href="{{ route('proyectos.index') }}?view=tareas" class="block rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 hover:bg-white transition-colors">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-bold text-slate-900 truncate">{{ $task['task_text'] }}</div>
                <div class="text-xs text-slate-500 mt-1 truncate">{{ $task['project_title'] }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ $task['due_date'] ? ('Vence: '.\Carbon\Carbon::parse($task['due_date'])->format('d/m/Y')) : 'Sin fecha de vencimiento' }}</div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <span class="inline-flex w-8 h-8 rounded-full border border-slate-200 bg-white items-center justify-center text-slate-600">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3 8-8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <span class="text-xs font-bold px-2 py-1 rounded-full border {{ $task['done'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">{{ $task['done'] ? 'Completada' : 'Pendiente' }}</span>
                <span class="text-xs font-bold text-slate-600">{{ $task['priority'] }}</span>
              </div>
            </div>
          </a>
        @empty
          <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-sm text-slate-500 text-center">No hay tareas asignadas para este perfil.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>

<div id="profileEditModal" class="hidden fixed inset-0 z-50">
  <div id="profileEditBackdrop" class="absolute inset-0 bg-slate-900/40"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-extrabold text-slate-900">Editar mi perfil</h3>
        <button type="button" id="closeProfileEditBtn" class="w-9 h-9 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50">✕</button>
      </div>
      <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
        @csrf
        <input type="hidden" name="remove_profile_photo" id="removeProfilePhotoInput" value="0">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="block">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Nombre</span>
            <input name="name" type="text" value="{{ old('name', $name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700" required>
          </label>
          <label class="block">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</span>
            <input name="email" type="email" value="{{ old('email', $email) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700" required>
          </label>
          <label class="block">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Celular</span>
            <input name="phone" type="text" value="{{ old('phone', $phone) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700" placeholder="Ej: +57 300 000 0000">
          </label>
          <div class="block md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Foto de perfil</span>
            <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 p-3">
              <div class="flex items-center gap-3 flex-wrap">
                <label for="profilePhotoInput" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-100 cursor-pointer">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h4l2-2h4l2 2h4v12H4z"/><circle cx="12" cy="13" r="3"/></svg>
                  Seleccionar archivo
                </label>
                <button type="button" id="removeProfilePhotoBtn" class="hidden inline-flex items-center gap-2 px-3 py-2 rounded-full border border-rose-200 bg-white text-sm font-bold text-rose-600 hover:bg-rose-50">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
                  Quitar foto
                </button>
                <span id="profilePhotoFileLabel" class="text-sm text-slate-500">Ningun archivo seleccionado</span>
              </div>
              <input id="profilePhotoInput" name="profile_photo" type="file" accept="image/*" class="hidden">
              <div id="profilePhotoPreview" class="mt-3 hidden items-center gap-3 rounded-xl border border-slate-200 bg-white p-2">
                <img id="profilePhotoPreviewImg" src="" alt="Vista previa" class="w-16 h-16 rounded-xl object-cover border border-slate-200">
                <div id="profilePhotoPreviewText" class="text-xs font-semibold text-slate-500">Vista previa de la foto</div>
              </div>
            </div>
          </div>
        </div>

        <label class="block">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Informacion del perfil</span>
          <textarea name="profile_info" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700" placeholder="Cargo, especialidad o descripcion breve">{{ old('profile_info', $profileInfo) }}</textarea>
        </label>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Cambio de clave</div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block">
              <span class="text-xs font-semibold text-slate-500">Nueva clave</span>
              <input name="new_password" type="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700">
            </label>
            <label class="block">
              <span class="text-xs font-semibold text-slate-500">Confirmar clave</span>
              <input name="new_password_confirmation" type="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700">
            </label>
          </div>
        </div>

        @if($errors->any())
          <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            @foreach($errors->all() as $error)
              <div>{{ $error }}</div>
            @endforeach
          </div>
        @endif

        <div class="flex items-center justify-end gap-2">
          <button type="button" id="cancelProfileEditBtn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
          <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full border border-slate-200 bg-[#ecfe88] text-sm font-semibold text-slate-900 hover:bg-[#d9f99d]">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="workedHoursCalendarModal" class="hidden fixed inset-0 z-50">
  <div id="workedHoursBackdrop" class="absolute inset-0 bg-slate-900/45"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-5xl max-h-[92vh] rounded-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden flex flex-col">
      <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-none">
        <div>
          <h3 class="text-lg font-extrabold text-slate-900">Horas trabajadas por dia</h3>
          <p class="text-xs text-slate-500">Consulta tus horas y cambia entre meses.</p>
        </div>
        <button type="button" id="closeWorkedHoursModal" class="w-9 h-9 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50">✕</button>
      </div>
      <div class="p-4 sm:p-6 overflow-y-auto flex-1">
        <div class="flex items-center justify-between gap-3 mb-4">
          <button type="button" id="workedHoursPrevMonth" class="w-10 h-10 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">‹</button>
          <div id="workedHoursMonthLabel" class="text-lg font-black text-slate-900"></div>
          <button type="button" id="workedHoursNextMonth" class="w-10 h-10 rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">›</button>
        </div>
        <div class="grid grid-cols-7 gap-1 sm:gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
          <div class="text-center">Lun</div><div class="text-center">Mar</div><div class="text-center">Mie</div><div class="text-center">Jue</div><div class="text-center">Vie</div><div class="text-center">Sab</div><div class="text-center">Dom</div>
        </div>
        <div id="workedHoursCalendarGrid" class="grid grid-cols-7 gap-1 sm:gap-2"></div>
        <div class="mt-4 rounded-2xl border px-4 py-3" style="background:#f3fea4;border-color:#d6e57a;">
          <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-2">
            <div>
              <div class="text-xs uppercase tracking-wider font-bold" style="color:#101729;">Total de Horas mensual</div>
              <div id="workedHoursMonthlyTotal" class="text-4xl font-black font-mono tracking-tight text-center md:text-left" style="color:#101729;">0H:0M</div>
            </div>
            <div class="text-sm font-semibold text-center md:text-right" style="color:#101729;">
              Hora promedio por dia:
              <span id="workedHoursDailyAverage" class="ml-1 font-black font-mono" style="color:#101729;">0H:0M</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const openBtn = document.getElementById('openProfileEditBtn');
    const modal = document.getElementById('profileEditModal');
    const closeBtn = document.getElementById('closeProfileEditBtn');
    const cancelBtn = document.getElementById('cancelProfileEditBtn');
    const backdrop = document.getElementById('profileEditBackdrop');
    const fileInput = document.getElementById('profilePhotoInput');
    const fileLabel = document.getElementById('profilePhotoFileLabel');
    const previewWrap = document.getElementById('profilePhotoPreview');
    const previewImg = document.getElementById('profilePhotoPreviewImg');
    const previewText = document.getElementById('profilePhotoPreviewText');
    const removeBtn = document.getElementById('removeProfilePhotoBtn');
    const removeInput = document.getElementById('removeProfilePhotoInput');

    const workedBtn = document.getElementById('openWorkedHoursCalendar');
    const workedModal = document.getElementById('workedHoursCalendarModal');
    const workedBackdrop = document.getElementById('workedHoursBackdrop');
    const workedClose = document.getElementById('closeWorkedHoursModal');
    const workedPrev = document.getElementById('workedHoursPrevMonth');
    const workedNext = document.getElementById('workedHoursNextMonth');
    const workedLabel = document.getElementById('workedHoursMonthLabel');
    const workedGrid = document.getElementById('workedHoursCalendarGrid');
    const workedTotal = document.getElementById('workedHoursMonthlyTotal');
    const workedAverage = document.getElementById('workedHoursDailyAverage');

    if (!openBtn || !modal) return;

    const open = () => modal.classList.remove('hidden');
    const close = () => modal.classList.add('hidden');

    openBtn.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);

    const initialPhoto = @json($profilePhoto);
    if (initialPhoto) {
      previewWrap?.classList.remove('hidden');
      previewWrap?.classList.add('flex');
      if (previewImg) previewImg.src = initialPhoto;
      if (previewText) previewText.textContent = 'Foto actual del perfil';
      if (removeBtn) removeBtn.classList.remove('hidden');
      if (fileLabel) fileLabel.textContent = 'Foto actual cargada';
    }

    fileInput?.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      if (removeInput) removeInput.value = '0';
      if (fileLabel) fileLabel.textContent = file.name;
      if (removeBtn) removeBtn.classList.remove('hidden');
      const reader = new FileReader();
      reader.onload = () => {
        if (previewImg) previewImg.src = String(reader.result || '');
        if (previewText) previewText.textContent = 'Foto seleccionada para guardar';
        previewWrap?.classList.remove('hidden');
        previewWrap?.classList.add('flex');
      };
      reader.readAsDataURL(file);
    });

    removeBtn?.addEventListener('click', () => {
      if (fileInput) fileInput.value = '';
      if (previewImg) previewImg.src = '';
      previewWrap?.classList.add('hidden');
      previewWrap?.classList.remove('flex');
      if (fileLabel) fileLabel.textContent = 'Ningun archivo seleccionado';
      if (removeInput) removeInput.value = '1';
      removeBtn.classList.add('hidden');
    });

    const monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    let calendarMonth = (function(){
      const base = @json($workedCalendar['month'] ?? now()->format('Y-m'));
      const parts = String(base).split('-');
      return new Date(Number(parts[0] || new Date().getFullYear()), Number(parts[1] || (new Date().getMonth()+1)) - 1, 1);
    })();
    let workedDays = @json($workedCalendar['days'] ?? []);

    function monthKey(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    }

    async function loadWorkedHours() {
      try {
        const response = await fetch(`/api/mi-perfil/horas?month=${encodeURIComponent(monthKey(calendarMonth))}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const json = await response.json().catch(() => ({}));
        workedDays = json.days || {};
        if (workedTotal) workedTotal.textContent = json.total_label || '0H:0M';
        const daysWorked = Object.values(workedDays).filter((entry) => Number(entry?.seconds || 0) > 0).length;
        const avg = daysWorked > 0 ? Math.floor(Number(json.total_seconds || 0) / daysWorked) : 0;
        if (workedAverage) workedAverage.textContent = formatHours(avg);
      } catch (e) {
        workedDays = {};
        if (workedTotal) workedTotal.textContent = '0H:0M';
        if (workedAverage) workedAverage.textContent = '0H:0M';
      }
      renderWorkedCalendar();
    }

    function formatHours(seconds) {
      const safe = Math.max(0, Number(seconds || 0));
      const h = Math.floor(safe / 3600);
      const m = Math.floor((safe % 3600) / 60);
      return `${h}H:${m}M`;
    }

    function renderWorkedCalendar() {
      if (!workedGrid || !workedLabel) return;

      const year = calendarMonth.getFullYear();
      const month = calendarMonth.getMonth();
      workedLabel.textContent = `${monthNames[month]} ${year}`;
      workedGrid.innerHTML = '';

      const first = new Date(year, month, 1);
      const firstWeekday = (first.getDay() + 6) % 7;
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      for (let i = 0; i < firstWeekday; i++) {
        workedGrid.insertAdjacentHTML('beforeend', '<div class="min-h-14 sm:min-h-16 rounded-xl border border-transparent"></div>');
      }

      for (let day = 1; day <= daysInMonth; day++) {
        const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const info = workedDays[dateKey] || null;
        const hasHours = info && Number(info.seconds || 0) > 0;
        const label = hasHours ? info.label : '0H:0M';

        workedGrid.insertAdjacentHTML('beforeend', `
          <div class="min-h-14 sm:min-h-16 rounded-xl border ${hasHours ? 'border-emerald-300' : 'border-slate-200'} p-2" style="background:${hasHours ? '#f3fea4' : '#f8fafc'};">
            <div class="text-[11px] font-bold ${hasHours ? 'text-emerald-700' : 'text-slate-500'}">${day}</div>
            <div class="mt-1 text-[11px] font-semibold ${hasHours ? 'text-emerald-600' : 'text-slate-400'}">${label}</div>
          </div>
        `);
      }
    }

    function openWorkedModal() {
      workedModal?.classList.remove('hidden');
      loadWorkedHours();
    }
    function closeWorkedModal() {
      workedModal?.classList.add('hidden');
    }

    workedBtn?.addEventListener('click', openWorkedModal);
    workedClose?.addEventListener('click', closeWorkedModal);
    workedBackdrop?.addEventListener('click', closeWorkedModal);
    workedPrev?.addEventListener('click', () => {
      calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1, 1);
      loadWorkedHours();
    });
    workedNext?.addEventListener('click', () => {
      calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 1);
      loadWorkedHours();
    });

    @if($errors->any())
      open();
    @endif
  })();
</script>
@endsection
