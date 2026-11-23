<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\SchoolExamGradeScale;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamMarkRevision;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolReportCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SchoolExaminationService
{
    /**
     * Store or update an examination term.
     */
    public function saveTerm(array $data, ?int $id = null): SchoolExamTerm
    {
        return DB::transaction(function () use ($data, $id) {
            if ($id) {
                $term = SchoolExamTerm::findOrFail($id);
                $term->update($data);

                return $term;
            }

            return SchoolExamTerm::create($data);
        });
    }

    /**
     * Store or update an examination schedule slot.
     */
    public function saveSchedule(array $data, ?int $id = null): SchoolExamSchedule
    {
        return DB::transaction(function () use ($data, $id) {
            if ($id) {
                $schedule = SchoolExamSchedule::findOrFail($id);
                $schedule->update($data);

                return $schedule;
            }

            return SchoolExamSchedule::create($data);
        });
    }

    /**
     * Auto assign exam schedules for subjects across selected classes.
     */
    public function autoAssignSchedules(int $termId, array $classIds, string $startDate, string $defaultStartTime = '09:00', string $defaultEndTime = '12:00'): int
    {
        $term = SchoolExamTerm::findOrFail($termId);
        $classes = AcademicClass::with('subjects')->whereIn('id', $classIds)->get();
        $assignedCount = 0;
        $currentDate = Carbon::parse($startDate);

        DB::transaction(function () use ($term, $classes, $defaultStartTime, $defaultEndTime, &$assignedCount, &$currentDate) {
            foreach ($classes as $class) {
                foreach ($class->subjects as $subject) {
                    $exists = SchoolExamSchedule::where('term_id', $term->id)
                        ->where('class_id', $class->id)
                        ->where('subject_id', $subject->id)
                        ->exists();

                    if (! $exists) {
                        SchoolExamSchedule::create([
                            'term_id' => $term->id,
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'exam_date' => $currentDate->toDateString(),
                            'start_time' => $defaultStartTime,
                            'end_time' => $defaultEndTime,
                            'full_marks' => 100,
                            'pass_marks' => 35,
                        ]);
                        $assignedCount++;
                        $currentDate->addDay();
                    }
                }
            }
        });

        return $assignedCount;
    }

    /**
     * Record or update marks for a schedule slot.
     */
    public function saveMarks(int $scheduleId, array $marks, int $actorId): int
    {
        $schedule = SchoolExamSchedule::findOrFail($scheduleId);
        $saved = 0;

        DB::transaction(function () use ($schedule, $marks, $actorId, &$saved) {
            foreach ($marks as $entry) {
                $studentId = (int) $entry['student_id'];
                $isAbsent = ! empty($entry['is_absent']);
                $obtained = $isAbsent ? 0.0 : (float) ($entry['marks_obtained'] ?? 0.0);

                $markRecord = SchoolExamMark::where('schedule_id', $schedule->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($markRecord) {
                    if ($markRecord->marks_obtained != $obtained || $markRecord->is_absent != $isAbsent) {
                        SchoolExamMarkRevision::create([
                            'exam_mark_id' => $markRecord->id,
                            'actor_id' => $actorId,
                            'before_marks' => $markRecord->marks_obtained,
                            'after_marks' => $obtained,
                            'reason' => $entry['remarks'] ?? 'Routine mark entry update',
                        ]);
                        $markRecord->update([
                            'marks_obtained' => $obtained,
                            'is_absent' => $isAbsent,
                            'remarks' => $entry['remarks'] ?? null,
                            'recorded_by' => $actorId,
                        ]);
                    }
                } else {
                    SchoolExamMark::create([
                        'schedule_id' => $schedule->id,
                        'student_id' => $studentId,
                        'marks_obtained' => $obtained,
                        'is_absent' => $isAbsent,
                        'remarks' => $entry['remarks'] ?? null,
                        'recorded_by' => $actorId,
                    ]);
                }
                $saved++;
            }
        });

        return $saved;
    }

    /**
     * Bulk import marks from CSV file.
     */
    public function bulkImportMarks(int $scheduleId, UploadedFile $file, int $actorId): int
    {
        $schedule = SchoolExamSchedule::findOrFail($scheduleId);
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        DB::transaction(function () use ($schedule, $handle, $actorId, &$imported) {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) {
                    continue;
                }
                $studentIdNumber = trim($row[0]);
                $student = User::where('student_id_number', $studentIdNumber)
                    ->orWhere('id', (int) $studentIdNumber)
                    ->first();

                if (! $student) {
                    continue;
                }

                $obtained = isset($row[1]) && is_numeric($row[1]) ? (float) $row[1] : 0.0;
                $isAbsent = isset($row[2]) && strtolower(trim($row[2])) === 'absent';

                SchoolExamMark::updateOrCreate(
                    ['schedule_id' => $schedule->id, 'student_id' => $student->id],
                    [
                        'marks_obtained' => $isAbsent ? 0.0 : $obtained,
                        'is_absent' => $isAbsent,
                        'recorded_by' => $actorId,
                    ]
                );
                $imported++;
            }
            fclose($handle);
        });

        return $imported;
    }

    /**
     * Save grading scale criteria.
     */
    public function saveGradeScale(array $data, ?int $id = null): SchoolExamGradeScale
    {
        if ($id) {
            $scale = SchoolExamGradeScale::findOrFail($id);
            $scale->update($data);

            return $scale;
        }

        return SchoolExamGradeScale::create($data);
    }

    /**
     * Generate report cards for students in an examination term.
     */
    public function generateReportCards(int $termId, ?int $classId = null): int
    {
        $term = SchoolExamTerm::findOrFail($termId);
        $schedulesQuery = SchoolExamSchedule::with(['marks', 'subject'])->where('term_id', $term->id);
        if ($classId) {
            $schedulesQuery->where('class_id', $classId);
        }
        $schedules = $schedulesQuery->get();

        $studentIds = $schedules->flatMap->marks->pluck('student_id')->unique();
        $generatedCount = 0;

        DB::transaction(function () use ($term, $schedules, $studentIds, &$generatedCount) {
            $grades = SchoolExamGradeScale::orderByDesc('min_percentage')->get();

            foreach ($studentIds as $studentId) {
                $student = User::find($studentId);
                if (! $student) {
                    continue;
                }

                $totalFullMarks = 0;
                $totalObtainedMarks = 0;
                $subjectMarksData = [];

                foreach ($schedules as $sched) {
                    $mark = $sched->marks->firstWhere('student_id', $studentId);
                    if ($mark) {
                        $full = (float) $sched->full_marks;
                        $obt = $mark->is_absent ? 0.0 : (float) $mark->marks_obtained;
                        $totalFullMarks += $full;
                        $totalObtainedMarks += $obt;

                        $subjectMarksData[] = [
                            'subject_id' => $sched->subject_id,
                            'subject_name' => $sched->subject?->name ?? 'Subject',
                            'full_marks' => $full,
                            'marks_obtained' => $obt,
                            'is_absent' => (bool) $mark->is_absent,
                        ];
                    }
                }

                $percentage = $totalFullMarks > 0 ? round(($totalObtainedMarks / $totalFullMarks) * 100, 2) : 0.0;
                $assignedGrade = 'F';
                $assignedGpa = 0.0;

                foreach ($grades as $g) {
                    if ($percentage >= (float) $g->min_percentage) {
                        $assignedGrade = $g->grade;
                        $assignedGpa = (float) $g->grade_point;
                        break;
                    }
                }

                SchoolReportCard::updateOrCreate(
                    [
                        'term_id' => $term->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'class_id' => $student->current_class_id,
                        'total_marks' => $totalFullMarks,
                        'obtained_marks' => $totalObtainedMarks,
                        'percentage' => $percentage,
                        'grade' => $assignedGrade,
                        'gpa' => $assignedGpa,
                        'subject_breakdown' => json_encode($subjectMarksData),
                        'status' => 'draft',
                    ]
                );
                $generatedCount++;
            }
        });

        return $generatedCount;
    }
}
