@extends('layouts.app')

@section('title', 'Bloom Taxonomy Engine' . ' — ' . ($school['name'] ?? config('app.name', 'School ERP')))

@section('content')
<div x-data="{ 
    openLessonModal: false, 
    openCsvModal: false,
    openTasksModal: false,
    selectedClassId: '{{ $classes->firstWhere('grade_level', 8)?->id ?? $classes->first()?->id }}',
    selectedSubjectId: '',
    selectedTopicId: '',
    distributionMode: 'balanced_random'
}" class="space-y-4">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-slate-100 dark:border-slate-800">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Bloom's Taxonomy Lesson Engine</h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
                Log lessons and auto-differentiate cognitive levels (Remember &rarr; Create)
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('blooms.sample-tasks-csv') }}" 
               class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1"
               title="Download CSV template for Bloom's tasks">
                <span>📥 Tasks CSV</span>
            </a>

            <button @click.stop="openTasksModal = true" 
                    type="button"
                    class="px-2.5 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1"
                    title="Upload Bloom's tasks via CSV and randomly assign">
                <span>📤 Import Tasks</span>
            </button>

            <a href="{{ route('blooms.sample-students-csv') }}" 
               class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center space-x-1"
               title="Download CSV student enrollment template">
                <span>📥 Roster CSV</span>
            </a>

            <button @click.stop="openCsvModal = true" 
                    type="button"
                    class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1"
                    title="Upload students to class via CSV">
                <span>📤 Import Roster</span>
            </button>

            <button @click.stop="openLessonModal = true" 
                    type="button"
                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1">
                <span>+ Log Lesson</span>
            </button>
        </div>
    </div>

    <!-- 6 Bloom's Taxonomy Cognitive Tiers Legend -->
    <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">6-Tier Bloom's Framework</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center">
                <span class="text-[9px] font-bold text-slate-400 block">LEVEL 1</span>
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 block">Remember</span>
                <span class="text-[9px] text-slate-500 block">Recall &amp; Define</span>
            </div>
            <div class="p-2 rounded-lg bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-center">
                <span class="text-[9px] font-bold text-blue-500 block">LEVEL 2</span>
                <span class="text-xs font-black text-blue-800 dark:text-blue-300 block">Understand</span>
                <span class="text-[9px] text-blue-600 dark:text-blue-400 block">Explain</span>
            </div>
            <div class="p-2 rounded-lg bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-center">
                <span class="text-[9px] font-bold text-emerald-500 block">LEVEL 3</span>
                <span class="text-xs font-black text-emerald-800 dark:text-emerald-300 block">Apply</span>
                <span class="text-[9px] text-emerald-600 dark:text-emerald-400 block">Execute</span>
            </div>
            <div class="p-2 rounded-lg bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-center">
                <span class="text-[9px] font-bold text-amber-500 block">LEVEL 4</span>
                <span class="text-xs font-black text-amber-800 dark:text-amber-300 block">Analyze</span>
                <span class="text-[9px] text-amber-600 dark:text-amber-400 block">Differentiate</span>
            </div>
            <div class="p-2 rounded-lg bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-center">
                <span class="text-[9px] font-bold text-purple-500 block">LEVEL 5</span>
                <span class="text-xs font-black text-purple-800 dark:text-purple-300 block">Evaluate</span>
                <span class="text-[9px] text-purple-600 dark:text-purple-400 block">Critique</span>
            </div>
            <div class="p-2 rounded-lg bg-rose-50/70 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-center">
                <span class="text-[9px] font-bold text-rose-500 block">LEVEL 6</span>
                <span class="text-xs font-black text-rose-800 dark:text-rose-300 block">Create</span>
                <span class="text-[9px] text-rose-600 dark:text-rose-400 block">Design</span>
            </div>
        </div>
    </div>

    <!-- Daily Lessons History -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="p-3 sm:p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Recorded Daily Lessons &amp; Distributions</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">View cognitive level allocations and evaluate student responses</p>
            </div>
            @if($pendingReviewsCount > 0)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                {{ $pendingReviewsCount }} Awaiting Review
            </span>
            @endif
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($lessons as $lesson)
            <div class="p-3 sm:p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition flex flex-col md:flex-row md:items-center justify-between gap-2.5 sm:gap-3">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                            {{ $lesson->academicClass->display_name }} &bull; {{ $lesson->subject->name }}
                        </span>
                        <span class="text-slate-400 text-[10px]">&bull;</span>
                        <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ $lesson->lesson_date->format('M d, Y') }}</span>
                    </div>

                    <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                        <a href="{{ route('blooms.lesson', $lesson->id) }}" class="hover:text-emerald-600 transition">
                            {{ $lesson->topic->title }}
                        </a>
                    </h4>

                    @if($lesson->subtopic)
                    <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium">
                        Subtopic: {{ $lesson->subtopic }}
                    </div>
                    @endif

                    <div class="text-[10px] text-slate-500 dark:text-slate-400 flex flex-wrap items-center gap-1.5 pt-0.5">
                        <span>Teacher: <strong class="text-slate-700 dark:text-slate-300">{{ $lesson->teacher->name }}</strong></span>
                        <span>&bull;</span>
                        <span>Mode: <strong class="text-slate-700 dark:text-slate-300 capitalize">{{ str_replace('_', ' ', $lesson->distribution_mode) }}</strong></span>
                        <span>&bull;</span>
                        <span>Students: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $lesson->assignments->count() }}</strong></span>
                    </div>

                    <!-- Mini Distribution Chips -->
                    <div class="flex items-center space-x-1 pt-1">
                        @for($lvl = 1; $lvl <= 6; $lvl++)
                        @php
                            $c = $lesson->assignments->where('bloom_level', $lvl)->count();
                            $meta = \App\Models\TopicBloomActivity::getLevelMetadata($lvl);
                        @endphp
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ $meta['badge_color'] }}" title="Level {{ $lvl }}: {{ $meta['name'] }}">
                            L{{ $lvl }}: {{ $c }}
                        </span>
                        @endfor
                    </div>
                </div>

                <div class="flex items-center space-x-3 self-end md:self-center">
                    <a href="{{ route('blooms.lesson', $lesson->id) }}" 
                       class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center space-x-1">
                        <span>View Roll Breakdown</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
            @empty
            <div class="p-12 text-center text-slate-400">
                <p>No daily lessons recorded yet. Click <strong>"+ Log Lesson &amp; Assign Levels"</strong> to distribute today's topic.</p>
            </div>
            @endforelse
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $lessons->links() }}
        </div>
    </div>

    <!-- Curriculum Sample: Pre-mapped Bloom's Topics (e.g. Class 8 Photosynthesis) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Mapped Topic Matrix: Photosynthesis &amp; Plant Nutrition (Class 8)</h3>
                <p class="text-xs text-slate-500">Every topic can be pre-configured across all 6 cognitive challenge tiers</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                6 Levels Configured
            </span>
        </div>

        @php
            $photoTopic = \App\Models\CurriculumTopic::where('title', 'like', '%Photosynthesis%')->with('bloomActivities')->first();
        @endphp

        @if($photoTopic)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($photoTopic->bloomActivities as $act)
            @php $meta = \App\Models\TopicBloomActivity::getLevelMetadata($act->bloom_level); @endphp
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-50 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $meta['badge_color'] }}">
                            Level {{ $act->bloom_level }}: {{ $act->level_name }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $act->verb_keywords }}</span>
                    </div>
                    <p class="text-xs text-slate-700 mt-2.5 whitespace-pre-line leading-relaxed font-medium">
                        {{ $act->activity_prompt }}
                    </p>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-200 text-[10px] text-slate-500 italic">
                    Rubric: {{ Str::limit($act->rubric_guide, 90) }}
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- LOG DAILY LESSON & ASSIGN MODAL -->
    <div x-show="openLessonModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openLessonModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-xl w-full p-6 sm:p-8 z-10">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                        🌱
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Record Today's Lesson</h3>
                        <p class="text-xs text-slate-500">Assign Bloom's levels &amp; randomize across roll numbers</p>
                    </div>
                </div>
                <button type="button" @click="openLessonModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('blooms.lesson.store') }}" class="space-y-4 mt-4">
                @csrf

                <!-- Class & Date -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Target Class *</label>
                        <select name="class_id" x-model="selectedClassId" @change="selectedSubjectId = ''; selectedTopicId = ''" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm bg-white font-medium">
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $c->grade_level === 8 ? 'selected' : '' }}>{{ $c->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lesson Date *</label>
                        <input type="date" name="lesson_date" required value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm font-medium">
                    </div>
                </div>

                <!-- Subject & Topic (Dynamic) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Subject *</label>
                        <select name="subject_id" x-model="selectedSubjectId" @change="selectedTopicId = ''" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm bg-white font-medium">
                            <option value="">-- Select Subject --</option>
                            @foreach($classes as $c)
                                @foreach($c->subjects as $subj)
                                    <option value="{{ $subj->id }}" x-show="selectedClassId == {{ $c->id }}" :hidden="selectedClassId != {{ $c->id }}" :disabled="selectedClassId != {{ $c->id }}">
                                        {{ $subj->name }} ({{ $subj->code }})
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Topic *</label>
                        <select name="topic_id" x-model="selectedTopicId" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm bg-white font-medium">
                            <option value="">-- Select Topic --</option>
                            @foreach($classes as $c)
                                @foreach($c->subjects as $subj)
                                    @foreach($subj->topics as $top)
                                        <option value="{{ $top->id }}" x-show="selectedSubjectId == {{ $subj->id }}" :hidden="selectedSubjectId != {{ $subj->id }}" :disabled="selectedSubjectId != {{ $subj->id }}">
                                            {{ $top->title }}
                                        </option>
                                    @endforeach
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Subtopic -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Subtopic / Focus Concept</label>
                    <input type="text" name="subtopic" placeholder="e.g. Iodine Starch Test, Light Reactions, etc." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm">
                </div>

                <!-- Distribution Logic Selection -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">Bloom's Randomization Mode *</label>

                    <div class="space-y-1.5 text-xs">
                        <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                            <input type="radio" name="distribution_mode" value="balanced_random" x-model="distributionMode" checked class="text-emerald-600 focus:ring-emerald-500">
                            <span><strong>Balanced Random (Recommended):</strong> Evenly shuffles roll numbers across all 6 levels (Remember &rarr; Create).</span>
                        </label>
                        <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                            <input type="radio" name="distribution_mode" value="uniform_level" x-model="distributionMode" class="text-emerald-600 focus:ring-emerald-500">
                            <span><strong>Uniform Level:</strong> All students receive the same designated Bloom's level.</span>
                        </label>
                        <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                            <input type="radio" name="distribution_mode" value="roll_range" x-model="distributionMode" class="text-emerald-600 focus:ring-emerald-500">
                            <span><strong>Roll Number Range:</strong> Rolls 1-2 Level 1, 3-4 Level 2, 5-6 Level 3, etc.</span>
                        </label>
                    </div>

                    <!-- Target Level Selector (Only when Uniform Level is selected) -->
                    <div x-show="distributionMode === 'uniform_level'" class="pt-2 border-t border-slate-200 mt-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Assign All Students To:</label>
                        <select name="target_level" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white font-bold">
                            <option value="1">Level 1 - Remember (Define / Recall)</option>
                            <option value="2">Level 2 - Understand (Explain / Contrast)</option>
                            <option value="3" selected>Level 3 - Apply (Demonstrate / Solve)</option>
                            <option value="4">Level 4 - Analyze (Differentiate / Examine)</option>
                            <option value="5">Level 5 - Evaluate (Critique / Defend)</option>
                            <option value="6">Level 6 - Create (Design / Formulate)</option>
                        </select>
                    </div>
                </div>

                <!-- Lesson Notes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Teacher Classroom Notes</label>
                    <textarea name="lesson_notes" rows="2" placeholder="Textbook pages covered, lab demonstrations performed, homework expectations..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openLessonModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                        Record Lesson &amp; Randomize
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- UPLOAD STUDENT ROSTER VIA CSV MODAL -->
    <div x-show="openCsvModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openCsvModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-8 animate-in fade-in duration-150 z-10">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold">
                        📋
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Upload Student Roster via CSV</h3>
                        <p class="text-xs text-slate-500">Bulk enroll students and map roll numbers to a class</p>
                    </div>
                </div>
                <button type="button" @click="openCsvModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('blooms.import-students-csv') }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Academic Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $c->grade_level === 8 ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select CSV File *</label>
                    <input type="file" 
                           name="csv_file" 
                           accept=".csv,text/csv" 
                           required 
                           class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-[11px] text-slate-400 mt-1">Expected columns: <code>roll_number, name, email</code>.</p>
                </div>

                <div class="bg-indigo-50/70 rounded-xl p-3 text-xs text-indigo-900 border border-indigo-100">
                    <span class="font-bold">Need a starting file?</span>
                    <a href="{{ route('blooms.sample-students-csv') }}" class="underline font-semibold ml-1 text-indigo-700 hover:text-indigo-900">
                        Download CSV roster format
                    </a>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openCsvModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                        Import &amp; Enroll Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- UPLOAD TASKS VIA CSV MODAL (Randomly Assigned) -->
    <div x-show="openTasksModal" x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openTasksModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full p-6 sm:p-8 animate-in fade-in duration-150 z-10">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Import Tasks CSV &amp; Assign</h3>
                        <p class="text-xs text-slate-500">Upload cognitive challenges to be randomly distributed to students</p>
                    </div>
                </div>
                <button type="button" @click="openTasksModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('blooms.import-tasks-csv') }}" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Daily Lesson *</label>
                    <select name="lesson_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        @forelse($lessons as $l)
                            <option value="{{ $l->id }}">{{ $l->academicClass->display_name }} &bull; {{ $l->topic->title }} ({{ $l->lesson_date->format('M d') }})</option>
                        @empty
                            <option disabled>No recorded lessons found. Record a lesson first.</option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Assignment Distribution Mode</label>
                    <select name="assignment_mode" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-medium">
                        <option value="random">🎲 Random Shuffled Assignment (Unpredictable challenges)</option>
                        <option value="sequential">🔢 Sequential Roll Mapping (Roll 1, 2, 3...)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Tasks CSV File *</label>
                    <input type="file" 
                           name="csv_file" 
                           accept=".csv,text/csv" 
                           required 
                           class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <p class="text-[11px] text-slate-400 mt-1">Expected columns: <code>bloom_level, task_prompt, rubric_guide, due_date</code>.</p>
                </div>

                <div class="bg-amber-50/70 rounded-xl p-3 text-xs text-amber-900 border border-amber-200">
                    <span class="font-bold">Need task template?</span>
                    <a href="{{ route('blooms.sample-tasks-csv') }}" class="underline font-semibold ml-1 text-amber-800 hover:text-amber-950">
                        Download Bloom's Tasks CSV template
                    </a>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="openTasksModal = false" class="px-4 py-2 text-slate-600 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-600/20 transition">
                        Import &amp; Randomly Assign Tasks
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
