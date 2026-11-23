@extends('layouts.app')

@section('title', 'Roll Call - ' . $class->display_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('school.attendance.index') }}" class="hover:text-emerald-500">Attendance</a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-bold">{{ $class->display_name }}</span>
    </div>

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ $class->display_name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Teacher: {{ $class->classTeacher?->name ?? 'Unassigned' }} • Room: {{ $class->room_number ?: 'Room not assigned' }} • {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" 
                    onclick="markAllStatus('present')"
                    class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold text-xs transition border border-emerald-200 dark:border-emerald-800">
                ✅ Mark All Present
            </button>
        </div>
    </div>

    @if(session('error'))
    <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs font-bold text-rose-800 dark:text-rose-200 flex items-start space-x-2 shadow-xs">
        <span class="text-base flex-shrink-0">⚠️</span>
        <div class="flex-1 leading-relaxed">{{ session('error') }}</div>
    </div>
    @endif

    <!-- Roll Call Form -->
    <form method="POST" action="{{ route('school.attendance.store', $class->id) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="session_date" value="{{ $date }}">

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3.5 w-16">Roll No</th>
                            <th class="p-3.5">Student</th>
                            <th class="p-3.5 text-center">Status</th>
                            <th class="p-3.5">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($class->students as $student)
                        @php
                            $rec = $existingRecords[$student->id] ?? null;
                            $currentStatus = $rec ? $rec['status'] : 'present';
                            $currentRemarks = $rec ? $rec['remarks'] : '';
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                #{{ $student->pivot->roll_number ?? $student->roll_number ?? '-' }}
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xs flex items-center justify-center flex-shrink-0">
                                        {{ strtoupper(substr($student->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-white block">{{ $student->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $student->admission_number ?? '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center justify-center space-x-3">
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="records[{{ $student->id }}][status]" value="present" 
                                               class="text-emerald-600 focus:ring-emerald-500 radio-present" 
                                               {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                        <span class="text-xs font-bold text-emerald-600">Present</span>
                                    </label>

                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="records[{{ $student->id }}][status]" value="absent" 
                                               class="text-rose-600 focus:ring-rose-500 radio-absent" 
                                               {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                        <span class="text-xs font-bold text-rose-600">Absent</span>
                                    </label>

                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="records[{{ $student->id }}][status]" value="late" 
                                               class="text-amber-600 focus:ring-amber-500 radio-late" 
                                               {{ $currentStatus === 'late' ? 'checked' : '' }}>
                                        <span class="text-xs font-bold text-amber-600">Late</span>
                                    </label>

                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" name="records[{{ $student->id }}][status]" value="half_day" 
                                               class="text-blue-600 focus:ring-blue-500 radio-half" 
                                               {{ $currentStatus === 'half_day' ? 'checked' : '' }}>
                                        <span class="text-xs font-bold text-blue-600">Half Day</span>
                                    </label>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <input type="text" name="records[{{ $student->id }}][remarks]" value="{{ $currentRemarks }}" placeholder="Optional remark..."
                                       class="w-full px-3 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400">
                                No students enrolled in this class yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('school.attendance.index', ['date' => $date]) }}" 
               class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold">
                Back to Dashboard
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/25">
                Save Class Attendance Record
            </button>
        </div>
    </form>

</div>

<script>
function markAllStatus(status) {
    const inputs = document.querySelectorAll('.radio-' + status);
    inputs.forEach(input => input.checked = true);
}
</script>
@endsection
