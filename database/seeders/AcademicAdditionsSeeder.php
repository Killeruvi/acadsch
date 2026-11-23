<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicAdditionsSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first() ?? User::first();
        $student = User::where('role', 'student')->first() ?? User::first();
        $class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $science8 = AcademicSubject::where('class_id', $class8->id)->first() ?? AcademicSubject::first();
        $topic = CurriculumTopic::where('subject_id', $science8->id)->first() ?? CurriculumTopic::first();

        // 1. Seed Attendance Session & Record
        $session = AttendanceSession::where('class_id', $class8->id)
            ->whereDate('session_date', now()->toDateString())
            ->first();

        if (! $session) {
            $session = AttendanceSession::create([
                'class_id' => $class8->id,
                'session_date' => now()->toDateString(),
                'teacher_id' => $teacher->id,
                'title' => "Morning Attendance - {$class8->name}",
                'notes' => 'Standard morning roll call for Science and Mathematics block.',
            ]);
        }

        $enrolledStudents = $class8->students()->take(5)->get();
        foreach ($enrolledStudents as $idx => $stu) {
            AttendanceRecord::firstOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $stu->id,
                ],
                [
                    'status' => $idx === 4 ? 'absent' : 'present',
                    'remarks' => $idx === 4 ? 'Reported flu' : 'On time',
                ]
            );
        }

        // 2. Seed Daily Practice Problems (DPP) with 5 MCQs
        $dpp = DailyDpp::firstOrCreate(
            [
                'class_id' => $class8->id,
                'dpp_number' => 'DPP-08-01',
            ],
            [
                'subject_id' => $science8->id,
                'topic_id' => $topic?->id,
                'teacher_id' => $teacher->id,
                'title' => 'DPP #1: Photosynthesis & Light Reactions',
                'assigned_date' => now()->toDateString(),
                'difficulty' => 'standard',
                'total_marks' => 20,
            ]
        );

        $questions = [
            [
                'num' => 1,
                'text' => 'Which cell organelle is the primary site of photosynthetic reactions in green plants?',
                'a' => 'Mitochondria',
                'b' => 'Chloroplast',
                'c' => 'Golgi Apparatus',
                'd' => 'Ribosome',
                'correct' => 'B',
                'exp' => 'Chloroplasts contain chlorophyll pigments and thylakoid membranes where light and dark photosynthetic reactions take place.',
            ],
            [
                'num' => 2,
                'text' => 'During light-dependent photosynthetic reactions, photolysis of water releases which gas into the atmosphere?',
                'a' => 'Carbon Dioxide (CO2)',
                'b' => 'Nitrogen (N2)',
                'c' => 'Oxygen (O2)',
                'd' => 'Methane (CH4)',
                'correct' => 'C',
                'exp' => 'Light energy absorbed by chlorophyll splits H2O molecules into protons, electrons, and free Oxygen gas (O2).',
            ],
            [
                'num' => 3,
                'text' => 'What is the chemical reagent used to detect the presence of starch produced during leaf photosynthesis?',
                'a' => 'Benedict Solution',
                'b' => 'Iodine Solution',
                'c' => 'Biuret Reagent',
                'd' => 'Phenolphthalein',
                'correct' => 'B',
                'exp' => 'Iodine solution turns blue-black in the presence of starch, proving photosynthetic glucose condensation into starch reserves.',
            ],
            [
                'num' => 4,
                'text' => 'Which tiny microscopic pores on the underside of plant leaves regulate gaseous exchange and transpiration?',
                'a' => 'Lenticels',
                'b' => 'Guard Cells',
                'c' => 'Stomata',
                'd' => 'Xylem Vessels',
                'correct' => 'C',
                'exp' => 'Stomata are microscopic pores flanked by guard cells that open and close for CO2 intake and H2O transpiration.',
            ],
            [
                'num' => 5,
                'text' => 'In the Calvin cycle (dark reaction), where within the chloroplast does carbon fixation take place?',
                'a' => 'Thylakoid Lumen',
                'b' => 'Granum',
                'c' => 'Stroma',
                'd' => 'Outer Membrane',
                'correct' => 'C',
                'exp' => 'The Stroma is the fluid-filled space containing enzymes such as RuBisCO required for carbon fixation into carbohydrates.',
            ],
        ];

        foreach ($questions as $q) {
            DppQuestion::firstOrCreate(
                [
                    'dpp_id' => $dpp->id,
                    'question_number' => $q['num'],
                ],
                [
                    'question_text' => $q['text'],
                    'option_a' => $q['a'],
                    'option_b' => $q['b'],
                    'option_c' => $q['c'],
                    'option_d' => $q['d'],
                    'correct_option' => $q['correct'],
                    'explanation' => $q['exp'],
                    'marks' => 4,
                ]
            );
        }

        // 3. Seed Daily Homework Task
        $homework = HomeworkTask::firstOrCreate(
            [
                'class_id' => $class8->id,
                'title' => 'Science Chapter 1: Laboratory Leaf Sketch & Stomatal Anatomy',
            ],
            [
                'subject_id' => $science8->id,
                'teacher_id' => $teacher->id,
                'instructions' => 'Observe a fresh leaf under a hand lens or microscope. Draw and label the stomata, guard cells, and epidermal tissue. Write 3 key functions.',
                'assigned_date' => now()->toDateString(),
                'due_date' => now()->addDay()->toDateString(),
            ]
        );

        if ($student) {
            HomeworkSubmission::firstOrCreate(
                [
                    'homework_id' => $homework->id,
                    'student_id' => $student->id,
                ],
                [
                    'student_notes' => 'Completed diagram in science notebook. Guard cells regulate opening based on turgor pressure. Functions: gas exchange, transpiration, temperature control.',
                    'status' => 'submitted',
                ]
            );

            // 4. Seed In-App Notifications for the student
            InAppNotification::firstOrCreate(
                [
                    'user_id' => $student->id,
                    'title' => '⚡ Bloom\'s Level 3 (Apply) Assigned!',
                ],
                [
                    'type' => 'bloom',
                    'message' => 'Today\'s Photosynthesis lesson was assigned to you at Level 3: Design a starch testing experiment.',
                    'action_url' => '/blooms',
                    'icon' => '⚡',
                    'is_read' => false,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $student->id,
                    'title' => '📝 Today\'s DPP-08-01 is Live',
                ],
                [
                    'type' => 'dpp',
                    'message' => '5 Practice problems on Photosynthesis are available for immediate same-day checking!',
                    'action_url' => "/dpp/{$dpp->id}",
                    'icon' => '📝',
                    'is_read' => false,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $student->id,
                    'title' => '📚 New Daily Homework Assigned',
                ],
                [
                    'type' => 'homework',
                    'message' => 'Science Chapter 1: Laboratory Leaf Sketch & Stomatal Anatomy is due tomorrow.',
                    'action_url' => '/homework',
                    'icon' => '📚',
                    'is_read' => false,
                ]
            );
        }
    }
}
