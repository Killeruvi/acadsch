@extends('layouts.app')

@section('title', 'Examination Marks Entry - School Management System')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('school.exams.index') }}" class="hover:text-emerald-500">Examinations</a>
        <span>/</span>
        <span class="text-slate-900 dark:text-white font-bold">Subject Marks Entry</span>
    </div>

    <!-- Header & Filter Selector -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Student Examination Marks Entry</h1>
            <p class="text-xs text-slate-500 mt-1">Select the exam term, class, and subject to input marks and calculate student performance grades.</p>
        </div>

        <form method="GET" action="{{ route('school.exams.marks-entry') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-2">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">1. Exam Term</label>
                <select name="term_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @foreach($terms as $term)
                    <option value="{{ $term->id }}" {{ $termId == $term->id ? 'selected' : '' }}>
                        {{ $term->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">2. Class &amp; Section</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">3. Subject</label>
                <select name="subject_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-white">
                    @if($selectedClass)
                    @foreach($selectedClass->subjects as $s)
                    <option value="{{ $s->id }}" {{ $subjectId == $s->id ? 'selected' : '' }}>
                        {{ $s->name }} ({{ $s->code }})
                    </option>
                    @endforeach
                    @endif
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition">
                    Load Students
                </button>
            </div>
        </form>
    </div>

    @if($selectedClass && $selectedSubject && $schedule)
    <!-- Marks Entry Table Form -->
    <form method="POST" action="{{ route('school.exams.marks.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="exam_term_id" value="{{ $termId }}">
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="subject_id" value="{{ $subjectId }}">

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div>
                <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $selectedSubject->name }}</span>
                <span class="text-slate-400">for {{ $selectedClass->display_name }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="font-bold">Max: {{ $schedule->max_marks }} · Pass: {{ $schedule->pass_marks }}</span>
                <button type="button"
                        onclick="document.getElementById('bulk-marks-modal').classList.remove('hidden')"
                        class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-bold text-xs transition">
                    Bulk CSV Import
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 uppercase font-black text-[10px] text-slate-500 tracking-wider">
                        <tr>
                            <th class="p-3.5 w-16">Roll No</th>
                            <th class="p-3.5">Student Name</th>
                            <th class="p-3.5 w-32">Marks Obtained</th>
                            <th class="p-3.5 w-24">Letter Grade</th>
                            <th class="p-3.5">Teacher Feedback / Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($students as $row)
                        @php $st = $row['student']; @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                #{{ $st->pivot->roll_number ?? $st->roll_number ?? '-' }}
                            </td>
                            <td class="p-3.5">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $st->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $st->admission_number ?? '-' }}</span>
                            </td>
                            <td class="p-3.5">
                                <input type="number" step="0.01" min="0" max="{{ $schedule->max_marks }}" 
                                       name="marks[{{ $st->id }}][marks_obtained]" 
                                       value="{{ $row['marks_obtained'] }}" 
                                       placeholder="0.0"
                                       class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                            </td>
                            <td class="p-3.5">
                                @if($row['letter_grade'])
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                    {{ $row['letter_grade'] }}
                                </span>
                                @else
                                <span class="text-slate-400 text-[10px] italic">Auto</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <input type="text" 
                                       name="marks[{{ $st->id }}][remarks]" 
                                       value="{{ $row['remarks'] }}" 
                                       placeholder="e.g. Excellent problem solving, neat presentation"
                                       class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-200">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                No students enrolled in this class.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('school.exams.index', ['term_id' => $termId]) }}" 
               class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/25">
                Save Subject Marks &amp; Calculate Grades
            </button>
        </div>
    </form>

    <!-- Modal: Bulk Marks CSV Import -->
    <div id="bulk-marks-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden p-4">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full shadow-2xl">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Bulk Import Marks (CSV)</h3>
                    <p class="text-xs text-slate-500">{{ $selectedSubject->name }} &bull; {{ $selectedClass->display_name }}</p>
                </div>
                <button onclick="document.getElementById('bulk-marks-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('school.exams.marks.bulk-import') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="exam_term_id" value="{{ $termId }}">
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Upload CSV File *</label>
                    <input type="file" name="marks_file" accept=".csv,.txt" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                    <span class="text-[10px] text-slate-400 mt-1 block">Expected columns: <code>roll_number</code>, <code>marks_obtained</code>, <code>remarks</code> (optional).</span>
                </div>

                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] font-mono space-y-1">
                    <span class="font-bold text-slate-600 dark:text-slate-300 block">Sample CSV format:</span>
                    <p class="text-slate-500">roll_number,marks_obtained,remarks</p>
                    <p class="text-slate-500">1,85.5,Great performance</p>
                    <p class="text-slate-500">2,92,Outstanding</p>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('bulk-marks-modal').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs">
                        Import Marks
                    </button>
                </div>
            </form>
        </div>
    </div>
    @elseif($selectedClass && $selectedSubject)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            No examination schedule exists for this term, class and subject. An administrator must create and approve the schedule before marks can be entered.
        </div>
    @endif

</div>
@endsection
