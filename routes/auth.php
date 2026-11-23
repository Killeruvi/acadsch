<?php

use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

// Captcha Routes
Route::get('/captcha', [CaptchaController::class, 'image'])->name('captcha.image');
Route::get('/captcha/refresh', [CaptchaController::class, 'refresh'])->name('captcha.refresh');

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.post');

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

// Logout (Authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});
