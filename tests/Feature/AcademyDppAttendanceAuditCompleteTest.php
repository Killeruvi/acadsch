<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\DailyDpp;
use App\Models\DppQuestion;
use App\Models\DppSubmission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyDppAttendanceAuditCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected AcademicClass $class10;

    protected AcademicClass $class9;

    protected AcademicSubject $physics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->teacher = User::firstOrCreate(
            ['email' => 'audit_teacher@primearc.academy'],
            [
                'name' => 'Prof. Audit Teacher',
                'password' => bcrypt('password123'),
                'role' => 'teacher',
                'faculty_code' => 'AUDIT-FAC-01',
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]
        );

        $this->student = User::firstOrCreate(
            ['email' => 'audit_student@primearc.academy'],
            [
                'name' => 'Audit Student',
                'password' => bcrypt('password123'),
                'role' => 'student',
                'student_id_number' => 'AUDIT-STU-01',
            ]
        );

        $this->class10 = AcademicClass::where('grade_level', 10)->first() ?? AcademicClass::create([
            'grade_level' => 10,
            'name' => 'Class 10',
        ]);

        $this->class9 = AcademicClass::where('grade_level', 9)->first() ?? AcademicClass::create([
            'grade_level' => 9,
            'name' => 'Class 9',
        ]);

        $this->physics = AcademicSubject::where('class_id', $this->class10->id)->first() ?? AcademicSubject::create([
            'class_id' => $this->class10->id,
            'name' => 'Physics',
            'code' => 'PHY10',
        ]);
    }

    /**
     * Test 1: Visibility of all published DPPs across academy without being restricted to 3.
     */
    public function test_all_published_dpps_are_visible_and_paginated(): void
    {
        // Create 5 published DPPs for Class 10
        for ($i = 1; $i <= 5; $i++) {
            DailyDpp::create([
                'class_id' => $this->class10->id,
                'subject_id' => $this->physics->id,
                'teacher_id' => $this->teacher->id,
                'dpp_number' => "AUDIT-DPP-10-0{$i}",
                'type' => 'dpp',
                'title' => "Class 10 DPP #{$i}",
                'assigned_date' => now()->toDateString(),
                'difficulty' => 'standard',
                'duration_minutes' => 15,
                'total_marks' => 20,
            ]);
        }

        // Test with class_id=all
        $response = $this->actingAs($this->student)->get(route('dpp.index', ['class_id' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('Class 10 DPP #1');
        $response->assertSee('Class 10 DPP #5');

        // Test with explicit class_id
        $responseClass = $this->actingAs($this->student)->get(route('dpp.index', ['class_id' => $this->class10->id]));
        $responseClass->assertStatus(200);
        $responseClass->assertSee('Class 10 DPP #1');
        $responseClass->assertSee('Class 10 DPP #5');
    }

    /**
     * Test 2: Teacher can edit published DPP using PUT or PATCH without 405 and preserving student submissions.
     */
    public function test_teacher_can_edit_published_dpp_and_preserve_student_submissions(): void
    {
        $dpp = DailyDpp::create([
            'class_id' => $this->class10->id,
            'subject_id' => $this->physics->id,
            'teacher_id' => $this->teacher->id,
            'dpp_number' => 'AUDIT-DPP-EDIT-01',
            'type' => 'dpp',
            'title' => 'Initial Title Before Teacher Edit',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'standard',
            'duration_minutes' => 20,
            'total_marks' => 8,
        ]);

        $q1 = DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 1,
            'question_text' => 'Initial Question 1 Text',
            'option_a' => 'A1',
            'option_b' => 'B1',
            'option_c' => 'C1',
            'option_d' => 'D1',
            'correct_option' => 'A',
            'explanation' => 'Initial explanation 1',
            'marks' => 4,
        ]);

        $q2 = DppQuestion::create([
            'dpp_id' => $dpp->id,
            'question_number' => 2,
            'question_text' => 'Initial Question 2 Text',
            'option_a' => 'A2',
            'option_b' => 'B2',
            'option_c' => 'C2',
            'option_d' => 'D2',
            'correct_option' => 'B',
            'explanation' => 'Initial explanation 2',
            'marks' => 4,
        ]);

        // Student submits attempt
        $submission = DppSubmission::create([
            'dpp_id' => $dpp->id,
            'student_id' => $this->student->id,
            'answers' => ['1' => 'A', '2' => 'B'],
            'score' => 8,
            'max_score' => 8,
            'percentage' => 100,
            'is_passed' => true,
            'status' => 'evaluated',
            'checked_at' => now(),
            'xp_earned' => 30,
        ]);

        // Teacher accesses edit page
        $editPageResponse = $this->actingAs($this->teacher)->get(route('dpp.edit', $dpp->id));
        $editPageResponse->assertStatus(200);
        $editPageResponse->assertSee('Edit Published DPP', false);

        // Teacher submits update via PUT
        $updateResponse = $this->actingAs($this->teacher)->put(route('dpp.update', $dpp->id), [
            'title' => 'Updated Title By Teacher',
            'type' => 'dpp',
            'assigned_date' => now()->toDateString(),
            'difficulty' => 'advanced',
            'duration_minutes' => 30,
            'pass_percentage' => 50,
            'negative_marks' => 0.5,
            'questions' => [
                [
                    'id' => $q1->id,
                    'text' => 'Revised Question 1 Prompt',
                    'option_a' => 'Updated A1',
                    'option_b' => 'Updated B1',
                    'option_c' => 'Updated C1',
                    'option_d' => 'Updated D1',
                    'correct' => 'A',
                    'explanation' => 'Revised solution explanation',
                    'marks' => 5,
                ],
                [
                    'id' => $q2->id,
                    'text' => 'Revised Question 2 Prompt',
                    'option_a' => 'A2',
                    'option_b' => 'B2',
                    'option_c' => 'C2',
                    'option_d' => 'D2',
                    'correct' => 'B',
                    'explanation' => 'Revised solution 2',
                    'marks' => 5,
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('dpp.show', $dpp->id));

        // Verify DPP updated
        $dpp->refresh();
        $this->assertEquals('Updated Title By Teacher', $dpp->title);
        $this->assertEquals(10, $dpp->total_marks);

        // Verify student submission is preserved intact
        $this->assertDatabaseHas('dpp_submissions', [
            'id' => $submission->id,
            'dpp_id' => $dpp->id,
            'student_id' => $this->student->id,
            'score' => 8,
        ]);
    }

    /**
     * Test 3: Teacher can edit and update Attendance records with history preserved.
     */
    public function test_teacher_can_edit_and_update_attendance_records(): void
    {
        $attendance = AttendanceSession::create([
            'class_id' => $this->class10->id,
            'session_date' => now()->toDateString(),
            'teacher_id' => $this->teacher->id,
            'title' => 'Audit Class Attendance',
        ]);

        $record = AttendanceRecord::create([
            'session_id' => $attendance->id,
            'student_id' => $this->student->id,
            'status' => 'absent',
            'remarks' => 'Initial unexcused absent',
        ]);

        // Teacher accesses attendance edit page
        $editResponse = $this->actingAs($this->teacher)->get(route('attendance.edit', $attendance->id));
        $editResponse->assertStatus(200);

        // Teacher updates attendance via PATCH
        $patchResponse = $this->actingAs($this->teacher)->patch(route('attendance.update', $attendance->id), [
            'session_date' => now()->toDateString(),
            'title' => 'Audit Class Attendance',
            'notes' => 'Updated notes by teacher',
            'attendance' => [
                $this->student->id => 'present',
            ],
        ]);

        $patchResponse->assertRedirect(route('attendance.show', $attendance->id));

        // Verify updated record
        $record->refresh();
        $this->assertEquals('present', $record->status);
    }
}
