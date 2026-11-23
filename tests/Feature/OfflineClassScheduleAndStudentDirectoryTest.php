<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\Batch;
use App\Models\ClassEnrollment;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineClassScheduleAndStudentDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student1;
    protected User $student2;
    protected AcademicClass $class8;
    protected AcademicClass $class9;
    protected Batch $batch8;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Principal Administrator',
            'email' => 'admin@primearc.edu.in',
            'role' => 'admin',
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Prof. Vikram Sen',
            'email' => 'vikram.sen@primearc.edu.in',
            'role' => 'teacher',
        ]);

        $this->class8 = AcademicClass::create([
            'name' => 'Class 8',
            'grade_level' => 8,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->class9 = AcademicClass::create([
            'name' => 'Class 9',
            'grade_level' => 9,
            'section' => 'B',
            'academic_year' => '2026-2027',
        ]);

        $this->batch8 = Batch::create([
            'class_id' => $this->class8->id,
            'name' => 'Class 8 - Morning Star',
            'code' => 'BATCH-08-M',
            'shift' => 'morning',
            'timing_slot' => '07:30 AM - 10:30 AM',
            'start_time' => '07:30',
            'end_time' => '10:30',
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Advanced Physics & Thermodynamics',
            'code' => 'PHY-301',
            'description' => 'Comprehensive core physics curriculum.',
        ]);

        $this->student1 = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav.sharma@primearc.edu.in',
            'role' => 'student',
            'student_id_number' => 'PA-STD-2026-01',
            'batch_id' => $this->batch8->id,
            'phone' => '+91 98765 43210',
            'guardian_name' => 'Ramesh Sharma',
            'guardian_phone' => '+91 98111 22334',
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'class_id' => $this->class8->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'status' => 'active',
        ]);

        $this->student2 = User::factory()->create([
            'name' => 'Bhavna Verma',
            'email' => 'bhavna.verma@primearc.edu.in',
            'role' => 'student',
            'student_id_number' => 'PA-STD-2026-02',
            'phone' => '+91 98765 99887',
            'guardian_name' => 'Sanjay Verma',
            'guardian_phone' => '+91 98222 33445',
            'is_approved' => true,
        ]);

        ClassEnrollment::create([
            'class_id' => $this->class9->id,
            'student_id' => $this->student2->id,
            'roll_number' => 12,
            'status' => 'active',
        ]);
    }

    public function test_can_create_offline_class_schedule_slot(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('schedules.store'), [
            'course_id' => $this->course->id,
            'class_id' => $this->class8->id,
            'batch_id' => $this->batch8->id,
            'delivery_mode' => 'offline',
            'title' => 'In-Person Physics Lab Practical',
            'day_of_week' => 'Monday',
            'start_time' => '08:30',
            'end_time' => '09:30',
            'room' => 'Hall 302 - Main Campus',
            'description' => 'Hands-on offline laboratory session.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('schedules', [
            'course_id' => $this->course->id,
            'class_id' => $this->class8->id,
            'batch_id' => $this->batch8->id,
            'delivery_mode' => 'offline',
            'title' => 'In-Person Physics Lab Practical',
            'day_of_week' => 'Monday',
            'room' => 'Hall 302 - Main Campus',
        ]);
    }

    public function test_can_filter_schedules_by_offline_mode(): void
    {
        Schedule::create([
            'course_id' => $this->course->id,
            'class_id' => $this->class8->id,
            'delivery_mode' => 'offline',
            'title' => 'Offline Chemistry Session',
            'day_of_week' => 'Tuesday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'room' => 'Lab 1',
        ]);

        Schedule::create([
            'course_id' => $this->course->id,
            'class_id' => $this->class8->id,
            'delivery_mode' => 'online',
            'title' => 'Virtual Webinar on Waves',
            'day_of_week' => 'Tuesday',
            'start_time' => '11:30',
            'end_time' => '12:30',
            'room' => 'Online',
        ]);

        $response = $this->actingAs($this->admin)->get(route('schedules.index', ['mode' => 'offline']));
        $response->assertStatus(200);
        $response->assertSee('Offline Chemistry Session');
        $response->assertSee('Offline Classroom');
    }

    public function test_offline_gateway_page_renders_successfully(): void
    {
        $response = $this->get(route('offline'));
        $response->assertStatus(200);
        $response->assertSee('Offline Class Schedule');
        $response->assertSee('offline-schedule-container');
    }

    public function test_student_directory_renders_list_view_with_students(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.index'));
        $response->assertStatus(200);
        $response->assertSee('Student Directory &amp; Academic Roster', false);
        $response->assertSee('Aarav Sharma');
        $response->assertSee('Bhavna Verma');
        $response->assertSee('PA-STD-2026-01');
        $response->assertSee('PA-STD-2026-02');
        $response->assertSee('Class 8');
        $response->assertSee('Class 9');
    }

    public function test_student_directory_filters_class_wise(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.index', [
            'class_id' => $this->class8->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Aarav Sharma');
        $response->assertDontSee('Bhavna Verma');
    }

    public function test_student_directory_search_filter(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('students.index', [
            'search' => 'Bhavna',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Bhavna Verma');
        $response->assertDontSee('Aarav Sharma');
    }

    public function test_student_directory_pagination(): void
    {
        // Seed 15 additional students
        for ($i = 3; $i <= 18; $i++) {
            $st = User::factory()->create([
                'name' => "Student {$i}",
                'email' => "student{$i}@primearc.edu.in",
                'role' => 'student',
                'student_id_number' => sprintf('PA-STD-2026-%02d', $i),
                'is_approved' => true,
            ]);
            ClassEnrollment::create([
                'class_id' => $this->class8->id,
                'student_id' => $st->id,
                'roll_number' => $i,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('students.index', [
            'per_page' => 10,
            'page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('students');
        $paginated = $response->viewData('students');
        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(18, $paginated->total());
        $this->assertEquals(2, $paginated->lastPage());
    }

    public function test_student_directory_exports_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.export-csv'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('Student ID', $content);
        $this->assertStringContainsString('Full Name', $content);
        $this->assertStringContainsString('Aarav Sharma', $content);
        $this->assertStringContainsString('PA-STD-2026-01', $content);
        $this->assertStringContainsString('Bhavna Verma', $content);
    }

    public function test_student_directory_grab_json(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.grab-json'));
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'count' => 2,
        ]);
        $response->assertJsonFragment([
            'name' => 'Aarav Sharma',
            'student_id_number' => 'PA-STD-2026-01',
        ]);
    }

    public function test_student_roster_print_sheet_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.roster-print', [
            'class_id' => $this->class8->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Official Student Class Register');
        $response->assertSee('Aarav Sharma');
        $response->assertSee('PA-STD-2026-01');
    }

    public function test_student_role_is_forbidden_from_student_directory(): void
    {
        $response = $this->actingAs($this->student1)->get(route('students.index'));
        $response->assertStatus(403);

        $csvResponse = $this->actingAs($this->student1)->get(route('students.export-csv'));
        $csvResponse->assertStatus(403);

        $jsonResponse = $this->actingAs($this->student1)->get(route('students.grab-json'));
        $jsonResponse->assertStatus(403);
    }
}
