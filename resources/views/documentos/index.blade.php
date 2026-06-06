@extends('layouts.app')
@section('title','Documentos')
@section('content')
<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
@php
  $currentSpace = in_array(($space ?? 'personal'), ['client', 'personal', 'clientes'], true) ? $space : 'personal';
  $isClientSpace = $currentSpace === 'client';

  $fmtBytes = function($bytes){
    if (!is_numeric($bytes) || $bytes <= 0) return '—';
    $units = ['B','KB','MB','GB'];
    $i = 0;
    $v = (float) $bytes;
    while ($v >= 1024 && $i < count($units)-1) { $v /= 1024; $i++; }
    return number_format($v, $i === 0 ? 0 : 1).' '.$units[$i];
  };

  $docIcons = [
    'pdf'  => ['bg'=>'bg-rose-50','color'=>'text-rose-500'],
    'drive'=> ['bg'=>'bg-sky-50','color'=>'text-sky-500'],
    'jpg'  => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
    'jpeg' => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
    'png'  => ['bg'=>'bg-amber-50','color'=>'text-amber-500'],
    'xlsx' => ['bg'=>'bg-emerald-50','color'=>'text-emerald-500'],
    'xls'  => ['bg'=>'bg-emerald-50','color'=>'text-emerald-500'],
    'doc'  => ['bg'=>'bg-indigo-50','color'=>'text-indigo-500'],
    'docx' => ['bg'=>'bg-indigo-50','color'=>'text-indigo-500'],
    'mp3'  => ['bg'=>'bg-violet-50','color'=>'text-violet-500'],
    'mp4'  => ['bg'=>'bg-purple-50','color'=>'text-purple-500'],
    'zip'  => ['bg'=>'bg-orange-50','color'=>'text-orange-500'],
  ];

  $getIcon = function($ext, $storage) use ($docIcons) {
    if ($storage === 'drive') return $docIcons['drive'];
    return $docIcons[strtolower((string) $ext)] ?? ['bg'=>'bg-slate-100','color'=>'text-slate-500'];
  };

  $previewableImages = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
  $extensionTone = function($ext, $storage = 'local') {
    $ext = strtolower((string) $ext);
    if ($storage === 'drive') return ['bg' => '#e0f2fe', 'fg' => '#0284c7', 'label' => 'DRIVE'];
    return match ($ext) {
      'pdf' => ['bg' => '#fee2e2', 'fg' => '#dc2626', 'label' => 'PDF'],
      'doc', 'docx' => ['bg' => '#dbeafe', 'fg' => '#2563eb', 'label' => strtoupper($ext)],
      'xls', 'xlsx', 'csv' => ['bg' => '#dcfce7', 'fg' => '#059669', 'label' => strtoupper($ext)],
      'ppt', 'pptx' => ['bg' => '#ffedd5', 'fg' => '#ea580c', 'label' => strtoupper($ext)],
      'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => ['bg' => '#fef3c7', 'fg' => '#f59e0b', 'label' => strtoupper($ext ?: 'IMG')],
      'zip', 'rar', '7z' => ['bg' => '#ffedd5', 'fg' => '#f97316', 'label' => strtoupper($ext)],
      'mp3', 'wav', 'm4a' => ['bg' => '#ede9fe', 'fg' => '#7c3aed', 'label' => strtoupper($ext)],
      'mp4', 'mov', 'avi' => ['bg' => '#f3e8ff', 'fg' => '#9333ea', 'label' => strtoupper($ext)],
      default => ['bg' => '#e2e8f0', 'fg' => '#475569', 'label' => strtoupper($ext ?: 'FILE')],
    };
  };

  $folderIcon = function($name) {
    $normalized = Str::of((string) $name)->lower()->ascii()->trim();
    if ($normalized->contains('factura')) {
      return 'lucide:receipt-text';
    }
    if ($normalized->contains('proyecto')) {
      return 'lucide:zap';
    }
    return null;
  };

  $isProtectedFolderLabel = function($name) {
    $parts = collect(explode('/', str_replace('\\', '/', (string) $name)))
      ->map(fn($p) => trim((string) $p))
      ->filter(fn($p) => $p !== '')
      ->values();
    if ($parts->count() !== 1) {
      return false;
    }
    return in_array(Str::lower(Str::ascii((string) $parts->first())), ['facturas', 'proyectos', 'clientes'], true);
  };

  $selectedClient = null;
  if (!empty($clienteId)) {
    $selectedClient = $clienteLookup->get($clienteId);
  }

  $activeFolderName = $folder !== '' ? $folder : 'Carpetas';
  $folderPresetColors = ['#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#64748b'];

  $folderMetaByName = collect($folders ?? [])->keyBy(function ($f) {
    return strtolower((string) ($f['name'] ?? ''));
  });
  $currentFolderColor = '#0ea5e9';
  if (!empty($folder)) {
    $folderMeta = $folderMetaByName->get(strtolower((string) $folder), []);
    if (!empty($folderMeta['color'])) {
      $currentFolderColor = $folderMeta['color'];
    }
  }
@endphp

