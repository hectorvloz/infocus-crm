@extends('layouts.app')
@section('title','Mis Notas')

@section('content')
<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
<style>
  .notes-search-shell{display:flex;align-items:center;gap:.75rem;max-width:42rem}
  .notes-search-box{position:relative;flex:1 1 auto;min-width:0}
  .notes-search-icon{position:absolute;left:1rem;top:50%;width:1.15rem;height:1.15rem;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
  .notes-search-input{width:100%;height:3.1rem;border-radius:1.15rem;border:1px solid #dbe5f1;background:#fff;padding:0 3rem 0 2.95rem;color:#1e293b;font-size:.98rem;font-weight:700;outline:none}
  .notes-search-clear{position:absolute;right:.65rem;top:50%;width:2rem;height:2rem;transform:translateY(-50%);border-radius:999px;border:1px solid #dbe5f1;color:#64748b;background:#f8fafc;display:inline-grid;place-content:center}
  .notes-search-clear.hidden{display:none}
  .notes-search-count{flex:0 0 auto;border-radius:999px;border:1px solid #dbe5f1;background:#f8fafc;padding:.55rem .8rem;color:#64748b;font-size:.78rem;font-weight:900}
  .note-ai-support-btn{display:inline-flex;align-items:center;gap:.45rem;height:2.25rem;border-radius:999px;border:2px solid #f0abfc;background:#fff;padding:0 .85rem;color:#1f2937;font-size:.78rem;font-weight:900;box-shadow:0 10px 22px rgba(217,70,239,.12);transition:background-color .15s ease,box-shadow .15s ease,transform .15s ease}
  .note-ai-support-btn:hover{background:#fdf4ff;box-shadow:0 14px 28px rgba(217,70,239,.18);transform:translateY(-1px)}
  .note-ai-support-spark{color:#d946ef;font-size:.95rem;line-height:1}
  .note-ai-support-panel{position:fixed;width:min(21rem,calc(100vw - 1.5rem));overflow:hidden;border-radius:1.25rem;border:1px solid #f5d0fe;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.22),0 0 0 1px #fae8ff;z-index:2147482500}
  .note-ai-support-panel.hidden{display:none!important}
  .note-ai-support-head{display:flex;align-items:center;gap:.75rem;border-bottom:1px solid #fae8ff;background:linear-gradient(180deg,#fff,#fdf4ff);padding:.9rem 1rem}
  .note-ai-support-avatar{display:inline-flex;width:2.3rem;height:2.3rem;align-items:center;justify-content:center;border-radius:999px;background:#fdf4ff;color:#d946ef;font-weight:900}
  .note-ai-support-title{font-size:.98rem;font-weight:950;color:#111827;line-height:1.1}
  .note-ai-support-subtitle{margin-top:.15rem;font-size:.72rem;font-weight:800;color:#94a3b8}
  .note-ai-support-close{margin-left:auto;display:inline-flex;width:1.85rem;height:1.85rem;align-items:center;justify-content:center;border-radius:.75rem;color:#94a3b8;font-size:1.4rem;font-weight:500;line-height:1}
  .note-ai-support-close:hover{background:#f1f5f9;color:#334155}
  .note-ai-support-messages{max-height:10.5rem;overflow-y:auto;background:#f8fafc;padding:.65rem;display:flex;flex-direction:column;gap:.45rem}
  .note-ai-support-message{border-radius:.85rem;padding:.58rem .7rem;font-size:.76rem;font-weight:750;line-height:1.35;box-shadow:0 8px 18px rgba(15,23,42,.06)}
  .note-ai-support-message.assistant{margin-right:2rem;background:#fff;color:#64748b}
  .note-ai-support-message.user{margin-left:2rem;background:#111827;color:#fff}
  .note-ai-support-actions{display:flex;gap:.4rem;overflow-x:auto;padding:.65rem .75rem .2rem;background:#fff;scrollbar-width:none}
  .note-ai-support-actions::-webkit-scrollbar{display:none}
  .note-ai-support-actions button{flex:0 0 auto;border-radius:999px;border:1px solid #f5d0fe;background:#fdf4ff;padding:.35rem .58rem;color:#86198f;font-size:.68rem;font-weight:900}
  .note-ai-support-actions button:hover{background:#f5d0fe;color:#701a75}
  .note-ai-support-custom{display:flex;gap:.45rem;padding:.65rem .75rem .8rem;background:#fff}
  .note-ai-support-custom input{min-width:0;flex:1;border-radius:.85rem;border:1px solid #e2e8f0;padding:.6rem .7rem;font-size:.76rem;font-weight:750;color:#1e293b;outline:none}
  .note-ai-support-custom input:focus{border-color:#f0abfc;box-shadow:0 0 0 3px rgba(240,171,252,.35)}
  .note-ai-support-custom button{border-radius:.85rem;background:#f5d0fe;padding:.6rem .8rem;color:#111827;font-size:.76rem;font-weight:950}
  .note-ai-support-custom button:disabled{opacity:.6;cursor:not-allowed}
</style>
<div id="notes-list-view">
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-slate-900">Mis Notas</h1>
      <p class="text-slate-500 mt-1">Todas tus notas rapidas organizadas por cliente</p>
    </div>
    <button id="add-note-btn" type="button" class="primary-add-btn">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14M5 12h14"/></svg>
      <span>Añadir nota</span>
    </button>
  </div>

  <div class="notes-search-shell mb-5">
    <div class="notes-search-box">
      <svg class="notes-search-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="position:absolute;left:1rem;top:50%;width:1.15rem;height:1.15rem;transform:translateY(-50%);color:#94a3b8;pointer-events:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m21 21-4.35-4.35M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z"/></svg>
      <input id="notes-search-input" type="text" class="notes-search-input" placeholder="Buscar por titulo, contenido o cliente..." autocomplete="off" aria-label="Buscar notas por titulo, contenido o cliente">
      <button id="notes-search-clear" type="button" class="notes-search-clear hidden" aria-label="Limpiar busqueda">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18 18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div id="notes-search-count" class="notes-search-count">0 resultados</div>
  </div>

  <div class="client-tabs-shell mb-8">
    <button id="client-tabs-prev" type="button" class="client-tabs-nav client-tabs-nav-left is-hidden" title="Ver categorias anteriores">
      <iconify-icon icon="lucide:chevron-left" width="20" height="20" aria-hidden="true"></iconify-icon>
    </button>
    <div id="client-tabs-scroll" class="overflow-x-auto scrollbar-hide">
      <div id="client-tabs" class="flex gap-2 pb-2 min-w-min">
        <button data-client="todos" class="client-tab-btn active shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap bg-slate-900 text-white">Todos</button>
        <button data-client="all" class="client-tab-btn shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap bg-slate-100 text-slate-600 hover:bg-slate-200">Sin cliente</button>
      </div>
    </div>
    <button id="client-tabs-next" type="button" class="client-tabs-nav client-tabs-nav-right is-hidden" title="Ver mas categorias">
      <iconify-icon icon="lucide:chevron-right" width="20" height="20" aria-hidden="true"></iconify-icon>
    </button>
  </div>

  <div class="note-cards-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <div id="notes-container" class="contents"></div>
  </div>
</div>

<div id="note-edit-view" class="hidden">
  <div class="mb-3 flex items-center justify-between gap-2 text-xs flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
      <div class="relative">
        <button id="note-client-trigger" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-300 bg-white text-slate-700 text-xs hover:bg-slate-50 transition-colors">
          <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span id="note-client-selected-label" class="truncate max-w-[120px]">Sin cliente</span>
          <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 9l6 6 6-6"/></svg>
        </button>
        <div id="note-client-dropdown" class="hidden absolute left-0 top-[calc(100%+0.4rem)] w-56 rounded-xl border border-slate-200 bg-white shadow-xl p-1.5 z-50">
          <input id="note-client-search" type="text" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs text-slate-700 mb-1.5 outline-none focus:border-indigo-300" placeholder="Buscar cliente...">
          <div id="note-client-options" class="max-h-48 overflow-y-auto space-y-0.5"></div>
        </div>
      </div>
      <button id="action-pdf" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-lime-300 text-slate-900 shadow">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v10"/><path d="M8 9l4 4 4-4"/><path d="M4 17v3h16v-3"/></svg>
        <span>PDF</span>
      </button>
      <button id="action-duplicate" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white hover:bg-neutral-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 8h12v12H8z"/><path d="M4 4h12v12H4z"/></svg>
        <span>Duplicar</span>
      </button>
      <button id="action-share" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-white hover:bg-neutral-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51 15.42 17.49"/><path d="M15.41 6.51 8.59 10.49"/></svg>
        <span>Compartir</span>
      </button>
      <div class="relative">
        <button id="action-ai-support" type="button" class="note-ai-support-btn" aria-haspopup="dialog" aria-expanded="false">
          <span class="note-ai-support-spark">✦</span>
          <span>Apoyo de IA</span>
        </button>
        <div id="note-ai-support-panel" class="note-ai-support-panel hidden">
          <div class="note-ai-support-head">
            <span class="note-ai-support-avatar">✦</span>
            <div>
              <div class="note-ai-support-title">Apoyo de IA</div>
              <div class="note-ai-support-subtitle">Edita esta nota con formato enriquecido</div>
            </div>
            <button id="note-ai-support-close" type="button" class="note-ai-support-close" aria-label="Cerrar apoyo de IA">×</button>
          </div>
          <div id="note-ai-support-messages" class="note-ai-support-messages">
            <div class="note-ai-support-message assistant">Dime qué quieres hacer con esta nota o elige un tono rápido.</div>
          </div>
          <div class="note-ai-support-actions" aria-label="Acciones rápidas de IA">
            <button type="button" data-note-ai-prompt="rewrite">Reescribir mejor</button>
            <button type="button" data-note-ai-prompt="formal">Más formal</button>
            <button type="button" data-note-ai-prompt="friendly">Más familiar</button>
            <button type="button" data-note-ai-prompt="summary">Resumir</button>
            <button type="button" data-note-ai-prompt="checklist">Convertir a checklist</button>
            <button type="button" data-note-ai-prompt="structure">Ordenar con títulos</button>
          </div>
          <div class="note-ai-support-custom">
            <input id="note-ai-support-input" type="text" placeholder="Ej. mejora la nota y subraya lo importante...">
            <button id="note-ai-support-send" type="button">Enviar</button>
          </div>
        </div>
      </div>
    </div>
    <button id="action-delete" type="button" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-rose-200 bg-white text-rose-700 hover:bg-rose-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 16h10l1-16"/></svg>
      <span>Eliminar</span>
    </button>
  </div>

  <div id="note-editor-container" class="rounded-2xl shadow-lg border border-slate-200">
    <div id="note-toolbar" class="quick-note-toolbar px-4 py-3 border-b border-[#dde0e8] bg-[#f4f5f8] sticky top-[-0.5rem] z-20">
      <div class="flex items-center gap-2 flex-wrap">
        <div id="note-format-menu-wrap" class="note-format-menu-wrap relative inline-flex shrink-0">
          <button id="note-format-trigger" type="button" class="note-format-trigger" title="Estilo de texto" aria-haspopup="listbox" aria-expanded="false">
            <span id="note-format-label">Texto normal</span>
            <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
          </button>
          <div id="note-format-menu" class="note-format-menu hidden absolute left-0 top-full mt-1 w-36 rounded-xl border border-white/15 bg-neutral-800 p-1 shadow-2xl z-50" role="listbox">
            <button type="button" class="note-format-option is-selected" data-note-format="p">Texto normal</button>
            <button type="button" class="note-format-option" data-note-format="h1">Titulo</button>
            <button type="button" class="note-format-option" data-note-format="h2">Subtitulo</button>
          </div>
        </div>
        <button data-note-cmd="checkline" type="button" class="note-tool-btn" title="Checklist"><iconify-icon icon="lucide:list-checks" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button data-note-cmd="numberline" type="button" class="note-tool-btn" title="Lista numerada"><iconify-icon icon="lucide:list-ordered" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button data-note-cmd="bold" type="button" class="note-tool-btn" title="Negrita"><iconify-icon icon="lucide:bold" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button data-note-cmd="italic" type="button" class="note-tool-btn" title="Cursiva"><iconify-icon icon="lucide:italic" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button data-note-cmd="strikeThrough" type="button" class="note-tool-btn" title="Tachado"><iconify-icon icon="lucide:strikethrough" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button data-note-cmd="highlight" type="button" class="note-tool-btn note-highlight-btn" title="Resaltar">
          <iconify-icon icon="lucide:highlighter" width="20" height="20" aria-hidden="true"></iconify-icon>
        </button>
        <button data-note-cmd="divider" type="button" class="note-tool-btn" title="Separador"><iconify-icon icon="lucide:minus" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <button id="note-emoji-toggle" type="button" class="note-tool-btn" title="Emoji">😊</button>
        <button id="note-image-btn" type="button" class="note-tool-btn" title="Imagen"><iconify-icon icon="lucide:image" width="20" height="20" aria-hidden="true"></iconify-icon></button>
        <div class="relative">
          <button id="note-color-toggle" type="button" class="note-tool-btn note-palette-toggle" title="Colores de fondo">
            <span class="note-palette-swatch" aria-hidden="true"></span>
          </button>
          <div id="note-color-popover" class="hidden absolute right-0 top-[calc(100%+0.45rem)] rounded-xl border border-slate-200 bg-white shadow-lg p-2 z-30">
            <div class="flex items-center gap-2">
              <button data-note-color="yellow" type="button" class="note-color-dot is-active" style="background:#f4dc38"></button>
              <button data-note-color="green" type="button" class="note-color-dot" style="background:#34c98d"></button>
              <button data-note-color="blue" type="button" class="note-color-dot" style="background:#31afe9"></button>
              <button data-note-color="pink" type="button" class="note-color-dot" style="background:#f46787"></button>
              <button data-note-color="purple" type="button" class="note-color-dot" style="background:#9b7df0"></button>
              <button data-note-color="white" type="button" class="note-color-dot" style="background:#c9d3e1"></button>
            </div>
          </div>
        </div>
        <input id="note-image-input" type="file" accept="image/*" class="hidden">
      </div>
      <div id="note-emoji-popover" class="hidden mt-2 rounded-xl border border-slate-200 bg-white shadow-lg p-2 w-fit">
        <div class="flex items-center gap-1.5">
          <button type="button" class="note-emoji-btn" data-note-emoji="😀">😀</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="😎">😎</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="🔥">🔥</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="✅">✅</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="📌">📌</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="💡">💡</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="🚀">🚀</button>
          <button type="button" class="note-emoji-btn" data-note-emoji="❤️">❤️</button>
        </div>
      </div>
    </div>

    <div id="note-canvas" class="relative px-4 py-4 min-h-96">
      <div id="note-editor" class="min-h-80 text-[1.02rem] leading-snug font-medium text-[#1f2d49] outline-none" contenteditable="true" spellcheck="true"></div>
    </div>

    <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 font-semibold">
      Guardado automatico
    </div>
  </div>
</div>

<div id="note-share-modal" class="fixed inset-0 z-[10000] hidden" role="dialog" aria-modal="true" aria-labelledby="note-share-title">
  <div class="fixed inset-0 bg-slate-900/55"></div>
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div id="note-share-title" class="text-lg font-extrabold text-slate-900">Compartir nota</div>
        <button id="note-share-close" type="button" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-500">✕</button>
      </div>
      <div class="p-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-2">
          <select id="share-user-select" class="h-10 rounded-xl border border-slate-200 px-3 text-sm text-slate-800"></select>
          <select id="share-mode-select" class="h-10 rounded-xl border border-slate-200 px-3 text-sm text-slate-800">
            <option value="view">Solo ver</option>
            <option value="edit">Puede editar</option>
          </select>
          <button id="share-add-btn" type="button" class="h-10 px-4 rounded-xl bg-slate-900 text-white text-sm font-semibold">Agregar</button>
        </div>
        <div id="share-collaborators-list" class="space-y-2"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const NOTES_USER_KEY = @json((string) (auth()->id() ?? session('user.id') ?? session('user.email') ?? 'anon'));
  const CURRENT_USER_NAME = @json((string) (optional(auth()->user())->name ?? session('user.name') ?? 'Usuario'));
  const NOTES_KEY = `infocus_quick_notes_v1_${NOTES_USER_KEY}`;
  const notesListView = document.getElementById('notes-list-view');
  const noteEditView = document.getElementById('note-edit-view');
  const notesContainer = document.getElementById('notes-container');
  const clientTabs = document.getElementById('client-tabs');
  const clientTabsScroll = document.getElementById('client-tabs-scroll');
  const clientTabsPrev = document.getElementById('client-tabs-prev');
  const clientTabsNext = document.getElementById('client-tabs-next');
  const notesSearchInput = document.getElementById('notes-search-input');
  const notesSearchClear = document.getElementById('notes-search-clear');
  const notesSearchCount = document.getElementById('notes-search-count');
  const noteEditor = document.getElementById('note-editor');
  const noteCanvas = document.getElementById('note-canvas');
  const noteEmojiToggle = document.getElementById('note-emoji-toggle');
  const noteEmojiPopover = document.getElementById('note-emoji-popover');
  const noteImageBtn = document.getElementById('note-image-btn');
  const noteImageInput = document.getElementById('note-image-input');
  const noteColorToggle = document.getElementById('note-color-toggle');
  const noteColorPopover = document.getElementById('note-color-popover');
  const noteFormatWrap = document.getElementById('note-format-menu-wrap');
  const noteFormatTrigger = document.getElementById('note-format-trigger');
  const noteFormatLabel = document.getElementById('note-format-label');
  const noteFormatMenu = document.getElementById('note-format-menu');
  const noteFormatOptions = Array.from(document.querySelectorAll('[data-note-format]'));
  const globalBackButton = document.getElementById('global-header-back-btn');

  const actionPdf = document.getElementById('action-pdf');
  const actionDuplicate = document.getElementById('action-duplicate');
  const actionShare = document.getElementById('action-share');
  const actionAiSupport = document.getElementById('action-ai-support');
  const noteAiSupportPanel = document.getElementById('note-ai-support-panel');
  const noteAiSupportClose = document.getElementById('note-ai-support-close');
  const noteAiSupportInput = document.getElementById('note-ai-support-input');
  const noteAiSupportSend = document.getElementById('note-ai-support-send');
  const actionDelete = document.getElementById('action-delete');
  const addNoteBtn = document.getElementById('add-note-btn');
  const noteClientTrigger = document.getElementById('note-client-trigger');
  const noteClientSelectedLabel = document.getElementById('note-client-selected-label');
  const noteClientDropdown = document.getElementById('note-client-dropdown');
  const noteClientOptions = document.getElementById('note-client-options');
  const noteClientSearch = document.getElementById('note-client-search');
  let activeNoteLinkedClient = '';

  const noteToolbarButtons = Array.from(document.querySelectorAll('#note-edit-view [data-note-cmd]'));
  const noteColorButtons = Array.from(document.querySelectorAll('#note-edit-view [data-note-color]'));
  const noteShareModal = document.getElementById('note-share-modal');
  const noteShareClose = document.getElementById('note-share-close');
  const shareUserSelect = document.getElementById('share-user-select');
  const shareModeSelect = document.getElementById('share-mode-select');
  const shareAddBtn = document.getElementById('share-add-btn');
  const shareCollaboratorsList = document.getElementById('share-collaborators-list');
  const NOTE_AI_CHAT_URL = @json(route('api.ai.chat'));
  const NOTE_AI_EXECUTE_URL = @json(route('api.ai.actions.execute'));

  let allNotes = [];
  let clients = [];
  let currentFilter = 'todos';
  let currentSearchQuery = '';
  let editingNoteIndex = -1;
  let activeNoteColor = 'yellow';
  let autosaveTimer = null;
  let notesSyncTimer = null;
  let isSavingToServer = false;
  let collaboratorsCatalog = [];

  const palette = {
    yellow: { editorClass: 'note-editor-yellow', cardBg: '#fef9c3', cardBorder: '#fde047' },
    green: { editorClass: 'note-editor-green', cardBg: '#dff4e7', cardBorder: '#9fd4ad' },
    blue: { editorClass: 'note-editor-blue', cardBg: '#def0ff', cardBorder: '#9cc6ed' },
    pink: { editorClass: 'note-editor-pink', cardBg: '#fee7f0', cardBorder: '#f5b7cd' },
    purple: { editorClass: 'note-editor-purple', cardBg: '#efe8ff', cardBorder: '#cdbdf8' },
    white: { editorClass: 'note-editor-white', cardBg: '#f8fafc', cardBorder: '#d5dee9' },
  };

  function loadNotes() {
    try {
      const parsed = JSON.parse(localStorage.getItem(NOTES_KEY) || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  }

  function broadcastNotesUpdated(source = 'mis-notas') {
    window.dispatchEvent(new CustomEvent('infocus-notes-updated', {
      detail: { key: NOTES_KEY, source },
    }));
  }

  function saveNotes() {
    localStorage.setItem(NOTES_KEY, JSON.stringify(allNotes));
    broadcastNotesUpdated();
    queueServerSync();
  }

  function queueServerSync() {
    clearTimeout(notesSyncTimer);
    notesSyncTimer = setTimeout(syncNotesToServer, 500);
  }

  async function syncNotesToServer() {
    if (isSavingToServer) return;
    isSavingToServer = true;
    try {
      await fetch('/api/mis-notas', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ notes: allNotes }),
      });
    } catch (_) {
      // Silencioso: mantenemos respaldo local y reintentamos en siguiente cambio.
    } finally {
      isSavingToServer = false;
    }
  }

  async function hydrateNotes() {
    const legacyLocal = loadNotes();
    const normalizeLocalNote = (note) => ({
      ...note,
      ownerKey: note?.ownerKey || NOTES_USER_KEY,
      ownerName: note?.ownerName || CURRENT_USER_NAME,
      collaborators: Array.isArray(note?.collaborators) ? note.collaborators : [],
      permission: note?.permission || 'owner',
      isShared: !!note?.isShared,
    });
    try {
      const res = await fetch('/api/mis-notas', { headers: { Accept: 'application/json' } });
      const json = await res.json();
      const remote = Array.isArray(json?.data) ? json.data : [];
      if (remote.length > 0) {
        allNotes = remote;
        localStorage.setItem(NOTES_KEY, JSON.stringify(remote));
        broadcastNotesUpdated('mis-notas-hydrate');
        return;
      }
      allNotes = legacyLocal.map(normalizeLocalNote);
      if (legacyLocal.length > 0) {
        await syncNotesToServer();
      }
    } catch (_) {
      allNotes = legacyLocal.map(normalizeLocalNote);
    }
  }

  function queueAutosave() {
    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(saveCurrentNoteFromEditor, 450);
  }

  function normalizeHtml(html) {
    const holder = document.createElement('div');
    holder.innerHTML = String(html || '').replace(/<div><br><\/div>/g, '<div></div>');
    scrubNoteHtml(holder);
    linkifyNoteTextNodes(holder);
    scrubNoteHtml(holder);
    return holder.innerHTML.trim();
  }

  function scrubNoteHtml(root) {
    if (!root) return;
    root.querySelectorAll('script,style,meta,link,title,xml,o\\:p').forEach((node) => node.remove());
    root.querySelectorAll('font').forEach((node) => {
      const fragment = document.createDocumentFragment();
      while (node.firstChild) fragment.appendChild(node.firstChild);
      node.replaceWith(fragment);
    });
    root.querySelectorAll('*').forEach((el) => {
      const tag = el.tagName.toLowerCase();
      const safeStyle = buildSafeNoteStyle(el);
      const allowedClasses = Array.from(el.classList || []).filter((className) => {
        return className.startsWith('note-') || className === 'is-empty';
      });
      Array.from(el.attributes).forEach((attr) => {
        const name = attr.name.toLowerCase();
        const keepLinkAttr = tag === 'a' && ['href', 'target', 'rel'].includes(name);
        const keepImageAttr = tag === 'img' && ['src', 'alt', 'loading'].includes(name);
        const keepCheckboxAttr = tag === 'input' && ['type', 'checked', 'contenteditable'].includes(name);
        const keepEditorData = ['data-placeholder', 'data-note-number', 'data-note-scale'].includes(name);
        if (!keepLinkAttr && !keepImageAttr && !keepCheckboxAttr && !keepEditorData) {
          el.removeAttribute(attr.name);
        }
      });
      if (safeStyle) el.setAttribute('style', safeStyle);
      if (allowedClasses.length > 0) {
        el.className = allowedClasses.join(' ');
      } else {
        el.removeAttribute('class');
      }
      if (tag === 'a') {
        const href = String(el.getAttribute('href') || '').trim();
        if (!isSafeNoteHref(href)) {
          el.removeAttribute('href');
        } else {
          el.setAttribute('target', '_blank');
          el.setAttribute('rel', 'noopener noreferrer');
        }
      }
      if (tag === 'input' && !el.classList.contains('note-checkbox')) {
        el.remove();
      }
    });
  }

  function buildSafeNoteStyle(el) {
    const tag = el.tagName.toLowerCase();
    const styles = [];
    const backgroundColor = el.style?.backgroundColor;
    if (backgroundColor && backgroundColor !== 'transparent' && backgroundColor !== 'rgba(0, 0, 0, 0)') {
      styles.push('background-color: ' + backgroundColor);
    }
    if (tag === 'img') {
      const width = el.style?.width || '';
      const maxWidth = el.style?.maxWidth || '';
      if (/^\d+(\.\d+)?%$/.test(width)) styles.push('width: ' + width);
      if (maxWidth === '100%') styles.push('max-width: 100%');
    }
    return styles.length ? styles.join('; ') : '';
  }

  function isSafeNoteHref(href) {
    if (!href) return false;
    return /^(https?:|mailto:|tel:|#|\/)/i.test(href);
  }

  function normalizeNoteAutolinkHref(raw = '') {
    const value = String(raw || '').trim();
    if (!value) return '';
    const href = /^www\./i.test(value) ? `https://${value}` : value;
    return /^(https?:\/\/|mailto:|tel:)/i.test(href) ? href : '';
  }

  function splitNoteAutolinkPunctuation(value = '') {
    let text = String(value || '');
    let suffix = '';
    while (/[),.;:!?]$/.test(text)) {
      const char = text.slice(-1);
      if (char === ')' && (text.match(/\(/g) || []).length >= (text.match(/\)/g) || []).length) break;
      suffix = char + suffix;
      text = text.slice(0, -1);
    }
    return { text, suffix };
  }

  function linkifyNoteTextNodes(root) {
    if (!root) return;
    const urlPattern = /\b((?:https?:\/\/|www\.)[^\s<>"']+)/gi;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const parent = node.parentElement;
        if (!parent || parent.closest('a,script,style,textarea,button')) return NodeFilter.FILTER_REJECT;
        urlPattern.lastIndex = 0;
        return urlPattern.test(node.nodeValue || '') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      },
    });
    const textNodes = [];
    while (walker.nextNode()) textNodes.push(walker.currentNode);
    textNodes.forEach((node) => {
      const fragment = document.createDocumentFragment();
      const source = node.nodeValue || '';
      let lastIndex = 0;
      source.replace(urlPattern, (match, _url, offset) => {
        const { text, suffix } = splitNoteAutolinkPunctuation(match);
        const href = normalizeNoteAutolinkHref(text);
        if (!href) return match;
        if (offset > lastIndex) fragment.appendChild(document.createTextNode(source.slice(lastIndex, offset)));
        const link = document.createElement('a');
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = text;
        fragment.appendChild(link);
        if (suffix) fragment.appendChild(document.createTextNode(suffix));
        lastIndex = offset + match.length;
        return match;
      });
      if (lastIndex < source.length) fragment.appendChild(document.createTextNode(source.slice(lastIndex)));
      node.replaceWith(fragment);
    });
  }

  function cleanPastedNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      return document.createTextNode(node.nodeValue || '');
    }
    if (node.nodeType !== Node.ELEMENT_NODE) {
      return document.createDocumentFragment();
    }

    const sourceTag = node.tagName.toLowerCase();
    if (['script', 'style', 'meta', 'link', 'title', 'xml', 'o:p'].includes(sourceTag)) {
      return document.createDocumentFragment();
    }
    if (sourceTag === 'br') return document.createElement('br');

    const sourceStyle = String(node.getAttribute('style') || '');
    const sourceText = (node.textContent || '').trim();
    const isBoldStyle = /font-weight\s*:\s*(bold|bolder|[6-9]00)/i.test(sourceStyle);
    const isItalicStyle = /font-style\s*:\s*italic/i.test(sourceStyle);
    const isUnderlineStyle = /text-decoration[^;]*underline/i.test(sourceStyle);
    const isStrikeStyle = /text-decoration[^;]*(line-through|strike)/i.test(sourceStyle);
    const isHighlightStyle = /background(?:-color)?\s*:\s*(?:rgb\(254,\s*240,\s*138\)|#?fef08a|yellow)/i.test(sourceStyle);
    const isHeadingLike = ['p', 'div', 'span'].includes(sourceTag)
      && isBoldStyle
      && sourceText.length > 0
      && sourceText.length <= 120
      && !/[.!?]$/.test(sourceText);

    const tagMap = {
      h1: 'h2',
      h2: 'h2',
      h3: 'h3',
      h4: 'h3',
      h5: 'h3',
      h6: 'h3',
      p: 'p',
      div: 'p',
      section: 'p',
      article: 'p',
      main: 'p',
      header: 'p',
      footer: 'p',
      address: 'p',
      pre: 'p',
      blockquote: 'blockquote',
      ul: 'ul',
      ol: 'ol',
      li: 'li',
      b: 'strong',
      strong: 'strong',
      i: 'em',
      em: 'em',
      s: 's',
      strike: 's',
      del: 's',
      u: 'u',
      span: 'span',
      mark: 'mark',
      a: 'a',
    };

    const targetTag = isHeadingLike ? 'h2' : (tagMap[sourceTag] || null);
    const target = targetTag ? document.createElement(targetTag) : document.createDocumentFragment();
    Array.from(node.childNodes).forEach((child) => target.appendChild(cleanPastedNode(child)));

    if (target.nodeType === Node.ELEMENT_NODE) {
      if (targetTag === 'a') {
        const href = String(node.getAttribute('href') || '').trim();
        if (isSafeNoteHref(href)) {
          target.setAttribute('href', href);
          target.setAttribute('rel', 'noopener noreferrer');
          target.setAttribute('target', '_blank');
        }
      }
      if (!['h2', 'h3', 'strong', 'b'].includes(targetTag) && isBoldStyle) {
        const strong = document.createElement('strong');
        while (target.firstChild) strong.appendChild(target.firstChild);
        target.appendChild(strong);
      }
      if (!['em', 'i'].includes(targetTag) && isItalicStyle) {
        const em = document.createElement('em');
        while (target.firstChild) em.appendChild(target.firstChild);
        target.appendChild(em);
      }
      if (targetTag !== 'u' && isUnderlineStyle) {
        const underline = document.createElement('u');
        while (target.firstChild) underline.appendChild(target.firstChild);
        target.appendChild(underline);
      }
      if (!['s', 'strike'].includes(targetTag) && isStrikeStyle) {
        const strike = document.createElement('s');
        while (target.firstChild) strike.appendChild(target.firstChild);
        target.appendChild(strike);
      }
      if (isHighlightStyle) {
        target.setAttribute('style', 'background-color:#fef08a');
      }
      if (['p', 'h2', 'h3', 'blockquote', 'li'].includes(targetTag) && !(target.textContent || '').trim() && !target.querySelector('br')) {
        target.innerHTML = '<br>';
      }
    }

    return target;
  }

  function normalizePastedHtml(html) {
    const source = document.createElement('div');
    source.innerHTML = String(html || '');
    const holder = document.createElement('div');
    Array.from(source.childNodes).forEach((node) => holder.appendChild(cleanPastedNode(node)));
    scrubNoteHtml(holder);
    linkifyNoteTextNodes(holder);
    scrubNoteHtml(holder);
    return holder.innerHTML;
  }

  function plainTextToNoteHtml(text) {
    return String(text || '')
      .replace(/\r\n?/g, '\n')
      .split(/\n{2,}/)
      .map((paragraph) => {
        const lines = paragraph.split('\n').map((line) => escapeNoteHtml(line));
        return '<p>' + (lines.join('<br>') || '<br>') + '</p>';
      })
      .join('');
  }

  function handleNotePaste(event) {
    if (!canEditCurrentNote()) return;
    const clipboard = event.clipboardData || window.clipboardData;
    if (!clipboard) return;
    const html = clipboard.getData('text/html');
    const text = clipboard.getData('text/plain');
    if (!html && !text) return;

    event.preventDefault();
    const cleanHtml = html ? normalizePastedHtml(html) : plainTextToNoteHtml(text);
    document.execCommand('insertHTML', false, cleanHtml || '<p><br></p>');
    ensureHeadingStructure();
    enforceSingleLineTitle();
    renumberNumberLines();
    syncChecklistVisualState();
    updateTitlePlaceholder();
    queueAutosave();
    updateToolbarActiveStates();
    updateInfocusAiCurrentNoteContext();
  }

  function htmlToPlainText(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html || '';
    temp.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
      const marker = checkbox.checked ? '[x] ' : '[ ] ';
      checkbox.replaceWith(document.createTextNode(marker));
    });
    // Insertar salto de linea tras cada elemento de bloque para preservar estructura
    temp.querySelectorAll('h1,h2,h3,h4,h5,h6,p,div,li').forEach((el) => {
      el.after(document.createTextNode('\n'));
    });
    return (temp.textContent || '').replace(/\u00a0/g, ' ').replace(/\n{3,}/g, '\n\n').trim();
  }

  function normalizeSearchText(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function resolveNoteClientName(note) {
    const raw = String(note?.linkedClient || '').trim();
    if (!raw) return '';
    const match = clients.find((client) => {
      const id = String(client.id ?? client.uuid ?? '').trim();
      const name = String(client.empresa || client.nombre || client.name || '').trim();
      return raw === id || normalizeSearchText(raw) === normalizeSearchText(name);
    });
    return String(match?.empresa || match?.nombre || match?.name || raw).trim();
  }

  function noteMatchesSearch(note, query) {
    const normalizedQuery = normalizeSearchText(query);
    if (!normalizedQuery) return true;
    const htmlHolder = document.createElement('div');
    htmlHolder.innerHTML = String(note.html || '');
    const heading = htmlHolder.querySelector('h1,h2,h3')?.textContent || note.title || '';
    const body = htmlToPlainText(note.html || note.plainText || '');
    const client = resolveNoteClientName(note);
    return normalizeSearchText(heading + ' ' + body + ' ' + client).includes(normalizedQuery);
  }

  function highlightSearchInElement(root, query) {
    const needle = normalizeSearchText(query);
    if (!root || !needle) return;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const text = node.nodeValue || '';
        if (!text.trim()) return NodeFilter.FILTER_REJECT;
        const parent = node.parentElement;
        if (parent?.closest?.('script,style,mark,.note-search-match')) return NodeFilter.FILTER_REJECT;
        return normalizeSearchText(text).includes(needle) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      },
    });
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach((node) => {
      const text = node.nodeValue || '';
      const normalized = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      const fragment = document.createDocumentFragment();
      let start = 0;
      let index = normalized.indexOf(needle);
      while (index !== -1) {
        if (index > start) fragment.appendChild(document.createTextNode(text.slice(start, index)));
        const mark = document.createElement('mark');
        mark.className = 'note-search-match';
        mark.textContent = text.slice(index, index + needle.length);
        fragment.appendChild(mark);
        start = index + needle.length;
        index = normalized.indexOf(needle, start);
      }
      if (start < text.length) fragment.appendChild(document.createTextNode(text.slice(start)));
      node.parentNode?.replaceChild(fragment, node);
    });
  }

  function highlightSearchHtml(html, query) {
    if (!String(query || '').trim()) return html;
    const holder = document.createElement('span');
    holder.innerHTML = String(html || '');
    highlightSearchInElement(holder, query);
    return holder.innerHTML;
  }

  function updateInfocusAiCurrentNoteContext() {
    if (editingNoteIndex < 0 || !allNotes[editingNoteIndex]) {
      window.__infocusAiCurrentNote = null;
      return;
    }

    const note = allNotes[editingNoteIndex];
    const html = normalizeHtml(noteEditor?.innerHTML || note.html || '');
    window.__infocusAiCurrentNote = {
      id: note.id || '',
      title: note.title || extractNoteTitleFromPlain(note.plainText || ''),
      html,
      plainText: htmlToPlainText(html || note.plainText || '').slice(0, 2600),
      client_name: resolveNoteClientName(note) || activeNoteLinkedClient || '',
      permission: note.permission || 'owner',
    };
  }

  function setAiNoteWorking(isWorking) {
    noteCanvas?.classList.toggle('note-ai-working', !!isWorking);
    noteEditor?.classList.toggle('note-ai-writing', !!isWorking);
  }

  function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  async function animateAiNoteReplacement(html) {
    if (!noteEditor) return;
    const temp = document.createElement('div');
    temp.innerHTML = html || '<p><br></p>';
    const nodes = Array.from(temp.childNodes).filter((node) => {
      return node.nodeType === Node.ELEMENT_NODE || String(node.textContent || '').trim() !== '';
    });
    noteEditor.innerHTML = '';
    noteEditor.focus();

    for (const node of nodes) {
      const clone = node.cloneNode(true);
      if (clone.nodeType === Node.ELEMENT_NODE) {
        clone.classList.add('note-ai-line-enter');
      }
      noteEditor.appendChild(clone);
      await sleep(55);
    }

    ensureHeadingStructure();
    syncChecklistVisualState();
  }

  window.__infocusAiApplyNoteUpdate = async function (payload) {
    if (!payload || editingNoteIndex < 0 || !allNotes[editingNoteIndex]) return false;
    const current = allNotes[editingNoteIndex];
    if (String(current.id || '') !== String(payload.id || '')) return false;

    setAiNoteWorking(true);
    await sleep(180);
    await animateAiNoteReplacement(payload.html || '');

    allNotes[editingNoteIndex] = {
      ...current,
      title: payload.title || extractNoteTitleFromPlain(payload.plainText || ''),
      html: normalizeHtml(noteEditor.innerHTML || payload.html || ''),
      plainText: payload.plainText || htmlToPlainText(noteEditor.innerHTML || ''),
      updatedAt: payload.updatedAt || Date.now(),
    };
    saveNotes();
    updateInfocusAiCurrentNoteContext();
    setTimeout(() => setAiNoteWorking(false), 800);
    return true;
  };

  window.addEventListener('infocus-ai-note-working', (event) => {
    const noteId = event.detail?.id || window.__infocusAiCurrentNote?.id || '';
    if (editingNoteIndex < 0 || !allNotes[editingNoteIndex]) return;
    if (noteId && String(allNotes[editingNoteIndex].id || '') !== String(noteId)) return;
    setAiNoteWorking(!!event.detail?.working);
  });

  function positionNoteAiSupportPanel() {
    if (!noteAiSupportPanel || !actionAiSupport || noteAiSupportPanel.classList.contains('hidden')) return;
    const rect = actionAiSupport.getBoundingClientRect();
    const gap = 10;
    const panelWidth = Math.min(336, window.innerWidth - 24);
    noteAiSupportPanel.style.width = `${panelWidth}px`;
    const left = Math.max(12, Math.min(window.innerWidth - panelWidth - 12, rect.right - panelWidth));
    const panelHeight = noteAiSupportPanel.offsetHeight || 320;
    const topAbove = rect.top - panelHeight - gap;
    const top = topAbove >= 12 ? topAbove : Math.min(window.innerHeight - panelHeight - 12, rect.bottom + gap);
    noteAiSupportPanel.style.left = `${left}px`;
    noteAiSupportPanel.style.top = `${Math.max(12, top)}px`;
  }

  function toggleNoteAiSupport(forceState = null) {
    if (!noteAiSupportPanel || !actionAiSupport) return;
    const shouldShow = forceState === null ? noteAiSupportPanel.classList.contains('hidden') : !!forceState;
    noteAiSupportPanel.classList.toggle('hidden', !shouldShow);
    actionAiSupport.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
    if (shouldShow) {
      updateInfocusAiCurrentNoteContext();
      requestAnimationFrame(positionNoteAiSupportPanel);
      setTimeout(() => noteAiSupportInput?.focus(), 0);
    }
  }

  function appendNoteAiSupportMessage(role, text) {
    const box = document.getElementById('note-ai-support-messages');
    if (!box) return;
    const node = document.createElement('div');
    node.className = `note-ai-support-message ${role === 'user' ? 'user' : 'assistant'}`;
    node.textContent = text;
    box.appendChild(node);
    box.scrollTop = box.scrollHeight;
  }

  function noteAiQuickPrompt(kind) {
    const map = {
      rewrite: 'Reescribe esta nota para que quede más clara, mejor redactada y profesional, manteniendo la idea original.',
      formal: 'Reescribe esta nota con un tono más formal y ordenado.',
      friendly: 'Reescribe esta nota con un tono más familiar, cercano y natural.',
      summary: 'Resume esta nota y deja solo lo importante, con títulos claros.',
      checklist: 'Convierte esta nota en un checklist accionable con casillas [ ] y pasos claros.',
      structure: 'Organiza esta nota con título principal, subtítulos, listas, negritas, subrayados y resaltados donde ayude.',
    };
    return map[kind] || '';
  }

  function buildNoteAiInstruction(userInstruction) {
    updateInfocusAiCurrentNoteContext();
    const note = window.__infocusAiCurrentNote || {};
    const title = note.title || 'Nota sin titulo';
    const noteId = note.id || '';
    const instruction = String(userInstruction || '').trim();
    return [
      'Trabaja únicamente sobre la nota personal abierta en Mis Notas.',
      `Nota ID: ${noteId}`,
      `Título actual: ${title}`,
      `Instrucción del usuario: ${instruction}`,
      '',
      'Devuelve una propuesta lista para aplicar con este formato exacto:',
      'Actualizar nota personal:',
      `Nota ID: ${noteId}`,
      'Título: [nuevo título]',
      'Contenido:',
      '[contenido final completo]',
      '',
      'Usa formato enriquecido compatible cuando aporte claridad:',
      '- # para título principal y ## para subtítulos',
      '- **negrita**, _cursiva_, ++subrayado++, ~~tachado~~, ==resaltado==',
      '- [ ] y [x] para checklist',
      '- listas numeradas y viñetas',
      '- enlaces en formato [texto](https://url.com)',
      '',
      'No respondas con explicación larga: entrega la propuesta final aplicable.',
    ].join('\n');
  }

  function noteAiContext(extraMessage = '') {
    updateInfocusAiCurrentNoteContext();
    return {
      page: document.title || 'Mis Notas',
      url: window.location.href,
      current_note: window.__infocusAiCurrentNote || null,
      forced_intent: 'note_update',
      last_user_message: String(extraMessage || ''),
    };
  }

  async function sendNoteAiSupport(message) {
    if (!canEditCurrentNote()) {
      appendNoteAiSupportMessage('assistant', 'Esta nota es de solo lectura para tu usuario.');
      return;
    }
    const userMessage = String(message || noteAiSupportInput?.value || '').trim();
    if (!userMessage) return;

    saveCurrentNoteFromEditor();
    updateInfocusAiCurrentNoteContext();
    if (!window.__infocusAiCurrentNote?.id) {
      appendNoteAiSupportMessage('assistant', 'Abre una nota antes de usar Apoyo de IA.');
      return;
    }

    appendNoteAiSupportMessage('user', userMessage);
    if (noteAiSupportInput) noteAiSupportInput.value = '';
    if (noteAiSupportSend) {
      noteAiSupportSend.disabled = true;
      noteAiSupportSend.textContent = 'Pensando...';
    }
    appendNoteAiSupportMessage('assistant', 'Estoy preparando una versión enriquecida de la nota...');
    setAiNoteWorking(true);

    try {
      const instruction = buildNoteAiInstruction(userMessage);
      const chatResponse = await fetch(NOTE_AI_CHAT_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify({
          chat_id: null,
          message: instruction,
          context: noteAiContext(userMessage),
        }),
      });
      const chatData = await chatResponse.json().catch(() => ({}));
      if (!chatResponse.ok) throw new Error(chatData.message || 'chat_failed');
      const proposal = String(chatData.message?.content || '').trim();
      if (!proposal) throw new Error('empty_proposal');

      const executeResponse = await fetch(NOTE_AI_EXECUTE_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken, 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify({
          chat_id: chatData.chat_id || null,
          proposal,
          context: noteAiContext(userMessage),
        }),
      });
      const executeData = await executeResponse.json().catch(() => ({}));
      if (!executeResponse.ok || !executeData.ok || !executeData.note_update) {
        throw new Error(executeData.message?.content || 'execute_failed');
      }

      await window.__infocusAiApplyNoteUpdate(executeData.note_update);
      appendNoteAiSupportMessage('assistant', 'Listo, actualicé la nota con formato enriquecido.');
    } catch (error) {
      console.error(error);
      appendNoteAiSupportMessage('assistant', 'No pude aplicar el cambio con IA. Intenta con una instrucción más concreta.');
      setAiNoteWorking(false);
    } finally {
      if (noteAiSupportSend) {
        noteAiSupportSend.disabled = false;
        noteAiSupportSend.textContent = 'Enviar';
      }
      noteAiSupportInput?.focus();
    }
  }

  window.addEventListener('infocus-ai-undo-applied', async (event) => {
    if (event.detail?.store !== 'mis_notas') return;
    const activeId = editingNoteIndex >= 0 ? String(allNotes[editingNoteIndex]?.id || '') : '';
    await hydrateNotes();
    const nextIndex = allNotes.findIndex((note) => String(note.id || '') === activeId);
    if (nextIndex >= 0) {
      editingNoteIndex = nextIndex;
      paintNoteEditor(allNotes[nextIndex], { focus: false });
    } else {
      editingNoteIndex = -1;
      noteEditView.classList.add('hidden');
      notesListView.classList.remove('hidden');
      window.__infocusAiCurrentNote = null;
      syncHeaderBackButton();
    }
    renderNotes();
  });

  function getTitlePlaceholderHtml() {
    return '<h1 class="note-title-placeholder is-empty" data-placeholder="Nota sin titulo"><br></h1>';
  }

  function updateTitlePlaceholder() {
    const heading = noteEditor.querySelector('h1[data-placeholder]');
    if (!heading) return;
    const isEmpty = (heading.textContent || '').replace(/\u00a0/g, ' ').trim() === '';
    heading.classList.toggle('is-empty', isEmpty);
  }

  function ensureHeadingStructure() {
    if (!noteEditor) return;
    const first = noteEditor.firstElementChild;
    if (!first) {
      noteEditor.innerHTML = getTitlePlaceholderHtml() + '<p><br></p>';
      updateTitlePlaceholder();
      return;
    }
    const tag = first.tagName.toLowerCase();
    if (!['h1', 'h2', 'h3'].includes(tag)) {
      const heading = document.createElement('h1');
      heading.innerHTML = first.innerHTML || first.textContent || '<br>';
      noteEditor.replaceChild(heading, first);
    } else if (tag !== 'h1') {
      const heading = document.createElement('h1');
      heading.innerHTML = first.innerHTML || first.textContent || '<br>';
      noteEditor.replaceChild(heading, first);
    }
    const title = noteEditor.firstElementChild;
    if (title && title.tagName.toLowerCase() === 'h1' && (title.textContent || '').trim() === '') {
      title.classList.add('note-title-placeholder');
      title.dataset.placeholder = 'Nota sin titulo';
    }
    updateTitlePlaceholder();
  }

  function enforceSingleLineTitle() {
    const heading = noteEditor.firstElementChild;
    if (!heading || heading.tagName.toLowerCase() !== 'h1') return;
    const lines = (heading.innerText || '').replace(/\r/g, '').split('\n');
    if (lines.length <= 1) return;
    const firstLine = lines.shift() || '';
    heading.textContent = firstLine;
    let cursor = heading;
    lines.forEach((line) => {
      if (!line.trim()) return;
      const p = document.createElement('p');
      p.textContent = line;
      cursor.insertAdjacentElement('afterend', p);
      cursor = p;
    });
  }

  function applyEditorColor(color) {
    Object.values(palette).forEach((tone) => noteCanvas.classList.remove(tone.editorClass));
    const selected = palette[color] ? color : 'yellow';
    noteCanvas.classList.add(palette[selected].editorClass);
    activeNoteColor = selected;
    noteColorButtons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.noteColor === selected);
    });
  }

  function placeCaret(target) {
    if (!target) return;
    const range = document.createRange();
    const selection = window.getSelection();
    if (!selection) return;
    target.focus?.();
    range.selectNodeContents(target);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
  }

  function getCurrentBlock() {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return null;
    let node = selection.getRangeAt(0).startContainer;
    if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;
    if (!node || !noteEditor.contains(node)) return null;
    return node.closest('.note-checkline, .note-numberline, h1, h2, h3, p, div') || null;
  }

  function isBlockEmpty(block) {
    if (!block) return true;
    const clone = block.cloneNode(true);
    clone.querySelectorAll('input, .note-number-marker').forEach((n) => n.remove());
    return (clone.textContent || '').replace(/\u00a0/g, ' ').trim() === '';
  }

  function createChecklistLine() {
    const line = document.createElement('div');
    line.className = 'note-checkline';
    line.innerHTML = '<input type="checkbox" class="note-checkbox" contenteditable="false"> <span><br></span>';
    return line;
  }

  function createNumberLine(number) {
    const line = document.createElement('div');
    line.className = 'note-numberline';
    line.dataset.noteNumber = String(number || 1);
    line.innerHTML = '<span class="note-number-marker" contenteditable="false">' + String(number || 1) + '.</span><span class="note-number-content"><br></span>';
    return line;
  }

  function createPlainLine() {
    const line = document.createElement('p');
    line.innerHTML = '<br>';
    return line;
  }

  function moveEditableContent(source, target) {
    const contentTarget = target.querySelector('.note-number-content') || target.querySelector('span') || target;
    const clone = source.cloneNode(true);
    clone.querySelectorAll('input, .note-number-marker').forEach((node) => node.remove());
    const html = clone.innerHTML.trim();
    const text = clone.textContent.replace(/\u00a0/g, ' ').trim();
    contentTarget.innerHTML = text || html ? html : '<br>';
  }

  function transformCurrentBlock(kind) {
    noteEditor.focus();
    const current = getCurrentBlock();
    const next = kind === 'checkline' ? createChecklistLine() : createNumberLine(1);

    if (!current || current === noteEditor) {
      insertBlock(next);
      if (kind === 'numberline') renumberNumberLines();
      return;
    }

    if (kind === 'checkline' && current.classList.contains('note-checkline')) {
      const plain = createPlainLine();
      moveEditableContent(current, plain);
      current.replaceWith(plain);
      placeCaret(plain);
      return;
    }

    if (kind === 'numberline' && current.classList.contains('note-numberline')) {
      const plain = createPlainLine();
      moveEditableContent(current, plain);
      current.replaceWith(plain);
      renumberNumberLines();
      placeCaret(plain);
      return;
    }

    moveEditableContent(current, next);
    current.replaceWith(next);
    if (kind === 'numberline') renumberNumberLines();
    const target = next.querySelector('.note-number-content') || next.querySelector('span') || next;
    placeCaret(target);
  }

  function applyTextFormat(blockTag) {
    if (!canEditCurrentNote()) return;
    const tag = ['p', 'h1', 'h2'].includes(blockTag) ? blockTag : 'p';
    noteEditor.focus();
    try {
      document.execCommand('formatBlock', false, '<' + tag + '>');
    } catch (_) {
      document.execCommand('formatBlock', false, tag);
    }
    ensureHeadingStructure();
    queueAutosave();
    updateToolbarActiveStates();
  }

  function insertBlock(block, afterBlock) {
    const reference = afterBlock && noteEditor.contains(afterBlock) ? afterBlock : getCurrentBlock();
    if (reference && reference !== noteEditor) reference.insertAdjacentElement('afterend', block);
    else noteEditor.appendChild(block);
    const target = block.querySelector('.note-number-content') || block.querySelector('span') || block;
    placeCaret(target);
  }

  function insertChecklistLine() {
    noteEditor.focus();
    transformCurrentBlock('checkline');
    queueAutosave();
    updateToolbarActiveStates();
  }

  function insertNumberLine() {
    noteEditor.focus();
    transformCurrentBlock('numberline');
    queueAutosave();
    updateToolbarActiveStates();
  }

  function insertDivider() {
    noteEditor.focus();
    const divider = document.createElement('hr');
    divider.className = 'note-divider';
    const nextLine = createPlainLine();
    const reference = getCurrentBlock();
    if (reference && reference !== noteEditor) reference.insertAdjacentElement('afterend', divider);
    else noteEditor.appendChild(divider);
    divider.insertAdjacentElement('afterend', nextLine);
    placeCaret(nextLine);
    queueAutosave();
  }

  function renumberNumberLines() {
    const lines = Array.from(noteEditor.querySelectorAll('.note-numberline'));
    let currentNumber = 1;
    lines.forEach((line) => {
      line.dataset.noteNumber = String(currentNumber);
      const marker = line.querySelector('.note-number-marker');
      if (marker) marker.textContent = currentNumber + '.';
      currentNumber += 1;
    });
  }

  function handleEditorEnter(event) {
    if (event.key !== 'Enter' || event.shiftKey) return;
    const current = getCurrentBlock();
    if (!current) return;
    if (current.tagName && current.tagName.toLowerCase() === 'h1') {
      event.preventDefault();
      const plain = createPlainLine();
      current.insertAdjacentElement('afterend', plain);
      placeCaret(plain);
      queueAutosave();
      return;
    }
    if (current.classList.contains('note-checkline')) {
      event.preventDefault();
      if (isBlockEmpty(current)) {
        const plain = createPlainLine();
        current.replaceWith(plain);
        placeCaret(plain);
      } else {
        insertBlock(createChecklistLine(), current);
      }
      queueAutosave();
      return;
    }
    if (current.classList.contains('note-numberline')) {
      event.preventDefault();
      if (isBlockEmpty(current)) {
        const plain = createPlainLine();
        current.replaceWith(plain);
        renumberNumberLines();
        placeCaret(plain);
      } else {
        const next = Number(current.dataset.noteNumber || 1) + 1;
        insertBlock(createNumberLine(next), current);
        renumberNumberLines();
      }
      queueAutosave();
    }
  }

  function extractNoteTitleFromPlain(plain) {
    const firstLine = String(plain || '').split('\n').map((line) => line.trim()).find((line) => line !== '');
    if (!firstLine) return 'Nota sin titulo';
    return firstLine.replace(/^((\d+\.)|(\[\s\])|(\[[xX]\]))\s*/g, '').trim() || 'Nota sin titulo';
  }

  function saveCurrentNoteFromEditor() {
    if (!canEditCurrentNote()) return;
    if (editingNoteIndex < 0 || !allNotes[editingNoteIndex]) return;
    ensureHeadingStructure();
    renumberNumberLines();
    syncChecklistVisualState();
    noteEditor.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
      const line = cb.closest('.note-checkline');
      if (cb.checked) {
        cb.setAttribute('checked', 'checked');
        line?.classList.add('is-checked');
      } else {
        cb.removeAttribute('checked');
        line?.classList.remove('is-checked');
      }
    });
    const html = normalizeHtml(noteEditor.innerHTML || '');
    const plain = htmlToPlainText(html);
    const now = Date.now();
    allNotes[editingNoteIndex] = {
      ...allNotes[editingNoteIndex],
      title: extractNoteTitleFromPlain(plain),
      html,
      plainText: plain,
      color: activeNoteColor,
      linkedClient: activeNoteLinkedClient,
      updatedAt: now,
      createdAt: allNotes[editingNoteIndex].createdAt || now,
    };
    saveNotes();
    updateInfocusAiCurrentNoteContext();
  }

  function escapeNoteHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function initials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
    const value = parts.map((p) => p[0]).join('').toUpperCase();
    return value || 'U';
  }

  function canEditCurrentNote() {
    if (editingNoteIndex < 0) return false;
    const note = allNotes[editingNoteIndex];
    if (!note) return false;
    return (note.permission || 'owner') !== 'view';
  }

  function syncNoteClientLabel() {
    if (noteClientSelectedLabel) noteClientSelectedLabel.textContent = activeNoteLinkedClient || 'Sin cliente';
  }

  function renderNoteClientOptions(search) {
    if (!noteClientOptions) return;
    const needle = String(search || '').trim().toLowerCase();
    const names = [...new Map(
      clients.map((c) => {
        const v = String(c.empresa || c.nombre || c.name || '').trim();
        return [v.toLocaleLowerCase('es'), v];
      }).filter(([k]) => k)
    ).values()].sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));
    const filtered = names.filter((n) => !needle || n.toLowerCase().includes(needle));
    if (activeNoteLinkedClient && !filtered.includes(activeNoteLinkedClient)) filtered.unshift(activeNoteLinkedClient);
    const all = [{ value: '', label: 'Sin cliente' }, ...filtered.map((n) => ({ value: n, label: n }))];
    noteClientOptions.innerHTML = all.map((item) => {
      const active = item.value === activeNoteLinkedClient;
      return '<button type="button" data-nc-value="' + escapeNoteHtml(item.value) + '" class="w-full rounded-lg px-2 py-1.5 text-left text-xs font-semibold transition-colors ' + (active ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50') + '">'
        + '<span class="inline-flex w-full items-center justify-between gap-2"><span class="truncate">' + escapeNoteHtml(item.label) + '</span>' + (active ? '<span>\u2713</span>' : '') + '</span>'
        + '</button>';
    }).join('');
    noteClientOptions.querySelectorAll('[data-nc-value]').forEach((btn) => {
      btn.addEventListener('click', () => {
        activeNoteLinkedClient = btn.dataset.ncValue || '';
        syncNoteClientLabel();
        renderNoteClientOptions(noteClientSearch ? noteClientSearch.value : '');
        closeNoteClientDropdown();
        queueAutosave();
      });
    });
  }

  function openNoteClientDropdown() {
    if (!noteClientDropdown) return;
    noteClientDropdown.classList.remove('hidden');
    if (noteClientSearch) { noteClientSearch.value = ''; renderNoteClientOptions(''); noteClientSearch.focus(); }
  }

  function closeNoteClientDropdown() {
    if (noteClientDropdown) noteClientDropdown.classList.add('hidden');
  }

  function closeNoteColorPopover() {
    if (noteColorPopover) noteColorPopover.classList.add('hidden');
  }

  function syncChecklistVisualState(root = noteEditor) {
    root.querySelectorAll('.note-checkline').forEach((line) => {
      const checkbox = line.querySelector('.note-checkbox');
      line.classList.toggle('is-checked', !!checkbox?.checked);
    });
  }

  function populateClientSelector(selectedValue) {
    activeNoteLinkedClient = String(selectedValue || '').trim();
    syncNoteClientLabel();
    renderNoteClientOptions('');
  }

  function paintNoteEditor(note, { focus = false } = {}) {
    if (!note) return;
    notesListView.classList.add('hidden');
    noteEditView.classList.remove('hidden');
    noteEditor.innerHTML = note.html || '';
    if (!note.html) noteEditor.innerHTML = getTitlePlaceholderHtml() + '<p><br></p>';
    ensureHeadingStructure();
    renumberNumberLines();
    syncChecklistVisualState();
    applyEditorColor(note.color || 'yellow');
    closeNoteColorPopover();
    populateClientSelector(note.linkedClient || '');
    applyNotePermissionState(note);
    syncHeaderBackButton();
    updateInfocusAiCurrentNoteContext();
    if (focus) noteEditor.focus();
  }

  function openNoteEditor(index) {
    editingNoteIndex = index;
    const note = allNotes[index];
    paintNoteEditor(note, { focus: true });
  }

  function applyNotePermissionState(note) {
    const permission = note?.permission || 'owner';
    const canEdit = permission !== 'view';
    const isOwner = permission === 'owner';
    noteEditor.contentEditable = canEdit ? 'true' : 'false';
    noteEditor.classList.toggle('opacity-80', !canEdit);
    noteEditor.classList.toggle('cursor-not-allowed', !canEdit);
    noteToolbarButtons.forEach((btn) => { btn.disabled = !canEdit; });
    noteColorButtons.forEach((btn) => { btn.disabled = !canEdit; });
    if (noteImageBtn) noteImageBtn.disabled = !canEdit;
    if (noteEmojiToggle) noteEmojiToggle.disabled = !canEdit;
    if (noteColorToggle) noteColorToggle.disabled = !canEdit;
    if (noteFormatTrigger) noteFormatTrigger.disabled = !canEdit;
    if (noteClientTrigger) noteClientTrigger.disabled = !canEdit;
    if (actionAiSupport) actionAiSupport.classList.toggle('hidden', !canEdit);
    if (!canEdit) toggleNoteAiSupport(false);
    if (actionDelete) actionDelete.classList.toggle('hidden', !isOwner);
    if (actionShare) actionShare.classList.toggle('hidden', !isOwner);
  }

  function createNewNote() {
    const now = Date.now();
    const defaultLinkedClient = typeof currentFilter === 'string' && currentFilter !== 'todos' ? currentFilter : '';
    const newNote = {
      id: 'qn_' + String(now) + '_' + Math.random().toString(36).slice(2, 6),
      title: 'Nota sin titulo',
      html: getTitlePlaceholderHtml() + '<p><br></p>',
      plainText: '',
      color: 'yellow',
      linkedClient: defaultLinkedClient,
      ownerKey: NOTES_USER_KEY,
      ownerName: CURRENT_USER_NAME,
      collaborators: [],
      permission: 'owner',
      isShared: false,
      createdAt: now,
      updatedAt: now,
    };

    allNotes.unshift(newNote);
    saveNotes();
    openNoteEditor(0);
  }

  function backToList() {
    saveCurrentNoteFromEditor();
    toggleNoteAiSupport(false);
    closeShareModal();
    noteEditView.classList.add('hidden');
    notesListView.classList.remove('hidden');
    editingNoteIndex = -1;
    window.__infocusAiCurrentNote = null;
    syncHeaderBackButton();
    renderNotes();
  }

  function syncHeaderBackButton() {
    if (!globalBackButton) return;
    const editingVisible = !noteEditView.classList.contains('hidden');
    globalBackButton.classList.toggle('hidden', !editingVisible);
  }

  function bindHeaderBackBehavior() {
    if (!globalBackButton) return;
    globalBackButton.onclick = function (event) {
      if (!noteEditView.classList.contains('hidden')) {
        event.preventDefault();
        backToList();
        return false;
      }
      window.location.href = '/mis-notas';
      return false;
    };
  }

  function downloadCurrentNotePdf() {
    if (editingNoteIndex < 0) return;
    const note = allNotes[editingNoteIndex];

    // Extraer titulo del H1 del HTML
    const pdfHolder = document.createElement('div');
    pdfHolder.innerHTML = note.html || '';
    const pdfTitleEl = pdfHolder.querySelector('h1,h2,h3');
    const pdfTitle = pdfTitleEl?.textContent?.replace(/\u00a0/g, ' ').trim() || String(note.title || 'Nota sin titulo').trim();
    if (pdfTitleEl) pdfTitleEl.remove();
    // Convertir checkboxes a marcadores de texto
    pdfHolder.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
      cb.replaceWith(document.createTextNode(cb.checked ? '[OK] ' : '[ ] '));
    });
    // Extraer lineas del cuerpo
    const pdfBodyLines = Array.from(pdfHolder.querySelectorAll('p,div,li,h4,h5,h6'))
      .map((el) => el.textContent?.replace(/\u00a0/g, ' ').trim())
      .filter(Boolean);
    const body = pdfBodyLines.join('\n') || '';

    const generate = () => {
      const doc = new window.jspdf.jsPDF({ unit: 'pt', format: 'a4' });
      const width = doc.internal.pageSize.getWidth();
      const height = doc.internal.pageSize.getHeight();
      const margin = 44;
      let y = margin;

      doc.setFillColor(240, 254, 151);
      doc.roundedRect(margin, y, width - (margin * 2), 72, 12, 12, 'F');
      doc.setTextColor(15, 23, 42);
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(20);
      doc.text('Mis Notas', margin + 18, y + 30);
      doc.setFontSize(12);
      doc.setFont('helvetica', 'normal');
      doc.setTextColor(71, 85, 105);
      doc.text(new Date(note.updatedAt || Date.now()).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }), margin + 18, y + 52);

      y += 98;
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(22);
      doc.setTextColor(30, 41, 59);
      const wrappedTitle = doc.splitTextToSize(pdfTitle || 'Nota sin titulo', width - (margin * 2));
      doc.text(wrappedTitle, margin, y);
      y += (wrappedTitle.length * 28) + 6;

      doc.setDrawColor(203, 213, 225);
      doc.line(margin, y, width - margin, y);
      y += 20;

      doc.setFont('helvetica', 'normal');
      doc.setFontSize(13);
      doc.setTextColor(51, 65, 85);

      const lines = doc.splitTextToSize(body || 'Sin contenido', width - (margin * 2));
      const lineHeight = 20;

      lines.forEach((line) => {
        if (y > height - margin) {
          doc.addPage();
          y = margin;
        }
        doc.text(line, margin, y);
        y += lineHeight;
      });

      const fileName = 'nota-' + new Date().toISOString().split('T')[0] + '.pdf';
      doc.save(fileName);
    };

    if (window.jspdf && window.jspdf.jsPDF) {
      generate();
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js';
    script.onload = generate;
    script.onerror = () => alert('No se pudo generar el PDF en este momento.');
    document.head.appendChild(script);
  }

  function duplicateCurrentNote() {
    if (editingNoteIndex < 0) return;
    const source = allNotes[editingNoteIndex];
    const copy = JSON.parse(JSON.stringify(source));
    const now = Date.now();
    copy.id = 'qn_' + String(now) + '_' + Math.random().toString(36).slice(2, 6);
    copy.createdAt = now;
    copy.updatedAt = now;
    copy.ownerKey = NOTES_USER_KEY;
    copy.ownerName = CURRENT_USER_NAME;
    copy.collaborators = [];
    copy.permission = 'owner';
    copy.isShared = false;
    allNotes.unshift(copy);
    saveNotes();
    backToList();
  }

  function deleteCurrentNote() {
    if (editingNoteIndex < 0) return;
    const note = allNotes[editingNoteIndex];
    if (!note) return;
    if ((note?.permission || 'owner') !== 'owner') return;
    if (!confirm('Eliminar esta nota?')) return;
    const deletedId = String(note.id || '');
    allNotes = allNotes.filter((item) => String(item.id || '') !== deletedId);
    editingNoteIndex = -1;
    window.__infocusAiCurrentNote = null;
    saveNotes();
    closeShareModal();
    noteEditView.classList.add('hidden');
    notesListView.classList.remove('hidden');
    syncHeaderBackButton();
    renderNotes();
  }

  function insertEmoji(emoji) {
    if (!canEditCurrentNote()) return;
    noteEditor.focus();
    document.execCommand('insertText', false, emoji + ' ');
    queueAutosave();
  }

  function insertImageAtCursor(dataUrl) {
    if (!canEditCurrentNote()) return;
    noteEditor.focus();
    const wrapper = document.createElement('div');
    wrapper.className = 'note-image-wrap';
    wrapper.contentEditable = 'false';
    wrapper.innerHTML = '<img src="' + dataUrl + '" class="note-inline-image" data-note-scale="100" alt="imagen nota">';
    const ref = getCurrentBlock();
    if (ref && ref !== noteEditor) ref.insertAdjacentElement('afterend', wrapper);
    else noteEditor.appendChild(wrapper);
    const next = createPlainLine();
    wrapper.insertAdjacentElement('afterend', next);
    placeCaret(next);
    queueAutosave();
  }

  function handleImageClick(targetImage) {
    const raw = prompt('Imagen: escribe "del" para eliminar o porcentaje (20-250) para escalar.', String(targetImage.dataset.noteScale || '100'));
    if (raw === null) return;
    const value = raw.trim().toLowerCase();
    if (value === 'del') {
      const wrap = targetImage.closest('.note-image-wrap');
      if (wrap) wrap.remove();
      queueAutosave();
      return;
    }
    const pct = Number(value);
    if (!Number.isFinite(pct)) return;
    const clamped = Math.max(20, Math.min(250, pct));
    targetImage.dataset.noteScale = String(clamped);
    targetImage.style.width = clamped + '%';
    targetImage.style.maxWidth = '100%';
    queueAutosave();
  }

  function isSelectionHighlighted() {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return false;
    let node = selection.getRangeAt(0).startContainer;
    if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;
    return !!node?.closest?.('[style*="background-color"], mark');
  }

  function execEditorCommand(command) {
    if (!canEditCurrentNote()) return;
    noteEditor.focus();
    if (command === 'checkline') {
      insertChecklistLine();
      return;
    }
    if (command === 'numberline') {
      insertNumberLine();
      return;
    }
    if (command === 'divider') {
      insertDivider();
      return;
    }
    if (command === 'highlight') {
      const color = isSelectionHighlighted() ? 'transparent' : '#fff59d';
      document.execCommand('backColor', false, color);
      queueAutosave();
      updateToolbarActiveStates();
      return;
    }
    if (command === 'title') {
      document.execCommand('formatBlock', false, 'h1');
      ensureHeadingStructure();
      queueAutosave();
      return;
    }
    document.execCommand(command, false, null);
    queueAutosave();
    updateToolbarActiveStates();
  }

  function isSelectionInsideNoteEditor() {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return false;
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;
    if (!container) return false;
    const node = container.nodeType === Node.TEXT_NODE ? container.parentNode : container;
    return !!(node && noteEditor.contains(node));
  }

  function updateToolbarActiveStates() {
    if (!noteToolbarButtons.length) return;
    const boldState = isSelectionInsideNoteEditor() ? document.queryCommandState('bold') : false;
    const italicState = isSelectionInsideNoteEditor() ? document.queryCommandState('italic') : false;
    const strikeState = isSelectionInsideNoteEditor() ? document.queryCommandState('strikeThrough') : false;
    const highlightState = isSelectionInsideNoteEditor() ? isSelectionHighlighted() : false;
    const currentBlock = isSelectionInsideNoteEditor() ? getCurrentBlock() : null;
    const inChecklist = !!(currentBlock && currentBlock.classList?.contains('note-checkline'));
    const inNumberList = !!(currentBlock && currentBlock.classList?.contains('note-numberline'));
    const tagName = currentBlock?.tagName?.toLowerCase();
    const activeFormat = ['h1', 'h2'].includes(tagName) ? tagName : 'p';
    const formatLabels = { p: 'Texto normal', h1: 'Titulo', h2: 'Subtitulo' };
    if (noteFormatLabel) noteFormatLabel.textContent = formatLabels[activeFormat] || formatLabels.p;
    noteFormatOptions.forEach((option) => {
      option.classList.toggle('is-selected', option.dataset.noteFormat === activeFormat);
    });

    noteToolbarButtons.forEach((button) => {
      const cmd = button.dataset.noteCmd || '';
      let active = false;
      if (cmd === 'bold') active = !!boldState;
      if (cmd === 'italic') active = !!italicState;
      if (cmd === 'strikeThrough') active = !!strikeState;
      if (cmd === 'highlight') active = !!highlightState;
      if (cmd === 'checkline') active = inChecklist;
      if (cmd === 'numberline') active = inNumberList;
      button.classList.toggle('note-tool-btn-active', active);
    });
  }

  function updateClientTabsNav() {
    if (!clientTabsScroll || !clientTabsPrev || !clientTabsNext) return;
    const maxScroll = Math.max(0, clientTabsScroll.scrollWidth - clientTabsScroll.clientWidth);
    const current = clientTabsScroll.scrollLeft;
    const hasOverflow = maxScroll > 4;
    const canScrollPrev = hasOverflow && current > 4;
    const canScrollNext = hasOverflow && current < maxScroll - 4;
    clientTabsPrev.classList.toggle('is-hidden', !canScrollPrev);
    clientTabsNext.classList.toggle('is-hidden', !canScrollNext);
    clientTabsScroll.closest('.client-tabs-shell')?.classList.toggle('has-prev', canScrollPrev);
    clientTabsScroll.closest('.client-tabs-shell')?.classList.toggle('has-next', canScrollNext);
  }

  function scrollClientTabs(direction) {
    if (!clientTabsScroll) return;
    const amount = Math.max(180, Math.floor(clientTabsScroll.clientWidth * 0.55));
    clientTabsScroll.scrollBy({ left: direction * amount, behavior: 'smooth' });
  }

  function renderClientTabs(clientsList) {
    const todosBtn = clientTabs.querySelector('[data-client="todos"]');
    todosBtn.addEventListener('click', () => selectClient('todos', 'Todos'));
    const sinClienteBtn = clientTabs.querySelector('[data-client="all"]');
    sinClienteBtn.addEventListener('click', () => selectClient(null, 'Sin cliente'));
    clientsList.forEach((client) => {
      const btn = document.createElement('button');
      btn.className = 'client-tab-btn shrink-0 px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap bg-slate-100 text-slate-600 hover:bg-slate-200';
      btn.dataset.client = client.id;
      btn.textContent = client.empresa;
      btn.addEventListener('click', () => selectClient(client.id, client.empresa));
      clientTabs.appendChild(btn);
    });
    if (clientTabsScroll) clientTabsScroll.scrollLeft = 0;
    requestAnimationFrame(() => requestAnimationFrame(updateClientTabsNav));
  }

  function selectClient(clientId, clientName) {
    document.querySelectorAll('.client-tab-btn').forEach((btn) => {
      btn.classList.remove('active', 'bg-slate-900', 'text-white');
      btn.classList.add('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
    });
    const activeBtn = clientId === 'todos'
      ? document.querySelector('[data-client="todos"]')
      : clientId === null
        ? document.querySelector('[data-client="all"]')
        : document.querySelector('[data-client="' + clientId + '"]');
    if (activeBtn) {
      activeBtn.classList.remove('bg-slate-100', 'text-slate-600', 'hover:bg-slate-200');
      activeBtn.classList.add('active', 'bg-slate-900', 'text-white');
    }
    currentFilter = clientId === 'todos' ? 'todos' : clientId === null ? null : clientName;
    renderNotes();
  }

  function buildNotePreviewHtml(noteHtml, query = '') {
    const holder = document.createElement('div');
    holder.innerHTML = String(noteHtml || '');
    holder.querySelector('h1,h2,h3')?.remove();
    holder.querySelectorAll('[contenteditable]').forEach((node) => node.removeAttribute('contenteditable'));
    holder.querySelectorAll('.note-checkline').forEach((line) => {
      const checkbox = line.querySelector('input[type="checkbox"]');
      const checked = !!checkbox?.checked;
      line.classList.toggle('is-checked', checked);
      const marker = document.createElement('span');
      marker.className = 'note-preview-checkbox' + (checked ? ' is-checked' : '');
      marker.setAttribute('aria-hidden', 'true');
      checkbox?.replaceWith(marker);
    });

    const meaningful = Array.from(holder.children)
      .filter((node) => {
        if (node.matches?.('hr, .note-image-wrap')) return true;
        if (node.querySelector?.('img')) return true;
        return (node.textContent || '').replace(/\u00a0/g, ' ').trim() !== '';
      })
      .slice(0, 5);

    if (!meaningful.length) return '<span class="text-slate-400">Sin contenido</span>';

    const preview = document.createElement('div');
    meaningful.forEach((node) => preview.appendChild(node.cloneNode(true)));
    highlightSearchInElement(preview, query);
    return preview.innerHTML;
  }

  function renderNotes() {
    notesContainer.innerHTML = '';
    const query = String(currentSearchQuery || '').trim();
    const clientFiltered = (currentFilter === 'todos'
      ? allNotes
      : currentFilter === null
        ? allNotes.filter((note) => !resolveNoteClientName(note))
        : allNotes.filter((note) => normalizeSearchText(resolveNoteClientName(note)) === normalizeSearchText(currentFilter))
    );
    const filtered = clientFiltered
      .filter((note) => noteMatchesSearch(note, query))
      .slice()
      .sort((a, b) => Number(b.createdAt || b.updatedAt || 0) - Number(a.createdAt || a.updatedAt || 0));

    if (notesSearchClear) notesSearchClear.classList.toggle('hidden', !query);
    if (notesSearchCount) {
      const resultCount = query ? filtered.length : 0;
      notesSearchCount.textContent = `${resultCount} ${resultCount === 1 ? 'resultado' : 'resultados'}`;
    }

    if (!filtered.length) {
      notesContainer.innerHTML = '<div class="col-span-full text-center py-16 text-slate-400 text-lg">' + (query ? 'No hay notas que coincidan con la busqueda' : 'No hay notas para esta seccion') + '</div>';
      return;
    }

    filtered.forEach((note) => {
      const actualIndex = allNotes.findIndex((item) => String(item.id) === String(note.id));
      const tone = palette[note.color] || palette.yellow;

      const htmlHolder = document.createElement('div');
      htmlHolder.innerHTML = String(note.html || '');
      const titleEl = htmlHolder.querySelector('h1,h2,h3');
      const title = titleEl?.textContent?.replace(/\u00a0/g, ' ').trim() || String(note.title || 'Nota sin titulo');
      const previewHtml = buildNotePreviewHtml(note.html || '', query);
      const titleHtml = highlightSearchHtml(escapeNoteHtml(title), query);
      const linkedClientName = resolveNoteClientName(note);
      const linkedClientPillHtml = currentFilter === 'todos'
        ? '<button type="button" class="note-client-pill" data-note-client-pill="' + escapeNoteHtml(linkedClientName || '') + '" title="' + escapeNoteHtml(linkedClientName || 'Sin cliente') + '"><span class="note-client-pill-label">' + highlightSearchHtml(escapeNoteHtml(linkedClientName || 'Sin cliente'), query) + '</span></button>'
        : '';

      const card = document.createElement('div');
      card.setAttribute('role', 'button');
      card.setAttribute('tabindex', '0');
      card.className = 'note-card-item rounded-xl p-5 border text-left transition-all hover:shadow-lg flex flex-col';
      card.style.backgroundColor = tone.cardBg;
      card.style.borderColor = tone.cardBorder;
      const ownerName = String(note.ownerName || 'Usuario');
      const collabs = Array.isArray(note.collaborators) ? note.collaborators : [];
      const people = [{ userName: ownerName }, ...collabs];
      const isOwner = String(note.ownerKey || '') === String(NOTES_USER_KEY);
      const showSharedMeta = (!isOwner) || collabs.length > 0;
      const avatars = people.slice(0, 4).map((person, idx) => (
        '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white text-[10px] font-bold bg-slate-800 text-white ' + (idx > 0 ? '-ml-2' : '') + '">' + initials(person.userName) + '</span>'
      )).join('');
      const extra = people.length > 4 ? '<span class="text-xs text-slate-500 ml-1">+' + (people.length - 4) + '</span>' : '';
      const sharedMetaHtml = showSharedMeta
        ? ('<div class="mt-3 text-[11px] text-slate-600">Propietario: <span class="font-semibold">' + escapeNoteHtml(ownerName) + '</span></div>'
          + '<div class="mt-2 flex items-center">' + avatars + extra + '</div>')
        : '';
      card.innerHTML = '<div class="font-extrabold text-lg text-slate-900 mb-2 line-clamp-2">' + titleHtml + '</div>'
        + '<div class="note-card-preview text-sm text-slate-700 flex-1">' + previewHtml + '</div>'
        + sharedMetaHtml
        + '<div class="note-card-footer" style="border-color:' + tone.cardBorder + '40">'
        + '<span>' + new Date(note.updatedAt || Date.now()).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' }) + '</span>'
        + linkedClientPillHtml
        + '</div>';
      card.addEventListener('click', () => openNoteEditor(actualIndex));
      card.querySelectorAll('.note-card-preview a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          window.open(link.href, '_blank', 'noopener,noreferrer');
        });
      });
      card.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        openNoteEditor(actualIndex);
      });
      card.querySelector('[data-note-client-pill]')?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const clientName = String(event.currentTarget?.getAttribute('data-note-client-pill') || '').trim();
        if (clientName) {
          const client = clients.find((item) => {
            const name = String(item.empresa || item.nombre || item.name || '').trim();
            return normalizeSearchText(name) === normalizeSearchText(clientName);
          });
          selectClient(client?.id || clientName, clientName);
        } else {
          selectClient(null, 'Sin cliente');
        }
      });
      notesContainer.appendChild(card);
    });
  }

  function renderShareUserOptions() {
    if (!shareUserSelect) return;
    if (!collaboratorsCatalog.length) {
      shareUserSelect.innerHTML = '<option value="">No hay usuarios disponibles</option>';
      return;
    }
    shareUserSelect.innerHTML = collaboratorsCatalog.map((u) => (
      '<option value="' + escapeNoteHtml(u.userKey) + '">' + escapeNoteHtml(u.userName) + '</option>'
    )).join('');
  }

  function openShareModal() {
    if (editingNoteIndex < 0) return;
    const note = allNotes[editingNoteIndex];
    if (!note || (note.permission || 'owner') !== 'owner') return;
    renderShareModalList();
    noteShareModal?.classList.remove('hidden');
  }

  function closeShareModal() {
    noteShareModal?.classList.add('hidden');
  }

  function renderShareModalList() {
    if (editingNoteIndex < 0 || !shareCollaboratorsList) return;
    const note = allNotes[editingNoteIndex];
    const collabs = Array.isArray(note.collaborators) ? note.collaborators : [];
    if (!collabs.length) {
      shareCollaboratorsList.innerHTML = '<div class="text-sm text-slate-400">Aun no has compartido esta nota.</div>';
      return;
    }
    shareCollaboratorsList.innerHTML = collabs.map((c, idx) => {
      const modeLabel = c.mode === 'edit' ? 'Edicion' : 'Vista';
      return '<div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2">'
        + '<div class="flex items-center gap-2"><span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 text-white text-[10px] font-bold">' + initials(c.userName) + '</span>'
        + '<div><div class="text-sm font-semibold text-slate-800">' + escapeNoteHtml(c.userName || 'Usuario') + '</div><div class="text-xs text-slate-500">' + modeLabel + '</div></div></div>'
        + '<div class="flex items-center gap-2">'
        + '<button type="button" data-share-toggle="' + idx + '" class="text-xs px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-50">Cambiar</button>'
        + '<button type="button" data-share-remove="' + idx + '" class="text-xs px-2 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50">Quitar</button>'
        + '</div></div>';
    }).join('');

    shareCollaboratorsList.querySelectorAll('[data-share-remove]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const i = Number(btn.getAttribute('data-share-remove'));
        const note = allNotes[editingNoteIndex];
        note.collaborators.splice(i, 1);
        saveNotes();
        renderShareModalList();
        renderNotes();
      });
    });

    shareCollaboratorsList.querySelectorAll('[data-share-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const i = Number(btn.getAttribute('data-share-toggle'));
        const note = allNotes[editingNoteIndex];
        note.collaborators[i].mode = note.collaborators[i].mode === 'edit' ? 'view' : 'edit';
        saveNotes();
        renderShareModalList();
      });
    });
  }

  fetch('/api/clientes', { headers: { Accept: 'application/json' } })
    .then((res) => res.json())
    .then((json) => {
      clients = Array.isArray(json && json.data) ? json.data : [];
      renderClientTabs(clients);
    })
    .catch(() => {});

  fetch('/api/mis-notas/colaboradores', { headers: { Accept: 'application/json' } })
    .then((res) => res.json())
    .then((json) => {
      collaboratorsCatalog = Array.isArray(json?.data) ? json.data : [];
      renderShareUserOptions();
    })
    .catch(() => {
      collaboratorsCatalog = [];
      renderShareUserOptions();
    });

  noteToolbarButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      execEditorCommand(btn.dataset.noteCmd || '');
      updateToolbarActiveStates();
    });
  });

  clientTabsPrev?.addEventListener('click', () => scrollClientTabs(-1));
  clientTabsNext?.addEventListener('click', () => scrollClientTabs(1));
  clientTabsScroll?.addEventListener('scroll', updateClientTabsNav, { passive: true });
  window.addEventListener('resize', updateClientTabsNav);
  notesSearchInput?.addEventListener('input', (event) => {
    currentSearchQuery = String(event.target.value || '');
    renderNotes();
  });
  notesSearchClear?.addEventListener('click', () => {
    currentSearchQuery = '';
    if (notesSearchInput) {
      notesSearchInput.value = '';
      notesSearchInput.focus();
    }
    renderNotes();
  });

  noteFormatTrigger?.addEventListener('click', (event) => {
    event.stopPropagation();
    noteEmojiPopover.classList.add('hidden');
    closeNoteColorPopover();
    const willOpen = noteFormatMenu?.classList.contains('hidden');
    noteFormatMenu?.classList.toggle('hidden', !willOpen);
    noteFormatTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });

  noteFormatOptions.forEach((option) => {
    option.addEventListener('click', () => {
      applyTextFormat(option.dataset.noteFormat || 'p');
      noteFormatMenu?.classList.add('hidden');
      noteFormatTrigger?.setAttribute('aria-expanded', 'false');
    });
  });

  noteColorButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      applyEditorColor(btn.dataset.noteColor || 'yellow');
      closeNoteColorPopover();
      queueAutosave();
    });
  });

  noteEmojiToggle.addEventListener('click', (event) => {
    event.stopPropagation();
    closeNoteColorPopover();
    noteEmojiPopover.classList.toggle('hidden');
  });

  document.querySelectorAll('.note-emoji-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      insertEmoji(btn.dataset.noteEmoji || '');
      noteEmojiPopover.classList.add('hidden');
    });
  });

  noteImageBtn.addEventListener('click', () => noteImageInput.click());
  noteColorToggle?.addEventListener('click', (event) => {
    event.stopPropagation();
    noteEmojiPopover.classList.add('hidden');
    noteColorPopover?.classList.toggle('hidden');
  });
  noteImageInput.addEventListener('change', (event) => {
    const file = event.target.files && event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => insertImageAtCursor(String(reader.result || ''));
    reader.readAsDataURL(file);
    noteImageInput.value = '';
  });

  noteEditor.addEventListener('click', (event) => {
    const link = event.target?.closest?.('a[href]');
    if (link && noteEditor.contains(link)) {
      event.preventDefault();
      window.open(link.href, '_blank', 'noopener,noreferrer');
      return;
    }
    const target = event.target;
    if (target instanceof HTMLImageElement && target.classList.contains('note-inline-image')) {
      event.preventDefault();
      handleImageClick(target);
    }
  });

  noteEditor.addEventListener('blur', () => {
    if (!canEditCurrentNote()) return;
    const normalized = normalizeHtml(noteEditor.innerHTML || '');
    if (normalized && normalized !== (noteEditor.innerHTML || '').trim()) {
      noteEditor.innerHTML = normalized;
    }
    saveCurrentNoteFromEditor();
  });

  noteEditor.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement) || !target.classList.contains('note-checkbox')) return;
    const line = target.closest('.note-checkline');
    line?.classList.toggle('is-checked', target.checked);
    queueAutosave();
    updateInfocusAiCurrentNoteContext();
  });

  noteEditor.addEventListener('keydown', handleEditorEnter);
  noteEditor.addEventListener('paste', handleNotePaste);
  noteEditor.addEventListener('keyup', updateToolbarActiveStates);
  noteEditor.addEventListener('mouseup', updateToolbarActiveStates);
  noteEditor.addEventListener('focus', updateToolbarActiveStates);
  noteEditor.addEventListener('input', () => {
    updateTitlePlaceholder();
    enforceSingleLineTitle();
    queueAutosave();
    updateToolbarActiveStates();
    updateInfocusAiCurrentNoteContext();
  });

  noteClientTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    noteClientDropdown?.classList.contains('hidden') ? openNoteClientDropdown() : closeNoteClientDropdown();
  });
  noteClientSearch?.addEventListener('input', (e) => renderNoteClientOptions(e.target.value));
  document.addEventListener('click', (e) => {
    if (!noteClientTrigger?.contains(e.target) && !noteClientDropdown?.contains(e.target)) closeNoteClientDropdown();
  });

  actionPdf.addEventListener('click', downloadCurrentNotePdf);
  actionDuplicate.addEventListener('click', duplicateCurrentNote);
  actionDelete.addEventListener('click', deleteCurrentNote);
  actionShare?.addEventListener('click', openShareModal);
  actionAiSupport?.addEventListener('click', (event) => {
    event.stopPropagation();
    toggleNoteAiSupport();
  });
  noteAiSupportClose?.addEventListener('click', () => toggleNoteAiSupport(false));
  noteAiSupportSend?.addEventListener('click', () => sendNoteAiSupport());
  noteAiSupportInput?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    sendNoteAiSupport();
  });
  document.querySelectorAll('[data-note-ai-prompt]').forEach((button) => {
    button.addEventListener('click', () => {
      const prompt = noteAiQuickPrompt(button.getAttribute('data-note-ai-prompt') || '');
      if (prompt) sendNoteAiSupport(prompt);
    });
  });
  document.addEventListener('pointerdown', (event) => {
    if (!noteAiSupportPanel || noteAiSupportPanel.classList.contains('hidden')) return;
    if (event.target?.closest?.('#note-ai-support-panel, #action-ai-support')) return;
    toggleNoteAiSupport(false);
  });
  window.addEventListener('resize', positionNoteAiSupportPanel);
  window.addEventListener('scroll', positionNoteAiSupportPanel, true);
  addNoteBtn?.addEventListener('click', createNewNote);

  shareAddBtn?.addEventListener('click', () => {
    if (editingNoteIndex < 0) return;
    const note = allNotes[editingNoteIndex];
    if (!note || (note.permission || 'owner') !== 'owner') return;
    const userKey = String(shareUserSelect?.value || '').trim();
    if (!userKey) return;
    const mode = String(shareModeSelect?.value || 'view') === 'edit' ? 'edit' : 'view';
    const user = collaboratorsCatalog.find((u) => String(u.userKey) === userKey);
    if (!user) return;
    note.collaborators = Array.isArray(note.collaborators) ? note.collaborators : [];
    const existing = note.collaborators.find((c) => String(c.userKey) === userKey);
    if (existing) {
      existing.mode = mode;
      existing.userName = user.userName;
    } else {
      note.collaborators.push({ userKey, userName: user.userName, mode });
    }
    saveNotes();
    renderShareModalList();
    renderNotes();
  });

  noteShareClose?.addEventListener('click', closeShareModal);
  noteShareModal?.addEventListener('click', (event) => {
    if (event.target === noteShareModal) closeShareModal();
  });

  document.addEventListener('pointerdown', (event) => {
    if (!noteEmojiPopover.classList.contains('hidden')) {
      if (!noteEmojiPopover.contains(event.target) && !noteEmojiToggle.contains(event.target)) {
        noteEmojiPopover.classList.add('hidden');
      }
    }
    if (noteColorPopover && !noteColorPopover.classList.contains('hidden')) {
      if (!noteColorPopover.contains(event.target) && !noteColorToggle?.contains(event.target)) {
        closeNoteColorPopover();
      }
    }
    if (noteFormatMenu && !noteFormatMenu.classList.contains('hidden')) {
      if (!noteFormatWrap?.contains(event.target)) {
        noteFormatMenu.classList.add('hidden');
        noteFormatTrigger?.setAttribute('aria-expanded', 'false');
      }
    }
  });

  document.addEventListener('selectionchange', () => {
    if (noteEditView.classList.contains('hidden')) return;
    updateToolbarActiveStates();
  });

  window.addEventListener('beforeunload', () => {
    saveCurrentNoteFromEditor();
    syncNotesToServer();
  });

  function isNoteEditorFocused() {
    const active = document.activeElement;
    return !!(active && noteEditor && noteEditor.contains(active));
  }

  function refreshNotesFromSharedStore() {
    const activeId = editingNoteIndex >= 0 ? String(allNotes[editingNoteIndex]?.id || '') : '';
    const keepTyping = isNoteEditorFocused();
    allNotes = loadNotes();

    if (editingNoteIndex >= 0) {
      const nextIndex = allNotes.findIndex((note) => String(note.id || '') === activeId);
      if (nextIndex >= 0) {
        editingNoteIndex = nextIndex;
        if (!keepTyping) {
          paintNoteEditor(allNotes[nextIndex], { focus: false });
        } else {
          updateInfocusAiCurrentNoteContext();
        }
      } else if (!keepTyping) {
        noteEditView.classList.add('hidden');
        notesListView.classList.remove('hidden');
        editingNoteIndex = -1;
        window.__infocusAiCurrentNote = null;
        syncHeaderBackButton();
        renderNotes();
      }
      return;
    }

    renderNotes();
  }

  window.addEventListener('infocus-notes-updated', (event) => {
    if (event.detail?.key && event.detail.key !== NOTES_KEY) return;
    if (String(event.detail?.source || '').startsWith('mis-notas')) return;
    refreshNotesFromSharedStore();
  });

  window.addEventListener('storage', (event) => {
    if (event.key !== NOTES_KEY) return;
    refreshNotesFromSharedStore();
  });

  bindHeaderBackBehavior();
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && noteShareModal && !noteShareModal.classList.contains('hidden')) {
      closeShareModal();
      return;
    }
    if (event.key === 'Escape' && !noteEditView.classList.contains('hidden')) {
      backToList();
    }
  });

  syncHeaderBackButton();
  updateToolbarActiveStates();
  (async () => {
    await hydrateNotes();
    selectClient('todos', 'Todos');
  })();
});
</script>

