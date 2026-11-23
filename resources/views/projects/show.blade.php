@extends('layouts.app')

@section('title', $project->title . ' - Project Dossier')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation -->
    <a href="{{ route('projects.index') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-500 flex items-center space-x-1">
        <span>&larr; Back to Case Studies &amp; Projects</span>
    </a>

    <!-- Project Header Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 space-y-4 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $project->isCaseStudy() ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300' }}">
                    {{ $project->isCaseStudy() ? '📑 Case Study Brief' : '🚀 Capstone Project' }}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $project->isGrouped() ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                    {{ $project->isGrouped() ? '👥 Grouped Team Assignment (Max ' . $project->max_team_size . ')' : '👤 Individual Submission (Ungrouped)' }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-bold text-xs">
                    ⭐ Max {{ $project->max_marks }} Marks
                </span>
                <span class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">
                    📅 Due: {{ $project->due_date->format('M d, Y') }}
                </span>
            </div>
        </div>

        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ $project->title }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Academic Class: <strong>{{ $project->academicClass->name }}</strong> &bull; Supervised by Faculty Instructor <strong>{{ $project->teacher->name }}</strong>
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs text-slate-700 dark:text-slate-300 leading-relaxed space-y-2">
            <h4 class="font-extrabold text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Problem Statement &amp; Scope:</h4>
            <p>{{ $project->description }}</p>
        </div>
    </div>

    @if(Auth::user()->isStudent())
    <!-- STUDENT SUBMISSION PANEL -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 space-y-5 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                <span>📤</span> <span>Your Submission &amp; Deliverables</span>
            </h3>
            @if($mySubmission)
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $mySubmission->status === 'evaluated' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                {{ ucfirst($mySubmission->status) }}
            </span>
            @endif
        </div>

        @if($mySubmission && $mySubmission->status === 'evaluated')
        <!-- Evaluated Result -->
        <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200">Evaluation Completed</span>
                <span class="text-base font-black text-emerald-700 dark:text-emerald-300">
                    {{ $mySubmission->score }} / {{ $project->max_marks }} Marks
                </span>
            </div>
            @if($mySubmission->teacher_feedback)
            <p class="text-xs text-slate-600 dark:text-slate-300 pt-2 border-t border-emerald-200 dark:border-emerald-800">
                <strong>Faculty Feedback:</strong> {{ $mySubmission->teacher_feedback }}
            </p>
            @endif
        </div>
        @endif

        <form action="{{ route('projects.submit', $project->id) }}" method="POST" class="space-y-4 text-xs">
            @csrf

            @if($project->isGrouped())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Team Name</label>
                    <input type="text" name="team_name" required value="{{ $mySubmission->team_name ?? '' }}" placeholder="e.g. Quantum Pioneers" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Teammates (Comma separated names/roll #s)</label>
                    <input type="text" name="team_members" value="{{ !empty($mySubmission->team_members) ? implode(', ', $mySubmission->team_members) : '' }}" placeholder="e.g. Aarav Sharma, Bhavna Verma" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
            </div>
            @endif

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Solution Summary / Research Abstract</label>
                <textarea name="solution_summary" rows="4" required placeholder="Detail your methodologies, empirical findings, and case conclusions..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ $mySubmission->solution_summary ?? '' }}</textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Deliverable Link (GitHub repo / Drive PDF / Slides URL)</label>
                <input type="url" name="deliverable_url" value="{{ $mySubmission->deliverable_url ?? '' }}" placeholder="https://github.com/... or https://drive.google.com/..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    {{ $mySubmission ? 'Update Deliverables' : 'Submit Project Brief 🚀' }}
                </button>
            </div>
        </form>
    </div>
    @endif

    @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
    <!-- TEACHER / FACULTY EVALUATION TABLE -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
            <span>📋</span> <span>Student Submissions ({{ $project->submissions->count() }})</span>
        </h3>

        @if($project->submissions->isEmpty())
        <div class="p-6 text-center text-xs text-slate-400">
            No student submissions received yet for this brief.
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-300">
                        <th class="p-3">Student / Team</th>
                        <th class="p-3">Format</th>
                        <th class="p-3">Summary</th>
                        <th class="p-3">Deliverable</th>
                        <th class="p-3">Score</th>
                        <th class="p-3 text-right">Evaluate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($submissions ?? $project->submissions as $sub)
                    <tr>
                        <td class="p-3 font-bold text-slate-900 dark:text-white">
                            <div>{{ $sub->student->name }}</div>
                            @if($sub->team_name)
                            <span class="text-[10px] text-indigo-500 font-bold">Team: {{ $sub->team_name }}</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ $project->isGrouped() ? 'Grouped' : 'Individual' }}
                            </span>
                        </td>
                        <td class="p-3 max-w-xs truncate" title="{{ $sub->solution_summary }}">
                            {{ $sub->solution_summary }}
                        </td>
                        <td class="p-3">
                            @if($sub->deliverable_url)
                            <a href="{{ $sub->deliverable_url }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                Open Link &rarr;
                            </a>
                            @else
                            <span class="text-slate-400">No link</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono font-bold">
                            {{ $sub->score !== null ? $sub->score . '/' . $project->max_marks : 'Pending' }}
                        </td>
                        <td class="p-3 text-right">
                            <form action="{{ route('projects.submissions.evaluate', $sub->id) }}" method="POST" class="inline-flex items-center space-x-1.5">
                                @csrf
                                <input type="number" name="score" value="{{ $sub->score ?? '' }}" placeholder="Score" required min="0" max="{{ $project->max_marks }}" class="w-16 px-2 py-1 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                                <input type="hidden" name="status" value="evaluated">
                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition">
                                    Grade
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(isset($submissions) && $submissions instanceof \Illuminate\Pagination\LengthAwarePaginator && $submissions->hasPages())
        <div class="p-3 border-t border-slate-100 dark:border-slate-800">
            {{ $submissions->links() }}
        </div>
        @endif
        @endif
    </div>
    @endif

</div>
@endsection