<style>
  .finder-surface {
    background: radial-gradient(circle at 20% -30%, rgba(195,251,127,.35), transparent 45%),
                radial-gradient(circle at 100% 10%, rgba(255,255,255,.85), transparent 45%),
                #f5f7fb;
  }
  .mac-folder-card {
    position: relative;
    border-radius: 24px;
    padding: 8px 8px 10px;
    min-height: 158px;
    background: transparent;
    border: 1px solid transparent;
    --folder-color: #0ea5e9;
    --folder-shadow: rgba(14, 165, 233, .18);
    transition: transform .18s ease, filter .18s ease;
  }
  .mac-folder-card:hover,
  .mac-folder-card.is-active {
    transform: translateY(-2px);
    filter: drop-shadow(0 18px 24px rgba(15,23,42,.08));
  }
  .mac-folder-card > .absolute.top-3.right-3 {
    opacity: 0;
    transform: translateY(-4px);
    transition: opacity .16s ease, transform .16s ease;
  }
  .mac-folder-card:hover > .absolute.top-3.right-3 {
    opacity: 1;
    transform: translateY(0);
  }
  .mac-fav {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    background: rgba(255,255,255,.95);
    border: 1px solid #dbe5f2;
    color: #8aa0ba;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 18px rgba(15,23,42,.06);
  }
  .mac-folder-shape {
    width: 108px;
    height: 72px;
    margin: 18px auto 10px;
    position: relative;
    border-radius: 10px 10px 12px 12px;
    background: var(--folder-color);
    background: linear-gradient(180deg, color-mix(in srgb, var(--folder-color) 88%, white 12%) 0%, var(--folder-color) 100%);
    box-shadow: 0 10px 18px var(--folder-shadow), inset 0 1px rgba(255,255,255,.28);
  }
  .mac-folder-shape::before {
    content: "";
    position: absolute;
    left: 0;
    top: -9px;
    width: 44px;
    height: 20px;
    border-radius: 9px 9px 0 0;
    background: var(--folder-color);
    background: color-mix(in srgb, var(--folder-color) 88%, white 12%);
  }
  .mac-folder-shape::after {
    content: "";
    position: absolute;
    left: 40px;
    right: 8px;
    top: 0;
    height: 7px;
    border-radius: 0 8px 0 0;
    background: rgba(255,255,255,.20);
  }
  .mac-folder-icon {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,.72);
    color: color-mix(in srgb, var(--folder-color) 34%, white 66%);
    opacity: .95;
  }
  .mac-folder-title {
    min-height: 24px;
    color: #172236;
    font-weight: 800;
    line-height: 1.12;
    text-align: center;
    font-size: 16px;
  }
  .mac-folder-date {
    margin-top: 1px;
    color: #60728b;
    text-align: center;
    font-size: 13px;
    line-height: 1.22;
  }
  .mac-chevron {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: #eef3f8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #8aa0ba;
  }
  .mac-doc-card {
    position: relative;
    min-height: 190px;
    border-radius: 22px;
    background: transparent;
    border: 1px solid transparent;
    padding: 8px 10px 10px;
    --doc-color: #ef4444;
    transition: transform .18s ease, filter .18s ease;
  }
  .mac-doc-card:hover {
    transform: translateY(-2px);
    filter: drop-shadow(0 16px 22px rgba(15,23,42,.08));
  }
  .doc-preview-stage {
    height: 132px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    border-radius: 18px;
  }
  .doc-preview-stage:focus-visible {
    outline: 3px solid #d9ff66;
    outline-offset: 6px;
    border-radius: 18px;
  }
  .doc-preview-image {
    width: 96px;
    height: 116px;
    object-fit: cover;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 14px 26px rgba(15,23,42,.10);
    background: #f8fafc;
  }
  .doc-image-extension {
    position: absolute;
    left: calc(50% - 38px);
    bottom: 13px;
    min-width: 50px;
    border-radius: 8px;
    padding: 6px 9px;
    background: var(--doc-color);
    color: #fff;
    font-size: 11px;
    line-height: 1;
    font-weight: 900;
    text-align: center;
    box-shadow: 0 8px 16px rgba(15,23,42,.14);
  }
  .doc-file-figure {
    position: relative;
    width: 96px;
    height: 118px;
    border-radius: 14px 8px 14px 14px;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    box-shadow: 0 12px 20px rgba(15,23,42,.10);
    overflow: hidden;
  }
  .doc-file-figure::before {
    content: "";
    position: absolute;
    right: 0;
    top: 0;
    width: 34px;
    height: 34px;
    border-radius: 0 8px 0 12px;
    background: linear-gradient(135deg, #dbe5f0 0%, #eef4fb 54%, #d7e1ec 55%);
  }
  .doc-extension-badge {
    position: absolute;
    left: 12px;
    top: 50px;
    border-radius: 7px;
    min-width: 66px;
    padding: 7px 9px;
    color: #fff;
    font-weight: 900;
    font-size: 15px;
    line-height: 1;
    letter-spacing: 0;
    box-shadow: 0 8px 18px rgba(15,23,42,.12);
    text-align: center;
  }
  .doc-lines {
    position: absolute;
    left: 23px;
    right: 20px;
    bottom: 20px;
    display: grid;
    gap: 6px;
  }
  .doc-lines span {
    height: 5px;
    border-radius: 999px;
    background: var(--doc-color);
    opacity: .48;
  }
  .doc-lines span:nth-child(2) { width: 82%; }
  .doc-lines span:nth-child(3) { width: 66%; }
  .doc-cloud {
    position: absolute;
    right: 10px;
    bottom: 10px;
    color: #0ea5e9;
  }
  .doc-card-title {
    color: #172236;
    font-size: 12px;
    line-height: 1.16;
    font-weight: 800;
    min-height: 28px;
    text-align: center;
    max-width: 118px;
  }
  .doc-card-date {
    margin-top: 2px;
    color: #60728b;
    font-size: 13px;
    line-height: 1.22;
    text-align: center;
  }
  .invoice-status-tag {
    position: absolute;
    right: 5px;
    bottom: 7px;
    border-radius: 999px;
    padding: 3px 6px;
    font-size: 9px;
    line-height: 1;
    font-weight: 900;
    max-width: calc(100% - 10px);
    white-space: nowrap;
    box-shadow: 0 8px 18px rgba(15,23,42,.14);
    border: 1px solid rgba(255,255,255,.82);
    background: #f1f5f9;
    color: #475569;
  }
  .invoice-status-tag.is-paid {
    background: #d1fae5;
    color: #047857;
  }
  .invoice-status-tag.is-partial {
    background: #e0e7ff;
    color: #4338ca;
  }
  .invoice-status-tag.is-overdue {
    background: #fee2e2;
    color: #dc2626;
  }
  .invoice-status-tag.is-pending {
    background: #fef3c7;
    color: #b45309;
  }
  .doc-title-row {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    margin-top: 2px;
  }
  .doc-title-chevron {
    display: none;
  }
  .preview-frame {
    width: 100%;
    height: min(62vh, 620px);
    border: 0;
    background: #f8fafc;
    transform-origin: 0 0;
  }
  .preview-image {
    max-width: 100%;
    max-height: min(62vh, 620px);
    object-fit: contain;
    margin: 0 auto;
    transform-origin: center center;
  }
  .preview-zoom-shell {
    height: min(62vh, 620px);
    overflow: auto;
    overscroll-behavior: contain;
    touch-action: none;
  }
  .preview-zoom-content {
    transform-origin: center center;
    transition: transform .08s ease-out;
  }
  .unsupported-preview-card {
    width: min(360px, 92vw);
    border-radius: 22px;
    background: white;
    border: 1px solid #dbe5f2;
    box-shadow: 0 18px 42px rgba(15,23,42,.10);
    padding: 28px;
    text-align: center;
  }
  .unsupported-preview-file {
    position: relative;
    width: 96px;
    height: 118px;
    margin: 0 auto 18px;
    border-radius: 14px 8px 14px 14px;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    box-shadow: 0 12px 20px rgba(15,23,42,.10);
    overflow: hidden;
  }
  .unsupported-preview-file::before {
    content: "";
    position: absolute;
    right: 0;
    top: 0;
    width: 34px;
    height: 34px;
    border-radius: 0 8px 0 12px;
    background: linear-gradient(135deg, #dbe5f0 0%, #eef4fb 54%, #d7e1ec 55%);
  }
  .upload-progress-panel {
    width: min(360px, calc(100vw - 32px));
    border-radius: 22px;
    border: 1px solid #dbe5f2;
    background: rgba(255,255,255,.96);
    box-shadow: 0 22px 54px rgba(15,23,42,.16);
    backdrop-filter: blur(10px);
  }
  .upload-progress-item {
    display: grid;
    grid-template-columns: 34px 1fr;
    gap: 10px;
    align-items: center;
    border-radius: 16px;
    background: #f8fafc;
    padding: 10px;
  }
  .upload-file-ghost {
    width: 34px;
    height: 42px;
    border-radius: 8px 4px 8px 8px;
    background: linear-gradient(180deg, #eef4fb, #e2e8f0);
    position: relative;
    overflow: hidden;
  }
  .upload-file-ghost::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent);
    transform: translateX(-100%);
    animation: upload-shimmer 1.2s ease-in-out infinite;
  }
  .upload-spinner {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    border: 2px solid #dbe5f2;
    border-top-color: #84cc16;
    animation: upload-spin .75s linear infinite;
  }
  @keyframes upload-spin { to { transform: rotate(360deg); } }
  @keyframes upload-shimmer { to { transform: translateX(100%); } }
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  @media (max-width: 640px) {
    .mac-folder-card {
      min-height: 150px;
      padding-inline: 10px;
    }
    .mac-folder-shape {
      width: 102px;
      height: 68px;
    }
    .mac-folder-title {
      font-size: 18px;
    }
    .mac-doc-card {
      padding: 14px;
      min-height: 260px;
    }
  }
</style>

<form id="dnd-form" method="POST" action="{{ route('documentos.upload') }}" enctype="multipart/form-data" class="hidden">
  @csrf
  <input type="hidden" name="scope" id="dnd_scope" value="{{ $currentSpace }}">
  <input type="hidden" name="storage_mode" value="local">
  <input type="hidden" name="cliente_id" id="dnd_cliente_id" value="{{ $clienteId ?? '' }}">
  <input type="hidden" name="folder" id="dnd_folder" value="{{ $folder ?? '' }}">
  <input type="file" name="archivo" id="dnd_file_input">
</form>

<form id="download-folder-form" method="GET" action="{{ route('documentos.folders.download') }}" class="hidden">
  <input type="hidden" name="scope" id="df_scope">
  <input type="hidden" name="cliente_id" id="df_cliente_id">
  <input type="hidden" name="name" id="df_name">
</form>

<div id="upload-progress-panel" class="upload-progress-panel fixed right-4 bottom-4 z-[1700] hidden">
  <div class="p-4 border-b border-slate-100 flex items-center justify-between">
    <div>
      <div class="text-sm font-black text-slate-900">Subiendo archivos</div>
      <div class="text-xs text-slate-500" id="upload-progress-summary">Preparando...</div>
    </div>
    <div class="upload-spinner" aria-hidden="true"></div>
  </div>
  <div id="upload-progress-list" class="p-3 space-y-2 max-h-72 overflow-y-auto"></div>
</div>

<div class="finder-surface rounded-[28px] border border-white/70 shadow-[0_20px_45px_rgba(15,23,42,.08)] p-3 sm:p-4">
  <div class="bg-white/85 backdrop-blur rounded-[24px] border border-slate-100 overflow-hidden">
    <div class="border-b border-slate-100 bg-white/80 px-4 sm:px-6 py-3 flex items-center justify-between gap-3 flex-wrap">
    </div>

    <div class="min-h-[72vh]">
      <main class="p-4 sm:p-6 space-y-4 bg-gradient-to-b from-white to-slate-50/50">
        @if(session('success'))
          <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm">{{ session('success') }}</div>
        @endif
        @php
          $firstError = $errors->first();
          $isGoogleAuthError = str_contains(strtolower((string) $firstError), 'google no esta autorizada');
        @endphp
        @if($errors->any() && session()->hasOldInput() && !$isGoogleAuthError)
          <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-rose-700 text-sm">{{ $firstError }}</div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 p-3 sm:p-4 flex flex-wrap items-end gap-2">
          <form method="GET" action="{{ route('documentos.index') }}" class="flex flex-wrap lg:flex-nowrap items-end gap-3 w-full">
            <input type="hidden" name="space" id="main_space" value="{{ $currentSpace }}">
            <input type="hidden" name="cliente_id" value="{{ $isClientSpace ? ($clienteId ?? '') : '__personal__' }}">
            <input type="hidden" name="folder" value="{{ $folder ?? '' }}">
            <div class="flex-1 min-w-[220px]">
              <input name="q" value="{{ $q }}" placeholder="Buscar por nombre de archivo..." class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-lime-300 outline-none">
            </div>
            <div class="min-w-[180px]">
              <select name="sort" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm">
                <option value="recent" @selected(($sort ?? 'recent') === 'recent')>Mas recientes</option>
                <option value="name" @selected(($sort ?? '') === 'name')>Nombre</option>
              </select>
            </div>
            <button class="px-6 py-2.5 rounded-2xl bg-lime-300 text-slate-900 font-semibold text-sm whitespace-nowrap">Aplicar</button>
          </form>
        </div>

        <div class="space-y-3">
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="openFolderModal()" class="px-3 py-2 rounded-xl text-xs sm:text-sm bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition-colors">Nueva carpeta</button>
            <button type="button" onclick="triggerFileInput()" class="px-3 py-2 rounded-xl text-xs sm:text-sm bg-lime-300 text-slate-900 font-semibold hover:bg-lime-200 transition-colors">Cargar archivo</button>
          </div>

          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3 text-sm text-slate-500">
              @if(!empty($folder))
                <a href="{{ route('documentos.index', array_filter(['space' => $currentSpace, 'cliente_id' => $space === 'client' && !empty($clienteId) ? $clienteId : null])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                  Atras
                </a>
              @endif
              <a href="{{ route('documentos.index', ['space' => 'personal']) }}" class="font-semibold text-slate-800 hover:text-slate-900 cursor-pointer">Documentos</a>
              <span>/</span>
              @if($space === 'clientes')
                <a href="{{ route('documentos.index', ['space' => 'clientes']) }}" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">Clientes</a>
              @elseif($space === 'client')
                <a href="{{ route('documentos.index', ['space' => 'clientes']) }}" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">Clientes</a>
                @if(!empty($clienteId) && $selectedClient)
                  <span>/</span>
                  <a href="{{ route('documentos.index', ['space' => 'client', 'cliente_id' => $clienteId]) }}" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">{{ $selectedClient['empresa'] ?? 'Cliente' }}</a>
                @endif
              @else
                <a href="{{ route('documentos.index', ['space' => 'personal']) }}" class="text-slate-600 hover:text-slate-900 font-medium cursor-pointer">Mis carpetas</a>
              @endif
              @if(!empty($folder))
                <span>/</span>
                <span class="text-slate-700 font-medium">{{ $activeFolderName }}</span>
              @endif
            </div>
            <div class="text-xs text-slate-400">{{ $isClientSpace ? 'Visible para cliente en su portal' : 'Solo visible para equipo interno' }}</div>
          </div>

          <div class="flex items-center justify-between">
            <div></div>
            <div class="flex items-center gap-2">
              <button type="button" onclick="setFolderView('grid')" id="btn-folder-grid" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600" title="Carpetas en cuadricula">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
              </button>
              <button type="button" onclick="setFolderView('list')" id="btn-folder-list" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600" title="Carpetas en lista">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
              </button>
            </div>
          </div>

          <div id="folders-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @if($showingInitial ?? false)
              <!-- Tarjeta Clientes especial en vista inicial -->
              <div onclick="navigateWithScroll('{{ route('documentos.index', ['space' => 'clientes']) }}')"
                class="mac-folder-card cursor-pointer" style="--folder-color: #0ea5e9; --folder-shadow: rgba(14, 165, 233, .18);">
                <div class="mac-folder-shape">
                  <div class="mac-folder-icon">
                    <iconify-icon icon="lucide:users" width="34" height="34" aria-hidden="true"></iconify-icon>
                  </div>
                </div>
                <div class="px-1">
                  <div class="mac-folder-title">Clientes</div>
                  <div class="mac-folder-date">{{ count($clientes ?? []) }} elementos</div>
                </div>
              </div>
              
              <!-- Carpetas personales -->
              @forelse(($folderStats ?? collect()) as $idx => $fs)
                @php
                  $isActiveFolder = ($folder ?? '') === ($fs['path'] ?? $fs['name'] ?? '');
                  $folderUrlParams = ['space' => $currentSpace, 'folder' => ($fs['path'] ?? $fs['name'])];
                  if ($isClientSpace && !empty($clienteId)) {
                    $folderUrlParams['cliente_id'] = $clienteId;
                  }
                  $folderColor = strtolower((string) ($fs['color'] ?? '#0ea5e9'));
                  $iconName = $folderIcon($fs['name'] ?? '');
                  $folderPath = (string) ($fs['path'] ?? $fs['name'] ?? '');
                  $canDeleteFolder = !$isProtectedFolderLabel($folderPath);
                @endphp
                    <div onclick="navigateWithScroll('{{ route('documentos.index', $folderUrlParams) }}')"
                      data-dnd-folder-target="{{ $fs['path'] ?? $fs['name'] }}"
                      draggable="true"
                      ondragstart="startFolderDrag(event, '{{ addslashes($fs['path'] ?? $fs['name']) }}')"
                      class="mac-folder-card cursor-pointer {{ $isActiveFolder ? 'is-active' : '' }}" style="--folder-color: {{ $folderColor }}; --folder-shadow: {{ $folderColor }}33;">
                  <div class="absolute top-3 right-3 flex items-center gap-1.5 z-10">
                    <button type="button" onclick="event.stopPropagation();downloadFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="w-7 h-7 rounded-full bg-white border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-50" title="Descargar carpeta como ZIP">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16"/></svg>
                    </button>
                    <button type="button" onclick="event.stopPropagation();openFolderEditModal('{{ addslashes($fs['path'] ?? $fs['name']) }}','{{ $folderColor }}')" class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50" title="Editar carpeta">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @if($canDeleteFolder)
                      <button type="button" onclick="event.stopPropagation();confirmDeleteFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="w-7 h-7 rounded-full bg-white border border-rose-200 flex items-center justify-center text-rose-600 hover:bg-rose-50" title="Eliminar carpeta">
                        <iconify-icon icon="lucide:trash-2" width="14" height="14" aria-hidden="true"></iconify-icon>
                      </button>
                    @endif
                  </div>
                  <div class="mac-folder-shape">
                    <div class="mac-folder-icon">
                      <iconify-icon icon="lucide:user" width="34" height="34" aria-hidden="true"></iconify-icon>
                    </div>
                  </div>
                  <div class="px-1">
                    <div class="mac-folder-title">{{ $fs['name'] }}</div>
                    <div class="mac-folder-date">{{ !empty($fs['updated_at']) ? \Illuminate\Support\Carbon::parse($fs['updated_at'])->format('d M Y, H:i') : number_format($fs['count'] ?? 0).' elementos' }}</div>
                  </div>
                </div>
              @empty
              @endforelse
            @elseif($showingClientes ?? false)
              @forelse(($clienteStats ?? collect()) as $idx => $cs)
                @php
                  $clientColor = '#0ea5e9';
                @endphp
                <div onclick="navigateWithScroll('{{ route('documentos.index', ['space' => 'client', 'cliente_id' => $cs['id']]) }}')"
                  class="mac-folder-card cursor-pointer" style="--folder-color: #0ea5e9; --folder-shadow: rgba(14, 165, 233, .18);">
                  <div class="mac-folder-shape">
                    <div class="mac-folder-icon">
                      <iconify-icon icon="lucide:user" width="34" height="34" aria-hidden="true"></iconify-icon>
                    </div>
                  </div>
                  <div class="px-1">
                    <div class="mac-folder-title">{{ $cs['empresa'] }}</div>
                    <div class="mac-folder-date">{{ number_format($cs['count'] ?? 0) }} elementos</div>
                  </div>
                </div>
              @empty
                <div class="sm:col-span-2 xl:col-span-5 rounded-2xl border border-dashed border-slate-200 bg-white p-5 text-sm text-slate-400">
                  No hay clientes disponibles.
                </div>
              @endforelse
            @else
              @forelse(($folderStats ?? collect()) as $idx => $fs)
                @php
                  $isActiveFolder = ($folder ?? '') === ($fs['path'] ?? $fs['name'] ?? '');
                  $folderUrlParams = ['space' => $currentSpace, 'folder' => ($fs['path'] ?? $fs['name'])];
                  if ($isClientSpace && !empty($clienteId)) {
                    $folderUrlParams['cliente_id'] = $clienteId;
                  }
                  $folderColor = strtolower((string) ($fs['color'] ?? '#0ea5e9'));
                  $iconName = $folderIcon($fs['name'] ?? '');
                  $folderPath = (string) ($fs['path'] ?? $fs['name'] ?? '');
                  $canDeleteFolder = !$isProtectedFolderLabel($folderPath);
                @endphp
                    <div onclick="navigateWithScroll('{{ route('documentos.index', $folderUrlParams) }}')"
                      data-dnd-folder-target="{{ $fs['path'] ?? $fs['name'] }}"
                      draggable="true"
                      ondragstart="startFolderDrag(event, '{{ addslashes($fs['path'] ?? $fs['name']) }}')"
                      class="mac-folder-card cursor-pointer {{ $isActiveFolder ? 'is-active' : '' }}" style="--folder-color: {{ $folderColor }}; --folder-shadow: {{ $folderColor }}33;">
                  <div class="absolute top-3 right-3 flex items-center gap-1.5 z-10">
                    <button type="button" onclick="event.stopPropagation();downloadFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="w-7 h-7 rounded-full bg-white border border-emerald-200 flex items-center justify-center text-emerald-600 hover:bg-emerald-50" title="Descargar carpeta como ZIP">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16"/></svg>
                    </button>
                    @if($isClientSpace && !empty($clienteId))
                      <button type="button" onclick="event.preventDefault();event.stopPropagation();toggleFolderVisibility('{{ addslashes($fs['path'] ?? $fs['name']) }}', {{ ($fs['client_visible'] ?? true) ? 'true' : 'false' }});return false;" class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center {{ ($fs['client_visible'] ?? true) ? 'text-blue-600 hover:bg-blue-50' : 'text-slate-400 hover:bg-slate-50' }}" title="{{ ($fs['client_visible'] ?? true) ? 'Visible en portal cliente' : 'Oculto en portal cliente' }}">
                        @if(($fs['client_visible'] ?? true))
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12 18.5 19.5 12 19.5 1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        @else
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.7 5.1A11.6 11.6 0 0112 4.5C18.5 4.5 22.5 12 22.5 12a17.8 17.8 0 01-3.1 4.2M6.3 6.3C3.4 8.2 1.5 12 1.5 12s4 7.5 10.5 7.5c1.7 0 3.3-.5 4.7-1.2"/></svg>
                        @endif
                      </button>
                    @endif
                    <button type="button" onclick="event.stopPropagation();openFolderEditModal('{{ addslashes($fs['path'] ?? $fs['name']) }}','{{ $folderColor }}')" class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50" title="Editar carpeta">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @if($canDeleteFolder)
                      <button type="button" onclick="event.stopPropagation();confirmDeleteFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="w-7 h-7 rounded-full bg-white border border-rose-200 flex items-center justify-center text-rose-600 hover:bg-rose-50" title="Eliminar carpeta">
                        <iconify-icon icon="lucide:trash-2" width="14" height="14" aria-hidden="true"></iconify-icon>
                      </button>
                    @endif
                  </div>
                  <div class="mac-folder-shape">
                    @if($iconName)
                      <div class="mac-folder-icon">
                        <iconify-icon icon="{{ $iconName }}" width="34" height="34" aria-hidden="true"></iconify-icon>
                      </div>
                    @endif
                  </div>
                  <div class="px-1">
                    <div class="mac-folder-title">{{ $fs['name'] }}</div>
                    <div class="mac-folder-date">{{ !empty($fs['updated_at']) ? \Illuminate\Support\Carbon::parse($fs['updated_at'])->format('d M Y, H:i') : number_format($fs['count'] ?? 0).' elementos' }}</div>
                  </div>
                </div>
              @empty
              @endforelse
            @endif
          </div>

          <div id="folders-list" class="hidden bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                  <th class="text-left px-4 py-3">Nombre</th>
                  <th class="text-left px-4 py-3">Tipo</th>
                  <th class="text-left px-4 py-3">Detalle</th>
                  <th class="text-left px-4 py-3">Fecha</th>
                  <th class="text-right px-4 py-3">Accion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @if($showingInitial ?? false)
                  <!-- Fila de Clientes especial -->
                  <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                      <a href="{{ route('documentos.index', ['space' => 'clientes']) }}" class="text-cyan-600 font-semibold hover:text-cyan-700">Clientes ({{ count($clientes ?? []) }} elementos)</a>
                    </td>
                    <td class="px-4 py-3 text-slate-500">Carpeta</td>
                    <td class="px-4 py-3 text-slate-500">{{ count($clientes ?? []) }} elementos</td>
                    <td class="px-4 py-3 text-slate-400">—</td>
                    <td class="px-4 py-3 text-right">
                      <a class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-lime-100" href="{{ route('documentos.index', ['space' => 'clientes']) }}">Abrir</a>
                    </td>
                  </tr>
                  
                  <!-- Carpetas personales -->
                  @forelse(($folderStats ?? collect()) as $fs)
                    @php
                      $folderUrlParams = ['space' => $currentSpace, 'folder' => ($fs['path'] ?? $fs['name'])];
                      if ($isClientSpace && !empty($clienteId)) {
                        $folderUrlParams['cliente_id'] = $clienteId;
                      }
                      $canDeleteFolder = !$isProtectedFolderLabel((string) ($fs['path'] ?? $fs['name'] ?? ''));
                    @endphp
                    <tr class="hover:bg-slate-50">
                      <td class="px-4 py-3 font-medium text-slate-800">{{ $fs['name'] }}</td>
                      <td class="px-4 py-3 text-slate-500">Carpeta</td>
                      <td class="px-4 py-3 text-slate-500">{{ number_format($fs['count'] ?? 0) }} elementos · {{ $fmtBytes($fs['size'] ?? 0) }}</td>
                      <td class="px-4 py-3 text-slate-400">{{ !empty($fs['updated_at']) ? \Illuminate\Support\Carbon::parse($fs['updated_at'])->format('d/m/Y H:i') : '—' }}</td>
                      <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-lime-100" href="{{ route('documentos.index', $folderUrlParams) }}">Abrir</a>
                        <a class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50" href="{{ route('documentos.folders.download', ['scope' => $currentSpace, 'cliente_id' => $clienteId, 'name' => ($fs['path'] ?? $fs['name'])]) }}">Descargar</a>
                        @if($isClientSpace && !empty($clienteId))
                          <button type="button" onclick="event.preventDefault();event.stopPropagation();toggleFolderVisibility('{{ addslashes($fs['path'] ?? $fs['name']) }}', {{ ($fs['client_visible'] ?? true) ? 'true' : 'false' }});return false;" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-xs font-semibold {{ ($fs['client_visible'] ?? true) ? 'text-blue-600 hover:bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">{{ ($fs['client_visible'] ?? true) ? 'Visible cliente' : 'Oculta cliente' }}</button>
                        @endif
                        <button type="button" onclick="openFolderEditModal('{{ addslashes($fs['path'] ?? $fs['name']) }}','{{ strtolower((string) ($fs['color'] ?? '#0ea5e9')) }}')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50">Editar</button>
                        @if($canDeleteFolder)
                          <button type="button" onclick="confirmDeleteFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="px-3 py-1.5 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-100 inline-flex items-center gap-1"><iconify-icon icon="lucide:trash-2" width="13" height="13"></iconify-icon>Eliminar</button>
                        @endif
                      </td>
                    </tr>
                  @empty
                  @endforelse
                @elseif($showingClientes ?? false)
                  @forelse(($clienteStats ?? collect()) as $cs)
                    <tr class="hover:bg-slate-50">
                      <td class="px-4 py-3 font-medium text-slate-800">{{ $cs['empresa'] }}</td>
                      <td class="px-4 py-3 text-slate-500">Cliente</td>
                      <td class="px-4 py-3 text-slate-500">{{ number_format($cs['count'] ?? 0) }} elementos · {{ $fmtBytes($cs['size'] ?? 0) }}</td>
                      <td class="px-4 py-3 text-slate-400">—</td>
                      <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-lime-100" href="{{ route('documentos.index', ['space' => 'client', 'cliente_id' => $cs['id']]) }}">Abrir</a>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No hay clientes.</td></tr>
                  @endforelse
                @else
                  @forelse(($folderStats ?? collect()) as $fs)
                    @php
                      $folderUrlParams = ['space' => $currentSpace, 'folder' => ($fs['path'] ?? $fs['name'])];
                      if ($isClientSpace && !empty($clienteId)) {
                        $folderUrlParams['cliente_id'] = $clienteId;
                      }
                      $canDeleteFolder = !$isProtectedFolderLabel((string) ($fs['path'] ?? $fs['name'] ?? ''));
                    @endphp
                    <tr class="hover:bg-slate-50">
                      <td class="px-4 py-3 font-medium text-slate-800">{{ $fs['name'] }}</td>
                      <td class="px-4 py-3 text-slate-500">Carpeta</td>
                      <td class="px-4 py-3 text-slate-500">{{ number_format($fs['count'] ?? 0) }} elementos · {{ $fmtBytes($fs['size'] ?? 0) }}</td>
                      <td class="px-4 py-3 text-slate-400">{{ !empty($fs['updated_at']) ? \Illuminate\Support\Carbon::parse($fs['updated_at'])->format('d/m/Y H:i') : '—' }}</td>
                      <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-lime-100" href="{{ route('documentos.index', $folderUrlParams) }}">Abrir</a>
                        <a class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50" href="{{ route('documentos.folders.download', ['scope' => $currentSpace, 'cliente_id' => $clienteId, 'name' => ($fs['path'] ?? $fs['name'])]) }}">Descargar</a>
                        <button type="button" onclick="openFolderEditModal('{{ addslashes($fs['path'] ?? $fs['name']) }}','{{ strtolower((string) ($fs['color'] ?? '#0ea5e9')) }}')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50">Editar</button>
                        @if($canDeleteFolder)
                          <button type="button" onclick="confirmDeleteFolder('{{ addslashes($fs['path'] ?? $fs['name']) }}')" class="px-3 py-1.5 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-100 inline-flex items-center gap-1"><iconify-icon icon="lucide:trash-2" width="13" height="13"></iconify-icon>Eliminar</button>
                        @endif
                      </td>
                    </tr>
                  @empty
                  @endforelse
                @endif
                @if(!($showingClientes ?? false))
                  @forelse($documents as $d)
                    @php
                      $ext = strtolower((string) ($d['ext'] ?? ''));
                      $di = $getIcon($ext, $d['storage'] ?? 'local');
                      $tone = $extensionTone($ext, $d['storage'] ?? 'local');
                      if (($d['storage'] ?? '') === 'factura') {
                        $tone = ['bg' => '#e0e7ff', 'fg' => '#4f46e5', 'label' => 'PDF'];
                      }
                      $label = $d['name'] ?? ($d['original_name'] ?? 'Documento');
                      $uploadedAt = !empty($d['uploaded_at']) ? $d['uploaded_at'] : ($d['created_at'] ?? null);
                      $storage = $d['storage'] ?? 'local';
                      $isPreviewImage = $storage === 'local' && in_array($ext, $previewableImages, true);
                      $isPreviewPdf = $storage === 'local' && $ext === 'pdf';
                      $isInvoiceDoc = ($d['storage'] ?? '') === 'factura';
                      $canPreview = $isPreviewImage || $isPreviewPdf || $isInvoiceDoc;
                      $previewUrl = $isInvoiceDoc ? route('facturas.print', $d['source_id']) : ($canPreview ? route('documentos.preview', $d['id']) : null);
                      $previewType = $isPreviewImage ? 'image' : 'pdf';
                      $downloadUrl = $isInvoiceDoc ? route('facturas.download', $d['source_id']) : route('documentos.download', $d['id']);
                      $previewTitle = $isInvoiceDoc ? (string) ($d['invoice_number'] ?? $d['source_id'] ?? $label) : $label;
                    @endphp
                    <tr class="hover:bg-slate-50">
                      <td class="px-4 py-3 font-medium text-slate-800 max-w-[260px] truncate" title="{{ $label }}">{{ $label }}</td>
                      <td class="px-4 py-3">
                        <span class="text-xs font-bold {{ ($d['storage'] ?? '') === 'factura' ? 'text-indigo-600' : $di['color'] }}">{{ ($d['storage'] ?? '') === 'factura' ? 'FACTURA' : (($d['storage'] ?? '') === 'drive' ? 'DRIVE' : strtoupper($ext ?: 'FILE')) }}</span>
                      </td>
                      <td class="px-4 py-3 text-slate-500">
                        {{ ($d['storage'] ?? '') === 'factura' ? 'PDF generado' : $fmtBytes($d['size'] ?? null) }}
                        @if(($d['storage'] ?? '') === 'factura')
                          <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ ($d['estado_factura_visual'] ?? $d['estado_factura'] ?? '') === 'Pagada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $d['estado_factura_visual'] ?? $d['estado_factura'] ?? 'Pendiente' }}</span>
                        @endif
                      </td>
                      <td class="px-4 py-3 text-slate-400">{{ $uploadedAt ? \Illuminate\Support\Carbon::parse($uploadedAt)->format('d/m/Y H:i') : '—' }}</td>
                      <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ $downloadUrl }}" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50" @if(($d['storage'] ?? '') === 'drive') target="_blank" @endif>Descargar</a>
                        @if(($d['storage'] ?? '') !== 'factura')
                          <button type="button" onclick="openFileEditModal('{{ $d['id'] }}','{{ addslashes($label) }}','{{ addslashes($d['folder'] ?? ($folder ?? '')) }}')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 inline-flex items-center gap-1"><iconify-icon icon="lucide:pencil" width="13" height="13"></iconify-icon>Editar</button>
                          <form method="POST" action="{{ route('documentos.destroy', $d['id']) }}" class="inline" onsubmit="return confirm('¿Eliminar documento?')">
                            @csrf @method('DELETE')
                            <button class="px-3 py-1.5 rounded-full bg-rose-50 text-rose-600 text-xs font-semibold hover:bg-rose-100 inline-flex items-center gap-1"><iconify-icon icon="lucide:trash-2" width="13" height="13"></iconify-icon>Eliminar</button>
                          </form>
                        @endif
                      </td>
                    </tr>
                  @empty
                    @if(($folderStats ?? collect())->isEmpty() && !($showingInitial ?? false))
                      <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No hay elementos.</td></tr>
                    @endif
                  @endforelse
                @endif
              </tbody>
            </table>
          </div>
        </div>

        <input type="file" id="quick-file-input" class="hidden" multiple onchange="handleQuickFileSelect(this)">

        <div id="view-grid">
          @if($documents->isEmpty())
            @if(!empty($folder) && ($folderStats ?? collect())->isEmpty())
              <div class="bg-white rounded-2xl border border-dashed border-slate-200 p-16 text-center text-slate-400">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 mx-auto mb-3 flex items-center justify-center">
                  <svg class="w-7 h-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <div class="font-semibold text-slate-700">No hay documentos en esta vista</div>
                <div class="text-xs mt-1">Crea carpeta o sube tu primer archivo.</div>
              </div>
            @endif
          @else
            <div class="grid grid-cols-3 md:grid-cols-5 xl:grid-cols-7 gap-x-5 gap-y-8">
              @foreach($documents as $d)
                @php
                  $ext = strtolower((string) ($d['ext'] ?? ''));
                  $di = $getIcon($ext, $d['storage'] ?? 'local');
                  $tone = $extensionTone($ext, $d['storage'] ?? 'local');
                  if (($d['storage'] ?? '') === 'factura') {
                    $tone = ['bg' => '#e0e7ff', 'fg' => '#4f46e5', 'label' => 'PDF'];
                  }
                  $label = $d['name'] ?? ($d['original_name'] ?? 'Documento');
                  $clientName = !empty($d['cliente_id']) ? (($clienteLookup->get($d['cliente_id'])['empresa'] ?? 'Cliente')) : 'Personal';
                  $extLabel = ($d['storage'] ?? 'local') === 'drive' ? 'DRIVE' : strtoupper($ext ?: 'FILE');
                  $uploadedBy = $d['uploaded_by'] ?? '—';
                  $uploadedAt = !empty($d['uploaded_at']) ? $d['uploaded_at'] : ($d['created_at'] ?? null);
                  $storage = $d['storage'] ?? 'local';
                  $isPreviewImage = $storage === 'local' && in_array($ext, $previewableImages, true);
                  $isPreviewPdf = $storage === 'local' && $ext === 'pdf';
                  $isInvoiceDoc = ($d['storage'] ?? '') === 'factura';
                  $canPreview = $isPreviewImage || $isPreviewPdf || $isInvoiceDoc;
                  $previewUrl = $isInvoiceDoc ? route('facturas.print', $d['source_id']) : ($canPreview ? route('documentos.preview', $d['id']) : null);
                  $previewType = $isPreviewImage ? 'image' : 'pdf';
                  $downloadUrl = $isInvoiceDoc ? route('facturas.download', $d['source_id']) : route('documentos.download', $d['id']);
                  $previewTitle = $isInvoiceDoc ? (string) ($d['invoice_number'] ?? $d['source_id'] ?? $label) : $label;
                  $invoiceStatusLabel = (string) ($d['estado_factura_visual'] ?? $d['estado_factura'] ?? '');
                  $invoiceStatusKey = Str::lower(Str::ascii($invoiceStatusLabel));
                  $invoiceStatusClass = str_contains($invoiceStatusKey, 'vencid') ? 'is-overdue'
                    : (str_contains($invoiceStatusKey, 'pagad') ? 'is-paid'
                    : (str_contains($invoiceStatusKey, 'parcial') ? 'is-partial' : 'is-pending'));
                @endphp
                <div class="mac-doc-card group" draggable="true" ondragstart="startDocumentDrag(event, '{{ $d['id'] }}')" style="--doc-color: {{ $tone['fg'] }};">
                  <div class="absolute top-4 right-4 z-10 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if(($d['storage'] ?? '') === 'factura')
                      <a href="{{ route('facturas.download', $d['source_id']) }}" class="w-8 h-8 rounded-full border border-emerald-200 bg-white flex items-center justify-center text-emerald-600 hover:bg-emerald-50" title="Descargar PDF">
                        <iconify-icon icon="lucide:download" width="16" height="16" aria-hidden="true"></iconify-icon>
                      </a>
                    @else
                      <a href="{{ route('documentos.download', $d['id']) }}" class="w-8 h-8 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-lime-50" title="Descargar" @if(($d['storage'] ?? '') === 'drive') target="_blank" @endif>
                        <iconify-icon icon="lucide:download" width="16" height="16" aria-hidden="true"></iconify-icon>
                      </a>
                      <button type="button" onclick="openFileEditModal('{{ $d['id'] }}','{{ addslashes($label) }}','{{ addslashes($d['folder'] ?? ($folder ?? '')) }}')" class="w-8 h-8 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 hover:bg-lime-50" title="Renombrar o mover">
                        <iconify-icon icon="lucide:pencil" width="15" height="15" aria-hidden="true"></iconify-icon>
                      </button>
                      <form method="POST" action="{{ route('documentos.destroy', $d['id']) }}" onsubmit="return confirm('¿Eliminar documento?')" class="inline-flex">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 rounded-full border border-rose-200 bg-white flex items-center justify-center text-rose-600 hover:bg-rose-50" title="Eliminar documento">
                          <iconify-icon icon="lucide:trash-2" width="15" height="15" aria-hidden="true"></iconify-icon>
                        </button>
                      </form>
                    @endif
                  </div>

                  <div class="doc-preview-stage" role="button" tabindex="0" title="{{ $canPreview ? 'Ver vista previa' : 'Descargar archivo' }}" onclick="{{ $canPreview ? "openDocumentPreview('".addslashes($previewTitle)."', '".$previewUrl."', '".$previewType."', '".$downloadUrl."', '".$tone['label']."', '".$tone['fg']."')" : "openUnsupportedPreview('".addslashes($previewTitle)."', '".$downloadUrl."', '".$tone['label']."', '".$tone['fg']."')" }}" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); this.click(); }">
                    @if($isPreviewImage)
                      <img src="{{ $previewUrl }}" class="doc-preview-image" alt="{{ $label }}">
                      <div class="doc-image-extension">{{ $tone['label'] }}</div>
                    @else
                      <div class="doc-file-figure">
                        <div class="doc-extension-badge" style="background: {{ $tone['fg'] }}">{{ $tone['label'] }}</div>
                        <div class="doc-lines" aria-hidden="true"><span></span><span></span><span></span></div>
                        @if(($d['storage'] ?? '') === 'drive')
                          <iconify-icon icon="lucide:cloud" class="doc-cloud" width="18" height="18" aria-hidden="true"></iconify-icon>
                        @endif
                        @if($isInvoiceDoc && $invoiceStatusLabel !== '')
                          <span class="invoice-status-tag {{ $invoiceStatusClass }}">{{ $invoiceStatusLabel }}</span>
                        @endif
                      </div>
                    @endif
                  </div>

                  <div class="doc-title-row">
                    <div class="doc-card-title line-clamp-2" title="{{ $label }}">{{ $label }}</div>
                    <iconify-icon icon="lucide:chevron-down" class="doc-title-chevron" width="17" height="17" aria-hidden="true"></iconify-icon>
                  </div>
                  <div class="doc-card-date">{{ $uploadedAt ? \Illuminate\Support\Carbon::parse($uploadedAt)->format('d M Y, H:i') : '—' }}</div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <div id="view-list" class="hidden">
          <div class="bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                  <th class="text-left px-4 py-3">Documento</th>
                  <th class="text-left px-4 py-3">Carpeta</th>
                  <th class="text-left px-4 py-3">Espacio</th>
                  <th class="text-left px-4 py-3">Tipo</th>
                  <th class="text-left px-4 py-3">Peso</th>
                  <th class="text-right px-4 py-3">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                @forelse($documents as $d)
                  @php
                    $ext = strtolower((string) ($d['ext'] ?? ''));
                    $di = $getIcon($ext, $d['storage'] ?? 'local');
                    $spaceLabel = !empty($d['cliente_id']) ? (($clienteLookup->get($d['cliente_id'])['empresa'] ?? 'Cliente')) : 'Personal';
                    $uploadedBy = $d['uploaded_by'] ?? '—';
                    $uploadedAt = !empty($d['uploaded_at']) ? $d['uploaded_at'] : ($d['created_at'] ?? null);
                  @endphp
                  <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $d['name'] ?? ($d['original_name'] ?? 'Documento') }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $d['folder'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">
                      {{ $spaceLabel }}
                      @if(($d['storage'] ?? '') === 'factura')
                        <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ ($d['estado_factura'] ?? '') === 'Pagada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $d['estado_factura'] ?? 'Pendiente' }}</span>
                      @endif
                    </td>
                    <td class="px-4 py-3"><span class="text-xs font-bold {{ ($d['storage'] ?? '') === 'factura' ? 'text-indigo-600' : $di['color'] }}">{{ ($d['storage'] ?? '') === 'factura' ? 'FACTURA' : (($d['storage'] ?? '') === 'drive' ? 'DRIVE' : strtoupper($ext ?: 'FILE')) }}</span></td>
                    <td class="px-4 py-3 text-slate-400">
                      {{ ($d['storage'] ?? '') === 'factura' ? 'Auto' : $fmtBytes($d['size'] ?? null) }}
                      <div class="text-[11px]">{{ $uploadedAt ? \Illuminate\Support\Carbon::parse($uploadedAt)->format('d/m/Y H:i') : '—' }}</div>
                      <div class="text-[11px]">{{ $uploadedBy }}</div>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                      @if(($d['storage'] ?? '') === 'factura')
                        <a href="{{ route('facturas.download', $d['source_id']) }}" class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100">Descargar PDF</a>
                      @else
                        <a href="{{ route('documentos.download', $d['id']) }}" class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-lime-100" @if(($d['storage'] ?? '') === 'drive') target="_blank" @endif>Descargar</a>
                        <button type="button" onclick="openFileEditModal('{{ $d['id'] }}','{{ addslashes($d['name'] ?? ($d['original_name'] ?? 'Documento')) }}','{{ addslashes($d['folder'] ?? ($folder ?? '')) }}')" class="px-3 py-1.5 rounded-full bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 inline-flex items-center gap-1"><iconify-icon icon="lucide:pencil" width="13" height="13"></iconify-icon>Editar</button>
                        <form method="POST" action="{{ route('documentos.destroy', $d['id']) }}" class="inline" onsubmit="return confirm('¿Eliminar documento?')">
                          @csrf @method('DELETE')
                          <button class="px-3 py-1.5 rounded-full bg-rose-50 text-rose-600 text-xs font-semibold hover:bg-rose-100 inline-flex items-center gap-1"><iconify-icon icon="lucide:trash-2" width="13" height="13"></iconify-icon>Eliminar</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No hay documentos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</div>

<div id="drag-overlay" class="fixed inset-0 z-[1700] hidden items-center justify-center bg-slate-950/35 backdrop-blur-[2px] p-4 md:pl-[20rem]">
  <div class="w-[min(520px,calc(100vw-2rem))] rounded-[28px] border-2 border-dashed border-lime-300 bg-white/95 px-6 py-7 text-center shadow-2xl ring-1 ring-white/70">
    <div class="w-14 h-14 rounded-2xl bg-lime-100 mx-auto mb-4 flex items-center justify-center">
      <svg class="w-7 h-7 text-lime-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-5l-4-4m0 0l-4 4m4-4v12"/></svg>
    </div>
    <div class="text-xl font-black text-slate-900">Suelta para cargar</div>
    <div class="text-sm text-slate-500 mt-1">Se cargara en la carpeta activa.</div>
  </div>
</div>

<form id="folder-delete-form" method="POST" action="{{ route('documentos.folders.destroy') }}" class="hidden">
  @csrf
  @method('DELETE')
  <input type="hidden" name="scope" value="{{ $currentSpace }}">
  <input type="hidden" name="cliente_id" value="{{ $clienteId ?? '' }}">
  <input type="hidden" name="name" id="delete_folder_name" value="">
</form>

<div id="modal-file-edit" class="fixed inset-0 bg-black/40 z-[1600] hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-slate-900">Editar archivo</div>
      <button type="button" onclick="closeModal('modal-file-edit')" class="text-slate-400 hover:text-slate-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" id="file-edit-form" class="p-5 space-y-4">
      @csrf
      @method('PUT')
      <div>
        <label class="text-sm font-medium text-slate-700">Nombre del archivo</label>
        <input name="name" id="file_edit_name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700">Mover a carpeta</label>
        <select name="folder" id="file_edit_folder" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
          @foreach(($dropTargetFolders ?? collect()) as $targetFolder)
            @php
              $targetFolderLabel = is_array($targetFolder)
                ? (string) ($targetFolder['path'] ?? $targetFolder['name'] ?? '')
                : (string) $targetFolder;
            @endphp
            @if($targetFolderLabel !== '')
              <option value="{{ $targetFolderLabel }}">{{ $targetFolderLabel }}</option>
            @endif
          @endforeach
          @if(!empty($folder))
            <option value="{{ $folder }}">{{ $folder }}</option>
          @endif
        </select>
        <div class="text-xs text-slate-500 mt-1">Cambia el destino sin duplicar el archivo.</div>
      </div>
      <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold text-sm">Guardar cambios</button>
    </form>
  </div>
</div>

<div id="modal-doc-preview" class="fixed inset-0 bg-black/50 z-[1600] hidden items-center justify-center p-3 sm:p-5">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="font-bold text-slate-900 truncate" id="doc-preview-title">Vista previa del documento</div>
        <div class="text-xs text-slate-400" id="doc-preview-subtitle">PDF e imágenes se muestran sin descargar.</div>
      </div>
      <div class="flex items-center gap-2">
        <a id="doc-preview-download" href="#" class="hidden w-9 h-9 rounded-full border border-emerald-200 bg-white flex items-center justify-center text-emerald-600 hover:bg-emerald-50" title="Descargar">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16"/></svg>
        </a>
        <button type="button" onclick="closeModal('modal-doc-preview')" class="w-9 h-9 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50" title="Cerrar">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
    <div id="doc-preview-zoom-shell" class="preview-zoom-shell bg-slate-100 p-3 sm:p-4">
      <div id="doc-preview-zoom-content" class="preview-zoom-content">
        <iframe id="doc-preview-frame" class="preview-frame rounded-xl hidden" title="Vista previa del documento"></iframe>
        <div id="doc-preview-image-wrap" class="hidden min-h-[320px] rounded-xl bg-white flex items-center justify-center p-4">
          <img id="doc-preview-image" class="preview-image" alt="Vista previa del documento">
        </div>
        <div id="doc-preview-unsupported" class="hidden min-h-[320px] rounded-xl bg-slate-100 flex items-center justify-center p-4">
          <div class="unsupported-preview-card">
            <div class="unsupported-preview-file">
              <div id="unsupported-preview-ext" class="doc-extension-badge">FILE</div>
              <div class="doc-lines" aria-hidden="true"><span></span><span></span><span></span></div>
            </div>
            <div class="font-bold text-slate-900 text-lg">Vista previa no disponible</div>
            <div class="text-sm text-slate-500 mt-1">Este formato no se puede mostrar con el visor nativo.</div>
            <a id="unsupported-preview-download" href="#" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-lime-300 px-5 py-3 font-bold text-slate-900 hover:bg-lime-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4M4 21h16"/></svg>
              Descargar
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="px-4 py-3 border-t bg-white">
      <div class="text-center font-bold text-slate-900 truncate" id="doc-preview-footer-title">Documento</div>
    </div>
  </div>
</div>

<div id="modal-folder-edit" class="fixed inset-0 bg-black/40 z-[1600] hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-slate-900">Editar carpeta</div>
      <button type="button" onclick="closeModal('modal-folder-edit')" class="text-slate-400 hover:text-slate-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('documentos.folders.update') }}" class="p-5 space-y-4">
      @csrf
      @method('PUT')
      <input type="hidden" name="scope" value="{{ $currentSpace }}">
      <input type="hidden" name="cliente_id" value="{{ $clienteId ?? '' }}">
      <input type="hidden" name="current_name" id="folder_edit_current" value="">
      <div>
        <label class="text-sm font-medium text-slate-700">Nuevo nombre</label>
        <input name="name" id="folder_edit_name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required>
      </div>
      <div>
        <label class="text-sm font-medium text-slate-700">Color de carpeta</label>
        <input type="hidden" name="color" id="folder_edit_color" value="{{ $currentFolderColor }}">
        <div class="mt-2 flex items-center gap-2 flex-wrap" id="folder_edit_palette">
          @foreach($folderPresetColors as $presetColor)
            <button type="button" class="folder-color-btn w-7 h-7 rounded-full border-2 border-white ring-1 ring-slate-300" data-target="folder_edit_color" data-color="{{ $presetColor }}" style="background-color: {{ $presetColor }};"></button>
          @endforeach
        </div>
      </div>
      <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold text-sm">Guardar cambios</button>
    </form>
  </div>
</div>

<div id="modal-upload" class="fixed inset-0 bg-black/40 z-[1600] hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-slate-900">Cargar documento</div>
      <button type="button" onclick="closeModal('modal-upload')" class="text-slate-400 hover:text-slate-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('documentos.upload') }}" enctype="multipart/form-data" class="p-5 space-y-4" id="upload-form">
      @csrf
      <input type="hidden" name="scope" id="mu_scope" value="{{ $currentSpace }}">
      <input type="hidden" name="storage_mode" value="local">
      <input type="hidden" name="cliente_id" id="mu_cliente" value="{{ $isClientSpace ? ($clienteId ?? '') : '' }}">
      <input type="hidden" name="folder_color" id="mu_folder_color" value="{{ $currentFolderColor }}">

      <div>
        <label class="text-sm font-medium text-slate-700">Ubicacion actual</label>
        <div class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm bg-slate-50 text-slate-700">
          {{ $isClientSpace ? 'Clientes'.(!empty($selectedClient['empresa']) ? ' / '.$selectedClient['empresa'] : '') : 'Personal' }}
        </div>
      </div>

      <div>
        <label class="text-sm font-medium text-slate-700">Carpeta</label>
        <input name="folder" id="mu_folder" value="{{ $folder ?? '' }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm {{ !empty($folder) ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Ej: Contratos 2026" @if(!empty($folder)) readonly @endif required>
        @if(!empty($folder))
          <div class="text-xs text-slate-500 mt-1">Se cargara en la carpeta actual.</div>
        @endif
      </div>

      @if(empty($folder))
      <div>
        <label class="text-sm font-medium text-slate-700">Color carpeta (si es nueva)</label>
        <input type="color" id="mu_color_picker" value="{{ $currentFolderColor }}" class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-2 py-1 bg-white" onchange="document.getElementById('mu_folder_color').value=this.value">
      </div>
      @endif

      <div>
        <label class="text-sm font-medium text-slate-700">Nombre visible (opcional)</label>
        <input name="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Nombre del documento">
      </div>

      <div>
        <label class="text-sm font-medium text-slate-700">Archivo</label>
        <input type="file" name="archivo" id="modal-file-input" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" multiple required>
      </div>

      <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 text-white font-semibold text-sm">Guardar</button>
    </form>
  </div>
</div>

<div id="modal-folder" class="fixed inset-0 bg-black/40 z-[1600] hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-slate-900">Nueva carpeta</div>
      <button type="button" onclick="closeModal('modal-folder')" class="text-slate-400 hover:text-slate-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <form method="POST" action="{{ route('documentos.folders.store') }}" class="p-5 space-y-4">
      @csrf
      <input type="hidden" name="scope" id="mf_scope" value="{{ $currentSpace }}">

      <div>
        <label class="text-sm font-medium text-slate-700">Espacio</label>
        <select id="mf_scope_select" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" onchange="setFolderScope(this.value)">
          <option value="client" @selected($isClientSpace)>Clientes</option>
          <option value="personal" @selected(!$isClientSpace)>Personal</option>
        </select>
      </div>

      <div id="mf_client_box">
        <label class="text-sm font-medium text-slate-700">Cliente</label>
        <select name="cliente_id" id="mf_cliente" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
          <option value="">Selecciona cliente...</option>
          @foreach($clientes as $c)
            <option value="{{ $c['id'] }}" @selected(($clienteId ?? '') === ($c['id'] ?? ''))>{{ $c['empresa'] ?? 'Cliente' }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="text-sm font-medium text-slate-700">Nombre de carpeta</label>
        <input name="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" required>
      </div>

      <div>
        <label class="text-sm font-medium text-slate-700">Color de carpeta</label>
        <input type="hidden" name="color" id="mf_color" value="{{ $currentFolderColor }}">
        <div class="mt-2 flex items-center gap-2 flex-wrap" id="mf_palette">
          @foreach($folderPresetColors as $presetColor)
            <button type="button" class="folder-color-btn w-7 h-7 rounded-full border-2 border-white ring-1 ring-slate-300" data-target="mf_color" data-color="{{ $presetColor }}" style="background-color: {{ $presetColor }};"></button>
          @endforeach
        </div>
      </div>

      <button type="submit" class="w-full py-3 rounded-xl bg-lime-300 text-slate-900 font-semibold text-sm">Crear carpeta</button>
    </form>
  </div>
</div>

<script>
const savedFileView = localStorage.getItem('fm_files_view') || 'grid';
const savedFolderView = localStorage.getItem('fm_folders_view') || 'grid';
let activeDragItem = null;
const DOC_SCROLL_KEY = 'documentos_scroll_state';
let docPreviewScale = 1;

function setDocPreviewScale(scale) {
  docPreviewScale = Math.min(3, Math.max(0.5, scale));
  const content = document.getElementById('doc-preview-zoom-content');
  if (content) {
    content.style.transform = `scale(${docPreviewScale})`;
    content.style.width = `${100 / docPreviewScale}%`;
  }
}

function resetDocPreviewScale() {
  setDocPreviewScale(1);
}

function getDocumentosScrollContainer() {
  return document.querySelector('main.custom-scroll');
}

function getDocumentosScrollScope() {
  const params = new URLSearchParams(window.location.search || '');
  const space = params.get('space') || '';
  const clienteId = params.get('cliente_id') || '';
  return window.location.pathname + '|space=' + space + '|cliente_id=' + clienteId;
}

function getCurrentScrollTop() {
  const container = getDocumentosScrollContainer();
  if (container) {
    return Math.max(0, container.scrollTop || 0);
  }
  return Math.max(0, window.scrollY || window.pageYOffset || 0);
}

function applyDocumentosScroll(top) {
  const safeTop = Number.isFinite(top) ? Math.max(0, top) : 0;
  const container = getDocumentosScrollContainer();
  if (container) {
    container.scrollTop = safeTop;
  }
  window.scrollTo(0, safeTop);
}

function persistDocumentosScroll() {
  try {
    const container = getDocumentosScrollContainer();
    sessionStorage.setItem(DOC_SCROLL_KEY, JSON.stringify({
      url: window.location.pathname + window.location.search,
      scope: getDocumentosScrollScope(),
      y: getCurrentScrollTop(),
      containerY: container ? Math.max(0, container.scrollTop || 0) : null,
      windowY: Math.max(0, window.scrollY || window.pageYOffset || 0),
    }));
  } catch (_) {}
}

function restoreDocumentosScroll() {
  try {
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
    const raw = sessionStorage.getItem(DOC_SCROLL_KEY);
    if (!raw) return;
    const state = JSON.parse(raw);
    const currentUrl = window.location.pathname + window.location.search;
    const currentScope = getDocumentosScrollScope();
    const matchesScope = !!(state && state.scope && state.scope === currentScope);
    const matchesUrl = !!(state && state.url === currentUrl);
    if (!matchesScope && !matchesUrl) return;
    const top = Number(state.containerY ?? state.y ?? state.windowY ?? 0);
    const windowTop = Number(state.windowY ?? state.y ?? 0);
    requestAnimationFrame(() => {
      applyDocumentosScroll(top);
      window.scrollTo(0, Number.isFinite(windowTop) ? Math.max(0, windowTop) : 0);
      setTimeout(() => {
        applyDocumentosScroll(top);
        window.scrollTo(0, Number.isFinite(windowTop) ? Math.max(0, windowTop) : 0);
      }, 120);
    });
  } catch (_) {}
}

function navigateWithScroll(url) {
  persistDocumentosScroll();
  window.location.href = url;
}

function setView(v) {
  const grid = document.getElementById('view-grid');
  const list = document.getElementById('view-list');
  if (grid) grid.classList.toggle('hidden', v !== 'grid');
  if (list) list.classList.add('hidden');
}

function setFolderView(v) {
  const grid = document.getElementById('folders-grid');
  const list = document.getElementById('folders-list');
  const fileGrid = document.getElementById('view-grid');
  const legacyFileList = document.getElementById('view-list');
  const btnGrid = document.getElementById('btn-folder-grid');
  const btnList = document.getElementById('btn-folder-list');
  if (!grid || !list || !btnGrid || !btnList) return;
  localStorage.setItem('fm_folders_view', v);
  grid.classList.toggle('hidden', v !== 'grid');
  list.classList.toggle('hidden', v !== 'list');
  if (fileGrid) fileGrid.classList.toggle('hidden', v !== 'grid');
  if (legacyFileList) legacyFileList.classList.add('hidden');
  btnGrid.classList.toggle('bg-lime-100', v === 'grid');
  btnList.classList.toggle('bg-lime-100', v === 'list');
}
setFolderView(savedFolderView);

function switchMainSpace(selectEl) {
  const form = selectEl.form;
  const spaceInput = form ? form.querySelector('input[name="space"]') : null;
  if (spaceInput) {
    spaceInput.value = selectEl.value === '__personal__' ? 'personal' : 'client';
  }
  if (form && form.folder) {
    form.folder.value = '';
  }
  if (form) {
    persistDocumentosScroll();
    form.submit();
  }
}

function openFileEditModal(id, name, folderName = '') {
  const form = document.getElementById('file-edit-form');
  form.action = '{{ route('documentos.update', '__ID__') }}'.replace('__ID__', id);
  document.getElementById('file_edit_name').value = name || '';
  const folderSelect = document.getElementById('file_edit_folder');
  if (folderSelect) {
    const targetFolder = folderName || '{{ $folder ?? '' }}';
    if (targetFolder && !Array.from(folderSelect.options).some((option) => option.value === targetFolder)) {
      const option = document.createElement('option');
      option.value = targetFolder;
      option.textContent = targetFolder;
      folderSelect.appendChild(option);
    }
    folderSelect.value = targetFolder || folderSelect.value;
  }
  const modal = document.getElementById('modal-file-edit');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function openFolderEditModal(name, color) {
  document.getElementById('folder_edit_current').value = name || '';
  document.getElementById('folder_edit_name').value = name || '';
  document.getElementById('folder_edit_color').value = color || '#0ea5e9';
  refreshColorPresetSelection('folder_edit_color');
  const modal = document.getElementById('modal-folder-edit');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function confirmDeleteFolder(name) {
  if (!confirm('¿Eliminar carpeta y su contenido?')) return;
  document.getElementById('delete_folder_name').value = name || '';
  persistDocumentosScroll();
  document.getElementById('folder-delete-form').submit();
}

function downloadFolder(folderName) {
  const form = document.getElementById('download-folder-form');
  document.getElementById('df_scope').value = '{{ $currentSpace }}';
  document.getElementById('df_cliente_id').value = '{{ $clienteId ?? '' }}';
  document.getElementById('df_name').value = folderName || '';
  persistDocumentosScroll();
  form.submit();
}

async function toggleFolderVisibility(folderName, currentlyVisible) {
  try {
    const response = await fetch('{{ route('documentos.folders.visibility') }}', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify({
        scope: '{{ $currentSpace }}',
        cliente_id: '{{ $clienteId ?? '' }}',
        name: folderName || '',
        client_visible: !currentlyVisible,
      }),
    });

    if (!response.ok) {
      throw new Error('toggle_failed');
    }

    persistDocumentosScroll();
    window.location.reload();
  } catch (_) {
    alert('No se pudo actualizar la visibilidad de la carpeta.');
  }
}

function openUploadModal() {
  const modal = document.getElementById('modal-upload');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}
function openFolderModal() {
  const modal = document.getElementById('modal-folder');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  refreshColorPresetSelection('mf_color');
  setFolderScope(document.getElementById('mf_scope_select').value);
}
function closeModal(id) {
  const modal = document.getElementById(id);
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  if (id === 'modal-doc-preview') {
    const frame = document.getElementById('doc-preview-frame');
    const image = document.getElementById('doc-preview-image');
    if (frame) frame.removeAttribute('src');
    if (image) image.removeAttribute('src');
    resetDocPreviewScale();
  }
}

function openDocumentPreview(title, url, type, downloadUrl = '', extLabel = 'FILE', extColor = '#475569') {
  const modal = document.getElementById('modal-doc-preview');
  const titleEl = document.getElementById('doc-preview-title');
  const footerTitleEl = document.getElementById('doc-preview-footer-title');
  const subtitleEl = document.getElementById('doc-preview-subtitle');
  const frame = document.getElementById('doc-preview-frame');
  const imageWrap = document.getElementById('doc-preview-image-wrap');
  const image = document.getElementById('doc-preview-image');
  const unsupported = document.getElementById('doc-preview-unsupported');
  const download = document.getElementById('doc-preview-download');
  if (!modal || !frame || !imageWrap || !image || !unsupported) return;

  if (titleEl) titleEl.textContent = 'Vista previa del documento';
  if (footerTitleEl) footerTitleEl.textContent = title || 'Documento';
  if (subtitleEl) subtitleEl.textContent = 'Usa trackpad o rueda sobre la vista para acercar y alejar.';
  frame.classList.add('hidden');
  imageWrap.classList.add('hidden');
  unsupported.classList.add('hidden');
  frame.removeAttribute('src');
  image.removeAttribute('src');
  if (download) {
    download.href = downloadUrl || url || '#';
    download.classList.toggle('hidden', !(downloadUrl || url));
  }
  resetDocPreviewScale();

  if (type === 'image') {
    image.src = url;
    imageWrap.classList.remove('hidden');
  } else {
    frame.src = url;
    frame.classList.remove('hidden');
  }

  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function openUnsupportedPreview(title, downloadUrl, extLabel = 'FILE', extColor = '#475569') {
  const modal = document.getElementById('modal-doc-preview');
  const titleEl = document.getElementById('doc-preview-title');
  const footerTitleEl = document.getElementById('doc-preview-footer-title');
  const subtitleEl = document.getElementById('doc-preview-subtitle');
  const frame = document.getElementById('doc-preview-frame');
  const imageWrap = document.getElementById('doc-preview-image-wrap');
  const image = document.getElementById('doc-preview-image');
  const unsupported = document.getElementById('doc-preview-unsupported');
  const download = document.getElementById('doc-preview-download');
  const unsupportedDownload = document.getElementById('unsupported-preview-download');
  const unsupportedExt = document.getElementById('unsupported-preview-ext');
  if (!modal || !frame || !imageWrap || !image || !unsupported) return;

  if (titleEl) titleEl.textContent = 'Vista previa no disponible';
  if (footerTitleEl) footerTitleEl.textContent = title || 'Archivo';
  if (subtitleEl) subtitleEl.textContent = 'Formato no compatible con vista previa nativa.';
  frame.classList.add('hidden');
  imageWrap.classList.add('hidden');
  frame.removeAttribute('src');
  image.removeAttribute('src');
  unsupported.classList.remove('hidden');
  if (download) {
    download.href = downloadUrl || '#';
    download.classList.remove('hidden');
  }
  if (unsupportedDownload) unsupportedDownload.href = downloadUrl || '#';
  if (unsupportedExt) {
    unsupportedExt.textContent = extLabel || 'FILE';
    unsupportedExt.style.background = extColor || '#475569';
  }
  resetDocPreviewScale();
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

const docPreviewShell = document.getElementById('doc-preview-zoom-shell');
if (docPreviewShell) {
  let previewTouchDistance = null;
  docPreviewShell.addEventListener('wheel', function(event) {
    if (!document.getElementById('modal-doc-preview')?.classList.contains('flex')) return;
    if (event.ctrlKey || event.metaKey) {
      event.preventDefault();
      const direction = event.deltaY > 0 ? -1 : 1;
      setDocPreviewScale(docPreviewScale + direction * 0.08);
    }
  }, { passive: false });
  docPreviewShell.addEventListener('touchstart', function(event) {
    if (event.touches.length === 2) {
      const dx = event.touches[0].clientX - event.touches[1].clientX;
      const dy = event.touches[0].clientY - event.touches[1].clientY;
      previewTouchDistance = Math.hypot(dx, dy);
    }
  }, { passive: true });
  docPreviewShell.addEventListener('touchmove', function(event) {
    if (event.touches.length !== 2 || previewTouchDistance === null) return;
    event.preventDefault();
    const dx = event.touches[0].clientX - event.touches[1].clientX;
    const dy = event.touches[0].clientY - event.touches[1].clientY;
    const nextDistance = Math.hypot(dx, dy);
    const delta = (nextDistance - previewTouchDistance) / 220;
    setDocPreviewScale(docPreviewScale + delta);
    previewTouchDistance = nextDistance;
  }, { passive: false });
  docPreviewShell.addEventListener('touchend', function(event) {
    if (event.touches.length < 2) previewTouchDistance = null;
  }, { passive: true });
}

['modal-upload','modal-folder','modal-file-edit','modal-folder-edit','modal-doc-preview'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('click', function(e){
      if (e.target === this) closeModal(id);
    });
  }
});

document.addEventListener('keydown', function(event) {
  if (event.key !== 'Escape') return;
  const opened = ['modal-doc-preview','modal-upload','modal-folder','modal-file-edit','modal-folder-edit']
    .find((id) => document.getElementById(id)?.classList.contains('flex'));
  if (opened) closeModal(opened);
});

function setUploadScope(scope) {
  const scopeInput = document.getElementById('mu_scope');
  if (scopeInput) {
    scopeInput.value = scope;
  }
}

function setFolderScope(scope) {
  document.getElementById('mf_scope').value = scope;
  const box = document.getElementById('mf_client_box');
  const select = document.getElementById('mf_cliente');
  const isClient = scope === 'client';
  box.classList.toggle('hidden', !isClient);
  select.required = isClient;
  if (!isClient) select.value = '';
}

function triggerFileInput() {
  document.getElementById('quick-file-input').click();
}

function handleQuickFileSelect(input) {
  if (!input.files.length) return;
  submitFileDrop(input.files, '{{ $currentSpace }}', '{{ $clienteId ?? '' }}', '{{ $folder ?? '' }}');
  input.value = '';
}

function uploadProgressItem(file, index) {
  const list = document.getElementById('upload-progress-list');
  if (!list) return null;
  const row = document.createElement('div');
  row.className = 'upload-progress-item';
  row.dataset.uploadIndex = String(index);
  row.innerHTML = `
    <div class="upload-file-ghost" aria-hidden="true"></div>
    <div class="min-w-0">
      <div class="flex items-center justify-between gap-2">
        <div class="text-xs font-black text-slate-800 truncate"></div>
        <div class="text-[11px] font-bold text-slate-400">0%</div>
      </div>
      <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
        <div class="h-full w-0 rounded-full bg-lime-300 transition-[width] duration-150"></div>
      </div>
    </div>`;
  row.querySelector('.text-xs').textContent = file.name || `Archivo ${index + 1}`;
  list.appendChild(row);
  return row;
}

function updateUploadProgress(row, percent, done = false) {
  if (!row) return;
  const clamped = Math.max(0, Math.min(100, Math.round(percent)));
  const bar = row.querySelector('.bg-lime-300');
  const label = row.querySelector('.text-\\[11px\\]');
  if (bar) bar.style.width = `${clamped}%`;
  if (label) {
    label.textContent = done ? 'Listo' : `${clamped}%`;
    label.classList.toggle('text-emerald-600', done);
    label.classList.toggle('text-slate-400', !done);
  }
}

function uploadSingleDocumentFile(file, scope, clienteId, folderName, row) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    const form = new FormData();
    form.append('_token', '{{ csrf_token() }}');
    form.append('scope', scope);
    form.append('storage_mode', 'local');
    form.append('cliente_id', clienteId || '');
    form.append('folder', folderName || 'General');
    form.append('folder_color', document.getElementById('mu_folder_color')?.value || '{{ $currentFolderColor }}');
    form.append('archivo', file);

    xhr.open('POST', '{{ route('documentos.upload') }}');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.upload.addEventListener('progress', (event) => {
      if (!event.lengthComputable) return;
      updateUploadProgress(row, (event.loaded / event.total) * 100);
    });
    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        updateUploadProgress(row, 100, true);
        resolve();
      } else {
        reject(new Error('upload_failed'));
      }
    });
    xhr.addEventListener('error', () => reject(new Error('upload_failed')));
    xhr.send(form);
  });
}

