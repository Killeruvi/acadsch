@extends('layouts.app')

@section('title', $dpp->dpp_number . ' — ' . $dpp->title . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@section('content')
<div x-data="dppEditorComponent()" class="space-y-4">

    <!-- Top Navigation / Breadcrumb -->
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center space-x-3 text-xs">
            @if($dpp->course_id)
            <a href="{{ route('courses.show', ['id' => $dpp->course_id, 'tab' => 'dpp']) }}" class="inline-flex items-center space-x-1 font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                <span>🎓 Back to Course DPPs</span>
            </a>
            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
            @endif
            <a href="{{ route('dpp.show', $dpp->id) }}" class="inline-flex items-center space-x-1.5 font-bold text-slate-500 hover:text-indigo-600 transition">
                <span>&larr;</span>
                <span>Back to {{ $dpp->dpp_number }} Overview</span>
            </a>
        </div>
        <div class="flex items-center space-x-2 text-xs">
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                {{ $dpp->academicClass->name ?? 'Class' }}
            </span>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $dpp->subject->name ?? 'Subject' }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $dpp->difficulty === 'olympiad' ? 'bg-purple-100 text-purple-800' : ($dpp->difficulty === 'advanced' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                {{ $dpp->difficulty }}
            </span>
        </div>
    </div>

    <!-- Header Banner (Compact & Inspiring) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 sm:p-4 text-slate-900 dark:text-white shadow-2xs border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    ✏️ Published DPP Editor
                </span>
                <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $dpp->dpp_number }}</span>
            </div>
            <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                Edit Published DPP &amp; Question Master Key
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Update metadata, adjust timer and pass criteria, or edit individual/bulk questions. Existing student submissions (<strong class="text-amber-600 dark:text-amber-400">{{ $dpp->submissions->count() }} attempts</strong>) are safely preserved.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" @click="openBankPickerModal = true" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                + From Question Bank
            </button>
            <label class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold cursor-pointer transition shadow-2xs flex items-center space-x-1">
                <span>📥 Import CSV</span>
                <input type="file" accept=".csv,.txt,.tsv" @change="handleCsvUpload($event)" class="sr-only">
            </label>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form action="{{ route('dpp.update', $dpp->id) }}" method="POST" data-preserve-scroll="true" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Metadata Section (Compact Responsive Grid) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                1. General Information &amp; Grading Policies
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $dpp->title) }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Practice Type *</label>
                    <select name="type" x-model="itemType" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        <option value="dpp" {{ $dpp->type === 'dpp' ? 'selected' : '' }}>Daily Practice Problem (DPP)</option>
                        <option value="test" {{ $dpp->type === 'test' ? 'selected' : '' }}>Timed Examination / Test (CBT)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Assigned Date *</label>
                    <input type="date" name="assigned_date" value="{{ old('assigned_date', $dpp->assigned_date->toDateString()) }}" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Difficulty *</label>
                    <select name="difficulty" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        <option value="easy" {{ in_array($dpp->difficulty, ['easy', 'standard']) ? 'selected' : '' }}>Easy / Standard</option>
                        <option value="medium" {{ in_array($dpp->difficulty, ['medium', 'advanced']) ? 'selected' : '' }}>Medium / Advanced</option>
                        <option value="hard" {{ in_array($dpp->difficulty, ['hard', 'olympiad']) ? 'selected' : '' }}>Hard / Olympiad</option>
                        <option value="standard" {{ $dpp->difficulty === 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="advanced" {{ $dpp->difficulty === 'advanced' ? 'selected' : '' }}>Advanced</option>
                        <option value="olympiad" {{ $dpp->difficulty === 'olympiad' ? 'selected' : '' }}>Olympiad Level</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Duration (Minutes)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $dpp->duration_minutes) }}" min="0" max="300" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Pass % Criteria</label>
                    <input type="number" name="pass_percentage" value="{{ old('pass_percentage', $dpp->pass_percentage ?? 40) }}" min="1" max="100" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Negative Marks per Wrong Answer</label>
                    <input type="number" step="0.25" min="0" max="10" name="negative_marks" value="{{ old('negative_marks', $dpp->negative_marks ?? 0) }}" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Curriculum Topic (Optional)</label>
                    <select name="topic_id" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        <option value="">General (No Topic Attached)</option>
                        @foreach($subjects as $s)
                            @foreach($s->topics as $t)
                            <option value="{{ $t->id }}" {{ (string)$dpp->topic_id === (string)$t->id ? 'selected' : '' }}>
                                {{ $s->name }} &bull; Ch {{ $t->chapter_number }}: {{ $t->title }}
                            </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Question Review & Editing Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-4">
            
            <!-- Controls Bar: Stepper Mode vs List Mode Toggle & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">View Mode:</span>
                    <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                        <button type="button" 
                                @click="viewMode = 'single'" 
                                :class="viewMode === 'single' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                            <span>🎯 Single Question Stepper</span>
                        </button>
                        <button type="button" 
                                @click="viewMode = 'list'" 
                                :class="viewMode === 'list' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                            <span>📜 All Questions List</span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-slate-500">
                        Total Questions: <strong class="text-indigo-600 dark:text-indigo-400" x-text="questions.length"></strong> &bull;
                        Total Marks: <strong class="text-emerald-600 dark:text-emerald-400" x-text="totalMarks"></strong>
                    </span>
                    <button type="button" @click="addQuestion()" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-bold border border-indigo-200 dark:border-indigo-800 transition">
                        + Add Question
                    </button>
                </div>
            </div>

            <!-- CSV Upload Toast / Notice if imported -->
            <div x-show="csvMessage" x-cloak class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 font-medium flex items-center justify-between">
                <span x-text="csvMessage"></span>
                <button type="button" @click="csvMessage = ''" class="text-emerald-600 font-bold ml-2">✕</button>
            </div>

            <!-- SINGLE QUESTION STEPPER MODE -->
            <div x-show="viewMode === 'single'" class="space-y-4">
                
                <!-- Stepper Progress Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white font-black text-xs">
                            Question <span x-text="currentIndex + 1"></span> of <span x-text="questions.length"></span>
                        </span>
                        <span class="text-xs text-slate-400" x-text="'Marks: ' + (questions[currentIndex]?.marks || 4)"></span>
                    </div>

                    <!-- Previous / Next Stepper Buttons -->
                    <div class="flex items-center space-x-2">
                        <button type="button" 
                                @click="prevQuestion()" 
                                :disabled="currentIndex === 0" 
                                class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            &larr; Prev
                        </button>
                        <button type="button" 
                                @click="nextQuestion()" 
                                :disabled="currentIndex >= questions.length - 1" 
                                class="px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-800 transition">
                            Next &rarr;
                        </button>
                    </div>
                </div>

                <!-- Stepper Progress Bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-emerald-400 h-1.5 rounded-full transition-all duration-200" 
                         :style="'width: ' + ((currentIndex + 1) / questions.length * 100) + '%'"></div>
                </div>

                <!-- Stepper Palette Bar -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                    <template x-for="(q, idx) in questions" :key="idx">
                        <button type="button" 
                                @click="currentIndex = idx" 
                                class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center transition flex-shrink-0"
                                :class="currentIndex === idx ? 'ring-2 ring-indigo-600 bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                x-text="idx + 1">
                        </button>
                    </template>
                </div>

                <!-- Current Question Card (Interactive Editor) -->
                <div x-show="questions[currentIndex]" class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            Editing Question #<span x-text="currentIndex + 1"></span>
                        </span>
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="duplicateQuestion(currentIndex)" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Duplicate
                            </button>
                            <span class="text-slate-300">&bull;</span>
                            <button type="button" @click="removeQuestion(currentIndex)" x-show="questions.length > 1" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                                Delete Question
                            </button>
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Question Prompt / Text *</label>
                        <textarea x-model="questions[currentIndex].text" rows="2" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- 4 Options -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option A *</label>
                            <input type="text" x-model="questions[currentIndex].option_a" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option B *</label>
                            <input type="text" x-model="questions[currentIndex].option_b" required class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option C</label>
                            <input type="text" x-model="questions[currentIndex].option_c" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Option D</label>
                            <input type="text" x-model="questions[currentIndex].option_d" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Correct Answer Selector & Marks -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Correct Option *</label>
                            <div class="flex items-center space-x-2">
                                <template x-for="opt in ['A', 'B', 'C', 'D']" :key="opt">
                                    <button type="button" 
                                            @click="questions[currentIndex].correct = opt" 
                                            class="w-9 h-9 rounded-xl font-black text-xs transition"
                                            :class="questions[currentIndex].correct === opt ? 'bg-emerald-600 text-white shadow-xs ring-2 ring-emerald-500' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 hover:bg-slate-100'"
                                            x-text="opt">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Marks Assigned</label>
                            <input type="number" min="1" max="50" x-model.number="questions[currentIndex].marks" class="w-28 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Explanation -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Step-by-Step Solution / Explanation</label>
                        <input type="text" x-model="questions[currentIndex].explanation" placeholder="Explain the rationale behind the correct option..." class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- ALL QUESTIONS LIST MODE -->
            <div x-show="viewMode === 'list'" class="space-y-3">
                <!-- Bulk Actions Toolbar -->
                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center space-x-2">
                        <button type="button" @click="selectAll()" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Select All</button>
                        <span class="text-slate-300">&bull;</span>
                        <button type="button" @click="deselectAll()" class="text-xs font-bold text-slate-500 hover:underline">Clear</button>
                        <span class="text-slate-400">Selected: <strong x-text="selectedCount"></strong></span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <span class="text-slate-500 font-medium">Bulk set marks:</span>
                        <button type="button" @click="bulkSetMarks(1)" class="px-2 py-0.5 rounded bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 font-bold">1</button>
                        <button type="button" @click="bulkSetMarks(2)" class="px-2 py-0.5 rounded bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 font-bold">2</button>
                        <button type="button" @click="bulkSetMarks(4)" class="px-2 py-0.5 rounded bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 font-bold">4</button>
                        <button type="button" @click="deleteSelected()" x-show="selectedCount > 0" class="px-2.5 py-0.5 rounded bg-rose-600 text-white font-bold hover:bg-rose-500 transition shadow-2xs">Delete Selected</button>
                    </div>
                </div>

                <!-- Questions List Cards -->
                <div class="space-y-2">
                    <template x-for="(q, idx) in questions" :key="idx">
                        <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="selectedIndices[idx]" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center font-bold text-[11px]" x-text="idx + 1"></span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Question <span x-text="idx + 1"></span></span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800" x-text="'Key: ' + q.correct"></span>
                                    <span class="text-[10px] font-semibold text-slate-400" x-text="q.marks + ' Marks'"></span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <button type="button" @click="moveUp(idx)" :disabled="idx === 0" class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-20" title="Move Up">↑</button>
                                    <button type="button" @click="moveDown(idx)" :disabled="idx === questions.length - 1" class="p-1 text-slate-400 hover:text-slate-700 disabled:opacity-20" title="Move Down">↓</button>
                                    <button type="button" @click="currentIndex = idx; viewMode = 'single'" class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-[11px] font-bold">
                                        Edit &rarr;
                                    </button>
                                </div>
                            </div>
                            <input type="text" x-model="q.text" required class="w-full px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100">
                        </div>
                    </template>
                </div>
            </div>

            <!-- HIDDEN INPUTS FOR ALL QUESTIONS SUBMISSION -->
            <template x-for="(q, idx) in questions" :key="idx">
                <div>
                    <input type="hidden" :name="'questions[' + idx + '][id]'" :value="q.id || ''">
                    <input type="hidden" :name="'questions[' + idx + '][text]'" :value="q.text">
                    <input type="hidden" :name="'questions[' + idx + '][option_a]'" :value="q.option_a">
                    <input type="hidden" :name="'questions[' + idx + '][option_b]'" :value="q.option_b">
                    <input type="hidden" :name="'questions[' + idx + '][option_c]'" :value="q.option_c">
                    <input type="hidden" :name="'questions[' + idx + '][option_d]'" :value="q.option_d">
                    <input type="hidden" :name="'questions[' + idx + '][correct]'" :value="q.correct">
                    <input type="hidden" :name="'questions[' + idx + '][explanation]'" :value="q.explanation">
                    <input type="hidden" :name="'questions[' + idx + '][marks]'" :value="q.marks">
                </div>
            </template>

            <!-- Save Action Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <a href="{{ route('dpp.show', $dpp->id) }}" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
                    Cancel Changes
                </a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/30 transition flex items-center space-x-1.5">
                    <span>💾 Save DPP &amp; Questions</span>
                </button>
            </div>
        </div>
    </form>

    <!-- QUESTION BANK PICKER MODAL -->
    <div x-show="openBankPickerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="openBankPickerModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full p-5 space-y-4 z-10 animate-in fade-in">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Select Questions from Question Bank</h3>
                <button type="button" @click="openBankPickerModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <input type="text" x-model="bankSearch" placeholder="Search questions or keywords..." class="w-full px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <template x-for="item in filteredBankQuestions" :key="item.id">
                    <label class="p-2.5 flex items-start space-x-2 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer">
                        <input type="checkbox" :value="item.id" x-model="selectedBankIds" class="rounded mt-0.5 border-slate-300 text-indigo-600">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-slate-100 line-clamp-2" x-text="item.question_text"></p>
                            <span class="text-[10px] text-slate-400" x-text="'Key: ' + item.correct_option + ' • Marks: ' + item.marks"></span>
                        </div>
                    </label>
                </template>
            </div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                <button type="button" @click="openBankPickerModal = false" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold text-slate-700">Cancel</button>
                <button type="button" @click="importSelectedBankQuestions()" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold">Import Selected</button>
            </div>
        </div>
    </div>

