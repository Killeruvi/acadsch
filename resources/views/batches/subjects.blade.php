@extends('layouts.app')

@section('title', ($batch ? $batch->name : 'My Batch') . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="w-full max-w-6xl mx-auto px-2 sm:px-4 py-3 space-y-3">

    @if(session('success'))
    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">&times;</button>
    </div>
    @endif

    <!-- 1. COMPACT TOP HEADER WITH INLINE METADATA PILL BADGES & EMBEDDED SLIM PROGRESS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <!-- Metadata Pills right near top header -->
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        <span>{{ $academicClass->name ?? 'Grade ' . $academicClass->grade_level }}</span>
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300">
                        Section {{ $academicClass?->section ?? 'A' }}
                    </span>
                    @if(!empty($batch->timing_slot))
                    <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60">
                        <span>{{ $batch->timing_slot }}</span>
                    </span>
                    @endif
                </div>

                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $batch->name }}
                </h1>
            </div>

            <!-- Switch Batch Button (Teacher/Admin only) -->
            @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
            <div class="flex items-center space-x-1.5 flex-shrink-0">
                <a href="{{ route('batches.select', ['class_id' => $batch->class_id]) }}"
                   class="px-2.5 py-1 rounded-md border border-slate-200 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                    <span>🔄</span>
                    <span>Change Class / Section</span>
                </a>
            </div>
            @endif
        </div>

        <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <span class="flex items-center gap-1.5">
                    <span>📚 Academic Subjects:</span>
                    <strong class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $subjects->count() }} Registered</strong>
                </span>
                <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">100% Curriculum Available</span>
            </div>
            <!-- Slim 1.5px Progress Bar -->
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" style="width: 100%"></div>
            </div>
        </div>
    </div>

    <!-- COMPACT SUBJECTS DIRECTORY WITH MINIMALIST BORDER DIVIDERS -->
    <div class="flex items-center justify-between pb-1 border-b border-slate-200 dark:border-slate-800">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center space-x-1.5">
            <span>📚</span>
            <span>Subjects Directory ({{ $subjects->count() }})</span>
        </h2>
        <span class="text-[11px] text-slate-400 dark:text-slate-500">
            Select subject to open chapters
        </span>
    </div>

    @if($subjects->isEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-6 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">No subjects found for this class.</p>
        @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
        <a href="{{ route('batches.select') }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-md text-xs font-bold transition">
            &larr; Choose Another Class
        </a>
        @endif
    </div>
    @else
    <!-- DENSE, RECTANGULAR UTILITARIAN SUBJECTS LIST WITH MINIMALIST BORDER DIVIDERS -->
    <div class="space-y-2.5">
        @foreach($subjects as $subj)
        @php
            $nameLower = strtolower($subj->name);
            $icon = '📚';
            $badgeColor = 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300';
            if (str_contains($nameLower, 'math')) {
                $icon = '📐';
                $badgeColor = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300';
            } elseif (str_contains($nameLower, 'phys')) {
                $icon = '⚡';
                $badgeColor = 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
            } elseif (str_contains($nameLower, 'chem')) {
                $icon = '🧪';
                $badgeColor = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
            } elseif (str_contains($nameLower, 'bio')) {
                $icon = '🧬';
                $badgeColor = 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300';
            } elseif (str_contains($nameLower, 'scien')) {
                $icon = '🔬';
                $badgeColor = 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300';
            } elseif (str_contains($nameLower, 'eng') || str_contains($nameLower, 'lit')) {
                $icon = '📖';
                $badgeColor = 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300';
            } elseif (str_contains($nameLower, 'social') || str_contains($nameLower, 'hist') || str_contains($nameLower, 'geo')) {
                $icon = '🌍';
                $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300';
            }
        @endphp

        <a href="{{ route('batches.chapters', ['batch' => $batch->id, 'subject' => $subj->id]) }}"
           class="group flex items-center justify-between p-2 sm:p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-500/70 hover:bg-slate-50/70 dark:hover:bg-slate-850/60 transition">
            
            <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0 flex-1">
                <!-- Subject Icon Badge -->
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-md {{ $badgeColor }} flex items-center justify-center text-base sm:text-lg shrink-0 group-hover:scale-105 transition">
                    {{ $icon }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-2 mb-0.5">
                        <span class="text-[10px] sm:text-[11px] font-mono font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ $subj->code ?? 'SUBJ' }}
                        </span>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center space-x-1">
                            <span>📖</span>
                            <span>{{ $subj->topics_count ?? 0 }} {{ Str::plural('Chapter', (int)($subj->topics_count ?? 0)) }}</span>
                        </span>
                    </div>

                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition truncate">
                        {{ $subj->name }}
                    </h3>
                </div>
            </div>

            <!-- Right: Content Counts & Open Action -->
            <div class="flex items-center space-x-2 sm:space-x-3 shrink-0 pl-2">
                <div class="hidden sm:flex items-center space-x-1.5 text-[11px] font-mono text-slate-400 dark:text-slate-500">
                    <span class="flex items-center space-x-0.5" title="Videos">
                        <span>🎬</span>
                        <span>{{ $subj->videos_count }}</span>
                    </span>
                    <span>&bull;</span>
                    <span class="flex items-center space-x-0.5" title="DPPs">
                        <span>📝</span>
                        <span>{{ $subj->dpps_count }}</span>
                    </span>
                </div>

                <span class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 group-hover:bg-emerald-600 text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-white transition flex items-center space-x-1">
                    <span>Open</span>
                    <span>&rarr;</span>
                </span>
            </div>
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
