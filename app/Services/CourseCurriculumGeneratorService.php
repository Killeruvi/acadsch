<?php

namespace App\Services;

use App\Models\AcademicSubject;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;

class CourseCurriculumGeneratorService
{
    /**
     * Generates a complete, structured curriculum for a course:
     * - Weekly Modules / Sections
     * - Recurring Timetable Schedules
     * - Lessons with Video Lectures, Homework, and Tabbed Formatted Notes
     * - Daily Practice Problems (DPP) with Multiple Choice Questions
     * - Homework Assignments with Rubrics
     * - Weekly Mastery Tests & Assessments
     */
    public static function generateForCourse(Course $course, array $options = []): void
    {
        $weeksCount = (int) ($options['weeks_count'] ?? 4);
        if ($weeksCount < 1) {
            $weeksCount = 4;
        }

        $scheduleDays = $options['schedule_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if (! is_array($scheduleDays) || empty($scheduleDays)) {
            $scheduleDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        }

        $startTime = $options['start_time'] ?? '10:00';
        $endTime = $options['end_time'] ?? '11:30';
        $deliveryMode = $options['delivery_mode'] ?? 'offline';
        $room = $options['room'] ?? 'Academic Hall 101';

        // 1. Generate Timetable Schedules (Always 5 instructional days a week)
        self::generateSchedules($course, $scheduleDays, $startTime, $endTime, $deliveryMode, $room);

        // 2. Generate Modules, Lessons, Videos, Tabbed Notes, DPPs, Homework, and Tests
        $domainThemes = self::resolveDomainThemes($course->title, $course->category);

        $globalLessonCounter = CourseLesson::where('course_id', $course->id)->max('lesson_number') ?? 0;
        $globalDppCounter = DailyDpp::where('course_id', $course->id)->max('dpp_number') ?? 0;

        for ($week = 1; $week <= $weeksCount; $week++) {
            $themeData = $domainThemes[($week - 1) % count($domainThemes)];
            $moduleTitle = "Week {$week}: ".$themeData['title'];

            // Find or create module
            $module = CourseModule::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'week_number' => $week,
                ],
                [
                    'title' => $moduleTitle,
                    'theme' => $themeData['theme'],
                    'description' => "In-depth study and rigorous exploration of {$themeData['theme']} within {$course->title}. Includes structured video lectures, interactive DPPs, homework challenges, and mastery notes.",
                    'learning_goals' => $themeData['goals'],
                    'order_index' => $week,
                ]
            );

