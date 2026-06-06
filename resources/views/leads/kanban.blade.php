@extends('layouts.app')
@section('title','Leads')
@section('content')
<div class="mb-4 flex items-center justify-between flex-wrap gap-3">
  <div>
    <div class="text-2xl font-extrabold">Leads</div>
    <div class="text-sm text-slate-500 mt-1">Kanban comercial para gestionar oportunidades, llamadas y reuniones.</div>
  </div>
  <div class="flex items-center gap-2">
    <a href="{{ route('leads.import.form') }}" class="px-3 py-1.5 rounded-full border text-sm font-semibold text-slate-700 hover:bg-slate-50">Importar CSV</a>
    <a href="{{ route('leads.export') }}" class="px-3 py-1.5 rounded-full border text-sm font-semibold text-slate-700 hover:bg-slate-50">Exportar CSV</a>
    <a href="{{ route('leads.create') }}" class="primary-add-btn">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
      Nuevo lead
    </a>
  </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <div class="flex items-center justify-between mb-3">
    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tablero comercial</div>
    <div class="text-xs text-slate-400">Arrastra tarjetas entre columnas</div>
  </div>

  <div id="leadsKanban" class="overflow-x-auto pb-2 custom-scroll">
    <div id="leadsBoard" class="flex gap-4 min-w-max"></div>
  </div>
</div>

<div id="leadDetailDrawer" class="fixed inset-0 z-[1600] hidden pointer-events-none" aria-modal="true" role="dialog">
  <div id="leadDetailBackdrop" class="absolute inset-0 z-0 bg-slate-900/45 pointer-events-auto"></div>
  <aside id="leadDrawerAside" role="document" class="absolute right-0 top-0 z-10 h-full w-full max-w-2xl bg-white shadow-2xl border-l border-slate-200 flex flex-col pointer-events-auto">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-3 min-w-0">
        <button id="closeLeadDrawerBtn" type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" aria-label="Cerrar detalle">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="min-w-0">
          <div id="drawerLeadName" class="text-2xl font-extrabold text-slate-900 truncate">Lead</div>
          <div id="drawerLeadMeta" class="text-xs text-slate-500 truncate">Detalle comercial</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a id="drawerEditLeadLink" href="{{ route('leads.edit', ['id' => '__LEAD__']) }}" onclick="event.preventDefault();event.stopPropagation();window.location.href=this.getAttribute('href');" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700">Editar</a>
        <button id="drawerHeaderTimerBtn" type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-lime-200 bg-lime-50 text-slate-800 text-sm font-extrabold hover:bg-lime-100">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
          <span id="drawerHeaderTimerLabel">Iniciar timer</span>
        </button>
        <form id="drawerDeleteLeadForm" action="{{ route('leads.destroy', ['id' => '__LEAD__']) }}" method="POST" onsubmit="event.stopPropagation(); return confirm('¿Eliminar este lead?');" class="inline">
          @csrf @method('DELETE')
          <button id="drawerDeleteLeadBtn" type="submit" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Eliminar lead" aria-label="Eliminar lead">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </form>
      </div>
    </div>

    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2 flex-wrap">
      <a id="drawerCallLink" href="#" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3a2 2 0 012 2v2a2 2 0 01-1 1.732l-1.5.866a11 11 0 005.634 5.634l.866-1.5A2 2 0 0115 13h2a2 2 0 012 2v3a2 2 0 01-2 2h-1C8.163 20 4 15.837 4 10V5z"/></svg>
        Llamar
      </a>
      <button id="drawerMailLink" type="button" class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
        Email
      </button>
      <div id="drawerStageBadge" class="ml-auto text-xs font-bold uppercase tracking-wide px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">Etapa</div>
    </div>

    <div class="flex-1 overflow-y-auto custom-scroll px-5 py-4 space-y-6">
      <section class="rounded-2xl border border-slate-200 p-4 bg-slate-50/60">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
          <div>
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Telefono</div>
            <div id="drawerPhone" class="font-semibold text-slate-800">-</div>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Origen</div>
            <div id="drawerOrigin" class="font-semibold text-slate-800">-</div>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Presupuesto</div>
            <div id="drawerValue" class="font-semibold text-slate-800">-</div>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Recordatorio</div>
            <div id="drawerReminder" class="font-semibold text-slate-800">-</div>
          </div>
        </div>
        <div class="mt-3 border-t border-slate-200 pt-3">
          <div class="text-[11px] uppercase tracking-wide text-slate-500 mb-2">Encargados</div>
          <div id="drawerAssignees" class="flex flex-wrap gap-2"></div>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div>
            <h3 class="text-base font-extrabold text-slate-900">Próxima reunión</h3>
            <p class="text-xs font-semibold text-slate-400">Actividades programadas en orden</p>
          </div>
          <span id="nextMeetingCount" class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">0</span>
        </div>
        <div id="nextMeetingList" class="space-y-2"></div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Programar actividad</h3>
          <div class="text-xs text-slate-400">Llamada o reunion por Meet</div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Tipo</label>
            <select id="agendaType" class="w-full rounded-xl border-slate-200 text-sm form-select">
              <option value="llamada">Llamada</option>
              <option value="reunion_meet">Reunion por Google Meet</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Titulo</label>
            <input id="agendaTitle" type="text" class="w-full rounded-xl border-slate-200 text-sm form-input" placeholder="Ej. Seguimiento propuesta">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Fecha y hora</label>
            <input id="agendaDateTime" type="text" class="w-full rounded-xl border-slate-200 text-sm form-input" placeholder="Seleccionar fecha/hora" readonly>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Duracion (min)</label>
            <input id="agendaDuration" type="number" min="5" max="240" value="30" required class="w-full rounded-xl border-slate-200 text-sm form-input">
          </div>
          <div class="md:col-span-2">
            <div id="agendaMeetModeWrap" class="hidden mb-3">
              <label class="block text-xs font-semibold text-slate-500 mb-1">Enlace de reunión</label>
              <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                <button id="agendaMeetModeAutoBtn" type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#f3fea4] text-slate-900">Crear Meet automático</button>
                <button id="agendaMeetModeManualBtn" type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600">Pegar URL manual</button>
              </div>
              <input id="agendaMeetMode" type="hidden" value="auto">
            </div>
            <div id="agendaMeetUrlWrap" class="hidden mb-3">
              <label class="block text-xs font-semibold text-slate-500 mb-1">URL de Google Meet</label>
              <input id="agendaMeetUrl" type="url" class="w-full rounded-xl border-slate-200 text-sm form-input" placeholder="https://meet.google.com/xxx-xxxx-xxx">
            </div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Descripcion</label>
            <textarea id="agendaDescription" rows="2" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Objetivo de la llamada o reunion"></textarea>
          </div>
        </div>
        <div class="mt-3 flex justify-end">
          <button id="agendaSaveBtn" type="button" class="px-4 py-2 rounded-full text-white text-sm font-bold" style="background:#101729;">Programar</button>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Timeline de seguimiento</h3>
          <span id="timelineCount" class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">0</span>
        </div>
        <div id="leadTimeline" class="space-y-2"></div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Enviar mensaje WhatsApp</h3>
          <span class="text-xs text-slate-400">Se abre WhatsApp Web/App</span>
        </div>
        <div class="space-y-3">
          <textarea id="whatsappMessage" rows="3" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Escribe el mensaje para este lead"></textarea>
          <div class="flex justify-end">
            <button id="sendWhatsappBtn" type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.48A11.91 11.91 0 0 0 12.03 0C5.4 0 .03 5.37.03 12c0 2.12.55 4.2 1.6 6.03L0 24l6.13-1.6A11.95 11.95 0 0 0 12.03 24C18.66 24 24 18.63 24 12a11.9 11.9 0 0 0-3.48-8.52zM12.03 21.8a9.8 9.8 0 0 1-4.99-1.37l-.36-.21-3.64.95.97-3.55-.24-.37a9.8 9.8 0 1 1 8.26 4.55zm5.45-7.35c-.3-.15-1.77-.87-2.05-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.24-.46-2.36-1.46-.87-.78-1.46-1.74-1.63-2.03-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.57-.48-.5-.67-.51h-.57c-.2 0-.52.08-.8.37-.27.3-1.05 1.02-1.05 2.5 0 1.47 1.07 2.9 1.22 3.1.15.2 2.1 3.2 5.1 4.48.71.31 1.27.5 1.71.64.72.23 1.38.2 1.9.12.58-.09 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35z"/></svg>
              Enviar por WhatsApp
            </button>
          </div>
        </div>
      </section>

      <section class="hidden rounded-2xl border border-slate-200 p-4" aria-hidden="true">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Agenda</h3>
          <span id="agendaCount" class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">0</span>
        </div>
        <div id="agendaList" class="space-y-2"></div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Notas</h3>
        </div>
        <div id="leadNotes" class="space-y-2 mb-3"></div>
        <div class="flex gap-2">
          <input id="leadNoteInput" class="flex-1 rounded-full border border-slate-200 px-3 py-2 text-sm" placeholder="Agregar nota...">
          <button id="leadNoteAddBtn" class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-semibold">Guardar</button>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 p-4" id="drawerTiempoSection">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-extrabold text-slate-900">Tiempo trabajado</h3>
          <span id="drawerTiempoTotal" class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">0 min</span>
        </div>
        <div id="drawerTiempoActive" class="hidden mb-3 rounded-xl border border-lime-300 bg-lime-50 px-3 py-2">
          <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <span class="inline-flex h-2 w-2 rounded-full bg-lime-500 animate-pulse"></span>
            Timer activo:&nbsp;<span id="drawerTiempoActiveDisplay" class="font-mono font-extrabold text-lime-700">00:00</span>
            <button type="button" id="drawerTiempoStopBtn" class="ml-auto px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs font-bold hover:bg-rose-200">Detener</button>
          </div>
        </div>
        <div id="drawerTiempoList" class="space-y-1.5 max-h-40 overflow-y-auto custom-scroll text-xs text-slate-500"></div>
        <button type="button" id="drawerTiempoStartBtn" class="mt-3 w-full rounded-xl border border-slate-200 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
          Iniciar timer en este lead
        </button>
      </section>
    </div>
  </aside>
