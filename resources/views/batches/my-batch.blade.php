@extends('layouts.app')

@section('title', ($batch ? $batch->name : 'My Batch') . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="space-y-3" x-data="{ currentTab: '{{ $activeTab }}' }">

    <!-- 1. COMPACT HEADER WITH INLINE PILL BADGES & EMBEDDED SLIM PROGRESS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <!-- Metadata Badges & Pill Tags right near top header -->
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $batch->code ?? 'BATCH' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        <span>{{ $batch->academicClass->name ?? 'Class' }}</span>
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono">
                        Session: {{ $batch->academic_year ?? '2026-2027' }}
                    </span>
                </div>
                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $batch->name ?? 'My Academic Batch' }}
                </h1>
            </div>

            <!-- Batch Switcher (for Admins, Teachers, or switching batches) -->
            @if($allBatches->count() > 1)
            <div class="flex items-center space-x-1.5 flex-shrink-0">
                <label for="batch-select" class="text-xs font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">Switch:</label>
                <select id="batch-select" 
                        onchange="window.location.href = '{{ route('my-batch') }}?batch_id=' + this.value"
                        class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-md px-2.5 py-1 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @foreach($allBatches as $b)
                    <option value="{{ $b->id }}" {{ $batch && $batch->id === $b->id ? 'selected' : '' }}>
                        {{ $b->name }} ({{ $b->code }})
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH HEADER -->
        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                <span>Batch Curriculum Readiness</span>
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">100% Active</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <!-- SUBJECTS LIST (COMPACT PILLS BAR) -->
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    Subjects in this Batch ({{ $subjects->count() }})
                </span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500">Select to view chapters</span>
            </div>

            <div class="flex flex-wrap gap-1.5">
                @forelse($subjects as $subj)
                @php
                    $isSelectedSubject = $selectedSubject && $selectedSubject->id === $subj->id;
                @endphp
                <a href="{{ route('my-batch', ['batch_id' => $batch->id, 'subject_id' => $subj->id]) }}"
                   class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-md text-xs font-bold transition {{ $isSelectedSubject ? 'bg-emerald-600 text-white shadow-xs ring-1 ring-emerald-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                    <span>📖 {{ $subj->name }}</span>
                    <span class="px-1.5 py-0.2 rounded font-mono text-[10px] font-bold {{ $isSelectedSubject ? 'bg-emerald-800 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                        {{ $subj->topics_count }}
                    </span>
                </a>
                @empty
                <p class="text-xs text-slate-400 py-1">No subjects found for this batch.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if($selectedSubject)
    <!-- CHAPTER SELECTOR & 5 TABS CONTAINER -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">

        <!-- LEFT SIDEBAR: CHAPTERS LIST -->
        <div class="lg:col-span-1 space-y-3">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm transition-colors">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">
                        Chapters ({{ $chapters->count() }})
                    </h3>
                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $selectedSubject->name }}
                    </span>
                </div>

                <div class="space-y-1.5 max-h-[520px] overflow-y-auto pr-1">
                    @forelse($chapters as $chap)
                    @php
                        $isSelectedChapter = $selectedChapter && $selectedChapter->id === $chap->id;
                    @endphp
                    <a href="{{ route('my-batch', ['batch_id' => $batch->id, 'subject_id' => $selectedSubject->id, 'topic_id' => $chap->id, 'tab' => $activeTab]) }}"
                       class="block p-2.5 rounded-xl text-xs transition {{ $isSelectedChapter ? 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-400 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200 font-bold' : 'bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-transparent' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded {{ $isSelectedChapter ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                                Ch {{ $chap->chapter_number }}
                            </span>
                            <div class="flex items-center space-x-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                                <span title="Videos">🎬 {{ $chap->videos_count }}</span>
                                <span title="DPPs">📝 {{ $chap->dpps_count }}</span>
                            </div>
                        </div>
                        <div class="mt-1 line-clamp-2 leading-snug">
                            {{ $chap->title }}
                        </div>
                    </a>
                    @empty
                    <p class="text-xs text-slate-400 py-3 text-center">No chapters recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT AREA: CHAPTER RESOURCES & 5 TABS -->
        <div class="lg:col-span-3 space-y-4">
            @if($selectedChapter)
            <!-- ACTIVE CHAPTER HEADER (COMPACT & DENSE) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Ch {{ $selectedChapter->chapter_number }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                {{ $selectedSubject->name }}
                            </span>
                        </div>
                        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white mt-0.5">
                            {{ $selectedChapter->title }}
                        </h2>
                    </div>
                    <div class="flex items-center space-x-1.5 flex-shrink-0">
                        <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ ucfirst($selectedChapter->status ?? 'Active') }}
                        </span>
                    </div>
                </div>

                <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
                <div>
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                        <span>Chapter Learning Mastery</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">In Progress</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: 70%"></div>
                    </div>
                </div>

                <!-- 1. COMPACT METRIC GRIDS: TIGHT MULTI-COLUMN CONTAINER FOR RESOURCE COUNTERS (Notes, Videos, DPP, Assignments, Tests) WITH INLINE BADGES & ZERO WASTED WHITESPACE -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 p-1 bg-slate-100/80 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg">
                    <!-- Resource 1: Notes -->
                    <button type="button"
                            @click="currentTab = 'notes'"
                            :class="currentTab === 'notes' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                            class="px-2 py-1.5 rounded-md border transition flex items-center justify-between gap-1 text-xs">
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-xs">📄</span>
                            <span class="truncate font-semibold text-[11px]">Notes</span>
                        </div>
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                              :class="currentTab === 'notes' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            1
                        </span>
                    </button>

                    <!-- Resource 2: Videos -->
                    <button type="button"
                            @click="currentTab = 'videos'"
                            :class="currentTab === 'videos' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                            class="px-2 py-1.5 rounded-md border transition flex items-center justify-between gap-1 text-xs">
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-xs">🎬</span>
                            <span class="truncate font-semibold text-[11px]">Videos</span>
                        </div>
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                              :class="currentTab === 'videos' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ $videos->count() }}
                        </span>
                    </button>

                    <!-- Resource 3: DPP -->
                    <button type="button"
                            @click="currentTab = 'dpps'"
                            :class="currentTab === 'dpps' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                            class="px-2 py-1.5 rounded-md border transition flex items-center justify-between gap-1 text-xs">
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-xs">📝</span>
                            <span class="truncate font-semibold text-[11px]">DPP</span>
                        </div>
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                              :class="currentTab === 'dpps' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ $dpps->count() }}
                        </span>
                    </button>

                    <!-- Resource 4: Assignments -->
                    <button type="button"
                            @click="currentTab = 'assignments'"
                            :class="currentTab === 'assignments' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                            class="px-2 py-1.5 rounded-md border transition flex items-center justify-between gap-1 text-xs">
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-xs">📋</span>
                            <span class="truncate font-semibold text-[11px]">Tasks</span>
                        </div>
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                              :class="currentTab === 'assignments' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ $assignments->count() }}
                        </span>
                    </button>

                    <!-- Resource 5: Tests -->
                    <button type="button"
                            @click="currentTab = 'tests'"
                            :class="currentTab === 'tests' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                            class="px-2 py-1.5 rounded-md border transition flex items-center justify-between gap-1 text-xs col-span-2 sm:col-span-1">
                        <div class="flex items-center gap-1 min-w-0">
                            <span class="text-xs">🧪</span>
                            <span class="truncate font-semibold text-[11px]">Tests</span>
                        </div>
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                              :class="currentTab === 'tests' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                            {{ $tests->count() }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: VIDEO LECTURES -->
            <div x-show="currentTab === 'videos'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                        Curriculum Video Lectures ({{ $videos->count() }})
                    </h4>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Streamable in HD</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @forelse($videos as $video)
                    @php
                        $isWatched = in_array($video->id, $watchedVideoIds);
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 hover:shadow-md transition">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                Lecture {{ $video->order_index }}
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $isWatched ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">
                                {{ $isWatched ? '✓ Watched' : 'Pending' }}
                            </span>
                        </div>
                        <h5 class="text-xs font-bold text-slate-900 dark:text-white mt-1.5 line-clamp-2">
                            {{ $video->title }}
                        </h5>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                            {{ $video->description }}
                        </p>
                        <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 dark:text-slate-400">⏱️ {{ $video->formatted_duration }}</span>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('videos.show', $video->id) }}" 
                                   class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                                    Watch &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                        No video lectures uploaded for this chapter yet.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 2: NOTES & GUIDES -->
            <div x-show="currentTab === 'notes'" class="space-y-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm transition-colors">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                Master Study Notes &bull; Chapter {{ $selectedChapter->chapter_number }}
                            </h4>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $selectedChapter->title }}</span>
                        </div>
                        @if(!empty($notes['resource_url']))
                        <a href="{{ $notes['resource_url'] }}" target="_blank" download
                           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Download PDF Notes</span>
                        </a>
                        @endif
                    </div>

                    <!-- Subtopics Pills -->
                    @if(!empty($notes['subtopics']))
                    <div class="mt-3">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-1.5">
                            Concepts in this Chapter
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($notes['subtopics'] as $st)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                &bull; {{ $st }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(empty($notes['resource_url']))
                    <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 text-xs text-center">
                        Official lecture PDF notes will be uploaded here by faculty.
                    </div>
                    @endif
                </div>
            </div>

            <!-- TAB 3: DAILY DPPS -->
            <div x-show="currentTab === 'dpps'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                        Daily Practice Problems (DPP) ({{ $dpps->count() }})
                    </h4>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Same-Day Cognitive Checking</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($dpps as $dpp)
                    @php
                        $sub = $dpp->submissions->first();
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 hover:shadow-sm transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                    {{ $dpp->dpp_number }}
                                </span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ $dpp->title }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center space-x-3">
                                <span>Questions: <strong>{{ $dpp->questions_count }}</strong></span>
                                <span>Total Marks: <strong>{{ $dpp->total_marks }}</strong></span>
                                <span>Difficulty: <strong class="capitalize">{{ $dpp->difficulty }}</strong></span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 flex-shrink-0">
                            @if($sub)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Score: {{ $sub->score }}/{{ $dpp->total_marks }} ({{ $sub->percentage }}%)
                            </span>
                            @endif

                            <a href="{{ route('dpp.show', $dpp->id) }}"
                               class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs">
                                {{ $sub ? 'Review DPP' : 'Attempt DPP &rarr;' }}
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                        No DPP assigned for this topic.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 4: ASSIGNMENTS -->
            <div x-show="currentTab === 'assignments'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                        Homework &amp; Assignments ({{ $assignments->count() }})
                    </h4>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Chapter Exercises &amp; Tasks</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($assignments as $hw)
                    @php
                        $sub = $hw->submissions->first();
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 hover:shadow-sm transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $hw->task_number }}
                                </span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ $hw->title }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center space-x-3">
                                <span>Due: <strong>{{ $hw->due_date?->format('M d, Y') ?? 'N/A' }}</strong></span>
                                <span>Max Marks: <strong>{{ $hw->max_marks }}</strong></span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 flex-shrink-0">
                            @if($sub)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Status: {{ ucfirst($sub->status) }} ({{ $sub->obtained_marks ?? 'Ungraded' }})
                            </span>
                            @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                Pending Submission
                            </span>
                            @endif

                            <a href="{{ route('homework.index') }}"
                               class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs">
                                View & Submit &rarr;
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                        No homework assignments recorded for this topic.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 5: TESTS (CHAPTER MOCK TESTS & TIMED QUIZZES) -->
            <div x-show="currentTab === 'tests'" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                        Chapter Mock Tests &amp; Quizzes ({{ $tests->count() }})
                    </h4>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Timed &bull; Evaluated Instantly</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($tests as $test)
                    @php
                        $sub = $test->submissions->first();
                    @endphp
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 hover:shadow-sm transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1.5">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 uppercase">
                                    🧪 Timed Test
                                </span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ $test->title }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>⏱️ <strong>{{ $test->duration_minutes }} Mins</strong></span>
                                <span>📊 <strong>{{ $test->questions_count }} Questions</strong></span>
                                <span>🎯 <strong>{{ $test->total_marks }} Marks</strong></span>
                                <span>Pass: <strong>{{ $test->pass_percentage }}%</strong></span>
                                @if($test->negative_marks > 0)
                                <span class="text-rose-500 font-bold">-{{ $test->negative_marks }} Neg Marks</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 flex-shrink-0">
                            @if($sub)
                            <div class="text-right">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $sub->is_passed ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                                    {{ $sub->is_passed ? 'Passed' : 'Needs Review' }}: {{ $sub->score }}/{{ $test->total_marks }}
                                </span>
                            </div>
                            @endif

                            <a href="{{ route('dpp.show', $test->id) }}"
                               class="px-4 py-2 rounded-xl text-xs font-extrabold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white transition shadow-sm">
                                {{ $sub ? 'Review Results' : 'Start Test ⚡' }}
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                        No mock tests created for this topic yet.
                    </div>
                    @endforelse
                </div>
            </div>

            @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500 text-xs">
                Select a chapter from the left to view video lectures, notes, DPPs, homework, and tests.
            </div>
            @endif
        </div>

    </div>
    @else
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500 text-xs">
        No subjects found for this batch.
    </div>
    @endif

</div>
@endsection
