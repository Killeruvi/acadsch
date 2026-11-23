@extends('layouts.app')

@section('title', $topic->title . ' — ' . $topic->subject->name . ' (' . $topic->subject->academicClass->name . ')')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" 
     x-data="{ 
         activeTab: '{{ request()->query('tab', 'subtopics') }}',
         videoModalOpen: false,
         currentVideoTitle: '',
         currentVideoEmbed: '',
         addVideoModalOpen: false,
         addDppModalOpen: false,
         addHwModalOpen: false,
         progressModalOpen: false,
         editChapterModalOpen: false,
         editVideoModalOpen: false,
         editVideoId: null,
         editVideoTitle: '',
         editVideoUrl: '',
         editVideoDuration: 30,
         editVideoDesc: '',
         editVideoNotes: ''
     }">

    <!-- Breadcrumb & Back Navigation -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <div class="flex items-center space-x-2">
            <a href="{{ route('syllabus.index', ['grade' => $topic->subject->academicClass->grade_level]) }}" class="hover:text-indigo-600 font-medium">
                Syllabus
            </a>
            <span>/</span>
            <span>{{ $topic->subject->academicClass->name }}</span>
            <span>/</span>
            <span class="font-semibold text-slate-700">{{ $topic->subject->name }}</span>
        </div>
        <a href="{{ route('syllabus.index', ['grade' => $topic->subject->academicClass->grade_level, 'subject_id' => $topic->subject->id]) }}" 
           class="inline-flex items-center space-x-1 font-bold text-indigo-600 hover:text-indigo-800">
            <span>&larr; Back to {{ $topic->subject->name }} Syllabus</span>
        </a>
    </div>

    <!-- Chapter Master Header Card (Compact & Modern) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-800 transition-colors">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div class="space-y-2 max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        {{ $topic->subject->academicClass->name }} &bull; {{ $topic->subject->name }} ({{ $topic->subject->code }})
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md text-xs font-extrabold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        Chapter {{ $topic->chapter_number }}
                    </span>
                    @if($topic->status === 'completed')
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                        ✓ Completed
                    </span>
                    @elseif($topic->status === 'in_progress')
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                        ⏳ In Progress
                    </span>
                    @else
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        Not Started
                    </span>
                    @endif
                </div>

                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ $topic->title }}
                </h1>

                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    {{ $topic->description }}
                </p>
            </div>

            <!-- Quick Action Buttons for Teachers / Admins -->
            <div class="flex flex-wrap md:flex-col gap-2 shrink-0">
                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <button type="button" 
                        @click="addVideoModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xs">
                    <span>🎬 + Video</span>
                </button>
                <button type="button" 
                        @click="addDppModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-xs">
                    <span>📝 + DPP</span>
                </button>
                <button type="button" 
                        @click="addHwModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-500 text-white transition shadow-xs">
                    <span>📋 + Homework</span>
                </button>
                <button type="button" 
                        @click="progressModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition">
                    <span>✏️ Status</span>
                </button>
                <button type="button" 
                        @click="editChapterModalOpen = true"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition">
                    <span>⚙️ Edit Chapter</span>
                </button>
                <form action="{{ route('syllabus.topics.destroy', $topic->id) }}" method="POST" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to permanently delete this chapter from the syllabus? All related references will be removed.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition">
                        <span>🗑️ Delete</span>
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Chapter Learning Structural Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Video Lectures</span>
                <span class="text-sm font-black text-indigo-600 dark:text-indigo-400 mt-0.5 block">
                    {{ $chapterMetrics['watched_videos'] }} / {{ $chapterMetrics['total_videos'] }} Watched
                </span>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1.5">
                    <div class="bg-indigo-500 h-1 rounded-full" style="width: {{ $chapterMetrics['video_progress'] }}%"></div>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Practice DPPs</span>
                <span class="text-sm font-black text-amber-600 dark:text-amber-400 mt-0.5 block">
                    {{ $chapterMetrics['total_dpps'] }} Problem Sets
                </span>
                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium block mt-1">
                    {{ $chapterMetrics['attempted_dpps'] }} Attempted
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Homework Tasks</span>
                <span class="text-sm font-black text-teal-600 dark:text-teal-400 mt-0.5 block">
                    {{ $chapterMetrics['total_homework'] }} Assignments
                </span>
                <span class="text-[10px] text-teal-600 dark:text-teal-400 font-medium block mt-1">
                    {{ $chapterMetrics['submitted_homework'] }} Submitted
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Bloom's Prompts</span>
                <span class="text-sm font-black text-rose-600 dark:text-rose-400 mt-0.5 block">
                    {{ $chapterMetrics['bloom_count'] }} L1-L6 Tasks
                </span>
                <span class="text-[10px] text-rose-600 dark:text-rose-400 font-medium block mt-1">
                    Cognitive Progression
                </span>
            </div>
        </div>
    </div>

    <!-- Interactive Navigation Tabs Bar -->
    <div class="bg-white rounded-2xl p-2 border border-slate-200 shadow-sm flex items-center space-x-1 overflow-x-auto scrollbar-thin">
        <button type="button" 
                @click="activeTab = 'subtopics'"
                :class="activeTab === 'subtopics' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs transition shrink-0">
            <span>📖 Subtopics &amp; Outline</span>
        </button>

        <button type="button" 
                @click="activeTab = 'videos'"
                :class="activeTab === 'videos' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs transition shrink-0">
            <span>🎬 Video Lectures</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-500/30 text-white font-extrabold">{{ $topic->videos->count() }}</span>
        </button>

        <button type="button" 
                @click="activeTab = 'dpp'"
                :class="activeTab === 'dpp' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs transition shrink-0">
            <span>📝 Daily Practice Problems (DPP)</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500/30 text-amber-900 font-extrabold">{{ $topic->dpps->count() }}</span>
        </button>

        <button type="button" 
                @click="activeTab = 'homework'"
                :class="activeTab === 'homework' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs transition shrink-0">
            <span>📋 Assignments &amp; Homework</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-500/30 text-emerald-900 font-extrabold">{{ $topic->homeworkTasks->count() }}</span>
        </button>

        <button type="button" 
                @click="activeTab = 'blooms'"
                :class="activeTab === 'blooms' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs transition shrink-0">
            <span>⚡ Bloom's Taxonomy (L1–L6)</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-rose-500/30 text-rose-900 font-extrabold">6 Levels</span>
        </button>
    </div>

    <!-- TAB 1: Subtopics & Conceptual Breakdown -->
    <div x-show="activeTab === 'subtopics'" x-cloak class="space-y-6">
        @if(!empty($topic->subtopics))
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h2 class="font-bold text-slate-900 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Core Subtopics &amp; Conceptual Modules</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach((array)$topic->subtopics as $idx => $sub)
                <div class="flex items-start space-x-3 p-3.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-200 transition">
                    <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0 mt-0.5">
                        {{ $idx + 1 }}
                    </span>
                    <div>
                        <span class="font-semibold text-xs text-slate-800">{{ $sub }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Study Notes & Resource Links -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <h2 class="font-bold text-slate-900 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <span>Curriculum Notes &amp; External References</span>
            </h2>

            @if($topic->study_notes || $topic->resource_url)
            <div class="space-y-3 text-xs text-slate-700">
                @if($topic->study_notes)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="font-bold text-slate-800 block mb-1">📝 Instructor Study Notes:</span>
                    <p class="leading-relaxed">{{ $topic->study_notes }}</p>
                </div>
                @endif

                @if($topic->resource_url)
                <div class="flex items-center space-x-2">
                    <span class="text-slate-500 font-medium">Official Reference Link:</span>
                    <a href="{{ $topic->resource_url }}" target="_blank" rel="noopener noreferrer" 
                       class="font-bold text-indigo-600 hover:text-indigo-800 underline inline-flex items-center gap-1">
                        <span>{{ $topic->resource_url }}</span>
                        <span>↗</span>
                    </a>
                </div>
                @endif
            </div>
            @else
            <p class="text-xs text-slate-500">No additional notes attached yet. Use "Update Status" to add notes and resources.</p>
            @endif
        </div>
    </div>

    <!-- TAB 2: Video Lectures (🎬) -->
    <div x-show="activeTab === 'videos'" x-cloak class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Chapter Video Lectures</h2>
                <p class="text-xs text-slate-500">High-definition lectures covering theoretical principles and problem sets.</p>
            </div>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button type="button" 
                    @click="addVideoModalOpen = true"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition">
                + Add Lecture
            </button>
            @endif
        </div>

        @if($topic->videos->isEmpty())
        <div class="p-8 rounded-2xl bg-white border border-dashed border-slate-200 text-center">
            <span class="text-3xl block mb-2">🎬</span>
            <p class="text-xs text-slate-500">No video lectures have been uploaded for this chapter yet.</p>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($topic->videos as $v)
            @php $watched = $v->isWatchedBy(Auth::id()); @endphp
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:border-indigo-300 transition flex flex-col justify-between">
                <div>
                    <!-- Video Thumbnail with Play Trigger -->
                    <div class="relative bg-slate-900 aspect-video group cursor-pointer"
                         @click="currentVideoTitle = '{{ addslashes($v->title) }}'; currentVideoEmbed = '{{ $v->embed_url }}'; videoModalOpen = true">
                        @if($v->thumbnail_url)
                        <img src="{{ $v->thumbnail_url }}" alt="{{ $v->title }}" class="w-full h-full object-cover group-hover:opacity-90 transition">
                        @else
                        <div class="w-full h-full bg-slate-800 flex items-center justify-center text-slate-600 font-bold text-xs">Video Lecture</div>
                        @endif
                        <div class="absolute inset-0 bg-black/30 group-hover:bg-black/10 transition flex items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition">
                                <svg class="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                        <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-black/70 text-white text-[10px] font-bold">
                            {{ $v->duration_formatted }}
                        </span>
                    </div>

                    <!-- Video Details -->
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-extrabold text-indigo-600 uppercase tracking-wider">Lecture #{{ $v->order_index }}</span>
                            @if($watched)
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                ✓ Watched
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                Unwatched
                            </span>
                            @endif
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm leading-snug">
                            {{ $v->title }}
                        </h3>
                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                            {{ $v->description }}
                        </p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="p-4 pt-0 border-t border-slate-100 mt-2 flex items-center justify-between text-xs">
                    <form action="{{ route('syllabus.videos.toggle-watched', $v->id) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $watched ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                            {{ $watched ? 'Mark as Unwatched' : '✓ Mark as Watched' }}
                        </button>
                    </form>

                    <div class="flex items-center space-x-2">
                        @if($v->lecture_notes_url)
                        <a href="{{ $v->lecture_notes_url }}" target="_blank" rel="noopener noreferrer" 
                           class="text-indigo-600 hover:text-indigo-800 font-bold underline text-xs">
                            Notes PDF ↗
                        </a>
                        @endif
                        <a href="{{ route('videos.show', $v->id) }}" class="text-slate-500 hover:text-slate-800 font-semibold text-xs">
                            Full View &rarr;
                        </a>
                        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <button type="button" 
                                @click="editVideoId = {{ $v->id }}; editVideoTitle = '{{ addslashes($v->title) }}'; editVideoUrl = '{{ addslashes($v->video_url) }}'; editVideoDuration = {{ round($v->duration_seconds / 60) }}; editVideoDesc = '{{ addslashes($v->description ?? '') }}'; editVideoNotes = '{{ addslashes($v->lecture_notes_url ?? '') }}'; editVideoModalOpen = true"
                                class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" 
                                title="Edit Video">
                            ✏️
                        </button>
                        <form action="{{ route('syllabus.videos.destroy', $v->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove this video lecture from the syllabus?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition" title="Delete Video">
                                🗑️
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- TAB 3: Daily Practice Problems (DPP) (📝) -->
    <div x-show="activeTab === 'dpp'" x-cloak class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Chapter DPP Practice Sets</h2>
                <p class="text-xs text-slate-500">Timed, objective multiple-choice problem sets with automated grading.</p>
            </div>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button type="button" 
                    @click="addDppModalOpen = true"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-500 transition">
                + Create DPP Set
            </button>
            @endif
        </div>

        @if($topic->dpps->isEmpty())
        <div class="p-8 rounded-2xl bg-white border border-dashed border-slate-200 text-center">
            <span class="text-3xl block mb-2">📝</span>
            <p class="text-xs text-slate-500">No DPPs created for this chapter yet.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($topic->dpps as $dpp)
            @php $sub = $dpp->submissions->first(); @endphp
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs hover:border-amber-300 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5 max-w-xl">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300">
                            {{ $dpp->dpp_number }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">
                            {{ $dpp->difficulty }}
                        </span>
                        @if($dpp->is_timed)
                        <span class="text-[11px] text-slate-500 font-medium">⏱ {{ $dpp->duration_minutes }} Mins</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-slate-900 text-base">
                        {{ $dpp->title }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        {{ $dpp->questions->count() }} Multiple Choice Questions &bull; {{ $dpp->total_marks }} Total Marks &bull; Pass Threshold: {{ $dpp->pass_percentage }}%
                    </p>
                </div>

                <div class="flex items-center space-x-3 shrink-0">
                    @if($sub)
                    <div class="text-right">
                        <span class="text-xs font-extrabold text-emerald-600 block">Score: {{ $sub->score }} / {{ $sub->max_score }}</span>
                        <span class="text-[10px] text-slate-500 font-semibold block">{{ round($sub->percentage, 1) }}% ({{ $sub->is_passed ? 'PASSED' : 'RETRY' }})</span>
                    </div>
                    <a href="{{ route('dpp.show', $dpp->id) }}" 
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 transition">
                        View Solutions
                    </a>
                    @else
                    <a href="{{ route('dpp.show', $dpp->id) }}" 
                       class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-sm">
                        Start DPP Test &rarr;
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- TAB 4: Assignments & Homework (📋) -->
    <div x-show="activeTab === 'homework'" x-cloak class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Chapter Assignments &amp; Homework Tasks</h2>
                <p class="text-xs text-slate-500">Descriptive synthesis, problem solving, and handwritten submissions.</p>
            </div>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button type="button" 
                    @click="addHwModalOpen = true"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-600 text-white hover:bg-teal-500 transition">
                + Assign Homework
            </button>
            @endif
        </div>

        @if($topic->homeworkTasks->isEmpty())
        <div class="p-8 rounded-2xl bg-white border border-dashed border-slate-200 text-center">
            <span class="text-3xl block mb-2">📋</span>
            <p class="text-xs text-slate-500">No homework tasks assigned for this chapter yet.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($topic->homeworkTasks as $hw)
            @php $hwSub = $hw->submissions->first(); @endphp
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs hover:border-teal-300 transition space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                            <span class="font-bold text-teal-700">Assigned: {{ $hw->assigned_date->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span class="font-bold text-rose-600">Due: {{ $hw->due_date->format('M d, Y') }}</span>
                        </div>
                        <h3 class="font-bold text-slate-900 text-base">
                            {{ $hw->title }}
                        </h3>
                    </div>

                    <div>
                        @if($hwSub)
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ✓ Submitted ({{ ucfirst($hwSub->status) }})
                        </span>
                        @else
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                            Pending Submission
                        </span>
                        @endif
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                    {{ $hw->instructions }}
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-[11px] text-slate-500">Teacher: {{ $hw->teacher->name ?? 'Course Instructor' }}</span>
                    <a href="{{ route('homework.index') }}" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-500 text-white transition shadow-sm">
                        {{ $hwSub ? 'View Submission in Homework Hub' : 'Submit Solution &rarr;' }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- TAB 5: Bloom's Taxonomy Cognitive Hierarchy (⚡) -->
    <div x-show="activeTab === 'blooms'" x-cloak class="space-y-4">
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Bloom's Taxonomy Cognitive Progression (L1 to L6)</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Automated pedagogical scaffolding mapped from simple Recall to creative Synthesis.</p>
                </div>
                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <a href="{{ route('blooms.index') }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition">
                    Launch Randomized Delivery &rarr;
                </a>
                @endif
            </div>

            <div class="space-y-3">
                @foreach($topic->bloomActivities as $act)
                @php $meta = \App\Models\TopicBloomActivity::getLevelMetadata($act->bloom_level); @endphp
                <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition bg-slate-50/50">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="px-2.5 py-1 rounded-md font-extrabold text-[11px] {{ $meta['badge_color'] }}">
                            Level {{ $act->bloom_level }}: {{ $act->level_name }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-semibold">
                            Keywords: {{ $act->verb_keywords }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-800 leading-relaxed font-medium">
                        {{ $act->activity_prompt }}
                    </div>
                    @if($act->rubric_guide)
                    <div class="mt-2 pt-2 border-t border-slate-200/60 text-[11px] text-slate-500">
                        <span class="font-semibold text-slate-700">Evaluation Rubric:</span> {{ $act->rubric_guide }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- MODAL 1: Play Video Modal -->
    <div x-show="videoModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="videoModalOpen = false; currentVideoEmbed = ''"></div>
        <div class="relative bg-slate-900 text-white rounded-3xl max-w-3xl w-full p-4 sm:p-6 shadow-2xl border border-slate-800 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <h3 class="text-sm sm:text-base font-bold truncate max-w-xl" x-text="currentVideoTitle"></h3>
                <button type="button" @click="videoModalOpen = false; currentVideoEmbed = ''" class="p-1 rounded-lg text-slate-400 hover:text-white">
                    ✕
                </button>
            </div>
            <div class="aspect-video w-full rounded-2xl overflow-hidden bg-black shadow-inner">
                <iframe x-show="currentVideoEmbed" :src="currentVideoEmbed" class="w-full h-full" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Add Video Modal (Teacher/Admin) -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="addVideoModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addVideoModalOpen = false"></div>
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Add Video Lecture to Chapter</h3>
                <button type="button" @click="addVideoModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('syllabus.topics.videos.store', $topic->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lecture Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Lecture 03: Experimental Derivations" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">YouTube / Video URL *</label>
                    <input type="url" name="video_url" required placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" value="30" min="1" max="300" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Lecture Notes Link (PDF)</label>
                        <input type="url" name="lecture_notes_url" placeholder="https://..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lecture Description</label>
                    <textarea name="description" rows="2" placeholder="Key topics covered in this video..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="addVideoModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white">Save Video</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Add DPP Modal -->
    <div x-show="addDppModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addDppModalOpen = false"></div>
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Create Chapter DPP Set</h3>
                <button type="button" @click="addDppModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('syllabus.topics.dpps.store', $topic->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">DPP Title *</label>
                    <input type="text" name="title" required value="DPP: {{ $topic->title }} Practice Problems" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Difficulty</label>
                        <select name="difficulty" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                            <option value="standard">Standard</option>
                            <option value="advanced">Advanced</option>
                            <option value="olympiad">Olympiad</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Timer (Minutes)</label>
                        <input type="number" name="duration_minutes" value="30" min="5" max="180" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pass %</label>
                        <input type="number" name="pass_percentage" value="60" min="30" max="100" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Negative Marks</label>
                        <input type="number" step="0.25" name="negative_marks" value="1.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="addDppModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white">Create DPP</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Add Homework Modal -->
    <div x-show="addHwModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="addHwModalOpen = false"></div>
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Assign Homework Task</h3>
                <button type="button" @click="addHwModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('syllabus.topics.homework.store', $topic->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Assignment Title *</label>
                    <input type="text" name="title" required value="Homework Task: {{ $topic->title }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Submission Deadline (Days from today)</label>
                    <input type="number" name="due_days" value="5" min="1" max="30" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Questions / Guidelines *</label>
                    <textarea name="instructions" rows="4" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Specify numerical problems, essay questions, and submission requirements..."></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="addHwModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-500 text-white">Assign Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Chapter Status Progress Modal -->
    <div x-show="progressModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="progressModalOpen = false"></div>
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Update Chapter Academic Progress</h3>
                <button type="button" @click="progressModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('syllabus.topics.progress', $topic->id) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Completion Status *</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500">
                        <option value="not_started" {{ $topic->status === 'not_started' ? 'selected' : '' }}>⚪ Not Started</option>
                        <option value="in_progress" {{ $topic->status === 'in_progress' ? 'selected' : '' }}>⏳ In Progress</option>
                        <option value="completed" {{ $topic->status === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Teacher Study Notes</label>
                    <textarea name="study_notes" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Notes for students...">{{ $topic->study_notes }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Official Reference URL</label>
                    <input type="url" name="resource_url" value="{{ $topic->resource_url }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-indigo-500" placeholder="https://...">
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="progressModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white">Save Progress</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT CHAPTER MODAL -->
    <div x-show="editChapterModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="editChapterModalOpen = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-6 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Chapter Details</h3>
                <button type="button" @click="editChapterModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
            </div>

            <form action="{{ route('syllabus.topics.update', $topic->id) }}" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Chapter # *</label>
                        <input type="number" name="chapter_number" min="1" required value="{{ old('chapter_number', $topic->chapter_number) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500">
                            <option value="not_started" {{ $topic->status === 'not_started' ? 'selected' : '' }}>⚪ Not Started</option>
                            <option value="in_progress" {{ $topic->status === 'in_progress' ? 'selected' : '' }}>⏳ In Progress</option>
                            <option value="completed" {{ $topic->status === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Chapter Title *</label>
                    <input type="text" name="title" required value="{{ old('title', $topic->title) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Overview</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">{{ old('description', $topic->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subtopics (Separated by semicolon or newline)</label>
                    <textarea name="subtopics" rows="2" placeholder="Topic 1; Topic 2; Topic 3" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">{{ is_array($topic->subtopics) ? implode('; ', $topic->subtopics) : '' }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Official Reference URL</label>
                    <input type="url" name="resource_url" value="{{ old('resource_url', $topic->resource_url) }}" placeholder="https://..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Faculty Study Notes</label>
                    <textarea name="study_notes" rows="2" placeholder="Important pointers for students..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">{{ old('study_notes', $topic->study_notes) }}</textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editChapterModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition">Update Chapter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT VIDEO MODAL -->
    <div x-show="editVideoModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="editVideoModalOpen = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-6 z-10 space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Video Lecture</h3>
                <button type="button" @click="editVideoModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">✕</button>
            </div>

            <form :action="'{{ url('/syllabus/videos') }}/' + editVideoId" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lecture Title *</label>
                    <input type="text" name="title" x-model="editVideoTitle" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Video Embed / Watch URL *</label>
                        <input type="url" name="video_url" x-model="editVideoUrl" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Duration (Mins)</label>
                        <input type="number" name="duration_minutes" x-model="editVideoDuration" min="1" max="300" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" x-model="editVideoDesc" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lecture Notes / Slide Deck PDF URL</label>
                    <input type="url" name="lecture_notes_url" x-model="editVideoNotes" placeholder="https://..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editVideoModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition">Update Video</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
