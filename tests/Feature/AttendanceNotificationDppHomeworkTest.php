<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\InAppNotification;
use App\Models\User;
use Database\Seeders\AcademicAdditionsSeeder;
use Database\Seeders\BloomsTaxonomySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SyllabusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceNotificationDppHomeworkTest extends TestCase
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

        $this->seed(DatabaseSeeder::class);
        $this->seed(BloomsTaxonomySeeder::class);
        $this->seed(SyllabusSeeder::class);
        $this->seed(AcademicAdditionsSeeder::class);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@primearc.academy'],
            [
                'name' => 'Dr. Rajeshwar Sharma',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'student_id_number' => 'ADM-001',
                'streak_count' => 5,
                'xp_points' => 1500,
                'academic_tier' => 'Distinguished Scholar',
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
                'xp_points' => 120,
            ]
        );

        $this->class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $this->scienceSubject = AcademicSubject::where('class_id', $this->class8->id)->first() ?? AcademicSubject::first();
        $this->class8->update(['class_teacher_id' => $this->teacher->id]);
        $this->scienceSubject->update(['teacher_id' => $this->teacher->id]);

        $this->admin->update(['is_approved' => true]);
        $this->teacher->update(['is_approved' => true]);
        $this->student->update(['is_approved' => true, 'current_class_id' => $this->class8->id]);

        // Ensure student is enrolled in class8
        $this->class8->students()->syncWithoutDetaching([
            $this->student->id => ['roll_number' => 99, 'status' => 'active'],
        ]);
    }

    /**
     * Test mobile bottom 4 tabs render on the layout.
     */
    public function test_mobile_bottom_four_tabs_render(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Courses');
        $response->assertSee("Bloom's", false);
        $response->assertSee('Syllabus');
    }

    /**
     * Test teacher can view attendance management and record session attendance.
     */
    public function test_teacher_can_record_attendance_and_student_earns_xp(): void
    {
        $initialXp = $this->student->xp_points;

        $response = $this->actingAs($this->admin)->get(route('attendance.index', ['class_id' => $this->class8->id]));
        $response->assertStatus(200);
        $response->assertSee('Attendance Management');

        $sessionDate = now()->subDays(2)->toDateString();
        $attendance = [];
        foreach ($this->class8->students as $stu) {
            $attendance[$stu->id] = 'present';
        }

        $storeResponse = $this->actingAs($this->admin)->post(route('attendance.store'), [
            'class_id' => $this->class8->id,
            'session_date' => $sessionDate,
            'title' => 'Afternoon Laboratory Session',
            'notes' => 'Conducted osmosis and cell permeability practicals.',
            'attendance' => $attendance,
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('attendance_sessions', [
            'class_id' => $this->class8->id,
            'title' => 'Afternoon Laboratory Session',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        // Verify student received +15 XP for presence
        $this->student->refresh();
        $this->assertEquals($initialXp + 15, $this->student->xp_points);
    }

    /**
     * Test student views their personal attendance summary.
     */
    public function test_student_views_personal_attendance_log(): void
    {
        $response = $this->actingAs($this->student)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee('Personal Attendance Record');
        $response->assertSee('Attendance Rate');
        $response->assertSee('Session History');
    }

    /**
     * Test in-app notifications hub and unread API.
     */
    public function test_in_app_notifications_hub_and_api(): void
    {
        // Create an unread notification for student
        $notif = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'dpp',
            'title' => 'New DPP Available',
            'message' => 'Solve today\'s DPP for immediate evaluation.',
            'action_url' => '/dpp',
            'icon' => '🎯',
            'is_read' => false,
        ]);

        // Test API endpoint
        $apiResponse = $this->actingAs($this->student)->getJson(route('notifications.unread'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure(['unread_count', 'notifications']);
        $this->assertGreaterThanOrEqual(1, $apiResponse->json('unread_count'));

        // Test web index
        $indexResponse = $this->actingAs($this->student)->get(route('notifications.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('New DPP Available');

        // Test marking as read via GET read route
        $readResponse = $this->actingAs($this->student)->get(route('notifications.read', $notif->id));
        $readResponse->assertRedirect('/dpp');
        $notif->refresh();
        $this->assertTrue($notif->is_read);

        // Test mark all read
        $markAllResponse = $this->actingAs($this->student)->post(route('notifications.mark-all-read'));
        $markAllResponse->assertRedirect();
        $unreadLeft = InAppNotification::where('user_id', $this->student->id)->where('is_read', false)->count();
        $this->assertEquals(0, $unreadLeft);
    }

    /**
     * Test Daily Practice Problems (DPP) solving with instant automated same-day checking.
     */
    public function test_student_submits_dpp_and_gets_instant_evaluation(): void
    {
        $initialXp = $this->student->xp_points;

        // Fetch or create a DPP with questions
        $dpp = DailyDpp::where('class_id', $this->class8->id)->first();
        if (! $dpp) {
            $dpp = DailyDpp::create([
                'class_id' => $this->class8->id,
                'subject_id' => $this->scienceSubject->id,
                'teacher_id' => $this->teacher->id,
                'dpp_number' => 'DPP-TEST-01',
                'title' => 'Photosynthesis Mastery',
                'assigned_date' => now()->toDateString(),
                'difficulty' => 'standard',
                'total_marks' => 8,
            ]);

            DppQuestion::create([
                'dpp_id' => $dpp->id,
                'question_number' => 1,
                'question_text' => 'Primary organelle for photosynthesis?',
                'option_a' => 'Mitochondria',
                'option_b' => 'Chloroplast',
                'option_c' => 'Ribosome',
                'option_d' => 'Nucleus',
                'correct_option' => 'B',
                'explanation' => 'Chloroplasts contain chlorophyll.',
                'marks' => 4,
            ]);

            DppQuestion::create([
                'dpp_id' => $dpp->id,
                'question_number' => 2,
                'question_text' => 'Gas released during photolysis?',
                'option_a' => 'CO2',
                'option_b' => 'Nitrogen',
                'option_c' => 'Oxygen',
                'option_d' => 'Hydrogen',
                'correct_option' => 'C',
                'explanation' => 'Oxygen is released.',
                'marks' => 4,
            ]);
        }

        // View DPP page as student
        $viewResponse = $this->actingAs($this->student)->get(route('dpp.show', $dpp->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee($dpp->title);

        // Submit answers: Q1 = B (Correct), Q2 = A (Incorrect)
        $submitResponse = $this->actingAs($this->student)->post(route('dpp.submit', $dpp->id), [
            'answers' => [
                '1' => 'B',
                '2' => 'A',
                '3' => 'B',
                '4' => 'C',
                '5' => 'C',
            ],
        ]);

        $submitResponse->assertRedirect(route('dpp.show', $dpp->id));

        // Verify instant evaluation in database
        $this->assertDatabaseHas('dpp_submissions', [
            'dpp_id' => $dpp->id,
            'student_id' => $this->student->id,
            'status' => 'evaluated',
        ]);

        // Verify XP (+30 XP for DPP, +50 XP for Test) awarded
        $this->student->refresh();
        $expectedAward = $dpp->isTest() ? 50 : 30;
        $this->assertEquals($initialXp + $expectedAward, $this->student->xp_points);

        // Verify notification dispatched
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->student->id,
            'type' => 'dpp',
        ]);

        // Review solutions as student
        $reviewResponse = $this->actingAs($this->student)->get(route('dpp.show', $dpp->id));
        $reviewResponse->assertStatus(200);
        $reviewResponse->assertSee('Automated Instant Evaluation Completed');
        $reviewResponse->assertSee('Step-by-Step Solution', false);
    }

    /**
     * Test Daily Homework assignment, student submission, and teacher verification.
     */
    public function test_homework_workflow_assignment_submission_and_verification(): void
    {
        $initialXp = $this->student->xp_points;

        // 1. Teacher assigns homework
        $assignResponse = $this->actingAs($this->teacher)->post(route('homework.store'), [
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'title' => 'Ex 6.2 Quadratic Equations & Graphical Solutions',
            'instructions' => 'Solve questions 1 through 10 in your homework notebook and write your key observations.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $assignResponse->assertRedirect();
        $task = HomeworkTask::where('title', 'Ex 6.2 Quadratic Equations & Graphical Solutions')->first();
        $this->assertNotNull($task);

        // Verify notification sent to class students
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->student->id,
            'type' => 'homework',
        ]);

        // 2. Student views homework dashboard and submits work
        $viewResponse = $this->actingAs($this->student)->get(route('homework.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Ex 6.2 Quadratic Equations');

        $xpBeforeSubmit = $this->student->fresh()->xp_points;

        $submitResponse = $this->actingAs($this->student)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Completed all 10 problems. Factorization was straightforward for problems 1-7. Used discriminant formula for 8-10.',
        ]);

        $submitResponse->assertRedirect();
        $submission = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals('submitted', $submission->status);

        // Verify +20 XP awarded for homework
        $this->student->refresh();
        $this->assertEquals($xpBeforeSubmit + 20, $this->student->xp_points);

        // 3. Teacher verifies homework submission
        $checkResponse = $this->actingAs($this->teacher)->patch(route('homework.submissions.check', $submission->id), [
            'status' => 'checked',
            'teacher_feedback' => 'Well documented derivations. Keep up the thorough analytical approach!',
        ]);

        $checkResponse->assertRedirect();
        $submission->refresh();
        $this->assertEquals('checked', $submission->status);
        $this->assertEquals('Well documented derivations. Keep up the thorough analytical approach!', $submission->teacher_feedback);
    }
}
