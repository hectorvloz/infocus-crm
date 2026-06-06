@extends('layouts.app')
@section('title','Editar lead')
@section('content')
  <div class="max-w-2xl bg-white rounded-2xl shadow border p-6">
    <div class="text-xl font-bold mb-4">Editar lead</div>
    <form method="POST" action="{{ route('leads.update',$lead['id']) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @csrf
      <div>
        <label class="text-sm font-medium">Nombre</label>
        <input name="nombre" value="{{ $lead['nombre'] }}" class="w-full rounded-lg border p-2.5" required>
      </div>
      <div>
        <label class="text-sm font-medium">Email</label>
        <input name="email" type="email" value="{{ $lead['email'] ?? '' }}" class="w-full rounded-lg border p-2.5">
      </div>
      <div>
        <label class="text-sm font-medium">Teléfono</label>
        <input name="telefono" value="{{ $lead['telefono'] ?? '' }}" class="w-full rounded-lg border p-2.5">
      </div>
      <div>
        <label class="text-sm font-medium">Etapa</label>
        <select name="etapa" class="w-full rounded-lg border p-2.5" required>
          @foreach(['Posible cliente','Contactado','Volver a llamar','Cliente'] as $opt)
            <option @selected(($lead['etapa'] ?? '')===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Presupuesto estimado</label>
        <input name="presupuesto_estimado" type="number" step="0.01" value="{{ $lead['presupuesto_estimado'] ?? ($lead['valor'] ?? '') }}" class="w-full rounded-lg border p-2.5" placeholder="Ej. 2500000">
      </div>
      <div>
        <label class="text-sm font-medium">Origen</label>
        <input name="origen" value="{{ $lead['origen'] ?? '' }}" class="w-full rounded-lg border p-2.5">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Encargados</label>
        @php $assigned = collect($lead['encargados'] ?? [])->map(fn($v) => (string) $v)->all(); @endphp
        @php $initialAssigned = collect(old('encargados', $assigned))->map(fn($v) => (string) $v)->filter()->values()->all(); @endphp
        <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50/70 p-3">
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="encargadosToggle" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Seleccionar encargados
            </button>
            <div id="encargadosSelected" class="flex flex-wrap items-center gap-2"></div>
          </div>
          <div id="encargadosPanel" class="mt-3 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
            <input id="encargadosSearch" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Buscar por nombre o correo...">
            <div id="encargadosOptions" class="mt-2 max-h-44 overflow-auto space-y-1 pr-1"></div>
          </div>
          <div id="encargadosHidden"></div>
        </div>
        <p class="text-xs text-slate-500 mt-1">Puedes seleccionar uno o varios (Cmd/Ctrl + click).</p>
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Notas</label>
        <textarea name="notas" class="w-full rounded-lg border p-2.5" rows="3">{{ $lead['notas'] ?? '' }}</textarea>
      </div>
      <div>
        <label class="text-sm font-medium">Recordatorio</label>
        <div class="relative">
          <input name="recordatorio" id="recordatorio" type="text" value="{{ $lead['recordatorio'] ?? '' }}" class="w-full rounded-lg border p-2.5 bg-white cursor-pointer" placeholder="Seleccionar fecha y hora">
        </div>
      </div>
      <div class="md:col-span-2 flex justify-end gap-3">
        <a href="{{ route('leads.index') }}" class="px-4 py-2 rounded-full border">Cancelar</a>
        <button class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Guardar cambios</button>
      </div>
    </form>
  </div>

<script>
  function initAssigneePicker(users, initialSelected) {
    const toggle = document.getElementById('encargadosToggle');
    const panel = document.getElementById('encargadosPanel');
    const search = document.getElementById('encargadosSearch');
    const options = document.getElementById('encargadosOptions');
    const selectedWrap = document.getElementById('encargadosSelected');
    const hiddenWrap = document.getElementById('encargadosHidden');
    if (!toggle || !panel || !search || !options || !selectedWrap || !hiddenWrap) return;

    const selected = new Set((initialSelected || []).map((v) => String(v).trim()).filter(Boolean));

    const initials = (name) => {
      const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
      if (!parts.length) return 'NA';
      return parts.slice(0, 2).map((p) => p.charAt(0).toUpperCase()).join('');
    };

    const syncHidden = () => {
      hiddenWrap.innerHTML = '';
      selected.forEach((name) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'encargados[]';
        input.value = name;
        hiddenWrap.appendChild(input);
      });
    };

    const renderSelected = () => {
      const values = Array.from(selected);
      if (!values.length) {
        selectedWrap.innerHTML = '<span class="text-xs text-slate-400">Sin encargados seleccionados</span>';
        return;
      }
      selectedWrap.innerHTML = values.map((name) => {
        return `<button type="button" data-remove="${name.replace(/"/g, '&quot;')}" class="group inline-flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-slate-900 text-[11px] font-bold text-white shadow-sm" title="${name.replace(/"/g, '&quot;')}">${initials(name)}<span class="ml-1 hidden text-[10px] font-semibold text-rose-200 group-hover:inline">x</span></button>`;
      }).join('');

      selectedWrap.querySelectorAll('[data-remove]').forEach((btn) => {
        btn.addEventListener('click', () => {
          selected.delete(btn.getAttribute('data-remove') || '');
          renderOptions();
          renderSelected();
          syncHidden();
        });
      });
    };

    const renderOptions = () => {
      const term = search.value.trim().toLowerCase();
      const filtered = (users || []).filter((u) => {
        const name = String(u.name || '').toLowerCase();
        const email = String(u.email || '').toLowerCase();
        return term === '' || name.includes(term) || email.includes(term);
      });

      if (!filtered.length) {
        options.innerHTML = '<div class="px-2 py-2 text-xs text-slate-400">No se encontraron usuarios.</div>';
        return;
      }

      options.innerHTML = filtered.map((u) => {
        const name = String(u.name || '').trim();
        const email = String(u.email || '').trim();
        const checked = selected.has(name) ? 'checked' : '';
        return `
          <label class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 cursor-pointer">
          <input type="checkbox" value="${name.replace(/"/g, '&quot;')}" ${checked} class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-700">${initials(name)}</span>
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-slate-800">${name}</span>
            <span class="block truncate text-xs text-slate-500">${email || 'Sin correo'}</span>
          </span>
          </label>
        `;
      }).join('');

      options.querySelectorAll('input[type="checkbox"]').forEach((check) => {
        check.addEventListener('change', () => {
          const value = String(check.value || '').trim();
          if (!value) return;
          if (check.checked) selected.add(value);
          else selected.delete(value);
          renderSelected();
          syncHidden();
        });
      });
    };

    toggle.addEventListener('click', () => {
      panel.classList.toggle('hidden');
      if (!panel.classList.contains('hidden')) search.focus();
    });

    search.addEventListener('input', renderOptions);

    document.addEventListener('click', (event) => {
      if (panel.classList.contains('hidden')) return;
      if (panel.contains(event.target) || toggle.contains(event.target)) return;
      panel.classList.add('hidden');
    });

    renderOptions();
    renderSelected();
    syncHidden();
  }

    // Initialize Flatpickr
    document.addEventListener("DOMContentLoaded", function() {
    initAssigneePicker(@json($assignableUsers ?? []), @json($initialAssigned));

        flatpickr("#recordatorio", {
            enableTime: true,
            altInput: true,
            altFormat: "d M, Y h:i K", 
            dateFormat: "Y-m-d H:i",
            defaultDate: "{{ $lead['recordatorio'] ?? '' }}",
            minDate: "today",
            locale: "es", // Spanish
            onOpen: function(selectedDates, dateStr, instance) {
                instance.calendarContainer.classList.add("airline-calendar");
            }
        });
    });
</script>
@endsection
