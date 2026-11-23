<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\FeeInvoice;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Database\Seeders\AcademyInstitutionalSeeder;
use Database\Seeders\BloomsTaxonomySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SyllabusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealAcademyAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teacher;

    protected User $student;

    protected User $student2;

    protected AcademicClass $class8;

    protected AcademicSubject $scienceSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seed(BloomsTaxonomySeeder::class);
        $this->seed(SyllabusSeeder::class);
        $this->seed(QuestionBankSeeder::class);
        $this->seed(AcademyInstitutionalSeeder::class);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@primearc.academy'],
            [
                'name' => 'Dr. Rajeshwar Sharma',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'student_id_number' => 'ADM-001',
                'streak_count' => 5,
                'xp_points' => 1500,
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
            ]
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student@primearc.academy'],
            [
                'name' => 'Aryabhata Sharma',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'STU-001',
                'roll_number' => 1,
                'streak_count' => 5,
                'xp_points' => 250,
            ]
        );

        $this->student2 = User::firstOrCreate(
            ['email' => 'student2@primearc.academy'],
            [
                'name' => 'Ramanujan Rao',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'STU-002',
                'roll_number' => 2,
                'streak_count' => 2,
                'xp_points' => 150,
            ]
        );

        $this->class8 = AcademicClass::firstOrCreate(
            ['grade_level' => 8],
            ['name' => 'Class 8', 'section' => 'A', 'room_number' => 'R-801']
        );

        $this->scienceSubject = AcademicSubject::firstOrCreate(
            ['code' => 'SCI-8'],
            ['name' => 'Science', 'class_id' => $this->class8->id]
        );

        $this->class8->update(['class_teacher_id' => $this->teacher->id]);
        $this->scienceSubject->update(['teacher_id' => $this->teacher->id]);
    }

    /**
     * Test 1: User can view official notices and filter by priority.
     */
    public function test_user_can_view_official_notices_and_filter_by_priority(): void
    {
        $response = $this->actingAs($this->student)->get(route('notices.index'));
        $response->assertStatus(200);
        $response->assertSee('Official Circulars', false);

        // Filter by urgent
        $urgentResponse = $this->actingAs($this->student)->get(route('notices.index', ['priority' => 'urgent']));
        $urgentResponse->assertStatus(200);
    }

    /**
     * Test 2: Teacher can publish urgent official circular.
     */
    public function test_teacher_can_publish_official_circular_and_broadcast(): void
    {
        $payload = [
            'title' => 'CBSE Board Examination Registration Circular 2026',
            'content' => 'All eligible Class 10 and 12 scholars must submit passport photos and fees by Friday.',
            'priority' => 'urgent',
            'target_audience' => 'students',
            'is_pinned' => 1,
        ];

        $response = $this->actingAs($this->teacher)->post(route('notices.store'), $payload);
        $response->assertRedirect(route('notices.index'));

        $this->assertDatabaseHas('academy_notices', [
            'title' => 'CBSE Board Examination Registration Circular 2026',
            'priority' => 'urgent',
            'target_audience' => 'students',
            'is_pinned' => true,
        ]);
    }

    /**
     * Test 3: Student can submit leave request.
     */
    public function test_student_can_submit_leave_application(): void
    {
        $payload = [
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'type' => 'medical',
            'reason' => 'Severe viral fever and physician has advised bed rest for 3 days.',
        ];

        $response = $this->actingAs($this->student)->post(route('leaves.store'), $payload);
        $response->assertRedirect(route('leaves.index', ['tab' => 'list']));

        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'type' => 'medical',
            'status' => 'pending',
        ]);
    }

    /**
     * Test 4: Teacher can review and approve student leave request with remarks.
     */
    public function test_teacher_can_review_and_approve_student_leave(): void
    {
        $leave = StudentLeaveRequest::create([
            'student_id' => $this->student->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'type' => 'academic',
            'reason' => 'National Science Olympiad Level 2 examination.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)->patch(route('leaves.review', $leave->id), [
            'status' => 'approved',
            'teacher_remarks' => 'Granted duty leave for National Olympiad. Bring hall ticket on return.',
        ]);

        $response->assertRedirect(route('leaves.index'));

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($this->teacher->id, $leave->reviewed_by);
        $this->assertNotNull($leave->teacher_remarks);
    }

    /**
     * Test 5: Admin can update institutional settings.
     */
    public function test_admin_can_update_institutional_settings(): void
    {
        $payload = [
            'institute_name' => 'Prime Arc Academy of Sciences',
            'campus_name' => 'South Delhi Premier Campus',
            'affiliation_number' => 'CBSE/AFF/2026/099',
            'academic_session' => '2026–2027',
            'principal_name' => 'Dr. Vikramaditya Sharma',
            'institution_phone' => '+91 11 4000 8900',
            'institution_email' => 'principal@primearc.edu.in',
            'institution_address' => 'Knowledge Park II, New Delhi - 110025, Bharat',
            'upi_vpa' => 'primearc.treasury@upi',
            'currency_symbol' => '₹',
            'motto' => 'विद्या ददाति विनयं',
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.update'), $payload);
        $response->assertRedirect(route('settings.index'));

        $this->assertEquals('Prime Arc Academy of Sciences', AcademySetting::get('institute_name'));
        $this->assertEquals('primearc.treasury@upi', AcademySetting::get('upi_vpa'));
    }

    /**
     * Test 6: Teacher can update chapter syllabus progress and study notes.
     */
    public function test_teacher_can_update_syllabus_topic_progress(): void
    {
        $topic = CurriculumTopic::firstOrCreate(
            ['title' => 'Chemical Reactions and Equations', 'subject_id' => $this->scienceSubject->id],
            [
                'chapter_number' => 1,
                'description' => 'Chemical changes and balanced equations',
                'subtopics' => ['Types of Reactions', 'Oxidation & Reduction'],
                'status' => 'not_started',
            ]
        );

        $response = $this->actingAs($this->teacher)->patch(route('syllabus.topics.progress', $topic->id), [
            'status' => 'completed',
            'study_notes' => 'Completed all NCERT exercises and chemical balancing laboratory demonstration.',
            'resource_url' => 'https://notes.primearc.academy/ch1-chemistry.pdf',
        ]);

        $response->assertRedirect();

        $topic->refresh();
        $this->assertEquals('completed', $topic->status);
        $this->assertNotNull($topic->completed_at);
        $this->assertEquals('https://notes.primearc.academy/ch1-chemistry.pdf', $topic->resource_url);
    }

    /**
     * Test 7: DPP evaluation correctly applies negative marking and calculates class rank and percentile.
     */
    public function test_dpp_submission_evaluates_negative_marks_class_rank_and_percentile(): void
    {
        $dpp = DailyDpp::create([
            'class_id' => $this->class8->id,
            'subject_id' => $this->scienceSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Advanced Physics Olympiad Practice',
            'dpp_number' => 'DPP-TEST-RANK-01',
            'type' => 'test',
            'difficulty' => 'olympiad',
            'duration_minutes' => 30,
            'total_questions' => 2,
            'total_marks' => 8,
            'negative_marks' => 1.0, // -1 mark per wrong question
            'pass_percentage' => 50,
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'published',
        ]);

        DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 1,
            'question_text' => 'What is the SI unit of electric force?',
            'option_a' => 'Newton',
            'option_b' => 'Joule',
            'option_c' => 'Watt',
            'option_d' => 'Volt',
            'correct_option' => 'A',
            'explanation' => 'Force is measured in Newtons.',
            'marks' => 4,
        ]);

        DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 2,
            'question_text' => 'What is the velocity of light in vacuum?',
            'option_a' => '3x10^8 m/s',
            'option_b' => '3x10^6 m/s',
            'option_c' => '3x10^5 km/s',
            'option_d' => 'None',
            'correct_option' => 'A',
            'explanation' => 'Speed of light is 3x10^8 m/s.',
            'marks' => 4,
        ]);

        // Student 1 submits: Q1 correct (+4), Q2 wrong (-1) -> Score = 3 / 8
        $this->actingAs($this->student)->post(route('dpp.submit', $dpp->id), [
            'answers' => ['1' => 'A', '2' => 'B'],
            'time_taken_seconds' => 320,
        ]);

        $sub1 = DppSubmission::where('dpp_id', $dpp->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($sub1);
        $this->assertEquals(3.0, $sub1->score);
        $this->assertEquals(1, $sub1->correct_count);
        $this->assertEquals(1, $sub1->incorrect_count);
        $this->assertEquals(1.0, $sub1->negative_deduction);

        // Student 2 submits: Q1 correct (+4), Q2 correct (+4) -> Score = 8 / 8
        $this->actingAs($this->student2)->post(route('dpp.submit', $dpp->id), [
            'answers' => ['1' => 'A', '2' => 'A'],
            'time_taken_seconds' => 180,
        ]);

        $sub2 = DppSubmission::where('dpp_id', $dpp->id)->where('student_id', $this->student2->id)->first();
        $this->assertNotNull($sub2);
        $this->assertEquals(8.0, $sub2->score);
        $this->assertEquals(2, $sub2->correct_count);
        $this->assertEquals(0, $sub2->incorrect_count);
        $this->assertEquals(1, $sub2->rank); // Student 2 ranked #1
        $this->assertEquals(100.0, $sub2->percentile);

        // Student 1 rank should update to #2
        $sub1->refresh();
        $this->assertEquals(2, $sub1->rank);
    }

    /**
     * Test 8: Attendance view identifies defaulters when student attendance is below 75%.
     */
    public function test_attendance_identifies_defaulters_below_75_percent(): void
    {
        $testStudent = $this->class8->students()->first();
        if (! $testStudent) {
            $maxRoll = $this->class8->enrollments()->max('roll_number') ?? 0;
            $this->class8->enrollments()->create([
                'student_id' => $this->student->id,
                'roll_number' => $maxRoll + 1,
                'status' => 'active',
            ]);
            $testStudent = $this->student;
        }

        // Create 4 sessions for class 8
        for ($i = 1; $i <= 4; $i++) {
            $session = AttendanceSession::create([
                'class_id' => $this->class8->id,
                'teacher_id' => $this->teacher->id,
                'title' => "Session {$i}",
                'session_date' => now()->subDays(5 - $i)->toDateString(),
            ]);

            // Mark absent 3 times, present 1 time -> 25% attendance (defaulter < 75%)
            $status = ($i === 1) ? 'present' : 'absent';
            AttendanceRecord::create([
                'session_id' => $session->id,
                'student_id' => $testStudent->id,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($this->teacher)->get(route('attendance.index', ['class_id' => $this->class8->id]));
        $response->assertStatus(200);
        $response->assertSee('Attendance Defaulters Alert', false);
        $response->assertSee($testStudent->name);
    }

    /**
     * Test 9: Fee invoice view shows UPI payload and institutional breakdown.
     */
    public function test_fee_invoice_shows_upi_qr_payload_and_institutional_heads(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-TEST-009',
            'student_id' => $this->student->id,
            'title' => 'Term 1 Tuition & Laboratory Fee',
            'category' => 'Tuition',
            'amount' => 12000.00,
            'discount' => 1000.00,
            'tax' => 0.00,
            'total_due' => 11000.00,
            'paid_amount' => 0.00,
            'status' => 'pending',
            'due_date' => now()->addDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($this->student)->get(route('fees.show', $invoice->id));
        $response->assertStatus(200);
        $response->assertSee('Instant Bharat UPI', false);
        $response->assertSee('Itemized Institutional Fee Heads', false);
        $response->assertSee('Open in UPI App', false);
    }
}
