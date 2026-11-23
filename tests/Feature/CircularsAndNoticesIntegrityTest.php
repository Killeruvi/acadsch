<?php

namespace Tests\Feature;

use App\Models\AcademyNotice;
use App\Models\CircularReadReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CircularsAndNoticesIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public', ['lock' => 0]);

        $this->admin = User::factory()->create([
            'name' => 'Principal Administrator',
            'email' => 'principal@primearc.academy',
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->teacher = User::factory()->create([
            'name' => 'Physics Teacher',
            'email' => 'physics@primearc.academy',
            'role' => 'teacher',
            'is_approved' => true,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav@student.academy',
            'role' => 'student',
            'is_approved' => true,
        ]);

        $this->parent = User::factory()->create([
            'name' => 'Sunita Sharma',
            'email' => 'sunita@guardian.academy',
            'role' => 'parent',
            'is_approved' => true,
        ]);
    }

    public function test_admin_can_publish_circular_with_pdf_attachment_and_auto_numbering(): void
    {
        $file = UploadedFile::fake()->create('Term_Datesheet_Circular.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('notices.store'), [
            'type' => 'circular',
            'title' => 'Annual Examination Term 1 Schedule',
            'content' => 'Please find attached the official datesheet for the upcoming term examinations.',
            'priority' => 'high',
            'target_audience' => 'all',
            'requires_acknowledgement' => 1,
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('notices.index'));
        $response->assertSessionHas('success');

        $year = now()->format('Y');
        $this->assertDatabaseHas('academy_notices', [
            'type' => 'circular',
            'title' => 'Annual Examination Term 1 Schedule',
            'priority' => 'high',
            'status' => 'published',
            'circular_number' => "CIR/{$year}/001",
            'requires_acknowledgement' => true,
        ]);

        $notice = AcademyNotice::where('title', 'Annual Examination Term 1 Schedule')->first();
        $this->assertNotNull($notice);
        $this->assertNotNull($notice->attachment_url);
        Storage::disk('public')->assertExists($notice->attachment_url);
    }

    public function test_teacher_circular_enters_pending_approval_and_admin_approves(): void
    {
        // Teacher creates a notice directly for students
        $resNotice = $this->actingAs($this->teacher)->post(route('notices.store'), [
            'type' => 'notice',
            'title' => 'Physics Practical Exam Instructions',
            'content' => 'Bring lab manuals and certified experiment records.',
            'priority' => 'normal',
            'target_audience' => 'students',
        ]);
        $resNotice->assertRedirect(route('notices.index'));

        $notice = AcademyNotice::where('title', 'Physics Practical Exam Instructions')->first();
        $this->assertNotNull($notice);
        $this->assertEquals('pending_approval', $notice->status);

        // Admin approves notice/circular
        $resApprove = $this->actingAs($this->admin)->post(route('notices.approve', $notice->id));
        $resApprove->assertRedirect();

        $notice->refresh();
        $this->assertEquals('published', $notice->status);
        $this->assertEquals($this->admin->id, $notice->approved_by);
        $this->assertNotNull($notice->approved_at);
        $this->assertNotNull($notice->published_at);
    }

    public function test_target_audience_data_isolation(): void
    {
        // Faculty-only circular
        $facultyNotice = AcademyNotice::create([
            'type' => 'circular',
            'author_id' => $this->admin->id,
            'title' => 'Confidential Staff Meeting Notes',
            'content' => 'Curriculum review on Friday 4 PM.',
            'priority' => 'high',
            'target_audience' => 'teachers',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // General public circular
        $publicNotice = AcademyNotice::create([
            'type' => 'circular',
            'author_id' => $this->admin->id,
            'title' => 'Winter Vacation Schedule',
            'content' => 'School reopens on January 5th.',
            'priority' => 'normal',
            'target_audience' => 'all',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Student views notice board circulars tab
        $studentRes = $this->actingAs($this->student)->get(route('notices.index', ['tab' => 'circulars']));
        $studentRes->assertStatus(200);
        $studentRes->assertSee('Winter Vacation Schedule');
        $studentRes->assertDontSee('Confidential Staff Meeting Notes');

        // Teacher views notice board circulars tab
        $teacherRes = $this->actingAs($this->teacher)->get(route('notices.index', ['tab' => 'circulars']));
        $teacherRes->assertStatus(200);
        $teacherRes->assertSee('Winter Vacation Schedule');
        $teacherRes->assertSee('Confidential Staff Meeting Notes');
    }

    public function test_read_receipt_and_acknowledgement(): void
    {
        $notice = AcademyNotice::create([
            'type' => 'circular',
            'author_id' => $this->admin->id,
            'title' => 'Mandatory Health Checkup Notice',
            'content' => 'Annual pediatric checkup will be conducted next week.',
            'priority' => 'normal',
            'target_audience' => 'all',
            'status' => 'published',
            'requires_acknowledgement' => true,
            'published_at' => now(),
        ]);

        // Student marks read and acknowledges
        $res = $this->actingAs($this->student)->post(route('notices.read', $notice->id), [
            'acknowledged' => 1,
        ]);
        $res->assertRedirect();

        $receipt = CircularReadReceipt::where('notice_id', $notice->id)
            ->where('user_id', $this->student->id)
            ->first();
        $this->assertNotNull($receipt);
        $this->assertTrue((bool) $receipt->acknowledged);
        $this->assertNotNull($receipt->acknowledged_at);

        // Check read stats endpoint
        $statsRes = $this->actingAs($this->admin)->get(route('notices.read-stats', $notice->id));
        $statsRes->assertStatus(200);
        $statsRes->assertJson([
            'read_count' => 1,
            'ack_count' => 1,
        ]);
    }

    public function test_updating_notice_attachment_replaces_old_file(): void
    {
        $file1 = UploadedFile::fake()->create('old_circular.pdf', 300, 'application/pdf');

        $this->actingAs($this->admin)->post(route('notices.store'), [
            'type' => 'circular',
            'title' => 'Transport Route Revision',
            'content' => 'Revised route timings attached.',
            'priority' => 'normal',
            'target_audience' => 'all',
            'attachment' => $file1,
        ]);

        $notice = AcademyNotice::where('title', 'Transport Route Revision')->first();
        $oldPath = $notice->attachment_url;
        $this->assertNotNull($oldPath);
        Storage::disk('public')->assertExists($oldPath);

        // Update with new attachment
        $file2 = UploadedFile::fake()->create('new_revised_circular.pdf', 400, 'application/pdf');
        $updateRes = $this->actingAs($this->admin)->put(route('notices.update', $notice->id), [
            'title' => 'Transport Route Revision V2',
            'content' => 'Corrected route timings attached.',
            'priority' => 'high',
            'target_audience' => 'all',
            'attachment' => $file2,
        ]);
        $updateRes->assertRedirect(route('notices.index'));

        $notice->refresh();
        $this->assertEquals('Transport Route Revision V2', $notice->title);
        $this->assertNotEquals($oldPath, $notice->attachment_url);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($notice->attachment_url);
    }
}
