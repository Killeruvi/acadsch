<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\CurriculumTopic;
use App\Models\CurriculumVideo;
use App\Models\UserVideoProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    /**
     * Display a comprehensive Video Lecture Library with class tabs, subject filters, and search.
     */
    public function index(Request $request): View
    {
        $allClasses = AcademicClass::orderBy('grade_level')->get();

        $user = auth()->user();
        $defaultGrade = 10;
        if ($user && $user->isStudent()) {
            if ($user->batch && $user->batch->academicClass) {
                $defaultGrade = $user->batch->academicClass->grade_level;
            }
        }

        $selectedGrade = (int) $request->query('grade', $defaultGrade);
        $selectedClass = $allClasses->firstWhere('grade_level', $selectedGrade) ?? $allClasses->first();

        $subjects = $selectedClass ? $selectedClass->subjects()->orderBy('name')->get() : collect();

        $selectedSubjectId = $request->query('subject_id');
        $selectedTopicId = $request->query('topic_id');
        $search = $request->query('search');

        $query = CurriculumVideo::with([
            'topic.subject.academicClass',
            'subject',
            'userProgress' => function ($q) {
                $q->where('user_id', auth()->id());
            }
        ])
        ->whereHas('subject', function ($q) use ($selectedClass) {
            if ($selectedClass) {
                $q->where('class_id', $selectedClass->id);
            }
        });

        if ($selectedSubjectId) {
            $query->where('subject_id', $selectedSubjectId);
        }

        if ($selectedTopicId) {
            $query->where('topic_id', $selectedTopicId);
        }

        if ($search) {
            $s = strtolower($search);
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$s}%"]);
            });
        }

        $videos = $query->orderBy('topic_id')->orderBy('order_index')->paginate(12)->withQueryString();

        // Calculate student's overall video progress in this class
        $classVideosCount = CurriculumVideo::whereHas('subject', function ($q) use ($selectedClass) {
            if ($selectedClass) {
                $q->where('class_id', $selectedClass->id);
            }
        })->count();

        $watchedCount = UserVideoProgress::where('user_id', auth()->id())
            ->where('is_completed', true)
            ->whereHas('video.subject', function ($q) use ($selectedClass) {
                if ($selectedClass) {
                    $q->where('class_id', $selectedClass->id);
                }
            })->count();

        $watchRate = $classVideosCount > 0 ? round(($watchedCount / $classVideosCount) * 100, 1) : 0.0;

        $stats = [
            'total_videos' => CurriculumVideo::count(),
            'class_videos' => $classVideosCount,
            'watched_count' => $watchedCount,
            'watch_rate' => $watchRate,
        ];

        return view('videos.index', compact(
            'allClasses',
            'selectedClass',
            'selectedGrade',
            'subjects',
            'selectedSubjectId',
            'selectedTopicId',
            'search',
            'videos',
            'stats'
        ));
    }

    /**
     * Show dedicated video lecture player view with lecture notes and syllabus details.
     */
    public function show(int $id): View
    {
        $video = CurriculumVideo::with([
            'topic.subject.academicClass',
            'subject',
            'topic.videos' => function ($q) {
                $q->orderBy('order_index');
            },
            'userProgress' => function ($q) {
                $q->where('user_id', auth()->id());
            }
        ])->findOrFail($id);

        $isWatched = $video->isWatchedBy(auth()->id());

        return view('videos.show', compact('video', 'isWatched'));
    }

    /**
     * Toggle watched progress for current student.
     */
    public function toggleWatched(Request $request, int $id): JsonResponse|RedirectResponse
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

        $newStatus = !$progress->is_completed;
        $progress->update([
            'is_completed' => $newStatus,
            'last_watched_at' => now(),
            'watched_seconds' => $newStatus ? $video->duration_seconds : 0,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_completed' => $newStatus,
                'message' => $newStatus ? 'Lecture marked as completed!' : 'Lecture marked as uncompleted.',
            ]);
        }

        return back()->with('success', $newStatus ? 'Video marked as watched!' : 'Video marked as unwatched.');
    }
}
