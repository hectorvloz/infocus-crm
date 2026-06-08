@extends('layouts.app')
@section('title','Reuniones')
@section('content')
<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
@php
  $monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  $dayNames = ['Lun','Mar','Mie','Jue','Vie','Sab','Dom'];
  $fullDayNames = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
  $meetingColors = [
    'emerald' => ['label' => 'Verde', 'class' => 'border-emerald-300 bg-emerald-50 text-emerald-800', 'dot' => 'bg-emerald-400'],
    'sky' => ['label' => 'Azul', 'class' => 'border-sky-300 bg-sky-50 text-sky-800', 'dot' => 'bg-sky-400'],
    'violet' => ['label' => 'Violeta', 'class' => 'border-violet-300 bg-violet-50 text-violet-800', 'dot' => 'bg-violet-400'],
    'rose' => ['label' => 'Rosa', 'class' => 'border-rose-300 bg-rose-50 text-rose-800', 'dot' => 'bg-rose-400'],
    'amber' => ['label' => 'Amarillo', 'class' => 'border-amber-300 bg-amber-50 text-amber-800', 'dot' => 'bg-amber-400'],
    'cyan' => ['label' => 'Cian', 'class' => 'border-cyan-300 bg-cyan-50 text-cyan-800', 'dot' => 'bg-cyan-400'],
    'slate' => ['label' => 'Gris', 'class' => 'border-slate-300 bg-slate-50 text-slate-800', 'dot' => 'bg-slate-400'],
  ];
  $today = \Carbon\Carbon::now($timezone)->toDateString();
  $now = \Carbon\Carbon::now($timezone);
  $nowMinutes = max(0, min((22 - 8 + 1) * 60, ($now->hour * 60 + $now->minute) - (8 * 60)));
  $daysCount = count($days);
  $calendarMinWidth = $daysCount === 1 ? '360px' : '100%';
  $dayMinTrack = $daysCount === 1 ? '18rem' : '0';
  $timeTrack = $daysCount === 1 ? '4.4rem' : 'clamp(2.75rem, 5vw, 4.2rem)';
@endphp

<style>
  .meetings-calendar {
    --slot-height: 62px;
    --calendar-day-pad-x: 0.55rem;
    --calendar-day-pad-y: 0.7rem;
    --calendar-event-font: 0.78rem;
    --calendar-event-pad: 0.45rem;
    overflow-y: visible;
    overflow-x: visible !important;
  }
  .meetings-main {
    overflow: visible !important;
  }
  .meetings-calendar-week-header {
    position: sticky;
    top: 0;
    z-index: 45;
    box-shadow: 0 1px 0 rgba(226, 232, 240, 0.9), 0 12px 24px rgba(15, 23, 42, 0.04);
  }
  .meetings-calendar-sticky-clone {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 110;
    display: none;
    overflow: hidden;
    pointer-events: none;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.10);
  }
  .meetings-calendar-sticky-clone.is-visible {
    display: block;
  }
  .meetings-calendar-sticky-clone-inner {
    will-change: transform;
  }
  .meetings-calendar-sticky-clone-inner > .meetings-calendar-week-header {
    position: static;
    width: 100%;
  }
  .meeting-card {
    top: calc(var(--start-min) / 60 * var(--slot-height));
    height: calc(var(--duration-min) / 60 * var(--slot-height) - 6px);
    overflow: visible;
  }
  .meeting-card-content {
    height: 100%;
    overflow: hidden;
    border-radius: 0;
  }
  .meeting-card {
    font-size: var(--calendar-event-font);
  }
  .meeting-card-time-row {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 0.25rem;
    max-width: 100%;
  }
  .meeting-time-pill {
    flex: 0 1 auto;
    min-width: 0;
    border-radius: 0.6rem;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.22rem 0.38rem;
    font-size: 0.62rem;
    font-weight: 900;
    line-height: 1;
    text-align: center;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    box-shadow: 0 6px 16px rgba(255, 255, 255, 0.42);
  }
  }
  .meetings-calendar {
    cursor: grab;
  }
  .meetings-calendar.is-dragging {
    cursor: grabbing;
    user-select: none;
  }
  .meetings-calendar.is-dragging * {
    user-select: none;
  }
  .meeting-card.is-past {
    filter: grayscale(1);
    opacity: 0.58;
  }
  .meeting-day-column.is-past {
    background: repeating-linear-gradient(
      135deg,
      rgba(148, 163, 184, 0.08) 0,
      rgba(148, 163, 184, 0.08) 8px,
      rgba(248, 250, 252, 0.45) 8px,
      rgba(248, 250, 252, 0.45) 16px
    );
  }
  .meeting-now-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background: #ef4444;
    z-index: 20;
    pointer-events: none;
  }
  .meeting-now-line::before {
    content: "";
    position: absolute;
    left: -5px;
    top: -5px;
    width: 12px;
    height: 12px;
    border-radius: 999px;
    background: #ef4444;
  }
  .meeting-past-overlay {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    background: repeating-linear-gradient(
      135deg,
      rgba(148, 163, 184, 0.08) 0,
      rgba(148, 163, 184, 0.08) 8px,
      rgba(255, 255, 255, 0.38) 8px,
      rgba(255, 255, 255, 0.38) 16px
    );
    z-index: 5;
    pointer-events: none;
  }
  .meeting-modal.is-open {
    display: flex;
  }
  .meeting-input-icon {
    position: absolute;
    left: 0.85rem;
    top: 2.3rem;
    height: 1rem;
    width: 1rem;
    color: #94a3b8;
    pointer-events: none;
  }
  .meeting-tags-box {
    min-height: 46px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
    border: 1px solid #dbe5f2;
    border-radius: 0.75rem;
    background: #fff;
    padding: 0.45rem 0.65rem;
  }
  .meeting-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    max-width: 100%;
    border-radius: 999px;
    background: #ecfe88;
    color: #0f172a;
    padding: 0.35rem 0.6rem;
    font-size: 0.82rem;
    font-weight: 800;
  }
  .meeting-tag button {
    height: 1.05rem;
    width: 1.05rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.12);
    line-height: 1;
  }
  .meeting-tags-input {
    min-width: 14rem;
    flex: 1 1 14rem;
    border: 0;
    outline: 0;
    padding: 0.35rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
  }
  .meeting-time-input,
  .meeting-date-input {
    height: 46px;
    min-height: 46px;
    line-height: 1.25;
    font-size: 0.95rem;
    font-weight: 800;
    padding-top: 0;
    padding-bottom: 0;
  }
  .meeting-meet-manual.is-hidden {
    display: none;
  }
  .meeting-card-title {
    display: block;
    font-size: var(--calendar-event-title-font, 0.74rem);
    line-height: 1.08;
    overflow: visible;
    padding-left: 0.08rem;
    padding-bottom: 0.18rem;
    word-break: break-word;
  }
  .meeting-color-option {
    height: 2.4rem;
    width: 2.4rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #dbe5f2;
    background: #fff;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .meeting-color-option.is-active {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(236, 254, 136, 0.75);
    transform: translateY(-1px);
  }
  .meeting-color-swatch {
    height: 100%;
    width: 100%;
    border-radius: inherit;
    display: block;
  }
  .meeting-delete-action {
    opacity: 0;
    transform: scale(0.96);
    pointer-events: none;
    color: #64748b;
    transition: opacity 0.15s ease, transform 0.15s ease, color 0.15s ease;
  }
  .meeting-card:hover .meeting-delete-action {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
  }
  .meeting-delete-form {
    position: absolute;
    right: -0.82rem;
    top: -0.82rem;
    z-index: 35;
  }
  .meeting-delete-action:hover {
    color: #ef4444;
  }
  .meeting-field {
    width: 100%;
    min-height: 54px;
    border-radius: 18px;
    border: 1.5px solid #dbe5f2;
    background: #fff;
    padding: 0 1rem;
    font-size: 0.98rem;
    line-height: 1.35;
    font-weight: 700;
    color: #1e293b;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.035);
  }
  .meeting-field::placeholder {
    color: #94a3b8;
    font-weight: 700;
  }
  .meeting-field:focus {
    outline: none;
    border-color: #b9f553;
    box-shadow: 0 0 0 4px rgba(236, 254, 136, 0.35), 0 10px 24px rgba(15, 23, 42, 0.06);
  }
  textarea.meeting-field {
    min-height: 104px;
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
    font-weight: 700;
    resize: vertical;
  }
  .meeting-label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #64748b;
  }
  .meetings-new-sidebar {
    display: none;
  }
  .meetings-mini-calendar {
    padding: 1rem;
  }
  .meetings-mini-month {
    font-size: 0.84rem;
    letter-spacing: 0.08em;
    white-space: nowrap;
  }
  .meetings-mini-weekdays {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    column-gap: 0.36rem;
    margin-bottom: 0.45rem;
    text-align: center;
    font-size: 0.64rem;
    font-weight: 900;
    color: #94a3b8;
  }
  .meetings-mini-days {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    column-gap: 0.28rem;
    row-gap: 0.42rem;
    justify-items: center;
  }
  .meetings-mini-day {
    position: relative;
    width: 1.9rem;
    height: 1.85rem;
    border-radius: 0.78rem;
    display: grid;
    place-content: center;
    font-size: 0.82rem;
    font-weight: 900;
    line-height: 1;
  }
  .meetings-mini-dot {
    position: absolute;
    bottom: 0.24rem;
    left: 50%;
    height: 0.32rem;
    width: 0.32rem;
    transform: translateX(-50%);
    border-radius: 999px;
  }
  @media (min-width: 900px) {
    .meetings-layout {
      grid-template-columns: minmax(14rem, 15.5rem) minmax(0, 1fr);
    }
    .meetings-main {
      order: 2;
    }
    .meetings-sidebar {
      order: 1;
      position: sticky;
      top: 1rem;
    }
    .meetings-new-inline {
      display: none;
    }
    .meetings-new-sidebar {
      display: inline-flex;
    }
  }
  @media (max-width: 1279px) {
    .meetings-header-actions {
      width: 100%;
      justify-content: space-between;
    }
    .meetings-date-nav {
      flex: 0 0 auto;
    }
    .meetings-new-inline {
      flex: 0 0 auto;
    }
  }
  @media (max-width: 520px) {
    .meetings-header-actions {
      gap: 0.6rem;
    }
    .meetings-new-inline {
      height: 2.75rem;
      width: 2.75rem;
      padding: 0;
      border-radius: 999px;
    }
    .meetings-new-inline span {
      display: none;
    }
  }
  @media (max-width: 1024px) {
    .meetings-calendar {
      --slot-height: 50px;
      --calendar-day-pad-x: 0.38rem;
      --calendar-day-pad-y: 0.48rem;
      --calendar-event-font: 0.68rem;
      --calendar-event-title-font: 0.66rem;
      --calendar-event-pad: 0.32rem;
    }
  }
  @media (max-width: 760px) {
    .meetings-calendar {
      --slot-height: 46px;
      --calendar-day-pad-x: 0.24rem;
      --calendar-day-pad-y: 0.38rem;
      --calendar-event-font: 0.58rem;
      --calendar-event-title-font: 0.58rem;
      --calendar-event-pad: 0.24rem;
      overflow-x: auto !important;
    }
    .meetings-calendar-day-name {
      font-size: 0.58rem;
      letter-spacing: 0.06em;
    }
    .meetings-calendar-day-number {
      height: 1.85rem;
      width: 1.85rem;
      border-radius: 999px;
      font-size: 0.98rem;
    }
    .meetings-calendar-time {
      font-size: 0.62rem;
      padding-left: 0.25rem;
      padding-right: 0.25rem;
    }
  }
