<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAndFullscreenTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::firstOrCreate(
            ['email' => 'student.pwa@primearc.edu.in'],
            [
                'name' => 'Aarav Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'PA-STD-2026-99',
            ]
        );
    }

    /**
     * Test that Web App Manifest file exists, is valid JSON, and contains PWA attributes.
     */
    public function test_pwa_manifest_exists_and_contains_valid_configuration(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $jsonContent = file_get_contents($manifestPath);
        $manifest = json_decode($jsonContent, true);

        $this->assertNotNull($manifest, 'manifest.json must be valid JSON');
        $this->assertEquals('Prime Arc Academy - Enterprise Academic Portal', $manifest['name']);
        $this->assertEquals('Prime Arc', $manifest['short_name']);
        $this->assertEquals('/dashboard', $manifest['start_url']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('#0f172a', $manifest['theme_color']);
        $this->assertEquals('#0f172a', $manifest['background_color']);

        // Assert icons are configured
        $this->assertNotEmpty($manifest['icons']);
        $iconSrcs = array_column($manifest['icons'], 'src');
        $this->assertContains('/icons/icon.svg', $iconSrcs);
        $this->assertContains('/icons/icon-192x192.png', $iconSrcs);
        $this->assertContains('/icons/icon-512x512.png', $iconSrcs);
        $this->assertContains('/icons/maskable-icon-512x512.png', $iconSrcs);

        // Assert shortcuts
        $this->assertNotEmpty($manifest['shortcuts']);
        $shortcutUrls = array_column($manifest['shortcuts'], 'url');
        $this->assertContains('/dashboard', $shortcutUrls);
        $this->assertContains('/dpp', $shortcutUrls);
        $this->assertContains('/blooms', $shortcutUrls);
    }

    /**
     * Test that Service Worker asset exists and contains caching strategies.
     */
    public function test_service_worker_asset_exists_and_contains_lifecycle_logic(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString('CACHE_NAME', $swContent);
        $this->assertStringContainsString('OFFLINE_URL', $swContent);
        $this->assertStringContainsString('/offline', $swContent);
        $this->assertStringContainsString("self.addEventListener('install'", $swContent);
        $this->assertStringContainsString("self.addEventListener('activate'", $swContent);
        $this->assertStringContainsString("self.addEventListener('fetch'", $swContent);
    }

    /**
     * Test that PWA & Fullscreen JavaScript manager exists and contains toggle logic.
     */
    public function test_pwa_fullscreen_js_asset_exists_and_contains_toggle_logic(): void
    {
        $jsPath = public_path('js/pwa-fullscreen.js');
        $this->assertFileExists($jsPath);

        $jsContent = file_get_contents($jsPath);
        $this->assertStringContainsString('toggleAppFullscreen', $jsContent);
        $this->assertStringContainsString('requestFullscreen', $jsContent);
        $this->assertStringContainsString('exitFullscreen', $jsContent);
        $this->assertStringContainsString('fullscreenchange', $jsContent);
        $this->assertStringContainsString('beforeinstallprompt', $jsContent);
        $this->assertStringContainsString('triggerPwaInstall', $jsContent);
        $this->assertStringContainsString('sw.js', $jsContent);
    }

    /**
     * Test that PWA icon files exist on disk.
     */
    public function test_pwa_icons_exist(): void
    {
        $this->assertFileExists(public_path('icons/icon.svg'));
        $this->assertFileExists(public_path('icons/icon-72x72.png'));
        $this->assertFileExists(public_path('icons/icon-96x96.png'));
        $this->assertFileExists(public_path('icons/icon-144x144.png'));
        $this->assertFileExists(public_path('icons/icon-192x192.png'));
        $this->assertFileExists(public_path('icons/icon-512x512.png'));
        $this->assertFileExists(public_path('icons/maskable-icon-192x192.png'));
        $this->assertFileExists(public_path('icons/maskable-icon-512x512.png'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
        $this->assertFileExists(public_path('favicon.ico'));
    }

    /**
     * Test that the dedicated /offline fallback route returns 200 with institutional theme.
     */
    public function test_offline_page_renders_with_institutional_details(): void
    {
        $response = $this->get(route('offline'));
        $response->assertStatus(200);
        $response->assertSee('Offline Mode');
        $response->assertSee('Connection Interrupted');
        $response->assertSee('Prime Arc Academy');
        $response->assertSee('Aim, Ascend, Achieve');
        $response->assertSee('Developed by Prithviraj with love for India 🇮🇳');
    }

    /**
     * Test that landing page renders PWA manifest link and Fullscreen button.
     */
    public function test_landing_page_renders_pwa_and_fullscreen_controls(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('manifest.json');
        $response->assertSee('viewport-fit=cover');
        $response->assertSee('id="fullscreen-toggle-btn"', false);
        $response->assertSee('id="pwa-install-btn"', false);
        $response->assertSee('pwa-fullscreen.js');
    }

    /**
     * Test that authenticated application layout renders PWA & Fullscreen controls.
     */
    public function test_authenticated_layout_renders_pwa_and_fullscreen_controls(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertStatus(200);

        // PWA Manifest and mobile meta tags
        $response->assertSee('manifest.json');
        $response->assertSee('theme-color');
        $response->assertSee('apple-mobile-web-app-capable');
        $response->assertSee('viewport-fit=cover');

        // Fullscreen toggle button & icons
        $response->assertSee('id="fullscreen-toggle-btn"', false);
        $response->assertSee('id="fs-expand-icon"', false);
        $response->assertSee('id="fs-compress-icon"', false);

        // PWA Install prompt triggers (desktop & mobile)
        $response->assertSee('id="pwa-install-btn"', false);
        $response->assertSee('id="pwa-mobile-install-btn"', false);

        // Script inclusion
        $response->assertSee('pwa-fullscreen.js');
    }
}