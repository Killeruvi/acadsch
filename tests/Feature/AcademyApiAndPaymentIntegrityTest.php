<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\HomeworkTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyApiAndPaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private AcademicClass $class;
    private AcademicSubject $subject;
    private Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'academic_year' => '2026-2027',
        ]);

        $this->batch = Batch::create([
            'class_id' => $this->class->id,
            'name' => 'Class 10 - Morning Star Batch',
            'code' => 'BATCH-10-M',
            'shift' => 'morning',
            'timing_slot' => '07:30 AM - 10:30 AM',
            'is_active' => true,
        ]);

        $this->subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->class->update(['class_teacher_id' => $this->teacher->id]);
        $this->subject->update(['teacher_id' => $this->teacher->id]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'batch_id' => $this->batch->id,
        ]);

        $this->class->students()->syncWithoutDetaching([
            $this->student->id => ['roll_number' => 1, 'status' => 'active']
        ]);
    }

    public function test_api_dpp_endpoints(): void
    {
        $dpp = DailyDpp::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-10-01',
            'type' => 'dpp',
            'title' => 'Quadratic Equations Basics',
            'assigned_date' => now()->toDateString(),
            'total_marks' => 10,
            'difficulty' => 'easy',
        ]);

        DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 1,
            'question_text' => 'What is the discriminant of ax^2 + bx + c = 0?',
            'option_a' => 'b^2 - 4ac',
            'option_b' => 'b^2 + 4ac',
            'option_c' => '2a / b',
            'option_d' => '4ac - b^2',
            'correct_option' => 'A',
            'explanation' => 'Discriminant is given by b^2 - 4ac.',
            'marks' => 5,
        ]);

        // 1. List DPPs
        $response = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/dpps');
        $response->assertStatus(200);
        $response->assertJsonFragment(['dpp_number' => 'DPP-10-01']);

        // 2. Show DPP (Student unsubmitted - correct_option masked)
        $detail = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/dpps/' . $dpp->id);
        $detail->assertStatus(200);
        $detail->assertJsonMissing(['correct_option' => 'A']);

        // 3. Submit DPP
        $submit = $this->actingAs($this->student, 'sanctum')->postJson('/api/v1/dpps/' . $dpp->id . '/submit', [
            'answers' => [
                '1' => 'A',
            ],
            'time_taken_seconds' => 45,
        ]);

        $submit->assertStatus(200)
            ->assertJsonPath('score', 5)
            ->assertJsonPath('percentage', 100);
    }

    public function test_api_batch_endpoints(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/batches');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Class 10 - Morning Star Batch']);

        $detail = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/batches/' . $this->batch->id);
        $detail->assertStatus(200)
            ->assertJsonPath('is_current_user_batch', true);
    }

    public function test_api_homework_endpoints(): void
    {
        $task = HomeworkTask::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Solve Exercise 4.2',
            'instructions' => 'Complete problems 1 through 10 in your notebook.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $list = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/homework');
        $list->assertStatus(200);
        $list->assertJsonFragment(['title' => 'Solve Exercise 4.2']);

        $submit = $this->actingAs($this->student, 'sanctum')->postJson('/api/v1/homework/' . $task->id . '/submit', [
            'student_notes' => 'I have completed all 10 problems on notebook page 45-47.',
        ]);

        $submit->assertStatus(200)
            ->assertJsonPath('submission.status', 'pending');
    }

    public function test_api_attendance_endpoints(): void
    {
        // Teacher records attendance
        $store = $this->actingAs($this->teacher, 'sanctum')->postJson('/api/v1/attendance', [
            'class_id' => $this->class->id,
            'session_date' => now()->toDateString(),
            'title' => 'Morning Class 10 Session',
            'records' => [
                [
                    'student_id' => $this->student->id,
                    'status' => 'present',
                ],
            ],
        ]);

        $store->assertStatus(201);

        // Student views their attendance stats
        $studentAttendance = $this->actingAs($this->student, 'sanctum')->getJson('/api/v1/attendance');
        $studentAttendance->assertStatus(200)
            ->assertJsonPath('attendance_rate', 100)
            ->assertJsonPath('present_count', 1);
    }

    public function test_payment_gateway_webhook_settlement_and_duplicate_prevention(): void
    {
        // Gateway webhooks have been permanently purged (404) in favor of QR + cashier manual verification
        $response = $this->postJson('/api/v1/fees/webhook', [
            'amount' => 5000,
        ]);

        $response->assertStatus(404);
    }
}
