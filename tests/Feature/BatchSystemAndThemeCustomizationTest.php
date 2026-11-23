<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Batch;
use App\Models\BatchChangeRequest;
use App\Models\ClassEnrollment;
use App\Models\CurriculumNote;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\HomeworkTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchSystemAndThemeCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $teacher;

    protected AcademicClass $academicClass;

    protected Batch $batch;

    protected AcademicSubject $subject;

    protected CurriculumTopic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicClass = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->batch = Batch::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Class 10 - Morning Star Batch',
            'code' => 'BATCH-10-M',
            'shift' => 'morning',
            'timing_slot' => '07:30 AM - 10:30 AM',
            'start_time' => '07:30',
            'end_time' => '10:30',
            'academic_year' => '2026-2027',
            'description' => 'Morning shift batch for Class 10',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Dr. Vikramaditya Sen',
            'email' => 'teacher@primearc.edu.in',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->academicClass->id,
            'roll_number' => 1,
            'batch_id' => $this->batch->id,
            'name' => 'Ananya Patel',
            'email' => 'ananya@student.primearc.edu.in',
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'class_id' => $this->academicClass->id,
            'student_id' => $this->student->id,
            'roll_number' => 1,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);

        $this->subject = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
            'description' => 'Secondary Mathematics',
        ]);

        $this->topic = CurriculumTopic::create([
            'subject_id' => $this->subject->id,
            'chapter_number' => 1,
            'title' => 'Real Numbers and Polynomials',
            'subtopics' => ['Euclid Division Lemma', 'Fundamental Theorem of Arithmetic'],
            'description' => 'Foundational number theory concepts.',
            'status' => 'in_progress',
            'study_notes' => 'Master study notes for Real Numbers with proofs and theorems.',
            'resource_url' => 'https://primearc.edu.in/resources/notes/math-ch1.pdf',
        ]);

        // 1. Video
        CurriculumVideo::create([
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'title' => 'Lecture 1: Introduction to Real Numbers',
            'video_url' => 'https://www.youtube.com/watch?v=demo_video_1',
            'video_provider' => 'youtube',
            'duration_seconds' => 1800,
            'order_index' => 1,
            'is_published' => true,
        ]);

        // 2. DPP (type != test)
        DailyDpp::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-10-01',
            'type' => 'dpp',
            'title' => 'Daily Practice Sheet 1: Real Numbers',
            'assigned_date' => now()->toDateString(),
            'due_time' => '23:59',
            'total_marks' => 20,
            'difficulty' => 'intermediate',
        ]);

        // 3. Homework Task (Assignment)
        HomeworkTask::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Assignment 1: Solve NCERT Exercise 1.1',
            'instructions' => 'Complete all 5 proofs in homework notebook.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        // 4. Test (type = test)
        DailyDpp::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'TEST-10-01',
            'type' => 'test',
            'title' => 'Chapter 1 Mock Test: Real Numbers',
            'assigned_date' => now()->toDateString(),
            'due_time' => '23:59',
            'total_marks' => 25,
            'difficulty' => 'intermediate',
            'duration_minutes' => 30,
            'is_timed' => true,
            'pass_percentage' => 40,
        ]);
    }

    public function test_class_and_batch_selection_form_displays_classes_and_batches(): void
    {
        // Enrolled student can view available shifts in their enrolled class to request shift changes
        $studentView = $this->actingAs($this->student)->get(route('batches.select', ['class_id' => $this->academicClass->id]));
        $studentView->assertStatus(200);
        $studentView->assertSee('Verified Enrollment');

        // Unassigned student can view available batches to join initial batch
        $unassignedStudent = User::factory()->create([
            'role' => 'student',
            'batch_id' => null,
        ]);
        $response = $this->actingAs($unassignedStudent)->get(route('batches.select', ['class_id' => $this->academicClass->id]));
        $response->assertStatus(200);

        // Class and Batch details
        $response->assertSee('Select Your Class &amp; Batch', false);
        $response->assertSee('Class 10');
        $response->assertSee('Class 10 - Morning Star Batch');
        $response->assertSee('07:30 AM - 10:30 AM');
        $response->assertSee('☀️ Morning');

        // Student cannot create custom batches
        $response->assertDontSee('Create Custom Batch');

        // Teacher view: sees Create Custom Batch and NOT sample batches (dummy purged)
        $teacherResponse = $this->actingAs($this->teacher)->get(route('batches.select', ['class_id' => $this->academicClass->id]));
        $teacherResponse->assertStatus(200);
        $teacherResponse->assertSee('Create Custom Batch');
        $teacherResponse->assertDontSee('Add Sample Batches');
    }

    public function test_teacher_can_create_new_batch_with_custom_timings_and_student_is_forbidden(): void
    {
        // Student cannot create batch (403 Forbidden)
        $studentResponse = $this->actingAs($this->student)->post(route('batches.create'), [
            'class_id' => $this->academicClass->id,
            'name' => 'Class 10 - Unauthorized Batch',
            'shift' => 'evening',
            'timing_slot' => '05:00 PM - 08:00 PM',
        ]);
        $studentResponse->assertStatus(403);

        // Teacher can create batch
        $response = $this->actingAs($this->teacher)->post(route('batches.create'), [
            'class_id' => $this->academicClass->id,
            'name' => 'Class 10 - Evening Achievers',
            'shift' => 'evening',
            'timing_slot' => '05:00 PM - 08:00 PM',
            'description' => 'Evening shift coaching batch',
        ]);

        $this->assertDatabaseHas('batches', [
            'name' => 'Class 10 - Evening Achievers',
            'shift' => 'evening',
            'timing_slot' => '05:00 PM - 08:00 PM',
        ]);

        $createdBatch = Batch::where('name', 'Class 10 - Evening Achievers')->first();
        $response->assertRedirect(route('batches.subjects', ['batch' => $createdBatch->id]));
    }

    public function test_deprecated_seed_samples_endpoint_is_removed_and_returns_404(): void
    {
        // Seed samples route has been completely purged in favor of real custom batch creation
        $response = $this->actingAs($this->teacher)->post('/my-batch/seed-samples', [
            'class_id' => $this->academicClass->id,
        ]);
        $response->assertStatus(404);
    }

    public function test_after_class_batch_selection_subjects_list_is_displayed_in_cards(): void
    {
        $response = $this->actingAs($this->student)->get(route('batches.subjects', ['batch' => $this->batch->id]));
        $response->assertStatus(200);

        // Header shows active batch info
        $response->assertSee($this->batch->name);
        $response->assertSee('07:30 AM - 10:30 AM');

        // Student view: cannot see Change Class / Batch button
        $response->assertDontSee('Change Class / Batch');

        // Teacher view: sees Change Class / Batch button
        $teacherResponse = $this->actingAs($this->teacher)->get(route('batches.subjects', ['batch' => $this->batch->id]));
        $teacherResponse->assertStatus(200);
        $teacherResponse->assertSee('Change Class / Batch');

        // Subject displayed in rectangular one-in-a-line card
        $response->assertSee($this->subject->name);
        $response->assertSee($this->subject->code);
        $response->assertSee('Open');
        $response->assertSee(route('batches.chapters', ['batch' => $this->batch->id, 'subject' => $this->subject->id]));
        $response->assertSee('space-y-2.5');
    }

    public function test_after_subject_selection_chapters_card_view_is_displayed(): void
    {
        $response = $this->actingAs($this->student)->get(route('batches.chapters', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
        ]));

        $response->assertStatus(200);

        // Header
        $response->assertSee('Back to Subjects');
        $response->assertSee($this->subject->name);

        // Chapters rectangular one-in-a-line card view
        $response->assertSee('Chapters List');
        $response->assertSee('Chapter 01');
        $response->assertSee($this->topic->title);
        $response->assertSee('Study');
        $response->assertSee('space-y-2.5');
        $response->assertSee(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]));
    }

    public function test_least_distractive_learning_page_with_five_tabs(): void
    {
        $response = $this->actingAs($this->student)->get(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]));

        $response->assertStatus(200);

        // Context header & compact chapter number label only (no bloated banner, no switch dropdown)
        $response->assertSee('Back to Chapters');
        $response->assertSee($this->subject->name);
        $response->assertSee('Chapter 01');
        $response->assertDontSee('Switch:');
        $response->assertDontSee('Active Chapter Workspace');

        // Check 5 Dedicated Tabs exist
        $response->assertSee('Videos');
        $response->assertSee('Notes & Guide');
        $response->assertSee('DPP');
        $response->assertSee('Assignments');
        $response->assertSee('Tests');

        // Tab 1: Video Lectures content
        $response->assertSee('Lecture 1: Introduction to Real Numbers');
        $response->assertSee('HD 1080p Stream');

        // Tab 2: Notes content
        $response->assertSee('Master Study Notes');
        $response->assertSee('Master study notes for Real Numbers with proofs');

        // Tab 3: DPP content
        $response->assertSee('Daily Practice Sheet 1: Real Numbers');
        $response->assertSee('Solve DPP');

        // Tab 4: Assignments content
        $response->assertSee('Assignment 1: Solve NCERT Exercise 1.1');
        $response->assertSee('View &amp; Submit', false);

        // Tab 5: Tests content
        $response->assertSee('Chapter 1 Mock Test: Real Numbers');
        $response->assertSee('30 Minutes');
        $response->assertSee('Start Test');
    }

    public function test_empty_state_views_for_all_five_categories_render_correctly(): void
    {
        // Create an empty topic with no videos, notes, dpps, assignments, or tests
        $emptyTopic = CurriculumTopic::create([
            'subject_id' => $this->subject->id,
            'chapter_number' => 99,
            'title' => 'Empty Chapter For Testing',
            'estimated_days' => 5,
            'status' => 'pending',
            'description' => 'Test chapter with no uploaded media.',
        ]);

        $response = $this->actingAs($this->student)->get(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $emptyTopic->id,
        ]));

        $response->assertStatus(200);

        // 1. Navigation & Pastel Metadata Badges
        $response->assertSee('Back to Chapters');
        $response->assertSee($this->subject->name);
        $response->assertSee('Chapter 99');
        $response->assertSee('bg-amber-50', false);
        $response->assertSee('bg-sky-50', false);
        $response->assertSee('bg-emerald-50', false);

        // 2. 5 Distinct interactive tabs with zero counts
        $response->assertSee('Videos');
        $response->assertSee('Notes &amp; Guide', false);
        $response->assertSee('DPP');
        $response->assertSee('Assignments');
        $response->assertSee('Tests');

        // 3. Category empty-state representations
        $response->assertSee('No Video Lectures Yet');
        $response->assertSee('Video sessions for this topic are being prepared.');

        $response->assertSee('No Notes or Guides Yet');
        $response->assertSee('Study material and lecture notes for this topic are being prepared.');

        $response->assertSee('No Daily Practice Problems Yet');
        $response->assertSee('Daily practice problems will be posted along with classes.');

        $response->assertSee('No Assignments Posted Yet');
        $response->assertSee('There are no pending homework assignments for this topic.');

        $response->assertSee('No Chapter Tests Scheduled');
        $response->assertSee('Upcoming chapter mock tests will appear here.');
    }

    public function test_my_batch_route_redirects_assigned_student_to_subjects(): void
    {
        $response = $this->actingAs($this->student)->get(route('my-batch'));
        $response->assertRedirect(route('batches.subjects', ['batch' => $this->batch->id]));
    }

    public function test_syllabus_section_hierarchy_select_class_then_subject(): void
    {
        // 1. Visit Syllabus root (Select Class)
        $response = $this->actingAs($this->student)->get(route('syllabus.index', ['grade' => 10]));
        $response->assertStatus(200);
        $response->assertSee('Step 1: Select Class');
        $response->assertSee('Step 2: Select Subject');
        $response->assertSee('Class 10');

        // 2. Select specific subject in Class 10
        $subjectResponse = $this->actingAs($this->student)->get(route('syllabus.index', [
            'grade' => 10,
            'subject_id' => $this->subject->id,
        ]));
        $subjectResponse->assertStatus(200);
        $subjectResponse->assertSee($this->subject->name);
        $subjectResponse->assertSee($this->topic->title);
    }

    public function test_weekly_timetable_is_in_simple_table_format(): void
    {
        $response = $this->actingAs($this->student)->get(route('schedules.index'));
        $response->assertStatus(200);

        // Check tabular format elements
        $response->assertSee('<table', false);
        $response->assertSee('Period 1');
        $response->assertSee('Monday');
        $response->assertSee('Tuesday');
        $response->assertSee('Wednesday');
        $response->assertSee('Thursday');
        $response->assertSee('Friday');
    }

    public function test_assignments_are_in_compact_table_format(): void
    {
        $response = $this->actingAs($this->student)->get(route('assignments.index'));
        $response->assertStatus(200);

        $response->assertSee('<table', false);
        $response->assertSee('Due Date');
        $response->assertSee('Max Marks');
        $response->assertSee('Status');
    }

    public function test_navbar_contains_my_batch_and_theme_toggle_and_auth_actions_container(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertStatus(200);

        // Navbar has My Batch link
        $response->assertSee(route('my-batch'));
        $response->assertSee('My Batch');

        // Theme toggle button and dark mode config exist
        $response->assertSee('id="theme-toggle-btn"', false);
        $response->assertSee('darkMode: \'class\'', false);
        $response->assertSee('id="navbar-auth-actions"', false);
        $response->assertSee('id="top-nav-container"', false);
    }

    public function test_student_can_request_batch_change_and_teacher_can_review_and_approve(): void
    {
        $newBatch = Batch::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Class 10 - Evening Batch',
            'code' => 'BATCH-10-EVENING',
            'shift' => 'evening',
            'timing_slot' => '05:00 PM - 08:00 PM',
            'academic_year' => '2026-2027',
        ]);

        // 1. Student can view batches.select page to inspect other shifts in their class
        $viewResponse = $this->actingAs($this->student)->get(route('batches.select'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Class 10 - Evening Batch');
        $viewResponse->assertSee('Request Change 📝');

        // 2. Student cannot directly switch batch via select.store
        $storeResponse = $this->actingAs($this->student)->post(route('batches.select.store'), [
            'batch_id' => $newBatch->id,
        ]);
        $storeResponse->assertStatus(403);
        $this->assertEquals($this->batch->id, $this->student->fresh()->batch_id);

        // 3. Student submits a batch change request
        $requestResponse = $this->actingAs($this->student)->post(route('batches.request-change'), [
            'batch_id' => $newBatch->id,
            'reason' => 'Need evening batch due to transit schedule',
        ]);
        $requestResponse->assertRedirect();
        $this->assertDatabaseHas('batch_change_requests', [
            'student_id' => $this->student->id,
            'requested_batch_id' => $newBatch->id,
            'status' => 'pending',
            'reason' => 'Need evening batch due to transit schedule',
        ]);

        $changeReq = BatchChangeRequest::where('student_id', $this->student->id)->first();

        // 3. Teacher approves request
        $reviewResponse = $this->actingAs($this->teacher)->post(route('batches.requests.review', $changeReq->id), [
            'action' => 'approve',
        ]);
        $reviewResponse->assertRedirect();
        $this->assertEquals('approved', $changeReq->fresh()->status);
        $this->assertEquals($newBatch->id, $this->student->fresh()->batch_id);
    }

    public function test_chapter_workspace_renders_multiple_notes_and_multiple_dpps_as_lists(): void
    {
        // Add 2 curriculum notes for the topic
        CurriculumNote::create([
            'topic_id' => $this->topic->id,
            'title' => 'Formula Sheet & Important Proofs',
            'note_type' => 'formula_sheet',
            'reading_minutes' => 10,
            'order_index' => 1,
            'is_published' => true,
        ]);

        CurriculumNote::create([
            'topic_id' => $this->topic->id,
            'title' => 'NCERT Exemplar Solutions & Mind Map',
            'note_type' => 'mind_map',
            'reading_minutes' => 15,
            'order_index' => 2,
            'is_published' => true,
        ]);

        // Add 2nd DPP for the topic
        DailyDpp::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-10-02',
            'type' => 'dpp',
            'title' => 'Daily Practice Sheet 2: Polynomials & Zeros',
            'assigned_date' => now()->toDateString(),
            'due_time' => '23:59',
            'total_marks' => 25,
            'difficulty' => 'advanced',
        ]);

        $response = $this->actingAs($this->student)->get(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]));

        $response->assertStatus(200);

        // Notes Tab renders list with both items
        $response->assertSee('Formula Sheet &amp; Important Proofs', false);
        $response->assertSee('NCERT Exemplar Solutions &amp; Mind Map', false);

        // DPP Tab renders list with both DPP sheets
        $response->assertSee('Daily Practice Sheet 1: Real Numbers');
        $response->assertSee('Daily Practice Sheet 2: Polynomials &amp; Zeros', false);
    }

    public function test_daily_homework_features_form_based_selection_for_class_and_subject(): void
    {
        $response = $this->actingAs($this->student)->get(route('homework.index', [
            'class_id' => $this->academicClass->grade_level,
            'subject_id' => $this->subject->id,
        ]));

        $response->assertStatus(200);

        // Form selection elements
        $response->assertSee('Step 1: Select Class / Grade');
        $response->assertSee('Step 2: Select Subject in '.$this->academicClass->display_name);
        $response->assertSee('Filter by Keyword');

        // Sanskrit motivating slogan
        $response->assertSee('तेजस्वि नावधीतमस्तु');

        // Homework item displayed
        $response->assertSee('Assignment 1: Solve NCERT Exercise 1.1');
    }

    public function test_student_dashboard_displays_prominent_streak_and_xp_cards(): void
    {
        $this->student->update([
            'streak_count' => 7,
            'xp_points' => 850,
            'academic_tier' => 'Honors Scholar',
            'last_active_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertStatus(200);

        // Prominent streak and XP cards in student stats
        $response->assertSee('Daily Streak');
        $response->assertSee('7');
        $response->assertSee('Continuous Learning');
        $response->assertSee('Academic XP');
        $response->assertSee('Honors Scholar');
        $response->assertSee('7d Streak');
    }

    public function test_blooms_taxonomy_features_two_tabs_tracker_and_assignments(): void
    {
        $response = $this->actingAs($this->student)->get(route('blooms.index'));
        $response->assertStatus(200);

        // Two tabs navigation
        $response->assertSee("activeTab: 'assignments'", false);
        $response->assertSee("Assignments by Bloom's", false);
        $response->assertSee('Cognitive Tracker');
        $response->assertSee('Your Cognitive Progress Tracker');
        $response->assertSee('Active &amp; Past Daily Prompts', false);
        $response->assertSee('Aim, Ascend, Achieve');
    }

    public function test_weekly_schedule_features_rolling_three_days_window(): void
    {
        $response = $this->actingAs($this->student)->get(route('schedules.index'));
        $response->assertStatus(200);

        // Rolling 3 days view
        $response->assertSee('3-Day View');
        $response->assertSee('Today');
        $response->assertSee('Tomorrow');
        $response->assertSee('Full Week Table');
        $response->assertSee('Aim, Ascend, Achieve');
    }

    public function test_footer_renders_india_credit_and_aim_ascend_achieve_motto(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('Developed by Prithviraj with love for India 🇮🇳');
        $response->assertSee('Aim, Ascend, Achieve');
        $response->assertDontSee('love for Bharat');
    }

    public function test_registration_form_displays_class_options(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Class / Grade');
        $response->assertSee('Select Class...');
        $response->assertSee($this->academicClass->name);
    }

    public function test_student_registration_requires_class(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Kavya Patel',
            'email' => 'kavya.patel@example.com',
            'role' => 'student',
            'phone' => '+91 98765 43210',
            'class_id' => '',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['class_id']);
        $this->assertDatabaseMissing('users', ['email' => 'kavya.patel@example.com']);
    }

    public function test_student_registration_requires_permanent_phone(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Kavya Patel',
            'email' => 'kavya.patel@example.com',
            'role' => 'student',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '', // Missing permanent phone
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', ['email' => 'kavya.patel@example.com']);
    }

    public function test_student_registration_with_class_assigns_batch_and_enrollment(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Kavya Patel',
            'email' => 'kavya.patel@example.com',
            'role' => 'student',
            'phone' => '+91 98765 43210',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'kavya.patel@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->role);
        $this->assertEquals('+91 98765 43210', $user->phone);
        $this->assertEquals($this->batch->id, $user->batch_id);

        // ClassEnrollment record is automatically created
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $this->academicClass->id,
            'student_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_page_reload_does_not_cause_unlimited_xp_glitch(): void
    {
        $this->student->update([
            'xp_points' => 100,
            'last_active_date' => null,
        ]);

        // 1. First visit today gives initial check-in XP (+25)
        $this->actingAs($this->student)->get(route('dashboard'));
        $this->student->refresh();
        $this->assertEquals(125, $this->student->xp_points);
        $this->assertEquals(now()->toDateString(), $this->student->last_active_date);

        // 2. Reloading / visiting dashboard again on the same day must NOT increase XP
        $this->actingAs($this->student)->get(route('dashboard'));
        $this->student->refresh();
        $this->assertEquals(125, $this->student->xp_points, 'XP must not inflate on dashboard reload');

        // 3. Visiting other passive routes on the same day must not inflate XP
        $this->actingAs($this->student)->get(route('attendance.index'));
        $this->student->refresh();
        $this->assertEquals(125, $this->student->xp_points, 'XP must not inflate on attendance page visit');

        $this->actingAs($this->student)->get(route('dpp.index'));
        $this->student->refresh();
        $this->assertEquals(125, $this->student->xp_points, 'XP must not inflate on DPP index visit');
    }

    public function test_faculty_can_upload_pdf_note_and_student_can_view_and_download(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('real_numbers_theorems.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->teacher)->post(route('batches.notes.upload', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]), [
            'title' => 'Real Numbers Theorem Proofs Guide',
            'note_type' => 'theory',
            'reading_minutes' => 15,
            'pdf_file' => $file,
        ]);

        $response->assertRedirect(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]));
        $response->assertSessionHas('success');

        // Check note was created with download_url and null content
        $this->assertDatabaseHas('curriculum_notes', [
            'topic_id' => $this->topic->id,
            'title' => 'Real Numbers Theorem Proofs Guide',
            'note_type' => 'theory',
            'content' => null,
            'is_published' => true,
        ]);

        // Student views learning workspace Tab 2
        $learnResponse = $this->actingAs($this->student)->get(route('batches.learn', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]));

        $learnResponse->assertStatus(200);
        $learnResponse->assertSee('Real Numbers Theorem Proofs Guide');
        $learnResponse->assertSee('PDF • Theory Notes');
        $learnResponse->assertSee('View PDF');
        $learnResponse->assertSee('Download');
    }

    public function test_student_cannot_upload_pdf_notes(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malicious.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->student)->post(route('batches.notes.upload', [
            'batch' => $this->batch->id,
            'subject' => $this->subject->id,
            'chapter' => $this->topic->id,
        ]), [
            'title' => 'Student Note',
            'note_type' => 'theory',
            'pdf_file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_faculty_can_delete_uploaded_pdf_note(): void
    {
        $note = CurriculumNote::create([
            'topic_id' => $this->topic->id,
            'title' => 'Outdated Practice Guide',
            'note_type' => 'revision',
            'download_url' => 'https://example.com/guide.pdf',
            'is_published' => true,
        ]);

        // Student cannot delete
        $studentAttempt = $this->actingAs($this->student)->delete(route('batches.notes.delete', $note->id));
        $studentAttempt->assertStatus(403);

        // Teacher can delete
        $teacherAttempt = $this->actingAs($this->teacher)->delete(route('batches.notes.delete', $note->id));
        $teacherAttempt->assertRedirect();
        $this->assertDatabaseMissing('curriculum_notes', ['id' => $note->id]);
    }
}
