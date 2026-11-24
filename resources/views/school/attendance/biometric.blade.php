@extends('layouts.app')

@section('title', 'Biometric Hardware & Punch Logs — PrimeOS')

@section('content')
<div class="space-y-6" x-data="{ addDeviceModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-300">
                    Hardware Bridge
                </span>
                <span class="text-xs text-slate-500">ADMS / eSSL / ZKTeco Push Gateway</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                Biometric Hardware &amp; Real-time Attendance
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Connected turnstiles, biometric punch streams, automated roll-call synchronization, and absentee parent alerts.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button @click="addDeviceModal = true" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold transition shadow-xs">
                <span>➕</span>
                <span>Register Machine</span>
            </button>

            <form action="{{ route('school.attendance.biometric.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                    <span>⚡</span>
                    <span>Process Queue</span>
                </button>
            </form>

            <form action="{{ route('school.attendance.biometric.absentee-alerts') }}" method="POST" class="inline" onsubmit="return confirm('Send automated morning absence alerts to all parents of absent scholars?');">
                @csrf
                <button type="submit" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition shadow-xs">
                    <span>📢</span>
                    <span>Send Absentee Alerts</span>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    <!-- Stat Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Total Machines</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_devices'] }}</div>
            <span class="text-[11px] text-teal-600 dark:text-teal-400 font-semibold mt-0.5 block">{{ $stats['online_devices'] }} Active Online</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Punches Today</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($stats['today_punches']) }}</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Campus In/Out</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Pending Queue</span>
            <div class="text-2xl font-black text-amber-500 mt-1">{{ number_format($stats['pending_punches']) }}</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Awaiting sync</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">Unmatched / Failed</span>
            <div class="text-2xl font-black text-rose-500 mt-1">{{ number_format($stats['failed_punches']) }}</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Invalid PIN/ID</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">API Protocol</span>
            <div class="text-lg font-black text-slate-900 dark:text-white mt-1">REST / ADMS</div>
            <span class="text-[11px] text-emerald-600 font-semibold mt-0.5 block">v1/biometric/push</span>
        </div>
    </div>

    <!-- Registered Biometric Hardware Devices -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Registered Physical Devices</h2>
                <p class="text-xs text-slate-500">Biometric punch clocks, facial recognition terminals, and RFID turnstiles.</p>
            </div>
            <span class="text-xs font-bold text-slate-500">{{ $devices->count() }} devices</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">Device Name &amp; Serial</th>
                        <th class="p-3">Model / Hardware</th>
                        <th class="p-3">Location</th>
                        <th class="p-3">Status / Heartbeat</th>
                        <th class="p-3">Auth Token</th>
                        <th class="p-3">Total Logs</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($devices as $dev)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 font-bold text-slate-900 dark:text-white">
                            <div>{{ $dev->device_name }}</div>
                            <div class="text-[10px] font-mono text-slate-400">SN: {{ $dev->device_serial }}</div>
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">
                            {{ $dev->device_model ?? 'Standard ADMS Terminal' }}
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">
                            📍 {{ $dev->device_location ?? 'Campus Entry' }}
                        </td>
                        <td class="p-3">
                            @if($dev->isOnline())
                            <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Online</span>
                            </span>
                            @else
                            <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span>Offline</span>
                            </span>
                            @endif
                            @if($dev->last_heartbeat)
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $dev->last_heartbeat->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="p-3">
                            <code class="text-[10px] px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono select-all">
                                {{ substr($dev->auth_token, 0, 12) }}...
                            </code>
                        </td>
                        <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">
                            {{ number_format($dev->logs_count) }}
                        </td>
                        <td class="p-3 text-right">
                            <form action="{{ route('school.attendance.biometric.devices.destroy', $dev->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove device registration?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold text-xs">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No physical biometric devices registered yet. Click "Register Machine" to add your first turnstile or punch clock.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Punch Logs Stream -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Live Biometric Punch Stream</h2>
                <p class="text-xs text-slate-500">Real-time punch transactions processed into daily attendance.</p>
            </div>

            <!-- Filters -->
            <form action="{{ route('school.attendance.biometric') }}" method="GET" class="flex items-center space-x-2">
                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                <select name="status" class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs font-semibold">
                    <option value="all">All Statuses</option>
                    <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Processed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed / Unmatched</option>
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-xs font-bold">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">Punch Time</th>
                        <th class="p-3">Biometric PIN / ID</th>
                        <th class="p-3">Identified User</th>
                        <th class="p-3">Terminal / Device</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Processing Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 whitespace-nowrap font-semibold text-slate-900 dark:text-white">
                            {{ $log->punch_time->format('h:i:s A • M d') }}
                        </td>
                        <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ $log->biometric_pin }}
                        </td>
                        <td class="p-3">
                            @if($log->user)
                            <div class="font-bold text-slate-900 dark:text-white">{{ $log->user->name }}</div>
                            <div class="text-[10px] text-slate-500 uppercase">{{ $log->user->role }} &bull; {{ $log->user->admission_number ?? 'Staff' }}</div>
                            @else
                            <span class="text-rose-500 text-[11px] font-semibold">Unmatched PIN</span>
                            @if($log->error_message)
                            <div class="text-[10px] text-slate-400">{{ $log->error_message }}</div>
                            @endif
                            @endif
                        </td>
                        <td class="p-3 text-slate-600 dark:text-slate-400">
                            {{ $log->device->device_name ?? 'API Gateway Direct' }}
                        </td>
                        <td class="p-3 font-bold uppercase text-[10px]">
                            <span class="px-2 py-0.5 rounded {{ $log->punch_type === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $log->punch_type }}
                            </span>
                        </td>
                        <td class="p-3">
                            @if($log->status === 'processed')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                ✓ Synced
                            </span>
                            @elseif($log->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                ⏳ Queued
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                ✕ Failed
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            No biometric punch records matching the selected filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Register Device Modal -->
    <div x-show="addDeviceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative" @click.away="addDeviceModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Register Biometric Machine</h3>
                <button type="button" @click="addDeviceModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('school.attendance.biometric.devices.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Device Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="device_name" required placeholder="e.g. Main Gate Turnstile 1" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Device Serial Number (Unique) <span class="text-rose-500">*</span></label>
                    <input type="text" name="device_serial" required placeholder="e.g. ZK-K40-982341" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-mono font-semibold focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Model / Brand</label>
                        <input type="text" name="device_model" placeholder="e.g. eSSL MB20 / ZKTeco" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Device IP (LAN)</label>
                        <input type="text" name="device_ip" placeholder="e.g. 192.168.1.150" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Physical Location</label>
                    <input type="text" name="device_location" placeholder="e.g. Campus North Gate / Library Entrance" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="addDeviceModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold">Register Device</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
