@extends('layouts.app')

@section('title', 'Vehicle-wise Transport Capacity & Allocation Report — PrimeOS')

@section('content')
<div class="max-w-[1400px] mx-auto space-y-4 pb-20">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ route('school.transport.index') }}" class="hover:text-purple-600">Transport Hub</a>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Vehicle-wise Riders</span>
            </div>
            <h1 class="text-lg font-black text-slate-900 dark:text-white">Vehicle Fleet Capacity, Occupancy &amp; Student Allocation</h1>
        </div>
        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
            <span>🖨️</span>
            <span>Print Report</span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($vehicles as $v)
        @php
            $riderCount = $v->activeStudentAssignments->count();
            $occupancyRate = $v->capacity > 0 ? round(($riderCount / $v->capacity) * 100, 1) : 0;
            $isOverloaded = $riderCount > $v->capacity;
        @endphp
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ $v->vehicle_number }}</span>
                    <span class="text-xs text-slate-500 block">{{ $v->vehicle_model ?? 'Standard' }} ({{ $v->vehicle_type ?? 'Bus' }})</span>
                </div>
                <div class="text-right">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isOverloaded ? 'bg-rose-50 text-rose-700 border border-rose-300' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $riderCount }} / {{ $v->capacity }} Seats ({{ $occupancyRate }}%)
                    </span>
                    @if($isOverloaded)
                    <span class="text-[9px] text-rose-600 font-bold block mt-0.5">⚠️ Over Capacity!</span>
                    @endif
                </div>
            </div>

            <div class="text-xs space-y-1 text-slate-600 dark:text-slate-400">
                <div>Route: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $v->route?->route_title ?? 'Unassigned' }}</span></div>
                <div>Driver: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $v->driver?->name ?? $v->driver_name ?? 'No Driver' }}</span> (📞 {{ $v->driver?->contact_number ?? $v->driver_phone ?? 'N/A' }})</div>
            </div>

            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                <div class="{{ $isOverloaded ? 'bg-rose-500' : 'bg-purple-600' }} h-2 rounded-full" style="width: {{ min(100, $occupancyRate) }}%"></div>
            </div>

            <div class="overflow-x-auto max-h-48 overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-bold text-[9px] text-slate-400 sticky top-0">
                        <tr>
                            <th class="p-1.5">Student</th>
                            <th class="p-1.5">Class</th>
                            <th class="p-1.5">Stop</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($v->activeStudentAssignments as $sa)
                        <tr>
                            <td class="p-1.5 font-bold text-slate-800 dark:text-slate-200">{{ $sa->student?->name }}</td>
                            <td class="p-1.5">{{ $sa->student?->currentClass?->display_name }}</td>
                            <td class="p-1.5 font-mono text-[10px]">{{ $sa->stop?->stop_name ?? 'Campus' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="p-2 text-center text-slate-400 text-xs">No students boarded to this vehicle.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