async function uploadDocumentFiles(fileList, scope, clienteId, folderName) {
  const files = Array.from(fileList || []).filter(Boolean);
  if (!files.length) return;

  if (scope === 'client' && !clienteId) {
    alert('Selecciona un cliente antes de cargar archivos.');
    return;
  }

  const resolvedFolder = folderName && folderName.trim() !== '' ? folderName : 'General';
  const panel = document.getElementById('upload-progress-panel');
  const list = document.getElementById('upload-progress-list');
  const summary = document.getElementById('upload-progress-summary');
  if (list) list.innerHTML = '';
  if (panel) panel.classList.remove('hidden');
  const rows = files.map(uploadProgressItem);

  try {
    for (let i = 0; i < files.length; i++) {
      if (summary) summary.textContent = `${i + 1} de ${files.length}`;
      await uploadSingleDocumentFile(files[i], scope, clienteId, resolvedFolder, rows[i]);
    }
    if (summary) summary.textContent = `${files.length} archivo${files.length === 1 ? '' : 's'} subido${files.length === 1 ? '' : 's'}`;
    persistDocumentosScroll();
    setTimeout(() => window.location.reload(), 550);
  } catch (_) {
    if (summary) summary.textContent = 'No se pudo completar la subida.';
    alert('No se pudo subir uno de los archivos. Intenta de nuevo.');
  }
}

