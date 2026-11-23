<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\BloomAssignment;
use App\Models\ClassEnrollment;
use App\Models\CurriculumTopic;
use App\Models\DailyLesson;
use App\Models\TopicBloomActivity;
use App\Models\User;
use App\Services\BloomsAssignmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BloomsTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Classes 1 through 8
        $classes = [];
        for ($i = 1; $i <= 8; $i++) {
            $classes[$i] = AcademicClass::firstOrCreate(
                ['name' => "Class {$i}", 'grade_level' => $i],
                [
                    'section' => 'A',
                    'academic_year' => '2026-2027',
                ]
            );
        }

        // 2. Create Subjects for Classes
        $class8 = $classes[8];
        $science8 = AcademicSubject::firstOrCreate(
            ['class_id' => $class8->id, 'name' => 'Science'],
            ['code' => 'SCI-08']
        );
        $math8 = AcademicSubject::firstOrCreate(
            ['class_id' => $class8->id, 'name' => 'Mathematics'],
            ['code' => 'MATH-08']
        );
        $english8 = AcademicSubject::firstOrCreate(
            ['class_id' => $class8->id, 'name' => 'English Literature'],
            ['code' => 'ENG-08']
        );

        $class7 = $classes[7];
        $science7 = AcademicSubject::firstOrCreate(
            ['class_id' => $class7->id, 'name' => 'Science'],
            ['code' => 'SCI-07']
        );

        $class6 = $classes[6];
        $science6 = AcademicSubject::firstOrCreate(
            ['class_id' => $class6->id, 'name' => 'Science'],
            ['code' => 'SCI-06']
        );

        // Subjects for Classes 1 to 5
        for ($i = 1; $i <= 5; $i++) {
            AcademicSubject::firstOrCreate(
                ['class_id' => $classes[$i]->id, 'name' => 'General Science & Environment'],
                ['code' => "GSE-0{$i}"]
            );
            AcademicSubject::firstOrCreate(
                ['class_id' => $classes[$i]->id, 'name' => 'Foundational Mathematics'],
                ['code' => "MATH-0{$i}"]
            );
        }

        // 3. Fully Map Class 8 Science Topic: "Photosynthesis & Plant Nutrition"
        $topicPhotosynthesis = CurriculumTopic::firstOrCreate(
            ['subject_id' => $science8->id, 'title' => 'Photosynthesis & Plant Nutrition'],
            [
                'chapter_number' => 4,
                'subtopics' => [
                    'Raw Materials and Stomatal Gas Exchange',
                    'Chloroplast Structure & Chlorophyll Pigments',
                    'Light Reactions & Calvin Cycle Basics',
                    'Iodine Test for Starch & Limiting Factors',
                ],
                'description' => 'Comprehensive study of biochemical energy conversion in autotrophs, pigment absorption, and starch synthesis.',
            ]
        );

        // Map all 6 Bloom's Taxonomy Levels for Photosynthesis
        $bloomPhotosynthesis = [
            [
                'bloom_level' => 1,
                'level_name' => 'Remember',
                'verb_keywords' => 'Define, List, Recall, Identify',
                'activity_prompt' => '1. List the 4 essential raw materials required for photosynthesis.\n2. State the chemical equation for photosynthesis in balanced form.\n3. Define the terms: chlorophyll, stomata, and chloroplast.',
                'rubric_guide' => 'Full credit for accurate formula, correct naming of raw materials (CO2, H2O, sunlight, chlorophyll), and precise biological definitions.',
            ],
            [
                'bloom_level' => 2,
                'level_name' => 'Understand',
                'verb_keywords' => 'Explain, Describe, Distinguish, Summarize',
                'activity_prompt' => '1. Explain in your own words how radiant solar energy is captured and transformed into chemical glucose.\n2. Describe the opening and closing mechanism of stomatal guard cells.\n3. Distinguish clearly between autotrophic and heterotrophic nutrition with examples.',
                'rubric_guide' => 'Assesses conceptual clarity, explanation of energy transformation, and clear contrasting of nutritional modes in complete sentences.',
            ],
            [
                'bloom_level' => 3,
                'level_name' => 'Apply',
                'verb_keywords' => 'Demonstrate, Solve, Experiment, Construct',
                'activity_prompt' => 'Design a controlled laboratory experiment to prove that sunlight is indispensable for starch formation in green leaves. Outline:\n- Apparatus & materials needed\n- Destarching procedure (48h dark storage)\n- Partial leaf masking with black paper\n- Iodine solution test steps & expected visual observations.',
                'rubric_guide' => 'Evaluates practical scientific methodology, control group setup, ethanol boiling safety precautions, and correct color change interpretation (blue-black).',
            ],
            [
                'bloom_level' => 4,
                'level_name' => 'Analyze',
                'verb_keywords' => 'Differentiate, Contrast, Graph, Examine',
                'activity_prompt' => 'Analyze the effect of differing light wavelengths (Red, Blue, Green) on photosynthetic rate:\n1. Draw a comparative graph showing anticipated oxygen bubble production under each color.\n2. Deduce why green light results in the lowest rate of carbon fixation based on chlorophyll absorption spectra.\n3. Differentiate how temperature acts as a limiting factor versus light intensity.',
                'rubric_guide' => 'Focuses on critical thinking, wavelength absorption spectrum analysis, and graphical reasoning about limiting factors.',
            ],
            [
                'bloom_level' => 5,
                'level_name' => 'Evaluate',
                'verb_keywords' => 'Critique, Judge, Justify, Defend',
                'activity_prompt' => 'A student conducted an experiment to measure photosynthetic rate using an Elodea water plant. They placed one beaker in direct sunlight using boiled (cooled) tap water and another in dim light with carbonated water. They concluded that sunlight does not affect photosynthesis because the dim-light carbonated beaker produced more bubbles.\n- Critique the validity of this experimental setup.\n- Identify the uncontrolled variables and experimental flaws.\n- Defend an alternative interpretation of their data.',
                'rubric_guide' => 'Assesses high-level evaluation skills: identifying that boiling removes dissolved CO2, while carbonation adds CO2, invalidating a single-variable conclusion.',
            ],
            [
                'bloom_level' => 6,
                'level_name' => 'Create',
                'verb_keywords' => 'Design, Formulate, Invent, Synthesize',
                'activity_prompt' => 'Formulate an architectural and engineering proposal for an "Artificial Bionic Leaf" system installed on urban rooftops.\n1. Propose how the artificial leaf mimics natural light-harvesting complexes to capture atmospheric CO2.\n2. Propose an end-product yield (e.g., green hydrogen fuel or organic biomass).\n3. Synthesize an infographic or structural schematic with annotations explaining its sustainability impact.',
                'rubric_guide' => 'Highest cognitive level: novel problem-solving, biomimicry principles, feasibility rationale, and creative synthetic thinking.',
            ],
        ];

        foreach ($bloomPhotosynthesis as $ba) {
            TopicBloomActivity::updateOrCreate(
                ['topic_id' => $topicPhotosynthesis->id, 'bloom_level' => $ba['bloom_level']],
                $ba
            );
        }

        // Additional curriculum topics for Class 8
        CurriculumTopic::firstOrCreate(
            ['subject_id' => $math8->id, 'title' => 'Linear Equations in One Variable'],
            [
                'chapter_number' => 2,
                'subtopics' => ['Variables & Coefficients', 'Transposition Method', 'Cross-Multiplication', 'Word Problems & Real-World Modeling'],
                'description' => 'Algebraic solving of single-variable linear equations and word problem translation.',
            ]
        );

        CurriculumTopic::firstOrCreate(
            ['subject_id' => $science7->id, 'title' => 'Heat and Temperature Measurement'],
            [
                'chapter_number' => 3,
                'subtopics' => ['Conduction, Convection & Radiation', 'Clinical vs Laboratory Thermometers', 'Sea Breeze & Land Breeze'],
                'description' => 'Thermodynamic principles, states of matter, and methods of thermal energy propagation.',
            ]
        );

        // 4. Enroll Students into Class 8 with Roll Numbers
        $students = User::where('role', 'student')->orderBy('id')->get();

        // Authentic Indian student roster for Class 8
        $additionalNames = [
            'Priyadarshini Rao', 'Arjun Nair', 'Sneha Kulkarni', 'Ishaan Malhotra', 'Meera Deshmukh', 'Kabir Singhania', 'Tanvi Reddy',
        ];

        $roll = 1;
        foreach ($students as $student) {
            ClassEnrollment::firstOrCreate(
                ['class_id' => $class8->id, 'student_id' => $student->id],
                ['roll_number' => $roll++, 'status' => 'active']
            );
        }

        foreach ($additionalNames as $idx => $name) {
            $email = strtolower(str_replace(' ', '.', $name)).'@primearc.edu.in';
            $extraStudent = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                    'student_id_number' => 'PA-STD-2026-'.str_pad((string) ($roll), 2, '0', STR_PAD_LEFT),
                    'card_valid_until' => now()->addYears(2),
                ]
            );

            ClassEnrollment::firstOrCreate(
                ['class_id' => $class8->id, 'student_id' => $extraStudent->id],
                ['roll_number' => $roll++, 'status' => 'active']
            );
        }

        // 5. Create Daily Lesson for Class 8 Photosynthesis
        $teacher = User::where('role', 'teacher')->first();

        $dailyLesson = DailyLesson::firstOrCreate(
            [
                'class_id' => $class8->id,
                'subject_id' => $science8->id,
                'topic_id' => $topicPhotosynthesis->id,
                'lesson_date' => now()->toDateString(),
            ],
            [
                'teacher_id' => $teacher->id,
                'subtopic' => 'Iodine Test for Starch & Limiting Factors',
                'distribution_mode' => 'balanced_random',
                'lesson_notes' => 'Taught standard textbook chapter 4. Conducted laboratory demonstration with Elodea plant bubbles and iodine test on variegated leaves.',
            ]
        );

        // Distribute Bloom's assignments across the 12 students in Class 8
        $bloomsService = new BloomsAssignmentService;
        $bloomsService->distributeAssignments($dailyLesson, 'balanced_random');

        // Simulate student submission for Roll 1 (e.g. Aarav Sharma)
        $roll1Enrollment = ClassEnrollment::where('class_id', $class8->id)->where('roll_number', 1)->first();

        if ($roll1Enrollment) {
            $roll1Assignment = BloomAssignment::where('lesson_id', $dailyLesson->id)
                ->where('student_id', $roll1Enrollment->student_id)
                ->first();

            if ($roll1Assignment) {
                $roll1Assignment->update([
                    'submission_content' => "Controlled Experiment Design:\n1. Destarched potted geranium for 48 hours in dark closet.\n2. Attached black stencil strip across middle of leaf A; left leaf B fully exposed as control.\n3. Exposed to 6 hours morning direct sunlight.\n4. Decolorized leaf by boiling in water bath with 90% ethanol.\n5. Applied dilute iodine solution:\n   - Masked zone remained pale brown (no starch).\n   - Unmasked area turned deep blue-black.\nConclusion: Proves light is strictly essential for starch synthesis.",
                    'submitted_at' => now()->subHours(2),
                    'status' => 'reviewed',
                    'score' => 96.00,
                    'teacher_feedback' => 'Superb experimental setup! Clear control variable, correct alcohol bath safety note, and accurate iodine reaction interpretation.',
                    'reviewed_by' => $teacher->id,
                    'reviewed_at' => now()->subHour(),
                ]);
            }
        }

        // Simulate submission for Roll 2 (e.g. Ananya Patel)
        $roll2Enrollment = ClassEnrollment::where('class_id', $class8->id)->where('roll_number', 2)->first();

        if ($roll2Enrollment) {
            $roll2Assignment = BloomAssignment::where('lesson_id', $dailyLesson->id)
                ->where('student_id', $roll2Enrollment->student_id)
                ->first();

            if ($roll2Assignment) {
                $roll2Assignment->update([
                    'submission_content' => "Analysis of Chlorophyll Absorption Spectrum:\nUnder green wavelength illumination (approx 500-550nm), chlorophyll a and b exhibit minimal absorption, reflecting most photon energy. Consequently, photosynthetic rate drops to baseline. Under red (660nm) and blue (430nm), photon capture by Photosystem II peaks, driving maximum photolysis of water.",
                    'submitted_at' => now()->subMinutes(45),
                    'status' => 'submitted',
                ]);
            }
        }
    }
}
