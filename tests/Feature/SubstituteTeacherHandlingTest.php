<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodSubstitute;
use App\Models\SchoolPeriodTimetable;
use App\Models\User;
use App\Services\AttendanceAccessControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SubstituteTeacherHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacherPrimary;

    private User $teacherSubstitute;

    private User $teacherBusy;

    private AcademicClass $class10;

    private AcademicSubject $math;

    private SchoolPeriodTimetable $slotPeriod1;

    protected function setUp(): void
    {
        parent::setUp();

        AcademySetting::set('onboarding_completed', 'true');
        AcademySetting::set('academic_session', '2026-27');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacherPrimary = User::factory()->create([
            'name' => 'Prof. Primary Teacher',
            'email' => 'primary@test.com',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->teacherSubstitute = User::factory()->create([
            'name' => 'Prof. Proxy Substitute',
            'email' => 'substitute@test.com',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->teacherBusy = User::factory()->create([
            'name' => 'Prof. Busy Teacher',
            'email' => 'busy@test.com',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->class10 = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-27',
            'capacity' => 40,
        ]);

        $this->math = AcademicSubject::create([
            'class_id' => $this->class10->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
            'teacher_id' => $this->teacherPrimary->id,
            'weekly_periods' => 6,
        ]);

        SchoolPeriod::firstOrCreate(
            ['period_number' => 1],
            [
                'name' => 'Period 1',
                'start_time' => '08:00:00',
                'end_time' => '08:45:00',
                'is_break' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $this->slotPeriod1 = SchoolPeriodTimetable::create([
            'class_id' => $this->class10->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacherPrimary->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
            'room_number' => 'Room 101',
        ]);
    }

    public function test_admin_can_assign_substitute_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.timetable.substitutes.store'), [
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23', // Monday
            'reason' => 'Primary teacher on medical leave',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_period_substitutes', [
            'timetable_id' => $this->slotPeriod1->id,
            'original_teacher_id' => $this->teacherPrimary->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23',
            'reason' => 'Primary teacher on medical leave',
            'status' => 'active',
        ]);
    }

    public function test_substitute_conflict_detection_when_teacher_has_live_period(): void
    {
        // Busy teacher has another class in Period 1 on Monday
        $otherClass = AcademicClass::create([
            'name' => 'Class 9',
            'grade_level' => 9,
            'section' => 'A',
            'academic_year' => '2026-27',
            'capacity' => 40,
        ]);

        SchoolPeriodTimetable::create([
            'class_id' => $otherClass->id,
            'teacher_id' => $this->teacherBusy->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.timetable.substitutes.store'), [
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherBusy->id,
            'date' => '2026-11-23',
        ]);

        $response->assertSessionHasErrors('substitute_teacher_id');
        $this->assertDatabaseMissing('school_period_substitutes', [
            'substitute_teacher_id' => $this->teacherBusy->id,
        ]);
    }

    public function test_substitute_conflict_detection_when_teacher_already_proxy_in_another_class(): void
    {
        // Assign substitute to another class in Period 1
        $otherClass = AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'section' => 'A',
            'academic_year' => '2026-27',
            'capacity' => 40,
        ]);

        $otherSlot = SchoolPeriodTimetable::create([
            'class_id' => $otherClass->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
        ]);

        SchoolPeriodSubstitute::create([
            'timetable_id' => $otherSlot->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23',
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        // Attempting to assign same substitute for class 10 in Period 1 on same date must be rejected
        $response = $this->actingAs($this->admin)->post(route('school.timetable.substitutes.store'), [
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23',
        ]);

        $response->assertSessionHasErrors('substitute_teacher_id');
    }

    public function test_admin_can_cancel_substitute(): void
    {
        $sub = SchoolPeriodSubstitute::create([
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23',
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('school.timetable.substitutes.destroy', $sub->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    public function test_teacher_cannot_assign_substitute(): void
    {
        $response = $this->actingAs($this->teacherPrimary)->post(route('school.timetable.substitutes.store'), [
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => '2026-11-23',
        ]);

        $response->assertForbidden();
    }

    public function test_substitute_teacher_is_authorized_in_access_control_service(): void
    {
        $today = now()->toDateString();
        SchoolPeriodSubstitute::create([
            'timetable_id' => $this->slotPeriod1->id,
            'substitute_teacher_id' => $this->teacherSubstitute->id,
            'date' => $today,
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $service = app(AttendanceAccessControlService::class);
        $request = Request::create('/school/attendance', 'POST');
        $result = $service->validateAttendanceAttempt(
            $request,
            $this->teacherSubstitute,
            $this->class10,
            $today,
            1,
            'store'
        );

        $this->assertStringNotContainsString('not assigned to this class', (string) ($result['reason'] ?? ''));
    }
}
