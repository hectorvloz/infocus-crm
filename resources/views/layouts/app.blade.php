@php
  $appSettings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
  $uiTheme = $appSettings['ui_theme'] ?? 'lime';
  $decimals = !empty($appSettings['show_decimals']) ? ((int) ($appSettings['decimal_places'] ?? 2)) : 0;
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Infocus CRM')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('partials.favicon')
  <script>
    (function () {
      try {
        localStorage.setItem('infocusAppearanceMode', 'light');
        document.documentElement.setAttribute('data-color-mode', 'light');
      } catch (e) {
        document.documentElement.setAttribute('data-color-mode', 'light');
      }
    })();
  </script>
  <script>
    (function () {
      try {
        var saved = parseInt(localStorage.getItem('sidebarScrollTop') || '0', 10) || 0;
        if (saved > 0) document.documentElement.classList.add('sidebar-scroll-pending');
      } catch (e) {}
    })();
  </script>
  <!-- Flatpickr (Custom Themed) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/fontawesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/solid.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js"></script>
  <!-- Spanish Locale -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <script>
    (function applyGlobalFlatpickrConfig() {
      if (!window.flatpickr) return;

      // Locale global en espanol para todos los calendarios.
      if (window.flatpickr.l10ns && window.flatpickr.l10ns.es) {
        window.flatpickr.localize(window.flatpickr.l10ns.es);
      }

      const prevOnReady = window.flatpickr.defaultConfig.onReady;
      const readyHandlers = Array.isArray(prevOnReady)
        ? prevOnReady.filter(Boolean)
        : (prevOnReady ? [prevOnReady] : []);

      readyHandlers.push(function (_, __, instance) {
        if (!instance || !instance.altInput) return;
        instance.altInput.addEventListener('focus', () => instance.open());
        instance.altInput.addEventListener('click', () => instance.open());
      });

      window.flatpickr.defaultConfig.locale = 'es';
      window.flatpickr.defaultConfig.monthSelectorType = 'static';
      window.flatpickr.defaultConfig.disableMobile = true;
      window.flatpickr.defaultConfig.onReady = readyHandlers;

      const resolveFlatpickrInstance = (target) => {
        if (!target) return null;
        const input = target.closest('input, textarea, .flatpickr-input');
        if (!input) return null;
        if (input._flatpickr) return input._flatpickr;
        if (input.classList?.contains('flatpickr-input')) {
          const sibling = input.previousElementSibling;
          if (sibling && sibling._flatpickr) return sibling._flatpickr;
        }
        return null;
      };

      const safeOpen = (instance) => {
        if (!instance || typeof instance.open !== 'function') return;
        requestAnimationFrame(() => {
          try { instance.open(); } catch (_) {}
        });
      };

      document.addEventListener('click', (event) => {
        const instance = resolveFlatpickrInstance(event.target);
        safeOpen(instance);
      }, true);

      document.addEventListener('focusin', (event) => {
        const instance = resolveFlatpickrInstance(event.target);
        safeOpen(instance);
      }, true);

      // Flatpickr necesita recibir los clicks de dias y flechas. No interceptamos
      // eventos del calendario aqui porque este listener corre en captura global.
    })();
  </script>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
        --accent-100: #f7fee7;
        --accent-300: #ecfe88;
        --accent-400: #d9f99d;
        --accent-500: #84cc16;
        --accent-600: #65a30d;
        --accent-700: #4d7c0f;
        --accent-ring: 236, 254, 136;
        --accent-secondary-100: #dbeafe;
        --accent-secondary-300: #93c5fd;
        --accent-secondary-500: #3b82f6;
        --accent-secondary-700: #1d4ed8;
    }
    body[data-theme="sky"] {
        --accent-100: #e0f2fe;
        --accent-300: #bae6fd;
        --accent-400: #7dd3fc;
        --accent-500: #38bdf8;
        --accent-600: #0284c7;
        --accent-700: #0369a1;
        --accent-ring: 125, 211, 252;
        --accent-secondary-100: #e0f2fe;
        --accent-secondary-300: #7dd3fc;
        --accent-secondary-500: #0ea5e9;
        --accent-secondary-700: #0369a1;
    }
    body[data-theme="violet"] {
        --accent-100: #f5f3ff;
        --accent-300: #ddd6fe;
        --accent-400: #c4b5fd;
        --accent-500: #8b5cf6;
        --accent-600: #7c3aed;
        --accent-700: #6d28d9;
        --accent-ring: 196, 181, 253;
        --accent-secondary-100: #ede9fe;
        --accent-secondary-300: #c4b5fd;
        --accent-secondary-500: #8b5cf6;
        --accent-secondary-700: #6d28d9;
    }
    body[data-theme="amber"] {
        --accent-100: #fef3c7;
        --accent-300: #fde68a;
        --accent-400: #fcd34d;
        --accent-500: #f59e0b;
        --accent-600: #d97706;
        --accent-700: #b45309;
        --accent-ring: 252, 211, 77;
        --accent-secondary-100: #fef3c7;
        --accent-secondary-300: #fcd34d;
        --accent-secondary-500: #f59e0b;
        --accent-secondary-700: #b45309;
    }
    body[data-theme="rose"] {
        --accent-100: #ffe4e6;
        --accent-300: #fecdd3;
        --accent-400: #fda4af;
        --accent-500: #f43f5e;
        --accent-600: #e11d48;
        --accent-700: #be123c;
        --accent-ring: 253, 164, 175;
        --accent-secondary-100: #ffe4e6;
        --accent-secondary-300: #fda4af;
        --accent-secondary-500: #f43f5e;
        --accent-secondary-700: #be123c;
    }
    body[data-theme="black"] {
        --accent-100: #f1f5f9;
        --accent-300: #0f172a;
        --accent-400: #111827;
        --accent-500: #0b0f1a;
        --accent-600: #0f172a;
        --accent-700: #0b1020;
        --accent-ring: 15, 23, 42;
        --accent-secondary-100: #e2e8f0;
        --accent-secondary-300: #64748b;
        --accent-secondary-500: #1f2937;
        --accent-secondary-700: #0f172a;
    }
    .bg-lime-300 { background-color: var(--accent-300) !important; }
    .bg-lime-400 { background-color: var(--accent-400) !important; }
    .bg-lime-500 { background-color: var(--accent-500) !important; }
    .text-lime-600 { color: var(--accent-600) !important; }
    .border-lime-300 { border-color: var(--accent-300) !important; }
    .hover\:bg-lime-400:hover { background-color: var(--accent-400) !important; }
    .bg-\[\#ecfe88\] { background-color: var(--accent-300) !important; }
    .hover\:bg-\[\#d9ea76\]:hover { background-color: var(--accent-400) !important; }
    .text-blue-500 { color: var(--accent-secondary-500) !important; }
    .text-blue-600 { color: var(--accent-secondary-700) !important; }
    .text-blue-700 { color: var(--accent-secondary-700) !important; }
    .bg-blue-50 { background-color: var(--accent-secondary-100) !important; }
    .reminder-check-burst {
      position: absolute;
      inset: -11px;
      pointer-events: none;
      z-index: 20;
    }
    .reminder-check-burst i {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      border-radius: 9999px;
      background: var(--accent-300);
      box-shadow: 0 0 10px rgba(var(--accent-ring), 0.9);
      transform: translate(-50%, -50%) scale(0.6);
      opacity: 0;
      animation: reminderCheckSpark .58s cubic-bezier(.18,.84,.36,1) forwards;
      animation-delay: var(--delay, 0ms);
    }
    .reminder-check-burst i:nth-child(3n) {
      width: 4px;
      height: 4px;
      background: #ffffff;
      box-shadow: 0 0 8px rgba(236, 254, 136, 0.95);
    }
    .reminder-check-burst i:nth-child(4n) {
      width: 6px;
      height: 6px;
      background: #0f172a;
      box-shadow: none;
    }
    @keyframes reminderCheckSpark {
      0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.45);
      }
      18% {
        opacity: 1;
      }
      100% {
        opacity: 0;
        transform: translate(calc(-50% + var(--x)), calc(-50% + var(--y))) scale(0.1);
      }
    }
    .bg-blue-100 { background-color: var(--accent-secondary-100) !important; }
    .bg-blue-200 { background-color: var(--accent-secondary-300) !important; }
    .border-blue-100 { border-color: var(--accent-secondary-100) !important; }
    .border-blue-200 { border-color: var(--accent-secondary-300) !important; }
    .hover\:text-blue-600:hover { color: var(--accent-secondary-700) !important; }
    .hover\:bg-blue-50:hover { background-color: var(--accent-secondary-100) !important; }
    .primary-add-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.6rem 1rem;
      border-radius: 0.85rem;
      background: var(--accent-300);
      color: #0f172a;
      font-weight: 800;
      line-height: 1;
      transition: background-color 0.2s ease, transform 0.15s ease;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }
    .primary-add-btn:hover {
      background: var(--accent-400);
    }
    .primary-add-btn:active {
      transform: translateY(1px);
    }
    .primary-add-btn svg {
      flex: 0 0 auto;
    }
    body[data-theme="black"] .bg-lime-300,
    body[data-theme="black"] .bg-\[\#ecfe88\] {
        color: #f8fafc !important;
    }
    input:focus, textarea:focus, select:focus {
        outline: none !important;
        border-color: var(--accent-400) !important;
        box-shadow: 0 0 0 3px rgba(var(--accent-ring), 0.45) !important;
    }
    /* Toast Animations */
    @keyframes toast-enter {
        0% { transform: translateY(-100%); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    @keyframes toast-leave {
        0% { transform: translateY(0); opacity: 1; }
        100% { transform: translateY(-20px); opacity: 0; }
    }
    .toast-enter { animation: toast-enter 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .toast-leave { animation: toast-leave 0.3s ease-in forwards; }

    /* Global progress fill animation */
    .progress-fill-anim {
      transform-origin: left center;
      will-change: width, transform;
      transition: width 0.55s cubic-bezier(0.22, 1, 0.36, 1), background-color 0.3s ease;
      animation: progress-fill-reveal 0.48s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes progress-fill-reveal {
      0% { transform: scaleX(0.12); opacity: 0.65; }
      100% { transform: scaleX(1); opacity: 1; }
    }

    /* Custom Flatpickr Theme - Lime/Bento Style */
    .flatpickr-calendar {
        background: #fff;
        border: 1px solid #f1f5f9; /* slate-100 */
        border-radius: 1.25rem;
        box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.10);
      padding: 0.55rem;
        font-family: inherit;
      width: 262px !important;
      min-width: 262px !important;
      max-width: calc(100vw - 24px) !important;
      z-index: 2147483400 !important;
    }
    .flatpickr-calendar.open,
    .flatpickr-calendar.inline,
    .flatpickr-calendar.static {
      z-index: 2147483400 !important;
    }
    .flatpickr-calendar:before, .flatpickr-calendar:after { display: none !important; }
    
    .flatpickr-months {
        padding-bottom: 0.25rem;
        align-items: center;
    }
    
    .flatpickr-current-month {
        font-size: 0.875rem;
        font-weight: 800;
        color: #0f172a;
        padding-top: 0;
        left: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
    }
    .flatpickr-current-month .cur-month,
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        font-weight: 800;
        appearance: none;
        -webkit-appearance: none;
        font-size: 0.875rem;
        padding: 0;
        background: transparent;
        border: 0;
        pointer-events: none;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months:hover {
        background: transparent;
    }
    .flatpickr-current-month .numInputWrapper {
      width: 4ch;
    }
    .flatpickr-current-month .numInputWrapper span {
      display: none;
    }
    .flatpickr-current-month input.cur-year {
        font-weight: 800;
        font-size: 0.875rem;
        background: transparent;
        border: 0;
        padding: 0;
        margin: 0;
        pointer-events: none;
        -moz-appearance: textfield;
        appearance: textfield;
    }
    .flatpickr-current-month input.cur-year::-webkit-outer-spin-button,
    .flatpickr-current-month input.cur-year::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .flatpickr-prev-month, .flatpickr-next-month {
        fill: #64748b !important;
        top: 0.75rem !important;
    }
    .flatpickr-prev-month:hover, .flatpickr-next-month:hover {
        fill: #0f172a !important;
        color: #0f172a !important;
    }

    .flatpickr-weekdays {
        margin-bottom: 0.15rem;
    }
    span.flatpickr-weekday {
        color: #94a3b8;
        font-weight: 700;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .flatpickr-days,
    .flatpickr-days .dayContainer {
      width: 248px !important;
      min-width: 248px !important;
      max-width: 248px !important;
    }

    .flatpickr-day {
        border-radius: 9999px;
        color: #334155;
        font-weight: 600;
        font-size: 0.78rem;
        height: 28px;
        line-height: 28px;
        margin-top: 1px;
        border: 1px solid transparent;
        transition: all 0.15s ease;
        text-align: center;
        padding-left: 0;
        width: 35px;
        max-width: 35px;
    }

    @media (max-width: 640px) {
      .flatpickr-calendar {
        width: 246px !important;
        min-width: 246px !important;
        max-width: calc(100vw - 16px) !important;
        position: fixed !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
      }

      .flatpickr-days,
      .flatpickr-days .dayContainer {
        width: 232px !important;
        min-width: 232px !important;
        max-width: 232px !important;
      }

      .flatpickr-day {
        width: 33px;
        max-width: 33px;
        height: 27px;
        line-height: 27px;
        font-size: 0.75rem;
      }
    }

    /* Keep modals above floating UI and fully scrollable on mobile */
    [id$="Modal"].fixed.inset-0,
    [aria-modal="true"].fixed.inset-0 {
      z-index: 160 !important;
      isolation: isolate;
    }

    @media (max-width: 768px) {
      [id$="Modal"].fixed.inset-0 > .fixed.inset-0.overflow-y-auto,
      [id$="Modal"].fixed.inset-0 > .fixed.inset-0.z-10.overflow-y-auto,
      [id$="Modal"].fixed.inset-0 > .fixed.inset-0.flex.items-center.justify-center,
      [id$="Modal"].fixed.inset-0 > .fixed.inset-0.z-10.flex.items-center.justify-center,
      [aria-modal="true"].fixed.inset-0 > .fixed.inset-0.overflow-y-auto,
      [aria-modal="true"].fixed.inset-0 > .fixed.inset-0.z-10.overflow-y-auto,
      [aria-modal="true"].fixed.inset-0 > .fixed.inset-0.flex.items-center.justify-center,
      [aria-modal="true"].fixed.inset-0 > .fixed.inset-0.z-10.flex.items-center.justify-center {
        align-items: flex-start !important;
        justify-content: center !important;
        padding: 0.75rem !important;
        overflow-y: auto !important;
        overscroll-behavior: contain;
      }

      [id$="Modal"].fixed.inset-0 [class*="max-w-"],
      [aria-modal="true"].fixed.inset-0 [class*="max-w-"] {
        max-height: calc(100dvh - 1.5rem) !important;
        overflow-y: auto !important;
      }
    }
    
    /* Hover State */
    .flatpickr-day:hover, 
    .flatpickr-day.prevMonthDay:hover, 
    .flatpickr-day.nextMonthDay:hover, 
    .flatpickr-day:focus {
        background: var(--accent-100);
        border-color: transparent;
        color: #0f172a;
    }

    /* Selected / Range Start / Range End */
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange,
    .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange,
    .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus,
    .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover {
        background: var(--accent-300) !important;
        color: #0f172a !important; /* Dark text for contrast */
        border-color: var(--accent-300) !important;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(var(--accent-ring), 0.4);
    }

    /* In Range (Middle) */
    .flatpickr-day.inRange,
    .flatpickr-day.prevMonthDay.inRange,
    .flatpickr-day.nextMonthDay.inRange,
    .flatpickr-day.today.inRange {
        background: #f7fee7 !important; /* lime-50 */
        border-color: transparent !important;
        color: #0f172a;
        box-shadow: -5px 0 0 #f7fee7, 5px 0 0 #f7fee7; /* Connect the range visually */
    }

    /* Today */
    .flatpickr-day.today {
        border-color: #e2e8f0;
        background: transparent;
    }
    .flatpickr-day.today:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    
    /* Range Ends (Rounding fix) */
    .flatpickr-day.startRange {
        border-radius: 50px 0 0 50px !important;
    }
    .flatpickr-day.endRange {
        border-radius: 0 50px 50px 0 !important;
    }
    .flatpickr-day.startRange.endRange {
        border-radius: 50px !important;
    }

    .flatpickr-calendar.reminder-date-calendar {
      width: 222px !important;
      min-width: 222px !important;
      max-width: calc(100vw - 24px) !important;
      border-radius: 1rem;
      padding: 0.42rem;
      box-shadow: 0 10px 24px -6px rgba(15, 23, 42, 0.18);
    }
    .flatpickr-calendar.reminder-date-calendar .flatpickr-months {
      padding-bottom: 0.1rem;
    }
    .flatpickr-calendar.reminder-date-calendar .flatpickr-current-month,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-current-month .cur-month,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-current-month input.cur-year {
      font-size: 0.78rem;
    }
    .flatpickr-calendar.reminder-date-calendar .flatpickr-prev-month,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-next-month {
      top: 0.55rem !important;
      height: 24px;
      padding: 4px;
    }
    .flatpickr-calendar.reminder-date-calendar span.flatpickr-weekday {
      font-size: 0.56rem;
    }
    .flatpickr-calendar.reminder-date-calendar .flatpickr-days,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-rContainer,
    .flatpickr-calendar.reminder-date-calendar .flatpickr-days .dayContainer {
      width: 208px !important;
      min-width: 208px !important;
      max-width: 208px !important;
    }
    .flatpickr-calendar.reminder-date-calendar .flatpickr-day {
      width: 29px;
      max-width: 29px;
      height: 24px;
      line-height: 24px;
      font-size: 0.68rem;
      margin-top: 0;
    }

    /* --- CUSTOM SELECT/DROPDOWN STYLING (Flat Design) --- */
    /* Container for the select */
    .custom-select-wrapper {
        position: relative;
        display: block;
    }
    /* The actual select element (hidden but accessible) */
    .custom-select-wrapper select {
        display: none; /* We will use JS to replace it, or style it heavily */
    }
    
    /* Native Select Styling */
    select.form-select {
        appearance: none;
        -webkit-appearance: none;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        padding-right: 2.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #0f172a;
        width: 100%;
        transition: all 0.2s ease;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
    }

    /* Standard Input Styling (No Arrow) */
    .form-input {
        appearance: none;
        -webkit-appearance: none;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #0f172a;
        width: 100%;
        transition: all 0.2s ease;
    }

    textarea {
        padding: 0.9rem 1rem;
        line-height: 1.55;
    }

    textarea.form-input:not([class*="px-"]):not([class*="pl-"]) {
        padding: 0.9rem 1rem;
    }

    .reminder-text-frame::before {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      top: 0.1rem;
      bottom: 0.05rem;
      min-height: 1.85rem;
      border-radius: 0.75rem;
      background: rgba(244, 253, 172, 0.45);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.12s ease;
      z-index: 0;
      box-shadow: none;
    }

    .reminder-text-frame input,
    .reminder-text-frame textarea {
      border: 0 !important;
      box-shadow: none !important;
      outline: 0 !important;
      -webkit-appearance: none;
      appearance: none;
    }

    .reminder-text-frame textarea {
      min-height: 1.95rem;
      resize: none;
      overflow: hidden;
      scrollbar-width: none;
    }

    .reminder-text-frame textarea::-webkit-scrollbar {
      display: none;
    }

    .reminder-text-frame:focus-within::before {
      opacity: 1;
    }

      /* Filter Pills (Global) */
      .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
      }
      .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.85rem;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
      }
      .filter-pill:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
      }
      .filter-pill:focus-within {
        border-color: #94a3b8;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.15);
        background: #fff;
      }
      .filter-pill input,
      .filter-pill select {
        border: none;
        background: transparent;
        outline: none;
        padding: 0;
        font: inherit;
        color: inherit;
        min-width: 0;
      }
      .filter-pill select {
        padding-right: 1.25rem;
        appearance: none;
        -webkit-appearance: none;
        background-image: none;
      }
      .filter-pill .filter-icon,
      .filter-pill .filter-caret {
        color: #94a3b8;
        flex-shrink: 0;
      }
      .filter-pill-lg {
        padding: 0.65rem 1rem;
      }
      .filter-pill-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-weight: 700;
        background: #ecfe88;
        color: #0f172a;
        border: 1px solid transparent;
        transition: background 0.2s ease;
      }
      .filter-pill-action:hover {
        background: #d9ef60;
      }

    /* ─── Global table row hover ─────────────────────── */
      tbody tr {
        transition: background-color 0.13s ease;
      }
      tbody tr:not(.no-hover):hover td,
      tbody tr:not(.no-hover):hover th {
        background-color: #f8fafc;
      }
      tbody tr.bg-rose-50:not(.no-hover):hover td,
      tbody tr.bg-rose-50:not(.no-hover):hover th {
        background-color: #fce7e9 !important;
      }

    /* Nombres clickeables en listados (empresa/proyecto) */
    table tbody td a.underline {
      color: #4d8f10;
      text-decoration-color: rgba(77, 143, 16, 0.5);
      text-underline-offset: 2px;
      transition: color 0.15s ease, text-decoration-color 0.15s ease;
    }
    table tbody td a.underline:hover {
      color: #3f7a0f;
      text-decoration-color: rgba(63, 122, 15, 0.65);
    }

    /* Global interactive cursor */
    a[href],
    button:not(:disabled),
    [type="button"]:not(:disabled),
    [type="submit"]:not(:disabled),
    [type="reset"]:not(:disabled),
    [role="button"]:not([aria-disabled="true"]),
    summary,
    label[for],
    input[type="checkbox"]:not(:disabled),
    input[type="radio"]:not(:disabled),
    select:not(:disabled) {
      cursor: pointer;
    }

    button:disabled,
    [type="button"]:disabled,
    [type="submit"]:disabled,
    [type="reset"]:disabled,
    [aria-disabled="true"] {
      cursor: not-allowed;
    }

    /* Hide Spin Buttons for Numbers */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    
    select.form-select:focus,
    .form-input:focus {
        border-color: #ecfe88; /* Lime border */
        box-shadow: 0 0 0 3px rgba(236, 254, 136, 0.3); /* Lime ring */
        outline: none;
    }

    /* Autocomplete / Datalist styling is tricky cross-browser. 
       We will use a lightweight custom dropdown structure for "Client" and "Product" fields
       to achieve the specific design requested. 
    */
    
    /* Custom Dropdown Component */
    .dropdown-container {
        position: relative;
        width: 100%;
    }
    
    .dropdown-trigger {
      width: 100%;
      background: #fff;
      border: 1px solid #dbe4f0;
      border-radius: 1rem;
      min-height: 3.1rem;
      padding: 0.8rem 1rem;
      font-size: 0.875rem;
      font-weight: 700;
      color: #334155;
      text-align: left;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      cursor: pointer;
      transition: all 0.2s;
      box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    
    .dropdown-trigger:focus-within,
    .dropdown-trigger.active {
      border-color: #d9f99d;
      box-shadow: 0 0 0 4px rgba(236, 254, 136, 0.32);
    }

    .dropdown-trigger input {
      border: none;
      outline: none;
      width: 100%;
      background: transparent;
      font-weight: inherit;
      color: inherit;
      padding: 0;
      margin: 0;
      line-height: 1.2;
    }

    .dropdown-trigger input::placeholder {
      color: #94a3b8;
    }

    .dropdown-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      width: 100%;
      background: #ffffff;
      border: 1px solid #eef2f7;
      border-radius: 1rem;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
      z-index: 50;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      overflow: hidden;
      max-height: 280px;
      overflow-y: auto;
      padding: 0.35rem;
    }
    
    .dropdown-menu.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-menu.drop-up {
      top: auto;
      bottom: calc(100% + 8px);
      transform-origin: bottom;
    }

    .dropdown-menu.drop-up.open {
      transform: translateY(0);
    }

    .dropdown-item {
      padding: 0.72rem 0.95rem;
      font-size: 0.875rem;
      color: #334155;
      cursor: pointer;
      border-radius: 0.8rem;
      transition: all 0.15s;
      font-weight: 500;
    }

    .dropdown-item:hover {
      background: #ecfe88;
      color: #0f172a;
    }

    .dropdown-item.selected {
      background: #f8fafc;
      color: #0f172a;
      font-weight: 700;
    }

    .dropdown-item strong {
      color: #0f172a;
      font-weight: 800;
    }

    .app-select-wrap {
      position: relative;
      width: 100%;
    }

    .app-native-select {
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0, 0, 0, 0) !important;
      white-space: nowrap !important;
      border: 0 !important;
    }

    .app-select-trigger {
      width: 100%;
      min-height: 2.5rem;
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      border: 1px solid #dbe4f0;
      border-radius: 0.85rem;
      background: #fff;
      padding: 0.52rem 0.8rem;
      color: #334155;
      font-size: 0.8125rem;
      font-weight: 600;
      text-align: left;
      box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
      transition: all 0.2s ease;
    }

    .app-select-trigger:hover {
      background: #fff;
    }

    .app-select-trigger:focus {
      outline: none;
      border-color: #d9f99d;
      box-shadow: 0 0 0 4px rgba(236, 254, 136, 0.32);
    }

    .app-select-trigger.is-open {
      border-color: #d9f99d;
      box-shadow: 0 0 0 4px rgba(236, 254, 136, 0.32);
    }

    .app-select-trigger.is-disabled {
      background: #f8fafc;
      color: #94a3b8;
      cursor: not-allowed;
    }

    .app-select-trigger.compact {
      min-height: 2.5rem;
      border-radius: 0.85rem;
      padding: 0.52rem 0.8rem;
      font-size: 0.8125rem;
      font-weight: 600;
    }

    .app-select-trigger.has-leading-icon {
      padding-left: 2.35rem;
    }

    .app-select-trigger.compact.has-leading-icon {
      padding-left: 2.35rem;
    }

    .app-select-label {
      min-width: 0;
      flex: 1;
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
      border: 1px solid #eef2f7;
      border-radius: 1rem;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
      overflow: hidden;
    }

    .app-select-menu.hidden {
      display: none;
    }

    .app-select-menu.drop-up {
      top: auto;
      bottom: calc(100% + 8px);
    }

    /* Product options dropdown flip support */
    .product-options.drop-up {
      top: auto !important;
      bottom: calc(100% + 4px);
    }

    .app-select-search-wrap {
      padding: 0.65rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .app-select-search {
      width: 100%;
      border: 0;
      border-radius: 0.8rem;
      background: #f8fafc;
      padding: 0.82rem 1rem;
      color: #334155;
      font-size: 0.875rem;
      line-height: 1.2;
    }

    .app-select-search:focus {
      outline: none;
      box-shadow: inset 0 0 0 1px #e2e8f0;
    }

    .app-select-search::placeholder {
      color: #94a3b8;
    }

    .app-select-options {
      max-height: 280px;
      overflow-y: auto;
      padding: 0.35rem;
    }

    .app-select-option {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      border: 0;
      background: transparent;
      border-radius: 0.8rem;
      padding: 0.72rem 0.95rem;
      color: #334155;
      font-size: 0.875rem;
      font-weight: 500;
      text-align: left;
      transition: all 0.15s ease;
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
      padding: 0.8rem 1rem;
      color: #94a3b8;
      font-size: 0.8125rem;
    }

    body.timer-fullscreen-open {
      overflow: hidden;
    }

    body.header-timer-picker-open {
      overflow: hidden;
    }

    body.timer-fullscreen-open header {
      z-index: 1 !important;
    }

    body.timer-fullscreen-open #globalTimerFullscreenPanel {
      z-index: 2147483650 !important;
    }
  </style>
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
  <script>
    // --- Shared flip-up utility for all dropdowns ---
    window.adjustDropPosition = function(triggerEl, menuEl) {
      menuEl.classList.remove('drop-up');
      const triggerRect = triggerEl.getBoundingClientRect();
      const menuHeight = menuEl.offsetHeight || 300;
      const spaceBelow = window.innerHeight - triggerRect.bottom;
      const spaceAbove = triggerRect.top;
      if (spaceBelow < menuHeight + 12 && spaceAbove > spaceBelow) {
        menuEl.classList.add('drop-up');
      }
    };

    // --- Global Custom Dropdown Logic ---
    window.setupDropdown = function(id, onSelect) {
      const container = document.getElementById(id);
      if(!container) return;
      const trigger = container.querySelector('.dropdown-trigger');
      const valueInput = container.querySelector('.dropdown-value-input') || container.querySelector('.dropdown-trigger input[type="text"]');
      const searchInput = container.querySelector('.dropdown-search-input') || valueInput;
      const menu = container.querySelector('.dropdown-menu');
      const items = container.querySelectorAll('.dropdown-item');

      if (!valueInput || !menu) return;

      const filterItems = () => {
        const val = String(searchInput?.value || '').toLowerCase();
        const currentItems = container.querySelectorAll('.dropdown-item');
        currentItems.forEach(item => {
          const text = item.innerText.toLowerCase();
          item.style.display = text.includes(val) ? 'block' : 'none';
        });
        menu.classList.add('open');
      };

      if (searchInput) {
        searchInput.addEventListener('input', filterItems);
      }

      const openMenu = () => {
        menu.classList.add('open');
        window.adjustDropPosition(container, menu);
        if (searchInput && searchInput !== valueInput) {
          searchInput.value = '';
          filterItems();
          searchInput.focus();
        }
      };

      if (trigger) {
        trigger.addEventListener('click', (e) => {
          if (e.target === searchInput) return;
          e.preventDefault();
          openMenu();
        });
      }

      valueInput.addEventListener('focus', openMenu);
      valueInput.addEventListener('click', openMenu);

        // Click Item
        // specific function to bind click events to items (useful for dynamic lists)
        const bindItems = (nodeList) => {
            nodeList.forEach(item => {
                // remove old listeners to avoid duplicates if re-binding? 
                // easier to just use a flag or clone, but for now simple addEventListener
                // To prevent multiple listeners, we can rely on the fact that we usually rebuild the list.
                // For static lists, this runs once.
                item.onclick = () => {
                     // Check if it's a placeholder
                    if(item.classList.contains('italic')) return;

                    valueInput.value = item.dataset.value || item.innerText.trim();
                    if (searchInput && searchInput !== valueInput) {
                      searchInput.value = '';
                    }
                    if (onSelect) onSelect(item);
                    menu.classList.remove('open');
                };
            });
        };
        
        bindItems(items);

        // Outside Click
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                menu.classList.remove('open');
            }
        });

        // Return a way to re-bind items (for dynamic lists)
        return {
            updateItems: () => {
                const newItems = container.querySelectorAll('.dropdown-item');
                bindItems(newItems);
            }
        };
    };

      // --- Global Custom Select Replacement ---
      (function() {
        const layoutClassPrefixes = ['w-', 'min-w-', 'max-w-', 'flex-', 'basis-', 'grow', 'shrink', 'mt-', 'mb-', 'ml-', 'mr-', 'mx-', 'my-', 'self-', 'justify-self-', 'col-span-'];

        function escapeHtml(text) {
          return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        function shouldEnhance(select) {
          if (!select) return false;
          if (select.dataset.appSelectEnhanced === '1') return false;
          if (select.dataset.nativeSelect === '1') return false;
          if (select.closest('.flatpickr-calendar')) return false;
          if (select.multiple || Number(select.size || 1) > 1) return false;
          if (select.classList.contains('hidden') || select.hidden) return false;
          return true;
        }

        function closeAllEnhancedSelects(exceptId) {
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

        function syncWrapperState(select) {
          const wrapper = select._appSelectWrapper;
          const trigger = select._appSelectTrigger;
          if (!wrapper || !trigger) return;
          if (select.disabled) {
            trigger.classList.add('is-disabled');
            trigger.disabled = true;
          } else {
            trigger.classList.remove('is-disabled');
            trigger.disabled = false;
          }
        }

        function getOptions(select) {
          return Array.from(select.options || [])
            .map((option, index) => ({
              index,
              value: String(option.value ?? ''),
              label: String(option.textContent || '').trim(),
              disabled: !!option.disabled,
              hidden: !!option.hidden || option.style.display === 'none',
              selected: !!option.selected,
            }))
            .filter((option) => !option.hidden);
        }

        function renderEnhancedOptions(select) {
          const optionsHost = select._appSelectOptions;
          const searchInput = select._appSelectSearch;
          if (!optionsHost || !searchInput) return;

          const selectedValue = String(select.value ?? '');
          const query = String(searchInput.value || '').trim().toLowerCase();
          const options = getOptions(select).filter((option) => option.label.toLowerCase().includes(query));

          if (!options.length) {
            optionsHost.innerHTML = '<div class="app-select-empty">Sin resultados</div>';
            return;
          }

          optionsHost.innerHTML = options.map((option) => {
            const isSelected = option.value === selectedValue;
            const disabledClass = option.disabled ? ' opacity-50 cursor-not-allowed' : '';
            const optionContent = typeof select._appSelectOptionHtml === 'function'
              ? select._appSelectOptionHtml(option, isSelected)
              : `<span>${escapeHtml(option.label)}</span>`;
            return `<button type="button" data-index="${option.index}" class="app-select-option ${isSelected ? 'is-selected' : ''}${disabledClass}">${optionContent}${isSelected ? '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : ''}</button>`;
          }).join('');
        }

        function syncEnhancedLabel(select) {
          const label = select._appSelectLabel;
          if (!label) return;
          const current = select.options[select.selectedIndex] || select.options[0];
          if (typeof select._appSelectLabelHtml === 'function') {
            label.innerHTML = select._appSelectLabelHtml(current);
          } else {
            label.textContent = current ? current.textContent.trim() : 'Seleccionar...';
          }
        }

        function enhanceSelect(select) {
          if (!shouldEnhance(select)) return;

          const selectId = select.id || `app-select-${Math.random().toString(36).slice(2, 10)}`;
          if (!select.id) select.id = selectId;
          select.dataset.appSelectEnhanced = '1';

          const wrapper = document.createElement('div');
          wrapper.className = 'app-select-wrap';
          select.classList.forEach((className) => {
            if (layoutClassPrefixes.some((prefix) => className === prefix || className.startsWith(prefix))) {
              wrapper.classList.add(className);
            }
          });

            const compact = /text-xs|text-sm|bg-transparent|border-none|h-9|py-1|py-2/.test(select.className);
            const hasLeadingIcon = !!(select.parentElement && Array.from(select.parentElement.children).some((child) => child !== select && child.classList && child.classList.contains('absolute')));

          const trigger = document.createElement('button');
          trigger.type = 'button';
            trigger.className = `app-select-trigger ${compact ? 'compact' : ''} ${hasLeadingIcon ? 'has-leading-icon' : ''}`;
          trigger.dataset.selectId = selectId;
          trigger.innerHTML = '<span class="app-select-label"></span><svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';

          const menu = document.createElement('div');
          menu.className = 'app-select-menu hidden';
          menu.dataset.selectId = selectId;
          menu.innerHTML = '<div class="app-select-search-wrap"><input type="text" class="app-select-search" placeholder="Buscar..."></div><div class="app-select-options custom-scroll"></div>';

          select.parentNode.insertBefore(wrapper, select);
          wrapper.appendChild(select);
          wrapper.appendChild(trigger);
          wrapper.appendChild(menu);

          select.classList.add('app-native-select');

          const label = trigger.querySelector('.app-select-label');
          const search = menu.querySelector('.app-select-search');
          const optionsHost = menu.querySelector('.app-select-options');

          select._appSelectWrapper = wrapper;
          select._appSelectTrigger = trigger;
          select._appSelectLabel = label;
          select._appSelectMenu = menu;
          select._appSelectSearch = search;
          select._appSelectOptions = optionsHost;

          trigger.addEventListener('click', () => {
            if (select.disabled) return;
            const willOpen = menu.classList.contains('hidden');
            closeAllEnhancedSelects(selectId);
            if (willOpen) {
              renderEnhancedOptions(select);
              menu.classList.remove('hidden');
              trigger.classList.add('is-open');
              window.adjustDropPosition(trigger, menu);
              search.value = '';
              search.focus();
            }
          });

          search.addEventListener('input', () => renderEnhancedOptions(select));

          optionsHost.addEventListener('click', (event) => {
            const optionButton = event.target.closest('.app-select-option');
            if (!optionButton) return;
            const optionIndex = Number(optionButton.dataset.index || -1);
            const option = select.options[optionIndex];
            if (!option || option.disabled) return;
            select.value = option.value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncEnhancedLabel(select);
            renderEnhancedOptions(select);
            menu.classList.add('hidden');
            trigger.classList.remove('is-open');
          });

          select.addEventListener('change', () => {
            syncEnhancedLabel(select);
            renderEnhancedOptions(select);
            syncWrapperState(select);
          });

          const observer = new MutationObserver(() => {
            syncEnhancedLabel(select);
            renderEnhancedOptions(select);
            syncWrapperState(select);
          });
          observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });

          syncEnhancedLabel(select);
          renderEnhancedOptions(select);
          syncWrapperState(select);
        }

        window.enhanceNativeSelects = function(root = document) {
          root.querySelectorAll('select').forEach((select) => enhanceSelect(select));
        };

        document.addEventListener('click', (event) => {
          document.querySelectorAll('.app-select-wrap').forEach((wrapper) => {
            if (!wrapper.contains(event.target)) {
              const select = wrapper.querySelector('select[data-app-select-enhanced="1"]');
              if (!select || !select._appSelectMenu || !select._appSelectTrigger) return;
              select._appSelectMenu.classList.add('hidden');
              select._appSelectTrigger.classList.remove('is-open');
            }
          });
        });

        document.addEventListener('DOMContentLoaded', () => {
          window.enhanceNativeSelects(document);

          const bodyObserver = new MutationObserver((mutations) => {
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

          bodyObserver.observe(document.body, { childList: true, subtree: true });
        });
      })();

    // Anti-flicker: Apply collapsed state immediately if saved
    try {
      if (window.matchMedia('(min-width: 768px)').matches && localStorage.getItem('sidebarCollapsed') === '1') {
        document.documentElement.classList.add('sidebar-is-collapsed');
      }
    } catch(e) {}
  </script>
  <style>
    .segmented-control{position:relative;border-radius:9999px;background:linear-gradient(135deg,rgba(231,255,116,.45),rgba(231,255,116,.25));box-shadow:inset 0 0 0 1px rgba(12,18,20,.06),0 8px 30px rgba(0,0,0,.06);backdrop-filter:blur(10px);padding:4px}
    .segmented-control .segmented-highlight{position:absolute;top:var(--seg-pad,4px);left:var(--seg-pad,4px);height:calc(100% - (var(--seg-pad,4px)*2));width:calc(var(--seg-w,50%) - (var(--seg-pad,4px)*2));transform:translateX(var(--seg-x,0px));border-radius:9999px;background:radial-gradient(120% 120% at 50% 10%,rgba(255,255,255,.9),rgba(255,255,255,.6));box-shadow:0 10px 30px rgba(0,0,0,.09), inset 0 0 0 1px rgba(12,18,20,.06);transition:transform .28s cubic-bezier(.2,.8,.2,1),width .28s cubic-bezier(.2,.8,.2,1),background .2s ease}
    .segmented-control .seg-btn{position:relative;z-index:1;padding:.5rem .875rem;border-radius:9999px;font-weight:700;color:#0B0F10;opacity:.8;transition:opacity .2s ease}
    .segmented-control .seg-btn.is-active{opacity:1}
    .menu-link.is-active{background:rgba(201,249,107,0.18);color:#c9f96b;border:1px solid rgba(201,249,107,0.3)}
    .menu-link.is-active:hover{background:rgba(201,249,107,0.22);color:#c9f96b}
    .submenu-link.is-active{color:#ecfe88}
    .submenu-link.is-active:hover{color:#ecfe88}
    /* Base fallback para evitar parpadeo azul/morado de links mientras cargan estilos */
    #sidebar a,
    #sidebar a:visited,
    #sidebar a:hover,
    #sidebar a:active {
      color: inherit;
      text-decoration: none;
    }
    #sidebar .menu-link {
      display: flex;
      align-items: center;
      gap: .75rem;
      min-height: 2.9rem;
      width: 100%;
      border-radius: 1rem;
      padding: .72rem .75rem;
      color: rgba(255,255,255,.72);
      background: transparent;
      border: 1px solid transparent;
    }
    #sidebar .menu-link:hover {
      background: rgba(255,255,255,.08);
      color: #fff;
    }
    #sidebar .menu-link svg {
      color: currentColor;
      flex-shrink: 0;
    }
    #sidebar .submenu-link {
      display: flex;
      align-items: center;
      gap: .75rem;
      width: 100%;
      border-radius: .75rem;
      padding: .5rem .75rem;
      color: rgba(255,255,255,.62);
      background: transparent;
      border: 1px solid transparent;
    }
    #sidebar .submenu-link:hover {
      color: #fff;
      background: rgba(255,255,255,.06);
    }
    #sidebar .appearance-toolbar {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: .35rem;
      margin-top: .55rem;
      padding: .32rem;
      border-radius: 1.25rem;
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.1);
    }
    #sidebar .appearance-toolbar.hidden {
      display: none !important;
    }
    #sidebar .appearance-mode-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .45rem;
      min-height: 2.35rem;
      border-radius: 1rem;
      color: rgba(255,255,255,.62);
      font-size: .78rem;
      font-weight: 800;
      transition: background-color .18s ease, color .18s ease, box-shadow .18s ease;
    }
    #sidebar .appearance-mode-btn:hover {
      color: #fff;
      background: rgba(255,255,255,.08);
    }
    html[data-color-mode="light"] #sidebar .appearance-mode-btn[data-appearance-mode="light"],
    html[data-color-mode="dark"] #sidebar .appearance-mode-btn[data-appearance-mode="dark"] {
      color: #111729;
      background: #f2fda2;
      box-shadow: 0 10px 24px rgba(0,0,0,.18);
    }
    #sidebar .appearance-mode-btn svg {
      width: 1rem;
      height: 1rem;
    }
    #sidebar.sidebar-shell{transition:width .28s cubic-bezier(.2,.8,.2,1);will-change:width;contain:layout paint}
    .menu-glass{position:relative;isolation:isolate}
    .menu-glass .menu-link{position:relative;z-index:1;transition-property:background-color,color,opacity;transition-duration:.2s;transition-timing-function:ease}
    /* Safari: evita flash azul al tocar enlaces/botones del menu */
    #sidebar .menu-link,
    #sidebar .submenu-link,
    #sidebar button,
    .mobile-bottom-nav a {
      -webkit-tap-highlight-color: transparent;
      -webkit-touch-callout: none;
      -webkit-focus-ring-color: transparent;
    }
    #sidebar .menu-link:focus,
    #sidebar .submenu-link:focus,
    #sidebar button:focus,
    .mobile-bottom-nav a:focus {
      outline: none !important;
      box-shadow: none !important;
    }
    #sidebar .menu-link:focus-visible,
    #sidebar .submenu-link:focus-visible,
    #sidebar button:focus-visible,
    .mobile-bottom-nav a:focus-visible {
      outline: none;
      box-shadow: 0 0 0 2px rgba(236, 254, 136, 0.5);
    }
    .menu-glass .menu-highlight{position:absolute;z-index:0;pointer-events:none;left:0;top:0;height:var(--menu-h,40px);width:var(--menu-w,calc(100% - 16px));border-radius:16px;background:radial-gradient(160% 160% at 50% 25%, rgba(255,255,255,.42), rgba(255,255,255,.06));-webkit-backdrop-filter:blur(18px) saturate(120%);backdrop-filter:blur(18px) saturate(120%);box-shadow:inset 0 0 0 1px rgba(255,255,255,.18), 0 16px 36px rgba(0,0,0,.18);-webkit-transform:translate3d(var(--menu-x,8px), var(--menu-y,0px), 0) scaleY(var(--menu-scale,1));transform:translate3d(var(--menu-x,8px), var(--menu-y,0px), 0) scaleY(var(--menu-scale,1));-webkit-backface-visibility:hidden;backface-visibility:hidden;will-change:transform,width,opacity;opacity:1;transition:transform .34s cubic-bezier(.2,.6,.3,1), width .34s cubic-bezier(.2,.6,.3,1), opacity .2s ease}
    @supports (-webkit-touch-callout: none) {
      .menu-glass .menu-highlight{-webkit-backdrop-filter:blur(12px) saturate(110%);backdrop-filter:blur(12px) saturate(110%)}
    }
    
    /* Collapsed Sidebar Tweaks */
    #sidebar.collapsed .menu-link { justify-content: center; padding-left: 0; padding-right: 0; margin-left: auto; margin-right: auto; width: 40px; height: 40px; border-radius: 9999px; }
    #sidebar.collapsed .menu-link svg { margin: 0; width: 20px; height: 20px; }
    #sidebar.collapsed #sidebarToggle { margin: 0 auto; }
    
    /* Anti-flicker CSS overrides for immediate collapse */
    .sidebar-is-collapsed #sidebar { width: 6rem !important; } /* w-24 */
    .sidebar-is-collapsed #sidebar .menu-link span[data-label],
    .sidebar-is-collapsed #sidebar [data-label="INFOCUS"],
    .sidebar-is-collapsed #sidebar svg[data-submenu-chevron],
    .sidebar-is-collapsed #sidebar .appearance-toolbar span { display: none !important; }
    .sidebar-is-collapsed #sidebar [id^="submenu-"] { display: none !important; }
    .sidebar-is-collapsed #sidebar .menu-link { justify-content: center; padding-left: 0; padding-right: 0; margin-left: auto; margin-right: auto; width: 40px; height: 40px; border-radius: 9999px; }
    .sidebar-is-collapsed #sidebar .menu-link svg { margin: 0; width: 20px; height: 20px; }
    .sidebar-is-collapsed #sidebar #sidebarToggle { margin: 0 auto; }
    .sidebar-is-collapsed #sidebar .appearance-toolbar { grid-template-columns: 1fr; padding: .25rem; }
    .sidebar-is-collapsed #sidebar .appearance-mode-btn { min-height: 2.25rem; border-radius: 9999px; }
    
    /* Logo switching */
    [data-collapsed-logo] { display: none !important; }
    .sidebar-is-collapsed #sidebar [data-collapsed-logo] { display: block !important; margin: 0 auto; }

    /* Sidebar toggle icons: X cuando expandido, hamburguesa cuando colapsado */
    #sidebar .sidebar-icon-open { display: none; }
    #sidebar .sidebar-icon-close { display: block; }
    .sidebar-is-collapsed #sidebar .sidebar-icon-open { display: block !important; }
    .sidebar-is-collapsed #sidebar .sidebar-icon-close { display: none !important; }
    /* Centrar el botón cuando colapsado (el logo desaparece y queda solo) */
    .sidebar-is-collapsed #sidebar #sidebarToggle { margin: 0 auto; }
    /* Custom Scrollbar */
    .custom-scroll::-webkit-scrollbar { display: none; }
    .custom-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    .sidebar-scroll-pending #sidebar .custom-scroll {
      visibility: hidden;
      pointer-events: none;
    }

    @media (max-width: 767px) {
      body.mobile-sidebar-open {
        overflow: hidden;
      }

      body.mobile-sidebar-open #sidebar {
        transform: translateX(0) !important;
      }

      body.mobile-sidebar-open #mobileSidebarBackdrop {
        display: block !important;
      }

      #sidebar {
        max-width: calc(100vw - 1rem);
      }

      #sidebar .flex-1.flex.flex-col {
        border-radius: 1.6rem;
      }

      #sidebar #sidebarToggle .sidebar-icon-open {
        display: none !important;
      }

      #sidebar #sidebarToggle .sidebar-icon-close {
        display: block !important;
      }

      .mobile-bottom-nav {
        bottom: calc(.75rem + env(safe-area-inset-bottom));
      }

      .mobile-bottom-link {
        min-width: 0;
        min-height: 3.15rem;
        border-radius: .95rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .16rem;
        font-size: .59rem;
        font-weight: 800;
        letter-spacing: -.01em;
        -webkit-tap-highlight-color: transparent;
      }

      .mobile-bottom-link svg {
        width: 1.28rem;
        height: 1.28rem;
      }

      .mobile-bottom-link.is-active {
        background: rgba(236, 254, 136, .12);
      }
    }

    #remindersPanel {
      border: 8px solid transparent;
      background:
        linear-gradient(#ffffff, #ffffff) padding-box,
        linear-gradient(135deg, rgba(244, 253, 172, .58) 0%, rgba(216, 246, 255, .5) 34%, rgba(240, 212, 255, .46) 68%, rgba(244, 253, 172, .5) 100%) border-box;
      box-shadow:
        inset 0 0 0 1px rgba(148, 163, 184, .14),
        0 28px 70px rgba(15, 23, 42, 0.20);
    }
    html[data-color-mode="dark"] body {
      background: #070b14 !important;
      color: #dbe7f5 !important;
      color-scheme: dark;
    }
    html[data-color-mode="dark"] header {
      background: rgba(7, 11, 20, .88) !important;
      border-bottom: 1px solid rgba(148, 163, 184, .12);
    }
    html[data-color-mode="dark"] main {
      background:
        radial-gradient(circle at 18% 0%, rgba(236, 254, 136, .08), transparent 34%),
        radial-gradient(circle at 100% 0%, rgba(56, 189, 248, .08), transparent 30%),
        #070b14 !important;
    }
    html[data-color-mode="dark"] #sidebar > div {
      background: linear-gradient(180deg, #f2fda2 0%, #ecfe88 100%) !important;
      border-color: rgba(17, 23, 41, .12) !important;
      color: #111729 !important;
      box-shadow: 0 28px 70px rgba(0,0,0,.32), inset 0 0 0 1px rgba(255,255,255,.34) !important;
    }
    html[data-color-mode="dark"] #sidebar > div > .h-16,
    html[data-color-mode="dark"] #sidebar .border-t {
      border-color: rgba(17, 23, 41, .14) !important;
    }
    html[data-color-mode="dark"] #sidebar .menu-link,
    html[data-color-mode="dark"] #sidebar .submenu-link,
    html[data-color-mode="dark"] #sidebar button {
      color: rgba(17, 23, 41, .72) !important;
    }
    html[data-color-mode="dark"] #sidebar .menu-link:hover,
    html[data-color-mode="dark"] #sidebar .submenu-link:hover,
    html[data-color-mode="dark"] #sidebar button:hover {
      background: rgba(17, 23, 41, .08) !important;
      color: #111729 !important;
    }
    html[data-color-mode="dark"] #sidebar .menu-link.is-active {
      background: rgba(17, 23, 41, .12) !important;
      border-color: rgba(17, 23, 41, .08) !important;
      color: #111729 !important;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.2), 0 14px 30px rgba(17, 23, 41, .1) !important;
      font-weight: 800 !important;
    }
    html[data-color-mode="dark"] #sidebar .menu-link.is-active * {
      color: #111729 !important;
      stroke: currentColor;
    }
    html[data-color-mode="dark"] #sidebar .menu-link.is-active:hover {
      background: rgba(17, 23, 41, .16) !important;
      color: #111729 !important;
    }
    html[data-color-mode="dark"] #sidebar .submenu-link.is-active {
      color: #111729 !important;
      background: rgba(17, 23, 41, .1) !important;
    }
    html[data-color-mode="dark"] #sidebar .appearance-toolbar {
      background: rgba(17, 23, 41, .1) !important;
      border-color: rgba(17, 23, 41, .14) !important;
    }
    html[data-color-mode="dark"] #sidebar .appearance-mode-btn {
      color: rgba(17, 23, 41, .68) !important;
    }
    html[data-color-mode="dark"] #sidebar .appearance-mode-btn[data-appearance-mode="dark"] {
      background: #111729 !important;
      color: #f2fda2 !important;
    }
    html[data-color-mode="dark"] :is(.bg-white, .bg-neutral-50, .bg-slate-50, .bg-slate-50\/70, .bg-slate-100, .bg-gray-50):not(#sidebar *):not(.mobile-bottom-nav *) {
      background-color: #111827 !important;
    }
    html[data-color-mode="dark"] :is(.bg-slate-900, .bg-\[\#111729\]):not(#sidebar *):not(.mobile-bottom-nav *) {
      background-color: #070b14 !important;
    }
    html[data-color-mode="dark"] :is(.text-slate-950, .text-slate-900, .text-slate-800):not(#sidebar *):not(.mobile-bottom-nav *) {
      color: #f8fafc !important;
    }
    html[data-color-mode="dark"] :is(.text-slate-700, .text-slate-600):not(#sidebar *):not(.mobile-bottom-nav *) {
      color: #cbd5e1 !important;
    }
    html[data-color-mode="dark"] :is(.text-slate-500, .text-slate-400):not(#sidebar *):not(.mobile-bottom-nav *) {
      color: #94a3b8 !important;
    }
    html[data-color-mode="dark"] :is(.border-slate-100, .border-slate-200, .border-gray-100, .border-gray-200, .divide-slate-100 > *, .divide-slate-200 > *):not(#sidebar *):not(.mobile-bottom-nav *) {
      border-color: #263349 !important;
    }
    html[data-color-mode="dark"] :is(input, textarea, select):not(#sidebar *):not(.flatpickr-input) {
      background-color: #0b1220 !important;
      border-color: #334155 !important;
      color: #e5edf8 !important;
    }
    html[data-color-mode="dark"] :is(input, textarea, select):not(#sidebar *)::placeholder {
      color: #64748b !important;
    }
    html[data-color-mode="dark"] :is(table, thead, tbody, tr, td, th):not(#sidebar *) {
      border-color: #263349 !important;
    }
    html[data-color-mode="dark"] :is(thead, .bg-slate-50):not(#sidebar *) {
      background-color: #0d1626 !important;
    }
    html[data-color-mode="dark"] :is(.shadow, .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl):not(#sidebar *) {
      --tw-shadow-color: rgba(0, 0, 0, .45) !important;
    }
    html[data-color-mode="dark"] .app-select-trigger,
    html[data-color-mode="dark"] .app-select-menu {
      background: #0b1220 !important;
      border-color: #334155 !important;
      color: #e5edf8 !important;
    }
    html[data-color-mode="dark"] .app-select-option:hover {
      background: #172033 !important;
    }
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *),
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *) * {
      color: #101729 !important;
    }
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *) :is(.text-white, .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-400, .text-slate-500, .text-slate-600, .text-slate-700, .text-slate-800, .text-slate-900, .text-slate-950) {
      color: #101729 !important;
    }
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *) :is(.text-lime-600, .text-lime-700, .text-emerald-600, .text-emerald-700) {
      color: #365314 !important;
    }
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *) :is(.bg-slate-900, .bg-\[\#111729\], .bg-black),
    html[data-color-mode="dark"] :is(.bg-lime-100, .bg-lime-200, .bg-lime-300, .bg-lime-400, .bg-\[\#ecfe88\], .bg-\[\#f2fda2\], .bg-\[\#f6fdb5\], .bg-\[\#d9ef60\], .bg-\[\#d9ea76\], .bg-\[\#d9f99d\]):not(#sidebar *) :is(.bg-slate-900, .bg-\[\#111729\], .bg-black) * {
      color: #f8fafc !important;
    }
    html[data-color-mode="dark"] #remindersPanel > .bg-\[\#f6fdb5\],
    html[data-color-mode="dark"] #remindersPanel > .bg-\[\#f6fdb5\] * {
      color: #101729 !important;
    }
    html[data-color-mode="dark"] #reminderAddCategoryBtn,
    html[data-color-mode="dark"] #reminderAddCategoryBtn * {
      color: #101729 !important;
    }
    html[data-color-mode="dark"] #headerRemindersBtn {
      background: #070b14 !important;
      border-color: rgba(236, 254, 136, .18) !important;
      color: #f2fda2 !important;
      box-shadow: 0 0 0 1px rgba(236, 254, 136, .08), 0 18px 38px rgba(0,0,0,.35) !important;
    }
    html[data-color-mode="dark"] #notificationsPanel,
    html[data-color-mode="dark"] #remindersPanel {
      color: #e5edf8 !important;
    }
    html[data-color-mode="dark"] #remindersPanel {
      background:
        linear-gradient(#111827, #111827) padding-box,
        linear-gradient(135deg, rgba(244,253,172,.8) 0%, rgba(216,246,255,.7) 34%, rgba(240,212,255,.74) 68%, rgba(244,253,172,.78) 100%) border-box;
    }
    html[data-color-mode="dark"] .flatpickr-calendar {
      background: #111827 !important;
      border-color: #334155 !important;
      box-shadow: 0 24px 70px rgba(0,0,0,.55) !important;
    }
    html[data-color-mode="dark"] .flatpickr-months,
    html[data-color-mode="dark"] .flatpickr-weekdays,
    html[data-color-mode="dark"] .flatpickr-current-month,
    html[data-color-mode="dark"] .flatpickr-weekday,
    html[data-color-mode="dark"] .flatpickr-day {
      color: #e5edf8 !important;
    }
    html[data-color-mode="dark"] .flatpickr-day:hover {
      background: #263349 !important;
      border-color: #334155 !important;
    }

    html, body {
      height: 100%;
    }
    body {
      min-height: 100dvh;
      height: 100dvh;
    }
    @supports (-webkit-touch-callout: none) {
      body {
        min-height: -webkit-fill-available;
      }
    }

  </style>
</head>
<body class="bg-neutral-50 text-slate-800 antialiased overflow-hidden" data-theme="{{ $uiTheme }}" data-decimals="{{ $decimals }}">
  @php
    $showGlobalBackButton = (
      !request()->routeIs('settings.*')
      && !request()->routeIs('dashboard')
      && !request()->routeIs('*.index')
    ) || request()->routeIs('mis-notas.index');
    $previousUrl = url()->previous();
    $forceBackToFallback = request()->routeIs('facturas.show');
    $fallbackBackUrl = request()->routeIs('facturas.show')
      ? route('facturas.index')
      : ($previousUrl && $previousUrl !== url()->current()
        ? $previousUrl
        : route('dashboard'));
  @endphp
  @php
    $headerUser = auth()->user();
    $headerUserName = trim((string) ($headerUser->name ?? session('user.name', 'Usuario')));
    $headerUserEmail = trim((string) ($headerUser->email ?? session('user.email', '')));
    $headerUserInitials = strtoupper(substr($headerUserName !== '' ? $headerUserName : 'US', 0, 2));
    // Resolver permisos de timer desde roles.json
    $headerUserRole = strtolower((string) ($headerUser->role ?? session('user.role', '')));
    $headerTimerPerms = ['proyectos' => true, 'leads' => true]; // Admin tiene todo
    if (!in_array($headerUserRole, ['admin', 'super_admin', 'manager'], true)) {
        try {
            $rolesStore = new \App\Repositories\FileStore('roles.json');
            $roleRecord = $rolesStore->find($headerUserRole) ?: $rolesStore->all()[0] ?? null;
            $rolePermsList = $roleRecord['permissions'] ?? [];
            if (in_array('*', $rolePermsList)) {
                $headerTimerPerms = ['proyectos' => true, 'leads' => true];
            } else {
                $headerTimerPerms = [
                    'proyectos' => in_array('timer.proyectos', $rolePermsList),
                    'leads'     => in_array('timer.leads', $rolePermsList),
                ];
            }
        } catch (\Throwable $e) {
            $headerTimerPerms = ['proyectos' => true, 'leads' => true];
        }
    }
  @endphp
  <div class="flex h-full">
    @include('partials.sidebar')
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden relative">
      <header class="flex-shrink-0 flex items-center justify-between gap-2 px-3 md:px-8 py-3 md:py-5 bg-neutral-50/90 backdrop-blur z-[120] sticky top-0">
        <div class="flex items-center gap-3">
          <button
            id="mobileSidebarOpenBtn"
            type="button"
            class="md:hidden inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-slate-800 shadow-sm border border-slate-200"
            title="Abrir menú"
            aria-label="Abrir menú"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
          </button>
          @if($showGlobalBackButton)
            <button
              id="global-header-back-btn"
              type="button"
              onclick="@if($forceBackToFallback) window.location.href='{{ $fallbackBackUrl }}'; @else if (window.history.length > 1) { window.history.back(); } else { window.location.href='{{ $fallbackBackUrl }}'; } @endif"
              class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-bold shadow-sm transition-all hover:brightness-90"
              style="background:#f0fe97;color:#1e293b"
              title="Volver"
              aria-label="Volver"
            >
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" />
              </svg>
            </button>
          @endif
          <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center"></h1>
        </div>
        <div class="flex items-center gap-2 md:gap-3 min-w-0">
          <div id="headerTaskTimerHost" class="hidden min-w-[250px] max-w-[430px] flex-1"></div>
          <div id="headerLeadTimerHost" class="hidden"></div>
          <button id="headerStartTimerBtn" type="button" onclick="handleHeaderTimerButtonClick()" class="inline-flex items-center justify-center h-10 w-10 md:h-11 md:w-11 rounded-full bg-white shadow-sm hover:shadow-md transition-all border border-slate-200 text-slate-600 hover:text-slate-900" title="Iniciar temporizador" aria-label="Iniciar temporizador">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <circle cx="12" cy="12" r="8"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/>
            </svg>
          </button>
          <button id="headerNotificationsBtn" type="button" class="relative inline-flex items-center justify-center h-10 w-10 md:h-11 md:w-11 rounded-full bg-white shadow-sm hover:shadow-md transition-all border border-slate-200 text-slate-600 hover:text-slate-900" title="Notificaciones" aria-label="Notificaciones">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 17a2.5 2.5 0 0 0 5 0"/>
            </svg>
            <span id="headerNotificationsCount" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] leading-[18px] text-center font-bold">0</span>
          </button>
          <button id="headerRemindersBtn" type="button" class="relative inline-flex items-center justify-center h-10 w-10 md:h-11 md:w-11 rounded-full bg-[#111729] shadow-sm hover:shadow-md transition-all border border-[#111729] text-[#f2fda2] hover:text-[#f2fda2]" title="Recordatorios" aria-label="Recordatorios">
            <i class="fa-solid fa-list-check text-[18px] leading-none" aria-hidden="true"></i>
            <span id="headerRemindersCount" class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] leading-[18px] text-center font-bold">0</span>
          </button>
          <div class="relative">
            <button id="headerProfileBtn" type="button" class="inline-flex h-10 md:h-auto items-center gap-2 rounded-full bg-white px-3 md:px-4 py-2 md:py-2.5 font-bold shadow-sm hover:shadow-md transition-all border border-slate-200 text-slate-700 hover:text-slate-900" title="Mi perfil" aria-label="Mi perfil">
              <span id="headerPresenceDot" class="inline-flex w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="12" cy="8" r="4"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0 1 16 0"/>
              </svg>
              <span class="hidden sm:inline">Mi perfil</span>
            </button>
            <div id="headerProfileMenu" class="hidden absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-2xl shadow-xl p-2" style="z-index:2147483400">
              <div class="px-3 py-2 border-b border-slate-100">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm font-extrabold">{{ $headerUserInitials }}</div>
                  <div class="min-w-0">
                    <div class="text-sm font-bold text-slate-900 truncate">{{ $headerUserName }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ $headerUserEmail !== '' ? $headerUserEmail : 'Sin email' }}</div>
                  </div>
                </div>
              </div>
              <div class="border-t border-slate-100 pt-1">
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50">Mi cuenta</a>
                <button type="button" id="logoutFromProfileMenu" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50">Cerrar sesión</button>
              </div>
            </div>
          </div>
        </div>
      </header>
      <div id="notificationsBackdrop" class="hidden fixed inset-0 bg-slate-900/30" style="z-index:2147483000"></div>
      <aside id="notificationsPanel" class="fixed top-0 right-0 h-full w-full max-w-md bg-white border-l border-slate-200 shadow-2xl transform translate-x-full transition-transform duration-300 ease-out flex flex-col" style="z-index:2147483010">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-extrabold text-slate-900">Notificaciones</h3>
            <p class="text-xs text-slate-500">Atajo: tecla N</p>
          </div>
          <button id="notificationsCloseBtn" type="button" class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
        </div>
        <div class="px-5 py-3 border-b border-slate-100 space-y-2">
          <div class="flex items-center justify-between gap-2">
            <div class="inline-flex rounded-xl border border-slate-200 p-1 bg-slate-50">
              <button type="button" data-notif-tab="unread" class="notif-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-800 bg-[#f3fea4] shadow-sm">No leídas</button>
              <button type="button" data-notif-tab="all" class="notif-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500">Todas</button>
            </div>
            <div class="flex items-center gap-2">
              <button id="browserNotificationsBtn" type="button" class="hidden rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-extrabold text-slate-600 shadow-sm hover:bg-slate-50">Activar alertas</button>
              <button id="notificationsMarkAllBtn" type="button" class="text-xs font-bold text-slate-600 hover:text-slate-900">Marcar todas</button>
            </div>
          </div>
          <div class="inline-flex flex-wrap rounded-xl border border-slate-200 p-1 bg-white gap-1">
            <button type="button" data-notif-module="all" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-800 bg-[#f3fea4]">Todas <span data-notif-count="all" class="ml-1 text-slate-400">0</span></button>
            <button type="button" data-notif-module="ventas" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-500">Ventas <span data-notif-count="ventas" class="ml-1 text-slate-400">0</span></button>
            <button type="button" data-notif-module="proyectos" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-500">Proyectos <span data-notif-count="proyectos" class="ml-1 text-slate-400">0</span></button>
            <button type="button" data-notif-module="reuniones" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-500">Reuniones <span data-notif-count="reuniones" class="ml-1 text-slate-400">0</span></button>
            <button type="button" data-notif-module="leads" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-500">Leads <span data-notif-count="leads" class="ml-1 text-slate-400">0</span></button>
            <button type="button" data-notif-module="portal" class="notif-module-btn px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-500">Portal <span data-notif-count="portal" class="ml-1 text-slate-400">0</span></button>
          </div>
        </div>
        <div id="notificationsList" class="flex-1 min-h-0 p-4 space-y-2 overflow-y-auto custom-scroll"></div>
      </aside>
      <div id="remindersBackdrop" class="hidden fixed inset-0 bg-slate-950/40" style="z-index:2147483290"></div>
      <aside id="remindersPanel" class="fixed top-5 right-5 bottom-5 h-auto w-[calc(100%-2.5rem)] max-w-md bg-white border-0 shadow-2xl transform translate-x-[calc(100%+2rem)] transition-transform duration-300 ease-out flex flex-col rounded-[2rem] overflow-hidden" style="z-index:2147483300">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-[#f6fdb5]">
          <div>
            <h3 class="text-2xl font-extrabold text-slate-900">Recordatorios</h3>
            <p class="text-xs text-slate-500">Usa # para vincular proyectos o tareas</p>
          </div>
          <button id="remindersCloseBtn" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 leading-none">✕</button>
        </div>
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/70 flex items-center gap-2">
          <button id="reminderCategoriesPrevBtn" type="button" class="hidden shrink-0 h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-100 disabled:cursor-default disabled:opacity-30" title="Ver categorías anteriores" aria-label="Ver categorías anteriores">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          </button>
          <div id="reminderCategories" class="min-w-0 flex-1 flex gap-2 overflow-x-auto custom-scroll"></div>
          <button id="reminderCategoriesNextBtn" type="button" class="hidden shrink-0 h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-100 disabled:cursor-default disabled:opacity-30" title="Ver más categorías" aria-label="Ver más categorías">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
          </button>
          <button id="reminderAddCategoryBtn" type="button" class="shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-full bg-[#f2fda2] text-slate-900 text-lg font-extrabold leading-none hover:bg-[#e5f27c]" title="Añadir categoría" aria-label="Añadir categoría">+</button>
        </div>
        <div class="px-5 pt-3 pb-2 border-b border-slate-100">
          <div id="reminderActiveCategoryTitle"></div>
        </div>
        <div class="px-5 py-3 flex items-center justify-between">
          <div class="text-sm font-extrabold text-slate-500 uppercase tracking-wide">Lista</div>
          <button id="reminderAddSectionBtn" type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-900 text-white hover:bg-[#f2fda2] hover:text-slate-900 transition-colors" title="Añadir lista" aria-label="Añadir lista">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
          </button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto custom-scroll px-5 pb-5">
          <div id="remindersList" class="space-y-5"></div>
          <button id="reminderShowComposerBtn" type="button" class="hidden mt-3 inline-flex items-center gap-2 rounded-2xl py-0.5 text-sm font-bold text-slate-400 hover:text-slate-600">
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-slate-200 bg-white text-sm font-black leading-[1]"><span class="-translate-y-px">+</span></span>
            <span>Añadir recordatorio</span>
          </button>
          <div id="reminderComposer" class="reminder-row group relative hidden items-start gap-2.5 rounded-2xl py-0.5">
            <span class="mt-[6px] h-6 w-6 shrink-0 rounded-full border-2 border-slate-300 bg-white"></span>
            <div class="min-w-0 flex-1">
              <div class="reminder-text-frame relative mt-[4px]">
                <input id="reminderNewText" type="text" class="relative z-10 w-full rounded-none border-0 bg-transparent px-1.5 pt-1 pb-0 pr-9 text-base font-medium leading-6 text-slate-800 outline-none ring-0 focus:bg-transparent focus:outline-none focus:ring-0 focus:shadow-none" placeholder="Nuevo recordatorio">
                <button id="reminderAddBtn" type="button" class="absolute right-1.5 top-1/2 z-20 -translate-y-1/2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#f2fda2] text-slate-900 text-sm font-extrabold leading-none hover:bg-[#e5f27c]">+</button>
                <div id="reminderLinkDropdown" class="hidden absolute left-0 right-0 top-[calc(100%+0.4rem)] rounded-2xl border border-slate-200 bg-white shadow-2xl p-2 z-[70] max-h-64 overflow-y-auto"></div>
              </div>
              <div class="mt-2 flex flex-wrap items-center gap-1.5">
                <div class="relative">
                  <input id="reminderNewPriority" type="hidden" value="">
                  <button id="reminderPriorityBtn" type="button" class="inline-flex h-7 items-center gap-1 rounded-full border border-slate-200 bg-slate-900/5 px-2.5 text-[10px] font-extrabold text-slate-600 hover:bg-slate-100">
                    <span id="reminderPriorityLabel">Prioridad</span>
                  </button>
                  <div id="reminderPriorityDropdown" class="hidden absolute left-0 bottom-[calc(100%+0.4rem)] z-[80] w-48 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                    <button type="button" data-reminder-priority-option="" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold text-slate-500 hover:bg-slate-50"><span>Sin prioridad</span><span data-reminder-priority-check="" class="hidden text-slate-900">✓</span></button>
                    <button type="button" data-reminder-priority-option="Con calma" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold text-emerald-700 hover:bg-emerald-50"><span class="inline-flex items-center gap-1.5"><span data-reminder-priority-icon="Con calma"></span>Con calma</span><span data-reminder-priority-check="Con calma" class="hidden text-slate-900">✓</span></button>
                    <button type="button" data-reminder-priority-option="Atención" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold text-amber-700 hover:bg-amber-50"><span class="inline-flex items-center gap-1.5"><span data-reminder-priority-icon="Atención"></span>Atención</span><span data-reminder-priority-check="Atención" class="hidden text-slate-900">✓</span></button>
                    <button type="button" data-reminder-priority-option="Urgente" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold text-rose-700 hover:bg-rose-50"><span class="inline-flex items-center gap-1.5"><span data-reminder-priority-icon="Urgente"></span>Urgente</span><span data-reminder-priority-check="Urgente" class="hidden text-slate-900">✓</span></button>
                  </div>
                </div>
                <input id="reminderNewDate" type="text" placeholder="Fecha" readonly class="h-7 w-28 rounded-full border border-slate-200 bg-white px-2.5 py-0 text-[10px] font-extrabold leading-none text-slate-500 outline-none placeholder:text-slate-500 focus:border-[#f2fda2]">
                <div id="reminderComposerLinkPreview" class="hidden"></div>
              </div>
            </div>
          </div>
        </div>
      </aside>
      <main class="flex-1 overflow-y-auto px-4 md:px-8 py-2 custom-scroll pb-24 md:pb-6">
        @yield('content')
      </main>
    </div>
  </div>
  <div id="globalTimerFullscreenPanel" class="fixed inset-0 z-[2147483650] hidden overflow-y-auto bg-slate-950/95 text-white backdrop-blur-sm">
    <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
      <button type="button" id="globalTimerFullscreenPipBtn" class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/15" title="Modo PiP" aria-label="Modo PiP">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
      </button>
      <button type="button" id="globalTimerFullscreenCloseBtn" class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/15" title="Cerrar" aria-label="Cerrar temporizador">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="mx-auto flex min-h-full w-full max-w-6xl flex-col justify-center px-4 py-12 md:px-8">
      <div class="mx-auto w-full max-w-4xl text-center">
        <div id="globalTimerFsProject" class="truncate text-xl font-extrabold text-white md:text-3xl">Proyecto</div>
        <div id="globalTimerFsClient" class="mt-1 truncate text-xs text-slate-300 md:text-base">Cliente</div>
        <div class="mt-4 flex flex-col items-center justify-center gap-3 md:flex-row">
          <div id="globalTimerFsDisplay" class="font-mono text-4xl font-extrabold leading-none tracking-tight text-lime-300 md:text-6xl">00:00:00</div>
          <div class="flex items-center justify-center gap-2">
          <button type="button" id="globalTimerFsPauseBtn" class="grid h-11 w-11 place-items-center rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/15" title="Pausar/continuar" aria-label="Pausar o continuar temporizador"></button>
          <button type="button" id="globalTimerFsSaveBtn" class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 text-sm font-extrabold text-white hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-45">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
            Guardar
          </button>
          <button type="button" id="globalTimerFsDeleteBtn" class="inline-flex h-10 items-center gap-2 rounded-xl border border-rose-300/35 bg-rose-500/10 px-4 text-sm font-extrabold text-rose-200 hover:bg-rose-500/15">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.1" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-8 0h10"/></svg>
            Eliminar
          </button>
          </div>
        </div>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-2">
        <section class="min-h-[320px] rounded-2xl bg-white/7 p-4 shadow-2xl">
          <h3 class="mb-3 text-lg font-extrabold uppercase tracking-[0.2em] text-white">Checklist</h3>
          <div id="globalTimerFsSubtasksList" class="max-h-64 space-y-2 overflow-y-auto pr-1 text-left"></div>
          <div class="mt-2 flex gap-2">
            <input id="globalTimerFsNewSubtaskInput" type="text" class="min-w-0 flex-1 rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs text-white outline-none placeholder:text-slate-400 focus:border-lime-300" placeholder="Añadir elemento">
            <button id="globalTimerFsAddSubtaskBtn" type="button" class="rounded-lg bg-lime-300 px-3 py-2 text-xs font-extrabold text-slate-950 hover:bg-lime-200">Añadir</button>
          </div>
        </section>

        <section class="min-h-[320px] rounded-2xl bg-white/7 p-4 shadow-2xl">
          <h3 class="mb-3 text-lg font-extrabold uppercase tracking-[0.2em] text-white">Notas</h3>
          <div id="globalTimerFsNotesList" class="max-h-56 space-y-1.5 overflow-y-auto pr-1 text-left"></div>
          <div class="mt-2">
            <textarea id="globalTimerFsNewNoteInput" rows="2" class="w-full resize-y rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs text-white outline-none placeholder:text-slate-400 focus:border-lime-300" placeholder="Escribe una nota de pipeline..."></textarea>
            <div class="mt-2 flex justify-end">
              <button id="globalTimerFsAddNoteBtn" type="button" class="rounded-lg bg-lime-300 px-3 py-2 text-xs font-extrabold text-slate-950 hover:bg-lime-200">Guardar nota</button>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <div id="globalTimerMiniPip" class="fixed bottom-4 right-4 z-[75] hidden w-64 rounded-2xl border border-slate-700 bg-slate-900/95 text-white shadow-2xl">
    <div class="px-3 py-2 border-b border-slate-700 flex items-center justify-between">
      <div class="min-w-0">
        <div id="globalTimerPipProject" class="text-xs font-bold truncate">Proyecto</div>
        <div id="globalTimerPipClient" class="text-[10px] text-slate-300 truncate">Cliente</div>
      </div>
      <div class="flex items-center gap-2">
        <button id="globalTimerPipToggleBtn" type="button" class="w-7 h-7 rounded-full border border-slate-500 text-lime-300 hover:bg-slate-800 flex items-center justify-center" title="Iniciar/Pausar" aria-label="Iniciar/Pausar temporizador">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </button>
        <button type="button" id="globalTimerPipCloseBtn" class="text-slate-300 hover:text-white" title="Cerrar PiP" aria-label="Cerrar PiP">✕</button>
      </div>
    </div>
    <div id="globalTimerPipDisplay" class="px-3 py-3 text-3xl font-mono font-extrabold text-lime-300 text-center">00:00:00</div>
  </div>

  <canvas id="globalTimerPipCanvas" width="520" height="520" class="fixed -bottom-20 -right-20 w-1 h-1 opacity-0 pointer-events-none"></canvas>
  <video id="globalTimerPipVideo" playsinline muted autoplay></video>
  <form id="headerLogoutForm" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const el = document.getElementById('sidebar');
    const btn = document.getElementById('sidebarToggle');
    const mobileSidebarOpenBtn = document.getElementById('mobileSidebarOpenBtn');
    const mobileSidebarBackdrop = document.getElementById('mobileSidebarBackdrop');
    const sidebarScrollHost = el ? el.querySelector('.custom-scroll') : null;
    const SIDEBAR_SCROLL_KEY = 'sidebarScrollTop';
    const APPEARANCE_MODE_KEY = 'infocusAppearanceMode';
    const APPEARANCE_MODE_ENABLED = false;
    const applyAppearanceMode = (mode) => {
      const nextMode = mode === 'dark' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-color-mode', nextMode);
      document.body?.setAttribute('data-color-mode', nextMode);
      document.querySelectorAll('[data-appearance-mode]').forEach((button) => {
        const isActive = button.getAttribute('data-appearance-mode') === nextMode;
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      try { localStorage.setItem(APPEARANCE_MODE_KEY, nextMode); } catch (e) {}
    };
    try {
      applyAppearanceMode('light');
    } catch (e) {
      applyAppearanceMode('light');
    }
    document.querySelectorAll('[data-appearance-mode]').forEach((button) => {
      button.addEventListener('click', () => {
        if (!APPEARANCE_MODE_ENABLED) return;
        applyAppearanceMode(button.getAttribute('data-appearance-mode'));
      });
    });
    const saveSidebarScroll = () => {
      if (!sidebarScrollHost) return;
      try { localStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebarScrollHost.scrollTop || 0)); } catch (e) {}
    };
    const restoreSidebarScroll = () => {
      if (!sidebarScrollHost) return;
      let saved = 0;
      try { saved = parseInt(localStorage.getItem(SIDEBAR_SCROLL_KEY) || '0', 10) || 0; } catch (e) {}
      sidebarScrollHost.scrollTop = saved;
      document.documentElement.classList.remove('sidebar-scroll-pending');
      requestAnimationFrame(() => { sidebarScrollHost.scrollTop = saved; });
    };

    if (sidebarScrollHost) {
      let _sidebarScrollRaf = null;
      sidebarScrollHost.addEventListener('scroll', () => {
        if (_sidebarScrollRaf) return;
        _sidebarScrollRaf = requestAnimationFrame(() => {
          _sidebarScrollRaf = null;
          saveSidebarScroll();
        });
      }, { passive: true });

      window.addEventListener('pagehide', saveSidebarScroll);
      window.addEventListener('beforeunload', saveSidebarScroll);
      restoreSidebarScroll();
    } else {
      document.documentElement.classList.remove('sidebar-scroll-pending');
    }
    const isMobileSidebar = () => window.matchMedia('(max-width: 767px)').matches;
    const openMobileSidebar = () => {
      document.body.classList.add('mobile-sidebar-open');
      mobileSidebarOpenBtn?.setAttribute('aria-expanded', 'true');
    };
    const closeMobileSidebar = () => {
      document.body.classList.remove('mobile-sidebar-open');
      mobileSidebarOpenBtn?.setAttribute('aria-expanded', 'false');
    };
    mobileSidebarOpenBtn?.addEventListener('click', openMobileSidebar);
    mobileSidebarBackdrop?.addEventListener('click', closeMobileSidebar);
    el?.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (isMobileSidebar()) closeMobileSidebar();
      });
    });
    const isCollapsed = () => el.classList.contains('collapsed') || el.classList.contains('w-24');
    const setCollapsed = (collapsed) => {
      if (!el || isMobileSidebar()) {
        document.documentElement.classList.remove('sidebar-is-collapsed');
        el?.classList.remove('w-24', 'collapsed');
        el?.classList.add('w-[19rem]');
        document.querySelectorAll('[data-label]').forEach(n => n.classList.remove('hidden'));
        return;
      }
      if (collapsed) {
        document.documentElement.classList.add('sidebar-is-collapsed');
        el.classList.remove('w-72');
        el.classList.add('w-24', 'collapsed');
        document.querySelectorAll('[data-label]').forEach(n => n.classList.add('hidden'));
        document.querySelectorAll('[id^="submenu-"]').forEach(s => s.classList.add('hidden'));
        document.querySelectorAll('[data-submenu-chevron]').forEach(c => c.style.transform = 'rotate(0)');
        document.querySelectorAll('[data-submenu-toggle]').forEach(t => {
          const key = 'submenu-'+t.getAttribute('data-submenu-toggle');
          try { localStorage.setItem(key, '0'); } catch(e){}
        });
        try { localStorage.setItem('sidebarCollapsed', '1'); } catch(e){}
      } else {
        document.documentElement.classList.remove('sidebar-is-collapsed');
        el.classList.add('w-72');
        el.classList.remove('w-24', 'collapsed');
        document.querySelectorAll('[data-label]').forEach(n => n.classList.remove('hidden'));
        try { localStorage.setItem('sidebarCollapsed', '0'); } catch(e){}
      }
    };
    if (btn) {
      btn.addEventListener('click', () => {
        if (isMobileSidebar()) {
          closeMobileSidebar();
          return;
        }
        setCollapsed(!isCollapsed());
      });
      try {
        if (!isMobileSidebar()) {
          const collapsed = localStorage.getItem('sidebarCollapsed') === '1';
          setCollapsed(collapsed);
        }
      } catch(e){}
    }
    window.addEventListener('resize', () => {
      if (!isMobileSidebar()) {
        closeMobileSidebar();
        try { setCollapsed(localStorage.getItem('sidebarCollapsed') === '1'); } catch(e) {}
      } else {
        document.documentElement.classList.remove('sidebar-is-collapsed');
        el?.classList.remove('w-24', 'collapsed');
      }
    });
    // Detectar qué submenu (si alguno) tiene un hijo activo en esta página
    const _activeSubmenuKeys = new Set();
    document.querySelectorAll('[id^="submenu-"]').forEach(p => {
      if (p.querySelector('.submenu-link.is-active')) _activeSubmenuKeys.add(p.id);
    });

    document.querySelectorAll('[data-submenu-toggle]').forEach(t => {
      const key = 'submenu-'+t.getAttribute('data-submenu-toggle');
      const panel = document.getElementById(key);
      const chev = document.querySelector('[data-submenu-chevron="'+t.getAttribute('data-submenu-toggle')+'"]');
      const hasActiveChild = _activeSubmenuKeys.has(key);
      const apply = (open) => {
        if (!panel) return;
        if (open) { panel.classList.remove('hidden'); if (chev) chev.style.transform='rotate(180deg)'; }
        else { panel.classList.add('hidden'); if (chev) chev.style.transform='rotate(0)'; }
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
      };
      try { 
        if (!isCollapsed()) {
          // Si hay otro submenu activo en esta página, no restaurar este desde storage
          const rivalActive = _activeSubmenuKeys.size > 0 && !hasActiveChild;
          const fromStorage = !rivalActive && localStorage.getItem(key)==='1';
          const shouldOpen = hasActiveChild || fromStorage;
          apply(shouldOpen);
          if (hasActiveChild) {
            localStorage.setItem(key, '1');
          } else if (rivalActive) {
            localStorage.setItem(key, '0');
          }
        }
      } catch(e){}
      t.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (isCollapsed()) {
          setCollapsed(false);
          apply(true);
          try { localStorage.setItem(key, '1'); } catch(e){}
        } else {
          const open = panel.classList.contains('hidden');
          apply(open);
          try { localStorage.setItem(key, open ? '1':'0'); } catch(e){}
        }
      });
    });

    // Mantener abierto el submenu padre al navegar por subopciones para evitar parpadeo
    document.querySelectorAll('#sidebar .submenu-link').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.stopPropagation();
        const panel = link.closest('[id^="submenu-"]');
        if (!panel) return;
        try { localStorage.setItem(panel.id, '1'); } catch (err) {}
        saveSidebarScroll();
      });
    });

    document.querySelectorAll('#sidebar .menu-link').forEach((link) => {
      link.addEventListener('click', () => {
        saveSidebarScroll();
      });
    });

    // Safari/iOS: limpia foco despues del tap para evitar flash azul de enfoque
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
      document.querySelectorAll('#sidebar .menu-link, #sidebar .submenu-link, .mobile-bottom-nav a').forEach((el) => {
        el.addEventListener('click', () => {
          setTimeout(() => {
            if (document.activeElement === el && typeof el.blur === 'function') {
              el.blur();
            }
          }, 0);
        });
      });
    }

    // Segmented control (liquid glass) initializer
    (function(){
      const groups = document.querySelectorAll('.segmented-control');
      groups.forEach(g=>{
        if(!g.querySelector('.segmented-highlight')){
          const h = document.createElement('span'); h.className='segmented-highlight'; g.appendChild(h);
        }
        const buttons = g.querySelectorAll('.seg-btn');
        const highlight = g.querySelector('.segmented-highlight');
        function position(active){
          if(!active || !highlight) return;
          const gb = g.getBoundingClientRect();
          const bb = active.getBoundingClientRect();
          const pad = parseFloat(getComputedStyle(g).paddingLeft)||0;
          const x = (bb.left - gb.left);
          const w = bb.width;
          highlight.style.setProperty('--seg-pad', pad+'px');
          highlight.style.setProperty('--seg-x', (x)+'px');
          highlight.style.setProperty('--seg-w', w+'px');
        }
        let active = g.querySelector('.seg-btn.is-active') || buttons[0];
        if(active){ position(active); }
        buttons.forEach(b=>{
          b.addEventListener('click', ()=>{
            buttons.forEach(x=>x.classList.remove('is-active'));
            b.classList.add('is-active');
            position(b);
          });
        });
        window.addEventListener('resize', ()=>position(g.querySelector('.seg-btn.is-active')||buttons[0]));
      });
    })();
    // Sidebar liquid-glass highlight
    (function(){
      const container = document.querySelector('.menu-glass');
      if(!container) return;
      let hl = container.querySelector('.menu-highlight');
      if(!hl){ hl = document.createElement('span'); hl.className='menu-highlight'; container.appendChild(hl); }
      function position(){
        const a = container.querySelector('.menu-link.is-active');
        if(!a) { hl.style.opacity='0'; return; }
        const cb = container.getBoundingClientRect();
        const ab = a.getBoundingClientRect();
        const padX = 8;
        let targetW = Math.max(0, ab.width - padX*2);
        let targetH = Math.max(36, ab.height - 8);
        const currentY = parseFloat(getComputedStyle(hl).getPropertyValue('--menu-y') || '0');
        let y = (ab.top - cb.top) + (ab.height - targetH)/2;
        let x = padX;
        // collapsed sidebar: center over icon and hide submenu highlight
        const collapsed = isCollapsed();
        if (collapsed) {
          targetW = 36;
          targetH = 36;
          y = (ab.top - cb.top) + (ab.height - targetH)/2;
          x = (cb.width - targetW)/2;
          document.getElementById('submenu-ventas')?.classList.add('hidden');
        } else {
          // intentar centrar al texto: medir el span data-label
          const label = a.querySelector('[data-label]');
          if (label) {
            const lb = label.getBoundingClientRect();
            const labelPad = 14;
            targetW = Math.max(120, lb.width + labelPad*2); // evitar píldora muy corta
            x = Math.max(padX, (lb.left - cb.left) - labelPad);
            y = (ab.top - cb.top) + (ab.height - targetH)/2;
          }
        }
        hl.style.setProperty('--menu-y', y+'px');
        hl.style.setProperty('--menu-x', x+'px');
        hl.style.setProperty('--menu-w', targetW+'px');
        hl.style.setProperty('--menu-h', targetH+'px');
        hl.style.opacity='1';
        // bounce stretch
        const dy = y - currentY;
        if (Math.abs(dy) > 6) {
          hl.style.setProperty('--menu-scale', dy > 0 ? '1.08' : '0.94');
          setTimeout(()=>hl.style.setProperty('--menu-scale','1'), 180);
        }
      }
      const queuePosition = () => window.requestAnimationFrame(position);
      position();
      window.addEventListener('resize', queuePosition);
      // If submenu toggles change height, reposition after animation
      document.querySelectorAll('[data-submenu-toggle]').forEach(btn=>{
        btn.addEventListener('click', ()=> setTimeout(queuePosition, 320));
      });
    })();
    // Auto-close submenu when clicking other top-level items
    (function(){
      const topLinks = document.querySelectorAll('.menu-link:not([data-submenu-toggle])');
      topLinks.forEach(link => {
        link.addEventListener('click', () => {
          document.querySelectorAll('[id^="submenu-"]').forEach(panel => {
            if (!panel.classList.contains('hidden')) {
              panel.classList.add('hidden');
              const id = panel.id.replace('submenu-', '');
              const chev = document.querySelector(`[data-submenu-chevron="${id}"]`);
              if (chev) chev.style.transform = 'rotate(0)';
              try { localStorage.setItem(panel.id, '0'); } catch(e){}
            }
          });
          // Update position of highlight
          setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
        });
      });
    })();

    (function(){
      const notificationsBtn = document.getElementById('headerNotificationsBtn');
      const notificationsDot = document.getElementById('headerNotificationsDot');
      const notificationsCount = document.getElementById('headerNotificationsCount');
      const notificationsPanel = document.getElementById('notificationsPanel');
      const notificationsBackdrop = document.getElementById('notificationsBackdrop');
      const notificationsCloseBtn = document.getElementById('notificationsCloseBtn');
      const notificationsList = document.getElementById('notificationsList');
      const markAllBtn = document.getElementById('notificationsMarkAllBtn');
      const browserNotificationsBtn = document.getElementById('browserNotificationsBtn');
      const tabButtons = document.querySelectorAll('.notif-tab-btn');
      const moduleButtons = document.querySelectorAll('.notif-module-btn');
      const moduleCountEls = document.querySelectorAll('[data-notif-count]');

      const remindersBtn = document.getElementById('headerRemindersBtn');
      const remindersCount = document.getElementById('headerRemindersCount');
      const remindersPanel = document.getElementById('remindersPanel');
      const remindersBackdrop = document.getElementById('remindersBackdrop');
      const remindersCloseBtn = document.getElementById('remindersCloseBtn');
      const reminderCategories = document.getElementById('reminderCategories');
      const reminderCategoriesPrevBtn = document.getElementById('reminderCategoriesPrevBtn');
      const reminderCategoriesNextBtn = document.getElementById('reminderCategoriesNextBtn');
      const reminderAddCategoryBtn = document.getElementById('reminderAddCategoryBtn');
      const reminderActiveCategoryTitle = document.getElementById('reminderActiveCategoryTitle');
      const remindersList = document.getElementById('remindersList');
      const reminderShowComposerBtn = document.getElementById('reminderShowComposerBtn');
      const reminderComposer = document.getElementById('reminderComposer');
      const reminderNewText = document.getElementById('reminderNewText');
      const reminderNewPriority = document.getElementById('reminderNewPriority');
      const reminderPriorityBtn = document.getElementById('reminderPriorityBtn');
      const reminderPriorityLabel = document.getElementById('reminderPriorityLabel');
      const reminderPriorityDropdown = document.getElementById('reminderPriorityDropdown');
      const reminderNewDate = document.getElementById('reminderNewDate');
      const reminderAddBtn = document.getElementById('reminderAddBtn');
      const reminderAddSectionBtn = document.getElementById('reminderAddSectionBtn');
      const reminderLinkDropdown = document.getElementById('reminderLinkDropdown');
      const reminderComposerLinkPreview = document.getElementById('reminderComposerLinkPreview');
      const remindersScrollArea = remindersList?.parentElement;

      const profileBtn = document.getElementById('headerProfileBtn');
      const profileMenu = document.getElementById('headerProfileMenu');
      const logoutBtn = document.getElementById('logoutFromProfileMenu');
      const logoutForm = document.getElementById('headerLogoutForm');
      const presenceDot = document.getElementById('headerPresenceDot');
      const presenceLabel = document.getElementById('headerPresenceLabel');
      const presenceButtons = document.querySelectorAll('.profile-status-btn');

      if (!notificationsBtn || !notificationsPanel || !notificationsList || !profileBtn || !profileMenu) return;

      const statusMap = {
        available: { label: 'Disponible', dotClass: 'bg-emerald-500' },
        focus: { label: 'En foco', dotClass: 'bg-sky-500' },
        away: { label: 'Ausente', dotClass: 'bg-amber-500' },
      };

      let notifications = [];
      let currentTab = 'unread';
      let currentModule = 'all';
      const BROWSER_NOTIFICATIONS_KEY = 'infocus_browser_notifications_seen_v1_' + @json((string) (auth()->id() ?? session('user.id') ?? session('user.email') ?? 'anon'));
      let browserNotificationsInitialLoad = true;
      let browserNotificationsSeen = new Set();
      const REMINDERS_KEY = 'infocus_header_reminders_v1_' + @json((string) (auth()->id() ?? session('user.id') ?? session('user.email') ?? 'anon'));
      let reminderCategoriesData = [];
      let reminderSections = [];
      let reminders = [];
      let reminderLinkOptions = [];
      let activeReminderLinkTarget = null;
      let reminderPendingLink = null;
      let activeReminderCategoryId = 'default-cat';
      let activeReminderSectionId = 'default';
      let reminderAllViewMode = 'clients';
      let reminderPriorityCollapsed = {};
      let reminderPopoverBusyUntil = 0;
      let reminderDatePickerOpen = false;
      let reminderRemoteLoaded = false;
      let reminderRemoteSaveTimer = null;
      let pendingReminderCategoryPromotionId = '';
      const ALL_REMINDERS_CATEGORY_ID = '__all__';

      function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      }

      function uid(prefix) {
        return prefix + '_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
      }

      function hasReminderText(item) {
        return String(item?.text || '').trim() !== '';
      }

      function normalizeReminderStructure() {
        const fallbackCategoryId = reminderCategoriesData[0]?.id || 'default-cat';
        const validCategoryIds = new Set(reminderCategoriesData.map((category) => String(category.id || '')));

        reminderSections = reminderSections
          .filter((section) => section?.id)
          .map((section) => ({
            collapsed: false,
            ...section,
            categoryId: validCategoryIds.has(String(section.categoryId || ''))
              ? section.categoryId
              : fallbackCategoryId,
          }));

        reminderCategoriesData.forEach((category) => {
          const categorySections = reminderSections.filter((section) => String(section.categoryId) === String(category.id));
          const implicitSections = categorySections.filter((section) => {
            const title = String(section.title || '').trim().toLowerCase();
            return !title || title === 'recordatorios';
          });
          if (implicitSections.length <= 1) return;

          const canonicalSection = implicitSections[0];
          const duplicateIds = new Set(implicitSections.slice(1).map((section) => String(section.id)));
          reminders = reminders.map((item) => (
            duplicateIds.has(String(item.sectionId || ''))
              ? { ...item, sectionId: canonicalSection.id }
              : item
          ));
          reminderSections = reminderSections.filter((section) => !duplicateIds.has(String(section.id)));
        });
      }

      function currentRemindersPayload() {
        return {
          categories: reminderCategoriesData,
          sections: reminderSections,
          items: reminders,
          allViewMode: reminderAllViewMode,
          priorityCollapsed: reminderPriorityCollapsed,
        };
      }

      function hasUsefulRemindersPayload(payload) {
        return Array.isArray(payload?.items) && payload.items.some(hasReminderText);
      }

      function applyRemindersPayload(payload) {
        const parsed = payload && typeof payload === 'object' ? payload : {};
        reminderCategoriesData = Array.isArray(parsed.categories) ? parsed.categories : [];
        reminderSections = Array.isArray(parsed.sections) ? parsed.sections : [];
        reminders = Array.isArray(parsed.items) ? parsed.items : [];
        reminderAllViewMode = parsed.allViewMode === 'priority' ? 'priority' : 'clients';
        reminderPriorityCollapsed = parsed.priorityCollapsed && typeof parsed.priorityCollapsed === 'object'
          ? parsed.priorityCollapsed
          : {};

        if (!reminderCategoriesData.length) {
          reminderCategoriesData = [{ id: 'default-cat', title: 'Recordatorios' }];
        }
        reminderCategoriesData = reminderCategoriesData.map((category) => ({ collapsed: false, ...category }));
        if (!reminderSections.length) {
          reminderSections = [{ id: 'default', categoryId: reminderCategoriesData[0].id, title: 'Recordatorios', collapsed: false }];
        }
        reminderSections = reminderSections.map((section) => ({ categoryId: reminderCategoriesData[0].id, ...section }));
        reminders = reminders.map((item) => ({
          sectionId: reminderSections[0]?.id || 'default',
          ...item,
          priority: normalizeReminderPriority(item?.priority),
        })).filter(hasReminderText);
        normalizeReminderStructure();
        activeReminderCategoryId = ALL_REMINDERS_CATEGORY_ID;
        activeReminderSectionId = reminderSections[0]?.id || 'default';
        persistRemindersOnly();
      }

      function readLocalRemindersPayload() {
        try {
          return JSON.parse(localStorage.getItem(REMINDERS_KEY) || '{}') || {};
        } catch (_) {
          return {};
        }
      }

      function loadReminders() {
        applyRemindersPayload(readLocalRemindersPayload());
      }

      function persistRemindersOnly() {
        try {
          localStorage.setItem(REMINDERS_KEY, JSON.stringify(currentRemindersPayload()));
        } catch (_) {}
        renderRemindersCounter();
      }

      async function saveRemindersRemoteNow() {
        if (!reminderRemoteLoaded) return;
        try {
          await fetch('/api/header/reminders', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ payload: currentRemindersPayload() }),
          });
        } catch (error) {
          console.warn('No se pudieron sincronizar los recordatorios.', error);
        }
      }

      function queueSaveRemindersRemote() {
        if (!reminderRemoteLoaded) return;
        clearTimeout(reminderRemoteSaveTimer);
        reminderRemoteSaveTimer = setTimeout(saveRemindersRemoteNow, 350);
      }

      function saveReminders() {
        persistRemindersOnly();
        queueSaveRemindersRemote();
        renderReminders();
      }

      async function loadRemindersFromServer() {
        const localPayload = readLocalRemindersPayload();
        try {
          const response = await fetch('/api/header/reminders', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
          });
          const data = await response.json().catch(() => ({}));
          const serverPayload = data?.payload && typeof data.payload === 'object' ? data.payload : null;
          reminderRemoteLoaded = true;

          if (serverPayload) {
            applyRemindersPayload(serverPayload);
            renderReminders();
            return;
          }

          if (!data?.exists && hasUsefulRemindersPayload(localPayload)) {
            applyRemindersPayload(localPayload);
            await saveRemindersRemoteNow();
            renderReminders();
          }
        } catch (error) {
          reminderRemoteLoaded = false;
          console.warn('No se pudieron cargar recordatorios del servidor.', error);
        }
      }

      function normalizeReminderPriority(priority) {
        const value = String(priority || '').trim().toLowerCase();
        if (value === 'urgente' || value === 'alta' || value === 'high') return 'Urgente';
        if (value === 'atención' || value === 'atencion' || value === 'media' || value === 'medium') return 'Atención';
        if (value === 'con calma' || value === 'baja' || value === 'low') return 'Con calma';
        return '';
      }

      function priorityText(priority) {
        return normalizeReminderPriority(priority);
      }

      function priorityClass(priority) {
        const normalized = normalizeReminderPriority(priority);
        if (normalized === 'Urgente') return 'border-rose-200 bg-rose-50 text-rose-700';
        if (normalized === 'Atención') return 'border-amber-200 bg-amber-50 text-amber-700';
        if (normalized === 'Con calma') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        return 'border-slate-200 bg-slate-50 text-slate-500';
      }

      function groupRemindersByPriority(items) {
        const groups = [
          { key: 'Urgente', label: 'Urgente', items: [] },
          { key: 'Atención', label: 'Atención', items: [] },
          { key: 'Con calma', label: 'Con calma', items: [] },
          { key: '', label: 'Sin prioridad', items: [] },
        ];
        const groupsByKey = new Map(groups.map((group) => [group.key, group]));
        items.filter(hasReminderText).forEach((item) => {
          const priority = normalizeReminderPriority(item?.priority);
          groupsByKey.get(priority)?.items.push(item);
        });
        return groups;
      }

      function reminderPriorityButtonHtml(priority, attrs = '') {
        const normalized = normalizeReminderPriority(priority);
        return `<button type="button" ${attrs} class="inline-flex h-6 items-center gap-1 rounded-full border px-2 text-[10px] font-extrabold hover:bg-slate-100 ${priorityClass(normalized)}">
          ${reminderPriorityIcon(normalized)}
          <span>${normalized || 'Prioridad'}</span>
          <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        </button>`;
      }

      function reminderPriorityMenuHtml(currentPriority, setAttr = '', menuKey = '') {
        const current = normalizeReminderPriority(currentPriority);
        const option = (value, label, color, hover) => {
          const normalized = normalizeReminderPriority(value);
          const active = normalized === current;
          const bg = active ? hover : '';
          return `<button type="button" ${setAttr} data-reminder-priority-value="${escapeHtml(normalized)}" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold ${color} ${hover ? `hover:${hover}` : 'hover:bg-slate-50'} ${bg}">
            <span class="inline-flex items-center gap-1.5">${normalized ? reminderPriorityIcon(normalized) : ''}${escapeHtml(label)}</span>
            <span class="${active ? '' : 'hidden'} text-slate-900">✓</span>
          </button>`;
        };
        return `<div class="hidden absolute left-0 top-[calc(100%+0.35rem)] z-[85] w-48 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl" data-reminder-priority-menu="${escapeHtml(menuKey)}">
          ${option('', 'Sin prioridad', 'text-slate-500', 'bg-slate-50')}
          ${option('Con calma', 'Con calma', 'text-emerald-700', 'bg-emerald-50')}
          ${option('Atención', 'Atención', 'text-amber-700', 'bg-amber-50')}
          ${option('Urgente', 'Urgente', 'text-rose-700', 'bg-rose-50')}
        </div>`;
      }

      function reminderPriorityIcon(priority) {
        const normalized = normalizeReminderPriority(priority);
        const cls = 'h-3 w-3 shrink-0 self-center';
        if (normalized === 'Urgente') {
          return `<svg class="${cls}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>`;
        }
        if (normalized === 'Atención') {
          return `<svg class="${cls}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>`;
        }
        if (normalized === 'Con calma') {
          return `<svg class="${cls}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>`;
        }
        return `<svg class="${cls}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l9 16H3L12 3z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>`;
      }

      function updateReminderPriorityPicker(value = '') {
        const normalized = normalizeReminderPriority(value);
        if (reminderNewPriority) reminderNewPriority.value = normalized;
        if (reminderPriorityLabel) {
          reminderPriorityLabel.innerHTML = `${reminderPriorityIcon(normalized)}<span>${normalized || 'Prioridad'}</span><svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>`;
          reminderPriorityLabel.className = 'inline-flex items-center gap-1';
        }
        if (reminderPriorityBtn) {
          reminderPriorityBtn.className = `inline-flex h-7 items-center gap-1 rounded-full border px-2.5 text-[10px] font-extrabold hover:bg-slate-100 ${priorityClass(normalized)}`;
        }
        reminderPriorityDropdown?.querySelectorAll('[data-reminder-priority-icon]').forEach((slot) => {
          slot.innerHTML = reminderPriorityIcon(slot.getAttribute('data-reminder-priority-icon') || '');
        });
        reminderPriorityDropdown?.querySelectorAll('[data-reminder-priority-check]').forEach((check) => {
          const option = check.getAttribute('data-reminder-priority-check') || '';
          check.classList.toggle('hidden', option !== normalized);
        });
        reminderPriorityDropdown?.querySelectorAll('[data-reminder-priority-option]').forEach((btn) => {
          const option = normalizeReminderPriority(btn.getAttribute('data-reminder-priority-option') || '');
          const active = option === normalized;
          btn.classList.remove('bg-slate-50', 'bg-emerald-50', 'bg-amber-50', 'bg-rose-50');
          if (!active) return;
          if (option === 'Con calma') btn.classList.add('bg-emerald-50');
          else if (option === 'Atención') btn.classList.add('bg-amber-50');
          else if (option === 'Urgente') btn.classList.add('bg-rose-50');
          else btn.classList.add('bg-slate-50');
        });
      }

      function isReminderSectionCollapsed(sectionId) {
        const section = reminderSections.find((item) => String(item.id) === String(sectionId));
        if (!section) return false;
        const title = String(section.title || '').trim();
        const isImplicitSection = !title || title.toLowerCase() === 'recordatorios';
        return !!section.collapsed && !isImplicitSection;
      }

      function syncReminderAddZonesVisibility(hiddenSectionId = '') {
        remindersList?.querySelectorAll('[data-reminder-section-add]').forEach((zone) => {
          const sectionId = zone.getAttribute('data-reminder-section-add') || '';
          const shouldHide = String(sectionId) === String(hiddenSectionId) || isReminderSectionCollapsed(sectionId);
          zone.classList.toggle('hidden', shouldHide);
        });
      }

      function openReminderComposer(sectionId = activeReminderSectionId, afterReminderId = '') {
        activeReminderSectionId = sectionId || activeReminderSectionId;
        const targetRow = afterReminderId
          ? remindersList?.querySelector(`[data-reminder-id="${CSS.escape(afterReminderId)}"]`)
          : null;
        const targetAppendZone = activeReminderSectionId
          ? remindersList?.querySelector(`[data-reminder-section-add="${CSS.escape(activeReminderSectionId)}"]`)
          : null;
        if (targetRow && reminderComposer) {
          targetRow.insertAdjacentElement('afterend', reminderComposer);
        } else if (targetAppendZone && reminderComposer) {
          targetAppendZone.insertAdjacentElement('beforebegin', reminderComposer);
        }
        if (reminderComposer) {
          reminderComposer.dataset.sectionId = activeReminderSectionId || '';
          reminderComposer.dataset.afterReminderId = afterReminderId || '';
        }
        syncReminderAddZonesVisibility(activeReminderSectionId);
        reminderShowComposerBtn?.classList.add('hidden');
        reminderComposer?.classList.remove('hidden');
        reminderComposer?.classList.add('flex');
        setTimeout(() => {
          scrollReminderComposerIntoView();
          reminderNewText?.focus();
        }, 0);
      }

      function scrollReminderComposerIntoView() {
        if (!reminderComposer || !remindersScrollArea) return;
        const composerRect = reminderComposer.getBoundingClientRect();
        const areaRect = remindersScrollArea.getBoundingClientRect();
        const bottomGap = 28;
        const topGap = 14;
        if (composerRect.bottom > areaRect.bottom - bottomGap) {
          remindersScrollArea.scrollTop += composerRect.bottom - areaRect.bottom + bottomGap;
          return;
        }
        if (composerRect.top < areaRect.top + topGap) {
          remindersScrollArea.scrollTop -= areaRect.top + topGap - composerRect.top;
        }
      }

      function closeReminderComposerIfEmpty(options = {}) {
        const force = !!options.force;
        if ((reminderNewText?.value || '').trim()) return;
        if ((reminderNewPriority?.value || '').trim()) return;
        if ((reminderNewDate?.value || '').trim()) return;
        if (reminderPendingLink) return;
        if (!force && isReminderPopoverActive()) return;
        reminderComposer?.classList.add('hidden');
        reminderComposer?.classList.remove('flex');
        if (reminderComposer) {
          reminderComposer.dataset.sectionId = '';
          reminderComposer.dataset.afterReminderId = '';
        }
        reminderShowComposerBtn?.classList.add('hidden');
        syncReminderAddZonesVisibility();
        hideReminderLinkDropdown();
        hideReminderPriorityDropdown();
        clearReminderPendingLink();
        updateReminderPriorityPicker('');
        if (reminderNewDate) {
          reminderNewDate._flatpickr?.clear();
          reminderNewDate.value = '';
        }
      }

      function formatReminderDate(value) {
        if (!value) return '';
        const parts = String(value).split('-');
        if (parts.length !== 3) return escapeHtml(value);
        return `${parts[2]}/${parts[1]}/${parts[0].slice(2)}`;
      }

      function initReminderDatePicker(input, onChange) {
        if (!input || input._flatpickr || !window.flatpickr) return input?._flatpickr || null;
        const globalReady = window.flatpickr.defaultConfig?.onReady;
        const readyHandlers = Array.isArray(globalReady)
          ? [...globalReady]
          : (globalReady ? [globalReady] : []);
        readyHandlers.push((_selectedDates, _dateStr, instance) => {
          if (instance?.altInput) {
            instance._positionElement = instance.altInput;
            instance.altInput.classList.add('reminder-date-trigger');
            instance.altInput.addEventListener('pointerdown', (event) => {
              markReminderPopoverBusy(900);
              event.stopPropagation();
            });
            instance.altInput.addEventListener('click', (event) => {
              markReminderPopoverBusy(900);
              event.stopPropagation();
            });
          }
          instance?.calendarContainer?.classList.add('reminder-date-calendar');
          instance?.calendarContainer?.addEventListener('pointerdown', (event) => {
            markReminderPopoverBusy(900);
            event.stopPropagation();
          });
          instance?.calendarContainer?.addEventListener('click', (event) => {
            markReminderPopoverBusy(900);
            event.stopPropagation();
          });
        });
        return flatpickr(input, {
          dateFormat: 'Y-m-d',
          altInput: true,
          altFormat: 'd/m/Y',
          allowInput: false,
          disableMobile: true,
          appendTo: document.body,
          positionElement: input,
          onReady: readyHandlers,
          onOpen: () => {
            reminderDatePickerOpen = true;
            markReminderPopoverBusy(900);
            hideReminderLinkDropdown();
            hideReminderPriorityDropdown();
            hideReminderRowPriorityDropdowns();
          },
          onClose: () => {
            markReminderPopoverBusy(900);
            setTimeout(() => {
              reminderDatePickerOpen = false;
            }, 120);
          },
          onChange: (_selectedDates, dateStr) => {
            if (typeof onChange === 'function') onChange(dateStr || '');
          },
        });
      }

      function markReminderPopoverBusy(duration = 240) {
        reminderPopoverBusyUntil = Date.now() + duration;
      }

      function isReminderPopoverActive() {
        if (reminderDatePickerOpen) return true;
        if (Date.now() < reminderPopoverBusyUntil) return true;
        if (reminderPriorityDropdown && !reminderPriorityDropdown.classList.contains('hidden')) return true;
        if (remindersList?.querySelector('[data-reminder-priority-menu]:not(.hidden)')) return true;
        if (document.querySelector('.flatpickr-calendar.open')) return true;
        return false;
      }

      function forceCloseEmptyReminderComposer() {
        if (document.activeElement instanceof HTMLElement && reminderComposer?.contains(document.activeElement)) {
          document.activeElement.blur();
        }
        reminderDatePickerOpen = false;
        reminderPopoverBusyUntil = 0;
        closeReminderComposerIfEmpty({ force: true });
      }

      function settleReminderComposerOnExit(options = {}) {
        const force = !!options.force;
        if (!force && isReminderPopoverActive()) return;
        if (document.activeElement instanceof HTMLElement && reminderComposer?.contains(document.activeElement)) {
          document.activeElement.blur();
        }
        const text = reminderNewText?.value || '';
        if (String(text).trim()) {
          addReminder(text, reminderNewPriority?.value || '', reminderNewDate?.value || '', null, { openNext: false });
          return;
        }
        forceCloseEmptyReminderComposer();
      }

      function renderRemindersCounter() {
        if (!remindersCount) return;
        const pending = reminders.filter((item) => hasReminderText(item) && !item.done).length;
        remindersCount.classList.toggle('hidden', pending === 0);
        remindersCount.textContent = String(pending > 99 ? '99+' : pending);
      }

      function reminderLinkMarkup(link) {
        if (!link || !link.title) return '';
        const tone = link.type === 'task' ? 'text-indigo-600 bg-indigo-50 border-indigo-100' : 'text-sky-700 bg-sky-50 border-sky-100';
        const prefix = link.type === 'task' ? '#' : '#';
        return `<a href="${escapeHtml(reminderLinkUrl(link))}" class="inline-flex max-w-full rounded-full border px-2 py-0.5 text-[11px] font-bold ${tone} hover:brightness-95" data-reminder-link-open><span class="truncate">${prefix}${escapeHtml(link.title)}</span></a>`;
      }

      function reminderLinkUrl(link) {
        if (!link) return '/proyectos';
        const params = new URLSearchParams();
        if (link.type === 'task') {
          params.set('view', 'tareas');
          if (link.projectId) params.set('open_project', link.projectId);
          if (link.id) params.set('open_task', link.id);
        } else if (link.projectId || link.id) {
          params.set('open_project', link.projectId || link.id);
        }
        const query = params.toString();
        return query ? `/proyectos?${query}` : '/proyectos';
      }

      function renderReminderComposerLinkPreview() {
        if (!reminderComposerLinkPreview) return;
        if (!reminderPendingLink) {
          reminderComposerLinkPreview.classList.add('hidden');
          reminderComposerLinkPreview.innerHTML = '';
          return;
        }
        reminderComposerLinkPreview.classList.remove('hidden');
        reminderComposerLinkPreview.innerHTML = reminderLinkMarkup(reminderPendingLink);
      }

      function clearReminderPendingLink() {
        reminderPendingLink = null;
        renderReminderComposerLinkPreview();
      }

      function closeReminderDatePickers(exceptInput = null) {
        [reminderNewDate, ...Array.from(remindersList?.querySelectorAll('[data-reminder-date]') || [])].forEach((input) => {
          if (!input || input === exceptInput) return;
          input._flatpickr?.close();
        });
      }

      function resizeReminderCategoryInput(input) {
        if (!input) return;
        const length = Math.max(4, String(input.value || input.placeholder || 'Categoría').length);
        input.style.width = `${Math.min(22, length)}ch`;
      }

      function isAllRemindersCategoryId(categoryId = activeReminderCategoryId) {
        return String(categoryId || '') === ALL_REMINDERS_CATEGORY_ID;
      }

      function isRemindersPanelOpen() {
        return Boolean(remindersPanel && !remindersPanel.classList.contains('translate-x-[calc(100%+2rem)]'));
      }

      function moveReminderCategoryToFront(categoryId, options = {}) {
        const id = String(categoryId || '');
        if (!id || isAllRemindersCategoryId(id)) return false;
        const index = reminderCategoriesData.findIndex((category) => String(category.id) === id);
        if (index < 0) return false;
        if (options.deferIfOpen !== false && isRemindersPanelOpen()) {
          pendingReminderCategoryPromotionId = id;
          return false;
        }
        if (index === 0) return false;
        const [category] = reminderCategoriesData.splice(index, 1);
        reminderCategoriesData.unshift(category);
        return true;
      }

      function applyPendingReminderCategoryPromotion() {
        const id = pendingReminderCategoryPromotionId;
        pendingReminderCategoryPromotionId = '';
        return id ? moveReminderCategoryToFront(id, { deferIfOpen: false }) : false;
      }

      function touchReminderCategoryBySection(sectionId) {
        const section = reminderSections.find((item) => String(item.id) === String(sectionId || ''));
        return section ? moveReminderCategoryToFront(section.categoryId) : false;
      }

      function ensureReminderSectionForCategory(categoryId) {
        let sections = reminderSections.filter((section) => String(section.categoryId) === String(categoryId));
        if (!sections.length) {
          const section = { id: uid('section'), categoryId, title: '', collapsed: false };
          reminderSections.push(section);
          sections = [section];
        }
        return sections;
      }

      function renderActiveReminderCategoryTitle() {
        if (!reminderActiveCategoryTitle) return;
        const allMode = isAllRemindersCategoryId(activeReminderCategoryId);
        const activeCategory = reminderCategoriesData.find((category) => String(category.id) === String(activeReminderCategoryId));
        if (allMode || !activeCategory) {
          const viewLabel = reminderAllViewMode === 'priority' ? 'Prioridad' : 'Clientes';
          reminderActiveCategoryTitle.innerHTML = `
            <div class="flex items-center justify-between gap-3">
              <div class="text-xl font-extrabold leading-tight text-slate-900">Todos</div>
              <div class="relative">
                <button type="button" id="reminderAllViewBtn" aria-haspopup="menu" aria-expanded="false" class="inline-flex h-8 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-[11px] font-extrabold text-slate-600 shadow-sm transition-colors hover:border-slate-300 hover:bg-slate-50">
                  <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4"/></svg>
                  <span>${viewLabel}</span>
                  <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="reminderAllViewDropdown" role="menu" class="hidden absolute right-0 top-[calc(100%+0.4rem)] z-[90] w-52 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                  <div class="px-2.5 pb-1 pt-1 text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Organizar por</div>
                  <button type="button" role="menuitemradio" aria-checked="${reminderAllViewMode === 'clients'}" data-reminder-all-view="clients" class="flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left text-xs font-extrabold ${reminderAllViewMode === 'clients' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'}">
                    <span>Clientes</span>
                    <span class="${reminderAllViewMode === 'clients' ? '' : 'hidden'}">✓</span>
                  </button>
                  <button type="button" role="menuitemradio" aria-checked="${reminderAllViewMode === 'priority'}" data-reminder-all-view="priority" class="flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left text-xs font-extrabold ${reminderAllViewMode === 'priority' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'}">
                    <span>Prioridad</span>
                    <span class="${reminderAllViewMode === 'priority' ? '' : 'hidden'}">✓</span>
                  </button>
                </div>
              </div>
            </div>
          `;
          const viewBtn = reminderActiveCategoryTitle.querySelector('#reminderAllViewBtn');
          const viewDropdown = reminderActiveCategoryTitle.querySelector('#reminderAllViewDropdown');
          viewBtn?.addEventListener('click', (event) => {
            event.stopPropagation();
            const shouldOpen = viewDropdown?.classList.contains('hidden');
            viewDropdown?.classList.toggle('hidden', !shouldOpen);
            viewBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
          });
          viewDropdown?.addEventListener('click', (event) => {
            event.stopPropagation();
            const option = event.target?.closest?.('[data-reminder-all-view]');
            if (!option) return;
            reminderAllViewMode = option.getAttribute('data-reminder-all-view') === 'priority' ? 'priority' : 'clients';
            persistRemindersOnly();
            renderReminders();
          });
          return;
        }
        reminderActiveCategoryTitle.innerHTML = `
          <input id="reminderActiveCategoryName" data-reminder-active-category-title="${escapeHtml(activeCategory.id)}" value="${escapeHtml(activeCategory.title || 'Categoría')}" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="block w-full rounded-none border-0 bg-transparent p-0 text-xl font-extrabold leading-tight text-slate-900 outline-none ring-0 focus:border-0 focus:bg-transparent focus:outline-none focus:ring-0 focus:shadow-none">
        `;
        const input = reminderActiveCategoryTitle.querySelector('[data-reminder-active-category-title]');
        input?.addEventListener('input', () => {
          const id = input.getAttribute('data-reminder-active-category-title') || '';
          reminderCategoriesData = reminderCategoriesData.map((category) => String(category.id) === String(id) ? { ...category, title: input.value || 'Categoría' } : category);
          persistRemindersOnly();
          renderReminderCategories();
        });
        input?.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            input.blur();
            saveReminders();
          }
        });
      }

      function renderReminderCategories() {
        if (!reminderCategories) return;
        const totalPending = reminders.filter((item) => !item.done).length;
        const allActive = isAllRemindersCategoryId(activeReminderCategoryId);
        const allChip = `<button type="button" data-reminder-category="${ALL_REMINDERS_CATEGORY_ID}" class="shrink-0 inline-flex w-fit items-center gap-1.5 rounded-full border py-1.5 pl-3 pr-2 text-[11px] font-extrabold transition-colors ${allActive ? 'bg-[#111729] text-white border-[#111729] shadow-sm' : 'bg-white text-[#111729] border-slate-200 hover:border-[#111729] hover:bg-slate-50'}">
          <span>Todos</span>
          <span class="rounded-full px-1.5 py-0.5 text-[10px] leading-none ${allActive ? 'bg-white/14 text-white' : 'bg-slate-100 text-slate-500'}">${totalPending}</span>
        </button>`;
        const categoryChips = reminderCategoriesData.map((category) => {
          const active = category.id === activeReminderCategoryId;
          const sectionIds = reminderSections
            .filter((section) => String(section.categoryId) === String(category.id))
            .map((section) => String(section.id));
          const count = reminders.filter((item) => sectionIds.includes(String(item.sectionId || '')) && !item.done).length;
          return `<div data-reminder-category="${escapeHtml(category.id)}" class="shrink-0 inline-flex w-fit max-w-[13rem] cursor-pointer items-center gap-1.5 rounded-full border py-1.5 pl-3 pr-2 text-[11px] font-extrabold transition-colors ${active ? 'bg-[#111729] text-white border-[#111729] shadow-sm' : 'bg-white text-[#111729] border-slate-200 hover:border-[#111729] hover:bg-slate-50'}">
            <span class="truncate">${escapeHtml(category.title || 'Categoría')}</span>
            <span class="rounded-full px-1.5 py-0.5 text-[10px] leading-none ${active ? 'bg-white/14 text-white' : 'bg-slate-100 text-slate-500'}">${count}</span>
            <button type="button" data-reminder-category-delete="${escapeHtml(category.id)}" class="${reminderCategoriesData.length <= 1 ? 'hidden' : ''} inline-flex h-4 w-4 items-center justify-center rounded-full text-xs ${active ? 'text-white/65 hover:bg-white/10 hover:text-white' : 'text-slate-400 hover:bg-slate-100 hover:text-rose-500'}" title="Eliminar categoría" aria-label="Eliminar categoría">×</button>
          </div>`;
        }).join('');
        reminderCategories.innerHTML = allChip + categoryChips;
        requestAnimationFrame(updateReminderCategoryScrollControls);
        reminderCategories.querySelectorAll('[data-reminder-category]').forEach((btn) => {
          btn.addEventListener('click', () => {
            activeReminderCategoryId = btn.getAttribute('data-reminder-category') || 'default-cat';
            activeReminderSectionId = isAllRemindersCategoryId(activeReminderCategoryId)
              ? (reminderSections[0]?.id || activeReminderSectionId)
              : (reminderSections.find((section) => String(section.categoryId) === String(activeReminderCategoryId))?.id || activeReminderSectionId);
            saveReminders();
          });
        });
        reminderCategories.querySelectorAll('[data-reminder-category-delete]').forEach((btn) => {
          btn.addEventListener('click', (event) => {
            event.stopPropagation();
            const id = btn.getAttribute('data-reminder-category-delete') || '';
            if (reminderCategoriesData.length <= 1) return;
            const fallback = reminderCategoriesData.find((category) => category.id !== id);
            if (!fallback) return;
            const deletedSectionIds = new Set(
              reminderSections
                .filter((section) => String(section.categoryId) === String(id))
                .map((section) => String(section.id))
            );
            reminders = reminders.filter((item) => !deletedSectionIds.has(String(item.sectionId || '')));
            reminderSections = reminderSections.filter((section) => String(section.categoryId) !== String(id));
            reminderCategoriesData = reminderCategoriesData.filter((category) => category.id !== id);
            if (activeReminderCategoryId === id) activeReminderCategoryId = fallback.id;
            activeReminderSectionId = ensureReminderSectionForCategory(activeReminderCategoryId)[0]?.id || reminderSections[0]?.id || 'default';
            saveReminders();
          });
        });
      }

      function updateReminderCategoryScrollControls() {
        if (!reminderCategories || !reminderCategoriesPrevBtn || !reminderCategoriesNextBtn) return;
        const maxScrollLeft = Math.max(0, reminderCategories.scrollWidth - reminderCategories.clientWidth);
        const hasOverflow = maxScrollLeft > 2;
        reminderCategoriesPrevBtn.classList.toggle('hidden', !hasOverflow);
        reminderCategoriesPrevBtn.classList.toggle('inline-flex', hasOverflow);
        reminderCategoriesNextBtn.classList.toggle('hidden', !hasOverflow);
        reminderCategoriesNextBtn.classList.toggle('inline-flex', hasOverflow);
        reminderCategoriesPrevBtn.disabled = !hasOverflow || reminderCategories.scrollLeft <= 2;
        reminderCategoriesNextBtn.disabled = !hasOverflow || reminderCategories.scrollLeft >= maxScrollLeft - 2;
      }

      function scrollReminderCategories(direction) {
        if (!reminderCategories) return;
        const amount = Math.max(160, Math.round(reminderCategories.clientWidth * 0.7));
        reminderCategories.scrollBy({ left: direction * amount, behavior: 'smooth' });
      }

      function renderReminders() {
        if (!remindersList) return;
        if (!isAllRemindersCategoryId(activeReminderCategoryId) && !reminderCategoriesData.some((category) => category.id === activeReminderCategoryId)) {
          activeReminderCategoryId = ALL_REMINDERS_CATEGORY_ID;
        }
        const allMode = isAllRemindersCategoryId(activeReminderCategoryId);
        let categorySections = allMode
          ? ensureReminderSectionForCategory(reminderCategoriesData[0]?.id || activeReminderCategoryId)
          : ensureReminderSectionForCategory(activeReminderCategoryId);
        if (!categorySections.some((section) => section.id === activeReminderSectionId)) {
          activeReminderSectionId = categorySections[0]?.id || reminderSections[0]?.id || 'default';
        }
        renderRemindersCounter();
        renderReminderCategories();
        renderActiveReminderCategoryTitle();

        const reminderItemHtml = (item, sectionId, options = {}) => {
          const priority = priorityText(item.priority);
          const date = formatReminderDate(item.dueDate);
          const categoryLabel = options.showCategory
            ? reminderCategoriesData.find((category) => reminderSections.some((section) => (
                String(section.id) === String(item.sectionId || '')
                && String(section.categoryId) === String(category.id)
              )))?.title
            : '';
          const metaHtml = (categoryLabel || priority || date || item.link) ? `<div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-bold group-focus-within:hidden">
                ${categoryLabel ? `<span class="inline-flex h-6 items-center rounded-full bg-slate-100 px-2 text-[10px] font-extrabold text-slate-500">${escapeHtml(categoryLabel)}</span>` : ''}
                ${priority ? `<span class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[10px] ${priorityClass(item.priority)}">${reminderPriorityIcon(item.priority)}${priority}</span>` : ''}
                ${date ? `<span class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-white px-2.5 py-0 text-[10px] font-extrabold leading-none text-slate-500">${date}</span>` : ''}
                ${item.link ? reminderLinkMarkup(item.link) : ''}
              </div>` : '';
          return `<div class="reminder-row group relative flex items-start gap-2.5 rounded-2xl py-0.5 transition-colors" draggable="true" data-reminder-id="${escapeHtml(item.id)}" data-reminder-section-id="${escapeHtml(sectionId)}">
            <button type="button" data-reminder-toggle="${escapeHtml(item.id)}" class="relative mt-[6px] inline-flex h-6 w-6 shrink-0 items-center justify-center overflow-visible rounded-full border-2 ${item.done ? 'border-[#f2fda2] bg-[#f2fda2] text-slate-900' : 'border-slate-300 bg-white text-transparent'}">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
            </button>
            <div class="min-w-0 flex-1">
              <div class="reminder-text-frame relative">
                <textarea data-reminder-text="${escapeHtml(item.id)}" rows="1" class="relative z-10 block w-full rounded-none border-0 bg-transparent px-1.5 pt-1 pb-0 text-base font-medium leading-6 ${item.done ? 'text-slate-400 line-through' : 'text-slate-800'} outline-none ring-0 focus:bg-transparent focus:outline-none focus:ring-0 focus:shadow-none" placeholder="Recordatorio">${escapeHtml(item.text || '')}</textarea>
              </div>
              ${metaHtml}
              <div class="mt-2 hidden group-focus-within:flex flex-nowrap items-center gap-1.5">
                <div class="relative">
                  ${reminderPriorityButtonHtml(item.priority, `data-reminder-priority-edit="${escapeHtml(item.id)}"`)}
                  ${reminderPriorityMenuHtml(item.priority, `data-reminder-priority-set="${escapeHtml(item.id)}"`, item.id)}
                </div>
                <input data-reminder-date="${escapeHtml(item.id)}" type="text" value="${escapeHtml(item.dueDate || '')}" readonly placeholder="Fecha" class="h-7 w-28 rounded-full border border-slate-200 bg-white px-2.5 py-0 text-[10px] font-extrabold leading-none text-slate-500 outline-none placeholder:text-slate-500 focus:border-[#f2fda2]">
                ${item.link ? reminderLinkMarkup(item.link) : ''}
              </div>
            </div>
            <button type="button" data-reminder-delete="${escapeHtml(item.id)}" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 text-sm font-bold">×</button>
          </div>`;
        };

        const sectionHtml = (section, siblingSections, compact = false) => {
          const items = reminders.filter((item) => String(item.sectionId || 'default') === String(section.id) && hasReminderText(item));
          const collapsed = !!section.collapsed;
          const title = String(section.title || '').trim();
          const isImplicitSection = !title || title.toLowerCase() === 'recordatorios';
          const itemHtml = items.map((item) => reminderItemHtml(item, section.id)).join('');
          return `<section class="${isImplicitSection ? '' : 'mt-6 border-t-2 border-slate-100 pt-5 first:mt-0 first:border-t-0 first:pt-0'}" data-reminder-section="${escapeHtml(section.id)}">
            <div class="${isImplicitSection ? 'hidden' : 'flex'} items-center justify-between gap-2">
              <button type="button" data-reminder-section-collapse="${escapeHtml(section.id)}" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 hover:bg-slate-50 hover:text-slate-700" title="${collapsed ? 'Abrir lista' : 'Compactar lista'}" aria-label="${collapsed ? 'Abrir lista' : 'Compactar lista'}">
                <svg class="h-3 w-3 transition-transform ${collapsed ? '-rotate-90' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
              </button>
              <input data-reminder-section-title="${escapeHtml(section.id)}" value="${escapeHtml(section.title || 'Lista')}" class="min-w-0 flex-1 rounded-xl border-0 bg-transparent px-1.5 py-0.5 ${compact ? 'text-lg' : 'text-xl'} font-black leading-tight text-slate-900 outline-none ring-0 focus:bg-transparent focus:outline-none focus:ring-0 focus:shadow-none">
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500">${items.filter((item) => !item.done).length}</span>
              <button type="button" data-reminder-section-delete="${escapeHtml(section.id)}" class="text-slate-300 hover:text-rose-500 text-sm font-bold ${siblingSections.length <= 1 ? 'hidden' : ''}">×</button>
            </div>
            <div class="${collapsed && !isImplicitSection ? 'hidden' : ''} ${isImplicitSection ? 'mt-0' : 'mt-2'} space-y-0.5" data-reminder-section-items="${escapeHtml(section.id)}">${itemHtml}</div>
            <button type="button" data-reminder-section-add="${escapeHtml(section.id)}" class="${collapsed && !isImplicitSection ? 'hidden' : ''} group mt-1 flex h-8 w-full items-center gap-2.5 py-1 text-left" title="Añadir recordatorio" aria-label="Añadir recordatorio">
              <span class="inline-flex h-6 w-6 shrink-0 rounded-full border-2 border-dotted border-slate-300 bg-white/10 transition-colors group-hover:border-slate-400"></span>
              <span class="pointer-events-none text-sm font-medium text-slate-300 opacity-0 group-hover:opacity-100">Nuevo recordatorio</span>
            </button>
          </section>`;
        };

        const clientSectionsHtml = () => reminderCategoriesData.map((category) => {
              const sections = ensureReminderSectionForCategory(category.id);
              const sectionIds = sections.map((section) => String(section.id));
              const categoryPending = reminders.filter((item) => sectionIds.includes(String(item.sectionId || '')) && hasReminderText(item) && !item.done).length;
              const collapsed = !!category.collapsed;
              return `<section class="rounded-2xl border border-slate-100 bg-white/70 px-3 py-2.5 shadow-sm" data-reminder-category-group="${escapeHtml(category.id)}">
                <div class="flex items-center gap-2">
                  <button type="button" data-reminder-category-collapse="${escapeHtml(category.id)}" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#111729] text-white/90 hover:bg-slate-800" title="${collapsed ? 'Abrir categoría' : 'Compactar categoría'}" aria-label="${collapsed ? 'Abrir categoría' : 'Compactar categoría'}">
                    <svg class="h-3 w-3 transition-transform ${collapsed ? '-rotate-90' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                  </button>
                  <div class="min-w-0 flex-1">
                    <div class="truncate text-xl font-black leading-tight text-slate-900">${escapeHtml(category.title || 'Categoría')}</div>
                  </div>
                  <span class="rounded-full bg-[#f2fda2] px-2 py-0.5 text-[11px] font-black text-slate-900">${categoryPending}</span>
                </div>
                <div class="${collapsed ? 'hidden' : ''} mt-3 space-y-4 pl-6">
                  ${sections.map((section) => sectionHtml(section, sections, true)).join('')}
                </div>
              </section>`;
            }).join('');

        const prioritySectionsHtml = () => groupRemindersByPriority(reminders).map((group) => {
          const groupId = group.key || 'none';
          const collapsed = !!reminderPriorityCollapsed[groupId];
          const pendingCount = group.items.filter((item) => !item.done).length;
          const groupClass = group.key ? priorityClass(group.key) : 'border-slate-200 bg-slate-50 text-slate-600';
          return `<section class="rounded-2xl border border-slate-100 bg-white/70 px-3 py-2.5 shadow-sm" data-reminder-priority-group="${escapeHtml(groupId)}">
            <div class="flex items-center gap-2">
              <button type="button" data-reminder-priority-collapse="${escapeHtml(groupId)}" aria-expanded="${!collapsed}" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#111729] text-white/90 hover:bg-slate-800" title="${collapsed ? 'Abrir prioridad' : 'Compactar prioridad'}" aria-label="${collapsed ? 'Abrir prioridad' : 'Compactar prioridad'}">
                <svg class="h-3 w-3 transition-transform ${collapsed ? '-rotate-90' : ''}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
              </button>
              <span class="inline-flex h-7 min-w-0 items-center gap-1.5 rounded-full border px-2.5 text-xs font-extrabold ${groupClass}">
                ${group.key ? reminderPriorityIcon(group.key) : '<span class="h-2 w-2 rounded-full bg-slate-300"></span>'}
                ${escapeHtml(group.label)}
              </span>
              <span class="ml-auto rounded-full bg-[#f2fda2] px-2 py-0.5 text-[11px] font-black text-slate-900">${pendingCount}</span>
            </div>
            <div class="${collapsed ? 'hidden' : ''} mt-3 space-y-0.5 pl-6">
              ${group.items.length
                ? group.items.map((item) => reminderItemHtml(item, item.sectionId || 'default', { showCategory: true })).join('')
                : '<div class="py-3 text-center text-xs font-semibold text-slate-400">No hay recordatorios</div>'}
            </div>
          </section>`;
        }).join('');

        const sectionsHtml = allMode
          ? (reminderAllViewMode === 'priority' ? prioritySectionsHtml() : clientSectionsHtml())
          : categorySections.map((section) => sectionHtml(section, categorySections)).join('');

        remindersList.innerHTML = sectionsHtml;
        bindReminderRows();
      }

      function addReminder(text, priority, dueDate, link = null, options = {}) {
        const clean = String(text || '').trim();
        if (!clean) return '';
        const safeDate = /^\d{4}-\d{2}-\d{2}$/.test(String(dueDate || '')) ? String(dueDate) : '';
        const linkedItem = link || reminderPendingLink || null;
        const targetSectionId = options.sectionId || reminderComposer?.dataset.sectionId || activeReminderSectionId || reminderSections[0]?.id || 'default';
        const insertAfterId = options.afterId ?? reminderComposer?.dataset.afterReminderId ?? '';
        const reminder = {
          id: uid('rem'),
          sectionId: targetSectionId,
          text: linkedItem ? (clean.replace(/#\S*$/, '').trim() || clean) : clean,
          priority: normalizeReminderPriority(priority),
          dueDate: safeDate,
          link: linkedItem,
          done: false,
          createdAt: Date.now(),
          updatedAt: Date.now(),
        };
        const insertIndex = insertAfterId
          ? reminders.findIndex((item) => String(item.id) === String(insertAfterId) && String(item.sectionId || 'default') === String(targetSectionId))
          : -1;
        if (insertIndex >= 0) {
          reminders.splice(insertIndex + 1, 0, reminder);
        } else {
          reminders.push(reminder);
        }
        touchReminderCategoryBySection(targetSectionId);
        if (reminderNewText) reminderNewText.value = '';
        updateReminderPriorityPicker('');
        if (reminderNewDate) {
          reminderNewDate._flatpickr?.clear();
          reminderNewDate.value = '';
        }
        clearReminderPendingLink();
        hideReminderLinkDropdown();
        hideReminderPriorityDropdown();
        saveReminders();
        if (options.openNext !== false) {
          openReminderComposer(targetSectionId, reminder.id);
        } else {
          closeReminderComposerIfEmpty({ force: true });
        }
        return reminder.id;
      }

      function autoSizeReminderText(input) {
        if (!input || input.tagName !== 'TEXTAREA') return;
        input.style.height = 'auto';
        input.style.height = `${Math.max(30, input.scrollHeight)}px`;
      }

      function reminderSearchText(value) {
        return String(value || '')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .replace(/\s+/g, ' ')
          .trim();
      }

      function reminderSectionForAi(sectionTitle) {
        const cleanTitle = String(sectionTitle || '').trim();
        if (!cleanTitle) {
          return activeReminderSectionId || reminderSections[0]?.id || 'default';
        }

        const categoryId = activeReminderCategoryId || reminderCategoriesData[0]?.id || 'default-cat';
        const normalizedTitle = reminderSearchText(cleanTitle);
        const existing = reminderSections.find((section) => (
          String(section.categoryId || categoryId) === String(categoryId)
          && reminderSearchText(section.title || '') === normalizedTitle
        ));
        if (existing) {
          return existing.id;
        }

        const section = {
          id: uid('section'),
          categoryId,
          title: cleanTitle,
          collapsed: false,
        };
        reminderSections.push(section);
        return section.id;
      }

      function resolveAiReminderLink(action) {
        const link = action?.link || null;
        if (link?.type && link?.id) {
          return {
            type: link.type,
            id: String(link.id),
            title: String(link.title || (link.type === 'task' ? 'Tarea' : 'Proyecto')),
            subtitle: String(link.subtitle || ''),
            projectId: String(link.projectId || link.id || ''),
          };
        }

        const taskId = String(action?.task?.id || '');
        const taskTitle = reminderSearchText(action?.task?.title || action?.task || '');
        const projectId = String(action?.project?.id || action?.task?.projectId || '');
        const projectTitle = reminderSearchText(action?.project?.title || action?.project || '');

        if (taskId) {
          const byTaskId = reminderLinkOptions.find((item) => item.type === 'task' && String(item.id) === taskId);
          if (byTaskId) return byTaskId;
        }

        if (taskTitle) {
          const byTaskTitle = reminderLinkOptions.find((item) => (
            item.type === 'task'
            && (!projectId || String(item.projectId || '') === projectId)
            && reminderSearchText(item.title || '') === taskTitle
          )) || reminderLinkOptions.find((item) => (
            item.type === 'task'
            && reminderSearchText(item.title || '').includes(taskTitle)
          ));
          if (byTaskTitle) return byTaskTitle;
        }

        if (projectId) {
          const byProjectId = reminderLinkOptions.find((item) => item.type === 'project' && String(item.id) === projectId);
          if (byProjectId) return byProjectId;
        }

        if (projectTitle) {
          return reminderLinkOptions.find((item) => (
            item.type === 'project'
            && (reminderSearchText(item.title || '') === projectTitle || reminderSearchText(item.title || '').includes(projectTitle))
          )) || null;
        }

        return null;
      }

      window.__infocusAiCreateReminder = async function(action) {
        const text = String(action?.text || '').trim();
        if (!text) {
          throw new Error('reminder_text_missing');
        }

        loadReminders();
        await loadReminderLinkOptions();
        const sectionId = reminderSectionForAi(action?.sectionTitle || '');
        activeReminderSectionId = sectionId;
        const link = resolveAiReminderLink(action);
        const id = addReminder(text, action?.priority || '', action?.dueDate || '', link, { sectionId, afterId: '' });
        if (!id) {
          throw new Error('reminder_create_failed');
        }

        openRemindersPanel();
        setTimeout(() => {
          const row = remindersList?.querySelector(`[data-reminder-id="${CSS.escape(id)}"]`);
          row?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          row?.classList.add('bg-[#f4fdac]/45');
          setTimeout(() => row?.classList.remove('bg-[#f4fdac]/45'), 900);
        }, 80);

        return { ok: true, id };
      };

      window.__infocusAiUndoReminder = async function(id) {
        const reminderId = String(id || '').trim();
        if (!reminderId) return false;
        loadReminders();
        const beforeCount = reminders.length;
        reminders = reminders.filter((item) => String(item.id || '') !== reminderId);
        if (reminders.length === beforeCount) return false;
        saveReminders();
        return true;
      };

      function playReminderCheckBurst(button) {
        if (!button) return;
        button.querySelector('.reminder-check-burst')?.remove();
        const burst = document.createElement('span');
        burst.className = 'reminder-check-burst';
        const points = [
          ['-18px', '-16px', '0ms'],
          ['3px', '-23px', '25ms'],
          ['19px', '-12px', '45ms'],
          ['22px', '5px', '15ms'],
          ['12px', '20px', '55ms'],
          ['-8px', '22px', '35ms'],
          ['-23px', '7px', '20ms'],
          ['-20px', '-4px', '60ms'],
        ];
        points.forEach(([x, y, delay]) => {
          const spark = document.createElement('i');
          spark.style.setProperty('--x', x);
          spark.style.setProperty('--y', y);
          spark.style.setProperty('--delay', delay);
          burst.appendChild(spark);
        });
        button.appendChild(burst);
        button.animate([
          { transform: 'scale(1)' },
          { transform: 'scale(1.16)', offset: 0.28 },
          { transform: 'scale(0.94)', offset: 0.58 },
          { transform: 'scale(1)' },
        ], { duration: 360, easing: 'cubic-bezier(.18,.84,.36,1)' });
        setTimeout(() => burst.remove(), 760);
      }

      function bindReminderRows() {
        let draggedReminderId = '';
        const clearReminderDropMarkers = () => {
          remindersList?.querySelectorAll('[data-reminder-id]').forEach((row) => {
            row.classList.remove('bg-[#f4fdac]/35');
            row.style.boxShadow = '';
          });
          remindersList?.querySelectorAll('[data-reminder-section-items], [data-reminder-section-add]').forEach((node) => {
            node.classList.remove('bg-[#f4fdac]/20', 'ring-2', 'ring-[#dff75f]/60');
          });
        };
        const getReminderDropPlacement = (row, event) => {
          const rect = row.getBoundingClientRect();
          return event.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
        };
        const showReminderDropMarker = (row, placement) => {
          clearReminderDropMarkers();
          row.style.boxShadow = placement === 'before'
            ? 'inset 0 2px 0 #dff75f'
            : 'inset 0 -2px 0 #dff75f';
        };
        const moveReminder = (sourceId, targetSectionId, targetId = '', placement = 'after') => {
          if (!sourceId) return false;
          const dragged = reminders.find((item) => String(item.id) === String(sourceId));
          if (!dragged) return false;
          const next = reminders.filter((item) => String(item.id) !== String(sourceId));
          const moved = { ...dragged, sectionId: targetSectionId || dragged.sectionId || activeReminderSectionId, updatedAt: Date.now() };

          if (targetId && String(targetId) !== String(sourceId)) {
            const targetIndex = next.findIndex((item) => String(item.id) === String(targetId));
            if (targetIndex >= 0) {
              next.splice(placement === 'after' ? targetIndex + 1 : targetIndex, 0, moved);
              reminders = next;
              return true;
            }
          }

          let lastSectionIndex = -1;
          next.forEach((item, index) => {
            if (String(item.sectionId || 'default') === String(moved.sectionId || 'default')) {
              lastSectionIndex = index;
            }
          });
          next.splice(lastSectionIndex >= 0 ? lastSectionIndex + 1 : next.length, 0, moved);
          reminders = next;
          return true;
        };
        remindersList?.querySelectorAll('[data-reminder-category-collapse]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-reminder-category-collapse') || '';
            reminderCategoriesData = reminderCategoriesData.map((category) => (
              String(category.id) === String(id) ? { ...category, collapsed: !category.collapsed } : category
            ));
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-priority-collapse]').forEach((btn) => {
          btn.addEventListener('click', (event) => {
            event.stopPropagation();
            const id = btn.getAttribute('data-reminder-priority-collapse') || 'none';
            reminderPriorityCollapsed[id] = !reminderPriorityCollapsed[id];
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-section-collapse]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-reminder-section-collapse') || '';
            reminderSections = reminderSections.map((section) => (
              String(section.id) === String(id) ? { ...section, collapsed: !section.collapsed } : section
            ));
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-section-add]').forEach((btn) => {
          btn.addEventListener('click', () => {
            openReminderComposer(btn.getAttribute('data-reminder-section-add') || activeReminderSectionId);
          });
          btn.addEventListener('dragover', (event) => {
            event.preventDefault();
            clearReminderDropMarkers();
            btn.classList.add('bg-[#f4fdac]/20', 'ring-2', 'ring-[#dff75f]/60');
          });
          btn.addEventListener('dragleave', () => {
            btn.classList.remove('bg-[#f4fdac]/20', 'ring-2', 'ring-[#dff75f]/60');
          });
          btn.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const sourceId = draggedReminderId || event.dataTransfer?.getData('text/plain') || '';
            const sectionId = btn.getAttribute('data-reminder-section-add') || activeReminderSectionId;
            if (moveReminder(sourceId, sectionId)) {
              draggedReminderId = '';
              clearReminderDropMarkers();
              saveReminders();
            }
          });
        });
        remindersList?.querySelectorAll('[data-reminder-section-items]').forEach((box) => {
          box.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (event.target?.closest?.('[data-reminder-id]')) return;
            clearReminderDropMarkers();
            box.classList.add('bg-[#f4fdac]/20');
          });
          box.addEventListener('dragleave', () => {
            box.classList.remove('bg-[#f4fdac]/20');
          });
          box.addEventListener('drop', (event) => {
            event.preventDefault();
            if (event.target?.closest?.('[data-reminder-id]')) return;
            if (!draggedReminderId) return;
            const sectionId = box.getAttribute('data-reminder-section-items') || activeReminderSectionId;
            if (moveReminder(draggedReminderId, sectionId)) {
              draggedReminderId = '';
              clearReminderDropMarkers();
              saveReminders();
            }
          });
        });
        remindersList?.querySelectorAll('[data-reminder-id]').forEach((row) => {
          row.addEventListener('dragstart', (event) => {
            draggedReminderId = row.getAttribute('data-reminder-id') || '';
            row.classList.add('opacity-50', 'bg-slate-50');
            event.dataTransfer?.setData('text/plain', draggedReminderId);
            if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
          });
          row.addEventListener('dragend', () => {
            row.classList.remove('opacity-50', 'bg-slate-50');
            clearReminderDropMarkers();
          });
          row.addEventListener('dragover', (event) => {
            event.preventDefault();
            const sourceId = draggedReminderId || event.dataTransfer?.getData('text/plain') || '';
            const targetId = row.getAttribute('data-reminder-id') || '';
            if (!sourceId || sourceId === targetId) return;
            showReminderDropMarker(row, getReminderDropPlacement(row, event));
          });
          row.addEventListener('dragleave', () => {
            row.style.boxShadow = '';
          });
          row.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            row.style.boxShadow = '';
            const sourceId = draggedReminderId || event.dataTransfer?.getData('text/plain') || '';
            const targetId = row.getAttribute('data-reminder-id') || '';
            const targetSectionId = row.getAttribute('data-reminder-section-id') || activeReminderSectionId;
            if (!sourceId || !targetId || sourceId === targetId) return;
            const placement = getReminderDropPlacement(row, event);
            if (moveReminder(sourceId, targetSectionId, targetId, placement)) {
              draggedReminderId = '';
              clearReminderDropMarkers();
              saveReminders();
            }
          });
        });
        remindersList?.querySelectorAll('[data-reminder-toggle]').forEach((btn) => {
          btn.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            event.stopPropagation();
          });
          btn.addEventListener('click', (event) => {
            event.stopPropagation();
            const id = btn.getAttribute('data-reminder-toggle');
            const current = reminders.find((item) => item.id === id);
            if (!current) return;
            const updated = { ...current, done: !current.done, updatedAt: Date.now() };
            const without = reminders.filter((item) => item.id !== id);
            if (updated.done) {
              const insertIndex = without.reduce((lastIndex, item, index) => (
                String(item.sectionId || 'default') === String(updated.sectionId || 'default') && !item.done ? index : lastIndex
              ), -1);
              without.splice(insertIndex + 1, 0, updated);
            } else {
              const sectionStart = without.findIndex((item) => String(item.sectionId || 'default') === String(updated.sectionId || 'default'));
              const firstDone = without.findIndex((item) => String(item.sectionId || 'default') === String(updated.sectionId || 'default') && item.done);
              const targetIndex = firstDone >= 0 ? firstDone : (sectionStart >= 0 ? sectionStart : without.length);
              without.splice(targetIndex, 0, updated);
            }
            reminders = without;
            touchReminderCategoryBySection(updated.sectionId);
            if (updated.done) {
              btn.classList.remove('border-slate-300', 'bg-white', 'text-transparent');
              btn.classList.add('border-[#f2fda2]', 'bg-[#f2fda2]', 'text-slate-900');
              btn.disabled = true;
              playReminderCheckBurst(btn);
              persistRemindersOnly();
              setTimeout(() => renderReminders(), 430);
            } else {
              saveReminders();
            }
          });
        });
        remindersList?.querySelectorAll('[data-reminder-delete]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-reminder-delete');
            reminders = reminders.filter((item) => item.id !== id);
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-text]').forEach((input) => {
          autoSizeReminderText(input);
          const removeReminderIfEmpty = () => {
            const id = input.getAttribute('data-reminder-text') || '';
            if (!id || String(input.value || '').trim()) return false;
            reminders = reminders.filter((item) => String(item.id || '') !== String(id));
            saveReminders();
            return true;
          };
          input.addEventListener('input', () => {
            const id = input.getAttribute('data-reminder-text');
            const row = input.closest('[data-reminder-id]');
            const sectionId = row?.getAttribute('data-reminder-section-id') || '';
            autoSizeReminderText(input);
            reminders = reminders.map((item) => item.id === id ? { ...item, text: input.value, updatedAt: Date.now() } : item);
            const categoryMoved = touchReminderCategoryBySection(sectionId);
            maybeShowReminderLinkDropdown(input, id);
            persistRemindersOnly();
            if (categoryMoved) renderReminderCategories();
          });
          input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const id = input.getAttribute('data-reminder-text') || '';
            const row = input.closest('[data-reminder-id]');
            const sectionId = row?.getAttribute('data-reminder-section-id') || activeReminderSectionId;
            if (!String(input.value || '').trim()) {
              reminders = reminders.filter((item) => String(item.id || '') !== String(id));
              saveReminders();
              openReminderComposer(sectionId);
              return;
            }
            reminders = reminders.map((item) => item.id === id ? { ...item, text: input.value, updatedAt: Date.now() } : item);
            persistRemindersOnly();
            hideReminderLinkDropdown();
            hideReminderPriorityDropdown();
            hideReminderRowPriorityDropdowns();
            openReminderComposer(sectionId, id);
          });
          input.addEventListener('blur', () => {
            setTimeout(() => removeReminderIfEmpty(), 80);
          });
        });
        remindersList?.querySelectorAll('[data-reminder-priority]').forEach((select) => {
          select.addEventListener('change', () => {
            const id = select.getAttribute('data-reminder-priority');
            const current = reminders.find((item) => item.id === id);
            reminders = reminders.map((item) => item.id === id ? { ...item, priority: normalizeReminderPriority(select.value), updatedAt: Date.now() } : item);
            touchReminderCategoryBySection(current?.sectionId);
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-priority-edit]').forEach((btn) => {
          btn.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            markReminderPopoverBusy();
          });
          btn.addEventListener('click', (event) => {
            event.stopPropagation();
            markReminderPopoverBusy();
            const id = btn.getAttribute('data-reminder-priority-edit') || '';
            const menu = remindersList.querySelector(`[data-reminder-priority-menu="${CSS.escape(id)}"]`);
            const shouldOpen = menu?.classList.contains('hidden');
            closeReminderDatePickers();
            hideReminderRowPriorityDropdowns(id);
            hideReminderPriorityDropdown();
            hideReminderLinkDropdown();
            if (menu) menu.classList.toggle('hidden', !shouldOpen);
          });
        });
        remindersList?.querySelectorAll('[data-reminder-priority-menu]').forEach((menu) => {
          menu.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            markReminderPopoverBusy();
          });
          menu.addEventListener('click', (event) => {
            event.stopPropagation();
            markReminderPopoverBusy();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-priority-set]').forEach((btn) => {
          btn.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            markReminderPopoverBusy();
          });
          btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            markReminderPopoverBusy();
            const id = btn.getAttribute('data-reminder-priority-set') || '';
            const value = normalizeReminderPriority(btn.getAttribute('data-reminder-priority-value') || '');
            const current = reminders.find((item) => item.id === id);
            reminders = reminders.map((item) => item.id === id ? { ...item, priority: value, updatedAt: Date.now() } : item);
            touchReminderCategoryBySection(current?.sectionId);
            hideReminderRowPriorityDropdowns();
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-date]').forEach((input) => {
          const picker = initReminderDatePicker(input, (dateStr) => {
            markReminderPopoverBusy();
            const id = input.getAttribute('data-reminder-date');
            const current = reminders.find((item) => item.id === id);
            reminders = reminders.map((item) => item.id === id ? { ...item, dueDate: dateStr, updatedAt: Date.now() } : item);
            const categoryMoved = touchReminderCategoryBySection(current?.sectionId);
            persistRemindersOnly();
            if (categoryMoved) renderReminderCategories();
          });
          if (picker) return;
          input.addEventListener('change', () => {
            const id = input.getAttribute('data-reminder-date');
            const current = reminders.find((item) => item.id === id);
            reminders = reminders.map((item) => item.id === id ? { ...item, dueDate: input.value, updatedAt: Date.now() } : item);
            touchReminderCategoryBySection(current?.sectionId);
            saveReminders();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-section-title]').forEach((input) => {
          input.addEventListener('focus', () => {
            activeReminderSectionId = input.getAttribute('data-reminder-section-title') || 'default';
          });
          input.addEventListener('input', () => {
            const id = input.getAttribute('data-reminder-section-title');
            reminderSections = reminderSections.map((section) => section.id === id ? { ...section, title: input.value || 'Recordatorios' } : section);
            persistRemindersOnly();
          });
        });
        remindersList?.querySelectorAll('[data-reminder-section-delete]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-reminder-section-delete');
            const section = reminderSections.find((row) => String(row.id) === String(id));
            if (!section) return;
            const fallback = reminderSections.find((row) => String(row.categoryId) === String(section.categoryId) && String(row.id) !== String(id));
            if (!fallback) return;
            reminders = reminders.map((item) => item.sectionId === id ? { ...item, sectionId: fallback.id } : item);
            reminderSections = reminderSections.filter((section) => section.id !== id);
            if (activeReminderSectionId === id) activeReminderSectionId = fallback.id;
            saveReminders();
          });
        });
      }

      async function loadReminderLinkOptions() {
        if (reminderLinkOptions.length) return;
        try {
          const response = await fetch('/api/proyectos', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json().catch(() => ({}));
          const rows = Array.isArray(json) ? json : (Array.isArray(json.data) ? json.data : []);
          reminderLinkOptions = rows.filter((project) => !project?.archived).flatMap((project) => {
            const projectId = String(project?.id || '');
            const projectTitle = String(project?.titulo || project?.nombre || 'Proyecto sin nombre');
            const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
            return [
              { type: 'project', id: projectId, title: projectTitle, subtitle: 'Proyecto', projectId },
              ...tasks.map((task) => ({
                type: 'task',
                id: String(task?.id || task?.uid || task?.title || task?.texto || Math.random()),
                title: String(task?.title || task?.texto || task?.nombre || 'Tarea sin nombre'),
                subtitle: projectTitle,
                projectId,
              })),
            ];
          });
        } catch (_) {
          reminderLinkOptions = [];
        }
      }

      function hideReminderLinkDropdown() {
        reminderLinkDropdown?.classList.add('hidden');
        if (reminderLinkDropdown) reminderLinkDropdown.innerHTML = '';
        activeReminderLinkTarget = null;
      }

      function hideReminderPriorityDropdown() {
        reminderPriorityDropdown?.classList.add('hidden');
      }

      function hideReminderAllViewDropdown() {
        const dropdown = reminderActiveCategoryTitle?.querySelector('#reminderAllViewDropdown');
        const button = reminderActiveCategoryTitle?.querySelector('#reminderAllViewBtn');
        dropdown?.classList.add('hidden');
        button?.setAttribute('aria-expanded', 'false');
      }

      function hideReminderRowPriorityDropdowns(exceptId = '') {
        remindersList?.querySelectorAll('[data-reminder-priority-menu]').forEach((menu) => {
          if (exceptId && menu.getAttribute('data-reminder-priority-menu') === exceptId) return;
          menu.classList.add('hidden');
        });
      }

      function currentHashQuery(value) {
        const match = String(value || '').match(/#([^\s#]*)$/);
        return match ? match[1].toLowerCase() : null;
      }

      async function maybeShowReminderLinkDropdown(input, reminderId = null) {
        const query = currentHashQuery(input.value);
        if (query === null || !reminderLinkDropdown) {
          hideReminderLinkDropdown();
          return;
        }
        await loadReminderLinkOptions();
        activeReminderLinkTarget = { input, reminderId };
        const filtered = reminderLinkOptions
          .filter((item) => item.title.toLowerCase().includes(query) || item.subtitle.toLowerCase().includes(query))
          .slice(0, 8);
        if (input === reminderNewText) {
          input.parentElement?.appendChild(reminderLinkDropdown);
          reminderLinkDropdown.style.position = '';
          reminderLinkDropdown.style.left = '';
          reminderLinkDropdown.style.right = '';
          reminderLinkDropdown.style.top = '';
          reminderLinkDropdown.style.width = '';
        } else {
          document.body.appendChild(reminderLinkDropdown);
          const rect = input.getBoundingClientRect();
          reminderLinkDropdown.style.position = 'fixed';
          reminderLinkDropdown.style.left = `${Math.max(12, rect.left)}px`;
          reminderLinkDropdown.style.right = 'auto';
          reminderLinkDropdown.style.top = `${Math.min(window.innerHeight - 260, rect.bottom + 6)}px`;
          reminderLinkDropdown.style.width = `${Math.min(320, Math.max(240, rect.width))}px`;
        }
        if (!filtered.length) {
          reminderLinkDropdown.innerHTML = '<div class="px-3 py-3 text-xs font-semibold text-slate-400">No encontré proyectos o tareas.</div>';
        } else {
          reminderLinkDropdown.innerHTML = filtered.map((item, index) => `
            <button type="button" data-reminder-link-index="${index}" class="w-full rounded-xl px-3 py-2 text-left hover:bg-slate-50">
              <div class="text-sm font-extrabold text-slate-800 truncate">#${escapeHtml(item.title)}</div>
              <div class="text-[11px] font-semibold text-slate-400 truncate">${escapeHtml(item.subtitle)}</div>
            </button>
          `).join('');
          reminderLinkDropdown.querySelectorAll('[data-reminder-link-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
              const item = filtered[Number(btn.getAttribute('data-reminder-link-index') || 0)];
              applyReminderLink(item);
            });
          });
        }
        reminderLinkDropdown.classList.remove('hidden');
      }

      function applyReminderLink(link) {
        if (!activeReminderLinkTarget || !link) return;
        const { input, reminderId } = activeReminderLinkTarget;
        const nextText = String(input.value || '').replace(/#([^\s#]*)$/, '').trim();
        input.value = nextText;
        if (reminderId) {
          reminders = reminders.map((item) => item.id === reminderId ? { ...item, text: nextText || item.text, link, updatedAt: Date.now() } : item);
          saveReminders();
        } else {
          reminderPendingLink = link;
          renderReminderComposerLinkPreview();
          reminderNewText?.focus();
        }
        hideReminderLinkDropdown();
      }

      function notificationModule(item) {
        const kind = String((item && item.kind) || '');
        if (['payment', 'invoice_sent', 'due_soon'].includes(kind)) return 'ventas';
        if (['overdue', 'upcoming', 'progress', 'timer_started'].includes(kind)) {
          return String((item && item.id) || '').includes('lead:') ? 'leads' : 'proyectos';
        }
        if (kind === 'meeting_reminder') return 'reuniones';
        if (kind === 'lead_reminder') return 'leads';
        if (kind === 'portal_access') return 'portal';
        return 'all';
      }

      function notificationVisual(item) {
        const kind = String((item && item.kind) || '');
        const title = String((item && item.title) || '').toLowerCase();
        if (kind === 'payment' || title.includes('pago')) {
          return { icon: 'fa-solid fa-dollar-sign', wrap: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        }
        if (kind === 'invoice_sent' || kind === 'due_soon' || title.includes('factura')) {
          return { icon: 'fa-regular fa-file-lines', wrap: 'bg-sky-50 text-sky-700 border-sky-200' };
        }
        if (kind === 'overdue') {
          return { icon: 'fa-solid fa-triangle-exclamation', wrap: 'bg-rose-50 text-rose-700 border-rose-200' };
        }
        if (kind === 'meeting_reminder') {
          return { icon: 'fa-regular fa-calendar-check', wrap: 'bg-violet-50 text-violet-700 border-violet-200' };
        }
        if (kind === 'lead_reminder' || String(item?.id || '').includes('lead:')) {
          return { icon: 'fa-solid fa-bullseye', wrap: 'bg-orange-50 text-orange-700 border-orange-200' };
        }
        if (kind === 'portal_access') {
          return { icon: 'fa-solid fa-right-to-bracket', wrap: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
        }
        if (kind === 'timer_started') {
          return { icon: 'fa-regular fa-clock', wrap: 'bg-amber-50 text-amber-700 border-amber-200' };
        }
        if (kind === 'progress' || kind === 'upcoming' || title.includes('proyecto')) {
          return { icon: 'fa-solid fa-diagram-project', wrap: 'bg-lime-50 text-lime-700 border-lime-200' };
        }
        return { icon: 'fa-regular fa-bell', wrap: 'bg-slate-50 text-slate-600 border-slate-200' };
      }

      function parseNotificationDate(value) {
        const raw = String(value || '').trim();
        if (!raw) return null;
        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
          const [year, month, day] = raw.split('-').map(Number);
          return new Date(year, month - 1, day);
        }
        const normalized = raw.includes(' ') && !raw.includes('T') ? raw.replace(' ', 'T') : raw;
        const parsed = new Date(normalized);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
      }

      function formatNotificationDate(value) {
        const date = parseNotificationDate(value);
        if (!date) return '';
        const raw = String(value || '');
        const hasTime = /\d{1,2}:\d{2}/.test(raw);
        const startOfDay = (input) => new Date(input.getFullYear(), input.getMonth(), input.getDate()).getTime();
        const today = startOfDay(new Date());
        const target = startOfDay(date);
        const diffDays = Math.round((target - today) / 86400000);
        const time = hasTime ? new Intl.DateTimeFormat('es-CO', { hour: '2-digit', minute: '2-digit' }).format(date) : '';
        let dayLabel = '';
        if (diffDays === 0) dayLabel = 'Hoy';
        else if (diffDays === 1) dayLabel = 'Mañana';
        else if (diffDays === -1) dayLabel = 'Ayer';
        else {
          dayLabel = new Intl.DateTimeFormat('es-CO', {
            day: '2-digit',
            month: 'short',
            year: date.getFullYear() === new Date().getFullYear() ? undefined : 'numeric',
          }).format(date);
        }
        return time ? `${dayLabel} · ${time}` : dayLabel;
      }

      function loadBrowserNotificationsSeen() {
        try {
          const parsed = JSON.parse(localStorage.getItem(BROWSER_NOTIFICATIONS_KEY) || '[]');
          browserNotificationsSeen = new Set(Array.isArray(parsed) ? parsed.map((id) => String(id || '')).filter(Boolean) : []);
        } catch (e) {
          browserNotificationsSeen = new Set();
        }
      }

      function rememberBrowserNotifications(ids) {
        ids.forEach((id) => {
          const safeId = String(id || '').trim();
          if (safeId) browserNotificationsSeen.add(safeId);
        });
        try {
          localStorage.setItem(BROWSER_NOTIFICATIONS_KEY, JSON.stringify(Array.from(browserNotificationsSeen).slice(-250)));
        } catch (e) {}
      }

      function browserNotificationsSupported() {
        return 'Notification' in window;
      }

      function webPushSupported() {
        return browserNotificationsSupported() && 'serviceWorker' in navigator && 'PushManager' in window && window.isSecureContext;
      }

      function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; i += 1) {
          outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
      }

      async function getInfocusServiceWorkerRegistration() {
        if (!('serviceWorker' in navigator)) return null;
        return navigator.serviceWorker.register('/infocus-sw.js');
      }

      async function subscribeWebPushNotifications() {
        if (!webPushSupported()) return false;
        const registration = await getInfocusServiceWorkerRegistration();
        if (!registration) return false;

        const keyResponse = await fetch('/api/header/notifications/push-key', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const keyJson = await keyResponse.json().catch(() => ({}));
        const publicKey = String(keyJson.public_key || '');
        if (!keyResponse.ok || !publicKey) return false;

        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
          subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey),
          });
        }

        const response = await fetch('/api/header/notifications/push-subscribe', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ subscription: subscription.toJSON() }),
        });

        return response.ok;
      }

      function updateBrowserNotificationsButton() {
        if (!browserNotificationsBtn) return;
        if (!browserNotificationsSupported()) {
          browserNotificationsBtn.classList.remove('hidden');
          browserNotificationsBtn.disabled = true;
          browserNotificationsBtn.textContent = 'Alertas no compatibles';
          browserNotificationsBtn.classList.add('opacity-60', 'cursor-not-allowed');
          return;
        }
        browserNotificationsBtn.classList.remove('hidden', 'opacity-60', 'cursor-not-allowed');
        browserNotificationsBtn.disabled = false;
        if (Notification.permission === 'granted') {
          browserNotificationsBtn.textContent = webPushSupported() ? 'Alertas activas' : 'Alertas activas aquí';
          browserNotificationsBtn.classList.add('border-lime-200', 'bg-lime-50', 'text-lime-800');
          browserNotificationsBtn.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-700');
        } else if (Notification.permission === 'denied') {
          browserNotificationsBtn.textContent = 'Alertas bloqueadas';
          browserNotificationsBtn.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
          browserNotificationsBtn.classList.remove('border-lime-200', 'bg-lime-50', 'text-lime-800');
        } else {
          browserNotificationsBtn.textContent = 'Activar alertas';
          browserNotificationsBtn.classList.remove('border-lime-200', 'bg-lime-50', 'text-lime-800', 'border-rose-200', 'bg-rose-50', 'text-rose-700');
        }
      }

      async function requestBrowserNotificationsPermission() {
        if (!browserNotificationsSupported()) return;
        try {
          const permission = await Notification.requestPermission();
          updateBrowserNotificationsButton();
          if (permission === 'granted') {
            const subscribed = await subscribeWebPushNotifications();
            rememberBrowserNotifications(notifications.filter((item) => !item.read).map((item) => item.id));
            if (window.showNotification) {
              window.showNotification(
                subscribed ? 'Alertas del navegador activadas, incluso con la app cerrada' : 'Alertas activadas solo mientras la app esté abierta',
                subscribed ? 'success' : 'warning'
              );
            }
          } else if (permission === 'denied' && window.showNotification) {
            window.showNotification('El navegador bloqueó las alertas. Actívalas desde permisos del sitio.', 'warning');
          }
        } catch (e) {
          updateBrowserNotificationsButton();
        }
      }

      function pushBrowserNotification(item) {
        if (!browserNotificationsSupported() || Notification.permission !== 'granted') return;
        const title = String(item?.title || 'Nueva notificación');
        const when = formatNotificationDate(item?.date);
        const body = [String(item?.message || '').trim(), when].filter(Boolean).join('\n');
        try {
          const notification = new Notification(title, {
            body,
            tag: 'infocus-' + String(item?.id || title),
            renotify: false,
            icon: '/favicon.ico',
          });
          notification.onclick = () => {
            window.focus();
            if (item?.id) markNotificationRead(String(item.id));
            if (item?.url) window.location.href = String(item.url);
            notification.close();
          };
        } catch (e) {}
      }

      function notifyNewBrowserNotifications(items) {
        const unread = (Array.isArray(items) ? items : []).filter((item) => item && !item.read && item.id);
        if (browserNotificationsInitialLoad) {
          rememberBrowserNotifications(unread.map((item) => item.id));
          browserNotificationsInitialLoad = false;
          return;
        }
        const fresh = unread.filter((item) => !browserNotificationsSeen.has(String(item.id)));
        if (!fresh.length) return;
        rememberBrowserNotifications(fresh.map((item) => item.id));
        if (!browserNotificationsSupported() || Notification.permission !== 'granted') return;
        fresh.slice(0, 3).forEach(pushBrowserNotification);
        if (fresh.length > 3) {
          pushBrowserNotification({
            id: 'summary:' + Date.now(),
            title: `${fresh.length} notificaciones nuevas`,
            message: 'Abre Infocus para revisar todas las notificaciones pendientes.',
            date: new Date().toISOString(),
            url: window.location.href,
          });
        }
      }

      function applyPresence(status) {
        const safe = statusMap[status] ? status : 'available';
        const info = statusMap[safe];

        if (presenceLabel) presenceLabel.textContent = info.label;
        if (presenceDot) {
          presenceDot.classList.remove('bg-emerald-500', 'bg-sky-500', 'bg-amber-500');
          presenceDot.classList.add(info.dotClass);
        }

        presenceButtons.forEach((btn) => {
          const active = btn.getAttribute('data-presence-status') === safe;
          btn.classList.toggle('bg-slate-900', active);
          btn.classList.toggle('text-white', active);
          btn.classList.toggle('border-slate-900', active);
          btn.classList.toggle('text-slate-700', !active);
        });

        try {
          localStorage.setItem('headerPresenceStatus', safe);
        } catch (e) {}
      }

      function renderNotificationCounter() {
        const unread = notifications.filter((item) => !item.read).length;
        if (notificationsDot) {
          notificationsDot.classList.toggle('hidden', unread === 0);
        }
        if (notificationsCount) {
          notificationsCount.classList.toggle('hidden', unread === 0);
          notificationsCount.textContent = String(unread > 99 ? '99+' : unread);
        }
      }

      function renderModuleCounters() {
        const source = currentTab === 'unread'
          ? notifications.filter((item) => !item.read)
          : notifications;
        const counts = { all: source.length, ventas: 0, proyectos: 0, reuniones: 0, leads: 0, portal: 0 };
        source.forEach((item) => {
          const mod = notificationModule(item);
          if (counts[mod] !== undefined) counts[mod] += 1;
        });
        moduleCountEls.forEach((el) => {
          const key = el.getAttribute('data-notif-count') || 'all';
          const val = counts[key] ?? 0;
          el.textContent = String(val > 99 ? '99+' : val);
        });
      }

      function renderNotifications() {
        const tabFiltered = currentTab === 'unread'
          ? notifications.filter((item) => !item.read)
          : notifications;
        const filtered = currentModule === 'all'
          ? tabFiltered
          : tabFiltered.filter((item) => notificationModule(item) === currentModule);
        renderModuleCounters();

        if (!filtered.length) {
          notificationsList.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-8 text-sm text-slate-500 text-center">No hay notificaciones para este filtro.</div>';
          return;
        }

        notificationsList.innerHTML = filtered.map((item) => {
          const tone = item.kind === 'overdue'
            ? 'border-rose-200 bg-rose-50'
            : (item.kind === 'upcoming' || item.kind === 'meeting_reminder' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50');
          const visual = notificationVisual(item);
          const dateLabel = formatNotificationDate(item.date);
          return `<a href="${escapeHtml(item.url || '#')}" data-notification-id="${escapeHtml(item.id)}" class="relative block rounded-xl border ${tone} px-3 py-3 pr-8 hover:bg-white transition-colors">
            <div class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border ${visual.wrap}">
                <i class="${visual.icon} text-sm" aria-hidden="true"></i>
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                  <div class="text-sm font-bold text-slate-900">${escapeHtml(item.title || 'Notificación')}</div>
                  ${dateLabel ? `<div class="inline-flex items-center gap-1 rounded-full bg-white/75 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200">
                    <i class="fa-regular fa-clock text-[10px]" aria-hidden="true"></i>
                    ${escapeHtml(dateLabel)}
                  </div>` : ''}
                </div>
                <div class="mt-1 text-xs text-slate-600 leading-5">${escapeHtml(item.message || '')}</div>
              </div>
              ${item.read ? '<span class="shrink-0 text-[11px] font-semibold text-slate-400">Leída</span>' : ''}
            </div>
            ${item.read ? '' : '<span class="absolute right-3 top-4 inline-flex w-2 h-2 rounded-full bg-rose-500"></span>'}
          </a>`;
        }).join('');

        notificationsList.querySelectorAll('[data-notification-id]').forEach((anchor) => {
          anchor.addEventListener('click', () => {
            const id = anchor.getAttribute('data-notification-id');
            if (!id) return;
            markNotificationRead(id);
          });
        });
      }

      async function loadNotifications() {
        try {
          const response = await fetch('/api/header/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json().catch(() => ({}));
          notifications = Array.isArray(json.items) ? json.items : [];
          renderNotificationCounter();
          renderNotifications();
          renderModuleCounters();
          notifyNewBrowserNotifications(notifications);
        } catch (error) {
          notifications = [];
          renderNotificationCounter();
          renderNotifications();
          renderModuleCounters();
        }
      }

      async function markNotificationRead(id) {
        notifications = notifications.map((item) => item.id === id ? ({ ...item, read: true }) : item);
        renderNotificationCounter();
        renderNotifications();
        renderModuleCounters();
        try {
          const response = await fetch('/api/header/notifications/read-one', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ notification_id: id }),
          });
          if (response.ok) loadNotifications();
        } catch (e) {}
      }

      async function markAllRead() {
        notifications = notifications.map((item) => ({ ...item, read: true }));
        renderNotificationCounter();
        renderNotifications();
        renderModuleCounters();
        try {
          const response = await fetch('/api/header/notifications/read-all', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ all: true }),
          });
          if (response.ok) loadNotifications();
        } catch (e) {}
      }

      function openNotificationsPanel() {
        closeRemindersPanel();
        notificationsBackdrop?.classList.remove('hidden');
        notificationsPanel.classList.remove('translate-x-full');
        profileMenu.classList.add('hidden');
        document.body.classList.remove('header-profile-menu-open');
      }

      function closeNotificationsPanel() {
        notificationsPanel.classList.add('translate-x-full');
        notificationsBackdrop?.classList.add('hidden');
      }

      function toggleNotificationsPanel() {
        const isOpen = !notificationsPanel.classList.contains('translate-x-full');
        if (isOpen) closeNotificationsPanel();
        else openNotificationsPanel();
      }

      function openRemindersPanel() {
        closeNotificationsPanel();
        const categoryMoved = applyPendingReminderCategoryPromotion();
        if (categoryMoved) {
          persistRemindersOnly();
          queueSaveRemindersRemote();
        }
        remindersBackdrop?.classList.remove('hidden');
        remindersPanel?.classList.remove('translate-x-[calc(100%+2rem)]');
        profileMenu.classList.add('hidden');
        document.body.classList.remove('header-profile-menu-open');
        renderReminders();
      }

      function closeRemindersPanel() {
        const categoryMoved = applyPendingReminderCategoryPromotion();
        if (categoryMoved) {
          persistRemindersOnly();
          queueSaveRemindersRemote();
        }
        remindersPanel?.classList.add('translate-x-[calc(100%+2rem)]');
        remindersBackdrop?.classList.add('hidden');
        hideReminderLinkDropdown();
        hideReminderPriorityDropdown();
        hideReminderAllViewDropdown();
        hideReminderRowPriorityDropdowns();
        closeReminderDatePickers();
        clearReminderPendingLink();
        if (reminderNewText) reminderNewText.value = '';
        closeReminderComposerIfEmpty();
      }

      function toggleRemindersPanel() {
        const isOpen = remindersPanel && !remindersPanel.classList.contains('translate-x-[calc(100%+2rem)]');
        if (isOpen) closeRemindersPanel();
        else openRemindersPanel();
      }

      function toggleProfileMenu() {
        closeNotificationsPanel();
        closeRemindersPanel();
        const shouldOpen = profileMenu.classList.contains('hidden');
        profileMenu.classList.toggle('hidden', !shouldOpen);
        document.body.classList.toggle('header-profile-menu-open', shouldOpen);
      }

      notificationsBtn.addEventListener('click', toggleNotificationsPanel);
      notificationsCloseBtn?.addEventListener('click', closeNotificationsPanel);
      notificationsBackdrop?.addEventListener('click', closeNotificationsPanel);
      markAllBtn?.addEventListener('click', markAllRead);

      remindersBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleRemindersPanel();
      });
      remindersCloseBtn?.addEventListener('click', closeRemindersPanel);
      remindersBackdrop?.addEventListener('click', closeRemindersPanel);
      reminderCategoriesPrevBtn?.addEventListener('click', () => scrollReminderCategories(-1));
      reminderCategoriesNextBtn?.addEventListener('click', () => scrollReminderCategories(1));
      reminderCategories?.addEventListener('scroll', updateReminderCategoryScrollControls, { passive: true });
      reminderCategories?.addEventListener('wheel', (event) => {
        if (reminderCategories.scrollWidth <= reminderCategories.clientWidth + 2) return;
        if (Math.abs(event.deltaX) >= Math.abs(event.deltaY)) return;
        event.preventDefault();
        reminderCategories.scrollLeft += event.deltaY;
      }, { passive: false });
      window.addEventListener('resize', updateReminderCategoryScrollControls);
      reminderShowComposerBtn?.addEventListener('click', openReminderComposer);
      reminderComposer?.addEventListener('focusout', () => {
        setTimeout(() => {
          const active = document.activeElement;
          if (active && reminderComposer.contains(active)) return;
          settleReminderComposerOnExit();
        }, 80);
      });
      reminderAddCategoryBtn?.addEventListener('click', () => {
        const title = 'Nueva categoría';
        const category = { id: uid('cat'), title, collapsed: false };
        const section = { id: uid('section'), categoryId: category.id, title: '', collapsed: false };
        reminderCategoriesData.unshift(category);
        reminderSections.push(section);
        activeReminderCategoryId = category.id;
        activeReminderSectionId = section.id;
        saveReminders();
        setTimeout(() => {
          const input = reminderActiveCategoryTitle?.querySelector(`[data-reminder-active-category-title="${CSS.escape(category.id)}"]`);
          input?.focus();
        }, 0);
      });
      reminderAddBtn?.addEventListener('click', () => {
        addReminder(reminderNewText?.value || '', reminderNewPriority?.value || '', reminderNewDate?.value || '');
      });
      reminderPriorityBtn?.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        event.stopPropagation();
        markReminderPopoverBusy();
      });
      reminderPriorityBtn?.addEventListener('click', (event) => {
        event.stopPropagation();
        markReminderPopoverBusy();
        closeReminderDatePickers();
        reminderPriorityDropdown?.classList.toggle('hidden');
        hideReminderLinkDropdown();
        hideReminderRowPriorityDropdowns();
      });
      reminderPriorityDropdown?.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        event.stopPropagation();
        markReminderPopoverBusy();
      });
      reminderPriorityDropdown?.querySelectorAll('[data-reminder-priority-option]').forEach((btn) => {
        btn.addEventListener('pointerdown', (event) => {
          event.preventDefault();
          event.stopPropagation();
          markReminderPopoverBusy();
        });
        btn.addEventListener('click', (event) => {
          event.preventDefault();
          event.stopPropagation();
          markReminderPopoverBusy();
          const value = normalizeReminderPriority(btn.getAttribute('data-reminder-priority-option') || '');
          updateReminderPriorityPicker(value);
          hideReminderPriorityDropdown();
        });
      });
      initReminderDatePicker(reminderNewDate);
      reminderNewText?.addEventListener('input', () => {
        maybeShowReminderLinkDropdown(reminderNewText);
        scrollReminderComposerIntoView();
      });
      reminderNewText?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          addReminder(reminderNewText.value, reminderNewPriority?.value || '', reminderNewDate?.value || '');
        }
        if (event.key === 'Escape') {
          hideReminderLinkDropdown();
        }
      });
      reminderAddSectionBtn?.addEventListener('click', () => {
        const categoryId = isAllRemindersCategoryId(activeReminderCategoryId)
          ? (reminderCategoriesData[0]?.id || 'default-cat')
          : (activeReminderCategoryId || reminderCategoriesData[0]?.id || 'default-cat');
        reminderSections.push({
          id: uid('section'),
          categoryId,
          title: 'Nuevo subtítulo',
          collapsed: false,
        });
        activeReminderSectionId = reminderSections[reminderSections.length - 1].id;
        saveReminders();
        setTimeout(() => {
          const inputs = remindersList?.querySelectorAll('[data-reminder-section-title]');
          const last = inputs?.[inputs.length - 1];
          last?.focus();
          last?.select();
        }, 0);
      });

          tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          currentTab = btn.getAttribute('data-notif-tab') || 'unread';
          tabButtons.forEach((inner) => {
            const active = inner === btn;
            inner.classList.toggle('bg-[#f3fea4]', active);
            inner.classList.toggle('shadow-sm', active);
            inner.classList.toggle('text-slate-800', active);
            inner.classList.toggle('text-slate-500', !active);
          });
          renderNotifications();
          renderModuleCounters();
        });
      });

      moduleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          currentModule = btn.getAttribute('data-notif-module') || 'all';
          moduleButtons.forEach((inner) => {
            const active = inner === btn;
            inner.classList.toggle('bg-[#f3fea4]', active);
            inner.classList.toggle('text-slate-800', active);
            inner.classList.toggle('text-slate-500', !active);
          });
          renderNotifications();
          renderModuleCounters();
        });
      });

      browserNotificationsBtn?.addEventListener('click', requestBrowserNotificationsPermission);

      profileBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        toggleProfileMenu();
      });

      profileMenu.addEventListener('click', (event) => {
        event.stopPropagation();
      });

      remindersPanel?.addEventListener('click', (event) => {
        const target = event.target;
        const insidePriority = target?.closest?.('#reminderPriorityBtn, #reminderPriorityDropdown, [data-reminder-priority-edit], [data-reminder-priority-menu]');
        const insideAllView = target?.closest?.('#reminderAllViewBtn, #reminderAllViewDropdown');
        const insideLink = target?.closest?.('#reminderLinkDropdown, [data-reminder-text], #reminderNewText');
        const insideDate = target?.closest?.('[data-reminder-date], #reminderNewDate, .flatpickr-input, .reminder-date-trigger, .flatpickr-calendar');
        const insideComposer = reminderComposer?.contains(target);
        const insideAddZone = target?.closest?.('[data-reminder-section-add]');
        const insideEditableTitle = target?.closest?.('[data-reminder-active-category-title], [data-reminder-section-title]');
        const insideComposerControl = target?.closest?.('#reminderNewText, #reminderAddBtn, #reminderPriorityBtn, #reminderPriorityDropdown, #reminderNewDate, .reminder-date-trigger, .flatpickr-calendar, #reminderLinkDropdown');
        if (!insidePriority) {
          hideReminderPriorityDropdown();
          hideReminderRowPriorityDropdowns();
        }
        if (!insideAllView) {
          hideReminderAllViewDropdown();
        }
        if (!insideLink) {
          hideReminderLinkDropdown();
        }
        if (!insideDate) {
          closeReminderDatePickers();
        }
        if ((!insideComposer || !insideComposerControl) && !insideAddZone && !insideEditableTitle && !insidePriority && !insideDate && !insideLink) {
          if (document.activeElement instanceof HTMLElement && remindersPanel.contains(document.activeElement)) {
            document.activeElement.blur();
          }
          setTimeout(() => settleReminderComposerOnExit({ force: true }), 0);
        }
        event.stopPropagation();
      });

      reminderLinkDropdown?.addEventListener('click', (event) => {
        event.stopPropagation();
      });

      reminderPriorityDropdown?.addEventListener('click', (event) => {
        event.stopPropagation();
      });

      document.addEventListener('click', (event) => {
        const target = event?.target instanceof Element ? event.target : null;
        if (target?.closest?.('.flatpickr-calendar, .reminder-date-trigger, #reminderPriorityDropdown, [data-reminder-priority-menu]')) {
          markReminderPopoverBusy();
          return;
        }
        profileMenu.classList.add('hidden');
        document.body.classList.remove('header-profile-menu-open');
        hideReminderLinkDropdown();
        hideReminderPriorityDropdown();
        hideReminderAllViewDropdown();
        hideReminderRowPriorityDropdowns();
        closeReminderDatePickers();
        if (document.activeElement instanceof HTMLElement && reminderComposer?.contains(document.activeElement)) {
          document.activeElement.blur();
        }
        setTimeout(() => settleReminderComposerOnExit({ force: true }), 0);
      });

      logoutBtn?.addEventListener('click', () => logoutForm?.submit());

      presenceButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          applyPresence(btn.getAttribute('data-presence-status') || 'available');
        });
      });

      document.addEventListener('keydown', (event) => {
        if (event.metaKey || event.ctrlKey || event.altKey) return;
        const key = String(event.key || '').toLowerCase();

        if (key === 'escape') {
          const closeTopModal = () => {
            const dialogs = Array.from(document.querySelectorAll('[role="dialog"][aria-modal="true"]')).filter((el) => !el.classList.contains('hidden'));
            const topDialog = dialogs[dialogs.length - 1];
            if (!topDialog) return false;

            const closeBtn = topDialog.querySelector('[data-modal-close], button[aria-label="Cerrar"], button[title="Cerrar"], button[id*="close" i], button[onclick*="close" i]');
            if (closeBtn) {
              closeBtn.click();
            } else {
              topDialog.classList.add('hidden');
            }
            return true;
          };

          if (closeTopModal()) {
            event.preventDefault();
            return;
          }

          closeNotificationsPanel();
          closeRemindersPanel();
          profileMenu.classList.add('hidden');
          document.body.classList.remove('header-profile-menu-open');
          return;
        }

        const target = event.target;
        const isTyping = target && (
          target.tagName === 'INPUT'
          || target.tagName === 'TEXTAREA'
          || target.tagName === 'SELECT'
          || target.isContentEditable
        );
        if (isTyping) return;

        if (key === 'n') {
          event.preventDefault();
          toggleNotificationsPanel();
        }
        if (key === 'p') {
          event.preventDefault();
          toggleProfileMenu();
        }
        if (key === 'r') {
          event.preventDefault();
          toggleRemindersPanel();
        }
        if (key === 't') {
          const timerBtn = document.getElementById('globalHeaderTimerToggleBtn');
          if (timerBtn) {
            event.preventDefault();
            timerBtn.click();
          }
        }
        if (key === '/') {
          const searchInput = document.querySelector('input[type="search"], input[name="search"], #globalSearchInput');
          if (searchInput) {
            event.preventDefault();
            searchInput.focus();
          }
        }
      });

      document.addEventListener('pointerdown', (event) => {
        const dialogs = Array.from(document.querySelectorAll('[role="dialog"][aria-modal="true"]')).filter((el) => !el.classList.contains('hidden'));
        const topDialog = dialogs[dialogs.length - 1];
        if (!topDialog) return;

        const targetEl = event.target instanceof Element ? event.target : null;
        if (!targetEl) return;
        if (targetEl.closest('.flatpickr-calendar')) return;

        const content = topDialog.querySelector('[role="document"], .relative.transform.overflow-hidden, .rounded-2xl.bg-white, .rounded-3xl.bg-white');
        if (content && content.contains(targetEl)) return;

        const closeBtn = topDialog.querySelector('[data-modal-close], button[aria-label="Cerrar"], button[title="Cerrar"], button[id*="close" i], button[onclick*="close" i]');
        if (closeBtn) {
          closeBtn.click();
        } else {
          topDialog.classList.add('hidden');
        }
      });

      try {
        applyPresence(localStorage.getItem('headerPresenceStatus') || 'available');
      } catch (e) {
        applyPresence('available');
      }
      loadReminders();
      updateReminderPriorityPicker('');
      renderReminders();
      loadRemindersFromServer();
      loadBrowserNotificationsSeen();
      updateBrowserNotificationsButton();
      loadNotifications();
      window.setInterval(loadNotifications, 60000);
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
          updateBrowserNotificationsButton();
          loadNotifications();
        }
      });
    })();

    (function () {
      const headerTimerPickerState = {
        projects: [],
        selectedProjectId: '',
        selectedTaskId: '',
        step: 1,
      };

      window.updateHeaderTimerButtonVisibility = (visible) => {
        const btn = document.getElementById('headerStartTimerBtn');
        if (!btn) return;
        btn.classList.toggle('hidden', visible === false);
      };

      function setHeaderTimerWizardStep(step) {
        headerTimerPickerState.step = step === 2 ? 2 : 1;
        const stepLabel = document.getElementById('headerTimerWizardStepLabel');
        const stepCount = document.getElementById('headerTimerWizardStepCount');
        const progress = document.getElementById('headerTimerWizardProgress');
        const paneOne = document.getElementById('headerTimerWizardPaneProject');
        const paneTwo = document.getElementById('headerTimerWizardPaneTask');
        const btnNext = document.getElementById('headerTimerWizardNextBtn');
        const btnBack = document.getElementById('headerTimerWizardBackBtn');
        const btnStart = document.getElementById('headerTimerStartConfirmBtn');

        if (stepLabel) stepLabel.textContent = headerTimerPickerState.step === 1 ? 'Paso 1: Seleccionar proyecto' : 'Paso 2: Seleccionar tarea';
        if (stepCount) stepCount.textContent = headerTimerPickerState.step === 1 ? '1/2' : '2/2';
        if (progress) progress.style.width = headerTimerPickerState.step === 1 ? '50%' : '100%';
        if (paneOne) paneOne.classList.toggle('hidden', headerTimerPickerState.step !== 1);
        if (paneTwo) paneTwo.classList.toggle('hidden', headerTimerPickerState.step !== 2);
        if (btnNext) btnNext.classList.toggle('hidden', headerTimerPickerState.step !== 1);
        if (btnBack) btnBack.classList.toggle('hidden', headerTimerPickerState.step !== 2);
        if (btnStart) btnStart.classList.toggle('hidden', headerTimerPickerState.step !== 2);
      }

      function renderHeaderTimerProjectList(query = '') {
        const list = document.getElementById('headerTimerProjectList');
        const nextBtn = document.getElementById('headerTimerWizardNextBtn');
        if (!list) return;
        const q = String(query || '').trim().toLowerCase();
        const filtered = headerTimerPickerState.projects.filter((project) => {
          const title = String(project?.titulo || '').toLowerCase();
          const client = String(project?.cliente || '').toLowerCase();
          return !q || title.includes(q) || client.includes(q);
        });

        if (!filtered.length) {
          list.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-4 text-center text-xs font-semibold text-slate-500">No hay proyectos que coincidan con la búsqueda.</div>';
          if (nextBtn) nextBtn.disabled = true;
          return;
        }

        list.innerHTML = filtered.map((project) => {
          const id = String(project?.id || '');
          const title = String(project?.titulo || 'Proyecto sin nombre').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
          const client = String(project?.cliente || 'Sin cliente').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
          const tasksTotal = Array.isArray(project?.tareas) ? project.tareas.length : 0;
          const selected = String(headerTimerPickerState.selectedProjectId) === id;
          return `<button type="button" data-header-timer-project-id="${id}" class="w-full rounded-xl border px-3 py-2.5 text-left transition-colors ${selected ? 'border-lime-300 bg-lime-50' : 'border-slate-200 bg-white hover:bg-slate-100'}">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-bold text-slate-800">${title}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">${client}</div>
              </div>
              <span class="text-[10px] font-bold text-slate-500 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 whitespace-nowrap">${tasksTotal} tareas</span>
            </div>
          </button>`;
        }).join('');

        document.querySelectorAll('[data-header-timer-project-id]').forEach((node) => {
          node.addEventListener('click', () => {
            headerTimerPickerState.selectedProjectId = String(node.getAttribute('data-header-timer-project-id') || '');
            renderHeaderTimerProjectList(document.getElementById('headerTimerProjectSearch')?.value || '');
          });
        });

        if (nextBtn) nextBtn.disabled = !headerTimerPickerState.selectedProjectId;
      }

      function renderHeaderTimerTaskOptions() {
        const list = document.getElementById('headerTimerTaskList');
        const hint = document.getElementById('headerTimerTaskHint');
        const current = headerTimerPickerState.projects.find((p) => String(p.id) === String(headerTimerPickerState.selectedProjectId));
        const tasks = Array.isArray(current?.tareas) ? current.tareas.filter((t) => !t?.done) : [];
        if (!list) return;

        const base = `<button type="button" data-header-timer-task-id="" class="w-full rounded-xl border px-3 py-2.5 text-left transition-colors ${headerTimerPickerState.selectedTaskId === '' ? 'border-lime-300 bg-lime-50' : 'border-slate-200 bg-white hover:bg-slate-100'}"><div class="text-sm font-semibold text-slate-700">Sin tarea específica</div></button>`;
        const items = tasks.map((task) => {
          const id = String(task?.id || '');
          const text = String(task?.texto || 'Tarea sin nombre').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
          const selected = headerTimerPickerState.selectedTaskId === id;
          return `<button type="button" data-header-timer-task-id="${id}" class="w-full rounded-xl border px-3 py-2.5 text-left transition-colors ${selected ? 'border-lime-300 bg-lime-50' : 'border-slate-200 bg-white hover:bg-slate-100'}"><div class="text-sm font-semibold text-slate-700 truncate">${text}</div></button>`;
        });
        list.innerHTML = [base].concat(items).join('');
        if (hint) hint.textContent = tasks.length ? 'Selecciona una tarea para iniciar el conteo.' : 'Este proyecto no tiene tareas activas; se iniciará temporizador general.';

        document.querySelectorAll('[data-header-timer-task-id]').forEach((node) => {
          node.addEventListener('click', () => {
            headerTimerPickerState.selectedTaskId = String(node.getAttribute('data-header-timer-task-id') || '');
            renderHeaderTimerTaskOptions();
          });
        });
      }

      function ensureHeaderTimerPickerModal() {
        if (document.getElementById('headerTimerPickerModal')) return;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `<div id="headerTimerPickerModal" class="fixed inset-0 hidden" style="z-index:2147483550" role="dialog" aria-modal="true" aria-labelledby="headerTimerPickerTitle">
          <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" data-header-timer-close></div>
          <div class="absolute inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
              <div role="document" class="my-4 w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
              <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0">
                  <h3 id="headerTimerPickerTitle" class="text-lg font-extrabold text-slate-900">¿En qué proyecto iniciarás el temporizador?</h3>
                  <p class="text-xs text-slate-500 mt-1">Primero elige proyecto y luego la tarea para comenzar a contar tiempo.</p>
                  <div class="mt-3">
                    <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-slate-400">
                      <span id="headerTimerWizardStepLabel">Paso 1: Seleccionar proyecto</span>
                      <span id="headerTimerWizardStepCount">1/2</span>
                    </div>
                    <div class="mt-1.5 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                      <div id="headerTimerWizardProgress" class="h-full rounded-full bg-lime-400 transition-all duration-200" style="width:50%"></div>
                    </div>
                  </div>
                </div>
                <button type="button" data-header-timer-close class="h-8 w-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100" aria-label="Cerrar">✕</button>
              </div>
              <div class="px-5 py-4">
                <div id="headerTimerWizardPaneProject" class="space-y-3">
                  <div class="relative">
                    <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-4.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input id="headerTimerProjectSearch" type="text" placeholder="Buscar proyecto..." class="w-full h-11 rounded-xl border-slate-200 bg-slate-50 pl-10 text-sm font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                  </div>
                  <div id="headerTimerProjectList" class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
                </div>
                <div id="headerTimerWizardPaneTask" class="hidden space-y-3">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Tarea</label>
                  <div id="headerTimerTaskList" class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
                  <p id="headerTimerTaskHint" class="text-[11px] text-slate-500"></p>
                </div>
              </div>
              <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-5 py-4">
                <button type="button" data-header-timer-close class="h-10 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 hover:bg-slate-100">Cancelar</button>
                <div class="flex items-center gap-2">
                  <button type="button" id="headerTimerWizardBackBtn" class="hidden h-10 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600 hover:bg-slate-100">Atrás</button>
                  <button type="button" id="headerTimerWizardNextBtn" class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-800 hover:bg-slate-100">Siguiente</button>
                  <button type="button" id="headerTimerStartConfirmBtn" class="hidden h-10 rounded-xl border border-lime-200 bg-lime-100 px-4 text-sm font-extrabold text-slate-900 hover:bg-lime-200">Iniciar temporizador</button>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>`;
        document.body.appendChild(wrapper.firstElementChild);

        document.querySelectorAll('[data-header-timer-close]').forEach((node) => {
          node.addEventListener('click', () => {
            const modal = document.getElementById('headerTimerPickerModal');
            if (modal) modal.classList.add('hidden');
            document.body.classList.remove('header-timer-picker-open');
          });
        });

        document.getElementById('headerTimerProjectSearch')?.addEventListener('input', (event) => {
          renderHeaderTimerProjectList(String(event.target?.value || ''));
        });

        document.getElementById('headerTimerWizardNextBtn')?.addEventListener('click', () => {
          if (!headerTimerPickerState.selectedProjectId) return;
          headerTimerPickerState.selectedTaskId = '';
          renderHeaderTimerTaskOptions();
          setHeaderTimerWizardStep(2);
        });

        document.getElementById('headerTimerWizardBackBtn')?.addEventListener('click', () => {
          setHeaderTimerWizardStep(1);
        });

        document.getElementById('headerTimerStartConfirmBtn')?.addEventListener('click', startHeaderTimerFromPicker);
      }

      async function openHeaderTimerPickerModal() {
        ensureHeaderTimerPickerModal();
        const modal = document.getElementById('headerTimerPickerModal');
        if (!modal) return;

        try {
          const response = await fetch('/api/proyectos', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json().catch(() => ({}));
          const rows = Array.isArray(json?.data) ? json.data : [];
          const projects = rows.filter((item) => !(item?.archived));
          if (!projects.length) {
            if (window.showNotification) window.showNotification('Primero crea un proyecto para iniciar el temporizador.', 'error');
            return;
          }

          headerTimerPickerState.projects = projects;
          headerTimerPickerState.selectedProjectId = String(projects[0]?.id || '');
          headerTimerPickerState.selectedTaskId = '';
          setHeaderTimerWizardStep(1);
          const search = document.getElementById('headerTimerProjectSearch');
          if (search) search.value = '';
          renderHeaderTimerProjectList('');
          document.body.classList.add('header-timer-picker-open');
          modal.classList.remove('hidden');
        } catch (_) {
          if (window.showNotification) window.showNotification('No se pudo cargar proyectos para el temporizador.', 'error');
        }
      }

      async function startHeaderTimerFromPicker() {
        const projectId = String(headerTimerPickerState.selectedProjectId || '');
        if (!projectId) return;

        try {
          const response = await fetch('/api/proyectos/timer', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              id: projectId,
              action: 'start',
              tarea_id: headerTimerPickerState.selectedTaskId || null,
            }),
          });
          const data = await response.json().catch(() => ({}));
          if (!response.ok || data?.ok === false) {
            throw new Error(data?.message || 'timer_start_failed');
          }

          const modal = document.getElementById('headerTimerPickerModal');
          if (modal) modal.classList.add('hidden');
          document.body.classList.remove('header-timer-picker-open');
          await syncGlobalTimerFromServer();
          if (window.showNotification) window.showNotification('Temporizador iniciado', 'success');
        } catch (error) {
          if (window.showNotification) window.showNotification(error?.message || 'No se pudo iniciar el temporizador.', 'error');
        }
      }

      window.handleHeaderTimerButtonClick = () => {
        const canProyectos = @json($headerTimerPerms['proyectos'] ?? false);
        const canLeads = @json($headerTimerPerms['leads'] ?? false);

        // Si hay card de lead activo visible, no reabrir selector
        const leadHost = document.getElementById('headerLeadTimerHost');
        if (leadHost && !leadHost.classList.contains('hidden')) return;

        if (!canProyectos && !canLeads) {
          if (window.showNotification) window.showNotification('No tienes permisos para usar el temporizador.', 'error');
          return;
        }

        if (canProyectos && canLeads) {
          // Show choice modal
          if (typeof window.ensureTimerChoiceModal === 'function') {
            window.ensureTimerChoiceModal();
            return;
          }
        }

        if (canLeads && !canProyectos) {
          // Direct to lead timer picker
          if (typeof window.openLeadTimerModal === 'function') {
            window.openLeadTimerModal();
          } else if (window.showNotification) {
            window.showNotification('Abre la página de Leads para usar el timer.', 'info');
          }
          return;
        }

        // canProyectos && !canLeads → direct to project picker
        if (typeof window.openQuickProjectActionModal === 'function') {
          window.openQuickProjectActionModal('start-timer');
        } else {
          openHeaderTimerPickerModal();
        }
      };

      // Timer choice modal (Proyecto / Lead)
      window.ensureTimerChoiceModal = function () {
        let modal = document.getElementById('timerChoiceModal');
        if (!modal) {
          const wrapper = document.createElement('div');
          wrapper.innerHTML = `<div id="timerChoiceModal" class="fixed inset-0 hidden" style="z-index:2147483500">
            <div class="absolute inset-0 bg-slate-950/55" id="timerChoiceBackdrop"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
              <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-100">
                  <h3 class="text-lg font-extrabold text-slate-900">¿En qué trabajas hoy?</h3>
                  <p class="text-xs text-slate-500 mt-1">Elige el tipo de trabajo para iniciar el temporizador.</p>
                </div>
                <div class="px-5 py-5 grid grid-cols-2 gap-3">
                  <button type="button" id="timerChoiceProyecto" class="flex flex-col items-center gap-2 rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-5 hover:border-slate-900 hover:bg-slate-100 transition-all">
                    <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>
                    <span class="text-sm font-extrabold text-slate-900">Proyecto</span>
                    <span class="text-[11px] text-slate-500 text-center">Tarea de un proyecto</span>
                  </button>
                  <button type="button" id="timerChoiceLead" class="flex flex-col items-center gap-2 rounded-2xl border-2 border-lime-200 bg-lime-50 px-4 py-5 hover:border-lime-400 hover:bg-lime-100 transition-all">
                    <svg class="w-8 h-8 text-lime-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
                    <span class="text-sm font-extrabold text-slate-900">Lead</span>
                    <span class="text-[11px] text-slate-500 text-center">Tiempo comercial</span>
                  </button>
                </div>
                <div class="px-5 pb-4">
                  <button type="button" id="timerChoiceCancel" class="w-full h-10 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
                </div>
              </div>
            </div>
          </div>`;
          document.body.appendChild(wrapper.firstElementChild);
          modal = document.getElementById('timerChoiceModal');
          const closeTimerChoiceModal = () => {
            modal.classList.add('hidden');
            document.body.classList.remove('timer-choice-modal-open');
          };
          document.getElementById('timerChoiceBackdrop')?.addEventListener('click', closeTimerChoiceModal);
          document.getElementById('timerChoiceCancel')?.addEventListener('click', closeTimerChoiceModal);
          document.getElementById('timerChoiceProyecto')?.addEventListener('click', () => {
            closeTimerChoiceModal();
            if (typeof window.openQuickProjectActionModal === 'function') window.openQuickProjectActionModal('start-timer');
            else openHeaderTimerPickerModal();
          });
          document.getElementById('timerChoiceLead')?.addEventListener('click', () => {
            closeTimerChoiceModal();
            if (typeof window.openLeadTimerModal === 'function') window.openLeadTimerModal();
            else if (window.showNotification) window.showNotification('Abre la página de Leads para usar el timer de leads.', 'info');
          });
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
              closeTimerChoiceModal();
            }
          });
        }
        document.body.classList.add('timer-choice-modal-open');
        modal.classList.remove('hidden');
      };

      const isProjectsPage = @json(request()->routeIs('proyectos.index'));
      if (isProjectsPage) return;

      const host = document.getElementById('headerTaskTimerHost');
      if (!host) return;

      const GLOBAL_TIMER_STATE_KEY = 'infocus_global_timer_state_v2';
      const LEGACY_GLOBAL_TIMER_STATE_KEYS = ['infocus_global_timer_state_v1'];
      const POMODORO_STATE_KEY = 'tdah_pomodoro_state_v2';
      const TIMER_HISTORY_PREFIX = 'project_timer_history_v2_';
      const currentUserName = @json($headerUserName);
      let globalTimerState = null;
      let globalTimerInterval = null;
      let globalPipRenderInterval = null;
      let globalPipWatchdogInterval = null;
      let globalPipStream = null;
      let globalPipStreamReady = false;
      let globalPipVideoTrack = null;
      let globalPipLastFrameAt = 0;
      let globalPipRecovering = false;
      let globalPipAudioContext = null;
      let globalPipAudioSource = null;
      let globalPipAudioTrack = null;
      let globalPipAudioPrimed = false;
      let suppressGlobalPipPlaybackSync = false;
      let globalTimerProjectSnapshot = null;
      let globalTimerFsShowAllSubtasks = false;
      let globalTimerFsShowAllNotes = false;

      function formatTimer(totalSeconds) {
        const sec = Math.max(0, Number(totalSeconds) || 0);
        const h = Math.floor(sec / 3600).toString().padStart(2, '0');
        const m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
        const s = (sec % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
      }

      function getStoredState() {
        try {
          LEGACY_GLOBAL_TIMER_STATE_KEYS.forEach((key) => localStorage.removeItem(key));
          const raw = localStorage.getItem(GLOBAL_TIMER_STATE_KEY);
          const parsed = raw ? JSON.parse(raw) : null;
          return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (_) {
          return null;
        }
      }

      function setStoredState(state) {
        globalTimerState = state && typeof state === 'object' ? state : null;
        try {
          if (globalTimerState) {
            localStorage.setItem(GLOBAL_TIMER_STATE_KEY, JSON.stringify(globalTimerState));
          } else {
            localStorage.removeItem(GLOBAL_TIMER_STATE_KEY);
          }
        } catch (_) {}
      }

      function clearGlobalTimerState() {
        const fallback = document.getElementById('globalTimerMiniPip');
        const video = document.getElementById('globalTimerPipVideo');
        closeGlobalTimerFullscreen();
        fallback?.classList.add('hidden');
        if (video && document.pictureInPictureElement === video && document.exitPictureInPicture) {
          document.exitPictureInPicture().catch(() => {});
        }
        if (video && video.webkitPresentationMode === 'picture-in-picture') {
          try { video.webkitSetPresentationMode('inline'); } catch (_) {}
        }
        stopGlobalPipRenderLoop();
        resetGlobalPipSource();
        globalTimerProjectSnapshot = null;
        setStoredState(null);
        renderGlobalTimer();
      }

      function getDisplayedSeconds() {
        if (!globalTimerState) return 0;
        const base = Math.max(0, Number(globalTimerState.currentSeconds || 0));
        if (!globalTimerState.isRunning) return base;
        const elapsed = Math.max(0, Math.floor((Date.now() - Number(globalTimerState.syncedAt || Date.now())) / 1000));
        return base + elapsed;
      }

      function getPomodoroHeaderState() {
        try {
          const parsed = JSON.parse(localStorage.getItem(POMODORO_STATE_KEY) || 'null');
          if (!parsed || typeof parsed !== 'object') return null;
          if (!parsed.isRunning && !parsed.activeTaskId && !parsed.activeProjectId && !(Number(parsed.loggedWorkLogs || 0) > 0)) return null;
          return parsed;
        } catch (_) {
          return null;
        }
      }

      function getPomodoroDisplayedSeconds(state) {
        if (!state) return 0;
        if (!state.isRunning || !state.endsAt) return Math.max(0, Number(state.remainingSeconds || 0));
        return Math.max(0, Math.ceil((Number(state.endsAt) - Date.now()) / 1000));
      }

      function formatPomodoroTimer(totalSeconds) {
        const safeSeconds = Math.max(0, Number(totalSeconds || 0));
        const minutes = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
        const seconds = (safeSeconds % 60).toString().padStart(2, '0');
        return `${minutes}:${seconds}`;
      }

      function renderGlobalPomodoroHeader(state) {
        if (!state) return;

        host.classList.remove('hidden');
        window.updateHeaderTimerButtonVisibility?.(false);

        if (!host.querySelector('#globalHeaderPomodoroCard')) {
          host.innerHTML = `<div id="globalHeaderPomodoroCard" role="button" tabindex="0" class="group relative cursor-pointer rounded-2xl border border-[#c8e17e] bg-[#dff8a7] px-2 py-1.5 shadow-[0_10px_22px_rgba(140,166,71,0.28)] min-w-0 text-left transition-all duration-150 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(140,166,71,0.32)] focus:outline-none focus:ring-2 focus:ring-[#111729]/20">
            <div class="absolute top-1.5 right-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0">
              <button id="globalHeaderPomodoroPipBtn" type="button" class="w-6 h-6 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 flex items-center justify-center" title="Modo PiP" aria-label="Modo PiP">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
              </button>
            </div>
            <div class="flex items-center gap-2 min-w-0 text-[#111729]">
              <button id="globalHeaderPomodoroToggleBtn" type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#111729] text-[#dff8a7]" title="Pausar Pomodoro"></button>
              <div class="min-w-0 flex-1">
                <div class="text-[10px] font-extrabold uppercase tracking-[0.28em] text-[#111729]/70">Pomodoro TDAH</div>
                <div id="globalHeaderPomodoroTask" class="truncate text-left text-xs lg:text-sm font-extrabold text-[#111729]">Pomodoro activo</div>
                <div id="globalHeaderPomodoroMeta" class="truncate text-[10px] lg:text-[11px] font-semibold text-[#111729]/65">Trabajo</div>
              </div>
              <div class="shrink-0 text-right min-w-[86px] lg:min-w-[98px]">
                <div id="globalHeaderPomodoroValue" class="text-2xl lg:text-[30px] font-mono font-extrabold tracking-tight text-[#111729] leading-none">25:00</div>
                <div class="mt-1 flex items-center justify-end gap-1.5">
                  <button id="globalHeaderPomodoroSaveBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-[#111729]/70 hover:text-[#111729]">Guardar</button>
                  <button id="globalHeaderPomodoroDeleteBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-rose-700/85 hover:text-rose-800">Eliminar</button>
                </div>
              </div>
            </div>
          </div>`;

          host.querySelector('#globalHeaderPomodoroCard')?.addEventListener('click', () => {
            window.openTdahPomodoroFullscreen?.();
          });
          host.querySelector('#globalHeaderPomodoroCard')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              window.openTdahPomodoroFullscreen?.();
            }
          });
          host.querySelector('#globalHeaderPomodoroToggleBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            window.toggleTdahPomodoroFromHeader?.();
          });
          host.querySelector('#globalHeaderPomodoroSaveBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            window.saveTdahPomodoroSession?.();
          });
          host.querySelector('#globalHeaderPomodoroDeleteBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            window.deleteTdahPomodoroSession?.();
          });
          host.querySelector('#globalHeaderPomodoroPipBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            window.openTdahPomodoroPip?.();
          });
        }

        const taskNode = host.querySelector('#globalHeaderPomodoroTask');
        const metaNode = host.querySelector('#globalHeaderPomodoroMeta');
        const valueNode = host.querySelector('#globalHeaderPomodoroValue');
        const toggleNode = host.querySelector('#globalHeaderPomodoroToggleBtn');
        const taskName = String(state.activeTaskName || 'Pomodoro activo');
        const meta = state.phase === 'break'
          ? `Descanso · ${state.breakMinutes || 15}m`
          : `${state.activeProjectTitle || 'En foco'} · ${state.workMinutes || 25}m`;

        if (taskNode) taskNode.textContent = taskName;
        if (metaNode) metaNode.textContent = meta;
        if (valueNode) valueNode.textContent = formatPomodoroTimer(getPomodoroDisplayedSeconds(state));
        if (toggleNode) {
          toggleNode.title = state.isRunning ? 'Pausar Pomodoro' : 'Continuar Pomodoro';
          toggleNode.innerHTML = state.isRunning
            ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
            : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';
        }
      }

      function updateGlobalTimerDom() {
        const pomodoroHeaderState = getPomodoroHeaderState();
        if (pomodoroHeaderState) {
          renderGlobalPomodoroHeader(pomodoroHeaderState);
          return;
        }

        if (!globalTimerState?.projectId) {
          host.classList.add('hidden');
          host.innerHTML = '';
          window.updateHeaderTimerButtonVisibility?.(true);
          return;
        }

        host.classList.remove('hidden');
        window.updateHeaderTimerButtonVisibility?.(false);

        if (!host.querySelector('#globalHeaderTimerCard')) {
          host.innerHTML = `<div id="globalHeaderTimerCard" role="button" tabindex="0" class="group relative w-full max-w-full cursor-pointer overflow-hidden rounded-2xl border border-[#2b3658] bg-[#101729] px-2 py-1.5 shadow-[0_10px_22px_rgba(16,23,41,0.32)] min-w-0 focus:outline-none focus:ring-2 focus:ring-lime-300/40">
            <div class="absolute top-1.5 right-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0">
              <button id="globalHeaderTimerPipBtn" type="button" class="w-6 h-6 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 flex items-center justify-center" title="Modo PiP" aria-label="Modo PiP">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
              </button>
            </div>
            <div class="grid min-w-0 grid-cols-[2rem_minmax(0,1fr)_8.75rem] items-center gap-2">
              <button id="globalHeaderTimerToggleBtn" type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#f0fe97] text-[#101729]" title="${globalTimerState.isRunning ? 'Pausar temporizador' : 'Continuar temporizador'}">${globalTimerState.isRunning ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>' : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>'}</button>
              <div class="min-w-0 overflow-hidden">
                <div class="truncate text-[10px] font-extrabold uppercase tracking-[0.28em] text-[#f0fe97]/70">En foco</div>
                <button id="globalHeaderTimerTask" type="button" class="block w-full max-w-full truncate text-left text-xs lg:text-sm font-extrabold text-[#f0fe97] hover:underline">${globalTimerState.taskName || 'Temporizador activo'}</button>
                <div id="globalHeaderTimerProject" class="w-full max-w-full truncate text-[10px] lg:text-[11px] font-semibold text-[#f0fe97]/60">${globalTimerState.projectTitle || 'Proyecto'}</div>
              </div>
              <div class="min-w-0 text-right">
                <div id="globalHeaderTimerValue" class="text-2xl lg:text-[30px] font-mono font-extrabold tracking-tight text-[#f0fe97] leading-none">${formatTimer(getDisplayedSeconds())}</div>
                <div class="mt-1 flex items-center justify-end gap-1.5">
                  <button id="globalHeaderTimerSaveBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-[#f0fe97]/75 hover:text-[#f0fe97]">Guardar</button>
                  <button id="globalHeaderTimerDeleteBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-rose-300/90 hover:text-rose-200">Eliminar</button>
                </div>
              </div>
            </div>
          </div>`;

          host.querySelector('#globalHeaderTimerCard')?.addEventListener('click', openGlobalTimerFullscreen);
          host.querySelector('#globalHeaderTimerCard')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              openGlobalTimerFullscreen();
            }
          });
          host.querySelector('#globalHeaderTimerToggleBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleGlobalTimer();
          });
          host.querySelector('#globalHeaderTimerTask')?.addEventListener('click', (event) => {
            event.stopPropagation();
            openGlobalTimerFullscreen();
          });
          host.querySelector('#globalHeaderTimerSaveBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            saveGlobalTimerLog();
          });
          host.querySelector('#globalHeaderTimerDeleteBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            deleteGlobalTimerLog();
          });
          host.querySelector('#globalHeaderTimerPipBtn')?.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleGlobalTimerMiniPip();
          });
        }

        const taskNode = host.querySelector('#globalHeaderTimerTask');
        const projectNode = host.querySelector('#globalHeaderTimerProject');
        const valueNode = host.querySelector('#globalHeaderTimerValue');
        const toggleNode = host.querySelector('#globalHeaderTimerToggleBtn');

        if (taskNode) taskNode.textContent = globalTimerState.taskName || 'Temporizador activo';
        if (projectNode) projectNode.textContent = `${globalTimerState.projectTitle || 'Proyecto'}`;
        if (valueNode) valueNode.textContent = formatTimer(getDisplayedSeconds());
        if (toggleNode) {
          toggleNode.title = globalTimerState.isRunning ? 'Pausar temporizador' : 'Continuar temporizador';
          toggleNode.innerHTML = globalTimerState.isRunning
            ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
            : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';
        }
      }

      function startGlobalTimerLoop() {
        if (globalTimerInterval) clearInterval(globalTimerInterval);
        const pomodoroHeaderState = getPomodoroHeaderState();
        if (pomodoroHeaderState?.isRunning) {
          globalTimerInterval = setInterval(() => {
            renderGlobalTimer();
          }, 1000);
          return;
        }

        if (globalTimerState?.isRunning) {
          globalTimerInterval = setInterval(() => {
            const valueNode = host.querySelector('#globalHeaderTimerValue');
            if (valueNode) valueNode.textContent = formatTimer(getDisplayedSeconds());
            syncGlobalTimerPanels();
          }, 1000);
          return;
        }
      }

      function renderGlobalTimer() {
        updateGlobalTimerDom();
        syncGlobalTimerPanels();
        setGlobalPipActionButtonState();
        startGlobalTimerLoop();
        // Keep mediaSession in sync so PiP play/pause icon is correct
        if ('mediaSession' in navigator && globalTimerState?.projectId) {
          try {
            navigator.mediaSession.playbackState = globalTimerState.isRunning ? 'playing' : 'paused';
          } catch (_) {}
        }
      }

      function syncGlobalTimerPanels() {
        const display = formatTimer(getDisplayedSeconds());
        const projectTitle = globalTimerState?.projectTitle || 'Proyecto';
        const clientName = globalTimerState?.clientName || 'Sin Cliente';
        const fsProject = document.getElementById('globalTimerFsProject');
        const fsClient = document.getElementById('globalTimerFsClient');
        const fsDisplay = document.getElementById('globalTimerFsDisplay');
        const pipProject = document.getElementById('globalTimerPipProject');
        const pipClient = document.getElementById('globalTimerPipClient');
        const pipDisplay = document.getElementById('globalTimerPipDisplay');
        if (fsProject) fsProject.textContent = projectTitle;
        if (fsClient) fsClient.textContent = clientName;
        if (fsDisplay) fsDisplay.textContent = display;
        if (pipProject) pipProject.textContent = projectTitle;
        if (pipClient) pipClient.textContent = clientName;
        if (pipDisplay) pipDisplay.textContent = display;
        syncGlobalTimerFullscreenControls();
        drawGlobalTimerPipCanvas(display);
      }

      function globalTimerEscapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;',
        }[char]));
      }

      function formatGlobalTimerNoteDate(value) {
        if (!value) return '';
        try {
          return new Intl.DateTimeFormat('es-CO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
          }).format(new Date(value));
        } catch (_) {
          return '';
        }
      }

      function getGlobalTimerTaskFromSnapshot() {
        if (!globalTimerProjectSnapshot || !globalTimerState?.taskId) return null;
        return (Array.isArray(globalTimerProjectSnapshot.tareas) ? globalTimerProjectSnapshot.tareas : [])
          .find((task) => String(task?.id || '') === String(globalTimerState.taskId || '')) || null;
      }

      async function fetchGlobalTimerProjectSnapshot(force = false) {
        if (!globalTimerState?.projectId) return null;
        if (!force && globalTimerProjectSnapshot && String(globalTimerProjectSnapshot.id) === String(globalTimerState.projectId)) {
          return globalTimerProjectSnapshot;
        }

        const res = await fetch(`/api/proyectos/${encodeURIComponent(globalTimerState.projectId)}`, {
          headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.data) throw new Error('No se pudo cargar el proyecto activo.');
        globalTimerProjectSnapshot = data.data;
        return globalTimerProjectSnapshot;
      }

      function renderGlobalTimerFullscreenSubtasks() {
        const list = document.getElementById('globalTimerFsSubtasksList');
        if (!list) return;

        const task = getGlobalTimerTaskFromSnapshot();
        if (!globalTimerState?.taskId) {
          list.innerHTML = '<div class="text-sm text-slate-300">Sin tarea vinculada para este temporizador.</div>';
          return;
        }
        if (!globalTimerProjectSnapshot) {
          list.innerHTML = '<div class="text-sm text-slate-300">Cargando checklist...</div>';
          return;
        }
        if (!task) {
          list.innerHTML = '<div class="text-sm text-slate-300">No encontré la tarea vinculada.</div>';
          return;
        }

        const subtasks = Array.isArray(task.subtasks) ? task.subtasks : [];
        if (!subtasks.length) {
          list.innerHTML = '<div class="text-sm text-slate-300">No hay elementos en el checklist todavía.</div>';
          return;
        }

        const hasMore = subtasks.length > 6;
        const visibleSubtasks = globalTimerFsShowAllSubtasks ? subtasks : subtasks.slice(0, 6);
        list.innerHTML = visibleSubtasks.map((subtask) => {
          const id = globalTimerEscapeHtml(String(subtask.id || ''));
          const done = !!subtask.done;
          return `<button type="button" data-global-timer-subtask-id="${id}" class="w-full flex items-center gap-3 rounded-lg bg-white/10 px-3 py-2 text-left hover:bg-white/20 transition-colors">
            <span class="w-5 h-5 rounded border ${done ? 'bg-lime-300 border-lime-300 text-slate-950' : 'border-slate-400 text-transparent'} flex items-center justify-center shrink-0">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="min-w-0 text-base ${done ? 'line-through text-slate-400' : 'text-white'}">${globalTimerEscapeHtml(String(subtask.texto || ''))}</span>
          </button>`;
        }).join('');

        list.querySelectorAll('[data-global-timer-subtask-id]').forEach((button) => {
          button.addEventListener('click', () => toggleGlobalTimerFullscreenSubtask(button.getAttribute('data-global-timer-subtask-id')));
        });

        if (hasMore) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'mt-1 text-xs font-bold text-lime-300 hover:text-lime-200';
          button.textContent = globalTimerFsShowAllSubtasks ? 'Ver menos' : 'Ver todas';
          button.addEventListener('click', () => {
            globalTimerFsShowAllSubtasks = !globalTimerFsShowAllSubtasks;
            renderGlobalTimerFullscreenSubtasks();
          });
          list.appendChild(button);
        }
      }

      function renderGlobalTimerFullscreenNotes() {
        const list = document.getElementById('globalTimerFsNotesList');
        if (!list) return;

        const task = getGlobalTimerTaskFromSnapshot();
        if (!globalTimerState?.taskId) {
          list.innerHTML = '<div class="text-sm text-slate-300">Sin tarea vinculada para este temporizador.</div>';
          return;
        }
        if (!globalTimerProjectSnapshot) {
          list.innerHTML = '<div class="text-sm text-slate-300">Cargando notas...</div>';
          return;
        }
        if (!task) {
          list.innerHTML = '<div class="text-sm text-slate-300">No encontré la tarea vinculada.</div>';
          return;
        }

        const notes = (Array.isArray(task.notes) ? task.notes : [])
          .slice()
          .sort((a, b) => new Date(b.updated_at || b.created_at || 0).getTime() - new Date(a.updated_at || a.created_at || 0).getTime());

        if (!notes.length) {
          list.innerHTML = '<div class="text-sm text-slate-300">No hay notas de pipeline todavía.</div>';
          return;
        }

        const hasMore = notes.length > 4;
        const visibleNotes = globalTimerFsShowAllNotes ? notes : notes.slice(0, 4);
        list.innerHTML = visibleNotes.map((note) => `
          <div class="rounded-lg border border-white/15 bg-white/10 px-3 py-2">
            <div class="flex items-center justify-between gap-2">
              <div class="text-[11px] font-bold text-slate-100">${globalTimerEscapeHtml(String(note.author_name || note.user || 'Usuario'))}</div>
              <div class="text-[10px] text-slate-300">${globalTimerEscapeHtml(formatGlobalTimerNoteDate(note.created_at))}</div>
            </div>
            <div class="mt-1 whitespace-pre-wrap text-sm leading-5 text-slate-100">${globalTimerEscapeHtml(String(note.texto || ''))}</div>
          </div>
        `).join('');

        if (hasMore) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'mt-1 text-xs font-bold text-lime-300 hover:text-lime-200';
          button.textContent = globalTimerFsShowAllNotes ? 'Ver menos' : 'Ver todas';
          button.addEventListener('click', () => {
            globalTimerFsShowAllNotes = !globalTimerFsShowAllNotes;
            renderGlobalTimerFullscreenNotes();
          });
          list.appendChild(button);
        }
      }

      function renderGlobalTimerFullscreenData() {
        renderGlobalTimerFullscreenSubtasks();
        renderGlobalTimerFullscreenNotes();
      }

      function syncGlobalTimerFullscreenControls() {
        const pauseBtn = document.getElementById('globalTimerFsPauseBtn');
        const saveBtn = document.getElementById('globalTimerFsSaveBtn');
        if (pauseBtn) {
          pauseBtn.innerHTML = globalTimerState?.isRunning
            ? '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
            : '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>';
        }
        if (saveBtn) {
          saveBtn.disabled = getDisplayedSeconds() <= 0;
        }
      }

      async function openGlobalTimerFullscreen() {
        if (!globalTimerState?.projectId) return;
        const panel = document.getElementById('globalTimerFullscreenPanel');
        if (!panel) return;
        panel.classList.remove('hidden');
        document.body.classList.add('timer-fullscreen-open');
        syncGlobalTimerPanels();
        globalTimerFsShowAllSubtasks = false;
        globalTimerFsShowAllNotes = false;
        renderGlobalTimerFullscreenData();
        try {
          await fetchGlobalTimerProjectSnapshot(true);
        } catch (error) {
          console.error(error);
        }
        renderGlobalTimerFullscreenData();
      }

      function closeGlobalTimerFullscreen() {
        const panel = document.getElementById('globalTimerFullscreenPanel');
        if (!panel) return;
        panel.classList.add('hidden');
        document.body.classList.remove('timer-fullscreen-open');
        if (document.fullscreenElement && document.exitFullscreen) {
          document.exitFullscreen().catch(() => {});
        }
      }

      async function addGlobalTimerFullscreenSubtask() {
        const input = document.getElementById('globalTimerFsNewSubtaskInput');
        const texto = String(input?.value || '').trim();
        if (!globalTimerState?.projectId || !globalTimerState?.taskId || !texto) return;

        const res = await fetch('/api/proyectos/tareas/subtareas/agregar', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || '', 'X-Requested-With': 'XMLHttpRequest'},
          body: JSON.stringify({id: globalTimerState.projectId, tarea_id: globalTimerState.taskId, texto}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) return;
        if (input) input.value = '';
        globalTimerProjectSnapshot = data.item;
        renderGlobalTimerFullscreenData();
      }

      async function addGlobalTimerFullscreenNote() {
        const input = document.getElementById('globalTimerFsNewNoteInput');
        const texto = String(input?.value || '').trim();
        if (!globalTimerState?.projectId || !globalTimerState?.taskId || !texto) return;

        const res = await fetch('/api/proyectos/tareas/notas/agregar', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || '', 'X-Requested-With': 'XMLHttpRequest'},
          body: JSON.stringify({id: globalTimerState.projectId, tarea_id: globalTimerState.taskId, texto}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) return;
        if (input) input.value = '';
        globalTimerProjectSnapshot = data.item;
        renderGlobalTimerFullscreenData();
      }

      async function toggleGlobalTimerFullscreenSubtask(subtaskId) {
        if (!globalTimerState?.projectId || !globalTimerState?.taskId || !subtaskId) return;

        const res = await fetch('/api/proyectos/tareas/subtareas/toggle', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken || '', 'X-Requested-With': 'XMLHttpRequest'},
          body: JSON.stringify({id: globalTimerState.projectId, tarea_id: globalTimerState.taskId, subtarea_id: subtaskId}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) return;
        globalTimerProjectSnapshot = data.item;
        renderGlobalTimerFullscreenData();
      }

      async function ensureGlobalPipSource() {
        const canvas = document.getElementById('globalTimerPipCanvas');
        const video = document.getElementById('globalTimerPipVideo');
        if (!canvas || !video || !canvas.captureStream) return false;
        if (globalPipTrackHealthy()) return true;
        resetGlobalPipSource();
        const stream = canvas.captureStream(30);
        globalPipStream = stream;
        globalPipVideoTrack = stream.getVideoTracks ? (stream.getVideoTracks()[0] || null) : null;
        const audioTrack = ensureGlobalPipSilentAudioTrack();
        if (audioTrack && globalPipStream.addTrack) {
          try { globalPipStream.addTrack(audioTrack); } catch (_) {}
        }
        if (globalPipVideoTrack) {
          globalPipVideoTrack.addEventListener('ended', () => {
            if (isGlobalNativePipOpen()) recoverGlobalPipSource();
          });
          globalPipVideoTrack.addEventListener('mute', () => {
            setTimeout(() => {
              if (isGlobalNativePipOpen()) recoverGlobalPipSource();
            }, 350);
          });
        }
        video.srcObject = globalPipStream;
        video.muted = true;
        video.playsInline = true;
        video.setAttribute('webkit-playsinline', 'true');
        video.autoplay = true;
        drawGlobalTimerPipCanvas(formatTimer(getDisplayedSeconds()));
        try { await video.play(); } catch (_) {}
        globalPipStreamReady = true;
        return true;
      }

      function resetGlobalPipSource() {
        if (globalPipStream?.getTracks) {
          globalPipStream.getTracks().forEach((track) => {
            try { track.stop(); } catch (_) {}
          });
        }
        globalPipStream = null;
        globalPipVideoTrack = null;
        globalPipStreamReady = false;
        const video = document.getElementById('globalTimerPipVideo');
        if (video) {
          try { video.srcObject = null; } catch (_) {}
        }
        stopGlobalPipSilentAudio();
      }

      function stopGlobalPipSilentAudio() {
        try { globalPipAudioSource?.stop(); } catch (_) {}
        try { globalPipAudioTrack?.stop(); } catch (_) {}
        try { globalPipAudioContext?.close(); } catch (_) {}
        globalPipAudioContext = null;
        globalPipAudioSource = null;
        globalPipAudioTrack = null;
      }

      function ensureGlobalPipSilentAudioTrack() {
        if (!globalPipAudioPrimed) return null;
        if (globalPipAudioTrack && globalPipAudioTrack.readyState !== 'ended') {
          if (globalPipAudioContext?.state === 'suspended') globalPipAudioContext.resume().catch(() => {});
          return globalPipAudioTrack;
        }

        const AudioCtor = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtor) return null;
        try {
          globalPipAudioContext = new AudioCtor();
          const destination = globalPipAudioContext.createMediaStreamDestination();
          const oscillator = globalPipAudioContext.createOscillator();
          const gain = globalPipAudioContext.createGain();
          oscillator.frequency.value = 1;
          gain.gain.value = 0.00001;
          oscillator.connect(gain);
          gain.connect(destination);
          oscillator.start();
          globalPipAudioSource = oscillator;
          globalPipAudioTrack = destination.stream.getAudioTracks()[0] || null;
          globalPipAudioContext.resume().catch(() => {});
          return globalPipAudioTrack;
        } catch (_) {
          return null;
        }
      }

      function globalPipTrackHealthy() {
        const video = document.getElementById('globalTimerPipVideo');
        const audioHealthy = !globalPipAudioPrimed || (globalPipAudioTrack && globalPipAudioTrack.readyState !== 'ended');
        return !!globalPipStream
          && !!globalPipVideoTrack
          && !!audioHealthy
          && globalPipVideoTrack.readyState !== 'ended'
          && video?.srcObject === globalPipStream;
      }

      function isGlobalNativePipOpen() {
        const video = document.getElementById('globalTimerPipVideo');
        return document.pictureInPictureElement === video || video?.webkitPresentationMode === 'picture-in-picture';
      }

      async function recoverGlobalPipSource() {
        if (globalPipRecovering) return;
        const video = document.getElementById('globalTimerPipVideo');
        if (!video) return;
        globalPipRecovering = true;
        try {
          resetGlobalPipSource();
          await ensureGlobalPipSource();
          drawGlobalTimerPipCanvas(formatTimer(getDisplayedSeconds()));
          suppressGlobalPipPlaybackSync = true;
          await video.play().catch(() => {});
          setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 0);
        } finally {
          globalPipRecovering = false;
        }
      }

      function setGlobalPipSourceVisible(show) {
        const video = document.getElementById('globalTimerPipVideo');
        if (!video) return;
        if (show) {
          video.style.position = 'fixed';
          video.style.top = '0';
          video.style.left = '0';
          video.style.width = '2px';
          video.style.height = '2px';
          video.style.opacity = '0.01';
          video.style.pointerEvents = 'none';
          video.style.zIndex = '1';
          video.style.transform = 'translateZ(0)';
          video.style.background = '#000';
        } else {
          video.style.position = 'fixed';
          video.style.top = '-9999px';
          video.style.left = '-9999px';
          video.style.width = '1px';
          video.style.height = '1px';
          video.style.opacity = '0';
          video.style.pointerEvents = 'none';
          video.style.zIndex = '-1';
          video.style.transform = '';
          video.style.background = 'transparent';
        }
      }

      function drawGlobalTimerPipCanvas(timeValue) {
        const canvas = document.getElementById('globalTimerPipCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const title = String(globalTimerState?.projectTitle || 'Proyecto');
        const client = String(globalTimerState?.clientName || 'Sin Cliente');
        const left = 34;
        const maxTextWidth = canvas.width - (left * 2);
        const fitText = (text, maxWidth, font) => {
          ctx.font = font;
          if (ctx.measureText(text).width <= maxWidth) return text;
          let safe = String(text || '');
          while (safe.length > 1 && ctx.measureText(`${safe}…`).width > maxWidth) {
            safe = safe.slice(0, -1);
          }
          return `${safe}…`;
        };

        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = (Math.floor(Date.now() / 250) % 2) ? '#10182b' : '#0f172a';
        ctx.fillRect(canvas.width - 3, canvas.height - 3, 2, 2);
        ctx.fillStyle = '#e2e8f0';
        ctx.font = 'bold 24px system-ui';
        ctx.fillText(fitText(title, maxTextWidth, 'bold 24px system-ui'), left, 142);
        ctx.fillStyle = '#94a3b8';
        ctx.font = '18px system-ui';
        ctx.fillText(fitText(client, maxTextWidth, '18px system-ui'), left, 172);
        ctx.fillStyle = '#bef264';
        ctx.font = 'bold 86px monospace';
        ctx.fillText(timeValue || '00:00:00', left, 330);
        if (globalPipVideoTrack && typeof globalPipVideoTrack.requestFrame === 'function') {
          globalPipVideoTrack.requestFrame();
        }
        globalPipLastFrameAt = Date.now();
      }

      function startGlobalPipRenderLoop() {
        if (globalPipRenderInterval) clearTimeout(globalPipRenderInterval);
        if (globalPipWatchdogInterval) clearInterval(globalPipWatchdogInterval);

        const pump = () => {
          if (!isGlobalNativePipOpen()) {
            globalPipRenderInterval = null;
            return;
          }
          syncGlobalTimerPanels();
          const video = document.getElementById('globalTimerPipVideo');
          if (isGlobalNativePipOpen() && video?.paused) {
            suppressGlobalPipPlaybackSync = true;
            video.play().catch(() => {}).finally(() => {
              setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 0);
            });
          }
          globalPipRenderInterval = setTimeout(pump, 250);
        };
        pump();

        globalPipWatchdogInterval = setInterval(() => {
          const video = document.getElementById('globalTimerPipVideo');
          if (!isGlobalNativePipOpen()) return;
          syncGlobalTimerPanels();
          const staleFrame = globalPipLastFrameAt && Date.now() - globalPipLastFrameAt > 2600;
          if (!globalPipTrackHealthy() || video?.paused || video?.readyState < 2 || staleFrame) {
            suppressGlobalPipPlaybackSync = true;
            video.play().catch(() => {}).finally(() => {
              setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 0);
            });
            if (!globalPipTrackHealthy() || video?.readyState < 2 || staleFrame) {
              recoverGlobalPipSource();
            }
          }
        }, 1000);
      }

      function stopGlobalPipRenderLoop() {
        if (globalPipRenderInterval) {
          clearTimeout(globalPipRenderInterval);
          globalPipRenderInterval = null;
        }
        if (globalPipWatchdogInterval) {
          clearInterval(globalPipWatchdogInterval);
          globalPipWatchdogInterval = null;
        }
        if (!isGlobalNativePipOpen()) stopGlobalPipSilentAudio();
      }

      function refreshGlobalPipAfterResume() {
        if (!isGlobalNativePipOpen() || !globalTimerState?.projectId) return;
        syncGlobalTimerPanels();
        ensureGlobalPipSource().then(() => {
          const video = document.getElementById('globalTimerPipVideo');
          if (!video) return;
          suppressGlobalPipPlaybackSync = true;
          video.play().catch(() => {}).finally(() => {
            setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 0);
          });
          if (!globalPipRenderInterval) startGlobalPipRenderLoop();
        });
      }

      function setGlobalPipActionButtonState() {
        const btn = document.getElementById('globalTimerPipToggleBtn');
        if (!btn) return;
        btn.innerHTML = globalTimerState?.isRunning
          ? '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
          : '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
      }

      function updateGlobalPipMediaSession() {
        if (!('mediaSession' in navigator)) return;
        try {
          navigator.mediaSession.metadata = new MediaMetadata({
            title: String(globalTimerState?.taskName || 'Temporizador activo'),
            artist: String(globalTimerState?.projectTitle || 'Proyecto'),
            album: 'InFocus CRM',
          });
          navigator.mediaSession.playbackState = globalTimerState?.isRunning ? 'playing' : 'paused';
          navigator.mediaSession.setActionHandler('play', () => {
            if (!globalTimerState?.projectId || globalTimerState.isRunning) return;
            toggleGlobalTimer().catch(() => {});
          });
          navigator.mediaSession.setActionHandler('pause', () => {
            if (!globalTimerState?.projectId || !globalTimerState.isRunning) return;
            toggleGlobalTimer().catch(() => {});
          });
          navigator.mediaSession.setActionHandler('previoustrack', () => { window.focus(); });
        } catch (_) {}
      }

      async function toggleGlobalTimerMiniPip() {
        if (!globalTimerState?.projectId) return;
        globalPipAudioPrimed = true;
        syncGlobalTimerPanels();
        const video = document.getElementById('globalTimerPipVideo');
        const fallback = document.getElementById('globalTimerMiniPip');

        if (video && document.pictureInPictureElement === video && document.exitPictureInPicture) {
          suppressGlobalPipPlaybackSync = true;
          await document.exitPictureInPicture().catch(() => {});
          setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 250);
          return;
        }

        if (video && video.webkitPresentationMode === 'picture-in-picture') {
          suppressGlobalPipPlaybackSync = true;
          video.webkitSetPresentationMode('inline');
          setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 250);
          return;
        }

        const ready = await ensureGlobalPipSource();
        if (ready && video && video.requestPictureInPicture) {
          try {
            setGlobalPipSourceVisible(true);
            await video.play().catch(() => {});
            startGlobalPipRenderLoop();
            await video.requestPictureInPicture();
            updateGlobalPipMediaSession();
            return;
          } catch (_) {}
        }

        if (ready && video && video.webkitSupportsPresentationMode && video.webkitSetPresentationMode) {
          try {
            setGlobalPipSourceVisible(true);
            await video.play().catch(() => {});
            if (video.webkitSupportsPresentationMode('picture-in-picture')) {
              startGlobalPipRenderLoop();
              video.webkitSetPresentationMode('picture-in-picture');
              updateGlobalPipMediaSession();
              return;
            }
          } catch (_) {}
        }

        fallback?.classList.toggle('hidden');
      }

      async function saveGlobalTimerLog() {
        if (!globalTimerState?.projectId) return;
        const projectId = globalTimerState.projectId;
        const seconds = getDisplayedSeconds();
        if (seconds <= 0) return;
        // Stop timer on server first if running
        if (globalTimerState.isRunning) {
          try {
            const stopResponse = await fetch('/api/proyectos/timer', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
              body: JSON.stringify({ id: projectId, action: 'stop', tarea_id: globalTimerState.taskId || null }),
            });
            const stopJson = await stopResponse.json().catch(() => ({}));
            if (!stopResponse.ok || stopJson?.ok === false) {
              throw new Error('stop_failed');
            }
          } catch (error) {
            if (window.showNotification) window.showNotification('No se pudo guardar el temporizador. Intenta de nuevo.', 'error');
            return;
          }
        }
        let entries = [];
        try {
          entries = JSON.parse(localStorage.getItem(TIMER_HISTORY_PREFIX + projectId) || '[]');
          if (!Array.isArray(entries)) entries = [];
        } catch (_) {
          entries = [];
        }
        entries.push({
          time: formatTimer(seconds),
          day: new Date().toLocaleDateString('es-ES'),
          saved_by: currentUserName || 'Usuario',
          task_name: String(globalTimerState.taskName || ''),
        });
        localStorage.setItem(TIMER_HISTORY_PREFIX + projectId, JSON.stringify(entries));
        clearGlobalTimerState();
        if (window.showNotification) window.showNotification('Tiempo guardado', 'success');
      }

      async function toggleGlobalTimer() {
        if (!globalTimerState?.projectId) return;
        const currentSeconds = getDisplayedSeconds();
        try {
          const response = await fetch('/api/proyectos/timer', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              id: globalTimerState.projectId,
              action: globalTimerState.isRunning ? 'stop' : 'start',
              tarea_id: globalTimerState.taskId || null,
            }),
          });
          const data = await response.json().catch(() => ({}));
          if (!response.ok || data?.ok === false) {
            throw new Error(data?.message || 'timer_toggle_failed');
          }

          setStoredState({
            ...globalTimerState,
            isRunning: !globalTimerState.isRunning,
            currentSeconds,
            syncedAt: Date.now(),
          });
          renderGlobalTimer();
        } catch (error) {
          if (window.showNotification) window.showNotification('No se pudo actualizar el temporizador', 'error');
        }
      }

      async function deleteGlobalTimerLog() {
        if (!globalTimerState?.projectId) return;
        const projectId = String(globalTimerState.projectId);
        const clearStaleTimer = () => {
          try {
            localStorage.setItem(`project_timer_reset_v1_${projectId}`, '0');
          } catch (_) {}
          clearGlobalTimerState();
        };
        try {
          const response = await fetch('/api/proyectos/timer/eliminar', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ id: projectId, tarea_id: globalTimerState.taskId || null }),
          });
          const json = await response.json().catch(() => ({}));
          if (response.status === 404 || response.status === 410) {
            clearStaleTimer();
            if (window.showNotification) window.showNotification('Temporizador local eliminado', 'success');
            return;
          }
          if (!response.ok || json?.ok === false) {
            clearStaleTimer();
            if (window.showNotification) window.showNotification('Temporizador local eliminado. Si vuelve a aparecer, recarga la página.', 'success');
            return;
          }
          const logs = Array.isArray(json?.item?.time_logs) ? json.item.time_logs : [];
          const gross = logs.reduce((acc, log) => {
            const start = Number(log?.start || 0);
            const end = Number(log?.end || Math.floor(Date.now() / 1000));
            if (!start || end < start) return acc;
            return acc + (end - start);
          }, 0);
          localStorage.setItem(`project_timer_reset_v1_${projectId}`, String(Math.max(0, Number(gross) || 0)));
          clearGlobalTimerState();
          if (window.showNotification) window.showNotification('Registro de tiempo eliminado', 'success');
        } catch (error) {
          clearStaleTimer();
          if (window.showNotification) window.showNotification('Temporizador local eliminado', 'success');
        }
      }

      async function syncGlobalTimerFromServer() {
        try {
          const response = await fetch('/api/header/active-timer', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json().catch(() => ({}));
          const item = json.item || null;
          const stored = getStoredState();
          const pomodoroHeaderState = getPomodoroHeaderState();

          if (pomodoroHeaderState?.isRunning) {
            globalTimerState = stored;
            renderGlobalTimer();
            return;
          }

          if (item?.project_id) {
            const serverSeconds = Math.max(0, Number(item.current_seconds || 0));
            setStoredState({
              projectId: String(item.project_id),
              projectTitle: String(item.project_title || 'Proyecto'),
              clientName: String(item.client || 'Sin Cliente'),
              taskId: String(item.task_id || ''),
              taskName: String(item.task_name || 'Temporizador activo'),
              currentSeconds: serverSeconds,
              isRunning: true,
              syncedAt: Date.now(),
            });
          } else if (stored?.projectId && stored.isRunning) {
            setStoredState(null);
          } else {
            globalTimerState = stored;
          }

          renderGlobalTimer();
        } catch (_) {
          globalTimerState = getStoredState();
          renderGlobalTimer();
        }
      }

      document.getElementById('globalTimerFullscreenCloseBtn')?.addEventListener('click', closeGlobalTimerFullscreen);
      document.getElementById('globalTimerFullscreenPipBtn')?.addEventListener('click', toggleGlobalTimerMiniPip);
      document.getElementById('globalTimerFsPauseBtn')?.addEventListener('click', toggleGlobalTimer);
      document.getElementById('globalTimerFsSaveBtn')?.addEventListener('click', saveGlobalTimerLog);
      document.getElementById('globalTimerFsDeleteBtn')?.addEventListener('click', deleteGlobalTimerLog);
      document.getElementById('globalTimerFsAddSubtaskBtn')?.addEventListener('click', addGlobalTimerFullscreenSubtask);
      document.getElementById('globalTimerFsAddNoteBtn')?.addEventListener('click', addGlobalTimerFullscreenNote);
      document.getElementById('globalTimerFsNewSubtaskInput')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          addGlobalTimerFullscreenSubtask();
        }
      });
      document.getElementById('globalTimerFsNewNoteInput')?.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
          event.preventDefault();
          addGlobalTimerFullscreenNote();
        }
      });
      document.getElementById('globalTimerPipToggleBtn')?.addEventListener('click', toggleGlobalTimer);
      document.getElementById('globalTimerPipCloseBtn')?.addEventListener('click', toggleGlobalTimerMiniPip);
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !document.getElementById('globalTimerFullscreenPanel')?.classList.contains('hidden')) {
          closeGlobalTimerFullscreen();
        }
      });
      document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
          closeGlobalTimerFullscreen();
        }
      });

      const globalPipVideo = document.getElementById('globalTimerPipVideo');
      globalPipVideo?.addEventListener('leavepictureinpicture', () => {
        stopGlobalPipRenderLoop();
        setGlobalPipSourceVisible(false);
      });
      globalPipVideo?.addEventListener('webkitpresentationmodechanged', () => {
        if (globalPipVideo.webkitPresentationMode !== 'picture-in-picture') stopGlobalPipRenderLoop();
        if (globalPipVideo.webkitPresentationMode === 'picture-in-picture' && !globalPipRenderInterval) startGlobalPipRenderLoop();
        setGlobalPipSourceVisible(globalPipVideo.webkitPresentationMode === 'picture-in-picture');
        refreshGlobalPipAfterResume();
      });
      globalPipVideo?.addEventListener('pause', () => {
        if (suppressGlobalPipPlaybackSync) return;
        const isNativePip = document.pictureInPictureElement === globalPipVideo || globalPipVideo.webkitPresentationMode === 'picture-in-picture';
        if (!isNativePip || !globalTimerState?.projectId) return;
        suppressGlobalPipPlaybackSync = true;
        globalPipVideo.play().catch(() => {}).finally(() => {
          setTimeout(() => { suppressGlobalPipPlaybackSync = false; }, 0);
        });
        syncGlobalTimerPanels();
      });
      globalPipVideo?.addEventListener('play', () => {
        if (suppressGlobalPipPlaybackSync) return;
        const isNativePip = document.pictureInPictureElement === globalPipVideo || globalPipVideo.webkitPresentationMode === 'picture-in-picture';
        if (!isNativePip || !globalTimerState?.projectId) return;
        syncGlobalTimerPanels();
      });
      globalPipVideo?.addEventListener('emptied', refreshGlobalPipAfterResume);
      globalPipVideo?.addEventListener('stalled', refreshGlobalPipAfterResume);
      globalPipVideo?.addEventListener('waiting', refreshGlobalPipAfterResume);
      document.addEventListener('visibilitychange', refreshGlobalPipAfterResume);
      window.addEventListener('pageshow', refreshGlobalPipAfterResume);
      window.addEventListener('focus', refreshGlobalPipAfterResume);

      window.addEventListener('storage', (event) => {
        if (event.key !== GLOBAL_TIMER_STATE_KEY && event.key !== POMODORO_STATE_KEY) return;
        globalTimerState = getStoredState();
        renderGlobalTimer();
      });

      window.addEventListener('infocus-global-timer-updated', () => {
        syncGlobalTimerFromServer();
      });

      window.addEventListener('tdah-pomodoro-state-updated', () => {
        renderGlobalTimer();
      });

      globalTimerState = getStoredState();
      renderGlobalTimer();
      syncGlobalTimerFromServer();
      setInterval(syncGlobalTimerFromServer, 15000);
    })();
  </script>
  <style>
    .infocus-ai-launcher {
      position: fixed;
      right: 1rem;
      bottom: 8.5rem;
      z-index: 2147483200;
      font-family: inherit;
      width: 3.18rem;
      height: 3.18rem;
      isolation: isolate;
      transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
    }

    body.infocus-productivity-open .infocus-ai-launcher {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transform: translateY(.75rem) scale(.86);
    }

    .infocus-ai-launcher::before {
      content: "";
      position: absolute;
      left: 50%;
      top: 50%;
      width: 4.2rem;
      height: 4.2rem;
      transform: translate(-50%, -50%);
      border-radius: 999px;
      background:
        conic-gradient(from 20deg, rgba(66, 133, 244, .95), rgba(168, 85, 247, .94), rgba(234, 67, 153, .9), rgba(52, 211, 153, .94), rgba(66, 133, 244, .95));
      filter: blur(5px) saturate(1.25);
      opacity: .82;
      box-shadow: 0 0 16px rgba(66, 133, 244, .28), 0 0 22px rgba(168, 85, 247, .24);
      animation: infocusAiHaloOrbit 5.2s linear infinite, infocusAiHaloBlink 2.8s ease-in-out infinite;
      pointer-events: none;
      z-index: -1;
    }

    .infocus-ai-launcher::after {
      content: "";
      position: absolute;
      left: 50%;
      top: 50%;
      width: 4.7rem;
      height: 4.7rem;
      transform: translate(-50%, -50%);
      border-radius: 999px;
      background:
        radial-gradient(circle at 26% 36%, rgba(66, 133, 244, .62), transparent 36%),
        radial-gradient(circle at 70% 34%, rgba(168, 85, 247, .58), transparent 36%),
        radial-gradient(circle at 72% 70%, rgba(52, 211, 153, .54), transparent 40%),
        radial-gradient(circle at 35% 72%, rgba(234, 67, 153, .46), transparent 38%);
      filter: blur(7px) saturate(1.2);
      opacity: .66;
      animation: infocusAiAuraDrift 6.4s ease-in-out infinite alternate;
      pointer-events: none;
      z-index: -1;
    }

    @media (min-width: 768px) {
      .infocus-ai-launcher {
        right: 1.5rem;
        bottom: 6.1rem;
      }
    }

    @media (max-width: 767px) {
      .infocus-ai-launcher {
        right: .95rem;
        bottom: calc(6.25rem + env(safe-area-inset-bottom));
        width: 2.75rem;
        height: 2.75rem;
        z-index: 2147482450;
      }

      .infocus-ai-launcher::before {
        width: 3.45rem;
        height: 3.45rem;
        filter: blur(4px) saturate(1.15);
        opacity: .72;
      }

      .infocus-ai-launcher::after {
        width: 3.9rem;
        height: 3.9rem;
        filter: blur(6px) saturate(1.1);
        opacity: .54;
      }

      body.infocus-productivity-open .infocus-ai-launcher {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(-3.25rem) scale(.92);
      }

      .infocus-ai-open {
        width: 2.75rem;
        height: 2.75rem;
        box-shadow:
          0 14px 28px rgba(15, 23, 42, .22),
          inset 0 0 0 1px rgba(255,255,255,.08),
          inset 0 -8px 18px rgba(0,0,0,.18);
      }

      .infocus-ai-sparkle-icon {
        width: 1.22rem;
        height: 1.22rem;
      }

      .infocus-ai-shell {
        right: .6rem;
        left: .6rem;
        bottom: calc(5.7rem + env(safe-area-inset-bottom));
        width: auto;
        height: min(34rem, calc(100vh - 7rem));
        border-radius: 1.35rem;
      }
    }

    .infocus-ai-open {
      position: relative;
      width: 3.18rem;
      height: 3.18rem;
      border: 0;
      border-radius: 999px;
      background: #111729;
      color: transparent;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow:
        0 18px 42px rgba(15, 23, 42, .24),
        inset 0 0 0 1px rgba(255, 255, 255, .06),
        inset 0 -10px 22px rgba(0, 0, 0, .2);
      overflow: hidden;
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .infocus-ai-open:hover {
      transform: translateY(-2px) scale(1.045);
      box-shadow:
        0 22px 50px rgba(15, 23, 42, .28),
        inset 0 0 0 1px rgba(255, 255, 255, .12),
        inset 0 -10px 22px rgba(0, 0, 0, .18);
    }
    .infocus-ai-open::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 35% 20%, rgba(255, 255, 255, .18), transparent 28%),
        radial-gradient(circle at 74% 80%, rgba(139, 92, 246, .2), transparent 36%),
        linear-gradient(145deg, rgba(236, 254, 136, .1), transparent 48%);
      opacity: 1;
    }
    .infocus-ai-open::after {
      display: none;
    }
    .infocus-ai-open svg,
    .infocus-ai-open i {
      position: relative;
      z-index: 1;
      font-size: 1.25rem;
      line-height: 1;
      background: linear-gradient(135deg, #a78bfa 0%, #ecfe88 58%, #a3e635 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      filter: drop-shadow(0 0 8px rgba(236, 254, 136, .28));
    }
    .infocus-ai-sparkle-icon {
      width: 1.46rem;
      height: 1.46rem;
      display: inline-block;
      color: #ecfe88;
    }
    .infocus-ai-sparkle-icon i {
      position: absolute;
      display: block;
      color: currentColor;
      line-height: 1;
      transform-origin: center;
    }
    .infocus-ai-sparkle-icon .sparkle-main {
      left: .36rem;
      top: .22rem;
      font-size: .72rem;
      transform: rotate(45deg) scaleX(.82);
    }
    .infocus-ai-sparkle-icon .sparkle-side {
      left: .88rem;
      top: .78rem;
      font-size: .46rem;
      transform: rotate(45deg) scaleX(.82);
    }
    .infocus-ai-sparkle-icon .sparkle-small {
      left: .12rem;
      top: .66rem;
      font-size: .34rem;
      color: #c4b5fd;
      transform: rotate(45deg) scaleX(.82);
    }

    .infocus-ai-shell {
      position: fixed;
      right: .85rem;
      bottom: .85rem;
      width: min(21.5rem, calc(100vw - 1.5rem));
      height: min(36.5rem, calc(100vh - 1.5rem));
      z-index: 2147483201;
      border-radius: 1.55rem;
      border: 1px solid rgba(148, 163, 184, .28);
      background: linear-gradient(160deg, rgba(230, 236, 255, .95), rgba(255, 255, 255, .98) 42%, rgba(255, 249, 238, .95));
      box-shadow: 0 30px 90px rgba(15, 23, 42, .24);
      overflow: hidden;
      display: none;
      transform: translateY(18px) scale(.98);
      opacity: 0;
      transition: transform .22s ease, opacity .22s ease;
    }

    .infocus-ai-shell.is-open {
      display: flex;
      flex-direction: column;
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    @media (max-width: 640px) {
      .infocus-ai-shell {
        inset: .6rem;
        width: auto;
        height: auto;
        border-radius: 1.6rem;
      }
    }

    .infocus-ai-shell::before {
      content: "";
      position: absolute;
      inset: -30% -20% auto -20%;
      height: 18rem;
      background: radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .32), transparent 33%),
                  radial-gradient(circle at 65% 25%, rgba(217, 70, 239, .25), transparent 30%),
                  radial-gradient(circle at 88% 38%, rgba(251, 146, 60, .22), transparent 28%);
      filter: blur(18px);
      animation: infocusAiGlow 9s ease-in-out infinite alternate;
      pointer-events: none;
    }

    .infocus-ai-header,
    .infocus-ai-body,
    .infocus-ai-composer {
      position: relative;
      z-index: 1;
    }

    .infocus-ai-header {
      padding: .72rem .78rem .55rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .55rem;
    }

    .infocus-ai-brand {
      display: inline-flex;
      align-items: center;
      gap: .42rem;
      padding: .28rem .5rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, .72);
      box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .16);
      color: #101729;
      font-size: .9rem;
      font-weight: 900;
    }

    .infocus-ai-avatar {
      width: 1.55rem;
      height: 1.55rem;
      border-radius: 999px;
      background: #ecfe88;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #101729;
      box-shadow: 0 10px 24px rgba(236, 254, 136, .26);
      font-size: .76rem;
    }

    .infocus-ai-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: .45rem;
      background: #8b2be2;
      color: #fff;
      font-size: .68rem;
      line-height: 1;
      padding: .22rem .3rem;
      font-weight: 900;
    }

    .infocus-ai-icon-btn {
      width: 2rem;
      height: 2rem;
      border-radius: 999px;
      border: 0;
      background: rgba(255, 255, 255, .55);
      color: #64748b;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background .15s ease, color .15s ease;
    }

    .infocus-ai-icon-btn:hover { background: #fff; color: #101729; }
    .infocus-ai-icon-btn svg { width: .98rem; height: .98rem; }

    .infocus-ai-menu {
      position: absolute;
      top: 3.35rem;
      left: .78rem;
      right: .78rem;
      border-radius: .86rem;
      background: rgba(255, 255, 255, .94);
      border: 1px solid rgba(226, 232, 240, .9);
      box-shadow: 0 22px 54px rgba(15, 23, 42, .16);
      padding: .35rem;
      display: none;
      z-index: 18;
      backdrop-filter: blur(18px);
    }

    .infocus-ai-menu.is-open { display: block; }
    .infocus-ai-menu button {
      width: 100%;
      display: flex;
      align-items: center;
      gap: .55rem;
      border: 0;
      border-radius: .7rem;
      background: transparent;
      padding: .52rem .62rem;
      color: #1e293b;
      font-weight: 800;
      text-align: left;
      min-height: 2.25rem;
    }
    .infocus-ai-menu button:hover { background: #f8fafc; }
    .infocus-ai-history {
      max-height: 8rem;
      overflow-y: auto;
      padding-top: .25rem;
      border-top: 1px solid #edf2f7;
    }

    .infocus-ai-history-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 2rem;
      gap: .25rem;
      align-items: center;
    }

    .infocus-ai-history-row .infocus-ai-history-item {
      min-width: 0;
    }

    .infocus-ai-menu .infocus-ai-history-delete {
      width: 2rem;
      height: 2rem;
      min-height: 2rem;
      padding: 0;
      justify-content: center;
      color: #fb7185;
    }

    .infocus-ai-menu .infocus-ai-history-delete:hover {
      background: #fff1f2;
      color: #e11d48;
    }

    .infocus-ai-body {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      padding: .55rem .78rem .7rem;
    }

    .infocus-ai-welcome {
      padding: .75rem .28rem .55rem;
      text-align: center;
      color: #0f172a;
    }
    .infocus-ai-welcome h2 {
      font-size: 1.32rem;
      line-height: 1.1;
      font-weight: 950;
      margin-bottom: .78rem;
    }
    .infocus-ai-suggestions {
      display: grid;
      gap: .42rem;
      text-align: left;
    }
    .infocus-ai-suggestion {
      width: 100%;
      border: 1px solid rgba(226, 232, 240, .9);
      background: rgba(255, 255, 255, .72);
      border-radius: .72rem;
      padding: .56rem .68rem;
      color: #172554;
      font-size: .8rem;
      font-weight: 750;
      text-align: left;
      transition: transform .16s ease, border-color .16s ease;
    }
    .infocus-ai-suggestion:hover {
      transform: translateX(3px);
      border-color: #c4b5fd;
    }
    .infocus-ai-suggestion.is-mental-block {
      border-color: rgba(132, 204, 22, .48);
      background: linear-gradient(135deg, #ecfe88, #bbf7d0);
      color: #102018;
      font-weight: 900;
      box-shadow: 0 12px 26px rgba(132, 204, 22, .18);
    }
    .infocus-ai-suggestion.is-mental-block:hover {
      border-color: rgba(101, 163, 13, .7);
      background: linear-gradient(135deg, #d9f99d, #86efac);
    }

    .infocus-ai-recent {
      margin-top: .85rem;
      text-align: left;
    }
    .infocus-ai-recent-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      margin-bottom: .42rem;
      padding: 0 .15rem;
      color: #64748b;
      font-size: .64rem;
      font-weight: 950;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    .infocus-ai-recent-list {
      display: grid;
      gap: .38rem;
    }
    .infocus-ai-recent-empty {
      border: 1px dashed rgba(203, 213, 225, .9);
      border-radius: .78rem;
      padding: .56rem .68rem;
      background: rgba(255, 255, 255, .5);
      color: #94a3b8;
      font-size: .76rem;
      font-weight: 750;
    }
    .infocus-ai-recent-chat {
      width: 100%;
      display: grid;
      grid-template-columns: 1.42rem minmax(0, 1fr);
      gap: .5rem;
      align-items: center;
      border: 1px solid rgba(226, 232, 240, .9);
      border-radius: .78rem;
      background: rgba(255, 255, 255, .64);
      padding: .52rem .62rem;
      color: #172554;
      text-align: left;
      transition: transform .16s ease, border-color .16s ease, background .16s ease;
    }
    .infocus-ai-recent-chat:hover {
      transform: translateX(3px);
      border-color: #c4b5fd;
      background: rgba(255, 255, 255, .88);
    }
    .infocus-ai-recent-chat > i,
    .infocus-ai-recent-chat > .infocus-ai-sparkle-icon {
      width: 1.42rem;
      height: 1.42rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: #ecfe88;
      color: #101729;
      font-size: .65rem;
    }
    .infocus-ai-recent-chat .infocus-ai-sparkle-icon {
      position: relative;
      color: #101729;
      flex: none;
    }
    .infocus-ai-recent-chat span {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-size: .78rem;
      font-weight: 850;
    }

    .infocus-ai-message {
      max-width: 92%;
      width: fit-content;
      padding: .58rem .68rem;
      border-radius: .86rem;
      margin-bottom: .55rem;
      white-space: pre-wrap;
      line-height: 1.38;
      font-size: .82rem;
      font-weight: 560;
      color: #101729;
    }
    .infocus-ai-message strong {
      font-weight: 900;
    }
    .infocus-ai-message .infocus-ai-action-link {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      width: fit-content;
      margin-top: .45rem;
      border-radius: 999px;
      background: #ecfe88;
      color: #101729;
      padding: .45rem .75rem;
      font-weight: 900;
      text-decoration: none;
      box-shadow: 0 10px 24px rgba(236, 254, 136, .22);
    }
    .infocus-ai-message .infocus-ai-action-link:hover {
      background: #d9f85d;
    }
    .infocus-ai-confirm-actions {
      display: flex;
      flex-wrap: wrap;
      gap: .38rem;
      margin-top: .55rem;
      padding-top: .48rem;
      border-top: 1px solid rgba(226, 232, 240, .7);
    }
    .infocus-ai-confirm-actions button {
      border: 0;
      border-radius: 999px;
      padding: .42rem .68rem;
      font-size: .74rem;
      font-weight: 900;
      line-height: 1;
      transition: transform .14s ease, background .14s ease;
    }
    .infocus-ai-confirm-actions button:hover {
      transform: translateY(-1px);
    }
    .infocus-ai-confirm-create {
      background: #ecfe88;
      color: #101729;
      box-shadow: 0 10px 22px rgba(236, 254, 136, .22);
    }
    .infocus-ai-confirm-cancel {
      background: #f1f5f9;
      color: #475569;
    }
    .infocus-ai-preview-card {
      margin-top: .62rem;
      border: 1px solid rgba(203, 213, 225, .72);
      border-radius: 1rem;
      background: #ffffff;
      overflow: hidden;
      box-shadow: 0 14px 30px rgba(15, 23, 42, .075);
      white-space: normal;
      line-height: 1.25;
    }
    .infocus-ai-preview-card__head {
      display: flex;
      align-items: center;
      gap: .58rem;
      padding: .72rem .78rem;
      background:
        radial-gradient(circle at 8% 0%, rgba(236, 254, 136, .24), transparent 38%),
        linear-gradient(135deg, #111729, #172033);
      color: #ffffff;
    }
    .infocus-ai-preview-card__icon {
      width: 1.85rem;
      height: 1.85rem;
      border-radius: 999px;
      background: #ecfe88;
      color: #111729;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: none;
      box-shadow: 0 8px 20px rgba(236, 254, 136, .25);
    }
    .infocus-ai-preview-card__title {
      min-width: 0;
      font-size: .86rem;
      font-weight: 950;
      line-height: 1.15;
    }
    .infocus-ai-preview-card__type {
      display: block;
      margin-bottom: .12rem;
      color: rgba(236, 254, 136, .92);
      font-size: .58rem;
      font-weight: 950;
      letter-spacing: .09em;
      text-transform: uppercase;
    }
    .infocus-ai-preview-card__meta {
      padding: .68rem .78rem .72rem;
      display: grid;
      gap: .42rem;
    }
    .infocus-ai-preview-row {
      display: grid;
      grid-template-columns: minmax(5rem, .7fr) minmax(0, 1fr);
      gap: .65rem;
      align-items: start;
      border-radius: .68rem;
      background: rgba(241, 245, 249, .72);
      padding: .46rem .54rem;
      font-size: .72rem;
      color: #64748b;
    }
    .infocus-ai-preview-row strong {
      color: #334155;
      font-weight: 900;
    }
    .infocus-ai-preview-row span {
      color: #0f172a;
      font-weight: 800;
      text-align: right;
      overflow-wrap: anywhere;
    }
    .infocus-ai-preview-list {
      margin: 0 .78rem .78rem;
      padding: .62rem .68rem;
      border-radius: .78rem;
      background: #f8fafc;
      border: 1px solid rgba(226, 232, 240, .9);
      color: #334155;
      font-size: .72rem;
      font-weight: 750;
    }
    .infocus-ai-preview-list strong {
      display: block;
      color: #0f172a;
      font-size: .66rem;
      font-weight: 950;
      letter-spacing: .06em;
      text-transform: uppercase;
      margin-bottom: .42rem;
    }
    .infocus-ai-preview-list div + div {
      margin-top: .35rem;
    }
    .infocus-ai-message.user {
      margin-left: auto;
      background: #ebe7ff;
      border-bottom-right-radius: .35rem;
    }
    .infocus-ai-message.assistant {
      margin-right: auto;
      background: rgba(255, 255, 255, .7);
      border: 1px solid rgba(226, 232, 240, .7);
      border-bottom-left-radius: .35rem;
    }
    .infocus-ai-message.thinking {
      color: #64748b;
      font-style: italic;
    }
    .infocus-ai-line {
      display: block;
      min-height: 1.25em;
    }
    .infocus-ai-line.is-appearing {
      opacity: 0;
      transform: translateY(8px);
      filter: blur(3px);
      animation: infocusAiLineIn .34s cubic-bezier(.2,.85,.25,1) forwards;
    }
    .infocus-ai-line.is-blank {
      min-height: .22em;
    }
    .infocus-ai-message.is-revealing {
      box-shadow: 0 18px 48px rgba(124, 58, 237, .09), inset 0 0 0 1px rgba(255, 255, 255, .55);
    }

    .infocus-ai-composer {
      padding: .62rem .72rem .72rem;
    }
    .infocus-ai-input-shell {
      position: relative;
      border-radius: 1.05rem;
      padding: 2px;
      background: linear-gradient(90deg, #93c5fd, #c084fc, #f0abfc, #fb923c, #ecfe88, #93c5fd);
      background-size: 280% 100%;
      animation: infocusAiBorder 5s linear infinite;
      box-shadow: 0 12px 28px rgba(99, 102, 241, .13);
    }
    .infocus-ai-input-inner {
      border-radius: .96rem;
      background: #fff;
      padding: .38rem;
      display: flex;
      align-items: end;
      gap: .42rem;
    }
    .infocus-ai-input {
      flex: 1;
      min-height: 1.9rem;
      max-height: 6rem;
      resize: none;
      border: 0;
      outline: 0;
      padding: .32rem .42rem;
      color: #172554;
      font-weight: 650;
      font-size: .82rem;
      line-height: 1.32;
      box-shadow: none !important;
      background: transparent !important;
    }
    .infocus-ai-input:focus {
      outline: none !important;
      border-color: transparent !important;
      box-shadow: none !important;
      --tw-ring-color: transparent !important;
      --tw-ring-shadow: 0 0 #0000 !important;
    }
    .infocus-ai-input::placeholder { color: #8b93c5; }
    .infocus-ai-send {
      width: 2rem;
      height: 2rem;
      border-radius: 999px;
      border: 0;
      color: #fff;
      background: linear-gradient(135deg, #2563eb, #7c3aed);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 8px 20px rgba(37, 99, 235, .2);
    }
    .infocus-ai-send:disabled { opacity: .55; cursor: not-allowed; }
    .infocus-ai-send svg { width: .94rem; height: .94rem; }

    .infocus-ai-thinking {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
    }
    .infocus-ai-thinking-robot {
      width: 1.55rem;
      height: 1.55rem;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #111729;
      background: #ecfe88;
      box-shadow: 0 0 18px rgba(236, 254, 136, .2), inset 0 0 0 1px rgba(148, 163, 184, .18);
      animation: infocusAiPulse 1.3s ease-in-out infinite;
    }
    .infocus-ai-thinking-robot > i,
    .infocus-ai-thinking-robot > .infocus-ai-sparkle-icon {
      font-size: .82rem;
      color: #111729;
    }
    .infocus-ai-thinking-dots {
      display: inline-flex;
      gap: .28rem;
      align-items: center;
    }
    .infocus-ai-thinking-dots span {
      width: .42rem;
      height: .42rem;
      border-radius: 999px;
      background: #7c3aed;
      animation: infocusAiDot 1s ease-in-out infinite;
    }
    .infocus-ai-thinking-dots span:nth-child(2) { animation-delay: .14s; }
    .infocus-ai-thinking-dots span:nth-child(3) { animation-delay: .28s; }

    @keyframes infocusAiSpin { to { transform: rotate(360deg); } }
    @keyframes infocusAiGlow {
      0% { transform: translateX(-4%) translateY(-2%); }
      100% { transform: translateX(5%) translateY(3%); }
    }
    @keyframes infocusAiHaloOrbit {
      to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    @keyframes infocusAiHaloBlink {
      0%, 100% { opacity: .30; filter: blur(7px) saturate(1); }
      48% { opacity: .48; filter: blur(9px) saturate(1.12); }
    }
    @keyframes infocusAiAuraDrift {
      0% { transform: translate(-54%, -46%) scale(.96); }
      100% { transform: translate(-46%, -54%) scale(1.05); }
    }
    @keyframes infocusAiBorder {
      0% { background-position: 0% 50%; }
      100% { background-position: 280% 50%; }
    }
    @keyframes infocusAiPulse {
      0%, 100% { transform: scale(.9); opacity: .72; }
      50% { transform: scale(1.08); opacity: 1; }
    }
    @keyframes infocusAiDot {
      0%, 100% { transform: translateY(0); opacity: .45; }
      50% { transform: translateY(-5px); opacity: 1; }
    }
    @keyframes infocusAiLineIn {
      0% { opacity: 0; transform: translateY(8px); filter: blur(3px); }
      70% { filter: blur(0); }
      100% { opacity: 1; transform: translateY(0); filter: blur(0); }
    }
    @media (prefers-reduced-motion: reduce) {
      .infocus-ai-line.is-appearing {
        opacity: 1;
        transform: none;
        filter: none;
        animation: none;
      }
    }
  </style>

  <div id="infocusAiLauncher" class="infocus-ai-launcher">
    <button id="infocusAiOpen" type="button" class="infocus-ai-open" aria-label="Abrir Infocus AI" title="Infocus AI">
      <i class="fa-solid fa-robot" aria-hidden="true"></i>
    </button>
  </div>

  <aside id="infocusAiShell" class="infocus-ai-shell" aria-hidden="true">
    <header class="infocus-ai-header">
      <button id="infocusAiMenuToggle" type="button" class="infocus-ai-brand" aria-label="Opciones de IA">
        <span class="infocus-ai-avatar">
          <i class="fa-solid fa-robot" aria-hidden="true"></i>
        </span>
        <span>Infocus</span>
        <span class="infocus-ai-badge">AI</span>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      <div class="flex items-center gap-1">
        <button id="infocusAiNewTop" type="button" class="infocus-ai-icon-btn" title="Nuevo chat" aria-label="Nuevo chat">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 5v14M5 12h14"/></svg>
        </button>
        <button id="infocusAiClose" type="button" class="infocus-ai-icon-btn" title="Cerrar" aria-label="Cerrar IA">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </header>

    <div id="infocusAiMenu" class="infocus-ai-menu">
      <button id="infocusAiNew" type="button">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 5v14M5 12h14"/></svg>
        Nuevo chat
      </button>
      <div class="px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-400">Historial</div>
      <div id="infocusAiHistory" class="infocus-ai-history custom-scroll">
        <div class="px-3 py-3 text-sm text-slate-400">Sin chats todavía</div>
      </div>
    </div>

    <main id="infocusAiBody" class="infocus-ai-body custom-scroll">
      <section id="infocusAiWelcome" class="infocus-ai-welcome">
        <h2>¿Qué quieres hacer?</h2>
        <div class="infocus-ai-suggestions">
          <button type="button" class="infocus-ai-suggestion" data-ai-suggestion="Ayúdame a entender mis gastos del CRM. Primero dime qué información necesitas revisar y qué análisis puedes hacer sin exponer datos sensibles.">Saber mis gastos</button>
          <button type="button" class="infocus-ai-suggestion" data-ai-suggestion="Ayúdame a revisar mis ingresos del CRM. Muéstrame qué facturas, pagos o ventas recientes puedes analizar y dame opciones para profundizar.">Ver Ingresos</button>
          <button type="button" class="infocus-ai-suggestion" data-ai-suggestion="Quiero analizar un proyecto del CRM. Ayúdame a revisar estado, tareas, riesgos y próximos pasos sin hacer cambios todavía.">Analizar un proyecto</button>
          <button type="button" class="infocus-ai-suggestion" data-ai-suggestion="Ayúdame a redactar un correo profesional para un cliente o lead.">Redactar un correo para un lead</button>
          <button type="button" class="infocus-ai-suggestion is-mental-block" data-ai-suggestion="¡Tengo un bloqueo mental! Ayúdame a destrabarme con 3 preguntas cortas, una primera acción de 2 minutos y opciones simples para continuar sin abrumarme.">¡Tengo un Bloque Mental!</button>
        </div>
        <div class="infocus-ai-recent" aria-label="Chats recientes">
          <div class="infocus-ai-recent-title">
            <span>Chats recientes</span>
          </div>
          <div id="infocusAiRecentChats" class="infocus-ai-recent-list">
            <div class="infocus-ai-recent-empty">Sin chats todavía</div>
          </div>
        </div>
      </section>
    </main>

    <form id="infocusAiForm" class="infocus-ai-composer">
      <div class="infocus-ai-input-shell">
        <div class="infocus-ai-input-inner">
          <textarea id="infocusAiInput" class="infocus-ai-input" rows="1" placeholder="Pregúntale algo a Infocus AI"></textarea>
          <button id="infocusAiSend" type="submit" class="infocus-ai-send" aria-label="Enviar">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </form>
  </aside>

  <script>
    (function () {
      const shell = document.getElementById('infocusAiShell');
      const openBtn = document.getElementById('infocusAiOpen');
      const closeBtn = document.getElementById('infocusAiClose');
      const body = document.getElementById('infocusAiBody');
      const welcome = document.getElementById('infocusAiWelcome');
      const form = document.getElementById('infocusAiForm');
      const input = document.getElementById('infocusAiInput');
      const sendBtn = document.getElementById('infocusAiSend');
      const menu = document.getElementById('infocusAiMenu');
      const menuToggle = document.getElementById('infocusAiMenuToggle');
      const historyEl = document.getElementById('infocusAiHistory');
      const recentChatsEl = document.getElementById('infocusAiRecentChats');
      const endpoints = {
        index: @json(route('api.ai.chats.index')),
        chat: @json(route('api.ai.chat')),
        execute: @json(route('api.ai.actions.execute')),
        undo: @json(route('api.ai.actions.undo')),
        showBase: @json(url('/api/ia/chats')),
        deleteBase: @json(url('/api/ia/chats')),
      };

      let currentChatId = null;
      let sending = false;
      const aiScrollStoragePrefix = 'infocusAiScrollTop:';

      function scrollAiToBottom(behavior = 'smooth') {
        if (!body) return;
        const pin = (mode = 'auto') => {
          const last = body.lastElementChild;
          body.scrollTop = body.scrollHeight;
          if (last && typeof last.scrollIntoView === 'function') {
            last.scrollIntoView({ block: 'end', inline: 'nearest', behavior: mode });
          }
          body.scrollTop = body.scrollHeight;
        };
        requestAnimationFrame(() => {
          pin(behavior === 'smooth' ? 'smooth' : 'auto');
          requestAnimationFrame(() => pin('auto'));
          [80, 180, 360, 700].forEach((delay) => setTimeout(() => pin('auto'), delay));
        });
      }

      function aiScrollStorageKey() {
        return `${aiScrollStoragePrefix}${currentChatId || 'draft'}`;
      }

      function saveAiScrollPosition() {
        if (!body || !window.localStorage) return;
        try {
          window.localStorage.setItem(aiScrollStorageKey(), String(body.scrollTop || 0));
        } catch (_) {}
      }

      function restoreAiScrollPosition() {
        if (!body || !window.localStorage) return false;
        let saved = null;
        try {
          saved = window.localStorage.getItem(aiScrollStorageKey());
        } catch (_) {
          saved = null;
        }
        if (saved === null) return false;

        const position = Math.max(0, Number(saved) || 0);
        const restore = () => {
          body.scrollTop = Math.min(position, Math.max(0, body.scrollHeight - body.clientHeight));
        };
        requestAnimationFrame(() => {
          restore();
          requestAnimationFrame(restore);
          [80, 180, 360].forEach((delay) => setTimeout(restore, delay));
        });
        return true;
      }

      function openShell() {
        shell.classList.add('is-open');
        shell.setAttribute('aria-hidden', 'false');
        loadHistory();
        if (!restoreAiScrollPosition()) {
          scrollAiToBottom('auto');
        }
        setTimeout(() => input?.focus(), 80);
      }

      function closeShell() {
        saveAiScrollPosition();
        shell.classList.remove('is-open');
        shell.setAttribute('aria-hidden', 'true');
        menu.classList.remove('is-open');
      }

      window.closeInfocusAiShell = closeShell;

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, (char) => ({
          '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));
      }

      function renderAiMarkdown(value) {
        let html = escapeHtml(value);
        html = html.replace(/\*\*([^*\n][\s\S]*?[^*\n])\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]+)\)/g, (match, label, href) => {
          const safeHref = String(href || '').replace(/"/g, '%22');
          const icon = safeHref.startsWith('/facturas/') ? '<i class="fa-regular fa-file-lines" aria-hidden="true"></i>' : '<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>';
          return `<a class="infocus-ai-action-link" href="${safeHref}">${icon}<span>${label}</span></a>`;
        });
        return html;
      }

      function aiExtractField(content, labels) {
        const text = String(content || '');
        for (const label of labels) {
          const pattern = new RegExp(`(?:^|\\n)\\s*(?:[-*•]\\s*)?\\**${label}\\**\\s*:\\s*([^\\n]+)`, 'i');
          const match = text.match(pattern);
          if (match) return match[1].replace(/\*\*/g, '').trim();
        }
        return '';
      }

      function aiExtractList(content, labels, max = 6) {
        const lines = String(content || '').split(/\n/);
        const items = [];
        let active = false;
        for (const rawLine of lines) {
          const line = rawLine.trim();
          if (!line) continue;
          if (labels.some((label) => new RegExp(`^\\**${label}\\**\\s*:`, 'i').test(line))) {
            active = true;
            continue;
          }
          if (!active) continue;
          if (/^(siguiente paso|¿|cliente|estado|prioridad|fecha|responsables|descripci[oó]n|para|asunto|mensaje|reuni[oó]n|cotizaci[oó]n|contrato)\b/i.test(line)) break;
          const clean = line.replace(/^\d+[\.)]\s*/, '').replace(/^[-*•]\s*/, '').replace(/\*\*/g, '').trim();
          if (clean) items.push(clean);
          if (items.length >= max) break;
        }
        return items;
      }

      function buildAiPreviewCard(content) {
        const text = String(content || '');
        const normalized = text.toLowerCase();
        let type = '';
        let icon = 'fa-diagram-project';
        let title = '';
        let rows = [];
        let listTitle = '';
        let list = [];

        if (/nuevo proyecto|proyecto propuesto|tareas sugeridas/.test(normalized)) {
          type = 'Proyecto';
          icon = 'fa-diagram-project';
          title = aiExtractField(text, ['Nombre', 'Proyecto', 'Título', 'Titulo']) || 'Proyecto propuesto';
          rows = [
            ['Cliente', aiExtractField(text, ['Cliente', 'Empresa']) || 'Sin Cliente'],
            ['Prioridad', aiExtractField(text, ['Prioridad']) || 'Con calma'],
            ['Vence', aiExtractField(text, ['Vencimiento', 'Fecha fin', 'Fecha de entrega']) || 'Sin vencimiento'],
          ];
          listTitle = 'Tareas sugeridas';
          list = aiExtractList(text, ['Tareas sugeridas', 'Tareas', 'Lista de tareas'], 5);
        } else if (/factura propuesta|nueva factura|factura\s+[a-z0-9-]+|enviar factura|listo para enviar.*factura/.test(normalized)) {
          type = 'Factura';
          icon = 'fa-file-invoice-dollar';
          title = aiExtractField(text, ['Factura', 'Folio', 'Número', 'Numero', 'Asunto']) || 'Factura propuesta';
          rows = [
            ['Cliente', aiExtractField(text, ['Cliente', 'Empresa']) || 'Pendiente'],
            ['Para', aiExtractField(text, ['Para', 'Destinatario', 'Correo']) || 'Pendiente'],
            ['Total', aiExtractField(text, ['Total', 'Monto', 'Valor']) || 'Por calcular'],
            ['Vence', aiExtractField(text, ['Vencimiento', 'Vence']) || 'Sin vencimiento'],
          ];
          listTitle = 'Items';
          list = aiExtractList(text, ['Items', 'Conceptos', 'Servicios'], 4);
        } else if (/cotizaci[oó]n propuesta|nueva cotizaci[oó]n/.test(normalized)) {
          type = 'Cotización';
          icon = 'fa-file-signature';
          title = aiExtractField(text, ['Cotización', 'Cotizacion', 'Cliente']) || 'Cotización propuesta';
          rows = [
            ['Cliente', aiExtractField(text, ['Cliente', 'Empresa']) || 'Pendiente'],
            ['Moneda', aiExtractField(text, ['Moneda']) || ''],
            ['Vence', aiExtractField(text, ['Vencimiento', 'Vence']) || 'Sin vencimiento'],
            ['Estado', aiExtractField(text, ['Estado']) || 'Borrador'],
          ];
          listTitle = 'Items';
          list = aiExtractList(text, ['Items', 'Conceptos', 'Servicios'], 4);
        } else if (/contrato propuesto|nuevo contrato/.test(normalized)) {
          type = 'Contrato';
          icon = 'fa-file-contract';
          title = aiExtractField(text, ['Contrato', 'Cliente', 'Proyecto']) || 'Contrato propuesto';
          rows = [
            ['Cliente', aiExtractField(text, ['Cliente', 'Empresa']) || 'Pendiente'],
            ['Proyecto', aiExtractField(text, ['Proyecto']) || 'Sin proyecto'],
            ['Monto', aiExtractField(text, ['Monto', 'Valor', 'Total']) || 'Pendiente'],
            ['Estado', aiExtractField(text, ['Estado']) || 'Borrador'],
          ];
        } else if (/gasto propuesto|nuevo gasto/.test(normalized)) {
          type = 'Gasto';
          icon = 'fa-receipt';
          title = aiExtractField(text, ['Gasto', 'Concepto', 'Descripción', 'Descripcion']) || 'Gasto propuesto';
          rows = [
            ['Proveedor', aiExtractField(text, ['Proveedor']) || 'Sin proveedor'],
            ['Monto', aiExtractField(text, ['Monto', 'Valor', 'Total']) || 'Pendiente'],
            ['Fecha', aiExtractField(text, ['Fecha']) || 'Hoy'],
            ['Categoría', aiExtractField(text, ['Categoría', 'Categoria']) || 'General'],
          ];
        } else if (/correo propuesto|email propuesto|listo para enviar|^para:/m.test(normalized)) {
          type = 'Correo';
          icon = 'fa-envelope';
          title = aiExtractField(text, ['Asunto', 'Subject']) || 'Correo propuesto';
          rows = [
            ['Para', aiExtractField(text, ['Para', 'Destinatario', 'Cliente']) || 'Pendiente'],
            ['Asunto', aiExtractField(text, ['Asunto', 'Subject']) || 'Sin asunto'],
          ];
        } else if (/reuni[oó]n|meeting/.test(normalized) && /fecha|hora|programar/.test(normalized)) {
          type = 'Reunión';
          icon = 'fa-calendar-days';
          title = aiExtractField(text, ['Título', 'Titulo', 'Reunión', 'Reunion', 'Nombre']) || 'Reunión propuesta';
          rows = [
            ['Cliente', aiExtractField(text, ['Cliente', 'Empresa']) || 'Sin cliente'],
            ['Fecha', aiExtractField(text, ['Fecha']) || 'Pendiente'],
            ['Inicio', aiExtractField(text, ['Hora inicio', 'Inicio', 'Hora']) || 'Pendiente'],
            ['Fin', aiExtractField(text, ['Hora fin', 'Fin']) || 'Pendiente'],
          ];
        }

        if (!type) return '';

        const rowsHtml = rows
          .filter(([, value]) => String(value || '').trim() !== '')
          .map(([label, value]) => `<div class="infocus-ai-preview-row"><strong>${escapeHtml(label)}</strong><span>${escapeHtml(value)}</span></div>`)
          .join('');
        const listHtml = list.length
          ? `<div class="infocus-ai-preview-list"><strong>${escapeHtml(listTitle)}</strong>${list.map((item) => `<div>${escapeHtml(item)}</div>`).join('')}</div>`
          : '';

        return `
          <section class="infocus-ai-preview-card" aria-label="Vista previa ${escapeHtml(type)}">
            <div class="infocus-ai-preview-card__head">
              <span class="infocus-ai-preview-card__icon"><i class="fa-solid ${icon}" aria-hidden="true"></i></span>
              <div class="infocus-ai-preview-card__title"><span class="infocus-ai-preview-card__type">${escapeHtml(type)}</span>${escapeHtml(title)}</div>
            </div>
            <div class="infocus-ai-preview-card__meta">${rowsHtml}</div>
            ${listHtml}
          </section>
        `;
      }

      function normalizeAiText(value) {
        return String(value || '')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase();
      }

      function isPersonalNoteProposal(content) {
        const text = normalizeAiText(content);
        return /actualizar nota personal|editar nota personal|reescribir nota personal|nota personal|mis notas|nueva nota/.test(text);
      }

      function isPersonalNoteUpdateProposal(content) {
        const text = normalizeAiText(content);
        return /actualizar nota personal|editar nota personal|reescribir nota personal/.test(text)
          || (/mis notas/.test(text) && /aplicar cambios|actualizar|editar|reescribir|reemplazar/.test(text));
      }

      function isStructuredEmailProposal(content) {
        const text = String(content || '');
        const normalized = normalizeAiText(text);
        const hasRecipient = aiExtractField(text, ['Para', 'Destinatario', 'Cliente']) !== '';
        const hasSubject = aiExtractField(text, ['Asunto', 'Subject']) !== '';
        const hasMessage = aiExtractField(text, ['Mensaje', 'Cuerpo', 'Body']) !== '' || /\n\s*(mensaje|cuerpo)\s*:/i.test(text);
        const proposalLanguage = /correo propuesto|email propuesto|listo para enviar|enviar correo|mensaje propuesto/.test(normalized);
        return proposalLanguage && hasRecipient && (hasSubject || hasMessage);
      }

      function cleanAiDisplayText(content) {
        const original = String(content || '')
          .replace(/<!--\s*AI_ACTIONS_JSON[\s\S]*?-->/gi, '')
          .replace(/```ai_actions[\s\S]*?```/gi, '')
          .replace(/\r\n?/g, '\n');
        const lines = original.split(/\n/);
        while (lines.length && lines[0].trim() === '') lines.shift();
        while (lines.length && lines[lines.length - 1].trim() === '') lines.pop();
        if (shouldShowCreateActions(original) && lines.length && /^\s*(?:\*\*)?\s*Siguiente paso\s*(?:\*\*)?\s*:/i.test(lines[lines.length - 1])) {
          lines.pop();
        }
        const compact = [];
        let previousWasBlank = false;
        for (const line of lines) {
          const blank = line.trim() === '';
          if (blank && previousWasBlank) continue;
          compact.push(line);
          previousWasBlank = blank;
        }
        return compact.join('\n').trim() || original.trim() || original;
      }

      function appendAiEnhancements(node, content) {
        if (!node || node.dataset.enhanced === '1') return;
        node.dataset.enhanced = '1';
        const card = buildAiPreviewCard(content);
        if (card) {
          const wrap = document.createElement('div');
          wrap.innerHTML = card;
          node.appendChild(wrap.firstElementChild);
        }
      }

      function waitAiReveal(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
      }

      async function revealAssistantMessage(node, content, options = {}) {
        if (!node) return;
        const text = String(content || '');
        const structuredActions = normalizeAiActions(options.actions || []);
        const displayText = cleanAiDisplayText(text);
        const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        const shouldAnimate = options.animate !== false && !reducedMotion;
        const lines = displayText.split(/\n/);

        node.classList.remove('thinking');
        node.classList.add('is-revealing');
        node.innerHTML = '';

        for (const line of lines) {
          const lineNode = document.createElement('span');
          lineNode.className = 'infocus-ai-line';
          if (line.trim() === '') lineNode.classList.add('is-blank');
          if (shouldAnimate) lineNode.classList.add('is-appearing');
          lineNode.innerHTML = line.trim() === '' ? '&nbsp;' : renderAiMarkdown(line);
          node.appendChild(lineNode);
          scrollAiToBottom('smooth');

          if (shouldAnimate) {
            const delay = Math.min(72, Math.max(28, line.length * 2.2));
            await waitAiReveal(delay);
          }
        }

        node.classList.remove('is-revealing');
        appendAiEnhancements(node, text);
        appendCreateActions(node, text, structuredActions);
        scrollAiToBottom('smooth');
      }

      function normalizeAiActions(actions = []) {
        const allowedTypes = new Set([
          'start_pomodoro',
          'create_project',
          'update_project',
          'add_project_task',
          'add_project_subtask',
          'add_project_note',
          'create_personal_note',
          'update_personal_note',
          'create_reminder',
          'create_meeting',
          'create_quote',
          'create_contract',
          'send_email',
          'send_recurring_invoice_early',
        ]);
        const items = Array.isArray(actions) ? actions : [];
        return items.map((action) => {
          if (!action || !allowedTypes.has(action.type)) return null;
          const minutes = Number(action.minutes);
          const normalized = {
            type: action.type,
            label: String(action.label || ''),
            fields: normalizeAiActionFields(action.fields || {}),
            requires_confirmation: action.requires_confirmation !== false,
          };
          if (action.type === 'start_pomodoro') {
            normalized.label = normalized.label || 'Activar pomodoro';
            normalized.minutes = [25, 30, 60].includes(minutes) ? minutes : 25;
            normalized.task = String(action.task || normalized.fields.task || 'Bloque de foco guiado por IA').trim().slice(0, 160) || 'Bloque de foco guiado por IA';
            normalized.open_pip = Boolean(action.open_pip || action.openPip || normalized.fields.open_pip);
          }
          return normalized;
        }).filter(Boolean);
      }

      function normalizeAiActionFields(fields, depth = 0) {
        if (!fields || typeof fields !== 'object' || Array.isArray(fields) || depth > 3) return {};
        return Object.entries(fields).reduce((acc, [key, value]) => {
          const safeKey = String(key || '').replace(/[^\w-]/g, '').slice(0, 60);
          if (!safeKey || ['type', 'requires_confirmation'].includes(safeKey)) return acc;
          if (typeof value === 'string') {
            acc[safeKey] = value.slice(0, 4000);
          } else if (typeof value === 'number' || typeof value === 'boolean' || value === null) {
            acc[safeKey] = value;
          } else if (Array.isArray(value)) {
            acc[safeKey] = value.slice(0, 40).map((item) => {
              if (typeof item === 'string') return item.slice(0, 1000);
              if (typeof item === 'number' || typeof item === 'boolean' || item === null) return item;
              if (item && typeof item === 'object' && !Array.isArray(item)) return normalizeAiActionFields(item, depth + 1);
              return '';
            }).filter((item) => item !== '');
          } else if (value && typeof value === 'object') {
            acc[safeKey] = normalizeAiActionFields(value, depth + 1);
          }
          return acc;
        }, {});
      }

      function primaryAiAction(actions = []) {
        return normalizeAiActions(actions)[0] || null;
      }

      function parseStructuredAiAction(actionsNode) {
        if (!actionsNode?.dataset?.aiAction) return null;
        try {
          return primaryAiAction([JSON.parse(actionsNode.dataset.aiAction)]);
        } catch (_) {
          return null;
        }
      }

      function shouldShowCreateActions(content, actions = []) {
        if (primaryAiAction(actions)) return true;
        const text = normalizeAiText(content);
        if (/te refieres a .*nota.* o .*proyecto|quieres crear un proyecto basado en esa nota|actualizar la nota que tienes abierta/i.test(text)) {
          return false;
        }
        const hasNonEmailAction = /factura|proyecto|tarea|subtarea|nota|recordatorio|gasto|reunion|cotizacion|contrato/.test(text);
        if (/correo|email|e-mail/.test(text) && !hasNonEmailAction && !isStructuredEmailProposal(content)) {
          return false;
        }
        if (isPomodoroProposal(content)) return true;
        const hasCreateIntent = [
          'nuevo proyecto',
          'actualizar proyecto',
          'nueva factura',
          'nueva tarea',
          'nueva subtarea',
          'nueva nota',
          'nuevo recordatorio',
          'recordatorio propuesto',
          'actualizar nota personal',
          'editar nota personal',
          'reescribir nota personal',
          'nuevo gasto',
          'nueva reunion',
          'nueva cotizacion',
          'nuevo contrato',
          'correo propuesto',
          'email propuesto',
          'listo para enviar',
          'listo para agregar',
          'listo para actualizar',
          'factura propuesta',
          'cotizacion propuesta',
          'contrato propuesto',
          'reunion propuesta',
          'proyecto propuesto',
          'tareas sugeridas',
          'gasto propuesto',
          'crear recordatorio',
          'confirmas la creacion',
          'confirmas crear',
          'puedo crear',
        ].some((needle) => text.includes(needle));
        const hasEntity = ['factura', 'proyecto', 'tarea', 'subtarea', 'nota', 'recordatorio', 'gasto', 'correo', 'email', 'e-mail', 'reunion', 'cotizacion', 'contrato'].some((needle) => text.includes(needle));
        const asksConfirmation = ['confirmas', 'crear ahora', 'enviar ahora', 'agregar ahora', 'actualizar ahora', 'antes de ejecutar', 'antes de enviarlo', 'antes de enviar', 'antes de agregar', 'antes de actualizar', 'antes de crearlo', 'antes de crear'].some((needle) => text.includes(needle));

        return hasEntity && (hasCreateIntent || asksConfirmation);
      }

      function aiActionLabels(action, content = '') {
        if (action?.type === 'start_pomodoro') {
          return { accept: 'Activar pomodoro', reject: 'No activar', busy: 'Activando...' };
        }
        const labels = {
          create_project: ['Crear proyecto', 'No crear', 'Creando...'],
          update_project: ['Aplicar cambios', 'No aplicar', 'Aplicando...'],
          add_project_task: ['Agregar tarea', 'No agregar', 'Agregando...'],
          add_project_subtask: ['Agregar subtarea', 'No agregar', 'Agregando...'],
          add_project_note: ['Agregar nota', 'No agregar', 'Agregando...'],
          create_personal_note: ['Crear nota', 'No crear', 'Creando nota...'],
          update_personal_note: ['Actualizar nota', 'No actualizar', 'Actualizando...'],
          create_reminder: ['Crear recordatorio', 'No crear', 'Creando recordatorio...'],
          create_meeting: ['Programar ahora', 'No programar', 'Programando...'],
          create_quote: ['Crear cotización', 'No crear', 'Creando cotización...'],
          create_contract: ['Crear contrato', 'No crear', 'Creando contrato...'],
          send_email: ['Enviar ahora', 'No enviar', 'Enviando...'],
          send_recurring_invoice_early: ['Enviar ahora', 'No enviar', 'Enviando...'],
        }[action?.type];
        if (labels) return { accept: labels[0], reject: labels[1], busy: labels[2] };
        return aiConfirmLabels(content);
      }

      function aiConfirmLabels(content) {
        const text = normalizeAiText(content);
        if (isPomodoroProposal(content)) {
          return { accept: 'Activar pomodoro', reject: 'No activar', busy: 'Activando...' };
        }
        if (isPersonalNoteUpdateProposal(content)) {
          return { accept: 'Actualizar nota', reject: 'No actualizar', busy: 'Actualizando...' };
        }
        if (isPersonalNoteProposal(content)) {
          return { accept: 'Crear nota', reject: 'No crear', busy: 'Creando nota...' };
        }
        if (/recordatorio|recordar/.test(text)) {
          return { accept: 'Crear recordatorio', reject: 'No crear', busy: 'Creando recordatorio...' };
        }
        const isProjectCreation = /proyecto/.test(text)
          && (/nuevo proyecto|proyecto propuesto|crear proyecto|crea proyecto|tareas sugeridas|listo para crear|crear ahora este proyecto|crear ahora el proyecto/.test(text));
        if (isProjectCreation) {
          return { accept: 'Crear ahora', reject: 'No crear', busy: 'Creando...' };
        }
        if (/correo|email|e-mail|listo para enviar|enviar ahora/.test(text)) {
          return { accept: 'Enviar ahora', reject: 'No enviar', busy: 'Enviando...' };
        }
        if (/reunion|meeting|programar ahora/.test(text)) {
          return { accept: 'Programar ahora', reject: 'No programar', busy: 'Programando...' };
        }
        if (/cotizacion/.test(text)) {
          return { accept: 'Crear cotización', reject: 'No crear', busy: 'Creando cotización...' };
        }
        if (/contrato/.test(text)) {
          return { accept: 'Crear contrato', reject: 'No crear', busy: 'Creando contrato...' };
        }
        if (/actualizar|mover|cambiar/.test(text)) {
          return { accept: 'Aplicar cambios', reject: 'No aplicar', busy: 'Aplicando...' };
        }
        if (/tablero|kanban/.test(text) && /nuevo|crear|crea|propuesto|listo para crear/.test(text)) {
          return { accept: 'Crear tablero', reject: 'No crear', busy: 'Creando tablero...' };
        }
        if (/agregar|añadir|anadir|nota|subtarea|tarea|columna|lista/.test(text) && !/nuevo proyecto|proyecto propuesto|nuevo tablero|tablero propuesto/.test(text)) {
          return { accept: 'Agregar ahora', reject: 'No agregar', busy: 'Agregando...' };
        }
        return { accept: 'Crear ahora', reject: 'No crear', busy: 'Creando...' };
      }

      function appendCreateActions(node, content, aiActions = []) {
        const structuredActions = normalizeAiActions(aiActions);
        if (!node || !shouldShowCreateActions(content, structuredActions)) return;
        const structuredAction = structuredActions[0] || null;
        const labels = structuredAction ? aiActionLabels(structuredAction, content) : aiConfirmLabels(content);
        const actionsNode = document.createElement('div');
        actionsNode.className = 'infocus-ai-confirm-actions';
        actionsNode.dataset.aiProposal = content || '';
        actionsNode.dataset.aiBusyLabel = labels.busy;
        if (structuredAction) {
          actionsNode.dataset.aiAction = JSON.stringify(structuredAction);
        }
        actionsNode.innerHTML = `
          <button type="button" class="infocus-ai-confirm-create" data-ai-confirm-create>${labels.accept}</button>
          <button type="button" class="infocus-ai-confirm-cancel" data-ai-confirm-cancel>${labels.reject}</button>
        `;
        node.appendChild(actionsNode);
        scrollAiToBottom('smooth');
      }

      function appendMessage(role, content, extraClass = '', actions = []) {
        if (welcome) welcome.style.display = 'none';
        const node = document.createElement('div');
        node.className = `infocus-ai-message ${role} ${extraClass}`.trim();
        node.innerHTML = role === 'assistant' ? renderAiMarkdown(cleanAiDisplayText(content)) : escapeHtml(content);
        if (role === 'assistant') {
          appendAiEnhancements(node, content);
          appendCreateActions(node, content, actions);
        }
        body.appendChild(node);
        scrollAiToBottom('auto');
        return node;
      }

      function appendThinkingMessage() {
        if (welcome) welcome.style.display = 'none';
        const node = document.createElement('div');
        node.className = 'infocus-ai-message assistant thinking';
        node.innerHTML = '<span class="infocus-ai-thinking"><span class="infocus-ai-thinking-robot"><i class="fa-solid fa-robot" aria-hidden="true"></i></span><span class="infocus-ai-thinking-dots"><span></span><span></span><span></span></span></span>';
        body.appendChild(node);
        scrollAiToBottom('smooth');
        return node;
      }

      function resetChat() {
        currentChatId = null;
        body.innerHTML = '';
        if (welcome) {
          welcome.style.display = '';
          body.appendChild(welcome);
        }
        menu.classList.remove('is-open');
        input.value = '';
        autosizeInput();
        input.focus();
      }

      function autosizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 128) + 'px';
      }

      function messageAsksForVisibleContext(text) {
        const normalized = normalizeAiText(text);
        return [
          'pantalla',
          'pagina actual',
          'contexto visible',
          'lo que estoy viendo',
          'lo que ves',
          'texto seleccionado',
          'seleccion actual',
          'seleccione',
          'seleccione esto',
          'seleccionado',
          'esto que seleccione',
          'esto seleccionado',
          'revisa esta pagina',
          'analiza esta pagina',
          'usa esta pagina',
          'segun esta pantalla',
          'segun lo visible',
        ].some((needle) => normalized.includes(needle));
      }

      function messageAsksForCurrentNote(text) {
        const normalized = normalizeAiText(text);
        return [
          'esta nota',
          'la nota',
          'mi nota',
          'mis notas',
          'nota personal',
          'actualizar nota',
          'reescribe',
          'reescribir',
          'redacta',
          'ordena',
          'estructura',
          'resume',
          'resumir',
          'amplia',
          'ampliar',
          'agrega ideas',
          'anade ideas',
          'mejora esta',
          'edita esta',
          'corrige esta',
        ].some((needle) => normalized.includes(needle));
      }

      function aiActionFeedback(content, ok = true) {
        if (isPersonalNoteUpdateProposal(content)) {
          return ok ? 'Nota actualizada en Mis Notas' : 'No pude actualizar la nota. Intenta de nuevo o revisa tus permisos.';
        }
        if (isPersonalNoteProposal(content)) {
          return ok ? 'Nota creada en Mis Notas' : 'No pude crear la nota. Intenta de nuevo o revisa tus permisos.';
        }
        if (/tablero|kanban|columna/.test(normalizeAiText(content))) {
          return ok ? 'Tablero actualizado en Proyectos' : 'No pude aplicar el cambio al tablero. Intenta de nuevo o revisa tus permisos.';
        }
        if (/recordatorio|recordar/.test(normalizeAiText(content))) {
          return ok ? 'Recordatorio creado en tu modal' : 'No pude crear el recordatorio. Intenta de nuevo.';
        }
        return ok ? 'Acción aplicada en el CRM' : 'No pude ejecutar eso en el CRM. Intenta de nuevo o revisa tus permisos.';
      }

      function parseProjectAiWorkingTarget(proposal = '', context = {}) {
        const currentProject = context.current_project || window.__infocusAiCurrentProject || null;
        const text = String(proposal || '');
        const normalized = normalizeAiText(text);
        const isProjectAction = /proyecto|tablero|kanban|columna|tarea|subtarea/.test(normalized);
        if (!isProjectAction) return null;

        const lineValue = (labels) => {
          for (const label of labels) {
            const pattern = new RegExp(`(?:^|\\n)\\s*(?:[-*•]\\s*)?\\**${label}\\**\\s*:\\s*([^\\n]+)`, 'i');
            const match = text.match(pattern);
            if (match?.[1]) return match[1].replace(/\*\*/g, '').trim();
          }
          return '';
        };

        return {
          projectId: currentProject?.id || '',
          projectTitle: lineValue(['Proyecto', 'Tablero']) || currentProject?.title || '',
          stage: lineValue(['Columna', 'Lista', 'Estado de tarea']) || '',
        };
      }

      function appendUndoAction(node, undoAction) {
        if (!node || !undoAction) return;
        const actions = document.createElement('div');
        actions.className = 'infocus-ai-confirm-actions';
        actions.innerHTML = '<button type="button" class="infocus-ai-confirm-create" data-ai-undo-action>Deshacer</button>';
        const button = actions.querySelector('[data-ai-undo-action]');
        button?.addEventListener('click', () => undoAiAction(button, undoAction));
        node.appendChild(actions);
        scrollAiToBottom('smooth');
      }

      async function undoAiAction(button, undoAction) {
        if (!button || button.disabled || !undoAction) return;
        button.disabled = true;
        button.textContent = 'Deshaciendo...';
        try {
          let result = null;
          if (undoAction.scope === 'local_reminder') {
            const ok = await window.__infocusAiUndoReminder?.(undoAction.id);
            if (!ok) throw new Error('local_undo_failed');
            result = { ok: true, store: 'recordatorios', id: undoAction.id, operation: 'delete' };
          } else {
            const response = await fetch(endpoints.undo, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
              },
              body: JSON.stringify({ token: undoAction.token }),
            });
            result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok) throw new Error(result.message || 'undo_failed');
          }

          button.textContent = 'Deshecho';
          button.classList.add('opacity-70');
          window.dispatchEvent(new CustomEvent('infocus-ai-undo-applied', { detail: result }));
          if (result?.store === 'proyectos' && /\/proyectos(?:$|[/?#])/.test(window.location.pathname + window.location.search)) {
            setTimeout(() => window.location.reload(), 450);
          }
          if (window.showNotification) window.showNotification('Acción deshecha', 'success');
        } catch (_) {
          button.disabled = false;
          button.textContent = 'Deshacer';
          if (window.showNotification) window.showNotification('No pude deshacer esa acción', 'error');
        }
      }

      function pageContext(messageText = '') {
        const params = new URLSearchParams(window.location.search || '');
        const currentProject = window.__infocusAiCurrentProject || null;
        const rawCurrentNote = window.__infocusAiCurrentNote || null;
        const formClientId = document.getElementById('clienteIdField')?.value || '';
        const formClientName = document.getElementById('clienteField')?.value || document.getElementById('clienteSearch')?.value || '';
        const isMisNotasPage = /\/mis-notas(?:$|[/?#])/.test(window.location.pathname + window.location.search);
        const currentNote = rawCurrentNote && (isMisNotasPage || messageAsksForCurrentNote(messageText))
          ? rawCurrentNote
          : (rawCurrentNote ? { id: rawCurrentNote.id, title: rawCurrentNote.title, permission: rawCurrentNote.permission } : null);
        const context = {
          page: document.title || '',
          url: window.location.href,
          current_project: currentProject || (
            params.get('open_project')
              ? { id: params.get('open_project'), title: '' }
              : null
          ),
          current_note: currentNote,
          current_client: (formClientId || formClientName) ? { id: formClientId, name: formClientName } : null,
          task_id: params.get('open_task') || '',
        };

        if (messageAsksForVisibleContext(messageText)) {
          context.selection = String(window.getSelection?.() || '').slice(0, 1800);
        }

        return context;
      }

      function isPomodoroProposal(content) {
        const text = String(content || '')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase();
        return /pomodoro|bloque de foco|trabajo enfocado|tdah/.test(text)
          && /activar|iniciar|empezar|propuesto|listo para activar|activar pomodoro/.test(text);
      }

      function parsePomodoroProposal(content) {
        const text = String(content || '');
        const normalized = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const minutesMatch = normalized.match(/\b(25|30|60)\s*(min|mins|minutos)?\b/);
        const taskMatch = text.match(/(?:Tarea|Enfoque|Objetivo|Bloque)\s*:\s*(.+)/i);

        return {
          minutes: minutesMatch ? Number(minutesMatch[1]) : 25,
          task: taskMatch ? taskMatch[1].replace(/\*\*/g, '').trim() : 'Bloque de foco guiado por IA',
          openPip: /\bpip\b|mini/i.test(text),
        };
      }

      async function executeLocalPomodoroAction(actionsNode) {
        const proposal = actionsNode?.dataset.aiProposal || '';
        if (!window.startTdahPomodoroFromAi) {
          throw new Error('tdah_pomodoro_not_ready');
        }

        const options = parsePomodoroProposal(proposal);
        const result = await window.startTdahPomodoroFromAi(options);
        actionsNode.remove();
        appendMessage('assistant', result?.ok
          ? `✅ **Pomodoro TDAH activado**\n\n- **Bloque:** ${options.minutes} minutos\n- **Enfoque:** ${options.task}`
          : 'No pude activar el Pomodoro TDAH. Revisa que haya una tarea o intenta de nuevo.'
        );
      }

      async function executeStructuredAiAction(actionsNode) {
        const action = parseStructuredAiAction(actionsNode);
        if (!action) return false;

        if (action.type === 'start_pomodoro') {
          if (!window.startTdahPomodoroFromAi) {
            throw new Error('tdah_pomodoro_not_ready');
          }

          const options = {
            minutes: action.minutes,
            task: action.task,
            openPip: action.open_pip,
          };
          const result = await window.startTdahPomodoroFromAi(options);
          actionsNode.remove();
          appendMessage('assistant', result?.ok
            ? `✅ **Pomodoro TDAH activado**\n\n- **Bloque:** ${options.minutes} minutos\n- **Enfoque:** ${options.task}`
            : 'No pude activar el Pomodoro TDAH. Revisa que haya una tarea o intenta de nuevo.'
          );
          return true;
        }

        return false;
      }

      function aiActionField(action, keys, fallback = '') {
        const fields = action?.fields || {};
        for (const key of keys) {
          const value = fields[key];
          if (value !== undefined && value !== null && String(value).trim() !== '') return value;
        }
        return fallback;
      }

      function aiActionLine(label, value) {
        if (value === undefined || value === null || String(value).trim() === '') return '';
        return `${label}: ${String(value).trim()}`;
      }

      function aiActionList(label, value, formatter = null) {
        const items = Array.isArray(value) ? value : (String(value || '').trim() ? [value] : []);
        if (!items.length) return '';
        const lines = items.map((item, index) => {
          if (formatter) return formatter(item, index);
          if (item && typeof item === 'object') {
            return `${index + 1}. ${String(item.title || item.name || item.text || item.task || item.description || '').trim()}`;
          }
          return `${index + 1}. ${String(item).trim()}`;
        }).filter((line) => String(line).trim() !== '');
        return lines.length ? `${label}:\n${lines.join('\n')}` : '';
      }

      function proposalFromStructuredAiAction(action, fallbackProposal = '') {
        const f = action?.fields || {};
        const title = aiActionField(action, ['title', 'name', 'text', 'task', 'project']);
        const parts = [];
        switch (action?.type) {
          case 'create_project':
            parts.push('Nuevo proyecto:');
            parts.push(aiActionLine('Nombre', aiActionField(action, ['title', 'name', 'project'])));
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'], 'Sin Cliente')));
            parts.push(aiActionLine('Estado', aiActionField(action, ['stage', 'status'])));
            parts.push(aiActionLine('Prioridad', aiActionField(action, ['priority'], 'Con calma')));
            parts.push(aiActionLine('Fecha inicio', aiActionField(action, ['start_date'])));
            parts.push(aiActionLine('Vencimiento', aiActionField(action, ['due_date', 'date'])));
            parts.push(aiActionLine('Responsables', aiActionField(action, ['responsible', 'responsibles'])));
            parts.push(aiActionLine('Descripción', aiActionField(action, ['description', 'content'])));
            parts.push(aiActionList('Columnas', f.columns, (item) => `- ${String(item).trim()}`));
            parts.push(aiActionList('Tareas sugeridas', f.tasks, (item, index) => {
              if (item && typeof item === 'object') {
                const taskTitle = String(item.title || item.name || item.text || item.task || '').trim();
                const stage = String(item.column || item.stage || '').trim();
                const subtasks = Array.isArray(item.subtasks) && item.subtasks.length
                  ? `\n   - Subtareas a agregar:\n${item.subtasks.map((subtask) => `     - ${String(subtask).trim()}`).join('\n')}`
                  : '';
                const description = String(item.description || '').trim();
                return `${index + 1}. ${stage ? `${stage}: ` : ''}${taskTitle}${description ? `\n   - Descripción: ${description}` : ''}${subtasks}`;
              }
              return `${index + 1}. ${String(item).trim()}`;
            }));
            break;
          case 'update_project':
            parts.push('Actualizar proyecto:');
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project', 'title', 'name'])));
            parts.push(aiActionLine('Descripción', aiActionField(action, ['description', 'content'])));
            parts.push(aiActionLine('Estado', aiActionField(action, ['stage', 'status'])));
            parts.push(aiActionLine('Prioridad', aiActionField(action, ['priority'])));
            parts.push(aiActionLine('Vencimiento', aiActionField(action, ['due_date', 'date'])));
            parts.push(aiActionLine('Responsables', aiActionField(action, ['responsible', 'responsibles'])));
            parts.push(aiActionList('Columnas', f.columns, (item) => `- ${String(item).trim()}`));
            parts.push(aiActionList('Tareas a agregar', f.tasks, (item, index) => `${index + 1}. ${String(item?.title || item?.name || item?.text || item).trim()}`));
            parts.push(aiActionList('Subtareas a agregar', f.subtasks, (item) => `- ${String(item).trim()}`));
            break;
          case 'add_project_task':
            parts.push('Nueva tarea:');
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project'])));
            parts.push(aiActionLine('Columna', aiActionField(action, ['column', 'stage'])));
            parts.push(aiActionLine('Tarea a agregar', title));
            parts.push(aiActionLine('Descripción', aiActionField(action, ['description', 'content'])));
            parts.push(aiActionLine('Prioridad', aiActionField(action, ['priority'])));
            parts.push(aiActionLine('Vencimiento', aiActionField(action, ['due_date', 'date'])));
            parts.push(aiActionLine('Responsables', aiActionField(action, ['responsible', 'responsibles'])));
            parts.push(aiActionList('Subtareas a agregar', f.subtasks, (item) => `- ${String(item).trim()}`));
            break;
          case 'add_project_subtask':
            parts.push('Nueva subtarea:');
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project'])));
            parts.push(aiActionLine('Tarea', aiActionField(action, ['task'])));
            parts.push(aiActionList('Subtareas a agregar', f.subtasks || [title], (item) => `- ${String(item).trim()}`));
            break;
          case 'add_project_note':
            parts.push('Nota de proyecto:');
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project'])));
            parts.push(aiActionLine('Tarea', aiActionField(action, ['task'])));
            parts.push(aiActionLine('Nota', aiActionField(action, ['note', 'content', 'text', 'description'])));
            break;
          case 'create_personal_note':
            parts.push('Nota personal:');
            parts.push(aiActionLine('Título', aiActionField(action, ['title', 'name'])));
            parts.push(aiActionLine('Contenido', aiActionField(action, ['content', 'text', 'description'])));
            parts.push(aiActionLine('Color', aiActionField(action, ['color'])));
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'])));
            break;
          case 'update_personal_note':
            parts.push('Actualizar nota personal:');
            parts.push(aiActionLine('Nota ID', aiActionField(action, ['note_id', 'id'])));
            parts.push(aiActionLine('Título', aiActionField(action, ['title', 'name'])));
            parts.push(aiActionLine('Contenido', aiActionField(action, ['content', 'text', 'description'])));
            break;
          case 'create_reminder':
            parts.push('Recordatorio propuesto:');
            parts.push(aiActionLine('Texto', aiActionField(action, ['text', 'title', 'description'])));
            parts.push(aiActionLine('Prioridad', aiActionField(action, ['priority'])));
            parts.push(aiActionLine('Fecha', aiActionField(action, ['date', 'due_date'])));
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project'])));
            parts.push(aiActionLine('Tarea', aiActionField(action, ['task'])));
            parts.push(aiActionLine('Lista', aiActionField(action, ['list', 'category'])));
            break;
          case 'create_meeting':
            parts.push('Reunión propuesta:');
            parts.push(aiActionLine('Título', title));
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'])));
            parts.push(aiActionLine('Fecha', aiActionField(action, ['date'])));
            parts.push(aiActionLine('Hora inicio', aiActionField(action, ['start_time', 'time'])));
            parts.push(aiActionLine('Hora fin', aiActionField(action, ['end_time'])));
            parts.push(aiActionLine('Ubicación', aiActionField(action, ['location'])));
            parts.push(aiActionLine('Responsables', aiActionField(action, ['responsible', 'responsibles'])));
            parts.push(aiActionLine('Invitados', aiActionField(action, ['recipients', 'invitees', 'emails'])));
            parts.push(aiActionLine('Notas', aiActionField(action, ['notes', 'description', 'content'])));
            break;
          case 'create_quote':
            parts.push('Cotización propuesta:');
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'])));
            parts.push(aiActionLine('Moneda', aiActionField(action, ['currency'])));
            parts.push(aiActionLine('Vencimiento', aiActionField(action, ['due_date', 'date'])));
            parts.push(aiActionLine('Estado', aiActionField(action, ['status'])));
            parts.push(aiActionList('Items', f.items, (item) => {
              if (item && typeof item === 'object') {
                return `- ${String(item.name || item.description || item.service || '').trim()} - ${item.quantity || 1} x ${item.price || item.amount || 0}`;
              }
              return `- ${String(item).trim()}`;
            }));
            break;
          case 'create_contract':
            parts.push('Contrato propuesto:');
            parts.push(aiActionLine('Contrato', title));
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'])));
            parts.push(aiActionLine('Proyecto', aiActionField(action, ['project'])));
            parts.push(aiActionLine('Monto', aiActionField(action, ['amount'])));
            parts.push(aiActionLine('Moneda', aiActionField(action, ['currency'])));
            parts.push(aiActionLine('Estado', aiActionField(action, ['status'])));
            parts.push(aiActionLine('Contenido', aiActionField(action, ['content', 'message', 'description'])));
            break;
          case 'send_email':
            parts.push('Correo propuesto:');
            parts.push(aiActionLine('Para', aiActionField(action, ['to', 'recipient', 'recipients'])));
            parts.push(aiActionLine('Asunto', aiActionField(action, ['subject'])));
            parts.push(aiActionLine('Mensaje', aiActionField(action, ['message', 'body', 'content'])));
            break;
          case 'send_recurring_invoice_early':
            parts.push('Factura recurrente adelantada:');
            parts.push(aiActionLine('Factura', aiActionField(action, ['invoice', 'invoice_number'])));
            parts.push(aiActionLine('Cliente', aiActionField(action, ['client'])));
            parts.push(aiActionLine('Fecha de emisión original', aiActionField(action, ['issue_date', 'original_issue_date'])));
            parts.push(aiActionLine('Vencimiento original', aiActionField(action, ['due_date', 'original_due_date'])));
            parts.push('Acción: Enviar hoy');
            break;
          default:
            return fallbackProposal;
        }

        const proposal = parts.filter((line) => String(line || '').trim() !== '').join('\n');
        return proposal || fallbackProposal;
      }

      async function loadHistory() {
        try {
          const response = await fetch(endpoints.index, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json();
          const items = Array.isArray(json.items) ? json.items : [];
          if (!items.length) {
            historyEl.innerHTML = '<div class="px-3 py-3 text-sm text-slate-400">Sin chats todavía</div>';
            renderRecentChats([]);
            return;
          }
          historyEl.innerHTML = items.map((item) => `
            <div class="infocus-ai-history-row">
              <button type="button" data-chat-id="${escapeHtml(item.id)}" class="infocus-ai-history-item">
                <i class="fa-solid fa-robot w-5 shrink-0 text-center text-slate-500" aria-hidden="true"></i>
                <span class="truncate">${escapeHtml(item.title || 'Nuevo chat')}</span>
              </button>
              <button type="button" data-delete-chat-id="${escapeHtml(item.id)}" class="infocus-ai-history-delete" title="Borrar chat" aria-label="Borrar chat">
                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
              </button>
            </div>
          `).join('');
          renderRecentChats(items.slice(0, 2));
        } catch (_) {}
      }

      function renderRecentChats(items) {
        if (!recentChatsEl) return;
        if (!Array.isArray(items) || !items.length) {
          recentChatsEl.innerHTML = '<div class="infocus-ai-recent-empty">Sin chats todavía</div>';
          return;
        }

        recentChatsEl.innerHTML = items.slice(0, 2).map((item) => `
          <button type="button" class="infocus-ai-recent-chat" data-chat-id="${escapeHtml(item.id)}" title="${escapeHtml(item.title || 'Nuevo chat')}">
            <i class="fa-solid fa-robot" aria-hidden="true"></i>
            <span>${escapeHtml(item.title || 'Nuevo chat')}</span>
          </button>
        `).join('');
      }

      async function deleteChat(id) {
        if (!id) return;
        try {
          const response = await fetch(`${endpoints.deleteBase}/${encodeURIComponent(id)}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          if (!response.ok) throw new Error('Error');
          if (currentChatId === id) resetChat();
          loadHistory();
        } catch (_) {
          if (window.showNotification) window.showNotification('No pude borrar ese chat', 'error');
        }
      }

      async function openChat(id) {
        try {
          const response = await fetch(`${endpoints.showBase}/${encodeURIComponent(id)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await response.json();
          const item = json.item || {};
          currentChatId = item.id || id;
          body.innerHTML = '';
          (Array.isArray(item.messages) ? item.messages : []).forEach((message) => {
            appendMessage(
              message.role === 'user' ? 'user' : 'assistant',
              message.content || '',
              '',
              message.role === 'assistant' ? (message.actions || []) : []
            );
          });
          if (!restoreAiScrollPosition()) {
            scrollAiToBottom('auto');
          }
          menu.classList.remove('is-open');
          input.focus();
        } catch (_) {
          if (window.showNotification) window.showNotification('No pude abrir ese chat', 'error');
        }
      }

      async function sendMessage(text) {
        if (sending || !text.trim()) return;
        sending = true;
        sendBtn.disabled = true;
        appendMessage('user', text.trim());
        input.value = '';
        autosizeInput();
        const thinking = appendThinkingMessage();

        try {
          const response = await fetch(endpoints.chat, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              chat_id: currentChatId,
              message: text.trim(),
              context: pageContext(text),
            }),
          });
          const json = await response.json();
          if (!response.ok) throw new Error(json.message || 'Error');
          currentChatId = json.chat_id || currentChatId;
          await revealAssistantMessage(thinking, json.message?.content || 'No recibí respuesta.', {
            actions: json.message?.actions || [],
          });
          loadHistory();
        } catch (error) {
          thinking.classList.remove('thinking');
          thinking.textContent = 'No pude enviar el mensaje. Revisa la configuración de IA o intenta otra vez.';
          scrollAiToBottom('smooth');
        } finally {
          sending = false;
          sendBtn.disabled = false;
          input.focus();
        }
      }

      async function executeConfirmedAction(actionsNode) {
        if (!actionsNode || sending) return;
        let proposal = actionsNode.dataset.aiProposal || '';
        const structuredAction = parseStructuredAiAction(actionsNode);
        sending = true;
        sendBtn.disabled = true;

        const buttons = actionsNode.querySelectorAll('button');
        buttons.forEach((button) => button.disabled = true);
        const createButton = actionsNode.querySelector('[data-ai-confirm-create]');
        if (createButton) createButton.textContent = actionsNode.dataset.aiBusyLabel || 'Procesando...';

        if (structuredAction?.type === 'start_pomodoro') {
          try {
            const handled = await executeStructuredAiAction(actionsNode);
            if (!handled) throw new Error('unsupported_ai_action');
          } catch (_) {
            appendMessage('assistant', 'No pude activar el Pomodoro TDAH. Intenta de nuevo.');
            buttons.forEach((button) => button.disabled = false);
            if (createButton) createButton.textContent = aiActionLabels(structuredAction, proposal).accept;
          } finally {
            sending = false;
            sendBtn.disabled = false;
            input.focus();
          }
          return;
        }

        if (structuredAction) {
          proposal = proposalFromStructuredAiAction(structuredAction, proposal);
        }

        if (isPomodoroProposal(proposal)) {
          try {
            await executeLocalPomodoroAction(actionsNode);
          } catch (_) {
            appendMessage('assistant', 'No pude activar el Pomodoro TDAH. Intenta de nuevo.');
            buttons.forEach((button) => button.disabled = false);
            if (createButton) createButton.textContent = aiConfirmLabels(proposal).accept;
          } finally {
            sending = false;
            sendBtn.disabled = false;
            input.focus();
          }
          return;
        }

        const thinking = appendThinkingMessage();
        const actionContext = pageContext(proposal);
        const confirmLabels = aiConfirmLabels(proposal);
        const isNoteAction = !!actionContext.current_note?.id && /nota personal|mis notas|actualizar nota|editar nota|reescribir nota/i.test(proposal);
        const projectWorkingTarget = parseProjectAiWorkingTarget(proposal, actionContext);
        if (actionContext.current_note?.id && confirmLabels.accept === 'Actualizar nota') {
          actionContext.forced_intent = 'note_update';
        }
        if (isNoteAction) {
          window.dispatchEvent(new CustomEvent('infocus-ai-note-working', {
            detail: { id: actionContext.current_note.id, working: true },
          }));
        }
        if (projectWorkingTarget) {
          window.dispatchEvent(new CustomEvent('infocus-ai-project-working', {
            detail: { ...projectWorkingTarget, working: true },
          }));
        }
        try {
          const response = await fetch(endpoints.execute, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
              chat_id: currentChatId,
              proposal,
              context: actionContext,
            }),
          });
          const json = await response.json().catch(() => ({}));
          currentChatId = json.chat_id || currentChatId;
          await revealAssistantMessage(thinking, json.message?.content || 'No pude ejecutar esa acción.', {
            actions: json.message?.actions || [],
          });
          let undoAction = json.undo_action || null;
          if (json.ok && json.note_update && window.__infocusAiApplyNoteUpdate) {
            await window.__infocusAiApplyNoteUpdate(json.note_update);
          } else if (json.ok && json.reminder_action && window.__infocusAiCreateReminder) {
            const reminderResult = await window.__infocusAiCreateReminder(json.reminder_action);
            if (reminderResult?.id) {
              undoAction = { scope: 'local_reminder', id: reminderResult.id };
            }
          } else if (json.ok && actionContext.forced_intent === 'note_update') {
            throw new Error('La acción no devolvió la actualización de la nota abierta.');
          }
          if (json.ok && (json.project_item || json.project_action)) {
            window.dispatchEvent(new CustomEvent('infocus-ai-project-updated', {
              detail: {
                project: json.project_item || null,
                action: json.project_action || null,
                fallback: projectWorkingTarget,
              },
            }));
          }
          if (json.ok && undoAction) {
            appendUndoAction(thinking, undoAction);
          }
          actionsNode.remove();
          loadHistory();
          if (json.ok && window.showNotification) {
            window.showNotification(aiActionFeedback(proposal, true), 'success');
          }
        } catch (_) {
          thinking.classList.remove('thinking');
          thinking.textContent = aiActionFeedback(proposal, false);
          scrollAiToBottom('smooth');
          buttons.forEach((button) => button.disabled = false);
          if (createButton) createButton.textContent = aiConfirmLabels(proposal).accept;
        } finally {
          if (isNoteAction) {
            window.dispatchEvent(new CustomEvent('infocus-ai-note-working', {
              detail: { id: actionContext.current_note.id, working: false },
            }));
          }
          if (projectWorkingTarget) {
            window.dispatchEvent(new CustomEvent('infocus-ai-project-working', {
              detail: { ...projectWorkingTarget, working: false },
            }));
          }
          sending = false;
          sendBtn.disabled = false;
          input.focus();
        }
      }

      openBtn?.addEventListener('click', openShell);
      closeBtn?.addEventListener('click', closeShell);
      document.getElementById('infocusAiNew')?.addEventListener('click', resetChat);
      document.getElementById('infocusAiNewTop')?.addEventListener('click', resetChat);
      menuToggle?.addEventListener('click', () => menu.classList.toggle('is-open'));
      historyEl?.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-delete-chat-id]');
        if (deleteButton) {
          event.preventDefault();
          event.stopPropagation();
          deleteChat(deleteButton.dataset.deleteChatId || '');
          return;
        }
        const button = event.target.closest('[data-chat-id]');
        if (button) openChat(button.dataset.chatId);
      });
      recentChatsEl?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-chat-id]');
        if (button) openChat(button.dataset.chatId);
      });
      form?.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
      });
      input?.addEventListener('input', autosizeInput);
      input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          sendMessage(input.value);
        }
      });
      body?.addEventListener('click', (event) => {
        const createButton = event.target.closest('[data-ai-confirm-create]');
        if (createButton) {
          executeConfirmedAction(createButton.closest('.infocus-ai-confirm-actions'));
          return;
        }
        const cancelButton = event.target.closest('[data-ai-confirm-cancel]');
        if (cancelButton) {
          const actions = cancelButton.closest('.infocus-ai-confirm-actions');
          const structuredAction = parseStructuredAiAction(actions);
          const labels = structuredAction
            ? aiActionLabels(structuredAction, actions?.dataset.aiProposal || '')
            : aiConfirmLabels(actions?.dataset.aiProposal || '');
          actions?.remove();
          sendMessage(`${labels.reject} por ahora. Quiero ajustar los datos antes de continuar.`);
          return;
        }
        const suggestion = event.target.closest('[data-ai-suggestion]');
        if (suggestion) sendMessage(suggestion.dataset.aiSuggestion || '');
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('is-open')) {
          closeShell();
        }
      });
      document.addEventListener('pointerdown', (event) => {
        if (!shell.classList.contains('is-open')) return;
        const target = event.target;
        if (shell.contains(target) || openBtn?.contains(target)) return;
        closeShell();
      });
    })();
  </script>

  <!-- TDAH Toolkit -->
  @include('partials.tdah-toolbar')

  <!-- Global Toast Notification Container -->
  <div id="toast-container" class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[10000] flex flex-col gap-3 w-full max-w-sm pointer-events-none px-4"></div>

  <script>
    /**
     * Global Notification System
     * Replaces standard alerts with beautiful top-down toasts
     */
    window.showNotification = function(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        // Icons
        const icons = {
            success: `<svg class="w-6 h-6 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            error: `<svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            info: `<svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
            warning: `<svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`
        };

        const bgColors = {
            success: 'bg-white border-lime-200',
            error: 'bg-white border-red-100',
            info: 'bg-white border-blue-100',
            warning: 'bg-white border-orange-100'
        };

        // Create Element
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-3 p-4 rounded-2xl shadow-2xl border ${bgColors[type] || bgColors.info} toast-enter relative overflow-hidden backdrop-blur-sm bg-opacity-95`;
        
        toast.innerHTML = `
            <div class="flex-shrink-0 bg-slate-50 p-2 rounded-full">
                ${icons[type] || icons.info}
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-slate-800 leading-tight">${message}</p>
            </div>
            <button class="text-slate-400 hover:text-slate-600 transition-colors p-1" onclick="this.parentElement.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        container.appendChild(toast);

        // Auto Remove
        setTimeout(() => {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-leave');
            toast.addEventListener('animationend', () => toast.remove());
        }, duration);
    };

    // Override Default Alert
    window.originalAlert = window.alert;
    window.alert = function(message) {
        // Detect type based on keywords (simple heuristic)
        let type = 'info';
        const lowerMsg = String(message).toLowerCase();
        if (lowerMsg.includes('error') || lowerMsg.includes('fallo') || lowerMsg.includes('incorrecto')) type = 'error';
        else if (lowerMsg.includes('exito') || lowerMsg.includes('correctamente') || lowerMsg.includes('guardado') || lowerMsg.includes('añadida')) type = 'success';
        else if (lowerMsg.includes('advertencia') || lowerMsg.includes('cuidado')) type = 'warning';
        
        window.showNotification(message, type);
    };

    // Check for Laravel Flash Messages
    document.addEventListener('DOMContentLoaded', () => {
        @if(session('success'))
            window.showNotification("{{ session('success') }}", 'success');
        @endif

        @if(session('error'))
            window.showNotification("{{ session('error') }}", 'error');
        @endif

        @if(session('warning'))
            window.showNotification("{{ session('warning') }}", 'warning');
        @endif

        @if(session('info'))
            window.showNotification("{{ session('info') }}", 'info');
        @endif
        
        // Show generic validation toast only after a real form submit/redirect.
        @if($errors->any() && session()->hasOldInput())
            window.showNotification("Por favor revisa los errores en el formulario.", 'error');
        @endif
    });
  </script>
</body>
</html>
