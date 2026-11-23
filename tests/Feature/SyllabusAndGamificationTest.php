<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SyllabusAndGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->seed(\Database\Seeders\BloomsTaxonomySeeder::class);
        $this->seed(\Database\Seeders\SyllabusSeeder::class);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@primearc.academy'],
            [
                'name' => 'Dr. Rajeshwar Sharma',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'student_id_number' => 'ADM-001',
                'streak_count' => 5,
                'xp_points' => 1500,
                'academic_tier' => 'Distinguished Scholar',
            ]
        );

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher@primearc.academy'],
            [
                'name' => 'Dr. Vikramaditya Sen',
                'password' => bcrypt('password123'),
                'role' => 'teacher',
                'student_id_number' => 'FAC-001',
                'streak_count' => 3,
                'xp_points' => 600,
                'academic_tier' => 'Honors Scholar',
            ]
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student@primearc.academy'],
            [
                'name' => 'Arjun Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'PA-STD-2026-99',
                'streak_count' => 2,
                'xp_points' => 150,
                'academic_tier' => 'Junior Scholar',
            ]
        );
    }

    public function test_syllabus_index_renders_with_classes_and_slogan(): void
    {
        $response = $this->actingAs($this->student)->get('/syllabus');

        $response->assertStatus(200);
        $response->assertSee('Chapterwise Academic Syllabus');
        $response->assertSee('Developed by Prithviraj with love for India 🇮🇳');
        $response->assertSee('Aim, Ascend, Achieve');
        $response->assertSee('space-y-2.5');
        $response->assertDontSee('PostgreSQL');
        $response->assertDontSee('Laravel 13');
    }

    public function test_syllabus_sample_csv_download(): void
    {
        $response = $this->actingAs($this->teacher)->get('/syllabus/sample-csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('chapter_number', $response->streamedContent());
        $this->assertStringContainsString('Introduction to Data Structures', $response->streamedContent());
    }

    public function test_syllabus_csv_import_creates_topics(): void
    {
        $class = AcademicClass::firstOrCreate(
            ['grade_level' => 11],
            ['name' => 'Class 11', 'section' => 'A', 'academic_year' => '2026-2027']
        );

        $subject = AcademicSubject::firstOrCreate(
            ['class_id' => $class->id, 'name' => 'Robotics & AI'],
            ['code' => 'ROB-11']
        );

        $csvContent = "chapter_number,title,subtopics,description\n"
                    . "1,Foundations of Autonomous Agents,Perception; Planning; Actuation,Mathematical principles of agentic loops\n"
                    . "2,Deep Reinforcement Learning,Q-learning; Policy Gradients,Reward optimization in continuous control environments";

        $file = UploadedFile::fake()->createWithContent('robotics_syllabus.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post('/syllabus/import-csv', [
            'subject_id' => $subject->id,
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('curriculum_topics', [
            'subject_id' => $subject->id,
            'title' => 'Foundations of Autonomous Agents',
            'chapter_number' => 1,
        ]);

        $this->assertDatabaseHas('curriculum_topics', [
            'subject_id' => $subject->id,
            'title' => 'Deep Reinforcement Learning',
            'chapter_number' => 2,
        ]);
    }

    public function test_blooms_sample_student_csv_and_bulk_roster_import(): void
    {
        $class = AcademicClass::firstOrCreate(
            ['grade_level' => 9],
            ['name' => 'Class 9', 'section' => 'A', 'academic_year' => '2026-2027']
        );

        // Test sample download
        $sampleResponse = $this->actingAs($this->teacher)->get('/blooms/sample-students-csv');
        $sampleResponse->assertStatus(200);
        $sampleResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('roll_number,name,email', $sampleResponse->streamedContent());

        // Test bulk upload
        $csvContent = "roll_number,name,email\n"
                    . "101,Devavrata Bhishma,bhishma.dev@primearc.academy\n"
                    . "102,Savitri Devi,savitri.devi@primearc.academy";

        $file = UploadedFile::fake()->createWithContent('students_roster.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post('/blooms/import-students-csv', [
            'class_id' => $class->id,
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'bhishma.dev@primearc.academy',
            'name' => 'Devavrata Bhishma',
            'role' => 'student',
        ]);

        $newStudent = User::where('email', 'bhishma.dev@primearc.academy')->first();
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $class->id,
            'student_id' => $newStudent->id,
            'roll_number' => 101,
        ]);
    }

    public function test_streak_and_xp_gamification_progression(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'xp_points' => 450,
            'streak_count' => 1,
            'last_active_date' => now()->subDay()->toDateString(),
            'academic_tier' => 'Junior Scholar',
        ]);

        // Trigger daily activity with XP gain that crosses the 500 threshold
        $user->recordDailyActivity(75);

        $user->refresh();
        $this->assertEquals(2, $user->streak_count);
        $this->assertEquals(525, $user->xp_points);
        $this->assertEquals('Honors Scholar', $user->academic_tier);
        $this->assertEquals(now()->toDateString(), $user->last_active_date);
    }

    public function test_welcome_page_has_no_tech_stack_mentions_and_features_bharat_credit(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Prime Arc Academy');
        $response->assertSee('Developed by Prithviraj with love for India 🇮🇳');
        $response->assertSee('Aim, Ascend, Achieve');
        $response->assertDontSee('Laravel');
        $response->assertDontSee('PostgreSQL');
    }
}
