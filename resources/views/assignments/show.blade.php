@extends('layouts.app')

@section('title', $assignment->title . ' - Assignment Details')

@section('content')
<div class="space-y-6">

    <!-- Top Action & Navigation Bar -->
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('assignments.index') }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-500 hover:text-emerald-600 transition">
            <span>&larr;</span>
            <span>Back to Assignments</span>
        </a>
        @if(Auth::user()->isAdmin() || (Auth::user()->isTeacher() && $assignment->teacher_id === Auth::id()))
        <div class="flex items-center space-x-2">
            <a href="{{ route('assignments.edit', $assignment->id) }}" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-500 text-white transition flex items-center space-x-1 shadow-sm">
                <span>✏️ Edit Assignment</span>
            </a>
            <form method="POST" action="{{ route('assignments.destroy', $assignment->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to permanently delete this assignment? All student submissions and grading will be removed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-rose-600 hover:bg-rose-500 text-white transition flex items-center space-x-1 shadow-sm">
                    <span>🗑️ Delete Assignment</span>
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Assignment Overview Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($assignment->academicClass)
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                        {{ $assignment->academicClass->display_name }}
                    </span>
                    @endif
                    @if($assignment->subject)
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        {{ $assignment->subject->name }}
                    </span>
                    @elseif($assignment->course)
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-800">{{ $assignment->course->code }}</span>
                    <span class="text-xs font-semibold text-slate-600">{{ $assignment->course->title }}</span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $assignment->title }}</h1>
                <div class="flex items-center space-x-2 text-xs text-slate-500 mt-1">
                    <span>Teacher: <strong class="text-slate-800 font-semibold">{{ $assignment->teacher?->name ?? 'TBA' }}</strong></span>
                    <span>&bull;</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Assigned To: {{ $assignment->assigned_to_label }}
                    </span>
                </div>
            </div>

            <div class="flex items-center space-x-4 bg-slate-50 border border-slate-200 p-4 rounded-xl">
                <div>
                    <span class="text-xs text-slate-400 block font-medium uppercase">Due Date</span>
                    <span class="text-sm font-bold text-slate-900">{{ $assignment->due_date->format('M d, Y - h:i A') }}</span>
                    <span class="text-[11px] text-slate-500 block">{{ $assignment->due_date->diffForHumans() }}</span>
                </div>
                <div class="border-l border-slate-200 pl-4">
                    <span class="text-xs text-slate-400 block font-medium uppercase">Max Score</span>
                    <span class="text-lg font-black text-emerald-600">{{ $assignment->max_points }} pts</span>
                </div>
            </div>
        </div>

        <!-- Description & Instructions -->
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-bold uppercase text-slate-400 tracking-wider mb-2">Instructions &amp; Problem Statement</h3>
            <div class="bg-slate-50 dark:bg-slate-800/60 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-200 leading-relaxed space-y-3">
                {!! $assignment->description ?? '<p class="text-slate-400 italic">No extra instructions provided by instructor.</p>' !!}
            </div>
        </div>

        @if($assignment->hasAttachment())
        <!-- Attached Reference Material / Worksheet -->
        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-bold uppercase text-slate-400 tracking-wider mb-3">Attached Study Material &amp; Worksheet</h3>
            <div class="p-4 sm:p-5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/80 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-bold text-2xl shrink-0 shadow-xs">
                        @if($assignment->is_pdf_attachment)
                            📄
                        @else
                            📎
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                            <span>{{ $assignment->attachment_name ?: basename($assignment->attachment_path) }}</span>
                            @if($assignment->formatted_attachment_size)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $assignment->formatted_attachment_size }}
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Official printable worksheet / reference sheet attached for this homework.
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 shrink-0">
                    <a href="{{ route('assignments.attachment', $assignment->id) }}" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                        <span>📥</span>
                        <span>Download Worksheet</span>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- STUDENT VIEW: Submission Box -->
    @if(Auth::user()->isStudent() || Auth::user()->isParent())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Your Assignment Submission</h3>

        @if($mySubmission)
            <div class="space-y-4">
                <!-- Submission Info Pill -->
                <div class="p-4 rounded-xl border {{ $mySubmission->status === 'graded' ? 'bg-emerald-50 border-emerald-200' : 'bg-blue-50 border-blue-200' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider {{ $mySubmission->status === 'graded' ? 'text-emerald-800' : 'text-blue-800' }}">
                            Status: {{ strtoupper($mySubmission->status) }}
                        </span>
                        <span class="text-xs text-slate-500">Submitted on {{ $mySubmission->submitted_at->format('M d, Y - h:i A') }}</span>
                    </div>

                    @if($mySubmission->status === 'graded')
                    <div class="mt-3 pt-3 border-t border-emerald-200/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-500">Score Received:</span>
                            <span class="text-xl font-black text-emerald-700 ml-1">{{ $mySubmission->grade }} / {{ $assignment->max_points }}</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            Evaluated by: <strong class="text-slate-700">{{ $mySubmission->grader?->name ?? 'Instructor' }}</strong>
                        </div>
                    </div>
                    @if($mySubmission->feedback)
                    <div class="mt-2 text-xs text-slate-700 bg-white p-3 rounded-lg border border-emerald-100">
                        <span class="font-bold text-emerald-800 block mb-0.5">Instructor Feedback:</span>
                        {{ $mySubmission->feedback }}
                    </div>
                    @endif
                    @endif
                </div>

                <!-- Submitted Work Content -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Your Submitted Response:</label>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-800 whitespace-pre-wrap">
                        {{ $mySubmission->content }}
                    </div>
                    @if($mySubmission->attachment_url)
                    <div class="mt-2 text-xs">
                        <span class="text-slate-500">Attachment / Repository:</span>
                        <a href="{{ $mySubmission->attachment_url }}" target="_blank" class="text-emerald-600 hover:underline font-semibold ml-1">
                            {{ $mySubmission->attachment_url }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        @elseif(Auth::user()->isStudent())
            <!-- Submission Form -->
            <form method="POST" action="{{ route('assignments.submit', $assignment->id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Submission Response / Solution Text *</label>
                    <textarea name="content" rows="6" required placeholder="Type or paste your solution, report, or answers here..." 
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Attachment / Reference URL (Optional)</label>
                    <input type="url" name="attachment_url" placeholder="https://drive.google.com/file/... or Project URL" 
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-600/20 transition">
                        Submit Solution
                    </button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">No submission has been recorded for the linked student yet.</p>
        @endif
    </div>
    @endif

    <!-- TEACHER & ADMIN VIEW: Submissions & Grading Management -->
    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Student Submissions &amp; Grading</h3>
                <p class="text-xs text-slate-500">Review student work, assign marks out of {{ $assignment->max_points }}, and provide feedback</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                {{ $assignment->submissions_count ?? $submissions->total() }} Submissions
            </span>
        </div>

        @if($submissions->isEmpty())
            <div class="text-center py-12 text-slate-400">
                <p>No student submissions received for this assignment yet.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($submissions as $sub)
                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-slate-50 transition space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200/80">
                        <div>
                            <span class="font-bold text-slate-900 text-base">{{ $sub->student->name }}</span>
                            <span class="text-xs text-slate-400 ml-2">({{ $sub->student->email }})</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-slate-500">Submitted: {{ $sub->submitted_at->format('M d, Y - h:i A') }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $sub->status === 'graded' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $sub->status }}
                            </span>
                        </div>
                    </div>

                    <!-- Submission Body -->
                    <div class="text-xs text-slate-700 bg-white p-4 rounded-xl border border-slate-200 whitespace-pre-wrap leading-relaxed">
                        {{ $sub->content }}
                    </div>

                    @if($sub->attachment_url)
                    <div class="text-xs">
                        <span class="text-slate-400">Resource:</span>
                        <a href="{{ $sub->attachment_url }}" target="_blank" class="text-emerald-600 hover:underline font-semibold ml-1">
                            {{ $sub->attachment_url }}
                        </a>
                    </div>
                    @endif

                    <!-- Teacher Grading Form -->
                    <form method="POST" action="{{ route('submissions.grade', $sub->id) }}" class="bg-white p-4 rounded-xl border border-slate-200 space-y-3">
                        @csrf
                        <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                            <span>Teacher Evaluation</span>
                            @if($sub->grade !== null)
                                <span class="text-emerald-700">Current Grade: {{ $sub->grade }}/{{ $assignment->max_points }}</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Score (Max {{ $assignment->max_points }})</label>
                                <input type="number" step="0.5" name="grade" required value="{{ old('grade', $sub->grade) }}" min="0" max="{{ $assignment->max_points }}"
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Feedback / Notes to Student</label>
                                <input type="text" name="feedback" value="{{ old('feedback', $sub->feedback) }}" placeholder="Constructive comments, critique, corrections..."
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                {{ $sub->grade !== null ? 'Update Grade & Feedback' : 'Save Grade & Feedback' }}
                            </button>
                        </div>
                    </form>
                </div>
                @endforeach
            </div>

            @if(isset($submissions) && $submissions instanceof \Illuminate\Pagination\LengthAwarePaginator && $submissions->hasPages())
            <div class="mt-4 p-3 bg-white rounded-xl border border-slate-200">
                {{ $submissions->links() }}
            </div>
            @endif
        @endif
    </div>
    @endif

</div>
@endsection
