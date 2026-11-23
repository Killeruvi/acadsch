<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\BloomAssignment;
use App\Models\ClassEnrollment;
use App\Models\CurriculumTopic;
use App\Models\DailyLesson;
use App\Models\TopicBloomActivity;
use App\Models\User;
use App\Services\BloomsAssignmentService;
use App\Services\CsvService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BloomsController extends Controller
{
    public function __construct(
        protected BloomsAssignmentService $bloomsService,
        protected CsvService $csvService
    ) {}

    protected function authorizeFaculty(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            abort(403, 'Unauthorized. Faculty credentials required.');
        }
    }

    protected function authorizeLessonManagement(DailyLesson $lesson): void
    {
        $user = Auth::user();
        if (! $user || $user->isAdmin()) {
            return;
        }

        $ownsLesson = (int) $lesson->teacher_id === (int) $user->id
            || (int) ($lesson->subject?->teacher_id ?? 0) === (int) $user->id
            || (int) ($lesson->academicClass?->class_teacher_id ?? 0) === (int) $user->id;

        abort_unless($user->isTeacher() && $ownsLesson, 403, 'You are not assigned to manage this lesson.');
    }

    protected function deleteBloomAttachment(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('private')->delete($path);
        // Clean up legacy public files during replacement/removal.
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Main Bloom's Taxonomy Assignment Dashboard (Role tailored).
     */
    public function index(): View
    {
        $user = Auth::user();
        $user->recordDailyActivity('login');

        if ($user->isStudent()) {
            // Student View
            $baseQuery = BloomAssignment::where('student_id', $user->id);
            $allAssignments = $baseQuery->get(['bloom_level', 'status']);

            // Progress tracking: count assignments across all 6 Bloom's levels
            $levelStats = [];
            for ($l = 1; $l <= 6; $l++) {
                $meta = TopicBloomActivity::getLevelMetadata($l);
                $count = $allAssignments->where('bloom_level', $l)->count();
                $completed = $allAssignments->where('bloom_level', $l)->whereIn('status', ['submitted', 'reviewed'])->count();
                $levelStats[$l] = [
                    'level' => $l,
                    'name' => $meta['name'],
                    'badge_color' => $meta['badge_color'],
                    'accent_color' => $meta['accent_color'],
                    'total_assigned' => $count,
                    'completed' => $completed,
                ];
            }

            $myAssignments = BloomAssignment::where('student_id', $user->id)
                ->with(['lesson.topic', 'lesson.subject', 'lesson.academicClass', 'lesson.teacher'])
                ->latest('created_at')
                ->paginate(8)
                ->withQueryString();

            return view('blooms.student', compact('myAssignments', 'levelStats'));
        }

        // Teacher & Admin View
        $classes = AcademicClass::with(['subjects.topics'])->orderBy('grade_level')->get();

        $query = DailyLesson::with([
            'academicClass',
            'subject',
            'topic',
            'teacher:id,name',
            'assignments.student:id,name',
        ]);

        if ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
        }

        $lessons = $query->latest('lesson_date')->latest('id')->paginate(10);

        // Pre-mapped topics with Bloom's activities
        $topicsWithBloom = CurriculumTopic::has('bloomActivities')
            ->with(['subject.academicClass', 'bloomActivities'])
            ->get();

        // Pending reviews count
        $pendingReviewsCount = BloomAssignment::where('status', 'submitted')
            ->when($user->isTeacher(), function ($q) use ($user) {
                $q->whereHas('lesson', function ($lq) use ($user) {
                    $lq->where('teacher_id', $user->id);
                });
            })
            ->count();

        return view('blooms.teacher', compact('classes', 'lessons', 'topicsWithBloom', 'pendingReviewsCount'));
    }

    /**
     * Store new daily lesson record & trigger roll-number Bloom's level distribution.
     */
    public function storeLesson(Request $request): RedirectResponse
    {
        $this->authorizeFaculty();

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'topic_id' => 'required|exists:curriculum_topics,id',
            'subtopic' => 'nullable|string|max:255',
            'lesson_date' => 'required|date',
            'distribution_mode' => 'required|in:balanced_random,uniform_level,roll_range',
            'target_level' => 'nullable|integer|min:1|max:6',
            'lesson_notes' => 'nullable|string',
        ]);

        $validated['teacher_id'] = Auth::id();

        // Create the daily lesson
        $lesson = DailyLesson::create($validated);

        // Run the Bloom's Taxonomy roll-number assignment engine
        $targetLevel = ! empty($validated['target_level']) ? (int) $validated['target_level'] : null;
        $assignments = $this->bloomsService->distributeAssignments($lesson, $validated['distribution_mode'], $targetLevel);

        NotificationService::notifyClass(
            $lesson->class_id,
            'bloom',
            "Bloom's Level Assigned: {$lesson->topic->title}",
            "Today's lesson has been recorded. Check your assigned Bloom's cognitive level (Level 1–6)!",
            route('blooms.index'),
            '⚡'
        );

        return redirect()->route('blooms.lesson', $lesson->id)
            ->with('success', "Daily lesson recorded! Successfully distributed Bloom's assignments to {$assignments->count()} students by roll number.");
    }

    /**
     * View lesson overview with detailed roll-number distribution table.
     */
    public function showLesson(int $id, Request $request): View
    {
        $lesson = DailyLesson::with([
            'academicClass.enrollments.student',
            'subject',
            'topic.bloomActivities',
            'teacher',
            'assignments.student',
            'assignments.reviewer',
        ])->findOrFail($id);

        $selectedLevel = $request->query('level');
        $selectedStatus = $request->query('status');
        $searchQuery = $request->query('search');

        // Filter assignments if requested
        $assignmentsQuery = $lesson->assignments();
        if ($selectedLevel && is_numeric($selectedLevel)) {
            $assignmentsQuery->where('bloom_level', (int) $selectedLevel);
        }
        if ($selectedStatus) {
            $assignmentsQuery->where('status', $selectedStatus);
        }
        if ($searchQuery) {
            $assignmentsQuery->where(function ($q) use ($searchQuery) {
                $q->where('activity_prompt', 'like', "%{$searchQuery}%")
                    ->orWhereHas('student', function ($sq) use ($searchQuery) {
                        $sq->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('student_id_number', 'like', "%{$searchQuery}%");
                    });
            });
        }
        $filteredAssignments = $assignmentsQuery->with(['student', 'reviewer'])->orderBy('roll_number')->paginate(12)->withQueryString();

        // Level distribution breakdown for this lesson (computed across all assignments)
        $breakdown = [];
        for ($i = 1; $i <= 6; $i++) {
            $meta = TopicBloomActivity::getLevelMetadata($i);
            $assignmentsForLevel = $lesson->assignments->where('bloom_level', $i);
            $breakdown[$i] = [
                'level' => $i,
                'name' => $meta['name'],
                'badge_color' => $meta['badge_color'],
                'count' => $assignmentsForLevel->count(),
                'students' => $assignmentsForLevel->pluck('student.name')->toArray(),
            ];
        }

        // Available students in the class for re-assignment / custom assignment
        $enrolledStudents = $lesson->academicClass->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->orderBy('roll_number')
            ->get();

        return view('blooms.lesson', compact(
            'lesson',
            'breakdown',
            'filteredAssignments',
            'enrolledStudents',
            'selectedLevel',
            'selectedStatus',
            'searchQuery'
        ));
    }

    /**
     * Delete a recorded daily lesson and all its assigned Bloom tasks.
     */
    public function destroyLesson(int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $user = Auth::user();

        $lesson = DailyLesson::with(['topic', 'assignments'])->findOrFail($id);

        if (! $user->isAdmin() && $lesson->teacher_id !== $user->id) {
            abort(403, 'Unauthorized to delete lessons created by another faculty member.');
        }

        $title = $lesson->topic->title ?? 'Daily Lesson';

        $lesson->assignments()->delete();
        $lesson->delete();

        return redirect()->route('blooms.index')
            ->with('success', "Daily Bloom lesson '{$title}' and its student assignments deleted successfully.");
    }

    /**
     * Download sample CSV template for importing Bloom's tasks.
     */
    public function sampleTasksCsv(): StreamedResponse
    {
        $headers = ['bloom_level', 'level_name', 'task_prompt', 'rubric_guide', 'due_date', 'roll_number'];
        $sampleData = [
            ['1', 'Remember', 'Define the first law of thermodynamics and list three everyday examples where it applies.', 'Include accurate textbook definitions and at least three clear real-world examples.', date('Y-m-d', strtotime('+3 days')), ''],
            ['2', 'Understand', 'Explain in your own words how cellular respiration and photosynthesis complement each other in an ecosystem.', 'Clearly contrast the input and output compounds with energy transfer explanation.', date('Y-m-d', strtotime('+3 days')), ''],
            ['3', 'Apply', 'Using the quadratic equation, calculate the trajectory apex and time-of-flight for a projectile launched at 45 degrees.', 'Show step-by-step mathematical working and unit conversions.', date('Y-m-d', strtotime('+4 days')), ''],
            ['4', 'Analyze', 'Differentiate between fiscal policy and monetary policy. Construct a comparative table of tools used by central banks versus treasuries.', 'Provide tabular analysis highlighting interest rates, taxation, and reserve ratios.', date('Y-m-d', strtotime('+5 days')), ''],
            ['5', 'Evaluate', 'Critique the efficiency and environmental footprint of solar photovoltaic vs wind turbine energy in semi-arid climates. Defend your choice.', 'Provide qualitative and quantitative justifications with counter-arguments.', date('Y-m-d', strtotime('+5 days')), ''],
            ['6', 'Create', 'Design an autonomous irrigation controller circuit blueprint with moisture threshold sensors and an emergency shutoff valve.', 'Provide functional architectural block diagram, component list, and operating logic.', date('Y-m-d', strtotime('+7 days')), ''],
        ];

        return $this->csvService->downloadCsv('blooms_tasks_import_template.csv', $headers, $sampleData);
    }

    /**
     * Import custom tasks from CSV and randomly assign them across class students.
     */
    public function importTasksCsv(Request $request, ?int $lessonId = null): RedirectResponse
    {
        $targetLessonId = $lessonId ?? $request->input('lesson_id');

        $request->validate([
            'lesson_id' => $targetLessonId ? 'nullable' : 'required|exists:daily_lessons,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
            'assignment_mode' => 'nullable|in:random,sequential',
            'clear_existing' => 'nullable|boolean',
        ]);

        $resolvedLessonId = $targetLessonId ?: $request->input('lesson_id');
        $lesson = DailyLesson::with('academicClass.enrollments')->findOrFail($resolvedLessonId);

        $parsedRows = $this->csvService->parseCsv($request->file('csv_file')->getPathname());
        if (empty($parsedRows)) {
            return back()->with('error', 'The uploaded CSV file is empty or formatted incorrectly.');
        }

        $assignmentMode = $request->input('assignment_mode', 'random');
        $clearExisting = $request->boolean('clear_existing', true);

        $assignments = $this->bloomsService->importAndAssignTasks(
            $lesson,
            $parsedRows,
            $assignmentMode,
            $clearExisting
        );

        if ($assignments->isEmpty()) {
            return back()->with('error', 'No valid tasks found in the CSV, or no active students enrolled in this class.');
        }

        NotificationService::notifyClass(
            $lesson->class_id,
            'bloom',
            "New Bloom's Tasks Imported & Assigned: {$lesson->topic->title}",
            "Your teacher has imported custom cognitive challenges for today's lesson. Check your assigned task!",
            route('blooms.lesson', $lesson->id),
            '🎯'
        );

        return redirect()->route('blooms.lesson', $lesson->id)
            ->with('success', 'Successfully imported '.count($parsedRows)." tasks and randomly distributed {$assignments->count()} assignments across students!");
    }

    /**
     * Export all lesson assignments and responses to CSV.
     */
    public function exportLessonCsv(int $id): StreamedResponse
    {
        $lesson = DailyLesson::with(['academicClass', 'subject', 'topic', 'assignments.student', 'assignments.reviewer'])->findOrFail($id);

        $headers = [
            'Roll Number',
            'Student Name',
            'Student Email',
            'Student ID',
            'Bloom Level',
            'Level Name',
            'Activity Prompt',
            'Rubric Guide',
            'Due Date',
            'Status',
            'Score',
            'Submitted At',
            'Student Submission',
            'Teacher Feedback',
        ];

        $data = [];
        foreach ($lesson->assignments->sortBy('roll_number') as $asg) {
            $data[] = [
                $asg->roll_number,
                $asg->student->name ?? 'Unknown',
                $asg->student->email ?? '',
                $asg->student->student_id_number ?? '',
                $asg->bloom_level,
                $asg->level_name,
                $asg->activity_prompt,
                $asg->rubric_guide ?? '',
                $asg->due_date?->format('Y-m-d') ?? '',
                $asg->status,
                $asg->score !== null ? $asg->score : '',
                $asg->submitted_at?->format('Y-m-d H:i') ?? '',
                $asg->submission_content ?? '',
                $asg->teacher_feedback ?? '',
            ];
        }

        $cleanTopic = Str::slug($lesson->topic->title);
        $filename = "Blooms_Lesson_{$lesson->id}_{$cleanTopic}_Assignments.csv";

        return $this->csvService->downloadCsv($filename, $headers, $data);
    }

    /**
     * Edit / Update an assigned Bloom's task.
     */
    public function updateAssignment(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::with(['student', 'lesson.academicClass', 'lesson.subject'])->findOrFail($id);
        abort_unless($assignment->lesson, 404);
        $this->authorizeLessonManagement($assignment->lesson);

        $validated = $request->validate([
            'bloom_level' => 'required|integer|min:1|max:6',
            'level_name' => 'nullable|string|max:100',
            'activity_prompt' => 'required|string|min:5',
            'rubric_guide' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:assigned,submitted,reviewed,revision_requested',
            'score' => 'nullable|numeric|min:0|max:100',
            'teacher_feedback' => 'nullable|string',
            'teacher_attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
            'remove_teacher_attachment' => 'nullable|boolean',
        ]);

        $level = (int) $validated['bloom_level'];
        $meta = TopicBloomActivity::getLevelMetadata($level);
        $validated['level_name'] = ! empty($validated['level_name']) ? $validated['level_name'] : $meta['name'];

        if (isset($validated['score']) && $validated['score'] !== '') {
            $validated['reviewed_by'] = Auth::id();
            $validated['reviewed_at'] = now();
            if (empty($validated['status']) || $validated['status'] === 'assigned') {
                $validated['status'] = 'reviewed';
            }
        }

        if ($request->boolean('remove_teacher_attachment')) {
            $this->deleteBloomAttachment($assignment->teacher_attachment_path);
            $validated['teacher_attachment_path'] = null;
            $validated['teacher_attachment_name'] = null;
            $validated['teacher_attachment_type'] = null;
            $validated['teacher_attachment_size'] = null;
        } elseif ($request->hasFile('teacher_attachment')) {
            $file = $request->file('teacher_attachment');
            $newPath = $file->store('blooms/reference_materials', 'private');
            if (! $newPath) {
                abort(500, 'The attachment could not be stored.');
            }
            $this->deleteBloomAttachment($assignment->teacher_attachment_path);
            $validated['teacher_attachment_path'] = $newPath;
            $validated['teacher_attachment_name'] = mb_substr(basename($file->getClientOriginalName()), 0, 180);
            $validated['teacher_attachment_type'] = strtolower($file->getClientOriginalExtension());
            $validated['teacher_attachment_size'] = $file->getSize();
        }

        $assignment->update($validated);

        return back()->with('success', "Assignment updated successfully for {$assignment->student->name} (Level {$assignment->bloom_level}: {$assignment->level_name})!");
    }

    /**
     * Re-assign an assignment to a different enrolled student.
     */
    public function reassignStudent(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::with(['lesson.academicClass', 'lesson.subject'])->findOrFail($id);
        abort_unless($assignment->lesson, 404);
        $this->authorizeLessonManagement($assignment->lesson);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $targetStudentId = (int) $validated['student_id'];

        // Verify target student is enrolled in the lesson's class
        $enrollment = ClassEnrollment::where('class_id', $assignment->lesson->class_id)
            ->where('student_id', $targetStudentId)
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Selected student is not enrolled in this class.');
        }

        $newStudent = User::findOrFail($targetStudentId);

        $this->deleteBloomAttachment($assignment->submission_attachment_path);
        $assignment->update([
            'student_id' => $newStudent->id,
            'roll_number' => $enrollment->roll_number,
            'submission_content' => null,
            'submission_attachment_path' => null,
            'submission_attachment_name' => null,
            'submission_attachment_type' => null,
            'submission_attachment_size' => null,
            'submitted_at' => null,
            'score' => null,
            'teacher_feedback' => null,
            'status' => 'assigned',
        ]);

        NotificationService::notifyUser(
            $newStudent->id,
            'bloom',
            "Bloom's Task Assigned to You: Level {$assignment->bloom_level}",
            "You have been assigned a Bloom's cognitive task for {$assignment->lesson->topic->title}.",
            route('blooms.index'),
            '⚡'
        );

        return back()->with('success', "Task successfully re-assigned to {$newStudent->name} (Roll #{$enrollment->roll_number})!");
    }

    /**
     * Reroll assignment: randomly generates or assigns a different Bloom's cognitive level for this student.
     */
    public function rerollAssignment(int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::with(['lesson.topic', 'student'])->findOrFail($id);

        $this->bloomsService->rerollAssignment($assignment);

        return back()->with('success', "Rerolled task for {$assignment->student->name}! New level: Level {$assignment->bloom_level} ({$assignment->level_name}).");
    }

    /**
     * Randomly reshuffle and re-distribute all tasks across students for this lesson.
     */
    public function reshuffleLesson(int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $lesson = DailyLesson::with('academicClass.enrollments')->findOrFail($id);

        $assignments = $this->bloomsService->reshuffleAssignments($lesson);

        NotificationService::notifyClass(
            $lesson->class_id,
            'bloom',
            "Tasks Reshuffled: {$lesson->topic->title}",
            "Bloom's taxonomy tasks have been randomly reshuffled across your class.",
            route('blooms.index'),
            '🎲'
        );

        return back()->with('success', "Successfully reshuffled and randomly re-assigned {$assignments->count()} Bloom's tasks across class students!");
    }

    /**
     * Request revision: teacher asks student to revise and improve their submission.
     */
    public function requestRevision(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::with('student', 'lesson')->findOrFail($id);

        $validated = $request->validate([
            'feedback' => 'required|string|min:3',
        ]);

        $assignment->update([
            'status' => 'revision_requested',
            'teacher_feedback' => $validated['feedback'],
        ]);

        NotificationService::notifyUser(
            $assignment->student_id,
            'bloom',
            "Revision Requested: Level {$assignment->bloom_level} {$assignment->level_name}",
            "Your teacher requested a revision on your Bloom's submission: \"{$validated['feedback']}\"",
            route('blooms.index'),
            '↩️'
        );

        return back()->with('success', "Revision requested for {$assignment->student->name}. Student has been notified.");
    }

    /**
     * Manually add a custom task for a student in this lesson.
     */
    public function storeCustomAssignment(Request $request, int $lessonId): RedirectResponse
    {
        $this->authorizeFaculty();
        $lesson = DailyLesson::with(['academicClass', 'subject'])->findOrFail($lessonId);
        $this->authorizeLessonManagement($lesson);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'bloom_level' => 'required|integer|min:1|max:6',
            'activity_prompt' => 'required|string|min:5',
            'rubric_guide' => 'nullable|string',
            'due_date' => 'nullable|date',
            'teacher_attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
        ]);

        $enrollment = ClassEnrollment::where('class_id', $lesson->class_id)
            ->where('student_id', $validated['student_id'])
            ->first();

        $meta = TopicBloomActivity::getLevelMetadata((int) $validated['bloom_level']);

        $teacherAttachmentPath = null;
        $teacherAttachmentName = null;
        $teacherAttachmentType = null;
        $teacherAttachmentSize = null;

        if ($request->hasFile('teacher_attachment')) {
            $file = $request->file('teacher_attachment');
            $teacherAttachmentPath = $file->store('blooms/reference_materials', 'private');
            abort_unless($teacherAttachmentPath, 500, 'The attachment could not be stored.');
            $teacherAttachmentName = mb_substr(basename($file->getClientOriginalName()), 0, 180);
            $teacherAttachmentType = strtolower($file->getClientOriginalExtension());
            $teacherAttachmentSize = $file->getSize();
        }

        $assignment = BloomAssignment::create([
            'lesson_id' => $lesson->id,
            'student_id' => $validated['student_id'],
            'roll_number' => $enrollment?->roll_number ?? 0,
            'bloom_level' => $validated['bloom_level'],
            'level_name' => $meta['name'],
            'activity_prompt' => $validated['activity_prompt'],
            'rubric_guide' => $validated['rubric_guide'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'teacher_attachment_path' => $teacherAttachmentPath,
            'teacher_attachment_name' => $teacherAttachmentName,
            'teacher_attachment_type' => $teacherAttachmentType,
            'teacher_attachment_size' => $teacherAttachmentSize,
            'status' => 'assigned',
        ]);

        $student = User::find($validated['student_id']);
        NotificationService::notifyUser(
            $assignment->student_id,
            'bloom',
            "New Bloom's Task: Level {$assignment->bloom_level} ({$meta['name']})",
            "A custom Bloom's activity has been assigned to you for {$lesson->topic->title}.",
            route('blooms.index'),
            '⚡'
        );

        return back()->with('success', "Custom task created and assigned to {$student->name} (Level {$assignment->bloom_level})!");
    }

    /**
     * Delete an assignment.
     */
    public function destroyAssignment(int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::with(['lesson.academicClass', 'lesson.subject'])->findOrFail($id);
        abort_unless($assignment->lesson, 404);
        $this->authorizeLessonManagement($assignment->lesson);
        $studentName = $assignment->student->name ?? 'Student';
        $level = $assignment->bloom_level;
        $this->deleteBloomAttachment($assignment->teacher_attachment_path);
        $this->deleteBloomAttachment($assignment->submission_attachment_path);
        $assignment->delete();

        return back()->with('success', "Assignment for {$studentName} (Level {$level}) removed.");
    }

    /**
     * Student submits or resubmits their Bloom's activity response (with support for written text and attachments like PDF, PPT, DOC).
     */
    public function submitAssignment(Request $request, int $id): RedirectResponse
    {
        $assignment = BloomAssignment::findOrFail($id);
        $user = Auth::user();

        if (! $user || ! $user->isStudent() || (int) $assignment->student_id !== (int) $user->id) {
            abort(403, 'Unauthorized. Students can only submit their own assigned activities.');
        }

        $validated = $request->validate([
            'submission_content' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
        ]);

        $hasText = ! empty(trim((string) ($validated['submission_content'] ?? '')));
        $hasFile = $request->hasFile('attachment');
        $hasExistingAttachment = ! empty($assignment->submission_attachment_path);

        if (! $hasText && ! $hasFile && ! $hasExistingAttachment) {
            return back()->withErrors([
                'submission_content' => 'Please provide written notes or attach a document (PDF, PowerPoint slides, Word document, etc.).',
            ])->withInput();
        }

        $isRevision = $assignment->status === 'revision_requested';

        $data = [
            'submitted_at' => now(),
            'status' => 'submitted',
        ];

        if ($hasText) {
            $data['submission_content'] = $validated['submission_content'];
        }

        if ($hasFile) {
            // Delete previously uploaded attachment if present
            $file = $request->file('attachment');
            $newPath = $file->store('blooms/submissions', 'private');
            if (! $newPath) {
                abort(500, 'The attachment could not be stored.');
            }
            $this->deleteBloomAttachment($assignment->submission_attachment_path);
            $data['submission_attachment_path'] = $newPath;
            $data['submission_attachment_name'] = mb_substr(basename($file->getClientOriginalName()), 0, 180);
            $data['submission_attachment_type'] = strtolower($file->getClientOriginalExtension());
            $data['submission_attachment_size'] = $file->getSize();
        }

        $assignment->update($data);

        // Award XP and maintain daily streak
        Auth::user()->recordDailyActivity('bloom_submission');

        $msg = $isRevision
            ? 'Your revised Bloom\'s response has been resubmitted! Teacher will review.'
            : 'Your Bloom\'s level activity has been submitted! +75 Academic XP awarded.';

        return back()->with('success', $msg);
    }

    /**
     * Download or stream Bloom's assignment attachment (submission or teacher reference material).
     */
    public function downloadAttachment(Request $request, int $id, string $type = 'submission')
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(in_array($type, ['submission', 'teacher'], true), 404);

        $assignment = BloomAssignment::with(['student', 'lesson.academicClass', 'lesson.subject'])->findOrFail($id);
        $lesson = $assignment->lesson;
        abort_unless($lesson, 404);
        $classId = (int) $lesson->class_id;
        $student = $assignment->student;

        $teacherOwnsLesson = $user->isTeacher() && (
            (int) $lesson->teacher_id === (int) $user->id
            || (int) ($lesson->subject?->teacher_id ?? 0) === (int) $user->id
            || (int) ($lesson->academicClass?->class_teacher_id ?? 0) === (int) $user->id
        );

        if ($type === 'submission') {
            $allowed = $user->isAdmin()
                || ($user->isStudent() && (int) $assignment->student_id === (int) $user->id)
                || $teacherOwnsLesson
                || ($user->isParent() && $user->isGuardianOf($student));
        } else {
            // Reference material is visible to the lesson's assigned class, its linked
            // parents, the responsible teacher, and administrators only.
            $allowed = $user->isAdmin() || $teacherOwnsLesson;
            if ($user->isStudent()) {
                $allowed = ((int) ($user->current_class_id ?? $user->batch?->class_id ?? 0) === $classId)
                    || $user->classEnrollments()->where('class_id', $classId)->whereIn('status', ['active', 'enrolled'])->exists();
            } elseif ($user->isParent()) {
                $allowed = User::childrenOf($user)->where(function ($q) use ($classId) {
                    $q->where('current_class_id', $classId)
                        ->orWhereHas('classEnrollments', fn ($e) => $e->where('class_id', $classId)->whereIn('status', ['active', 'enrolled']));
                })->exists();
            }
        }

        abort_unless($allowed, 403, 'You are not authorized to download this attachment.');

        $path = $type === 'teacher' ? $assignment->teacher_attachment_path : $assignment->submission_attachment_path;
        $name = $type === 'teacher'
            ? ($assignment->teacher_attachment_name ?? 'bloom_assignment_material.'.($assignment->teacher_attachment_type ?? 'pdf'))
            : ($assignment->submission_attachment_name ?? 'bloom_submission.'.($assignment->submission_attachment_type ?? 'pdf'));

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404, 'The requested attachment document is not available or has been removed.');
        }

        return Storage::disk('private')->download($path, basename($name));
    }

    /**
     * Download sample CSV for student enrollment roster.
     */
    public function sampleStudentCsv(): StreamedResponse
    {
        $headers = ['roll_number', 'name', 'email'];
        $sampleData = [
            ['1', 'Aarav Sharma', 'aarav.sharma@primearc.academy'],
            ['2', 'Ananya Patel', 'ananya.patel@primearc.academy'],
            ['3', 'Rohan Gupta', 'rohan.gupta@primearc.academy'],
            ['4', 'Diya Iyer', 'diya.iyer@primearc.academy'],
            ['5', 'Vikramaditya Verma', 'vikram.verma@primearc.academy'],
        ];

        return $this->csvService->downloadCsv('student_roster_template.csv', $headers, $sampleData);
    }

    /**
     * Export student roll list for a given class.
     */
    public function exportStudentsCsv(int $classId): StreamedResponse
    {
        $class = AcademicClass::with(['enrollments.student'])->findOrFail($classId);

        $headers = ['Roll Number', 'Student Name', 'Email', 'Student ID', 'Status'];
        $data = [];

        foreach ($class->enrollments as $enr) {
            $data[] = [
                $enr->roll_number,
                $enr->student->name,
                $enr->student->email,
                $enr->student->student_id_number ?? ('STU-'.str_pad($enr->student->id, 5, '0', STR_PAD_LEFT)),
                $enr->status,
            ];
        }

        $filename = "{$class->name}_Student_Roster.csv";

        return $this->csvService->downloadCsv($filename, $headers, $data);
    }

    /**
     * Import student roll list for a given class via CSV.
     */
    public function importStudentsCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $class = AcademicClass::findOrFail($request->input('class_id'));
        $rows = $this->csvService->parseCsv($request->file('csv_file')->getPathname());

        if (empty($rows)) {
            return back()->with('error', 'The uploaded CSV file is empty or formatted incorrectly.');
        }

        $enrolledCount = 0;
        foreach ($rows as $index => $row) {
            $name = $row['name'] ?? ($row['student_name'] ?? null);
            $email = $row['email'] ?? ($row['student_email'] ?? null);
            $rollNumber = isset($row['roll_number']) ? (int) $row['roll_number'] : ($index + 1);

            if (! $name || ! $email) {
                continue;
            }

            // Find or create student user
            $newAdmissionNumber = User::generateNextAdmissionNumber();
            $student = User::firstOrCreate(
                ['email' => strtolower(trim($email))],
                [
                    'name' => trim($name),
                    'password' => Hash::make(Str::random(64)),
                    'role' => 'student',
                    'admission_number' => $newAdmissionNumber,
                    'student_id_number' => $newAdmissionNumber,
                    'xp_points' => 0,
                    'academic_tier' => $class->display_name,
                    'is_approved' => false,
                ]
            );

            $academicYear = trim((string) $class->academic_year);
            if ($academicYear === '') {
                throw ValidationException::withMessages([
                    'file' => 'The selected class has no configured academic session.',
                ]);
            }
            $activeEnrollment = ClassEnrollment::query()->where('student_id', $student->id)
                ->where('academic_year', $academicYear)->where('status', 'active')
                ->lockForUpdate()->first();
            if ($activeEnrollment && (int) $activeEnrollment->class_id !== (int) $class->id) {
                throw ValidationException::withMessages([
                    'file' => "{$student->name} is already enrolled in another class for {$academicYear}. Use the class transfer workflow.",
                ]);
            }

            $requestedRoll = $rollNumber > 0 ? $rollNumber : null;
            if ($requestedRoll !== null && ! ClassEnrollment::isRollNumberAvailable($class->id, $requestedRoll, $student->id, $academicYear)) {
                throw ValidationException::withMessages([
                    'file' => "Roll number {$requestedRoll} is already assigned in this class and session.",
                ]);
            }
            $assignedRoll = $requestedRoll ?? ($activeEnrollment?->roll_number ?: ClassEnrollment::generateNextRollNumber($class->id, $student->id, $academicYear));

            if ($activeEnrollment) {
                $activeEnrollment->update(['roll_number' => $assignedRoll]);
            } else {
                ClassEnrollment::create([
                    'class_id' => $class->id, 'student_id' => $student->id,
                    'academic_year' => $academicYear, 'roll_number' => $assignedRoll,
                    'admission_number' => $student->admission_number, 'status' => 'active',
                ]);
            }
            $student->forceFill(['current_class_id' => $class->id, 'roll_number' => $assignedRoll])->save();

            $enrolledCount++;
        }

        return redirect()->route('blooms.index')
            ->with('success', "Successfully enrolled and mapped {$enrolledCount} students to {$class->name} via CSV!");
    }

    /**
     * Teacher grades and reviews a student's Bloom's submission.
     */
    public function gradeAssignment(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $assignment = BloomAssignment::findOrFail($id);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'teacher_feedback' => 'nullable|string',
        ]);

        $assignment->update([
            'score' => $validated['score'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'status' => 'reviewed',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        // Award review XP to student if passed
        if ($assignment->student) {
            $assignment->student->increment('xp_points', 25);

            NotificationService::notifyUser(
                $assignment->student_id,
                'bloom',
                "Bloom's Submission Evaluated: {$assignment->level_name}",
                "Your Level {$assignment->bloom_level} submission received a score of {$validated['score']}/100 (+25 XP).",
                route('blooms.index'),
                '🎯'
            );
        }

        return back()->with('success', "Evaluation recorded for {$assignment->student->name} (Level {$assignment->bloom_level}: {$assignment->level_name}).");
    }

    /**
     * AJAX helper to fetch subjects and topics for a chosen class.
     */
    public function getCurriculumData(int $classId): JsonResponse
    {
        $subjects = AcademicSubject::where('class_id', $classId)
            ->with(['topics.bloomActivities'])
            ->get();

        return response()->json(['subjects' => $subjects]);
    }
}
