<?php

use App\Http\Controllers\AcademyEventController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BloomsController;
use App\Http\Controllers\CertificateBadgeController;
use App\Http\Controllers\CourseAssessmentController;
use App\Http\Controllers\DppController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LiveQuizController;
use App\Http\Controllers\ProjectCaseStudyController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VirtualClassController;
use App\Http\Controllers\WebAssignmentController;
use App\Http\Controllers\WebCourseController;
use Illuminate\Support\Facades\Route;

// Public Courses & Curriculum Directory
Route::get('/courses', [WebCourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{id}', [WebCourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{id}/handouts/{filename}', [WebCourseController::class, 'downloadHandoutPdf'])->name('courses.handout.pdf');
Route::get('/courses/{id}/lessons/{lessonNumber}/pdf', [WebCourseController::class, 'lessonPdf'])->name('courses.lesson.pdf');

// Authenticated LMS Features
Route::middleware(['auth', 'student.account.active'])->group(function () {
    // Student Batch Hub
    Route::get('/my-batch', [BatchController::class, 'index'])->name('my-batch');
    Route::get('/my-batch/select', [BatchController::class, 'selectClassBatch'])->name('batches.select');
    Route::post('/my-batch/select', [BatchController::class, 'storeSelection'])->name('batches.select.store');
    Route::post('/my-batch/request-change', [BatchController::class, 'requestChange'])->name('batches.request-change');
    Route::get('/my-batch/{batch}', [BatchController::class, 'subjects'])->name('batches.subjects')->whereNumber('batch');
    Route::get('/my-batch/{batch}/subjects/{subject}', [BatchController::class, 'chapters'])->name('batches.chapters')->whereNumber('batch')->whereNumber('subject');
    Route::get('/my-batch/{batch}/subjects/{subject}/chapters/{chapter?}', [BatchController::class, 'learn'])->name('batches.learn')->whereNumber('batch')->whereNumber('subject');

    // Faculty Batch Operations
    Route::middleware('role:teacher,admin')->group(function () {
        Route::post('/my-batch/create', [BatchController::class, 'storeBatch'])->name('batches.create');
        Route::post('/my-batch/requests/{batchChangeRequest}/review', [BatchController::class, 'reviewRequest'])->name('batches.requests.review');
        Route::match(['put', 'patch', 'post'], '/my-batch/{batch}', [BatchController::class, 'updateBatch'])->name('batches.update')->whereNumber('batch');
        Route::delete('/my-batch/{batch}', [BatchController::class, 'destroyBatch'])->name('batches.destroy')->whereNumber('batch');
        Route::post('/my-batch/{batch}/subjects/{subject}/chapters/{chapter}/notes', [BatchController::class, 'uploadNote'])->name('batches.notes.upload')->whereNumber('batch')->whereNumber('subject')->whereNumber('chapter');
        Route::delete('/my-batch/notes/{note}', [BatchController::class, 'deleteNote'])->name('batches.notes.delete');
    });

    // Bloom's Taxonomy Daily Assignment System
    Route::get('/blooms', [BloomsController::class, 'index'])->name('blooms.index');
    Route::get('/blooms/lessons/{id}', [BloomsController::class, 'showLesson'])->name('blooms.lesson');
    Route::post('/blooms/assignments/{id}/submit', [BloomsController::class, 'submitAssignment'])->middleware('throttle:10,1')->name('blooms.assignment.submit');
    Route::get('/blooms/assignments/{id}/attachment/{type?}', [BloomsController::class, 'downloadAttachment'])->name('blooms.assignment.download-attachment');
    Route::get('/blooms/curriculum-data/{classId}', [BloomsController::class, 'getCurriculumData'])->name('blooms.curriculum.data');

    // Syllabus Section
    Route::get('/syllabus', [SyllabusController::class, 'index'])->name('syllabus.index');
    Route::get('/syllabus/tracker', [SyllabusController::class, 'tracker'])->name('syllabus.tracker');
    Route::get('/syllabus/{id}', [SyllabusController::class, 'show'])->name('syllabus.show')->whereNumber('id');
    Route::match(['post', 'patch'], '/syllabus/videos/{id}/toggle-watched', [SyllabusController::class, 'toggleVideoWatched'])->name('syllabus.videos.toggle-watched');

    // Video Lectures Hub
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('videos.show');
    Route::match(['post', 'patch'], '/videos/{id}/toggle-watched', [VideoController::class, 'toggleWatched'])->name('videos.toggle-watched');

    // Daily Practice Problems (DPP) & Timed Tests
    Route::get('/dpp', [DppController::class, 'index'])->name('dpp.index');
    Route::get('/dpp/{id}', [DppController::class, 'show'])->name('dpp.show')->whereNumber('id');
    Route::post('/dpp/{id}/submit', [DppController::class, 'submit'])->name('dpp.submit')->whereNumber('id');
    Route::get('/question-bank/api', [DppController::class, 'apiQuestionBank'])->name('question-bank.api');

    // Daily Homework System
    Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
    Route::post('/homework/{id}/submit', [HomeworkController::class, 'submit'])->name('homework.submit');
    Route::get('/homework/{id}/attachment/{type?}', [HomeworkController::class, 'downloadAttachment'])->name('homework.download-attachment');

    // Course Enrollment & Assessments
    Route::post('/courses/{id}/enroll', [WebCourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{courseId}/assessments/{assessmentId}', [CourseAssessmentController::class, 'show'])->name('courses.assessments.show');
    Route::post('/courses/{courseId}/assessments/{assessmentId}/submit', [CourseAssessmentController::class, 'submit'])->name('courses.assessments.submit');

    // Assignments
    Route::get('/assignments', [WebAssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{id}', [WebAssignmentController::class, 'show'])->name('assignments.show');
    Route::get('/assignments/{id}/attachment', [WebAssignmentController::class, 'downloadAttachment'])->name('assignments.attachment');
    Route::post('/assignments/{id}/submit', [WebAssignmentController::class, 'submit'])->name('assignments.submit');

    // Live Quizzes Arena
    Route::get('/quizzes', [LiveQuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}', [LiveQuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/submit', [LiveQuizController::class, 'submit'])->name('quizzes.submit');

    // Case Studies & Projects
    Route::get('/projects', [ProjectCaseStudyController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectCaseStudyController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/submit', [ProjectCaseStudyController::class, 'submit'])->name('projects.submit');

    // School Events
    Route::get('/events', [AcademyEventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [AcademyEventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/register', [AcademyEventController::class, 'register'])->name('events.register');
    Route::post('/events/{event}/unregister', [AcademyEventController::class, 'unregister'])->name('events.unregister');

    // Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Certificates & Badges
    Route::get('/certificates', [CertificateBadgeController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateBadgeController::class, 'showCertificate'])->name('certificates.show');

    // Virtual Classes
    Route::get('/virtual-classes', [VirtualClassController::class, 'index'])->name('virtual-classes.index');
    Route::get('/virtual-classes/{virtualClass}', [VirtualClassController::class, 'show'])->name('virtual-classes.show');
    Route::get('/virtual-classes/{virtualClass}/join', [VirtualClassController::class, 'join'])->name('virtual-classes.join');

    // Teacher & Admin LMS Management
    Route::middleware('role:teacher,admin')->group(function () {
        // Blooms CSV & Management
        Route::get('/blooms/sample-students-csv', [BloomsController::class, 'sampleStudentCsv'])->name('blooms.sample-students-csv');
        Route::get('/blooms/export-students-csv/{classId}', [BloomsController::class, 'exportStudentsCsv'])->name('blooms.export-students-csv');
        Route::get('/blooms/sample-tasks-csv', [BloomsController::class, 'sampleTasksCsv'])->name('blooms.sample-tasks-csv');
        Route::get('/blooms/lessons/{id}/export-csv', [BloomsController::class, 'exportLessonCsv'])->name('blooms.lesson.export-csv');
        Route::post('/blooms/lessons', [BloomsController::class, 'storeLesson'])->name('blooms.lesson.store');
        Route::delete('/blooms/lessons/{id}', [BloomsController::class, 'destroyLesson'])->name('blooms.lesson.destroy');
        Route::post('/blooms/assignments/{id}/grade', [BloomsController::class, 'gradeAssignment'])->name('blooms.assignment.grade');
        Route::post('/blooms/import-students-csv', [BloomsController::class, 'importStudentsCsv'])->name('blooms.import-students-csv');
        Route::post('/blooms/import-tasks-csv', [BloomsController::class, 'importTasksCsv'])->name('blooms.import-tasks-csv');
        Route::post('/blooms/lessons/{id}/import-tasks', [BloomsController::class, 'importTasksCsv'])->name('blooms.lesson.import-tasks');
        Route::post('/blooms/lessons/{id}/reshuffle', [BloomsController::class, 'reshuffleLesson'])->name('blooms.lesson.reshuffle');
        Route::post('/blooms/lessons/{id}/assignments', [BloomsController::class, 'storeCustomAssignment'])->middleware('throttle:10,1')->name('blooms.lesson.assignments.store');
        Route::match(['post', 'put', 'patch'], '/blooms/assignments/{id}', [BloomsController::class, 'updateAssignment'])->middleware('throttle:10,1')->name('blooms.assignment.update');
        Route::post('/blooms/assignments/{id}/reassign', [BloomsController::class, 'reassignStudent'])->name('blooms.assignment.reassign');
        Route::post('/blooms/assignments/{id}/reroll', [BloomsController::class, 'rerollAssignment'])->name('blooms.assignment.reroll');
        Route::post('/blooms/assignments/{id}/request-revision', [BloomsController::class, 'requestRevision'])->name('blooms.assignment.request-revision');
        Route::delete('/blooms/assignments/{id}', [BloomsController::class, 'destroyAssignment'])->name('blooms.assignment.destroy');

        // Syllabus CSV & Management
        Route::get('/syllabus/sample-csv', [SyllabusController::class, 'sampleCsv'])->name('syllabus.sample-csv');
        Route::get('/syllabus/export-csv/{classId}', [SyllabusController::class, 'exportCsv'])->name('syllabus.export-csv');
        Route::post('/syllabus/import-csv', [SyllabusController::class, 'importCsv'])->name('syllabus.import-csv');
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

        // DPP Management & CSV
        Route::get('/dpp/sample-csv', [DppController::class, 'sampleCsv'])->name('dpp.sample-csv');
        Route::get('/dpp/{id}/export-csv', [DppController::class, 'exportCsv'])->name('dpp.export-csv')->whereNumber('id');
        Route::post('/dpp', [DppController::class, 'store'])->name('dpp.store');
        Route::post('/dpp/bulk-import', [DppController::class, 'bulkImport'])->name('dpp.bulk-import');
        Route::post('/question-bank', [DppController::class, 'storeQuestionBank'])->name('question-bank.store');
        Route::get('/dpp/{id}/edit', [DppController::class, 'edit'])->name('dpp.edit')->whereNumber('id');
        Route::match(['post', 'put', 'patch'], '/dpp/{id}', [DppController::class, 'update'])->name('dpp.update')->whereNumber('id');
        Route::delete('/dpp/{id}', [DppController::class, 'destroy'])->name('dpp.destroy')->whereNumber('id');
        Route::delete('/dpp/{id}/questions/{questionId}', [DppController::class, 'destroyQuestion'])->name('dpp.questions.destroy')->whereNumber(['id', 'questionId']);

        // Homework Management
        Route::post('/homework', [HomeworkController::class, 'store'])->name('homework.store');
        Route::match(['put', 'patch', 'post'], '/homework/{id}', [HomeworkController::class, 'update'])->name('homework.update')->whereNumber('id');
        Route::delete('/homework/{id}', [HomeworkController::class, 'destroy'])->name('homework.destroy')->whereNumber('id');
        Route::match(['post', 'patch'], '/homework/submissions/{id}/check', [HomeworkController::class, 'check'])->name('homework.submissions.check');

        // Courses Management
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
        Route::post('/courses/{courseId}/assessments', [CourseAssessmentController::class, 'store'])->name('courses.assessments.store');
        Route::match(['put', 'patch', 'post'], '/courses/{courseId}/assessments/{assessmentId}', [CourseAssessmentController::class, 'update'])->name('courses.assessments.update');
        Route::delete('/courses/{courseId}/assessments/{assessmentId}', [CourseAssessmentController::class, 'destroy'])->name('courses.assessments.destroy');

        // Assignments Management
        Route::post('/assignments', [WebAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{id}/edit', [WebAssignmentController::class, 'edit'])->name('assignments.edit');
        Route::match(['put', 'patch', 'post'], '/assignments/{id}', [WebAssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{id}', [WebAssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::post('/submissions/{id}/grade', [WebAssignmentController::class, 'grade'])->name('submissions.grade');

        // Quizzes Management
        Route::post('/quizzes', [LiveQuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [LiveQuizController::class, 'edit'])->name('quizzes.edit');
        Route::match(['put', 'patch', 'post'], '/quizzes/{quiz}', [LiveQuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [LiveQuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('/quizzes/{quiz}/import-csv', [LiveQuizController::class, 'importCsv'])->name('quizzes.import-csv');
        Route::post('/quizzes/{quiz}/import-text', [LiveQuizController::class, 'importBulkText'])->name('quizzes.import-text');
        Route::post('/quizzes/{quiz}/questions', [LiveQuizController::class, 'storeQuestion'])->name('quizzes.questions.store');
        Route::match(['put', 'patch', 'post'], '/quizzes/{quiz}/questions/{question}', [LiveQuizController::class, 'updateQuestion'])->name('quizzes.questions.update');
        Route::delete('/quizzes/{quiz}/questions/{question}', [LiveQuizController::class, 'destroyQuestion'])->name('quizzes.questions.destroy');
        Route::get('/quizzes/sample-csv', [LiveQuizController::class, 'sampleCsv'])->name('quizzes.sample-csv');
        Route::get('/quizzes/{quiz}/sample-csv', [LiveQuizController::class, 'sampleCsv'])->name('quizzes.quiz.sample-csv');
        Route::get('/quizzes/{quiz}/export-csv', [LiveQuizController::class, 'exportCsv'])->name('quizzes.export-csv');

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

        // Virtual Classes Management
        Route::post('/virtual-classes', [VirtualClassController::class, 'store'])->name('virtual-classes.store');
        Route::delete('/virtual-classes/{virtualClass}', [VirtualClassController::class, 'destroy'])->name('virtual-classes.destroy');
    });
});
