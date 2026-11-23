@extends('layouts.app')

@section('title', 'Daily Class Attendance - School Management System')

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Academics</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Daily Attendance</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-check-box"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Daily Class Attendance
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Attendance
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Monitor live scholar attendance rates, review classroom roll-calls, and audit absentee logs across divisions.
            </p>
        </div>

        <form method="GET" action="{{ route('school.attendance.index') }}" class="flex items-center space-x-2">
            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500">
            <button type="submit" class="primary-btn" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                <span>Go</span>
            </button>
        </form>
    </div>

    @if(session('error'))
    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs font-bold text-rose-800 dark:text-rose-200 flex items-start space-x-2 shadow-xs">
        <span class="text-base flex-shrink-0">⚠️</span>
        <div class="flex-1 leading-relaxed">{{ session('error') }}</div>
    </div>
    @endif

    <!-- Infix Attendance Metric Cards (.white-box.single-summery) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Present Rate</span>
            <div class="text-2xl font-black gradient-color2 mt-1.5 font-mono">{{ $overallRate }}%</div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2">
                <div class="h-1.5 rounded-full" style="width: {{ $overallRate }}%; background: var(--system_primary_gradient);"></div>
            </div>
        </div>

        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Present</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">
                {{ $totalPresent }} <span class="text-xs font-normal text-slate-400">/ {{ $totalStudents }}</span>
            </div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Active scholars marked</p>
        </div>

        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Absent</span>
            <div class="text-2xl font-black text-rose-500 mt-1.5 font-mono">{{ $totalAbsent }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Without validated leave</p>
        </div>

        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Late / Half Day</span>
            <div class="text-2xl font-black text-amber-500 mt-1.5 font-mono">{{ $totalLate }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Tardy logs recorded</p>
        </div>
    </div>

    <!-- Classes Roll Call Grid -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Classrooms Roll Call Status</h2>
            <span class="text-xs text-slate-500 font-medium">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3.5">Class &amp; Section</th>
                        <th class="p-3.5">Class Teacher</th>
                        <th class="p-3.5">Enrolled</th>
                        <th class="p-3.5">Present</th>
                        <th class="p-3.5">Absent</th>
                        <th class="p-3.5">Present %</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($classes as $c)
                    @php $st = $classStatus[$c->id] ?? null; @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                            {{ $c->display_name }}
                            <span class="text-[10px] text-slate-400 block">{{ $c->room_number ?: 'Room not assigned' }}</span>
                        </td>
                        <td class="p-3.5">
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ $c->classTeacher ? $c->classTeacher->name : 'Unassigned' }}</span>
                        </td>
                        <td class="p-3.5 font-bold text-slate-800 dark:text-slate-200">
                            {{ $c->students->count() }}
                        </td>
                        <td class="p-3.5 font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $st ? $st['present'] : 0 }}
                        </td>
                        <td class="p-3.5 font-bold text-rose-500">
                            {{ $st ? $st['absent'] : 0 }}
                        </td>
                        <td class="p-3.5">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $st ? $st['rate'] : 0 }}%</span>
                                <div class="w-16 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $st ? $st['rate'] : 0 }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5">
                            @if($st && $st['status'] === 'marked')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Completed
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                Pending Roll Call
                            </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-right space-x-1.5">
                            <a href="{{ route('school.attendance.take', ['classId' => $c->id, 'date' => $date]) }}" 
                               class="px-3 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs inline-block transition shadow-xs">
                                {{ $st && $st['status'] === 'marked' ? 'Edit Roll Call' : 'Take Attendance' }}
                            </a>
                            <a href="{{ route('school.attendance.register', $c->id) }}" 
                               class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs inline-block transition">
                                Register
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-12 text-center">
                            @if(Auth::user()->isTeacher())
                                <div class="max-w-md mx-auto space-y-2 py-4">
                                    <span class="text-4xl block mb-2">⏳</span>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Classes Assigned Yet</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                                        Admin will assign you the classes after a while, just wait. When assigned, only then your assigned classes will appear here for you to mark attendance.
                                    </p>
                                </div>
                            @else
                                <span class="text-slate-400 text-xs">No classes registered in the system.</span>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
