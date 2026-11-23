@extends('layouts.app')

@section('title', 'Parent Portal — Ward Academic Dashboard')

@section('content')
<div class="max-w-[1400px] mx-auto px-3 sm:px-4 lg:px-6 py-4 pb-24 sm:pb-8 space-y-4" 
     x-data="{ 
         activeTab: new URLSearchParams(window.location.search).get('tab') || 'overview',
         leaveSubTab: 'view',
         setTab(tab) {
             this.activeTab = tab;
             const url = new URL(window.location);
             url.searchParams.set('tab', tab);
             window.history.replaceState({}, '', url);
             window.dispatchEvent(new CustomEvent('parent-tab-changed', { detail: { tab: tab } }));
         }
     }"
     x-init="window.__setParentTab = (tab) => setTab(tab);"
     @parent-tab-changed.window="activeTab = $event.detail.tab">

    <!-- Top Navigation with Large Touch Targets & Multi-Student Selector -->
    @include('school.parent._nav')

    @if($activeChild)
    <!-- Compact Student Header Strip (No Oversized Banners) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white font-black text-sm flex items-center justify-center flex-shrink-0 shadow-xs">
                {{ strtoupper(substr($activeChild->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">{{ $activeChild->name }}</h1>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]">
                        Active Enrolled
                    </span>
                </div>
                <div class="text-slate-500 dark:text-slate-400 text-xs mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span>Adm: <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $activeChild->admission_number ?: 'Not assigned' }}</strong></span>
                    <span>&bull;</span>
                    <span>Class: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $activeChild->currentClass?->display_name ?? 'Class Not Assigned' }}</strong></span>
                    <span>&bull;</span>
                    <span>Roll: <strong class="text-slate-800 dark:text-slate-200">#{{ $activeChild->roll_number ?: 'Not assigned' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <span class="text-slate-500 text-xs hidden sm:inline">Class Teacher:</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg">
                👨‍🏫 {{ $activeChild->currentClass?->classTeacher?->name ?? 'Not assigned' }}
            </span>
            <button type="button" @click="setTab('leaves'); leaveSubTab = 'apply'" 
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs inline-flex items-center space-x-1.5 transition shadow-xs">
                <span>📝</span>
                <span>Apply Leave</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: OVERVIEW DASHBOARD                  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'overview'" class="space-y-4">
        <!-- Compact Key Metrics Summary (Dense & Flat) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Metric 1: Today's Roll Call -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold uppercase tracking-wider">
                    <span>Today's Status</span>
                    <button type="button" @click="setTab('attendance')" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Log &rarr;</button>
                </div>
                <div class="flex items-center space-x-2 mt-1.5">
                    @if($todayAttendance === 'present')
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">Present</span>
                    @elseif($todayAttendance === 'absent')
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <span class="text-sm font-black text-rose-500">Absent</span>
                    @elseif($todayAttendance === 'late')
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-sm font-black text-amber-500">Late</span>
                    @elseif($todayAttendance === 'excused')
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-sm font-black text-blue-600 dark:text-blue-400">Excused (Approved Leave)</span>
                    @else
                        <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                        <span class="text-sm font-bold text-slate-500">Pending Call</span>
                    @endif
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block truncate">{{ now()->format('l, d M Y') }}</span>
            </div>

            <!-- Metric 2: Attendance Rate -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold uppercase tracking-wider">
                    <span>Attendance Rate</span>
                    <span class="text-[10px] font-bold {{ $attendanceStats['percentage'] === null ? 'text-slate-400' : ($attendanceStats['percentage'] >= 75 ? 'text-emerald-500' : 'text-rose-500') }}">
                        {{ $attendanceStats['total'] > 0 ? $attendanceStats['present'].'/'.$attendanceStats['total'].' Days' : 'No records' }}
                    </span>
                </div>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-1">
                    {{ $attendanceStats['percentage'] !== null ? $attendanceStats['percentage'].'%' : 'No data yet' }}
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2">
                    <div class="h-1.5 rounded-full {{ $attendanceStats['percentage'] === null ? 'bg-slate-300' : ($attendanceStats['percentage'] >= 75 ? 'bg-emerald-500' : 'bg-rose-500') }}" style="width: {{ min(100, $attendanceStats['percentage'] ?? 0) }}%"></div>
                </div>
            </div>

            <!-- Metric 3: Fee Status -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold uppercase tracking-wider">
                    <span>Tuition Dues</span>
                    <button type="button" @click="setTab('fees')" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Pay &rarr;</button>
                </div>
                <div class="text-lg font-black mt-1 {{ $feeStats['total_due'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    ₹{{ number_format($feeStats['total_due'], 0) }}
                </div>
                <span class="text-[10px] text-slate-400 block truncate mt-1">
                    {{ $feeStats['total_due'] > 0 ? $pendingFees->count() . ' invoice(s) pending' : 'Zero outstanding dues' }}
                </span>
            </div>

            <!-- Metric 4: Leave Status -->
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold uppercase tracking-wider">
                    <span>Leave Requests</span>
                    <button type="button" @click="setTab('leaves')" class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Apply &rarr;</button>
                </div>
                <div class="text-lg font-black text-slate-900 dark:text-white mt-1">
                    {{ $pendingLeavesCount }} Pending
                </div>
                <span class="text-[10px] text-slate-400 block truncate mt-1">
                    {{ $approvedLeavesCount }} approved on record
                </span>
            </div>
        </div>

        <!-- Routine & Roll-Call Columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Today's Routine Preview -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">🗓️</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Today's Routine ({{ now()->format('l') }})</h3>
                    </div>
                    <button type="button" @click="setTab('timetable')" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                        Weekly Routine &rarr;
                    </button>
                </div>
                <div class="space-y-2">
                    @forelse($todayTimetable as $slot)
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2.5">
                            <span class="px-2 py-1 rounded-md bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold font-mono">
                                P{{ $slot->period_number }}
                            </span>
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs block">{{ $slot->subject?->name ?? 'General Session' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium">{{ $slot->teacher?->name ?? 'Staff Assigned' }}</span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-6">No timetable periods scheduled for today.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Roll-Call Log -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">📋</span>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Attendance Logs</h3>
                    </div>
                    <button type="button" @click="setTab('attendance')" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                        Full Register &rarr;
                    </button>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($recentAttendance as $rec)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 block text-xs">
                                {{ $rec->session ? \Carbon\Carbon::parse($rec->session->session_date)->format('D, d M Y') : $rec->created_at->format('D, d M Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $rec->session?->title ?? 'Morning Attendance' }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase {{ $rec->status === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($rec->status === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300') }}">
                            {{ ucfirst($rec->status) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-6">No recent roll-call records on file.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Circulars Preview -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center space-x-2">
                    <span class="text-base">📢</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Official Institutional Circulars</h3>
                </div>
                <a href="{{ route('notices.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                    View All &rarr;
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($notices as $n)
                <div class="py-2.5">
                    <span class="text-[10px] font-bold text-slate-400">{{ \Carbon\Carbon::parse($n->published_at)->format('d M Y') }}</span>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs mt-0.5">{{ $n->title }}</h4>
                </div>
                @empty
                <p class="text-xs text-slate-400 text-center py-4">No published circulars at this time.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: ATTENDANCE REGISTER TAB             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'attendance'" x-cloak class="space-y-4">
        <!-- Attendance Stats Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
            <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Recorded</span>
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $attendanceStats['total'] }}</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Present</span>
                <span class="text-lg font-black text-emerald-600">{{ $attendanceStats['present'] }}</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block">Absent</span>
                <span class="text-lg font-black text-rose-500">{{ $attendanceStats['absent'] }}</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] font-bold text-amber-500 uppercase tracking-wider block">Late</span>
                <span class="text-lg font-black text-amber-500">{{ $attendanceStats['late'] }}</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 text-center col-span-2 sm:col-span-1">
                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block">Attendance Rate</span>
                <span class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ $attendanceStats['percentage'] !== null ? $attendanceStats['percentage'].'%' : 'No data yet' }}</span>
            </div>
        </div>

        <!-- Visual Attendance Distribution Graph -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex-1 space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Visual Roll-Call Breakdown</span>
                <h4 class="text-sm font-black text-slate-900 dark:text-white">Attendance Proportions for {{ $activeChild->name }}</h4>
                <p class="text-xs text-slate-500">Graphical analytics across verified daily roll calls and approved leave records.</p>
                <div class="pt-2 flex items-center space-x-3 text-xs font-bold">
                    <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> <span>{{ $attendanceStats['present'] }} Present</span></span>
                    <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> <span>{{ $attendanceStats['absent'] }} Absent</span></span>
                    <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> <span>{{ $attendanceStats['late'] }} Late</span></span>
                </div>
            </div>
            <div class="w-36 h-36 relative flex items-center justify-center flex-shrink-0">
                <canvas id="chart-parent-attendance"></canvas>
            </div>
        </div>

        <!-- Attendance Logs Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">📋 Roll-Call History for {{ $activeChild->name }}</h3>
                <span class="text-[11px] text-slate-500 font-medium">Class: {{ $activeChild->currentClass?->display_name ?? 'N/A' }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Date</th>
                            <th class="p-3">Session &amp; Class</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Marked By</th>
                            <th class="p-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($allAttendanceRecords as $rec)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ $rec->session ? \Carbon\Carbon::parse($rec->session->session_date)->format('D, d M Y') : $rec->created_at->format('D, d M Y') }}
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $rec->session?->title ?? 'Daily Roll Call' }}</span>
                                <span class="text-[10px] text-slate-400">{{ $rec->session?->academicClass?->display_name ?? ($activeChild->currentClass?->display_name ?? 'Class') }}</span>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $rec->status === 'present' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($rec->status === 'absent' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : ($rec->status === 'excused' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300')) }}">
                                    {{ ucfirst($rec->status) }}
                                </span>
                            </td>
                            <td class="p-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                {{ $rec->session?->teacher?->name ?? 'Class Teacher' }}
                            </td>
                            <td class="p-3 text-slate-500 italic">
                                {{ $rec->remarks ?? 'Regular roll call entry' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-xs text-slate-400">
                                No attendance records logged yet for {{ $activeChild->name }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: LEAVE MANAGEMENT PORTAL TAB         -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'leaves'" x-cloak class="space-y-4">
        <!-- Sub-Tabs: View Leaves | Apply Leave -->
        <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
            <button type="button" @click="leaveSubTab = 'view'"
                    :class="leaveSubTab === 'view' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm transition flex items-center space-x-1.5">
                <span>📋</span>
                <span>Leaves on File ({{ $allLeaves->count() }})</span>
            </button>
            <button type="button" @click="leaveSubTab = 'apply'"
                    :class="leaveSubTab === 'apply' ? 'bg-slate-900 text-white dark:bg-emerald-600 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm transition flex items-center space-x-1.5">
                <span>✍️</span>
                <span>Apply for Leave</span>
            </button>
        </div>

        <!-- Sub-Tab: View Leaves List -->
        <div x-show="leaveSubTab === 'view'" class="space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($allLeaves as $leave)
                <div class="p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $leave->type === 'medical' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : ($leave->type === 'family' ? 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300') }}">
                                {{ ucfirst($leave->type) }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $leave->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($leave->status === 'rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300') }}">
                                {{ ucfirst($leave->status) }}
                            </span>
                            <span class="text-slate-400 text-[11px]">Applied {{ $leave->created_at->format('d M Y') }}</span>
                        </div>
                        <p class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                            {{ \Carbon\Carbon::parse($leave->start_date)->format('D, d M Y') }} &rarr; {{ \Carbon\Carbon::parse($leave->end_date)->format('D, d M Y') }}
                        </p>
                        <p class="text-slate-600 dark:text-slate-400 text-xs">{{ $leave->reason }}</p>
                        @if($leave->review_remarks)
                        <div class="text-[11px] text-slate-500 italic">
                            Staff Remark: "{{ $leave->review_remarks }}" — by {{ $leave->reviewer?->name ?? 'Administration' }}
                        </div>
                        @endif
                    </div>

                    @if($leave->status === 'pending')
                    <form method="POST" action="{{ route('leaves.destroy', $leave->id) }}" onsubmit="return confirm('Cancel this pending leave application?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-200 dark:border-rose-800 transition">
                            Cancel Request
                        </button>
                    </form>
                    @endif
                </div>
                @empty
                <div class="p-8 text-center text-xs text-slate-400">
                    <span class="text-2xl block mb-1">📝</span>
                    No leave applications on file for {{ $activeChild->name }}.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sub-Tab: Apply Leave Form (On-Page, Shallow) -->
        <div x-show="leaveSubTab === 'apply'" x-cloak class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-xs">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">✍️ Submit Leave Application for {{ $activeChild->name }}</h3>
            <form method="POST" action="{{ route('leaves.store') }}" class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="student_id" value="{{ $activeChild->id }}">

                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs">
                    Applying on behalf of: <strong class="text-slate-900 dark:text-white">{{ $activeChild->name }}</strong>
                    <span class="text-slate-400 ml-1">({{ $activeChild->currentClass?->display_name ?? 'Class' }})</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Absence Category *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <label class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="type" value="medical" required class="text-emerald-600" checked>
                            <span class="font-bold">🏥 Medical</span>
                        </label>
                        <label class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="type" value="family" required class="text-emerald-600">
                            <span class="font-bold">👨‍👩‍👧 Family</span>
                        </label>
                        <label class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="type" value="academic" required class="text-emerald-600">
                            <span class="font-bold">📚 Academic</span>
                        </label>
                        <label class="border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 flex items-center space-x-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="type" value="other" required class="text-emerald-600">
                            <span class="font-bold">📌 Other</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                        <input type="date" name="start_date" required min="{{ now()->toDateString() }}" value="{{ old('start_date', now()->toDateString()) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Date *</label>
                        <input type="date" name="end_date" required min="{{ now()->toDateString() }}" value="{{ old('end_date', now()->toDateString()) }}"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason for Absence *</label>
                    <textarea name="reason" rows="3" required placeholder="Please provide specific reason for absence..."
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">{{ old('reason') }}</textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="leaveSubTab = 'view'" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition shadow-xs">
                        Submit Leave Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: REPORT CARDS & GRADES TAB           -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'exams'" x-cloak class="space-y-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">📜 Official Examination Report Cards</h3>
                    <p class="text-xs text-slate-500">Term-wise academic scores, percentile, grades, and ranks for {{ $activeChild->name }}.</p>
                </div>
                <a href="{{ route('school.exams.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                    Exam Datesheets &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs mt-2">
                @forelse($recentReportCards as $rc)
                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">{{ $rc->examTerm?->name ?? 'Term Examination' }}</h4>
                        <div class="text-slate-500 text-xs mt-1 flex flex-wrap items-center gap-x-3">
                            <span>Class: <strong class="text-slate-700 dark:text-slate-300">{{ $rc->academicClass?->display_name ?? 'Class' }}</strong></span>
                            <span>Marks: <strong class="text-slate-900 dark:text-white font-mono">{{ $rc->total_marks_obtained }} / {{ $rc->total_max_marks }}</strong></span>
                            <span>Percentage: <strong class="text-emerald-600 font-mono">{{ $rc->percentage }}%</strong></span>
                            <span>Grade: <strong class="text-indigo-600">{{ $rc->overall_grade }}</strong></span>
                            <span>Class Rank: <strong class="text-amber-600">#{{ $rc->class_rank ?? '-' }}</strong></span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('school.report-cards.show', $rc->id) }}" 
                           class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-2xs">
                            View Full Report Card
                        </a>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400 text-center py-8">No formal examination report cards published yet for this academic session.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 5: WEEKLY ROUTINE & TIMETABLE TAB      -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'timetable'" x-cloak class="space-y-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">🗓️ Weekly Class Routine &amp; Bell Schedule</h3>
                    <p class="text-xs text-slate-500">Period allocation for <strong class="text-slate-800 dark:text-slate-200">{{ $activeChild->currentClass?->display_name ?? 'Class' }}</strong></p>
                </div>
                <a href="{{ route('school.timetable.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                    Master Grid &rarr;
                </a>
            </div>

            @php
                $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            @endphp
            <div class="space-y-4 mt-3">
                @foreach($weekDays as $day)
                    @php $daySlots = $weeklyTimetable->get($day, collect()); @endphp
                    @if($daySlots->isNotEmpty())
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/50 dark:bg-slate-800/40">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            {{ $day }}
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($daySlots as $slot)
                            <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs">
                                <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono mb-1">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Period {{ $slot->period_number }}</span>
                                    <span>{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}</span>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white block truncate">{{ $slot->subject?->name ?? 'General' }}</span>
                                <span class="text-[10px] text-slate-500 block truncate">{{ $slot->teacher?->name ?? 'Faculty Staff' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 6: TUITION FEES & INVOICES TAB         -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'fees'" x-cloak class="space-y-4">
        <!-- Fee Summary Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Billed</span>
                <div class="text-xl font-black text-slate-900 dark:text-white mt-1">₹{{ number_format($feeStats['total_billed'], 2) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Total Paid</span>
                <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">₹{{ number_format($feeStats['total_paid'], 2) }}</div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Outstanding Balance</span>
                <div class="text-xl font-black text-rose-500 mt-1">₹{{ number_format($feeStats['total_due'], 2) }}</div>
            </div>
        </div>

        <!-- Invoices List -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">💳 Tuition Invoices for {{ $activeChild->name }}</h3>
                <a href="{{ route('school.fees.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">
                    Payment Gateway &rarr;
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($allInvoices as $inv)
                <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $inv->invoice_number }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                                {{ strtoupper($inv->status) }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-600 dark:text-slate-400 block mt-0.5">{{ $inv->title }}</span>
                        <span class="text-[10px] text-slate-400 block">Due Date: {{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</span>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <span class="text-sm font-black text-slate-900 dark:text-white block">₹{{ number_format($inv->total_due, 2) }}</span>
                            @if($inv->status !== 'paid')
                            <span class="text-[10px] text-rose-500 font-bold block">Due: ₹{{ number_format($inv->total_due - $inv->paid_amount, 2) }}</span>
                            @endif
                        </div>
                        @if($inv->status === 'paid' && $inv->payments->isNotEmpty())
                        <a href="{{ route('school.fees.receipt', $inv->payments->first()->id) }}" 
                           class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold text-xs transition">
                            Receipt
                        </a>
                        @elseif($inv->status !== 'paid')
                        <a href="{{ route('school.fees.index') }}" 
                           class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-2xs">
                            Pay Now
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-xs text-slate-400">
                    No fee invoices found for {{ $activeChild->name }}.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    @else
    <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
        <span class="text-4xl block">👨‍👩‍👧</span>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">No Linked Student Account Found</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto">
            Please ensure your registered parent email address matches the student's guardian record in institutional admissions.
        </p>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chart-parent-attendance');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [
                        {{ (int)($attendanceStats['present'] ?? 0) }},
                        {{ (int)($attendanceStats['absent'] ?? 0) }},
                        {{ (int)($attendanceStats['late'] ?? 0) }}
                    ],
                    backgroundColor: ['#10b981', '#f43f5e', '#f59e0b'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
@endsection
