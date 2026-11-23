<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Batch;
use App\Models\BatchChangeRequest;
use App\Models\CurriculumNote;
use App\Models\CurriculumTopic;
use App\Models\DailyDpp;
use App\Models\HomeworkTask;
use App\Models\User;
use App\Models\UserVideoProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BatchController extends Controller
{
    /**
     * Entry point for /my-batch.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Students cannot change class or batch: always redirect directly to their assigned batch
        if ($user->isStudent() && $user->batch_id) {
            return redirect()->route('batches.subjects', ['batch' => $user->batch_id]);
        }

        if ($request->has('select') && ($user->isTeacher() || $user->isAdmin())) {
            return redirect()->route('batches.select');
        }

        $batchId = $request->query('batch_id') ?? $user->batch_id;

        if ($batchId) {
            $batch = Batch::find($batchId);
            if ($batch) {
                if ($user->batch_id !== $batch->id && ($user->isTeacher() || $user->isAdmin())) {
                    $user->update(['batch_id' => $batch->id]);
                }
                return redirect()->route('batches.subjects', ['batch' => $batch->id]);
            }
        }

        // If student has no batch, check if there's any batch available in their enrolled class first
        if ($user->isStudent() && !$user->batch_id) {
            $enrolledClassId = $user->classEnrollments()->first()?->class_id;
            $classBatch = $enrolledClassId
                ? Batch::where('class_id', $enrolledClassId)->where('is_active', true)->first()
                : null;
            if ($classBatch) {
                $user->update(['batch_id' => $classBatch->id]);
                return redirect()->route('batches.subjects', ['batch' => $classBatch->id]);
            }
        }

        // If user has no batch, check if there's any batch available or go to selection
        $defaultBatch = Batch::where('is_active', true)->first();
        if ($defaultBatch && !$user->batch_id) {
            $user->update(['batch_id' => $defaultBatch->id]);
            return redirect()->route('batches.subjects', ['batch' => $defaultBatch->id]);
        }

        return redirect()->route('batches.select');
    }

    /**
     * Step 1: Form to select Class & view batches.
     * Permission: Teachers & Admins can freely switch classes and manage batches.
     * Students are locked to their enrolled class and can view shifts to submit a BatchChangeRequest.
     */
    public function selectClassBatch(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        $classes = AcademicClass::withCount('batches')->orderBy('grade_level')->get();

        // Determine active class: for students, strictly lock to their enrolled class
        if ($user->isStudent()) {
            $userBatch = $user->batch ?? ($user->batch_id ? Batch::find($user->batch_id) : null);
            $selectedClassId = $userBatch?->class_id ?? $user->classEnrollments()->first()?->class_id;
        } else {
            $selectedClassId = $request->query('class_id');
            if (!$selectedClassId && $user->batch_id) {
                $userBatch = Batch::find($user->batch_id);
                $selectedClassId = $userBatch?->class_id;
            }
        }

        if (!$selectedClassId) {
            $selectedClassId = $classes->firstWhere('grade_level', 10)?->id ?? $classes->first()?->id;
        }

        $selectedClass = $classes->firstWhere('id', (int)$selectedClassId) ?? $classes->first();

        // Get batches for this class
        $batches = collect();
        if ($selectedClass) {
            $batches = Batch::where('class_id', $selectedClass->id)
                ->where('is_active', true)
                ->withCount('students')
                ->orderByRaw("CASE WHEN shift = 'morning' THEN 1 WHEN shift = 'evening' THEN 2 ELSE 3 END")
                ->orderBy('name')
                ->get();
        }

        // Student's pending batch change request
        $pendingRequest = $user->isStudent()
            ? BatchChangeRequest::with('requestedBatch')->where('student_id', $user->id)->where('status', 'pending')->first()
            : null;

        // Faculty/Admin: all pending batch change requests
        $allPendingRequests = ($user->isTeacher() || $user->isAdmin())
            ? BatchChangeRequest::with(['student', 'currentBatch', 'requestedBatch'])->where('status', 'pending')->latest()->paginate(10, ['*'], 'requests_page')->withQueryString()
            : collect();

        return view('batches.select', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'batches' => $batches,
            'currentBatchId' => $user->batch_id,
            'pendingRequest' => $pendingRequest,
            'allPendingRequests' => $allPendingRequests,
        ]);
    }

    /**
     * Store the chosen batch for the user.
     * Permission: Only Teachers & Admins can change batches. Enrolled students are strictly forbidden.
     */
    public function storeSelection(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
        ]);

        $user = Auth::user();
        $batch = Batch::findOrFail($validated['batch_id']);

        // Security check: if student already has a batch, they cannot directly change it
        if ($user->isStudent() && $user->batch_id && $user->batch_id !== $batch->id) {
            abort(403, 'Unauthorized. Students cannot change their class or batch.');
        }

        $user->update(['batch_id' => $batch->id]);

        return redirect()->route('batches.subjects', ['batch' => $batch->id])
            ->with('success', "Active batch set to {$batch->name} ({$batch->timing_slot})!");
    }

    /**
     * Students submit a Batch Change Request (Reason required, pending teacher/admin approval).
     */
    public function requestChange(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isStudent()) {
            return redirect()->back()->with('error', 'Only students submit batch change requests.');
        }

        $requestedBatchId = $request->input('requested_batch_id', $request->input('batch_id'));
        $request->merge(['requested_batch_id' => $requestedBatchId]);

        $validated = $request->validate([
            'requested_batch_id' => 'required|exists:batches,id',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $batch = Batch::findOrFail($validated['requested_batch_id']);

        // Check if pending request already exists
        $existing = BatchChangeRequest::where('student_id', $user->id)->where('status', 'pending')->first();
        if ($existing) {
            $existing->update([
                'requested_batch_id' => $batch->id,
                'reason' => $validated['reason'],
            ]);
            return redirect()->route('batches.select')
                ->with('success', "Updated your pending batch change request to \"{$batch->name}\".");
        }

        BatchChangeRequest::create([
            'student_id' => $user->id,
            'current_batch_id' => $user->batch_id,
            'requested_batch_id' => $batch->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('batches.select')
            ->with('success', "Batch change request to \"{$batch->name}\" submitted successfully. Awaiting faculty/admin approval.");
    }

    /**
     * Teacher & Admin only: Approve or Reject a Batch Change Request.
     */
    public function reviewRequest(BatchChangeRequest $batchChangeRequest, Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Unauthorized. Only teachers and administrators can review batch change requests.');
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'approve') {
            $student = $batchChangeRequest->student;
            $student?->update(['batch_id' => $batchChangeRequest->requested_batch_id]);

            $batchChangeRequest->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? 'Approved by faculty',
            ]);

            return redirect()->back()->with('success', "Approved batch change for student {$student?->name}!");
        } else {
            $batchChangeRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? 'Rejected by faculty',
            ]);

            return redirect()->back()->with('success', "Batch change request rejected.");
        }
    }

    /**
     * Create a new custom batch (Teacher or Admin Only).
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Unauthorized. Only teachers and administrators can create academic batches.');
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'name' => 'required|string|max:100',
            'shift' => 'required|in:morning,evening,custom,weekend',
            'timing_slot' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $class = AcademicClass::findOrFail($validated['class_id']);

        $timingSlot = $validated['timing_slot'] ?? null;
        if (empty($timingSlot) && !empty($validated['start_time']) && !empty($validated['end_time'])) {
            $timingSlot = $validated['start_time'] . ' - ' . $validated['end_time'];
        }

        $code = 'BATCH-' . $class->grade_level . '-' . strtoupper(Str::random(5));

        $batch = Batch::create([
            'class_id' => $class->id,
            'name' => $validated['name'],
            'code' => $code,
            'shift' => $validated['shift'],
            'timing_slot' => $timingSlot,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'academic_year' => '2026-2027',
            'description' => $validated['description'] ?? "Batch for Class {$class->grade_level} ({$timingSlot}).",
            'is_active' => true,
        ]);

        return redirect()->route('batches.subjects', ['batch' => $batch->id])
            ->with('success', "Batch \"{$batch->name}\" created successfully!");
    }

    /**
     * Update an existing batch (Teacher or Admin Only).
     */
    public function updateBatch(Request $request, Batch $batch): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Unauthorized. Only teachers and administrators can update academic batches.');
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:academic_classes,id',
            'name' => 'required|string|max:100',
            'shift' => 'required|in:morning,evening,custom,weekend',
            'timing_slot' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:20',
            'end_time' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $timingSlot = $validated['timing_slot'] ?? null;
        if (empty($timingSlot) && !empty($validated['start_time']) && !empty($validated['end_time'])) {
            $timingSlot = $validated['start_time'] . ' - ' . $validated['end_time'];
        }

        $batch->update([
            'class_id' => $validated['class_id'],
            'name' => $validated['name'],
            'shift' => $validated['shift'],
            'timing_slot' => $timingSlot,
            'start_time' => $validated['start_time'] ?? $batch->start_time,
            'end_time' => $validated['end_time'] ?? $batch->end_time,
            'description' => $validated['description'] ?? $batch->description,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
        ]);

        return redirect()->route('batches.select', ['class_id' => $batch->class_id])
            ->with('success', "Batch \"{$batch->name}\" updated successfully!");
    }

    /**
     * Delete an academic batch (Teacher or Admin Only).
     */
    public function destroyBatch(Request $request, Batch $batch): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Unauthorized. Only teachers and administrators can delete academic batches.');
        }

        $classId = $batch->class_id;
        $name = $batch->name;

        // Dissociate enrolled students safely
        User::where('batch_id', $batch->id)->update(['batch_id' => null]);
        BatchChangeRequest::where('current_batch_id', $batch->id)->orWhere('requested_batch_id', $batch->id)->delete();
        $batch->schedules()->delete();
        $batch->delete();

        return redirect()->route('batches.select', ['class_id' => $classId])
            ->with('success', "Batch \"{$name}\" deleted successfully.");
    }

    /**
     * Step 2: Dedicated Subjects page showing compact list of subjects in cards.
     */
    public function subjects(Batch $batch, Request $request): View
    {
        $batch->load('academicClass');
        
        $subjects = AcademicSubject::where('class_id', $batch->class_id)
            ->withCount('topics')
            ->orderBy('name')
            ->get();

        foreach ($subjects as $subject) {
            $topicIds = CurriculumTopic::where('subject_id', $subject->id)->pluck('id');
            $subject->videos_count = \App\Models\CurriculumVideo::where('subject_id', $subject->id)->count();
            $subject->dpps_count = DailyDpp::whereIn('topic_id', $topicIds)->where('type', 'dpp')->count();
            $subject->tests_count = DailyDpp::whereIn('topic_id', $topicIds)->where('type', 'test')->count();
        }

        return view('batches.subjects', [
            'batch' => $batch,
            'academicClass' => $batch->academicClass,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Step 3: Dedicated Chapters Card View List for selected subject.
     */
    public function chapters(Batch $batch, AcademicSubject $subject, Request $request): View
    {
        $batch->load('academicClass');
        
        $chapters = CurriculumTopic::where('subject_id', $subject->id)
            ->orderBy('chapter_number')
            ->get();

        foreach ($chapters as $ch) {
            $ch->videos_count = \App\Models\CurriculumVideo::where('topic_id', $ch->id)->count();
            $ch->dpps_count = DailyDpp::where('topic_id', $ch->id)->where('type', 'dpp')->count();
            $ch->tests_count = DailyDpp::where('topic_id', $ch->id)->where('type', 'test')->count();
            $ch->homework_count = HomeworkTask::where('topic_id', $ch->id)->count();
            $ch->notes_count = CurriculumNote::where('topic_id', $ch->id)->where('is_published', true)->count();
        }

        return view('batches.chapters', [
            'batch' => $batch,
            'academicClass' => $batch->academicClass,
            'subject' => $subject,
            'chapters' => $chapters,
        ]);
    }

    /**
     * Step 4: Least distractive learning page with ONLY the 5 tabs.
     * Notes and DPPs are rendered as clean, rich lists.
     */
    public function learn(Batch $batch, AcademicSubject $subject, $chapter = null, ?Request $request = null): View
    {
        $request = $request ?? request();
        $user = Auth::user();
        $batch->load('academicClass');

        $chapters = CurriculumTopic::where('subject_id', $subject->id)
            ->orderBy('chapter_number')
            ->get();

        $selectedChapter = null;
        if ($chapter instanceof CurriculumTopic) {
            $selectedChapter = $chapter;
        } elseif (is_numeric($chapter)) {
            $selectedChapter = $chapters->firstWhere('id', (int)$chapter);
        }

        if (!$selectedChapter && $request->filled('chapter_id')) {
            $selectedChapter = $chapters->firstWhere('id', (int)$request->query('chapter_id'));
        }

        if (!$selectedChapter && $chapters->isNotEmpty()) {
            $selectedChapter = $chapters->first();
        }

        $videos = collect();
        $watchedVideoIds = [];
        $notesList = collect();
        $dpps = collect();
        $assignments = collect();
        $tests = collect();

        if ($selectedChapter) {
            // Tab 1: Video Lectures
            $videos = $selectedChapter->videos()->orderBy('order_index')->get();
            $watchedVideoIds = UserVideoProgress::where('user_id', $user->id)
                ->whereIn('video_id', $videos->pluck('id'))
                ->where('is_completed', true)
                ->pluck('video_id')
                ->all();

            // Tab 2: Notes & Guides (List of multiple notes per chapter)
            $notesList = CurriculumNote::where('topic_id', $selectedChapter->id)
                ->where('is_published', true)
                ->orderBy('id')
                ->get();

            // Tab 3: Daily DPPs (List of DPP sheets with Solve / Resolve status)
            $isFirstChapter = $chapters->isNotEmpty() && $chapters->first()->id === $selectedChapter->id;
            $dpps = DailyDpp::where(function ($query) use ($selectedChapter, $subject, $isFirstChapter) {
                    $query->where('topic_id', $selectedChapter->id);
                    if ($isFirstChapter) {
                        $query->orWhere(function ($sub) use ($subject) {
                            $sub->whereNull('topic_id')->where('subject_id', $subject->id);
                        });
                    }
                })
                ->where('type', 'dpp')
                ->withCount('questions')
                ->orderBy('dpp_number')
                ->get();

            $dppSubmissions = \App\Models\DppSubmission::where('student_id', $user->id)
                ->whereIn('dpp_id', $dpps->pluck('id'))
                ->get()
                ->keyBy('dpp_id');

            foreach ($dpps as $dpp) {
                $dpp->user_submission = $dppSubmissions->get($dpp->id);
            }

            // Tab 4: Assignments (Homework Tasks)
            $assignments = HomeworkTask::where(function ($query) use ($selectedChapter, $subject, $isFirstChapter) {
                    $query->where('topic_id', $selectedChapter->id);
                    if ($isFirstChapter) {
                        $query->orWhere(function ($sub) use ($subject) {
                            $sub->whereNull('topic_id')->where('subject_id', $subject->id);
                        });
                    }
                })
                ->with(['submissions' => function ($query) use ($user) {
                    $query->where('student_id', $user->id);
                }])
                ->orderBy('due_date', 'desc')
                ->get();

            // Tab 5: Tests (with user submission for Solve vs Retest)
            $tests = DailyDpp::where(function ($query) use ($selectedChapter, $subject, $isFirstChapter) {
                    $query->where('topic_id', $selectedChapter->id);
                    if ($isFirstChapter) {
                        $query->orWhere(function ($sub) use ($subject) {
                            $sub->whereNull('topic_id')->where('subject_id', $subject->id);
                        });
                    }
                })
                ->where('type', 'test')
                ->withCount('questions')
                ->orderBy('assigned_date', 'desc')
                ->get();

            $testSubmissions = \App\Models\DppSubmission::where('student_id', $user->id)
                ->whereIn('dpp_id', $tests->pluck('id'))
                ->get()
                ->keyBy('dpp_id');

            foreach ($tests as $test) {
                $test->user_submission = $testSubmissions->get($test->id);
            }
        }

        // Calculate overall subject video progress
        $allSubjectTopicIds = $chapters->pluck('id');
        $allSubjectVideoIds = \App\Models\CurriculumVideo::whereIn('topic_id', $allSubjectTopicIds)->pluck('id');
        $totalSubjectVideos = $allSubjectVideoIds->count();
        $completedSubjectVideos = UserVideoProgress::where('user_id', $user->id)
            ->whereIn('video_id', $allSubjectVideoIds)
            ->where('is_completed', true)
            ->count();
        $overallProgressPercent = $totalSubjectVideos > 0 
            ? round(($completedSubjectVideos / $totalSubjectVideos) * 100) 
            : 0;

        // Calculate chapter progress
        $chapterTotalVideos = $videos->count();
        $chapterCompletedVideos = count($watchedVideoIds);
        $chapterProgressPercent = $chapterTotalVideos > 0
            ? round(($chapterCompletedVideos / $chapterTotalVideos) * 100)
            : 0;

        return view('batches.learn', [
            'batch' => $batch,
            'academicClass' => $batch->academicClass,
            'subject' => $subject,
            'chapters' => $chapters,
            'selectedChapter' => $selectedChapter,
            'videos' => $videos,
            'watchedVideoIds' => $watchedVideoIds,
            'totalSubjectVideos' => $totalSubjectVideos,
            'completedSubjectVideos' => $completedSubjectVideos,
            'overallProgressPercent' => $overallProgressPercent,
            'chapterTotalVideos' => $chapterTotalVideos,
            'chapterCompletedVideos' => $chapterCompletedVideos,
            'chapterProgressPercent' => $chapterProgressPercent,
            'notesList' => $notesList,
            'notes' => [
                'study_notes' => $selectedChapter?->study_notes,
                'resource_url' => $selectedChapter?->resource_url,
                'pdf_url' => $selectedChapter?->resource_url,
                'subtopics' => $selectedChapter?->subtopics ?? [],
            ],
            'dpps' => $dpps,
            'assignments' => $assignments,
            'tests' => $tests,
        ]);
    }

    /**
     * Upload authentic PDF notes for a chapter (Faculty & Admin only).
     */
    public function uploadNote(Request $request, Batch $batch, AcademicSubject $subject, CurriculumTopic $chapter): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Only faculty and administrators can upload curriculum notes.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'note_type' => 'required|in:theory,formula_sheet,ncert_solution,revision',
            'pdf_file' => 'required|file|mimes:pdf|max:20480',
            'reading_minutes' => 'nullable|integer|min:1|max:120',
        ], [
            'pdf_file.required' => 'Please select a PDF document to upload.',
            'pdf_file.mimes' => 'The uploaded file must be a valid PDF document.',
            'pdf_file.max' => 'The PDF document must not exceed 20MB in size.',
        ]);

        $file = $request->file('pdf_file');
        $fileName = 'note_' . $chapter->id . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('notes', $fileName, 'public');
        $downloadUrl = asset('storage/' . $path);

        CurriculumNote::create([
            'topic_id' => $chapter->id,
            'title' => $validated['title'],
            'note_type' => $validated['note_type'],
            'content' => null,
            'download_url' => $downloadUrl,
            'reading_minutes' => $validated['reading_minutes'] ?? 10,
            'is_published' => true,
        ]);

        return redirect()->route('batches.learn', [
            'batch' => $batch->id,
            'subject' => $subject->id,
            'chapter' => $chapter->id,
        ])->with('success', "PDF note '{$validated['title']}' uploaded successfully!");
    }

    /**
     * Delete an uploaded curriculum PDF note (Faculty & Admin only).
     */
    public function deleteNote(CurriculumNote $note): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($note->download_url) {
            $parsed = parse_url($note->download_url, PHP_URL_PATH);
            $filePath = ltrim(str_replace('/storage/', '', $parsed), '/');
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            }
        }

        $note->delete();
        return back()->with('success', 'Curriculum PDF note removed successfully.');
    }

    /**
     * Backwards compatibility for previous route calls to myBatch().
     */
    public function myBatch(Request $request)
    {
        return $this->index($request);
    }
}