</div>

<script>
function dppEditorComponent() {
    const rawQuestions = {{ Js::from($dpp->questions) }};
    const initialList = rawQuestions.map(q => ({
        id: q.id,
        text: q.question_text || '',
        option_a: q.option_a || '',
        option_b: q.option_b || '',
        option_c: q.option_c || '',
        option_d: q.option_d || '',
        correct: q.correct_option || 'A',
        explanation: q.explanation || '',
        marks: q.marks || 4,
    }));

    return {
        viewMode: 'single', // 'single' or 'list'
        currentIndex: 0,
        itemType: '{{ $dpp->type }}',
        questions: initialList.length > 0 ? initialList : [
            { id: null, text: '', option_a: '', option_b: '', option_c: '', option_d: '', correct: 'A', explanation: '', marks: 4 }
        ],
        selectedIndices: {},
        csvMessage: '',
        openBankPickerModal: false,
        bankQuestions: {{ Js::from($questionBank) }},
        bankSearch: '',
        selectedBankIds: [],
        get filteredBankQuestions() {
            if (!this.bankSearch) return this.bankQuestions;
            const q = this.bankSearch.toLowerCase();
            return this.bankQuestions.filter(item => item.question_text && item.question_text.toLowerCase().includes(q));
        },
        importSelectedBankQuestions() {
            this.selectedBankIds.forEach(id => {
                const bq = this.bankQuestions.find(b => b.id == id);
                if (bq) {
                    this.questions.push({
                        id: null,
                        text: bq.question_text,
                        option_a: bq.option_a,
                        option_b: bq.option_b,
                        option_c: bq.option_c,
                        option_d: bq.option_d,
                        correct: bq.correct_option,
                        explanation: bq.explanation || '',
                        marks: bq.marks || 4
                    });
                }
            });
            this.selectedBankIds = [];
            this.openBankPickerModal = false;
        },
        get totalMarks() {
            return this.questions.reduce((sum, q) => sum + (parseInt(q.marks, 10) || 4), 0);
        },
        get selectedCount() {
            return Object.values(this.selectedIndices).filter(Boolean).length;
        },
        selectAll() {
            this.questions.forEach((_, idx) => this.selectedIndices[idx] = true);
        },
        deselectAll() {
            this.selectedIndices = {};
        },
        bulkSetMarks(val) {
            const hasSelection = this.selectedCount > 0;
            this.questions.forEach((q, idx) => {
                if (!hasSelection || this.selectedIndices[idx]) {
                    q.marks = val;
                }
            });
        },
        deleteSelected() {
            if (this.questions.length <= this.selectedCount) {
                alert('At least one question must be retained.');
                return;
            }
            if (!confirm(`Delete ${this.selectedCount} selected questions?`)) return;
            this.questions = this.questions.filter((_, idx) => !this.selectedIndices[idx]);
            this.selectedIndices = {};
            if (this.currentIndex >= this.questions.length) {
                this.currentIndex = Math.max(0, this.questions.length - 1);
            }
        },
        nextQuestion() {
            if (this.currentIndex < this.questions.length - 1) {
                this.currentIndex++;
            }
        },
        prevQuestion() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },
        addQuestion() {
            this.questions.push({
                id: null,
                text: '',
                option_a: '',
                option_b: '',
                option_c: '',
                option_d: '',
                correct: 'A',
                explanation: '',
                marks: 4
            });
            this.currentIndex = this.questions.length - 1;
        },
        duplicateQuestion(idx) {
            const current = this.questions[idx];
            if (current) {
                this.questions.splice(idx + 1, 0, {
                    id: null,
                    text: current.text,
                    option_a: current.option_a,
                    option_b: current.option_b,
                    option_c: current.option_c,
                    option_d: current.option_d,
                    correct: current.correct,
                    explanation: current.explanation,
                    marks: current.marks
                });
                this.currentIndex = idx + 1;
            }
        },
        removeQuestion(idx) {
            if (this.questions.length > 1) {
                this.questions.splice(idx, 1);
                if (this.currentIndex >= this.questions.length) {
                    this.currentIndex = this.questions.length - 1;
                }
            }
        },
        moveUp(idx) {
            if (idx > 0) {
                const temp = this.questions[idx];
                this.questions[idx] = this.questions[idx - 1];
                this.questions[idx - 1] = temp;
                this.questions = [...this.questions];
            }
        },
        moveDown(idx) {
            if (idx < this.questions.length - 1) {
                const temp = this.questions[idx];
                this.questions[idx] = this.questions[idx + 1];
                this.questions[idx + 1] = temp;
                this.questions = [...this.questions];
            }
        },
        handleCsvUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const text = e.target.result;
                    const parsed = this.parseCsvQuestions(text);
                    if (parsed.length > 0) {
                        if (this.questions.length === 1 && !this.questions[0].text.trim()) {
                            this.questions = parsed;
                        } else {
                            this.questions = this.questions.concat(parsed);
                        }
                        this.csvMessage = `Imported ${parsed.length} questions from ${file.name}. Review below.`;
                    }
                } catch (err) {
                    alert('Error reading CSV: ' + err.message);
                }
            };
            reader.readAsText(file);
        },
        parseCsvQuestions(text) {
            const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim().length > 0);
            if (lines.length < 2) return [];
            const header = lines[0].toLowerCase();
            const questions = [];

            for (let i = 1; i < lines.length; i++) {
                const row = lines[i].split(',').map(s => s.replace(/^"|"$/g, '').trim());
                if (row.length >= 3 && row[0]) {
                    let ans = (row[5] || 'A').toUpperCase();
                    if (!['A', 'B', 'C', 'D'].includes(ans)) ans = 'A';
                    questions.push({
                        id: null,
                        text: row[0],
                        option_a: row[1] || '',
                        option_b: row[2] || '',
                        option_c: row[3] || '',
                        option_d: row[4] || '',
                        correct: ans,
                        explanation: row[6] || '',
                        marks: parseInt(row[7], 10) || 4
                    });
                }
            }
            return questions;
        }
    };
}
</script>
@endsection
