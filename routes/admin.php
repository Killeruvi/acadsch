<?php

use App\Http\Controllers\AcademyNoticeController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacultyCodeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\School\AdministrationController;
use App\Http\Controllers\School\BulkDataHubController;
use App\Http\Controllers\School\SchoolComplianceDocumentController;
use App\Http\Controllers\School\SchoolHolidayController;
use App\Http\Controllers\School\SchoolOnboardingController;
use App\Http\Controllers\School\SchoolReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebAnalyticsController;
use Illuminate\Support\Facades\Route;

// Institutional School Onboarding & Installation Wizard
Route::prefix('school/onboarding')->name('school.onboarding.')->group(function () {
    Route::get('/', [SchoolOnboardingController::class, 'index'])->name('index');
    Route::post('/create-admin', [SchoolOnboardingController::class, 'createAdmin'])->middleware('throttle:3,10')->name('create-admin');
    Route::post('/reset-database', [SchoolOnboardingController::class, 'resetDatabase'])->name('reset-database');
    Route::post('/profile', [SchoolOnboardingController::class, 'saveProfile'])->name('save-profile');
    Route::post('/k12-presets', [SchoolOnboardingController::class, 'applyK12Presets'])->name('k12-presets');
    Route::post('/skip/{step}', [SchoolOnboardingController::class, 'skipStep'])->name('skip-step')->whereNumber('step');
    Route::post('/finish', [SchoolOnboardingController::class, 'finish'])->name('finish');
});

