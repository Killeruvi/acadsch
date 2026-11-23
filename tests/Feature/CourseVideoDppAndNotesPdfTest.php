<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\CurriculumNote;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseVideoDppAndNotesPdfTest extends TestCase
{
    use RefreshDatabase;

    private function createCourse(User $teacher): Course
    {
        return Course::create([
            'title' => 'Software Testing',
            'code' => 'TEST101',
            'category' => 'Computer Science',
            'credits' => 3,
            'capacity' => 25,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    private function createModule(Course $course): CourseModule
    {
        return CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1: Introduction',
            'description' => 'Basics of software testing',
            'order_index' => 1,
            'week_number' => 1,
        ]);
    }

    public function test_teacher_can_add_video_to_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $res = $this->actingAs($teacher)->post(route('courses.videos.store', $course->id), [
            'title' => 'Lecture 1: Intro to Dynamic Programming',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 45,
            'description' => 'Detailed lecture breakdown',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('course_lessons', [
            'course_id' => $course->id,
            'title' => 'Lecture 1: Intro to Dynamic Programming',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 45,
        ]);
    }

    public function test_teacher_can_attach_video_to_existing_lesson(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $module = $this->createModule($course);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'Initial Lesson',
        ]);

        $res = $this->actingAs($teacher)->post(route('courses.videos.store', $course->id), [
            'lesson_id' => $lesson->id,
            'title' => 'Updated Lesson Title',
            'video_url' => 'https://vimeo.com/12345678',
            'duration_minutes' => 50,
        ]);

        $res->assertRedirect();
        $lesson->refresh();
        $this->assertEquals('https://vimeo.com/12345678', $lesson->video_url);
        $this->assertEquals('Updated Lesson Title', $lesson->title);
        $this->assertEquals(50, $lesson->duration_minutes);
    }

    public function test_teacher_can_add_dpp_to_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $res = $this->actingAs($teacher)->post(route('courses.dpps.store', $course->id), [
            'title' => 'DPP 01: Core Algorithms',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'standard',
            'duration_minutes' => 30,
            'total_marks' => 20,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('daily_dpps', [
            'course_id' => $course->id,
            'title' => 'DPP 01: Core Algorithms',
            'difficulty' => 'standard',
            'duration_minutes' => 30,
            'total_marks' => 20,
        ]);
    }

    public function test_course_notes_tab_shows_only_pdf_and_no_prewritten_text(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $module = $this->createModule($course);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'Data Structures Deep Dive',
            'pdf_url' => '/uploads/course_notes/sample.pdf',
        ]);

        $response = $this->actingAs($teacher)->get(route('courses.show', ['id' => $course->id, 'tab' => 'notes']));

        $response->assertStatus(200);
        $response->assertSee('/uploads/course_notes/sample.pdf');
        // Assert that the pre-written text sections are NOT present
        $response->assertDontSee('Executive Lecture Summary');
        $response->assertDontSee('Theoretical Foundations & Core Concepts');
        $response->assertDontSee('Rapid Revision Cheatsheet');
        $response->assertDontSee('Common Misconceptions & Mistakes to Avoid');
    }

    public function test_batch_learn_shows_only_pdf_notes_and_no_prewritten_study_notes(): void
    {
        $class = AcademicClass::create(['name' => 'Grade 10', 'code' => 'G10', 'grade_level' => 10]);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $batch = Batch::create([
            'class_id' => $class->id,
            'name' => 'Alpha Batch',
            'code' => 'ALPHA-10',
            'is_active' => true,
        ]);
        $subject = AcademicSubject::create([
            'class_id' => $class->id,
            'name' => 'Mathematics',
            'code' => 'MATH10',
        ]);
        $topic = CurriculumTopic::create([
            'subject_id' => $subject->id,
            'title' => 'Real Numbers',
            'chapter_number' => 1,
            'study_notes' => 'Some pre-written text that should not appear in notes tab',
        ]);
        $note = CurriculumNote::create([
            'topic_id' => $topic->id,
            'title' => 'Real Numbers Formula Sheet PDF',
            'file_path' => 'notes/real_numbers.pdf',
            'note_type' => 'formula_sheet',
            'reading_minutes' => 15,
        ]);

        $response = $this->actingAs($teacher)->get(route('batches.learn', [
            'batch' => $batch->id,
            'subject' => $subject->id,
            'chapter' => $topic->id,
            'tab' => 'notes',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Real Numbers Formula Sheet PDF');
        // Assert that the pre-written study_notes text is NOT shown as a card
        $response->assertDontSee('Some pre-written text that should not appear in notes tab');
        $response->assertDontSee('Master Study Notes');
    }

    public function test_batch_learn_allows_teacher_to_add_video(): void
    {
        $class = AcademicClass::create(['name' => 'Grade 10', 'code' => 'G10', 'grade_level' => 10]);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $batch = Batch::create([
            'class_id' => $class->id,
            'name' => 'Alpha Batch',
            'code' => 'ALPHA-10',
            'is_active' => true,
        ]);
        $subject = AcademicSubject::create([
            'class_id' => $class->id,
            'name' => 'Mathematics',
            'code' => 'MATH10',
        ]);
        $topic = CurriculumTopic::create([
            'subject_id' => $subject->id,
            'title' => 'Real Numbers',
            'chapter_number' => 1,
        ]);

        // Check page has Add Video Lecture modal and button
        $pageResponse = $this->actingAs($teacher)->get(route('batches.learn', [
            'batch' => $batch->id,
            'subject' => $subject->id,
            'chapter' => $topic->id,
            'tab' => 'videos',
        ]));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Add Video Lecture');

        // Post new video to this chapter
        $storeRes = $this->actingAs($teacher)->post(route('syllabus.topics.videos.store', $topic->id), [
            'title' => 'Euclid Division Lemma Video',
            'video_url' => 'https://www.youtube.com/watch?v=123456',
            'duration_minutes' => 35,
            'description' => 'Explanation of Euclid Lemma',
        ]);

        $storeRes->assertRedirect();
        $this->assertDatabaseHas('curriculum_videos', [
            'topic_id' => $topic->id,
            'title' => 'Euclid Division Lemma Video',
            'video_url' => 'https://www.youtube.com/watch?v=123456',
        ]);
    }

    public function test_cannot_upload_note_pdf_when_course_has_no_lessons(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        // Course has 0 lessons
        $this->assertEquals(0, $course->lessons()->count());

        $file = UploadedFile::fake()->create('lecture_notes.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('courses.notes.upload', $course->id), [
            'pdf_file' => $file,
        ]);

        $response->assertSessionHasErrors(['pdf_file']);
        // Verify no dummy module or lesson was auto-created
        $this->assertEquals(0, $course->fresh()->lessons()->count());
    }

    public function test_can_upload_note_pdf_when_course_has_lessons(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $module = $this->createModule($course);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'Initial Lecture',
        ]);

        $file = UploadedFile::fake()->create('lecture_notes.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('courses.notes.upload', $course->id), [
            'lesson_id' => $lesson->id,
            'pdf_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull($lesson->fresh()->pdf_url);
        $this->assertStringContainsString('.pdf', $lesson->fresh()->pdf_url);
    }

    public function test_course_notes_tab_shows_advisory_when_no_lectures_exist(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)->get(route('courses.show', ['id' => $course->id, 'tab' => 'notes']));

        $response->assertStatus(200);
        $response->assertSee('No lectures in this course yet.');
        $response->assertSee('Add Lecture in Curriculum');
        $response->assertDontSee('Upload Note PDF');
    }

    public function test_lesson_supports_multiple_videos(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $module = $this->createModule($course);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'Phonetics Lesson',
        ]);

        // Add 1st video
        $this->actingAs($teacher)->post(route('courses.videos.store', $course->id), [
            'lesson_id' => $lesson->id,
            'title' => 'Part 1: Lip Articulation Masterclass',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 20,
        ])->assertRedirect();

        // Add 2nd video to same lesson
        $this->actingAs($teacher)->post(route('courses.videos.store', $course->id), [
            'lesson_id' => $lesson->id,
            'title' => 'Part 2: Mirror Drill & Minimal Pairs Practice',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_minutes' => 25,
        ])->assertRedirect();

        $lesson->refresh();
        $this->assertCount(2, $lesson->videos);
        $this->assertEquals(2, $lesson->allVideos()->count());

        $firstVideo = $lesson->videos->first();
        $secondVideo = $lesson->videos->last();
        $this->assertEquals('Part 1: Lip Articulation Masterclass', $firstVideo->title);
        $this->assertEquals('Part 2: Mirror Drill & Minimal Pairs Practice', $secondVideo->title);

        // Verify course show page renders module dropdown and both videos
        $res = $this->actingAs($teacher)->get(route('courses.show', $course->id));
        $res->assertStatus(200);
        $res->assertSee('Modules & Lessons');
        $res->assertSee('Part 1: Lip Articulation Masterclass');
        $res->assertSee('Part 2: Mirror Drill & Minimal Pairs Practice');
    }

    public function test_teacher_can_delete_lesson_video(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createCourse($teacher);
        $module = $this->createModule($course);
        $lesson = CourseLesson::create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'lesson_number' => 1,
            'week_number' => 1,
            'day_of_week' => 1,
            'title' => 'Lesson with Videos',
        ]);

        $video = \App\Models\CourseLessonVideo::create([
            'lesson_id' => $lesson->id,
            'course_id' => $course->id,
            'title' => 'Temporary Video',
            'video_url' => 'https://www.youtube.com/watch?v=test1234',
            'duration_minutes' => 15,
            'order_index' => 1,
        ]);

        $res = $this->actingAs($teacher)->delete(route('courses.videos.destroy', [$course->id, $video->id]));
        $res->assertRedirect();
        $this->assertDatabaseMissing('course_lesson_videos', ['id' => $video->id]);
    }
}
