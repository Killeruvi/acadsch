@extends('layouts.app')

@section('title', $course->code . ' - ' . $course->title)

@section('content')
<script id="lesson-pdfs-data" type="application/json">@json($course->lessons->pluck('pdf_url', 'id'))</script>
<div x-data="{ 
    activeTab: '{{ request('tab', 'curriculum') }}', 
    videoView: 'list',
    expandedLessonId: null,
    expandedModuleIds: [],
    expandedWeeks: {},
    toggleModule(id) {
        if (this.expandedModuleIds.includes(id)) {
            this.expandedModuleIds = this.expandedModuleIds.filter(i => i !== id);
        } else {
            this.expandedModuleIds.push(id);
        }
    },
    isModuleExpanded(id) {
        return this.expandedModuleIds.includes(id);
    },
    expandAllModules() {
        this.expandedModuleIds = [{{ $course->modules->pluck('id')->implode(',') }}];
    },
    collapseAllModules() {
        this.expandedModuleIds = [];
        this.expandedWeeks = {};
    },
    toggleWeek(modId, weekNum) {
        const key = modId + '_' + weekNum;
        this.expandedWeeks[key] = !this.expandedWeeks[key];
    },
    isWeekExpanded(modId, weekNum) {
        return !!this.expandedWeeks[modId + '_' + weekNum];
    },
    playVideo(url, title) {
        let embed = url;
        if (url.includes('youtube.com/watch?v=')) {
            let m = url.match(/v=([a-zA-Z0-9_-]+)/);
            if (m) embed = 'https://www.youtube-nocookie.com/embed/' + m[1] + '?rel=0&modestbranding=1&autoplay=1';
        } else if (url.includes('youtu.be/')) {
            let m = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
            if (m) embed = 'https://www.youtube-nocookie.com/embed/' + m[1] + '?rel=0&modestbranding=1&autoplay=1';
        }
        this.activeVideoUrl = embed;
        this.activeVideoTitle = title;
        this.openVideoModal = true;
    },
    isUploadLessonPdfOpen: false,
    uploadLessonId: {{ $course->lessons->first()?->id ?? 0 }},
    openScheduleModal: false, 
    openAssignmentModal: false, 
    openVideoModal: false,
    openAddVideoModal: false,
    openEditVideoModal: false,
    editVideoId: null,
    editVideoLessonId: null,
    editVideoTitle: '',
    editVideoUrl: '',
    editVideoDuration: 30,
    editVideoDesc: '',
    openAddDppModal: false,
    openCsvImportModal: false,
    openAddModuleModal: false,
    openAddLessonModal: false,
    defaultLessonModuleId: '{{ $course->modules->first()?->id ?? '' }}',
    openEditModuleModal: false,
    editModuleId: null,
    editModuleTitle: '',
    editModuleOrder: 1,
    editModuleTheme: '',
    editModuleDescription: '',
    openEditLessonModal: false,
    editLessonId: null,
    editLessonModuleId: null,
    editLessonTitle: '',
    editLessonNumber: 1,
    editLessonDuration: 45,
    editLessonVideoUrl: '',
    editLessonPdfUrl: '',
    editLessonSummary: '',
    openEditCourseModal: false,
    editCourseCode: '{{ addslashes($course->code) }}',
    editCourseTitle: '{{ addslashes($course->title) }}',
    editCourseCategory: '{{ addslashes($course->category) }}',
    editCourseCredits: {{ $course->credits }},
    editCourseCapacity: {{ $course->capacity }},
    editCourseTeacherId: '{{ $course->teacher_id }}',
    editCourseStatus: '{{ $course->status }}',
    editCourseDescription: '{{ addslashes($course->description ?? '') }}',
    openScheduleEditModal: false,
    editScheduleId: null,
    editScheduleTitle: '',
    editScheduleDay: 'Monday',
    editScheduleStart: '09:00',
    editScheduleEnd: '10:00',
    editScheduleMode: 'offline',
    editScheduleRoom: '',
    editScheduleDesc: '',
    openAddAssessmentModal: false,
    openEditAssessmentModal: false,
    editAssessmentId: null,
    editAssessmentTitle: '',
    editAssessmentType: 'quiz',
    editAssessmentWeek: 1,
    editAssessmentModuleId: '{{ $course->modules->first()?->id ?? '' }}',
    editAssessmentDesc: '',
    editAssessmentPoints: 100,
    editAssessmentPass: 60,
    editAssessmentTime: 45,
    selectedLessonForVideo: null,
    videoModalTitle: '',
    videoModalUrl: '',
    videoModalDuration: '',
    activeVideoUrl: '',
    activeVideoTitle: '',
    selectedNoteLessonId: {{ $course->lessons->first()?->id ?? 0 }},
    lessonPdfs: JSON.parse(document.getElementById('lesson-pdfs-data')?.textContent || '{}'),
    get currentPdfUrl() {
        return this.lessonPdfs[this.selectedNoteLessonId] || '';
    }
}" class="space-y-4">

    <!-- Course Hero Panel -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs p-3.5 sm:p-4 relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-md text-xs font-black bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 tracking-wide">
                        {{ $course->code }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $course->category }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ $course->credits }} Academic Credits
                    </span>
                    @if($course->modules->isNotEmpty())
                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                        <span>📚</span> <span>{{ $course->modules->count() }} Modules &bull; {{ $course->lessons->count() }} Lessons</span>
                    </span>
                    @endif
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ $course->title }}
                </h1>

                @if($course->description)
                <div class="text-xs text-slate-600 dark:text-slate-300 leading-normal max-w-3xl">
                    {{ $course->description }}
                </div>
                @endif
            </div>

            <!-- Enrollment / Action CTAs -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
                @auth
                    @if(!empty($isEnrolled))
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <span class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg text-xs font-bold flex items-center justify-center space-x-1 shadow-xs">
                                <span>✓</span> <span>Enrolled</span>
                            </span>
                        </div>
                    @else
                        @if(Auth::user()->isStudent())
                            @if($course->enrollments_count < $course->capacity)
                            <form method="POST" action="{{ route('courses.enroll', $course->id) }}" data-no-spa="false">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition active:scale-95 flex items-center justify-center space-x-1.5">
                                    <span>🎓 Enroll in this Course</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </form>
                            @else
                            <span class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-lg text-xs font-semibold">Course Full</span>
                            @endif
                        @endif
                    @endif

                    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <div class="flex items-center flex-wrap gap-1.5">
                            <button @click.stop="openEditCourseModal = true" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1" title="Edit Course Details">
                                <span>✏️</span> <span>Edit</span>
                            </button>
                            <form method="POST" action="{{ route('courses.destroy', $course->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete course \'{{ addslashes($course->title) }}\' and all its curriculum modules, lessons, and data?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-lg text-xs font-bold border border-rose-200 dark:border-rose-800 transition flex items-center space-x-1" title="Delete Course">
                                    <span>🗑️</span>
                                </button>
                            </form>
                            <button @click.stop="openAddVideoModal = true; selectedLessonForVideo = null; videoModalTitle = ''; videoModalUrl = ''; videoModalDuration = ''" class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1">
                                <span>🎥</span> <span>+ Video</span>
                            </button>
                            <button @click.stop="openAddDppModal = true" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1">
                                <span>⚡</span> <span>+ DPP</span>
                            </button>
                            <button @click.stop="openScheduleModal = true" class="px-2.5 py-1.5 bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1">
                                <span>🗓️</span> <span>+ Schedule</span>
                            </button>
                            <button @click.stop="openAssignmentModal = true" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center space-x-1">
                                <span>📝</span> <span>+ Assign</span>
                            </button>
                        </div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition text-center">
                        Sign In to Enroll
                    </a>
                @endauth
            </div>
        </div>

        <!-- Instructor & Stats Bar -->
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="flex items-center space-x-3 col-span-2 sm:col-span-1">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold text-sm">
                    {{ substr($course->teacher?->name ?? 'T', 0, 1) }}
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 block">Instructor</span>
                    <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $course->teacher?->name ?? 'Faculty Panel' }}</span>
                </div>
            </div>

            <div>
                <span class="text-slate-400 dark:text-slate-500 block">Enrolled Scholars</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $course->enrollments_count }} / {{ $course->capacity }} Seats</span>
            </div>

            <div>
                <span class="text-slate-400 dark:text-slate-500 block">Class Schedule</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $course->schedules->count() }} Weekly Sessions</span>
            </div>

            <div>
                <span class="text-slate-400 dark:text-slate-500 block">Course Status</span>
                <span class="inline-block mt-0.5 px-2 py-0.5 rounded-lg font-bold uppercase text-[10px] bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {{ $course->status }}
                </span>
            </div>
        </div>
    </div>

    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <!-- Faculty Curriculum Management & Bulk Feeding Bar -->
    <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="space-y-0.5">
            <div class="flex items-center space-x-2">
                <span class="text-sm">🛠️</span>
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">Curriculum Structure &amp; Content Management</h3>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Direct manual setup or bulk CSV feeding. No auto-generated placeholder filler.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('courses.curriculum.template-csv', $course->id) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center space-x-1 shadow-2xs" title="Download CSV Template for Bulk Content Feeding">
                <span>📥</span>
                <span>CSV Template</span>
            </a>
            <button type="button" @click="openCsvImportModal = true" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center space-x-1 shadow-xs">
                <span>📤</span>
                <span>Bulk Import CSV</span>
            </button>
            <button type="button" @click="openAddModuleModal = true" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition flex items-center space-x-1 shadow-xs">
                <span>+ Module</span>
            </button>
            <button type="button" @click="defaultLessonModuleId = '{{ $course->modules->first()?->id ?? '' }}'; openAddLessonModal = true" class="px-3.5 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs transition flex items-center space-x-1 shadow-xs">
                <span>+ Lesson</span>
            </button>
        </div>
    </div>
    @endif

    <!-- COURSE MASTER TABBED NAVIGATION -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-1.5 shadow-xs overflow-x-auto">
        <nav class="flex items-center space-x-1 min-w-max text-xs font-bold" aria-label="Course Sections">
            
            <!-- Tab 1: Modules & Lessons (Curriculum Dropdown) -->
            <button type="button" @click="activeTab = 'curriculum'"
                    :class="activeTab === 'curriculum' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>📑</span>
                <span>Modules &amp; Lessons</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'curriculum' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->modules->count() }}
                </span>
            </button>

            <!-- Tab 2: Timetable & Schedule -->
            <button type="button" @click="activeTab = 'schedule'"
                    :class="activeTab === 'schedule' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>🗓️</span>
                <span>Schedule &amp; Timetable</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'schedule' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->schedules->count() }}
                </span>
            </button>

            <!-- Tab 3: Video Lectures -->
            <button type="button" @click="activeTab = 'videos'"
                    :class="activeTab === 'videos' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>🎥</span>
                <span>Video Lectures</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'videos' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->lessons->count() }}
                </span>
            </button>

            <!-- Tab 4: DPP Daily Practice -->
            <button type="button" @click="activeTab = 'dpp'"
                    :class="activeTab === 'dpp' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>📝</span>
                <span>DPP (Daily Practice)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'dpp' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->dpps->count() }}
                </span>
            </button>

            <!-- Tab 5: Homework & Assignments -->
            <button type="button" @click="activeTab = 'homework'"
                    :class="activeTab === 'homework' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>💼</span>
                <span>Homework &amp; Tasks</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'homework' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->assignments->count() }}
                </span>
            </button>

            <!-- Tab 6: Tests & Assessments -->
            <button type="button" @click="activeTab = 'tests'"
                    :class="activeTab === 'tests' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <span>🏆</span>
                <span>Tests &amp; Quizzes</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'tests' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                    {{ $course->assessments->count() }}
                </span>
            </button>

            <!-- Tab 7: Notes & Study Material (HIGHLIGHTED PROMINENTLY) -->
            <button type="button" @click="activeTab = 'notes'"
                    :class="activeTab === 'notes' ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md shadow-teal-600/20' : 'text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/60 hover:bg-teal-100 dark:hover:bg-teal-900/60'"
                    class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2 border border-teal-300 dark:border-teal-700">
                <span>📖</span>
                <span>Lecture Notes (PDF)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeTab === 'notes' ? 'bg-white/20 text-white' : 'bg-teal-200 dark:bg-teal-800 text-teal-800 dark:text-teal-200'">
                    {{ $course->lessons->count() }}
                </span>
            </button>

        </nav>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: CURRICULUM MODULES & LESSONS (DROPDOWN FORMAT) -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'curriculum'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                    <span>📑</span>
                    <span>Curriculum Modules &amp; Lessons</span>
                </h3>
                <p class="text-xs text-slate-500">
                    Browse modules in drop-down format to view lessons, attached video lectures, notes, and Chapter Deadlines.
                </p>
            </div>
            <div class="flex items-center flex-wrap gap-2 text-xs">
                <!-- Expand / Collapse All -->
                <button type="button" @click="expandAllModules()" class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition flex items-center space-x-1">
                    <span>⬇️</span>
                    <span>Expand All</span>
                </button>
                <button type="button" @click="collapseAllModules()" class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition flex items-center space-x-1">
                    <span>⬆️</span>
                    <span>Collapse All</span>
                </button>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" @click="openAddModuleModal = true" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold shadow-xs transition flex items-center space-x-1">
                    <span>+ Module</span>
                </button>
                <button type="button" @click="openCsvImportModal = true" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-xs transition flex items-center space-x-1">
                    <span>📤 Bulk CSV</span>
                </button>
                @endif
            </div>
        </div>

        @if($course->modules->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">🎯</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Curriculum Modules Configured</p>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Once course modules and lessons are loaded, their target completion dates, lessons, and video masterclasses will be tracked here.
            </p>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <div class="mt-4 flex items-center justify-center space-x-2">
                <button type="button" @click="openAddModuleModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center space-x-1">
                    <span>➕ Add First Module</span>
                </button>
                <button type="button" @click="openCsvImportModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center space-x-1">
                    <span>📤 Bulk Import CSV</span>
                </button>
            </div>
            @endif
        </div>
        @else
        <div class="space-y-3">
            @foreach($course->modules as $mod)
            @php
                $modAssessment = $course->assessments->firstWhere('week_number', $mod->week_number) ?? $course->assessments->first();
                $targetDays = max(7, ($mod->order_index ?? $loop->iteration) * 7);
                $targetDeadline = now()->addDays($targetDays);
                $modLessons = $mod->lessons;
                $totalModVideos = $modLessons->sum(fn($l) => $l->allVideos()->count());
            @endphp
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition">
                <!-- Module Header (Click to Toggle Dropdown) -->
                <div class="p-3.5 sm:p-4 cursor-pointer hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition flex items-center justify-between gap-3 select-none"
                     @click="toggleModule({{ $mod->id }})">
                    
                    <!-- Clean Minimal Listing: Only Module Number and Name -->
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-black text-xs shrink-0">
                            M{{ $mod->order_index ?? $loop->iteration }}
                        </span>
                        <h4 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white truncate">
                            {{ $mod->title }}
                        </h4>
                    </div>

                    <!-- Right Side: Action Buttons & Toggle Chevron -->
                    <div class="flex items-center space-x-2 shrink-0">
                        @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                        <button type="button" 
                                @click.stop="defaultLessonModuleId = '{{ $mod->id }}'; openAddLessonModal = true"
                                class="px-2.5 py-1.5 rounded-xl bg-teal-50 dark:bg-teal-950/60 hover:bg-teal-100 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 text-xs font-bold transition flex items-center space-x-1" title="Add Lesson to this Module">
                            <span>+ Lesson</span>
                        </button>
                        <button type="button" 
                                @click.stop="editModuleId = {{ $mod->id }}; editModuleTitle = '{{ addslashes($mod->title) }}'; editModuleOrder = {{ $mod->order_index ?? 1 }}; editModuleTheme = '{{ addslashes($mod->theme ?? '') }}'; editModuleDescription = '{{ addslashes($mod->description ?? '') }}'; openEditModuleModal = true"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition" title="Edit Module">
                            <span>✏️</span>
                        </button>
                        <form method="POST" action="{{ route('courses.modules.destroy', [$course->id, $mod->id]) }}" class="inline" data-confirm="CAUTION: Are you sure you want to delete this module and all its lessons?" onsubmit="return confirm('CAUTION: Are you sure you want to delete this module and all its lessons?');" @click.stop>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold transition" title="Delete Module">
                                <span>🗑️</span>
                            </button>
                        </form>
                        @endif

                        <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 transition-transform duration-200"
                             :class="{ 'rotate-180 text-emerald-600 dark:text-emerald-400': isModuleExpanded({{ $mod->id }}) }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Expanded Container (Only shown when module is expanded) -->
                <div x-show="isModuleExpanded({{ $mod->id }})" x-cloak class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-950/40 p-4 space-y-4">
                    
                    <!-- Module Details Shown When Expanded: Target deadlines, description, and milestone badges -->
                    <div class="p-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                    Target: {{ $targetDeadline->format('M d, Y') }}
                                </span>
                                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                                    ⏱️ Within {{ $targetDays }} days
                                </span>
                            </div>

                            <!-- Milestones & Counts Badges -->
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-lg font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    <span>🎬</span>
                                    <span>{{ $modLessons->count() }} Lessons</span>
                                </span>

                                <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-lg font-bold 
                                    @if($totalModVideos > 0) bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800
                                    @else bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 @endif">
                                    <span>🎥</span>
                                    <span>{{ $totalModVideos ?? 0 }} {{ Str::plural('Video', (int)($totalModVideos ?? 0)) }}</span>
                                </span>

                                <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-lg font-bold
                                    @if($course->dpps->isNotEmpty()) bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800
                                    @else bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700 @endif">
                                    <span>📝</span>
                                    <span>DPP Active</span>
                                </span>

                                @if($modAssessment)
                                <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-lg font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border border-purple-300 dark:border-purple-800">
                                    <span>🏆</span>
                                    <span>{{ $modAssessment->total_points }} Pts Test</span>
                                </span>
                                @endif
                            </div>
                        </div>

                        @if($mod->description)
                        <p class="text-xs text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/80">{{ $mod->description }}</p>
                        @endif
                    </div>

                    <!-- Lessons List Encapsulated by Week -->
                    @if($modLessons->isEmpty())
                    <div class="p-6 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                        <p class="text-xs font-bold">No lessons added to this module yet.</p>
                        @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                        <button type="button" @click="defaultLessonModuleId = '{{ $mod->id }}'; openAddLessonModal = true" class="mt-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold inline-flex items-center space-x-1">
                            <span>➕ Add First Lesson</span>
                        </button>
                        @endif
                    </div>
                    @else
                    @php
                        $lessonsByWeek = $modLessons->groupBy(function($l) {
                            return $l->week_number ?: 1;
                        })->sortKeys();
                    @endphp
                    <div class="space-y-3">
                        @foreach($lessonsByWeek as $weekNum => $weekLessons)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
                            <!-- Week Header: Only Week and Count, Expands to Show Lessons -->
                            <div class="p-3 sm:p-3.5 bg-slate-50/80 dark:bg-slate-800/50 hover:bg-slate-100/70 dark:hover:bg-slate-800 transition cursor-pointer flex items-center justify-between select-none"
                                 @click="toggleWeek({{ $mod->id }}, {{ $weekNum }})">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-xs flex items-center justify-center">
                                        W{{ $weekNum }}
                                    </span>
                                    <h5 class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">
                                        Week {{ $weekNum }}
                                    </h5>
                                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                        ({{ $weekLessons->count() }} {{ Str::plural('Lesson', (int)$weekLessons->count()) }})
                                    </span>
                                </div>
                                <div class="w-6 h-6 rounded-lg bg-slate-200/60 dark:bg-slate-700/60 flex items-center justify-center text-slate-500 transition-transform duration-200"
                                     :class="{ 'rotate-180 text-indigo-600 dark:text-indigo-400': isWeekExpanded({{ $mod->id }}, {{ $weekNum }}) }">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>

                            <!-- Week Content (Lessons for this week) -->
                            <div x-show="isWeekExpanded({{ $mod->id }}, {{ $weekNum }})" x-cloak class="p-3 sm:p-4 space-y-2.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-950/20">
                                @foreach($weekLessons as $l)
                        @php
                            $lessonVideos = $l->allVideos();
                            $isWatched = Auth::check() && \App\Models\CourseLessonProgress::where('user_id', Auth::id())->where('lesson_id', $l->id)->where('is_completed', true)->exists();
                        @endphp
                        <div class="p-3.5 sm:p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-2.5 hover:border-slate-300 dark:hover:border-slate-700 transition">
                            
                            <!-- Lesson Title Row -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                        L{{ $l->lesson_number }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                                            Lesson {{ $l->lesson_number }}: {{ $l->title }}
                                        </h5>
                                        <div class="flex items-center space-x-2 text-[10px] text-slate-400 mt-0.5">
                                            <span>⏱️ {{ $l->duration_minutes ?? 45 }} mins</span>
                                            <span>&bull;</span>
                                            <span class="font-bold text-indigo-600 dark:text-indigo-400">🎥 {{ $lessonVideos->count() }} {{ Str::plural('Video', (int)$lessonVideos->count()) }}</span>
                                            @if($l->pdf_url)
                                            <span>&bull;</span>
                                            <span class="inline-flex items-center space-x-1">
                                                <a href="{{ $l->pdf_url }}" target="_blank" class="text-teal-600 dark:text-teal-400 hover:underline font-bold" title="Mobile Enhanced Bulletin Notes (390x780)">📄 PDF Note</a>
                                                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                                                <form method="POST" action="{{ route('courses.lessons.destroy-note', [$course->id, $l->id]) }}" class="inline" data-confirm="Remove PDF note from this lesson?" onsubmit="return confirm('Remove PDF note from this lesson?');" @click.stop>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-slate-400 hover:text-rose-500 text-[10px] ml-0.5" title="Remove PDF Note">&times;</button>
                                                </form>
                                                @endif
                                            </span>
                                            @endif
                                            @if($l->homework_task)
                                            <span>&bull;</span>
                                            <a href="{{ $l->homework_task }}" target="_blank" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold inline-flex items-center space-x-0.5" title="Printable A4 Self-Testing Worksheet">
                                                <span>🖨️</span><span>DPP Sheet</span>
                                            </a>
                                            @endif
                                            @if($l->dpp)
                                            <span>&bull;</span>
                                            <a href="{{ route('dpp.show', $l->dpp->id) }}" class="text-amber-600 dark:text-amber-400 hover:underline font-bold">📝 {{ $l->dpp->title }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Lesson Action Buttons -->
                                <div class="flex items-center flex-wrap gap-1.5 shrink-0">
                                    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                                    <!-- Add Video Button -->
                                    <button type="button" 
                                            @click="selectedLessonForVideo = {{ $l->id }}; videoModalTitle = 'Lesson {{ $l->lesson_number }} Video'; videoModalUrl = ''; videoModalDuration = '30'; openAddVideoModal = true"
                                            class="px-2 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[11px] font-bold transition flex items-center space-x-1"
                                            title="Add a video to this lesson">
                                        <span>➕ Video</span>
                                    </button>
                                    <!-- Upload PDF Button -->
                                    <button type="button"
                                            @click="uploadLessonId = {{ $l->id }}; isUploadLessonPdfOpen = true"
                                            class="px-2 py-1 rounded-lg bg-teal-50 dark:bg-teal-950/60 hover:bg-teal-100 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 text-[11px] font-bold transition flex items-center space-x-1"
                                            title="Upload PDF note for this lesson">
                                        <span>📤 Note</span>
                                    </button>
                                    <!-- Add DPP Button -->
                                    <button type="button"
                                            @click="openAddDppModal = true"
                                            class="px-2 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-[11px] font-bold transition flex items-center space-x-1"
                                            title="Add practice questions / DPP set">
                                        <span>⚡ DPP</span>
                                    </button>
                                    <!-- Edit Lesson Button -->
                                    <button type="button" 
                                            @click="editLessonId = {{ $l->id }}; editLessonModuleId = {{ $l->module_id ?? 'null' }}; editLessonTitle = '{{ addslashes($l->title) }}'; editLessonNumber = {{ $l->lesson_number }}; editLessonDuration = {{ $l->duration_minutes ?? 45 }}; editLessonVideoUrl = '{{ addslashes($l->video_url ?? '') }}'; editLessonPdfUrl = '{{ addslashes($l->pdf_url ?? '') }}'; editLessonSummary = '{{ addslashes($l->topic_summary ?? '') }}'; openEditLessonModal = true"
                                            class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-[11px] font-semibold transition">
                                        <span>✏️</span>
                                    </button>
                                    <!-- Delete Lesson -->
                                    <form method="POST" action="{{ route('courses.lessons.destroy', [$course->id, $l->id]) }}" class="inline" data-confirm="Delete this lesson?" onsubmit="return confirm('Delete this lesson?');" @click.stop>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-[11px] font-semibold transition">
                                            <span>🗑️</span>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            @if($l->topic_summary)
                            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/80">
                                {{ $l->topic_summary }}
                            </p>
                            @endif

                            <!-- MULTI-VIDEO LIST FOR THIS LESSON (1 OR MORE VIDEOS) -->
                            <div class="pt-1 border-t border-slate-100 dark:border-slate-800/60">
                                @if($lessonVideos->isNotEmpty())
                                <div class="space-y-1.5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                                        Lesson Video Lectures ({{ $lessonVideos->count() }}):
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($lessonVideos as $vIdx => $v)
                                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs group">
                                            <button type="button" 
                                                    @click="playVideo('{{ $v->video_url }}', '{{ addslashes($v->title) }}')"
                                                    class="flex items-center space-x-2 text-left min-w-0 flex-1 hover:text-emerald-600 dark:hover:text-emerald-400 font-bold transition">
                                                <span class="w-5 h-5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 flex items-center justify-center text-[10px] shrink-0">
                                                    ▶️
                                                </span>
                                                <span class="truncate">{{ $v->title }}</span>
                                                <span class="text-[10px] font-normal text-slate-400 shrink-0">({{ $v->duration_minutes ?? 30 }}m)</span>
                                            </button>
                                            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                                            <div class="flex items-center space-x-1 ml-2 shrink-0">
                                                <button type="button" 
                                                        @click="editVideoId = {{ $v->id ?? 0 }}; editVideoLessonId = {{ $l->id }}; editVideoTitle = '{{ addslashes($v->title) }}'; editVideoUrl = '{{ addslashes($v->video_url) }}'; editVideoDuration = {{ $v->duration_minutes ?? 30 }}; editVideoDesc = '{{ addslashes($v->description ?? '') }}'; openEditVideoModal = true"
                                                        class="text-slate-400 hover:text-indigo-600 transition text-[11px]" title="Edit video details">
                                                    ✏️
                                                </button>
                                                <form method="POST" action="{{ route('courses.videos.destroy', [$course->id, $v->id ?? 0]) }}" class="inline" data-confirm="Remove this video?" onsubmit="return confirm('Remove this video?');" @click.stop>
                                                    @csrf
                                                    @method('DELETE')
                                                    @if(($v->id ?? 0) === 0)
                                                    <input type="hidden" name="lesson_id" value="{{ $l->id }}">
                                                    @endif
                                                    <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Delete video">
                                                        &times;
                                                    </button>
                                                </form>
                                            </div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @else
                                <div class="flex items-center justify-between text-xs text-slate-400 py-0.5">
                                    <span class="italic text-[11px]">No video lectures attached yet.</span>
                                    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                                    <button type="button" 
                                            @click="selectedLessonForVideo = {{ $l->id }}; videoModalTitle = 'Lesson {{ $l->lesson_number }} Video'; videoModalUrl = ''; videoModalDuration = '30'; openAddVideoModal = true"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-[11px] flex items-center space-x-1">
                                        <span>➕ Attach First Video</span>
                                    </button>
                                    @endif
                                </div>
                                @endif
                            </div>

                        </div>
                        @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: SCHEDULE & TIMETABLE -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'schedule'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Weekly Class Schedule &amp; Timetable</h3>
                <p class="text-xs text-slate-500">Live lecture slots, physical classrooms, and offline lab sessions</p>
            </div>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button @click.stop="openScheduleModal = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                <span>+ Add Session</span>
            </button>
            @endif
        </div>

        @if($course->schedules->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">🗓️</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Timetable Sessions Scheduled</p>
            <p class="text-xs text-slate-500 mt-1">Use "+ Add Session" above to define weekly recurring class slots.</p>
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach($course->schedules as $sch)
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between {{ $sch->is_cancelled ? 'opacity-65' : '' }}">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                            @if(($sch->delivery_mode ?? 'offline') === 'offline') bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300
                            @else bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 @endif">
                            {{ ucfirst($sch->delivery_mode ?? 'offline') }} Classroom
                        </span>
                        <div class="flex items-center space-x-1">
                            @if($sch->is_cancelled)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">Cancelled</span>
                            @endif
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $sch->day_of_week }}
                            </span>
                        </div>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 dark:text-white {{ $sch->is_cancelled ? 'line-through text-slate-400' : '' }}">{{ $sch->title }}</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        @if($sch->is_cancelled && $sch->cancellation_reason)
                        <span class="text-rose-600 dark:text-rose-400 font-semibold">Reason: {{ $sch->cancellation_reason }}</span>
                        @else
                        {{ $sch->description ?? 'Weekly scheduled instructional class.' }}
                        @endif
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1">
                            <span>⏰</span> <span>{{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }}</span>
                        </span>
                        @if($sch->room)
                        <span class="text-[11px] text-slate-500 flex items-center space-x-1">
                            <span>📍</span> <span>{{ $sch->room }}</span>
                        </span>
                        @endif
                    </div>

                    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                    <div class="flex items-center justify-end space-x-1 pt-1 border-t border-slate-100 dark:border-slate-800/60">
                        <form method="POST" action="{{ route('schedules.toggle-cancel', $sch->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold {{ $sch->is_cancelled ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }} transition">
                                {{ $sch->is_cancelled ? 'Restore' : 'Cancel' }}
                            </button>
                        </form>
                        <button type="button" 
                                @click="editScheduleId = {{ $sch->id }}; editScheduleTitle = '{{ addslashes($sch->title) }}'; editScheduleDay = '{{ $sch->day_of_week }}'; editScheduleStart = '{{ substr($sch->start_time, 0, 5) }}'; editScheduleEnd = '{{ substr($sch->end_time, 0, 5) }}'; editScheduleMode = '{{ $sch->delivery_mode ?? 'offline' }}'; editScheduleRoom = '{{ addslashes($sch->room ?? '') }}'; editScheduleDesc = '{{ addslashes($sch->description ?? '') }}'; openScheduleEditModal = true"
                                class="px-2 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 transition">
                            ✏️ Edit
                        </button>
                        <form method="POST" action="{{ route('schedules.destroy', $sch->id) }}" class="inline" onsubmit="return confirm('Delete this schedule session?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition" title="Delete Session">
                                🗑️
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: VIDEO LECTURES -->
    <!-- ========================================== -->
    @php
        $courseLessons = $course->lessons;
        $totalCourseLessons = $courseLessons->count();
        $completedCourseLessons = Auth::check() 
            ? \App\Models\CourseLessonProgress::where('user_id', Auth::id())->whereIn('lesson_id', $courseLessons->pluck('id'))->where('is_completed', true)->count() 
            : 0;
        $courseOverallProgress = $totalCourseLessons > 0 ? round(($completedCourseLessons / $totalCourseLessons) * 100) : 0;
    @endphp
    <div x-show="activeTab === 'videos'" class="space-y-4">
        
        <!-- Lecture Header & View Switcher -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Course Lecture Directory</h3>
                <p class="text-xs text-slate-500">Access structured on-demand video masterclasses and lecture sessions</p>
            </div>
            
            <div class="flex items-center flex-wrap gap-2">
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" 
                        @click="openAddVideoModal = true; selectedLessonForVideo = null; videoModalTitle = ''; videoModalUrl = ''; videoModalDuration = ''"
                        class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                    <span>➕ Add Video Lecture</span>
                </button>
                @endif
                <span class="text-xs font-semibold text-slate-500">{{ $totalCourseLessons }} Lectures</span>
                <!-- View Switcher -->
                <div class="flex items-center space-x-1 p-0.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs">
                    <button type="button" 
                            @click="videoView = 'list'"
                            :class="videoView === 'list' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 font-medium'"
                            class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1">
                        <span>☰</span>
                        <span>Normal List View</span>
                    </button>
                    <button type="button" 
                            @click="videoView = 'grid'"
                            :class="videoView === 'grid' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 font-medium'"
                            class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1">
                        <span>⊞</span>
                        <span>Grid View</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- DUAL PROGRESS BARS: OVERALL & PER-MODULE PROGRESS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- 1. Overall Course Progress -->
            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                    <span class="text-slate-700 dark:text-slate-300 flex items-center space-x-1.5">
                        <span>🎓</span> <span>Overall Course Lecture Progress</span>
                    </span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-extrabold">{{ $completedCourseLessons }}/{{ $totalCourseLessons }} ({{ $courseOverallProgress }}%)</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500" style="width: {{ $courseOverallProgress }}%"></div>
                </div>
            </div>

            <!-- 2. Per-Module Breakdown -->
            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                    <span class="text-slate-700 dark:text-slate-300 flex items-center space-x-1.5">
                        <span>📑</span> <span>Curriculum Modules Breakdown</span>
                    </span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-extrabold">{{ $course->modules->count() }} Modules</span>
                </div>
                <div class="flex items-center space-x-1.5">
                    @foreach($course->modules as $m)
                    @php
                        $modLessons = $courseLessons->where('module_id', $m->id);
                        $modTotal = $modLessons->count();
                        $modDone = Auth::check() 
                            ? \App\Models\CourseLessonProgress::where('user_id', Auth::id())->whereIn('lesson_id', $modLessons->pluck('id'))->where('is_completed', true)->count() 
                            : 0;
                        $modPct = $modTotal > 0 ? round(($modDone / $modTotal) * 100) : 0;
                    @endphp
                    <div class="flex-1 text-center" title="{{ $m->title }}: {{ $modDone }}/{{ $modTotal }}">
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mb-0.5">
                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $modPct }}%"></div>
                        </div>
                        <span class="text-[9px] font-mono text-slate-400">W{{ $m->week_number }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if($courseLessons->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">🎥</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Video Lectures Loaded</p>
            <p class="text-xs text-slate-500 mt-1">Video lectures will appear here once lessons are published.</p>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button type="button" @click="openAddVideoModal = true; selectedLessonForVideo = null; videoModalTitle = ''; videoModalUrl = ''; videoModalDuration = ''" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs inline-flex items-center space-x-1.5">
                <span>➕ Add First Video Lecture</span>
            </button>
            @endif
        </div>
        @else

        <!-- 1. NORMAL LIST VIEW (CLEAN LECTURE 1, LECTURE 2 TITLES WITH ACCORDION DROPDOWN) -->
        <div x-show="videoView === 'list'" class="space-y-2">
            @foreach($courseLessons as $idx => $l)
            @php
                $lVideos = $l->allVideos();
                $isDone = Auth::check() && \App\Models\CourseLessonProgress::where('user_id', Auth::id())->where('lesson_id', $l->id)->where('is_completed', true)->exists();
            @endphp
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-2xs transition">
                <div class="p-3.5 sm:p-4 flex items-center justify-between cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/60 transition"
                     @click="expandedLessonId = (expandedLessonId === {{ $l->id }} ? null : {{ $l->id }})">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-mono font-black text-xs flex items-center justify-center shrink-0">
                            L{{ $l->lesson_number }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">
                                Lecture {{ $l->lesson_number }}: {{ $l->title }}
                            </h4>
                            <div class="flex items-center space-x-2 text-[10px] text-slate-400 mt-0.5">
                                <span>Week {{ $l->week_number }}</span>
                                <span>&bull;</span>
                                <span>⏱️ {{ $l->duration_minutes ?? 60 }} mins</span>
                                <span>&bull;</span>
                                <span class="capitalize">{{ $l->level }} Level</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2.5 shrink-0 pl-2">
                        @if($isDone)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                            ✓ Watched
                        </span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-500">
                            Unwatched
                        </span>
                        @endif

                        <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 transition">
                            <svg class="w-4 h-4 transform transition-transform duration-200"
                                 :class="{ 'rotate-180': expandedLessonId === {{ $l->id }} }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Dropdown Details Panel -->
                <div x-show="expandedLessonId === {{ $l->id }}" x-cloak class="px-4 pb-4 pt-1 bg-slate-50/50 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed pt-2">
                        {{ $l->topic_summary ?? 'Comprehensive lecture covering core theoretical principles and practical application.' }}
                    </p>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200/60 dark:border-slate-800">
                        <div class="flex items-center space-x-2 text-xs text-slate-500">
                            <button type="button" @click="activeTab = 'notes'; selectedNoteLessonId = {{ $l->id }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold">
                                📖 Lecture Handout &rarr;
                            </button>
                        </div>
                        <div class="flex items-center flex-wrap gap-2">
                            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                            <button type="button" 
                                    @click="editLessonId = {{ $l->id }}; editLessonModuleId = {{ $l->module_id ?? 'null' }}; editLessonTitle = '{{ addslashes($l->title) }}'; editLessonNumber = {{ $l->lesson_number }}; editLessonDuration = {{ $l->duration_minutes ?? 45 }}; editLessonVideoUrl = '{{ addslashes($l->video_url ?? '') }}'; editLessonPdfUrl = '{{ addslashes($l->pdf_url ?? '') }}'; editLessonSummary = '{{ addslashes($l->topic_summary ?? '') }}'; openEditLessonModal = true"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition flex items-center space-x-1" title="Edit Lesson">
                                <span>✏️ Edit Lesson</span>
                            </button>
                            <form method="POST" action="{{ route('courses.lessons.destroy', [$course->id, $l->id]) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete this lecture?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition flex items-center space-x-1" title="Delete Lesson">
                                    <span>🗑️</span>
                                </button>
                            </form>
                            <button type="button" 
                                    @click="openAddVideoModal = true; selectedLessonForVideo = {{ $l->id }}; videoModalTitle = 'Lesson {{ $l->lesson_number }} Video'; videoModalUrl = ''; videoModalDuration = '30'"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center space-x-1">
                                <span>🎥</span>
                                <span>➕ Add Video</span>
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- Multi-Video Playlist Strip for this Lesson in List View -->
                    @if($lVideos->isNotEmpty())
                    <div class="space-y-1.5 pt-2 border-t border-slate-200/60 dark:border-slate-800">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Attached Video Lectures ({{ $lVideos->count() }}):</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($lVideos as $v)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-xs group">
                                <button type="button" 
                                        @click="playVideo('{{ $v->video_url }}', '{{ addslashes($v->title) }}')"
                                        class="flex items-center space-x-2 text-left min-w-0 flex-1 hover:text-emerald-600 dark:hover:text-emerald-400 font-bold transition">
                                    <span class="w-5 h-5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 flex items-center justify-center text-[10px] shrink-0">
                                        ▶️
                                    </span>
                                    <span class="truncate">{{ $v->title }}</span>
                                    <span class="text-[10px] font-normal text-slate-400 shrink-0">({{ $v->duration_minutes ?? 30 }}m)</span>
                                </button>
                                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                                <div class="flex items-center space-x-1 ml-1 shrink-0">
                                    <button type="button" 
                                            @click="editVideoId = {{ $v->id ?? 0 }}; editVideoLessonId = {{ $l->id }}; editVideoTitle = '{{ addslashes($v->title) }}'; editVideoUrl = '{{ addslashes($v->video_url) }}'; editVideoDuration = {{ $v->duration_minutes ?? 30 }}; editVideoDesc = '{{ addslashes($v->description ?? '') }}'; openEditVideoModal = true"
                                            class="text-slate-400 hover:text-indigo-600 transition text-[11px]" title="Edit video details">
                                        ✏️
                                    </button>
                                    <form method="POST" action="{{ route('courses.videos.destroy', [$course->id, $v->id ?? 0]) }}" class="inline" onsubmit="return confirm('Remove video?');">
                                        @csrf
                                        @method('DELETE')
                                        @if(($v->id ?? 0) === 0)
                                        <input type="hidden" name="lesson_id" value="{{ $l->id }}">
                                        @endif
                                        <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Delete video">&times;</button>
                                    </form>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="pt-1 text-xs text-slate-400 italic">No video lectures attached yet.</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- 2. COMPACT GRID VIEW -->
        <div x-show="videoView === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach($courseLessons as $l)
            @php
                $isDone = Auth::check() && \App\Models\CourseLessonProgress::where('user_id', Auth::id())->where('lesson_id', $l->id)->where('is_completed', true)->exists();
            @endphp
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex flex-col justify-between hover:border-emerald-500/50 transition">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-mono font-black uppercase px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                            Lecture {{ $l->lesson_number }}
                        </span>
                        @if($isDone)
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">✓ Watched</span>
                        @else
                        <span class="text-[10px] text-slate-400">⏱️ {{ $l->duration_minutes }}m</span>
                        @endif
                    </div>
                    <h4 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white line-clamp-1">
                        {{ $l->title }}
                    </h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                        {{ $l->topic_summary }}
                    </p>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" @click="activeTab = 'notes'; selectedNoteLessonId = {{ $l->id }}" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">
                        Notes &rarr;
                    </button>
                    @php $gVideos = $l->allVideos(); @endphp
                    @if($gVideos->isNotEmpty())
                    <button type="button" 
                            @click="playVideo('{{ $gVideos->first()->video_url }}', '{{ addslashes($gVideos->first()->title) }}')"
                            class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs flex items-center space-x-1">
                        <span>▶️ Watch ({{ $gVideos->count() }})</span>
                    </button>
                    @else
                    <span class="text-[10px] text-slate-400">Pending</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: DPP (DAILY PRACTICE PROBLEMS) -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'dpp'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Daily Practice Problems (DPP)</h3>
                <p class="text-xs text-slate-500">Timed daily challenge sets with full question explanations</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-400 font-semibold">{{ $course->dpps->count() }} DPP Sets</span>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" @click="openAddDppModal = true" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                    <span>➕ Add DPP Set</span>
                </button>
                @endif
            </div>
        </div>

        @if($course->dpps->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">📝</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No DPP Sets Published Yet</p>
            <p class="text-xs text-slate-500 mt-1">Daily practice questions will be loaded for each lesson.</p>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button type="button" @click="openAddDppModal = true" class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs inline-flex items-center space-x-1.5">
                <span>➕ Create First DPP Set</span>
            </button>
            @endif
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach($course->dpps as $dpp)
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase
                            @if($dpp->difficulty === 'easy') bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300
                            @elseif($dpp->difficulty === 'hard') bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300
                            @else bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 @endif">
                            {{ $dpp->difficulty }}
                        </span>
                        <span class="text-xs font-bold text-slate-400">
                            {{ $dpp->questions->count() }} Questions
                        </span>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $dpp->title }}</h4>
                    <div class="flex items-center space-x-2 text-xs text-slate-500 mt-2">
                        <span>🎯 {{ $dpp->total_marks }} Marks</span>
                        <span>&bull;</span>
                        <span>⏱️ {{ $dpp->duration_minutes }}m @if($dpp->is_timed)(Timed)@endif</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">Pass: {{ $dpp->pass_percentage }}%</span>
                    <div class="flex items-center space-x-1.5">
                        <a href="{{ route('dpp.show', $dpp->id) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            Solve DPP &rarr;
                        </a>
                        @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                        <a href="{{ route('dpp.edit', $dpp->id) }}" class="px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-800 transition" title="Edit DPP & Questions">
                            ✏️ Edit
                        </a>
                        <form method="POST" action="{{ route('dpp.destroy', $dpp->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete this DPP set and all its questions?');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                            <button type="submit" class="px-2 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800 transition" title="Delete DPP">
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

    <!-- ========================================== -->
    <!-- TAB 5: HOMEWORK & ASSIGNMENTS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'homework'" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Homework &amp; Assignments</h3>
                <p class="text-xs text-slate-500">Weekly problem sets, code/derivation challenges, and grading rubrics</p>
            </div>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button @click.stop="openAssignmentModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                <span>+ Post Assignment</span>
            </button>
            @endif
        </div>

        @if($course->assignments->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">💼</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Assignments Posted Yet</p>
            <p class="text-xs text-slate-500 mt-1">Weekly homework tasks will appear here.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($course->assignments as $assignment)
            <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            {{ $assignment->max_points }} Points
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            {{ $assignment->assigned_to_label }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">
                            Due: {{ date('M d, Y', strtotime($assignment->due_date)) }}
                        </span>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $assignment->title }}</h4>
                    <div class="text-xs text-slate-600 dark:text-slate-300 max-w-2xl space-y-1.5 mt-1">{!! $assignment->description !!}</div>
                    @if($assignment->hasAttachment())
                    <div class="mt-2.5 flex items-center gap-2">
                        <a href="{{ route('assignments.attachment', $assignment->id) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-bold transition">
                            <span>{{ $assignment->is_pdf_attachment ? '📄' : '📎' }}</span>
                            <span>Download {{ $assignment->attachment_name ?: 'Worksheet' }}</span>
                            @if($assignment->formatted_attachment_size)
                            <span class="text-[10px] font-normal opacity-75">({{ $assignment->formatted_attachment_size }})</span>
                            @endif
                        </a>
                    </div>
                    @endif
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                    <a href="{{ route('assignments.show', $assignment->id) }}" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        View &rarr;
                    </a>
                    @if(Auth::check() && (Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $assignment->teacher_id === Auth::id())))
                    <a href="{{ route('assignments.edit', $assignment->id) }}" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition" title="Edit Assignment">
                        ✏️ Edit
                    </a>
                    <form method="POST" action="{{ route('assignments.destroy', $assignment->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this assignment and all submissions?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-800 transition" title="Delete Assignment">
                            🗑️
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 6: TESTS & ASSESSMENTS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'tests'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Mastery Tests &amp; Examinations</h3>
                <p class="text-xs text-slate-500">Weekly evaluations, diagnostic assessments, and module completion tests</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-400 font-semibold">{{ $course->assessments->count() }} Tests</span>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" @click="openAddAssessmentModal = true" class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
                    <span>➕ Schedule Assessment</span>
                </button>
                @endif
            </div>
        </div>

        @if($course->assessments->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500">
            <span class="text-3xl block mb-2">🏆</span>
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Tests or Assessments Scheduled</p>
            <p class="text-xs text-slate-500 mt-1">Module exams will appear here as the course progresses.</p>
            @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
            <button type="button" @click="openAddAssessmentModal = true" class="mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-xs inline-flex items-center space-x-1.5">
                <span>➕ Schedule First Assessment</span>
            </button>
            @endif
        </div>
        @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
            @foreach($course->assessments as $assessment)
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                            {{ ucfirst($assessment->type) }} Test
                        </span>
                        <span class="text-xs font-bold text-slate-400">
                            {{ $assessment->total_points }} Marks
                        </span>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $assessment->title }}</h4>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $assessment->description }}</p>

                    <div class="flex items-center space-x-2 text-xs text-slate-500 mt-3">
                        <span>⏱️ {{ $assessment->time_limit_minutes }} mins</span>
                        <span>&bull;</span>
                        <span>Passing: {{ $assessment->passing_score }}%</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">Week {{ $assessment->week_number }}</span>
                    <div class="flex items-center space-x-1.5">
                        <a href="{{ route('courses.assessments.show', [$course->id, $assessment->id]) }}" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            Take Test &rarr;
                        </a>
                        @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                        <button type="button"
                                @click="editAssessmentId = {{ $assessment->id }}; editAssessmentTitle = '{{ addslashes($assessment->title) }}'; editAssessmentType = '{{ $assessment->type }}'; editAssessmentWeek = {{ $assessment->week_number }}; editAssessmentModuleId = '{{ $assessment->module_id ?? '' }}'; editAssessmentDesc = '{{ addslashes($assessment->description ?? '') }}'; editAssessmentPoints = {{ $assessment->total_points }}; editAssessmentPass = {{ $assessment->passing_score }}; editAssessmentTime = {{ $assessment->time_limit_minutes }}; openEditAssessmentModal = true"
                                class="px-2 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                            ✏️ Edit
                        </button>
                        <form method="POST" action="{{ route('courses.assessments.destroy', [$course->id, $assessment->id]) }}" class="inline" onsubmit="return confirm('Delete this assessment?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1.5 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800 transition">
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

    <!-- ======================================================= -->
    <!-- TAB 7: OFFICIAL LECTURE NOTES (PDF ONLY) -->
    <!-- ======================================================= -->
    <div x-show="activeTab === 'notes'" class="space-y-3">
        <!-- Direct PDF Viewer Toolbar (Download and New Tab Only) -->
        <div class="p-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-2.5 min-w-0">
                <span class="text-base shrink-0">📕</span>
                @if($course->lessons->isNotEmpty())
                <select x-model.number="selectedNoteLessonId" class="w-full sm:w-auto px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-emerald-500">
                    @foreach($course->lessons as $l)
                    <option value="{{ $l->id }}">
                        Lesson {{ $l->lesson_number }}: {{ $l->title }} @if(!empty($l->pdf_url)) &bull; [PDF Loaded] @endif
                    </option>
                    @endforeach
                </select>
                @else
                <span class="text-xs font-bold text-slate-500">No Lessons Loaded</span>
                @endif
            </div>

            <div class="flex items-center space-x-2 shrink-0">
                <a :href="currentPdfUrl" target="_blank" x-show="currentPdfUrl" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold flex items-center space-x-1.5 transition">
                    <span>Open in New Tab ↗</span>
                </a>
                <a :href="currentPdfUrl" download x-show="currentPdfUrl" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center space-x-1.5 shadow-xs transition">
                    <span>📥 Download PDF</span>
                </a>
                <template x-if="currentPdfUrl">
                    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                    <form method="POST" :action="'/courses/{{ $course->id }}/lessons/' + selectedNoteLessonId + '/notes'" class="inline" onsubmit="return confirm('Remove PDF note from this lesson?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold transition flex items-center space-x-1" title="Remove PDF Handout">
                            <span>🗑️ Remove PDF</span>
                        </button>
                    </form>
                    @endif
                </template>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                    @if($course->lessons->isNotEmpty())
                    <button type="button" @click="uploadLessonId = selectedNoteLessonId || {{ $course->lessons->first()?->id ?? 0 }}; isUploadLessonPdfOpen = true" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold flex items-center space-x-1 transition shadow-xs">
                        <span>📤 Upload Note PDF</span>
                    </button>
                    @else
                    <button type="button" @click="activeTab = 'curriculum'; openAddLessonModal = true" class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center space-x-1.5 transition shadow-xs" title="Lectures are required before notes can be uploaded">
                        <span>➕ Add Lecture First</span>
                    </button>
                    @endif
                @endif
            </div>
        </div>

        <!-- Direct PDF Viewer Body -->
        <template x-if="currentPdfUrl">
            <div class="w-full h-[760px] rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-sm">
                <iframe :src="currentPdfUrl" class="w-full h-full border-0" title="Lecture PDF Viewer"></iframe>
            </div>
        </template>
        <template x-if="!currentPdfUrl">
            <div class="rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 p-10 text-center bg-white dark:bg-slate-900 shadow-2xs space-y-3">
                <span class="text-3xl block">📄</span>
                @if($course->lessons->isEmpty())
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No lectures in this course yet.</p>
                <p class="text-[11px] text-slate-400 max-w-sm mx-auto">PDF notes and study sheets cannot be uploaded until lectures are created. Please add lectures in the Curriculum tab first.</p>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" @click="activeTab = 'curriculum'; openAddLessonModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center space-x-1.5">
                    <span>➕ Add Lecture in Curriculum &rarr;</span>
                </button>
                @endif
                @else
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No PDF uploaded for this lecture yet.</p>
                <p class="text-[11px] text-slate-400">Authentic faculty PDF notes and study sheets will display here directly.</p>
                @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
                <button type="button" @click="uploadLessonId = selectedNoteLessonId || {{ $course->lessons->first()?->id ?? 0 }}; isUploadLessonPdfOpen = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center space-x-1.5">
                    <span>📤 Upload Note PDF</span>
                </button>
                @endif
                @endif
            </div>
        </template>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: ADD SCHEDULE SESSION -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openScheduleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openScheduleModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openScheduleModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Timetable Schedule Slot</h3>
                    <button type="button" @click="openScheduleModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('schedules.store') }}" class="space-y-3 mt-3">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Session Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. {{ $course->code }} - Lecture" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Day *</label>
                        <select name="day_of_week" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Delivery Mode</label>
                        <select name="delivery_mode" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="offline">Offline Classroom</option>
                            <option value="online">Online Live</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Start Time *</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">End Time *</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Room / Location</label>
                    <input type="text" name="room" value="{{ old('room') }}" placeholder="e.g. Academic Hall 101" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openScheduleModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold">Save Schedule</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 2: POST ASSIGNMENT -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openAssignmentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAssignmentModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAssignmentModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Post New Course Assignment</h3>
                    <button type="button" @click="openAssignmentModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data" x-data="{ targetType: 'all_enrolled' }" class="space-y-3 mt-3">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">

                <!-- Assigned To Target Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assigned To (Target Scholars) *</label>
                    <select name="target_type" x-model="targetType" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs font-semibold">
                        <option value="all_enrolled">👥 All Enrolled Scholars in this Course</option>
                        <option value="all_students">🏫 All Active Scholars in Academy</option>
                        <option value="specific_student">👤 Specific Individual Scholar</option>
                    </select>
                </div>

                <!-- Specific Student Dropdown -->
                <div x-show="targetType === 'specific_student'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Select Specific Scholar *</label>
                    <select name="student_id" class="w-full px-3 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        @foreach(\App\Models\User::where('role', 'student')->orderBy('name')->get() as $std)
                            <option value="{{ $std->id }}">{{ $std->name }} ({{ $std->student_id_number ?? $std->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Case Study &amp; Problem Set" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Due Date *</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Max Points *</label>
                        <input type="number" name="max_points" value="{{ old('max_points') }}" placeholder="100" min="1" max="1000" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Instructions / Description</label>
                    <textarea name="description" rows="3" placeholder="Submission instructions, rubric, and task guidelines..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Attach Worksheet / DPP (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900 border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Attach printable worksheet, PDF DPP, or reference sheet (Max 25MB).</p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openAssignmentModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold">Publish Assignment</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 3: WATCH VIDEO LECTURE -->
    <!-- ========================================== -->
    <div x-show="openVideoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openVideoModal = false">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="openVideoModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            <div class="relative bg-slate-900 rounded-3xl shadow-2xl border border-slate-800 max-w-3xl w-full p-5 z-10 text-white text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] flex flex-col" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 flex-shrink-0">
                    <h3 class="text-sm font-bold text-white truncate" x-text="activeVideoTitle || 'Lecture Video'"></h3>
                    <button type="button" @click="openVideoModal = false" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="mt-4 aspect-video rounded-2xl overflow-hidden bg-black border border-slate-800 flex-1">
                    <iframe :src="activeVideoUrl" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4: UPLOAD LESSON PDF NOTE -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="isUploadLessonPdfOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="isUploadLessonPdfOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isUploadLessonPdfOpen = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>📤</span>
                        <span>Upload Lecture PDF Note</span>
                    </h3>
                    <button type="button" @click="isUploadLessonPdfOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                @if($course->lessons->isNotEmpty())
                <form action="{{ route('courses.notes.upload', $course->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target Lecture / Lesson *</label>
                        <select x-model.number="uploadLessonId" name="lesson_id" class="w-full px-3 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            @foreach($course->lessons as $l)
                            <option value="{{ $l->id }}">Lesson {{ $l->lesson_number }}: {{ $l->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Select PDF File (.pdf) *</label>
                        <input type="file" name="pdf_file" accept=".pdf,application/pdf" required class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300">
                        <p class="text-[10px] text-slate-400 mt-1">Maximum 20MB. Standard PDF document.</p>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="isUploadLessonPdfOpen = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm">Upload PDF</button>
                    </div>
                </form>
                @else
                <div class="space-y-4 mt-3">
                    <div class="p-4 bg-amber-500/10 border border-amber-500/25 rounded-2xl text-amber-800 dark:text-amber-200 space-y-1.5">
                        <div class="flex items-center space-x-2 font-bold text-xs text-amber-900 dark:text-amber-100">
                            <span>⚠️</span>
                            <span>Cannot Upload PDF: No Lectures Found</span>
                        </div>
                        <p class="text-[11px] leading-relaxed text-amber-700 dark:text-amber-300">
                            PDF notes must be attached to an existing lecture. Uploading is disabled until at least one lecture or topic is added to the course curriculum.
                        </p>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="isUploadLessonPdfOpen = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-400">Close</button>
                        <button type="button" @click="isUploadLessonPdfOpen = false; activeTab = 'curriculum'; openAddLessonModal = true" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold inline-flex items-center space-x-1.5 shadow-sm">
                            <span>➕ Add Lecture in Curriculum</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 5: ADD VIDEO LECTURE -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openAddVideoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAddVideoModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddVideoModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                        <span>🎥</span>
                        <span>Add Video Lecture</span>
                    </h3>
                    <button type="button" @click="openAddVideoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('courses.videos.store', $course->id) }}" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target Lecture / Slot</label>
                        <select name="lesson_id" x-model="selectedLessonForVideo" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">-- Add as New Lecture --</option>
                            @foreach($course->lessons as $l)
                            <option value="{{ $l->id }}">Attach to Lesson {{ $l->lesson_number }}: {{ $l->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lecture Title *</label>
                        <input type="text" name="title" x-model="videoModalTitle" required placeholder="e.g. Introduction to Dynamic Programming" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Video Stream URL *</label>
                        <input type="url" name="video_url" x-model="videoModalUrl" required placeholder="https://www.youtube.com/watch?v=... or MP4 stream" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <p class="text-[10px] text-slate-400 mt-0.5">Supports YouTube, Vimeo, or direct video streams.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" x-model="videoModalDuration" min="1" max="360" placeholder="30" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Topic Summary / Description</label>
                        <textarea name="description" rows="2" placeholder="Brief outline of concepts covered in this video lecture..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openAddVideoModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold">Save Video Lecture</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 6: ADD DAILY DPP PRACTICE SET -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openAddDppModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAddDppModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddDppModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 pt-3 pb-24 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 max-h-[calc(100dvh-7rem)] sm:max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                        <span>⚡</span>
                        <span>Post DPP Practice Set</span>
                    </h3>
                    <button type="button" @click="openAddDppModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('courses.dpps.store', $course->id) }}" class="space-y-3 mt-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Academic Class / Subject *</label>
                        <select name="subject_id" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">Select an existing class subject</option>
                            @foreach($classSubjects as $classSubject)
                            <option value="{{ $classSubject->id }}">{{ $classSubject->academicClass->display_name }} — {{ $classSubject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">DPP Set Title *</label>
                        <input type="text" name="title" required placeholder="e.g. DPP: Core Fundamentals &amp; Exercises" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assigned Date *</label>
                            <input type="date" name="assigned_date" value="{{ date('Y-m-d') }}" required class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Difficulty</label>
                            <select name="difficulty" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="standard">Standard</option>
                                <option value="advanced">Advanced</option>
                                <option value="olympiad">Olympiad Tier</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Duration (Mins)</label>
                            <input type="number" name="duration_minutes" placeholder="30" min="0" max="300" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Total Marks</label>
                            <input type="number" name="total_marks" placeholder="20" min="1" max="500" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Link to Lesson (Optional)</label>
                        <select name="lesson_id" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">-- General Course DPP --</option>
                            @foreach($course->lessons as $l)
                            <option value="{{ $l->id }}">Lesson {{ $l->lesson_number }}: {{ $l->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openAddDppModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold">Create DPP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 7: BULK IMPORT CURRICULUM (CSV) -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openCsvImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openCsvImportModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openCsvImportModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 z-10 text-left my-2 sm:my-4 space-y-4" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>📊</span>
                        <span>Bulk Feed Course Curriculum (CSV)</span>
                    </h3>
                    <button type="button" @click="openCsvImportModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-2">
                    <p class="font-semibold text-slate-800 dark:text-slate-200">Upload structured CSV with columns:</p>
                    <code class="block font-mono text-[10px] bg-white dark:bg-slate-950 p-2 rounded-lg border border-slate-200 dark:border-slate-800 text-emerald-600 dark:text-emerald-400 overflow-x-auto">
                        module_title,module_order,module_description,lesson_number,lesson_title,duration_minutes,video_url,pdf_url,summary
                    </code>
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[11px] text-slate-500">Need a blank template?</span>
                        <a href="{{ route('courses.curriculum.template-csv', $course->id) }}" class="inline-flex items-center space-x-1 font-bold text-indigo-600 dark:text-indigo-400 hover:underline text-xs">
                            <span>📥 Download CSV Template</span>
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('courses.curriculum.import-csv', $course->id) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Select CSV File (.csv) *</label>
                        <input type="file" name="curriculum_csv" accept=".csv,text/csv,text/plain" required class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openCsvImportModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold">Import Curriculum</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 8: ADD MODULE (MANUAL SETUP) -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openAddModuleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAddModuleModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddModuleModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>📑</span>
                        <span>Add Curriculum Module / Unit</span>
                    </h3>
                    <button type="button" @click="openAddModuleModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('courses.modules.store', $course->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Module Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Unit 1: Foundations of Mechanics" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Order / Week #</label>
                            <input type="number" name="order_index" value="{{ ($course->modules->max('order_index') ?? 0) + 1 }}" min="1" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Theme (Optional)</label>
                            <input type="text" name="theme" placeholder="e.g. Kinematics" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Description (Optional)</label>
                        <textarea name="description" rows="2" placeholder="Overview of concepts and problem-solving targets..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openAddModuleModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold">Create Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 9: EDIT MODULE -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openEditModuleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openEditModuleModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditModuleModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>✏️</span>
                        <span>Edit Module</span>
                    </h3>
                    <button type="button" @click="openEditModuleModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/courses/{{ $course->id }}/modules/' + editModuleId" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Module Title *</label>
                        <input type="text" name="title" x-model="editModuleTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Order / Week #</label>
                            <input type="number" name="order_index" x-model.number="editModuleOrder" min="1" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Theme</label>
                            <input type="text" name="theme" x-model="editModuleTheme" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Description</label>
                        <textarea name="description" x-model="editModuleDescription" rows="2" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openEditModuleModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold">Update Module</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 10: ADD LESSON (MANUAL SETUP) -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openAddLessonModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAddLessonModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddLessonModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3 max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>🎬</span>
                        <span>Add Lesson / Topic</span>
                    </h3>
                    <button type="button" @click="openAddLessonModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form method="POST" action="{{ route('courses.lessons.store', $course->id) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target Module *</label>
                        <select name="module_id" x-model="defaultLessonModuleId" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            @foreach($course->modules as $m)
                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lesson Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Free-Body Diagrams & Friction" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lesson #</label>
                            <input type="number" name="lesson_number" value="{{ ($course->lessons->max('lesson_number') ?? 0) + 1 }}" min="1" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Duration (Mins)</label>
                            <input type="number" name="duration_minutes" value="45" min="1" max="480" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Video Stream URL (Optional)</label>
                        <input type="url" name="video_url" placeholder="https://youtube.com/... or MP4 link" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Study Material PDF (Optional)</label>
                        <input type="file" name="pdf_file" accept=".pdf,application/pdf" class="w-full text-xs text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Summary / Key Takeaways</label>
                        <textarea name="topic_summary" rows="2" placeholder="Summary of what is taught in this lesson..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openAddLessonModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs font-bold">Add Lesson</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- MODAL 11: EDIT LESSON -->
    <!-- ========================================== -->
    @if(Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher()))
    <div x-show="openEditLessonModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openEditLessonModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditLessonModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3 max-h-[85vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>✏️</span>
                        <span>Edit Lesson</span>
                    </h3>
                    <button type="button" @click="openEditLessonModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/courses/{{ $course->id }}/lessons/' + editLessonId" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target Module *</label>
                        <select name="module_id" x-model="editLessonModuleId" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            @foreach($course->modules as $m)
                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lesson Title *</label>
                        <input type="text" name="title" x-model="editLessonTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lesson #</label>
                            <input type="number" name="lesson_number" x-model.number="editLessonNumber" min="1" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Duration (Mins)</label>
                            <input type="number" name="duration_minutes" x-model.number="editLessonDuration" min="1" max="480" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Video Stream URL</label>
                        <input type="url" name="video_url" x-model="editLessonVideoUrl" placeholder="https://..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Replace PDF Note (Optional)</label>
                        <input type="file" name="pdf_file" accept=".pdf,application/pdf" class="w-full text-xs text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950 dark:file:text-emerald-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Summary</label>
                        <textarea name="topic_summary" x-model="editLessonSummary" rows="2" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openEditLessonModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-teal-600 hover:bg-teal-500 text-white rounded-xl text-xs font-bold">Update Lesson</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 12: EDIT COURSE DETAILS -->
    <div x-show="openEditCourseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openEditCourseModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditCourseModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 z-10 text-left my-2 sm:my-4 space-y-4" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>✏️</span>
                        <span>Edit Course Details</span>
                    </h3>
                    <button type="button" @click="openEditCourseModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form action="{{ route('courses.update', $course->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Code *</label>
                            <input type="text" name="code" x-model="editCourseCode" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs uppercase text-slate-900 dark:text-white">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Title *</label>
                            <input type="text" name="title" x-model="editCourseTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Category *</label>
                            <input type="text" name="category" x-model="editCourseCategory" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Credits *</label>
                            <input type="number" name="credits" x-model.number="editCourseCredits" min="1" max="12" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Capacity</label>
                            <input type="number" name="capacity" x-model.number="editCourseCapacity" min="1" max="500" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Status</label>
                            <select name="status" x-model="editCourseStatus" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Teacher</label>
                            <select name="teacher_id" x-model="editCourseTeacherId" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="">-- No Teacher Assigned --</option>
                                @if($course->teacher)
                                <option value="{{ $course->teacher->id }}">{{ $course->teacher->name }}</option>
                                @endif
                                @foreach(\App\Models\User::whereIn('role', ['teacher', 'admin'])->where('id', '!=', $course->teacher_id)->orderBy('name')->get() as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->role) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Description</label>
                        <textarea name="description" x-model="editCourseDescription" rows="3" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openEditCourseModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold">Update Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 13: EDIT VIDEO LECTURE -->
    <div x-show="openEditVideoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openEditVideoModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditVideoModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>✏️</span>
                        <span>Edit Video Lecture</span>
                    </h3>
                    <button type="button" @click="openEditVideoModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/courses/{{ $course->id }}/videos/' + editVideoId" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="lesson_id" :value="editVideoLessonId">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Video Title *</label>
                        <input type="text" name="title" x-model="editVideoTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Stream URL (YouTube, Vimeo, MP4) *</label>
                        <input type="url" name="video_url" x-model="editVideoUrl" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Duration (Mins)</label>
                        <input type="number" name="duration_minutes" x-model.number="editVideoDuration" min="1" max="360" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openEditVideoModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold">Update Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 14: EDIT SCHEDULE SESSION -->
    <div x-show="openScheduleEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openScheduleEditModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openScheduleEditModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>🗓️</span>
                        <span>Edit Class Schedule Slot</span>
                    </h3>
                    <button type="button" @click="openScheduleEditModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/schedules/' + editScheduleId" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="course_id" value="{{ $course->id }}">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Session Title *</label>
                        <input type="text" name="title" x-model="editScheduleTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Day of Week *</label>
                            <select name="day_of_week" x-model="editScheduleDay" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Delivery Mode</label>
                            <select name="delivery_mode" x-model="editScheduleMode" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="offline">Offline (Physical)</option>
                                <option value="online">Online (Virtual)</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Start Time *</label>
                            <input type="time" name="start_time" x-model="editScheduleStart" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">End Time *</label>
                            <input type="time" name="end_time" x-model="editScheduleEnd" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Room / Location (Optional)</label>
                        <input type="text" name="room" x-model="editScheduleRoom" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openScheduleEditModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold">Update Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 15: ADD MASTERY ASSESSMENT -->
    <div x-show="openAddAssessmentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openAddAssessmentModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddAssessmentModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>🏆</span>
                        <span>Schedule Course Assessment</span>
                    </h3>
                    <button type="button" @click="openAddAssessmentModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('courses.assessments.store', $course->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assessment Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Unit 1 Milestone: Lip Articulation Test" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Evaluation Type *</label>
                            <select name="type" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="oral">Oral Interview / Speech</option>
                                <option value="quiz">Objective Quiz</option>
                                <option value="written">Written Analysis</option>
                                <option value="project">Project / Case Presentation</option>
                                <option value="exam">Comprehensive Exam</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Week Number *</label>
                            <input type="number" name="week_number" value="1" min="1" max="52" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Total Marks *</label>
                            <input type="number" name="total_points" value="100" min="1" max="500" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Pass % *</label>
                            <input type="number" name="passing_score" value="60" min="1" max="100" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Time (Mins)</label>
                            <input type="number" name="time_limit_minutes" value="45" min="5" max="300" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Link to Module (Optional)</label>
                        <select name="module_id" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">-- General Course Assessment --</option>
                            @foreach($course->modules as $m)
                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Instructions / Assessment Rubric</label>
                        <textarea name="description" rows="2" placeholder="Tasks, speech prompts, and scoring criteria..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openAddAssessmentModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold">Schedule Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 16: EDIT MASTERY ASSESSMENT -->
    <div x-show="openEditAssessmentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="openEditAssessmentModal = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditAssessmentModal = false"></div>
        <div class="min-h-full flex items-start sm:items-center justify-center p-3 sm:p-6 text-center">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 z-10 text-left my-2 sm:my-4 space-y-3" @click.stop>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>✏️</span>
                        <span>Edit Course Assessment</span>
                    </h3>
                    <button type="button" @click="openEditAssessmentModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/courses/{{ $course->id }}/assessments/' + editAssessmentId" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assessment Title *</label>
                        <input type="text" name="title" x-model="editAssessmentTitle" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Evaluation Type *</label>
                            <select name="type" x-model="editAssessmentType" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                                <option value="oral">Oral Interview / Speech</option>
                                <option value="quiz">Objective Quiz</option>
                                <option value="written">Written Analysis</option>
                                <option value="project">Project / Case Presentation</option>
                                <option value="exam">Comprehensive Exam</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Week Number *</label>
                            <input type="number" name="week_number" x-model.number="editAssessmentWeek" min="1" max="52" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Total Marks *</label>
                            <input type="number" name="total_points" x-model.number="editAssessmentPoints" min="1" max="500" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Pass % *</label>
                            <input type="number" name="passing_score" x-model.number="editAssessmentPass" min="1" max="100" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Time (Mins)</label>
                            <input type="number" name="time_limit_minutes" x-model.number="editAssessmentTime" min="5" max="300" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Link to Module (Optional)</label>
                        <select name="module_id" x-model="editAssessmentModuleId" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                            <option value="">-- General Course Assessment --</option>
                            @foreach($course->modules as $m)
                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Instructions / Assessment Rubric</label>
                        <textarea name="description" x-model="editAssessmentDesc" rows="2" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="openEditAssessmentModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold">Update Assessment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
