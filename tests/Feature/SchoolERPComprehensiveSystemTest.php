<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchoolERPComprehensiveSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Grade 10',
            'grade_level' => 10,
            'stream' => 'Science',
            'section' => 'A',
            'capacity' => 40,
            'room_number' => 'Lab 1',
            'academic_year' => '2026-2027',
            'class_teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_fee_defaulters_filter_and_stats(): void
    {
        $student1 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);
        $student2 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);

        // Defaulter: past due date and unpaid
        FeeInvoice::create([
            'invoice_number' => 'INV-DEF-001',
            'student_id' => $student1->id,
            'title' => 'Tuition Fee Past Due',
            'category' => 'tuition',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'overdue',
            'due_date' => now()->subDays(5)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        // Non-defaulter: future due date
        FeeInvoice::create([
            'invoice_number' => 'INV-CUR-002',
            'student_id' => $student2->id,
            'title' => 'Tuition Fee Current',
            'category' => 'tuition',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(15)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        $response = $this->actingAs($this->admin)->get(route('school.fees.index', ['filter' => 'defaulters']));
        $response->assertStatus(200);
        $response->assertSee('INV-DEF-001');
        $response->assertDontSee('INV-CUR-002');
    }

    public function test_fee_waiver_approval_flow(): void
    {
        $student = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-WAIV-001',
            'student_id' => $student->id,
            'title' => 'Tuition Fee',
            'category' => 'tuition',
            'amount' => 6000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 6000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.waiver', $invoice->id), [
            'waiver_amount' => 2000.00,
            'reason' => 'Merit Scholarship Top Scorer',
        ]);

        $response->assertRedirect();
        $invoice->refresh();

        $this->assertEquals(2000.00, (float) $invoice->discount);
        $this->assertEquals(4000.00, (float) $invoice->total_due);
        $this->assertStringContainsString('Merit Scholarship Top Scorer', $invoice->notes);
    }

    public function test_fee_refund_flow(): void
    {
        $student = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-REF-001',
            'student_id' => $student->id,
            'title' => 'Tuition Fee Paid',
            'category' => 'tuition',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 5000.00,
            'status' => 'paid',
            'due_date' => now()->addDays(10)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        $payment = FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'transaction_reference' => 'TXN-REFUND-TEST',
            'receipt_number' => 'REC-001',
            'paid_at' => now(),
            'status' => 'completed',
            'received_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.refund', $payment->id), [
            'reason' => 'Duplicate payment refund requested by parent',
        ]);

        $response->assertRedirect();
        $payment->refresh();
        $invoice->refresh();

        $this->assertEquals('refunded', $payment->status);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_bulk_payment_recording(): void
    {
        $student1 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);
        $student2 = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id]);

        $inv1 = FeeInvoice::create([
            'invoice_number' => 'INV-BULK-001',
            'student_id' => $student1->id,
            'title' => 'Tuition Inv 1',
            'category' => 'tuition',
            'amount' => 3000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 3000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        $inv2 = FeeInvoice::create([
            'invoice_number' => 'INV-BULK-002',
            'student_id' => $student2->id,
            'title' => 'Tuition Inv 2',
            'category' => 'tuition',
            'amount' => 4000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 4000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10)->toDateString(),
            'billing_type' => 'monthly',
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.bulk-payments'), [
            'payments' => [
                [
                    'invoice_id' => $inv1->id,
                    'amount' => 3000.00,
                    'payment_method' => 'cash',
                    'transaction_reference' => 'CASH-BATCH-01',
                ],
                [
                    'invoice_id' => $inv2->id,
                    'amount' => 2000.00,
                    'payment_method' => 'upi',
                    'transaction_reference' => 'UPI-BATCH-02',
                ],
            ],
        ]);

        $response->assertRedirect();
        $inv1->refresh();
        $inv2->refresh();

        $this->assertEquals('paid', $inv1->status);
        $this->assertEquals(3000.00, (float) $inv1->paid_amount);
        $this->assertEquals('partial', $inv2->status);
        $this->assertEquals(2000.00, (float) $inv2->paid_amount);
    }

    public function test_auto_assign_exam_schedules_from_class_subjects(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Mid Term 2026',
            'academic_year' => '2026-2027',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => 'upcoming',
        ]);

        $sub1 = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Physics',
            'code' => 'PHY-10',
            'teacher_id' => $this->teacher->id,
            'weekly_periods' => 5,
        ]);

        $sub2 = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Chemistry',
            'code' => 'CHEM-10',
            'teacher_id' => $this->teacher->id,
            'weekly_periods' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.exams.auto-assign-schedules'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('school_exam_schedules', [
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
            'subject_id' => $sub1->id,
        ]);

        $this->assertDatabaseHas('school_exam_schedules', [
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
            'subject_id' => $sub2->id,
        ]);
    }

    public function test_bulk_marks_csv_import_and_reevaluation(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Final Term 2026',
            'academic_year' => '2026-2027',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'ongoing',
        ]);

        $subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
            'teacher_id' => $this->teacher->id,
            'weekly_periods' => 6,
        ]);

        $schedule = SchoolExamSchedule::create([
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'exam_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100.00,
            'pass_marks' => 35.00,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $this->class->id,
            'roll_number' => '15',
        ]);

        ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'roll_number' => 15,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);

        // Test Bulk CSV Import
        $csvContent = "roll_number,marks_obtained,remarks\n15,88.5,Excellent work\n";
        $csvFile = UploadedFile::fake()->createWithContent('marks.csv', $csvContent);

        $importResponse = $this->actingAs($this->admin)->post(route('school.exams.marks.bulk-import'), [
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'marks_file' => $csvFile,
        ]);

        $importResponse->assertRedirect();

        $mark = SchoolExamMark::where('exam_term_id', $term->id)
            ->where('class_id', $this->class->id)
            ->where('subject_id', $subject->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($mark);
        $this->assertEquals(88.5, (float) $mark->marks_obtained);
        $this->assertEquals('A', $mark->letter_grade);

        // Test Re-evaluation flow
        $reEvalResponse = $this->actingAs($this->admin)->post(route('school.exams.marks.re-evaluate', $mark->id), [
            'revised_marks' => 95.0,
            'reason' => 'Totaling error corrected on page 3',
        ]);

        $reEvalResponse->assertRedirect();
        $mark->refresh();

        $this->assertEquals(95.0, (float) $mark->marks_obtained);
        $this->assertEquals('A+', $mark->letter_grade);
        $this->assertStringContainsString('Totaling error corrected on page 3', $mark->teacher_remarks);
    }

    public function test_auto_absent_command_skips_holidays_and_excuses_leaves(): void
    {
        Carbon::setTestNow('2026-11-20 18:00:00'); // Friday close-of-day

        try {
            $studentOnLeave = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id, 'is_approved' => true]);
            $unrecordedStudent = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id, 'is_approved' => true]);

            ClassEnrollment::create([
                'student_id' => $studentOnLeave->id,
                'class_id' => $this->class->id,
                'academic_year' => $this->class->academic_year ?: '2026-2027',
                'roll_number' => 1,
                'status' => 'active',
            ]);

            ClassEnrollment::create([
                'student_id' => $unrecordedStudent->id,
                'class_id' => $this->class->id,
                'academic_year' => $this->class->academic_year ?: '2026-2027',
                'roll_number' => 2,
                'status' => 'active',
            ]);

            $today = now()->toDateString();

            StudentLeaveRequest::create([
                'student_id' => $studentOnLeave->id,
                'start_date' => $today,
                'end_date' => $today,
                'type' => 'medical',
                'reason' => 'Doctor consultation',
                'status' => 'approved',
                'approved_by' => $this->admin->id,
            ]);

            // Case 1: Run command when today is NOT a holiday
            Artisan::call('attendance:auto-absent', ['--date' => $today]);

            // Student on approved leave should be marked 'excused'
            $leaveRecord = AttendanceRecord::where('student_id', $studentOnLeave->id)->first();
            $this->assertNotNull($leaveRecord);
            $this->assertEquals('excused', $leaveRecord->status);

            // Unrecorded student should be marked 'absent'
            $absentRecord = AttendanceRecord::where('student_id', $unrecordedStudent->id)->first();
            $this->assertNotNull($absentRecord);
            $this->assertEquals('absent', $absentRecord->status);

            // Case 2: On a declared holiday, command should skip and not create records
            $holidayDate = '2026-11-19'; // Thursday holiday
            AcademicHoliday::create([
                'name' => 'National Festival Holiday',
                'type' => 'festival',
                'holiday_date' => $holidayDate,
                'is_instructional_off' => true,
                'description' => 'Official holiday',
            ]);

            $studentNew = User::factory()->create(['role' => 'student', 'current_class_id' => $this->class->id, 'is_approved' => true]);
            ClassEnrollment::create([
                'student_id' => $studentNew->id,
                'class_id' => $this->class->id,
                'academic_year' => $this->class->academic_year ?: '2026-2027',
                'roll_number' => 3,
                'status' => 'active',
            ]);
            Artisan::call('attendance:auto-absent', ['--date' => $holidayDate]);

            $holidayRecord = AttendanceRecord::where('student_id', $studentNew->id)
                ->whereHas('session', fn ($q) => $q->whereDate('session_date', $holidayDate))
                ->first();
            $this->assertNull($holidayRecord);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_visual_analytics_graphs_render_in_dashboards(): void
    {
        // 1. Admin Dashboard visual charts
        $adminRes = $this->actingAs($this->admin)->get(route('dashboard'));
        $adminRes->assertOk();
        $adminRes->assertSee('chart-attendance-trend');
        $adminRes->assertSee('chart-fee-distribution');
        $adminRes->assertSee('chart-admissions-funnel');
        $adminRes->assertSee('chart-class-capacity');
        $adminRes->assertSee('chart.js');

        // 2. Analytics Hub visual charts
        $analyticsRes = $this->actingAs($this->admin)->get(route('analytics.index'));
        $analyticsRes->assertOk();
        $analyticsRes->assertSee('chart-bloom-radar');
        $analyticsRes->assertSee('chart-batch-attendance');
        $analyticsRes->assertSee('chart.js');

        // 3. Parent Portal visual chart
        $parent = User::factory()->create(['role' => 'parent']);
        $student = User::factory()->create([
            'role' => 'student',
            'parent_id' => $parent->id,
            'current_class_id' => $this->class->id,
        ]);

        $parentRes = $this->actingAs($parent)->get(route('school.parent.dashboard'));
        $parentRes->assertOk();
        $parentRes->assertSee('chart-parent-attendance');
        $parentRes->assertSee('chart.js');
    }
}
