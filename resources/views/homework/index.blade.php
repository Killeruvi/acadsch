@extends('layouts.app')

@section('title', 'Daily Homework Management' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ 
    openAssignModal: false,
    openEditModal: false,
    editTask: {
        id: null,
        class_id: '',
        subject_id: '',
        title: '',
        assigned_date: '',
        due_date: '',
        instructions: ''
    },
    selectedClassId: '{{ $selectedClass->id ?? '' }}',
    classesData: {{ Js::from($allClasses) }},
    get availableSubjects() {
        const cls = this.classesData.find(c => c.id == this.selectedClassId);
        return cls ? cls.subjects : [];
    },
    openEdit(t) {
        this.editTask = {
            id: t.id,
            class_id: t.class_id,
            subject_id: t.subject_id,
            title: t.title,
            assigned_date: t.assigned_date,
            due_date: t.due_date,
            instructions: t.instructions || ''
        };
        this.openEditModal = true;
    }
}" class="space-y-4 max-w-6xl mx-auto py-2 sm:py-3">

    <!-- Clean Compact Header (No giant bloated banner) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs transition-colors">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        📚 Daily Homework
                    </span>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-bold">
                        🔥 <em>"तेजस्वि नावधीतमस्तु"</em> &bull; Ignite Brilliance
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5">
                    Daily Homework &amp; Study Tasks
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Curriculum homework assignments with submission tracking, teacher verification, and academic XP rewards.
                </p>
            </div>

            <div class="flex items-center space-x-2 flex-shrink-0">
                @if(Auth::user()->isStudent())
                <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-1.5 text-center">
                    <span class="text-[10px] text-slate-400 block font-medium">Completed</span>
                    <span class="text-xs font-black text-amber-500 dark:text-amber-400">{{ count($mySubmissions) }} Tasks (+20 XP ea)</span>
                </div>
                @else
                <button @click.stop="openAssignModal = true" 
                        type="button" 
                        class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>+ Assign Daily Homework</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- SIMPLE LEAST-COMPLICATED FORM: SELECT CLASS & SELECT SUBJECT (Form-Based Selection) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form action="{{ route('homework.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-end">
            <!-- 1. Select Class -->
            <div>
                <label for="homework-class" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Step 1: Select Class / Grade
                </label>
                <select id="homework-class" name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    @foreach($allClasses as $c)
                    <option value="{{ $c->grade_level }}" {{ (int)request('class_id', $selectedGrade) === (int)$c->grade_level ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Select Subject -->
            <div>
                <label for="homework-subject" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Step 2: Select Subject in {{ $selectedClass->display_name ?? 'Class' }}
                </label>
                <select id="homework-subject" name="subject_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="all" {{ $selectedSubjectId === 'all' || !$selectedSubjectId ? 'selected' : '' }}>All Subjects in {{ $selectedClass->display_name ?? 'Class' }} ({{ $subjects->count() }})</option>
                    @foreach($subjects as $subj)
                    <option value="{{ $subj->id }}" {{ (string)$selectedSubjectId === (string)$subj->id ? 'selected' : '' }}>
                        {{ $subj->name }} ({{ $subj->code }})
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filter by Keyword -->
            <div>
                <label for="homework-search" class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Filter by Keyword
                </label>
                <div class="flex items-center space-x-1.5">
                    <input type="text" 
                           id="homework-search"
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="Search homework..." 
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <button type="submit" class="px-3.5 py-2 bg-slate-900 dark:bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-xs flex-shrink-0">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Homework Tasks Feed -->
    <div class="space-y-3">
        @forelse($homeworkList as $task)
        @php
            $submission = $mySubmissions[$task->id] ?? null;
            if ($submission instanceof \Illuminate\Support\Collection) $submission = $submission->first();
        @endphp
        <div x-data="{ expanded: false }" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden transition hover:border-slate-300 dark:hover:border-slate-700">
            <div class="p-4 sm:p-5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs">
                            {{ $task->subject->name ?? 'Curriculum Subject' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                            {{ $task->academicClass->display_name ?? 'Class' }}
                        </span>
                        <span class="text-slate-400 text-xs">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 text-xs font-medium">Assigned: {{ $task->assigned_date->format('M d, Y') }}</span>
                        <span class="text-slate-400 text-xs">&bull;</span>
                        <span class="text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded">
                            Due: {{ $task->due_date->format('M d, Y') }}
                        </span>
                    </div>

                    <!-- Status Indicator -->
                    <div>
                        @if(Auth::user()->isStudent() || Auth::user()->isParent())
                            @if($submission)
                                @if($submission->status === 'checked')
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-extrabold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        Verified Checked ✅
                                    </span>
                                @elseif($submission->status === 'incomplete')
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-extrabold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        Action Required ⚠️
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-extrabold bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Submitted ⏳ Pending Review
                                    </span>
                                @endif
                            @else
                                <span class="px-2.5 py-1 rounded-xl text-xs font-extrabold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    Not Submitted ✍️
                                </span>
                            @endif
                        @else
                            <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ Auth::user()->isAdmin() || Auth::user()->isTeacher() ? $task->submissions_count : ($submission ? 'Child submitted' : 'Awaiting submission') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">
                        {{ $task->title }}
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 whitespace-pre-wrap leading-relaxed">
                        {{ $task->instructions }}
                    </p>

                    @if($task->hasAttachment())
                    <div class="mt-2.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base">{{ $task->attachment_icon }}</span>
                            <div class="truncate">
                                <span class="font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $task->attachment_name }}</span>
                                <span class="text-[10px] text-slate-500 uppercase">{{ $task->attachment_type }} &bull; {{ $task->formatted_attachment_size }} &bull; Reference Document</span>
                            </div>
                        </div>
                        <a href="{{ route('homework.download-attachment', ['id' => $task->id, 'type' => 'task']) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white shrink-0 transition"
                           download>
                            <span>⬇️ Download File</span>
                        </a>
                    </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">
                        Teacher: <strong class="text-slate-700 dark:text-slate-200">{{ $task->teacher->name ?? 'Faculty Member' }}</strong>
                    </span>

                    <div class="flex items-center space-x-2">
                        @if(Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $task->teacher_id === Auth::id()))
                        <button type="button" 
                                @click="openEdit({
                                    id: {{ $task->id }},
                                    class_id: '{{ $task->class_id }}',
                                    subject_id: '{{ $task->subject_id }}',
                                    title: '{{ addslashes($task->title) }}',
                                    assigned_date: '{{ $task->assigned_date ? $task->assigned_date->format('Y-m-d') : '' }}',
                                    due_date: '{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}',
                                    instructions: '{{ addslashes(str_replace(["\r", "\n"], [' ', ' '], $task->instructions)) }}'
                                })" 
                                class="px-2 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 transition" 
                                title="Edit Homework">
                            ✏️ Edit
                        </button>
                        <form method="POST" action="{{ route('homework.destroy', $task->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to delete this homework assignment?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 rounded-lg text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-800 transition" title="Delete Homework">
                                🗑️
                            </button>
                        </form>
                        @endif
                        <button @click="expanded = !expanded" 
                                type="button" 
                                class="font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 flex items-center space-x-1">
                            <span x-text="expanded ? 'Hide Details' : 'View Submission & Notes'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Expanded Details & Submission / Verification Workspace -->
            <div x-show="expanded" x-cloak class="bg-slate-50 dark:bg-slate-950/60 p-4 sm:p-5 border-t border-slate-100 dark:border-slate-800 space-y-4">
                <!-- STUDENT SUBMISSION PANEL -->
                @if(Auth::user()->isStudent())
                    @if($submission)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Your Submitted Work</h4>
                                <span class="text-[11px] text-slate-400 font-medium">Submitted {{ $submission->created_at->diffForHumans() }}</span>
                            </div>
                            @if($submission->student_notes)
                            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-mono whitespace-pre-wrap">
                                {{ $submission->student_notes }}
                            </div>
                            @endif

                            @if($submission->hasAttachment())
                            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-base">{{ $submission->attachment_icon }}</span>
                                    <div class="truncate">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $submission->attachment_name }}</span>
                                        <span class="text-[10px] text-slate-500 uppercase">{{ $submission->attachment_type }} &bull; {{ $submission->formatted_attachment_size }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('homework.download-attachment', ['id' => $submission->id, 'type' => 'submission']) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shrink-0 transition"
                                   download>
                                    <span>⬇️ Download My Submission</span>
                                </a>
                            </div>
                            @endif

                            @if($submission->teacher_feedback)
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-900 dark:text-emerald-200">
                                <strong class="text-emerald-800 dark:text-emerald-300">Teacher's Feedback:</strong> {{ $submission->teacher_feedback }}
                            </div>
                            @endif
                        </div>
                    @else
                        <form method="POST" action="{{ route('homework.submit', $task->id) }}" enctype="multipart/form-data" class="space-y-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Your Solution / Homework Response
                                </label>
                                <textarea name="student_notes" rows="3" placeholder="Write your answers, calculations, solution notes, or summary here (optional if attaching a document/slides)..." class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-xs font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    📎 Attach Homework File / Slides Deck / Document (Optional, PDF, PPT/PPTX, DOC/DOCX, Images, Zip - up to 50MB)
                                </label>
                                <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                                       class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-300 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-950 px-2 py-1.5">
                                <span class="text-[11px] text-slate-400 block">Submitting daily homework earns you +20 Academic XP! You can provide written notes, upload a presentation/document file, or both.</span>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                                    <span>Submit Homework</span>
                                    <span class="bg-emerald-800/60 px-1.5 py-0.5 rounded text-[10px] text-amber-300 font-extrabold">+20 XP</span>
                                </button>
                            </div>
                        </form>
                    @endif
                @endif

                <!-- PARENT VIEW: only the linked child's submission, never the full class roster -->
                @if(Auth::user()->isParent())
                    @if($submission)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 space-y-2">
                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">Linked Student Submission</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{{ $submission->student_notes }}</p>
                            @if($submission->teacher_feedback)
                                <p class="text-xs text-emerald-700 dark:text-emerald-300">Teacher feedback: {{ $submission->teacher_feedback }}</p>
                            @endif
                            @if($submission->hasAttachment())
                                <a class="text-xs font-semibold text-indigo-600" href="{{ route('homework.download-attachment', ['id' => $submission->id, 'type' => 'submission']) }}">Download linked student's submission</a>
                            @endif
                        </div>
                    @else
                        <p class="text-xs text-slate-500">No linked student submission has been recorded yet.</p>
                    @endif
                @endif

                <!-- TEACHER / ADMIN VERIFICATION PANEL -->
                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Student Homework Submissions</h4>

                        <div class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            @forelse($task->submissions as $sub)
                            <div class="p-3.5 space-y-2.5 hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-bold text-slate-900 dark:text-white text-xs">{{ $sub->student->name }}</span>
                                        <span class="text-slate-400 text-[11px] ml-2 font-mono">{{ $sub->student->student_id_number }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ $sub->created_at->format('M d, h:i A') }}</span>
                                </div>

                                <div class="p-2.5 bg-slate-50 dark:bg-slate-950 rounded-lg text-xs text-slate-800 dark:text-slate-200 font-mono whitespace-pre-wrap">
                                    {{ $sub->student_notes }}
                                </div>

                                @if($sub->hasAttachment())
                                <div class="p-2 bg-emerald-50/60 dark:bg-emerald-950/40 rounded-lg border border-emerald-200 dark:border-emerald-800 flex items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span>{{ $sub->attachment_icon }}</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ $sub->attachment_name }}</span>
                                        <span class="text-[10px] text-slate-400">({{ $sub->formatted_attachment_size }})</span>
                                    </div>
                                    <a href="{{ route('homework.download-attachment', ['id' => $sub->id, 'type' => 'submission']) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-md bg-emerald-600 hover:bg-emerald-500 text-white shrink-0 transition"
                                       download>
                                        <span>⬇️ Download Work</span>
                                    </a>
                                </div>
                                @endif

                                <!-- Teacher Verification Form -->
                                <form method="POST" action="{{ route('homework.submissions.check', $sub->id) }}" class="flex flex-col sm:flex-row sm:items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    @csrf
                                    @method('PATCH')

                                    <div class="flex-1">
                                        <input type="text" name="teacher_feedback" value="{{ $sub->teacher_feedback }}" placeholder="Optional feedback for student..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <select name="status" class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                                            <option value="checked" {{ $sub->status === 'checked' ? 'selected' : '' }}>Checked ✅</option>
                                            <option value="incomplete" {{ $sub->status === 'incomplete' ? 'selected' : '' }}>Incomplete ⚠️</option>
                                            <option value="missing" {{ $sub->status === 'missing' ? 'selected' : '' }}>Missing ❌</option>
                                        </select>

                                        <button type="submit" class="px-3 py-1.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition">
                                            Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                            @empty
                            <div class="p-5 text-center text-slate-400 text-xs">
                                No submissions received for this homework task yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-400 space-y-2 shadow-xs">
            <div class="text-2xl">📚</div>
            <p class="font-bold text-slate-700 dark:text-slate-300 text-xs">No daily homework found for the selected criteria.</p>
            <p class="text-[11px] text-slate-400">Assignments will be listed here daily.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $homeworkList->links() }}
    </div>

    <!-- ASSIGN HOMEWORK MODAL (TEACHER / ADMIN) -->
    @if(!Auth::user()->isStudent())
    <div x-show="openAssignModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="openAssignModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-5 sm:p-6 animate-in fade-in duration-150 z-10">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Assign Daily Homework</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Provide instructions, target class, subject, and due date</p>
                </div>
                <button type="button" @click="openAssignModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('homework.store') }}" enctype="multipart/form-data" class="space-y-3.5 mt-3">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Class *</label>
                        <select name="class_id" x-model="selectedClassId" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            @foreach($allClasses as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Subject *</label>
                        <select name="subject_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="">-- Choose Subject --</option>
                            @foreach($allClasses as $c)
                                @foreach($c->subjects as $subj)
                                    <option value="{{ $subj->id }}" x-show="selectedClassId == {{ $c->id }}" :hidden="selectedClassId != {{ $c->id }}" :disabled="selectedClassId != {{ $c->id }}">{{ $subj->name }} ({{ $c->display_name }})</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Homework Title *</label>
                    <input type="text" name="title" required placeholder="e.g. NCERT Chapter 6 Ex 6.1 Questions 1 to 5" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Assigned Date *</label>
                        <input type="date" name="assigned_date" required value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Due Date *</label>
                        <input type="date" name="due_date" required value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Detailed Instructions / Questions *</label>
                    <textarea name="instructions" rows="3" required placeholder="Outline the problems, exercise questions, diagrams to draw, or questions to solve..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        📎 Reference Material / Question Sheet / Slides Deck (Optional, PDF, PPT/PPTX, DOC/DOCX, up to 50MB)
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-950 px-2 py-1.5">
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openAssignModal = false" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Assign Homework &amp; Notify Class
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT HOMEWORK MODAL (TEACHER / ADMIN) -->
    <div x-show="openEditModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="openEditModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-5 sm:p-6 animate-in fade-in duration-150 z-10">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Daily Homework</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Modify instructions, target class, subject, or dates</p>
                </div>
                <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" :action="'{{ url('/homework') }}/' + editTask.id" enctype="multipart/form-data" class="space-y-3.5 mt-3">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Class *</label>
                        <select name="class_id" x-model="editTask.class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            @foreach($allClasses as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Subject *</label>
                        <select name="subject_id" x-model="editTask.subject_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                            <option value="">-- Choose Subject --</option>
                            @foreach($allClasses as $c)
                                @foreach($c->subjects as $subj)
                                    <option value="{{ $subj->id }}" x-show="editTask.class_id == {{ $c->id }}" :hidden="editTask.class_id != {{ $c->id }}" :disabled="editTask.class_id != {{ $c->id }}">{{ $subj->name }} ({{ $c->display_name }})</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Homework Title *</label>
                    <input type="text" name="title" x-model="editTask.title" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Assigned Date *</label>
                        <input type="date" name="assigned_date" x-model="editTask.assigned_date" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Due Date *</label>
                        <input type="date" name="due_date" x-model="editTask.due_date" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Detailed Instructions / Questions *</label>
                    <textarea name="instructions" rows="3" x-model="editTask.instructions" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        📎 Replace Reference Material (Optional, leave blank to keep current)
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.txt,.odt,.rtf,.png,.jpg,.jpeg,.webp,.zip"
                           class="block w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-950 px-2 py-1.5">
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openEditModal = false" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Update Homework
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
