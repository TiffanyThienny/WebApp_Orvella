@props(['active', 'icon'])

@php
$classes = ($active ?? false)
            ? 'flex items-center space-x-3 px-3.5 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-500/30 transition-all duration-300 transform scale-[1.02] group relative overflow-hidden border border-blue-400/20'
            : 'flex items-center space-x-3 px-3.5 py-2.5 text-sm font-medium rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-all duration-300 group border border-transparent hover:border-slate-700/50';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if($active ?? false)
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,1)]"></div>
    @endif
    <i data-lucide="{{ $icon }}" class="w-5 h-5 flex-shrink-0 transition-transform duration-300 group-hover:scale-110 @if($active ?? false) text-white animate-pulse @else text-slate-400 group-hover:text-blue-400 @endif"></i>
    <span class="truncate font-semibold tracking-wide">{{ $slot }}</span>
</a>
