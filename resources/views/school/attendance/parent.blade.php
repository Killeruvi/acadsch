@extends('layouts.app')

@section('title', 'Ward Attendance Register - Parent Portal')

@section('content')
<div class="max-w-[1400px] mx-auto px-3 sm:px-4 lg:px-6 py-4 pb-24 sm:pb-8 space-y-3">

    <!-- Consistent Parent Navigation -->
    @include('school.parent._nav')

    @if($activeChild)
    <!-- Compact Active Child Profile Strip -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 shadow-xs flex flex-wrap items-center justify-between gap-2 text-xs">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center flex-shrink-0">
                {{ strtoupper(substr($activeChild->name, 0, 2)) }}
            </div>
            <div>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $activeChild->name }}</span>
                <span class="text-slate-500 dark:text-slate-400 block text-[11px]">
                    Adm: <strong class="text-slate-700 dark:text-slate-300">{{ $activeChild->admission_number ?: 'Not assigned' }}</strong> &bull;
                    Class: <strong class="text-slate-700 dark:text-slate-300">{{ $activeChild->currentClass?->display_name ?? 'Not Enrolled' }}</strong> &bull;
                    Roll: <strong class="text-slate-700 dark:text-slate-300">{{ $activeChild->roll_number ?: 'Not assigned' }}</strong>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <div class="text-right mr-1">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Attendance</span>
                <span class="font-black text-sm {{ $attendancePercentage >= config('attendance.warning_percentage') ? 'text-emerald-600' : ($attendancePercentage >= config('attendance.minimum_percentage') ? 'text-amber-600' : 'text-rose-600') }}">
                    {{ $totalSessions > 0 ? $attendancePercentage . '%' : 'No data yet' }}
                </span>
            </div>
            <a href="{{ route('leaves.index', ['tab' => 'apply', 'student_id' => $activeChild->id]) }}" 
               class="px-2.5 py-1 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center space-x-1 transition">
                <span>📝</span>
                <span>Apply Leave</span>
            </a>
        </div>
    </div>

    @if($attendancePercentage < config('attendance.minimum_percentage') && $totalSessions > 0)
    <!-- Compact Defaulter Notice -->
    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl p-3 text-xs flex items-center justify-between gap-3 shadow-2xs">
        <div class="flex items-center space-x-2">
            <span class="text-base">⚠️</span>
            <span class="text-rose-800 dark:text-rose-300">
                <strong>Attendance Warning:</strong> Current attendance is <strong>{{ $attendancePercentage }}%</strong> (minimum threshold: {{ config('attendance.minimum_percentage') }}%).
            </span>
        </div>
        <a href="{{ route('leaves.index', ['tab' => 'apply', 'student_id' => $activeChild->id]) }}" 
           class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] whitespace-nowrap shadow-2xs">
            Submit Justification &rarr;
        </a>
    </div>
    @endif

    <!-- Compact Metrics Row (Flat, Dense) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Class Days</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-0.5">{{ $totalSessions }}</div>
            <span class="text-[10px] text-slate-400">Official sessions</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Days Present</span>
            <div class="text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $presentCount }}</div>
            <span class="text-[10px] text-emerald-600 font-medium">On-campus</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block">Days Absent</span>
            <div class="text-base font-black text-rose-500 mt-0.5">{{ $absentCount }}</div>
            <span class="text-[10px] text-rose-500 font-medium">Unexcused</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-amber-500 uppercase tracking-wider block">Late / Excused</span>
            <div class="text-base font-black text-amber-500 mt-0.5">{{ $lateCount }}</div>
            <span class="text-[10px] text-amber-500 font-medium">Authorized</span>
        </div>
    </div>

    <!-- Attendance Log Table (Compact, Dense) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Daily Attendance Roll-Call Register</h2>
                <p class="text-[10px] text-slate-500">Official chronological morning attendance log recorded by faculty</p>
            </div>
            @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->total() > 0)
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ $records->total() }} records
            </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-2.5">Date</th>
                        <th class="p-2.5">Class / Section</th>
                        <th class="p-2.5">Session / Event</th>
                        <th class="p-2.5">Marked By</th>
                        <th class="p-2.5">Status</th>
                        <th class="p-2.5">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($records as $rec)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-2.5 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            {{ $rec->session ? \Carbon\Carbon::parse($rec->session->session_date)->format('D, d M Y') : \Carbon\Carbon::parse($rec->created_at)->format('D, d M Y') }}
                        </td>
                        <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200">
                            {{ $rec->session && $rec->session->academicClass ? $rec->session->academicClass->display_name : ($activeChild->currentClass ? $activeChild->currentClass->display_name : 'Class') }}
                        </td>
                        <td class="p-2.5 text-slate-600 dark:text-slate-400">
                            {{ $rec->session ? $rec->session->title : 'Daily Roll Call' }}
                        </td>
                        <td class="p-2.5 font-medium text-slate-700 dark:text-slate-300">
                            {{ $rec->session && $rec->session->teacher ? $rec->session->teacher->name : 'Class Teacher' }}
                        </td>
                        <td class="p-2.5">
                            @if($rec->status === 'present')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 inline-flex items-center space-x-1">
                                    <span>✓</span>
                                    <span>Present</span>
                                </span>
                            @elseif($rec->status === 'absent')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 inline-flex items-center space-x-1">
                                    <span>✕</span>
                                    <span>Absent</span>
                                </span>
                            @elseif($rec->status === 'late')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 inline-flex items-center space-x-1">
                                    <span>⏱</span>
                                    <span>Late</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 inline-flex items-center space-x-1">
                                    <span>📋</span>
                                    <span>{{ ucfirst($rec->status) }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="p-2.5 text-slate-500 italic text-[11px]">
                            {{ $rec->remarks ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            No attendance records on file for {{ $activeChild->name }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
        <div class="p-2.5 border-t border-slate-100 dark:border-slate-800">
            {{ $records->links() }}
        </div>
        @endif
    </div>

    @else
    <div class="p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800">
        <span class="text-3xl block mb-2">👨‍👩‍👧</span>
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">No Enrolled Children Found</h3>
        <p class="text-xs text-slate-500 mt-1">Please contact school administration to link your student's admission record.</p>
    </div>
    @endif

</div>
@endsection
