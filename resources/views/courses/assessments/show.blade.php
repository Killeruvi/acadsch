@extends('layouts.app')

@section('title', $assessment->title . ' — ' . $course->title)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation / Breadcrumbs -->
    <div class="flex items-center justify-between">
        <a href="{{ route('courses.show', ['id' => $course->id, 'tab' => 'tests']) }}" class="inline-flex items-center space-x-2 text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
            <span>&larr;</span>
            <span>Back to {{ $course->title }}</span>
        </a>
        <div class="flex items-center space-x-2 text-xs">
            <span class="px-2.5 py-1 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold uppercase">
                Week {{ $assessment->week_number }} &bull; {{ ucfirst($assessment->type) }} Test
            </span>
        </div>
    </div>

    <!-- Assessment Header Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                    Mastery Examination
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2">
                    {{ $assessment->title }}
                </h1>
                <p class="text-xs text-slate-500 mt-1 max-w-xl">
                    {{ $assessment->description ?? 'Weekly evaluation assessing core theoretical principles, phonological accuracy, and spontaneous communication competence.' }}
                </p>
            </div>

            <!-- Stats Badge Grid -->
            <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center shrink-0">
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                    <div class="text-base sm:text-lg font-black text-indigo-600 dark:text-indigo-400">{{ $assessment->total_points }}</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Marks</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                    <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $assessment->passing_score }}%</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400">Pass Req</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700">
                    <div class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400">{{ $assessment->time_limit_minutes }}m</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400">Time Limit</div>
                </div>
            </div>
        </div>

        @if($submission)
        <!-- Submission Status Banner -->
        <div class="mt-4 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg">✓</span>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Attempt Evaluated</h4>
                    <p class="text-xs text-emerald-700 dark:text-emerald-400">
                        Score: <strong>{{ $submission->score }} / {{ $assessment->total_points }}</strong> ({{ $submission->percentage }}%) &bull; Grade: <strong>{{ $submission->grade_letter }}</strong>
                    </p>
                </div>
            </div>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-100 text-xs font-black">
                {{ $submission->grade_letter }} Passed
            </span>
        </div>
        @endif
    </div>

    <!-- Interactive Assessment Submission Box -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
        <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">Examination Response Submission</h3>
            <p class="text-xs text-slate-500 mt-0.5">Submit your oral presentation response, written analysis, or test answers.</p>
        </div>

        <form method="POST" action="{{ route('courses.assessments.submit', [$course->id, $assessment->id]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                    Student Notes / Response Script *
                </label>
                <textarea name="answers[response_text]" rows="5" required placeholder="Type your answers, self-evaluation script, or key conversational observations..." class="w-full p-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">{{ $submission?->answers['response_text'] ?? '' }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                    Oral Audio Recording / Presentation Link (Optional)
                </label>
                <input type="url" name="answers[audio_url]" value="{{ $submission?->answers['audio_url'] ?? '' }}" placeholder="https://drive.google.com/... or audio upload link" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white">
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('courses.show', ['id' => $course->id, 'tab' => 'tests']) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center space-x-1.5">
                    <span>🚀 Submit Assessment Attempt</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
