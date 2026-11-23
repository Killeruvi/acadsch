<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiOwnershipSecurityPass7Test extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_list_school_wide_fee_invoices(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        Sanctum::actingAs($teacher);

        $this->getJson('/api/v1/fees')->assertForbidden();
    }

    public function test_student_cannot_mark_an_invoice_paid_by_claiming_a_payment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/fees/999999/pay', [
            'amount' => 100,
            'payment_method' => 'UPI / NetBanking',
        ])->assertForbidden();
    }

    public function test_parent_cannot_mark_an_invoice_paid_without_verified_gateway_or_admin_recording(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        Sanctum::actingAs($parent);

        $this->postJson('/api/v1/fees/999999/pay', [
            'amount' => 100,
            'payment_method' => 'Cash',
        ])->assertForbidden();
    }
}
