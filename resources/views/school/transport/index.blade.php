@extends('layouts.app')

@section('title', 'Transport Fleet & Student Logistics — PrimeOS')

@section('content')
<div class="max-w-[1600px] mx-auto space-y-4 pb-24 sm:pb-8">

    <!-- Header Section (No banners, Title only) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2.5 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-0.5">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Logistics</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Transport Hub</span>
            </div>

            <div class="flex items-center space-x-2">
                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-car"></i>
                </span>
                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
                    Transport &amp; Fleet Logistics Hub
                </h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    Fleet Operations
                </span>
            </div>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center flex-wrap gap-1.5">
            <button type="button" onclick="openModal('add-vehicle-modal')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-truck text-[11px]"></i>
                <span>+ Vehicle</span>
            </button>
            <button type="button" onclick="openModal('add-route-modal')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-map-alt text-[11px]"></i>
                <span>+ Route</span>
            </button>
            <button type="button" onclick="openModal('add-stop-modal')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-location-pin text-[11px]"></i>
                <span>+ Stop</span>
            </button>
            <button type="button" onclick="openModal('add-staff-modal')" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1">
                <i class="ti-user text-[11px]"></i>
                <span>+ Driver/Staff</span>
            </button>
            <button type="button" onclick="openModal('assign-student-modal')" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center space-x-1 shadow-xs">
                <i class="ti-plus text-[11px]"></i>
                <span>Assign Student</span>
            </button>
            <button type="button" onclick="openModal('start-trip-modal')" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center space-x-1 shadow-xs">
                <i class="ti-control-play text-[11px]"></i>
                <span>Start Trip</span>
            </button>
            <button type="button" onclick="openModal('generate-fees-modal')" class="primary-btn" style="padding: 0.35rem 0.85rem; font-size: 0.75rem;">
                <i class="ti-receipt text-[11px]"></i>
                <span>Calculate Fees</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Small Summary Cards (Dense, moderate spacing) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Fleet Vehicles</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                {{ $stats['active_vehicles'] }} <span class="text-xs text-slate-400 font-normal">/ {{ $stats['total_vehicles'] }}</span>
            </div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-0.5">{{ $stats['active_vehicles'] }} active buses</p>
        </div>

        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Transit Routes</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ $stats['total_routes'] }}</div>
            <p class="text-[10px] text-slate-400 mt-0.5">Corridors established</p>
        </div>

        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Designated Stops</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ $stats['total_stops'] }}</div>
            <p class="text-[10px] text-slate-400 mt-0.5">Pickup &amp; drop points</p>
        </div>

        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Drivers &amp; Staff</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                {{ $stats['total_drivers'] }} <span class="text-xs text-slate-400 font-normal">/ {{ $stats['total_staff'] }}</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">Licensed crew</p>
        </div>

        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block">Active Riders</span>
            <div class="text-xl font-black text-indigo-600 mt-1 font-mono">{{ $stats['active_riders'] }}</div>
            <p class="text-[10px] text-indigo-500 font-semibold mt-0.5">{{ $stats['opted_out_riders'] }} opted out</p>
        </div>

        <div class="white-box single-summery p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Monthly Transport Dues</span>
            <div class="text-xl font-black text-emerald-600 mt-1 font-mono">₹{{ number_format($stats['total_monthly_transport_revenue'], 0) }}</div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-0.5">Calculated revenue</p>
        </div>
    </div>

    <!-- Navigation Tabs & Search Toolbar -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-2.5 shadow-2xs space-y-2.5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2">
            <!-- Tabs -->
            <div class="flex items-center space-x-1 overflow-x-auto pb-1 lg:pb-0 text-xs font-bold scrollbar-none">
                <a href="{{ route('school.transport.index', ['tab' => 'vehicles']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'vehicles' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    🚌 Vehicles ({{ $stats['total_vehicles'] }})
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'routes']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'routes' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    🛣️ Routes ({{ $stats['total_routes'] }})
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'stops']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'stops' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    🚏 Stops ({{ $stats['total_stops'] }})
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'staff']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'staff' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    👨‍✈️ Drivers &amp; Staff ({{ $stats['total_staff'] }})
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'students']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'students' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    🎒 Student Rosters ({{ $stats['active_riders'] }})
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'trips']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'trips' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    🚦 Live Trips &amp; Dispatch
                </a>
                <a href="{{ route('school.transport.index', ['tab' => 'audit']) }}"
                   class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ $tab === 'audit' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    📜 Audit Trail
                </a>
            </div>

            <!-- Report Quick Links -->
            <div class="flex items-center space-x-1.5 text-xs">
                <a href="{{ route('school.transport.reports.routes') }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold transition flex items-center space-x-1">
                    <span>📊</span>
                    <span>Route Report</span>
                </a>
                <a href="{{ route('school.transport.reports.vehicles') }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold transition flex items-center space-x-1">
                    <span>🚍</span>
                    <span>Vehicle Report</span>
                </a>
                <a href="{{ route('school.transport.reports.drivers') }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold transition flex items-center space-x-1">
                    <span>👨‍✈️</span>
                    <span>Driver Report</span>
                </a>
            </div>
        </div>

        <!-- Live Search & Filtering Form -->
        <form method="GET" action="{{ route('school.transport.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, vehicle #, license..."
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                <i class="ti-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
            </div>

            <div>
                <select name="route_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    <option value="">-- All Routes --</option>
                    @foreach($allRoutes as $r)
                        <option value="{{ $r->id }}" {{ $routeFilter == $r->id ? 'selected' : '' }}>{{ $r->route_title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="status" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    <option value="">-- All Statuses --</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="maintenance" {{ $statusFilter === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="opted_out" {{ $statusFilter === 'opted_out' ? 'selected' : '' }}>Opted Out</option>
                </select>
            </div>

            <div class="flex items-center space-x-1.5">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold transition flex-1">
                    Apply Filter
                </button>
                @if($search || $routeFilter || $statusFilter || $classFilter)
                <a href="{{ route('school.transport.index', ['tab' => $tab]) }}" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold transition">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TAB 1: VEHICLES -->
    @if($tab === 'vehicles')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Vehicle #</th>
                        <th class="p-3">Type &amp; Model</th>
                        <th class="p-3">Capacity</th>
                        <th class="p-3">Assigned Route</th>
                        <th class="p-3">Driver / Contact</th>
                        <th class="p-3">Insurance &amp; Fitness</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($vehicles as $v)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $v->vehicle_number }}
                            @if($v->year_made) <span class="text-[10px] text-slate-400 font-normal">({{ $v->year_made }})</span> @endif
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $v->vehicle_model ?? 'Standard' }}</span>
                            <span class="text-[10px] text-slate-500">{{ $v->vehicle_type ?? 'Bus' }}</span>
                        </td>
                        <td class="p-3 font-mono">
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $v->capacity }} seats</span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $v->route?->route_title ?? 'Unassigned' }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $v->driver?->name ?? $v->driver_name ?? 'No Driver' }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">{{ $v->driver?->contact_number ?? $v->driver_phone ?? 'N/A' }}</span>
                        </td>
                        <td class="p-3 text-[11px] font-mono">
                            <div>Ins: <span class="{{ $v->insurance_renewal_date && $v->insurance_renewal_date < now() ? 'text-rose-600 font-bold' : 'text-slate-600 dark:text-slate-300' }}">{{ $v->insurance_renewal_date ? $v->insurance_renewal_date->format('d M Y') : 'N/A' }}</span></div>
                            <div>Fit: <span class="{{ $v->fitness_certificate_expiry && $v->fitness_certificate_expiry < now() ? 'text-rose-600 font-bold' : 'text-slate-600 dark:text-slate-300' }}">{{ $v->fitness_certificate_expiry ? $v->fitness_certificate_expiry->format('d M Y') : 'N/A' }}</span></div>
                        </td>
                        <td class="p-3">
                            @if($v->status === 'active')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Active</span>
                            @elseif($v->status === 'maintenance')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">Maintenance</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Inactive</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" onclick="editVehicle({{ json_encode($v) }})" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white" title="Edit Vehicle">
                                    <i class="ti-pencil text-xs"></i>
                                </button>
                                <form method="POST" action="{{ route('school.transport.vehicles.destroy', $v->id) }}" onsubmit="return confirm('Decommission and remove vehicle {{ $v->vehicle_number }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600" title="Delete Vehicle">
                                        <i class="ti-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-400">No transport fleet vehicles found matching criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $vehicles->links() }}
        </div>
    </div>
    @endif

    <!-- TAB 2: ROUTES -->
    @if($tab === 'routes')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Route Title</th>
                        <th class="p-3">Corridor Points</th>
                        <th class="p-3">Distance &amp; Timing</th>
                        <th class="p-3">Stops</th>
                        <th class="p-3">Vehicles</th>
                        <th class="p-3">Riders</th>
                        <th class="p-3 text-right">Monthly Fare</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($routes as $r)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $r->route_title }}</span>
                            @if($r->description) <span class="text-[10px] text-slate-400">{{ Str::limit($r->description, 40) }}</span> @endif
                        </td>
                        <td class="p-3 text-[11px] text-slate-500">
                            {{ $r->start_point ?? 'Depot' }} &rarr; {{ $r->end_point ?? 'Campus' }}
                        </td>
                        <td class="p-3 text-[11px] font-mono">
                            <div>{{ $r->distance_km ? $r->distance_km . ' km' : 'Direct' }}</div>
                            <div class="text-[10px] text-slate-400">{{ $r->morning_timing ?? '07:00 AM' }} / {{ $r->evening_timing ?? '03:00 PM' }}</div>
                        </td>
                        <td class="p-3 font-mono font-bold">
                            <a href="{{ route('school.transport.index', ['tab' => 'stops', 'route_id' => $r->id]) }}" class="text-purple-600 hover:underline">
                                {{ $r->stops_count }} stops
                            </a>
                        </td>
                        <td class="p-3 font-mono font-bold">
                            {{ $r->vehicles_count }} fleet
                        </td>
                        <td class="p-3 font-mono">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                {{ $r->active_students_count }} students
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            ₹{{ number_format($r->fare, 2) }}
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" onclick="editRoute({{ json_encode($r) }})" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white" title="Edit Route">
                                    <i class="ti-pencil text-xs"></i>
                                </button>
                                <form method="POST" action="{{ route('school.transport.routes.destroy', $r->id) }}" onsubmit="return confirm('Delete route {{ $r->route_title }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600" title="Delete Route">
                                        <i class="ti-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-400">No transit routes found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $routes->links() }}
        </div>
    </div>
    @endif

    <!-- TAB 3: STOPS -->
    @if($tab === 'stops')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Seq #</th>
                        <th class="p-3">Stop Name &amp; Landmark</th>
                        <th class="p-3">Route</th>
                        <th class="p-3">Pickup Time</th>
                        <th class="p-3">Drop Time</th>
                        <th class="p-3">Fare Override</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($stops as $st)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                            #{{ $st->stop_sequence }}
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $st->stop_name }}</span>
                            @if($st->landmark) <span class="text-[10px] text-slate-400 font-mono">📍 {{ $st->landmark }}</span> @endif
                        </td>
                        <td class="p-3 font-bold text-purple-600 dark:text-purple-400">
                            {{ $st->route?->route_title }}
                        </td>
                        <td class="p-3 font-mono text-slate-700 dark:text-slate-300">
                            {{ $st->pickup_time ?? '07:15 AM' }}
                        </td>
                        <td class="p-3 font-mono text-slate-700 dark:text-slate-300">
                            {{ $st->drop_time ?? '03:15 PM' }}
                        </td>
                        <td class="p-3 font-mono font-bold">
                            {{ $st->fare !== null && $st->fare > 0 ? '₹' . number_format($st->fare, 2) : 'Default Route Fare' }}
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" onclick="editStop({{ json_encode($st) }})" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white" title="Edit Stop">
                                    <i class="ti-pencil text-xs"></i>
                                </button>
                                <form method="POST" action="{{ route('school.transport.stops.destroy', $st->id) }}" onsubmit="return confirm('Remove stop {{ $st->stop_name }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600" title="Delete Stop">
                                        <i class="ti-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-slate-400">No designated stops found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $stops->links() }}
        </div>
    </div>
    @endif

    <!-- TAB 4: DRIVERS & STAFF -->
    @if($tab === 'staff')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Staff Name</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">Contact</th>
                        <th class="p-3">License # &amp; Expiry</th>
                        <th class="p-3">Assigned Vehicle</th>
                        <th class="p-3">Assigned Route</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($staffMembers as $sm)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $sm->name }}</span>
                            @if($sm->address) <span class="text-[10px] text-slate-400">{{ Str::limit($sm->address, 30) }}</span> @endif
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $sm->role === 'driver' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                {{ $sm->role }}
                            </span>
                        </td>
                        <td class="p-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                            📞 {{ $sm->contact_number }}
                        </td>
                        <td class="p-3 font-mono text-[11px]">
                            <div>{{ $sm->license_number ?? 'N/A' }}</div>
                            @if($sm->license_expiry)
                            <div class="text-[10px] {{ $sm->license_expiry < now() ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                Exp: {{ $sm->license_expiry->format('d M Y') }}
                            </div>
                            @endif
                        </td>
                        <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $sm->vehicle?->vehicle_number ?? 'Unassigned' }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $sm->route?->route_title ?? $sm->vehicle?->route?->route_title ?? 'Unassigned' }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $sm->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($sm->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" onclick="editStaff({{ json_encode($sm) }})" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white" title="Edit Staff">
                                    <i class="ti-pencil text-xs"></i>
                                </button>
                                <form method="POST" action="{{ route('school.transport.staff.destroy', $sm->id) }}" onsubmit="return confirm('Remove personnel {{ $sm->name }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600" title="Delete Staff">
                                        <i class="ti-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-400">No drivers or attendants recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $staffMembers->links() }}
        </div>
    </div>
    @endif

    <!-- TAB 5: STUDENT ROSTERS & BULK ASSIGN -->
    @if($tab === 'students')
    <div class="space-y-3">
        <!-- Bulk Action Bar -->
        <form method="POST" action="{{ route('school.transport.students.bulk_assign') }}" id="bulk-assign-form" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 shadow-2xs space-y-2.5">
            @csrf
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-black text-slate-900 dark:text-white">Bulk Assign Unregistered Students</span>
                    <span class="text-[10px] text-slate-400">Select unassigned students below to batch enroll them into transit routes</span>
                </div>
                <div class="text-xs text-purple-600 font-bold" id="selected-count-badge">0 students selected</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Target Route *</label>
                    <select name="route_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        <option value="">-- Choose Route --</option>
                        @foreach($allRoutes as $r)
                        <option value="{{ $r->id }}">{{ $r->route_title }} (₹{{ number_format($r->fare, 2) }}/mo)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Waiver %</label>
                    <input type="number" step="0.01" min="0" max="100" name="waiver_percentage" placeholder="0%" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Flat Discount (₹)</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" placeholder="₹0.00" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition shadow-xs text-xs">
                        Execute Bulk Assign
                    </button>
                </div>
            </div>

            <!-- Unassigned Student Selector Quick Chips -->
            @if($unassignedStudents->isNotEmpty())
            <div class="pt-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Unassigned Students Queue (Click to select/unselect):</span>
                <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto p-1.5 bg-slate-50 dark:bg-slate-800/40 rounded-lg">
                    @foreach($unassignedStudents as $us)
                    <label class="inline-flex items-center space-x-1.5 px-2 py-1 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] cursor-pointer hover:border-purple-400 transition">
                        <input type="checkbox" name="student_ids[]" value="{{ $us->id }}" onchange="updateSelectedCount()" class="student-checkbox rounded text-purple-600 focus:ring-purple-500">
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $us->name }}</span>
                        <span class="text-[9px] text-slate-400 font-mono">({{ $us->currentClass?->display_name ?? 'No Class' }})</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
        </form>

        <!-- Student Transport Assignments Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Student &amp; Class</th>
                            <th class="p-3">Route &amp; Stop</th>
                            <th class="p-3">Bus # &amp; Driver Contact</th>
                            <th class="p-3">Start Date</th>
                            <th class="p-3">Monthly Fare</th>
                            <th class="p-3">Waiver / Discount</th>
                            <th class="p-3">Effective Dues</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($studentAssignments as $sa)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <a href="{{ route('school.students.show', $sa->student_id) }}" class="font-bold text-slate-900 dark:text-white hover:text-purple-600 transition block">
                                    {{ $sa->student?->name ?? 'Unknown Student' }}
                                </a>
                                <span class="text-[10px] text-slate-500 font-mono">{{ $sa->student?->admission_number }} • {{ $sa->student?->currentClass?->display_name ?? 'N/A' }}</span>
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-purple-600 dark:text-purple-400 block">{{ $sa->route?->route_title }}</span>
                                <span class="text-[10px] text-slate-500">🚏 {{ $sa->stop?->stop_name ?? 'Depot' }} ({{ $sa->stop?->pickup_time ?? '07:15 AM' }})</span>
                            </td>
                            <td class="p-3 font-mono text-[11px]">
                                <div class="font-bold text-blue-600 dark:text-blue-400">{{ $sa->vehicle?->vehicle_number ?? 'Auto-Dispatch' }}</div>
                                <div class="text-[10px] text-slate-400">📞 {{ $sa->vehicle?->driver?->contact_number ?? $sa->vehicle?->driver_phone ?? 'School Dispatch' }}</div>
                            </td>
                            <td class="p-3 font-mono text-[11px] text-slate-500">
                                {{ $sa->start_date ? $sa->start_date->format('d M Y') : 'Active' }}
                            </td>
                            <td class="p-3 font-mono text-slate-800 dark:text-slate-200">
                                ₹{{ number_format($sa->monthly_fare, 2) }}
                            </td>
                            <td class="p-3 font-mono text-[11px]">
                                @if($sa->waiver_percentage > 0)
                                    <span class="text-emerald-600 font-bold block">{{ $sa->waiver_percentage }}% waiver</span>
                                @endif
                                @if($sa->discount_amount > 0)
                                    <span class="text-amber-600 font-bold block">-₹{{ number_format($sa->discount_amount, 2) }}</span>
                                @endif
                                @if($sa->waiver_percentage <= 0 && $sa->discount_amount <= 0)
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                ₹{{ number_format($sa->effective_fare, 2) }}
                            </td>
                            <td class="p-3">
                                @if($sa->status === 'active')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800">Opted Out</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    @if($sa->status === 'active')
                                    <form method="POST" action="{{ route('school.transport.students.opt_out', $sa->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Opt out student {{ $sa->student?->name }} from transport?');" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold">
                                            Opt Out
                                        </button>
                                    </form>
                                    @else
                                    <form method="POST" action="{{ route('school.transport.students.opt_in', $sa->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                                            Reactivate
                                        </button>
                                    </form>
                                    @endif

                                    <form method="POST" action="{{ route('school.transport.students.destroy', $sa->id) }}" onsubmit="return confirm('Delete this transport assignment?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded hover:bg-rose-50 text-slate-400 hover:text-rose-600" title="Delete Assignment">
                                            <i class="ti-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-400">No student transport assignments recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                {{ $studentAssignments->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- TAB 6: TRIPS & DISPATCH -->
    @if($tab === 'trips')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Trip ID</th>
                        <th class="p-3">Date &amp; Type</th>
                        <th class="p-3">Route</th>
                        <th class="p-3">Bus # &amp; Driver</th>
                        <th class="p-3">Passengers Manifest</th>
                        <th class="p-3">Timestamps</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($trips as $tp)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                            #TRP-{{ str_pad($tp->id, 4, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $tp->trip_date->format('d M Y') }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $tp->trip_type === 'pickup' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $tp->trip_type }} Run
                            </span>
                        </td>
                        <td class="p-3 font-bold text-purple-600 dark:text-purple-400">
                            {{ $tp->route?->route_title }}
                        </td>
                        <td class="p-3 font-mono text-[11px]">
                            <div class="font-bold text-blue-600 dark:text-blue-400">{{ $tp->vehicle?->vehicle_number ?? 'Auto Bus' }}</div>
                            <div class="text-[10px] text-slate-500">{{ $tp->driver?->name ?? 'Designated Driver' }}</div>
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $tp->passengers_count }} students</span>
                            <div class="flex items-center space-x-1 mt-0.5">
                                <span class="text-[10px] text-emerald-600 font-bold">✓ {{ $tp->passengers->where('status', 'boarded')->count() }} boarded</span>
                                <span class="text-[10px] text-slate-400">•</span>
                                <span class="text-[10px] text-rose-600 font-bold">✗ {{ $tp->passengers->where('status', 'absent')->count() }} absent</span>
                            </div>
                        </td>
                        <td class="p-3 font-mono text-[11px] text-slate-500">
                            <div>Start: {{ $tp->started_at ? $tp->started_at->format('H:i') : 'Scheduled' }}</div>
                            <div>End: {{ $tp->ended_at ? $tp->ended_at->format('H:i') : '--:--' }}</div>
                        </td>
                        <td class="p-3">
                            @if($tp->status === 'in_transit')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 animate-pulse">In Transit</span>
                            @elseif($tp->status === 'completed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">Completed</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">{{ ucfirst($tp->status) }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                @if($tp->status === 'in_transit')
                                <form method="POST" action="{{ route('school.transport.trips.end', $tp->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-[11px] font-bold">
                                        End Trip
                                    </button>
                                </form>
                                @endif
                                <button type="button" onclick="document.getElementById('trip-passengers-{{ $tp->id }}').classList.toggle('hidden')" class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">
                                    Manifest
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Expandable Manifest Drawer -->
                    <tr id="trip-passengers-{{ $tp->id }}" class="hidden bg-slate-50/50 dark:bg-slate-800/30">
                        <td colspan="8" class="p-4">
                            <div class="space-y-2">
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">Trip Passenger Attendance &amp; Boarding Confirmation</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    @foreach($tp->passengers as $p)
                                    <div class="p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-white block">{{ $p->student?->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $p->stop?->stop_name ?? 'Default Stop' }}</span>
                                        </div>
                                        <form method="POST" action="{{ route('school.transport.passengers.status', $p->id) }}" class="flex items-center space-x-1">
                                            @csrf
                                            <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold rounded px-1.5 py-0.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                                                <option value="pending" {{ $p->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="boarded" {{ $p->status === 'boarded' ? 'selected' : '' }}>Boarded</option>
                                                <option value="dropped" {{ $p->status === 'dropped' ? 'selected' : '' }}>Dropped</option>
                                                <option value="absent" {{ $p->status === 'absent' ? 'selected' : '' }}>Absent</option>
                                            </select>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-6 text-center text-slate-400">No dispatch trips registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $trips->links() }}
        </div>
    </div>
    @endif

    <!-- TAB 7: AUDIT TRAIL -->
    @if($tab === 'audit')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                    <tr>
                        <th class="p-3">Timestamp</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Entity</th>
                        <th class="p-3">Details</th>
                        <th class="p-3 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($audits as $au)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition font-mono text-[11px]">
                        <td class="p-3 text-slate-500">
                            {{ $au->created_at ? $au->created_at->format('d M Y, H:i') : 'Just now' }}
                        </td>
                        <td class="p-3 font-bold text-slate-800 dark:text-slate-200">
                            {{ $au->user?->name ?? 'System' }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $au->action }}
                            </span>
                        </td>
                        <td class="p-3 text-purple-600 dark:text-purple-400 font-bold">
                            {{ $au->entity_type }} #{{ $au->entity_id ?? '-' }}
                        </td>
                        <td class="p-3 font-sans text-slate-700 dark:text-slate-300">
                            {{ $au->details }}
                        </td>
                        <td class="p-3 text-right text-slate-400">
                            {{ $au->ip_address }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-slate-400">No transport audit trail events recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $audits->links() }}
        </div>
    </div>
    @endif

</div>

<!-- ======================================================== -->
<!-- MODALS SECTION -->
<!-- ======================================================== -->

<!-- Modal: Add Vehicle -->
<div id="add-vehicle-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Register Fleet Vehicle</h3>
            <button onclick="closeModal('add-vehicle-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.vehicles.store') }}" class="space-y-2.5 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" placeholder="DL-01-AB-1234" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Type *</label>
                    <select name="vehicle_type" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="Bus">Standard Bus</option>
                        <option value="Minibus">Mini Bus</option>
                        <option value="Van">Transport Van</option>
                        <option value="Cab">Cab / Car</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Passenger Capacity (Seats) *</label>
                    <input type="number" name="capacity" value="40" required min="1" max="150" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" placeholder="Tata Starbus 40" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Insurance Renewal Date</label>
                    <input type="date" name="insurance_renewal_date" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Fitness Certificate Expiry</label>
                    <input type="date" name="fitness_certificate_expiry" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Driver</label>
                    <select name="driver_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- No Driver Assigned --</option>
                        @foreach($allDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->contact_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Route</label>
                    <select name="route_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Unassigned --</option>
                        @foreach($allRoutes as $r)
                        <option value="{{ $r->id }}">{{ $r->route_title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                    <select name="status" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="active">Active</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Year Made</label>
                    <input type="text" name="year_made" placeholder="2025" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('add-vehicle-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Vehicle -->
<div id="edit-vehicle-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Edit Fleet Vehicle</h3>
            <button onclick="closeModal('edit-vehicle-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" id="edit-vehicle-form" class="space-y-2.5 text-xs">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" id="edit-v-number" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Type *</label>
                    <select name="vehicle_type" id="edit-v-type" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="Bus">Standard Bus</option>
                        <option value="Minibus">Mini Bus</option>
                        <option value="Van">Transport Van</option>
                        <option value="Cab">Cab / Car</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Capacity (Seats) *</label>
                    <input type="number" name="capacity" id="edit-v-capacity" required min="1" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" id="edit-v-model" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Insurance Renewal</label>
                    <input type="date" name="insurance_renewal_date" id="edit-v-insurance" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Fitness Expiry</label>
                    <input type="date" name="fitness_certificate_expiry" id="edit-v-fitness" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Driver</label>
                    <select name="driver_id" id="edit-v-driver" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- No Driver --</option>
                        @foreach($allDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Route</label>
                    <select name="route_id" id="edit-v-route" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Unassigned --</option>
                        @foreach($allRoutes as $r)
                        <option value="{{ $r->id }}">{{ $r->route_title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                <select name="status" id="edit-v-status" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('edit-vehicle-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Update Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Route -->
<div id="add-route-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Register Transit Route</h3>
            <button onclick="closeModal('add-route-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.routes.store') }}" class="space-y-2.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Route Title *</label>
                <input type="text" name="route_title" placeholder="e.g. North Campus Corridor" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Start Point</label>
                    <input type="text" name="start_point" placeholder="Sector 18 Metro" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">End Point</label>
                    <input type="text" name="end_point" placeholder="Main School Gate" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Distance (km)</label>
                    <input type="number" step="0.1" name="distance_km" placeholder="14.5" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Monthly Fare (₹) *</label>
                    <input type="number" step="0.01" name="fare" placeholder="1500.00" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Morning Pickup</label>
                    <input type="text" name="morning_timing" placeholder="06:45 AM - 07:45 AM" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Evening Drop</label>
                    <input type="text" name="evening_timing" placeholder="02:30 PM - 03:30 PM" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                <select name="status" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('add-route-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Save Route</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Stop -->
<div id="add-stop-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Add Route Stop</h3>
            <button onclick="closeModal('add-stop-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.stops.store') }}" class="space-y-2.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Link to Route *</label>
                <select name="route_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Select Route --</option>
                    @foreach($allRoutes as $r)
                    <option value="{{ $r->id }}" {{ $routeFilter == $r->id ? 'selected' : '' }}>{{ $r->route_title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Stop Name *</label>
                    <input type="text" name="stop_name" placeholder="Civil Lines Crossing" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Sequence # *</label>
                    <input type="number" name="stop_sequence" value="1" min="1" max="100" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pickup Time</label>
                    <input type="text" name="pickup_time" placeholder="07:15 AM" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Drop Time</label>
                    <input type="text" name="drop_time" placeholder="03:15 PM" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Stop Fare Override (₹)</label>
                    <input type="number" step="0.01" name="fare" placeholder="Leave empty for route fare" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Landmark</label>
                    <input type="text" name="landmark" placeholder="Near Post Office" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('add-stop-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Save Stop</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Staff (Driver / Attendant) -->
<div id="add-staff-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Register Transport Staff</h3>
            <button onclick="closeModal('add-staff-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.staff.store') }}" class="space-y-2.5 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Rajesh Kumar" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                    <select name="role" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="driver">Driver</option>
                        <option value="attendant">Bus Attendant</option>
                        <option value="conductor">Conductor</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Contact Number *</label>
                    <input type="text" name="contact_number" required placeholder="+91 9876543210" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">License Number</label>
                    <input type="text" name="license_number" placeholder="DL-042011002345" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">License Expiry</label>
                    <input type="date" name="license_expiry" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                    <select name="status" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="active">Active</option>
                        <option value="on_leave">On Leave</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assign to Vehicle</label>
                    <select name="assigned_vehicle_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                        <option value="">-- None --</option>
                        @foreach($allVehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->vehicle_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assign to Route</label>
                    <select name="assigned_route_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- None --</option>
                        @foreach($allRoutes as $r)
                        <option value="{{ $r->id }}">{{ $r->route_title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('add-staff-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Save Staff</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Single Student -->
<div id="assign-student-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Assign Student to Transit</h3>
            <button onclick="closeModal('assign-student-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.students.assign') }}" class="space-y-2.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Student *</label>
                <select name="student_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Choose Student --</option>
                    @foreach($unassignedStudents as $us)
                    <option value="{{ $us->id }}">{{ $us->name }} ({{ $us->admission_number }} - {{ $us->currentClass?->display_name }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Transit Route *</label>
                <select name="route_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Choose Route --</option>
                    @foreach($allRoutes as $r)
                    <option value="{{ $r->id }}">{{ $r->route_title }} (₹{{ number_format($r->fare, 2) }}/mo)</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Stop (Optional)</label>
                    <select name="stop_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Route Default --</option>
                        @foreach($stops as $st)
                        <option value="{{ $st->id }}">{{ $st->route?->route_title }} &rarr; {{ $st->stop_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Vehicle (Optional)</label>
                    <select name="vehicle_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                        <option value="">-- Auto-Assign --</option>
                        @foreach($allVehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->vehicle_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Waiver %</label>
                    <input type="number" step="0.01" min="0" max="100" name="waiver_percentage" placeholder="0%" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Direct Discount (₹)</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" placeholder="₹0.00" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="pt-1">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="generate_invoice" value="1" checked class="rounded text-purple-600">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Auto-generate current month transport fee invoice (no fines)</span>
                </label>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('assign-student-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Start Trip -->
<div id="start-trip-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Dispatch Live Transit Trip</h3>
            <button onclick="closeModal('start-trip-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.trips.store') }}" class="space-y-2.5 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Trip Date *</label>
                    <input type="date" name="trip_date" value="{{ date('Y-m-d') }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Trip Type *</label>
                    <select name="trip_type" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="pickup">Morning Pickup</option>
                        <option value="drop">Afternoon Drop</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Route Corridor *</label>
                <select name="route_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Choose Route --</option>
                    @foreach($allRoutes as $r)
                    <option value="{{ $r->id }}">{{ $r->route_title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Fleet Vehicle</label>
                    <select name="vehicle_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                        <option value="">-- Route Default Bus --</option>
                        @foreach($allVehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->vehicle_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Driver</label>
                    <select name="driver_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Vehicle Default Driver --</option>
                        @foreach($allDrivers as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <p class="text-[11px] text-slate-400">All registered active students on this route will automatically be loaded onto the dispatch manifest.</p>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('start-trip-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Launch Trip</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Generate Fees -->
<div id="generate-fees-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 mb-3">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">Calculate Monthly Transport Invoices</h3>
            <button onclick="closeModal('generate-fees-modal')" class="text-slate-400 hover:text-white text-xs">✕</button>
        </div>

        <form method="POST" action="{{ route('school.transport.fees.generate') }}" class="space-y-2.5 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Billing Month (YYYY-MM) *</label>
                <input type="month" name="billing_month" value="{{ date('Y-m') }}" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Route</label>
                <select name="route_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- All Active Routes --</option>
                    @foreach($allRoutes as $r)
                    <option value="{{ $r->id }}">{{ $r->route_title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Invoice Due Date</label>
                <input type="date" name="due_date" value="{{ date('Y-m-15') }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
            </div>

            <p class="text-[11px] text-emerald-600 font-semibold">Invoices will be auto-linked to student fee ledgers with applied waivers/discounts. Late fines are strictly zero (no fines).</p>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeModal('generate-fees-modal')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.35rem 1rem; font-size: 0.75rem;">Generate Invoices</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.student-checkbox:checked').length;
    document.getElementById('selected-count-badge').textContent = `${count} students selected`;
}

function editVehicle(v) {
    document.getElementById('edit-vehicle-form').action = `/school/transport/vehicles/${v.id}`;
    document.getElementById('edit-v-number').value = v.vehicle_number || '';
    document.getElementById('edit-v-type').value = v.vehicle_type || 'Bus';
    document.getElementById('edit-v-capacity').value = v.capacity || 40;
    document.getElementById('edit-v-model').value = v.vehicle_model || '';
    document.getElementById('edit-v-insurance').value = v.insurance_renewal_date ? v.insurance_renewal_date.substring(0, 10) : '';
    document.getElementById('edit-v-fitness').value = v.fitness_certificate_expiry ? v.fitness_certificate_expiry.substring(0, 10) : '';
    document.getElementById('edit-v-driver').value = v.driver_id || '';
    document.getElementById('edit-v-route').value = v.route_id || '';
    document.getElementById('edit-v-status').value = v.status || 'active';
    openModal('edit-vehicle-modal');
}
</script>
@endsection
