<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CaptchaService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function showForgotPassword(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given institutional email.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'captcha' => 'nullable|string',
        ]);

        $throttleKey = 'forgot-password|'.Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many reset link requests. Please wait {$seconds} seconds before trying again.",
            ])->onlyInput('email');
        }

        // Verify security captcha
        if (! CaptchaService::verify($request->input('captcha'), $request->input('captcha_token'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'captcha' => 'Invalid or expired security verification code. Please enter the fresh code.',
            ])->onlyInput('email');
        }

        // Send reset link using Laravel's Password broker
        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        RateLimiter::hit($throttleKey, 60);

        if ($status === Password::RESET_LINK_SENT) {
            $user = User::where('email', $request->input('email'))->first();
            $directToken = null;
            if ($user && app()->environment('local', 'testing')) {
                // For local developer convenience without active SMTP server, provide a quick link
                $rawToken = Password::broker()->createToken($user);
                $directToken = route('password.reset', ['token' => $rawToken, 'email' => $user->email]);
            }

            return back()
                ->with('status', __($status))
                ->with('direct_reset_url', $directToken);
        }

        return back()->withErrors(['email' => __($status)])->onlyInput('email');
    }

    /**
     * Display the password reset form for a given token.
     */
    public function showResetPassword(Request $request, ?string $token = null): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.reset-password', [
            'token' => $token ?? $request->route('token'),
            'email' => $request->query('email', old('email', '')),
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'captcha' => 'nullable|string',
        ]);

        $resetThrottleKey = 'password-reset|'.Str::lower($request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($resetThrottleKey, 5)) {
            $seconds = RateLimiter::availableIn($resetThrottleKey);

            return back()->withErrors([
                'email' => "Too many password reset attempts. Please wait {$seconds} seconds before trying again.",
            ])->withInput($request->only('email'));
        }
        RateLimiter::hit($resetThrottleKey, 900);

        // Verify security captcha if provided or required
        if ($request->filled('captcha') && ! CaptchaService::verify($request->input('captcha'), $request->input('captcha_token'))) {
            return back()->withErrors([
                'captcha' => 'Invalid or expired security verification code.',
            ])->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::broker()->reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                // A password reset invalidates every previously issued API credential
                // and the application's single-session marker for this account.
                $user->tokens()->delete();
                Cache::forget('user_active_session_'.$user->id);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            RateLimiter::clear($resetThrottleKey);

            return redirect()->route('login')->with(
                'success',
                'Your password has been successfully reset! You can now log in with your new password.'
            );
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
