<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamTerm;
use App\Models\SchoolReportCard;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CompileReportCardsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exams:compile-report-cards 
                            {exam_term_id : ID of the exam term}
                            {--class_id= : Compile report cards for a specific class ID only}
                            {--publish : Automatically mark report cards as published}
                            {--dry-run : Simulate compilation without saving records}';

    /**
     * The console command description.
     */
    protected $description = 'Batch compile academic marks, calculate grades, class ranks, attendance percentages, and generate student report cards';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $termId = (int) $this->argument('exam_term_id');
        $term = SchoolExamTerm::find($termId);

        if (! $term) {
            $this->error("Exam Term ID {$termId} not found.");

            return self::FAILURE;
        }

        $classIdFilter = $this->option('class_id') ? (int) $this->option('class_id') : null;
        $isPublished = (bool) $this->option('publish');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Starting report card compilation for term: {$term->name}".($isDryRun ? ' [DRY-RUN]' : ''));

        $classesQuery = AcademicClass::with(['students']);
        if ($classIdFilter) {
            $classesQuery->where('id', $classIdFilter);
        }

        $classes = $classesQuery->get();
        if ($classes->isEmpty()) {
            $this->warn('No classes found to compile.');

            return self::SUCCESS;
        }

        $totalCardsGenerated = 0;
        $termEndDate = $term->end_date ? min(now()->toDateString(), $term->end_date) : now()->toDateString();

        foreach ($classes as $class) {
            $students = $class->students;
            if ($students->isEmpty()) {
                continue;
            }

            // Calculate total working days for this class and term
            $sessionQuery = AttendanceSession::where('class_id', $class->id)
                ->whereDate('session_date', '<=', $termEndDate);
            if ($term->start_date) {
                $sessionQuery->whereDate('session_date', '>=', $term->start_date);
            }
            $totalSessions = max(1, $sessionQuery->count());

            $studentResults = [];

            foreach ($students as $student) {
                $marks = SchoolExamMark::where('exam_term_id', $term->id)
                    ->where('class_id', $class->id)
                    ->where('student_id', $student->id)
                    ->get();

                $totalMax = (float) $marks->sum('max_marks');
                $totalObtained = (float) $marks->sum('marks_obtained');

                $hasFailedSubject = $marks->contains(function ($m) {
                    return ((float) $m->marks_obtained) < ((float) $m->pass_marks);
                });

                $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0.00;
                $gpa = round($percentage / 10, 2);

                $resultStatus = ($hasFailedSubject || $percentage < 35) ? 'FAIL' : 'PASS';

                if ($resultStatus === 'FAIL') {
                    $overallGrade = $hasFailedSubject ? 'COMPARTMENT' : 'F';
                } elseif ($percentage >= 90) {
                    $overallGrade = 'A+';
                } elseif ($percentage >= 80) {
                    $overallGrade = 'A';
                } elseif ($percentage >= 70) {
                    $overallGrade = 'B+';
                } elseif ($percentage >= 60) {
                    $overallGrade = 'B';
                } elseif ($percentage >= 50) {
                    $overallGrade = 'C';
                } else {
                    $overallGrade = 'D';
                }

                $daysPresent = AttendanceRecord::where('student_id', $student->id)
                    ->whereHas('session', function ($sq) use ($class, $term, $termEndDate) {
                        $sq->where('class_id', $class->id)
                            ->whereDate('session_date', '<=', $termEndDate);
                        if ($term->start_date) {
                            $sq->whereDate('session_date', '>=', $term->start_date);
                        }
                    })
                    ->whereIn('status', ['present', 'late', 'half_day'])
                    ->count();

                $daysPresent = min($daysPresent, $totalSessions);
                $attPct = min(100.00, max(0.00, round(($daysPresent / $totalSessions) * 100, 2)));

                $studentResults[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'total_max' => $totalMax > 0 ? $totalMax : 100.00,
                    'total_obtained' => $totalObtained,
                    'percentage' => $percentage,
                    'gpa' => $gpa,
                    'overall_grade' => $overallGrade,
                    'result_status' => $resultStatus,
                    'total_working_days' => $totalSessions,
                    'days_present' => $daysPresent,
                    'att_pct' => $attPct,
                ];
            }

            // Assign Class Ranks by sorting descending on marks obtained
            usort($studentResults, function ($a, $b) {
                return $b['total_obtained'] <=> $a['total_obtained'];
            });

            $currentRank = 1;
            foreach ($studentResults as $index => &$res) {
                if ($index > 0 && $res['total_obtained'] < $studentResults[$index - 1]['total_obtained']) {
                    $currentRank = $index + 1;
                }
                $res['class_rank'] = $currentRank;

                if (! $isDryRun) {
                    $card = SchoolReportCard::updateOrCreate(
                        [
                            'exam_term_id' => $term->id,
                            'class_id' => $class->id,
                            'student_id' => $res['student_id'],
                        ],
                        [
                            'total_max_marks' => $res['total_max'],
                            'total_marks_obtained' => $res['total_obtained'],
                            'percentage' => $res['percentage'],
                            'gpa' => $res['gpa'],
                            'overall_grade' => $res['overall_grade'],
                            'class_rank' => $res['class_rank'],
                            'total_working_days' => $res['total_working_days'],
                            'days_present' => $res['days_present'],
                            'attendance_percentage' => $res['att_pct'],
                            'result_status' => $res['result_status'],
                            'is_published' => $isPublished,
                            'generated_date' => now()->toDateString(),
                        ]
                    );

                    if ($isPublished) {
                        NotificationService::notifyUser(
                            $res['student_id'],
                            'academic',
                            "🎉 Report Card Published: {$term->name}",
                            "Your report card for {$term->name} has been published with grade {$res['overall_grade']} (Rank: #{$res['class_rank']}).",
                            route('school.report-cards.show', $card->id),
                            '📜'
                        );
                    }
                }

                $totalCardsGenerated++;
            }
        }

        $this->table(
            ['Term Name', 'Classes Scanned', 'Report Cards Processed', 'Published', 'Mode'],
            [
                [$term->name, $classes->count(), $totalCardsGenerated, $isPublished ? 'Yes' : 'No', $isDryRun ? 'DRY-RUN' : 'LIVE'],
            ]
        );

        $this->info('Report card compilation completed successfully.');

        return self::SUCCESS;
    }
}
