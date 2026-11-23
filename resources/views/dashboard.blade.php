@extends('layouts.app')

@section('title', 'Dashboard' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="space-y-4">

    <!-- Infix LMS Signature Dashboard Title & Action Row -->
    <div class="white_box p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 font-semibold mb-1">
                <i class="ti-calendar"></i>
                <span>{{ now()->format('l, F j, Y') }}</span>
                <span>&bull;</span>
                <span class="text-slate-500 font-mono">{{ now()->format('h:i A') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Welcome To — {{ $school['name'] }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider text-white shadow-xs" style="background: var(--system_primary_gradient);">
                    @if($user->isAdmin()) ADMIN PORTAL @else {{ strtoupper($user->role) }} @endif
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ $school['motto'] ?? 'Aim, Ascend, Achieve • Where Passion Meets Precision' }} &bull; <span class="hidden sm:inline">Your potential has no fixed limits</span>
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            @if($user->isAdmin())
            <a href="{{ route('appearance.index') }}" class="primary-btn" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <i class="ti-palette text-xs"></i> <span>Themes</span>
            </a>
            @endif
            <a href="{{ route('my-batch') }}" class="primary-btn" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <span>🎒 My Batch</span>
            </a>
            <a href="{{ route('schedules.index') }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-calendar text-xs"></i>
                <span>Timetable</span>
            </a>
            @if($user->isAdmin() || $user->isTeacher())
            <a href="{{ route('analytics.index') }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-bar-chart text-xs"></i>
                <span>Reports</span>
            </a>
            @endif
        </div>
    </div>

    @if(!empty($birthdayCelebration))
    <div class="p-4 rounded-xl bg-gradient-to-r from-pink-500/10 via-purple-500/10 to-indigo-500/10 border border-pink-300 dark:border-pink-800 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">🎂</span>
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">{{ $birthdayCelebration['message'] }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Institutional birthday blessings! Enjoy your special day!</p>
            </div>
        </div>
        <span class="px-3 py-1 text-xs font-black bg-pink-500 text-white rounded-full shadow-sm">+100 XP</span>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- 1. ADMIN EXECUTIVE DASHBOARD (INFIX ARCHITECTURE) -->
    <!-- ========================================== -->
    @if($user->isAdmin())
        @include('dashboard.infix-admin')
    @endif

    <!-- TEACHER DASHBOARD -->
    @if($user->isTeacher())
    <div class="space-y-3">
        <!-- Compact Stats Row with Minimalist Borders -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">My Courses</div>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono">{{ $taughtCourses->count() }}</div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Students</div>
                <div class="text-lg font-black text-emerald-600 mt-0.5 font-mono">{{ $totalStudentsTaught }}</div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">To Grade</div>
                <div class="text-lg font-black {{ $pendingSubmissions->count() > 0 ? 'text-amber-500' : 'text-slate-900 dark:text-white' }} mt-0.5 font-mono">
                    {{ $pendingSubmissions->count() }}
                </div>
            </div>
        </div>

        <!-- Pending Submissions Needing Grading -->
        @if($pendingSubmissions->count() > 0)
        <div class="bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-lg p-2.5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-bold text-amber-900 dark:text-amber-300">Submissions Awaiting Grading</h3>
                <span class="px-1.5 py-0.2 rounded-full bg-amber-200 dark:bg-amber-900 text-amber-800 dark:text-amber-200 text-[10px] font-bold">{{ $pendingSubmissions->count() }} items</span>
            </div>
            <div class="space-y-1.5">
                @foreach($pendingSubmissions as $sub)
                <div class="bg-white dark:bg-slate-900 p-2 rounded-md border border-amber-200/80 dark:border-amber-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 text-xs">
                    <div>
                        <div class="font-bold text-slate-900 dark:text-white">{{ $sub->student->name }}</div>
                        <div class="text-[11px] text-slate-500">{{ $sub->assignment->title }} &bull; {{ $sub->assignment->course->code }}</div>
                    </div>
                    <a href="{{ route('assignments.show', $sub->assignment->id) }}" 
                       class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs rounded-md transition whitespace-nowrap text-center">
                        Grade &rarr;
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- My Teaching Courses Grid -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">My Teaching Courses</h3>
                <a href="{{ route('courses.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">Course Catalog &rarr;</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($taughtCourses as $tc)
                <div class="bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">{{ $tc->code }}</span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $tc->title }}</h4>
                        </div>
                        <span class="text-[10px] font-mono text-slate-500">{{ $tc->credits }} Credits</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-1">{{ $tc->description }}</p>

                    <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-500">Students: <strong class="text-slate-900 dark:text-white font-mono">{{ $tc->enrollments_count }}/{{ $tc->capacity }}</strong></span>
                        <a href="{{ route('courses.show', $tc->id) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold text-xs">Details &rarr;</a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Weekly Teaching Timetable -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3">
            <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm mb-2">Weekly Class Schedule</h3>
            @if($schedules->isEmpty())
                <p class="text-xs text-slate-500">No scheduled sessions found.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($schedules as $s)
                    <div class="p-2 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850/60">
                        <div class="flex items-center justify-between text-[10px] font-mono font-bold text-slate-500 uppercase">
                            <span>{{ $s->day_of_week }}</span>
                            <span class="text-emerald-600 dark:text-emerald-400">{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</span>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-xs mt-0.5 truncate">{{ $s->course->code }}: {{ $s->title }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">
                            <span>📍 {{ $s->room ?? 'Room TBA' }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- STUDENT DASHBOARD -->
    @if($user->isStudent())
    <div class="space-y-3">

        <!-- 1. COMPACT METRIC GRIDS: RESOURCE COUNTERS (Notes, Videos, DPP, Assignments, Tests) WITH INLINE BADGES & ZERO WASTED WHITESPACE -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 p-1 bg-slate-100/80 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg">
            <!-- Resource 1: Notes -->
            <a href="{{ route('my-batch') }}?tab=notes" class="px-2.5 py-1.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 hover:border-emerald-500/50 transition flex items-center justify-between gap-1.5 text-xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-sm">📕</span>
                    <span class="truncate font-semibold text-slate-700 dark:text-slate-200">Notes</span>
                </div>
                <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ $totalNotesCount ?? 0 }}
                </span>
            </a>

            <!-- Resource 2: Videos -->
            <a href="{{ route('my-batch') }}?tab=videos" class="px-2.5 py-1.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 hover:border-emerald-500/50 transition flex items-center justify-between gap-1.5 text-xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-sm">🎬</span>
                    <span class="truncate font-semibold text-slate-700 dark:text-slate-200">Videos</span>
                </div>
                <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ $totalVideosCount ?? 0 }}
                </span>
            </a>

            <!-- Resource 3: DPP -->
            <a href="{{ route('dpp.index') }}" class="px-2.5 py-1.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 hover:border-emerald-500/50 transition flex items-center justify-between gap-1.5 text-xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-sm">📝</span>
                    <span class="truncate font-semibold text-slate-700 dark:text-slate-200">DPP</span>
                </div>
                <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                    {{ $totalDppsCount ?? 0 }}
                </span>
            </a>

            <!-- Resource 4: Assignments -->
            <a href="{{ route('assignments.index') }}" class="px-2.5 py-1.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 hover:border-emerald-500/50 transition flex items-center justify-between gap-1.5 text-xs">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-sm">📋</span>
                    <span class="truncate font-semibold text-slate-700 dark:text-slate-200">Assignments</span>
                </div>
                <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300">
                    {{ $totalHomeworkCount ?? $assignments->count() }}
                </span>
            </a>

            <!-- Resource 5: Tests -->
            <a href="{{ route('my-batch') }}?tab=tests" class="px-2.5 py-1.5 rounded-md border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-slate-800 hover:border-emerald-500/50 transition flex items-center justify-between gap-1.5 text-xs col-span-2 sm:col-span-1">
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="text-sm">🧪</span>
                    <span class="truncate font-semibold text-slate-700 dark:text-slate-200">Tests</span>
                </div>
                <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded font-mono text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300">
                    {{ $totalTestsCount ?? 0 }}
                </span>
            </a>
        </div>

        <!-- 1. COMPACT METRIC GRIDS: STUDENT STATS CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            <!-- 1. Learning Streak Card -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Daily Streak</div>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5">🔥 {{ $user->streak_count ?? 1 }}d Streak</div>
                <div class="text-[10px] text-slate-400 truncate">Continuous Learning</div>
            </div>

            <!-- 2. Academic XP & Tier Card -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Academic XP</div>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5">⚡ {{ number_format($user->xp_points ?? 0) }}</div>
                <div class="text-[10px] text-slate-400 truncate">{{ $user->academic_tier ?? 'Honors Scholar' }}</div>
            </div>

            <!-- 3. Syllabus Coverage -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Syllabus</div>
                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">{{ $syllabusProgressRate ?? 0 }}%</div>
                <div class="text-[10px] text-slate-400 truncate">Coverage Completed</div>
            </div>

            <!-- 4. Attendance Rate -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-sky-600 dark:text-sky-400 uppercase tracking-wider">Attendance</div>
                <div class="text-lg font-black text-sky-600 dark:text-sky-400 mt-0.5 font-mono">{{ $attendanceRate !== null ? number_format($attendanceRate, 1) . '%' : '—' }}</div>
                <div class="text-[10px] text-slate-400 truncate">{{ $attendanceSessionsCount ?? 0 }} Sessions</div>
            </div>

            <!-- 5. Active Deliverables -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Deliverables</div>
                <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono">{{ $completedHomeworkCount ?? 0 }}/{{ $totalHomeworkCount ?? $assignments->count() }}</div>
                <div class="text-[10px] text-slate-400 truncate">Tasks Handed In</div>
            </div>

            <!-- 6. Academic Score -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Avg Score</div>
                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">{{ $avgStudentGrade }}%</div>
                <div class="text-[10px] text-slate-400 truncate">Academic Standing</div>
            </div>
        </div>

        <!-- Motivational Knowledge Station -->
        <div id="motivation-station" class="p-3 rounded-xl bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-purple-500/10 border border-amber-300 dark:border-amber-800 flex items-center justify-between gap-3 flame-active">
            <div class="flex items-center space-x-3 min-w-0">
                <span class="text-xl flex-shrink-0">🔥</span>
                <div class="min-w-0">
                    <div class="text-xs font-black text-slate-900 dark:text-white truncate">
                        Spark ⚡ &bull; {{ $school['motto'] ?? 'Aim, Ascend, Achieve • Where Passion Meets Precision' }}
                    </div>
                    <div class="text-[11px] text-slate-600 dark:text-slate-300 truncate">
                        "Don't just prepare for the future. Build, discover and create it."
                    </div>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-500 text-white shadow-xs flex-shrink-0">
                Spark ⚡
            </span>
        </div>

        <!-- 3. MINIMALIST BORDER DIVIDERS: 2 COLUMN FLAT PANELS (ENROLLED COURSES, DELIVERABLES & SCHEDULE) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <!-- Left 2 Cols: My Batch & Assignments Table -->
            <div class="lg:col-span-2 space-y-3">
                <!-- Enrolled Courses & My Batch Flat Panel -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 transition-colors">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">My Academic Batch &amp; Courses</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $user->batch->name ?? 'Enrolled Batch' }}</span>
                        </div>
                        <a href="{{ route('my-batch') }}" class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-xs">
                            Open My Batch &rarr;
                        </a>
                    </div>

                    <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
                    <div class="pt-1.5 pb-2">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                            <span>Syllabus Progress: <strong>{{ $syllabusProgressRate ?? 0 }}% completed</strong></span>
                            <span class="font-mono text-[10px] text-slate-400">Target Coverage</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $syllabusProgressRate ?? 0 }}%"></div>
                        </div>
                    </div>

                    @if($enrollments->isEmpty())
                        <div class="text-center py-3 text-slate-400 text-xs">
                            <p>Standard grade curriculum registered in My Batch.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($enrollments as $enr)
                            <div class="p-2 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850/60 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <div class="flex items-center justify-between">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-extrabold bg-white dark:bg-slate-700 text-emerald-800 dark:text-emerald-300 border border-slate-200 dark:border-slate-600">{{ $enr->course->code }}</span>
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $enr->grade_letter ?? 'In Progress' }}</span>
                                </div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs mt-1 truncate">{{ $enr->course->title }}</h4>
                                <div class="mt-1.5 pt-1 border-t border-slate-200/60 dark:border-slate-700/60 flex justify-between items-center text-[11px]">
                                    <span class="text-slate-500 dark:text-slate-400 truncate">{{ $enr->course->teacher?->name ?? 'TBA' }}</span>
                                    <a href="{{ route('courses.show', $enr->course_id) }}" class="text-emerald-700 dark:text-emerald-400 font-bold hover:underline shrink-0">Details &rarr;</a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Assignments Status Flat Panel with Minimalist Borders -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 transition-colors">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Assignments &amp; Deliverables</h3>
                        <a href="{{ route('assignments.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">View All &rarr;</a>
                    </div>

                    <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
                    <div class="pt-1.5 pb-2">
                        @php
                            $hwTotal = max(1, $totalHomeworkCount ?? $assignments->count());
                            $hwDone = $completedHomeworkCount ?? 0;
                            $hwPercent = round(($hwDone / $hwTotal) * 100);
                        @endphp
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                            <span>Deliverables Submitted: <strong>{{ $hwDone }}/{{ $hwTotal }}</strong></span>
                            <span class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">{{ $hwPercent }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $hwPercent }}%"></div>
                        </div>
                    </div>

                    @if($assignments->isEmpty())
                        <p class="text-xs text-slate-400 py-3 text-center">No active assignments pending.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-850/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-600 dark:text-slate-400">
                                        <th class="p-2">Course</th>
                                        <th class="p-2">Assignment</th>
                                        <th class="p-2">Due</th>
                                        <th class="p-2 text-center">Status</th>
                                        <th class="p-2 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($assignments->take(5) as $asg)
                                    @php
                                        $sub = $mySubmissions[$asg->id] ?? null;
                                        $isOverdue = now()->isAfter($asg->due_date);
                                    @endphp
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition">
                                        <td class="p-2 font-bold text-slate-800 dark:text-slate-200">
                                            <span class="px-1.5 py-0.2 rounded font-mono bg-slate-100 dark:bg-slate-800 text-[10px]">{{ $asg->course->code }}</span>
                                        </td>
                                        <td class="p-2 font-medium text-slate-900 dark:text-white">
                                            {{ $asg->title }}
                                        </td>
                                        <td class="p-2 text-slate-500 dark:text-slate-400 whitespace-nowrap text-[11px] font-mono">
                                            {{ $asg->due_date->format('M d') }}
                                        </td>
                                        <td class="p-2 text-center whitespace-nowrap">
                                            @if($sub)
                                                @if($sub->status === 'graded')
                                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold font-mono bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                                        {{ $sub->grade }}/{{ $asg->max_points }}
                                                    </span>
                                                @else
                                                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300">
                                                        Submitted
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold {{ $isOverdue ? 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">
                                                    {{ $isOverdue ? 'Overdue' : 'Pending' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-right whitespace-nowrap">
                                            <a href="{{ route('assignments.show', $asg->id) }}" class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition">
                                                {{ $sub ? 'View' : 'Submit' }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right 1 Col: Class Timetable Flat Panel with Minimalist Borders -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-3 transition-colors">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Weekly Timetable</h3>
                    <a href="{{ route('schedules.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">Full Grid &rarr;</a>
                </div>

                <!-- 4. INLINE PROGRESS BARS: SLIM & EMBEDDED DIRECTLY BENEATH SECTION HEADER -->
                <div class="pt-1.5 pb-2">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                        <span>Upcoming Classes: <strong>{{ $mySchedules->count() }} slots</strong></span>
                        <span class="font-mono text-[10px] text-teal-600 dark:text-teal-400 font-bold">Scheduled</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-500 rounded-full" style="width: 100%"></div>
                    </div>
                </div>

                @if($mySchedules->isEmpty())
                    <p class="text-xs text-slate-400 py-3 text-center">No scheduled sessions for this week.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-850/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-600 dark:text-slate-400">
                                    <th class="p-1.5">Day &amp; Time</th>
                                    <th class="p-1.5">Subject</th>
                                    <th class="p-1.5">Room</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($mySchedules->take(6) as $sch)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/40 transition">
                                    <td class="p-1.5 whitespace-nowrap">
                                        <span class="font-bold text-slate-900 dark:text-white block text-xs">{{ substr($sch->day_of_week, 0, 3) }}</span>
                                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold font-mono">{{ substr($sch->start_time, 0, 5) }}</span>
                                    </td>
                                    <td class="p-1.5">
                                        <span class="font-bold text-slate-900 dark:text-white block line-clamp-1 text-xs">{{ $sch->title }}</span>
                                        <span class="text-[10px] font-mono text-slate-500">{{ $sch->course->code }}</span>
                                    </td>
                                    <td class="p-1.5 text-slate-500 text-[10px] whitespace-nowrap">
                                        📍 {{ $sch->room ?? 'TBA' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>

@if($user->isAdmin())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#cbd5e1' : '#334155';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    // 1. Attendance Trend Chart
    const ctxAtt = document.getElementById('chart-attendance-trend');
    if (ctxAtt) {
        new Chart(ctxAtt, {
            type: 'line',
            data: {
                labels: {!! json_encode($attendanceTrendChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Present',
                        data: {!! json_encode($attendanceTrendChart['present'] ?? []) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Absent',
                        data: {!! json_encode($attendanceTrendChart['absent'] ?? []) !!},
                        borderColor: '#f43f5e',
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Excused',
                        data: {!! json_encode($attendanceTrendChart['excused'] ?? []) !!},
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        pointRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // Infix 6-Month Income vs Expense Trend Chart
    const ctxIncExp = document.getElementById('chart-income-expense-trend');
    if (ctxIncExp) {
        new Chart(ctxIncExp, {
            type: 'bar',
            data: {
                labels: {!! json_encode($financialTrendChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Operating Income',
                        data: {!! json_encode($financialTrendChart['income'] ?? []) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    },
                    {
                        label: 'Operating Expenses',
                        data: {!! json_encode($financialTrendChart['expenses'] ?? []) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    },
                    {
                        label: 'Fees Realized',
                        data: {!! json_encode($financialTrendChart['fees'] ?? []) !!},
                        backgroundColor: '#7c32ff',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Fee Distribution Chart
    const ctxFee = document.getElementById('chart-fee-distribution');
    if (ctxFee) {
        new Chart(ctxFee, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($feeDistributionChart['labels'] ?? []) !!},
                datasets: [{
                    data: {!! json_encode($feeDistributionChart['data'] ?? []) !!},
                    backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    // 3. Admissions Funnel Chart
    const ctxAdm = document.getElementById('chart-admissions-funnel');
    if (ctxAdm) {
        new Chart(ctxAdm, {
            type: 'bar',
            data: {
                labels: {!! json_encode($admissionsChart['labels'] ?? []) !!},
                datasets: [{
                    label: 'Scholars',
                    data: {!! json_encode($admissionsChart['data'] ?? []) !!},
                    backgroundColor: ['#6366f1', '#10b981', '#f43f5e', '#eab308'],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // 4. Class Capacity Chart
    const ctxCap = document.getElementById('chart-class-capacity');
    if (ctxCap) {
        new Chart(ctxCap, {
            type: 'bar',
            data: {
                labels: {!! json_encode($classCapacityChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Enrolled',
                        data: {!! json_encode($classCapacityChart['enrolled'] ?? []) !!},
                        backgroundColor: '#0ea5e9',
                        borderRadius: 4,
                    },
                    {
                        label: 'Max Capacity',
                        data: {!! json_encode($classCapacityChart['capacity'] ?? []) !!},
                        backgroundColor: isDark ? '#334155' : '#cbd5e1',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endif
@endsection
