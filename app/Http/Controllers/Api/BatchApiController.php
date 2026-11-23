<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchApiController extends Controller
{
    /**
     * List all batches.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Batch::with('academicClass:id,name,grade_level')
            ->withCount('students')
            ->where('is_active', true);

        if ($user->isStudent()) {
            $query->whereKey($user->batch_id);
        } elseif ($user->isParent()) {
            $batchIds = \App\Models\User::childrenOf($user)->whereNotNull('batch_id')->pluck('batch_id');
            $query->whereIn('id', $batchIds);
        } elseif ($user->isTeacher()) {
            $classIds = \App\Models\AcademicClass::query()->where(function ($q) use ($user) {
                $q->where('class_teacher_id', $user->id)
                    ->orWhereHas('subjects', fn ($sq) => $sq->where('teacher_id', $user->id))
                    ->orWhereHas('timetables', fn ($tq) => $tq->where('teacher_id', $user->id));
            })->select('id');
            $query->whereIn('class_id', $classIds);
        } elseif (!$user->isAdmin()) {
            abort(403);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->query('shift'));
        }

        $batches = $query->orderBy('class_id')
            ->orderByRaw("CASE WHEN shift = 'morning' THEN 1 WHEN shift = 'evening' THEN 2 ELSE 3 END")
            ->paginate(20);

        return response()->json($batches);
    }

    /**
     * Show a single batch with its subjects and details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $batch = Batch::with('academicClass.subjects')->withCount('students')->findOrFail($id);

        $isCurrentUserBatch = $user->isStudent() && (int) $user->batch_id === (int) $batch->id;
        $isChildBatch = false;
        if ($user->isParent()) {
            $isChildBatch = \App\Models\User::childrenOf($user)
                ->where('batch_id', $batch->id)->exists();
        }

        if ($user->isStudent()) {
            abort_unless($isCurrentUserBatch, 403, 'You may only view your own batch.');
        } elseif ($user->isParent()) {
            abort_unless($isChildBatch, 403, 'This batch is not linked to your children.');
        } elseif (! $user->isTeacher() && ! $user->isAdmin()) {
            abort(403);
        }

        // Do not expose a full student roster through a general batch-detail endpoint.
        // Student and parent views receive only batch/class information and aggregate counts.
        if ($user->isAdmin()) {
            $batch->load(['students:id,name,student_id_number,avatar_url']);
        }

        $pendingRequest = null;
        if ($user->isStudent()) {
            $pendingRequest = BatchChangeRequest::where('student_id', $user->id)
                ->where('status', 'pending')->first();
        }

        return response()->json([
            'batch' => $batch,
            'is_current_user_batch' => $isCurrentUserBatch,
            'pending_batch_change_request' => $pendingRequest,
        ]);
    }
}