<style>
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

.notes-search-shell {
  display: flex;
  align-items: center;
  gap: .75rem;
  max-width: 42rem;
}
.notes-search-box {
  position: relative;
  flex: 1 1 auto;
  min-width: 0;
}
.notes-search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  width: 1.15rem;
  height: 1.15rem;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}
.notes-search-input {
  width: 100%;
  height: 3.1rem;
  border-radius: 1.15rem;
  border: 1px solid #dbe5f1;
  background: #fff;
  padding: 0 3rem 0 2.95rem;
  color: #1e293b;
  font-size: .98rem;
  font-weight: 700;
  outline: none;
  box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
  transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
}
.notes-search-input::placeholder {
  color: #94a3b8;
  font-weight: 700;
}
.notes-search-input:focus {
  border-color: #d9ff66;
  box-shadow: 0 0 0 4px rgba(236, 254, 136, .48), 0 12px 26px rgba(15, 23, 42, .06);
}
.notes-search-clear {
  position: absolute;
  right: .65rem;
  top: 50%;
  width: 2rem;
  height: 2rem;
  transform: translateY(-50%);
  border-radius: 999px;
  border: 1px solid #dbe5f1;
  color: #64748b;
  background: #f8fafc;
  display: inline-grid;
  place-content: center;
}
.notes-search-clear:hover {
  color: #0f172a;
  background: #eef2f7;
}
.notes-search-clear.hidden,
.notes-search-count.hidden {
  display: none;
}
.notes-search-count {
  flex: 0 0 auto;
  border-radius: 999px;
  border: 1px solid #dbe5f1;
  background: #f8fafc;
  padding: .55rem .8rem;
  color: #64748b;
  font-size: .78rem;
  font-weight: 900;
}
.note-search-match {
  background: linear-gradient(180deg, transparent 58%, rgba(217, 255, 102, .85) 58%);
  color: inherit;
  padding: 0 .04em;
  text-decoration: underline;
  text-decoration-color: #84cc16;
  text-decoration-thickness: 2px;
  text-underline-offset: .16em;
}
.note-cards-grid {
  align-items: start;
}
.note-card-item {
  align-self: start;
  height: auto;
}
.note-card-footer {
  display: grid;
  grid-template-columns: max-content minmax(0, 1fr);
  align-items: center;
  gap: .6rem;
  margin-top: 1rem;
  padding-top: .75rem;
  border-top: 1px solid;
  color: #64748b;
  font-size: .78rem;
  font-weight: 700;
}
.note-client-pill {
  display: inline-flex;
  align-items: center;
  justify-self: end;
  width: min(100%, 10rem);
  min-width: 0;
  height: 1.55rem;
  border-radius: 999px;
  border: 1px solid rgba(71, 85, 105, .18);
  background: rgba(255, 255, 255, .68);
  padding: 0 .6rem;
  color: #334155;
  font-size: .68rem;
  font-weight: 900;
  line-height: 1;
  overflow: hidden;
  cursor: pointer;
  transition: border-color .15s ease, background-color .15s ease, color .15s ease;
}
.note-client-pill-label {
  display: block;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.note-client-pill:hover,
.note-client-pill:focus-visible {
  border-color: rgba(132, 204, 22, .5);
  background: rgba(236, 254, 136, .82);
  color: #0f172a;
  outline: none;
}

.client-tabs-shell {
  position: relative;
  padding: 0;
}
.client-tabs-shell::before,
.client-tabs-shell::after {
  content: "";
  position: absolute;
  top: 0;
  bottom: .5rem;
  width: 2.2rem;
  pointer-events: none;
  z-index: 5;
  opacity: 0;
  transition: opacity .15s ease;
}
.client-tabs-shell::before {
  left: 0;
  background: linear-gradient(90deg, #f8fafc 0%, rgba(248,250,252,0) 100%);
}
.client-tabs-shell::after {
  right: 0;
  background: linear-gradient(270deg, #f8fafc 0%, rgba(248,250,252,0) 100%);
}
.client-tabs-shell.has-prev::before,
.client-tabs-shell.has-next::after {
  opacity: 1;
}
.client-tabs-nav {
  position: absolute;
  top: .25rem;
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 1px solid #dbe2ea;
  background: #fff;
  color: #0f172a;
  display: inline-grid;
  place-content: center;
  z-index: 10;
  box-shadow: 0 8px 20px rgba(15,23,42,.08);
  transition: opacity .15s ease, transform .15s ease, background-color .15s ease;
}
.client-tabs-nav:hover {
  background: #f8fafc;
  transform: translateY(-1px);
}
.client-tabs-nav-left { left: .35rem; }
.client-tabs-nav-right { right: .35rem; }
.client-tabs-nav.is-hidden {
  opacity: 0;
  pointer-events: none;
  transform: scale(.92);
}

.note-card-preview {
  max-height: 9.6rem;
  overflow: hidden;
}
.note-card-preview p,
.note-card-preview div,
.note-card-preview h2,
.note-card-preview h3 {
  margin: 0 0 .32rem;
  line-height: 1.35;
}
.note-card-preview h2,
.note-card-preview h3 {
  font-weight: 800;
  color: #1f2d49;
}
.note-card-preview .note-checkline,
.note-card-preview .note-numberline {
  display: flex;
  align-items: flex-start;
  gap: .42rem;
}
.note-card-preview .note-number-marker {
  min-width: 1.25rem;
  color: #64748b;
  font-weight: 800;
  text-align: right;
}
.note-card-preview .note-number-content,
.note-card-preview .note-checkline span:not(.note-preview-checkbox) {
  min-width: 0;
}
.note-card-preview .note-checkline.is-checked span:not(.note-preview-checkbox) {
  color: #64748b;
  text-decoration: line-through;
  text-decoration-thickness: 2px;
}
.note-card-preview .note-preview-checkbox {
  width: 1rem;
  height: 1rem;
  margin-top: .12rem;
  border: 2px solid #64748b;
  border-radius: .28rem;
  background: rgba(255,255,255,.72);
  display: inline-grid;
  place-content: center;
  flex: 0 0 auto;
}
.note-card-preview .note-preview-checkbox.is-checked {
  border-color: #4f46e5;
  background: #4f46e5;
}
.note-card-preview .note-preview-checkbox.is-checked::after {
  content: "";
  width: .3rem;
  height: .52rem;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: translateY(-1px) rotate(45deg);
}
.note-card-preview .note-divider {
  border: 0;
  border-top: 1px solid rgba(100,116,139,.25);
  margin: .55rem 0;
}
.note-card-preview .note-image-wrap {
  margin: .35rem 0 .5rem;
}
.note-card-preview .note-inline-image {
  width: 100% !important;
  max-height: 5.2rem;
  object-fit: cover;
  border-radius: .65rem;
  border: 1px solid rgba(15,23,42,.12);
  display: block;
}

#note-edit-view .note-tool-btn {
  width: 2.05rem;
  height: 2.05rem;
  border-radius: 0.75rem;
  border: 1px solid transparent;
  color: #111728;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}
#note-edit-view .note-tool-btn:hover { background: rgba(17,23,40,0.10); color: #111728; }
#note-edit-view .note-tool-btn-active { background: rgba(17,23,40,0.14); color: #111728; border-color: rgba(17,23,40,0.25); }
#note-edit-view .note-tool-text-btn { font-size: 1.1rem; line-height: 1; }
#note-edit-view .note-tool-btn iconify-icon {
  color: currentColor;
  display: block;
}
#note-edit-view .note-highlight-btn iconify-icon {
  color: #111728;
}
#note-edit-view .note-highlight-btn.note-tool-btn-active {
  background: #fff59d;
  border-color: #facc15;
}
#note-edit-view .note-highlight-btn.note-tool-btn-active iconify-icon {
  color: #713f12;
}
#note-edit-view .note-format-menu-wrap {
  position: relative;
  display: inline-flex;
  flex: 0 0 auto;
}
#note-edit-view .note-format-trigger {
  height: 2.05rem;
  width: 8.3rem;
  border-radius: .75rem;
  border: 1px solid #d4dae3;
  background: #fff;
  color: #111728;
  font-size: .82rem;
  font-weight: 700;
  padding: 0 .55rem 0 .7rem;
  outline: none;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: .45rem;
  box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
