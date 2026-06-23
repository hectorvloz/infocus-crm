<div id="tdah-toolkit" class="fixed bottom-32 right-4 md:bottom-6 md:right-6 z-[120] md:z-[9999] font-sans flex flex-col items-end tdah-prep">
    <!-- Menu / Panel -->
    <div id="tdah-panel" class="fixed left-3 right-3 bottom-28 max-h-[calc(100vh-7.5rem)] overflow-y-auto w-auto max-w-none md:static md:mb-4 md:w-[23rem] bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 hidden transform transition-all origin-bottom scale-95 opacity-0 md:origin-bottom-right z-[125]">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <div class="bg-indigo-100 p-1.5 rounded-lg">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                Modo TDAH
            </h3>
            <button id="tdah-close" class="text-slate-400 hover:text-slate-600 p-1 rounded-full hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- 1. The One Thing -->
        <div class="mb-5">
            <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-1.5 block">La Única Cosa (Prioridad)</label>
            <div class="relative group">
                <input type="text" id="tdah-one-thing" class="w-full bg-lime-50 border-lime-200 rounded-xl px-3 py-2 text-sm font-bold text-slate-800 placeholder:text-lime-700/40 focus:ring-2 focus:ring-lime-400 focus:border-lime-400 transition-shadow" placeholder="Selecciona o escribe una tarea en La unica cosa que haras">
                <div class="absolute right-2 top-2">
                    <input type="checkbox" id="tdah-one-thing-check" class="rounded-full text-lime-600 focus:ring-lime-500 cursor-pointer w-5 h-5 border-lime-300 bg-white">
                </div>
                <!-- Dropdown for Tasks -->
                <div id="tdah-tasks-dropdown" class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-48 overflow-y-auto custom-scroll">
                    <div class="p-2 text-[10px] text-slate-400 font-bold uppercase bg-slate-50 sticky top-0 border-b border-slate-100">Todas las tareas</div>
                    <div id="tdah-tasks-list">
                        <!-- Filled via JS -->
                    </div>
                </div>
            </div>
            <p id="tdah-one-thing-praise" class="text-xs text-lime-600 font-bold mt-1 hidden animate-bounce">¡Bien hecho! 🎉</p>
        </div>

        <!-- 2. Pomodoro -->
        <div id="tdah-pomodoro-zone" class="mb-5">
            <!-- Tiempo Pomodoro Label -->
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Tiempo Pomodoro</div>
            
            <!-- Timer + Play + Reset Row -->
            <div class="mb-3 relative group flex items-center gap-2">
                <!-- Timer Display (dark box with time) -->
                <div class="flex-1 bg-slate-900 text-white rounded-xl py-3 px-4 text-center font-mono text-2xl font-bold tracking-widest relative overflow-hidden">
                    <span id="tdah-timer-display">25:00</span>
                    <div class="progress-fill-anim absolute bottom-0 left-0 h-1 bg-lime-400 transition-all duration-1000" id="tdah-timer-progress" style="width: 100%"></div>
                    
                    <!-- Fullscreen/PiP Controls (appear on hover, top-right corner) -->
                    <div id="tdah-pomodoro-controls" class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none group-hover:pointer-events-auto">
                        <button id="tdah-open-fullscreen" type="button" class="w-8 h-8 rounded-full border border-[#cde98d] bg-[#dff8a7] text-[#101729] hover:bg-[#cfe88f] flex items-center justify-center transition-colors" title="Pantalla completa">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
                        </button>
                        <button id="tdah-open-pip" type="button" class="w-8 h-8 rounded-full border border-[#cde98d] bg-[#dff8a7] text-[#101729] hover:bg-[#cfe88f] flex items-center justify-center transition-colors" title="Modo PiP">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" stroke-width="2"/><rect x="12" y="11" width="7" height="6" rx="1" stroke-width="2"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Play/Pause button -->
                <button id="tdah-timer-start" type="button" class="w-12 h-12 rounded-xl bg-lime-400 hover:bg-lime-500 text-slate-900 flex items-center justify-center font-bold shadow-sm transition-colors active:scale-95 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>

                <!-- Reset button -->
                <button id="tdah-timer-reset" type="button" class="w-12 h-12 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors active:scale-95 flex-shrink-0" title="Reiniciar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>

            <!-- Work Options -->
            <div class="mb-3 flex items-center justify-between gap-2">
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Tiempo de Trabajo</div>
                <div id="tdah-work-options" class="flex items-center gap-1">
                    <button type="button" data-work="25" class="tdah-work-btn text-[10px] px-2 py-0.5 rounded bg-[#dff8a7] text-[#101729] font-bold">25m</button>
                    <button type="button" data-work="30" class="tdah-work-btn text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">30m</button>
                    <button type="button" data-work="60" class="tdah-work-btn text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">60m</button>
                </div>
            </div>

            <!-- Break Options -->
            <div class="mb-3 flex items-center justify-between gap-2">
                <div id="tdah-break-label" class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Descanso automático: 15m</div>
                <div id="tdah-break-options" class="flex items-center gap-1">
                    <button type="button" data-break="5" class="tdah-break-btn text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">5m</button>
                    <button type="button" data-break="15" class="tdah-break-btn text-[10px] px-2 py-0.5 rounded bg-[#dff8a7] text-[#101729] font-bold">15m</button>
                    <button type="button" data-break="30" class="tdah-break-btn text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold hover:bg-slate-200">30m</button>
                </div>
            </div>

        </div>

    </div>

    <!-- Main Floating Button (hidden, controlled by productivity launcher) -->
    <button id="tdah-toggle" class="flex items-center justify-center w-[3.1rem] h-[3.1rem] bg-[#101729] text-[#f0fe97] rounded-full shadow-2xl hover:bg-[#1a2542] transition-all transform hover:scale-105 active:scale-95 group z-[130] border-[3px] border-white">
        <span class="inline-flex w-full h-full items-center justify-center">
            <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        </span>
        </button>
</div>

<div id="quick-notes-toolkit" class="fixed bottom-32 right-4 md:bottom-6 md:right-6 z-[126] md:z-[10000]">
    <div id="quick-notes-panel" class="fixed left-3 right-3 bottom-28 w-auto max-w-none md:left-auto md:right-6 md:bottom-24 md:w-[21.5rem] bg-white rounded-3xl shadow-2xl border border-slate-200 hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-2 text-slate-900">
                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 20h9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16.5 3.5a2.12 2.12 0 113 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                <h3 class="font-bold text-slate-800 text-lg leading-none">Notas rápidas</h3>
            </div>
            <div class="flex items-center gap-2">
                <div id="quick-notes-count" class="text-slate-400 text-xs font-semibold">0 notas</div>
                <button id="quick-notes-close" type="button" class="w-8 h-8 rounded-full border border-slate-200 text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div id="quick-notes-list-view" class="min-h-0 flex-1 flex flex-col">
            <div class="quick-notes-scroll min-h-0 flex-1 overflow-y-auto px-4 py-3">
                <div id="quick-notes-list" class="space-y-2.5">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/60 py-16 text-center text-slate-400">
                        <div class="text-base font-semibold">No tienes notas aún</div>
                    </div>
                </div>
            </div>
            <div class="p-3 border-t border-slate-100 shrink-0 bg-white">
                <button id="quick-notes-new" type="button" class="w-full rounded-2xl bg-[#ecfe88] hover:bg-[#d9ef60] text-slate-900 text-base font-extrabold py-2.5 transition-colors">+ Nueva nota</button>
            </div>
        </div>
        <div id="quick-notes-editor-view" class="hidden">
            <div class="flex items-center justify-between px-3.5 py-2 border-b border-slate-200">
                <button id="quick-note-back" type="button" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-500 transition-colors">
                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="text-base font-extrabold text-slate-800 leading-none">Editando nota</div>
                <button id="quick-note-delete" type="button" class="w-8 h-8 rounded-full hover:bg-rose-50 text-rose-400 hover:text-rose-600 transition-colors">
                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6V4h8v2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6l-1 14H6L5 6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6M14 11v6"/></svg>
                </button>
            </div>
            <div class="quick-note-toolbar px-4 py-3 border-b border-[#dde0e8] bg-[#f4f5f8]">
                <div class="flex items-center gap-2">
                    <button data-qn-cmd="checkline" type="button" class="qn-tool-btn" title="Checklist">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 11l3 3L22 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2 20h14"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2 12h4"/></svg>
                    </button>
                    <button data-qn-cmd="numberline" type="button" class="qn-tool-btn" title="Lista numerada">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 6h12M10 12h12M10 18h12"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h.01M4 12h.01M4 18h.01"/></svg>
                    </button>
                    <button data-qn-cmd="bold" type="button" class="qn-tool-btn qn-tool-text-btn">B</button>
                    <button data-qn-cmd="italic" type="button" class="qn-tool-btn qn-tool-text-btn italic">I</button>
                    <button data-qn-cmd="strikeThrough" type="button" class="qn-tool-btn qn-tool-text-btn line-through">S</button>
                    <button data-qn-cmd="divider" type="button" class="qn-tool-btn" title="Separador">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.4" d="M5 12h14"/></svg>
                    </button>
                    <button id="quick-note-emoji-toggle" type="button" class="qn-tool-btn" title="Emoji">😊</button>
                    <button id="quick-note-image-btn" type="button" class="qn-tool-btn" title="Insertar imagen">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 15-4.5-4.5L8 19"/></svg>
                    </button>
                    <div class="relative">
                        <button id="quick-note-color-toggle" type="button" class="qn-tool-btn qn-palette-toggle" title="Colores de fondo">
                            <span class="qn-palette-swatch" aria-hidden="true"></span>
                        </button>
                        <div id="quick-note-color-popover" class="hidden absolute right-0 top-[calc(100%+0.45rem)] rounded-xl border border-slate-200 bg-white shadow-lg p-2 z-30">
                            <div class="flex items-center gap-2">
                                <button data-qn-color="yellow" type="button" class="qn-color-dot is-active" style="background:#f4dc38"></button>
                                <button data-qn-color="green" type="button" class="qn-color-dot" style="background:#34c98d"></button>
                                <button data-qn-color="blue" type="button" class="qn-color-dot" style="background:#31afe9"></button>
                                <button data-qn-color="pink" type="button" class="qn-color-dot" style="background:#f46787"></button>
                                <button data-qn-color="purple" type="button" class="qn-color-dot" style="background:#9b7df0"></button>
                                <button data-qn-color="white" type="button" class="qn-color-dot" style="background:#c9d3e1"></button>
                            </div>
                        </div>
                    </div>
                    <input id="quick-note-image-input" type="file" accept="image/*" class="hidden">
                </div>
                <div id="quick-note-emoji-popover" class="hidden mt-2 rounded-xl border border-slate-200 bg-white shadow-lg p-2 w-fit">
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="😀">😀</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="😎">😎</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="🔥">🔥</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="✅">✅</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="📌">📌</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="💡">💡</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="🚀">🚀</button>
                        <button type="button" class="qn-emoji-btn" data-qn-emoji="❤️">❤️</button>
                    </div>
                </div>
            </div>
            <div id="quick-note-editor-canvas" class="relative px-4 py-4 pb-14 bg-[#f7f6dd] border-b border-slate-200">
                <div id="quick-note-editor" class="min-h-[170px] text-[1.02rem] leading-snug font-medium text-[#1f2d49] outline-none" contenteditable="true" spellcheck="true"></div>
                <div class="absolute bottom-3 right-3">
                    <button id="quick-note-client-toggle" type="button" class="w-8 h-8 rounded-full bg-indigo-600 text-white shadow-lg hover:bg-indigo-500 transition-colors" title="Vincular cliente">
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div id="quick-note-client-popover" class="hidden absolute bottom-10 right-0 w-52 rounded-2xl border border-slate-200 bg-white shadow-2xl p-2 z-20">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Vincular cliente</div>
                        <div class="relative">
                            <button id="quick-note-client-select-trigger" type="button" class="w-full rounded-xl border border-slate-200 text-xs px-2.5 py-2 text-slate-700 bg-white inline-flex items-center justify-between gap-2">
                                <span id="quick-note-client-selected-label" class="truncate">Sin cliente</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="quick-note-client-dropdown" class="hidden absolute left-0 right-0 bottom-[calc(100%+0.4rem)] rounded-xl border border-slate-200 bg-white shadow-xl p-1.5">
                                <input id="quick-note-client-search" type="text" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs text-slate-700 mb-1.5" placeholder="Buscar cliente...">
                                <div id="quick-note-client-options" class="max-h-36 overflow-y-auto space-y-1"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-slate-50 text-slate-400 text-xs font-semibold">
                Enter añade línea · Backspace en vacío cambia tipo
            </div>
        </div>
    </div>
</div>

