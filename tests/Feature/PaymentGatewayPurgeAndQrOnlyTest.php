<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademySetting;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayPurgeAndQrOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected User $admin;

    protected User $cashier;

    protected FeeInvoice $invoice;

    protected AcademicClass $academicClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicClass = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_approved' => true,
            'current_class_id' => $this->academicClass->id,
            'roll_number' => 1,
        ]);

        ClassEnrollment::create([
            'class_id' => $this->academicClass->id,
            'student_id' => $this->student->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_approved' => true,
        ]);

        $this->invoice = FeeInvoice::create([
            'invoice_number' => 'INV-2026-1001',
            'student_id' => $this->student->id,
            'title' => 'Tuition Fee - October 2026',
            'category' => 'tuition',
            'amount' => 5000.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total_due' => 5000.00,
            'paid_amount' => 0.00,
            'status' => 'pending',
            'due_date' => now()->addDays(15)->toDateString(),
            'billing_type' => 'monthly_auto',
            'billing_month' => now()->format('Y-m'),
        ]);
    }

    public function test_payment_gateway_webhook_endpoints_are_completely_removed_and_return_404(): void
    {
        $payload = [
            'event' => 'payment.captured',
            'data' => [
                'transaction_id' => 'tx_mock_123',
                'amount' => 5000,
            ],
        ];

        // All legacy gateway webhook URLs return 404
        $this->postJson('/api/fees/webhook', $payload)->assertStatus(404);
        $this->postJson('/api/v1/fees/webhook', $payload)->assertStatus(404);
    }

    public function test_gateway_service_configs_and_keys_are_purged(): void
    {
        $this->assertNull(config('services.ownpay'));
        $this->assertNull(config('services.razorpay'));
        $this->assertNull(config('services.stripe'));
        $this->assertNull(config('services.cashfree'));
    }

    public function test_fee_invoice_show_displays_qr_payment_flow_and_no_gateway_checkout(): void
    {
        AcademySetting::set('payment_qr_image', 'school/qr/sample.png');
        AcademySetting::set('upi_vpa', 'school@upi');

        $response = $this->actingAs($this->student)->get(route('fees.show', $this->invoice->id));
        $response->assertStatus(200);

        // QR / Offline instructions are shown
        $response->assertSee('Pay via School QR Code');
        $response->assertSee('school@upi');

        // Zero gateway buttons or checkout SDKs
        $response->assertDontSee('Pay with Razorpay');
        $response->assertDontSee('Pay with Stripe');
        $response->assertDontSee('checkout.ownpay.io');
    }

    public function test_cashier_or_admin_can_record_received_payment_and_generate_receipt(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('school.fees.pay', $this->invoice->id), [
            'amount' => 5000.00,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'upi',
            'reference_no' => 'UPI-REF-9988776655',
            'notes' => 'Settled via Official School QR verification at counter.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_payments', [
            'invoice_id' => $this->invoice->id,
            'student_id' => $this->student->id,
            'amount' => 5000.00,
            'payment_method' => 'upi',
            'transaction_reference' => 'UPI-REF-9988776655',
            'status' => 'completed',
        ]);

        $this->assertEquals('paid', $this->invoice->fresh()->status);
        $this->assertEquals(5000.00, (float) $this->invoice->fresh()->paid_amount);
    }
}
