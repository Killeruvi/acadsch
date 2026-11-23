<?php

use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\School\AcademicProgressionController;
use App\Http\Controllers\School\AcademicYearController;
use App\Http\Controllers\School\AccountManagementController;
use App\Http\Controllers\School\AdmissionEnquiryController;
use App\Http\Controllers\School\DormitoryController;
use App\Http\Controllers\School\FrontOfficeController;
use App\Http\Controllers\School\LibraryController;
use App\Http\Controllers\School\SchoolAccountsController;
use App\Http\Controllers\School\SchoolAttendanceController;
use App\Http\Controllers\School\SchoolClassController;
use App\Http\Controllers\School\SchoolExaminationController;
use App\Http\Controllers\School\SchoolFeeController;
use App\Http\Controllers\School\SchoolHolidayController;
use App\Http\Controllers\School\SchoolParentController;
use App\Http\Controllers\School\SchoolSectionController;
use App\Http\Controllers\School\SchoolStreamController;
use App\Http\Controllers\School\SchoolStudentController;
use App\Http\Controllers\School\SchoolSubjectController;
use App\Http\Controllers\School\SchoolTimetableController;
use App\Http\Controllers\School\StaffAttendanceController;
use App\Http\Controllers\School\StaffEmploymentController;
use App\Http\Controllers\School\StaffFinalSettlementController;
use App\Http\Controllers\School\StaffPayrollComplianceController;
use App\Http\Controllers\School\StaffPayrollController;
use App\Http\Controllers\School\StaffSelfServiceController;
use App\Http\Controllers\School\StudentExitController;
use App\Http\Controllers\School\StudentPromotionController;
use App\Http\Controllers\School\StudyMaterialController;
use App\Http\Controllers\School\TeacherAssignmentController;
use App\Http\Controllers\School\TransportController;
use App\Http\Controllers\StaffDirectoryController;
use App\Http\Controllers\VirtualIdCardController;
use App\Http\Controllers\WebScheduleController;
use Illuminate\Support\Facades\Route;

// Public Admission Inquiries & Verification
Route::post('/admissions/enquiry', [AdmissionEnquiryController::class, 'storePublic'])
    ->middleware('throttle:5,10')->name('admissions.enquiry.store');

Route::get('/verify/id-card/{hash}', [SchoolStudentController::class, 'verifyIdCard'])
    ->where('hash', '[0-9]+\\.[a-fA-F0-9]{64}')->middleware('throttle:30,1')->name('school.students.verify-id');

Route::get('/school/exams/admit-card/verify/{token}', [SchoolExaminationController::class, 'verifyAdmitCard'])
    ->middleware('throttle:60,1')->name('school.exams.admit-card.verify');

