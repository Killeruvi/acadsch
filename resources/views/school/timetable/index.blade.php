@extends('layouts.app')

@section('title', 'Weekly Class Timetable & Routine - School Management System')

@section('content')
<div class="max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 py-4 pb-24 sm:pb-8 space-y-4">

    @if(Auth::user()->isParent())
    @include('school.parent._nav')
    @if($children->isNotEmpty())
    <form method="GET" action="{{ route('school.timetable.index') }}" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 flex items-center gap-3">
        <input type="hidden" name="view" value="class">
        <label for="child_id" class="text-xs font-bold text-slate-700 dark:text-slate-300">Student</label>
        <select id="child_id" name="child_id" onchange="this.form.submit()" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white">
            @foreach($children as $child)<option value="{{ $child->id }}" {{ $activeChild?->id === $child->id ? 'selected' : '' }}>{{ $child->name }}</option>@endforeach
        </select>
    </form>
    @endif
    @endif

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Weekly Class Timetable</h1>

        <div class="flex items-center flex-wrap gap-2">
            @if($selectedClass && Auth::user()->isAdmin())
            <form method="POST" action="{{ route('school.timetable.auto-generate', $selectedClass->id) }}"
                  onsubmit="return confirm('Auto-generate a complete 5-day period routine for {{ $selectedClass->display_name }} based on its subjects?');">
                @csrf
                <button type="submit" 
                        class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                    <span>⚡ Auto-Generate Routine</span>
                </button>
            </form>
            @endif

            @if(Auth::user()->isAdmin())
            <button type="button" onclick="document.getElementById('period-master-modal').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold">Manage Periods</button>
            <button type="button" 
                    onclick="document.getElementById('add-period-modal').classList.remove('hidden')"
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>➕ Add Period</span>
            </button>
            @endif
        </div>
    </div>

    <!-- View Switcher & Class / Teacher Filter -->
    <div class="bg-white dark:bg-slate-900 p-2.5 sm:p-3 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex items-center space-x-2">
            <a href="{{ route('school.timetable.index', ['view' => 'class', 'class_id' => $classId]) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewType === 'class' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                🏫 Classroom Timetable
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <a href="{{ route('school.timetable.index', ['view' => 'teacher', 'teacher_id' => $teacherId]) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $viewType === 'teacher' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                👨‍🏫 Teacher Timetable
            </a>
            @endif
        </div>

        <form method="GET" action="{{ route('school.timetable.index') }}" class="flex items-center space-x-2">
            <input type="hidden" name="view" value="{{ $viewType }}">

            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                @if($viewType === 'class')
                <select name="class_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                        {{ $c->display_name }}{{ $c->room_number ? ' ('.$c->room_number.')' : '' }}
                    </option>
                    @endforeach
                </select>
                @else
                <select name="teacher_id" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}" {{ $teacherId == $t->id ? 'selected' : '' }}>
                        {{ $t->name }} (Faculty)
                    </option>
                    @endforeach
                </select>
                @endif
            @else
                <span class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300">
                    Class: {{ $selectedClass ? $selectedClass->display_name : 'Class Routine' }}
                </span>
            @endif
        </form>
    </div>

    <!-- Weekly Grid Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
            <div class="font-bold text-slate-900 dark:text-white flex items-center space-x-2">
                @if($viewType === 'class' && $selectedClass)
                <span>Schedule:</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">{{ $selectedClass->display_name }}</span>
                <span class="text-[11px] font-mono text-slate-500">({{ $selectedClass->room_number ?: 'Room not assigned' }})</span>
                @elseif($viewType === 'teacher' && $selectedTeacher)
                <span>Schedule:</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">{{ $selectedTeacher->name }}</span>
                <span class="text-[11px] text-slate-500">(Teaching Load)</span>
                @endif
            </div>
            <span class="text-[10px] font-mono text-slate-400">{{ count($periods) }} configured slots</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-center text-xs text-slate-600 dark:text-slate-400 border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 text-left w-28 border-r border-slate-200 dark:border-slate-800">Day</th>
                        @foreach($periods as $pNum => $pData)
                        <th class="p-2.5 min-w-[130px] border-r border-slate-100 dark:border-slate-800 last:border-0 {{ isset($pData['is_break']) && $pData['is_break'] ? 'bg-amber-50/50 dark:bg-amber-950/20' : '' }}">
                            <span class="block font-black text-slate-900 dark:text-white">{{ $pData['name'] }}</span>
                            <span class="text-[9px] font-mono font-medium text-slate-400">{{ $pData['start'] }} - {{ $pData['end'] }}</span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($days as $day)
                    <tr>
                        <td class="p-3.5 text-left font-black text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-800/30 border-r border-slate-200 dark:border-slate-800">
                            {{ $day }}
                        </td>

                        @foreach($periods as $pNum => $pData)
                        @if(isset($pData['is_break']) && $pData['is_break'])
                        <td class="p-2 bg-amber-50/40 dark:bg-amber-950/20 border-r border-slate-100 dark:border-slate-800 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                            🍱 Recess Break
                        </td>
                        @else
                        @php
                            $slot = $grid[$day][$pNum] ?? null;
                            $subProxy = ($slot && isset($substitutes)) ? ($substitutes->get($slot->id)) : null;
                        @endphp
                        <td class="p-2 border-r border-slate-100 dark:border-slate-800 align-top">
                            @if($slot)
                            <div class="p-2 rounded-xl {{ $subProxy ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700/60' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700/60' }} border text-left space-y-0.5">
                                <span class="font-bold text-[11px] text-slate-900 dark:text-white block truncate">
                                    {{ $slot->subject ? $slot->subject->name : ($slot->title ?? 'Period') }}
                                </span>
                                <span class="text-[10px] text-slate-500 block truncate">
                                    @if($viewType === 'class')
                                        @if($subProxy)
                                            <span class="text-amber-700 dark:text-amber-300 font-bold block">Proxy: {{ $subProxy->substituteTeacher?->name }}</span>
                                            <span class="line-through text-slate-400 block text-[9px]">({{ $slot->teacher ? $slot->teacher->name : 'Staff' }})</span>
                                        @else
                                            👨‍🏫 {{ $slot->teacher ? $slot->teacher->name : 'Staff' }}
                                        @endif
                                    @else
                                    🏫 {{ $slot->academicClass ? $slot->academicClass->display_name : 'Class' }}
                                    @endif
                                </span>
                                <span class="text-[9px] font-mono text-emerald-600 dark:text-emerald-400 block">
                                    {{ $slot->room_number ?? 'Room not assigned' }}
                                </span>
                                @if(Auth::user()->isAdmin() && !$subProxy && $slot->teacher_id)
                                <button type="button" onclick="openSubstituteModal('{{ $slot->id }}', '{{ addslashes($slot->academicClass?->display_name ?? 'Class') }}', 'Period {{ $slot->period_number }}', '{{ addslashes($slot->teacher?->name ?? 'Teacher') }}')" class="mt-1 text-[9px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 hover:bg-amber-200 text-slate-700 dark:text-slate-200 block">
                                    + Assign Proxy
                                </button>
                                @elseif(Auth::user()->isAdmin() && $subProxy)
                                <form method="POST" action="{{ route('school.timetable.substitutes.destroy', $subProxy->id) }}" class="mt-1 inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[9px] px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 hover:bg-rose-200">
                                        Cancel Proxy
                                    </button>
                                </form>
                                @endif
                            </div>
                            @else
                            <div class="p-2 text-center text-slate-300 dark:text-slate-700 text-[10px]">
                                —
                            </div>
                            @endif
                        </td>
                        @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