</style>

<div class="space-y-5">
  <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Reuniones</h1>
      <p class="text-slate-500 mt-1">Agenda semanal para reuniones con clientes y enlaces de Google Meet.</p>
    </div>
    <div class="meetings-header-actions flex flex-wrap items-center gap-2">
      <div class="meetings-date-nav flex items-center gap-2">
        <a href="{{ route('reuniones.index', ['week' => ($viewMode === 'dia' ? $focusDate->copy()->subDay() : $weekStart->copy()->subWeek())->toDateString(), 'vista' => $viewMode]) }}" class="h-11 w-11 inline-flex items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50" title="Anterior">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
        </a>
        <a href="{{ route('reuniones.index', ['vista' => $viewMode]) }}" class="h-11 px-5 inline-flex items-center rounded-full border border-slate-200 bg-white text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Hoy</a>
        <a href="{{ route('reuniones.index', ['week' => ($viewMode === 'dia' ? $focusDate->copy()->addDay() : $weekStart->copy()->addWeek())->toDateString(), 'vista' => $viewMode]) }}" class="h-11 w-11 inline-flex items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50" title="Siguiente">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>
      <button type="button" data-open-meeting-modal class="meetings-new-inline xl:hidden h-11 px-4 inline-flex items-center justify-center gap-2 rounded-full bg-[#ecfe88] border border-lime-200 text-slate-950 text-sm font-extrabold shadow-sm shadow-lime-200/60 hover:bg-lime-300" title="Nueva reunion">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        <span>Nueva reunion</span>
      </button>
    </div>
  </div>

  @if(session('success'))
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
  @endif
  @if(session('warning'))
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">{{ session('warning') }}</div>
  @endif
  @if($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">Revisa los campos de la reunion.</div>
  @endif

  <div class="meetings-layout grid grid-cols-1 gap-5 items-start">
    <section class="meetings-main bg-white rounded-[28px] border border-slate-200 shadow-sm overflow-hidden xl:order-2">
      <div class="px-5 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div>
          <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ $viewMode === 'dia' ? 'Vista diaria' : 'Vista semanal' }}</div>
          <div class="text-xl font-extrabold text-slate-900">
            @if($viewMode === 'dia')
              {{ $fullDayNames[$focusDate->dayOfWeekIso - 1] }} {{ $focusDate->format('d') }} {{ $monthNames[$focusDate->month - 1] }} {{ $focusDate->year }}
            @else
              {{ $weekStart->format('d') }} {{ $monthNames[$weekStart->month - 1] }} - {{ $weekEnd->format('d') }} {{ $monthNames[$weekEnd->month - 1] }} {{ $weekEnd->year }}
            @endif
          </div>
        </div>
        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 p-1 text-sm font-bold text-slate-500 w-fit">
          <a href="{{ route('reuniones.index', ['week' => $focusDate->toDateString(), 'vista' => 'semana']) }}" class="px-4 py-2 rounded-full {{ $viewMode === 'semana' ? 'bg-slate-900 text-white' : 'hover:bg-white text-slate-600' }}">Semana</a>
          <a href="{{ route('reuniones.index', ['week' => $focusDate->toDateString(), 'vista' => 'dia']) }}" class="px-4 py-2 rounded-full {{ $viewMode === 'dia' ? 'bg-slate-900 text-white' : 'hover:bg-white text-slate-600' }}">Dia</a>
        </div>
      </div>

      <div class="meetings-calendar overflow-x-auto">
        <div style="min-width: {{ $calendarMinWidth }};">
          <div class="meetings-calendar-week-header grid border-b border-slate-100 bg-white" style="grid-template-columns: {{ $timeTrack }} repeat({{ $daysCount }}, minmax({{ $dayMinTrack }}, 1fr));">
            <div class="px-1.5 py-[var(--calendar-day-pad-y)] text-[11px] font-bold text-slate-400 sm:px-2">Hora</div>
            @foreach($days as $index => $day)
              <div class="px-[var(--calendar-day-pad-x)] py-[var(--calendar-day-pad-y)] border-l border-slate-100 text-center">
                <div class="meetings-calendar-day-name text-xs font-extrabold uppercase tracking-[0.12em] {{ $day->toDateString() === $today ? 'text-lime-700' : 'text-slate-400' }}">{{ $dayNames[$day->dayOfWeekIso - 1] }}</div>
                <div class="meetings-calendar-day-number mt-1 inline-flex items-center justify-center h-9 w-9 rounded-2xl text-lg font-extrabold {{ $day->toDateString() === $today ? 'bg-[#ecfe88] text-slate-950' : 'text-slate-900' }}">{{ $day->format('d') }}</div>
              </div>
            @endforeach
          </div>

          <div class="grid relative" style="grid-template-columns: {{ $timeTrack }} repeat({{ $daysCount }}, minmax({{ $dayMinTrack }}, 1fr));">
            <div>
              @foreach($hours as $hour)
                <div class="meetings-calendar-time h-[var(--slot-height)] px-2 pt-2 text-xs font-bold text-slate-400 border-b border-slate-100">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
              @endforeach
            </div>
            @foreach($days as $dayIndex => $day)
              @php
                $isPastDay = $day->toDateString() < $today;
                $isTodayColumn = $day->toDateString() === $today;
              @endphp
              <div class="meeting-day-column relative border-l border-slate-100 {{ $isPastDay ? 'is-past' : '' }}">
                @foreach($hours as $hour)
                  <button type="button" data-open-meeting-modal data-date="{{ $day->toDateString() }}" data-time="{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00" class="block w-full h-[var(--slot-height)] border-b border-slate-100 hover:bg-slate-50 transition"></button>
                @endforeach
	                @if($isTodayColumn && $now->hour >= 8 && $now->hour <= 22)
	                  <div class="meeting-past-overlay" style="height: calc({{ $nowMinutes }} / 60 * var(--slot-height));"></div>
	                  <div class="meeting-now-line" style="top: calc({{ $nowMinutes }} / 60 * var(--slot-height));"></div>
	                @endif
	                <div class="absolute inset-x-1 top-0 bottom-0 pointer-events-none sm:inset-x-1.5 lg:inset-x-2">
	                  @php
	                    $dayMeetings = collect($weekMeetings[$day->toDateString()] ?? [])
	                      ->map(function ($meeting, $meetingIndex) use ($timezone) {
	                        $start = \Carbon\Carbon::parse($meeting['inicio_at'])->setTimezone($timezone);
	                        $end = \Carbon\Carbon::parse($meeting['fin_at'] ?? $meeting['inicio_at'])->setTimezone($timezone);
	                        $startMin = max(0, $start->hour * 60 + $start->minute - 8 * 60);
	                        $durationMin = max(30, $start->diffInMinutes($end));

	                        return [
	                          'meeting' => $meeting,
	                          'meeting_index' => $meetingIndex,
	                          'start' => $start,
	                          'end' => $end,
	                          'start_min' => $startMin,
	                          'end_min' => $startMin + $durationMin,
	                          'duration_min' => $durationMin,
	                        ];
	                      })
	                      ->sortBy([
	                        ['start_min', 'asc'],
	                        ['end_min', 'asc'],
	                      ])
	                      ->values()
	                      ->all();

	                    $layoutMeetings = [];
	                    $cluster = [];
	                    $clusterEnd = null;
	                    $flushMeetingCluster = function () use (&$cluster, &$layoutMeetings) {
	                      if (empty($cluster)) {
	                        return;
	                      }

	                      $laneEnds = [];
	                      $clusterLayout = [];

	                      foreach ($cluster as $clusterItem) {
	                        $lane = 0;
	                        while (isset($laneEnds[$lane]) && $clusterItem['start_min'] < $laneEnds[$lane]) {
	                          $lane++;
	                        }

	                        $clusterItem['lane'] = $lane;
	                        $laneEnds[$lane] = $clusterItem['end_min'];
	                        $clusterLayout[] = $clusterItem;
	                      }

	                      $lanes = max(1, count($laneEnds));
	                      foreach ($clusterLayout as $clusterItem) {
	                        $clusterItem['lanes'] = $lanes;
	                        $layoutMeetings[] = $clusterItem;
	                      }

	                      $cluster = [];
	                    };

	                    foreach ($dayMeetings as $layoutItem) {
	                      if ($clusterEnd === null || $layoutItem['start_min'] < $clusterEnd) {
	                        $cluster[] = $layoutItem;
	                        $clusterEnd = max($clusterEnd ?? 0, $layoutItem['end_min']);
	                      } else {
	                        $flushMeetingCluster();
	                        $cluster = [$layoutItem];
	                        $clusterEnd = $layoutItem['end_min'];
	                      }
	                    }
	                    $flushMeetingCluster();
	                  @endphp
	                  @foreach($layoutMeetings as $layoutItem)
	                    @php
	                      $meeting = $layoutItem['meeting'];
	                      $start = $layoutItem['start'];
	                      $end = $layoutItem['end'];
	                      $startMin = $layoutItem['start_min'];
	                      $durationMin = $layoutItem['duration_min'];
	                      $meetingLanes = max(1, (int) ($layoutItem['lanes'] ?? 1));
	                      $meetingLane = max(0, (int) ($layoutItem['lane'] ?? 0));
	                      $laneWidth = 100 / $meetingLanes;
	                      $laneGap = $meetingLanes > 1 ? 1.2 : 0;
	                      $leftPct = $meetingLane * $laneWidth;
	                      $widthPct = max(12, $laneWidth - $laneGap);
	                      $colorKey = $meeting['color'] ?? 'emerald';
	                      $accent = $meetingColors[$colorKey]['class'] ?? $meetingColors['emerald']['class'];
	                      $fullDate = $fullDayNames[$start->dayOfWeekIso - 1] . ' ' . $start->format('j') . ' ' . $monthNames[$start->month - 1] . ' ' . $start->year;
                      $isPastMeeting = $end->lt($now);
                    @endphp
                    <div
	                      class="meeting-card absolute rounded-xl border px-[var(--calendar-event-pad)] py-[var(--calendar-event-pad)] shadow-sm pointer-events-auto cursor-pointer hover:shadow-md transition {{ $accent }} {{ $isPastMeeting ? 'is-past' : '' }}"
	                      style="--start-min: {{ $startMin }}; --duration-min: {{ $durationMin }}; left: {{ $leftPct }}%; width: calc({{ $widthPct }}%); z-index: {{ 10 + $meetingLane }};"
                      data-meeting-card
                      data-title="{{ e($meeting['titulo'] ?? 'Reunion') }}"
                      data-client="{{ e($meeting['cliente'] ?? 'Sin cliente') }}"
                      data-date="{{ $start->format('d/m/Y') }}"
                      data-full-date="{{ e($fullDate) }}"
                      data-time="{{ $start->format('H:i') }} - {{ $end->format('H:i') }}"
                      data-location="{{ e($meeting['ubicacion'] ?? '') }}"
                      data-notes="{{ e($meeting['notas'] ?? '') }}"
                      data-meet="{{ e($meeting['meet_link'] ?? '') }}"
                      data-invites="{{ e(implode(', ', $meeting['invitados'] ?? [])) }}"
                      data-responsibles="{{ e(implode(', ', $meeting['responsables'] ?? [])) }}"
                      data-responsible-ids="{{ e(json_encode($meeting['responsable_ids'] ?? [])) }}"
                      data-id="{{ e($meeting['id'] ?? '') }}"
                      data-source="{{ e($meeting['source'] ?? 'meeting') }}"
                      data-client-id="{{ e($meeting['cliente_id'] ?? '') }}"
                      data-raw-date="{{ $start->toDateString() }}"
                      data-start="{{ $start->format('H:i') }}"
                      data-end="{{ $end->format('H:i') }}"
                      data-color="{{ e($meeting['color'] ?? 'emerald') }}"
                    >
                      <div class="meeting-card-content">
                        <div class="min-w-0">
                          <div class="meeting-card-title font-extrabold text-slate-900">{{ $meeting['titulo'] ?? 'Reunion' }}</div>
                          <div class="meeting-card-time-row mt-1">
                            <span class="meeting-time-pill">{{ $start->format('H:i') }} - {{ $end->format('H:i') }}</span>
                          </div>
                          <div class="mt-1 text-[11px] font-bold opacity-75 truncate hidden 2xl:block">{{ $meeting['cliente'] ?? 'Sin cliente' }}</div>
                          @if(!empty($meeting['ubicacion']))
                            <div class="mt-1 text-[11px] font-bold opacity-75 truncate">{{ $meeting['ubicacion'] }}</div>
                          @endif
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                          @if(!empty($meeting['meet_link']))
                            <a href="{{ $meeting['meet_link'] }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2 py-1 text-[11px] font-extrabold hover:bg-white">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 10 4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>
                              Meet
                            </a>
                          @endif
                          @if(!empty($meeting['meet_error']))
                            <span class="truncate text-[11px] font-bold opacity-80" title="{{ $meeting['meet_error'] }}">Meet pendiente</span>
                          @endif
                        </div>
                      </div>
                      @if(($meeting['source'] ?? 'meeting') !== 'lead')
                        <form action="{{ route('reuniones.destroy', $meeting['id']) }}" method="POST" onsubmit="return confirm('¿Eliminar esta reunion?')" class="meeting-delete-form" data-meeting-delete>
                          @csrf @method('DELETE')
                          <button class="meeting-delete-action h-8 w-8 rounded-full bg-white/95 border border-slate-200 inline-flex items-center justify-center hover:bg-white shadow-md" title="Eliminar">
                            <iconify-icon icon="lucide:x" width="18" height="18" aria-hidden="true"></iconify-icon>
                          </button>
                        </form>
                      @endif
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <aside class="meetings-sidebar space-y-5 xl:order-1 xl:sticky xl:top-4">
      <button type="button" data-open-meeting-modal class="meetings-new-sidebar w-full h-14 items-center justify-center gap-3 rounded-[22px] bg-[#ecfe88] border border-lime-200 text-slate-950 text-base font-extrabold shadow-lg shadow-lime-200/60 hover:bg-lime-300">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        Nueva reunion
      </button>

      <div class="meetings-mini-calendar bg-white rounded-[28px] border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <a href="{{ route('reuniones.index', ['week' => $monthCursor->copy()->subMonth()->toDateString(), 'vista' => $viewMode]) }}" class="h-9 w-9 rounded-full hover:bg-slate-100 grid place-content-center text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
          <div class="meetings-mini-month font-extrabold uppercase text-slate-800">{{ $monthNames[$monthCursor->month - 1] }} {{ $monthCursor->year }}</div>
          <a href="{{ route('reuniones.index', ['week' => $monthCursor->copy()->addMonth()->toDateString(), 'vista' => $viewMode]) }}" class="h-9 w-9 rounded-full hover:bg-slate-100 grid place-content-center text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
          </a>
        </div>
        <div class="meetings-mini-weekdays uppercase">
          @foreach($dayNames as $dayName)
            <div>{{ mb_substr($dayName, 0, 1) }}</div>
          @endforeach
        </div>
        <div class="meetings-mini-days">
          @foreach($monthDays as $day)
            @php
              $dayDate = $day['date']->toDateString();
              $isToday = $dayDate === $today;
              $isSelectedWeek = $viewMode === 'dia'
                ? $dayDate === $focusDate->toDateString()
                : $day['date']->betweenIncluded($weekStart, $weekEnd);
              $dayClass = !$day['in_month']
                ? 'text-slate-300'
                : ($isToday
                  ? 'bg-[#ecfe88] text-slate-950'
                  : ($isSelectedWeek ? 'bg-slate-100 text-slate-900' : 'text-slate-700 hover:bg-slate-100'));
            @endphp
            <a href="{{ route('reuniones.index', ['week' => $dayDate, 'vista' => $viewMode]) }}" class="meetings-mini-day {{ $dayClass }}">
              {{ $day['date']->format('j') }}
              @if($day['has_meeting'])
                <span class="meetings-mini-dot {{ $isToday ? 'bg-slate-950' : 'bg-[#84cc16]' }}"></span>
              @endif
            </a>
          @endforeach
        </div>
      </div>

      <div class="bg-white rounded-[28px] border border-slate-200 shadow-sm p-5">
        <div class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-400 mb-1">Proximas</div>
        <h2 class="text-xl font-extrabold text-slate-900 mb-4">Reuniones</h2>
        <div class="space-y-3">
          @forelse($upcoming as $meeting)
            @php $start = \Carbon\Carbon::parse($meeting['inicio_at'])->setTimezone($timezone); @endphp
            <div class="rounded-2xl border border-slate-200 p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-sm font-extrabold text-slate-900 truncate">{{ $meeting['titulo'] ?? 'Reunion' }}</div>
                  <div class="text-xs font-semibold text-slate-500 truncate">{{ $meeting['cliente'] ?? 'Sin cliente' }}</div>
                </div>
                <div class="text-right shrink-0">
                  <div class="text-xs font-extrabold text-lime-700">{{ $start->format('d/m') }}</div>
                  <div class="text-xs font-bold text-slate-500">{{ $start->format('H:i') }}</div>
                </div>
              </div>
              @if(!empty($meeting['meet_link']))
                <a href="{{ $meeting['meet_link'] }}" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs font-extrabold text-emerald-700 hover:text-emerald-800">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15 10 4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>
                  Abrir Meet
                </a>
              @endif
            </div>
          @empty
            <div class="rounded-2xl border border-dashed border-slate-200 p-5 text-sm font-semibold text-slate-400">No hay reuniones proximas.</div>
          @endforelse
        </div>
      </div>
    </aside>
  </div>