</div>

<div id="leadEmailModal" class="fixed inset-0 z-[1700] hidden items-center justify-center bg-slate-950/55 px-4 py-8" style="z-index: 1800;" aria-modal="true" role="dialog">
  <div data-lead-email-dialog role="document" class="w-full max-w-xl rounded-[24px] bg-white shadow-2xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4">
      <div class="min-w-0">
        <h2 class="text-2xl font-extrabold text-slate-900">Enviar email</h2>
        <p id="leadEmailToLabel" class="text-sm font-semibold text-slate-500 truncate">Lead</p>
      </div>
      <button type="button" data-close-lead-email data-modal-close class="h-10 w-10 rounded-full hover:bg-slate-100 grid place-content-center text-slate-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <div>
        <label class="block text-sm font-bold text-slate-700 mb-1">Asunto</label>
        <input id="leadEmailSubject" type="text" class="w-full rounded-xl border-slate-200 text-sm form-input" placeholder="Asunto del correo">
      </div>
      <div>
        <label class="block text-sm font-bold text-slate-700 mb-1">Mensaje</label>
        <textarea id="leadEmailBody" rows="8" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Escribe el mensaje para este lead"></textarea>
      </div>
      <div id="leadEmailStatus" class="hidden rounded-xl border px-3 py-2 text-sm font-semibold"></div>
      <div class="flex justify-end gap-3">
        <button type="button" data-close-lead-email class="px-5 py-3 rounded-full border border-slate-200 font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
        <button id="leadEmailSendBtn" type="button" class="px-6 py-3 rounded-full bg-[#ecfe88] text-slate-950 font-extrabold hover:bg-lime-300">Enviar email</button>
      </div>
    </div>
  </div>
</div>

{{-- Lead Timer Modal --}}
<div id="leadTimerModal" class="fixed inset-0 z-[1400] hidden">
  <div class="absolute inset-0 bg-slate-950/55" id="leadTimerBackdrop"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
      <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
          <h3 class="text-lg font-extrabold text-slate-900">¿En qué trabajas hoy?</h3>
          <p class="text-xs text-slate-500 mt-1">Selecciona un lead para iniciar el temporizador y vincular el tiempo.</p>
        </div>
        <button type="button" id="leadTimerModalClose" class="h-8 w-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100">×</button>
      </div>
      <div class="px-5 py-4 space-y-3">
        <div class="relative">
          <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-4.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input id="leadTimerSearch" type="text" placeholder="Buscar lead..." class="w-full h-11 rounded-xl border-slate-200 bg-slate-50 pl-10 text-sm font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500">
        </div>
        <div id="leadTimerList" class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
      </div>
      <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-5 py-4">
        <button type="button" id="leadTimerCancelBtn" class="h-10 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancelar</button>
        <button type="button" id="leadTimerStartBtn" disabled class="h-10 rounded-xl border border-lime-200 bg-lime-100 px-4 text-sm font-extrabold text-slate-900 hover:bg-lime-200 disabled:opacity-50 disabled:cursor-not-allowed">Iniciar temporizador</button>
      </div>
    </div>
  </div>
</div>

