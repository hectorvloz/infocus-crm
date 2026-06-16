<aside id="sidebar" class="hidden md:flex flex-col w-72 h-full z-50 p-4 sidebar-shell">
  @php
    $s = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
    $logoLarge = $s['logo_large'] ?? $s['logo'] ?? null;
    $useCompanyLogoInSidebar = !empty($s['sidebar_use_company_logo']);
    $crmSidebarLogo = '/uploads/branding/logo-infocus-white.png';
    $sidebarLogo = $useCompanyLogoInSidebar
      ? ($logoLarge ?: $crmSidebarLogo)
      : $crmSidebarLogo;
    $sidebarLogoUrl = app_public_asset_url($sidebarLogo);
    $logoSmall = $s['logo_small'] ?? null;
    $currentUser = auth()->user();
    $can = fn(string $permission) => \App\Support\RoleAccess::can($currentUser, $permission);
    $canAny = fn(array $permissions) => \App\Support\RoleAccess::canAny($currentUser, $permissions);
    $canAccessSettings = $can('ajustes.read');
    $canAccessProjects = $can('proyectos.read');
    $canAccessSales = $canAny(['facturas.read', 'pagos.read', 'cotizaciones.read', 'productos.read']);
  @endphp
  <div class="flex-1 flex flex-col bg-slate-900 text-white rounded-[32px] shadow-2xl overflow-hidden border border-white/10">
    <!-- Header / Logo -->
    <div class="h-16 flex items-center px-4 border-b border-white/10 shrink-0 relative">
      {{-- Logo izquierda, desaparece al colapsar --}}
      <div class="flex-1 flex items-center justify-start min-w-0" data-label="INFOCUS">
        @if(!empty($sidebarLogoUrl))
          <img src="{{ $sidebarLogoUrl }}" class="h-10 max-w-[160px] object-contain object-left" alt="Infocus CRM">
        @else
          <div class="font-bold text-2xl tracking-wider text-white truncate">{{ $s['company_name'] ?? 'INFOCUS' }}</div>
        @endif
      </div>

      {{-- Botón toggle: X cuando está expandido, hamburguesa cuando está colapsado --}}
      <button id="sidebarToggle" class="absolute right-3 shrink-0 flex items-center justify-center text-white/70 hover:text-white w-9 h-9 rounded-lg hover:bg-white/10 transition-colors">
        {{-- X para cerrar (visible cuando expandido) --}}
        <svg class="w-5 h-5 sidebar-icon-close" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{-- Hamburguesa para abrir (visible cuando colapsado) --}}
        <svg class="w-5 h-5 sidebar-icon-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>

    <!-- Menu -->
    <div class="flex-1 overflow-y-auto p-3 custom-scroll">
      <nav class="space-y-2">
        @if($can('panel.read'))
          <a href="{{ route('dashboard') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('dashboard') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            <span data-label class="text-sm font-medium">Panel</span>
          </a>
        @endif

        @if($can('mis-notas.read'))
          <a href="{{ route('mis-notas.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('mis-notas.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            <span data-label class="text-sm font-medium">Mis Notas</span>
          </a>
        @endif
        
        @if($can('clientes.read'))
          <a href="{{ route('clientes.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('clientes.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
            <span data-label class="text-sm font-medium">Clientes</span>
          </a>
        @endif

        @if($can('reuniones.read'))
          <a href="{{ route('reuniones.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('reuniones.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 15h.01M12 15h.01M16 15h.01"/></svg>
            <span data-label class="text-sm font-medium">Reuniones</span>
          </a>
        @endif

        @if($can('documentos.read'))
          <a href="{{ route('documentos.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('documentos.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h4.2a2 2 0 0 1 1.4.58L13 7.5h5.5A2.5 2.5 0 0 1 21 10v7.5A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-10Z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18"/>
            </svg>
            <span data-label class="text-sm font-medium">Documentos</span>
          </a>
        @endif

        @if($canAccessProjects)
        <div class="space-y-1">
          <button type="button" data-submenu-toggle="proyectos" class="menu-link w-full group flex items-center justify-between px-3 py-3 rounded-2xl hover:bg-white/10 text-white/70 transition-all {{ request()->routeIs('proyectos.*') ? 'is-active text-slate-900' : '' }}" aria-expanded="{{ request()->routeIs('proyectos.*') ? 'true' : 'false' }}">
            <span class="flex items-center gap-3">
              <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <span data-label class="text-sm font-medium">Proyectos</span>
            </span>
            <svg class="h-4 w-4 transition-transform duration-200 {{ request()->routeIs('proyectos.*') ? 'rotate-180' : '' }}" data-label data-submenu-chevron="proyectos" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div id="submenu-proyectos" class="{{ request()->routeIs('proyectos.*') ? '' : 'hidden' }} overflow-hidden transition-all duration-300 pl-4 ml-2 border-l border-white/10 space-y-1 my-1">
            <a href="{{ route('proyectos.index', ['view' => 'kanban']) }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('proyectos.*') && request('view', 'kanban') === 'kanban' ? 'is-active' : '' }}">
              <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="7" height="7" rx="1.5"/><rect x="14" y="4" width="7" height="7" rx="1.5"/><rect x="3" y="13" width="7" height="7" rx="1.5"/><rect x="14" y="13" width="7" height="7" rx="1.5"/></svg>
              <span class="text-sm">Kanban</span>
            </a>
            <a href="{{ route('proyectos.index', ['view' => 'lista']) }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('proyectos.*') && request('view') === 'lista' ? 'is-active' : '' }}">
              <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg>
              <span class="text-sm">Lista</span>
            </a>
            <a href="{{ route('proyectos.index', ['view' => 'tareas']) }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('proyectos.*') && request('view') === 'tareas' ? 'is-active' : '' }}">
              <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12l2.7 2.7L16.5 8.5"/></svg>
              <span class="text-sm">Tareas</span>
            </a>
          </div>
        </div>
        @endif

        @if($can('leads.read'))
          <a href="{{ route('leads.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('leads.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 4h18"/><path d="M7 4v5l5 5v6l4-2v-4l5-5"/></svg>
            <span data-label class="text-sm font-medium">Leads</span>
          </a>
        @endif

        <!-- Ventas Submenu -->
        @if($canAccessSales)
        <div class="space-y-1">
          <button type="button" data-submenu-toggle="ventas" class="menu-link w-full group flex items-center justify-between px-3 py-3 rounded-2xl hover:bg-white/10 text-white/70 transition-all {{ request()->routeIs('facturas.*')||request()->routeIs('pagos.*')||request()->routeIs('cotizaciones.*')||request()->routeIs('productos.*') ? 'is-active text-slate-900' : '' }}" aria-expanded="{{ request()->routeIs('facturas.*')||request()->routeIs('pagos.*')||request()->routeIs('cotizaciones.*')||request()->routeIs('productos.*') ? 'true' : 'false' }}">
            <span class="flex items-center gap-3">
              <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
              <span data-label class="text-sm font-medium">Ventas</span>
            </span>
            <svg class="h-4 w-4 transition-transform duration-200 {{ request()->routeIs('facturas.*')||request()->routeIs('pagos.*')||request()->routeIs('cotizaciones.*')||request()->routeIs('productos.*') ? 'rotate-180' : '' }}" data-label data-submenu-chevron="ventas" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div id="submenu-ventas" class="{{ request()->routeIs('facturas.*')||request()->routeIs('pagos.*')||request()->routeIs('cotizaciones.*')||request()->routeIs('productos.*') ? '' : 'hidden' }} overflow-hidden transition-all duration-300 pl-4 ml-2 border-l border-white/10 space-y-1 my-1">
            @if($can('facturas.read'))
              <a href="{{ route('facturas.index') }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('facturas.*') ? 'is-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span class="text-sm">Facturas</span>
              </a>
            @endif
            @if($can('pagos.read'))
              <a href="{{ route('pagos.index') }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('pagos.*') ? 'is-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <span class="text-sm">Pagos</span>
              </a>
            @endif
            @if($can('cotizaciones.read'))
              <a href="{{ route('cotizaciones.index') }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('cotizaciones.*') ? 'is-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span class="text-sm">Cotizaciones</span>
              </a>
            @endif
            @if($can('productos.read'))
              <a href="{{ route('productos.index') }}" class="submenu-link flex items-center gap-3 px-3 py-2 rounded-xl text-white/60 hover:text-white hover:bg-white/5 {{ request()->routeIs('productos.*') ? 'is-active' : '' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                <span class="text-sm">Productos y Servicios</span>
              </a>
            @endif
          </div>
        </div>
        @endif

        @if($can('gastos.read'))
          <a href="{{ route('gastos.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('gastos.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span data-label class="text-sm font-medium">Gastos</span>
          </a>
        @endif

        @if($can('contratos.read'))
          <a href="{{ route('contratos.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('contratos.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span data-label class="text-sm font-medium">Contratos</span>
          </a>
        @endif

        @if($can('correo.read'))
          <a href="{{ route('correo.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('correo.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4 8 8 6 8-6"/></svg>
            <span data-label class="text-sm font-medium">Correo</span>
          </a>
        @endif

        @if($can('reportes.read'))
          <a href="{{ route('reportes.index') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 transition-all {{ request()->routeIs('reportes.*') ? 'is-active text-slate-900' : 'text-white/70' }}">
            <svg class="h-6 w-6 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span data-label class="text-sm font-medium">Reportes</span>
          </a>
        @endif
      </nav>
    </div>

    @if($canAccessSettings)
      <div class="p-3 border-t border-white/10 shrink-0">
        <a href="{{ route('settings.edit') }}" class="menu-link group flex items-center gap-3 px-3 py-3 rounded-2xl hover:bg-white/10 text-white/70 transition-all {{ request()->routeIs('settings.*') ? 'is-active text-slate-900' : '' }}">
          <svg class="h-6 w-6 shrink-0 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
          <span data-label class="text-sm font-medium">Ajustes</span>
        </a>
        <div class="appearance-toolbar hidden" style="display:none!important" role="toolbar" aria-label="Modo de apariencia" aria-hidden="true">
          <button type="button" class="appearance-mode-btn" data-appearance-mode="light" aria-label="Modo claro" title="Modo claro">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="12" r="4"/>
              <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
            <span>Claro</span>
          </button>
          <button type="button" class="appearance-mode-btn" data-appearance-mode="dark" aria-label="Modo oscuro" title="Modo oscuro">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.8A8.5 8.5 0 1 1 10.2 3a6.6 6.6 0 0 0 10.8 10.8Z"/>
            </svg>
            <span>Oscuro</span>
          </button>
        </div>
      </div>
    @endif
  </div>