</div>

<div id="meetingModal" class="meeting-modal fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 px-4 py-8">
  <div class="w-full max-w-3xl max-h-[88vh] rounded-[24px] bg-white shadow-2xl overflow-hidden flex flex-col">
    <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 shrink-0">
      <div>
        <h2 id="meetingFormTitle" class="text-2xl font-extrabold text-slate-900">Nueva reunion</h2>
        <p class="text-sm font-semibold text-slate-500">Agenda con cliente y crea Google Meet si esta conectado.</p>
      </div>
      <button type="button" data-close-meeting-modal class="h-10 w-10 rounded-full hover:bg-slate-100 grid place-content-center text-slate-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="meetingForm" method="POST" action="{{ route('reuniones.store') }}" class="p-6 space-y-5 overflow-y-auto">
      @csrf
      <div>
        <label class="text-sm font-medium">Titulo</label>
        <input id="meetingTitleInput" name="titulo" value="{{ old('titulo') }}" required class="form-input" placeholder="Reunion de seguimiento">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto] gap-4 items-end">
        <div class="relative">
          <label class="text-sm font-medium">Cliente</label>
          <svg class="meeting-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
          <select id="meetingClientSelect" name="cliente_id" class="form-select pl-9">
            <option value="">Sin cliente</option>
            @foreach($clientes as $cliente)
              <option value="{{ $cliente['id'] }}" @selected(old('cliente_id') === ($cliente['id'] ?? null))>{{ $cliente['empresa'] ?? 'Cliente' }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-sm font-medium">Color</label>
          <input id="meetingColorInput" type="hidden" name="color" value="{{ old('color', 'emerald') }}">
          <div class="mt-2 flex flex-wrap gap-2">
            @foreach($meetingColors as $key => $color)
              <button type="button" class="meeting-color-option {{ old('color', 'emerald') === $key ? 'is-active' : '' }}" data-meeting-color="{{ $key }}" title="{{ $color['label'] }}">
                <span class="meeting-color-swatch {{ $color['dot'] }}"></span>
              </button>
            @endforeach
          </div>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium">Invitados</label>
        <div id="meetingInvitesBox" class="meeting-tags-box">
          <input id="meetingInvitesInput" type="text" class="meeting-tags-input" placeholder="correo@cliente.com, otro@correo.com">
        </div>
        <input id="meetingInvitesHidden" type="hidden" name="invitados" value="{{ old('invitados') }}">
        <p class="mt-1 text-xs font-semibold text-slate-400">Separa correos con coma o Enter. Puedes borrar el correo del cliente si no debe recibir invitacion.</p>
      </div>
      <div>
        <label class="text-sm font-medium">Encargados</label>
        <div class="relative mt-2">
          <div id="meetingResponsibleBox" class="meeting-tags-box">
            <input id="meetingResponsibleInput" type="text" class="meeting-tags-input" placeholder="Buscar encargado...">
          </div>
          <div id="meetingResponsibleHidden"></div>
          <div id="meetingResponsibleDropdown" class="hidden absolute z-30 mt-2 w-full max-h-60 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"></div>
        </div>
        <p class="mt-1 text-xs font-semibold text-slate-400">Solo los encargados verán esta reunion. Los administradores ven todas.</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="relative">
          <label class="text-sm font-medium">Fecha</label>
          <svg class="meeting-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
          <input id="meetingDateInput" name="fecha" value="{{ old('fecha', $weekStart->toDateString()) }}" required class="form-input pl-9 meeting-date-input">
        </div>
        <div class="relative">
          <label class="text-sm font-medium">Inicio</label>
          <svg class="meeting-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <input id="meetingStartInput" type="time" name="hora_inicio" value="{{ old('hora_inicio', '09:00') }}" required class="form-input pl-9 meeting-time-input">
        </div>
        <div class="relative">
          <label class="text-sm font-medium">Fin</label>
          <svg class="meeting-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <input id="meetingEndInput" type="time" name="hora_fin" value="{{ old('hora_fin', '09:30') }}" required class="form-input pl-9 meeting-time-input">
        </div>
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Ubicacion</label>
        <svg class="meeting-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.14 2 5 5.14 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.86-3.14-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
        <input id="meetingLocationInput" name="ubicacion" value="{{ old('ubicacion') }}" class="form-input pl-9" placeholder="Oficina, direccion, sala o lugar presencial">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Notas</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
        <textarea id="meetingNotesInput" name="notas" rows="4" class="form-input pl-9 min-h-[6.5rem]" placeholder="Objetivo, temas o acuerdos previos">{{ old('notas') }}</textarea>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
          <input id="meetingCreateMeetCheckbox" type="checkbox" name="crear_meet" value="1" class="h-5 w-5 rounded border-slate-300 text-lime-500 focus:ring-lime-200" {{ old('crear_meet') ? 'checked' : '' }}>
          Crear enlace de Google Meet
        </label>
        <div id="meetingManualMeetWrap" class="relative meeting-meet-manual">
          <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 10 4.553-2.276A1 1 0 0 1 21 8.618v6.764a1 1 0 0 1-1.447.894L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>
          <input id="meetingManualMeetInput" type="url" name="meet_url" value="{{ old('meet_url') }}" class="form-input pl-9" placeholder="O pega una URL de Meet manual">
        </div>
        <p class="text-xs font-semibold text-slate-500">
          Estado: {{ !empty($settings['google_calendar_access_token']) || !empty($settings['google_calendar_refresh_token']) ? 'Google Calendar conectado' : 'Google Calendar sin OAuth conectado' }}.
        </p>
      </div>
      <div class="flex justify-end gap-3">
        <button type="button" data-close-meeting-modal class="px-5 py-3 rounded-full border border-slate-200 font-bold text-slate-600 hover:bg-slate-50">Cancelar</button>
        <button id="meetingSubmitBtn" class="px-6 py-3 rounded-full bg-[#ecfe88] text-slate-950 font-extrabold hover:bg-lime-300">Crear reunion</button>
      </div>
    </form>
  </div>
</div>

<div id="meetingDetailModal" class="meeting-modal fixed inset-0 z-[101] hidden items-center justify-center bg-slate-950/50 px-4 py-8">
  <div class="w-full max-w-xl rounded-[24px] bg-white shadow-2xl overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4">
      <div class="min-w-0">
        <h2 id="meetingDetailTitle" class="text-2xl font-extrabold text-slate-900 truncate">Reunion</h2>
        <p id="meetingDetailClient" class="text-sm font-semibold text-slate-500 truncate">Sin cliente</p>
      </div>
      <button type="button" data-close-meeting-detail class="h-10 w-10 rounded-full hover:bg-slate-100 grid place-content-center text-slate-500">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Fecha</div>
          <div id="meetingDetailDate" class="mt-1 text-base font-extrabold text-slate-900">-</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Hora</div>
          <div id="meetingDetailTime" class="mt-1 text-base font-extrabold text-slate-900">-</div>
        </div>
      </div>
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Ubicacion</div>
        <div id="meetingDetailLocation" class="mt-1 text-sm font-bold text-slate-700">Por definir</div>
      </div>
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Invitados</div>
        <div id="meetingDetailInvites" class="mt-1 text-sm font-bold text-slate-700">Sin invitados</div>
      </div>
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Encargados</div>
        <div id="meetingDetailResponsibles" class="mt-1 text-sm font-bold text-slate-700">Sin encargados</div>
      </div>
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Notas</div>
        <div id="meetingDetailNotes" class="mt-1 text-sm font-semibold text-slate-600 whitespace-pre-wrap">Sin notas</div>
      </div>
      <div class="flex justify-end gap-3 pt-1">
        <button type="button" data-close-meeting-detail class="px-5 py-3 rounded-full border border-slate-200 font-bold text-slate-600 hover:bg-slate-50">Cerrar</button>
        <button id="meetingDetailEdit" type="button" class="px-5 py-3 rounded-full border border-slate-200 font-bold text-slate-700 hover:bg-slate-50">Editar</button>
        <a id="meetingDetailJoin" href="#" target="_blank" class="hidden px-6 py-3 rounded-full bg-[#ecfe88] text-slate-950 font-extrabold hover:bg-lime-300">Entrar a reunion</a>
      </div>
    </div>
  </div>
</div>

@php
  $clientOptions = collect($clientes)->map(fn ($cliente) => [
    'id' => $cliente['id'] ?? '',
    'email' => $cliente['contacto_email'] ?? $cliente['email'] ?? '',
  ])->values()->all();

  $teamUserOptions = collect($teamUsers)->map(fn ($user) => [
    'id' => $user['id'] ?? '',
    'name' => $user['name'] ?? '',
    'email' => $user['email'] ?? '',
    'role' => $user['role'] ?? '',
  ])->values()->all();
@endphp

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const clients = @json($clientOptions);
    const teamUsers = @json($teamUserOptions);
    const modal = document.getElementById('meetingModal');
    const detailModal = document.getElementById('meetingDetailModal');
    const detailTitle = document.getElementById('meetingDetailTitle');
    const detailClient = document.getElementById('meetingDetailClient');
    const detailDate = document.getElementById('meetingDetailDate');
    const detailTime = document.getElementById('meetingDetailTime');
    const detailLocation = document.getElementById('meetingDetailLocation');
    const detailInvites = document.getElementById('meetingDetailInvites');
    const detailResponsibles = document.getElementById('meetingDetailResponsibles');
    const detailNotes = document.getElementById('meetingDetailNotes');
    const detailJoin = document.getElementById('meetingDetailJoin');
    const detailEdit = document.getElementById('meetingDetailEdit');
    const meetingForm = document.getElementById('meetingForm');
    const meetingFormTitle = document.getElementById('meetingFormTitle');
    const meetingTitleInput = document.getElementById('meetingTitleInput');
    const meetingLocationInput = document.getElementById('meetingLocationInput');
    const meetingNotesInput = document.getElementById('meetingNotesInput');
    const meetingManualMeetInput = document.getElementById('meetingManualMeetInput');
    const meetingSubmitBtn = document.getElementById('meetingSubmitBtn');
    const dateInput = document.getElementById('meetingDateInput');
    const startInput = document.getElementById('meetingStartInput');
    const endInput = document.getElementById('meetingEndInput');
    const clientSelect = document.getElementById('meetingClientSelect');
    const invitesBox = document.getElementById('meetingInvitesBox');
    const invitesInput = document.getElementById('meetingInvitesInput');
    const invitesHidden = document.getElementById('meetingInvitesHidden');
    const responsibleBox = document.getElementById('meetingResponsibleBox');
    const responsibleInput = document.getElementById('meetingResponsibleInput');
    const responsibleHidden = document.getElementById('meetingResponsibleHidden');
    const responsibleDropdown = document.getElementById('meetingResponsibleDropdown');
    const createMeetCheckbox = document.getElementById('meetingCreateMeetCheckbox');
    const manualMeetWrap = document.getElementById('meetingManualMeetWrap');
    const colorInput = document.getElementById('meetingColorInput');
    const calendarScroller = document.querySelector('.meetings-calendar');
    const calendarHeader = document.querySelector('.meetings-calendar-week-header');
    const colorButtons = Array.from(document.querySelectorAll('[data-meeting-color]'));
    if (calendarScroller) {
      let stickyClone = null;
      let stickyCloneInner = null;
      const pageScrollHost = document.querySelector('main.custom-scroll') || window;
      const getStickyTop = () => {
        if (pageScrollHost === window) return 0;
        return Math.max(0, pageScrollHost.getBoundingClientRect().top);
      };
      const updateStickyCalendarHeader = () => {
        if (!calendarHeader) return;
        if (!stickyClone) {
          stickyClone = document.createElement('div');
          stickyClone.className = 'meetings-calendar-sticky-clone';
          stickyCloneInner = document.createElement('div');
          stickyCloneInner.className = 'meetings-calendar-sticky-clone-inner';
          stickyCloneInner.innerHTML = calendarHeader.outerHTML;
          stickyClone.appendChild(stickyCloneInner);
          document.body.appendChild(stickyClone);
        }

        const scrollerRect = calendarScroller.getBoundingClientRect();
        const headerRect = calendarHeader.getBoundingClientRect();
        const stickyTop = getStickyTop();
        const shouldShow = headerRect.top < stickyTop && scrollerRect.bottom > stickyTop + headerRect.height;

        stickyClone.classList.toggle('is-visible', shouldShow);
        if (!shouldShow || !stickyCloneInner) return;

        stickyClone.style.top = '0px';
        stickyClone.style.left = `${scrollerRect.left}px`;
        stickyClone.style.width = `${scrollerRect.width}px`;
        stickyClone.style.height = `${stickyTop + headerRect.height}px`;
        stickyCloneInner.style.width = `${calendarHeader.scrollWidth}px`;
        stickyCloneInner.style.transform = `translate(${-calendarScroller.scrollLeft}px, ${stickyTop}px)`;
      };

      updateStickyCalendarHeader();
      pageScrollHost.addEventListener('scroll', updateStickyCalendarHeader, { passive: true });
      window.addEventListener('resize', updateStickyCalendarHeader);
      calendarScroller.addEventListener('scroll', updateStickyCalendarHeader, { passive: true });

      let isCalendarDragging = false;
      let didCalendarDrag = false;
      let calendarStartX = 0;
      let calendarStartScrollLeft = 0;
      let calendarSuppressClick = false;
      const stopCalendarDrag = () => {
        if (!isCalendarDragging) return;
        isCalendarDragging = false;
        calendarScroller.classList.remove('is-dragging');
        window.setTimeout(() => {
          calendarSuppressClick = false;
        }, 0);
      };
      calendarScroller.addEventListener('mousedown', (event) => {
        if (event.button !== 0) return;
        if (calendarScroller.scrollWidth <= calendarScroller.clientWidth) return;
        if (event.target.closest('[data-meeting-card], a, input, select, textarea')) return;
        isCalendarDragging = true;
        didCalendarDrag = false;
        calendarStartX = event.pageX;
        calendarStartScrollLeft = calendarScroller.scrollLeft;
        calendarScroller.classList.add('is-dragging');
      });
      calendarScroller.addEventListener('mousemove', (event) => {
        if (!isCalendarDragging) return;
        const deltaX = event.pageX - calendarStartX;
        if (Math.abs(deltaX) > 4) {
          didCalendarDrag = true;
          calendarSuppressClick = true;
        }
        calendarScroller.scrollLeft = calendarStartScrollLeft - deltaX;
      });
      calendarScroller.addEventListener('mouseup', stopCalendarDrag);
      calendarScroller.addEventListener('mouseleave', stopCalendarDrag);
      calendarScroller.addEventListener('click', (event) => {
        if (!calendarSuppressClick && !didCalendarDrag) return;
        event.preventDefault();
        event.stopPropagation();
        calendarSuppressClick = false;
        didCalendarDrag = false;
      }, true);
    }
    colorButtons.forEach((button) => {
      button.addEventListener('click', () => {
        colorButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');
        if (colorInput) colorInput.value = button.dataset.meetingColor || 'emerald';
      });
    });
    const syncMeetMode = () => {
      manualMeetWrap?.classList.toggle('is-hidden', Boolean(createMeetCheckbox?.checked));
    };
    createMeetCheckbox?.addEventListener('change', syncMeetMode);
    syncMeetMode();
    const invites = new Set();
    let activeMeetingCard = null;
    const normalizeEmail = (value) => String(value || '').trim().toLowerCase();
    const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    const syncInvites = () => {
      if (!invitesBox || !invitesInput || !invitesHidden) return;
      invitesBox.querySelectorAll('[data-invite-tag]').forEach((tag) => tag.remove());
      Array.from(invites).forEach((email) => {
        const tag = document.createElement('span');
        tag.className = 'meeting-tag';
        tag.dataset.inviteTag = email;
        tag.innerHTML = `<span class="truncate">${email}</span><button type="button" aria-label="Quitar ${email}">x</button>`;
        tag.querySelector('button').addEventListener('click', () => {
          invites.delete(email);
          syncInvites();
        });
        invitesBox.insertBefore(tag, invitesInput);
      });
      invitesHidden.value = Array.from(invites).join(', ');
    };
    const addInvite = (email) => {
      const normalized = normalizeEmail(email);
      if (!isEmail(normalized)) return false;
      invites.add(normalized);
      syncInvites();
      return true;
    };
    const addInvitesFromText = (text) => {
      String(text || '').split(/[,;\s]+/).forEach(addInvite);
    };
    const clearInvites = () => {
      invites.clear();
      syncInvites();
    };
    const setColor = (color) => {
      const selectedColor = color || 'emerald';
      colorButtons.forEach((item) => item.classList.toggle('is-active', item.dataset.meetingColor === selectedColor));
      if (colorInput) colorInput.value = selectedColor;
    };
    const selectedResponsibleIdsFromCard = (card) => {
      try {
        const parsed = JSON.parse(card?.dataset.responsibleIds || '[]');
        return Array.isArray(parsed) ? parsed.map(String) : [];
      } catch (_) {
        return [];
      }
    };
    const selectedResponsibles = new Map();
    const renderResponsibleDropdown = () => {
      if (!responsibleDropdown || !responsibleInput) return;
      const query = responsibleInput.value.trim().toLowerCase();
      const options = teamUsers
        .filter((user) => !selectedResponsibles.has(String(user.id || '')))
        .filter((user) => {
          if (!query) return true;
          return [user.name, user.email, user.role].some((value) => String(value || '').toLowerCase().includes(query));
        });
      if (!options.length) {
        responsibleDropdown.innerHTML = '<div class="rounded-xl border border-dashed border-slate-200 px-3 py-2 text-sm font-semibold text-slate-400">Sin resultados</div>';
        return;
      }
      responsibleDropdown.innerHTML = options.map((user) => `
        <button type="button" class="w-full rounded-xl px-3 py-2 text-left hover:bg-lime-50" data-responsible-option="${String(user.id || '')}">
          <span class="block text-sm font-extrabold text-slate-800">${String(user.name || '')}</span>
          <span class="block text-xs font-semibold text-slate-400">${String(user.email || user.role || '')}</span>
        </button>
      `).join('');
    };
    const syncResponsibleTags = () => {
      if (!responsibleBox || !responsibleInput || !responsibleHidden) return;
      responsibleBox.querySelectorAll('[data-responsible-tag]').forEach((tag) => tag.remove());
      responsibleHidden.innerHTML = '';
      Array.from(selectedResponsibles.values()).forEach((user) => {
        const tag = document.createElement('span');
        tag.className = 'meeting-tag';
        tag.dataset.responsibleTag = user.id;
        tag.innerHTML = `<span class="truncate">${user.name}</span><button type="button" aria-label="Quitar ${user.name}">x</button>`;
        tag.querySelector('button').addEventListener('click', () => {
          selectedResponsibles.delete(String(user.id || ''));
          syncResponsibleTags();
          renderResponsibleDropdown();
        });
        responsibleBox.insertBefore(tag, responsibleInput);

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'responsable_ids[]';
        input.value = user.id;
        responsibleHidden.appendChild(input);
      });
    };
    const addResponsible = (id) => {
      const user = teamUsers.find((item) => String(item.id || '') === String(id || ''));
      if (!user) return;
      selectedResponsibles.set(String(user.id || ''), user);
      if (responsibleInput) responsibleInput.value = '';
      syncResponsibleTags();
      renderResponsibleDropdown();
    };
    const setResponsibleSelection = (ids = []) => {
      selectedResponsibles.clear();
      (Array.isArray(ids) ? ids : []).forEach((id) => {
        const user = teamUsers.find((item) => String(item.id || '') === String(id || ''));
        if (user) selectedResponsibles.set(String(user.id || ''), user);
      });
      if (responsibleInput) responsibleInput.value = '';
      syncResponsibleTags();
      renderResponsibleDropdown();
    };
    const setCreateMode = () => {
      if (meetingForm) meetingForm.action = '{{ route('reuniones.store') }}';
      if (meetingFormTitle) meetingFormTitle.textContent = 'Nueva reunion';
      if (meetingSubmitBtn) meetingSubmitBtn.textContent = 'Crear reunion';
      if (meetingTitleInput) meetingTitleInput.value = '';
      if (clientSelect) clientSelect.value = '';
      if (meetingLocationInput) meetingLocationInput.value = '';
      if (meetingNotesInput) meetingNotesInput.value = '';
      if (meetingManualMeetInput) meetingManualMeetInput.value = '';
      if (createMeetCheckbox) createMeetCheckbox.checked = false;
      setColor('emerald');
      setResponsibleSelection([]);
      clearInvites();
      syncMeetMode();
    };
    const setEditMode = (card) => {
      if (!card || !card.dataset.id) return;
      if (meetingForm) meetingForm.action = `/reuniones/${card.dataset.id}`;
      if (meetingFormTitle) meetingFormTitle.textContent = 'Editar reunion';
      if (meetingSubmitBtn) meetingSubmitBtn.textContent = 'Guardar cambios';
      if (meetingTitleInput) meetingTitleInput.value = card.dataset.title || '';
      if (clientSelect) clientSelect.value = card.dataset.clientId || '';
      if (dateInput) dateInput.value = card.dataset.rawDate || '';
      if (startInput) startInput.value = card.dataset.start || '';
      if (endInput) endInput.value = card.dataset.end || '';
      if (meetingLocationInput) meetingLocationInput.value = card.dataset.location || '';
      if (meetingNotesInput) meetingNotesInput.value = card.dataset.notes || '';
      if (meetingManualMeetInput) meetingManualMeetInput.value = card.dataset.meet || '';
      if (createMeetCheckbox) createMeetCheckbox.checked = false;
      setColor(card.dataset.color || 'emerald');
      setResponsibleSelection(selectedResponsibleIdsFromCard(card));
      clearInvites();
      addInvitesFromText(card.dataset.invites || '');
      syncMeetMode();
    };
    addInvitesFromText(invitesHidden?.value || '');
    invitesInput?.addEventListener('keydown', (event) => {
      if (['Enter', ',', ';', ' '].includes(event.key)) {
        event.preventDefault();
        addInvitesFromText(invitesInput.value);
        invitesInput.value = '';
      }
      if (event.key === 'Backspace' && invitesInput.value === '' && invites.size) {
        invites.delete(Array.from(invites).pop());
        syncInvites();
      }
    });
    invitesInput?.addEventListener('blur', () => {
      addInvitesFromText(invitesInput.value);
      invitesInput.value = '';
    });
    invitesBox?.addEventListener('click', () => invitesInput?.focus());
    clientSelect?.addEventListener('change', () => {
      const selected = clients.find((client) => client.id === clientSelect.value);
      if (selected?.email) addInvite(selected.email);
    });
    responsibleInput?.addEventListener('focus', () => {
      renderResponsibleDropdown();
      responsibleDropdown?.classList.remove('hidden');
    });
    responsibleInput?.addEventListener('input', () => {
      renderResponsibleDropdown();
      responsibleDropdown?.classList.remove('hidden');
    });
    responsibleInput?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();
      const firstOption = responsibleDropdown?.querySelector('[data-responsible-option]');
      if (firstOption) addResponsible(firstOption.dataset.responsibleOption);
    });
    responsibleBox?.addEventListener('click', () => {
      responsibleInput?.focus();
      renderResponsibleDropdown();
      responsibleDropdown?.classList.remove('hidden');
    });
    responsibleDropdown?.addEventListener('click', (event) => {
      const option = event.target.closest('[data-responsible-option]');
      if (!option) return;
      addResponsible(option.dataset.responsibleOption);
      responsibleInput?.focus();
      responsibleDropdown.classList.remove('hidden');
    });
    document.addEventListener('click', (event) => {
      if (!responsibleBox?.contains(event.target) && !responsibleDropdown?.contains(event.target)) {
        responsibleDropdown?.classList.add('hidden');
      }
    });
    const openModal = (date, time) => {
      setCreateMode();
      if (date && dateInput) dateInput.value = date;
      if (time && startInput) {
        startInput.value = time;
        const [hour, minute] = time.split(':').map(Number);
        const end = new Date();
        end.setHours(hour, minute + 30, 0, 0);
        endInput.value = `${String(end.getHours()).padStart(2, '0')}:${String(end.getMinutes()).padStart(2, '0')}`;
      }
      modal?.classList.add('is-open');
    };
    document.querySelectorAll('[data-open-meeting-modal]').forEach((button) => {
      button.addEventListener('click', () => openModal(button.dataset.date, button.dataset.time));
    });
    document.querySelectorAll('[data-close-meeting-modal]').forEach((button) => {
      button.addEventListener('click', () => modal?.classList.remove('is-open'));
    });
    modal?.addEventListener('click', (event) => {
      if (event.target === modal) modal.classList.remove('is-open');
    });
    const openDetail = (card) => {
      if (!card) return;
      activeMeetingCard = card;
      detailTitle.textContent = card.dataset.title || 'Reunion';
      detailClient.textContent = card.dataset.client || 'Sin cliente';
      detailDate.textContent = card.dataset.fullDate || card.dataset.date || '-';
      detailTime.textContent = card.dataset.time || '-';
      detailLocation.textContent = card.dataset.location || 'Por definir';
      detailInvites.textContent = card.dataset.invites || 'Sin invitados';
      detailResponsibles.textContent = card.dataset.responsibles || 'Sin encargados';
      detailNotes.textContent = card.dataset.notes || 'Sin notas';
      if (detailEdit) {
        detailEdit.classList.toggle('hidden', card.dataset.source === 'lead');
      }
      const meet = card.dataset.meet || '';
      if (meet) {
        detailJoin.href = meet;
        detailJoin.classList.remove('hidden');
      } else {
        detailJoin.href = '#';
        detailJoin.classList.add('hidden');
      }
      detailModal?.classList.add('is-open');
    };
    detailEdit?.addEventListener('click', () => {
      if (!activeMeetingCard) return;
      setEditMode(activeMeetingCard);
      detailModal?.classList.remove('is-open');
      modal?.classList.add('is-open');
    });
    document.querySelectorAll('[data-meeting-card]').forEach((card) => {
      card.addEventListener('click', (event) => {
        if (event.target.closest('[data-meeting-delete], a')) return;
        openDetail(card);
      });
    });
    document.querySelectorAll('[data-close-meeting-detail]').forEach((button) => {
      button.addEventListener('click', () => detailModal?.classList.remove('is-open'));
    });
    detailModal?.addEventListener('click', (event) => {
      if (event.target === detailModal) detailModal.classList.remove('is-open');
    });
    if (window.flatpickr && dateInput) {
      flatpickr(dateInput, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        disableMobile: true,
      });
    }
    @if($errors->any())
      modal?.classList.add('is-open');
    @endif
  });
</script>
@endsection
