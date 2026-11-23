<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\BloomAssignment;
use App\Models\CurriculumTopic;
use App\Models\DailyLesson;
use App\Models\User;
use App\Services\BloomsAssignmentService;
use Database\Seeders\BloomsTaxonomySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloomsTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(BloomsTaxonomySeeder::class);
    }

    public function test_curriculum_skeleton_has_classes_and_mapped_bloom_levels(): void
    {
        // Assert Classes 1 through 8 exist
        $this->assertEquals(8, AcademicClass::whereBetween('grade_level', [1, 8])->count());

        // Assert Class 8 Science exists
        $class8 = AcademicClass::where('grade_level', 8)->first();
        $this->assertNotNull($class8);

        $science8 = AcademicSubject::where('class_id', $class8->id)->where('name', 'Science')->first();
        $this->assertNotNull($science8);

        // Assert Photosynthesis topic has all 6 Bloom levels
        $topic = CurriculumTopic::where('subject_id', $science8->id)->where('title', 'like', '%Photosynthesis%')->first();
        $this->assertNotNull($topic);

        $this->assertEquals(6, $topic->bloomActivities()->count());
        $levels = $topic->bloomActivities()->pluck('bloom_level')->sort()->values()->toArray();
        $this->assertEquals([1, 2, 3, 4, 5, 6], $levels);
    }

    public function test_balanced_random_roll_number_distribution(): void
    {
        $class8 = AcademicClass::where('grade_level', 8)->first();
        $subject = AcademicSubject::where('class_id', $class8->id)->first();
        $topic = CurriculumTopic::where('subject_id', $subject->id)->first();
        $teacher = User::where('role', 'teacher')->first();

        $lesson = DailyLesson::create([
            'class_id' => $class8->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'teacher_id' => $teacher->id,
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'balanced_random',
        ]);

        $service = new BloomsAssignmentService;
        $assignments = $service->distributeAssignments($lesson, 'balanced_random');

        $studentCount = $class8->enrollments()->count();
        $this->assertGreaterThan(0, $studentCount);
        $this->assertEquals($studentCount, $assignments->count());

        // Assert all roll numbers received an assignment
        $assignedRolls = $assignments->pluck('roll_number')->unique()->sort()->values()->toArray();
        $expectedRolls = range(1, $studentCount);
        $this->assertEquals($expectedRolls, $assignedRolls);

        // Assert every Bloom level from 1 to 6 was assigned at least once (since 12 students >= 6)
        $distinctLevels = $assignments->pluck('bloom_level')->unique()->sort()->values()->toArray();
        $this->assertEquals([1, 2, 3, 4, 5, 6], $distinctLevels);
    }

    public function test_uniform_level_distribution(): void
    {
        $class8 = AcademicClass::where('grade_level', 8)->first();
        $subject = AcademicSubject::where('class_id', $class8->id)->first();
        $topic = CurriculumTopic::where('subject_id', $subject->id)->first();
        $teacher = User::where('role', 'teacher')->first();

        $lesson = DailyLesson::create([
            'class_id' => $class8->id,
            'subject_id' => $subject->id,
            'topic_id' => $topic->id,
            'teacher_id' => $teacher->id,
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'uniform_level',
            'target_level' => 3,
        ]);

        $service = new BloomsAssignmentService;
        $assignments = $service->distributeAssignments($lesson, 'uniform_level', 3);

        $distinctLevels = $assignments->pluck('bloom_level')->unique()->toArray();
        $this->assertEquals([3], $distinctLevels);
        $this->assertEquals('Apply', $assignments->first()->level_name);
    }

    public function test_student_can_view_assignment_and_submit_response(): void
    {
        $student = User::where('role', 'student')->first();
        $assignment = BloomAssignment::where('student_id', $student->id)->first();

        $this->assertNotNull($assignment);

        // Student visits student Bloom's page
        $response = $this->actingAs($student)->get('/blooms');
        $response->assertStatus(200)
            ->assertSee('Cognitive Progress Tracker')
            ->assertSee('Active & Past Daily Prompts');

        // Student submits solution
        $submitResponse = $this->actingAs($student)->post("/blooms/assignments/{$assignment->id}/submit", [
            'submission_content' => 'Tested starch synthesis with dark box and iodine reagent. Result confirms chlorophyll activity.',
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('bloom_assignments', [
            'id' => $assignment->id,
            'status' => 'submitted',
        ]);
    }

    public function test_teacher_can_evaluate_student_bloom_assignment(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $assignment = BloomAssignment::where('status', 'submitted')->first() ?? BloomAssignment::first();

        $response = $this->actingAs($teacher)->post("/blooms/assignments/{$assignment->id}/grade", [
            'score' => 94.5,
            'teacher_feedback' => 'Clear scientific reasoning aligned with Bloom criteria.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bloom_assignments', [
            'id' => $assignment->id,
            'score' => 94.50,
            'status' => 'reviewed',
            'reviewed_by' => $teacher->id,
        ]);
    }

    public function test_teacher_can_view_lesson_breakdown(): void
    {
        $teacher = User::where('role', 'teacher')->first();
        $lesson = DailyLesson::first();

        $response = $this->actingAs($teacher)->get("/blooms/lessons/{$lesson->id}");
        $response->assertStatus(200)
            ->assertSee('Roll-Number Assignment Roster')
            ->assertSee($lesson->topic->title);
    }

    public function test_ajax_curriculum_endpoint_returns_json(): void
    {
        $admin = User::where('role', 'admin')->first();
        $class8 = AcademicClass::where('grade_level', 8)->first();

        $response = $this->actingAs($admin)->getJson("/blooms/curriculum-data/{$class8->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'subjects' => [
                    '*' => ['id', 'name', 'code', 'topics'],
                ],
            ]);
    }
}
