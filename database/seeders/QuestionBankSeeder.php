<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first() ?? User::first();
        $class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $science8 = AcademicSubject::where('class_id', $class8->id)->where('name', 'like', '%Science%')->first()
            ?? AcademicSubject::where('class_id', $class8->id)->first();
        $math8 = AcademicSubject::where('class_id', $class8->id)->where('name', 'like', '%Math%')->first();
        $topic = CurriculumTopic::where('subject_id', $science8?->id)->first();

        // 1. Seed Question Bank for Class 8 Science & Mathematics
        $bankQuestions = [
            [
                'subject_id' => $science8->id,
                'text' => 'Which cell organelle is known as the powerhouse of the cell due to ATP synthesis?',
                'a' => 'Endoplasmic Reticulum',
                'b' => 'Mitochondria',
                'c' => 'Lysosome',
                'd' => 'Vacuole',
                'correct' => 'B',
                'explanation' => 'Mitochondria generate cellular energy currency (ATP) through cellular respiration and oxidative phosphorylation.',
                'difficulty' => 'standard',
                'tags' => 'Cell Biology, NCERT, Class 8',
            ],
            [
                'subject_id' => $science8->id,
                'text' => 'What is the SI unit of pressure exerted on a surface per unit area?',
                'a' => 'Joule',
                'b' => 'Newton',
                'c' => 'Pascal (N/m²)',
                'd' => 'Watt',
                'correct' => 'C',
                'explanation' => 'Pressure is force per unit area (P = F / A). 1 Pascal equals 1 Newton per square meter.',
                'difficulty' => 'standard',
                'tags' => 'Physics, Force & Pressure, Class 8',
            ],
            [
                'subject_id' => $science8->id,
                'text' => 'Which characteristic sound property depends directly on the amplitude of acoustic vibration?',
                'a' => 'Pitch (Frequency)',
                'b' => 'Loudness',
                'c' => 'Speed of sound',
                'd' => 'Wave timber',
                'correct' => 'B',
                'explanation' => 'Loudness is proportional to the square of the amplitude of vibration. Larger amplitude creates a louder sound.',
                'difficulty' => 'advanced',
                'tags' => 'Physics, Sound, Olympiad',
            ],
            [
                'subject_id' => $science8->id,
                'text' => 'Which gas is produced when metals react with dilute hydrochloric acid?',
                'a' => 'Oxygen gas',
                'b' => 'Nitrogen dioxide',
                'c' => 'Hydrogen gas (H2)',
                'd' => 'Chlorine gas',
                'correct' => 'C',
                'explanation' => 'Metal + Dilute Acid -> Metal Salt + Hydrogen gas, which burns with a characteristic pop sound.',
                'difficulty' => 'standard',
                'tags' => 'Chemistry, Metals and Non-Metals',
            ],
            [
                'subject_id' => $science8->id,
                'text' => 'In an ecosystem food chain, what role is performed by saprophytic fungi and bacteria?',
                'a' => 'Primary Producers',
                'b' => 'Tertiary Consumers',
                'c' => 'Decomposers',
                'd' => 'Apex Predators',
                'correct' => 'C',
                'explanation' => 'Decomposers break down dead organic matter and recycle essential nutrients back into the soil and atmosphere.',
                'difficulty' => 'standard',
                'tags' => 'Ecology, Microorganisms',
            ],
            [
                'subject_id' => $math8 ? $math8->id : $science8->id,
                'text' => 'Solve for x: 3x - 7 = 14 + 5x',
                'a' => 'x = -10.5',
                'b' => 'x = 10.5',
                'c' => 'x = -3.5',
                'd' => 'x = 3.5',
                'correct' => 'A',
                'explanation' => '3x - 5x = 14 + 7 => -2x = 21 => x = -21/2 = -10.5.',
                'difficulty' => 'standard',
                'tags' => 'Mathematics, Linear Equations',
            ],
            [
                'subject_id' => $math8 ? $math8->id : $science8->id,
                'text' => 'What is the smallest number by which 180 must be multiplied so that the product becomes a perfect square?',
                'a' => '2',
                'b' => '3',
                'c' => '5',
                'd' => '7',
                'correct' => 'C',
                'explanation' => 'Prime factorization of 180 = 2² × 3² × 5. To make pairs, 5 needs one more partner: 180 × 5 = 900 = 30².',
                'difficulty' => 'advanced',
                'tags' => 'Mathematics, Squares & Roots, Olympiad',
            ],
            [
                'subject_id' => $math8 ? $math8->id : $science8->id,
                'text' => 'The sum of all interior angles of a convex hexagon (6 sides) is:',
                'a' => '540 degrees',
                'b' => '720 degrees',
                'c' => '900 degrees',
                'd' => '1080 degrees',
                'correct' => 'B',
                'explanation' => 'Sum = (n - 2) × 180° = (6 - 2) × 180° = 4 × 180° = 720°.',
                'difficulty' => 'standard',
                'tags' => 'Mathematics, Geometry, Polygons',
            ],
        ];

        foreach ($bankQuestions as $bq) {
            QuestionBank::firstOrCreate(
                [
                    'class_id' => $class8->id,
                    'subject_id' => $bq['subject_id'],
                    'question_text' => $bq['text'],
                ],
                [
                    'topic_id' => $topic?->id,
                    'teacher_id' => $teacher->id,
                    'option_a' => $bq['a'],
                    'option_b' => $bq['b'],
                    'option_c' => $bq['c'],
                    'option_d' => $bq['d'],
                    'correct_option' => $bq['correct'],
                    'explanation' => $bq['explanation'],
                    'difficulty' => $bq['difficulty'],
                    'marks' => 4,
                    'tags' => $bq['tags'],
                ]
            );
        }

        // 2. Seed a Formal Timed Mock Test for Class 8
        $test = DailyDpp::firstOrCreate(
            [
                'class_id' => $class8->id,
                'dpp_number' => 'TEST-08-01',
            ],
            [
                'type' => 'test',
                'subject_id' => $science8->id,
                'topic_id' => $topic?->id,
                'teacher_id' => $teacher->id,
                'title' => 'Mid-Term Science & Analytical Skills Mock Test',
                'assigned_date' => now()->toDateString(),
                'difficulty' => 'advanced',
                'duration_minutes' => 20, // 20-minute countdown timer
                'is_timed' => true,
                'pass_percentage' => 50,
                'total_marks' => 16,
            ]
        );

        $testQuestions = [
            [
                'num' => 1,
                'text' => 'Which cell organelle is known as the powerhouse of the cell due to ATP synthesis?',
                'a' => 'Endoplasmic Reticulum',
                'b' => 'Mitochondria',
                'c' => 'Lysosome',
                'd' => 'Vacuole',
                'correct' => 'B',
                'exp' => 'Mitochondria generate cellular energy currency (ATP) through cellular respiration.',
            ],
            [
                'num' => 2,
                'text' => 'What is the SI unit of pressure exerted on a surface per unit area?',
                'a' => 'Joule',
                'b' => 'Newton',
                'c' => 'Pascal (N/m²)',
                'd' => 'Watt',
                'correct' => 'C',
                'exp' => 'Pressure is force per unit area (P = F / A). 1 Pascal equals 1 Newton per square meter.',
            ],
            [
                'num' => 3,
                'text' => 'Which characteristic sound property depends directly on the amplitude of acoustic vibration?',
                'a' => 'Pitch (Frequency)',
                'b' => 'Loudness',
                'c' => 'Speed of sound',
                'd' => 'Wave timber',
                'correct' => 'B',
                'exp' => 'Loudness is proportional to the square of amplitude.',
            ],
            [
                'num' => 4,
                'text' => 'Which gas is produced when metals react with dilute hydrochloric acid?',
                'a' => 'Oxygen gas',
                'b' => 'Nitrogen dioxide',
                'c' => 'Hydrogen gas (H2)',
                'd' => 'Chlorine gas',
                'correct' => 'C',
                'exp' => 'Metal + Dilute Acid -> Metal Salt + Hydrogen gas.',
            ],
        ];

        foreach ($testQuestions as $tq) {
            DppQuestion::firstOrCreate(
                [
                    'dpp_id' => $test->id,
                    'question_number' => $tq['num'],
                ],
                [
                    'question_text' => $tq['text'],
                    'option_a' => $tq['a'],
                    'option_b' => $tq['b'],
                    'option_c' => $tq['c'],
                    'option_d' => $tq['d'],
                    'correct_option' => $tq['correct'],
                    'explanation' => $tq['exp'],
                    'marks' => 4,
                ]
            );
        }
    }
}
