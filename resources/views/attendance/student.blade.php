@extends('layouts.app')

@section('title', 'My Attendance Log' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="space-y-3">

    <!-- COMPACT, PROFESSIONAL ATTENDANCE HEADER -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        📋 Personal Attendance Record
                    </span>
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-bold">
                        🔥 Aim, Ascend, Achieve
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">
                        Min Required: {{ config('attendance.minimum_percentage') }}%
                    </span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
                    My Attendance &amp; Session Log
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Track your presence across all academic classes, daily sessions, and earned discipline XP.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                <div class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-center">
                    <span class="text-[9px] uppercase font-bold text-slate-400 block tracking-wider">Attendance Rate</span>
                    <span class="text-base font-black font-mono {{ $attendancePercentage >= config('attendance.warning_percentage') ? 'text-emerald-600 dark:text-emerald-400' : ($attendancePercentage >= config('attendance.minimum_percentage') ? 'text-amber-500' : 'text-rose-500') }}">
                        @if($totalSessions > 0)
                            {{ $attendancePercentage }}%
                        @else
                            No data yet
                        @endif
                    </span>
                </div>
                <a href="{{ route('leaves.index') }}" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1">
                    <span>📝</span>
                    <span>Apply for Leave</span>
                </a>
            </div>
        </div>

        <!-- Embedded Slim Progress Bar (1.5px) -->
        <div class="w-full bg-slate-100 dark:bg-slate-800 h-[1.5px] rounded-full overflow-hidden mt-1">
            <div class="h-full {{ $attendancePercentage >= config('attendance.minimum_percentage') ? 'bg-emerald-500' : 'bg-rose-500' }}" style="width: {{ min(100, $attendancePercentage) }}%;"></div>
        </div>
    </div>

    @if($totalSessions > 0 && $attendancePercentage < config('attendance.minimum_percentage'))
    <!-- Defaulter Warning Banner (Compact) -->
    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 rounded-lg p-2.5">
        <div class="flex items-start space-x-2.5">
            <span class="text-lg flex-shrink-0">⚠️</span>
            <div class="flex-1 text-xs">
                <h4 class="font-bold text-rose-900 dark:text-rose-300 uppercase tracking-wide text-[11px]">
                    Statutory Attendance Defaulter Alert (&lt; {{ config('attendance.minimum_percentage') }}%)
                </h4>
                <p class="text-rose-700 dark:text-rose-400 text-[11px] mt-0.5 leading-relaxed">
                    Cumulative attendance is <strong>{{ $attendancePercentage }}%</strong>, below the mandatory {{ config('attendance.minimum_percentage') }}% threshold. Risk of examination debarment.
                </p>
                <div class="mt-1.5">
                    <a href="{{ route('leaves.index') }}" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold transition">
                        <span>📝 Submit Leave Justification</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Compact Metric Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Sessions</span>
            <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono">{{ $totalSessions }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400">Campus Sessions</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Present</span>
            <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">{{ $presentCount }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400">+15 XP / session</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Absences</span>
            <div class="text-lg font-black text-rose-600 dark:text-rose-400 mt-0.5 font-mono">{{ $absentCount }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400">Logged records</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Discipline XP</span>
            <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono">+{{ $presentCount * 15 }}</div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400">Presence reward</div>
        </div>
    </div>

    <!-- Attendance Log List (Compact & Utilitarian) -->
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">Session History</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Chronological class attendance history</p>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                Page {{ $records->currentPage() }} of {{ $records->lastPage() }}
            </span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($records as $rec)
            <div class="p-2.5 hover:bg-slate-50 dark:hover:bg-slate-850/60 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="space-y-0.5">
                    <div class="flex items-center space-x-1.5 text-[11px]">
                        <span class="px-1.5 py-0.2 rounded font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                            {{ $rec->session->academicClass->name ?? 'Campus Class' }}
                        </span>
                        <span class="text-slate-400">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">
                            {{ $rec->session->session_date->format('D, M d, Y') }}
                        </span>
                    </div>

                    <h4 class="font-bold text-slate-900 dark:text-white text-xs">
                        {{ $rec->session->title }}
                    </h4>

                    @if($rec->session->notes)
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $rec->session->notes }}</p>
                    @endif

                    <div class="text-[10px] text-slate-400">
                        Instructor: <strong class="text-slate-700 dark:text-slate-300">{{ $rec->session->teacher->name ?? 'Faculty' }}</strong>
                    </div>
                </div>

                <div class="flex items-center space-x-2 self-end sm:self-center shrink-0">
                    @if($rec->status === 'present')
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-1">
                            <span>✅</span>
                            <span>Present</span>
                        </span>
                    @elseif($rec->status === 'late')
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center space-x-1">
                            <span>⏱️</span>
                            <span>Late</span>
                        </span>
                    @elseif($rec->status === 'excused')
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 flex items-center space-x-1">
                            <span>📝</span>
                            <span>Excused</span>
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800 flex items-center space-x-1">
                            <span>❌</span>
                            <span>Absent</span>
                        </span>
                    @endif

                    @if(in_array($rec->status, ['present', 'late']))
                        <span class="text-[10px] font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800">
                            +15 XP
                        </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-xs">
                <p>No attendance records logged for your profile yet.</p>
            </div>
            @endforelse
        </div>

        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $records->links() }}
        </div>
    </div>

</div>
@endsection
