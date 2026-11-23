<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseLesson;
use App\Models\CourseLessonVideo;
use App\Models\CourseModule;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SpokenEnglishCoursePdfSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/spoken_english_curriculum.json');
        if (!File::exists($jsonPath)) {
            $this->command?->error("JSON curriculum not found at {$jsonPath}. Run build_curriculum_from_pdf.py first.");
            return;
        }

        $dataset = json_decode(File::get($jsonPath), true);
        if (!$dataset || !is_array($dataset)) {
            $this->command?->error("Failed to parse JSON curriculum from {$jsonPath}.");
            return;
        }

        // 1. Resolve Teacher, Class and Subject
        $teacher = User::where('role', 'teacher')->first() 
            ?? User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'teacher', 'name' => 'English Faculty Lead']);

        $academicClass = \App\Models\AcademicClass::first() 
            ?? \App\Models\AcademicClass::create([
                'name' => 'Communication & Language Arts',
                'grade_level' => 1,
            ]);

        $academicSubject = \App\Models\AcademicSubject::first() 
            ?? \App\Models\AcademicSubject::create([
                'class_id' => $academicClass->id,
                'name' => 'English Grammar & Spoken Communication',
                'code' => 'ENG',
            ]);

        // 2. Create or Reset Course
        $course = Course::updateOrCreate(
            ['code' => 'ENG-101'],
            [
                'title' => 'Spoken English & Communication Masterclass',
                'category' => 'English & Communication',
                'description' => 'Comprehensive 46-Lesson Spoken English & Grammar Masterclass based directly on the Spoken English Guru / Englishwale textbook. Features authentic phonic guides, parts of speech, all 12 tenses, causative verbs, active & passive voice, 30 real-life situational roleplays, and job interview mastery with downloadable lesson PDF notes.',
                'credits' => 6,
                'capacity' => 60,
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ]
        );

        $this->command?->info("Course created: ID {$course->id} - {$course->title} ({$course->code})");

        // 3. Create Modules
        $modulesMap = [];
        $modulesConfig = [
            1 => [
                'title' => 'Module 1: Foundations & Phonics Mastery',
                'theme' => 'Basics of English, Alphabet, Greetings, Etiquette, Vowel & Consonant Phonics, Daily Vocabulary',
                'description' => 'Master the bedrock fundamentals of the English language: letter phonetics, conversational greetings, social etiquette, standard pronunciation rules, and high-frequency vocabulary.',
                'order' => 1,
                'week' => 1,
            ],
            2 => [
                'title' => 'Module 2: Sentence Building Blocks & Parts of Speech',
                'theme' => 'WH Questions, 8 Parts of Speech, Nouns, Pronouns, Adjectives, Verbs, Adverbs, Articles, Prepositions & Determiners',
                'description' => 'Comprehensive mastery of the 8 parts of speech and structural building blocks: question formation, noun genders/numbers, pronoun cases, verb forms, adverbial modification, and relational prepositions.',
                'order' => 2,
                'week' => 3,
            ],
            3 => [
                'title' => 'Module 3: Non-Action Sentences, Existence & Spatial States',
                'theme' => 'Simple Sentences (Present, Past, Future Categories I & II), Concept of There, Position Sense',
                'description' => 'Express states of being, identity, possession, and existence without action verbs. Complete command over Is/Am/Are, Has/Have, Was/Were, Had, Will be/have, There, and spatial positioning.',
                'order' => 3,
                'week' => 6,
            ],
            4 => [
                'title' => 'Module 4: The 12 English Tenses & Temporal Mastery',
                'theme' => '12 English Tenses: Indefinite, Continuous, Perfect, Perfect Continuous across Present, Past & Future',
                'description' => 'Rigorous deep-dive into the entire temporal system of English: all 12 tenses, timeline markers, auxiliary shifts, and extensive translation drills.',
                'order' => 4,
                'week' => 7,
            ],
            5 => [
                'title' => 'Module 5: Advanced Functional Auxiliaries, Voice & Causatives',
                'theme' => 'Usage of It, Modals, Conjunctions, Imperatives, Let, Causative Verbs (Get/Make), Passive Voice & Special Cases',
                'description' => 'Elevate speaking ability with modern conversational mechanics: modal degrees, dummy subject It, causative actions with Get and Make, imperative instructions, and active-passive voice.',
                'order' => 5,
                'week' => 8,
            ],
            6 => [
                'title' => 'Module 6: Conversational Fluency & Vocabulary Precision',
                'theme' => 'Sentence Fillers, Question Tags, Phrasal Verbs, Prepositional Phrases, Homophones, One Word Substitutions',
                'description' => 'Speak with natural English flow, cadence, and nuance: filler words, intonation tags, colloquial phrasal verbs, idioms, and precise one-word vocabulary.',
                'order' => 6,
                'week' => 11,
            ],
            7 => [
                'title' => 'Module 7: Applied Practice, Error Correction & Translation',
                'theme' => 'Hindi-English Translation, Objective MCQs, Syntax Error Spotting, Mixed Practice & Mock Benchmarks',
                'description' => 'Rigorous testing and reinforcement drills: bi-directional translation, objective error detection, syntax correction, and full-length assessment papers.',
                'order' => 7,
                'week' => 13,
            ],
            8 => [
                'title' => 'Module 8: Real-Life Spoken Scenarios, Listening & Professional Interviews',
                'theme' => '30 Situational Roleplays, Passage Listening, Idioms & Proverbs, Certification, Job Interview Q&A, Dictionary',
                'description' => 'Practical communication mastery: 30 live conversation roleplays (Doctor, Shopping, Banking, Hotel, Social), job interview preparation, spoken assessment, and complete English-Hindi dictionary.',
                'order' => 8,
                'week' => 15,
            ],
        ];

        foreach ($modulesConfig as $modKey => $cfg) {
            $module = CourseModule::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'order_index' => $cfg['order'],
                ],
                [
                    'title' => $cfg['title'],
                    'theme' => $cfg['theme'],
                    'description' => $cfg['description'],
                    'week_number' => $cfg['week'],
                ]
            );
            $modulesMap[$modKey] = $module;
        }

        $this->command?->info("Created 8 modules for {$course->title}.");

        // 4. Create 46 Lessons
        $createdLessons = 0;
        foreach ($dataset as $row) {
            $modId = $row['module_id'] ?? 1;
            $module = $modulesMap[$modId] ?? $modulesMap[1];

            $lesson = CourseLesson::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'lesson_number' => $row['lesson_number'],
                ],
                [
                    'module_id' => $module->id,
                    'week_number' => $row['week_number'] ?? $module->week_number,
                    'day_of_week' => $row['day_of_week'] ?? 'Monday',
                    'title' => $row['title'],
                    'duration_minutes' => $row['duration_minutes'] ?? 45,
                    'level' => ($row['lesson_number'] <= 15) ? 'beginner' : (($row['lesson_number'] <= 34) ? 'intermediate' : 'advanced'),
                    'topic_summary' => $row['summary'],
                    'learning_objectives' => "Master the core grammatical and spoken mechanics of: {$row['subtopics']}",
                    'expected_outcomes' => "Confident command of spoken sentences, pronunciation, and written application for {$row['title']}",
                    'vocabulary' => $row['subtopics'],
                    'concepts' => "Textbook Reference Pages {$row['start_page']} to {$row['end_page']} ({$row['page_count']} pages). Complete study material extracted from Spoken English Guru eBook.",
                    'class_notes' => $row['class_notes'] ?? null,
                    'pdf_url' => $row['pdf_url'],
                    'video_url' => $row['video_url'],
                    'order_index' => $row['lesson_number'],
                ]
            );

            // Create Lesson Video record for direct playback
            if (!empty($row['video_url'])) {
                CourseLessonVideo::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'lesson_id' => $lesson->id,
                        'video_url' => $row['video_url'],
                    ],
                    [
                        'title' => "Masterclass Lecture: {$row['title']}",
                        'duration_minutes' => $row['duration_minutes'] ?? 45,
                        'description' => "Official instructional video lecture accompanying {$row['title']} (Pages {$row['start_page']}-{$row['end_page']}).",
                        'order_index' => 1,
                    ]
                );
            }

            $createdLessons++;
        }

        $this->command?->info("Created {$createdLessons} lessons with video lectures and sliced PDF handouts.");

        // 5. Timetable Schedules (Monday, Wednesday, Friday at 10:00 AM)
        $days = ['Monday', 'Wednesday', 'Friday'];
        foreach ($days as $day) {
            Schedule::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'day_of_week' => $day,
                ],
                [
                    'title' => "Spoken English & Communication Lecture - {$day}",
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'room' => 'Language Lab & Multimedia Hall 101',
                    'delivery_mode' => 'offline',
                    'is_cancelled' => false,
                ]
            );
        }

        // 6. Assessments for Major Milestone Modules
        $assessments = [
            ['title' => 'Module 1 Phonics & Baseline Proficiency Test', 'week' => 2, 'points' => 50],
            ['title' => 'Module 2 Parts of Speech & Syntax Assessment', 'week' => 5, 'points' => 100],
            ['title' => 'Module 4 All 12 Tenses Comprehensive Mastery Exam', 'week' => 7, 'points' => 100],
            ['title' => 'Module 5 Modals, Passive Voice & Causative Verbs Test', 'week' => 10, 'points' => 100],
            ['title' => 'Module 7 Objective Syntax & Translation Benchmark', 'week' => 14, 'points' => 100],
            ['title' => 'Module 8 Final Spoken English Telephonic & Interview Certification', 'week' => 16, 'points' => 150],
        ];

        foreach ($assessments as $idx => $ass) {
            CourseAssessment::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'title' => $ass['title'],
                ],
                [
                    'type' => 'test',
                    'week_number' => $ass['week'],
                    'total_points' => $ass['points'],
                    'passing_score' => (int)($ass['points'] * 0.6),
                    'description' => "Complete all questions based on the Spoken English Guru course syllabus for Week {$ass['week']}.",
                    'is_published' => true,
                ]
            );
        }

        // 7. Sample DPP Sheets linked to Lessons
        $sampleDpps = [
            ['lesson_num' => 1, 'title' => 'DPP-01: Alphabet, Numbers & Greetings Drill', 'week' => 1],
            ['lesson_num' => 3, 'title' => 'DPP-02: Phonics & Vowel Sounds Pronunciation Drill', 'week' => 2],
            ['lesson_num' => 7, 'title' => 'DPP-03: Noun Classification & Genders Identification', 'week' => 3],
            ['lesson_num' => 19, 'title' => 'DPP-04: The 12 English Tenses Sentence Formulation', 'week' => 7],
            ['lesson_num' => 26, 'title' => 'DPP-05: Active to Passive Voice Transformation Practice', 'week' => 10],
            ['lesson_num' => 41, 'title' => 'DPP-06: Situational Dialogue & Conversation Roleplay Drill', 'week' => 15],
        ];

        foreach ($sampleDpps as $idx => $dppData) {
            $matchingLesson = CourseLesson::where('course_id', $course->id)
                ->where('lesson_number', $dppData['lesson_num'])
                ->first();

            $dpp = DailyDpp::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'title' => $dppData['title'],
                ],
                [
                    'class_id' => $academicClass->id,
                    'subject_id' => $academicSubject->id,
                    'teacher_id' => $teacher->id,
                    'lesson_id' => $matchingLesson?->id,
                    'dpp_number' => $idx + 1,
                    'assigned_date' => now()->toDateString(),
                    'total_marks' => 10,
                    'difficulty' => 'medium',
                ]
            );

            // Add sample questions
            if ($dpp->questions()->count() === 0) {
                DppQuestion::create([
                    'dpp_id' => $dpp->id,
                    'question_number' => 1,
                    'question_text' => "What is the primary grammatical purpose demonstrated in {$dppData['title']}?",
                    'option_a' => 'Accurate grammatical formulation and natural spoken fluency',
                    'option_b' => 'Mechanical word memorization without phonetics',
                    'option_c' => 'Grammar translation without spoken conversational drills',
                    'option_d' => 'Passive listening without speaking practice',
                    'correct_option' => 'A',
                    'explanation' => "As detailed in the textbook, active practice and pronunciation mastery are required for authentic spoken communication.",
                    'marks' => 2,
                ]);
            }
        }

        $this->command?->info("Seeding complete! {$course->title} is fully set up with 8 modules, 46 lessons, 6 milestone assessments, and daily DPP sheets.");
    }
}
