<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\LiveQuiz;
use App\Models\LiveQuizQuestion;
use App\Models\QuizAttempt;
use App\Models\UserBadge;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveQuizController extends Controller
{
    protected function authorizeManageQuiz(?LiveQuiz $quiz = null): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            abort(403, 'Unauthorized. Faculty credentials required to manage quiz arenas.');
        }

        if ($quiz && ! $user->isAdmin() && $quiz->creator_id && (int) $quiz->creator_id !== (int) $user->id) {
            abort(403, 'Unauthorized to modify quizzes created by another faculty member.');
        }
        if ($quiz && $quiz->attempts()->exists()) {
            abort(409, 'This quiz has recorded attempts. Its questions and scoring rules are locked to preserve result integrity. Create a new quiz for revised content.');
        }
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user->isStudent() && ! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Quizzes are not available for this role.');
        }
        $allClasses = AcademicClass::orderBy('grade_level')->get();
        if ($user->isStudent()) {
            $visibleClassIds = collect([$user->current_class_id, $user->batch?->class_id])->filter();
            if (method_exists($user, 'classEnrollments')) {
                $visibleClassIds = $visibleClassIds->merge($user->classEnrollments()->pluck('class_id'));
            }
            $allClasses = $allClasses->whereIn('id', $visibleClassIds->unique());
            $allSubjects = AcademicSubject::whereIn('class_id', $visibleClassIds->unique())->orderBy('name')->get();
        } else {
            $allSubjects = AcademicSubject::orderBy('name')->get();
        }
        $selectedClassId = $request->query('class_id');
        $statusFilter = $request->query('status', 'active');
        $searchTerm = trim((string) $request->query('search', ''));

        $query = LiveQuiz::with(['creator', 'academicClass', 'subject'])->withCount('questions');
        if ($user->isStudent()) {
            $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter();
            if (method_exists($user, 'classEnrollments')) {
                $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id'));
            }
            $query->where('status', 'active')->where(function ($q) use ($classIds) {
                $q->whereNull('class_id')->orWhereIn('class_id', $classIds->unique()->values());
            });
        }

        if ($selectedClassId && $selectedClassId !== 'all') {
            $query->where('class_id', $selectedClassId);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('access_code', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $quizzes = $query->latest()->paginate(9)->withQueryString();

        $myAttempts = QuizAttempt::with('quiz')
            ->where('student_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('quizzes.index', compact('quizzes', 'allClasses', 'allSubjects', 'selectedClassId', 'statusFilter', 'searchTerm', 'myAttempts'));
    }

    public function show(LiveQuiz $quiz, Request $request): View
    {
        $user = Auth::user();
        if (! $user->isStudent() && ! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Quizzes are not available for this role.');
        }
        $quiz->load(['academicClass', 'subject', 'creator']);
        if ($user->isStudent()) {
            if ($quiz->status !== 'active') {
                abort(404);
            }
            if ($quiz->class_id) {
                $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter()->map(fn ($id) => (int) $id);
                if (method_exists($user, 'classEnrollments')) {
                    $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id')->map(fn ($id) => (int) $id));
                }
                if (! $classIds->contains((int) $quiz->class_id)) {
                    abort(403, 'This quiz is not assigned to your class.');
                }
            }
        }

        $previousAttempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->latest()
            ->first();

        // Students receive no answer key or explanation before attempting the quiz.
        $questions = $quiz->questions()->orderBy('id')->get();
        if ($user->isStudent() && ! $previousAttempt) {
            $questions->each(function ($question) {
                $question->setAttribute('correct_option', null);
                $question->setAttribute('explanation', null);
            });
        }

        // Arena Leaderboard for this quiz
        $leaderboard = QuizAttempt::with('student')
            ->where('quiz_id', $quiz->id)
            ->orderBy('score', 'desc')
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();

        // Paginated questions for faculty management table
        $paginatedQuestions = $quiz->questions()->orderBy('id')->paginate(10, ['*'], 'qpage')->withQueryString();

        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allSubjects = AcademicSubject::orderBy('name')->get();

        return view('quizzes.show', compact('quiz', 'questions', 'previousAttempt', 'leaderboard', 'paginatedQuestions', 'allClasses', 'allSubjects'));
    }

    public function submit(Request $request, LiveQuiz $quiz): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isStudent()) {
            abort(403, 'Only students can participate in live quizzes.');
        }
        $quiz->load('questions');
        if ($quiz->class_id) {
            $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter()->map(fn ($id) => (int) $id);
            if (method_exists($user, 'classEnrollments')) {
                $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id')->map(fn ($id) => (int) $id));
            }
            if (! $classIds->contains((int) $quiz->class_id)) {
                abort(403, 'This quiz is not assigned to your class.');
            }
        }
        if ($quiz->status !== 'active') {
            return back()->with('error', 'This live quiz arena is currently not active.');
        }

        $totalQuestions = $quiz->questions->count();
        if ($totalQuestions === 0) {
            return back()->with('error', 'This quiz does not have any questions yet.');
        }

        $validated = $request->validate([
            'answers' => 'nullable|array|max:500',
            'answers.*' => 'nullable|in:A,B,C,D,a,b,c,d',
        ]);
        $submittedAnswers = $validated['answers'] ?? [];
        $score = 0;
        $correctCount = 0;
        $breakdown = [];

        foreach ($quiz->questions as $q) {
            $ans = $submittedAnswers[$q->id] ?? null;
            $isCorrect = (strtoupper(trim((string) $ans)) === strtoupper(trim($q->correct_option)));

            if ($isCorrect) {
                $score += $q->points;
                $correctCount++;
            }

            $breakdown[$q->id] = [
                'chosen' => $ans,
                'correct' => $q->correct_option,
                'is_correct' => $isCorrect,
                'explanation' => $q->explanation,
                'question_text' => $q->question,
                'points' => $q->points,
            ];
        }

        $xpEarned = (int) round(($correctCount / max(1, $totalQuestions)) * $quiz->xp_reward);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $user->id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'xp_earned' => $xpEarned,
            'answers_payload' => $breakdown,
        ]);

        $previousBest = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->where('id', '!=', $attempt->id)
            ->max('xp_earned') ?? 0;

        $deltaXp = max(0, $xpEarned - $previousBest);
        if ($deltaXp > 0) {
            $user->recordDailyActivity($deltaXp);
        } else {
            $user->recordDailyActivity('quiz_submission', 0);
        }

        // Award "Speed Quizzer" badge if perfect score
        if ($correctCount === $totalQuestions && $totalQuestions >= 3) {
            UserBadge::firstOrCreate([
                'user_id' => $user->id,
                'badge_key' => 'speed_quizzer',
            ], [
                'title' => 'Speed Quizzer',
                'icon' => '⚡',
                'category' => 'academics',
                'description' => 'Achieved 100% accuracy in a live competitive quiz sprint!',
                'awarded_at' => now(),
            ]);
        }

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('success', "Live quiz completed! You scored {$score} points and earned +{$deltaXp} XP toward your best result! 🔥");
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManageQuiz();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'class_id' => 'nullable|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'description' => 'nullable|string',
            'time_per_question' => 'required|integer|min:10|max:180',
            'xp_reward' => 'required|integer|min:20|max:1000',
            'questions' => 'nullable|array',
            'questions.*.question' => 'required_with:questions|string',
            'questions.*.option_a' => 'required_with:questions|string',
            'questions.*.option_b' => 'required_with:questions|string',
            'questions.*.option_c' => 'required_with:questions|string',
            'questions.*.option_d' => 'required_with:questions|string',
            'questions.*.correct_option' => 'required_with:questions|in:A,B,C,D,a,b,c,d',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'nullable|integer|min:1',
        ]);

        if (! empty($validated['class_id']) && ! empty($validated['subject_id'])) {
            $class = AcademicClass::findOrFail($validated['class_id']);
            if (! $class->subjects()->whereKey($validated['subject_id'])->exists()) {
                return back()->withErrors(['subject_id' => 'The selected subject is not assigned to this class.'])->withInput();
            }
            $subject = AcademicSubject::findOrFail($validated['subject_id']);
            if (Auth::user()->isTeacher() && (int) $subject->teacher_id !== (int) Auth::id()) {
                abort(403, 'You can only create quizzes for subjects assigned to you.');
            }
        }

        $quiz = LiveQuiz::create([
            'creator_id' => Auth::id(),
            'class_id' => $validated['class_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'access_code' => 'QZ-'.strtoupper(Str::random(5)),
            'time_per_question' => $validated['time_per_question'],
            'xp_reward' => $validated['xp_reward'],
            'status' => 'active',
        ]);

        if (! empty($validated['questions'])) {
            foreach ($validated['questions'] as $qData) {
                if (empty(trim((string) ($qData['question'] ?? '')))) {
                    continue;
                }
                LiveQuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $qData['question'],
                    'option_a' => $qData['option_a'],
                    'option_b' => $qData['option_b'],
                    'option_c' => $qData['option_c'],
                    'option_d' => $qData['option_d'],
                    'correct_option' => strtoupper(trim($qData['correct_option'])),
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => $qData['points'] ?? 10,
                ]);
            }
        }

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('success', "Live quiz '{$quiz->title}' created successfully! Access code: {$quiz->access_code}");
    }

    public function edit(LiveQuiz $quiz): View
    {
        $this->authorizeManageQuiz($quiz);
        $allClasses = AcademicClass::orderBy('grade_level')->get();
        $allSubjects = AcademicSubject::orderBy('name')->get();

        return view('quizzes.edit', compact('quiz', 'allClasses', 'allSubjects'));
    }

    public function update(Request $request, LiveQuiz $quiz): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'class_id' => 'nullable|exists:academic_classes,id',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'description' => 'nullable|string|max:10000',
            'time_per_question' => 'required|integer|min:10|max:180',
            'xp_reward' => 'required|integer|min:20|max:1000',
            'status' => 'required|in:active,draft,archived',
        ]);
        if (! empty($validated['class_id']) && ! empty($validated['subject_id'])) {
            $class = AcademicClass::findOrFail($validated['class_id']);
            if (! $class->subjects()->whereKey($validated['subject_id'])->exists()) {
                return back()->withErrors(['subject_id' => 'The selected subject is not assigned to this class.'])->withInput();
            }
            $subject = AcademicSubject::findOrFail($validated['subject_id']);
            if (Auth::user()->isTeacher() && (int) $subject->teacher_id !== (int) Auth::id()) {
                abort(403, 'You can only manage quizzes for subjects assigned to you.');
            }
        }

        $quiz->update($validated);

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('success', "Live quiz '{$quiz->title}' updated successfully!");
    }

    public function destroy(LiveQuiz $quiz): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        $title = $quiz->title;
        $quiz->questions()->delete();
        $quiz->attempts()->delete();
        $quiz->delete();

        return redirect()->route('quizzes.index')
            ->with('success', "Live quiz '{$title}' and its associated records were removed.");
    }

    public function storeQuestion(Request $request, LiveQuiz $quiz): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        $validated = $request->validate([
            'question' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_option' => 'required|in:A,B,C,D,a,b,c,d',
            'explanation' => 'nullable|string',
            'points' => 'nullable|integer|min:1|max:100',
        ]);

        LiveQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => $validated['question'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'correct_option' => strtoupper(trim($validated['correct_option'])),
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'] ?? 10,
        ]);

        return redirect()->route('quizzes.show', ['quiz' => $quiz->id, 'view' => 'manage'])
            ->with('success', 'New question added successfully to this live quiz!');
    }

    public function updateQuestion(Request $request, LiveQuiz $quiz, LiveQuizQuestion $question): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_option' => 'required|in:A,B,C,D,a,b,c,d',
            'explanation' => 'nullable|string',
            'points' => 'nullable|integer|min:1|max:100',
        ]);

        $question->update([
            'question' => $validated['question'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'correct_option' => strtoupper(trim($validated['correct_option'])),
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'] ?? 10,
        ]);

        return redirect()->route('quizzes.show', ['quiz' => $quiz->id, 'view' => 'manage'])
            ->with('success', 'Question updated successfully!');
    }

    public function destroyQuestion(LiveQuiz $quiz, LiveQuizQuestion $question): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $question->delete();

        return redirect()->route('quizzes.show', ['quiz' => $quiz->id, 'view' => 'manage'])
            ->with('success', 'Question deleted successfully.');
    }

    public function importCsv(Request $request, LiveQuiz $quiz, CsvService $csvService): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        $request->validate([
            'csv_file' => 'required|file|max:5120',
        ]);

        $file = $request->file('csv_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'tsv', 'txt'], true)) {
            return back()->withErrors(['csv_file' => 'Uploaded question file must be a CSV, TSV, or TXT file.']);
        }

        $parsedRows = $csvService->parseCsv($file->getRealPath());
        if (empty($parsedRows)) {
            return back()->withErrors(['csv_file' => 'No readable questions found in uploaded CSV file. Please follow the sample format.']);
        }

        $imported = 0;
        foreach ($parsedRows as $row) {
            $questionText = $row['question'] ?? $row['question_text'] ?? $row['prompt'] ?? $row['text'] ?? $row['q'] ?? null;
            if (empty(trim((string) $questionText))) {
                continue;
            }

            $optA = $row['option_a'] ?? $row['optiona'] ?? $row['a'] ?? $row['opt_a'] ?? '';
            $optB = $row['option_b'] ?? $row['optionb'] ?? $row['b'] ?? $row['opt_b'] ?? '';
            $optC = $row['option_c'] ?? $row['optionc'] ?? $row['c'] ?? $row['opt_c'] ?? '';
            $optD = $row['option_d'] ?? $row['optiond'] ?? $row['d'] ?? $row['opt_d'] ?? '';

            if (trim((string) $optA) === '' || trim((string) $optB) === '' || trim((string) $optC) === '' || trim((string) $optD) === '') {
                continue;
            }

            $rawCorrect = strtoupper(trim((string) ($row['correct_option'] ?? $row['correct'] ?? $row['answer'] ?? $row['ans'] ?? '')));
            if (! in_array($rawCorrect, ['A', 'B', 'C', 'D'], true)) {
                continue;
            }
            $correctOption = $rawCorrect;
            $explanation = $row['explanation'] ?? $row['solution'] ?? $row['sol'] ?? null;
            $points = (int) ($row['points'] ?? $row['marks'] ?? 10);
            if ($points <= 0) {
                $points = 10;
            }

            LiveQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => trim((string) $questionText),
                'option_a' => trim((string) $optA),
                'option_b' => trim((string) $optB),
                'option_c' => trim((string) $optC),
                'option_d' => trim((string) $optD),
                'correct_option' => $correctOption,
                'explanation' => $explanation ? trim((string) $explanation) : null,
                'points' => $points,
            ]);

            $imported++;
        }

        return redirect()->route('quizzes.show', ['quiz' => $quiz->id, 'view' => 'manage'])
            ->with('success', "Successfully imported {$imported} questions into {$quiz->title}!");
    }

    public function importBulkText(Request $request, LiveQuiz $quiz): RedirectResponse
    {
        $this->authorizeManageQuiz($quiz);
        $request->validate([
            'raw_text' => 'required|string|min:15',
        ]);

        $rawText = $request->input('raw_text');
        $lines = preg_split('/\r\n|\r|\n/', $rawText);

        $imported = 0;
        $currentQ = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                if ($currentQ && ! empty($currentQ['question']) && ! empty($currentQ['option_a']) && ! empty($currentQ['option_b']) && ! empty($currentQ['option_c']) && ! empty($currentQ['option_d']) && ! empty($currentQ['correct_option'])) {
                    LiveQuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question' => $currentQ['question'],
                        'option_a' => $currentQ['option_a'],
                        'option_b' => $currentQ['option_b'],
                        'option_c' => $currentQ['option_c'],
                        'option_d' => $currentQ['option_d'],
                        'correct_option' => $currentQ['correct_option'],
                        'explanation' => $currentQ['explanation'] ?? null,
                        'points' => $currentQ['points'] ?? 10,
                    ]);
                    $imported++;
                    $currentQ = null;
                }

                continue;
            }

            // Question prompt match
            if (preg_match('/^(?:Q(?:\:|\.|\s*)|\d+[\.\)]\s*)(.*)$/i', $trimmed, $m) && (! $currentQ || ! empty($currentQ['option_a']))) {
                if ($currentQ && ! empty($currentQ['question']) && ! empty($currentQ['option_a']) && ! empty($currentQ['option_b']) && ! empty($currentQ['option_c']) && ! empty($currentQ['option_d']) && ! empty($currentQ['correct_option'])) {
                    LiveQuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question' => $currentQ['question'],
                        'option_a' => $currentQ['option_a'],
                        'option_b' => $currentQ['option_b'],
                        'option_c' => $currentQ['option_c'],
                        'option_d' => $currentQ['option_d'],
                        'correct_option' => $currentQ['correct_option'],
                        'explanation' => $currentQ['explanation'] ?? null,
                        'points' => $currentQ['points'] ?? 10,
                    ]);
                    $imported++;
                }
                $currentQ = [
                    'question' => trim($m[1]),
                    'option_a' => '',
                    'option_b' => '',
                    'option_c' => '',
                    'option_d' => '',
                    'correct_option' => null,
                    'explanation' => null,
                    'points' => 10,
                ];

                continue;
            }

            // Option A
            if (preg_match('/^(?:A[\)\.\:]\s*|\(A\)\s*)(.*)$/i', $trimmed, $m) && $currentQ) {
                $currentQ['option_a'] = trim($m[1]);

                continue;
            }

            // Option B
            if (preg_match('/^(?:B[\)\.\:]\s*|\(B\)\s*)(.*)$/i', $trimmed, $m) && $currentQ) {
                $currentQ['option_b'] = trim($m[1]);

                continue;
            }

            // Option C
            if (preg_match('/^(?:C[\)\.\:]\s*|\(C\)\s*)(.*)$/i', $trimmed, $m) && $currentQ) {
                $currentQ['option_c'] = trim($m[1]);

                continue;
            }

            // Option D
            if (preg_match('/^(?:D[\)\.\:]\s*|\(D\)\s*)(.*)$/i', $trimmed, $m) && $currentQ) {
                $currentQ['option_d'] = trim($m[1]);

                continue;
            }

            // Correct Answer / Key
            if (preg_match('/^(?:Ans(?:wer)?[\:\.]?\s*|Key[\:\.]?\s*)([A-Da-d])/i', $trimmed, $m) && $currentQ) {
                $currentQ['correct_option'] = strtoupper(trim($m[1]));

                continue;
            }

            // Explanation
            if (preg_match('/^(?:Exp(?:lanation)?[\:\.]?\s*|Sol(?:ution)?[\:\.]?\s*)(.*)$/i', $trimmed, $m) && $currentQ) {
                $currentQ['explanation'] = trim($m[1]);

                continue;
            }

            // Points / Marks
            if (preg_match('/^(?:Points|Marks)[\:\.]?\s*(\d+)/i', $trimmed, $m) && $currentQ) {
                $currentQ['points'] = (int) $m[1];

                continue;
            }
        }

        // Save last pending question
        if ($currentQ && ! empty($currentQ['question']) && ! empty($currentQ['option_a']) && ! empty($currentQ['option_b']) && ! empty($currentQ['option_c']) && ! empty($currentQ['option_d']) && ! empty($currentQ['correct_option'])) {
            LiveQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $currentQ['question'],
                'option_a' => $currentQ['option_a'],
                'option_b' => $currentQ['option_b'],
                'option_c' => $currentQ['option_c'],
                'option_d' => $currentQ['option_d'],
                'correct_option' => $currentQ['correct_option'],
                'explanation' => $currentQ['explanation'] ?? null,
                'points' => $currentQ['points'] ?? 10,
            ]);
            $imported++;
        }

        if ($imported === 0) {
            return back()->withErrors(['raw_text' => 'Could not detect questions formatted with Q: and Options A, B, C, D. Please check format.']);
        }

        return redirect()->route('quizzes.show', ['quiz' => $quiz->id, 'view' => 'manage'])
            ->with('success', "Successfully parsed and imported {$imported} questions from raw text!");
    }

    public function sampleCsv(?LiveQuiz $quiz = null): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="live_quiz_sample_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'question',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'correct_option',
                'explanation',
                'points',
            ]);

            fputcsv($handle, [
                'Which enzyme is primarily responsible for unzipping the DNA double helix during replication?',
                'DNA Helicase',
                'DNA Ligase',
                'RNA Primase',
                'Topoisomerase',
                'A',
                'Helicase breaks the hydrogen bonds between purines and pyrimidines to expose single strands.',
                '10',
            ]);

            fputcsv($handle, [
                'What is the standard unit of electric conductance in SI units?',
                'Ohm',
                'Siemens',
                'Henry',
                'Farad',
                'B',
                'Siemens (S) is the SI derived unit of electric conductance and admittance, equivalent to inverse Ohm.',
                '15',
            ]);

            fputcsv($handle, [
                'During competitive inhibition, how does the Km and Vmax change?',
                'Km increases, Vmax remains unchanged',
                'Km decreases, Vmax decreases',
                'Both Km and Vmax decrease',
                'Km remains unchanged, Vmax decreases',
                'A',
                'Competitive inhibitors bind to active sites, increasing Km while Vmax can still be reached at high substrate concentration.',
                '20',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    public function exportCsv(LiveQuiz $quiz): StreamedResponse
    {
        $filename = 'quiz_'.Str::slug($quiz->title).'_questions.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($quiz) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'question',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'correct_option',
                'explanation',
                'points',
            ]);

            foreach ($quiz->questions()->orderBy('id')->get() as $q) {
                fputcsv($handle, [
                    $q->question,
                    $q->option_a,
                    $q->option_b,
                    $q->option_c,
                    $q->option_d,
                    $q->correct_option,
                    $q->explanation,
                    $q->points,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
