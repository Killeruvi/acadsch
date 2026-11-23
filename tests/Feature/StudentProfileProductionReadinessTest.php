<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\ClassEnrollment;
use App\Models\AcademySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolExamTerm;
use App\Models\SchoolReportCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class StudentProfileProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AcademySetting::set('onboarding_completed', 'true');
    }

    private function makeUser(string $role, string $email, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' User',
            'email' => $email,
            'password' => Hash::make('TestPassword123!'),
            'role' => $role,
            'is_approved' => true,
        ], $extra));
    }

    private function makeClass(?int $teacherId = null): AcademicClass
    {
        return AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'section' => 'A',
            'academic_year' => '2026-2027',
            'class_teacher_id' => $teacherId,
            'room_number' => null,
        ]);
    }

    private function makeReportCard(AcademicClass $class, User $student, bool $published = true): SchoolReportCard
    {
        $term = SchoolExamTerm::create([
            'name' => 'Term 1',
            'academic_year' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'status' => 'completed',
        ]);

        return SchoolReportCard::create([
            'exam_term_id' => $term->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'total_max_marks' => 500,
            'total_marks_obtained' => 400,
            'percentage' => 80,
            'gpa' => 8,
            'overall_grade' => 'A',
            'result_status' => 'PASS',
            'is_published' => $published,
            'generated_date' => '2026-10-01',
        ]);
    }

    public function test_student_profile_uses_the_students_own_attendance_records_and_never_invents_a_perfect_rate(): void
    {
        $admin = $this->makeUser('admin', 'profile-admin@example.test');
        $teacher = $this->makeUser('teacher', 'profile-teacher@example.test');
        $class = $this->makeClass($teacher->id);
        $student = $this->makeUser('student', 'profile-student@example.test', [
            'current_class_id' => $class->id,
            'admission_number' => 'ADM-1001',
        ]);

        foreach (['present', 'absent', 'excused'] as $index => $status) {
            $session = AttendanceSession::create([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'session_date' => now()->subDays($index + 1)->toDateString(),
                'title' => 'Daily Attendance',
            ]);
            AttendanceRecord::create([
                'session_id' => $session->id,
                'student_id' => $student->id,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('school.students.show', $student->id));

        $response->assertOk();
        $response->assertSee('50.0%');
        $response->assertSee('1 attended');
        $response->assertSee('1 absent');
        $response->assertSee('1 excused');
        $response->assertDontSee('Room 101');
        $response->assertDontSee('2026-27');
        $response->assertDontSee('Blood: O+');
    }

    public function test_student_profile_displays_no_records_instead_of_a_false_hundred_percent(): void
    {
        $admin = $this->makeUser('admin', 'empty-profile-admin@example.test');
        $class = $this->makeClass();
        $student = $this->makeUser('student', 'empty-profile-student@example.test', [
            'current_class_id' => $class->id,
        ]);

        $response = $this->actingAs($admin)->get(route('school.students.show', $student->id));

        $response->assertOk();
        $response->assertSee('No records');
        $response->assertDontSee('100.0%');
    }

    public function test_teacher_cannot_open_student_profile_outside_assigned_classes(): void
    {
        $teacher = $this->makeUser('teacher', 'scope-teacher@example.test');
        $assignedClass = $this->makeClass($teacher->id);
        $otherClass = $this->makeClass();
        $student = $this->makeUser('student', 'scope-student@example.test', [
            'current_class_id' => $otherClass->id,
        ]);

        $response = $this->actingAs($teacher)->get(route('school.students.show', $student->id));

        $response->assertForbidden();
    }

    public function test_report_card_is_limited_to_its_student_parent_and_assigned_faculty(): void
    {
        $teacher = $this->makeUser('teacher', 'report-teacher@example.test');
        $class = $this->makeClass($teacher->id);
        $parent = $this->makeUser('parent', 'report-parent@example.test');
        $student = $this->makeUser('student', 'report-student@example.test', [
            'parent_id' => $parent->id,
            'current_class_id' => $class->id,
        ]);
        $otherStudent = $this->makeUser('student', 'report-other-student@example.test', [
            'current_class_id' => $class->id,
        ]);
        $reportCard = $this->makeReportCard($class, $student);

        $this->actingAs($student)->get(route('school.report-cards.show', $reportCard->id))->assertOk();
        $this->actingAs($parent)->get(route('school.report-cards.show', $reportCard->id))->assertOk();
        $this->actingAs($teacher)->get(route('school.report-cards.show', $reportCard->id))->assertOk();
        $this->actingAs($otherStudent)->get(route('school.report-cards.show', $reportCard->id))->assertForbidden();
    }

    public function test_students_and_parents_cannot_view_unpublished_report_cards(): void
    {
        $parent = $this->makeUser('parent', 'draft-parent@example.test');
        $student = $this->makeUser('student', 'draft-student@example.test', [
            'parent_id' => $parent->id,
        ]);
        $class = $this->makeClass();
        $reportCard = $this->makeReportCard($class, $student, false);

        $this->actingAs($student)->get(route('school.report-cards.show', $reportCard->id))->assertNotFound();
        $this->actingAs($parent)->get(route('school.report-cards.show', $reportCard->id))->assertNotFound();
    }

    public function test_admin_password_reset_sends_a_reset_link_without_exposing_or_overwriting_a_temporary_password(): void
    {
        Notification::fake();
        $admin = $this->makeUser('admin', 'reset-admin@example.test');
        $student = $this->makeUser('student', 'reset-student@example.test');
        $originalHash = $student->password;

        $response = $this->actingAs($admin)->post(route('school.users.reset-account', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('account_reset_result');
        $response->assertSessionMissing('temporary_password');
        $this->assertSame($originalHash, $student->fresh()->password);
        Notification::assertSentTo($student, ResetPassword::class);
    }
    public function test_legacy_student_profile_does_not_show_fabricated_ids_or_perfect_attendance_for_empty_history(): void
    {
        $admin = $this->makeUser('admin', 'legacy-profile-admin@example.test');
        $student = $this->makeUser('student', 'legacy-profile-student@example.test');

        $response = $this->actingAs($admin)->get(route('students.show', $student->id));

        $response->assertOk();
        $response->assertSee('Not assigned');
        $response->assertSee('No records');
        $response->assertDontSee('SCH-STD-');
        $response->assertDontSee('Default Batch');
        $response->assertDontSee('100.0%');
    }

    public function test_legacy_student_profile_and_edit_are_scoped_to_teacher_assignments(): void
    {
        $teacher = $this->makeUser('teacher', 'legacy-scope-teacher@example.test');
        $assignedClass = $this->makeClass($teacher->id);
        $otherClass = $this->makeClass();
        $student = $this->makeUser('student', 'legacy-scope-student@example.test', [
            'current_class_id' => $otherClass->id,
        ]);

        $this->actingAs($teacher)->get(route('students.show', $student->id))->assertForbidden();
        $this->actingAs($teacher)->get(route('students.edit', $student->id))->assertForbidden();
    }


    public function test_school_student_profile_edit_cannot_change_class_admission_identity_or_parent_link(): void
    {
        $admin = $this->makeUser('admin', 'edit-immutable-admin@example.test');
        $class = $this->makeClass();
        $otherClass = $this->makeClass();
        $parent = $this->makeUser('parent', 'edit-parent-one@example.test');
        $otherParent = $this->makeUser('parent', 'edit-parent-two@example.test');
        $student = $this->makeUser('student', 'edit-student@example.test', [
            'current_class_id' => $class->id,
            'parent_id' => $parent->id,
            'admission_number' => 'ADM-EDIT-1',
            'student_id_number' => 'ADM-EDIT-1',
        ]);
        ClassEnrollment::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'roll_number' => 7,
            'admission_number' => 'ADM-EDIT-1',
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('school.students.update', $student->id), [
            'name' => 'Updated Student Name',
            'email' => $student->email,
            'class_id' => $otherClass->id,
            'admission_number' => 'FORGED-ADM',
            'student_id_number' => 'FORGED-ID',
            'parent_id' => $otherParent->id,
            'guardian_name' => 'Guardian Name',
            'roll_number' => 7,
        ]);

        $response->assertRedirect(route('school.students.show', $student->id));
        $this->assertSame($class->id, $student->fresh()->current_class_id);
        $this->assertSame($parent->id, $student->fresh()->parent_id);
        $this->assertSame('ADM-EDIT-1', $student->fresh()->admission_number);
        $this->assertSame('ADM-EDIT-1', $student->fresh()->student_id_number);
        $this->assertSame(7, $student->fresh()->roll_number);
        $this->assertSame('Updated Student Name', $student->fresh()->name);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'roll_number' => 7,
        ]);
        $this->assertDatabaseMissing('class_enrollments', [
            'student_id' => $student->id,
            'class_id' => $otherClass->id,
        ]);
    }

    public function test_school_student_edit_rejects_duplicate_roll_number_instead_of_silently_reassigning(): void
    {
        $admin = $this->makeUser('admin', 'edit-roll-admin@example.test');
        $class = $this->makeClass();
        $student = $this->makeUser('student', 'edit-roll-one@example.test', [
            'current_class_id' => $class->id,
            'roll_number' => 1,
        ]);
        $otherStudent = $this->makeUser('student', 'edit-roll-two@example.test', [
            'current_class_id' => $class->id,
            'roll_number' => 2,
        ]);
        foreach ([[$student, 1], [$otherStudent, 2]] as [$record, $roll]) {
            ClassEnrollment::create([
                'class_id' => $class->id,
                'student_id' => $record->id,
                'roll_number' => $roll,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($admin)->from(route('school.students.edit', $student->id))
            ->put(route('school.students.update', $student->id), [
                'name' => $student->name,
                'email' => $student->email,
                'roll_number' => 2,
            ]);

        $response->assertRedirect(route('school.students.edit', $student->id));
        $response->assertSessionHasErrors('roll_number');
        $this->assertSame(1, $student->fresh()->roll_number);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'roll_number' => 1,
            'status' => 'active',
        ]);
    }

    public function test_school_student_edit_and_update_are_both_class_scoped_for_teachers(): void
    {
        $teacher = $this->makeUser('teacher', 'edit-scope-teacher@example.test');
        $assignedClass = $this->makeClass($teacher->id);
        $otherClass = $this->makeClass();
        $student = $this->makeUser('student', 'edit-scope-student@example.test', [
            'current_class_id' => $otherClass->id,
        ]);

        $this->actingAs($teacher)->get(route('school.students.edit', $student->id))->assertForbidden();
        $this->actingAs($teacher)->put(route('school.students.update', $student->id), [
            'name' => 'Attempted Update',
            'email' => $student->email,
        ])->assertForbidden();
        $this->assertSame('Student User', $student->fresh()->name);
    }

    public function test_teacher_cannot_change_student_login_email_through_school_profile_edit(): void
    {
        $teacher = $this->makeUser('teacher', 'edit-email-teacher@example.test');
        $class = $this->makeClass($teacher->id);
        $student = $this->makeUser('student', 'edit-email-student@example.test', [
            'current_class_id' => $class->id,
        ]);

        $response = $this->actingAs($teacher)->put(route('school.students.update', $student->id), [
            'name' => 'Updated By Teacher',
            'email' => 'changed-login@example.test',
        ]);

        $response->assertRedirect(route('school.students.show', $student->id));
        $this->assertSame('edit-email-student@example.test', $student->fresh()->email);
        $this->assertSame('Updated By Teacher', $student->fresh()->name);

        $admin = $this->makeUser('admin', 'edit-email-admin@example.test');
        $this->actingAs($admin)->put(route('school.students.update', $student->id), [
            'name' => 'Updated By Admin',
            'email' => 'admin-changed-login@example.test',
        ])->assertRedirect(route('school.students.show', $student->id));
        $this->assertSame('edit-email-student@example.test', $student->fresh()->email);
        $this->assertSame('Updated By Admin', $student->fresh()->name);
    }

    public function test_legacy_student_edit_cannot_change_class_batch_or_admission_identity(): void
    {
        $admin = $this->makeUser('admin', 'legacy-edit-admin@example.test');
        $class = $this->makeClass();
        $otherClass = $this->makeClass();
        $student = $this->makeUser('student', 'legacy-edit-student@example.test', [
            'current_class_id' => $class->id,
            'student_id_number' => 'LEGACY-ADM-1',
            'admission_number' => 'LEGACY-ADM-1',
        ]);

        $response = $this->actingAs($admin)->put(route('students.update', $student->id), [
            'name' => 'Legacy Updated Name',
            'email' => $student->email,
            'class_id' => $otherClass->id,
            'student_id_number' => 'FORGED-LEGACY-ID',
            'batch_id' => 999999,
        ]);

        $response->assertRedirect(route('students.show', $student->id));
        $this->assertSame($class->id, $student->fresh()->current_class_id);
        $this->assertSame('LEGACY-ADM-1', $student->fresh()->student_id_number);
        $this->assertSame('LEGACY-ADM-1', $student->fresh()->admission_number);
        $this->assertSame('Legacy Updated Name', $student->fresh()->name);
    }

    public function test_unapproved_or_withdrawn_student_cannot_reuse_an_authenticated_session(): void
    {
        $student = $this->makeUser('student', 'inactive-session-student@example.test', [
            'is_approved' => false,
        ]);

        $response = $this->actingAs($student)->get(route('profile.show'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

}
