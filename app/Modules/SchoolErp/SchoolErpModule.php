<?php

namespace App\Modules\SchoolErp;

use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\Dormitory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolFeeStructure;
use App\Models\SchoolPeriod;
use App\Models\SchoolPeriodTimetable;
use App\Models\SchoolReportCard;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffLeaveRequest;
use App\Models\StaffPayroll;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;

/**
 * School ERP Module Boundary Definition
 *
 * The School ERP domain encapsulates institutional administrative workflows:
 * - Academic structure (Academic Years, Classes, Sections, Streams, Subjects).
 * - Student Lifecycle (Admissions, Identity Cards, Attendance, Report Cards, Progression).
 * - Staff Management (Profiles, Biometric/Daily Attendance, Leave Balances, Payroll).
 * - Financial Operations (Fee Structures, Invoices, Offline/UPI Payments, Waivers).
 * - Operational Logistics (School Timetable, Substitute Allocation, Transport Fleet, Dormitories).
 * - Front Office & Institutional Governance (Visitors, Call logs, Postal, Compliance).
 */
final class SchoolErpModule
{
    public const NAME = 'School ERP & Institution Management';

    public const VERSION = '2.0.0';

    /**
     * Core models owned by School ERP domain.
     */
    public static function models(): array
    {
        return [
            AcademicClass::class,
            AcademicHoliday::class,
            AcademicYear::class,
            ClassEnrollment::class,
            FeeInvoice::class,
            FeePayment::class,
            SchoolFeeStructure::class,
            SchoolExamTerm::class,
            SchoolExamSchedule::class,
            SchoolExamMark::class,
            SchoolReportCard::class,
            SchoolPeriod::class,
            SchoolPeriodTimetable::class,
            StaffAttendanceRecord::class,
            StaffLeaveRequest::class,
            StaffPayroll::class,
            TransportRoute::class,
            TransportVehicle::class,
            Dormitory::class,
        ];
    }
}
