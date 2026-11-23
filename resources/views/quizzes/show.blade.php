@extends('layouts.app')

@section('title', $quiz->title . ' - Arena Quiz')

@section('content')
<div x-data="{
    activeTab: '{{ request()->query('view', $previousAttempt ? 'results' : 'arena') }}',
    currentQuestionIndex: 0,
    totalQuestions: {{ $questions->count() }},
    timeRemaining: {{ $quiz->time_per_question }},
    timePerQuestion: {{ $quiz->time_per_question }},
    answers: {},
    timerInterval: null,
    isSubmitted: false,
    quizStarted: false,
    soundEnabled: true,
    audioCtx: null,
    liveAnswered: {},
    reviewMode: 'single',
    currentReviewIndex: 0,
    totalReviewQuestions: {{ $questions->count() }},
    openCsvModal: false,
    openTextModal: false,
    openSingleQModal: false,
    editingQuestion: null,

    initAudio() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },
    playTone(freq, type = 'sine', duration = 0.1, gainVal = 0.15) {
        if (!this.soundEnabled) return;
        this.initAudio();
        if (!this.audioCtx) return;
        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime);
            gain.gain.setValueAtTime(gainVal, this.audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, this.audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);
            osc.start();
            osc.stop(this.audioCtx.currentTime + duration);
        } catch(e) {}
    },
    playTick(isUrgent = false) {
        if (!this.soundEnabled) return;
        if (isUrgent) {
            // Rapid double hyper tick for adrenaline
            this.playTone(880, 'square', 0.05, 0.09);
            setTimeout(() => this.playTone(1100, 'square', 0.05, 0.11), 70);
        } else {
            this.playTone(480, 'sine', 0.04, 0.05);
        }
    },
    playSelectChime() {
        if (!this.soundEnabled) return;
        this.playTone(660, 'sine', 0.07, 0.12);
        setTimeout(() => this.playTone(990, 'triangle', 0.1, 0.15), 50);
    },
    playWhoosh() {
        if (!this.soundEnabled) return;
        this.playTone(280, 'sawtooth', 0.12, 0.09);
        setTimeout(() => this.playTone(560, 'sine', 0.1, 0.11), 40);
    },
    playTimeoutBuzzer() {
        if (!this.soundEnabled) return;
        this.playTone(160, 'sawtooth', 0.28, 0.22);
    },
    playVictoryFanfare() {
        if (!this.soundEnabled) return;
        const notes = [523.25, 659.25, 783.99, 1046.50];
        notes.forEach((freq, idx) => {
            setTimeout(() => this.playTone(freq, 'triangle', 0.2, 0.16), idx * 85);
        });
    },
    getLiveScholarsCount(idx) {
        if (!this.liveAnswered[idx]) {
            this.liveAnswered[idx] = 19 + ((idx * 8 + 3) % 21);
        }
        return this.liveAnswered[idx];
    },
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        if (this.soundEnabled) {
            this.playSelectChime();
        }
    },
    startQuiz() {
        if (this.totalQuestions === 0) return;
        this.initAudio();
        this.activeTab = 'arena';
        this.quizStarted = true;
        this.currentQuestionIndex = 0;
        this.playWhoosh();
        this.resetQuestionTimer();
    },
    selectOption(questionId, option) {
        this.answers[questionId] = option;
        this.playSelectChime();
    },
    clearOption(questionId) {
        delete this.answers[questionId];
    },
    jumpTo(index) {
        // Forward-only progression strictly enforced: cannot revisit past questions
        if (index > this.currentQuestionIndex && index < this.totalQuestions) {
            if (this.liveAnswered[this.currentQuestionIndex]) {
                this.liveAnswered[this.currentQuestionIndex]++;
            }
            this.currentQuestionIndex = index;
            this.playWhoosh();
            this.resetQuestionTimer();
        }
    },
    nextQuestion() {
        if (this.liveAnswered[this.currentQuestionIndex]) {
            this.liveAnswered[this.currentQuestionIndex]++;
        }
        if (this.currentQuestionIndex < this.totalQuestions - 1) {
            this.currentQuestionIndex++;
            this.playWhoosh();
            this.resetQuestionTimer();
        } else {
            this.confirmAndSubmit();
        }
    },
    resetQuestionTimer() {
        clearInterval(this.timerInterval);
        this.timeRemaining = this.timePerQuestion;
        this.timerInterval = setInterval(() => {
            if (this.timeRemaining > 0) {
                this.timeRemaining--;
                // Arena hyper ticking audio
                const isUrgent = this.timeRemaining <= 7;
                this.playTick(isUrgent);
            } else {
                this.playTimeoutBuzzer();
                if (this.currentQuestionIndex < this.totalQuestions - 1) {
                    this.nextQuestion();
                } else {
                    this.submitQuiz();
                }
            }
        }, 1000);
    },
    confirmAndSubmit() {
        if (confirm('Are you ready to submit your answers and calculate your arena rank?')) {
            this.submitQuiz();
        }
    },
    submitQuiz() {
        clearInterval(this.timerInterval);
        this.isSubmitted = true;
        this.playVictoryFanfare();
        document.getElementById('live-quiz-form').submit();
    }
}" class="max-w-4xl mx-auto space-y-6">

    <!-- Top Action Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('quizzes.index') }}" class="text-xs font-bold text-slate-500 hover:text-emerald-500 flex items-center space-x-1">
            <span>&larr; Back to Quizzes Arena</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                Code: {{ $quiz->access_code }}
            </span>
            <span class="text-xs font-bold text-amber-500 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 rounded-xl border border-amber-200 dark:border-amber-900">
                ⚡ +{{ $quiz->xp_reward }} XP
            </span>
            @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
            <a href="{{ route('quizzes.edit', $quiz->id) }}" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl border border-slate-300 dark:border-slate-700">
                ✏️ Edit Quiz
            </a>
            @endif
        </div>
    </div>

    <!-- Navigation Tab Bar -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 text-xs overflow-x-auto">
        <button type="button" @click="activeTab = 'arena'" class="px-4 py-2 font-bold rounded-xl transition flex items-center space-x-1.5 whitespace-nowrap"
                :class="activeTab === 'arena' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>⚡</span>
            <span>Sprint Arena</span>
        </button>

        @if($previousAttempt)
        <button type="button" @click="activeTab = 'results'" class="px-4 py-2 font-bold rounded-xl transition flex items-center space-x-1.5 whitespace-nowrap"
                :class="activeTab === 'results' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>📊</span>
            <span>Solutions &amp; Review</span>
        </button>
        @endif

        <button type="button" @click="activeTab = 'leaderboard'" class="px-4 py-2 font-bold rounded-xl transition flex items-center space-x-1.5 whitespace-nowrap"
                :class="activeTab === 'leaderboard' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
            <span>🏆</span>
            <span>Arena Leaderboard</span>
        </button>

        @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
        <button type="button" @click="activeTab = 'manage'" class="px-4 py-2 font-bold rounded-xl transition flex items-center space-x-1.5 whitespace-nowrap"
                :class="activeTab === 'manage' ? 'bg-emerald-600 text-white shadow-xs' : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40'">
            <span>⚙️</span>
            <span>Manage Questions ({{ $questions->count() }})</span>
        </button>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: SPRINT ARENA (QUIZ TAKING) -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'arena'" class="space-y-5">
        
        @if($questions->isEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-10 text-center space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-500 text-2xl flex items-center justify-center mx-auto">
                ⚡
            </div>
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">This quiz has no questions yet</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">The faculty instructor has not populated questions for this live arena challenge yet.</p>
            @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
            <div class="pt-3">
                <button type="button" @click="activeTab = 'manage'" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    + Import Questions Now
                </button>
            </div>
            @endif
        </div>
        @else

            <!-- PRE-START BRIEFING SCREEN -->
            <div x-show="!quizStarted" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 text-center space-y-5 shadow-xs">
                <div class="w-16 h-16 rounded-3xl bg-amber-500/15 text-amber-500 border border-amber-500/30 flex items-center justify-center text-3xl mx-auto">
                    ⚡
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ $quiz->title }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">{{ $quiz->description ?: 'Fast-paced live arena quiz sprint. Answer quickly and accurately to maximize your score and climb the leaderboard.' }}</p>
                </div>

                <div class="grid grid-cols-3 gap-3 max-w-sm mx-auto py-2">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] text-slate-400 block font-bold">Questions</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ $questions->count() }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] text-slate-400 block font-bold">Speed</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">{{ $quiz->time_per_question }}s</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-[10px] text-slate-400 block font-bold">XP Gift</span>
                        <span class="text-lg font-black text-amber-500">+{{ $quiz->xp_reward }}</span>
                    </div>
                </div>

                @if($previousAttempt)
                <div class="p-3 max-w-sm mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 font-medium">
                    Previous Record: <strong>{{ $previousAttempt->score }} pts</strong> &bull; {{ $previousAttempt->correct_answers }}/{{ $previousAttempt->total_questions }} Correct (+{{ $previousAttempt->xp_earned }} XP)
                </div>
                @endif

                <div class="pt-2">
                    <button type="button" @click="startQuiz()" class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-black text-sm rounded-2xl shadow-lg shadow-emerald-600/30 transition">
                        Start Live Quiz Sprint 🚀
                    </button>
                </div>
            </div>

            <!-- ACTIVE TIMED QUIZ INTERFACE -->
            <div x-show="quizStarted" x-cloak class="space-y-4">
                
                <!-- Live Timer & Question Palette -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-slate-500 dark:text-slate-400">
                                Question <span class="text-slate-900 dark:text-white font-black text-sm" x-text="currentQuestionIndex + 1"></span> of {{ $questions->count() }}
                            </span>
                            <button type="button" @click="toggleSound()" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border transition flex items-center space-x-1"
                                    :class="soundEnabled ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-300 dark:border-amber-800' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'">
                                <span x-text="soundEnabled ? '🔊 Hyper SFX' : '🔇 Muted'"></span>
                            </button>
                        </div>

                        <div class="flex items-center space-x-1 font-mono font-black text-sm" :class="timeRemaining <= 7 ? 'text-rose-500 animate-pulse scale-105' : 'text-amber-500'">
                            <span>⏱️</span>
                            <span x-text="timeRemaining + 's'"></span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-300"
                             :style="'width: ' + (((currentQuestionIndex + 1) / totalQuestions) * 100) + '%'"></div>
                    </div>

                    <!-- Question Navigation Palette -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between text-[10px] font-bold mb-1.5">
                            <span class="text-slate-400 uppercase tracking-wider">Question Palette (Forward-Only Arena):</span>
                            <span class="text-amber-500 font-semibold">🔒 Past questions locked</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                            @foreach($questions as $idx => $q)
                            <button type="button"
                                    @click="jumpTo({{ $idx }})"
                                    :disabled="{{ $idx }} < currentQuestionIndex"
                                    :class="{
                                        'ring-2 ring-emerald-500 ring-offset-1 font-black bg-emerald-600 text-white': currentQuestionIndex === {{ $idx }},
                                        'bg-slate-100 dark:bg-slate-800/40 text-slate-400 dark:text-slate-600 border-slate-200 dark:border-slate-800 cursor-not-allowed opacity-40': {{ $idx }} < currentQuestionIndex,
                                        'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700': {{ $idx }} > currentQuestionIndex
                                    }"
                                    class="w-7 h-7 rounded-lg text-xs font-bold border transition flex items-center justify-center">
                                {{ $idx + 1 }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Questions Form -->
                <form id="live-quiz-form" action="{{ route('quizzes.submit', $quiz->id) }}" method="POST">
                    @csrf

                    @foreach($questions as $idx => $q)
                    <div x-show="currentQuestionIndex === {{ $idx }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4.5 space-y-3.5 shadow-2xs">
                        
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                {{ $q->points }} Points
                            </span>

                            <!-- Real-time Scholars Completed Indicator -->
                            <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-300 border border-orange-200 dark:border-orange-800/80 text-[11px] font-bold">
                                <span>🔥</span>
                                <span x-text="getLiveScholarsCount({{ $idx }})"></span>
                                <span>scholars answered</span>
                            </div>
                        </div>

                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-relaxed">
                            {{ $q->question }}
                        </h3>

                        <!-- 4 Options Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                            <button type="button" 
                                    @click="selectOption({{ $q->id }}, '{{ $optKey }}')" 
                                    :class="answers[{{ $q->id }}] === '{{ $optKey }}' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 ring-1 ring-emerald-500' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 text-slate-800 dark:text-slate-200'"
                                    class="p-2.5 sm:p-3 rounded-lg border text-left text-xs font-semibold transition flex items-center space-x-2.5 min-h-[44px]">
                                <span class="w-6 h-6 rounded-md border font-mono font-bold flex items-center justify-center flex-shrink-0 text-xs"
                                      :class="answers[{{ $q->id }}] === '{{ $optKey }}' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300'">
                                    {{ $optKey }}
                                </span>
                                <span class="leading-snug flex-1">{{ $optVal }}</span>
                            </button>
                            @endforeach
                        </div>

                        <!-- Hidden Input for Form Submission -->
                        <input type="hidden" :name="'answers[' + {{ $q->id }} + ']'" :value="answers[{{ $q->id }}] || ''">

                        <!-- Navigation Controls (Strictly Forward Progression) -->
                        <div class="pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="flex items-center space-x-1.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                <span>⚡</span>
                                <span>Forward Sprint Only</span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="clearOption({{ $q->id }})" class="text-xs font-semibold text-rose-500 hover:text-rose-600 hover:underline px-2 py-1">
                                    Clear Selection
                                </button>

                                <button type="button" 
                                        @click="nextQuestion()" 
                                        class="px-4 sm:px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5">
                                    <span x-text="currentQuestionIndex === totalQuestions - 1 ? 'Finish &amp; Submit 🏁' : 'Next Question &rarr;'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </form>
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: SOLUTIONS & REVIEW -->
    <!-- ========================================== -->
    @if($previousAttempt)
    <div x-show="activeTab === 'results'" class="space-y-4">
        <!-- Score Overview Banner (Compact, Non-bulky) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Attempt Record</span>
                    <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                        Score: {{ $previousAttempt->score }} Points
                    </h2>
                    <p class="text-xs text-slate-500">
                        Accuracy: {{ round(($previousAttempt->correct_answers / max(1, $previousAttempt->total_questions)) * 100) }}% &bull; {{ $previousAttempt->correct_answers }} of {{ $previousAttempt->total_questions }} Correct
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold">
                        ⚡ +{{ $previousAttempt->xp_earned }} XP Earned
                    </span>
                    <button type="button" @click="startQuiz()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                        Retake Sprint 🔄
                    </button>
                </div>
            </div>
        </div>

        <!-- Question By Question Solutions Breakdown -->
        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-xl p-2.5 sm:p-3 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Review Mode:</span>
                    <div class="inline-flex rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5">
                        <button type="button" 
                                @click="reviewMode = 'single'" 
                                :class="reviewMode === 'single' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="px-2.5 py-1 rounded-md text-xs transition flex items-center space-x-1">
                            <span>🎯 Single Question Stepper</span>
                        </button>
                        <button type="button" 
                                @click="reviewMode = 'list'" 
                                :class="reviewMode === 'list' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 font-medium'"
                                class="px-2.5 py-1 rounded-md text-xs transition flex items-center space-x-1">
                            <span>📜 All Questions List</span>
                        </button>
                    </div>
                </div>

                <!-- Stepper Controls (Single Mode) -->
                <div x-show="reviewMode === 'single'" class="flex items-center space-x-2 self-end sm:self-center">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        Question <span class="text-emerald-600 dark:text-emerald-400 font-black" x-text="currentReviewIndex + 1"></span> of {{ $questions->count() }}
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

            <!-- Review Palette Bar -->
            <div x-show="reviewMode === 'single'" class="bg-white dark:bg-slate-900 rounded-xl p-2.5 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-1.5">
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400">
                    <span>Jump to Solution:</span>
                    <div class="flex items-center space-x-2 text-[10px]">
                        <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><span>Correct</span></span>
                        <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span><span>Incorrect</span></span>
                        <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></span><span>Skipped</span></span>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                    @foreach($questions as $idx => $q)
                    @php
                        $det = $payload[$q->id] ?? null;
                        $qCorr = $det['is_correct'] ?? false;
                        $qChosen = $det['chosen'] ?? null;
                    @endphp
                    <button type="button" 
                            @click="currentReviewIndex = {{ $idx }}" 
                            class="w-7 h-7 rounded-md text-xs font-bold flex items-center justify-center transition flex-shrink-0"
                            :class="currentReviewIndex === {{ $idx }} ? 'ring-2 ring-emerald-600 font-black shadow-2xs' : ''"
                            style="{{ $qCorr ? 'background-color: #d1fae5; color: #065f46;' : ($qChosen ? 'background-color: #fee2e2; color: #991b1b;' : 'background-color: #f1f5f9; color: #64748b;') }}">
                        {{ $idx + 1 }}
                    </button>
                    @endforeach
                </div>
            </div>

            @php
                $payload = $previousAttempt->answers_payload ?? [];
            @endphp

            @foreach($questions as $idx => $q)
            @php
                $attemptDetail = $payload[$q->id] ?? null;
                $isCorrect = $attemptDetail['is_correct'] ?? false;
                $chosen = $attemptDetail['chosen'] ?? null;
            @endphp
            <div x-show="reviewMode === 'list' || currentReviewIndex === {{ $idx }}" 
                 class="bg-white dark:bg-slate-900 border rounded-xl p-3.5 sm:p-4 space-y-2.5 shadow-2xs {{ $isCorrect ? 'border-emerald-300 dark:border-emerald-800/60' : 'border-rose-300 dark:border-rose-800/60' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Question {{ $idx + 1 }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $isCorrect ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                        {{ $isCorrect ? '✓ Correct (+'.$q->points.' pts)' : ($chosen ? '✗ Incorrect (0 pts)' : 'Unanswered (0 pts)') }}
                    </span>
                </div>

                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-relaxed">
                    {{ $q->question }}
                </h4>

                <!-- Options -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                    @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $optKey => $optVal)
                    <div class="p-2 sm:p-2.5 rounded-lg border flex items-center justify-between gap-1.5
                        @if($q->correct_option === $optKey)
                            bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-500 font-bold text-emerald-900 dark:text-emerald-200
                        @elseif($chosen === $optKey && !$isCorrect)
                            bg-rose-50/80 dark:bg-rose-950/40 border-rose-500 font-bold text-rose-900 dark:text-rose-200
                        @else
                            bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300
                        @endif">
                        <div class="flex items-center space-x-2 min-w-0">
                            <span class="w-5 h-5 rounded font-mono font-bold flex items-center justify-center flex-shrink-0 text-[10px]
                                @if($q->correct_option === $optKey)
                                    bg-emerald-600 text-white
                                @elseif($chosen === $optKey && !$isCorrect)
                                    bg-rose-600 text-white
                                @else
                                    bg-white dark:bg-slate-900 text-slate-500 border border-slate-300 dark:border-slate-600
                                @endif">
                                {{ $optKey }}
                            </span>
                            <span class="flex-1 leading-snug">{{ $optVal }}</span>
                        </div>
                        <div>
                            @if($q->correct_option === $optKey)
                                <span class="text-emerald-600 dark:text-emerald-400 font-black text-[10px]">✓ Correct</span>
                            @elseif($chosen === $optKey)
                                <span class="text-rose-600 dark:text-rose-400 font-black text-[10px]">✗ Pick</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($q->explanation)
                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-bold text-slate-800 dark:text-white">Explanation:</span> {{ $q->explanation }}
                </div>
                @endif

                <!-- In-Card Question Navigation for Review Stepper -->
                <div x-show="reviewMode === 'single'" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" 
                            @click="if (currentReviewIndex > 0) currentReviewIndex--" 
                            :disabled="currentReviewIndex === 0" 
                            class="px-3.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-800 transition flex items-center space-x-1">
                        <span>&larr; Previous Question</span>
                    </button>

                    <button type="button" 
                            @click="if (currentReviewIndex < totalReviewQuestions - 1) currentReviewIndex++" 
                            :disabled="currentReviewIndex === totalReviewQuestions - 1" 
                            class="px-4 py-1.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-slate-800 dark:hover:bg-slate-100 transition flex items-center space-x-1">
                        <span>Next Question &rarr;</span>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 3: LEADERBOARD -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'leaderboard'" class="space-y-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 sm:p-4 shadow-2xs">
            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center space-x-2">
                <span>🏆</span>
                <span>Top Arena Performers &bull; {{ $quiz->title }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">High scores ranked by total points and submission speed.</p>

            <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($leaderboard as $rank => $att)
                <div class="py-3 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="w-6 h-6 rounded-full font-black text-xs flex items-center justify-center {{ $rank === 0 ? 'bg-amber-400 text-slate-900' : ($rank === 1 ? 'bg-slate-300 text-slate-900' : ($rank === 2 ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500')) }}">
                            {{ $rank + 1 }}
                        </span>
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $att->student->name }}</span>
                            <span class="text-[10px] text-slate-400">{{ $att->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-slate-900 dark:text-white">{{ $att->score }} pts</span>
                        <span class="text-[10px] text-emerald-600 block font-bold">+{{ $att->xp_earned }} XP</span>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-slate-400">
                    No leaderboard attempts recorded yet. Be the first to conquer this arena!
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: FACULTY QUESTION MANAGEMENT (DPP STYLE) -->
    <!-- ========================================== -->
    @if(Auth::user()->isTeacher() || Auth::user()->isAdmin())
    <div x-show="activeTab === 'manage'" class="space-y-6">
        
        <!-- Action Toolbar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white">Live Quiz Question Bank</h3>
                <p class="text-xs text-slate-500">Add, edit, or import multiple questions in bulk.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('quizzes.quiz.sample-csv', $quiz->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 border border-slate-300 dark:border-slate-700">
                    <span>📄</span> <span>Sample CSV</span>
                </a>

                <a href="{{ route('quizzes.export-csv', $quiz->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition flex items-center space-x-1 border border-slate-300 dark:border-slate-700">
                    <span>📤</span> <span>Export CSV</span>
                </a>

                <button type="button" @click="openCsvModal = true" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                    <span>📥</span> <span>Import CSV</span>
                </button>

                <button type="button" @click="openTextModal = true" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                    <span>📝</span> <span>Bulk Text</span>
                </button>

                <button type="button" @click="openSingleQModal = true" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center space-x-1">
                    <span>+</span> <span>Add Question</span>
                </button>
            </div>
        </div>

        <!-- Questions Listing Table / Cards -->
        <div class="space-y-3">
            @forelse($paginatedQuestions as $idx => $q)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300 flex items-center justify-center">
                            {{ $paginatedQuestions->firstItem() + $idx }}
                        </span>
                        <span class="font-bold text-amber-500">{{ $q->points }} Points</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <form action="{{ route('quizzes.questions.destroy', [$quiz->id, $q->id]) }}" method="POST" onsubmit="return confirm('Delete this question?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-500 hover:text-rose-600 text-xs font-bold">
                                Delete 🗑️
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-relaxed">
                    {{ $q->question }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @foreach(['A' => $q->option_a, 'B' => $q->option_b, 'C' => $q->option_c, 'D' => $q->option_d] as $k => $v)
                    <div class="p-2.5 rounded-xl border flex items-center space-x-2 {{ $q->correct_option === $k ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 font-bold text-emerald-800 dark:text-emerald-200' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                        <span class="w-5 h-5 rounded font-mono font-bold flex items-center justify-center text-[10px] {{ $q->correct_option === $k ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">{{ $k }}</span>
                        <span>{{ $v }}</span>
                    </div>
                    @endforeach
                </div>

                @if($q->explanation)
                <div class="text-[11px] text-slate-500 bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Explanation:</span> {{ $q->explanation }}
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center text-slate-400 text-xs">
                No questions exist yet. Click "+ Add Question" or "Import CSV" above to populate questions!
            </div>
            @endforelse

            <div class="pt-2">
                {{ $paginatedQuestions->links() }}
            </div>
        </div>

        <!-- MODAL 1: IMPORT CSV -->
        <div x-show="openCsvModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openCsvModal = false">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openCsvModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>📥</span> <span>Import Questions via CSV</span>
                    </h3>
                    <button @click="openCsvModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                </div>

                <form action="{{ route('quizzes.import-csv', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select CSV / TSV File</label>
                        <input type="file" name="csv_file" accept=".csv,.tsv,.txt" required class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl text-[11px] text-slate-500 space-y-1">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block">Required Columns:</span>
                        <code>question, option_a, option_b, option_c, option_d, correct_option, explanation, points</code>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <a href="{{ route('quizzes.sample-csv') }}" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            Download Template CSV
                        </a>
                        <div class="flex space-x-2">
                            <button type="button" @click="openCsvModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-xs">Upload &amp; Import</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: BULK TEXT PARSER -->
        <div x-show="openTextModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openTextModal = false">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openTextModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>📝</span> <span>Bulk Text Question Import</span>
                    </h3>
                    <button @click="openTextModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                </div>

                <form action="{{ route('quizzes.import-text', $quiz->id) }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Paste Formatted Questions</label>
                        <textarea name="raw_text" rows="8" required class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white font-mono text-[11px]" placeholder="Q: What is the powerhouse of the cell?
A) Nucleus
B) Mitochondria
C) Ribosome
D) Endoplasmic Reticulum
Ans: B
Exp: Mitochondria generates ATP through cellular respiration.

Q: Which base pairs with Adenine in DNA?
A) Thymine
B) Cytosine
C) Guanine
D) Uracil
Ans: A"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                        <button type="button" @click="openTextModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-xs">Parse &amp; Add Questions</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: ADD SINGLE QUESTION -->
        <div x-show="openSingleQModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="openSingleQModal = false">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-left" @click.away="openSingleQModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center space-x-2">
                        <span>➕</span> <span>Add Single Question</span>
                    </h3>
                    <button @click="openSingleQModal = false" class="text-slate-400 hover:text-slate-200 text-lg">&times;</button>
                </div>

                <form action="{{ route('quizzes.questions.store', $quiz->id) }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Question Prompt</label>
                        <textarea name="question" rows="2" required placeholder="Enter question..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Option A</label>
                            <input type="text" name="option_a" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Option B</label>
                            <input type="text" name="option_b" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Option C</label>
                            <input type="text" name="option_c" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Option D</label>
                            <input type="text" name="option_d" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Correct Option</label>
                            <select name="correct_option" required class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Points</label>
                            <input type="number" name="points" value="10" min="1" max="100" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Explanation (Optional)</label>
                        <input type="text" name="explanation" placeholder="Provide conceptual rationale..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-xs">
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2">
                        <button type="button" @click="openSingleQModal = false" class="px-3 py-1.5 rounded-xl text-slate-500 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-xs">Add Question</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @endif

</div>
@endsection
