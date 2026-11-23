<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\ClassEnrollment;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodTimetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableAndPeriodMasterIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher1;

    private User $teacher2;

    private User $student;

    private AcademicClass $class10A;

    private AcademicClass $class10B;

    private AcademicSubject $math10A;

    private AcademicSubject $sci10B;

    private SchoolPeriod $period1;

    private SchoolPeriod $period2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher1 = User::factory()->create(['role' => 'teacher', 'name' => 'Prof. Ramanujan']);
        $this->teacher2 = User::factory()->create(['role' => 'teacher', 'name' => 'Prof. Bose']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->class10A = AcademicClass::create([
            'name' => 'Class 10',
            'section' => 'A',
            'grade_level' => 10,
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'room_number' => 'Room 101',
        ]);

        $this->class10B = AcademicClass::create([
            'name' => 'Class 10',
            'section' => 'B',
            'grade_level' => 10,
            'stream' => 'General',
            'academic_year' => '2026-2027',
            'room_number' => 'Room 102',
        ]);

        ClassEnrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class10A->id,
            'academic_year' => '2026-2027',
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $this->math10A = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'Mathematics',
            'code' => 'MTH-10A',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 6,
        ]);

        $this->sci10B = AcademicSubject::create([
            'class_id' => $this->class10B->id,
            'name' => 'Science',
            'code' => 'SCI-10B',
            'teacher_id' => $this->teacher2->id,
            'weekly_periods' => 6,
        ]);

        $this->period1 = SchoolPeriod::firstOrCreate(
            ['period_number' => 1],
            ['name' => 'Period 1', 'start_time' => '08:30', 'end_time' => '09:15', 'is_break' => false, 'is_active' => true, 'sort_order' => 1]
        );

        $this->period2 = SchoolPeriod::firstOrCreate(
            ['period_number' => 2],
            ['name' => 'Period 2', 'start_time' => '09:15', 'end_time' => '10:00', 'is_break' => false, 'is_active' => true, 'sort_order' => 2]
        );
    }

    /** 1. Period times are auto-matched from SchoolPeriod master and user-submitted times are ignored */
    public function test_period_times_are_auto_matched_from_period_master(): void
    {
        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $this->class10A->id,
            'subject_id' => $this->math10A->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '11:00', // Submitted fake time
            'end_time' => '12:00',   // Submitted fake time
            'room_number' => 'Room 101',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('school_period_timetables', [
            'class_id' => $this->class10A->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30:00', // Exact period 1 master start time
            'end_time' => '09:15:00',   // Exact period 1 master end time
        ]);
    }

    /** 2. Non-admin cannot manage period master */
    public function test_non_admin_cannot_manage_period_master(): void
    {
        // Teacher attempt to create period
        $resTeacher = $this->actingAs($this->teacher1)->post(route('school.timetable.periods.store'), [
            'period_number' => 11,
            'name' => 'Period 11',
            'start_time' => '15:00',
            'end_time' => '15:45',
        ]);
        $resTeacher->assertForbidden();

        // Student attempt to create period
        $resStudent = $this->actingAs($this->student)->post(route('school.timetable.periods.store'), [
            'period_number' => 11,
            'name' => 'Period 11',
            'start_time' => '15:00',
            'end_time' => '15:45',
        ]);
        $resStudent->assertForbidden();
    }

    /** 3. Overlapping periods in period master are rejected */
    public function test_overlapping_periods_in_master_are_rejected(): void
    {
        // Period 1 is 08:30 - 09:15. Overlapping slot 09:00 - 09:45 must fail.
        $response = $this->actingAs($this->admin)->post(route('school.timetable.periods.store'), [
            'period_number' => 12,
            'name' => 'Clashing Period',
            'start_time' => '09:00',
            'end_time' => '09:45',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    /** 4. Conflict detection: Teacher clashing on same period and day across classes */
    public function test_teacher_clash_on_same_period_and_day_is_rejected(): void
    {
        // Schedule teacher 1 in Class 10A on Monday Period 1
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $this->math10A->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'room_number' => 'Room 101',
            'title' => 'Math',
        ]);

        // Attempt to schedule same teacher 1 in Class 10B on Monday Period 1 with teacher override
        $math10B = AcademicSubject::create([
            'class_id' => $this->class10B->id,
            'name' => 'Mathematics',
            'code' => 'MTH-10B',
            'teacher_id' => $this->teacher1->id,
            'weekly_periods' => 6,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $this->class10B->id,
            'subject_id' => $math10B->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'room_number' => 'Room 102',
        ]);

        $response->assertSessionHasErrors('teacher_id');
    }

    /** 5. Conflict detection: Room clashing on same period and day across classes */
    public function test_room_clash_on_same_period_and_day_is_rejected(): void
    {
        // Class 10A occupies Room 101 on Monday Period 1
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $this->math10A->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'room_number' => 'Room 101',
            'title' => 'Math',
        ]);

        // Class 10B attempts to book Room 101 with Teacher 2 on Monday Period 1
        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $this->class10B->id,
            'subject_id' => $this->sci10B->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'room_number' => 'Room 101', // Clash
        ]);

        $response->assertSessionHasErrors('room_number');
    }

    /** 6. Conflict detection: Class already occupied on same period and day */
    public function test_class_period_clash_is_rejected(): void
    {
        SchoolPeriodTimetable::create([
            'class_id' => $this->class10A->id,
            'subject_id' => $this->math10A->id,
            'teacher_id' => $this->teacher1->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:30',
            'end_time' => '09:15',
            'room_number' => 'Room 101',
            'title' => 'Math',
        ]);

        // Attempt to book another subject in Class 10A on same slot
        $english = AcademicSubject::create([
            'class_id' => $this->class10A->id,
            'name' => 'English',
            'code' => 'ENG-10A',
            'teacher_id' => $this->teacher2->id,
            'weekly_periods' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post(route('school.timetable.store'), [
            'class_id' => $this->class10A->id,
            'subject_id' => $english->id,
            'teacher_id' => $this->teacher2->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'room_number' => 'Room 101',
        ]);

        $response->assertSessionHasErrors('period_number');
    }
}
