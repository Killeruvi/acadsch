@extends('layouts.app')

@section('title', 'Assignments' . ' — ' . ($school['name'] ?? config('app.name', 'School Management System')))

@section('content')
<div x-data="{ openCreateModal: false, targetType: 'class' }" class="space-y-3">

    <!-- COMPACT METADATA HEADER WITH INLINE PILL BADGES & EMBEDDED SLIM PROGRESS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-800 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60">
                    <span class="mr-1">📁</span>
                    <span>School Coursework</span>
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60">
                    <span class="mr-1">📊</span>
                    <span>Class Continuous Assessment</span>
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ $assignments->total() }} Active
                </span>
            </div>

            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <button @click.stop="openCreateModal = true" 
                    class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-md text-xs font-bold transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Create Assignment</span>
            </button>
            @endif
        </div>

        <div class="flex items-center justify-between pt-0.5">
            <div>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-tight">
                    Academic Assignments &amp; Coursework
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Class-based assignments, subject coursework, submission tracking, and grading records.
                </p>
            </div>
        </div>

        <!-- Embedded Slim Progress Bar (1.5px) -->
        <div class="w-full bg-slate-100 dark:bg-slate-800 h-[1.5px] rounded-full overflow-hidden mt-1">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full" style="width: 100%;"></div>
        </div>
    </div>

    <!-- FILTER BAR: CLASS, SECTION, SUBJECT & SEARCH -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-2.5">
        <form method="GET" action="{{ route('assignments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2 text-xs">
            <!-- Filter by Class -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">Class / Grade</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                    <option value="">All Classes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ (string)$selectedClassId === (string)$c->id ? 'selected' : '' }}>
                            {{ $c->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter by Section -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">Section</label>
                <select name="section_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                    <option value="">All Sections</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ (string)$selectedSectionId === (string)$sec->id ? 'selected' : '' }}>
                            Section {{ $sec->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter by Subject -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">Subject</label>
                <select name="subject_id" onchange="this.form.submit()" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ (string)$selectedSubjectId === (string)$sub->id ? 'selected' : '' }}>
                            {{ $sub->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Search Title / Description -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-0.5">Search</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Keywords, title..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                    Filter
                </button>
                @if(!empty($selectedClassId) || !empty($selectedSectionId) || !empty($selectedSubjectId) || !empty($search))
                <a href="{{ route('assignments.index') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-xs font-bold transition" title="Clear Filters">
                    ✕
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABLE FORMAT FOR SCHOOL ASSIGNMENTS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden transition-colors">
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">
                Active Assignments ({{ $assignments->total() }})
            </h3>
            <span class="text-[11px] text-slate-400">Sorted by due date</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        <th class="p-3">Class &amp; Section</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3">Assignment Details</th>
                        <th class="p-3">Due Date</th>
                        <th class="p-3 text-center">Max Marks</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($assignments as $asg)
                    @php
                        $mySub = $mySubmissions[$asg->id] ?? null;
                        $isOverdue = now()->isAfter($asg->due_date);
                    @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        <!-- Class & Section -->
                        <td class="p-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                                @if($asg->academicClass)
                                    {{ $asg->academicClass->name }}
                                    @if($asg->section || $asg->academicClass->section)
                                        (Sec {{ $asg->section?->name ?? $asg->academicClass->section }})
                                    @endif
                                @elseif($asg->target_type === 'all_students')
                                    School-Wide
                                @else
                                    {{ $asg->course?->code ?? 'General' }}
                                @endif
                            </span>
                            @if($asg->teacher)
                            <div class="text-[10px] text-slate-400 mt-1 flex items-center space-x-1">
                                <span>👨‍🏫</span>
                                <span class="truncate max-w-[130px]">{{ $asg->teacher->name }}</span>
                            </div>
                            @endif
                        </td>

                        <!-- Subject -->
                        <td class="p-3 whitespace-nowrap">
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                {{ $asg->subject?->name ?? ($asg->course?->title ?? 'General Academic') }}
                            </span>
                            @if($asg->subject?->code)
                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                {{ $asg->subject->code }}
                            </div>
                            @endif
                        </td>

                        <!-- Title & Scope -->
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('assignments.show', $asg->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition block">
                                    {{ $asg->title }}
                                </a>
                                @if($asg->hasAttachment())
                                <a href="{{ route('assignments.attachment', $asg->id) }}" target="_blank" title="Worksheet: {{ $asg->attachment_name }}" class="inline-flex items-center text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition">
                                    <span>📎</span>
                                </a>
                                @endif
                                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    {{ $asg->assigned_to_label }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 max-w-md mt-0.5">
                                {{ strip_tags($asg->description ?? 'No specific instructions provided.') }}
                            </p>
                        </td>

                        <!-- Due Date -->
                        <td class="p-3 whitespace-nowrap">
                            <div class="text-xs font-semibold {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $asg->due_date->format('M d, Y') }}
                            </div>
                            <div class="text-[10px] text-slate-400">
                                {{ $asg->due_date->format('h:i A') }} ({{ $asg->due_date->diffForHumans() }})
                            </div>
                        </td>

                        <!-- Max Points -->
                        <td class="p-3 text-center font-bold text-slate-800 dark:text-slate-200">
                            {{ $asg->max_points }}
                        </td>

                        <!-- Status Badge -->
                        <td class="p-3 text-center whitespace-nowrap">
                            @if(Auth::user()->isStudent() || Auth::user()->isParent())
                                @if($mySub)
                                    @if($mySub->status === 'graded')
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                            Score: {{ $mySub->grade }}/{{ $asg->max_points }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300">
                                            Submitted
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $isOverdue ? 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">
                                        {{ $isOverdue ? 'Overdue' : 'Pending' }}
                                    </span>
                                @endif
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $asg->submissions_count }} Submissions
                                </span>
                            @endif
                        </td>

                        <!-- Action Link -->
                        <td class="p-3 text-right whitespace-nowrap">
                            <a href="{{ route('assignments.show', $asg->id) }}" 
                               class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition">
                                View &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400 text-xs">
                            No assignments currently assigned matching the selected criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $assignments->links() }}
        </div>
    </div>

    <!-- Create Assignment Modal (Teachers / Admins) -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div x-show="openCreateModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openCreateModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 z-10 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                    <span>📝</span>
                    <span>Create School Assignment</span>
                </h3>
                <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data" class="space-y-3 mt-3">
                @csrf

                <!-- Target Audience -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target Audience *</label>
                    <select name="target_type" x-model="targetType" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs font-semibold">
                        <option value="class">🏫 Class &amp; Section</option>
                        @if(Auth::user()->isAdmin())
                            <option value="all_students">🌐 School-Wide (All Students)</option>
                        @endif
                        <option value="specific_student">👤 Specific Individual Student</option>
                    </select>
                </div>

                <!-- Academic Class Selection (when target is class) -->
                <div x-show="targetType === 'class'">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Academic Class *</label>
                    <select name="class_id" :required="targetType === 'class'" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        <option value="">-- Select Academic Class --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Section Selection (Optional override for class) -->
                <div x-show="targetType === 'class'">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Section (Optional)</label>
                    <select name="section_id" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        <option value="">Default Class Section / All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">Section {{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Subject Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Academic Subject (Optional)</label>
                    <select name="subject_id" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        <option value="">General Coursework / Multi-Disciplinary</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code ?? 'SUB' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Specific Student Dropdown -->
                <div x-show="targetType === 'specific_student'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Select Specific Student *</label>
                    <select name="student_id" :required="targetType === 'specific_student'" class="w-full px-3 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $std)
                            <option value="{{ $std->id }}">{{ $std->name }} ({{ $std->student_id_number ?? $std->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assignment Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assignment Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Chapter 4: Quadratic Equations Worksheet" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                </div>

                <!-- Instructions / Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Instructions / Description</label>
                    <textarea name="description" rows="3" placeholder="Submission criteria, questions to solve, textbook page reference..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs"></textarea>
                </div>

                <!-- File Attachment -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Attach Worksheet / Reference File (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900 border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Upload problem worksheet, assignment PDF, or rubric file (Max 25MB).</p>
                </div>

                <!-- Due Date & Max Marks Grid -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Due Date &amp; Time *</label>
                        <input type="datetime-local" name="due_date" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Max Marks / Points *</label>
                        <input type="number" name="max_points" value="100" min="1" max="1000" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs font-semibold">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openCreateModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-sm">Publish Assignment</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
