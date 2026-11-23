<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\Assignment;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAndStaffDirectoryProtectionAndDesktopLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $academicClass;

    protected function setUp(): void
    {
        parent::setUp();
        AcademySetting::set('onboarding_completed', 'true');

        $this->academicClass = AcademicClass::create([
            'name' => 'Grade 12 Science',
            'grade_level' => 12,
            'section' => 'A',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Officer',
            'email' => 'admin.officer@primearc.edu',
            'password' => Hash::make('SuperSecretAdmin123!'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Dr. Robert Smith',
            'email' => 'robert.smith@primearc.edu',
            'password' => Hash::make('TeacherPass123!'),
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->student = User::create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav.sharma@primearc.edu',
            'password' => Hash::make('StudentPass123!'),
            'role' => 'student',
            'is_approved' => true,
            'student_id_number' => 'STU-2026-001',
        ]);

        $this->student->classEnrollments()->create([
            'class_id' => $this->academicClass->id,
            'roll_number' => '101',
            'status' => 'active',
        ]);
    }

    public function test_desktop_layout_renders_profile_dropdown_and_institutional_mottos(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Aim, Ascend, Achieve');
        $response->assertSee('Your potential has no fixed limits');
        $response->assertSee('user-profile-menu-button');
        $response->assertSee(route('staff.index'));
        $response->assertSee(route('students.index'));
        $response->assertDontSee('flame-active');
    }

    public function test_student_directory_view_analysis_and_edit(): void
    {
        // 1. View deep student analysis
        $showResponse = $this->actingAs($this->admin)->get(route('students.show', $this->student->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Aarav Sharma');
        $showResponse->assertSee('STU-2026-001');
        $showResponse->assertSee('Attendance Record');
        $showResponse->assertSee('Withdraw Student');

        // 2. View student edit form
        $editResponse = $this->actingAs($this->admin)->get(route('students.edit', $this->student->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Scholar Information');

        // 3. Update student profile
        $updateResponse = $this->actingAs($this->admin)->put(route('students.update', $this->student->id), [
            'name' => 'Aarav Sharma Updated',
            'email' => 'aarav.sharma@primearc.edu',
            'phone' => '+91 9876543210',
            'student_id_number' => 'STU-2026-001-REV',
            'class_id' => $this->academicClass->id,
            'roll_number' => '102',
            'is_approved' => 1,
        ]);

        $updateResponse->assertRedirect(route('students.show', $this->student->id));
        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'name' => 'Aarav Sharma Updated',
            'student_id_number' => 'STU-2026-001',
        ]);
    }

    public function test_student_deletion_fails_without_proper_tri_factor_protection(): void
    {
        // Missing delete confirmation keyword and wrong password
        $response = $this->actingAs($this->admin)->delete(route('students.destroy', $this->student->id), [
            'confirmation_text' => 'NOPE',
            'admin_password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors(['confirmation_text']);
        $this->assertDatabaseHas('users', ['id' => $this->student->id]);
    }

    public function test_student_withdrawal_succeeds_and_preserves_the_student_record(): void
    {
        $apiToken = $this->student->createToken('withdrawal-test-token');
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $apiToken->accessToken->id]);

        $currentMonth = now()->format('Y-m');
        $futureMonth = now()->addMonths(2)->format('Y-m');

        // Past/current invoice with payment
        $retainedInvoice = FeeInvoice::create([
            'invoice_number' => 'INV-RET-001',
            'student_id' => $this->student->id,
            'title' => 'Tuition Fee Current',
            'category' => 'tuition',
            'amount' => 2000,
            'discount' => 0,
            'tax' => 0,
            'total_due' => 2000,
            'paid_amount' => 2000,
            'status' => 'paid',
            'due_date' => now()->toDateString(),
            'billing_type' => 'monthly_auto',
            'billing_month' => $currentMonth,
        ]);

        // Future uncollected invoice
        $futureInvoice = FeeInvoice::create([
            'invoice_number' => 'INV-FUT-002',
            'student_id' => $this->student->id,
            'title' => 'Tuition Fee Future',
            'category' => 'tuition',
            'amount' => 2000,
            'discount' => 0,
            'tax' => 0,
            'total_due' => 2000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addMonths(2)->toDateString(),
            'billing_type' => 'monthly_auto',
            'billing_month' => $futureMonth,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('students.destroy', $this->student->id), [
            'confirmation_text' => 'WITHDRAW',
            'admin_password' => 'SuperSecretAdmin123!',
            'withdrawal_reason' => 'Parent submitted a signed withdrawal request.',
            'effective_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('school.students.index'));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $apiToken->accessToken->id]);
        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'is_approved' => 0,
            'current_class_id' => null,
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->academicClass->id,
            'status' => 'withdrawn',
        ]);
        $this->assertDatabaseHas('student_withdrawal_records', [
            'student_id' => $this->student->id,
            'withdrawn_by' => $this->admin->id,
            'reason' => 'Parent submitted a signed withdrawal request.',
        ]);
        // Verified: Retained invoice remains intact, while future uncollected invoice was cleanly purged
        $this->assertDatabaseHas('fee_invoices', ['id' => $retainedInvoice->id]);
        $this->assertDatabaseMissing('fee_invoices', ['id' => $futureInvoice->id]);
        $this->assertNotEmpty($this->student->fresh()->remember_token);
    }

    public function test_teacher_cannot_withdraw_a_student_even_through_the_legacy_endpoint(): void
    {
        $response = $this->actingAs($this->teacher)->delete(route('students.destroy', $this->student->id), [
            'confirmation_text' => 'WITHDRAW',
            'admin_password' => 'TeacherPass123!',
            'withdrawal_reason' => 'Parent submitted a signed withdrawal request.',
            'effective_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $this->student->id, 'is_approved' => 1]);
        $this->assertDatabaseMissing('student_withdrawal_records', ['student_id' => $this->student->id]);
    }

    public function test_withdrawal_requires_a_documented_reason_and_effective_date(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('students.destroy', $this->student->id), [
            'confirmation_text' => 'WITHDRAW',
            'admin_password' => 'SuperSecretAdmin123!',
            'withdrawal_reason' => 'short',
            'effective_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['withdrawal_reason', 'effective_date']);
        $this->assertDatabaseHas('users', ['id' => $this->student->id, 'is_approved' => 1]);
        $this->assertDatabaseMissing('student_withdrawal_records', ['student_id' => $this->student->id]);
    }

    public function test_duplicate_withdrawal_is_rejected_without_creating_another_audit_record(): void
    {
        $payload = [
            'confirmation_text' => 'WITHDRAW',
            'admin_password' => 'SuperSecretAdmin123!',
            'withdrawal_reason' => 'Parent submitted a signed withdrawal request.',
            'effective_date' => now()->toDateString(),
        ];

        $this->actingAs($this->admin)->delete(route('students.destroy', $this->student->id), $payload)
            ->assertRedirect(route('school.students.index'));

        $this->actingAs($this->admin)->delete(route('students.destroy', $this->student->id), $payload)
            ->assertSessionHasErrors('student');

        $this->assertDatabaseCount('student_withdrawal_records', 1);
    }

    public function test_staff_directory_index_profile_and_edit(): void
    {
        // 1. Staff directory index
        $indexResponse = $this->actingAs($this->admin)->get(route('staff.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Faculty &amp; Staff Directory', false);
        $indexResponse->assertSee('Dr. Robert Smith');

        // 2. Staff profile show
        $showResponse = $this->actingAs($this->admin)->get(route('staff.show', $this->teacher->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Dr. Robert Smith');
        $showResponse->assertSee('Caution: Permanent Staff Account Deletion');

        // 3. Staff edit form
        $editResponse = $this->actingAs($this->admin)->get(route('staff.edit', $this->teacher->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Faculty / Staff Member');

        // 4. Update staff
        $updateResponse = $this->actingAs($this->admin)->put(route('staff.update', $this->teacher->id), [
            'name' => 'Prof. Robert Smith Ph.D.',
            'email' => 'robert.smith@primearc.edu',
            'phone' => '+91 9123456789',
            'role' => 'teacher',
            'is_approved' => 1,
        ]);

        $updateResponse->assertRedirect(route('staff.show', $this->teacher->id));
        $this->assertDatabaseHas('users', [
            'id' => $this->teacher->id,
            'name' => 'Prof. Robert Smith Ph.D.',
        ]);
    }

    public function test_admin_cannot_delete_themselves_from_staff_directory(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('staff.destroy', $this->admin->id), [
            'confirmation_text' => 'DELETE',
            'admin_password' => 'SuperSecretAdmin123!',
            'captcha_answer' => 8,
            'captcha_expected' => 8,
        ]);

        $response->assertSessionHasErrors(['admin_password']);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_staff_deletion_succeeds_with_tri_factor_protection(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('staff.destroy', $this->teacher->id), [
            'confirmation_text' => 'DELETE',
            'admin_password' => 'SuperSecretAdmin123!',
            'captcha_answer' => 12,
            'captcha_expected' => 12,
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseMissing('users', ['id' => $this->teacher->id]);
    }

    public function test_assignment_edit_update_and_destroy(): void
    {
        $course = Course::create([
            'title' => 'Theoretical Mechanics',
            'code' => 'MECH-301',
            'teacher_id' => $this->teacher->id,
            'is_published' => true,
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Lagrangian Dynamics Problem Set 1',
            'description' => 'Derive Euler-Lagrange equations for double pendulum.',
            'due_date' => now()->addDays(7),
            'max_points' => 100,
            'assigned_to' => 'all',
        ]);

        // 1. Edit view
        $editResponse = $this->actingAs($this->teacher)->get(route('assignments.edit', $assignment->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Assignment');

        // 2. Update assignment
        $updateResponse = $this->actingAs($this->teacher)->put(route('assignments.update', $assignment->id), [
            'course_id' => $course->id,
            'title' => 'Lagrangian Dynamics Revised Set',
            'description' => 'Updated instructions with generalized coordinates.',
            'due_date' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'max_points' => 120,
            'assigned_to' => 'all',
        ]);

        $updateResponse->assertRedirect(route('assignments.show', $assignment->id));
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Lagrangian Dynamics Revised Set',
            'max_points' => 120,
        ]);

        // 3. Destroy assignment
        $destroyResponse = $this->actingAs($this->teacher)->delete(route('assignments.destroy', $assignment->id));
        $destroyResponse->assertRedirect(route('assignments.index'));
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_attendance_session_destroy(): void
    {
        $session = AttendanceSession::create([
            'academic_class_id' => $this->academicClass->id,
            'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(),
            'title' => 'Morning Physics Lecture',
            'notes' => 'Full attendance expected',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('attendance.destroy', $session->id));
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseMissing('attendance_sessions', ['id' => $session->id]);
    }
}
