@extends('layouts.app')

@section('title', "Lesson: {$lesson->topic->title} - Bloom's Distribution")

@section('content')
<div x-data="{ 
    openImportModal: false, 
    openEditModal: false, 
    openReassignModal: false, 
    openRevisionModal: false, 
    openAddModal: false,
    editForm: {
        id: null,
        level: 1,
        level_name: '',
        prompt: '',
        rubric: '',
        due_date: '',
        status: 'assigned',
        score: '',
        feedback: ''
    },
    reassignForm: {
        id: null,
        studentName: '',
        currentRoll: '',
        student_id: ''
    },
    revisionForm: {
        id: null,
        studentName: '',
        feedback: ''
    },
    openEdit(item) {
        this.editForm = {
            id: item.id,
            level: item.bloom_level,
            level_name: item.level_name,
            prompt: item.activity_prompt,
            rubric: item.rubric_guide || '',
            due_date: item.due_date || '',
            status: item.status,
            score: item.score !== null ? item.score : '',
            feedback: item.teacher_feedback || ''
        };
        this.openEditModal = true;
    },
    openReassign(item) {
        this.reassignForm = {
            id: item.id,
            studentName: item.student ? item.student.name : 'Unknown',
            currentRoll: item.roll_number,
            student_id: item.student_id
        };
        this.openReassignModal = true;
    },
    openRevision(item) {
        this.revisionForm = {
            id: item.id,
            studentName: item.student ? item.student.name : 'Unknown',
            feedback: item.teacher_feedback || ''
        };
        this.openRevisionModal = true;
    }
}" class="space-y-6">

    <!-- Lesson Overview Banner -->
    <div class="bg-navy-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs">
                <span class="px-2.5 py-0.5 rounded-md font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    {{ $lesson->academicClass->name }} (Sec {{ $lesson->academicClass->section }})
                </span>
                <span class="text-slate-400">&bull;</span>
                <span class="text-slate-300 font-semibold">{{ $lesson->subject->name }}</span>
                <span class="text-slate-400">&bull;</span>
                <span class="text-slate-300">{{ $lesson->lesson_date->format('M d, Y') }}</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-2">{{ $lesson->topic->title }}</h1>

            @if($lesson->subtopic)
            <div class="text-xs text-emerald-400 font-medium mt-1">
                Focus Subtopic: {{ $lesson->subtopic }}
            </div>
            @endif

            <p class="text-xs text-slate-300 mt-2 max-w-2xl leading-relaxed">
                {{ $lesson->lesson_notes ?? 'Standard curriculum lesson conducted. Random Bloom\'s levels distributed by student roll number.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Download Tasks Template -->
            <a href="{{ route('blooms.sample-tasks-csv') }}" 
               class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center space-x-1.5"
               title="Download Tasks CSV Template">
                <span>📄 Tasks Sample</span>
            </a>

            <!-- Import Tasks CSV -->
            <button type="button" @click="openImportModal = true"
                    class="px-3.5 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-600/30 transition flex items-center space-x-1.5">
                <span>📥 Import Tasks CSV</span>
            </button>

            <!-- Export Assignments CSV -->
            <a href="{{ route('blooms.lesson.export-csv', $lesson->id) }}" 
               class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/30 transition flex items-center space-x-1.5"
               title="Export full roster assignments to CSV">
                <span>📤 Export CSV</span>
            </a>

            <!-- Reshuffle All Tasks Button -->
            <form method="POST" action="{{ route('blooms.lesson.reshuffle', $lesson->id) }}" 
                  onsubmit="return confirm('Reshuffle all Bloom\'s cognitive tasks randomly across class students?');"
                  class="inline-block">
                @csrf
                <button type="submit" 
                        class="px-3.5 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-600/30 transition flex items-center space-x-1.5">
                    <span>🎲 Reshuffle All</span>
                </button>
            </form>

            <!-- Add Custom Task -->
            <button type="button" @click="openAddModal = true"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center space-x-1.5">
                <span>➕ Add Task</span>
            </button>

            <a href="{{ route('blooms.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition">
                &larr; All Lessons
            </a>

            @if(Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $lesson->teacher_id === Auth::id()))
            <form method="POST" action="{{ route('blooms.lesson.destroy', $lesson->id) }}" 
                  onsubmit="return confirm('CAUTION: Are you sure you want to delete this entire daily Bloom lesson and all student assignments?');"
                  class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/30 transition flex items-center space-x-1.5"
                        title="Delete Entire Lesson">
                    <span>🗑️ Delete Lesson</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- 6 Bloom's Cognitive Levels Distribution Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($breakdown as $b)
        <a href="{{ route('blooms.lesson', ['id' => $lesson->id, 'level' => $b['level']]) }}" 
           class="p-3.5 rounded-xl border {{ $selectedLevel == $b['level'] ? 'border-indigo-600 ring-2 ring-indigo-500/30 bg-indigo-50/20' : 'border-slate-200 bg-white' }} hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">LEVEL {{ $b['level'] }}</span>
                    <span class="text-xs font-black px-1.5 py-0.5 rounded {{ $b['badge_color'] }}">{{ $b['count'] }}</span>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900 mt-1">{{ $b['name'] }}</h4>
            </div>
            <div class="mt-2 text-[10px] text-slate-500 line-clamp-1" title="{{ implode(', ', $b['students']) }}">
                {{ count($b['students']) > 0 ? implode(', ', $b['students']) : 'None' }}
            </div>
        </a>
        @endforeach
    </div>

    <!-- Filter and Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('blooms.lesson', $lesson->id) }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <!-- Level Filter -->
                <select name="level" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-semibold bg-white text-slate-700">
                    <option value="">All Bloom's Levels</option>
                    @for($i = 1; $i <= 6; $i++)
                        @php $m = \App\Models\TopicBloomActivity::getLevelMetadata($i); @endphp
                        <option value="{{ $i }}" {{ $selectedLevel == $i ? 'selected' : '' }}>Level {{ $i }}: {{ $m['name'] }}</option>
                    @endfor
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-semibold bg-white text-slate-700">
                    <option value="">All Statuses</option>
                    <option value="assigned" {{ $selectedStatus === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="submitted" {{ $selectedStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="reviewed" {{ $selectedStatus === 'reviewed' ? 'selected' : '' }}>Reviewed / Graded</option>
                    <option value="revision_requested" {{ $selectedStatus === 'revision_requested' ? 'selected' : '' }}>Revision Requested</option>
                </select>

                @if($selectedLevel || $selectedStatus || $searchQuery)
                <a href="{{ route('blooms.lesson', $lesson->id) }}" class="px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 font-bold transition">
                    Clear Filters
                </a>
                @endif
            </div>

            <!-- Search input -->
            <div class="flex items-center space-x-2 w-full sm:w-72">
                <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Search student or prompt..."
                       class="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-hidden">
                <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Roll Number Assignments & Submissions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Roll-Number Assignment Roster</h3>
                <p class="text-xs text-slate-500">Targeted cognitive assignments, submissions, editing &amp; re-assignment controls</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-full">
                    {{ $filteredAssignments->count() }} of {{ $lesson->assignments->count() }} Tasks
                </span>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($filteredAssignments as $assignment)
            @php $meta = $assignment->metadata; @endphp
            <div class="p-5 hover:bg-slate-50/70 transition space-y-3">
                <!-- Header row: Roll, Student Name, Level Badge, Status, Controls -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center font-mono font-black text-xs shadow-sm">
                            #{{ $assignment->roll_number }}
                        </span>
                        <div>
                            <span class="font-bold text-slate-900 text-sm">{{ $assignment->student->name ?? 'Unassigned' }}</span>
                            <span class="text-xs text-slate-400 font-mono ml-1">({{ $assignment->student->student_id_number ?? 'ID N/A' }})</span>
                            @if($assignment->student?->email)
                            <span class="text-[11px] text-slate-400 hidden sm:inline ml-2">&bull; {{ $assignment->student->email }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Bloom Level Badge -->
                        <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider {{ $meta['badge_color'] }}">
                            Level {{ $assignment->bloom_level }}: {{ $assignment->level_name }}
                        </span>

                        <!-- Due Date -->
                        @if($assignment->due_date)
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $assignment->due_date->isPast() ? 'bg-rose-50 text-rose-700 border border-rose-200 font-bold' : 'bg-slate-100 text-slate-600' }}">
                            📅 {{ $assignment->due_date->format('M d') }}
                        </span>
                        @endif

                        <!-- Status Badge -->
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                            @if($assignment->status === 'reviewed') bg-emerald-100 text-emerald-800
                            @elseif($assignment->status === 'submitted') bg-amber-100 text-amber-800
                            @elseif($assignment->status === 'revision_requested') bg-orange-100 text-orange-800
                            @else bg-slate-100 text-slate-600 @endif">
                            {{ str_replace('_', ' ', $assignment->status) }}
                        </span>

                        <!-- Score Badge -->
                        @if($assignment->score !== null)
                        <span class="px-2 py-0.5 rounded font-mono font-black text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">
                            💯 {{ $assignment->score }}/100
                        </span>
                        @endif

                        <!-- Quick Action Menu -->
                        <div class="flex items-center space-x-1 pl-2 border-l border-slate-200">
                            <!-- Edit Button -->
                            <button type="button" 
                                    @click="openEdit({{ json_encode($assignment) }})" 
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition" 
                                    title="Edit Task & Rubric">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>

                            <!-- Re-assign Button -->
                            <button type="button" 
                                    @click="openReassign({{ json_encode($assignment) }})" 
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" 
                                    title="Re-assign to Another Student">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            </button>

                            <!-- Reroll Level Button -->
                            <form method="POST" action="{{ route('blooms.assignment.reroll', $assignment->id) }}" class="inline-block"
                                  onsubmit="return confirm('Reroll Bloom\'s cognitive level and task for this student?');">
                                @csrf
                                <button type="submit" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-purple-600 hover:bg-purple-50 transition" 
                                        title="Reroll Level / Randomize Task">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </button>
                            </form>

                            <!-- Request Revision Button (if submitted or reviewed) -->
                            @if(in_array($assignment->status, ['submitted', 'reviewed']))
                            <button type="button" 
                                    @click="openRevision({{ json_encode($assignment) }})" 
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" 
                                    title="Request Revision from Student">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            </button>
                            @endif

                            <!-- Delete Button -->
                            <form method="POST" action="{{ route('blooms.assignment.destroy', $assignment->id) }}" class="inline-block"
                                  onsubmit="return confirm('Remove this assignment from the roster?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" 
                                        title="Delete Assignment">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Assigned Prompt & Rubric -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed font-medium space-y-1.5">
                    <div>
                        <span class="font-bold text-slate-900 block mb-0.5">Assigned Activity Prompt:</span>
                        <div class="whitespace-pre-line">{{ $assignment->activity_prompt }}</div>
                    </div>
                    @if($assignment->rubric_guide)
                    <div class="text-[11px] text-slate-500 pt-1.5 border-t border-slate-200/80 flex items-start gap-1">
                        <span class="font-bold text-slate-700">Evaluation Rubric / Hints:</span>
                        <span>{{ $assignment->rubric_guide }}</span>
                    </div>
                    @endif
                    @if($assignment->hasTeacherAttachment())
                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 min-w-0 text-slate-800">
                            <span>{{ $assignment->teacher_attachment_icon }}</span>
                            <span class="truncate font-semibold text-xs">{{ $assignment->teacher_attachment_name }}</span>
                            <span class="text-[10px] text-slate-400">({{ $assignment->formatted_teacher_attachment_size }})</span>
                        </div>
                        <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'teacher']) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 shrink-0 transition"
                           download>
                            <span>⬇️ Teacher File</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Student Submission (If Submitted) -->
                @if($assignment->submission_content || $assignment->hasSubmissionAttachment())
                <div class="p-4 rounded-xl bg-emerald-50/40 border border-emerald-200/80 space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold text-emerald-900">
                        <span>Student's Submitted Response:</span>
                        <span class="text-[11px] text-slate-500">{{ $assignment->submitted_at?->diffForHumans() }}</span>
                    </div>
                    @if($assignment->submission_content)
                    <div class="p-3 bg-white rounded-lg border border-emerald-100 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed font-normal">
                        {{ $assignment->submission_content }}
                    </div>
                    @endif

                    @if($assignment->hasSubmissionAttachment())
                    <div class="p-2.5 bg-white rounded-lg border border-emerald-200 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">{{ $assignment->submission_attachment_icon }}</span>
                            <div class="truncate">
                                <span class="font-bold text-slate-900 truncate block">{{ $assignment->submission_attachment_name }}</span>
                                <span class="text-[10px] text-slate-500 uppercase">{{ $assignment->submission_attachment_type }} &bull; {{ $assignment->formatted_submission_attachment_size }}</span>
                            </div>
                        </div>
                        <a href="{{ route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission']) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shrink-0"
                           download>
                            <span>⬇️ Download Work</span>
                        </a>
                    </div>
                    @endif

                    @if($assignment->teacher_feedback)
                    <div class="p-2.5 bg-emerald-100/60 rounded-lg text-xs text-emerald-950 font-medium">
                        <strong>Teacher Evaluation:</strong> {{ $assignment->teacher_feedback }}
                    </div>
                    @endif
                </div>
                @endif

                <!-- Teacher Grading / Evaluation Form -->
                @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                <form method="POST" action="{{ route('blooms.assignment.grade', $assignment->id) }}" class="p-3 rounded-xl bg-white border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    @csrf
                    <div class="w-full sm:w-28 flex-shrink-0">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Score (0-100)</label>
                        <input type="number" step="0.5" name="score" required value="{{ old('score', $assignment->score ?? 90) }}" min="0" max="100"
                               class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-900">
                    </div>

                    <div class="w-full flex-grow">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Teacher Feedback &amp; Rubric Notes</label>
                        <input type="text" name="teacher_feedback" value="{{ old('teacher_feedback', $assignment->teacher_feedback) }}" placeholder="Constructive comments based on Bloom's level criteria..."
                               class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                    </div>

                    <button type="submit" class="w-full sm:w-auto px-4 py-2 mt-4 sm:mt-0 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-sm transition whitespace-nowrap self-end">
                        {{ $assignment->score !== null ? 'Update Score' : 'Save Grade' }}
                    </button>
                </form>
                @endif

            </div>
            @empty
            <div class="p-12 text-center text-slate-400">
                <p class="text-sm font-semibold">No assignments match the selected filters.</p>
                <a href="{{ route('blooms.lesson', $lesson->id) }}" class="text-indigo-600 hover:underline text-xs mt-1 inline-block">
                    Reset filters
                </a>
            </div>
            @endforelse

            @if($filteredAssignments instanceof \Illuminate\Pagination\LengthAwarePaginator && $filteredAssignments->hasPages())
            <div class="p-4 bg-white rounded-xl border border-slate-200">
                {{ $filteredAssignments->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: IMPORT TASKS CSV DIRECTLY TO THIS LESSON -->
    <div x-show="openImportModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openImportModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-8 z-10 animate-in fade-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Import Tasks CSV &amp; Assign</h3>
                        <p class="text-xs text-slate-500">For {{ $lesson->academicClass->name }} &bull; {{ $lesson->topic->title }}</p>
                    </div>
                </div>
                <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('blooms.lesson.import-tasks', $lesson->id) }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Distribution Method</label>
                    <select name="assignment_mode" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        <option value="random">🎲 Random Shuffled Assignment across Class Students</option>
                        <option value="sequential">🔢 Sequential Roll Mapping (Roll 1, 2, 3...)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select CSV File *</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required 
                           class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <p class="text-[11px] text-slate-400 mt-1">Columns: <code>bloom_level, task_prompt, rubric_guide, due_date</code>.</p>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" name="clear_existing" value="1" id="clear_existing" checked
                           class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300">
                    <label for="clear_existing" class="text-xs text-slate-700 font-medium">Replace existing assignments for this lesson</label>
                </div>

                <div class="bg-amber-50/70 rounded-xl p-3 text-xs text-amber-900 border border-amber-200">
                    <span class="font-bold">Need task template?</span>
                    <a href="{{ route('blooms.sample-tasks-csv') }}" class="underline font-semibold ml-1 text-amber-800 hover:text-amber-950">
                        Download Bloom's Tasks CSV template
                    </a>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openImportModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-600/20 transition">
                        Import &amp; Randomly Distribute
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT ASSIGNMENT & RUBRIC -->
    <div x-show="openEditModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openEditModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-xl w-full p-6 sm:p-8 z-10 animate-in fade-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-sm">
                        ✏️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Bloom's Task &amp; Evaluation</h3>
                        <p class="text-xs text-slate-500">Update cognitive level, challenge prompt, rubric and status</p>
                    </div>
                </div>
                <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" :action="'/blooms/assignments/' + editForm.id" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bloom's Level (1 to 6) *</label>
                        <select name="bloom_level" x-model="editForm.level" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                            <option value="1">Level 1: Remember</option>
                            <option value="2">Level 2: Understand</option>
                            <option value="3">Level 3: Apply</option>
                            <option value="4">Level 4: Analyze</option>
                            <option value="5">Level 5: Evaluate</option>
                            <option value="6">Level 6: Create</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" x-model="editForm.due_date"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Activity Prompt / Task Description *</label>
                    <textarea name="activity_prompt" x-model="editForm.prompt" rows="3" required
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rubric Guide / Criteria Hints</label>
                    <textarea name="rubric_guide" x-model="editForm.rubric" rows="2"
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs" placeholder="Evaluation criteria or helpful guidance for the student..."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        📎 Attach / Update Reference Material (PDF, PPT/PPTX, DOC/DOCX, up to 50MB)
                    </label>
                    <input type="file" name="teacher_attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl bg-white px-2 py-1.5">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Assignment Status</label>
                        <select name="status" x-model="editForm.status" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white">
                            <option value="assigned">Assigned</option>
                            <option value="submitted">Submitted</option>
                            <option value="reviewed">Reviewed / Graded</option>
                            <option value="revision_requested">Revision Requested</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Score (0 to 100)</label>
                        <input type="number" step="0.5" min="0" max="100" name="score" x-model="editForm.score"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs" placeholder="Optional score">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Teacher Feedback</label>
                    <input type="text" name="teacher_feedback" x-model="editForm.feedback"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs" placeholder="Teacher notes...">
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                        Update Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: RE-ASSIGN TO DIFFERENT STUDENT -->
    <div x-show="openReassignModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openReassignModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-6 sm:p-8 z-10 animate-in fade-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-sm">
                        🔄
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Re-assign Task</h3>
                        <p class="text-xs text-slate-500">Currently assigned to <span class="font-bold text-slate-800" x-text="reassignForm.studentName"></span></p>
                    </div>
                </div>
                <button type="button" @click="openReassignModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" :action="'/blooms/assignments/' + reassignForm.id + '/reassign'" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select New Target Student *</label>
                    <select name="student_id" x-model="reassignForm.student_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        @foreach($enrolledStudents as $enr)
                            <option value="{{ $enr->student->id }}">Roll #{{ $enr->roll_number }}: {{ $enr->student->name }} ({{ $enr->student->student_id_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900">
                    Re-assigning will transfer this Bloom's task and update the target roll number. Previous submissions on this task will be reset.
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openReassignModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/20 transition">
                        Confirm Re-assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: REQUEST REVISION -->
    <div x-show="openRevisionModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openRevisionModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-6 sm:p-8 z-10 animate-in fade-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                        ↩️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Request Revision</h3>
                        <p class="text-xs text-slate-500">Ask <span class="font-bold text-slate-800" x-text="revisionForm.studentName"></span> to revise their answer</p>
                    </div>
                </div>
                <button type="button" @click="openRevisionModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" :action="'/blooms/assignments/' + revisionForm.id + '/request-revision'" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Feedback &amp; Guidance for Revision *</label>
                    <textarea name="feedback" rows="3" required x-model="revisionForm.feedback"
                              placeholder="Please elaborate on the second mechanism and provide mathematical calculations..."
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-amber-500"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openRevisionModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-600/20 transition">
                        Send Revision Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: ADD CUSTOM TASK -->
    <div x-show="openAddModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openAddModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-8 z-10 animate-in fade-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                        ➕
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Add Custom Bloom's Task</h3>
                        <p class="text-xs text-slate-500">Create a tailored cognitive challenge for an enrolled student</p>
                    </div>
                </div>
                <button type="button" @click="openAddModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('blooms.lesson.assignments.store', $lesson->id) }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Student *</label>
                    <select name="student_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        @foreach($enrolledStudents as $enr)
                            <option value="{{ $enr->student->id }}">Roll #{{ $enr->roll_number }}: {{ $enr->student->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bloom's Level *</label>
                        <select name="bloom_level" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                            <option value="1">Level 1: Remember</option>
                            <option value="2">Level 2: Understand</option>
                            <option value="3">Level 3: Apply</option>
                            <option value="4">Level 4: Analyze</option>
                            <option value="5">Level 5: Evaluate</option>
                            <option value="6">Level 6: Create</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Activity Prompt *</label>
                    <textarea name="activity_prompt" rows="3" required placeholder="Enter cognitive prompt or challenge..."
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rubric Guide / Criteria</label>
                    <textarea name="rubric_guide" rows="2" placeholder="Evaluation criteria or hints for the student..."
                              class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        📎 Reference Material / Slides Deck / Document (Optional, PDF, PPT/PPTX, DOC/DOCX, up to 50MB)
                    </label>
                    <input type="file" name="teacher_attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-xl bg-white px-2 py-1.5">
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openAddModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                        Assign Custom Task
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
