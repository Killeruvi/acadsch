@extends('layouts.app')

@section('title', 'Quiz: ' . $quiz->title . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('quizzes.show', $quiz->id) }}" class="text-xs font-bold text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>&larr; Back to Quiz Arena</span>
        </a>
        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
            {{ $quiz->access_code }}
        </span>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
            <div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">Edit Live Arena Quiz</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Modify parameters, timing, reward XP, or status for this competitive sprint.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $quiz->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                {{ ucfirst($quiz->status) }}
            </span>
        </div>

        <form action="{{ route('quizzes.update', $quiz->id) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quiz Title</label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class</label>
                    <select name="class_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="">All Classes (Open Campus)</option>
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}" {{ $quiz->class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subject</label>
                    <select name="subject_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="">General / Interdisciplinary</option>
                        @foreach($allSubjects as $s)
                            <option value="{{ $s->id }}" {{ $quiz->subject_id == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Time Per Question (sec)</label>
                    <input type="number" name="time_per_question" value="{{ old('time_per_question', $quiz->time_per_question) }}" min="10" max="180" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">XP Reward</label>
                    <input type="number" name="xp_reward" value="{{ old('xp_reward', $quiz->xp_reward) }}" min="20" max="1000" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="active" {{ $quiz->status === 'active' ? 'selected' : '' }}>Active (Joinable)</option>
                        <option value="draft" {{ $quiz->status === 'draft' ? 'selected' : '' }}>Draft (Hidden)</option>
                        <option value="archived" {{ $quiz->status === 'archived' ? 'selected' : '' }}>Archived (Read-Only)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description &amp; Guidelines</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl shadow-xs transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
