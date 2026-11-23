<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MotivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotivationAndFireSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::firstOrCreate(
            ['email' => 'scholar.fire@primearc.edu.in'],
            [
                'name' => 'Aryabhata Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'PA-STD-2026-77',
                'streak_count' => 5,
                'xp_points' => 450,
            ]
        );
    }

    /**
     * Test MotivationService fire levels compute properly from streak counts.
     */
    public function test_motivation_service_computes_fire_heat_levels_accurately(): void
    {
        // 1-day streak -> Level 1 (Kindle Spark)
        $fire1 = MotivationService::getFireStatus(1);
        $this->assertEquals(1, $fire1['level']);
        $this->assertEquals('Kindle Spark', $fire1['title']);
        $this->assertEquals('🔥', $fire1['badge']);
        $this->assertGreaterThan(0, $fire1['days_to_next']);

        // 3-day streak -> Level 2 (Blazing Momentum)
        $fire3 = MotivationService::getFireStatus(3);
        $this->assertEquals(2, $fire3['level']);
        $this->assertEquals('Blazing Momentum', $fire3['title']);
        $this->assertEquals('🔥🔥', $fire3['badge']);

        // 7-day streak -> Level 3 (Raging Inferno)
        $fire7 = MotivationService::getFireStatus(7);
        $this->assertEquals(3, $fire7['level']);
        $this->assertEquals('Raging Inferno', $fire7['title']);
        $this->assertEquals('⚡🔥', $fire7['badge']);

        // 14-day streak -> Level 4 (Cosmic Supernova)
        $fire14 = MotivationService::getFireStatus(14);
        $this->assertEquals(4, $fire14['level']);
        $this->assertEquals('Cosmic Supernova', $fire14['title']);
        $this->assertEquals('🌟🔥', $fire14['badge']);

        // 35-day streak -> Level 5 (Eternal Agni Master)
        $fire35 = MotivationService::getFireStatus(35);
        $this->assertEquals(5, $fire35['level']);
        $this->assertEquals('Eternal Agni Master', $fire35['title']);
        $this->assertEquals('👑🔥', $fire35['badge']);
    }

    /**
     * Test MotivationService facts and quotes return non-empty academic payload.
     */
    public function test_motivation_service_provides_rich_scientific_and_academic_facts(): void
    {
        $fact = MotivationService::getRandomFact();
        $this->assertArrayHasKey('title', $fact);
        $this->assertArrayHasKey('category', $fact);
        $this->assertArrayHasKey('fact', $fact);
        $this->assertArrayHasKey('takeaway', $fact);
        $this->assertArrayHasKey('fire_multiplier', $fact);

        $quote = MotivationService::getRandomQuote();
        $this->assertArrayHasKey('quote', $quote);
        $this->assertArrayHasKey('author', $quote);
        $this->assertArrayHasKey('slogan', $quote);

        $payload = MotivationService::getSparkPayload(5);
        $this->assertEquals('Aim, Ascend, Achieve', $payload['slogan']);
        $this->assertEquals(2, $payload['fire']['level']); // 5-day streak is Level 2
    }

    /**
     * Test /motivation/spark endpoint returns 200 JSON with dynamic motivational sparks.
     */
    public function test_motivation_spark_api_endpoint_returns_json_payload(): void
    {
        $response = $this->actingAs($this->student)->getJson(route('motivation.spark'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'quote' => ['quote', 'author', 'slogan', 'tag'],
                    'fact' => ['title', 'category', 'fact', 'takeaway', 'fire_multiplier'],
                    'fire' => ['level', 'title', 'badge', 'label', 'description', 'intensity_pct'],
                    'slogan',
                ],
            ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertEquals('Blazing Momentum', $response->json('data.fire.title'));
    }

    /**
     * Test Dashboard renders fiery motivational station, flame animations, and interactive spark buttons.
     */
    public function test_dashboard_renders_fiery_motivation_station_and_facts(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));

        $response->assertStatus(200);

        // Motivational Knowledge Station
        $response->assertSee('id="motivation-station"', false);
        $response->assertSee('flame-active');
        $response->assertSee('Spark ⚡');
    }

    /**
     * Test Landing page renders interactive Sparks of Genius card with dynamic facts.
     */
    public function test_welcome_page_renders_interactive_sparks_of_genius(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Sparks of Genius');
        $response->assertSee('Ignite Next Spark 🔥');
        $response->assertSee('flame-active');
        $response->assertSee('Aim, Ascend, Achieve');
    }
}