<div id="productivity-launcher" class="fixed bottom-32 right-4 md:bottom-6 md:right-6 z-[131] md:z-[10001]">
    <div id="productivity-actions" class="hidden absolute bottom-[3.4rem] right-1 flex flex-col items-center gap-1">
        <button id="productivity-open-notes" type="button" class="productivity-action-btn" title="Notas rápidas" aria-label="Notas rápidas">
            <span class="inline-flex w-full h-full items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 20h9"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16.5 3.5a2.12 2.12 0 113 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </span>
        </button>
        <button id="productivity-open-tdah" type="button" class="productivity-action-btn" title="Modo TDAH" aria-label="Modo TDAH">
            <span class="inline-flex w-full h-full items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </span>
        </button>
    </div>
    <button id="productivity-toggle" type="button" class="w-[3.1rem] h-[3.1rem] rounded-full bg-[#f0fe97] text-[#101729] shadow-2xl border-2 border-[#101729] flex items-center justify-center transition-transform hover:scale-105 hover:bg-[#e6f67f]">
        <svg id="productivity-toggle-plus" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.52 2.35a1 1 0 0 1 .43 1.1L12.3 9H18a1 1 0 0 1 .78 1.63l-9.2 11.35a1 1 0 0 1-1.73-.9L9.7 14H4.9a1 1 0 0 1-.82-1.57l8.2-11a1 1 0 0 1 1.24-.08Z"/></svg>
        <svg id="productivity-toggle-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<div id="tdah-pomodoro-fullscreen" class="fixed inset-0 z-[140] hidden backdrop-blur-md" style="background:#101729;">
    <div class="absolute top-4 right-4 flex items-center gap-2">
        <button id="tdah-pomodoro-fs-toggle" type="button" class="w-11 h-11 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Pantalla completa">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
        </button>
        <button id="tdah-pomodoro-pip-btn" type="button" class="w-11 h-11 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Pasar a PiP">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" stroke-width="2"/><rect x="12" y="11" width="7" height="6" rx="1" stroke-width="2"/></svg>
        </button>
        <button id="tdah-pomodoro-close" type="button" class="w-11 h-11 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Cerrar pomodoro">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="h-full w-full flex items-center justify-center p-6">
        <div class="w-full max-w-4xl text-center">
            <div id="tdah-pomodoro-phase" class="text-xs md:text-sm font-extrabold uppercase tracking-[0.32em] text-lime-300/80 mb-4">Trabajo enfocado</div>
            <div id="tdah-pomodoro-task-title" class="text-2xl md:text-5xl font-extrabold text-white mb-2">Selecciona o escribe una tarea</div>
            <div id="tdah-pomodoro-project-title" class="text-sm md:text-xl text-slate-300 mb-8">Pomodoro</div>
            <div id="tdah-pomodoro-fs-display" class="text-[72px] md:text-[140px] leading-none font-mono font-extrabold text-lime-300 tracking-tight">25:00</div>
            <div class="mt-8 flex items-center justify-center gap-3">
                <button id="tdah-pomodoro-fs-start" type="button" class="w-14 h-14 rounded-full bg-lime-300 text-[#101729] hover:bg-lime-200 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <button id="tdah-pomodoro-fs-reset" type="button" class="w-14 h-14 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            <div class="mt-5 flex items-center justify-center gap-3">
                <button id="tdah-pomodoro-fs-save" type="button" class="px-4 py-2 rounded-xl border border-white/20 bg-white/10 text-white text-sm font-bold hover:bg-white/20">Guardar</button>
                <button id="tdah-pomodoro-fs-delete" type="button" class="px-4 py-2 rounded-xl border border-rose-300/40 bg-rose-500/10 text-rose-100 text-sm font-bold hover:bg-rose-500/20">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<div id="tdah-pomodoro-mini-pip" class="fixed bottom-24 right-5 z-[155] hidden w-72 rounded-2xl border border-[#2b3658] bg-[#101729] p-4 text-white shadow-2xl">
    <div class="mb-3 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div id="tdah-pomodoro-mini-phase" class="text-[10px] font-extrabold uppercase tracking-[0.26em] text-lime-300/80">Pomodoro</div>
            <div id="tdah-pomodoro-mini-task" class="mt-1 truncate text-sm font-extrabold">Selecciona o escribe una tarea</div>
            <div id="tdah-pomodoro-mini-project" class="truncate text-xs font-semibold text-slate-400">Pomodoro</div>
        </div>
        <button id="tdah-pomodoro-mini-close" type="button" class="shrink-0 rounded-full border border-white/10 px-2 py-0.5 text-sm text-slate-300 hover:bg-white/10 hover:text-white" title="Cerrar PiP" aria-label="Cerrar PiP">×</button>
    </div>
    <div id="tdah-pomodoro-mini-display" class="rounded-xl bg-[#070c16] px-3 py-3 text-center font-mono text-4xl font-extrabold tracking-tight text-lime-300">25:00</div>
    <div class="mt-3 flex items-center justify-center gap-2">
        <button id="tdah-pomodoro-mini-start" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-lime-300 text-[#101729] hover:bg-lime-200"></button>
        <button id="tdah-pomodoro-mini-reset" type="button" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white hover:bg-white/15">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>
</div>

<video id="tdah-native-pip-video" class="hidden" muted playsinline></video>
<canvas id="tdah-native-pip-canvas" class="hidden" width="520" height="520"></canvas>

<div id="tdah-pomodoro-timer-conflict-modal" class="fixed inset-0 z-[160] hidden">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="window.closeTdahPomodoroTimerConflictModal?.('cancel')"></div>
    <div class="relative h-full w-full flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="text-lg font-extrabold text-slate-900">Temporizador activo detectado</div>
            <div class="mt-2 text-sm leading-6 text-slate-600">Ya hay un temporizador de tarea en marcha. ¿Quieres guardar o eliminar el temporizador actual antes de iniciar Pomodoro TDAH?</div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" onclick="window.closeTdahPomodoroTimerConflictModal?.('cancel')" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button type="button" onclick="window.closeTdahPomodoroTimerConflictModal?.('delete')" class="px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 text-sm font-bold text-rose-700 hover:bg-rose-100">Eliminar</button>
                <button type="button" onclick="window.closeTdahPomodoroTimerConflictModal?.('save')" class="px-4 py-2 rounded-xl bg-lime-300 text-sm font-bold text-[#101729] hover:bg-lime-200">Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Prevent first-paint flicker when navigating between pages. */
    #tdah-toolkit.tdah-prep #tdah-toggle,
    #tdah-toolkit.tdah-prep #tdah-panel,
    #tdah-toolkit.tdah-prep #tdah-toggle svg {
        transition: none !important;
        animation: none !important;
    }

    #tdah-toolkit #tdah-toggle {
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
        will-change: transform;
        transform: translateZ(0);
        touch-action: none;
        cursor: grab;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        right: 0;
        bottom: 0;
    }

    #tdah-toolkit #tdah-toggle:active {
        cursor: grabbing;
    }

    /* Focus Mode Styles */
    body.tdah-focus-active #sidebar,
    body.tdah-focus-active header,
    body.tdah-focus-active .dashboard-view:not(#view-notas) { /* Keep Notes view accessible if needed, or dim everything */
        opacity: 0.05;
        pointer-events: none;
        filter: grayscale(100%);
        transition: opacity 0.5s ease, filter 0.5s ease;
    }
    body.tdah-focus-active main {
        margin-left: 0 !important;
        /* max-width: 900px; Remove max-width restriction to avoid layout jumping */
        /* margin: 0 auto; */
    }
    body.tdah-focus-active #tdah-toolkit {
        opacity: 1 !important;
        pointer-events: auto;
        filter: none;
    }
    
    .tdah-visible {
        display: block !important;
        opacity: 1 !important;
        transform: scale(100%) !important;
    }

    /* PiP/Fullscreen controls - hidden by default, show on group hover */
    #tdah-pomodoro-controls {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    /* Show on hover in group */
    #tdah-pomodoro-zone .group:hover #tdah-pomodoro-controls {
        opacity: 1;
        pointer-events: auto;
    }

    /* Always show on touch devices */
    @media (hover: none) and (pointer: coarse) {
        #tdah-pomodoro-controls {
            opacity: 1;
            pointer-events: auto;
        }
    }

    @media (min-width: 768px) {
        #tdah-panel {
            left: auto;
            right: auto;
            bottom: auto;
        }
    }

    @media (max-width: 767px) {
        #tdah-toolkit,
        #quick-notes-toolkit {
            right: .95rem !important;
            bottom: calc(9.65rem + env(safe-area-inset-bottom)) !important;
            z-index: 2147482440 !important;
        }

        #productivity-launcher {
            right: .95rem !important;
            bottom: calc(9.65rem + env(safe-area-inset-bottom)) !important;
            z-index: 2147482460 !important;
        }

        #tdah-toolkit #tdah-toggle,
        #productivity-toggle,
        .productivity-action-btn {
            width: 2.75rem !important;
            height: 2.75rem !important;
            border-width: 2px !important;
            box-shadow: 0 14px 28px -14px rgba(16, 23, 41, .72), 0 7px 16px -12px rgba(16, 23, 41, .55) !important;
        }

        #productivity-actions {
            bottom: 3.05rem !important;
            right: 0 !important;
            gap: .35rem !important;
        }

        #tdah-panel,
        #quick-notes-panel {
            left: .75rem !important;
            right: .75rem !important;
            bottom: calc(13rem + env(safe-area-inset-bottom)) !important;
            max-height: calc(100vh - 15rem) !important;
            border-radius: 1.35rem !important;
        }

        #tdah-pomodoro-mini-pip {
            right: .75rem !important;
            bottom: calc(5.85rem + env(safe-area-inset-bottom)) !important;
            width: min(17rem, calc(100vw - 1.5rem)) !important;
            z-index: 2147482470 !important;
        }
    }

    .productivity-action-btn {
        width: 3.1rem;
        height: 3.1rem;
        border-radius: 9999px;
        border: 3px solid #ffffff;
        background: #101729;
        color: #f0fe97;
        box-shadow: 0 18px 38px -18px rgba(16, 23, 41, 0.58), 0 8px 18px -12px rgba(16, 23, 41, 0.38);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
    }

    .productivity-action-btn:hover {
        background: #1a2542;
        transform: translateY(-1px) scale(1.04);
        box-shadow: 0 22px 42px -18px rgba(16, 23, 41, 0.65), 0 10px 22px -12px rgba(16, 23, 41, 0.42);
    }

    .productivity-action-btn:active {
        transform: translateY(0) scale(0.96);
    }

    .quick-note-toolbar .qn-tool-btn {
        width: 2.05rem;
        height: 2.05rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        color: #60728b;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .quick-note-toolbar .qn-tool-btn:hover { background: #eef2f8; }
    .quick-note-toolbar .qn-tool-btn-active { background: #dfe3ff; color: #4f46e5; }
    .quick-note-toolbar .qn-tool-text-btn { font-size: 1.1rem; line-height: 1; }
    .quick-note-toolbar .qn-palette-toggle {
        border-radius: 999px;
        border-color: #d4dae3;
        background: #ffffff;
    }
    .quick-note-toolbar .qn-palette-swatch {
        width: 1.08rem;
        height: 1.08rem;
        border-radius: 999px;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 1px rgba(96, 114, 139, 0.25);
        background: conic-gradient(from 210deg, #f4dc38, #34c98d, #31afe9, #f46787, #9b7df0, #f4dc38);
        display: inline-block;
    }

    .qn-color-dot {
        width: 1.6rem;
        height: 1.6rem;
        border-radius: 999px;
        border: 2px solid transparent;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .qn-color-dot:hover { transform: scale(1.05); }
    .qn-color-dot.is-active { border-color: #4f46e5; }

    .qn-checkline {
        display: flex;
        align-items: center;
        gap: 0.45rem;
    }
    .qn-checkline span,
    .qn-numberline .qn-number-content {
        min-width: 1ch;
        outline: none;
    }
    .qn-checkline .qn-checkbox {
        width: 1.05rem;
        height: 1.05rem;
        accent-color: #4f46e5;
        cursor: pointer;
        flex: 0 0 auto;
    }
    .qn-numberline {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }
    .qn-number-marker {
        min-width: 1.35rem;
        color: #60728b;
        font-weight: 800;
        text-align: right;
        flex: 0 0 auto;
    }

    .qn-card {
        border-radius: 1.15rem;
        border: 1px solid #f3df63;
        padding: 0.8rem 0.85rem 0.7rem;
    }
        .qn-card-wrapper { position: relative; }
        .qn-card-delete-btn { position: absolute; top: 0.45rem; right: 0.45rem; width: 1.6rem; height: 1.6rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: transparent; color: #b0a0a0; border: none; cursor: pointer; opacity: 0; transition: opacity 0.15s, background 0.15s, color 0.15s; padding: 0; }
        .qn-card-wrapper:hover .qn-card-delete-btn { opacity: 1; }
        .qn-card-delete-btn:hover { background: rgba(220,38,38,0.12); color: #dc2626; }
        .qn-card-title { font-size: 0.95rem; font-weight: 800; color: #1f2d49; line-height: 1.2; }
    #quick-notes-panel:not(.hidden) {
        display: flex;
        flex-direction: column;
        max-height: min(42rem, calc(100vh - 8rem));
        overflow: hidden;
    }
    #quick-notes-editor-view:not(.hidden) {
        display: flex;
        min-height: 0;
        flex-direction: column;
    }
    #quick-note-editor-canvas {
        min-height: 0;
        max-height: min(27rem, calc(100vh - 18rem));
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    .qn-card-body { margin-top: 0.35rem; color: #1f2d49; font-size: 0.84rem; line-height: 1.38; white-space: pre-wrap; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; }
    .qn-card-client { margin-top: 0.4rem; }
    .qn-card-client-badge { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #c7d2fe; background: #eef2ff; color: #4f46e5; font-size: 0.62rem; font-weight: 700; padding: 0.1rem 0.42rem; max-width: 100%; }
    .qn-card-client-badge > span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .qn-card-meta { margin-top: 0.42rem; padding-top: 0.38rem; border-top: 1px solid rgba(31,45,73,.14); display: flex; align-items: center; justify-content: space-between; color: #8da0b9; font-weight: 600; font-size: 0.72rem; }

    #quick-note-editor,
    #quick-note-editor * {
        font-family: inherit !important;
    }
    #quick-note-editor span,
    #quick-note-editor font {
        font-size: inherit !important;
        line-height: inherit;
    }
    #quick-note-editor h1,
    #quick-note-editor h2,
    #quick-note-editor h3 {
        margin: 0 0 0.4rem;
        line-height: 1.2;
        font-weight: 800;
        text-align: left;
        overflow-wrap: anywhere;
    }
    #quick-note-editor h1 {
        font-size: 1.42em;
    }
    #quick-note-editor h2 {
        font-size: 1.24em;
        font-weight: 750;
        color: #31415f;
    }
    #quick-note-editor h3 {
        font-size: 1.1em;
    }
    #quick-note-editor div,
    #quick-note-editor p,
    #quick-note-editor li {
        margin: 0 0 0.3rem;
        font-size: 0.92em;
        font-weight: 400;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }
    #quick-note-editor {
        caret-color: #101729;
        cursor: text;
    }
    #quick-note-editor:focus {
        outline: none;
    }
    #quick-note-editor h1.qn-title-placeholder {
        position: relative;
        text-align: left;
    }
    #quick-note-editor h1.qn-title-placeholder.is-empty::before {
        content: attr(data-placeholder);
        color: #a8b3c4;
        pointer-events: none;
    }
    #quick-note-editor .qn-divider {
        border: 0;
        border-top: 2px solid rgba(96, 114, 139, 0.28);
        margin: 0.75rem 0;
    }

    #quick-note-editor .qn-image-wrap {
        margin: 0.5rem 0 0.75rem;
        border: 1px dashed rgba(15, 23, 42, 0.16);
        border-radius: 0.75rem;
        padding: 0.45rem;
        background: rgba(255, 255, 255, 0.38);
    }

    #quick-note-editor .qn-inline-image {
        display: block;
        max-width: 100%;
        height: auto;
        border-radius: 0.55rem;
        cursor: pointer;
    }

    .qn-emoji-btn {
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .qn-emoji-btn:hover {
        background: #f1f5f9;
    }

    .qn-editor-yellow { background: #f7f6dd; }
    .qn-editor-green { background: #e8f7ec; }
    .qn-editor-blue { background: #e6f3ff; }
    .qn-editor-pink { background: #fde8f0; }
    .qn-editor-purple { background: #f0ecff; }
    .qn-editor-white { background: #f8fafc; }

    @media (min-width: 768px) {
        .qn-card-title { font-size: 1rem; }
        .qn-card-body { font-size: 0.86rem; }
        .qn-card-meta { font-size: 0.74rem; }
    }

        #quick-notes-panel:not(.hidden) {
            display: flex;
            flex-direction: column;
            max-height: min(32rem, calc(100vh - 9rem));
        }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Elements
        const toolkit = document.getElementById('tdah-toolkit');
        const toggleBtn = document.getElementById('tdah-toggle');
        const closeBtn = document.getElementById('tdah-close');
        const panel = document.getElementById('tdah-panel');

        // Re-enable transitions after initial paint to avoid visual blink on page change.
        if (toolkit) {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => toolkit.classList.remove('tdah-prep'));
            });
        }
        
        // --- Toggle Visibility ---
        function openTdahPanel() {
            if (!panel.classList.contains('hidden')) return;
            panel.classList.remove('hidden');
            requestAnimationFrame(() => {
                positionPanelSafely();
                panel.classList.add('tdah-visible');
            });
            loadActiveProjects();
            if (typeof syncProductivityToggleIcon === 'function') syncProductivityToggleIcon();
        }

        function closeTdahPanel() {
            if (panel.classList.contains('hidden')) return;
            panel.classList.remove('tdah-visible');
            setTimeout(() => {
                panel.classList.add('hidden');
                if (typeof syncProductivityToggleIcon === 'function') syncProductivityToggleIcon();
            }, 200);
        }

        function toggleMenu() {
            if (panel.classList.contains('hidden')) {
                openTdahPanel();
            } else {
                closeTdahPanel();
            }
        }
        
        let dragState = null;
        let suppressToggleClick = false;
        const tdahDragEnabled = false;

        function clamp(val, min, max) {
            return Math.min(Math.max(val, min), max);
        }

        function getViewportBounds() {
            const margin = 8;
            const mobileBottomNavHeight = window.innerWidth < 768 ? 96 : margin;
            return {
                margin,
                maxLeft: Math.max(margin, window.innerWidth - toggleBtn.offsetWidth - margin),
                maxTop: Math.max(margin, window.innerHeight - toggleBtn.offsetHeight - mobileBottomNavHeight),
            };
        }

        function positionPanelSafely() {
            if (!panel || !toggleBtn) return;

            if (window.innerWidth < 768) {
                panel.style.position = '';
                panel.style.left = '';
                panel.style.top = '';
                panel.style.right = '';
                panel.style.bottom = '';
                panel.style.width = '';
                return;
            }

            panel.style.position = 'fixed';
            panel.style.width = '23rem';
            panel.style.maxWidth = 'calc(100vw - 24px)';
            panel.style.maxHeight = 'calc(100vh - 120px)';
            panel.style.overflowY = 'auto';

            const margin = 12;
            const toggleRect = toggleBtn.getBoundingClientRect();
            const panelRect = panel.getBoundingClientRect();
            const panelWidth = panelRect.width || 368;
            const panelHeight = panelRect.height || 560;

            const maxLeft = Math.max(margin, window.innerWidth - panelWidth - margin);
            const maxTop = Math.max(margin, window.innerHeight - panelHeight - margin);

            const preferredLeft = toggleRect.right - panelWidth;
            const preferredTop = toggleRect.top - panelHeight - 12;
            const fallbackTop = toggleRect.bottom + 12;

            const left = clamp(preferredLeft, margin, maxLeft);
            const top = preferredTop < margin
                ? clamp(fallbackTop, margin, maxTop)
                : clamp(preferredTop, margin, maxTop);

            panel.style.left = `${left}px`;
            panel.style.top = `${top}px`;
            panel.style.right = 'auto';
            panel.style.bottom = 'auto';
        }

        function applyStoredPosition() {
            const raw = localStorage.getItem('tdah_toolkit_pos');
            if (!raw || !toolkit || !toggleBtn) return;
            try {
                const pos = JSON.parse(raw);
                if (typeof pos.left !== 'number' || typeof pos.top !== 'number') return;
                const bounds = getViewportBounds();
                toolkit.style.left = clamp(pos.left, bounds.margin, bounds.maxLeft) + 'px';
                toolkit.style.top = clamp(pos.top, bounds.margin, bounds.maxTop) + 'px';
                toolkit.style.right = 'auto';
                toolkit.style.bottom = 'auto';
                if (!panel.classList.contains('hidden')) {
                    positionPanelSafely();
                }
            } catch (_) {}
        }

        function onDragMove(e) {
            if (!dragState || !toolkit || !toggleBtn) return;
            const dx = e.clientX - dragState.startX;
            const dy = e.clientY - dragState.startY;
            if (!dragState.moved && (Math.abs(dx) > 4 || Math.abs(dy) > 4)) {
                dragState.moved = true;
            }

            const bounds = getViewportBounds();
            const left = clamp(dragState.startLeft + dx, bounds.margin, bounds.maxLeft);
            const top = clamp(dragState.startTop + dy, bounds.margin, bounds.maxTop);

            toolkit.style.left = left + 'px';
            toolkit.style.top = top + 'px';
            toolkit.style.right = 'auto';
            toolkit.style.bottom = 'auto';
        }

        function onDragEnd() {
            if (!dragState || !toolkit) return;
            const moved = dragState.moved;
            dragState = null;
            window.removeEventListener('pointermove', onDragMove);
            window.removeEventListener('pointerup', onDragEnd);

            if (moved) {
                suppressToggleClick = true;
                const left = parseFloat(toolkit.style.left || '0');
                const top = parseFloat(toolkit.style.top || '0');
                localStorage.setItem('tdah_toolkit_pos', JSON.stringify({ left, top }));
                setTimeout(() => { suppressToggleClick = false; }, 0);
            }
        }

        if (toggleBtn && toolkit && tdahDragEnabled) {
            applyStoredPosition();

            toggleBtn.addEventListener('pointerdown', (e) => {
                const rect = toolkit.getBoundingClientRect();
                dragState = {
                    startX: e.clientX,
                    startY: e.clientY,
                    startLeft: rect.left,
                    startTop: rect.top,
                    moved: false,
                };
                window.addEventListener('pointermove', onDragMove);
                window.addEventListener('pointerup', onDragEnd);
            });

            toggleBtn.addEventListener('click', (e) => {
                if (suppressToggleClick) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                toggleMenu();
            });

            window.addEventListener('resize', () => {
                applyStoredPosition();
                if (!panel.classList.contains('hidden')) {
                    positionPanelSafely();
                }
            });
        }

        window.addEventListener('resize', () => {
            if (!panel.classList.contains('hidden')) {
                positionPanelSafely();
            }
        });

        if(closeBtn) closeBtn.addEventListener('click', toggleMenu);

        document.addEventListener('pointerdown', (event) => {
            const target = event.target;
            if (!target || panel.classList.contains('hidden')) return;
            if (toolkit.contains(target)) return;
            closeTdahPanel();
        });

        // --- Quick Notes ---
        const quickNotesPanel = document.getElementById('quick-notes-panel');
        const quickNotesClose = document.getElementById('quick-notes-close');
        const quickNotesListView = document.getElementById('quick-notes-list-view');
        const quickNotesEditorView = document.getElementById('quick-notes-editor-view');
        const quickNotesNew = document.getElementById('quick-notes-new');
        const quickNotesList = document.getElementById('quick-notes-list');
        const quickNotesCount = document.getElementById('quick-notes-count');
        const quickNoteEditor = document.getElementById('quick-note-editor');
        const quickNoteBack = document.getElementById('quick-note-back');
        const quickNoteDelete = document.getElementById('quick-note-delete');
        const quickNoteCanvas = document.getElementById('quick-note-editor-canvas');
        const quickNoteClientToggle = document.getElementById('quick-note-client-toggle');
        const quickNoteClientPopover = document.getElementById('quick-note-client-popover');
        const quickNoteClientSelectTrigger = document.getElementById('quick-note-client-select-trigger');
        const quickNoteClientSelectedLabel = document.getElementById('quick-note-client-selected-label');
        const quickNoteClientDropdown = document.getElementById('quick-note-client-dropdown');
        const quickNoteClientSearch = document.getElementById('quick-note-client-search');
        const quickNoteClientOptions = document.getElementById('quick-note-client-options');
        const quickNoteEmojiToggle = document.getElementById('quick-note-emoji-toggle');
        const quickNoteEmojiPopover = document.getElementById('quick-note-emoji-popover');
        const quickNoteEmojiButtons = Array.from(document.querySelectorAll('.qn-emoji-btn'));
        const quickNoteImageBtn = document.getElementById('quick-note-image-btn');
        const quickNoteImageInput = document.getElementById('quick-note-image-input');
        const quickNoteColorToggle = document.getElementById('quick-note-color-toggle');
        const quickNoteColorPopover = document.getElementById('quick-note-color-popover');
        const quickToolbarButtons = Array.from(document.querySelectorAll('[data-qn-cmd]'));
        const quickColorButtons = Array.from(document.querySelectorAll('[data-qn-color]'));

        const productivityToggle = document.getElementById('productivity-toggle');
        const productivityActions = document.getElementById('productivity-actions');
        const productivityOpenNotes = document.getElementById('productivity-open-notes');
        const productivityOpenTdah = document.getElementById('productivity-open-tdah');
        const productivityPlus = document.getElementById('productivity-toggle-plus');
        const productivityCloseIcon = document.getElementById('productivity-toggle-close');
        const productivityLauncher = document.getElementById('productivity-launcher');

        const QUICK_NOTES_USER_KEY = @json((string) (auth()->id() ?? session('user.id') ?? session('user.email') ?? 'anon'));
        const QUICK_NOTES_KEY = `infocus_quick_notes_v1_${QUICK_NOTES_USER_KEY}`;
        const quickNotePalette = {
            yellow: { editorClass: 'qn-editor-yellow', cardBg: '#fef9c3', cardBorder: '#fde047' },
            green: { editorClass: 'qn-editor-green', cardBg: '#dff4e7', cardBorder: '#9fd4ad' },
            blue: { editorClass: 'qn-editor-blue', cardBg: '#def0ff', cardBorder: '#9cc6ed' },
            pink: { editorClass: 'qn-editor-pink', cardBg: '#fee7f0', cardBorder: '#f5b7cd' },
            purple: { editorClass: 'qn-editor-purple', cardBg: '#efe8ff', cardBorder: '#cdbdf8' },
            white: { editorClass: 'qn-editor-white', cardBg: '#f8fafc', cardBorder: '#d5dee9' },
        };

        let quickNotes = [];
        let activeQuickNoteId = null;
        let activeQuickColor = 'yellow';
        let activeQuickLinkedClient = '';
        let quickNoteClientsCache = [];
        let isProductivityMenuOpen = false;

        function loadQuickNotes() {
            try {
                const parsed = JSON.parse(localStorage.getItem(QUICK_NOTES_KEY) || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) {
                return [];
            }
        }

        function persistQuickNotes() {
            localStorage.setItem(QUICK_NOTES_KEY, JSON.stringify(quickNotes));
        }

        function getQuickNoteById(id) {
            return quickNotes.find((note) => String(note.id) === String(id)) || null;
        }

        function formatQuickDate(timestamp) {
            const date = Number(timestamp || Date.now());
            const now = new Date();
            const target = new Date(date);
            const isSameDay = now.toDateString() === target.toDateString();
            if (isSameDay) return 'Hoy';
            return target.toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit' });
        }

        function normalizeQuickNoteHtml(html) {
            const holder = document.createElement('div');
            holder.innerHTML = String(html || '').replace(/<div><br><\/div>/g, '<div></div>');
            promoteQuickHeadingBlocks(holder);
            scrubQuickNoteHtml(holder);
            return holder.innerHTML.trim();
        }

        function isQuickHeadingText(text) {
            const value = String(text || '').replace(/\s+/g, ' ').trim();
            if (!value || value.length > 120 || /[.!?;:]$/.test(value)) return false;
            return /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(value);
        }

        function isQuickAllCapsHeading(text) {
            const value = String(text || '').replace(/\s+/g, ' ').trim();
            if (!isQuickHeadingText(value)) return false;
            const letters = value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '');
            return letters.length >= 3 && letters === letters.toUpperCase();
        }

        function hasSingleQuickBoldChild(el) {
            const meaningful = Array.from(el.childNodes || []).filter((node) => {
                return !(node.nodeType === Node.TEXT_NODE && !(node.textContent || '').trim());
            });
            return meaningful.length === 1
                && meaningful[0].nodeType === Node.ELEMENT_NODE
                && ['strong', 'b'].includes(meaningful[0].tagName.toLowerCase());
        }

        function promoteQuickHeadingBlocks(root) {
            if (!root) return;
            root.querySelectorAll('p,div').forEach((block) => {
                if (!block.isConnected) return;
                if (block.closest('li,.qn-checkline,.qn-numberline')) return;
                const text = (block.textContent || '').replace(/\u00a0/g, ' ').trim();
                if (!isQuickHeadingText(text)) return;
                const shouldPromote = hasSingleQuickBoldChild(block) || isQuickAllCapsHeading(text);
                if (!shouldPromote) return;
                const heading = document.createElement(isQuickAllCapsHeading(text) ? 'h2' : 'h3');
                heading.innerHTML = block.innerHTML;
                block.replaceWith(heading);
            });
        }

        function isSafeQuickNoteHref(href) {
            if (!href) return false;
            return /^(https?:|mailto:|tel:|#|\/)/i.test(href);
        }

        function buildSafeQuickNoteStyle(el) {
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

        function scrubQuickNoteHtml(root) {
            if (!root) return;
            root.querySelectorAll('script,style,meta,link,title,xml,o\\:p').forEach((node) => node.remove());
            root.querySelectorAll('font').forEach((node) => {
                const fragment = document.createDocumentFragment();
                while (node.firstChild) fragment.appendChild(node.firstChild);
                node.replaceWith(fragment);
            });
            root.querySelectorAll('*').forEach((el) => {
                const tag = el.tagName.toLowerCase();
                const safeStyle = buildSafeQuickNoteStyle(el);
                const allowedClasses = Array.from(el.classList || []).filter((className) => {
                    return className.startsWith('qn-') || className === 'is-empty';
                });
                Array.from(el.attributes).forEach((attr) => {
                    const name = attr.name.toLowerCase();
                    const keepLinkAttr = tag === 'a' && ['href', 'target', 'rel'].includes(name);
                    const keepImageAttr = tag === 'img' && ['src', 'alt', 'loading'].includes(name);
                    const keepCheckboxAttr = tag === 'input' && ['type', 'checked', 'contenteditable'].includes(name);
                    const keepEditorData = ['data-placeholder', 'data-qn-number', 'data-qn-scale'].includes(name);
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
                    if (!isSafeQuickNoteHref(href)) {
                        el.removeAttribute('href');
                    } else {
                        el.setAttribute('rel', 'noopener noreferrer');
                    }
                }
                if (tag === 'input' && !el.classList.contains('qn-checkbox')) {
                    el.remove();
                }
            });
        }

        function cleanQuickPastedNode(node) {
            if (node.nodeType === Node.TEXT_NODE) return document.createTextNode(node.nodeValue || '');
            if (node.nodeType !== Node.ELEMENT_NODE) return document.createDocumentFragment();
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
            Array.from(node.childNodes).forEach((child) => target.appendChild(cleanQuickPastedNode(child)));
            if (target.nodeType === Node.ELEMENT_NODE) {
                if (targetTag === 'a') {
                    const href = String(node.getAttribute('href') || '').trim();
                    if (isSafeQuickNoteHref(href)) {
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

        function normalizeQuickPastedHtml(html) {
            const source = document.createElement('div');
            source.innerHTML = String(html || '');
            const holder = document.createElement('div');
            Array.from(source.childNodes).forEach((node) => holder.appendChild(cleanQuickPastedNode(node)));
            promoteQuickHeadingBlocks(holder);
            scrubQuickNoteHtml(holder);
            return holder.innerHTML;
        }

        function quickPlainTextToHtml(text) {
            return String(text || '')
                .replace(/\r\n?/g, '\n')
                .split(/\n{2,}/)
                .map((paragraph) => {
                    const lines = paragraph.split('\n').map((line) => escapeHtml(line));
                    return '<p>' + (lines.join('<br>') || '<br>') + '</p>';
                })
                .join('');
        }

        function handleQuickNotePaste(event) {
            if (!quickNoteEditor) return;
            const clipboard = event.clipboardData || window.clipboardData;
            if (!clipboard) return;
            const html = clipboard.getData('text/html');
            const text = clipboard.getData('text/plain');
            if (!html && !text) return;
            event.preventDefault();
            const cleanHtml = html ? normalizeQuickPastedHtml(html) : quickPlainTextToHtml(text);
            document.execCommand('insertHTML', false, cleanHtml || '<p><br></p>');
            ensureQuickNoteHeadingStructure();
            renumberQuickNumberLines();
            syncQuickChecklistStateForSave();
            updateQuickTitlePlaceholder();
            saveQuickNoteFromEditor({ returnToList: false });
            updateQuickToolbarActiveStates();
        }

        function getQuickTitlePlaceholderHtml() {
            return '<h1 class="qn-title-placeholder is-empty" data-placeholder="Nota sin título"><br></h1>';
        }

        function updateQuickTitlePlaceholder() {
            const heading = quickNoteEditor?.querySelector('h1[data-placeholder]');
            if (!heading) return;
            const isEmpty = (heading.textContent || '').replace(/\u00a0/g, ' ').trim() === '';
            heading.classList.toggle('is-empty', isEmpty);
        }

        function ensureQuickNoteHeadingStructure() {
            if (!quickNoteEditor) return;
            const firstElement = quickNoteEditor.firstElementChild;
            if (!firstElement) {
                quickNoteEditor.innerHTML = `${getQuickTitlePlaceholderHtml()}<p><br></p>`;
                updateQuickTitlePlaceholder();
                return;
            }
            const tag = firstElement.tagName.toLowerCase();
            if (!['h1', 'h2', 'h3'].includes(tag)) {
                const heading = document.createElement('h1');
                heading.innerHTML = firstElement.innerHTML || firstElement.textContent || '<br>';
                quickNoteEditor.replaceChild(heading, firstElement);
            } else if (tag !== 'h1') {
                const heading = document.createElement('h1');
                heading.innerHTML = firstElement.innerHTML || firstElement.textContent || '<br>';
                quickNoteEditor.replaceChild(heading, firstElement);
            }
            const title = quickNoteEditor.firstElementChild;
            if (title?.tagName?.toLowerCase() === 'h1' && (title.textContent || '').trim() === '') {
                title.classList.add('qn-title-placeholder');
                title.dataset.placeholder = 'Nota sin título';
            }
            updateQuickTitlePlaceholder();
        }

        function htmlToPlainText(html) {
            const temp = document.createElement('div');
            temp.innerHTML = html || '';
            temp.querySelectorAll('.qn-title-placeholder.is-empty').forEach((titleEl) => {
                titleEl.textContent = '';
            });
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

        function syncQuickChecklistStateForSave() {
            if (!quickNoteEditor) return;
            quickNoteEditor.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                if (checkbox.checked) checkbox.setAttribute('checked', 'checked');
                else checkbox.removeAttribute('checked');
            });
        }

        function placeQuickEditorCaret(target) {
            if (!target) return;
            const editableTarget = target.matches?.('[contenteditable="false"]')
                ? target.parentElement
                : target;
            const range = document.createRange();
            const selection = window.getSelection();
            if (!selection) return;
            editableTarget.focus?.();
            range.selectNodeContents(editableTarget);
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
        }

        function getQuickEditorCurrentBlock() {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || !quickNoteEditor) return null;
            let node = selection.getRangeAt(0).startContainer;
            if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;
            if (!node || !quickNoteEditor.contains(node)) return null;
            return node.closest?.('.qn-checkline, .qn-numberline, h1, h2, h3, p, div') || null;
        }

        function isQuickEditorBlockEmpty(block) {
            if (!block) return true;
            const clone = block.cloneNode(true);
            clone.querySelectorAll('input, .qn-number-marker').forEach((node) => node.remove());
            return (clone.textContent || '').replace(/\u00a0/g, ' ').trim() === '';
        }

        function createQuickChecklistLine() {
            const line = document.createElement('div');
            line.className = 'qn-checkline';
            line.innerHTML = '<input type="checkbox" class="qn-checkbox" contenteditable="false"> <span><br></span>';
            return line;
        }

        function createQuickNumberLine(number = 1) {
            const line = document.createElement('div');
            line.className = 'qn-numberline';
            line.dataset.qnNumber = String(number);
            line.innerHTML = `<span class="qn-number-marker" contenteditable="false">${number}.</span><span class="qn-number-content"><br></span>`;
            return line;
        }

        function insertQuickEditorBlock(block, afterBlock = null) {
            if (!quickNoteEditor || !block) return;
            const reference = afterBlock && quickNoteEditor.contains(afterBlock) ? afterBlock : getQuickEditorCurrentBlock();
            if (reference && reference !== quickNoteEditor) {
                reference.insertAdjacentElement('afterend', block);
            } else {
                quickNoteEditor.appendChild(block);
            }
            const contentTarget = block.querySelector('.qn-number-content') || block.querySelector('span') || block;
            placeQuickEditorCaret(contentTarget);
        }

        function createPlainQuickEditorLine() {
            const line = document.createElement('p');
            line.innerHTML = '<br>';
            return line;
        }

        function insertQuickChecklistLine() {
            if (!quickNoteEditor) return;
            quickNoteEditor.focus();
            insertQuickEditorBlock(createQuickChecklistLine());
        }

        function insertQuickNumberedLine() {
            if (!quickNoteEditor) return;
            quickNoteEditor.focus();
            insertQuickEditorBlock(createQuickNumberLine(1));
        }

        function insertQuickDivider() {
            if (!quickNoteEditor) return;
            quickNoteEditor.focus();
            const divider = document.createElement('hr');
            divider.className = 'qn-divider';
            const nextLine = createPlainQuickEditorLine();
            const reference = getQuickEditorCurrentBlock();
            if (reference && reference !== quickNoteEditor) {
                reference.insertAdjacentElement('afterend', divider);
            } else {
                quickNoteEditor.appendChild(divider);
            }
            divider.insertAdjacentElement('afterend', nextLine);
            placeQuickEditorCaret(nextLine);
        }

        function insertQuickEmoji(emoji) {
            if (!quickNoteEditor || !emoji) return;
            quickNoteEditor.focus();
            document.execCommand('insertText', false, `${emoji} `);
        }

        function insertQuickImageAtCursor(dataUrl) {
            if (!quickNoteEditor || !dataUrl) return;
            quickNoteEditor.focus();
            const wrapper = document.createElement('div');
            wrapper.className = 'qn-image-wrap';
            wrapper.contentEditable = 'false';
            wrapper.innerHTML = `<img src="${dataUrl}" class="qn-inline-image" data-qn-scale="100" alt="imagen nota">`;
            const reference = getQuickEditorCurrentBlock();
            if (reference && reference !== quickNoteEditor) {
                reference.insertAdjacentElement('afterend', wrapper);
            } else {
                quickNoteEditor.appendChild(wrapper);
            }
            const nextLine = createPlainQuickEditorLine();
            wrapper.insertAdjacentElement('afterend', nextLine);
            placeQuickEditorCaret(nextLine);
        }

        function handleQuickImageInteraction(imageElement) {
            if (!imageElement) return;
            const raw = prompt('Imagen: escribe "del" para eliminar o porcentaje (20-250) para escalar.', String(imageElement.dataset.qnScale || '100'));
            if (raw === null) return;
            const value = raw.trim().toLowerCase();
            if (value === 'del') {
                imageElement.closest('.qn-image-wrap')?.remove();
                return;
            }
            const percentage = Number(value);
            if (!Number.isFinite(percentage)) return;
            const clamped = Math.max(20, Math.min(250, percentage));
            imageElement.dataset.qnScale = String(clamped);
            imageElement.style.width = `${clamped}%`;
            imageElement.style.maxWidth = '100%';
        }

        function renumberQuickNumberLines(startLine = null) {
            if (!quickNoteEditor) return;
            const lines = Array.from(quickNoteEditor.querySelectorAll('.qn-numberline'));
            let currentNumber = 1;
            lines.forEach((line) => {
                const previous = line.previousElementSibling;
                if (!previous?.classList?.contains('qn-numberline')) currentNumber = 1;
                line.dataset.qnNumber = String(currentNumber);
                const marker = line.querySelector('.qn-number-marker');
                if (marker) marker.textContent = `${currentNumber}.`;
                currentNumber += 1;
            });
            if (startLine) {
                const contentTarget = startLine.querySelector('.qn-number-content') || startLine;
                placeQuickEditorCaret(contentTarget);
            }
        }

        function handleQuickEditorEnter(event) {
            if (!quickNoteEditor || event.key !== 'Enter' || event.shiftKey) return;
            const currentBlock = getQuickEditorCurrentBlock();
            if (!currentBlock) return;

            if (currentBlock.classList?.contains('qn-checkline')) {
                event.preventDefault();
                if (isQuickEditorBlockEmpty(currentBlock)) {
                    const plainLine = createPlainQuickEditorLine();
                    currentBlock.replaceWith(plainLine);
                    placeQuickEditorCaret(plainLine);
                    return;
                }
                insertQuickEditorBlock(createQuickChecklistLine(), currentBlock);
                return;
            }

            if (currentBlock.classList?.contains('qn-numberline')) {
                event.preventDefault();
                if (isQuickEditorBlockEmpty(currentBlock)) {
                    const plainLine = createPlainQuickEditorLine();
                    currentBlock.replaceWith(plainLine);
                    renumberQuickNumberLines();
                    placeQuickEditorCaret(plainLine);
                    return;
                }
                const nextNumber = Number(currentBlock.dataset.qnNumber || 1) + 1;
                const nextLine = createQuickNumberLine(nextNumber);
                insertQuickEditorBlock(nextLine, currentBlock);
                renumberQuickNumberLines(nextLine);
            }
        }

        function getQuickNoteTitle(plain) {
            const firstLine = String(plain || '').split('\n').map((line) => line.trim()).find((line) => line !== '');
            if (!firstLine) return 'Nota sin título';
            return firstLine.replace(/^((\d+\.)|(\[\s\])|(\[[xX]\]))\s*/g, '').trim() || 'Nota sin título';
        }

        function getChecklistStats(plain) {
            const total = (plain.match(/\[\s\]/g) || []).length + (plain.match(/\[[xX]\]/g) || []).length;
            const done = (plain.match(/\[[xX]\]/g) || []).length;
            return { total, done };
        }

        function applyQuickEditorColor(color) {
            if (!quickNoteCanvas) return;
            Object.values(quickNotePalette).forEach((tone) => quickNoteCanvas.classList.remove(tone.editorClass));
            const selected = quickNotePalette[color] ? color : 'yellow';
            quickNoteCanvas.classList.add(quickNotePalette[selected].editorClass);
            activeQuickColor = selected;
            quickColorButtons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.qnColor === selected);
            });
        }

        function getQuickClientNameValue(value) {
            return String(value || '').trim();
        }

        function setActiveQuickLinkedClient(value) {
            activeQuickLinkedClient = getQuickClientNameValue(value);
            syncQuickClientLabel();
        }

        function syncQuickClientLabel() {
            if (!quickNoteClientSelectedLabel) return;
            quickNoteClientSelectedLabel.textContent = activeQuickLinkedClient || 'Sin cliente';
        }

        function normalizeClientNames(names) {
            const unique = new Map();
            (Array.isArray(names) ? names : []).forEach((item) => {
                const value = getQuickClientNameValue(item);
                if (!value) return;
                const key = value.toLocaleLowerCase('es');
                if (!unique.has(key)) unique.set(key, value);
            });
            return Array.from(unique.values()).sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));
        }

        function renderQuickNoteClientOptions(search = '') {
            if (!quickNoteClientOptions) return;
            const needle = String(search || '').trim().toLowerCase();
            const values = normalizeClientNames([...quickNoteClientsCache, activeQuickLinkedClient]);
            const filtered = values.filter((name) => !needle || name.toLowerCase().includes(needle));

            const all = [
                { value: '', label: 'Sin cliente' },
                ...filtered.map((name) => ({ value: name, label: name })),
            ];

            quickNoteClientOptions.innerHTML = all.map((item) => {
                const isActive = item.value === activeQuickLinkedClient;
                return `
                    <button type="button" data-qn-client-value="${escapeHtml(item.value)}" class="w-full rounded-lg px-2 py-1.5 text-left text-xs font-semibold transition-colors ${isActive ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-50'}">
                        <span class="inline-flex w-full items-center justify-between gap-2">
                            <span class="truncate">${escapeHtml(item.label)}</span>
                            ${isActive ? '<span class="text-xs">✓</span>' : ''}
                        </span>
                    </button>
                `;
            }).join('');

            quickNoteClientOptions.querySelectorAll('[data-qn-client-value]').forEach((button) => {
                button.addEventListener('click', () => {
                    setActiveQuickLinkedClient(button.dataset.qnClientValue || '');
                    syncQuickClientLabel();
                    renderQuickNoteClientOptions(quickNoteClientSearch?.value || '');
                    closeQuickClientDropdown();
                });
            });
        }

        async function loadQuickNoteClients(force = false) {
            if (quickNoteClientsCache.length && !force) return;
            try {
                const res = await fetch('/api/clientes', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const json = await res.json().catch(() => ({}));
                const clients = Array.isArray(json?.data) ? json.data : [];
                quickNoteClientsCache = normalizeClientNames(clients.map((client) => client?.empresa || ''));
            } catch (_) {}
        }

        function openQuickClientDropdown() {
            quickNoteClientDropdown?.classList.remove('hidden');
            if (quickNoteClientSearch) {
                quickNoteClientSearch.value = '';
                renderQuickNoteClientOptions('');
                quickNoteClientSearch.focus();
            }
        }

        function closeQuickClientDropdown() {
            quickNoteClientDropdown?.classList.add('hidden');
        }

        async function openQuickClientPopover() {
            await loadQuickNoteClients();
            syncQuickClientLabel();
            renderQuickNoteClientOptions('');
            quickNoteClientPopover?.classList.remove('hidden');
            openQuickClientDropdown();
        }

        function closeQuickClientPopover() {
            closeQuickClientDropdown();
            quickNoteClientPopover?.classList.add('hidden');
        }

        function closeQuickColorPopover() {
            quickNoteColorPopover?.classList.add('hidden');
        }

        function setQuickNotesCount() {
            if (!quickNotesCount) return;
            const count = quickNotes.length;
            quickNotesCount.textContent = `${count} ${count === 1 ? 'nota' : 'notas'}`;
        }

        function renderQuickNotesList() {
            if (!quickNotesList) return;
            setQuickNotesCount();
            if (!quickNotes.length) {
                quickNotesList.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-slate-50/60 py-12 text-center text-slate-400"><div class="text-base font-semibold">No tienes notas aún</div></div>';
                return;
            }

            const sorted = [...quickNotes].sort((a, b) => Number(b.updatedAt || 0) - Number(a.updatedAt || 0));
            quickNotesList.innerHTML = sorted.map((note) => {
                const palette = quickNotePalette[note.color] || quickNotePalette.yellow;
                const plain = String(note.plainText || '');
                const title = escapeHtml(note.title || 'Nota sin título');
                // Extraer cuerpo desde HTML para no mezclar titulo y contenido
                const bodyDiv = document.createElement('div');
                bodyDiv.innerHTML = note.html || '';
                const titleElQn = bodyDiv.querySelector('h1,h2,h3,[data-placeholder]');
                if (titleElQn) titleElQn.remove();
                bodyDiv.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
                    cb.replaceWith(document.createTextNode(cb.checked ? '☑ ' : '□ '));
                });
                const allBodyLinesQn = Array.from(bodyDiv.querySelectorAll('p,div,li'))
                    .map((el) => el.textContent?.replace(/\u00a0/g, ' ').trim())
                    .filter(Boolean);
                const bodyLinesQn = allBodyLinesQn.slice(0, 2);
                if (allBodyLinesQn.length > 2 && bodyLinesQn.length > 0) {
                    bodyLinesQn[bodyLinesQn.length - 1] = bodyLinesQn[bodyLinesQn.length - 1].replace(/\s*$/, '') + '...';
                }
                const body = escapeHtml(bodyLinesQn.join('\n')).replace(/\n/g, '<br>');
                const stats = getChecklistStats(plain);
                const progress = stats.total > 0 ? `${stats.done}/${stats.total}` : '';
                const linkedClient = getQuickClientNameValue(note.linkedClient || '');
                const linkedClientMarkup = linkedClient
                    ? `<div class="qn-card-client"><span class="qn-card-client-badge"><span>${escapeHtml(linkedClient)}</span></span></div>`
                    : '';
                return `
                        <div class="qn-card-wrapper">
                            <button type="button" data-qn-open="${escapeHtml(note.id)}" class="qn-card w-full text-left" style="background:${palette.cardBg}; border-color:${palette.cardBorder}; padding-right:2.2rem;">
                                <div class="qn-card-title">${title}</div>
                                ${body ? `<div class="qn-card-body">${body}</div>` : ''}
                                ${linkedClientMarkup}
                                <div class="qn-card-meta">
                                    <span>${formatQuickDate(note.updatedAt)}</span>
                                    <span>${progress}</span>
                                </div>
                            </button>
                            <button type="button" data-qn-delete="${escapeHtml(note.id)}" class="qn-card-delete-btn" title="Eliminar nota">
                                <svg style="width:0.85rem;height:0.85rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6V4h8v2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6l-1 14H6L5 6"/></svg>
                            </button>
                        </div>
                `;
            }).join('');

            quickNotesList.querySelectorAll('[data-qn-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    const note = getQuickNoteById(button.getAttribute('data-qn-open'));
                    if (!note) return;
                    openQuickNoteEditor(note);
                });
            });

                quickNotesList.querySelectorAll('[data-qn-delete]').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const id = btn.getAttribute('data-qn-delete');
                        quickNotes = quickNotes.filter((item) => String(item.id) !== String(id));
                        persistQuickNotes();
                        renderQuickNotesList();
                    });
                });
        }

        function isQuickNotesEditorVisible() {
            return !!quickNotesEditorView && !quickNotesEditorView.classList.contains('hidden');
        }

        function showQuickNotesListView() {
            quickNotesListView?.classList.remove('hidden');
            quickNotesEditorView?.classList.add('hidden');
            activeQuickNoteId = null;
        }

        function showQuickNotesEditorView() {
            quickNotesListView?.classList.add('hidden');
            quickNotesEditorView?.classList.remove('hidden');
        }

        function openQuickNotesPanel() {
            closeTdahPanel();
            if (!quickNotesPanel) return;
            quickNotesPanel.classList.remove('hidden');
            renderQuickNotesList();
            showQuickNotesListView();
            syncProductivityToggleIcon();
        }

        function closeQuickNotesPanel() {
            if (isQuickNotesEditorVisible()) {
                saveQuickNoteFromEditor({ returnToList: false });
            }
            closeQuickClientPopover();
            closeQuickColorPopover();
            quickNotesPanel?.classList.add('hidden');
            showQuickNotesListView();
            syncProductivityToggleIcon();
        }

        function openQuickNoteEditor(note = null) {
            if (quickNotesPanel?.classList.contains('hidden')) {
                quickNotesPanel.classList.remove('hidden');
            }
            const editing = note || null;
            activeQuickNoteId = editing ? String(editing.id) : null;
            const html = editing?.html || '';
            const color = editing?.color || 'yellow';
            setActiveQuickLinkedClient(editing?.linkedClient || '');
            if (quickNoteEditor) {
                quickNoteEditor.innerHTML = html || '';
                if (!html) {
                    quickNoteEditor.innerHTML = `${getQuickTitlePlaceholderHtml()}<p><br></p>`;
                }
                ensureQuickNoteHeadingStructure();
                updateQuickTitlePlaceholder();
                quickNoteEditor.focus();
            }
            applyQuickEditorColor(color);
            closeQuickClientPopover();
            closeQuickColorPopover();
            showQuickNotesEditorView();
        }

        function saveQuickNoteFromEditor({ returnToList = true } = {}) {
            if (!quickNoteEditor) return;
            ensureQuickNoteHeadingStructure();
            syncQuickChecklistStateForSave();
            const html = normalizeQuickNoteHtml(quickNoteEditor.innerHTML || '');
            const plain = htmlToPlainText(html);

            if (!plain) {
                if (activeQuickNoteId) {
                    quickNotes = quickNotes.filter((item) => String(item.id) !== String(activeQuickNoteId));
                    persistQuickNotes();
                    renderQuickNotesList();
                }
                if (returnToList) showQuickNotesListView();
                return;
            }

            const timestamp = Date.now();
            const payload = {
                id: activeQuickNoteId || `qn_${timestamp}_${Math.random().toString(36).slice(2, 6)}`,
                title: getQuickNoteTitle(plain),
                html,
                plainText: plain,
                color: activeQuickColor,
                linkedClient: getQuickClientNameValue(activeQuickLinkedClient),
                updatedAt: timestamp,
                createdAt: activeQuickNoteId ? (getQuickNoteById(activeQuickNoteId)?.createdAt || timestamp) : timestamp,
            };

            const existingIndex = quickNotes.findIndex((item) => String(item.id) === String(payload.id));
            if (existingIndex >= 0) {
                quickNotes[existingIndex] = payload;
            } else {
                quickNotes.unshift(payload);
            }
            persistQuickNotes();
            renderQuickNotesList();
            if (returnToList) showQuickNotesListView();
        }

        function deleteActiveQuickNote() {
            if (activeQuickNoteId) {
                quickNotes = quickNotes.filter((item) => String(item.id) !== String(activeQuickNoteId));
                persistQuickNotes();
                renderQuickNotesList();
            }
            setActiveQuickLinkedClient('');
            closeQuickClientPopover();
            showQuickNotesListView();
        }

        function isSelectionInsideQuickNoteEditor() {
            if (!quickNoteEditor) return false;
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return false;
            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            if (!container) return false;
            const node = container.nodeType === Node.TEXT_NODE ? container.parentNode : container;
            return !!(node && quickNoteEditor.contains(node));
        }

        function updateQuickToolbarActiveStates() {
            if (!quickToolbarButtons.length) return;
            const insideEditor = isSelectionInsideQuickNoteEditor();
            const boldState = insideEditor ? document.queryCommandState('bold') : false;
            const italicState = insideEditor ? document.queryCommandState('italic') : false;
            const strikeState = insideEditor ? document.queryCommandState('strikeThrough') : false;
            const currentBlock = insideEditor ? getQuickEditorCurrentBlock() : null;
            const inChecklist = !!(currentBlock && currentBlock.classList?.contains('qn-checkline'));
            const inNumberList = !!(currentBlock && currentBlock.classList?.contains('qn-numberline'));

            quickToolbarButtons.forEach((button) => {
                const cmd = button.dataset.qnCmd || '';
                let active = false;
                if (cmd === 'bold') active = !!boldState;
                if (cmd === 'italic') active = !!italicState;
                if (cmd === 'strikeThrough') active = !!strikeState;
                if (cmd === 'checkline') active = inChecklist;
                if (cmd === 'numberline') active = inNumberList;
                button.classList.toggle('qn-tool-btn-active', active);
            });
        }

        function execQuickEditorCommand(command) {
            if (!quickNoteEditor) return;
            quickNoteEditor.focus();
            if (command === 'checkline') {
                insertQuickChecklistLine();
                updateQuickToolbarActiveStates();
                return;
            }
            if (command === 'numberline') {
                insertQuickNumberedLine();
                updateQuickToolbarActiveStates();
                return;
            }
            if (command === 'divider') {
                insertQuickDivider();
                updateQuickToolbarActiveStates();
                return;
            }
            if (command === 'title') {
                document.execCommand('formatBlock', false, 'h1');
                ensureQuickNoteHeadingStructure();
                updateQuickToolbarActiveStates();
                return;
            }
            document.execCommand(command, false, null);
            updateQuickToolbarActiveStates();
        }

        function isTdahPanelOpen() {
            return !!panel && !panel.classList.contains('hidden');
        }

        function isQuickNotesPanelOpen() {
            return !!quickNotesPanel && !quickNotesPanel.classList.contains('hidden');
        }

        function syncProductivityToggleIcon() {
            const hasOpenSurface = isProductivityMenuOpen || isQuickNotesPanelOpen() || isTdahPanelOpen();
            productivityPlus?.classList.toggle('hidden', hasOpenSurface);
            productivityCloseIcon?.classList.toggle('hidden', !hasOpenSurface);
            document.body.classList.toggle('infocus-productivity-open', hasOpenSurface);
        }

        function setProductivityMenuState(open) {
            isProductivityMenuOpen = !!open;
            productivityActions?.classList.toggle('hidden', !isProductivityMenuOpen);
            syncProductivityToggleIcon();
        }

        productivityToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            if (isProductivityMenuOpen || isQuickNotesPanelOpen() || isTdahPanelOpen()) {
                setProductivityMenuState(false);
                closeQuickNotesPanel();
                closeTdahPanel();
                syncProductivityToggleIcon();
                return;
            }
            setProductivityMenuState(true);
        });
        productivityOpenTdah?.addEventListener('click', (event) => {
            event.stopPropagation();
            setProductivityMenuState(false);
            closeQuickNotesPanel();
            openTdahPanel();
            syncProductivityToggleIcon();
        });
        productivityOpenNotes?.addEventListener('click', (event) => {
            event.stopPropagation();
            setProductivityMenuState(false);
            openQuickNotesPanel();
            syncProductivityToggleIcon();
        });

        quickNotesClose?.addEventListener('click', closeQuickNotesPanel);
        quickNotesNew?.addEventListener('click', () => openQuickNoteEditor(null));
        quickNoteBack?.addEventListener('click', saveQuickNoteFromEditor);
        quickNoteDelete?.addEventListener('click', deleteActiveQuickNote);
        quickNoteClientToggle?.addEventListener('click', async (event) => {
            event.stopPropagation();
            if (!quickNoteClientPopover?.classList.contains('hidden')) {
                closeQuickClientPopover();
                return;
            }
            await openQuickClientPopover();
        });
        quickNoteClientSelectTrigger?.addEventListener('click', (event) => {
            event.stopPropagation();
            if (quickNoteClientDropdown?.classList.contains('hidden')) {
                openQuickClientDropdown();
            } else {
                closeQuickClientDropdown();
            }
        });
        quickNoteClientSearch?.addEventListener('input', () => {
            renderQuickNoteClientOptions(quickNoteClientSearch.value || '');
        });

        quickToolbarButtons.forEach((button) => {
            button.addEventListener('click', () => {
                execQuickEditorCommand(button.dataset.qnCmd || '');
                updateQuickToolbarActiveStates();
            });
        });
        quickColorButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyQuickEditorColor(button.dataset.qnColor || 'yellow');
                closeQuickColorPopover();
            });
        });
        quickNoteEmojiToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            closeQuickColorPopover();
            quickNoteEmojiPopover?.classList.toggle('hidden');
        });
        quickNoteEmojiButtons.forEach((button) => {
            button.addEventListener('click', () => {
                insertQuickEmoji(button.dataset.qnEmoji || '');
                quickNoteEmojiPopover?.classList.add('hidden');
            });
        });
        quickNoteImageBtn?.addEventListener('click', () => quickNoteImageInput?.click());
        quickNoteColorToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            quickNoteEmojiPopover?.classList.add('hidden');
            quickNoteColorPopover?.classList.toggle('hidden');
        });
        quickNoteImageInput?.addEventListener('change', (event) => {
            const file = event.target?.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => insertQuickImageAtCursor(String(reader.result || ''));
            reader.readAsDataURL(file);
            quickNoteImageInput.value = '';
        });
        quickNoteEditor?.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLImageElement && target.classList.contains('qn-inline-image')) {
                event.preventDefault();
                handleQuickImageInteraction(target);
            }
        });
        quickNoteEditor?.addEventListener('keydown', handleQuickEditorEnter);
        quickNoteEditor?.addEventListener('paste', handleQuickNotePaste);
        quickNoteEditor?.addEventListener('keyup', updateQuickToolbarActiveStates);
        quickNoteEditor?.addEventListener('mouseup', updateQuickToolbarActiveStates);
        quickNoteEditor?.addEventListener('focus', updateQuickToolbarActiveStates);
        quickNoteEditor?.addEventListener('input', () => {
            updateQuickTitlePlaceholder();
            updateQuickToolbarActiveStates();
        });

        document.addEventListener('selectionchange', () => {
            if (!isQuickNotesEditorVisible()) return;
            updateQuickToolbarActiveStates();
        });

        document.addEventListener('pointerdown', (event) => {
            const target = event.target;
            if (!target) return;
            if (isProductivityMenuOpen && productivityLauncher && !productivityLauncher.contains(target)) {
                setProductivityMenuState(false);
            }
            if (!quickNotesPanel?.classList.contains('hidden')) {
                const insideNotes = quickNotesPanel.contains(target) || productivityLauncher?.contains(target);
                if (!insideNotes) closeQuickNotesPanel();
                if (insideNotes && quickNoteClientPopover && !quickNoteClientPopover.classList.contains('hidden')) {
                    const insideClientPopup = quickNoteClientPopover.contains(target) || quickNoteClientToggle?.contains(target);
                    if (!insideClientPopup) closeQuickClientPopover();
                }
                if (quickNoteEmojiPopover && !quickNoteEmojiPopover.classList.contains('hidden')) {
                    const insideEmojiPopup = quickNoteEmojiPopover.contains(target) || quickNoteEmojiToggle?.contains(target);
                    if (!insideEmojiPopup) quickNoteEmojiPopover.classList.add('hidden');
                }
                if (quickNoteColorPopover && !quickNoteColorPopover.classList.contains('hidden')) {
                    const insideColorPopup = quickNoteColorPopover.contains(target) || quickNoteColorToggle?.contains(target);
                    if (!insideColorPopup) closeQuickColorPopover();
                }
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isQuickNotesEditorVisible()) {
                saveQuickNoteFromEditor();
            } else if (event.key === 'Escape' && !quickNotesPanel?.classList.contains('hidden')) {
                closeQuickNotesPanel();
            }
        });

        quickNotes = loadQuickNotes();
        applyQuickEditorColor('yellow');
        updateQuickToolbarActiveStates();
        renderQuickNotesList();

        const oneThingInput = document.getElementById('tdah-one-thing');
        const oneThingCheck = document.getElementById('tdah-one-thing-check');
        const oneThingPraise = document.getElementById('tdah-one-thing-praise');
        const dropdown = document.getElementById('tdah-tasks-dropdown');
        const tasksList = document.getElementById('tdah-tasks-list');
        const pomodoroTaskLabel = document.getElementById('tdah-pomodoro-task');
        const pomodoroOpenFullscreenBtn = document.getElementById('tdah-open-fullscreen');
        const pomodoroOpenPipBtn = document.getElementById('tdah-open-pip');
        const breakLabel = document.getElementById('tdah-break-label');
        const breakOptionButtons = Array.from(document.querySelectorAll('.tdah-break-btn'));
        const workOptionButtons = Array.from(document.querySelectorAll('.tdah-work-btn'));
        const timerDisplay = document.getElementById('tdah-timer-display');
        const timerStart = document.getElementById('tdah-timer-start');
        const timerReset = document.getElementById('tdah-timer-reset');
        const timerProgress = document.getElementById('tdah-timer-progress');
        const pomodoroFullscreen = document.getElementById('tdah-pomodoro-fullscreen');
        const pomodoroCloseBtn = document.getElementById('tdah-pomodoro-close');
        const pomodoroFsToggleBtn = document.getElementById('tdah-pomodoro-fs-toggle');
        const pomodoroToPipBtn = document.getElementById('tdah-pomodoro-pip-btn');
        const pomodoroPhase = document.getElementById('tdah-pomodoro-phase');
        const pomodoroFsDisplay = document.getElementById('tdah-pomodoro-fs-display');
        const pomodoroFsStart = document.getElementById('tdah-pomodoro-fs-start');
        const pomodoroFsReset = document.getElementById('tdah-pomodoro-fs-reset');
        const pomodoroFsSave = document.getElementById('tdah-pomodoro-fs-save');
        const pomodoroFsDelete = document.getElementById('tdah-pomodoro-fs-delete');
        const pomodoroTaskTitle = document.getElementById('tdah-pomodoro-task-title');
        const pomodoroProjectTitle = document.getElementById('tdah-pomodoro-project-title');
        const nativePipVideo = document.getElementById('tdah-native-pip-video');
        const nativePipCanvas = document.getElementById('tdah-native-pip-canvas');
        const pomodoroMiniPip = document.getElementById('tdah-pomodoro-mini-pip');
        const pomodoroMiniClose = document.getElementById('tdah-pomodoro-mini-close');
        const pomodoroMiniStart = document.getElementById('tdah-pomodoro-mini-start');
        const pomodoroMiniReset = document.getElementById('tdah-pomodoro-mini-reset');
        const pomodoroMiniDisplay = document.getElementById('tdah-pomodoro-mini-display');
        const pomodoroMiniPhase = document.getElementById('tdah-pomodoro-mini-phase');
        const pomodoroMiniTask = document.getElementById('tdah-pomodoro-mini-task');
        const pomodoroMiniProject = document.getElementById('tdah-pomodoro-mini-project');
        const pomodoroTimerConflictModal = document.getElementById('tdah-pomodoro-timer-conflict-modal');

        const ONE_THING_TASK_KEY = 'tdah_one_thing_task_v1';
        const POMODORO_STATE_KEY = 'tdah_pomodoro_state_v2';
        const GLOBAL_TIMER_STATE_KEY = 'infocus_global_timer_state_v1';
        const TIMER_HISTORY_PREFIX = 'project_timer_history_v2_';
        const DEFAULT_BREAK_MINUTES = 15;
        const BREAK_MINUTES_OPTIONS = [5, 15, 30];
        const playIcon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        const pauseIcon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        const playIconLarge = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        const pauseIconLarge = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

        let tdahTasksCache = [];
        let tdahSelectedTask = null;
        let timerInterval = null;
        let pomodoroTransitioning = false;
        let nativePipStream = null;
        let nativePipRenderInterval = null;
        let nativePipWatchdogInterval = null;
        let nativePipVideoTrack = null;
        let nativePipLastFrameAt = 0;
        let nativePipRecovering = false;
        let nativePipAudioContext = null;
        let nativePipAudioSource = null;
        let nativePipAudioTrack = null;
        let nativePipAudioPrimed = false;
        let nativePipOpening = false;
        let nativePipLastErrorAt = 0;
        let syncingNativePipPlayback = false;
        let nativePipLastPlaybackState = null;
        let pomodoroTimerConflictResolver = null;
        let syncingOneThingCheck = false;
        let pomodoroState = loadPomodoroState();

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[char] || char));
        }

        function createPomodoroState(workMinutes = 25) {
            return {
                workMinutes,
                breakMinutes: DEFAULT_BREAK_MINUTES,
                phase: 'work',
                remainingSeconds: workMinutes * 60,
                isRunning: false,
                endsAt: null,
                backendTimerActive: false,
                fullscreenVisible: false,
                activeProjectId: '',
                activeProjectTitle: '',
                activeTaskId: '',
                activeTaskName: '',
                loggedWorkLogs: 0,
            };
        }

        function loadPomodoroState() {
            const baseState = createPomodoroState();
            try {
                const parsed = JSON.parse(localStorage.getItem(POMODORO_STATE_KEY) || '{}');
                const workMinutes = [25, 30, 60].includes(Number(parsed?.workMinutes)) ? Number(parsed.workMinutes) : 25;
                const breakMinutes = BREAK_MINUTES_OPTIONS.includes(Number(parsed?.breakMinutes)) ? Number(parsed.breakMinutes) : DEFAULT_BREAK_MINUTES;
                const phase = parsed?.phase === 'break' ? 'break' : 'work';
                const phaseTotal = (phase === 'break' ? breakMinutes : workMinutes) * 60;
                return {
                    ...baseState,
                    workMinutes,
                    breakMinutes,
                    phase,
                    remainingSeconds: Math.max(0, Number(parsed?.remainingSeconds || phaseTotal)) || phaseTotal,
                    isRunning: !!parsed?.isRunning,
                    endsAt: Number(parsed?.endsAt || 0) || null,
                    backendTimerActive: !!parsed?.backendTimerActive,
                    fullscreenVisible: !!parsed?.fullscreenVisible,
                    activeProjectId: String(parsed?.activeProjectId || ''),
                    activeProjectTitle: String(parsed?.activeProjectTitle || ''),
                    activeTaskId: String(parsed?.activeTaskId || ''),
                    activeTaskName: String(parsed?.activeTaskName || ''),
                    loggedWorkLogs: Math.max(0, Number(parsed?.loggedWorkLogs || 0)),
                };
            } catch (_) {
                return baseState;
            }
        }

        function resetPomodoroSessionState() {
            const workMinutes = pomodoroState.workMinutes;
            const breakMinutes = pomodoroState.breakMinutes;
            pomodoroState = createPomodoroState(workMinutes);
            pomodoroState.breakMinutes = breakMinutes;
        }

        function hasPomodoroSession(state = pomodoroState) {
            if (!state || typeof state !== 'object') return false;
            return !!(state.isRunning || state.activeTaskId || state.activeProjectId || Number(state.loggedWorkLogs || 0) > 0);
        }

        async function deletePomodoroLoggedSegments() {
            const projectId = String(pomodoroState.activeProjectId || '');
            if (!projectId) return true;

            let deleteCount = Math.max(0, Number(pomodoroState.loggedWorkLogs || 0));
            if (pomodoroState.phase === 'work' && pomodoroState.backendTimerActive) {
                deleteCount += 1;
            }

            try {
                for (let index = 0; index < deleteCount; index += 1) {
                    await fetch('/api/proyectos/timer/eliminar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ id: projectId }),
                    });
                }
                return true;
            } catch (_) {
                notify('No se pudo eliminar el tiempo del Pomodoro.', 'error');
                return false;
            }
        }

        async function savePomodoroSession() {
            clearInterval(timerInterval);
            if (pomodoroState.phase === 'work' && pomodoroState.backendTimerActive) {
                const stopped = await syncPomodoroBackend('stop', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId }, true);
                if (stopped) pomodoroState.loggedWorkLogs = Math.max(0, Number(pomodoroState.loggedWorkLogs || 0)) + 1;
            }
            closePomodoroFullscreen();
            closeNativePomodoroPip();
            resetPomodoroSessionState();
            renderPomodoro();
            notify('Tiempo de Pomodoro guardado.');
        }

        async function deletePomodoroSession() {
            clearInterval(timerInterval);
            const deleted = await deletePomodoroLoggedSegments();
            if (!deleted) return;
            closePomodoroFullscreen();
            closeNativePomodoroPip();
            resetPomodoroSessionState();
            renderPomodoro();
            notify('Tiempo de Pomodoro eliminado.');
        }

        function persistPomodoroState() {
            localStorage.setItem(POMODORO_STATE_KEY, JSON.stringify(pomodoroState));
            window.dispatchEvent(new CustomEvent('tdah-pomodoro-state-updated', { detail: { ...pomodoroState } }));
        }

        function closeExistingTimerFullscreenPanels() {
            const timerPanel = document.getElementById('timerFullscreenPanel');
            const globalTimerPanel = document.getElementById('globalTimerFullscreenPanel');
            timerPanel?.classList.add('hidden');
            globalTimerPanel?.classList.add('hidden');
            if (document.fullscreenElement && (document.fullscreenElement === timerPanel || document.fullscreenElement === globalTimerPanel) && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
        }

        function getActiveTaskTimerState() {
            try {
                const parsed = JSON.parse(localStorage.getItem(GLOBAL_TIMER_STATE_KEY) || 'null');
                return parsed && typeof parsed === 'object' ? parsed : null;
            } catch (_) {
                return null;
            }
        }

        function getActiveTaskTimerDisplayedSeconds(state) {
            if (!state) return 0;
            const base = Math.max(0, Number(state.currentSeconds || 0));
            if (!state.isRunning) return base;
            const elapsed = Math.max(0, Math.floor((Date.now() - Number(state.syncedAt || Date.now())) / 1000));
            return base + elapsed;
        }

        function clearActiveTaskTimerState() {
            try {
                localStorage.removeItem(GLOBAL_TIMER_STATE_KEY);
            } catch (_) {}
            window.dispatchEvent(new Event('infocus-global-timer-updated'));
        }

        function askPomodoroTimerConflictResolution() {
            if (!pomodoroTimerConflictModal) return Promise.resolve('cancel');
            pomodoroTimerConflictModal.classList.remove('hidden');
            return new Promise((resolve) => {
                pomodoroTimerConflictResolver = resolve;
            });
        }

        window.closeTdahPomodoroTimerConflictModal = function(action = 'cancel') {
            pomodoroTimerConflictModal?.classList.add('hidden');
            if (pomodoroTimerConflictResolver) pomodoroTimerConflictResolver(action);
            pomodoroTimerConflictResolver = null;
        };

        async function saveActiveTaskTimerBeforePomodoro(state) {
            if (!state?.projectId) return true;
            try {
                if (state.isRunning) {
                    await fetch('/api/proyectos/timer', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            id: state.projectId,
                            action: 'stop',
                            tarea_id: state.taskId || null,
                        }),
                    });
                }

                let entries = [];
                try {
                    const parsed = JSON.parse(localStorage.getItem(TIMER_HISTORY_PREFIX + state.projectId) || '[]');
                    entries = Array.isArray(parsed) ? parsed : [];
                } catch (_) {
                    entries = [];
                }

                entries.push({
                    time: formatTimer(getActiveTaskTimerDisplayedSeconds(state)),
                    day: new Date().toLocaleDateString('es-ES'),
                    saved_by: 'Usuario',
                    task_name: String(state.taskName || ''),
                });

                localStorage.setItem(TIMER_HISTORY_PREFIX + state.projectId, JSON.stringify(entries));
                clearActiveTaskTimerState();
                closeExistingTimerFullscreenPanels();
                return true;
            } catch (error) {
                notify('No se pudo guardar el temporizador actual.', 'error');
                return false;
            }
        }

        async function deleteActiveTaskTimerBeforePomodoro(state) {
            if (!state?.projectId) return true;
            try {
                await fetch('/api/proyectos/timer/eliminar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ id: String(state.projectId) }),
                });
                clearActiveTaskTimerState();
                closeExistingTimerFullscreenPanels();
                return true;
            } catch (error) {
                notify('No se pudo eliminar el temporizador actual.', 'error');
                return false;
            }
        }

        async function ensurePomodoroCanStart() {
            const activeTaskTimer = getActiveTaskTimerState();
            if (!activeTaskTimer?.projectId || !activeTaskTimer.isRunning) return true;
            const resolution = await askPomodoroTimerConflictResolution();
            if (resolution === 'save') return saveActiveTaskTimerBeforePomodoro(activeTaskTimer);
            if (resolution === 'delete') return deleteActiveTaskTimerBeforePomodoro(activeTaskTimer);
            return false;
        }

        function getStoredSelectedTask() {
            try {
                const parsed = JSON.parse(localStorage.getItem(ONE_THING_TASK_KEY) || 'null');
                if (!parsed?.projectId || !parsed?.taskId) return null;
                return {
                    projectId: String(parsed.projectId),
                    projectTitle: String(parsed.projectTitle || ''),
                    taskId: String(parsed.taskId),
                    taskName: String(parsed.taskName || ''),
                    done: !!parsed.done,
                };
            } catch (_) {
                return null;
            }
        }

        function persistSelectedTask() {
            if (tdahSelectedTask) localStorage.setItem(ONE_THING_TASK_KEY, JSON.stringify(tdahSelectedTask));
            else localStorage.removeItem(ONE_THING_TASK_KEY);
        }

        function buildTaskCatalog(projects) {
            return projects.flatMap((project) => {
                const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
                return tasks.map((task) => ({
                    projectId: String(project.id || ''),
                    projectTitle: String(project.titulo || 'Proyecto sin nombre'),
                    taskId: String(task.id || ''),
                    taskName: String(task.texto || task.titulo || 'Tarea sin título'),
                    done: !!task.done,
                }));
            }).sort((a, b) => {
                if (a.done !== b.done) return a.done ? 1 : -1;
                return `${a.projectTitle} ${a.taskName}`.localeCompare(`${b.projectTitle} ${b.taskName}`, 'es', { sensitivity: 'base' });
            });
        }

        function findTaskByIds(projectId, taskId) {
            return tdahTasksCache.find((item) => String(item.projectId) === String(projectId) && String(item.taskId) === String(taskId)) || null;
        }

        function applySelectedTask(task, options = {}) {
            const { syncInput = true, persist = true } = options;
            tdahSelectedTask = task ? { ...task } : null;
            if (syncInput && oneThingInput) oneThingInput.value = tdahSelectedTask ? tdahSelectedTask.taskName : '';
            if (persist) persistSelectedTask();
            if (oneThingInput) localStorage.setItem('tdah_one_thing', oneThingInput.value || '');
            syncOneThingTaskState();
            renderPomodoro();
        }

        function setOneThingCompletedState(checked) {
            if (oneThingCheck) oneThingCheck.checked = !!checked;
            localStorage.setItem('tdah_one_thing_done', checked ? 'true' : 'false');
            if (!oneThingInput) return;
            if (checked) {
                oneThingInput.classList.add('line-through', 'opacity-50');
                if (oneThingPraise) oneThingPraise.classList.remove('hidden');
            } else {
                oneThingInput.classList.remove('line-through', 'opacity-50');
                if (oneThingPraise) oneThingPraise.classList.add('hidden');
            }
        }

        function syncOneThingTaskState() {
            if (!tdahSelectedTask) return;
            syncingOneThingCheck = true;
            setOneThingCompletedState(!!tdahSelectedTask.done);
            setTimeout(() => { syncingOneThingCheck = false; }, 0);
        }

        async function syncSelectedTaskCompletionFromOneThing(checked) {
            if (!tdahSelectedTask?.projectId || !tdahSelectedTask?.taskId) return;
            if (!!tdahSelectedTask.done === !!checked) return;
            try {
                const response = await fetch('/api/proyectos/tareas/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                    },
                    body: JSON.stringify({ id: tdahSelectedTask.projectId, tarea_id: tdahSelectedTask.taskId }),
                });
                const data = await response.json().catch(() => ({}));
                if (!data.ok) throw new Error('toggle_task_failed');
                await loadActiveProjects(true);
            } catch (_) {
                notify('No se pudo sincronizar el check con la tarea seleccionada.', 'error');
            }
        }

        function renderTaskSuggestions(query = '') {
            if (!tasksList) return;
            const needle = String(query || '').trim().toLowerCase();
            const matches = tdahTasksCache.filter((task) => !needle || `${task.taskName} ${task.projectTitle}`.toLowerCase().includes(needle)).slice(0, 60);

            if (!matches.length) {
                tasksList.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No hay tareas que coincidan.</div>';
                return;
            }

            tasksList.innerHTML = matches.map((task) => `
                <button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-b-0 transition-colors" data-project-id="${escapeHtml(task.projectId)}" data-task-id="${escapeHtml(task.taskId)}">
                    <div class="text-xs font-bold text-slate-800 truncate">${escapeHtml(task.taskName)}</div>
                    <div class="mt-0.5 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                        <span class="truncate">${escapeHtml(task.projectTitle)}</span>
                        <span class="rounded-full px-1.5 py-0.5 font-bold ${task.done ? 'bg-slate-100 text-slate-500' : 'bg-lime-50 text-lime-700'}">${task.done ? 'Hecha' : 'Activa'}</span>
                    </div>
                </button>
            `).join('');

            tasksList.querySelectorAll('button[data-task-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const task = findTaskByIds(button.dataset.projectId, button.dataset.taskId);
                    if (!task) return;
                    applySelectedTask(task);
                    dropdown?.classList.add('hidden');
                });
            });
        }

        async function loadActiveProjects(force = false) {
            if (!force && tdahTasksCache.length) {
                renderTaskSuggestions(oneThingInput?.value || '');
                return;
            }

            try {
                const res = await fetch('/api/proyectos', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('API Error');

                const json = await res.json();
                const projects = (json.data || []).filter((project) => project.etapa !== 'Cerrado' && project.etapa !== 'Cancelado');
                tdahTasksCache = buildTaskCatalog(projects);

                const storedTask = getStoredSelectedTask();
                if (storedTask) applySelectedTask(findTaskByIds(storedTask.projectId, storedTask.taskId) || storedTask, { syncInput: true, persist: true });
                renderTaskSuggestions(oneThingInput?.value || '');
            } catch (error) {
                console.error('TDAH Toolkit: Error loading projects', error);
                if (tasksList) tasksList.innerHTML = '<div class="px-3 py-2 text-xs text-red-400">Error cargando tareas.</div>';
            }
        }

        function formatPomodoroTime(totalSeconds) {
            const safeSeconds = Math.max(0, Number(totalSeconds || 0));
            const minutes = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
            const seconds = Math.floor(safeSeconds % 60).toString().padStart(2, '0');
            return `${minutes}:${seconds}`;
        }

        function getPhaseDurationSeconds() {
            return (pomodoroState.phase === 'break' ? pomodoroState.breakMinutes : pomodoroState.workMinutes) * 60;
        }

        function getRemainingSeconds() {
            if (!pomodoroState.isRunning || !pomodoroState.endsAt) return Math.max(0, Number(pomodoroState.remainingSeconds || 0));
            return Math.max(0, Math.ceil((pomodoroState.endsAt - Date.now()) / 1000));
        }

        function notify(message, type = 'success') {
            if (window.showNotification) {
                window.showNotification(message, type);
                return;
            }
            if (type === 'error') alert(message);
        }

        function playPomodoroChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const audioCtx = new AudioCtx();
                const now = audioCtx.currentTime;
                [523.25, 659.25, 783.99].forEach((frequency, index) => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = frequency;
                    gain.gain.setValueAtTime(0.0001, now + index * 0.16);
                    gain.gain.exponentialRampToValueAtTime(0.12, now + index * 0.16 + 0.03);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + index * 0.16 + 0.22);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start(now + index * 0.16);
                    osc.stop(now + index * 0.16 + 0.24);
                });
            } catch (_) {}
        }

        async function syncPomodoroBackend(action, context, silent = false) {
            const taskContext = context || { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId };
            if (!taskContext?.projectId || !taskContext?.taskId) {
                pomodoroState.backendTimerActive = false;
                return false;
            }

            try {
                const response = await fetch('/api/proyectos/timer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        id: taskContext.projectId,
                        action,
                        tarea_id: taskContext.taskId,
                    }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) throw new Error('pomodoro_timer_sync_failed');
                pomodoroState.backendTimerActive = action === 'start';
                return true;
            } catch (error) {
                pomodoroState.backendTimerActive = false;
                if (!silent) notify('No se pudo sincronizar el tiempo enfocado con la tarea.', 'error');
                return false;
            }
        }

        function openPomodoroFullscreen() {
            if (!pomodoroFullscreen) return;
            const selectedTask = tdahSelectedTask || getStoredSelectedTask();
            const hasValidTask = !!(pomodoroState.activeTaskId || selectedTask?.taskId || pomodoroState.activeTaskName);
            if (!hasValidTask) {
                notify('Selecciona o escribe una tarea en La unica cosa que haras.', 'error');
                return;
            }
            pomodoroState.fullscreenVisible = true;
            pomodoroFullscreen.classList.remove('hidden');
            closeNativePomodoroPip();
            persistPomodoroState();
        }

        function closePomodoroFullscreen() {
            if (!pomodoroFullscreen) return;
            pomodoroState.fullscreenVisible = false;
            pomodoroFullscreen.classList.add('hidden');
            persistPomodoroState();
        }

        function openCustomPomodoroPip() {
            updateCustomPomodoroPip();
            pomodoroMiniPip?.classList.remove('hidden');
            pomodoroState.fullscreenVisible = false;
            pomodoroFullscreen?.classList.add('hidden');
            persistPomodoroState();
        }

        function closeCustomPomodoroPip() {
            pomodoroMiniPip?.classList.add('hidden');
        }

        function updateCustomPomodoroPip() {
            if (!pomodoroMiniPip) return;
            const remaining = getRemainingSeconds();
            const activeTaskName = pomodoroState.activeTaskName || tdahSelectedTask?.taskName || 'Selecciona o escribe una tarea';
            const activeProjectTitle = pomodoroState.activeProjectTitle || tdahSelectedTask?.projectTitle || 'Pomodoro';
            const phaseLabel = pomodoroState.phase === 'break' ? 'Descanso activo' : 'Trabajo enfocado';
            if (pomodoroMiniDisplay) pomodoroMiniDisplay.textContent = formatPomodoroTime(remaining);
            if (pomodoroMiniPhase) pomodoroMiniPhase.textContent = phaseLabel;
            if (pomodoroMiniTask) pomodoroMiniTask.textContent = activeTaskName;
            if (pomodoroMiniProject) pomodoroMiniProject.textContent = pomodoroState.phase === 'break' ? `${activeProjectTitle} · descanso de ${pomodoroState.breakMinutes}m` : `${activeProjectTitle} · ${pomodoroState.workMinutes}m`;
            if (pomodoroMiniStart) pomodoroMiniStart.innerHTML = pomodoroState.isRunning ? pauseIcon : playIcon;
        }

        function supportsNativePomodoroPip() {
            if (!nativePipVideo) return false;
            return !!nativePipVideo.requestPictureInPicture
                || (typeof nativePipVideo.webkitSupportsPresentationMode === 'function' && nativePipVideo.webkitSupportsPresentationMode('picture-in-picture'));
        }

        function closeNativePomodoroPip() {
            stopNativePipRenderLoop();
            setNativePipVideoPosition(false);
            if (document.pictureInPictureElement === nativePipVideo && document.exitPictureInPicture) {
                document.exitPictureInPicture().catch(() => {});
            }
            if (nativePipVideo && typeof nativePipVideo.webkitSetPresentationMode === 'function' && nativePipVideo.webkitPresentationMode === 'picture-in-picture') {
                try { nativePipVideo.webkitSetPresentationMode('inline'); } catch (_) {}
            }
        }

        function isNativePomodoroPipOpen() {
            return document.pictureInPictureElement === nativePipVideo
                || (nativePipVideo && nativePipVideo.webkitPresentationMode === 'picture-in-picture');
        }

        function playNativePipVideoSilently() {
            if (!nativePipVideo) return Promise.resolve();
            syncingNativePipPlayback = true;
            const playPromise = nativePipVideo.play();
            const release = () => setTimeout(() => { syncingNativePipPlayback = false; }, 0);
            if (playPromise && typeof playPromise.then === 'function') {
                return playPromise.catch(() => {}).finally(release);
            }
            release();
            return Promise.resolve();
        }

        function pauseNativePipVideoSilently() {
            if (!nativePipVideo) return;
            syncingNativePipPlayback = true;
            try {
                nativePipVideo.pause();
            } catch (_) {}
            setTimeout(() => { syncingNativePipPlayback = false; }, 0);
        }

        function updateNativePipMediaSession() {
            if (!('mediaSession' in navigator)) return;
            const activeTaskName = pomodoroState.activeTaskName || tdahSelectedTask?.taskName || 'Pomodoro Tdah';
            const activeProjectTitle = pomodoroState.activeProjectTitle || tdahSelectedTask?.projectTitle || 'Infocus';

            try {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: activeTaskName,
                    artist: activeProjectTitle,
                    album: pomodoroState.phase === 'break' ? 'Descanso activo' : 'Trabajo enfocado',
                });
            } catch (_) {}

            try {
                navigator.mediaSession.playbackState = pomodoroState.isRunning ? 'playing' : 'paused';
            } catch (_) {}

            try {
                navigator.mediaSession.setActionHandler('play', async () => {
                    if (!pomodoroState.isRunning) await startOrPausePomodoro();
                    syncNativePipPlaybackState();
                });
                navigator.mediaSession.setActionHandler('pause', async () => {
                    if (pomodoroState.isRunning) await startOrPausePomodoro();
                    syncNativePipPlaybackState();
                });
            } catch (_) {}

            ['seekbackward', 'seekforward', 'previoustrack', 'nexttrack', 'skipad', 'seekto'].forEach((action) => {
                try { navigator.mediaSession.setActionHandler(action, null); } catch (_) {}
            });
        }

        function syncNativePipPlaybackState() {
            updateNativePipMediaSession();
            if (!isNativePomodoroPipOpen()) {
                nativePipLastPlaybackState = null;
                return;
            }
            const desiredPlaybackState = pomodoroState.isRunning ? 'playing' : 'paused';
            if (nativePipLastPlaybackState === desiredPlaybackState) return;
            nativePipLastPlaybackState = desiredPlaybackState;
            playNativePipVideoSilently();
        }

        async function handleNativePipVideoPlay() {
            if (syncingNativePipPlayback || !isNativePomodoroPipOpen()) return;
            renderNativePipFrame();
            syncNativePipPlaybackState();
        }

        async function handleNativePipVideoPause() {
            if (syncingNativePipPlayback || !isNativePomodoroPipOpen()) return;
            await playNativePipVideoSilently();
            renderNativePipFrame();
            syncNativePipPlaybackState();
        }

        function renderNativePipFrame() {
            if (!nativePipCanvas) return;
            const ctx = nativePipCanvas.getContext('2d');
            if (!ctx) return;

            const width = nativePipCanvas.width;
            const height = nativePipCanvas.height;
            const remaining = formatPomodoroTime(getRemainingSeconds());
            const taskLabel = pomodoroState.activeTaskName || tdahSelectedTask?.taskName || 'Selecciona o escribe una tarea';
            const projectLabel = pomodoroState.activeProjectTitle || tdahSelectedTask?.projectTitle || 'Sin Cliente';

            ctx.fillStyle = '#111729';
            ctx.fillRect(0, 0, width, height);
            ctx.fillStyle = (Math.floor(Date.now() / 250) % 2) ? '#11182a' : '#111729';
            ctx.fillRect(width - 3, height - 3, 2, 2);

            const left = 34;
            const maxTextWidth = width - (left * 2);
            const fitText = (text, maxWidth, font) => {
                ctx.font = font;
                if (ctx.measureText(text).width <= maxWidth) return text;
                let safe = text;
                while (safe.length > 1 && ctx.measureText(`${safe}…`).width > maxWidth) {
                    safe = safe.slice(0, -1);
                }
                return `${safe}…`;
            };

            const fittedTask = fitText(taskLabel, maxTextWidth, '700 36px -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif');
            const fittedProject = fitText(projectLabel, maxTextWidth, '500 21px -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif');

            ctx.fillStyle = '#e2e8f0';
            ctx.font = '700 36px -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(fittedTask, left, 128);

            ctx.fillStyle = '#94a3b8';
            ctx.font = '500 21px -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
            ctx.fillText(fittedProject, left, 160);

            ctx.fillStyle = '#bef264';
            ctx.font = '700 16px -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif';
            ctx.fillText('Pomodoro Tdah', left, 196);

            ctx.fillStyle = '#bef264';
            ctx.font = '800 118px ui-monospace, SFMono-Regular, Menlo, monospace';
            ctx.fillText(remaining, left, 372);
            ctx.textAlign = 'start';
            
            if (nativePipVideoTrack && typeof nativePipVideoTrack.requestFrame === 'function') {
                nativePipVideoTrack.requestFrame();
            }
            nativePipLastFrameAt = Date.now();
        }

        function resetNativePipSource() {
            if (nativePipStream?.getTracks) {
                nativePipStream.getTracks().forEach((track) => {
                    try { track.stop(); } catch (_) {}
                });
            }
            nativePipStream = null;
            nativePipVideoTrack = null;
            if (nativePipVideo) {
                try { nativePipVideo.srcObject = null; } catch (_) {}
            }
            stopNativePipSilentAudio();
        }

        function stopNativePipSilentAudio() {
            try { nativePipAudioSource?.stop(); } catch (_) {}
            try { nativePipAudioTrack?.stop(); } catch (_) {}
            try { nativePipAudioContext?.close(); } catch (_) {}
            nativePipAudioContext = null;
            nativePipAudioSource = null;
            nativePipAudioTrack = null;
        }

        function ensureNativePipSilentAudioTrack() {
            if (!nativePipAudioPrimed) return null;
            if (nativePipAudioTrack && nativePipAudioTrack.readyState !== 'ended') {
                if (nativePipAudioContext?.state === 'suspended') nativePipAudioContext.resume().catch(() => {});
                return nativePipAudioTrack;
            }

            const AudioCtor = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtor) return null;
            try {
                nativePipAudioContext = new AudioCtor();
                const destination = nativePipAudioContext.createMediaStreamDestination();
                const oscillator = nativePipAudioContext.createOscillator();
                const gain = nativePipAudioContext.createGain();
                oscillator.frequency.value = 1;
                gain.gain.value = 0.00001;
                oscillator.connect(gain);
                gain.connect(destination);
                oscillator.start();
                nativePipAudioSource = oscillator;
                nativePipAudioTrack = destination.stream.getAudioTracks()[0] || null;
                nativePipAudioContext.resume().catch(() => {});
                return nativePipAudioTrack;
            } catch (_) {
                return null;
            }
        }

        function nativePipTrackHealthy() {
            const audioHealthy = !nativePipAudioPrimed || (nativePipAudioTrack && nativePipAudioTrack.readyState !== 'ended');
            return !!nativePipStream
                && !!nativePipVideoTrack
                && !!audioHealthy
                && nativePipVideoTrack.readyState !== 'ended'
                && nativePipVideo?.srcObject === nativePipStream;
        }

        async function recoverNativePipSource() {
            if (nativePipRecovering || !nativePipVideo || !nativePipCanvas) return;
            nativePipRecovering = true;
            try {
                resetNativePipSource();
                prepareNativePipSource();
                renderNativePipFrame();
                await playNativePipVideoSilently();
            } finally {
                nativePipRecovering = false;
            }
        }

        function prepareNativePipSource() {
            if (!nativePipVideo || !nativePipCanvas || !nativePipCanvas.captureStream) return false;
            nativePipVideo.classList.remove('hidden');
            setNativePipVideoPosition(false);
            if (nativePipTrackHealthy()) return true;
            resetNativePipSource();
            
            renderNativePipFrame();
            nativePipStream = nativePipCanvas.captureStream(2);
            nativePipVideoTrack = nativePipStream.getVideoTracks ? (nativePipStream.getVideoTracks()[0] || null) : null;
            const audioTrack = ensureNativePipSilentAudioTrack();
            if (audioTrack && nativePipStream.addTrack) {
                try { nativePipStream.addTrack(audioTrack); } catch (_) {}
            }
            if (nativePipVideoTrack) {
                nativePipVideoTrack.addEventListener('ended', () => {
                    if (isNativePomodoroPipOpen()) recoverNativePipSource();
                });
                nativePipVideoTrack.addEventListener('mute', () => {
                    setTimeout(() => {
                        if (isNativePomodoroPipOpen()) recoverNativePipSource();
                    }, 350);
                });
            }
            nativePipVideo.srcObject = nativePipStream;
            nativePipVideo.muted = true;
            nativePipVideo.playsInline = true;
            nativePipVideo.autoplay = true;
            nativePipVideo.setAttribute('webkit-playsinline', 'true');
            nativePipVideo.disablePictureInPicture = false;
            return true;
        }

        async function ensureNativePipSource() {
            if (!prepareNativePipSource()) return false;
            
            try {
                await playNativePipVideoSilently();
            } catch (_) {}
            return true;
        }

        function primeNativePipSource() {
            nativePipAudioPrimed = true;
            if (!prepareNativePipSource()) return;
            renderNativePipFrame();
            playNativePipVideoSilently();
        }

        function startNativePipRenderLoop() {
            if (nativePipRenderInterval) clearTimeout(nativePipRenderInterval);
            if (nativePipWatchdogInterval) clearInterval(nativePipWatchdogInterval);

            const pump = () => {
                if (!isNativePomodoroPipOpen()) {
                    nativePipRenderInterval = null;
                    return;
                }
                renderNativePipFrame();
                if (isNativePomodoroPipOpen() && nativePipVideo?.paused) {
                    playNativePipVideoSilently();
                }
                nativePipRenderInterval = setTimeout(pump, 500);
            };
            pump();

            nativePipWatchdogInterval = setInterval(() => {
                if (!isNativePomodoroPipOpen()) return;
                renderNativePipFrame();
                const staleFrame = nativePipLastFrameAt && Date.now() - nativePipLastFrameAt > 2600;
                if (!nativePipTrackHealthy() || nativePipVideo?.paused || nativePipVideo?.readyState < 2 || staleFrame) {
                    playNativePipVideoSilently();
                    if (!nativePipTrackHealthy() || nativePipVideo?.readyState < 2 || staleFrame) {
                        recoverNativePipSource();
                    }
                }
            }, 1000);
        }

        function stopNativePipRenderLoop() {
            if (nativePipRenderInterval) {
                clearTimeout(nativePipRenderInterval);
                nativePipRenderInterval = null;
            }
            if (nativePipWatchdogInterval) {
                clearInterval(nativePipWatchdogInterval);
                nativePipWatchdogInterval = null;
            }
            if (!isNativePomodoroPipOpen()) stopNativePipSilentAudio();
        }

        function refreshNativePipAfterResume() {
            if (!isNativePomodoroPipOpen()) return;
            renderNativePipFrame();
            ensureNativePipSource().then(() => {
                playNativePipVideoSilently();
                if (!nativePipRenderInterval) startNativePipRenderLoop();
            });
        }

        function setNativePipVideoPosition(visible) {
            if (!nativePipVideo) return;
            nativePipVideo.classList.remove('hidden');
            nativePipVideo.style.display = 'block';
            if (visible) {
                nativePipVideo.style.position = 'fixed';
                nativePipVideo.style.top = '0';
                nativePipVideo.style.left = '0';
                nativePipVideo.style.width = '2px';
                nativePipVideo.style.height = '2px';
                nativePipVideo.style.opacity = '0.01';
                nativePipVideo.style.pointerEvents = 'none';
                nativePipVideo.style.zIndex = '1';
                nativePipVideo.style.transform = 'translateZ(0)';
                nativePipVideo.style.background = '#000';
            } else {
                nativePipVideo.style.position = 'fixed';
                nativePipVideo.style.top = '-9999px';
                nativePipVideo.style.left = '-9999px';
                nativePipVideo.style.width = '1px';
                nativePipVideo.style.height = '1px';
                nativePipVideo.style.opacity = '0';
                nativePipVideo.style.pointerEvents = 'none';
                nativePipVideo.style.zIndex = '-1';
                nativePipVideo.style.background = 'transparent';
            }
        }

        async function openNativePomodoroPip() {
            if (nativePipOpening) return;
            nativePipAudioPrimed = true;
            if (!supportsNativePomodoroPip()) {
                notify('PiP nativo no disponible. Prueba en Chrome, Edge o Safari actualizado.', 'error');
                return;
            }

            const ready = prepareNativePipSource();
            if (!ready) {
                notify('No se pudo preparar el source de PiP.', 'error');
                return;
            }

            nativePipOpening = true;
            try {
                setNativePipVideoPosition(true);
                playNativePipVideoSilently();
                startNativePipRenderLoop();
                
                if (nativePipVideo.requestPictureInPicture) {
                    await nativePipVideo.requestPictureInPicture();
                } else if (typeof nativePipVideo.webkitSetPresentationMode === 'function') {
                    nativePipVideo.webkitSetPresentationMode('picture-in-picture');
                }
                
                pomodoroState.fullscreenVisible = false;
                pomodoroFullscreen?.classList.add('hidden');
                persistPomodoroState();
                syncNativePipPlaybackState();
            } catch (e) {
                setNativePipVideoPosition(false);
                stopNativePipRenderLoop();
                console.error('PiP error:', e);
                openCustomPomodoroPip();
                const now = Date.now();
                if (now - nativePipLastErrorAt > 1800) {
                    nativePipLastErrorAt = now;
                    notify('Abrí el PiP interno del CRM porque el navegador bloqueó el PiP nativo.', 'info');
                }
            } finally {
                nativePipOpening = false;
            }
        }

        async function openPreferredPomodoroPip() {
            await openNativePomodoroPip();
        }

        async function toggleBrowserFullscreen() {
            try {
                if (!document.fullscreenElement) {
                    await document.documentElement.requestFullscreen();
                } else {
                    await document.exitFullscreen();
                }
            } catch (_) {}
        }

        function updatePomodoroIcons() {
            if (timerStart) timerStart.innerHTML = pomodoroState.isRunning ? pauseIcon : playIcon;
            if (pomodoroFsStart) pomodoroFsStart.innerHTML = pomodoroState.isRunning ? pauseIconLarge : playIconLarge;
        }

        function renderPomodoro() {
            const remaining = getRemainingSeconds();
            pomodoroState.remainingSeconds = remaining;

            const selectedTask = tdahSelectedTask;
            const activeTaskName = pomodoroState.activeTaskName || selectedTask?.taskName || 'Selecciona o escribe una tarea';
            const activeProjectTitle = pomodoroState.activeProjectTitle || selectedTask?.projectTitle || 'Pomodoro';
            const phaseLabel = pomodoroState.phase === 'break' ? 'Descanso activo' : 'Trabajo enfocado';
            const timerColor = pomodoroState.phase === 'break' ? '#93c5fd' : '#a3e635';
            const progress = Math.max(0, Math.min(100, (remaining / Math.max(1, getPhaseDurationSeconds())) * 100));

            if (pomodoroTaskLabel) pomodoroTaskLabel.textContent = selectedTask ? `${selectedTask.taskName} · ${selectedTask.projectTitle}` : 'Selecciona o escribe una tarea en La unica cosa que haras';
            if (timerDisplay) timerDisplay.textContent = formatPomodoroTime(remaining);
            if (pomodoroFsDisplay) pomodoroFsDisplay.textContent = formatPomodoroTime(remaining);
            if (pomodoroPhase) pomodoroPhase.textContent = phaseLabel;
            if (pomodoroTaskTitle) pomodoroTaskTitle.textContent = activeTaskName;
            if (pomodoroProjectTitle) pomodoroProjectTitle.textContent = pomodoroState.phase === 'break' ? `${activeProjectTitle} · descanso de ${pomodoroState.breakMinutes}m` : `${activeProjectTitle} · bloque de ${pomodoroState.workMinutes}m`;
            if (breakLabel) breakLabel.textContent = `Descanso automático: ${pomodoroState.breakMinutes}m`;
            breakOptionButtons.forEach((button) => {
                const minutes = Number(button.dataset.break || 0);
                const active = minutes === pomodoroState.breakMinutes;
                button.classList.toggle('bg-[#dff8a7]', active);
                button.classList.toggle('text-[#101729]', active);
                button.classList.toggle('bg-slate-100', !active);
                button.classList.toggle('text-slate-600', !active);
            });
            workOptionButtons.forEach((button) => {
                const minutes = Number(button.dataset.work || 0);
                const active = minutes === pomodoroState.workMinutes;
                button.classList.toggle('bg-[#dff8a7]', active);
                button.classList.toggle('text-[#101729]', active);
                button.classList.toggle('bg-slate-100', !active);
                button.classList.toggle('text-slate-600', !active);
            });
            if (timerProgress) {
                timerProgress.style.width = `${progress}%`;
                timerProgress.style.backgroundColor = timerColor;
            }
            if (pomodoroFsDisplay) pomodoroFsDisplay.style.color = timerColor;
            renderNativePipFrame();
            updateCustomPomodoroPip();
            updateNativePipMediaSession();
            syncNativePipPlaybackState();

            updatePomodoroIcons();
            persistPomodoroState();
        }

        function syncPomodoroInterval() {
            clearInterval(timerInterval);
            if (!pomodoroState.isRunning) return;
            timerInterval = setInterval(async () => {
                const remaining = getRemainingSeconds();
                pomodoroState.remainingSeconds = remaining;
                renderPomodoro();
                if (remaining > 0 || pomodoroTransitioning) return;
                pomodoroTransitioning = true;

                if (pomodoroState.phase === 'work') {
                    const stopped = await syncPomodoroBackend('stop', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId }, true);
                    if (stopped) {
                        pomodoroState.loggedWorkLogs = Math.max(0, Number(pomodoroState.loggedWorkLogs || 0)) + 1;
                    }
                    pomodoroState.phase = 'break';
                    pomodoroState.isRunning = true;
                    pomodoroState.remainingSeconds = pomodoroState.breakMinutes * 60;
                    pomodoroState.endsAt = Date.now() + (pomodoroState.breakMinutes * 60 * 1000);
                    playPomodoroChime();
                    notify(`Bloque de foco completado. Empieza tu descanso de ${pomodoroState.breakMinutes} minutos.`);
                } else {
                    playPomodoroChime();
                    const nextTask = tdahSelectedTask || getStoredSelectedTask();
                    pomodoroState.phase = 'work';
                    pomodoroState.remainingSeconds = pomodoroState.workMinutes * 60;
                    pomodoroState.endsAt = null;
                    pomodoroState.isRunning = false;
                    pomodoroState.activeProjectId = '';
                    pomodoroState.activeProjectTitle = '';
                    pomodoroState.activeTaskId = '';
                    pomodoroState.activeTaskName = '';

                    if (nextTask) {
                        tdahSelectedTask = nextTask;
                        pomodoroState.activeProjectId = String(nextTask.projectId);
                        pomodoroState.activeProjectTitle = String(nextTask.projectTitle || '');
                        pomodoroState.activeTaskId = String(nextTask.taskId);
                        pomodoroState.activeTaskName = String(nextTask.taskName || '');
                        const started = await syncPomodoroBackend('start', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId });
                        if (started) {
                            pomodoroState.isRunning = true;
                            pomodoroState.endsAt = Date.now() + (pomodoroState.workMinutes * 60 * 1000);
                            notify(`Descanso terminado. Nuevo bloque de ${pomodoroState.workMinutes} minutos en marcha.`);
                        }
                    } else {
                        notify('Descanso terminado. Selecciona o escribe una tarea en La unica cosa que haras.', 'error');
                    }
                }

                renderPomodoro();
                syncPomodoroInterval();
                pomodoroTransitioning = false;
            }, 250);
        }

        async function startOrPausePomodoro(options = {}) {
            const { openFullscreen = false } = options;

            if (pomodoroState.isRunning) {
                if (openFullscreen) openPomodoroFullscreen();
                pomodoroState.remainingSeconds = getRemainingSeconds();
                pomodoroState.isRunning = false;
                pomodoroState.endsAt = null;
                if (pomodoroState.phase === 'work' && pomodoroState.backendTimerActive) {
                    const stopped = await syncPomodoroBackend('stop', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId }, true);
                    if (stopped) {
                        pomodoroState.loggedWorkLogs = Math.max(0, Number(pomodoroState.loggedWorkLogs || 0)) + 1;
                    }
                }
                renderPomodoro();
                syncPomodoroInterval();
                return;
            }

            if (pomodoroState.phase === 'work') {
                const canStart = await ensurePomodoroCanStart();
                if (!canStart) return;
                const selectedTask = tdahSelectedTask || getStoredSelectedTask();
                if (!selectedTask) {
                    notify('Selecciona o escribe una tarea en La unica cosa que haras.', 'error');
                    return;
                }
                if (!pomodoroState.activeTaskId) {
                    pomodoroState.activeProjectId = String(selectedTask.projectId);
                    pomodoroState.activeProjectTitle = String(selectedTask.projectTitle || '');
                    pomodoroState.activeTaskId = String(selectedTask.taskId);
                    pomodoroState.activeTaskName = String(selectedTask.taskName || '');
                }

                const synced = await syncPomodoroBackend('start', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId });
                if (!synced) return;
            }

            if (openFullscreen) openPomodoroFullscreen();
            pomodoroState.remainingSeconds = getRemainingSeconds() || getPhaseDurationSeconds();
            pomodoroState.isRunning = true;
            pomodoroState.endsAt = Date.now() + (pomodoroState.remainingSeconds * 1000);
            renderPomodoro();
            syncPomodoroInterval();
        }

        async function resetPomodoro(silent = false) {
            clearInterval(timerInterval);
            if (pomodoroState.phase === 'work' && pomodoroState.backendTimerActive) {
                const stopped = await syncPomodoroBackend('stop', { projectId: pomodoroState.activeProjectId, taskId: pomodoroState.activeTaskId }, true);
                if (stopped) {
                    pomodoroState.loggedWorkLogs = Math.max(0, Number(pomodoroState.loggedWorkLogs || 0)) + 1;
                }
            }

            resetPomodoroSessionState();
            renderPomodoro();
            if (!silent) notify('Pomodoro reiniciado.');
        }

        function setBreakMinutes(minutes) {
            const safeMinutes = BREAK_MINUTES_OPTIONS.includes(Number(minutes)) ? Number(minutes) : DEFAULT_BREAK_MINUTES;
            pomodoroState.breakMinutes = safeMinutes;
            if (pomodoroState.phase === 'break' && !pomodoroState.isRunning) {
                pomodoroState.remainingSeconds = safeMinutes * 60;
            }
            renderPomodoro();
        }

        window.setTimer = async function(minutes) {
            const safeMinutes = [25, 30, 60].includes(Number(minutes)) ? Number(minutes) : 25;
            await resetPomodoro(true);
            pomodoroState.workMinutes = safeMinutes;
            pomodoroState.remainingSeconds = safeMinutes * 60;
            renderPomodoro();
        }

        window.startTdahPomodoroFromAi = async function(options = {}) {
            const safeMinutes = [25, 30, 60].includes(Number(options?.minutes)) ? Number(options.minutes) : 25;
            const focusTask = String(options?.task || '').trim() || 'Bloque de foco guiado por IA';
            const canStart = await ensurePomodoroCanStart();
            if (!canStart) return { ok: false, reason: 'timer_conflict' };

            if (pomodoroState.isRunning || pomodoroState.backendTimerActive || hasPomodoroSession(pomodoroState)) {
                await resetPomodoro(true);
            }

            applySelectedTask(null, { syncInput: false, persist: true });
            if (oneThingInput) {
                oneThingInput.value = focusTask;
                localStorage.setItem('tdah_one_thing', focusTask);
            }
            if (oneThingCheck) setOneThingCompletedState(false);

            pomodoroState.workMinutes = safeMinutes;
            pomodoroState.phase = 'work';
            pomodoroState.remainingSeconds = safeMinutes * 60;
            pomodoroState.activeProjectId = '';
            pomodoroState.activeProjectTitle = 'Pomodoro TDAH';
            pomodoroState.activeTaskId = '';
            pomodoroState.activeTaskName = focusTask;
            pomodoroState.backendTimerActive = false;
            pomodoroState.loggedWorkLogs = 0;
            pomodoroState.isRunning = true;
            pomodoroState.endsAt = Date.now() + (safeMinutes * 60 * 1000);
            pomodoroState.fullscreenVisible = false;

            renderPomodoro();
            syncPomodoroInterval();
            if (options?.openPip) {
                openPreferredPomodoroPip().catch(() => openCustomPomodoroPip());
            }
            notify(`Pomodoro TDAH activado: ${safeMinutes} minutos.`);
            return { ok: true, state: { ...pomodoroState } };
        }

        if(oneThingInput) {
            oneThingInput.value = localStorage.getItem('tdah_one_thing') || '';
            if (localStorage.getItem('tdah_one_thing_done') === 'true') {
                setOneThingCompletedState(true);
            }

            oneThingInput.addEventListener('input', (e) => {
                localStorage.setItem('tdah_one_thing', e.target.value);
                renderTaskSuggestions(e.target.value);
                dropdown?.classList.remove('hidden');
                if (tdahSelectedTask && e.target.value !== tdahSelectedTask.taskName) {
                    applySelectedTask(null, { syncInput: false, persist: true });
                }
                if (e.target.value === '') {
                    if(oneThingCheck) oneThingCheck.checked = false;
                    localStorage.setItem('tdah_one_thing_done', 'false');
                    oneThingInput.classList.remove('line-through', 'opacity-50');
                    if(oneThingPraise) oneThingPraise.classList.add('hidden');
                }
            });

            oneThingInput.addEventListener('focus', async () => {
                await loadActiveProjects();
                dropdown?.classList.remove('hidden');
                renderTaskSuggestions(oneThingInput.value);
            });
            oneThingInput.addEventListener('blur', () => setTimeout(() => dropdown?.classList.add('hidden'), 200));
        }

        if(oneThingCheck) {
            oneThingCheck.addEventListener('change', async (e) => {
                const checked = !!e.target.checked;
                if (syncingOneThingCheck) return;
                setOneThingCompletedState(checked);
                if (tdahSelectedTask?.projectId && tdahSelectedTask?.taskId) {
                    await syncSelectedTaskCompletionFromOneThing(checked);
                }
            });
        }

        // --- 2. Focus Mode Logic ---
        const focusTrigger = document.getElementById('tdah-focus-trigger');
        if(focusTrigger) {
            focusTrigger.addEventListener('click', () => {
                document.body.classList.toggle('tdah-focus-active');
                const isActive = document.body.classList.contains('tdah-focus-active');
                
                const bg = document.getElementById('tdah-focus-toggle-bg');
                const dot = document.getElementById('tdah-focus-toggle-dot');
                
                if (isActive) {
                    bg.classList.replace('bg-slate-200', 'bg-indigo-600');
                    dot.classList.replace('translate-x-1', 'translate-x-5');
                } else {
                    bg.classList.replace('bg-indigo-600', 'bg-slate-200');
                    dot.classList.replace('translate-x-5', 'translate-x-1');
                }
            });
        }

        if (timerStart) timerStart.addEventListener('click', () => startOrPausePomodoro());
        if (timerReset) timerReset.addEventListener('click', () => resetPomodoro());
        if (pomodoroFsStart) pomodoroFsStart.addEventListener('click', () => startOrPausePomodoro());
        if (pomodoroFsReset) pomodoroFsReset.addEventListener('click', () => resetPomodoro());
        if (pomodoroFsSave) pomodoroFsSave.addEventListener('click', () => savePomodoroSession());
        if (pomodoroFsDelete) pomodoroFsDelete.addEventListener('click', () => deletePomodoroSession());
        if (pomodoroCloseBtn) pomodoroCloseBtn.addEventListener('click', closePomodoroFullscreen);
        if (pomodoroOpenFullscreenBtn) pomodoroOpenFullscreenBtn.addEventListener('click', openPomodoroFullscreen);
        if (pomodoroOpenPipBtn) pomodoroOpenPipBtn.addEventListener('pointerdown', primeNativePipSource);
        if (pomodoroToPipBtn) pomodoroToPipBtn.addEventListener('pointerdown', primeNativePipSource);
        if (pomodoroOpenPipBtn) pomodoroOpenPipBtn.addEventListener('click', openPreferredPomodoroPip);
        if (pomodoroToPipBtn) pomodoroToPipBtn.addEventListener('click', openPreferredPomodoroPip);
        if (pomodoroFsToggleBtn) pomodoroFsToggleBtn.addEventListener('click', toggleBrowserFullscreen);
        if (pomodoroMiniClose) pomodoroMiniClose.addEventListener('click', closeCustomPomodoroPip);
        if (pomodoroMiniStart) pomodoroMiniStart.addEventListener('click', () => startOrPausePomodoro());
        if (pomodoroMiniReset) pomodoroMiniReset.addEventListener('click', () => resetPomodoro());
        prepareNativePipSource();
        breakOptionButtons.forEach((button) => {
            button.addEventListener('click', () => setBreakMinutes(Number(button.dataset.break || 15)));
        });
        workOptionButtons.forEach((button) => {
            button.addEventListener('click', () => window.setTimer(Number(button.dataset.work || 25)));
        });

        if (nativePipVideo) {
            nativePipVideo.addEventListener('play', handleNativePipVideoPlay);
            nativePipVideo.addEventListener('pause', handleNativePipVideoPause);
            nativePipVideo.addEventListener('leavepictureinpicture', () => {
                stopNativePipRenderLoop();
                setNativePipVideoPosition(false);
                nativePipLastPlaybackState = null;
                renderNativePipFrame();
                updateNativePipMediaSession();
            });
            nativePipVideo.addEventListener('webkitpresentationmodechanged', () => {
                if (nativePipVideo.webkitPresentationMode !== 'picture-in-picture') {
                    stopNativePipRenderLoop();
                    setNativePipVideoPosition(false);
                    nativePipLastPlaybackState = null;
                } else {
                    if (!nativePipRenderInterval) startNativePipRenderLoop();
                    syncNativePipPlaybackState();
                }
                renderNativePipFrame();
                updateNativePipMediaSession();
                refreshNativePipAfterResume();
            });
            nativePipVideo.addEventListener('emptied', refreshNativePipAfterResume);
            nativePipVideo.addEventListener('stalled', refreshNativePipAfterResume);
            nativePipVideo.addEventListener('waiting', refreshNativePipAfterResume);
        }

        document.addEventListener('visibilitychange', refreshNativePipAfterResume);
        window.addEventListener('pageshow', refreshNativePipAfterResume);
        window.addEventListener('focus', refreshNativePipAfterResume);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && pomodoroState.fullscreenVisible) closePomodoroFullscreen();
            if (event.key === 'Escape' && !pomodoroTimerConflictModal?.classList.contains('hidden')) {
                window.closeTdahPomodoroTimerConflictModal('cancel');
            }
        });

        window.toggleTdahPomodoroFromHeader = () => startOrPausePomodoro();
        window.resetTdahPomodoroFromHeader = () => resetPomodoro();
        window.openTdahPomodoroFullscreen = () => openPomodoroFullscreen();
        window.openTdahPomodoroPip = () => openPreferredPomodoroPip();
        window.saveTdahPomodoroSession = () => savePomodoroSession();
        window.deleteTdahPomodoroSession = () => deletePomodoroSession();

        if (pomodoroState.fullscreenVisible) pomodoroFullscreen?.classList.remove('hidden');
        loadActiveProjects();
        renderPomodoro();
        syncPomodoroInterval();

        // --- 4. Brain Dump Logic (Sync) ---
        const brainDump = document.getElementById('tdah-braindump');
        if(brainDump) {
            // Initial Load
            brainDump.value = localStorage.getItem('tdah_braindump') || '';
            
            // Save on Input
            brainDump.addEventListener('input', (e) => {
                localStorage.setItem('tdah_braindump', e.target.value);
                // Trigger event so Dashboard knows
                window.dispatchEvent(new Event('tdah-notes-updated'));
                // Trigger storage event for other tabs
                window.dispatchEvent(new Event('storage'));
            });

            // Listen for changes from Dashboard
            window.addEventListener('storage', (e) => {
                if (e.key === 'tdah_braindump' || e.type === 'storage') {
                    brainDump.value = localStorage.getItem('tdah_braindump') || '';
                }
            });
            
            // Listen for custom event from same window (Dashboard widget)
            window.addEventListener('tdah-notes-updated', () => {
                 brainDump.value = localStorage.getItem('tdah_braindump') || '';
            });
        }

    });
</script>
