<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpinnerAnimationAndConnectedDesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.edu',
            'name' => 'Admin Controller',
        ]);
    }

    /**
     * Test that spinner animation is rendered instead of full-screen rocket animation.
     */
    public function test_spinner_animation_renders_instead_of_fullscreen_rocket(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Spinner preloader
        $response->assertSee('infix-spinner');
        $response->assertSee('infix-preloader');
        $response->assertSee('outer-ring');
        $response->assertSee('middle-ring');
        $response->assertSee('infix-spinner-core');
        $response->assertSee('infix-spinner-orbit-dot');
        $response->assertDontSee('longfazers');
        $response->assertDontSee('speeder');

        // Inline SPA content spinner with pure blur backdrop
        $response->assertSee('spa-inline-spinner');
        $response->assertSee('spa-spinner-backdrop');
        $response->assertSee('spa-spinner-card');
    }

    /**
     * Test that page headers have unified Infix breadcrumbs and icon badges across modules.
     */
    public function test_pages_have_unified_infix_breadcrumbs_and_headers(): void
    {
        // 1. Attendance page
        $attRes = $this->actingAs($this->admin)->get(route('school.attendance.index'));
        $attRes->assertOk();
        $attRes->assertSee('Daily Attendance');
        $attRes->assertSee('ti-check-box');
        $attRes->assertSee('white-box single-summery');

        // 2. Fees page
        $feeRes = $this->actingAs($this->admin)->get(route('school.fees.index'));
        $feeRes->assertOk();
        $feeRes->assertSee('Fee Management');
        $feeRes->assertSee('ti-wallet');
        $feeRes->assertSee('white-box single-summery');

        // 3. Courses page
        $courseRes = $this->actingAs($this->admin)->get(route('courses.index'));
        $courseRes->assertOk();
        $courseRes->assertSee('Courses Catalog');
        $courseRes->assertSee('ti-book');

        // 4. Students Directory
        $stuRes = $this->actingAs($this->admin)->get(route('school.students.index'));
        $stuRes->assertOk();
        $stuRes->assertSee('Student Directory');
        $stuRes->assertSee('ti-id-badge');

        // 5. Sections Hub
        $secRes = $this->actingAs($this->admin)->get(route('school.sections.index'));
        $secRes->assertOk();
        $secRes->assertSee('Sections Hub');
        $secRes->assertSee('ti-layout-grid3');
    }

    /**
     * Test that sidebar active states stay coherent and connected.
     */
    public function test_sidebar_active_states_are_coherent(): void
    {
        $attRes = $this->actingAs($this->admin)->get(route('school.attendance.index'));
        $attRes->assertSee('active infix-nav-active');

        $feeRes = $this->actingAs($this->admin)->get(route('school.fees.index'));
        $feeRes->assertSee('active infix-nav-active');
    }
}
