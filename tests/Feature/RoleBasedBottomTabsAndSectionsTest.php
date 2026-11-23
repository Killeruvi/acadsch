<?php

namespace Tests\Feature;

use App\Models\SchoolSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedBottomTabsAndSectionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'System Admin',
            'email' => 'admin@primearc.test',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Sharma',
            'email' => 'teacher@primearc.test',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Aarav Student',
            'email' => 'student@primearc.test',
        ]);
    }

    /** 1. Admin Bottom Tabs (Max 5 Tabs: Home, Students, Fees, Academics, More) */
    public function test_admin_has_proper_role_based_bottom_tabs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('mobile-bottom-tabs');
        $response->assertSee('Students');
        $response->assertSee('Fees');
        $response->assertSee('Academics');
        $response->assertSee('More');
    }

    /** 2. Teacher Bottom Tabs (Max 5 Tabs: Home, Attendance, Classes, Tasks, More) */
    public function test_teacher_has_proper_role_based_bottom_tabs(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('mobile-bottom-tabs');
        $response->assertSee('Attendance');
        $response->assertSee('Classes');
        $response->assertSee('Tasks');
        $response->assertSee('More');
    }

    /** 3. Student Bottom Tabs (Max 5 Tabs: Home, Learn, Attendance, Tasks, More) */
    public function test_student_has_proper_role_based_bottom_tabs(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('mobile-bottom-tabs');
        $response->assertSee('Learn');
        $response->assertSee('Attendance');
        $response->assertSee('Tasks');
        $response->assertSee('More');
    }

    /** 4. Parent Bottom Tabs (Max 5 Tabs: Home, Attendance, Fees, Leaves, More) */
    public function test_parent_has_proper_role_based_bottom_tabs(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'name' => 'Guardian Parent',
            'email' => 'parent@primearc.test',
        ]);

        $response = $this->actingAs($parent)->get(route('school.parent.dashboard'));
        $response->assertStatus(200);

        $response->assertSee('mobile-bottom-tabs');
        $response->assertSee('Attendance');
        $response->assertSee('Fees');
        $response->assertSee('Leaves');
        $response->assertSee('More');
    }

    /** 5. Sections Page Renders Rich UI (Not Raw JSON) */
    public function test_sections_page_renders_html_ui_not_raw_json(): void
    {
        SchoolSection::create([
            'name' => 'D',
            'stream' => 'General',
            'room_number' => 'Room 101',
            'capacity' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('school.sections.index'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        $response->assertSee('Reusable Academic Sections');
        $response->assertSee('Section D');
        $response->assertSee('Room 101');
        $response->assertSee('45 Seats');
        $response->assertDontSee('{"success":true');
    }

    /** 6. Sections Page Still Serves JSON For API/AJAX Requests */
    public function test_sections_page_returns_json_when_requested(): void
    {
        SchoolSection::create([
            'name' => 'E',
            'stream' => 'Science',
            'room_number' => 'Lab 3',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->json('GET', route('school.sections.index', ['stream' => 'Science']));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'name' => 'E',
            'stream' => 'Science',
        ]);
    }

    /** 7. Admin Can Create Section And Returns Redirect To Sections Index */
    public function test_admin_can_create_section_from_sections_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('school.sections.index'))
            ->post(route('school.sections.store'), [
                'name' => 'D',
                'stream' => 'General',
                'room_number' => 'Room 303',
                'capacity' => 50,
            ]);

        $response->assertRedirect(route('school.sections.index'));
        $this->assertDatabaseHas('school_sections', [
            'name' => 'D',
            'stream' => 'General',
            'room_number' => 'Room 303',
            'capacity' => 50,
        ]);
    }
}
