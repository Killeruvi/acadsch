<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\DailyDpp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DppSelectionLogoutAndLightLandingTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $class8;

    protected AcademicClass $class9;

    protected AcademicSubject $subject8;

    protected AcademicSubject $subject9;

    protected DailyDpp $dpp8;

    protected DailyDpp $dpp9;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Dr. Rosalind Franklin',
            'email' => 'rosalind@primearc.academy',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Gregor Mendel',
            'email' => 'mendel@primearc.academy',
        ]);

        $this->class8 = AcademicClass::create([
            'name' => 'Class 8',
            'section' => 'A',
            'grade_level' => 8,
            'academic_year' => '2026-2027',
        ]);

        $this->class9 = AcademicClass::create([
            'name' => 'Class 9',
            'section' => 'A',
            'grade_level' => 9,
            'academic_year' => '2026-2027',
        ]);

        $this->subject8 = AcademicSubject::create([
            'class_id' => $this->class8->id,
            'name' => 'Biology & Genetics',
            'code' => 'BIO-801',
        ]);

        $this->subject9 = AcademicSubject::create([
            'class_id' => $this->class9->id,
            'name' => 'Biotechnology & Microbiology',
            'code' => 'BIO-901',
        ]);

        $this->dpp8 = DailyDpp::create([
            'class_id' => $this->class8->id,
            'subject_id' => $this->subject8->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-08-01',
            'title' => 'DNA Double Helix & Base Pairing',
            'type' => 'dpp',
            'difficulty' => 'standard',
            'duration_minutes' => 15,
            'total_marks' => 20,
            'assigned_date' => now()->toDateString(),
        ]);

        $this->dpp9 = DailyDpp::create([
            'class_id' => $this->class9->id,
            'subject_id' => $this->subject9->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-09-01',
            'title' => 'Bacterial Culture & Plasmids',
            'type' => 'test',
            'difficulty' => 'advanced',
            'duration_minutes' => 30,
            'total_marks' => 40,
            'assigned_date' => now()->toDateString(),
        ]);
    }

    public function test_logout_works_with_both_post_and_get(): void
    {
        // 1. Test POST logout
        $this->actingAs($this->student);
        $this->assertAuthenticated();

        $postResponse = $this->post(route('logout'));
        $postResponse->assertRedirect(route('login'));
        $this->assertGuest();

        // 2. Test GET logout
        $this->actingAs($this->teacher);
        $this->assertAuthenticated();

        $getResponse = $this->get(route('logout'));
        $getResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_dpp_class_and_subject_selection_all_scenarios(): void
    {
        $this->actingAs($this->teacher);

        // Scenario 1: Select "all" classes
        $allResponse = $this->get(route('dpp.index', ['class_id' => 'all']));
        $allResponse->assertStatus(200);
        $allResponse->assertSee('DNA Double Helix & Base Pairing');
        $allResponse->assertSee('Bacterial Culture & Plasmids');

        // Scenario 2: Select Class 8 specifically
        $c8Response = $this->get(route('dpp.index', ['class_id' => $this->class8->id]));
        $c8Response->assertStatus(200);
        $c8Response->assertSee('DNA Double Helix & Base Pairing');
        $c8Response->assertDontSee('Bacterial Culture & Plasmids');

        // Scenario 3: Mismatched class & subject (e.g. selected Class 9 but old subject from Class 8)
        // Controller must auto-reset subject rather than showing a broken 0-result query
        $mismatchResponse = $this->get(route('dpp.index', [
            'class_id' => $this->class9->id,
            'subject_id' => $this->subject8->id, // Mismatched subject from Class 8
        ]));
        $mismatchResponse->assertStatus(200);
        $mismatchResponse->assertSee('Bacterial Culture & Plasmids'); // Should display Class 9 DPP because invalid subject was safely auto-reset!

        // Scenario 4: Matching class and subject
        $matchResponse = $this->get(route('dpp.index', [
            'class_id' => $this->class8->id,
            'subject_id' => $this->subject8->id,
        ]));
        $matchResponse->assertStatus(200);
        $matchResponse->assertSee('DNA Double Helix & Base Pairing');
    }

    public function test_landing_page_is_light_themed_and_biotech_themed(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // Assert NOT dark pitch-black theme
        $response->assertDontSee('bg-[#0A0A0A]');
        // Assert light background
        $response->assertSee('bg-slate-50');
        // Assert Biotechnology and DNA elements
        $response->assertSee('Biotechnology');
        $response->assertSee('DNA');
        $response->assertSee('Pure Sciences');
        $response->assertSee('Bacillus Cell Model');
        $response->assertSee('Daily Science Spark');

        // Assert slogan beneath logo is removed from public layout
        $response->assertDontSee('Biotechnology • Pure Sciences • CBT Hub');
    }

    public function test_public_related_pages_are_light_themed(): void
    {
        $routes = ['about', 'admissions', 'contact', 'privacy-policy', 'terms'];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertStatus(200);
            $response->assertDontSee('bg-[#0A0A0A]');
            $response->assertDontSee('Biotechnology • Pure Sciences • CBT Hub');
        }
    }

    public function test_college_class_and_no_slogan_in_app_layout(): void
    {
        $college = AcademicClass::create([
            'name' => 'College',
            'section' => 'A',
            'grade_level' => 13,
            'academic_year' => '2026-2027',
        ]);

        $collegeSubject = AcademicSubject::create([
            'class_id' => $college->id,
            'name' => 'Biotechnology & Genetic Engineering',
            'code' => 'BIO-UG101',
        ]);

        $collegeDpp = DailyDpp::create([
            'class_id' => $college->id,
            'subject_id' => $collegeSubject->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-COL-01',
            'title' => 'Recombinant DNA & Vector Engineering',
            'type' => 'dpp',
            'difficulty' => 'advanced',
            'duration_minutes' => 45,
            'total_marks' => 50,
            'assigned_date' => now()->toDateString(),
        ]);

        // Verify College appears on public landing page
        $landingResponse = $this->get(route('home'));
        $landingResponse->assertStatus(200);
        $landingResponse->assertSee('College');
        $landingResponse->assertSee('UNDERGRAD');

        // Verify College appears in DPP index view and slogan is not present in app layout
        $this->actingAs($this->teacher);
        $dppResponse = $this->get(route('dpp.index', ['class_id' => $college->id]));
        $dppResponse->assertStatus(200);
        $dppResponse->assertSee('College');
        $dppResponse->assertSee('Recombinant DNA & Vector Engineering');
        $dppResponse->assertDontSee('CENTER OF ACADEMIC EXCELLENCE');
    }
}
