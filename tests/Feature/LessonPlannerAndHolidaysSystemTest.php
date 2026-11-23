<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\LessonPlan;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPlannerAndHolidaysSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $academicClass;

    protected AcademicSubject $subject;

    protected CurriculumTopic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher.planner@primearc.edu.in',
            'name' => 'Prof. S. Ramanujan',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student.planner@primearc.edu.in',
            'name' => 'Kavya Patel',
        ]);

        $this->academicClass = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->subject = AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Biotechnology & Pure Science',
            'code' => 'BIO-101',
        ]);

        $this->topic = CurriculumTopic::create([
            'subject_id' => $this->subject->id,
            'title' => 'Cell Structure & Genetics',
            'chapter_number' => 1,
            'subtopics' => [
                'Cell Organelles & Plasma Membrane',
                'DNA Double Helix & Replication',
                'Mendelian Laws & Genetic Inheritance',
            ],
            'description' => 'Fundamental study of cellular machinery and genetic inheritance.',
        ]);

        AcademicCalendarService::syncOfficialHolidays(2026);
    }

    public function test_lesson_planner_index_loads_for_student_and_displays_classes_and_subjects(): void
    {
        $response = $this->actingAs($this->student)->get(route('lesson-planner.index', [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Lesson Planners &amp; Term Pacing', false);
        $response->assertSee('Class 10');
        $response->assertSee('Biotechnology &amp; Pure Science', false);
        $response->assertSee('Holidays &amp; Festivals', false);
    }

    public function test_holidays_calendar_lists_all_major_indian_festivals_and_national_holidays(): void
    {
        $response = $this->actingAs($this->student)->get(route('lesson-planner.holidays', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertSee('National Holidays &amp; Cultural Festivals', false);
        $response->assertSee('Diwali / Deepavali &amp; Govardhan Puja', false);
        $response->assertSee('Holi (Festival of Colors)');
        $response->assertSee('Independence Day of India');
        $response->assertSee('Republic Day of India');
        $response->assertSee('Eid-ul-Fitr');
        $response->assertSee('Christmas &amp; Winter Recess', false);
        $response->assertSee('Maha Navami &amp; Dussehra', false);
    }

    public function test_faculty_can_create_custom_lesson_plan_with_pedagogy_and_objectives(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'lesson_number' => 1,
            'title' => 'Ch 01 L01: Cell Theory & Modern Cytology',
            'subtopics' => 'Cell membranes and transport mechanics',
            'learning_objectives' => 'Understand active vs passive membrane transport.',
            'pedagogy' => 'Laboratory Practical & Experiment',
            'planned_date' => '2026-07-15', // Normal Wednesday (no holiday)
            'period_slot' => 'Period 1 (08:30 - 09:30)',
            'bloom_focus' => 'Understand & Apply',
            'resources_needed' => 'Light microscope and slides',
            'practical_lab' => 'Observe onion epidermis under 40x magnification',
            'homework_assignment' => 'DPP-01 Q1-Q10',
            'term' => 'Term 1',
            'status' => 'planned',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lesson_plans', [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'title' => 'Ch 01 L01: Cell Theory & Modern Cytology',
            'pedagogy' => 'Laboratory Practical & Experiment',
            'planned_date' => '2026-07-15 00:00:00',
            'status' => 'planned',
        ]);
    }

    public function test_faculty_creating_lesson_on_holiday_triggers_holiday_shift_and_observance_note(): void
    {
        // 15 August 2026 is Independence Day of India
        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'lesson_number' => 2,
            'title' => 'Ch 01 L02: DNA Polymerase & Semiconservative Replication',
            'subtopics' => 'DNA elongation and Okazaki fragments',
            'pedagogy' => 'Interactive Concept Lecture',
            'planned_date' => '2026-08-15', // Independence Day
            'period_slot' => 'Period 2 (09:40 - 10:40)',
            'bloom_focus' => 'Apply & Analyze',
            'term' => 'Term 1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $plan = LessonPlan::whereDate('planned_date', '2026-08-15')->first();
        $this->assertNotNull($plan);
        $this->assertEquals('holiday_shift', $plan->status);
        $this->assertNotNull($plan->holiday_id);
        $this->assertStringContainsString('Independence Day', $plan->holiday_note);
    }

    public function test_faculty_can_auto_generate_lesson_plans_skipping_sundays_and_holidays(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.auto-generate'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'start_date' => '2026-08-10',
            'term' => 'Term 1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $plans = LessonPlan::where('class_id', $this->academicClass->id)
            ->where('subject_id', $this->subject->id)
            ->get();

        $this->assertGreaterThanOrEqual(3, $plans->count());

        // Assert none of the actual teaching dates land on a Sunday
        foreach ($plans as $p) {
            $this->assertFalse($p->planned_date->isSunday(), "Lesson {$p->id} should not be scheduled on Sunday");
        }
    }

    public function test_lesson_plan_detail_page_renders_learning_objectives_and_practical_lab(): void
    {
        $plan = LessonPlan::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'lesson_number' => 1,
            'title' => 'Ch 01 L01: Cytology and Mitosis',
            'subtopics' => 'Stages of Mitosis in Root Tip',
            'learning_objectives' => 'Identify Prophase, Metaphase, Anaphase, and Telophase.',
            'pedagogy' => 'Laboratory Practical & Experiment',
            'planned_date' => '2026-07-20',
            'period_slot' => 'Period 1 (08:30 - 09:30)',
            'bloom_focus' => 'Analyze & Evaluate',
            'practical_lab' => 'Stain onion root tip with acetocarmine to view chromosome alignment.',
            'homework_assignment' => 'Draw mitosis stages in practical notebook.',
            'term' => 'Term 1',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->student)->get(route('lesson-planner.show', $plan->id));

        $response->assertStatus(200);
        $response->assertSee('Ch 01 L01: Cytology and Mitosis');
        $response->assertSee('Identify Prophase, Metaphase, Anaphase, and Telophase.');
        $response->assertSee('Stain onion root tip with acetocarmine');
        $response->assertSee('Laboratory Practical &amp; Experiment', false);
    }

    public function test_faculty_can_update_and_delete_lesson_plan(): void
    {
        $plan = LessonPlan::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'lesson_number' => 1,
            'title' => 'Original Lesson Plan Title',
            'planned_date' => '2026-07-22',
            'period_slot' => 'Period 1 (08:30 - 09:30)',
            'pedagogy' => 'Interactive Concept Lecture',
            'bloom_focus' => 'Understand & Apply',
            'term' => 'Term 1',
            'status' => 'planned',
        ]);

        // Update
        $updateResponse = $this->actingAs($this->teacher)->put(route('lesson-planner.update', $plan->id), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'lesson_number' => 1,
            'title' => 'Updated Lesson Plan Title: Quantum Biology',
            'planned_date' => '2026-07-23',
            'period_slot' => 'Period 2 (09:40 - 10:40)',
            'pedagogy' => 'Case Study & Inquiry Seminar',
            'bloom_focus' => 'Evaluate & Create',
            'term' => 'Term 1',
            'status' => 'completed',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('lesson_plans', [
            'id' => $plan->id,
            'title' => 'Updated Lesson Plan Title: Quantum Biology',
            'status' => 'completed',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->teacher)->delete(route('lesson-planner.destroy', $plan->id));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('lesson_plans', ['id' => $plan->id]);
    }

    public function test_student_cannot_create_or_edit_lesson_plans_returns_403(): void
    {
        $createResponse = $this->actingAs($this->student)->get(route('lesson-planner.create'));
        $createResponse->assertStatus(403);

        $storeResponse = $this->actingAs($this->student)->post(route('lesson-planner.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'lesson_number' => 1,
            'title' => 'Unauthorized Student Plan',
            'planned_date' => '2026-07-25',
            'period_slot' => 'Period 1',
            'pedagogy' => 'Lecture',
            'bloom_focus' => 'Recall',
            'term' => 'Term 1',
        ]);
        $storeResponse->assertStatus(403);
    }

    public function test_lesson_plans_export_csv_streams_proper_data(): void
    {
        LessonPlan::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'lesson_number' => 1,
            'title' => 'CSV Exportable Lesson Plan',
            'planned_date' => '2026-07-28',
            'period_slot' => 'Period 1',
            'pedagogy' => 'Interactive Lecture',
            'bloom_focus' => 'Apply',
            'term' => 'Term 1',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->student)->get(route('lesson-planner.export-csv', [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
        ]));

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('CSV Exportable Lesson Plan', $content);
        $this->assertStringContainsString('BIO-101', $content);
    }

    public function test_faculty_can_add_custom_holiday_updating_lesson_schedule(): void
    {
        // First create a lesson on the target date
        $plan = LessonPlan::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'lesson_number' => 1,
            'title' => 'Pre-existing Session on Sports Day',
            'planned_date' => '2026-09-18',
            'period_slot' => 'Period 1',
            'pedagogy' => 'Interactive Lecture',
            'bloom_focus' => 'Apply',
            'term' => 'Term 1',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->teacher)->post(route('lesson-planner.holidays.store'), [
            'name' => 'Annual Academy Sports Gala',
            'holiday_date' => '2026-09-18',
            'type' => 'institutional',
            'badge_icon' => '🏆',
            'description' => 'Inter-house athletic meet and campus tournaments.',
            'is_instructional_off' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_holidays', [
            'name' => 'Annual Academy Sports Gala',
            'badge_icon' => '🏆',
        ]);

        // Assert the pre-existing lesson on that date was automatically marked holiday_shift
        $plan->refresh();
        $this->assertEquals('holiday_shift', $plan->status);
        $this->assertNotNull($plan->holiday_id);
    }
}
