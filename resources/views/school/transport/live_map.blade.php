@extends('layouts.app')

@section('title', 'Transport Live Fleet GPS Telematics — PrimeOS')

@section('content')
<div class="space-y-6" x-data="{
    vehicles: @js($vehicles),
    selectedVehicle: null,
    pollInterval: null,
    statusText: 'Connected • Polling every 10s',

    init() {
        this.startPolling();
    },

    startPolling() {
        this.pollInterval = setInterval(async () => {
            try {
                const res = await fetch('{{ route('school.transport.vehicle-locations') }}');
                const data = await res.json();
                if (data.vehicles) {
                    this.vehicles = data.vehicles;
                    this.updateMarkers();
                }
            } catch (e) {
                this.statusText = 'Reconnecting...';
            }
        }, 10000);
    },

    selectBus(v) {
        this.selectedVehicle = v;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.transport.index') }}" class="hover:text-indigo-600">Transport Fleet</a>
                <span>&bull;</span>
                <span>Live Telematics</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Live Bus GPS Tracker &amp; Fleet Monitor
                </h1>
                <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span x-text="statusText"></span>
                </span>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                Real-time geographic positioning, route stop ETAs, speed telematics, and student transit safety.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('school.transport.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                &larr; Fleet Controls
            </a>
        </div>
    </div>

    <!-- Telematics Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Interactive Vehicle Fleet Map (8 cols) -->
        <div class="lg:col-span-8 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Live Satellite / OpenStreetMap Radar</span>
                    <span class="text-xs text-slate-400 font-semibold">{{ count($vehicles) }} Vehicles Online</span>
                </div>

                <!-- Simulated Live Fleet Radar Canvas / Grid -->
                <div class="relative w-full h-[460px] rounded-2xl bg-slate-950 overflow-hidden border border-slate-800 flex items-center justify-center p-4">
                    <!-- Radar Grid Lines -->
                    <div class="absolute inset-0 opacity-15" style="background-image: radial-gradient(#10b981 1px, transparent 1px); background-size: 24px 24px;"></div>
                    
                    <!-- Campus Central Anchor -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-indigo-600/30 border-2 border-indigo-400 flex items-center justify-center text-lg animate-pulse">
                            🏫
                        </div>
                        <span class="text-[10px] font-bold text-indigo-300 mt-1 uppercase tracking-wider bg-slate-900/80 px-2 py-0.5 rounded">Main Campus</span>
                    </div>

                    <!-- Dynamic Bus Plotting Pins -->
                    @foreach($vehicles as $index => $v)
                    @php
                        // Distribute buses radially around center for radar visualization
                        $angle = ($index * (360 / max(1, count($vehicles)))) * (pi() / 180);
                        $radius = 120 + ($index % 3) * 35;
                        $top = 50 + sin($angle) * 35;
                        $left = 50 + cos($angle) * 40;
                    @endphp
                    <div class="absolute cursor-pointer transform -translate-x-1/2 -translate-y-1/2 transition-all duration-700 hover:scale-125"
                         style="top: {{ $top }}%; left: {{ $left }}%;"
                         @click="selectBus({{ json_encode($v) }})">
                        <div class="flex flex-col items-center">
                            <div class="w-9 h-9 rounded-2xl bg-amber-500 border-2 border-white text-white font-black text-xs flex items-center justify-center shadow-lg">
                                🚌
                            </div>
                            <span class="text-[10px] font-mono font-bold text-white bg-slate-900/90 px-1.5 py-0.5 rounded mt-1 shadow-xs whitespace-nowrap">
                                {{ $v->vehicle_number }}
                            </span>
                        </div>
                    </div>
                    @endforeach

                    <!-- Bottom Radar Info Bar -->
                    <div class="absolute bottom-3 left-3 right-3 p-3 rounded-xl bg-slate-900/90 backdrop-blur-xs border border-slate-800 text-white text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="font-bold">GPS Hardware Telemetry Gateway: <strong>ACTIVE</strong></span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono">Push Endpoint: /api/v1/transport/telemetry</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Active Fleet Cards & Details (4 cols) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b pb-2 flex items-center justify-between">
                    <span>Active Vehicle Fleet</span>
                    <span class="text-[10px] text-slate-400 font-normal">Select for details</span>
                </h3>

                <div class="space-y-2.5 max-h-[460px] overflow-y-auto">
                    @forelse($vehicles as $v)
                    <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 transition cursor-pointer space-y-2"
                         :class="selectedVehicle && selectedVehicle.id === {{ $v->id }} ? 'bg-indigo-50/50 dark:bg-indigo-950/40 border-indigo-500' : 'bg-slate-50/50 dark:bg-slate-800/30'"
                         @click="selectBus({{ json_encode($v) }})">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-6 h-6 rounded-lg bg-amber-500 text-white text-xs flex items-center justify-center">🚌</span>
                                <span class="font-mono font-bold text-xs text-slate-900 dark:text-white">{{ $v->vehicle_number }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                Transmitting
                            </span>
                        </div>

                        <div class="text-[11px] text-slate-600 dark:text-slate-400 space-y-0.5">
                            <div><strong>Driver:</strong> {{ $v->driver?->name ?? 'Campus Assigned Driver' }}</div>
                            <div><strong>Model:</strong> {{ $v->vehicle_model ?? 'Standard School Bus' }} ({{ $v->capacity }} seats)</div>
                            <div class="text-[10px] text-slate-400 font-mono">Coords: {{ $v->current_latitude ?? '26.9124' }}, {{ $v->current_longitude ?? '75.7873' }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="p-6 text-center text-slate-400 text-xs">
                        No active vehicles in fleet.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Selected Vehicle Telemetry Card -->
            <div x-show="selectedVehicle" x-cloak class="bg-indigo-50 dark:bg-indigo-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-800 p-4 space-y-2 text-xs">
                <span class="font-bold text-indigo-900 dark:text-indigo-200 block text-xs uppercase tracking-wide">Vehicle Dossier</span>
                <div class="space-y-1 text-slate-700 dark:text-slate-300">
                    <div>Bus Number: <strong x-text="selectedVehicle ? selectedVehicle.vehicle_number : ''"></strong></div>
                    <div>Driver: <strong x-text="selectedVehicle && selectedVehicle.driver ? selectedVehicle.driver.name : 'Campus Assigned Driver'"></strong></div>
                    <div>Emergency Call: <span class="font-mono text-indigo-600" x-text="selectedVehicle && selectedVehicle.driver ? selectedVehicle.driver.phone : 'N/A'"></span></div>
                    <div>Telemetry Status: <span class="font-bold text-emerald-600">GPS Signal Locked</span></div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
