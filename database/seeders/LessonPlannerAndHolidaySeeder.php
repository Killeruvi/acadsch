<?php

namespace Database\Seeders;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LessonPlannerAndHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Sync official national holidays & festivals for 2026 and 2027
        AcademicCalendarService::syncOfficialHolidays(2026);
        AcademicCalendarService::syncOfficialHolidays(2027);

        // 2. Find a faculty teacher to assign as author
        $teacher = User::where('role', 'teacher')->first() ?? User::where('role', 'admin')->first();
        $teacherId = $teacher?->id;

        // 3. Loop through all classes and all subjects to seed rich lesson plans
        $classes = AcademicClass::with('subjects.topics')->orderBy('grade_level')->get();

        // Academic session start date (e.g. 15 June 2026 for Term 1)
        $term1Start = Carbon::create(2026, 6, 15);

        foreach ($classes as $class) {
            foreach ($class->subjects as $subject) {
                AcademicCalendarService::generateSubjectLessonPlans(
                    $subject,
                    $term1Start->copy(),
                    $teacherId,
                    'Term 1'
                );
            }
        }
    }
}
