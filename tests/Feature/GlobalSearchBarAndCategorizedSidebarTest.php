<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchBarAndCategorizedSidebarTest extends TestCase
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
            'email' => 'admin_nav@primeos.edu',
            'name' => 'Admin Controller',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher_nav@primeos.edu',
            'name' => 'Faculty Controller',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_nav@primeos.edu',
            'name' => 'Student Controller',
        ]);
    }

    /**
     * Test that global search bar is present in the top navigation bar and responsive.
     */
    public function test_global_search_bar_is_present_and_visible_on_all_screens(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="global-search-container"', false);
        $response->assertSee('id="infixGlobalSearch"', false);
        $response->assertSee('id="globalSearchResults"', false);
        $response->assertSee('GLOBAL_SEARCH_INDEX', false);

        // Ensure search bar is NOT hidden on mobile screens (removed 'hidden sm:flex')
        $response->assertDontSee('search_field hidden sm:flex', false);
    }

    /**
     * Test that sidebar is categorized into collapsible dropdowns with .has-arrow and .sidebar-submenu.
     */
    public function test_sidebar_is_categorized_with_dropdowns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('sidebar-dropdown', false);
        $response->assertSee('has-arrow', false);
        $response->assertSee('dropdown-chevron', false);
        $response->assertSee('sidebar-submenu', false);
        $response->assertSee('ACADEMIC HUB', false);
        $response->assertSee('LEARNING ARENA', false);
        $response->assertSee('SYSTEM ADMIN', false);
    }

    /**
     * Test that all restored items are present in the sidebar for admin.
     */
    public function test_all_restored_modules_are_present_in_admin_sidebar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();

        // 1. Academics
        $response->assertSee(route('school.classes.index'));
        $response->assertSee(route('school.sections.index'));
        $response->assertSee(route('school.subjects.index'));
        $response->assertSee(route('my-batch'));
        $response->assertSee(route('syllabus.index'));
        $response->assertSee(route('school.timetable.index'));
        $response->assertSee(route('school.holidays.index'));
        $response->assertSee(route('school.assignments.index'));
        $response->assertSee(route('blooms.index'));

        // 2. Student Info & Staff
        $response->assertSee(route('school.students.index'));
        $response->assertSee(route('students.index'));
        $response->assertSee(route('idcard.show'));
        $response->assertSee(route('admissions'));
        $response->assertSee(route('staff.index'));
        $response->assertSee(route('admin.faculty-codes.index'));

        // 3. Attendance & Fees
        $response->assertSee(route('school.attendance.index'));
        $response->assertSee(route('leaves.index'));
        $response->assertSee(route('school.fees.index'));

        // 4. Examinations
        $response->assertSee(route('school.exams.index'));
        $response->assertSee(route('school.exams.marks-entry'));
        $response->assertSee(route('certificates.index'));

        // 5. Learning Arena & Media
        $response->assertSee(route('courses.index'));
        $response->assertSee(route('dpp.index'));
        $response->assertSee(route('quizzes.index'));
        $response->assertSee(route('homework.index'));
        $response->assertSee(route('assignments.index'));
        $response->assertSee(route('projects.index'));
        $response->assertSee(route('leaderboard.index'));
        $response->assertSee(route('virtual-classes.index'));
        $response->assertSee(route('videos.index'));
        $response->assertSee(route('events.index'));

        // 6. Communication & System
        $response->assertSee(route('notices.index'));
        $response->assertSee(route('notifications.index'));
        $response->assertSee(route('settings.index'));
        $response->assertSee(route('appearance.index'));
        $response->assertSee(route('school.bulk.index'));
        $response->assertSee(route('school.onboarding.index'));
    }

    /**
     * Test that active route dropdown is automatically opened and link highlighted.
     */
    public function test_active_route_dropdown_is_opened(): void
    {
        // 1. Visit Sections Hub
        $secRes = $this->actingAs($this->admin)->get(route('school.sections.index'));
        $secRes->assertOk();
        $secRes->assertSee('dropdown-open', false);
        $secRes->assertSee('active infix-nav-active', false);

        // 2. Visit Attendance Hub
        $attRes = $this->actingAs($this->admin)->get(route('school.attendance.index'));
        $attRes->assertOk();
        $attRes->assertSee('dropdown-open', false);
        $attRes->assertSee('active infix-nav-active', false);
    }
}
