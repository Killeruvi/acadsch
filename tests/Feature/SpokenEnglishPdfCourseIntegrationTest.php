<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\SpokenEnglishCoursePdfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpokenEnglishPdfCourseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Lead English Faculty',
        ]);

        $this->seed(SpokenEnglishCoursePdfSeeder::class);
        $this->course = Course::where('code', 'ENG-101')->firstOrFail();
    }

    public function test_course_is_structured_with_eight_modules_and_forty_six_lessons(): void
    {
        $this->assertEquals('ENG-101', $this->course->code);
        $this->assertEquals(8, $this->course->modules()->count());
        $this->assertEquals(46, $this->course->lessons()->count());
        $this->assertEquals(6, $this->course->assessments()->count());
        $this->assertEquals(6, $this->course->dpps()->count());
        $this->assertEquals(3, $this->course->schedules()->count());
    }

    public function test_every_lesson_has_attached_pdf_and_video(): void
    {
        $lessons = $this->course->lessons()->with('videos')->get();

        foreach ($lessons as $lesson) {
            $this->assertNotEmpty($lesson->pdf_url, "Lesson {$lesson->lesson_number} must have a pdf_url");
            $this->assertStringStartsWith('/uploads/course_notes/', $lesson->pdf_url);
            $this->assertNotEmpty($lesson->video_url, "Lesson {$lesson->lesson_number} must have a video_url");
            $this->assertGreaterThanOrEqual(1, $lesson->videos->count(), "Lesson {$lesson->lesson_number} must have attached video records");
        }
    }

    public function test_curriculum_page_displays_minimal_collapsed_modules_and_weeks(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('courses.show', $this->course->id));

        $response->assertStatus(200);

        // Modules present
        $response->assertSee('Module 1: Foundations & Phonics Mastery');
        $response->assertSee('Module 4: The 12 English Tenses & Temporal Mastery');
        $response->assertSee('Module 8: Real-Life Spoken Scenarios, Listening & Professional Interviews');

        // Initial collapsed state in Alpine
        $response->assertSee('expandedModuleIds: []', false);
        $response->assertSee('expandedWeeks: {}', false);

        // Lessons encapsulated by Week
        $response->assertSee('Week 1');
        $response->assertSee('Week 7');
        $response->assertSee('Week 15');
        $response->assertSee('Lesson 1: Before We Start');
        $response->assertSee('Lesson 19: The 12 English Tenses Mastery');
        $response->assertSee('Lesson 41: 30 Real-Life Written Conversations');
    }

    public function test_curriculum_csv_is_downloadable_and_matches_all_lessons(): void
    {
        $csvPath = public_path('uploads/spoken_english_curriculum.csv');
        $this->assertFileExists($csvPath);

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertCount(47, $lines); // Header + 46 rows

        $header = str_getcsv($lines[0]);
        $this->assertContains('module_title', $header);
        $this->assertContains('lesson_title', $header);
        $this->assertContains('video_url', $header);
        $this->assertContains('pdf_url', $header);
    }
}
