@extends('layouts.app')

@section('title', 'Route-wise Student Transport Report — PrimeOS')

@section('content')
<div class="max-w-[1400px] mx-auto space-y-4 pb-20">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ route('school.transport.index') }}" class="hover:text-purple-600">Transport Hub</a>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Route-wise Students</span>
            </div>
            <h1 class="text-lg font-black text-slate-900 dark:text-white">Route-wise Student Allocation &amp; Transit Manifest</h1>
        </div>
        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
            <span>🖨️</span>
            <span>Print Report</span>
        </button>
    </div>

    @foreach($routes as $r)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center space-x-2">
                    <span>🛣️</span>
                    <span>{{ $r->route_title }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                        {{ $r->activeStudentAssignments->count() }} active riders
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ $r->start_point ?? 'Depot' }} &rarr; {{ $r->end_point ?? 'Campus' }} • Fare: ₹{{ number_format($r->fare, 2) }}/mo • Distance: {{ $r->distance_km ?? '-' }} km
                </p>
            </div>
            <div class="text-xs font-mono text-right">
                <span class="text-slate-400 block text-[10px]">Monthly Corridor Revenue</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">₹{{ number_format($r->activeStudentAssignments->sum(fn($a) => $a->effective_fare), 2) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-bold text-[10px] text-slate-500">
                    <tr>
                        <th class="p-2">#</th>
                        <th class="p-2">Student Name</th>
                        <th class="p-2">Admission #</th>
                        <th class="p-2">Class &amp; Section</th>
                        <th class="p-2">Assigned Stop</th>
                        <th class="p-2">Pickup Time</th>
                        <th class="p-2 text-right">Effective Monthly Fee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($r->activeStudentAssignments as $idx => $sa)
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-2 font-mono text-[11px]">{{ $idx + 1 }}</td>
                        <td class="p-2 font-bold text-slate-900 dark:text-white">{{ $sa->student?->name }}</td>
                        <td class="p-2 font-mono text-[11px]">{{ $sa->student?->admission_number }}</td>
                        <td class="p-2">{{ $sa->student?->currentClass?->display_name ?? 'N/A' }}</td>
                        <td class="p-2 font-bold text-purple-600">{{ $sa->stop?->stop_name ?? 'Direct Corridor' }}</td>
                        <td class="p-2 font-mono">{{ $sa->stop?->pickup_time ?? $r->morning_timing ?? '07:15 AM' }}</td>
                        <td class="p-2 text-right font-mono font-bold text-emerald-600">₹{{ number_format($sa->effective_fare, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-400 text-xs">No active student riders allocated to this corridor.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
