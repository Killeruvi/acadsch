<?php

namespace Database\Seeders;

use App\Models\AcademyNotice;
use App\Models\AcademySetting;
use App\Models\CurriculumTopic;
use App\Models\StudentLeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademyInstitutionalSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $teacher = User::where('role', 'teacher')->first() ?? User::first();
        $student = User::where('role', 'student')->first() ?? User::first();

        // 1. Seed Institutional Settings
        $settings = [
            'institute_name' => 'Prime Arc Academy',
            'campus_name' => 'New Delhi Central Campus',
            'affiliation_number' => 'CBSE/AFF/2026/089',
            'academic_session' => '2026–2027',
            'principal_name' => 'Dr. Rajeshwar Sharma',
            'institution_phone' => '+91 98765 43210',
            'institution_email' => 'admissions@primearc.academy',
            'institution_address' => 'Plot 42, Knowledge Corridor, Institutional Area, New Delhi 110092',
            'upi_vpa' => 'primearc@upi',
            'currency_symbol' => '₹',
            'motto' => 'विद्या ददाति विनयं',
            'established_year' => '2018',
        ];

        foreach ($settings as $key => $val) {
            AcademySetting::set($key, $val);
        }

        // 2. Seed Official Academy Circulars & Notices
        $notices = [
            [
                'author_id' => $admin->id,
                'title' => 'Mid-Term Board Prep Examination & Admit Card Schedule (Session 2026-27)',
                'content' => 'All enrolled students from Classes 8 to 12 are hereby notified that the Mid-Term Examination starts next Monday. Admit cards and verified Privilege ID cards must be presented at the examination hall. Please verify your attendance percentage is above 75% to ensure debarment clearance.',
                'priority' => 'urgent',
                'target_audience' => 'all',
                'is_pinned' => true,
            ],
            [
                'author_id' => $admin->id,
                'title' => 'Parent-Teacher Academic Performance & Cognitive Progress Review Meeting',
                'content' => "The bi-monthly Parent-Teacher Meeting (PTM) will take place this Saturday from 09:00 AM to 01:30 PM. Parents can review Bloom's cognitive taxonomy radar, attendance records, and term fee receipts with class teachers.",
                'priority' => 'high',
                'target_audience' => 'parents',
                'is_pinned' => true,
            ],
            [
                'author_id' => $teacher->id,
                'title' => 'National Science Olympiad & Inter-Academy Robotics Workshop Registration',
                'content' => 'Registration is now open for the All-Bharat Science & STEM Olympiad. Interested students can apply through the Academic Portal before Friday.',
                'priority' => 'normal',
                'target_audience' => 'students',
                'is_pinned' => false,
            ],
        ];

        foreach ($notices as $n) {
            AcademyNotice::firstOrCreate(
                ['title' => $n['title']],
                $n
            );
        }

        // 3. Seed Sample Leave Request
        if ($student) {
            StudentLeaveRequest::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'start_date' => now()->subDays(5)->toDateString(),
                ],
                [
                    'end_date' => now()->subDays(4)->toDateString(),
                    'type' => 'medical',
                    'reason' => 'Severe viral fever and physician-advised rest. Medical certificate attached in documents vault.',
                    'status' => 'approved',
                    'reviewed_by' => $teacher->id,
                    'teacher_remarks' => 'Leave approved. Student advised to complete pending Science Chapter 1 DPP and homework.',
                ]
            );
        }

        // 4. Update Curriculum Topics with Progress Status & Study Resources
        $topics = CurriculumTopic::take(10)->get();
        foreach ($topics as $idx => $top) {
            $status = $idx < 3 ? 'completed' : ($idx < 6 ? 'in_progress' : 'not_started');
            $top->update([
                'status' => $status,
                'completed_at' => $status === 'completed' ? now()->subDays($idx * 2) : null,
                'study_notes' => null,
                'resource_url' => 'https://ncert.nic.in/textbook.php',
            ]);
        }
    }
}
