<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\Batch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\User;
use App\Services\MonthlyFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyFeesAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student1;

    protected User $student2;

    protected AcademicClass $class10;

    protected Batch $batch10A;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.fees@primearc.edu.in'],
            [
                'name' => 'Finance Director Raman',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'employee_id_number' => 'ADM-FEE-01',
            ]
        );

        $this->class10 = AcademicClass::create([
            'name' => 'Class 10',
            'section' => 'A',
            'grade_level' => 10,
            'academic_year' => '2026-2027',
        ]);

        $this->batch10A = Batch::create([
            'class_id' => $this->class10->id,
            'name' => 'Class 10 Biotech Scholars',
            'code' => 'BAT-10A',
            'shift' => 'Morning',
            'timing_slot' => '08:00 - 14:00',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $this->student1 = User::create([
            'name' => 'Scholar Dev',
            'email' => 'scholar.dev@primearc.edu.in',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_approved' => true,
            'student_id_number' => 'PA-STD-101',
            'batch_id' => $this->batch10A->id,
        ]);

        $this->student2 = User::create([
            'name' => 'Scholar Priya',
            'email' => 'scholar.priya@primearc.edu.in',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_approved' => true,
            'student_id_number' => 'PA-STD-102',
            'batch_id' => $this->batch10A->id,
        ]);
    }

    /**
     * 1. Test Admin fees index page displays both manual and auto monthly controls.
     */
    public function test_admin_fees_index_renders_both_manual_and_monthly_automation_controls(): void
    {
        $response = $this->actingAs($this->admin)->get(route('fees.index'));

        $response->assertStatus(200);
        $response->assertSee('Issue Manual Invoice');
        $response->assertSee('Auto Monthly Invoices');
        $response->assertSee('Monthly Settings');
        $response->assertSee('Monthly Auto');
        $response->assertSee('Manual');
    }

    /**
     * 2. Test Admin can generate recurring monthly fees in bulk.
     */
    public function test_admin_can_generate_monthly_fees_in_bulk(): void
    {
        $response = $this->actingAs($this->admin)->post(route('fees.generate-monthly'), [
            'month' => 11,
            'year' => 2026,
            'amount' => 1800.00,
            'due_date' => '2026-11-15',
        ]);

        $response->assertRedirect(route('fees.index'));
        $response->assertSessionHas('success');

        // Both students should have received an invoice
        $this->assertEquals(2, FeeInvoice::where('billing_type', 'monthly_auto')->count());

        $invoice1 = FeeInvoice::where('student_id', $this->student1->id)->first();
        $this->assertNotNull($invoice1);
        $this->assertEquals('monthly_auto', $invoice1->billing_type);
        $this->assertEquals('2026-11', $invoice1->billing_month);
        $this->assertEquals(1800.00, $invoice1->amount);
        $this->assertEquals(1800.00, $invoice1->total_due);
        $this->assertEquals('pending', $invoice1->status);
        $this->assertEquals('2026-11-15', $invoice1->due_date->format('Y-m-d'));
        $this->assertTrue($invoice1->isMonthlyAuto());
        $this->assertFalse($invoice1->isManual());
    }

    /**
     * 3. Test Idempotency: Re-running monthly generation prevents duplicate billing.
     */
    public function test_monthly_generation_is_idempotent_and_prevents_duplicate_billing(): void
    {
        // First run
        MonthlyFeeService::generateMonthlyInvoices(month: 11, year: 2026, customAmount: 1500.00);
        $this->assertEquals(2, FeeInvoice::count());

        // Second run with same month
        $result = MonthlyFeeService::generateMonthlyInvoices(month: 11, year: 2026, customAmount: 1500.00);

        // No new invoices should be created; all candidates skipped
        $this->assertEquals(0, $result['generated_count']);
        $this->assertEquals(2, $result['skipped_count']);
        $this->assertEquals(2, FeeInvoice::count());
    }

    /**
     * 4. Test Both Manual Invoices and Monthly Auto Invoices Coexist.
     */
    public function test_both_manual_invoice_and_monthly_auto_invoices_coexist(): void
    {
        // 1. Generate an auto monthly invoice
        $this->actingAs($this->admin)->post(route('fees.generate-monthly'), [
            'month' => 11,
            'year' => 2026,
            'amount' => 1500.00,
        ]);

        // 2. Issue a manual invoice for a special event/lab
        $this->actingAs($this->admin)->post(route('fees.store'), [
            'student_id' => $this->student1->id,
            'title' => 'Advanced Biotechnology Wet Lab Kit Fee',
            'category' => 'Lab & Tech Fee',
            'amount' => 3500.00,
            'discount' => 500.00,
            'due_date' => '2026-11-20',
            'notes' => 'One-time manual lab consumables invoice.',
        ]);

        $this->assertEquals(3, FeeInvoice::count()); // 2 monthly auto + 1 manual

        $manualInvoice = FeeInvoice::where('title', 'Advanced Biotechnology Wet Lab Kit Fee')->first();
        $this->assertNotNull($manualInvoice);
        $this->assertTrue($manualInvoice->isManual());
        $this->assertFalse($manualInvoice->isMonthlyAuto());
        $this->assertEquals(3000.00, $manualInvoice->total_due);

        // Verify filter by billing_type=monthly_auto
        $autoFilterRes = $this->actingAs($this->admin)->get(route('fees.index', ['billing_type' => 'monthly_auto']));
        $autoFilterRes->assertStatus(200);
        $autoFilterRes->assertSee('Monthly Tuition Fee - November 2026');
        $autoFilterRes->assertDontSee('Advanced Biotechnology Wet Lab Kit Fee');

        // Verify filter by billing_type=manual
        $manualFilterRes = $this->actingAs($this->admin)->get(route('fees.index', ['billing_type' => 'manual']));
        $manualFilterRes->assertStatus(200);
        $manualFilterRes->assertSee('Advanced Biotechnology Wet Lab Kit Fee');
        $manualFilterRes->assertDontSee('Monthly Tuition Fee - November 2026');
    }

    /**
     * 5. Test Artisan Command executes monthly recurring billing.
     */
    public function test_artisan_command_generates_monthly_fees(): void
    {
        $this->artisan('fees:generate-monthly', [
            '--month' => 12,
            '--year' => 2026,
            '--amount' => 2000.00,
            '--due' => '2026-12-10',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertEquals(2, FeeInvoice::where('billing_month', '2026-12')->count());
        $this->assertEquals(2000.00, FeeInvoice::where('billing_month', '2026-12')->first()->total_due);
    }

    /**
     * 6. Test Admin can configure monthly fee defaults.
     */
    public function test_admin_can_update_monthly_fee_automation_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('fees.monthly-config'), [
            'auto_enabled' => '1',
            'default_amount' => 2200.00,
            'due_day' => 12,
            'category' => 'Tuition',
            'title_template' => 'Institutional Monthly Dues - {month} {year}',
            'notes_template' => 'Standard monthly fee.',
        ]);

        $response->assertRedirect(route('fees.index'));
        $response->assertSessionHas('success');

        $config = MonthlyFeeService::getMonthlyFeeConfig();
        $this->assertTrue($config['auto_enabled']);
        $this->assertEquals(2200.00, $config['default_amount']);
        $this->assertEquals(12, $config['due_day']);
        $this->assertEquals('Institutional Monthly Dues - {month} {year}', $config['title_template']);
    }

    /**
     * 7. Test Student can view their auto monthly invoice and settle it online with instant receipt.
     */
    public function test_student_can_view_and_pay_auto_monthly_invoice(): void
    {
        MonthlyFeeService::generateMonthlyInvoices(month: 11, year: 2026, customAmount: 1600.00);

        $invoice = FeeInvoice::where('student_id', $this->student1->id)->first();
        $this->assertNotNull($invoice);

        // Student views invoice
        $showRes = $this->actingAs($this->student1)->get(route('fees.show', $invoice->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('Monthly Recurring');
        $showRes->assertSee('1,600.00');

        // Student cannot self-pay online (403 Forbidden under QR/Cashier-only model)
        $payRes = $this->actingAs($this->student1)->post(route('fees.pay', $invoice->id), [
            'amount' => 1600.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
        ]);
        $payRes->assertStatus(403);

        // Cashier/Admin records payment
        $adminPayRes = $this->actingAs($this->admin)->post(route('fees.pay', $invoice->id), [
            'amount' => 1600.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
            'reference_no' => 'UPI-REC-1600',
            'notes' => 'Settled via school QR code.',
        ]);

        $payment = FeePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $adminPayRes->assertRedirect(route('fees.receipt', $payment->id));

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.00, $invoice->balance);

        // Student views receipt
        $receiptRes = $this->actingAs($this->student1)->get(route('fees.receipt', $payment->id));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee('OFFICIALLY PAID');
        $receiptRes->assertSee($payment->receipt_number);
    }
}
