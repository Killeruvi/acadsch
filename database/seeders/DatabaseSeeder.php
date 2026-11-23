<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FacultyAccessCode;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Schedule;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@primearc.edu'],
            [
                'name' => 'PrimeArc Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+91 98101 23456',
                'bio' => 'Principal and School Administration, PrimeArc Public School.',
                'student_id_number' => 'PA-ADM-0001',
                'date_of_birth' => '1982-04-12',
                'address' => 'Plot 42, Institutional Area, New Delhi 110092',
                'blood_group' => 'O+',
                'card_valid_until' => now()->addYears(3)->toDateString(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'principal@primearc.edu.in'],
            [
                'name' => 'Dr. Rajeshwar Sharma (Principal)',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+91 98101 23456',
                'bio' => 'Principal and Head of School, PrimeArc Public School.',
                'student_id_number' => 'PA-ADM-0002',
                'date_of_birth' => '1982-04-12',
                'address' => 'Plot 42, Institutional Area, New Delhi 110092',
                'blood_group' => 'O+',
                'card_valid_until' => now()->addYears(3)->toDateString(),
            ]
        );

        FacultyAccessCode::firstOrCreate(
            ['code' => 'FAC-2026-INIT'],
            [
                'generated_by' => $admin->id,
                'notes' => 'Master Initial Faculty Authorization Code',
                'is_used' => false,
            ]
        );

        // 2. Create Teachers (Single Faculty for Testing)
        $teacher1 = User::firstOrCreate(
            ['email' => 'teacher@primearc.edu'],
            [
                'name' => 'Dr. Vikramaditya Sen',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => '+91 98112 34567',
                'bio' => 'Senior Professor of Physics & Applied Computer Science.',
                'student_id_number' => 'PA-FAC-0002',
                'date_of_birth' => '1984-06-23',
                'address' => 'Faculty Block B, Prime Arc Campus, New Delhi 110092',
                'blood_group' => 'A+',
                'card_valid_until' => now()->addYears(2)->toDateString(),
            ]
        );
        $teacher2 = $teacher1;
        $teacher3 = $teacher1;

        // 3. Create Students (Single Scholar for Testing)
        $student1 = User::firstOrCreate(
            ['email' => 'student@primearc.edu'],
            [
                'name' => 'Aarav Sharma',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'phone' => '+91 98765 43201',
                'bio' => 'Senior Secondary student interested in computational algorithms and Olympiad physics.',
                'student_id_number' => 'PA-STD-2026-01',
                'date_of_birth' => '2004-03-15',
                'address' => 'Student Hostel, PrimeArc Public School, New Delhi',
                'guardian_name' => 'Ramesh Sharma',
                'guardian_phone' => '+91 98111 55667',
                'guardian_email' => 'r.sharma@parent.primearc.edu.in',
                'blood_group' => 'O+',
                'card_valid_until' => now()->addYears(2)->toDateString(),
            ]
        );
        $student2 = $student1;
        $student3 = $student1;
        $student4 = $student1;
        $student5 = $student1;

        // 4. Create Courses
        $course1 = Course::firstOrCreate(
            ['code' => 'CS101'],
            [
                'title' => 'Introduction to Computer Science & Algorithms',
                'description' => 'Fundamental concepts of computation, data structures, recursion, complexity, and algorithmic thinking.',
                'category' => 'Computer Science',
                'credits' => 4,
                'capacity' => 40,
                'teacher_id' => $teacher1->id,
                'status' => 'active',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
            ]
        );

        $course2 = Course::firstOrCreate(
            ['code' => 'SE204'],
            [
                'title' => 'Modern Full-Stack Web Architecture',
                'description' => 'Architecting robust web applications with Laravel, relational databases, clean APIs, and modern reactive frontends.',
                'category' => 'Software Engineering',
                'credits' => 4,
                'capacity' => 35,
                'teacher_id' => $teacher2->id,
                'status' => 'active',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
            ]
        );

        $course3 = Course::firstOrCreate(
            ['code' => 'PHY101'],
            [
                'title' => 'Fundamentals of Classical & Modern Physics',
                'description' => 'Mechanics, electromagnetism, wave-particle duality, and introductory quantum mechanics with lab sessions.',
                'category' => 'Physics',
                'credits' => 4,
                'capacity' => 30,
                'teacher_id' => $teacher3->id,
                'status' => 'active',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
            ]
        );

        $course4 = Course::firstOrCreate(
            ['code' => 'DS301'],
            [
                'title' => 'Data Science & Machine Learning Applications',
                'description' => 'Statistical modeling, exploratory data analysis, neural networks, supervised & unsupervised machine learning.',
                'category' => 'Data Science',
                'credits' => 3,
                'capacity' => 25,
                'teacher_id' => $teacher1->id,
                'status' => 'active',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(4)->endOfMonth()->toDateString(),
            ]
        );

        // 5. Create Schedules
        $schedules = [
            // CS101
            ['course_id' => $course1->id, 'title' => 'Lecture & Problem Solving', 'day_of_week' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'Aryabhata Hall 101'],
            ['course_id' => $course1->id, 'title' => 'Hands-on Programming Lab', 'day_of_week' => 'Wednesday', 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => 'Computer Lab 3'],
            // SE204
            ['course_id' => $course2->id, 'title' => 'Architecture & Design Patterns', 'day_of_week' => 'Tuesday', 'start_time' => '11:00:00', 'end_time' => '12:30:00', 'room' => 'C.V. Raman Auditorium'],
            ['course_id' => $course2->id, 'title' => 'Code Review & Team Projects', 'day_of_week' => 'Thursday', 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'room' => 'Software Studio A'],
            // PHY101
            ['course_id' => $course3->id, 'title' => 'Physics Principles', 'day_of_week' => 'Monday', 'start_time' => '13:00:00', 'end_time' => '14:30:00', 'room' => 'Science Bldg Room 204'],
            ['course_id' => $course3->id, 'title' => 'Experimental Lab Session', 'day_of_week' => 'Friday', 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'room' => 'Physics Laboratory B'],
            // DS301
            ['course_id' => $course4->id, 'title' => 'Machine Learning Seminar', 'day_of_week' => 'Friday', 'start_time' => '14:00:00', 'end_time' => '16:30:00', 'room' => 'Data Center Lab 4'],
        ];

        foreach ($schedules as $sch) {
            Schedule::firstOrCreate(
                ['course_id' => $sch['course_id'], 'day_of_week' => $sch['day_of_week'], 'start_time' => $sch['start_time']],
                $sch
            );
        }

        // 6. Enrollments
        $enrollments = [
            ['course_id' => $course1->id, 'student_id' => $student1->id, 'status' => 'enrolled', 'grade_letter' => 'A', 'final_score' => 94.5],
            ['course_id' => $course2->id, 'student_id' => $student1->id, 'status' => 'enrolled', 'grade_letter' => 'A-', 'final_score' => 91.0],
            ['course_id' => $course1->id, 'student_id' => $student2->id, 'status' => 'enrolled', 'grade_letter' => 'B+', 'final_score' => 88.0],
            ['course_id' => $course2->id, 'student_id' => $student2->id, 'status' => 'enrolled', 'grade_letter' => 'A', 'final_score' => 96.0],
            ['course_id' => $course3->id, 'student_id' => $student3->id, 'status' => 'enrolled', 'grade_letter' => 'A', 'final_score' => 95.0],
            ['course_id' => $course1->id, 'student_id' => $student3->id, 'status' => 'enrolled', 'grade_letter' => 'B', 'final_score' => 84.0],
            ['course_id' => $course4->id, 'student_id' => $student4->id, 'status' => 'enrolled', 'grade_letter' => 'A+', 'final_score' => 98.0],
            ['course_id' => $course2->id, 'student_id' => $student4->id, 'status' => 'enrolled', 'grade_letter' => 'A', 'final_score' => 92.5],
            ['course_id' => $course1->id, 'student_id' => $student5->id, 'status' => 'enrolled', 'grade_letter' => 'B-', 'final_score' => 81.0],
            ['course_id' => $course2->id, 'student_id' => $student5->id, 'status' => 'enrolled', 'grade_letter' => 'B+', 'final_score' => 87.0],
        ];

        foreach ($enrollments as $enr) {
            Enrollment::firstOrCreate(
                ['course_id' => $enr['course_id'], 'student_id' => $enr['student_id']],
                $enr
            );
        }

        // 7. Assignments
        $assignment1 = Assignment::firstOrCreate(
            ['course_id' => $course1->id, 'title' => 'Lab 1: Algorithm Complexity & Big-O Analysis'],
            [
                'teacher_id' => $teacher1->id,
                'description' => 'Analyze the time and space complexity of merge sort, quicksort, and heap sort. Provide benchmarks with empirical measurements.',
                'due_date' => now()->addDays(5),
                'max_points' => 100,
            ]
        );

        $assignment2 = Assignment::firstOrCreate(
            ['course_id' => $course2->id, 'title' => 'Project 1: PostgreSQL REST API Implementation'],
            [
                'teacher_id' => $teacher2->id,
                'description' => 'Build and document a normalized database schema with Laravel migrations, Eloquent relationships, and Sanctum authenticated API endpoints.',
                'due_date' => now()->addDays(7),
                'max_points' => 100,
            ]
        );

        $assignment3 = Assignment::firstOrCreate(
            ['course_id' => $course3->id, 'title' => 'Problem Set 2: Harmonic Oscillators & Wave Propagation'],
            [
                'teacher_id' => $teacher3->id,
                'description' => 'Solve problems 1-8 from Chapter 4 concerning damped harmonic motion and resonance in physical wave guides.',
                'due_date' => now()->addDays(3),
                'max_points' => 100,
            ]
        );

        // 8. Submissions
        Submission::firstOrCreate(
            ['assignment_id' => $assignment1->id, 'student_id' => $student1->id],
            [
                'content' => 'Completed algorithmic benchmark tests across 10,000 to 1,000,000 array items. Demonstrated O(n log n) scaling for merge sort with minimal recursion overhead.',
                'submitted_at' => now()->subDay(),
                'grade' => 95.0,
                'status' => 'graded',
                'feedback' => 'Exceptional analysis! Clear graphs and clean code documentation.',
                'graded_by' => $teacher1->id,
                'graded_at' => now()->subHours(6),
            ]
        );

        Submission::firstOrCreate(
            ['assignment_id' => $assignment1->id, 'student_id' => $student2->id],
            [
                'content' => 'Implemented sorting benchmarks using Python and C extensions. Observed cache locality effects during in-place quicksort partitioning.',
                'submitted_at' => now()->subHours(12),
                'grade' => 88.0,
                'status' => 'graded',
                'feedback' => 'Great work on the cache locality discussion. Be sure to address worst-case pivot selection in future submissions.',
                'graded_by' => $teacher1->id,
                'graded_at' => now()->subHours(2),
            ]
        );

        Submission::firstOrCreate(
            ['assignment_id' => $assignment2->id, 'student_id' => $student1->id],
            [
                'content' => "Repository URL: https://github.com/student/laravel-pg-api\nImplemented full REST endpoints, validation requests, seeders, and token authentication.",
                'submitted_at' => now()->subHours(4),
                'grade' => null,
                'status' => 'submitted',
                'feedback' => null,
            ]
        );

        // 9. Fee Invoices & Online Payments
        // Invoice 1: Alice Paid Tuition
        $inv1 = FeeInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-001'],
            [
                'student_id' => $student1->id,
                'course_id' => $course1->id,
                'title' => 'Semester 1 Tuition & Lab Fee - CS101',
                'category' => 'Tuition',
                'amount' => 1200.00,
                'discount' => 100.00,
                'tax' => 0.00,
                'total_due' => 1100.00,
                'paid_amount' => 1100.00,
                'status' => 'paid',
                'due_date' => now()->addDays(14)->toDateString(),
                'notes' => 'Early enrollment discount of $100 applied.',
            ]
        );

        FeePayment::firstOrCreate(
            ['receipt_number' => 'REC-2026-001'],
            [
                'invoice_id' => $inv1->id,
                'student_id' => $student1->id,
                'transaction_reference' => 'TXN-PA-994821',
                'payment_method' => 'Credit Card',
                'amount' => 1100.00,
                'paid_at' => now()->subDays(2),
                'status' => 'successful',
                'received_by' => $admin->id,
                'notes' => 'Paid via Prime Arc Online Portal (Visa **** 4242).',
            ]
        );

        // Invoice 2: Alice Pending Tech Fee
        FeeInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-002'],
            [
                'student_id' => $student1->id,
                'course_id' => $course2->id,
                'title' => 'Software Studio & Cloud Sandbox Fee - SE204',
                'category' => 'Lab & Tech Fee',
                'amount' => 250.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 250.00,
                'paid_amount' => 0.00,
                'status' => 'pending',
                'due_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Cloud development workspace allocation.',
            ]
        );

        // Invoice 3: Bob Partial Payment
        $inv3 = FeeInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-003'],
            [
                'student_id' => $student2->id,
                'course_id' => $course2->id,
                'title' => 'Web Architecture Tuition - SE204',
                'category' => 'Tuition',
                'amount' => 1200.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 1200.00,
                'paid_amount' => 600.00,
                'status' => 'partial',
                'due_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Split installment plan agreed.',
            ]
        );

        FeePayment::firstOrCreate(
            ['receipt_number' => 'REC-2026-002'],
            [
                'invoice_id' => $inv3->id,
                'student_id' => $student2->id,
                'transaction_reference' => 'TXN-PA-884210',
                'payment_method' => 'UPI / NetBanking',
                'amount' => 600.00,
                'paid_at' => now()->subDay(),
                'status' => 'successful',
                'received_by' => $admin->id,
                'notes' => 'First installment received.',
            ]
        );

        // Invoice 4: Charlie Paid Physics Lab
        $inv4 = FeeInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-004'],
            [
                'student_id' => $student3->id,
                'course_id' => $course3->id,
                'title' => 'Physics Laboratory Instrumentation Fee - PHY101',
                'category' => 'Lab & Tech Fee',
                'amount' => 350.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 350.00,
                'paid_amount' => 350.00,
                'status' => 'paid',
                'due_date' => now()->subDays(5)->toDateString(),
                'notes' => 'Spectrometer & laser safety protocols license.',
            ]
        );

        FeePayment::firstOrCreate(
            ['receipt_number' => 'REC-2026-003'],
            [
                'invoice_id' => $inv4->id,
                'student_id' => $student3->id,
                'transaction_reference' => 'TXN-PA-773194',
                'payment_method' => 'Debit Card',
                'amount' => 350.00,
                'paid_at' => now()->subDays(4),
                'status' => 'successful',
                'received_by' => $admin->id,
                'notes' => 'Full payment settled online.',
            ]
        );

        // Invoice 5: Diya Overdue Fee
        FeeInvoice::firstOrCreate(
            ['invoice_number' => 'INV-2026-005'],
            [
                'student_id' => $student4->id,
                'course_id' => $course4->id,
                'title' => 'Machine Learning Cluster Compute Fee - DS301',
                'category' => 'Tuition',
                'amount' => 850.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 850.00,
                'paid_amount' => 0.00,
                'status' => 'overdue',
                'due_date' => now()->subDays(7)->toDateString(),
                'notes' => 'Reminder notice sent.',
            ]
        );

        // 13. Institutional Academic Modules
        $this->call([
            BloomsTaxonomySeeder::class,
            SyllabusSeeder::class,
            QuestionBankSeeder::class,
            AcademyInstitutionalSeeder::class,
            AcademicAdditionsSeeder::class,
            CurriculumStructuralResourcesSeeder::class,
            BatchSeeder::class,
            CollegeClassAndCurriculumSeeder::class,
            ArenaAndEnrichmentSeeder::class,
            LessonPlannerAndHolidaySeeder::class,
            SchoolSystemSeeder::class,
        ]);
    }
}
