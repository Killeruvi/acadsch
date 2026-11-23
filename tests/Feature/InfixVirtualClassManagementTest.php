<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfixVirtualClassManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@virtual.test',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Dr. Live Host',
            'email' => 'teacher@virtual.test',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Scholar Student',
            'email' => 'student@virtual.test',
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'stream' => 'Science',
            'section' => 'A',
            'capacity' => 40,
        ]);
    }

    /** 1. Virtual Classes Index Page Renders Cleanly */
    public function test_user_can_view_virtual_classes_index_page(): void
    {
        VirtualClass::create([
            'title' => 'Advanced Physics Live Doubt Clearing',
            'description' => 'Thermodynamics & Carnot Engine discussion',
            'class_id' => $this->class->id,
            'instructor_id' => $this->teacher->id,
            'host' => 'zoom',
            'start_time' => now()->addHours(2),
            'duration' => 60,
            'meeting_id' => '987 654 321',
            'password' => 'physics2026',
            'join_url' => 'https://zoom.us/j/987654321',
            'capacity' => 100,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($this->admin)->get(route('virtual-classes.index'));
        $response->assertStatus(200);
        $response->assertSee('Virtual Classes &amp; Live Sessions', false);
        $response->assertSee('Advanced Physics Live Doubt Clearing');
        $response->assertSee('Zoom Meeting');
        $response->assertSee('Total Sessions');
    }

    /** 2. Faculty Can Schedule a New Virtual Class Session */
    public function test_faculty_can_schedule_virtual_class(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('virtual-classes.store'), [
            'title' => 'Biology Cellular Mitosis Webinar',
            'description' => 'Deep dive into cell division phases',
            'class_id' => $this->class->id,
            'host' => 'jitsi',
            'start_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 45,
            'capacity' => 80,
        ]);

        $response->assertRedirect(route('virtual-classes.index'));
        $this->assertDatabaseHas('virtual_classes', [
            'title' => 'Biology Cellular Mitosis Webinar',
            'host' => 'jitsi',
            'instructor_id' => $this->teacher->id,
            'duration' => 45,
        ]);
    }

    /** 3. Student Can View Virtual Class Details and Join Link */
    public function test_student_can_view_virtual_class_details_and_join(): void
    {
        $vc = VirtualClass::create([
            'title' => 'Calculus Live Masterclass',
            'description' => 'Derivatives and chain rule practice',
            'class_id' => $this->class->id,
            'instructor_id' => $this->teacher->id,
            'host' => 'google_meet',
            'start_time' => now()->addHour(),
            'duration' => 90,
            'join_url' => 'https://meet.google.com/abc-defg-hij',
            'capacity' => 60,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($this->student)->get(route('virtual-classes.show', $vc->id));
        $response->assertStatus(200);
        $response->assertSee('Calculus Live Masterclass');
        $response->assertSee('Google Meet');
        $response->assertSee('Join Classroom Meeting');

        // Test Join action
        $joinResponse = $this->actingAs($this->student)->get(route('virtual-classes.join', $vc->id));
        $joinResponse->assertRedirect('https://meet.google.com/abc-defg-hij');
    }

    /** 4. Teacher Can Cancel/Delete Scheduled Virtual Class */
    public function test_instructor_can_cancel_scheduled_virtual_class(): void
    {
        $vc = VirtualClass::create([
            'title' => 'Temporary Test Session',
            'class_id' => $this->class->id,
            'instructor_id' => $this->teacher->id,
            'host' => 'custom',
            'start_time' => now()->addHours(5),
            'duration' => 30,
        ]);

        $response = $this->actingAs($this->teacher)->delete(route('virtual-classes.destroy', $vc->id));
        $response->assertRedirect(route('virtual-classes.index'));
        $this->assertDatabaseMissing('virtual_classes', [
            'id' => $vc->id,
        ]);
    }
}
