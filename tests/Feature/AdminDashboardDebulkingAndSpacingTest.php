<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardDebulkingAndSpacingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'principal@primeos.edu',
            'name' => 'Principal Henderson',
        ]);
    }

    /**
     * Test that Admin dashboard incorporates domain quick-filter pill tabs to de-clutter the view.
     */
    public function test_admin_dashboard_renders_domain_quick_filter_tabs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('activeSection: \'all\'', false);
        $response->assertSee('All Operations');
        $response->assertSee('Daily Snapshot');
        $response->assertSee('Academics &amp; Rosters', false);
        $response->assertSee('Finance &amp; Admissions', false);
        $response->assertSee('Trends &amp; Alerts', false);
        $response->assertSee('Quick Actions');
        $response->assertSee('Visual Analytics');
    }

    /**
     * Test that Admin dashboard utilizes airy spacing and Infix card standards.
     */
    public function test_admin_dashboard_utilizes_airy_spacing_and_box_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('space-y-8 sm:space-y-10');
        $response->assertSee('box_header');
        $response->assertSee('white-box single-summery');
        $response->assertSee('rounded-2xl');
    }

    /**
     * Test that all 10 priority operational domains render correctly with proper metrics.
     */
    public function test_all_operational_domains_render_with_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        // Priority sections
        $response->assertSee("Today's Snapshot", false);
        $response->assertSee('Admissions Pipeline');
        $response->assertSee('Fees &amp; Finance', false);
        $response->assertSee('Visual Analytics &amp; Institutional Trends', false);
        $response->assertSee('Academic Operations');
        $response->assertSee('Student &amp; Staff Overview', false);
        $response->assertSee('Leave &amp; Attendance Trends', false);
        $response->assertSee('Circulars &amp; Communication', false);
        $response->assertSee('Alerts &amp; Anomalies', false);
        $response->assertSee('Quick Actions');
        $response->assertSee('Recent Activity Feed');
    }
}
