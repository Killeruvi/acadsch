<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\InAppNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $otherTeacher;

    private User $student;

    private User $otherStudent;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_test@primearc.edu',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher_test@primearc.edu',
            'is_approved' => true,
        ]);

        $this->otherTeacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'other_teacher_test@primearc.edu',
            'is_approved' => true,
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent_test@primearc.edu',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_test@primearc.edu',
            'parent_id' => $this->parent->id,
            'is_approved' => true,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => 'student',
            'email' => 'other_student_test@primearc.edu',
            'is_approved' => true,
        ]);
    }

    private function createTestCourse(User $teacher): Course
    {
        return Course::create([
            'title' => 'Advanced Physics',
            'code' => 'PHYS-'.uniqid(),
            'description' => 'Calculus based physics',
            'credits' => 4,
            'category' => 'Science',
            'level' => 'Grade 12',
            'teacher_id' => $teacher->id,
            'capacity' => 50,
            'is_published' => true,
        ]);
    }

    public function test_parent_dashboard_requires_parent_or_admin_role(): void
    {
        // Unauthenticated
        $this->get(route('school.parent.dashboard'))->assertRedirect(route('login'));

        // Student forbidden
        $this->actingAs($this->student)
            ->get(route('school.parent.dashboard'))
            ->assertStatus(403);

        // Teacher forbidden
        $this->actingAs($this->teacher)
            ->get(route('school.parent.dashboard'))
            ->assertStatus(403);

        // Parent authorized
        $this->actingAs($this->parent)
            ->get(route('school.parent.dashboard'))
            ->assertStatus(200);

        // Admin authorized
        $this->actingAs($this->admin)
            ->get(route('school.parent.dashboard'))
            ->assertStatus(200);
    }

    public function test_course_enrollment_prevents_idor(): void
    {
        $course = $this->createTestCourse($this->teacher);

        // Student cannot enroll other student
        $this->actingAs($this->student)
            ->post(route('courses.enroll', $course->id), [
                'student_id' => $this->otherStudent->id,
            ])
            ->assertStatus(403);

        // Student can enroll self
        $this->actingAs($this->student)
            ->post(route('courses.enroll', $course->id), [
                'student_id' => $this->student->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_api_enrollment_store_and_index_scoping(): void
    {
        $course = $this->createTestCourse($this->teacher);

        Enrollment::create([
            'course_id' => $course->id,
            'student_id' => $this->otherStudent->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        // Student cannot enroll other student via API
        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/enrollments', [
                'course_id' => $course->id,
                'student_id' => $this->otherStudent->id,
            ])
            ->assertStatus(403);

        // Student can enroll self via API
        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/enrollments', [
                'course_id' => $course->id,
            ])
            ->assertStatus(201);

        // Student only sees personal enrollments in API index
        $indexResponse = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/enrollments')
            ->assertStatus(200);

        $data = $indexResponse->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->student->id, $data[0]['student_id']);
    }

    public function test_batch_administrative_actions_require_teacher_or_admin(): void
    {
        // Student forbidden from creating batches
        $this->actingAs($this->student)
            ->post(route('batches.create'), [
                'name' => 'Unauthorized Batch',
            ])
            ->assertStatus(403);

        // Teacher allowed
        $this->actingAs($this->teacher)
            ->post(route('batches.create'), [
                'name' => 'Physics Batch 2027',
                'class_level' => 'Class 11',
                'academic_year' => '2026-2027',
            ])
            ->assertRedirect();
    }

    public function test_virtual_id_card_authorization(): void
    {
        // Student viewing self
        $this->actingAs($this->student)
            ->get(route('idcard.show', $this->student->id))
            ->assertStatus(200);

        // Student viewing another student is forbidden
        $this->actingAs($this->student)
            ->get(route('idcard.show', $this->otherStudent->id))
            ->assertStatus(403);

        // Parent viewing own child is allowed
        $this->actingAs($this->parent)
            ->get(route('idcard.show', $this->student->id))
            ->assertStatus(200);

        // Parent viewing unrelated student is forbidden
        $this->actingAs($this->parent)
            ->get(route('idcard.show', $this->otherStudent->id))
            ->assertStatus(403);

        // Faculty cannot view a student outside assigned classes.
        $this->actingAs($this->teacher)
            ->get(route('idcard.show', $this->student->id))
            ->assertStatus(403);
        $this->actingAs($this->teacher)
            ->get(route('school.students.id-card', $this->student->id))
            ->assertStatus(403);

        $assignedClass = AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'section' => 'A',
            'academic_year' => '2026-2027',
            'class_teacher_id' => $this->teacher->id,
        ]);
        $this->student->update(['current_class_id' => $assignedClass->id]);

        $this->actingAs($this->teacher)
            ->get(route('idcard.show', $this->student->id))
            ->assertStatus(200);
        $this->actingAs($this->teacher)
            ->get(route('school.students.id-card', $this->student->id))
            ->assertStatus(200);

        // A different teacher cannot access the same student's card.
        $this->actingAs($this->otherTeacher)
            ->get(route('idcard.show', $this->student->id))
            ->assertStatus(403);
    }

    public function test_holiday_modifications_require_admin(): void
    {
        // Student forbidden from modifying institutional calendar/holidays
        $this->actingAs($this->student)
            ->post(route('school.holidays.store'), [
                'name' => 'Winter Vacation',
                'from_date' => '2026-12-25',
                'to_date' => '2026-12-31',
            ])
            ->assertStatus(403);

        // Teacher forbidden from admin holiday management
        $this->actingAs($this->teacher)
            ->post(route('school.holidays.store'), [
                'name' => 'Winter Vacation',
                'from_date' => '2026-12-25',
                'to_date' => '2026-12-31',
            ])
            ->assertStatus(403);
    }

    public function test_csv_exports_require_faculty(): void
    {
        $this->actingAs($this->student)
            ->get(route('blooms.sample-students-csv'))
            ->assertStatus(403);

        $this->actingAs($this->student)
            ->get(route('dpp.sample-csv'))
            ->assertStatus(403);

        $this->actingAs($this->student)
            ->get(route('syllabus.sample-csv'))
            ->assertStatus(403);

        $this->actingAs($this->teacher)
            ->get(route('dpp.sample-csv'))
            ->assertStatus(200);
    }

    public function test_api_assignment_ownership_protection(): void
    {
        $course = $this->createTestCourse($this->teacher);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Thermodynamics Lab',
            'due_date' => now()->addDays(7),
            'max_points' => 100,
        ]);

        // Other teacher cannot update this teacher's assignment
        $this->actingAs($this->otherTeacher, 'sanctum')
            ->putJson("/api/v1/assignments/{$assignment->id}", [
                'title' => 'Hijacked Assignment',
            ])
            ->assertStatus(403);

        // Creator teacher can update
        $this->actingAs($this->teacher, 'sanctum')
            ->putJson("/api/v1/assignments/{$assignment->id}", [
                'title' => 'Updated Lab Title',
            ])
            ->assertStatus(200);

        // Admin can update any
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/assignments/{$assignment->id}", [
                'title' => 'Admin Verified Title',
            ])
            ->assertStatus(200);
    }

    public function test_school_fee_receipt_authorization(): void
    {
        $invoice = FeeInvoice::create([
            'title' => 'Tuition Fee Invoice',
            'invoice_number' => 'INV-TEST-001',
            'student_id' => $this->student->id,
            'category' => 'tuition',
            'amount' => 5000,
            'total_due' => 5000,
            'paid_amount' => 5000,
            'status' => 'paid',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $payment = FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $this->student->id,
            'amount' => 5000,
            'payment_method' => 'upi',
            'transaction_reference' => 'TXN-TEST-123',
            'receipt_number' => 'REC-TEST-001',
            'paid_at' => now(),
            'status' => 'completed',
        ]);

        // Own student can view
        $this->actingAs($this->student)
            ->get(route('school.fees.receipt', $payment->id))
            ->assertStatus(200);

        // Other student forbidden
        $this->actingAs($this->otherStudent)
            ->get(route('school.fees.receipt', $payment->id))
            ->assertStatus(403);

        // Parent of child can view
        $this->actingAs($this->parent)
            ->get(route('school.fees.receipt', $payment->id))
            ->assertStatus(200);

        // Admin can view
        $this->actingAs($this->admin)
            ->get(route('school.fees.receipt', $payment->id))
            ->assertStatus(200);
    }

    public function test_authorization_gates_and_policies(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('admin-only'));
        $this->assertFalse(Gate::forUser($this->teacher)->allows('admin-only'));
        $this->assertFalse(Gate::forUser($this->student)->allows('admin-only'));

        $this->assertTrue(Gate::forUser($this->admin)->allows('faculty-access'));
        $this->assertTrue(Gate::forUser($this->teacher)->allows('faculty-access'));
        $this->assertFalse(Gate::forUser($this->student)->allows('faculty-access'));

        $this->assertTrue(Gate::forUser($this->parent)->allows('parent-portal'));
        $this->assertFalse(Gate::forUser($this->student)->allows('parent-portal'));

        $this->assertTrue(Gate::forUser($this->student)->allows('student-portal'));
        $this->assertFalse(Gate::forUser($this->teacher)->allows('student-portal'));
    }

    public function test_parent_accessing_attendance_is_redirected_to_ward_records_not_teacher_taking_page(): void
    {
        $class = AcademicClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'grade_level' => 10,
            'class_teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);

        $this->student->current_class_id = $class->id;
        $this->student->save();

        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(),
            'title' => 'Morning Roll Call',
        ]);

        AttendanceRecord::create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        AttendanceRecord::create([
            'session_id' => $session->id,
            'student_id' => $this->otherStudent->id,
            'status' => 'absent',
        ]);

        // 1. Parent navigating to /attendance is redirected to ward attendance
        $response = $this->actingAs($this->parent)->get(route('attendance.index'));
        $response->assertRedirect(route('school.attendance.index'));

        // 2. Parent viewing school.attendance.index gets the parent view with only ward records
        $response = $this->actingAs($this->parent)->get(route('school.attendance.index'));
        $response->assertStatus(200);
        $response->assertViewIs('school.attendance.parent');
        $response->assertSee($this->student->name);
        $response->assertDontSee('Take Attendance');
        $response->assertDontSee('Record Attendance Session');

        // 3. Parent accessing attendance register with classId is safely redirected to their ward's presence record
        $response = $this->actingAs($this->parent)->get(route('school.attendance.register', $class->id));
        $response->assertRedirect(route('school.attendance.index'));
    }

    public function test_parent_and_student_forbidden_from_taking_roll_call(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));
        $class = AcademicClass::create([
            'name' => 'Grade 9',
            'section' => 'B',
            'grade_level' => 9,
            'class_teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        // Parent forbidden from roll-call interface
        $this->actingAs($this->parent)
            ->get(route('school.attendance.take', $class->id))
            ->assertStatus(403);

        $this->actingAs($this->parent)
            ->post(route('school.attendance.store', $class->id), [
                'session_date' => now()->toDateString(),
                'attendance' => [$this->student->id => ['status' => 'present']],
            ])
            ->assertStatus(403);

        // Student forbidden from roll-call interface
        $this->actingAs($this->student)
            ->get(route('school.attendance.take', $class->id))
            ->assertStatus(403);

        // Teacher can access roll-call interface
        $this->actingAs($this->teacher)
            ->get(route('school.attendance.take', $class->id))
            ->assertStatus(200);
        Carbon::setTestNow();
    }

    public function test_parent_and_student_access_to_individual_attendance_sessions(): void
    {
        $class = AcademicClass::create([
            'name' => 'Grade 11',
            'section' => 'C',
            'grade_level' => 11,
            'class_teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(),
            'title' => 'Biology Session',
        ]);

        // Parent visiting /attendance/{id} is redirected to ward records
        $this->actingAs($this->parent)
            ->get(route('attendance.show', $session->id))
            ->assertRedirect(route('school.attendance.index'));

        // Student visiting /attendance/{id} gets 403 Forbidden
        $this->actingAs($this->student)
            ->get(route('attendance.show', $session->id))
            ->assertStatus(403);

        // Teacher can view full session
        $this->actingAs($this->teacher)
            ->get(route('attendance.show', $session->id))
            ->assertStatus(200);
    }

    public function test_school_attendance_store_succeeds_without_title(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));
        $class = AcademicClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'grade_level' => 10,
            'class_teacher_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'roll_number' => 1,
        ]);

        $response = $this->actingAs($this->teacher)->post(route('school.attendance.store', $class->id), [
            'session_date' => now()->toDateString(),
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                    'remarks' => 'Present on time',
                ],
            ],
            // 'title' is intentionally omitted to verify no Undefined array key error occurs
        ]);

        $response->assertRedirect(route('school.attendance.index', ['date' => now()->toDateString()]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_sessions', [
            'class_id' => $class->id,
            'title' => "Daily Roll Call - {$class->display_name}",
        ]);
        Carbon::setTestNow();
    }

    public function test_notification_one_by_one_and_bulk_deletion(): void
    {
        $notif1 = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'homework',
            'title' => 'Homework assigned',
            'message' => 'Math homework due tomorrow',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $notif2 = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'fee',
            'title' => 'Fee Receipt',
            'message' => 'Payment received',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $notifUnread = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'attendance',
            'title' => 'Attendance notice',
            'message' => 'You were marked present',
            'is_read' => false,
        ]);

        $otherUserNotif = InAppNotification::create([
            'user_id' => $this->otherStudent->id,
            'type' => 'dpp',
            'title' => 'DPP Score',
            'message' => 'Your score is 90%',
            'is_read' => true,
        ]);

        // 1. Another user cannot delete someone else's notification
        $this->actingAs($this->student)
            ->delete(route('notifications.destroy', $otherUserNotif->id))
            ->assertStatus(404);

        // 2. User can delete their own single notification
        $response = $this->actingAs($this->student)
            ->delete(route('notifications.destroy', $notif1->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('in_app_notifications', ['id' => $notif1->id]);

        // 3. User can bulk delete all read notifications
        $response = $this->actingAs($this->student)
            ->post(route('notifications.delete-read'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('in_app_notifications', ['id' => $notif2->id]);
        // Unread notification is kept
        $this->assertDatabaseHas('in_app_notifications', ['id' => $notifUnread->id]);
        // Other user's notification is kept
        $this->assertDatabaseHas('in_app_notifications', ['id' => $otherUserNotif->id]);

        // 4. Bulk delete selected notifications
        $notifToSelect1 = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'bloom',
            'title' => 'Bloom quiz result',
            'message' => 'Passed Level 3',
            'is_read' => false,
        ]);
        $notifToSelect2 = InAppNotification::create([
            'user_id' => $this->student->id,
            'type' => 'bloom',
            'title' => 'Bloom quiz result 2',
            'message' => 'Passed Level 4',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('notifications.bulk-delete'), [
                'ids' => [$notifToSelect1->id, $notifToSelect2->id],
            ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('in_app_notifications', ['id' => $notifToSelect1->id]);
        $this->assertDatabaseMissing('in_app_notifications', ['id' => $notifToSelect2->id]);
    }
}
