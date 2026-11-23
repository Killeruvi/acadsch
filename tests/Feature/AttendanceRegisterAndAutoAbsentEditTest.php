<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AttendanceRegisterAndAutoAbsentEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student1;

    private User $student2;

    private AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student1 = User::factory()->create(['role' => 'student', 'is_approved' => true]);
        $this->student2 = User::factory()->create(['role' => 'student', 'is_approved' => true]);

        $this->class = AcademicClass::create([
            'name' => 'Class 9',
            'section' => 'A',
            'grade_level' => 9,
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'class_teacher_id' => $this->teacher->id,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student1->id,
            'class_id' => $this->class->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student2->id,
            'class_id' => $this->class->id,
            'academic_year' => '2026-2027',
            'roll_number' => 2,
            'status' => 'active',
        ]);
    }

    /** 1. Class attendance register renders monthly matrix without geofence or dummy data */
    public function test_attendance_register_renders_monthly_matrix(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('school.attendance.register', [
            'classId' => $this->class->id,
            'month' => '2026-11',
        ]));

        $response->assertOk();
        $response->assertSee($this->student1->name);
        $response->assertSee($this->student2->name);
        $response->assertSee('Class 9 - Section A');
    }

    /** 2. Edit-after-auto-absent flow: Admin or Teacher can update auto-absent records with audit revision */
    public function test_edit_after_auto_absent_updates_record_and_writes_revision(): void
    {
        Carbon::setTestNow('2026-11-20 18:00:00'); // Friday instructional day

        try {
            $today = '2026-11-20';

            // 1. Run auto-absent to generate default absences
            Artisan::call('attendance:auto-absent', ['--date' => $today]);

            $session = AttendanceSession::where('class_id', $this->class->id)
                ->whereDate('session_date', $today)
                ->firstOrFail();

            $this->assertDatabaseHas('attendance_records', [
                'session_id' => $session->id,
                'student_id' => $this->student1->id,
                'status' => 'absent',
            ]);

            // 2. Admin edits Student 1 from 'absent' to 'late' with reason
            $response = $this->actingAs($this->admin)->post(route('school.attendance.store', $this->class->id), [
                'session_date' => $today,
                'title' => 'Corrected Roll Call',
                'correction_reason' => 'Student arrived on delayed bus',
                'records' => [
                    $this->student1->id => [
                        'status' => 'late',
                        'remarks' => 'Bus breakdown delay',
                    ],
                    $this->student2->id => [
                        'status' => 'absent',
                        'remarks' => 'Unexcused',
                    ],
                ],
            ]);

            $response->assertRedirect();

            // Verify status was updated to late
            $this->assertDatabaseHas('attendance_records', [
                'session_id' => $session->id,
                'student_id' => $this->student1->id,
                'status' => 'late',
                'remarks' => 'Bus breakdown delay',
            ]);

            // Verify audit revision was logged
            $this->assertDatabaseHas('attendance_record_revisions', [
                'attendance_session_id' => $session->id,
                'student_id' => $this->student1->id,
                'before_status' => 'absent',
                'after_status' => 'late',
                'reason' => 'Student arrived on delayed bus',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
