<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AppearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfixThemesAndAppearanceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_theme@school.edu.in',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher_theme@school.edu.in',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_theme@school.edu.in',
        ]);
    }

    public function test_guest_is_redirected_from_appearance_hub(): void
    {
        $response = $this->get(route('appearance.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_and_teacher_cannot_access_appearance_hub(): void
    {
        $response = $this->actingAs($this->student)->get(route('appearance.index'));
        $response->assertForbidden();

        $responseTeacher = $this->actingAs($this->teacher)->get(route('appearance.index'));
        $responseTeacher->assertForbidden();
    }

    public function test_admin_can_access_appearance_hub_and_view_themes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('appearance.index'));
        $response->assertOk();
        $response->assertSee('Themes &amp; Appearance Management', false);
        $response->assertSee('Infix LMS Classic', false);
        $response->assertSee('Infix LMS Vibrant Pink', false);
        $response->assertSee('Edume Modern Ocean', false);
        $response->assertSee('WeTech Tech Emerald', false);
        $response->assertSee('Royal Indigo &amp; Violet', false);
        $response->assertSee('Dark Sapphire Elegance', false);
    }

    public function test_admin_can_activate_infix_themes(): void
    {
        $response = $this->actingAs($this->admin)->post(route('appearance.activate'), [
            'theme_id' => 'infix_vibrant',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appearance = AppearanceService::get();
        $this->assertEquals('infix_vibrant', $appearance['active_key']);
        $this->assertEquals('#FB1159', $appearance['theme']['primary_color']);

        // Switch to Edume
        $this->actingAs($this->admin)->post(route('appearance.activate'), [
            'theme_id' => 'edume_blue',
        ]);
        $appearance = AppearanceService::get();
        $this->assertEquals('edume_blue', $appearance['active_key']);
        $this->assertEquals('#0066FF', $appearance['theme']['primary_color']);

        // Switch back to Infix Classic
        $this->actingAs($this->admin)->post(route('appearance.activate'), [
            'theme_id' => 'infix_classic',
        ]);
        $appearance = AppearanceService::get();
        $this->assertEquals('infix_classic', $appearance['active_key']);
        $this->assertEquals('#7c32ff', $appearance['theme']['primary_color']);
    }

    public function test_admin_can_customize_color_palette(): void
    {
        $response = $this->actingAs($this->admin)->post(route('appearance.customize'), [
            'primary_color' => '#8B5CF6',
            'secondary_color' => '#1E1B4B',
            'accent_color' => '#EC4899',
            'bg_color' => '#FAF5FF',
            'footer_bg' => '#0F172A',
            'footer_text' => '#94A3B8',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appearance = AppearanceService::get();
        $this->assertEquals('#8B5CF6', $appearance['theme']['primary_color']);
        $this->assertEquals('#1E1B4B', $appearance['theme']['secondary_color']);
    }

    public function test_admin_can_update_typography_fonts(): void
    {
        $response = $this->actingAs($this->admin)->post(route('appearance.fonts'), [
            'family1' => 'Source Sans Pro',
            'family2' => 'Jost',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appearance = AppearanceService::get();
        $this->assertEquals('Source Sans Pro', $appearance['fonts']['family1']);
        $this->assertEquals('Jost', $appearance['fonts']['family2']);
    }

    public function test_admin_can_configure_preloader(): void
    {
        $response = $this->actingAs($this->admin)->post(route('appearance.preloader'), [
            'preloader_status' => '1',
            'preloader_style' => 'circle',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appearance = AppearanceService::get();
        $this->assertTrue($appearance['preloader']['status']);
        $this->assertEquals('circle', $appearance['preloader']['style']);
    }

    public function test_public_landing_and_dashboard_render_infix_theme_variables(): void
    {
        $responseHome = $this->get(route('home'));
        $responseHome->assertOk();
        $responseHome->assertSee('infix-appearance-style', false);
        $responseHome->assertSee('--system_primery_color', false);
        $responseHome->assertSee('infix-preloader', false);

        $responseDashboard = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('infix-appearance-style', false);
        $responseDashboard->assertSee('--system_primary_gradient', false);
    }
}
