@extends('layouts.app')

@section('title', 'Parent-Teacher Consultations (PTM) — PrimeOS')

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                    Parent-Teacher Partnership
                </span>
                <span class="text-xs text-slate-500">Academic Sessions</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                Parent-Teacher Consultations (PTM)
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Structured consultation schedules, appointment slot bookings, and academic progress notes.
            </p>
        </div>

        @if(Auth::user()->isAdmin())
        <div class="flex items-center space-x-3">
            <button @click="showCreateModal = true" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">
                <span>➕</span>
                <span>Schedule New PTM</span>
            </button>
        </div>
        @endif
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    @if(Auth::user()->isParent() && $myBookings->isNotEmpty())
    <!-- Parent's Active Bookings Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-indigo-200 dark:border-indigo-900 p-5 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center space-x-2 mb-3">
            <span>🗓️</span>
            <span>Your Confirmed Consultation Appointments</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($myBookings as $booking)
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        {{ \Carbon\Carbon::parse($booking->slot_time)->format('h:i A') }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                        {{ $booking->status === 'booked' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-200 text-slate-700' }}">
                        {{ $booking->status }}
                    </span>
                </div>
                <div class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ $booking->schedule->title }}
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                    <div><strong>Date:</strong> {{ $booking->schedule->meeting_date->format('M d, Y') }}</div>
                    <div><strong>Student:</strong> {{ $booking->student->name }}</div>
                    <div><strong>Faculty:</strong> {{ $booking->teacher->name }}</div>
                    @if($booking->schedule->meeting_venue)
                    <div><strong>Venue:</strong> {{ $booking->schedule->meeting_venue }}</div>
                    @endif
                </div>

                @if($booking->status === 'booked')
                <div class="pt-2 flex justify-end">
                    <form action="{{ route('school.ptm.booking.cancel', $booking->id) }}" method="POST" onsubmit="return confirm('Cancel this appointment?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400">
                            Cancel Appointment
                        </button>
                    </form>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Schedules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($schedules as $sched)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between hover:border-indigo-400 transition">
            <div class="space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ $sched->title }}
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                        {{ $sched->status === 'scheduled' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $sched->status }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <div class="flex items-center space-x-1.5">
                        <span>📅</span>
                        <span>{{ $sched->meeting_date->format('l, M d, Y') }}</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <span>⏰</span>
                        <span>{{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }} ({{ $sched->slot_duration_minutes }}m slots)</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <span>📍</span>
                        <span>{{ ucfirst($sched->mode) }} {{ $sched->meeting_venue ? '• ' . $sched->meeting_venue : '' }}</span>
                    </div>
                    @if($sched->academicClass)
                    <div class="flex items-center space-x-1.5">
                        <span>🏫</span>
                        <span>Class: <strong>{{ $sched->academicClass->display_name }}</strong></span>
                    </div>
                    @else
                    <div class="flex items-center space-x-1.5">
                        <span>🏫</span>
                        <span>All Enrolled Classes</span>
                    </div>
                    @endif
                </div>

                @if($sched->description)
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                    {{ $sched->description }}
                </p>
                @endif
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between mt-4">
                <span class="text-xs font-semibold text-slate-500">
                    {{ $sched->bookings->count() }} Bookings
                </span>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('school.ptm.show', $sched->id) }}" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        View Schedule
                    </a>

                    @if(Auth::user()->isParent())
                    <a href="{{ route('school.ptm.book', $sched->id) }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition">
                        Book Slot
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 p-12 text-center">
            <span class="text-4xl block mb-2">🗓️</span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Consultation Meetings Scheduled</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Upcoming parent-teacher meeting schedules will appear here.</p>
        </div>
        @endforelse
    </div>

    <!-- Admin Create Modal -->
    @if(Auth::user()->isAdmin())
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative" @click.away="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Schedule Parent-Teacher Consultation</h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('school.ptm.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Event Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Term Parent-Teacher Consultation" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class</label>
                        <select name="class_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                            <option value="">All Classes (School-Wide)</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Meeting Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="meeting_date" required min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time <span class="text-rose-500">*</span></label>
                        <input type="time" name="start_time" required value="09:00" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Time <span class="text-rose-500">*</span></label>
                        <input type="time" name="end_time" required value="13:00" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slot Duration <span class="text-rose-500">*</span></label>
                        <select name="slot_duration_minutes" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                            <option value="10">10 mins</option>
                            <option value="15" selected>15 mins</option>
                            <option value="20">20 mins</option>
                            <option value="30">30 mins</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Consultation Mode</label>
                        <select name="mode" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                            <option value="in_person">In-Person on Campus</option>
                            <option value="online">Online Video Meeting</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Campus Venue / Room</label>
                        <input type="text" name="meeting_venue" placeholder="e.g. Main Auditorium / Classrooms" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notes / Instructions</label>
                    <textarea name="description" rows="2" placeholder="Instructions for parents regarding parking, reports collection, etc." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Generate Schedule</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
