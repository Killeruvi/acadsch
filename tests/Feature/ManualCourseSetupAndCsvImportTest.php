<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManualCourseSetupAndCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $admin;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::create([
            'name' => 'Prof. Alan Turing',
            'email' => 'turing@primearc.edu',
            'password' => Hash::make('Secret123!'),
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Dean Administrator',
            'email' => 'dean@primearc.edu',
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->course = Course::create([
            'code' => 'CS-401',
            'title' => 'Compiler Design & Formal Grammars',
            'category' => 'Computer Science',
            'credits' => 4,
            'capacity' => 35,
            'teacher_id' => $this->teacher->id,
            'description' => 'Lexical analysis, parsing, AST generation, and LLVM intermediate code emission.',
            'status' => 'published',
            'is_published' => true,
        ]);
    }

    public function test_course_starts_clean_without_auto_generated_dummy_content(): void
    {
        $this->assertEquals(0, $this->course->modules()->count());
        $this->assertEquals(0, $this->course->lessons()->count());
        $this->assertEquals(0, $this->course->dpps()->count());

        $response = $this->actingAs($this->teacher)->get(route('courses.show', $this->course->id));
        $response->assertStatus(200);
        $response->assertSee('Curriculum Structure &amp; Content Management', false);
        $response->assertSee('Bulk Import CSV');
        $response->assertSee('+ Module');
        $response->assertSee('+ Lesson');
        $response->assertDontSee('🚀 Auto-Generate');
    }

    public function test_direct_manual_module_creation_edit_and_delete(): void
    {
        // 1. Create module
        $createRes = $this->actingAs($this->teacher)->post(route('courses.modules.store', $this->course->id), [
            'title' => 'Unit 1: Lexical Analysis & Finite Automata',
            'order_index' => 1,
            'theme' => 'Lexers',
            'description' => 'Regex to DFA conversion, token streaming, and buffer pair implementations.',
        ]);

        $createRes->assertRedirect();
        $this->assertDatabaseHas('course_modules', [
            'course_id' => $this->course->id,
            'title' => 'Unit 1: Lexical Analysis & Finite Automata',
            'order_index' => 1,
        ]);

        $module = CourseModule::where('course_id', $this->course->id)->first();
        $this->assertNotNull($module);

        // 2. Update module
        $updateRes = $this->actingAs($this->teacher)->put(route('courses.modules.update', [$this->course->id, $module->id]), [
            'title' => 'Unit 1: Lexical Analysis & DFA Algorithms (Updated)',
            'order_index' => 1,
            'theme' => 'Lexers & Automata',
            'description' => 'Extended coverage of Thompson construction.',
        ]);

        $updateRes->assertRedirect();
        $this->assertDatabaseHas('course_modules', [
            'id' => $module->id,
            'title' => 'Unit 1: Lexical Analysis & DFA Algorithms (Updated)',
        ]);

        // 3. Delete module
        $delRes = $this->actingAs($this->teacher)->delete(route('courses.modules.destroy', [$this->course->id, $module->id]));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
    }

    public function test_direct_manual_lesson_creation_edit_and_delete(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Unit 2: Parsing & Syntax Trees',
            'order_index' => 1,
            'week_number' => 1,
        ]);

        // 1. Create lesson
        $createRes = $this->actingAs($this->teacher)->post(route('courses.lessons.store', $this->course->id), [
            'module_id' => $module->id,
            'title' => 'LL(1) Top-Down Parsing & First/Follow Sets',
            'lesson_number' => 1,
            'duration_minutes' => 50,
            'video_url' => 'https://www.youtube.com/watch?v=LL1ParsingDemo',
            'topic_summary' => 'Computing FIRST and FOLLOW sets for context-free grammars.',
        ]);

        $createRes->assertRedirect();
        $this->assertDatabaseHas('course_lessons', [
            'course_id' => $this->course->id,
            'module_id' => $module->id,
            'title' => 'LL(1) Top-Down Parsing & First/Follow Sets',
            'lesson_number' => 1,
            'duration_minutes' => 50,
            'video_url' => 'https://www.youtube.com/watch?v=LL1ParsingDemo',
        ]);

        $lesson = CourseLesson::where('course_id', $this->course->id)->first();
        $this->assertNotNull($lesson);

        // 2. Update lesson
        $updateRes = $this->actingAs($this->teacher)->put(route('courses.lessons.update', [$this->course->id, $lesson->id]), [
            'module_id' => $module->id,
            'title' => 'LL(1) & Predictive Parsing Table Construction',
            'lesson_number' => 1,
            'duration_minutes' => 60,
            'video_url' => 'https://www.youtube.com/watch?v=PredictiveParser',
            'topic_summary' => 'Handling grammar conflicts and ambiguity.',
        ]);

        $updateRes->assertRedirect();
        $this->assertDatabaseHas('course_lessons', [
            'id' => $lesson->id,
            'title' => 'LL(1) & Predictive Parsing Table Construction',
            'duration_minutes' => 60,
        ]);

        // 3. Delete lesson
        $delRes = $this->actingAs($this->teacher)->delete(route('courses.lessons.destroy', [$this->course->id, $lesson->id]));
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('course_lessons', ['id' => $lesson->id]);
    }

    public function test_curriculum_csv_template_download(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('courses.curriculum.template-csv', $this->course->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('module_title', $content);
        $this->assertStringContainsString('module_order', $content);
        $this->assertStringContainsString('lesson_title', $content);
        $this->assertStringContainsString('duration_minutes', $content);
        $this->assertStringContainsString('video_url', $content);
        $this->assertStringContainsString('pdf_url', $content);
    }

    public function test_bulk_curriculum_csv_import_feeds_modules_and_lessons(): void
    {
        $csvContent = implode("\n", [
            'module_title,module_order,module_description,lesson_number,lesson_title,duration_minutes,video_url,pdf_url,summary',
            '"Module A: Lexical Architecture",1,"Tokens and scan buffers",1,"Finite State Automata",45,"https://youtube.com/watch?v=automata","","State transition diagrams and deterministic machines"',
            '"Module A: Lexical Architecture",1,"Tokens and scan buffers",2,"Flex & Lex Generators",50,"https://youtube.com/watch?v=flex","","Generating C scanner files from regular expressions"',
            '"Module B: Syntax & Semantic Analysis",2,"Context-Free grammars and ASTs",3,"LR(0) and SLR(1) Parsing",60,"https://youtube.com/watch?v=lr0","","Shift-reduce conflict resolution"',
            '"Module B: Syntax & Semantic Analysis",2,"Context-Free grammars and ASTs",4,"Type Checking & Symbol Tables",55,"https://youtube.com/watch?v=types","","Static typing rules and scope resolution"',
        ]);

        $file = UploadedFile::fake()->createWithContent('curriculum_feed.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post(route('courses.curriculum.import-csv', $this->course->id), [
            'curriculum_csv' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify modules created from CSV
        $this->assertEquals(2, $this->course->modules()->count());
        $this->assertDatabaseHas('course_modules', [
            'course_id' => $this->course->id,
            'title' => 'Module A: Lexical Architecture',
            'order_index' => 1,
        ]);
        $this->assertDatabaseHas('course_modules', [
            'course_id' => $this->course->id,
            'title' => 'Module B: Syntax & Semantic Analysis',
            'order_index' => 2,
        ]);

        // Verify lessons created from CSV
        $this->assertEquals(4, $this->course->lessons()->count());
        $this->assertDatabaseHas('course_lessons', [
            'course_id' => $this->course->id,
            'lesson_number' => 1,
            'title' => 'Finite State Automata',
            'duration_minutes' => 45,
            'video_url' => 'https://youtube.com/watch?v=automata',
        ]);
        $this->assertDatabaseHas('course_lessons', [
            'course_id' => $this->course->id,
            'lesson_number' => 4,
            'title' => 'Type Checking & Symbol Tables',
            'duration_minutes' => 55,
        ]);
    }
}
