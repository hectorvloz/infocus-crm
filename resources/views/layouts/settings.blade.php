<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Ajustes - Infocus CRM')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('partials.favicon')
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { limeInk: '#E7FF74', slateInk: '#0B0F10' },
          boxShadow: { soft: '0 6px 24px rgba(0,0,0,0.06)' },
          borderRadius: { xl2: '20px' }
        }
      }
    }
  </script>
  <style>
    /* Custom Scrollbar */
    .custom-scroll::-webkit-scrollbar { display: none; }
    .custom-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    .settings-nav-link.is-active { background-color: #f3fea4; color: #0f172a; font-weight: 600; }

    textarea {
      padding: 0.9rem 1rem;
      line-height: 1.55;
    }

    .app-select-wrap {
      position: relative;
      width: 100%;
    }

    .app-native-select {
      position: absolute !important;
      inset: 0;
      width: 0;
      height: 0;
      opacity: 0;
      pointer-events: none;
    }

    .app-select-trigger {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      border: 1px solid #cbd5e1;
      border-radius: 0.85rem;
      background: #fff;
      min-height: 44px;
      padding: 0.62rem 0.95rem;
      color: #0f172a;
      font-size: 0.92rem;
      font-weight: 500;
      text-align: left;
    }

    .app-select-trigger.is-open {
      border-color: #d9f99d;
      box-shadow: 0 0 0 3px rgba(236, 254, 136, 0.45);
    }

    .app-select-label {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .app-select-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      z-index: 80;
      width: 100%;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 0.95rem;
      box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
      overflow: hidden;
    }

    .app-select-menu.hidden { display: none; }
    .app-select-menu.drop-up { top: auto; bottom: calc(100% + 8px); }

    .app-select-search-wrap {
      padding: 0.6rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .app-select-search {
      width: 100%;
      border: 0;
      border-radius: 0.75rem;
      background: #f8fafc;
      padding: 0.68rem 0.85rem;
      color: #334155;
      font-size: 0.85rem;
    }

    .app-select-search:focus {
      outline: none;
      box-shadow: inset 0 0 0 1px #dbe4ee;
    }

    .app-select-options {
      max-height: 260px;
      overflow-y: auto;
      padding: 0.35rem;
    }

    .app-select-option {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.7rem;
      border: 0;
      background: transparent;
      border-radius: 0.75rem;
      padding: 0.62rem 0.82rem;
      color: #334155;
      font-size: 0.85rem;
      font-weight: 500;
      text-align: left;
      transition: all .15s ease;
    }

    .app-select-option:hover {
      background: #ecfe88;
      color: #0f172a;
    }

    .app-select-option.is-selected {
      background: #f8fafc;
      color: #0f172a;
      font-weight: 700;
    }

    .app-select-empty {
      padding: 0.75rem 0.9rem;
      color: #94a3b8;
      font-size: 0.8rem;
    }
  </style>
</head>
<body class="bg-neutral-50 text-slate-800 antialiased h-screen overflow-hidden flex">
  
  <!-- Settings Sidebar -->
  <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 h-full">
    <div class="p-6 border-b border-slate-100 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="p-2 -ml-2 rounded-xl hover:bg-slate-50 text-slate-400 hover:text-slate-900 transition-colors" title="Volver al Dashboard">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h1 class="font-bold text-lg text-slate-900">Configuración</h1>
    </div>
    
    <nav class="flex-1 overflow-y-auto custom-scroll p-4 space-y-1">
        <div class="px-3 py-2 text-xs font-bold text-slate-400 uppercase tracking-wider">General</div>
        <a href="{{ route('settings.edit') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.edit') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="text-sm">Empresa</span>
        </a>
        
        <div class="px-3 py-2 mt-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Sistema</div>
        <a href="{{ route('settings.team.index') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.team.*') || request()->routeIs('settings.roles.*') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span class="text-sm">Equipo y Roles</span>
        </a>
        <a href="{{ route('settings.smtp') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.smtp') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="text-sm">SMTP / Email</span>
        </a>
        <a href="{{ route('settings.invoice') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.invoice') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-sm">Facturación</span>
        </a>
        <a href="{{ route('settings.payment_methods') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.payment_methods') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="text-sm">Formas de Pago</span>
        </a>
        <a href="{{ route('settings.templates') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.templates') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/></svg>
            <span class="text-sm">Plantillas Email</span>
        </a>

        <div class="px-3 py-2 mt-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Avanzado</div>
        <a href="{{ route('settings.integrations') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.integrations') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
            <span class="text-sm">Integraciones</span>
        </a>
        <a href="{{ route('settings.ai') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.ai') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5V3m-5 7H5a2 2 0 00-2 2v2m16-4h-2m2 0a2 2 0 012 2v2M7 10h10a2 2 0 012 2v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6a2 2 0 012-2zM9 15h.01M15 15h.01M10 18h4"/>
            </svg>
            <span class="text-sm">IA</span>
        </a>
        <a href="{{ route('settings.backup') }}" class="settings-nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors {{ request()->routeIs('settings.backup') ? 'is-active' : '' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <span class="text-sm">Respaldos</span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-100">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span class="text-sm font-medium">Salir de Ajustes</span>
        </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 h-full overflow-y-auto custom-scroll bg-neutral-50 p-8">
    <div class="max-w-3xl mx-auto">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 border border-green-100 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
          <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-700 border border-red-100 flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
          </div>
        @endif

        @yield('content')
    </div>
  </main>

  <script>
    window.adjustDropPosition = function(triggerEl, menuEl) {
      menuEl.classList.remove('drop-up');
      const triggerRect = triggerEl.getBoundingClientRect();
      const menuHeight = menuEl.offsetHeight || 280;
      const spaceBelow = window.innerHeight - triggerRect.bottom;
      const spaceAbove = triggerRect.top;
      if (spaceBelow < menuHeight + 12 && spaceAbove > spaceBelow) {
        menuEl.classList.add('drop-up');
      }
    };

    (function() {
      function shouldEnhance(select) {
        if (!select) return false;
        if (select.dataset.appSelectEnhanced === '1') return false;
        if (select.dataset.nativeSelect === '1') return false;
        if (select.multiple || Number(select.size || 1) > 1) return false;
        if (select.classList.contains('hidden') || select.hidden) return false;
        return true;
      }

      function closeAll(exceptId) {
        document.querySelectorAll('.app-select-menu').forEach((menu) => {
          if (!exceptId || menu.dataset.selectId !== exceptId) {
            menu.classList.add('hidden');
          }
        });
        document.querySelectorAll('.app-select-trigger').forEach((trigger) => {
          if (!exceptId || trigger.dataset.selectId !== exceptId) {
            trigger.classList.remove('is-open');
          }
        });
      }

      function getOptions(select) {
        return Array.from(select.options || []).map((opt, index) => ({
          index,
          value: String(opt.value ?? ''),
          label: String(opt.textContent || '').trim(),
          disabled: !!opt.disabled,
        }));
      }

      function syncLabel(select) {
        const label = select._appSelectLabel;
        if (!label) return;
        const current = select.options[select.selectedIndex] || select.options[0];
        label.textContent = current ? current.textContent.trim() : 'Seleccionar...';
      }

      function renderOptions(select) {
        const host = select._appSelectOptions;
        const search = select._appSelectSearch;
        if (!host || !search) return;
        const selectedValue = String(select.value ?? '');
        const query = String(search.value || '').trim().toLowerCase();
        const options = getOptions(select).filter((opt) => opt.label.toLowerCase().includes(query));
        if (!options.length) {
          host.innerHTML = '<div class="app-select-empty">Sin resultados</div>';
          return;
        }
        host.innerHTML = options.map((opt) => {
          const isSelected = opt.value === selectedValue;
          const disabledClass = opt.disabled ? ' opacity-50 cursor-not-allowed' : '';
          return `<button type="button" data-index="${opt.index}" class="app-select-option ${isSelected ? 'is-selected' : ''}${disabledClass}"><span>${opt.label.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>${isSelected ? '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : ''}</button>`;
        }).join('');
      }

      function enhanceSelect(select) {
        if (!shouldEnhance(select)) return;
        const selectId = select.id || `app-select-${Math.random().toString(36).slice(2, 10)}`;
        if (!select.id) select.id = selectId;
        select.dataset.appSelectEnhanced = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'app-select-wrap';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'app-select-trigger';
        trigger.dataset.selectId = selectId;
        trigger.innerHTML = '<span class="app-select-label"></span><svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';

        const menu = document.createElement('div');
        menu.className = 'app-select-menu hidden';
        menu.dataset.selectId = selectId;
        menu.innerHTML = '<div class="app-select-search-wrap"><input type="text" class="app-select-search" placeholder="Buscar..."></div><div class="app-select-options custom-scroll"></div>';

        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);
        select.classList.add('app-native-select');

        select._appSelectLabel = trigger.querySelector('.app-select-label');
        select._appSelectMenu = menu;
        select._appSelectSearch = menu.querySelector('.app-select-search');
        select._appSelectOptions = menu.querySelector('.app-select-options');

        trigger.addEventListener('click', () => {
          if (select.disabled) return;
          const willOpen = menu.classList.contains('hidden');
          closeAll(selectId);
          if (willOpen) {
            renderOptions(select);
            menu.classList.remove('hidden');
            trigger.classList.add('is-open');
            window.adjustDropPosition(trigger, menu);
            select._appSelectSearch.value = '';
            select._appSelectSearch.focus();
          }
        });

        select._appSelectSearch.addEventListener('input', () => renderOptions(select));

        select._appSelectOptions.addEventListener('click', (event) => {
          const optionBtn = event.target.closest('.app-select-option');
          if (!optionBtn) return;
          const idx = Number(optionBtn.dataset.index || -1);
          const option = select.options[idx];
          if (!option || option.disabled) return;
          select.value = option.value;
          select.dispatchEvent(new Event('input', { bubbles: true }));
          select.dispatchEvent(new Event('change', { bubbles: true }));
          syncLabel(select);
          renderOptions(select);
          menu.classList.add('hidden');
          trigger.classList.remove('is-open');
        });

        select.addEventListener('change', () => {
          syncLabel(select);
          renderOptions(select);
        });

        syncLabel(select);
        renderOptions(select);
      }

      window.enhanceNativeSelects = function(root = document) {
        root.querySelectorAll('select').forEach((select) => enhanceSelect(select));
      };

      document.addEventListener('click', (event) => {
        document.querySelectorAll('.app-select-wrap').forEach((wrap) => {
          if (!wrap.contains(event.target)) {
            const menu = wrap.querySelector('.app-select-menu');
            const trigger = wrap.querySelector('.app-select-trigger');
            if (menu) menu.classList.add('hidden');
            if (trigger) trigger.classList.remove('is-open');
          }
        });
      });

      document.addEventListener('DOMContentLoaded', () => {
        window.enhanceNativeSelects(document);

        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
              if (!(node instanceof Element)) return;
              if (node.matches('select')) {
                enhanceSelect(node);
                return;
              }
              if (node.querySelectorAll) {
                window.enhanceNativeSelects(node);
              }
            });
          });
        });

        observer.observe(document.body, { childList: true, subtree: true });
      });
    })();
  </script>

</body>
</html>