function submitFileDrop(files, scope, clienteId, folderName) {
  const resolvedFolder = folderName && folderName.trim() !== '' ? folderName : 'General';

  if (scope === 'client' && !clienteId) {
    alert('Selecciona un cliente antes de cargar archivos.');
    return;
  }

  uploadDocumentFiles(files, scope, clienteId, resolvedFolder);
}

function refreshColorPresetSelection(hiddenInputId) {
  const hidden = document.getElementById(hiddenInputId);
  if (!hidden) return;
  const selected = (hidden.value || '').toLowerCase();
  document.querySelectorAll(`.folder-color-btn[data-target="${hiddenInputId}"]`).forEach((btn) => {
    const color = (btn.dataset.color || '').toLowerCase();
    const active = color === selected;
    btn.classList.toggle('ring-2', active);
    btn.classList.toggle('ring-offset-2', active);
    btn.classList.toggle('ring-slate-900', active);
  });
}

document.querySelectorAll('.folder-color-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const targetId = btn.dataset.target;
    const color = btn.dataset.color;
    const hidden = targetId ? document.getElementById(targetId) : null;
    if (!hidden || !color) return;
    hidden.value = color;
    refreshColorPresetSelection(targetId);
  });
});

function dragHasFiles(event) {
  return !!event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files');
}

