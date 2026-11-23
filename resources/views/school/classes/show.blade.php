@extends('layouts.app')

@section('title', $class->display_name . ' - Class Details')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('school.classes.index') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold flex items-center space-x-1">
            <i class="ti-arrow-left text-[10px]"></i>
            <span>Classes</span>
        </a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-bold">{{ $class->display_name }}</span>
    </div>

    <!-- Class Header Card (Compact, structured, scannable) -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-lg text-white font-black text-lg flex items-center justify-center font-mono shadow-xs" style="background: var(--system_primary_gradient);">
                G{{ $class->grade_level }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">{{ $class->display_name }}</h1>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-2xs" style="background: var(--system_primary_gradient);">
                        {{ $class->academic_year ?: 'Session not assigned' }}
                    </span>
                </div>
                <div class="flex items-center flex-wrap gap-1.5 mt-1 text-[11px]">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold flex items-center space-x-1">
                        <i class="ti-home text-slate-400 text-xs"></i>
                        <span>Room: <strong class="font-mono">{{ $class->room_number ?: 'Not assigned' }}</strong></span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold flex items-center space-x-1">
                        <i class="ti-user text-slate-400 text-xs"></i>
                        <span>Incharge: <strong>{{ $class->classTeacher ? $class->classTeacher->name : 'Unassigned' }}</strong></span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold font-mono">
                        {{ $class->students_count }} / {{ $class->capacity }} Enrolled
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-1.5">
            <a href="{{ route('school.attendance.take', $class->id) }}" 
               class="px-3 py-1.5 rounded-lg text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
               style="background: var(--system_primary_gradient);">
                <i class="ti-check-box text-xs"></i>
                <span>Roll Call</span>
            </a>
            <a href="{{ route('school.attendance.register', $class->id) }}" 
               class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5">
                <i class="ti-calendar text-xs"></i>
                <span>Register</span>
            </a>
            <a href="{{ route('school.timetable.index', ['class_id' => $class->id]) }}" 
               class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <i class="ti-time text-xs"></i>
                <span>Timetable</span>
            </a>
            @if(Auth::user()->isAdmin())
            <button type="button" onclick="document.getElementById('edit-class-modal').classList.remove('hidden')"
               class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
               title="Edit Class Details">
                <i class="ti-pencil text-xs"></i>
                <span>Edit</span>
            </button>
            <a href="{{ route('school.assignments.index', ['class_id' => $class->id]) }}" 
               class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
               title="Manage Faculty & Subject Assignments">
                <i class="ti-blackboard text-xs"></i>
                <span>Assignments</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Main Grid: Subjects & Student Roster -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">

        <!-- Left Column (2 Cols): Student Roster -->
        <div class="lg:col-span-2 space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xs">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Enrolled Students Roster</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold font-mono">{{ $class->students_count }} Students Enrolled</span>
                        <form method="GET" action="{{ route('school.classes.show', $class->id) }}" class="flex gap-1">
                            <input name="student_search" value="{{ $studentSearch ?? '' }}" placeholder="Find student" class="w-32 rounded border border-slate-200 dark:border-slate-700 bg-transparent px-2 py-1 text-[10px]">
                            <button class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 text-[10px]">Search</button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                            <tr>
                                <th class="p-3 rounded-l-xl">Roll No</th>
                                <th class="p-3">Admission No</th>
                                <th class="p-3">Student Name</th>
                                <th class="p-3">Parent Contact</th>
                                <th class="p-3 text-right rounded-r-xl">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($enrolledStudents as $student)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $student->pivot->roll_number ?? $student->roll_number ?? '-' }}
                                </td>
                                <td class="p-3 font-mono text-[11px] text-emerald-600 dark:text-emerald-400">
                                    {{ $student->admission_number ?? $student->student_id_number ?? '-' }}
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px] flex items-center justify-center">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $student->name }}</span>
                                    </div>
                                </td>
                                <td class="p-3 text-[11px]">
                                    <div class="truncate max-w-[140px]">{{ $student->guardian_name ?? 'N/A' }}</div>
                                    <div class="text-slate-400 text-[10px]">{{ $student->guardian_phone ?? $student->phone ?? '-' }}</div>
                                </td>
                                <td class="p-3 text-right space-x-1">
                                    <a href="{{ route('school.students.show', $student->id) }}" 
                                       class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[11px]"
                                       title="View Student File">
                                        File
                                    </a>
                                    <a href="{{ route('school.students.id-card', $student->id) }}" 
                                       class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-400 font-bold text-[11px]"
                                       title="Print ID Card">
                                        ID
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    No students admitted in this class yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(isset($enrolledStudents) && $enrolledStudents instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $enrolledStudents->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column (1 Col): Curriculum Subjects & Teachers -->
        <div class="space-y-3">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xs">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Curriculum Subjects</h2>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <button type="button" 
                            onclick="document.getElementById('add-subject-modal').classList.remove('hidden')"
                            class="px-2 py-1 rounded bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-400 text-xs font-bold"
                            title="Add Subject">
                        ➕ Add
                    </button>
                    @endif
                </div>

                <div class="space-y-2">
                    @forelse($classSubjects ?? $class->subjects as $subject)
                    <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-1.5">
                                <span class="font-mono text-[10px] font-bold text-slate-400 uppercase bg-slate-200 dark:bg-slate-700 px-1 py-0.2 rounded">
                                    {{ $subject->code }}
                                </span>
                                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $subject->name }}</h4>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                    👨‍🏫 {{ $subject->teacher?->name ?? ($class->academic_year === \App\Models\AcademySetting::get('academic_session') ? $subject->subjectMaster?->defaultTeacher?->name : null) ?? 'Unassigned' }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-400 font-bold">
                                    {{ $subject->weekly_periods ?? 'Not configured' }} per/wk
                                </span>
                            </div>
                        </div>

                        @if(Auth::user()->isAdmin())
                        <form method="POST" action="{{ route('school.classes.subjects.destroy', ['id' => $class->id, 'subjectId' => $subject->id]) }}" 
                              onsubmit="return confirm('Remove subject {{ $subject->name }} from this class?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-rose-500 text-xs font-bold p-1">✕</button>
                        </form>
                        @endif
                    </div>
                    @empty
                    <div class="p-6 text-center text-xs text-slate-400">
                        No subjects added to this class yet.
                    </div>
                    @endforelse
                </div>

                @if(isset($classSubjects) && $classSubjects instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    {{ $classSubjects->withQueryString()->links() }}
                </div>
                @endif
            </div>

            <!-- Fee Policy Summary -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Class Fee Structure</h3>
                    <a href="{{ route('school.fees.index', ['class_id' => $class->id]) }}" class="text-xs font-bold text-emerald-600 hover:underline">Manage</a>
                </div>

                <div class="space-y-2 text-xs">
                    @forelse($class->feeStructures as $fee)
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                        <span class="text-slate-600 dark:text-slate-400">{{ $fee->fee_title }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($fee->amount, 2) }}</span>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400">No fee structure is configured for this class.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal: Add Subject from the active Subject Master catalogue -->
@if(Auth::user()->isAdmin())
<div id="add-subject-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden"
     x-data="{
         masterSubjects: {{ Js::from($masterSubjects) }},
         selectedMasterId: '',
         weeklyPeriods: '',
         selectedTeacherId: '',
         onSelect(id) {
             const found = this.masterSubjects.find(s => String(s.id) === String(id));
             this.weeklyPeriods = found ? (found.default_periods ?? '') : '';
             this.selectedTeacherId = found && found.default_teacher_id ? String(found.default_teacher_id) : '';
         }
     }">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div><h3 class="text-base font-bold text-slate-900 dark:text-white">Add Subject to {{ $class->display_name }}</h3><span class="text-[10px] text-emerald-600 block">Choose an active subject master for {{ $class->stream }}.</span></div>
            <button type="button" onclick="document.getElementById('add-subject-modal').classList.add('hidden')" class="text-slate-400">✕</button>
        </div>
        <form method="POST" action="{{ route('school.classes.subjects.store', $class->id) }}" class="space-y-4">@csrf
            <div>
                <label class="block text-xs font-bold mb-1">Subject *</label>
                <select name="subject_master_id" x-model="selectedMasterId" @change="onSelect($event.target.value)" required class="w-full rounded-xl border p-2 text-xs bg-white dark:bg-slate-800">
                    <option value="">Select from subject master</option>
                    @foreach($masterSubjects->where('is_already_assigned', false) as $master)
                    <option value="{{ $master->id }}">{{ $master->name }} ({{ $master->grade_code }}) — {{ $master->category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-bold mb-1">Weekly periods *</label><input type="number" name="weekly_periods" x-model="weeklyPeriods" min="1" max="40" required class="w-full rounded-xl border p-2 text-xs bg-white dark:bg-slate-800"></div>
                <div><label class="block text-xs font-bold mb-1">Subject teacher</label><select name="teacher_id" x-model="selectedTeacherId" class="w-full rounded-xl border p-2 text-xs bg-white dark:bg-slate-800"><option value="">Use subject master default</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="teacher_override" value="1"> Override global subject teacher for this class only</label>
            <div class="flex justify-end gap-2"><button type="button" onclick="document.getElementById('add-subject-modal').classList.add('hidden')" class="rounded-lg bg-slate-100 px-3 py-2 text-xs">Cancel</button><button class="rounded-lg bg-emerald-600 text-white px-4 py-2 text-xs font-bold">Add subject</button></div>
        </form>
    </div>
</div>

<!-- Modal: Edit Class (Select from Reusable Sections — No free text) -->
<div id="edit-class-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden"
     x-data="{
         selectedStream: {{ Js::from($class->stream) }},
         selectedSectionId: '{{ $class->section_id }}',
         allSections: {{ Js::from($allSections ?? $availableSections) }},
         get availableSectionsForStream() {
             return this.allSections.filter(s => (s.stream || '').toLowerCase() === (this.selectedStream || '').toLowerCase());
         }
     }">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Class: {{ $class->display_name }}</h3>
                <p class="text-[10px] text-slate-500">Section must be selected from the existing reusable sections library.</p>
            </div>
            <button onclick="document.getElementById('edit-class-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.classes.update', $class->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Grade Level (1-12) *</label>
                    <select name="grade_level" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        @foreach(config('school.grade_levels', []) as $grade)<option value="{{ $grade }}" {{ (int) $class->grade_level === (int) $grade ? 'selected' : '' }}>Grade {{ $grade }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Class / Grade Name *</label>
                    <input type="text" name="name" required value="{{ $class->name }}"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Stream *</label>
                    <select name="stream" x-model="selectedStream" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        @foreach($streams as $key => $label)
                        <option value="{{ $key }}" {{ $class->stream === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Reusable Section *</label>
                    <select name="section_id" x-model="selectedSectionId" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <template x-for="sec in availableSectionsForStream" :key="sec.id">
                            <option :value="sec.id" :selected="selectedSectionId == sec.id" x-text="'Section ' + sec.name + ' (Cap: ' + sec.capacity + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Homeroom Incharge</label>
                    <select name="class_teacher_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Unassigned --</option>
                        @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ $class->class_teacher_id === $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Room Number</label>
                    <input type="text" name="room_number" value="{{ $class->room_number }}"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Student Capacity</label>
                    <input type="number" name="capacity" value="{{ $class->capacity }}" min="{{ config('school.capacity.minimum') }}" max="{{ config('school.capacity.maximum') }}" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Year</label>
                    <select name="academic_year" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        @foreach($academicYears as $sess)
                            <option value="{{ $sess }}" {{ $sess === $currentSession ? 'selected' : '' }}>{{ $sess }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('edit-class-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-600/30">
                    Update Class
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
