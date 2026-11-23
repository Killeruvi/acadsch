<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LightAndDarkModeToggleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $teacher;

    protected User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_darkmode@school.edu.in',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_darkmode@school.edu.in',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher_darkmode@school.edu.in',
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent_darkmode@school.edu.in',
        ]);
    }

    public function test_header_contains_interactive_light_dark_toggle_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Header toggle button
        $response->assertSee('id="theme-toggle-btn"', false);
        $response->assertSee('onclick="toggleThemeMode()"', false);
        $response->assertSee('role="switch"', false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('theme-mode-label', false);
        $response->assertSee('ti-sun', false);
        $response->assertSee('ti-moon', false);
    }

    public function test_sidebar_and_profile_dropdown_include_theme_mode_switchers(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('dashboard'));
        $response->assertOk();

        // Sidebar theme switcher
        $response->assertSee('Theme Mode', false);
        $response->assertSee('Sidebar Bottom: Theme Mode Switcher', false);

        // Profile dropdown dark mode toggle
        $response->assertSee('Quick Dark Mode Toggle Option', false);
        $response->assertSee('ti-shine', false);
    }

    public function test_mobile_bottom_drawer_sheet_includes_dark_mode_toggle(): void
    {
        $response = $this->actingAs($this->parent)->get(route('school.parent.dashboard'));
        $response->assertOk();

        // Bottom drawer includes quick dark/light mode switch
        $response->assertSee('Quick Dark / Light Mode Switch in Bottom Drawer', false);
        $response->assertSee('Switch system appearance', false);
    }

    public function test_appearance_settings_hub_includes_universal_color_mode_selector(): void
    {
        $response = $this->actingAs($this->admin)->get(route('appearance.index'));
        $response->assertOk();

        $response->assertSee('Universal Color Mode', false);
        $response->assertSee('Light Mode', false);
        $response->assertSee('Dark Mode', false);
        $response->assertSee('Crisp white canvas', false);
        $response->assertSee('Deep OLED slate', false);
    }

    public function test_dynamic_styles_include_white_box_and_smooth_transitions_for_dark_mode(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertOk();

        $response->assertSee('.dark .white-box, .dark .white_box', false);
        $response->assertSee('html.theme-transitioning', false);
        $response->assertSee('toggleThemeMode', false);
    }
}
