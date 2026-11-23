<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\DailyDpp;
use App\Models\HomeworkTask;
use App\Models\TopicBloomActivity;
use App\Models\UserVideoProgress;
use App\Services\CsvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SyllabusController extends Controller
{
    public function __construct(protected CsvService $csvService) {}

    protected function authorizeFaculty(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            abort(403, 'Unauthorized. Faculty or administrator credentials required.');
        }
    }

    /**
     * Display chapterwise syllabus with strict hierarchy: Class -> Subject -> Chapters.
     */
    public function index(Request $request): View
    {
        $allClasses = AcademicClass::orderBy('grade_level')->get();

        // Step 1: Selected Grade Level / Class (defaults to student's class, or Grade 10)
        $user = Auth::user();
        $defaultGrade = 10;
        if ($user && $user->batch && $user->batch->academicClass) {
            $defaultGrade = $user->batch->academicClass->grade_level;
        }

        $selectedGrade = (int) $request->query('grade', $request->query('class_id', $defaultGrade));
        $selectedClass = $allClasses->firstWhere('grade_level', $selectedGrade)
            ?? $allClasses->firstWhere('id', $selectedGrade)
            ?? $allClasses->first();

        // Step 2: Subjects for Selected Class
        $subjects = $selectedClass ? $selectedClass->subjects()->withCount('topics')->orderBy('name')->get() : collect();

        $selectedSubjectId = $request->query('subject_id');
        $selectedSubject = null;
        if ($selectedSubjectId && $selectedSubjectId !== 'all') {
            $selectedSubject = $subjects->firstWhere('id', (int) $selectedSubjectId);
        }
        if (! $selectedSubject && $selectedSubjectId !== 'all' && $subjects->isNotEmpty()) {
            $selectedSubject = $subjects->first();
            $selectedSubjectId = $selectedSubject->id;
        }

        $search = $request->query('search');

        // Step 3: Chapters (Topics) for Selected Subject
        $query = CurriculumTopic::with(['subject.academicClass', 'bloomActivities'])
            ->withCount(['videos', 'dpps', 'homeworkTasks', 'bloomActivities'])
            ->whereHas('subject', function ($q) use ($selectedClass) {
                if ($selectedClass) {
                    $q->where('class_id', $selectedClass->id);
                }
            });

        if ($selectedSubjectId && $selectedSubjectId !== 'all') {
            $query->where('subject_id', $selectedSubjectId);
        }

        if ($search) {
            $s = strtolower($search);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$s}%"]);
            });
        }

        $topics = $query->orderBy('chapter_number')->paginate(12)->withQueryString();

        // Syllabus completion progress for selected class
        $classTopicsQuery = CurriculumTopic::whereHas('subject', function ($q) use ($selectedClass) {
            if ($selectedClass) {
                $q->where('class_id', $selectedClass->id);
            }
        });
        $classTotalTopics = $classTopicsQuery->count();
        $classCompletedTopics = (clone $classTopicsQuery)->where('status', 'completed')->count();
        $classInProgressTopics = (clone $classTopicsQuery)->where('status', 'in_progress')->count();
        $syllabusProgressRate = $classTotalTopics > 0 ? round(($classCompletedTopics / $classTotalTopics) * 100, 1) : 0.0;

        // Quick syllabus overview stats
        $stats = [
            'total_classes' => $allClasses->count(),
            'total_subjects' => AcademicSubject::count(),
            'total_chapters' => CurriculumTopic::count(),
            'total_videos' => CurriculumVideo::count(),
            'total_dpps' => DailyDpp::count(),
            'total_homework' => HomeworkTask::count(),
            'mapped_bloom_activities' => TopicBloomActivity::count(),
            'class_total' => $classTotalTopics,
            'class_completed' => $classCompletedTopics,
            'class_in_progress' => $classInProgressTopics,
            'progress_rate' => $syllabusProgressRate,
        ];

        return view('syllabus.index', compact(
            'allClasses',
            'selectedClass',
            'selectedGrade',
            'subjects',
            'selectedSubject',
            'selectedSubjectId',
            'search',
            'topics',
            'stats',
            'syllabusProgressRate',
            'classTotalTopics',
            'classCompletedTopics',
            'classInProgressTopics'
        ));
    }

    /**
     * Show a detailed view of an individual chapter with its Bloom's levels, Videos, DPPs, and Homework.
     */
    public function show(int $id): View
    {
        $userId = auth()->id();

        $topic = CurriculumTopic::with([
            'subject.academicClass',
            'bloomActivities',
            'lessons.teacher:id,name',
            'videos.userProgress' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            },
            'dpps.questions',
            'dpps.submissions' => function ($q) use ($userId) {
                $q->where('student_id', $userId);
            },
            'homeworkTasks.submissions' => function ($q) use ($userId) {
                $q->where('student_id', $userId);
            },
        ])->findOrFail($id);

        $totalVideos = $topic->videos->count();
        $watchedVideos = $topic->videos->filter(function ($v) use ($userId) {
            return $v->isWatchedBy($userId);
        })->count();

        $totalDpps = $topic->dpps->count();
        $attemptedDpps = $topic->dpps->filter(function ($d) {
            return $d->submissions->isNotEmpty();
        })->count();

        $totalHomework = $topic->homeworkTasks->count();
        $submittedHomework = $topic->homeworkTasks->filter(function ($h) {
            return $h->submissions->isNotEmpty();
        })->count();

        $chapterMetrics = [
            'total_videos' => $totalVideos,
            'watched_videos' => $watchedVideos,
            'video_progress' => $totalVideos > 0 ? round(($watchedVideos / $totalVideos) * 100) : 0,
            'total_dpps' => $totalDpps,
            'attempted_dpps' => $attemptedDpps,
            'total_homework' => $totalHomework,
            'submitted_homework' => $submittedHomework,
            'bloom_count' => $topic->bloomActivities->count(),
        ];

        return view('syllabus.show', compact('topic', 'chapterMetrics'));
    }

    /**
     * Teacher / Admin adds a new video lecture to a chapter topic.
     */
    public function storeVideo(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::with('subject')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url',
            'duration_minutes' => 'nullable|integer|min:1|max:300',
            'description' => 'nullable|string',
            'lecture_notes_url' => 'nullable|url',
        ]);

        $orderIndex = ($topic->videos()->max('order_index') ?? 0) + 1;
        $durationSeconds = isset($validated['duration_minutes']) ? ($validated['duration_minutes'] * 60) : 1800;

        CurriculumVideo::create([
            'topic_id' => $topic->id,
            'subject_id' => $topic->subject_id,
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
            'video_provider' => 'youtube',
            'duration_seconds' => $durationSeconds,
            'order_index' => $orderIndex,
            'description' => $validated['description'] ?? "Video lecture covering key conceptual principles of {$topic->title}.",
            'lecture_notes_url' => $validated['lecture_notes_url'] ?? null,
            'is_free_preview' => true,
            'is_published' => true,
        ]);

        return back()->with('success', "Video lecture '{$validated['title']}' added successfully to {$topic->title}!");
    }

    /**
     * Teacher / Admin updates a video lecture.
     */
    public function updateVideo(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $video = CurriculumVideo::with('topic')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url',
            'duration_minutes' => 'nullable|integer|min:1|max:300',
            'description' => 'nullable|string',
            'lecture_notes_url' => 'nullable|url',
        ]);

        $durationSeconds = isset($validated['duration_minutes']) ? ($validated['duration_minutes'] * 60) : $video->duration_seconds;

        $video->update([
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
            'duration_seconds' => $durationSeconds,
            'description' => $validated['description'] ?? $video->description,
            'lecture_notes_url' => $validated['lecture_notes_url'] ?? null,
        ]);

        return back()->with('success', "Video lecture '{$video->title}' updated successfully.");
    }

    /**
     * Teacher / Admin deletes a video lecture.
     */
    public function destroyVideo(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $video = CurriculumVideo::findOrFail($id);
        $title = $video->title;

        UserVideoProgress::where('video_id', $video->id)->delete();
        $video->delete();

        return back()->with('success', "Video lecture '{$title}' removed from syllabus.");
    }

    /**
     * Teacher / Admin adds a new DPP to a chapter topic.
     */
    public function storeDpp(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::with('subject.academicClass')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'difficulty' => 'required|in:standard,advanced,olympiad',
            'duration_minutes' => 'required|integer|min:5|max:180',
            'pass_percentage' => 'nullable|integer|min:30|max:100',
            'negative_marks' => 'nullable|numeric|min:0|max:5',
        ]);

        $class = $topic->subject->academicClass;
        $dppCount = DailyDpp::where('topic_id', $topic->id)->count() + 1;
        $dppNum = sprintf('DPP-%02d-%s-%02d-P%d', $class->grade_level, strtoupper(substr($topic->subject->code ?? 'SUB', 0, 4)), $topic->chapter_number, $dppCount);

        $dpp = DailyDpp::create([
            'class_id' => $class->id,
            'subject_id' => $topic->subject_id,
            'topic_id' => $topic->id,
            'teacher_id' => auth()->id(),
            'dpp_number' => $dppNum,
            'title' => $validated['title'],
            'assigned_date' => now()->toDateString(),
            'due_time' => '23:59',
            'total_marks' => 20,
            'difficulty' => $validated['difficulty'],
            'duration_minutes' => $validated['duration_minutes'],
            'is_timed' => true,
            'pass_percentage' => $validated['pass_percentage'] ?? 60,
            'negative_marks' => $validated['negative_marks'] ?? 1.00,
        ]);

        return back()->with('success', "DPP '{$dpp->title}' ({$dpp->dpp_number}) created successfully for {$topic->title}!");
    }

    /**
     * Teacher / Admin adds a new Homework to a chapter topic.
     */
    public function storeHomework(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::with('subject.academicClass')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'due_days' => 'required|integer|min:1|max:30',
        ]);

        HomeworkTask::create([
            'class_id' => $topic->subject->class_id,
            'subject_id' => $topic->subject_id,
            'topic_id' => $topic->id,
            'teacher_id' => auth()->id(),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'assigned_date' => now()->toDateString(),
            'due_date' => now()->addDays($validated['due_days'])->toDateString(),
        ]);

        return back()->with('success', "Homework task '{$validated['title']}' created successfully for {$topic->title}!");
    }

    /**
     * Toggle video watched status from chapter view.
     */
    public function toggleVideoWatched(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $video = CurriculumVideo::findOrFail($id);
        $userId = auth()->id();

        $progress = UserVideoProgress::firstOrCreate(
            [
                'user_id' => $userId,
                'video_id' => $video->id,
            ],
            [
                'is_completed' => false,
                'watched_seconds' => $video->duration_seconds,
                'last_watched_at' => now(),
            ]
        );

        $newStatus = ! $progress->is_completed;
        $progress->update([
            'is_completed' => $newStatus,
            'last_watched_at' => now(),
            'watched_seconds' => $newStatus ? $video->duration_seconds : 0,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_completed' => $newStatus,
                'message' => $newStatus ? 'Video marked as watched!' : 'Video marked as unwatched.',
            ]);
        }

        return back()->with('success', $newStatus ? 'Video marked as watched!' : 'Video marked as unwatched.');
    }

    /**
     * Download sample CSV for chapterwise syllabus.
     */
    public function sampleCsv(): StreamedResponse
    {
        $headers = ['chapter_number', 'title', 'subtopics', 'description'];
        $sampleData = [
            [
                '1',
                'Introduction to Data Structures & Algorithms',
                'Arrays, Linked Lists, Stacks, Queues',
                'Fundamental linear and non-linear data structures, memory layout, and computational complexity analysis.',
            ],
            [
                '2',
                'Sorting and Searching Algorithms',
                'Binary Search, Merge Sort, Quick Sort, Complexity O(n log n)',
                'Algorithmic design patterns, divide and conquer paradigms, and empirical runtime benchmarking.',
            ],
            [
                '3',
                'Graph Theory and Pathfinding',
                'Breadth-First Search, Depth-First Search, Dijkstras Algorithm',
                'Graph traversal, shortest path computations, and real-world network routing models.',
            ],
        ];

        return $this->csvService->downloadCsv('syllabus_template_classes_1_to_12.csv', $headers, $sampleData);
    }

    /**
     * Export syllabus for a selected class as CSV.
     */
    public function exportCsv(int $classId): StreamedResponse
    {
        $class = AcademicClass::with(['subjects.topics'])->findOrFail($classId);

        $headers = ['Class', 'Subject', 'Subject Code', 'Chapter #', 'Chapter Title', 'Subtopics', 'Description'];
        $data = [];

        foreach ($class->subjects as $subject) {
            foreach ($subject->topics as $topic) {
                $subtopicsStr = is_array($topic->subtopics) ? implode('; ', $topic->subtopics) : (string) $topic->subtopics;
                $data[] = [
                    $class->name,
                    $subject->name,
                    $subject->code ?? '',
                    $topic->chapter_number,
                    $topic->title,
                    $subtopicsStr,
                    $topic->description,
                ];
            }
        }

        $filename = "Syllabus_{$class->name}_Academic_Curriculum.csv";

        return $this->csvService->downloadCsv($filename, $headers, $data);
    }

    /**
     * Import chapters into a subject via CSV.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        $this->authorizeFaculty();
        $request->validate([
            'subject_id' => 'required|exists:academic_subjects,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $subject = AcademicSubject::with('academicClass')->findOrFail($request->input('subject_id'));
        $rows = $this->csvService->parseCsv($request->file('csv_file')->getPathname());

        if (empty($rows)) {
            return back()->with('error', 'The uploaded CSV file is empty or corrupted.');
        }

        $importedCount = 0;
        foreach ($rows as $row) {
            $chapterNumber = isset($row['chapter_number']) ? (int) $row['chapter_number'] : ($importedCount + 1);
            $title = $row['title'] ?? ($row['chapter_title'] ?? null);

            if (! $title) {
                continue;
            }

            $subtopics = [];
            if (! empty($row['subtopics'])) {
                $subtopics = array_map('trim', explode(';', $row['subtopics']));
                if (count($subtopics) === 1 && str_contains($row['subtopics'], ',')) {
                    $subtopics = array_map('trim', explode(',', $row['subtopics']));
                }
            }

            $description = $row['description'] ?? 'Curriculum standard topic for '.$subject->name;

            CurriculumTopic::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'chapter_number' => $chapterNumber,
                ],
                [
                    'title' => $title,
                    'subtopics' => $subtopics,
                    'description' => $description,
                ]
            );

            $importedCount++;
        }

        return redirect()->route('syllabus.index', ['grade' => $subject->academicClass->grade_level, 'subject_id' => $subject->id])
            ->with('success', "Successfully imported {$importedCount} syllabus chapters for {$subject->name} ({$subject->academicClass->name}) via CSV!");
    }

    /**
     * Teacher / Admin updates topic completion progress and study resources.
     */
    public function updateProgress(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,completed',
            'study_notes' => 'nullable|string',
            'resource_url' => 'nullable|url',
        ]);

        $topic->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
            'study_notes' => $validated['study_notes'] ?? null,
            'resource_url' => $validated['resource_url'] ?? null,
        ]);

        return back()->with('success', "Curriculum topic '{$topic->title}' status updated to ".ucwords(str_replace('_', ' ', $validated['status'])).'!');
    }

    /**
     * Dual-Sided Whole Syllabus Tracker:
     * Side 1: Chapter Theory & Syllabus Coverage Tracker
     * Side 2: Chapter-wise Activities Checklist (Labs, Competitions, Projects, Debates, Symposia)
     */
    public function tracker(Request $request): View
    {
        $allClasses = AcademicClass::orderBy('grade_level')->get();

        $user = Auth::user();
        $defaultGrade = 10;
        if ($user && $user->batch && $user->batch->academicClass) {
            $defaultGrade = $user->batch->academicClass->grade_level;
        }

        $selectedGrade = (int) $request->query('grade', $request->query('class_id', $defaultGrade));
        $selectedClass = $allClasses->firstWhere('grade_level', $selectedGrade)
            ?? $allClasses->firstWhere('id', $selectedGrade)
            ?? $allClasses->first();

        $subjects = $selectedClass ? $selectedClass->subjects()->withCount('topics')->orderBy('name')->get() : collect();

        $selectedSubjectId = $request->query('subject_id', 'all');
        $selectedSubject = null;
        if ($selectedSubjectId && $selectedSubjectId !== 'all') {
            $selectedSubject = $subjects->firstWhere('id', (int) $selectedSubjectId);
        }

        $activityFilter = $request->query('activity', 'all');
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search');

        // Query Topics with relationships
        $query = CurriculumTopic::with(['subject.academicClass', 'bloomActivities', 'lessonPlans', 'dpps', 'homeworkTasks'])
            ->whereHas('subject', function ($q) use ($selectedClass) {
                if ($selectedClass) {
                    $q->where('class_id', $selectedClass->id);
                }
            });

        if ($selectedSubject) {
            $query->where('subject_id', $selectedSubject->id);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $s = strtolower($search);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$s}%"]);
            });
        }

        $allClassTopics = (clone $query)->orderBy('chapter_number')->get();

        // Auto-initialize topic activities from lesson plans if checklist is empty
        foreach ($allClassTopics as $top) {
            if (empty($top->activity_checklist) && $top->lessonPlans->isNotEmpty()) {
                $checklist = $top->normalized_activity_checklist;
                $hasComp = $top->lessonPlans->contains(fn ($lp) => $lp->isCompetition());
                $hasProj = $top->lessonPlans->contains(fn ($lp) => $lp->isProject());
                $hasDeb = $top->lessonPlans->contains(fn ($lp) => $lp->isDebate());
                $hasSymp = $top->lessonPlans->contains(fn ($lp) => $lp->isSymposium());
                $hasLab = $top->lessonPlans->contains(fn ($lp) => ! empty($lp->practical_lab));
                $hasRev = $top->lessonPlans->contains(fn ($lp) => $lp->isRevision());
                $hasTest = $top->lessonPlans->contains(fn ($lp) => $lp->isTest());

                if ($hasComp) {
                    $checklist['competition']['organized'] = true;
                }
                if ($hasProj) {
                    $checklist['project']['organized'] = true;
                }
                if ($hasDeb) {
                    $checklist['debate']['organized'] = true;
                }
                if ($hasSymp) {
                    $checklist['symposium']['organized'] = true;
                }
                if ($hasLab) {
                    $checklist['lab']['organized'] = true;
                }
                if ($hasRev) {
                    $checklist['revision']['organized'] = true;
                }
                if ($hasTest) {
                    $checklist['test']['organized'] = true;
                }

                $top->activity_checklist = $checklist;
                $top->save();
            }
        }

        // Apply activity filter if needed
        $topics = $allClassTopics;
        if ($activityFilter !== 'all') {
            $topics = $topics->filter(function ($top) use ($activityFilter) {
                return $top->isActivityOrganized($activityFilter);
            });
        }

        // Aggregate Metrics for Selected View
        $totalChapters = $allClassTopics->count();
        $completedTheoryCount = $allClassTopics->where('status', 'completed')->count();
        $inProgressTheoryCount = $allClassTopics->where('status', 'in_progress')->count();
        $theoryRate = $totalChapters > 0 ? round(($completedTheoryCount / $totalChapters) * 100, 1) : 0.0;

        $totalLabsOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('lab'))->count();
        $totalCompetitionsOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('competition'))->count();
        $totalProjectsOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('project'))->count();
        $totalDebatesOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('debate'))->count();
        $totalSymposiaOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('symposium'))->count();
        $totalRevisionsOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('revision'))->count();
        $totalTestsOrganized = $allClassTopics->filter(fn ($t) => $t->isActivityOrganized('test'))->count();

        $activitiesPerChapter = count(CurriculumTopic::defaultActivityChecklistTemplate());
        $totalPossibleActivities = $totalChapters * $activitiesPerChapter;
        $totalOrganizedActivities = $totalLabsOrganized + $totalCompetitionsOrganized + $totalProjectsOrganized + $totalDebatesOrganized + $totalSymposiaOrganized + $totalRevisionsOrganized + $totalTestsOrganized;
        $activityRate = $totalPossibleActivities > 0 ? round(($totalOrganizedActivities / $totalPossibleActivities) * 100, 1) : 0.0;

        return view('syllabus.tracker', compact(
            'allClasses',
            'selectedClass',
            'selectedGrade',
            'subjects',
            'selectedSubject',
            'selectedSubjectId',
            'activityFilter',
            'statusFilter',
            'search',
            'topics',
            'totalChapters',
            'completedTheoryCount',
            'inProgressTheoryCount',
            'theoryRate',
            'totalLabsOrganized',
            'totalCompetitionsOrganized',
            'totalProjectsOrganized',
            'totalDebatesOrganized',
            'totalSymposiaOrganized',
            'totalRevisionsOrganized',
            'totalTestsOrganized',
            'totalOrganizedActivities',
            'totalPossibleActivities',
            'activityRate'
        ));
    }

    /**
     * Toggle or update an activity checklist item for a topic.
     */
    public function toggleActivity(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isTeacher() && ! $user->isAdmin())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'activity_type' => 'required|string|in:lab,competition,project,debate,symposium,revision,test',
            'organized' => 'nullable|boolean',
            'date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $topic = CurriculumTopic::findOrFail($id);
        $state = $request->has('organized') ? $request->boolean('organized') : null;

        $topic->toggleActivity(
            $validated['activity_type'],
            $state,
            $validated['date'] ?? null,
            $validated['notes'] ?? null
        );

        $checklist = $topic->normalized_activity_checklist;
        $item = $checklist[$validated['activity_type']];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'topic_id' => $topic->id,
                'activity_type' => $validated['activity_type'],
                'organized' => $item['organized'],
                'item' => $item,
                'organized_count' => $topic->organized_activities_count,
                'progress_percent' => $topic->activity_progress_percent,
                'message' => "{$item['icon']} {$item['name']} marked as ".($item['organized'] ? 'Organized / Completed' : 'Pending').'!',
            ]);
        }

        return back()->with('success', "{$item['icon']} {$item['name']} updated for {$topic->title}!");
    }

    /**
     * Teacher / Admin stores a new syllabus topic manually.
     */
    public function storeTopic(Request $request): RedirectResponse
    {
        $this->authorizeFaculty();
        $validated = $request->validate([
            'subject_id' => 'required|exists:academic_subjects,id',
            'chapter_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subtopics' => 'nullable|string',
            'resource_url' => 'nullable|url',
            'study_notes' => 'nullable|string',
        ]);

        $subtopics = [];
        if (! empty($validated['subtopics'])) {
            $subtopics = array_values(array_filter(array_map('trim', preg_split('/[;,\n\r]+/', $validated['subtopics']))));
        }

        $topic = CurriculumTopic::create([
            'subject_id' => $validated['subject_id'],
            'chapter_number' => (int) $validated['chapter_number'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'subtopics' => $subtopics,
            'resource_url' => $validated['resource_url'] ?? null,
            'study_notes' => $validated['study_notes'] ?? null,
            'status' => 'not_started',
        ]);

        $subject = AcademicSubject::with('academicClass')->find($topic->subject_id);
        $grade = $subject?->academicClass?->grade_level ?? 8;

        return redirect()->route('syllabus.index', ['grade' => $grade, 'subject_id' => $topic->subject_id])
            ->with('success', "Chapter {$topic->chapter_number} ({$topic->title}) added to syllabus.");
    }

    /**
     * Teacher / Admin updates an existing syllabus topic manually.
     */
    public function updateTopic(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::with('subject.academicClass')->findOrFail($id);

        $validated = $request->validate([
            'chapter_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subtopics' => 'nullable|string',
            'resource_url' => 'nullable|url',
            'study_notes' => 'nullable|string',
            'status' => 'nullable|in:not_started,in_progress,completed',
        ]);

        $subtopics = [];
        if (! empty($validated['subtopics'])) {
            $subtopics = array_values(array_filter(array_map('trim', preg_split('/[;,\n\r]+/', $validated['subtopics']))));
        }

        $updateData = [
            'chapter_number' => (int) $validated['chapter_number'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'subtopics' => $subtopics,
            'resource_url' => $validated['resource_url'] ?? null,
            'study_notes' => $validated['study_notes'] ?? null,
        ];

        if (! empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
            if ($validated['status'] === 'completed' && ! $topic->completed_at) {
                $updateData['completed_at'] = now();
            } elseif ($validated['status'] !== 'completed') {
                $updateData['completed_at'] = null;
            }
        }

        $topic->update($updateData);

        return redirect()->route('syllabus.show', $topic->id)
            ->with('success', "Chapter {$topic->chapter_number} ({$topic->title}) updated successfully.");
    }

    /**
     * Teacher / Admin deletes a syllabus topic.
     */
    public function destroyTopic(Request $request, int $id): RedirectResponse
    {
        $this->authorizeFaculty();
        $topic = CurriculumTopic::with('subject.academicClass')->findOrFail($id);
        $grade = $topic->subject?->academicClass?->grade_level ?? 8;
        $subjectId = $topic->subject_id;
        $chapterNum = $topic->chapter_number;
        $title = $topic->title;

        $topic->delete();

        return redirect()->route('syllabus.index', ['grade' => $grade, 'subject_id' => $subjectId])
            ->with('success', "Chapter {$chapterNum} ({$title}) deleted from syllabus.");
    }
}
