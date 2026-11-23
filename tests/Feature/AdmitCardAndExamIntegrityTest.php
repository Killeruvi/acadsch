<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\ClassEnrollment;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmitCardAndExamIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher1;
    private User $teacher2;
    private User $student1;
    private User $student2;
    private User $parent1;
    private AcademicClass $class1;
    private AcademicClass $class2;
    private SchoolExamTerm $term;
    private AcademicSubject $subject1;
    private SchoolExamSchedule $schedule1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher1 = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->teacher2 = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->parent1 = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent1@test.com',
            'is_approved' => true,
        ]);

        $this->class1 = AcademicClass::create([
            'name' => 'Grade 10',
            'grade_level' => '10',
            'section' => 'A',
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'capacity' => 40,
            'class_teacher_id' => $this->teacher1->id,
            'status' => 'active',
        ]);

        $this->class2 = AcademicClass::create([
            'name' => 'Grade 9',
            'grade_level' => '9',
            'section' => 'B',
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'capacity' => 35,
            'class_teacher_id' => $this->teacher2->id,
            'status' => 'active',
        ]);

        $this->student1 = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->class1->id,
            'admission_number' => 'ADM-2026-0001',
            'roll_number' => 101,
            'guardian_email' => $this->parent1->email,
            'guardian_name' => $this->parent1->name,
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student1->id,
            'class_id' => $this->class1->id,
            'academic_year' => '2026-2027',
            'roll_number' => 101,
            'admission_number' => 'ADM-2026-0001',
            'status' => 'active',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->class2->id,
            'admission_number' => 'ADM-2026-0002',
            'roll_number' => 202,
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student2->id,
            'class_id' => $this->class2->id,
            'academic_year' => '2026-2027',
            'roll_number' => 202,
            'admission_number' => 'ADM-2026-0002',
            'status' => 'active',
        ]);

        $this->term = SchoolExamTerm::create([
            'name' => 'Mid-Term Examination 2026',
            'academic_year' => '2026-2027',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-25',
            'status' => 'published',
        ]);

        $this->subject1 = AcademicSubject::create([
            'class_id' => $this->class1->id,
            'name' => 'Mathematics',
            'code' => 'MATH10',
            'teacher_id' => $this->teacher1->id,
            'credit_hours' => 4,
            'status' => 'active',
        ]);

        $this->schedule1 = SchoolExamSchedule::create([
            'exam_term_id' => $this->term->id,
            'class_id' => $this->class1->id,
            'subject_id' => $this->subject1->id,
            'exam_date' => '2026-10-12',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'room_number' => 'Room 101',
            'max_marks' => 100,
            'pass_marks' => 40,
        ]);
    }

    public function test_admin_can_view_any_student_admit_card(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student1->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Official Examination Admit Card');
        $response->assertSee($this->student1->name);
        $response->assertSee($this->student1->admission_number);
        $response->assertSee('Mathematics');
        $response->assertSee('Room 101');
    }

    public function test_teacher_can_view_admit_card_for_student_in_assigned_class(): void
    {
        $response = $this->actingAs($this->teacher1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student1->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($this->student1->name);
    }

    public function test_teacher_forbidden_from_viewing_admit_card_outside_assigned_class(): void
    {
        // teacher1 is assigned to class1, student2 is in class2
        $response = $this->actingAs($this->teacher1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student2->id,
            ]));

        $response->assertStatus(403);
    }

    public function test_student_can_view_own_admit_card(): void
    {
        $response = $this->actingAs($this->student1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($this->student1->name);
    }

    public function test_student_forbidden_from_viewing_another_students_admit_card(): void
    {
        $response = $this->actingAs($this->student1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student2->id,
            ]));

        $response->assertStatus(403);
    }

    public function test_parent_can_view_child_admit_card(): void
    {
        $response = $this->actingAs($this->parent1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student1->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($this->student1->name);
    }

    public function test_parent_forbidden_from_viewing_unrelated_student_admit_card(): void
    {
        $response = $this->actingAs($this->parent1)
            ->get(route('school.exams.admit-card', [
                'termId' => $this->term->id,
                'studentId' => $this->student2->id,
            ]));

        $response->assertStatus(403);
    }

    public function test_qr_verification_endpoint_with_valid_token(): void
    {
        $sig = hash_hmac('sha256', "admit:{$this->term->id}:{$this->student1->id}:{$this->student1->admission_number}", config('app.key'));
        $payload = [
            'term_id' => $this->term->id,
            'student_id' => $this->student1->id,
            'sig' => $sig,
        ];
        $token = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $response = $this->getJson(route('school.exams.admit-card.verify', ['token' => $token]));

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'status' => 'VERIFIED_GENUINE',
                'student_name' => $this->student1->name,
                'admission_number' => $this->student1->admission_number,
                'term_name' => $this->term->name,
            ]);
    }

    public function test_qr_verification_endpoint_rejects_tampered_signature(): void
    {
        $payload = [
            'term_id' => $this->term->id,
            'student_id' => $this->student1->id,
            'sig' => 'fake-counterfeit-signature',
        ];
        $token = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $response = $this->getJson(route('school.exams.admit-card.verify', ['token' => $token]));

        $response->assertStatus(403)
            ->assertJson([
                'valid' => false,
                'message' => 'Admit card signature mismatch / counterfeit detected.',
            ]);
    }
}
