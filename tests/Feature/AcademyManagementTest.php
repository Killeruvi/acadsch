<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Models\User;
use App\Services\SchoolBrandingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SchoolBrandingService::flush();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_can_authenticate_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'principal@primearc.edu.in',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_can_retrieve_course_catalog_via_api(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'code', 'category', 'credits', 'enrollments_count'],
                ],
            ]);
    }

    public function test_can_retrieve_single_course_details(): void
    {
        $student = User::where('role', 'student')->first();
        $course = Course::first();

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonPath('course.id', $course->id)
            ->assertJsonStructure([
                'course' => [
                    'id',
                    'title',
                    'code',
                    'teacher',
                    'schedules',
                    'assignments',
                ],
            ]);
    }

    public function test_student_can_enroll_in_course(): void
    {
        $student = User::where('role', 'student')->first();

        // Find or create course not yet enrolled in
        $unEnrolledCourse = Course::whereDoesntHave('enrollments', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->first();

        if (! $unEnrolledCourse) {
            $unEnrolledCourse = Course::create([
                'title' => 'Advanced Robotics',
                'code' => 'ROB401',
                'category' => 'Engineering',
                'credits' => 4,
                'capacity' => 20,
            ]);
        }

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/enrollments', [
                'course_id' => $unEnrolledCourse->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('enrollment.course_id', $unEnrolledCourse->id)
            ->assertJsonPath('enrollment.student_id', $student->id);

        $this->assertDatabaseHas('enrollments', [
            'course_id' => $unEnrolledCourse->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_student_can_submit_assignment_and_teacher_can_grade(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $student = User::where('role', 'student')->first();

        $course = Course::create([
            'title' => 'Software Testing',
            'code' => 'TEST101',
            'category' => 'Computer Science',
            'credits' => 3,
            'capacity' => 25,
            'teacher_id' => $teacher->id,
        ]);

        Enrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'enrolled',
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Unit Testing Workshop',
            'description' => 'Write automated PHPUnit feature tests',
            'due_date' => now()->addDays(5),
            'max_points' => 100,
        ]);

        // Student submits
        $subResponse = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
                'content' => 'All feature tests passing with 100% assertions.',
                'attachment_url' => 'https://github.com/student/test-suite',
            ]);

        $subResponse->assertStatus(201)
            ->assertJsonPath('submission.status', 'submitted');

        $submissionId = $subResponse->json('submission.id');

        // Teacher grades
        $gradeResponse = $this->actingAs($teacher, 'sanctum')
            ->putJson("/api/v1/submissions/{$submissionId}/grade", [
                'grade' => 98.5,
                'feedback' => 'Flawless coverage and clean assertions.',
            ]);

        $gradeResponse->assertStatus(200)
            ->assertJsonPath('submission.grade', '98.50')
            ->assertJsonPath('submission.status', 'graded');
    }

    public function test_analytics_overview_endpoint(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/analytics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_students',
                    'total_teachers',
                    'total_courses',
                    'total_enrollments',
                    'total_assignments',
                    'total_submissions',
                    'average_grade_percentage',
                ],
                'courses_by_category',
                'top_courses_by_enrollment',
                'recent_submissions',
            ]);
    }

    public function test_web_institutional_login_and_dashboard_renders(): void
    {
        $response = $this->post('/login', [
            'email' => 'principal@primearc.edu.in',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertStatus(200)
            ->assertSee('Dr. Rajeshwar Sharma')
            ->assertSee('ADMIN PORTAL');
    }

    public function test_web_courses_page_renders(): void
    {
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($student)->get('/courses');
        $response->assertStatus(200)
            ->assertSee('Courses Catalog')
            ->assertSee('CS101');
    }

    public function test_course_creation_form_modal_has_all_fields_and_submits_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        $teacher = User::where('role', 'teacher')->first();

        // 1. Check form modal renders all fields for faculty/admin
        $pageResponse = $this->actingAs($admin)->get('/courses');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Create New Course');
        $pageResponse->assertSee('name="code"', false);
        $pageResponse->assertSee('name="title"', false);
        $pageResponse->assertSee('name="category"', false);
        $pageResponse->assertSee('name="credits"', false);
        $pageResponse->assertSee('name="capacity"', false);
        $pageResponse->assertSee('name="teacher_id"', false);
        $pageResponse->assertSee('name="status"', false);
        $pageResponse->assertSee('name="start_date"', false);
        $pageResponse->assertSee('name="end_date"', false);
        $pageResponse->assertSee('name="description"', false);

        // 2. Submit course with full field payload
        $storeResponse = $this->actingAs($admin)->post(route('courses.store'), [
            'code' => 'BIO-201',
            'title' => 'Cellular & Molecular Biology',
            'category' => 'Biology',
            'credits' => 4,
            'capacity' => 45,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-12-01',
            'end_date' => '2027-05-30',
            'description' => 'Comprehensive laboratory course exploring cellular mechanics and genetics.',
        ]);

        $created = Course::where('code', 'BIO-201')->first();
        $this->assertNotNull($created);
        $this->assertEquals(45, $created->capacity);
        $this->assertEquals($teacher->id, $created->teacher_id);
        $this->assertEquals('active', $created->status);
        $this->assertEquals('2026-12-01', $created->start_date->format('Y-m-d'));
        $this->assertEquals('2027-05-30', $created->end_date->format('Y-m-d'));
        $storeResponse->assertRedirect(route('courses.show', $created->id));
    }

    public function test_course_creation_generates_full_structure_and_renders_tabbed_sections_with_notes(): void
    {
        $teacher = User::where('role', 'teacher')->first();

        // 1. Create course (clean slate without dummy auto-generation)
        $res = $this->actingAs($teacher)->post(route('courses.store'), [
            'code' => 'CS-302',
            'title' => 'Advanced Operating Systems & Kernel Architecture',
            'category' => 'Computer Science',
            'credits' => 4,
            'capacity' => 40,
            'description' => 'Operating systems kernel design, process synchronization, and memory hierarchies.',
        ]);

        $course = Course::where('code', 'CS-302')->first();
        $this->assertNotNull($course);
        $res->assertRedirect(route('courses.show', $course->id));

        // Verify course starts clean (no pre-filled dummy content)
        $this->assertEquals(0, $course->modules()->count());
        $this->assertEquals(0, $course->lessons()->count());

        // 2. Direct manual module creation
        $modRes = $this->actingAs($teacher)->post(route('courses.modules.store', $course->id), [
            'title' => 'Module 1: Kernel Subsystems & Scheduling',
            'order_index' => 1,
            'theme' => 'Operating Systems',
            'description' => 'Process management and thread scheduling.',
        ]);
        $modRes->assertSessionHas('success');
        $module = $course->modules()->first();
        $this->assertNotNull($module);
        $this->assertEquals('Module 1: Kernel Subsystems & Scheduling', $module->title);

        // 3. Direct manual lesson creation
        $lesRes = $this->actingAs($teacher)->post(route('courses.lessons.store', $course->id), [
            'module_id' => $module->id,
            'title' => 'Process Synchronization & Semaphore Primitives',
            'lesson_number' => 1,
            'duration_minutes' => 60,
            'topic_summary' => 'Mutexes, semaphores, and reader-writer locking primitives.',
        ]);
        $lesRes->assertSessionHas('success');
        $this->assertEquals(1, $course->lessons()->count());

        // 4. Verify Course Show page renders curriculum and management options
        $showRes = $this->actingAs($teacher)->get(route('courses.show', $course->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Chapter Deadlines');
        $showRes->assertSee('Schedule &amp; Timetable', false);
        $showRes->assertSee('Video Lectures');
        $showRes->assertSee('DPP (Daily Practice)', false);
        $showRes->assertSee('Homework &amp; Tasks', false);
        $showRes->assertSee('Tests &amp; Quizzes', false);
        $showRes->assertSee('Curriculum Structure &amp; Content Management', false);
    }

    public function test_web_schedule_page_renders(): void
    {
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($student)->get('/schedule');
        $response->assertStatus(200)
            ->assertSee('Academic Timetable')
            ->assertSee('Monday');
    }

    public function test_web_profile_page_renders_and_updates(): void
    {
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($student)->get('/profile');
        $response->assertStatus(200)
            ->assertSee('Personal & Academic Information')
            ->assertSee($student->name);

        $updateResponse = $this->actingAs($student)->put('/profile', [
            'name' => 'Aarav K. Sharma',
            'phone' => '+91 98101 99999',
            'address' => 'Flat 402, Lotus Enclave, New Delhi',
            'blood_group' => 'O+',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Aarav K. Sharma',
            'phone' => '+91 98101 99999',
        ]);
    }

    public function test_web_virtual_id_card_renders(): void
    {
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($student)->get('/id-card');
        $response->assertStatus(200)
            ->assertSee('Student Identity Card')
            ->assertSee(strtoupper(SchoolBrandingService::getInstituteName()))
            ->assertSee($student->admission_number ?: 'Not assigned');
    }

    public function test_student_cannot_self_pay_fees_online_and_only_cashier_can_record(): void
    {
        $student = User::where('role', 'student')->first();
        $admin = User::where('role', 'admin')->first();
        $invoice = FeeInvoice::where('student_id', $student->id)
            ->where('status', 'pending')
            ->first();

        if (! $invoice) {
            $invoice = FeeInvoice::create([
                'invoice_number' => 'INV-TEST-001',
                'student_id' => $student->id,
                'title' => 'Lab Usage Fee',
                'category' => 'Lab & Tech Fee',
                'amount' => 150.00,
                'total_due' => 150.00,
                'paid_amount' => 0.00,
                'status' => 'pending',
                'due_date' => now()->addDays(7)->toDateString(),
            ]);
        }

        // Student is forbidden from recording payment
        $forbiddenResponse = $this->actingAs($student)->post("/fees/{$invoice->id}/pay", [
            'amount' => $invoice->balance,
            'payment_method' => 'Credit Card',
        ]);
        $forbiddenResponse->assertStatus(403);

        // Admin records cashier payment
        $response = $this->actingAs($admin)->post(route('school.fees.pay', $invoice->id), [
            'amount' => $invoice->balance,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
            'notes' => 'Settled via cashier desk.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('fee_payments', [
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_issue_fee_invoice(): void
    {
        $admin = User::where('role', 'admin')->first();
        $student = User::where('role', 'student')->first();

        $response = $this->actingAs($admin)->post('/fees', [
            'student_id' => $student->id,
            'title' => 'Annual Matriculation Fee',
            'category' => 'Registration',
            'amount' => 300.00,
            'discount' => 25.00,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_invoices', [
            'student_id' => $student->id,
            'title' => 'Annual Matriculation Fee',
            'total_due' => 275.00,
        ]);
    }
}
