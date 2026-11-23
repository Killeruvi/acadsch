@props([
    'title' => '',
    'value' => '0',
    'subtitle' => null,
    'icon' => null,
    'color' => 'indigo', // indigo, emerald, amber, rose, sky, purple
    'trend' => null,
    'trendUp' => true,
    'href' => null,
])

@php
    $colorClasses = [
        'indigo' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400',
    ][$color] ?? 'bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
@endphp

<div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden group">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $title }}</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $value }}</h3>
            @if($subtitle)
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 {{ $colorClasses }} text-xl">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($trend !== null)
        <div class="mt-3 flex items-center gap-1.5 text-xs font-semibold {{ $trendUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
            <span>{{ $trendUp ? '↑' : '↓' }}</span>
            <span>{{ $trend }}</span>
        </div>
    @endif

    @if($href)
        <a href="{{ $href }}" class="absolute inset-0 focus:outline-hidden" aria-label="{{ $title }}"></a>
    @endif
</div>
