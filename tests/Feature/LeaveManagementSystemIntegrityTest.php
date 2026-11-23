<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementSystemIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $parent;

    private User $child1;

    private User $child2;

    private AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->parent = User::factory()->create(['role' => 'parent', 'email' => 'parent@academy.edu']);

        $this->class = AcademicClass::create([
            'name' => 'Class 8',
            'section' => 'A',
            'grade_level' => 8,
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'class_teacher_id' => $this->teacher->id,
        ]);

        $this->child1 = User::factory()->create([
            'role' => 'student',
            'name' => 'Aarav Sharma',
            'parent_id' => $this->parent->id,
            'current_class_id' => $this->class->id,
            'is_approved' => true,
        ]);

        $this->child2 = User::factory()->create([
            'role' => 'student',
            'name' => 'Diya Sharma',
            'parent_id' => $this->parent->id,
            'current_class_id' => $this->class->id,
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'student_id' => $this->child1->id,
            'class_id' => $this->class->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);

        ClassEnrollment::create([
            'student_id' => $this->child2->id,
            'class_id' => $this->class->id,
            'academic_year' => '2026-2027',
            'roll_number' => 2,
            'status' => 'active',
        ]);
    }

    /** 1. Multi-student selector and Apply/View tabs render for Parent */
    public function test_parent_sees_multi_child_selector_and_tabs(): void
    {
        $response = $this->actingAs($this->parent)->get(route('leaves.index'));
        $response->assertOk();
        $response->assertSee('Aarav Sharma');
        $response->assertSee('Diya Sharma');
        $response->assertSee('Apply Leave');
        $response->assertSee('View Leaves');
    }

    /** 2. Parent submits leave for selected child on instructional weekday */
    public function test_parent_applies_leave_for_selected_child(): void
    {
        // 2026-11-23 is Monday (instructional day)
        $response = $this->actingAs($this->parent)->post(route('leaves.store'), [
            'student_id' => $this->child1->id,
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-24',
            'type' => 'medical',
            'reason' => 'Severe viral fever and doctor prescribed rest',
        ]);

        $response->assertRedirect(route('leaves.index', ['tab' => 'view']));

        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->child1->id,
            'start_date' => '2026-11-23 00:00:00',
            'end_date' => '2026-11-24 00:00:00',
            'type' => 'medical',
            'status' => 'pending',
        ]);
    }

    /** 3. Overlapping leave requests for same student are rejected */
    public function test_overlapping_leave_requests_are_rejected(): void
    {
        StudentLeaveRequest::create([
            'student_id' => $this->child1->id,
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-25',
            'type' => 'family',
            'reason' => 'Family wedding',
            'status' => 'pending',
        ]);

        // Attempt overlapping submission
        $response = $this->actingAs($this->parent)->post(route('leaves.store'), [
            'student_id' => $this->child1->id,
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-26',
            'type' => 'medical',
            'reason' => 'Dental appointment',
        ]);

        $response->assertSessionHas('error');
    }

    /** 4. Leave request falling entirely on official holidays or weekends is rejected */
    public function test_leave_on_official_holidays_or_weekends_is_rejected(): void
    {
        // 2026-11-29 is a Sunday
        $response = $this->actingAs($this->parent)->post(route('leaves.store'), [
            'student_id' => $this->child1->id,
            'start_date' => '2026-11-29',
            'end_date' => '2026-11-29',
            'type' => 'family',
            'reason' => 'Family visit on Sunday',
        ]);

        $response->assertSessionHas('error');
    }

    /** 5. Exam collision notice is attached when leave coincides with scheduled exam */
    public function test_exam_schedule_notice_attached_to_leave_reason(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Midterm Assessment 2026',
            'start_date' => '2026-11-23',
            'end_date' => '2026-11-27',
            'academic_year' => '2026-2027',
        ]);

        $subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Mathematics',
            'code' => 'MTH-08',
        ]);

        SchoolExamSchedule::create([
            'exam_term_id' => $term->id,
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'exam_date' => '2026-11-25',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'max_marks' => 100,
            'pass_marks' => 35,
        ]);

        $this->actingAs($this->parent)->post(route('leaves.store'), [
            'student_id' => $this->child2->id,
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-26',
            'type' => 'academic',
            'reason' => 'Attending State Science Fair',
        ]);

        $leave = StudentLeaveRequest::where('student_id', $this->child2->id)->firstOrFail();
        $this->assertStringContainsString('Notice: 1 exam(s) scheduled during this leave period', $leave->reason);
    }

    /** 6. Approval syncs attendance to 'excused' and cancellation reverts to 'absent' */
    public function test_leave_approval_syncs_attendance_and_cancellation_reverts(): void
    {
        Carbon::setTestNow('2026-11-25 15:00:00'); // Wednesday

        try {
            $date = '2026-11-25';

            // Create existing session with 'absent' record for Child 1
            $session = AttendanceSession::create([
                'class_id' => $this->class->id,
                'teacher_id' => $this->teacher->id,
                'session_date' => $date,
                'title' => 'Daily Roll Call',
                'session_type' => 'daily_homeroom',
            ]);

            $record = AttendanceRecord::create([
                'session_id' => $session->id,
                'student_id' => $this->child1->id,
                'status' => 'absent',
            ]);

            // Submit leave
            $leave = StudentLeaveRequest::create([
                'student_id' => $this->child1->id,
                'start_date' => $date,
                'end_date' => $date,
                'type' => 'medical',
                'reason' => 'Hospital appointment',
                'status' => 'pending',
            ]);

            // 1. Teacher approves leave
            $reviewRes = $this->actingAs($this->teacher)->post(route('leaves.review', $leave->id), [
                'status' => 'approved',
                'teacher_remarks' => 'Approved with medical certificate',
            ]);

            $reviewRes->assertRedirect(route('leaves.index'));

            $leave->refresh();
            $this->assertEquals('approved', $leave->status);

            // Verify attendance was synchronized to 'excused'
            $record->refresh();
            $this->assertEquals('excused', $record->status);

            // 2. Parent cancels the approved leave
            $cancelRes = $this->actingAs($this->parent)->delete(route('leaves.destroy', $leave->id));
            $cancelRes->assertRedirect();

            $leave->refresh();
            $this->assertEquals('cancelled', $leave->status);

            // Verify attendance was reverted back to 'absent'
            $record->refresh();
            $this->assertEquals('absent', $record->status);
        } finally {
            Carbon::setTestNow();
        }
    }
}
