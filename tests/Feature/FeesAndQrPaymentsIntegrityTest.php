<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolFeeStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeesAndQrPaymentsIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cashier;

    private User $parent;

    private User $student;

    private AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->parent = User::factory()->create(['role' => 'parent']);
        $this->student = User::factory()->create([
            'role' => 'student',
            'parent_id' => $this->parent->id,
            'is_approved' => true,
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Class 10',
            'section' => 'A',
            'grade_level' => 10,
            'stream' => 'General',
            'academic_year' => '2026-2027',
        ]);

        $this->student->update(['current_class_id' => $this->class->id]);

        ClassEnrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);
    }

    /** 1. Auto invoice generation creates invoices from fee structure without GST or fines */
    public function test_auto_invoice_generation_without_gst_or_fines(): void
    {
        SchoolFeeStructure::create([
            'class_id' => $this->class->id,
            'fee_title' => 'Monthly Tuition Fee',
            'amount' => 3500.00,
            'frequency' => 'monthly',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.generate-invoices'), [
            'class_id' => $this->class->id,
            'billing_month' => '2026-11',
            'due_date' => '2026-11-30',
        ]);

        $response->assertRedirect();

        $invoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('billing_month', '2026-11')
            ->firstOrFail();

        $this->assertEquals(3500.00, (float) $invoice->amount);
        $this->assertEquals(0.00, (float) $invoice->tax);
        $this->assertEquals(0.00, (float) $invoice->discount);
        $this->assertEquals(3500.00, (float) $invoice->total_due);
        $this->assertEquals('unpaid', $invoice->status);
    }

    /** 2. Partial payment supported and cashier records payment */
    public function test_partial_payment_recording_by_cashier(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-001',
            'student_id' => $this->student->id,
            'title' => 'Term 1 Fee',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => '2026-11-30',
        ]);

        // Cashier records partial payment of 2000
        $response = $this->actingAs($this->cashier)->post(route('school.fees.pay', $invoice->id), [
            'amount' => 2000.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
            'reference_no' => 'UTR-UNIQUE-101',
            'notes' => 'First installment',
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(2000.00, (float) $invoice->paid_amount);

        $payment = FeePayment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(2000.00, (float) $payment->amount);
        $this->assertEquals('UTR-UNIQUE-101', $payment->transaction_reference);
        $this->assertEquals('completed', $payment->status);
        $this->assertNotNull($payment->receipt_number);
    }

    /** 3. Duplicate transaction reference (UTR) is strictly rejected */
    public function test_duplicate_transaction_reference_is_rejected(): void
    {
        $invoice1 = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-002',
            'student_id' => $this->student->id,
            'title' => 'Fee 1',
            'amount' => 1000.00,
            'total_due' => 1000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => '2026-11-30',
        ]);

        $invoice2 = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-003',
            'student_id' => $this->student->id,
            'title' => 'Fee 2',
            'amount' => 1000.00,
            'total_due' => 1000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => '2026-11-30',
        ]);

        // First payment with reference UTR-DUP-999 succeeds
        $this->actingAs($this->cashier)->post(route('school.fees.pay', $invoice1->id), [
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
            'reference_no' => 'UTR-DUP-999',
        ]);

        // Second payment attempting exact same reference UTR-DUP-999 must fail
        $resDuplicate = $this->actingAs($this->cashier)->post(route('school.fees.pay', $invoice2->id), [
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
            'reference_no' => 'UTR-DUP-999',
        ]);

        $resDuplicate->assertSessionHasErrors('reference_no');
    }

    /** 4. Cashier verifies pending UPI payment and checks receipt */
    public function test_cashier_verifies_pending_payment_and_views_receipt(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-004',
            'student_id' => $this->student->id,
            'title' => 'Library Fee',
            'amount' => 800.00,
            'total_due' => 800.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => '2026-11-30',
        ]);

        $payment = FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $this->student->id,
            'amount' => 800.00,
            'payment_method' => 'qr',
            'transaction_reference' => 'PENDING-UTR-001',
            'receipt_number' => 'REC-PENDING-001',
            'status' => 'pending',
            'payment_gateway' => 'manual',
        ]);

        // Cashier verifies payment with UTR
        $response = $this->actingAs($this->cashier)->post(route('school.fees.verify-upi', $payment->id), [
            'utr_number' => 'UTR-BANK-VERIFY-1234',
        ]);

        $response->assertRedirect(route('school.fees.receipt', $payment->id));

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals('UTR-BANK-VERIFY-1234', $payment->transaction_reference);

        // View receipt
        $receiptView = $this->actingAs($this->cashier)->get(route('school.fees.receipt', $payment->id));
        $receiptView->assertOk();
        $receiptView->assertSee('UTR-BANK-VERIFY-1234');
        $receiptView->assertSee('REC-PENDING-001');
    }

    /** 5. Fee waiver approval discounts total due */
    public function test_fee_waiver_discounts_total_due(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-005',
            'student_id' => $this->student->id,
            'title' => 'Tuition with Scholarship',
            'amount' => 4000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 4000.00,
            'paid_amount' => 0.00,
            'status' => 'unpaid',
            'due_date' => '2026-11-30',
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.fees.waiver', $invoice->id), [
            'waiver_amount' => 1000.00,
            'reason' => 'Merit Scholarship',
        ]);

        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->discount);
        $this->assertEquals(3000.00, (float) $invoice->total_due);
    }
}