@if(Auth::user()->isAdmin())
<!-- Modal: Add Period Slot -->
<div id="add-period-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden"
     x-data="{
         selectedClassId: '{{ $classId ?: ($classes->first()?->id ?? '') }}',
         selectedSubjectId: '',
         selectedTeacherId: '',
         classSubjects: {{ Js::from($classes->mapWithKeys(fn($c) => [$c->id => $c->subjects->map(fn($s) => [
             'id' => $s->id,
             'name' => $s->name,
             'code' => $s->code,
             'teacher_id' => $s->teacher_id,
         ])])) }},
         get availableSubjects() {
             return this.classSubjects[this.selectedClassId] || [];
         },
         onClassChange() {
             this.selectedSubjectId = '';
             this.selectedTeacherId = '';
         },
         onSubjectChange() {
             const found = this.availableSubjects.find(s => String(s.id) === String(this.selectedSubjectId));
             this.selectedTeacherId = found && found.teacher_id ? String(found.teacher_id) : ''; 
         }
     }">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Schedule Period Slot</h3>
                <span class="text-[10px] text-slate-500 block">Class-dependent subject selector</span>
            </div>
            <button onclick="document.getElementById('add-period-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.timetable.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Class *</label>
                <select name="class_id" x-model="selectedClassId" @change="onClassChange()" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold">
                    <option value="">-- Select Class --</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->display_name }} ({{ $c->stream ?: 'Stream not set' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Day *</label>
                    <select name="day_of_week" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        @foreach($days as $d)
                        <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teaching Period *</label>
                    <select name="period_number" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                        @foreach($periods as $pNum => $pData)
                            @if(empty($pData['is_break']))<option value="{{ $pNum }}">{{ $pData['name'] }} ({{ $pData['start'] }}–{{ $pData['end'] }})</option>@endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Subject
                    <span class="text-[10px] text-slate-400 font-normal">(Filtered by selected class)</span>
                </label>
                <select name="subject_id" x-model="selectedSubjectId" @change="onSubjectChange()" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold">
                    <option value="">-- Select Subject --</option>
                    <template x-for="subj in availableSubjects" :key="subj.id">
                        <option :value="subj.id" x-text="subj.name + ' (' + subj.code + ')'"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Teacher</label>
                <select name="teacher_id" x-model="selectedTeacherId" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Room (optional)</label>
                <input type="text" name="room_number" maxlength="50" placeholder="Use class room if left blank" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300"><input type="checkbox" name="teacher_override" value="1"> Override the globally assigned subject teacher</label>
            <p class="text-[10px] text-slate-400">Start and end times are always taken from the active period master; only administrators can edit period timings.</p>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-period-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-600/30">
                    Save Slot
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Period master configuration -->
<div id="period-master-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 p-4">
    <div class="mx-auto mt-8 max-w-3xl rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-2xl space-y-4">
        <div class="flex items-center justify-between"><h3 class="font-bold text-slate-900 dark:text-white">Period Master</h3><button type="button" onclick="document.getElementById('period-master-modal').classList.add('hidden')">✕</button></div>
        <form method="POST" action="{{ route('school.timetable.periods.store') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-2 border-b pb-4">@csrf
            <input type="number" name="period_number" min="0" max="{{ config('school.periods.maximum_number') }}" required placeholder="Number" class="rounded border p-2 text-xs bg-transparent">
            <input name="name" maxlength="80" required placeholder="Period name" class="rounded border p-2 text-xs bg-transparent">
            <input type="time" name="start_time" required class="rounded border p-2 text-xs bg-transparent">
            <input type="time" name="end_time" required class="rounded border p-2 text-xs bg-transparent">
            <button class="rounded bg-indigo-600 text-white text-xs font-bold p-2">Add period</button>
            <label class="text-xs sm:col-span-5"><input type="checkbox" name="is_break" value="1"> This is a break slot</label>
        </form>
        <div class="space-y-2 max-h-[55vh] overflow-y-auto">
            @foreach($allPeriods as $masterPeriod)
            <form method="POST" action="{{ route('school.timetable.periods.update', $masterPeriod->id) }}" class="grid grid-cols-1 sm:grid-cols-6 gap-2 items-center rounded-lg border p-2">@csrf @method('PATCH')
                <div class="text-xs font-bold">#{{ $masterPeriod->period_number }} {{ $masterPeriod->is_break ? 'Break' : 'Teaching' }}</div>
                <input name="name" value="{{ $masterPeriod->name }}" required maxlength="80" class="rounded border p-1.5 text-xs bg-transparent">
                <input type="time" name="start_time" value="{{ substr((string)$masterPeriod->start_time,0,5) }}" required class="rounded border p-1.5 text-xs bg-transparent">
                <input type="time" name="end_time" value="{{ substr((string)$masterPeriod->end_time,0,5) }}" required class="rounded border p-1.5 text-xs bg-transparent">
                <input type="hidden" name="is_break" value="0"><label class="text-xs"><input type="checkbox" name="is_break" value="1" {{ $masterPeriod->is_break ? 'checked' : '' }}> Break</label>
                <div class="flex gap-2"><button class="text-xs text-indigo-600 font-bold">Save</button></form>
                    <form method="POST" action="{{ route('school.timetable.periods.toggle', $masterPeriod->id) }}">@csrf @method('PATCH')<button class="text-xs {{ $masterPeriod->is_active ? 'text-rose-600' : 'text-emerald-600' }}">{{ $masterPeriod->is_active ? 'Deactivate' : 'Activate' }}</button></form></div>
            @endforeach
        </div>
    </div>
</div>

<!-- Modal: Assign Proxy Substitute Teacher -->
<div id="substitute-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Assign Proxy Substitute</h3>
                <span id="sub-modal-subtitle" class="text-[10px] text-slate-500 block">Cover instructional period</span>
            </div>
            <button onclick="document.getElementById('substitute-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.timetable.substitutes.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="timetable_id" id="sub-timetable-id">

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Coverage Date *</label>
                <input type="date" name="date" value="{{ $todayDate }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Substitute Teacher *</label>
                <select name="substitute_teacher_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
                    <option value="">-- Choose Faculty --</option>
                    @foreach($teachers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Notes</label>
                <input type="text" name="reason" placeholder="e.g. Leave coverage, emergency proxy" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('substitute-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-sm">Assign Proxy</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSubstituteModal(slotId, className, periodName, teacherName) {
    document.getElementById('sub-timetable-id').value = slotId;
    document.getElementById('sub-modal-subtitle').textContent = className + ' • ' + periodName + ' (Original: ' + teacherName + ')';
    document.getElementById('substitute-modal').classList.remove('hidden');
}
</script>
@endif

@endsection