</aside>

<!-- Mobile Bottom Nav -->
<div class="mobile-bottom-nav fixed bottom-3 left-3 right-3 md:hidden z-50">
  <div class="bg-slate-900 text-white rounded-2xl shadow-xl px-3 py-3 flex items-center justify-around">
    @if($can('panel.read'))
      <a href="{{ route('dashboard') }}" class="p-2 {{ request()->routeIs('dashboard') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
      </a>
    @endif
    @if($can('mis-notas.read'))
      <a href="{{ route('mis-notas.index') }}" class="p-2 {{ request()->routeIs('mis-notas.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-2.5 0-3.5 1.5-3.5 3.5 0 1 .25 1.75.75 2.25M12 3c2.5 0 3.5 1.5 3.5 3.5 0 1-.25 1.75-.75 2.25M12 3v5.75M8.5 6.5c-1 .5-1.5 1.5-1.5 2.5 0 2 1 3.5 3 4v3c0 1.5 1 2.5 2.5 2.5s2.5-1 2.5-2.5v-3c2-0.5 3-2 3-4 0-1-.5-2-1.5-2.5M6.5 11c-.5 0-1 .5-1 1v4c0 1.5 1 2.5 2.5 2.5M17.5 11c.5 0 1 .5 1 1v4c0 1.5-1 2.5-2.5 2.5"/></svg>
      </a>
    @endif
    @if($can('clientes.read'))
      <a href="{{ route('clientes.index') }}" class="p-2 {{ request()->routeIs('clientes.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
      </a>
    @endif
    @if($can('reuniones.read'))
      <a href="{{ route('reuniones.index') }}" class="p-2 {{ request()->routeIs('reuniones.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path stroke-linecap="round" d="M8 2v4M16 2v4M3 10h18"/></svg>
      </a>
    @endif
    @if($can('documentos.read'))
      <a href="{{ route('documentos.index') }}" class="p-2 {{ request()->routeIs('documentos.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h4.2a2 2 0 0 1 1.4.58L13 7.5h5.5A2.5 2.5 0 0 1 21 10v7.5A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5v-10Z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18"/>
        </svg>
      </a>
    @endif
    @if($can('proyectos.read'))
      <a href="{{ route('proyectos.index') }}" class="p-2 {{ request()->routeIs('proyectos.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      </a>
    @endif
    @if($can('facturas.read'))
      <a href="{{ route('facturas.index') }}" class="p-2 {{ request()->routeIs('facturas.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 3h10l4 4v14H7zM10 13h6M10 9h6M10 17h6"/></svg>
      </a>
    @endif
    @if($can('leads.read'))
      <a href="{{ route('leads.index') }}" class="p-2 {{ request()->routeIs('leads.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 4h18"/><path d="M7 4v5l5 5v6l4-2v-4l5-5"/></svg>
      </a>
    @endif
    @if($can('cotizaciones.read'))
      <a href="{{ route('cotizaciones.index') }}" class="p-2 {{ request()->routeIs('cotizaciones.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </a>
    @endif
    @if($can('productos.read'))
      <a href="{{ route('productos.index') }}" class="p-2 {{ request()->routeIs('productos.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </a>
    @endif
    @if($can('contratos.read'))
      <a href="{{ route('contratos.index') }}" class="p-2 {{ request()->routeIs('contratos.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </a>
    @endif
    @if($canAccessSettings)
      <a href="{{ route('settings.edit') }}" class="p-2 {{ request()->routeIs('settings.*') ? 'text-lime-300' : 'text-white/70' }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0A1.65 1.65 0 0 0 10.91 3H11a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0A1.65 1.65 0 0 0 21 10.91V11a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </a>
    @endif
  </div>
</div>
