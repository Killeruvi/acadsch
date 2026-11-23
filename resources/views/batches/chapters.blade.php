@extends('layouts.app')

@section('title', ($batch ? $batch->name : 'My Batch') . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="w-full max-w-[1500px] mx-auto px-2 sm:px-4 py-3 space-y-3">

    <!-- 1. COMPACT TOP HEADER WITH INLINE METADATA PILL BADGES & EMBEDDED SLIM PROGRESS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- Left: Back link and Inline Pill Badges -->
            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                <a href="{{ route('batches.subjects', ['batch' => $batch->id]) }}" 
                   class="inline-flex items-center space-x-1 text-xs font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition mr-1">
                    <span class="font-black">&larr;</span>
                    <span>Back to Subjects</span>
                </a>

                <!-- Batch Name Pastel Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60">
                    <span class="mr-1">🎒</span>
                    <span>{{ $batch->name }}</span>
                </span>

                <!-- Subject Category Pastel Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 dark:bg-sky-950/50 text-sky-800 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800/60">
                    <span class="mr-1">🧬</span>
                    <span>{{ $subject->name }}</span>
                </span>

                <!-- Class / Grade Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    <span>{{ $academicClass->name ?? 'Class ' . $academicClass->grade_level }}</span>
                </span>
            </div>

            <!-- Right: Chapter Metric Count -->
            <div class="flex items-center space-x-1.5 flex-shrink-0">
                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ $chapters->count() }} {{ Str::plural('Chapter', (int)$chapters->count()) }}
                </span>
            </div>
        </div>

        @php
            $completedChaptersCount = $chapters->where('status', 'completed')->count();
            $chaptersProgressPercent = $chapters->count() > 0 ? round(($completedChaptersCount / $chapters->count()) * 100) : 0;
        @endphp

        <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $subject->name }} Syllabus Progress:</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $completedChaptersCount }}/{{ $chapters->count() }} Chapters Completed ({{ $chaptersProgressPercent }}%)</span>
                </div>
                <span class="text-[10px] font-mono font-bold text-slate-400 dark:text-slate-500">Target Coverage</span>
            </div>
            <!-- Slim Inline Progress Bar (1.5px height) -->
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-300" style="width: {{ $chaptersProgressPercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- CHAPTERS LIST (COMPACT, UTILITARIAN WITH MINIMALIST BORDER DIVIDERS) -->
    <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
            <span>📖</span>
            <span>Chapters List ({{ $chapters->count() }})</span>
        </h2>
        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
            Dense View &bull; Select chapter to study
        </span>
    </div>

    @if($chapters->isEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">No chapters registered yet for this subject.</p>
        <a href="{{ route('batches.subjects', ['batch' => $batch->id]) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-md text-xs font-bold transition">
            &larr; Back to Subjects
        </a>
    </div>
    @else
    <!-- DENSE, COMPACT UTILITARIAN CHAPTER ROWS WITH MINIMALIST BORDER DIVIDERS -->
    <div class="space-y-2.5">
        @foreach($chapters as $ch)
        <a href="{{ route('batches.learn', ['batch' => $batch->id, 'subject' => $subject->id, 'chapter' => $ch->id]) }}"
           class="group flex items-center justify-between p-2 sm:p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500/70 hover:bg-slate-50/70 dark:hover:bg-slate-850/60 transition">
            
            <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0 flex-1">
                <!-- Chapter Number Badge -->
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 flex flex-col items-center justify-center text-center shrink-0 group-hover:scale-105 transition">
                    <span class="text-[8px] uppercase font-black text-emerald-600 dark:text-emerald-400 leading-none">CH</span>
                    <span class="text-xs sm:text-sm font-black text-emerald-800 dark:text-emerald-200 leading-tight">
                        {{ str_pad((string)$ch->chapter_number, 2, '0', STR_PAD_LEFT) }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1 mb-0.5">
                        <span class="text-[11px] font-mono font-bold text-slate-500 dark:text-slate-400">
                            Chapter {{ str_pad((string)$ch->chapter_number, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded {{ $ch->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                            {{ $ch->status === 'completed' ? '✓ Completed' : 'Active' }}
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            ⏱️ {{ max(7, ($ch->chapter_number ?: 1) * 7) }}d Target
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            📝 DPP: {{ $ch->dpps_count > 0 ? $ch->dpps_count . ' Sets' : 'Assigned' }}
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                            🏆 Test Ready
                        </span>
                    </div>

                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition truncate">
                        {{ $ch->title }}
                    </h3>
                </div>
            </div>

            <!-- Right: Content Counts & Study Action -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0 pl-2">
                <div class="hidden sm:flex items-center space-x-1.5 text-[11px] font-mono text-slate-400 dark:text-slate-500">
                    <span class="flex items-center space-x-0.5" title="Videos">
                        <span>🎬</span>
                        <span>{{ $ch->videos_count }}</span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center space-x-0.5" title="DPPs">
                        <span>📝</span>
                        <span>{{ $ch->dpps_count }}</span>
                    </span>
                </div>

                <span class="px-2.5 py-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 group-hover:bg-emerald-600 group-hover:text-white text-xs font-bold transition flex items-center space-x-1">
                    <span>Study</span>
                    <span>&rarr;</span>
                </span>
            </div>
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
