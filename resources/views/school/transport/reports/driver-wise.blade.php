@extends('layouts.app')

@section('title', 'Driver & Staff Transport Assignment Report — PrimeOS')

@section('content')
<div class="max-w-[1400px] mx-auto space-y-4 pb-20">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ route('school.transport.index') }}" class="hover:text-purple-600">Transport Hub</a>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Driver-wise Corridors</span>
            </div>
            <h1 class="text-lg font-black text-slate-900 dark:text-white">Driver Roster, Assigned Vehicles &amp; Transit Routes</h1>
        </div>
        <button onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs">
            <span>🖨️</span>
            <span>Print Report</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-bold text-[10px] text-slate-500">
                    <tr>
                        <th class="p-3">Driver Name</th>
                        <th class="p-3">Contact</th>
                        <th class="p-3">License # &amp; Expiry</th>
                        <th class="p-3">Assigned Vehicle</th>
                        <th class="p-3">Assigned Route Corridor</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($drivers as $d)
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-3 font-bold text-slate-900 dark:text-white">
                            {{ $d->name }}
                        </td>
                        <td class="p-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                            📞 {{ $d->contact_number }}
                        </td>
                        <td class="p-3 font-mono text-[11px]">
                            <div>{{ $d->license_number ?? 'N/A' }}</div>
                            @if($d->license_expiry)
                            <div class="text-[10px] text-slate-400">Expires: {{ $d->license_expiry->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $d->vehicle?->vehicle_number ?? 'Unassigned' }}
                            @if($d->vehicle) <span class="text-[10px] text-slate-400 font-normal">({{ $d->vehicle->vehicle_model }})</span> @endif
                        </td>
                        <td class="p-3 font-bold text-purple-600 dark:text-purple-400">
                            {{ $d->route?->route_title ?? $d->vehicle?->route?->route_title ?? 'Unassigned' }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $d->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($d->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-slate-400">No driver personnel registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
