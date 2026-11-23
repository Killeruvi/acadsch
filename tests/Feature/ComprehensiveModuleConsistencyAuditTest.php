<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\ClassEnrollment;
use App\Models\DailyDpp;
use App\Models\DailyLesson;
use App\Models\FeeInvoice;
use App\Models\HomeworkTask;
use App\Models\HomeworkSubmission;
use App\Models\LiveQuiz;
use App\Models\LiveQuizQuestion;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveModuleConsistencyAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_upi_generation_blocked_on_fully_settled_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-SETTLED-01',
            'student_id' => $student->id,
            'title' => 'Tuition Fee',
            'category' => 'tuition',
            'amount' => 5000,
            'total_due' => 5000,
            'paid_amount' => 5000,
            'due_date' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $response = $this->actingAs($student)->postJson(route('school.fees.initiate-upi', $invoice->id), [
            'amount' => 100,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'This invoice has already been settled in full.']);
    }

    public function test_dpp_access_and_submission_blocked_for_cross_class_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classA = AcademicClass::create(['name' => 'Grade 10-A', 'grade_level' => 10, 'academic_year' => '2026-2027']);
        $classB = AcademicClass::create(['name' => 'Grade 9-A', 'grade_level' => 9, 'academic_year' => '2026-2027']);

        $studentA = User::factory()->create(['role' => 'student', 'current_class_id' => $classA->id]);

        $dppB = DailyDpp::create([
            'class_id' => $classB->id,
            'teacher_id' => $teacher->id,
            'title' => 'Grade 9 Science DPP',
            'dpp_number' => 'DPP-09-SCI-01',
            'assigned_date' => now()->toDateString(),
            'total_marks' => 20,
            'difficulty' => 'standard',
            'duration_minutes' => 30,
        ]);

        // Student from Class A attempting to view or submit Class B DPP
        $showRes = $this->actingAs($studentA)->getJson("/api/v1/dpps/{$dppB->id}");
        $showRes->assertStatus(403);

        $submitRes = $this->actingAs($studentA)->postJson("/api/v1/dpps/{$dppB->id}/submit", [
            'answers' => ['A', 'B'],
        ]);
        $submitRes->assertStatus(403);
    }

    public function test_homework_role_authorization_and_cross_class_submission_guards(): void
    {
        $classA = AcademicClass::create(['name' => 'Grade 10-A', 'grade_level' => 10, 'academic_year' => '2026-2027']);
        $classB = AcademicClass::create(['name' => 'Grade 8-A', 'grade_level' => 8, 'academic_year' => '2026-2027']);
        $subjectA = AcademicSubject::create(['class_id' => $classA->id, 'name' => 'Mathematics', 'code' => 'MATH-10']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $studentA = User::factory()->create(['role' => 'student', 'current_class_id' => $classA->id]);
        $studentB = User::factory()->create(['role' => 'student', 'current_class_id' => $classB->id]);

        // 1. Students cannot create homework
        $createRes = $this->actingAs($studentA)->post(route('homework.store'), [
            'class_id' => $classA->id,
            'subject_id' => $subjectA->id,
            'title' => 'Malicious Student Task',
            'instructions' => 'None',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);
        $createRes->assertStatus(403);

        // Teacher assigns valid homework
        $task = HomeworkTask::create([
            'class_id' => $classA->id,
            'subject_id' => $subjectA->id,
            'teacher_id' => $teacher->id,
            'title' => 'Geometry Proofs',
            'instructions' => 'Complete problems 1 to 5',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        // 2. Student B (enrolled in class B) cannot submit homework assigned to class A
        $subResB = $this->actingAs($studentB)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Attempting cross-class submission',
        ]);
        $subResB->assertStatus(403);

        // 3. Student A (enrolled in class A) can submit
        $subResA = $this->actingAs($studentA)->post(route('homework.submit', $task->id), [
            'student_notes' => 'Here are my solutions to problems 1-5',
        ]);
        $subResA->assertRedirect(route('homework.index'));

        // 4. Students cannot mark or grade homework
        $submission = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $studentA->id)->first();
        $gradeRes = $this->actingAs($studentA)->post(route('homework.submissions.check', $submission->id), [
            'status' => 'checked',
            'teacher_feedback' => 'Self-approved',
        ]);
        $gradeRes->assertStatus(403);
    }

    public function test_circulars_cannot_be_published_by_students(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $res = $this->actingAs($student)->post(route('notices.store'), [
            'title' => 'Unauthorized Circular',
            'content' => 'Campus is closed today.',
            'priority' => 'urgent',
            'target_audience' => 'all',
        ]);

        $res->assertStatus(403);
    }

    public function test_blooms_taxonomy_teacher_authorization_and_ownership(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);

        $class = AcademicClass::create(['name' => 'Grade 10-A', 'grade_level' => 10, 'academic_year' => '2026-2027']);
        $subject = AcademicSubject::create(['class_id' => $class->id, 'name' => 'Science', 'code' => 'SCI-10']);
        $topic = \App\Models\CurriculumTopic::create([
            'subject_id' => $subject->id,
            'chapter_number' => 1,
            'title' => 'Photosynthesis',
        ]);

        // Student cannot record lessons
        $resStudent = $this->actingAs($student)->post(route('blooms.lesson.store'), [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'balanced_random',
        ]);
        $resStudent->assertStatus(403);

        // Teacher 1 creates a lesson
        $lesson = DailyLesson::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'teacher_id' => $teacher1->id,
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'balanced_random',
        ]);

        // Teacher 2 cannot delete Teacher 1's lesson
        $resDelTeacher2 = $this->actingAs($teacher2)->delete(route('blooms.lesson.destroy', $lesson->id));
        $resDelTeacher2->assertStatus(403);
    }

    public function test_live_quiz_anti_xp_farming_delta_enforcement(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student', 'xp_points' => 100]);

        $quiz = LiveQuiz::create([
            'creator_id' => $teacher->id,
            'title' => 'Speed Physics',
            'access_code' => 'QZ-12345',
            'time_per_question' => 30,
            'xp_reward' => 50,
            'status' => 'active',
        ]);

        $q1 = LiveQuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question' => 'Unit of force?',
            'option_a' => 'Newton',
            'option_b' => 'Joule',
            'option_c' => 'Watt',
            'option_d' => 'Pascal',
            'correct_option' => 'A',
            'points' => 10,
        ]);

        // Attempt 1: 100% correct -> awards 50 XP
        $this->actingAs($student)->post(route('quizzes.submit', $quiz->id), [
            'answers' => [$q1->id => 'A'],
        ]);

        $student->refresh();
        $this->assertEquals(150, $student->xp_points);

        // Attempt 2: Re-submitting same 100% score -> delta XP is 0 (prevents infinite loop farming)
        $this->actingAs($student)->post(route('quizzes.submit', $quiz->id), [
            'answers' => [$q1->id => 'A'],
        ]);

        $student->refresh();
        $this->assertEquals(150, $student->xp_points, 'XP must not inflate upon identical quiz re-attempt');
    }

    public function test_staff_directory_privilege_escalation_guard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Teacher attempting to modify admin's dossier or elevate roles
        $res = $this->actingAs($teacher)->put(route('staff.update', $admin->id), [
            'name' => 'Compromised Admin',
            'email' => 'hacked@school.com',
            'role' => 'admin',
        ]);

        $res->assertStatus(403);
    }

    public function test_student_directory_enrollment_reconciliation_on_transfer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classOld = AcademicClass::create(['name' => 'Grade 8-A', 'grade_level' => 8, 'academic_year' => '2026-2027']);
        $classNew = AcademicClass::create(['name' => 'Grade 9-A', 'grade_level' => 9, 'academic_year' => '2026-2027']);

        $student = User::factory()->create([
            'role' => 'student',
            'current_class_id' => $classOld->id,
        ]);

        $oldEnrollment = ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classOld->id,
            'roll_number' => '08',
            'status' => 'active',
        ]);

        // Admin transfers student to Grade 9 via Student Directory
        $res = $this->actingAs($admin)->put(route('students.update', $student->id), [
            'name' => $student->name,
            'email' => $student->email,
            'class_id' => $classNew->id,
            'roll_number' => '12',
        ]);

        $res->assertRedirect(route('students.show', $student->id));

        $student->refresh();
        $oldEnrollment->refresh();

        $this->assertEquals($classNew->id, $student->current_class_id);
        $this->assertEquals('transferred', $oldEnrollment->status);

        $newEnrollment = ClassEnrollment::where('student_id', $student->id)
            ->where('class_id', $classNew->id)
            ->first();

        $this->assertNotNull($newEnrollment);
        $this->assertEquals('active', $newEnrollment->status);
        $this->assertEquals('12', $newEnrollment->roll_number);
    }

    public function test_parent_portal_dashboard_loads_and_scopes_circulars_without_column_exception(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'guardian@family.org']);
        $class = AcademicClass::create(['name' => 'Grade 7-A', 'grade_level' => 7, 'academic_year' => '2026-2027']);

        $student = User::factory()->create([
            'role' => 'student',
            'parent_id' => $parent->id,
            'current_class_id' => $class->id,
        ]);

        // Create circulars: 1 for parents, 1 for teachers only
        \App\Models\AcademyNotice::create([
            'author_id' => $parent->id,
            'title' => 'Parent Teacher Conference',
            'content' => 'Scheduled for next Friday.',
            'priority' => 'high',
            'target_audience' => 'parents',
            'published_at' => now(),
        ]);

        \App\Models\AcademyNotice::create([
            'author_id' => $parent->id,
            'title' => 'Confidential Faculty Meeting',
            'content' => 'Strictly internal staff agenda.',
            'priority' => 'urgent',
            'target_audience' => 'teachers',
            'published_at' => now(),
        ]);

        $res = $this->actingAs($parent)->get(route('school.parent.dashboard'));

        $res->assertStatus(200);
        $res->assertSee('Parent Teacher Conference');
        $res->assertDontSee('Confidential Faculty Meeting');
    }
}
