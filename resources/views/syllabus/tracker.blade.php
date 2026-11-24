@extends('layouts.app')

@section('title', 'Whole Syllabus & Chapter Activities Dual Tracker' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div x-data="{ 
    expandAll: false,
    selectedTopic: null,
    toastMessage: '',
    showToast: false,
    
    notify(msg) {
        this.toastMessage = msg;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 3500);
    },

    async toggleActivity(topicId, activityKey, currentState) {
        try {
            const csrfToken = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').getAttribute('content') : '';
            const newState = !currentState;
            const res = await fetch(`/syllabus/topics/${topicId}/toggle-activity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    activity_type: activityKey,
                    organized: newState
                })
            });

            if (res.ok) {
                const data = await res.json();
                this.notify(data.message || 'Activity status updated!');
                setTimeout(() => { window.location.reload(); }, 600);
            } else {
                this.notify('Failed to update activity. Faculty authorization required.');
            }
        } catch (e) {
            console.error(e);
            this.notify('Network error occurred.');
        }
    }
}" class="space-y-4 py-2 sm:py-3 max-w-7xl mx-auto">

    <!-- Toast Notification -->
    <div x-show="showToast" x-cloak 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-20 right-4 z-50 bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-2xl flex items-center space-x-2 border border-slate-700">
        <span class="text-emerald-400 font-black">✓</span>
        <span x-text="toastMessage"></span>
    </div>

    <!-- Top Navigation Header -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xs transition-colors">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Dual-Sided Whole Syllabus Tracker &bull; Dual Activity &amp; Theory Tracker
                    </span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                        ⚡ Concise Chapter Roadmap
                    </span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                    {{ $selectedClass->display_name ?? 'Class' }} — Chapterwise Syllabus &amp; Activities Matrix
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Crisp, concise chapter view with interactive dropdown drawers. Track lecture theory on one side and practical activity executions on the other.
                </p>
            </div>

            <!-- Navigation Mode Switchers -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('syllabus.index', ['grade' => $selectedGrade, 'subject_id' => $selectedSubjectId]) }}" 
                   class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    📖 Syllabus Catalog
                </a>
                <span class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold shadow-xs">
                    📊 Dual Tracker
                </span>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MASTER PROGRESS TRACKER: DUAL THEORY & ACTIVITIES METRICS                -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 pt-5 border-t border-slate-100 dark:border-slate-800">
            
            <!-- 1. THEORY PROGRESS TRACKER -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50/70 via-indigo-50/40 to-white dark:from-blue-950/30 dark:via-indigo-950/20 dark:to-slate-900 border border-blue-200/80 dark:border-blue-900/60 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">📖</span>
                        <span class="text-xs font-black text-blue-900 dark:text-blue-200 uppercase tracking-wider">Theory Progress Tracker</span>
                    </div>
                    <span class="text-sm font-black text-blue-800 dark:text-blue-300 font-mono">{{ $theoryRate }}%</span>
                </div>

                <!-- Theory Progress Bar -->
                <div class="w-full bg-blue-200/70 dark:bg-blue-900/50 h-2.5 rounded-full overflow-hidden p-0.5">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $theoryRate }}%"></div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-400 font-semibold pt-1">
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">✓ {{ $completedTheoryCount }} Covered</span>
                    <span class="text-amber-700 dark:text-amber-400 font-bold">⏳ {{ $inProgressTheoryCount }} In Progress</span>
                    <span class="text-slate-500 font-bold">○ {{ max(0, $totalChapters - $completedTheoryCount - $inProgressTheoryCount) }} Planned</span>
                    <span class="text-slate-700 dark:text-slate-300 font-black font-mono">Total: {{ $totalChapters }} Ch</span>
                </div>
            </div>

            <!-- 2. ACTIVITIES PROGRESS TRACKER -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50/70 via-teal-50/40 to-white dark:from-emerald-950/30 dark:via-teal-950/20 dark:to-slate-900 border border-emerald-200/80 dark:border-emerald-900/60 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">🎯</span>
                        <span class="text-xs font-black text-emerald-900 dark:text-emerald-200 uppercase tracking-wider">Activities Progress Tracker</span>
                    </div>
                    <span class="text-sm font-black text-emerald-800 dark:text-emerald-300 font-mono">{{ $activityRate }}%</span>
                </div>

                <!-- Activities Progress Bar -->
                <div class="w-full bg-emerald-200/70 dark:bg-emerald-900/50 h-2.5 rounded-full overflow-hidden p-0.5">
                    <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 h-full rounded-full transition-all duration-500" style="width: {{ $activityRate }}%"></div>
                </div>

                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 pt-1">
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $totalOrganizedActivities }} / {{ $totalPossibleActivities }} Executed</span>
                    <span class="text-slate-500 font-medium">🧪 {{ $totalLabsOrganized }} Labs &bull; 🏆 {{ $totalCompetitionsOrganized }} Quiz &bull; 🚀 {{ $totalProjectsOrganized }} Proj</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Filters & Search Ribbon -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-3 sm:p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
        <form action="{{ route('syllabus.tracker') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 items-end">
            <!-- 1. Select Class -->
            <div>
                <label for="track-grade" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Class / Grade
                </label>
                <select id="track-grade" name="grade" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    @foreach($allClasses as $c)
                    <option value="{{ $c->grade_level }}" {{ (int)$selectedGrade === (int)$c->grade_level ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Select Subject -->
            <div>
                <label for="track-subject" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Subject
                </label>
                <select id="track-subject" name="subject_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="all" {{ $selectedSubjectId === 'all' ? 'selected' : '' }}>All Subjects ({{ $subjects->count() }})</option>
                    @foreach($subjects as $subj)
                    <option value="{{ $subj->id }}" {{ (string)$selectedSubjectId === (string)$subj->id ? 'selected' : '' }}>
                        {{ $subj->name }} ({{ $subj->topics_count }} ch)
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Activity Filter -->
            <div>
                <label for="track-activity" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Filter Activity
                </label>
                <select id="track-activity" name="activity" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="all" {{ $activityFilter === 'all' ? 'selected' : '' }}>All Chapters &amp; Activities</option>
                    <option value="lab" {{ $activityFilter === 'lab' ? 'selected' : '' }}>🧪 Chapters with Labs</option>
                    <option value="competition" {{ $activityFilter === 'competition' ? 'selected' : '' }}>🏆 Chapters with Quizzes</option>
                    <option value="project" {{ $activityFilter === 'project' ? 'selected' : '' }}>🚀 Chapters with Projects</option>
                    <option value="debate" {{ $activityFilter === 'debate' ? 'selected' : '' }}>🗣️ Chapters with Debates</option>
                    <option value="symposium" {{ $activityFilter === 'symposium' ? 'selected' : '' }}>🔬 Chapters with Symposia</option>
                    <option value="revision" {{ $activityFilter === 'revision' ? 'selected' : '' }}>🔄 Chapters with Revisions</option>
                    <option value="test" {{ $activityFilter === 'test' ? 'selected' : '' }}>📝 Chapters with Tests</option>
                </select>
            </div>

            <!-- 4. Keyword Search -->
            <div>
                <label for="track-search" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Search Chapter Title
                </label>
                <div class="flex items-center space-x-1.5">
                    <input type="text" id="track-search" name="search" value="{{ $search }}" placeholder="Search chapter..."
                           class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 dark:bg-slate-800 text-white text-xs font-bold">
                        Go
                    </button>
                </div>
            </div>
        </form>

        <!-- Controls: Expand/Collapse All Toggle & Quick Filter Chips -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div class="flex items-center space-x-2">
                <button type="button" 
                        @click="expandAll = !expandAll" 
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center space-x-1">
                    <span x-text="expandAll ? '▲ Collapse All Chapters' : '▼ Expand All Chapters'"></span>
                </button>
                <span class="text-[11px] text-slate-400">Showing {{ $topics->count() }} Chapters</span>
            </div>

            <div class="flex items-center space-x-1.5 overflow-x-auto text-[11px]">
                <a href="{{ route('syllabus.tracker', array_merge(request()->query(), ['activity' => 'all'])) }}"
                   class="px-2 py-0.5 rounded-lg font-bold transition {{ $activityFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                   All ({{ $totalChapters }})
                </a>
                <a href="{{ route('syllabus.tracker', array_merge(request()->query(), ['activity' => 'lab'])) }}"
                   class="px-2 py-0.5 rounded-lg font-bold transition {{ $activityFilter === 'lab' ? 'bg-teal-600 text-white' : 'bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 text-teal-800 dark:text-teal-300' }}">
                   🧪 Labs Organized ({{ $totalLabsOrganized }})
                </a>
                <a href="{{ route('syllabus.tracker', array_merge(request()->query(), ['activity' => 'competition'])) }}"
                   class="px-2 py-0.5 rounded-lg font-bold transition {{ $activityFilter === 'competition' ? 'bg-amber-600 text-white' : 'bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300' }}">
                   🏆 Competitions ({{ $totalCompetitionsOrganized }})
                </a>
                <a href="{{ route('syllabus.tracker', array_merge(request()->query(), ['activity' => 'project'])) }}"
                   class="px-2 py-0.5 rounded-lg font-bold transition {{ $activityFilter === 'project' ? 'bg-purple-600 text-white' : 'bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-300' }}">
                   🚀 Projects ({{ $totalProjectsOrganized }})
                </a>
                <a href="{{ route('syllabus.tracker', array_merge(request()->query(), ['activity' => 'debate'])) }}"
                   class="px-2 py-0.5 rounded-lg font-bold transition {{ $activityFilter === 'debate' ? 'bg-rose-600 text-white' : 'bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300' }}">
                   🗣️ Debates ({{ $totalDebatesOrganized }})
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CONCISE & CRISP CHAPTER LIST WITH ACCORDION DROPDOWN                      -->
    <!-- ========================================================================= -->
    <div class="space-y-2">
        @forelse($topics as $topic)
        @php
            $checklist = $topic->normalized_activity_checklist;
            $organizedCount = $topic->organized_activities_count;
            $progressPercent = $topic->activity_progress_percent;
            $isTheoryDone = ($topic->status === 'completed');
            $isTheoryInProgress = ($topic->status === 'in_progress');
        @endphp
        
        <div x-data="{ open: false }" 
             x-init="$watch('expandAll', value => open = value)"
             class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xs transition hover:border-slate-300 dark:hover:border-slate-700">
            
            <!-- CRISP CHAPTER HEADER BAR (ONLY CHAPTER NAME + DUAL TRACKER BADGES) -->
            <button type="button" 
                    @click="open = !open" 
                    class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition gap-3">
                
                <!-- Left: Chapter Number Badge, Name & Subject -->
                <div class="flex items-center space-x-3 min-w-0 flex-1">
                    <span class="px-2.5 py-1 rounded-xl text-xs font-black font-mono flex-shrink-0
                        @if($isTheoryDone && $organizedCount === count($checklist)) bg-emerald-500 text-white
                        @elseif($isTheoryDone || $organizedCount > 0) bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300
                        @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                        Ch {{ $topic->chapter_number }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate">
                                {{ $topic->title }}
                            </h3>
                            <span class="hidden sm:inline text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 flex-shrink-0">
                                {{ $topic->subject->name ?? 'Subject' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Dual Tracker Indicators (Theory Badge + Activities Mini-Bar + Chevron) -->
                <div class="flex items-center space-x-3 flex-shrink-0">
                    
                    <!-- 1. Theory Tracker Status Pill -->
                    <div class="hidden sm:flex items-center">
                        @if($isTheoryDone)
                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-950/80 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            <span>✓</span> <span>Theory Covered</span>
                        </span>
                        @elseif($isTheoryInProgress)
                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            <span>⏳</span> <span>Theory In Progress</span>
                        </span>
                        @else
                        <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            <span>○</span> <span>Theory Planned</span>
                        </span>
                        @endif
                    </div>

                    <!-- 2. Activities Tracker Mini-Bar -->
                    <div class="flex items-center space-x-2">
                        <div class="hidden md:block w-16 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black font-mono
                            @if($organizedCount === count($checklist)) bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                            @elseif($organizedCount > 0) bg-teal-50 text-teal-800 dark:bg-teal-950 dark:text-teal-300
                            @else bg-slate-100 text-slate-500 dark:bg-slate-800 @endif">
                            🎯 {{ $organizedCount }}/{{ count($checklist) }}
                        </span>
                    </div>

                    <!-- Chevron Dropdown Indicator -->
                    <div class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-slate-600">
                        <svg class="w-3.5 h-3.5 transform transition-transform duration-200" 
                             :class="open ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : ''" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

            </button>

            <!-- ========================================================================= -->
            <!-- EXPANDABLE DROPDOWN DRAWER: DUAL-SIDED SYLLABUS & ACTIVITIES DEEP DIVE    -->
            <!-- ========================================================================= -->
            <div x-show="open" x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-slate-800">
                    
                    <!-- LEFT COLUMN: CHAPTER THEORY & CONCEPTS TRACKER -->
                    <div class="p-4 sm:p-5 space-y-3 bg-white/70 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1.5 text-xs text-blue-700 dark:text-blue-300 font-bold uppercase tracking-wider">
                                <span>📖</span>
                                <span>Chapter Theory &amp; Concepts Tracker</span>
                            </div>
                            <a href="{{ route('syllabus.show', $topic->id) }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-0.5">
                                <span>Study Guide &amp; Notes</span>
                                <span>&rarr;</span>
                            </a>
                        </div>

                        <!-- Theory Description & Subtopics (Collapsible Dropdown, Closed First) -->
                        <details class="group text-xs rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/40 dark:bg-blue-950/20 p-3 transition">
                            <summary class="flex items-center justify-between cursor-pointer select-none font-bold text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400">
                                <span class="flex items-center space-x-1.5">
                                    <span>📝</span>
                                    <span>Chapter Overview &amp; Subtopics</span>
                                </span>
                                <div class="flex items-center space-x-1 text-slate-400 text-[11px] font-normal">
                                    <span class="group-open:hidden">View</span>
                                    <span class="hidden group-open:inline">Hide</span>
                                    <svg class="w-3.5 h-3.5 transform transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </summary>
                            
                            <div class="mt-2.5 pt-2 border-t border-blue-200/50 dark:border-blue-900/50 space-y-2">
                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                    {{ $topic->description }}
                                </p>

                                @if(!empty($topic->subtopics))
                                <div class="space-y-1 pt-1">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Syllabus Subtopics:</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($topic->subtopics as $s)
                                        <span class="px-2 py-0.5 rounded-lg bg-blue-100/70 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 text-[11px] font-medium border border-blue-200/60 dark:border-blue-900/40">
                                            &bull; {{ $s }}
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </details>

                        <!-- Supporting Resources Ribbon -->
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500">
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-semibold">
                                📝 {{ $topic->dpps->count() }} Daily DPPs
                            </span>
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-semibold">
                                ✍️ {{ $topic->homeworkTasks->count() }} Homework Tasks
                            </span>
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-semibold">
                                🧠 {{ $topic->bloomActivities->count() }} Bloom Tasks
                            </span>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: CHAPTER ACTIVITIES CHECKLIST TRACKER -->
                    <div class="p-4 sm:p-5 space-y-3 bg-slate-50/80 dark:bg-slate-900/40">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-1.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold uppercase tracking-wider">
                                <span>🎯</span>
                                <span>Chapter Activities Checklist</span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold">
                                Click toggle to update
                            </span>
                        </div>

                        <!-- 7 Interactive Activities Stack -->
                        <div class="space-y-1.5">
                            @foreach($checklist as $key => $act)
                            @php
                                $isOrganized = !empty($act['organized']);
                            @endphp
                            <div class="p-2.5 rounded-xl border transition flex items-center justify-between gap-2.5 
                                {{ $isOrganized ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800/80 text-emerald-950 dark:text-emerald-100' : 'bg-white dark:bg-slate-800/60 border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300' }}">
                                
                                <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                                    <span class="text-base shrink-0">{{ $act['icon'] }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-2">
                                            <strong class="font-bold text-xs truncate">{{ $act['name'] }}</strong>
                                            @if($isOrganized && !empty($act['date']))
                                            <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-400">
                                                📅 {{ $act['date'] }}
                                            </span>
                                            @endif
                                        </div>
                                        <p class="text-[10px] truncate text-slate-500 dark:text-slate-400">
                                            {{ $act['notes'] ?? 'Activity verified for chapter.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Toggle Action Button -->
                                <div class="shrink-0">
                                    @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                                    <button type="button" 
                                            @click="toggleActivity({{ $topic->id }}, '{{ $key }}', {{ $isOrganized ? 'true' : 'false' }})"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black transition flex items-center space-x-1 
                                            {{ $isOrganized ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600' }}">
                                        <span>{{ $isOrganized ? '✓ Organized' : '○ Pending' }}</span>
                                    </button>
                                    @else
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase 
                                        {{ $isOrganized ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                        {{ $isOrganized ? '✓ Organized' : 'Pending' }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>

        </div>
        @empty
        <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 text-slate-400 space-y-2">
            <span class="text-3xl block">📋</span>
            <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No chapters found matching the selected filter criteria.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection
