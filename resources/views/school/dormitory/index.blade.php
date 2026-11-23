@extends('layouts.app')

@section('title', 'Hostel & Dormitory — ' . ($school['name'] ?? 'Infix LMS'))

@section('content')
<div class="max-w-[1600px] mx-auto space-y-5 pb-24 sm:pb-8">

    <!-- Infix Breadcrumbs & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-500">Residential</span>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">Dormitory &amp; Hostel</span>
            </div>

            <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm shadow-xs" style="background: var(--system_primary_gradient);">
                    <i class="ti-home"></i>
                </span>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Dormitory &amp; Hostel Facility
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                    Infix Hostel
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Manage campus boarding facilities, dormitory room allocations, bed capacities, and residential tariffs.
            </p>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center flex-wrap gap-2">
            <button type="button" 
                    onclick="document.getElementById('add-room-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-key text-xs"></i>
                <span>Add Room</span>
            </button>
            <button type="button" onclick="document.getElementById('add-assignment-modal').classList.remove('hidden')" class="px-3 py-1.5 rounded-xl bg-cyan-600 text-white text-xs font-bold">
                <i class="ti-user"></i> Allocate Bed
            </button>
            <button type="button" 
                    onclick="document.getElementById('add-dorm-modal').classList.remove('hidden')"
                    class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">
                <i class="ti-plus text-xs"></i>
                <span>Add Dormitory</span>
            </button>
        </div>
        @endif
    </div>

    <!-- Infix Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hostels / Dormitories</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono">{{ $stats['total_dormitories'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Residential campus buildings</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider block">Total Rooms</span>
            <div class="text-2xl font-black text-purple-600 mt-1.5 font-mono">{{ $stats['total_rooms'] }}</div>
            <p class="text-[10px] text-slate-400 mt-1">Configured residential rooms</p>
        </div>
        <div class="white-box single-summery p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Bed Capacity</span>
            <div class="text-2xl font-black text-emerald-600 mt-1.5 font-mono">{{ $stats['total_beds'] }}</div>
            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Total boarding capacity</p>
        </div>
        <div class="white-box p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-cyan-600 uppercase tracking-wider">Occupied beds today</span>
            <div class="text-2xl font-black text-cyan-600 mt-1.5">{{ $stats['occupied_beds'] }}</div>
        </div>
        <div class="white-box p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Checked-in residents</span>
            <div class="text-2xl font-black text-amber-600 mt-1.5">{{ $stats['active_residents'] }}</div>
        </div>
    </div>

    <!-- Dormitories and Rooms Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Dormitories List -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span>🏢</span> Dormitory Blocks
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Dormitory Name</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Rooms</th>
                            <th class="p-3 text-right">Intake Capacity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($dormitories as $d)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $d->dormitory_name }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $d->type }}
                                </span>
                            </td>
                            <td class="p-3 font-mono">
                                {{ $d->rooms->count() }} rooms
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ $d->intake_capacity }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">No dormitories registered yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $dormitories->links() }}
            </div>
        </div>

        <!-- Rooms List -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-3">
            <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span>🛏️</span> Room Inventory &amp; Tariffs
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3">Room #</th>
                            <th class="p-3">Dormitory</th>
                            <th class="p-3">Beds</th>
                            <th class="p-3 text-right">Cost / Bed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($rooms as $rm)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3 font-mono font-bold text-purple-600 dark:text-purple-400">
                                {{ $rm->room_number }}
                                <span class="text-[10px] text-slate-400 block capitalize">{{ $rm->room_type }}</span>
                            </td>
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $rm->dormitory?->dormitory_name ?? 'N/A' }}
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $rm->number_of_beds }} beds
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                ₹{{ number_format($rm->cost_per_bed, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-slate-400">No rooms configured yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                {{ $rooms->links() }}
            </div>
        </div>
    </div>

    <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <div class="flex items-center justify-between"><h2 class="text-sm font-black text-slate-900 dark:text-white">Resident Allocations &amp; Check-in / Check-out</h2><span class="text-xs text-slate-500">{{ $assignments->total() }} records</span></div>
        <div class="overflow-x-auto"><table class="w-full text-xs text-left"><thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 uppercase"><tr><th class="p-3">Student</th><th class="p-3">Room / Bed</th><th class="p-3">Stay</th><th class="p-3">Monthly fee</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($assignments as $assignment)<tr><td class="p-3 font-semibold">{{ $assignment->student?->name ?? 'Student unavailable' }}<span class="block text-slate-400">{{ $assignment->student?->admission_number ?? $assignment->student?->student_id_number ?? '' }}</span></td><td class="p-3">{{ $assignment->room?->dormitory?->dormitory_name }} / {{ $assignment->room?->room_number }}<span class="block text-slate-400">Bed {{ $assignment->bed_number }}</span></td><td class="p-3">{{ $assignment->start_date?->format('d M Y') }} – {{ $assignment->end_date?->format('d M Y') ?? 'Open-ended' }}</td><td class="p-3">₹{{ number_format((float)$assignment->monthly_fee, 2) }}</td><td class="p-3"><span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800">{{ ucfirst($assignment->status) }}</span></td><td class="p-3">@if(Auth::user()->isAdmin())<div class="flex flex-wrap gap-1">
        @if($assignment->status === 'reserved')<form method="POST" action="{{ route('school.dormitory.assignments.check-in', $assignment->id) }}">@csrf<button class="px-2 py-1 rounded bg-emerald-600 text-white">Check in</button></form>@endif
        @if($assignment->status === 'active')<form method="POST" action="{{ route('school.dormitory.assignments.check-out', $assignment->id) }}">@csrf<button class="px-2 py-1 rounded bg-amber-600 text-white">Check out</button></form>@endif
        @if(in_array($assignment->status, ['reserved','active']))<form method="POST" action="{{ route('school.dormitory.assignments.cancel', $assignment->id) }}" onsubmit="return confirm('Cancel this residential assignment?')">@csrf<button class="px-2 py-1 rounded bg-rose-600 text-white">Cancel</button></form>@endif
        </div>@endif</td></tr>@empty<tr><td colspan="6" class="p-6 text-center text-slate-400">No student bed assignments yet.</td></tr>@endforelse
        </tbody></table></div>{{ $assignments->links() }}
    </section>

</div>

<!-- Modal: Add Dormitory -->
@if(Auth::user()->isAdmin())
<div id="add-dorm-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Register Dormitory Block</h3>
            <button onclick="document.getElementById('add-dorm-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.dormitory.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Dormitory Name *</label>
                <input type="text" name="dormitory_name" placeholder="Tagore House / Main Hostel" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Type *</label>
                    <select name="type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="boys">Boys Hostel</option>
                        <option value="girls">Girls Hostel</option>
                        <option value="mixed">Co-ed / Mixed</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Intake Capacity</label>
                    <input type="number" name="intake_capacity" value="100" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div><label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Warden Name</label><input name="warden_name" maxlength="150" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800" /></div>
                <div><label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Warden Phone</label><input name="warden_phone" maxlength="30" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800" /></div>
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Campus Location / Address</label>
                <input type="text" name="address" placeholder="East Campus Wing, Block B" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-dorm-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Save Dormitory</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Room -->
<div id="add-room-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Configure Dormitory Room</h3>
            <button onclick="document.getElementById('add-room-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.dormitory.rooms.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Dormitory Block *</label>
                <select name="dormitory_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    @foreach($dormitories as $d)
                    <option value="{{ $d->id }}">{{ $d->dormitory_name }} ({{ strtoupper($d->type) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Room Number *</label>
                    <input type="text" name="room_number" placeholder="RM-204" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Room Type</label>
                    <select name="room_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                        <option value="single">Single Bed</option>
                        <option value="double" selected>Double Bed</option>
                        <option value="dorm">Dormitory 4+ Bed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Number of Beds *</label>
                    <input type="number" name="number_of_beds" value="2" min="1" max="20" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Cost Per Bed (₹) *</label>
                    <input type="number" step="0.01" name="cost_per_bed" placeholder="3500.00" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-room-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Cancel</button>
                <button type="submit" class="primary-btn" style="padding: 0.4rem 1.1rem; font-size: 0.75rem;">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Allocate a bed -->
<div id="add-assignment-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden p-4 overflow-y-auto">
 <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl max-w-lg w-full space-y-4">
  <div class="flex justify-between"><h3 class="font-bold text-slate-900 dark:text-white">Reserve Student Bed</h3><button type="button" onclick="document.getElementById('add-assignment-modal').classList.add('hidden')">✕</button></div>
  <form method="POST" action="{{ route('school.dormitory.assignments.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">@csrf
   <div class="sm:col-span-2"><label class="block font-bold mb-1">Student *</label><select name="student_id" required class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number ?? $student->student_id_number ?? ('ID '.$student->id) }}</option>@endforeach</select></div>
   <div class="sm:col-span-2"><label class="block font-bold mb-1">Room *</label><select name="dormitory_room_id" required class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"><option value="">Select room</option>@foreach($activeRooms as $room)<option value="{{ $room->id }}">{{ $room->dormitory?->dormitory_name }} — {{ $room->room_number }} ({{ $room->number_of_beds }} beds)</option>@endforeach</select></div>
   <div><label class="block font-bold mb-1">Bed Number *</label><input name="bed_number" type="number" min="1" required class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"></div>
   <div><label class="block font-bold mb-1">Monthly Fee (₹) *</label><input name="monthly_fee" type="number" min="0" step="0.01" required class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"></div>
   <div><label class="block font-bold mb-1">Start Date *</label><input name="start_date" type="date" min="{{ today()->toDateString() }}" required class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"></div>
   <div><label class="block font-bold mb-1">End Date</label><input name="end_date" type="date" class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"></div>
   <div class="sm:col-span-2"><label class="block font-bold mb-1">Notes</label><textarea name="notes" rows="2" class="w-full p-2 rounded-lg border dark:bg-slate-800 dark:border-slate-700"></textarea></div>
   <div class="sm:col-span-2 flex justify-end"><button type="submit" class="primary-btn">Reserve Bed</button></div>
  </form>
 </div>
</div>
@endif
@endsection
