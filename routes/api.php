<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchApiController;
use App\Http\Controllers\Api\BiometricApiController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DppApiController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\FeeController as ApiFeeController;
use App\Http\Controllers\Api\HomeworkApiController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\School\TransportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Public Authentication (Throttled for brute-force protection)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Biometric Device Push & Telemetry API (Turnstiles, Punch clocks, ADMS push)
    Route::post('/biometric/push', [BiometricApiController::class, 'push'])->middleware('throttle:120,1');
    Route::post('/biometric/heartbeat', [BiometricApiController::class, 'heartbeat'])->middleware('throttle:60,1');
    Route::get('/biometric/status', [BiometricApiController::class, 'status'])->middleware('throttle:60,1');

    // Payment Gateway Webhooks & Checkout Initiation
    Route::post('/payments/webhook/{gateway}', [PaymentWebhookController::class, 'handleWebhook'])->name('api.payments.webhook');
    Route::post('/payments/initiate', [PaymentWebhookController::class, 'initiate'])->name('api.payments.initiate');

    // Transport Live GPS Telematics Push
    Route::post('/transport/telemetry', [TransportController::class, 'recordTelemetry'])->name('api.transport.telemetry');

    // Authenticated API Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth session
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Academic Cohorts & Class Sections
        Route::get('/batches', [BatchApiController::class, 'index']);
        Route::get('/batches/{id}', [BatchApiController::class, 'show']);

        // Daily Practice Problems (DPP) & Mock Tests
        Route::get('/dpps', [DppApiController::class, 'index']);
        Route::get('/dpps/{id}', [DppApiController::class, 'show']);
        Route::post('/dpps/{id}/submit', [DppApiController::class, 'submit']);

        // Daily Homework
        Route::get('/homework', [HomeworkApiController::class, 'index']);
        Route::get('/homework/{id}', [HomeworkApiController::class, 'show']);
        Route::post('/homework/{id}/submit', [HomeworkApiController::class, 'submit']);

        // Attendance System
        Route::get('/attendance', [AttendanceApiController::class, 'index']);

        // Courses & Schedule (read)
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{id}', [CourseController::class, 'show']);
        Route::get('/schedules', [ScheduleController::class, 'index']);

        // Enrollments
        Route::get('/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);

        // Assignments & Submissions
        Route::get('/assignments', [AssignmentController::class, 'index']);
        Route::get('/assignments/{id}', [AssignmentController::class, 'show']);
        Route::post('/assignments/{id}/submissions', [SubmissionController::class, 'store']);

        // Fees
        Route::get('/fees', [ApiFeeController::class, 'index']);
        Route::post('/fees/{id}/pay', [ApiFeeController::class, 'pay']);

        // Analytics
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);

        // Teacher and Admin Operations
        Route::middleware('role:teacher,admin')->group(function () {
            Route::post('/attendance', [AttendanceApiController::class, 'store']);
            Route::post('/courses', [CourseController::class, 'store']);
            Route::put('/courses/{id}', [CourseController::class, 'update']);
            Route::post('/schedules', [ScheduleController::class, 'store']);
            Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);
            Route::post('/assignments', [AssignmentController::class, 'store']);
            Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
            Route::get('/assignments/{id}/submissions', [SubmissionController::class, 'index']);
            Route::put('/submissions/{id}/grade', [SubmissionController::class, 'grade']);
        });

        // Admin-exclusive Operations
        Route::middleware('role:admin')->group(function () {
            Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
            Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/enrollments/{id}', [EnrollmentController::class, 'update']);
            Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);
        });
    });
});
