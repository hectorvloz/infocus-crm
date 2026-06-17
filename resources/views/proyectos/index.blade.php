@extends('layouts.app')
@section('title','Proyectos')
@section('content')
  <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
  <style>
    #global-header-back-btn {
      display: none !important;
    }

    #newProjectModal .ts-dropdown,
    #projectModal .ts-dropdown,
    #taskDetailModal .ts-dropdown,
    #timerTaskModal .ts-dropdown,
    #quickProjectActionModal .ts-dropdown {
      z-index: 1200 !important;
    }

    #newProjectModal .flatpickr-calendar,
    #projectModal .flatpickr-calendar,
    #taskDetailModal .flatpickr-calendar,
    #timerTaskModal .flatpickr-calendar,
    #quickProjectActionModal .flatpickr-calendar {
      z-index: 2147483000 !important;
    }

    #kanbanScroll,
    #kanban,
    #kanban .stage-column,
    #kanban .drag-container {
      cursor: grab;
    }

    #kanbanScroll.is-grabbing {
      cursor: grabbing;
    }

    #kanbanScroll.is-grabbing * {
      cursor: grabbing !important;
      user-select: none;
    }

    .project-desc-shell {
      position: relative;
    }

    .project-desc-shell .compact-rich-editor {
      min-height: 13rem;
      overflow: visible;
      transition: height .18s ease, box-shadow .18s ease;
    }

    .compact-rich-editor-shell {
      position: relative;
      border-radius: .9rem;
      border: 1px solid #e2e8f0;
      background: rgba(248, 250, 252, .6);
      box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
      transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
      overflow: visible;
    }

    .compact-rich-editor-shell:focus-within,
    .compact-rich-editor-shell.is-active {
      border-color: #d9f99d;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(236, 254, 136, .55);
    }

    .compact-desc-toolbar {
      display: none;
      align-items: center;
      gap: .25rem;
      flex-wrap: nowrap;
      overflow: visible;
      white-space: nowrap;
      border-bottom: 1px solid #e2e8f0;
      padding: .35rem;
      background: rgba(241, 245, 249, .8);
      border-radius: .85rem .85rem 0 0;
      scrollbar-width: none;
    }

    .compact-desc-toolbar::-webkit-scrollbar {
      display: none;
    }

    .compact-rich-editor-shell.is-active .compact-desc-toolbar {
      display: flex;
    }

    .compact-desc-toolbar > * {
      flex: 0 0 auto;
    }

    .compact-desc-tool,
    .compact-desc-format-trigger {
      height: 1.85rem;
      border-radius: .6rem;
      border: 1px solid transparent;
      background: transparent;
      color: #334155;
      font-size: .72rem;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .compact-desc-tool {
      min-width: 1.85rem;
      padding: 0 .45rem;
    }

    .compact-desc-format-wrap {
      position: relative;
      flex: 0 0 auto;
    }

    .compact-desc-format-trigger {
      width: 6.8rem !important;
      min-width: 6.8rem;
      max-width: 6.8rem;
      flex: 0 0 6.8rem;
      background: #fff;
      border-color: #dbe5f2;
      padding: 0 .45rem;
      outline: none;
      justify-content: space-between;
      gap: .35rem;
    }

    .compact-desc-format-menu {
      position: absolute;
      left: 0;
      top: calc(100% + .35rem);
      z-index: 2147482600;
      width: 8.6rem;
      border-radius: .85rem;
      border: 1px solid rgba(15, 23, 42, .10);
      background: #111827;
      padding: .25rem;
      box-shadow: 0 18px 38px rgba(15, 23, 42, .24);
    }

    .compact-desc-format-menu.hidden {
      display: none !important;
    }

    .compact-desc-format-option {
      width: 100%;
      height: 1.85rem;
      border-radius: .55rem;
      display: flex;
      align-items: center;
      gap: .4rem;
      padding: 0 .55rem;
      color: #f8fafc;
      font-size: .78rem;
      font-weight: 800;
      text-align: left;
    }

    .compact-desc-format-option:hover {
      background: rgba(255, 255, 255, .12);
    }

    .compact-desc-format-option.is-selected {
      background: #d9f99d;
      color: #111827;
    }

    .compact-desc-tool:hover {
      background: #fff;
      border-color: #dbe5f2;
      color: #0f172a;
    }

    .compact-desc-tool.is-active {
      background: rgba(17, 24, 39, .12);
      border-color: rgba(17, 24, 39, .22);
      color: #111827;
    }

    .compact-desc-tool.is-highlight.is-active {
      background: #fef9c3;
      color: #713f12;
    }

    .compact-rich-editor {
      width: 100%;
      min-height: 13rem;
      max-height: 26rem;
      padding: .85rem 1rem;
      color: #1e293b;
      font-size: 1rem;
      line-height: 1.65;
      outline: none;
      resize: vertical;
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
      text-transform: none;
      letter-spacing: 0;
    }

    .compact-rich-editor * {
      text-transform: none !important;
      letter-spacing: 0 !important;
    }

    .compact-rich-editor::-webkit-scrollbar {
      width: .45rem;
    }

    .compact-rich-editor::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }

    .compact-desc-tool iconify-icon {
      color: currentColor;
      display: block;
      pointer-events: none;
    }

    .compact-rich-editor:empty::before {
      content: attr(data-placeholder);
      color: #94a3b8;
      pointer-events: none;
    }

    .compact-rich-editor h1,
    .compact-rich-editor h2,
    .compact-rich-editor h3 {
      margin: 0 0 .35rem;
      color: #0f172a;
      line-height: 1.25;
      font-weight: 900;
    }

    .compact-rich-editor h1 { font-size: 1.35rem; }
    .compact-rich-editor h2 { font-size: 1.08rem; }
    .compact-rich-editor p,
    .compact-rich-editor div,
    .compact-rich-editor li {
      margin: 0 0 .3rem;
      font-size: 1em;
      font-weight: 400;
    }

    .compact-rich-editor ul,
    .compact-rich-editor ol {
      margin: .25rem 0 .35rem 1.2rem;
      padding: 0;
    }

    .compact-rich-editor hr {
      margin: .65rem 0;
      border: 0;
      border-top: 1px solid #e2e8f0;
    }

    .compact-rich-editor mark {
      border-radius: .25rem;
      background: #fef08a;
      padding: 0 .12rem;
    }

    .compact-rich-editor .note-checkline {
      display: flex;
      align-items: flex-start;
      gap: .55rem;
      margin: 0 0 .35rem;
    }

    .compact-rich-editor .note-checkline span {
      min-width: 1ch;
      outline: none;
    }

    .compact-rich-editor .note-checkline.is-checked span {
      color: #64748b;
      text-decoration: line-through;
    }

    .compact-rich-editor .note-checkbox {
      appearance: none;
      width: 1.05rem;
      height: 1.05rem;
      flex: 0 0 auto;
      margin-top: .25rem;
      border-radius: .35rem;
      border: 2px solid #cbd5e1;
      background: #fff;
      cursor: pointer;
      display: inline-grid;
      place-items: center;
    }

    .compact-rich-editor .note-checkbox:checked {
      background: #d9f99d;
      border-color: #a3e635;
    }

    .compact-rich-editor .note-checkbox:checked::after {
      content: "✓";
      color: #111827;
      font-size: .78rem;
      font-weight: 900;
      line-height: 1;
    }

    .compact-rich-editor .note-numberline {
      display: flex;
      align-items: flex-start;
      gap: .55rem;
      margin: 0 0 .35rem;
    }

    .compact-rich-editor .note-number-marker {
      min-width: 1.2rem;
      color: #64748b;
      font-size: .82rem;
      font-weight: 900;
      line-height: 1.65;
    }

    .compact-rich-editor .note-number-content {
      min-width: 1ch;
      outline: none;
    }

    #taskSubtasksList {
      position: relative;
    }

    #taskSubtasksList.is-ai-working {
      border-radius: 1rem;
    }

    #taskSubtasksList.is-ai-working::before {
      content: "";
      position: absolute;
      inset: -.45rem;
      z-index: 3;
      pointer-events: none;
      border-radius: 1rem;
      border: 2px solid rgba(217, 70, 239, .62);
      background:
        radial-gradient(circle at 16% 18%, rgba(232, 121, 249, .26), transparent 30%),
        linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, .78) 45%, transparent 68%);
      background-size: 100% 100%, 220% 100%;
      box-shadow: 0 14px 34px rgba(217, 70, 239, .16);
      animation: taskChecklistAiGlow .95s ease-in-out infinite;
    }

    #taskSubtasksList.is-ai-working::after {
      content: "IA ajustando checklist";
      position: absolute;
      right: .45rem;
      top: -.9rem;
      z-index: 4;
      pointer-events: none;
      border-radius: 999px;
      background: #fff;
      border: 1px solid rgba(217, 70, 239, .28);
      color: #86198f;
      font-size: .65rem;
      font-weight: 900;
      letter-spacing: .02em;
      padding: .22rem .5rem;
      box-shadow: 0 10px 22px rgba(15, 23, 42, .10);
    }

    .task-subtask-ai-enter {
      animation: taskSubtaskAiEnter .48s cubic-bezier(.2, .9, .2, 1) both;
    }

    @keyframes taskChecklistAiGlow {
      0% { background-position: 0 0, 180% 0; opacity: .72; }
      50% { opacity: 1; }
      100% { background-position: 0 0, -70% 0; opacity: .72; }
    }

    @keyframes taskSubtaskAiEnter {
      from {
        opacity: 0;
        transform: translateY(10px) scale(.985);
        background: rgba(250, 232, 255, .75);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
        background: transparent;
      }
    }

    .project-desc-shell.is-collapsed.has-overflow::after {
      content: "";
      pointer-events: none;
      position: absolute;
      left: 1px;
      right: 1px;
      bottom: 2.35rem;
      height: 5.5rem;
      border-radius: 0 0 .75rem .75rem;
      background: linear-gradient(to bottom, rgba(248, 250, 252, 0), rgba(248, 250, 252, .92) 62%, rgba(255, 255, 255, 1));
    }

    .project-desc-toggle {
      display: none;
      margin-top: .45rem;
      width: fit-content;
      align-items: center;
      gap: .45rem;
      border-radius: .45rem;
      background: #f1f5f9;
      padding: .35rem .65rem;
      font-size: .75rem;
      font-weight: 800;
      color: #475569;
      transition: background-color .15s ease, color .15s ease;
    }

    .project-desc-toggle:hover {
      background: #e2e8f0;
      color: #1e293b;
    }

    .project-desc-toggle svg {
      height: .95rem;
      width: .95rem;
      transition: transform .15s ease;
    }

    .project-desc-shell.has-overflow .project-desc-toggle {
      display: inline-flex;
    }

    .project-desc-shell.toggle-dismissed .project-desc-toggle {
      display: none;
    }

    .project-file-grid {
      display: grid;
      gap: .55rem;
    }

    .project-file-card {
      position: relative;
      display: grid;
      grid-template-columns: 4.45rem minmax(0, 1fr) auto;
      align-items: center;
      gap: .9rem;
      border-radius: .9rem;
      padding: .45rem .55rem;
      background: #fff;
      border: 1px solid #e2e8f0;
      transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
      --file-color: #4f46e5;
    }

    .project-file-card:hover {
      transform: translateY(-1px);
      border-color: #d9ff66;
      box-shadow: 0 14px 28px rgba(15,23,42,.08);
    }

    .project-file-actions {
      display: flex;
      align-items: center;
      gap: .45rem;
    }

    .project-file-action {
      width: 2.15rem;
      height: 2.15rem;
      border-radius: 999px;
      border: 1px solid #e2e8f0;
      background: rgba(255,255,255,.96);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #475569;
      box-shadow: 0 8px 18px rgba(15,23,42,.08);
    }

    .project-file-action.danger {
      color: #e11d48;
      border-color: #fecdd3;
    }

    .project-file-preview {
      width: 4rem;
      height: 3.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      border-radius: .55rem;
      cursor: pointer;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
    }

    .project-file-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: .5rem;
      background: #f8fafc;
    }

    .project-file-figure {
      position: relative;
      width: 100%;
      height: 100%;
      border-radius: .5rem;
      background: #f1f5f9;
      overflow: hidden;
    }

    .project-file-figure::before {
      content: "";
      position: absolute;
      right: 0;
      top: 0;
      width: 1.25rem;
      height: 1.25rem;
      border-radius: 0 .45rem 0 .55rem;
      background: linear-gradient(135deg, #dbe5f0 0%, #eef4fb 54%, #d7e1ec 55%);
    }

    .project-file-ext {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      min-width: 2.9rem;
      border-radius: .4rem;
      padding: .34rem .45rem;
      background: var(--file-color);
      color: #fff;
      font-size: .75rem;
      line-height: 1;
      font-weight: 900;
      text-align: center;
    }

    .project-file-lines {
      position: absolute;
      left: 1.35rem;
      right: 1.35rem;
      bottom: .65rem;
      display: grid;
      gap: .22rem;
    }

    .project-file-lines span {
      height: .22rem;
      border-radius: 999px;
      background: var(--file-color);
      opacity: .42;
    }

    .project-file-lines span:nth-child(2) { width: 78%; }

    .project-file-image-ext {
      position: absolute;
      left: .35rem;
      bottom: .35rem;
      min-width: 2.7rem;
      border-radius: .45rem;
      padding: .32rem .45rem;
      background: var(--file-color);
      color: #fff;
      font-size: .68rem;
      line-height: 1;
      font-weight: 900;
      text-align: center;
      box-shadow: 0 8px 14px rgba(15,23,42,.16);
    }

    .project-file-title {
      color: #172236;
      font-size: .95rem;
      line-height: 1.18;
      font-weight: 800;
      min-height: 0;
      text-align: left;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .project-file-date {
      margin-top: .25rem;
      color: #60728b;
      font-size: .78rem;
      line-height: 1.2;
      text-align: left;
    }

    .project-upload-progress {
      position: fixed;
      right: 1rem;
      bottom: 1rem;
      z-index: 1700;
      width: min(360px, calc(100vw - 2rem));
      border: 1px solid #e2e8f0;
      border-radius: 1.35rem;
      background: rgba(255,255,255,.96);
      box-shadow: 0 22px 54px rgba(15,23,42,.16);
      backdrop-filter: blur(10px);
      padding: .8rem;
      display: grid;
      gap: .65rem;
    }

    .project-upload-progress.hidden {
      display: none !important;
    }

    .project-upload-row {
      display: grid;
      grid-template-columns: 2.4rem 1fr;
      gap: .7rem;
      align-items: center;
      padding: .6rem;
      border-radius: .85rem;
      background: #fff;
      border: 1px solid #e2e8f0;
    }

    .project-upload-ghost {
      width: 2.35rem;
      height: 2.85rem;
      border-radius: .55rem;
      background: linear-gradient(180deg, #eef4fb, #e2e8f0);
      position: relative;
      overflow: hidden;
    }

    .project-upload-ghost::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent);
      animation: projectFileShimmer 1.2s infinite;
    }

    @keyframes projectFileShimmer {
      from { transform: translateX(-100%); }
      to { transform: translateX(100%); }
    }

    @media (max-width: 520px) {
      .project-file-card {
        grid-template-columns: 3.9rem minmax(0, 1fr);
      }

      .project-file-actions {
        grid-column: 1 / -1;
        justify-content: flex-end;
      }

      .project-file-preview {
        width: 3.55rem;
        height: 3.55rem;
      }
    }

    #modalDropzone.is-dragging {
      border-color: #d9ff66;
      background: #f8ffe8;
      box-shadow: inset 0 0 0 1px rgba(132,204,22,.25), 0 18px 42px rgba(132,204,22,.10);
    }

    #taskFileDropzone.is-dragging {
      border-color: #d9ff66;
      background: #f8ffe8;
      box-shadow: inset 0 0 0 1px rgba(132,204,22,.22), 0 14px 30px rgba(132,204,22,.08);
    }

    .project-modal-drop-overlay {
      position: absolute;
      inset: 0;
      z-index: 80;
      border-radius: 1rem;
      background: rgba(248,250,252,.88);
      backdrop-filter: blur(10px);
      padding: 1rem;
      display: none;
      align-items: center;
      justify-content: center;
    }

    .project-modal-drop-overlay.is-active {
      display: flex;
    }

    .project-modal-drop-box {
      width: min(640px, 92%);
      min-height: min(420px, 72vh);
      border: 3px dashed #d9ff66;
      border-radius: 1.75rem;
      background: rgba(255,255,255,.92);
      box-shadow: 0 24px 70px rgba(15,23,42,.16);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: #334155;
    }

    .project-preview-shell {
      height: min(62vh, 620px);
      overflow: auto;
      overscroll-behavior: contain;
      touch-action: none;
      background: #f1f5f9;
    }

    .project-file-preview-modal {
      z-index: 2400;
    }

    .project-file-preview-dialog {
      width: min(78vw, 880px);
    }

    @media (max-width: 768px) {
      .project-file-preview-dialog {
        width: calc(100vw - 1.5rem);
      }
    }

    .project-preview-content {
      transform-origin: center center;
      transition: transform .08s ease-out;
    }

    .project-preview-frame {
      width: 100%;
      height: min(62vh, 620px);
      border: 0;
      background: #f8fafc;
    }

    .project-preview-image {
      max-width: 100%;
      max-height: min(62vh, 620px);
      object-fit: contain;
      margin: 0 auto;
      transform-origin: center center;
    }

    .project-unsupported-card {
      width: min(340px, 92vw);
      border-radius: 1.35rem;
      background: white;
      border: 1px solid #dbe5f2;
      box-shadow: 0 18px 42px rgba(15,23,42,.10);
      padding: 1.5rem;
      text-align: center;
    }

    .project-board-grid {
      display: grid;
      grid-template-columns: repeat(1, minmax(0, 1fr));
      gap: .85rem;
    }

    @media (min-width: 640px) {
      .project-board-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (min-width: 1024px) {
      .project-board-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media (min-width: 1280px) {
      .project-board-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }
    }

    .project-board-card {
      position: relative;
      display: block;
      width: 100%;
      padding: 0;
      overflow: hidden;
      border-radius: 1rem;
      border: 1px solid #dbe5f2;
      background: #fff;
      color: #172236;
      appearance: none;
      box-shadow: 0 12px 28px rgba(15, 23, 42, .09);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .project-board-card:hover {
      transform: translateY(-2px);
      border-color: #d9ff66;
      box-shadow: 0 18px 42px rgba(15, 23, 42, .14);
    }

    .project-board-cover {
      height: 58px;
      border-radius: 1rem 1rem 0 0;
      background:
        radial-gradient(circle at 18% 8%, rgba(236, 254, 136, .75), transparent 32%),
        linear-gradient(135deg, var(--board-from), var(--board-to));
      background-size: cover;
      background-position: center;
    }

    .project-board-cover.has-image {
      height: 66px;
      background-size: cover;
      background-position: center;
    }

    .project-cover-preview {
      min-height: 112px;
      border-radius: 1.25rem;
      border: 1px solid #dbe5f2;
      background:
        radial-gradient(circle at 18% 8%, rgba(236, 254, 136, .75), transparent 32%),
        linear-gradient(135deg, var(--cover-from, #0f766e), var(--cover-to, #bef264));
      background-size: cover;
      background-position: center;
      box-shadow: inset 0 -42px 80px rgba(15, 23, 42, .22);
    }

    .project-cover-swatch {
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 999px;
      border: 2px solid #fff;
      box-shadow: 0 0 0 1px #dbe5f2, 0 8px 18px rgba(15, 23, 42, .10);
      transition: transform .16s ease, box-shadow .16s ease;
    }

    .project-cover-swatch:hover,
    .project-cover-swatch.is-active {
      transform: translateY(-1px);
      box-shadow: 0 0 0 3px #ecfe88, 0 12px 24px rgba(15, 23, 42, .14);
    }

    .project-cover-trigger-wrap {
      position: relative;
      display: inline-flex;
      justify-content: flex-end;
    }

    .project-cover-popover {
      position: fixed;
      z-index: 2147482200;
      width: min(19.5rem, calc(100vw - 1.5rem));
      max-height: min(28rem, calc(100vh - 1.5rem));
      overflow-y: auto;
      border-radius: .95rem;
      border: 1px solid #dbe5f2;
      background: #fff;
      padding: .75rem;
      color: #0f172a;
      box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }

    .project-cover-popover.hidden {
      display: none !important;
    }

    .project-cover-section {
      display: grid;
      gap: .55rem;
    }

    .project-cover-section + .project-cover-section {
      margin-top: .8rem;
      padding-top: .75rem;
      border-top: 1px solid #eef2f7;
    }

    .project-cover-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .5rem;
    }

    .project-cover-section-title {
      color: #334155;
      font-size: .66rem;
      font-weight: 900;
      line-height: 1;
      letter-spacing: .14em;
      text-transform: uppercase;
    }

    .project-cover-more-btn {
      height: 1.65rem;
      border-radius: .58rem;
      border: 1px solid #dbe5f2;
      background: #f8fafc;
      padding: 0 .55rem;
      color: #475569;
      font-size: .66rem;
      font-weight: 900;
      transition: background-color .15s ease, color .15s ease, border-color .15s ease;
    }

    .project-cover-more-btn:hover {
      background: #ecfe88;
      border-color: #c8f24a;
      color: #0f172a;
    }

    .project-cover-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .45rem;
    }

    .project-cover-grid.is-colors {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .project-cover-option {
      position: relative;
      min-height: 3.75rem;
      aspect-ratio: 16 / 9;
      overflow: hidden;
      border-radius: .58rem;
      border: 2px solid transparent;
      background: #e2e8f0;
      background-size: cover;
      background-position: center;
      box-shadow: inset 0 -42px 70px rgba(15, 23, 42, .14);
      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .project-cover-option:hover {
      transform: translateY(-1px);
      border-color: rgba(217, 255, 102, .78);
      box-shadow: inset 0 -42px 70px rgba(15, 23, 42, .24), 0 14px 28px rgba(15, 23, 42, .12);
    }

    .project-cover-option.is-active {
      border-color: #a3e635;
      box-shadow: inset 0 -42px 70px rgba(15, 23, 42, .18), 0 0 0 3px rgba(236, 254, 136, .75);
    }

    .project-cover-option span {
      position: absolute;
      left: .55rem;
      right: .55rem;
      bottom: .45rem;
      display: block;
      color: #fff;
      font-size: .56rem;
      font-weight: 900;
      line-height: 1.1;
      text-align: left;
      text-shadow: 0 1px 6px rgba(15, 23, 42, .75);
    }

    .project-cover-option.is-active::after {
      content: "✓";
      position: absolute;
      right: .45rem;
      top: .45rem;
      width: 1.15rem;
      height: 1.15rem;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: #0f172a;
      color: #ecfe88;
      font-size: .68rem;
      font-weight: 900;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .28);
    }

    .project-cover-color-option {
      min-height: 3.1rem;
      border-radius: .58rem;
      border: 2px solid transparent;
      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .project-cover-color-option:hover {
      transform: translateY(-1px);
      border-color: rgba(217, 255, 102, .78);
      box-shadow: 0 14px 28px rgba(15, 23, 42, .22);
    }

    .project-cover-color-option.is-active {
      border-color: #a3e635;
      box-shadow: 0 0 0 3px rgba(236, 254, 136, .38);
    }

    .project-cover-upload-btn {
      width: 100%;
      min-height: 3rem;
      border-radius: .7rem;
      border: 1.5px dashed #cbd5e1;
      background: #f8fafc;
      display: flex;
      align-items: center;
      gap: .65rem;
      padding: .65rem .7rem;
      color: #334155;
      text-align: left;
      transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }

    .project-cover-upload-btn:hover {
      border-color: #b8f52f;
      background: #fbffea;
      box-shadow: 0 0 0 3px rgba(236, 254, 136, .28);
    }

    .project-cover-upload-icon {
      width: 2rem;
      height: 2rem;
      border-radius: .55rem;
      display: grid;
      place-items: center;
      background: #ecfe88;
      color: #0f172a;
      flex: 0 0 auto;
    }

    @media (max-width: 640px) {
      .project-cover-popover {
        width: calc(100vw - 1rem);
      }

      .project-cover-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    .project-board-footer {
      background: #fff;
      border-top: 1px solid #e2e8f0;
      padding: .65rem .85rem .7rem;
      min-height: 0;
    }

    html[data-color-mode="dark"] .project-board-card {
      border-color: #243044;
      background: #111827;
      color: #fff;
      box-shadow: 0 12px 28px rgba(0, 0, 0, .28);
    }

    html[data-color-mode="dark"] .project-board-footer {
      border-top-color: rgba(148, 163, 184, .16);
      background: rgba(15, 23, 42, .92);
    }

    .project-board-detail {
      min-height: calc(100vh - 128px);
    }

    body.project-board-open {
      overflow: hidden;
    }

    body.project-board-open #proyectos-kanban {
      overflow: hidden;
    }

    body.project-board-open .project-board-detail {
      min-height: 0;
      overflow: hidden;
    }

    body.project-board-open .project-board-columns {
      min-height: 0;
      overflow-x: auto;
      overflow-y: hidden;
    }

    .project-board-anim {
      transition: opacity .18s ease, transform .18s ease;
      will-change: opacity, transform;
    }

    .project-board-anim.is-entering {
      opacity: 0;
      transform: translateY(8px);
    }

    .project-board-anim.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    @media (prefers-reduced-motion: reduce) {
      .project-board-anim {
        transition: none;
      }

      .project-board-anim.is-entering,
      .project-board-anim.is-visible {
        transform: none;
      }
    }

    .project-board-columns {
      position: relative;
      isolation: isolate;
      display: flex;
      align-items: flex-start;
      gap: .8rem;
      overflow-x: auto;
      padding: .2rem .1rem 1rem;
      min-height: calc(100vh - 205px);
      scroll-behavior: smooth;
      user-select: none;
    }

    .project-board-columns.is-ai-working::before {
      content: "";
      position: absolute;
      inset: .2rem;
      z-index: 0;
      pointer-events: none;
      border-radius: 1.15rem;
      background: linear-gradient(120deg, rgba(129, 140, 248, .42), rgba(217, 70, 239, .38), rgba(240, 254, 151, .55), rgba(56, 189, 248, .32), rgba(129, 140, 248, .42));
      background-size: 260% 260%;
      filter: blur(18px);
      opacity: .62;
      animation: projectBoardAiGlow 2.25s ease-in-out infinite;
    }

    .project-board-columns.is-ai-working::after {
      content: attr(data-ai-label);
      position: absolute;
      left: .75rem;
      top: .45rem;
      z-index: 4;
      pointer-events: none;
      border-radius: 999px;
      border: 1px solid rgba(217, 70, 239, .22);
      background: rgba(255, 255, 255, .9);
      color: #86198f;
      padding: .34rem .64rem;
      font-size: .68rem;
      font-weight: 900;
      line-height: 1;
      box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
      backdrop-filter: blur(10px);
    }

    .project-board-column {
      position: relative;
      z-index: 1;
      width: min(292px, calc(100vw - 2rem));
      min-width: 276px;
      max-height: min(980px, calc(100vh - 205px));
      display: flex;
      flex-direction: column;
      align-self: flex-start;
      border-radius: .95rem;
      border: 1px solid #dbe5f2;
      background: linear-gradient(180deg, #f8fafc, #eef3f9);
      overflow: hidden;
    }

    .project-board-column.is-ai-working {
      isolation: isolate;
      border-color: rgba(217, 70, 239, .25);
      box-shadow: 0 0 0 3px rgba(236, 254, 151, .55), 0 18px 44px rgba(168, 85, 247, .16);
    }

    .project-board-column.is-ai-working::before {
      content: "";
      position: absolute;
      inset: -12px;
      z-index: 0;
      pointer-events: none;
      background: linear-gradient(120deg, rgba(129, 140, 248, .42), rgba(217, 70, 239, .38), rgba(240, 254, 151, .62), rgba(56, 189, 248, .32), rgba(129, 140, 248, .42));
      background-size: 260% 260%;
      filter: blur(16px);
      opacity: .7;
      animation: projectBoardAiGlow 2.25s ease-in-out infinite;
    }

    .project-board-column.is-ai-working > * {
      position: relative;
      z-index: 1;
    }

    .project-board-column.is-column-dragging {
      opacity: .5;
      transform: rotate(.35deg);
    }

    .project-board-column-preview {
      width: min(292px, calc(100vw - 2rem));
      min-width: 276px;
      min-height: 8.5rem;
      align-self: stretch;
      flex: 0 0 auto;
      pointer-events: none;
      border-radius: .95rem;
      border: 2px dashed #b8f52f;
      background: rgba(236, 255, 183, .42);
      box-shadow: inset 0 0 0 3px rgba(217, 255, 102, .22);
    }

    .project-board-column-header {
      cursor: default;
      user-select: none;
    }

    .project-board-column-header:active {
      cursor: default;
    }

    .project-board-column-body {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      padding: .65rem;
      display: flex;
      flex-direction: column;
      gap: .55rem;
    }

    .project-board-ai-creating {
      position: relative;
      display: flex;
      align-items: center;
      gap: .6rem;
      border-radius: .85rem;
      border: 1px solid rgba(217, 70, 239, .22);
      background: linear-gradient(135deg, rgba(255, 255, 255, .96), rgba(250, 245, 255, .92));
      padding: .72rem .78rem;
      color: #334155;
      box-shadow: 0 14px 32px rgba(168, 85, 247, .12);
      overflow: hidden;
    }

    .project-board-ai-creating::before {
      content: "";
      position: absolute;
      inset: -45%;
      background: linear-gradient(90deg, transparent, rgba(217, 70, 239, .2), rgba(217, 255, 102, .24), transparent);
      transform: translateX(-60%);
      animation: projectBoardAiSweep 1.45s ease-in-out infinite;
    }

    .project-board-ai-creating-icon {
      position: relative;
      z-index: 1;
      width: 1.85rem;
      height: 1.85rem;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #0f172a;
      color: #d9ff66;
      box-shadow: 0 0 0 4px rgba(217, 255, 102, .18);
    }

    .project-board-ai-creating-text {
      position: relative;
      z-index: 1;
      min-width: 0;
      display: grid;
      gap: .08rem;
      font-size: .73rem;
      font-weight: 900;
      line-height: 1.2;
    }

    .project-board-ai-creating-text span:last-child {
      color: #64748b;
      font-size: .66rem;
      letter-spacing: .02em;
    }

    @keyframes projectBoardAiSweep {
      0% { transform: translateX(-70%) rotate(8deg); opacity: .25; }
      50% { opacity: .95; }
      100% { transform: translateX(70%) rotate(8deg); opacity: .25; }
    }

    @keyframes projectBoardAiGlow {
      0%, 100% { background-position: 0% 50%; opacity: .58; }
      50% { background-position: 100% 50%; opacity: .92; }
    }

    @media (max-width: 767px) {
      .project-board-columns {
        min-height: calc(100vh - 175px);
      }

      .project-board-column {
        max-height: calc(100vh - 175px);
      }
    }

    .project-task-card {
      display: flex;
      flex: 0 0 auto;
      flex-direction: column;
      border-radius: .85rem;
      border: 1px solid #e2e8f0;
      background: #fff;
      padding: 0;
      box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
      cursor: default;
      overflow: hidden;
    }

    .project-task-card.is-entering {
      animation: projectTaskCardEnter .46s cubic-bezier(.16, .84, .28, 1);
    }

    .project-task-card.is-pending {
      border-color: #d9ff66;
      box-shadow: 0 0 0 3px rgba(236, 254, 136, .38), 0 10px 22px rgba(15, 23, 42, .08);
    }

    .project-task-card.is-pending .project-task-card-body::after {
      content: "Creando...";
      position: absolute;
      right: .7rem;
      bottom: .55rem;
      color: #65a30d;
      font-size: .62rem;
      font-weight: 900;
      letter-spacing: .02em;
    }

    @keyframes projectTaskCardEnter {
      0% {
        opacity: 0;
        transform: translateY(8px) scale(.99);
      }
      72% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
      100% {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .project-task-card.is-entering {
        animation: none;
      }
    }

    .project-task-cover {
      width: 100%;
      aspect-ratio: 16 / 10;
      object-fit: cover;
      display: block;
      flex: 0 0 auto;
      background: #e2e8f0;
      border-bottom: 1px solid #e2e8f0;
    }

    .project-task-card-body {
      position: relative;
      min-height: 5.25rem;
      flex: 0 0 auto;
      padding: .8rem;
      background: #fff;
      gap: 0 !important;
    }

    .project-task-toggle {
      margin-top: .15rem;
      margin-right: 0;
      width: 0 !important;
      min-width: 0;
      opacity: 0;
      pointer-events: none;
      transform: translateX(-.18rem) scale(.86);
      transition:
        width .34s cubic-bezier(.16, .84, .28, 1),
        min-width .34s cubic-bezier(.16, .84, .28, 1),
        margin-right .34s cubic-bezier(.16, .84, .28, 1),
        opacity .32s ease,
        transform .36s cubic-bezier(.16, .84, .28, 1);
    }

    .project-task-card:hover .project-task-toggle,
    .project-task-card:focus-within .project-task-toggle,
    .project-task-card.is-done .project-task-toggle {
      width: 1.25rem !important;
      min-width: 1.25rem;
      opacity: 1;
      pointer-events: auto;
      transform: scale(1);
      margin-right: .625rem;
    }

    .project-task-title {
      display: -webkit-box;
      max-height: 2.6em;
      overflow: hidden;
      overflow-wrap: anywhere;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
    }

    .project-task-footer {
      min-height: 1.5rem;
    }

    .project-task-card:active {
      cursor: default;
    }

    .project-task-card.is-done {
      background: #f8fafc;
      color: #94a3b8;
    }

    .project-board-drop-active {
      outline: 2px dashed #d9ff66;
      outline-offset: -6px;
      background: #f7ffe8;
    }

    .project-board-drop-indicator {
      height: .45rem;
      border-radius: 999px;
      background: #d9ff66;
      box-shadow: 0 0 0 3px rgba(217, 255, 102, .22);
      margin: .1rem 0;
    }

    .project-task-card.is-board-dragging {
      opacity: .45;
      transform: rotate(.4deg);
    }

    .progress-fill-live {
      transition: width .5s ease, background-color .2s ease;
    }

    .project-client-filter {
      position: relative;
      width: 100%;
      z-index: 2147482100;
    }

    .project-client-filter #clientSelector {
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      min-width: 0 !important;
      min-height: 0 !important;
      margin: -1px !important;
      padding: 0 !important;
      overflow: hidden !important;
      clip: rect(0 0 0 0) !important;
      clip-path: inset(50%) !important;
      white-space: nowrap !important;
      border: 0 !important;
      opacity: 0 !important;
      pointer-events: none !important;
    }

    .project-client-filter-button {
      width: 100%;
      height: 2.65rem;
      border-radius: .82rem;
      border: 1px solid #dbe5f2;
      background: #fff;
      color: #334155;
      box-shadow: 0 8px 18px rgba(15, 23, 42, .04);
      padding: 0 .82rem;
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      gap: .55rem;
      font-size: .86rem;
      font-weight: 800;
      line-height: 1;
      transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
    }

    .project-client-filter-button:hover,
    .project-client-filter-button[aria-expanded="true"] {
      border-color: #d9f99d;
      box-shadow: 0 0 0 3px rgba(236, 254, 136, .35), 0 10px 24px rgba(15, 23, 42, .07);
    }

    .project-client-filter-button svg {
      flex: 0 0 auto;
      color: #94a3b8;
      transition: transform .16s ease;
    }

    .project-client-filter-button[aria-expanded="true"] svg {
      transform: rotate(180deg);
    }

    .project-client-filter-menu {
      position: absolute;
      left: 0;
      top: calc(100% + .35rem);
      z-index: 2147482000;
      width: min(20rem, calc(100vw - 2rem));
      max-height: 15rem;
      overflow-y: auto;
      border-radius: .9rem;
      border: 1px solid #dbe5f2;
      background: #fff;
      padding: .35rem;
      box-shadow: 0 18px 44px rgba(15, 23, 42, .16);
    }

    .project-client-filter-menu.hidden {
      display: none !important;
    }

    .project-client-filter-option {
      width: 100%;
      border-radius: .72rem;
      padding: .58rem .7rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
      color: #334155;
      font-size: .82rem;
      font-weight: 800;
      text-align: left;
      transition: background-color .14s ease, color .14s ease;
    }

    .project-client-filter-option:hover,
    .project-client-filter-option.is-active {
      background: #f1f5f9;
      color: #0f172a;
    }

    .project-client-filter-option.is-active {
      background: #ecfe88;
    }
  </style>
  <div id="stagesData" data-stages='{{ json_encode($stages) }}'></div>
  <div id="projectBoardsHeader" class="project-board-anim is-visible relative z-[2147482050] mb-4 flex items-center justify-between flex-wrap gap-3 overflow-visible">
    <div>
      <div id="projectsSectionTitle" data-build-marker="boards-v1" class="text-2xl font-extrabold">Tableros de proyectos</div>
      <div id="projectsSectionDescription" class="text-sm text-slate-500 mt-1">Abre un tablero para organizar sus tarjetas, tareas y entregables por columnas.</div>
    </div>

    <div class="flex w-full items-center gap-3 flex-wrap">
      <div class="min-w-[240px] flex-1 lg:flex-none">
        <div class="project-client-filter" id="projectClientFilter">
          <select id="clientSelector" class="sr-only" data-native-select="1" aria-label="Filtrar por cliente" tabindex="-1">
          <option value="">Todos los Clientes</option>
          @foreach($clientes as $c)
            <option value="{{ $c['id'] }}">{{ $c['empresa'] }}</option>
          @endforeach
        </select>
          <button id="projectClientFilterButton" type="button" class="project-client-filter-button" aria-haspopup="listbox" aria-expanded="false" onclick="toggleProjectClientFilter(event)">
            <span id="projectClientFilterLabel" class="min-w-0 truncate">Todos los Clientes</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
          </button>
          <div id="projectClientFilterMenu" class="project-client-filter-menu hidden" role="listbox" aria-label="Filtrar proyectos por cliente">
            <button type="button" class="project-client-filter-option" data-project-client-option="" role="option" onclick="selectProjectClientFilter('')">
              <span class="truncate">Todos los Clientes</span>
              <span class="hidden text-slate-900" data-project-client-check="">✓</span>
            </button>
            @foreach($clientes as $c)
              <button type="button" class="project-client-filter-option" data-project-client-option="{{ $c['id'] }}" role="option" onclick="selectProjectClientFilter(@js($c['id']))">
                <span class="truncate">{{ $c['empresa'] }}</span>
                <span class="hidden text-slate-900" data-project-client-check="{{ $c['id'] }}">✓</span>
              </button>
            @endforeach
          </div>
        </div>
      </div>

      <div class="ml-auto flex items-center gap-3">
        <button id="openArchivedProjectsBtn" type="button" onclick="openArchivedProjectsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:bg-slate-50" title="Papelera de proyectos archivados" aria-label="Papelera de proyectos archivados">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12m-9 0V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0 1 12a2 2 0 002 2h4a2 2 0 002-2l1-12"/></svg>
        </button>

        <button id="addProjectBtn" onclick="openNewProjectModal()" class="primary-add-btn">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          Nuevo Tablero
        </button>
      </div>
    </div>
  </div>

  <div id="newProjectModal" class="fixed inset-0 z-50 hidden" aria-labelledby="new-project-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-visible rounded-2xl bg-white text-left shadow-2xl border border-slate-200 transition-all sm:my-8 sm:w-full sm:max-w-2xl flex flex-col">
          <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between rounded-t-2xl">
            <div>
              <div class="text-2xl font-extrabold text-slate-900 leading-none">Nuevo Tablero</div>
              <div class="text-sm text-slate-500 mt-1">Define portada, fechas, prioridad y meta de tiempo.</div>
            </div>
            <button onclick="closeNewProjectModal()" class="text-slate-400 hover:text-slate-600 transition-colors p-2 rounded-full hover:bg-slate-200/50">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-5 space-y-3.5">
            <div>
              <div class="mb-2 flex items-center justify-between gap-3">
                <label class="block text-sm font-semibold text-slate-700">Portada</label>
                <div class="project-cover-trigger-wrap">
                  <button type="button" onclick="toggleNewProjectCoverGallery()" class="inline-flex h-9 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 text-xs font-extrabold text-slate-700 shadow-sm hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2 1.6-1.6a2 2 0 012.8 0L20 14m-9-6h.01M5 20h14a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                    Cambiar
                  </button>
                  <div id="newProjectCoverGallery" class="project-cover-popover hidden"></div>
                </div>
              </div>
              <div id="newProjectCoverPreview" class="project-cover-preview mb-3"></div>
            </div>
            <div>
              <label class="block text-xl font-extrabold text-slate-900 mb-2 leading-none">Título</label>
              <input id="newProjectTitle" class="w-full h-12 rounded-xl border-slate-200 px-4 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-lg font-semibold text-slate-800 placeholder:text-slate-400" placeholder="Ej. Rediseño landing">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Descripción</label>
              <textarea id="newProjectDescription" rows="3" class="block w-full min-h-[5.5rem] rounded-xl border-slate-200 px-4 py-3 leading-6 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-sm font-semibold text-slate-700 placeholder:text-slate-400" placeholder="Objetivo, alcance, entregables o contexto del proyecto"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Cliente enlazado</label>
                <select id="newProjectClient" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700">
                  <option value="">Sin cliente</option>
                  @foreach($clientes as $c)
                    <option value="{{ $c['id'] }}">{{ $c['empresa'] }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tiempo previsto</label>
                <div class="flex items-center gap-2">
                  <div class="relative">
                    <input id="newProjectPlannedDays" type="number" min="0" max="999" step="1" value="0" class="w-[86px] h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-[18px] font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">d</span>
                  </div>
                  <div class="relative">
                    <input id="newProjectPlannedHours" type="number" min="0" max="99" step="1" value="0" class="w-[78px] h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-[18px] font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">h</span>
                  </div>
                  <div class="relative">
                    <input id="newProjectPlannedMinutes" type="number" min="0" max="99" step="1" value="0" class="w-[78px] h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-[18px] font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">m</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Prioridad</label>
                <select id="newProjectPriority" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700">
                  <option value="Con calma">Con calma</option>
                  <option value="Atención">Atención</option>
                  <option value="Urgente">Urgente</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Fecha de inicio</label>
                <div class="relative" onclick="openNewProjectDatePicker('start')">
                  <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <input type="text" id="newProjectStart" readonly placeholder="dd/mm/aaaa" class="w-full h-11 rounded-xl border-slate-200 pl-11 text-base font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer">
                </div>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Fecha de entrega</label>
                <div class="relative" onclick="openNewProjectDatePicker('due')">
                  <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <input type="text" id="newProjectDue" readonly placeholder="dd/mm/aaaa" class="w-full h-11 rounded-xl border-slate-200 pl-11 text-base font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer">
                </div>
              </div>
            </div>
          </div>
          <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50 rounded-b-2xl">
            <button onclick="closeNewProjectModal()" class="px-5 py-2.5 rounded-full border text-base font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
            <button onclick="createProjectFromModal()" class="px-5 py-2.5 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-base font-extrabold">Crear Tablero</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="archivedProjectsModal" class="fixed inset-0 z-[95] hidden" aria-modal="true" role="dialog" aria-labelledby="archived-projects-title">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeArchivedProjectsModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
        <div class="relative mx-auto w-[min(96vw,1200px)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-2xl">
          <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50 px-5 py-4">
            <div>
              <div id="archived-projects-title" class="text-xl font-extrabold text-slate-900">Papelera de proyectos</div>
              <div class="mt-1 text-xs text-slate-500">Consulta proyectos archivados, su fecha de creación, estado de avance y elimínalos permanentemente.</div>
            </div>
            <div class="flex items-center gap-2">
              <button id="deleteAllArchivedBtn" type="button" onclick="deleteAllArchivedProjects()" class="hidden inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 transition-colors hover:bg-rose-100">
                Eliminar todos
              </button>
              <button type="button" onclick="closeArchivedProjectsModal()" class="rounded-full p-2 text-slate-400 transition-colors hover:bg-slate-200/60 hover:text-slate-600" aria-label="Cerrar papelera de proyectos">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
          <div class="max-h-[72vh] overflow-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-white">
                <tr>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[16rem]">Proyecto</th>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Cliente</th>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[9rem]">Creación</th>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[9rem]">Progreso</th>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Completado</th>
                  <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[7rem]">Acciones</th>
                </tr>
              </thead>
              <tbody id="archivedProjectsModalBody" class="divide-y divide-slate-100"></tbody>
            </table>
            <div id="archivedProjectsModalEmpty" class="hidden px-6 py-12 text-center">
              <div class="text-sm font-semibold text-slate-600">No hay proyectos archivados</div>
              <div class="mt-1 text-xs text-slate-500">Cuando archives proyectos aparecerán aquí.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="proyectos-kanban" class="min-h-[calc(100vh-190px)]">
    <div id="projectBoardsView" class="project-board-anim is-visible">
      <div id="projectBoardsCount" class="hidden">0 tableros</div>
      <div id="projectBoardsGrid" class="project-board-grid"></div>
    </div>

    <div id="projectBoardDetailView" class="project-board-detail project-board-anim hidden">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
        <div class="flex min-w-0 items-center gap-3">
          <button type="button" onclick="closeProjectBoard()" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-lime-200 bg-lime-100 text-slate-950 shadow-sm hover:bg-lime-200" aria-label="Volver a tableros">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div class="min-w-0">
            <div id="projectBoardTitle" class="truncate text-xl font-black leading-tight text-slate-950 sm:text-2xl">Tablero</div>
            <div id="projectBoardMeta" class="mt-0.5 truncate text-xs font-bold text-slate-700 sm:text-sm">Proyecto</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" onclick="openProject(currentBoardProjectId)" class="inline-flex h-9 items-center gap-2 rounded-full border border-slate-900/10 bg-white/80 px-3 text-xs font-extrabold text-slate-800 shadow-sm hover:bg-white sm:px-4 sm:text-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4Z"/></svg>
            Detalles
          </button>
          <button type="button" onclick="openBoardTaskTrash()" class="relative inline-flex h-9 items-center gap-2 rounded-full border border-slate-900/10 bg-white/80 px-3 text-xs font-extrabold text-slate-800 shadow-sm hover:bg-white sm:px-3" title="Papelera de tareas">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
            <span class="hidden sm:inline">Papelera</span>
            <span id="boardTaskTrashCount" class="hidden min-w-5 rounded-full bg-slate-900 px-1.5 py-0.5 text-[10px] font-black leading-none text-white">0</span>
          </button>
          <button type="button" onclick="addBoardStage()" class="inline-flex h-9 items-center gap-2 rounded-full border border-lime-200 bg-lime-100 px-3 text-xs font-extrabold text-slate-900 shadow-sm hover:bg-lime-200 sm:px-4 sm:text-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 5v14m7-7H5"/></svg>
            Columna
          </button>
        </div>
      </div>
      <div id="projectBoardColumns" class="project-board-columns custom-scroll"></div>
    </div>
  </div>

  <div id="projectFilePreviewModal" class="project-file-preview-modal fixed inset-0 hidden items-center justify-center bg-black/50 p-3 sm:p-5" aria-modal="true" role="dialog">
    <div class="project-file-preview-dialog max-h-[92vh] overflow-hidden rounded-2xl bg-white shadow-2xl border border-slate-200 flex flex-col">
      <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
        <div class="min-w-0">
          <div id="projectFilePreviewTitle" class="text-lg font-black text-slate-900 truncate">Vista previa</div>
          <div id="projectFilePreviewSubtitle" class="mt-1 text-xs font-semibold text-slate-500">Usa trackpad o rueda sobre la vista para acercar y alejar.</div>
        </div>
        <div class="flex items-center gap-2">
          <a id="projectFilePreviewDownload" href="#" target="_blank" class="w-10 h-10 rounded-full border border-emerald-200 bg-white flex items-center justify-center text-emerald-600 hover:bg-emerald-50" title="Descargar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
          </a>
          <button type="button" onclick="event.stopPropagation();closeProjectFilePreview()" class="w-10 h-10 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50" title="Cerrar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <div id="projectFilePreviewShell" class="project-preview-shell p-3 sm:p-4">
        <div id="projectFilePreviewContent" class="project-preview-content">
          <iframe id="projectFilePreviewFrame" class="project-preview-frame hidden rounded-xl bg-white shadow" title="Vista previa del archivo"></iframe>
          <div id="projectFilePreviewImageWrap" class="hidden min-h-[320px] rounded-xl bg-slate-100 flex items-center justify-center p-4">
            <img id="projectFilePreviewImage" class="project-preview-image" alt="Vista previa">
          </div>
          <div id="projectFilePreviewUnsupported" class="hidden min-h-[320px] rounded-xl bg-slate-100 flex items-center justify-center p-4">
            <div class="project-unsupported-card">
              <div id="projectFilePreviewExt" class="mx-auto mb-4 inline-flex rounded-xl px-5 py-3 text-xl font-black text-white bg-slate-500">FILE</div>
              <div class="text-lg font-black text-slate-900">Vista previa no disponible</div>
              <div class="mt-1 text-sm text-slate-500">Este formato se puede descargar directamente.</div>
              <a id="projectFilePreviewUnsupportedDownload" href="#" target="_blank" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-lime-300 px-5 py-3 font-bold text-slate-900 hover:bg-lime-200">
                Descargar archivo
              </a>
            </div>
          </div>
        </div>
      </div>
      <div id="projectFilePreviewFooter" class="border-t border-slate-100 px-5 py-3 text-sm font-bold text-slate-700 truncate"></div>
    </div>
  </div>

  <!-- Project Detail Modal -->
  <div id="projectModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-visible rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl h-[85vh] flex flex-col">
           <div id="projectModalDropOverlay" class="project-modal-drop-overlay">
             <div class="project-modal-drop-box">
               <svg class="h-16 w-16 text-lime-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
               <div class="text-2xl font-black text-slate-900">Suelta para cargar archivos</div>
               <div class="mt-2 text-sm font-semibold text-slate-500">Se guardarán en la carpeta del proyecto.</div>
             </div>
           </div>
           <!-- Header -->
           <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-none rounded-t-2xl">
             <div class="flex items-center gap-3 w-full">
                <div class="w-10 h-10 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 font-bold" id="modalAvatar">HV</div>
                <div class="flex-1 min-w-0">
                    <input id="modalTitle" class="block w-full bg-transparent border-0 p-0 text-xl font-extrabold text-slate-900 focus:ring-0 placeholder-slate-400" placeholder="Título del Proyecto">
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                      <select id="modalClientSelect" class="w-72 max-w-full"></select>
                    </div>
                </div>
             </div>
             <button onclick="closeProjectModal({ force: true })" class="text-slate-400 hover:text-slate-600 transition-colors p-2 rounded-full hover:bg-slate-200/50">
               <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
             </button>
           </div>

           <div class="bg-white px-6 py-3 border-b border-slate-100 flex-none">
             <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1 gap-1">
               <button type="button" data-project-tab="info" onclick="setProjectModalTab('info')" class="project-detail-tab rounded-xl px-4 py-2 text-sm font-bold text-slate-600">Información</button>
               <button type="button" data-project-tab="notes" onclick="setProjectModalTab('notes')" class="project-detail-tab rounded-xl px-4 py-2 text-sm font-bold text-slate-600">Notas</button>
             </div>
           </div>
           
           <!-- Body -->
           <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
               <!-- Main Content -->
               <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scroll">
                  <div id="projectModalInfoTab" class="space-y-8">
                   
                   <!-- Description -->
                   <div>
                       <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                           <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                           Descripción
                       </label>
                       <div id="projectDescShell" class="project-desc-shell is-collapsed">
                         <div class="compact-rich-editor-shell" data-desc-editor-shell>
                           <div class="compact-desc-toolbar" aria-label="Herramientas de descripcion">
                             <div class="compact-desc-format-wrap">
                               <button type="button" class="compact-desc-format-trigger" onmousedown="event.preventDefault()" onclick="toggleCompactDescFormatMenu('modalDesc')">
                                 <span data-compact-desc-format-label="modalDesc">Texto</span>
                                 <iconify-icon icon="lucide:chevron-down" width="14" height="14" aria-hidden="true"></iconify-icon>
                               </button>
                               <div class="compact-desc-format-menu hidden" data-compact-desc-format-menu="modalDesc">
                                 <button type="button" class="compact-desc-format-option is-selected" data-compact-desc-format-option="modalDesc" data-format="p" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('modalDesc','p')">Texto</button>
                                 <button type="button" class="compact-desc-format-option" data-compact-desc-format-option="modalDesc" data-format="h1" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('modalDesc','h1')">Titulo</button>
                                 <button type="button" class="compact-desc-format-option" data-compact-desc-format-option="modalDesc" data-format="h2" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('modalDesc','h2')">Subtitulo</button>
                               </div>
                             </div>
                             <button type="button" class="compact-desc-tool" data-compact-desc-cmd="bold" title="Negrita" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'bold')"><iconify-icon icon="lucide:bold" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool" data-compact-desc-cmd="italic" title="Cursiva" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'italic')"><iconify-icon icon="lucide:italic" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool" data-compact-desc-cmd="strikeThrough" title="Tachado" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'strikeThrough')"><iconify-icon icon="lucide:strikethrough" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool is-highlight" data-compact-desc-cmd="highlight" title="Resaltar" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'hiliteColor', '#fef08a')"><iconify-icon icon="lucide:highlighter" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool" data-compact-desc-cmd="checkline" title="Checklist" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'checkline')"><iconify-icon icon="lucide:list-checks" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool" data-compact-desc-cmd="numberline" title="Lista numerada" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'numberline')"><iconify-icon icon="lucide:list-ordered" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                             <button type="button" class="compact-desc-tool" title="Separador" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('modalDesc', 'insertHorizontalRule')"><iconify-icon icon="lucide:minus" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                           </div>
                           <div id="modalDesc" class="compact-rich-editor" contenteditable="true" spellcheck="true" data-placeholder="Añade una descripción detallada..." onfocus="activateCompactDescEditor('modalDesc')" oninput="queueDescriptionAutosave()"></div>
                         </div>
                         <button id="projectDescToggle" type="button" onclick="toggleProjectDescription()" class="project-desc-toggle">
                           <svg id="projectDescToggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                           <span id="projectDescToggleText">Mostrar más</span>
                         </button>
                       </div>
                         <div class="mt-2 text-right">
                           <span id="modalDescAutosaveStatus" class="text-xs font-bold text-slate-400">Autoguardado</span>
                       </div>
                   </div>

                   <!-- Attachments -->
                   <div>
                       <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                           <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                           Archivos Adjuntos
                       </label>
                       <div id="modalFilesList" class="project-file-grid mb-4">
                           <!-- Files injected here -->
                       </div>
                       
                       <!-- Dropzone -->
                       <div id="modalDropzone" class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:bg-slate-50 transition-colors cursor-pointer" onclick="document.getElementById('modalFileInput').click()">
                           <input type="file" id="modalFileInput" class="hidden" multiple onchange="handleModalFileUpload(this.files)">
                           <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                           <p class="mt-1 text-xs text-slate-500">Haz clic o arrastra archivos aquí</p>
                       </div>
                       <div id="projectUploadProgress" class="project-upload-progress hidden">
                         <div class="flex items-center justify-between">
                           <div class="text-xs font-black text-slate-800">Subiendo archivos</div>
                           <div id="projectUploadSummary" class="text-[11px] font-bold text-slate-400">0%</div>
                         </div>
                         <div id="projectUploadProgressList" class="grid gap-2"></div>
                       </div>
                   </div>
                  </div>

                  <div id="projectModalTasksTab" class="hidden space-y-8">
                   <!-- Tasks / Checklist -->
                   <div>
                       <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                           <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                           Tareas
                       </label>
                       
                       <!-- Progress Bar -->
                       <div class="mb-4">
                         <div class="flex items-center justify-between mb-1">
                           <span class="text-[11px] font-semibold text-slate-500">Progreso</span>
                           <span id="modalTaskProgressLabel" class="text-[11px] font-bold text-lime-700">0%</span>
                         </div>
                         <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                             <div id="modalTaskProgress" class="h-2 rounded-full" style="width: 0%; background-color: #f43f5e"></div>
                         </div>
                       </div>
                       
                       <div id="modalTaskList" class="space-y-2 mb-3">
                           <!-- Tasks injected here -->
                       </div>
                       
                         <div id="modalTaskAddWrap" class="space-y-2">
                           <div class="flex gap-2">
                             <input id="newTaskInput" class="flex-1 rounded-lg border-slate-200 text-sm shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="Añadir nueva tarea..." onkeydown="if(event.key==='Enter'){ event.preventDefault(); addTask({refocus:true}); }">
                               <button type="button" onclick="addTask({refocus:true})" class="px-3 py-2 text-slate-900 rounded-lg font-bold text-sm border border-lime-200" style="background-color:#dff8a7;">Añadir</button>
                           </div>
                         </div>
                   </div>
                  </div>

                  <div id="projectModalNotesTab" class="hidden space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                      <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                          <div class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Pipeline de notas</div>
                          <div class="text-sm text-slate-500 mt-1">Ve primero el historial y abre el formulario solo cuando quieras crear una nueva nota.</div>
                        </div>
                        <button type="button" id="projectNoteToggleBtn" onclick="toggleProjectNoteComposer()" class="px-4 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold hover:bg-lime-400 whitespace-nowrap">Agregar nota</button>
                      </div>
                      <div id="projectNotesList" class="space-y-4"></div>
                    </div>

                    <div id="projectNoteComposer" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-5">
                      <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                          <div class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Nueva nota</div>
                          <div class="text-sm text-slate-500 mt-1">Selecciona la tarea vinculada y redacta la nota.</div>
                        </div>
                        <button type="button" onclick="toggleProjectNoteComposer(false)" class="w-9 h-9 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-100 flex items-center justify-center" title="Cerrar formulario">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>
                      <div class="grid grid-cols-1 xl:grid-cols-[260px_minmax(0,1fr)] gap-4">
                        <div class="space-y-2">
                          <label class="block text-[11px] uppercase tracking-wider font-bold text-slate-500">Tarea vinculada</label>
                          <div class="relative">
                            <select id="projectNoteTaskSelect" class="w-full h-12 appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-11 text-base font-semibold text-slate-700 shadow-sm transition focus:border-lime-500 focus:ring-2 focus:ring-lime-200"></select>
                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                          </div>
                        </div>
                        <div class="space-y-2">
                          <label class="block text-[11px] uppercase tracking-wider font-bold text-slate-500">Nota</label>
                          <textarea id="projectNoteInput" rows="5" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base leading-6 text-slate-900 shadow-sm transition focus:border-lime-500 focus:ring-2 focus:ring-lime-200 placeholder:text-slate-400" placeholder="Escribe una nota de seguimiento..."></textarea>
                        </div>
                      </div>
                      <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-slate-500">Cada nota mostrará la tarea vinculada, la fecha de subida y quién la creó.</div>
                        <div class="flex items-center justify-end gap-2">
                          <button type="button" onclick="toggleProjectNoteComposer(false)" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancelar</button>
                          <button type="button" onclick="addProjectNote()" class="px-4 py-2 rounded-xl bg-lime-300 text-slate-900 font-bold hover:bg-lime-400">Guardar nota</button>
                        </div>
                      </div>
                    </div>
                  </div>

               </div>
               
               <!-- Sidebar -->
               <div id="projectModalSidebar" class="w-full md:w-80 bg-slate-50 border-l border-slate-100 p-6 space-y-6 overflow-y-auto">
                   
                   <!-- Timer Large -->
                   <div class="group relative bg-[#111729] rounded-xl shadow-sm border border-[#1f2a47] p-4 text-center">
                       <div class="absolute top-3 right-3 flex items-center gap-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0">
                         <button
                           type="button"
                           onclick="openTimerFullscreen()"
                           class="w-8 h-8 rounded-full border border-slate-600 text-slate-300 hover:text-white hover:bg-[#1f2a47] flex items-center justify-center transition-colors"
                           title="Pantalla completa"
                           aria-label="Pantalla completa"
                         >
                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9V5a1 1 0 011-1h4m10 5V5a1 1 0 00-1-1h-4M4 15v4a1 1 0 001 1h4m10-5v4a1 1 0 01-1 1h-4"/></svg>
                         </button>
                         <button
                           type="button"
                           id="timerMiniBtn"
                           onclick="toggleTimerMiniPip()"
                           class="w-8 h-8 rounded-full border border-slate-600 text-slate-300 hover:text-white hover:bg-[#1f2a47] flex items-center justify-center transition-colors"
                           title="Modo PiP"
                           aria-label="Modo PiP"
                           aria-pressed="false"
                         >
                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
                         </button>
                       </div>
                       <div class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Temporizador</div>
                       <div id="modalTimerDisplay" class="text-4xl font-mono font-bold text-white bg-[#0a0f1a] rounded-xl px-4 py-3 mb-4 tracking-tighter">00:00:00</div>
                       <div id="modalTimerTaskLabel" class="text-[11px] text-slate-400 mb-3 font-semibold">Sin tarea vinculada</div>
                       <button id="modalTimerBtn" onclick="toggleModalTimer()" class="w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-lime-400 text-slate-900 hover:bg-lime-500">
                           <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                           <span>Iniciar</span>
                       </button>
                       <div class="mt-3 grid grid-cols-2 gap-2">
                         <button id="modalTimerSaveBtn" type="button" onclick="saveCurrentTimerLog()" disabled class="px-2 py-1.5 rounded-lg border border-slate-600 bg-slate-700/40 text-[11px] font-bold text-slate-500 cursor-not-allowed transition-colors">Guardar</button>
                         <button type="button" onclick="resetCurrentTimer()" class="px-2 py-1.5 rounded-lg border border-slate-600 bg-slate-700/50 text-[11px] font-bold text-slate-200 hover:bg-slate-700 transition-colors">Reiniciar</button>
                       </div>
                   </div>

                      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total invertido</div>
                        <div id="modalTimerInvestedDisplay" class="text-3xl font-mono font-extrabold text-slate-800 tracking-tight">0d 0h 0m</div>
                        <div class="mt-2 flex items-center justify-between">
                          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Meta prevista</div>
                          <div id="plannedGoalActions" class="flex items-center gap-2">
                            <button type="button" id="plannedGoalEditBtn" onclick="enablePlannedGoalEdit()" class="w-6 h-6 rounded-full border border-slate-200 bg-white text-slate-500 hover:text-slate-700 hover:bg-slate-50 flex items-center justify-center" title="Editar meta prevista" aria-label="Editar meta prevista">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.232-6.232a2.5 2.5 0 013.536 3.536L12.536 14.536a4 4 0 01-1.414.944L8 16l.52-3.122A4 4 0 019 11z"/></svg>
                            </button>
                            <button type="button" id="plannedGoalSaveBtn" onclick="savePlannedGoalEdit()" class="hidden w-6 h-6 rounded-full border border-emerald-200 bg-white text-emerald-600 hover:bg-emerald-50 flex items-center justify-center" title="Guardar meta prevista" aria-label="Guardar meta prevista">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                          </div>
                        </div>
                        <div id="modalTimerPlannedDisplay" class="text-sm font-mono font-bold text-slate-600 mt-1 tracking-tight">0d 0h 0m</div>
                        <div id="modalTimerPlannedEditor" class="hidden mt-2 flex items-center gap-2">
                          <div class="relative">
                            <input id="plannedGoalDays" type="number" min="0" max="999" step="1" value="0" class="w-[72px] h-9 rounded-lg border-slate-200 bg-white text-slate-900 text-base font-bold pr-7 pl-2 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">d</span>
                          </div>
                          <div class="relative">
                            <input id="plannedGoalHours" type="number" min="0" max="99" step="1" value="0" class="w-[66px] h-9 rounded-lg border-slate-200 bg-white text-slate-900 text-base font-bold pr-7 pl-2 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">h</span>
                          </div>
                          <div class="relative">
                            <input id="plannedGoalMinutes" type="number" min="0" max="99" step="1" value="0" class="w-[66px] h-9 rounded-lg border-slate-200 bg-white text-slate-900 text-base font-bold pr-7 pl-2 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">m</span>
                          </div>
                        </div>
                        <div id="modalTimerCompareDisplay" class="text-[11px] font-semibold text-slate-500 mt-1">Comparación: +0d 0h 0m</div>
                      </div>

                     <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                       <div class="flex items-center justify-between mb-3">
                         <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Historial de Tiempo</div>
                         <button type="button" onclick="openAddTimeModal()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-2.5 text-[10px] font-bold text-slate-900 hover:bg-lime-200 transition-colors">
                           <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                           <span>Agregar tiempo</span>
                         </button>
                       </div>
                       <div id="modalTimeLogList" class="max-h-44 overflow-y-auto space-y-2 pr-1"></div>
                   </div>
                   
                   <!-- Metadata -->
                   <div class="space-y-4">
                         <div>
                           <div class="mb-2 flex items-center justify-between gap-3">
                             <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Portada</div>
                             <div class="project-cover-trigger-wrap">
                               <button type="button" onclick="toggleModalCoverGallery()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-[10px] font-bold text-slate-700 hover:bg-slate-50">
                                 <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2 1.6-1.6a2 2 0 012.8 0L20 14m-9-6h.01M5 20h14a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                                 Cambiar
                               </button>
                               <div id="modalCoverGallery" class="project-cover-popover hidden"></div>
                             </div>
                           </div>
                           <div id="modalCoverPreview" class="project-cover-preview min-h-[92px]"></div>
                           <button id="modalCoverClearBtn" type="button" onclick="clearModalCoverImage()" class="mt-2 hidden text-[11px] font-bold text-slate-500 hover:text-rose-600">Quitar imagen de portada</button>
                         </div>

                         <div>
                           <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Responsables</div>
                             <div class="relative" id="responsibleSearchWrap">
                               <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm transition focus-within:border-lime-500 focus-within:ring-2 focus-within:ring-lime-200">
                                 <div id="modalResponsablesList" class="mb-1.5 flex min-h-[1.75rem] flex-wrap items-center gap-1.5"></div>
                                 <div class="flex items-center gap-2">
                                   <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.8 18a7.2 7.2 0 100-14.4 7.2 7.2 0 000 14.4z"/></svg>
                                   <input id="newResponsibleInput" class="block h-7 min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:ring-0" placeholder="Añadir responsable..." onfocus="searchResponsables(this.value, true)" oninput="searchResponsables(this.value)">
                                 </div>
                               </div>
                               <div id="responsibleSearchResults" class="hidden absolute z-20 mt-2 w-full max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                           </div>
                         </div>
                       
                       <div>
                           <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Vencimiento</div>
                           <div class="relative">
                             <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                             </div>
                             <input type="text" id="modalDueDate" readonly placeholder="Seleccionar fecha" class="block w-full h-10 rounded-xl border-slate-200 pl-10 text-sm font-semibold shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer">
                           </div>
                       </div>

                       <div>
                           <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Prioridad</div>
                           <input type="hidden" id="modalPriority" value="Con calma">
                           <div class="grid grid-cols-1 gap-2 min-[420px]:grid-cols-3" id="modalPrioritySelector">
                             <button type="button" data-priority="Con calma" class="priority-chip inline-flex h-10 items-center justify-center gap-1 px-2 rounded-xl text-[11px] font-bold border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                               <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
                               <span class="whitespace-nowrap leading-tight text-center">Con calma</span>
                             </button>
                             <button type="button" data-priority="Atención" class="priority-chip inline-flex h-10 items-center justify-center gap-1 px-2 rounded-xl text-[11px] font-bold border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                               <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                               <span class="leading-tight text-center">Atención</span>
                             </button>
                             <button type="button" data-priority="Urgente" class="priority-chip inline-flex h-10 items-center justify-center gap-1 px-2 rounded-xl text-[11px] font-bold border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 transition-colors">
                               <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                               <span class="leading-tight text-center">Urgente</span>
                             </button>
                           </div>
                       </div>
                   </div>

                   <!-- Actions -->
                   <div class="pt-6 border-t border-slate-200 space-y-2">
                       <button type="button" onclick="archiveProject()" class="w-full py-2 px-3 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 text-left flex items-center gap-2">
                         <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0-12-4 4m4-4 4 4M4 14v3a3 3 0 003 3h10a3 3 0 003-3v-3"/></svg>
                           Archivar Proyecto
                       </button>
                   </div>
               </div>
           </div>
        </div>
      </div>
    </div>
  </div>

  <div id="boardTaskTrashModal" class="fixed inset-0 z-[96] hidden" aria-modal="true" role="dialog" aria-labelledby="board-task-trash-title">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeBoardTaskTrash()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
        <div class="relative mx-auto w-[min(94vw,760px)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-2xl">
          <div class="flex items-start justify-between gap-4 border-b border-slate-100 bg-slate-50 px-5 py-4">
            <div>
              <div id="board-task-trash-title" class="text-xl font-extrabold text-slate-900">Papelera de tareas</div>
              <div id="boardTaskTrashSubtitle" class="mt-1 text-xs text-slate-500">Tareas archivadas de este tablero.</div>
            </div>
            <button type="button" onclick="closeBoardTaskTrash()" class="rounded-full p-2 text-slate-400 transition-colors hover:bg-slate-200/60 hover:text-slate-600" aria-label="Cerrar papelera de tareas">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div id="boardTaskTrashList" class="max-h-[62vh] overflow-y-auto divide-y divide-slate-100"></div>
          <div id="boardTaskTrashEmpty" class="hidden px-6 py-12 text-center">
            <div class="text-sm font-semibold text-slate-600">No hay tareas archivadas</div>
            <div class="mt-1 text-xs text-slate-500">Cuando elimines una tarea del tablero aparecerá aquí.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="timerTaskModal" class="fixed inset-0 z-[85] hidden" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeTimerTaskModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
        <div class="px-5 py-4 border-b border-slate-100">
          <div class="text-lg font-extrabold text-slate-900">¿Qué tarea vas a realizar?</div>
          <div class="text-xs text-slate-500 mt-1">Vincula el temporizador para sumar tiempo a una tarea.</div>
          <div class="mt-3">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-slate-400">
              <span>Paso 2: Seleccionar tarea</span>
              <span>2/2</span>
            </div>
            <div class="mt-1.5 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full rounded-full bg-lime-400" style="width:100%"></div>
            </div>
            <div id="timerTaskProjectLabel" class="mt-1 text-[11px] text-slate-500"></div>
          </div>
        </div>
        <div class="p-5 space-y-3">
          <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Tarea</label>
          <div id="timerTaskList" class="max-h-52 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
          <select id="timerTaskSelect" class="hidden"></select>
          <div class="text-[11px] text-slate-400">Tip: puedes iniciar sin tarea y vincular después.</div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
          <button type="button" onclick="closeTimerTaskModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
          <button id="timerTaskConfirmBtn" type="button" onclick="confirmTimerTaskSelection()" class="px-4 py-2 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-sm font-bold">Iniciar temporizador</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para agregar tiempo manualmente -->
  <div id="addTimeModal" class="fixed inset-0 z-[110] hidden" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeAddTimeModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
        <div class="px-5 py-4 border-b border-slate-100">
          <div class="text-lg font-extrabold text-slate-900">Agregar tiempo manualmente</div>
          <div class="text-xs text-slate-500 mt-1">Si olvidaste iniciar el temporizador, puedes agregarlo aquí.</div>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Proyecto</label>
            <select id="addTimeProjectSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700"></select>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tarea</label>
            <select id="addTimeTaskSelect" class="w-full h-11 rounded-xl border-slate-200 shadow-sm focus:border-lime-500 focus:ring-lime-500 text-base font-medium text-slate-700">
              <option value="">Sin tarea vinculada</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tiempo a agregar</label>
            <div class="grid grid-cols-2 gap-2">
              <div class="relative">
                <input id="addTimeHours" type="number" min="0" max="23" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">h</span>
              </div>
              <div class="relative">
                <input id="addTimeMinutes" type="number" min="0" max="59" step="1" value="0" class="w-full h-11 rounded-xl border-slate-200 bg-white text-slate-900 text-lg font-bold pr-8 pl-3 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold">m</span>
              </div>
            </div>
          </div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
          <button type="button" onclick="closeAddTimeModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
          <button type="button" onclick="saveAddedTime()" class="px-4 py-2 rounded-full bg-lime-400 hover:bg-lime-500 text-slate-900 text-sm font-bold">Guardar tiempo</button>
        </div>
      </div>
    </div>
  </div>

  <div id="quickProjectActionModal" class="fixed inset-0 z-[87] hidden" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/45 backdrop-blur-sm" onclick="closeQuickProjectActionModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-visible">
        <div class="px-5 py-4 border-b border-slate-100 rounded-t-2xl">
          <div id="quickProjectActionTitle" class="text-lg font-extrabold text-slate-900">Selecciona un proyecto</div>
          <div id="quickProjectActionDescription" class="text-xs text-slate-500 mt-1">Elige en qué proyecto quieres continuar.</div>
          <div id="quickProjectTimerStepper" class="mt-3 hidden">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wide text-slate-400">
              <span>Paso 1: Seleccionar proyecto</span>
              <span>1/2</span>
            </div>
            <div class="mt-1.5 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
              <div id="quickProjectTimerProgress" class="h-full rounded-full bg-lime-400 transition-all duration-200" style="width:50%"></div>
            </div>
          </div>
        </div>
        <div class="p-5 space-y-3">
          <div class="relative">
            <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-4.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input id="quickProjectActionSearch" type="text" placeholder="Buscar proyecto..." class="w-full h-11 rounded-xl border-slate-200 bg-slate-50 pl-10 text-sm font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500" oninput="renderQuickProjectActionList(this.value)">
          </div>
          <div id="quickProjectActionList" class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 space-y-2"></div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex justify-end rounded-b-2xl">
          <button type="button" onclick="closeQuickProjectActionModal()" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
        </div>
      </div>
    </div>
  </div>

  <div id="timerSwitchConfirmModal" class="fixed inset-0 z-[86] hidden" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeTimerSwitchConfirm(false)"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-md rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-visible">
        <div class="px-5 py-4 border-b border-slate-100 rounded-t-2xl">
          <div class="text-lg font-extrabold text-slate-900">¿Cambiar de tarea?</div>
          <div class="text-sm text-slate-600 mt-1">¿Estás seguro de cambiar de tarea? Se eliminará el tiempo actual e iniciarás otra tarea.</div>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2 rounded-b-2xl">
          <button type="button" onclick="closeTimerSwitchConfirm(false)" class="px-4 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
          <button type="button" onclick="closeTimerSwitchConfirm(true)" class="px-4 py-2 rounded-full bg-rose-500 hover:bg-rose-600 text-white text-sm font-bold">Sí, cambiar</button>
        </div>
      </div>
    </div>
  </div>

  <div id="taskDetailModal" class="fixed inset-0 z-[90] hidden" aria-modal="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="closeTaskModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-visible rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl h-[85vh] flex flex-col">
          <div class="bg-slate-50 px-5 py-3 border-b border-slate-100 flex items-start justify-between gap-3 flex-none rounded-t-2xl">
            <div class="flex items-start gap-3 w-full min-w-0">
              <button id="taskModalDoneBtn" type="button" onclick="if(currentTaskId) toggleTask(currentTaskId)" class="mt-1 w-7 h-7 rounded-full border-2 border-slate-300 bg-white text-transparent hover:border-lime-300 flex items-center justify-center shrink-0" title="Completar tarea" aria-label="Completar tarea">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
              </button>
              <div class="flex-1 min-w-0">
                <input id="taskModalTitle" class="block w-full bg-transparent border-0 p-0 text-lg md:text-xl leading-tight font-extrabold text-slate-900 focus:ring-0 placeholder-slate-400" placeholder="Nombre de la tarea">
                <div class="mt-2 flex items-center gap-2 flex-wrap">
                  <span id="taskModalProjectLabel" class="inline-flex h-8 max-w-[220px] items-center overflow-hidden whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-600 shadow-sm text-ellipsis">Proyecto</span>
                  <span id="taskModalStageLabel" class="inline-flex h-8 max-w-[160px] items-center overflow-hidden whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-600 shadow-sm text-ellipsis">Columna</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button type="button" onclick="deleteTask()" class="w-10 h-10 rounded-full border border-rose-200 bg-white text-rose-500 hover:bg-rose-50 flex items-center justify-center" title="Eliminar tarea">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
              </button>
              <button type="button" onclick="closeTaskModal()" class="w-10 h-10 rounded-full text-slate-400 hover:text-slate-600 transition-colors hover:bg-slate-200/50 flex items-center justify-center" title="Cerrar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <div class="bg-white px-6 py-3 border-b border-slate-100 flex-none">
            <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1 gap-1">
              <button type="button" data-task-tab="info" onclick="setTaskModalTab('info')" class="task-detail-tab rounded-xl px-4 py-2 text-sm font-bold text-slate-600">Información</button>
              <button type="button" data-task-tab="notes" onclick="setTaskModalTab('notes')" class="task-detail-tab rounded-xl px-4 py-2 text-sm font-bold text-slate-600">Notas</button>
            </div>
          </div>

          <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
            <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scroll">
              <div id="taskModalInfoTab" class="space-y-8">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    Descripción
                  </label>
                  <div class="compact-rich-editor-shell" data-desc-editor-shell>
                    <div class="compact-desc-toolbar" aria-label="Herramientas de descripcion">
                      <div class="compact-desc-format-wrap">
                        <button type="button" class="compact-desc-format-trigger" onmousedown="event.preventDefault()" onclick="toggleCompactDescFormatMenu('taskModalDescription')">
                          <span data-compact-desc-format-label="taskModalDescription">Texto</span>
                          <iconify-icon icon="lucide:chevron-down" width="14" height="14" aria-hidden="true"></iconify-icon>
                        </button>
                        <div class="compact-desc-format-menu hidden" data-compact-desc-format-menu="taskModalDescription">
                          <button type="button" class="compact-desc-format-option is-selected" data-compact-desc-format-option="taskModalDescription" data-format="p" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('taskModalDescription','p')">Texto</button>
                          <button type="button" class="compact-desc-format-option" data-compact-desc-format-option="taskModalDescription" data-format="h1" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('taskModalDescription','h1')">Titulo</button>
                          <button type="button" class="compact-desc-format-option" data-compact-desc-format-option="taskModalDescription" data-format="h2" onmousedown="event.preventDefault()" onclick="applyCompactDescFormat('taskModalDescription','h2')">Subtitulo</button>
                        </div>
                      </div>
                      <button type="button" class="compact-desc-tool" data-compact-desc-cmd="bold" title="Negrita" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'bold')"><iconify-icon icon="lucide:bold" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool" data-compact-desc-cmd="italic" title="Cursiva" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'italic')"><iconify-icon icon="lucide:italic" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool" data-compact-desc-cmd="strikeThrough" title="Tachado" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'strikeThrough')"><iconify-icon icon="lucide:strikethrough" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool is-highlight" data-compact-desc-cmd="highlight" title="Resaltar" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'hiliteColor', '#fef08a')"><iconify-icon icon="lucide:highlighter" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool" data-compact-desc-cmd="checkline" title="Checklist" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'checkline')"><iconify-icon icon="lucide:list-checks" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool" data-compact-desc-cmd="numberline" title="Lista numerada" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'numberline')"><iconify-icon icon="lucide:list-ordered" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                      <button type="button" class="compact-desc-tool" title="Separador" onmousedown="event.preventDefault()" onclick="runCompactDescCommand('taskModalDescription', 'insertHorizontalRule')"><iconify-icon icon="lucide:minus" width="15" height="15" aria-hidden="true"></iconify-icon></button>
                    </div>
                    <div id="taskModalDescription" class="compact-rich-editor" contenteditable="true" spellcheck="true" data-placeholder="Añade una descripción detallada..." onfocus="activateCompactDescEditor('taskModalDescription')" oninput="queueTaskDescriptionAutosave()"></div>
                  </div>
                  <div class="mt-2 text-right">
                    <span id="taskModalDescAutosaveStatus" class="text-xs font-bold text-slate-400">Autoguardado</span>
                  </div>
                </div>

                <div>
                  <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                      <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      Checklist
                    </label>
                    <button id="taskAiSupportTrigger" type="button" onclick="toggleTaskAiSupport()" class="inline-flex h-9 items-center gap-2 rounded-xl border-2 border-fuchsia-300 bg-white px-3 text-xs font-extrabold text-slate-800 shadow-sm hover:bg-fuchsia-50">
                      <svg class="h-3.5 w-3.5 text-fuchsia-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.5l1.8 5.1 5.1 1.8-5.1 1.8L12 16.3l-1.8-5.1-5.1-1.8 5.1-1.8L12 2.5Zm6.2 10.5.9 2.5 2.4.9-2.4.9-.9 2.5-.9-2.5-2.5-.9 2.5-.9.9-2.5ZM5.2 14l.8 2.1 2.1.8-2.1.8-.8 2.1-.8-2.1-2.1-.8 2.1-.8.8-2.1Z"/></svg>
                      Apoyo de IA
                      <span class="hidden rounded-md bg-fuchsia-200 px-1.5 py-0.5 text-[10px] font-black text-fuchsia-900 sm:inline">NOVEDAD</span>
                    </button>
                  </div>
                  <div id="taskAiSupportPanel" class="fixed hidden w-[min(20rem,calc(100vw-1.5rem))] overflow-hidden rounded-2xl border border-fuchsia-200 bg-white shadow-2xl ring-1 ring-fuchsia-100" style="z-index:2147482500;">
                    <div class="flex items-center justify-between gap-2 border-b border-fuchsia-100 bg-white px-3 py-2">
                      <div class="flex min-w-0 items-center gap-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-500">
                          <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.5l1.8 5.1 5.1 1.8-5.1 1.8L12 16.3l-1.8-5.1-5.1-1.8 5.1-1.8L12 2.5Zm6.2 10.5.9 2.5 2.4.9-2.4.9-.9 2.5-.9-2.5-2.5-.9 2.5-.9.9-2.5ZM5.2 14l.8 2.1 2.1.8-2.1.8-.8 2.1-.8-2.1-2.1-.8 2.1-.8.8-2.1Z"/></svg>
                        </span>
                        <div class="min-w-0">
                          <div class="truncate text-sm font-black text-slate-900">Apoyo de IA</div>
                          <div class="truncate text-[10px] font-semibold text-slate-400">Checklist de la tarea</div>
                        </div>
                      </div>
                      <button type="button" onclick="toggleTaskAiSupport(false)" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar apoyo de IA">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                    </div>
                    <div id="taskAiSupportMessages" class="max-h-40 space-y-2 overflow-y-auto bg-slate-50 px-2.5 py-2.5 text-sm">
                      <div class="rounded-xl bg-white px-3 py-2 text-[11px] font-semibold leading-snug text-slate-500 shadow-sm">Dime cómo ajustar el checklist: agregar, reescribir o crear tareas.</div>
                    </div>
                    <div class="flex gap-2 border-t border-slate-100 bg-white p-2">
                      <input id="taskAiSupportInput" class="min-w-0 flex-1 rounded-lg border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-800 focus:border-fuchsia-300 focus:ring-fuchsia-200" placeholder="Ej. divide esto en pasos..." onkeydown="if(event.key==='Enter'){ event.preventDefault(); sendTaskAiSupport(); }">
                      <button id="taskAiSupportSendBtn" type="button" onclick="sendTaskAiSupport()" class="rounded-lg bg-fuchsia-100 px-3 py-2 text-xs font-extrabold text-slate-900 hover:bg-fuchsia-200">Enviar</button>
                    </div>
                  </div>
                  <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-[11px] font-semibold text-slate-500">Progreso</span>
                      <span id="taskSubtaskProgressLabel" class="text-[11px] font-bold text-lime-700">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                      <div id="taskSubtaskProgress" class="h-2 rounded-full" style="width:0%;background-color:#84cc16"></div>
                    </div>
                  </div>
                  <div id="taskSubtasksList" class="space-y-2"></div>
                  <div id="taskSubtaskComposer" class="mt-3 flex flex-col sm:flex-row gap-2">
                    <input id="newSubtaskInput" class="flex-1 h-10 rounded-lg border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="Añadir elemento" onkeydown="if(event.key==='Enter'){ event.preventDefault(); addSubtask({ refocus: true }); }">
                    <button type="button" onclick="addSubtask()" class="h-10 px-4 rounded-lg text-slate-900 font-bold text-sm border border-lime-200 sm:min-w-[96px]" style="background-color:#dff8a7;">Añadir</button>
                  </div>
                </div>

                <div>
                  <div class="mb-3 flex items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                      <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a4 4 0 10-5.657-5.657L5.757 10.757a6 6 0 108.486 8.486L20 13.486"/></svg>
                      Archivos Adjuntos
                    </label>
                    <button type="button" onclick="document.getElementById('taskFileInput')?.click()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-extrabold text-slate-600 shadow-sm hover:bg-slate-50">Añadir</button>
                  </div>
                  <div id="taskFilesList" class="project-file-grid mb-3"></div>
                  <div id="taskFileDropzone" class="rounded-xl border-2 border-dashed border-slate-200 bg-white/70 px-4 py-6 text-center transition-colors hover:bg-slate-50 cursor-pointer" onclick="document.getElementById('taskFileInput')?.click()">
                    <input type="file" id="taskFileInput" class="hidden" multiple onchange="handleTaskFileUpload(this.files); this.value='';">
                    <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <div class="mt-2 text-xs font-bold text-slate-500">Haz clic o arrastra archivos aquí</div>
                    <div class="mt-1 text-[11px] font-semibold text-slate-400">La primera imagen será portada.</div>
                  </div>
                </div>
              </div>

              <div id="taskModalNotesTab" class="hidden space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                  <div class="text-sm font-bold text-slate-700 mb-3">Notas</div>
                  <div id="taskModalPipelineNotes" class="space-y-2"></div>
                  <div class="mt-3 flex flex-col gap-2">
                    <textarea id="taskModalNewNoteInput" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-2 focus:ring-lime-200 placeholder:text-slate-400" placeholder="Añadir nota..."></textarea>
                    <div class="flex justify-end">
                      <button type="button" onclick="addTaskModalNote()" class="h-10 px-4 rounded-lg border border-lime-200 bg-lime-200 text-sm font-bold text-slate-900 hover:bg-lime-300">Guardar nota</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="w-full md:w-80 bg-slate-50 border-l border-slate-100 p-6 space-y-6 overflow-y-auto">
              <div class="group relative bg-[#111729] rounded-xl shadow-sm border border-[#1f2a47] p-4 text-center">
                <div class="absolute top-3 right-3 flex items-center gap-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0">
                  <button type="button" onclick="openTimerFullscreen()" class="w-8 h-8 rounded-full border border-slate-600 text-slate-300 hover:text-white hover:bg-[#1f2a47] flex items-center justify-center transition-colors" title="Pantalla completa" aria-label="Pantalla completa">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9V5a1 1 0 011-1h4m10 5V5a1 1 0 00-1-1h-4M4 15v4a1 1 0 001 1h4m10-5v4a1 1 0 01-1 1h-4"/></svg>
                  </button>
                  <button type="button" onclick="toggleTimerMiniPip()" class="w-8 h-8 rounded-full border border-slate-600 text-slate-300 hover:text-white hover:bg-[#1f2a47] flex items-center justify-center transition-colors" title="Modo PiP" aria-label="Modo PiP">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
                  </button>
                </div>
                <div class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Temporizador</div>
                <div id="taskTimerDisplay" class="text-4xl font-mono font-bold text-white bg-[#0a0f1a] rounded-xl px-4 py-3 mb-4 tracking-tighter">00:00:00</div>
                <div id="taskTimerTaskLabel" class="text-[11px] text-slate-400 mb-3 font-semibold truncate">Tarea actual</div>
                <button id="taskTimerBtn" type="button" onclick="if(currentProjectId && currentTaskId) toggleTaskTimer(currentProjectId, currentTaskId)" class="w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-lime-400 text-slate-900 hover:bg-lime-500">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  <span>Iniciar</span>
                </button>
                <div class="mt-3 grid grid-cols-2 gap-2">
                  <button id="taskTimerSaveBtn" type="button" onclick="saveCurrentTimerLog()" disabled class="px-2 py-1.5 rounded-lg border border-slate-600 bg-slate-700/40 text-[11px] font-bold text-slate-500 cursor-not-allowed transition-colors">Guardar</button>
                  <button type="button" onclick="resetCurrentTimer()" class="px-2 py-1.5 rounded-lg border border-slate-600 bg-slate-700/50 text-[11px] font-bold text-slate-200 hover:bg-slate-700 transition-colors">Reiniciar</button>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total invertido</div>
                <div id="taskTimerInvestedDisplay" class="text-3xl font-mono font-extrabold text-slate-800 tracking-tight">0d 0h 0m</div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                  <div class="text-xs uppercase tracking-wider font-extrabold text-slate-400">Historial de tiempo</div>
                  <button type="button" onclick="openTaskAddTimeModal()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-2.5 text-[10px] font-bold text-slate-900 hover:bg-lime-200 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Agregar tiempo</span>
                  </button>
                </div>
                <div id="taskTimeHistoryList" class="space-y-2 max-h-64 overflow-y-auto pr-1"></div>
              </div>

              <div id="taskModalEditFields" class="space-y-4">
                <div>
                  <label class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Fecha de inicio</label>
                  <div class="relative mt-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                    <input type="text" id="taskModalStart" readonly class="w-full h-11 rounded-xl border-slate-200 bg-white pl-9 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500 cursor-pointer" placeholder="Seleccionar inicio">
                  </div>
                </div>
                <div>
                  <label class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Fecha de finalización</label>
                  <div class="relative mt-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </span>
                    <input type="text" id="taskModalEnd" readonly class="w-full h-11 rounded-xl border-slate-200 bg-white pl-9 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500 cursor-pointer" placeholder="Seleccionar fin">
                  </div>
                </div>
                <div>
                  <label class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Prioridad</label>
                  <div class="relative mt-1">
                    <span id="taskModalPriorityIcon" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-amber-600">
                      <svg class="w-4 h-4 shrink-0 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    </span>
                    <select id="taskModalPriority" class="w-full h-11 rounded-xl border-slate-200 bg-white pl-9 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500">
                      <option value="Vencido" disabled>Vencido</option>
                      <option value="Con calma">Con calma</option>
                      <option value="Atención">Atención</option>
                      <option value="Urgente">Urgente</option>
                    </select>
                  </div>
                </div>
                <div>
                  <label class="text-[11px] uppercase tracking-wider font-bold text-slate-500">Encargados</label>
                  <input type="hidden" id="taskModalOwnerIds" value="">
                  <div class="mt-1 space-y-2">
                    <div class="relative" id="taskOwnerSearchWrap">
                      <div class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 z-10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.8 18a7.2 7.2 0 100-14.4 7.2 7.2 0 000 14.4z"/></svg>
                      </div>
                      <input id="taskOwnerSearchInput" class="block w-full h-11 rounded-xl border-slate-200 bg-white pl-11 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="Buscar usuario..." onfocus="searchTaskOwners(this.value, true)" oninput="searchTaskOwners(this.value)">
                      <div id="taskOwnerSearchResults" class="hidden absolute z-20 mt-2 w-full max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                    </div>
                    <div id="taskOwnersList" class="space-y-2"></div>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border p-4" style="background-color:#101729;border-color:#233055;">
                <div class="text-xs uppercase tracking-wider font-extrabold text-lime-300 mb-3">Resumen</div>
                <div id="taskModalMeta" class="text-sm"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="timerFullscreenPanel" class="fixed inset-0 z-[80] hidden bg-slate-950/90 backdrop-blur-sm">
    <div class="absolute top-4 right-4 flex items-center gap-2">
      <button type="button" onclick="openTimerFullscreen()" class="w-10 h-10 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Pantalla completa" aria-label="Pantalla completa">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9V5a1 1 0 011-1h4m10 5V5a1 1 0 00-1-1h-4M4 15v4a1 1 0 001 1h4m10-5v4a1 1 0 01-1 1h-4"/></svg>
      </button>
      <button type="button" data-advanced-control onclick="openPinnedTimerPip()" class="w-10 h-10 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Modo PiP" aria-label="Modo PiP">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
      </button>
      <button type="button" onclick="closeTimerFullscreen()" class="w-10 h-10 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Cerrar" aria-label="Cerrar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    <div class="h-full w-full flex flex-col items-center justify-center px-4 pb-6">
      <div class="w-full max-w-6xl">
        <div class="text-center mb-4">
          <div id="timerFsProject" class="text-xl md:text-3xl font-extrabold text-white mb-1">Tarea</div>
          <div id="timerFsClient" class="text-xs md:text-base text-slate-300 mb-2">Proyecto · Cliente</div>
          <div class="flex flex-col md:flex-row items-center justify-center gap-3">
            <div id="timerFsDisplay" class="text-4xl md:text-6xl leading-none font-mono font-extrabold text-lime-300 tracking-tight">00:00:00</div>
            <div class="flex items-center justify-center gap-2">
              <button id="timerFsPauseBtn" type="button" onclick="togglePinnedTimerRun()" class="w-11 h-11 rounded-full border border-white/20 bg-white/10 text-white hover:bg-white/20 flex items-center justify-center" title="Pausar temporizador" aria-label="Pausar temporizador">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
              </button>
              <button id="timerFsSaveBtn" type="button" onclick="savePinnedTimerLog()" class="px-4 py-2 rounded-xl border border-white/20 bg-white/10 text-white text-sm font-bold hover:bg-white/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Guardar
              </button>
                <button id="timerFsDeleteBtn" type="button" onclick="deletePinnedTimerEntry()" class="px-4 py-2 rounded-xl border border-rose-300/40 bg-rose-500/10 text-rose-100 text-sm font-bold hover:bg-rose-500/20 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/></svg>
                  Eliminar
                </button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white/5 rounded-2xl p-4 text-left min-h-[320px]">
            <div class="text-lg uppercase tracking-[0.2em] text-slate-100 font-extrabold mb-3">Sub tareas</div>
            <div id="timerFsSubtasksList" class="space-y-2 max-h-64 overflow-y-auto pr-1"></div>
            <div class="mt-2 flex items-center gap-2">
              <input id="timerFsNewSubtaskInput" class="flex-1 h-9 rounded-lg border border-white/20 bg-white/10 px-2.5 text-xs text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-lime-300/40" placeholder="Añadir sub tarea" onkeydown="if(event.key==='Enter') addTimerFullscreenSubtask()">
              <button type="button" onclick="addTimerFullscreenSubtask()" class="h-9 px-3 rounded-lg bg-lime-300 text-slate-900 text-xs font-bold hover:bg-lime-200">Añadir</button>
            </div>
          </div>

          <div class="bg-white/5 rounded-2xl p-4 text-left min-h-[320px]">
            <div class="text-lg uppercase tracking-[0.2em] text-slate-100 font-extrabold mb-3">Notas</div>
            <div id="timerFsNotesList" class="space-y-1.5 max-h-56 overflow-y-auto pr-1"></div>
            <div class="mt-2 space-y-2">
              <textarea id="timerFsNewNoteInput" rows="2" class="w-full rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-lime-300/40" placeholder="Escribe una nota de pipeline..."></textarea>
              <div class="flex justify-end">
                <button type="button" onclick="addTimerFullscreenNote()" class="h-9 px-3 rounded-lg bg-lime-300 text-slate-900 text-xs font-bold hover:bg-lime-200">Guardar nota</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="timerMiniPip" class="fixed bottom-4 right-4 z-[75] hidden w-64 rounded-2xl border border-slate-700 bg-slate-900/95 text-white shadow-2xl">
    <div class="px-3 py-2 border-b border-slate-700 flex items-center justify-between">
      <div class="min-w-0">
        <div id="timerPipProject" class="text-xs font-bold truncate">Proyecto</div>
        <div id="timerPipClient" class="text-[10px] text-slate-300 truncate">Cliente</div>
      </div>
      <div class="flex items-center gap-2">
        <button id="timerPipToggleBtn" type="button" onclick="toggleModalTimer()" class="w-7 h-7 rounded-full border border-slate-500 text-lime-300 hover:bg-slate-800 flex items-center justify-center" title="Iniciar/Pausar" aria-label="Iniciar/Pausar temporizador">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </button>
        <button type="button" onclick="toggleTimerMiniPip()" class="text-slate-300 hover:text-white" title="Cerrar PiP" aria-label="Cerrar PiP">✕</button>
      </div>
    </div>
    <div id="timerPipDisplay" class="px-3 py-3 text-3xl font-mono font-extrabold text-lime-300 text-center">00:00:00</div>
  </div>

  <canvas id="timerPipCanvas" width="520" height="520" class="fixed -bottom-20 -right-20 w-1 h-1 opacity-0 pointer-events-none"></canvas>
  <video id="timerPipVideo" playsinline muted autoplay></video>

  <div id="proyectos-tareas" class="hidden bg-white rounded-2xl shadow border p-4 mt-4">
    <div class="mb-3 rounded-2xl border border-slate-200 bg-slate-50/95 px-3 py-2">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="ml-auto flex items-center gap-2 text-xs">
          <span id="tasksQuickFiltersStatus" class="font-bold text-slate-600">Filtros activos: 0</span>
          <button id="tasksQuickClearBtn" type="button" onclick="resetQuickFilters('tareas')" class="hidden h-7 rounded-md border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-slate-600 hover:bg-slate-100">Limpiar</button>
          <span id="tasksQuickSimpleBadge" class="hidden rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">Modo simple activo</span>
        </div>
      </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <div class="filter-pill min-w-[220px]">
        <svg class="h-4 w-4 filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input id="tasksSearchInput" type="text" placeholder="Buscar tarea o proyecto" class="w-full" oninput="setGlobalTaskSearch(this.value)">
      </div>
      <button type="button" data-task-filter="all" class="global-task-filter is-active inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-slate-200 bg-slate-900 text-white">Todas</button>
      <button type="button" data-task-filter="urgent" class="global-task-filter inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-rose-200 bg-rose-50 text-rose-700">
        <svg class="h-4 w-4 shrink-0 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <span>Urgente</span>
      </button>
      <button type="button" data-task-filter="attention" class="global-task-filter inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-amber-200 bg-amber-50 text-amber-700">
        <svg class="h-4 w-4 shrink-0 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
        <span>Atención</span>
      </button>
      <button type="button" data-task-filter="calm" class="global-task-filter inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-emerald-200 bg-emerald-50 text-emerald-700">
        <svg class="h-4 w-4 shrink-0 self-center" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
        <span>Con calma</span>
      </button>
      <button type="button" data-task-filter="overdue" class="global-task-filter inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-slate-200 bg-slate-50 text-slate-600">Vencidas</button>
      <button type="button" data-task-filter="completed" class="global-task-filter inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold border border-violet-200 bg-violet-50 text-violet-700">Completadas</button>
    </div>
    <div id="globalTasksBoard" class="space-y-4"></div>
  </div>

  <div id="proyectos-lista" class="hidden bg-white rounded-2xl shadow border mt-4 overflow-visible">
    <div class="px-4 py-3 border-b border-slate-200 bg-white">
      <div class="sticky top-2 z-20 mb-3 rounded-2xl border border-slate-200 bg-slate-50/95 backdrop-blur px-3 py-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="quickAddTaskFromCurrentView()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-3 text-xs font-bold text-slate-900 hover:bg-lime-200">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <span>Tarea</span>
            </button>
            <button type="button" onclick="quickStartTimerFromCurrentView()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-100">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Iniciar timer</span>
            </button>
          </div>
          <div class="flex items-center gap-2 text-xs">
            <span id="listQuickFiltersStatus" class="font-bold text-slate-600">Filtros activos: 0</span>
            <button id="listQuickClearBtn" type="button" onclick="resetQuickFilters('lista')" class="hidden h-7 rounded-md border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-slate-600 hover:bg-slate-100">Limpiar</button>
            <span id="listQuickSimpleBadge" class="hidden rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">Modo simple activo</span>
          </div>
        </div>
      </div>
      <div id="listFilterBar" class="flex flex-wrap items-center gap-2">
        <div class="filter-pill min-w-[220px]">
          <svg class="h-4 w-4 filter-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input id="listProjectSearchInput" type="text" placeholder="Buscar proyecto o cliente" class="w-full" oninput="setListProjectSearch(this.value)">
        </div>
        <div class="relative" data-list-filter-wrap="priority">
          <button id="listFilterPriorityBtn" type="button" onclick="toggleListFilterDropdown('priority')" class="inline-flex h-9 min-w-[185px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-bold text-slate-700 shadow-sm hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86l-7.55 13.08A2 2 0 004.47 20h15.06a2 2 0 001.73-3.06L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span id="listFilterPriorityLabel" class="flex-1 truncate text-left">Prioridad</span>
            <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div id="listFilterPriorityMenu" class="hidden absolute left-0 top-11 z-30 w-[280px] overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl">
            <div class="border-b border-slate-100 p-2">
              <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-4.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="listFilterPrioritySearch" type="text" placeholder="Buscar prioridad..." class="w-full border-0 bg-transparent p-0 text-sm leading-none text-slate-700 placeholder:text-slate-400 focus:ring-0" oninput="updateListFilterOptions('priority', this.value)">
              </div>
            </div>
            <div id="listFilterPriorityOptions" class="max-h-64 overflow-y-auto p-1"></div>
          </div>
        </div>

        <div class="relative" data-list-filter-wrap="date">
          <button id="listFilterDateBtn" type="button" onclick="toggleListFilterDropdown('date')" class="inline-flex h-9 min-w-[185px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-bold text-slate-700 shadow-sm hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span id="listFilterDateLabel" class="flex-1 truncate text-left">Fecha</span>
            <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div id="listFilterDateMenu" class="hidden absolute left-0 top-11 z-30 w-[280px] overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl">
            <div class="border-b border-slate-100 p-2">
              <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-4.4a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input id="listFilterDateSearch" type="text" placeholder="Buscar fecha..." class="w-full border-0 bg-transparent p-0 text-sm leading-none text-slate-700 placeholder:text-slate-400 focus:ring-0" oninput="updateListFilterOptions('date', this.value)">
              </div>
            </div>
            <div id="listFilterDateOptions" class="max-h-64 overflow-y-auto p-1"></div>
          </div>
        </div>

        <div class="relative" data-list-filter-wrap="sort">
          <button id="listFilterSortBtn" type="button" onclick="toggleListFilterDropdown('sort')" class="inline-flex h-9 min-w-[185px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-bold text-slate-700 shadow-sm hover:bg-slate-50">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16m0 0-3-3m3 3 3-3M17 20V4m0 0-3 3m3-3 3 3"/></svg>
            <span id="listFilterSortLabel" class="flex-1 truncate text-left">Orden</span>
            <svg class="h-4 w-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div id="listFilterSortMenu" class="hidden absolute left-0 top-11 z-30 w-[280px] overflow-hidden rounded-xl border border-slate-100 bg-white shadow-xl">
            <div id="listFilterSortOptions" class="max-h-64 overflow-y-auto p-1"></div>
          </div>
        </div>

        <button type="button" onclick="clearListFilters()" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3.5 text-[13px] font-bold text-slate-600 shadow-sm hover:bg-slate-50">Limpiar</button>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm table-auto">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Proyecto</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Cliente</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium">Prioridad</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[12rem]">Progreso</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[12rem]">Responsables</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Fecha límite</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Acciones</th>
          </tr>
        </thead>
        <tbody id="projectListBody" class="divide-y"></tbody>
      </table>
    </div>
    <div id="projectListPagination" class="hidden px-4 py-3 border-t border-slate-200 bg-white"></div>
  </div>

  <div id="proyectos-calendario" class="hidden bg-white rounded-2xl shadow border p-6 mt-4">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <div>
        <div class="text-xs font-bold text-lime-700 bg-lime-100 px-2 py-0.5 rounded-full inline-block mb-1">Calendario</div>
        <div id="calLabel" class="text-2xl font-extrabold text-slate-900"></div>
        <div class="text-xs text-slate-500 mt-1">Vencimientos y entregas de proyectos</div>
      </div>
      <div class="flex items-center gap-2">
        <button id="prevMonth" class="px-3 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">‹</button>
        <button id="nextMonth" class="px-3 py-2 rounded-full border text-sm font-semibold text-slate-600 hover:bg-slate-50">›</button>
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <div class="grid grid-cols-7 gap-2 text-[10px] font-bold text-slate-400 mb-3">
          <div class="text-center">L</div>
          <div class="text-center">M</div>
          <div class="text-center">X</div>
          <div class="text-center">J</div>
          <div class="text-center">V</div>
          <div class="text-center">S</div>
          <div class="text-center">D</div>
        </div>
        <div id="calendarGrid" class="grid grid-cols-7 gap-3 text-xs"></div>
      </div>
      <div class="space-y-4">
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-bold text-slate-900">Google Calendar</div>
            <div class="text-[10px] font-semibold {{ !empty($settings['google_calendar_enabled']) ? 'text-emerald-600 bg-emerald-100' : 'text-slate-400 bg-slate-200' }} px-2 py-0.5 rounded-full">
              {{ !empty($settings['google_calendar_enabled']) ? 'Conectado' : 'Desconectado' }}
            </div>
          </div>
          <p class="text-xs text-slate-500 mb-3">Sincroniza entregas y vencimientos con tu calendario.</p>
          <div class="flex flex-wrap gap-2">
            <a href="{{ route('settings.integrations') }}" class="px-3 py-2 rounded-full border text-xs font-semibold text-slate-600 hover:bg-white">Configurar</a>
            @if(!empty($settings['google_calendar_id']))
              <a href="https://calendar.google.com/calendar/u/0/r?cid={{ urlencode($settings['google_calendar_id']) }}" target="_blank" class="px-3 py-2 rounded-full bg-lime-400 text-slate-900 text-xs font-bold hover:bg-lime-500">Abrir</a>
            @endif
          </div>
        </div>
        @if(!empty($settings['google_calendar_enabled']) && !empty($settings['google_calendar_embed_url']))
        <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
          <div class="px-4 py-3 border-b border-slate-100 text-xs font-bold text-slate-600">Vista Google</div>
          <iframe class="w-full h-80" src="{{ $settings['google_calendar_embed_url'] }}" style="border:0" loading="lazy"></iframe>
        </div>
        @endif
      </div>
    </div>
  </div>

  <div id="proyectos-archivados" class="hidden bg-white rounded-2xl shadow border mt-4 overflow-visible">
    <div class="overflow-x-auto">
      <table class="w-full text-sm table-auto">
        <thead>
          <tr>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Proyecto</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium whitespace-nowrap">Cliente</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium">Prioridad</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[12rem]">Progreso</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[12rem]">Responsables</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Fecha límite</th>
            <th class="px-4 py-3 text-left text-slate-500 font-medium min-w-[10rem]">Acciones</th>
          </tr>
        </thead>
        <tbody id="projectListBodyArchived" class="divide-y divide-slate-100"></tbody>
      </table>
    </div>
    <div id="archivedProjectsEmpty" class="px-4 py-8 text-center">
      <svg class="w-16 h-16 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div class="text-slate-500 font-semibold">No hay proyectos archivados</div>
      <div class="text-sm text-slate-400 mt-1">Los proyectos que archives aparecerán aquí.</div>
    </div>
  </div>

  <script>
    window.addProjectTo = window.addProjectTo || function(){};
    let stages = JSON.parse(document.getElementById('stagesData').dataset.stages || '[]');
    const urlParams = new URLSearchParams(location.search);
    let currentClienteId = urlParams.get('cliente_id') || '';
    let openProjectFromQuery = urlParams.get('open_project') || '';
    let openTaskFromQuery = urlParams.get('open_task') || '';
    let openNewProjectFromQuery = urlParams.get('new_project') === '1';
    let openHeaderTimerFromQuery = urlParams.get('header_timer') === '1';
    let animateProgressBarsOnce = false;

    function clearOpenProjectQueryParam() {
      const nextUrl = new URL(window.location.href);
      const hasOpenProject = nextUrl.searchParams.has('open_project');
      const hasOpenTask = nextUrl.searchParams.has('open_task');
      const hasNewProject = nextUrl.searchParams.has('new_project');
      if (!hasOpenProject && !hasOpenTask && !hasNewProject) return;
      nextUrl.searchParams.delete('open_project');
      nextUrl.searchParams.delete('open_task');
      nextUrl.searchParams.delete('new_project');
      window.history.replaceState({}, '', nextUrl);
    }

    function syncProjectClientFilterUI() {
      const select = document.getElementById('clientSelector');
      const label = document.getElementById('projectClientFilterLabel');
      const button = document.getElementById('projectClientFilterButton');
      cleanupProjectClientNativeSelectEnhancement();
      const currentValue = String(select?.value || '');
      const selectedText = select?.selectedOptions?.[0]?.textContent?.trim() || 'Todos los Clientes';
      if (label) label.textContent = selectedText;
      document.querySelectorAll('[data-project-client-option]').forEach((option) => {
        const isActive = String(option.getAttribute('data-project-client-option') || '') === currentValue;
        option.classList.toggle('is-active', isActive);
        option.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      document.querySelectorAll('[data-project-client-check]').forEach((check) => {
        check.classList.toggle('hidden', String(check.getAttribute('data-project-client-check') || '') !== currentValue);
      });
      button?.setAttribute('aria-label', `Filtrar por cliente: ${selectedText}`);
    }

    function cleanupProjectClientNativeSelectEnhancement() {
      const filter = document.getElementById('projectClientFilter');
      const select = document.getElementById('clientSelector');
      if (!filter || !select) return;

      const wrapper = select.closest('.app-select-wrap');
      if (wrapper && wrapper.parentElement === filter) {
        filter.insertBefore(select, wrapper);
        wrapper.remove();
      }

      delete select.dataset.appSelectEnhanced;
      select.classList.remove('app-native-select');
      select._appSelectWrapper = null;
      select._appSelectTrigger = null;
      select._appSelectLabel = null;
      select._appSelectMenu = null;
      select._appSelectSearch = null;
      select._appSelectOptions = null;
    }

    function closeProjectClientFilter() {
      const menu = document.getElementById('projectClientFilterMenu');
      const button = document.getElementById('projectClientFilterButton');
      menu?.classList.add('hidden');
      button?.setAttribute('aria-expanded', 'false');
    }

    function toggleProjectClientFilter(event = null) {
      event?.preventDefault?.();
      event?.stopPropagation?.();
      const menu = document.getElementById('projectClientFilterMenu');
      const button = document.getElementById('projectClientFilterButton');
      if (!menu || !button) return;
      const willOpen = menu.classList.contains('hidden');
      menu.classList.toggle('hidden', !willOpen);
      button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (willOpen) syncProjectClientFilterUI();
    }

    function selectProjectClientFilter(value = '') {
      const select = document.getElementById('clientSelector');
      if (!select) return;
      select.value = String(value || '');
      syncProjectClientFilterUI();
      closeProjectClientFilter();
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // Set initial selector value
    if (currentClienteId) {
        document.getElementById('clientSelector').value = currentClienteId;
    }
    syncProjectClientFilterUI();
    
    document.getElementById('clientSelector').addEventListener('change', (e) => {
        currentClienteId = e.target.value;
        syncProjectClientFilterUI();
        const url = new URL(window.location);
        if (currentClienteId) {
            url.searchParams.set('cliente_id', currentClienteId);
        } else {
            url.searchParams.delete('cliente_id');
        }
        window.history.pushState({}, '', url);
        loadData();
    });

    let projects = [];
    const PROJECT_LIST_PAGE_SIZE = 20;
    let projectListCurrentPage = 1;
    let archivedProjects = [];
    let projectModalReadOnly = false;
    let focusMode = false;
    let currentProjectId = null; // For modal
    let newProjectCoverColor = '#0f766e|#bef264';
    let newProjectCoverImage = '';
    let newProjectCoverFile = null;
    let newProjectCoverObjectUrl = '';
    const projectCoverPickerExpanded = {
      newPhotos: false,
      newColors: false,
      modalPhotos: false,
      modalColors: false,
    };
    const PROJECT_COVER_PALETTES = [
      ['#0f766e', '#bef264'],
      ['#0f4c81', '#ec4899'],
      ['#7c3aed', '#f472b6'],
      ['#f97316', '#ef4444'],
      ['#2563eb', '#22d3ee'],
      ['#111827', '#64748b'],
      ['#12355b', '#1d4ed8'],
      ['#0891b2', '#22d3ee'],
      ['#0f172a', '#334155'],
      ['#14532d', '#84cc16'],
      ['#be123c', '#fb7185'],
      ['#581c87', '#c084fc'],
    ];
    const PROJECT_COVER_PRESETS = [
      { name: 'Neon suave', url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Prisma', url: 'https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Vidrio azul', url: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Luz líquida', url: 'https://images.unsplash.com/photo-1557672172-298e090bd0f1?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Aurora digital', url: 'https://images.unsplash.com/photo-1635776062360-af423602aff3?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Cromo', url: 'https://images.unsplash.com/photo-1604076913837-52ab5629fba9?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Bruma violeta', url: 'https://images.unsplash.com/photo-1579547621706-1a9c79d5c9f1?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Ondas', url: 'https://images.unsplash.com/photo-1558591710-4b4a1ae0f04d?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Malla azul', url: 'https://images.unsplash.com/photo-1614851099362-9adf73ccebe9?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Tinta', url: 'https://images.unsplash.com/photo-1574169208507-84376144848b?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Pulso', url: 'https://images.unsplash.com/photo-1620121684840-edffcfc4b878?auto=format&fit=crop&w=1200&q=80' },
      { name: 'Líneas', url: 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=1200&q=80' },
    ];
    let modalDescAutosaveTimer = null;
    let taskDescAutosaveTimer = null;
    let taskDetailsAutosaveTimer = null;
    let projectDescriptionExpanded = false;
    const pendingProjectDescriptions = {};
    let currentTaskId = null; // For task detail modal
    let taskAddInFlight = false;
    let taskAiChecklistWorking = false;
    let taskAiAnimatedSubtaskIds = new Set();
    let timerInterval = null; // For modal timer
    let taskTimerInterval = null;
    let headerTimerInterval = null;
    let isDraggingCard = false;
    let dragCardId = null;
    let kanbanPanActive = false;
    let kanbanPanStartX = 0;
    let kanbanPanStartLeft = 0;
    let kanbanPanMoved = false;
    let kanbanPanSuppressClickUntil = 0;
    let modalDuePicker = null;
    let pipStreamReady = false;
    let pipRenderInterval = null;

    function looksLikeHtml(value = '') {
      return /<\/?[a-z][\s\S]*>/i.test(String(value || ''));
    }

    function normalizeDescriptionPlainText(value = '') {
      return String(value || '')
        .replace(/\r\n?/g, '\n')
        .replace(/\u00a0/g, ' ')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n[ \t]+/g, '\n')
        .replace(/([.!?])\s*(\d+[.)]\s+)/g, '$1\n\n$2')
        .replace(/([^\n])\s+([–—]\s+)/g, (match, before, dash) => {
          if (/[,:;]$/.test(before)) return match;
          return `${before}\n${dash}`;
        });
    }

    function plainTextToDescriptionHtml(value = '') {
      const text = normalizeDescriptionPlainText(value);
      if (!text.trim()) return '';
      const lines = text.split('\n');
      const chunks = [];
      let orderedBuffer = [];
      let unorderedBuffer = [];
      const flushLists = () => {
        if (unorderedBuffer.length) {
          chunks.push(`<ul>${unorderedBuffer.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`);
          unorderedBuffer = [];
        }
        if (orderedBuffer.length) {
          chunks.push(`<ol>${orderedBuffer.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ol>`);
          orderedBuffer = [];
        }
      };

      lines.forEach((line) => {
        const clean = line.trim();
        const checklist = clean.match(/^\[(x|X|\s)?\]\s+(.+)$/);
        const bullet = clean.match(/^[-*•]\s+(.+)$/);
        const ordered = clean.match(/^\d+[.)]\s+(.+)$/);
        if (checklist) {
          flushLists();
          chunks.push(`<div class="note-checkline${String(checklist[1] || '').toLowerCase() === 'x' ? ' is-checked' : ''}"><input type="checkbox" class="note-checkbox" contenteditable="false"${String(checklist[1] || '').toLowerCase() === 'x' ? ' checked' : ''}><span contenteditable="true">${escapeHtml(checklist[2])}</span></div>`);
          return;
        }
        if (bullet) {
          orderedBuffer = [];
          unorderedBuffer.push(bullet[1]);
          return;
        }
        if (ordered) {
          unorderedBuffer = [];
          orderedBuffer.push(ordered[1]);
          return;
        }
        flushLists();
        if (!clean) {
          chunks.push('<div><br></div>');
          return;
        }
        chunks.push(`<p>${escapeHtml(line)}</p>`);
      });
      flushLists();
      return chunks.join('');
    }

    function normalizeClipboardDescriptionHtml(html = '', plainText = '') {
      if (!String(html || '').trim()) return plainTextToDescriptionHtml(plainText);
      const template = document.createElement('template');
      template.innerHTML = String(html || '');
      template.content.querySelectorAll('script,style,meta,link,iframe,object,embed,svg,canvas,img,video,audio,table,colgroup,thead,tbody,tfoot,tr,td,th').forEach((node) => {
        if (['TD', 'TH'].includes(node.tagName)) {
          node.replaceWith(document.createTextNode(`${node.textContent || ''} `));
          return;
        }
        node.remove();
      });
      template.content.querySelectorAll('*').forEach((node) => {
        const tag = node.tagName;
        if (tag === 'H1') {
          const paragraph = document.createElement('p');
          paragraph.innerHTML = node.innerHTML;
          node.replaceWith(paragraph);
          return;
        }
        if (/^H[4-6]$/.test(tag)) {
          const h2 = document.createElement('h2');
          h2.innerHTML = node.innerHTML;
          node.replaceWith(h2);
          return;
        }
        if (tag === 'FONT') {
          const span = document.createElement('span');
          span.innerHTML = node.innerHTML;
          node.replaceWith(span);
          return;
        }
        const sourceStyle = String(node.getAttribute('style') || '');
        const isBoldStyle = /font-weight\s*:\s*(bold|bolder|[6-9]00)/i.test(sourceStyle);
        const isItalicStyle = /font-style\s*:\s*italic/i.test(sourceStyle);
        const isUnderlineStyle = /text-decoration[^;]*underline/i.test(sourceStyle);
        const isStrikeStyle = /text-decoration[^;]*(line-through|strike)/i.test(sourceStyle);
        const isHeadingLike = ['P', 'DIV', 'SPAN'].includes(tag)
          && isBoldStyle
          && (node.textContent || '').trim().length <= 120
          && !/[.!?]$/.test((node.textContent || '').trim());
        if (isHeadingLike) {
          const h2 = document.createElement('h2');
          h2.innerHTML = node.innerHTML;
          node.replaceWith(h2);
          return;
        }
        if (isBoldStyle && !['STRONG', 'B', 'H1', 'H2', 'H3'].includes(tag)) {
          const strong = document.createElement('strong');
          strong.innerHTML = node.innerHTML;
          node.innerHTML = '';
          node.appendChild(strong);
        }
        if (isItalicStyle && !['EM', 'I'].includes(tag)) {
          const em = document.createElement('em');
          em.innerHTML = node.innerHTML;
          node.innerHTML = '';
          node.appendChild(em);
        }
        if (isUnderlineStyle && tag !== 'U') {
          const underline = document.createElement('u');
          underline.innerHTML = node.innerHTML;
          node.innerHTML = '';
          node.appendChild(underline);
        }
        if (isStrikeStyle && !['S', 'STRIKE'].includes(tag)) {
          const strike = document.createElement('s');
          strike.innerHTML = node.innerHTML;
          node.innerHTML = '';
          node.appendChild(strike);
        }
        Array.from(node.attributes).forEach((attr) => {
          const name = attr.name.toLowerCase();
          const value = attr.value || '';
          const isHighlight = name === 'style' && /background(?:-color)?\s*:\s*(?:rgb\(254,\s*240,\s*138\)|#?fef08a|yellow)/i.test(value);
          if (isHighlight) {
            node.setAttribute('style', 'background-color:#fef08a');
          } else {
            node.removeAttribute(attr.name);
          }
        });
      });
      return sanitizeDescriptionHtml(template.innerHTML);
    }

    function sanitizeDescriptionHtml(value = '') {
      const raw = String(value || '').trim();
      if (!raw) return '';
      const source = looksLikeHtml(raw) ? raw : plainTextToDescriptionHtml(raw);
      const template = document.createElement('template');
      template.innerHTML = source;
      const allowedTags = new Set(['B', 'STRONG', 'I', 'EM', 'S', 'STRIKE', 'U', 'MARK', 'P', 'DIV', 'BR', 'UL', 'OL', 'LI', 'H1', 'H2', 'H3', 'HR', 'SPAN', 'INPUT']);
      const walk = (node) => {
        Array.from(node.childNodes).forEach((child) => {
          if (child.nodeType === Node.ELEMENT_NODE) {
            if (!allowedTags.has(child.tagName)) {
              child.replaceWith(document.createTextNode(child.textContent || ''));
              return;
            }
            Array.from(child.attributes).forEach((attr) => {
              const name = attr.name.toLowerCase();
              const value = attr.value || '';
              const isSafeStyle = name === 'style' && /background(?:-color)?\s*:\s*(?:rgb\(254,\s*240,\s*138\)|#?fef08a|yellow)/i.test(value);
              const isSafeCheckboxAttr = child.tagName === 'INPUT' && ['type', 'checked', 'contenteditable'].includes(name);
              if (name !== 'class' && !isSafeStyle && !isSafeCheckboxAttr) child.removeAttribute(attr.name);
              if (child.tagName === 'INPUT' && child.getAttribute('type') !== 'checkbox') child.remove();
              if (name === 'class' && !/^note-|^compact-/.test(value)) child.removeAttribute(attr.name);
            });
          }
          walk(child);
        });
      };
      walk(template.content);
      return template.innerHTML.trim();
    }

    function getCompactDescValue(editorId) {
      const editor = document.getElementById(editorId);
      if (!editor) return '';
      const html = sanitizeDescriptionHtml(editor.innerHTML || '');
      return hasCompactDescMeaningfulContent(html) ? html : '';
    }

    function hasCompactDescMeaningfulContent(html = '') {
      const template = document.createElement('template');
      template.innerHTML = sanitizeDescriptionHtml(html);
      template.content.querySelectorAll('br, input, hr').forEach((node) => node.remove());
      const text = (template.content.textContent || '').replace(/\u00a0/g, ' ').trim();
      return text.length > 0;
    }

    function setCompactDescValue(editorId, value = '') {
      const editor = document.getElementById(editorId);
      if (!editor) return;
      editor.innerHTML = sanitizeDescriptionHtml(value);
      syncCompactDescEditorState(editorId);
    }

    function activateCompactDescEditor(editorId) {
      const editor = document.getElementById(editorId);
      const shell = editor?.closest?.('[data-desc-editor-shell]');
      shell?.classList.add('is-active');
      resetCompactDescToolbarState(editorId);
      requestAnimationFrame(() => updateCompactDescFormatState(editorId));
    }

    function isCompactDescEditorFocused(editorId) {
      const editor = document.getElementById(editorId);
      return !!editor && (document.activeElement === editor || editor.contains(document.activeElement));
    }

    function setCompactDescEditable(editorId, editable = true) {
      const editor = document.getElementById(editorId);
      const shell = editor?.closest?.('[data-desc-editor-shell]');
      if (!editor) return;
      editor.contentEditable = editable ? 'true' : 'false';
      editor.classList.toggle('cursor-not-allowed', !editable);
      editor.classList.toggle('opacity-70', !editable);
      shell?.querySelectorAll('button,select').forEach((control) => { control.disabled = !editable; });
    }

    function getCompactDescEditor(editorId) {
      return document.getElementById(editorId);
    }

    function isSelectionInsideCompactDescEditor(editorId) {
      const editor = getCompactDescEditor(editorId);
      const selection = window.getSelection?.();
      if (!editor || !selection || !selection.rangeCount) return false;
      const anchor = selection.anchorNode;
      const focus = selection.focusNode;
      return !!((anchor && editor.contains(anchor)) || (focus && editor.contains(focus)));
    }

    function resetCompactDescToolbarState(editorId) {
      const label = document.querySelector(`[data-compact-desc-format-label="${editorId}"]`);
      if (label) label.textContent = 'Texto';
      document.querySelectorAll(`[data-compact-desc-format-option="${editorId}"]`).forEach((option) => {
        option.classList.toggle('is-selected', option.getAttribute('data-format') === 'p');
      });
      document.querySelectorAll(`[data-desc-editor-shell] [data-compact-desc-cmd]`).forEach((button) => {
        const toolbarEditor = button.closest('[data-desc-editor-shell]')?.querySelector('.compact-rich-editor')?.id;
        if (toolbarEditor === editorId) button.classList.remove('is-active');
      });
    }

    function placeCompactDescCaret(target) {
      if (!target) return;
      const range = document.createRange();
      const selection = window.getSelection();
      range.selectNodeContents(target);
      range.collapse(false);
      selection.removeAllRanges();
      selection.addRange(range);
    }

    function getCompactDescCurrentBlock(editorId) {
      const editor = getCompactDescEditor(editorId);
      const selection = window.getSelection();
      if (!editor || !selection || !selection.rangeCount) return null;
      let node = selection.anchorNode;
      if (!node) return null;
      if (node.nodeType === Node.TEXT_NODE && node.parentElement === editor) {
        const wrapper = compactDescPlainLine(node.textContent ? escapeHtml(node.textContent) : '<br>');
        editor.replaceChild(wrapper, node);
        placeCompactDescCaret(wrapper);
        return wrapper;
      }
      if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;
      while (node && node !== editor) {
        if (node.nodeType === Node.ELEMENT_NODE && (node.classList?.contains('note-checkline') || node.classList?.contains('note-numberline') || /^(P|DIV|H1|H2|H3|LI|UL|OL|HR)$/.test(node.tagName))) return node;
        node = node.parentElement;
      }
      if (editor.childNodes.length && Array.from(editor.childNodes).some((child) => child.nodeType === Node.TEXT_NODE && String(child.textContent || '').trim())) {
        const wrapper = compactDescPlainLine(editor.innerHTML || '<br>');
        editor.innerHTML = '';
        editor.appendChild(wrapper);
        placeCompactDescCaret(wrapper);
        return wrapper;
      }
      return null;
    }

    function compactDescPlainLine(html = '<br>') {
      const line = document.createElement('div');
      line.innerHTML = html || '<br>';
      return line;
    }

    function compactDescCheckline(html = '<br>') {
      const line = document.createElement('div');
      line.className = 'note-checkline';
      line.innerHTML = '<input type="checkbox" class="note-checkbox" contenteditable="false"><span contenteditable="true"></span>';
      line.querySelector('span').innerHTML = html || '<br>';
      return line;
    }

    function compactDescNumberline(number = 1, html = '<br>') {
      const line = document.createElement('div');
      line.className = 'note-numberline';
      line.innerHTML = `<span class="note-number-marker" contenteditable="false">${number}.</span><span class="note-number-content" contenteditable="true"></span>`;
      line.querySelector('.note-number-content').innerHTML = html || '<br>';
      return line;
    }

    function extractCompactDescBlockHtml(block) {
      if (!block) return '<br>';
      const clone = block.cloneNode(true);
      clone.querySelectorAll('input, .note-number-marker').forEach((node) => node.remove());
      const content = clone.querySelector('.note-number-content') || clone.querySelector('.note-checkline span') || clone;
      return content.innerHTML?.trim() || '<br>';
    }

    function insertCompactDescBlock(editorId, block) {
      const editor = getCompactDescEditor(editorId);
      if (!editor || !block) return;
      const current = getCompactDescCurrentBlock(editorId);
      if (current && current !== editor && editor.contains(current)) {
        current.insertAdjacentElement('afterend', block);
      } else {
        editor.appendChild(block);
      }
      const target = block.querySelector('.note-number-content') || block.querySelector('.note-checkline span') || block;
      placeCompactDescCaret(target);
    }

    function transformCompactDescBlock(editorId, kind) {
      const editor = getCompactDescEditor(editorId);
      if (!editor) return;
      const current = getCompactDescCurrentBlock(editorId);
      const sourceHtml = extractCompactDescBlockHtml(current);
      let next;

      if (kind === 'checkline') {
        if (current?.classList?.contains('note-checkline')) next = compactDescPlainLine(sourceHtml);
        else next = compactDescCheckline(sourceHtml);
      } else if (kind === 'numberline') {
        if (current?.classList?.contains('note-numberline')) next = compactDescPlainLine(sourceHtml);
        else next = compactDescNumberline(1, sourceHtml);
      }

      if (!next) return;
      if (current && current !== editor && editor.contains(current)) current.replaceWith(next);
      else editor.appendChild(next);
      syncCompactDescEditorState(editorId);
      const target = next.querySelector('.note-number-content') || next.querySelector('.note-checkline span') || next;
      placeCompactDescCaret(target);
    }

    function renumberCompactDescNumberLines(editorId) {
      const editor = getCompactDescEditor(editorId);
      if (!editor) return;
      editor.querySelectorAll('.note-numberline').forEach((line, index) => {
        let marker = line.querySelector('.note-number-marker');
        if (!marker) {
          marker = document.createElement('span');
          marker.className = 'note-number-marker';
          marker.contentEditable = 'false';
          line.prepend(marker);
        }
        marker.textContent = `${index + 1}.`;
      });
    }

    function syncCompactDescChecklistState(editorId) {
      const editor = getCompactDescEditor(editorId);
      if (!editor) return;
      editor.querySelectorAll('.note-checkline').forEach((line) => {
        const checkbox = line.querySelector('.note-checkbox');
        line.classList.toggle('is-checked', !!checkbox?.checked);
      });
    }

    function syncCompactDescEditorState(editorId) {
      renumberCompactDescNumberLines(editorId);
      syncCompactDescChecklistState(editorId);
      updateCompactDescFormatState(editorId);
    }

    function queueCompactDescAutosave(editorId) {
      if (editorId === 'modalDesc') {
        queueDescriptionAutosave();
        refreshProjectDescriptionClamp();
      } else if (editorId === 'taskModalDescription') {
        queueTaskDescriptionAutosave();
      }
    }

    function closeCompactDescFormatMenus(exceptEditorId = '') {
      document.querySelectorAll('[data-compact-desc-format-menu]').forEach((menu) => {
        if (menu.getAttribute('data-compact-desc-format-menu') !== exceptEditorId) menu.classList.add('hidden');
      });
    }

    function deactivateCompactDescEditors() {
      document.querySelectorAll('[data-desc-editor-shell].is-active').forEach((shell) => {
        const editor = shell.querySelector('.compact-rich-editor');
        if (editor?.id) flushCompactDescEditor(editor.id);
        const selection = window.getSelection?.();
        if (selection && editor?.contains?.(selection.anchorNode)) selection.removeAllRanges();
        if (document.activeElement === editor) editor.blur();
        shell.classList.remove('is-active');
      });
      closeCompactDescFormatMenus();
    }

    function flushCompactDescEditor(editorId) {
      const editor = getCompactDescEditor(editorId);
      if (!editor) return;
      const value = getCompactDescValue(editorId);
      if (value === '' && editor.innerHTML !== '') {
        editor.innerHTML = '';
        syncCompactDescEditorState(editorId);
      }

      if (editorId === 'modalDesc') {
        if (projectModalReadOnly || !currentProjectId) return;
        const projectId = String(currentProjectId);
        pendingProjectDescriptions[projectId] = value;
        const project = projects.find((item) => String(item.id) === projectId);
        if (project) project.descripcion = value;
        refreshProjectDescriptionClamp();
        clearTimeout(modalDescAutosaveTimer);
        setDescriptionAutosaveStatus('saving');
        saveDescriptionAutosave(projectId, value);
        return;
      }

      if (editorId === 'taskModalDescription') {
        if (!currentProjectId || !currentTaskId) return;
        const task = getCurrentTask();
        if (task) task.descripcion = value;
        clearTimeout(taskDescAutosaveTimer);
        setTaskDescriptionAutosaveStatus('saving');
        saveTaskDescriptionAutosave(value);
      }
    }

    function toggleCompactDescFormatMenu(editorId) {
      activateCompactDescEditor(editorId);
      const menu = document.querySelector(`[data-compact-desc-format-menu="${editorId}"]`);
      if (!menu) return;
      const willOpen = menu.classList.contains('hidden');
      closeCompactDescFormatMenus(editorId);
      menu.classList.toggle('hidden', !willOpen);
      getCompactDescEditor(editorId)?.focus();
    }

    function applyCompactDescFormat(editorId, format) {
      closeCompactDescFormatMenus();
      runCompactDescCommand(editorId, 'formatBlock', format);
    }

    function updateCompactDescFormatState(editorId) {
      const labels = { p: 'Texto', h1: 'Titulo', h2: 'Subtitulo' };
      const selectionInsideEditor = isSelectionInsideCompactDescEditor(editorId);
      const block = selectionInsideEditor ? getCompactDescCurrentBlock(editorId) : null;
      const tag = ['H1', 'H2'].includes(block?.tagName) ? block.tagName.toLowerCase() : 'p';
      const label = document.querySelector(`[data-compact-desc-format-label="${editorId}"]`);
      if (label) label.textContent = labels[tag] || 'Texto';
      document.querySelectorAll(`[data-compact-desc-format-option="${editorId}"]`).forEach((option) => {
        option.classList.toggle('is-selected', option.getAttribute('data-format') === tag);
      });

      let bold = false;
      let italic = false;
      let strike = false;
      let highlight = false;
      if (selectionInsideEditor) {
        try {
          bold = document.queryCommandState('bold');
          italic = document.queryCommandState('italic');
          strike = document.queryCommandState('strikeThrough');
          const backColor = String(document.queryCommandValue('backColor') || '').toLowerCase();
          highlight = backColor.includes('254') || backColor.includes('fef08a') || backColor.includes('yellow');
        } catch (_) {}
      }
      const inChecklist = !!block?.classList?.contains('note-checkline');
      const inNumberline = !!block?.classList?.contains('note-numberline');
      document.querySelectorAll(`[data-desc-editor-shell] [data-compact-desc-cmd]`).forEach((button) => {
        const toolbarEditor = button.closest('[data-desc-editor-shell]')?.querySelector('.compact-rich-editor')?.id;
        if (toolbarEditor !== editorId) return;
        const cmd = button.getAttribute('data-compact-desc-cmd');
        const active = (cmd === 'bold' && bold)
          || (cmd === 'italic' && italic)
          || (cmd === 'strikeThrough' && strike)
          || (cmd === 'highlight' && highlight)
          || (cmd === 'checkline' && inChecklist)
          || (cmd === 'numberline' && inNumberline);
        button.classList.toggle('is-active', !!active);
      });
    }

    function runCompactDescCommand(editorId, command, value = null) {
      const editor = document.getElementById(editorId);
      if (!editor || editor.contentEditable === 'false') return;
      activateCompactDescEditor(editorId);
      editor.focus();
      try {
        if (command === 'checkline' || command === 'numberline') {
          transformCompactDescBlock(editorId, command);
        } else if (command === 'formatBlock') {
          document.execCommand('formatBlock', false, `<${value || 'p'}>`);
        } else {
          document.execCommand(command, false, value);
        }
      } catch (_) {
        if (command === 'formatBlock') document.execCommand('formatBlock', false, value || 'p');
      }
      syncCompactDescEditorState(editorId);
      queueCompactDescAutosave(editorId);
    }

    function insertCompactDescHtml(editorId, html = '') {
      const editor = getCompactDescEditor(editorId);
      if (!editor || editor.contentEditable === 'false') return;
      activateCompactDescEditor(editorId);
      editor.focus();
      const safeHtml = sanitizeDescriptionHtml(html);
      if (!safeHtml) return;
      try {
        document.execCommand('insertHTML', false, safeHtml);
      } catch (_) {
        const selection = window.getSelection();
        const range = selection?.rangeCount ? selection.getRangeAt(0) : null;
        if (!range) return;
        const fragment = document.createRange().createContextualFragment(safeHtml);
        range.deleteContents();
        range.insertNode(fragment);
      }
      syncCompactDescEditorState(editorId);
      queueCompactDescAutosave(editorId);
    }

    function clipboardFileName(file, index = 0) {
      const cleanName = String(file?.name || '').trim();
      if (cleanName) return cleanName;
      const mime = String(file?.type || '').toLowerCase();
      const ext = mime.includes('png') ? 'png'
        : mime.includes('jpeg') || mime.includes('jpg') ? 'jpg'
        : mime.includes('webp') ? 'webp'
        : mime.includes('gif') ? 'gif'
        : 'bin';
      return `pegado-${new Date().toISOString().replace(/[:.]/g, '-')}-${index + 1}.${ext}`;
    }

    function getClipboardFiles(event) {
      const clipboard = event.clipboardData || window.clipboardData;
      if (!clipboard) return [];
      const files = [];
      Array.from(clipboard.items || []).forEach((item) => {
        if (item.kind !== 'file') return;
        const file = item.getAsFile?.();
        if (file) files.push(file);
      });
      if (!files.length) {
        Array.from(clipboard.files || []).forEach((file) => {
          if (file) files.push(file);
        });
      }
      return files.map((file, index) => {
        if (String(file.name || '').trim()) return file;
        try {
          return new File([file], clipboardFileName(file, index), {
            type: file.type || 'application/octet-stream',
            lastModified: file.lastModified || Date.now(),
          });
        } catch (_) {
          return file;
        }
      });
    }

    function isElementOpen(element) {
      return !!element && !element.classList.contains('hidden');
    }

    function getClipboardUploadContext() {
      const taskModal = document.getElementById('taskDetailModal');
      if (isElementOpen(taskModal) && currentProjectId && currentTaskId) return 'task';
      const projectModal = document.getElementById('projectModal');
      if (isElementOpen(projectModal) && currentProjectId && !projectModalReadOnly) return 'project';
      return '';
    }

    async function handleClipboardFilePaste(event) {
      const files = getClipboardFiles(event);
      if (!files.length) return false;
      const context = getClipboardUploadContext();
      if (!context) return false;
      event.preventDefault();
      if (window.showNotification) {
        window.showNotification(files.length > 1 ? 'Subiendo archivos pegados...' : 'Subiendo archivo pegado...', 'info');
      }
      if (context === 'task') await handleTaskFileUpload(files);
      else await handleModalFileUpload(files);
      return true;
    }

    window.activateCompactDescEditor = activateCompactDescEditor;
    window.runCompactDescCommand = runCompactDescCommand;
    window.toggleCompactDescFormatMenu = toggleCompactDescFormatMenu;
    window.applyCompactDescFormat = applyCompactDescFormat;

    document.addEventListener('input', (event) => {
      const editor = event.target?.closest?.('.compact-rich-editor');
      if (!editor?.id) return;
      syncCompactDescEditorState(editor.id);
    });

    document.addEventListener('keyup', (event) => {
      const editor = event.target?.closest?.('.compact-rich-editor');
      if (!editor?.id) return;
      updateCompactDescFormatState(editor.id);
    });

    document.addEventListener('mouseup', (event) => {
      const editor = event.target?.closest?.('.compact-rich-editor');
      if (!editor?.id) return;
      updateCompactDescFormatState(editor.id);
    });

    document.addEventListener('change', (event) => {
      const checkbox = event.target?.closest?.('.compact-rich-editor .note-checkbox');
      if (!checkbox) return;
      const editor = checkbox.closest('.compact-rich-editor');
      if (!editor?.id) return;
      syncCompactDescChecklistState(editor.id);
      queueCompactDescAutosave(editor.id);
    });

    document.addEventListener('paste', (event) => {
      if (getClipboardFiles(event).length) {
        handleClipboardFilePaste(event);
        return;
      }
      const editor = event.target?.closest?.('.compact-rich-editor');
      if (!editor?.id) return;
      event.preventDefault();
      const clipboard = event.clipboardData || window.clipboardData;
      const html = clipboard?.getData('text/html') || '';
      const text = clipboard?.getData('text/plain') || '';
      const normalized = normalizeClipboardDescriptionHtml(html, text);
      insertCompactDescHtml(editor.id, normalized);
    });

    document.addEventListener('mousedown', (event) => {
      if (event.target?.closest?.('[data-desc-editor-shell]')) return;
      deactivateCompactDescEditors();
      if (event.target?.closest?.('.compact-desc-format-wrap')) return;
      closeCompactDescFormatMenus();
    });
    let pipVideoTrack = null;
    let pipLastDisplayValue = '00:00:00';
    let suppressPipPlaybackSync = false;
    let responsibleSearchDebounce = null;
    let responsibleSearchAbort = null;
    let taskOwnerSearchDebounce = null;
    let taskOwnerSearchAbort = null;
    let subtaskOwnerSearchDebounce = null;
    let subtaskOwnerSearchAbort = null;
    let taskStartPicker = null;
    let taskEndPicker = null;
    let subtaskDuePicker = null;
    let currentProjectModalTab = 'info';
    let currentTaskModalTab = 'info';
    let currentProjectEditingNoteId = null;
    let isProjectNoteComposerOpen = false;
    let currentTaskModalEditing = true;
    let currentTaskEditingNoteId = null;
    let currentEditingSubtaskId = null;
    let newProjectStartPicker = null;
    let newProjectDuePicker = null;
    let modalBackdropIgnoreUntil = 0;
    let pendingTimerProjectId = null;
    let pendingTimerTaskId = '';
    let pendingTimerResolver = null;
    let quickProjectActionMode = '';
    let pendingTimerSwitchResolver = null;
    let pinnedTimerProjectId = null;
    let pinnedTimerTaskId = null;
    let timerFsShowAllSubtasks = false;
    let timerFsShowAllNotes = false;
    let headerTimerLastProjectId = null;
    let headerTimerLastSeconds = 0;
    let kanbanDragPreviewEl = null;
    let currentTaskView = ['kanban', 'tareas', 'lista', 'archivados'].includes(urlParams.get('view')) ? urlParams.get('view') : 'kanban';
    let globalTaskFilter = 'all';
    let globalTasksClientPage = 1;
    const GLOBAL_TASKS_CLIENTS_PER_PAGE = 3;
    const GLOBAL_TASKS_PER_PROJECT_PAGE = 5;
    const GLOBAL_TASKS_COLLAPSED_KEY = 'infocus_global_tasks_collapsed_projects_v1';
    const globalTasksProjectPages = {};
    const globalTasksCollapsedProjects = new Set();
    let globalTaskSearchQuery = '';
    let listProjectSearchQuery = '';
    let listFilterPriority = '';
    let listFilterDate = 'all';
    let listFilterSort = 'newest';
    let listFilterOpenMenu = null;
    const listFilterSearch = { priority: '', date: '', sort: '' };
    const responsibleCatalogById = {};

    function markCalendarInteraction(cooldownMs = 800) {
      modalBackdropIgnoreUntil = Date.now() + cooldownMs;
    }

    document.addEventListener('pointerdown', (event) => {
      if (event.target?.closest?.('.flatpickr-calendar, .flatpickr-input')) {
        markCalendarInteraction();
      }
    }, true);
    document.addEventListener('mousedown', (event) => {
      if (event.target?.closest?.('.flatpickr-calendar, .flatpickr-input')) {
        markCalendarInteraction();
      }
    }, true);
    document.addEventListener('touchstart', (event) => {
      if (event.target?.closest?.('.flatpickr-calendar, .flatpickr-input')) {
        markCalendarInteraction(1000);
      }
    }, true);
    document.addEventListener('click', (event) => {
      if (event.target?.closest?.('.flatpickr-calendar, .flatpickr-input')) {
        markCalendarInteraction();
      }
    }, true);
    document.addEventListener('pointerdown', (event) => {
      if (!currentEditingSubtaskId) return;
      if (event.target?.closest?.('[data-subtask-editor], .flatpickr-calendar, .flatpickr-input')) return;
      saveSubtaskDetails({ closeAfter: true });
    });
    document.addEventListener('pointerdown', (event) => {
      const panel = document.getElementById('taskAiSupportPanel');
      if (!panel || panel.classList.contains('hidden')) return;
      if (event.target?.closest?.('#taskAiSupportPanel, #taskAiSupportTrigger')) return;
      panel.classList.add('hidden');
    });
    window.addEventListener('resize', () => {
      positionTaskAiSupportPanel();
      if (document.body.classList.contains('project-board-open')) {
        fitProjectBoardViewport();
      }
    });
    window.addEventListener('scroll', positionTaskAiSupportPanel, true);
    const responsibleCatalogByName = {};
    const LIST_PRIORITY_OPTIONS = [
      { value: '', label: 'Todas' },
      { value: 'Urgente', label: 'Urgente' },
      { value: 'Atención', label: 'Atención' },
      { value: 'Con calma', label: 'Con calma' },
    ];
    const LIST_DATE_OPTIONS = [
      { value: 'all', label: 'Todas' },
      { value: 'overdue', label: 'Vencidas' },
      { value: 'today', label: 'Vencen hoy' },
      { value: 'next7', label: 'Próximos 7 días' },
      { value: 'next30', label: 'Próximos 30 días' },
      { value: 'no-date', label: 'Sin fecha' },
    ];
    const LIST_SORT_OPTIONS = [
      { value: 'newest', label: 'Más recientes' },
      { value: 'oldest', label: 'Más antiguos' },
    ];
    const currentUserDisplayName = @json(optional(auth()->user())->name ?: session('user.name'));
    const TIMER_HISTORY_PREFIX = 'project_timer_history_v2_';
    const TIMER_RESET_PREFIX = 'project_timer_reset_v1_';
    const TASK_TIMER_RESET_PREFIX = 'project_task_timer_reset_v1_';
    const GLOBAL_TIMER_STATE_KEY = 'infocus_global_timer_state_v1';
    const POMODORO_STATE_KEY = 'tdah_pomodoro_state_v2';
    const clientesData = @json($clientes);
    const initialBoardSlug = @json($boardSlug ?? '');
    let isProjectsSimpleMode = true;

    const kanban = document.getElementById('kanban');
    const globalTasksBoard = document.getElementById('globalTasksBoard');
    const projectGlobalBackButton = document.getElementById('global-header-back-btn');
    let currentBoardProjectId = urlParams.get('board') || '';
    let pendingBoardSlug = initialBoardSlug || '';
    let boardTaskDrag = null;
    let boardColumnDrag = null;
    let boardColumnPreviewEl = null;
    let boardColumnPreviewOrder = null;
    let boardTaskComposerFocusStage = '';
    let boardPendingTaskSeq = 0;
    const boardPendingTasks = new Map();
    const boardRecentTaskIds = new Set();
    let boardStageEditingName = '';
    let boardAiWorking = null;
    const DEFAULT_PROJECT_TASK_STAGES = ['Por hacer', 'En proceso', 'Revisión', 'Terminado'];
    window.history.replaceState({
      view: pendingBoardSlug || currentBoardProjectId ? 'project-board' : 'project-boards',
      boardSlug: pendingBoardSlug || currentBoardProjectId || '',
    }, '', window.location.href);
    window.addEventListener('popstate', () => {
      currentClienteId = new URLSearchParams(window.location.search || '').get('cliente_id') || '';
      const clientSelector = document.getElementById('clientSelector');
      if (clientSelector && clientSelector.value !== currentClienteId) clientSelector.value = currentClienteId;
      syncProjectClientFilterUI();
      closeProjectClientFilter();
      syncProjectBoardFromLocation();
    });

    // --- Dynamic Stages Logic ---
    function stageColumn(title, count) {
      return `
      <div class="flex flex-col w-96 h-full max-h-full rounded-2xl bg-gradient-to-b from-slate-50 to-slate-100/50 border border-slate-200/60 shadow-sm hover:shadow-md transition-all stage-column" data-stage="${title}">
        <!-- Header -->
        <div class="flex-none p-4 flex items-center justify-between group border-b border-slate-200/50">
          <div class="flex items-center gap-3 flex-1">
            <div class="font-bold text-slate-900 text-sm uppercase tracking-widest cursor-text outline-none bg-gradient-to-r from-lime-300 to-lime-200 px-3 py-1.5 rounded-lg shadow-sm border border-lime-300/50 hover:shadow-md transition-all" contenteditable="true" onblur="renameStage('${title}', this.innerText)" onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur()}">${title}</div>
            <span class="bg-white px-2.5 py-1 rounded-lg text-xs font-extrabold text-slate-600 shadow-sm border border-slate-100">${count}</span>
          </div>
          <div data-advanced-control class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity gap-1">
              <button class="p-1.5 rounded-lg hover:bg-slate-200/50 text-slate-400 hover:text-slate-600 transition-colors" title="Mover left" onclick="moveStage('${title}', -1)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              </button>
              <button class="p-1.5 rounded-lg hover:bg-slate-200/50 text-slate-400 hover:text-slate-600 transition-colors" title="Mover right" onclick="moveStage('${title}', 1)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
              <button class="p-1.5 rounded-lg hover:bg-lime-100/70 text-slate-400 hover:text-lime-700 transition-colors" title="Añadir columna al lado" onclick="addStageAdjacent('${title}')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              </button>
              <button class="p-1.5 rounded-lg hover:bg-rose-100/60 text-slate-400 hover:text-rose-600 transition-colors" title="Eliminar columna" onclick="deleteStage('${title}')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
          </div>
        </div>
        
        <!-- Cards Container -->
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3 custom-scroll drag-container min-h-0" data-stage="${title}">
          <!-- Cards go here -->
        </div>
        
      </div>`;
    }

    async function manageStageColumns() {
      const action = prompt('Escribe "agregar" para crear una columna nueva o "eliminar" para borrar una columna existente.');
      if (!action) return;

      const normalized = String(action).trim().toLowerCase();
      if (normalized === 'agregar') {
        const name = prompt('Nombre de la nueva columna:');
        const cleanName = String(name || '').trim();
        if (!cleanName) return;
        if (stages.includes(cleanName)) {
          if (window.showNotification) window.showNotification('Esa columna ya existe', 'error');
          return;
        }
        stages.push(cleanName);
        await saveStages();
        return;
      }

      if (normalized === 'eliminar') {
        const name = prompt('Escribe el nombre exacto de la columna que quieres eliminar:');
        const cleanName = String(name || '').trim();
        if (!cleanName || !stages.includes(cleanName)) return;
        await deleteStage(cleanName);
      }
    }

    async function addStageAdjacent(currentName) {
      const baseName = 'NUEVA';
      let cleanName = baseName;
      let counter = 2;
      while (stages.includes(cleanName)) {
        cleanName = `${baseName} ${counter}`;
        counter += 1;
      }

      const idx = stages.findIndex((stage) => stage === currentName);
      if (idx === -1) {
        stages.push(cleanName);
      } else {
        stages.splice(idx + 1, 0, cleanName);
      }
      await saveStages();
    }

    async function deleteStage(name) {
      if (!name || !stages.includes(name)) return;
      if (stages.length <= 1) {
        if (window.showNotification) window.showNotification('Debe quedar al menos una columna en el Kanban', 'error');
        return;
      }

      const confirmed = confirm(`Si eliminas el kanban "${name}" se archivarán todos los proyectos dentro, ¿estás seguro?`);
      if (!confirmed) return;

      stages = stages.filter((stage) => stage !== name);
      await saveStages(null, null, { deletedName: name, archiveProjects: true });
    }

    async function renameStage(oldName, newName) {
        newName = newName.trim();
        if (!newName || newName === oldName) {
            loadData(); // Revert visual change
            return;
        }
        const idx = stages.indexOf(oldName);
        if (idx !== -1) {
            stages[idx] = newName;
            await saveStages(oldName, newName);
        }
    }

    async function moveStage(name, direction) {
        const idx = stages.indexOf(name);
        if (idx === -1) return;
        const newIdx = idx + direction;
        if (newIdx >= 0 && newIdx < stages.length) {
            const temp = stages[idx];
            stages[idx] = stages[newIdx];
            stages[newIdx] = temp;
            await saveStages();
        }
    }

    async function saveStages(oldName, newName, options = {}) {
        let body = { stages: stages };
        if (oldName && newName) {
            body['old_name'] = oldName;
            body['new_name'] = newName;
        }
      if (options.deletedName) {
        body['deleted_name'] = options.deletedName;
        body['archive_projects'] = !!options.archiveProjects;
      }
        await fetch('/api/proyectos/stages', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify(body)
        });
        loadData();
    }


    function normalizePriority(value) {
      const v = String(value || '').trim().toLowerCase();
      if (v === 'urgente' || v === 'alta') return 'Urgente';
      if (v === 'atención' || v === 'atencion' || v === 'media' || v === 'importante') return 'Atención';
      if (v === 'con calma' || v === 'baja') return 'Con calma';
      return 'Con calma';
    }

    function getProjectTaskStats(project) {
      const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
      const total = tasks.length;
      const done = tasks.filter((task) => !!task?.done).length;
      const pct = total === 0 ? 0 : Math.round((done / total) * 100);
      return { total, done, pending: Math.max(total - done, 0), pct };
    }

    function progressBarColor(pct) {
      if (pct >= 100) return '#10b981'; // emerald — completado
      if (pct >= 70)  return '#84cc16'; // lime — va bien
      if (pct >= 31)  return '#f59e0b'; // amber — en proceso
      return '#f43f5e';                 // rose — atrasado
    }

    function projectCard(p) {
      const taskStats = getProjectTaskStats(p);
      const prog = taskStats.pct;
      const normalizedPriority = getEffectiveProjectPriority(p);
      const stripColorMap = { 'Vencido': '#334155', 'Urgente': '#ef4444', 'Atención': '#f59e0b', 'Con calma': '#16a34a' };
      const stripColor = stripColorMap[normalizedPriority] || '#94a3b8';

      const logs = Array.isArray(p.time_logs) ? p.time_logs : [];
      const isRunning = logs.length > 0 && !logs[logs.length - 1].end;
      const nowTs = Math.floor(Date.now() / 1000);
      const totalSeconds = logs.reduce((acc, log) => {
        const start = Number(log?.start || 0);
        if (start <= 0) return acc;
        const end = Number(log?.end || (isRunning ? nowTs : start));
        return acc + Math.max(0, end - start);
      }, 0);
      const hours = Math.floor(totalSeconds / 3600);
      const mins = Math.floor((totalSeconds % 3600) / 60);
      const investedDisplay = `${hours}h ${mins}m`;

      const formatDate = (raw) => {
        if (!raw) return null;
        const normalized = /^\d{4}-\d{2}-\d{2}$/.test(String(raw)) ? `${raw}T12:00:00` : raw;
        const dt = new Date(normalized);
        if (Number.isNaN(dt.getTime())) return null;
        return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
      };

      const startLabel = formatDate(p.inicio || p.created_at) || '—';
      const dueLabel = formatDate(p.vencimiento) || '—';

      const today = new Date();
      const dueDate = p.vencimiento ? new Date(/^\d{4}-\d{2}-\d{2}$/.test(String(p.vencimiento)) ? `${p.vencimiento}T12:00:00` : p.vencimiento) : null;
      const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime();
      const startOfDue = dueDate ? new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate()).getTime() : null;
      const diffDays = startOfDue !== null ? Math.floor((startOfDue - startOfToday) / 86400000) : null;

      let badgeText = 'Sin fecha';
      let badgeBg = '#94a3b8';
      let progressColor = '#94a3b8';
      if (diffDays !== null) {
        if (diffDays < 0) {
          badgeText = 'Vencido';
          badgeBg = '#ef4444';
          progressColor = '#ef4444';
        } else if (diffDays <= 7) {
          badgeText = `${diffDays} día${diffDays !== 1 ? 's' : ''} restante${diffDays !== 1 ? 's' : ''}`;
          badgeBg = '#ef4444';
          progressColor = '#ef4444';
        } else if (diffDays <= 30) {
          const weeks = Math.ceil(diffDays / 7);
          badgeText = `${weeks} semana${weeks !== 1 ? 's' : ''}`;
          badgeBg = '#f59e0b';
          progressColor = '#f59e0b';
        } else if (diffDays <= 365) {
          const months = Math.ceil(diffDays / 30);
          badgeText = `${months} mes${months !== 1 ? 'es' : ''}`;
          badgeBg = '#0d9488';
          progressColor = '#0d9488';
        } else {
          const years = Math.ceil(diffDays / 365);
          badgeText = `${years} año${years !== 1 ? 's' : ''}`;
          badgeBg = '#16a34a';
          progressColor = '#16a34a';
        }
      }

      const projectResponsible = getProjectResponsibleSources(p);
      const projectBadge = renderResponsibleBadges(projectResponsible.names, projectResponsible.ids, {
        limit: 2,
        bubbleClass: 'w-7 h-7 rounded-full border-2 border-white bg-slate-200 text-slate-600 text-[9px] font-bold flex items-center justify-center overflow-hidden',
        wrapperClass: 'flex -space-x-2',
        emptyHtml: '<div class="w-7 h-7 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-[9px] font-bold text-slate-500">SR</div>'
      });
      const isProjectOverdue = diffDays !== null && diffDays < 0;
      const projectStatusBadges = isProjectOverdue
        ? `<span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-extrabold" style="background:${badgeBg};color:#fff;">Vencido</span>`
        : `${getTaskPriorityBadge(normalizedPriority)}<span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-extrabold" style="background:${badgeBg};color:#fff;">${badgeText}</span>`;

      return `
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 cursor-move hover:shadow-md transition-all group relative overflow-hidden" draggable="true" data-id="${p.id}" onclick="handleCardClick(event, '${p.id}')">
        <div class="absolute inset-x-0 top-0 h-1" style="background:${stripColor};"></div>
        <div class="p-4 pt-5">
          <div class="flex items-start justify-between gap-2 mb-2">
             <div class="text-[11px] font-semibold text-slate-400">${startLabel} &ndash; ${dueLabel}</div>
             <div data-advanced-control class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
               <button class="text-slate-300 hover:text-slate-500" onclick="event.stopPropagation(); openProject('${p.id}')">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
               </button>
             </div>
          </div>

          <div class="font-extrabold text-slate-900 text-lg leading-tight mb-0.5 hover:text-blue-600 transition-colors cursor-pointer outline-none focus:bg-slate-50 focus:ring-2 focus:ring-lime-300 rounded px-1 -mx-1 project-title" data-project-id="${p.id}" ondblclick="event.stopPropagation(); editProjectTitle(this, '${p.id}')" onclick="event.stopPropagation(); handleCardClick(event, '${p.id}')">${escapeHtml(p.titulo || 'Proyecto')}</div>
          <div class="text-sm text-slate-500 mb-3">${escapeHtml(p.etapa || 'Sin etapa')}</div>

          <div>
            <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">
              <span>Progreso</span><span>${prog}%</span>
            </div>
            <div class="h-2.5 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full rounded-full transition-all" style="width:${prog}%;background:${progressColor};"></div>
            </div>
          </div>

          <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 font-semibold">
            <span>${taskStats.total} tarea${taskStats.total === 1 ? '' : 's'}</span>
            <span>${investedDisplay} invertidas</span>
          </div>

          <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center">${projectBadge}</div>
            <div class="flex flex-wrap items-center justify-end gap-2">${projectStatusBadges}</div>
          </div>
        </div>
      </div>`;
    }

    function renderKanban(list) {
      renderProjectBoards(list);
      if (!currentBoardProjectId && pendingBoardSlug) {
        const projectFromSlug = findProjectByBoardSlug(pendingBoardSlug);
        if (projectFromSlug?.id) {
          currentBoardProjectId = String(projectFromSlug.id);
        } else {
          showProjectBoardLoading();
          refreshProjectsSimpleModeUI();
          return;
        }
      }
      if (currentBoardProjectId && list.some((project) => String(project.id) === String(currentBoardProjectId))) {
        renderProjectBoard(currentBoardProjectId);
      } else if (pendingBoardSlug) {
        showProjectBoardLoading();
      } else {
        closeProjectBoard({ skipUrl: true });
      }
      refreshProjectsSimpleModeUI();
    }

    function parseProjectCoverColor(value) {
      const parts = String(value || '').split('|').map((part) => part.trim()).filter(Boolean);
      if (parts.length >= 2) return [parts[0], parts[1]];
      return null;
    }

    function boardCoverTone(project, index = 0) {
      const saved = parseProjectCoverColor(project?.cover_color);
      if (saved) return saved;
      const tones = PROJECT_COVER_PALETTES;
      const raw = String(project?.id || project?.titulo || index);
      let hash = 0;
      for (let i = 0; i < raw.length; i += 1) hash = ((hash << 5) - hash) + raw.charCodeAt(i);
      return tones[Math.abs(hash) % tones.length];
    }

    function formatBoardDate(raw) {
      if (!raw) return 'Sin fecha';
      const normalized = /^\d{4}-\d{2}-\d{2}$/.test(String(raw)) ? `${raw}T12:00:00` : raw;
      const date = new Date(normalized);
      if (Number.isNaN(date.getTime())) return 'Sin fecha';
      return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function projectBoardSlug(project) {
      const base = String(project?.titulo || project?.id || 'tablero')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 72);
      return base || String(project?.id || 'tablero');
    }

    function findProjectByBoardSlug(slug) {
      const clean = String(slug || '').trim();
      if (!clean) return null;
      return projects.find((project) => projectBoardSlug(project) === clean)
        || projects.find((project) => String(project.id || '') === clean)
        || null;
    }

    function getBoardSlugFromLocation() {
      const path = decodeURIComponent(String(window.location.pathname || '')).replace(/\/+$/g, '');
      const prefix = '/proyectos/';
      if (path.startsWith(prefix)) return path.slice(prefix.length).trim();
      const params = new URLSearchParams(window.location.search || '');
      return String(params.get('board') || '').trim();
    }

    function renderProjectBoards(list) {
      const grid = document.getElementById('projectBoardsGrid');
      const count = document.getElementById('projectBoardsCount');
      if (!grid) return;

      const rows = Array.isArray(list) ? list : [];
      if (count) count.textContent = `${rows.length} tablero${rows.length === 1 ? '' : 's'}`;

      if (!rows.length) {
        grid.innerHTML = `<div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
          <div class="text-sm font-bold text-slate-600">No hay tableros todavía.</div>
          <button type="button" onclick="openNewProjectModal()" class="mt-3 inline-flex items-center gap-2 rounded-full bg-lime-300 px-4 py-2 text-sm font-black text-slate-900 hover:bg-lime-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m7-7H5"/></svg>
            Crear tablero
          </button>
        </div>`;
        return;
      }

      grid.innerHTML = rows.map((project, index) => {
        const [from, to] = boardCoverTone(project, index);
        const coverImage = String(project.cover_image || '').trim();
        const coverClass = coverImage ? 'project-board-cover has-image' : 'project-board-cover';
        const coverStyle = coverImage ? `background-image:url(&quot;${escapeHtml(coverImage)}&quot;);` : '';
        const safeId = String(project.id || '').replace(/'/g, "\\'");
        const stats = getProjectTaskStats(project);
        const taskCount = Array.isArray(project.tareas) ? project.tareas.length : 0;
        const stage = escapeHtml(project.etapa || 'Sin estado');
        const client = escapeHtml(project.cliente || 'Sin cliente');
        const due = formatBoardDate(project.vencimiento);
        return `<button type="button" onclick="openProjectBoard('${safeId}')" class="project-board-card text-left" style="--board-from:${from};--board-to:${to};">
          <div class="${coverClass}" style="${coverStyle}"></div>
          <div class="project-board-footer">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate text-base font-black leading-tight sm:text-lg">${escapeHtml(project.titulo || 'Tablero sin titulo')}</div>
                <div class="mt-0.5 truncate text-[11px] font-semibold text-slate-500">${client} · ${stage}</div>
              </div>
              <span class="rounded-full bg-lime-200 px-1.5 py-0.5 text-[11px] font-black leading-none text-slate-950">${stats.pct}%</span>
            </div>
            <div class="mt-2.5 flex items-center justify-between gap-3 text-[11px] font-bold text-slate-600">
              <span>${taskCount} tarjeta${taskCount === 1 ? '' : 's'}</span>
              <span class="truncate text-right">${escapeHtml(due)}</span>
            </div>
          </div>
        </button>`;
      }).join('');
    }

    function getProjectBoardStages(project) {
      const stored = Array.isArray(project?.task_stages) ? project.task_stages : [];
      const clean = stored.map((stage) => String(stage || '').trim()).filter(Boolean);
      return clean.length ? clean : DEFAULT_PROJECT_TASK_STAGES.slice();
    }

    function setBoardRoute(slug = '', mode = 'push') {
      const url = new URL(window.location.href);
      const nextPath = slug ? `/proyectos/${encodeURIComponent(slug)}` : '/proyectos';
      url.pathname = nextPath;
      url.searchParams.delete('board');
      url.searchParams.delete('open_project');
      url.searchParams.delete('open_task');
      url.searchParams.delete('new_project');
      const state = { view: slug ? 'project-board' : 'project-boards', boardSlug: slug || '' };
      if (mode === 'replace') window.history.replaceState(state, '', url);
      else window.history.pushState(state, '', url);
    }

    function transitionBoardViews(showDetail = false) {
      const header = document.getElementById('projectBoardsHeader');
      const list = document.getElementById('projectBoardsView');
      const detail = document.getElementById('projectBoardDetailView');
      document.body.classList.toggle('project-board-open', !!showDetail);
      const show = (node) => {
        if (!node) return;
        node.classList.remove('hidden', 'is-visible');
        node.classList.add('is-entering');
        requestAnimationFrame(() => {
          node.classList.remove('is-entering');
          node.classList.add('is-visible');
        });
      };
      const hide = (node) => {
        if (!node) return;
        node.classList.remove('is-entering', 'is-visible');
        node.classList.add('hidden');
      };

      if (showDetail) {
        hide(header);
        hide(list);
        show(detail);
        fitProjectBoardViewport();
        return;
      }

      hide(detail);
      show(header);
      show(list);
      resetProjectBoardViewport();
    }

    function fitProjectBoardViewport() {
      const shell = document.getElementById('proyectos-kanban');
      const detail = document.getElementById('projectBoardDetailView');
      const columns = document.getElementById('projectBoardColumns');
      if (!shell || !detail || !columns) return;

      requestAnimationFrame(() => {
        const shellTop = shell.getBoundingClientRect().top;
        const shellHeight = Math.max(280, window.innerHeight - shellTop - 14);
        shell.style.height = `${shellHeight}px`;
        detail.style.height = `${shellHeight}px`;
        const detailHeader = detail.firstElementChild;
        const headerHeight = detailHeader ? detailHeader.getBoundingClientRect().height : 0;
        columns.style.height = `${Math.max(220, shellHeight - headerHeight - 16)}px`;
      });
    }

    function resetProjectBoardViewport() {
      const shell = document.getElementById('proyectos-kanban');
      const detail = document.getElementById('projectBoardDetailView');
      const columns = document.getElementById('projectBoardColumns');
      if (shell) shell.style.height = '';
      if (detail) detail.style.height = '';
      if (columns) columns.style.height = '';
    }

    function showProjectBoardLoading(project = null) {
      transitionBoardViews(true);
      bindProjectBoardHeaderBack();
      const title = document.getElementById('projectBoardTitle');
      const meta = document.getElementById('projectBoardMeta');
      const container = document.getElementById('projectBoardColumns');
      if (title) title.textContent = project?.titulo || 'Cargando tablero';
      if (meta) meta.textContent = 'Preparando columnas y tarjetas...';
      updateBoardTaskTrashCount(project || {});
      if (container) {
        container.innerHTML = [1, 2, 3].map(() => `
          <div class="project-board-column pointer-events-none">
            <div class="border-b border-slate-200/80 px-3 py-3">
              <div class="h-5 w-32 animate-pulse rounded-full bg-slate-200"></div>
            </div>
            <div class="project-board-column-body">
              <div class="h-24 animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"></div>
              <div class="h-20 animate-pulse rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"></div>
            </div>
          </div>`).join('');
      }
    }

    function openProjectBoard(projectId, options = {}) {
      currentBoardProjectId = String(projectId || '');
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      const slug = projectBoardSlug(project || { id: currentBoardProjectId });
      pendingBoardSlug = '';
      showProjectBoardLoading(project || null);
      if (!options.skipUrl) setBoardRoute(slug, options.replaceUrl ? 'replace' : 'push');
      renderProjectBoard(currentBoardProjectId, { preserveScroll: false });
    }

    function closeProjectBoard(options = {}) {
      currentBoardProjectId = '';
      pendingBoardSlug = '';
      transitionBoardViews(false);
      if (!options.skipUrl) {
        setBoardRoute('', options.replaceUrl ? 'replace' : 'push');
      }
    }

    function bindProjectBoardHeaderBack() {
      if (!projectGlobalBackButton) return;
      projectGlobalBackButton.classList.add('hidden');
      projectGlobalBackButton.onclick = null;
    }

    function syncProjectBoardFromLocation() {
      const slug = getBoardSlugFromLocation();
      if (!slug) {
        closeProjectBoard({ skipUrl: true });
        return;
      }

      const project = findProjectByBoardSlug(slug);
      if (project?.id) {
        currentBoardProjectId = String(project.id);
        pendingBoardSlug = '';
        showProjectBoardLoading(project);
        renderProjectBoard(currentBoardProjectId, { preserveScroll: false });
        return;
      }

      pendingBoardSlug = slug;
      currentBoardProjectId = '';
      showProjectBoardLoading();
    }

    function renderProjectBoard(projectId, options = {}) {
      const project = projects.find((item) => String(item.id) === String(projectId));
      if (!project) {
        closeProjectBoard({ skipUrl: true });
        return;
      }

      window.__infocusAiCurrentProject = {
        id: String(project.id || projectId || ''),
        title: String(project.titulo || 'Tablero'),
      };
      transitionBoardViews(true);
      bindProjectBoardHeaderBack();

      const title = document.getElementById('projectBoardTitle');
      const meta = document.getElementById('projectBoardMeta');
      if (title) title.textContent = project.titulo || 'Tablero';
      if (meta) {
        const stats = getProjectTaskStats(project);
        meta.textContent = `${project.cliente || 'Sin cliente'} · ${project.etapa || 'Sin estado'} · ${stats.total} tarjeta${stats.total === 1 ? '' : 's'} · ${stats.pct}% completado`;
      }
      updateBoardTaskTrashCount(project);

      const container = document.getElementById('projectBoardColumns');
      if (!container) return;
      const restoreScroll = options.preserveScroll !== false;
      const previousScrollLeft = restoreScroll ? Number(options.scrollLeft ?? container.scrollLeft ?? 0) : 0;
      const boardStages = getProjectBoardStages(project);
      const tasks = (Array.isArray(project.tareas) ? project.tareas : []).slice().sort((a, b) => Number(a.board_order || 0) - Number(b.board_order || 0));
      const aiState = getProjectBoardAiState(project);
      container.classList.toggle('is-ai-working', aiState.isBoardWide);
      if (aiState.isBoardWide) {
        container.setAttribute('data-ai-label', 'IA ajustando tablero');
      } else {
        container.removeAttribute('data-ai-label');
      }

      container.innerHTML = boardStages.map((stage) => {
        const stageTasks = tasks.filter((task) => (String(task.board_stage || 'Por hacer').trim() || 'Por hacer') === stage);
        const safeStageArg = escapeHtml(JSON.stringify(stage));
        const isEditingStage = boardStageEditingName === stage;
        const isAiTargetStage = aiState.isColumn && normalizeBoardStageText(aiState.stage) === normalizeBoardStageText(stage);
        return `<div class="project-board-column ${isAiTargetStage ? 'is-ai-working' : ''}" draggable="true" data-board-stage="${escapeHtml(stage)}" data-board-column-stage="${escapeHtml(stage)}">
          <div class="project-board-column-header flex items-center justify-between gap-2 border-b border-slate-200/80 px-3 py-2.5" title="Arrastra para reorganizar columnas">
            <span class="shrink-0 text-slate-300" data-board-column-handle aria-hidden="true">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.2" d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"/></svg>
            </span>
            ${isEditingStage ? `<input type="text" data-board-stage-name-input="${escapeHtml(stage)}" value="${escapeHtml(stage)}" class="min-w-0 flex-1 rounded-lg border border-lime-300 bg-white px-2 py-1 text-sm font-black text-slate-950 outline-none ring-4 ring-lime-100" onkeydown="handleBoardStageNameKey(event, ${safeStageArg})" onblur="commitBoardStageName(${safeStageArg}, this.value)">` : `<button type="button" onclick="startBoardStageNameEdit(${safeStageArg})" class="group min-w-0 flex-1 text-left" title="Renombrar columna">
              <span class="inline-flex max-w-full items-center gap-1.5">
                <span class="min-w-0 truncate text-sm font-black text-slate-950">${escapeHtml(stage)}</span>
                <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 opacity-0 transition group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4Z"/></svg>
              </span>
            </button>`}
            <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-black text-slate-500 shadow-sm">${stageTasks.length}</span>
          </div>
          <div class="project-board-column-body custom-scroll" data-board-drop-stage="${escapeHtml(stage)}">
            ${projectBoardAiCreatingIndicator(project, stage)}
            ${stageTasks.map((task) => projectBoardTaskCard(project, task)).join('')}
            ${projectBoardAddTaskButton(stage)}
          </div>
        </div>`;
      }).join('');

      enableProjectBoardDnD();
      initProjectBoardDragScroll();
      fitProjectBoardViewport();
      if (restoreScroll && previousScrollLeft > 0) {
        requestAnimationFrame(() => {
          container.scrollLeft = previousScrollLeft;
        });
      }
      focusProjectBoardInlineControls();
    }

    function normalizeBoardStageText(value = '') {
      return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();
    }

    function resolveBoardAiStage(project, preferredStage = '') {
      const stages = getProjectBoardStages(project);
      const preferred = normalizeBoardStageText(preferredStage);
      if (!preferred) return stages[0] || 'Por hacer';
      const exact = stages.find((stage) => normalizeBoardStageText(stage) === preferred);
      if (exact) return exact;
      return stages.find((stage) => {
        const normalized = normalizeBoardStageText(stage);
        return normalized && (normalized.includes(preferred) || preferred.includes(normalized));
      }) || stages[0] || 'Por hacer';
    }

    function getProjectBoardAiState(project) {
      if (!boardAiWorking?.working) return { working: false, isBoardWide: false, isColumn: false, stage: '' };
      const targetProjectMatches = !boardAiWorking.projectId || String(boardAiWorking.projectId) === String(project?.id || '');
      const targetTitleMatches = !boardAiWorking.projectTitle || normalizeBoardStageText(boardAiWorking.projectTitle) === normalizeBoardStageText(project?.titulo || '');
      if (!targetProjectMatches && !targetTitleMatches) return { working: false, isBoardWide: false, isColumn: false, stage: '' };

      const explicitStage = String(boardAiWorking.stage || '').trim();
      if (!explicitStage) return { working: true, isBoardWide: true, isColumn: false, stage: '' };

      return {
        working: true,
        isBoardWide: false,
        isColumn: true,
        stage: resolveBoardAiStage(project, explicitStage),
      };
    }

    function projectBoardAiCreatingIndicator(project, stage) {
      const aiState = getProjectBoardAiState(project);
      if (!aiState.isColumn) return '';
      if (normalizeBoardStageText(aiState.stage) !== normalizeBoardStageText(stage)) return '';
      return `<div class="project-board-ai-creating" aria-live="polite">
        <span class="project-board-ai-creating-icon"><i class="fa-solid fa-robot" aria-hidden="true"></i></span>
        <span class="project-board-ai-creating-text">
          <span>IA creando tarjeta</span>
          <span>${escapeHtml(stage)}</span>
        </span>
      </div>`;
    }

    function updateBoardTaskTrashCount(project) {
      const count = Array.isArray(project?.archived_tasks) ? project.archived_tasks.length : 0;
      const badge = document.getElementById('boardTaskTrashCount');
      if (!badge) return;
      badge.textContent = String(count);
      badge.classList.toggle('hidden', count === 0);
    }

    function openBoardTaskTrash() {
      renderBoardTaskTrash();
      document.getElementById('boardTaskTrashModal')?.classList.remove('hidden');
    }

    function closeBoardTaskTrash() {
      document.getElementById('boardTaskTrashModal')?.classList.add('hidden');
    }

    function renderBoardTaskTrash() {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      const list = document.getElementById('boardTaskTrashList');
      const empty = document.getElementById('boardTaskTrashEmpty');
      const subtitle = document.getElementById('boardTaskTrashSubtitle');
      if (!list || !empty) return;

      const archived = Array.isArray(project?.archived_tasks) ? project.archived_tasks : [];
      if (subtitle) subtitle.textContent = `${project?.titulo || 'Tablero'} · ${archived.length} tarea${archived.length === 1 ? '' : 's'} archivada${archived.length === 1 ? '' : 's'}`;
      empty.classList.toggle('hidden', archived.length > 0);
      list.classList.toggle('hidden', archived.length === 0);

      list.innerHTML = archived.map((task) => {
        const safeTaskId = String(task.id || '').replace(/'/g, "\\'");
        const priority = getTaskPriorityBadge(getEffectiveTaskPriority(task, project), 'xs');
        const archivedAt = task.archived_at ? new Date(task.archived_at).toLocaleDateString('es-ES') : 'Sin fecha';
        return `<div class="flex items-center justify-between gap-3 px-5 py-4">
          <div class="min-w-0">
            <div class="truncate text-sm font-extrabold text-slate-900">${escapeHtml(task.texto || 'Tarea sin titulo')}</div>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
              <span>${escapeHtml(task.board_stage || task.archived_from_stage || 'Por hacer')}</span>
              <span>Archivada ${escapeHtml(archivedAt)}</span>
              ${priority}
            </div>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <button type="button" onclick="restoreArchivedBoardTask('${safeTaskId}')" class="rounded-lg border border-lime-200 bg-lime-50 px-3 py-2 text-xs font-extrabold text-slate-900 hover:bg-lime-100">Restaurar</button>
            <button type="button" onclick="deleteArchivedBoardTask('${safeTaskId}')" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-extrabold text-rose-600 hover:bg-rose-50">Eliminar</button>
          </div>
        </div>`;
      }).join('');
    }

    async function restoreArchivedBoardTask(taskId) {
      if (!currentBoardProjectId || !taskId) return;
      const response = await fetch('/api/proyectos/tareas/restaurar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentBoardProjectId, tarea_id: taskId}),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.item) return;
      patchBoardProject(data.item);
      renderProjectBoard(currentBoardProjectId);
      renderBoardTaskTrash();
      if (window.showNotification) window.showNotification('Tarea restaurada', 'success');
    }

    async function deleteArchivedBoardTask(taskId) {
      if (!currentBoardProjectId || !taskId) return;
      if (!confirm('¿Eliminar definitivamente esta tarea?')) return;
      const response = await fetch('/api/proyectos/tareas/eliminar-archivada', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentBoardProjectId, tarea_id: taskId}),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.item) return;
      patchBoardProject(data.item);
      renderProjectBoard(currentBoardProjectId);
      renderBoardTaskTrash();
      if (window.showNotification) window.showNotification('Tarea eliminada definitivamente', 'success');
    }

    function patchBoardProject(project) {
      const idx = projects.findIndex((item) => String(item.id) === String(project?.id));
      if (idx >= 0) projects[idx] = project;
    }

    window.addEventListener('infocus-ai-project-working', (event) => {
      const detail = event.detail || {};
      boardAiWorking = {
        working: !!detail.working,
        projectId: String(detail.projectId || ''),
        projectTitle: String(detail.projectTitle || ''),
        stage: String(detail.stage || ''),
      };
      if (currentBoardProjectId) {
        const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
        renderProjectBoard(currentBoardProjectId, { scrollLeft });
      }
    });

    window.addEventListener('infocus-ai-project-updated', (event) => {
      const detail = event.detail || {};
      if (detail.project?.id) {
        patchBoardProject(detail.project);
      }

      const updatedProjectId = String(detail.project?.id || detail.action?.project_id || detail.fallback?.projectId || '');
      if (updatedProjectId && String(currentBoardProjectId || '') === updatedProjectId) {
        const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
        renderProjectBoard(currentBoardProjectId, { scrollLeft });
      } else {
        renderKanban(projects);
      }
    });

    function projectBoardTaskCard(project, task) {
      const safeProjectId = String(project.id || '').replace(/'/g, "\\'");
      const safeTaskId = String(task.id || '').replace(/'/g, "\\'");
      const done = !!task.done;
      const isPending = !!task._pending;
      const isEntering = isPending || boardRecentTaskIds.has(String(task.id || ''));
      const priority = normalizePriority(task.priority || project.prioridad || 'Con calma');
      const due = task.due_date || task.end_date || '';
      const owners = getTaskOwnerSources(task, project);
      const files = Array.isArray(task.files) ? task.files : [];
      const cover = getTaskCoverFile(task);
      const coverUrl = cover?.preview_url || cover?.url || '';
      const ownersHtml = renderResponsibleBadges(owners.names, owners.ids, {
        limit: 2,
        wrapperClass: 'flex -space-x-2',
        bubbleClass: 'w-6 h-6 rounded-full border-2 border-white bg-slate-200 text-slate-600 text-[8px] font-bold flex items-center justify-center overflow-hidden',
        emptyHtml: '<span class="text-[11px] font-semibold text-slate-400">Sin responsable</span>',
      });
      return `<div class="project-task-card ${done ? 'is-done' : ''} ${coverUrl ? 'has-cover' : ''} ${isPending ? 'is-pending' : ''} ${isEntering ? 'is-entering' : ''}" draggable="${isPending ? 'false' : 'true'}" data-board-task-id="${escapeHtml(task.id || '')}" onclick="${isPending ? '' : `openProjectTask('${safeProjectId}', '${safeTaskId}')`}">
        ${coverUrl ? `<img src="${escapeHtml(coverUrl)}" class="project-task-cover" alt="">` : ''}
        <div class="project-task-card-body flex items-start gap-2.5">
          <button type="button" onclick="event.stopPropagation(); toggleTask('${safeTaskId}', '${safeProjectId}')" class="project-task-toggle h-5 w-5 shrink-0 rounded-full border-2 ${done ? 'border-lime-300 bg-lime-200 text-slate-950' : 'border-slate-300 bg-white'} flex items-center justify-center">
            ${done ? '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>' : ''}
          </button>
          <div class="min-w-0 flex-1">
            <div class="project-task-title text-[13px] font-bold leading-snug ${done ? 'line-through text-slate-400' : 'text-slate-900'}">${escapeHtml(task.texto || 'Tarjeta sin titulo')}</div>
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
              ${getTaskPriorityBadge(priority)}
              ${due ? `<span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-500">${escapeHtml(formatBoardDate(due))}</span>` : ''}
            </div>
            <div class="project-task-footer mt-2 flex items-center justify-between gap-2">
              ${ownersHtml}
              <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400">
                ${files.length ? `<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a4 4 0 10-5.657-5.657L5.757 10.757a6 6 0 108.486 8.486L20 13.486"/></svg>${files.length}` : ''}
                <span>${(task.subtasks || []).length} Tareas</span>
              </span>
            </div>
          </div>
        </div>
      </div>`;
    }

    function getNextBoardTaskOrder(project, stage) {
      const normalizedStage = String(stage || 'Por hacer').trim() || 'Por hacer';
      return (Array.isArray(project?.tareas) ? project.tareas : [])
        .filter((task) => (String(task.board_stage || 'Por hacer').trim() || 'Por hacer') === normalizedStage)
        .reduce((max, task) => Math.max(max, Number(task.board_order || 0)), -1) + 1;
    }

    function findCreatedBoardTaskId(project, text, stage, knownIds = new Set()) {
      const normalizedStage = String(stage || 'Por hacer').trim() || 'Por hacer';
      const matches = (Array.isArray(project?.tareas) ? project.tareas : [])
        .filter((task) => {
          if (knownIds.has(String(task.id || ''))) return false;
          const taskStage = String(task.board_stage || 'Por hacer').trim() || 'Por hacer';
          return taskStage === normalizedStage && String(task.texto || '').trim() === String(text || '').trim();
        });
      if (!matches.length) return '';
      matches.sort((a, b) => Number(b.board_order || 0) - Number(a.board_order || 0));
      return String(matches[0].id || '');
    }

    function mergePendingBoardTasks(project) {
      if (!project?.id) return project;
      const pending = Array.from(boardPendingTasks.values())
        .filter((item) => String(item.projectId || '') === String(project.id || ''))
        .map((item) => item.task);
      if (!pending.length) return project;
      const existingIds = new Set((Array.isArray(project.tareas) ? project.tareas : []).map((task) => String(task.id || '')));
      const missingPending = pending.filter((task) => !existingIds.has(String(task.id || '')));
      if (!missingPending.length) return project;
      return {
        ...project,
        tareas: (Array.isArray(project.tareas) ? project.tareas : []).concat(missingPending),
      };
    }

    function projectBoardAddTaskButton(stage) {
      const safeStageArg = escapeHtml(JSON.stringify(stage || 'Por hacer'));
      return `<div class="project-board-card-composer" data-no-pan>
        <input type="text"
          data-board-composer-stage="${escapeHtml(stage || '')}"
          class="w-full rounded-lg border border-dashed border-slate-300 bg-white/80 px-3 py-2.5 text-xs font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-lime-300 focus:bg-white focus:ring-4 focus:ring-lime-100"
          placeholder="Añadir tarjeta"
          autocomplete="off"
          onkeydown="handleBoardTaskComposerKey(event, ${safeStageArg})">
      </div>`;
    }

    async function addBoardTask(stage, text = '') {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return;
      const clean = String(text || '').trim();
      if (!clean) return;
      const targetStage = stage || 'Por hacer';
      boardTaskComposerFocusStage = targetStage;
      const knownIds = new Set((Array.isArray(project.tareas) ? project.tareas : []).map((task) => String(task.id || '')));
      const tempId = `pending-${Date.now()}-${++boardPendingTaskSeq}`;
      const pendingTask = {
        id: tempId,
        texto: clean,
        priority: 'Con calma',
        board_stage: targetStage,
        board_order: getNextBoardTaskOrder(project, targetStage),
        done: false,
        subtasks: [],
        files: [],
        _pending: true,
      };
      boardPendingTasks.set(tempId, { projectId: project.id, task: pendingTask });
      project.tareas = Array.isArray(project.tareas) ? project.tareas.concat(pendingTask) : [pendingTask];
      renderProjectBoard(project.id);

      const response = await fetch('/api/proyectos/tareas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({
          id: project.id,
          texto: clean,
          priority: 'Con calma',
          board_stage: targetStage,
        }),
      });
      const data = await response.json().catch(() => ({}));
      if (response.ok && data.item) {
        boardPendingTasks.delete(tempId);
        const createdId = findCreatedBoardTaskId(data.item, clean, targetStage, knownIds);
        if (createdId) {
          boardRecentTaskIds.add(createdId);
          window.setTimeout(() => boardRecentTaskIds.delete(createdId), 700);
        }
        const mergedProject = mergePendingBoardTasks(data.item);
        const idx = projects.findIndex((item) => String(item.id) === String(project.id));
        if (idx >= 0) projects[idx] = mergedProject;
        renderProjectBoard(project.id);
        return true;
      }
      boardPendingTasks.delete(tempId);
      project.tareas = (Array.isArray(project.tareas) ? project.tareas : []).filter((task) => String(task.id || '') !== tempId);
      renderProjectBoard(project.id);
      if (window.showNotification) window.showNotification('No se pudo crear la tarjeta', 'error');
      return false;
    }

    function handleBoardTaskComposerKey(event, stage) {
      if (event.key !== 'Enter' || event.isComposing) return;
      event.preventDefault();
      const input = event.currentTarget;
      const clean = String(input?.value || '').trim();
      if (!clean) return;
      input.value = '';
      addBoardTask(stage, clean).then((created) => {
        if (created === false && input && document.body.contains(input)) {
          input.value = clean;
          input.focus();
        }
      });
    }

    async function addBoardStage() {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return;
      const name = prompt('Nombre de la nueva columna:');
      const clean = String(name || '').trim();
      if (!clean) return;
      const currentStages = getProjectBoardStages(project);
      if (currentStages.includes(clean)) return;
      const taskStages = currentStages.concat(clean);
      const updated = await updateProjectField('task_stages', taskStages, project.id);
      const idx = projects.findIndex((item) => String(item.id) === String(project.id));
      if (idx >= 0 && updated) projects[idx] = {...projects[idx], ...updated};
      renderProjectBoard(project.id);
    }

    function startBoardStageNameEdit(stage) {
      const cleanStage = String(stage || '').trim();
      if (!cleanStage) return;
      boardStageEditingName = cleanStage;
      const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
      renderProjectBoard(currentBoardProjectId, { scrollLeft });
    }

    function handleBoardStageNameKey(event, oldStage) {
      if (event.key === 'Escape') {
        event.preventDefault();
        boardStageEditingName = '';
        const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
        renderProjectBoard(currentBoardProjectId, { scrollLeft });
        return;
      }
      if (event.key !== 'Enter' || event.isComposing) return;
      event.preventDefault();
      event.currentTarget?.blur();
    }

    async function commitBoardStageName(oldStage, value) {
      if (boardStageEditingName !== oldStage) return;
      const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
      boardStageEditingName = '';
      await renameBoardStage(oldStage, value, { scrollLeft });
    }

    async function renameBoardStage(oldStage, nextStage = '', options = {}) {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return;
      const oldName = String(oldStage || '').trim();
      if (!oldName) return;

      const nextName = String(nextStage || '').trim();
      if (!nextName || nextName === oldName) {
        renderProjectBoard(project.id, { scrollLeft: options.scrollLeft });
        return;
      }

      const currentStages = getProjectBoardStages(project);
      if (currentStages.some((stage) => stage !== oldName && stage.toLowerCase() === nextName.toLowerCase())) {
        if (window.showNotification) window.showNotification('Ya existe una columna con ese nombre.', 'warning');
        renderProjectBoard(project.id, { scrollLeft: options.scrollLeft });
        return;
      }

      const taskStages = currentStages.map((stage) => stage === oldName ? nextName : stage);
      const renamedTasks = (Array.isArray(project.tareas) ? project.tareas : [])
        .filter((task) => (String(task.board_stage || 'Por hacer').trim() || 'Por hacer') === oldName);

      project.task_stages = taskStages;
      renamedTasks.forEach((task) => { task.board_stage = nextName; });
      renderProjectBoard(project.id, { scrollLeft: options.scrollLeft });

      try {
        const updated = await updateProjectField('task_stages', taskStages, project.id);
        if (updated) {
          const idx = projects.findIndex((item) => String(item.id) === String(project.id));
          if (idx >= 0) projects[idx] = {...projects[idx], ...updated};
        }

        for (const [order, task] of renamedTasks.entries()) {
          const response = await fetch('/api/proyectos/tareas/mover', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({
              id: project.id,
              tarea_id: task.id,
              board_stage: nextName,
              board_order: order,
            }),
          });
          const data = await response.json().catch(() => ({}));
          if (response.ok && data.item) {
            const idx = projects.findIndex((item) => String(item.id) === String(project.id));
            if (idx >= 0) projects[idx] = data.item;
          }
        }
      } catch (error) {
        console.error('Error renaming board stage', error);
        await loadData();
        return;
      }

      renderProjectBoard(project.id, { scrollLeft: options.scrollLeft });
    }
    window.renameBoardStage = renameBoardStage;
    window.startBoardStageNameEdit = startBoardStageNameEdit;
    window.handleBoardStageNameKey = handleBoardStageNameKey;
    window.commitBoardStageName = commitBoardStageName;
    window.handleBoardTaskComposerKey = handleBoardTaskComposerKey;

    function focusProjectBoardInlineControls() {
      requestAnimationFrame(() => {
        if (boardStageEditingName) {
          const input = Array.from(document.querySelectorAll('[data-board-stage-name-input]'))
            .find((item) => item.getAttribute('data-board-stage-name-input') === boardStageEditingName);
          if (input) {
            input.focus();
            input.select();
          }
          return;
        }

        if (!boardTaskComposerFocusStage) return;
        const input = Array.from(document.querySelectorAll('[data-board-composer-stage]'))
          .find((item) => item.getAttribute('data-board-composer-stage') === boardTaskComposerFocusStage);
        if (input) {
          input.focus();
          input.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
      });
    }

    function enableProjectBoardDnD() {
      const board = document.getElementById('projectBoardColumns');
      if (!board) return;
      const dropIndicator = document.createElement('div');
      dropIndicator.className = 'project-board-drop-indicator';
      dropIndicator.setAttribute('data-board-drop-indicator', '1');
      if (!boardColumnPreviewEl) {
        boardColumnPreviewEl = document.createElement('div');
        boardColumnPreviewEl.className = 'project-board-column-preview';
        boardColumnPreviewEl.setAttribute('data-board-column-preview', '1');
      }

      const clearDropState = () => {
        board.querySelectorAll('.project-board-drop-active').forEach((zone) => zone.classList.remove('project-board-drop-active'));
        dropIndicator.remove();
      };

      const clearColumnDropState = () => {
        board.querySelectorAll('.is-column-dragging').forEach((column) => column.classList.remove('is-column-dragging'));
        boardColumnPreviewEl?.remove();
        boardColumnPreviewOrder = null;
      };

      board.querySelectorAll('[data-board-column-stage]').forEach((column) => {
        column.addEventListener('dragstart', (event) => {
          if (event.target?.closest?.('[data-board-task-id], button, a, input, select, textarea, [contenteditable="true"]')) return;
          boardColumnDrag = column.getAttribute('data-board-column-stage') || '';
          if (!boardColumnDrag) return;
          event.dataTransfer.setData('application/x-infocus-board-column', boardColumnDrag);
          event.dataTransfer.effectAllowed = 'move';
          column.classList.add('is-column-dragging');
          clearDropState();
        });
        column.addEventListener('dragend', () => {
          boardColumnDrag = null;
          boardColumnPreviewOrder = null;
          clearColumnDropState();
        });
      });

      if (board.dataset.columnDndReady !== '1') {
        board.dataset.columnDndReady = '1';
        board.addEventListener('dragover', (event) => {
          if (!boardColumnDrag || !boardColumnPreviewEl) return;
          event.preventDefault();
          event.dataTransfer.dropEffect = 'move';
          positionBoardColumnPreview(board, event, boardColumnPreviewEl);
        });

        board.addEventListener('drop', async (event) => {
          if (!boardColumnDrag) return;
          event.preventDefault();
          const stage = event.dataTransfer.getData('application/x-infocus-board-column') || boardColumnDrag;
          const targetOrder = getBoardColumnDropOrder(board, event, stage);
          boardColumnPreviewEl?.remove();
          boardColumnDrag = null;
          boardColumnPreviewOrder = null;
          await moveBoardColumn(stage, targetOrder);
        });
      }

      board.querySelectorAll('[data-board-task-id]').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
          if (boardColumnDrag) return;
          boardTaskDrag = card.getAttribute('data-board-task-id');
          event.dataTransfer.setData('text/plain', boardTaskDrag);
          event.dataTransfer.effectAllowed = 'move';
          card.classList.add('is-board-dragging');
        });
        card.addEventListener('dragend', () => {
          card.classList.remove('is-board-dragging');
          boardTaskDrag = null;
          clearDropState();
        });
      });
      board.querySelectorAll('[data-board-drop-stage]').forEach((zone) => {
        zone.addEventListener('dragover', (event) => {
          if (boardColumnDrag) return;
          event.preventDefault();
          event.dataTransfer.dropEffect = 'move';
          zone.classList.add('project-board-drop-active');
          positionBoardDropIndicator(zone, event, dropIndicator);
        });
        zone.addEventListener('dragleave', (event) => {
          if (zone.contains(event.relatedTarget)) return;
          zone.classList.remove('project-board-drop-active');
        });
        zone.addEventListener('drop', async (event) => {
          if (boardColumnDrag) return;
          event.preventDefault();
          const taskId = event.dataTransfer.getData('text/plain') || boardTaskDrag;
          const stage = zone.getAttribute('data-board-drop-stage') || 'Por hacer';
          if (!taskId || !currentBoardProjectId) return;
          const order = getBoardDropOrder(zone, event, taskId);
          clearDropState();
          await moveBoardTask(taskId, stage, order);
        });
      });
    }

    function getBoardColumnsForDrop(board, draggingStage = '') {
      return Array.from(board.querySelectorAll('[data-board-column-stage]'))
        .filter((column) => String(column.getAttribute('data-board-column-stage') || '') !== String(draggingStage || ''));
    }

    function getBoardColumnDropOrder(board, event, draggingStage = boardColumnDrag) {
      const columns = getBoardColumnsForDrop(board, draggingStage);
      const pointerX = event.clientX;
      for (let index = 0; index < columns.length; index += 1) {
        const rect = columns[index].getBoundingClientRect();
        if (pointerX < rect.left + rect.width / 2) return index;
      }
      return columns.length;
    }

    function wouldBoardColumnOrderChange(stage, order = 0) {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return false;
      const currentStages = getProjectBoardStages(project);
      const fromIndex = currentStages.findIndex((item) => String(item) === String(stage));
      if (fromIndex < 0) return false;
      const remaining = currentStages.filter((_, index) => index !== fromIndex);
      const targetOrder = Math.max(0, Math.min(Number(order) || 0, remaining.length));
      const nextStages = remaining.slice();
      nextStages.splice(targetOrder, 0, currentStages[fromIndex]);
      return nextStages.join('\n') !== currentStages.join('\n');
    }

    function positionBoardColumnPreview(board, event, preview) {
      const order = getBoardColumnDropOrder(board, event, boardColumnDrag);
      const columns = getBoardColumnsForDrop(board, boardColumnDrag);
      if (!columns.length || !wouldBoardColumnOrderChange(boardColumnDrag, order)) {
        preview.remove();
        boardColumnPreviewOrder = null;
        return;
      }
      if (preview.isConnected && boardColumnPreviewOrder === order) return;
      boardColumnPreviewOrder = order;
      if (order >= columns.length) {
        board.appendChild(preview);
        return;
      }
      board.insertBefore(preview, columns[order]);
    }

    async function moveBoardColumn(stage, order = 0) {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return;
      const currentStages = getProjectBoardStages(project);
      const fromIndex = currentStages.findIndex((item) => String(item) === String(stage));
      if (fromIndex < 0) return;
      const moving = currentStages[fromIndex];
      const remaining = currentStages.filter((_, index) => index !== fromIndex);
      const targetOrder = Math.max(0, Math.min(Number(order) || 0, remaining.length));
      const nextStages = remaining.slice();
      nextStages.splice(targetOrder, 0, moving);
      if (nextStages.join('\n') === currentStages.join('\n')) return;

      project.task_stages = nextStages;
      const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
      renderProjectBoard(project.id, { scrollLeft });

      try {
        const updated = await updateProjectField('task_stages', nextStages, project.id);
        const idx = projects.findIndex((item) => String(item.id) === String(project.id));
        if (idx >= 0 && updated) projects[idx] = {...projects[idx], ...updated};
        renderProjectBoard(project.id, { scrollLeft });
      } catch (error) {
        console.error('Error moving board column', error);
        await loadData();
      }
    }

    function getBoardDropCards(zone, draggingTaskId = '') {
      return Array.from(zone.querySelectorAll('[data-board-task-id]'))
        .filter((card) => String(card.getAttribute('data-board-task-id') || '') !== String(draggingTaskId || ''));
    }

    function getBoardDropOrder(zone, event, draggingTaskId = boardTaskDrag) {
      const cards = getBoardDropCards(zone, draggingTaskId);
      const pointerY = event.clientY;
      for (let index = 0; index < cards.length; index += 1) {
        const rect = cards[index].getBoundingClientRect();
        if (pointerY < rect.top + rect.height / 2) return index;
      }
      return cards.length;
    }

    function positionBoardDropIndicator(zone, event, indicator) {
      const order = getBoardDropOrder(zone, event, boardTaskDrag);
      const cards = getBoardDropCards(zone, boardTaskDrag);
      const addButton = zone.querySelector('.project-board-card-composer');
      if (!cards.length) {
        zone.insertBefore(indicator, addButton || null);
        return;
      }
      if (order >= cards.length) {
        zone.insertBefore(indicator, addButton || null);
        return;
      }
      zone.insertBefore(indicator, cards[order]);
    }

    function initProjectBoardDragScroll() {
      const scroller = document.getElementById('projectBoardColumns');
      if (!scroller || scroller.dataset.panReady === '1') return;
      scroller.dataset.panReady = '1';

      const isInteractiveTarget = (target) => {
        if (!target) return false;
        return !!target.closest('button,a,input,select,textarea,[contenteditable="true"],[data-no-pan],.project-board-column-header,.project-task-card');
      };

      let active = false;
      let moved = false;
      let startX = 0;
      let startY = 0;
      let startLeft = 0;

      scroller.addEventListener('mousedown', (event) => {
        if (event.button !== 0) return;
        if (isInteractiveTarget(event.target)) return;
        event.preventDefault();
        active = true;
        moved = false;
        startX = event.clientX;
        startY = event.clientY;
        startLeft = scroller.scrollLeft;
        scroller.classList.add('is-grabbing');
      });

      window.addEventListener('mousemove', (event) => {
        if (!active) return;
        const deltaX = event.clientX - startX;
        const deltaY = event.clientY - startY;
        if (Math.abs(deltaX) > 3 && Math.abs(deltaX) > Math.abs(deltaY)) {
          moved = true;
        }
        if (!moved) return;
        event.preventDefault();
        scroller.scrollLeft = startLeft - deltaX;
      }, { passive: false });

      const stop = () => {
        if (!active) return;
        if (moved) kanbanPanSuppressClickUntil = Date.now() + 160;
        active = false;
        moved = false;
        scroller.classList.remove('is-grabbing');
      };

      window.addEventListener('mouseup', stop);
      window.addEventListener('blur', stop);
      scroller.addEventListener('mouseleave', stop);
    }

    async function moveBoardTask(taskId, stage, order = 0) {
      const project = projects.find((item) => String(item.id) === String(currentBoardProjectId));
      if (!project) return;
      const didMove = applyLocalBoardTaskMove(project, taskId, stage, order);
      const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
      if (didMove) {
        renderProjectBoard(project.id, { scrollLeft });
      }
      const response = await fetch('/api/proyectos/tareas/mover', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({
          id: project.id,
          tarea_id: taskId,
          board_stage: stage,
          board_order: order,
        }),
      });
      const data = await response.json().catch(() => ({}));
      if (response.ok && data.item) {
        const idx = projects.findIndex((item) => String(item.id) === String(project.id));
        if (idx >= 0) projects[idx] = data.item;
        renderProjectBoard(project.id, { scrollLeft });
      } else {
        loadData();
      }
    }

    function applyLocalBoardTaskMove(project, taskId, stage, order = 0) {
      const tasks = Array.isArray(project?.tareas) ? project.tareas.slice() : [];
      const movingIndex = tasks.findIndex((item) => String(item.id) === String(taskId));
      if (movingIndex < 0) return false;

      const moving = {...tasks[movingIndex], board_stage: stage};
      const remaining = tasks.filter((_, index) => index !== movingIndex);
      const normalizedStage = String(stage || 'Por hacer').trim() || 'Por hacer';
      const targetOrder = Math.max(0, Number(order) || 0);
      const before = [];
      const after = [];
      let stageSeen = 0;

      remaining.forEach((task) => {
        const taskStage = String(task.board_stage || 'Por hacer').trim() || 'Por hacer';
        if (taskStage === normalizedStage && stageSeen++ >= targetOrder) {
          after.push(task);
        } else {
          before.push(task);
        }
      });

      const nextTasks = before.concat(moving, after);
      const stageCounters = {};
      nextTasks.forEach((task) => {
        const taskStage = String(task.board_stage || 'Por hacer').trim() || 'Por hacer';
        task.board_stage = taskStage;
        task.board_order = stageCounters[taskStage] || 0;
        stageCounters[taskStage] = (stageCounters[taskStage] || 0) + 1;
      });

      project.tareas = nextTasks;
      return true;
    }

    function hasProjectRealActivity(list = projects) {
      return (Array.isArray(list) ? list : []).some((project) => {
        const logs = Array.isArray(project?.time_logs) ? project.time_logs : [];
        const hasLoggedTime = logs.some((log) => {
          const start = Number(log?.start || 0);
          const end = Number(log?.end || 0);
          return start > 0 && end > start;
        });

        const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
        const hasTaskProgress = tasks.some((task) => !!task?.done || Number(task?.total_seconds || 0) > 0);

        return hasLoggedTime || hasTaskProgress;
      });
    }

    function refreshProjectsSimpleModeUI() {
      isProjectsSimpleMode = !hasProjectRealActivity(projects);
      document.querySelectorAll('[data-advanced-control]').forEach((node) => {
        node.classList.toggle('hidden', isProjectsSimpleMode);
      });

      const taskBadge = document.getElementById('tasksQuickSimpleBadge');
      const listBadge = document.getElementById('listQuickSimpleBadge');
      if (taskBadge) taskBadge.classList.toggle('hidden', !isProjectsSimpleMode);
      if (listBadge) listBadge.classList.toggle('hidden', !isProjectsSimpleMode);
    }

    function countActiveFiltersForView(view = currentTaskView) {
      if (view === 'tareas') {
        let count = 0;
        if (globalTaskFilter !== 'all') count += 1;
        if (globalTaskSearchQuery.trim()) count += 1;
        return count;
      }
      if (view === 'lista') {
        let count = 0;
        if (listFilterPriority) count += 1;
        if (listFilterDate !== 'all') count += 1;
        if (listFilterSort !== 'newest') count += 1;
        if (listProjectSearchQuery.trim()) count += 1;
        return count;
      }
      return 0;
    }

    function renderQuickActionsStatus(view = currentTaskView) {
      const activeCount = countActiveFiltersForView(view);
      if (view === 'tareas') {
        const status = document.getElementById('tasksQuickFiltersStatus');
        const clearBtn = document.getElementById('tasksQuickClearBtn');
        if (status) status.textContent = `Filtros activos: ${activeCount}`;
        if (clearBtn) clearBtn.classList.toggle('hidden', activeCount === 0);
      }
      if (view === 'lista') {
        const status = document.getElementById('listQuickFiltersStatus');
        const clearBtn = document.getElementById('listQuickClearBtn');
        if (status) status.textContent = `Filtros activos: ${activeCount}`;
        if (clearBtn) clearBtn.classList.toggle('hidden', activeCount === 0);
      }
    }

    function getAvailableProjectsForQuickAction() {
      const baseList = Array.isArray(projects) ? projects : [];
      if (!currentClienteId) return baseList;
      const byClient = baseList.filter((project) => String(project?.cliente_id || '') === String(currentClienteId));
      return byClient.length ? byClient : baseList;
    }

    function openQuickProjectActionModal(mode) {
      const availableProjects = getAvailableProjectsForQuickAction();
      if (!availableProjects.length) {
        if (window.showNotification) window.showNotification('Primero crea un proyecto para continuar.', 'error');
        openNewProjectModal();
        return;
      }

      quickProjectActionMode = String(mode || '');
      const modal = document.getElementById('quickProjectActionModal');
      const title = document.getElementById('quickProjectActionTitle');
      const description = document.getElementById('quickProjectActionDescription');
      const search = document.getElementById('quickProjectActionSearch');
      const timerStepper = document.getElementById('quickProjectTimerStepper');
      const timerProgress = document.getElementById('quickProjectTimerProgress');

      if (!modal || !title || !description || !search) return;

      if (quickProjectActionMode === 'add-task') {
        title.textContent = '¿En qué proyecto crearás la tarea?';
        description.textContent = 'Selecciona el proyecto para abrir su modal y añadir la nueva tarea.';
        if (timerStepper) timerStepper.classList.add('hidden');
      } else {
        title.textContent = '¿En qué proyecto iniciarás el temporizador?';
        description.textContent = 'Primero elige proyecto y luego la tarea para comenzar a contar tiempo.';
        if (timerStepper) timerStepper.classList.remove('hidden');
        if (timerProgress) timerProgress.style.width = '50%';
      }

      search.value = '';
      renderQuickProjectActionList('');
      modal.classList.remove('hidden');
      setTimeout(() => search.focus(), 80);
    }

    function closeQuickProjectActionModal() {
      const modal = document.getElementById('quickProjectActionModal');
      if (modal) modal.classList.add('hidden');
      quickProjectActionMode = '';
    }

    function renderQuickProjectActionList(query = '') {
      const list = document.getElementById('quickProjectActionList');
      if (!list) return;
      const search = String(query || '').trim().toLowerCase();
      const availableProjects = getAvailableProjectsForQuickAction();

      const filtered = availableProjects.filter((project) => {
        const title = String(project?.titulo || '').toLowerCase();
        const client = String(project?.cliente || '').toLowerCase();
        const stage = String(project?.etapa || '').toLowerCase();
        return !search || title.includes(search) || client.includes(search) || stage.includes(search);
      });

      if (!filtered.length) {
        list.innerHTML = `<div class="rounded-xl border border-dashed border-slate-200 bg-white px-3 py-4 text-center">
          <div class="text-xs font-semibold text-slate-500">No hay proyectos que coincidan con la búsqueda.</div>
          <button type="button" onclick="openNewProjectModal(); closeQuickProjectActionModal();" class="mt-2 inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-3 text-xs font-bold text-slate-900 hover:bg-lime-200">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Crear proyecto</span>
          </button>
        </div>`;
        return;
      }

      list.innerHTML = filtered.map((project) => {
        const safeId = String(project?.id || '').replace(/'/g, "\\'");
        const title = escapeHtml(project?.titulo || 'Proyecto');
        const client = escapeHtml(project?.cliente || 'Sin cliente');
        const stage = escapeHtml(project?.etapa || 'Sin etapa');
        const tasksTotal = Array.isArray(project?.tareas) ? project.tareas.length : 0;
        return `<button type="button" onclick="confirmQuickProjectAction('${safeId}')" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left hover:bg-slate-100 transition-colors">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="truncate text-sm font-bold text-slate-800">${title}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">${client} · ${stage}</div>
            </div>
            <span class="text-[10px] font-bold text-slate-500 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 whitespace-nowrap">${tasksTotal} tareas</span>
          </div>
        </button>`;
      }).join('');
    }

    async function confirmQuickProjectAction(projectId) {
      const selectedProjectId = String(projectId || '').trim();
      if (!selectedProjectId) return;
      const mode = quickProjectActionMode;
      closeQuickProjectActionModal();

      if (mode === 'add-task') {
        openProject(selectedProjectId);
        setTimeout(() => {
          const input = document.getElementById('newTaskInput');
          if (input) input.focus();
        }, 160);
        return;
      }

      if (mode === 'start-timer') {
        const taskId = await openTimerTaskModal(selectedProjectId);
        if (taskId === undefined) return;
        await toggleTaskTimer(selectedProjectId, taskId || '');
      }
    }

    function quickAddTaskFromCurrentView() {
      openQuickProjectActionModal('add-task');
    }

    function quickStartTimerFromCurrentView() {
      openQuickProjectActionModal('start-timer');
    }

    function resetQuickFilters(view = currentTaskView) {
      if (view === 'tareas') {
        globalTaskFilter = 'all';
        globalTaskSearchQuery = '';
        globalTasksClientPage = 1;
        const tasksSearchInput = document.getElementById('tasksSearchInput');
        if (tasksSearchInput) tasksSearchInput.value = '';
        document.querySelectorAll('.global-task-filter').forEach((btn) => {
          const active = (btn.getAttribute('data-task-filter') || 'all') === 'all';
          btn.classList.toggle('is-active', active);
          btn.classList.toggle('bg-slate-900', active);
          btn.classList.toggle('text-white', active);
        });
        renderGlobalTasksView(projects);
        renderQuickActionsStatus('tareas');
        return;
      }

      if (view === 'lista') {
        clearListFilters();
      }
    }
    
    function enableDnD() {
      if (!kanban) return;
      const cards = kanban.querySelectorAll('[draggable="true"]');
      const zones = kanban.querySelectorAll('.drag-container');

      const clearKanbanDragPreview = () => {
        if (kanbanDragPreviewEl) {
          kanbanDragPreviewEl.remove();
          kanbanDragPreviewEl = null;
        }
      };

      const setFullKanbanDragImage = (event, card) => {
        clearKanbanDragPreview();
        const rect = card.getBoundingClientRect();
        const clone = card.cloneNode(true);
        clone.removeAttribute('draggable');
        clone.style.position = 'fixed';
        clone.style.top = '-10000px';
        clone.style.left = '-10000px';
        clone.style.width = `${rect.width}px`;
        clone.style.height = `${rect.height}px`;
        clone.style.pointerEvents = 'none';
        clone.style.opacity = '1';
        clone.style.transform = 'none';
        clone.style.zIndex = '2147483647';
        clone.classList.remove('opacity-50', 'rotate-2');
        clone.classList.add('shadow-2xl');
        document.body.appendChild(clone);
        kanbanDragPreviewEl = clone;
        try {
          event.dataTransfer.setDragImage(clone, Math.min(rect.width / 2, 180), Math.min(rect.height / 2, 120));
        } catch (_) {}
      };

      const resetDndState = () => {
        isDraggingCard = false;
        dragCardId = null;
        kanbanPanActive = false;
        kanbanPanMoved = false;
        clearKanbanDragPreview();
        document.getElementById('kanbanScroll')?.classList.remove('is-grabbing');
        document.getElementById('proyectos-kanban')?.classList.remove('is-grabbing');
      };

      cards.forEach(card => {
        card.addEventListener('dragstart', e => {
           resetDndState();
           dragCardId = card.getAttribute('data-id');
           e.dataTransfer.setData('text/plain', dragCardId);
           e.dataTransfer.setData('text', dragCardId);
           e.dataTransfer.effectAllowed = 'move';
           setFullKanbanDragImage(e, card);
           isDraggingCard = true;
           card.classList.add('opacity-50', 'rotate-2');
        });
        card.addEventListener('dragend', () => {
           card.classList.remove('opacity-50', 'rotate-2');
           resetDndState();
        });
      });

      zones.forEach(zone => {
        zone.addEventListener('dragenter', e => {
           e.preventDefault();
           zone.classList.add('bg-slate-200/50');
        });
        zone.addEventListener('dragover', e => {
           e.preventDefault();
           e.dataTransfer.dropEffect = 'move';
           zone.classList.add('bg-slate-200/50');
        });
        zone.addEventListener('dragleave', () => {
           zone.classList.remove('bg-slate-200/50');
        });
        zone.addEventListener('drop', async e => {
           e.preventDefault();
           e.stopPropagation();
           zone.classList.remove('bg-slate-200/50');
           
           // Obtener el id del proyecto
           let id = e.dataTransfer.getData('text/plain') || e.dataTransfer.getData('text') || dragCardId;
           const newStage = zone.getAttribute('data-stage');
           
           if (!id || !newStage) {
               console.warn('Drop failed:', {id, newStage, dragCardId});
               return;
           }
           
           // Actualizar localmente
           const p = projects.find(x => String(x.id) === String(id));
           if (p && p.etapa !== newStage) {
               const oldStage = p.etapa;
               p.etapa = newStage;
               renderKanban(projects);
               
               // Guardar en server
               try {
                   const response = await fetch('/api/proyectos/mover', {
                       method: 'POST',
                       headers: {
                           'Content-Type': 'application/json',
                           'X-CSRF-TOKEN': window.csrfToken
                       },
                       body: JSON.stringify({id, etapa: newStage})
                   });
                   
                   if (!response.ok) {
                       console.error('Server error:', response.status);
                       p.etapa = oldStage; // Revertir cambio
                       renderKanban(projects);
                   }
               } catch (err) {
                   console.error('Network error:', err);
                   p.etapa = oldStage; // Revertir cambio
                   renderKanban(projects);
               }
           }

           resetDndState();
        });
      });

      if (!window.__kanbanDndWindowBound) {
        window.addEventListener('dragend', resetDndState);
        window.addEventListener('drop', resetDndState);
        window.addEventListener('blur', resetDndState);
        window.__kanbanDndWindowBound = true;
      }
    }

    function handleCardClick(event, id) {
        if (Date.now() < kanbanPanSuppressClickUntil) return;
        if (isDraggingCard) return;
        openProject(id);
    }

    function editProjectTitle(element, id) {
        const originalText = element.innerText;
        element.contentEditable = true;
        element.focus();
        element.selectAll?.() || element.select?.();
        
        const saveTitle = async () => {
            element.contentEditable = false;
            const newTitle = element.innerText.trim();
            if (newTitle && newTitle !== originalText) {
                await updateTitle(id, newTitle);
            } else {
                element.innerText = originalText;
            }
        };
        
        element.onblur = saveTitle;
        element.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveTitle();
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                element.innerText = originalText;
                element.contentEditable = false;
            }
        };
    }

    function initKanbanDragScroll() {
      const scroller = document.getElementById('kanbanScroll');
      const panSurface = document.getElementById('proyectos-kanban') || scroller;
      if (!scroller || scroller.dataset.panReady === '1') return;
      scroller.dataset.panReady = '1';

      const isInteractiveTarget = (target) => {
        if (!target) return false;
        return !!target.closest('button,a,input,select,textarea,[contenteditable="true"],.ts-dropdown,[data-no-pan],[draggable="true"]');
      };

      let kanbanPanStartY = 0;

      panSurface.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        if (isInteractiveTarget(e.target)) return;

        kanbanPanActive = true;
        kanbanPanMoved = false;
        kanbanPanStartX = e.clientX;
        kanbanPanStartY = e.clientY;
        kanbanPanStartLeft = scroller.scrollLeft;
        scroller.classList.add('is-grabbing');
        panSurface.classList.add('is-grabbing');
      });

      window.addEventListener('mousemove', (e) => {
        if (!kanbanPanActive) return;
        const deltaX = e.clientX - kanbanPanStartX;
        const deltaY = e.clientY - kanbanPanStartY;
        
        // Solo activar pan si el movimiento es principalmente horizontal
        if (Math.abs(deltaX) > 3 && Math.abs(deltaX) > Math.abs(deltaY) * 1.5) {
          kanbanPanMoved = true;
        }
        
        if (kanbanPanMoved) {
          e.preventDefault();
          scroller.scrollLeft = kanbanPanStartLeft - deltaX;
        }
      }, { passive: false });

      const stopPan = () => {
        if (!kanbanPanActive) return;
        if (kanbanPanMoved) {
          kanbanPanSuppressClickUntil = Date.now() + 180;
        }
        kanbanPanActive = false;
        kanbanPanMoved = false;
        scroller.classList.remove('is-grabbing');
        panSurface.classList.remove('is-grabbing');
      };

      window.addEventListener('mouseup', stopPan);
      panSurface.addEventListener('mouseleave', stopPan);
      scroller.addEventListener('dragstart', (e) => {
        if (kanbanPanActive || kanbanPanMoved) {
          e.preventDefault();
        }
        stopPan();
      });

      window.addEventListener('blur', stopPan);
    }

    async function toggleTimer(id, action) {
        try {
            if (action === 'start') {
              const taskId = await openTimerTaskModal(id);
              if (typeof taskId === 'undefined') return;
              await sendTimerAction(id, 'start', taskId || null);
            } else {
              await sendTimerAction(id, 'stop', null);
            }
            loadData();
        } catch (e) {
            console.error(e);
        }
    }

    function selectTimerTask(taskId) {
      pendingTimerTaskId = String(taskId || '');
      const select = document.getElementById('timerTaskSelect');
      if (select) select.value = pendingTimerTaskId;

      document.querySelectorAll('[data-timer-task-option]').forEach((node) => {
        const nodeValue = String(node.getAttribute('data-task-id') || '');
        const selected = nodeValue === pendingTimerTaskId;
        node.classList.toggle('bg-lime-100', selected);
        node.classList.toggle('border-lime-300', selected);
        node.classList.toggle('text-slate-900', selected);
        node.classList.toggle('bg-white', !selected);
        node.classList.toggle('border-slate-200', !selected);
      });
    }

    function renderTimerTaskList(tasks) {
      const list = document.getElementById('timerTaskList');
      if (!list) return;

      const base = `<button type="button" data-timer-task-option data-task-id="" onclick="selectTimerTask('')" class="w-full text-left rounded-xl border px-3 py-2 text-sm font-semibold bg-white border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors">Sin tarea vinculada</button>`;
      const items = tasks.map((t) => {
        const safeId = String(t.id ?? '').replace(/'/g, "\\'");
        const safeText = escapeHtml(String(t.texto || 'Tarea sin nombre'));
        const status = t.done ? '<span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold">Completada</span>' : '<span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-bold">Pendiente</span>';
        return `<button type="button" data-timer-task-option data-task-id="${safeId}" onclick="selectTimerTask('${safeId}')" class="w-full text-left rounded-xl border px-3 py-2 bg-white border-slate-200 hover:bg-slate-100 transition-colors">
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-semibold text-slate-700 truncate">${safeText}</span>
            ${status}
          </div>
        </button>`;
      });

      list.innerHTML = [base].concat(items).join('');
      selectTimerTask('');
    }

    function openTimerTaskModal(projectId) {
      const p = projects.find(x => x.id === projectId);
      const modal = document.getElementById('timerTaskModal');
      const select = document.getElementById('timerTaskSelect');
      const projectLabel = document.getElementById('timerTaskProjectLabel');
      if (!modal || !select) return Promise.resolve(undefined);

      const tasks = Array.isArray(p?.tareas) ? p.tareas : [];
      select.innerHTML = ['<option value="">Sin tarea vinculada</option>']
        .concat(tasks.map(t => `<option value="${t.id}">${t.texto}</option>`))
        .join('');
      renderTimerTaskList(tasks);
      pendingTimerTaskId = '';
      if (projectLabel) projectLabel.textContent = `Proyecto: ${String(p?.titulo || 'Proyecto')}`;

      pendingTimerProjectId = projectId;
      modal.classList.remove('hidden');

      return new Promise(resolve => {
        pendingTimerResolver = resolve;
      });
    }

    function closeTimerTaskModal() {
      const modal = document.getElementById('timerTaskModal');
      if (modal) modal.classList.add('hidden');
      if (pendingTimerResolver) pendingTimerResolver(undefined);
      pendingTimerResolver = null;
      pendingTimerProjectId = null;
      pendingTimerTaskId = '';
    }

    function confirmTimerTaskSelection() {
      const modal = document.getElementById('timerTaskModal');
      const select = document.getElementById('timerTaskSelect');
      if (modal) modal.classList.add('hidden');
      if (pendingTimerResolver) pendingTimerResolver(pendingTimerTaskId || select?.value || '');
      pendingTimerResolver = null;
      pendingTimerProjectId = null;
      pendingTimerTaskId = '';
    }

    function setAddTimeTaskOptions(projectId) {
      const taskSelect = document.getElementById('addTimeTaskSelect');
      if (!taskSelect) return;

      const selectedProject = projects.find(p => String(p.id) === String(projectId));
      const tasks = Array.isArray(selectedProject?.tareas) ? selectedProject.tareas : [];
      const options = `<option value="">Sin tarea vinculada</option>` + tasks.map(t =>
        `<option value="${t.id || ''}">${escapeHtml(t.texto || 'Tarea sin nombre')}</option>`
      ).join('');
      taskSelect.innerHTML = options;
    }

    function openAddTimeModal(preselectedProjectId = currentProjectId, preselectedTaskId = '') {
      const modal = document.getElementById('addTimeModal');
      if (!modal) return;

      modal.classList.remove('hidden');

      const projectSelect = document.getElementById('addTimeProjectSelect');
      if (projectSelect) {
        projectSelect.innerHTML = projects
          .map(p => `<option value="${p.id || ''}">${escapeHtml(p.titulo || 'Proyecto')}</option>`)
          .join('');

        const selectedProjectId = String(preselectedProjectId || (projects[0]?.id || ''));
        projectSelect.value = selectedProjectId;
        setAddTimeTaskOptions(selectedProjectId);
        const taskSelect = document.getElementById('addTimeTaskSelect');
        if (taskSelect && preselectedTaskId) taskSelect.value = String(preselectedTaskId);
        projectSelect.onchange = (e) => setAddTimeTaskOptions(e.target.value);
      }
      
      // Reset inputs
      document.getElementById('addTimeHours').value = '0';
      document.getElementById('addTimeMinutes').value = '0';
    }

    function openTaskAddTimeModal() {
      if (!currentProjectId || !currentTaskId) return;
      openAddTimeModal(currentProjectId, currentTaskId);
    }
    window.openTaskAddTimeModal = openTaskAddTimeModal;

    function closeAddTimeModal() {
      const modal = document.getElementById('addTimeModal');
      if (modal) modal.classList.add('hidden');
    }

    async function saveAddedTime() {
      const projectId = document.getElementById('addTimeProjectSelect').value || currentProjectId;
      const hours = parseInt(document.getElementById('addTimeHours').value || 0);
      const minutes = parseInt(document.getElementById('addTimeMinutes').value || 0);
      const taskId = document.getElementById('addTimeTaskSelect').value || null;

      if (!projectId) {
        alert('Por favor selecciona un proyecto');
        return;
      }

      if (hours === 0 && minutes === 0) {
        alert('Por favor ingresa un tiempo válido');
        return;
      }

      try {
        const response = await fetch('/api/proyectos/tiempo/manual', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
          },
          body: JSON.stringify({
            id: projectId,
            tarea_id: taskId,
            horas: hours,
            minutos: minutes
          })
        });

        if (response.ok) {
          closeAddTimeModal();
          await loadData(); // Recargar datos para mostrar el tiempo agregado
          if (currentProjectId && currentTaskId) {
            const project = projects.find(p => String(p.id) === String(currentProjectId));
            const task = (project?.tareas || []).find(t => String(t.id) === String(currentTaskId));
            if (task) renderTaskDetail(task, { preserveState: true });
          }
        } else {
          alert('Error al guardar el tiempo');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Error al guardar el tiempo');
      }
    }

    function isProjectCompleted(project) {
      const taskPct = Number(getProjectTaskStats(project || {}).pct || 0);
      const projectPct = Number(project?.progreso || 0);
      return taskPct >= 100 || projectPct >= 100;
    }

    function formatArchivedCreatedAt(project) {
      const raw = project?.created_at || project?.inicio || null;
      if (!raw) return 'Sin fecha';
      const normalized = /^\d{4}-\d{2}-\d{2}$/.test(String(raw)) ? `${raw}T12:00:00` : raw;
      const date = new Date(normalized);
      if (Number.isNaN(date.getTime())) return 'Sin fecha';
      return date.toLocaleDateString('es-ES');
    }

    function renderArchivedProjectsModalList(list) {
      const body = document.getElementById('archivedProjectsModalBody');
      const empty = document.getElementById('archivedProjectsModalEmpty');
      const deleteAllBtn = document.getElementById('deleteAllArchivedBtn');
      if (!body || !empty) return;

      const rows = Array.isArray(list) ? list : [];
      if (!rows.length) {
        body.innerHTML = '';
        empty.classList.remove('hidden');
        if (deleteAllBtn) {
          deleteAllBtn.classList.add('hidden');
          deleteAllBtn.disabled = true;
        }
        return;
      }

      empty.classList.add('hidden');
      if (deleteAllBtn) {
        deleteAllBtn.classList.remove('hidden');
        deleteAllBtn.disabled = false;
      }
      body.innerHTML = rows.map((p) => {
        const safeId = String(p.id || '').replace(/'/g, "\\'");
        const title = escapeHtml(p.titulo || 'Proyecto sin título');
        const client = escapeHtml(p.cliente || 'Sin cliente');
        const createdAt = formatArchivedCreatedAt(p);
        const progress = Number(getProjectTaskStats(p).pct || 0);
        const completed = isProjectCompleted(p);

        return `<tr class="hover:bg-slate-50 transition-colors">
          <td class="px-4 py-3 font-semibold text-slate-900">
            <button type="button" onclick="openArchivedProjectDetails('${safeId}')" class="text-left hover:underline">${title}</button>
          </td>
          <td class="px-4 py-3 text-slate-700">${client}</td>
          <td class="px-4 py-3 text-slate-700">${createdAt}</td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200">
                <div class="h-2 rounded-full bg-slate-900" style="width:${progress}%"></div>
              </div>
              <span class="text-xs font-bold text-slate-700">${progress}%</span>
            </div>
          </td>
          <td class="px-4 py-3">
            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold ${completed ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'}">${completed ? 'Si, 100%' : 'No'}</span>
          </td>
          <td class="px-4 py-3">
            <div class="inline-flex items-center gap-1">
              <button type="button" onclick="restoreProject('${safeId}')" class="inline-grid place-content-center w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1 shadow-sm" title="Restaurar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              </button>
              <button type="button" onclick="permanentlyDeleteProject('${safeId}')" class="inline-grid place-content-center w-9 h-9 rounded-full border border-rose-200 text-rose-500 hover:text-rose-700 hover:bg-rose-50 hover:border-rose-300 transition-all focus:outline-none focus:ring-2 focus:ring-rose-200 focus:ring-offset-1 shadow-sm" title="Eliminar permanente">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </td>
        </tr>`;
      }).join('');
    }

    async function deleteAllArchivedProjects() {
      const rows = Array.isArray(archivedProjects) ? archivedProjects.filter((p) => p && p.id) : [];
      if (!rows.length) {
        alert('No hay proyectos archivados para eliminar.');
        return;
      }

      const confirmed = confirm(`Se eliminarán ${rows.length} proyecto(s) de forma permanente. Esta acción no se puede deshacer. ¿Deseas continuar?`);
      if (!confirmed) return;

      const deleteAllBtn = document.getElementById('deleteAllArchivedBtn');
      if (deleteAllBtn) {
        deleteAllBtn.disabled = true;
        deleteAllBtn.textContent = 'Eliminando...';
      }

      try {
        for (const project of rows) {
          const response = await fetch('/api/proyectos/eliminar', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({ id: project.id })
          });

          if (!response.ok) {
            throw new Error('delete_archived_failed');
          }
        }

        await loadData();
      } catch (err) {
        console.error('Error:', err);
        alert('No se pudieron eliminar todos los proyectos archivados.');
      } finally {
        if (deleteAllBtn) {
          deleteAllBtn.disabled = false;
          deleteAllBtn.textContent = 'Eliminar todos';
        }
      }
    }

    async function loadArchivedProjects() {
      const qs = currentClienteId ? ('?cliente_id=' + encodeURIComponent(currentClienteId)) : '';
      const res = await fetch('/api/proyectos/archivados' + qs);
      const json = await res.json().catch(() => ({}));
      archivedProjects = Array.isArray(json.data) ? json.data : [];
      renderArchivedProjectsView(archivedProjects);
      renderArchivedProjectsModalList(archivedProjects);
      return archivedProjects;
    }

    function openArchivedProjectsModal() {
      const modal = document.getElementById('archivedProjectsModal');
      if (!modal) return;
      modal.classList.remove('hidden');
      loadArchivedProjects();
    }

    function closeArchivedProjectsModal() {
      const modal = document.getElementById('archivedProjectsModal');
      if (modal) modal.classList.add('hidden');
    }

    function openArchivedProjectDetails(projectId) {
      if (!projectId) return;
      closeArchivedProjectsModal();
      openProject(projectId, { readOnly: true, useArchivedData: true });
    }

    async function restoreProject(projectId) {
      try {
        const response = await fetch('/api/proyectos/actualizar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
          },
          body: JSON.stringify({
            id: projectId,
            archived: false
          })
        });

        if (response.ok) {
          await loadData();
        } else {
          alert('Error al restaurar el proyecto');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Error al restaurar el proyecto');
      }
    }

    async function archiveProjectById(projectId, options = {}) {
      if (!projectId) return;
      const confirmed = confirm('¿Estás seguro de que quieres archivar este proyecto? Puedes restaurarlo desde la pestaña "Archivados".');
      if (!confirmed) return;

      try {
        const response = await fetch('/api/proyectos/actualizar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
          },
          body: JSON.stringify({
            id: projectId,
            archived: true,
            archived_at: new Date().toISOString()
          })
        });

        if (response.ok) {
          if (options?.closeProjectModal) {
            closeProjectModal();
            return;
          }
          await loadData();
        } else {
          alert('Error al archivar el proyecto');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Error al archivar el proyecto');
      }
    }

    async function archiveProject() {
      if (!currentProjectId) return;
      await archiveProjectById(currentProjectId, { closeProjectModal: true });
    }

    async function permanentlyDeleteProject(projectId) {
      if (!projectId) return;
      const confirmed = confirm('Esta acción eliminará el proyecto permanentemente. ¿Deseas continuar?');
      if (!confirmed) return;

      try {
        const response = await fetch('/api/proyectos/eliminar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
          },
          body: JSON.stringify({ id: projectId })
        });

        if (response.ok) {
          await loadData();
        } else {
          alert('No se pudo eliminar el proyecto');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('No se pudo eliminar el proyecto');
      }
    }

    async function sendTimerAction(projectId, action, taskId = null) {
      const res = await fetch('/api/proyectos/timer', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: projectId, action, tarea_id: taskId || null})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        throw new Error('timer_action_failed');
      }
      return data.item;
    }

    function kanbanAddCardButton(stage) {
      const safeStageArg = escapeHtml(JSON.stringify(stage || ''));
      return `<button type="button" data-no-pan onclick="addProjectTo(${safeStageArg})" class="w-full rounded-xl border border-dashed border-slate-200 bg-white/70 px-3 py-3 text-sm font-extrabold text-slate-500 transition hover:border-lime-300 hover:bg-lime-50 hover:text-slate-900">
        <span class="inline-flex items-center justify-center gap-2">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m7-7H5"/></svg>
          Añadir tarjeta
        </span>
      </button>`;
    }

    function addProjectTo(stage) {
        let etapa = stage || stages[0];
        if (!etapa) {
            etapa = 'Prospecto';
            stages = ['Prospecto'];
            saveStages();
        }
        openNewProjectModal(etapa);
    }
    window.addProjectTo = addProjectTo;

    function renderNewProjectCoverPicker() {
      const preview = document.getElementById('newProjectCoverPreview');
      const gallery = document.getElementById('newProjectCoverGallery');
      const [from, to] = parseProjectCoverColor(newProjectCoverColor) || PROJECT_COVER_PALETTES[0];

      if (preview) {
        preview.style.setProperty('--cover-from', from);
        preview.style.setProperty('--cover-to', to);
        preview.style.backgroundImage = newProjectCoverImage
          ? `url("${newProjectCoverImage}")`
          : '';
      }

      if (gallery) {
        gallery.innerHTML = renderProjectCoverPopover({
          activeImage: newProjectCoverImage,
          activeColor: newProjectCoverColor,
          target: 'new',
        });
      }
    }

    function renderProjectCoverPopover({ activeImage = '', activeColor = '', target = 'new' } = {}) {
      const active = String(activeImage || '').trim();
      const photosKey = `${target}Photos`;
      const colorsKey = `${target}Colors`;
      const photosExpanded = !!projectCoverPickerExpanded[photosKey];
      const colorsExpanded = !!projectCoverPickerExpanded[colorsKey];
      const photos = photosExpanded ? PROJECT_COVER_PRESETS : PROJECT_COVER_PRESETS.slice(0, 4);
      const colors = colorsExpanded ? PROJECT_COVER_PALETTES : PROJECT_COVER_PALETTES.slice(0, 4);
      const inputId = `${target}ProjectCoverUploadInput`;
      const photoOptions = photos.map((preset, index) => {
        const isActive = active === preset.url;
        const originalIndex = PROJECT_COVER_PRESETS.indexOf(preset);
        const action = target === 'modal'
          ? `selectModalCoverPreset(${originalIndex})`
          : `selectNewProjectCoverPreset(${originalIndex})`;
        return `<button type="button" class="project-cover-option ${isActive ? 'is-active' : ''}" style="background-image:url('${escapeHtml(preset.url)}')" onclick="${action}" aria-label="Usar portada ${escapeHtml(preset.name)}">
          <span>${escapeHtml(preset.name)}</span>
        </button>`;
      }).join('');
      const colorOptions = colors.map(([start, end]) => {
        const value = `${start}|${end}`;
        const isActive = !active && value === activeColor;
        const action = target === 'modal'
          ? `selectModalCoverColor('${value}')`
          : `selectNewProjectCoverColor('${value}')`;
        return `<button type="button" class="project-cover-color-option ${isActive ? 'is-active' : ''}" style="background:linear-gradient(135deg, ${start}, ${end});" onclick="${action}" aria-label="Usar color de portada"></button>`;
      }).join('');

      return `<div class="project-cover-section">
        <div class="project-cover-section-header">
          <div class="project-cover-section-title">Fondos de imagen</div>
          <button type="button" class="project-cover-more-btn" onclick="toggleProjectCoverMore('${target}', 'photos', event)">${photosExpanded ? 'Ver menos' : 'Ver más'}</button>
        </div>
        <div class="project-cover-grid">${photoOptions}</div>
      </div>
      <div class="project-cover-section">
        <div class="project-cover-section-header">
          <div class="project-cover-section-title">Colores</div>
          <button type="button" class="project-cover-more-btn" onclick="toggleProjectCoverMore('${target}', 'colors', event)">${colorsExpanded ? 'Ver menos' : 'Ver más'}</button>
        </div>
        <div class="project-cover-grid is-colors">${colorOptions}</div>
      </div>
      <div class="project-cover-section">
        <div class="project-cover-section-header">
          <div class="project-cover-section-title">Subir personalizada</div>
        </div>
        <input id="${inputId}" type="file" accept="image/*" class="hidden" onchange="handleProjectCoverCustomUpload(this.files, '${target}'); this.value='';">
        <button type="button" class="project-cover-upload-btn" onclick="document.getElementById('${inputId}')?.click()">
          <span class="project-cover-upload-icon">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2 1.6-1.6a2 2 0 012.8 0L20 14m-9-6h.01M5 20h14a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
          </span>
          <span>
            <span class="block text-xs font-black text-slate-800">Elegir imagen</span>
            <span class="block text-[10px] font-bold text-slate-500">JPG, PNG o WebP</span>
          </span>
        </button>
      </div>`;
    }

    function toggleProjectCoverMore(target = 'new', type = 'photos', event = null) {
      event?.preventDefault?.();
      event?.stopPropagation?.();
      const key = `${target}${type === 'colors' ? 'Colors' : 'Photos'}`;
      projectCoverPickerExpanded[key] = !projectCoverPickerExpanded[key];
      if (target === 'modal') renderModalCoverPicker();
      else renderNewProjectCoverPicker();
      positionOpenProjectCoverPicker(target);
    }
    window.toggleProjectCoverMore = toggleProjectCoverMore;

    function positionProjectCoverPopover(gallery, trigger) {
      if (!gallery || !trigger) return;
      const gap = 10;
      const margin = 12;
      gallery.style.visibility = 'hidden';
      gallery.classList.remove('hidden');

      const triggerRect = trigger.getBoundingClientRect();
      const popoverRect = gallery.getBoundingClientRect();
      const maxLeft = window.innerWidth - popoverRect.width - margin;
      let left = Math.min(Math.max(margin, triggerRect.right - popoverRect.width), maxLeft);
      let top = triggerRect.bottom + gap;

      if (top + popoverRect.height > window.innerHeight - margin) {
        top = Math.max(margin, triggerRect.top - popoverRect.height - gap);
      }

      gallery.style.left = `${left}px`;
      gallery.style.top = `${top}px`;
      gallery.style.right = 'auto';
      gallery.style.visibility = '';
    }

    function positionOpenProjectCoverPicker(target = 'new') {
      const galleryId = target === 'modal' ? 'modalCoverGallery' : 'newProjectCoverGallery';
      const gallery = document.getElementById(galleryId);
      if (!gallery || gallery.classList.contains('hidden')) return;
      const trigger = gallery.closest('.project-cover-trigger-wrap')?.querySelector('button');
      positionProjectCoverPopover(gallery, trigger);
    }

    function toggleNewProjectCoverGallery(forceState = null) {
      const gallery = document.getElementById('newProjectCoverGallery');
      if (!gallery) return;
      const shouldShow = forceState === null ? gallery.classList.contains('hidden') : !!forceState;
      if (shouldShow) document.getElementById('modalCoverGallery')?.classList.add('hidden');
      gallery.classList.toggle('hidden', !shouldShow);
      if (shouldShow) {
        renderNewProjectCoverPicker();
        positionOpenProjectCoverPicker('new');
      }
    }
    window.toggleNewProjectCoverGallery = toggleNewProjectCoverGallery;

    function selectNewProjectCoverPreset(index) {
      const preset = PROJECT_COVER_PRESETS[Number(index)];
      if (!preset) return;
      clearNewProjectCoverFile();
      newProjectCoverImage = preset.url;
      renderNewProjectCoverPicker();
      toggleNewProjectCoverGallery(false);
    }
    window.selectNewProjectCoverPreset = selectNewProjectCoverPreset;

    function selectNewProjectCoverColor(value) {
      newProjectCoverColor = value || `${PROJECT_COVER_PALETTES[0][0]}|${PROJECT_COVER_PALETTES[0][1]}`;
      clearNewProjectCoverFile();
      newProjectCoverImage = '';
      renderNewProjectCoverPicker();
      toggleNewProjectCoverGallery(false);
    }
    window.selectNewProjectCoverColor = selectNewProjectCoverColor;

    function clearNewProjectCoverImage() {
      newProjectCoverImage = '';
      clearNewProjectCoverFile();
      renderNewProjectCoverPicker();
    }
    window.clearNewProjectCoverImage = clearNewProjectCoverImage;

    function clearNewProjectCoverFile() {
      newProjectCoverFile = null;
      if (newProjectCoverObjectUrl) {
        URL.revokeObjectURL(newProjectCoverObjectUrl);
        newProjectCoverObjectUrl = '';
      }
    }

    async function handleProjectCoverCustomUpload(files, target = 'new') {
      const file = Array.from(files || [])[0];
      if (!file) return;
      if (!String(file.type || '').startsWith('image/')) {
        if (window.showNotification) window.showNotification('Elige una imagen para la portada', 'error');
        return;
      }

      if (target === 'modal') {
        toggleModalCoverGallery(false);
        await uploadAndApplyProjectCover(file, currentProjectId).catch(() => {});
        return;
      }

      clearNewProjectCoverFile();
      newProjectCoverFile = file;
      newProjectCoverObjectUrl = URL.createObjectURL(file);
      newProjectCoverImage = newProjectCoverObjectUrl;
      renderNewProjectCoverPicker();
      toggleNewProjectCoverGallery(false);
    }
    window.handleProjectCoverCustomUpload = handleProjectCoverCustomUpload;

    function renderModalCoverPicker(project = null) {
      const p = project || projects.find(x => String(x.id) === String(currentProjectId)) || {};
      const preview = document.getElementById('modalCoverPreview');
      const gallery = document.getElementById('modalCoverGallery');
      const clearBtn = document.getElementById('modalCoverClearBtn');
      const coverImage = String(p.cover_image || '').trim();
      const [from, to] = parseProjectCoverColor(p.cover_color) || boardCoverTone(p, 0);

      if (preview) {
        preview.style.setProperty('--cover-from', from);
        preview.style.setProperty('--cover-to', to);
        preview.style.backgroundImage = coverImage ? `url("${coverImage}")` : '';
      }
      if (gallery) {
        gallery.innerHTML = renderProjectCoverPopover({
          activeImage: coverImage,
          activeColor: p.cover_color || '',
          target: 'modal',
        });
      }
      clearBtn?.classList.toggle('hidden', !coverImage);
    }

    async function saveModalCoverImage(value) {
      if (projectModalReadOnly || !currentProjectId) return;
      const coverImage = String(value || '').trim();
      const project = projects.find(x => String(x.id) === String(currentProjectId));
      if (project) project.cover_image = coverImage;
      renderModalCoverPicker(project);
      const updated = await updateProjectField('cover_image', coverImage || null);
      if (updated) renderModalCoverPicker(updated);
      renderKanban(projects);
      if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
        renderProjectBoard(currentProjectId);
      }
    }

    async function saveModalCoverColor(value) {
      if (projectModalReadOnly || !currentProjectId) return;
      const coverColor = String(value || '').trim() || `${PROJECT_COVER_PALETTES[0][0]}|${PROJECT_COVER_PALETTES[0][1]}`;
      const project = projects.find(x => String(x.id) === String(currentProjectId));
      if (project) {
        project.cover_image = '';
        project.cover_color = coverColor;
      }
      renderModalCoverPicker(project);
      await updateProjectField('cover_image', null);
      const updated = await updateProjectField('cover_color', coverColor);
      if (updated) renderModalCoverPicker(updated);
      renderKanban(projects);
      if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
        renderProjectBoard(currentProjectId);
      }
    }

    function toggleModalCoverGallery(forceState = null) {
      const gallery = document.getElementById('modalCoverGallery');
      if (!gallery) return;
      const shouldShow = forceState === null ? gallery.classList.contains('hidden') : !!forceState;
      if (shouldShow) document.getElementById('newProjectCoverGallery')?.classList.add('hidden');
      gallery.classList.toggle('hidden', !shouldShow);
      if (shouldShow) {
        renderModalCoverPicker();
        positionOpenProjectCoverPicker('modal');
      }
    }
    window.toggleModalCoverGallery = toggleModalCoverGallery;

    function closeProjectCoverPickers() {
      document.getElementById('newProjectCoverGallery')?.classList.add('hidden');
      document.getElementById('modalCoverGallery')?.classList.add('hidden');
    }

    function selectModalCoverPreset(index) {
      const preset = PROJECT_COVER_PRESETS[Number(index)];
      if (!preset) return;
      toggleModalCoverGallery(false);
      saveModalCoverImage(preset.url).catch(() => {
        if (window.showNotification) window.showNotification('No se pudo actualizar la portada', 'error');
      });
    }
    window.selectModalCoverPreset = selectModalCoverPreset;

    function selectModalCoverColor(value) {
      toggleModalCoverGallery(false);
      saveModalCoverColor(value).catch(() => {
        if (window.showNotification) window.showNotification('No se pudo actualizar el color de portada', 'error');
      });
    }
    window.selectModalCoverColor = selectModalCoverColor;

    function clearModalCoverImage() {
      toggleModalCoverGallery(false);
      saveModalCoverImage('').catch(() => {
        if (window.showNotification) window.showNotification('No se pudo quitar la portada', 'error');
      });
    }
    window.clearModalCoverImage = clearModalCoverImage;

    function openNewProjectModal(stage = '') {
        const modal = document.getElementById('newProjectModal');
        clearNewProjectCoverFile();
        newProjectCoverColor = `${PROJECT_COVER_PALETTES[0][0]}|${PROJECT_COVER_PALETTES[0][1]}`;
        newProjectCoverImage = '';
        renderNewProjectCoverPicker();
        toggleNewProjectCoverGallery(false);
        document.getElementById('newProjectTitle').value = '';
        document.getElementById('newProjectDescription').value = '';
        document.getElementById('newProjectPriority').value = 'Con calma';
        document.getElementById('newProjectClient').value = currentClienteId || '';
      document.getElementById('newProjectPlannedDays').value = '0';
      document.getElementById('newProjectPlannedHours').value = '0';
      document.getElementById('newProjectPlannedMinutes').value = '0';
      initNewProjectDatePickers();
      if (newProjectStartPicker) newProjectStartPicker.clear();
      if (newProjectDuePicker) newProjectDuePicker.clear();
        modal.classList.remove('hidden');
        setTimeout(() => document.getElementById('newProjectTitle')?.focus(), 0);
    }

    function closeNewProjectModal() {
      if (newProjectStartPicker) newProjectStartPicker.close();
      if (newProjectDuePicker) newProjectDuePicker.close();
      clearNewProjectCoverFile();
        document.getElementById('newProjectModal').classList.add('hidden');
    }

    function handleNewProjectModalBackdropClick() {}

    async function createProjectFromModal() {
        const titulo = document.getElementById('newProjectTitle').value.trim();
        const descripcion = document.getElementById('newProjectDescription').value.trim();
        const etapa = (stages && stages[0]) || 'Prospecto';
        const prioridad = normalizePriority(document.getElementById('newProjectPriority').value || 'Con calma');
        const cliente = document.getElementById('newProjectClient').value || (currentClienteId || 'general');
        const inicio = document.getElementById('newProjectStart').value || null;
        const vencimiento = document.getElementById('newProjectDue').value || null;
      const plannedDays = Math.max(0, Number(document.getElementById('newProjectPlannedDays').value || 0));
      const plannedHours = Math.max(0, Number(document.getElementById('newProjectPlannedHours').value || 0));
      const plannedMinutes = Math.max(0, Number(document.getElementById('newProjectPlannedMinutes').value || 0));
        const plannedSeconds = Math.floor((plannedDays * 86400) + (plannedHours * 3600) + (plannedMinutes * 60));
        if (!titulo) return;
        const response = await fetch('/api/proyectos/crear', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({
                cliente_id: cliente,
                titulo,
                prioridad,
                etapa,
                descripcion,
                inicio,
                vencimiento,
                cover_color: newProjectCoverColor,
                cover_image: newProjectCoverFile ? '' : newProjectCoverImage,
                planned_seconds: plannedSeconds
            })
        });

        const payload = await response.json().catch(() => ({}));
        const createdProjectId = payload?.item?.id;
        if (response.ok && createdProjectId && newProjectCoverFile) {
          await uploadAndApplyProjectCover(newProjectCoverFile, createdProjectId, { silent: true }).catch(() => {
            if (window.showNotification) window.showNotification('El proyecto se creó, pero no se pudo subir la portada', 'error');
          });
        }

        closeNewProjectModal();
        loadData();
    }

    function setProjectModalReadOnly(readOnly = false) {
      projectModalReadOnly = !!readOnly;

      const titleInput = document.getElementById('modalTitle');
      const clientSelect = document.getElementById('modalClientSelect');
      const desc = document.getElementById('modalDesc');
      const descAutosaveStatus = document.getElementById('modalDescAutosaveStatus');
      const taskInput = document.getElementById('newTaskInput');
      const taskAddWrap = document.getElementById('modalTaskAddWrap');
      const dropzone = document.getElementById('modalDropzone');
      const noteToggleBtn = document.getElementById('projectNoteToggleBtn');
      const noteComposer = document.getElementById('projectNoteComposer');
      const notesTabBtn = document.querySelector('[data-project-tab="notes"]');
      const sidebar = document.getElementById('projectModalSidebar');
      const prioritySelector = document.getElementById('modalPrioritySelector');
      const responsablesInput = document.getElementById('newResponsibleInput');
      const dueDateInput = document.getElementById('modalDueDate');

      if (titleInput) {
        titleInput.readOnly = projectModalReadOnly;
        titleInput.classList.toggle('pointer-events-none', projectModalReadOnly);
      }
      if (clientSelect) clientSelect.disabled = projectModalReadOnly;
      if (desc) setCompactDescEditable('modalDesc', !projectModalReadOnly);
      if (descAutosaveStatus) descAutosaveStatus.classList.toggle('hidden', projectModalReadOnly);
      if (taskInput) taskInput.disabled = projectModalReadOnly;
      if (taskAddWrap) taskAddWrap.classList.toggle('hidden', projectModalReadOnly);
      if (dropzone) dropzone.classList.toggle('hidden', projectModalReadOnly);
      if (noteToggleBtn) noteToggleBtn.classList.toggle('hidden', projectModalReadOnly);
      if (noteComposer && projectModalReadOnly) noteComposer.classList.add('hidden');
      if (notesTabBtn) notesTabBtn.classList.toggle('hidden', projectModalReadOnly);
      if (sidebar) sidebar.classList.toggle('hidden', projectModalReadOnly);
      if (prioritySelector) prioritySelector.classList.toggle('pointer-events-none', projectModalReadOnly);
      if (prioritySelector) prioritySelector.classList.toggle('opacity-70', projectModalReadOnly);
      if (responsablesInput) responsablesInput.disabled = projectModalReadOnly;
      if (dueDateInput) dueDateInput.disabled = projectModalReadOnly;

      if (projectModalReadOnly) {
        setProjectModalTab('info');
      }
    }

    // --- Modal Functions ---
    async function openProject(id, options = {}) {
        currentProjectId = id;
      currentTaskId = null;
      currentProjectModalTab = 'info';
      currentProjectEditingNoteId = null;
      isProjectNoteComposerOpen = false;
      const useArchivedData = !!options?.useArchivedData;
      const sourceList = useArchivedData ? archivedProjects : projects;
      let p = sourceList.find(x => String(x.id) === String(id));
      if (!p && useArchivedData) {
        const res = await fetch(`/api/proyectos/${encodeURIComponent(String(id))}`);
        const json = await res.json().catch(() => ({}));
        p = json?.data || null;
      }
        if (!p) return;

      window.__infocusAiCurrentProject = {
        id: String(p.id || id || ''),
        title: String(p.titulo || 'Proyecto'),
      };

      setProjectModalReadOnly(!!options?.readOnly);

        // Header
        setModalHeaderAvatar(p);
        document.getElementById('modalTitle').value = p.titulo;
        document.getElementById('modalTitle').onblur = (e) => updateTitle(id, e.target.value);

        const clientSelect = document.getElementById('modalClientSelect');
        clientSelect.innerHTML = [`<option value="">Sin Cliente</option>`]
          .concat((clientesData || []).map(c => `<option value="${c.id}">${c.empresa}</option>`))
          .join('');
        clientSelect.value = p.cliente_id || '';
        clientSelect.onchange = (e) => updateProjectClient(e.target.value, e.target.options[e.target.selectedIndex]?.text || 'Sin Cliente');
        
        // Description
        const descInput = document.getElementById('modalDesc');
        const hasPendingDesc = Object.prototype.hasOwnProperty.call(pendingProjectDescriptions, String(id));
        const nextDescription = hasPendingDesc ? pendingProjectDescriptions[String(id)] : (p.descripcion || '');
        if (descInput && document.activeElement !== descInput) {
          setCompactDescValue('modalDesc', nextDescription);
          projectDescriptionExpanded = false;
          document.getElementById('projectDescShell')?.classList.remove('toggle-dismissed');
        }
        if (descInput) {
          setDescriptionAutosaveStatus(hasPendingDesc ? 'pending' : 'idle');
          requestAnimationFrame(refreshProjectDescriptionClamp);
        }
        
        // Tasks
        renderModalTasks(p.tareas || []);
        renderProjectNotesPanel(p);

        // Files
        renderModalFiles(p.files || []);
        renderModalCoverPicker(p);
        
        // Metadata
        initModalDuePicker();
        setModalDueDate(p.vencimiento || '');
        setModalPriority(normalizePriority(p.prioridad), false);
        renderModalResponsables(p.responsables || (p.miembro ? [p.miembro] : []), p.responsable_ids || []);
        updateProjectDetailSummary(p);
        renderModalTimeLogs();
        syncTimerPanelsMeta(p);
        document.getElementById('responsibleSearchResults').classList.add('hidden');
        ensureNativePipSource().catch(() => {});
        
        // Timer
        updateModalTimer(p);
        setProjectModalTab(currentProjectModalTab);
        
        // Show
        document.getElementById('projectModal').classList.remove('hidden');
        requestAnimationFrame(refreshProjectDescriptionClamp);
    }

    function handleProjectModalBackdropClick() {}

    async function closeProjectModal() {
      const forceClose = !!(arguments[0] && typeof arguments[0] === 'object' && arguments[0].force);
      if (!forceClose && Date.now() < modalBackdropIgnoreUntil) {
        return;
      }
      const closingProjectId = String(currentProjectId || '');
      if (closingProjectId && typeof pendingProjectDescriptions[closingProjectId] === 'string') {
        clearTimeout(modalDescAutosaveTimer);
        try {
          await saveDescriptionAutosave(closingProjectId, pendingProjectDescriptions[closingProjectId]);
        } catch (_) {}
      }
        document.getElementById('projectModal').classList.add('hidden');
      window.__infocusAiCurrentProject = null;
      if (typeof hideProjectDropOverlay === 'function') hideProjectDropOverlay();
      closeTaskModal();
        if (pipRenderInterval) clearInterval(pipRenderInterval);
      setPipSourceVisible(false);
      closeTimerFullscreen();
        setProjectModalReadOnly(false);
        currentProjectId = null;
        currentProjectModalTab = 'info';
        currentProjectEditingNoteId = null;
        isProjectNoteComposerOpen = false;
        loadData(); // Refresh board to reflect changes
    }
    
    async function updateProjectField(field, value, projectId = currentProjectId) {
        if (projectModalReadOnly) return;
        if (!projectId) return;
        const localProject = projects.find(x => String(x.id) === String(projectId));
        if (localProject) localProject[field] = value;
        const response = await fetch('/api/proyectos/actualizar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({id: projectId, [field]: value})
        });
        if (!response.ok) throw new Error('project_update_failed');
        const payload = await response.json().catch(() => ({}));
        if (payload.item) {
          const idx = projects.findIndex(x => String(x.id) === String(projectId));
          if (idx >= 0) projects[idx] = {...projects[idx], ...payload.item};
        }
        return payload.item;
    }

    async function updateProjectClient(clienteId, clienteName) {
      if (projectModalReadOnly) return;
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (p) {
        p.cliente_id = clienteId || '';
        p.cliente = clienteName || 'Sin Cliente';
        syncTimerPanelsMeta(p);
      }
      await updateProjectField('cliente_id', clienteId || null);
    }

    function setModalPriority(priority, persist = true) {
      const normalized = normalizePriority(priority);
      const hidden = document.getElementById('modalPriority');
      hidden.value = normalized;
      document.querySelectorAll('#modalPrioritySelector .priority-chip').forEach(btn => {
        const isActive = btn.getAttribute('data-priority') === normalized;
        const baseClass = 'priority-chip inline-flex h-10 items-center justify-center gap-1 px-2 rounded-xl text-[11px] font-bold border transition-all';
        btn.className = `${baseClass} grayscale text-slate-500 border-slate-200 bg-slate-100 hover:grayscale-0`;
        if (isActive) {
          if (normalized === 'Con calma') {
            btn.className = `${baseClass} ring-2 ring-emerald-200 text-emerald-700 border-emerald-200 bg-emerald-50`;
          } else if (normalized === 'Atención') {
            btn.className = `${baseClass} ring-2 ring-amber-200 text-amber-700 border-amber-200 bg-amber-50`;
          } else {
            btn.className = `${baseClass} ring-2 ring-rose-200 text-rose-700 border-rose-200 bg-rose-50`;
          }
        }
      });
      if (persist) updateProjectField('prioridad', normalized);
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function normalizeProfilePhotoPath(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';
      if (/^https?:\/\//i.test(raw) || raw.startsWith('data:')) return raw;
      return raw.startsWith('/') ? raw : `/${raw}`;
    }

    function getInitials(name, fallback = 'SR') {
      const clean = String(name || '').trim();
      if (!clean) return fallback;
      const parts = clean.split(/\s+/).filter(Boolean).slice(0, 2);
      const letters = parts.map((part) => part.charAt(0)).join('');
      if (letters) return letters.toUpperCase();
      return clean.substring(0, 2).toUpperCase() || fallback;
    }

    function rememberResponsibleProfile(entry) {
      if (!entry || typeof entry !== 'object') return;
      const id = String(entry.id || '').trim();
      const name = String(entry.name || '').trim();
      const avatar = normalizeProfilePhotoPath(entry.profile_photo || entry.profile_photo_path || entry.avatar || entry.avatar_url || entry.photo || entry.photo_url || entry.foto || '');
      const payload = { id, name, avatar };

      if (id) {
        const previous = responsibleCatalogById[id] || {};
        responsibleCatalogById[id] = {
          id,
          name: payload.name || previous.name || '',
          avatar: payload.avatar || previous.avatar || '',
        };
      }

      if (name) {
        const key = name.toLowerCase();
        const previous = responsibleCatalogByName[key] || {};
        responsibleCatalogByName[key] = {
          id: payload.id || previous.id || '',
          name,
          avatar: payload.avatar || previous.avatar || '',
        };
      }
    }

    function getProjectResponsibleSources(project) {
      const names = Array.isArray(project?.responsables) && project.responsables.length
        ? project.responsables.filter(Boolean)
        : (project?.miembro ? [project.miembro] : []);
      const ids = Array.isArray(project?.responsable_ids) ? project.responsable_ids.filter(Boolean) : [];
      return { names, ids };
    }

    function getTaskOwnerSources(task, project = null) {
      const names = Array.isArray(task?.owners) ? task.owners.filter(Boolean) : [];
      const ids = Array.isArray(task?.owner_ids) ? task.owner_ids.filter(Boolean) : [];
      if (names.length || ids.length) return { names, ids };
      return getProjectResponsibleSources(project || null);
    }

    function getResponsibleProfiles(names = [], ids = []) {
      const safeNames = Array.isArray(names) ? names : [];
      const safeIds = Array.isArray(ids) ? ids : [];
      const length = Math.max(safeNames.length, safeIds.length);
      const items = [];

      for (let idx = 0; idx < length; idx += 1) {
        const id = String(safeIds[idx] || '').trim();
        const incomingName = String(safeNames[idx] || '').trim();
        const byId = id ? responsibleCatalogById[id] : null;
        const byName = incomingName ? responsibleCatalogByName[incomingName.toLowerCase()] : null;
        const name = incomingName || byId?.name || byName?.name || '';
        if (!name && !id) continue;
        const avatar = normalizeProfilePhotoPath(byId?.avatar || byName?.avatar || '');
        items.push({ id: id || byName?.id || '', name, avatar });
      }

      return items.filter((profile, idx, arr) => {
        const key = profile.id || profile.name.toLowerCase();
        return arr.findIndex((item) => (item.id || item.name.toLowerCase()) === key) === idx;
      });
    }

    function renderResponsibleBadges(names = [], ids = [], options = {}) {
      const profiles = getResponsibleProfiles(names, ids);
      if (!profiles.length) {
        return options.emptyHtml || '<span class="text-slate-500">Sin encargados</span>';
      }

      const limit = Math.max(1, Number(options.limit) || 3);
      const visible = profiles.slice(0, limit);
      const bubbleClass = options.bubbleClass || 'w-7 h-7 rounded-full border border-slate-200 bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center overflow-hidden';
      const wrapperClass = options.wrapperClass || 'flex items-center gap-1.5';
      const extraClass = options.extraClass || 'text-[10px] text-slate-500 font-semibold';
      const extra = profiles.length > limit ? `<span class="${extraClass}">+${profiles.length - limit}</span>` : '';
      const title = escapeHtml(profiles.map((profile) => profile.name).join(', '));

      const bubbles = visible.map((profile) => {
        if (profile.avatar) {
          return `<span class="${bubbleClass}"><img src="${escapeHtml(profile.avatar)}" alt="${escapeHtml(profile.name)}" class="h-full w-full object-cover" loading="lazy"></span>`;
        }
        return `<span class="${bubbleClass}">${escapeHtml(getInitials(profile.name))}</span>`;
      }).join('');

      return `<div class="${wrapperClass}" title="${title}">${bubbles}${extra}</div>`;
    }

    function setModalHeaderAvatar(project) {
      const avatarNode = document.getElementById('modalAvatar');
      if (!avatarNode) return;
      const source = getProjectResponsibleSources(project);
      const profile = getResponsibleProfiles(source.names, source.ids)[0] || null;
      if (profile && profile.avatar) {
        avatarNode.innerHTML = `<img src="${escapeHtml(profile.avatar)}" alt="${escapeHtml(profile.name)}" class="h-full w-full object-cover">`;
        avatarNode.classList.add('overflow-hidden');
        return;
      }
      const fallback = profile?.name || project?.miembro || 'HV';
      avatarNode.textContent = getInitials(fallback, 'HV');
      avatarNode.classList.remove('overflow-hidden');
    }

    function renderModalResponsables(responsables, responsableIds = []) {
      const list = document.getElementById('modalResponsablesList');
      if (!list) return;
      const profiles = getResponsibleProfiles(responsables, responsableIds);
      if (!profiles.length) {
        list.innerHTML = '<span class="text-xs font-semibold text-slate-400">Sin responsables asignados</span>';
        const project = projects.find(x => String(x.id) === String(currentProjectId))
          || archivedProjects.find(x => String(x.id) === String(currentProjectId))
          || null;
        if (project) updateProjectDetailSummary(project);
        return;
      }
      list.innerHTML = profiles.map((profile, idx) => `
        <div class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 py-1 pl-1 pr-2">
          <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center overflow-hidden">${profile.avatar ? `<img src="${escapeHtml(profile.avatar)}" alt="${escapeHtml(profile.name)}" class="h-full w-full object-cover" loading="lazy">` : escapeHtml(getInitials(profile.name))}</div>
          <span class="max-w-[8.5rem] truncate text-xs font-bold text-slate-700" title="${escapeHtml(profile.name)}">${escapeHtml(profile.name)}</span>
          <button type="button" class="text-slate-300 hover:text-rose-500 text-base leading-none" onclick="removeResponsible(${idx})">×</button>
        </div>
      `).join('');

      const project = projects.find(x => String(x.id) === String(currentProjectId))
        || archivedProjects.find(x => String(x.id) === String(currentProjectId))
        || null;
      if (project) updateProjectDetailSummary(project);
    }

    function updateProjectDetailSummary(project) {
      const assignedNode = document.getElementById('modalAssignedSummary');
      const investedNode = document.getElementById('modalInvestedSummary');
      if (!assignedNode || !investedNode) return;

      const source = getProjectResponsibleSources(project || {});
      const profiles = getResponsibleProfiles(source.names, source.ids);
      assignedNode.innerText = profiles.length
        ? profiles.map((profile) => profile.name).join(', ')
        : 'Sin asignados';

      investedNode.innerText = formatInvestedDh(getProjectGrossSeconds(project || {}));
    }

    async function searchResponsables(query = '', immediate = false) {
      const box = document.getElementById('responsibleSearchResults');
      const q = String(query || '').trim();
      if (responsibleSearchDebounce) clearTimeout(responsibleSearchDebounce);

      const run = async () => {
        if (responsibleSearchAbort) responsibleSearchAbort.abort();
        responsibleSearchAbort = new AbortController();
        box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Buscando usuarios...</div>';
        box.classList.remove('hidden');

        try {
          const res = await fetch('/api/proyectos/responsables/search?q=' + encodeURIComponent(q), {
            signal: responsibleSearchAbort.signal,
          });
          const json = await res.json().catch(() => ({data: []}));
          const list = Array.isArray(json.data) ? json.data : [];

          if (!list.length) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No se encontraron usuarios</div>';
            box.classList.remove('hidden');
            return;
          }

          box.innerHTML = list.map(u => {
            rememberResponsibleProfile(u);
            const safeId = String(u.id).replace(/'/g, "\\'");
            const safeName = String(u.name).replace(/'/g, "\\'");
            const avatar = normalizeProfilePhotoPath(u.profile_photo || '');
            const safeAvatar = String(avatar || '').replace(/'/g, "\\'");
            const initials = escapeHtml(getInitials(u.name || 'US', 'US'));
            const name = escapeHtml(u.name || 'Usuario');
            const email = escapeHtml(u.email || '');
            const role = escapeHtml(u.role || 'equipo');
            const avatarHtml = avatar
              ? `<img src="${escapeHtml(avatar)}" alt="${name}" class="h-full w-full object-cover" loading="lazy">`
              : initials;

            return `<button type="button" class="w-full text-left px-3 py-2.5 hover:bg-slate-50 border-b border-slate-100 last:border-b-0" onclick="addResponsibleFromCatalog('${safeId}', '${safeName}', '${safeAvatar}')">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center overflow-hidden">${avatarHtml}</div>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-700 truncate">${name}</div>
                  <div class="text-[11px] text-slate-400 truncate">${email} ${role ? '• ' + role : ''}</div>
                </div>
              </div>
            </button>`;
          }).join('');
          box.classList.remove('hidden');
        } catch (error) {
          if (error.name === 'AbortError') return;
          box.innerHTML = '<div class="px-3 py-2 text-xs text-rose-500">No se pudo buscar usuarios</div>';
          box.classList.remove('hidden');
        }
      };

      if (immediate) {
        run();
        return;
      }

      responsibleSearchDebounce = setTimeout(run, 220);
    }

    function renderModalTimeLogs(logs) {
      const list = document.getElementById('modalTimeLogList');
      const entries = getSavedTimerHistory();
      if (!entries.length) {
        list.innerHTML = '<div class="text-xs text-slate-400">Aun no has guardado tiempos</div>';
        return;
      }
      list.innerHTML = entries.slice().reverse().map((log, i) => {
        let actor = String(log.saved_by || '').trim();
        if (actor === '' || actor.toLowerCase() === 'equipo') {
          actor = resolveCurrentUserName(projects.find(x => x.id === currentProjectId));
        }
        return `<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold text-slate-500">${entries.length - i}.</span>
            <span class="text-sm font-extrabold text-lime-600 tracking-tight">${log.time}</span>
            <span class="text-xs font-semibold text-slate-500">${log.day}</span>
          </div>
          ${log.task_name ? `<div class="mt-1 text-[11px] text-slate-500">Tarea: <span class="font-semibold">${escapeHtml(log.task_name)}</span></div>` : ''}
          <div class="mt-1 text-[11px] text-slate-400">Guardado por: <span class="font-semibold text-slate-500">${escapeHtml(actor)}</span></div>
        </div>`;
      }).join('');
    }

    function getSavedTimerHistory(projectId = currentProjectId) {
      if (!projectId) return [];
      try {
        const raw = localStorage.getItem(TIMER_HISTORY_PREFIX + projectId);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
      } catch (_) {
        return [];
      }
    }

    function setSavedTimerHistory(items, projectId = currentProjectId) {
      if (!projectId) return;
      localStorage.setItem(TIMER_HISTORY_PREFIX + projectId, JSON.stringify(Array.isArray(items) ? items : []));
    }

    function getTimerResetBase(projectId = currentProjectId) {
      if (!projectId) return 0;
      const val = Number(localStorage.getItem(TIMER_RESET_PREFIX + projectId) || 0);
      return Number.isFinite(val) ? Math.max(0, val) : 0;
    }

    function setTimerResetBase(totalSeconds, projectId = currentProjectId) {
      if (!projectId) return;
      localStorage.setItem(TIMER_RESET_PREFIX + projectId, String(Math.max(0, Number(totalSeconds) || 0)));
    }

    function taskTimerResetKey(projectId = currentProjectId, taskId = currentTaskId) {
      if (!projectId || !taskId) return '';
      return `${TASK_TIMER_RESET_PREFIX}${projectId}_${taskId}`;
    }

    function getTaskTimerResetBase(projectId = currentProjectId, taskId = currentTaskId) {
      const key = taskTimerResetKey(projectId, taskId);
      if (!key) return 0;
      const val = Number(localStorage.getItem(key) || 0);
      return Number.isFinite(val) ? Math.max(0, val) : 0;
    }

    function setTaskTimerResetBase(totalSeconds, projectId = currentProjectId, taskId = currentTaskId) {
      const key = taskTimerResetKey(projectId, taskId);
      if (!key) return;
      localStorage.setItem(key, String(Math.max(0, Number(totalSeconds) || 0)));
    }

    function persistGlobalTimerState(project, task, currentSeconds, isRunning) {
      if (!project?.id) return;
      const payload = {
        projectId: String(project.id),
        projectTitle: String(project.titulo || 'Proyecto'),
        clientName: String(project.cliente || 'Sin Cliente'),
        taskId: String(task?.id || task?.task_id || pinnedTimerTaskId || ''),
        taskName: String(task?.texto || task?.task_name || 'Temporizador activo'),
        currentSeconds: Math.max(0, Number(currentSeconds || 0)),
        isRunning: !!isRunning,
        syncedAt: Date.now(),
      };
      localStorage.setItem(GLOBAL_TIMER_STATE_KEY, JSON.stringify(payload));
    }

    function getGlobalTimerState() {
      try {
        const raw = localStorage.getItem(GLOBAL_TIMER_STATE_KEY);
        const parsed = raw ? JSON.parse(raw) : null;
        return parsed && typeof parsed === 'object' ? parsed : null;
      } catch (_) {
        return null;
      }
    }

    function clearGlobalTimerState() {
      try {
        localStorage.removeItem(GLOBAL_TIMER_STATE_KEY);
      } catch (_) {}
    }

    function getHeaderPomodoroState() {
      try {
        const parsed = JSON.parse(localStorage.getItem(POMODORO_STATE_KEY) || 'null');
        if (!parsed || typeof parsed !== 'object') return null;
        if (!parsed.isRunning && !parsed.activeTaskId && !parsed.activeProjectId && !(Number(parsed.loggedWorkLogs || 0) > 0)) return null;
        return parsed;
      } catch (_) {
        return null;
      }
    }

    function getHeaderPomodoroRemainingSeconds(state) {
      if (!state) return 0;
      if (!state.isRunning || !state.endsAt) return Math.max(0, Number(state.remainingSeconds || 0));
      return Math.max(0, Math.ceil((Number(state.endsAt) - Date.now()) / 1000));
    }

    function formatPomodoroHeaderTimer(totalSeconds) {
      const safeSeconds = Math.max(0, Number(totalSeconds || 0));
      const minutes = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
      const seconds = (safeSeconds % 60).toString().padStart(2, '0');
      return `${minutes}:${seconds}`;
    }

    function renderProjectPomodoroHeader(host, state) {
      if (!host || !state) return;
      host.classList.remove('hidden');

      if (!host.querySelector('#headerPomodoroCard')) {
        host.innerHTML = `<div id="headerPomodoroCard" role="button" tabindex="0" class="group relative cursor-pointer rounded-2xl border border-[#c8e17e] bg-[#dff8a7] px-2 py-1.5 shadow-[0_10px_22px_rgba(140,166,71,0.28)] min-w-0 w-full text-left transition-all duration-150 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(140,166,71,0.32)] focus:outline-none focus:ring-2 focus:ring-[#111729]/20">
          <div class="absolute top-1.5 right-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0">
            <button type="button" id="headerPomodoroPipBtn" class="w-6 h-6 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 flex items-center justify-center" title="Modo PiP" aria-label="Modo PiP">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
            </button>
          </div>
          <div class="flex items-center gap-2 min-w-0 text-[#111729]">
            <button id="headerPomodoroToggleBtn" type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#111729] text-[#dff8a7]" title="Pausar Pomodoro"></button>
            <div class="min-w-0 flex-1">
              <div class="text-[10px] font-extrabold uppercase tracking-[0.28em] text-[#111729]/70">Pomodoro TDAH</div>
              <div id="headerPomodoroTask" class="truncate text-left text-xs lg:text-sm font-extrabold text-[#111729]">Pomodoro activo</div>
              <div id="headerPomodoroMeta" class="truncate text-[10px] lg:text-[11px] font-semibold text-[#111729]/65">Trabajo</div>
            </div>
            <div class="shrink-0 text-right min-w-[86px] lg:min-w-[98px]">
              <div id="headerPomodoroValue" class="text-2xl lg:text-[30px] font-mono font-extrabold tracking-tight text-[#111729] leading-none">25:00</div>
              <div class="mt-1 flex items-center justify-end gap-1.5">
                <button id="headerPomodoroSaveBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-[#111729]/70 hover:text-[#111729]">Guardar</button>
                <button id="headerPomodoroDeleteBtn" type="button" class="text-[10px] lg:text-[11px] font-bold text-rose-700/85 hover:text-rose-800">Eliminar</button>
              </div>
            </div>
          </div>
        </div>`;

        host.querySelector('#headerPomodoroCard')?.addEventListener('click', () => {
          window.openTdahPomodoroFullscreen?.();
        });
        host.querySelector('#headerPomodoroCard')?.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            window.openTdahPomodoroFullscreen?.();
          }
        });
        host.querySelector('#headerPomodoroToggleBtn')?.addEventListener('click', (event) => {
          event.stopPropagation();
          window.toggleTdahPomodoroFromHeader?.();
        });
        host.querySelector('#headerPomodoroSaveBtn')?.addEventListener('click', (event) => {
          event.stopPropagation();
          window.saveTdahPomodoroSession?.();
        });
        host.querySelector('#headerPomodoroDeleteBtn')?.addEventListener('click', (event) => {
          event.stopPropagation();
          window.deleteTdahPomodoroSession?.();
        });
        host.querySelector('#headerPomodoroPipBtn')?.addEventListener('click', (event) => {
          event.stopPropagation();
          window.openTdahPomodoroPip?.();
        });
      }

      const taskNode = host.querySelector('#headerPomodoroTask');
      const metaNode = host.querySelector('#headerPomodoroMeta');
      const valueNode = host.querySelector('#headerPomodoroValue');
      const toggleNode = host.querySelector('#headerPomodoroToggleBtn');
      const taskName = String(state.activeTaskName || 'Pomodoro activo');
      const meta = state.phase === 'break'
        ? `Descanso · ${state.breakMinutes || 15}m`
        : `${state.activeProjectTitle || 'En foco'} · ${state.workMinutes || 25}m`;

      if (taskNode) taskNode.innerText = taskName;
      if (metaNode) metaNode.innerText = meta;
      if (valueNode) valueNode.innerText = formatPomodoroHeaderTimer(getHeaderPomodoroRemainingSeconds(state));
      if (toggleNode) {
        toggleNode.title = state.isRunning ? 'Pausar Pomodoro' : 'Continuar Pomodoro';
        toggleNode.innerHTML = state.isRunning
          ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
          : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';
      }
    }

    function formatTimer(totalSeconds) {
      const sec = Math.max(0, Number(totalSeconds) || 0);
      const h = Math.floor(sec / 3600).toString().padStart(2,'0');
      const m = Math.floor((sec % 3600) / 60).toString().padStart(2,'0');
      const s = (sec % 60).toString().padStart(2,'0');
      return `${h}:${m}:${s}`;
    }

    function compactTimerTaskTitle(value) {
      const raw = String(value || 'Temporizador activo').trim();
      if (raw.length <= 18) return raw;
      return `${raw.slice(0, 12).trim()} .....`;
    }

    function formatInvestedDh(totalSeconds) {
      const sec = Math.max(0, Number(totalSeconds) || 0);
      const days = Math.floor(sec / 86400);
      const hours = Math.floor((sec % 86400) / 3600);
      const minutes = Math.floor((sec % 3600) / 60);
      return `${days}d ${hours}h ${minutes}m`;
    }

    function formatDeltaInvested(totalSeconds) {
      const sec = Number(totalSeconds) || 0;
      const sign = sec >= 0 ? '+' : '-';
      return `${sign}${formatInvestedDh(Math.abs(sec))}`;
    }

    function parseDurationToSeconds(value) {
      const input = String(value || '').trim().toLowerCase();
      if (!input) return 0;

      const dMatch = input.match(/(\d+)\s*d/);
      const hMatch = input.match(/(\d+)\s*h/);
      const mMatch = input.match(/(\d+)\s*m/);

      if (dMatch || hMatch || mMatch) {
        const d = Math.max(0, Number(dMatch?.[1] || 0));
        const h = Math.max(0, Math.min(23, Number(hMatch?.[1] || 0)));
        const m = Math.max(0, Math.min(59, Number(mMatch?.[1] || 0)));
        return Math.floor((d * 86400) + (h * 3600) + (m * 60));
      }

      const plain = input.match(/\d+/g) || [];
      const d = Math.max(0, Number(plain[0] || 0));
      const h = Math.max(0, Math.min(23, Number(plain[1] || 0)));
      const m = Math.max(0, Math.min(59, Number(plain[2] || 0)));
      return Math.floor((d * 86400) + (h * 3600) + (m * 60));
    }

    function fillPlannedGoalEditor(seconds) {
      const total = Math.max(0, Number(seconds) || 0);
      const days = Math.floor(total / 86400);
      const hours = Math.floor((total % 86400) / 3600);
      const minutes = Math.floor((total % 3600) / 60);
      const d = document.getElementById('plannedGoalDays');
      const h = document.getElementById('plannedGoalHours');
      const m = document.getElementById('plannedGoalMinutes');
      if (d) d.value = String(days);
      if (h) h.value = String(hours);
      if (m) m.value = String(minutes);
    }

    function enablePlannedGoalEdit() {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;
      fillPlannedGoalEditor(p.planned_seconds || 0);
      document.getElementById('modalTimerPlannedDisplay')?.classList.add('hidden');
      document.getElementById('modalTimerPlannedEditor')?.classList.remove('hidden');
      document.getElementById('plannedGoalEditBtn')?.classList.add('hidden');
      document.getElementById('plannedGoalSaveBtn')?.classList.remove('hidden');
      document.getElementById('plannedGoalDays')?.focus();
    }

    async function savePlannedGoalEdit() {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;

      const days = Math.max(0, Math.min(999, Number(document.getElementById('plannedGoalDays')?.value || 0)));
      const hours = Math.max(0, Math.min(99, Number(document.getElementById('plannedGoalHours')?.value || 0)));
      const minutes = Math.max(0, Math.min(99, Number(document.getElementById('plannedGoalMinutes')?.value || 0)));
      const seconds = Math.floor((days * 86400) + (hours * 3600) + (minutes * 60));

      p.planned_seconds = seconds;
      await updateProjectField('planned_seconds', seconds);
      updateInvestedDisplays(p);
      document.getElementById('modalTimerPlannedDisplay')?.classList.remove('hidden');
      document.getElementById('modalTimerPlannedEditor')?.classList.add('hidden');
      document.getElementById('plannedGoalEditBtn')?.classList.remove('hidden');
      document.getElementById('plannedGoalSaveBtn')?.classList.add('hidden');
      if (window.showNotification) window.showNotification('Meta prevista actualizada', 'success');
    }

    function getProjectGrossSeconds(p) {
      const logs = p?.time_logs || [];
      return logs.reduce((acc, log) => {
        const end = log.end || Math.floor(Date.now()/1000);
        return acc + (end - log.start);
      }, 0);
    }

    function getCurrentProjectTotalSeconds(p) {
      const gross = getProjectGrossSeconds(p);
      const base = getTimerResetBase(p?.id || currentProjectId);
      return Math.max(0, gross - base);
    }

    function setTimerSaveButtonState(buttonId, enabled) {
      const btn = document.getElementById(buttonId);
      if (!btn) return;
      btn.disabled = !enabled;
      btn.className = enabled
        ? 'px-2 py-1.5 rounded-lg border border-lime-400 bg-lime-100/20 text-[11px] font-bold text-lime-300 hover:bg-lime-100/30 transition-colors'
        : 'px-2 py-1.5 rounded-lg border border-slate-600 bg-slate-700/40 text-[11px] font-bold text-slate-500 cursor-not-allowed transition-colors';
    }

    function setFullscreenTimerSaveButtonState(enabled) {
      const btn = document.getElementById('timerFsSaveBtn');
      if (!btn) return;
      btn.disabled = !enabled;
      btn.className = enabled
        ? 'px-4 py-2 rounded-xl border border-white/20 bg-white/10 text-white text-sm font-bold hover:bg-white/20 flex items-center gap-2'
        : 'px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-slate-500 text-sm font-bold cursor-not-allowed flex items-center gap-2';
    }

    function updateInvestedDisplays(p) {
      const gross = getProjectGrossSeconds(p);
      const value = formatInvestedDh(gross);
      const plannedSeconds = Math.max(0, Number(p?.planned_seconds || 0));
      const plannedValue = formatInvestedDh(plannedSeconds);
      const diffSeconds = gross - plannedSeconds;
      const diffValue = formatDeltaInvested(diffSeconds);
      const modalInvested = document.getElementById('modalTimerInvestedDisplay');
      const modalPlanned = document.getElementById('modalTimerPlannedDisplay');
      const modalCompare = document.getElementById('modalTimerCompareDisplay');
      if (modalInvested) modalInvested.innerText = value;
      if (modalPlanned) modalPlanned.innerText = plannedValue;
      if (modalCompare) {
        modalCompare.innerText = `Comparación: ${diffValue}`;
        modalCompare.classList.remove('text-slate-500', 'text-rose-600', 'text-emerald-600');
        if (diffSeconds > 0) {
          modalCompare.classList.add('text-rose-600');
        } else if (diffSeconds < 0) {
          modalCompare.classList.add('text-emerald-600');
        } else {
          modalCompare.classList.add('text-slate-500');
        }
      }

      updateProjectDetailSummary(p);
    }

    function resolveCurrentUserName(project = null) {
      const fromAuth = String(currentUserDisplayName || '').trim();
      if (fromAuth !== '') return fromAuth;

      const logs = project?.time_logs || [];
      const current = logs.length ? logs[logs.length - 1] : null;
      const fromLog = String(current?.user || '').trim();
      if (fromLog !== '') return fromLog;

      return 'Usuario';
    }

    async function saveCurrentTimerLog() {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;

      const isTaskModalOpen = !document.getElementById('taskDetailModal')?.classList.contains('hidden');
      const taskBeforeSave = isTaskModalOpen ? getCurrentTask() : null;
      const pendingBeforeStop = taskBeforeSave
        ? getTaskDisplayedSeconds(p, taskBeforeSave)
        : getCurrentProjectTotalSeconds(p);
      if (pendingBeforeStop <= 0) {
        setTimerSaveButtonState('modalTimerSaveBtn', false);
        setTimerSaveButtonState('taskTimerSaveBtn', false);
        if (window.showNotification) window.showNotification('No hay tiempo para guardar.', 'info');
        return;
      }

      const logs = p.time_logs || [];
      const isRunning = logs.length > 0 && !logs[logs.length - 1].end;
      if (isRunning) {
        await toggleModalTimer();
      }

      const refreshed = projects.find(x => x.id === currentProjectId) || p;
      const refreshedTask = taskBeforeSave ? getTaskFromProject(refreshed, taskBeforeSave.id) : null;
      const latestLogs = refreshed.time_logs || [];
      const lastLog = latestLogs.length ? latestLogs[latestLogs.length - 1] : null;
      const pendingSeconds = refreshedTask
        ? getTaskDisplayedSeconds(refreshed, refreshedTask)
        : getCurrentProjectTotalSeconds(refreshed);
      const secondsToSave = Math.max(0, pendingSeconds || pendingBeforeStop);
      if (secondsToSave <= 0) {
        setTimerSaveButtonState('modalTimerSaveBtn', false);
        setTimerSaveButtonState('taskTimerSaveBtn', false);
        if (window.showNotification) window.showNotification('No hay tiempo para guardar.', 'info');
        return;
      }
      const display = formatTimer(secondsToSave);
      const day = new Date().toLocaleDateString('es-ES');
      const entries = getSavedTimerHistory();
      entries.push({
        time: display,
        day,
        saved_by: resolveCurrentUserName(refreshed),
        task_name: String(lastLog?.task_name || ''),
      });
      setSavedTimerHistory(entries);
      setTimerResetBase(getProjectGrossSeconds(refreshed), refreshed.id);
      if (refreshedTask?.id) {
        setTaskTimerResetBase(Number(refreshedTask.total_seconds || 0), refreshed.id, refreshedTask.id);
      }
      renderModalTimeLogs();
      updateModalTimer(refreshed);
      if (refreshedTask?.id && currentTaskId && String(refreshedTask.id) === String(currentTaskId)) {
        updateTaskTimerPanels(refreshedTask);
        renderTaskTimeHistory(refreshedTask.id);
      }
      syncPinnedTimerHud();
      if (window.showNotification) window.showNotification('Tiempo guardado en historial', 'success');
    }

    function resetCurrentTimer() {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;
      const logs = p.time_logs || [];
      const isRunning = logs.length > 0 && !logs[logs.length - 1].end;
      const isTaskModalOpen = !document.getElementById('taskDetailModal')?.classList.contains('hidden');
      const taskBeforeReset = isTaskModalOpen ? getCurrentTask() : null;

      const applyReset = () => {
        const freshProject = projects.find(x => x.id === currentProjectId) || p;
        const freshTask = taskBeforeReset ? getTaskFromProject(freshProject, taskBeforeReset.id) : null;
        const grossNow = getProjectGrossSeconds(freshProject);
        setTimerResetBase(grossNow);
        if (freshTask?.id) {
          setTaskTimerResetBase(Number(freshTask.total_seconds || 0), freshProject.id, freshTask.id);
        }
        const display = document.getElementById('modalTimerDisplay');
        if (display) display.innerText = '00:00:00';
        syncTimerPanelsDisplay('00:00:00');
        setTimerSaveButtonState('modalTimerSaveBtn', false);
        setTimerSaveButtonState('taskTimerSaveBtn', false);
        if (freshTask?.id) updateTaskTimerPanels(freshTask);
      };

      if (isRunning) {
        toggleModalTimer().finally(applyReset);
      } else {
        applyReset();
      }
    }

    async function addResponsibleFromCatalog(userId, userName, profilePhoto = '') {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;

      rememberResponsibleProfile({ id: userId, name: userName, profile_photo: profilePhoto });

      const names = Array.isArray(p.responsables) ? [...p.responsables] : (p.miembro ? [p.miembro] : []);
      const ids = Array.isArray(p.responsable_ids) ? [...p.responsable_ids] : [];

      if (!ids.includes(userId) && !names.includes(userName)) {
        ids.push(userId);
        names.push(userName);
      }

      p.responsables = names;
      p.responsable_ids = ids;
      p.miembro = names[0] || null;

      await updateProjectField('responsables', names);
      await updateProjectField('responsable_ids', ids);
      await updateProjectField('miembro', p.miembro);

      renderModalResponsables(names, ids);
      setModalHeaderAvatar(p);
      syncTimerPanelsMeta(p);
      document.getElementById('newResponsibleInput').value = '';
      document.getElementById('responsibleSearchResults').classList.add('hidden');
    }

    async function removeResponsible(index) {
      if (!currentProjectId) return;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;
      const list = Array.isArray(p.responsables) ? [...p.responsables] : (p.miembro ? [p.miembro] : []);
      const ids = Array.isArray(p.responsable_ids) ? [...p.responsable_ids] : [];
      list.splice(index, 1);
      if (ids.length > index) ids.splice(index, 1);
      p.responsables = list;
      p.responsable_ids = ids;
      p.miembro = list[0] || null;
      await updateProjectField('responsables', list);
      await updateProjectField('responsable_ids', ids);
      await updateProjectField('miembro', p.miembro);
      renderModalResponsables(list, ids);
      setModalHeaderAvatar(p);
      syncTimerPanelsMeta(p);
    }

    function syncTimerPanelsMeta(p, task = null) {
      const taskLabel = task?.texto || task?.task_name || getActiveTimerTaskLabel(p) || 'Sin tarea vinculada';
      document.getElementById('timerFsProject').innerText = taskLabel;
      document.getElementById('timerFsClient').innerText = `${p?.titulo || 'Proyecto'} · ${p?.cliente || 'Sin Cliente'}`;
      document.getElementById('timerPipProject').innerText = p?.titulo || 'Proyecto';
      document.getElementById('timerPipClient').innerText = p?.cliente || 'Sin Cliente';
    }

    function syncTimerPanelsDisplay(value) {
      document.getElementById('timerFsDisplay').innerText = value;
      document.getElementById('timerPipDisplay').innerText = value;
      drawTimerPipCanvas(value);
      const project = getPinnedTimerProject() || projects.find(x => String(x.id) === String(currentProjectId));
      setFullscreenTimerSaveButtonState(project ? getCurrentProjectTotalSeconds(project) > 0 : false);
    }

    function getActiveTimerTaskLabel(p) {
      const logs = p?.time_logs || [];
      const running = logs.length ? logs[logs.length - 1] : null;
      if (running && !running.end) {
        return running.task_name || 'Sin tarea vinculada';
      }
      return 'Sin tarea vinculada';
    }

    function updateModalTimerTaskLabel(p) {
      const el = document.getElementById('modalTimerTaskLabel');
      if (!el) return;
      el.innerText = getActiveTimerTaskLabel(p);
    }

    function getRunningLog(project) {
      const logs = project?.time_logs || [];
      const current = logs.length ? logs[logs.length - 1] : null;
      return current && !current.end ? current : null;
    }

    function findRunningProject() {
      return projects.find((project) => !!getRunningLog(project)) || null;
    }

    function getTaskFromProject(project, taskId) {
      if (!project || !taskId) return null;
      return (project.tareas || []).find((task) => String(task.id || '') === String(taskId || '')) || null;
    }

    function patchProjectInState(item) {
      if (!item?.id) return;
      projects = projects.map((project) => String(project.id) === String(item.id) ? item : project);
    }

    function setPinnedTimerContext(projectId = null, taskId = null) {
      pinnedTimerProjectId = projectId ? String(projectId) : null;
      pinnedTimerTaskId = taskId ? String(taskId) : null;
    }

    function getPinnedTimerProject() {
      const runningProject = findRunningProject();
      if (runningProject) return runningProject;
      if (!pinnedTimerProjectId) return null;
      return projects.find((project) => String(project.id) === String(pinnedTimerProjectId)) || null;
    }

    function getPinnedTimerTask(project = null) {
      const targetProject = project || getPinnedTimerProject();
      if (!targetProject) return null;
      const running = getRunningLog(targetProject);
      const taskId = String(running?.task_id || pinnedTimerTaskId || '');
      return getTaskFromProject(targetProject, taskId);
    }

    function updateHeaderTaskTimer(project, task) {
      const host = document.getElementById('headerTaskTimerHost');
      if (!host) return;

      const pomodoroHeaderState = getHeaderPomodoroState();
      if (pomodoroHeaderState) {
        renderProjectPomodoroHeader(host, pomodoroHeaderState);
        return;
      }

      if (!project) {
        host.classList.add('hidden');
        host.innerHTML = '';
        clearGlobalTimerState();
        window.updateHeaderTimerButtonVisibility?.(true);
        return;
      }

      const running = getRunningLog(project);
      const taskName = task?.texto || running?.task_name || 'Temporizador activo';
      const compactTaskName = compactTimerTaskTitle(taskName);
      const projectName = project.titulo || 'Proyecto';
      const isRunning = !!running;
      const currentSecondsRaw = getCurrentProjectTotalSeconds(project);
      const projectId = String(project.id || '');
      let stableSeconds = currentSecondsRaw;
      if (isRunning) {
        if (headerTimerLastProjectId === projectId) {
          stableSeconds = Math.max(currentSecondsRaw, headerTimerLastSeconds);
        }
        headerTimerLastProjectId = projectId;
        headerTimerLastSeconds = stableSeconds;
      } else {
        headerTimerLastProjectId = projectId;
        headerTimerLastSeconds = currentSecondsRaw;
      }
      const timeValue = formatTimer(stableSeconds);

      // Keep fullscreen/PiP panels synced even when timer is controlled from header.
      syncTimerPanelsMeta(project, task || running);
      syncTimerPanelsDisplay(timeValue);

      host.classList.remove('hidden');
      window.updateHeaderTimerButtonVisibility?.(false);

      // Render once; update text/icons only to avoid hover flicker every second.
      if (!host.querySelector('#headerTimerCard')) {
        host.innerHTML = `<div id="headerTimerCard" role="button" tabindex="0" onclick="openPinnedTimerDetailPanel(event)" onkeydown="if(event.key==='Enter' || event.key===' '){event.preventDefault(); openPinnedTimerDetailPanel(event);}" class="group cursor-pointer relative rounded-2xl border border-[#2b3658] bg-[#101729] px-2 py-1.5 shadow-[0_10px_22px_rgba(16,23,41,0.32)] min-w-0 w-full text-left transition-all duration-150 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(16,23,41,0.36)] focus:outline-none focus:ring-2 focus:ring-[#dff47f]/55">
          <div class="absolute top-1.5 right-1.5 opacity-0 pointer-events-none -translate-y-1 transition-all duration-150 group-hover:opacity-100 group-hover:pointer-events-auto group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-y-0">
            <button type="button" data-advanced-control onclick="event.stopPropagation(); openPinnedTimerPip();" class="w-6 h-6 rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 flex items-center justify-center" title="Modo PiP" aria-label="Modo PiP">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="14" rx="2" ry="2" stroke-width="2"></rect><rect x="12" y="11" width="8" height="6" rx="1.5" ry="1.5" stroke-width="2"></rect></svg>
            </button>
          </div>
          <div class="flex items-center gap-2 min-w-0">
            <button id="headerTimerToggleBtn" type="button" onclick="event.stopPropagation(); togglePinnedTimerRun()" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#f0fe97] text-[#101729]" title="${isRunning ? 'Pausar temporizador' : 'Continuar temporizador'}">${isRunning ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>' : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>'}</button>
            <div class="min-w-0 flex-1">
              <div class="text-[10px] font-extrabold uppercase tracking-[0.28em] text-[#f0fe97]/70">En foco</div>
              <div id="headerTimerTask" class="truncate text-left text-xs lg:text-sm font-extrabold text-[#f0fe97]">${compactTaskName}</div>
              <div id="headerTimerProject" class="truncate text-[10px] lg:text-[11px] font-semibold text-[#f0fe97]/60">${projectName}</div>
            </div>
            <div class="shrink-0 text-right min-w-[86px] lg:min-w-[98px]">
              <div id="headerPinnedTimerValue" class="text-2xl lg:text-[30px] font-mono font-extrabold tracking-tight text-[#f0fe97] leading-none">${timeValue}</div>
              <div class="mt-1 flex items-center justify-end gap-1.5">
                <button type="button" onclick="event.stopPropagation(); savePinnedTimerLog()" class="text-[10px] lg:text-[11px] font-bold text-[#f0fe97]/75 hover:text-[#f0fe97]">Guardar</button>
                <button type="button" onclick="event.stopPropagation(); deletePinnedTimerEntry()" class="text-[10px] lg:text-[11px] font-bold text-rose-300/90 hover:text-rose-200">Eliminar</button>
              </div>
            </div>
          </div>
        </div>`;
      }

      const headerCard = host.querySelector('#headerTimerCard');
      if (headerCard) {
        headerCard.dataset.projectId = String(project.id || '');
      }

      const headerTask = host.querySelector('#headerTimerTask');
      if (headerTask) headerTask.innerText = compactTaskName;

      const headerProject = host.querySelector('#headerTimerProject');
      if (headerProject) headerProject.innerText = `${projectName}`;

      const headerValue = host.querySelector('#headerPinnedTimerValue');
      if (headerValue) headerValue.innerText = timeValue;

      const headerToggleBtn = host.querySelector('#headerTimerToggleBtn');
      if (headerToggleBtn) {
        headerToggleBtn.title = isRunning ? 'Pausar temporizador' : 'Continuar temporizador';
        headerToggleBtn.innerHTML = isRunning
          ? '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
          : '<svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>';
      }

      persistGlobalTimerState(project, task || running, stableSeconds, isRunning);

      syncTimerFullscreenActionButtons(isRunning);
    }

    function syncPinnedTimerHud() {
      if (headerTimerInterval) {
        clearInterval(headerTimerInterval);
        headerTimerInterval = null;
      }

      const runningProject = findRunningProject();
      let project = runningProject;

      if (runningProject) {
        const running = getRunningLog(runningProject);
        setPinnedTimerContext(runningProject.id, running?.task_id || null);
      } else if (!pinnedTimerProjectId) {
        const stored = getGlobalTimerState();
        if (stored?.projectId) {
          setPinnedTimerContext(stored.projectId, stored.taskId || null);
          project = projects.find((entry) => String(entry.id) === String(stored.projectId)) || null;
        }
      } else if (pinnedTimerProjectId) {
        project = projects.find((entry) => String(entry.id) === String(pinnedTimerProjectId)) || null;
      }

      if (!project) {
        setPinnedTimerContext(null, null);
        updateHeaderTaskTimer(null, null);
        refreshProjectsSimpleModeUI();
        const pomodoroHeaderState = getHeaderPomodoroState();
        if (pomodoroHeaderState?.isRunning) {
          headerTimerInterval = setInterval(() => {
            if (findRunningProject()) {
              syncPinnedTimerHud();
              return;
            }
            updateHeaderTaskTimer(null, null);
          }, 1000);
        }
        return;
      }

      const task = getPinnedTimerTask(project);
      updateHeaderTaskTimer(project, task);
      refreshProjectsSimpleModeUI();

      if (!runningProject) return;

      headerTimerInterval = setInterval(() => {
        const liveProject = getPinnedTimerProject();
        const liveTask = getPinnedTimerTask(liveProject);
        if (!liveProject || !getRunningLog(liveProject)) {
          syncPinnedTimerHud();
          return;
        }
        updateHeaderTaskTimer(liveProject, liveTask);
      }, 1000);
    }

    function openActiveTaskFromFocus() {
      const project = getPinnedTimerProject();
      const task = getPinnedTimerTask(project);
      if (!project) return;
      if (task?.id) {
        openProjectTask(project.id, task.id);
        return;
      }
      openProject(project.id);
    }

    function openPinnedTimerDetailPanel() {
      openTimerExpandedPanel();
    }

    function openTimerExpandedPanel() {
      const project = getPinnedTimerProject();
      const task = getPinnedTimerTask(project);
      if (!project) return;
      timerFsShowAllSubtasks = false;
      timerFsShowAllNotes = false;
      currentProjectId = project.id;
      if (task?.id) currentTaskId = task.id;
      syncTimerPanelsMeta(project, task || getRunningLog(project));
      syncTimerPanelsDisplay(formatTimer(getCurrentProjectTotalSeconds(project)));
      refreshTimerFullscreenColumns(project, task);
      syncTimerFullscreenActionButtons(!!getRunningLog(project));
      const panel = document.getElementById('timerFullscreenPanel');
      if (panel) panel.classList.remove('hidden');
    }

    function openPinnedTimerFullscreen() {
      openTimerExpandedPanel();
      openTimerFullscreen();
    }

    async function openPinnedTimerPip() {
      const project = getPinnedTimerProject();
      if (!project) return;
      currentProjectId = project.id;
      syncTimerPanelsMeta(project, getPinnedTimerTask(project));
      syncTimerPanelsDisplay(formatTimer(getCurrentProjectTotalSeconds(project)));
      await toggleTimerMiniPip();
    }

    function refreshTimerFullscreenColumns(project = null, task = null) {
      const resolvedProject = project || getPinnedTimerProject();
      const resolvedTask = task || getPinnedTimerTask(resolvedProject);
      renderTimerFullscreenSubtasks(resolvedProject, resolvedTask);
      renderTimerFullscreenNotes(resolvedTask);
    }

    function syncTimerFullscreenActionButtons(isRunning) {
      const btn = document.getElementById('timerFsPauseBtn');
      if (btn) {
        btn.title = isRunning ? 'Pausar temporizador' : 'Reanudar temporizador';
        btn.setAttribute('aria-label', isRunning ? 'Pausar temporizador' : 'Reanudar temporizador');
        btn.innerHTML = isRunning
          ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>'
          : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>';
      }
      const project = getPinnedTimerProject() || projects.find(x => String(x.id) === String(currentProjectId));
      setFullscreenTimerSaveButtonState(project ? getCurrentProjectTotalSeconds(project) > 0 : false);
    }

    function toggleTimerFsShowAllSubtasks() {
      timerFsShowAllSubtasks = !timerFsShowAllSubtasks;
      refreshTimerFullscreenColumns();
    }

    function toggleTimerFsShowAllNotes() {
      timerFsShowAllNotes = !timerFsShowAllNotes;
      refreshTimerFullscreenColumns();
    }

    function renderTimerFullscreenSubtasks(project, task) {
      const list = document.getElementById('timerFsSubtasksList');
      if (!list) return;
      if (!project || !task?.id) {
        list.innerHTML = '<div class="text-sm text-slate-300">Sin tarea vinculada para este temporizador.</div>';
        return;
      }

      const subtasks = Array.isArray(task.subtasks) ? task.subtasks : [];
      if (!subtasks.length) {
        list.innerHTML = '<div class="text-sm text-slate-300">No hay sub tareas todavía.</div>';
        return;
      }

      const hasMore = subtasks.length > 6;
      const visibleSubtasks = timerFsShowAllSubtasks ? subtasks : subtasks.slice(0, 6);

      list.innerHTML = visibleSubtasks.map((subtask) => `
        <button type="button" onclick="toggleTimerFullscreenSubtask('${String(subtask.id || '').replace(/'/g, "\\'")}")" class="w-full flex items-center gap-2 bg-white/10 px-3 py-2 text-left hover:bg-white/20 transition-colors">
          <span class="w-5 h-5 rounded border ${subtask.done ? 'bg-lime-400 border-lime-400 text-slate-900' : 'border-slate-400 text-transparent'} flex items-center justify-center shrink-0">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
          </span>
          <span class="text-base ${subtask.done ? 'line-through text-slate-400' : 'text-white'}">${escapeHtml(String(subtask.texto || ''))}</span>
        </button>
      `).join('');

      if (hasMore) {
        list.innerHTML += `<button type="button" onclick="toggleTimerFsShowAllSubtasks()" class="mt-1 text-xs font-bold text-lime-300 hover:text-lime-200">${timerFsShowAllSubtasks ? 'Ver menos' : 'Ver todas'}</button>`;
      }
    }

    function renderTimerFullscreenNotes(task) {
      const list = document.getElementById('timerFsNotesList');
      if (!list) return;
      if (!task?.id) {
        list.innerHTML = '<div class="text-sm text-slate-300">Sin tarea vinculada para este temporizador.</div>';
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
      const visibleNotes = timerFsShowAllNotes ? notes : notes.slice(0, 4);

      list.innerHTML = visibleNotes.map((note) => {
        const author = escapeHtml(String(note.author_name || note.user || 'Usuario'));
        const created = escapeHtml(formatTaskNoteDate(note.created_at));
        const text = escapeHtml(String(note.texto || ''));
        return `<div class="rounded-lg border border-white/15 bg-white/10 px-2.5 py-1.5">
          <div class="flex items-center justify-between gap-2">
            <div class="text-[11px] font-bold text-slate-100">${author}</div>
            <div class="text-[10px] text-slate-300">${created}</div>
          </div>
          <div class="mt-1 text-xs leading-5 text-slate-100 whitespace-pre-wrap">${text}</div>
        </div>`;
      }).join('');

      if (hasMore) {
        list.innerHTML += `<button type="button" onclick="toggleTimerFsShowAllNotes()" class="mt-1 text-xs font-bold text-lime-300 hover:text-lime-200">${timerFsShowAllNotes ? 'Ver menos' : 'Ver todas'}</button>`;
      }
    }

    async function addTimerFullscreenSubtask() {
      const project = getPinnedTimerProject();
      const task = getPinnedTimerTask(project);
      const input = document.getElementById('timerFsNewSubtaskInput');
      const texto = String(input?.value || '').trim();
      if (!project?.id || !task?.id || !texto) return;

      const res = await fetch('/api/proyectos/tareas/subtareas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: project.id, tarea_id: task.id, texto})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      if (input) input.value = '';
      patchProjectInState(data.item);
      const refreshedProject = projects.find((entry) => String(entry.id) === String(project.id));
      const refreshedTask = getTaskFromProject(refreshedProject, task.id);
      refreshTimerFullscreenColumns(refreshedProject, refreshedTask);
      if (currentTaskId && String(currentTaskId) === String(task.id) && refreshedTask) {
        renderTaskDetail(refreshedTask, { preserveState: true });
      }
      syncPinnedTimerHud();
    }

    async function addTimerFullscreenNote() {
      const project = getPinnedTimerProject();
      const task = getPinnedTimerTask(project);
      const input = document.getElementById('timerFsNewNoteInput');
      const texto = String(input?.value || '').trim();
      if (!project?.id || !task?.id || !texto) return;

      const res = await fetch('/api/proyectos/tareas/notas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: project.id, tarea_id: task.id, texto})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      if (input) input.value = '';
      patchProjectInState(data.item);
      const refreshedProject = projects.find((entry) => String(entry.id) === String(project.id));
      const refreshedTask = getTaskFromProject(refreshedProject, task.id);
      refreshTimerFullscreenColumns(refreshedProject, refreshedTask);
      if (currentTaskId && String(currentTaskId) === String(task.id) && refreshedTask) {
        renderTaskDetail(refreshedTask, { preserveState: true });
      }
      syncPinnedTimerHud();
    }

    async function toggleTimerFullscreenSubtask(subtaskId) {
      const project = getPinnedTimerProject();
      const task = getPinnedTimerTask(project);
      if (!project?.id || !task?.id || !subtaskId) return;

      const res = await fetch('/api/proyectos/tareas/subtareas/toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: project.id, tarea_id: task.id, subtarea_id: subtaskId})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      patchProjectInState(data.item);
      const refreshedProject = projects.find((entry) => String(entry.id) === String(project.id));
      const refreshedTask = getTaskFromProject(refreshedProject, task.id);
      refreshTimerFullscreenColumns(refreshedProject, refreshedTask);
      if (currentTaskId && String(currentTaskId) === String(task.id) && refreshedTask) {
        renderTaskDetail(refreshedTask, { preserveState: true });
      }
      syncPinnedTimerHud();
    }

    async function pausePinnedTimer() {
      const project = findRunningProject() || getPinnedTimerProject();
      const running = project ? getRunningLog(project) : null;
      if (!project || !running) return;
      try {
        const keepTaskId = String(running?.task_id || pinnedTimerTaskId || '');
        const item = await sendTimerAction(project.id, 'stop', null);
        patchProjectInState(item);
        setPinnedTimerContext(project.id, keepTaskId || null);
        await loadData();
      } catch (error) {
        console.error(error);
      }
    }

    async function resumePinnedTimer() {
      const project = getPinnedTimerProject();
      if (!project) return;
      try {
        const taskId = pinnedTimerTaskId || null;
        const item = await sendTimerAction(project.id, 'start', taskId);
        patchProjectInState(item);
        setPinnedTimerContext(project.id, taskId);
        await loadData();
      } catch (error) {
        console.error(error);
      }
    }

    async function togglePinnedTimerRun() {
      const project = getPinnedTimerProject();
      if (!project) return;
      if (getRunningLog(project)) {
        await pausePinnedTimer();
      } else {
        await resumePinnedTimer();
      }
      // Keep mediaSession playback state in sync for PiP controls
      if ('mediaSession' in navigator) {
        try {
          const _lp = getPinnedTimerProject();
          navigator.mediaSession.playbackState = _lp && getRunningLog(_lp) ? 'playing' : 'paused';
        } catch (_) {}
      }
    }

    async function savePinnedTimerLog() {
      const project = getPinnedTimerProject();
      if (!project) return;
      const task = getPinnedTimerTask(project);
      const pendingBeforeStop = getCurrentProjectTotalSeconds(project);
      if (pendingBeforeStop <= 0) {
        setFullscreenTimerSaveButtonState(false);
        if (window.showNotification) window.showNotification('No hay tiempo para guardar.', 'info');
        return;
      }
      // Stop timer on server first if running
      const running = getRunningLog(project);
      if (running) {
        try {
          const stopped = await sendTimerAction(project.id, 'stop', null);
          patchProjectInState(stopped);
        } catch (_) {}
      }
      const refreshedProject = projects.find(x => String(x.id) === String(project.id)) || project;
      const refreshedTask = task?.id ? getTaskFromProject(refreshedProject, task.id) : null;
      const pendingSeconds = getCurrentProjectTotalSeconds(refreshedProject) || pendingBeforeStop;
      if (pendingSeconds <= 0) {
        setFullscreenTimerSaveButtonState(false);
        if (window.showNotification) window.showNotification('No hay tiempo para guardar.', 'info');
        return;
      }
      const display = formatTimer(pendingSeconds);
      const day = new Date().toLocaleDateString('es-ES');
      const entries = getSavedTimerHistory(project.id);
      entries.push({
        time: display,
        day,
        saved_by: resolveCurrentUserName(refreshedProject),
        task_name: String(refreshedTask?.texto || task?.texto || ''),
      });
      setSavedTimerHistory(entries, project.id);
      setTimerResetBase(getProjectGrossSeconds(refreshedProject), refreshedProject.id);
      if (refreshedTask?.id) {
        setTaskTimerResetBase(Number(refreshedTask.total_seconds || 0), refreshedProject.id, refreshedTask.id);
      }
      setPinnedTimerContext(null, null);
      clearGlobalTimerState();
      await loadData();
      syncPinnedTimerHud();
      if (window.showNotification) window.showNotification('Tiempo guardado', 'success');
    }

    async function deletePinnedTimerEntry() {
      const project = getPinnedTimerProject();
      if (!project?.id) return;
      try {
        const response = await fetch('/api/proyectos/timer/eliminar', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ id: String(project.id) }),
        });
        const json = await response.json().catch(() => ({}));
        if (json.item) {
          patchProjectInState(json.item);
          setTimerResetBase(getProjectGrossSeconds(json.item), project.id);
        }
        closeTimerFullscreen();
        setPinnedTimerContext(null, null);
        clearGlobalTimerState();
        await loadData();
        syncPinnedTimerHud();
        if (window.showNotification) window.showNotification('Registro de tiempo eliminado', 'success');
      } catch (error) {
        console.error(error);
        if (window.showNotification) window.showNotification('No se pudo eliminar el registro', 'error');
      }
    }

    async function toggleTaskTimer(projectId, taskId) {
      const runningProject = findRunningProject();
      const runningLog = runningProject ? getRunningLog(runningProject) : null;
      const sameTaskRunning = !!runningProject
        && String(runningProject.id) === String(projectId)
        && String(runningLog?.task_id || '') === String(taskId || '');
      const switchingTask = !!runningProject && !!runningLog && !sameTaskRunning;

      if (switchingTask) {
        const accepted = await askConfirmTimerSwitch();
        if (!accepted) return;
      }

      try {
        if (switchingTask) {
          const removed = await removeTimerLogEntry(runningProject.id);
          patchProjectInState(removed);
          setTimerResetBase(getProjectGrossSeconds(removed), runningProject.id);
        } else if (runningProject && runningLog) {
          const stopped = await sendTimerAction(runningProject.id, 'stop', null);
          patchProjectInState(stopped);
        }

        if (!sameTaskRunning) {
          const started = await sendTimerAction(projectId, 'start', taskId || null);
          patchProjectInState(started);
          setTimerResetBase(getProjectGrossSeconds(started), projectId);
          setPinnedTimerContext(projectId, taskId || null);
        } else {
          setPinnedTimerContext(projectId, taskId || null);
        }

        await loadData();
        if (currentProjectId) {
          const modalProject = projects.find((entry) => String(entry.id) === String(currentProjectId));
          if (modalProject) {
            renderModalTasks(modalProject.tareas || []);
            updateModalTimer(modalProject);
            if (currentTaskId) {
              const freshTask = (modalProject.tareas || []).find((entry) => String(entry.id) === String(currentTaskId));
              if (freshTask) renderTaskDetail(freshTask, { preserveState: true });
            }
          }
        }
      } catch (error) {
        console.error(error);
      }
    }

    function askConfirmTimerSwitch() {
      const modal = document.getElementById('timerSwitchConfirmModal');
      if (!modal) {
        return Promise.resolve(confirm('¿Estás seguro de cambiar de tarea? Se eliminará el tiempo actual e iniciarás otra tarea.'));
      }
      modal.classList.remove('hidden');
      return new Promise((resolve) => {
        pendingTimerSwitchResolver = resolve;
      });
    }

    function closeTimerSwitchConfirm(accepted) {
      const modal = document.getElementById('timerSwitchConfirmModal');
      if (modal) modal.classList.add('hidden');
      if (pendingTimerSwitchResolver) pendingTimerSwitchResolver(!!accepted);
      pendingTimerSwitchResolver = null;
    }

    async function removeTimerLogEntry(projectId) {
      const response = await fetch('/api/proyectos/timer/eliminar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ id: String(projectId) }),
      });
      const json = await response.json().catch(() => ({}));
      if (!json?.item) throw new Error('timer_delete_failed');
      return json.item;
    }

    function openTimerFullscreen() {
      const panel = document.getElementById('timerFullscreenPanel');
      if (!panel) return;
      if (panel.requestFullscreen) {
        panel.requestFullscreen().catch(() => {});
      }
    }

    function closeTimerFullscreen() {
      const panel = document.getElementById('timerFullscreenPanel');
      panel.classList.add('hidden');
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
      }
    }

    async function ensureNativePipSource() {
      if (pipStreamReady) return true;
      const canvas = document.getElementById('timerPipCanvas');
      const video = document.getElementById('timerPipVideo');
      if (!canvas || !video || !canvas.captureStream) return false;
      const stream = canvas.captureStream(30);
      pipVideoTrack = stream.getVideoTracks ? (stream.getVideoTracks()[0] || null) : null;
      video.srcObject = stream;
      video.muted = true;
      video.playsInline = true;
      video.setAttribute('webkit-playsinline', 'true');
      video.autoplay = true;
      drawTimerPipCanvas(getPipLiveDisplayValue());
      try {
        await video.play();
      } catch (e) {}
      pipStreamReady = true;
      return true;
    }

    function getPipLiveDisplayValue() {
      const pinnedProject = getPinnedTimerProject();
      const fallbackProject = currentProjectId
        ? projects.find((x) => String(x.id) === String(currentProjectId))
        : null;
      const project = pinnedProject || fallbackProject;
      if (!project) return pipLastDisplayValue;
      const value = formatTimer(getCurrentProjectTotalSeconds(project));
      pipLastDisplayValue = value;
      return value;
    }

    function startPipRenderLoop() {
      if (pipRenderInterval) clearInterval(pipRenderInterval);
      pipRenderInterval = setInterval(() => {
        const val = getPipLiveDisplayValue();
        drawTimerPipCanvas(val);
      }, 250);
    }

    function setPipSourceVisible(show) {
      const video = document.getElementById('timerPipVideo');
      if (!video) return;
      if (show) {
        // Safari suele devolver negro si el video fuente está totalmente oculto.
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

    function drawTimerPipCanvas(timeValue) {
      const canvas = document.getElementById('timerPipCanvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      if (!ctx) return;
      const p = getPinnedTimerProject() || projects.find((x) => String(x.id) === String(currentProjectId)) || {};
      const title = p.titulo || 'Proyecto';
      const client = p.cliente || 'Sin Cliente';
      const safeTimeValue = String(timeValue || pipLastDisplayValue || '00:00:00');
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
      ctx.fillStyle = '#e2e8f0';
      ctx.font = 'bold 24px system-ui';
      ctx.fillText(fitText(title, maxTextWidth, 'bold 24px system-ui'), left, 142);
      ctx.fillStyle = '#94a3b8';
      ctx.font = '18px system-ui';
      ctx.fillText(fitText(client, maxTextWidth, '18px system-ui'), left, 172);
      ctx.fillStyle = '#bef264';
      ctx.font = 'bold 86px monospace';
      ctx.fillText(safeTimeValue, left, 330);
      if (pipVideoTrack && typeof pipVideoTrack.requestFrame === 'function') {
        pipVideoTrack.requestFrame();
      }
    }

    function setPipButtonState(active) {
      const btn = document.getElementById('timerMiniBtn');
      if (!btn) return;
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      btn.classList.toggle('border-lime-300', active);
      btn.classList.toggle('text-lime-600', active);
      btn.classList.toggle('bg-lime-50', active);
      btn.classList.toggle('border-slate-200', !active);
      btn.classList.toggle('text-slate-500', !active);
      btn.classList.toggle('bg-transparent', !active);
    }

    function syncPipTimerActionButton(isRunning) {
      const pipToggleBtn = document.getElementById('timerPipToggleBtn');
      if (!pipToggleBtn) return;
      if (isRunning) {
        pipToggleBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
        pipToggleBtn.classList.add('text-rose-300');
        pipToggleBtn.classList.remove('text-lime-300');
      } else {
        pipToggleBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>';
        pipToggleBtn.classList.add('text-lime-300');
        pipToggleBtn.classList.remove('text-rose-300');
      }
      if ('mediaSession' in navigator) {
        try {
          navigator.mediaSession.playbackState = isRunning ? 'playing' : 'paused';
        } catch (_) {}
      }
    }

    async function setCurrentProjectTimerRunning(shouldRun) {
      const resolvedProject = getPinnedTimerProject()
        || (currentProjectId ? projects.find((x) => String(x.id) === String(currentProjectId)) : null);
      if (!resolvedProject?.id) return;
      currentProjectId = resolvedProject.id;
      const p = resolvedProject;
      if (!p) return;
      const logs = p.time_logs || [];
      const isRunning = logs.length > 0 && !logs[logs.length - 1].end;
      if (isRunning === shouldRun) return;

      if (shouldRun) {
        const resumeTaskId = pinnedTimerTaskId || String(logs[logs.length - 1]?.task_id || '') || null;
        const taskId = resumeTaskId || await openTimerTaskModal(currentProjectId);
        if (typeof taskId === 'undefined') return;
        const item = await sendTimerAction(currentProjectId, 'start', taskId || null);
        p.time_logs = item.time_logs || [];
        p.tareas = item.tareas || p.tareas || [];
        setPinnedTimerContext(currentProjectId, taskId || null);
        updateModalTimer(p);
        renderModalTasks(p.tareas || []);
        syncPinnedTimerHud();
        return;
      }

      const item = await sendTimerAction(currentProjectId, 'stop', null);
      p.time_logs = item.time_logs || [];
      p.tareas = item.tareas || p.tareas || [];
      setPinnedTimerContext(currentProjectId, null);
      updateModalTimer(p);
      renderModalTasks(p.tareas || []);
      syncPinnedTimerHud();
    }

    async function toggleTimerMiniPip() {
      const video = document.getElementById('timerPipVideo');

      if (video && document.pictureInPictureElement === video && document.exitPictureInPicture) {
        suppressPipPlaybackSync = true;
        await document.exitPictureInPicture();
        setTimeout(() => { suppressPipPlaybackSync = false; }, 250);
        setPipButtonState(false);
        return;
      }

      if (video && video.webkitPresentationMode === 'picture-in-picture') {
        suppressPipPlaybackSync = true;
        video.webkitSetPresentationMode('inline');
        setTimeout(() => { suppressPipPlaybackSync = false; }, 250);
        setPipButtonState(false);
        return;
      }

      const ready = await ensureNativePipSource();
      if (ready && video && video.requestPictureInPicture) {
        try {
          setPipSourceVisible(true);
          await video.play().catch(() => {});
          startPipRenderLoop();
          await video.requestPictureInPicture();
          setPipButtonState(true);
          if ('mediaSession' in navigator) {
            const _p = getPinnedTimerProject();
            navigator.mediaSession.metadata = new MediaMetadata({
              title: getPinnedTimerTask(_p)?.texto || 'Temporizador activo',
              artist: _p?.titulo || 'Proyecto',
              album: 'InFocus CRM',
            });
            navigator.mediaSession.playbackState = getRunningLog(_p || {}) ? 'playing' : 'paused';
            navigator.mediaSession.setActionHandler('play', () => { togglePinnedTimerRun(); });
            navigator.mediaSession.setActionHandler('pause', () => { togglePinnedTimerRun(); });
            navigator.mediaSession.setActionHandler('previoustrack', () => { window.focus(); });
          }
          return;
        } catch (e) {}
      }

      if (ready && video && video.webkitSupportsPresentationMode && video.webkitSetPresentationMode) {
        try {
          setPipSourceVisible(true);
          await video.play().catch(() => {});
          if (video.webkitSupportsPresentationMode('picture-in-picture')) {
            startPipRenderLoop();
            video.webkitSetPresentationMode('picture-in-picture');
            setPipButtonState(true);
            return;
          }
        } catch (e) {}
      }

      // fallback interno
      const el = document.getElementById('timerMiniPip');
      const isHidden = el.classList.contains('hidden');
      el.classList.toggle('hidden');
      setPipButtonState(isHidden);
    }

    function initModalPrioritySelector() {
      document.querySelectorAll('#modalPrioritySelector .priority-chip').forEach(btn => {
        btn.addEventListener('click', () => setModalPriority(btn.getAttribute('data-priority'), true));
      });
    }

    function ensureFlatpickrAssets(callback) {
      const runWithLocale = () => {
        const done = () => {
          if (window.flatpickr?.l10ns?.es) {
            window.flatpickr.localize(window.flatpickr.l10ns.es);
          }
          callback();
        };

        if (window.flatpickr?.l10ns?.es) {
          done();
          return;
        }

        if (!document.getElementById('flatpickr-locale-es')) {
          const localeScript = document.createElement('script');
          localeScript.id = 'flatpickr-locale-es';
          localeScript.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js';
          localeScript.onload = done;
          document.body.appendChild(localeScript);
          return;
        }

        const waitLocale = () => {
          if (window.flatpickr?.l10ns?.es) {
            done();
            return;
          }
          setTimeout(waitLocale, 50);
        };
        waitLocale();
      };

      if (window.flatpickr) {
        runWithLocale();
        return;
      }

      if (!document.getElementById('flatpickr-css')) {
        const link = document.createElement('link');
        link.id = 'flatpickr-css';
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        document.head.appendChild(link);
      }

      if (document.getElementById('flatpickr-js')) {
        const waitFlatpickr = () => {
          if (window.flatpickr) {
            runWithLocale();
            return;
          }
          setTimeout(waitFlatpickr, 50);
        };
        waitFlatpickr();
        return;
      }
      const script = document.createElement('script');
      script.id = 'flatpickr-js';
      script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
      script.onload = runWithLocale;
      document.body.appendChild(script);
    }

    function initModalDuePicker() {
      ensureFlatpickrAssets(() => {
        if (modalDuePicker) return;
        const projectModalHost = document.getElementById('projectModal');
        modalDuePicker = window.flatpickr('#modalDueDate', {
          dateFormat: 'Y-m-d',
          altInput: true,
          altInputClass: 'block w-full h-12 rounded-xl border border-slate-200 pl-11 text-base shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer',
          altFormat: 'd M, Y',
          disableMobile: true,
          locale: 'es',
          appendTo: projectModalHost || undefined,
          clickOpens: true,
          onOpen: function(_, __, instance) {
            markCalendarInteraction(1000);
            if (instance?.calendarContainer) {
              instance.calendarContainer.style.zIndex = '2147483000';
              instance.calendarContainer.addEventListener('click', (ev) => ev.stopPropagation());
              instance.calendarContainer.addEventListener('mousedown', (ev) => ev.stopPropagation());
              instance.calendarContainer.addEventListener('mouseup', (ev) => ev.stopPropagation());
              instance.calendarContainer.addEventListener('pointerdown', (ev) => ev.stopPropagation());
              instance.calendarContainer.addEventListener('pointerup', (ev) => ev.stopPropagation());
              instance.calendarContainer.addEventListener('touchstart', (ev) => ev.stopPropagation(), { passive: true });
              instance.calendarContainer.addEventListener('touchend', (ev) => ev.stopPropagation(), { passive: true });
            }
          },
          onChange: function(selectedDates, dateStr) {
            markCalendarInteraction();
            updateProjectField('vencimiento', dateStr || null);
          },
          onClose: function() {
            markCalendarInteraction();
          }
        });
      });
    }

    function setModalDueDate(value) {
      if (modalDuePicker) {
        modalDuePicker.setDate(value || null, false);
      } else {
        document.getElementById('modalDueDate').value = value || '';
      }
    }

    function initNewProjectDatePickers() {
      ensureFlatpickrAssets(() => {
        const newProjectModalHost = document.getElementById('newProjectModal');
        if (!newProjectStartPicker) {
          newProjectStartPicker = window.flatpickr('#newProjectStart', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altInputClass: 'w-full h-11 rounded-xl border border-slate-200 pl-11 text-base font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer',
            altFormat: 'd M, Y',
            disableMobile: true,
            locale: 'es',
            appendTo: newProjectModalHost || undefined,
            clickOpens: true,
            onOpen: function(_, __, instance) {
              markCalendarInteraction();
              if (instance?.calendarContainer) {
                instance.calendarContainer.style.zIndex = '2147483000';
                instance.calendarContainer.addEventListener('click', (ev) => ev.stopPropagation());
                instance.calendarContainer.addEventListener('mousedown', (ev) => ev.stopPropagation());
                instance.calendarContainer.addEventListener('pointerdown', (ev) => ev.stopPropagation());
              }
            },
            onReady: function(_, __, instance) {
              if (instance.altInput) {
                instance.altInput.addEventListener('click', () => instance.open());
                instance.altInput.addEventListener('focus', () => instance.open());
              }
            },
            onChange: function() {
              markCalendarInteraction();
            },
            onClose: function() {
              markCalendarInteraction();
            }
          });
        }
        if (!newProjectDuePicker) {
          newProjectDuePicker = window.flatpickr('#newProjectDue', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altInputClass: 'w-full h-11 rounded-xl border border-slate-200 pl-11 text-base font-medium text-slate-700 shadow-sm focus:border-lime-500 focus:ring-lime-500 bg-white cursor-pointer',
            altFormat: 'd M, Y',
            disableMobile: true,
            locale: 'es',
            appendTo: newProjectModalHost || undefined,
            clickOpens: true,
            onOpen: function(_, __, instance) {
              markCalendarInteraction();
              if (instance?.calendarContainer) {
                instance.calendarContainer.style.zIndex = '2147483000';
                instance.calendarContainer.addEventListener('click', (ev) => ev.stopPropagation());
                instance.calendarContainer.addEventListener('mousedown', (ev) => ev.stopPropagation());
                instance.calendarContainer.addEventListener('pointerdown', (ev) => ev.stopPropagation());
              }
            },
            onReady: function(_, __, instance) {
              if (instance.altInput) {
                instance.altInput.addEventListener('click', () => instance.open());
                instance.altInput.addEventListener('focus', () => instance.open());
              }
            },
            onChange: function() {
              markCalendarInteraction();
            },
            onClose: function() {
              markCalendarInteraction();
            }
          });
        }
      });
    }

    function openNewProjectDatePicker(kind) {
      initNewProjectDatePickers();
      const tryOpenPicker = (attempt = 0) => {
        const picker = kind === 'due' ? newProjectDuePicker : newProjectStartPicker;
        if (picker) {
          picker.open();
          return;
        }
        if (attempt < 20) {
          setTimeout(() => tryOpenPicker(attempt + 1), 25);
        }
      };
      tryOpenPicker();
    }
    
    function refreshProjectDescriptionClamp() {
      const shell = document.getElementById('projectDescShell');
      const editor = document.getElementById('modalDesc');
      const toggleText = document.getElementById('projectDescToggleText');
      const toggleIcon = document.getElementById('projectDescToggleIcon');
      if (!shell || !editor) return;
      if (isCompactDescEditorFocused('modalDesc')) {
        shell.classList.remove('is-collapsed');
        shell.classList.add('toggle-dismissed');
        editor.style.height = '';
        return;
      }

      const collapsedMaxHeight = 320;
      editor.style.height = 'auto';
      const fullHeight = Math.max(editor.scrollHeight, 152);
      const hasOverflow = fullHeight > collapsedMaxHeight + 8;

      shell.classList.toggle('has-overflow', hasOverflow);
      shell.classList.toggle('is-collapsed', hasOverflow && !projectDescriptionExpanded);

      if (hasOverflow && !projectDescriptionExpanded) {
        editor.style.height = `${collapsedMaxHeight}px`;
      } else {
        editor.style.height = `${fullHeight}px`;
      }

      if (toggleText) {
        toggleText.textContent = projectDescriptionExpanded ? 'Mostrar menos' : 'Mostrar más';
      }
      if (toggleIcon) {
        toggleIcon.style.transform = projectDescriptionExpanded ? 'rotate(180deg)' : '';
      }
    }

    function toggleProjectDescription() {
      projectDescriptionExpanded = true;
      document.getElementById('projectDescShell')?.classList.add('toggle-dismissed');
      refreshProjectDescriptionClamp();
    }

    function setDescriptionAutosaveStatus(state) {
      const status = document.getElementById('modalDescAutosaveStatus');
      if (!status) return;
      status.classList.remove('text-slate-400', 'text-amber-600', 'text-lime-600', 'text-rose-600');
      if (state === 'saving') {
        status.textContent = 'Guardando...';
        status.classList.add('text-amber-600');
      } else if (state === 'saved') {
        status.textContent = 'Guardado';
        status.classList.add('text-lime-600');
      } else if (state === 'error') {
        status.textContent = 'No se pudo guardar';
        status.classList.add('text-rose-600');
      } else {
        status.textContent = 'Autoguardado';
        status.classList.add('text-slate-400');
      }
    }

    function queueDescriptionAutosave() {
      if (projectModalReadOnly || !currentProjectId) return;
      const desc = getCompactDescValue('modalDesc');
      const projectId = String(currentProjectId);
      pendingProjectDescriptions[projectId] = desc;
      const p = projects.find(x => String(x.id) === projectId);
      if (p) p.descripcion = desc;
      refreshProjectDescriptionClamp();
      setDescriptionAutosaveStatus('saving');
      clearTimeout(modalDescAutosaveTimer);
      modalDescAutosaveTimer = setTimeout(() => saveDescriptionAutosave(projectId, desc), 650);
    }

    async function saveDescriptionAutosave(projectId, desc) {
      if (!projectId) return;
      try {
        await updateProjectField('descripcion', desc, projectId);
        if (pendingProjectDescriptions[projectId] === desc) {
          delete pendingProjectDescriptions[projectId];
        }
        if (String(currentProjectId || '') === projectId) {
          setDescriptionAutosaveStatus('saved');
        }
      } catch (error) {
        console.error('Error guardando descripcion:', error);
        if (String(currentProjectId || '') === projectId) {
          setDescriptionAutosaveStatus('error');
        }
      }
    }

    // --- Tasks ---
    function formatTaskInvested(totalSeconds) {
        const sec = Math.max(0, Number(totalSeconds) || 0);
      const d = Math.floor(sec / 86400);
      const h = Math.floor((sec % 86400) / 3600).toString().padStart(2, '0');
      const m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
      return `${d}d:${h}h:${m}m`;
    }

    function normalizeTaskPriority(value) {
      const v = String(value || '').trim().toLowerCase();
      if (v === 'vencido' || v === 'vencida' || v === 'overdue') return 'Vencido';
      if (v === 'urgente' || v === 'alta') return 'Urgente';
      if (v === 'atención' || v === 'atencion' || v === 'media') return 'Atención';
      return 'Con calma';
    }

    function getEffectiveTaskPriority(task, project = null) {
      const due = getTaskDueDate(task, project);
      if (due && !task?.done) {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const dueDay = new Date(due.getFullYear(), due.getMonth(), due.getDate());
        const diffDays = Math.floor((dueDay - today) / 86400000);
        if (diffDays < 0) return 'Vencido';
        if (diffDays <= 7) return 'Atención';
      }
      return normalizeTaskPriority(task?.priority || project?.prioridad || 'Con calma');
    }

    function getEffectiveProjectPriority(project) {
      const due = project?.vencimiento ? new Date(`${project.vencimiento}T12:00:00`) : null;
      if (due && !Number.isNaN(due.getTime())) {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const dueDay = new Date(due.getFullYear(), due.getMonth(), due.getDate());
        const diffDays = Math.floor((dueDay - today) / 86400000);
        if (diffDays < 0) return 'Vencido';
        if (diffDays <= 7) return 'Atención';
      }
      return normalizePriority(project?.prioridad || 'Con calma');
    }

    function getTaskPriorityStyles(value) {
      const level = normalizeTaskPriority(value);
      if (level === 'Urgente') {
        return {
          bar: 'bg-rose-500',
          chip: 'bg-rose-50 text-rose-700 border-rose-200',
        };
      }
      if (level === 'Vencido') {
        return {
          bar: 'bg-slate-700',
          chip: 'bg-slate-100 text-slate-700 border-slate-300',
        };
      }
      if (level === 'Atención') {
        return {
          bar: 'bg-amber-500',
          chip: 'bg-amber-50 text-amber-700 border-amber-200',
        };
      }
      return {
        bar: 'bg-emerald-500',
        chip: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      };
    }

    function getTaskPriorityIcon(value, className = 'h-3.5 w-3.5 shrink-0 self-center') {
      const level = normalizeTaskPriority(value);
      if (level === 'Urgente') {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>`;
      }
      if (level === 'Vencido') {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`;
      }
      if (level === 'Atención') {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>`;
      }
      return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>`;
    }

    function getTaskPriorityBadge(value, size = 'sm') {
      const level = normalizeTaskPriority(value);
      const textSize = size === 'xs' ? 'text-[10px] leading-none' : 'text-xs';
      const iconSize = size === 'xs' ? 'h-3 w-3' : 'h-3.5 w-3.5';
      return `<span class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 font-bold ${textSize} ${getTaskPriorityStyles(level).chip}">${getTaskPriorityIcon(level, `${iconSize} shrink-0 self-center`)}<span>${level}</span></span>`;
    }

    function getProjectStageIcon(stage, className = 'h-3.5 w-3.5 shrink-0') {
      const value = String(stage || '').trim().toLowerCase();
      if (/complet|cerrad|final|entreg/.test(value)) {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`;
      }
      if (/revisi|review|valid/.test(value)) {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>`;
      }
      if (/progreso|desarrollo|curso|activo/.test(value)) {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/></svg>`;
      }
      if (/pausa|espera|hold/.test(value)) {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M10 15V9"/><path d="M14 15V9"/></svg>`;
      }
      if (/cancel|perdid|rechaz/.test(value)) {
        return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`;
      }
      return `<svg class="${className}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4Z"/></svg>`;
    }

    function getProjectStageBadge(stage, compact = false) {
      const label = escapeHtml(stage || 'Sin etapa');
      const size = compact ? 'text-[11px] px-2 py-0.5' : 'text-xs px-2.5 py-1';
      return `<span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 ${size} font-extrabold text-slate-600">${getProjectStageIcon(stage)}<span>${label}</span></span>`;
    }

    function getProjectStageCircle(stage, className = 'h-8 w-8') {
      return `<span class="inline-flex ${className} shrink-0 items-center justify-center rounded-full border border-lime-200 bg-lime-50 text-slate-900 shadow-[0_6px_18px_-12px_rgba(132,204,22,0.75)]" title="${escapeHtml(stage || 'Sin etapa')}">${getProjectStageIcon(stage, 'h-4 w-4 shrink-0')}</span>`;
    }

    function refreshProjectStageUI() {
      const select = document.getElementById('modalStage');
      const stage = select?.value || 'Inicio';
      if (select?._appSelectTrigger) {
        const trigger = select._appSelectTrigger;
        const current = select.options[select.selectedIndex] || select.options[0];
        if (select._appSelectLabel) select._appSelectLabel.textContent = current ? current.textContent.trim() : stage;
        trigger.classList.remove('has-leading-icon');
      }
    }

    function refreshTaskModalPriorityUI() {
      const select = document.getElementById('taskModalPriority');
      const iconWrap = document.getElementById('taskModalPriorityIcon');
      const level = normalizeTaskPriority(select?.value || 'Con calma');
      const styles = getTaskPriorityStyles(level);
      const textClass = (styles.chip.match(/text-\S+/) || ['text-amber-700'])[0];
      if (select) {
        select._appSelectLabelHtml = (current) => {
          const currentLevel = normalizeTaskPriority(current?.value || current?.textContent || level);
          return `<span class="inline-flex min-w-0 items-center gap-1.5">${getTaskPriorityIcon(currentLevel, 'h-3.5 w-3.5 shrink-0 self-center')}<span class="truncate">${escapeHtml(currentLevel)}</span></span>`;
        };
        select._appSelectOptionHtml = (option) => {
          const optionLevel = normalizeTaskPriority(option.value || option.label || level);
          const optionTextClass = (getTaskPriorityStyles(optionLevel).chip.match(/text-\S+/) || ['text-slate-700'])[0];
          return `<span class="inline-flex min-w-0 items-center gap-2 ${optionTextClass}">${getTaskPriorityIcon(optionLevel, 'h-4 w-4 shrink-0 self-center')}<span class="truncate">${escapeHtml(optionLevel)}</span></span>`;
        };
        const trigger = select._appSelectTrigger || null;
        if (trigger) {
          const isOpen = trigger.classList.contains('is-open');
          const isDisabled = select.disabled;
          const current = select.options[select.selectedIndex] || select.options[0];
          select.classList.add('app-native-select');
          if (select._appSelectLabel) select._appSelectLabel.innerHTML = select._appSelectLabelHtml(current);
          trigger.className = `app-select-trigger rounded-full border px-3 py-1.5 text-sm font-extrabold shadow-sm ${styles.chip}`;
          trigger.classList.toggle('is-open', isOpen);
          trigger.classList.toggle('is-disabled', isDisabled);
          trigger.disabled = isDisabled;
        }
      }
      if (iconWrap) {
        const isEnhanced = !!select?._appSelectTrigger;
        iconWrap.className = `pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 ${textClass} ${isEnhanced ? 'hidden' : ''}`;
        iconWrap.innerHTML = getTaskPriorityIcon(level, 'w-4 h-4 shrink-0 self-center');
      }
    }

    function formatTaskDateRange(task) {
      const start = task?.start_date || null;
      const end = task?.end_date || task?.due_date || null;
      if (!start && !end) return 'Sin fecha';
      if (start && end) {
        const s = new Date(start + 'T12:00:00').toLocaleDateString('es-ES');
        const e = new Date(end + 'T12:00:00').toLocaleDateString('es-ES');
        return `${s} - ${e}`;
      }
      const d = new Date((start || end) + 'T12:00:00').toLocaleDateString('es-ES');
      return d;
    }

    function getTaskDueDate(task, project) {
      const raw = task?.end_date || task?.due_date || project?.vencimiento || '';
      if (!raw) return null;
      const date = new Date(`${raw}T12:00:00`);
      return Number.isNaN(date.getTime()) ? null : date;
    }

    function taskMatchesGlobalFilter(task, project) {
      const due = getTaskDueDate(task, project);
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const in7 = new Date(today);
      in7.setDate(in7.getDate() + 7);
      const level = getEffectiveTaskPriority(task, project);

      if (globalTaskFilter === 'urgent') return level === 'Urgente';
      if (globalTaskFilter === 'attention') return level === 'Atención';
      if (globalTaskFilter === 'calm') return level === 'Con calma';
      if (globalTaskFilter === 'progress') return level === 'Atención' || level === 'Con calma';
      if (globalTaskFilter === 'inprogress') return !task?.done && Number(task?.total_seconds || 0) > 0;
      if (globalTaskFilter === 'pending') return !task?.done;
      if (globalTaskFilter === 'completed') return !!task?.done;
      if (globalTaskFilter === 'overdue') return !!due && !task?.done && due < today;
      if (globalTaskFilter === 'upcoming') return !!due && !task?.done && due >= today && due <= in7;
      return true;
    }

    function openProjectTask(projectId, taskId) {
      if (!projectId || !taskId) return;
      currentProjectId = projectId;
      currentTaskId = null;
      currentTaskModalEditing = true;
      currentTaskEditingNoteId = null;
      document.getElementById('projectModal')?.classList.add('hidden');
      openTaskModal(taskId);
    }

    function getGlobalTasksProjectPage(projectId, totalItems = 0) {
      const totalPages = Math.max(1, Math.ceil(Math.max(0, Number(totalItems) || 0) / GLOBAL_TASKS_PER_PROJECT_PAGE));
      const currentPage = Math.max(1, Math.min(totalPages, Number(globalTasksProjectPages[String(projectId)] || 1)));
      globalTasksProjectPages[String(projectId)] = currentPage;
      return currentPage;
    }

    function setGlobalTasksProjectPage(projectId, page) {
      const safeProjectId = String(projectId || '').trim();
      if (!safeProjectId) return;
      globalTasksProjectPages[safeProjectId] = Math.max(1, Number(page) || 1);
      renderGlobalTasksView(projects);
    }

    function loadGlobalTasksCollapsedProjects() {
      try {
        const parsed = JSON.parse(localStorage.getItem(GLOBAL_TASKS_COLLAPSED_KEY) || '[]');
        if (Array.isArray(parsed)) {
          parsed.forEach((id) => {
            const safeId = String(id || '').trim();
            if (safeId) globalTasksCollapsedProjects.add(safeId);
          });
        }
      } catch (_) {}
    }

    function persistGlobalTasksCollapsedProjects() {
      try {
        localStorage.setItem(GLOBAL_TASKS_COLLAPSED_KEY, JSON.stringify(Array.from(globalTasksCollapsedProjects)));
      } catch (_) {}
    }

    function toggleGlobalTasksProjectCollapse(projectId) {
      const safeProjectId = String(projectId || '').trim();
      if (!safeProjectId) return;
      if (globalTasksCollapsedProjects.has(safeProjectId)) {
        globalTasksCollapsedProjects.delete(safeProjectId);
      } else {
        globalTasksCollapsedProjects.add(safeProjectId);
      }
      persistGlobalTasksCollapsedProjects();
      renderGlobalTasksView(projects);
    }

    function setGlobalTasksClientPage(page) {
      globalTasksClientPage = Math.max(1, Number(page) || 1);
      renderGlobalTasksView(projects);
    }

    function setGlobalTaskSearch(query) {
      globalTaskSearchQuery = String(query || '').trim().toLowerCase();
      globalTasksClientPage = 1;
      Object.keys(globalTasksProjectPages).forEach((key) => {
        globalTasksProjectPages[key] = 1;
      });
      renderGlobalTasksView(projects);
      renderQuickActionsStatus('tareas');
    }

    function setListProjectSearch(query) {
      listProjectSearchQuery = String(query || '').trim().toLowerCase();
      renderProjectListView(projects);
      renderQuickActionsStatus('lista');
    }

    function taskMatchesGlobalSearch(task, project) {
      const query = globalTaskSearchQuery.trim();
      if (!query) return true;
      const haystack = [
        String(task?.texto || ''),
        String(project?.titulo || ''),
        String(project?.cliente || ''),
        String(getEffectiveTaskPriority(task, project)),
      ].join(' ').toLowerCase();
      return haystack.includes(query);
    }

    function projectMatchesGlobalSearch(project) {
      const query = globalTaskSearchQuery.trim();
      if (!query) return true;
      const haystack = [
        String(project?.titulo || ''),
        String(project?.cliente || ''),
        String(project?.etapa || ''),
      ].join(' ').toLowerCase();
      return haystack.includes(query);
    }

    async function openProjectAddTask(projectId) {
      if (!projectId) return;
      await openProject(projectId);
      window.requestAnimationFrame(() => {
        const input = document.getElementById('newTaskInput');
        if (!input) return;
        input.focus();
        input.scrollIntoView({ block: 'center', behavior: 'smooth' });
      });
    }

    window.setGlobalTasksProjectPage = setGlobalTasksProjectPage;
    window.setGlobalTasksClientPage = setGlobalTasksClientPage;
    window.toggleGlobalTasksProjectCollapse = toggleGlobalTasksProjectCollapse;

    function renderGlobalTasksView(list) {
      if (!globalTasksBoard) return;
      const filteredProjects = [];

      list.forEach((p) => {
        const tasks = Array.isArray(p.tareas) ? p.tareas : [];
        const filtered = tasks.filter((t) => taskMatchesGlobalFilter(t, p) && taskMatchesGlobalSearch(t, p));
        const projectWithoutTasks = tasks.length === 0;
        const includeWithoutTasks = projectWithoutTasks
          && globalTaskFilter === 'all'
          && projectMatchesGlobalSearch(p);
        if (!filtered.length && !includeWithoutTasks) return;
        filteredProjects.push({ project: p, filteredTasks: filtered });
      });

      if (!filteredProjects.length) {
        globalTasksBoard.innerHTML = `<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
          <div class="text-sm font-semibold text-slate-600">No hay tareas para este filtro.</div>
          <div class="mt-1 text-xs text-slate-500">Cambia los filtros o crea un proyecto para comenzar.</div>
          <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <button type="button" onclick="resetQuickFilters('tareas')" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-100">Limpiar filtros</button>
            <button type="button" onclick="openNewProjectModal()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-3 text-xs font-bold text-slate-900 hover:bg-lime-200">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <span>Crear proyecto</span>
            </button>
          </div>
        </div>`;
        renderQuickActionsStatus('tareas');
        refreshProjectsSimpleModeUI();
        return;
      }

      const totalClientPages = Math.max(1, Math.ceil(filteredProjects.length / GLOBAL_TASKS_CLIENTS_PER_PAGE));
      globalTasksClientPage = Math.max(1, Math.min(totalClientPages, globalTasksClientPage));
      const clientStart = (globalTasksClientPage - 1) * GLOBAL_TASKS_CLIENTS_PER_PAGE;
      const visibleProjects = filteredProjects.slice(clientStart, clientStart + GLOBAL_TASKS_CLIENTS_PER_PAGE);

      const blocks = visibleProjects.map(({ project: p, filteredTasks }) => {
        const taskStats = getProjectTaskStats(p);
        const safeProjectId = String(p.id || '').replace(/'/g, "\\'");
        const isCollapsed = globalTasksCollapsedProjects.has(String(p.id || ''));
        const projectPage = getGlobalTasksProjectPage(p.id, filteredTasks.length);
        const totalTaskPages = Math.max(1, Math.ceil(filteredTasks.length / GLOBAL_TASKS_PER_PROJECT_PAGE));
        const taskStart = (projectPage - 1) * GLOBAL_TASKS_PER_PROJECT_PAGE;
        const visibleTasks = filteredTasks.slice(taskStart, taskStart + GLOBAL_TASKS_PER_PROJECT_PAGE);

        const taskItems = visibleTasks.map((t) => {
          const level = getEffectiveTaskPriority(t, p);
          const due = getTaskDueDate(t, p);
          const dueLabel = due ? due.toLocaleDateString('es-ES') : 'Sin fecha';
          const ownerSource = getTaskOwnerSources(t, p);
          const hasOwners = ownerSource.names.length > 0 || ownerSource.ids.length > 0;
          const ownerLabel = hasOwners ? getResponsibleProfiles(ownerSource.names, ownerSource.ids).map((profile) => profile.name).join(', ') : 'Sin encargados';
          const ownerBadge = renderResponsibleBadges(ownerSource.names, ownerSource.ids, {
            limit: 3,
            bubbleClass: 'w-7 h-7 rounded-full border border-slate-200 bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center overflow-hidden',
            wrapperClass: 'flex items-center gap-1.5 shrink-0',
            extraClass: 'text-[10px] text-slate-500 font-semibold',
            emptyHtml: '<span class="text-slate-500 shrink-0">Sin encargados</span>'
          });
          const runningProject = findRunningProject();
          const runningLog = runningProject ? getRunningLog(runningProject) : null;
          const isTaskRunning = !!runningProject && String(runningProject.id) === String(p.id) && String(runningLog?.task_id || '') === String(t.id || '');
          return `<div class="group px-2 py-2 border-b border-slate-200 last:border-b-0 rounded-xl cursor-pointer transition-colors hover:bg-slate-50" onclick="openProjectTask('${p.id}', '${t.id}')">
            <div class="flex items-center gap-3">
              <button type="button" onclick="event.stopPropagation(); toggleTask('${t.id}', '${p.id}')" class="flex-none w-5 h-5 rounded border ${t.done ? 'bg-lime-500 border-lime-500 text-white' : 'border-slate-300 text-transparent'} flex items-center justify-center transition-colors" title="${t.done ? 'Desmarcar tarea' : 'Completar tarea'}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
              </button>
              <div class="flex-1 min-w-0">
                <button type="button" onclick="event.stopPropagation(); openProjectTask('${p.id}', '${t.id}')" class="block w-full text-left text-base font-bold ${t.done ? 'text-slate-400 line-through' : 'text-slate-800 hover:text-slate-950'} break-words leading-snug">${escapeHtml(t.texto || 'Tarea')}</button>
                <div class="mt-1 flex flex-wrap items-center gap-3 text-[11px]">
                  ${ownerBadge}
                  <div class="flex items-center gap-1.5 text-slate-600">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>${dueLabel}</span>
                  </div>
                  ${getTaskPriorityBadge(level)}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" onclick="event.stopPropagation(); openProjectTask('${p.id}', '${t.id}')" class="w-8 h-8 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center" title="Ver tarea">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                </button>
                <button type="button" onclick="event.stopPropagation(); toggleTaskTimer('${p.id}', '${t.id}')" class="inline-flex h-10 items-center gap-2 rounded-xl border px-3 text-sm font-extrabold shadow-sm transition-colors ${isTaskRunning ? 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800' : 'border-lime-200 bg-lime-50 text-slate-800 hover:bg-lime-100'}" title="${isTaskRunning ? 'Pausar temporizador' : 'Iniciar temporizador'}">
                  ${isTaskRunning
                    ? '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg><span>Pausar</span>'
                    : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>Iniciar timer</span>'}
                </button>
              </div>
            </div>
          </div>`;
        }).join('');

        const addTaskButton = `<div class="px-4 py-3 border-t border-slate-100 bg-slate-50/70">
          <button type="button" onclick="openProjectAddTask('${safeProjectId}')" class="inline-flex h-8 items-center rounded-lg border border-lime-200 bg-lime-100 px-3 text-xs font-bold text-slate-900 hover:bg-lime-200">
            Añadir tarea
          </button>
        </div>`;

        const emptyTaskState = `<div class="px-4 py-6 text-center">
          <div class="text-sm font-semibold text-slate-600">Este proyecto aún no tiene tareas.</div>
        </div>`;

        const hasVisibleTasks = visibleTasks.length > 0;
        const taskPaginator = hasVisibleTasks && totalTaskPages > 1
          ? `<div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-slate-100 bg-slate-50">
              <div class="text-[11px] font-semibold text-slate-500">Tareas ${taskStart + 1}-${Math.min(taskStart + GLOBAL_TASKS_PER_PROJECT_PAGE, filteredTasks.length)} de ${filteredTasks.length}</div>
              <div class="flex items-center gap-2">
                <button type="button" onclick="setGlobalTasksProjectPage('${safeProjectId}', ${projectPage - 1})" ${projectPage <= 1 ? 'disabled' : ''} class="h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Anterior</button>
                <span class="text-[11px] font-bold text-slate-600">${projectPage}/${totalTaskPages}</span>
                <button type="button" onclick="setGlobalTasksProjectPage('${safeProjectId}', ${projectPage + 1})" ${projectPage >= totalTaskPages ? 'disabled' : ''} class="h-8 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Siguiente</button>
              </div>
            </div>`
          : '';

        return `<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex items-start gap-2">
                  <button type="button" onclick="toggleGlobalTasksProjectCollapse('${safeProjectId}')" class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border transition-all ${isCollapsed ? 'border-slate-200 bg-white text-slate-500 shadow-sm hover:border-lime-300 hover:bg-lime-50 hover:text-slate-900' : 'border-lime-200 bg-lime-50 text-slate-900 shadow-[0_6px_18px_-12px_rgba(132,204,22,0.65)] hover:bg-lime-100'}" title="${isCollapsed ? 'Desplegar tareas' : 'Comprimir tareas'}" aria-label="${isCollapsed ? 'Desplegar tareas' : 'Comprimir tareas'}" aria-expanded="${isCollapsed ? 'false' : 'true'}">
                    <svg class="h-4 w-4 transition-transform duration-200 ${isCollapsed ? '-rotate-90' : 'rotate-0'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M19 9l-7 7-7-7"/></svg>
                  </button>
                  <div class="min-w-0">
                    <button type="button" onclick="openProject('${p.id}')" class="block w-full text-left text-xl font-extrabold leading-tight text-slate-950 hover:text-slate-800">${escapeHtml(p.titulo || 'Proyecto')}</button>
                    <div>
                      <button type="button" onclick="openProject('${p.id}')" class="mt-1 text-sm font-medium text-slate-500 hover:text-slate-700">${escapeHtml(p.cliente || 'Sin cliente')} · ${escapeHtml(p.etapa || 'Sin etapa')}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="text-xs font-bold text-slate-500 bg-white border border-slate-200 rounded-full px-2.5 py-1 whitespace-nowrap">${taskStats.total} tareas</div>
            </div>
            <div class="mt-3">
              <div class="mb-1.5 flex items-center justify-between text-xs font-semibold text-slate-500">
                <span>Tareas</span>
                <span class="font-bold text-lime-700">${taskStats.done}/${taskStats.total} · ${taskStats.pct}%</span>
              </div>
              <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                <div class="${animateProgressBarsOnce ? 'progress-fill-live ' : ''}h-2 rounded-full" style="width:${taskStats.pct}%; background-color:${progressBarColor(taskStats.pct)}"></div>
              </div>
            </div>
          </div>
          ${isCollapsed ? '' : `<div class="px-3 py-1">${hasVisibleTasks ? taskItems : emptyTaskState}</div>${taskPaginator}${addTaskButton}`}
        </div>`;
      });

      const clientPaginator = totalClientPages > 1
        ? `<div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="text-sm font-semibold text-slate-600">Clientes ${clientStart + 1}-${Math.min(clientStart + GLOBAL_TASKS_CLIENTS_PER_PAGE, filteredProjects.length)} de ${filteredProjects.length}</div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="setGlobalTasksClientPage(${globalTasksClientPage - 1})" ${globalTasksClientPage <= 1 ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Anterior</button>
              <span class="text-sm font-bold text-slate-700">${globalTasksClientPage}/${totalClientPages}</span>
              <button type="button" onclick="setGlobalTasksClientPage(${globalTasksClientPage + 1})" ${globalTasksClientPage >= totalClientPages ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Siguiente</button>
            </div>
          </div>`
        : '';

      globalTasksBoard.innerHTML = `${blocks.join('')}${clientPaginator}`;
      renderQuickActionsStatus('tareas');
      refreshProjectsSimpleModeUI();
    }

    function setProjectListPage(page) {
      const nextPage = Number(page) || 1;
      projectListCurrentPage = Math.max(1, nextPage);
      renderProjectListView(projects);
    }

    function renderProjectListPagination(totalItems) {
      const pagination = document.getElementById('projectListPagination');
      if (!pagination) return;

      const totalPages = Math.max(1, Math.ceil((Number(totalItems) || 0) / PROJECT_LIST_PAGE_SIZE));
      if (projectListCurrentPage > totalPages) projectListCurrentPage = totalPages;

      if (!totalItems) {
        pagination.classList.add('hidden');
        pagination.innerHTML = '';
        return;
      }

      const start = (projectListCurrentPage - 1) * PROJECT_LIST_PAGE_SIZE;
      const from = start + 1;
      const to = Math.min(start + PROJECT_LIST_PAGE_SIZE, totalItems);

      pagination.classList.remove('hidden');
      pagination.innerHTML = `<div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        <div class="text-sm font-semibold text-slate-600">Proyectos ${from}-${to} de ${totalItems}</div>
        <div class="flex items-center gap-2">
          <button type="button" onclick="setProjectListPage(${projectListCurrentPage - 1})" ${projectListCurrentPage <= 1 ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Anterior</button>
          <span class="text-sm font-bold text-slate-700">${projectListCurrentPage}/${totalPages}</span>
          <button type="button" onclick="setProjectListPage(${projectListCurrentPage + 1})" ${projectListCurrentPage >= totalPages ? 'disabled' : ''} class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">Siguiente</button>
        </div>
      </div>`;
    }

    function renderProjectListView(list) {
      const body = document.getElementById('projectListBody');
      if (!body) return;

      renderListFilterChips();
      const filteredList = filterProjectListData(list);

      if (!Array.isArray(filteredList) || !filteredList.length) {
        projectListCurrentPage = 1;
        body.innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center">
          <div class="text-sm font-semibold text-slate-600">No hay proyectos para este filtro.</div>
          <div class="mt-1 text-xs text-slate-500">Puedes limpiar filtros o crear un nuevo proyecto.</div>
          <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <button type="button" onclick="resetQuickFilters('lista')" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-100">Limpiar filtros</button>
            <button type="button" onclick="openNewProjectModal()" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-100 px-3 text-xs font-bold text-slate-900 hover:bg-lime-200">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <span>Crear proyecto</span>
            </button>
          </div>
        </td></tr>`;
        renderProjectListPagination(0);
        renderQuickActionsStatus('lista');
        refreshProjectsSimpleModeUI();
        return;
      }

      const totalItems = filteredList.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / PROJECT_LIST_PAGE_SIZE));
      if (projectListCurrentPage > totalPages) projectListCurrentPage = totalPages;
      const start = (projectListCurrentPage - 1) * PROJECT_LIST_PAGE_SIZE;
      const pageList = filteredList.slice(start, start + PROJECT_LIST_PAGE_SIZE);

      body.innerHTML = pageList.map((p) => {
        const title = escapeHtml(p.titulo || 'Proyecto sin título');
        const client = escapeHtml(p.cliente || 'Sin cliente');
        const priority = getEffectiveProjectPriority(p);
        const prog = getProjectTaskStats(p).pct;
        const due = p.vencimiento ? new Date(`${p.vencimiento}T12:00:00`) : null;
        const dueLabel = (due && !Number.isNaN(due.getTime())) ? due.toLocaleDateString('es-ES') : 'Sin fecha';
        const initial = escapeHtml(String(p.titulo || 'P').trim().charAt(0).toUpperCase() || 'P');
        const safeProjectId = String(p.id || '').replace(/'/g, "\\'");
        const ownerSource = getProjectResponsibleSources(p);
        const ownerProfiles = getResponsibleProfiles(ownerSource.names, ownerSource.ids);
        const ownerLabel = ownerProfiles.length ? escapeHtml(ownerProfiles.map((profile) => profile.name).join(', ')) : 'Sin responsables';
        const ownerBubbles = renderResponsibleBadges(ownerSource.names, ownerSource.ids, {
          limit: 2,
          bubbleClass: 'inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-900 text-[10px] font-extrabold text-white overflow-hidden',
          wrapperClass: 'flex items-center gap-1.5 min-w-0',
          extraClass: 'text-xs font-bold text-slate-500',
          emptyHtml: '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-900 text-[10px] font-extrabold text-white">SR</span>'
        });

        return `<tr class="cursor-pointer transition-colors hover:bg-slate-50" onclick="openProject('${p.id}')">
          <td class="px-4 py-4 whitespace-nowrap">
            <div class="flex items-center gap-2.5">
              <div class="h-7 w-7 rounded-full bg-slate-900 text-white text-xs font-bold grid place-content-center flex-shrink-0">${initial}</div>
              <div class="min-w-0">
                <div class="text-base font-normal text-lime-600 no-underline">${title}</div>
              </div>
            </div>
          </td>
          <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-slate-700">${client}</td>
          <td class="px-4 py-4">
            ${getTaskPriorityBadge(priority)}
          </td>
          <td class="px-4 py-4 min-w-[12rem]">
            <div class="flex items-center gap-2">
              <div class="h-2.5 w-24 rounded-full bg-slate-200 overflow-hidden">
                <div class="${animateProgressBarsOnce ? 'progress-fill-live ' : ''}h-2.5 rounded-full bg-[#101729]" style="width:${prog}%"></div>
              </div>
              <span class="font-bold text-slate-700">${prog}%</span>
            </div>
          </td>
          <td class="px-4 py-4 min-w-[12rem]">
            <div class="min-w-0" title="${ownerLabel}">${ownerBubbles}</div>
          </td>
          <td class="px-4 py-4 whitespace-nowrap text-slate-700">${dueLabel}</td>
          <td class="px-4 py-4 whitespace-nowrap">
            <button type="button" onclick="event.stopPropagation(); archiveProjectById('${safeProjectId}')" class="inline-grid place-content-center w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-1 shadow-sm" title="Archivar proyecto">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0-12-4 4m4-4 4 4M4 14v3a3 3 0 003 3h10a3 3 0 003-3v-3"/></svg>
            </button>
          </td>
        </tr>`;
      }).join('');
      renderProjectListPagination(totalItems);
      renderQuickActionsStatus('lista');
      refreshProjectsSimpleModeUI();
    }

    function renderArchivedProjectsView(list) {
      const body = document.getElementById('projectListBodyArchived');
      const emptyState = document.getElementById('archivedProjectsEmpty');
      if (!body || !emptyState) return;

      // Filter only archived projects
      const archivedList = Array.isArray(list) ? list.filter(p => p.archived === true || p.archived === 1 || String(p.archived).toLowerCase() === 'true') : [];

      if (!archivedList.length) {
        body.innerHTML = '';
        emptyState.style.display = 'block';
        return;
      }

      emptyState.style.display = 'none';
      body.innerHTML = archivedList.map((p) => {
        const title = escapeHtml(p.titulo || 'Proyecto sin título');
        const client = escapeHtml(p.cliente || 'Sin cliente');
        const priority = getEffectiveProjectPriority(p);
        const prog = getProjectTaskStats(p).pct;
        const due = p.vencimiento ? new Date(`${p.vencimiento}T12:00:00`) : null;
        const dueLabel = (due && !Number.isNaN(due.getTime())) ? due.toLocaleDateString('es-ES') : 'Sin fecha';
        const initial = escapeHtml(String(p.titulo || 'P').trim().charAt(0).toUpperCase() || 'P');
        const ownerSource = getProjectResponsibleSources(p);
        const ownerProfiles = getResponsibleProfiles(ownerSource.names, ownerSource.ids);
        const ownerLabel = ownerProfiles.length ? escapeHtml(ownerProfiles.map((profile) => profile.name).join(', ')) : 'Sin responsables';
        const ownerBubbles = renderResponsibleBadges(ownerSource.names, ownerSource.ids, {
          limit: 2,
          bubbleClass: 'inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-900 text-[10px] font-extrabold text-white overflow-hidden',
          wrapperClass: 'flex items-center gap-1.5 min-w-0',
          extraClass: 'text-xs font-bold text-slate-500',
          emptyHtml: '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-900 text-[10px] font-extrabold text-white">SR</span>'
        });

        return `<tr class="transition-colors hover:bg-slate-50">
          <td class="px-4 py-4 min-w-[22rem]">
            <div class="flex items-center gap-2.5 whitespace-nowrap">
              <div class="h-8 w-8 rounded-full bg-slate-900 text-white text-xs font-bold grid place-content-center flex-shrink-0">${initial}</div>
              <div class="min-w-0">
                <button type="button" onclick="openArchivedProjectDetails('${String(p.id).replace(/'/g, "\\'")}')" class="truncate font-medium text-slate-900 hover:underline">${title}</button>
              </div>
            </div>
          </td>
          <td class="px-4 py-4 min-w-[12rem] whitespace-nowrap text-sm font-semibold text-slate-700">${client}</td>
          <td class="px-4 py-4">
            ${getTaskPriorityBadge(priority)}
          </td>
          <td class="px-4 py-4 min-w-[12rem]">
            <div class="flex items-center gap-2">
              <div class="h-2.5 w-24 rounded-full bg-slate-200 overflow-hidden">
                <div class="${animateProgressBarsOnce ? 'progress-fill-live ' : ''}h-2.5 rounded-full bg-[#101729]" style="width:${prog}%"></div>
              </div>
              <span class="font-bold text-slate-700">${prog}%</span>
            </div>
          </td>
          <td class="px-4 py-4 min-w-[12rem]">
            <div class="min-w-0" title="${ownerLabel}">${ownerBubbles}</div>
          </td>
          <td class="px-4 py-4 whitespace-nowrap text-slate-700">${dueLabel}</td>
          <td class="px-4 py-4 whitespace-nowrap">
            <button type="button" onclick="restoreProject('${p.id}')" class="inline-flex h-8 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition-colors">
              <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Restaurar
            </button>
          </td>
        </tr>`;
      }).join('');
    }

    function getListFilterOptions(type) {
      if (type === 'priority') return LIST_PRIORITY_OPTIONS;
      if (type === 'date') return LIST_DATE_OPTIONS;
      if (type === 'sort') return LIST_SORT_OPTIONS;
      return [];
    }

    function getListFilterValue(type) {
      if (type === 'priority') return listFilterPriority;
      if (type === 'date') return listFilterDate;
      if (type === 'sort') return listFilterSort;
      return '';
    }

    function getListFilterDisplayLabel(type) {
      const options = getListFilterOptions(type);
      const current = String(getListFilterValue(type));
      const selected = options.find((opt) => String(opt.value) === current);
      const fallback = type === 'priority' ? 'Prioridad' : (type === 'date' ? 'Fecha' : 'Orden');
      if (!selected) return fallback;
      if (type === 'priority') return selected.value ? `Prioridad: ${selected.label}` : 'Prioridad';
      if (type === 'date') return selected.value !== 'all' ? `Fecha: ${selected.label}` : 'Fecha';
      return selected.value !== 'newest' ? `Orden: ${selected.label}` : 'Orden';
    }

    function renderListFilterChips() {
      const config = [
        { type: 'priority', buttonId: 'listFilterPriorityBtn', labelId: 'listFilterPriorityLabel' },
        { type: 'date', buttonId: 'listFilterDateBtn', labelId: 'listFilterDateLabel' },
        { type: 'sort', buttonId: 'listFilterSortBtn', labelId: 'listFilterSortLabel' },
      ];

      config.forEach(({ type, buttonId, labelId }) => {
        const button = document.getElementById(buttonId);
        const label = document.getElementById(labelId);
        if (!button || !label) return;
        label.textContent = getListFilterDisplayLabel(type);
        const active = type === 'date'
          ? listFilterDate !== 'all'
          : (type === 'sort' ? listFilterSort !== 'newest' : !!getListFilterValue(type));
        button.classList.toggle('border-slate-900', active);
        button.classList.toggle('text-slate-900', active);
        button.classList.toggle('border-slate-200', !active);
        button.classList.toggle('text-slate-700', !active);
      });
    }

    function renderListFilterDropdownOptions(type) {
      const suffix = type.charAt(0).toUpperCase() + type.slice(1);
      const container = document.getElementById(`listFilter${suffix}Options`);
      if (!container) return;
      const search = String(listFilterSearch[type] || '').trim().toLowerCase();
      const selected = String(getListFilterValue(type));
      const options = getListFilterOptions(type).filter((opt) => String(opt.label || '').toLowerCase().includes(search));

      if (!options.length) {
        container.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Sin resultados</div>';
        return;
      }

      container.innerHTML = options.map((opt) => {
        const isSelected = String(opt.value) === selected;
        const safeType = type.replace(/'/g, '');
        const safeValue = String(opt.value ?? '').replace(/'/g, "\\'");
        return `<button type="button" onclick="setListFilterValue('${safeType}', '${safeValue}')" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm transition-colors ${isSelected ? 'bg-[#101729] font-extrabold text-white' : 'text-slate-700 hover:bg-[#ecfe88]'}">${escapeHtml(String(opt.label || ''))}${isSelected ? '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : ''}</button>`;
      }).join('');
    }

    function closeListFilterDropdowns() {
      ['priority', 'date', 'sort'].forEach((type) => {
        const suffix = type.charAt(0).toUpperCase() + type.slice(1);
        const menu = document.getElementById(`listFilter${suffix}Menu`);
        if (menu) menu.classList.add('hidden');
      });
      listFilterOpenMenu = null;
    }

    function toggleListFilterDropdown(type) {
      const suffix = type.charAt(0).toUpperCase() + type.slice(1);
      const menu = document.getElementById(`listFilter${suffix}Menu`);
      if (!menu) return;
      if (listFilterOpenMenu === type) {
        closeListFilterDropdowns();
        return;
      }
      closeListFilterDropdowns();
      renderListFilterDropdownOptions(type);
      menu.classList.remove('hidden');
      listFilterOpenMenu = type;
      const searchInput = document.getElementById(`listFilter${suffix}Search`);
      if (searchInput) searchInput.focus();
    }

    function updateListFilterOptions(type, query) {
      listFilterSearch[type] = String(query || '');
      renderListFilterDropdownOptions(type);
    }

    function setListFilterValue(type, value) {
      if (type === 'priority') listFilterPriority = String(value || '');
      if (type === 'date') listFilterDate = String(value || 'all');
      if (type === 'sort') listFilterSort = String(value || 'newest');
      closeListFilterDropdowns();
      renderProjectListView(projects);
      renderQuickActionsStatus('lista');
    }

    function clearListFilters() {
      listFilterPriority = '';
      listFilterDate = 'all';
      listFilterSort = 'newest';
      listProjectSearchQuery = '';
      listFilterSearch.priority = '';
      listFilterSearch.date = '';
      listFilterSearch.sort = '';
      const prioritySearch = document.getElementById('listFilterPrioritySearch');
      const dateSearch = document.getElementById('listFilterDateSearch');
      const listSearchInput = document.getElementById('listProjectSearchInput');
      if (prioritySearch) prioritySearch.value = '';
      if (dateSearch) dateSearch.value = '';
      if (listSearchInput) listSearchInput.value = '';
      closeListFilterDropdowns();
      renderProjectListView(projects);
      renderQuickActionsStatus('lista');
    }

    function getProjectCreatedTime(project) {
      const raw = project?.created_at || project?.updated_at || project?.inicio || project?.vencimiento || null;
      if (!raw) return 0;
      const normalized = /^\d{4}-\d{2}-\d{2}$/.test(String(raw)) ? `${raw}T12:00:00` : raw;
      const timestamp = new Date(normalized).getTime();
      return Number.isNaN(timestamp) ? 0 : timestamp;
    }

    function filterProjectListData(list) {
      if (!Array.isArray(list)) return [];
      const now = new Date();
      now.setHours(0, 0, 0, 0);
      const plus7 = new Date(now);
      plus7.setDate(plus7.getDate() + 7);
      const plus30 = new Date(now);
      plus30.setDate(plus30.getDate() + 30);

      const filtered = list.filter((p) => {
        const listQuery = listProjectSearchQuery.trim();
        if (listQuery) {
          const haystack = [
            String(p?.titulo || ''),
            String(p?.cliente || ''),
            String(p?.etapa || ''),
            String(getEffectiveProjectPriority(p)),
          ].join(' ').toLowerCase();
          if (!haystack.includes(listQuery)) return false;
        }

        const projectPriority = getEffectiveProjectPriority(p);
        const due = p.vencimiento ? new Date(`${p.vencimiento}T12:00:00`) : null;
        const hasDue = !!(due && !Number.isNaN(due.getTime()));

        if (listFilterPriority && projectPriority !== listFilterPriority) return false;

        if (listFilterDate === 'no-date') return !hasDue;
        if (!hasDue) return listFilterDate === 'all';

        const dueDay = new Date(due);
        dueDay.setHours(0, 0, 0, 0);

        if (listFilterDate === 'overdue') return dueDay < now;
        if (listFilterDate === 'today') return dueDay.getTime() === now.getTime();
        if (listFilterDate === 'next7') return dueDay >= now && dueDay <= plus7;
        if (listFilterDate === 'next30') return dueDay >= now && dueDay <= plus30;

        return true;
      });

      return filtered.sort((a, b) => {
        const diff = getProjectCreatedTime(b) - getProjectCreatedTime(a);
        return listFilterSort === 'oldest' ? -diff : diff;
      });
    }

    function renderModalTasks(tasks) {
        const list = document.getElementById('modalTaskList');
        const project = projects.find(x => x.id === currentProjectId)
          || archivedProjects.find(x => x.id === currentProjectId)
          || null;
        const readOnly = projectModalReadOnly;
        list.innerHTML = tasks.map(t => {
            const ownerSource = getTaskOwnerSources(t, project);
            const ownerBadges = renderResponsibleBadges(ownerSource.names, ownerSource.ids, {
              limit: 3,
              bubbleClass: 'w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-[9px] font-bold flex items-center justify-center overflow-hidden',
              wrapperClass: 'flex items-center gap-1 shrink-0',
              extraClass: 'text-[10px] text-slate-500 font-semibold',
              emptyHtml: '<span class="text-slate-500 shrink-0">Sin encargados</span>'
            });
            return `
            <div ${readOnly ? '' : `onclick="openTaskModal('${t.id}')"`} class="group px-1 py-2 ${readOnly ? '' : 'cursor-pointer'}">
              <div class="flex items-center gap-3">
                <button ${readOnly ? 'disabled' : `onclick="event.stopPropagation(); toggleTask('${t.id}')"`} class="flex-none w-5 h-5 rounded border ${t.done ? 'bg-lime-500 border-lime-500 text-white' : 'border-slate-300 text-transparent'} flex items-center justify-center transition-colors ${readOnly ? 'opacity-70 cursor-default' : ''}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </button>
                <div class="flex-1 min-w-0">
                  <div class="text-[13px] font-bold ${t.done ? 'text-slate-400 line-through' : 'text-slate-800'} truncate leading-tight">${t.texto}</div>
                  <div class="mt-1 flex flex-wrap items-center gap-2 text-[10px]">
                    ${ownerBadges}
                    <div class="flex items-center gap-1 text-slate-600 shrink-0">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      <span>${formatTaskDateRange(t)}</span>
                    </div>
                    ${getTaskPriorityBadge(getEffectiveTaskPriority(t, project), 'xs')}
                  </div>
                </div>
                ${readOnly ? '' : `<div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                  <button onclick="event.stopPropagation(); openTaskModal('${t.id}')" class="w-7 h-7 rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center" title="Ver tarea">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                  </button>
                  <button onclick="event.stopPropagation(); toggleTaskTimer('${currentProjectId}', '${t.id}')" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-lime-200 bg-lime-50 px-2.5 text-xs font-extrabold text-slate-800 hover:bg-lime-100" title="Iniciar temporizador">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Iniciar timer</span>
                  </button>
                  <button onclick="event.stopPropagation(); deleteTask('${t.id}')" class="w-7 h-7 rounded-full border border-rose-200 bg-white text-rose-500 hover:bg-rose-50 flex items-center justify-center" title="Eliminar tarea">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                  </button>
                </div>`}
              </div>
              <div class="mt-2 border-b border-slate-200"></div>
            </div>
          `;
          }).join('');
        
        // Update progress bar
        const total = tasks.length;
        const done = tasks.filter(t => t.done).length;
        const pct = total === 0 ? 0 : (done / total) * 100;
        const progressEl = document.getElementById('modalTaskProgress');
        if (progressEl) progressEl.classList.toggle('progress-fill-live', animateProgressBarsOnce);
        progressEl.style.width = `${pct}%`;
        progressEl.style.backgroundColor = progressBarColor(Math.round(pct));
        const pctLabel = document.getElementById('modalTaskProgressLabel');
        if (pctLabel) pctLabel.innerText = `${Math.round(pct)}%`;

        if (currentTaskId) {
          const exists = tasks.some(t => t.id === currentTaskId);
          if (!exists) {
            closeTaskModal();
          } else {
            const fresh = tasks.find(t => t.id === currentTaskId);
            renderTaskDetail(fresh);
          }
        }
    }

    async function addTask(options = {}) {
      if (projectModalReadOnly) return;
        if (taskAddInFlight) return;
        const { refocus = true } = options;
        const input = document.getElementById('newTaskInput');
        const text = input?.value.trim() || '';
        if (!text || !currentProjectId) return;
        taskAddInFlight = true;
        
      try {
        const res = await fetch('/api/proyectos/tareas/agregar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({
          id: currentProjectId,
          texto: text,
          board_stage: 'Por hacer',
        })
        });
        const data = await res.json();
        if (data.ok) {
            input.value = '';
            renderModalTasks(data.item.tareas || []);
            // Update local projects array to keep in sync without reload
            const p = projects.find(x => x.id === currentProjectId);
            if (p) p.tareas = data.item.tareas;
            if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
              renderProjectBoard(currentProjectId);
            }

            if (refocus) {
              setTimeout(() => input?.focus(), 0);
            }
        }
      } finally {
        taskAddInFlight = false;
      }
    }
    
    async function toggleTask(taskId, projectId = currentProjectId) {
      if (projectModalReadOnly) return;
        if (!projectId) return;
        const res = await fetch('/api/proyectos/tareas/toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
            body: JSON.stringify({id: projectId, tarea_id: taskId})
        });
        const data = await res.json();
        if (data.ok) {
            animateProgressBarsOnce = true;
            const p = projects.find(x => String(x.id) === String(projectId));
            if (p) p.tareas = data.item.tareas || [];
            if (String(currentProjectId || '') === String(projectId)) {
              renderModalTasks(data.item.tareas || []);
            }
            if (String(currentTaskId || '') === String(taskId)) {
              const refreshed = (data.item.tareas || []).find(t => String(t.id) === String(taskId));
              if (refreshed) renderTaskDetail(refreshed, { preserveState: true });
            }
            renderKanban(projects);
            renderGlobalTasksView(projects);
            renderProjectListView(projects);
            animateProgressBarsOnce = false;
        }
    }

    function openTaskModal(taskId) {
      if (projectModalReadOnly) return;
      currentTaskId = taskId;
      currentTaskModalEditing = true;
      currentTaskEditingNoteId = null;
      currentTaskModalTab = 'info';
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return;
      const task = (p.tareas || []).find(t => t.id === taskId);
      if (!task) return;
      renderTaskDetail(task);
      document.getElementById('taskDetailModal')?.classList.remove('hidden');
      setTaskModalTab('info');
    }

    function closeTaskModal() {
      if (taskTimerInterval) {
        clearInterval(taskTimerInterval);
        taskTimerInterval = null;
      }
      if (currentProjectId && currentTaskId) {
        const pendingDesc = getCompactDescValue('taskModalDescription');
        if (typeof pendingDesc === 'string') {
          clearTimeout(taskDescAutosaveTimer);
          saveTaskDescriptionAutosave(pendingDesc);
        }
      }
      currentTaskId = null;
      currentTaskModalEditing = true;
      currentTaskEditingNoteId = null;
      currentEditingSubtaskId = null;
      currentTaskModalTab = 'info';
      document.getElementById('taskDetailModal')?.classList.add('hidden');
      document.getElementById('taskOwnerSearchResults')?.classList.add('hidden');
    }

    function getCurrentTask() {
      if (!currentProjectId || !currentTaskId) return null;
      const p = projects.find(x => x.id === currentProjectId);
      if (!p) return null;
      return (p.tareas || []).find(t => t.id === currentTaskId) || null;
    }

    function parseTaskOwnerIds() {
      const raw = String(document.getElementById('taskModalOwnerIds')?.value || '');
      return raw.split(',').map(v => v.trim()).filter(Boolean);
    }

    function renderTaskOwners(names = [], ids = []) {
      const container = document.getElementById('taskOwnersList');
      const hiddenIds = document.getElementById('taskModalOwnerIds');
      if (!container || !hiddenIds) return;

      const cleanNames = Array.isArray(names) ? names.filter(Boolean) : [];
      const cleanIds = Array.isArray(ids) ? ids.filter(Boolean) : [];
      hiddenIds.value = cleanIds.join(',');

      if (!cleanNames.length) {
        container.innerHTML = '<div class="h-11 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 flex items-center text-xs text-slate-500">Sin encargados seleccionados.</div>';
        return;
      }

      container.innerHTML = cleanNames.map((name, idx) => {
        const initials = escapeHtml(String(name).substring(0, 2).toUpperCase());
        const label = escapeHtml(String(name));
        return `<div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">${initials}</div>
            <div class="text-sm font-semibold text-slate-700 truncate">${label}</div>
          </div>
          ${currentTaskModalEditing ? `<button type="button" onclick="removeTaskOwner(${idx})" class="text-slate-400 hover:text-rose-500" title="Quitar encargado">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>` : '<span class="text-xs font-semibold text-slate-400">Asignado</span>'}
        </div>`;
      }).join('');
    }

    async function searchTaskOwners(query = '', immediate = false) {
      if (!currentTaskModalEditing) return;
      const box = document.getElementById('taskOwnerSearchResults');
      if (!box) return;

      const q = String(query || '').trim();
      if (!q) {
        box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Escribe para buscar usuarios</div>';
        box.classList.remove('hidden');
        return;
      }

      if (taskOwnerSearchDebounce) clearTimeout(taskOwnerSearchDebounce);

      const run = async () => {
        if (taskOwnerSearchAbort) taskOwnerSearchAbort.abort();
        taskOwnerSearchAbort = new AbortController();
        box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Buscando usuarios...</div>';
        box.classList.remove('hidden');

        try {
          const res = await fetch('/api/proyectos/responsables/search?q=' + encodeURIComponent(q), {
            signal: taskOwnerSearchAbort.signal,
          });
          const json = await res.json().catch(() => ({data: []}));
          const list = Array.isArray(json.data) ? json.data : [];

          if (!list.length) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No se encontraron usuarios</div>';
            box.classList.remove('hidden');
            return;
          }

          box.innerHTML = list.map(u => {
            const safeId = String(u.id).replace(/'/g, "\\'");
            const safeName = String(u.name || '').replace(/'/g, "\\'");
            const initials = escapeHtml(String(u.name || 'US').substring(0, 2).toUpperCase());
            const name = escapeHtml(u.name || 'Usuario');
            const email = escapeHtml(u.email || '');
            return `<button type="button" class="w-full text-left px-3 py-2.5 hover:bg-slate-50 border-b border-slate-100 last:border-b-0" onclick="addTaskOwnerFromCatalog('${safeId}', '${safeName}')">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">${initials}</div>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-700 truncate">${name}</div>
                  <div class="text-[11px] text-slate-400 truncate">${email}</div>
                </div>
              </div>
            </button>`;
          }).join('');
          box.classList.remove('hidden');
        } catch (error) {
          if (error.name === 'AbortError') return;
          box.innerHTML = '<div class="px-3 py-2 text-xs text-rose-500">No se pudo buscar usuarios</div>';
          box.classList.remove('hidden');
        }
      };

      if (immediate) {
        run();
        return;
      }

      taskOwnerSearchDebounce = setTimeout(run, 220);
    }

    function addTaskOwnerFromCatalog(userId, userName) {
      if (!currentTaskModalEditing) return;
      const task = getCurrentTask();
      if (!task) return;
      const names = Array.isArray(task.owners) ? [...task.owners] : [];
      const ids = Array.isArray(task.owner_ids) ? [...task.owner_ids] : [];

      if (!ids.includes(userId) && !names.includes(userName)) {
        ids.push(userId);
        names.push(userName);
      }

      task.owners = names;
      task.owner_ids = ids;
      renderTaskOwners(names, ids);

      const input = document.getElementById('taskOwnerSearchInput');
      const box = document.getElementById('taskOwnerSearchResults');
      if (input) input.value = '';
      if (box) box.classList.add('hidden');
      queueTaskDetailsAutosave(150);
    }

    function removeTaskOwner(index) {
      if (!currentTaskModalEditing) return;
      const task = getCurrentTask();
      if (!task) return;
      const names = Array.isArray(task.owners) ? [...task.owners] : [];
      const ids = Array.isArray(task.owner_ids) ? [...task.owner_ids] : [];
      names.splice(index, 1);
      if (ids.length > index) ids.splice(index, 1);
      task.owners = names;
      task.owner_ids = ids;
      renderTaskOwners(names, ids);
      queueTaskDetailsAutosave(150);
    }

    function initSubtaskDuePicker() {
      const input = document.getElementById('editSubtaskDueDate');
      if (!input) return;
      ensureFlatpickrAssets(() => {
        if (subtaskDuePicker) {
          subtaskDuePicker.destroy();
          subtaskDuePicker = null;
        }
        subtaskDuePicker = window.flatpickr('#editSubtaskDueDate', {
          dateFormat: 'Y-m-d',
          altInput: true,
          altInputClass: 'h-7 w-[4.35rem] max-w-[4.35rem] border-0 bg-transparent p-0 text-[11px] font-extrabold text-slate-600 placeholder:text-slate-400 focus:ring-0 cursor-pointer',
          altFormat: 'd/m/Y',
          disableMobile: true,
          closeOnSelect: false,
          locale: 'es',
          onOpen: () => markCalendarInteraction(1000),
          onChange: () => {
            setSubtaskAutosaveStatus('Guardando...');
            saveSubtaskDetails({ closeAfter: false, rerender: false });
          },
          onReady: (_, __, instance) => {
            if (instance.altInput) instance.altInput.style.width = '4.35rem';
          },
        });
      });
    }

    function initTaskDatePickers() {
      ensureFlatpickrAssets(() => {
        if (!taskStartPicker) {
          taskStartPicker = window.flatpickr('#taskModalStart', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altInputClass: 'w-full h-11 rounded-xl border border-slate-200 bg-slate-50 pl-9 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500 cursor-pointer',
            altFormat: 'd M, Y',
            disableMobile: true,
            locale: 'es',
            onChange: () => queueTaskDetailsAutosave(150),
          });
        }

        if (!taskEndPicker) {
          taskEndPicker = window.flatpickr('#taskModalEnd', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altInputClass: 'w-full h-11 rounded-xl border border-slate-200 bg-slate-50 pl-9 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500 cursor-pointer',
            altFormat: 'd M, Y',
            disableMobile: true,
            locale: 'es',
            onChange: () => queueTaskDetailsAutosave(150),
          });
        }

        applyTaskModalEditState();
      });
    }

    function setTaskDateValues(startDate, endDate) {
      if (taskStartPicker) {
        taskStartPicker.setDate(startDate || null, false);
      } else {
        const startEl = document.getElementById('taskModalStart');
        if (startEl) startEl.value = startDate || '';
      }

      if (taskEndPicker) {
        taskEndPicker.setDate(endDate || null, false);
      } else {
        const endEl = document.getElementById('taskModalEnd');
        if (endEl) endEl.value = endDate || '';
      }
    }

    function setProjectModalTab(tab = 'info') {
      currentProjectModalTab = ['info', 'notes'].includes(tab) ? tab : 'info';
      const infoTab = document.getElementById('projectModalInfoTab');
      const tasksTab = document.getElementById('projectModalTasksTab');
      const notesTab = document.getElementById('projectModalNotesTab');
      if (infoTab) infoTab.classList.toggle('hidden', currentProjectModalTab !== 'info');
      if (tasksTab) tasksTab.classList.add('hidden');
      if (notesTab) notesTab.classList.toggle('hidden', currentProjectModalTab !== 'notes');

      document.querySelectorAll('.project-detail-tab').forEach((btn) => {
        const isActive = btn.getAttribute('data-project-tab') === currentProjectModalTab;
        btn.classList.toggle('bg-slate-900', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-sm', isActive);
        btn.classList.toggle('text-slate-600', !isActive);
      });
    }

    function setTaskModalTab(tab = 'info') {
      currentTaskModalTab = ['info', 'notes'].includes(tab) ? tab : 'info';
      const infoTab = document.getElementById('taskModalInfoTab');
      const notesTab = document.getElementById('taskModalNotesTab');
      if (infoTab) infoTab.classList.toggle('hidden', currentTaskModalTab !== 'info');
      if (notesTab) notesTab.classList.toggle('hidden', currentTaskModalTab !== 'notes');

      document.querySelectorAll('.task-detail-tab').forEach((btn) => {
        const isActive = btn.getAttribute('data-task-tab') === currentTaskModalTab;
        btn.classList.toggle('bg-slate-900', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-sm', isActive);
        btn.classList.toggle('text-slate-600', !isActive);
      });
    }
    window.setTaskModalTab = setTaskModalTab;
    window.handleTaskPriorityChange = handleTaskPriorityChange;

    function setTaskDatePickerEditable(picker, inputId, editable) {
      const input = document.getElementById(inputId);
      if (input) input.disabled = !editable;
      if (picker) {
        picker.set('clickOpens', editable);
        if (picker.altInput) {
          picker.altInput.disabled = !editable;
          picker.altInput.classList.toggle('cursor-pointer', editable);
          picker.altInput.classList.toggle('opacity-70', !editable);
        }
      }
    }

    function applyTaskModalEditState() {
      const title = document.getElementById('taskModalTitle');
      const description = document.getElementById('taskModalDescription');
      const priority = document.getElementById('taskModalPriority');
      const ownerSearch = document.getElementById('taskOwnerSearchInput');
      const ownerSearchWrap = document.getElementById('taskOwnerSearchWrap');
      const subtaskComposer = document.getElementById('taskSubtaskComposer');
      const primaryBtn = document.getElementById('taskModalPrimaryBtn');
      const primaryIcon = document.getElementById('taskModalPrimaryIcon');
      currentTaskModalEditing = true;

      if (title) {
        title.readOnly = false;
        title.classList.remove('cursor-default');
      }
      if (description) {
        setCompactDescEditable('taskModalDescription', true);
        description.classList.remove('cursor-default');
      }
      if (priority) {
        priority.disabled = false;
        priority.classList.remove('opacity-70');
      }
      refreshTaskModalPriorityUI();
      if (ownerSearch) {
        ownerSearch.disabled = false;
      }
      if (ownerSearchWrap) {
        ownerSearchWrap.classList.remove('hidden');
      }
      if (subtaskComposer) {
        subtaskComposer.classList.remove('hidden');
      }

      setTaskDatePickerEditable(taskStartPicker, 'taskModalStart', true);
      setTaskDatePickerEditable(taskEndPicker, 'taskModalEnd', true);

      if (primaryBtn) {
        primaryBtn.title = 'Guardar cambios';
      }
      if (primaryIcon) {
        primaryIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
      }

      const task = getCurrentTask();
      if (task) {
        renderTaskOwners(task.owners || [], task.owner_ids || []);
        renderTaskSubtasks(task.subtasks || []);
      }
    }

    function positionTaskAiSupportPanel() {
      const panel = document.getElementById('taskAiSupportPanel');
      const trigger = document.getElementById('taskAiSupportTrigger');
      if (!panel || !trigger || panel.classList.contains('hidden')) return;
      const rect = trigger.getBoundingClientRect();
      const gap = 10;
      const panelWidth = Math.min(320, window.innerWidth - 24);
      panel.style.width = `${panelWidth}px`;
      const left = Math.max(12, Math.min(window.innerWidth - panelWidth - 12, rect.right - panelWidth));
      const panelHeight = panel.offsetHeight || 320;
      const topAbove = rect.top - panelHeight - gap;
      const top = topAbove >= 12
        ? topAbove
        : Math.min(window.innerHeight - panelHeight - 12, rect.bottom + gap);
      panel.style.left = `${left}px`;
      panel.style.top = `${Math.max(12, top)}px`;
    }

    function toggleTaskAiSupport(forceState = null) {
      const panel = document.getElementById('taskAiSupportPanel');
      if (!panel) return;
      const shouldShow = forceState === null ? panel.classList.contains('hidden') : !!forceState;
      panel.classList.toggle('hidden', !shouldShow);
      if (shouldShow) {
        requestAnimationFrame(positionTaskAiSupportPanel);
        setTimeout(() => document.getElementById('taskAiSupportInput')?.focus(), 0);
      }
    }

    function resetTaskAiSupport() {
      const panel = document.getElementById('taskAiSupportPanel');
      const messages = document.getElementById('taskAiSupportMessages');
      const input = document.getElementById('taskAiSupportInput');
      if (panel) panel.classList.add('hidden');
      if (input) input.value = '';
      if (messages) {
        messages.innerHTML = '<div class="rounded-xl bg-white px-3 py-2 text-[11px] font-semibold leading-snug text-slate-500 shadow-sm">Dime cómo ajustar el checklist: agregar, reescribir o crear tareas.</div>';
      }
    }

    function appendTaskAiSupportMessage(role, text) {
      const box = document.getElementById('taskAiSupportMessages');
      if (!box) return;
      const isUser = role === 'user';
      const node = document.createElement('div');
      node.className = `rounded-xl px-3 py-2 text-xs font-semibold shadow-sm ${isUser ? 'ml-8 bg-slate-900 text-white' : 'mr-8 bg-white text-slate-600'}`;
      node.textContent = text;
      box.appendChild(node);
      box.scrollTop = box.scrollHeight;
    }

    function getSubtaskAnimationId(subtask) {
      return String(subtask?.id || subtask?.texto || '').trim();
    }

    function setTaskAiChecklistWorking(working = false) {
      taskAiChecklistWorking = !!working;
      const list = document.getElementById('taskSubtasksList');
      list?.classList.toggle('is-ai-working', taskAiChecklistWorking);
    }

    async function sendTaskAiSupport() {
      if (!currentProjectId || !currentTaskId) return;
      const input = document.getElementById('taskAiSupportInput');
      const button = document.getElementById('taskAiSupportSendBtn');
      const message = String(input?.value || '').trim();
      if (!message) return;
      const previousTask = getCurrentTask();
      const previousSubtaskIds = new Set((previousTask?.subtasks || []).map(getSubtaskAnimationId).filter(Boolean));

      appendTaskAiSupportMessage('user', message);
      if (input) input.value = '';
      if (button) {
        button.disabled = true;
        button.textContent = 'Pensando...';
      }
      appendTaskAiSupportMessage('assistant', 'Estoy preparando cambios para el checklist...');
      setTaskAiChecklistWorking(true);

      try {
        const response = await fetch('/api/proyectos/tareas/ia-apoyo', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
          body: JSON.stringify({ id: currentProjectId, tarea_id: currentTaskId, message }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
          appendTaskAiSupportMessage('assistant', data.message || 'No pude aplicar cambios con IA.');
          setTaskAiChecklistWorking(false);
          return;
        }

        const updatedTasks = data.item.tareas || [];
        const updatedTask = updatedTasks.find((task) => String(task.id) === String(currentTaskId));
        taskAiAnimatedSubtaskIds = new Set((updatedTask?.subtasks || [])
          .map(getSubtaskAnimationId)
          .filter((id) => id && !previousSubtaskIds.has(id)));

        const p = projects.find((item) => String(item.id) === String(currentProjectId));
        if (p) {
          p.tareas = updatedTasks;
          p.archived_tasks = data.item.archived_tasks || p.archived_tasks || [];
        }
        syncCurrentProjectTasks(updatedTasks, { rerenderTaskDetail: true });
        if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
          renderProjectBoard(currentBoardProjectId);
        }
        appendTaskAiSupportMessage('assistant', data.message || 'Listo, apliqué los cambios.');
        setTimeout(() => {
          setTaskAiChecklistWorking(false);
          taskAiAnimatedSubtaskIds.clear();
        }, 950);
      } catch (error) {
        console.error(error);
        appendTaskAiSupportMessage('assistant', 'No pude conectar con el apoyo de IA.');
        setTaskAiChecklistWorking(false);
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = 'Enviar';
        }
        input?.focus();
      }
    }

    function toggleTaskModalEditMode(forceState = null) {
      currentTaskModalEditing = true;
      saveTaskDetails();
    }

    function renderTaskDetail(task, options = {}) {
      if (!task) return;
      const preserveState = !!options.preserveState;
      if (!preserveState) {
        currentTaskModalEditing = true;
        currentTaskEditingNoteId = null;
        currentEditingSubtaskId = null;
        resetTaskAiSupport();
      }
      const title = document.getElementById('taskModalTitle');
      const description = document.getElementById('taskModalDescription');
      const start = document.getElementById('taskModalStart');
      const end = document.getElementById('taskModalEnd');
      const prio = document.getElementById('taskModalPriority');
      const meta = document.getElementById('taskModalMeta');
      const doneBtn = document.getElementById('taskModalDoneBtn');
      if (title) title.value = task.texto || '';
      if (description && !isCompactDescEditorFocused('taskModalDescription')) {
        setCompactDescValue('taskModalDescription', task.descripcion || '');
      }
      if (doneBtn) {
        doneBtn.classList.toggle('border-lime-300', !!task.done);
        doneBtn.classList.toggle('bg-lime-200', !!task.done);
        doneBtn.classList.toggle('text-slate-950', !!task.done);
        doneBtn.classList.toggle('border-slate-300', !task.done);
        doneBtn.classList.toggle('bg-white', !task.done);
        doneBtn.classList.toggle('text-transparent', !task.done);
      }
      initTaskDatePickers();
      if (start || end) {
        setTaskDateValues(task.start_date || '', task.end_date || task.due_date || '');
      }
      const project = projects.find(x => x.id === currentProjectId) || archivedProjects.find(x => x.id === currentProjectId) || null;
      const projectLabel = document.getElementById('taskModalProjectLabel');
      const stageLabel = document.getElementById('taskModalStageLabel');
      if (projectLabel) projectLabel.textContent = project?.titulo || 'Proyecto';
      if (stageLabel) stageLabel.textContent = String(task.board_stage || 'Por hacer').trim() || 'Por hacer';
      if (prio) {
        prio.value = getEffectiveTaskPriority(task, project);
        refreshTaskModalPriorityUI();
      }
      renderTaskOwners(task.owners || [], task.owner_ids || []);
      const ownerInput = document.getElementById('taskOwnerSearchInput');
      if (ownerInput) ownerInput.value = '';
      document.getElementById('taskOwnerSearchResults')?.classList.add('hidden');

      if (meta) {
        const status = task.done ? 'Completada' : 'Pendiente';
        const priorityText = getEffectiveTaskPriority(task, project);
        const startText = formatTaskInlineDate(task.start_date || '');
        const endText = formatTaskInlineDate(task.end_date || task.due_date || '');
        const ownersText = (task.owners || []).length ? escapeHtml((task.owners || []).join(', ')) : 'Sin encargados';
        meta.innerHTML = `<div class="space-y-1.5 text-sm">
          <div><span class="font-extrabold text-lime-300">Estado:</span> <span class="font-bold text-white">${status}</span></div>
          <div class="flex items-center gap-2"><span class="font-extrabold text-lime-300">Prioridad:</span> ${getTaskPriorityBadge(priorityText)}</div>
          <div><span class="font-extrabold text-lime-300">Fecha inicio:</span> <span class="font-bold text-white">${startText}</span></div>
          <div><span class="font-extrabold text-lime-300">Fecha finalización:</span> <span class="font-bold text-white">${endText}</span></div>
          <div><span class="font-extrabold text-lime-300">Encargados:</span> <span class="font-bold text-white">${ownersText}</span></div>
        </div>`;
      }

      renderTaskSubtasks(task.subtasks || []);
      renderTaskFiles(task.files || []);
      renderTaskTimeHistory(task.id);
      updateTaskTimerPanels(task);
      renderTaskPipelineNotes(task);
      if (!preserveState) {
        const noteInput = document.getElementById('taskModalNewNoteInput');
        if (noteInput) noteInput.value = '';
      }
      applyTaskModalEditState();
    }

    function renderTaskSubtasks(subtasks) {
      const list = document.getElementById('taskSubtasksList');
      const progress = document.getElementById('taskSubtaskProgress');
      const progressLabel = document.getElementById('taskSubtaskProgressLabel');
      if (!list) return;
      const total = Array.isArray(subtasks) ? subtasks.length : 0;
      const done = (Array.isArray(subtasks) ? subtasks : []).filter((item) => !!item.done).length;
      const pct = total ? Math.round((done / total) * 100) : 0;
      list.classList.toggle('is-ai-working', taskAiChecklistWorking);
      if (progress) progress.classList.toggle('progress-fill-live', animateProgressBarsOnce);
      if (progress) progress.style.width = `${pct}%`;
      if (progressLabel) progressLabel.textContent = `${pct}%`;
      if (!subtasks.length) {
        list.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-500">No hay elementos en el checklist todavía.</div>';
        return;
      }
      if (subtaskDuePicker) {
        subtaskDuePicker.destroy();
        subtaskDuePicker = null;
      }
      const project = projects.find(x => x.id === currentProjectId) || null;
      const task = getCurrentTask();
      list.innerHTML = subtasks.map((s, index) => renderSubtaskRow(s, task, project, index)).join('');
      if (currentEditingSubtaskId) {
        setTimeout(initSubtaskDuePicker, 0);
      }
    }

    function renderSubtaskRow(subtask, task, project, index = 0) {
      const subtaskId = String(subtask.id || '');
      const safeId = subtaskId.replace(/'/g, "\\'");
      const isEditing = String(currentEditingSubtaskId || '') === subtaskId;
      if (isEditing) return renderSubtaskEditor(subtask, task, project, safeId);
      const shouldAnimate = taskAiAnimatedSubtaskIds.has(getSubtaskAnimationId(subtask));
      const animationStyle = shouldAnimate ? `style="animation-delay:${Math.min(index, 8) * 55}ms"` : '';

      const ownerBadges = renderResponsibleBadges(subtask.owners || [], subtask.owner_ids || [], {
        limit: 3,
        bubbleClass: 'w-7 h-7 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center overflow-hidden',
        wrapperClass: 'flex items-center gap-1 shrink-0',
        extraClass: 'text-[10px] text-slate-500 font-semibold',
        emptyHtml: '<span class="text-xs font-semibold text-slate-400">Sin asignar</span>'
      });
      const dueDate = subtask.due_date ? formatTaskInlineDate(subtask.due_date) : 'Sin fecha';
      const priority = getEffectiveSubtaskPriority(subtask, task, project);

      return `
        <div onclick="openSubtaskEditor('${safeId}')" ${animationStyle} class="group cursor-pointer border-b border-slate-200 px-1 py-4 last:border-b-0 hover:bg-slate-50/60 transition-colors ${shouldAnimate ? 'task-subtask-ai-enter' : ''}">
          <div class="flex items-start gap-4">
            <button type="button" onclick="event.stopPropagation(); toggleSubtask('${safeId}')" class="mt-1 w-6 h-6 rounded-md border ${subtask.done ? 'bg-lime-500 border-lime-500 text-white' : 'border-slate-300 bg-white text-transparent'} flex items-center justify-center shrink-0 transition-colors" title="Marcar / desmarcar elemento">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </button>
            <div class="min-w-0 flex-1">
              <div class="text-sm font-extrabold ${subtask.done ? 'text-slate-400 line-through' : 'text-slate-800'} truncate leading-tight">${escapeHtml(subtask.texto || 'Elemento sin nombre')}</div>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                ${ownerBadges}
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600">
                  <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  ${escapeHtml(dueDate)}
                </span>
                ${getTaskPriorityBadge(priority, 'xs')}
              </div>
            </div>
            <button type="button" onclick="event.stopPropagation(); removeSubtask('${safeId}')" class="opacity-0 group-hover:opacity-100 w-8 h-8 rounded-full border border-rose-200 bg-white text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-opacity" title="Eliminar elemento">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>`;
    }

    function renderSubtaskEditor(subtask, task, project, safeId) {
      const selectedOwners = renderSubtaskOwnerEditor(subtask);
      const priority = getEffectiveSubtaskPriority(subtask, task, project);
      const rawPriority = normalizeTaskPriority(subtask.priority || task?.priority || 'Con calma');
      const parentIsOverdue = getEffectiveTaskPriority(task || {}, project) === 'Vencido';
      const priorityControl = parentIsOverdue
        ? getTaskPriorityBadge('Vencido', 'xs')
        : renderSubtaskPriorityPicker(rawPriority || priority || 'Con calma');
      const text = escapeHtml(String(subtask.texto || ''));
      const due = escapeHtml(String(subtask.due_date || ''));
      return `
        <div data-subtask-editor class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 shadow-sm">
          <input id="editSubtaskText" type="text" value="${text}" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm font-extrabold leading-6 text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500" placeholder="Nombre del elemento" oninput="setSubtaskAutosaveStatus('Pendiente')" onkeydown="if(event.key==='Enter'){ event.preventDefault(); saveSubtaskDetails({ closeAfter: true }); }">
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <div class="relative">
              <button type="button" onclick="toggleSubtaskOwnerSearch()" class="inline-flex h-8 items-center gap-1.5 rounded-full border border-transparent px-2.5 text-xs font-extrabold text-slate-600 transition-colors hover:border-slate-200 hover:bg-white">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3M15 7a4 4 0 11-8 0 4 4 0 018 0zM3 21a6 6 0 0112 0"/></svg>
                Asignar
              </button>
              <div id="subtaskOwnerSearchPanel" class="hidden absolute left-0 top-10 z-30 w-72 rounded-xl border border-slate-200 bg-white shadow-xl">
                <input id="subtaskOwnerSearchInput" class="w-full border-0 border-b border-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 focus:ring-0" placeholder="Buscar usuario..." oninput="searchSubtaskOwners(this.value)" onfocus="searchSubtaskOwners(this.value, true)">
                <div id="subtaskOwnerSearchResults" class="max-h-56 overflow-y-auto"></div>
              </div>
            </div>
            ${priorityControl}
            <label class="relative inline-flex h-8 w-32 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-extrabold text-slate-600 shadow-sm transition-colors focus-within:border-lime-300 focus-within:ring-2 focus-within:ring-lime-100">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <input id="editSubtaskDueDate" type="text" value="${due}" class="h-7 min-w-0 flex-1 border-0 bg-transparent p-0 text-xs font-extrabold text-slate-700 placeholder:text-slate-400 focus:ring-0" placeholder="Fecha">
            </label>
            <span id="subtaskAutosaveStatus" class="ml-auto text-[11px] font-bold text-slate-400">Autoguardado</span>
          </div>
          <div id="subtaskOwnerEditorList" class="mt-2 flex flex-wrap items-center gap-2">${selectedOwners}</div>
        </div>`;
    }

    function getEffectiveSubtaskPriority(subtask, task, project) {
      const parentPriority = getEffectiveTaskPriority(task || {}, project);
      if (parentPriority === 'Vencido') return 'Vencido';
      return normalizeTaskPriority(subtask?.priority || task?.priority || parentPriority || 'Con calma');
    }

    function subtaskPriorityClass(value) {
      const priority = normalizeTaskPriority(value);
      if (priority === 'Urgente') return 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100';
      if (priority === 'Atención') return 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100';
      return 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100';
    }

    function renderSubtaskPriorityPicker(value = 'Con calma') {
      const current = normalizeTaskPriority(value || 'Con calma');
      const option = (priority) => {
        const normalized = normalizeTaskPriority(priority);
        const isActive = normalized === current;
        const color = normalized === 'Urgente' ? 'text-rose-700 hover:bg-rose-50'
          : normalized === 'Atención' ? 'text-amber-700 hover:bg-amber-50'
          : 'text-emerald-700 hover:bg-emerald-50';
        const activeBg = isActive
          ? (normalized === 'Urgente' ? 'bg-rose-50' : normalized === 'Atención' ? 'bg-amber-50' : 'bg-emerald-50')
          : '';
        return `<button type="button" onclick="selectSubtaskPriority('${normalized}')" class="flex w-full items-center justify-between gap-2 rounded-xl px-2.5 py-1.5 text-left text-xs font-extrabold ${color} ${activeBg}">
          <span class="inline-flex items-center gap-1.5">${getTaskPriorityIcon(normalized, 'h-3 w-3 shrink-0 self-center')}${escapeHtml(normalized)}</span>
          <span class="${isActive ? '' : 'hidden'} text-slate-900">✓</span>
        </button>`;
      };
      return `<div class="relative shrink-0" data-subtask-priority-wrap>
        <input type="hidden" id="editSubtaskPriority" value="${escapeHtml(current)}">
        <button id="editSubtaskPriorityButton" type="button" onclick="toggleSubtaskPriorityMenu(event)" class="inline-flex h-8 min-w-[8.8rem] items-center justify-center gap-1.5 rounded-full border px-3 text-xs font-extrabold shadow-sm transition-colors ${subtaskPriorityClass(current)}">
          <span id="editSubtaskPriorityIcon" class="inline-flex shrink-0">${getTaskPriorityIcon(current, 'w-3.5 h-3.5 shrink-0 self-center')}</span>
          <span id="editSubtaskPriorityLabel">${escapeHtml(current || 'Prioridad')}</span>
          <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        </button>
        <div id="editSubtaskPriorityMenu" class="hidden absolute left-0 top-[calc(100%+0.35rem)] z-40 w-48 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl">
          ${option('Con calma')}
          ${option('Atención')}
          ${option('Urgente')}
        </div>
      </div>`;
    }

    function closeSubtaskPriorityMenu() {
      document.getElementById('editSubtaskPriorityMenu')?.classList.add('hidden');
    }

    function toggleSubtaskPriorityMenu(event = null) {
      event?.preventDefault?.();
      event?.stopPropagation?.();
      document.getElementById('subtaskOwnerSearchPanel')?.classList.add('hidden');
      document.getElementById('editSubtaskPriorityMenu')?.classList.toggle('hidden');
    }

    function selectSubtaskPriority(value) {
      updateSubtaskPriorityPill(value);
      closeSubtaskPriorityMenu();
      setSubtaskAutosaveStatus('Guardando...');
      saveSubtaskDetails({ closeAfter: false, rerender: false });
    }

    function updateSubtaskPriorityPill(value) {
      const normalized = normalizeTaskPriority(value || 'Con calma');
      const input = document.getElementById('editSubtaskPriority');
      const icon = document.getElementById('editSubtaskPriorityIcon');
      const label = document.getElementById('editSubtaskPriorityLabel');
      const button = document.getElementById('editSubtaskPriorityButton');
      if (input) input.value = normalized;
      if (icon) icon.innerHTML = getTaskPriorityIcon(normalized, 'w-3.5 h-3.5 shrink-0 self-center');
      if (label) label.textContent = normalized || 'Prioridad';
      if (button) {
        button.className = `inline-flex h-8 min-w-[8.8rem] items-center justify-center gap-1.5 rounded-full border px-3 text-xs font-extrabold shadow-sm transition-colors ${subtaskPriorityClass(normalized)}`;
      }
    }

    function renderSubtaskOwnerEditor(subtask) {
      const profiles = getResponsibleProfiles(subtask.owners || [], subtask.owner_ids || []);
      if (!profiles.length) {
        return '<span class="text-xs font-semibold text-slate-400">Sin asignados</span>';
      }
      return profiles.map((profile, index) => {
        const initials = escapeHtml(String(profile.name || 'US').substring(0, 2).toUpperCase());
        const name = escapeHtml(profile.name || 'Usuario');
        return `<span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-600">
          <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 text-[9px] flex items-center justify-center">${initials}</span>
          ${name}
          <button type="button" onclick="removeSubtaskOwner(${index})" class="text-slate-400 hover:text-rose-500" title="Quitar asignado">×</button>
        </span>`;
      }).join('');
    }

    function formatTaskInlineDate(value) {
      if (!value) return 'Sin fecha';
      const date = new Date(`${value}T12:00:00`);
      if (Number.isNaN(date.getTime())) return 'Sin fecha';
      return date.toLocaleDateString('es-ES');
    }

    function renderTaskPipelineNotes(task) {
      const box = document.getElementById('taskModalPipelineNotes');
      if (!box) return;

      const notes = (Array.isArray(task?.notes) ? task.notes : [])
        .slice()
        .sort((a, b) => new Date(b.updated_at || b.created_at || 0).getTime() - new Date(a.updated_at || a.created_at || 0).getTime());

      if (!notes.length) {
        box.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-500">No hay notas de pipeline para esta tarea.</div>';
        return;
      }

      box.innerHTML = notes.map((note) => {
        const author = escapeHtml(String(note.author_name || note.user || 'Usuario'));
        const created = escapeHtml(formatTaskNoteDate(note.created_at));
        const updated = note.updated_at ? `<span class="text-[11px] text-slate-400">Editada ${escapeHtml(formatTaskNoteDate(note.updated_at))}</span>` : '';
        const text = escapeHtml(String(note.texto || ''));
        return `<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
          <div class="flex items-start justify-between gap-2">
            <div class="text-xs font-bold text-slate-700">${author}</div>
            <div class="text-[11px] text-slate-500 text-right">${created}<br>${updated}</div>
          </div>
          <div class="mt-2 text-sm text-slate-700 leading-6 whitespace-pre-wrap">${text}</div>
        </div>`;
      }).join('');
    }

    function formatTaskNoteDate(value) {
      if (!value) return 'Sin fecha';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return 'Sin fecha';
      return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    }

    function renderProjectNoteTaskOptions(project) {
      const select = document.getElementById('projectNoteTaskSelect');
      if (!select) return;
      const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
      if (!tasks.length) {
        select.innerHTML = '<option value="">Sin tareas disponibles</option>';
        select.disabled = true;
        return;
      }

      select.disabled = false;
      const currentValue = select.value || '';
      select.innerHTML = tasks.map((task) => `<option value="${escapeHtml(String(task.id || ''))}">${escapeHtml(String(task.texto || 'Tarea sin nombre'))}</option>`).join('');
      const stillExists = tasks.some((task) => String(task.id || '') === currentValue);
      select.value = stillExists ? currentValue : String(tasks[0].id || '');
    }

    function toggleProjectNoteComposer(forceState = null) {
      const composer = document.getElementById('projectNoteComposer');
      const toggleBtn = document.getElementById('projectNoteToggleBtn');
      if (!composer) return;

      isProjectNoteComposerOpen = forceState === null ? !isProjectNoteComposerOpen : !!forceState;
      composer.classList.toggle('hidden', !isProjectNoteComposerOpen);

      if (toggleBtn) {
        toggleBtn.textContent = isProjectNoteComposerOpen ? 'Ocultar formulario' : 'Agregar nota';
      }

      if (isProjectNoteComposerOpen) {
        document.getElementById('projectNoteInput')?.focus();
      }
    }

    function getProjectTaskNoteEntries(project) {
      const tasks = Array.isArray(project?.tareas) ? project.tareas : [];
      return tasks.flatMap((task) => {
        const notes = Array.isArray(task?.notes) ? task.notes : [];
        return notes.map((note) => ({
          ...note,
          task_id: String(task.id || ''),
          task_name: String(task.texto || 'Tarea sin nombre'),
        }));
      }).sort((a, b) => new Date(b.updated_at || b.created_at || 0).getTime() - new Date(a.updated_at || a.created_at || 0).getTime());
    }

    function renderProjectNotes(project) {
      const list = document.getElementById('projectNotesList');
      if (!list) return;
      const items = getProjectTaskNoteEntries(project);
      if (!items.length) {
        list.innerHTML = '<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Todavía no hay notas en este proyecto.</div>';
        return;
      }

      list.innerHTML = items.map((note, index) => {
        const noteId = String(note.id || '');
        const isEditing = currentProjectEditingNoteId === noteId;
        const author = escapeHtml(String(note.author_name || 'Usuario'));
        const created = escapeHtml(formatTaskNoteDate(note.created_at));
        const updated = note.updated_at ? `<span class="text-xs text-slate-400">Editada ${escapeHtml(formatTaskNoteDate(note.updated_at))}</span>` : '';
        const text = escapeHtml(String(note.texto || ''));
        const taskName = escapeHtml(String(note.task_name || 'Tarea sin nombre'));
        return `<div class="relative pl-8">
          <div class="absolute left-3 top-0 bottom-0 w-px bg-slate-200 ${index === items.length - 1 ? 'hidden' : ''}"></div>
          <div class="absolute left-0 top-5 w-6 h-6 rounded-full border border-lime-200 bg-lime-100 text-slate-700 flex items-center justify-center text-[10px] font-bold">${index + 1}</div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div>
                <div class="text-sm font-extrabold text-slate-800">${author}</div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                  <span class="inline-flex items-center rounded-full border border-lime-200 bg-lime-50 px-2 py-0.5 font-bold text-lime-700">${taskName}</span>
                  <span>Subida ${created}</span>
                  ${updated}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" onclick="startEditProjectNote('${note.task_id}','${noteId}')" class="text-xs font-bold text-slate-500 hover:text-slate-800">Editar</button>
                <button type="button" onclick="deleteProjectNote('${note.task_id}','${noteId}')" class="text-xs font-bold text-rose-500 hover:text-rose-700">Eliminar</button>
              </div>
            </div>
            ${isEditing
              ? `<div class="space-y-3">
                  <textarea id="taskNoteEditInput-${noteId}" rows="4" class="w-full rounded-2xl border-slate-200 bg-white text-slate-900 shadow-sm focus:border-lime-500 focus:ring-lime-500">${text}</textarea>
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" onclick="cancelProjectNoteEdit()" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-white">Cancelar</button>
                    <button type="button" onclick="saveProjectNoteEdit('${note.task_id}','${noteId}')" class="px-3 py-2 rounded-xl bg-slate-900 text-sm font-bold text-white hover:bg-slate-800">Guardar</button>
                  </div>
                </div>`
              : `<div class="text-sm leading-6 text-slate-700 whitespace-pre-wrap">${text}</div>`}
          </div>
        </div>`;
      }).join('');
    }

    function renderProjectNotesPanel(project) {
      renderProjectNoteTaskOptions(project);
      renderProjectNotes(project);
      toggleProjectNoteComposer(false);
    }

    function renderTaskTimeHistory(taskId) {
      const p = projects.find(x => x.id === currentProjectId);
      const box = document.getElementById('taskTimeHistoryList');
      if (!p || !box) return;

      const logs = (p.time_logs || []).filter(l => String(l.task_id || '') === String(taskId || ''));
      if (!logs.length) {
        box.innerHTML = '<div class="text-xs text-slate-500">No hay registros de tiempo para esta tarea.</div>';
        return;
      }

      box.innerHTML = logs.slice().reverse().map((l) => {
        const start = Number(l.start || 0);
        const end = Number(l.end || 0);
        const duration = Math.max(0, (end || Math.floor(Date.now() / 1000)) - start);
        const date = start ? new Date(start * 1000).toLocaleString('es-ES') : '-';
        const user = escapeHtml(String(l.user || 'Sistema'));
        return `<div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
          <div class="flex items-center justify-between gap-2">
            <div class="text-xs font-semibold text-slate-700 truncate">${user}</div>
            <div class="text-xs font-bold text-cyan-600">${formatTimer(duration)}</div>
          </div>
          <div class="text-[11px] text-slate-500 mt-1">${date}</div>
        </div>`;
      }).join('');
    }

    function getTaskRunningLog(project, taskId) {
      const logs = project?.time_logs || [];
      const current = logs.length ? logs[logs.length - 1] : null;
      if (!current || current.end) return null;
      return String(current.task_id || '') === String(taskId || '') ? current : null;
    }

    function getTaskDisplayedSeconds(project, task) {
      if (!task) return 0;
      const base = Math.max(0, Number(task.total_seconds || 0));
      const running = getTaskRunningLog(project, task.id);
      const resetBase = getTaskTimerResetBase(project?.id || currentProjectId, task.id);
      if (!running) return Math.max(0, base - resetBase);
      const start = Number(running.start || 0);
      if (!start) return Math.max(0, base - resetBase);
      return Math.max(0, base + Math.max(0, Math.floor(Date.now() / 1000) - start) - resetBase);
    }

    function updateTaskTimerPanels(task = getCurrentTask()) {
      const project = projects.find(x => String(x.id) === String(currentProjectId));
      if (!project || !task) return;
      if (taskTimerInterval) clearInterval(taskTimerInterval);

      const btn = document.getElementById('taskTimerBtn');
      const display = document.getElementById('taskTimerDisplay');
      const invested = document.getElementById('taskTimerInvestedDisplay');
      const label = document.getElementById('taskTimerTaskLabel');
      const running = getTaskRunningLog(project, task.id);

      if (label) label.textContent = task.texto || 'Tarea actual';

      const paint = () => {
        const seconds = getTaskDisplayedSeconds(project, task);
        const timerValue = formatTimer(seconds);
        if (display) display.textContent = timerValue;
        if (invested) invested.textContent = formatInvestedDh(Math.max(0, Number(task.total_seconds || 0)));
        setTimerSaveButtonState('taskTimerSaveBtn', seconds > 0);
      };

      if (btn) {
        if (running) {
          btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg><span>Pausar</span>';
          btn.className = 'w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200';
        } else {
          btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg><span>Iniciar</span>';
          btn.className = 'w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-lime-400 text-slate-900 hover:bg-lime-500';
        }
      }

      paint();
      if (running) {
        taskTimerInterval = setInterval(paint, 1000);
      }
    }

    function syncCurrentProjectTasks(updatedTasks, options = {}) {
      const p = projects.find(x => x.id === currentProjectId);
      if (p) p.tareas = updatedTasks || [];
      renderModalTasks(updatedTasks || []);
      if (p) renderProjectNotesPanel(p);
      if (options.rerenderTaskDetail && currentTaskId) {
        const refreshed = getCurrentTask();
        if (refreshed) {
          renderTaskDetail(refreshed, { preserveState: true });
        }
      }
    }

    async function addProjectNote() {
      if (!currentProjectId) return;
      const taskSelect = document.getElementById('projectNoteTaskSelect');
      const taskId = String(taskSelect?.value || '').trim();
      if (!taskId) return;
      const input = document.getElementById('projectNoteInput');
      const texto = String(input?.value || '').trim();
      if (!texto) return;

      const res = await fetch('/api/proyectos/tareas/notas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: taskId, texto})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo agregar la nota', 'error');
        return;
      }

      if (input) input.value = '';
      currentProjectEditingNoteId = null;
      setProjectModalTab('notes');
      toggleProjectNoteComposer(false);
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      if (window.showNotification) window.showNotification('Nota agregada', 'success');
    }

    async function addTaskModalNote() {
      if (!currentProjectId || !currentTaskId) return;
      const input = document.getElementById('taskModalNewNoteInput');
      const texto = String(input?.value || '').trim();
      if (!texto) return;

      const res = await fetch('/api/proyectos/tareas/notas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, texto})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo agregar la nota', 'error');
        return;
      }

      if (input) input.value = '';
      currentProjectEditingNoteId = null;
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      if (window.showNotification) window.showNotification('Nota agregada', 'success');
    }

    function startEditProjectNote(taskId, noteId) {
      currentProjectEditingNoteId = noteId;
      const p = projects.find(x => x.id === currentProjectId);
      if (p) renderProjectNotes(p);
    }

    function cancelProjectNoteEdit() {
      currentProjectEditingNoteId = null;
      const p = projects.find(x => x.id === currentProjectId);
      if (p) renderProjectNotes(p);
    }

    async function saveProjectNoteEdit(taskId, noteId) {
      if (!currentProjectId || !taskId || !noteId) return;
      const input = document.getElementById(`taskNoteEditInput-${noteId}`);
      const texto = String(input?.value || '').trim();
      if (!texto) return;

      const res = await fetch('/api/proyectos/tareas/notas/actualizar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: taskId, nota_id: noteId, texto})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo editar la nota', 'error');
        return;
      }

      currentProjectEditingNoteId = null;
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      if (window.showNotification) window.showNotification('Nota actualizada', 'success');
    }

    async function deleteProjectNote(taskId, noteId) {
      if (!currentProjectId || !taskId || !noteId) return;
      if (!confirm('¿Eliminar esta nota?')) return;

      const res = await fetch('/api/proyectos/tareas/notas/eliminar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: taskId, nota_id: noteId})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo eliminar la nota', 'error');
        return;
      }

      currentProjectEditingNoteId = null;
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      if (window.showNotification) window.showNotification('Nota eliminada', 'success');
    }

    function handleTaskPriorityChange() {
      const select = document.getElementById('taskModalPriority');
      const task = getCurrentTask();
      if (task && select?.value && select.value !== 'Vencido') {
        task.priority = normalizeTaskPriority(select.value);
      }
      refreshTaskModalPriorityUI();
      queueTaskDetailsAutosave(150);
    }

    function queueTaskDetailsAutosave(delay = 450) {
      if (!currentProjectId || !currentTaskId) return;
      clearTimeout(taskDetailsAutosaveTimer);
      taskDetailsAutosaveTimer = setTimeout(() => {
        saveTaskDetails({ silent: true, rerenderTaskDetail: false });
      }, delay);
    }

    async function saveTaskDetails(options = {}) {
      if (!currentProjectId || !currentTaskId) return;
      const text = (document.getElementById('taskModalTitle')?.value || '').trim();
      if (!text) return;
      const descripcion = getCompactDescValue('taskModalDescription');
      const start_date = document.getElementById('taskModalStart')?.value || null;
      const end_date = document.getElementById('taskModalEnd')?.value || null;
      const due_date = end_date || null;
      const task = getCurrentTask();
      const selectedPriority = document.getElementById('taskModalPriority')?.value || 'Con calma';
      const priority = selectedPriority === 'Vencido'
        ? normalizeTaskPriority(task?.priority || 'Con calma')
        : selectedPriority;
      const owners = Array.isArray(task?.owners) ? task.owners : [];
      const owner_ids = parseTaskOwnerIds();
      const board_stage = String(task?.board_stage || 'Por hacer').trim() || 'Por hacer';
      const board_order = Number(task?.board_order || 0);

      if (task) {
        task.texto = text;
        task.descripcion = descripcion;
        task.start_date = start_date;
        task.end_date = end_date;
        task.due_date = due_date;
        task.priority = normalizeTaskPriority(priority);
        task.owners = owners;
        task.owner_ids = owner_ids;
      }

      const res = await fetch('/api/proyectos/tareas/actualizar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, texto: text, descripcion, start_date, end_date, due_date, priority, owners, owner_ids, board_stage, board_order})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo guardar la tarea', 'error');
        return;
      }

      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: options.rerenderTaskDetail !== false });
      if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
        const scrollLeft = document.getElementById('projectBoardColumns')?.scrollLeft || 0;
        renderProjectBoard(currentBoardProjectId, { scrollLeft });
      }
      currentTaskModalEditing = true;
      applyTaskModalEditState();
      if (!options.silent && window.showNotification) window.showNotification('Tarea actualizada', 'success');
    }

    function setTaskDescriptionAutosaveStatus(state) {
      const status = document.getElementById('taskModalDescAutosaveStatus');
      if (!status) return;
      status.classList.remove('text-slate-400', 'text-amber-600', 'text-lime-600', 'text-rose-600');
      if (state === 'saving') {
        status.textContent = 'Guardando...';
        status.classList.add('text-amber-600');
      } else if (state === 'saved') {
        status.textContent = 'Guardado';
        status.classList.add('text-lime-600');
      } else if (state === 'error') {
        status.textContent = 'No se pudo guardar';
        status.classList.add('text-rose-600');
      } else {
        status.textContent = 'Autoguardado';
        status.classList.add('text-slate-400');
      }
    }

    function queueTaskDescriptionAutosave() {
      if (!currentProjectId || !currentTaskId) return;
      const task = getCurrentTask();
      if (!task) return;
      const desc = getCompactDescValue('taskModalDescription');
      task.descripcion = desc;
      setTaskDescriptionAutosaveStatus('saving');
      clearTimeout(taskDescAutosaveTimer);
      taskDescAutosaveTimer = setTimeout(() => saveTaskDescriptionAutosave(desc), 650);
    }
    window.queueTaskDescriptionAutosave = queueTaskDescriptionAutosave;

    async function saveTaskDescriptionAutosave(desc) {
      if (!currentProjectId || !currentTaskId) return;
      const task = getCurrentTask();
      if (!task) return;
      const text = String(task.texto || document.getElementById('taskModalTitle')?.value || '').trim();
      if (!text) return;
      try {
        const res = await fetch('/api/proyectos/tareas/actualizar', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
          body: JSON.stringify({
            id: currentProjectId,
            tarea_id: currentTaskId,
            texto: text,
            descripcion: desc,
            start_date: task.start_date || null,
            end_date: task.end_date || task.due_date || null,
            due_date: task.due_date || task.end_date || null,
            priority: normalizeTaskPriority(task.priority || 'Con calma'),
            owners: Array.isArray(task.owners) ? task.owners : [],
            owner_ids: Array.isArray(task.owner_ids) ? task.owner_ids : [],
            board_stage: task.board_stage || 'Por hacer',
            board_order: Number(task.board_order || 0),
          }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) throw new Error('task_description_update_failed');
        syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: false });
        setTaskDescriptionAutosaveStatus('saved');
      } catch (error) {
        console.error('Error guardando descripcion de tarea:', error);
        setTaskDescriptionAutosaveStatus('error');
      }
    }

    async function deleteTask(taskId = null) {
      if (!currentProjectId) return;
      const targetTaskId = taskId || currentTaskId;
      if (!targetTaskId) return;
      if (!confirm('¿Mover esta tarea a la papelera?')) return;

      const res = await fetch('/api/proyectos/tareas/eliminar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: targetTaskId})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        if (window.showNotification) window.showNotification('No se pudo eliminar la tarea', 'error');
        return;
      }

      const p = projects.find(x => x.id === currentProjectId);
      if (p) {
        p.tareas = data.item.tareas || [];
        p.archived_tasks = data.item.archived_tasks || [];
      }
      renderModalTasks(data.item.tareas || []);
      if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
        renderProjectBoard(currentBoardProjectId);
      }
      if (targetTaskId === currentTaskId) closeTaskModal();
      if (window.showNotification) window.showNotification('Tarea movida a la papelera', 'success');
    }

    function getCurrentSubtask(subtaskId = currentEditingSubtaskId) {
      const task = getCurrentTask();
      if (!task || !subtaskId) return null;
      return (task.subtasks || []).find((item) => String(item.id || '') === String(subtaskId || '')) || null;
    }

    function openSubtaskEditor(subtaskId) {
      currentEditingSubtaskId = String(subtaskId || '');
      const task = getCurrentTask();
      renderTaskSubtasks(task?.subtasks || []);
      setTimeout(() => document.getElementById('editSubtaskText')?.focus(), 0);
    }

    function cancelSubtaskEditor() {
      currentEditingSubtaskId = null;
      const task = getCurrentTask();
      renderTaskSubtasks(task?.subtasks || []);
    }

    function setSubtaskAutosaveStatus(text) {
      const status = document.getElementById('subtaskAutosaveStatus');
      if (status) status.textContent = text;
    }

    function toggleSubtaskOwnerSearch() {
      const panel = document.getElementById('subtaskOwnerSearchPanel');
      if (!panel) return;
      closeSubtaskPriorityMenu();
      panel.classList.toggle('hidden');
      if (!panel.classList.contains('hidden')) {
        const input = document.getElementById('subtaskOwnerSearchInput');
        input?.focus();
        searchSubtaskOwners(input?.value || '', true);
      }
    }

    async function searchSubtaskOwners(query = '', immediate = false) {
      const box = document.getElementById('subtaskOwnerSearchResults');
      if (!box) return;

      const q = String(query || '').trim();
      if (!q) {
        box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Escribe para buscar usuarios</div>';
        return;
      }

      if (subtaskOwnerSearchDebounce) clearTimeout(subtaskOwnerSearchDebounce);

      const run = async () => {
        if (subtaskOwnerSearchAbort) subtaskOwnerSearchAbort.abort();
        subtaskOwnerSearchAbort = new AbortController();
        box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">Buscando usuarios...</div>';

        try {
          const res = await fetch('/api/proyectos/responsables/search?q=' + encodeURIComponent(q), {
            signal: subtaskOwnerSearchAbort.signal,
          });
          const json = await res.json().catch(() => ({data: []}));
          const users = Array.isArray(json.data) ? json.data : [];
          if (!users.length) {
            box.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No se encontraron usuarios</div>';
            return;
          }
          box.innerHTML = users.map((user) => {
            const safeId = String(user.id || '').replace(/'/g, "\\'");
            const safeName = String(user.name || '').replace(/'/g, "\\'");
            const initials = escapeHtml(String(user.name || 'US').substring(0, 2).toUpperCase());
            const name = escapeHtml(user.name || 'Usuario');
            const email = escapeHtml(user.email || '');
            return `<button type="button" class="w-full border-b border-slate-100 px-3 py-2.5 text-left last:border-b-0 hover:bg-slate-50" onclick="addSubtaskOwnerFromCatalog('${safeId}', '${safeName}')">
              <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">${initials}</span>
                <span class="min-w-0">
                  <span class="block truncate text-sm font-semibold text-slate-700">${name}</span>
                  <span class="block truncate text-[11px] text-slate-400">${email}</span>
                </span>
              </div>
            </button>`;
          }).join('');
        } catch (error) {
          if (error.name === 'AbortError') return;
          box.innerHTML = '<div class="px-3 py-2 text-xs text-rose-500">No se pudo buscar usuarios</div>';
        }
      };

      if (immediate) {
        run();
        return;
      }
      subtaskOwnerSearchDebounce = setTimeout(run, 220);
    }

    function addSubtaskOwnerFromCatalog(userId, userName) {
      const subtask = getCurrentSubtask();
      if (!subtask) return;
      subtask.texto = String(document.getElementById('editSubtaskText')?.value || subtask.texto || '');
      subtask.due_date = String(document.getElementById('editSubtaskDueDate')?.value || subtask.due_date || '');
      subtask.priority = String(document.getElementById('editSubtaskPriority')?.value || subtask.priority || 'Con calma');
      const names = Array.isArray(subtask.owners) ? [...subtask.owners] : [];
      const ids = Array.isArray(subtask.owner_ids) ? [...subtask.owner_ids] : [];
      if (!ids.includes(userId) && !names.includes(userName)) {
        ids.push(userId);
        names.push(userName);
      }
      subtask.owners = names;
      subtask.owner_ids = ids;
      renderTaskSubtasks(getCurrentTask()?.subtasks || []);
      saveSubtaskDetails({ closeAfter: false });
    }

    function removeSubtaskOwner(index) {
      const subtask = getCurrentSubtask();
      if (!subtask) return;
      subtask.texto = String(document.getElementById('editSubtaskText')?.value || subtask.texto || '');
      subtask.due_date = String(document.getElementById('editSubtaskDueDate')?.value || subtask.due_date || '');
      subtask.priority = String(document.getElementById('editSubtaskPriority')?.value || subtask.priority || 'Con calma');
      const names = Array.isArray(subtask.owners) ? [...subtask.owners] : [];
      const ids = Array.isArray(subtask.owner_ids) ? [...subtask.owner_ids] : [];
      names.splice(index, 1);
      if (ids.length > index) ids.splice(index, 1);
      subtask.owners = names;
      subtask.owner_ids = ids;
      renderTaskSubtasks(getCurrentTask()?.subtasks || []);
      saveSubtaskDetails({ closeAfter: false });
    }

    async function saveSubtaskDetails(options = {}) {
      const closeAfter = options.closeAfter !== false;
      const shouldRerender = options.rerender !== false;
      const editingId = String(currentEditingSubtaskId || '');
      if (!currentProjectId || !currentTaskId || !editingId) return;
      const subtask = getCurrentSubtask(editingId);
      if (!subtask) return;
      const text = String(document.getElementById('editSubtaskText')?.value || '').trim();
      if (!text) {
        document.getElementById('editSubtaskText')?.focus();
        return;
      }
      const dueDate = String(document.getElementById('editSubtaskDueDate')?.value || '').trim();
      const priority = String(document.getElementById('editSubtaskPriority')?.value || subtask.priority || 'Con calma').trim();
      const owners = Array.isArray(subtask.owners) ? subtask.owners.filter(Boolean) : [];
      const ownerIds = Array.isArray(subtask.owner_ids) ? subtask.owner_ids.filter(Boolean) : [];

      setSubtaskAutosaveStatus('Guardando...');
      const res = await fetch('/api/proyectos/tareas/subtareas/actualizar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({
          id: currentProjectId,
          tarea_id: currentTaskId,
          subtarea_id: editingId,
          texto: text,
          owners,
          owner_ids: ownerIds,
          due_date: dueDate,
          priority,
        })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      currentEditingSubtaskId = closeAfter && currentEditingSubtaskId === editingId ? null : currentEditingSubtaskId;
      if (!shouldRerender) {
        const project = projects.find((item) => String(item.id) === String(currentProjectId));
        if (project) {
          project.tareas = data.item.tareas || project.tareas || [];
          renderModalTasks(project.tareas || []);
        }
        setSubtaskAutosaveStatus('Autoguardado');
        return;
      }
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
    }

    function keepSubtaskComposerVisible() {
      const input = document.getElementById('newSubtaskInput');
      const composer = document.getElementById('taskSubtaskComposer');
      if (composer) {
        composer.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
      }
      setTimeout(() => input?.focus({ preventScroll: true }), 120);
    }

    async function addSubtask(options = {}) {
      if (!currentProjectId || !currentTaskId) return;
      const input = document.getElementById('newSubtaskInput');
      const text = (input?.value || '').trim();
      if (!text) return;

      const res = await fetch('/api/proyectos/tareas/subtareas/agregar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, texto: text})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      if (input) input.value = '';
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      if (options.refocus) {
        setTimeout(keepSubtaskComposerVisible, 0);
      }
    }

    async function toggleSubtask(subtaskId) {
      if (!currentProjectId || !currentTaskId || !subtaskId) return;
      const res = await fetch('/api/proyectos/tareas/subtareas/toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, subtarea_id: subtaskId})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      animateProgressBarsOnce = true;
      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
      renderGlobalTasksView(projects);
      renderProjectListView(projects);
      animateProgressBarsOnce = false;
    }

    async function removeSubtask(subtaskId) {
      if (!currentProjectId || !currentTaskId || !subtaskId) return;
      const res = await fetch('/api/proyectos/tareas/subtareas/eliminar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
        body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, subtarea_id: subtaskId})
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) return;

      syncCurrentProjectTasks(data.item.tareas || [], { rerenderTaskDetail: true });
    }

    // --- Files ---
    const projectPreviewableImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    function projectFileExt(name = '', explicitExt = '') {
      const ext = String(explicitExt || '').trim().toLowerCase();
      if (ext) return ext;
      const clean = String(name || '').split('?')[0];
      const parts = clean.split('.');
      return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    function projectFileTone(ext = '') {
      const normalized = String(ext || '').toLowerCase();
      if (normalized === 'pdf') return {color: '#4f46e5', label: 'PDF'};
      if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(normalized)) return {color: '#f59e0b', label: (normalized || 'img').toUpperCase()};
      if (['doc', 'docx'].includes(normalized)) return {color: '#2563eb', label: normalized.toUpperCase()};
      if (['xls', 'xlsx', 'csv'].includes(normalized)) return {color: '#059669', label: normalized.toUpperCase()};
      if (['ppt', 'pptx'].includes(normalized)) return {color: '#ea580c', label: normalized.toUpperCase()};
      if (['zip', 'rar', '7z'].includes(normalized)) return {color: '#f97316', label: normalized.toUpperCase()};
      return {color: '#64748b', label: (normalized || 'FILE').toUpperCase()};
    }

    function projectFileDate(value) {
      if (!value) return '—';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return '—';
      return date.toLocaleString('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function projectFileTitle(name = '') {
      return String(name || 'Documento').replace(/\.[^/.]+$/, '');
    }

    let projectFilePreviewScale = 1;

    function setProjectFilePreviewScale(value) {
      projectFilePreviewScale = Math.max(0.55, Math.min(2.6, value));
      const content = document.getElementById('projectFilePreviewContent');
      if (content) content.style.transform = `scale(${projectFilePreviewScale})`;
    }

    function resetProjectFilePreviewScale() {
      setProjectFilePreviewScale(1);
    }

    function openProjectFilePreviewFromCard(card) {
      if (!card) return;
      openProjectFilePreview({
        title: card.dataset.previewTitle || 'Documento',
        url: card.dataset.previewUrl || '',
        type: card.dataset.previewType || 'unsupported',
        downloadUrl: card.dataset.downloadUrl || '',
        extLabel: card.dataset.extLabel || 'FILE',
        extColor: card.dataset.extColor || '#475569',
      });
    }

    function openProjectFilePreview({title, url, type, downloadUrl, extLabel, extColor}) {
      const modal = document.getElementById('projectFilePreviewModal');
      const titleEl = document.getElementById('projectFilePreviewTitle');
      const subtitleEl = document.getElementById('projectFilePreviewSubtitle');
      const footerEl = document.getElementById('projectFilePreviewFooter');
      const frame = document.getElementById('projectFilePreviewFrame');
      const imageWrap = document.getElementById('projectFilePreviewImageWrap');
      const image = document.getElementById('projectFilePreviewImage');
      const unsupported = document.getElementById('projectFilePreviewUnsupported');
      const download = document.getElementById('projectFilePreviewDownload');
      const unsupportedDownload = document.getElementById('projectFilePreviewUnsupportedDownload');
      const unsupportedExt = document.getElementById('projectFilePreviewExt');
      if (!modal || !frame || !imageWrap || !image || !unsupported) return;
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }

      if (titleEl) titleEl.textContent = type === 'unsupported' ? 'Vista previa no disponible' : 'Vista previa del documento';
      if (subtitleEl) subtitleEl.textContent = type === 'unsupported' ? 'Formato no compatible con vista previa nativa.' : 'Usa trackpad o rueda sobre la vista para acercar y alejar.';
      if (footerEl) footerEl.textContent = title || 'Documento';
      frame.classList.add('hidden');
      imageWrap.classList.add('hidden');
      unsupported.classList.add('hidden');
      frame.removeAttribute('src');
      image.removeAttribute('src');
      if (download) {
        download.href = downloadUrl || url || '#';
        download.classList.toggle('hidden', !(downloadUrl || url));
      }
      resetProjectFilePreviewScale();

      if (type === 'image') {
        image.src = url;
        imageWrap.classList.remove('hidden');
      } else if (type === 'pdf') {
        frame.src = url;
        frame.classList.remove('hidden');
      } else {
        unsupported.classList.remove('hidden');
        if (unsupportedDownload) unsupportedDownload.href = downloadUrl || url || '#';
        if (unsupportedExt) {
          unsupportedExt.textContent = extLabel || 'FILE';
          unsupportedExt.style.background = extColor || '#475569';
        }
      }

      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeProjectFilePreview() {
      const modal = document.getElementById('projectFilePreviewModal');
      const frame = document.getElementById('projectFilePreviewFrame');
      const image = document.getElementById('projectFilePreviewImage');
      if (!modal) return;
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      if (frame) frame.removeAttribute('src');
      if (image) image.removeAttribute('src');
      resetProjectFilePreviewScale();
    }

    function getTaskCoverFile(task) {
      const files = Array.isArray(task?.files) ? task.files : [];
      if (!files.length) return null;
      const coverId = String(task?.cover_file_id || '');
      const byId = coverId ? files.find((file) => String(file?.id || '') === coverId) : null;
      if (byId && String(byId.mime || '').startsWith('image/')) return byId;
      return files.find((file) => String(file?.mime || '').startsWith('image/')) || null;
    }

    function renderTaskFiles(files) {
      const container = document.getElementById('taskFilesList');
      if (!container) return;
      const items = Array.isArray(files) ? files : [];
      if (!items.length) {
        container.innerHTML = '';
        return;
      }

      const project = projects.find(x => String(x?.id || '') === String(currentProjectId || '')) || {};
      container.innerHTML = items.map((file) => {
        const id = String(file?.id || '').replace(/'/g, "\\'");
        const name = String(file?.name || 'Documento');
        const safeName = escapeHtml(name);
        const ext = projectFileExt(name, file?.ext || '');
        const tone = projectFileTone(ext);
        const isImage = projectPreviewableImages.includes(ext) || String(file?.mime || '').startsWith('image/');
        const downloadUrl = file?.download_url || file?.url || (id ? `/documentos/${encodeURIComponent(id)}/download` : '#');
        const previewUrl = file?.preview_url || (id ? `/documentos/${encodeURIComponent(id)}/preview` : downloadUrl);
        const openUrl = isImage || ext === 'pdf' ? previewUrl : downloadUrl;
        const previewType = isImage ? 'image' : (ext === 'pdf' ? 'pdf' : 'unsupported');
        const folderPath = String(file?.folder || (project?.titulo ? `Proyectos / ${project.titulo}` : 'Proyectos'));
        const clientId = String(project?.cliente_id || file?.cliente_id || '');
        const folderSpace = clientId ? 'client' : 'personal';
        const folderUrl = `/documentos?space=${encodeURIComponent(folderSpace)}${clientId ? `&cliente_id=${encodeURIComponent(clientId)}` : ''}&folder=${encodeURIComponent(folderPath)}`;
        const metaLabel = `Añadido: ${projectFileDate(file?.date || file?.uploaded_at || file?.created_at)}${isImage ? ' · Portada' : ''}`;
        const figure = isImage
          ? `<img src="${previewUrl}" class="project-file-thumb" alt="${safeName}"><div class="project-file-image-ext" style="background:${tone.color}">${tone.label}</div>`
          : `<div class="project-file-figure"><div class="project-file-ext" style="background:${tone.color}">${tone.label}</div><div class="project-file-lines" aria-hidden="true"><span></span><span></span></div></div>`;

        return `
          <div class="project-file-card group cursor-pointer" style="--file-color:${tone.color}" role="button" tabindex="0"
            data-preview-title="${safeName}"
            data-preview-url="${escapeHtml(openUrl)}"
            data-preview-type="${previewType}"
            data-download-url="${escapeHtml(downloadUrl)}"
            data-ext-label="${escapeHtml(tone.label)}"
            data-ext-color="${escapeHtml(tone.color)}"
            onclick="openProjectFilePreviewFromCard(this)"
            onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); openProjectFilePreviewFromCard(this); }">
            <div class="project-file-preview" title="${safeName}">${figure}</div>
            <div class="min-w-0">
              <div class="project-file-title" title="${safeName}">${safeName}</div>
              <div class="project-file-date">${escapeHtml(metaLabel)}</div>
            </div>
            <div class="project-file-actions">
              <a href="${folderUrl}" class="project-file-action" title="Abrir carpeta en Documentos" target="_blank" onclick="event.stopPropagation()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 17L17 7M9 7h8v8"/></svg>
              </a>
              <a href="${downloadUrl}" class="project-file-action" title="Descargar" target="_blank" onclick="event.stopPropagation()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
              </a>
              <button type="button" onclick="event.stopPropagation(); removeTaskFile('${id}')" class="project-file-action danger" title="Eliminar adjunto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 6h18M8 6V4h8v2m-1 5v6M9 11v6m-2 4h10a2 2 0 002-2V6H5v13a2 2 0 002 2z"/></svg></button>
            </div>
          </div>`;
      }).join('');
    }

    function uploadTaskFile(file, row) {
      return new Promise((resolve, reject) => {
        if (!currentProjectId || !currentTaskId) {
          reject(new Error('missing_task'));
          return;
        }
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('id', currentProjectId);
        formData.append('tarea_id', currentTaskId);
        formData.append('file', file);
        formData.append('_token', window.csrfToken);

        xhr.open('POST', '/api/proyectos/archivo');
        xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
          if (!event.lengthComputable) return;
          updateProjectUploadRow(row, (event.loaded / event.total) * 100);
        });
        xhr.addEventListener('load', () => {
          const data = JSON.parse(xhr.responseText || '{}');
          if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
            updateProjectUploadRow(row, 100, true);
            resolve(data.item);
            return;
          }
          reject(new Error('upload_failed'));
        });
        xhr.addEventListener('error', () => reject(new Error('upload_failed')));
        xhr.send(formData);
      });
    }

    async function handleTaskFileUpload(files) {
      if (!files || files.length === 0 || !currentProjectId || !currentTaskId) return;
      const fileList = Array.from(files);
      const panel = document.getElementById('projectUploadProgress');
      const list = document.getElementById('projectUploadProgressList');
      const summary = document.getElementById('projectUploadSummary');
      if (list) list.innerHTML = '';
      if (panel) panel.classList.remove('hidden');
      const rows = fileList.map(createProjectUploadRow);

      try {
        let lastItem = null;
        for (let i = 0; i < fileList.length; i++) {
          if (summary) summary.textContent = `${i + 1} de ${fileList.length}`;
          lastItem = await uploadTaskFile(fileList[i], rows[i]);
        }

        if (lastItem) {
          syncCurrentProjectTasks(lastItem.tareas || [], { rerenderTaskDetail: true });
          if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
            renderProjectBoard(currentBoardProjectId);
          }
          if (summary) summary.textContent = `${fileList.length} archivo${fileList.length === 1 ? '' : 's'} subido${fileList.length === 1 ? '' : 's'}`;
          if (window.showNotification) window.showNotification(fileList.length > 1 ? 'Adjuntos subidos' : 'Adjunto subido', 'success');
          setTimeout(() => {
            panel?.classList.add('hidden');
            if (list) list.innerHTML = '';
            if (summary) summary.textContent = 'Preparando...';
          }, 900);
        }
      } catch (error) {
        if (summary) summary.textContent = 'No se pudo completar la subida.';
        if (window.showNotification) window.showNotification('Error de conexión al subir', 'error');
        setTimeout(() => panel?.classList.add('hidden'), 2200);
      }
    }

    async function removeTaskFile(fileId) {
      if (!currentProjectId || !currentTaskId || !fileId) return;
      if (!confirm('¿Eliminar este archivo adjunto?')) return;
      try {
        const res = await fetch('/api/proyectos/archivo/eliminar', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
          body: JSON.stringify({id: currentProjectId, tarea_id: currentTaskId, file_id: fileId})
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) throw new Error('delete_failed');
        syncCurrentProjectTasks(data.item?.tareas || [], { rerenderTaskDetail: true });
        if (String(currentBoardProjectId || '') === String(currentProjectId || '')) {
          renderProjectBoard(currentBoardProjectId);
        }
        if (window.showNotification) window.showNotification('Adjunto eliminado', 'success');
      } catch (error) {
        if (window.showNotification) window.showNotification('No se pudo eliminar el adjunto', 'error');
      }
    }

    function renderModalFiles(files) {
        const container = document.getElementById('modalFilesList');
        if (!container) return;
        const canDelete = !projectModalReadOnly;
        const items = Array.isArray(files) ? files : [];
        if (!items.length) {
          container.innerHTML = '';
          return;
        }

        container.innerHTML = items.map(f => {
          const project = projects.find(x => String(x?.id || '') === String(currentProjectId || '')) || {};
          const id = String(f?.id || '').replace(/'/g, "\\'");
          const name = String(f?.name || 'Documento');
          const safeName = escapeHtml(name);
          const ext = projectFileExt(name, f?.ext || '');
          const tone = projectFileTone(ext);
          const isImage = projectPreviewableImages.includes(ext);
          const downloadUrl = f?.download_url || f?.url || (id ? `/documentos/${encodeURIComponent(id)}/download` : '#');
          const previewUrl = f?.preview_url || (id ? `/documentos/${encodeURIComponent(id)}/preview` : downloadUrl);
          const openUrl = isImage || ext === 'pdf' ? previewUrl : downloadUrl;
          const previewType = isImage ? 'image' : (ext === 'pdf' ? 'pdf' : 'unsupported');
          const folderPath = String(f?.folder || (project?.titulo ? `Proyectos / ${project.titulo}` : 'Proyectos'));
          const clientId = String(project?.cliente_id || f?.cliente_id || '');
          const folderSpace = clientId ? 'client' : 'personal';
          const folderUrl = `/documentos?space=${encodeURIComponent(folderSpace)}${clientId ? `&cliente_id=${encodeURIComponent(clientId)}` : ''}&folder=${encodeURIComponent(folderPath)}`;
          const metaLabel = `Añadido: ${projectFileDate(f?.date || f?.uploaded_at || f?.created_at)}${isImage ? ' · Imagen' : ''}`;
          const figure = isImage
            ? `<img src="${previewUrl}" class="project-file-thumb" alt="${safeName}"><div class="project-file-image-ext" style="background:${tone.color}">${tone.label}</div>`
            : `<div class="project-file-figure"><div class="project-file-ext" style="background:${tone.color}">${tone.label}</div><div class="project-file-lines" aria-hidden="true"><span></span><span></span></div></div>`;

          return `
            <div class="project-file-card group cursor-pointer" style="--file-color:${tone.color}" role="button" tabindex="0"
              data-preview-title="${safeName}"
              data-preview-url="${escapeHtml(openUrl)}"
              data-preview-type="${previewType}"
              data-download-url="${escapeHtml(downloadUrl)}"
              data-ext-label="${escapeHtml(tone.label)}"
              data-ext-color="${escapeHtml(tone.color)}"
              onclick="openProjectFilePreviewFromCard(this)"
              onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); openProjectFilePreviewFromCard(this); }">
              <div class="project-file-preview" title="${safeName}">
                ${figure}
              </div>
              <div class="min-w-0">
                <div class="project-file-title" title="${safeName}">${safeName}</div>
                <div class="project-file-date">${escapeHtml(metaLabel)}</div>
              </div>
              <div class="project-file-actions">
                <a href="${folderUrl}" class="project-file-action" title="Abrir carpeta en Documentos" target="_blank" onclick="event.stopPropagation()">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 17L17 7M9 7h8v8"/></svg>
                </a>
                <a href="${downloadUrl}" class="project-file-action" title="Descargar" target="_blank" onclick="event.stopPropagation()">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>
                </a>
                ${(canDelete && id) ? `<button type="button" onclick="event.stopPropagation();removeModalFile('${id}')" class="project-file-action danger" title="Eliminar adjunto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 6h18M8 6V4h8v2m-1 5v6M9 11v6m-2 4h10a2 2 0 002-2V6H5v13a2 2 0 002 2z"/></svg></button>` : ''}
              </div>
            </div>
          `;
        }).join('');
    }

    async function removeModalFile(fileId) {
      if (projectModalReadOnly) return;
      if (!currentProjectId || !fileId) return;
      if (!confirm('¿Eliminar este archivo adjunto?')) return;

      try {
        const res = await fetch('/api/proyectos/archivo/eliminar', {
          method: 'POST',
          headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
          body: JSON.stringify({id: currentProjectId, file_id: fileId})
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) {
          throw new Error('delete_failed');
        }

        renderModalFiles(data.item?.files || []);
        const p = projects.find(x => x.id === currentProjectId);
        if (p) p.files = data.item?.files || [];
        if (window.showNotification) window.showNotification('Adjunto eliminado', 'success');
      } catch (e) {
        if (window.showNotification) window.showNotification('No se pudo eliminar el adjunto', 'error');
      }
    }
    
    function createProjectUploadRow(file, index) {
      const list = document.getElementById('projectUploadProgressList');
      if (!list) return null;
      const row = document.createElement('div');
      row.className = 'project-upload-row';
      row.innerHTML = `
        <div class="project-upload-ghost" aria-hidden="true"></div>
        <div class="min-w-0">
          <div class="flex items-center justify-between gap-2">
            <div class="text-xs font-black text-slate-800 truncate"></div>
            <div class="project-upload-percent text-[11px] font-bold text-slate-400">0%</div>
          </div>
          <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
            <div class="project-upload-bar h-full w-0 rounded-full bg-lime-300 transition-[width] duration-150"></div>
          </div>
        </div>`;
      row.querySelector('.text-xs').textContent = file.name || `Archivo ${index + 1}`;
      list.appendChild(row);
      return row;
    }

    function updateProjectUploadRow(row, percent, done = false) {
      if (!row) return;
      const clamped = Math.max(0, Math.min(100, Math.round(percent)));
      const bar = row.querySelector('.project-upload-bar');
      const label = row.querySelector('.project-upload-percent');
      if (bar) bar.style.width = `${clamped}%`;
      if (label) {
        label.textContent = done ? 'Listo' : `${clamped}%`;
        label.classList.toggle('text-emerald-600', done);
        label.classList.toggle('text-slate-400', !done);
      }
    }

    function uploadProjectFile(file, row, projectId = currentProjectId) {
      return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('id', projectId);
        formData.append('file', file);
        formData.append('_token', window.csrfToken);

        xhr.open('POST', '/api/proyectos/archivo');
        xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
          if (!event.lengthComputable) return;
          updateProjectUploadRow(row, (event.loaded / event.total) * 100);
        });
        xhr.addEventListener('load', () => {
          const data = JSON.parse(xhr.responseText || '{}');
          if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
            updateProjectUploadRow(row, 100, true);
            resolve(data.item);
            return;
          }
          reject(new Error('upload_failed'));
        });
        xhr.addEventListener('error', () => reject(new Error('upload_failed')));
        xhr.send(formData);
      });
    }

    async function uploadAndApplyProjectCover(file, projectId = currentProjectId, options = {}) {
      if (!file || !projectId) return;
      if (!String(file.type || '').startsWith('image/')) {
        if (window.showNotification) window.showNotification('Elige una imagen para la portada', 'error');
        return;
      }

      const panel = document.getElementById('projectUploadProgress');
      const list = document.getElementById('projectUploadProgressList');
      const summary = document.getElementById('projectUploadSummary');
      if (list) list.innerHTML = '';
      if (panel && String(projectId) === String(currentProjectId || '')) panel.classList.remove('hidden');
      const row = String(projectId) === String(currentProjectId || '') ? createProjectUploadRow(file, 0) : null;

      try {
        if (summary && String(projectId) === String(currentProjectId || '')) summary.textContent = 'Subiendo portada...';
        const updatedProject = await uploadProjectFile(file, row, projectId);
        const imageFile = [...(updatedProject?.files || [])].reverse().find((item) => {
          const mime = String(item?.mime || '');
          return mime.startsWith('image/');
        });
        const coverUrl = imageFile?.preview_url || imageFile?.url || '';
        if (!coverUrl) throw new Error('cover_url_missing');
        const updated = await updateProjectField('cover_image', coverUrl, projectId);

        const localProject = projects.find(x => String(x.id) === String(projectId));
        if (localProject) {
          localProject.files = updatedProject?.files || localProject.files || [];
          localProject.cover_image = coverUrl;
        }
        if (String(projectId) === String(currentProjectId || '')) {
          renderModalFiles(updatedProject?.files || []);
          renderModalCoverPicker(updated || localProject || updatedProject);
          renderKanban(projects);
          if (String(currentBoardProjectId || '') === String(projectId)) renderProjectBoard(projectId);
        }
        if (!options.silent && window.showNotification) window.showNotification('Portada actualizada', 'success');
      } catch (error) {
        if (!options.silent && window.showNotification) window.showNotification('No se pudo subir la portada', 'error');
        throw error;
      } finally {
        if (String(projectId) === String(currentProjectId || '')) {
          setTimeout(() => {
            panel?.classList.add('hidden');
            if (list) list.innerHTML = '';
            if (summary) summary.textContent = 'Preparando...';
          }, 900);
        }
      }
    }

    async function handleModalFileUpload(files) {
      if (projectModalReadOnly) return;
      if (!files || files.length === 0 || !currentProjectId) return;
      const fileList = Array.from(files);
      const panel = document.getElementById('projectUploadProgress');
      const list = document.getElementById('projectUploadProgressList');
      const summary = document.getElementById('projectUploadSummary');
      if (list) list.innerHTML = '';
      if (panel) panel.classList.remove('hidden');
      const rows = fileList.map(createProjectUploadRow);

      try {
        let lastItem = null;
        for (let i = 0; i < fileList.length; i++) {
          if (summary) summary.textContent = `${i + 1} de ${fileList.length}`;
          lastItem = await uploadProjectFile(fileList[i], rows[i]);
        }

        if (lastItem) {
          renderModalFiles(lastItem.files || []);
          const p = projects.find(x => x.id === currentProjectId);
          if (p) p.files = lastItem.files || [];
          if (summary) summary.textContent = `${fileList.length} archivo${fileList.length === 1 ? '' : 's'} subido${fileList.length === 1 ? '' : 's'}`;
          if (window.showNotification) {
            window.showNotification(fileList.length > 1 ? 'Archivos subidos correctamente' : 'Archivo subido correctamente', 'success');
          }
          setTimeout(() => {
            panel?.classList.add('hidden');
            if (list) list.innerHTML = '';
            if (summary) summary.textContent = 'Preparando...';
          }, 900);
        }
      } catch(e) {
        if (summary) summary.textContent = 'No se pudo completar la subida.';
        if (window.showNotification) window.showNotification('Error de conexión al subir', 'error');
        setTimeout(() => panel?.classList.add('hidden'), 2200);
      }
    }

    // --- Modal Timer ---
    function updateModalTimer(p) {
        if (timerInterval) clearInterval(timerInterval);
        
        const logs = p.time_logs || [];
        const isRunning = logs.length > 0 && !logs[logs.length-1].end;
        const btn = document.getElementById('modalTimerBtn');
        const display = document.getElementById('modalTimerDisplay');
        syncPipTimerActionButton(isRunning);
        updateModalTimerTaskLabel(p);
        updateInvestedDisplays(p);
        
        if (isRunning) {
            btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg><span>Pausar</span>';
            btn.className = "w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200";
            
            // Start tick
            timerInterval = setInterval(() => {
              const totalSeconds = getCurrentProjectTotalSeconds(p);
              const val = formatTimer(totalSeconds);
                display.innerText = val;
                syncTimerPanelsDisplay(val);
                setTimerSaveButtonState('modalTimerSaveBtn', totalSeconds > 0);
                updateInvestedDisplays(p);
            }, 1000);
        } else {
            btn.innerHTML = '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg><span>Iniciar</span>';
            btn.className = "w-full py-2 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2 bg-lime-400 text-slate-900 hover:bg-lime-500";
            
            const totalSeconds = getCurrentProjectTotalSeconds(p);
            const val = formatTimer(totalSeconds);
            display.innerText = val;
            syncTimerPanelsDisplay(val);
            setTimerSaveButtonState('modalTimerSaveBtn', totalSeconds > 0);
            updateInvestedDisplays(p);
        }
    }
    
    async function toggleModalTimer() {
      if (projectModalReadOnly) return;
        if (!currentProjectId) return;
        const p = projects.find(x => x.id === currentProjectId);
        const logs = p.time_logs || [];
        const isRunning = logs.length > 0 && !logs[logs.length-1].end;

      if (!isRunning) {
        const taskId = await openTimerTaskModal(currentProjectId);
        if (typeof taskId === 'undefined') return;
        const item = await sendTimerAction(currentProjectId, 'start', taskId || null);
        p.time_logs = item.time_logs || [];
        p.tareas = item.tareas || p.tareas || [];
        updateModalTimer(p);
        renderModalTasks(p.tareas || []);
        return;
      }

      const item = await sendTimerAction(currentProjectId, 'stop', null);
      p.time_logs = item.time_logs || [];
      p.tareas = item.tareas || p.tareas || [];
      updateModalTimer(p);
      renderModalTasks(p.tareas || []);
    }

    // --- Inline Title Edit ---
    async function updateTitle(id, newTitle) {
      if (projectModalReadOnly) return;
        if (!newTitle.trim()) return;
        // Check if title actually changed to avoid unnecessary calls
        // We can do this by finding the project in local state
        const p = projects.find(x => x.id === id);
        if (p && p.titulo === newTitle.trim()) return;

        try {
            await fetch('/api/proyectos/actualizar', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken},
                body: JSON.stringify({id, titulo: newTitle.trim()})
            });
            // No need to reload full data if we just updated title, but good for consistency
        } catch(e) {
            console.error('Update title error', e);
        }
    }


    function applyFocus(on){
      if (!kanban) return;
      focusMode = on;
      kanban.classList.toggle('focus-mode', on);
      kanban.querySelectorAll('[data-stage]').forEach(col=>{
        col.addEventListener('mouseenter', ()=>{ if(focusMode){ kanban.querySelectorAll('[data-stage]').forEach(c=>c.style.opacity = c===col? '1':'0.35'); }});
      });
      kanban.addEventListener('mouseleave', ()=>{ if(focusMode){ kanban.querySelectorAll('[data-stage]').forEach(c=>c.style.opacity='1'); }});
    }

    function setProjectView(view) {
      currentTaskView = ['kanban', 'tareas', 'lista', 'archivados'].includes(view) ? view : 'kanban';
      updateProjectSectionHeader(currentTaskView);
      const elKanban = document.getElementById('proyectos-kanban');
      const elTareas = document.getElementById('proyectos-tareas');
      const elLista = document.getElementById('proyectos-lista');
      const elArchivados = document.getElementById('proyectos-archivados');
      if (elKanban) elKanban.classList.toggle('hidden', currentTaskView !== 'kanban');
      if (elTareas) elTareas.classList.toggle('hidden', currentTaskView !== 'tareas');
      if (elLista) elLista.classList.toggle('hidden', currentTaskView !== 'lista');
      if (elArchivados) elArchivados.classList.toggle('hidden', currentTaskView !== 'archivados');

      const nextUrl = new URL(window.location.href);
      nextUrl.searchParams.set('view', currentTaskView);
      window.history.replaceState({}, '', nextUrl);

      if (currentTaskView === 'tareas') {
        renderGlobalTasksView(projects);
      } else if (currentTaskView === 'lista') {
        renderProjectListView(projects);
      } else if (currentTaskView === 'archivados') {
        renderArchivedProjectsView(archivedProjects);
      }
      renderQuickActionsStatus(currentTaskView);
      refreshProjectsSimpleModeUI();
    }

    function updateProjectSectionHeader(view) {
      const titleEl = document.getElementById('projectsSectionTitle');
      const descEl = document.getElementById('projectsSectionDescription');
      if (!titleEl || !descEl) return;

      const copyByView = {
        kanban: {
          title: 'Tableros de proyectos',
          description: 'Abre un tablero para organizar sus tarjetas, tareas y entregables por columnas.',
        },
        lista: {
          title: 'Lista de proyectos',
          description: 'Ve una lista de todos los proyectos y su progreso.',
        },
        tareas: {
          title: 'Tareas de proyectos',
          description: 'Revisa todas las tareas de tus proyectos y su avance en un solo lugar.',
        },
        archivados: {
          title: 'Proyectos archivados',
          description: 'Ve todos los proyectos que has archivado. Puedes restaurarlos en cualquier momento.',
        },
      };

      const copy = copyByView[view] || copyByView.kanban;
      titleEl.textContent = copy.title;
      descEl.textContent = copy.description;
    }

    async function loadData(){
      const qs = currentClienteId ? ('?cliente_id='+encodeURIComponent(currentClienteId)) : '';
      const res = await fetch('/api/proyectos'+qs);
      const json = await res.json();
      let list = json.data || [];
      list = list.map((project) => {
        const pendingDesc = pendingProjectDescriptions[String(project.id || '')];
        return typeof pendingDesc === 'string' ? {...project, descripcion: pendingDesc} : project;
      });
      await loadArchivedProjects();
      // No filter inputs in new UI for now
      projects = list;
      renderKanban(list);
      renderGlobalTasksView(list);
      renderProjectListView(list);
      renderArchivedProjectsView(archivedProjects);
      syncPinnedTimerHud();
      if (openHeaderTimerFromQuery) {
        currentTaskView = 'tareas';
      }
      setProjectView(currentTaskView);
	      if (openHeaderTimerFromQuery) {
	        openHeaderTimerFromQuery = false;
	        const _htu = new URL(window.location.href);
	        _htu.searchParams.delete('header_timer');
	        window.history.replaceState({}, '', _htu);
	        openQuickProjectActionModal('start-timer');
	      }
	      if (openNewProjectFromQuery) {
	        openNewProjectFromQuery = false;
	        clearOpenProjectQueryParam();
	        setTimeout(() => openNewProjectModal(), 120);
	      }
	      if (openProjectFromQuery || openTaskFromQuery) {
        let projectIdToOpen = String(openProjectFromQuery || '');
        if (!projectIdToOpen && openTaskFromQuery) {
          const ownerProject = projects.find((p) => (p.tareas || []).some((t) => String(t.id || '') === String(openTaskFromQuery)));
          projectIdToOpen = String(ownerProject?.id || '');
        }

        const exists = projectIdToOpen && projects.some((p) => String(p.id) === String(projectIdToOpen));
        if (exists) {
          const taskIdToOpen = String(openTaskFromQuery || '');
          openProjectFromQuery = '';
          openTaskFromQuery = '';
          clearOpenProjectQueryParam();
          setTimeout(() => {
            if (!taskIdToOpen) {
              openProjectBoard(projectIdToOpen, { replaceUrl: true });
              return;
            }
            const targetProject = projects.find((p) => String(p.id) === String(projectIdToOpen));
            const hasTask = (targetProject?.tareas || []).some((t) => String(t.id || '') === String(taskIdToOpen));
            if (hasTask) {
              openProjectTask(projectIdToOpen, taskIdToOpen);
            } else {
              openProject(projectIdToOpen);
            }
          }, 120);
        }
      }
      // renderSummary(list); // removed
      // renderList(list); // Removed - function not defined
      renderCalendar(list);
    }
    
    // Removed filter listeners
    document.getElementById('focusToggle')?.addEventListener('click', ()=> applyFocus(!focusMode));
    document.querySelectorAll('.global-task-filter').forEach((btn) => {
      btn.addEventListener('click', () => {
        globalTaskFilter = btn.getAttribute('data-task-filter') || 'all';
        globalTasksClientPage = 1;
        document.querySelectorAll('.global-task-filter').forEach((b) => {
          const active = b === btn;
          b.classList.toggle('is-active', active);
          b.classList.toggle('bg-slate-900', active);
          b.classList.toggle('text-white', active);
        });
        renderGlobalTasksView(projects);
        renderQuickActionsStatus('tareas');
      });
    });

    initModalPrioritySelector();
    document.getElementById('taskModalPriority')?.addEventListener('change', handleTaskPriorityChange);
    document.getElementById('taskModalTitle')?.addEventListener('input', () => queueTaskDetailsAutosave());
    document.addEventListener('keydown', (e) => {
      const quickProjectModal = document.getElementById('quickProjectActionModal');
      if (e.key === 'Escape' && quickProjectModal && !quickProjectModal.classList.contains('hidden')) {
        e.preventDefault();
        closeQuickProjectActionModal();
        return;
      }

      const timerPanel = document.getElementById('timerFullscreenPanel');
      const timerPanelOpen = timerPanel && !timerPanel.classList.contains('hidden');
      if (e.key === 'Escape' && timerPanelOpen) {
        e.preventDefault();
        closeTimerFullscreen();
        return;
      }

      if (e.code !== 'Space') return;
      const modal = document.getElementById('projectModal');
      if (!modal || modal.classList.contains('hidden')) return;
      const tag = (e.target?.tagName || '').toLowerCase();
      const isTyping = tag === 'input' || tag === 'textarea' || e.target?.isContentEditable;
      if (isTyping) return;
      e.preventDefault();
      toggleModalTimer();
    });

    document.addEventListener('click', (e) => {
      const wrap = document.getElementById('responsibleSearchWrap');
      const box = document.getElementById('responsibleSearchResults');
      if (wrap && box && !wrap.contains(e.target)) {
        box.classList.add('hidden');
      }

      const taskWrap = document.getElementById('taskOwnerSearchWrap');
      const taskBox = document.getElementById('taskOwnerSearchResults');
      if (taskWrap && taskBox && !taskWrap.contains(e.target)) {
        taskBox.classList.add('hidden');
      }

      if (!e.target?.closest?.('#projectClientFilter')) {
        closeProjectClientFilter();
      }

      if (!e.target?.closest?.('[data-subtask-priority-wrap]')) {
        closeSubtaskPriorityMenu();
      }

      if (!e.target?.closest?.('.project-cover-trigger-wrap')) {
        closeProjectCoverPickers();
      }

      const filterWrap = e.target?.closest?.('[data-list-filter-wrap]');
      if (!filterWrap) {
        closeListFilterDropdowns();
      }
    });

    document.addEventListener('fullscreenchange', () => {
      if (!document.fullscreenElement) {
        document.getElementById('timerFullscreenPanel')?.classList.add('hidden');
      }
    });

    document.getElementById('projectFilePreviewModal')?.addEventListener('click', function(event) {
      event.stopPropagation();
      if (event.target === this) closeProjectFilePreview();
    });

    const projectPreviewShell = document.getElementById('projectFilePreviewShell');
    if (projectPreviewShell) {
      let projectPreviewTouchDistance = null;
      projectPreviewShell.addEventListener('wheel', function(event) {
        if (!document.getElementById('projectFilePreviewModal')?.classList.contains('flex')) return;
        if (event.ctrlKey || event.metaKey) {
          event.preventDefault();
          const direction = event.deltaY > 0 ? -1 : 1;
          setProjectFilePreviewScale(projectFilePreviewScale + direction * 0.08);
        }
      }, { passive: false });
      projectPreviewShell.addEventListener('touchstart', function(event) {
        if (event.touches.length === 2) {
          const dx = event.touches[0].clientX - event.touches[1].clientX;
          const dy = event.touches[0].clientY - event.touches[1].clientY;
          projectPreviewTouchDistance = Math.hypot(dx, dy);
        }
      }, { passive: true });
      projectPreviewShell.addEventListener('touchmove', function(event) {
        if (event.touches.length !== 2 || projectPreviewTouchDistance === null) return;
        event.preventDefault();
        const dx = event.touches[0].clientX - event.touches[1].clientX;
        const dy = event.touches[0].clientY - event.touches[1].clientY;
        const nextDistance = Math.hypot(dx, dy);
        const delta = (nextDistance - projectPreviewTouchDistance) / 220;
        setProjectFilePreviewScale(projectFilePreviewScale + delta);
        projectPreviewTouchDistance = nextDistance;
      }, { passive: false });
      projectPreviewShell.addEventListener('touchend', function(event) {
        if (event.touches.length < 2) projectPreviewTouchDistance = null;
      }, { passive: true });
    }

    document.addEventListener('keydown', function(event) {
      if (event.key !== 'Escape') return;
      if (document.getElementById('projectFilePreviewModal')?.classList.contains('flex')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        closeProjectFilePreview();
      }
    }, true);

    window.addEventListener('storage', (event) => {
      if (event.key !== GLOBAL_TIMER_STATE_KEY && event.key !== POMODORO_STATE_KEY) return;
      syncPinnedTimerHud();
    });

    window.addEventListener('infocus-global-timer-updated', () => {
      syncPinnedTimerHud();
    });

    window.addEventListener('tdah-pomodoro-state-updated', () => {
      syncPinnedTimerHud();
    });

    window.quickAddTaskFromCurrentView = quickAddTaskFromCurrentView;
    window.quickStartTimerFromCurrentView = quickStartTimerFromCurrentView;
    window.resetQuickFilters = resetQuickFilters;
    window.renderQuickProjectActionList = renderQuickProjectActionList;
    window.confirmQuickProjectAction = confirmQuickProjectAction;
    window.closeQuickProjectActionModal = closeQuickProjectActionModal;

    const pipVideoEl = document.getElementById('timerPipVideo');
    setPipSourceVisible(false);
    setPipButtonState(false);
    pipVideoEl?.addEventListener('leavepictureinpicture', () => {
      setPipButtonState(false);
      if (pipRenderInterval) clearInterval(pipRenderInterval);
      setPipSourceVisible(false);
    });
    pipVideoEl?.addEventListener('webkitpresentationmodechanged', () => {
      setPipButtonState(pipVideoEl.webkitPresentationMode === 'picture-in-picture');
      if (pipVideoEl.webkitPresentationMode !== 'picture-in-picture' && pipRenderInterval) {
        clearInterval(pipRenderInterval);
      }
      setPipSourceVisible(pipVideoEl.webkitPresentationMode === 'picture-in-picture');
    });
    pipVideoEl?.addEventListener('pause', () => {
      if (suppressPipPlaybackSync) return;
      const isNativePip = document.pictureInPictureElement === pipVideoEl || pipVideoEl.webkitPresentationMode === 'picture-in-picture';
      if (!isNativePip) return;
      setCurrentProjectTimerRunning(false).catch(() => {});
    });
    pipVideoEl?.addEventListener('play', () => {
      if (suppressPipPlaybackSync) return;
      const isNativePip = document.pictureInPictureElement === pipVideoEl || pipVideoEl.webkitPresentationMode === 'picture-in-picture';
      if (!isNativePip) return;
      setCurrentProjectTimerRunning(true).catch(() => {});
    });

    function projectDragHasFiles(event) {
      return !!event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files');
    }

    const projectDropzone = document.getElementById('modalDropzone');
    const projectModalEl = document.getElementById('projectModal');
    const projectDropOverlay = document.getElementById('projectModalDropOverlay');
    let projectModalDragCounter = 0;

    function showProjectDropOverlay() {
      if (projectModalReadOnly || !projectDropOverlay) return;
      projectDropOverlay.classList.add('is-active');
      projectDropzone?.classList.add('is-dragging');
    }

    function hideProjectDropOverlay() {
      projectModalDragCounter = 0;
      projectDropOverlay?.classList.remove('is-active');
      projectDropzone?.classList.remove('is-dragging');
    }

    projectModalEl?.addEventListener('dragenter', (event) => {
      if (projectModalReadOnly || !projectDragHasFiles(event)) return;
      event.preventDefault();
      projectModalDragCounter++;
      showProjectDropOverlay();
    });

    projectModalEl?.addEventListener('dragover', (event) => {
      if (projectModalReadOnly || !projectDragHasFiles(event)) return;
      event.preventDefault();
      showProjectDropOverlay();
    });

    projectModalEl?.addEventListener('dragleave', (event) => {
      if (!projectDragHasFiles(event)) return;
      projectModalDragCounter = Math.max(0, projectModalDragCounter - 1);
      if (projectModalDragCounter === 0) hideProjectDropOverlay();
    });

    projectModalEl?.addEventListener('drop', (event) => {
      if (projectModalReadOnly || !projectDragHasFiles(event)) return;
      event.preventDefault();
      const files = event.dataTransfer?.files;
      hideProjectDropOverlay();
      if (files && files.length) handleModalFileUpload(files);
    });

    projectDropzone?.addEventListener('dragover', (event) => {
      if (projectModalReadOnly || !projectDragHasFiles(event)) return;
      event.preventDefault();
      showProjectDropOverlay();
    });

    const taskFileDropzone = document.getElementById('taskFileDropzone');
    taskFileDropzone?.addEventListener('dragover', (event) => {
      if (!projectDragHasFiles(event)) return;
      event.preventDefault();
      taskFileDropzone.classList.add('is-dragging');
    });
    taskFileDropzone?.addEventListener('dragleave', (event) => {
      if (taskFileDropzone.contains(event.relatedTarget)) return;
      taskFileDropzone.classList.remove('is-dragging');
    });
    taskFileDropzone?.addEventListener('drop', (event) => {
      if (!projectDragHasFiles(event)) return;
      event.preventDefault();
      taskFileDropzone.classList.remove('is-dragging');
      const files = event.dataTransfer?.files;
      if (files && files.length) handleTaskFileUpload(files);
    });

    document.addEventListener('focusout', (event) => {
      if (event.target?.id !== 'newTaskInput') return;
      const input = event.target;
      if (!(input instanceof HTMLInputElement)) return;
      if (!input.value.trim()) return;
      setTimeout(() => {
        if (document.activeElement === input) return;
        addTask({ refocus: false });
      }, 80);
    });

    initKanbanDragScroll();
    setProjectView(currentTaskView);

    // Calendario
    let calDate = new Date();
    function renderCalendar(list){
      const grid = document.getElementById('calendarGrid');
      const label = document.getElementById('calLabel');
      const y = calDate.getFullYear(), m = calDate.getMonth();
      const first = new Date(y,m,1).getDay();
      const days = new Date(y,m+1,0).getDate();
      label.textContent = calDate.toLocaleDateString('es-ES',{month:'long',year:'numeric'});
      grid.innerHTML = '';
      const startOffset = (first + 6) % 7;
      for(let i=0;i<startOffset;i++) grid.insertAdjacentHTML('beforeend','<div class="min-h-24 rounded-2xl border border-transparent"></div>');
      for(let d=1; d<=days; d++){
        const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const items = list.filter(p => (p.vencimiento||'').startsWith(dateStr));
        const today = new Date();
        const isToday = today.getFullYear() === y && today.getMonth() === m && today.getDate() === d;
        grid.insertAdjacentHTML('beforeend', `<div class="min-h-28 rounded-2xl border border-slate-100 bg-slate-50/60 p-2 hover:bg-white transition-colors flex flex-col gap-2">
          <div class="flex items-center justify-between">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold ${isToday ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200'}">${d}</div>
            ${items.length ? `<div class="text-[10px] font-bold text-slate-400">${items.length}</div>` : ''}
          </div>
          <div class="space-y-2">
            ${items.map(p=>{
              const dateForGoogle = p.vencimiento ? p.vencimiento.replace(/-/g,'') : '';
              const googleLink = dateForGoogle ? `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(p.titulo||'Proyecto')}&dates=${dateForGoogle}/${dateForGoogle}` : '#';
              return `
                <div class="rounded-xl border border-slate-100 bg-white px-2 py-1.5 shadow-sm">
                  <div class="text-[10px] font-bold text-slate-700 truncate">${p.titulo}</div>
                  <div class="text-[9px] text-slate-400 mt-0.5 flex items-center justify-between">
                    <span>${p.cliente ?? 'Sin Cliente'}</span>
                    <a href="${googleLink}" target="_blank" class="text-lime-600 font-bold">+</a>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>`);
      }
    }
    document.getElementById('prevMonth')?.addEventListener('click', ()=>{ calDate.setMonth(calDate.getMonth()-1); renderCalendar(projects); });
    document.getElementById('nextMonth')?.addEventListener('click', ()=>{ calDate.setMonth(calDate.getMonth()+1); renderCalendar(projects); });

    loadGlobalTasksCollapsedProjects();
    loadData();
  </script>
@endsection
