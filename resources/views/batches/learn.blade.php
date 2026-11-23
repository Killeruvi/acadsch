@extends('layouts.app')

@section('title', ($selectedChapter ? 'Ch ' . $selectedChapter->chapter_number . ': ' . $selectedChapter->title . ' - ' : '') . $subject->name . ' - ' . $batch->name)

@section('content')
<div class="w-full max-w-6xl mx-auto px-2 sm:px-4 py-3 space-y-2.5" 
     x-data="{ 
        currentTab: '{{ request()->query('tab', 'notes') }}', 
        isUploadNoteOpen: false, 
        isCreateVideoOpen: false,
        isCreateDppOpen: false,
        isCreateHwOpen: false,
        videoView: 'list',
        expandedVideoId: null,
        previewPdfTitle: '', 
        previewPdfUrl: '', 
        isPdfModalOpen: false 
     }">

    @if(session('success'))
    <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 font-bold">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800 dark:text-rose-400 font-bold">&times;</button>
    </div>
    @endif

    <!-- 1. COMPACT METADATA HEADER WITH INLINE PILL BADGES & EMBEDDED SLIM PROGRESS BAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <!-- Top Row: Back button, Inline Pill Badges for metadata right near header -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                <!-- Back Link -->
                <a href="{{ route('batches.chapters', ['batch' => $batch->id, 'subject' => $subject->id]) }}" 
                   class="inline-flex items-center space-x-1 text-xs font-bold text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition mr-1">
                    <span class="font-black">&larr;</span>
                    <span>Back to Chapters</span>
                </a>

                <!-- Batch Name Pastel Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60">
                    <span class="mr-1">🎒</span>
                    <span>{{ $batch->name }}</span>
                </span>

                <!-- Subject Tag Pastel Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 dark:bg-sky-950/50 text-sky-800 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800/60">
                    <span class="mr-1">🧬</span>
                    <span>{{ $subject->name }}</span>
                </span>

                <!-- Chapter Tag Pastel Pill Badge -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60">
                    <span class="mr-1">📖</span>
                    <span>Chapter {{ $selectedChapter ? str_pad((string)$selectedChapter->chapter_number, 2, '0', STR_PAD_LEFT) : '01' }}@if($selectedChapter && $selectedChapter->title) &bull; {{ Str::limit($selectedChapter->title, 26) }}@endif</span>
                </span>
            </div>

            <!-- Header Status -->
            <div class="flex items-center space-x-1.5 flex-shrink-0">
                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ ucfirst($selectedChapter?->status ?? 'Active') }}
                </span>
            </div>
        </div>

        <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
            <div class="flex flex-wrap items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 gap-2 mb-1">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="flex items-center gap-1">
                        <span>🎓 Subject Progress:</span>
                        <strong class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $completedSubjectVideos }}/{{ $totalSubjectVideos }} ({{ $overallProgressPercent }}%)</strong>
                    </span>
                    <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">&bull;</span>
                    <span class="flex items-center gap-1">
                        <span>📖 Chapter {{ $selectedChapter ? $selectedChapter->chapter_number : 1 }}:</span>
                        <strong class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $chapterCompletedVideos }}/{{ $chapterTotalVideos }} ({{ $chapterProgressPercent }}%)</strong>
                    </span>
                </div>
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Curriculum Metrics</span>
            </div>
            <!-- Slim Dual Progress Bar (1.5px height to maximize visible data per screen height) -->
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden flex">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-300" style="width: {{ $overallProgressPercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- 1. COMPACT METRIC GRIDS: TIGHT MULTI-COLUMN CONTAINER FOR RESOURCE COUNTERS (Notes, Videos, DPP, Assignments, Tests) WITH INLINE BADGES & ZERO WASTED WHITESPACE -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 p-1 bg-slate-100/80 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg">
        
        <!-- Resource 1: Notes -->
        <button type="button" 
                @click="currentTab = 'notes'"
                :class="currentTab === 'notes' 
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                class="px-2.5 py-1.5 rounded-md border transition flex items-center justify-between gap-1.5 text-xs">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm">📕</span>
                <span class="truncate font-semibold">Notes &amp; Guide</span>
            </div>
            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                  :class="currentTab === 'notes' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                {{ $notesList->count() }}
            </span>
        </button>

        <!-- Resource 2: Videos -->
        <button type="button" 
                @click="currentTab = 'videos'"
                :class="currentTab === 'videos' 
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                class="px-2.5 py-1.5 rounded-md border transition flex items-center justify-between gap-1.5 text-xs">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm">🎬</span>
                <span class="truncate font-semibold">Videos</span>
            </div>
            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                  :class="currentTab === 'videos' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                {{ $videos->count() }}
            </span>
        </button>

        <!-- Resource 3: DPP -->
        <button type="button" 
                @click="currentTab = 'dpps'"
                :class="currentTab === 'dpps' 
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                class="px-2.5 py-1.5 rounded-md border transition flex items-center justify-between gap-1.5 text-xs">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm">📝</span>
                <span class="truncate font-semibold">DPP</span>
            </div>
            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                  :class="currentTab === 'dpps' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                {{ $dpps->count() }}
            </span>
        </button>

        <!-- Resource 4: Assignments -->
        <button type="button" 
                @click="currentTab = 'assignments'"
                :class="currentTab === 'assignments' 
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                class="px-2.5 py-1.5 rounded-md border transition flex items-center justify-between gap-1.5 text-xs">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm">📋</span>
                <span class="truncate font-semibold">Assignments</span>
            </div>
            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                  :class="currentTab === 'assignments' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                {{ $assignments->count() }}
            </span>
        </button>

        <!-- Resource 5: Tests -->
        <button type="button" 
                @click="currentTab = 'tests'"
                :class="currentTab === 'tests' 
                    ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white border-emerald-500/50 dark:border-emerald-500 shadow-2xs font-extrabold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40 border-transparent font-medium'"
                class="px-2.5 py-1.5 rounded-md border transition flex items-center justify-between gap-1.5 text-xs col-span-2 sm:col-span-1">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-sm">🧪</span>
                <span class="truncate font-semibold">Tests</span>
            </div>
            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold"
                  :class="currentTab === 'tests' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">
                {{ $tests->count() }}
            </span>
        </button>
    </div>

    <!-- 3. CONTENT DISPLAY AREA: MINIMALIST FLAT PANEL WITH SUBTLE BORDER -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 min-h-[300px]">

        <!-- TAB 1 CONTENT: VIDEOS (LIST VIEW & GRID VIEW WITH DROPDOWN EXPANSION) -->
        <div x-show="currentTab === 'videos'" class="space-y-4">
            
            <!-- Video Lectures Subheader with View Switcher -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Lecture Directory</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        {{ $videos->count() }} {{ Str::plural('Lecture', (int)$videos->count()) }}
                    </span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                        &bull; {{ $chapterCompletedVideos }} Completed
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    @if((auth()->user()->isTeacher() || auth()->user()->isAdmin()) && $selectedChapter)
                    <button type="button" 
                            @click="isCreateVideoOpen = true"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs flex items-center space-x-1.5">
                        <span>➕</span>
                        <span>Add Video Lecture</span>
                    </button>
                    @endif

                    <!-- View Switcher Toggle: List vs Grid -->
                    <div class="flex items-center space-x-1 p-0.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs">
                        <button type="button" 
                                @click="videoView = 'list'"
                                :class="videoView === 'list' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 font-medium'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1.5">
                            <span>☰</span>
                            <span>Normal List View</span>
                        </button>
                        <button type="button" 
                                @click="videoView = 'grid'"
                                :class="videoView === 'grid' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 font-medium'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1.5">
                            <span>⊞</span>
                            <span>Grid View</span>
                        </button>
                    </div>
                </div>
            </div>

            @if($videos->isEmpty())
            <!-- Minimal Empty-State View -->
            <div class="py-12 sm:py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3 shadow-2xs">
                    🎬
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Video Lectures Yet</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm mx-auto">Video sessions for this topic are being prepared.</p>
                @if((auth()->user()->isTeacher() || auth()->user()->isAdmin()) && $selectedChapter)
                <button type="button" @click="isCreateVideoOpen = true" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs inline-flex items-center space-x-1.5">
                    <span>➕ Add First Video Lecture</span>
                </button>
                @endif
            </div>
            @else

            <!-- 1. NORMAL LIST VIEW (CLEAN LECTURE TITLES WITH DROPDOWN) -->
            <div x-show="videoView === 'list'" class="space-y-2">
                @foreach($videos as $idx => $vid)
                @php
                    $isWatched = in_array($vid->id, $watchedVideoIds);
                @endphp
                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-850/80 overflow-hidden transition">
                    <div class="p-3.5 sm:p-4 flex items-center justify-between cursor-pointer hover:bg-slate-100/60 dark:hover:bg-slate-800/60 transition"
                         @click="expandedVideoId = (expandedVideoId === {{ $vid->id }} ? null : {{ $vid->id }})">
                        <div class="flex items-center space-x-3 min-w-0 flex-1">
                            <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-black text-xs flex items-center justify-center shrink-0">
                                L{{ $idx + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                    Lecture {{ $idx + 1 }}: {{ $vid->title }}
                                </h3>
                                <div class="flex items-center space-x-2 text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                    <span>⏱️ {{ $vid->duration_minutes ?? 30 }} mins</span>
                                    <span>&bull;</span>
                                    <span>HD 1080p Stream</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2.5 shrink-0 pl-2">
                            @if($isWatched)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                ✓ Completed
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                Unwatched
                            </span>
                            @endif

                            <button type="button" 
                                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
                                <svg class="w-4 h-4 transform transition-transform duration-200"
                                     :class="{ 'rotate-180': expandedVideoId === {{ $vid->id }} }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown Accordion Content -->
                    <div x-show="expandedVideoId === {{ $vid->id }}" x-cloak class="px-4 pb-4 pt-1 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed pt-2">
                            {{ $vid->description ?? 'Detailed interactive lecture session exploring core fundamentals, practical derivations, and real-world implementations.' }}
                        </p>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center space-x-3 text-[11px] text-slate-500">
                                <span>🎥 Streaming Platform Ready</span>
                                <span>&bull;</span>
                                <span>Duration: {{ $vid->duration_minutes ?? 30 }} mins</span>
                            </div>
                            <a href="{{ route('videos.show', $vid->id) }}"
                               class="px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1.5">
                                <span>▶️ Watch Lecture</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- 2. COMPACT GRID VIEW -->
            <div x-show="videoView === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($videos as $idx => $vid)
                @php
                    $isWatched = in_array($vid->id, $watchedVideoIds);
                @endphp
                <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-850 hover:border-emerald-500/50 transition shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-mono font-black uppercase px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Lecture {{ $idx + 1 }}
                            </span>
                            @if($isWatched)
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">✓ Done</span>
                            @else
                            <span class="text-[10px] text-slate-400">⏱️ {{ $vid->duration_minutes ?? 30 }}m</span>
                            @endif
                        </div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white line-clamp-2">
                            {{ $vid->title }}
                        </h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                            {{ $vid->description ?? 'Standard curriculum lecture' }}
                        </p>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">HD 1080p Stream</span>
                        <a href="{{ route('videos.show', $vid->id) }}"
                           class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-2xs flex items-center space-x-1">
                            <span>Watch</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>

        <!-- TAB 2 CONTENT: NOTES & GUIDE -->
        <div x-show="currentTab === 'notes'" class="space-y-3">
            <!-- Notes Subheader -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Curriculum PDF Notes &amp; Guides</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        {{ $notesList->count() + (!empty($notes['study_notes']) || !empty($notes['resource_url']) ? 1 : 0) }} Documents
                    </span>
                </div>
                @if((auth()->user()->isTeacher() || auth()->user()->isAdmin()) && $selectedChapter)
                <button type="button" 
                        @click="isUploadNoteOpen = true"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1.5">
                    <span>➕</span>
                    <span>Upload PDF Note</span>
                </button>
                @endif
            </div>

            @if(!empty($notes['study_notes']) || !empty($notes['resource_url']))
            <div class="bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/60 rounded-lg p-3 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">
                                Master Study Notes
                            </span>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                {{ $selectedChapter?->title }}
                            </span>
                        </div>
                        @if(!empty($notes['study_notes']))
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                            {{ $notes['study_notes'] }}
                        </p>
                        @endif
                    </div>
                    @if(!empty($notes['resource_url']))
                    <a href="{{ $notes['resource_url'] }}" target="_blank" download class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center space-x-1 flex-shrink-0">
                        <span>📥 Download PDF</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @foreach($notesList as $note)
            @php
                $noteIcon = '📕';
                $badgeText = 'Theory Notes';
                if ($note->note_type === 'formula_sheet') {
                    $noteIcon = '📐';
                    $badgeText = 'Formula Sheet';
                } elseif ($note->note_type === 'ncert_solution') {
                    $noteIcon = '📖';
                    $badgeText = 'NCERT Solution';
                } elseif ($note->note_type === 'revision') {
                    $noteIcon = '⚡';
                    $badgeText = 'Quick Revision';
                }
            @endphp
            <div class="bg-slate-50/80 dark:bg-slate-850/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 sm:p-3 transition hover:border-slate-300 dark:hover:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 min-w-0">
                <div class="flex items-start space-x-3 sm:space-x-3.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg flex-shrink-0">
                        {{ $noteIcon }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 flex-shrink-0">
                                PDF • {{ $badgeText }}
                            </span>
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white break-words">
                                {{ $note->title }}
                            </h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-400 dark:text-slate-500 mt-1.5">
                            <span>⏱️ {{ $note->reading_minutes ?? 10 }} Mins read</span>
                            <span>&bull;</span>
                            <span>PDF Document</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-center">
                    <button type="button"
                            @click="previewPdfTitle = '{{ addslashes($note->title) }}'; previewPdfUrl = '{{ $note->download_url }}'; isPdfModalOpen = true"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>👁️ View PDF</span>
                    </button>
                    @if(!empty($note->download_url))
                    <a href="{{ $note->download_url }}" target="_blank" download
                       class="px-3 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition flex items-center space-x-1">
                        <span>📥 Download</span>
                    </a>
                    @endif
                    @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('batches.notes.delete', $note->id) }}" onsubmit="return confirm('Delete this PDF note?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-xl text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition" title="Delete Note">
                            🗑️
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach

            @if($notesList->isEmpty() && empty($notes['study_notes']) && empty($notes['resource_url']))
            <!-- Minimal Empty-State View -->
            <div class="py-12 sm:py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3 shadow-2xs">
                    📕
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Notes or Guides Yet</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm mx-auto">Study material and lecture notes for this topic are being prepared.</p>
                @if((auth()->user()->isTeacher() || auth()->user()->isAdmin()) && $selectedChapter)
                <button type="button" @click="isUploadNoteOpen = true" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs">
                    Upload First PDF Note
                </button>
                @endif
            </div>
            @endif
        </div>

        <!-- TAB 3 CONTENT: DAILY PRACTICE PROBLEMS (DPP) -->
        <div x-show="currentTab === 'dpps'" class="space-y-3">
            <!-- DPP Subheader with Create Button & Universal Link -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Daily Practice Problems (DPP)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        {{ $dpps->count() }} DPP Sheets
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                    <button type="button" 
                            @click="isCreateDppOpen = true"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>➕</span>
                        <span>Post DPP Sheet</span>
                    </button>
                    @endif
                    <a href="{{ route('dpp.index', ['class_id' => $academicClass->id, 'subject_id' => $subject->id]) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition flex items-center space-x-1">
                        <span>Full DPP Hub &rarr;</span>
                    </a>
                </div>
            </div>

            @forelse($dpps as $dpp)
            <div class="bg-slate-50/80 dark:bg-slate-850/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 sm:p-3 transition hover:border-slate-300 dark:hover:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 min-w-0">
                <div class="flex items-start space-x-3 sm:space-x-3.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg flex-shrink-0">
                        📝
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60 flex-shrink-0">
                                {{ $dpp->dpp_number }}
                            </span>
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white break-words">
                                {{ $dpp->title }}
                            </h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1.5">
                            <span>🎯 {{ $dpp->questions_count }} Problems</span>
                            <span>&bull;</span>
                            <span>{{ $dpp->total_marks }} Marks</span>
                            <span>&bull;</span>
                            <span class="capitalize font-semibold text-slate-600 dark:text-slate-300">{{ $dpp->difficulty }}</span>
                            @if($dpp->duration_minutes)
                            <span>&bull;</span>
                            <span>⏱️ {{ $dpp->duration_minutes }}m</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Actions: Solve DPP & Resolve DPP + Faculty Quick Controls -->
                <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-center">
                    @if($dpp->user_submission)
                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold {{ $dpp->user_submission->is_passed ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                        Score: {{ $dpp->user_submission->score }}/{{ $dpp->user_submission->max_score }}
                    </span>
                    <a href="{{ route('dpp.show', ['id' => $dpp->id, 'resolve' => 1]) }}"
                       title="Practice this DPP sheet again"
                       class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>🔄</span>
                        <span>Resolve</span>
                    </a>
                    <a href="{{ route('dpp.show', $dpp->id) }}"
                       class="px-3 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-slate-300 transition">
                        Scorecard
                    </a>
                    @else
                    <a href="{{ route('dpp.show', $dpp->id) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs flex items-center space-x-1.5">
                        <span>Solve DPP</span>
                        <span>&rarr;</span>
                    </a>
                    @endif

                    @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                    <a href="{{ route('dpp.edit', $dpp->id) }}" title="Edit DPP metadata & questions" class="p-2 rounded-xl text-xs text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition">
                        ✏️
                    </a>
                    <a href="{{ route('dpp.export-csv', $dpp->id) }}" title="Export questions as CSV" class="p-2 rounded-xl text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        📥
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <!-- Minimal Empty-State View -->
            <div class="py-12 sm:py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3 shadow-2xs">
                    📝
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Daily Practice Problems Yet</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm mx-auto">Daily practice problems will be posted along with classes.</p>
                @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                <button type="button" @click="isCreateDppOpen = true" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs">
                    Post First DPP Sheet
                </button>
                @endif
            </div>
            @endforelse
        </div>

        <!-- TAB 4 CONTENT: ASSIGNMENTS (HOMEWORK) -->
        <div x-show="currentTab === 'assignments'" class="space-y-3">
            <!-- Homework Subheader with Assign Button & Universal Link -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Chapter Homework Tasks &amp; Deliverables</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold">
                        {{ $assignments->count() }} Tasks
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                    <button type="button" 
                            @click="isCreateHwOpen = true"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>➕</span>
                        <span>Assign Homework</span>
                    </button>
                    @endif
                    <a href="{{ route('homework.index', ['class_id' => $academicClass->id, 'subject_id' => $subject->id]) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition flex items-center space-x-1">
                        <span>Full Homework Hub &rarr;</span>
                    </a>
                </div>
            </div>

            @forelse($assignments as $hw)
            @php
                $sub = $hw->submissions->first();
            @endphp
            <div class="bg-slate-50/80 dark:bg-slate-850/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 sm:p-3 transition hover:border-slate-300 dark:hover:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 min-w-0">
                <div class="flex items-start space-x-3 sm:space-x-3.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg flex-shrink-0">
                        📋
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white break-words">
                                {{ $hw->title }}
                            </h3>
                            @if($hw->attachment_path)
                            <a href="{{ route('homework.download-attachment', ['id' => $hw->id, 'type' => 'task']) }}" 
                               class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200/80 flex items-center space-x-1"
                               title="Download Teacher Reference Document">
                                <span>📎</span>
                                <span>Handout</span>
                            </a>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 break-words line-clamp-2">
                            {{ $hw->instructions ?? 'Submit completed homework document.' }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-400 dark:text-slate-500 mt-1.5">
                            <span>📅 Due: {{ \Carbon\Carbon::parse($hw->due_date)->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span>Max {{ $hw->max_marks ?? 100 }} Marks</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-center">
                    @if($sub)
                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                        Status: {{ ucfirst($sub->status) }}
                    </span>
                    @else
                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                        Pending
                    </span>
                    @endif

                    <a href="{{ route('homework.index', ['class_id' => $academicClass->id, 'subject_id' => $subject->id]) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1.5">
                        <span>View &amp; Submit</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
            @empty
            <!-- Minimal Empty-State View -->
            <div class="py-12 sm:py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3 shadow-2xs">
                    📋
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Assignments Posted Yet</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm mx-auto">There are no pending homework assignments for this topic.</p>
                @if(auth()->user()->isTeacher() || auth()->user()->isAdmin())
                <button type="button" @click="isCreateHwOpen = true" class="mt-4 px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-xs">
                    Assign First Homework Task
                </button>
                @endif
            </div>
            @endforelse
        </div>

        <!-- TAB 5 CONTENT: TESTS (CHAPTER MOCK TESTS & QUIZZES) -->
        <div x-show="currentTab === 'tests'" class="space-y-3">
            @forelse($tests as $test)
            <div class="bg-slate-50/80 dark:bg-slate-850/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 sm:p-3 transition hover:border-slate-300 dark:hover:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 min-w-0">
                <div class="flex items-start space-x-3 sm:space-x-3.5 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg flex-shrink-0">
                        🧪
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 flex-shrink-0">
                                Timed Test
                            </span>
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white break-words">
                                {{ $test->title }}
                            </h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400 mt-1.5">
                            <span>⏱️ {{ $test->duration_minutes ?? 30 }} Minutes</span>
                            <span>&bull;</span>
                            <span>{{ $test->questions_count }} Questions</span>
                            <span>&bull;</span>
                            <span>{{ $test->total_marks }} Marks (Pass: {{ $test->pass_percentage }}%)</span>
                            @if($test->negative_marks)
                            <span>&bull;</span>
                            <span class="text-rose-500 dark:text-rose-400 font-semibold">-{{ $test->negative_marks }} Neg</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-center">
                    @if($test->user_submission)
                    <span class="px-2.5 py-1 rounded-xl text-xs font-bold {{ $test->user_submission->is_passed ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                        Score: {{ $test->user_submission->score }}/{{ $test->user_submission->max_score }}
                    </span>
                    <a href="{{ route('dpp.show', ['id' => $test->id, 'resolve' => 1]) }}"
                       class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>🔄</span>
                        <span>Retest</span>
                    </a>
                    <a href="{{ route('dpp.show', $test->id) }}"
                       class="px-3 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-slate-300 transition">
                        Scorecard
                    </a>
                    @else
                    <a href="{{ route('dpp.show', $test->id) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white transition shadow-xs flex items-center space-x-1.5">
                        <span>Start Test</span>
                        <span>&rarr;</span>
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <!-- Minimal Empty-State View -->
            <div class="py-12 sm:py-16 text-center">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-xl mb-3 shadow-2xs">
                    🧪
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Chapter Tests Scheduled</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm mx-auto">Upcoming chapter mock tests will appear here.</p>
            </div>
            @endforelse
        </div>

    </div>

    <!-- PDF DOCUMENT VIEWER MODAL -->
    <div x-show="isPdfModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="isPdfModalOpen = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-4xl w-full p-4 sm:p-5 shadow-2xl relative max-h-[90vh] flex flex-col"
             @click.away="isPdfModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2 truncate pr-2">
                    <span class="text-base flex-shrink-0">📕</span>
                    <div class="truncate">
                        <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400 block leading-tight">Curriculum PDF Viewer</span>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white truncate" x-text="previewPdfTitle"></h3>
                    </div>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <template x-if="previewPdfUrl && previewPdfUrl !== '#'">
                        <a :href="previewPdfUrl" target="_blank" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-1">
                            <span>Open in Tab ↗</span>
                        </a>
                    </template>
                    <template x-if="previewPdfUrl && previewPdfUrl !== '#'">
                        <a :href="previewPdfUrl" download target="_blank" class="px-2.5 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center space-x-1">
                            <span>📥 Download</span>
                        </a>
                    </template>
                    <button type="button" @click="isPdfModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none p-1">&times;</button>
                </div>
            </div>
            
            <div class="py-3 flex-1 min-h-[380px] sm:min-h-[500px] bg-slate-50 dark:bg-slate-950/50 rounded-2xl overflow-hidden flex flex-col">
                <template x-if="previewPdfUrl && previewPdfUrl !== '#'">
                    <iframe :src="previewPdfUrl" class="w-full h-full flex-1 border-0 rounded-xl" title="PDF Document Viewer"></iframe>
                </template>
                <template x-if="!previewPdfUrl || previewPdfUrl === '#'">
                    <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                        <span class="text-4xl mb-2">📄</span>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300">PDF preview is not directly available.</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Please download the file to view it on your device.</p>
                    </div>
                </template>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-xs">
                <span class="text-slate-400 dark:text-slate-500 text-[11px]">Interactive Reader Mode</span>
                <button type="button" @click="isPdfModalOpen = false" class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    Close Viewer
                </button>
            </div>
        </div>
    </div>

    @if((auth()->user()->isTeacher() || auth()->user()->isAdmin()) && $selectedChapter)
    <!-- FACULTY UPLOAD PDF NOTE MODAL -->
    <div x-show="isUploadNoteOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="isUploadNoteOpen = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-5 shadow-2xl relative"
             @click.away="isUploadNoteOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">📤</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Upload Chapter PDF Note</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Add an authentic PDF guide for Chapter {{ $selectedChapter->chapter_number }}</p>
                    </div>
                </div>
                <button type="button" @click="isUploadNoteOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('batches.notes.upload', ['batch' => $batch->id, 'subject' => $subject->id, 'chapter' => $selectedChapter->id]) }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Note Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Fundamental Theorem of Arithmetic & Proofs"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Note Type <span class="text-rose-500">*</span>
                        </label>
                        <select name="note_type" required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="theory">Theory Notes</option>
                            <option value="formula_sheet">Formula Sheet</option>
                            <option value="ncert_solution">NCERT Solution</option>
                            <option value="revision">Quick Revision</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Est. Reading Time
                        </label>
                        <input type="number" name="reading_minutes" min="1" max="120" value="{{ old('reading_minutes') }}" placeholder="15"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Select PDF Document (.pdf) <span class="text-rose-500">*</span>
                    </label>
                    <input type="file" name="pdf_file" accept=".pdf,application/pdf" required
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300 hover:file:bg-emerald-100 transition">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Maximum file size: 20MB. Only PDF format supported.</p>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                    <button type="button" @click="isUploadNoteOpen = false"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-xs flex items-center space-x-1">
                        <span>📤 Upload PDF</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FACULTY ADD VIDEO LECTURE MODAL -->
    <div x-show="isCreateVideoOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="isCreateVideoOpen = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-5 shadow-2xl relative"
             @click.away="isCreateVideoOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">🎬</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Add Video Lecture</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Add an interactive lecture stream for Chapter {{ $selectedChapter->chapter_number }}</p>
                    </div>
                </div>
                <button type="button" @click="isCreateVideoOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('syllabus.topics.videos.store', $selectedChapter->id) }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Lecture Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Fundamental Theorem of Arithmetic &amp; Proofs"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Video Stream URL <span class="text-rose-500">*</span>
                    </label>
                    <input type="url" name="video_url" required placeholder="https://www.youtube.com/watch?v=... or MP4 stream"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-[10px] text-slate-400 mt-1">Supports YouTube, Vimeo, or direct video streams.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Duration (Minutes)
                        </label>
                        <input type="number" name="duration_minutes" min="1" max="300" placeholder="30"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Lecture Notes URL (Optional)
                        </label>
                        <input type="url" name="lecture_notes_url" placeholder="https://... or link to PDF"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Description / Lecture Notes Summary
                    </label>
                    <textarea name="description" rows="2" placeholder="Brief outline of concepts covered..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                    <button type="button" @click="isCreateVideoOpen = false"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-xs flex items-center space-x-1">
                        <span>🎬 Save Video</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FACULTY POST CHAPTER DPP MODAL -->
    <div x-show="isCreateDppOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="isCreateDppOpen = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-5 shadow-2xl relative"
             @click.away="isCreateDppOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">📝</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Create Daily DPP Sheet</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Add an interactive practice set for Chapter {{ $selectedChapter->chapter_number }}</p>
                    </div>
                </div>
                <button type="button" @click="isCreateDppOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('dpp.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="class_id" value="{{ $academicClass->id }}">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="topic_id" value="{{ $selectedChapter->id }}">
                <input type="hidden" name="type" value="dpp">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        DPP Sheet Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. DPP {{ str_pad((string)($dpps->count() + 1), 2, '0', STR_PAD_LEFT) }}: {{ $selectedChapter->title }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Assigned Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="assigned_date" required value="{{ old('assigned_date', date('Y-m-d')) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Rigor Difficulty <span class="text-rose-500">*</span>
                        </label>
                        <select name="difficulty" required
                                class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="standard" selected>Standard Problem Set</option>
                            <option value="advanced">Advanced Analytical</option>
                            <option value="olympiad">National Olympiad Tier</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Duration (Minutes)
                        </label>
                        <input type="number" name="duration_minutes" min="0" max="300" value="{{ old('duration_minutes') }}" placeholder="30"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Total Marks
                        </label>
                        <input type="number" name="total_marks" min="1" max="500" value="{{ old('total_marks') }}" placeholder="20"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/60 text-[11px] text-indigo-800 dark:text-indigo-300 flex items-center justify-between">
                    <span>💡 Full question editor, MCQ options, and CSV import are synced.</span>
                    <a href="{{ route('dpp.index', ['class_id' => $academicClass->id, 'subject_id' => $subject->id]) }}" target="_blank" class="font-bold underline ml-2 shrink-0">
                        Universal Studio &rarr;
                    </a>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                    <button type="button" @click="isCreateDppOpen = false"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-xs flex items-center space-x-1">
                        <span>📝 Create DPP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FACULTY ASSIGN CHAPTER HOMEWORK MODAL -->
    <div x-show="isCreateHwOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="isCreateHwOpen = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-5 shadow-2xl relative"
             @click.away="isCreateHwOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">📋</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Assign Chapter Homework</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Post a real deliverable for Chapter {{ $selectedChapter->chapter_number }}</p>
                    </div>
                </div>
                <button type="button" @click="isCreateHwOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
            </div>

            <form action="{{ route('homework.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                @csrf
                <input type="hidden" name="class_id" value="{{ $academicClass->id }}">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="topic_id" value="{{ $selectedChapter->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Homework Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Chapter {{ $selectedChapter->chapter_number }} Comprehensive Problem Set"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Instructions &amp; Criteria <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="instructions" rows="3" required placeholder="Submission requirements, problems to solve, proof steps..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Assigned Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="assigned_date" required value="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Submission Due Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="due_date" required value="{{ date('Y-m-d', strtotime('+7 days')) }}"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Attach Reference File / Worksheet (Optional PDF, DOC, ZIP)
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 dark:file:bg-amber-950 dark:file:text-amber-300 hover:file:bg-amber-100 transition">
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                    <button type="button" @click="isCreateHwOpen = false"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs transition shadow-xs flex items-center space-x-1">
                        <span>📋 Assign Task</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
