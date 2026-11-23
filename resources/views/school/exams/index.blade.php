@extends('layouts.app')

@section('title', 'Examinations & Report Cards - School Management System')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4 pb-24 sm:pb-8 space-y-4">

    @if(Auth::user()->isParent())
        @include('school.parent._nav')
    @endif

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Examinations Hub</h1>

        <div class="flex items-center flex-wrap gap-2">
            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
            <a href="{{ route('school.exams.marks-entry') }}" 
               class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                <span>📝 Enter Marks</span>
            </a>
            @endif
            @if(Auth::user()->isStudent() && $currentTerm)
            <a href="{{ route('school.exams.admit-card', ['termId' => $currentTerm->id]) }}" 
               class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5" target="_blank">
                <span>🎫 My Admit Card</span>
            </a>
            @endif
            @if(Auth::user()->isParent() && $currentTerm && $activeChild)
            <a href="{{ route('school.exams.admit-card', ['termId' => $currentTerm->id, 'studentId' => $activeChild->id]) }}" 
               class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5" target="_blank">
                <span>🎫 {{ $activeChild->name }}'s Admit Card</span>
            </a>
            @endif
            @if(Auth::user()->isAdmin())
            @endif
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Exam Terms</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_terms'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Scheduled Papers</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_schedules'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Marks Entered</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_marks'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Report Cards</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total_report_cards'] }}</div>
        </div>
    </div>

    <!-- Exam Readiness Checklist (Rule 2) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xs space-y-2.5">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">📋 Exam Readiness Checklist</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ $readiness['term_name'] }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-black text-slate-900 dark:text-white font-mono">{{ $readiness['percentage'] }}% Ready</span>
                <div class="w-24 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" style="width: {{ $readiness['percentage'] }}%"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between {{ $readiness['term_defined'] ? 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-800/40 text-slate-500' }}">
                <div>
                    <span class="font-bold block">1. Term Defined</span>
                    <span class="text-[10px] opacity-75">{{ $readiness['term_defined'] ? 'Active' : 'Missing' }}</span>
                </div>
                <span>{{ $readiness['term_defined'] ? '✅' : '⚪' }}</span>
            </div>

            <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between {{ $readiness['schedules_ready'] ? 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-800/40 text-slate-500' }}">
                <div>
                    <span class="font-bold block">2. Schedules Assigned</span>
                    <span class="text-[10px] opacity-75">{{ $readiness['scheduled_count'] }} slot(s)</span>
                </div>
                <span>{{ $readiness['schedules_ready'] ? '✅' : '⚪' }}</span>
            </div>

            <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between {{ $readiness['marks_ready'] ? 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-800/40 text-slate-500' }}">
                <div>
                    <span class="font-bold block">3. Marks Recorded</span>
                    <span class="text-[10px] opacity-75">{{ $readiness['marks_count'] }} recorded</span>
                </div>
                <span>{{ $readiness['marks_ready'] ? '✅' : '⚪' }}</span>
            </div>

            <div class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between {{ $readiness['report_cards_ready'] ? 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300' : 'bg-slate-50 dark:bg-slate-800/40 text-slate-500' }}">
                <div>
                    <span class="font-bold block">4. Cards Published</span>
                    <span class="text-[10px] opacity-75">{{ $readiness['report_cards_count'] }} issued</span>
                </div>
                <span>{{ $readiness['report_cards_ready'] ? '✅' : '⚪' }}</span>
            </div>
        </div>
    </div>

    <!-- Term Selector Pills -->
    <div class="flex items-center space-x-2 overflow-x-auto pb-1">
        @foreach($terms as $term)
        <a href="{{ route('school.exams.index', ['term_id' => $term->id]) }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $currentTerm && $currentTerm->id === $term->id ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            {{ $term->name }} ({{ $term->academic_year }})
        </a>
        @endforeach
    </div>

    @if($currentTerm && Auth::user()->isAdmin())
    <details class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3">
        <summary class="cursor-pointer text-xs font-bold text-slate-700 dark:text-slate-200">Edit selected examination term</summary>
        <form method="POST" action="{{ route('school.exams.terms.update', $currentTerm->id) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            @csrf @method('PUT')
            <input name="name" value="{{ $currentTerm->name }}" required maxlength="150" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <input name="academic_year" value="{{ $currentTerm->academic_year }}" required maxlength="30" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <select name="status" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
                @foreach(['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'] as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected($currentTerm->status === $statusValue)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <input type="date" name="start_date" value="{{ $currentTerm->start_date?->format('Y-m-d') }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <input type="date" name="end_date" value="{{ $currentTerm->end_date?->format('Y-m-d') }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <input name="description" value="{{ $currentTerm->description }}" maxlength="500" placeholder="Description" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <button class="sm:col-span-3 rounded-lg bg-blue-600 text-white p-2 text-xs font-bold">Save examination term</button>
        </form>
    </details>
    @endif

    @if($currentTerm)
    <!-- Active Term Dashboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Datesheet & Exam Schedule (1 Col) -->
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Datesheet &amp; Schedule</h2>
                        <p class="text-xs text-slate-500">Exam papers scheduled for {{ $currentTerm->name }}.</p>
                    </div>
                    @if(Auth::user()->isAdmin())
                    <button type="button" 
                            onclick="document.getElementById('add-schedule-modal').classList.remove('hidden')"
                            class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-400 text-xs font-bold">
                        ➕ Add Slot
                    </button>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($schedules as $sched)
                    <div class="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                {{ $sched->academicClass ? $sched->academicClass->display_name : 'Class' }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 font-mono">
                                {{ \Carbon\Carbon::parse($sched->exam_date)->format('d M Y') }}
                            </span>
                        </div>
                        <h4 class="text-xs font-black text-slate-900 dark:text-white mt-1.5">{{ $sched->subject ? $sched->subject->name : 'Subject' }}</h4>
                        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-400">
                            <span>Time: {{ substr($sched->start_time, 0, 5) }} - {{ substr($sched->end_time, 0, 5) }}</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">Max Marks: {{ $sched->max_marks }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="p-6 text-center text-xs text-slate-400">
                        No examination dates scheduled for this term yet.
                    </div>
                    @endforelse
                </div>

                @if(isset($schedules) && $schedules instanceof \Illuminate\Pagination\LengthAwarePaginator && $schedules->hasPages())
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $schedules->withQueryString()->links() }}
                </div>
                @endif
            </div>

            <!-- Generate Report Cards Card (Utilitarian panel, structured micro-badges) -->
            @if(Auth::user()->isAdmin())
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 space-y-2.5 shadow-2xs">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-xs font-black text-slate-900 dark:text-white flex items-center space-x-1.5">
                        <span>⚡</span>
                        <span>Generate Draft Report Cards</span>
                    </h3>
                    <div class="flex items-center gap-1 text-[9px] font-bold">
                        <span class="px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Configured Grades</span>
                        <span class="px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">Rankings</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('school.exams.generate-report-cards') }}" class="space-y-2 text-xs">
                    @csrf
                    <input type="hidden" name="exam_term_id" value="{{ $currentTerm->id }}">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Target Class *</label>
                        <select name="class_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-2xs">
                        Generate Drafts
                    </button>
                </form>
                <form method="POST" action="{{ route('school.exams.report-cards.publish') }}" class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    @csrf
                    <input type="hidden" name="exam_term_id" value="{{ $currentTerm->id }}">
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400">Class to publish</label>
                    <select name="class_id" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                        @foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->display_name }}</option>@endforeach
                    </select>
                    <button type="submit" class="w-full py-2 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Publish reviewed drafts</button>
                </form>
            </div>
            @endif
        </div>

        <!-- Right: Generated Student Marksheets & Ranks (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white">Report Cards &amp; Class Ranks</h2>
                        <p class="text-xs text-slate-500">Generated student marksheets for {{ $currentTerm->name }}. Drafts remain staff-only until published.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                            <tr>
                                <th class="p-3">Rank</th>
                                <th class="p-3">Student Name</th>
                                <th class="p-3">Class</th>
                                <th class="p-3">Total Marks</th>
                                <th class="p-3">Percentage</th>
                                <th class="p-3">Grade</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($reportCards as $rc)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono font-black text-amber-500">
                                    #{{ $rc->class_rank ?? '-' }}
                                </td>
                                <td class="p-3 font-bold text-slate-900 dark:text-white">
                                    {{ $rc->student ? $rc->student->name : 'Student' }}
                                    <span class="text-[10px] text-slate-400 block font-mono">{{ $rc->student ? $rc->student->admission_number : '' }}</span>
                                </td>
                                <td class="p-3 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $rc->academicClass ? $rc->academicClass->display_name : '-' }}
                                </td>
                                <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $rc->total_marks_obtained }} / {{ $rc->total_max_marks }}
                                </td>
                                <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $rc->percentage }}%
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                        {{ $rc->overall_grade }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <a href="{{ route('school.report-cards.show', $rc->id) }}" 
                                           class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] inline-flex items-center space-x-1 shadow-xs">
                                            <span>🖨️</span>
                                            <span>Print Marksheet</span>
                                        </a>
                                        <a href="{{ route('school.exams.admit-card', ['termId' => $rc->exam_term_id, 'studentId' => $rc->student_id]) }}" 
                                           target="_blank"
                                           class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] inline-flex items-center space-x-1 shadow-xs">
                                            <span>🎫</span>
                                            <span>Admit Card</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400">
                                    No report cards generated yet. Click "Generate Drafts" to calculate.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($reportCards, 'links'))
                <div class="mt-4">
                    {{ $reportCards->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>

    </div>
    @else
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8 text-center shadow-xs space-y-3">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 text-2xl flex items-center justify-center mx-auto">
            📋
        </div>
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Examination Terms Created Yet</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
                Set up your first academic term (e.g. Mid-Term Assessment, Final Board Exam) to manage examination schedules, enter subject marks, and publish student report cards.
            </p>
        </div>
        @if(Auth::user()->isAdmin())
        <div class="pt-2">
            <button type="button" onclick="document.getElementById('create-term-modal').classList.remove('hidden')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center space-x-1.5">
                <span>➕ Create First Exam Term</span>
            </button>
        </div>
        @endif
    </div>
    @endif

</div>

<!-- Modal: Create Exam Term -->
@if(Auth::user()->isAdmin())
<div id="create-term-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New Exam Term</h3>
            <button onclick="document.getElementById('create-term-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.exams.terms.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Term Name *</label>
                <input type="text" name="name" required placeholder="e.g. Term 1 / Mid-Term Examination"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date *</label>
                    <input type="date" name="start_date" required value="{{ now()->toDateString() }}"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Date *</label>
                    <input type="date" name="end_date" required value="{{ now()->addWeeks(2)->toDateString() }}"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Year</label>
                    <select name="academic_year" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        @php
                            $currentSession = $school['academic_session'] ?? '2026-2027';
                            $sessionList = ['2024-2025', '2025-2026', '2026-2027', '2027-2028', '2028-2029'];
                            if (!in_array($currentSession, $sessionList)) {
                                array_unshift($sessionList, $currentSession);
                            }
                        @endphp
                        @foreach($sessionList as $sess)
                            <option value="{{ $sess }}" {{ $sess === $currentSession ? 'selected' : '' }}>{{ $sess }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed" selected>Completed</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('create-term-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-600/30">
                    Create Term
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Schedule Slot -->
@if($currentTerm)
<div id="add-schedule-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden"
     x-data="{
         selectedClassId: '{{ $classes->first()?->id ?? '' }}',
         classSubjects: {{ Js::from($classes->mapWithKeys(fn($c) => [$c->id => $c->subjects->map(fn($s) => [
             'id' => $s->id,
             'name' => $s->name,
             'code' => $s->code,
         ])])) }},
         get availableSubjects() {
             return this.classSubjects[this.selectedClassId] || [];
         }
     }">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Schedule Exam Paper</h3>
                <span class="text-[10px] text-slate-500 block">Class-dependent subject selector</span>
            </div>
            <button onclick="document.getElementById('add-schedule-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form method="POST" action="{{ route('school.exams.schedules.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="exam_term_id" value="{{ $currentTerm->id }}">

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Class *</label>
                <select name="class_id" x-model="selectedClassId" id="sched-class-select" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-bold">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->display_name }} ({{ $c->stream ?: 'General' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Subject *
                    <span class="text-[10px] text-slate-400 font-normal">(Filtered by selected class)</span>
                </label>
                <select name="subject_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-bold">
                    <option value="">-- Select Subject --</option>
                    <template x-for="subj in availableSubjects" :key="subj.id">
                        <option :value="subj.id" x-text="subj.name + ' (' + subj.code + ')'"></option>
                    </template>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Exam Date *</label>
                    <input type="date" name="exam_date" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Max Marks *</label>
                    <input type="number" name="max_marks" min="0.01" step="0.01" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time *</label>
                    <input type="time" name="start_time" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">End Time *</label>
                    <input type="time" name="end_time" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pass Marks *</label>
                <input type="number" name="pass_marks" min="0" step="0.01" required
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('add-schedule-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm shadow-emerald-600/30">
                    Save Schedule Slot
                </button>
            </div>
        </form>
    </div>
</div>


@endif
@endif


    @if(Auth::user()->isAdmin())
    <section class="mt-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Grading Scale Master</h2>
            <span class="text-[10px] text-slate-500">Database-configured grade thresholds</span>
        </div>
        <form method="POST" action="{{ route('school.exams.grading-scales.store') }}" class="grid grid-cols-2 md:grid-cols-6 gap-2">
            @csrf
            <input name="grade_code" required maxlength="10" placeholder="Grade code" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <input name="minimum_percentage" required type="number" min="0" max="100" step="0.01" placeholder="Minimum %" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <input name="label" required maxlength="80" placeholder="Description" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <select name="is_passing" required class="rounded-lg border p-2 text-xs dark:bg-slate-800"><option value="1">Passing</option><option value="0">Not passing</option></select>
            <input name="sort_order" required type="number" min="0" value="0" placeholder="Order" class="rounded-lg border p-2 text-xs dark:bg-slate-800">
            <button class="rounded-lg bg-emerald-600 text-white text-xs font-bold p-2">Add grade</button>
        </form>
        <div class="space-y-2">
            @foreach($gradeScales as $scale)
            <form method="POST" action="{{ route('school.exams.grading-scales.update', $scale->id) }}" class="grid grid-cols-2 md:grid-cols-7 gap-2 items-center">
                @csrf @method('PUT')
                <input name="grade_code" value="{{ $scale->grade_code }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
                <input name="minimum_percentage" type="number" min="0" max="100" step="0.01" value="{{ $scale->minimum_percentage }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
                <input name="label" value="{{ $scale->label }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
                <select name="is_passing" class="rounded-lg border p-2 text-xs dark:bg-slate-800"><option value="1" @selected($scale->is_passing)>Passing</option><option value="0" @selected(!$scale->is_passing)>Not passing</option></select>
                <input name="sort_order" type="number" min="0" value="{{ $scale->sort_order }}" required class="rounded-lg border p-2 text-xs dark:bg-slate-800">
                <select name="is_active" class="rounded-lg border p-2 text-xs dark:bg-slate-800"><option value="1" @selected($scale->is_active)>Active</option><option value="0" @selected(!$scale->is_active)>Inactive</option></select>
                <button class="rounded-lg bg-blue-600 text-white text-xs font-bold p-2">Save</button>
            </form>
            @endforeach
        </div>
    </section>
    @endif

@endsection