            // Generate 2 structured lessons per module
            for ($lessonInWeek = 1; $lessonInWeek <= 2; $lessonInWeek++) {
                $globalLessonCounter++;
                $lessonTitle = ($lessonInWeek === 1)
                    ? "{$themeData['lesson1_title']} ({$course->code})"
                    : "{$themeData['lesson2_title']} & Practical Application";

                $dayOfWeek = ($lessonInWeek === 1) ? 1 : 3; // Monday or Wednesday

                $formattedNotes = self::generateStructuredNotes(
                    $course,
                    $moduleTitle,
                    $lessonTitle,
                    $themeData,
                    $lessonInWeek
                );

                $lesson = CourseLesson::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'lesson_number' => $globalLessonCounter,
                    ],
                    [
                        'module_id' => $module->id,
                        'week_number' => $week,
                        'day_of_week' => $dayOfWeek,
                        'title' => $lessonTitle,
                        'duration_minutes' => 60,
                        'level' => ($week === 1) ? 'foundation' : (($week >= 4) ? 'challenge' : 'standard'),
                        'topic_summary' => "Comprehensive lecture covering {$themeData['theme']}. Focuses on core theory, practical methodologies, and systematic analysis.",
                        'learning_objectives' => [
                            "Master foundational principles of {$themeData['theme']}",
                            'Analyze real-world problem sets and avoid common misconceptions',
                            'Apply theoretical derivations and framework patterns effectively',
                        ],
                        'expected_outcomes' => 'Scholars will be able to construct well-reasoned solutions, interpret relevant data, and articulate key concepts with precision.',
                        'vocabulary' => [
                            $themeData['vocab'][0] ?? 'Principle',
                            $themeData['vocab'][1] ?? 'Methodology',
                            $themeData['vocab'][2] ?? 'Synthesis',
                        ],
                        'concepts' => [
                            $themeData['theme'],
                            'Structured Analytical Frameworks',
                            'Execution & Verification Strategies',
                        ],
                        'teaching_materials' => [
                            'Interactive Lecture Presentation',
                            'Tabbed Cheatsheet & Handout',
                            'Daily Practice Problem Set (DPP)',
                        ],
                        'video_url' => $options['video_url'] ?? null,
                        'pdf_url' => $options['pdf_url'] ?? "/courses/{$course->id}/handouts/week-{$week}-lesson-{$lessonInWeek}.pdf",
                        'homework_task' => 'Complete the weekly analytical problem set, prepare code or written explanations for all exercises, and submit by Sunday 23:59.',
                        'evaluation_criteria' => 'Accuracy of logic (40%), clarity of methodology (30%), edge-case consideration (30%).',
                        'class_notes' => $formattedNotes,
                        'order_index' => $lessonInWeek,
                    ]
                );

                // 3. Create Daily DPP for this lesson/module
                $globalDppCounter++;
                self::generateDppForLesson($course, $lesson, $globalDppCounter, $themeData, $week);
            }

            // 4. Create Homework Assignment for this module
            self::generateAssignmentForModule($course, $module, $week, $themeData);

            // 5. Create Weekly Assessment / Test for this module
            self::generateAssessmentForModule($course, $module, $week, $themeData);
        }
    }

    /**
     * Generates recurring timetable schedules for the course.
     */
    protected static function generateSchedules(Course $course, array $days, string $start, string $end, string $mode, string $room): void
    {
        foreach ($days as $day) {
            Schedule::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'day_of_week' => $day,
                    'start_time' => $start,
                ],
                [
                    'end_time' => $end,
                    'title' => "{$course->code} - Lecture & Discussion",
                    'delivery_mode' => $mode,
                    'room' => $room,
                    'description' => "Weekly recurring lecture session for {$course->title}.",
                ]
            );
        }
    }

    /**
     * Generates rich, tabbed, formatted class notes for a lesson.
     */
    protected static function generateStructuredNotes(Course $course, string $moduleTitle, string $lessonTitle, array $themeData, int $lessonInWeek): array
    {
        return [
            'summary' => "This session provides an in-depth breakdown of {$lessonTitle} within {$course->title}. Learners examine core operational mechanisms, theoretical foundations, and systematic application guidelines.",
            'key_concepts' => [
                [
                    'title' => "1. Theoretical Foundation of {$themeData['theme']}",
                    'description' => "Understanding the primary postulates and axioms that govern {$themeData['theme']}. Critical focus on state transitions, validation boundaries, and underlying principles.",
                    'takeaway' => 'Always verify boundary constraints before applying general formulas.',
                ],
                [
                    'title' => '2. Analytical Framework & Methodologies',
                    'description' => 'Deconstructing complex problems into modular, verifiable steps. Using structured workflows rather than ad-hoc heuristics.',
                    'takeaway' => 'Break down multi-variable challenges into sequential independent stages.',
                ],
                [
                    'title' => '3. Practical Real-World Execution',
                    'description' => "Translating conceptual models into robust implementations, case studies, or architectural patterns in {$course->category}.",
                    'takeaway' => 'Benchmark solutions under edge-case stress conditions.',
                ],
            ],
            'definitions' => [
                [
                    'term' => $themeData['vocab'][0] ?? 'Foundational State',
                    'definition' => 'The initial baseline condition from which all evolutionary computations or transformations commence.',
                    'context' => 'Used as the reference benchmark in performance profiling.',
                ],
                [
                    'term' => $themeData['vocab'][1] ?? 'Invariant Property',
                    'definition' => 'A condition or relationship that remains strictly true across all valid states and iterations.',
                    'context' => 'Guarantees correctness in iterative and concurrent systems.',
                ],
                [
                    'term' => $themeData['vocab'][2] ?? 'Convergence Limit',
                    'definition' => 'The asymptotic threshold toward which successive approximations tend as iterations approach infinity.',
                    'context' => 'Dictates termination criteria in optimization routines.',
                ],
            ],
            'formulas_or_examples' => [
                [
                    'title' => 'Core Working Formula / Execution Pattern',
                    'code_or_formula' => $themeData['formula'] ?? 'f(x) = ∑ [ w_i * φ_i(x) ] + Bias_term',
                    'explanation' => 'Calculates the weighted linear response where w_i represents dimensional parameters and φ_i is the basis transformation function.',
                ],
                [
                    'title' => 'Standard Implementation Pattern',
                    'code_or_formula' => "// Verification Workflow\nfunction validateAndExecute(input) {\n    if (!isValid(input)) throw new Error('Constraint Violation');\n    return computeTransform(input);\n}",
                    'explanation' => 'Defensive execution pattern preventing runtime exceptions and ensuring state determinism.',
                ],
            ],
            'common_mistakes' => [
                [
                    'mistake' => 'Overlooking edge-case boundary conditions during initialization.',
                    'correction' => 'Explicitly validate null, zero, and boundary limits before entering main processing loops.',
                    'rule' => 'Guard clauses must precede computational pipelines.',
                ],
                [
                    'mistake' => 'Assuming linear scaling behavior across non-linear domains.',
                    'correction' => 'Profile with representative multi-scale datasets to accurately observe polynomial or logarithmic traits.',
                    'rule' => 'Empirical benchmarking is required for complexity verification.',
                ],
            ],
            'faq' => [
                [
                    'question' => "What is the most effective approach to mastering {$themeData['theme']}?",
                    'answer' => 'Review the theoretical concept cards, solve the daily DPP set without referencing solutions first, and review the common mistakes section.',
                ],
                [
                    'question' => 'How does this topic relate to the subsequent module?',
                    'answer' => 'The principles established here form the mathematical and architectural prerequisites for upcoming advanced optimization topics.',
                ],
            ],
            'revision_takeaways' => [
                'Review foundational definitions and ensure mastery of the core invariant property.',
                'Solve DPP problem set within the allotted 30-minute test window.',
                'Complete weekly homework assignment prior to Sunday midnight deadline.',
                'Review the downloadable lecture PDF handout for supplemental reference material.',
            ],
        ];
    }

    /**
     * Generates a Daily Practice Problem (DPP) with 4 questions.
     */
    protected static function generateDppForLesson(Course $course, CourseLesson $lesson, int $dppNumber, array $themeData, int $week): void
    {
        $academicSubject = AcademicSubject::with('academicClass')
            ->where(function ($query) use ($course) {
                $query->whereRaw('UPPER(name) = ?', [mb_strtoupper($course->title)])
                    ->orWhereRaw('UPPER(code) = ?', [mb_strtoupper((string) $course->code)]);
            })->first();
        $academicClass = $academicSubject?->academicClass;
        if (! $academicSubject || ! $academicClass) {
            // Course DPP generation is skipped until an administrator maps this course to a real class subject.
            return;
        }

        $teacherId = $course->teacher_id ?? $academicSubject->teacher_id;
        if (! $teacherId || ! User::whereKey($teacherId)->whereIn('role', ['teacher', 'admin'])->exists()) {
            return;
        }

        $dpp = DailyDpp::firstOrCreate(
            [
                'course_id' => $course->id,
                'dpp_number' => $dppNumber,
            ],
            [
                'lesson_id' => $lesson->id,
                'class_id' => $academicClass->id,
                'subject_id' => $academicSubject->id,
                'teacher_id' => $teacherId,
                'title' => "DPP {$dppNumber}: {$themeData['theme']} Problem Set",
                'type' => 'dpp',
                'assigned_date' => Carbon::now()->addDays($dppNumber),
                'due_time' => '23:59:00',
                'total_marks' => 20,
                'difficulty' => ($week === 1) ? 'easy' : (($week >= 3) ? 'hard' : 'medium'),
                'duration_minutes' => 30,
                'is_timed' => true,
                'pass_percentage' => 60,
                'negative_marks' => 0.25,
            ]
        );

        if ($dpp->questions()->count() === 0) {
            $questionsData = [
                [
                    'q' => "In {$course->title}, what is the primary role of {$themeData['vocab'][0]} in establishing state consistency?",
                    'a' => 'It guarantees invariant conditions are preserved across transitions.',
                    'b' => 'It maximizes runtime latency and increases complexity.',
                    'c' => 'It bypasses validation constraints during execution.',
                    'd' => 'It randomly reorganizes parameter weights.',
                    'correct' => 'a',
                    'explanation' => 'The primary purpose is to ensure that invariant properties remain strictly unviolated throughout the system lifecycle.',
                ],
                [
                    'q' => "Which of the following best describes the asymptotic behavior of {$themeData['theme']} under standard conditions?",
                    'a' => 'Exponential explosion with unbounded divergence.',
                    'b' => 'Monotonic convergence toward optimal equilibrium.',
                    'c' => 'Random oscillatory divergence without termination.',
                    'd' => 'Immediate saturation to zero.',
                    'correct' => 'b',
                    'explanation' => 'Under regular boundary constraints, the governing equations exhibit monotonic convergence toward an optimal stationary point.',
                ],
                [
                    'q' => 'When evaluating edge cases, which condition requires immediate defensive handling?',
                    'a' => 'Inputs falling strictly within normal operating bounds.',
                    'b' => 'Null references, zero division, or out-of-range parameters.',
                    'c' => 'High-speed network throughput.',
                    'd' => 'Sufficient memory allocation.',
                    'correct' => 'b',
                    'explanation' => 'Boundary violations such as zero division, uninitialized pointers, or null values must be trapped immediately via guard clauses.',
                ],
                [
                    'q' => 'What is the recommended verification step after executing the transformation algorithm?',
                    'a' => 'Ignore all output values and proceed immediately.',
                    'b' => 'Assert output against analytical expectations and residual tolerance.',
                    'c' => 'Restart the system without logging.',
                    'd' => 'Invert all arithmetic signs.',
                    'correct' => 'b',
                    'explanation' => 'Residual validation and assertion checking verify that computed solutions fall within acceptable numerical tolerances.',
                ],
            ];

            foreach ($questionsData as $idx => $qData) {
                DppQuestion::create([
                    'dpp_id' => $dpp->id,
                    'question_number' => $idx + 1,
                    'question_text' => $qData['q'],
                    'option_a' => $qData['a'],
                    'option_b' => $qData['b'],
                    'option_c' => $qData['c'],
                    'option_d' => $qData['d'],
                    'correct_option' => $qData['correct'],
                    'explanation' => $qData['explanation'],
                    'marks' => 5,
                ]);
            }
        }
    }

    /**
     * Generates a Homework Assignment for a module.
     */
    protected static function generateAssignmentForModule(Course $course, CourseModule $module, int $week, array $themeData): void
    {
        $teacherId = $course->teacher_id ?? (User::whereIn('role', ['teacher', 'admin'])->first()?->id ?? 1);

        Assignment::firstOrCreate(
            [
                'course_id' => $course->id,
                'title' => "Assignment {$week}: {$themeData['theme']} Application",
            ],
            [
                'teacher_id' => $teacherId,
                'description' => "Complete the analytical case study and problem exercises for {$module->title}. Provide full derivations, working code or diagrams, and a concise summary explaining your design decisions. Submit as PDF or Markdown text.",
                'due_date' => Carbon::now()->addDays(7 * $week),
                'max_points' => 100,
            ]
        );
    }

    /**
     * Generates a Course Assessment / Test for a module.
     */
    protected static function generateAssessmentForModule(Course $course, CourseModule $module, int $week, array $themeData): void
    {
        CourseAssessment::firstOrCreate(
            [
                'course_id' => $course->id,
                'title' => "{$course->code} - Module {$week} Mastery Test",
            ],
            [
                'module_id' => $module->id,
                'type' => 'weekly',
                'week_number' => $week,
                'description' => "Comprehensive timed assessment evaluating conceptual understanding, problem-solving speed, and analytical rigor for {$module->title}.",
                'total_points' => 100,
                'passing_score' => 60,
                'time_limit_minutes' => 45,
                'is_published' => true,
                'rubric' => [
                    'concept_accuracy' => '40 points for precision in logic and core principles',
                    'methodology' => '30 points for structured step-by-step problem deconstruction',
                    'edge_cases' => '30 points for identifying potential boundary failures and mitigations',
                ],
                'questions' => [
                    [
                        'id' => 1,
                        'question' => "Explain the fundamental theorem governing {$themeData['theme']} and provide one practical application.",
                        'points' => 20,
                    ],
                    [
                        'id' => 2,
                        'question' => "Derive the mathematical or architectural expression for {$themeData['vocab'][0]} and analyze its complexity.",
                        'points' => 20,
                    ],
                    [
                        'id' => 3,
                        'question' => 'Identify two common anti-patterns or mistakes observed in this domain and detail the recommended corrective strategies.',
                        'points' => 20,
                    ],
                    [
                        'id' => 4,
                        'question' => "Analyze the boundary conditions for {$themeData['theme']} under resource-constrained execution.",
                        'points' => 20,
                    ],
                    [
                        'id' => 5,
                        'question' => 'Synthesize a comprehensive solution combining the theoretical model with empirical validation.',
                        'points' => 20,
                    ],
                ],
            ]
        );
    }

    /**
     * Resolves tailored curriculum themes based on course title & category.
     */
    protected static function resolveDomainThemes(string $title, string $category): array
    {
        $text = strtolower($title.' '.$category);

        if (str_contains($text, 'cs') || str_contains($text, 'comput') || str_contains($text, 'data') || str_contains($text, 'code') || str_contains($text, 'program')) {
            return [
                [
                    'title' => 'Core Architecture & Data Structures',
                    'theme' => 'Memory Layout, Data Modeling & Algorithmic Complexity',
                    'goals' => ['Analyze Big-O time and space complexity', 'Implement core abstract data structures', 'Prevent memory leaks and invalid state references'],
                    'lesson1_title' => 'Fundamental Memory Models & Computational Primitives',
                    'lesson2_title' => 'Efficient Algorithmic Decomposition & Complexity Profiling',
                    'vocab' => ['Encapsulation', 'Asymptotic Complexity', 'Dynamic Allocation'],
                    'formula' => 'T(n) = a * T(n/b) + O(n^d) [Master Theorem]',
                    'video_url' => 'https://www.youtube.com/embed/8jLOx1hD3_o',
                ],
                [
                    'title' => 'Design Patterns & System Modularity',
                    'theme' => 'Object-Oriented Design & Clean Architecture',
                    'goals' => ['Apply SOLID principles to scalable design', 'Implement creational and behavioral patterns', 'Construct unit and integration test harnesses'],
                    'lesson1_title' => 'SOLID Architectural Principles & Dependency Injection',
                    'lesson2_title' => 'Resilient Design Patterns & Fault-Tolerant Pipelines',
                    'vocab' => ['Polymorphism', 'Decoupling', 'Idempotency'],
                    'formula' => 'Cohesion_Score = Internal_Interactions / Total_Boundaries',
                    'video_url' => 'https://www.youtube.com/embed/v9ejT8FO-7I',
                ],
                [
                    'title' => 'Concurrency, Pipelines & Parallelism',
                    'theme' => 'Multithreading, Synchronization & Asynchronous Execution',
                    'goals' => ['Avoid deadlocks and race conditions', 'Build event-driven asynchronous pipelines', 'Profile concurrency throughput and CPU utilization'],
                    'lesson1_title' => 'Thread Safety, Locks & Mutex Orchestration',
                    'lesson2_title' => 'Reactive Streams & Asynchronous Event Processing',
                    'vocab' => ['Deadlock', 'Atomic Operation', 'Backpressure'],
                    'formula' => 'Speedup = 1 / [ (1 - P) + (P / N) ] [Amdahl\'s Law]',
                    'video_url' => 'https://www.youtube.com/embed/7ENFeb-J75k',
                ],
                [
                    'title' => 'Performance Optimization, Security & Capstone',
                    'theme' => 'Hardening, Caching Strategies & Production Deployment',
                    'goals' => ['Harden input vectors against injection exploits', 'Implement multi-tier distributed caching', 'Deploy end-to-end verified project'],
                    'lesson1_title' => 'Defensive Engineering, Security Auditing & Sanitation',
                    'lesson2_title' => 'High-Throughput Optimization & Capstone Synthesis',
                    'vocab' => ['Sanitization', 'Cache Invalidation', 'Scalability'],
                    'formula' => 'Throughput = Requests_Served / Latency_Window',
                    'video_url' => 'https://www.youtube.com/embed/8aGhZQkoFbQ',
                ],
            ];
        }

        if (str_contains($text, 'physic') || str_contains($text, 'chem') || str_contains($text, 'math') || str_contains($text, 'bio') || str_contains($text, 'science')) {
            return [
                [
                    'title' => 'Foundational Principles & Classical Mechanics',
                    'theme' => 'Core Laws, Dimensional Analysis & Physical States',
                    'goals' => ['Master fundamental conservation laws', 'Execute precise dimensional analysis', 'Calculate state variables under equilibrium'],
                    'lesson1_title' => 'Newtonian Mechanics, Forces & Conservation Laws',
                    'lesson2_title' => 'Equilibrium Systems & Vector Decomposition',
                    'vocab' => ['Equilibrium', 'Momentum Conservation', 'Thermodynamics'],
                    'formula' => 'F_net = m * (dv/dt) ; E_total = K + U',
                    'video_url' => 'https://www.youtube.com/embed/b1t41Q3xRM8',
                ],
                [
                    'title' => 'Field Dynamics, Oscillations & Waves',
                    'theme' => 'Wave Equations, Energy Transfer & Resonance',
                    'goals' => ['Model simple harmonic motion', 'Solve boundary value wave equations', 'Analyze electromagnetic field propagation'],
                    'lesson1_title' => 'Simple Harmonic Motion & Damped Oscillations',
                    'lesson2_title' => 'Wave Superposition, Interference & Waveguides',
                    'vocab' => ['Resonance', 'Superposition', 'Phase Velocity'],
                    'formula' => '∂²y/∂x² = (1/v²) * (∂²y/∂t²)',
                    'video_url' => 'https://www.youtube.com/embed/mBfZ_u_8gN4',
                ],
                [
                    'title' => 'Electromagnetism & Thermodynamics',
                    'theme' => 'Maxwell Relations, Heat Engines & Entropy',
                    'goals' => ['Calculate magnetic flux and electromotive force', 'Apply entropy and Carnot cycle limits', 'Solve kinetic theory problems'],
                    'lesson1_title' => 'Gauss, Faraday & Maxwell Equations',
                    'lesson2_title' => 'Entropy, Free Energy & Thermodynamic Work',
                    'vocab' => ['Entropy', 'Electromotive Force', 'Carnot Limit'],
                    'formula' => '∮ E · dA = Q_enclosed / ε₀ ; ΔS ≥ 0',
                    'video_url' => 'https://www.youtube.com/embed/k4Jk1_n2k70',
                ],
                [
                    'title' => 'Modern Quantum Phenomena & Capstone Review',
                    'theme' => 'Wave-Particle Duality, Quantum States & Final Synthesis',
                    'goals' => ['Explain photoelectric effect and de Broglie wavelength', 'Apply 1D Schrödinger wave mechanics', 'Complete comprehensive final review'],
                    'lesson1_title' => 'Wave-Particle Duality & Probability Amplitudes',
                    'lesson2_title' => 'Quantum Wells, Uncertainty & Capstone Synthesis',
                    'vocab' => ['Probability Amplitude', 'Eigenstate', 'Uncertainty Principle'],
                    'formula' => 'iħ (∂Ψ/∂t) = ĤΨ [Schrödinger Equation]',
                    'video_url' => 'https://www.youtube.com/embed/p7bzE1E5pmY',
                ],
            ];
        }

        // Generic comprehensive academic domain
        return [
            [
                'title' => 'Foundations & Conceptual Terminology',
                'theme' => 'Core Definitions, Historical Context & Principles',
                'goals' => ['Define key domain terms', 'Understand foundational history and context', 'Identify baseline operating assumptions'],
                'lesson1_title' => 'Introduction, Core Definitions & Baseline Theory',
                'lesson2_title' => 'Taxonomy, Models & Foundational Exercises',
                'vocab' => ['Axiom', 'Methodology', 'Paradigm'],
                'formula' => 'Value_Efficiency = Quality_Output / Energy_Input',
                'video_url' => null,
            ],
            [
                'title' => 'Analytical Frameworks & Methodologies',
                'theme' => 'Step-by-Step Deconstruction & Analysis',
                'goals' => ['Structure multi-step evaluations', 'Apply quantitative and qualitative frameworks', 'Critique standard industry practices'],
                'lesson1_title' => 'Structured Frameworks & Problem Breakdown',
                'lesson2_title' => 'Data-Driven Validation & Case Methodologies',
                'vocab' => ['Hypothesis', 'Correlation', 'Empirical Proof'],
                'formula' => 'Confidence_Interval = μ ± Z * (σ / √n)',
                'video_url' => null,
            ],
            [
                'title' => 'Intermediate Synthesis & Practical Problems',
                'theme' => 'Complex Case Studies & Cross-Domain Challenges',
                'goals' => ['Solve non-trivial real-world cases', 'Synthesize competing perspectives', 'Design structured solutions'],
                'lesson1_title' => 'Case Study Deconstructions & Complex Scenarios',
                'lesson2_title' => 'Optimization Strategies & Problem Sets',
                'vocab' => ['Trade-off', 'Optimization', 'Sustainability'],
                'formula' => 'ROI = (Net_Gain / Cost_Investment) * 100',
                'video_url' => null,
            ],
            [
                'title' => 'Advanced Mastery, Capstone Project & Review',
                'theme' => 'Comprehensive Integration & Evaluation',
                'goals' => ['Defend independent capstone conclusions', 'Review all key exam topics', 'Achieve full course mastery'],
                'lesson1_title' => 'Advanced System Integration & Capstone Practice',
                'lesson2_title' => 'Comprehensive Course Synthesis & Review',
                'vocab' => ['Capstone', 'Mastery', 'Verification'],
                'formula' => 'Mastery_Score = (0.4 * DPP) + (0.3 * Homework) + (0.3 * Tests)',
                'video_url' => null,
            ],
        ];
    }
}
