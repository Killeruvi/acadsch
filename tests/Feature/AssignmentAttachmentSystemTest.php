<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AssignmentAttachmentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure local uploads directory exists
        if (! file_exists(public_path('uploads/assignments'))) {
            mkdir(public_path('uploads/assignments'), 0755, true);
        }
    }

    private function createTestCourse(User $teacher): Course
    {
        return Course::create([
            'title' => 'Test English Course',
            'code' => 'ENG-TEST',
            'description' => 'Test course description',
            'instructor_id' => $teacher->id,
            'status' => 'published',
            'is_published' => true,
            'price' => 0,
        ]);
    }

    public function test_teacher_can_create_assignment_with_attachment_file(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createTestCourse($teacher);

        $fakePdf = UploadedFile::fake()->create('worksheet_unit1.pdf', 120, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('assignments.store'), [
            'course_id' => $course->id,
            'title' => 'Test Unit 1 Homework',
            'description' => 'Complete all exercises in the attached worksheet.',
            'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'max_points' => 100,
            'target_type' => 'all_enrolled',
            'attachment' => $fakePdf,
        ]);

        $assignment = Assignment::where('title', 'Test Unit 1 Homework')->first();
        $this->assertNotNull($assignment);
        $response->assertRedirect(route('assignments.show', $assignment->id));
        $this->assertTrue($assignment->hasAttachment());
        $this->assertEquals('worksheet_unit1.pdf', $assignment->attachment_name);
        $this->assertTrue($assignment->is_pdf_attachment);
        $this->assertNotEmpty($assignment->formatted_attachment_size);
        $this->assertFileExists(public_path(ltrim($assignment->attachment_path, '/')));

        // Clean up created file
        @unlink(public_path(ltrim($assignment->attachment_path, '/')));
        $assignment->delete();
    }

    public function test_attachment_download_endpoint_works(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = $this->createTestCourse($teacher);
        $course->students()->attach($student->id, ['status' => 'enrolled', 'enrolled_at' => now()]);

        // Create assignment with sample attached file in public/uploads/assignments
        $sampleName = 'sample_dpp_'.time().'.pdf';
        $fullPath = public_path('uploads/assignments/'.$sampleName);
        file_put_contents($fullPath, '%PDF-1.4 dummy pdf content');

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Sample DPP Assignment',
            'description' => 'Plain text description without code or html links.',
            'due_date' => now()->addDays(5),
            'max_points' => 50,
            'target_type' => 'all_enrolled',
            'attachment_path' => '/uploads/assignments/'.$sampleName,
            'attachment_name' => 'Original_DPP_Title.pdf',
            'attachment_type' => 'application/pdf',
            'attachment_size' => filesize($fullPath),
        ]);

        $response = $this->actingAs($student)->get(route('assignments.attachment', $assignment->id));
        $response->assertOk();
        $response->assertHeader('content-disposition');

        // Test display on assignments show page
        $showResponse = $this->actingAs($student)->get(route('assignments.show', $assignment->id));
        $showResponse->assertOk();
        $showResponse->assertSee('Attached Study Material &amp; Worksheet', false);
        $showResponse->assertSee('Original_DPP_Title.pdf');
        $showResponse->assertSee('Download Worksheet');

        // Clean up
        @unlink($fullPath);
        $assignment->delete();
    }

    public function test_teacher_can_replace_and_remove_attachment(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $course = $this->createTestCourse($teacher);

        $initialFile = 'old_worksheet_'.time().'.pdf';
        $fullPath = public_path('uploads/assignments/'.$initialFile);
        file_put_contents($fullPath, 'old content');

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Editable Assignment',
            'due_date' => now()->addDays(3),
            'max_points' => 100,
            'attachment_path' => '/uploads/assignments/'.$initialFile,
            'attachment_name' => 'Old.pdf',
            'attachment_type' => 'application/pdf',
            'attachment_size' => filesize($fullPath),
        ]);

        // Remove attachment
        $response = $this->actingAs($teacher)->put(route('assignments.update', $assignment->id), [
            'course_id' => $course->id,
            'title' => 'Editable Assignment Updated',
            'due_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'max_points' => 100,
            'remove_attachment' => 1,
        ]);

        $response->assertRedirect(route('assignments.show', $assignment->id));
        $assignment->refresh();
        $this->assertFalse($assignment->hasAttachment());
        $this->assertNull($assignment->attachment_path);
        $this->assertFileDoesNotExist($fullPath);

        $assignment->delete();
    }
}
