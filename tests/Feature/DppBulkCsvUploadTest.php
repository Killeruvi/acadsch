<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DppBulkCsvUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected AcademicClass $academicClass;
    protected AcademicSubject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher_bulk@primearc.academy'],
            [
                'name' => 'Prof. Homi Bhabha',
                'password' => bcrypt('password123'),
                'role' => 'teacher',
                'faculty_code' => 'FAC-999',
                'device_registration_token' => 'device_token_teacher_bulk',
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student_bulk@primearc.academy'],
            [
                'name' => 'Aryan Ramanujan',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'STU-999',
            ]
        );

        $this->academicClass = AcademicClass::first() ?? AcademicClass::create([
            'name' => 'Class 10',
            'grade_level' => 10,
        ]);

        $this->subject = AcademicSubject::first() ?? AcademicSubject::create([
            'class_id' => $this->academicClass->id,
            'name' => 'Physics',
            'code' => 'PHY10',
        ]);
    }

    /**
     * Test sample CSV template download.
     */
    public function test_sample_csv_can_be_downloaded_by_authenticated_users(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('dpp.sample-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('primearc_dpp_sample_template.csv', $response->headers->get('content-disposition'));

        // Stream output
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Question', $content);
        $this->assertStringContainsString('Option A', $content);
        $this->assertStringContainsString('Correct Option', $content);
        $this->assertStringContainsString('Explanation', $content);
        $this->assertStringContainsString('What is the SI unit of electric potential difference?', $content);
    }

    /**
     * Test creating a DPP with an uploaded CSV file.
     */
    public function test_dpp_can_be_created_using_uploaded_csv_file(): void
    {
        $csvContent = "Question,Option A,Option B,Option C,Option D,Correct Option,Explanation,Marks\n" .
            "\"What is the unit of force?\",\"Joule\",\"Newton\",\"Watt\",\"Pascal\",\"B\",\"Force is measured in Newtons (N) in SI units.\",4\n" .
            "\"What is the acceleration due to gravity on Earth?\",\"9.8 m/s^2\",\"8.9 m/s^2\",\"11.2 m/s^2\",\"5.0 m/s^2\",\"A\",\"Standard Earth gravity g is approx 9.8 m/s^2.\",4\n" .
            "\"Which law states F = ma?\",\"First Law\",\"Second Law\",\"Third Law\",\"Zeroth Law\",\"B\",\"Newton's second law of motion states F = ma.\",4\n";

        $file = UploadedFile::fake()->createWithContent('physics_test.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post(route('dpp.store'), [
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'type' => 'test',
            'title' => 'Newtonian Mechanics Weekly Test',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'standard',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'negative_marks' => 1.0,
            'csv_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $dpp = DailyDpp::where('title', 'Newtonian Mechanics Weekly Test')->first();
        $this->assertNotNull($dpp);
        $this->assertEquals(3, $dpp->questions()->count());
        $this->assertEquals(12, $dpp->total_marks);

        $q1 = $dpp->questions()->where('question_number', 1)->first();
        $this->assertEquals('What is the unit of force?', $q1->question_text);
        $this->assertEquals('Newton', $q1->option_b);
        $this->assertEquals('B', $q1->correct_option);
    }

    /**
     * Test exporting DPP questions to CSV.
     */
    public function test_dpp_questions_can_be_exported_to_csv(): void
    {
        $dpp = DailyDpp::create([
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'TEST-10-99',
            'type' => 'test',
            'title' => 'Thermodynamics Assessment',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'advanced',
            'duration_minutes' => 45,
            'total_marks' => 8,
        ]);

        DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 1,
            'question_text' => 'What is absolute zero temperature?',
            'option_a' => '0 Kelvin',
            'option_b' => '0 Celsius',
            'option_c' => '-100 Celsius',
            'option_d' => '273 Kelvin',
            'correct_option' => 'A',
            'explanation' => '0 Kelvin corresponds to -273.15 Celsius.',
            'marks' => 4,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('dpp.export-csv', $dpp->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('What is absolute zero temperature?', $content);
        $this->assertStringContainsString('0 Kelvin', $content);
    }

    /**
     * Test bulk import into Question Bank from CSV.
     */
    public function test_bulk_import_questions_into_question_bank(): void
    {
        $csvContent = "Question,Option A,Option B,Option C,Option D,Correct Option,Explanation,Marks\n" .
            "\"What is Ohm's Law?\",\"V = IR\",\"P = VI\",\"F = ma\",\"E = mc^2\",\"A\",\"Ohm's Law states that V = IR.\",4\n" .
            "\"What is the unit of resistance?\",\"Ohm\",\"Volt\",\"Ampere\",\"Tesla\",\"A\",\"Resistance is measured in Ohms.\",4\n";

        $file = UploadedFile::fake()->createWithContent('ohms_law.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post(route('dpp.bulk-import'), [
            'target' => 'bank',
            'class_id' => $this->academicClass->id,
            'subject_id' => $this->subject->id,
            'difficulty' => 'standard',
            'csv_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('question_bank', [
            'question_text' => "What is Ohm's Law?",
            'correct_option' => 'A',
        ]);
        $this->assertDatabaseHas('question_bank', [
            'question_text' => 'What is the unit of resistance?',
            'correct_option' => 'A',
        ]);
    }
}
