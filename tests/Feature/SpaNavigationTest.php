<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->student = User::firstOrCreate(
            ['email' => 'student@primearc.academy'],
            [
                'name' => 'Aarav Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'PAA-STU-001',
                'streak_count' => 3,
                'xp_points' => 250,
            ]
        );
    }

    /**
     * Test that the SPA layout renders the top progress bar, the dead body skeleton structure, and the central spinner.
     */
    public function test_spa_layout_renders_progress_bar_skeleton_overlay_and_spinner(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));

        $response->assertStatus(200);

        // Top loading progress bar
        $response->assertSee('id="spa-progress"', false);

        // Dead animated body structure skeleton overlay
        $response->assertSee('id="spa-skeleton-overlay"', false);
        $response->assertSee('shimmer-active', false);

        // High-tech glowing central spinner & badge
        $response->assertSee('Loading Module');
        $response->assertSee('animate-spin', false);

        // Dynamic content container & navigation anchors
        $response->assertSee('id="spa-content"', false);
        $response->assertSee('id="top-nav-links"', false);
        $response->assertSee('id="mobile-bottom-tabs"', false);

        // SPA script inclusion
        $response->assertSee('spa-engine.js');
    }

    /**
     * Test that public/js/spa-engine.js exists and is accessible.
     */
    public function test_spa_engine_asset_file_exists_and_contains_core_logic(): void
    {
        $filePath = public_path('js/spa-engine.js');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('spa-skeleton-overlay', $content);
        $this->assertStringContainsString('spa-progress', $content);
        $this->assertStringContainsString('spa-content', $content);
        $this->assertStringContainsString('pushState', $content);
        $this->assertStringContainsString('navigate', $content);
    }

    /**
     * Test seamless XHR / SPA fetch response across multiple modules without page reload.
     */
    public function test_spa_seamless_fetch_returns_swappable_dom_elements(): void
    {
        $routes = [
            route('dashboard'),
            route('syllabus.index'),
            route('courses.index'),
            route('blooms.index'),
            route('dpp.index'),
            route('homework.index'),
            route('attendance.index'),
            route('fees.index'),
            route('notifications.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->student)
                ->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-SPA-Request' => 'true',
                ])
                ->get($url);

            $response->assertStatus(200);
            $response->assertSee('id="spa-content"', false);
            $response->assertSee('id="app-toasts"', false);
            $response->assertSee('id="top-nav-links"', false);
            $response->assertSee('id="mobile-bottom-tabs"', false);
        }
    }
}
