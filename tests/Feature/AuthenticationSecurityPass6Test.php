<?php

namespace Tests\Feature;

use App\Models\AcademySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationSecurityPass6Test extends TestCase
{
    use RefreshDatabase;

    public function test_unapproved_student_cannot_obtain_an_api_token(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_approved' => false,
            'password' => Hash::make('StrongPass123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertForbidden()->assertJsonPath('message', 'This account is inactive or awaiting administrative approval.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_approved_student_can_obtain_an_api_token(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_approved' => true,
            'password' => Hash::make('StrongPass123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ])->assertOk()->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_public_api_registration_rejects_privileged_role_assignment(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Privilege Escalation Attempt',
            'email' => 'role-escalation@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'role' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'role-escalation@example.test']);
    }

    public function test_api_login_failure_and_success_logs_audit_record(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // Failed API login attempt
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ])->assertStatus(422);

        $this->assertDatabaseHas('school_admin_audits', [
            'action' => 'api_login_failed',
            'user_id' => $user->id,
        ]);

        // Successful API login attempt
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'CorrectPassword123!',
        ])->assertOk();

        $this->assertDatabaseHas('school_admin_audits', [
            'action' => 'api_login_success',
            'user_id' => $user->id,
        ]);
    }

    public function test_web_login_failure_and_success_logs_audit_records(): void
    {
        AcademySetting::set('onboarding_completed', 'true');

        $user = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'password' => Hash::make('AdminPass123!'),
        ]);

        // Failed Web login attempt
        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'WrongPass!',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseHas('school_admin_audits', [
            'action' => 'login_failed',
            'user_id' => $user->id,
        ]);

        // Successful Web login attempt
        $successResponse = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'AdminPass123!',
        ]);

        $successResponse->assertRedirect();
        $this->assertDatabaseHas('school_admin_audits', [
            'action' => 'login_success',
            'user_id' => $user->id,
        ]);

        // Logout audit
        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));
        $this->assertDatabaseHas('school_admin_audits', [
            'action' => 'logout',
            'user_id' => $user->id,
        ]);
    }
}