function isDocumentDropArea(event) {
  const target = event.target;
  if (!target || !target.closest) return true;
  return !!target.closest('.finder-surface') || target === dragOverlay || !!target.closest('#drag-overlay');
}

function startFolderDrag(event, folderPath) {
  activeDragItem = { type: 'folder', value: folderPath || '' };
  event.dataTransfer.setData('text/plain', folderPath || '');
  event.dataTransfer.effectAllowed = 'move';
}

function startDocumentDrag(event, docId) {
  activeDragItem = { type: 'document', value: docId || '' };
  event.dataTransfer.setData('text/plain', docId || '');
  event.dataTransfer.effectAllowed = 'move';
}

async function moveDraggedItemTo(targetFolder) {
  if (!activeDragItem || !targetFolder) return;
  try {
    const body = {
      scope: '{{ $currentSpace }}',
      cliente_id: '{{ $clienteId ?? '' }}',
      item_type: activeDragItem.type,
      target_folder: targetFolder
    };
    if (activeDragItem.type === 'document') {
      body.item_id = activeDragItem.value;
    } else {
      body.current_name = activeDragItem.value;
    }

    const res = await fetch('{{ route('documentos.move') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify(body)
    });

    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json.ok) {
      throw new Error('move_failed');
    }
    persistDocumentosScroll();
    window.location.reload();
  } catch (_) {
    alert('No se pudo mover. Intenta de nuevo.');
  } finally {
    activeDragItem = null;
  }
}

