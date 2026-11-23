@extends('layouts.app')

@section('title', $session->title . ' — Attendance Details' . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
@php
    $canEdit = Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $session->teacher_id === Auth::id());
    $initialAttendance = [];
    foreach ($session->records as $r) {
        $initialAttendance[(string)$r->student_id] = $r->status;
    }
@endphp

<div x-data="{
    isEditing: {{ ($isEditing ?? false) ? 'true' : 'false' }},
    attendance: {{ Js::from($initialAttendance) }},
    markAll(status) {
        Object.keys(this.attendance).forEach(id => {
            this.attendance[id] = status;
        });
    }
}" class="space-y-4">

    <!-- Top Navigation / Breadcrumb -->
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('attendance.index', ['class_id' => $session->class_id]) }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
            <span>&larr;</span>
            <span>Back to Sessions List</span>
        </a>
        <div class="flex items-center space-x-2 text-xs">
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                {{ $session->academicClass?->name ?? 'Class not linked' }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $session->session_date->format('M d, Y') }}</span>
            @if($canEdit)
            <button type="button" 
                    @click="isEditing = !isEditing" 
                    class="px-3 py-1 rounded-lg font-bold text-xs transition flex items-center space-x-1 shadow-2xs"
                    :class="isEditing ? 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200' : 'bg-indigo-600 hover:bg-indigo-500 text-white'">
                <span x-text="isEditing ? '✕ Cancel' : '✏️ Edit Attendance'"></span>
            </button>
            @endif
        </div>
    </div>

    <!-- Header Banner (Compact & Responsive) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-indigo-900/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                <span>📋 Class Session Roster</span>
            </div>
            <h1 class="text-lg sm:text-xl font-black tracking-tight text-white">
                {{ $session->title }}
            </h1>
            <p class="text-xs text-slate-300 max-w-xl leading-relaxed">
                Recorded by <strong class="text-white">{{ $session->teacher->name }}</strong> on {{ $session->session_date->format('l, F j, Y') }}.
            </p>
            @if($session->notes)
            <div class="mt-2 p-2 rounded-lg bg-white/5 border border-white/10 text-xs text-slate-300">
                <span class="font-bold text-amber-300">Notes:</span> {{ $session->notes }}
            </div>
            @endif
        </div>

        <div class="flex items-center gap-2 self-start md:self-center">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 text-center min-w-[130px]">
                <div class="text-2xl font-black {{ $rate >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ $rate }}%
                </div>
                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-300 mt-0.5">Attendance Rate</div>
                <div class="text-[10px] text-slate-400">{{ $presentCount }}/{{ $session->records->count() }} Present</div>
            </div>
            @if($canEdit)
            <div class="hidden sm:block">
                <button type="button" @click="isEditing = true" x-show="!isEditing" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                    <span>✏️ Edit Session</span>
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Stats Bar (Compact) -->
    <div class="grid grid-cols-3 gap-2.5 sm:gap-3 text-center">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total</span>
            <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">{{ $session->records->count() }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">Present</span>
            <div class="text-lg sm:text-xl font-black text-emerald-700 dark:text-emerald-400">{{ $presentCount }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">Absent</span>
            <div class="text-lg sm:text-xl font-black text-rose-700 dark:text-rose-400">{{ $absentCount }}</div>
        </div>
    </div>

    <!-- EDIT FORM / VIEW WRAPPER -->
    <form action="{{ route('attendance.update', $session->id) }}" method="POST" data-preserve-scroll="true" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Edit Session Metadata (Visible only in edit mode) -->
        <div x-show="isEditing" x-cloak class="bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-amber-200 dark:border-amber-800">
                <div class="flex items-center space-x-2">
                    <span class="text-base">✏️</span>
                    <h3 class="text-xs font-bold text-amber-900 dark:text-amber-200">
                        Editing Published Attendance Session — Changes preserve historical integrity &amp; adjust student streaks
                    </h3>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" @click="markAll('present')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-600 text-white hover:bg-emerald-500 transition shadow-2xs">
                        Mark All Present
                    </button>
                    <button type="button" @click="markAll('absent')" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-600 text-white hover:bg-rose-500 transition shadow-2xs">
                        Mark All Absent
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Session Title *</label>
                    <input type="text" name="title" value="{{ $session->title }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Session Date *</label>
                    <input type="date" name="session_date" value="{{ $session->session_date->toDateString() }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Session Notes (Optional)</label>
                <input type="text" name="notes" value="{{ $session->notes }}" placeholder="e.g. Science Lab Practical or Lecture Notes" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <!-- Student Attendance Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
            <div class="p-3.5 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Student Roster Attendance</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Class: {{ $session->academicClass->name ?? 'Class' }} &bull; {{ $session->records->count() }} enrolled candidates</p>
                </div>
                @if($canEdit)
                <div x-show="isEditing" class="flex items-center space-x-2">
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                        <span>💾 Save Attendance Changes</span>
                    </button>
                </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 font-bold">
                        <tr>
                            <th class="px-3.5 py-2.5">Roll</th>
                            <th class="px-3.5 py-2.5">Student</th>
                            <th class="px-3.5 py-2.5">Admission ID</th>
                            <th class="px-3.5 py-2.5">Status</th>
                            <th class="px-3.5 py-2.5 text-right">Academic XP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($session->records as $index => $rec)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-3.5 py-3">
                                <span class="w-6 h-6 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[11px]">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-3.5 py-3">
                                <div class="font-bold text-slate-900 dark:text-white text-xs">{{ $rec->student->name }}</div>
                                <div class="text-slate-400 text-[10px]">{{ $rec->student->email }}</div>
                            </td>
                            <td class="px-3.5 py-3 text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                {{ $rec->student->student_id_number ?? 'PAA-STU-' . $rec->student_id }}
                            </td>
                            <td class="px-3.5 py-3">
                                <!-- VIEW MODE -->
                                <div x-show="!isEditing">
                                    @if($rec->status === 'present')
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            ✅ Present
                                        </span>
                                    @elseif($rec->status === 'late')
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            ⏱️ Late
                                        </span>
                                    @elseif($rec->status === 'excused')
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            📝 Excused
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            ❌ Absent
                                        </span>
                                    @endif
                                </div>

                                <!-- EDIT MODE (Interactive Button Chips) -->
                                <div x-show="isEditing" x-cloak class="flex items-center space-x-1">
                                    <input type="hidden" name="attendance[{{ $rec->student_id }}]" :value="attendance['{{ $rec->student_id }}'] || 'present'">
                                    <button type="button" 
                                            @click="attendance['{{ $rec->student_id }}'] = 'present'" 
                                            class="px-2 py-1 rounded text-[10px] font-bold transition"
                                            :class="attendance['{{ $rec->student_id }}'] === 'present' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-emerald-50'">
                                        Present
                                    </button>
                                    <button type="button" 
                                            @click="attendance['{{ $rec->student_id }}'] = 'late'" 
                                            class="px-2 py-1 rounded text-[10px] font-bold transition"
                                            :class="attendance['{{ $rec->student_id }}'] === 'late' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-amber-50'">
                                        Late
                                    </button>
                                    <button type="button" 
                                            @click="attendance['{{ $rec->student_id }}'] = 'absent'" 
                                            class="px-2 py-1 rounded text-[10px] font-bold transition"
                                            :class="attendance['{{ $rec->student_id }}'] === 'absent' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-rose-50'">
                                        Absent
                                    </button>
                                    <button type="button" 
                                            @click="attendance['{{ $rec->student_id }}'] = 'excused'" 
                                            class="px-2 py-1 rounded text-[10px] font-bold transition"
                                            :class="attendance['{{ $rec->student_id }}'] === 'excused' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-blue-50'">
                                        Excused
                                    </button>
                                </div>
                            </td>
                            <td class="px-3.5 py-3 text-right">
                                <span x-show="!isEditing">
                                    @if(in_array($rec->status, ['present', 'late']))
                                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                            +15 XP
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </span>
                                <span x-show="isEditing" x-cloak class="text-[10px] text-slate-400 font-semibold"
                                      x-text="attendance['{{ $rec->student_id }}'] === 'present' || attendance['{{ $rec->student_id }}'] === 'late' ? '+15 XP' : '0 XP'">
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                                No student records found in this attendance session.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($canEdit)
            <div x-show="isEditing" x-cloak class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">Remember to save after updating student statuses.</span>
                <div class="flex items-center space-x-2">
                    <button type="button" @click="isEditing = false" class="px-3.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                        <span>💾 Save Attendance</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </form>

</div>
@endsection
