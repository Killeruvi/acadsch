<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProgressionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicClass $classA;

    private AcademicClass $classB;

    private AcademicClass $nextYearClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create([
            'role' => 'student', 'is_approved' => true, 'current_class_id' => null,
            'admission_number' => 'ADM-HISTORY-001',
        ]);

        $this->classA = AcademicClass::create([
            'name' => 'Class 9', 'grade_level' => 9, 'section' => 'A', 'academic_year' => '2026-2027', 'capacity' => 40,
        ]);
        $this->classB = AcademicClass::create([
            'name' => 'Class 9', 'grade_level' => 9, 'section' => 'B', 'academic_year' => '2026-2027', 'capacity' => 40,
        ]);
        $this->nextYearClass = AcademicClass::create([
            'name' => 'Class 10', 'grade_level' => 10, 'section' => 'A', 'academic_year' => '2027-2028', 'capacity' => 40,
        ]);

        ClassEnrollment::create([
            'class_id' => $this->classA->id, 'student_id' => $this->student->id,
            'roll_number' => 1, 'admission_number' => $this->student->admission_number,
            'academic_year' => '2026-2027', 'status' => 'active',
        ]);
        $this->student->forceFill(['current_class_id' => $this->classA->id, 'roll_number' => 1])->save();

        $session = AttendanceSession::create([
            'class_id' => $this->classA->id, 'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(), 'title' => 'Test attendance',
        ]);
        AttendanceRecord::create([
            'session_id' => $session->id, 'student_id' => $this->student->id, 'status' => 'present',
        ]);
    }

    public function test_promotion_creates_new_session_enrollment_and_preserves_old_enrollment(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.promotion.promote'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->nextYearClass->id,
            'student_ids' => [$this->student->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $this->classA->id, 'student_id' => $this->student->id,
            'academic_year' => '2026-2027', 'status' => 'promoted',
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $this->nextYearClass->id, 'student_id' => $this->student->id,
            'academic_year' => '2027-2028', 'status' => 'active', 'roll_number' => 1,
        ]);
        $this->assertSame($this->nextYearClass->id, $this->student->fresh()->current_class_id);
    }

    public function test_same_session_transfer_preserves_previous_class_record(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.promotion.transfer'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->classB->id,
            'student_ids' => [$this->student->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $this->classA->id, 'student_id' => $this->student->id, 'status' => 'transferred',
        ]);
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $this->classB->id, 'student_id' => $this->student->id,
            'academic_year' => '2026-2027', 'status' => 'active',
        ]);
        $this->assertSame($this->classB->id, $this->student->fresh()->current_class_id);

        // A student may transfer back to a class in the same session; old rows remain historical.
        $this->actingAs($this->admin)->post(route('school.promotion.transfer'), [
            'source_class_id' => $this->classB->id,
            'target_class_id' => $this->classA->id,
            'student_ids' => [$this->student->id],
        ])->assertRedirect();
        $this->assertDatabaseCount('class_enrollments', 3);
        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id);
    }

    public function test_transfer_cannot_cross_academic_sessions(): void
    {
        $this->actingAs($this->admin)->post(route('school.promotion.transfer'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->nextYearClass->id,
            'student_ids' => [$this->student->id],
        ])->assertSessionHasErrors('target_class_id');

        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id);
    }

    public function test_student_with_outstanding_fee_balance_cannot_be_promoted(): void
    {
        FeeInvoice::create([
            'invoice_number' => 'INV-PROMO-DUE-001', 'student_id' => $this->student->id,
            'title' => 'Tuition fee', 'amount' => 1000, 'total_due' => 1000,
            'paid_amount' => 250, 'due_date' => now()->addDays(7)->toDateString(), 'status' => 'partial',
        ]);

        $this->actingAs($this->admin)->post(route('school.promotion.promote'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->nextYearClass->id,
            'student_ids' => [$this->student->id],
        ])->assertSessionHasErrors('student_ids');

        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id);
        $this->assertDatabaseCount('class_enrollments', 1);
    }

    public function test_selected_student_must_belong_to_the_submitted_source_class(): void
    {
        $unrelatedStudent = User::factory()->create(['role' => 'student', 'is_approved' => true]);

        $this->actingAs($this->admin)->post(route('school.promotion.promote'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->nextYearClass->id,
            'student_ids' => [$unrelatedStudent->id],
        ])->assertSessionHasErrors('student_ids');

        $this->assertNull($unrelatedStudent->fresh()->current_class_id);
        $this->assertDatabaseCount('class_enrollments', 1);
    }

    public function test_students_without_attendance_are_not_treated_as_one_hundred_percent(): void
    {
        AttendanceRecord::query()->delete();
        AttendanceSession::query()->delete();

        $this->actingAs($this->admin)->get(route('school.promotion.index', ['source_class_id' => $this->classA->id]))
            ->assertOk()->assertSee('No data');

        $this->actingAs($this->admin)->post(route('school.promotion.promote'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->nextYearClass->id,
            'student_ids' => [$this->student->id],
        ])->assertSessionHasErrors('student_ids');

        $this->assertSame($this->classA->id, $this->student->fresh()->current_class_id);
    }

    public function test_re_admission_allows_a_later_second_withdrawal_episode(): void
    {
        $withdrawalPayload = [
            'confirmation_text' => 'WITHDRAW', 'admin_password' => 'password',
            'withdrawal_reason' => 'Family relocation requested withdrawal.',
            'effective_date' => now()->toDateString(),
        ];

        $this->actingAs($this->admin)->delete(route('school.students.destroy', $this->student->id), $withdrawalPayload)->assertRedirect();
        $this->assertDatabaseHas('student_withdrawal_records', ['student_id' => $this->student->id, 're_admitted_at' => null]);

        $this->actingAs($this->admin)->post(route('school.promotion.readmit'), [
            'student_id' => $this->student->id, 'target_class_id' => $this->nextYearClass->id,
        ])->assertRedirect();

        $this->assertTrue((bool) $this->student->fresh()->is_approved);
        $this->assertSame($this->nextYearClass->id, $this->student->fresh()->current_class_id);
        $this->assertDatabaseHas('student_withdrawal_records', [
            'student_id' => $this->student->id, 're_admitted_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete(route('school.students.destroy', $this->student->id), $withdrawalPayload)->assertRedirect();
        $this->assertDatabaseCount('student_withdrawal_records', 2);
    }
}
