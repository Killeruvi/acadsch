<?php

use App\Http\Controllers\AcademyEventController;
use App\Http\Controllers\AcademyNoticeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\CertificateBadgeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DppController;
use App\Http\Controllers\FacultyCodeController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LiveQuizController;
use App\Http\Controllers\MotivationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectCaseStudyController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\VirtualClassController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentDirectoryController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VirtualIdCardController;
use App\Http\Controllers\WebAnalyticsController;
use App\Http\Controllers\WebAssignmentController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebCourseController;
use App\Http\Controllers\WebScheduleController;
use App\Http\Controllers\School\SchoolClassController;
use App\Http\Controllers\School\SchoolSectionController;
use App\Http\Controllers\School\SchoolStreamController;
use App\Http\Controllers\School\SchoolSubjectController;
use App\Http\Controllers\School\SchoolStudentController;
use App\Http\Controllers\School\AdmissionEnquiryController;
use App\Http\Controllers\School\SchoolAttendanceController;
use App\Http\Controllers\School\SchoolExaminationController;
use App\Http\Controllers\School\SchoolTimetableController;
use App\Http\Controllers\School\SchoolFeeController;
use App\Http\Controllers\School\SchoolParentController;
use App\Http\Controllers\School\TeacherAssignmentController;
use App\Http\Controllers\School\BulkDataHubController;
use App\Http\Controllers\School\SchoolOnboardingController;
use App\Http\Controllers\School\AccountManagementController;
use Illuminate\Support\Facades\Route;

// Public Landing Page & Educational Ecosystem Pages
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/admissions', function () {
    return view('pages.admissions');
})->name('admissions');
Route::post('/admissions/enquiry', [AdmissionEnquiryController::class, 'storePublic'])
    ->middleware('throttle:5,10')->name('admissions.enquiry.store');

Route::get('/privacy-policy', function () {
    return view('pages.privacy-policy');
})->name('privacy-policy');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email|max:120',
        'phone' => 'required|string|max:20',
        'inquiry_type' => 'required|string|in:admissions,curriculum,academics,general',
        'message' => 'required|string|max:2000',
    ]);

    // Send Telegram alert to admin channel
    try {
        \App\Services\TelegramService::sendAlert(
            "New Web Inquiry: " . strtoupper($data['inquiry_type']),
            "<b>From:</b> " . htmlspecialchars($data['name']) . " (" . htmlspecialchars($data['email']) . " / " . htmlspecialchars($data['phone']) . ")\n<b>Message:</b>\n" . htmlspecialchars($data['message'])
        );
    } catch (\Throwable $e) {}

    // Dispatch in-app notification to administrators
    $adminIds = \App\Models\User::where('role', 'admin')->pluck('id')->toArray();
    if (!empty($adminIds)) {
        \App\Services\NotificationService::notifyUsers(
            $adminIds,
            'inquiry',
            "New " . ucfirst($data['inquiry_type']) . " inquiry from {$data['name']}",
            $data['message'],
            null,
            '📩'
        );
    }

    $school = \App\Services\SchoolBrandingService::get();
    return back()->with('success', 'Thank you for reaching out to ' . ($school['name'] ?? 'PrimeOS Academy') . '. Our Academic Relations team will connect with you within 24 hours.');
})->name('contact.submit');