#note-edit-view .note-format-trigger:hover {
  background: #f8fafc;
}
#note-edit-view .note-format-trigger:focus {
  border-color: rgba(17,23,40,.35);
  box-shadow: 0 0 0 3px rgba(17,23,40,.08);
}
#note-edit-view .note-format-trigger:disabled {
  opacity: .55;
  cursor: not-allowed;
}
#note-edit-view .note-format-menu {
  position: absolute;
  left: 0;
  top: calc(100% + .35rem);
  width: 8.8rem;
  z-index: 40;
  display: flex;
  flex-direction: column;
  gap: .1rem;
  border-radius: .85rem;
  border: 1px solid rgba(255,255,255,.14);
  background: rgba(31, 31, 34, .95);
  box-shadow: 0 16px 34px rgba(15,23,42,.24);
  padding: .25rem;
  overflow: hidden;
}
#note-edit-view #note-format-menu.hidden {
  display: none !important;
}
#note-edit-view .note-format-option {
  width: 100%;
  height: 1.75rem;
  border-radius: .5rem;
  display: flex;
  align-items: center;
  gap: .35rem;
  padding: 0 .55rem;
  color: #f8fafc;
  font-size: .8rem;
  font-weight: 750;
  text-align: left;
}
#note-edit-view .note-format-option:hover {
  background: rgba(255,255,255,.12);
}
#note-edit-view .note-format-option.is-selected {
  background: #ff5f72;
  color: #fff;
}
#note-edit-view .note-format-option.is-selected::before {
  content: "✓";
  font-weight: 900;
}
#note-edit-view .note-palette-toggle {
  border-radius: 999px;
  border-color: #d4dae3;
  background: #fff;
}
#note-edit-view .note-palette-swatch {
  width: 1.08rem;
  height: 1.08rem;
  border-radius: 999px;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px rgba(96,114,139,.25);
  background: conic-gradient(from 210deg, #f4dc38, #34c98d, #31afe9, #f46787, #9b7df0, #f4dc38);
  display: inline-block;
}