// Authenticated Administrative Core
Route::middleware(['auth', 'student.account.active'])->group(function () {
    // Dashboard & Profile
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::match(['post', 'put'], '/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Academy Circulars & Notices
    Route::get('/notices', [AcademyNoticeController::class, 'index'])->name('notices.index');
    Route::post('/notices/{id}/read', [AcademyNoticeController::class, 'markRead'])->name('notices.read')->whereNumber('id');

    // In-App Notification System
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/delete-read', [NotificationController::class, 'deleteRead'])->name('notifications.delete-read');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('notifications.bulk-delete');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy')->whereNumber('id');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read')->whereNumber('id');
    Route::get('/api/notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');

    // Faculty & Admin: Notices & Calendar Management
    Route::middleware('role:teacher,admin')->group(function () {
        Route::post('/notices', [AcademyNoticeController::class, 'store'])->name('notices.store');
        Route::match(['put', 'patch', 'post'], '/notices/{id}', [AcademyNoticeController::class, 'update'])->name('notices.update')->whereNumber('id');
        Route::delete('/notices/{id}', [AcademyNoticeController::class, 'destroy'])->name('notices.destroy')->whereNumber('id');
        Route::post('/notices/{id}/approve', [AcademyNoticeController::class, 'approve'])->name('notices.approve')->whereNumber('id');
        Route::post('/notices/{id}/reject', [AcademyNoticeController::class, 'reject'])->name('notices.reject')->whereNumber('id');
        Route::get('/notices/{id}/read-stats', [AcademyNoticeController::class, 'readStats'])->name('notices.read-stats')->whereNumber('id');

        Route::post('/school/holidays', [SchoolHolidayController::class, 'store'])->name('school.holidays.store');
        Route::match(['put', 'patch', 'post'], '/school/holidays/{id}', [SchoolHolidayController::class, 'update'])->name('school.holidays.update')->whereNumber('id');
        Route::delete('/school/holidays/{id}', [SchoolHolidayController::class, 'destroy'])->name('school.holidays.destroy')->whereNumber('id');

        Route::get('/analytics', [WebAnalyticsController::class, 'index'])->name('analytics.index');
    });

    // Bulk Data Import & Export Hub
    Route::middleware('role:admin,teacher')->prefix('school/bulk')->name('school.bulk.')->group(function () {
        Route::get('/', [BulkDataHubController::class, 'index'])->name('index');
        Route::post('/students/import', [BulkDataHubController::class, 'importStudents'])->name('students.import');
        Route::get('/students/export', [BulkDataHubController::class, 'exportStudents'])->name('students.export');
        Route::post('/staff/import', [BulkDataHubController::class, 'importStaff'])->name('staff.import');
        Route::get('/staff/export', [BulkDataHubController::class, 'exportStaff'])->name('staff.export');
        Route::post('/classes-subjects/import', [BulkDataHubController::class, 'importClassesAndSubjects'])->name('classes-subjects.import');
        Route::get('/classes-subjects/export', [BulkDataHubController::class, 'exportClassesAndSubjects'])->name('classes-subjects.export');
        Route::post('/parents/import', [BulkDataHubController::class, 'importParents'])->name('parents.import');
        Route::get('/parents/export', [BulkDataHubController::class, 'exportParents'])->name('parents.export');
        Route::get('/credentials/export', [BulkDataHubController::class, 'exportCredentials'])->name('credentials.export');
        Route::post('/credentials/email-bulk', [BulkDataHubController::class, 'sendBulkCredentials'])->name('credentials.email');
    });

    // Institutional Reports Center
    Route::middleware('role:admin,teacher,cashier')->prefix('school/reports')->name('school.reports.')->group(function () {
        Route::get('/', [SchoolReportController::class, 'index'])->name('index');
        Route::get('/export', [SchoolReportController::class, 'export'])->name('export');
        Route::get('/print', [SchoolReportController::class, 'print'])->name('print');
    });

    // Administrator Exclusive Operations
    Route::middleware('role:admin')->group(function () {
        Route::get('/school/administration', [AdministrationController::class, 'index'])->name('school.administration.index');
        Route::get('/school/administration/audit.csv', [AdministrationController::class, 'exportAudit'])->name('school.administration.audit.export');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/qr-image', [SettingsController::class, 'uploadQrImage'])->name('settings.qr-image');

        Route::post('/school-compliance-documents', [SchoolComplianceDocumentController::class, 'store'])->name('school-compliance-documents.store');
        Route::put('/school-compliance-documents/{document}', [SchoolComplianceDocumentController::class, 'update'])->name('school-compliance-documents.update');
        Route::get('/school-compliance-documents/{document}/download', [SchoolComplianceDocumentController::class, 'download'])->name('school-compliance-documents.download');
        Route::delete('/school-compliance-documents/{document}', [SchoolComplianceDocumentController::class, 'destroy'])->name('school-compliance-documents.destroy');

        // Themes & Appearance Hub (Infix System)
        Route::get('/appearance', [AppearanceController::class, 'index'])->name('appearance.index');
        Route::post('/appearance/activate', [AppearanceController::class, 'activate'])->name('appearance.activate');
        Route::post('/appearance/customize', [AppearanceController::class, 'customize'])->name('appearance.customize');
        Route::post('/appearance/fonts', [AppearanceController::class, 'updateFonts'])->name('appearance.fonts');
        Route::post('/appearance/preloader', [AppearanceController::class, 'updatePreloader'])->name('appearance.preloader');

        // Faculty Authorization Codes & Device Locks
        Route::get('/admin/faculty-codes', [FacultyCodeController::class, 'index'])->name('admin.faculty-codes.index');
        Route::post('/admin/faculty-codes', [FacultyCodeController::class, 'store'])->name('admin.faculty-codes.store');
        Route::delete('/admin/faculty-codes/{code}', [FacultyCodeController::class, 'destroy'])->name('admin.faculty-codes.destroy');
        Route::post('/admin/faculty-codes/toggle-lock/{user}', [FacultyCodeController::class, 'toggleTeacherLock'])->name('admin.faculty.toggle-lock');
        Route::delete('/admin/faculty-codes/device-locks/{lock}', [FacultyCodeController::class, 'unlockDevice'])->name('admin.faculty.unlock-device');
    });
});
