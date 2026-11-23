<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolFeeStructure;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\SchoolSection;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeepInconsistencyRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected AcademicClass $class8;
    protected AcademicClass $class9;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.edu',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@primeos.edu',
            'is_approved' => true,
        ]);

        $sectionA = SchoolSection::firstOrCreate(
            ['stream' => 'General', 'name' => 'A'],
            ['capacity' => 40]
        );

        $sectionB = SchoolSection::firstOrCreate(
            ['stream' => 'General', 'name' => 'B'],
            ['capacity' => 40]
        );

        $this->class8 = AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'stream' => 'General',
            'section' => 'A',
            'section_id' => $sectionA->id,
            'class_teacher_id' => $this->teacher->id,
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $this->class9 = AcademicClass::create([
            'name' => 'Class 9',
            'grade_level' => 9,
            'stream' => 'General',
            'section' => 'B',
            'section_id' => $sectionB->id,
            'academic_year' => '2026-2027',
            'capacity' => 40,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Rohan Sen',
            'email' => 'rohan@primeos.edu',
            'admission_number' => 'ADM-2026-8001',
            'current_class_id' => $this->class8->id,
            'roll_number' => 1,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class8->id,
            'admission_number' => 'ADM-2026-8001',
            'roll_number' => 1,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);
    }

    /**
     * Fix 1: Leave approval does not create future phantom AttendanceSession records,
     * but pre-populates 'excused' status when the teacher opens roll call on that date.
     */
    public function test_leave_approval_does_not_create_future_phantom_attendance_sessions(): void
    {
        $futureDate = now()->addDays(5)->toDateString();

        $leave = StudentLeaveRequest::create([
            'student_id' => $this->student->id,
            'type' => 'medical',
            'start_date' => $futureDate,
            'end_date' => $futureDate,
            'reason' => 'Doctor appointment and medical observation',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)->post(route('leaves.review', $leave->id), [
            'status' => 'approved',
            'teacher_remarks' => 'Approved on medical grounds.',
        ]);

        $response->assertSessionHas('success');

        // Verify NO future attendance session was pre-instantiated in database
        $futureSession = AttendanceSession::where('class_id', $this->class8->id)
            ->whereDate('session_date', $futureDate)
            ->first();
        $this->assertNull($futureSession);

        // When teacher visits the attendance take page for that future date, student is pre-marked excused
        $pageResponse = $this->actingAs($this->admin)->get(route('school.attendance.take', [
            'classId' => $this->class8->id,
            'date' => $futureDate,
        ]));

        $pageResponse->assertOk();
        $existingRecords = $pageResponse->viewData('existingRecords');
        $this->assertArrayHasKey($this->student->id, $existingRecords);
        $this->assertEquals('excused', $existingRecords[$this->student->id]['status']);
    }

    /**
     * Fix 2: Student transfer deactivates the prior class enrollment to prevent dual active enrollment.
     */
    public function test_class_transfer_deactivates_old_class_enrollment(): void
    {
        $response = $this->actingAs($this->admin)->put(route('school.students.update', $this->student->id), [
            'name' => 'Rohan Sen',
            'email' => 'rohan@primeos.edu',
            'admission_number' => 'ADM-2026-8001',
            'class_id' => $this->class9->id,
            'roll_number' => 15,
        ]);

        $response->assertSessionHas('success');

        $this->student->refresh();
        $this->assertEquals($this->class9->id, $this->student->current_class_id);

        // Class 8 enrollment must be transferred
        $oldEnrollment = ClassEnrollment::where('student_id', $this->student->id)
            ->where('class_id', $this->class8->id)
            ->first();
        $this->assertNotNull($oldEnrollment);
        $this->assertEquals('transferred', $oldEnrollment->status);

        // Class 9 enrollment must be active
        $newEnrollment = ClassEnrollment::where('student_id', $this->student->id)
            ->where('class_id', $this->class9->id)
            ->first();
        $this->assertNotNull($newEnrollment);
        $this->assertEquals('active', $newEnrollment->status);
    }

    /**
     * Fix 3: Homeroom Class Teacher cannot be assigned to a user with student or parent role.
     */
    public function test_class_teacher_cannot_be_assigned_to_student_or_parent(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($this->admin)->post(route('school.assignments.class-teacher.assign'), [
            'class_id' => $this->class8->id,
            'teacher_id' => $parent->id,
        ]);

        $response->assertSessionHasErrors(['teacher_id']);

        $this->class8->refresh();
        $this->assertNotEquals($parent->id, $this->class8->class_teacher_id);
    }

    /**
     * Fix 4: Exam marks entry rejects marks_obtained exceeding max_marks and pass_marks exceeding max_marks.
     */
    public function test_exam_marks_validation_rejects_invalid_ranges(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Term 1 Midterms',
            'academic_year' => '2026-2027',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $subject = AcademicSubject::create([
            'class_id' => $this->class8->id,
            'name' => 'Mathematics',
            'code' => 'MTH-8',
            'teacher_id' => $this->teacher->id,
        ]);

        // Case A: pass_marks >= max_marks is rejected
        $response1 = $this->actingAs($this->admin)->post(route('school.exams.marks.store'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->class8->id,
            'subject_id' => $subject->id,
            'max_marks' => 100,
            'pass_marks' => 110, // Invalid!
            'marks' => [
                $this->student->id => ['marks_obtained' => 85],
            ],
        ]);
        $response1->assertSessionHasErrors(['pass_marks']);

        // Case B: marks_obtained > max_marks is rejected
        $response2 = $this->actingAs($this->admin)->post(route('school.exams.marks.store'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->class8->id,
            'subject_id' => $subject->id,
            'max_marks' => 100,
            'pass_marks' => 35,
            'marks' => [
                $this->student->id => ['marks_obtained' => 135], // Invalid!
            ],
        ]);
        $response2->assertSessionHasErrors(["marks.{$this->student->id}.marks_obtained"]);
    }

    /**
     * Fix 5: Report Card assigns COMPARTMENT/FAIL if a student failed a subject,
     * and bounds attendance percentage properly.
     */
    public function test_report_card_honors_subject_failure_and_bounds_attendance(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Annual Exam',
            'academic_year' => '2026-2027',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
            'status' => 'completed',
        ]);

        $sub1 = AcademicSubject::create(['class_id' => $this->class8->id, 'name' => 'Science', 'code' => 'SCI-8']);
        $sub2 = AcademicSubject::create(['class_id' => $this->class8->id, 'name' => 'English', 'code' => 'ENG-8']);

        // High score in sub1, failing score in sub2
        SchoolExamMark::create([
            'exam_term_id' => $term->id,
            'class_id' => $this->class8->id,
            'subject_id' => $sub1->id,
            'student_id' => $this->student->id,
            'marks_obtained' => 95,
            'max_marks' => 100,
            'pass_marks' => 35,
            'letter_grade' => 'A+',
        ]);

        SchoolExamMark::create([
            'exam_term_id' => $term->id,
            'class_id' => $this->class8->id,
            'subject_id' => $sub2->id,
            'student_id' => $this->student->id,
            'marks_obtained' => 20, // Failed!
            'max_marks' => 100,
            'pass_marks' => 35,
            'letter_grade' => 'F',
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.exams.generate-report-cards'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->class8->id,
        ]);

        $response->assertSessionHas('success');

        $reportCard = SchoolReportCard::where('exam_term_id', $term->id)
            ->where('class_id', $this->class8->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($reportCard);
        $this->assertEquals('FAIL', $reportCard->result_status);
        $this->assertEquals('COMPARTMENT', $reportCard->overall_grade);
        $this->assertLessThanOrEqual(100.0, (float) $reportCard->attendance_percentage);
    }

    /**
     * Fix 6: Fee invoice batch generation only sums monthly structures and is not suppressed by incidental invoices.
     */
    public function test_fee_batch_invoicing_filters_monthly_structures_only(): void
    {
        // Monthly tuition: 3500
        SchoolFeeStructure::create([
            'class_id' => $this->class8->id,
            'fee_title' => 'Monthly Tuition',
            'amount' => 3500.00,
            'due_frequency' => 'monthly',
        ]);

        // Annual development fee: 12000 (must NOT be billed monthly)
        SchoolFeeStructure::create([
            'class_id' => $this->class8->id,
            'fee_title' => 'Annual Infrastructure Fee',
            'amount' => 12000.00,
            'due_frequency' => 'annual',
        ]);

        // Existing incidental library fee for that month
        FeeInvoice::create([
            'invoice_number' => 'LIB-FINE-99',
            'student_id' => $this->student->id,
            'title' => 'Library Lost Book Replacement',
            'category' => 'library',
            'amount' => 250.00,
            'total_due' => 250.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->toDateString(),
            'billing_month' => '2026-11',
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.generate-invoices'), [
            'class_id' => $this->class8->id,
            'billing_month' => '2026-11',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertSessionHas('success');

        // Tuition invoice must have been created despite library invoice existing
        $tuitionInv = FeeInvoice::where('student_id', $this->student->id)
            ->where('billing_month', '2026-11')
            ->where('category', 'tuition')
            ->first();

        $this->assertNotNull($tuitionInv);
        // Must bill only the 3500 monthly fee, NOT the 12000 annual fee
        $this->assertEquals(3500.00, (float) $tuitionInv->amount);
        $this->assertEquals(3500.00, (float) $tuitionInv->total_due);
    }

    /**
     * Fix 7: Attendance cannot be marked on Academic Holidays by faculty.
     */
    public function test_attendance_cannot_be_recorded_on_academic_holidays_by_faculty(): void
    {
        $today = now()->toDateString();

        AcademicHoliday::create([
            'name' => 'National Republic Day',
            'type' => 'gazetted',
            'holiday_date' => $today,
            'is_instructional_off' => true,
        ]);

        $response = $this->actingAs($this->teacher)->post(route('school.attendance.store', ['classId' => $this->class8->id]), [
            'session_date' => $today,
            'records' => [
                $this->student->id => ['status' => 'present'],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Academic Holiday', session('error'));
    }
}