#note-edit-view .note-color-dot {
  width: 1.6rem;
  height: 1.6rem;
  border-radius: 999px;
  border: 2px solid transparent;
  transition: transform .15s ease, border-color .15s ease;
}
#note-edit-view .note-color-dot:hover { transform: scale(1.05); }
#note-edit-view .note-color-dot.is-active { border-color: #111728; box-shadow: 0 0 0 2px rgba(17,23,40,0.3); }

#note-editor,
#note-editor * {
  font-family: inherit !important;
}
#note-editor span,
#note-editor font {
  font-size: inherit !important;
  line-height: inherit;
}
#note-editor h1, #note-editor h2, #note-editor h3 {
  margin: 0 0 .4rem;
  line-height: 1.2;
  font-weight: 800;
  text-align: left;
}
#note-editor h1 {
  font-size: 1.75em;
}
#note-editor h2 {
  font-size: 1.28em;
  font-weight: 750;
  color: #31415f;
}
#note-editor h3 {
  font-size: 1.12em;
}
#note-editor div, #note-editor p, #note-editor li {
  margin: 0 0 .3rem;
  font-size: .96em;
  font-weight: 400;
  line-height: 1.55;
}
#note-editor a,
.note-card-preview a {
  color: #2563eb;
  font-weight: 800;
  text-decoration: underline;
  text-decoration-thickness: 2px;
  text-underline-offset: 3px;
  word-break: break-word;
}
#note-editor a:hover,
.note-card-preview a:hover {
  color: #1d4ed8;
}
#note-canvas.note-ai-working {
  isolation: isolate;
}
#note-canvas.note-ai-working::before {
  content: "";
  position: absolute;
  inset: -8px;
  border-radius: 1.25rem;
  pointer-events: none;
  z-index: 0;
  background: linear-gradient(120deg, rgba(129,140,248,.55), rgba(217,70,239,.48), rgba(240,254,151,.62), rgba(56,189,248,.42), rgba(129,140,248,.55));
  background-size: 260% 260%;
  filter: blur(18px);
  opacity: .85;
  animation: noteAiGlow 2.4s ease-in-out infinite;
}
#note-canvas.note-ai-working::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 1rem;
  pointer-events: none;
  z-index: 1;
  box-shadow: inset 0 0 0 2px rgba(255,255,255,.45), 0 0 0 1px rgba(168,85,247,.22);
}
#note-canvas.note-ai-working #note-editor {
  position: relative;
  z-index: 2;
}
#note-editor .note-ai-line-enter {
  animation: noteAiLineEnter .46s cubic-bezier(.2,.8,.2,1) both;
}
@keyframes noteAiGlow {
  0%, 100% { background-position: 0% 50%; opacity: .62; }
  50% { background-position: 100% 50%; opacity: .95; }
}
@keyframes noteAiLineEnter {
  from {
    opacity: 0;
    transform: translateY(9px);
    filter: blur(5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
    filter: blur(0);
  }
}
#note-editor .note-title-placeholder.is-empty::before {
  content: attr(data-placeholder);
  color: #a8b3c4;
  pointer-events: none;
}
#note-editor .note-divider {
  border: 0;
  border-top: 2px solid rgba(96,114,139,.28);
  margin: .75rem 0;
}
#note-editor .note-checkline { display: flex; align-items: center; gap: .5rem; }
#note-editor .note-checkline span, #note-editor .note-numberline .note-number-content { min-width: 1ch; outline: none; }
#note-editor .note-checkline.is-checked span {
  color: #64748b;
  text-decoration: line-through;
  text-decoration-thickness: 2px;
  text-decoration-color: rgba(71, 85, 105, .75);
}
#note-editor .note-checkbox {
  appearance: none;
  -webkit-appearance: none;
  position: relative;
  width: 1.12rem;
  height: 1.12rem;
  border: 2px solid #64748b;
  border-radius: .32rem;
  background: rgba(255,255,255,.82);
  cursor: pointer;
  flex: 0 0 auto;
  display: inline-grid;
  place-content: center;
  transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
