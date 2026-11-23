@extends('layouts.app')

@section('title', 'Assignments' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div x-data="{ openCreateModal: false }" class="space-y-3">

    <!-- COMPACT METADATA HEADER WITH INLINE PILL BADGES & EMBEDDED SLIM PROGRESS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 space-y-2">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-800 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60">
                    <span class="mr-1">📁</span>
                    <span>Deliverables</span>
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60">
                    <span class="mr-1">📊</span>
                    <span>Continuous Assessment</span>
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
                    Coursework &amp; Assignments
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Tabular overview of assignments, deadlines, points, and grading statuses.
                </p>
            </div>
        </div>

        <!-- Embedded Slim Progress Bar (1.5px) -->
        <div class="w-full bg-slate-100 dark:bg-slate-800 h-[1.5px] rounded-full overflow-hidden mt-1">
            <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full" style="width: 100%;"></div>
        </div>
    </div>

    <!-- SIMPLE TABLE FORMAT FOR ASSIGNMENTS (COMPACT & LESS SPACED) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden transition-colors">
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">
                All Active Assignments ({{ $assignments->total() }})
            </h3>
            <span class="text-[11px] text-slate-400">Sorted by due date</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        <th class="p-3">Course</th>
                        <th class="p-3">Assignment Title</th>
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
                        <!-- Course Code -->
                        <td class="p-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $asg->course?->code ?? 'Campus' }}
                            </span>
                            <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[120px]">
                                {{ $asg->course?->title ?? 'Academy-wide assignment' }}
                            </div>
                        </td>

                        <!-- Title & Description -->
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('assignments.show', $asg->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition block">
                                    {{ $asg->title }}
                                </a>
                                @if($asg->hasAttachment())
                                <a href="{{ route('assignments.attachment', $asg->id) }}" target="_blank" title="Attached worksheet: {{ $asg->attachment_name }}" class="inline-flex items-center text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition">
                                    <span>📎</span>
                                </a>
                                @endif
                                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    {{ $asg->assigned_to_label }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 max-w-md mt-0.5">
                                {{ strip_tags($asg->description ?? 'No extra instructions provided.') }}
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
                                    {{ $asg->submissions_count }} Turn-ins
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
                        <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                            No assignments currently assigned.
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
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-5 z-10">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Create New Assignment</h3>
                <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data" x-data="{ targetType: 'all_enrolled' }" class="space-y-3 mt-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Course <span x-show="targetType === 'all_enrolled'">*</span></label>
                    <select name="course_id" :required="targetType === 'all_enrolled'" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        <option value="">-- General / Academy-Wide (No Specific Course) --</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned To (Target Scholars) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Assigned To (Target Scholars) *</label>
                    <select name="target_type" x-model="targetType" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs font-semibold">
                        <option value="all_enrolled">👥 All Enrolled Scholars in Course</option>
                        @if(Auth::user()->isAdmin())
                            <option value="all_students">🏫 All Active Scholars in Academy</option>
                        @endif
                        <option value="specific_student">👤 Specific Individual Scholar</option>
                    </select>
                </div>

                <!-- Specific Student Dropdown -->
                <div x-show="targetType === 'specific_student'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Select Specific Scholar *</label>
                    <select name="student_id" class="w-full px-3 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                        @foreach($students ?? \App\Models\User::where('role', 'student')->get() as $std)
                            <option value="{{ $std->id }}">{{ $std->name }} ({{ $std->student_id_number ?? $std->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Problem Set 4: Vector Calculus" class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Instructions / Description</label>
                    <textarea name="description" rows="2" placeholder="Submission criteria, problems..." class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Attach Worksheet / File (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.png,.jpg,.jpeg" class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900 border border-slate-300 dark:border-slate-700 rounded-xl p-1 bg-white dark:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-1">Upload DPP PDF, problem worksheet, or rubric file (Max 25MB).</p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Due Date &amp; Time *</label>
                        <input type="datetime-local" name="due_date" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Max Points *</label>
                        <input type="number" name="max_points" value="100" min="1" required class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="openCreateModal = false" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold">Create Deliverable</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
