@extends('layouts.app')

@section('title', 'Attendance Management' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ openTakeModal: false, selectedModalClassId: '{{ $selectedClass->id ?? ($allClasses->first()->id ?? '') }}' }" class="space-y-3.5">

    <!-- Header Section (Compact, structured, zero paragraph walls) -->
    <div class="bg-white dark:bg-slate-900 rounded-lg p-3 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
                    Attendance Management
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    Roll-Call Register
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Min Required: {{ config('attendance.minimum_percentage') }}%
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('leaves.index') }}" 
               class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-md text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1">
                <span>📝 Leaves</span>
            </a>
            @if($allClasses->isNotEmpty())
            <button @click.stop="openTakeModal = true" 
                    type="button" 
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-md text-xs font-bold shadow-2xs transition flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Take Attendance</span>
            </button>
            @endif
        </div>
    </div>

    @if($allClasses->isEmpty() && Auth::user()->isTeacher())
    <div class="p-5 text-center bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 space-y-1.5">
        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
            ⏳ Awaiting Class Assignment
        </span>
        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">No Classes Assigned To Your Account</h4>
        <div class="text-[11px] text-slate-500 dark:text-slate-400">
            Administrator must assign your homeroom or subject periods before roll call activates.
        </div>
    </div>
    @else

    <!-- Attendance Defaulters Alert (< {{ config('attendance.minimum_percentage') }}% Requirement) -->
    @if(!empty($defaulters) && count($defaulters) > 0)
    <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 rounded-xl p-3 sm:p-3.5 shadow-2xs">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start space-x-2.5">
                <span class="text-lg flex-shrink-0">⚠️</span>
                <div>
                    <h4 class="text-xs font-black text-rose-900 dark:text-rose-300 uppercase tracking-wide">
                        Attendance Defaulters Alert (&lt; {{ config('attendance.minimum_percentage') }}%) — {{ $selectedClass->display_name ?? 'Class' }}
                    </h4>
                    <p class="text-[11px] text-rose-700 dark:text-rose-400">
                        {{ count($defaulters) }} student(s) below the configured {{ config('attendance.minimum_percentage') }}% threshold.
                    </p>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($defaulters as $d)
                        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 border border-rose-200 dark:border-rose-800 flex items-center justify-between shadow-2xs">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ $d['student']->name }}</span>
                                <span class="text-[10px] text-slate-500">{{ $d['present'] }}/{{ $d['total'] }} Sessions</span>
                            </div>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800">
                                {{ $d['rate'] }}%
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <a href="{{ route('leaves.index') }}" class="text-[11px] font-bold text-rose-700 dark:text-rose-400 hover:underline flex-shrink-0">
                Leaves &rarr;
            </a>
        </div>
    </div>
    @endif

    <!-- Class Filter Tabs -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Academic Class</span>
            <span class="text-[10px] text-slate-400">Viewing: <strong class="text-indigo-600 dark:text-indigo-400">{{ $selectedClass->display_name ?? 'No class selected' }}</strong></span>
        </div>
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-thin">
            @foreach($allClasses as $c)
            <a href="{{ route('attendance.index', ['class_id' => $c->id]) }}" 
               class="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold transition {{ (int)$selectedClassId === (int)$c->id ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                {{ $c->display_name }}
            </a>
            @endforeach
        </div>
    </div>

    <!-- Attendance Sessions History Log -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3 sm:p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Recorded Sessions</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Attendance sessions with roll-call ratios</p>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $totalSessionsConducted }} Sessions
            </span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($sessions as $session)
            @php
                $presentCount = $session->records->whereIn('status', ['present', 'late'])->count();
                $absentCount = $session->records->where('status', 'absent')->count();
                $totalInSession = $session->records->count();
                $rate = $totalInSession > 0 ? round(($presentCount / $totalInSession) * 100, 1) : 0;
            @endphp
            <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition flex flex-col md:flex-row md:items-center justify-between gap-2.5 sm:gap-3">
                <div class="space-y-0.5">
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            {{ $session->academicClass->display_name ?? 'Campus Session' }}
                        </span>
                        <span class="text-slate-400 text-[10px]">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $session->session_date->format('M d, Y') }}</span>
                    </div>

                    <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                        <a href="{{ route('attendance.show', $session->id) }}" class="hover:text-indigo-600 transition">
                            {{ $session->title }}
                        </a>
                    </h4>

                    @if($session->notes)
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $session->notes }}</p>
                    @endif

                    <div class="text-[10px] text-slate-400">
                        By <strong class="text-slate-600 dark:text-slate-300">{{ $session->teacher->name }}</strong>
                    </div>
                </div>

                <!-- Metrics & Link -->
                <div class="flex items-center space-x-3 self-end md:self-center">
                    <div class="text-right">
                        <div class="text-xs font-bold text-slate-900 dark:text-white">{{ $presentCount }}/{{ $totalInSession }} Present</div>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-extrabold {{ $rate >= config('attendance.warning_percentage') ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                            {{ $rate }}% Rate
                        </span>
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <a href="{{ route('attendance.edit', $session->id) }}" 
                           class="px-2 py-1 bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-bold border border-indigo-200 dark:border-indigo-800 transition">
                            ✏️ Edit
                        </a>
                        <a href="{{ route('attendance.show', $session->id) }}" 
                           class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-[11px] font-bold transition">
                            Roster &rarr;
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-slate-400 text-xs">
                <span>No attendance sessions recorded for this class yet.</span>
            </div>
            @endforelse
        </div>

        <div class="p-2.5 border-t border-slate-100 dark:border-slate-800">
            {{ $sessions->links() }}
        </div>
    </div>

    <!-- TAKE ATTENDANCE MODAL -->
    <div x-show="openTakeModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <!-- Backdrop Overlay -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="openTakeModal = false"></div>

        <!-- Modal Dialog Content -->
        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-4 sm:p-5 animate-in fade-in duration-150 z-10 space-y-3">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white">Take Session Attendance</h3>
                <span class="text-[10px] text-slate-500">Roll-call roster for selected academic class</span>
            </div>
            <button @click="openTakeModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('attendance.store') }}" class="space-y-4 mt-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Class *</label>
                    <select name="class_id" x-model="selectedModalClassId" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-bold">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Session Date *</label>
                    <input type="date" name="session_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Session Title *</label>
                <input type="text" name="title" required value="Class Session Attendance" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
            </div>

            <!-- Student Roster Quick Toggles -->
            <div class="pt-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Class Student Roster</span>
                    <span class="text-[11px] text-emerald-600 font-semibold">Attending students earn +15 XP</span>
                </div>

                @foreach($allClasses as $c)
                <div x-show="selectedModalClassId == {{ $c->id }}" class="max-h-64 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
                    @forelse($c->students as $student)
                    <div class="p-3 flex items-center justify-between text-xs hover:bg-slate-50">
                        <div class="flex items-center space-x-2.5">
                            <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                {{ $student->pivot->roll_number ?? $loop->iteration }}
                            </span>
                            <div>
                                <span class="font-bold text-slate-800">{{ $student->name }}</span>
                                <span class="text-slate-400 text-[10px] block">{{ $student->student_id_number }}</span>
                            </div>
                        </div>

                        <!-- Status Selector -->
                        <div class="flex items-center space-x-1">
                            <label class="cursor-pointer">
                                <input type="radio" :name="selectedModalClassId == {{ $c->id }} ? 'attendance[{{ $student->id }}]' : ''" value="present" checked :disabled="selectedModalClassId != {{ $c->id }}" class="peer sr-only">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-200 peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600 transition">
                                    Present
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" :name="selectedModalClassId == {{ $c->id }} ? 'attendance[{{ $student->id }}]' : ''" value="absent" :disabled="selectedModalClassId != {{ $c->id }}" class="peer sr-only">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-200 peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 transition">
                                    Absent
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" :name="selectedModalClassId == {{ $c->id }} ? 'attendance[{{ $student->id }}]' : ''" value="late" :disabled="selectedModalClassId != {{ $c->id }}" class="peer sr-only">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-200 peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-600 transition">
                                    Late
                                </span>
                            </label>
                        </div>
                    </div>
                    @empty
                    <div class="p-6 text-center text-slate-400 text-xs">
                        No students enrolled in {{ $c->display_name }} yet.
                    </div>
                    @endforelse
                </div>
                @endforeach
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Session Notes (Optional)</label>
                <textarea name="notes" rows="2" placeholder="Class participation notes, topics covered..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" @click="openTakeModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                    Save Attendance Record
                </button>
            </div>
        </form>
    </div>
</div>
@endif

</div>
@endsection
