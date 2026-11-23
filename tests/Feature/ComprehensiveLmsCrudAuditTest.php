<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprehensiveLmsCrudAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createTeacher(): User
    {
        return User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Audit Expert',
            'email' => 'teacher_' . uniqid() . '@primearc.test',
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Controller',
            'email' => 'admin_' . uniqid() . '@primearc.test',
        ]);
    }

    private function createAcademicClass(int $grade = 10): AcademicClass
    {
        return AcademicClass::firstOrCreate(
            ['grade_level' => $grade],
            [
                'name' => 'Class ' . $grade,
                'section' => 'A',
                'stream' => 'Science',
            ]
        );
    }

    private function createSubject(AcademicClass $class, string $name = 'Physics'): AcademicSubject
    {
        return AcademicSubject::firstOrCreate(
            ['class_id' => $class->id, 'name' => $name],
            ['code' => strtoupper(substr($name, 0, 4))]
        );
    }

    // =========================================================================
    // 1. HOMEWORK CRUD TESTS
    // =========================================================================

    public function test_teacher_can_update_homework_task(): void
    {
        Storage::fake('public', ['lock' => 0]);

        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(10);
        $subject = $this->createSubject($class, 'Mathematics');
        $subject->update(['teacher_id' => $teacher->id]);

        $task = HomeworkTask::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Initial Calculus Homework',
            'instructions' => 'Solve problems 1 to 5.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $newAttachment = UploadedFile::fake()->create('revised_notes.pdf', 300, 'application/pdf');

        $response = $this->actingAs($teacher)->put(route('homework.update', $task->id), [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => 'Updated Advanced Calculus Homework',
            'instructions' => 'Solve problems 1 to 10 with proofs.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'attachment' => $newAttachment,
        ]);

        $response->assertRedirect(route('homework.index'));
        $response->assertSessionHas('success');

        $task->refresh();
        $this->assertEquals('Updated Advanced Calculus Homework', $task->title);
        $this->assertEquals('Solve problems 1 to 10 with proofs.', $task->instructions);
        $this->assertNotNull($task->attachment_path);
        Storage::disk('public')->assertExists($task->attachment_path);
    }

    public function test_teacher_can_delete_homework_task_and_cascades_submissions(): void
    {
        Storage::fake('public', ['lock' => 0]);

        $teacher = $this->createTeacher();
        $student = User::factory()->create(['role' => 'student']);
        $class = $this->createAcademicClass(9);
        $subject = $this->createSubject($class, 'Chemistry');

        $fakeAttachmentPath = 'homework/tasks/sample_attachment.pdf';
        Storage::disk('public')->put($fakeAttachmentPath, 'Sample content');

        $task = HomeworkTask::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Atomic Structure Task',
            'instructions' => 'Draw Bohr model',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'attachment_path' => $fakeAttachmentPath,
            'attachment_name' => 'sample_attachment.pdf',
        ]);

        $subAttachmentPath = 'homework/submissions/student_sub.pdf';
        Storage::disk('public')->put($subAttachmentPath, 'Student answer');

        $submission = HomeworkSubmission::create([
            'homework_id' => $task->id,
            'student_id' => $student->id,
            'student_notes' => 'Completed Bohr model drawings',
            'status' => 'submitted',
            'attachment_path' => $subAttachmentPath,
            'attachment_name' => 'student_sub.pdf',
        ]);

        $response = $this->actingAs($teacher)->delete(route('homework.destroy', $task->id));

        $response->assertRedirect(route('homework.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('homework_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('homework_submissions', ['id' => $submission->id]);
        Storage::disk('public')->assertMissing($fakeAttachmentPath);
        Storage::disk('public')->assertMissing($subAttachmentPath);
    }

    // =========================================================================
    // 2. DPP VALIDATION & UPDATE TESTS
    // =========================================================================

    public function test_dpp_update_accepts_medium_difficulty_and_course_dpp_options(): void
    {
        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(11);
        $subject = $this->createSubject($class, 'Biology');
        $subject->update(['teacher_id' => $teacher->id]);

        $dpp = DailyDpp::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'dpp_number' => 'DPP-TEST-01',
            'title' => 'Cell Structure DPP',
            'type' => 'dpp',
            'difficulty' => 'standard',
            'assigned_date' => now()->toDateString(),
            'duration_minutes' => 30,
            'is_timed' => true,
            'total_marks' => 4,
            'pass_percentage' => 40,
            'negative_marks' => 0.25,
            'is_published' => true,
        ]);

        $q = DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 1,
            'question_text' => 'What is the powerhouse of the cell?',
            'option_a' => 'Ribosome',
            'option_b' => 'Mitochondria',
            'option_c' => 'Nucleus',
            'option_d' => 'Lysosome',
            'correct_option' => 'B',
            'explanation' => 'Mitochondria produces ATP.',
            'marks' => 4,
        ]);

        // Update with 'medium' difficulty (which previously would fail standard,advanced,olympiad validation)
        $response = $this->actingAs($teacher)->put(route('dpp.update', $dpp->id), [
            'title' => 'Cell Structure & Respiration DPP',
            'type' => 'dpp',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'medium',
            'duration_minutes' => 45,
            'pass_percentage' => 50,
            'negative_marks' => 0.5,
            'questions' => [
                [
                    'id' => $q->id,
                    'text' => 'What organelle produces ATP via cellular respiration?',
                    'option_a' => 'Ribosome',
                    'option_b' => 'Mitochondria',
                    'option_c' => 'Endoplasmic Reticulum',
                    'option_d' => 'Chloroplast',
                    'correct' => 'B',
                    'explanation' => 'ATP synthesis occurs in the mitochondria.',
                    'marks' => 4,
                ]
            ],
        ]);

        $response->assertRedirect(route('dpp.show', $dpp->id));
        $response->assertSessionHas('success');

        $dpp->refresh();
        $this->assertEquals('medium', $dpp->difficulty);
        $this->assertEquals('Cell Structure & Respiration DPP', $dpp->title);
        $this->assertEquals(45, $dpp->duration_minutes);
    }

    // =========================================================================
    // 3. COURSE ASSIGNMENT DELETION REDIRECT CONTEXT
    // =========================================================================

    public function test_deleting_course_assignment_redirects_back_to_course_homework_tab(): void
    {
        $teacher = $this->createTeacher();
        $course = Course::create([
            'title' => 'Data Structures and Algorithms',
            'code' => 'CS201',
            'category' => 'Computer Science',
            'credits' => 4,
            'capacity' => 40,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Binary Trees Problem Set',
            'description' => 'Implement AVL tree rebalancing',
            'due_date' => now()->addDays(5)->toDateString(),
            'max_points' => 100,
        ]);

        $response = $this->actingAs($teacher)->delete(route('assignments.destroy', $assignment->id), [
            'course_id' => $course->id,
        ]);

        $response->assertRedirect(route('courses.show', ['id' => $course->id, 'tab' => 'homework']));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    // =========================================================================
    // 4. SYLLABUS TOPIC CRUD TESTS
    // =========================================================================

    public function test_teacher_can_create_update_and_delete_syllabus_chapter(): void
    {
        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(10);
        $subject = $this->createSubject($class, 'Science');

        // 1. Create Topic
        $storeResponse = $this->actingAs($teacher)->post(route('syllabus.topics.store'), [
            'subject_id' => $subject->id,
            'chapter_number' => 1,
            'title' => 'Chemical Reactions and Equations',
            'description' => 'Foundations of stoichiometry and reaction balance',
            'subtopics' => 'Types of Reactions; Balancing Equations; Redox',
            'resource_url' => 'https://ncert.nic.in/science/ch1.pdf',
            'study_notes' => 'Focus on precipitation and displacement experiments',
        ]);

        $storeResponse->assertRedirect(route('syllabus.index', ['grade' => $class->grade_level, 'subject_id' => $subject->id]));
        $storeResponse->assertSessionHas('success');

        $topic = CurriculumTopic::where('title', 'Chemical Reactions and Equations')->first();
        $this->assertNotNull($topic);
        $this->assertCount(3, $topic->subtopics);
        $this->assertEquals(1, $topic->chapter_number);

        // 2. Update Topic
        $updateResponse = $this->actingAs($teacher)->put(route('syllabus.topics.update', $topic->id), [
            'chapter_number' => 1,
            'title' => 'Chemical Reactions, Equations & Catalysis',
            'description' => 'Updated syllabus including exothermic and endothermic kinetics',
            'subtopics' => 'Types of Reactions; Balancing; Catalysis; Redox',
            'status' => 'in_progress',
        ]);

        $updateResponse->assertRedirect(route('syllabus.show', $topic->id));
        $updateResponse->assertSessionHas('success');

        $topic->refresh();
        $this->assertEquals('Chemical Reactions, Equations & Catalysis', $topic->title);
        $this->assertEquals('in_progress', $topic->status);
        $this->assertCount(4, $topic->subtopics);

        // 3. Delete Topic
        $deleteResponse = $this->actingAs($teacher)->delete(route('syllabus.topics.destroy', $topic->id));
        $deleteResponse->assertRedirect(route('syllabus.index', ['grade' => $class->grade_level, 'subject_id' => $subject->id]));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('curriculum_topics', ['id' => $topic->id]);
    }

    // =========================================================================
    // 5. BATCH MANAGEMENT CRUD TESTS
    // =========================================================================

    public function test_teacher_can_update_and_delete_batch(): void
    {
        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(12);

        $batch = Batch::create([
            'class_id' => $class->id,
            'name' => 'Grade 12 Morning Superstars',
            'code' => 'BATCH-12-TEST1',
            'shift' => 'morning',
            'timing_slot' => '07:30 AM - 10:30 AM',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);

        // Student assigned to this batch
        $student = User::factory()->create([
            'role' => 'student',
            'batch_id' => $batch->id,
        ]);

        // 1. Update Batch
        $updateResponse = $this->actingAs($teacher)->put(route('batches.update', $batch->id), [
            'class_id' => $class->id,
            'name' => 'Grade 12 JEE Advanced Morning Shift',
            'shift' => 'morning',
            'timing_slot' => '07:00 AM - 10:30 AM',
            'description' => 'Targeting Top 100 AIR JEE aspirants',
        ]);

        $updateResponse->assertRedirect(route('batches.select', ['class_id' => $class->id]));
        $updateResponse->assertSessionHas('success');

        $batch->refresh();
        $this->assertEquals('Grade 12 JEE Advanced Morning Shift', $batch->name);
        $this->assertEquals('07:00 AM - 10:30 AM', $batch->timing_slot);

        // 2. Delete Batch
        $deleteResponse = $this->actingAs($teacher)->delete(route('batches.destroy', $batch->id));
        $deleteResponse->assertRedirect(route('batches.select', ['class_id' => $class->id]));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('batches', ['id' => $batch->id]);
        $student->refresh();
        $this->assertNull($student->batch_id); // Student safely dissociated
    }

    // =========================================================================
    // 6. ACADEMIC HOLIDAY MANAGEMENT CRUD TESTS
    // =========================================================================

    public function test_faculty_can_create_update_and_delete_holiday(): void
    {
        $admin = $this->createAdmin();

        // 1. Store Holiday
        $storeResponse = $this->actingAs($admin)->post(route('school.holidays.store'), [
            'name' => 'National Science Day Observance',
            'holiday_date' => '2026-02-28',
            'type' => 'festival',
            'badge_icon' => '🔬',
            'description' => 'Honoring discovery of Raman Effect',
            'is_instructional_off' => 1,
        ]);

        $storeResponse->assertRedirect(route('school.holidays.index', ['year' => 2026]));
        $storeResponse->assertSessionHas('success');

        $holiday = AcademicHoliday::where('name', 'National Science Day Observance')->first();
        $this->assertNotNull($holiday);
        $this->assertEquals('2026-02-28', $holiday->holiday_date->toDateString());

        // 2. Update Holiday
        $updateResponse = $this->actingAs($admin)->put(route('school.holidays.update', $holiday->id), [
            'name' => 'National Science Day & Technology Expo',
            'holiday_date' => '2026-02-28',
            'end_date' => '2026-03-01',
            'type' => 'institutional',
            'badge_icon' => '🚀',
            'description' => 'Extended 2-day technology demonstration and robotics gala',
            'is_instructional_off' => 1,
        ]);

        $updateResponse->assertRedirect(route('school.holidays.index', ['year' => 2026]));
        $updateResponse->assertSessionHas('success');

        $holiday->refresh();
        $this->assertEquals('National Science Day & Technology Expo', $holiday->name);
        $this->assertEquals('institutional', $holiday->type);
        $this->assertEquals('🚀', $holiday->badge_icon);
        $this->assertEquals('2026-03-01', $holiday->end_date->toDateString());

        // 3. Delete Holiday
        $deleteResponse = $this->actingAs($admin)->delete(route('school.holidays.destroy', $holiday->id));
        $deleteResponse->assertRedirect(route('school.holidays.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('academic_holidays', ['id' => $holiday->id]);
    }

    // =========================================================================
    // 7. SYLLABUS VIDEO LECTURE CRUD & PROGRESS CLEANUP
    // =========================================================================

    public function test_teacher_can_update_and_delete_syllabus_video_lecture(): void
    {
        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(10);
        $subject = $this->createSubject($class, 'Physics');

        $topic = CurriculumTopic::create([
            'subject_id' => $subject->id,
            'chapter_number' => 1,
            'title' => 'Light: Reflection and Refraction',
            'status' => 'not_started',
        ]);

        $video = \App\Models\CurriculumVideo::create([
            'topic_id' => $topic->id,
            'subject_id' => $subject->id,
            'title' => 'Introduction to Mirrors',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'video_provider' => 'youtube',
            'duration_seconds' => 1200,
            'order_index' => 1,
            'is_published' => true,
        ]);

        $student = User::factory()->create(['role' => 'student']);
        \App\Models\UserVideoProgress::create([
            'user_id' => $student->id,
            'video_id' => $video->id,
            'is_completed' => true,
        ]);

        // 1. Update Video
        $updateResp = $this->actingAs($teacher)->put(route('syllabus.videos.update', $video->id), [
            'title' => 'Concave and Convex Mirrors Masterclass',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 35,
            'description' => 'Ray tracing diagrams and focal length calculations',
        ]);

        $updateResp->assertRedirect();
        $updateResp->assertSessionHas('success');

        $video->refresh();
        $this->assertEquals('Concave and Convex Mirrors Masterclass', $video->title);
        $this->assertEquals(2100, $video->duration_seconds);

        // 2. Delete Video
        $deleteResp = $this->actingAs($teacher)->delete(route('syllabus.videos.destroy', $video->id));
        $deleteResp->assertRedirect();
        $deleteResp->assertSessionHas('success');

        $this->assertDatabaseMissing('curriculum_videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('user_video_progress', ['video_id' => $video->id]);
    }

    // =========================================================================
    // 8. BLOOM'S LESSON DELETION & TASK CLEANUP
    // =========================================================================

    public function test_teacher_can_delete_blooms_daily_lesson(): void
    {
        $teacher = $this->createTeacher();
        $class = $this->createAcademicClass(10);
        $subject = $this->createSubject($class, 'Chemistry');
        $topic = CurriculumTopic::create([
            'subject_id' => $subject->id,
            'chapter_number' => 1,
            'title' => 'Acids and Bases',
            'status' => 'not_started',
        ]);

        $lesson = \App\Models\DailyLesson::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'teacher_id' => $teacher->id,
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'uniform_level',
            'target_level' => 1,
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $assignment = \App\Models\BloomAssignment::create([
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'roll_number' => '101',
            'topic_id' => $topic->id,
            'bloom_level' => 1,
            'level_name' => 'Remember',
            'activity_prompt' => 'List 3 strong acids',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($teacher)->delete(route('blooms.lesson.destroy', $lesson->id));
        $response->assertRedirect(route('blooms.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('daily_lessons', ['id' => $lesson->id]);
        $this->assertDatabaseMissing('bloom_assignments', ['id' => $assignment->id]);
    }

    // =========================================================================
    // 9. STUDENT LEAVE CANCELLATION & FEE INVOICE DELETION
    // =========================================================================

    public function test_student_can_cancel_pending_leave_request(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $class = $this->createAcademicClass(8);

        $leave = \App\Models\StudentLeaveRequest::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'type' => 'medical',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Viral fever recovery',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->delete(route('leaves.destroy', $leave->id));
        $response->assertRedirect(route('leaves.index', ['tab' => 'view']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('student_leave_requests', [
            'id' => $leave->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_delete_fee_invoice(): void
    {
        $admin = $this->createAdmin();
        $student = User::factory()->create(['role' => 'student']);

        $invoice = \App\Models\FeeInvoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'student_id' => $student->id,
            'title' => 'Annual Lab Equipment Fee',
            'category' => 'Lab & Tech Fee',
            'amount' => 5000,
            'total_due' => 5000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->delete(route('fees.destroy', $invoice->id));
        $response->assertRedirect(route('fees.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('fee_invoices', ['id' => $invoice->id]);
    }

    // =========================================================================
    // 10. ACADEMY NOTICE UPDATE
    // =========================================================================

    public function test_admin_can_update_official_notice(): void
    {
        $admin = $this->createAdmin();

        $notice = \App\Models\AcademyNotice::create([
            'author_id' => $admin->id,
            'title' => 'Annual Sports Meet Announcement',
            'content' => 'Sports day will be held on Friday.',
            'priority' => 'normal',
            'target_audience' => 'all',
            'is_pinned' => false,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('notices.update', $notice->id), [
            'title' => 'Rescheduled: Annual Sports Meet & Track Gala',
            'content' => 'Sports day is postponed to Saturday due to track resurfacing.',
            'priority' => 'urgent',
            'target_audience' => 'all',
            'is_pinned' => 1,
        ]);

        $response->assertRedirect(route('notices.index'));
        $response->assertSessionHas('success');

        $notice->refresh();
        $this->assertEquals('Rescheduled: Annual Sports Meet & Track Gala', $notice->title);
        $this->assertEquals('urgent', $notice->priority);
        $this->assertTrue((bool)$notice->is_pinned);
    }
}
