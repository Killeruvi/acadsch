<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\HomeworkTask;
use App\Models\TopicBloomActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumStructuralSupportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $class10;

    protected AcademicSubject $mathSubject;

    protected CurriculumTopic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Principal Dr. Sharma',
            'email' => 'admin@primearc.edu.in',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Vikramaditya',
            'email' => 'teacher@primearc.edu.in',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Aarav Patel',
            'email' => 'aarav@student.primearc.edu.in',
        ]);

        $this->class10 = AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
            'section' => 'A',
            'academic_year' => '2026-2027',
        ]);

        $this->mathSubject = AcademicSubject::create([
            'class_id' => $this->class10->id,
            'name' => 'Mathematics',
            'code' => 'MATH-10',
        ]);

        $this->topic = CurriculumTopic::create([
            'subject_id' => $this->mathSubject->id,
            'chapter_number' => 1,
            'title' => 'Real Numbers',
            'description' => 'Fundamental theorem of arithmetic and irrationality proofs.',
            'subtopics' => ['Euclid Lemma', 'Prime Factorisation', 'Irrational Numbers'],
        ]);

        // Attach Bloom activities
        for ($i = 1; $i <= 6; $i++) {
            TopicBloomActivity::create([
                'topic_id' => $this->topic->id,
                'bloom_level' => $i,
                'level_name' => 'Level '.$i,
                'verb_keywords' => 'Recall, Solve, Analyze',
                'activity_prompt' => "Bloom activity prompt for level {$i}",
                'rubric_guide' => "Demonstrate level {$i} criteria",
            ]);
        }
    }

    public function test_syllabus_index_loads_with_class_tabs_and_structural_metrics(): void
    {
        $response = $this->actingAs($this->student)->get(route('syllabus.index', ['grade' => 10]));

        $response->assertStatus(200);
        $response->assertSee('Academic Syllabus &amp; Curriculum', false);
        $response->assertSee('Class 10');
        $response->assertSee('Real Numbers');
    }

    public function test_chapter_show_view_displays_all_structural_tabs(): void
    {
        // Seed Video
        $video = CurriculumVideo::create([
            'topic_id' => $this->topic->id,
            'subject_id' => $this->mathSubject->id,
            'title' => 'Lecture 01: Fundamental Theorem of Arithmetic',
            'video_url' => 'https://www.youtube.com/watch?v=Ytkbvd-iB0w',
            'video_provider' => 'youtube',
            'duration_seconds' => 1800,
            'order_index' => 1,
            'description' => 'Detailed lecture on prime factorisation.',
        ]);

        // Seed DPP
        $dpp = DailyDpp::create([
            'class_id' => $this->class10->id,
            'subject_id' => $this->mathSubject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'DPP-10-MATH-01',
            'title' => 'DPP #1: Real Numbers Practice',
            'assigned_date' => now()->toDateString(),
            'due_time' => '23:59',
            'total_marks' => 20,
            'difficulty' => 'standard',
            'duration_minutes' => 30,
            'is_timed' => true,
        ]);

        // Seed Homework
        $hw = HomeworkTask::create([
            'class_id' => $this->class10->id,
            'subject_id' => $this->mathSubject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Homework Task #1: Real Numbers Exercises',
            'instructions' => 'Solve exercise 1.1 questions 1 to 5.',
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($this->student)->get(route('syllabus.show', $this->topic->id));

        $response->assertStatus(200);
        $response->assertSee('Real Numbers');
        $response->assertSee('Lecture 01: Fundamental Theorem of Arithmetic');
        $response->assertSee('DPP #1: Real Numbers Practice');
        $response->assertSee('Homework Task #1: Real Numbers Exercises');
        $response->assertSee("Bloom's Taxonomy Cognitive Progression", false);
    }

    public function test_teacher_can_add_video_to_chapter(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('syllabus.topics.videos.store', $this->topic->id), [
            'title' => 'Lecture 02: Proving Irrationality of Root 2',
            'video_url' => 'https://www.youtube.com/watch?v=fF_L4o-C2pM',
            'duration_minutes' => 35,
            'description' => 'Contradiction proof for irrational numbers.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('curriculum_videos', [
            'topic_id' => $this->topic->id,
            'title' => 'Lecture 02: Proving Irrationality of Root 2',
            'duration_seconds' => 2100,
        ]);
    }

    public function test_teacher_can_add_dpp_to_chapter(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('syllabus.topics.dpps.store', $this->topic->id), [
            'title' => 'DPP #2: Prime Factorisation Test',
            'difficulty' => 'advanced',
            'duration_minutes' => 25,
            'pass_percentage' => 70,
            'negative_marks' => 1.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_dpps', [
            'topic_id' => $this->topic->id,
            'title' => 'DPP #2: Prime Factorisation Test',
            'difficulty' => 'advanced',
            'duration_minutes' => 25,
        ]);
    }

    public function test_teacher_can_add_homework_to_chapter(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('syllabus.topics.homework.store', $this->topic->id), [
            'title' => 'Homework #2: Proof of Theorems',
            'instructions' => 'Write formal proofs for Theorem 1.1 and Theorem 1.2.',
            'due_days' => 4,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('homework_tasks', [
            'topic_id' => $this->topic->id,
            'title' => 'Homework #2: Proof of Theorems',
        ]);
    }

    public function test_student_can_toggle_video_watched_status(): void
    {
        $video = CurriculumVideo::create([
            'topic_id' => $this->topic->id,
            'subject_id' => $this->mathSubject->id,
            'title' => 'Lecture on Real Numbers',
            'video_url' => 'https://www.youtube.com/watch?v=Ytkbvd-iB0w',
            'video_provider' => 'youtube',
            'duration_seconds' => 1500,
            'order_index' => 1,
        ]);

        $this->assertFalse($video->isWatchedBy($this->student->id));

        // Toggle to watched
        $response = $this->actingAs($this->student)->post(route('videos.toggle-watched', $video->id));
        $response->assertRedirect();

        $this->assertTrue($video->fresh()->isWatchedBy($this->student->id));

        // Toggle back to unwatched
        $this->actingAs($this->student)->post(route('videos.toggle-watched', $video->id));
        $this->assertFalse($video->fresh()->isWatchedBy($this->student->id));
    }

    public function test_video_hub_lists_and_filters_videos(): void
    {
        CurriculumVideo::create([
            'topic_id' => $this->topic->id,
            'subject_id' => $this->mathSubject->id,
            'title' => 'Lecture on Real Numbers',
            'video_url' => 'https://www.youtube.com/watch?v=Ytkbvd-iB0w',
            'video_provider' => 'youtube',
            'duration_seconds' => 1500,
            'order_index' => 1,
        ]);

        $response = $this->actingAs($this->student)->get(route('videos.index', ['grade' => 10]));
        $response->assertStatus(200);
        $response->assertSee('Digital Video Classroom');
        $response->assertSee('Lecture on Real Numbers');
    }

    public function test_video_show_page_renders_player(): void
    {
        $video = CurriculumVideo::create([
            'topic_id' => $this->topic->id,
            'subject_id' => $this->mathSubject->id,
            'title' => 'Lecture on Real Numbers',
            'video_url' => 'https://www.youtube.com/watch?v=Ytkbvd-iB0w',
            'video_provider' => 'youtube',
            'duration_seconds' => 1500,
            'order_index' => 1,
        ]);

        $response = $this->actingAs($this->student)->get(route('videos.show', $video->id));
        $response->assertStatus(200);
        $response->assertSee('Lecture on Real Numbers');
        $response->assertSee('youtube-nocookie.com/embed/Ytkbvd-iB0w');
    }
}
