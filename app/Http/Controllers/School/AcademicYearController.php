<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademySetting;
use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    private function authorizeAdmin(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403, 'Administrative access is required.');
        return $actor;
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $sessions = AcademicClass::query()
            ->whereNotNull('academic_year')->where('academic_year', '<>', '')
            ->select('academic_year', DB::raw('COUNT(*) as class_count'))
            ->groupBy('academic_year')->orderByDesc('academic_year')->get();
        $activeSession = AcademySetting::get('academic_session');
        $sessionSetup = [];
        foreach ($sessions as $session) {
            $classIds = AcademicClass::where('academic_year', $session->academic_year)->pluck('id');
            $sessionSetup[$session->academic_year] = [
                'fees' => DB::table('school_fee_structures')->whereIn('class_id', $classIds)->where('academic_year', $session->academic_year)->count(),
                'timetable' => DB::table('school_period_timetables')->whereIn('class_id', $classIds)->count(),
                'exams' => DB::table('school_exam_terms')->where('academic_year', $session->academic_year)->count(),
            ];
        }
        return view('school.academic-years.index', compact('sessions', 'activeSession', 'sessionSetup'));
    }

    /** Create next-session class and subject masters without copying student or operational history. */
    public function cloneSession(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdmin($request);
        $data = $request->validate([
            'source_year' => ['required', 'string', 'max:20'],
            'target_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ]);
        [$start, $end] = array_map('intval', explode('-', $data['target_year']));
        if ($end !== $start + 1) {
            throw ValidationException::withMessages(['target_year' => 'Enter consecutive academic years, for example 2027-2028.']);
        }

        $result = DB::transaction(function () use ($data, $actor) {
            $sourceClasses = AcademicClass::query()->where('academic_year', $data['source_year'])
                ->orderBy('grade_level')->orderBy('stream')->orderBy('section')->lockForUpdate()->get();
            if ($sourceClasses->isEmpty()) {
                throw ValidationException::withMessages(['source_year' => 'The selected source session has no class records.']);
            }
            if (AcademicClass::query()->where('academic_year', $data['target_year'])->exists()) {
                throw ValidationException::withMessages(['target_year' => 'Classes already exist for this session. Use the existing classes instead of cloning duplicates.']);
            }

            $createdClasses = 0;
            $createdSubjects = 0;
            $createdFees = 0;
            $createdTimetableRows = 0;
            $classMap = [];
            foreach ($sourceClasses as $source) {
                $newClass = AcademicClass::create([
                    'name' => $source->name,
                    'grade_level' => $source->grade_level,
                    'stream' => $source->stream,
                    'section' => $source->section,
                    'section_id' => $source->section_id,
                    'academic_year' => $data['target_year'],
                    'class_teacher_id' => null,
                    'room_number' => null,
                    'capacity' => $source->capacity,
                ]);
                $createdClasses++;
                $classMap[$source->id] = $newClass->id;
                $subjectMap = [];

                foreach (AcademicSubject::query()->where('class_id', $source->id)->orderBy('id')->get() as $subject) {
                    $newSubject = AcademicSubject::create([
                        'class_id' => $newClass->id,
                        'subject_master_id' => $subject->subject_master_id,
                        'name' => $subject->name,
                        'code' => $subject->code,
                        'teacher_id' => null,
                        'weekly_periods' => $subject->weekly_periods,
                    ]);
                    $subjectMap[$subject->id] = $newSubject->id;
                    $createdSubjects++;
                }

                foreach (DB::table('school_fee_structures')->where('class_id', $source->id)->where('academic_year', $data['source_year'])->get() as $fee) {
                    DB::table('school_fee_structures')->insert([
                        'class_id' => $newClass->id, 'fee_title' => $fee->fee_title, 'fee_type' => $fee->fee_type,
                        'amount' => $fee->amount, 'due_frequency' => $fee->due_frequency, 'academic_year' => $data['target_year'],
                        'description' => $fee->description, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $createdFees++;
                }

                foreach (DB::table('school_period_timetables')->where('class_id', $source->id)->get() as $period) {
                    DB::table('school_period_timetables')->insert([
                        'class_id' => $newClass->id, 'subject_id' => $period->subject_id ? ($subjectMap[$period->subject_id] ?? null) : null,
                        'teacher_id' => null, 'day_of_week' => $period->day_of_week, 'period_number' => $period->period_number,
                        'start_time' => $period->start_time, 'end_time' => $period->end_time, 'room_number' => null,
                        'is_break' => $period->is_break, 'title' => $period->title, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $createdTimetableRows++;
                }
            }

            $createdTerms = 0;
            foreach (\App\Models\SchoolExamTerm::where('academic_year', $data['source_year'])->get() as $term) {
                $newTerm = \App\Models\SchoolExamTerm::create([
                    'name' => $term->name, 'academic_year' => $data['target_year'],
                    'start_date' => Carbon::parse($term->start_date)->addYear()->toDateString(),
                    'end_date' => Carbon::parse($term->end_date)->addYear()->toDateString(),
                    'status' => 'upcoming', 'description' => $term->description,
                ]);
                foreach (DB::table('school_exam_schedules')->where('exam_term_id', $term->id)->get() as $schedule) {
                    $newClassId = $classMap[$schedule->class_id] ?? null;
                    if (!$newClassId) continue;
                    $newSubjectId = DB::table('academic_subjects')->where('class_id', $newClassId)->where('name', DB::table('academic_subjects')->where('id', $schedule->subject_id)->value('name'))->value('id');
                    if (!$newSubjectId) continue;
                    DB::table('school_exam_schedules')->insert([
                        'exam_term_id' => $newTerm->id, 'class_id' => $newClassId, 'subject_id' => $newSubjectId,
                        'exam_date' => Carbon::parse($schedule->exam_date)->addYear()->toDateString(), 'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time, 'room_number' => null, 'max_marks' => $schedule->max_marks,
                        'pass_marks' => $schedule->pass_marks, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $createdTerms++;
            }

            Log::notice('Academic session structure cloned.', [
                'actor_id' => $actor->id, 'source_year' => $data['source_year'],
                'target_year' => $data['target_year'], 'classes_created' => $createdClasses,
                'subjects_created' => $createdSubjects, 'fee_structures_created' => $createdFees,
                'timetable_rows_created' => $createdTimetableRows, 'exam_terms_created' => $createdTerms,
            ]);
            return compact('createdClasses', 'createdSubjects', 'createdFees', 'createdTimetableRows', 'createdTerms');
        });

        return redirect()->route('school.academic-years.index')->with('success',
            "Created {$result['createdClasses']} classes, {$result['createdSubjects']} subjects, {$result['createdFees']} fee structures, {$result['createdTimetableRows']} timetable rows and {$result['createdTerms']} exam terms for {$data['target_year']}. Teacher assignments and room allocations were cleared; student records, marks, report cards and payments were not copied. Review all dates, teachers and fees before activation.");
    }

    public function activate(Request $request): RedirectResponse
    {
        $actor = $this->authorizeAdmin($request);
        $data = $request->validate(['academic_year' => ['required', 'string', 'max:20']]);
        $hasClasses = AcademicClass::query()->where('academic_year', $data['academic_year'])->exists();
        if (!$hasClasses) {
            throw ValidationException::withMessages(['academic_year' => 'Create or configure classes for this session before activating it.']);
        }

        $previous = AcademySetting::get('academic_session');
        DB::transaction(function () use ($data) {
            AcademySetting::set('academic_session', $data['academic_year']);
        });
        Log::notice('Active academic session changed.', [
            'actor_id' => $actor->id, 'previous_session' => $previous,
            'new_session' => $data['academic_year'],
        ]);

        return redirect()->route('school.academic-years.index')->with('success',
            "Academic session {$data['academic_year']} is now active. Review class teachers, subject teachers, timetables, fees and calendar settings before the first school day.");
    }
}
