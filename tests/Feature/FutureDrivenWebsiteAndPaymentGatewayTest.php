<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FutureDrivenWebsiteAndPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_treasury@primeos.edu',
            'name' => 'Admin Controller',
        ]);
    }

    /**
     * Test that Institutional Settings page renders Static QR & Treasury setup fields, and no gateways.
     */
    public function test_institutional_settings_renders_payment_gateway_setup(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('School Payment QR Code', false);
        $response->assertSee('upi_vpa', false);
        $response->assertSee('bank_account', false);

        // Verification: All gateway fields and webhook URLs are stripped
        $response->assertDontSee('razorpay_key_id', false);
        $response->assertDontSee('stripe_publishable_key', false);
        $response->assertDontSee('cashfree_app_id', false);
        $response->assertDontSee('/api/v1/fees/webhook', false);
    }

    /**
     * Test that admin can configure and save school treasury and QR settings.
     */
    public function test_admin_can_update_payment_gateway_settings(): void
    {
        $payload = [
            'institute_name' => 'Greenwood Public School',
            'campus_name' => 'Main Innovation Campus',
            'academic_session' => '2026-2027',
            'currency_symbol' => '₹',
            'currency_code' => 'INR',
            'upi_vpa' => 'greenwood@okhdfcbank',
            'auto_receipt_generate' => '1',
            'bank_name' => 'HDFC Bank',
            'bank_account_number' => '50100492819382',
            'bank_ifsc' => 'HDFC0001234',
            'bank_account_holder' => 'Greenwood Educational Trust',
            'bank_branch' => 'Institutional Tech Park',
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.update'), $payload);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $config = PaymentGatewayService::getConfig();
        $this->assertEquals('none', $config['active_gateway']);
        $this->assertEquals('manual', $config['mode']);
        $this->assertEquals('₹', $config['currency_symbol']);

        $enabledGateways = PaymentGatewayService::getEnabledGateways();
        $this->assertEmpty($enabledGateways);
    }

    /**
     * Test gateway connection ping endpoint has been removed.
     */
    public function test_gateway_connection_ping_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/settings/test-gateway', [
            'gateway' => 'razorpay',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that public landing page renders the world-class future-driven institution design and copy.
     */
    public function test_public_landing_page_renders_future_driven_vision(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        // 1. Core Vision & Hero Headline
        $response->assertSee("Tomorrow's World Needs More Than Employees.", false);
        $response->assertSee("It Needs Creators.");
        $response->assertSee("Don't just prepare students for the future.", false);
        $response->assertSee('Explore Our Vision');
        $response->assertSee('Discover Our Learning Approach');

        // 2. Brand Positioning Pillars
        $response->assertSee('Discovery over Memorization');
        $response->assertSee('Creation over Consumption');
        $response->assertSee('Understanding over Rote Learning');
        $response->assertSee('Innovation over Repetition');
        $response->assertSee('Entrepreneurship over Dependency');
        $response->assertSee('Opportunity Creation');

        // 3. The 6 Educational Philosophy Pillars
        $response->assertSee('Discovery &amp; Scientific Thinking', false);
        $response->assertSee('Innovation &amp; Invention', false);
        $response->assertSee('Entrepreneurship &amp; Job Creation', false);
        $response->assertSee('Advanced Technology');
        $response->assertSee('Problem-Solving &amp; Critical Thinking', false);
        $response->assertSee('Communication &amp; Collaboration', false);

        // 4. The 8-Stage Transformation Cycle
        $response->assertSee('CURIOSITY');
        $response->assertSee('QUESTIONING');
        $response->assertSee('DISCOVERY');
        $response->assertSee('UNDERSTANDING');
        $response->assertSee('EXPERIMENTATION');
        $response->assertSee('INVENTION');
        $response->assertSee('ENTREPRENEURSHIP');
        $response->assertSee('IMPACT');

        // 5. Real-World Learning
        $response->assertSee('From Classroom Knowledge to Real-World Impact');
        $response->assertSee('Biological Solutions to Environmental Waste');
        $response->assertSee('AI Diagnostic Models in Healthcare &amp; Crops', false);
        $response->assertSee('Renewable Micro-Grids &amp; Storage Chemistry', false);

        // 6. Pathways
        $response->assertSee('Scientists &amp; Researchers', false);
        $response->assertSee('Engineers &amp; Inventors', false);
        $response->assertSee('Technology Developers', false);
        $response->assertSee('Founders &amp; Entrepreneurs', false);

        // 7. Academic Foundations
        $response->assertSee('Mathematics');
        $response->assertSee('Physics');
        $response->assertSee('Chemistry');
        $response->assertSee('Biology');
        $response->assertSee('Computer Science');

        // 8. Founder's Vision & Closing Statement
        $response->assertSee('An Education System That Creates Possibilities.');
        $response->assertSee('Learn what exists.');
        $response->assertSee('Discover what is possible.');
        $response->assertSee('Create what comes next.');

        // 9. Real Images Presence
        $response->assertSee('hero-students-learning.jpg');
        $response->assertSee('action-science-experiment.jpg');
        $response->assertSee('action-technology-coding.jpg');
    }
}
