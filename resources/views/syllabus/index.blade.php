@extends('layouts.app')

@section('title', 'Syllabus Explorer' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="space-y-2.5 sm:space-y-4" x-data="{ createTopicModalOpen: false, importModalOpen: false, progressModalOpen: false, progressTopicId: null, progressTopicTitle: '', progressTopicStatus: 'not_started', progressTopicNotes: '', progressTopicUrl: '' }">

    <!-- COMPACT HEADER (NO BIG BLOATED BANNER) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs transition-colors">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        Curriculum Directory
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 Aim, Ascend, Achieve
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                    Chapterwise Academic Syllabus &amp; Curriculum
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Select class and subject from the form below to browse chapters, study guides, and Bloom's learning milestones.
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('syllabus.tracker', ['grade' => $selectedGrade, 'subject_id' => $selectedSubjectId]) }}" 
                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition shadow-2xs">
                    <span>📋</span>
                    <span>Dual Activities Tracker</span>
                </a>

                <a href="{{ route('syllabus.sample-csv') }}" 
                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Sample CSV</span>
                </a>

                @if($selectedClass)
                <a href="{{ route('syllabus.export-csv', $selectedClass->id) }}" 
                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export {{ $selectedClass->display_name }} CSV</span>
                </a>
                @endif

                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <button type="button" 
                        @click.stop="createTopicModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                    <span>+ Add Chapter</span>
                </button>
                <button type="button" 
                        @click.stop="importModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <span>Upload CSV</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- SIMPLE LEAST-COMPLICATED FORM: SELECT CLASS & SELECT SUBJECT (Form-Based Selection) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form action="{{ route('syllabus.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-end">
            <!-- 1. Select Class -->
            <div>
                <label for="syllabus-grade" class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <span>🎓</span>
                    <span>Step 1: Select Class</span>
                </label>
                <select id="syllabus-grade" name="grade" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    @foreach($allClasses as $c)
                    <option value="{{ $c->grade_level }}" {{ (int)$selectedGrade === (int)$c->grade_level ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Select Subject -->
            <div>
                <label for="syllabus-subject" class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <span>📖</span>
                    <span>Step 2: Select Subject in {{ $selectedClass->display_name ?? 'Class' }}</span>
                </label>
                <select id="syllabus-subject" name="subject_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="all" {{ $selectedSubjectId === 'all' ? 'selected' : '' }}>All Subjects in {{ $selectedClass->display_name ?? 'Class' }} ({{ $subjects->count() }})</option>
                    @foreach($subjects as $subj)
                    <option value="{{ $subj->id }}" {{ (string)$selectedSubjectId === (string)$subj->id ? 'selected' : '' }}>
                        {{ $subj->name }} ({{ $subj->topics_count ?? 0 }} {{ Str::plural('chapter', (int)($subj->topics_count ?? 0)) }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Search / Filter Chapters -->
            <div>
                <label for="syllabus-search" class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <span>🔍</span>
                    <span>Filter by Keyword</span>
                </label>
                <div class="flex items-center space-x-1.5">
                    <input type="text" 
                           id="syllabus-search"
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search chapters..." 
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <button type="submit" class="px-3.5 py-2 bg-slate-900 dark:bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-xs flex-shrink-0 flex items-center space-x-1">
                        <span>Filter</span>
                        <span>⚡</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- CRISPIEST SYLLABUS TOOLBAR: NAMES ONLY WITH EXPANDABLE DROPDOWNS -->
    <div class="flex items-center justify-between text-xs py-1 px-1">
        <div class="flex items-center space-x-2">
            <span class="font-black text-slate-800 dark:text-slate-100 uppercase text-[11px] tracking-wider">
                Chapters ({{ $topics->total() ?? $topics->count() }})
            </span>
            <span class="text-slate-400 text-[11px]">&bull;</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px]">
                {{ $syllabusProgressRate }}% Covered
            </span>
        </div>
        <div class="flex items-center space-x-2 text-[11px]">
            <button type="button" 
                    onclick="document.querySelectorAll('details.syllabus-dropdown').forEach(d => d.open = true)"
                    class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                Expand All
            </button>
            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
            <button type="button" 
                    onclick="document.querySelectorAll('details.syllabus-dropdown').forEach(d => d.open = false)"
                    class="font-semibold text-slate-500 hover:underline">
                Collapse All
            </button>
        </div>
    </div>

    <!-- STEP 3: CRISPIEST CHAPTERS LIST (JUST ONLY NAMES WITH DROPDOWN) -->
    @if($topics->isEmpty())
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center border border-slate-200 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No chapters found</h3>
        <p class="text-xs text-slate-500 mt-1">Please select another subject or reset your search query.</p>
    </div>
    @else
    <div class="space-y-2.5">
        @foreach($topics as $topic)
        <details class="syllabus-dropdown group rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden transition-all duration-150 hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs">
            <!-- SUMMARY: CRISPIEST FORMAT - JUST ONLY NAMES WITH DROPDOWN ARROW -->
            <summary class="flex items-center justify-between px-3.5 py-2.5 sm:py-3 cursor-pointer select-none transition hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                <div class="flex items-center space-x-2.5 min-w-0 flex-1 pr-2">
                    <span class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400 shrink-0">
                        {{ $topic->chapter_number }}.
                    </span>
                    <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                        {{ $topic->title }}
                    </span>
                    @if($topic->status === 'completed')
                    <span class="text-[10px] text-emerald-600 font-bold shrink-0" title="Completed">✓</span>
                    @elseif($topic->status === 'in_progress')
                    <span class="text-[10px] text-amber-500 font-bold shrink-0" title="In Progress">⏳</span>
                    @endif
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                    @if($selectedSubjectId === 'all')
                    <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium hidden sm:inline-block">
                        {{ $topic->subject->name }}
                    </span>
                    @endif
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </summary>

            <!-- DROPDOWN CONTENT (SUBTOPICS & ACTION ON DEMAND) -->
            <div class="px-3.5 pb-3 pt-1 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/60 dark:bg-slate-950/40 space-y-2 text-xs">
                @if(!empty($topic->subtopics) && is_array($topic->subtopics))
                <div class="pt-1 flex flex-wrap gap-1.5">
                    @foreach($topic->subtopics as $st)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-[11px]">
                        &bull; {{ $st }}
                    </span>
                    @endforeach
                </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-slate-200/60 dark:border-slate-800/60 text-[11px]">
                    <div class="flex items-center space-x-3 text-slate-500 dark:text-slate-400 text-[11px]">
                        <span>🎬 {{ $topic->videos_count ?? 0 }} {{ Str::plural('lecture', (int)($topic->videos_count ?? 0)) }}</span>
                        <span>&bull;</span>
                        <span>📝 {{ $topic->dpps_count ?? 0 }} {{ Str::plural('DPP', (int)($topic->dpps_count ?? 0)) }}</span>
                        <span>&bull;</span>
                        <span>📋 {{ $topic->homework_tasks_count ?? 0 }} {{ Str::plural('task', (int)($topic->homework_tasks_count ?? 0)) }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <button type="button"
                                @click="progressTopicId = {{ $topic->id }}; progressTopicTitle = '{{ addslashes($topic->title) }}'; progressTopicStatus = '{{ $topic->status }}'; progressTopicNotes = '{{ addslashes($topic->study_notes ?? '') }}'; progressTopicUrl = '{{ addslashes($topic->resource_url ?? '') }}'; progressModalOpen = true"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold transition">
                            ✏️ Status
                        </button>
                        @endif

                        <a href="{{ route('syllabus.show', $topic->id) }}" 
                           class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition flex items-center space-x-1">
                            <span>Explore Chapter &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </details>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $topics->links() }}
    </div>
    @endif

    <!-- Modal: Upload CSV Syllabus (Faculty & Admin Only) -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="importModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="importModalOpen = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 z-10 animate-in fade-in duration-150">
            <form method="POST" action="{{ route('syllabus.import-csv') }}" enctype="multipart/form-data">
                @csrf
                <div class="bg-slate-900 dark:bg-slate-950 px-6 py-5 text-white flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black tracking-widest uppercase text-emerald-400">Curriculum CSV Importer</span>
                        <h3 class="text-base font-extrabold text-white">Import Chapterwise Syllabus</h3>
                    </div>
                    <button type="button" @click="importModalOpen = false" class="text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Target Subject *</label>
                        <select name="subject_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-white">
                            @foreach($subjects as $subj)
                            <option value="{{ $subj->id }}" {{ (int)$selectedSubjectId === (int)$subj->id ? 'selected' : '' }}>
                                {{ $subj->name }} ({{ $selectedClass->display_name ?? '' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Select CSV File *</label>
                        <input type="file" 
                               name="csv_file" 
                               accept=".csv,text/csv" 
                               required 
                               class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" 
                            @click="importModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                        Upload &amp; Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Update Chapter Syllabus Progress -->
    <div x-show="progressModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="progressModalOpen = false"></div>

        <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 z-10 animate-in fade-in duration-150">
            <form method="POST" :action="'/syllabus/topics/' + progressTopicId + '/progress'">
                @csrf
                @method('PATCH')
                
                <div class="bg-slate-900 dark:bg-slate-950 px-6 py-5 text-white flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-black tracking-widest uppercase text-emerald-400">Academic Pacing &amp; Coverage</span>
                        <h3 class="text-base font-extrabold text-white truncate max-w-sm" x-text="progressTopicTitle">Update Topic Progress</h3>
                    </div>
                    <button type="button" @click="progressModalOpen = false" class="text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Coverage Status *</label>
                        <select name="status" x-model="progressTopicStatus" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs font-semibold text-slate-800 dark:text-white">
                            <option value="not_started">⚪ Not Started</option>
                            <option value="in_progress">⏳ In Progress (Currently Teaching)</option>
                            <option value="completed">✅ Completed (Full Topic Covered)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Lecture / Study Notes Summary</label>
                        <textarea name="study_notes" x-model="progressTopicNotes" rows="3" placeholder="Key concepts covered..."
                                  class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Resource / Notes URL (Optional)</label>
                        <input type="url" name="resource_url" x-model="progressTopicUrl" placeholder="https://..."
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="progressModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-800 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm transition">
                        Update Progress
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE CHAPTER MODAL -->
    <div x-show="createTopicModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden animate-in fade-in duration-200"
             @click.away="createTopicModalOpen = false">
            <div class="bg-indigo-600 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">📖</span>
                    <h3 class="text-base font-extrabold text-white">Add New Syllabus Chapter</h3>
                </div>
                <button type="button" @click="createTopicModalOpen = false" class="text-indigo-200 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('syllabus.topics.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Target Subject *</label>
                    <select name="subject_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs font-semibold text-slate-800 dark:text-white">
                        @foreach($subjects as $subj)
                        <option value="{{ $subj->id }}" {{ (string)$selectedSubjectId === (string)$subj->id ? 'selected' : '' }}>
                            {{ $subj->name }} ({{ $selectedClass->display_name ?? 'Class' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Chapter # *</label>
                        <input type="number" name="chapter_number" min="1" required value="{{ ($topics->count() ?? 0) + 1 }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs font-bold text-slate-800 dark:text-white">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Chapter Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Chemical Reactions and Equations" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Overview of learning outcomes..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Subtopics (Semicolon separated)</label>
                    <textarea name="subtopics" rows="2" placeholder="Balancing Equations; Types of Reactions; Oxidation" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Reference URL (Optional)</label>
                    <input type="url" name="resource_url" placeholder="https://..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-indigo-500 text-xs text-slate-800 dark:text-white">
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end space-x-3">
                    <button type="button" @click="createTopicModalOpen = false" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-800 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-sm transition">
                        Create Chapter
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