const dragOverlay = document.getElementById('drag-overlay');
let dragCounter = 0;

window.addEventListener('dragenter', (event) => {
  if (!dragHasFiles(event)) return;
  if (!isDocumentDropArea(event)) return;
  event.preventDefault();
  dragCounter++;
  dragOverlay?.classList.remove('hidden');
  dragOverlay?.classList.add('flex');
});

window.addEventListener('dragover', (event) => {
  if (!dragHasFiles(event)) return;
  if (!isDocumentDropArea(event)) return;
  event.preventDefault();
});

window.addEventListener('dragleave', (event) => {
  if (!dragHasFiles(event)) return;
  dragCounter = Math.max(0, dragCounter - 1);
  if (dragCounter === 0) {
    dragOverlay?.classList.add('hidden');
    dragOverlay?.classList.remove('flex');
  }
});

window.addEventListener('drop', (event) => {
  const target = event.target && event.target.closest ? event.target.closest('[data-dnd-folder-target]') : null;
  if (target && activeDragItem && !dragHasFiles(event)) {
    event.preventDefault();
    const targetFolder = target.getAttribute('data-dnd-folder-target') || '';
    moveDraggedItemTo(targetFolder);
    return;
  }

  if (!dragHasFiles(event)) return;
  if (!isDocumentDropArea(event)) return;
  event.preventDefault();
  dragCounter = 0;
  dragOverlay?.classList.add('hidden');
  dragOverlay?.classList.remove('flex');
  const files = event.dataTransfer.files;
  if (!files || !files.length) return;
  submitFileDrop(files, '{{ $currentSpace }}', '{{ $clienteId ?? '' }}', '{{ $folder ?? '' }}');
});

document.getElementById('upload-form')?.addEventListener('submit', function(event) {
  const fileInput = document.getElementById('modal-file-input');
  if (!fileInput || !fileInput.files.length) return;
  event.preventDefault();
  closeModal('modal-upload');
  uploadDocumentFiles(
    fileInput.files,
    document.getElementById('mu_scope')?.value || '{{ $currentSpace }}',
    document.getElementById('mu_cliente')?.value || '{{ $clienteId ?? '' }}',
    document.getElementById('mu_folder')?.value || '{{ $folder ?? '' }}'
  );
  fileInput.value = '';
});

document.querySelectorAll('[data-dnd-folder-target]').forEach((el) => {
  el.addEventListener('dragover', (event) => {
    if (!activeDragItem || dragHasFiles(event)) return;
    event.preventDefault();
    el.classList.add('ring-2', 'ring-lime-300');
  });
  el.addEventListener('dragleave', () => {
    el.classList.remove('ring-2', 'ring-lime-300');
  });
  el.addEventListener('drop', (event) => {
    if (!activeDragItem || dragHasFiles(event)) return;
    event.preventDefault();
    el.classList.remove('ring-2', 'ring-lime-300');
    const targetFolder = el.getAttribute('data-dnd-folder-target') || '';
    moveDraggedItemTo(targetFolder);
  });
});

setUploadScope('{{ $currentSpace }}');
setFolderScope('{{ $currentSpace }}');
refreshColorPresetSelection('mf_color');
refreshColorPresetSelection('folder_edit_color');
restoreDocumentosScroll();
window.addEventListener('pageshow', () => restoreDocumentosScroll());
window.addEventListener('load', () => restoreDocumentosScroll());
setTimeout(() => restoreDocumentosScroll(), 360);

document.addEventListener('click', function(event) {
  const anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
  if (!anchor) return;
  if (anchor.target === '_blank' || anchor.hasAttribute('download')) return;
  const href = anchor.getAttribute('href') || '';
  if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
  persistDocumentosScroll();
}, true);

document.querySelectorAll('form').forEach((form) => {
  form.addEventListener('submit', () => persistDocumentosScroll());
});
</script>
@endsection
