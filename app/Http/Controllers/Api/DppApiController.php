<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DppApiController extends Controller
{
    /**
     * List daily practice problems and mock tests.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isStudent() && !$user->isTeacher() && !$user->isAdmin())) return response()->json(['message' => 'Practice assessments are not available for this role.'], 403);
        $query = DailyDpp::with(['academicClass:id,name,grade_level', 'subject:id,name,code', 'teacher:id,name'])
            ->withCount('questions');

        if ($user && $user->isStudent()) {
            $query->whereDate('assigned_date', '<=', now()->toDateString());
            $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter();
            if (method_exists($user, 'classEnrollments')) $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id'));
            $query->whereIn('class_id', $classIds->unique()->values());
            if ($request->filled('class_id') && !$classIds->contains((int) $request->query('class_id'))) {
                return response()->json(['message' => 'You cannot access assessments for another class.'], 403);
            }
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
            if ($request->filled('class_id')) $query->where('class_id', $request->query('class_id'));
        } elseif ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->query('difficulty'));
        }

        $dpps = $query->latest('assigned_date')->paginate(15);

        // Append student submission status if student
        if ($user->isStudent()) {
            $studentId = $user->id;
            $dppIds = $dpps->pluck('id');
            $submissions = DppSubmission::whereIn('dpp_id', $dppIds)
                ->where('student_id', $studentId)
                ->get()
                ->keyBy('dpp_id');

            $dpps->getCollection()->transform(function ($dpp) use ($submissions) {
                $sub = $submissions->get($dpp->id);
                $dpp->user_submission = $sub ? [
                    'id' => $sub->id,
                    'score' => $sub->score,
                    'max_score' => $sub->max_score,
                    'percentage' => $sub->percentage,
                    'is_passed' => (bool)$sub->is_passed,
                    'status' => $sub->status,
                    'submitted_at' => $sub->updated_at,
                ] : null;
                return $dpp;
            });
        }

        return response()->json($dpps);
    }

    /**
     * Show a specific DPP or mock test.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isStudent() && !$user->isTeacher() && !$user->isAdmin())) return response()->json(['message' => 'Practice assessments are not available for this role.'], 403);
        $dpp = DailyDpp::with(['academicClass', 'subject', 'topic', 'teacher:id,name', 'questions'])->findOrFail($id);

        if ($user && $user->isStudent()) {
            $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter()->map(fn ($id) => (int) $id);
            if (method_exists($user, 'classEnrollments')) $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id')->map(fn ($id) => (int) $id));
            if (!$classIds->contains((int) $dpp->class_id)) return response()->json(['message' => 'Unauthorized. This practice assessment is not assigned to your enrolled class.'], 403);
            if (!$dpp->assigned_date || $dpp->assigned_date->toDateString() > now()->toDateString()) return response()->json(['message' => 'This assessment is not available yet.'], 403);
        } elseif ($user->isTeacher()) {
            abort_unless((int) $dpp->teacher_id === (int) $user->id, 403);
        }

        $submission = $dpp->studentSubmission($user->id);

        $hasSubmitted = (bool)$submission;

        // Mask correct options if student hasn't submitted yet
        if ($user->isStudent()) {
            $questions = $dpp->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_number' => $q->question_number,
                    'question_text' => $q->question_text,
                    'option_a' => $q->option_a,
                    'option_b' => $q->option_b,
                    'option_c' => $q->option_c,
                    'option_d' => $q->option_d,
                    'marks' => $q->marks,
                ];
            });
        } else {
            $questions = $dpp->questions;
        }

        return response()->json([
            'dpp' => [
                'id' => $dpp->id,
                'title' => $dpp->title,
                'dpp_number' => $dpp->dpp_number,
                'type' => $dpp->type,
                'difficulty' => $dpp->difficulty,
                'duration_minutes' => $dpp->duration_minutes,
                'is_timed' => (bool)$dpp->is_timed,
                'total_marks' => $dpp->total_marks,
                'pass_percentage' => $dpp->pass_percentage,
                'negative_marks' => $dpp->negative_marks,
                'assigned_date' => $dpp->assigned_date?->toDateString(),
                'due_time' => $dpp->due_time,
                'class' => $dpp->academicClass,
                'subject' => $dpp->subject,
                'teacher' => $dpp->teacher,
                'questions_count' => $dpp->questions->count(),
            ],
            'questions' => $questions,
            'user_submission' => $submission,
        ]);
    }

    /**
     * Submit answers for a DPP or test.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->isStudent()) {
            return response()->json(['message' => 'Only students can submit daily practice problems.'], 403);
        }

        $dpp = DailyDpp::with('questions')->findOrFail($id);

        $classIds = collect([$user->current_class_id, $user->batch?->class_id])->filter()->map(fn ($id) => (int) $id);
        if (method_exists($user, 'classEnrollments')) $classIds = $classIds->merge($user->classEnrollments()->pluck('class_id')->map(fn ($id) => (int) $id));
        if (!$classIds->contains((int) $dpp->class_id)) return response()->json(['message' => 'Unauthorized. You cannot submit assessments for another class.'], 403);
        if (!$dpp->assigned_date || now()->lt($dpp->assigned_date->copy()->startOfDay())) return response()->json(['message' => 'This assessment is not open yet.'], 403);
        try {
            $deadline = $dpp->assigned_date->copy()->setTimeFromTimeString($dpp->due_time ?: '23:59');
        } catch (\Throwable $exception) {
            $deadline = $dpp->assigned_date->copy()->endOfDay();
        }
        if (now()->gt($deadline)) return response()->json(['message' => 'The assessment submission deadline has passed.'], 403);
        if ($dpp->questions->isEmpty()) return response()->json(['message' => 'This assessment has no questions.'], 422);
        if (DppSubmission::where('dpp_id', $dpp->id)->where('student_id', $user->id)->exists()) {
            return response()->json(['message' => 'An attempt has already been recorded. Contact your teacher if a reset is required.'], 409);
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
        $negativeMarksPerQuestion = (float)($dpp->negative_marks ?? 0.0);

        foreach ($dpp->questions as $question) {
            $qNum = (string)$question->question_number;
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
        $xpEarned = $dpp->isTest() ? 50 : 30;

        $submission = DppSubmission::create([
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
        ]);

        // Award the same configured first-attempt XP as the web workflow.
        $user->recordDailyActivity($xpEarned);

        return response()->json([
            'message' => 'DPP submitted and evaluated successfully!',
            'submission' => $submission,
            'xp_earned' => $xpEarned,
            'is_passed' => $isPassed,
            'score' => $finalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
        ], 200);
    }
}
