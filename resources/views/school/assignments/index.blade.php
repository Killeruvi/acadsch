@extends('layouts.app')

@section('title', 'Teacher & Subject Assignment — School ERP')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 space-y-4" x-data="{
    openAssignModal: false,
    openClassTeacherModal: false,
    openTimetableModal: false,
    openEditModal: false,
    editSubject: { id: null, name: '', code: '', teacher_id: '', default_teacher_id: '', weekly_periods: '', class_name: '' }
}">

    <!-- Breadcrumb & Minimal Title Header (No banners, strictly utilitarian) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <div>
            <div class="flex items-center space-x-2 text-[11px] text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-500">School ERP</a>
                <span>/</span>
                <a href="{{ route('school.classes.index') }}" class="hover:text-emerald-500">Classes</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Teacher &amp; Subject Assignment</span>
            </div>
            <div class="flex items-center space-x-2">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Teacher &amp; Subject Assignment
                </h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    🛡️ Admin Master Panel
                </span>
                <span class="hidden sm:inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    Conflict Guard: Active
                </span>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center flex-wrap gap-2">
            <button @click="openAssignModal = true" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>➕</span>
                <span>Assign Subject</span>
            </button>
            <button @click="openClassTeacherModal = true" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>👨‍🏫</span>
                <span>Assign Class Teacher</span>
            </button>
            <button @click="openTimetableModal = true" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold border border-slate-700 transition flex items-center space-x-1.5">
                <span>🗓️</span>
                <span>Schedule Period</span>
            </button>
        </div>
    </div>

    <!-- Flash Error / Success Alerts -->
    @if(session('success'))
    <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span>✅</span>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs space-y-1">
        <div class="font-bold flex items-center space-x-1.5">
            <span>⚠️</span>
            <span>Conflict / Validation Error:</span>
        </div>
        <ul class="list-disc list-inside space-y-0.5 text-[11px]">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Compact Metric Summary Row (Dense, zero whitespace waste) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Faculty Assigned</span>
            <div class="flex items-baseline space-x-1 mt-0.5">
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['assigned_teachers'] }}</span>
                <span class="text-xs text-slate-400">/ {{ $stats['total_teachers'] }} teachers</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1 rounded-full mt-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1 rounded-full" style="width: {{ $stats['total_teachers'] > 0 ? round(($stats['assigned_teachers'] / $stats['total_teachers']) * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Subject Mappings</span>
            <div class="flex items-baseline space-x-1 mt-0.5">
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['total_subjects'] }}</span>
                <span class="text-xs {{ $stats['unassigned_subjects'] > 0 ? 'text-amber-500 font-bold' : 'text-slate-400' }}">
                    ({{ $stats['unassigned_subjects'] }} unassigned)
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Across all grades &amp; sections</span>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Class Teachers</span>
            <div class="flex items-baseline space-x-1 mt-0.5">
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['total_classes'] - $stats['unassigned_class_teachers'] }}</span>
                <span class="text-xs text-slate-400">/ {{ $stats['total_classes'] }} homerooms</span>
            </div>
            <span class="text-[10px] {{ $stats['unassigned_class_teachers'] > 0 ? 'text-rose-500 font-bold' : 'text-emerald-500' }} block mt-1">
                {{ $stats['unassigned_class_teachers'] > 0 ? $stats['unassigned_class_teachers'] . ' class needing teacher' : 'All classes assigned' }}
            </span>
        </div>

        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Timetable Slots</span>
            <div class="flex items-baseline space-x-1 mt-0.5">
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['total_timetable_slots'] }}</span>
                <span class="text-xs text-slate-400">periods scheduled</span>
            </div>
            <span class="text-[10px] text-indigo-500 font-medium block mt-1">Conflict-validated routine</span>
        </div>
    </div>

    <!-- Filter & Search Bar (Dense, inline) -->
    <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-2">
        <!-- Navigation Tabs -->
        <div class="flex items-center space-x-1 overflow-x-auto pb-1 md:pb-0">
            <a href="{{ route('school.assignments.index', ['tab' => 'subjects', 'class_id' => $selectedClassId, 'teacher_id' => $selectedTeacherId, 'search' => $search]) }}" 
               class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center space-x-1.5 {{ $tab === 'subjects' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>📚 Subject Mappings</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'subjects' ? 'bg-emerald-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $subjects->total() }}</span>
            </a>
            <a href="{{ route('school.assignments.index', ['tab' => 'class_teachers', 'class_id' => $selectedClassId, 'teacher_id' => $selectedTeacherId, 'search' => $search]) }}" 
               class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center space-x-1.5 {{ $tab === 'class_teachers' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>👨‍🏫 Class Teachers</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'class_teachers' ? 'bg-emerald-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $classesWithTeachers->total() }}</span>
            </a>
            <a href="{{ route('school.assignments.index', ['tab' => 'timetable', 'class_id' => $selectedClassId, 'teacher_id' => $selectedTeacherId, 'search' => $search]) }}" 
               class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center space-x-1.5 {{ $tab === 'timetable' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>🗓️ Timetable Slots</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'timetable' ? 'bg-emerald-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $timetableSlots->total() }}</span>
            </a>
            <a href="{{ route('school.assignments.index', ['tab' => 'audit', 'class_id' => $selectedClassId, 'teacher_id' => $selectedTeacherId, 'search' => $search]) }}" 
               class="px-2.5 py-1.5 rounded-md text-xs font-bold transition flex items-center space-x-1.5 {{ $tab === 'audit' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span>📋 Audit Log</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'audit' ? 'bg-emerald-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $audits->total() }}</span>
            </a>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('school.assignments.index') }}" class="flex items-center flex-wrap gap-1.5 text-xs">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <select name="class_id" onchange="this.form.submit()" class="px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs">
                <option value="">All Classes &amp; Sections</option>
                @foreach($allClasses as $c)
                <option value="{{ $c->id }}" {{ (string)$selectedClassId === (string)$c->id ? 'selected' : '' }}>
                    {{ $c->display_name }}
                </option>
                @endforeach
            </select>

            <select name="teacher_id" onchange="this.form.submit()" class="px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs">
                <option value="">All Faculty</option>
                @foreach($allTeachers as $t)
                <option value="{{ $t->id }}" {{ (string)$selectedTeacherId === (string)$t->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
                @endforeach
            </select>

            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Live search mappings..." 
                       oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 400);"
                       class="pl-6 pr-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs w-36 sm:w-44 focus:ring-1 focus:ring-emerald-500">
                <span class="absolute inset-y-0 left-1.5 flex items-center pointer-events-none text-slate-400 text-[11px]">🔍</span>
            </div>

            @if($selectedClassId || $selectedTeacherId || $search)
            <a href="{{ route('school.assignments.index', ['tab' => $tab]) }}" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold" title="Reset Filters">
                ✕ Reset
            </a>
            @endif
        </form>
    </div>

    <!-- TAB 1: SUBJECT MAPPINGS -->
    @if($tab === 'subjects')
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">Class / Section</th>
                        <th class="p-2.5">Subject &amp; Code</th>
                        <th class="p-2.5">Assigned Subject Teacher</th>
                        <th class="p-2.5 text-center">Periods/Wk</th>
                        <th class="p-2.5 text-center">Timetable Routine</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($subjects as $sub)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="p-2.5 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-5 h-5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-mono text-[10px] flex items-center justify-center font-bold">
                                    G{{ $sub->academicClass->grade_level }}
                                </span>
                                <span>{{ $sub->academicClass->display_name }}</span>
                            </div>
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $sub->name }}</span>
                                <span class="px-1.5 py-0.2 rounded font-mono text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase">
                                    {{ $sub->code }}
                                </span>
                            </div>
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            @php
                                $assignedTeacher = $sub->teacher ?? ($sub->academicClass?->academic_year === \App\Models\AcademySetting::get('academic_session') ? $sub->subjectMaster?->defaultTeacher : null);
                            @endphp
                            @if($assignedTeacher)
                            <div class="flex items-center space-x-2">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] flex items-center justify-center">
                                    {{ strtoupper(substr($assignedTeacher->name, 0, 1)) }}
                                </div>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $assignedTeacher->name }}</span>
                                <span class="text-[10px] text-slate-400">({{ $assignedTeacher->email }})</span>
                            </div>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                ⚠️ Unassigned
                            </span>
                            @endif
                        </td>
                        <td class="p-2.5 text-center font-mono font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $sub->weekly_periods ?? 'Not configured' }}
                        </td>
                        <td class="p-2.5 text-center whitespace-nowrap">
                            @php $slotCount = $sub->timetables->count(); @endphp
                            @if($slotCount > 0)
                            <a href="{{ route('school.assignments.index', ['tab' => 'timetable', 'class_id' => $sub->class_id, 'search' => $sub->name]) }}" 
                               class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 hover:underline">
                                {{ $slotCount }} period slots
                            </a>
                            @else
                            <span class="text-[11px] text-slate-400">No slots</span>
                            @endif
                        </td>
                        <td class="p-2.5 text-right whitespace-nowrap space-x-1">
                            <button @click="editSubject = {
                                id: {{ $sub->id }},
                                name: '{{ addslashes($sub->name) }}',
                                code: '{{ addslashes($sub->code) }}',
                                teacher_id: '{{ $sub->teacher_id ?? (($sub->academicClass?->academic_year === \App\Models\AcademySetting::get('academic_session')) ? $sub->subjectMaster?->default_teacher_id : null) ?? '' }}',
                                default_teacher_id: '{{ ($sub->academicClass?->academic_year === \App\Models\AcademySetting::get('academic_session') ? $sub->subjectMaster?->default_teacher_id : null) ?? '' }}',
                                weekly_periods: {{ $sub->weekly_periods !== null ? (int) $sub->weekly_periods : "''" }},
                                class_name: '{{ addslashes($sub->academicClass->display_name) }}'
                            }; openEditModal = true" 
                            type="button" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[11px]">
                                Edit
                            </button>

                            @if($sub->teacher_id)
                            <form method="POST" action="{{ route('school.assignments.subjects.destroy', $sub->id) }}" class="inline" onsubmit="return confirm('Unassign teacher from {{ $sub->name }} in {{ $sub->academicClass->display_name }}?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="delete_subject" value="0">
                                <button type="submit" class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900 text-amber-700 dark:text-amber-400 font-bold text-[11px]">
                                    Unassign
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('school.assignments.subjects.destroy', $sub->id) }}" class="inline" onsubmit="return confirm('Delete subject {{ $sub->name }} and associated routine from {{ $sub->academicClass->display_name }} entirely?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="delete_subject" value="1">
                                <button type="submit" class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-400 font-bold text-[11px]">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                            No subject assignments found matching your filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subjects->hasPages())
        <div class="p-2 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
            {{ $subjects->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- TAB 2: CLASS TEACHERS (HOMEROOM) -->
    @if($tab === 'class_teachers')
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">Class &amp; Section</th>
                        <th class="p-2.5">Grade Level</th>
                        <th class="p-2.5">Room &amp; Capacity</th>
                        <th class="p-2.5 text-center">Enrolled Students</th>
                        <th class="p-2.5">Assigned Class Teacher</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($classesWithTeachers as $c)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="p-2.5 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            <a href="{{ route('school.classes.show', $c->id) }}" class="hover:text-emerald-500 flex items-center space-x-1.5">
                                <span>{{ $c->display_name }}</span>
                                <span class="text-[10px] text-slate-400">↗</span>
                            </a>
                        </td>
                        <td class="p-2.5 whitespace-nowrap font-mono">
                            Grade {{ $c->grade_level }}
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $c->room_number ?: 'Room not assigned' }}</span>
                            <span class="text-[10px] text-slate-400">(Cap: {{ $c->capacity }})</span>
                        </td>
                        <td class="p-2.5 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                            {{ $c->students_count }}
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            @if($c->classTeacher)
                            <div class="flex items-center space-x-2">
                                <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold text-[10px] flex items-center justify-center">
                                    {{ strtoupper(substr($c->classTeacher->name, 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $c->classTeacher->name }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $c->classTeacher->email }}</span>
                                </div>
                            </div>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                ⚠️ Homeroom Vacant
                            </span>
                            @endif
                        </td>
                        <td class="p-2.5 text-right whitespace-nowrap space-x-1">
                            <!-- Quick Assign / Change Form -->
                            <form method="POST" action="{{ route('school.assignments.class-teacher.assign') }}" class="inline-flex items-center space-x-1">
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $c->id }}">
                                <select name="teacher_id" required class="px-1.5 py-1 rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[11px] max-w-[130px]">
                                    <option value="">-- Choose --</option>
                                    @foreach($allTeachers as $t)
                                    <option value="{{ $t->id }}" {{ $c->class_teacher_id === $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px]">
                                    {{ $c->class_teacher_id ? 'Edit' : 'Save' }}
                                </button>
                            </form>

                            @if($c->class_teacher_id)
                            <form method="POST" action="{{ route('school.assignments.class-teacher.remove', $c->id) }}" class="inline" onsubmit="return confirm('Remove {{ $c->classTeacher->name }} as Class Teacher of {{ $c->display_name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-[11px]">
                                    Clear
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                            No classes found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($classesWithTeachers->hasPages())
        <div class="p-2 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
            {{ $classesWithTeachers->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- TAB 3: TIMETABLE PERIOD SLOTS -->
    @if($tab === 'timetable')
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">Day</th>
                        <th class="p-2.5">Period #</th>
                        <th class="p-2.5">Timing Window</th>
                        <th class="p-2.5">Class / Section</th>
                        <th class="p-2.5">Subject</th>
                        <th class="p-2.5">Faculty In Charge</th>
                        <th class="p-2.5">Room</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($timetableSlots as $slot)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="p-2.5 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">{{ $slot->day_of_week }}</span>
                        </td>
                        <td class="p-2.5 font-mono font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            Period {{ $slot->period_number }}
                        </td>
                        <td class="p-2.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                            {{ $slot->start_time }} - {{ $slot->end_time }}
                        </td>
                        <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {{ $slot->academicClass ? $slot->academicClass->display_name : '-' }}
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $slot->subject ? $slot->subject->name : ($slot->title ?? 'Recess') }}</span>
                            @if($slot->subject)
                            <span class="text-[10px] text-slate-400 font-mono">({{ $slot->subject->code }})</span>
                            @endif
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            @if($slot->teacher)
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $slot->teacher->name }}</span>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 italic">None</span>
                            @endif
                        </td>
                        <td class="p-2.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                            {{ $slot->room_number ?? '-' }}
                        </td>
                        <td class="p-2.5 text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('school.assignments.timetable.destroy', $slot->id) }}" class="inline" onsubmit="return confirm('Remove period {{ $slot->period_number }} ({{ $slot->day_of_week }}) from timetable?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-400 font-bold text-[11px]">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-400 text-xs">
                            No timetable period slots found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($timetableSlots->hasPages())
        <div class="p-2 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
            {{ $timetableSlots->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- TAB 4: AUDIT LOG OF ASSIGNMENT CHANGES -->
    @if($tab === 'audit')
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="p-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 dark:text-slate-300">
                🔒 Immutable Audit Trail of Faculty &amp; Subject Mappings
            </span>
            <span class="text-[11px] text-slate-500 font-mono">
                Total Logs: {{ $audits->total() }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 dark:bg-slate-800/40 uppercase font-black text-[10px] text-slate-500 tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">Timestamp</th>
                        <th class="p-2.5">Action</th>
                        <th class="p-2.5">Admin Operator</th>
                        <th class="p-2.5">Description &amp; Event Scope</th>
                        <th class="p-2.5">Target Teacher</th>
                        <th class="p-2.5">Class / Section</th>
                        <th class="p-2.5 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($audits as $log)
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                        <td class="p-2.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                            {{ $log->created_at->format('d M Y, H:i:s') }}
                        </td>
                        <td class="p-2.5 whitespace-nowrap">
                            @if(str_contains($log->action, 'assigned'))
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 uppercase">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                            @elseif(str_contains($log->action, 'updated'))
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 uppercase">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                            @else
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 uppercase">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                            @endif
                        </td>
                        <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {{ $log->admin ? $log->admin->name : 'Administrator' }}
                        </td>
                        <td class="p-2.5 text-slate-700 dark:text-slate-300">
                            {{ $log->description }}
                        </td>
                        <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {{ $log->teacher_name ?? ($log->teacher ? $log->teacher->name : '-') }}
                        </td>
                        <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {{ $log->class_name ?? ($log->academicClass ? $log->academicClass->display_name : '-') }}
                        </td>
                        <td class="p-2.5 font-mono text-[10px] text-slate-400 text-right whitespace-nowrap">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                            No assignment audit events recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($audits->hasPages())
        <div class="p-2 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50">
            {{ $audits->links() }}
        </div>
        @endif
    </div>
    @endif

    <!-- ======================================================== -->
    <!-- MODAL 1: ASSIGN / MAP SUBJECT -->
    <!-- ======================================================== -->
    <div x-show="openAssignModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openAssignModal = false" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Assign Subject &amp; Teacher</h3>
                    <p class="text-[11px] text-slate-500">Map a faculty member to teach a subject in an academic class.</p>
                </div>
                <button @click="openAssignModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('school.assignments.subjects.store') }}" class="space-y-3 text-xs" 
                  x-data="{ 
                      linkRoutine: false,
                      selectedClassId: '{{ $selectedClassId ?: ($allClasses->first()?->id ?? '') }}',
                      classes: {{ Js::from($allClasses->map(fn($c) => [
                          'id' => $c->id,
                          'name' => $c->display_name,
                          'stream' => $c->stream,
                          'grade' => $c->grade_level,
                          'assigned_subject_names' => $c->subjects->pluck('name')->map(fn($n) => strtoupper($n))->toArray(),
                          'assigned_subject_codes' => $c->subjects->pluck('code')->map(fn($cd) => strtoupper($cd))->toArray(),
                          'assigned_subject_master_ids' => $c->subjects->pluck('subject_master_id')->filter()->values()->toArray(),
                      ])) }},
                      masterSubjects: {{ Js::from($allMasterSubjects->map(fn($m) => [
                          'id' => $m->id,
                          'name' => $m->name,
                          'code' => $m->code,
                          'stream' => $m->stream,
                          'category' => $m->category,
                          'default_periods' => $m->default_periods,
                          'default_teacher_id' => $m->default_teacher_id,
                          'min_grade' => $m->min_grade,
                          'max_grade' => $m->max_grade,
                      ])) }},
                      selectedName: '',
                      selectedCode: '',
                      selectedTeacherId: '',
                      weeklyPeriods: '',
                      get currentClass() {
                          return this.classes.find(c => String(c.id) === String(this.selectedClassId));
                      },
                      get availableMasterSubjects() {
                          const cls = this.currentClass;
                          const stream = cls ? cls.stream : null;
                          const grade = cls ? Number(cls.grade) : null;
                          const assignedNames = cls ? cls.assigned_subject_names : [];
                          const assignedCodes = cls ? cls.assigned_subject_codes : [];
                          const assignedMasterIds = cls ? cls.assigned_subject_master_ids : [];

                          if (!cls || !grade || !stream) return [];
                          return this.masterSubjects
                              .filter(m => (m.stream === 'All' || m.stream === stream) && grade >= Number(m.min_grade) && grade <= Number(m.max_grade))
                              .map(m => {
                                  const gradeCode = m.code.includes('-') ? m.code : (m.code + '-' + String(grade).padStart(2, '0'));
                                  const isAssigned = assignedMasterIds.includes(m.id) || assignedNames.includes(m.name.toUpperCase()) || assignedCodes.includes(gradeCode.toUpperCase()) || assignedCodes.includes(m.code.toUpperCase());
                                  return {
                                      ...m,
                                      grade_code: gradeCode,
                                      is_already_assigned: isAssigned
                                  };
                              });
                      },
                      onSelectName(name) {
                          this.selectedName = name;
                          const found = this.availableMasterSubjects.find(s => s.name === name);
                          if (found) {
                              this.selectedCode = found.grade_code;
                              this.weeklyPeriods = found.default_periods ?? '';
                              this.selectedTeacherId = found.default_teacher_id ? String(found.default_teacher_id) : '';
                          }
                      },
                      onSelectCode(code) {
                          this.selectedCode = code;
                          const found = this.availableMasterSubjects.find(s => s.grade_code === code || s.code === code);
                          if (found) {
                              this.selectedName = found.name;
                              this.weeklyPeriods = found.default_periods ?? '';
                              this.selectedTeacherId = found.default_teacher_id ? String(found.default_teacher_id) : '';
                          }
                      },
                      get isCurrentlyAssigned() {
                          if (!this.selectedName && !this.selectedCode) return false;
                          const found = this.availableMasterSubjects.find(s => s.name === this.selectedName || s.grade_code === this.selectedCode || s.code === this.selectedCode);
                          return found ? !!found.is_already_assigned : false;
                      }
                  }">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class &amp; Section *</label>
                    <select name="class_id" x-model="selectedClassId" @change="selectedName = ''; selectedCode = ''; weeklyPeriods = ''; selectedTeacherId = ''" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                        <option value="">-- Choose Class --</option>
                        @foreach($allClasses as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->display_name }} (Grade {{ $c->grade_level }} &bull; {{ $c->stream ?: 'Stream not set' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Subject Name *
                            <span class="text-[10px] text-slate-400 font-normal">(From Master)</span>
                        </label>
                        <select name="name" x-model="selectedName" @change="onSelectName($event.target.value)" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                            <option value="">-- Select Subject Name --</option>
                            <template x-for="subj in availableMasterSubjects" :key="subj.id">
                                <option :value="subj.name" :disabled="subj.is_already_assigned"
                                        x-text="subj.name + ' (' + subj.category + ')' + (subj.is_already_assigned ? ' — [Already Added]' : '')">
                                </option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Subject Code *
                            <span class="text-[10px] text-slate-400 font-normal">(Auto-Filled / Select)</span>
                        </label>
                        <select name="code" x-model="selectedCode" @change="onSelectCode($event.target.value)" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono font-bold">
                            <option value="">-- Select Subject Code --</option>
                            <template x-for="subj in availableMasterSubjects" :key="'c-' + subj.id">
                                <option :value="subj.grade_code" :disabled="subj.is_already_assigned"
                                        x-text="subj.grade_code + ' (' + subj.name + ')'">
                                </option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Faculty In Charge</label>
                        <select name="teacher_id" x-model="selectedTeacherId" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            <option value="">Use subject master default / unassigned</option>
                            @foreach($allTeachers as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Periods/Wk</label>
                        <input type="number" name="weekly_periods" x-model="weeklyPeriods" min="1" max="40" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono font-bold">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="teacher_override" value="1"> Override the global subject teacher for this class only</label>
                <!-- Apply a new default across all current and future class mappings -->
                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                    <label class="flex items-start space-x-2 cursor-pointer">
                        <input type="checkbox" name="apply_globally" value="1" class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Auto-Assign Globally (All Classes with this Subject)</span>
                            <span class="block text-[10px] text-slate-500">Sets the subject master default, updates existing class mappings with conflict checks, and applies automatically to future sections. Use the class-only override when needed.</span>
                        </div>
                    </label>
                </div>

                <!-- Optional Timetable Link Accordion -->
                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 space-y-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="link_timetable" value="1" x-model="linkRoutine" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Optional: Schedule a Period in Timetable Now</span>
                    </label>

                    <div x-show="linkRoutine" class="space-y-2 pt-1 border-t border-slate-200 dark:border-slate-700">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-semibold text-slate-600 dark:text-slate-400 text-[10px] mb-0.5">Day of Week</label>
                                <select name="day_of_week" class="w-full px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
@foreach(config('school_timetable.days', []) as $day)<option value="{{ $day }}">{{ $day }}</option>@endforeach
</select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 dark:text-slate-400 text-[10px] mb-0.5">Period Slot</label>
                                <select name="period_number" class="w-full px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                                    @foreach($stats['period_timings'] as $num => $tInfo)
                                    <option value="{{ $num }}">{{ $tInfo['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-600 dark:text-slate-400 text-[10px] mb-0.5">Room Number</label>
                            <input type="text" name="room_number" placeholder="Room or lab name" 
                                   class="w-full px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button @click="openAssignModal = false" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-xs"
                            x-text="isCurrentlyAssigned ? 'Edit' : 'Save Assignment'">
                        Save Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: ASSIGN CLASS TEACHER (HOMEROOM) -->
    <!-- ======================================================== -->
    <div x-show="openClassTeacherModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openClassTeacherModal = false" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Assign Class Teacher</h3>
                    <p class="text-[11px] text-slate-500">Assign official homeroom in-charge for student roll call and reports.</p>
                </div>
                <button @click="openClassTeacherModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('school.assignments.class-teacher.assign') }}" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Class &amp; Section *</label>
                    <select name="class_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                        <option value="">-- Choose Class --</option>
                        @foreach($allClasses as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->display_name }} (Current: {{ $c->classTeacher ? $c->classTeacher->name : 'Unassigned' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Teacher *</label>
                    <select name="teacher_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                        <option value="">-- Choose Faculty Member --</option>
                        @foreach($allTeachers as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->name }} ({{ $t->email }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button @click="openClassTeacherModal = false" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-xs">
                        Assign Homeroom In-Charge
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 3: SCHEDULE PERIOD TIMETABLE SLOT -->
    <!-- ======================================================== -->
    <div x-show="openTimetableModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openTimetableModal = false" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Schedule Timetable Period</h3>
                    <p class="text-[11px] text-slate-500">Maps teacher &amp; subject to a live weekly period slot with conflict checking.</p>
                </div>
                <button @click="openTimetableModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('school.assignments.timetable.store') }}" class="space-y-3 text-xs" x-data="{
                classId: '{{ $selectedClassId ?? '' }}',
                selectedSubjectId: '',
                selectedTeacherId: '',
                subjectsList: @js($allClasses->mapWithKeys(fn($c) => [$c->id => $c->subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'teacher_id' => $s->teacher_id ?? ($c->academic_year === \App\Models\AcademySetting::get('academic_session') ? $s->subjectMaster?->default_teacher_id : null)])])),
                get availableSubjects() { return this.subjectsList[this.classId] || []; },
                selectSubject() { const item = this.availableSubjects.find(s => String(s.id) === String(this.selectedSubjectId)); this.selectedTeacherId = item && item.teacher_id ? String(item.teacher_id) : ''; }
            }">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class *</label>
                    <select name="class_id" x-model="classId" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                        <option value="">-- Choose Class --</option>
                        @foreach($allClasses as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subject *</label>
                        <select name="subject_id" x-model="selectedSubjectId" @change="selectSubject()" required class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            <option value="">-- Choose Subject --</option>
                            <template x-for="sub in availableSubjects" :key="sub.id"><option :value="sub.id" x-text="sub.name"></option></template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Teacher *</label>
                        <select name="teacher_id" x-model="selectedTeacherId" required class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            <option value="">-- Assign subject teacher first --</option>
                            @foreach($allTeachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Day of Week *</label>
                        <select name="day_of_week" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
@foreach(config('school_timetable.days', []) as $day)<option value="{{ $day }}">{{ $day }}</option>@endforeach
</select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Period Number *</label>
                        <select name="period_number" required class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            @foreach($stats['period_timings'] as $num => $tInfo)
                            <option value="{{ $num }}">{{ $tInfo['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300"><input type="checkbox" name="teacher_override" value="1"> Explicitly override the subject's assigned teacher</label>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Room Number (Optional)</label>
                    <input type="text" name="room_number" placeholder="Room or lab name" 
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button @click="openTimetableModal = false" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-bold shadow-xs">
                        Validate &amp; Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 4: EDIT SUBJECT ASSIGNMENT -->
    <!-- ======================================================== -->
    <div x-show="openEditModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="openEditModal = false" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Edit Subject Assignment</h3>
                    <p class="text-[11px] text-slate-500" x-text="editSubject.class_name"></p>
                </div>
                <button @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold">✕</button>
            </div>

            <form method="POST" :action="'/school/assignments/subjects/' + editSubject.id" class="space-y-3 text-xs"
                  x-data="{
                      masterSubjects: {{ Js::from($allMasterSubjects) }},
                      onEditSelectName(name) {
                          editSubject.name = name;
                          const found = this.masterSubjects.find(s => s.name === name);
                          if (found) {
                              editSubject.code = found.code;
                              if (!editSubject.weekly_periods) editSubject.weekly_periods = found.default_periods;
                          }
                      },
                      onEditSelectCode(code) {
                          editSubject.code = code;
                          const found = this.masterSubjects.find(s => (s.code === code || s.grade_code === code));
                          if (found) {
                              editSubject.name = found.name;
                              if (!editSubject.weekly_periods) editSubject.weekly_periods = found.default_periods;
                          }
                      }
                  }">
                @csrf
                @method('PUT')

                <div class="rounded-lg bg-slate-50 dark:bg-slate-800 p-3">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Subject identity (managed in Subject Master)</div>
                    <div class="font-bold text-slate-900 dark:text-white" x-text="editSubject.name"></div>
                    <div class="font-mono text-xs text-slate-500" x-text="editSubject.code"></div>
                    <input type="hidden" name="name" :value="editSubject.name">
                    <input type="hidden" name="code" :value="editSubject.code">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Subject Code *
                            <span class="text-[10px] text-slate-400 font-normal">(Select / Auto)</span>
                        </label>
                        <select name="code" x-model="editSubject.code" @change="onEditSelectCode($event.target.value)" required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 uppercase font-mono font-bold">
                            <option value="">-- Choose Code --</option>
                            <template x-for="subj in masterSubjects" :key="'edit-mc-' + subj.id">
                                <option :value="subj.code" x-text="subj.code + ' (' + subj.name + ')'" :selected="editSubject.code === subj.code"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Weekly Periods *</label>
                        <input type="number" name="weekly_periods" x-model="editSubject.weekly_periods" required min="1" max="40" 
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Faculty Member</label>
                    <select name="teacher_id" x-model="editSubject.teacher_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold">
                        <option value="">Use subject master default / unassigned</option>
                        @foreach($allTeachers as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                    <label class="flex items-center space-x-2 cursor-pointer text-xs"><input type="checkbox" name="teacher_override" value="1" class="rounded" :checked="!!editSubject.default_teacher_id && String(editSubject.teacher_id) !== String(editSubject.default_teacher_id)"><span>Override the global default for this class only</span></label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="apply_globally" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Update global default and all classes using this subject</span>
                    </label>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <button @click="openEditModal = false" type="button" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-xs">
                        Update Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
