@extends('layouts.app')

@section('title', 'Weekly Schedule' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
@php
    $today = now();
    $rollingDays = [];
    for ($i = 0; $i < 3; $i++) {
        $d = $today->copy()->addDays($i);
        $rollingDays[] = [
            'dayName' => $d->format('l'),
            'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $d->format('l')),
            'dateFormatted' => $d->format('D, M d'),
        ];
    }
    $currentDayName = $rollingDays[0]['dayName'];
@endphp

{{-- Offline Schedule Data Cache Script --}}
<script id="offline-schedule-data" type="application/json">
{!! json_encode($offlinePayload ?? []) !!}
</script>
<script>
    (function() {
        try {
            const dataEl = document.getElementById('offline-schedule-data');
            if (dataEl && dataEl.textContent) {
                const payload = JSON.parse(dataEl.textContent);
                if (Array.isArray(payload) && payload.length > 0) {
                    localStorage.setItem('school_offline_schedule_cache', JSON.stringify(payload));
                }
            }
        } catch(e) {
            console.warn('Schedule offline caching notice:', e);
        }
    })();
</script>

<div x-data="{ 
    openScheduleModal: false, 
    activeView: 'day', 
    selectedDay: '{{ $currentDayName }}',
    init() {
        try {
            const dataEl = document.getElementById('offline-schedule-data');
            if (dataEl && dataEl.textContent) {
                const payload = JSON.parse(dataEl.textContent);
                if (Array.isArray(payload) && payload.length > 0) {
                    localStorage.setItem('school_offline_schedule_cache', JSON.stringify(payload));
                }
            }
        } catch(e) {
            console.warn('Schedule offline caching notice:', e);
        }
    }
}" class="max-w-6xl mx-auto space-y-5">

    <!-- UPCOMING HOLIDAYS BANNER (COMPACT) -->
    @if(!empty($holidays) && $holidays->isNotEmpty())
    <div class="p-3 sm:p-3.5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-rose-500/5 to-amber-500/5 border border-amber-500/25 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <span class="text-xl flex-shrink-0">🌴</span>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30">Upcoming Holidays</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Instructional classes suspended</span>
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs">
                    @foreach($holidays->take(4) as $h)
                    <span class="font-bold text-slate-800 dark:text-white flex items-center space-x-1">
                        <span>{{ $h->badge_icon ?? '🎉' }}</span>
                        <span>{{ $h->name }}</span>
                        <span class="text-[10px] font-mono text-slate-500">({{ \Carbon\Carbon::parse($h->holiday_date)->format('M d') }}@if($h->end_date && $h->end_date !== $h->holiday_date) - {{ \Carbon\Carbon::parse($h->end_date)->format('M d') }}@endif)</span>
                    </span>
                    @if(!$loop->last) <span class="text-slate-300 dark:text-slate-700">&bull;</span> @endif
                    @endforeach
                </div>
            </div>
        </div>

    </div>
    @endif

    <!-- COMPACT, CLEAN HEADER -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs transition-colors">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        Academic Calendar
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 Aim, Ascend, Achieve
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                    Weekly Schedule &amp; Academic Timetable
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Offline physical classroom sessions &amp; online lectures with timings, rooms, and faculty instructors.
                </p>
            </div>

            <!-- Controls: Delivery Mode Filter + View Switcher + Add Slot -->
            <div class="flex flex-wrap items-center gap-2">
                
                <!-- DELIVERY MODE FILTER (ALL | OFFLINE | ONLINE) -->
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700 text-xs">
                    <a href="{{ route('schedules.index', array_merge(request()->except(['mode']))) }}" 
                       class="px-2.5 py-1 rounded-lg transition {{ empty($modeFilter) || $modeFilter === 'all' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium' }}">
                        All Classes
                    </a>
                    <a href="{{ route('schedules.index', array_merge(request()->except(['mode']), ['mode' => 'offline'])) }}" 
                       class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1 {{ ($modeFilter ?? '') === 'offline' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium' }}">
                        <span>🏫</span>
                        <span>Offline</span>
                    </a>
                    <a href="{{ route('schedules.index', array_merge(request()->except(['mode']), ['mode' => 'online'])) }}" 
                       class="px-2.5 py-1 rounded-lg transition flex items-center space-x-1 {{ ($modeFilter ?? '') === 'online' ? 'bg-white dark:bg-slate-700 text-cyan-600 dark:text-cyan-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium' }}">
                        <span>🌐</span>
                        <span>Online</span>
                    </a>
                </div>

                <!-- View Switcher Tabs: Day View (3 Days Rolling) vs Full Week Table -->
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700 text-xs">
                    <button type="button" 
                            @click="activeView = 'day'" 
                            :class="activeView === 'day' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1">
                        <span>📅</span>
                        <span>3-Day View</span>
                    </button>
                    <button type="button" 
                            @click="activeView = 'table'" 
                            :class="activeView === 'table' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1">
                        <span>📊</span>
                        <span>Full Week Table</span>
                    </button>
                </div>

                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <button @click.stop="openScheduleModal = true" 
                        class="inline-flex items-center space-x-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    <span>+</span>
                    <span>Add Slot</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- 1. ROLLING 3-DAYS DYNAMIC VIEW (TODAY, TOMORROW, NEXT DAY) -->
    <div x-show="activeView === 'day'" class="space-y-4">
        <!-- Day Switcher Horizontal Pills (Only 3 Rolling Days) -->
        <div class="flex items-center space-x-2 overflow-x-auto p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs">
            @foreach($rollingDays as $rd)
            @php
                $dayName = $rd['dayName'];
                $dayCount = ($schedulesByDay[$dayName] ?? collect())->count();
                $isToday = ($rd['label'] === 'Today');
            @endphp
            <button type="button"
                    @click="selectedDay = '{{ $dayName }}'"
                    :class="selectedDay === '{{ $dayName }}' ? 'bg-emerald-600 text-white shadow-xs font-extrabold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center space-x-2 whitespace-nowrap">
                <span>{{ $rd['label'] }}</span>
                <span class="text-xs opacity-80 font-normal">({{ $rd['dateFormatted'] }})</span>
                @if($isToday)
                <span class="px-1.5 py-0.5 rounded-full text-xs uppercase font-extrabold" :class="selectedDay === '{{ $dayName }}' ? 'bg-white/20 text-white' : 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300'">
                    Today 🔥
                </span>
                @endif
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="selectedDay === '{{ $dayName }}' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'">
                    {{ $dayCount }}
                </span>
            </button>
            @endforeach
        </div>

        <!-- Schedule Items for Selected Rolling Day -->
        @foreach($rollingDays as $rd)
        @php
            $day = $rd['dayName'];
            $daySessions = $schedulesByDay[$day] ?? collect();
        @endphp
        <div x-show="selectedDay === '{{ $day }}'" class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 flex items-center gap-2">
                    <span>{{ $rd['label'] }} ({{ $day }}) Sessions</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold">{{ $daySessions->count() }}</span>
                </h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $rd['dateFormatted'] }} &bull; Chronological order
                </span>
            </div>

            @if($daySessions->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center shadow-xs">
                <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-lg mb-2">
                    🧘
                </div>
                <h3 class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 mb-0.5">No Classes Scheduled on {{ $rd['label'] }} ({{ $day }})</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500">Dedicated self-study, DPP practice, and revision day.</p>
            </div>
            @else
            <div class="space-y-2.5">
                @foreach($daySessions as $idx => $sch)
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition shadow-2xs hover:shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start space-x-3.5">
                        <!-- Period badge -->
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-[10px] uppercase font-bold text-emerald-500 leading-none">Period</span>
                            <span class="text-base font-black leading-tight">{{ $idx + 1 }}</span>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if($sch->course)
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $sch->course->code }}
                                </span>
                                @endif

                                @if($sch->isOffline())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                    🏫 Offline Classroom
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-cyan-100 dark:bg-cyan-950/80 text-cyan-800 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800">
                                    🌐 Online Class
                                </span>
                                @endif

                                @if($sch->academicClass)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                                    {{ $sch->academicClass->name }}
                                </span>
                                @endif

                                @if($sch->batch)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    {{ $sch->batch->name }}
                                </span>
                                @endif

                                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white {{ $sch->is_cancelled ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                    {{ $sch->course?->title ?? $sch->title }}
                                </h3>

                                @if($sch->is_cancelled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                                    ⛔ Cancelled: {{ $sch->cancellation_reason ?? 'By Faculty Notice' }}
                                </span>
                                @endif
                            </div>

                            <div class="text-xs text-slate-600 dark:text-slate-300 mt-1 font-medium">
                                {{ $sch->title }}
                            </div>

                            <div class="flex items-center space-x-3 text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                                <span>👤 {{ $sch->course?->teacher?->name ?? 'Faculty Instructor' }}</span>
                                <span>&bull;</span>
                                <span>📍 {{ $sch->isOffline() ? ($sch->room ?? 'Campus Classroom TBA') : ($sch->room ?? 'Online Room') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Time & Action Pill -->
                    <div class="flex items-center space-x-2.5 flex-shrink-0 self-end sm:self-center">
                        <div class="text-right">
                            <span class="text-sm font-black text-slate-900 dark:text-white block">
                                {{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }}
                            </span>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                                60 mins lecture
                            </span>
                        </div>
                        @if($sch->course_id)
                        <a href="{{ route('courses.show', $sch->course_id) }}" 
                           class="px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:border-emerald-500 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition">
                            Details &rarr;
                        </a>
                        @endif

                        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <form method="POST" action="{{ route('schedules.toggle-cancel', $sch->id) }}" onsubmit="return confirm('{{ $sch->is_cancelled ? 'Restore this class session?' : 'Mark this class session as cancelled?' }}')">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $sch->is_cancelled ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-300' }}">
                                {{ $sch->is_cancelled ? '✓ Restore' : '✕ Cancel' }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- 2. FULL WEEK CLEAN TABLE (FOR COMPREHENSIVE OVERVIEW & TEST COMPATIBILITY) -->
    <div x-show="activeView === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden transition-colors">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    Full Week Matrix Table
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">All periods and days displayed in a simple table.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        <th class="p-3 w-28 border-r border-slate-200 dark:border-slate-800">Day</th>
                        <th class="p-3 border-r border-slate-200 dark:border-slate-800">Period 1 (08:30 - 09:30)</th>
                        <th class="p-3 border-r border-slate-200 dark:border-slate-800">Period 2 (09:30 - 10:30)</th>
                        <th class="p-3 border-r border-slate-200 dark:border-slate-800">Period 3 (10:45 - 11:45)</th>
                        <th class="p-3 border-r border-slate-200 dark:border-slate-800">Period 4 (11:45 - 12:45)</th>
                        <th class="p-3">Period 5 (01:30 - 02:30)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                    @php
                        $daySessions = $schedulesByDay[$day] ?? collect();
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-bold bg-slate-50/80 dark:bg-slate-800/50 text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800 align-top">
                            <div>{{ $day }}</div>
                            <span class="text-xs font-medium text-slate-400">
                                {{ $daySessions->count() }} {{ Str::plural('Class', (int)$daySessions->count()) }}
                            </span>
                        </td>

                        @if($daySessions->isEmpty())
                        <td colspan="5" class="p-3 text-center text-slate-400 dark:text-slate-500 italic text-xs">
                            No scheduled academic sessions
                        </td>
                        @else
                            @for($col = 0; $col < 5; $col++)
                            @php
                                $sch = $daySessions->get($col);
                            @endphp
                            <td class="p-2 border-r border-slate-100 dark:border-slate-800 align-top min-w-[140px]">
                                @if($sch)
                                <div class="p-2 rounded-lg border {{ $sch->is_cancelled ? 'border-rose-200 dark:border-rose-900 bg-rose-50/40 dark:bg-rose-950/20' : 'border-slate-200 dark:border-slate-700 bg-emerald-50/40 dark:bg-emerald-950/20' }} hover:shadow-xs transition space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-extrabold px-1.5 py-0.2 rounded {{ $sch->is_cancelled ? 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200' : 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' }}">
                                            {{ $sch->course?->code ?? 'CLASS' }}
                                        </span>
                                        <span class="text-slate-500 dark:text-slate-400 font-semibold">
                                            {{ substr($sch->start_time, 0, 5) }}
                                        </span>
                                    </div>
                                    <a href="{{ $sch->course_id ? route('courses.show', $sch->course_id) : '#' }}" class="font-bold {{ $sch->is_cancelled ? 'line-through text-slate-400' : 'text-slate-900 dark:text-white hover:text-emerald-600' }} block line-clamp-1 leading-tight text-xs">
                                        {{ $sch->course?->title ?? $sch->title }}
                                    </a>
                                    @if($sch->is_cancelled)
                                    <span class="text-[9px] font-black uppercase text-rose-600 dark:text-rose-400 block">
                                        ⛔ Cancelled
                                    </span>
                                    @endif
                                    <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                        <span>📍 {{ $sch->isOffline() ? ($sch->room ?? 'Room TBA') : 'Online' }}</span>
                                        <span class="text-[10px] font-bold {{ $sch->isOffline() ? 'text-emerald-600' : 'text-cyan-500' }}">
                                            {{ $sch->isOffline() ? 'Offline' : 'Online' }}
                                        </span>
                                    </div>
                                </div>
                                @else
                                <div class="h-10 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-center text-xs text-slate-300 dark:text-slate-700">
                                    -
                                </div>
                                @endif
                            </td>
                            @endfor
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. ALL WEEKS ACADEMIC DELIVERABLES (ASSIGNMENTS, HOMEWORK & TESTS) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs transition-colors space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                    <span>📋</span>
                    <span>All Weeks Academic Deliverables &amp; Milestones</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Synchronized deliverables across all weeks: assignments, chapter homework, and mock tests</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('assignments.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">Assignments Hub &rarr;</a>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <a href="{{ route('homework.index') }}" class="text-xs font-bold text-amber-600 hover:underline">Homework Hub &rarr;</a>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <a href="{{ route('dpp.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">DPPs &amp; Tests Hub &rarr;</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- 1. Assignments Column -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                        <span>📝</span> <span>Course Deliverables</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                        {{ $upcomingAssignments->count() }} Active
                    </span>
                </div>
                @forelse($upcomingAssignments as $asgn)
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 hover:border-emerald-500/50 transition shadow-2xs space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-mono font-bold px-1.5 py-0.2 rounded bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200/80">
                            {{ $asgn->course?->code ?? 'GENERAL' }}
                        </span>
                        <span class="text-[10px] font-bold text-emerald-600">Max {{ $asgn->max_points }} pts</span>
                    </div>
                    <a href="{{ route('assignments.show', $asgn->id) }}" class="text-xs font-extrabold text-slate-900 dark:text-white hover:text-emerald-600 block truncate">
                        {{ $asgn->title }}
                    </a>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span>Due: {{ \Carbon\Carbon::parse($asgn->due_date)->format('M d, H:i') }}</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $asgn->assigned_to_label }}</span>
                    </div>
                </div>
                @empty
                <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                    No pending deliverables
                </div>
                @endforelse
            </div>

            <!-- 2. Homework Tasks Column -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                        <span>📋</span> <span>Chapter Homework</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                        {{ $upcomingHomework->count() }} Tasks
                    </span>
                </div>
                @forelse($upcomingHomework as $hw)
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 hover:border-amber-500/50 transition shadow-2xs space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-700 dark:text-slate-300">
                            {{ $hw->subject?->name ?? 'Academics' }}
                        </span>
                        <span class="text-[10px] font-bold text-amber-600">Max {{ $hw->max_marks }}m</span>
                    </div>
                    <a href="{{ route('homework.index') }}" class="text-xs font-extrabold text-slate-900 dark:text-white hover:text-amber-600 block truncate">
                        {{ $hw->title }}
                    </a>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span>Due: {{ \Carbon\Carbon::parse($hw->due_date)->format('M d') }}</span>
                        @if($hw->attachment_path)
                        <span class="text-amber-600 font-semibold">📎 Attached Handout</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                    No pending homework tasks
                </div>
                @endforelse
            </div>

            <!-- 3. Tests & Assessments Column -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                        <span>🧪</span> <span>Chapter Mock Tests</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300">
                        {{ $upcomingTests->count() }} Tests
                    </span>
                </div>
                @forelse($upcomingTests as $test)
                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 hover:border-indigo-500/50 transition shadow-2xs space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ $test->dpp_number }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-500">⏱️ {{ $test->duration_minutes }}m</span>
                    </div>
                    <a href="{{ route('dpp.show', $test->id) }}" class="text-xs font-extrabold text-slate-900 dark:text-white hover:text-indigo-600 block truncate">
                        {{ $test->title }}
                    </a>
                    <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span>{{ $test->total_marks }} Marks</span>
                        <span class="text-indigo-600 font-bold">Start Test &rarr;</span>
                    </div>
                </div>
                @empty
                <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400">
                    No upcoming tests scheduled
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- MODAL: ADD TIMETABLE SLOT (TEACHERS & ADMINS) -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="openScheduleModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="openScheduleModal = false">
        
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative"
             @click.away="openScheduleModal = false">
            
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                    <span>📅</span>
                    <span>Schedule Class Session (Offline / Online)</span>
                </h3>
                <button type="button" @click="openScheduleModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form action="{{ route('schedules.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Delivery Mode: Offline Classroom vs Online Live -->
                <div>
                    <label for="delivery_mode" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                        Class Mode / Delivery Type
                    </label>
                    <select id="delivery_mode" name="delivery_mode" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                        <option value="offline" selected>🏫 Offline Classroom Session (In-Person / Physical)</option>
                        <option value="online">🌐 Online Live Stream (Digital Classroom)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="course_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Course / Subject</label>
                        <select id="course_id" name="course_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            @foreach(($allCourses ?? $courses ?? []) as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Target Class (Optional)</label>
                        <select id="class_id" name="class_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            <option value="">-- General / Multi-Class --</option>
                            @foreach(($allClasses ?? []) as $cl)
                                <option value="{{ $cl->id }}">{{ $cl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="schedule_title" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Session Topic</label>
                    <input type="text" id="schedule_title" name="title" required placeholder="e.g. Chapter 1: Proofs & Applications" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="day_of_week" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Day</label>
                        <select id="day_of_week" name="day_of_week" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="schedule_room" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Room / Location</label>
                        <input type="text" id="schedule_room" name="room" placeholder="e.g. Room 302, Hall A" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="start_time" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required value="08:30" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label for="end_time" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">End Time</label>
                        <input type="time" id="end_time" name="end_time" required value="09:30" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end space-x-2">
                    <button type="button" @click="openScheduleModal = false" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
