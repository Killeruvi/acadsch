# Architecture Boundaries: LMS vs School ERP

This application enforces clean boundaries between **School ERP** and **LMS**:

| Boundary | Domain | Key Responsibilities | Primary Controllers / Services |
| :--- | :--- | :--- | :--- |
| **School ERP** | Institutional & Administrative | Student Lifecycle, Roll Call, Staff Attendance, Payroll, Fee Invoices & Payments, Timetables, Exams, Logistics | `App\Http\Controllers\School\*`, `App\Services\Payroll\*`, `App\Services\HR\*` |
| **LMS** | Academic & Learning | Courses, Videos, Homework, Daily Practice Problems (DPP), Quizzes, Gamification, Bloom's Taxonomy | `App\Http\Controllers\WebCourseController`, `DppController`, `HomeworkController`, `LiveQuizController` |

### Unified Controllers Policy
- **Fees**: Unified in `App\Http\Controllers\School\SchoolFeeController`.
- **Attendance**: Unified in `App\Http\Controllers\School\SchoolAttendanceController` (students) and `App\Http\Controllers\School\StaffAttendanceController` (staff).
- **Students**: Unified in `App\Http\Controllers\School\SchoolStudentController`.
- Legacy root controllers (`FeeController`, `AttendanceController`, `StudentDirectoryController`) are marked `@deprecated` and delegate to the canonical School ERP controllers to maintain full backward compatibility without duplicate domain logic.
