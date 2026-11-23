<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\User;
use App\Services\SchoolReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolReportSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Principal Administrator',
            'email' => 'admin@primeos.edu',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Alan Turing',
            'email' => 'alan.turing@primeos.edu',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Ada Lovelace',
            'email' => 'ada.lovelace@primeos.edu',
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Class 10 - Section A',
            'grade_level' => 10,
            'section' => 'A',
            'stream' => 'Science',
            'academic_year' => '2026-27',
            'capacity' => 45,
            'class_teacher_id' => $this->teacher->id,
            'room_number' => 'Lab 301',
        ]);

        $this->student->update([
            'current_class_id' => $this->class->id,
            'roll_number' => 1,
            'admission_number' => 'ADM-2026-0001',
            'guardian_name' => 'Lord Byron',
            'guardian_phone' => '+91 99999 88888',
        ]);
    }

    /**
     * Test admin can access reports center overview and switch between categories.
     */
    public function test_admin_can_access_reports_center_and_switch_categories(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Institutional Reports Center');
        $response->assertSee('Academic & Examinations');
        $response->assertSee('Attendance & Registers');
        $response->assertSee('Fees & Treasury');
        $response->assertSee('Student Demographics');
        $response->assertSee('Faculty & Operations');
        $response->assertSee('Export CSV / Excel');
        $response->assertSee('Print Official Report');
    }

    /**
     * Test admin can access financial fee collection reports.
     */
    public function test_admin_can_view_fee_collection_and_treasury_reports(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-2026-TEST-001',
            'student_id' => $this->student->id,
            'title' => 'Term 1 Tuition Fee',
            'category' => 'tuition',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 5000.00,
            'status' => 'paid',
            'due_date' => now()->addDays(15),
        ]);

        FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $this->student->id,
            'transaction_reference' => 'TXN-UPI-998877',
            'payment_method' => 'upi',
            'amount' => 5000.00,
            'paid_at' => now(),
            'status' => 'success',
            'receipt_number' => 'REC-2026-0001',
            'payment_gateway' => 'bharat_qr',
        ]);

        $response = $this->actingAs($this->admin)->get(route('school.reports.index', [
            'category' => 'finance',
            'report_type' => 'fee_collection',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Fee Collection Register');
        $response->assertSee('Total Fee Realized');
        $response->assertSee('₹ 5,000.00');
        $response->assertSee('REC-2026-0001');
        $response->assertSee('Ada Lovelace');
    }

    /**
     * Test teacher can view academic and attendance reports.
     */
    public function test_teacher_can_access_academic_and_attendance_reports(): void
    {
        // Academic
        $academicResp = $this->actingAs($this->teacher)->get(route('school.reports.index', [
            'category' => 'academic',
            'report_type' => 'exam_merit_list',
        ]));
        $academicResp->assertStatus(200);
        $academicResp->assertSee('Terminal Exam Merit List & Rankings');

        // Attendance
        $attResp = $this->actingAs($this->teacher)->get(route('school.reports.index', [
            'category' => 'attendance',
            'report_type' => 'attendance_summary',
        ]));
        $attResp->assertStatus(200);
        $attResp->assertSee('Daily Class Attendance Summary');
    }

    /**
     * Test teacher is strictly forbidden from accessing financial treasury reports.
     */
    public function test_teacher_is_forbidden_from_financial_reports(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('school.reports.index', [
            'category' => 'finance',
            'report_type' => 'fee_collection',
        ]));

        $response->assertStatus(403);
    }

    /**
     * Test student cannot access reports center.
     */
    public function test_student_cannot_access_reports_center(): void
    {
        $response = $this->actingAs($this->student)->get(route('school.reports.index'));
        $response->assertStatus(403);
    }

    /**
     * Test guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('school.reports.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test CSV export endpoint streams proper CSV headers and file content.
     */
    public function test_csv_export_streams_valid_content(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.reports.export', [
            'category' => 'students',
            'report_type' => 'class_strength',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename='));
    }

    /**
     * Test print view renders institutional letterhead and official authorization signatures.
     */
    public function test_print_view_renders_institutional_letterhead_and_signatures(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.reports.print', [
            'category' => 'faculty',
            'report_type' => 'teacher_workload',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Faculty Workload & Allocation Matrix');
        $response->assertSee('Prepared By (Reporting Officer)');
        $response->assertSee('Verified By (Registrar / HOD)');
        $response->assertSee('Principal / Head of Institution');
        $response->assertSee('window.print()', false);
    }

    /**
     * Test that all report types defined in the system execute without error.
     */
    public function test_all_defined_report_types_execute_cleanly(): void
    {
        foreach (SchoolReportService::REPORTS as $key => $meta) {
            $response = $this->actingAs($this->admin)->get(route('school.reports.index', [
                'category' => $meta['category'],
                'report_type' => $key,
            ]));

            $this->assertEquals(
                200,
                $response->status(),
                "Failed executing report type: {$key}"
            );
            $response->assertSee($meta['name']);
        }
    }
}
