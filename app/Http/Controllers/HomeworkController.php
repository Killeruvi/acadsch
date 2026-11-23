<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeworkController extends Controller
{
    /**
     * Daily Homework Dashboard (Role Tailored with Form-Based Selection).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $user->recordDailyActivity('visit');

        $classQuery = AcademicClass::with('subjects')->orderBy('grade_level');
        if ($user->isStudent()) {
            $allowedClassIds = $user->classEnrollments()->where(function ($q) { $q->whereNull('status')->orWhereIn('status', ['active', 'enrolled']); })->pluck('class_id');
            if ($user->current_class_id) $allowedClassIds->push($user->current_class_id);
            $classQuery->whereIn('id', $allowedClassIds->unique());
        } elseif ($user->isTeacher()) {
            $teacherClassIds = AcademicSubject::where('teacher_id', $user->id)->pluck('class_id')
                ->merge(HomeworkTask::where('teacher_id', $user->id)->pluck('class_id'))->unique();
            $classQuery->whereIn('id', $teacherClassIds);
        } elseif ($user->isParent()) {
            $childIds = \App\Models\User::childrenOf($user)->pluck('id');
            $classIds = \App\Models\ClassEnrollment::whereIn('student_id', $childIds)->whereIn('status', ['active', 'enrolled'])->pluck('class_id');
            $classQuery->whereIn('id', $classIds->unique());
        }
        $allClasses = $classQuery->get();

        // Determine default grade level
        $defaultGrade = 8;
        if ($user->batch && $user->batch->academicClass) {
            $defaultGrade = $user->batch->academicClass->grade_level;
        } elseif ($user->isStudent() && $user->classEnrollments()->exists()) {
            $firstClass = $user->classEnrollments()->with('academicClass')->first()?->academicClass;
            if ($firstClass) {
                $defaultGrade = $firstClass->grade_level;
            }
        }

        $selectedGrade = (int) $request->query('grade', $request->query('class_id', $defaultGrade));
        $selectedClass = $allClasses->firstWhere('grade_level', $selectedGrade) 
            ?? $allClasses->firstWhere('id', $selectedGrade) 
            ?? $allClasses->first();

        // Available subjects for the chosen class
        $subjects = $selectedClass ? $selectedClass->subjects()->orderBy('name')->get() : collect();
        $selectedSubjectId = $request->query('subject_id');
        $selectedSubject = null;
        if ($selectedSubjectId && $selectedSubjectId !== 'all') {
            $selectedSubject = $subjects->firstWhere('id', (int)$selectedSubjectId);
        }

        $search = $request->query('search');

        $query = HomeworkTask::with(['academicClass', 'subject', 'teacher:id,name'])->withCount('submissions');
        if ($user->isTeacher()) $query->where('teacher_id', $user->id);
        if ($user->isAdmin() || $user->isTeacher()) $query->with(['submissions.student:id,name,email']);

        if ($selectedClass) {
            $query->where('class_id', $selectedClass->id);
        } elseif (!$user->isAdmin() && !$user->isTeacher()) {
            $query->whereRaw('1 = 0');
        }

        if ($selectedSubjectId && $selectedSubjectId !== 'all') {
            $query->where('subject_id', (int)$selectedSubjectId);
        }

        if ($search) {
            $s = strtolower($search);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(instructions) LIKE ?', ["%{$s}%"]);
            });
        }

        $homeworkList = $query->latest('assigned_date')->paginate(10)->withQueryString();

        $mySubmissions = [];
        if ($user->isStudent()) {
            $mySubmissions = HomeworkSubmission::where('student_id', $user->id)->whereIn('homework_id', $homeworkList->pluck('id'))->get()->keyBy('homework_id');
        } elseif ($user->isParent()) {
            $childIds = \App\Models\User::childrenOf($user)->pluck('id');
            $mySubmissions = HomeworkSubmission::whereIn('student_id', $childIds)->whereIn('homework_id', $homeworkList->pluck('id'))->get()->groupBy('homework_id');
        }

        return view('homework.index', compact(
            'homeworkList',
            'allClasses',
            'selectedClass',
            'selectedGrade',
            'subjects',
            'selectedSubject',
            'selectedSubjectId',
            'search',
            'mySubmissions'
        ));
    }

    /**
     * Teacher assigns new homework.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            abort(403, 'Unauthorized. Only teachers and administrators can assign homework.');
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'topic_id' => 'nullable|exists:curriculum_topics,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string|max:20000',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
        ]);

        abort_unless(AcademicSubject::whereKey($validated['subject_id'])->where('class_id', $validated['class_id'])->exists(), 422, 'The selected subject does not belong to the selected class.');
        if ($user->isTeacher()) abort_unless(AcademicSubject::whereKey($validated['subject_id'])->where('teacher_id', $user->id)->exists(), 403, 'You may only assign homework for subjects assigned to you.');
        if (!empty($validated['topic_id'])) abort_unless(\App\Models\CurriculumTopic::whereKey($validated['topic_id'])->where('subject_id', $validated['subject_id'])->exists(), 422, 'The selected topic does not belong to the selected subject.');

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;
        $attachmentSize = null;

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('homework/tasks', 'public');
            $attachmentName = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $attachmentType = strtolower($file->getClientOriginalExtension());
            $attachmentSize = $file->getSize();
        }

        $task = HomeworkTask::create([
            'class_id' => $validated['class_id'],
            'subject_id' => $validated['subject_id'],
            'topic_id' => $request->input('topic_id') ?: null,
            'teacher_id' => Auth::id(),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'assigned_date' => $validated['assigned_date'],
            'due_date' => $validated['due_date'],
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'attachment_size' => $attachmentSize,
        ]);

        NotificationService::notifyClass(
            $task->class_id,
            'homework',
            "New Daily Homework: {$task->title}",
            "Homework assigned for {$task->subject->name}. Due date: " . date('M d, Y', strtotime($task->due_date)),
            route('homework.index'),
            '📚'
        );

        return redirect()->route('homework.index', ['class_id' => $task->class_id])
            ->with('success', "Daily homework '{$task->title}' assigned to {$task->academicClass->name}!");
    }

    /**
     * Student submits completed homework.
     */
    public function submit(Request $request, int $id): RedirectResponse
    {
        $task = HomeworkTask::findOrFail($id);
        $user = Auth::user();

        if (!$user || !$user->isStudent()) {
            abort(403, 'Only students can submit homework.');
        }

        $isEnrolled = (int) $user->current_class_id === (int) $task->class_id
            || $user->classEnrollments()->where('class_id', $task->class_id)->whereIn('status', ['active', 'enrolled'])->exists();
        abort_unless($isEnrolled, 403, 'You are not enrolled in the class assigned to this homework.');
        abort_unless(now()->toDateString() >= $task->assigned_date->toDateString(), 403, 'This homework is not open for submissions yet.');

        $validated = $request->validate([
            'student_notes' => 'nullable|string|max:20000',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
        ]);

        $notes = trim((string)($validated['student_notes'] ?? ''));
        $hasFile = $request->hasFile('attachment') && $request->file('attachment')->isValid();

        if (empty($notes) && !$hasFile) {
            return back()->withErrors([
                'student_notes' => 'Please provide written response notes or attach a homework document/slides deck.',
            ]);
        }

        $existing = HomeworkSubmission::where('homework_id', $task->id)
            ->where('student_id', $user->id)
            ->first();

        $attachmentPath = $existing?->attachment_path;
        $attachmentName = $existing?->attachment_name;
        $attachmentType = $existing?->attachment_type;
        $attachmentSize = $existing?->attachment_size;

        if ($hasFile) {
            if ($existing && $existing->attachment_path) {
                foreach (['local', 'public'] as $diskName) if (Storage::disk($diskName)->exists($existing->attachment_path)) Storage::disk($diskName)->delete($existing->attachment_path);
            }

            $file = $request->file('attachment');
            $attachmentPath = $file->store('homework/submissions', 'local');
            $attachmentName = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $attachmentType = strtolower($file->getClientOriginalExtension());
            $attachmentSize = $file->getSize();
        }

        HomeworkSubmission::updateOrCreate(
            [
                'homework_id' => $task->id,
                'student_id' => $user->id,
            ],
            [
                'student_notes' => $notes ?: 'Attached file response.',
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_type' => $attachmentType,
                'attachment_size' => $attachmentSize,
                'status' => 'submitted',
                'teacher_feedback' => null,
                'checked_at' => null,
                'is_late' => now()->toDateString() > $task->due_date->toDateString(),
                'resubmission_count' => (int) ($existing?->resubmission_count ?? 0) + ($existing ? 1 : 0),
            ]
        );

        // XP is awarded only on the first successful submission, not on every resubmission.
        if (!$existing) $user->recordDailyActivity(20);

        return redirect()->route('homework.index')
            ->with('success', $existing ? 'Homework resubmitted successfully.' : 'Homework submitted successfully! +20 Academic XP awarded.');
    }

    /**
     * Download attachment for homework task or submission.
     */
    public function downloadAttachment(Request $request, int $id, string $type = 'task'): StreamedResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($type === 'task') {
            $task = HomeworkTask::findOrFail($id);
            if (!$task->hasAttachment()) abort(404, 'Homework task attachment not found.');
            $diskName = Storage::disk('local')->exists($task->attachment_path) ? 'local' : 'public';
            if (!Storage::disk($diskName)->exists($task->attachment_path)) abort(404, 'Homework task attachment not found.');
            $allowed = $user->isAdmin() || ($user->isTeacher() && (int) $task->teacher_id === (int) $user->id);
            if ($user->isStudent()) $allowed = (int) $user->current_class_id === (int) $task->class_id || $user->classEnrollments()->where('class_id', $task->class_id)->whereIn('status', ['active', 'enrolled'])->exists();
            if ($user->isParent()) {
                $childIds = \App\Models\User::childrenOf($user)->pluck('id');
                $allowed = \App\Models\ClassEnrollment::whereIn('student_id', $childIds)->where('class_id', $task->class_id)->exists();
            }
            abort_unless($allowed, 403);
            return Storage::disk($diskName)->download($task->attachment_path, $task->attachment_name);
        }

        if ($type === 'submission') {
            $submission = HomeworkSubmission::with('homework')->findOrFail($id);
            if (!$submission->hasAttachment()) abort(404, 'Homework submission attachment not found.');
            $diskName = Storage::disk('local')->exists($submission->attachment_path) ? 'local' : 'public';
            if (!Storage::disk($diskName)->exists($submission->attachment_path)) abort(404, 'Homework submission attachment not found.');

            // Authorization: own student record, linked parent, or the task's assigned teacher/admin.
            if ($user->isTeacher() && (int) $submission->homework->teacher_id !== (int) $user->id) abort(403);
            if (!$user->isStudent() && !$user->isParent() && !$user->isTeacher() && !$user->isAdmin()) abort(403);
            if ($user->isStudent() && (int) $user->id !== (int) $submission->student_id) {
                abort(403, 'Unauthorized. You may only download your own submitted homework files.');
            }

            if ($user->isParent()) {
                $isLinkedChild = (int) $submission->student->parent_id === (int) $user->id
                    || $user->isGuardianOf($submission->student);
                abort_unless($isLinkedChild, 403, 'Parents may only view homework submitted by their own children.');
            }

            if ($user->isAdmin() || ($user->isTeacher() && (int) $submission->homework->teacher_id === (int) $user->id) || ($user->isStudent() && (int) $user->id === (int) $submission->student_id) || $user->isParent()) {
                return Storage::disk($diskName)->download($submission->attachment_path, $submission->attachment_name);
            }
            abort(403);
        }

        abort(400, 'Invalid attachment type.');
    }

    /**
     * Teacher marks homework submission as checked.
     */
    public function check(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isTeacher())) {
            abort(403, 'Unauthorized. Only teachers and administrators can check homework.');
        }

        $submission = HomeworkSubmission::with(['homework.subject', 'student'])->findOrFail($id);
        abort_unless($user->isAdmin() || ($user->isTeacher() && (int) $submission->homework->teacher_id === (int) $user->id), 403);

        $validated = $request->validate([
            'status' => 'required|in:checked,incomplete,missing',
            'teacher_feedback' => 'nullable|string',
        ]);

        $submission->update([
            'status' => $validated['status'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'checked_at' => now(),
        ]);

        NotificationService::notifyUser(
            $submission->student_id,
            'homework',
            "Homework Verified: {$submission->homework->title}",
            "Your teacher verified your homework status: " . ucfirst($validated['status']),
            route('homework.index'),
            '✅'
        );

        return back()->with('success', "Homework for {$submission->student->name} marked as " . ucfirst($validated['status']) . ".");
    }

    /**
     * Update an existing homework task.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $task = HomeworkTask::findOrFail($id);
        if (!$user->isAdmin() && $task->teacher_id !== $user->id) {
            abort(403, 'Unauthorized to edit this homework task.');
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'subject_id' => 'required|exists:academic_subjects,id',
            'topic_id' => 'nullable|exists:curriculum_topics,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string|max:20000',
            'assigned_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assigned_date',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200',
        ]);

        abort_unless(AcademicSubject::whereKey($validated['subject_id'])->where('class_id', $validated['class_id'])->exists(), 422, 'The selected subject does not belong to the selected class.');
        if ($user->isTeacher()) abort_unless(AcademicSubject::whereKey($validated['subject_id'])->where('teacher_id', $user->id)->exists(), 403, 'You may only assign homework for subjects assigned to you.');
        if (!empty($validated['topic_id'])) abort_unless(\App\Models\CurriculumTopic::whereKey($validated['topic_id'])->where('subject_id', $validated['subject_id'])->exists(), 422, 'The selected topic does not belong to the selected subject.');

        $updateData = [
            'class_id' => $validated['class_id'],
            'subject_id' => $validated['subject_id'],
            'topic_id' => $request->input('topic_id') ?: null,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'assigned_date' => $validated['assigned_date'],
            'due_date' => $validated['due_date'],
        ];

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            if ($task->attachment_path) foreach (['local', 'public'] as $diskName) if (Storage::disk($diskName)->exists($task->attachment_path)) Storage::disk($diskName)->delete($task->attachment_path);
            $file = $request->file('attachment');
            $updateData['attachment_path'] = $file->store('homework/tasks', 'public');
            $updateData['attachment_name'] = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $updateData['attachment_type'] = strtolower($file->getClientOriginalExtension());
            $updateData['attachment_size'] = $file->getSize();
        }

        $task->update($updateData);

        return redirect()->route('homework.index')
            ->with('success', "Homework \"{$task->title}\" updated successfully.");
    }

    /**
     * Delete a homework task and its submissions.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $task = HomeworkTask::with('submissions')->findOrFail($id);
        if (!$user->isAdmin() && $task->teacher_id !== $user->id) {
            abort(403, 'Unauthorized to delete this homework task.');
        }

        $title = $task->title;

        if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        foreach ($task->submissions as $submission) {
            if ($submission->attachment_path) foreach (['local', 'public'] as $diskName) if (Storage::disk($diskName)->exists($submission->attachment_path)) Storage::disk($diskName)->delete($submission->attachment_path);
            $submission->delete();
        }

        $task->delete();

        return redirect()->route('homework.index')
            ->with('success', "Homework \"{$title}\" deleted successfully.");
    }
}
