<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademyNotice;
use App\Models\AcademySetting;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\BloomAssignment;
use App\Models\Course;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DailyLesson;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\InAppNotification;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\StudentLeaveRequest;
use App\Models\Submission;
use App\Models\TopicBloomActivity;
use App\Models\User;
use Database\Seeders\AcademicAdditionsSeeder;
use Database\Seeders\AcademyInstitutionalSeeder;
use Database\Seeders\BloomsTaxonomySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SyllabusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveAllFeaturesAndFormsTest extends TestCase
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
        $this->seed(QuestionBankSeeder::class);
        $this->seed(AcademyInstitutionalSeeder::class);
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
                'name' => 'Aryabhata Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'STU-001',
                'roll_number' => 1,
                'streak_count' => 4,
                'xp_points' => 300,
                'academic_tier' => 'Distinguished Scholar',
            ]
        );

        $this->class8 = AcademicClass::where('grade_level', 8)->first() ?? AcademicClass::first();
        $this->scienceSubject = AcademicSubject::where('class_id', $this->class8->id)->first() ?? AcademicSubject::first();
        $this->class8->update(['class_teacher_id' => $this->teacher->id]);
        $this->scienceSubject->update(['teacher_id' => $this->teacher->id]);

        $maxRoll = $this->class8->enrollments()->max('roll_number') ?? 0;
        $this->class8->students()->syncWithoutDetaching([
            $this->student->id => ['roll_number' => $maxRoll + 1, 'status' => 'active']
        ]);
    }

    /**
     * 1. Test Authentic Institutional Logins, Role Switching, and Logout.
     */
    public function test_institutional_logins_and_logout_work_seamlessly(): void
    {
        // Institutional login as admin
        $resAdmin = $this->post(route('login.post'), [
            'email' => $this->admin->email,
            'password' => 'password123',
        ]);
        $resAdmin->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('admin', auth()->user()->role);

        // Institutional login as teacher
        $resTeacher = $this->post(route('login.post'), [
            'email' => $this->teacher->email,
            'password' => 'password123',
        ]);
        $resTeacher->assertRedirect(route('dashboard'));
        $this->assertEquals('teacher', auth()->user()->role);

        // Institutional login as student
        $resStudent = $this->post(route('login.post'), [
            'email' => $this->student->email,
            'password' => 'password123',
        ]);
        $resStudent->assertRedirect(route('dashboard'));
        $this->assertEquals('student', auth()->user()->role);

        // Logout
        $resLogout = $this->post(route('logout'));
        $resLogout->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 2. Test Dashboards for Admin, Teacher, and Student.
     */
    public function test_dashboard_renders_for_all_roles_with_streak_xp_motivation(): void
    {
        // Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('dashboard'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Admin');

        // Teacher
        $resTeacher = $this->actingAs($this->teacher)->get(route('dashboard'));
        $resTeacher->assertStatus(200);
        $resTeacher->assertSee('TEACHER PORTAL');

        // Student
        $resStudent = $this->actingAs($this->student)->get(route('dashboard'));
        $resStudent->assertStatus(200);
        $resStudent->assertSee('STUDENT PORTAL');
        $resStudent->assertSee('Streak');
        $resStudent->assertSee('XP');
    }

    /**
     * 3. Test Profile View and Update Form.
     */
    public function test_profile_view_and_update_form(): void
    {
        $res = $this->actingAs($this->student)->get(route('profile.show'));
        $res->assertStatus(200);
        $res->assertSee('Personal &amp; Academic Information', false);

        $updateRes = $this->actingAs($this->student)->put(route('profile.update'), [
            'name' => 'Aryabhata Sharma (Updated)',
            'phone' => '+91 98765 43210',
            'address' => '742 Ancient Mathematical Boulevard, Pataliputra',
            'bio' => 'Passionate about astrophysics, number theory, and celestial orbits.',
            'blood_group' => 'O+',
            'guardian_name' => 'Soma Sharma',
            'guardian_phone' => '+91 98765 00000',
            'guardian_email' => 'guardian@primearc.academy',
        ]);

        $updateRes->assertRedirect();
        $this->student->refresh();
        $this->assertEquals('Aryabhata Sharma (Updated)', $this->student->name);
        $this->assertEquals('O+', $this->student->blood_group);
        $this->assertEquals('Soma Sharma', $this->student->guardian_name);
    }

    /**
     * 4. Test virtual student identity card rendering and verification link.
     */
    public function test_virtual_id_card_renders_with_privilege_and_cryptographic_verification(): void
    {
        $res = $this->actingAs($this->student)->get(route('idcard.show'));
        $res->assertStatus(200);
        $res->assertSee('Student Identity Card');
        $res->assertSee('Institution-issued identity card');
        $res->assertDontSee('Presidential Scholar');
        $res->assertDontSee('PRIVILEGE PASS');
        $res->assertSee('Print / Save');
    }

    /**
     * 5. Test Course Creation, Catalog, and Enrollment.
     */
    public function test_course_creation_and_student_enrollment(): void
    {
        // Teacher creates course
        $storeCourseRes = $this->actingAs($this->teacher)->post(route('courses.store'), [
            'code' => 'QUANT-101',
            'title' => 'Quantum Mechanics & Wave Functions',
            'category' => 'Physics',
            'description' => 'Comprehensive introduction to wave-particle duality and Schrödinger equations.',
            'credits' => 4,
            'capacity' => 40,
        ]);

        $course = Course::where('code', 'QUANT-101')->first();
        $this->assertNotNull($course);
        $storeCourseRes->assertRedirect(route('courses.show', $course->id));

        // Course details view
        $showCourseRes = $this->actingAs($this->student)->get(route('courses.show', $course->id));
        $showCourseRes->assertStatus(200);
        $showCourseRes->assertSee('Quantum Mechanics');

        // Student enrolls in course
        $enrollRes = $this->actingAs($this->student)->post(route('courses.enroll', $course->id));
        $enrollRes->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
        ]);
    }

    /**
     * 6. Test Assignment Creation, Submission, and Grading.
     */
    public function test_assignment_workflow_create_submit_grade(): void
    {
        $course = Course::first();

        // Teacher creates assignment
        $storeRes = $this->actingAs($this->teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Comprehensive Analytical Problem Set 1',
            'description' => 'Solve problems 1 through 15 on pages 120-125.',
            'due_date' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'max_points' => 100,
        ]);
        $storeRes->assertRedirect();

        $assignment = Assignment::where('title', 'Comprehensive Analytical Problem Set 1')->first();
        $this->assertNotNull($assignment);

        // Student submits assignment
        $submitRes = $this->actingAs($this->student)->post(route('assignments.submit', $assignment->id), [
            'content' => 'Attached are complete mathematical proofs and calculations for all 15 problems.',
        ]);
        $submitRes->assertRedirect();

        $submission = Submission::where('assignment_id', $assignment->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($submission);

        // Teacher grades submission
        $gradeRes = $this->actingAs($this->teacher)->post(route('submissions.grade', $submission->id), [
            'grade' => 96.5,
            'feedback' => 'Exceptional derivations and rigorous notation throughout.',
        ]);
        $gradeRes->assertRedirect();

        $submission->refresh();
        $this->assertEquals(96.5, $submission->grade);
        $this->assertEquals('Exceptional derivations and rigorous notation throughout.', $submission->feedback);
    }

    /**
     * 7. Test Class Schedule Slot Addition.
     */
    public function test_schedule_slot_creation_and_view(): void
    {
        $course = Course::first();

        $storeRes = $this->actingAs($this->teacher)->post(route('schedules.store'), [
            'course_id' => $course->id,
            'title' => 'Theoretical Lecture Session',
            'day_of_week' => 'Wednesday',
            'start_time' => '11:00',
            'end_time' => '12:30',
            'room' => 'Hall of Ramanujan 301',
        ]);
        $storeRes->assertRedirect();

        $this->assertDatabaseHas('schedules', [
            'course_id' => $course->id,
            'day_of_week' => 'Wednesday',
            'room' => 'Hall of Ramanujan 301',
        ]);

        $viewRes = $this->actingAs($this->teacher)->get(route('schedules.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Hall of Ramanujan 301');
    }

    /**
     * 8. Test Online Fees Module: Invoice Issuing, Online Checkout, and Official Receipt.
     */
    public function test_fees_module_invoice_creation_online_payment_and_receipt(): void
    {
        // Admin issues fee invoice
        $storeRes = $this->actingAs($this->admin)->post(route('fees.store'), [
            'student_id' => $this->student->id,
            'title' => 'Academic Term Tuition & Innovation Lab Fee',
            'category' => 'Tuition',
            'amount' => 15000.00,
            'discount' => 1000.00,
            'due_date' => now()->addDays(20)->toDateString(),
            'notes' => 'Early enrollment academic discount applied.',
        ]);
        $storeRes->assertRedirect();

        $invoice = FeeInvoice::where('title', 'Academic Term Tuition & Innovation Lab Fee')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(14000.00, $invoice->total_due);
        $this->assertEquals(14000.00, $invoice->balance);

        // Invoice detail view
        $showRes = $this->actingAs($this->student)->get(route('fees.show', $invoice->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Academic Term Tuition');

        // Student settles fee online
        $payRes = $this->actingAs($this->student)->post(route('fees.pay', $invoice->id), [
            'amount' => 14000.00,
            'payment_method' => 'UPI / NetBanking',
        ]);

        $payment = FeePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $payRes->assertRedirect(route('fees.receipt', $payment->id));

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.00, $invoice->balance);

        // Printable receipt view
        $receiptRes = $this->actingAs($this->student)->get(route('fees.receipt', $payment->id));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee('OFFICIALLY PAID');
        $receiptRes->assertSee($payment->receipt_number);
    }

    /**
     * 9. Test Bloom's Taxonomy Daily Engine: Log Lesson, Roll-number Assignment, Student Submission, and Grading.
     */
    public function test_blooms_taxonomy_daily_lesson_engine(): void
    {
        $topic = CurriculumTopic::where('subject_id', $this->scienceSubject->id)->first();
        if (!$topic) {
            $topic = CurriculumTopic::create([
                'subject_id' => $this->scienceSubject->id,
                'chapter_number' => 1,
                'title' => 'Cellular Structure & Organelles',
                'description' => 'Plant and animal cell comparison',
                'subtopics' => ['Mitochondria', 'Chloroplasts'],
            ]);
        }

        // Create Bloom's activities 1-6 for the topic
        for ($l = 1; $l <= 6; $l++) {
            TopicBloomActivity::firstOrCreate(
                ['topic_id' => $topic->id, 'bloom_level' => $l],
                ['activity_prompt' => "Level {$l} activity for {$topic->title}"]
            );
        }

        // Teacher records lesson
        $logLessonRes = $this->actingAs($this->teacher)->post(route('blooms.lesson.store'), [
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'topic_id' => $topic->id,
            'subtopic' => 'Organelle energetics and ATP synthesis',
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'balanced_random',
            'lesson_notes' => 'Taught cellular respiration and ATP cycle.',
        ]);
        $logLessonRes->assertRedirect();

        $lesson = DailyLesson::where('topic_id', $topic->id)->latest('id')->first();
        $this->assertNotNull($lesson);

        // Verify roll-number assigned Bloom's record for student
        $assignment = BloomAssignment::where('lesson_id', $lesson->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($assignment);
        $this->assertGreaterThanOrEqual(1, $assignment->bloom_level);
        $this->assertLessThanOrEqual(6, $assignment->bloom_level);

        // Student submits Bloom's response
        $submitRes = $this->actingAs($this->student)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'Here is my complete analytical breakdown comparing plant and animal cell chloroplasts.',
        ]);
        $submitRes->assertRedirect();

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);

        // Teacher grades Bloom's response
        $gradeRes = $this->actingAs($this->teacher)->post(route('blooms.assignment.grade', $assignment->id), [
            'score' => 95.0,
            'teacher_feedback' => 'Superb understanding of organelle differentiation at this cognitive tier.',
        ]);
        $gradeRes->assertRedirect();

        $assignment->refresh();
        $this->assertEquals('reviewed', $assignment->status);
        $this->assertEquals(95.0, $assignment->score);

        // Test sample and export CSVs
        $sampleCsvRes = $this->actingAs($this->teacher)->get(route('blooms.sample-students-csv'));
        $sampleCsvRes->assertStatus(200);

        $exportCsvRes = $this->actingAs($this->teacher)->get(route('blooms.export-students-csv', $this->class8->id));
        $exportCsvRes->assertStatus(200);
    }

    /**
     * 10. Test Chapterwise Syllabus Section & Topic Progress Update.
     */
    public function test_syllabus_navigation_and_progress_updating(): void
    {
        $res = $this->actingAs($this->student)->get(route('syllabus.index', ['class_id' => $this->class8->id]));
        $res->assertStatus(200);
        $res->assertSee('Chapterwise Academic Syllabus');

        $topic = CurriculumTopic::where('subject_id', $this->scienceSubject->id)->first();
        $this->assertNotNull($topic);

        // Teacher updates topic progress
        $updateProgressRes = $this->actingAs($this->teacher)->patch(route('syllabus.topics.progress', $topic->id), [
            'status' => 'completed',
            'study_notes' => 'All lectures, diagrams, and revision worksheets completed.',
            'resource_url' => 'https://resources.primearc.academy/ch1.pdf',
        ]);
        $updateProgressRes->assertRedirect();

        $topic->refresh();
        $this->assertEquals('completed', $topic->status);
    }

    /**
     * 11. Test Attendance System, Defaulters, and Leave Workflow.
     */
    public function test_attendance_and_student_leave_requests(): void
    {
        // 1. Teacher records attendance
        $recordRes = $this->actingAs($this->teacher)->post(route('attendance.store'), [
            'class_id' => $this->class8->id,
            'session_date' => now()->toDateString(),
            'title' => 'Morning Science Lecture',
            'notes' => 'Full engagement in scientific experimentation.',
            'attendance' => [
                $this->student->id => 'present',
            ],
        ]);
        $recordRes->assertRedirect();

        $this->assertDatabaseHas('attendance_sessions', [
            'class_id' => $this->class8->id,
            'title' => 'Morning Science Lecture',
        ]);

        // 2. Student applies for leave
        $leaveRes = $this->actingAs($this->student)->post(route('leaves.store'), [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'type' => 'academic',
            'reason' => 'Attending All-India Inter-School Science Congress.',
        ]);
        $leaveRes->assertRedirect(route('leaves.index', ['tab' => 'list']));

        $leave = StudentLeaveRequest::where('student_id', $this->student->id)->latest('id')->first();
        $this->assertNotNull($leave);
        $this->assertEquals('pending', $leave->status);

        // 3. Teacher approves leave
        $reviewRes = $this->actingAs($this->teacher)->patch(route('leaves.review', $leave->id), [
            'status' => 'approved',
            'teacher_remarks' => 'Approved. Submit conference certificate upon return.',
        ]);
        $reviewRes->assertRedirect();

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
    }

    /**
     * 12. Test Official Notice Board Publication & Withdrawal.
     */
    public function test_official_notice_board_publish_and_destroy(): void
    {
        // Teacher publishes notice
        $storeRes = $this->actingAs($this->teacher)->post(route('notices.store'), [
            'title' => 'Annual Science Exhibition 2026 Announcement',
            'content' => 'All students are invited to register innovative working models by next week.',
            'priority' => 'high',
            'target_audience' => 'all',
            'is_pinned' => 0,
        ]);
        $storeRes->assertRedirect();

        $notice = AcademyNotice::where('title', 'Annual Science Exhibition 2026 Announcement')->first();
        $this->assertNotNull($notice);

        // Teacher deletes notice
        $deleteRes = $this->actingAs($this->teacher)->delete(route('notices.destroy', $notice->id));
        $deleteRes->assertRedirect();

        $this->assertDatabaseMissing('academy_notices', ['id' => $notice->id]);
    }

    /**
     * 13. Test In-App Notification Hub & Mark All Read.
     */
    public function test_in_app_notifications_and_mark_all_read(): void
    {
        InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'fees',
            'title' => 'Invoice Settled',
            'message' => 'Your academic fee invoice was settled in full.',
            'action_url' => '/fees',
            'icon' => '💳',
            'is_read' => false,
        ]);

        $apiRes = $this->actingAs($this->student)->getJson(route('notifications.unread'));
        $apiRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $apiRes->json('unread_count'));

        $markAllRes = $this->actingAs($this->student)->post(route('notifications.mark-all-read'));
        $markAllRes->assertRedirect();

        $unreadCount = InAppNotification::where('user_id', $this->student->id)->where('is_read', false)->count();
        $this->assertEquals(0, $unreadCount);
    }

    /**
     * 14. Test Daily Homework Assignment, Submission, and Verification.
     */
    public function test_daily_homework_workflow(): void
    {
        // Teacher assigns homework
        $storeRes = $this->actingAs($this->teacher)->post(route('homework.store'), [
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'title' => 'Photosynthesis Chemical Reaction Worksheet',
            'instructions' => 'Complete the balancing equation worksheet on slide 14.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        $storeRes->assertRedirect();

        $task = HomeworkTask::where('title', 'Photosynthesis Chemical Reaction Worksheet')->first();
        $this->assertNotNull($task);

        // Student submits homework
        $submitRes = $this->actingAs($this->student)->post(route('homework.submit', $task->id), [
            'student_notes' => 'All 6 balancing chemical questions solved with reactants and products labeled.',
        ]);
        $submitRes->assertRedirect();

        $sub = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($sub);

        // Teacher verifies homework
        $checkRes = $this->actingAs($this->teacher)->patch(route('homework.submissions.check', $sub->id), [
            'status' => 'checked',
            'teacher_feedback' => 'Clean balanced equations with stoichiometry noted.',
        ]);
        $checkRes->assertRedirect();

        $sub->refresh();
        $this->assertEquals('checked', $sub->status);
    }

    /**
     * 15. Test Analytics and Settings.
     */
    public function test_analytics_and_settings_access(): void
    {
        $analyticsRes = $this->actingAs($this->teacher)->get(route('analytics.index'));
        $analyticsRes->assertStatus(200);
        $analyticsRes->assertSee('Institutional Intelligence');

        $settingsRes = $this->actingAs($this->admin)->get(route('settings.index'));
        $settingsRes->assertStatus(200);
        $settingsRes->assertSee('Institutional Settings');
    }
}
