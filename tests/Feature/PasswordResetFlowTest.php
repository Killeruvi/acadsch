<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Account Recovery');
        $response->assertSee('Send Reset Link');
    }

    public function test_authenticated_user_is_redirected_away_from_forgot_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.request'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'student.reset@primearc.edu.in',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'student.reset@primearc.edu.in',
            'captcha' => 'TEST_BYPASS',
        ]);

        $response->assertSessionHas('status');
        $response->assertRedirect();
    }

    public function test_reset_password_screen_can_be_rendered_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'student.reset2@primearc.edu.in',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Create New Password');
        $response->assertSee($user->email);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'student.reset3@primearc.edu.in',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'student.reset4@primearc.edu.in',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token-here',
            'email' => $user->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }
}
