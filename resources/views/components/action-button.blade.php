@props(['href' => null, 'icon' => null, 'color' => 'slate', 'title' => ''])

@php
    $base = "inline-grid place-content-center w-9 h-9 rounded-full border transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 shadow-sm";
    
    $colors = [
        'slate' => 'border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 hover:border-slate-300 focus:ring-slate-200',
        'rose'  => 'border-rose-200 text-rose-500 hover:text-rose-700 hover:bg-rose-50 hover:border-rose-300 focus:ring-rose-200',
        'lime'  => 'border-lime-200 text-lime-600 hover:text-lime-800 hover:bg-lime-50 hover:border-lime-300 focus:ring-lime-200',
    ];
    
    $class = $base . ' ' . ($colors[$color] ?? $colors['slate']);
    
    $svg = match($icon) {
        'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>',
        'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'pdf' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4 4 4-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17v3h16v-3"/>',
        'pay' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 1v22"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>',
        'duplicate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h12v12H8z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h12v12H4z"/>',
        'whatsapp' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 1 9 9H7l-4 2 2-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12c0-1-1-2-2-2"/>',
        default => null,
    };
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $class, 'title' => $title]) }}>
    @if($svg)
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $svg !!}</svg>
    @else
        {{ $slot }}
    @endif
</a>
@else
<button {{ $attributes->merge(['class' => $class, 'title' => $title]) }}>
    @if($svg)
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $svg !!}</svg>
    @else
        {{ $slot }}
    @endif
</button>
@endif
