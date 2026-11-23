<?php

namespace App\Services;

use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DppManagementService
{
    /**
     * Store or update a Daily Practice Problem (DPP) with questions.
     */
    public function saveDpp(array $data, array $questions, int $creatorId, ?int $id = null): DailyDpp
    {
        return DB::transaction(function () use ($data, $questions, $creatorId, $id) {
            if ($id) {
                $dpp = DailyDpp::findOrFail($id);
                $dpp->update($data);
            } else {
                $data['created_by'] = $creatorId;
                $dpp = DailyDpp::create($data);
            }

            if (! empty($questions)) {
                foreach ($questions as $q) {
                    if (isset($q['id']) && $q['id']) {
                        DppQuestion::where('id', $q['id'])->where('daily_dpp_id', $dpp->id)->update([
                            'question_text' => $q['question_text'],
                            'option_a' => $q['option_a'],
                            'option_b' => $q['option_b'],
                            'option_c' => $q['option_c'],
                            'option_d' => $q['option_d'],
                            'correct_option' => $q['correct_option'],
                            'marks' => $q['marks'] ?? 1,
                            'explanation' => $q['explanation'] ?? null,
                        ]);
                    } else {
                        DppQuestion::create([
                            'daily_dpp_id' => $dpp->id,
                            'question_text' => $q['question_text'],
                            'option_a' => $q['option_a'],
                            'option_b' => $q['option_b'],
                            'option_c' => $q['option_c'],
                            'option_d' => $q['option_d'],
                            'correct_option' => $q['correct_option'],
                            'marks' => $q['marks'] ?? 1,
                            'explanation' => $q['explanation'] ?? null,
                        ]);
                    }
                }
            }

            return $dpp;
        });
    }

    /**
     * Automated grading for student DPP submission.
     */
    public function gradeSubmission(int $dppId, int $studentId, array $answers, int $timeSpentSeconds = 0): DppSubmission
    {
        return DB::transaction(function () use ($dppId, $studentId, $answers, $timeSpentSeconds) {
            $dpp = DailyDpp::with('questions')->findOrFail($dppId);
            $correctCount = 0;
            $totalMarksObtained = 0.0;
            $totalPossibleMarks = (float) $dpp->total_marks;

            $questionMap = $dpp->questions->keyBy('id');

            foreach ($answers as $qId => $selectedOption) {
                $question = $questionMap->get((int) $qId);
                if ($question && strtoupper($selectedOption) === strtoupper($question->correct_option)) {
                    $correctCount++;
                    $totalMarksObtained += (float) ($question->marks ?? 1.0);
                }
            }

            $percentage = $totalPossibleMarks > 0 ? round(($totalMarksObtained / $totalPossibleMarks) * 100, 2) : 0.0;

            $submission = DppSubmission::updateOrCreate(
                [
                    'daily_dpp_id' => $dpp->id,
                    'student_id' => $studentId,
                ],
                [
                    'answers' => json_encode($answers),
                    'score' => $totalMarksObtained,
                    'percentage' => $percentage,
                    'correct_answers' => $correctCount,
                    'total_questions' => $dpp->questions->count(),
                    'time_spent_seconds' => $timeSpentSeconds,
                    'submitted_at' => now(),
                    'status' => 'graded',
                ]
            );

            // Award XP to student (+25 XP for completing DPP)
            $student = User::find($studentId);
            if ($student) {
                $student->recordDailyActivity(25);
            }

            return $submission;
        });
    }
}
