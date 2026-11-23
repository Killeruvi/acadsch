<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicProject;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\BatchChangeRequest;
use App\Models\ClassEnrollment;
use App\Models\Course;
use App\Models\DailyDpp;
use App\Models\DppSubmission;
use App\Models\FacultyAccessCode;
use App\Models\ProjectSubmission;
use App\Models\Schedule;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolFeeStructure;
use App\Models\SchoolSection;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutogenerateAdmissionAndRollNumberPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicClass $classA;

    private AcademicClass $classB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.academy',
        ]);

        $this->classA = AcademicClass::create([
            'name' => 'Class 10-A',
            'grade_level' => 10,
            'stream' => 'General',
            'section' => 'A',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $this->classB = AcademicClass::create([
            'name' => 'Class 10-B',
            'grade_level' => 10,
            'stream' => 'General',
            'section' => 'B',
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        AcademySetting::set('academic_session', '2026-2027');
        foreach ([$this->classA, $this->classB] as $schoolClass) {
            SchoolFeeStructure::create([
                'class_id' => $schoolClass->id,
                'fee_title' => 'Configured tuition fee',
                'fee_type' => 'tuition',
                'amount' => 2500,
                'due_frequency' => 'monthly',
                'academic_year' => '2026-2027',
            ]);
        }
    }

    public function test_user_generate_next_admission_number_is_sequential_and_conflict_free(): void
    {
        $year = date('Y');
        $adm1 = User::generateNextAdmissionNumber();
        $this->assertStringStartsWith("ADM-{$year}-", $adm1);

        // Create student with this number
        User::factory()->create([
            'role' => 'student',
            'admission_number' => $adm1,
            'student_id_number' => $adm1,
        ]);

        // Next number must be different and incremented
        $adm2 = User::generateNextAdmissionNumber();
        $this->assertNotEquals($adm1, $adm2);
        $this->assertStringStartsWith("ADM-{$year}-", $adm2);

        // Pre-create an artificial future number e.g. ADM-YYYY-0050
        $futureAdm = sprintf('ADM-%s-0050', $year);
        User::factory()->create([
            'role' => 'student',
            'admission_number' => $futureAdm,
            'student_id_number' => $futureAdm,
        ]);

        // Next generated number should jump past 0050 to 0051
        $adm3 = User::generateNextAdmissionNumber();
        $this->assertEquals(sprintf('ADM-%s-0051', $year), $adm3);
    }

    public function test_class_enrollment_generate_next_roll_number_is_per_class_and_fills_gaps(): void
    {
        $roll1 = ClassEnrollment::generateNextRollNumber($this->classA->id);
        $this->assertEquals(1, $roll1);

        $student1 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->classA->id]);
        ClassEnrollment::create([
            'class_id' => $this->classA->id,
            'student_id' => $student1->id,
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $roll2 = ClassEnrollment::generateNextRollNumber($this->classA->id);
        $this->assertEquals(2, $roll2);

        // Class B has independent roll numbers starting at 1
        $rollB = ClassEnrollment::generateNextRollNumber($this->classB->id);
        $this->assertEquals(1, $rollB);

        // Create student with roll 3 in Class A (leaving roll 2 available)
        $student3 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->classA->id]);
        ClassEnrollment::create([
            'class_id' => $this->classA->id,
            'student_id' => $student3->id,
            'roll_number' => 3,
            'status' => 'active',
        ]);

        // Gap roll 2 should be allocated
        $gapRoll = ClassEnrollment::generateNextRollNumber($this->classA->id);
        $this->assertEquals(2, $gapRoll);
    }

    public function test_school_student_store_autogenerates_admission_and_roll_when_missing(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->admin)->post(route('school.students.store'), [
            'name' => 'Auto Candidate',
            'email' => 'auto.candidate@test.com',
            'class_id' => $this->classA->id,
            'guardian_name' => 'Auto Candidate Guardian',
            'guardian_phone' => '9876543210',
            'guardian_email' => 'auto.guardian@test.com',
            // admission_number and roll_number intentionally omitted
        ]);

        $response->assertRedirect();
        $student = User::where('email', 'auto.candidate@test.com')->first();
        $this->assertNotNull($student);
        $this->assertNotNull($student->admission_number);
        $this->assertStringStartsWith('ADM-', $student->admission_number);
        $this->assertEquals(1, $student->roll_number);

        $enrollment = ClassEnrollment::where('student_id', $student->id)->where('class_id', $this->classA->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertEquals(1, $enrollment->roll_number);
        $this->assertEquals($student->admission_number, $enrollment->admission_number);
        $this->assertDatabaseHas('fee_invoices', [
            'student_id' => $student->id,
            'title' => 'Configured tuition fee',
            'amount' => 2500.00,
            'total_due' => 2500.00,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'auto.guardian@test.com',
            'role' => 'parent',
        ]);
        $parent = User::where('email', 'auto.guardian@test.com')->firstOrFail();
        $this->assertFalse(Hash::check('password123', $student->password));
        $this->assertFalse(Hash::check('password123', $parent->password));
        Notification::assertSentTo($student, ResetPassword::class);
        Notification::assertSentTo($parent, ResetPassword::class);
    }

    public function test_school_student_store_rejects_admission_without_configured_session_fee(): void
    {
        SchoolFeeStructure::where('class_id', $this->classA->id)->delete();

        $response = $this->from(route('school.students.create'))
            ->actingAs($this->admin)
            ->post(route('school.students.store'), [
                'name' => 'Unconfigured Candidate',
                'email' => 'unconfigured.candidate@test.com',
                'class_id' => $this->classA->id,
                'guardian_name' => 'Candidate Guardian',
                'guardian_phone' => '9876543212',
                'guardian_email' => 'candidate.guardian@test.com',
            ]);

        $response->assertRedirect(route('school.students.create'));
        $response->assertSessionHasErrors('class_id');
        $this->assertDatabaseMissing('users', ['email' => 'unconfigured.candidate@test.com']);
    }

    public function test_empty_phone_is_stored_as_null_not_a_fabricated_number(): void
    {
        $student = User::factory()->create(['role' => 'student', 'phone' => null]);
        $this->assertNull($student->fresh()->phone);
    }

    public function test_school_student_store_prevents_conflicts_when_taken_numbers_provided(): void
    {
        // Student 1 with Roll 1
        $student1 = User::factory()->create([
            'role' => 'student',
            'admission_number' => 'ADM-2026-0001',
            'student_id_number' => 'ADM-2026-0001',
            'current_class_id' => $this->classA->id,
            'roll_number' => 1,
        ]);
        ClassEnrollment::create([
            'class_id' => $this->classA->id,
            'student_id' => $student1->id,
            'roll_number' => 1,
            'admission_number' => 'ADM-2026-0001',
            'status' => 'active',
        ]);

        // Try submitting Student 2 with the SAME admission number and SAME roll number 1
        $response = $this->actingAs($this->admin)->post(route('school.students.store'), [
            'name' => 'Colliding Candidate',
            'email' => 'colliding@test.com',
            'class_id' => $this->classA->id,
            'guardian_name' => 'Collision Guardian',
            'guardian_phone' => '9876543211',
            'guardian_email' => 'collision.guardian@test.com',
            'admission_number' => 'ADM-2026-0001', // conflicting
            'roll_number' => 1,                   // conflicting
        ]);

        $response->assertRedirect();
        $student2 = User::where('email', 'colliding@test.com')->first();
        $this->assertNotNull($student2);
        // Admission number was regenerated to be conflict-free
        $this->assertNotEquals('ADM-2026-0001', $student2->admission_number);
        // Roll number was dynamically assigned to next available
        $this->assertEquals(2, $student2->roll_number);

        $enrollment2 = ClassEnrollment::where('student_id', $student2->id)->first();
        $this->assertEquals(2, $enrollment2->roll_number);
    }

    public function test_school_student_update_resolves_roll_number_conflict_on_class_transfer(): void
    {
        // In Class B, student already has Roll 5
        $existingStudentB = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->classB->id,
            'roll_number' => 5,
        ]);
        ClassEnrollment::create([
            'class_id' => $this->classB->id,
            'student_id' => $existingStudentB->id,
            'roll_number' => 5,
            'status' => 'active',
        ]);

        // Student A transferring from Class A to Class B with conflicting Roll 5
        $studentA = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->classA->id,
            'roll_number' => 1,
            'admission_number' => 'ADM-2026-9999',
            'is_approved' => true,
        ]);
        ClassEnrollment::create([
            'class_id' => $this->classA->id,
            'student_id' => $studentA->id,
            'roll_number' => 1,
            'status' => 'active',
            'academic_year' => $this->classA->academic_year,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.promotion.transfer'), [
            'source_class_id' => $this->classA->id,
            'target_class_id' => $this->classB->id,
            'student_ids' => [$studentA->id],
        ]);

        $response->assertRedirect();
        $studentA->refresh();
        $this->assertEquals($this->classB->id, $studentA->current_class_id);
        // Must not conflict with existing roll 5 in Class B
        $this->assertNotEquals(5, $studentA->roll_number);
        $this->assertEquals(1, $studentA->roll_number); // lowest available in Class B
    }

    public function test_universal_pagination_across_views(): void
    {
        // 1. School Classes Detail Roster Pagination
        // Create 20 students enrolled in Class A
        for ($i = 1; $i <= 20; $i++) {
            $st = User::factory()->create([
                'role' => 'student',
                'name' => "Student Roster {$i}",
                'current_class_id' => $this->classA->id,
                'roll_number' => $i,
            ]);
            ClassEnrollment::create([
                'class_id' => $this->classA->id,
                'student_id' => $st->id,
                'roll_number' => $i,
                'status' => 'active',
            ]);
        }

        // Create 15 subjects in Class A
        for ($s = 1; $s <= 15; $s++) {
            AcademicSubject::create([
                'class_id' => $this->classA->id,
                'name' => "Subject {$s}",
                'code' => "SUB-{$s}",
                'weekly_periods' => 4,
            ]);
        }

        $resClassShow = $this->actingAs($this->admin)->get(route('school.classes.show', $this->classA->id));
        $resClassShow->assertOk();
        $resClassShow->assertSee('students_page=2');
        $resClassShow->assertSee('subjects_page=2');

        // 2. School Classes Index Reusable Sections Pagination
        for ($sec = 1; $sec <= 15; $sec++) {
            SchoolSection::create([
                'name' => "Sec{$sec}",
                'stream' => 'General',
                'capacity' => 35,
                'is_active' => true,
            ]);
        }
        $resClassesIndex = $this->actingAs($this->admin)->get(route('school.classes.index', ['tab' => 'sections']));
        $resClassesIndex->assertOk();
        $resClassesIndex->assertSee('sections_page=2');

        // 3. School Exam Schedules Pagination
        $term = SchoolExamTerm::create([
            'name' => 'Midterm 2026',
            'academic_year' => '2026-2027',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'upcoming',
        ]);
        $subjects = AcademicSubject::where('class_id', $this->classA->id)->get();
        for ($sch = 1; $sch <= 15; $sch++) {
            SchoolExamSchedule::create([
                'exam_term_id' => $term->id,
                'class_id' => $this->classA->id,
                'subject_id' => $subjects[$sch - 1]->id,
                'exam_date' => now()->addDays($sch)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'max_marks' => 100,
                'pass_marks' => 35,
            ]);
        }
        $resExams = $this->actingAs($this->admin)->get(route('school.exams.index'));
        $resExams->assertOk();
        $resExams->assertSee('schedules_page=2');

        // 4. Certificates Pagination
        $studentUser = User::where('role', 'student')->first();
        for ($c = 1; $c <= 12; $c++) {
            UserCertificate::create([
                'user_id' => $studentUser->id,
                'issued_by' => $this->admin->id,
                'title' => "Honor Certificate {$c}",
                'citation' => "Exemplary Academic Excellence {$c}",
                'certificate_number' => "CERT-2026-{$c}",
                'category' => 'academics',
                'verification_hash' => "HASH{$c}".time(),
                'issued_date' => now(),
            ]);
        }
        $resCert = $this->actingAs($this->admin)->get(route('certificates.index', ['user_id' => $studentUser->id]));
        $resCert->assertOk();
        $resCert->assertSee('certificates_page=2');

        // 5. Batches Select Pending Requests Pagination
        $batchA = Batch::create([
            'class_id' => $this->classA->id,
            'name' => 'Batch Alpha',
            'code' => 'BATCH-A',
            'shift' => 'morning',
            'timing_slot' => '08:00 AM - 01:00 PM',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        $batchB = Batch::create([
            'class_id' => $this->classA->id,
            'name' => 'Batch Beta',
            'code' => 'BATCH-B',
            'shift' => 'evening',
            'timing_slot' => '02:00 PM - 07:00 PM',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
        for ($r = 1; $r <= 12; $r++) {
            $reqStudent = User::factory()->create(['role' => 'student']);
            BatchChangeRequest::create([
                'student_id' => $reqStudent->id,
                'current_batch_id' => $batchA->id,
                'requested_batch_id' => $batchB->id,
                'reason' => "Request reason {$r}",
                'status' => 'pending',
            ]);
        }
        $resBatchSelect = $this->actingAs($this->admin)->get(route('batches.select'));
        $resBatchSelect->assertOk();
        $resBatchSelect->assertSee('requests_page=2');

        // 6. Faculty Codes Pagination
        for ($fc = 1; $fc <= 12; $fc++) {
            FacultyAccessCode::create([
                'code' => "FACCODE-{$fc}-".time(),
                'generated_by' => $this->admin->id,
                'is_used' => false,
            ]);
        }
        $resFaculty = $this->actingAs($this->admin)->get(route('admin.faculty-codes.index'));
        $resFaculty->assertOk();
        $resFaculty->assertSee('unused_page=2');

        // 7. Academic Holidays Pagination
        for ($h = 1; $h <= 20; $h++) {
            AcademicHoliday::create([
                'name' => "Holiday Recess {$h}",
                'holiday_date' => now()->startOfYear()->addDays($h * 10)->toDateString(),
                'type' => 'festival',
                'is_instructional_off' => true,
            ]);
        }
        $resHolidays = $this->actingAs($this->admin)->get(route('school.holidays.index'));
        $resHolidays->assertOk();
        $resHolidays->assertSee('page=2');

        // 8. School Sections Index Pagination
        for ($sec = 1; $sec <= 15; $sec++) {
            SchoolSection::create([
                'name' => "HubSec{$sec}",
                'stream' => 'General',
                'capacity' => 35,
                'is_active' => true,
            ]);
        }
        $resSectionsIndex = $this->actingAs($this->admin)->get(route('school.sections.index'));
        $resSectionsIndex->assertOk();
        $resSectionsIndex->assertSee('page=2');

        // 9. API Schedule Endpoint Pagination
        $course = Course::firstOrCreate(
            ['id' => 1],
            ['title' => 'Sample Course', 'code' => 'CRS-PAG-01', 'category' => 'General', 'teacher_id' => $this->admin->id, 'capacity' => 50]
        );
        for ($sch = 1; $sch <= 25; $sch++) {
            Schedule::create([
                'course_id' => $course->id,
                'title' => "Schedule Slot {$sch}",
                'day_of_week' => 'Monday',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ]);
        }
        Sanctum::actingAs($this->admin);
        $resApiSched = $this->json('GET', '/api/v1/schedules', ['page' => 2, 'per_page' => 10]);
        $resApiSched->assertOk();
        $resApiSched->assertJsonStructure([
            'current_page',
            'data',
            'total',
        ]);

        // 10. DPP Submissions Pagination
        $dpp = DailyDpp::create([
            'title' => 'Pagination DPP Test',
            'type' => 'dpp',
            'dpp_number' => 999,
            'class_id' => $this->classA->id,
            'subject_id' => 1,
            'teacher_id' => $this->admin->id,
            'assigned_date' => now()->toDateString(),
            'total_marks' => 20,
            'duration_minutes' => 30,
        ]);
        for ($s = 1; $s <= 20; $s++) {
            $student = User::factory()->create(['role' => 'student']);
            DppSubmission::create([
                'dpp_id' => $dpp->id,
                'student_id' => $student->id,
                'score' => 15,
                'max_score' => 20,
                'percentage' => 75,
                'answers' => [],
                'checked_at' => now(),
                'is_passed' => true,
                'submitted_at' => now(),
            ]);
        }
        $resDpp = $this->actingAs($this->admin)->get(route('dpp.show', $dpp->id));
        $resDpp->assertOk();
        $resDpp->assertSee('submissions_page=2');

        // 11. Assignment Submissions Pagination
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $this->admin->id,
            'title' => 'Pagination Assignment Test',
            'description' => 'Test pagination on assignment submissions',
            'due_date' => now()->addDays(5),
            'max_points' => 100,
        ]);
        for ($as = 1; $as <= 15; $as++) {
            $student = User::factory()->create(['role' => 'student']);
            Submission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'content' => "Student solution content {$as}",
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        }
        $resAssign = $this->actingAs($this->admin)->get(route('assignments.show', $assignment->id));
        $resAssign->assertOk();
        $resAssign->assertSee('submissions_page=2');

        // API Submissions Pagination
        $resApiSub = $this->json('GET', "/api/v1/assignments/{$assignment->id}/submissions", ['per_page' => 5, 'page' => 2]);
        $resApiSub->assertOk();
        $resApiSub->assertJsonStructure([
            'assignment',
            'submissions' => [
                'current_page',
                'data',
                'total',
            ],
        ]);

        // 12. Project Submissions Pagination
        $project = AcademicProject::create([
            'class_id' => $this->classA->id,
            'subject_id' => 1,
            'teacher_id' => $this->admin->id,
            'title' => 'Capstone Pagination Test',
            'type' => 'project',
            'group_mode' => 'individual',
            'description' => 'Project brief text for pagination',
            'due_date' => now()->addDays(10),
            'max_marks' => 100,
        ]);
        for ($ps = 1; $ps <= 15; $ps++) {
            $student = User::factory()->create(['role' => 'student']);
            ProjectSubmission::create([
                'project_id' => $project->id,
                'student_id' => $student->id,
                'solution_summary' => "Solution summary description {$ps}",
                'submitted_at' => now(),
            ]);
        }
        $resProj = $this->actingAs($this->admin)->get(route('projects.show', $project->id));
        $resProj->assertOk();
        $resProj->assertSee('submissions_page=2');
    }
}
