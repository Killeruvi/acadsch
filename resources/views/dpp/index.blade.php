@extends('layouts.app')

@section('title', 'DPP & Timed Tests' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="dppComponent()" class="space-y-4">

    <!-- Header Section (Compact, Fire & Inspiring) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-3 sm:p-3.5 text-slate-900 dark:text-white shadow-2xs border border-slate-200 dark:border-slate-800 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        ⏱️ Practice Hub
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 Aim, Ascend, Achieve
                    </span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white mt-0.5">
                    Daily DPPs &amp; Timed Examination Tests
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Single-question CBT practice with automated scoring, solution keys, and re-attempt / resolve capabilities.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                @if(Auth::user()->isStudent())
                <div class="bg-slate-50 dark:bg-slate-800 rounded-lg px-2.5 py-1 border border-slate-200 dark:border-slate-700 text-center">
                    <span class="text-[9px] text-slate-400 block uppercase font-bold">Solved</span>
                    <span class="text-xs font-black text-amber-500">{{ $solvedCount }}</span>
                </div>
                @else
                <div class="flex items-center space-x-1.5">
                    <a href="{{ route('dpp.sample-csv') }}" 
                       title="Download sample CSV template for DPP questions" 
                       class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 rounded-lg text-xs font-bold border border-emerald-200 dark:border-emerald-800 transition flex items-center space-x-1 shadow-2xs">
                        <span>📥 CSV</span>
                    </a>
                    <button @click.stop="openBankModal = true" 
                            type="button" 
                            class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                        + Bank
                    </button>
                    <button @click.stop="openCreateModal = true" 
                            type="button" 
                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-2xs transition">
                        + Create DPP / Test
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SIMPLE LEAST-COMPLICATED FORM: SELECT CLASS & SELECT SUBJECT -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-3 sm:p-3.5 border border-slate-200 dark:border-slate-800 shadow-2xs">
        <form action="{{ route('dpp.index') }}" method="GET" data-spa-auto-submit="true" data-preserve-scroll="true" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 items-end">
            <!-- 1. Select Class -->
            <div>
                <label for="dpp_class_id" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    1. Select Class / Grade
                </label>
                <select id="dpp_class_id" name="class_id" data-spa-filter="true" onchange="const s = document.getElementById('dpp_subject_id'); if (s) s.value = '';" class="spa-auto-submit w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                    <option value="all" {{ request('class_id') === 'all' ? 'selected' : '' }}>All Classes &amp; Grades</option>
                    @foreach($allClasses as $c)
                    <option value="{{ $c->id }}" {{ ($selectedClass && (int)$selectedClass->id === (int)$c->id) ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Select Subject -->
            <div>
                <label for="dpp_subject_id" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    2. Select Subject
                </label>
                <select id="dpp_subject_id" name="subject_id" data-spa-filter="true" class="spa-auto-submit w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                    <option value="">{{ $selectedClass ? 'All Subjects in ' . $selectedClass->display_name : 'All Subjects (Academy Wide)' }}</option>
                    @foreach($subjects ?? [] as $subj)
                    <option value="{{ $subj->id }}" {{ (string)($selectedSubjectId ?? '') === (string)$subj->id ? 'selected' : '' }}>
                        {{ $subj->name }} ({{ $subj->code }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filter Type -->
            <div>
                <label for="dpp_filter_type" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    3. Practice Type
                </label>
                <select id="dpp_filter_type" name="type" data-spa-filter="true" class="spa-auto-submit w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                    <option value="all" {{ ($filterType ?? 'all') === 'all' ? 'selected' : '' }}>All Sets &amp; Tests</option>
                    <option value="dpp" {{ ($filterType ?? 'all') === 'dpp' ? 'selected' : '' }}>📝 Daily DPPs (Practice)</option>
                    <option value="test" {{ ($filterType ?? 'all') === 'test' ? 'selected' : '' }}>⏱️ Timed Tests &amp; Exams</option>
                    <option value="rapid_fire" {{ ($filterType ?? 'all') === 'rapid_fire' ? 'selected' : '' }}>⚡ Rapid-Fire Speed Sprints ({{ $rapidFireCount ?? 24 }})</option>
                    <option value="bank" {{ ($filterType ?? 'all') === 'bank' ? 'selected' : '' }}>🏛️ Question Bank ({{ $bankCount }})</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Active Filter Indicator -->
    <div class="flex items-center justify-between px-1">
        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
            Showing practice sheets for: <strong class="text-slate-900 dark:text-white">{{ $selectedClass->display_name ?? 'All Classes (Full Academy)' }}</strong>
            @if(!empty($selectedSubjectId))
            &bull; Subject: <strong class="text-indigo-600 dark:text-indigo-400">{{ ($subjects ?? collect())->firstWhere('id', (int)$selectedSubjectId)?->name }}</strong>
            @endif
        </div>
        <span class="text-[11px] text-slate-400 font-semibold">{{ $dpps->total() }} Practice Sets</span>
    </div>

    <!-- QUESTION BANK REPOSITORY VIEW -->
    @if($filterType === 'bank')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base">Institutional Question Bank ({{ $selectedClass->display_name ?? 'Class 8' }})</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Reusable catalog of questions for tests and daily practice problems</p>
            </div>
            @if(!Auth::user()->isStudent())
            <button @click="openBankModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition self-start sm:self-auto shadow-2xs">
                + Add Question to Bank
            </button>
            @endif
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($questionBank as $idx => $q)
            <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition space-y-1.5">
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="w-5 h-5 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-[10px]">
                        #{{ $idx + 1 }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $q->subject->name ?? 'Subject' }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase {{ $q->difficulty === 'olympiad' ? 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300' : ($q->difficulty === 'advanced' ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300') }}">
                        {{ $q->difficulty }}
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                        Correct: Option {{ $q->correct_option }}
                    </span>
                    @if($q->tags)
                    <span class="text-[10px] text-slate-400 font-mono">{{ $q->tags }}</span>
                    @endif
                </div>

                <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm pt-0.5">
                    {{ $q->question_text }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs text-slate-600 dark:text-slate-300 pt-0.5">
                    <div class="p-1.5 rounded border {{ $q->correct_option === 'A' ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/40 font-bold text-emerald-800 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/40' }}">
                        A) {{ $q->option_a }}
                    </div>
                    <div class="p-1.5 rounded border {{ $q->correct_option === 'B' ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/40 font-bold text-emerald-800 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/40' }}">
                        B) {{ $q->option_b }}
                    </div>
                    <div class="p-1.5 rounded border {{ $q->correct_option === 'C' ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/40 font-bold text-emerald-800 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/40' }}">
                        C) {{ $q->option_c }}
                    </div>
                    <div class="p-1.5 rounded border {{ $q->correct_option === 'D' ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50/50 dark:bg-emerald-950/40 font-bold text-emerald-800 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/40' }}">
                        D) {{ $q->option_d }}
                    </div>
                </div>

                <div class="p-2 bg-indigo-50/40 dark:bg-indigo-950/30 rounded-lg text-xs text-slate-600 dark:text-slate-400 border border-indigo-100/60 dark:border-indigo-800/40 mt-1">
                    <strong class="text-indigo-800 dark:text-indigo-300">Explanation:</strong> {{ $q->explanation }}
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 space-y-1.5">
                <div class="text-2xl">🏛️</div>
                <p class="font-semibold text-slate-600 dark:text-slate-300 text-xs">No questions cataloged in the Question Bank for this class yet.</p>
                <p class="text-[11px] text-slate-400">Click "+ Add to Question Bank" to begin building the repository.</p>
            </div>
            @endforelse
        </div>
    </div>
    @else

    <!-- DPP & TIMED TESTS LIST -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3 sm:p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                    {{ $filterType === 'test' ? 'Timed Examinations & CBT Tests' : ($filterType === 'dpp' ? 'Daily Practice Problems (DPP)' : 'Practice Problems & Timed Tests') }}
                </h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Single-question test stepper with automated same-day evaluation</p>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                Page {{ $dpps->currentPage() }}/{{ $dpps->lastPage() }}
            </span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($dpps as $dpp)
            @php
                $submission = $mySubmissions[$dpp->id] ?? null;
                $isTest = $dpp->isTest();
            @endphp
            <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition flex flex-col md:flex-row md:items-center justify-between gap-2.5 sm:gap-3">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <!-- Module Type Badge -->
                        @if($isTest)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800 flex items-center space-x-1">
                                <span>⏱️</span>
                                <span>Exam</span>
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-1">
                                <span>🎯</span>
                                <span>DPP</span>
                            </span>
                        @endif

                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            {{ $dpp->dpp_number }}
                        </span>

                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                            {{ $dpp->subject->name ?? 'Subject' }}
                        </span>

                        <!-- Timer badge -->
                        @if($dpp->isTimed())
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                                ⏱️ {{ $dpp->duration_minutes }}m
                            </span>
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                Untimed
                            </span>
                        @endif

                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase {{ $dpp->difficulty === 'olympiad' ? 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300' : ($dpp->difficulty === 'advanced' ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300') }}">
                            {{ $dpp->difficulty }}
                        </span>

                        <span class="text-slate-400 text-[10px]">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $dpp->assigned_date->format('M d, Y') }}</span>
                    </div>

                    <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                        <a href="{{ route('dpp.show', $dpp->id) }}" class="hover:text-indigo-600 transition">
                            {{ $dpp->title }}
                        </a>
                    </h4>

                    @if($dpp->topic)
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Topic: <strong class="text-slate-700 dark:text-slate-300">{{ $dpp->topic->topic_name }}</strong> (Ch {{ $dpp->topic->chapter_number }})
                    </p>
                    @endif

                    <div class="text-[10px] text-slate-400">
                        {{ $dpp->questions_count ?? $dpp->questions->count() }} Qs &bull; {{ $dpp->total_marks }} Marks 
                        @if($isTest) &bull; Pass: {{ $dpp->pass_percentage }}% @endif
                        &bull; By <strong class="text-slate-600 dark:text-slate-300">{{ $dpp->teacher->name ?? 'Faculty' }}</strong>
                    </div>
                </div>

                <!-- Action Button & Status -->
                <div class="flex items-center space-x-2 self-end md:self-center">
                    @if(Auth::user()->isStudent())
                        @if($submission)
                            <div class="text-right">
                                <div class="text-xs font-bold text-emerald-600">
                                    {{ $submission->score }}/{{ $submission->max_score }} ({{ $submission->percentage }}%)
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $submission->is_passed ? 'Passed ✅' : 'Revise ⚠️' }}
                                    @if($submission->time_taken_seconds)
                                        &bull; {{ floor($submission->time_taken_seconds / 60) }}m {{ $submission->time_taken_seconds % 60 }}s
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('dpp.show', $dpp->id) }}" 
                               class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap">
                                Solutions &rarr;
                            </a>
                        @else
                            <a href="{{ route('dpp.show', $dpp->id) }}" 
                               class="px-3.5 py-1.5 bg-gradient-to-r {{ $isTest ? 'from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500' : 'from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600' }} text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1.5 whitespace-nowrap">
                                <span>{{ $isTest ? 'Start Exam' : 'Solve DPP' }}</span>
                                <span class="bg-black/20 px-1 py-0.2 rounded text-[9px] text-amber-300 font-extrabold">+{{ $isTest ? 50 : 30 }} XP</span>
                            </a>
                        @endif
                    @else
                        <div class="text-right">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ $dpp->submissions_count ?? $dpp->submissions->count() }} Subs
                            </div>
                            <div class="text-[10px] text-emerald-600 font-semibold">Auto-checked</div>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <a href="{{ route('dpp.edit', $dpp->id) }}" 
                               class="px-2 py-1 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-bold border border-indigo-200 dark:border-indigo-800 transition">
                                ✏️ Edit
                            </a>
                            <form method="POST" action="{{ route('dpp.destroy', $dpp->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to permanently delete this DPP / Timed Test set?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-lg text-[11px] font-bold border border-rose-200 dark:border-rose-800 transition" title="Delete DPP">
                                    🗑️
                                </button>
                            </form>
                            <a href="{{ route('dpp.show', $dpp->id) }}" 
                               class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-[11px] font-bold transition">
                                Key &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-12 text-center text-slate-400 space-y-2">
                <div class="text-3xl">📝</div>
                <p class="font-semibold text-slate-600">No practice problem sets or timed tests found for this class.</p>
                <p class="text-xs text-slate-400">Click "+ Create DPP or Timed Test" to add one.</p>
                @if(!Auth::user()->isStudent())
                <button @click.stop="openCreateModal = true" type="button" class="mt-2 inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition">
                    <span>+ Create First DPP or Test</span>
                </button>
                @endif
            </div>
            @endforelse
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $dpps->links() }}
        </div>
    </div>
    @endif

    <!-- CREATE DPP / TIMED TEST MODAL (TEACHER / ADMIN) -->
    @if(!Auth::user()->isStudent())
    <div x-show="openCreateModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openCreateModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-3xl w-full p-6 sm:p-8 animate-in fade-in duration-150 max-h-[90vh] overflow-y-auto z-10">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Create DPP or Timed Online Examination</h3>
                    <p class="text-xs text-slate-500">Choose between Daily DPP or Timed Test, set countdown timer, and import from Question Bank</p>
                </div>
                <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

        <form method="POST" action="{{ route('dpp.store') }}" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf

            <!-- Module Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Module Format *</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl border cursor-pointer transition flex items-center space-x-3" 
                           :class="itemType === 'dpp' ? 'border-emerald-600 bg-emerald-50/50 ring-1 ring-emerald-600' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="type" value="dpp" x-model="itemType" class="sr-only">
                        <span class="text-xl">🎯</span>
                        <div>
                            <span class="text-xs font-bold block text-slate-900">Daily Practice Problem (DPP)</span>
                            <span class="text-[11px] text-slate-500">Regular daily curriculum practice set</span>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border cursor-pointer transition flex items-center space-x-3" 
                           :class="itemType === 'test' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="type" value="test" x-model="itemType" class="sr-only">
                        <span class="text-xl">⏱️</span>
                        <div>
                            <span class="text-xs font-bold block text-slate-900">Timed Online Examination (Test)</span>
                            <span class="text-[11px] text-slate-500">Formal test with countdown clock &amp; pass criteria</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Class *</label>
                    <select name="class_id" x-model="selectedClassId" @change="createSubjectId = ''" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Subject *</label>
                    <select name="subject_id" x-model="createSubjectId" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                        <option value="">-- Select Subject --</option>
                        <template x-for="subj in availableSubjects" :key="subj.id">
                            <option :value="subj.id" x-text="subj.name + ' (' + (subj.code || 'Subj') + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Assigned Date *</label>
                    <input type="date" name="assigned_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Weekly Chapter 4 Examination or Light Reactions DPP" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Difficulty *</label>
                    <select name="difficulty" x-model="difficulty" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        <option value="standard">Standard (NCERT / Board)</option>
                        <option value="advanced">Advanced (Application)</option>
                        <option value="olympiad">Olympiad / Competitive</option>
                    </select>
                </div>
            </div>

            <!-- Timer, Pass Percentage & Negative Marking Controls -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        ⏱️ Countdown Timer *
                    </label>
                    <input type="number" name="duration_minutes" x-model="durationMinutes" min="0" max="300" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold bg-white">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">0 for untimed, or minutes (15, 30, 60).</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Passing Marks (%)
                    </label>
                    <input type="number" name="pass_percentage" x-model="passPercentage" min="1" max="100" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold bg-white">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Passing threshold (40% DPP, 50% Test).</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Negative Marking
                    </label>
                    <input type="number" name="negative_marks" step="0.25" min="0" max="5" value="1.0" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold bg-white">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Penalty per wrong answer (e.g. 1.0).</span>
                </div>
            </div>

            <!-- BULK CSV / EXCEL UPLOAD BOX -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border-2 border-dashed border-emerald-500/30 dark:border-emerald-500/40 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start space-x-3">
                        <div class="p-2.5 rounded-xl bg-emerald-600 text-white font-black text-base shadow-sm">
                            📊
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span>Bulk Upload Questions via CSV / Excel</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">Automated</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Upload a CSV, TSV, or spreadsheet export. Instantly auto-fills all questions, options A-D, answer keys, and solutions!
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <a href="{{ route('dpp.sample-csv') }}" 
                           class="px-3 py-1.5 rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs">
                            <span>📥 Download Sample CSV</span>
                        </a>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <div class="relative w-full">
                        <input type="file" 
                               id="dpp_csv_file_input"
                               name="csv_file" 
                               accept=".csv,.tsv,.txt"
                               @change="handleCsvUpload($event)"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 file:cursor-pointer cursor-pointer border border-slate-300 rounded-xl bg-white p-1">
                    </div>

                    <button type="button" 
                            x-show="questions.length > 1 || (questions.length === 1 && questions[0].text.trim())" 
                            @click="resetQuestions()"
                            class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-rose-600 whitespace-nowrap transition">
                        Reset / Clear
                    </button>
                </div>

                <!-- Column Reference Chips -->
                <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-500 pt-1">
                    <span class="font-bold text-slate-600">Expected Columns:</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Question</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Option A</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Option B</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Option C</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Option D</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Correct Option</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Explanation</span>
                    <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-mono text-[10px]">Marks</span>
                </div>

                <!-- Live Status Feedback -->
                <div x-show="csvMessage" x-cloak class="p-2.5 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-semibold flex items-center space-x-2">
                    <span>✅</span>
                    <span x-text="csvMessage"></span>
                </div>
                <div x-show="csvError" x-cloak class="p-2.5 rounded-xl bg-rose-100 border border-rose-300 text-rose-800 text-xs font-semibold flex items-center space-x-2">
                    <span>⚠️</span>
                    <span x-text="csvError"></span>
                </div>
            </div>

            <!-- Questions Builder Header & Mode Toggle -->
            <div class="pt-2 border-t border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Questions (<span x-text="questions.length"></span>)
                        </span>
                        <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs">
                            <button type="button" 
                                    @click="builderMode = 'single'" 
                                    :class="builderMode === 'single' ? 'bg-white text-indigo-600 shadow-2xs font-bold' : 'text-slate-600 font-medium'" 
                                    class="px-2.5 py-1 rounded-md transition">
                                🎯 Single Stepper
                            </button>
                            <button type="button" 
                                    @click="builderMode = 'list'" 
                                    :class="builderMode === 'list' ? 'bg-white text-indigo-600 shadow-2xs font-bold' : 'text-slate-600 font-medium'" 
                                    class="px-2.5 py-1 rounded-md transition">
                                📜 All List
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Select from Question Bank Button -->
                        <button type="button" @click.stop="openBankPickerModal = true" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold transition flex items-center space-x-1">
                            <span>🏛️ Question Bank</span>
                        </button>

                        <button type="button" @click="addQuestion()" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1">
                            <span>+ Add Question</span>
                        </button>
                    </div>
                </div>

                <!-- SINGLE QUESTION REVIEW STEPPER (ONE AT A TIME) -->
                <div x-show="builderMode === 'single'" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700">
                            Question <strong class="text-indigo-600" x-text="builderIndex + 1"></strong> of <strong x-text="questions.length"></strong>
                        </span>
                        <div class="flex items-center space-x-1.5">
                            <button type="button" 
                                    @click="if (builderIndex > 0) builderIndex--" 
                                    :disabled="builderIndex === 0" 
                                    class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 transition">
                                &larr; Prev
                            </button>
                            <button type="button" 
                                    @click="if (builderIndex < questions.length - 1) builderIndex++" 
                                    :disabled="builderIndex >= questions.length - 1" 
                                    class="px-2.5 py-1 rounded-lg bg-slate-900 text-white text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-800 transition">
                                Next &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-500 to-emerald-400 h-1.5 rounded-full transition-all" :style="'width: ' + ((builderIndex + 1) / questions.length * 100) + '%'"></div>
                    </div>

                    <!-- Numbered Palette -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                        <template x-for="(q, idx) in questions" :key="idx">
                            <button type="button" 
                                    @click="builderIndex = idx" 
                                    class="w-7 h-7 rounded-lg text-xs font-bold flex items-center justify-center transition flex-shrink-0"
                                    :class="builderIndex === idx ? 'ring-2 ring-indigo-600 bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                    x-text="idx + 1">
                            </button>
                        </template>
                    </div>

                    <!-- Single Card Content -->
                    <div x-show="questions[builderIndex]" class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-700" x-text="'Question #' + (builderIndex + 1)"></span>
                            <div class="flex items-center space-x-2">
                                <span class="text-[11px] text-slate-500 font-medium">Marks:</span>
                                <input type="number" :name="'questions[' + builderIndex + '][marks]'" x-model="questions[builderIndex].marks" min="1" max="20" class="w-14 px-2 py-0.5 rounded border border-slate-300 text-xs font-bold text-center">
                                <button type="button" @click="removeQuestion(builderIndex)" x-show="questions.length > 1" class="text-rose-500 hover:text-rose-700 text-xs font-semibold ml-2">
                                    Remove
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Question Prompt *</label>
                            <textarea :name="'questions[' + builderIndex + '][text]'" x-model="questions[builderIndex].text" required rows="2" placeholder="Write question prompt..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium bg-white"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500">Option A *</label>
                                <input type="text" :name="'questions[' + builderIndex + '][option_a]'" x-model="questions[builderIndex].option_a" required placeholder="Option A text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500">Option B *</label>
                                <input type="text" :name="'questions[' + builderIndex + '][option_b]'" x-model="questions[builderIndex].option_b" required placeholder="Option B text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500">Option C</label>
                                <input type="text" :name="'questions[' + builderIndex + '][option_c]'" x-model="questions[builderIndex].option_c" placeholder="Option C text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500">Option D</label>
                                <input type="text" :name="'questions[' + builderIndex + '][option_d]'" x-model="questions[builderIndex].option_d" placeholder="Option D text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium bg-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                            <div>
                                <label class="block text-[11px] font-bold text-emerald-700">Correct Option *</label>
                                <select :name="'questions[' + builderIndex + '][correct]'" x-model="questions[builderIndex].correct" required class="w-full px-3 py-1.5 rounded-lg border border-emerald-300 text-xs font-bold bg-white text-emerald-800">
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-indigo-700">Step-by-Step Solution Explanation *</label>
                                <input type="text" :name="'questions[' + builderIndex + '][explanation]'" x-model="questions[builderIndex].explanation" required placeholder="Explanation shown to students after test completion..." class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALL QUESTIONS LIST MODE -->
                <div x-show="builderMode === 'list'" class="space-y-4">
                    <template x-for="(q, idx) in questions" :key="idx">
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-bold text-indigo-700" x-text="'Question #' + (idx + 1)"></span>
                                    <button type="button" @click="builderIndex = idx; builderMode = 'single'" class="text-[10px] text-indigo-600 font-bold hover:underline">
                                        Review Single &rarr;
                                    </button>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[11px] text-slate-500 font-medium">Marks:</span>
                                    <input type="number" :name="'questions[' + idx + '][marks]'" x-model="q.marks" min="1" max="20" class="w-14 px-2 py-0.5 rounded border border-slate-300 text-xs font-bold text-center">
                                    <button type="button" @click="removeQuestion(idx)" x-show="questions.length > 1" class="text-rose-500 hover:text-rose-700 text-xs font-semibold ml-2">
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div>
                                <textarea :name="'questions[' + idx + '][text]'" x-model="q.text" required rows="2" placeholder="Write question prompt..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium"></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500">Option A</label>
                                    <input type="text" :name="'questions[' + idx + '][option_a]'" x-model="q.option_a" required placeholder="Option A text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500">Option B</label>
                                    <input type="text" :name="'questions[' + idx + '][option_b]'" x-model="q.option_b" required placeholder="Option B text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500">Option C</label>
                                    <input type="text" :name="'questions[' + idx + '][option_c]'" x-model="q.option_c" required placeholder="Option C text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500">Option D</label>
                                    <input type="text" :name="'questions[' + idx + '][option_d]'" x-model="q.option_d" required placeholder="Option D text" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                <div>
                                    <label class="block text-[11px] font-bold text-emerald-700">Correct Option *</label>
                                    <select :name="'questions[' + idx + '][correct]'" x-model="q.correct" required class="w-full px-3 py-1.5 rounded-lg border border-emerald-300 text-xs font-bold bg-white text-emerald-800">
                                        <option value="A">Option A</option>
                                        <option value="B">Option B</option>
                                        <option value="C">Option C</option>
                                        <option value="D">Option D</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-indigo-700">Step-by-Step Solution Explanation *</label>
                                    <input type="text" :name="'questions[' + idx + '][explanation]'" x-model="q.explanation" required placeholder="Explanation shown to students after test completion..." class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Save to Question Bank Checkbox -->
            <div class="pt-2">
                <label class="flex items-center space-x-2 text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="save_to_bank" value="1" x-model="saveToBank" class="rounded text-indigo-600 focus:ring-indigo-500">
                    <span class="font-semibold">Also save these questions to the Institutional Question Bank for future reuse</span>
                </label>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" @click="openCreateModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                    Publish <span x-text="itemType === 'test' ? 'Timed Test' : 'Daily DPP'"></span> &amp; Activate Timer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- QUESTION BANK SELECTOR DRAWER / MODAL -->
