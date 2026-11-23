@extends('layouts.app')

@section('title', $dpp->dpp_number . ' — ' . $dpp->title . ' — ' . ($school['name'] ?? 'PrimeArc Public School'))

@if(Auth::user()->isStudent() && (!$submission || ($isResolving ?? false)))
@section('hide_bottom_nav', 'true')
@endif

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation / Breadcrumb -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-xs">
            @if($dpp->course_id)
            <a href="{{ route('courses.show', ['id' => $dpp->course_id, 'tab' => 'dpp']) }}" class="inline-flex items-center space-x-1 font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                <span>🎓 Back to Course DPPs</span>
            </a>
            <span class="text-slate-300 dark:text-slate-700">&bull;</span>
            @endif
            <a href="{{ route('dpp.index', ['class_id' => $dpp->academicClass->grade_level ?? 8, 'type' => $dpp->type]) }}" class="inline-flex items-center space-x-2 font-bold text-slate-500 hover:text-indigo-600 transition">
                <span>&larr; Back to {{ $dpp->isTest() ? 'Timed Tests' : 'DPP Feed' }}</span>
            </a>
        </div>
        <div class="flex items-center space-x-2 text-xs">
            @if(!Auth::user()->isStudent())
            <a href="{{ route('dpp.edit', $dpp->id) }}" title="Edit DPP metadata and questions" class="px-2.5 py-1 rounded-lg font-bold bg-indigo-600 text-white hover:bg-indigo-500 transition flex items-center space-x-1 shadow-2xs">
                <span>✏️ Edit DPP</span>
            </a>
            <a href="{{ route('dpp.export-csv', $dpp->id) }}" title="Export DPP questions to CSV file" class="px-2.5 py-1 rounded-lg font-bold bg-slate-900 text-white hover:bg-slate-800 transition flex items-center space-x-1 shadow-2xs">
                <span>📥 Export CSV</span>
            </a>
            <form method="POST" action="{{ route('dpp.destroy', $dpp->id) }}" class="inline" onsubmit="return confirm('CAUTION: Are you sure you want to permanently delete this DPP / Timed Test set? All questions and student submissions will be deleted.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-2.5 py-1 rounded-lg font-bold bg-rose-600 text-white hover:bg-rose-500 transition flex items-center space-x-1 shadow-2xs" title="Delete DPP">
                    <span>🗑️ Delete</span>
                </button>
            </form>
            @endif
            <span class="px-2.5 py-1 rounded-lg font-bold bg-indigo-50 text-indigo-700">
                {{ $dpp->academicClass->name ?? 'Class' }}
            </span>
            <span class="px-2.5 py-1 rounded-lg font-bold bg-slate-100 text-slate-700">
                {{ $dpp->subject->name ?? 'Subject' }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $dpp->difficulty === 'olympiad' ? 'bg-purple-100 text-purple-800' : ($dpp->difficulty === 'advanced' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800') }}">
                {{ $dpp->difficulty }}
            </span>
        </div>
    </div>

    <!-- STUDENT SOLVING INTERFACE (ONE QUESTION AT A TIME WITH LIVE COUNTDOWN TIMER & MOBILE-FIRST STREAMLINED UI) -->
    @if(Auth::user()->isStudent() && (!$submission || ($isResolving ?? false)))

    @if($isResolving ?? false)
    <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs font-bold flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs">
        <div class="flex items-center space-x-2">
            <span class="text-base">🔄</span>
            <span>Re-solving Mode Active: Practice again to improve mastery. Previous Score: {{ $previousSubmission->score ?? 0 }}/{{ $previousSubmission->max_score ?? $dpp->total_marks }}</span>
        </div>
        @if($previousSubmission)
        <a href="{{ route('dpp.show', $dpp->id) }}" class="px-3 py-1 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs transition shadow-xs text-center">
            View Saved Scorecard &rarr;
        </a>
        @endif
    </div>
    @endif

    <div x-data="{
        currentIndex: 0,
        totalQuestions: {{ $dpp->questions_count ?? $dpp->questions->count() }},
        answers: {},
        markedForReview: {},
        isTimed: {{ $dpp->isTimed() ? 'true' : 'false' }},
        remainingSeconds: {{ $dpp->duration_minutes * 60 }},
        timeTakenSeconds: 0,
        timerFormatted: '{{ sprintf('%02d:00', $dpp->duration_minutes) }}',
        timerInterval: null,
        openSubmitModal: false,
        showPaletteDrawer: false,
        questionsData: [
            @foreach($dpp->questions as $q)
            { id: {{ $q->id }}, qNum: '{{ $q->question_number }}', marks: {{ $q->marks }} },
            @endforeach
        ],
        get currentQNum() {
            return this.questionsData[this.currentIndex]?.qNum || '1';
        },
        get currentQuestionMarks() {
            return this.questionsData[this.currentIndex]?.marks || 1;
        },
        get answeredCount() {
            return Object.values(this.answers).filter(v => v !== null && v !== undefined && v !== '').length;
        },
        get unansweredCount() {
            return this.totalQuestions - this.answeredCount;
        },
        get markedCount() {
            return Object.values(this.markedForReview).filter(Boolean).length;
        },
        get progressPercentage() {
            return this.totalQuestions > 0 ? Math.round((this.answeredCount / this.totalQuestions) * 100) : 0;
        },
        init() {
            if (this.isTimed && this.remainingSeconds > 0) {
                this.updateFormattedTime();
                this.timerInterval = setInterval(() => {
                    if (this.remainingSeconds > 0) {
                        this.remainingSeconds--;
                        this.timeTakenSeconds++;
                        this.updateFormattedTime();
                    } else {
                        clearInterval(this.timerInterval);
                        alert('Time is up! Your responses are being submitted for immediate automated evaluation.');
                        this.$refs.examForm.submit();
                    }
                }, 1000);
            }

            // Keyboard navigation shortcuts
            window.addEventListener('keydown', (e) => {
                if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
                if (e.key === 'ArrowRight' || e.key === 'n' || e.key === 'N') {
                    this.nextQuestion();
                } else if (e.key === 'ArrowLeft' || e.key === 'p' || e.key === 'P') {
                    this.prevQuestion();
                } else if (['1', 'a', 'A'].includes(e.key)) {
                    this.answers[this.currentQNum] = 'A';
                } else if (['2', 'b', 'B'].includes(e.key)) {
                    this.answers[this.currentQNum] = 'B';
                } else if (['3', 'c', 'C'].includes(e.key)) {
                    this.answers[this.currentQNum] = 'C';
                } else if (['4', 'd', 'D'].includes(e.key)) {
                    this.answers[this.currentQNum] = 'D';
                } else if (e.key === 'm' || e.key === 'M') {
                    this.toggleMarkForReview(this.currentQNum);
                }
            });
        },
        updateFormattedTime() {
            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            this.timerFormatted = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },
        nextQuestion() {
            if (this.currentIndex < this.totalQuestions - 1) {
                this.currentIndex++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        prevQuestion() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },
        selectQuestion(idx) {
            this.currentIndex = idx;
            this.showPaletteDrawer = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        toggleMarkForReview(qNum) {
            this.markedForReview[qNum] = !this.markedForReview[qNum];
        },
        clearCurrentAnswer(qNum) {
            delete this.answers[qNum];
            const radios = document.querySelectorAll('input[name=\'answers[' + qNum + ']\']');
            radios.forEach(r => r.checked = false);
        }
    }" class="space-y-3 pb-24">

        <!-- 1. COMPACT UNIFIED STICKY TOP BAR (Consolidates Active Timer, Question Number & Progress Bar into a Single Slim Row) -->
        <div class="sticky top-12 sm:top-14 z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs px-3 sm:px-4 py-2 sm:py-2.5 transition-all">
            <div class="flex items-center justify-between gap-2">
                <!-- Left: Question Indicator & Module Badge -->
                <div class="flex items-center space-x-2 min-w-0">
                    <span class="text-xs font-black text-slate-900 dark:text-white flex-shrink-0">
                        Q <span class="text-indigo-600 dark:text-indigo-400 font-extrabold" x-text="currentIndex + 1"></span><span class="text-slate-400 font-semibold">/<span x-text="totalQuestions"></span></span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex-shrink-0">
                        <span x-text="currentQuestionMarks"></span> Marks
                    </span>
                    <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-bold truncate max-w-[150px] md:max-w-[200px] {{ $dpp->isTest() ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                        {{ $dpp->isTest() ? 'Timed Examination' : 'Daily Practice Problem' }}
                    </span>
                </div>

                <!-- Center: Compact Timer Display -->
                @if($dpp->isTimed())
                <div class="flex items-center space-x-1.5 px-2.5 sm:px-3 py-1 rounded-xl border text-xs font-mono font-bold transition flex-shrink-0"
                     :class="remainingSeconds < 120 ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-300 animate-pulse' : (remainingSeconds < 300 ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-700')">
                    <span class="text-[10px] uppercase font-bold text-slate-400 hidden xs:inline">Time Left:</span>
                    <span class="text-xs sm:text-sm font-black" x-text="timerFormatted">{{ sprintf('%02d:00', $dpp->duration_minutes) }}</span>
                    <span class="w-2 h-2 rounded-full" :class="remainingSeconds < 120 ? 'bg-rose-500 animate-ping' : 'bg-emerald-400'"></span>
                </div>
                @endif

                <!-- Right: Scalable Question Palette Trigger Button -->
                <button type="button" 
                        @click="showPaletteDrawer = true" 
                        class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5 border border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-2xs"
                        title="Open Question Palette Drawer">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="hidden sm:inline">Question Palette:</span>
                    <span><span x-text="answeredCount"></span>/<span x-text="totalQuestions"></span></span>
                    <span class="text-[11px]">📋</span>
                </button>
            </div>

            <!-- Consolidated Slim Progress Bar Line -->
            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1 mt-2 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 h-full rounded-full transition-all duration-300" :style="'width: ' + progressPercentage + '%'"></div>
            </div>
        </div>

        <!-- 2. STREAMLINED TEST-TAKING VIEW (FLATTENED UNIFIED SURFACE WITHOUT NESTED CARDS) -->
        <form x-ref="examForm" method="POST" action="{{ route('dpp.submit', $dpp->id) }}">
            @csrf
            <input type="hidden" name="resolve" value="{{ $isResolving ? 1 : 0 }}">
            <input type="hidden" name="time_taken_seconds" :value="timeTakenSeconds">

            <!-- QUESTIONS: ONLY ONE IS ACTIVE AT A TIME -->
            @foreach($dpp->questions as $qIndex => $q)
            <div x-show="currentIndex === {{ $qIndex }}" style="display: none;" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                
                <!-- Stepper Question Header & Status Pills -->
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800 text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-2xs">
                            {{ $q->question_number }}
                        </span>
                        <span class="font-extrabold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px]">
                            Question {{ $qIndex + 1 }} of {{ $dpp->questions_count ?? $dpp->questions->count() }}
                        </span>
                        <span class="text-slate-400 text-[11px] font-medium hidden sm:inline">&bull; Multiple Choice Question</span>
                    </div>
                    <div class="flex items-center space-x-1.5">
                        <span x-show="answers['{{ $q->question_number }}']" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            Answered ✓
                        </span>
                        <span x-show="markedForReview['{{ $q->question_number }}']" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">
                            Marked for Review 🔖
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            {{ $q->marks }} Marks
                        </span>
                    </div>
                </div>

                <!-- Clean Question Prompt -->
                <div class="text-slate-900 dark:text-slate-100 font-bold text-xs sm:text-sm leading-relaxed">
                    {{ $q->question_text }}
                </div>

                <!-- 4 Responsive Option Selectors (Minimum 44px Touch Targets) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                    @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                    <label class="relative flex items-center p-2.5 sm:p-3 rounded-lg border cursor-pointer transition select-none group min-h-[46px]"
                           :class="answers['{{ $q->question_number }}'] === '{{ $optKey }}' ? 'border-emerald-600 dark:border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 ring-1 ring-emerald-500/30' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:border-indigo-300 dark:hover:border-slate-700 hover:bg-slate-100/60 dark:hover:bg-slate-800/40'">
                        <input type="radio" 
                               name="answers[{{ $q->question_number }}]" 
                               value="{{ $optKey }}" 
                               @change="answers['{{ $q->question_number }}'] = '{{ $optKey }}'"
                               class="sr-only">
                        <div class="w-6 h-6 rounded-md flex items-center justify-center font-bold text-xs flex-shrink-0 mr-2.5 transition"
                             :class="answers['{{ $q->question_number }}'] === '{{ $optKey }}' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300 group-hover:bg-indigo-100 group-hover:text-indigo-700 dark:group-hover:bg-slate-700'">
                            {{ $optKey }}
                        </div>
                        <span class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium leading-snug flex-1">
                            {{ $optVal }}
                        </span>
                    </label>
                    @endforeach
                </div>

                <!-- In-Card Question Navigation & Actions (Always visible directly beneath options) -->
                <div class="pt-3.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2.5">
                    <!-- Left Action: Previous Question -->
                    <button type="button" 
                            @click="prevQuestion()" 
                            :disabled="currentIndex === 0" 
                            class="px-3.5 sm:px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition flex items-center space-x-1.5 shadow-2xs">
                        <span>&larr; Previous</span>
                    </button>

                    <!-- Center & Right Actions -->
                    <div class="flex items-center space-x-2 ml-auto">
                        <!-- Mark for Review Button -->
                        <button type="button" 
                                @click="toggleMarkForReview('{{ $q->question_number }}')" 
                                class="px-2.5 sm:px-3 py-2 rounded-xl text-xs font-bold border transition flex items-center space-x-1 shadow-2xs"
                                :class="markedForReview['{{ $q->question_number }}'] ? 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                            <span>🔖</span>
                            <span class="hidden xs:inline" x-text="markedForReview['{{ $q->question_number }}'] ? 'Marked' : 'Review'"></span>
                        </button>

                        <!-- Clear Answer Button -->
                        <button type="button" 
                                @click="clearCurrentAnswer('{{ $q->question_number }}')" 
                                x-show="answers['{{ $q->question_number }}']" 
                                class="px-2.5 py-2 text-xs font-bold text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition">
                            Clear
                        </button>

                        <!-- Next Question Button -->
                        @if($qIndex < (($dpp->questions_count ?? $dpp->questions->count()) - 1))
                        <button type="button" 
                                @click="nextQuestion()" 
                                class="px-4 sm:px-5 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold hover:bg-slate-800 dark:hover:bg-slate-100 transition flex items-center space-x-1.5 shadow-sm">
                            <span>Next Question &rarr;</span>
                        </button>
                        @else
                        <!-- Last Question Submit Trigger -->
                        <button type="button" 
                                @click.stop="openSubmitModal = true" 
                                class="px-4 sm:px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center space-x-1.5">
                            <span>Finish &amp; Submit 🏁</span>
                        </button>
                        @endif
                    </div>
                </div>

            </div>
            @endforeach

            <!-- 3. QUESTION PALETTE COLLAPSIBLE BOTTOM SHEET (MOBILE) / SLIDE-OUT DRAWER (DESKTOP) (Scalable to 100+ Questions) -->
            <div x-show="showPaletteDrawer" x-cloak
                 class="fixed inset-0 z-50 flex justify-end"
                 role="dialog" 
                 aria-modal="true"
                 aria-label="Question Palette Overview">
                <!-- Backdrop Overlay -->
                <div x-show="showPaletteDrawer"
                     x-transition:enter="transition-opacity ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="showPaletteDrawer = false"
                     class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"></div>

                <!-- Drawer Panel -->
                <div x-show="showPaletteDrawer"
                     x-transition:enter="transition ease-out duration-200 transform"
                     x-transition:enter-start="translate-y-full md:translate-y-0 md:translate-x-full"
                     x-transition:enter-end="translate-y-0 md:translate-x-0"
                     x-transition:leave="transition ease-in duration-150 transform"
                     x-transition:leave-start="translate-y-0 md:translate-x-0"
                     x-transition:leave-end="translate-y-full md:translate-y-0 md:translate-x-full"
                     class="fixed inset-x-0 bottom-0 md:relative md:inset-y-0 md:left-auto md:w-96 max-h-[82vh] md:max-h-full h-auto md:h-full bg-white dark:bg-slate-900 border-t md:border-t-0 md:border-l border-slate-200 dark:border-slate-800 rounded-t-3xl md:rounded-none shadow-2xl flex flex-col z-10 safe-area-inset-bottom">
                    
                    <!-- Drawer Mobile Handle -->
                    <div class="md:hidden w-10 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mt-2.5 mb-1"></div>

                    <!-- Drawer Header -->
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white text-sm flex items-center space-x-1.5">
                                <span>📋</span>
                                <span>Question Palette: Overview</span>
                            </h3>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 space-x-2">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400"><span x-text="answeredCount"></span> Answered</span> &bull;
                                <span><span x-text="unansweredCount"></span> Left</span> &bull;
                                <span class="font-bold text-purple-600 dark:text-purple-400"><span x-text="markedCount"></span> Review</span>
                            </div>
                        </div>
                        <button type="button" @click="showPaletteDrawer = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            ✕
                        </button>
                    </div>

                    <!-- Color-Coded Legend Bar -->
                    <div class="px-4 py-2 bg-slate-50 dark:bg-slate-950/50 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2 text-[10px] font-bold">
                        <span class="flex items-center space-x-1 text-emerald-700 dark:text-emerald-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Answered</span>
                        </span>
                        <span class="flex items-center space-x-1 text-slate-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span>Unanswered</span>
                        </span>
                        <span class="flex items-center space-x-1 text-purple-700 dark:text-purple-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <span>Marked Review</span>
                        </span>
                        <span class="flex items-center space-x-1 text-indigo-700 dark:text-indigo-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span>Current</span>
                        </span>
                    </div>

                    <!-- Scalable Question Palette Grid (100+ questions supported smoothly) -->
                    <div class="flex-1 overflow-y-auto p-4 max-h-[50vh] md:max-h-none">
                        <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-6 gap-2">
                            @foreach($dpp->questions as $idx => $q)
                            <button type="button" 
                                    @click="selectQuestion({{ $idx }})"
                                    class="h-10 rounded-xl font-bold text-xs flex items-center justify-center transition relative shadow-2xs"
                                    :class="currentIndex === {{ $idx }} ? 'ring-2 ring-indigo-600 bg-indigo-600 text-white shadow-sm font-black' : (markedForReview['{{ $q->question_number }}'] ? 'bg-purple-100 dark:bg-purple-950/80 text-purple-800 dark:text-purple-300 border border-purple-300 dark:border-purple-800' : (answers['{{ $q->question_number }}'] ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'))">
                                <span>{{ $idx + 1 }}</span>
                                <span x-show="markedForReview['{{ $q->question_number }}']" class="absolute -top-1 -right-1 text-[9px]">🔖</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Drawer Footer: Dedicated Safe Submit Area -->
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 space-y-2">
                        <button type="button" 
                                @click="showPaletteDrawer = false; openSubmitModal = true;"
                                class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-xs shadow-md shadow-emerald-600/30 transition flex items-center justify-center space-x-1.5">
                            <span>Finish &amp; Submit</span>
                            <span class="bg-emerald-800/60 px-1.5 py-0.5 rounded text-[10px] text-amber-300 font-extrabold">+{{ $dpp->isTest() ? 50 : 30 }} XP</span>
                        </button>
                        <button type="button" @click="showPaletteDrawer = false" class="w-full py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                            Resume Solving &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. THUMB-FRIENDLY FIXED BOTTOM ACTION CONTROLS (EASILY ACCESSIBLE ON MOBILE VIEWPORTS) -->
            <div class="fixed bottom-0 left-0 right-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-3 sm:px-6 py-2.5 shadow-lg safe-area-inset-bottom">
                <div class="max-w-4xl mx-auto flex items-center justify-between gap-2">
                    
                    <!-- Previous Button (Left Thumb) -->
                    <button type="button" 
                            @click="prevQuestion()" 
                            :disabled="currentIndex === 0" 
                            class="px-3.5 sm:px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-30 disabled:cursor-not-allowed transition flex items-center space-x-1 shadow-2xs">
                        <span>&larr; Prev</span>
                    </button>

                    <!-- Center Controls: Mark for Review & Clear Answer -->
                    <div class="flex items-center space-x-1.5">
                        <!-- Mark for Review Button -->
                        <button type="button" 
                                @click="toggleMarkForReview(currentQNum)" 
                                class="px-2.5 sm:px-3 py-2 rounded-xl text-xs font-bold border transition flex items-center space-x-1 shadow-2xs"
                                :class="markedForReview[currentQNum] ? 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border-purple-300 dark:border-purple-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                            <span>🔖</span>
                            <span class="hidden sm:inline" x-text="markedForReview[currentQNum] ? 'Marked' : 'Mark Review'"></span>
                        </button>

                        <!-- Clear Answer Button -->
                        <button type="button" 
                                @click="clearCurrentAnswer(currentQNum)" 
                                x-show="answers[currentQNum]" 
                                class="px-2.5 sm:px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                            <span>Clear Answer</span>
                        </button>

                        <!-- Mobile Palette Quick Opener -->
                        <button type="button" 
                                @click="showPaletteDrawer = true" 
                                class="sm:hidden px-2.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <span>📋 <span x-text="answeredCount"></span>/<span x-text="totalQuestions"></span></span>
                        </button>
                    </div>

                    <!-- Next or Submit Button (Right Thumb) -->
                    <div>
                        <!-- Next Question Button -->
                        <button type="button" 
                                x-show="currentIndex < totalQuestions - 1"
                                @click="nextQuestion()" 
                                class="px-4 sm:px-5 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold hover:bg-slate-800 dark:hover:bg-slate-100 transition flex items-center space-x-1 shadow-sm">
                            <span>Next &rarr;</span>
                        </button>

                        <!-- Final Question Submit Trigger (High-Contrast Emerald CTA) -->
                        <button type="button" 
                                x-show="currentIndex === totalQuestions - 1"
                                @click.stop="openSubmitModal = true" 
                                class="px-4 sm:px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition flex items-center space-x-1">
                            <span>Finish &amp; Submit</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SUBMIT CONFIRMATION MODAL (Compact & Mobile-friendly) -->
            <div x-show="openSubmitModal" x-cloak 
                 class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3">
                <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" @click="openSubmitModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-4 sm:p-5 text-center space-y-3 z-10">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-xl shadow-2xs">
                        📝
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">Ready to Submit?</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Your responses will be evaluated instantly with solutions and performance metrics.</p>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 grid grid-cols-3 gap-2 text-xs border border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Answered</div>
                            <strong class="text-sm font-black text-emerald-600 dark:text-emerald-400" x-text="answeredCount"></strong>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Unanswered</div>
                            <strong class="text-sm font-black text-amber-600 dark:text-amber-400" x-text="unansweredCount"></strong>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Review</div>
                            <strong class="text-sm font-black text-purple-600 dark:text-purple-400" x-text="markedCount"></strong>
                        </div>
                    </div>

                    <div class="flex space-x-2 pt-1">
                        <button type="button" @click="openSubmitModal = false" class="flex-1 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Keep Solving
                        </button>
                        <button type="submit" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            Yes, Submit Test
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>
    @endif

    <!-- STUDENT RESULTS & STEP-BY-STEP SOLUTIONS REVIEW (AFTER SUBMISSION) -->
    @if(Auth::user()->isStudent() && $submission)
    <div x-data="{ reviewMode: 'single', currentReviewIndex: 0, totalReviewQuestions: {{ $dpp->questions_count ?? $dpp->questions->count() }} }" class="space-y-3">

        <!-- Score Card Banner (Clean, Compact, Non-bulky Mobile-first Ribbon) -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3 sm:p-4 text-slate-900 dark:text-white shadow-2xs border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <span>🎉 Instant Evaluation Scorecard — Automated Instant Evaluation Completed</span>
                    </div>
                    <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $dpp->title }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Evaluated on {{ $submission->checked_at?->format('M d, Y h:i A') ?? 'Today' }}
                        @if($submission->time_taken_seconds)
                            &bull; Completed in {{ floor($submission->time_taken_seconds / 60) }}m {{ $submission->time_taken_seconds % 60 }}s
                        @endif
                    </p>
                </div>

                <!-- Right / Inline Score & Rank Cluster -->
                <div class="flex flex-wrap items-center gap-2">
                    @if(isset($submission->rank))
                    <div class="px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-center">
                        <div class="text-sm font-black text-amber-700 dark:text-amber-400">#{{ $submission->rank }}</div>
                        <div class="text-[9px] uppercase font-bold text-amber-600 dark:text-amber-500">Class Rank</div>
                        @if(isset($submission->percentile))
                        <div class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">🎯 {{ $submission->percentile }}%tile</div>
                        @endif
                    </div>
                    @endif

                    <div class="px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center min-w-[120px]">
                        <div class="text-base font-black {{ $submission->percentage >= ($dpp->pass_percentage ?? 40) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            {{ $submission->score }}/{{ $submission->max_score }}
                        </div>
                        <div class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">
                            {{ $submission->percentage }}% Net Score
                        </div>
                    </div>

                    <div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-black inline-block {{ $submission->is_passed ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-700' }}">
                            {{ $submission->is_passed ? 'PASSED ✅' : 'NEEDS REVISION ⚠️' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Strip: Re-solve & XP -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="text-amber-600 dark:text-amber-400 font-bold text-[11px] sm:text-xs">
                    🔥 <em>"तेजस्वि नावधीतमस्तु"</em> &bull; +{{ $submission->xp_earned }} Academic XP Credited
                </span>
                <a href="{{ route('dpp.show', ['id' => $dpp->id, 'resolve' => 1]) }}"
                   class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-2xs">
                    <span>🔄</span>
                    <span>Resolve DPP</span>
                </a>
            </div>
        </div>

        <!-- Evaluation Performance Breakdown (Streamlined Metric Strip) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] block">Correct Answers</span>
                <span class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5 block">✅ {{ $submission->correct_count ?? 0 }}</span>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Earned full marks</span>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] block">Incorrect Answers</span>
                <span class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 mt-0.5 block">❌ {{ $submission->incorrect_count ?? 0 }}</span>
                <span class="text-[10px] text-rose-500 dark:text-rose-400 font-medium">
                    @if(($submission->negative_deduction ?? 0) > 0)
                        -{{ $submission->negative_deduction }} negative marks
                    @else
                        Zero penalty
                    @endif
                </span>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] block">Unattempted</span>
                <span class="text-base sm:text-lg font-black text-slate-700 dark:text-slate-300 mt-0.5 block">⚪ {{ $submission->unattempted_count ?? 0 }}</span>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Skipped questions</span>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] block">Speed &amp; Pace</span>
                <span class="text-base sm:text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5 block">
                    {{ floor(($submission->time_taken_seconds ?? 0) / 60) }}m {{ ($submission->time_taken_seconds ?? 0) % 60 }}s
                </span>
                <span class="text-[10px] text-indigo-500 dark:text-indigo-400 font-medium">Total time taken</span>
            </div>
        </div>

        <!-- Mode Switcher: Single Question Mode vs List Mode -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-2.5 border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Review Mode:</span>
                <div class="inline-flex rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5">
                    <button type="button" 
                            @click="reviewMode = 'single'" 
                            :class="reviewMode === 'single' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition flex items-center space-x-1">
                        <span>🎯 Single Question Stepper</span>
                    </button>
                    <button type="button" 
                            @click="reviewMode = 'list'" 
                            :class="reviewMode === 'list' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition flex items-center space-x-1">
                        <span>📜 All Questions List</span>
                    </button>
                </div>
            </div>

            <!-- Single Mode Stepper Controls -->
            <div x-show="reviewMode === 'single'" class="flex items-center space-x-2 self-end sm:self-center">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                    Question <span class="text-indigo-600 dark:text-indigo-400 font-black" x-text="currentReviewIndex + 1"></span> of {{ $dpp->questions_count ?? $dpp->questions->count() }}
                </span>
                <button type="button" 
                        @click="if (currentReviewIndex > 0) currentReviewIndex--" 
                        :disabled="currentReviewIndex === 0" 
                        class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    &larr; Prev
                </button>
                <button type="button" 
                        @click="if (currentReviewIndex < totalReviewQuestions - 1) currentReviewIndex++" 
                        :disabled="currentReviewIndex === totalReviewQuestions - 1" 
                        class="px-3 py-1 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-800 dark:hover:bg-slate-100 transition">
                    Next &rarr;
                </button>
            </div>
        </div>

        <!-- Palette for Review Mode -->
        <div x-show="reviewMode === 'single'" class="bg-white dark:bg-slate-900 rounded-xl p-2.5 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-1.5">
            <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400">
                <span>Jump to Question:</span>
                <div class="flex items-center space-x-2 text-[10px]">
                    <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span>Correct</span></span>
                    <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span><span>Incorrect</span></span>
                    <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span><span>Skipped</span></span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                @foreach($dpp->questions as $idx => $q)
                @php
                    $ansInfo = $submission->answers[(string)$q->question_number] ?? null;
                    $isCorrect = $ansInfo['is_correct'] ?? false;
                    $isAnswered = !empty($ansInfo['chosen']);
                @endphp
                <button type="button" 
                        @click="currentReviewIndex = {{ $idx }}" 
                        class="w-7 h-7 rounded-md text-xs font-bold flex items-center justify-center transition flex-shrink-0"
                        :class="currentReviewIndex === {{ $idx }} ? 'ring-2 ring-indigo-600 font-black shadow-2xs' : ''"
                        style="{{ $isCorrect ? 'background-color: #d1fae5; color: #065f46;' : ($isAnswered ? 'background-color: #fee2e2; color: #991b1b;' : 'background-color: #f1f5f9; color: #64748b;') }}">
                    {{ $idx + 1 }}
                </button>
                @endforeach
            </div>
        </div>

        <!-- Detailed Solutions List -->
        <div class="space-y-3">
            @foreach($dpp->questions as $qIndex => $q)
            @php
                $ansInfo = $submission->answers[(string)$q->question_number] ?? null;
                $chosenOpt = $ansInfo['chosen'] ?? null;
                $isCorrect = $ansInfo['is_correct'] ?? false;
            @endphp
            <div x-show="reviewMode === 'list' || currentReviewIndex === {{ $qIndex }}" class="bg-white dark:bg-slate-900 rounded-xl border {{ $isCorrect ? 'border-emerald-200 dark:border-emerald-800/60' : 'border-rose-200 dark:border-rose-800/60' }} p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-md {{ $isCorrect ? 'bg-emerald-600' : 'bg-rose-600' }} text-white flex items-center justify-center font-bold text-xs">
                            Q{{ $q->question_number }}
                        </span>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                            {{ $isCorrect ? 'Correct (+' . $q->marks . ' Marks)' : 'Incorrect (0 Marks)' }}
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $isCorrect ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300' }}">
                        {{ $isCorrect ? '✅ Solved Correctly' : '❌ Needs Review' }}
                    </span>
                </div>

                <div class="text-slate-900 dark:text-slate-100 font-semibold text-xs sm:text-sm leading-relaxed">
                    {{ $q->question_text }}
                </div>

                <!-- 4 Options with Colored Highlights (Compact Grid) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                    @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                    @php
                        $isThisChosen = ($chosenOpt === $optKey);
                        $isThisCorrect = ($q->correct_option === $optKey);
                    @endphp
                    <div class="p-2 sm:p-2.5 rounded-lg border flex items-center justify-between gap-2 text-xs {{ $isThisCorrect ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 font-bold text-emerald-900 dark:text-emerald-200' : ($isThisChosen ? 'border-rose-500 bg-rose-50/70 dark:bg-rose-950/40 font-semibold text-rose-900 dark:text-rose-200' : 'border-slate-200 dark:border-slate-700 bg-slate-50/30 dark:bg-slate-800/40 text-slate-700 dark:text-slate-300') }}">
                        <div class="flex items-center space-x-2 min-w-0">
                            <span class="w-5 h-5 rounded flex items-center justify-center font-bold text-[11px] flex-shrink-0 {{ $isThisCorrect ? 'bg-emerald-600 text-white' : ($isThisChosen ? 'bg-rose-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300') }}">
                                {{ $optKey }}
                            </span>
                            <span class="leading-snug truncate sm:text-clip">
                                {{ $optVal }}
                            </span>
                        </div>
                        <div class="flex-shrink-0">
                            @if($isThisCorrect && $isThisChosen)
                                <span class="text-[9px] uppercase font-black text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950 px-1.5 py-0.5 rounded">Your Answer (Correct)</span>
                            @elseif($isThisCorrect)
                                <span class="text-[9px] uppercase font-black text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950 px-1.5 py-0.5 rounded">Correct Answer</span>
                            @elseif($isThisChosen)
                                <span class="text-[9px] uppercase font-black text-rose-700 dark:text-rose-300 bg-rose-100 dark:bg-rose-950 px-1.5 py-0.5 rounded">Your Selection</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Step-by-Step Explanation Box -->
                <div class="p-2.5 sm:p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1">
                    <div class="flex items-center space-x-1.5 text-xs font-bold text-indigo-700 dark:text-indigo-400">
                        <span>💡 Step-by-Step Solution &amp; Explanation:</span>
                    </div>
                    <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                        {{ $q->explanation }}
                    </p>
                </div>

                <!-- Footer Navigation for Single Mode -->
                <div x-show="reviewMode === 'single'" class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" 
                            @click="if (currentReviewIndex > 0) currentReviewIndex--" 
                            :disabled="currentReviewIndex === 0" 
                            class="px-3 py-1 rounded-lg border border-slate-300 dark:border-slate-700 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        &larr; Previous Question
                    </button>
                    <button type="button" 
                            @click="if (currentReviewIndex < totalReviewQuestions - 1) currentReviewIndex++" 
                            :disabled="currentReviewIndex === totalReviewQuestions - 1" 
                            class="px-3.5 py-1 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-800 dark:hover:bg-slate-100 transition">
                        Next Question &rarr;
                    </button>
                </div>
            </div>
            @endforeach
        </div>

    </div>
    @endif

    <!-- TEACHER / ADMIN VIEW: QUESTIONS, ANSWER KEY & STUDENT SUBMISSIONS -->
    @if(!Auth::user()->isStudent())
    <div class="space-y-4">

        <!-- Header Overview (Compact, Clean, Non-bulky) -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-3.5 sm:p-4 border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <span>{{ $dpp->isTest() ? '⏱️ Timed Online Examination' : '🎯 Daily Practice Problem' }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                    {{ $dpp->title }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Class: <strong class="text-slate-700 dark:text-slate-200">{{ $dpp->academicClass->name ?? 'Class' }}</strong> &bull;
                    Subject: <strong class="text-slate-700 dark:text-slate-200">{{ $dpp->subject->name ?? 'Subject' }}</strong> &bull;
                    Timer: <strong class="text-slate-700 dark:text-slate-200">{{ $dpp->duration_minutes }} Mins</strong> &bull;
                    Total Marks: <strong class="text-slate-700 dark:text-slate-200">{{ $dpp->total_marks }}</strong>
                </p>
                <div class="pt-1 flex items-center space-x-2">
                    <a href="{{ route('dpp.edit', $dpp->id) }}" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition flex items-center space-x-1 shadow-2xs">
                        <span>✏️ Edit DPP &amp; Questions</span>
                    </a>
                    <a href="{{ route('dpp.export-csv', $dpp->id) }}" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-800 dark:text-slate-200 font-bold text-xs transition flex items-center space-x-1 border border-slate-200 dark:border-slate-700">
                        <span>📥 Export CSV</span>
                    </a>
                </div>
            </div>

            <div class="px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center min-w-[120px] self-start sm:self-auto">
                <div class="text-2xl font-black text-amber-500">{{ $dpp->submissions_count ?? $dpp->submissions->count() }}</div>
                <div class="text-[10px] uppercase font-extrabold tracking-wider text-slate-500 dark:text-slate-400">Submissions</div>
            </div>
        </div>

        <!-- Questions Key & Explanations -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-3.5 sm:p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Questions &amp; Master Answer Key</h3>
                <span class="text-[11px] text-slate-400">{{ $dpp->questions_count ?? $dpp->questions->count() }} Questions</span>
            </div>
            
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($dpp->questions as $q)
                <div class="py-3 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400">Question {{ $q->question_number }} ({{ $q->marks }} Marks)</span>
                        <span class="text-[11px] font-extrabold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/70 px-2 py-0.5 rounded">
                            Key: Option {{ $q->correct_option }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $q->question_text }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs text-slate-600 dark:text-slate-300 pt-0.5">
                        <div class="p-1.5 rounded border border-slate-200 dark:border-slate-700 {{ $q->correct_option === 'A' ? 'font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-300' : 'bg-slate-50/40 dark:bg-slate-800/30' }}">A) {{ $q->option_a }}</div>
                        <div class="p-1.5 rounded border border-slate-200 dark:border-slate-700 {{ $q->correct_option === 'B' ? 'font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-300' : 'bg-slate-50/40 dark:bg-slate-800/30' }}">B) {{ $q->option_b }}</div>
                        <div class="p-1.5 rounded border border-slate-200 dark:border-slate-700 {{ $q->correct_option === 'C' ? 'font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-300' : 'bg-slate-50/40 dark:bg-slate-800/30' }}">C) {{ $q->option_c }}</div>
                        <div class="p-1.5 rounded border border-slate-200 dark:border-slate-700 {{ $q->correct_option === 'D' ? 'font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/30 border-emerald-300' : 'bg-slate-50/40 dark:bg-slate-800/30' }}">D) {{ $q->option_d }}</div>
                    </div>

                    <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg text-xs text-slate-600 dark:text-slate-400 mt-1.5 border border-slate-200 dark:border-slate-700">
                        <strong class="text-indigo-700 dark:text-indigo-400">Explanation:</strong> {{ $q->explanation }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Student Submissions Table (Responsive & Compact) -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
            <div class="p-3 sm:p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Student Submissions &amp; Auto-Graded Scores</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Same-day results with time taken and passing status</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ $dpp->submissions_count ?? $dpp->submissions->count() }} Submissions
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 font-bold text-[10px]">
                        <tr>
                            <th class="px-3 py-2.5">Rank</th>
                            <th class="px-3 py-2.5">Student</th>
                            <th class="px-3 py-2.5 hidden sm:table-cell">Admission ID</th>
                            <th class="px-3 py-2.5">Score</th>
                            <th class="px-3 py-2.5">Accuracy</th>
                            <th class="px-3 py-2.5 hidden sm:table-cell">Time Taken</th>
                            <th class="px-3 py-2.5 text-right">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($submissions ?? $dpp->submissions as $sub)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    #{{ $sub->rank ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $sub->student->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono sm:hidden">
                                    {{ $sub->student->student_id_number ?? 'PAA-STU-' . $sub->student_id }}
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-slate-600 dark:text-slate-400 font-mono hidden sm:table-cell text-[11px]">
                                {{ $sub->student->student_id_number ?? 'PAA-STU-' . $sub->student_id }}
                            </td>
                            <td class="px-3 py-2.5 font-bold text-slate-900 dark:text-white">
                                {{ $sub->score }}/{{ $sub->max_score }}
                                <div class="text-[9px] text-slate-400 font-normal">
                                    {{ $sub->correct_count ?? 0 }}C &bull; {{ $sub->incorrect_count ?? 0 }}W
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $sub->percentage >= ($dpp->pass_percentage ?? 40) ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                                    {{ $sub->percentage }}%
                                </span>
                                @if(isset($sub->percentile))
                                <div class="text-[9px] text-slate-500 font-bold mt-0.5">{{ $sub->percentile }}%tile</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-slate-500 dark:text-slate-400 hidden sm:table-cell text-[11px]">
                                @if($sub->time_taken_seconds)
                                    {{ floor($sub->time_taken_seconds / 60) }}m {{ $sub->time_taken_seconds % 60 }}s
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $sub->is_passed ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">
                                    {{ $sub->is_passed ? 'Passed ✅' : 'Failed ❌' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400 text-xs">
                                No students have submitted this practice problem set or exam yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($submissions) && $submissions instanceof \Illuminate\Pagination\LengthAwarePaginator && $submissions->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                {{ $submissions->links() }}
            </div>
            @endif
        </div>

    </div>
    @endif

</div>
@endsection
