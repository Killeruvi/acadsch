@extends('layouts.app')

@section('title', 'Monthly Attendance Register - ' . $class->display_name)

@section('content')
<style>
@media print {
    nav, header, aside, .no-print, button, form {
        display: none !important;
    }
    body, main, .max-w-\[1680px\] {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        background: #fff !important;
        color: #000 !important;
    }
    .print-only {
        display: block !important;
    }
    table {
        font-size: 8px !important;
    }
}
</style>

<div class="max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 no-print">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ $class->display_name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Teacher: {{ $class->classTeacher?->name ?? 'Unassigned' }} &bull; {{ $carbonMonth->format('F Y') }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                <span>🖨️</span>
                <span>Print Register</span>
            </button>
            <form method="GET" action="{{ route('school.attendance.register', $class->id) }}" class="flex items-center space-x-2">
                <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()"
                       class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold">
                    Go
                </button>
            </form>
        </div>
    </div>

    <!-- Monthly Register Matrix -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs">
            <span class="font-bold text-slate-900 dark:text-white">Attendance Register Matrix</span>
            <div class="flex items-center flex-wrap gap-3 text-xs font-bold">
                <span class="flex items-center space-x-1"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> <span class="text-emerald-700 dark:text-emerald-400">P = Present</span></span>
                <span class="flex items-center space-x-1"><span class="w-3 h-3 rounded bg-rose-500 inline-block"></span> <span class="text-rose-700 dark:text-rose-400">A = Absent</span></span>
                <span class="flex items-center space-x-1"><span class="w-3 h-3 rounded bg-blue-500 inline-block"></span> <span class="text-blue-700 dark:text-blue-400">E = Excused (Leave)</span></span>
                <span class="flex items-center space-x-1"><span class="w-3 h-3 rounded bg-amber-500 inline-block"></span> <span class="text-amber-700 dark:text-amber-400">L = Late</span></span>
                <span class="flex items-center space-x-1"><span class="w-3 h-3 rounded bg-slate-200 dark:bg-slate-700 inline-block"></span> <span class="text-slate-400">- = No Session / Holiday</span></span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[750px]">
            <table class="w-full text-center text-[11px] text-slate-600 dark:text-slate-400 border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/90 font-black text-[10px] text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-20">
                    <tr>
                        <th class="p-2 text-left sticky left-0 bg-slate-50 dark:bg-slate-800 z-30 w-48 border-r border-slate-200 dark:border-slate-700">Student Name</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                        <th class="p-1 min-w-[24px] border-r border-slate-100 dark:border-slate-800/50">{{ $d }}</th>
                        @endfor
                        <th class="p-2 text-emerald-700 dark:text-emerald-400">P</th>
                        <th class="p-2 text-rose-700 dark:text-rose-400">A</th>
                        <th class="p-2 text-blue-700 dark:text-blue-400">E</th>
                        <th class="p-2 font-black text-slate-900 dark:text-white">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($class->students as $student)
                    @php
                        $tot = $studentTotals[$student->id] ?? ['present' => 0, 'absent' => 0, 'excused' => 0, 'late' => 0, 'total' => 0];
                        $pct = $tot['total'] > 0 ? round((($tot['present'] + $tot['excused']) / $tot['total']) * 100) : 100;
                    @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="p-2 text-left sticky left-0 bg-white dark:bg-slate-900 z-10 border-r border-slate-200 dark:border-slate-800 whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-white block truncate max-w-[170px]">{{ $student->name }}</span>
                            <span class="text-[9px] text-slate-400 font-mono">Roll #{{ $student->pivot->roll_number ?? $student->roll_number ?? '-' }}</span>
                        </td>

                        @for($d = 1; $d <= $daysInMonth; $d++)
                        @php $st = $matrix[$student->id][$d] ?? null; @endphp
                        <td class="p-1 border-r border-slate-100 dark:border-slate-800/40">
                            @if($st === 'present')
                            <span class="w-5 h-5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold inline-flex items-center justify-center text-[9px]">P</span>
                            @elseif($st === 'absent')
                            <span class="w-5 h-5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-bold inline-flex items-center justify-center text-[9px]">A</span>
                            @elseif($st === 'excused')
                            <span class="w-5 h-5 rounded bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold inline-flex items-center justify-center text-[9px]">E</span>
                            @elseif($st === 'late' || $st === 'half_day')
                            <span class="w-5 h-5 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold inline-flex items-center justify-center text-[9px]">L</span>
                            @else
                            <span class="text-slate-300 dark:text-slate-700 text-[10px]">&bull;</span>
                            @endif
                        </td>
                        @endfor

                        <td class="p-2 font-bold text-emerald-700 dark:text-emerald-400">{{ $tot['present'] }}</td>
                        <td class="p-2 font-bold text-rose-700 dark:text-rose-400">{{ $tot['absent'] }}</td>
                        <td class="p-2 font-bold text-blue-700 dark:text-blue-400">{{ $tot['excused'] }}</td>
                        <td class="p-2 font-black text-slate-900 dark:text-white">{{ $pct }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $daysInMonth + 5 }}" class="p-8 text-center text-slate-400">
                            No students enrolled in this class.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <!-- Totals Row (Rule 4: Totals row: present, absent, %) -->
                @if($class->students->isNotEmpty() && !empty($dayTotals))
                <tfoot class="bg-slate-100 dark:bg-slate-800 font-black text-[10px] text-slate-800 dark:text-slate-200 sticky bottom-0 z-20 border-t-2 border-slate-300 dark:border-slate-700">
                    <tr>
                        <td class="p-2 text-left sticky left-0 bg-slate-100 dark:bg-slate-800 z-30 border-r border-slate-300 dark:border-slate-700 uppercase">
                            Daily Present
                        </td>
                        @php $sumPresent = 0; $sumTotal = 0; @endphp
                        @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $p = $dayTotals[$d]['present'] ?? 0;
                            $sumPresent += $p;
                            $sumTotal += ($dayTotals[$d]['total'] ?? 0);
                        @endphp
                        <td class="p-1 text-emerald-700 dark:text-emerald-400 border-r border-slate-200 dark:border-slate-700">
                            {{ $p > 0 ? $p : '-' }}
                        </td>
                        @endfor
                        <td class="p-2 text-emerald-700 dark:text-emerald-400 font-black">{{ $sumPresent }}</td>
                        <td class="p-2 text-rose-700 dark:text-rose-400 font-black">
                            {{ array_sum(array_column($dayTotals, 'absent')) }}
                        </td>
                        <td class="p-2 text-blue-700 dark:text-blue-400 font-black">
                            {{ array_sum(array_column($dayTotals, 'excused')) }}
                        </td>
                        <td class="p-2 text-slate-900 dark:text-white font-black">
                            {{ $sumTotal > 0 ? round(($sumPresent / $sumTotal) * 100) : 0 }}%
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