<div x-show="openBankPickerModal" x-cloak 
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openBankPickerModal = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full p-6 animate-in fade-in duration-150 max-h-[85vh] flex flex-col z-10">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">Select from Question Bank</h3>
                <p class="text-xs text-slate-500">Select questions to instantly add to your DPP or Test</p>
            </div>
            <button type="button" @click="openBankPickerModal = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="py-3">
            <input type="text" x-model="bankSearchQuery" placeholder="Search questions by topic, text, or tag..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
        </div>

        <!-- Questions List with Checkboxes -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl p-2 max-h-[50vh]">
            <template x-for="bq in filteredBankQuestions" :key="bq.id">
                <label class="p-3 hover:bg-slate-50 flex items-start space-x-3 cursor-pointer rounded-lg transition">
                    <input type="checkbox" :value="bq.id" x-model="selectedBankIndices[bq.id]" class="mt-1 rounded text-indigo-600 focus:ring-indigo-500">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2 text-[10px]">
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold" x-text="bq.subject ? bq.subject.name : 'Subject'"></span>
                            <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold uppercase" x-text="bq.difficulty"></span>
                            <span class="text-slate-400" x-text="'Key: ' + bq.correct_option"></span>
                        </div>
                        <p class="text-xs font-semibold text-slate-900 mt-1 leading-snug" x-text="bq.question_text"></p>
                        <p class="text-[11px] text-slate-500 mt-0.5 truncate" x-text="'A: ' + bq.option_a + ' | B: ' + bq.option_b"></p>
                    </div>
                </label>
            </template>
            <div x-show="filteredBankQuestions.length === 0" class="p-8 text-center text-slate-400 text-xs">
                No matching questions found in the Question Bank.
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 mt-3">
            <span class="text-xs text-slate-500 font-medium">
                <span x-text="Object.values(selectedBankIndices).filter(Boolean).length"></span> questions selected
            </span>
            <div class="flex space-x-2">
                <button type="button" @click="openBankPickerModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                <button type="button" @click="importSelectedFromBank()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                    Import Selected Questions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ADD TO QUESTION BANK MODAL -->