// Public Courses & Curriculum Directory
Route::get('/courses', [WebCourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{id}', [WebCourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{id}/handouts/{filename}', [WebCourseController::class, 'downloadHandoutPdf'])->name('courses.handout.pdf');
Route::get('/courses/{id}/lessons/{lessonNumber}/pdf', [WebCourseController::class, 'lessonPdf'])->name('courses.lesson.pdf');

Route::get('/offline', function () {
    return view('offline');
})->name('offline');

Route::get('/certificates/verify/{hash}', [CertificateBadgeController::class, 'verifyCertificate'])->middleware('throttle:30,1')->name('certificates.verify');
Route::get('/verify/id-card/{hash}', [SchoolStudentController::class, 'verifyIdCard'])->where('hash', '[0-9]+\\.[a-fA-F0-9]{64}')->middleware('throttle:30,1')->name('school.students.verify-id');
Route::get('/school/exams/admit-card/verify/{token}', [SchoolExaminationController::class, 'verifyAdmitCard'])->middleware('throttle:60,1')->name('school.exams.admit-card.verify');

Route::get('/motivation/spark', [MotivationController::class, 'spark'])->name('motivation.spark');

Route::get('/captcha', [CaptchaController::class, 'image'])->name('captcha.image');
Route::get('/captcha/refresh', [CaptchaController::class, 'refresh'])->name('captcha.refresh');

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.post');

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

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

// Public Template CSV Downloads for Setup & Onboarding
Route::get('/school/bulk/sample/{type}', [BulkDataHubController::class, 'sampleCsv'])->name('school.bulk.sample');


// Authenticated Web Application
Route::middleware(['auth', 'student.account.active'])->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-batch', [BatchController::class, 'index'])->name('my-batch');
    Route::get('/my-batch/select', [BatchController::class, 'selectClassBatch'])->name('batches.select');
    Route::post('/my-batch/select', [BatchController::class, 'storeSelection'])->name('batches.select.store');
    Route::post('/my-batch/request-change', [BatchController::class, 'requestChange'])->name('batches.request-change');
    Route::get('/my-batch/{batch}', [BatchController::class, 'subjects'])->name('batches.subjects')->whereNumber('batch');
    Route::get('/my-batch/{batch}/subjects/{subject}', [BatchController::class, 'chapters'])->name('batches.chapters')->whereNumber('batch')->whereNumber('subject');
    Route::get('/my-batch/{batch}/subjects/{subject}/chapters/{chapter?}', [BatchController::class, 'learn'])->name('batches.learn')->whereNumber('batch')->whereNumber('subject');

    // Faculty & Admin Batch Management Operations
    Route::middleware('role:teacher,admin')->group(function () {
        Route::post('/my-batch/create', [BatchController::class, 'storeBatch'])->name('batches.create');
        Route::post('/my-batch/requests/{batchChangeRequest}/review', [BatchController::class, 'reviewRequest'])->name('batches.requests.review');
        Route::match(['put', 'patch', 'post'], '/my-batch/{batch}', [BatchController::class, 'updateBatch'])->name('batches.update')->whereNumber('batch');
        Route::delete('/my-batch/{batch}', [BatchController::class, 'destroyBatch'])->name('batches.destroy')->whereNumber('batch');
        Route::post('/my-batch/{batch}/subjects/{subject}/chapters/{chapter}/notes', [BatchController::class, 'uploadNote'])->name('batches.notes.upload')->whereNumber('batch')->whereNumber('subject')->whereNumber('chapter');
        Route::delete('/my-batch/notes/{note}', [BatchController::class, 'deleteNote'])->name('batches.notes.delete');
    });

    // User Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::match(['post', 'put'], '/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Staff employee self-service: all record lookups are scoped to the authenticated staff account.
    Route::middleware('role:teacher,admin')->prefix('staff-self-service')->name('staff.self-service.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'index'])->name('index');
        Route::post('/tax-declarations', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'saveDeclaration'])->name('tax.save');
        Route::post('/tax-declarations/{declaration}/submit', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'submitDeclaration'])->whereNumber('declaration')->name('tax.submit');
        Route::get('/documents/{kind}/{id}', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'downloadDocument'])->whereNumber('id')->name('download');
    });
    Route::get('/school/hr/tax-declarations', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'reviewQueue'])->middleware('role:admin')->name('school.hr.tax-declarations.index');
    Route::post('/school/hr/tax-declarations/{declaration}/review', [\App\Http\Controllers\School\StaffSelfServiceController::class, 'reviewDeclaration'])->middleware(['role:admin'])->whereNumber('declaration')->name('school.hr.tax-declarations.review');

    // Virtual ID Card
    Route::get('/id-card/{userId?}', [VirtualIdCardController::class, 'show'])->name('idcard.show');

    // Fees Module (QR + Cashier/Admin manual payment recording)
    Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
    Route::get('/fees/{id}', [FeeController::class, 'showInvoice'])->name('fees.show');
    Route::post('/fees/{id}/pay', [FeeController::class, 'payOnline'])->name('fees.pay')->middleware(['role:admin,cashier', 'throttle:30,1']);
    Route::post('/fees/{id}/reminder', [FeeController::class, 'sendReminder'])->name('fees.reminder')->middleware(['role:admin,cashier', 'throttle:10,1']);
    Route::get('/fees/receipt/{paymentId}', [FeeController::class, 'receipt'])->name('fees.receipt');

    // Bloom's Taxonomy Daily Assignment System
    Route::get('/blooms', [\App\Http\Controllers\BloomsController::class, 'index'])->name('blooms.index');
    Route::get('/blooms/lessons/{id}', [\App\Http\Controllers\BloomsController::class, 'showLesson'])->name('blooms.lesson');
    Route::post('/blooms/assignments/{id}/submit', [\App\Http\Controllers\BloomsController::class, 'submitAssignment'])->middleware('throttle:10,1')->name('blooms.assignment.submit');
    Route::get('/blooms/assignments/{id}/attachment/{type?}', [\App\Http\Controllers\BloomsController::class, 'downloadAttachment'])->name('blooms.assignment.download-attachment');
    Route::get('/blooms/curriculum-data/{classId}', [\App\Http\Controllers\BloomsController::class, 'getCurriculumData'])->name('blooms.curriculum.data');

    // Chapterwise Syllabus Section (Classes 1 to 12 & College)
    Route::get('/syllabus', [SyllabusController::class, 'index'])->name('syllabus.index');
    Route::get('/syllabus/tracker', [SyllabusController::class, 'tracker'])->name('syllabus.tracker');
    Route::get('/syllabus/{id}', [SyllabusController::class, 'show'])->name('syllabus.show')->whereNumber('id');
    Route::match(['post', 'patch'], '/syllabus/videos/{id}/toggle-watched', [SyllabusController::class, 'toggleVideoWatched'])->name('syllabus.videos.toggle-watched');

    // Video Lectures Hub (Classes 1 to 12)
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('videos.show');
    Route::match(['post', 'patch'], '/videos/{id}/toggle-watched', [VideoController::class, 'toggleWatched'])->name('videos.toggle-watched');

    // Attendance System & Student Leaves
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leaves.index');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leaves.store');
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy')->whereNumber('id');

    // Academy Circulars & Official Notice Board
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

    // Daily Practice Problems (DPP) & Timed Tests with Same-Day Checking
    Route::get('/dpp', [DppController::class, 'index'])->name('dpp.index');
    Route::get('/dpp/{id}', [DppController::class, 'show'])->name('dpp.show')->whereNumber('id');
    Route::post('/dpp/{id}/submit', [DppController::class, 'submit'])->name('dpp.submit')->whereNumber('id');
    Route::get('/question-bank/api', [DppController::class, 'apiQuestionBank'])->name('question-bank.api');

    // Daily Homework System
    Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::post('/homework/{id}/submit', [HomeworkController::class, 'submit'])->name('homework.submit');
    Route::get('/homework/{id}/attachment/{type?}', [HomeworkController::class, 'downloadAttachment'])->name('homework.download-attachment');

    Route::middleware('role:teacher,admin')->group(function () {
        // Bloom's & Curriculum CSV Data Management
        Route::get('/blooms/sample-students-csv', [\App\Http\Controllers\BloomsController::class, 'sampleStudentCsv'])->name('blooms.sample-students-csv');
        Route::get('/blooms/export-students-csv/{classId}', [\App\Http\Controllers\BloomsController::class, 'exportStudentsCsv'])->name('blooms.export-students-csv');
        Route::get('/blooms/sample-tasks-csv', [\App\Http\Controllers\BloomsController::class, 'sampleTasksCsv'])->name('blooms.sample-tasks-csv');
        Route::get('/blooms/lessons/{id}/export-csv', [\App\Http\Controllers\BloomsController::class, 'exportLessonCsv'])->name('blooms.lesson.export-csv');
        Route::get('/syllabus/sample-csv', [SyllabusController::class, 'sampleCsv'])->name('syllabus.sample-csv');
        Route::get('/syllabus/export-csv/{classId}', [SyllabusController::class, 'exportCsv'])->name('syllabus.export-csv');

        // DPP CSV Management
        Route::get('/dpp/sample-csv', [DppController::class, 'sampleCsv'])->name('dpp.sample-csv');
        Route::get('/dpp/{id}/export-csv', [DppController::class, 'exportCsv'])->name('dpp.export-csv')->whereNumber('id');

        Route::post('/blooms/lessons', [\App\Http\Controllers\BloomsController::class, 'storeLesson'])->name('blooms.lesson.store');
        Route::delete('/blooms/lessons/{id}', [\App\Http\Controllers\BloomsController::class, 'destroyLesson'])->name('blooms.lesson.destroy');
        Route::post('/blooms/assignments/{id}/grade', [\App\Http\Controllers\BloomsController::class, 'gradeAssignment'])->name('blooms.assignment.grade');
        Route::post('/blooms/import-students-csv', [\App\Http\Controllers\BloomsController::class, 'importStudentsCsv'])->name('blooms.import-students-csv');
        Route::post('/blooms/import-tasks-csv', [\App\Http\Controllers\BloomsController::class, 'importTasksCsv'])->name('blooms.import-tasks-csv');
        Route::post('/blooms/lessons/{id}/import-tasks', [\App\Http\Controllers\BloomsController::class, 'importTasksCsv'])->name('blooms.lesson.import-tasks');
        Route::post('/blooms/lessons/{id}/reshuffle', [\App\Http\Controllers\BloomsController::class, 'reshuffleLesson'])->name('blooms.lesson.reshuffle');
        Route::post('/blooms/lessons/{id}/assignments', [\App\Http\Controllers\BloomsController::class, 'storeCustomAssignment'])->middleware('throttle:10,1')->name('blooms.lesson.assignments.store');
        Route::match(['post', 'put', 'patch'], '/blooms/assignments/{id}', [\App\Http\Controllers\BloomsController::class, 'updateAssignment'])->middleware('throttle:10,1')->name('blooms.assignment.update');
        Route::post('/blooms/assignments/{id}/reassign', [\App\Http\Controllers\BloomsController::class, 'reassignStudent'])->name('blooms.assignment.reassign');
        Route::post('/blooms/assignments/{id}/reroll', [\App\Http\Controllers\BloomsController::class, 'rerollAssignment'])->name('blooms.assignment.reroll');
        Route::post('/blooms/assignments/{id}/request-revision', [\App\Http\Controllers\BloomsController::class, 'requestRevision'])->name('blooms.assignment.request-revision');
        Route::delete('/blooms/assignments/{id}', [\App\Http\Controllers\BloomsController::class, 'destroyAssignment'])->name('blooms.assignment.destroy');
        Route::post('/syllabus/import-csv', [SyllabusController::class, 'importCsv'])->name('syllabus.import-csv');

        // Attendance Recording & Leaves
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{id}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit')->whereNumber('id');
        Route::match(['post', 'put', 'patch'], '/attendance/{id}', [AttendanceController::class, 'update'])->name('attendance.update')->whereNumber('id');
        Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy'])->name('attendance.destroy')->whereNumber('id');
        Route::match(['post', 'patch'], '/leave-requests/{id}/review', [LeaveRequestController::class, 'review'])->name('leaves.review')->whereNumber('id');
        Route::post('/syllabus/topics', [SyllabusController::class, 'storeTopic'])->name('syllabus.topics.store');
        Route::match(['put', 'patch', 'post'], '/syllabus/topics/{id}', [SyllabusController::class, 'updateTopic'])->name('syllabus.topics.update')->whereNumber('id');
        Route::delete('/syllabus/topics/{id}', [SyllabusController::class, 'destroyTopic'])->name('syllabus.topics.destroy')->whereNumber('id');
        Route::match(['post', 'patch'], '/syllabus/topics/{id}/progress', [SyllabusController::class, 'updateProgress'])->name('syllabus.topics.progress')->whereNumber('id');
        Route::post('/syllabus/topics/{id}/toggle-activity', [SyllabusController::class, 'toggleActivity'])->name('syllabus.topics.toggle-activity')->whereNumber('id');
        Route::post('/syllabus/topics/{id}/videos', [SyllabusController::class, 'storeVideo'])->name('syllabus.topics.videos.store')->whereNumber('id');
        Route::match(['put', 'patch', 'post'], '/syllabus/videos/{id}', [SyllabusController::class, 'updateVideo'])->name('syllabus.videos.update')->whereNumber('id');
        Route::delete('/syllabus/videos/{id}', [SyllabusController::class, 'destroyVideo'])->name('syllabus.videos.destroy')->whereNumber('id');
        Route::post('/syllabus/topics/{id}/dpps', [SyllabusController::class, 'storeDpp'])->name('syllabus.topics.dpps.store')->whereNumber('id');
        Route::post('/syllabus/topics/{id}/homework', [SyllabusController::class, 'storeHomework'])->name('syllabus.topics.homework.store')->whereNumber('id');

        // Circulars / Notices
        Route::post('/notices', [AcademyNoticeController::class, 'store'])->name('notices.store');
        Route::match(['put', 'patch', 'post'], '/notices/{id}', [AcademyNoticeController::class, 'update'])->name('notices.update')->whereNumber('id');
        Route::delete('/notices/{id}', [AcademyNoticeController::class, 'destroy'])->name('notices.destroy')->whereNumber('id');
        Route::post('/notices/{id}/approve', [AcademyNoticeController::class, 'approve'])->name('notices.approve')->whereNumber('id');
        Route::post('/notices/{id}/reject', [AcademyNoticeController::class, 'reject'])->name('notices.reject')->whereNumber('id');
        Route::get('/notices/{id}/read-stats', [AcademyNoticeController::class, 'readStats'])->name('notices.read-stats')->whereNumber('id');

        // DPP & Test Creation, Bulk CSV Import & Question Bank
        Route::post('/dpp', [DppController::class, 'store'])->name('dpp.store');
        Route::post('/dpp/bulk-import', [DppController::class, 'bulkImport'])->name('dpp.bulk-import');
        Route::post('/question-bank', [DppController::class, 'storeQuestionBank'])->name('question-bank.store');
        Route::get('/dpp/{id}/edit', [DppController::class, 'edit'])->name('dpp.edit')->whereNumber('id');
        Route::match(['post', 'put', 'patch'], '/dpp/{id}', [DppController::class, 'update'])->name('dpp.update')->whereNumber('id');
        Route::delete('/dpp/{id}', [DppController::class, 'destroy'])->name('dpp.destroy')->whereNumber('id');
        Route::delete('/dpp/{id}/questions/{questionId}', [DppController::class, 'destroyQuestion'])->name('dpp.questions.destroy')->whereNumber(['id', 'questionId']);

        // Homework Creation, Update, Deletion & Verification
        Route::post('/homework', [HomeworkController::class, 'store'])->name('homework.store');
        Route::match(['put', 'patch', 'post'], '/homework/{id}', [HomeworkController::class, 'update'])->name('homework.update')->whereNumber('id');
        Route::delete('/homework/{id}', [HomeworkController::class, 'destroy'])->name('homework.destroy')->whereNumber('id');
        Route::match(['post', 'patch'], '/homework/submissions/{id}/check', [HomeworkController::class, 'check'])->name('homework.submissions.check');
    });

    // Courses Enrollment & Assessments (Requires Authentication)
    Route::post('/courses/{id}/enroll', [WebCourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{courseId}/assessments/{assessmentId}', [\App\Http\Controllers\CourseAssessmentController::class, 'show'])->name('courses.assessments.show');
    Route::post('/courses/{courseId}/assessments/{assessmentId}/submit', [\App\Http\Controllers\CourseAssessmentController::class, 'submit'])->name('courses.assessments.submit');

    // Assignments
    Route::get('/assignments', [WebAssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{id}', [WebAssignmentController::class, 'show'])->name('assignments.show');
    Route::get('/assignments/{id}/attachment', [WebAssignmentController::class, 'downloadAttachment'])->name('assignments.attachment');
    Route::post('/assignments/{id}/submit', [WebAssignmentController::class, 'submit'])->name('assignments.submit');

    // Schedule / Timetable
    Route::get('/schedule', [WebScheduleController::class, 'index'])->name('schedules.index');
    Route::redirect('/schedules', '/schedule');

    // Academic Holidays & Festivals System (Rule 15 - Visible to all roles)
    Route::get('/school/holidays', [\App\Http\Controllers\School\SchoolHolidayController::class, 'index'])->name('school.holidays.index');
    Route::redirect('/lesson-planner/holidays', '/school/holidays');
    Route::redirect('/lesson-planner', '/school/holidays');

    // Live Quizzes Arena - Student Participation
    Route::get('/quizzes', [LiveQuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}', [LiveQuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/submit', [LiveQuizController::class, 'submit'])->name('quizzes.submit');

    // Case Studies & Projects (Grouped & Ungrouped)
    Route::get('/projects', [ProjectCaseStudyController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectCaseStudyController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/submit', [ProjectCaseStudyController::class, 'submit'])->name('projects.submit');

    // Debate Organisations & Talent Shows (Academy Events)
    Route::get('/events', [AcademyEventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [AcademyEventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/register', [AcademyEventController::class, 'register'])->name('events.register');
    Route::post('/events/{event}/unregister', [AcademyEventController::class, 'unregister'])->name('events.unregister');

    // Campus-Wide & Class-Wise Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Official Certificates & Badges Showcase
    Route::get('/certificates', [CertificateBadgeController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateBadgeController::class, 'showCertificate'])->name('certificates.show');

    // Teacher & Admin Actions
    Route::middleware('role:teacher,admin')->group(function () {
        // Student Directory & Roster Management (Faculty & Admin)
        Route::get('/students', [StudentDirectoryController::class, 'index'])->name('students.index');
        Route::get('/students/export-csv', [StudentDirectoryController::class, 'exportCsv'])->name('students.export-csv');
        Route::get('/students/grab-json', [StudentDirectoryController::class, 'grabJson'])->name('students.grab-json');
        Route::get('/students/roster-print', [StudentDirectoryController::class, 'rosterPrint'])->name('students.roster-print');
        Route::get('/students/{student}', [StudentDirectoryController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentDirectoryController::class, 'edit'])->name('students.edit');
        Route::match(['put', 'patch', 'post'], '/students/{student}', [StudentDirectoryController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentDirectoryController::class, 'destroy'])->name('students.destroy');

        // Staff Directory & Management (Faculty & Admin)
        Route::get('/staff', [\App\Http\Controllers\StaffDirectoryController::class, 'index'])->name('staff.index');
        Route::get('/staff/export-csv', [\App\Http\Controllers\StaffDirectoryController::class, 'exportCsv'])->name('staff.export-csv');
        Route::get('/staff/{staff}', [\App\Http\Controllers\StaffDirectoryController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [\App\Http\Controllers\StaffDirectoryController::class, 'edit'])->name('staff.edit');
        Route::match(['put', 'patch', 'post'], '/staff/{staff}', [\App\Http\Controllers\StaffDirectoryController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [\App\Http\Controllers\StaffDirectoryController::class, 'destroy'])->name('staff.destroy');
        Route::get('/staff/{staff}/employment', [\App\Http\Controllers\School\StaffEmploymentController::class, 'show'])->whereNumber('staff')->name('staff.employment.show');
        Route::post('/staff/{staff}/employment/profile', [\App\Http\Controllers\School\StaffEmploymentController::class, 'saveProfile'])->whereNumber('staff')->name('staff.employment.profile');
        Route::post('/staff/{staff}/employment/contracts', [\App\Http\Controllers\School\StaffEmploymentController::class, 'uploadContract'])->whereNumber('staff')->name('staff.employment.contracts.store');
        Route::get('/staff/{staff}/employment/contracts/{contract}/download', [\App\Http\Controllers\School\StaffEmploymentController::class, 'downloadContract'])->whereNumber('staff')->whereNumber('contract')->name('staff.employment.contracts.download');
        Route::post('/staff/{staff}/employment/documents', [\App\Http\Controllers\School\StaffEmploymentController::class, 'uploadDocument'])->whereNumber('staff')->name('staff.employment.documents.store');
        Route::get('/staff/{staff}/employment/documents/{document}/download', [\App\Http\Controllers\School\StaffEmploymentController::class, 'downloadDocument'])->whereNumber('staff')->whereNumber('document')->name('staff.employment.documents.download');
        Route::post('/staff/{staff}/employment/separate', [\App\Http\Controllers\School\StaffEmploymentController::class, 'separate'])->whereNumber('staff')->name('staff.employment.separate');
        Route::post('/staff/{staff}/employment/onboarding/{task}', [\App\Http\Controllers\School\StaffEmploymentController::class, 'updateOnboardingTask'])->whereNumber('staff')->whereNumber('task')->name('staff.employment.onboarding.update');

        // Employee final settlement (controller enforces administrator-only access).
        Route::get('/hr/final-settlements', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'index'])->name('school.staff.final-settlements.index');
        Route::post('/hr/final-settlements', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'create'])->name('school.staff.final-settlements.create');
        Route::post('/hr/final-settlements/{settlement}/items', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'addItem'])->whereNumber('settlement')->name('school.staff.final-settlements.items.store');
        Route::delete('/hr/final-settlements/{settlement}/items/{item}', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'deleteItem'])->whereNumber('settlement')->whereNumber('item')->name('school.staff.final-settlements.items.destroy');
        Route::post('/hr/final-settlements/{settlement}/approve', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'approve'])->whereNumber('settlement')->name('school.staff.final-settlements.approve');
        Route::post('/hr/final-settlements/{settlement}/payments', [\App\Http\Controllers\School\StaffFinalSettlementController::class, 'recordPayment'])->whereNumber('settlement')->name('school.staff.final-settlements.payments.store');

        Route::post('/courses', [WebCourseController::class, 'store'])->name('courses.store');
        Route::match(['put', 'patch', 'post'], '/courses/{id}', [WebCourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{id}', [WebCourseController::class, 'destroy'])->name('courses.destroy');
        Route::get('/courses/{courseId}/curriculum/template-csv', [WebCourseController::class, 'downloadCurriculumCsvTemplate'])->name('courses.curriculum.template-csv');
        Route::post('/courses/{courseId}/curriculum/import-csv', [WebCourseController::class, 'importCurriculumCsv'])->name('courses.curriculum.import-csv');
        Route::post('/courses/{courseId}/modules', [WebCourseController::class, 'storeModule'])->name('courses.modules.store');
        Route::match(['put', 'patch', 'post'], '/courses/{courseId}/modules/{moduleId}', [WebCourseController::class, 'updateModule'])->name('courses.modules.update');
        Route::delete('/courses/{courseId}/modules/{moduleId}', [WebCourseController::class, 'destroyModule'])->name('courses.modules.destroy');
        Route::post('/courses/{courseId}/lessons', [WebCourseController::class, 'storeLesson'])->name('courses.lessons.store');
        Route::match(['put', 'patch', 'post'], '/courses/{courseId}/lessons/{lessonId}', [WebCourseController::class, 'updateLesson'])->name('courses.lessons.update');
        Route::delete('/courses/{courseId}/lessons/{lessonId}', [WebCourseController::class, 'destroyLesson'])->name('courses.lessons.destroy');
        Route::post('/courses/{courseId}/videos', [WebCourseController::class, 'storeVideo'])->name('courses.videos.store');
        Route::match(['put', 'patch', 'post'], '/courses/{courseId}/videos/{videoId}', [WebCourseController::class, 'updateLessonVideo'])->name('courses.videos.update');
        Route::delete('/courses/{courseId}/videos/{videoId}', [WebCourseController::class, 'destroyLessonVideo'])->name('courses.videos.destroy');
        Route::post('/courses/{courseId}/dpps', [WebCourseController::class, 'storeDpp'])->name('courses.dpps.store');
        Route::post('/courses/{courseId}/lessons/{lessonId?}/notes', [WebCourseController::class, 'uploadLessonPdf'])->name('courses.lessons.upload-note');
        Route::delete('/courses/{courseId}/lessons/{lessonId}/notes', [WebCourseController::class, 'destroyLessonPdf'])->name('courses.lessons.destroy-note');
        Route::post('/courses/{courseId}/notes', [WebCourseController::class, 'uploadLessonPdf'])->name('courses.notes.upload');
        Route::post('/courses/{courseId}/assessments', [\App\Http\Controllers\CourseAssessmentController::class, 'store'])->name('courses.assessments.store');
        Route::match(['put', 'patch', 'post'], '/courses/{courseId}/assessments/{assessmentId}', [\App\Http\Controllers\CourseAssessmentController::class, 'update'])->name('courses.assessments.update');
        Route::delete('/courses/{courseId}/assessments/{assessmentId}', [\App\Http\Controllers\CourseAssessmentController::class, 'destroy'])->name('courses.assessments.destroy');
        Route::post('/assignments', [WebAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{id}/edit', [WebAssignmentController::class, 'edit'])->name('assignments.edit');
        Route::match(['put', 'patch', 'post'], '/assignments/{id}', [WebAssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{id}', [WebAssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/submissions/{id}/grade', [WebAssignmentController::class, 'grade'])->name('submissions.grade');
        Route::post('/schedules', [WebScheduleController::class, 'store'])->name('schedules.store');
        Route::match(['put', 'patch', 'post'], '/schedules/{schedule}', [WebScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [WebScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/toggle-cancel', [WebScheduleController::class, 'toggleCancel'])->name('schedules.toggle-cancel');

        // Quizzes Management & Question Importing
        Route::post('/quizzes', [LiveQuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [LiveQuizController::class, 'edit'])->name('quizzes.edit');
        Route::match(['put', 'patch', 'post'], '/quizzes/{quiz}', [LiveQuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [LiveQuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('/quizzes/{quiz}/import-csv', [LiveQuizController::class, 'importCsv'])->name('quizzes.import-csv');
        Route::post('/quizzes/{quiz}/import-text', [LiveQuizController::class, 'importBulkText'])->name('quizzes.import-text');
        Route::post('/quizzes/{quiz}/questions', [LiveQuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
        Route::match(['put', 'patch', 'post'], '/quizzes/{quiz}/questions/{question}', [LiveQuizController::class, 'updateQuestion'])->name('quizzes.questions.update');
        Route::delete('/quizzes/{quiz}/questions/{question}', [LiveQuizController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');

        // Projects Management
        Route::post('/projects', [ProjectCaseStudyController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}/edit', [ProjectCaseStudyController::class, 'edit'])->name('projects.edit');
        Route::match(['put', 'patch', 'post'], '/projects/{project}', [ProjectCaseStudyController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectCaseStudyController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/submissions/{submission}/evaluate', [ProjectCaseStudyController::class, 'evaluate'])->name('projects.submissions.evaluate');

        // Events Management
        Route::post('/events', [AcademyEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [AcademyEventController::class, 'edit'])->name('events.edit');
        Route::match(['put', 'patch', 'post'], '/events/{event}', [AcademyEventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [AcademyEventController::class, 'destroy'])->name('events.destroy');
        Route::post('/events/{event}/podium', [AcademyEventController::class, 'updatePodium'])->name('events.podium');
        Route::get('/events/{event}/export-participants-csv', [AcademyEventController::class, 'exportParticipantsCsv'])->name('events.export-participants-csv');

        // Certificates Management
        Route::post('/certificates/issue', [CertificateBadgeController::class, 'issueCertificate'])->name('certificates.issue');
        Route::delete('/certificates/{certificate}', [CertificateBadgeController::class, 'destroy'])->name('certificates.destroy');

        // Academic Holidays & Calendar Actions (Rule 15)
        Route::post('/school/holidays', [\App\Http\Controllers\School\SchoolHolidayController::class, 'store'])->name('school.holidays.store');
        Route::match(['put', 'patch', 'post'], '/school/holidays/{id}', [\App\Http\Controllers\School\SchoolHolidayController::class, 'update'])->name('school.holidays.update')->whereNumber('id');
        Route::delete('/school/holidays/{id}', [\App\Http\Controllers\School\SchoolHolidayController::class, 'destroy'])->name('school.holidays.destroy')->whereNumber('id');

        // Quizzes CSV Exports & Question Templates
        Route::get('/quizzes/sample-csv', [LiveQuizController::class, 'sampleCsv'])->name('quizzes.sample-csv');
        Route::get('/quizzes/{quiz}/sample-csv', [LiveQuizController::class, 'sampleCsv'])->name('quizzes.quiz.sample-csv');
        Route::get('/quizzes/{quiz}/export-csv', [LiveQuizController::class, 'exportCsv'])->name('quizzes.export-csv');
    });

    // Admin-exclusive Actions
    Route::middleware('role:admin')->group(function () {
        Route::get('/school/administration', [\App\Http\Controllers\School\AdministrationController::class, 'index'])->name('school.administration.index');
        Route::get('/school/administration/audit.csv', [\App\Http\Controllers\School\AdministrationController::class, 'exportAudit'])->name('school.administration.audit.export');
        Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
        Route::post('/fees/generate-monthly', [FeeController::class, 'generateMonthly'])->name('fees.generate-monthly');
        Route::post('/fees/monthly-config', [FeeController::class, 'updateMonthlyConfig'])->name('fees.monthly-config');
        Route::delete('/fees/{id}', [FeeController::class, 'destroy'])->name('fees.destroy')->whereNumber('id');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/qr-image', [SettingsController::class, 'uploadQrImage'])->name('settings.qr-image');
        Route::post('/school-compliance-documents', [\App\Http\Controllers\School\SchoolComplianceDocumentController::class, 'store'])->name('school-compliance-documents.store');
        Route::put('/school-compliance-documents/{document}', [\App\Http\Controllers\School\SchoolComplianceDocumentController::class, 'update'])->name('school-compliance-documents.update');
        Route::get('/school-compliance-documents/{document}/download', [\App\Http\Controllers\School\SchoolComplianceDocumentController::class, 'download'])->name('school-compliance-documents.download');
        Route::delete('/school-compliance-documents/{document}', [\App\Http\Controllers\School\SchoolComplianceDocumentController::class, 'destroy'])->name('school-compliance-documents.destroy');

        // Themes & Appearance Hub (Infix System)
        Route::get('/appearance', [AppearanceController::class, 'index'])->name('appearance.index');
        Route::post('/appearance/activate', [AppearanceController::class, 'activate'])->name('appearance.activate');
        Route::post('/appearance/customize', [AppearanceController::class, 'customize'])->name('appearance.customize');
        Route::post('/appearance/fonts', [AppearanceController::class, 'updateFonts'])->name('appearance.fonts');
        Route::post('/appearance/preloader', [AppearanceController::class, 'updatePreloader'])->name('appearance.preloader');

        // Faculty Authorization Codes & Device Lock Control
        Route::get('/admin/faculty-codes', [FacultyCodeController::class, 'index'])->name('admin.faculty-codes.index');
        Route::post('/admin/faculty-codes', [FacultyCodeController::class, 'store'])->name('admin.faculty-codes.store');
        Route::delete('/admin/faculty-codes/{code}', [FacultyCodeController::class, 'destroy'])->name('admin.faculty-codes.destroy');
        Route::post('/admin/faculty-codes/toggle-lock/{user}', [FacultyCodeController::class, 'toggleTeacherLock'])->name('admin.faculty.toggle-lock');
        Route::delete('/admin/faculty-codes/device-locks/{lock}', [FacultyCodeController::class, 'unlockDevice'])->name('admin.faculty.unlock-device');
    });

    // Analytics (Teacher & Admin)
    Route::middleware('role:teacher,admin')->group(function () {
        Route::get('/analytics', [WebAnalyticsController::class, 'index'])->name('analytics.index');
    });

    // ==========================================
    // PROPER SCHOOL MANAGEMENT SYSTEM (SCHOOL ERP)
    // ==========================================

    // 1. School Classes & Sections Management (Admin & Teachers only)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/school/classes', [SchoolClassController::class, 'index'])->name('school.classes.index');
        Route::get('/school/classes/{id}', [SchoolClassController::class, 'show'])->name('school.classes.show')->whereNumber('id');
        Route::get('/school/sections', [SchoolSectionController::class, 'index'])->name('school.sections.index');
        Route::get('/school/streams', [SchoolStreamController::class, 'index'])->name('school.streams.index');
        Route::get('/school/subjects', [SchoolSubjectController::class, 'index'])->name('school.subjects.index');
        Route::get('/school/subjects/master-options', [SchoolClassController::class, 'masterOptions'])->name('school.subjects.master-options');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/school/classes', [SchoolClassController::class, 'store'])->name('school.classes.store');
        Route::match(['put', 'patch', 'post'], '/school/classes/{id}', [SchoolClassController::class, 'update'])->name('school.classes.update')->whereNumber('id');
        Route::delete('/school/classes/{id}', [SchoolClassController::class, 'destroy'])->name('school.classes.destroy')->whereNumber('id');
        Route::post('/school/classes/{id}/subjects', [SchoolClassController::class, 'storeSubject'])->name('school.classes.subjects.store')->whereNumber('id');
        Route::delete('/school/classes/{id}/subjects/{subjectId}', [SchoolClassController::class, 'destroySubject'])->name('school.classes.subjects.destroy')->whereNumber(['id', 'subjectId']);
        
        // Reusable Sections Management
        Route::post('/school/sections', [SchoolSectionController::class, 'store'])->name('school.sections.store');
        Route::patch('/school/sections/{id}', [SchoolSectionController::class, 'update'])->name('school.sections.update')->whereNumber('id');
        Route::patch('/school/sections/{id}/toggle', [SchoolSectionController::class, 'toggle'])->name('school.sections.toggle')->whereNumber('id');
        Route::delete('/school/sections/{id}', [SchoolSectionController::class, 'destroy'])->name('school.sections.destroy')->whereNumber('id');

        // Academic stream master data: deactivation preserves historical references.
        Route::post('/school/streams', [SchoolStreamController::class, 'store'])->name('school.streams.store');
        Route::patch('/school/streams/{id}', [SchoolStreamController::class, 'update'])->name('school.streams.update')->whereNumber('id');
        Route::patch('/school/streams/{id}/toggle', [SchoolStreamController::class, 'toggle'])->name('school.streams.toggle')->whereNumber('id');

        // Master Subjects Management
        Route::post('/school/subjects', [SchoolSubjectController::class, 'store'])->name('school.subjects.store');
        Route::patch('/school/subjects/{id}', [SchoolSubjectController::class, 'update'])->name('school.subjects.update')->whereNumber('id');
        Route::patch('/school/subjects/{id}/activate', [SchoolSubjectController::class, 'activate'])->name('school.subjects.activate')->whereNumber('id');
        Route::delete('/school/subjects/{id}', [SchoolSubjectController::class, 'destroy'])->name('school.subjects.destroy')->whereNumber('id');
    });

    // Infix Virtual Class & Live Meetings (All Authenticated Scholars & Faculty)
    Route::get('/virtual-classes', [VirtualClassController::class, 'index'])->name('virtual-classes.index');
    Route::get('/virtual-classes/{virtualClass}', [VirtualClassController::class, 'show'])->name('virtual-classes.show');
    Route::get('/virtual-classes/{virtualClass}/join', [VirtualClassController::class, 'join'])->name('virtual-classes.join');
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/virtual-classes', [VirtualClassController::class, 'store'])->name('virtual-classes.store');
        Route::delete('/virtual-classes/{virtualClass}', [VirtualClassController::class, 'destroy'])->name('virtual-classes.destroy');
    });

    // Admission enquiry management is administrator-only.
    Route::middleware('role:admin')->prefix('/school/admissions')->name('school.admissions.')->group(function () {
        Route::get('/enquiries', [AdmissionEnquiryController::class, 'index'])->name('enquiries.index');
        Route::patch('/enquiries/{enquiry}', [AdmissionEnquiryController::class, 'update'])->name('enquiries.update');
    });

    // 2. School Student Admissions & Directory (Directory: Admin & Teacher only; ID Card: Owner/Parent/Admin/Teacher)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/school/students', [SchoolStudentController::class, 'index'])->name('school.students.index');
        Route::get('/school/students/export-csv', [SchoolStudentController::class, 'exportCsv'])->name('school.students.export-csv');
        Route::get('/school/students/{id}', [SchoolStudentController::class, 'show'])->name('school.students.show')->whereNumber('id');
        Route::get('/school/student-admission-documents/{documentId}/download', [SchoolStudentController::class, 'downloadAdmissionDocument'])->name('school.students.admission-documents.download')->whereNumber('documentId');
        Route::get('/school/students/create', [SchoolStudentController::class, 'create'])->name('school.students.create');
        Route::post('/school/students', [SchoolStudentController::class, 'store'])->name('school.students.store');
        Route::get('/school/students/{id}/edit', [SchoolStudentController::class, 'edit'])->name('school.students.edit')->whereNumber('id');
        Route::match(['put', 'patch', 'post'], '/school/students/{id}', [SchoolStudentController::class, 'update'])->name('school.students.update')->whereNumber('id');
        Route::delete('/school/students/{id}', [SchoolStudentController::class, 'destroy'])->name('school.students.destroy')->whereNumber('id');
        Route::post('/school/users/{id}/reset-account', [AccountManagementController::class, 'resetAccount'])->name('school.users.reset-account')->whereNumber('id');
    });
    Route::get('/school/students/{id}/id-card', [SchoolStudentController::class, 'idCard'])->name('school.students.id-card')->whereNumber('id');

    // 3. School Daily Class Attendance & Roll Call
    // Attendance Index: Role-aware (Parents see Ward logs, Students see personal summary, Teachers/Admins see Roll Call)
    Route::get('/school/attendance', [SchoolAttendanceController::class, 'index'])->name('school.attendance.index');
    Route::get('/school/attendance/register/{classId}', [SchoolAttendanceController::class, 'register'])->name('school.attendance.register')->whereNumber('classId');

    // Attendance Taking & Modification: Strictly restricted to Faculty & Admins (Parents & Students forbidden)
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/school/attendance/classes/{classId}', [SchoolAttendanceController::class, 'take'])->name('school.attendance.take')->whereNumber('classId');
        Route::post('/school/attendance/classes/{classId}', [SchoolAttendanceController::class, 'store'])->name('school.attendance.store')->whereNumber('classId');
    });

    // 4. School Examinations, Marks & Official Report Cards
    Route::get('/school/exams', [SchoolExaminationController::class, 'index'])->name('school.exams.index');
    Route::get('/school/exams/admit-card/{termId}/{studentId?}', [SchoolExaminationController::class, 'admitCard'])->name('school.exams.admit-card')->whereNumber('termId')->whereNumber('studentId');
    Route::get('/school/report-cards/{id}', [SchoolExaminationController::class, 'showReportCard'])->name('school.report-cards.show')->whereNumber('id');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/school/exams/marks-entry', [SchoolExaminationController::class, 'marksEntry'])->name('school.exams.marks-entry');
        Route::post('/school/exams/marks', [SchoolExaminationController::class, 'storeMarks'])->name('school.exams.marks.store');
        Route::post('/school/exams/marks/bulk-import', [SchoolExaminationController::class, 'bulkImportMarks'])->name('school.exams.marks.bulk-import');
        Route::post('/school/exams/marks/{id}/re-evaluate', [SchoolExaminationController::class, 'reEvaluateMark'])->name('school.exams.marks.re-evaluate')->whereNumber('id');
        Route::post('/school/exams/terms', [SchoolExaminationController::class, 'storeTerm'])->name('school.exams.terms.store');
        Route::put('/school/exams/terms/{id}', [SchoolExaminationController::class, 'updateTerm'])->name('school.exams.terms.update')->whereNumber('id');
        Route::delete('/school/exams/terms/{id}', [SchoolExaminationController::class, 'destroyTerm'])->name('school.exams.terms.destroy')->whereNumber('id');
        Route::post('/school/exams/schedules', [SchoolExaminationController::class, 'storeSchedule'])->name('school.exams.schedules.store');
        Route::post('/school/exams/auto-assign-schedules', [SchoolExaminationController::class, 'autoAssignSchedules'])->name('school.exams.auto-assign-schedules');
        Route::delete('/school/exams/schedules/{id}', [SchoolExaminationController::class, 'destroySchedule'])->name('school.exams.schedules.destroy')->whereNumber('id');
        Route::post('/school/exams/generate-report-cards', [SchoolExaminationController::class, 'generateReportCards'])->name('school.exams.generate-report-cards');
        Route::post('/school/exams/report-cards/publish', [SchoolExaminationController::class, 'publishReportCards'])->name('school.exams.report-cards.publish');
        Route::post('/school/exams/grading-scales', [SchoolExaminationController::class, 'storeGradeScale'])->name('school.exams.grading-scales.store');
        Route::put('/school/exams/grading-scales/{id}', [SchoolExaminationController::class, 'updateGradeScale'])->name('school.exams.grading-scales.update')->whereNumber('id');
        Route::delete('/school/exams/grading-scales/{id}', [SchoolExaminationController::class, 'destroyGradeScale'])->name('school.exams.grading-scales.destroy')->whereNumber('id');
    });

    // 5. School Period-Based Timetable & Routine
    Route::get('/school/timetable', [SchoolTimetableController::class, 'index'])->name('school.timetable.index');

    Route::middleware('role:admin')->group(function () {
        Route::post('/school/timetable', [SchoolTimetableController::class, 'store'])->name('school.timetable.store');
        Route::post('/school/timetable/periods', [SchoolTimetableController::class, 'storePeriod'])->name('school.timetable.periods.store');
        Route::patch('/school/timetable/periods/{id}', [SchoolTimetableController::class, 'updatePeriod'])->name('school.timetable.periods.update')->whereNumber('id');
        Route::patch('/school/timetable/periods/{id}/toggle', [SchoolTimetableController::class, 'togglePeriod'])->name('school.timetable.periods.toggle')->whereNumber('id');
        Route::delete('/school/timetable/{id}', [SchoolTimetableController::class, 'destroy'])->name('school.timetable.destroy')->whereNumber('id');
        Route::post('/school/timetable/auto-generate/{classId}', [SchoolTimetableController::class, 'autoGenerate'])->name('school.timetable.auto-generate')->whereNumber('classId');
        Route::post('/school/timetable/substitutes', [SchoolTimetableController::class, 'storeSubstitute'])->name('school.timetable.substitutes.store');
        Route::delete('/school/timetable/substitutes/{id}', [SchoolTimetableController::class, 'destroySubstitute'])->name('school.timetable.substitutes.destroy')->whereNumber('id');
    });

    // 6. School Fees Management
    Route::get('/school/fees', [SchoolFeeController::class, 'index'])->name('school.fees.index');
    Route::get('/school/fees/receipt/{paymentId}', [SchoolFeeController::class, 'receipt'])->name('school.fees.receipt')->whereNumber('paymentId');

    // Cashier + Admin: mark payment received, verify UPI/QR, bulk record
    Route::middleware('role:admin,cashier')->group(function () {
        Route::post('/school/fees/invoices/{invoiceId}/pay', [SchoolFeeController::class, 'recordPayment'])->name('school.fees.pay')->whereNumber('invoiceId');
        Route::post('/school/fees/payments/{paymentId}/verify-upi', [SchoolFeeController::class, 'verifyUpiPayment'])->name('school.fees.verify-upi')->whereNumber('paymentId');
        Route::post('/school/fees/bulk-payments', [SchoolFeeController::class, 'bulkRecordPayments'])->name('school.fees.bulk-payments');
    });

    // Show QR for payment — available to any authenticated user (view only)
    Route::post('/school/fees/invoices/{invoiceId}/initiate-upi', [SchoolFeeController::class, 'initiateUpiPayment'])->name('school.fees.initiate-upi')->whereNumber('invoiceId');

    Route::middleware('role:admin')->group(function () {
        Route::post('/school/fees/structures', [SchoolFeeController::class, 'storeStructure'])->name('school.fees.structures.store');
        Route::delete('/school/fees/structures/{id}', [SchoolFeeController::class, 'destroyStructure'])->name('school.fees.structures.destroy')->whereNumber('id');
        Route::post('/school/fees/generate-invoices', [SchoolFeeController::class, 'generateClassInvoices'])->name('school.fees.generate-invoices');
        Route::post('/school/fees/invoices/{invoiceId}/waiver', [SchoolFeeController::class, 'approveWaiver'])->name('school.fees.waiver')->whereNumber('invoiceId');
        Route::post('/school/fees/payments/{paymentId}/refund', [SchoolFeeController::class, 'processRefund'])->name('school.fees.refund')->whereNumber('paymentId');
    });

    // 7. Parent Portal
    Route::middleware('role:parent,admin')->group(function () {
        Route::get('/parent/dashboard', [SchoolParentController::class, 'dashboard'])->name('school.parent.dashboard');
    });

    // 8. Teacher & Subject Assignment (Admin Exclusive)
    Route::middleware('role:admin')->prefix('school/assignments')->name('school.assignments.')->group(function () {
        Route::get('/', [TeacherAssignmentController::class, 'index'])->name('index');
        Route::post('/subjects', [TeacherAssignmentController::class, 'storeSubject'])->name('subjects.store');
        Route::match(['put', 'patch', 'post'], '/subjects/{id}', [TeacherAssignmentController::class, 'updateSubject'])->name('subjects.update')->whereNumber('id');
        Route::delete('/subjects/{id}', [TeacherAssignmentController::class, 'destroySubject'])->name('subjects.destroy')->whereNumber('id');
        Route::post('/class-teacher', [TeacherAssignmentController::class, 'assignClassTeacher'])->name('class-teacher.assign');
        Route::delete('/class-teacher/{classId}', [TeacherAssignmentController::class, 'removeClassTeacher'])->name('class-teacher.remove')->whereNumber('classId');
        Route::post('/timetable', [TeacherAssignmentController::class, 'storeTimetable'])->name('timetable.store');
        Route::delete('/timetable/{id}', [TeacherAssignmentController::class, 'destroyTimetable'])->name('timetable.destroy')->whereNumber('id');
    });

    // 9. Bulk Data Import & Export Hub (Faculty & Admin)
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

    // 10. Institutional Reports Center (Admin, Faculty & Cashier)
    Route::middleware('role:admin,teacher,cashier')->prefix('school/reports')->name('school.reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\SchoolReportController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\School\SchoolReportController::class, 'export'])->name('export');
        Route::get('/print', [\App\Http\Controllers\School\SchoolReportController::class, 'print'])->name('print');
    });

    // ==========================================
    // INFIX SCHOOL MANAGEMENT SYSTEM MODULES
    // ==========================================

    // 11. Infix Library Management
    Route::prefix('school/library')->name('school.library.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\LibraryController::class, 'index'])->name('index');
        Route::post('/books', [\App\Http\Controllers\School\LibraryController::class, 'storeBook'])->name('books.store');
        Route::post('/issue', [\App\Http\Controllers\School\LibraryController::class, 'issueBook'])->name('issue');
        Route::post('/return/{issueId}', [\App\Http\Controllers\School\LibraryController::class, 'returnBook'])->name('return')->whereNumber('issueId');
        Route::post('/renew/{issueId}', [\App\Http\Controllers\School\LibraryController::class, 'renewBook'])->name('renew')->whereNumber('issueId');
        Route::post('/reserve', [\App\Http\Controllers\School\LibraryController::class, 'reserveBook'])->name('reserve');
    });

    // 12. Infix Transport & Fleet Management - Complete CRUD & Operations
    Route::prefix('school/transport')->name('school.transport.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\TransportController::class, 'index'])->name('index');
        
        // Vehicles CRUD & Operations
        Route::post('/vehicles', [\App\Http\Controllers\School\TransportController::class, 'storeVehicle'])->name('vehicles.store');
        Route::put('/vehicles/{id}', [\App\Http\Controllers\School\TransportController::class, 'updateVehicle'])->name('vehicles.update')->whereNumber('id');
        Route::delete('/vehicles/{id}', [\App\Http\Controllers\School\TransportController::class, 'destroyVehicle'])->name('vehicles.destroy')->whereNumber('id');
        Route::post('/vehicles/{id}/status', [\App\Http\Controllers\School\TransportController::class, 'updateVehicleStatus'])->name('vehicles.status')->whereNumber('id');

        // Routes CRUD
        Route::post('/routes', [\App\Http\Controllers\School\TransportController::class, 'storeRoute'])->name('routes.store');
        Route::put('/routes/{id}', [\App\Http\Controllers\School\TransportController::class, 'updateRoute'])->name('routes.update')->whereNumber('id');
        Route::delete('/routes/{id}', [\App\Http\Controllers\School\TransportController::class, 'destroyRoute'])->name('routes.destroy')->whereNumber('id');

        // Stops CRUD
        Route::post('/stops', [\App\Http\Controllers\School\TransportController::class, 'storeStop'])->name('stops.store');
        Route::put('/stops/{id}', [\App\Http\Controllers\School\TransportController::class, 'updateStop'])->name('stops.update')->whereNumber('id');
        Route::delete('/stops/{id}', [\App\Http\Controllers\School\TransportController::class, 'destroyStop'])->name('stops.destroy')->whereNumber('id');

        // Drivers & Staff CRUD
        Route::post('/staff', [\App\Http\Controllers\School\TransportController::class, 'storeStaff'])->name('staff.store');
        Route::put('/staff/{id}', [\App\Http\Controllers\School\TransportController::class, 'updateStaff'])->name('staff.update')->whereNumber('id');
        Route::delete('/staff/{id}', [\App\Http\Controllers\School\TransportController::class, 'destroyStaff'])->name('staff.destroy')->whereNumber('id');
        Route::post('/staff/{id}/reassign', [\App\Http\Controllers\School\TransportController::class, 'reassignStaff'])->name('staff.reassign')->whereNumber('id');

        // Student Assignments & Operations
        Route::post('/students/assign', [\App\Http\Controllers\School\TransportController::class, 'assignStudent'])->name('students.assign');
        Route::post('/students/bulk-assign', [\App\Http\Controllers\School\TransportController::class, 'bulkAssignStudents'])->name('students.bulk_assign');
        Route::post('/students/{id}/opt-out', [\App\Http\Controllers\School\TransportController::class, 'optOutStudent'])->name('students.opt_out')->whereNumber('id');
        Route::post('/students/{id}/opt-in', [\App\Http\Controllers\School\TransportController::class, 'optInStudent'])->name('students.opt_in')->whereNumber('id');
        Route::delete('/students/{id}', [\App\Http\Controllers\School\TransportController::class, 'destroyAssignment'])->name('students.destroy')->whereNumber('id');

        // Transport Fees
        Route::post('/fees/generate', [\App\Http\Controllers\School\TransportController::class, 'generateMonthlyFees'])->name('fees.generate');

        // Trips & Daily Operations
        Route::post('/trips', [\App\Http\Controllers\School\TransportController::class, 'storeTrip'])->name('trips.store');
        Route::post('/trips/{id}/end', [\App\Http\Controllers\School\TransportController::class, 'endTrip'])->name('trips.end')->whereNumber('id');
        Route::post('/passengers/{id}/status', [\App\Http\Controllers\School\TransportController::class, 'updatePassengerStatus'])->name('passengers.status')->whereNumber('id');

        // Reports
        Route::get('/reports/route-wise', [\App\Http\Controllers\School\TransportController::class, 'reportRouteWise'])->name('reports.routes');
        Route::get('/reports/vehicle-wise', [\App\Http\Controllers\School\TransportController::class, 'reportVehicleWise'])->name('reports.vehicles');
        Route::get('/reports/driver-wise', [\App\Http\Controllers\School\TransportController::class, 'reportDriverWise'])->name('reports.drivers');
    });

    // 13. Infix Dormitory & Hostel Facility
    Route::prefix('school/dormitory')->name('school.dormitory.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\DormitoryController::class, 'index'])->name('index');
        Route::post('/dormitories', [\App\Http\Controllers\School\DormitoryController::class, 'storeDormitory'])->name('store');
        Route::post('/rooms', [\App\Http\Controllers\School\DormitoryController::class, 'storeRoom'])->name('rooms.store');
        Route::post('/assignments', [\App\Http\Controllers\School\DormitoryController::class, 'storeAssignment'])->name('assignments.store');
        Route::post('/assignments/{id}/check-in', [\App\Http\Controllers\School\DormitoryController::class, 'checkIn'])->whereNumber('id')->name('assignments.check-in');
        Route::post('/assignments/{id}/check-out', [\App\Http\Controllers\School\DormitoryController::class, 'checkOut'])->whereNumber('id')->name('assignments.check-out');
        Route::post('/assignments/{id}/cancel', [\App\Http\Controllers\School\DormitoryController::class, 'cancelAssignment'])->whereNumber('id')->name('assignments.cancel');
    });

    // 14. Infix Accounts (Income & Expenses)
    Route::middleware('role:admin')->prefix('school/accounts')->name('school.accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\SchoolAccountsController::class, 'index'])->name('index');
        Route::post('/income', [\App\Http\Controllers\School\SchoolAccountsController::class, 'storeIncome'])->name('income.store');
        Route::post('/expense', [\App\Http\Controllers\School\SchoolAccountsController::class, 'storeExpense'])->name('expense.store');
    });

    // Staff attendance, leave and payroll-period reconciliation
    Route::prefix('school/staff-attendance')->name('school.staff-attendance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StaffAttendanceController::class, 'index'])->name('index');
        Route::post('/mark', [\App\Http\Controllers\School\StaffAttendanceController::class, 'mark'])->middleware('role:admin')->name('mark');
        Route::post('/leave', [\App\Http\Controllers\School\StaffAttendanceController::class, 'applyLeave'])->name('leave.apply');
        Route::post('/leave/{id}/review', [\App\Http\Controllers\School\StaffAttendanceController::class, 'reviewLeave'])->middleware('role:admin')->whereNumber('id')->name('leave.review');
        Route::get('/leave-balances', [\App\Http\Controllers\School\StaffAttendanceController::class, 'leaveBalances'])->name('leave.balances');
        Route::post('/leave-policies', [\App\Http\Controllers\School\StaffAttendanceController::class, 'saveLeavePolicy'])->middleware('role:admin')->name('leave.policies.store');
        Route::post('/leave-encashment', [\App\Http\Controllers\School\StaffAttendanceController::class, 'requestLeaveEncashment'])->name('leave.encashment.request');
        Route::post('/leave-encashment/{id}/review', [\App\Http\Controllers\School\StaffAttendanceController::class, 'reviewLeaveEncashment'])->middleware('role:admin')->whereNumber('id')->name('leave.encashment.review');
        Route::post('/leave-policies/{id}/close', [\App\Http\Controllers\School\StaffAttendanceController::class, 'closeLeavePolicy'])->middleware('role:admin')->whereNumber('id')->name('leave.policies.close');
        Route::post('/leave-balances/adjust', [\App\Http\Controllers\School\StaffAttendanceController::class, 'adjustLeaveBalance'])->middleware('role:admin')->name('leave.balances.adjust');
    });

    // 15. Infix Human Resources & Staff Payroll
    Route::prefix('school/payroll')->name('school.payroll.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StaffPayrollController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\School\StaffPayrollController::class, 'export'])->middleware('role:admin,cashier')->name('export');
        Route::get('/{id}/payslip', [\App\Http\Controllers\School\StaffPayrollController::class, 'showPayslip'])->whereNumber('id')->name('payslip');
        Route::get('/setup', [\App\Http\Controllers\School\StaffPayrollController::class, 'setup'])->middleware('role:admin')->name('setup');
        Route::post('/structures', [\App\Http\Controllers\School\StaffPayrollController::class, 'storeStructure'])->middleware('role:admin')->name('structures.store');
        Route::post('/assignments', [\App\Http\Controllers\School\StaffPayrollController::class, 'assignStructure'])->middleware('role:admin')->name('assignments.store');
        Route::post('/statutory-rules/{id}', [\App\Http\Controllers\School\StaffPayrollController::class, 'updateRuleStatus'])->middleware('role:admin')->whereNumber('id')->name('rules.update');
        Route::post('/generate', [\App\Http\Controllers\School\StaffPayrollController::class, 'generate'])->middleware('role:admin')->name('generate');
        Route::post('/adjustments', [\App\Http\Controllers\School\StaffPayrollController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::post('/adjustments/{id}/review', [\App\Http\Controllers\School\StaffPayrollController::class, 'reviewAdjustment'])->middleware('role:admin')->whereNumber('id')->name('adjustments.review');
        Route::post('/advances', [\App\Http\Controllers\School\StaffPayrollController::class, 'storeAdvance'])->name('advances.store');
        Route::post('/advances/{id}/review', [\App\Http\Controllers\School\StaffPayrollController::class, 'reviewAdvance'])->middleware('role:admin')->whereNumber('id')->name('advances.review');
        Route::post('/advances/{id}/disburse', [\App\Http\Controllers\School\StaffPayrollController::class, 'disburseAdvance'])->middleware('role:admin')->whereNumber('id')->name('advances.disburse');
        Route::post('/approve/{id}', [\App\Http\Controllers\School\StaffPayrollController::class, 'approve'])->middleware('role:admin')->whereNumber('id')->name('approve');
        Route::post('/pay/{id}', [\App\Http\Controllers\School\StaffPayrollController::class, 'markAsPaid'])->middleware('role:admin,cashier')->whereNumber('id')->name('pay');
        Route::get('/compliance', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'index'])->middleware('role:admin')->name('compliance');
        Route::post('/compliance/reconcile', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'reconcile'])->middleware('role:admin')->name('compliance.reconcile');
        Route::post('/compliance/remittances', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'createRemittance'])->middleware('role:admin')->name('compliance.remittances.store');
        Route::post('/compliance/remittances/{id}/payment', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'recordRemittancePayment'])->middleware('role:admin')->whereNumber('id')->name('compliance.remittances.payment');
        Route::get('/compliance/register', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'exportRegister'])->middleware('role:admin')->name('compliance.register');
        Route::post('/compliance/bank-statements', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'importBankStatement'])->middleware('role:admin')->name('compliance.bank-statements.import');
        Route::post('/compliance/bank-transactions/{id}/match', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'matchBankTransaction'])->middleware('role:admin')->whereNumber('id')->name('compliance.bank-transactions.match');
        Route::post('/compliance/bank-transactions/{id}/unmatch', [\App\Http\Controllers\School\StaffPayrollComplianceController::class, 'unmatchBankTransaction'])->middleware('role:admin')->whereNumber('id')->name('compliance.bank-transactions.unmatch');
    });

    // 16. Infix Study Material & Download Center
    Route::prefix('school/study-materials')->name('school.study-materials.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StudyMaterialController::class, 'index'])->name('index');
        Route::post('/upload', [\App\Http\Controllers\School\StudyMaterialController::class, 'store'])->name('store');
        Route::delete('/{id}', [\App\Http\Controllers\School\StudyMaterialController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}/download', [\App\Http\Controllers\School\StudyMaterialController::class, 'download'])->whereNumber('id')->name('download');
    });

    // 17. Infix Front Office & Reception
    Route::prefix('school/front-office')->name('school.front-office.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\FrontOfficeController::class, 'index'])->name('index');
        Route::post('/visitor', [\App\Http\Controllers\School\FrontOfficeController::class, 'storeVisitor'])->name('visitor.store');
        Route::post('/call', [\App\Http\Controllers\School\FrontOfficeController::class, 'storeCall'])->name('call.store');
        Route::post('/postal', [\App\Http\Controllers\School\FrontOfficeController::class, 'storePostal'])->name('postal.store');
    });

    // Academic session setup is restricted to administrators.
    Route::prefix('school/academic-years')->name('school.academic-years.')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\AcademicYearController::class, 'index'])->name('index');
        Route::post('/clone', [\App\Http\Controllers\School\AcademicYearController::class, 'cloneSession'])->name('clone');
        Route::post('/activate', [\App\Http\Controllers\School\AcademicYearController::class, 'activate'])->name('activate');
    });

    Route::prefix('school/student-exits')->name('school.student-exits.')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StudentExitController::class, 'index'])->name('index');
        Route::post('/{withdrawalId}/issue-certificate', [\App\Http\Controllers\School\StudentExitController::class, 'issue'])->whereNumber('withdrawalId')->name('issue');
        Route::get('/certificate/{id}', [\App\Http\Controllers\School\StudentExitController::class, 'certificate'])->whereNumber('id')->name('certificate');
    });

    Route::prefix('school/progression')->name('school.progression.')->middleware('role:admin')->group(function () {
        Route::get('/policy', [\App\Http\Controllers\School\AcademicProgressionController::class, 'policy'])->name('policy');
        Route::post('/policy', [\App\Http\Controllers\School\AcademicProgressionController::class, 'savePolicy'])->name('policy.save');
        Route::get('/bulk-preview', [\App\Http\Controllers\School\AcademicProgressionController::class, 'preview'])->name('preview');
        Route::post('/bulk-promote', [\App\Http\Controllers\School\AcademicProgressionController::class, 'bulkPromote'])->name('bulk-promote');
        Route::get('/audit.csv', [\App\Http\Controllers\School\AcademicProgressionController::class, 'auditCsv'])->name('audit');
        Route::post('/graduate', [\App\Http\Controllers\School\AcademicProgressionController::class, 'graduate'])->name('graduate');
        Route::get('/certificate/{id}', [\App\Http\Controllers\School\AcademicProgressionController::class, 'certificate'])->whereNumber('id')->name('certificate');
    });

    // 18. Infix Student Promotion & Academic Session Rollover
    Route::prefix('school/promotion')->name('school.promotion.')->group(function () {
        Route::get('/', [\App\Http\Controllers\School\StudentPromotionController::class, 'index'])->name('index');
        Route::post('/promote', [\App\Http\Controllers\School\StudentPromotionController::class, 'promote'])->name('promote');
        Route::post('/transfer', [\App\Http\Controllers\School\StudentPromotionController::class, 'transfer'])->name('transfer');
        Route::post('/readmit', [\App\Http\Controllers\School\StudentPromotionController::class, 'readmit'])->name('readmit');
    });
});
