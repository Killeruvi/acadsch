<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\FeeInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@primeos.edu',
            'name' => 'Admin Principal',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'email' => 'teacher@primeos.edu',
            'name' => 'Prof. Oak',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@primeos.edu',
            'name' => 'Ash Ketchum',
        ]);
    }

    /**
     * Test that Admin dashboard renders successfully with all 10 priority sections.
     */
    public function test_admin_dashboard_renders_all_ten_priority_sections(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);

        // Section 1: Today's Snapshot
        $response->assertSee('Snapshot');
        $response->assertSee('Attendance Today');
        $response->assertSee('Faculty Availability');
        $response->assertSee('Leave Requests');

        // Section 2: Admissions Pipeline
        $response->assertSee('Admissions Pipeline');
        $response->assertSee('Applications Received');
        $response->assertSee('Under Review');
        $response->assertSee('Conversion');

        // Section 3: Fees & Finance
        $response->assertSee('Fees');
        $response->assertSee('Collected Today');
        $response->assertSee('Outstanding Dues');
        $response->assertSee('Defaulters');

        // Section 4: Academic Operations
        $response->assertSee('Academic Operations');
        $response->assertSee('Class Attendance Taken');
        $response->assertSee('Examinations Readiness');
        $response->assertSee('Curriculum Progress');

        // Section 5: Student & Staff Overview
        $response->assertSee('Student');
        $response->assertSee('Staff Overview');
        $response->assertSee('Total Enrolled Scholars');
        $response->assertSee('Faculty');

        // Section 6: Leave & Attendance Trends
        $response->assertSee('Leave');
        $response->assertSee('Attendance Trends');
        $response->assertSee('Pending Leaves');
        $response->assertSee('Approved This Week');
        $response->assertSee('Weekly Attendance');
        $response->assertSee('Chronic Absentees');

        // Section 7: Circulars & Communication
        $response->assertSee('Circulars');
        $response->assertSee('Communication');
        $response->assertSee('Published');
        $response->assertSee('Drafts');
        $response->assertSee('Parent Engagement');

        // Section 8: Alerts & Anomalies
        $response->assertSee('Alerts');
        $response->assertSee('Anomalies');
        $response->assertSee('Rejected Attempts');
        $response->assertSee('Data Inconsistencies');

        // Section 9: Quick Actions
        $response->assertSee('Quick Actions');
        $response->assertSee('Approve Leave');
        $response->assertSee('Take Roll Call');
        $response->assertSee('Publish Notice');
        $response->assertSee('Admit Scholar');
        $response->assertSee('Add Class');
        $response->assertSee('Generate Fee');

        // Section 10: Recent Activity Feed
        $response->assertSee('Recent Activity Feed');
    }

    /**
     * Test that student sees student dashboard and not the administrative analytics.
     */
    public function test_student_does_not_see_admin_priority_sections(): void
    {
        $response = $this->actingAs($this->student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Today&#039;s Snapshot', false);
        $response->assertDontSee('Admissions Pipeline');
        $response->assertDontSee('Academic Operations');
        $response->assertDontSee('Alerts &amp; Anomalies', false);
    }

    /**
     * Test that abnormal attendance is properly highlighted in Today's Snapshot.
     */
    public function test_abnormal_attendance_is_highlighted_in_todays_snapshot(): void
    {
        $class = AcademicClass::create([
            'name' => 'Grade 10 - Alpha',
            'grade_level' => 10,
            'section' => 'A',
            'stream' => 'Science',
            'academic_year' => '2026-2027',
            'capacity' => 30,
        ]);

        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'teacher_id' => $this->teacher->id,
            'session_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        // 1 present, 4 absent = 20% attendance (< 75% threshold)
        AttendanceRecord::create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        for ($i = 0; $i < 4; $i++) {
            $otherStudent = User::factory()->create(['role' => 'student']);
            AttendanceRecord::create([
                'session_id' => $session->id,
                'student_id' => $otherStudent->id,
                'status' => 'absent',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Abnormal Attendance');
        $response->assertSee('Grade 10 - Alpha');
        $response->assertSee('1 Classes');
    }

    /**
     * Test fee defaulters and outstanding metric calculations.
     */
    public function test_fee_metrics_reflect_in_dashboard(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-001',
            'student_id' => $this->student->id,
            'title' => 'Term 1 Tuition Fee',
            'amount' => 5000.00,
            'total_due' => 5000.00,
            'paid_amount' => 1000.00,
            'status' => 'partially_paid',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Outstanding Dues');
        $response->assertSee('4,000'); // 5000 - 1000 = 4000
    }
}
