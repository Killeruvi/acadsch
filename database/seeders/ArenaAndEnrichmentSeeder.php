<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicProject;
use App\Models\AcademicSubject;
use App\Models\AcademyEvent;
use App\Models\LiveQuiz;
use App\Models\LiveQuizQuestion;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserCertificate;
use Illuminate\Database\Seeder;

class ArenaAndEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();
        $student = User::where('role', 'student')->first();
        $class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $science = AcademicSubject::where('name', 'like', '%Science%')->first() ?? AcademicSubject::first();

        if (! $teacher || ! $class8) {
            return;
        }

        // 1. Live Quizzes
        $quiz = LiveQuiz::firstOrCreate([
            'access_code' => 'QZ-OLYMP',
        ], [
            'class_id' => $class8->id,
            'subject_id' => $science?->id,
            'creator_id' => $teacher->id,
            'title' => 'National Physics Olympiad Sprint',
            'description' => 'Fast-paced live arena test evaluating Newton’s mechanics, rotational inertia, and wave dynamics.',
            'time_per_question' => 30,
            'xp_reward' => 120,
            'status' => 'active',
        ]);

        if ($quiz->questions()->count() === 0) {
            LiveQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => 'What is the SI unit of gravitational potential energy?',
                'option_a' => 'Newton (N)',
                'option_b' => 'Joule (J)',
                'option_c' => 'Watt (W)',
                'option_d' => 'Pascal (Pa)',
                'correct_option' => 'B',
                'explanation' => 'Energy in all forms (kinetic, potential, thermal) is measured in Joules (J = kg·m²/s²).',
                'points' => 10,
            ]);

            LiveQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => 'When a body moves with constant velocity, its net external acceleration is:',
                'option_a' => 'Zero',
                'option_b' => '9.8 m/s²',
                'option_c' => 'Infinite',
                'option_d' => 'Proportional to its mass',
                'correct_option' => 'A',
                'explanation' => 'According to Newton\'s First Law, constant velocity implies zero net change in velocity (a = dv/dt = 0).',
                'points' => 10,
            ]);

            LiveQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => 'Which of the following electromagnetic waves has the highest frequency?',
                'option_a' => 'Microwaves',
                'option_b' => 'Ultraviolet',
                'option_c' => 'Gamma rays',
                'option_d' => 'Infrared',
                'correct_option' => 'C',
                'explanation' => 'Gamma rays reside at the highest energy and frequency end of the electromagnetic spectrum.',
                'points' => 10,
            ]);
        }

        // 2. Academic Projects & Case Studies (Grouped and Ungrouped)
        AcademicProject::firstOrCreate([
            'title' => 'Smart City Autonomous Traffic Flow Optimization',
        ], [
            'class_id' => $class8->id,
            'subject_id' => $science?->id,
            'teacher_id' => $teacher->id,
            'type' => 'project',
            'group_mode' => 'grouped',
            'max_team_size' => 4,
            'description' => 'Design an automated traffic sequencing model using graph theory and sensor feedback to minimize metropolitan vehicle wait times.',
            'problem_statement' => 'Urban density creates bottlenecks at arterial junctions. Model an adaptive signal algorithm that reacts dynamically to vehicular queue density.',
            'evaluation_rubric' => 'Mathematical formulation (30%), simulation code/diagrams (40%), final presentation & team synergy (30%).',
            'due_date' => now()->addWeeks(3)->toDateString(),
            'max_marks' => 100,
        ]);

        AcademicProject::firstOrCreate([
            'title' => 'Quantum Tunneling in Modern Semiconductor Nanostructures',
        ], [
            'class_id' => $class8->id,
            'subject_id' => $science?->id,
            'teacher_id' => $teacher->id,
            'type' => 'case_study',
            'group_mode' => 'individual',
            'max_team_size' => 1,
            'description' => 'Comprehensive analytical study investigating quantum leakage in sub-3nm FinFET and Gate-All-Around (GAA) transistor architectures.',
            'problem_statement' => 'As gate oxide thicknesses drop below 1.5 nanometers, wave-packet tunneling limits traditional gate insulation. Propose alternate high-k dielectric solutions.',
            'evaluation_rubric' => 'Literature review (25%), physical equations (35%), proposed solution feasibility (40%).',
            'due_date' => now()->addWeeks(2)->toDateString(),
            'max_marks' => 75,
        ]);

        // 3. Extracurricular Events (Debates & Talent Shows)
        AcademyEvent::firstOrCreate([
            'title' => 'Prime Arc National Parliamentary Debate 2026',
        ], [
            'coordinator_id' => $teacher->id,
            'category' => 'debate',
            'event_format' => 'parliamentary_team',
            'description' => 'Official Asian Parliamentary format debate tournament. Motion: "This House Would Mandate Algorithmic Transparency in Educational Grading."',
            'rules_and_rubric' => '7-minute constructive speeches, 3-minute whip replies. Points of Information (POIs) accepted between minutes 1 and 6.',
            'event_datetime' => now()->addDays(5)->setTime(14, 0),
            'venue' => 'Chanakya Central Auditorium & Global Stream',
            'status' => 'upcoming',
            'registered_participants' => $student ? [$student->id] : [],
            'podium_winners' => [
                '1st' => 'Quantum Orators (Class 10-A)',
                '2nd' => 'Dialectic Pioneers (Class 9-B)',
                '3rd' => 'The Rhetoricians (Class 8-A)',
            ],
        ]);

        AcademyEvent::firstOrCreate([
            'title' => 'Prime Arc Innovation & Creative Talent Showcase 2026',
        ], [
            'coordinator_id' => $teacher->id,
            'category' => 'talent_show',
            'event_format' => 'individual',
            'description' => 'Annual multidisciplinary talent exhibition celebrating scientific invention, digital arts, classical instrumentation, and dramatic monologues.',
            'rules_and_rubric' => 'Maximum 5-minute performance or live prototype demonstration judged on originality, technical execution, and stage impact.',
            'event_datetime' => now()->addDays(12)->setTime(16, 30),
            'venue' => 'Rabindranath Tagore Cultural Center & Amphitheatre',
            'status' => 'upcoming',
            'registered_participants' => [],
            'podium_winners' => null,
        ]);

        // 4. Badges & Certificates for Sample Student
        if ($student) {
            UserBadge::firstOrCreate([
                'user_id' => $student->id,
                'badge_key' => 'streak_titan',
            ], [
                'title' => 'Streak Titan',
                'icon' => '🔥',
                'category' => 'streaks',
                'description' => 'Maintained a persistent 7+ day active learning streak across subjects!',
                'awarded_at' => now()->subDays(2),
            ]);

            UserBadge::firstOrCreate([
                'user_id' => $student->id,
                'badge_key' => 'speed_quizzer',
            ], [
                'title' => 'Speed Quizzer',
                'icon' => '⚡',
                'category' => 'academics',
                'description' => 'Achieved 100% accuracy in a live competitive quiz sprint!',
                'awarded_at' => now()->subDays(1),
            ]);

            UserCertificate::firstOrCreate([
                'certificate_number' => 'CERT-PA-2026-0001',
            ], [
                'user_id' => $student->id,
                'issuer_id' => $teacher->id,
                'title' => 'Certificate of Academic Distinction in Classical Mechanics',
                'category' => 'academic_excellence',
                'citation' => 'In recognition of superlative mathematical rigor, exemplary problem formulation, and unwavering scholarly discipline throughout the term.',
                'issued_date' => now()->subMonth(),
                'verification_hash' => hash('sha256', 'CERT-PA-2026-0001'.$student->id),
            ]);
        }
    }
}
