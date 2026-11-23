<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\Batch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolFeeStructure;
use App\Models\User;
use App\Services\MonthlyFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomatedMonthlyFeesAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected User $parent;
    protected AcademicClass $class9;
    protected Batch $batch9A;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.finance@academy.edu',
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent.sharma@example.com',
        ]);

        $this->class9 = AcademicClass::create([
            'name' => 'Class 9',
            'section' => 'A',
            'grade_level' => 9,
            'academic_year' => '2026-2027',
        ]);

        $this->batch9A = Batch::create([
            'class_id' => $this->class9->id,
            'name' => 'Class 9 Science Batch',
            'code' => 'BAT-9A',
            'shift' => 'Morning',
            'timing_slot' => '08:00 - 14:00',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_approved' => true,
            'email' => 'student.rohan@academy.edu',
            'current_class_id' => $this->class9->id,
            'batch_id' => $this->batch9A->id,
            'parent_id' => $this->parent->id,
            'guardian_email' => $this->parent->email,
        ]);
    }

    public function test_dynamic_monthly_fee_calculation_from_class_structures(): void
    {
        // 1. Create class fee structures (Tuition: 3000, Lab: 500, Activity: 300)
        SchoolFeeStructure::create([
            'class_id' => $this->class9->id,
            'fee_title' => 'Monthly Tuition Fee',
            'fee_type' => 'tuition',
            'amount' => 3000.00,
            'due_frequency' => 'monthly',
        ]);

        SchoolFeeStructure::create([
            'class_id' => $this->class9->id,
            'fee_title' => 'Science Lab & Tech Fee',
            'fee_type' => 'lab',
            'amount' => 500.00,
            'due_frequency' => 'monthly',
        ]);

        SchoolFeeStructure::create([
            'class_id' => $this->class9->id,
            'fee_title' => 'Sports & Activity Fee',
            'fee_type' => 'activity',
            'amount' => 300.00,
            'due_frequency' => 'monthly',
        ]);

        // 2. Run monthly fee generation for current month
        $result = MonthlyFeeService::generateMonthlyInvoices(
            month: 11,
            year: 2026
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['generated_count']);

        $invoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('billing_month', '2026-11')
            ->first();

        $this->assertNotNull($invoice);
        // Calculated total: 3000 + 500 + 300 = 3800.00
        $this->assertEquals(3800.00, (float)$invoice->amount);
        $this->assertEquals(3800.00, (float)$invoice->total_due);
        $this->assertEquals('monthly_auto', $invoice->billing_type);

        // Verify detailed breakdown
        $breakdown = $invoice->fee_breakdown;
        $this->assertIsArray($breakdown);
        $this->assertTrue($breakdown['monthly_billing']);
        $this->assertCount(3, $breakdown['heads']);
        $this->assertEquals(3800.00, $breakdown['subtotal']);
    }

    public function test_auto_charge_runs_automatically_and_carries_forward_arrears(): void
    {
        // 1. Create an existing overdue invoice from prior month
        FeeInvoice::create([
            'invoice_number' => 'INV-2026-PREV-01',
            'student_id' => $this->student->id,
            'title' => 'Previous Month Tuition',
            'category' => 'Tuition',
            'amount' => 2000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 2000.00,
            'paid_amount' => 500.00, // 1500 overdue
            'status' => 'partial',
            'billing_type' => 'monthly_auto',
            'billing_month' => '2026-10',
            'due_date' => '2026-10-15',
        ]);

        // 2. Trigger auto charge for current month
        MonthlyFeeService::updateMonthlyFeeConfig([
            'auto_enabled' => true,
            'default_amount' => 2500.00,
        ]);
        $res = MonthlyFeeService::autoChargeCurrentMonthIfDue();
        $this->assertNotEquals('disabled', $res['status'] ?? null);

        $currentMonthKey = sprintf('%04d-%02d', date('Y'), date('n'));
        $currentInvoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('billing_month', $currentMonthKey)
            ->first();

        $this->assertNotNull($currentInvoice);
        $this->assertEquals('monthly_auto', $currentInvoice->billing_type);

        // Verify arrears carried forward in breakdown
        $this->assertEquals(1500.00, (float)$currentInvoice->fee_breakdown['previous_arrears']);
        $this->assertStringContainsString('Carried Forward Arrears', $currentInvoice->notes);
    }

    public function test_fees_index_displays_paginated_invoices_and_payment_receipts(): void
    {
        // Create 20 invoices to test pagination
        for ($i = 1; $i <= 20; $i++) {
            $inv = FeeInvoice::create([
                'invoice_number' => sprintf('INV-PAG-%04d', $i),
                'student_id' => $this->student->id,
                'title' => "Term Test Fee #{$i}",
                'category' => 'Tuition',
                'amount' => 1000.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 1000.00,
                'paid_amount' => 1000.00,
                'status' => 'paid',
                'billing_type' => 'monthly_auto',
                'billing_month' => sprintf('2025-%02d', ($i % 12) + 1),
                'due_date' => now()->toDateString(),
            ]);

            FeePayment::create([
                'invoice_id' => $inv->id,
                'student_id' => $this->student->id,
                'amount' => 1000.00,
                'payment_method' => 'upi',
                'transaction_reference' => "TXN-PAG-{$i}",
                'receipt_number' => sprintf('REC-PAG-%04d', $i),
                'paid_at' => now(),
                'status' => 'completed',
            ]);
        }

        // Test main fees index
        $res = $this->actingAs($this->admin)->get(route('fees.index'));
        $res->assertStatus(200);
        $res->assertSee('Settled Payment Receipts &amp; Transaction Ledger', false);
        $res->assertSee('Automated Monthly Charging &amp; Dynamic Fee Calculation Active', false);

        // Test school fees index
        $schoolRes = $this->actingAs($this->admin)->get(route('school.fees.index'));
        $schoolRes->assertStatus(200);
        $schoolRes->assertSee('Fee Payment Receipts &amp; Transaction Ledger', false);

        // Test student view
        $studentRes = $this->actingAs($this->student)->get(route('school.fees.index'));
        $studentRes->assertStatus(200);

        // Test parent view
        $parentRes = $this->actingAs($this->parent)->get(route('school.fees.index'));
        $parentRes->assertStatus(200);
    }

    public function test_api_fee_invoices_supports_per_page_pagination(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            FeeInvoice::create([
                'invoice_number' => sprintf('INV-API-%04d', $i),
                'student_id' => $this->student->id,
                'title' => "API Fee #{$i}",
                'category' => 'Tuition',
                'amount' => 500.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 500.00,
                'paid_amount' => 0.00,
                'status' => 'pending',
                'due_date' => now()->addDays(5)->toDateString(),
            ]);
        }

        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $res = $this->getJson('/api/v1/fees?per_page=3');
        $res->assertStatus(200);
        $res->assertJsonPath('per_page', 3);
        $this->assertCount(3, $res->json('data'));
    }
}
