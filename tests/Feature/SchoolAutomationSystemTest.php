<?php

namespace Tests\Feature;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\InAppNotification;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamTerm;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchoolAutomationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_auto_alert_notifies_parent_and_excuses_approved_leave(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent1@school.test']);
        $student1 = User::factory()->create([
            'role' => 'student',
            'parent_id' => $parent->id,
            'guardian_email' => $parent->email,
        ]);
        $student2 = User::factory()->create([
            'role' => 'student',
            'parent_id' => $parent->id,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = AcademicClass::create([
            'name' => 'Grade 10',
            'grade_level' => 10,
            'section' => 'A',
            'stream' => 'Science',
            'academic_session' => '2025-2026',
            'class_teacher_id' => $teacher->id,
        ]);

        $student1->update(['current_class_id' => $class->id]);
        $student2->update(['current_class_id' => $class->id]);

        $today = now()->toDateString();
        $session = AttendanceSession::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'session_date' => $today,
            'title' => 'Daily Roll Call',
        ]);

        // Student 1 is absent without leave
        $rec1 = AttendanceRecord::create([
            'session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'absent',
        ]);

        // Student 2 has approved leave today
        StudentLeaveRequest::create([
            'student_id' => $student2->id,
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Doctor appointment',
            'status' => 'approved',
            'type' => 'medical',
        ]);

        $rec2 = AttendanceRecord::create([
            'session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);

        // Run attendance automation command
        $exitCode = Artisan::call('attendance:auto-alert', ['--date' => $today]);
        $this->assertEquals(0, $exitCode);

        // Student 1 parent should receive absence notification
        $parentNotif = InAppNotification::where('user_id', $parent->id)
            ->where('title', 'like', '%Absence Alert%')
            ->first();
        $this->assertNotNull($parentNotif);
        $this->assertStringContainsString($student1->name, $parentNotif->message);

        // Student 2 record should be automatically transitioned to excused
        $rec2->refresh();
        $this->assertEquals('excused', $rec2->status);
        $this->assertStringContainsString('Auto-excused', $rec2->remarks);
    }

    public function test_apply_overdue_fee_fines_transitions_status_and_applies_fine(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $student = User::factory()->create(['role' => 'student', 'parent_id' => $parent->id]);

        $pastDueDate = now()->subDays(5)->toDateString();

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-2025-9999',
            'student_id' => $student->id,
            'title' => 'Tuition Fee',
            'category' => 'Tuition',
            'amount' => 1500.00,
            'total_due' => 1500.00,
            'paid_amount' => 0.00,
            'status' => 'pending',
            'due_date' => $pastDueDate,
        ]);

        $exitCode = Artisan::call('fees:apply-overdue-fines', ['--fine' => 75.00]);
        $this->assertEquals(0, $exitCode);

        $invoice->refresh();
        $this->assertEquals('overdue', $invoice->status);
        $this->assertEquals(75.00, (float) $invoice->late_fine_amount);
        $this->assertEquals(1575.00, (float) $invoice->total_due);

        // Student and parent should have overdue notification
        $this->assertTrue(
            InAppNotification::where('user_id', $student->id)
                ->where('title', 'like', '%Overdue Fee%')
                ->exists()
        );
        $this->assertTrue(
            InAppNotification::where('user_id', $parent->id)
                ->where('title', 'like', '%Overdue Fee%')
                ->exists()
        );
    }

    public function test_compile_report_cards_calculates_grades_and_ranks_in_bulk(): void
    {
        $term = SchoolExamTerm::create([
            'name' => 'Mid Term 2025',
            'academic_session' => '2025-2026',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $class = AcademicClass::create([
            'name' => 'Grade 10',
            'grade_level' => 10,
            'section' => 'A',
            'stream' => 'General',
            'academic_session' => '2025-2026',
        ]);

        $subject = AcademicSubject::create([
            'name' => 'Mathematics',
            'code' => 'MATH101',
            'class_id' => $class->id,
        ]);

        $student1 = User::factory()->create(['role' => 'student', 'current_class_id' => $class->id]);
        $student2 = User::factory()->create(['role' => 'student', 'current_class_id' => $class->id]);

        $class->students()->attach([
            $student1->id => ['roll_number' => '101'],
            $student2->id => ['roll_number' => '102'],
        ]);

        // Student 1 scored 95/100, Student 2 scored 65/100
        SchoolExamMark::create([
            'exam_term_id' => $term->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'student_id' => $student1->id,
            'marks_obtained' => 95.00,
            'max_marks' => 100.00,
            'pass_marks' => 35.00,
        ]);

        SchoolExamMark::create([
            'exam_term_id' => $term->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'student_id' => $student2->id,
            'marks_obtained' => 65.00,
            'max_marks' => 100.00,
            'pass_marks' => 35.00,
        ]);

        $exitCode = Artisan::call('exams:compile-report-cards', [
            'exam_term_id' => $term->id,
            '--class_id' => $class->id,
            '--publish' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Check Student 1 Report Card
        $card1 = SchoolReportCard::where('student_id', $student1->id)->where('exam_term_id', $term->id)->first();
        $this->assertNotNull($card1);
        $this->assertEquals(95.00, (float) $card1->percentage);
        $this->assertEquals('A+', $card1->overall_grade);
        $this->assertEquals(1, $card1->class_rank);
        $this->assertEquals('PASS', $card1->result_status);

        // Check Student 2 Report Card
        $card2 = SchoolReportCard::where('student_id', $student2->id)->where('exam_term_id', $term->id)->first();
        $this->assertNotNull($card2);
        $this->assertEquals(65.00, (float) $card2->percentage);
        $this->assertEquals('B', $card2->overall_grade);
        $this->assertEquals(2, $card2->class_rank);
    }

    public function test_daily_school_digest_runs_cleanly_and_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $invoice = FeeInvoice::create([
            'invoice_number' => 'INV-2025-0001',
            'student_id' => $student->id,
            'title' => 'Tuition',
            'amount' => 500.00,
            'total_due' => 500.00,
            'paid_amount' => 500.00,
            'due_date' => now()->toDateString(),
            'status' => 'paid',
        ]);

        FeePayment::create([
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'transaction_reference' => 'TXN-TEST-12345',
            'receipt_number' => 'RCP-TEST-12345',
            'amount' => 500.00,
            'payment_method' => 'upi',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $exitCode = Artisan::call('school:daily-digest');
        $this->assertEquals(0, $exitCode);

        $notif = InAppNotification::where('user_id', $admin->id)
            ->where('title', 'like', '%Daily Operational Digest%')
            ->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Revenue Collected', $notif->message);
    }

    public function test_attendance_shortage_alert_flags_students_below_threshold(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $student = User::factory()->create(['role' => 'student', 'parent_id' => $parent->id]);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $class = AcademicClass::create([
            'name' => 'Grade 12',
            'grade_level' => 12,
            'section' => 'A',
            'stream' => 'Commerce',
            'academic_session' => '2025-2026',
        ]);

        // Create 10 sessions, student present in only 4 (40% < 75%)
        for ($i = 1; $i <= 10; $i++) {
            $sess = AttendanceSession::create([
                'class_id' => $class->id,
                'teacher_id' => $teacher->id,
                'session_date' => now()->subDays($i)->toDateString(),
            ]);

            AttendanceRecord::create([
                'session_id' => $sess->id,
                'student_id' => $student->id,
                'status' => $i <= 4 ? 'present' : 'absent',
            ]);
        }

        $exitCode = Artisan::call('attendance:audit-shortage', [
            '--threshold' => 75,
            '--min-sessions' => 10,
        ]);
        $this->assertEquals(0, $exitCode);

        $alert = InAppNotification::where('user_id', $parent->id)
            ->where('title', 'like', '%Low Attendance Warning%')
            ->first();
        $this->assertNotNull($alert);
        $this->assertStringContainsString('40%', $alert->message);
    }

    public function test_timetable_auto_substitute_finds_free_teacher_and_dispatches_proxy(): void
    {
        $teacherAbsent = User::factory()->create(['role' => 'teacher', 'name' => 'Prof Absent']);
        $teacherFree = User::factory()->create(['role' => 'teacher', 'name' => 'Prof Free']);

        $class = AcademicClass::create([
            'name' => 'Grade 11',
            'grade_level' => 11,
            'section' => 'A',
            'stream' => 'Science',
            'academic_session' => '2025-2026',
        ]);

        $subject = AcademicSubject::create([
            'name' => 'Physics',
            'code' => 'PHY101',
            'class_id' => $class->id,
            'teacher_id' => $teacherAbsent->id,
        ]);

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeekIso;

        // Timetable slot for absent teacher
        SchoolPeriodTimetable::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherAbsent->id,
            'day_of_week' => $dayOfWeek,
            'period_number' => 1,
            'start_time' => '09:00',
            'end_time' => '09:45',
            'room_number' => 'Lab 1',
            'is_break' => false,
        ]);

        // Mark Teacher Absent via approved leave
        StudentLeaveRequest::create([
            'student_id' => $teacherAbsent->id,
            'start_date' => $today,
            'end_date' => $today,
            'reason' => 'Medical Sick Leave',
            'status' => 'approved',
            'type' => 'medical',
        ]);

        $exitCode = Artisan::call('timetable:auto-substitute', [
            '--date' => $today,
            '--day' => $dayOfWeek,
            '--notify' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Teacher Free should have received proxy coverage notification
        $proxyNotif = InAppNotification::where('user_id', $teacherFree->id)
            ->where('title', 'like', '%Proxy Coverage Request%')
            ->first();
        $this->assertNotNull($proxyNotif);
        $this->assertStringContainsString('Period 1', $proxyNotif->message);
    }
}
