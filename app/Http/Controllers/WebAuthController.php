<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\Batch;
use App\Models\ClassEnrollment;
use App\Models\FacultyAccessCode;
use App\Models\FacultyDeviceLock;
use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        $isOnboarded = \App\Models\AcademySetting::get('onboarding_completed') === 'true';
        $adminCount = User::where('role', 'admin')->count();

        // If system is unconfigured and has zero admins, send straight to onboarding
        if (!$isOnboarded && $adminCount === 0) {
            return redirect()->route('school.onboarding.index');
        }

        if (Auth::check()) {
            if (!$isOnboarded && Auth::user()->isAdmin()) {
                return redirect()->route('school.onboarding.index');
            }
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
            'captcha' => 'nullable|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $targetUser = User::where('email', $credentials['email'])->first();
            $this->logAuthAudit($targetUser?->id, 'login_throttled', "Rate limit cap triggered for {$credentials['email']}", ['ip' => $request->ip(), 'seconds' => $seconds], $request->ip());
            return back()->withErrors([
                'email' => "Too many authentication attempts. Rate limit cap triggered. Please wait {$seconds} seconds before trying again.",
            ])->onlyInput('email');
        }

        // Verify institutional security captcha
        if (!CaptchaService::verify($request->input('captcha'), $request->input('captcha_token'))) {
            RateLimiter::hit($throttleKey, 60);
            $targetUser = User::where('email', $credentials['email'])->first();
            $this->logAuthAudit($targetUser?->id, 'login_captcha_failed', "Security captcha verification failed for {$credentials['email']}", ['ip' => $request->ip()], $request->ip());
            return back()->withErrors([
                'captcha' => 'Invalid or expired security captcha. Please enter the fresh code.',
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            $user = Auth::user();

            // Institutional access lock: faculty and students require an active approval state.
            if (($user->isTeacher() || $user->isStudent()) && !$user->is_approved) {
                $this->logAuthAudit($user->id, 'login_unapproved_blocked', "Unapproved {$user->role} account attempted login: {$user->email}", ['role' => $user->role], $request->ip());
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = $user->isTeacher()
                    ? 'Your faculty account is locked pending administrative approval. Please contact the administrator.'
                    : 'This student account is inactive or awaiting approval. Contact the school administration.';

                return back()->withErrors(['email' => $message])->onlyInput('email');
            }

            $isOnboarded = \App\Models\AcademySetting::get('onboarding_completed') === 'true';

            // Initial System Setup enforcement
            if (!$isOnboarded) {
                if (!$user->isAdmin()) {
                    $this->logAuthAudit($user->id, 'login_setup_blocked', "Non-admin account blocked during institutional setup: {$user->email}", ['role' => $user->role], $request->ip());
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'The School Management System is currently undergoing initial setup. Please contact your campus administrator.',
                    ])->onlyInput('email');
                }

                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();
                \Illuminate\Support\Facades\Cache::put('user_active_session_' . $user->id, session()->getId(), now()->addDay());
                $user->recordDailyActivity('login');
                $this->logAuthAudit($user->id, 'login_success', "Administrator {$user->name} authenticated into setup wizard", ['role' => $user->role], $request->ip());

                return redirect()->route('school.onboarding.index')
                    ->with('info', 'Institutional setup is currently in progress. Resuming the onboarding wizard.');
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            \Illuminate\Support\Facades\Cache::put('user_active_session_' . $user->id, session()->getId(), now()->addDay());
            $user->recordDailyActivity('login');
            $this->logAuthAudit($user->id, 'login_success', "User {$user->name} ({$user->role}) authenticated successfully", ['role' => $user->role, 'email' => $user->email], $request->ip());

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        RateLimiter::hit($throttleKey, 60);
        $failedUser = User::where('email', $credentials['email'])->first();
        $this->logAuthAudit($failedUser?->id, 'login_failed', "Failed login attempt with invalid credentials for {$credentials['email']}", ['ip' => $request->ip(), 'user_agent' => $request->userAgent()], $request->ip());

        return back()->withErrors([
            'email' => 'The provided credentials do not match our institutional records.',
        ])->onlyInput('email');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $classes = AcademicClass::orderBy('grade_level')->get();
        return view('auth.register', compact('classes'));
    }

    public function register(Request $request): RedirectResponse
    {
        $throttleKey = 'register|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many registration attempts. Rate limit cap triggered. Please wait {$seconds} seconds.",
            ])->onlyInput('email');
        }

        // Verify institutional security captcha
        if (!CaptchaService::verify($request->input('captcha'), $request->input('captcha_token'))) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'captcha' => 'Invalid or expired security captcha. Please enter the fresh code.',
            ])->withInput($request->except(['password', 'password_confirmation', 'captcha']));
        }

        $role = $request->input('role', 'student');
        $hasClasses = AcademicClass::exists();

        // Security check for Teacher onboarding: Device limit & Admin authorization code
        $accessCode = null;
        $deviceId = null;
        $deviceFingerprint = null;

        if ($role === 'teacher') {
            $deviceId = $request->cookie('pa_device_id') ?? $request->header('X-Device-Id');
            $deviceFingerprint = hash('sha256', $request->ip() . '|' . ($request->userAgent() ?? ''));

            // 0. Enforce single teacher registration per physical device
            if (!empty($deviceId) && FacultyDeviceLock::isDeviceLocked($deviceId, null)) {
                RateLimiter::hit($throttleKey, 60);
                return back()->withErrors([
                    'faculty_code' => 'A teacher registration has already been submitted from this device. Institutional security allows only 1 faculty registration per hardware device. Please contact administration.',
                ])->withInput($request->except(['password', 'password_confirmation', 'captcha']));
            }

            if (empty($deviceId)) {
                $deviceId = (string) Str::uuid();
            }

            // 1. Special code manually generated by admin is strictly required
            $rawCode = strtoupper(trim((string)$request->input('faculty_code')));
            if (empty($rawCode)) {
                RateLimiter::hit($throttleKey, 60);
                return back()->withErrors([
                    'faculty_code' => 'The special faculty authorization code is mandatory for teacher registration.',
                ])->withInput($request->except(['password', 'password_confirmation', 'captcha']));
            }

            $accessCode = FacultyAccessCode::where('code', $rawCode)
                ->where('is_used', false)
                ->first();

            if (!$accessCode) {
                RateLimiter::hit($throttleKey, 60);
                return back()->withErrors([
                    'faculty_code' => 'The provided faculty authorization code is invalid, expired, or has already been used. Please contact the administrator.',
                ])->withInput($request->except(['password', 'password_confirmation', 'captcha']));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:student,teacher',
            'class_id' => ($role === 'student' && $hasClasses ? 'required' : 'nullable') . '|exists:academic_classes,id',
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'faculty_code' => $role === 'teacher' ? 'required|string|max:50' : 'nullable|string',
            'captcha' => 'nullable|string',
        ], [
            'class_id.required' => 'Please select your academic class to complete registration.',
            'class_id.exists' => 'The selected academic class is invalid.',
            'phone.required' => 'A permanent phone number is mandatory for institutional identity verification.',
            'phone.regex' => 'Please provide a valid permanent phone number format (e.g. +91 98765 43210).',
            'faculty_code.required' => 'The special faculty authorization code is required to complete teacher registration.',
        ]);

        RateLimiter::hit($throttleKey, 60);

        $role = $validated['role'] ?? 'student';
        $classId = !empty($validated['class_id']) ? (int)$validated['class_id'] : null;

        $batchId = null;
        $classAcademicYear = $classId ? AcademicClass::query()->whereKey($classId)->value('academic_year') : null;
        if ($classId) {
            $batch = Batch::where('class_id', $classId)->where('is_active', true)->first();
            if (!$batch) {
                $academicClass = AcademicClass::find($classId);
                if ($academicClass) {
                    $batch = Batch::create([
                        'class_id' => $academicClass->id,
                        'name' => $academicClass->name . ' - Morning Batch',
                        'code' => 'B-' . $academicClass->grade_level . '-MORN',
                        'shift' => 'morning',
                        'timing_slot' => '08:00 AM - 01:00 PM',
                        'academic_year' => $academicClass->academic_year,
                        'is_active' => true,
                    ]);
                }
            }
            if ($batch) {
                $batchId = $batch->id;
            }
        }

        $admissionNumber = $role === 'student' ? User::generateNextAdmissionNumber() : null;
        $prefix = $role === 'teacher' ? 'PA-FAC-' : 'PA-STD-';
        $studentIdNum = $admissionNumber ?: ($prefix . date('Y') . '-' . str_pad((string)(User::count() + 1), 4, '0', STR_PAD_LEFT));

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'phone' => $validated['phone'],
            'batch_id' => $batchId,
            'current_class_id' => $classId,
            'admission_number' => $admissionNumber,
            'student_id_number' => $studentIdNum,
            'roll_number' => ($classId && $role === 'student') ? ClassEnrollment::generateNextRollNumber($classId, null, $classAcademicYear) : null,
            'xp_points' => 100,
            'academic_tier' => $role === 'teacher' ? 'Faculty Associate' : 'Junior Scholar',
            'streak_count' => 1,
            'last_active_date' => now()->toDateString(),
            'card_valid_until' => now()->addYears(2),
            'is_approved' => true,
            'approved_by' => $accessCode ? $accessCode->generated_by : null,
            'approved_at' => $accessCode ? now() : null,
        ]);

        if ($classId && $role === 'student') {
            ClassEnrollment::firstOrCreate(
                [
                    'class_id' => $classId,
                    'student_id' => $user->id,
                    'academic_year' => $classAcademicYear,
                ],
                [
                    'roll_number' => $user->roll_number ?: ClassEnrollment::generateNextRollNumber($classId, null, $classAcademicYear),
                    'admission_number' => $admissionNumber,
                    'status' => 'active',
                ]
            );
        }

        // Finalize teacher authorization and lock the device
        if ($role === 'teacher' && $accessCode) {
            $accessCode->update([
                'is_used' => true,
                'used_by' => $user->id,
                'used_at' => now(),
                'device_fingerprint' => $deviceFingerprint,
            ]);

            if (empty($deviceId)) {
                $deviceId = (string) Str::uuid();
            }

            FacultyDeviceLock::create([
                'device_id' => $deviceId,
                'device_fingerprint' => $deviceFingerprint,
                'ip_address' => $request->ip(),
                'user_id' => $user->id,
                'faculty_code_id' => $accessCode->id,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        \Illuminate\Support\Facades\Cache::put('user_active_session_' . $user->id, session()->getId(), now()->addDay());

        $response = redirect()->route('dashboard')
            ->with('success', $role === 'teacher'
                ? "Faculty registration verified and activated with administrator code [{$accessCode->code}]!"
                : 'Your institutional account has been successfully verified and activated! (+100 Scholar XP)');

        if ($role === 'teacher' && !empty($deviceId)) {
            $response->withCookie(cookie()->forever('pa_device_id', $deviceId));
        }

        return $response;
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->logAuthAudit($user->id, 'logout', "User {$user->name} ({$user->role}) logged out securely", ['email' => $user->email, 'role' => $user->role], $request->ip());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been securely signed out.');
    }

    /**
     * Persist security and access audits to school_admin_audits and Laravel system logs.
     */
    private function logAuthAudit(?int $userId, string $action, string $description, ?array $newValues = null, ?string $ip = null): void
    {
        try {
            DB::table('school_admin_audits')->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'old_values' => null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $ip,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Auth audit log write failed: ' . $e->getMessage());
        }
    }
}
