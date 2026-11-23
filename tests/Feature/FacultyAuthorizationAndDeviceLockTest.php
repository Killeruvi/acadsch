<?php

namespace Tests\Feature;

use App\Models\FacultyAccessCode;
use App\Models\FacultyDeviceLock;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FacultyAuthorizationAndDeviceLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Principal Dr. Rajeshwar Sharma',
            'email' => 'principal@primearc.edu.in',
            'phone' => '+91 98101 23456',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Vikramaditya Sen',
            'email' => 'dr.sen@primearc.edu.in',
            'phone' => '+91 98112 34567',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Aarav Patel',
            'email' => 'aarav@student.primearc.edu.in',
            'phone' => '+91 98223 45678',
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_access_faculty_codes_panel_and_generate_code(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.faculty-codes.index'));

        $response->assertStatus(200);
        $response->assertSee('Faculty Authorization Codes &amp; Teacher Lock Control', false);
        $response->assertSee('Generate Manual Authorization Code');

        // Admin generates code
        $postResponse = $this->actingAs($this->admin)->post(route('admin.faculty-codes.store'), [
            'notes' => 'For Physics Department Head',
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('faculty_access_codes', [
            'generated_by' => $this->admin->id,
            'notes' => 'For Physics Department Head',
            'is_used' => false,
        ]);
    }

    public function test_non_admin_cannot_access_faculty_codes_panel(): void
    {
        // Student blocked
        $studentResponse = $this->actingAs($this->student)->get(route('admin.faculty-codes.index'));
        $studentResponse->assertStatus(403);

        // Teacher blocked
        $teacherResponse = $this->actingAs($this->teacher)->get(route('admin.faculty-codes.index'));
        $teacherResponse->assertStatus(403);
    }

    public function test_teacher_registration_fails_without_faculty_code(): void
    {
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('register.post'), [
            'name' => 'Dr. Rakesh Nair',
            'email' => 'rakesh.nair@primearc.edu.in',
            'role' => 'teacher',
            'phone' => '+91 98999 55555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'faculty_code' => '', // Missing code
            'captcha' => $code,
        ]);

        $response->assertSessionHasErrors(['faculty_code']);
        $this->assertDatabaseMissing('users', ['email' => 'rakesh.nair@primearc.edu.in']);
    }

    public function test_teacher_registration_fails_with_invalid_or_used_code(): void
    {
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('register.post'), [
            'name' => 'Dr. Rakesh Nair',
            'email' => 'rakesh.nair@primearc.edu.in',
            'role' => 'teacher',
            'phone' => '+91 98999 55555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'faculty_code' => 'FAC-INVALID-CODE',
            'captcha' => $code,
        ]);

        $response->assertSessionHasErrors(['faculty_code']);
        $this->assertDatabaseMissing('users', ['email' => 'rakesh.nair@primearc.edu.in']);
    }

    public function test_teacher_registration_succeeds_with_valid_admin_code(): void
    {
        $facultyCode = FacultyAccessCode::createUniqueCode($this->admin->id, 'Onboarding Prof. Sen');
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('register.post'), [
            'name' => 'Dr. Rakesh Nair',
            'email' => 'rakesh.nair@primearc.edu.in',
            'role' => 'teacher',
            'phone' => '+91 98999 55555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'faculty_code' => $facultyCode->code,
            'captcha' => $code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'rakesh.nair@primearc.edu.in')->first();
        $this->assertNotNull($user);
        $this->assertEquals('teacher', $user->role);
        $this->assertTrue($user->is_approved);
        $this->assertEquals($this->admin->id, $user->approved_by);

        // Code marked used
        $facultyCode->refresh();
        $this->assertTrue($facultyCode->is_used);
        $this->assertEquals($user->id, $facultyCode->used_by);

        // Device lock recorded
        $this->assertDatabaseHas('faculty_device_locks', [
            'user_id' => $user->id,
            'faculty_code_id' => $facultyCode->id,
        ]);

        // Permanent device cookie returned
        $response->assertCookie('pa_device_id');
    }

    public function test_only_one_teacher_registration_is_permitted_from_one_device(): void
    {
        $deviceId = (string) Str::uuid();

        // 1. First teacher registers from this device
        $facultyCode1 = FacultyAccessCode::createUniqueCode($this->admin->id, 'Teacher 1');
        $code1 = CaptchaService::generatePhrase();

        $response1 = $this->withCookies(['pa_device_id' => $deviceId])
            ->post(route('register.post'), [
                'name' => 'Dr. First Teacher',
                'email' => 'first.teacher@primearc.edu.in',
                'role' => 'teacher',
                'phone' => '+91 98999 11111',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'faculty_code' => $facultyCode1->code,
                'captcha' => $code1,
            ]);

        $response1->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'first.teacher@primearc.edu.in']);

        auth()->logout();

        // 2. Second teacher attempts to register from the SAME device
        $facultyCode2 = FacultyAccessCode::createUniqueCode($this->admin->id, 'Teacher 2');
        $code2 = CaptchaService::generatePhrase();

        $response2 = $this->withCookies(['pa_device_id' => $deviceId])
            ->post(route('register.post'), [
                'name' => 'Dr. Second Teacher',
                'email' => 'second.teacher@primearc.edu.in',
                'role' => 'teacher',
                'phone' => '+91 98999 22222',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'faculty_code' => $facultyCode2->code,
                'captcha' => $code2,
            ]);

        $response2->assertSessionHasErrors(['faculty_code']);
        $errors = session('errors')->get('faculty_code');
        $this->assertStringContainsString('already been submitted from this device', $errors[0]);

        $this->assertDatabaseMissing('users', ['email' => 'second.teacher@primearc.edu.in']);
    }

    public function test_locked_teacher_cannot_log_in(): void
    {
        $this->teacher->update(['is_approved' => false]);
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('login.post'), [
            'email' => $this->teacher->email,
            'password' => 'password',
            'captcha' => $code,
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('locked pending administrative approval', $errors[0]);
        $this->assertGuest();
    }

    public function test_admin_can_lock_and_unlock_teacher(): void
    {
        $this->assertTrue($this->teacher->is_approved);

        // Lock
        $lockResponse = $this->actingAs($this->admin)->post(route('admin.faculty.toggle-lock', $this->teacher->id));
        $lockResponse->assertRedirect();
        $this->teacher->refresh();
        $this->assertFalse($this->teacher->is_approved);

        // Unlock
        $unlockResponse = $this->actingAs($this->admin)->post(route('admin.faculty.toggle-lock', $this->teacher->id));
        $unlockResponse->assertRedirect();
        $this->teacher->refresh();
        $this->assertTrue($this->teacher->is_approved);
    }

    public function test_admin_can_unlock_device(): void
    {
        $lock = FacultyDeviceLock::create([
            'device_id' => 'test-device-uuid',
            'device_fingerprint' => 'test-fingerprint',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertDatabaseHas('faculty_device_locks', ['id' => $lock->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.faculty.unlock-device', $lock->id));
        $response->assertRedirect();

        $this->assertDatabaseMissing('faculty_device_locks', ['id' => $lock->id]);
    }
}
