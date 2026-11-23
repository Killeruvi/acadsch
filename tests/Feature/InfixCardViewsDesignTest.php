<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfixCardViewsDesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $teacher;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin_cardview@school.edu.in',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher_cardview@school.edu.in',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_cardview@school.edu.in',
        ]);

        $this->course = Course::create([
            'title' => 'Advanced Infix Architecture',
            'code' => 'INFX-401',
            'category' => 'Computer Science',
            'credits' => 4,
            'capacity' => 45,
            'teacher_id' => $this->teacher->id,
            'status' => 'published',
            'description' => 'Comprehensive masterclass on modular enterprise LMS architectures.',
        ]);
    }

    public function test_dynamic_styles_include_infix_cardview_definitions(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));
        $response->assertOk();

        // Checks for core Infix card classes
        $response->assertSee('.white-box.single-summery', false);
        $response->assertSee('.couse_wizged, .course_wizged', false);
        $response->assertSee('.couse_wizged .thumb', false);
        $response->assertSee('.couse_wizged .prise_tag', false);
        $response->assertSee('.couse_wizged .course_content', false);
        $response->assertSee('.couse_wizged .course_less_students', false);
        $response->assertSee('.single_member_card', false);
        $response->assertSee('.box_header', false);
    }

    public function test_courses_index_renders_infix_course_wizard_cards(): void
    {
        $response = $this->actingAs($this->student)->get(route('courses.index'));
        $response->assertOk();

        // Infix course card structure
        $response->assertSee('couse_wizged', false);
        $response->assertSee('thumb', false);
        $response->assertSee('prise_tag', false);
        $response->assertSee('course_content', false);
        $response->assertSee('rating_cart', false);
        $response->assertSee('rateing', false);
        $response->assertSee('course_less_students', false);
        $response->assertSee('Advanced Infix Architecture', false);
        $response->assertSee('INFX-401', false);
        $response->assertSee('4 Cr', false);
    }

    public function test_student_directory_renders_infix_single_member_cards_and_view_mode_toggle(): void
    {
        $response = $this->actingAs($this->admin)->get(route('school.students.index'));
        $response->assertOk();

        // View mode toggle
        $response->assertSee("viewMode: 'cards'", false);
        $response->assertSee('single_member_card', false);
        $response->assertSee('white-box single-summery', false);
        $response->assertSee('gradient-color2', false);
    }

    public function test_student_directory_supports_ajax_results_for_live_search_and_pagination(): void
    {
        $response = $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get(route('school.students.index', ['search' => 'student']));

        $response->assertOk();
        $response->assertJsonStructure(['html', 'total']);
        $this->assertStringContainsString('student-directory-results', (string) $response->json('html'));
    }

    public function test_classes_and_sections_render_infix_white_box_cards(): void
    {
        $responseClasses = $this->actingAs($this->admin)->get(route('school.classes.index'));
        $responseClasses->assertOk();
        $responseClasses->assertSee('white-box', false);

        $responseSections = $this->actingAs($this->admin)->get(route('school.sections.index'));
        $responseSections->assertOk();
        $responseSections->assertSee('white-box single-summery', false);
        $responseSections->assertSee('gradient-color2', false);
    }
}
