<?php

use App\Http\Controllers\CertificateBadgeController;
use App\Http\Controllers\MotivationController;
use App\Http\Controllers\School\BulkDataHubController;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SchoolBrandingService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public Educational Ecosystem Information Pages
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/admissions', function () {
    return view('pages.admissions');
})->name('admissions');

Route::get('/privacy-policy', function () {
    return view('pages.privacy-policy');
})->name('privacy-policy');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::post('/contact', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email|max:120',
        'phone' => 'required|string|max:20',
        'inquiry_type' => 'required|string|in:admissions,curriculum,academics,general',
        'message' => 'required|string|max:2000',
    ]);

    // Send Telegram alert to admin channel
    try {
        TelegramService::sendAlert(
            'New Web Inquiry: '.strtoupper($data['inquiry_type']),
            '<b>From:</b> '.htmlspecialchars($data['name']).' ('.htmlspecialchars($data['email']).' / '.htmlspecialchars($data['phone']).")\n<b>Message:</b>\n".htmlspecialchars($data['message'])
        );
    } catch (Throwable $e) {
    }

    // Dispatch in-app notification to administrators
    $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
    if (! empty($adminIds)) {
        NotificationService::notifyUsers(
            $adminIds,
            'inquiry',
            'New '.ucfirst($data['inquiry_type'])." inquiry from {$data['name']}",
            $data['message'],
            null,
            '📩'
        );
    }

    $school = SchoolBrandingService::get();

    return back()->with('success', 'Thank you for reaching out to '.($school['name'] ?? 'PrimeOS Academy').'. Our Academic Relations team will connect with you within 24 hours.');
})->name('contact.submit');

// PWA Offline Fallback
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Public Verification Endpoints
Route::get('/certificates/verify/{hash}', [CertificateBadgeController::class, 'verifyCertificate'])
    ->middleware('throttle:30,1')->name('certificates.verify');

Route::get('/motivation/spark', [MotivationController::class, 'spark'])->name('motivation.spark');

// Public Template CSV Downloads for Setup & Onboarding
Route::get('/school/bulk/sample/{type}', [BulkDataHubController::class, 'sampleCsv'])->name('school.bulk.sample');
