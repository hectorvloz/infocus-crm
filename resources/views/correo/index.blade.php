@extends('layouts.app')
@section('title', 'Correo')

@section('content')
@php
  $company = $settings['company_name'] ?? 'Infocus CRM';
@endphp

<div class="max-w-7xl mx-auto px-4 md:px-8 py-8 space-y-6">
  <div>
    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900">Correo</h1>
    <p class="text-slate-500 text-sm mt-1">Envía correos, crea plantillas personalizadas y revisa el historial.</p>
  </div>

  @if(session('ok'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 font-medium">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 font-medium">{{ $errors->first() }}</div>
  @endif

  <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-1">
    <button type="button" data-tab-btn="correo" class="tab-btn rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100">Correo</button>
    <button type="button" data-tab-btn="plantillas" class="tab-btn rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Plantillas</button>
    <button type="button" data-tab-btn="historial" class="tab-btn rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Historial</button>
  </div>

  <section data-tab-panel="correo" class="tab-panel">
    <div class="grid grid-cols-1 gap-6">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Enviar correo</h2>

        <div class="mb-4">
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Plantillas personalizadas</label>
          <select id="customTemplateSelect" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <option value="">Selecciona una plantilla</option>
            @foreach($customTemplates as $tpl)
              <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }}</option>
            @endforeach
          </select>
        </div>

        <form method="POST" action="{{ route('correo.send') }}" class="space-y-4">
          @csrf
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente</label>
            <select id="clientSelect" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm mb-3">
              <option value="">Personalizado</option>
              @foreach($clients as $clientRow)
                <option value="{{ $clientRow['email'] }}" data-name="{{ $clientRow['name'] }}">{{ $clientRow['name'] }} · {{ $clientRow['email'] }}</option>
              @endforeach
            </select>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Para</label>
            <input name="to" value="{{ old('to') }}" placeholder="cliente@correo.com, otro@correo.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Asunto</label>
            <input id="mailSubject" name="subject" value="{{ old('subject') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Mensaje</label>
            <div class="rounded-xl border border-slate-300 overflow-hidden">
              <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
                <button type="button" data-editor-block="h1" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">H1</button>
                <button type="button" data-editor-block="h2" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">H2</button>
                <button type="button" data-editor-block="p" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Párrafo</button>
                <span class="mx-1 h-5 w-px bg-slate-200"></span>
                <button type="button" data-editor-cmd="bold" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100">B</button>
                <button type="button" data-editor-cmd="italic" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs italic text-slate-700 hover:bg-slate-100">I</button>
                <span class="mx-1 h-5 w-px bg-slate-200"></span>
                <button type="button" data-editor-cmd="insertHorizontalRule" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Línea</button>
              </div>
              <div id="mailBodyEditor" contenteditable="true" class="min-h-[260px] px-3 py-2.5 text-sm text-slate-800 focus:outline-none"></div>
            </div>
            <textarea id="mailBody" name="body" class="hidden" required>{{ old('body') }}</textarea>
          </div>
          <div class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-xl p-3">
            Variables útiles: <span class="font-semibold">{empresa}</span>, <span class="font-semibold">{fecha}</span>, <span class="font-semibold">{usuario}</span>, <span class="font-semibold">{cliente}</span>.
          </div>
          <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Enviar correo</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section data-tab-panel="plantillas" class="tab-panel hidden">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
      <h2 class="text-lg font-bold text-slate-900 mb-4">Plantillas personalizadas</h2>
      <div class="mb-4">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Seleccionar plantilla</label>
        <select id="templateEditorSelect" class="w-full md:w-[28rem] rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
          <option value="__new__">+ Añadir nueva</option>
          @foreach($customTemplates as $tpl)
            <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }}</option>
          @endforeach
        </select>
      </div>

      <form id="templateEditorForm" method="POST" action="{{ route('correo.templates.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" id="templateMethod" name="_method" value="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre</label>
            <input id="templateName" name="name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Asunto</label>
            <input id="templateSubject" name="subject" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Mensaje</label>
          <div class="rounded-xl border border-slate-300 overflow-hidden">
            <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
              <button type="button" data-template-editor-block="h1" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">H1</button>
              <button type="button" data-template-editor-block="h2" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">H2</button>
              <button type="button" data-template-editor-block="p" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Párrafo</button>
              <span class="mx-1 h-5 w-px bg-slate-200"></span>
              <button type="button" data-template-editor-cmd="bold" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100">B</button>
              <button type="button" data-template-editor-cmd="italic" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs italic text-slate-700 hover:bg-slate-100">I</button>
              <span class="mx-1 h-5 w-px bg-slate-200"></span>
              <button type="button" data-template-editor-cmd="insertHorizontalRule" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Línea</button>
            </div>
            <div id="templateBodyEditor" contenteditable="true" class="min-h-[260px] px-3 py-2.5 text-sm text-slate-800 focus:outline-none"></div>
          </div>
          <textarea id="templateBody" name="body" class="hidden" required></textarea>
        </div>
        <div class="flex items-center justify-end gap-2">
          <button id="templateSaveBtn" type="submit" class="rounded-xl bg-lime-300 px-4 py-2.5 text-sm font-bold text-slate-900 hover:bg-lime-400">Guardar plantilla</button>
        </div>
      </form>

      <form id="templateDeleteForm" method="POST" action="" class="mt-2 hidden justify-end" onsubmit="return confirm('¿Eliminar esta plantilla?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Eliminar</button>
      </form>
    </div>
  </section>

  <section data-tab-panel="historial" class="tab-panel hidden">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">Historial de correos enviados</h2>
        <form method="POST" action="{{ route('correo.verify_cron') }}">
          @csrf
          <button type="submit" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Verificar cron</button>
        </form>
      </div>
      <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-semibold {{ !empty($cronStatus['is_ok']) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
          {{ !empty($cronStatus['is_ok']) ? 'Cron activo' : 'Cron atrasado' }}
        </span>
        <span class="text-slate-500">Última señal: {{ $cronStatus['last_run_human'] ?? 'Sin datos' }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 border-b border-slate-100">
            <tr>
              <th class="px-4 py-3">Fecha</th>
              <th class="px-4 py-3">Para</th>
              <th class="px-4 py-3">Asunto</th>
              <th class="px-4 py-3">Origen</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Enviado por</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($history as $row)
              <tr>
                <td class="px-4 py-3 text-slate-600">{{ !empty($row['sent_at']) ? \Carbon\Carbon::parse($row['sent_at'])->format('d/m/Y H:i') : '—' }}</td>
                <td class="px-4 py-3 text-slate-700">{{ is_array($row['to'] ?? null) ? implode(', ', $row['to']) : ($row['to'] ?? '—') }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['subject'] ?? '—' }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $row['source'] ?? 'app' }}</td>
                <td class="px-4 py-3">
                  @php
                    $status = strtolower((string) ($row['status'] ?? 'enviado'));
                    $isFailed = $status === 'fallido';
                  @endphp
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $isFailed ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}"
                        @if($isFailed && !empty($row['error'])) title="{{ $row['error'] }}" @endif>
                    {{ $isFailed ? 'Fallido' : 'Enviado' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-500">{{ $row['sent_by_name'] ?? ($row['sent_by'] ?? 'Sistema') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-8 text-center text-slate-400">Aún no hay correos enviados desde la app.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<script>
  const customTemplates = @json($customTemplates);
  const subjectEl = document.getElementById('mailSubject');
  const bodyEl = document.getElementById('mailBody');
  const editorEl = document.getElementById('mailBodyEditor');
  const customSelect = document.getElementById('customTemplateSelect');
  const clientSelect = document.getElementById('clientSelect');
  const toInput = document.querySelector('input[name="to"]');
  const templateEditorSelect = document.getElementById('templateEditorSelect');
  const templateEditorForm = document.getElementById('templateEditorForm');
  const templateMethod = document.getElementById('templateMethod');
  const templateDeleteForm = document.getElementById('templateDeleteForm');
  const templateName = document.getElementById('templateName');
  const templateSubject = document.getElementById('templateSubject');
  const templateBody = document.getElementById('templateBody');
  const templateBodyEditor = document.getElementById('templateBodyEditor');
  const templateSaveBtn = document.getElementById('templateSaveBtn');

  function applyBlockFormat(targetEditor, block) {
    targetEditor.focus();
    try { document.execCommand('formatBlock', false, `<${block}>`); }
    catch (e) { document.execCommand('formatBlock', false, block); }
  }

  function setActiveButtons(prefix, state) {
    const activeCls = ['bg-slate-900', 'text-white', 'border-slate-900'];
    const inactiveCls = ['bg-white', 'text-slate-700', 'border-slate-200'];
    const items = [
      { key: 'h1', sel: `[data-${prefix}-block="h1"]` },
      { key: 'h2', sel: `[data-${prefix}-block="h2"]` },
      { key: 'p', sel: `[data-${prefix}-block="p"]` },
      { key: 'bold', sel: `[data-${prefix}-cmd="bold"]` },
      { key: 'italic', sel: `[data-${prefix}-cmd="italic"]` },
    ];
    items.forEach(({ key, sel }) => {
      const btn = document.querySelector(sel);
      if (!btn) return;
      const on = !!state[key];
      btn.classList.remove(...(on ? inactiveCls : activeCls));
      btn.classList.add(...(on ? activeCls : inactiveCls));
    });
  }

  function updateToolbarState(editor, prefix) {
    if (!editor) return;
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return setActiveButtons(prefix, {});
    const n = sel.anchorNode;
    const el = n?.nodeType === Node.TEXT_NODE ? n.parentElement : n;
    if (!el || !editor.contains(el)) return setActiveButtons(prefix, {});
    const block = el.closest('h1,h2,p,div');
    const tag = (block?.tagName || '').toLowerCase();
    setActiveButtons(prefix, {
      h1: tag === 'h1',
      h2: tag === 'h2',
      p: tag === 'p' || tag === 'div',
      bold: document.queryCommandState('bold'),
      italic: document.queryCommandState('italic'),
    });
  }

  function applyTemplate(subject, body) {
    subjectEl.value = subject || '';
    editorEl.innerHTML = body || '';
    bodyEl.value = editorEl.innerHTML.trim();
    updateToolbarState(editorEl, 'editor');
  }

  customSelect?.addEventListener('change', (e) => {
    const tpl = customTemplates.find(t => t.id === e.target.value);
    if (!tpl) return;
    applyTemplate(tpl.subject, tpl.body);
  });

  document.querySelectorAll('[data-editor-cmd]').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.execCommand(btn.getAttribute('data-editor-cmd'), false);
      editorEl.focus();
      bodyEl.value = editorEl.innerHTML.trim();
      updateToolbarState(editorEl, 'editor');
    });
  });

  document.querySelectorAll('[data-editor-block]').forEach((btn) => {
    btn.addEventListener('click', () => {
      applyBlockFormat(editorEl, btn.getAttribute('data-editor-block'));
      bodyEl.value = editorEl.innerHTML.trim();
      updateToolbarState(editorEl, 'editor');
    });
  });

  editorEl.addEventListener('input', () => { bodyEl.value = editorEl.innerHTML.trim(); updateToolbarState(editorEl, 'editor'); });
  editorEl.addEventListener('keyup', () => updateToolbarState(editorEl, 'editor'));
  editorEl.addEventListener('mouseup', () => updateToolbarState(editorEl, 'editor'));

  clientSelect?.addEventListener('change', () => {
    const val = clientSelect.value || '';
    if (val !== '') { toInput.value = val; toInput.readOnly = true; toInput.classList.add('bg-slate-50'); }
    else { toInput.readOnly = false; toInput.classList.remove('bg-slate-50'); if (!@json((string) old('to'))) toInput.value = ''; }
  });

  function syncTemplateEditorToTextarea() { templateBody.value = templateBodyEditor.innerHTML.trim(); }
  function setTemplateEditorContent(html) { templateBodyEditor.innerHTML = html || ''; syncTemplateEditorToTextarea(); updateToolbarState(templateBodyEditor, 'template-editor'); }

  document.querySelectorAll('[data-template-editor-cmd]').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.execCommand(btn.getAttribute('data-template-editor-cmd'), false);
      templateBodyEditor.focus();
      syncTemplateEditorToTextarea();
      updateToolbarState(templateBodyEditor, 'template-editor');
    });
  });

  document.querySelectorAll('[data-template-editor-block]').forEach((btn) => {
    btn.addEventListener('click', () => {
      applyBlockFormat(templateBodyEditor, btn.getAttribute('data-template-editor-block'));
      syncTemplateEditorToTextarea();
      updateToolbarState(templateBodyEditor, 'template-editor');
    });
  });

  templateBodyEditor?.addEventListener('input', () => { syncTemplateEditorToTextarea(); updateToolbarState(templateBodyEditor, 'template-editor'); });
  templateBodyEditor?.addEventListener('keyup', () => updateToolbarState(templateBodyEditor, 'template-editor'));
  templateBodyEditor?.addEventListener('mouseup', () => updateToolbarState(templateBodyEditor, 'template-editor'));

  const templateStoreUrl = @json(route('correo.templates.store'));
  const templateUpdatePattern = @json(route('correo.templates.update', ['id' => '__ID__']));
  const templateDeletePattern = @json(route('correo.templates.destroy', ['id' => '__ID__']));
  function resetTemplateEditor() {
    templateEditorForm.action = templateStoreUrl;
    templateMethod.value = '';
    templateName.value = '';
    templateSubject.value = '';
    setTemplateEditorContent('');
    templateSaveBtn.textContent = 'Guardar plantilla';
    templateDeleteForm.classList.add('hidden');
    templateDeleteForm.classList.remove('flex');
    templateDeleteForm.action = '';
  }
  function setTemplateEditor(id) {
    const tpl = customTemplates.find(t => t.id === id);
    if (!tpl) return resetTemplateEditor();
    templateEditorForm.action = templateUpdatePattern.replace('__ID__', tpl.id);
    templateMethod.value = 'PUT';
    templateName.value = tpl.name || '';
    templateSubject.value = tpl.subject || '';
    setTemplateEditorContent(tpl.body || '');
    templateSaveBtn.textContent = 'Actualizar plantilla';
    templateDeleteForm.action = templateDeletePattern.replace('__ID__', tpl.id);
    templateDeleteForm.classList.remove('hidden');
    templateDeleteForm.classList.add('flex');
  }
  templateEditorSelect?.addEventListener('change', () => templateEditorSelect.value === '__new__' ? resetTemplateEditor() : setTemplateEditor(templateEditorSelect.value));
  if (templateEditorSelect) { templateEditorSelect.value = '__new__'; resetTemplateEditor(); }

  @if(old('subject') === null && old('body') === null)
    applyTemplate('Seguimiento de servicio - {{ $company }}', "<h2>Hola {cliente},</h2><p>Espero que estés muy bien.</p><p>Te comparto la información solicitada.</p><hr><p>Quedo atento(a) a tu confirmación.</p><p>Saludos,<br>{usuario}<br>{{ $company }}</p>");
  @else
    editorEl.innerHTML = bodyEl.value || '';
    bodyEl.value = editorEl.innerHTML.trim();
  @endif

  document.addEventListener('selectionchange', () => {
    updateToolbarState(editorEl, 'editor');
    updateToolbarState(templateBodyEditor, 'template-editor');
  });

  const tabButtons = document.querySelectorAll('[data-tab-btn]');
  const tabPanels = document.querySelectorAll('[data-tab-panel]');
  function setTab(tab) {
    tabButtons.forEach((btn) => {
      const active = btn.dataset.tabBtn === tab;
      btn.classList.toggle('bg-slate-100', active);
      btn.classList.toggle('text-slate-700', active);
      btn.classList.toggle('text-slate-600', !active);
    });
    tabPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab));
  }
  tabButtons.forEach((btn) => btn.addEventListener('click', () => setTab(btn.dataset.tabBtn)));
  setTab('correo');
  updateToolbarState(editorEl, 'editor');
  updateToolbarState(templateBodyEditor, 'template-editor');
</script>
<style>
  #mailBodyEditor, #templateBodyEditor { line-height: 1.45; }
  #mailBodyEditor h1, #templateBodyEditor h1 { font-size: 2rem; line-height: 1.15; font-weight: 800; margin: 0 0 10px; color: #0f172a; }
  #mailBodyEditor h2, #templateBodyEditor h2 { font-size: 1.35rem; line-height: 1.2; font-weight: 700; margin: 0 0 8px; color: #0f172a; }
  #mailBodyEditor p, #templateBodyEditor p { margin: 0 0 6px; }
  #mailBodyEditor hr, #templateBodyEditor hr { border: 0; border-top: 1px solid #cbd5e1; margin: 8px 0; }
</style>
@endsection