<script id="leadsData" type="application/json">@json($leads)</script>
<script>
(() => {
  const columns = ['Posible cliente','Contactado','Volver a llamar','Cliente'];
  const stageStyles = {
    'Posible cliente': {dot:'bg-blue-500', bg:'from-blue-50 to-white'},
    'Contactado': {dot:'bg-amber-500', bg:'from-amber-50 to-white'},
    'Volver a llamar': {dot:'bg-fuchsia-500', bg:'from-fuchsia-50 to-white'},
    'Cliente': {dot:'bg-emerald-500', bg:'from-emerald-50 to-white'}
  };

  const leads = JSON.parse(document.getElementById('leadsData').textContent || '[]');
  const board = document.getElementById('leadsBoard');
  const scroller = document.getElementById('leadsKanban');
  const drawer = document.getElementById('leadDetailDrawer');
  const drawerBackdrop = document.getElementById('leadDetailBackdrop');
  const closeDrawerBtn = document.getElementById('closeLeadDrawerBtn');
  const editLeadLink = document.getElementById('drawerEditLeadLink');
  const deleteLeadForm = document.getElementById('drawerDeleteLeadForm');
  let activeLead = null;

  const fmtMoney = (n) => {
    const value = Number(n || 0);
    if (!value) return '-';
    return new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', maximumFractionDigits:0 }).format(value);
  };

  const normalizeStage = (stage) => {
    const map = {
      'Nuevo':'Posible cliente','Calificado':'Posible cliente','Propuesta':'Contactado',
      'Llamar':'Volver a llamar','Seguimiento':'Volver a llamar','Ganado':'Cliente','Perdido':'Posible cliente'
    };
    if (columns.includes(stage)) return stage;
    return map[stage] || 'Posible cliente';
  };

  function renderBoard() {
    board.innerHTML = columns.map((stage) => {
      const s = stageStyles[stage] || stageStyles['Posible cliente'];
      return `
        <section class="w-[310px] rounded-2xl border border-slate-200 bg-gradient-to-b ${s.bg} p-3">
          <header class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full ${s.dot}"></span>
              <h3 class="text-sm font-extrabold text-slate-800">${stage}</h3>
            </div>
            <span data-count="${stage}" class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">0</span>
          </header>
          <div class="space-y-2 min-h-[280px]" data-stage="${stage}"></div>
        </section>
      `;
    }).join('');

    const counts = {};
    leads.forEach((lead) => {
      const stage = normalizeStage(lead.etapa || 'Posible cliente');
      lead.etapa = stage;
      const zone = board.querySelector(`[data-stage="${stage}"]`);
      if (!zone) return;
      zone.insertAdjacentHTML('beforeend', leadCard(lead));
      counts[stage] = (counts[stage] || 0) + 1;
    });

    columns.forEach((stage) => {
      const el = board.querySelector(`[data-count="${stage}"]`);
      if (el) el.textContent = counts[stage] || 0;
    });

    bindDnD();
    bindCardOpen();
  }

  function leadCard(lead) {
    const reminder = lead.recordatorio ? new Date(lead.recordatorio).toLocaleString('es-CO', { dateStyle:'short', timeStyle:'short' }) : '';
    const assignees = (Array.isArray(lead.encargados) ? lead.encargados : []).slice(0, 3);
    const assigneesHtml = assignees.length
      ? assignees.map((name) => `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-900 text-white text-[10px] font-bold" title="${escapeHtml(name)}">${escapeHtml(getInitials(name))}</span>`).join('')
      : '<span class="text-[10px] text-slate-400">Sin encargado</span>';
    return `
      <article class="lead-card rounded-2xl border border-slate-200 bg-white p-3 shadow-sm cursor-move" draggable="true" data-id="${lead.id}">
        <div class="flex items-start justify-between gap-2">
          <button type="button" class="lead-open text-left text-base font-extrabold text-slate-900 hover:text-slate-700">${escapeHtml(lead.nombre || 'Lead')}</button>
          <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">${escapeHtml(lead.origen || 'Sin origen')}</span>
        </div>
        <div class="text-sm text-slate-500 mt-0.5">${escapeHtml(lead.telefono || 'Sin telefono')}</div>
        <div class="text-xs text-slate-500 mt-2 line-clamp-2">${escapeHtml(previewNote(lead))}</div>
        <div class="mt-3 flex items-center justify-between">
          <span class="text-xs font-bold text-slate-700">${fmtMoney(lead.presupuesto_estimado ?? lead.valor)}</span>
          ${reminder ? `<span class="text-[10px] px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-semibold">${escapeHtml(reminder)}</span>` : ''}
        </div>
        <div class="mt-2 flex items-center justify-between gap-1.5">
          <div class="flex items-center gap-1.5">${assigneesHtml}</div>
          ${(lead.tiempo_total_min || 0) > 0 ? `<span class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-500 rounded-full border border-slate-200 px-1.5 py-0.5" title="Tiempo trabajado"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>${fmtMinutes(lead.tiempo_total_min)}</span>` : ''}
        </div>
      </article>
    `;
  }

  function getInitials(text) {
    const parts = String(text || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'NA';
    return parts.slice(0, 2).map((p) => p.charAt(0).toUpperCase()).join('');
  }

  function previewNote(lead) {
    if (Array.isArray(lead.notas_lista) && lead.notas_lista.length) {
      return lead.notas_lista[lead.notas_lista.length - 1].texto || '';
    }
    return lead.notas || 'Sin notas';
  }

  function escapeHtml(text) {
    return String(text || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function bindDnD() {
    board.querySelectorAll('.lead-card[draggable="true"]').forEach((card) => {
      card.addEventListener('dragstart', (event) => {
        event.dataTransfer.setData('text/plain', card.dataset.id);
      });
    });

    board.querySelectorAll('[data-stage]').forEach((zone) => {
      zone.addEventListener('dragover', (event) => event.preventDefault());
      zone.addEventListener('drop', async (event) => {
        event.preventDefault();
        const id = event.dataTransfer.getData('text/plain');
        const etapa = zone.dataset.stage;
        const res = await fetch('{{ route('api.leads.mover') }}', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':window.csrfToken},
          body: JSON.stringify({id, etapa}),
        });
        if (!res.ok) return;
        const idx = leads.findIndex((item) => item.id === id);
        if (idx >= 0) leads[idx].etapa = etapa;
        renderBoard();
      });
    });
  }

  function bindCardOpen() {
    board.querySelectorAll('.lead-open').forEach((btn) => {
      btn.addEventListener('click', async (event) => {
        const card = event.target.closest('.lead-card');
        if (!card) return;
        const id = card.dataset.id;
        await openDrawer(id);
      });
    });
  }

  async function openDrawer(id) {
    const local = leads.find((item) => item.id === id);
    if (!local) return;

    try {
      const res = await fetch(`/api/leads/${id}`);
      if (res.ok) {
        const payload = await res.json();
        if (payload && payload.item) {
          Object.assign(local, payload.item);
        }
      }
    } catch (error) {
      console.error(error);
    }

    activeLead = local;
    hydrateDrawer(local);
    drawer.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeDrawer() {
    drawer.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    activeLead = null;
  }

  function renderAssignees(lead) {
    const host = document.getElementById('drawerAssignees');
    const assignees = Array.isArray(lead.encargados) ? lead.encargados.filter(Boolean) : [];
    if (!assignees.length) {
      host.innerHTML = '<span class="text-xs text-slate-400">Sin encargado asignado</span>';
      return;
    }
    host.innerHTML = assignees.map((name) => `
      <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-700">
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-900 text-white text-[10px] font-bold">${escapeHtml(getInitials(name))}</span>
        ${escapeHtml(name)}
      </span>
    `).join('');
  }

  function hydrateDrawer(lead) {
    document.getElementById('drawerLeadName').textContent = lead.nombre || 'Lead';
    document.getElementById('drawerLeadMeta').textContent = `${lead.email || 'Sin email'} • ${lead.etapa || ''}`;
    editLeadLink.href = `{{ route('leads.edit', ['id' => '__LEAD__']) }}`.replace('__LEAD__', encodeURIComponent(lead.id || ''));
    deleteLeadForm.action = `{{ route('leads.destroy', ['id' => '__LEAD__']) }}`.replace('__LEAD__', encodeURIComponent(lead.id || ''));
    document.getElementById('drawerCallLink').href = lead.telefono ? `tel:${lead.telefono}` : '#';
    document.getElementById('drawerStageBadge').textContent = lead.etapa || 'Etapa';
    document.getElementById('drawerPhone').textContent = lead.telefono || '-';
    document.getElementById('drawerOrigin').textContent = lead.origen || '-';
    document.getElementById('drawerValue').textContent = fmtMoney(lead.presupuesto_estimado ?? lead.valor);
    document.getElementById('drawerReminder').textContent = lead.recordatorio ? new Date(lead.recordatorio).toLocaleString('es-CO') : '-';
    renderAssignees(lead);

    document.getElementById('agendaTitle').value = `Seguimiento ${lead.nombre || ''}`.trim();
    document.getElementById('agendaDescription').value = '';
    document.getElementById('agendaDuration').value = 30;
    document.getElementById('agendaMeetMode').value = 'auto';
    document.getElementById('agendaMeetUrl').value = '';
    document.getElementById('whatsappMessage').value = `Hola ${lead.nombre || ''}, te escribo para hacer seguimiento de nuestra conversación.`.trim();
    toggleMeetUrlField();

    renderTimeline(lead);
    renderNextMeetings(lead.agenda || []);
    renderAgenda(lead.agenda || []);
    renderNotes(lead);
    renderTiempoTrabajado(lead);
    updateLeadTimerDisplays();
  }

  function renderTimeline(lead) {
    const list = document.getElementById('leadTimeline');
    const count = document.getElementById('timelineCount');
    const agenda = Array.isArray(lead.agenda) ? lead.agenda : [];
    const agendaItems = agenda
      .map((item) => ({
        ...item,
        kind: 'agenda',
        scheduledAt: item.fecha_hora ? new Date(item.fecha_hora) : null,
        createdAt: item.creado_en ? new Date(item.creado_en) : null,
      }))
      .filter((item) => item.scheduledAt && !Number.isNaN(item.scheduledAt.getTime()));

    const emailItems = (Array.isArray(lead.emails_enviados) ? lead.emails_enviados : [])
      .map((item) => ({
        ...item,
        kind: 'email',
        scheduledAt: item.fecha ? new Date(item.fecha) : null,
        createdAt: item.fecha ? new Date(item.fecha) : null,
      }))
      .filter((item) => item.scheduledAt && !Number.isNaN(item.scheduledAt.getTime()));

    const items = [...agendaItems, ...emailItems].sort((a, b) => b.scheduledAt - a.scheduledAt);

    count.textContent = String(items.length);
    if (!items.length) {
      list.innerHTML = '<div class="text-xs text-slate-400">Aun no hay actividades registradas para este lead.</div>';
      return;
    }

    const now = new Date();
    list.innerHTML = items.map((item) => {
      if (item.kind === 'email') {
        const sentAt = item.scheduledAt.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
        return `
          <article class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
            <div class="flex items-start gap-3">
              <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-slate-900"></span>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-slate-800 truncate">Email: ${escapeHtml(item.subject || 'Sin asunto')}</div>
                <div class="text-xs text-slate-500 mt-0.5">Enviado: ${sentAt} · Desde ${escapeHtml(item.from || 'SMTP')}</div>
                <div class="mt-1 text-xs text-slate-600 line-clamp-2">${escapeHtml(item.body || '')}</div>
              </div>
            </div>
          </article>
        `;
      }
      const isMeet = item.tipo === 'reunion_meet';
      const dot = isMeet ? 'bg-emerald-500' : 'bg-blue-500';
      const label = isMeet ? 'Reunion Meet' : 'Llamada';
      const scheduled = item.scheduledAt.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
      const created = item.createdAt && !Number.isNaN(item.createdAt.getTime())
        ? item.createdAt.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' })
        : null;
      const hasMeetLink = isMeet && !!item.meet_link;
      const joinDeadline = new Date(item.scheduledAt.getTime() + (3 * 60 * 60 * 1000));
      const canJoin = hasMeetLink && now <= joinDeadline;
      const meetError = isMeet ? (item.meet_error || '') : '';

      return `
        <article class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
          <div class="flex items-start gap-3">
            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full ${dot}"></span>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-bold text-slate-800 truncate">${escapeHtml(item.titulo || label)}</div>
              <div class="text-xs text-slate-500 mt-0.5">${label} · Programada: ${scheduled}</div>
              ${created ? `<div class="text-[11px] text-slate-400 mt-0.5">Creada: ${created}</div>` : ''}
              ${canJoin ? `
                <div class="mt-2">
                  <a href="${escapeHtml(item.meet_link)}" target="_blank" rel="noopener" class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600">
                    Unirme a reunión
                  </a>
                </div>
              ` : ''}
              ${(!hasMeetLink && meetError) ? `
                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs text-amber-800">
                  <strong>Meet no enlazado:</strong> ${escapeHtml(meetError)}
                </div>
              ` : ''}
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  function getMeetingSchedule(item) {
    const start = item?.fecha_hora ? new Date(item.fecha_hora) : null;
    if (!start || Number.isNaN(start.getTime())) return null;
    const duration = Math.max(5, Number(item.duracion_min || 30));
    const end = new Date(start.getTime() + duration * 60 * 1000);
    return { start, end, duration };
  }

  function renderNextMeetings(items) {
    const list = document.getElementById('nextMeetingList');
    const count = document.getElementById('nextMeetingCount');
    if (!list || !count) return;

    const now = new Date();
    const meetings = (Array.isArray(items) ? items : [])
      .filter((item) => item.tipo === 'reunion_meet')
      .map((item) => ({ ...item, schedule: getMeetingSchedule(item) }))
      .filter((item) => item.schedule && item.schedule.end >= now)
      .sort((a, b) => a.schedule.start - b.schedule.start);

    count.textContent = String(meetings.length);
    if (!meetings.length) {
      list.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs font-semibold text-slate-400">No hay reuniones próximas.</div>';
      return;
    }

    list.innerHTML = meetings.map((item, index) => {
      const { start, end, duration } = item.schedule;
      const isActive = start <= now && end >= now;
      const label = isActive ? 'En curso' : (index === 0 ? 'Próximamente' : 'Programada');
      const labelClass = isActive ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-lime-100 text-slate-800 border-lime-200';
      const scheduled = start.toLocaleString('es-CO', { dateStyle: 'medium', timeStyle: 'short' });
      const timeRange = `${start.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })} - ${end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })}`;
      const meetLink = item.meet_link || '';

      return `
        <article class="rounded-xl border border-slate-200 bg-white px-3 py-3">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-extrabold ${labelClass}">${label}</span>
                <span class="text-[11px] font-bold text-slate-400">${duration} min</span>
              </div>
              <div class="mt-1 text-sm font-extrabold text-slate-900 truncate">${escapeHtml(item.titulo || 'Reunión por Meet')}</div>
              <div class="mt-0.5 text-xs font-semibold text-slate-500">${scheduled} · ${timeRange}</div>
              ${item.descripcion ? `<div class="mt-1 text-xs text-slate-500 line-clamp-2">${escapeHtml(item.descripcion)}</div>` : ''}
            </div>
            ${meetLink ? `
              <a href="${escapeHtml(meetLink)}" target="_blank" rel="noopener" class="shrink-0 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-extrabold text-emerald-700 hover:bg-emerald-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 19h8a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Abrir Meet
              </a>
            ` : ''}
          </div>
          ${(!meetLink && item.meet_error) ? `<div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs text-amber-800"><strong>Meet pendiente:</strong> ${escapeHtml(item.meet_error)}</div>` : ''}
        </article>
      `;
    }).join('');
  }

  function renderAgenda(items) {
    const list = document.getElementById('agendaList');
    const count = document.getElementById('agendaCount');
    if (!list || !count) return;
    count.textContent = String(items.length || 0);

    if (!items.length) {
      list.innerHTML = '<div class="text-xs text-slate-400">No hay actividades programadas.</div>';
      return;
    }

    const sorted = [...items].sort((a, b) => new Date(a.fecha_hora) - new Date(b.fecha_hora));
    list.innerHTML = sorted.map((item) => {
      const isMeet = item.tipo === 'reunion_meet';
      const typeColor = isMeet ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700';
      const initials = escapeHtml(item.creado_por_iniciales || getInitials(item.creado_por || 'SI'));
      return `
        <article class="rounded-xl border border-slate-200 p-3 bg-white">
          <div class="flex items-center justify-between gap-2 mb-1">
            <div class="flex items-center gap-2 min-w-0">
              <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-900 text-white text-[11px] font-bold" title="${escapeHtml(item.creado_por || 'Sistema')}">${initials}</span>
              <div class="font-semibold text-slate-800 truncate">${escapeHtml(item.titulo || (isMeet ? 'Reunion' : 'Llamada'))}</div>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold ${typeColor}">${isMeet ? 'Meet' : 'Llamada'}</span>
          </div>
          <div class="text-xs text-slate-500">${new Date(item.fecha_hora).toLocaleString('es-CO', { dateStyle:'medium', timeStyle:'short' })} • ${item.duracion_min || 30} min</div>
          ${item.descripcion ? `<div class="text-xs text-slate-600 mt-1">${escapeHtml(item.descripcion)}</div>` : ''}
          ${item.meet_link ? `<a href="${item.meet_link}" target="_blank" class="inline-flex mt-2 text-xs font-bold text-emerald-700 hover:text-emerald-800">Abrir enlace Meet</a>` : ''}
        </article>
      `;
    }).join('');
  }

  function getNotes(lead) {
    if (Array.isArray(lead.notas_lista)) return lead.notas_lista;
    if (lead.notas) return [{ id:'legacy', texto: lead.notas, fecha: lead.updated_at || lead.created_at || '' }];
    return [];
  }

  function renderNotes(lead) {
    const notes = getNotes(lead);
    const wrap = document.getElementById('leadNotes');
    if (!notes.length) {
      wrap.innerHTML = '<div class="text-xs text-slate-400">Aun no hay notas.</div>';
      return;
    }
    wrap.innerHTML = notes.slice().reverse().map((note) => `
      <article class="rounded-xl border border-slate-200 bg-white px-3 py-2">
        <div class="text-[11px] text-slate-400 mb-1">${note.fecha ? new Date(note.fecha).toLocaleString('es-CO') : ''}</div>
        <div class="text-sm text-slate-700">${escapeHtml(note.texto)}</div>
      </article>
    `).join('');
  }

  async function saveNote() {
    if (!activeLead) return;
    const input = document.getElementById('leadNoteInput');
    const texto = input.value.trim();
    if (!texto) return;

    const res = await fetch('{{ route('api.leads.notas.agregar') }}', {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':window.csrfToken},
      body: JSON.stringify({id: activeLead.id, texto}),
    });

    if (!res.ok) return;
    const payload = await res.json();
    if (!payload.ok || !payload.item) return;

    Object.assign(activeLead, payload.item);
    input.value = '';
    renderNotes(activeLead);
    renderBoard();
  }

  async function saveAgenda() {
    if (!activeLead) return;

    const tipo = document.getElementById('agendaType').value;
    const titulo = document.getElementById('agendaTitle').value.trim();
    const fechaHora = document.getElementById('agendaDateTime').value.trim();
    const duracion = Number(document.getElementById('agendaDuration').value || 30);
    const descripcion = document.getElementById('agendaDescription').value.trim();
    const meetMode = document.getElementById('agendaMeetMode')?.value || 'auto';
    const meetUrl = meetMode === 'manual' ? document.getElementById('agendaMeetUrl').value.trim() : '';

    if (!titulo || !fechaHora) {
      alert('Completa titulo y fecha/hora.');
      return;
    }

    const btn = document.getElementById('agendaSaveBtn');
    btn.disabled = true;
    btn.textContent = 'Programando...';

    try {
      const res = await fetch('{{ route('api.leads.agenda.programar') }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':window.csrfToken},
        body: JSON.stringify({
          id: activeLead.id,
          tipo,
          titulo,
          fecha_hora: fechaHora,
          duracion_min: duracion,
          descripcion,
          meet_url: meetUrl,
        }),
      });

      if (!res.ok) {
        let message = 'No se pudo programar la actividad.';
        try {
          const err = await res.json();
          if (err && err.message) message = err.message;
        } catch (_) {}
        alert(message);
        return;
      }

      const payload = await res.json();
      if (payload.ok && payload.item) {
        Object.assign(activeLead, payload.item);
        const idx = leads.findIndex((item) => item.id === activeLead.id);
        if (idx >= 0) leads[idx] = activeLead;
        hydrateDrawer(activeLead);
        renderBoard();
      }
    } finally {
      btn.disabled = false;
      btn.textContent = 'Programar';
    }
  }

  function sanitizePhone(phone) {
    return String(phone || '').replace(/[^\d]/g, '');
  }

  function sendWhatsapp() {
    if (!activeLead) return;
    const phone = sanitizePhone(activeLead.telefono || '');
    const message = document.getElementById('whatsappMessage').value.trim();
    if (!phone) {
      alert('Este lead no tiene telefono para WhatsApp.');
      return;
    }
    const url = `https://wa.me/${phone}${message ? `?text=${encodeURIComponent(message)}` : ''}`;
    window.open(url, '_blank');
  }

  function openLeadEmailModal() {
    if (!activeLead) return;
    if (!activeLead.email) {
      alert('Este lead no tiene email.');
      return;
    }
    const modal = document.getElementById('leadEmailModal');
    const label = document.getElementById('leadEmailToLabel');
    const subject = document.getElementById('leadEmailSubject');
    const body = document.getElementById('leadEmailBody');
    const status = document.getElementById('leadEmailStatus');
    if (label) label.textContent = `${activeLead.nombre || 'Lead'} · ${activeLead.email}`;
    if (subject) subject.value = `Seguimiento ${activeLead.nombre || ''}`.trim();
    if (body) body.value = `Hola ${activeLead.nombre || ''},\n\nTe escribo para hacer seguimiento de nuestra conversación.\n\nQuedo atento.`;
    if (status) {
      status.className = 'hidden rounded-xl border px-3 py-2 text-sm font-semibold';
      status.textContent = '';
    }
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    setTimeout(() => subject?.focus(), 30);
  }

  function closeLeadEmailModal() {
    const modal = document.getElementById('leadEmailModal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
  }

  async function sendLeadEmail() {
    if (!activeLead) return;
    const btn = document.getElementById('leadEmailSendBtn');
    const status = document.getElementById('leadEmailStatus');
    const subject = document.getElementById('leadEmailSubject')?.value.trim() || '';
    const body = document.getElementById('leadEmailBody')?.value.trim() || '';
    if (!subject || !body) {
      alert('Completa asunto y mensaje.');
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Enviando...';
    }
    if (status) {
      status.className = 'rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-600';
      status.textContent = 'Enviando correo...';
    }

    try {
      const res = await fetch('{{ route('api.leads.email.enviar') }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':window.csrfToken},
        body: JSON.stringify({ id: activeLead.id, subject, body }),
      });
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || !payload.ok) {
        throw new Error(payload.message || 'No se pudo enviar el correo.');
      }
      Object.assign(activeLead, payload.item);
      const idx = leads.findIndex((item) => item.id === activeLead.id);
      if (idx >= 0) leads[idx] = activeLead;
      renderTimeline(activeLead);
      renderBoard();
      if (window.showNotification) window.showNotification('Correo enviado al lead.', 'success');
      closeLeadEmailModal();
    } catch (error) {
      if (status) {
        status.className = 'rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700';
        status.textContent = error.message || 'No se pudo enviar el correo.';
      }
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Enviar email';
      }
    }
  }

  function toggleMeetUrlField() {
    const type = document.getElementById('agendaType')?.value;
    const modeWrap = document.getElementById('agendaMeetModeWrap');
    const wrap = document.getElementById('agendaMeetUrlWrap');
    if (!wrap || !modeWrap) return;
    if (type === 'reunion_meet') {
      modeWrap.classList.remove('hidden');
      wrap.classList.remove('hidden');
      updateMeetModeUI();
    } else {
      modeWrap.classList.add('hidden');
      wrap.classList.add('hidden');
    }
  }

  function updateMeetModeUI() {
    const mode = document.getElementById('agendaMeetMode')?.value || 'auto';
    const autoBtn = document.getElementById('agendaMeetModeAutoBtn');
    const manualBtn = document.getElementById('agendaMeetModeManualBtn');
    const urlWrap = document.getElementById('agendaMeetUrlWrap');
    if (!autoBtn || !manualBtn || !urlWrap) return;

    if (mode === 'manual') {
      autoBtn.classList.remove('bg-[#f3fea4]', 'text-slate-900');
      autoBtn.classList.add('text-slate-600');
      manualBtn.classList.add('bg-[#f3fea4]', 'text-slate-900');
      manualBtn.classList.remove('text-slate-600');
      urlWrap.classList.remove('hidden');
    } else {
      manualBtn.classList.remove('bg-[#f3fea4]', 'text-slate-900');
      manualBtn.classList.add('text-slate-600');
      autoBtn.classList.add('bg-[#f3fea4]', 'text-slate-900');
      autoBtn.classList.remove('text-slate-600');
      urlWrap.classList.add('hidden');
      const urlInput = document.getElementById('agendaMeetUrl');
      if (urlInput) urlInput.value = '';
    }
  }

  function setupDragScroll() {
    if (!scroller) return;
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    scroller.style.cursor = 'grab';
    scroller.addEventListener('mousedown', (event) => {
      if (event.target.closest('.lead-card, .lead-open, button, a, input, textarea, select')) return;
      isDown = true;
      scroller.style.cursor = 'grabbing';
      startX = event.pageX - scroller.offsetLeft;
      scrollLeft = scroller.scrollLeft;
    });

    window.addEventListener('mouseup', () => {
      isDown = false;
      scroller.style.cursor = 'grab';
    });

    scroller.addEventListener('mouseleave', () => {
      isDown = false;
      scroller.style.cursor = 'grab';
    });

    scroller.addEventListener('mousemove', (event) => {
      if (!isDown) return;
      event.preventDefault();
      const x = event.pageX - scroller.offsetLeft;
      const walk = (x - startX) * 1.25;
      scroller.scrollLeft = scrollLeft - walk;
    });
  }

  closeDrawerBtn.addEventListener('click', closeDrawer);
  // Regla estricta: solo backdrop cierra. Clicks internos se mantienen funcionales.
  drawerBackdrop.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    closeDrawer();
  });
  document.getElementById('leadNoteAddBtn').addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    saveNote();
  });
  document.getElementById('agendaSaveBtn').addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    saveAgenda();
  });
  document.getElementById('sendWhatsappBtn').addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    sendWhatsapp();
  });
  document.getElementById('drawerMailLink')?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    openLeadEmailModal();
  });
  document.getElementById('drawerCallLink')?.addEventListener('click', (event) => {
    if (!activeLead?.telefono) {
      event.preventDefault();
      alert('Este lead no tiene teléfono.');
    }
  });
  document.querySelector('[data-lead-email-dialog]')?.addEventListener('click', (event) => {
    event.stopPropagation();
  });
  document.querySelectorAll('[data-close-lead-email]').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      closeLeadEmailModal();
    });
  });
  document.getElementById('leadEmailModal')?.addEventListener('click', (event) => {
    event.preventDefault();
    if (event.target === event.currentTarget) closeLeadEmailModal();
  });
  document.getElementById('leadEmailSendBtn')?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    sendLeadEmail();
  });
  document.getElementById('agendaType').addEventListener('change', toggleMeetUrlField);
  document.getElementById('agendaMeetModeAutoBtn').addEventListener('click', () => {
    const mode = document.getElementById('agendaMeetMode');
    if (mode) mode.value = 'auto';
    updateMeetModeUI();
  });
  document.getElementById('agendaMeetModeManualBtn').addEventListener('click', () => {
    const mode = document.getElementById('agendaMeetMode');
    if (mode) mode.value = 'manual';
    updateMeetModeUI();
  });
  editLeadLink.addEventListener('click', (event) => {
    if (!activeLead || !activeLead.id) {
      event.preventDefault();
    }
  });
  deleteLeadForm.addEventListener('submit', (event) => {
    if (!activeLead || !activeLead.id) {
      event.preventDefault();
    }
  });
  document.addEventListener('keydown', (event) => {
    const emailModal = document.getElementById('leadEmailModal');
    if (event.key === 'Escape' && emailModal && !emailModal.classList.contains('hidden')) {
      closeLeadEmailModal();
      return;
    }
    if (event.key === 'Escape' && drawer && !drawer.classList.contains('hidden')) {
      closeDrawer();
    }
  });

  if (window.flatpickr) {
    const agendaDateTime = document.getElementById('agendaDateTime');
    flatpickr(agendaDateTime, {
      enableTime: true,
      time_24hr: false,
      altInput: true,
      altFormat: 'd/m/Y h:i K',
      dateFormat: 'Y-m-d h:i K',
      minDate: 'today',
      locale: 'es',
      monthSelectorType: 'static',
      shorthandCurrentMonth: true,
      disableMobile: true,
      position: 'auto left',
      onOpen: (_, __, instance) => {
        if (instance.altInput) {
          instance.set('positionElement', instance.altInput);
        }
      },
    });
  } else {
    // Fallback usable si flatpickr no carga.
    const agendaDateTime = document.getElementById('agendaDateTime');
    if (agendaDateTime) {
      agendaDateTime.readOnly = false;
      agendaDateTime.type = 'datetime-local';
    }
  }

  renderBoard();
  setupDragScroll();
  updateMeetModeUI();
  toggleMeetUrlField();

  // ── Lead Timer ──────────────────────────────────────────
  const LEAD_TIMER_KEY = 'infocus_lead_timer_v1';
  let leadTimerInterval = null;
  let leadTimerSelectedId = null;

  function fmtMinutes(min) {
    const m = Math.max(0, Math.round(Number(min) || 0));
    if (m < 60) return `${m}min`;
    return `${Math.floor(m / 60)}h ${m % 60 > 0 ? (m % 60) + 'min' : ''}`;
  }

  function fmtSecondsTimer(sec) {
    const s = Math.max(0, Math.floor(sec));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const ss = s % 60;
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(ss).padStart(2,'0')}`;
    return `${String(m).padStart(2,'0')}:${String(ss).padStart(2,'0')}`;
  }

  function getLeadTimerState() {
    try { return JSON.parse(localStorage.getItem(LEAD_TIMER_KEY) || 'null'); } catch (_) { return null; }
  }

  function setLeadTimerState(state) {
    try {
      if (state) localStorage.setItem(LEAD_TIMER_KEY, JSON.stringify(state));
      else localStorage.removeItem(LEAD_TIMER_KEY);
    } catch (_) {}
  }

  function getLeadElapsed(state) {
    if (!state) return 0;
    return Math.max(0, Math.floor((Date.now() - Number(state.started_at_ms || 0)) / 1000));
  }

  function updateLeadTimerDisplays() {
    const state = getLeadTimerState();
    const elapsed = state ? getLeadElapsed(state) : 0;
    const display = fmtSecondsTimer(elapsed);

    // Drawer active display
    const activeDiv = document.getElementById('drawerTiempoActive');
    const activeDisplay = document.getElementById('drawerTiempoActiveDisplay');
    const startBtn = document.getElementById('drawerTiempoStartBtn');
    const headerTimerLabel = document.getElementById('drawerHeaderTimerLabel');
    if (state && activeLead && state.lead_id === activeLead.id) {
      if (activeDiv) { activeDiv.classList.remove('hidden'); }
      if (activeDisplay) activeDisplay.textContent = display;
      if (startBtn) startBtn.classList.add('hidden');
      if (headerTimerLabel) headerTimerLabel.textContent = 'Detener timer';
    } else {
      if (activeDiv) activeDiv.classList.add('hidden');
      if (startBtn) startBtn.classList.remove('hidden');
      if (headerTimerLabel) headerTimerLabel.textContent = 'Iniciar timer';
    }

    // Header host
    updateLeadTimerHeader(state, elapsed);
  }

  function updateLeadTimerHeader(state, elapsed) {
    const host = document.getElementById('headerLeadTimerHost');
    if (!host) return;
    if (!state) {
      host.classList.add('hidden');
      host.innerHTML = '';
      if (window.updateHeaderTimerButtonVisibility) window.updateHeaderTimerButtonVisibility(true);
      return;
    }
    host.classList.remove('hidden');
    if (window.updateHeaderTimerButtonVisibility) window.updateHeaderTimerButtonVisibility(false);
    const display = fmtSecondsTimer(elapsed);
    if (!host.querySelector('#leadTimerHeaderCard')) {
      host.innerHTML = `
        <div id="leadTimerHeaderCard" class="inline-flex items-center gap-2 rounded-2xl border border-lime-300 bg-lime-50 px-3 py-2 shadow-sm">
          <span class="inline-flex h-2 w-2 rounded-full bg-lime-500 animate-pulse"></span>
          <div class="min-w-0">
            <div class="text-[10px] font-bold uppercase tracking-wide text-lime-800">Lead activo</div>
            <div id="leadTimerHeaderName" class="text-xs font-extrabold text-slate-900 truncate max-w-[120px]">${escapeHtml(state.lead_nombre || 'Lead')}</div>
          </div>
          <div id="leadTimerHeaderDisplay" class="text-xl font-mono font-extrabold text-lime-700 tabular-nums">${display}</div>
          <button type="button" id="leadTimerHeaderStopBtn" class="ml-1 px-2 py-1 rounded-lg bg-rose-100 text-rose-700 text-xs font-bold hover:bg-rose-200">Detener</button>
        </div>`;
      document.getElementById('leadTimerHeaderStopBtn')?.addEventListener('click', stopLeadTimer);
    } else {
      const d = host.querySelector('#leadTimerHeaderDisplay');
      if (d) d.textContent = display;
    }
  }

  async function startLeadTimer(leadId, leadNombre) {
    try {
      const res = await fetch('{{ route('api.leads.timer.iniciar') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        body: JSON.stringify({ id: leadId }),
      });
      const json = await res.json();
      if (!json.ok) throw new Error();
      setLeadTimerState({ lead_id: leadId, lead_nombre: leadNombre, started_at_ms: Date.now() });
      if (!leadTimerInterval) {
        leadTimerInterval = setInterval(updateLeadTimerDisplays, 1000);
      }
      updateLeadTimerDisplays();
      if (window.showNotification) window.showNotification(`Timer iniciado: ${leadNombre}`, 'success');
    } catch (_) {
      if (window.showNotification) window.showNotification('No se pudo iniciar el timer.', 'error');
    }
  }

  async function stopLeadTimer() {
    const state = getLeadTimerState();
    try {
      const res = await fetch('{{ route('api.leads.timer.detener') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
        body: JSON.stringify({}),
      });
      const json = await res.json();
      if (json.ok && json.lead_id) {
        // Refresh lead data
        const idx = leads.findIndex((l) => l.id === json.lead_id);
        if (idx >= 0) {
          leads[idx].tiempo_total_min = json.tiempo_total_min;
          leads[idx].tiempo_trabajado = leads[idx].tiempo_trabajado || [];
          leads[idx].tiempo_trabajado.push({ duracion_min: json.duracion_min, fecha: new Date().toISOString(), user_name: '' });
          if (activeLead && activeLead.id === json.lead_id) {
            Object.assign(activeLead, leads[idx]);
            hydrateDrawer(activeLead);
          }
        }
        renderBoard();
        if (window.showNotification) window.showNotification(`Timer detenido: ${fmtMinutes(json.duracion_min)} registrados`, 'success');
      }
    } catch (_) {}
    setLeadTimerState(null);
    clearInterval(leadTimerInterval);
    leadTimerInterval = null;
    updateLeadTimerDisplays();
  }

  function renderTiempoTrabajado(lead) {
    const totalEl = document.getElementById('drawerTiempoTotal');
    const listEl = document.getElementById('drawerTiempoList');
    const total = lead.tiempo_total_min || 0;
    if (totalEl) totalEl.textContent = fmtMinutes(total);
    const entries = Array.isArray(lead.tiempo_trabajado) ? lead.tiempo_trabajado : [];
    if (!listEl) return;
    if (!entries.length) {
      listEl.innerHTML = '<div class="text-xs text-slate-400">Sin sesiones registradas aún.</div>';
      return;
    }
    const sorted = [...entries].sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
    listEl.innerHTML = sorted.slice(0, 10).map((e) => {
      const d = e.fecha ? new Date(e.fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short' }) : '';
      return `<div class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 border border-slate-100 px-2.5 py-1.5">
        <span class="font-semibold">${escapeHtml(e.user_name || 'Usuario')}</span>
        <span class="text-slate-400">${d}</span>
        <span class="font-bold text-slate-700">${fmtMinutes(e.duracion_min)}</span>
      </div>`;
    }).join('');
  }

  // Expose for header
  window.openLeadTimerModal = function () {
    leadTimerSelectedId = null;
    const modal = document.getElementById('leadTimerModal');
    const list = document.getElementById('leadTimerList');
    const startBtn = document.getElementById('leadTimerStartBtn');
    const searchInput = document.getElementById('leadTimerSearch');
    if (!modal) return;
    if (searchInput) searchInput.value = '';
    renderLeadTimerList('');
    if (startBtn) startBtn.disabled = true;
    modal.classList.remove('hidden');
  };

  function renderLeadTimerList(query) {
    const list = document.getElementById('leadTimerList');
    const startBtn = document.getElementById('leadTimerStartBtn');
    if (!list) return;
    const q = query.toLowerCase();
    const filtered = leads.filter((l) => {
      const n = (l.nombre || '').toLowerCase();
      const e = (l.etapa || '').toLowerCase();
      return !q || n.includes(q) || e.includes(q);
    });
    if (!filtered.length) {
      list.innerHTML = '<div class="text-center text-xs text-slate-400 py-4">No hay leads que coincidan.</div>';
      return;
    }
    list.innerHTML = filtered.map((l) => {
      const selected = leadTimerSelectedId === l.id;
      return `<button type="button" data-lead-timer-id="${escapeHtml(l.id)}" class="w-full rounded-xl border px-3 py-2.5 text-left transition-colors ${selected ? 'border-lime-300 bg-lime-50' : 'border-slate-200 bg-white hover:bg-slate-50'}">
        <div class="flex items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="text-sm font-bold text-slate-800 truncate">${escapeHtml(l.nombre || 'Lead')}</div>
            <div class="text-[11px] text-slate-500">${escapeHtml(l.etapa || '')}${l.tiempo_total_min ? ' · ' + fmtMinutes(l.tiempo_total_min) + ' acumulados' : ''}</div>
          </div>
          <span class="text-[10px] font-bold border border-slate-200 rounded-full px-2 py-0.5 text-slate-500">${escapeHtml(fmtMoney(l.presupuesto_estimado ?? l.valor))}</span>
        </div>
      </button>`;
    }).join('');
    list.querySelectorAll('[data-lead-timer-id]').forEach((btn) => {
      btn.addEventListener('click', () => {
        leadTimerSelectedId = btn.getAttribute('data-lead-timer-id');
        if (startBtn) startBtn.disabled = false;
        renderLeadTimerList(document.getElementById('leadTimerSearch')?.value || '');
      });
    });
  }

  document.getElementById('leadTimerSearch')?.addEventListener('input', (e) => renderLeadTimerList(e.target.value || ''));
  document.getElementById('leadTimerModalClose')?.addEventListener('click', () => document.getElementById('leadTimerModal')?.classList.add('hidden'));
  document.getElementById('leadTimerCancelBtn')?.addEventListener('click', () => document.getElementById('leadTimerModal')?.classList.add('hidden'));
  document.getElementById('leadTimerBackdrop')?.addEventListener('click', () => document.getElementById('leadTimerModal')?.classList.add('hidden'));
  document.getElementById('leadTimerStartBtn')?.addEventListener('click', async () => {
    if (!leadTimerSelectedId) return;
    const lead = leads.find((l) => l.id === leadTimerSelectedId);
    if (!lead) return;
    document.getElementById('leadTimerModal')?.classList.add('hidden');
    await startLeadTimer(lead.id, lead.nombre || 'Lead');
  });
  document.getElementById('drawerTiempoStartBtn')?.addEventListener('click', () => {
    if (!activeLead) return;
    const state = getLeadTimerState();
    if (state) { stopLeadTimer(); return; }
    startLeadTimer(activeLead.id, activeLead.nombre || 'Lead');
  });
  document.getElementById('drawerHeaderTimerBtn')?.addEventListener('click', () => {
    if (!activeLead) return;
    const state = getLeadTimerState();
    if (state && state.lead_id === activeLead.id) {
      stopLeadTimer();
      return;
    }
    startLeadTimer(activeLead.id, activeLead.nombre || 'Lead');
  });
  document.getElementById('drawerTiempoStopBtn')?.addEventListener('click', stopLeadTimer);

  // Init timer on page load
  (async () => {
    const state = getLeadTimerState();
    if (state) {
      // Verify server still has active timer
      try {
        const res = await fetch('{{ route('api.leads.timer.activo') }}');
        const json = await res.json();
        if (!json.active) setLeadTimerState(null);
        else leadTimerInterval = setInterval(updateLeadTimerDisplays, 1000);
      } catch (_) {}
    }
    updateLeadTimerDisplays();
  })();

})();
</script>
@endsection
