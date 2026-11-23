<?php

namespace App\Services;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\LessonPlan;
use App\Models\User;
use Carbon\Carbon;

class AcademicCalendarService
{
    /**
     * Check if a given date is non-instructional (configured weekend or declared closure).
     */
    public static function isNonInstructionalDay(Carbon $date): bool
    {
        // Weekend policy is shared with attendance and is institution-configurable.
        if (in_array($date->format('l'), config('academic_calendar.weekend_days', config('attendance.weekend_days', ['Sunday'])), true)) {
            return true;
        }

        // Check if date falls in declared holiday/festival
        $holiday = AcademicHoliday::findHolidayForDate($date);

        return $holiday !== null;
    }

    /**
     * Returns the matching AcademicHoliday if date falls on one.
     */
    public static function getHolidayForDate(Carbon $date): ?AcademicHoliday
    {
        return AcademicHoliday::findHolidayForDate($date);
    }

    /**
     * Returns the next available instructional day (skipping configured weekends and declared closures).
     */
    public static function getNextInstructionalDay(Carbon $startDate): Carbon
    {
        $date = $startDate->copy();

        while (self::isNonInstructionalDay($date)) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Automatically schedule a sequence of lessons across topics, respecting all holidays & festivals.
     */
    public static function generateSubjectLessonPlans(
        AcademicSubject $subject,
        Carbon $startDate,
        ?int $teacherId = null,
        string $term = 'Term 1'
    ): int {
        $topics = $subject->topics()->orderBy('chapter_number')->get();
        if ($topics->isEmpty()) {
            return 0;
        }

        $currentDate = self::getNextInstructionalDay($startDate);
        $lessonNumber = 1;
        $orderIndex = 1;
        $activeHolidayShift = null;

        $pedagogyRotation = [
            'Interactive Concept Lecture',
            'Laboratory Practical & Experiment',
            'Problem Solving & DPP Drill',
            'Case Study & Inquiry Seminar',
            'Bloom Cognitive Challenge Workshop',
        ];

        $bloomFocusRotation = [
            'Remember & Understand',
            'Understand & Apply',
            'Apply & Analyze',
            'Analyze & Evaluate',
            'Evaluate & Create',
        ];

        $periodSlots = [
            'Period 1 (08:30 - 09:30)',
            'Period 2 (09:40 - 10:40)',
            'Period 3 (11:00 - 12:00)',
            'Period 4 (12:10 - 01:10)',
        ];

        $created = 0;

        foreach ($topics as $topic) {
            // Plan 3-4 structured lessons per topic chapter
            $subtopics = is_array($topic->subtopics) && ! empty($topic->subtopics)
                ? $topic->subtopics
                : [
                    'Foundations, Core Principles & Notation',
                    'Mechanisms, Problem Formulations & Experiments',
                    'Advanced Applications, Synthesis & Exam Preparation',
                ];

            foreach ($subtopics as $sIdx => $subtopicTitle) {
                $status = 'planned';
                $holidayNote = null;
                $holidayId = null;

                // Check if current session was shifted due to a holiday/festival
                if ($activeHolidayShift) {
                    $status = 'holiday_shift';
                    $holidayId = $activeHolidayShift->id;
                    $holidayNote = "Deferred due to {$activeHolidayShift->badge_icon} {$activeHolidayShift->name}. Rescheduled to {$currentDate->format('D, M d')}.";
                    $activeHolidayShift = null;
                }

                $pedagogy = $pedagogyRotation[($lessonNumber - 1) % count($pedagogyRotation)];
                $bloomFocus = $bloomFocusRotation[($lessonNumber - 1) % count($bloomFocusRotation)];
                $slot = $periodSlots[($lessonNumber - 1) % count($periodSlots)];

                $labText = null;
                if (str_contains($pedagogy, 'Laboratory') || str_contains($pedagogy, 'Experiment')) {
                    $labText = "Hands-on demonstration / lab kit exercise on {$subtopicTitle}. Observe apparatus and record measurements in lab portfolio.";
                }

                LessonPlan::updateOrCreate(
                    [
                        'class_id' => $subject->class_id,
                        'subject_id' => $subject->id,
                        'topic_id' => $topic->id,
                        'lesson_number' => $lessonNumber,
                    ],
                    [
                        'teacher_id' => $teacherId,
                        'activity_type' => 'regular',
                        'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." L{$lessonNumber}: {$subtopicTitle}",
                        'subtopics' => $subtopicTitle,
                        'learning_objectives' => "Master concept fundamentals of {$subtopicTitle}. Able to formulate solutions, explain mechanisms, and clear topic DPP questions with 80%+ accuracy.",
                        'pedagogy' => $pedagogy,
                        'planned_date' => $currentDate->toDateString(),
                        'period_slot' => $slot,
                        'bloom_focus' => $bloomFocus,
                        'resources_needed' => "Course textbook chapter {$topic->chapter_number}, curriculum notes PDF, scientific apparatus or calculator.",
                        'practical_lab' => $labText,
                        'homework_assignment' => "Complete DPP practice set for {$topic->title} (Part ".($sIdx + 1).') and review summary definitions.',
                        'status' => $status,
                        'holiday_id' => $holidayId,
                        'holiday_note' => $holidayNote,
                        'term' => $term,
                        'order_index' => $orderIndex++,
                    ]
                );

                $created++;
                $lessonNumber++;

                // Advance by 2 calendar days
                $rawNext = $currentDate->copy()->addDays(2);
                if ($rawNext->isSunday()) {
                    $rawNext->addDay();
                }

                // Check if this falls on a declared holiday or festival
                $hitHoliday = self::getHolidayForDate($rawNext);
                if ($hitHoliday) {
                    $activeHolidayShift = $hitHoliday;
                    $currentDate = self::getNextInstructionalDay($rawNext->copy()->addDay());
                } else {
                    $currentDate = self::getNextInstructionalDay($rawNext);
                }
            }

            // Chapter-mapped Milestone Activity: Competition, Project, Debate, or Symposium
            $milestoneType = match ($topic->chapter_number % 4) {
                1 => 'competition',
                2 => 'project',
                3 => 'debate',
                0 => 'symposium',
            };

            $milestoneTitle = match ($milestoneType) {
                'competition' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🏆 Competitions Day: {$topic->title} Olympiad Sprint",
                'project' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🚀 Projects Day: {$topic->title} Hands-on Model",
                'debate' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🗣️ Debates Day: {$topic->title} Academic Discourse",
                'symposium' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🔬 Symposium Day: {$topic->title} Research Discovery",
            };

            LessonPlan::updateOrCreate(
                [
                    'class_id' => $subject->class_id,
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'lesson_number' => $lessonNumber,
                ],
                [
                    'teacher_id' => $teacherId,
                    'activity_type' => $milestoneType,
                    'title' => $milestoneTitle,
                    'subtopics' => "Milestone {$milestoneType} activity testing and applying {$topic->title}",
                    'learning_objectives' => "Mastery evaluation through {$milestoneType} for {$topic->title}",
                    'pedagogy' => ucfirst($milestoneType).' Workshop & Presentation',
                    'planned_date' => $currentDate->toDateString(),
                    'period_slot' => $periodSlots[($lessonNumber - 1) % count($periodSlots)],
                    'bloom_focus' => 'Analyze, Evaluate & Synthesize',
                    'resources_needed' => 'Competition / Project kit and presentation rubric.',
                    'practical_lab' => ($milestoneType === 'project' ? 'Project prototyping and testing.' : null),
                    'homework_assignment' => 'Document findings and reflection in chapter journal.',
                    'status' => 'planned',
                    'term' => $term,
                    'order_index' => $orderIndex++,
                ]
            );
            $created++;
            $lessonNumber++;

            // Chapter Revision Day: Formula Marathon & Synoptic Recall
            $revisionDate = self::getNextInstructionalDay($currentDate->copy()->addDays(2));
            LessonPlan::updateOrCreate(
                [
                    'class_id' => $subject->class_id,
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'lesson_number' => $lessonNumber,
                ],
                [
                    'teacher_id' => $teacherId,
                    'activity_type' => 'revision',
                    'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🔄 Revision Day: {$topic->title} Mind-Map & Formula Sprint",
                    'subtopics' => "Comprehensive synoptic revision, formula derivations, concept mapping, and flashcard recap for {$topic->title}",
                    'learning_objectives' => 'Consolidate active recall, resolve lingering doubt queries, and achieve formula fluency prior to chapter test.',
                    'pedagogy' => 'Active Recall Drill & Synoptic Mind-Mapping',
                    'planned_date' => $revisionDate->toDateString(),
                    'period_slot' => $periodSlots[($lessonNumber - 1) % count($periodSlots)],
                    'bloom_focus' => 'Remember, Analyze & Synthesize',
                    'resources_needed' => 'Formula cheat sheets, blank mind-map templates, flashcards, and quick-fire quiz deck.',
                    'practical_lab' => null,
                    'homework_assignment' => 'Self-timed 30-minute flashcard drill and review formula derivation cheat sheet.',
                    'status' => 'planned',
                    'term' => $term,
                    'order_index' => $orderIndex++,
                ]
            );
            $created++;
            $lessonNumber++;

            // Chapter Test Day
            $testDate = self::getNextInstructionalDay($revisionDate->copy()->addDays(2));
            LessonPlan::updateOrCreate(
                [
                    'class_id' => $subject->class_id,
                    'subject_id' => $subject->id,
                    'topic_id' => $topic->id,
                    'lesson_number' => $lessonNumber,
                ],
                [
                    'teacher_id' => $teacherId,
                    'activity_type' => 'test',
                    'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 📝 Chapter Test Day: {$topic->title} Full Exam Mock",
                    'subtopics' => "Timed 60-minute examination under exam conditions on {$topic->title}",
                    'learning_objectives' => "Summative mastery assessment on {$topic->title}. 85%+ target score.",
                    'pedagogy' => 'Chapter Mastery Examination & Error Analysis',
                    'planned_date' => $testDate->toDateString(),
                    'period_slot' => $periodSlots[($lessonNumber - 1) % count($periodSlots)],
                    'bloom_focus' => 'Evaluate & Formulate Under Time Pressure',
                    'resources_needed' => 'Question paper, OMR sheets, marking rubric.',
                    'practical_lab' => null,
                    'homework_assignment' => 'Complete error analysis log for missed questions.',
                    'status' => 'planned',
                    'term' => $term,
                    'order_index' => $orderIndex++,
                ]
            );
            $created++;
            $lessonNumber++;

            // Advance date after chapter test
            $rawNext = $testDate->copy()->addDays(2);
            if ($rawNext->isSunday()) {
                $rawNext->addDay();
            }
            $hitHoliday = self::getHolidayForDate($rawNext);
            if ($hitHoliday) {
                $activeHolidayShift = $hitHoliday;
                $currentDate = self::getNextInstructionalDay($rawNext->copy()->addDay());
            } else {
                $currentDate = self::getNextInstructionalDay($rawNext);
            }
        }

        return $created;
    }

    /**
     * Synchronize official academic national holidays & festivals for a given calendar year.
     */
    public static function syncOfficialHolidays(int $year): void
    {
        $holidays = [
            [
                'name' => 'Makar Sankranti / Pongal',
                'holiday_date' => "{$year}-01-14",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🪁',
                'description' => 'Harvest festival celebrated nationwide marking the sun transition into Makara rashi.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Republic Day of India',
                'holiday_date' => "{$year}-01-26",
                'end_date' => null,
                'type' => 'national',
                'badge_icon' => '🇮🇳',
                'description' => 'Commemorating the enactment of the Constitution of India in 1950.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Maha Shivratri',
                'holiday_date' => "{$year}-02-15",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🔱',
                'description' => 'Sacred festival of Shiva celebrating overcoming darkness and ignorance.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Holi (Festival of Colors)',
                'holiday_date' => "{$year}-03-04",
                'end_date' => "{$year}-03-05",
                'type' => 'festival',
                'badge_icon' => '🎨',
                'description' => 'Celebration of spring, love, and the victory of good over evil.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Eid-ul-Fitr',
                'holiday_date' => "{$year}-03-21",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🌙',
                'description' => 'Islamic festival celebrating the conclusion of the holy month of Ramadan.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Dr. B.R. Ambedkar Jayanti / Baisakhi',
                'holiday_date' => "{$year}-04-14",
                'end_date' => null,
                'type' => 'national',
                'badge_icon' => '⚖️',
                'description' => 'Honoring the principal architect of the Indian Constitution and harvest.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'May Day / International Workers Day',
                'holiday_date' => "{$year}-05-01",
                'end_date' => null,
                'type' => 'national',
                'badge_icon' => '🛠️',
                'description' => 'Commemorating labor rights and the contribution of workers.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Institutional Summer Vacation',
                'holiday_date' => "{$year}-05-25",
                'end_date' => "{$year}-06-14",
                'type' => 'vacation',
                'badge_icon' => '☀️',
                'description' => 'Annual mid-year academic recess for students and faculty development.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Independence Day of India',
                'holiday_date' => "{$year}-08-15",
                'end_date' => null,
                'type' => 'national',
                'badge_icon' => '🇮🇳',
                'description' => 'Celebrating national independence achieved on 15 August 1947.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Raksha Bandhan',
                'holiday_date' => "{$year}-08-27",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🧵',
                'description' => 'Traditional celebration honoring mutual protection, care, and duty.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Krishna Janmashtami',
                'holiday_date' => "{$year}-09-04",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🦚',
                'description' => 'Celebrating the birth of Lord Krishna with cultural performances.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Ganesh Chaturthi',
                'holiday_date' => "{$year}-09-14",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🐘',
                'description' => 'Festival of wisdom, new beginnings, and intellectual attainment.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Mahatma Gandhi Jayanti',
                'holiday_date' => "{$year}-10-02",
                'end_date' => null,
                'type' => 'national',
                'badge_icon' => '🕊️',
                'description' => 'National holiday commemorating the birth of the Father of the Nation.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Maha Navami & Dussehra / Vijayadashami',
                'holiday_date' => "{$year}-10-19",
                'end_date' => "{$year}-10-21",
                'type' => 'festival',
                'badge_icon' => '🏹',
                'description' => 'Celebration of truth, righteousness, and the triumph of knowledge.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Diwali / Deepavali & Govardhan Puja',
                'holiday_date' => "{$year}-11-08",
                'end_date' => "{$year}-11-11",
                'type' => 'festival',
                'badge_icon' => '🪔',
                'description' => 'Festival of Lights symbolizing victory of light over dark, knowledge over ignorance.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Guru Nanak Jayanti',
                'holiday_date' => "{$year}-11-24",
                'end_date' => null,
                'type' => 'festival',
                'badge_icon' => '🪯',
                'description' => 'Honoring the birth and spiritual teachings of Guru Nanak Dev Ji.',
                'is_instructional_off' => true,
            ],
            [
                'name' => 'Christmas & Winter Recess',
                'holiday_date' => "{$year}-12-24",
                'end_date' => "{$year}-12-31",
                'type' => 'vacation',
                'badge_icon' => '🎄',
                'description' => 'Annual winter institutional break and Christmas celebration.',
                'is_instructional_off' => true,
            ],
        ];

        $academicYear = $year.'-'.($year + 1);

        foreach ($holidays as $h) {
            AcademicHoliday::firstOrCreate(
                [
                    'holiday_date' => $h['holiday_date'],
                    'name' => $h['name'],
                ],
                array_merge($h, ['academic_year' => $academicYear])
            );
        }
    }

    /**
     * Generates a complete 1-year synchronized curriculum plan for all subjects of a class,
     * where on every single instructional day ALL subjects are taught in parallel.
     */
    public static function generateSynchronizedOneYearPlan(
        AcademicClass $class,
        Carbon $startDate,
        ?int $teacherId = null,
        int $targetInstructionalDays = 200,
        string $densityMode = 'all' // 'single', 'double', 'triple', 'all'
    ): array {
        self::syncOfficialHolidays($startDate->year);
        self::syncOfficialHolidays($startDate->year + 1);

        $subjects = $class->subjects()->with(['topics' => fn ($q) => $q->orderBy('chapter_number')])->orderBy('id')->get();
        if ($subjects->isEmpty()) {
            return ['days' => 0, 'sessions' => 0, 'subjects_count' => 0];
        }

        if (! $teacherId) {
            $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();
            $teacherId = $teacher?->id;
        }

        $periodSlots = [
            0 => 'Period 1 (08:30 - 09:30)',
            1 => 'Period 2 (09:40 - 10:40)',
            2 => 'Period 3 (11:00 - 12:00)',
            3 => 'Period 4 (12:10 - 01:10)',
            4 => 'Period 5 (01:40 - 02:40)',
            5 => 'Period 6 (02:50 - 03:50)',
        ];

        $pedagogyRotation = [
            'Interactive Concept Lecture',
            'Laboratory Practical & Experiment',
            'Problem Solving & DPP Drill',
            'Case Study & Inquiry Seminar',
            'Bloom Cognitive Challenge Workshop',
            'Formative Review & Mastery Check',
        ];

        $bloomFocusRotation = [
            'Remember & Understand',
            'Understand & Apply',
            'Apply & Analyze',
            'Analyze & Evaluate',
            'Evaluate & Create',
            'Remember, Analyze & Synthesize',
        ];

        // 1. Pre-build rich curriculum catalogs for each subject including Competitions, Projects, and Debates Days
        $subjectCatalogs = [];
        foreach ($subjects as $subject) {
            $topics = $subject->topics;
            $catalog = [];
            $lessonNum = 1;
            $chapterCount = 0;

            if ($topics->isNotEmpty()) {
                foreach ($topics as $topic) {
                    $chapterCount++;
                    $subtopics = is_array($topic->subtopics) && ! empty($topic->subtopics)
                        ? $topic->subtopics
                        : [
                            'Foundations, Core Principles & Notation',
                            'Mechanisms, Problem Formulations & Experiments',
                            'Advanced Applications, Synthesis & Exam Preparation',
                        ];

                    foreach ($subtopics as $sIdx => $st) {
                        $catalog[] = [
                            'topic_id' => $topic->id,
                            'activity_type' => 'regular',
                            'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." L{$lessonNum}: {$st}",
                            'subtopics' => $st,
                            'learning_objectives' => "Master concept fundamentals of {$st} in {$subject->name}. Formulate solutions and achieve 80%+ accuracy on DPP practice.",
                            'pedagogy' => $pedagogyRotation[($lessonNum - 1) % count($pedagogyRotation)],
                            'bloom_focus' => $bloomFocusRotation[($lessonNum - 1) % count($bloomFocusRotation)],
                            'resources_needed' => "Course textbook chapter {$topic->chapter_number}, curriculum notes, scientific calculator or lab kit.",
                            'practical_lab' => (($lessonNum % 3 === 0) ? "Hands-on demonstration / lab kit exercise on {$st}. Record observations in journal." : null),
                            'homework_assignment' => "Complete daily homework worksheet for {$st} and review summary notes.",
                        ];
                        $lessonNum++;
                    }

                    // Chapter-mapped Milestone Activity: Competitions Day, Projects Day, Debates Day, or Symposium Day
                    $milestoneType = match ($chapterCount % 4) {
                        1 => 'competition',
                        2 => 'project',
                        3 => 'debate',
                        0 => 'symposium',
                    };

                    if ($milestoneType === 'competition') {
                        $catalog[] = [
                            'topic_id' => $topic->id,
                            'activity_type' => 'competition',
                            'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🏆 Competitions Day: {$topic->title} Olympiad & Problem Sprint",
                            'subtopics' => "Timed Olympiad sprint, buzzer quiz rounds, and multi-step speed problem drill on {$topic->title}",
                            'learning_objectives' => "Test deep analytical problem solving under competitive exam conditions. Rank leaderboard standing on {$topic->title}.",
                            'pedagogy' => 'Competitive Olympiad Sprint & Quiz Bowl',
                            'bloom_focus' => 'Analyze, Evaluate & Rapid Problem Solving',
                            'resources_needed' => 'Competition problem booklet, countdown timer, scoring rubric, and live buzzer system.',
                            'practical_lab' => null,
                            'homework_assignment' => 'Review Olympiad solutions and analyze errors in competition journal.',
                        ];
                    } elseif ($milestoneType === 'project') {
                        $catalog[] = [
                            'topic_id' => $topic->id,
                            'activity_type' => 'project',
                            'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🚀 Projects Day: {$topic->title} Hands-on Model & Capstone",
                            'subtopics' => "Construct functional physical apparatus, experimental prototype, or software model based on {$topic->title}",
                            'learning_objectives' => "Apply theoretical principles of {$topic->title} to design, build, and demonstrate an authentic working project or case model.",
                            'pedagogy' => 'Project-Based Prototyping & Exhibition',
                            'bloom_focus' => 'Create, Synthesize & Construct Prototype',
                            'resources_needed' => 'STEM lab kit, fabrication supplies, project poster board, measuring apparatus.',
                            'practical_lab' => "Assemble and test functional model demonstrating {$topic->title}. Record trial logs and measurements.",
                            'homework_assignment' => 'Finalize 2-page project documentation sheet with schematic diagram.',
                        ];
                    } elseif ($milestoneType === 'debate') {
                        $catalog[] = [
                            'topic_id' => $topic->id,
                            'activity_type' => 'debate',
                            'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🗣️ Debates Day: {$topic->title} Ethics, Innovation & Policy Forum",
                            'subtopics' => "Parliamentary debate motion on societal impact, emerging technology, and controversial applications of {$topic->title}",
                            'learning_objectives' => "Articulate persuasive scientific evidence, formulate rebuttal arguments, and critically evaluate real-world dilemmas of {$topic->title}.",
                            'pedagogy' => 'Parliamentary Debate & Ethical Inquiry',
                            'bloom_focus' => 'Evaluate, Critique & Formulate Arguments',
                            'resources_needed' => 'Debate briefing motion document, speaker rebuttal timer, parliamentary rules rubric.',
                            'practical_lab' => null,
                            'homework_assignment' => 'Submit a 300-word written position argument defending your team stance.',
                        ];
                    } else {
                        $catalog[] = [
                            'topic_id' => $topic->id,
                            'activity_type' => 'symposium',
                            'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🔬 Symposium Day: {$topic->title} Research Discovery & Viva",
                            'subtopics' => "Student presentations of research papers, investigative inquiries, and faculty viva voce on {$topic->title}",
                            'learning_objectives' => 'Defend research methodology, interpret scientific data figures, and synthesize comprehensive chapter insights.',
                            'pedagogy' => 'Academic Seminar & Peer Review Symposium',
                            'bloom_focus' => 'Synthesize, Defend & Peer Review',
                            'resources_needed' => 'Presentation slides, research summary abstracts, peer critique forms.',
                            'practical_lab' => 'Demonstrate key experimental principle to symposium audience during viva.',
                            'homework_assignment' => 'Summarize key peer feedback from symposium presentation.',
                        ];
                    }
                    $lessonNum++;

                    // Every chapter includes an official Synoptic Revision & Formula Sprint Day
                    $catalog[] = [
                        'topic_id' => $topic->id,
                        'activity_type' => 'revision',
                        'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 🔄 Revision Day: {$topic->title} Mind-Map & Formula Sprint",
                        'subtopics' => "Comprehensive synoptic revision, formula derivations, concept mapping, and flashcard recap for {$topic->title}",
                        'learning_objectives' => 'Consolidate active recall, resolve lingering doubt queries, and achieve formula fluency prior to chapter test.',
                        'pedagogy' => 'Active Recall Drill & Synoptic Mind-Mapping',
                        'bloom_focus' => 'Remember, Analyze & Synthesize',
                        'resources_needed' => 'Formula cheat sheets, blank mind-map templates, flashcards, and quick-fire quiz deck.',
                        'practical_lab' => null,
                        'homework_assignment' => 'Self-timed 30-minute flashcard drill and review formula derivation cheat sheet.',
                    ];
                    $lessonNum++;

                    // Every chapter concludes with an official Chapter Test Day
                    $catalog[] = [
                        'topic_id' => $topic->id,
                        'activity_type' => 'test',
                        'title' => 'Ch '.str_pad((string) $topic->chapter_number, 2, '0', STR_PAD_LEFT)." 📝 Chapter Test Day: {$topic->title} Full Exam Mock",
                        'subtopics' => 'Timed 60-minute chapter examination under exam conditions followed by self-evaluation and remedial debrief',
                        'learning_objectives' => "Assess summative mastery across all cognitive domains of {$topic->title}. Achieve 85%+ benchmark on mixed concept questions.",
                        'pedagogy' => 'Standardized Chapter Examination & Mastery Assessment',
                        'bloom_focus' => 'Evaluate, Analyze & Formulate Under Time Pressure',
                        'resources_needed' => 'Printed question booklet, OMR answer sheets, stopwatch timer, and marking scheme.',
                        'practical_lab' => null,
                        'homework_assignment' => 'Complete error analysis log: write correct solutions and explanations for all missed questions.',
                    ];
                    $lessonNum++;
                }
            }

            // Fill remainder with revision, symposiums, and board exam drills
            $revIndex = 1;
            while (count($catalog) < $targetInstructionalDays) {
                $top = $topics->isNotEmpty() ? $topics[$revIndex % $topics->count()] : null;
                $topTitle = $top ? $top->title : $subject->name;
                $topId = $top ? $top->id : null;
                $activityType = match ($revIndex % 5) {
                    1 => 'competition',
                    2 => 'project',
                    3 => 'debate',
                    default => 'revision',
                };

                $prefix = match ($activityType) {
                    'competition' => '🏆 Advanced Olympiad Sprint',
                    'project' => '🚀 Capstone Innovation Lab',
                    'debate' => '🗣️ Grand Academic Debate',
                    default => '🔄 Cumulative Revision & Board Mock Drill',
                };

                $catalog[] = [
                    'topic_id' => $topId,
                    'activity_type' => $activityType,
                    'title' => 'Unit '.ceil($revIndex / 5)." {$prefix}: {$topTitle}",
                    'subtopics' => "High-yield mastery, past exam papers, and analytical symposium for {$topTitle}",
                    'learning_objectives' => 'Reinforce deep cognitive recall, tackle complex multi-concept questions, and finalize annual exam readiness.',
                    'pedagogy' => $pedagogyRotation[$revIndex % count($pedagogyRotation)],
                    'bloom_focus' => $bloomFocusRotation[$revIndex % count($bloomFocusRotation)],
                    'resources_needed' => 'Mock exam paper, revision study notes, past board question archives.',
                    'practical_lab' => ($revIndex % 4 === 0 ? 'Practical exam apparatus inspection & viva voce review.' : null),
                    'homework_assignment' => "Timed 45-minute mock test on {$topTitle}.",
                ];
                $revIndex++;
            }

            $subjectCatalogs[$subject->id] = $catalog;
        }

        // 2. Build sequential linear block queue if densityMode is 'block' (1 chapter at a time, no parallel chapters)
        $linearBlockQueue = [];
        if ($densityMode === 'block') {
            $maxChapters = $subjects->max(fn ($s) => $s->topics->count()) ?: 1;
            for ($ch = 1; $ch <= $maxChapters; $ch++) {
                foreach ($subjects as $s) {
                    $cat = $subjectCatalogs[$s->id] ?? [];
                    $topicForCh = $s->topics->firstWhere('chapter_number', $ch);
                    if ($topicForCh) {
                        $chItems = array_values(array_filter($cat, fn ($it) => $it['topic_id'] === $topicForCh->id));
                        foreach ($chItems as $ci) {
                            $linearBlockQueue[] = ['subject' => $s, 'item' => $ci];
                        }
                    }
                }
            }
            $bIdx = 0;
            while (count($linearBlockQueue) < $targetInstructionalDays) {
                $s = $subjects[$bIdx % $subjects->count()];
                $cat = $subjectCatalogs[$s->id];
                $linearBlockQueue[] = ['subject' => $s, 'item' => $cat[$bIdx % count($cat)]];
                $bIdx++;
            }
        }

        // 3. Calendar allocation loop respecting densityMode: 'single', 'double', 'triple', 'all', 'block'
        $currDate = $startDate->copy();
        $instructionalDay = 0;
        $totalSessionsCreated = 0;
        $subjectLessonPointers = array_fill_keys($subjects->pluck('id')->toArray(), 0);

        while ($instructionalDay < $targetInstructionalDays) {
            // Sundays are weekly off
            if ($currDate->isSunday()) {
                $currDate->addDay();

                continue;
            }

            // Official holidays & cultural festivals are instructional off
            $holiday = self::getHolidayForDate($currDate);
            if ($holiday && $holiday->is_instructional_off) {
                $currDate->addDay();

                continue;
            }

            // Valid instructional working day
            $instructionalDay++;
            $dateStr = $currDate->toDateString();
            $month = $currDate->month;
            $term = ($month >= 6 && $month <= 10) ? 'Term 1' : 'Term 2';

            // Sequential Single Chapter Block Mode: 1 chapter taught exclusively end-to-end
            if ($densityMode === 'block') {
                $blockEntry = $linearBlockQueue[($instructionalDay - 1) % count($linearBlockQueue)];
                $subject = $blockEntry['subject'];
                $catItem = $blockEntry['item'];
                $periodSlot = 'Chapter Mastery Block (Focused Immersion)';

                $planData = [
                    'topic_id' => $catItem['topic_id'],
                    'teacher_id' => $teacherId,
                    'lesson_number' => $instructionalDay,
                    'activity_type' => $catItem['activity_type'] ?? 'regular',
                    'title' => $catItem['title'],
                    'subtopics' => $catItem['subtopics'],
                    'learning_objectives' => $catItem['learning_objectives'],
                    'pedagogy' => $catItem['pedagogy'],
                    'period_slot' => $periodSlot,
                    'bloom_focus' => $catItem['bloom_focus'],
                    'resources_needed' => $catItem['resources_needed'],
                    'practical_lab' => $catItem['practical_lab'],
                    'homework_assignment' => $catItem['homework_assignment'],
                    'status' => 'planned',
                    'term' => $term,
                    'order_index' => $instructionalDay,
                ];

                $existingPlan = LessonPlan::where('class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->whereDate('planned_date', $dateStr)
                    ->first();

                if ($existingPlan) {
                    $existingPlan->update($planData);
                } else {
                    LessonPlan::create(array_merge($planData, [
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'planned_date' => $dateStr,
                    ]));
                }

                $totalSessionsCreated++;
                $currDate->addDay();

                continue;
            }

            // Determine which subjects are taught today based on densityMode
            $activeSubjectsForDay = match ($densityMode) {
                'single' => [
                    $subjects[($instructionalDay - 1) % $subjects->count()],
                ],
                'double' => [
                    $subjects[((($instructionalDay - 1) * 2)) % $subjects->count()],
                    $subjects[((($instructionalDay - 1) * 2) + 1) % $subjects->count()],
                ],
                'triple' => [
                    $subjects[((($instructionalDay - 1) * 3)) % $subjects->count()],
                    $subjects[((($instructionalDay - 1) * 3) + 1) % $subjects->count()],
                    $subjects[((($instructionalDay - 1) * 3) + 2) % $subjects->count()],
                ],
                default => $subjects, // 'all'
            };

            foreach ($activeSubjectsForDay as $sIdx => $subject) {
                $periodSlot = $periodSlots[$sIdx % count($periodSlots)];
                $ptr = $subjectLessonPointers[$subject->id];
                $catItem = $subjectCatalogs[$subject->id][$ptr % count($subjectCatalogs[$subject->id])];
                $subjectLessonPointers[$subject->id]++;

                $planData = [
                    'topic_id' => $catItem['topic_id'],
                    'teacher_id' => $teacherId,
                    'lesson_number' => $ptr + 1,
                    'activity_type' => $catItem['activity_type'] ?? 'regular',
                    'title' => $catItem['title'],
                    'subtopics' => $catItem['subtopics'],
                    'learning_objectives' => $catItem['learning_objectives'],
                    'pedagogy' => $catItem['pedagogy'],
                    'period_slot' => $periodSlot,
                    'bloom_focus' => $catItem['bloom_focus'],
                    'resources_needed' => $catItem['resources_needed'],
                    'practical_lab' => $catItem['practical_lab'],
                    'homework_assignment' => $catItem['homework_assignment'],
                    'status' => 'planned',
                    'term' => $term,
                    'order_index' => $instructionalDay,
                ];

                $existingPlan = LessonPlan::where('class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->whereDate('planned_date', $dateStr)
                    ->first();

                if ($existingPlan) {
                    $existingPlan->update($planData);
                } else {
                    LessonPlan::create(array_merge($planData, [
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'planned_date' => $dateStr,
                    ]));
                }
                $totalSessionsCreated++;
            }

            $currDate->addDay();
        }

        return [
            'days' => $instructionalDay,
            'sessions' => $totalSessionsCreated,
            'subjects_count' => $subjects->count(),
            'density_mode' => $densityMode,
            'end_date' => $currDate->copy()->subDay()->toDateString(),
        ];
    }
}
