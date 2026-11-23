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
    