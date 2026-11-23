<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumCollapseAndSpaConfirmTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected Course $course;

    protected CourseModule $module;

    protected CourseLesson $lessonW1;

    protected CourseLesson $lessonW2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Advanced English & Communication Masterclass',
            'code' => 'ENG-999',
            'category' => 'Languages',
            'credits' => 4,
            'capacity' => 50,
            'status' => 'active',
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Mastering Conversational Eloquence',
            'order_index' => 1,
            'week_number' => 1,
            'description' => 'In-depth foundation of conversational presence.',
        ]);

        $this->lessonW1 = CourseLesson::create([
            'course_id' => $this->course->id,
            'module_id' => $this->module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 'Monday',
            'title' => 'Voice Tone & Modulation',
            'duration_minutes' => 45,
            'order_index' => 1,
        ]);

        $this->lessonW2 = CourseLesson::create([
            'course_id' => $this->course->id,
            'module_id' => $this->module->id,
            'lesson_number' => 2,
            'week_number' => 2,
            'day_of_week' => 'Tuesday',
            'title' => 'Active Listening Dynamics',
            'duration_minutes' => 50,
            'order_index' => 2,
        ]);
    }

    public function test_curriculum_modules_are_collapsed_by_default_in_alpine_state(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('courses.show', $this->course->id));

        $response->assertStatus(200);

        // Verify Alpine initialization has empty expandedModuleIds and expandedWeeks
        $response->assertSee('expandedModuleIds: []', false);
        $response->assertSee('expandedWeeks: {}', false);
        $response->assertSee('toggleWeek(modId, weekNum)', false);
    }

    public function test_module_header_has_minimal_listing_and_action_buttons(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('courses.show', $this->course->id));

        $response->assertStatus(200);

        // Verify module number and title are present in minimal header
        $response->assertSee('M1');
        $response->assertSee('Mastering Conversational Eloquence');

        // Verify delete form has data-confirm and onsubmit confirm guard with click.stop
        $response->assertSee('data-confirm="CAUTION: Are you sure you want to delete this module and all its lessons?"', false);
        $response->assertSee('onsubmit="return confirm(\'CAUTION: Are you sure you want to delete this module and all its lessons?\');"', false);
        $response->assertSee('@click.stop', false);
    }

    public function test_lessons_are_encapsulated_by_week_in_curriculum(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('courses.show', $this->course->id));

        $response->assertStatus(200);

        // Verify Week grouping is displayed
        $response->assertSee('Week 1');
        $response->assertSee('Week 2');
        $response->assertSee('Voice Tone & Modulation');
        $response->assertSee('Active Listening Dynamics');

        // Verify Week expand/collapse trigger
        $response->assertSee('toggleWeek('.$this->module->id.', 1)', false);
        $response->assertSee('toggleWeek('.$this->module->id.', 2)', false);
    }

    public function test_spa_engine_prevents_default_cancelled_confirm_submissions(): void
    {
        $spaEnginePath = public_path('js/spa-engine.js');
        $this->assertFileExists($spaEnginePath);

        $spaJs = file_get_contents($spaEnginePath);

        // Must check e.defaultPrevented before intercepting submit
        $this->assertStringContainsString('if (e.defaultPrevented) return;', $spaJs);

        // Must check data-confirm
        $this->assertStringContainsString("form.hasAttribute('data-confirm')", $spaJs);
    }
}
