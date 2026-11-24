@extends('layouts.app')

@section('title', $schedule->title . ' — PTM Matrix')

@section('content')
<div class="space-y-6" x-data="{ feedbackModal: false, activeBooking: {} }">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.ptm.index') }}" class="hover:text-indigo-600">Parent-Teacher Consultations</a>
                <span>&bull;</span>
                <span>Schedule Details</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ $schedule->title }}
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                Date: <strong>{{ $schedule->meeting_date->format('l, M d, Y') }}</strong> &bull;
                Time: <strong>{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</strong> &bull;
                Mode: <strong>{{ ucfirst($schedule->mode) }}</strong>
                @if($schedule->meeting_venue)
                &bull; Venue: <strong>{{ $schedule->meeting_venue }}</strong>
                @endif
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('school.ptm.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                &larr; Back to PTM List
            </a>
            @if(Auth::user()->isParent())
            <a href="{{ route('school.ptm.book', $schedule->id) }}" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">
                Book Consultation Slot
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold">
        {{ session('success') }}
    </div>
    @endif

    <!-- Booking Matrix / Grid -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Faculty Consultation Slot Grid</h2>
                <p class="text-xs text-slate-500">Live booking status by faculty and time slot.</p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center space-x-1"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> <span>Confirmed Booking</span></span>
                <span class="inline-flex items-center space-x-1"><span class="w-3 h-3 rounded-full bg-slate-200 dark:bg-slate-700 inline-block"></span> <span>Available Slot</span></span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-3">Faculty Member</th>
                        @foreach($slots as $slot)
                        <th class="p-3 text-center whitespace-nowrap">{{ \Carbon\Carbon::parse($slot)->format('h:i A') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($teachers as $teacher)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="p-3 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <span class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-black text-xs">
                                    {{ substr($teacher->name, 0, 1) }}
                                </span>
                                <div>
                                    <div>{{ $teacher->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-normal">{{ $teacher->email }}</div>
                                </div>
                            </div>
                        </td>

                        @foreach($slots as $slot)
                        @php
                            $booking = $bookingMatrix[$teacher->id][$slot] ?? null;
                        @endphp
                        <td class="p-2 text-center align-middle">
                            @if($booking)
                            <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 text-[11px] space-y-1">
                                <div class="font-bold text-emerald-900 dark:text-emerald-300">{{ $booking->student->name }}</div>
                                <div class="text-[10px] text-emerald-700 dark:text-emerald-400">Parent: {{ $booking->parent->name }}</div>

                                @if(Auth::user()->isAdmin() || Auth::id() === $teacher->id)
                                <button type="button" 
                                    @click="activeBooking = {{ json_encode([
                                        'id' => $booking->id,
                                        'student_name' => $booking->student->name,
                                        'parent_name' => $booking->parent->name,
                                        'slot_time' => $booking->slot_time,
                                        'parent_notes' => $booking->parent_notes,
                                        'teacher_feedback' => $booking->teacher_feedback,
                                        'action_items' => $booking->action_items,
                                        'status' => $booking->status,
                                    ]) }}; feedbackModal = true;"
                                    class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 underline block w-full mt-1">
                                    {{ $booking->teacher_feedback ? 'Edit Notes' : 'Add Notes' }}
                                </button>
                                @endif
                            </div>
                            @else
                            <div class="p-2 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-[10px] text-slate-400">
                                Open
                            </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Teacher Consultation Feedback Modal -->
    <div x-show="feedbackModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative" @click.away="feedbackModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Consultation Assessment</h3>
                    <p class="text-xs text-slate-500" x-text="'Student: ' + activeBooking.student_name + ' • Parent: ' + activeBooking.parent_name"></p>
                </div>
                <button type="button" @click="feedbackModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form :action="'{{ url('school/ptm/booking') }}/' + activeBooking.id + '/feedback'" method="POST" class="space-y-3.5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Appointment Outcome</label>
                    <select name="status" x-model="activeBooking.status" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold">
                        <option value="booked">Booked (Pending)</option>
                        <option value="completed">Completed Successfully</option>
                        <option value="no_show">Parent No-Show</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div x-show="activeBooking.parent_notes" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-xs">
                    <span class="font-bold text-slate-700 dark:text-slate-300 block mb-0.5">Parent Consultation Query:</span>
                    <span class="text-slate-600 dark:text-slate-400" x-text="activeBooking.parent_notes"></span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teacher Academic Observations</label>
                    <textarea name="teacher_feedback" x-model="activeBooking.teacher_feedback" rows="3" placeholder="Key strengths, focus areas, classroom engagement..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Follow-Up Action Items</label>
                    <textarea name="action_items" x-model="activeBooking.action_items" rows="2" placeholder="Actionable steps for student & parent..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <button type="button" @click="feedbackModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Save Consultation Notes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
