<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\QuestionBank;
use App\Services\CsvService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DppController extends Controller
{
    protected function authorizeManageDpp(DailyDpp $dpp, $user): void
    {
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            abort(403, 'Only teachers and administrators can manage assessments.');
        }
        if (! $user->isAdmin() && (int) $dpp->teacher_id !== (int) $user->id) {
            abort(403, 'You can only manage assessments you created.');
        }
    }

    protected function isWithinSubmissionWindow(DailyDpp $dpp): bool
    {
        if (! $dpp->assigned_date) {
            return false;
        }
        $assignedAt = $dpp->assigned_date->copy()->startOfDay();
        if (now()->lt($assignedAt)) {
            return false;
        }
        try {
            $deadline = $dpp->assigned_date->copy()->setTimeFromTimeString($dpp->due_time ?: '23:59');
        } catch (\Throwable $exception) {
            $deadline = $dpp->assigned_date->copy()->endOfDay();
        }

        return now()->lte($deadline);
    }

    protected function authorizeStudentClassAccess(DailyDpp $dpp, $user): void
    {
        if (! $user || ! $user->isStudent()) {
            return;
        }
        $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter()->map(fn ($id) => (int) $id);
        if (method_exists($user, 'classEnrollments')) {
            $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id')->map(fn ($id) => (int) $id));
        }
        if (! $classIds->contains((int) $dpp->class_id)) {
            abort(403, 'This practice assessment is not assigned to your enrolled class.');
        }
    }

    /**
     * DPP & Timed Tests Index Feed (Role Tailored).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user->isStudent() && ! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Practice assessments are not available for this role.');
        }
        $user->recordDailyActivity('visit');

        $allClasses = AcademicClass::with(['subjects.topics'])->orderBy('grade_level')->get();
        if ($user->isStudent()) {
            $visibleClassIds = collect([$user->current_class_id, $user->batch?->class_id])->filter();
            if (method_exists($user, 'classEnrollments')) {
                $visibleClassIds = $visibleClassIds->merge($user->classEnrollments()->pluck('class_id'));
            }
            $allClasses = $allClasses->whereIn('id', $visibleClassIds->unique());
        }

        $rawClassId = $request->query('class_id');
        $selectedClass = null;
        $isAllClasses = ($rawClassId === 'all');

        if ($isAllClasses) {
            $selectedClass = null;
        } elseif ($rawClassId !== null && $rawClassId !== '') {
            $numericId = (int) $rawClassId;
            $selectedClass = $allClasses->firstWhere('id', $numericId)
                ?? $allClasses->firstWhere('grade_level', $numericId);
        } else {
            // Default selection: if student, pick their enrolled class
            if ($user->isStudent()) {
                if ($user->batch && $user->batch->academicClass) {
                    $selectedClass = $user->batch->academicClass;
                } elseif ($user->classEnrollments()->exists()) {
                    $firstEnroll = $user->classEnrollments()->with('academicClass')->first();
                    $selectedClass = $firstEnroll?->academicClass;
                }
            }
            if (! $selectedClass && ! $user->isStudent()) {
                $classWithDpps = DailyDpp::first()?->academicClass;
                $selectedClass = $classWithDpps ?? $allClasses->first();
            }
        }

        $selectedGrade = $selectedClass ? $selectedClass->grade_level : null;
        $subjects = $selectedClass ? $selectedClass->subjects()->orderBy('name')->get() : AcademicSubject::orderBy('name')->get();
        if ($user->isStudent() && ! $selectedClass) {
            $subjects = AcademicSubject::whereIn('class_id', $visibleClassIds->unique())->orderBy('name')->get();
        }
        $selectedSubjectId = $request->query('subject_id');
        $filterType = $request->query('type', 'all'); // all, dpp, test, bank

        // Validate selectedSubjectId against selectedClass:
        // If user changed class and subject belonged to previous class, automatically reset it to avoid 0-result mismatch!
        if ($selectedClass && $selectedSubjectId) {
            if (! $subjects->contains('id', (int) $selectedSubjectId)) {
                $selectedSubjectId = null;
            }
        }

        $query = DailyDpp::with(['academicClass', 'subject', 'topic', 'teacher:id,name'])
            ->withCount(['submissions', 'questions']);

        if ($user->isStudent()) {
            $query->whereDate('assigned_date', '<=', now()->toDateString());
            $studentClassIds = collect([$user->current_class_id, $user->batch?->class_id])->filter();
            if (method_exists($user, 'classEnrollments')) {
                $studentClassIds = $studentClassIds->merge($user->classEnrollments()->pluck('class_id'));
            }
            $studentClassIds = $studentClassIds->unique()->values();
            $query->whereIn('class_id', $studentClassIds);
            if ($selectedClass && ! $studentClassIds->contains($selectedClass->id)) {
                abort(403, 'You cannot view assessments for another class.');
            }
        } elseif ($selectedClass) {
            $query->where('class_id', $selectedClass->id);
        }

        if ($selectedSubjectId) {
            $query->where('subject_id', $selectedSubjectId);
        }

        if (in_array($filterType, ['dpp', 'test', 'rapid_fire'])) {
            $query->where('type', $filterType);
        }

        $dpps = $query->latest('assigned_date')->latest('id')->paginate(12)->withQueryString();

        // If student, collect their submission map
        $mySubmissions = [];
        if ($user->isStudent()) {
            $mySubmissions = DppSubmission::where('student_id', $user->id)
                ->get()
                ->keyBy('dpp_id');
        }

        // Question Bank for this class & subject
        $questionBank = $user->isStudent() ? collect() : QuestionBank::with(['subject', 'topic'])
            ->when($selectedClass, fn ($q) => $q->where('class_id', $selectedClass->id))
            ->latest()->limit(100)->get();

        // Stats are scoped to the student's enrolled classes; staff retain academy-wide totals.
        $statsQuery = DailyDpp::query();
        if ($user->isStudent()) {
            $statsQuery->whereIn('class_id', $visibleClassIds->unique());
        }
        $todayDppCount = (clone $statsQuery)->where('assigned_date', now()->toDateString())->count();
        $testCount = (clone $statsQuery)->where('type', 'test')->count();
        $rapidFireCount = (clone $statsQuery)->where('type', 'rapid_fire')->count();
        $bankCount = $user->isStudent() ? 0 : QuestionBank::count();
        $solvedCount = $user->isStudent() ? count($mySubmissions) : DppSubmission::count();

        return view('dpp.index', compact(
            'dpps',
            'allClasses',
            'selectedClass',
            'selectedGrade',
            'subjects',
            'selectedSubjectId',
            'mySubmissions',
            'questionBank',
            'filterType',
            'todayDppCount',
            'testCount',
            'rapidFireCount',
            'bankCount',
            'solvedCount'
        ));
    }

    /**
     * View and solve / review a specific Daily DPP or Timed Test (One Question at a Time).
     * Supports ?resolve=1 to re-attempt and resolve the DPP anew.
     */
    public function show(Request $request, int $id): View
    {
        $dpp = DailyDpp::with(['academicClass', 'subject', 'topic', 'teacher:id,name', 'questions'])
            ->withCount('submissions')->findOrFail($id);

        $user = Auth::user();
        if (! $user->isStudent() && ! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Practice assessments are not available for this role.');
        }
        $this->authorizeStudentClassAccess($dpp, $user);
        $isResolving = $request->boolean('resolve') || $request->query('resolve') == '1';

        $submission = ($user->isStudent() && ! $isResolving)
            ? DppSubmission::where('dpp_id', $dpp->id)->where('student_id', Auth::id())->first()
            : null;

        $previousSubmission = Auth::user()->isStudent()
            ? DppSubmission::where('dpp_id', $dpp->id)->where('student_id', Auth::id())->first()
            : null;

        // Do not expose the answer key or explanations before the student's attempt.
        if ($user->isStudent() && ! $submission) {
            $dpp->setRelation('questions', $dpp->questions->map(function ($question) {
                $question->setAttribute('correct_option', null);
                $question->setAttribute('explanation', null);

                return $question;
            }));
        }
        $submissions = $user->isStudent() ? collect() : $dpp->submissions()->with('student:id,name,student_id_number')->latest('id')->paginate(15, ['*'], 'submissions_page')->withQueryString();

        return view('dpp.show', compact('dpp', 'submission', 'isResolving', 'previousSubmission', 'submissions'));
    }

    /**
     * Student submits answers for automated instant same-day checking.
     */
    public function submit(Request $request, int $id): RedirectResponse
    {
        $dpp = DailyDpp::with('questions')->findOrFail($id);
        $user = Auth::user();

        if (! $user->isStudent()) {
            abort(403, 'Only students can submit practice problems or tests.');
        }
        $this->authorizeStudentClassAccess($dpp, $user);
        if (! $this->isWithinSubmissionWindow($dpp)) {
            return back()->with('error', 'The assessment is not currently open for submissions. Check its assigned date and deadline.');
        }
        if ($dpp->questions->isEmpty()) {
            return back()->with('error', 'This assessment has no questions and cannot be submitted.');
        }
        $existingSubmission = DppSubmission::where('dpp_id', $dpp->id)->where('student_id', $user->id)->first();
        if ($existingSubmission && ! $request->boolean('resolve')) {
            return redirect()->route('dpp.show', $dpp->id)->with('error', 'An attempt has already been recorded. Use Resolve DPP to make a revision attempt.');
        }
        $validated = $request->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|in:A,B,C,D',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        $score = 0;
        $maxScore = 0;
        $evaluatedAnswers = [];
        $answers = $validated['answers'] ?? [];
        $correctCount = 0;
        $incorrectCount = 0;
        $unattemptedCount = 0;
        $negativeMarksPerQuestion = (float) ($dpp->negative_marks ?? 0.0);

        foreach ($dpp->questions as $question) {
            $qNum = (string) $question->question_number;
            $studentChoice = $answers[$qNum] ?? null;
            $questionMarks = $question->marks;
            $maxScore += $questionMarks;

            if (empty($studentChoice)) {
                $unattemptedCount++;
                $isCorrect = false;
                $earned = 0;
            } elseif ($studentChoice === $question->correct_option) {
                $correctCount++;
                $isCorrect = true;
                $earned = $questionMarks;
                $score += $questionMarks;
            } else {
                $incorrectCount++;
                $isCorrect = false;
                $earned = -$negativeMarksPerQuestion;
                $score -= $negativeMarksPerQuestion;
            }

            $evaluatedAnswers[$qNum] = [
                'chosen' => $studentChoice,
                'correct' => $question->correct_option,
                'is_correct' => $isCorrect,
                'marks' => $earned,
            ];
        }

        $finalScore = max(0, $score);
        $negativeDeduction = round($incorrectCount * $negativeMarksPerQuestion, 2);
        $percentage = $maxScore > 0 ? round(($finalScore / $maxScore) * 100, 1) : 0;
        $isPassed = ($percentage >= ($dpp->pass_percentage ?? 40));

        // Tests award +50 XP, DPPs award +30 XP
        $xpEarned = $dpp->isTest() ? 50 : 30;

        $submission = DppSubmission::updateOrCreate(
            ['dpp_id' => $dpp->id, 'student_id' => $user->id],
            [
                'dpp_id' => $dpp->id,
                'student_id' => $user->id,
                'answers' => $evaluatedAnswers,
                'score' => $finalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'xp_earned' => $xpEarned,
                'time_taken_seconds' => $validated['time_taken_seconds'] ?? null,
                'is_passed' => $isPassed,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrectCount,
                'unattempted_count' => $unattemptedCount,
                'negative_deduction' => $negativeDeduction,
                'checked_at' => now(),
                'status' => 'evaluated',
            ]
        );

        // Update real-time rank and percentile across all submissions for this DPP/Test
        $allSubmissions = DppSubmission::where('dpp_id', $dpp->id)->orderByDesc('score')->get();
        $totalSubmissions = $allSubmissions->count();
        foreach ($allSubmissions as $index => $sub) {
            $rank = $index + 1;
            $countBelow = $allSubmissions->where('score', '<', $sub->score)->count();
            $percentile = $totalSubmissions > 1 ? round(($countBelow / ($totalSubmissions - 1)) * 100, 1) : 100.0;
            $sub->update(['rank' => $rank, 'percentile' => $percentile]);
        }

        // XP is awarded only for the first attempt; revision attempts update the result without farming XP.
        $xpAwarded = $existingSubmission ? 0 : $xpEarned;
        if ($xpAwarded > 0) {
            $user->recordDailyActivity($xpAwarded);
        }

        // Send instant notification
        $moduleTitle = $dpp->isTest() ? 'Timed Examination' : 'Daily DPP';
        NotificationService::notifyUser(
            $user->id,
            'dpp',
            "{$moduleTitle} Evaluation: {$dpp->dpp_number}",
            "Your submission was evaluated instantly! Score: {$finalScore}/{$maxScore} ({$percentage}%). ".($isPassed ? 'Passed ✅' : 'Needs Revision ⚠️')." +{$xpAwarded} XP awarded.",
            route('dpp.show', $dpp->id),
            '🎯'
        );

        return redirect()->route('dpp.show', $dpp->id)
            ->with('success', "{$moduleTitle} checked instantly! You scored {$finalScore}/{$maxScore} ({$percentage}%) and earned +{$xpAwarded} Academic XP.");
    }

    /**
     * Download a ready-to-use Sample CSV Template for DPP questions.
     */
    public function sampleCsv(CsvService $csvService): StreamedResponse
    {
        $headers = [
            'Question',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Option',
            'Explanation',
            'Marks',
        ];

        $sampleData = [
            [
                'What is the SI unit of electric potential difference?',
                'Ampere',
                'Volt',
                'Ohm',
                'Watt',
                'B',
                'The SI unit of electric potential difference is the Volt (V), named after Alessandro Volta.',
                '4',
            ],
            [
                'Which organelle is known as the powerhouse of the cell?',
                'Ribosome',
                'Golgi apparatus',
                'Mitochondria',
                'Lysosome',
                'C',
                'Mitochondria generate most of the chemical energy needed to power the cell\'s biochemical reactions (ATP).',
                '4',
            ],
            [
                'If f(x) = 3x^2 - 5x + 2, what is the value of f\'(2)?',
                '7',
                '5',
                '12',
                '9',
                'A',
                'The derivative f\'(x) = 6x - 5. Evaluating at x = 2 gives f\'(2) = 6(2) - 5 = 12 - 5 = 7.',
                '4',
            ],
            [
                'What is the oxidation state of Manganese in KMnO4?',
                '+2',
                '+4',
                '+6',
                '+7',
                'D',
                'In KMnO4, K is +1 and oxygen is -2. Total is 0: +1 + Mn + 4(-2) = 0 => Mn = +7.',
                '4',
            ],
            [
                'Light year is a unit of which physical quantity?',
                'Time',
                'Distance',
                'Light intensity',
                'Mass',
                'B',
                'A light-year is the distance that light travels in a vacuum in one Julian year (approx. 9.46 trillion km).',
                '4',
            ],
        ];

        return $csvService->downloadCsv('primearc_dpp_sample_template.csv', $headers, $sampleData);
    }

    /**
     * Export an existing DPP or Timed Test questions to CSV.
     */
    public function exportCsv(int $id, CsvService $csvService): StreamedResponse
    {
        $dpp = DailyDpp::with(['questions' => fn ($q) => $q->orderBy('question_number')])->findOrFail($id);
        $this->authorizeManageDpp($dpp, Auth::user());

        $headers = [
            'Question Number',
            'Question',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Option',
            'Explanation',
            'Marks',
        ];

        $data = [];
        foreach ($dpp->questions as $q) {
            $data[] = [
                $q->question_number,
                $q->question_text,
                $q->option_a,
                $q->option_b,
                $q->option_c,
                $q->option_d,
                $q->correct_option,
                $q->explanation ?? '',
                $q->marks,
            ];
        }

        $safeNum = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dpp->dpp_number);

        return $csvService->downloadCsv("primearc_{$safeNum}_questions.csv", $headers, $data);
    }

    /**
     * Teacher creates new Daily DPP or Formal Timed Test.
     * Supports both manual entry and bulk CSV / Excel file upload.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'topic_id' => 'nullable|exists:curriculum_topics,id',
            'type' => 'required|in:dpp,test',
            'title' => 'required|string|max:255',
            'assigned_date' => 'required|date',
            'difficulty' => 'required|in:standard,advanced,olympiad',
            'duration_minutes' => 'required|integer|min:0|max:300',
            'pass_percentage' => 'nullable|integer|min:1|max:100',
            'negative_marks' => 'nullable|numeric|min:0|max:10',
            'save_to_bank' => 'nullable|boolean',
            'csv_file' => 'nullable|file|max:5120',
            'questions' => 'nullable|array',
            'questions.*.text' => 'nullable|string',
            'questions.*.option_a' => 'required_with:questions.*.text|string|max:2000',
            'questions.*.option_b' => 'required_with:questions.*.text|string|max:2000',
            'questions.*.option_c' => 'required_with:questions.*.text|string|max:2000',
            'questions.*.option_d' => 'required_with:questions.*.text|string|max:2000',
            'questions.*.correct' => 'required_with:questions.*.text|in:A,B,C,D,a,b,c,d',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.marks' => 'nullable|integer|min:1',
        ]);

        $class = AcademicClass::findOrFail($validated['class_id']);
        if (! $class->subjects()->whereKey($validated['subject_id'])->exists()) {
            return back()->withErrors(['subject_id' => 'The selected subject is not assigned to the selected class.'])->withInput();
        }
        $selectedSubject = AcademicSubject::findOrFail($validated['subject_id']);
        if (Auth::user()->isTeacher() && (int) $selectedSubject->teacher_id !== (int) Auth::id()) {
            abort(403, 'You can only create assessments for subjects assigned to you.');
        }
        if (! empty($validated['topic_id']) && ! CurriculumTopic::whereKey($validated['topic_id'])->whereHas('subject', fn ($q) => $q->whereKey($validated['subject_id']))->exists()) {
            return back()->withErrors(['topic_id' => 'The selected topic does not belong to the selected subject.'])->withInput();
        }
        $csvService = app(CsvService::class);
        $questionsList = [];

        // 1. Process CSV / TSV file upload if attached
        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, ['csv', 'txt', 'tsv'])) {
                return back()->withErrors(['csv_file' => 'Uploaded question file must be a CSV, TSV, or TXT file.'])->withInput();
            }

            $csvQuestions = $this->parseQuestionsFromCsv($file->getRealPath(), $csvService);
            foreach ($csvQuestions as $cq) {
                $questionsList[] = $cq;
            }
        }

        // 2. Process manual question builder inputs (if submitted)
        if (! empty($validated['questions']) && is_array($validated['questions'])) {
            foreach ($validated['questions'] as $q) {
                if (! empty(trim((string) ($q['text'] ?? '')))) {
                    $rawCorrect = strtoupper(trim((string) ($q['correct'] ?? 'A')));
                    $correct = in_array($rawCorrect, ['A', 'B', 'C', 'D']) ? $rawCorrect : 'A';

                    $questionsList[] = [
                        'text' => trim($q['text']),
                        'option_a' => trim($q['option_a'] ?? ''),
                        'option_b' => trim($q['option_b'] ?? ''),
                        'option_c' => trim($q['option_c'] ?? ''),
                        'option_d' => trim($q['option_d'] ?? ''),
                        'correct' => $correct,
                        'explanation' => trim($q['explanation'] ?? ''),
                        'marks' => max(1, (int) ($q['marks'] ?? 4)),
                    ];
                }
            }
        }

        if (empty($questionsList)) {
            return back()->withErrors([
                'questions' => 'Please provide at least one valid question either by filling out the form or uploading a CSV file.',
            ])->withInput();
        }

        $isTest = ($validated['type'] === 'test');

        $itemCount = DailyDpp::where('class_id', $validated['class_id'])
            ->where('type', $validated['type'])
            ->count() + 1;

        $prefix = $isTest ? 'TEST' : 'DPP';
        $dppNum = "{$prefix}-".str_pad($class->grade_level, 2, '0', STR_PAD_LEFT).'-'.str_pad($itemCount, 2, '0', STR_PAD_LEFT);

        $totalMarks = 0;
        foreach ($questionsList as $q) {
            $totalMarks += ($q['marks'] ?? 4);
        }

        $dpp = DailyDpp::create([
            'class_id' => $class->id,
            'subject_id' => $validated['subject_id'],
            'topic_id' => $validated['topic_id'] ?? null,
            'teacher_id' => Auth::id(),
            'dpp_number' => $dppNum,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'assigned_date' => $validated['assigned_date'],
            'difficulty' => $validated['difficulty'],
            'duration_minutes' => (int) $validated['duration_minutes'],
            'is_timed' => ((int) $validated['duration_minutes'] > 0),
            'pass_percentage' => (int) ($validated['pass_percentage'] ?? ($isTest ? 50 : 40)),
            'negative_marks' => (float) ($request->input('negative_marks', 0.0)),
            'total_marks' => $totalMarks,
        ]);

        foreach ($questionsList as $idx => $q) {
            $marks = (int) ($q['marks'] ?? 4);

            DppQuestion::create([
                'dpp_id' => $dpp->id,
                'question_number' => $idx + 1,
                'question_text' => $q['text'],
                'option_a' => $q['option_a'],
                'option_b' => $q['option_b'],
                'option_c' => $q['option_c'],
                'option_d' => $q['option_d'],
                'correct_option' => $q['correct'],
                'explanation' => $q['explanation'],
                'marks' => $marks,
            ]);

            // If teacher wants to save to central Question Bank
            if (! empty($validated['save_to_bank'])) {
                QuestionBank::firstOrCreate(
                    [
                        'class_id' => $class->id,
                        'subject_id' => $validated['subject_id'],
                        'question_text' => $q['text'],
                    ],
                    [
                        'topic_id' => $validated['topic_id'] ?? null,
                        'teacher_id' => Auth::id(),
                        'option_a' => $q['option_a'],
                        'option_b' => $q['option_b'],
                        'option_c' => $q['option_c'],
                        'option_d' => $q['option_d'],
                        'correct_option' => $q['correct'],
                        'explanation' => $q['explanation'],
                        'difficulty' => $validated['difficulty'],
                        'marks' => $marks,
                    ]
                );
            }
        }

        // Notify class students
        $typeLabel = $isTest ? 'Timed Online Test' : 'Daily DPP';
        NotificationService::notifyClass(
            $class->id,
            'dpp',
            "New {$typeLabel}: {$dpp->title}",
            "{$typeLabel} {$dpp->dpp_number} ({$dpp->duration_minutes} mins, ".count($questionsList).' questions) published with instant scoring!',
            route('dpp.show', $dpp->id),
            $isTest ? '⏱️' : '📝'
        );

        return redirect()->route('dpp.show', $dpp->id)
            ->with('success', "{$typeLabel} {$dpp->dpp_number} created successfully with ".count($questionsList).' questions!');
    }

    /**
     * Bulk import questions directly into an existing DPP or into the Question Bank.
     */
    public function bulkImport(Request $request, CsvService $csvService): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|max:5120',
            'target' => 'required|in:dpp,bank',
            'dpp_id' => 'required_if:target,dpp|nullable|exists:daily_dpps,id',
            'class_id' => 'required_if:target,bank|nullable|exists:academic_classes,id',
            'subject_id' => 'required_if:target,bank|nullable|exists:academic_subjects,id',
            'difficulty' => 'nullable|in:standard,advanced,olympiad',
        ]);

        $file = $request->file('csv_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'tsv'])) {
            return back()->withErrors(['csv_file' => 'Uploaded question file must be a CSV, TSV, or TXT file.']);
        }

        $questions = $this->parseQuestionsFromCsv($file->getRealPath(), $csvService);

        if (empty($questions)) {
            return back()->withErrors(['csv_file' => 'No valid questions could be read from the uploaded file. Please ensure columns match the sample template.']);
        }

        if ($request->input('target') === 'dpp') {
            $dpp = DailyDpp::with('questions')->findOrFail($request->input('dpp_id'));
            $this->authorizeManageDpp($dpp, Auth::user());
            if ($dpp->submissions()->exists()) {
                return back()->with('error', 'Questions are locked after student attempts to preserve result integrity.');
            }
            $currentMaxNumber = $dpp->questions->max('question_number') ?? 0;
            $addedMarks = 0;

            foreach ($questions as $idx => $q) {
                $marks = (int) ($q['marks'] ?? 4);
                $addedMarks += $marks;

                DppQuestion::create([
                    'dpp_id' => $dpp->id,
                    'question_number' => $currentMaxNumber + $idx + 1,
                    'question_text' => $q['text'],
                    'option_a' => $q['option_a'],
                    'option_b' => $q['option_b'],
                    'option_c' => $q['option_c'],
                    'option_d' => $q['option_d'],
                    'correct_option' => $q['correct'],
                    'explanation' => $q['explanation'],
                    'marks' => $marks,
                ]);
            }

            $dpp->increment('total_marks', $addedMarks);

            return redirect()->route('dpp.show', $dpp->id)
                ->with('success', count($questions)." questions successfully appended to {$dpp->dpp_number} from file!");
        } else {
            // Target is Question Bank
            $classId = $request->input('class_id');
            $subjectId = $request->input('subject_id');
            $difficulty = $request->input('difficulty', 'standard');
            $class = AcademicClass::findOrFail($classId);
            if (! $class->subjects()->whereKey($subjectId)->exists()) {
                return back()->withErrors(['subject_id' => 'The selected subject is not assigned to this class.']);
            }
            $subject = AcademicSubject::findOrFail($subjectId);
            if (Auth::user()->isTeacher() && (int) $subject->teacher_id !== (int) Auth::id()) {
                abort(403, 'You can only import questions for subjects assigned to you.');
            }
            $importedCount = 0;

            foreach ($questions as $q) {
                QuestionBank::firstOrCreate(
                    [
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'question_text' => $q['text'],
                    ],
                    [
                        'teacher_id' => Auth::id(),
                        'option_a' => $q['option_a'],
                        'option_b' => $q['option_b'],
                        'option_c' => $q['option_c'],
                        'option_d' => $q['option_d'],
                        'correct_option' => $q['correct'],
                        'explanation' => $q['explanation'],
                        'difficulty' => $difficulty,
                        'marks' => (int) ($q['marks'] ?? 4),
                    ]
                );
                $importedCount++;
            }

            return redirect()->route('dpp.index')
                ->with('success', "{$importedCount} questions successfully cataloged into Question Bank from file!");
        }
    }

    /**
     * Parse and normalize questions from an uploaded CSV, TSV, or TXT file.
     * Supports flexible header naming (question/text/q, option_a/a, correct/answer, explanation/solution, marks).
     */
    protected function parseQuestionsFromCsv(string $filePath, CsvService $csvService): array
    {
        $rows = $csvService->parseCsv($filePath);
        $questions = [];

        foreach ($rows as $row) {
            // Flexible question text discovery
            $text = $row['question'] ?? $row['question_text'] ?? $row['text'] ?? $row['prompt'] ?? $row['q'] ?? null;
            if (empty(trim((string) $text))) {
                continue;
            }

            // Options discovery
            $optA = $row['option_a'] ?? $row['optiona'] ?? $row['a'] ?? $row['opt_a'] ?? $row['option_1'] ?? '';
            $optB = $row['option_b'] ?? $row['optionb'] ?? $row['b'] ?? $row['opt_b'] ?? $row['option_2'] ?? '';
            $optC = $row['option_c'] ?? $row['optionc'] ?? $row['c'] ?? $row['opt_c'] ?? $row['option_3'] ?? '';
            $optD = $row['option_d'] ?? $row['optiond'] ?? $row['d'] ?? $row['opt_d'] ?? $row['option_4'] ?? '';

            if (trim((string) $optA) === '' || trim((string) $optB) === '' || trim((string) $optC) === '' || trim((string) $optD) === '') {
                continue;
            }

            $rawCorrect = strtoupper(trim((string) ($row['correct_option'] ?? $row['correct'] ?? $row['answer'] ?? $row['ans'] ?? '')));
            $correctMap = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D'];
            $correct = $correctMap[$rawCorrect] ?? $rawCorrect;
            if (! in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                continue;
            }

            $explanation = $row['explanation'] ?? $row['solution'] ?? $row['sol'] ?? $row['exp'] ?? '';
            $marks = (int) ($row['marks'] ?? $row['mark'] ?? 4);
            if ($marks <= 0) {
                $marks = 4;
            }

            $questions[] = [
                'text' => trim((string) $text),
                'option_a' => trim((string) $optA),
                'option_b' => trim((string) $optB),
                'option_c' => trim((string) $optC),
                'option_d' => trim((string) $optD),
                'correct' => $correct,
                'explanation' => trim((string) $explanation),
                'marks' => $marks,
            ];
        }

        return $questions;
    }

    /**
     * Store question directly into Question Bank.
     */
    public function storeQuestionBank(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'topic_id' => 'nullable|exists:curriculum_topics,id',
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_option' => 'required|in:A,B,C,D',
            'explanation' => 'required|string',
            'difficulty' => 'required|in:standard,advanced,olympiad',
            'marks' => 'required|integer|min:1|max:20',
            'tags' => 'nullable|string',
        ]);

        $class = AcademicClass::findOrFail($validated['class_id']);
        if (! $class->subjects()->whereKey($validated['subject_id'])->exists()) {
            return back()->withErrors(['subject_id' => 'The selected subject is not assigned to this class.'])->withInput();
        }
        $selectedSubject = AcademicSubject::findOrFail($validated['subject_id']);
        if (Auth::user()->isTeacher() && (int) $selectedSubject->teacher_id !== (int) Auth::id()) {
            abort(403, 'You can only add questions for subjects assigned to you.');
        }
        if (! empty($validated['topic_id']) && ! CurriculumTopic::whereKey($validated['topic_id'])->where('subject_id', $validated['subject_id'])->exists()) {
            return back()->withErrors(['topic_id' => 'The selected topic does not belong to this subject.'])->withInput();
        }
        $validated['teacher_id'] = Auth::id();
        QuestionBank::create($validated);

        return back()->with('success', 'Question successfully cataloged into Institutional Question Bank!');
    }

    /**
     * API to search Question Bank for modal selection.
     */
    public function apiQuestionBank(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            abort(403, 'Question bank access is restricted to faculty.');
        }
        $classId = $request->query('class_id');
        $subjectId = $request->query('subject_id');
        $search = $request->query('q');

        $query = QuestionBank::query();

        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $questions = $query->latest()->limit(50)->get();

        return response()->json([
            'count' => $questions->count(),
            'questions' => $questions,
        ]);
    }

    /**
     * Show form to edit an existing published DPP or Timed Test.
     * Loads existing questions and allows both Single-Question and List view editing.
     */
    public function edit(int $id): View
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Only teachers and administrators can edit DPPs.');
        }

        $dpp = DailyDpp::with([
            'academicClass',
            'subject.topics',
            'topic',
            'teacher:id,name',
            'questions' => fn ($q) => $q->orderBy('question_number'),
            'submissions',
        ])->findOrFail($id);
        $this->authorizeManageDpp($dpp, $user);
        if ($dpp->submissions()->exists()) {
            abort(409, 'This assessment has student attempts and is locked. Duplicate it to create a revised version.');
        }

        $allClasses = AcademicClass::with(['subjects.topics'])->orderBy('grade_level')->get();
        $subjects = $dpp->academicClass ? $dpp->academicClass->subjects()->orderBy('name')->get() : collect();

        // Question bank items for quick import
        $questionBank = QuestionBank::with(['subject', 'topic'])
            ->where('class_id', $dpp->class_id)
            ->latest()
            ->limit(50)
            ->get();

        return view('dpp.edit', compact('dpp', 'allClasses', 'subjects', 'questionBank'));
    }

    /**
     * Update existing published DPP and its questions.
     * Preserves student attempts, submissions, and results without duplicate records.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Only teachers and administrators can edit DPPs.');
        }

        $dpp = DailyDpp::with('questions')->findOrFail($id);
        $this->authorizeManageDpp($dpp, $user);
        if ($dpp->submissions()->exists()) {
            return back()->with('error', 'This assessment already has student attempts. Its questions and scoring rules are locked to preserve result integrity. Duplicate it to create a revised version.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:dpp,test',
            'assigned_date' => 'required|date',
            'difficulty' => 'required|in:standard,advanced,olympiad,easy,medium,hard',
            'duration_minutes' => 'required|integer|min:0|max:300',
            'pass_percentage' => 'nullable|integer|min:1|max:100',
            'negative_marks' => 'nullable|numeric|min:0|max:10',
            'subject_id' => 'nullable|exists:academic_subjects,id',
            'topic_id' => 'nullable|exists:curriculum_topics,id',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.text' => 'required|string',
            'questions.*.option_a' => 'required|string',
            'questions.*.option_b' => 'required|string',
            'questions.*.option_c' => 'nullable|string',
            'questions.*.option_d' => 'nullable|string',
            'questions.*.correct' => 'required|in:A,B,C,D',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.marks' => 'nullable|integer|min:1',
        ]);

        $isTest = ($validated['type'] === 'test');
        $subjectId = $validated['subject_id'] ?? $dpp->subject_id;
        if ($subjectId && ! $dpp->academicClass()->first()->subjects()->whereKey($subjectId)->exists()) {
            return back()->withErrors(['subject_id' => 'The selected subject is not assigned to this class.'])->withInput();
        }
        $updatedSubject = $subjectId ? AcademicSubject::findOrFail($subjectId) : null;
        if (Auth::user()->isTeacher() && $updatedSubject && (int) $updatedSubject->teacher_id !== (int) Auth::id()) {
            abort(403, 'You can only manage assessments for subjects assigned to you.');
        }
        $topicId = $validated['topic_id'] ?? $dpp->topic_id;
        if ($topicId && ! CurriculumTopic::whereKey($topicId)->where('subject_id', $subjectId)->exists()) {
            return back()->withErrors(['topic_id' => 'The selected topic does not belong to this subject.'])->withInput();
        }

        DB::transaction(function () use ($dpp, $validated, $isTest) {
            $dpp->update([
                'title' => $validated['title'],
                'type' => $validated['type'],
                'assigned_date' => $validated['assigned_date'],
                'difficulty' => $validated['difficulty'],
                'duration_minutes' => (int) $validated['duration_minutes'],
                'is_timed' => ((int) $validated['duration_minutes'] > 0),
                'pass_percentage' => (int) ($validated['pass_percentage'] ?? ($isTest ? 50 : 40)),
                'negative_marks' => (float) ($validated['negative_marks'] ?? 0.0),
                'subject_id' => $validated['subject_id'] ?? $dpp->subject_id,
                'topic_id' => $validated['topic_id'] ?? $dpp->topic_id,
            ]);

            $existingQuestions = $dpp->questions->keyBy('id');
            $keptQuestionIds = [];
            $totalMarks = 0;

            foreach ($validated['questions'] as $order => $q) {
                $qText = trim($q['text']);
                if (empty($qText)) {
                    continue;
                }

                $qId = ! empty($q['id']) ? (int) $q['id'] : null;
                $marks = max(1, (int) ($q['marks'] ?? 4));
                $totalMarks += $marks;

                $correct = strtoupper(trim($q['correct'] ?? 'A'));
                if (! in_array($correct, ['A', 'B', 'C', 'D'])) {
                    $correct = 'A';
                }

                $qData = [
                    'question_number' => $order + 1,
                    'question_text' => $qText,
                    'option_a' => trim($q['option_a']),
                    'option_b' => trim($q['option_b']),
                    'option_c' => trim($q['option_c'] ?? ''),
                    'option_d' => trim($q['option_d'] ?? ''),
                    'correct_option' => $correct,
                    'explanation' => trim($q['explanation'] ?? ''),
                    'marks' => $marks,
                ];

                if ($qId && $existingQuestions->has($qId)) {
                    $existing = $existingQuestions->get($qId);
                    $existing->update($qData);
                    $keptQuestionIds[] = $qId;
                } else {
                    $newQ = DppQuestion::create(array_merge($qData, ['dpp_id' => $dpp->id]));
                    $keptQuestionIds[] = $newQ->id;
                }
            }

            // Remove only questions that were explicitly deleted
            if (! empty($keptQuestionIds)) {
                $dpp->questions()->whereNotIn('id', $keptQuestionIds)->delete();
            }

            $dpp->update(['total_marks' => $totalMarks]);
        });

        return redirect()->route('dpp.show', $dpp->id)
            ->with('success', "DPP {$dpp->dpp_number} updated successfully with ".count($validated['questions']).' questions. Existing student attempts and scores remain preserved.');
    }

    /**
     * Delete an individual question from a published DPP.
     */
    public function destroyQuestion(int $dppId, int $questionId): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $dpp = DailyDpp::with('questions')->findOrFail($dppId);
        $this->authorizeManageDpp($dpp, $user);
        if ($dpp->submissions()->exists()) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'Questions are locked after student attempts to preserve result integrity.'], 409);
            }

            return back()->with('error', 'Questions are locked after student attempts to preserve result integrity.');
        }
        if ($dpp->questions()->count() <= 1) {
            if (request()->wantsJson()) {
                return response()->json(['error' => 'A DPP must retain at least one question.'], 422);
            }

            return back()->with('error', 'A DPP must retain at least one question.');
        }

        $question = $dpp->questions()->findOrFail($questionId);
        $marksDeducted = $question->marks;
        $question->delete();

        // Renumber remaining questions and recompute marks
        $remaining = $dpp->questions()->orderBy('question_number')->get();
        foreach ($remaining as $idx => $q) {
            $q->update(['question_number' => $idx + 1]);
        }
        $dpp->decrement('total_marks', $marksDeducted);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'total_questions' => $remaining->count(), 'total_marks' => $dpp->total_marks]);
        }

        return back()->with('success', 'Question deleted successfully and remaining questions re-ordered.');
    }

    /**
     * Delete an entire DPP challenge set with confirmation.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $dpp = DailyDpp::with(['questions', 'submissions'])->findOrFail($id);
        $this->authorizeManageDpp($dpp, $user);
        if ($dpp->submissions()->exists()) {
            return back()->with('error', 'This assessment has recorded student results and cannot be deleted. Archive it instead to preserve the academic record.');
        }
        $title = $dpp->title;
        $courseId = $dpp->course_id ?? $request->input('course_id');

        // Delete questions and submissions
        $dpp->submissions()->delete();
        $dpp->questions()->delete();
        $dpp->delete();

        if ($courseId) {
            return redirect()->route('courses.show', ['id' => $courseId, 'tab' => 'dpp'])
                ->with('success', "Daily Practice Problem set \"{$title}\" has been deleted.");
        }

        return redirect()->route('dpp.index')
            ->with('success', "Daily Practice Problem set \"{$title}\" has been deleted.");
    }
}