// Authenticated School ERP Workflows
Route::middleware(['auth', 'student.account.active'])->group(function () {
    // Virtual ID Card
    Route::get('/id-card/{userId?}', [VirtualIdCardController::class, 'show'])->name('idcard.show');

    // School Calendar & Holidays
    Route::get('/school/holidays', [SchoolHolidayController::class, 'index'])->name('school.holidays.index');
    Route::redirect('/lesson-planner/holidays', '/school/holidays');
    Route::redirect('/lesson-planner', '/school/holidays');

    // 1. Unified Fee Management (Canonical SchoolFeeController)
    Route::get('/fees', [SchoolFeeController::class, 'index'])->name('fees.index');
    Route::get('/fees/{id}', [SchoolFeeController::class, 'showInvoice'])->name('fees.show');
    Route::post('/fees/{id}/pay', [SchoolFeeController::class, 'payOnline'])->name('fees.pay')->middleware(['role:admin,cashier', 'throttle:30,1']);
    Route::post('/fees/{id}/reminder', [SchoolFeeController::class, 'sendReminder'])->name('fees.reminder')->middleware(['role:admin,cashier', 'throttle:10,1']);
    Route::get('/fees/receipt/{paymentId}', [SchoolFeeController::class, 'receipt'])->name('fees.receipt');
    Route::get('/school/fees', [SchoolFeeController::class, 'index'])->name('school.fees.index');
    Route::get('/school/fees/receipt/{paymentId}', [SchoolFeeController::class, 'receipt'])->name('school.fees.receipt')->whereNumber('paymentId');
    Route::post('/school/fees/invoices/{invoiceId}/initiate-upi', [SchoolFeeController::class, 'initiateUpiPayment'])->name('school.fees.initiate-upi')->whereNumber('invoiceId');

    Route::middleware('role:admin,cashier')->group(function () {
        Route::post('/school/fees/invoices/{invoiceId}/pay', [SchoolFeeController::class, 'recordPayment'])->name('school.fees.pay')->whereNumber('invoiceId');
        Route::post('/school/fees/payments/{paymentId}/verify-upi', [SchoolFeeController::class, 'verifyUpiPayment'])->name('school.fees.verify-upi')->whereNumber('paymentId');
        Route::post('/school/fees/bulk-payments', [SchoolFeeController::class, 'bulkRecordPayments'])->name('school.fees.bulk-payments');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/fees', [SchoolFeeController::class, 'store'])->name('fees.store');
        Route::post('/fees/generate-monthly', [SchoolFeeController::class, 'generateMonthly'])->name('fees.generate-monthly');
        Route::post('/fees/monthly-config', [SchoolFeeController::class, 'updateMonthlyConfig'])->name('fees.monthly-config');
        Route::delete('/fees/{id}', [SchoolFeeController::class, 'destroy'])->name('fees.destroy')->whereNumber('id');
        Route::post('/school/fees/structures', [SchoolFeeController::class, 'storeStructure'])->name('school.fees.structures.store');
        Route::delete('/school/fees/structures/{id}', [SchoolFeeController::class, 'destroyStructure'])->name('school.fees.structures.destroy')->whereNumber('id');
        Route::post('/school/fees/generate-invoices', [SchoolFeeController::class, 'generateClassInvoices'])->name('school.fees.generate-invoices');
        Route::post('/school/fees/invoices/{invoiceId}/waiver', [SchoolFeeController::class, 'approveWaiver'])->name('school.fees.waiver')->whereNumber('invoiceId');
        Route::post('/school/fees/payments/{paymentId}/refund', [SchoolFeeController::class, 'processRefund'])->name('school.fees.refund')->whereNumber('paymentId');
    });

    // 2. Unified Attendance System (Canonical SchoolAttendanceController)
    Route::get('/attendance', [SchoolAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [SchoolAttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/school/attendance', [SchoolAttendanceController::class, 'index'])->name('school.attendance.index');
    Route::get('/school/attendance/register/{classId}', [SchoolAttendanceController::class, 'register'])->name('school.attendance.register')->whereNumber('classId');

    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leaves.index');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leaves.store');
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy')->whereNumber('id');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/attendance', [SchoolAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{id}/edit', [SchoolAttendanceController::class, 'edit'])->name('attendance.edit')->whereNumber('id');
        Route::match(['post', 'put', 'patch'], '/attendance/{id}', [SchoolAttendanceController::class, 'update'])->name('attendance.update')->whereNumber('id');
        Route::delete('/attendance/{id}', [SchoolAttendanceController::class, 'destroy'])->name('attendance.destroy')->whereNumber('id');
        Route::match(['post', 'patch'], '/leave-requests/{id}/review', [LeaveRequestController::class, 'review'])->name('leaves.review')->whereNumber('id');
        Route::get('/school/attendance/classes/{classId}', [SchoolAttendanceController::class, 'take'])->name('school.attendance.take')->whereNumber('classId');
        Route::post('/school/attendance/classes/{classId}', [SchoolAttendanceController::class, 'store'])->name('school.attendance.store')->whereNumber('classId');
    });

    // 3. Unified Student Directory (Canonical SchoolStudentController)
    Route::get('/school/students/{id}/id-card', [SchoolStudentController::class, 'idCard'])->name('school.students.id-card')->whereNumber('id');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/students', [SchoolStudentController::class, 'index'])->name('students.index');
        Route::get('/students/export-csv', [SchoolStudentController::class, 'exportCsv'])->name('students.export-csv');
        Route::get('/students/grab-json', [SchoolStudentController::class, 'grabJson'])->name('students.grab-json');
        Route::get('/students/roster-print', [SchoolStudentController::class, 'rosterPrint'])->name('students.roster-print');
        Route::get('/students/{student}', [SchoolStudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [SchoolStudentController::class, 'edit'])->name('students.edit');
        Route::match(['put', 'patch', 'post'], '/students/{student}', [SchoolStudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [SchoolStudentController::class, 'destroy'])->name('students.destroy');

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

    // 4. Staff Directory & HR Management
    Route::middleware('role:teacher,admin')->group(function () {
        Route::get('/staff', [StaffDirectoryController::class, 'index'])->name('staff.index');
        Route::get('/staff/export-csv', [StaffDirectoryController::class, 'exportCsv'])->name('staff.export-csv');
        Route::get('/staff/{staff}', [StaffDirectoryController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [StaffDirectoryController::class, 'edit'])->name('staff.edit');
        Route::match(['put', 'patch', 'post'], '/staff/{staff}', [StaffDirectoryController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffDirectoryController::class, 'destroy'])->name('staff.destroy');
        Route::get('/staff/{staff}/employment', [StaffEmploymentController::class, 'show'])->whereNumber('staff')->name('staff.employment.show');
        Route::post('/staff/{staff}/employment/profile', [StaffEmploymentController::class, 'saveProfile'])->whereNumber('staff')->name('staff.employment.profile');
        Route::post('/staff/{staff}/employment/contracts', [StaffEmploymentController::class, 'uploadContract'])->whereNumber('staff')->name('staff.employment.contracts.store');
        Route::get('/staff/{staff}/employment/contracts/{contract}/download', [StaffEmploymentController::class, 'downloadContract'])->whereNumber('staff')->whereNumber('contract')->name('staff.employment.contracts.download');
        Route::post('/staff/{staff}/employment/documents', [StaffEmploymentController::class, 'uploadDocument'])->whereNumber('staff')->name('staff.employment.documents.store');
        Route::get('/staff/{staff}/employment/documents/{document}/download', [StaffEmploymentController::class, 'downloadDocument'])->whereNumber('staff')->whereNumber('document')->name('staff.employment.documents.download');
        Route::post('/staff/{staff}/employment/separate', [StaffEmploymentController::class, 'separate'])->whereNumber('staff')->name('staff.employment.separate');
        Route::post('/staff/{staff}/employment/onboarding/{task}', [StaffEmploymentController::class, 'updateOnboardingTask'])->whereNumber('staff')->whereNumber('task')->name('staff.employment.onboarding.update');

        // Self-Service
        Route::prefix('staff-self-service')->name('staff.self-service.')->group(function () {
            Route::get('/', [StaffSelfServiceController::class, 'index'])->name('index');
            Route::post('/tax-declarations', [StaffSelfServiceController::class, 'saveDeclaration'])->name('tax.save');
            Route::post('/tax-declarations/{declaration}/submit', [StaffSelfServiceController::class, 'submitDeclaration'])->whereNumber('declaration')->name('tax.submit');
            Route::get('/documents/{kind}/{id}', [StaffSelfServiceController::class, 'downloadDocument'])->whereNumber('id')->name('download');
        });

        // Final settlements
        Route::get('/hr/final-settlements', [StaffFinalSettlementController::class, 'index'])->name('school.staff.final-settlements.index');
        Route::post('/hr/final-settlements', [StaffFinalSettlementController::class, 'create'])->name('school.staff.final-settlements.create');
        Route::post('/hr/final-settlements/{settlement}/items', [StaffFinalSettlementController::class, 'addItem'])->whereNumber('settlement')->name('school.staff.final-settlements.items.store');
        Route::delete('/hr/final-settlements/{settlement}/items/{item}', [StaffFinalSettlementController::class, 'deleteItem'])->whereNumber('settlement')->whereNumber('item')->name('school.staff.final-settlements.items.destroy');
        Route::post('/hr/final-settlements/{settlement}/approve', [StaffFinalSettlementController::class, 'approve'])->whereNumber('settlement')->name('school.staff.final-settlements.approve');
        Route::post('/hr/final-settlements/{settlement}/payments', [StaffFinalSettlementController::class, 'recordPayment'])->whereNumber('settlement')->name('school.staff.final-settlements.payments.store');
    });

    Route::get('/school/hr/tax-declarations', [StaffSelfServiceController::class, 'reviewQueue'])->middleware('role:admin')->name('school.hr.tax-declarations.index');
    Route::post('/school/hr/tax-declarations/{declaration}/review', [StaffSelfServiceController::class, 'reviewDeclaration'])->middleware('role:admin')->whereNumber('declaration')->name('school.hr.tax-declarations.review');

    // 5. Classes, Sections, Streams & Subjects
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

        Route::post('/school/sections', [SchoolSectionController::class, 'store'])->name('school.sections.store');
        Route::patch('/school/sections/{id}', [SchoolSectionController::class, 'update'])->name('school.sections.update')->whereNumber('id');
        Route::patch('/school/sections/{id}/toggle', [SchoolSectionController::class, 'toggle'])->name('school.sections.toggle')->whereNumber('id');
        Route::delete('/school/sections/{id}', [SchoolSectionController::class, 'destroy'])->name('school.sections.destroy')->whereNumber('id');

        Route::post('/school/streams', [SchoolStreamController::class, 'store'])->name('school.streams.store');
        Route::patch('/school/streams/{id}', [SchoolStreamController::class, 'update'])->name('school.streams.update')->whereNumber('id');
        Route::patch('/school/streams/{id}/toggle', [SchoolStreamController::class, 'toggle'])->name('school.streams.toggle')->whereNumber('id');

        Route::post('/school/subjects', [SchoolSubjectController::class, 'store'])->name('school.subjects.store');
        Route::patch('/school/subjects/{id}', [SchoolSubjectController::class, 'update'])->name('school.subjects.update')->whereNumber('id');
        Route::patch('/school/subjects/{id}/activate', [SchoolSubjectController::class, 'activate'])->name('school.subjects.activate')->whereNumber('id');
        Route::delete('/school/subjects/{id}', [SchoolSubjectController::class, 'destroy'])->name('school.subjects.destroy')->whereNumber('id');
    });

    // 6. Admissions Enquiry Management
    Route::middleware('role:admin')->prefix('/school/admissions')->name('school.admissions.')->group(function () {
        Route::get('/enquiries', [AdmissionEnquiryController::class, 'index'])->name('enquiries.index');
        Route::patch('/enquiries/{enquiry}', [AdmissionEnquiryController::class, 'update'])->name('enquiries.update');
    });

    // 7. Examinations & Report Cards
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

    // 8. Timetable & Routine
    Route::get('/schedule', [WebScheduleController::class, 'index'])->name('schedules.index');
    Route::redirect('/schedules', '/schedule');
    Route::get('/school/timetable', [SchoolTimetableController::class, 'index'])->name('school.timetable.index');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/schedules', [WebScheduleController::class, 'store'])->name('schedules.store');
        Route::match(['put', 'patch', 'post'], '/schedules/{schedule}', [WebScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [WebScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/toggle-cancel', [WebScheduleController::class, 'toggleCancel'])->name('schedules.toggle-cancel');
    });

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

    // 9. Parent Portal
    Route::middleware('role:parent,admin')->group(function () {
        Route::get('/parent/dashboard', [SchoolParentController::class, 'dashboard'])->name('school.parent.dashboard');
    });

    // 10. Teacher & Subject Assignment
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

    // 11. Library Management
    Route::prefix('school/library')->name('school.library.')->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');
        Route::post('/books', [LibraryController::class, 'storeBook'])->name('books.store');
        Route::post('/issue', [LibraryController::class, 'issueBook'])->name('issue');
        Route::post('/return/{issueId}', [LibraryController::class, 'returnBook'])->name('return')->whereNumber('issueId');
        Route::post('/renew/{issueId}', [LibraryController::class, 'renewBook'])->name('renew')->whereNumber('issueId');
        Route::post('/reserve', [LibraryController::class, 'reserveBook'])->name('reserve');
    });

    // 12. Transport Management
    Route::prefix('school/transport')->name('school.transport.')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('index');
        Route::post('/vehicles', [TransportController::class, 'storeVehicle'])->name('vehicles.store');
        Route::put('/vehicles/{id}', [TransportController::class, 'updateVehicle'])->name('vehicles.update')->whereNumber('id');
        Route::delete('/vehicles/{id}', [TransportController::class, 'destroyVehicle'])->name('vehicles.destroy')->whereNumber('id');
        Route::post('/vehicles/{id}/status', [TransportController::class, 'updateVehicleStatus'])->name('vehicles.status')->whereNumber('id');
        Route::post('/routes', [TransportController::class, 'storeRoute'])->name('routes.store');
        Route::put('/routes/{id}', [TransportController::class, 'updateRoute'])->name('routes.update')->whereNumber('id');
        Route::delete('/routes/{id}', [TransportController::class, 'destroyRoute'])->name('routes.destroy')->whereNumber('id');
        Route::post('/stops', [TransportController::class, 'storeStop'])->name('stops.store');
        Route::put('/stops/{id}', [TransportController::class, 'updateStop'])->name('stops.update')->whereNumber('id');
        Route::delete('/stops/{id}', [TransportController::class, 'destroyStop'])->name('stops.destroy')->whereNumber('id');
        Route::post('/staff', [TransportController::class, 'storeStaff'])->name('staff.store');
        Route::put('/staff/{id}', [TransportController::class, 'updateStaff'])->name('staff.update')->whereNumber('id');
        Route::delete('/staff/{id}', [TransportController::class, 'destroyStaff'])->name('staff.destroy')->whereNumber('id');
        Route::post('/staff/{id}/reassign', [TransportController::class, 'reassignStaff'])->name('staff.reassign')->whereNumber('id');
        Route::post('/students/assign', [TransportController::class, 'assignStudent'])->name('students.assign');
        Route::post('/students/bulk-assign', [TransportController::class, 'bulkAssignStudents'])->name('students.bulk_assign');
        Route::post('/students/{id}/opt-out', [TransportController::class, 'optOutStudent'])->name('students.opt_out')->whereNumber('id');
        Route::post('/students/{id}/opt-in', [TransportController::class, 'optInStudent'])->name('students.opt_in')->whereNumber('id');
        Route::delete('/students/{id}', [TransportController::class, 'destroyAssignment'])->name('students.destroy')->whereNumber('id');
        Route::post('/fees/generate', [TransportController::class, 'generateMonthlyFees'])->name('fees.generate');
        Route::post('/trips', [TransportController::class, 'storeTrip'])->name('trips.store');
        Route::post('/trips/{id}/end', [TransportController::class, 'endTrip'])->name('trips.end')->whereNumber('id');
        Route::post('/passengers/{id}/status', [TransportController::class, 'updatePassengerStatus'])->name('passengers.status')->whereNumber('id');
        Route::get('/reports/route-wise', [TransportController::class, 'reportRouteWise'])->name('reports.routes');
        Route::get('/reports/vehicle-wise', [TransportController::class, 'reportVehicleWise'])->name('reports.vehicles');
        Route::get('/reports/driver-wise', [TransportController::class, 'reportDriverWise'])->name('reports.drivers');
    });

    // 13. Dormitory Management
    Route::prefix('school/dormitory')->name('school.dormitory.')->group(function () {
        Route::get('/', [DormitoryController::class, 'index'])->name('index');
        Route::post('/dormitories', [DormitoryController::class, 'storeDormitory'])->name('store');
        Route::post('/rooms', [DormitoryController::class, 'storeRoom'])->name('rooms.store');
        Route::post('/assignments', [DormitoryController::class, 'storeAssignment'])->name('assignments.store');
        Route::post('/assignments/{id}/check-in', [DormitoryController::class, 'checkIn'])->whereNumber('id')->name('assignments.check-in');
        Route::post('/assignments/{id}/check-out', [DormitoryController::class, 'checkOut'])->whereNumber('id')->name('assignments.check-out');
        Route::post('/assignments/{id}/cancel', [DormitoryController::class, 'cancelAssignment'])->whereNumber('id')->name('assignments.cancel');
    });

    // 14. Accounts (Income & Expenses)
    Route::middleware('role:admin')->prefix('school/accounts')->name('school.accounts.')->group(function () {
        Route::get('/', [SchoolAccountsController::class, 'index'])->name('index');
        Route::post('/income', [SchoolAccountsController::class, 'storeIncome'])->name('income.store');
        Route::post('/expense', [SchoolAccountsController::class, 'storeExpense'])->name('expense.store');
    });

    // 15. Staff Attendance & Leave Balances
    Route::prefix('school/staff-attendance')->name('school.staff-attendance.')->group(function () {
        Route::get('/', [StaffAttendanceController::class, 'index'])->name('index');
        Route::post('/mark', [StaffAttendanceController::class, 'mark'])->middleware('role:admin')->name('mark');
        Route::post('/leave', [StaffAttendanceController::class, 'applyLeave'])->name('leave.apply');
        Route::post('/leave/{id}/review', [StaffAttendanceController::class, 'reviewLeave'])->middleware('role:admin')->whereNumber('id')->name('leave.review');
        Route::get('/leave-balances', [StaffAttendanceController::class, 'leaveBalances'])->name('leave.balances');
        Route::post('/leave-policies', [StaffAttendanceController::class, 'saveLeavePolicy'])->middleware('role:admin')->name('leave.policies.store');
        Route::post('/leave-encashment', [StaffAttendanceController::class, 'requestLeaveEncashment'])->name('leave.encashment.request');
        Route::post('/leave-encashment/{id}/review', [StaffAttendanceController::class, 'reviewLeaveEncashment'])->middleware('role:admin')->whereNumber('id')->name('leave.encashment.review');
        Route::post('/leave-policies/{id}/close', [StaffAttendanceController::class, 'closeLeavePolicy'])->middleware('role:admin')->name('leave.policies.close');
        Route::post('/leave-balances/adjust', [StaffAttendanceController::class, 'adjustLeaveBalance'])->middleware('role:admin')->name('leave.balances.adjust');
    });

    // 16. Staff Payroll
    Route::prefix('school/payroll')->name('school.payroll.')->group(function () {
        Route::get('/', [StaffPayrollController::class, 'index'])->name('index');
        Route::get('/export', [StaffPayrollController::class, 'export'])->middleware('role:admin,cashier')->name('export');
        Route::get('/{id}/payslip', [StaffPayrollController::class, 'showPayslip'])->whereNumber('id')->name('payslip');
        Route::get('/setup', [StaffPayrollController::class, 'setup'])->middleware('role:admin')->name('setup');
        Route::post('/structures', [StaffPayrollController::class, 'storeStructure'])->middleware('role:admin')->name('structures.store');
        Route::post('/assignments', [StaffPayrollController::class, 'assignStructure'])->middleware('role:admin')->name('assignments.store');
        Route::post('/statutory-rules/{id}', [StaffPayrollController::class, 'updateRuleStatus'])->middleware('role:admin')->whereNumber('id')->name('rules.update');
        Route::post('/generate', [StaffPayrollController::class, 'generate'])->middleware('role:admin')->name('generate');
        Route::post('/adjustments', [StaffPayrollController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::post('/adjustments/{id}/review', [StaffPayrollController::class, 'reviewAdjustment'])->middleware('role:admin')->whereNumber('id')->name('adjustments.review');
        Route::post('/advances', [StaffPayrollController::class, 'storeAdvance'])->name('advances.store');
        Route::post('/advances/{id}/review', [StaffPayrollController::class, 'reviewAdvance'])->middleware('role:admin')->whereNumber('id')->name('advances.review');
        Route::post('/advances/{id}/disburse', [StaffPayrollController::class, 'disburseAdvance'])->middleware('role:admin')->whereNumber('id')->name('advances.disburse');
        Route::post('/approve/{id}', [StaffPayrollController::class, 'approve'])->middleware('role:admin')->whereNumber('id')->name('approve');
        Route::post('/pay/{id}', [StaffPayrollController::class, 'markAsPaid'])->middleware('role:admin,cashier')->whereNumber('id')->name('pay');
        Route::get('/compliance', [StaffPayrollComplianceController::class, 'index'])->middleware('role:admin')->name('compliance');
        Route::post('/compliance/reconcile', [StaffPayrollComplianceController::class, 'reconcile'])->middleware('role:admin')->name('compliance.reconcile');
        Route::post('/compliance/remittances', [StaffPayrollComplianceController::class, 'createRemittance'])->middleware('role:admin')->name('compliance.remittances.store');
        Route::post('/compliance/remittances/{id}/payment', [StaffPayrollComplianceController::class, 'recordRemittancePayment'])->middleware('role:admin')->whereNumber('id')->name('compliance.remittances.payment');
        Route::get('/compliance/register', [StaffPayrollComplianceController::class, 'exportRegister'])->middleware('role:admin')->name('compliance.register');
        Route::post('/compliance/bank-statements', [StaffPayrollComplianceController::class, 'importBankStatement'])->middleware('role:admin')->name('compliance.bank-statements.import');
        Route::post('/compliance/bank-transactions/{id}/match', [StaffPayrollComplianceController::class, 'matchBankTransaction'])->middleware('role:admin')->whereNumber('id')->name('compliance.bank-transactions.match');
        Route::post('/compliance/bank-transactions/{id}/unmatch', [StaffPayrollComplianceController::class, 'unmatchBankTransaction'])->middleware('role:admin')->whereNumber('id')->name('compliance.bank-transactions.unmatch');
    });

    // 17. Study Material & Download Center
    Route::prefix('school/study-materials')->name('school.study-materials.')->group(function () {
        Route::get('/', [StudyMaterialController::class, 'index'])->name('index');
        Route::post('/upload', [StudyMaterialController::class, 'store'])->name('store');
        Route::delete('/{id}', [StudyMaterialController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}/download', [StudyMaterialController::class, 'download'])->whereNumber('id')->name('download');
    });

    // 18. Front Office & Reception
    Route::prefix('school/front-office')->name('school.front-office.')->group(function () {
        Route::get('/', [FrontOfficeController::class, 'index'])->name('index');
        Route::post('/visitor', [FrontOfficeController::class, 'storeVisitor'])->name('visitor.store');
        Route::post('/call', [FrontOfficeController::class, 'storeCall'])->name('call.store');
        Route::post('/postal', [FrontOfficeController::class, 'storePostal'])->name('postal.store');
    });

    // 19. Academic Years & Sessions Setup
    Route::prefix('school/academic-years')->name('school.academic-years.')->middleware('role:admin')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::post('/clone', [AcademicYearController::class, 'cloneSession'])->name('clone');
        Route::post('/activate', [AcademicYearController::class, 'activate'])->name('activate');
    });

    // 20. Student Exits & Transfer Certificates
    Route::prefix('school/student-exits')->name('school.student-exits.')->middleware('role:admin')->group(function () {
        Route::get('/', [StudentExitController::class, 'index'])->name('index');
        Route::post('/{withdrawalId}/issue-certificate', [StudentExitController::class, 'issue'])->whereNumber('withdrawalId')->name('issue');
        Route::get('/certificate/{id}', [StudentExitController::class, 'certificate'])->whereNumber('id')->name('certificate');
    });

    // 21. Academic Progression & Graduation
    Route::prefix('school/progression')->name('school.progression.')->middleware('role:admin')->group(function () {
        Route::get('/policy', [AcademicProgressionController::class, 'policy'])->name('policy');
        Route::post('/policy', [AcademicProgressionController::class, 'savePolicy'])->name('policy.save');
        Route::get('/bulk-preview', [AcademicProgressionController::class, 'preview'])->name('preview');
        Route::post('/bulk-promote', [AcademicProgressionController::class, 'bulkPromote'])->name('bulk-promote');
        Route::get('/audit.csv', [AcademicProgressionController::class, 'auditCsv'])->name('audit');
        Route::post('/graduate', [AcademicProgressionController::class, 'graduate'])->name('graduate');
        Route::get('/certificate/{id}', [AcademicProgressionController::class, 'certificate'])->whereNumber('id')->name('certificate');
    });

    // 22. Student Promotion & Session Rollover
    Route::prefix('school/promotion')->name('school.promotion.')->group(function () {
        Route::get('/', [StudentPromotionController::class, 'index'])->name('index');
        Route::post('/promote', [StudentPromotionController::class, 'promote'])->name('promote');
        Route::post('/transfer', [StudentPromotionController::class, 'transfer'])->name('transfer');
        Route::post('/readmit', [StudentPromotionController::class, 'readmit'])->name('readmit');
    });
});
