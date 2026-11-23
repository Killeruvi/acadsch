@extends('layouts.app')

@section('title', 'Live Quizzes & Competition Arena' . ' — ' . ($school['name'] ?? 'PrimeOS Academy'))

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Live Quizzes &amp; Arena
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Speed, accuracy, and concept mastery sprints
            </p>
        </div>

        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
        <div class="flex flex-wrap items-center gap-2" x-data="{ openCreateModal: false }">
            <a href="{{ route('quizzes.sample-csv') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 border border-slate-300 dark:border-slate-700">
                <span>📄 Sample CSV</span>
            </a>

            <button @click="openCreateModal = true" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                    <span>+</span>
                    <span>Create Live Quiz</span>
                </button>

                <!-- Create Quiz Modal -->
                <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openCreateModal = false">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openCreateModal = false"
                         x-data="{
                             selectedClass: '',
                             allSubjects: {{ Js::from($allSubjects) }},
                             get filteredSubjects() {
                                 if (!this.selectedClass) return this.allSubjects;
                                 return this.allSubjects.filter(s => s.class_id == this.selectedClass);
                             }
                         }">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                                <span>⚡</span> <span>Create Live Arena Quiz</span>
                            </h3>
                            <button @click="openCreateModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                        </div>
                        <form action="{{ route('quizzes.store') }}" method="POST" class="space-y-3 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quiz Title</label>
                                <input type="text" name="title" required placeholder="e.g. Molecular Genetics &amp; DNA Replication Sprint" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Class</label>
                                    <select name="class_id" x-model="selectedClass" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                        <option value="">All Classes (Open Arena)</option>
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
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Time Per Question (sec)</label>
                                    <input type="number" name="time_per_question" value="30" min="10" max="180" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">XP Reward</label>
                                    <input type="number" name="xp_reward" value="100" min="20" max="1000" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Rules</label>
                                <textarea name="description" rows="2" placeholder="Brief rules, guidelines, or topics covered..." class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                            </div>

                            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl text-amber-800 dark:text-amber-300 text-[11px]">
                                💡 <strong>Tip:</strong> After publishing, you can bulk-import questions via <strong>CSV file</strong> or <strong>copy-paste text</strong> in one click inside the quiz management console!
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                                <button type="button" @click="openCreateModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-xs">Publish &amp; Add Questions &rarr;</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Search & Filter Controls -->
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Search input -->
            <form action="{{ route('quizzes.index') }}" method="GET" class="flex items-center space-x-2 flex-1 max-w-md">
                @if($selectedClassId)
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                @endif
                <div class="relative w-full">
                    <input type="text" name="search" value="{{ $searchTerm }}" placeholder="Search quizzes by title or code..." class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white">
                    <span class="absolute left-2.5 top-2 text-slate-400 text-xs">🔍</span>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 dark:bg-slate-700 text-white text-xs font-bold rounded-xl hover:bg-slate-700 transition">Search</button>
            </form>

            <!-- Status Filter -->
            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 font-bold">Status:</span>
                <a href="{{ route('quizzes.index', array_merge(request()->query(), ['status' => 'active'])) }}" class="px-2.5 py-1 rounded-lg font-bold transition {{ ($statusFilter ?? 'active') === 'active' ? 'bg-emerald-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}">Active</a>
                <a href="{{ route('quizzes.index', array_merge(request()->query(), ['status' => 'all'])) }}" class="px-2.5 py-1 rounded-lg font-bold transition {{ ($statusFilter ?? '') === 'all' ? 'bg-emerald-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' }}">All</a>
            </div>
        </div>

        <!-- Class Filter Horizontal Pills -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pt-3 mt-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('quizzes.index', array_merge(request()->query(), ['class_id' => 'all'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ empty($selectedClassId) || $selectedClassId === 'all' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                All Classes
            </a>
            @foreach($allClasses as $c)
            <a href="{{ route('quizzes.index', array_merge(request()->query(), ['class_id' => $c->id])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition {{ $selectedClassId == $c->id ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                {{ $c->display_name }}
            </a>
            @endforeach
        </div>

    <!-- Quizzes High-Density List (Clean & Mobile-Friendly, No Bulky Cardviews) -->
    @if($quizzes->isEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-8 text-center">
        <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-500 flex items-center justify-center text-lg mx-auto mb-2">
            ⚡
        </div>
        <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200">No live quizzes match your filters</h3>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Try clearing your filters or create a new quiz challenge above!</p>
    </div>
    @else
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 shadow-2xs overflow-hidden">
        @foreach($quizzes as $q)
        <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="font-mono text-[9px] font-black px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                        {{ $q->access_code }}
                    </span>
                    @if($q->academicClass)
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                        {{ $q->academicClass->display_name }}
                    </span>
                    @endif
                    @if($q->subject)
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                        &bull; {{ $q->subject->name }}
                    </span>
                    @endif
                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 ml-1">
                        ⚡ +{{ $q->xp_reward }} XP
                    </span>
                    @if($q->status !== 'active')
                    <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                        {{ $q->status }}
                    </span>
                    @endif
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('quizzes.show', $q->id) }}" class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition truncate">
                        {{ $q->title }}
                    </a>
                </div>

                <div class="flex items-center space-x-3 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>⏱️ {{ $q->time_per_question }}s / Q</span>
                    <span>&bull;</span>
                    <span>❓ {{ $q->questions_count }} Questions</span>
                    <span>&bull;</span>
                    <span>By {{ $q->creator->name }}</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:self-center shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                <a href="{{ route('quizzes.edit', $q->id) }}" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition text-xs" title="Edit Quiz Settings">
                    ✏️
                </a>
                <form action="{{ route('quizzes.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this live quiz? All questions and attempts will be removed.');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1 text-rose-400 hover:text-rose-600 transition text-xs" title="Delete Quiz">
                        🗑️
                    </button>
                </form>
                @endif

                <a href="{{ route('quizzes.show', $q->id) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-2xs whitespace-nowrap">
                    Enter Arena &rarr;
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="pt-2">
        {{ $quizzes->links() }}
    </div>
    @endif

</div>
@endsection
