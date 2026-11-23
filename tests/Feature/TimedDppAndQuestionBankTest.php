<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimedDppAndQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected AcademicClass $class8;
    protected AcademicSubject $scienceSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Database\Seeders\BloomsTaxonomySeeder::class);
        $this->seed(\Database\Seeders\SyllabusSeeder::class);
        $this->seed(\Database\Seeders\QuestionBankSeeder::class);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@primearc.academy'],
            [
                'name' => 'Dr. Rajeshwar Sharma',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'student_id_number' => 'ADM-001',
                'streak_count' => 5,
                'xp_points' => 1500,
            ]
        );

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher@primearc.academy'],
            [
                'name' => 'Dr. Vikramaditya Sen',
                'password' => bcrypt('password123'),
                'role' => 'teacher',
                'student_id_number' => 'FAC-001',
                'streak_count' => 3,
                'xp_points' => 600,
            ]
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student@primearc.academy'],
            [
                'name' => 'Aarav Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'PAA-STU-001',
                'streak_count' => 2,
                'xp_points' => 100,
            ]
        );

        $this->class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $this->scienceSubject = AcademicSubject::where('class_id', $this->class8->id)->first() ?? AcademicSubject::first();
        $this->class8->update(['class_teacher_id' => $this->teacher->id]);
        $this->scienceSubject->update(['teacher_id' => $this->teacher->id]);

        $this->class8->students()->syncWithoutDetaching([
            $this->student->id => ['roll_number' => 88, 'status' => 'active']
        ]);
    }

    /**
     * Test Question Bank storage and API search.
     */
    public function test_teacher_can_catalog_and_search_question_bank(): void
    {
        // 1. Teacher stores question into question bank
        $response = $this->actingAs($this->teacher)->post(route('question-bank.store'), [
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'question_text' => 'What is the acceleration due to gravity on the surface of Earth?',
            'option_a' => '8.9 m/s²',
            'option_b' => '9.8 m/s²',
            'option_c' => '10.5 m/s²',
            'option_d' => '12.0 m/s²',
            'correct_option' => 'B',
            'explanation' => 'Standard acceleration due to Earth gravity g is approximately 9.8 m/s².',
            'difficulty' => 'standard',
            'marks' => 4,
            'tags' => 'Physics, Gravitation, Class 8',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_bank', [
            'question_text' => 'What is the acceleration due to gravity on the surface of Earth?',
            'correct_option' => 'B',
        ]);

        // 2. Search question bank via API
        $apiResponse = $this->actingAs($this->teacher)->getJson(route('question-bank.api', [
            'class_id' => $this->class8->id,
            'q' => 'gravity',
        ]));

        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure(['count', 'questions']);
        $this->assertGreaterThanOrEqual(1, $apiResponse->json('count'));
    }

    /**
     * Test teacher can create a formal Timed Test with countdown duration and question bank saving.
     */
    public function test_teacher_can_create_timed_test_with_timer_duration(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('dpp.store'), [
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'type' => 'test',
            'title' => 'Unit 1 Gravitation & Motion Timed Assessment',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'advanced',
            'duration_minutes' => 25,
            'pass_percentage' => 50,
            'save_to_bank' => 1,
            'questions' => [
                [
                    'text' => 'Which law states every action has an equal and opposite reaction?',
                    'option_a' => 'Newton First Law',
                    'option_b' => 'Newton Second Law',
                    'option_c' => 'Newton Third Law',
                    'option_d' => 'Law of Conservation of Energy',
                    'correct' => 'C',
                    'explanation' => 'Newton\'s Third Law states for every action there is an equal and opposite reaction.',
                    'marks' => 4,
                ],
            ],
        ]);

        $response->assertRedirect();

        $test = DailyDpp::where('title', 'Unit 1 Gravitation & Motion Timed Assessment')->first();
        $this->assertNotNull($test);
        $this->assertEquals('test', $test->type);
        $this->assertEquals(25, $test->duration_minutes);
        $this->assertTrue($test->is_timed);
        $this->assertEquals(50, $test->pass_percentage);
        $this->assertStringStartsWith('TEST-', $test->dpp_number);

        // Verify question saved to bank
        $this->assertDatabaseHas('question_bank', [
            'question_text' => 'Which law states every action has an equal and opposite reaction?',
        ]);
    }

    /**
     * Test single-question stepper layout and live countdown timer interface for student.
     */
    public function test_student_sees_single_question_stepper_and_countdown_clock(): void
    {
        $test = DailyDpp::where('type', 'test')->first();
        $this->assertNotNull($test);

        $response = $this->actingAs($this->student)->get(route('dpp.show', $test->id));

        $response->assertStatus(200);
        $response->assertSee('Timed Examination');
        $response->assertSee('Time Left');
        $response->assertSee('Question Palette:');
        $response->assertSee('Clear Answer');
        $response->assertSee('Finish &amp; Submit', false);
    }

    /**
     * Test student solves timed test and earns +50 XP with pass/fail evaluation and time tracking.
     */
    public function test_student_submits_timed_test_and_receives_pass_status_and_fifty_xp(): void
    {
        $test = DailyDpp::where('type', 'test')->with('questions')->first();
        $this->assertNotNull($test);

        $initialXp = $this->student->fresh()->xp_points;

        // Build answers: answer all correctly
        $answers = [];
        foreach ($test->questions as $q) {
            $answers[(string)$q->question_number] = $q->correct_option;
        }

        $response = $this->actingAs($this->student)->post(route('dpp.submit', $test->id), [
            'answers' => $answers,
            'time_taken_seconds' => 480, // 8 minutes
        ]);

        $response->assertRedirect(route('dpp.show', $test->id));

        // Verify submission record
        $submission = DppSubmission::where('dpp_id', $test->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals(100.0, $submission->percentage);
        $this->assertTrue($submission->is_passed);
        $this->assertEquals(480, $submission->time_taken_seconds);
        $this->assertEquals(50, $submission->xp_earned); // 50 XP for formal test

        // Verify user received +50 XP
        $this->student->refresh();
        $this->assertEquals($initialXp + 50, $this->student->xp_points);

        // Verify review screen displays scorecard
        $reviewResponse = $this->actingAs($this->student)->get(route('dpp.show', $test->id));
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('PASSED');
        $reviewResponse->assertSee('Completed in 8m 0s');
        $reviewResponse->assertSee('Step-by-Step Solution');
    }

    /**
     * Test that teacher DPP page contains modal directives, click.stop triggers, and negative marking inputs.
     */
    public function test_teacher_dpp_page_contains_modal_directives_and_negative_marking(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('dpp.index'));

        $response->assertStatus(200);
        $response->assertSee('@click.stop="openCreateModal = true"', false);
        $response->assertSee('x-show="openCreateModal"', false);
        $response->assertSee('@click.stop="openBankPickerModal = true"', false);
        $response->assertSee('name="negative_marks"', false);
        $response->assertDontSee('@click.away="openCreateModal = false"', false);
    }

    /**
     * Test that teacher attendance page contains modal directives, click.stop triggers, and backdrop overlays.
     */
    public function test_teacher_attendance_page_contains_modal_directives_and_dynamic_roster(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('@click.stop="openTakeModal = true"', false);
        $response->assertSee('x-show="openTakeModal"', false);
        $response->assertSee('@click="openTakeModal = false"', false);
        $response->assertDontSee('@click.away="openTakeModal = false"', false);
    }
}
