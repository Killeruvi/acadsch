<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassEnrollment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolFeeStructure;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Principal / Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'principal@primearc.edu.in'],
            [
                'name' => 'Dr. Rajeshwar Sharma (Principal)',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+91 98101 23456',
                'bio' => 'Principal and Head of Institution, Prime Arc International School.',
                'admission_number' => 'SCH-DIR-01',
                'student_id_number' => 'PA-ADM-0001',
                'is_approved' => true,
            ]
        );

        // 2. Ensure Teachers (Faculty)
        $teacherSen = User::firstOrCreate(
            ['email' => 'teacher@primearc.edu'],
            [
                'name' => 'Dr. Vikramaditya Sen',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => '+91 98112 34567',
                'bio' => 'Senior Faculty of Physical Sciences & Applied Mathematics.',
                'student_id_number' => 'PA-FAC-0002',
                'admission_number' => 'FAC-0002',
                'is_approved' => true,
            ]
        );

        $teacherTuring = User::firstOrCreate(
            ['email' => 'alan.turing@primearc.edu'],
            [
                'name' => 'Dr. Alan Turing',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => '+91 98113 45678',
                'bio' => 'Senior Mathematics & Computer Science Specialist.',
                'student_id_number' => 'PA-FAC-0003',
                'admission_number' => 'FAC-0003',
                'is_approved' => true,
            ]
        );

        $teacherCurie = User::firstOrCreate(
            ['email' => 'marie.curie@primearc.edu'],
            [
                'name' => 'Prof. Marie Curie',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => '+91 98114 56789',
                'bio' => 'Chairperson of Science Curriculum & Chemistry Department.',
                'student_id_number' => 'PA-FAC-0004',
                'admission_number' => 'FAC-0004',
                'is_approved' => true,
            ]
        );

        $teacherLovelace = User::firstOrCreate(
            ['email' => 'ada.lovelace@primearc.edu'],
            [
                'name' => 'Dr. Ada Lovelace',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => '+91 98115 67890',
                'bio' => 'Department of Computer Science & Information Technology.',
                'student_id_number' => 'PA-FAC-0005',
                'admission_number' => 'FAC-0005',
                'is_approved' => true,
            ]
        );

        // 3. Create Parent User Account
        $parent = User::firstOrCreate(
            ['email' => 'parent@primearc.edu'],
            [
                'name' => 'Ramesh Sharma',
                'password' => Hash::make('password123'),
                'role' => 'parent',
                'phone' => '+91 98111 55667',
                'address' => 'Plot 104, Institutional Enclave, Sector 62, New Delhi',
                'is_approved' => true,
            ]
        );

        // 4. Create K-12 School Classes & Sections
        $classesConfig = [
            ['name' => 'Class 1', 'grade_level' => 1, 'section' => 'A', 'room_number' => 'Room 101', 'capacity' => 30],
            ['name' => 'Class 2', 'grade_level' => 2, 'section' => 'A', 'room_number' => 'Room 102', 'capacity' => 30],
            ['name' => 'Class 3', 'grade_level' => 3, 'section' => 'A', 'room_number' => 'Room 103', 'capacity' => 35],
            ['name' => 'Class 4', 'grade_level' => 4, 'section' => 'A', 'room_number' => 'Room 104', 'capacity' => 35],
            ['name' => 'Class 5', 'grade_level' => 5, 'section' => 'A', 'room_number' => 'Room 105', 'capacity' => 35],
            ['name' => 'Class 6', 'grade_level' => 6, 'section' => 'A', 'room_number' => 'Room 201', 'capacity' => 40],
            ['name' => 'Class 7', 'grade_level' => 7, 'section' => 'A', 'room_number' => 'Room 202', 'capacity' => 40],
            ['name' => 'Class 8', 'grade_level' => 8, 'section' => 'A', 'room_number' => 'Room 203', 'capacity' => 40],
            ['name' => 'Class 9', 'grade_level' => 9, 'section' => 'A', 'room_number' => 'Room 301', 'capacity' => 40, 'teacher' => $teacherCurie->id],
            ['name' => 'Class 10', 'grade_level' => 10, 'section' => 'A', 'room_number' => 'Room 302', 'capacity' => 40, 'teacher' => $teacherSen->id],
            ['name' => 'Class 10', 'grade_level' => 10, 'section' => 'B', 'room_number' => 'Room 303', 'capacity' => 40],
            ['name' => 'Class 11', 'grade_level' => 11, 'section' => 'Science', 'room_number' => 'Sci Lab Block A', 'capacity' => 35, 'teacher' => $teacherLovelace->id],
            ['name' => 'Class 11', 'grade_level' => 11, 'section' => 'Commerce', 'room_number' => 'Room 401', 'capacity' => 35],
            ['name' => 'Class 12', 'grade_level' => 12, 'section' => 'Science', 'room_number' => 'Sci Lab Block B', 'capacity' => 35, 'teacher' => $teacherTuring->id],
            ['name' => 'Class 12', 'grade_level' => 12, 'section' => 'Commerce', 'room_number' => 'Room 402', 'capacity' => 35],
        ];

        $classesMap = [];
        foreach ($classesConfig as $cfg) {
            $stream = $cfg['stream'] ?? (in_array($cfg['section'], ['Science', 'Commerce', 'Arts']) ? $cfg['section'] : null);
            $class = AcademicClass::firstOrCreate(
                [
                    'name' => $cfg['name'],
                    'section' => $cfg['section'],
                ],
                [
                    'grade_level' => $cfg['grade_level'],
                    'stream' => $stream,
                    'academic_year' => '2026-2027',
                    'class_teacher_id' => $cfg['teacher'] ?? null,
                    'room_number' => $cfg['room_number'],
                    'capacity' => $cfg['capacity'],
                ]
            );

            // Update attributes if existing
            $class->update([
                'grade_level' => $cfg['grade_level'],
                'stream' => $stream,
                'room_number' => $cfg['room_number'],
                'capacity' => $cfg['capacity'],
                'class_teacher_id' => $cfg['teacher'] ?? $class->class_teacher_id,
            ]);

            $classesMap[$cfg['name'] . '-' . $cfg['section']] = $class;
        }

        $class10A = $classesMap['Class 10-A'];
        $class12Sci = $classesMap['Class 12-Science'];

        // 5. Create Subjects for Class 10-A
        $subjects10A = [
            ['name' => 'English Language & Literature', 'code' => 'ENG-10', 'teacher' => $teacherCurie->id, 'weekly' => 6],
            ['name' => 'Mathematics Standard', 'code' => 'MTH-10', 'teacher' => $teacherTuring->id, 'weekly' => 7],
            ['name' => 'General Science (Physics & Chem)', 'code' => 'SCI-10', 'teacher' => $teacherSen->id, 'weekly' => 7],
            ['name' => 'Social Sciences & History', 'code' => 'SST-10', 'teacher' => $teacherCurie->id, 'weekly' => 5],
            ['name' => 'Computer Applications & Coding', 'code' => 'CA-10', 'teacher' => $teacherLovelace->id, 'weekly' => 5],
        ];

        $createdSubjects10A = [];
        foreach ($subjects10A as $sub) {
            $createdSubjects10A[] = AcademicSubject::firstOrCreate(
                [
                    'class_id' => $class10A->id,
                    'code' => $sub['code'],
                ],
                [
                    'name' => $sub['name'],
                    'teacher_id' => $sub['teacher'],
                    'weekly_periods' => $sub['weekly'],
                ]
            );
        }

        // Subjects for Class 12-Science
        $subjects12Sci = [
            ['name' => 'Advanced Physics', 'code' => 'PHY-12', 'teacher' => $teacherSen->id, 'weekly' => 7],
            ['name' => 'Pure Mathematics & Calculus', 'code' => 'MTH-12', 'teacher' => $teacherTuring->id, 'weekly' => 7],
            ['name' => 'Inorganic & Organic Chemistry', 'code' => 'CHM-12', 'teacher' => $teacherCurie->id, 'weekly' => 7],
            ['name' => 'Computer Science & Python', 'code' => 'CS-12', 'teacher' => $teacherLovelace->id, 'weekly' => 6],
        ];

        foreach ($subjects12Sci as $sub) {
            AcademicSubject::firstOrCreate(
                [
                    'class_id' => $class12Sci->id,
                    'code' => $sub['code'],
                ],
                [
                    'name' => $sub['name'],
                    'teacher_id' => $sub['teacher'],
                    'weekly_periods' => $sub['weekly'],
                ]
            );
        }

        // 6. Ensure Students and enroll into Class 10-A
        $studentAarav = User::firstOrCreate(
            ['email' => 'student@primearc.edu'],
            [
                'name' => 'Aarav Sharma',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'admission_number' => 'ADM-2026-0001',
                'student_id_number' => 'PA-STD-2026-01',
                'current_class_id' => $class10A->id,
                'roll_number' => 1,
                'parent_id' => $parent->id,
                'phone' => '+91 98765 43201',
                'date_of_birth' => '2010-04-15',
                'guardian_name' => 'Ramesh Sharma',
                'guardian_phone' => '+91 98111 55667',
                'guardian_email' => 'parent@primearc.edu',
                'blood_group' => 'O+',
                'is_approved' => true,
            ]
        );
        $studentAarav->update([
            'current_class_id' => $class10A->id,
            'roll_number' => 1,
            'parent_id' => $parent->id,
            'admission_number' => 'ADM-2026-0001',
        ]);

        $studentsData = [
            ['name' => 'Alice Smith', 'email' => 'alice@student.school.edu', 'roll' => 2, 'adm' => 'ADM-2026-0002'],
            ['name' => 'Bob Johnson', 'email' => 'bob@student.school.edu', 'roll' => 3, 'adm' => 'ADM-2026-0003'],
            ['name' => 'Priya Patel', 'email' => 'priya@student.school.edu', 'roll' => 4, 'adm' => 'ADM-2026-0004'],
            ['name' => 'Karan Verma', 'email' => 'karan@student.school.edu', 'roll' => 5, 'adm' => 'ADM-2026-0005'],
        ];

        $enrolledStudents10A = [$studentAarav];

        foreach ($studentsData as $st) {
            $user = User::firstOrCreate(
                ['email' => $st['email']],
                [
                    'name' => $st['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'student',
                    'admission_number' => $st['adm'],
                    'student_id_number' => $st['adm'],
                    'current_class_id' => $class10A->id,
                    'roll_number' => $st['roll'],
                    'date_of_birth' => '2010-06-20',
                    'blood_group' => 'B+',
                    'guardian_name' => 'Parent of ' . $st['name'],
                    'guardian_phone' => '+91 98222 33445',
                    'is_approved' => true,
                ]
            );
            $user->update([
                'current_class_id' => $class10A->id,
                'roll_number' => $st['roll'],
                'admission_number' => $st['adm'],
            ]);
            $enrolledStudents10A[] = $user;
        }

        // Create ClassEnrollment rows
        foreach ($enrolledStudents10A as $st) {
            ClassEnrollment::updateOrCreate(
                ['class_id' => $class10A->id, 'student_id' => $st->id],
                [
                    'roll_number' => $st->roll_number,
                    'admission_number' => $st->admission_number,
                    'academic_year' => '2026-2027',
                    'status' => 'active',
                ]
            );
        }

        // 7. Examination Term
        $term1 = SchoolExamTerm::firstOrCreate(
            ['name' => 'Term 1 / Mid-Term Examination 2026-27'],
            [
                'academic_year' => '2026-2027',
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->subDays(15)->toDateString(),
                'status' => 'completed',
                'description' => 'Official Mid-Term Examination for all classes.',
            ]
        );

        $termAnnual = SchoolExamTerm::firstOrCreate(
            ['name' => 'Annual Examination 2026-27'],
            [
                'academic_year' => '2026-2027',
                'start_date' => now()->addMonths(2)->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'status' => 'upcoming',
                'description' => 'Final Board & Annual Comprehensive Evaluation.',
            ]
        );

        // 8. Exam Schedules & Marks for Class 10-A
        $baseScores = [
            $studentAarav->id => [92.0, 95.0, 89.0, 91.0, 96.0],
            $enrolledStudents10A[1]->id => [88.0, 82.0, 85.0, 86.0, 90.0],
            $enrolledStudents10A[2]->id => [75.0, 78.0, 72.0, 79.0, 80.0],
            $enrolledStudents10A[3]->id => [94.0, 96.0, 92.0, 95.0, 98.0],
            $enrolledStudents10A[4]->id => [68.0, 70.0, 65.0, 71.0, 74.0],
        ];

        foreach ($createdSubjects10A as $subIdx => $subject) {
            // Datesheet
            SchoolExamSchedule::updateOrCreate(
                [
                    'exam_term_id' => $term1->id,
                    'class_id' => $class10A->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'exam_date' => now()->subDays(25 - $subIdx * 2)->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'room_number' => 'Exam Hall A',
                    'max_marks' => 100.00,
                    'pass_marks' => 35.00,
                ]
            );

            // Marks entry
            foreach ($enrolledStudents10A as $st) {
                $score = $baseScores[$st->id][$subIdx] ?? 85.0;
                $grade = SchoolExamMark::calculateGrade($score, 100.0);

                SchoolExamMark::updateOrCreate(
                    [
                        'exam_term_id' => $term1->id,
                        'class_id' => $class10A->id,
                        'subject_id' => $subject->id,
                        'student_id' => $st->id,
                    ],
                    [
                        'marks_obtained' => $score,
                        'max_marks' => 100.00,
                        'pass_marks' => 35.00,
                        'letter_grade' => $grade,
                        'teacher_remarks' => $score >= 90 ? 'Demonstrated exceptional analytical depth.' : 'Good conceptual grasp and consistent effort.',
                    ]
                );
            }
        }

        // 9. Generate Report Cards for Class 10-A
        $results = [];
        foreach ($enrolledStudents10A as $st) {
            $studentMarks = SchoolExamMark::where('exam_term_id', $term1->id)
                ->where('class_id', $class10A->id)
                ->where('student_id', $st->id)
                ->get();

            $totMax = $studentMarks->sum('max_marks') ?: 500.00;
            $totObt = $studentMarks->sum('marks_obtained');
            $pct = round(($totObt / $totMax) * 100, 2);

            $results[] = [
                'student' => $st,
                'totMax' => $totMax,
                'totObt' => $totObt,
                'pct' => $pct,
                'gpa' => round($pct / 10, 2),
                'grade' => $pct >= 90 ? 'A+' : ($pct >= 80 ? 'A' : 'B+'),
            ];
        }

        usort($results, fn($a, $b) => $b['pct'] <=> $a['pct']);

        foreach ($results as $rank => $r) {
            SchoolReportCard::updateOrCreate(
                [
                    'exam_term_id' => $term1->id,
                    'class_id' => $class10A->id,
                    'student_id' => $r['student']->id,
                ],
                [
                    'total_max_marks' => $r['totMax'],
                    'total_marks_obtained' => $r['totObt'],
                    'percentage' => $r['pct'],
                    'gpa' => $r['gpa'],
                    'overall_grade' => $r['grade'],
                    'class_rank' => $rank + 1,
                    'total_working_days' => 110,
                    'days_present' => 106,
                    'attendance_percentage' => 96.36,
                    'result_status' => 'PASS',
                    'teacher_remarks' => 'Outstanding academic focus, exemplary discipline, and active classroom engagement.',
                    'principal_remarks' => 'Commended for excellent scholastic achievement. Promoted with distinction.',
                    'is_published' => true,
                    'generated_date' => now()->toDateString(),
                ]
            );
        }

        // 10. Weekly Timetable for Class 10-A (Periods 1 to 7)
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = [
            1 => ['start' => '08:30:00', 'end' => '09:15:00'],
            2 => ['start' => '09:15:00', 'end' => '10:00:00'],
            3 => ['start' => '10:00:00', 'end' => '10:45:00'],
            4 => ['start' => '10:45:00', 'end' => '11:30:00'],
            5 => ['start' => '12:15:00', 'end' => '13:00:00'],
            6 => ['start' => '13:00:00', 'end' => '13:45:00'],
            7 => ['start' => '13:45:00', 'end' => '14:30:00'],
        ];

        $subList = $createdSubjects10A;
        $sIdx = 0;
        foreach ($days as $day) {
            foreach ($periods as $pNum => $time) {
                $subj = $subList[$sIdx % count($subList)];
                $sIdx++;

                SchoolPeriodTimetable::updateOrCreate(
                    [
                        'class_id' => $class10A->id,
                        'day_of_week' => $day,
                        'period_number' => $pNum,
                    ],
                    [
                        'subject_id' => $subj->id,
                        'teacher_id' => $subj->teacher_id,
                        'start_time' => $time['start'],
                        'end_time' => $time['end'],
                        'room_number' => 'Room 302',
                        'is_break' => false,
                        'title' => $subj->name,
                    ]
                );
            }
        }

        // 11. Daily Morning Attendance for Today
        $todaySession = AttendanceSession::updateOrCreate(
            [
                'class_id' => $class10A->id,
                'session_date' => now()->toDateString(),
            ],
            [
                'teacher_id' => $teacherSen->id,
                'title' => "Morning Roll Call - {$class10A->display_name}",
                'notes' => 'Full morning assembly and regular period session.',
            ]
        );

        foreach ($enrolledStudents10A as $st) {
            AttendanceRecord::updateOrCreate(
                [
                    'session_id' => $todaySession->id,
                    'student_id' => $st->id,
                ],
                [
                    'status' => 'present',
                    'remarks' => 'Present on time for morning assembly',
                ]
            );
        }

        // 12. School Fee Structures & Sample Invoice
        $feeStructures = [
            ['title' => 'Monthly Tuition Fee', 'type' => 'tuition', 'amount' => 4500.00, 'freq' => 'monthly'],
            ['title' => 'Science Laboratory & Practical Fee', 'type' => 'lab', 'amount' => 800.00, 'freq' => 'monthly'],
            ['title' => 'Computer Lab & Coding Curriculum', 'type' => 'lab', 'amount' => 600.00, 'freq' => 'monthly'],
            ['title' => 'Annual Activity & Library Charge', 'type' => 'annual', 'amount' => 3500.00, 'freq' => 'annual'],
        ];

        foreach ($feeStructures as $fs) {
            SchoolFeeStructure::updateOrCreate(
                [
                    'class_id' => $class10A->id,
                    'fee_title' => $fs['title'],
                ],
                [
                    'fee_type' => $fs['type'],
                    'amount' => $fs['amount'],
                    'due_frequency' => $fs['freq'],
                    'academic_year' => '2026-2027',
                    'description' => 'Mandatory scholastic tariff for Class 10.',
                ]
            );
        }

        // Fee Invoice for Aarav Sharma
        $invMonth = now()->format('Y-m');
        $invoice = FeeInvoice::updateOrCreate(
            [
                'invoice_number' => "SCH-INV-2026-{$invMonth}-001",
            ],
            [
                'student_id' => $studentAarav->id,
                'title' => "Monthly School Tuition & Lab Charges ({$invMonth})",
                'category' => 'tuition',
                'amount' => 5900.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total_due' => 5900.00,
                'paid_amount' => 5900.00,
                'status' => 'paid',
                'due_date' => now()->endOfMonth()->toDateString(),
                'billing_type' => 'monthly',
                'billing_month' => $invMonth,
                'notes' => 'Official tuition invoice for Aarav Sharma (Class 10 - Section A)',
            ]
        );

        // Payment receipt for invoice
        FeePayment::firstOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'student_id' => $studentAarav->id,
                'amount' => 5900.00,
                'payment_method' => 'upi',
                'transaction_reference' => 'UPI-PA-9982348123',
                'receipt_number' => 'SCH-REC-2026-9041',
                'paid_at' => now()->subDays(2),
                'status' => 'completed',
                'received_by' => $admin->id,
                'notes' => 'Paid via UPI by Ramesh Sharma (Parent)',
            ]
        );
    }
}
