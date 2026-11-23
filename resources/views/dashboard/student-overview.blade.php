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

