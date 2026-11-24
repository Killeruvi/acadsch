@extends('layouts.app')

@section('title', 'Book Consultation — ' . $schedule->title)

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ selectedChild: '{{ $children->first()?->id }}', selectedTeacher: '', selectedSlot: '' }">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('school.ptm.index') }}" class="hover:text-indigo-600">Parent-Teacher Consultations</a>
                <span>&bull;</span>
                <span>Appointment Booking</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Book Consultation Slot
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                {{ $schedule->title }} &bull; {{ $schedule->meeting_date->format('l, M d, Y') }}
            </p>
        </div>

        <a href="{{ route('school.ptm.index') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
            &larr; Cancel
        </a>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('school.ptm.book.store', $schedule->id) }}" method="POST" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-xs space-y-5">
        @csrf

        <!-- Step 1: Select Child -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                1. Select Enrolled Scholar <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($children as $child)
                <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition"
                    :class="selectedChild == '{{ $child->id }}' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'">
                    <input type="radio" name="student_id" value="{{ $child->id }}" x-model="selectedChild" required class="sr-only">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-black text-xs flex items-center justify-center">
                            {{ substr($child->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $child->name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $child->currentClass?->display_name ?? 'Class Scholar' }}</div>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <!-- Step 2: Select Teacher -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                2. Select Faculty Member / Subject Teacher <span class="text-rose-500">*</span>
            </label>
            <select name="teacher_id" x-model="selectedTeacher" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Choose Teacher --</option>
                @foreach($teachers as $t)
                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- Step 3: Select Time Slot -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                3. Choose Available Time Slot <span class="text-rose-500">*</span>
            </label>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                @foreach($slots as $slot)
                <label class="relative flex items-center justify-center p-2.5 rounded-xl border cursor-pointer text-center transition"
                    :class="selectedSlot == '{{ $slot }}' ? 'border-indigo-600 bg-indigo-600 text-white font-bold shadow-xs' : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    <input type="radio" name="slot_time" value="{{ $slot }}" x-model="selectedSlot" required class="sr-only">
                    <span class="text-xs">{{ \Carbon\Carbon::parse($slot)->format('h:i A') }}</span>
                </label>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Each consultation slot is {{ $schedule->slot_duration_minutes }} minutes long.</p>
        </div>

        <!-- Step 4: Parent Notes -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                4. Agenda / Specific Questions For Teacher (Optional)
            </label>
            <textarea name="parent_notes" rows="3" placeholder="e.g. Discussing performance in Mathematics term exam, focus during homework, etc." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="text-xs text-slate-500">
                Mode: <span class="font-bold text-slate-700 dark:text-slate-300">{{ ucfirst($schedule->mode) }}</span>
                @if($schedule->meeting_venue)
                &bull; Venue: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $schedule->meeting_venue }}</span>
                @endif
            </div>

            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs">
                Confirm Consultation Appointment
            </button>
        </div>
    </form>
</div>
@endsection
