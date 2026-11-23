@extends('layouts.app')

@section('title', 'Case Studies & Capstone Projects' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Academic Case Studies &amp; Projects
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Applied research, team capstones, and problem statements
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div x-data="{ openCreateModal: false }">
            <button @click="openCreateModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>+</span>
                    <span>New Project / Case Study</span>
                </button>

                <!-- Create Project Modal -->
                <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openCreateModal = false">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openCreateModal = false"
                         x-data="{
                             selectedClass: '{{ $allClasses->first()->id ?? '' }}',
                             allSubjects: {{ Js::from($allSubjects) }},
                             get filteredSubjects() {
                                 if (!this.selectedClass) return this.allSubjects;
                                 return this.allSubjects.filter(s => s.class_id == this.selectedClass);
                             }
                         }">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                <span>🚀</span> <span>Assign Project or Case Study</span>
                            </h3>
                            <button @click="openCreateModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                        </div>
                        <form action="{{ route('projects.store') }}" method="POST" class="space-y-3 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Title</label>
                                <input type="text" name="title" required placeholder="e.g. Smart Grid Power Optimization Algorithm" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Type</label>
                                    <select name="type" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="project">Capstone Project</option>
                                        <option value="case_study">Real-World Case Study</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Grouping Mode</label>
                                    <select name="group_mode" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="individual">👤 Individual (Ungrouped)</option>
                                        <option value="grouped">👥 Grouped (Teams)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Academic Class</label>
                                    <select name="class_id" x-model="selectedClass" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        @foreach($allClasses as $c)
                                            <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Subject</label>
                                    <select name="subject_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="">General / Interdisciplinary</option>
                                        <template x-for="subj in filteredSubjects" :key="subj.id">
                                            <option :value="subj.id" x-text="subj.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max Team Size</label>
                                    <input type="number" name="max_team_size" value="4" min="1" max="10" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Due Date</label>
                                    <input type="date" name="due_date" required value="{{ now()->addWeeks(2)->format('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Max Marks</label>
                                    <input type="number" name="max_marks" value="100" min="10" max="500" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Problem Statement &amp; Requirements</label>
                                <textarea name="description" rows="3" required placeholder="Describe the project goals and deliverables..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                                <button type="button" @click="openCreateModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-xs">Publish Brief</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Filter Pills Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
            <!-- Mode Filters (Individual vs Grouped) -->
            <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                <a href="{{ route('projects.index', array_merge(request()->except(['mode']), ['mode' => 'all'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition {{ ($modeFilter ?? 'all') === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500' }}">
                    All Formats
                </a>
                <a href="{{ route('projects.index', array_merge(request()->except(['mode']), ['mode' => 'grouped'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition flex items-center space-x-1 {{ ($modeFilter ?? '') === 'grouped' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500' }}">
                    <span>👥</span> <span>Grouped Teams</span>
                </a>
                <a href="{{ route('projects.index', array_merge(request()->except(['mode']), ['mode' => 'individual'])) }}" class="px-3 py-1.5 rounded-lg font-bold transition flex items-center space-x-1 {{ ($modeFilter ?? '') === 'individual' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500' }}">
                    <span>👤</span> <span>Individual (Ungrouped)</span>
                </a>
            </div>

            <!-- Type Filter (Project vs Case Study) -->
            <div class="flex items-center space-x-1.5">
                <a href="{{ route('projects.index', array_merge(request()->except(['type']), ['type' => 'all'])) }}" class="px-2.5 py-1 rounded-lg font-semibold {{ ($typeFilter ?? 'all') === 'all' ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400' }}">
                    All Types
                </a>
                <span>&bull;</span>
                <a href="{{ route('projects.index', array_merge(request()->except(['type']), ['type' => 'case_study'])) }}" class="px-2.5 py-1 rounded-lg font-semibold {{ ($typeFilter ?? '') === 'case_study' ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400' }}">
                    Case Studies
                </a>
                <span>&bull;</span>
                <a href="{{ route('projects.index', array_merge(request()->except(['type']), ['type' => 'project'])) }}" class="px-2.5 py-1 rounded-lg font-semibold {{ ($typeFilter ?? '') === 'project' ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400' }}">
                    Projects
                </a>
            </div>
        </div>

    <!-- Projects High-Density List (Clean & Mobile-Friendly) -->
    @if($projects->isEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center">
        <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-lg mx-auto mb-2">
            🚀
        </div>
        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No project briefs matching your criteria</h3>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Try resetting the filters or check back when new problem statements are published.</p>
    </div>
    @else
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 shadow-2xs overflow-hidden">
        @foreach($projects as $p)
        <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider {{ $p->isCaseStudy() ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' }}">
                        {{ $p->isCaseStudy() ? '📑 Case Study' : '🚀 Project' }}
                    </span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold {{ $p->isGrouped() ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $p->isGrouped() ? '👥 Group (Max ' . $p->max_team_size . ')' : '👤 Solo' }}
                    </span>
                    <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $p->academicClass->display_name }}
                    </span>
                    @if($p->subject)
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                        &bull; {{ $p->subject->name }}
                    </span>
                    @endif
                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 ml-1">
                        {{ $p->max_marks }} Marks
                    </span>
                </div>

                <div>
                    <a href="{{ route('projects.show', $p->id) }}" class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate block">
                        {{ $p->title }}
                    </a>
                </div>

                <div class="flex items-center space-x-3 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>📅 Due: {{ $p->due_date->format('M d, Y') }}</span>
                    <span>&bull;</span>
                    <span>📥 {{ $p->submissions_count }} Submissions</span>
                    <span>&bull;</span>
                    <span>Advisor: {{ $p->teacher->name }}</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:self-center shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
                <a href="{{ route('projects.edit', $p->id) }}" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition text-xs" title="Edit Project">
                    ✏️
                </a>
                <form action="{{ route('projects.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Delete this project and all its submissions?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 text-rose-400 hover:text-rose-600 transition text-xs" title="Delete Project">
                        🗑️
                    </button>
                </form>
                @endif
                <a href="{{ route('projects.show', $p->id) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-2xs whitespace-nowrap">
                    View Brief &rarr;
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="pt-2">
        {{ $projects->links() }}
    </div>
    @endif

</div>
@endsection
