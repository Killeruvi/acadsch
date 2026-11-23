<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Batch;
use App\Models\ClassEnrollment;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = AcademicClass::orderBy('grade_level')->get();

        $batchTitles = [
            1 => 'Class 1 - Early Foundation Batch',
            2 => 'Class 2 - Foundation Excellence Batch',
            3 => 'Class 3 - Primary Explorers Batch',
            4 => 'Class 4 - Primary Scholars Batch',
            5 => 'Class 5 - Junior Achievers Batch',
            6 => 'Class 6 - Middle School Alpha Batch',
            7 => 'Class 7 - Middle School Pioneers Batch',
            8 => 'Class 8 - Alpha Champions Batch',
            9 => 'Class 9 - Board Foundation Batch',
            10 => 'Class 10 - Alpha Board Master Batch',
            11 => 'Class 11 - Higher Secondary Science & Commerce Batch',
            12 => 'Class 12 - Senior Board & Competitive Excellence Batch',
        ];

        $createdBatches = [];

        foreach ($classes as $class) {
            $grade = $class->grade_level;

            // 1. Batch A
            $codeM = 'BATCH-' . str_pad((string)$grade, 2, '0', STR_PAD_LEFT) . '-A';
            $batchM = Batch::updateOrCreate(
                ['code' => $codeM],
                [
                    'class_id' => $class->id,
                    'name' => "Class {$grade} - Batch A",
                    'shift' => 'morning',
                    'timing_slot' => null,
                    'start_time' => '08:00',
                    'end_time' => '13:00',
                    'academic_year' => '2026-2027',
                    'description' => "Academic batch A for Class {$grade} students.",
                    'is_active' => true,
                ]
            );

            // 2. Batch B
            $codeE = 'BATCH-' . str_pad((string)$grade, 2, '0', STR_PAD_LEFT) . '-B';
            $batchE = Batch::updateOrCreate(
                ['code' => $codeE],
                [
                    'class_id' => $class->id,
                    'name' => "Class {$grade} - Batch B",
                    'shift' => 'evening',
                    'timing_slot' => null,
                    'start_time' => '08:00',
                    'end_time' => '13:00',
                    'academic_year' => '2026-2027',
                    'description' => "Academic batch B for Class {$grade} students.",
                    'is_active' => true,
                ]
            );

            // 3. Batch C
            $codeC = 'BATCH-' . str_pad((string)$grade, 2, '0', STR_PAD_LEFT) . '-C';
            $batchC = Batch::updateOrCreate(
                ['code' => $codeC],
                [
                    'class_id' => $class->id,
                    'name' => "Class {$grade} - Batch C",
                    'shift' => 'custom',
                    'timing_slot' => null,
                    'start_time' => '08:00',
                    'end_time' => '13:00',
                    'academic_year' => '2026-2027',
                    'description' => "Academic batch C for Class {$grade} students.",
                    'is_active' => true,
                ]
            );

            // Backwards compatibility for previous code
            $codeOld = 'BATCH-' . str_pad((string)$grade, 2, '0', STR_PAD_LEFT) . 'A';
            Batch::where('code', $codeOld)->update([
                'shift' => 'morning',
                'timing_slot' => null,
                'start_time' => '08:00',
                'end_time' => '13:00',
            ]);

            $createdBatches[$class->id] = $batchM;
        }

        // Assign each student to their class's batch
        $students = User::where('role', 'student')->get();
        foreach ($students as $student) {
            $enrollment = ClassEnrollment::where('student_id', $student->id)->first();
            $classId = $enrollment ? $enrollment->class_id : 8; // default to Class 8

            if (isset($createdBatches[$classId])) {
                $student->update(['batch_id' => $createdBatches[$classId]->id]);
            } elseif (!empty($createdBatches)) {
                $student->update(['batch_id' => reset($createdBatches)->id]);
            }
        }

        // Populate Notes and Mock Tests for Topics where missing
        $topics = CurriculumTopic::with(['subject.academicClass'])->get();
        $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();
        $teacherId = $teacher ? $teacher->id : 1;

        // Topics now host authentic faculty-uploaded resources rather than placeholder notes.
    }
}
