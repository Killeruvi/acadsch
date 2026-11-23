@extends('layouts.app')

@section('title', 'Project: ' . $project->title . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('projects.show', $project->id) }}" class="text-xs font-bold text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>&larr; Back to Project Details</span>
        </a>
        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $project->isGrouped() ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
            {{ $project->isGrouped() ? '👥 Group Collaborative' : '👤 Individual Task' }}
        </span>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs">
        <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white mb-1">Edit Project / Case Study</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Update requirements, rubrics, due date, or max score.</p>

        <form action="{{ route('projects.update', $project->id) }}" method="POST" class="space-y-4 text-xs"
              x-data="{
                  selectedClass: '{{ $project->class_id }}',
                  allSubjects: {{ Js::from($allSubjects) }},
                  get filteredSubjects() {
                      if (!this.selectedClass) return this.allSubjects;
                      return this.allSubjects.filter(s => s.class_id == this.selectedClass);
                  }
              }">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $project->title) }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Class</label>
                    <select name="class_id" x-model="selectedClass" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        @foreach($allClasses as $c)
                            <option value="{{ $c->id }}" {{ $project->class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subject</label>
                    <select name="subject_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="">General / Interdisciplinary</option>
                        <template x-for="subj in filteredSubjects" :key="subj.id">
                            <option :value="subj.id" x-text="subj.name" :selected="subj.id == {{ $project->subject_id ?? 'null' }}"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="project" {{ $project->type === 'project' ? 'selected' : '' }}>Academic Project</option>
                        <option value="case_study" {{ $project->type === 'case_study' ? 'selected' : '' }}>Real-World Case Study</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Collaboration Mode</label>
                    <select name="group_mode" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                        <option value="individual" {{ $project->group_mode === 'individual' ? 'selected' : '' }}>Individual (Single Student)</option>
                        <option value="grouped" {{ $project->group_mode === 'grouped' ? 'selected' : '' }}>Grouped (Team Collaboration)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max Team Size</label>
                    <input type="number" name="max_team_size" value="{{ old('max_team_size', $project->max_team_size) }}" min="1" max="10" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $project->due_date ? $project->due_date->format('Y-m-d') : '') }}" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max Marks</label>
                    <input type="number" name="max_marks" value="{{ old('max_marks', $project->max_marks) }}" min="10" max="500" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Overview Description</label>
                <textarea name="description" rows="3" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('description', $project->description) }}</textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Problem Statement / Objectives</label>
                <textarea name="problem_statement" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('problem_statement', $project->problem_statement) }}</textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Evaluation Rubric &amp; Criteria</label>
                <textarea name="evaluation_rubric" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">{{ old('evaluation_rubric', $project->evaluation_rubric) }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a href="{{ route('projects.show', $project->id) }}" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-bold">
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
