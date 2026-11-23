<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\AttendanceRejectionLog;
use App\Models\AttendanceSession;
use App\Models\SchoolPeriodTimetable;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAccessControlRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $assignedTeacher;
    private User $unassignedTeacher;
    private User $student;
    private AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        AcademySetting::set('attendance_period_grace_minutes', '5');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_access@primearc.edu',
            'is_approved' => true,
        ]);

        $this->assignedTeacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'assigned_teacher@primearc.edu',
            'is_approved' => true,
        ]);

        $this->unassignedTeacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'unassigned_teacher@primearc.edu',
            'is_approved' => true,
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Grade 10',
            'section' => 'A',
            'grade_level' => 10,
            'academic_year' => '2026-2027',
            'class_teacher_id' => $this->assignedTeacher->id,
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_attend@primearc.edu',
            'current_class_id' => $this->class->id,
            'is_approved' => true,
        ]);

        $this->class->students()->attach($this->student->id, [
            'roll_number' => 1,
            'academic_year' => '2026-2027',
            'status' => 'active',
        ]);
    }

    /**
     * Rule 1: Admin can mark attendance anytime, for any class/period, not restricted by period time.
     */
    public function test_admin_can_mark_attendance_anytime_for_any_class_and_period(): void
    {
        // Admin marking for unassigned class, outside normal class hours
        Carbon::setTestNow(Carbon::parse('2026-11-17 23:45:00')); // Tuesday midnight

        $response = $this->actingAs($this->admin)->post(route('school.attendance.store', $this->class->id), [
            'session_date' => '2026-11-17',
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                    'remarks' => 'Admin override',
                ],
            ],
        ]);

        $response->assertRedirect(route('school.attendance.index', ['date' => '2026-11-17']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_sessions', [
            'class_id' => $this->class->id,
            'title' => "Daily Roll Call - {$this->class->display_name}",
        ]);

        Carbon::setTestNow();
    }

    /**
     * Rule 2.1: Teacher cannot mark attendance for classes not assigned to them.
     */
    public function test_teacher_cannot_mark_attendance_for_unassigned_class(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:00:00')); // Monday 09:00

        $response = $this->actingAs($this->unassignedTeacher)->post(route('school.attendance.store', $this->class->id), [
            'session_date' => '2026-11-16',
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('not assigned to this class', session('error'));

        // Rule 4: Verify rejected attempt is logged
        $this->assertDatabaseHas('attendance_rejection_logs', [
            'user_id' => $this->unassignedTeacher->id,
            'class_id' => $this->class->id,
            'user_role' => 'teacher',
        ]);

        $log = AttendanceRejectionLog::where('user_id', $this->unassignedTeacher->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('not assigned', $log->reason);

        Carbon::setTestNow();
    }

    /**
     * Rule 2.2: Teacher cannot mark attendance outside their assigned period time.
     */
    public function test_teacher_cannot_mark_attendance_outside_assigned_period_time(): void
    {
        // Assign Period 1 (08:30 - 09:15) on Monday to assignedTeacher
        $subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
            'teacher_id' => $this->assignedTeacher->id,
        ]);

        SchoolPeriodTimetable::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->assignedTeacher->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30:00',
            'end_time' => '09:15:00',
            'is_break' => false,
            'title' => 'Math Period 1',
        ]);

        // Attempting at 14:00 on Monday (outside 08:30 - 09:15 period window)
        Carbon::setTestNow(Carbon::parse('2026-11-16 14:00:00'));

        $response = $this->actingAs($this->assignedTeacher)->post(route('school.attendance.store', $this->class->id), [
            'session_date' => '2026-11-16',
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Cannot mark attendance outside your assigned period time', session('error'));

        // Rule 4: Verify rejection logged with reason and timestamp
        $log = AttendanceRejectionLog::where('user_id', $this->assignedTeacher->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('outside your assigned period time', $log->reason);

        Carbon::setTestNow();
    }

    /**
     * Rule 2.3: Teacher CAN mark attendance during their assigned period time.
     */
    public function test_teacher_can_mark_attendance_during_assigned_period_time(): void
    {
        $subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Science',
            'code' => 'SCI-10',
            'teacher_id' => $this->assignedTeacher->id,
        ]);

        SchoolPeriodTimetable::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->assignedTeacher->id,
            'day_of_week' => 'Monday',
            'period_number' => 2,
            'start_time' => '09:15:00',
            'end_time' => '10:00:00',
            'is_break' => false,
            'title' => 'Science Period 2',
        ]);

        // Current time is Monday 09:30 (inside period 09:15 - 10:00)
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));

        $response = $this->actingAs($this->assignedTeacher)->post(route('school.attendance.store', $this->class->id), [
            'session_date' => '2026-11-16',
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                    'remarks' => 'Attended live period',
                ],
            ],
        ]);

        $response->assertRedirect(route('school.attendance.index', ['date' => '2026-11-16']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        Carbon::setTestNow();
    }

    /**
     * Rule 3: No geofence / location restriction exists; attendance can be marked without coordinates.
     */
    public function test_attendance_marking_succeeds_without_any_geolocation_or_ip_restriction(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 09:30:00'));

        // Admin marking without any latitude, longitude or premises IP
        $response = $this->actingAs($this->admin)->post(route('school.attendance.store', $this->class->id), [
            'session_date' => '2026-11-16',
            'records' => [
                $this->student->id => [
                    'status' => 'present',
                ],
            ],
        ]);

        $response->assertRedirect(route('school.attendance.index', ['date' => '2026-11-16']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        Carbon::setTestNow();
    }

    /**
     * Rule 4: API attendance recording enforces timetable & premises access control.
     */
    public function test_api_attendance_store_enforces_access_control(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 14:00:00'));

        // Unassigned teacher attempting via API
        $response = $this->actingAs($this->unassignedTeacher, 'sanctum')->postJson('/api/v1/attendance', [
            'class_id' => $this->class->id,
            'session_date' => '2026-11-16',
            'title' => 'Math API Session',
            'records' => [
                [
                    'student_id' => $this->student->id,
                    'status' => 'present',
                ],
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);

        Carbon::setTestNow();
    }

    /**
     * Unassigned teacher sees zero classes and pending assignment message.
     */
    public function test_unassigned_teacher_sees_waiting_message_and_no_classes(): void
    {
        $response = $this->actingAs($this->unassignedTeacher)->get(route('school.attendance.index'));
        $response->assertOk();
        $response->assertSee('Admin will assign you the classes after a while, just wait.');
        $response->assertDontSee($this->class->display_name);

        // Also verify class management listing
        $classResponse = $this->actingAs($this->unassignedTeacher)->get(route('school.classes.index'));
        $classResponse->assertOk();
        $classResponse->assertSee('Admin will assign you classes after a while');
    }

    /**
     * Parent can view and apply leave for their child on the flat portal.
     */
    public function test_parent_can_apply_leave_for_their_child(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'guardian_parent@primearc.edu',
            'is_approved' => true,
        ]);

        $this->student->update(['parent_id' => $parent->id]);

        $response = $this->actingAs($parent)->get(route('leaves.index'));
        $response->assertOk();
        $response->assertSee('Leave Management Portal');
        $response->assertSee('Apply Leave');
        $response->assertSee('View Leaves');

        $storeResponse = $this->actingAs($parent)->post(route('leaves.store'), [
            'student_id' => $this->student->id,
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-22',
            'type' => 'medical',
            'reason' => 'Doctor appointment and recovery.',
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'status' => 'pending',
            'type' => 'medical',
            'reason' => 'Doctor appointment and recovery.',
        ]);
    }

    /**
     * Parent cannot apply leave for an unrelated student (IDOR prevention).
     */
    public function test_parent_cannot_apply_leave_for_unrelated_student(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'other_parent@primearc.edu',
            'is_approved' => true,
        ]);

        $parentOwnChild = User::factory()->create([
            'role' => 'student',
            'email' => 'own_child@primearc.edu',
            'parent_id' => $parent->id,
            'is_approved' => true,
        ]);

        $unrelatedStudent = User::factory()->create([
            'role' => 'student',
            'email' => 'other_student@primearc.edu',
            'parent_id' => null,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($parent)->post(route('leaves.store'), [
            'student_id' => $unrelatedStudent->id,
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-22',
            'type' => 'medical',
            'reason' => 'Unauthorized attempt.',
        ]);

        $response->assertSessionHasErrors(['student_id']);
        $this->assertDatabaseMissing('student_leave_requests', [
            'student_id' => $unrelatedStudent->id,
            'reason' => 'Unauthorized attempt.',
        ]);
    }
}