#note-editor .note-checkbox:hover {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,.12);
}
#note-editor .note-checkbox:checked {
  border-color: #4f46e5;
  background: #4f46e5;
  box-shadow: 0 3px 8px rgba(79,70,229,.24);
}
#note-editor .note-checkbox:checked::after {
  content: "";
  width: .34rem;
  height: .58rem;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: translateY(-1px) rotate(45deg);
}
#note-editor .note-numberline { display: flex; align-items: flex-start; gap: .5rem; }
#note-editor .note-number-marker {
  min-width: 1.35rem;
  color: #60728b;
  font-weight: 800;
  text-align: right;
  flex: 0 0 auto;
}

#note-editor .note-image-wrap {
  margin: .5rem 0 .75rem;
  border: 1px dashed rgba(15, 23, 42, .15);
  border-radius: .75rem;
  padding: .5rem;
  background: rgba(255,255,255,.36);
}
#note-editor .note-inline-image {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: .5rem;
  cursor: pointer;
}

.note-editor-yellow { background: #f7f6dd; }
.note-editor-green { background: #e8f7ec; }
.note-editor-blue { background: #e6f3ff; }
.note-editor-pink { background: #fde8f0; }
.note-editor-purple { background: #f0ecff; }
.note-editor-white { background: #f8fafc; }

#note-edit-view .note-emoji-btn {
  width: 1.9rem;
  height: 1.9rem;
  border-radius: .55rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
#note-edit-view .note-emoji-btn:hover { background: #f1f5f9; }
</style>
@endsection