<div x-show="openBankModal" x-cloak 
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openBankModal = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-xl w-full p-6 animate-in fade-in duration-150 z-10">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">Add to Question Bank</h3>
                <p class="text-xs text-slate-500">Catalog reusable questions across subjects and chapters</p>
            </div>
            <button type="button" @click="openBankModal = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Mode Tabs: Single vs Bulk CSV -->
        <div class="flex border-b border-slate-200 mt-2">
            <button type="button" 
                    @click="bankModalTab = 'single'"
                    class="py-2 px-4 text-xs font-bold border-b-2 transition"
                    :class="bankModalTab === 'single' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                ✍️ Single Question
            </button>
            <button type="button" 
                    @click="bankModalTab = 'bulk'"
                    class="py-2 px-4 text-xs font-bold border-b-2 transition flex items-center space-x-1"
                    :class="bankModalTab === 'bulk' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                <span>📊 Bulk CSV Upload</span>
                <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-emerald-100 text-emerald-800">Fast</span>
            </button>
        </div>

        <!-- 1. Single Question Form -->
        <form x-show="bankModalTab === 'single'" method="POST" action="{{ route('question-bank.store') }}" class="space-y-3 mt-3">
            @csrf

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Target Class *</label>
                    <select name="class_id" x-model="selectedClassId" @change="bankSubjectId = ''" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white font-bold">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Subject *</label>
                    <select name="subject_id" x-model="bankSubjectId" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white font-bold">
                        <option value="">-- Select Subject --</option>
                        <template x-for="subj in availableSubjects" :key="subj.id">
                            <option :value="subj.id" x-text="subj.name + ' (' + (subj.code || 'Subj') + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Question Text *</label>
                <textarea name="question_text" required rows="2" placeholder="Write question prompt..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500">Option A *</label>
                    <input type="text" name="option_a" required class="w-full px-3 py-1 rounded-lg border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500">Option B *</label>
                    <input type="text" name="option_b" required class="w-full px-3 py-1 rounded-lg border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500">Option C *</label>
                    <input type="text" name="option_c" required class="w-full px-3 py-1 rounded-lg border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500">Option D *</label>
                    <input type="text" name="option_d" required class="w-full px-3 py-1 rounded-lg border border-slate-300 text-xs">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-1">
                <div>
                    <label class="block text-[10px] font-bold text-emerald-700">Correct Option *</label>
                    <select name="correct_option" required class="w-full px-2 py-1 rounded-lg border border-emerald-300 text-xs font-bold bg-white text-emerald-800">
                        <option value="A">Option A</option>
                        <option value="B">Option B</option>
                        <option value="C">Option C</option>
                        <option value="D">Option D</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-600">Difficulty</label>
                    <select name="difficulty" required class="w-full px-2 py-1 rounded-lg border border-slate-300 text-xs bg-white">
                        <option value="standard">Standard</option>
                        <option value="advanced">Advanced</option>
                        <option value="olympiad">Olympiad</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-600">Marks</label>
                    <input type="number" name="marks" value="4" min="1" max="20" required class="w-full px-2 py-1 rounded-lg border border-slate-300 text-xs font-bold text-center">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-indigo-700 mb-1">Step-by-Step Solution &amp; Explanation *</label>
                <input type="text" name="explanation" required placeholder="Explanation for students..." class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Tags (Optional)</label>
                <input type="text" name="tags" placeholder="e.g. Biology, Photosynthesis, NCERT" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium">
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" @click="openBankModal = false" class="px-4 py-1.5 text-slate-600 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                    Save to Question Bank
                </button>
            </div>
        </form>

        <!-- 2. Bulk CSV Import Form -->
        <form x-show="bankModalTab === 'bulk'" x-cloak method="POST" action="{{ route('dpp.bulk-import') }}" enctype="multipart/form-data" class="space-y-3 mt-3">
            @csrf
            <input type="hidden" name="target" value="bank">

            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800">
                <p class="font-bold">📥 Bulk Import Questions into Question Bank</p>
                <p class="text-[11px] text-emerald-700 mt-0.5">Upload a CSV or spreadsheet containing questions, options, answers, and solutions.</p>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Target Class *</label>
                    <select name="class_id" x-model="selectedClassId" @change="bankSubjectId = ''" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white font-bold">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Subject *</label>
                    <select name="subject_id" x-model="bankSubjectId" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white font-bold">
                        <option value="">-- Select Subject --</option>
                        <template x-for="subj in availableSubjects" :key="subj.id">
                            <option :value="subj.id" x-text="subj.name + ' (' + (subj.code || 'Subj') + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Difficulty</label>
                <select name="difficulty" required class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                    <option value="standard">Standard (NCERT / Board)</option>
                    <option value="advanced">Advanced (Application)</option>
                    <option value="olympiad">Olympiad / Competitive</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Upload CSV / TSV File *</label>
                <input type="file" name="csv_file" accept=".csv,.tsv,.txt" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 file:cursor-pointer cursor-pointer border border-slate-300 rounded-lg bg-white p-1">
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a href="{{ route('dpp.sample-csv') }}" class="text-[11px] text-emerald-600 hover:underline font-bold flex items-center space-x-1">
                    <span>📥 Download Sample CSV</span>
                </a>
                <div class="flex space-x-2">
                    <button type="button" @click="openBankModal = false" class="px-4 py-1.5 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                        Import to Question Bank
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif


</div>

<script>
function dppComponent() {
    return { 
    openCreateModal: false,
    openBankModal: false,
    openBankPickerModal: false,
    bankModalTab: 'single',
    builderMode: 'single',
    builderIndex: 0,
    selectedClassId: '{{ $selectedClass->id ?? '' }}',
    createSubjectId: '',
    bankSubjectId: '',
    bulkSubjectId: '',
    classesData: {{ Js::from($allClasses) }},
    itemType: 'dpp', // 'dpp' or 'test'
    durationMinutes: 15,
    passPercentage: 40,
    difficulty: 'standard',
    saveToBank: true,
    get availableSubjects() {
        const cls = this.classesData.find(c => c.id == this.selectedClassId);
        return cls ? cls.subjects : [];
    },
    questions: [
        { text: '', option_a: '', option_b: '', option_c: '', option_d: '', correct: 'A', explanation: '', marks: 4 }
    ],
    addQuestion() {
        if (this.questions.length < 50) {
            this.questions.push({ text: '', option_a: '', option_b: '', option_c: '', option_d: '', correct: 'A', explanation: '', marks: 4 });
        }
    },
    removeQuestion(idx) {
        if (this.questions.length > 1) {
            this.questions.splice(idx, 1);
        }
    },
    resetQuestions() {
        if (confirm('Clear all questions and start fresh?')) {
            this.questions = [
                { text: '', option_a: '', option_b: '', option_c: '', option_d: '', correct: 'A', explanation: '', marks: 4 }
            ];
            this.csvFileName = '';
            this.csvQuestionsCount = 0;
            this.csvMessage = '';
            this.csvError = '';
            const fInput = document.getElementById('dpp_csv_file_input');
            if (fInput) fInput.value = '';
        }
    },
    // CSV Bulk Upload state & handlers
    csvFileName: '',
    csvQuestionsCount: 0,
    csvMessage: '',
    csvError: '',
    handleCsvUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        this.csvFileName = file.name;
        this.csvError = '';
        this.csvMessage = '';

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const text = e.target.result;
                const parsed = this.parseCsvQuestions(text);
                if (!parsed || parsed.length === 0) {
                    this.csvError = 'No valid questions found in this file. Please check column headers or download the sample template.';
                    return;
                }

                // If current list only has 1 blank question, replace it; else append
                if (this.questions.length === 1 && !this.questions[0].text.trim()) {
                    this.questions = parsed;
                } else {
                    this.questions = this.questions.concat(parsed);
                }

                this.csvQuestionsCount = parsed.length;
                this.csvMessage = `Successfully imported ${parsed.length} questions from ${file.name}! You can review, edit, or adjust marks below.`;
            } catch (err) {
                console.error(err);
                this.csvError = 'Error reading file: ' + err.message;
            }
        };
        reader.readAsText(file);
    },
    parseCsvQuestions(text) {
        // Strip BOM
        text = text.replace(/^\uFEFF/, '');

        // Auto-detect delimiter from first non-empty line
        const rawLines = text.split(/\r\n|\n|\r/);
        const firstLine = rawLines.find(l => l.trim().length > 0) || '';
        let delim = ',';
        const commas = (firstLine.match(/,/g) || []).length;
        const semis = (firstLine.match(/;/g) || []).length;
        const tabs = (firstLine.match(/\t/g) || []).length;
        if (semis > commas && semis > tabs) delim = ';';
        else if (tabs > commas && tabs > semis) delim = '\t';

        // Parse CSV rows handling quoted values
        const rows = [];
        let currentRow = [''];
        let inQuotes = false;

        for (let i = 0; i < text.length; i++) {
            const c = text[i];
            const next = text[i + 1];

            if (c === '"') {
                if (inQuotes && next === '"') {
                    currentRow[currentRow.length - 1] += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (c === delim && !inQuotes) {
                currentRow.push('');
            } else if ((c === '\r' || c === '\n') && !inQuotes) {
                if (c === '\r' && next === '\n') i++;
                if (currentRow.some(cell => cell.trim().length > 0)) {
                    rows.push(currentRow);
                }
                currentRow = [''];
            } else {
                currentRow[currentRow.length - 1] += c;
            }
        }
        if (currentRow.some(cell => cell.trim().length > 0)) {
            rows.push(currentRow);
        }

        if (rows.length < 2) return [];

        const headerRow = rows[0].map(h => h.trim().toLowerCase().replace(/[^a-z0-9]/g, '_'));

        const findIdx = (candidates) => {
            return headerRow.findIndex(h => candidates.some(cand => h === cand || h.includes(cand)));
        };

        const qIdx = findIdx(['question_text', 'question', 'prompt', 'text', 'q']);
        const aIdx = findIdx(['option_a', 'optiona', 'opt_a', 'a']);
        const bIdx = findIdx(['option_b', 'optionb', 'opt_b', 'b']);
        const cIdx = findIdx(['option_c', 'optionc', 'opt_c', 'c']);
        const dIdx = findIdx(['option_d', 'optiond', 'opt_d', 'd']);
        const ansIdx = findIdx(['correct_option', 'correct', 'answer', 'ans']);
        const expIdx = findIdx(['explanation', 'solution', 'sol', 'exp']);
        const marksIdx = findIdx(['marks', 'mark', 'score']);

        if (qIdx === -1 || aIdx === -1 || bIdx === -1) {
            return [];
        }

        const questions = [];
        for (let r = 1; r < rows.length; r++) {
            const row = rows[r];
            const qText = (row[qIdx] || '').trim();
            const optA = (row[aIdx] || '').trim();
            const optB = (row[bIdx] || '').trim();
            const optC = (cIdx !== -1 ? row[cIdx] : '') || '';
            const optD = (dIdx !== -1 ? row[dIdx] : '') || '';

            if (!qText || !optA) continue;

            let rawAns = (ansIdx !== -1 ? (row[ansIdx] || '').trim().toUpperCase() : 'A');
            let correct = 'A';
            if (rawAns.includes('B') || rawAns === '2') correct = 'B';
            else if (rawAns.includes('C') || rawAns === '3') correct = 'C';
            else if (rawAns.includes('D') || rawAns === '4') correct = 'D';
            else correct = 'A';

            const exp = (expIdx !== -1 ? (row[expIdx] || '').trim() : '');
            let marks = 4;
            if (marksIdx !== -1 && row[marksIdx]) {
                const parsedMarks = parseInt(row[marksIdx], 10);
                if (!isNaN(parsedMarks) && parsedMarks > 0) marks = parsedMarks;
            }

            questions.push({
                text: qText,
                option_a: optA,
                option_b: optB,
                option_c: optC.trim(),
                option_d: optD.trim(),
                correct: correct,
                explanation: exp,
                marks: marks
            });
        }

        return questions;
    },
    // Question Bank Picker state
    bankQuestionsList: {{ Js::from($questionBank) }},
    bankSearchQuery: '',
    selectedBankIndices: {},
    get filteredBankQuestions() {
        if (!this.bankSearchQuery) return this.bankQuestionsList;
        const q = this.bankSearchQuery.toLowerCase();
        return this.bankQuestionsList.filter(item => 
            (item.question_text && item.question_text.toLowerCase().includes(q)) ||
            (item.tags && item.tags.toLowerCase().includes(q)) ||
            (item.subject && item.subject.name && item.subject.name.toLowerCase().includes(q))
        );
    },
    importSelectedFromBank() {
        const selectedIds = Object.keys(this.selectedBankIndices).filter(k => this.selectedBankIndices[k]);
        if (selectedIds.length === 0) {
            alert('Please select at least one question from the Question Bank.');
            return;
        }
        
        selectedIds.forEach(id => {
            const bq = this.bankQuestionsList.find(b => b.id == id);
            if (bq) {
                // If first question is empty, replace it, else push
                if (this.questions.length === 1 && !this.questions[0].text.trim()) {
                    this.questions[0] = {
                        text: bq.question_text,
                        option_a: bq.option_a,
                        option_b: bq.option_b,
                        option_c: bq.option_c,
                        option_d: bq.option_d,
                        correct: bq.correct_option,
                        explanation: bq.explanation,
                        marks: bq.marks || 4
                    };
                } else {
                    this.questions.push({
                        text: bq.question_text,
                        option_a: bq.option_a,
                        option_b: bq.option_b,
                        option_c: bq.option_c,
                        option_d: bq.option_d,
                        correct: bq.correct_option,
                        explanation: bq.explanation,
                        marks: bq.marks || 4
                    });
                }
            }
        });
        
        this.selectedBankIndices = {};
        this.openBankPickerModal = false;
    }
};
}
</script>
@endsection