<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicClass;
use App\Models\AcademicHoliday;
use App\Models\AcademicSubject;
use App\Models\AcademySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\SchoolExamGradeScale;
use App\Models\SchoolExamMark;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolReportCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolExaminationController extends Controller
{
    /**
     * Examination Control Hub & Term List (Scoped per role).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $termId = $request->input('term_id');
        $terms = SchoolExamTerm::withCount(['schedules', 'marks', 'reportCards'])
            ->latest('start_date')
            ->get();

        $currentTerm = $termId ? $terms->firstWhere('id', $termId) : $terms->first();

        $classesQuery = AcademicClass::with('subjects')->orderBy('grade_level')->orderBy('section');
        if ($user->isTeacher()) {
            $classesQuery->where(function ($query) use ($user) {
                $query->where('class_teacher_id', $user->id)
                    ->orWhereHas('subjects', fn ($subjectQuery) => $subjectQuery->where('teacher_id', $user->id));
            });
        } elseif ($user->isStudent()) {
            $classesQuery->whereKey($user->current_class_id ?? 0);
        } elseif ($user->isParent()) {
            $childrenForClasses = User::childrenOf($user)->pluck('current_class_id')->filter();
            $classesQuery->whereIn('id', $childrenForClasses);
        }
        $classes = $classesQuery->get();

        $schedules = collect();
        $reportCards = collect();

        if ($currentTerm) {
            $schedulesQuery = SchoolExamSchedule::where('exam_term_id', $currentTerm->id)
                ->with(['academicClass', 'subject'])
                ->orderBy('exam_date')
                ->orderBy('start_time');

            $reportCardsQuery = SchoolReportCard::where('exam_term_id', $currentTerm->id)
                ->with(['student', 'academicClass'])
                ->orderBy('percentage', 'desc');

            if ($user->isStudent()) {
                if ($user->current_class_id) {
                    $schedulesQuery->where('class_id', $user->current_class_id);
                } else {
                    $schedulesQuery->whereRaw('1 = 0');
                }
                $reportCardsQuery->where('student_id', $user->id)->where('is_published', true);
            } elseif ($user->isParent()) {
                $children = User::childrenOf($user)
                    ->with('currentClass')
                    ->get();

                $selectedChildId = $request->input('child_id', session('parent_active_student_id', $children->first()?->id));
                if ($selectedChildId && $children->contains('id', (int) $selectedChildId)) {
                    session(['parent_active_student_id' => (int) $selectedChildId]);
                } else {
                    $selectedChildId = $children->first()?->id;
                    session(['parent_active_student_id' => $selectedChildId]);
                }
                $activeChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();

                if ($activeChild) {
                    if ($activeChild->current_class_id) {
                        $schedulesQuery->where('class_id', $activeChild->current_class_id);
                    }
                    $reportCardsQuery->where('student_id', $activeChild->id)->where('is_published', true);
                } else {
                    $childIds = $children->pluck('id');
                    $childClassIds = $children->pluck('current_class_id')->filter();
                    if ($childClassIds->isNotEmpty()) {
                        $schedulesQuery->whereIn('class_id', $childClassIds);
                    }
                    $reportCardsQuery->whereIn('student_id', $childIds)->where('is_published', true);
                }
            }

            if ($user->isTeacher()) {
                $assignedIds = $classes->pluck('id');
                $schedulesQuery->whereIn('class_id', $assignedIds);
                $reportCardsQuery->whereIn('class_id', $assignedIds);
            }
            $schedules = $schedulesQuery->paginate(12, ['*'], 'schedules_page')->withQueryString();
            $reportCards = $reportCardsQuery->paginate(15)->withQueryString();
        } else {
            $schedules = new LengthAwarePaginator([], 0, 12, 1, ['pageName' => 'schedules_page']);
            $reportCards = new LengthAwarePaginator([], 0, 15, 1);
        }

        $scopeClassIds = $classes->pluck('id');
        $stats = [
            'total_terms' => $terms->count(),
            'total_schedules' => SchoolExamSchedule::when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count(),
            'total_marks' => SchoolExamMark::when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count(),
            'total_report_cards' => SchoolReportCard::when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count(),
        ];

        // Exam Readiness Checklist (Rule 2)
        $totalClassesCount = $classes->count();
        $totalSubjectsCount = AcademicSubject::count();
        $scheduledCount = $currentTerm ? SchoolExamSchedule::where('exam_term_id', $currentTerm->id)->when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count() : 0;
        $marksCount = $currentTerm ? SchoolExamMark::where('exam_term_id', $currentTerm->id)->when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count() : 0;
        $reportCardsCount = $currentTerm ? SchoolReportCard::where('exam_term_id', $currentTerm->id)->where('is_published', true)->when(! $user->isAdmin(), fn ($q) => $q->whereIn('class_id', $scopeClassIds))->count() : 0;

        $readiness = [
            'term_defined' => $currentTerm !== null,
            'term_name' => $currentTerm?->name ?? 'None',
            'schedules_ready' => $scheduledCount > 0,
            'scheduled_count' => $scheduledCount,
            'marks_ready' => $marksCount > 0,
            'marks_count' => $marksCount,
            'report_cards_ready' => $reportCardsCount > 0,
            'report_cards_count' => $reportCardsCount,
            'percentage' => $currentTerm ? min(100, round((
                ($currentTerm ? 25 : 0) +
                ($scheduledCount > 0 ? 25 : 0) +
                ($marksCount > 0 ? 25 : 0) +
                ($reportCardsCount > 0 ? 25 : 0)
            ))) : 0,
        ];

        $children = $user->isParent() ? ($children ?? collect()) : collect();
        $activeChild = $user->isParent() ? ($activeChild ?? null) : null;

        $gradeScales = $user->isAdmin()
            ? SchoolExamGradeScale::orderBy('sort_order')->get()
            : collect();

        return view('school.exams.index', compact('terms', 'currentTerm', 'classes', 'schedules', 'reportCards', 'stats', 'readiness', 'children', 'activeChild', 'gradeScales'));
    }

    /**
     * Store new Exam Term.
     */
    public function storeTerm(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'academic_year' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:upcoming,ongoing,completed',
            'description' => 'nullable|string|max:500',
        ]);
        $activeSession = AcademySetting::get('academic_session');
        if (empty($validated['academic_year']) && ! $activeSession) {
            return back()->withInput()->withErrors(['academic_year' => 'Configure the active academic session first.']);
        }
        $validated['academic_year'] = $validated['academic_year'] ?? $activeSession;
        $overlap = SchoolExamTerm::where('academic_year', $validated['academic_year'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])->exists();
        abort_if($overlap, 422, 'An examination term already overlaps these dates in this academic year.');
        $term = SchoolExamTerm::create($validated);
        Log::info('Examination term created', ['actor_id' => $actor->id, 'term_id' => $term->id]);

        return redirect()->route('school.exams.index', ['term_id' => $term->id])
            ->with('success', 'Examination term created.');
    }

    /**
     * Delete Exam Term.
     */
    public function updateTerm(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $term = SchoolExamTerm::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'academic_year' => 'required|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:upcoming,ongoing,completed',
            'description' => 'nullable|string|max:500',
        ]);
        $outOfRangeSchedule = $term->schedules()
            ->where(function ($query) use ($validated) {
                $query->whereDate('exam_date', '<', $validated['start_date'])
                    ->orWhereDate('exam_date', '>', $validated['end_date']);
            })->exists();
        abort_if($outOfRangeSchedule, 409, 'The revised term dates would exclude existing exam schedules.');
        if ($term->marks()->exists() || $term->reportCards()->exists()) {
            abort_if($validated['academic_year'] !== $term->academic_year, 409,
                'Academic year cannot be changed after marks or report cards exist.');
        }
        $overlap = SchoolExamTerm::where('id', '!=', $term->id)
            ->where('academic_year', $validated['academic_year'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])->exists();
        abort_if($overlap, 422, 'Another examination term overlaps these dates in this academic year.');
        $term->update($validated);
        Log::warning('Examination term updated', ['actor_id' => $actor->id, 'term_id' => $term->id]);

        return back()->with('success', 'Examination term updated.');
    }

    public function destroyTerm(int $id): RedirectResponse
    {
        $actor = request()->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $term = SchoolExamTerm::findOrFail($id);
        abort_if($term->schedules()->exists() || $term->marks()->exists() || $term->reportCards()->exists(),
            409, 'This examination term has academic records and cannot be deleted. Mark it completed instead.');
        $term->delete();
        Log::warning('Examination term deleted', ['actor_id' => $actor->id, 'term_id' => $id]);

        return redirect()->route('school.exams.index')->with('success', 'Empty examination term deleted.');
    }

    /**
     * Store exam schedule slot (datesheet).
     */
    public function storeSchedule(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_number' => 'nullable|string|max:50',
            'max_marks' => 'required|numeric|min:0.01|max:9999.99',
            'pass_marks' => 'required|numeric|min:0|lte:max_marks',
        ]);
        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        abort_unless($validated['exam_date'] >= $term->start_date->toDateString()
            && $validated['exam_date'] <= $term->end_date->toDateString(), 422,
            'Exam date must fall within the examination term.');
        $subjectBelongs = AcademicSubject::whereKey($validated['subject_id'])
            ->where('class_id', $validated['class_id'])->exists();
        abort_unless($subjectBelongs, 422, 'Subject does not belong to the selected class.');
        $holiday = AcademicHoliday::findHolidayForDate($validated['exam_date']);
        abort_if($holiday && $holiday->is_instructional_off, 422, 'Examinations cannot be scheduled on an institutional holiday.');
        $existingSchedule = SchoolExamSchedule::where('exam_term_id', $term->id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])->first();
        $room = trim((string) ($validated['room_number'] ?? ''));
        if ($room !== '') {
            $collision = SchoolExamSchedule::whereDate('exam_date', $validated['exam_date'])
                ->when($existingSchedule, fn ($query) => $query->where('id', '!=', $existingSchedule->id))
                ->where('room_number', $room)
                ->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                })->exists();
            abort_if($collision, 409, 'The selected room is already allocated during this time.');
        }
        $sameClassCollision = SchoolExamSchedule::whereDate('exam_date', $validated['exam_date'])
            ->when($existingSchedule, fn ($query) => $query->where('id', '!=', $existingSchedule->id))
            ->where('class_id', $validated['class_id'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })->exists();
        abort_if($sameClassCollision, 409, 'This class already has an examination scheduled during this time.');

        $subjectTeacherId = AcademicSubject::whereKey($validated['subject_id'])->value('teacher_id');
        if ($subjectTeacherId) {
            $teacherCollision = SchoolExamSchedule::query()
                ->join('academic_subjects', 'academic_subjects.id', '=', 'school_exam_schedules.subject_id')
                ->where('academic_subjects.teacher_id', $subjectTeacherId)
                ->whereDate('school_exam_schedules.exam_date', $validated['exam_date'])
                ->when($existingSchedule, fn ($query) => $query->where('school_exam_schedules.id', '!=', $existingSchedule->id))
                ->where('school_exam_schedules.start_time', '<', $validated['end_time'])
                ->where('school_exam_schedules.end_time', '>', $validated['start_time'])
                ->exists();
            abort_if($teacherCollision, 409, 'The assigned subject teacher already has an examination during this time.');
        }

        $schedule = $existingSchedule;
        if ($schedule && SchoolExamMark::where('exam_term_id', $term->id)
            ->where('class_id', $validated['class_id'])->where('subject_id', $validated['subject_id'])->exists()) {
            abort(409, 'A schedule with recorded marks cannot be overwritten. Use the approved schedule amendment process.');
        }
        SchoolExamSchedule::updateOrCreate(
            ['exam_term_id' => $term->id, 'class_id' => $validated['class_id'], 'subject_id' => $validated['subject_id']],
            $validated
        );
        Log::info('Exam schedule saved', ['actor_id' => $actor->id, 'term_id' => $term->id,
            'class_id' => $validated['class_id'], 'subject_id' => $validated['subject_id']]);

        return back()->with('success', 'Examination schedule saved.');
    }

    /**
     * Delete schedule slot.
     */
    public function destroySchedule(int $id): RedirectResponse
    {
        $actor = request()->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $schedule = SchoolExamSchedule::findOrFail($id);
        abort_if(SchoolExamMark::where('exam_term_id', $schedule->exam_term_id)
            ->where('class_id', $schedule->class_id)->where('subject_id', $schedule->subject_id)->exists(),
            409, 'This schedule has marks recorded and cannot be deleted.');
        $schedule->delete();
        Log::warning('Exam schedule deleted', ['actor_id' => $actor->id, 'schedule_id' => $id]);

        return back()->with('success', 'Examination schedule removed.');
    }

    /**
     * Auto-assign exam schedule from class subjects.
     */
    public function autoAssignSchedules(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);

        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
        ]);

        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        $class = AcademicClass::with('subjects')->findOrFail($validated['class_id']);

        $assignedCount = 0;
        $currentDate = Carbon::parse($term->start_date);
        $termEnd = Carbon::parse($term->end_date);

        DB::transaction(function () use ($term, $class, &$assignedCount, $currentDate, $termEnd) {
            foreach ($class->subjects as $subject) {
                $exists = SchoolExamSchedule::where('exam_term_id', $term->id)
                    ->where('class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                while ($currentDate->lte($termEnd)) {
                    $isWeekend = in_array($currentDate->format('l'), config('attendance.weekend_days', ['Sunday']), true);
                    $holiday = AcademicHoliday::findHolidayForDate($currentDate);
                    if (! $isWeekend && (! $holiday || ! $holiday->is_instructional_off)) {
                        break;
                    }
                    $currentDate->addDay();
                }

                $examDate = $currentDate->lte($termEnd) ? $currentDate->toDateString() : $term->start_date->toDateString();

                SchoolExamSchedule::create([
                    'exam_term_id' => $term->id,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'exam_date' => $examDate,
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'room_number' => $class->room_number ?? 'Exam Hall A',
                    'max_marks' => 100.00,
                    'pass_marks' => 35.00,
                ]);

                $assignedCount++;
                $currentDate->addDay();
            }
        });

        return back()->with('success', "Auto-assigned {$assignedCount} subject exam schedules for {$class->display_name}.");
    }
    // Administrators must create schedules from verified examination planning data.

    /**
     * Bulk Import Marks from CSV file.
     * Rule 2: Bulk marks import (CSV/Excel).
     */
    public function bulkImportMarks(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isAdmin() || $actor->isTeacher()), 403);
        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'marks_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);
        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        $class = AcademicClass::with('students')->findOrFail($validated['class_id']);
        $subject = AcademicSubject::findOrFail($validated['subject_id']);
        abort_unless((int) $subject->class_id === (int) $class->id, 422);
        $this->authorizeExamClass($actor, $class, $subject);
        abort_if($term->status === 'completed', 409, 'Completed examination marks are locked.');
        $schedule = SchoolExamSchedule::where('exam_term_id', $term->id)
            ->where('class_id', $class->id)->where('subject_id', $subject->id)->firstOrFail();

        $handle = fopen($request->file('marks_file')->getRealPath(), 'r');
        abort_if($handle === false, 422, 'Unable to read the uploaded CSV file.');
        try {
            $header = fgetcsv($handle);
            abort_if(! $header, 422, 'CSV file is empty.');
            $headerMap = [];
            foreach ($header as $index => $column) {
                $headerMap[strtolower(trim((string) $column))] = $index;
            }
            abort_unless(isset($headerMap['admission_number']) || isset($headerMap['roll_number']), 422,
                'CSV must contain admission_number or roll_number.');
            $marksColumn = $headerMap['marks_obtained'] ?? $headerMap['marks'] ?? null;
            abort_if($marksColumn === null, 422, 'CSV must contain marks_obtained.');
            $students = $class->students->keyBy(fn ($student) => (string) $student->admission_number);
            $rollStudents = $class->students->keyBy(fn ($student) => (string) ($student->pivot?->roll_number ?? $student->roll_number));
            $rows = [];
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (! array_filter($row, fn ($v) => trim((string) $v) !== '')) {
                    continue;
                }
                $admission = isset($headerMap['admission_number']) ? trim((string) ($row[$headerMap['admission_number']] ?? '')) : '';
                $roll = isset($headerMap['roll_number']) ? trim((string) ($row[$headerMap['roll_number']] ?? '')) : '';
                $student = ($admission !== '' ? $students->get($admission) : null)
                    ?? ($roll !== '' ? $rollStudents->get($roll) : null);
                if (! $student) {
                    throw ValidationException::withMessages(['marks_file' => "CSV row {$line}: student is not enrolled in the selected class."]);
                }
                $raw = trim((string) ($row[$marksColumn] ?? ''));
                if ($raw === '' || ! is_numeric($raw)) {
                    throw ValidationException::withMessages(['marks_file' => "CSV row {$line}: marks must be numeric."]);
                }
                $obtained = (float) $raw;
                if ($obtained < 0 || $obtained > (float) $schedule->max_marks) {
                    throw ValidationException::withMessages(['marks_file' => "CSV row {$line}: marks are outside the permitted range."]);
                }
                $rows[] = [
                    'student_id' => $student->id,
                    'marks_obtained' => $obtained,
                    'remarks' => isset($headerMap['remarks']) ? trim((string) ($row[$headerMap['remarks']] ?? '')) : null,
                ];
            }
            abort_if(! $rows, 422, 'CSV contains no valid marks rows.');
            abort_if(count(array_unique(array_column($rows, 'student_id'))) !== count($rows), 422,
                'CSV contains duplicate student rows.');
        } finally {
            fclose($handle);
        }

        DB::transaction(function () use ($rows, $term, $class, $subject, $schedule, $actor) {
            foreach ($rows as $row) {
                $published = SchoolReportCard::where('exam_term_id', $term->id)
                    ->where('class_id', $class->id)->where('student_id', $row['student_id'])
                    ->where('is_published', true)->exists();
                abort_if($published, 409, 'Published results are locked. Use re-evaluation.');
                SchoolExamMark::updateOrCreate(
                    ['exam_term_id' => $term->id, 'class_id' => $class->id,
                        'subject_id' => $subject->id, 'student_id' => $row['student_id']],
                    ['marks_obtained' => $row['marks_obtained'], 'max_marks' => $schedule->max_marks,
                        'pass_marks' => $schedule->pass_marks,
                        'letter_grade' => SchoolExamMark::calculateGrade($row['marks_obtained'], (float) $schedule->max_marks),
                        'teacher_remarks' => $row['remarks']]
                );
            }
            Log::info('Exam marks CSV imported', ['actor_id' => $actor->id, 'term_id' => $term->id,
                'class_id' => $class->id, 'subject_id' => $subject->id, 'row_count' => count($rows)]);
        });

        return back()->with('success', 'CSV marks imported successfully.');
    }

    /**
     * Re-evaluation / mark correction flow with audit.
     * Rule 2: Re-evaluation/correction flow with audit.
     */
    public function reEvaluateMark(Request $request, int $markId): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $mark = SchoolExamMark::with(['student', 'subject', 'academicClass', 'examTerm'])->findOrFail($markId);
        $validated = $request->validate([
            'revised_marks' => ['required', 'numeric', 'min:0', 'max:'.$mark->max_marks],
            'reason' => 'required|string|min:10|max:1000',
        ]);
        $newValue = (float) $validated['revised_marks'];
        DB::transaction(function () use ($mark, $validated, $newValue, $actor) {
            $locked = SchoolExamMark::whereKey($mark->id)->lockForUpdate()->firstOrFail();
            $oldValue = (float) $locked->marks_obtained;
            DB::table('school_exam_mark_revisions')->insert([
                'exam_mark_id' => $locked->id,
                'old_marks' => $oldValue,
                'new_marks' => $newValue,
                'reason' => $validated['reason'],
                'changed_by' => $actor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $remarks = trim(($locked->teacher_remarks ? $locked->teacher_remarks.' | ' : '').'Re-evaluation: '.$validated['reason']);
            $locked->update([
                'marks_obtained' => $newValue,
                'letter_grade' => SchoolExamMark::calculateGrade($newValue, (float) $locked->max_marks),
                'teacher_remarks' => $remarks,
            ]);
            SchoolReportCard::where('exam_term_id', $locked->exam_term_id)
                ->where('class_id', $locked->class_id)
                ->where('student_id', $locked->student_id)
                ->update(['is_published' => false]);
            Log::warning('Exam mark re-evaluated', [
                'actor_id' => $actor->id, 'exam_mark_id' => $locked->id,
                'old_marks' => $oldValue, 'new_marks' => $newValue,
            ]);
        });

        return back()->with('success', 'Re-evaluation saved with an immutable revision record. Regenerate the draft report card for review.');
    }

    /**
     * Marks Entry Interface (Term + Class + Subject).
     * Strictly restricted to Faculty & Administrators.
     */
    public function storeGradeScale(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $validated = $request->validate([
            'grade_code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9+_-]+$/', 'unique:school_exam_grade_scales,grade_code'],
            'minimum_percentage' => 'required|numeric|min:0|max:100|unique:school_exam_grade_scales,minimum_percentage',
            'label' => 'required|string|max:80',
            'is_passing' => 'required|boolean',
            'sort_order' => 'required|integer|min:0|max:999',
        ]);
        SchoolExamGradeScale::create($validated + ['is_active' => true]);
        Log::info('Exam grade scale created', ['actor_id' => $request->user()->id, 'grade_code' => $validated['grade_code']]);

        return back()->with('success', 'Grading scale added.');
    }

    public function updateGradeScale(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $scale = SchoolExamGradeScale::findOrFail($id);
        $validated = $request->validate([
            'grade_code' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9+_-]+$/', 'unique:school_exam_grade_scales,grade_code,'.$id],
            'minimum_percentage' => 'required|numeric|min:0|max:100|unique:school_exam_grade_scales,minimum_percentage,'.$id,
            'label' => 'required|string|max:80',
            'is_passing' => 'required|boolean',
            'sort_order' => 'required|integer|min:0|max:999',
            'is_active' => 'required|boolean',
        ]);
        $scale->update($validated);
        Log::warning('Exam grade scale updated', ['actor_id' => $request->user()->id, 'grade_scale_id' => $id]);

        return back()->with('success', 'Grading scale updated.');
    }

    public function destroyGradeScale(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $scale = SchoolExamGradeScale::findOrFail($id);
        abort_if(SchoolExamMark::where('letter_grade', $scale->grade_code)->exists(), 409,
            'This grade is used by recorded marks. Deactivate it instead of deleting it.');
        $scale->delete();
        Log::warning('Exam grade scale deleted', ['actor_id' => $request->user()->id, 'grade_scale_id' => $id]);

        return back()->with('success', 'Unused grading scale deleted.');
    }

    public function publishReportCards(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
        ]);
        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        abort_if($term->status !== 'completed', 409, 'Only completed examination terms can be published.');
        $drafts = SchoolReportCard::where('exam_term_id', $term->id)
            ->where('class_id', $validated['class_id'])->where('is_published', false)->get();
        abort_if($drafts->isEmpty(), 422, 'No draft report cards are available for publication.');
        abort_if($drafts->contains(fn ($card) => $card->overall_grade === 'PENDING'), 409,
            'One or more report cards are incomplete.');
        DB::transaction(function () use ($drafts, $actor) {
            foreach ($drafts as $card) {
                $card->update(['is_published' => true]);
            }
            Log::warning('Report cards published', [
                'actor_id' => $actor->id, 'report_card_ids' => $drafts->pluck('id')->all(),
            ]);
        });

        return back()->with('success', 'Reviewed report cards published.');
    }

    public function marksEntry(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isTeacher()), 403);

        $terms = SchoolExamTerm::whereIn('status', ['upcoming', 'ongoing'])
            ->orderByDesc('start_date')->get();
        $classesQuery = AcademicClass::with('subjects')->orderBy('grade_level')->orderBy('section');
        if ($user->isTeacher()) {
            $classesQuery->where(function ($q) use ($user) {
                $q->where('class_teacher_id', $user->id)
                    ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $user->id));
            });
        }
        $classes = $classesQuery->get();

        $termId = $request->integer('term_id') ?: $terms->first()?->id;
        $classId = $request->integer('class_id') ?: $classes->first()?->id;
        $selectedClass = $classes->firstWhere('id', $classId);
        abort_if($classId && ! $selectedClass, 403, 'You are not assigned to this class.');

        $subjects = $selectedClass ? $selectedClass->subjects : collect();
        if ($user->isTeacher() && $selectedClass) {
            $subjects = $subjects->filter(fn ($subject) => (int) $subject->teacher_id === (int) $user->id);
        }
        $subjectId = $request->integer('subject_id') ?: $subjects->first()?->id;
        $selectedSubject = $subjects->firstWhere('id', $subjectId);
        abort_if($subjectId && ! $selectedSubject, 403, 'You are not assigned to this subject.');

        $students = [];
        $schedule = null;
        if ($selectedClass && $termId && $selectedSubject) {
            $schedule = SchoolExamSchedule::where('exam_term_id', $termId)
                ->where('class_id', $selectedClass->id)
                ->where('subject_id', $selectedSubject->id)->first();
            if ($schedule) {
                $enrolled = $selectedClass->students()->orderBy('class_enrollments.roll_number')->get();
                $existingMarks = SchoolExamMark::where('exam_term_id', $termId)
                    ->where('class_id', $selectedClass->id)
                    ->where('subject_id', $selectedSubject->id)->get()->keyBy('student_id');
                foreach ($enrolled as $student) {
                    $mark = $existingMarks->get($student->id);
                    $students[] = [
                        'student' => $student,
                        'marks_obtained' => $mark?->marks_obtained,
                        'max_marks' => $schedule->max_marks,
                        'pass_marks' => $schedule->pass_marks,
                        'letter_grade' => $mark?->letter_grade,
                        'remarks' => $mark?->teacher_remarks ?? '',
                    ];
                }
            }
        }

        return view('school.exams.marks-entry', compact(
            'terms', 'classes', 'termId', 'classId', 'subjectId',
            'selectedClass', 'selectedSubject', 'schedule', 'students'
        ));
    }

    /**
     * Batch store exam marks.
     * Strictly restricted to Faculty & Administrators.
     */
    public function storeMarks(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isAdmin() || $actor->isTeacher()), 403);

        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
            'subject_id' => 'required|integer|exists:academic_subjects,id',
            'marks' => 'required|array|min:1',
            'marks.*.marks_obtained' => 'nullable|numeric|min:0',
            'marks.*.remarks' => 'nullable|string|max:255',
        ]);

        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        $class = AcademicClass::with('students')->findOrFail($validated['class_id']);
        $subject = AcademicSubject::findOrFail($validated['subject_id']);
        abort_unless((int) $subject->class_id === (int) $class->id, 422, 'Subject does not belong to the selected class.');

        $schedule = SchoolExamSchedule::where('exam_term_id', $term->id)
            ->where('class_id', $class->id)->where('subject_id', $subject->id)->firstOrFail();

        $this->authorizeExamClass($actor, $class, $subject);
        abort_if($term->status === 'completed', 409, 'Marks for a completed term are locked; use the approved re-evaluation workflow.');

        $enrolledIds = $class->students()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $submittedIds = array_map('intval', array_keys($validated['marks']));
        abort_if(array_diff($submittedIds, $enrolledIds), 422, 'Marks were submitted for a student not enrolled in this class.');

        DB::transaction(function () use ($validated, $term, $class, $subject, $schedule, $actor) {
            foreach ($validated['marks'] as $studentId => $data) {
                if (! array_key_exists('marks_obtained', $data) || $data['marks_obtained'] === null || $data['marks_obtained'] === '') {
                    continue;
                }
                $obtained = (float) $data['marks_obtained'];
                if ($obtained > (float) $schedule->max_marks) {
                    throw ValidationException::withMessages([
                        "marks.{$studentId}.marks_obtained" => 'Marks cannot exceed the scheduled maximum.',
                    ]);
                }
                $mark = SchoolExamMark::firstOrNew([
                    'exam_term_id' => $term->id, 'class_id' => $class->id,
                    'subject_id' => $subject->id, 'student_id' => (int) $studentId,
                ]);
                if ($mark->exists && SchoolReportCard::where('exam_term_id', $term->id)
                    ->where('class_id', $class->id)->where('student_id', (int) $studentId)
                    ->where('is_published', true)->exists()) {
                    throw ValidationException::withMessages([
                        "marks.{$studentId}.marks_obtained" => 'Published results are locked. Submit a re-evaluation request.',
                    ]);
                }
                $mark->fill([
                    'marks_obtained' => $obtained,
                    'max_marks' => $schedule->max_marks,
                    'pass_marks' => $schedule->pass_marks,
                    'letter_grade' => SchoolExamMark::calculateGrade($obtained, (float) $schedule->max_marks),
                    'teacher_remarks' => $data['remarks'] ?? null,
                ])->save();
            }
            Log::info('Exam marks batch saved', [
                'actor_id' => $actor->id, 'term_id' => $term->id,
                'class_id' => $class->id, 'subject_id' => $subject->id,
            ]);
        });

        return back()->with('success', 'Marks saved for the selected enrolled students.');
    }

    /**
     * Generate or recalculate Official Report Cards for a class.
     */
    public function generateReportCards(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $validated = $request->validate([
            'exam_term_id' => 'required|integer|exists:school_exam_terms,id',
            'class_id' => 'required|integer|exists:academic_classes,id',
        ]);
        $term = SchoolExamTerm::findOrFail($validated['exam_term_id']);
        abort_if($term->status !== 'completed', 409, 'Complete the examination term before generating final report cards.');
        $class = AcademicClass::with('students')->findOrFail($validated['class_id']);
        $students = $class->students;
        abort_if($students->isEmpty(), 422, 'No active enrolled students were found in this class.');

        $schedules = SchoolExamSchedule::where('exam_term_id', $term->id)
            ->where('class_id', $class->id)->get();
        abort_if($schedules->isEmpty(), 422, 'No examination schedules exist for this class and term.');
        $marks = SchoolExamMark::where('exam_term_id', $term->id)
            ->where('class_id', $class->id)->get()->groupBy('student_id');
        $scheduledSubjectIds = $schedules->pluck('subject_id')->map(fn ($id) => (int) $id)->all();

        $results = [];
        foreach ($students as $student) {
            $studentMarks = $marks->get($student->id, collect());
            $markedSubjectIds = $studentMarks->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
            $missing = array_diff($scheduledSubjectIds, $markedSubjectIds);
            if ($missing) {
                return back()->with('error', "Report cards cannot be generated: marks are missing for student {$student->admission_number} in one or more scheduled subjects.");
            }
            $totalMax = (float) $studentMarks->sum('max_marks');
            $totalObtained = (float) $studentMarks->sum('marks_obtained');
            abort_if($totalMax <= 0, 422, 'A student has no valid maximum-mark total.');
            $percentage = round(($totalObtained / $totalMax) * 100, 2);
            $failedSubject = $studentMarks->contains(fn ($mark) => (float) $mark->marks_obtained < (float) $mark->pass_marks);
            $gradeScale = SchoolExamGradeScale::query()
                ->where('is_active', true)
                ->where('minimum_percentage', '<=', $percentage)
                ->orderByDesc('minimum_percentage')
                ->first();
            abort_if(! $gradeScale, 422, 'No active grading scale covers a student percentage. Configure grading master data.');

            $termEnd = $term->end_date->toDateString();
            $sessionsQuery = AttendanceSession::where('class_id', $class->id)
                ->whereDate('session_date', '<=', $termEnd);
            if ($term->start_date) {
                $sessionsQuery->whereDate('session_date', '>=', $term->start_date->toDateString());
            }
            $totalSessions = $sessionsQuery->count();
            $daysPresent = AttendanceRecord::where('student_id', $student->id)
                ->whereHas('session', function ($query) use ($class, $term) {
                    $query->where('class_id', $class->id)
                        ->whereDate('session_date', '<=', $term->end_date->toDateString())
                        ->whereDate('session_date', '>=', $term->start_date->toDateString());
                })->whereIn('status', ['present', 'late', 'half_day'])->count();
            $daysPresent = min($daysPresent, $totalSessions);
            $attendanceAvailable = $totalSessions > 0;
            $attendancePercentage = $attendanceAvailable
                ? round(($daysPresent / $totalSessions) * 100, 2)
                : 0;

            $results[] = [
                'student_id' => $student->id,
                'total_max' => $totalMax,
                'total_obtained' => $totalObtained,
                'percentage' => $percentage,
                'gpa' => 0,
                'gpa_configured' => false,
                'overall_grade' => $gradeScale->grade_code,
                'result_status' => ($failedSubject || ! $gradeScale->is_passing) ? 'FAIL' : 'PASS',
                'total_working_days' => $totalSessions,
                'days_present' => $daysPresent,
                'attendance_percentage' => $attendancePercentage,
                'attendance_data_available' => $attendanceAvailable,
            ];
        }

        usort($results, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
        DB::transaction(function () use ($results, $term, $class, $actor) {
            $rank = 0;
            $lastPercentage = null;
            foreach ($results as $index => $result) {
                if ($lastPercentage === null || $result['percentage'] < $lastPercentage) {
                    $rank = $index + 1;
                    $lastPercentage = $result['percentage'];
                }
                $report = SchoolReportCard::firstOrNew([
                    'exam_term_id' => $term->id, 'class_id' => $class->id,
                    'student_id' => $result['student_id'],
                ]);
                if ($report->exists && $report->is_published) {
                    continue;
                }
                $report->fill([
                    'total_max_marks' => $result['total_max'],
                    'total_marks_obtained' => $result['total_obtained'],
                    'percentage' => $result['percentage'],
                    'gpa' => $result['gpa'],
                    'gpa_configured' => $result['gpa_configured'],
                    'overall_grade' => $result['overall_grade'],
                    'class_rank' => $rank,
                    'total_working_days' => $result['total_working_days'],
                    'days_present' => $result['days_present'],
                    'attendance_percentage' => $result['attendance_percentage'],
                    'attendance_data_available' => $result['attendance_data_available'],
                    'result_status' => $result['result_status'],
                    'teacher_remarks' => null,
                    'principal_remarks' => null,
                    'is_published' => false,
                    'generated_date' => now()->toDateString(),
                ])->save();
            }
            Log::info('Draft report cards generated', [
                'actor_id' => $actor->id, 'term_id' => $term->id,
                'class_id' => $class->id, 'student_count' => count($results),
            ]);
        });

        return redirect()->route('school.exams.index', ['term_id' => $term->id])
            ->with('success', 'Draft report cards generated from complete marks, configured grading scales and available attendance records. Review before publication.');
    }

    /**
     * Formal Printable Report Card View / Marksheet.
     */
    public function showReportCard(int $id): View
    {
        $actor = request()->user();
        abort_unless($actor, 401);

        $reportCard = SchoolReportCard::with([
            'examTerm',
            'academicClass.classTeacher',
            'student.parent',
        ])->findOrFail($id);

        $isFaculty = $actor->isAdmin() || $actor->isTeacher();
        $isOwnStudentRecord = $actor->isStudent() && (int) $reportCard->student_id === (int) $actor->id;
        $isOwnChildRecord = $actor->isGuardianOf($reportCard->student);

        abort_unless($isFaculty || $isOwnStudentRecord || $isOwnChildRecord, 403,
            'You are not authorized to view this report card.');

        if (! $isFaculty) {
            abort_unless($reportCard->is_published, 404,
                'This report card has not been published.');
        }

        if ($actor->isTeacher()) {
            $assignedClassIds = AcademicClass::query()
                ->where(function ($query) use ($actor) {
                    $query->where('class_teacher_id', $actor->id)
                        ->orWhereHas('subjects', fn ($subjectQuery) => $subjectQuery->where('teacher_id', $actor->id))
                        ->orWhereHas('timetables', fn ($timetableQuery) => $timetableQuery->where('teacher_id', $actor->id));
                })
                ->pluck('id')
                ->map(fn ($classId) => (int) $classId)
                ->all();

            abort_unless(in_array((int) $reportCard->class_id, $assignedClassIds, true), 403,
                'You may only view report cards for your assigned classes.');
        }

        $marks = SchoolExamMark::where('exam_term_id', $reportCard->exam_term_id)
            ->where('class_id', $reportCard->class_id)
            ->where('student_id', $reportCard->student_id)
            ->with(['subject', 'revisions.changedBy'])
            ->get();

        return view('school.exams.report-card', compact('reportCard', 'marks'));
    }

    /**
     * Enforce server-side teacher scope for exam operations.
     */
    private function authorizeExamClass(User $actor, AcademicClass $class, AcademicSubject $subject): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        abort_unless($actor->isTeacher(), 403);

        // A class-teacher assignment grants pastoral/classroom visibility, not
        // authority to alter every subject's examination marks. Only the
        // explicitly assigned subject teacher may enter/import marks for that
        // subject. Administrators retain institution-wide authority.
        $isAssignedSubjectTeacher = (int) $subject->teacher_id === (int) $actor->id;
        abort_unless($isAssignedSubjectTeacher, 403,
            'Only the teacher assigned to this subject may enter or import its examination marks.');

        abort_unless((int) $subject->class_id === (int) $class->id, 422,
            'The selected subject does not belong to this class.');
    }

    /**
     * Official Printable Examination Admit Card / Hall Ticket.
     */
    public function admitCard(Request $request, int $termId, ?int $studentId = null): View
    {
        $actor = $request->user();
        abort_unless($actor, 401);

        $term = SchoolExamTerm::findOrFail($termId);

        if ($studentId === null) {
            abort_unless($actor->isStudent(), 400, 'Student ID must be specified.');
            $studentId = $actor->id;
        }

        $student = User::where('role', 'student')->findOrFail($studentId);

        $isFaculty = $actor->isAdmin() || $actor->isTeacher();
        $isOwnRecord = $actor->isStudent() && (int) $actor->id === (int) $student->id;
        $isGuardian = $actor->isParent() && $actor->isGuardianOf($student);

        abort_unless($isFaculty || $isOwnRecord || $isGuardian, 403,
            'You are not authorized to view this admit card.');

        $class = $student->currentClass ?? $student->classEnrollments()->where('status', 'active')->first()?->academicClass;
        abort_unless($class, 422, 'The student does not have an active class enrollment.');

        if ($actor->isTeacher() && ! $actor->isAdmin()) {
            $assignedClassIds = AcademicClass::query()
                ->where(function ($query) use ($actor) {
                    $query->where('class_teacher_id', $actor->id)
                        ->orWhereHas('subjects', fn ($subjectQuery) => $subjectQuery->where('teacher_id', $actor->id))
                        ->orWhereHas('timetables', fn ($timetableQuery) => $timetableQuery->where('teacher_id', $actor->id));
                })
                ->pluck('id')
                ->map(fn ($classId) => (int) $classId)
                ->all();

            abort_unless(in_array((int) $class->id, $assignedClassIds, true), 403,
                'You may only view admit cards for students in your assigned classes.');
        }

        $schedules = SchoolExamSchedule::where('exam_term_id', $term->id)
            ->where('class_id', $class->id)
            ->with(['subject'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        $tokenPayload = [
            'term_id' => $term->id,
            'student_id' => $student->id,
            'sig' => hash_hmac('sha256', "admit:{$term->id}:{$student->id}:{$student->admission_number}", config('app.key')),
        ];
        $token = rtrim(strtr(base64_encode(json_encode($tokenPayload)), '+/', '-_'), '=');

        return view('school.exams.admit-card', compact('term', 'student', 'class', 'schedules', 'token'));
    }

    /**
     * QR Verification endpoint for Examination Admit Card / Hall Ticket.
     */
    public function verifyAdmitCard(string $token)
    {
        $base64 = strtr($token, '-_', '+/');
        $padding = strlen($base64) % 4;
        if ($padding) {
            $base64 .= str_repeat('=', 4 - $padding);
        }
        $decoded = json_decode(base64_decode($base64), true);

        if (! $decoded || ! isset($decoded['term_id'], $decoded['student_id'], $decoded['sig'])) {
            if (request()->wantsJson() || request()->is('api/*')) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid or corrupted admit card verification token.',
                ], 400);
            }
            abort(400, 'Invalid or corrupted admit card verification token.');
        }

        $term = SchoolExamTerm::find($decoded['term_id']);
        $student = User::where('role', 'student')->find($decoded['student_id']);

        if (! $term || ! $student) {
            if (request()->wantsJson() || request()->is('api/*')) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Record not found for verification token.',
                ], 404);
            }
            abort(404, 'Record not found for verification token.');
        }

        $expectedSig = hash_hmac('sha256', "admit:{$term->id}:{$student->id}:{$student->admission_number}", config('app.key'));
        if (! hash_equals($expectedSig, $decoded['sig'])) {
            if (request()->wantsJson() || request()->is('api/*')) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Admit card signature mismatch / counterfeit detected.',
                ], 403);
            }
            abort(403, 'Admit card signature mismatch / counterfeit detected.');
        }

        $class = $student->currentClass ?? $student->classEnrollments()->where('status', 'active')->first()?->academicClass;

        $verificationData = [
            'valid' => true,
            'status' => 'VERIFIED_GENUINE',
            'term_name' => $term->name,
            'academic_year' => $term->academic_year,
            'student_name' => $student->name,
            'admission_number' => $student->admission_number,
            'roll_number' => $student->roll_number,
            'class_name' => $class?->display_name ?? 'N/A',
            'verified_at' => now()->toIso8601String(),
        ];

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json($verificationData);
        }

        return view('school.exams.admit-card-verified', compact('verificationData', 'term', 'student', 'class'));
    }
}
