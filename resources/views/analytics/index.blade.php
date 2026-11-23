@extends('layouts.app')

@section('title', ($school['name'] ?? 'PrimeOS') . ' — Analytics & Reports')

@section('content')
<div class="space-y-4">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Analytics &amp; Reports</h1>
            <p class="text-xs text-slate-500 mt-0.5">DPP mastery rates, daily homework evaluations, batch attendance audits, and Bloom's cognitive depth</p>
        </div>
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live Intelligence</span>
        </div>
    </div>

    <!-- 6 Primary Academic Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Daily DPPs</div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalDpps }}</div>
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                {{ $totalDppSubmissions }} solved &bull; {{ $avgDppScore }}% avg
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Homework Tasks</div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalHomeworkTasks }}</div>
            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                {{ $homeworkCheckedCount }}/{{ $totalHomeworkSubmissions }} checked
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attendance Rate</div>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $attendanceRate }}%</div>
            <div class="text-[11px] text-slate-500 font-semibold mt-0.5">
                Across {{ $totalAttendanceSessions }} sessions
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Academic Batches</div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalBatches }}</div>
            <div class="text-[11px] text-slate-500 font-semibold mt-0.5">
                In {{ $totalClasses }} grade classes
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bloom's Tasks</div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalBloomAssignments }}</div>
            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">
                Cognitive assignments
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Published Exam Results</div>
            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $publishedReportCards }}</div>
            <div class="text-[11px] text-slate-500 font-semibold mt-0.5">
                {{ $averageReportPercentage !== null ? $averageReportPercentage.'% average' : 'No published results yet' }}
            </div>
        </div>
    </div>

    <!-- Visual Analytics Graphs Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Chart 1: Bloom's Cognitive Radar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white text-sm">Bloom's Cognitive Depth Radar</h3>
                    <p class="text-[11px] text-slate-500">Curriculum weight across cognitive taxonomy tiers</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 rounded-md">Cognitive Map</span>
            </div>
            <div class="h-60 relative flex items-center justify-center">
                <canvas id="chart-bloom-radar"></canvas>
            </div>
        </div>

        <!-- Chart 2: Batch Attendance & Participation -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white text-sm">Batch Attendance &amp; Engagement</h3>
                    <p class="text-[11px] text-slate-500">Roll-call adherence % across active school sections</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-md">Roll-Call Audit</span>
            </div>
            <div class="h-60 relative">
                <canvas id="chart-batch-attendance"></canvas>
            </div>
        </div>
    </div>

    <!-- 2 Column: Academic Deep-Dive & Curriculum Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Bloom's Taxonomy Cognitive Distribution -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white text-base">Bloom's Taxonomy Cognitive Distribution</h3>
                    <p class="text-xs text-slate-500">Evaluation depth across Levels 1 (Remember) to 6 (Create)</p>
                </div>
                <span class="text-xs font-bold px-2 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 rounded-lg">6 Levels</span>
            </div>

            <div class="space-y-3.5">
                @foreach($bloomLevels as $lvl => $info)
                @php
                    $pct = $totalBloomAssignments > 0 ? round(($info['count'] / $totalBloomAssignments) * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                            <span class="w-4 h-4 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] flex items-center justify-center font-black">{{ $lvl }}</span>
                            <span>Level {{ $lvl }}: {{ $info['name'] }}</span>
                        </span>
                        <span class="{{ $info['text'] }}">{{ $info['count'] }} tasks ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="{{ $info['bg'] }} h-2 rounded-full transition-all duration-500" style="width: {{ max(4, $pct) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Academic Hall of Fame (Top Streaks & XP) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-black text-slate-900 dark:text-white text-base">Academic Hall of Fame</h3>
                    <p class="text-xs text-slate-500">Top students by Academic XP and active daily streaks</p>
                </div>
                <span class="text-xs font-bold px-2 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-lg">🔥 Streaks</span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($topAchievers as $index => $scholar)
                <div class="py-2.5 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-6 h-6 rounded-lg {{ $index === 0 ? 'bg-amber-400 text-amber-950 font-black' : ($index === 1 ? 'bg-slate-300 text-slate-800 font-bold' : ($index === 2 ? 'bg-amber-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 font-semibold')) }} flex items-center justify-center text-[11px] shadow-2xs">
                            #{{ $index + 1 }}
                        </span>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">{{ $scholar->name }}</div>
                            <div class="text-[10px] text-slate-400">{{ $scholar->batch?->name ?? 'Enrolled' }} &bull; {{ $scholar->dpp_submissions_count }} DPPs</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-emerald-600 dark:text-emerald-400 text-xs">⚡ {{ number_format($scholar->xp_points) }} XP</div>
                        <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold">🔥 {{ $scholar->streak_count }} day streak</div>
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-xs text-slate-400 italic">No student activity logged yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Batch Performance & Live Attendance Matrix -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-black text-slate-900 dark:text-white text-base">Batch Operations &amp; Attendance Performance</h3>
                <p class="text-xs text-slate-500">Live roster capacity, timing schedules, and calculated attendance rate</p>
            </div>
            <span class="text-xs font-bold text-slate-400">{{ $batchPerformance->count() }} Batches</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 uppercase text-[10px] font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Batch Name</th>
                        <th class="px-5 py-3">Academic Grade</th>
                        <th class="px-5 py-3">Shift &amp; Timings</th>
                        <th class="px-5 py-3">Active Students</th>
                        <th class="px-5 py-3">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($batchPerformance as $batch)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                            {{ $batch['name'] }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $batch['class_name'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ ucfirst($batch['shift']) }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ $batch['timing'] ?? 'Flexible' }}</span>
                        </td>
                        <td class="px-5 py-3.5 font-semibold">
                            {{ $batch['students_count'] }} Students
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $batch['attendance_rate'] }}%"></div>
                                </div>
                                <span class="font-bold text-[11px] {{ $batch['attendance_rate'] >= 75 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $batch['attendance_rate'] }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-4 text-center text-xs text-slate-400 italic">No batches created yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Elective Courses Performance & Academic Activity (Collapsible / Secondary) -->
    @if($totalCourses > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-3">Courses by Discipline</h3>
            <div class="space-y-3">
                @foreach($categories as $cat)
                <div>
                    <div class="flex justify-between text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        <span>{{ $cat->category }}</span>
                        <span>{{ $cat->count }} Course(s)</span>
                    </div>
                    @php $catPercent = min(100, round(($cat->count / max(1, $totalCourses)) * 100)); @endphp
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $catPercent }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Recent Graded Activity Box -->
            <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
                <h4 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider mb-2.5">Recent Grading Audits</h4>
                <div class="space-y-2">
                    @foreach($recentGraded as $rg)
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs">
                        <div class="flex items-center justify-between font-semibold text-slate-800 dark:text-slate-200">
                            <span>{{ $rg->student->name }}</span>
                            <span class="text-emerald-700 dark:text-emerald-400 font-black">{{ $rg->grade }}/{{ $rg->assignment->max_points }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $rg->assignment->title }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Course Capacity &amp; Academic Activity</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 uppercase text-[10px] font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Course</th>
                            <th class="px-5 py-3">Instructor</th>
                            <th class="px-5 py-3">Capacity</th>
                            <th class="px-5 py-3">Assignments</th>
                            <th class="px-5 py-3">Fill Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($courses as $c)
                        @php $rate = min(100, round(($c->enrollments_count / max(1, $c->capacity)) * 100)); @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="px-5 py-3">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $c->code }}</span>
                                <span class="text-[11px] text-slate-500">{{ $c->title }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $c->teacher?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-3">{{ $c->enrollments_count }} / {{ $c->capacity }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">{{ $c->assignments_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $rate }}%"></div>
                                    </div>
                                    <span class="font-bold text-[11px] text-slate-700 dark:text-slate-300">{{ $rate }}%</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#cbd5e1' : '#334155';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    // 1. Bloom's Cognitive Radar
    const ctxBloom = document.getElementById('chart-bloom-radar');
    if (ctxBloom) {
        const bloomLabels = {!! json_encode(array_values(array_map(fn($b) => $b['name'], $bloomLevels))) !!};
        const bloomData = {!! json_encode(array_values(array_map(fn($b) => $b['count'], $bloomLevels))) !!};

        new Chart(ctxBloom, {
            type: 'radar',
            data: {
                labels: bloomLabels,
                datasets: [{
                    label: 'Assignments',
                    data: bloomData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.25)',
                    borderWidth: 2,
                    pointBackgroundColor: '#f59e0b',
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    r: {
                        angleLines: { color: gridColor },
                        grid: { color: gridColor },
                        pointLabels: { color: textColor, font: { size: 10, weight: 'bold' } },
                        ticks: { display: false }
                    }
                }
            }
        });
    }

    // 2. Batch Attendance Performance Chart
    const ctxBatch = document.getElementById('chart-batch-attendance');
    if (ctxBatch) {
        const batchNames = {!! json_encode($batchPerformance->pluck('name')->toArray()) !!};
        const batchRates = {!! json_encode($batchPerformance->pluck('rate')->toArray()) !!};

        new Chart(ctxBatch, {
            type: 'bar',
            data: {
                labels: batchNames,
                datasets: [{
                    label: 'Attendance Rate %',
                    data: batchRates,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 10 } }, grid: { display: false } },
                    y: { 
                        min: 0, 
                        max: 100, 
                        ticks: { color: textColor, font: { size: 9 }, callback: v => v + '%' }, 
                        grid: { color: gridColor } 
                    }
                }
            }
        });
    }
});
</script>
@endsection
