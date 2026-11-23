<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\BloomAssignment;
use App\Models\ClassEnrollment;
use App\Models\CurriculumTopic;
use App\Models\DailyLesson;
use App\Models\TopicBloomActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BloomsCsvImportAndTaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student1;

    protected User $student2;

    protected User $student3;

    protected AcademicClass $class;

    protected AcademicSubject $subject;

    protected CurriculumTopic $topic;

    protected DailyLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Ramanujan',
            'email' => 'ramanujan@primearc.academy',
        ]);

        $this->student1 = User::factory()->create([
            'role' => 'student',
            'name' => 'Aryabhata Sharma',
            'email' => 'aryabhata@primearc.academy',
            'student_id_number' => 'STU-00101',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'student',
            'name' => 'Bhaskara Patel',
            'email' => 'bhaskara@primearc.academy',
            'student_id_number' => 'STU-00102',
        ]);

        $this->student3 = User::factory()->create([
            'role' => 'student',
            'name' => 'Varahamihira Gupta',
            'email' => 'varaha@primearc.academy',
            'student_id_number' => 'STU-00103',
        ]);

        $this->class = AcademicClass::create([
            'name' => 'Class 9',
            'section' => 'A',
            'grade_level' => 9,
            'academic_year' => '2026-2027',
        ]);

        // Enroll students with roll numbers
        ClassEnrollment::create(['class_id' => $this->class->id, 'student_id' => $this->student1->id, 'roll_number' => 1, 'status' => 'active']);
        ClassEnrollment::create(['class_id' => $this->class->id, 'student_id' => $this->student2->id, 'roll_number' => 2, 'status' => 'active']);
        ClassEnrollment::create(['class_id' => $this->class->id, 'student_id' => $this->student3->id, 'roll_number' => 3, 'status' => 'active']);

        $this->subject = AcademicSubject::create([
            'class_id' => $this->class->id,
            'name' => 'Advanced Physics',
            'code' => 'PHY-901',
        ]);

        $this->topic = CurriculumTopic::create([
            'subject_id' => $this->subject->id,
            'title' => 'Quantum Mechanics & Wave Duality',
            'chapter_number' => 4,
            'order_index' => 1,
        ]);

        // Create standard bloom activities for topic
        for ($i = 1; $i <= 6; $i++) {
            $meta = TopicBloomActivity::getLevelMetadata($i);
            TopicBloomActivity::create([
                'topic_id' => $this->topic->id,
                'bloom_level' => $i,
                'level_name' => $meta['name'],
                'verb_keywords' => $meta['verbs'],
                'activity_prompt' => "Standard Level {$i} prompt for {$this->topic->title}.",
                'rubric_guide' => "Demonstrate clear comprehension of Level {$i}.",
            ]);
        }

        $this->lesson = DailyLesson::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'topic_id' => $this->topic->id,
            'teacher_id' => $this->teacher->id,
            'subtopic' => 'Photoelectric Effect',
            'lesson_date' => now()->toDateString(),
            'distribution_mode' => 'balanced_random',
        ]);
    }

    public function test_can_download_sample_tasks_csv(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('blooms.sample-tasks-csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('attachment; filename="blooms_tasks_import_template.csv"', $response->headers->get('Content-Disposition') ?? '');

        $content = $response->streamedContent();
        $this->assertStringContainsString('bloom_level', $content);
        $this->assertStringContainsString('task_prompt', $content);
        $this->assertStringContainsString('rubric_guide', $content);
    }

    public function test_can_import_tasks_csv_and_randomly_assign_to_class_students(): void
    {
        $csvContent = implode("\n", [
            'bloom_level,level_name,task_prompt,rubric_guide,due_date',
            '1,Remember,"State Einstein\'s photoelectric equation and define the work function.","Accurate formula and parameter definitions.",2026-11-10',
            '3,Apply,"Calculate the stopping potential for light of wavelength 400nm on zinc metal.","Show numerical calculation and units in Volts.",2026-11-12',
            '6,Create,"Design an optical sensor circuit using photoelectric tubes with safety relay.","Include circuit diagram and component specifications.",2026-11-15',
        ]);

        $file = UploadedFile::fake()->createWithContent('tasks.csv', $csvContent);

        $response = $this->actingAs($this->teacher)->post(route('blooms.lesson.import-tasks', $this->lesson->id), [
            'csv_file' => $file,
            'assignment_mode' => 'random',
            'clear_existing' => true,
        ]);

        $response->assertRedirect(route('blooms.lesson', $this->lesson->id));
        $response->assertSessionHas('success');

        // Verify assignments created in database
        $assignments = BloomAssignment::where('lesson_id', $this->lesson->id)->get();
        $this->assertCount(3, $assignments);

        // Verify each student has one assignment
        $assignedStudentIds = $assignments->pluck('student_id')->sort()->values()->toArray();
        $expectedStudentIds = collect([$this->student1->id, $this->student2->id, $this->student3->id])->sort()->values()->toArray();
        $this->assertEquals($expectedStudentIds, $assignedStudentIds);

        // Verify prompts were loaded from CSV
        $prompts = $assignments->pluck('activity_prompt')->toArray();
        $this->assertTrue(
            collect($prompts)->contains(fn ($p) => str_contains($p, 'photoelectric'))
        );
    }

    public function test_can_reshuffle_all_tasks_randomly(): void
    {
        // First create assignments
        $csvContent = implode("\n", [
            'bloom_level,task_prompt',
            '1,"Task Alpha for Level 1"',
            '3,"Task Beta for Level 3"',
            '6,"Task Gamma for Level 6"',
        ]);
        $file = UploadedFile::fake()->createWithContent('tasks.csv', $csvContent);
        $this->actingAs($this->teacher)->post(route('blooms.lesson.import-tasks', $this->lesson->id), [
            'csv_file' => $file,
            'assignment_mode' => 'random',
        ]);

        $this->assertEquals(3, BloomAssignment::where('lesson_id', $this->lesson->id)->count());

        // Reshuffle
        $response = $this->actingAs($this->teacher)->post(route('blooms.lesson.reshuffle', $this->lesson->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(3, BloomAssignment::where('lesson_id', $this->lesson->id)->count());
    }

    public function test_can_reassign_task_to_different_student(): void
    {
        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 4,
            'level_name' => 'Analyze',
            'activity_prompt' => 'Analyze wave-particle dualism anomalies.',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->teacher)->post(route('blooms.assignment.reassign', $assignment->id), [
            'student_id' => $this->student2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals($this->student2->id, $assignment->student_id);
        $this->assertEquals(2, $assignment->roll_number); // Roll number updated to Bhaskara's roll 2
    }

    public function test_can_reroll_individual_assignment_level(): void
    {
        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 1,
            'level_name' => 'Remember',
            'activity_prompt' => 'Define basic terminology.',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->teacher)->post(route('blooms.assignment.reroll', $assignment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertNotEquals(1, $assignment->bloom_level); // Rerolled to another level (2-6)
        $this->assertNotEmpty($assignment->activity_prompt);
    }

    public function test_can_edit_assignment_and_rubric(): void
    {
        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 2,
            'level_name' => 'Understand',
            'activity_prompt' => 'Original prompt description.',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->teacher)->post(route('blooms.assignment.update', $assignment->id), [
            'bloom_level' => 5,
            'activity_prompt' => 'Updated challenge: Critically evaluate Heisenberg uncertainty limits.',
            'rubric_guide' => 'Evaluate minimum momentum uncertainty with Planck constant.',
            'due_date' => '2026-11-20',
            'status' => 'reviewed',
            'score' => 95,
            'teacher_feedback' => 'Superb analysis of quantum boundaries!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals(5, $assignment->bloom_level);
        $this->assertEquals('Evaluate', $assignment->level_name);
        $this->assertEquals('Updated challenge: Critically evaluate Heisenberg uncertainty limits.', $assignment->activity_prompt);
        $this->assertEquals('Evaluate minimum momentum uncertainty with Planck constant.', $assignment->rubric_guide);
        $this->assertEquals('2026-11-20', $assignment->due_date?->format('Y-m-d'));
        $this->assertEquals(95, $assignment->score);
        $this->assertEquals('reviewed', $assignment->status);
        $this->assertEquals('Superb analysis of quantum boundaries!', $assignment->teacher_feedback);
    }

    public function test_teacher_can_request_revision_and_student_can_resubmit(): void
    {
        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 3,
            'level_name' => 'Apply',
            'activity_prompt' => 'Calculate Compton scattering shift.',
            'submission_content' => 'Shift is approx 0.024 angstrom.',
            'status' => 'submitted',
        ]);

        // Teacher requests revision
        $reqResponse = $this->actingAs($this->teacher)->post(route('blooms.assignment.request-revision', $assignment->id), [
            'feedback' => 'Please provide step-by-step formula derivation with scattering angle theta = 90 deg.',
        ]);

        $reqResponse->assertRedirect();
        $reqResponse->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('revision_requested', $assignment->status);
        $this->assertStringContainsString('step-by-step formula derivation', $assignment->teacher_feedback);

        // Student views student dashboard and resubmits
        $viewResponse = $this->actingAs($this->student1)->get(route('blooms.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Revision Requested by Teacher');

        $submitResponse = $this->actingAs($this->student1)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'Revised Solution: Using delta_lambda = (h/mc)*(1 - cos(theta)), for theta=90, delta_lambda = 2.43 pm.',
        ]);

        $submitResponse->assertRedirect();
        $submitResponse->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertStringContainsString('Using delta_lambda', $assignment->submission_content);
    }

    public function test_can_manually_add_custom_task(): void
    {
        $response = $this->actingAs($this->teacher)->post(route('blooms.lesson.assignments.store', $this->lesson->id), [
            'student_id' => $this->student3->id,
            'bloom_level' => 6,
            'activity_prompt' => 'Create a computer simulation of electron diffraction patterns.',
            'rubric_guide' => 'Check modular code structure and visualization clarity.',
            'due_date' => '2026-11-25',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bloom_assignments', [
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student3->id,
            'bloom_level' => 6,
            'level_name' => 'Create',
            'activity_prompt' => 'Create a computer simulation of electron diffraction patterns.',
            'due_date' => '2026-11-25 00:00:00',
        ]);
    }

    public function test_can_export_lesson_assignments_csv(): void
    {
        BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 3,
            'level_name' => 'Apply',
            'activity_prompt' => 'Sample export prompt.',
            'status' => 'reviewed',
            'score' => 92,
            'teacher_feedback' => 'Well done.',
        ]);

        $response = $this->actingAs($this->teacher)->get(route('blooms.lesson.export-csv', $this->lesson->id));

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Roll Number', $content);
        $this->assertStringContainsString('Student Name', $content);
        $this->assertStringContainsString('Aryabhata Sharma', $content);
        $this->assertStringContainsString('Sample export prompt', $content);
        $this->assertStringContainsString('92', $content);
    }

    public function test_can_delete_assignment(): void
    {
        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 2,
            'level_name' => 'Understand',
            'activity_prompt' => 'To be deleted prompt.',
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->teacher)->delete(route('blooms.assignment.destroy', $assignment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('bloom_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_student_can_submit_blooms_assignment_with_pdf_attachment(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 4,
            'level_name' => 'Analyze',
            'activity_prompt' => 'Analyze wave-particle duality and submit report.',
            'status' => 'assigned',
        ]);

        $file = UploadedFile::fake()->create('wave_particle_analysis.pdf', 1500, 'application/pdf');

        $response = $this->actingAs($this->student1)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'Here is my comprehensive research findings document.',
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertNotNull($assignment->submission_attachment_path);
        $this->assertEquals('wave_particle_analysis.pdf', $assignment->submission_attachment_name);
        $this->assertEquals('pdf', $assignment->submission_attachment_type);
        $this->assertTrue($assignment->hasSubmissionAttachment());
        $this->assertEquals('📄', $assignment->submission_attachment_icon);

        Storage::disk('private')->assertExists($assignment->submission_attachment_path);
    }

    public function test_student_can_submit_blooms_assignment_with_presentation_slides_deck(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 6,
            'level_name' => 'Create',
            'activity_prompt' => 'Create a presentation slide deck on quantum tunneling applications.',
            'status' => 'assigned',
        ]);

        $pptFile = UploadedFile::fake()->create(
            'quantum_tunneling_presentation.pptx',
            3200,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        );

        $response = $this->actingAs($this->student1)->post(route('blooms.assignment.submit', $assignment->id), [
            'attachment' => $pptFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertEquals('quantum_tunneling_presentation.pptx', $assignment->submission_attachment_name);
        $this->assertEquals('pptx', $assignment->submission_attachment_type);
        $this->assertEquals('📊', $assignment->submission_attachment_icon);

        Storage::disk('private')->assertExists($assignment->submission_attachment_path);
    }

    public function test_teacher_can_attach_reference_presentation_or_document_and_users_can_download(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $referenceDoc = UploadedFile::fake()->create('quantum_mechanics_rubric.docx', 1200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        // Teacher creates custom task with reference document attachment
        $response = $this->actingAs($this->teacher)->post(route('blooms.lesson.assignments.store', $this->lesson->id), [
            'student_id' => $this->student2->id,
            'bloom_level' => 5,
            'activity_prompt' => 'Evaluate experimental apparatus using the attached guideline document.',
            'due_date' => '2026-11-28',
            'teacher_attachment' => $referenceDoc,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assignment = BloomAssignment::where('student_id', $this->student2->id)->latest()->first();
        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->hasTeacherAttachment());
        $this->assertEquals('quantum_mechanics_rubric.docx', $assignment->teacher_attachment_name);
        $this->assertEquals('docx', $assignment->teacher_attachment_type);
        $this->assertEquals('📝', $assignment->teacher_attachment_icon);

        Storage::disk('private')->assertExists($assignment->teacher_attachment_path);

        // Student 2 downloads teacher reference document
        $downloadResponse = $this->actingAs($this->student2)->get(
            route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'teacher'])
        );
        $downloadResponse->assertStatus(200);

        // Student 2 submits their response with a PDF presentation/report
        $studentPdf = UploadedFile::fake()->create('apparatus_evaluation_report.pdf', 2500, 'application/pdf');
        $this->actingAs($this->student2)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'Completed evaluation according to the rubric.',
            'attachment' => $studentPdf,
        ]);

        $assignment->refresh();
        $this->assertTrue($assignment->hasSubmissionAttachment());

        // Teacher downloads student submission attachment
        $teacherDownloadResponse = $this->actingAs($this->teacher)->get(
            route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission'])
        );
        $teacherDownloadResponse->assertStatus(200);

        // Student 3 (unauthorized student) is blocked from downloading Student 2's submission
        $forbiddenResponse = $this->actingAs($this->student3)->get(
            route('blooms.assignment.download-attachment', ['id' => $assignment->id, 'type' => 'submission'])
        );
        $forbiddenResponse->assertStatus(403);
    }

    public function test_bloom_attachment_model_does_not_expose_public_storage_urls(): void
    {
        $assignment = new BloomAssignment([
            'submission_attachment_path' => 'blooms/submissions/private-file.pdf',
            'teacher_attachment_path' => 'blooms/reference_materials/private-guide.pdf',
        ]);

        $this->assertNull($assignment->submission_attachment_url);
        $this->assertNull($assignment->teacher_attachment_url);
    }

    public function test_resubmission_replaces_old_attachment(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $initialFile = UploadedFile::fake()->create('draft_slides.ppt', 1800, 'application/vnd.ms-powerpoint');

        $assignment = BloomAssignment::create([
            'lesson_id' => $this->lesson->id,
            'student_id' => $this->student1->id,
            'roll_number' => 1,
            'bloom_level' => 3,
            'level_name' => 'Apply',
            'activity_prompt' => 'Prepare practical slides on photon emission.',
            'status' => 'assigned',
        ]);

        $this->actingAs($this->student1)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'First draft presentation.',
            'attachment' => $initialFile,
        ]);

        $assignment->refresh();
        $firstPath = $assignment->submission_attachment_path;
        Storage::disk('private')->assertExists($firstPath);

        // Teacher requests revision
        $assignment->update(['status' => 'revision_requested', 'teacher_feedback' => 'Please add more slides on frequency calculation.']);

        // Student resubmits with revised PPTX slides
        $revisedFile = UploadedFile::fake()->create('final_slides_v2.pptx', 2400, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
        $this->actingAs($this->student1)->post(route('blooms.assignment.submit', $assignment->id), [
            'submission_content' => 'Revised presentation with complete calculations.',
            'attachment' => $revisedFile,
        ]);

        $assignment->refresh();
        $this->assertEquals('submitted', $assignment->status);
        $this->assertEquals('final_slides_v2.pptx', $assignment->submission_attachment_name);
        $this->assertEquals('pptx', $assignment->submission_attachment_type);
        Storage::disk('private')->assertExists($assignment->submission_attachment_path);
        // Previous draft should be cleaned up
        Storage::disk('private')->assertMissing($firstPath);
    }
}
