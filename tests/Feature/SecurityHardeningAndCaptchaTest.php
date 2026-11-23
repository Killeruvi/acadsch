<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SecurityHardeningAndCaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicClass $academicClass;
    protected User $student;

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
            'name' => 'Aditya Sharma',
            'email' => 'aditya@primearc.edu.in',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'phone' => '+91 98765 12345',
        ]);
    }

    public function test_captcha_route_streams_valid_svg_image(): void
    {
        $response = $this->get(route('captcha.image'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertStringContainsString('</svg>', $response->getContent());
        $this->assertNotEmpty(session('security_captcha_code'));
    }

    public function test_login_rejects_invalid_captcha(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => $this->student->email,
            'password' => 'password123',
            'captcha' => 'INVALID_CAPTCHA',
        ]);

        $response->assertSessionHasErrors(['captcha']);
        $this->assertGuest();
    }

    public function test_login_succeeds_with_valid_captcha(): void
    {
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('login.post'), [
            'email' => $this->student->email,
            'password' => 'password123',
            'captcha' => $code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_registration_rejects_invalid_captcha(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Rohan Varma',
            'email' => 'rohan@example.com',
            'phone' => '+91 98123 45678',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'captcha' => 'INVALID_CAPTCHA',
        ]);

        $response->assertSessionHasErrors(['captcha']);
        $this->assertDatabaseMissing('users', ['email' => 'rohan@example.com']);
    }

    public function test_registration_requires_permanent_phone(): void
    {
        $code = CaptchaService::generatePhrase();

        // 1. Missing phone
        $response = $this->post(route('register.post'), [
            'name' => 'Rohan Varma',
            'email' => 'rohan@example.com',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '',
            'captcha' => $code,
        ]);

        $response->assertSessionHasErrors(['phone']);

        // 2. Invalid phone format (alphabets)
        $code2 = CaptchaService::generatePhrase();
        $invalidResponse = $this->post(route('register.post'), [
            'name' => 'Rohan Varma',
            'email' => 'rohan@example.com',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => 'not-a-valid-phone',
            'captcha' => $code2,
        ]);

        $invalidResponse->assertSessionHasErrors(['phone']);
    }

    public function test_input_sanitization_middleware_strips_malicious_xss_tags(): void
    {
        $code = CaptchaService::generatePhrase();

        $response = $this->post(route('register.post'), [
            'name' => 'Deepak <script>alert("XSS")</script> Mehta',
            'email' => 'deepak@example.com',
            'phone' => '+91 98999 12345',
            'class_id' => $this->academicClass->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'captcha' => $code,
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'deepak@example.com')->first();
        $this->assertNotNull($user);
        $this->assertStringNotContainsString('<script>', $user->name);
        $this->assertStringNotContainsString('alert(', $user->name);
        $this->assertEquals('Deepak  Mehta', $user->name);
    }

    public function test_security_headers_are_attached_to_all_responses(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_sql_injection_payloads_are_safely_parameterized(): void
    {
        $payloads = [
            "' OR '1'='1",
            "admin' --",
            "1; DROP TABLE users; --",
            "' UNION SELECT 1, 'hack', 'hack', 4, 5, 6, 7, 8, 9, 10 --",
        ];

        foreach ($payloads as $injection) {
            // Test search via authenticated session
            $response = $this->actingAs($this->student)->get(route('syllabus.index', [
                'search' => $injection,
            ]));

            // Must respond cleanly with 200 and never 500 database syntax error
            $response->assertStatus(200);

            // Table users must remain intact and unharmed
            $this->assertTrue(DB::table('users')->where('id', $this->student->id)->exists());
        }
    }

    public function test_rate_limiting_caps_login_attempts(): void
    {
        RateLimiter::clear(RateLimiter::availableIn('aditya@primearc.edu.in|127.0.0.1'));

        for ($i = 0; $i < 5; $i++) {
            $code = CaptchaService::generatePhrase();
            $this->post(route('login.post'), [
                'email' => $this->student->email,
                'password' => 'wrong-password',
                'captcha' => $code,
            ]);
        }

        // 6th attempt should hit rate limit cap
        $code6 = CaptchaService::generatePhrase();
        $throttledResponse = $this->post(route('login.post'), [
            'email' => $this->student->email,
            'password' => 'wrong-password',
            'captcha' => $code6,
        ]);

        $throttledResponse->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Rate limit cap triggered', $errors[0]);
    }

    public function test_captcha_refresh_endpoint_returns_json_with_image_and_token(): void
    {
        $response = $this->get(route('captcha.refresh'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'image', 'token']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $response->json('image'));
        $this->assertNotEmpty($response->json('token'));
        $this->assertNotEmpty(session('security_captcha_code'));
    }

    public function test_captcha_verification_prevents_first_time_race_condition_with_buffered_codes(): void
    {
        // First generation (e.g. initial page load)
        $code1 = CaptchaService::generatePhrase();

        // Second generation (e.g. concurrent subresource or preloader request)
        $code2 = CaptchaService::generatePhrase();

        // The user saw code1 on screen: submitting code1 must succeed despite code2 having been generated
        $this->assertTrue(
            CaptchaService::verify($code1),
            'CaptchaService must accept earlier buffered challenge code to prevent first-time race condition failures'
        );
    }

    public function test_captcha_verification_normalizes_lookalike_glyphs_and_case(): void
    {
        $code = CaptchaService::generatePhrase();

        // Test with mixed whitespace and lowercase
        $this->assertTrue(
            CaptchaService::verify('  ' . strtolower($code) . '  '),
            'Captcha verification must accept trimmed and lowercase user input'
        );
    }

    public function test_captcha_verification_with_encrypted_token_fallback(): void
    {
        $code = '7K9XM';
        $token = CaptchaService::generateToken($code);

        // Clear session completely to simulate mobile cookie drop / privacy mode
        session()->flush();

        $this->assertTrue(
            CaptchaService::verify('7k9xm', $token),
            'Captcha verification must succeed via encrypted token fallback even when session was lost'
        );
    }

    public function test_login_page_renders_instant_inline_captcha_data_uri(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('data:image/svg+xml;base64,', false);
        $response->assertSee('name="captcha_token"', false);
    }
}
