<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeworkApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = HomeworkTask::with(['academicClass:id,name', 'subject:id,name', 'teacher:id,name'])->withCount('submissions');
        if ($user->isAdmin()) {
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->integer('class_id'));
            }
        } elseif ($user->isTeacher()) {
            $query->where('teacher_id', $user->id);
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->integer('class_id'));
            }
        } elseif ($user->isStudent()) {
            $classIds = $user->classEnrollments()->whereIn('status', ['active', 'enrolled'])->pluck('class_id');
            if ($user->current_class_id) {
                $classIds->push($user->current_class_id);
            }
            $query->whereIn('class_id', $classIds->unique());
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            $classIds = ClassEnrollment::whereIn('student_id', $childIds)->whereIn('status', ['active', 'enrolled'])->pluck('class_id');
            $query->whereIn('class_id', $classIds->unique());
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }
        $tasks = $query->latest('assigned_date')->paginate(min(100, max(1, $request->integer('per_page', 15))));
        if ($user->isStudent()) {
            $subs = HomeworkSubmission::whereIn('homework_id', $tasks->pluck('id'))->where('student_id', $user->id)->get()->keyBy('homework_id');
            $tasks->getCollection()->transform(function ($task) use ($subs) {
                $sub = $subs->get($task->id);
                $task->user_submission = $sub ? ['id' => $sub->id, 'status' => $sub->status, 'submitted_at' => $sub->created_at, 'teacher_feedback' => $sub->teacher_feedback, 'has_attachment' => $sub->hasAttachment(), 'attachment_name' => $sub->attachment_name, 'attachment_type' => $sub->attachment_type, 'attachment_size' => $sub->formatted_attachment_size, 'download_url' => $sub->hasAttachment() ? route('homework.download-attachment', ['id' => $sub->id, 'type' => 'submission']) : null] : null;
                $task->task_attachment_download_url = $task->hasAttachment() ? route('homework.download-attachment', ['id' => $task->id, 'type' => 'task']) : null;

                return $task;
            });
        }

        return response()->json($tasks);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $task = HomeworkTask::with(['academicClass', 'subject', 'teacher:id,name'])->findOrFail($id);
        abort_unless($this->canViewTask($user, $task), 403);
        $submission = $user->isStudent() ? HomeworkSubmission::where('homework_id', $id)->where('student_id', $user->id)->first() : null;

        return response()->json(['task' => $task, 'user_submission' => $submission]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isStudent()) {
            return response()->json(['message' => 'Only students can submit daily homework.'], 403);
        }
        $task = HomeworkTask::findOrFail($id);
        $enrolled = (int) $user->current_class_id === (int) $task->class_id || $user->classEnrollments()->where('class_id', $task->class_id)->whereIn('status', ['active', 'enrolled'])->exists();
        abort_unless($enrolled, 403, 'You are not enrolled in the class assigned to this homework.');
        abort_unless(now()->toDateString() >= $task->assigned_date->toDateString(), 403, 'This homework is not open for submissions yet.');
        $validated = $request->validate(['student_notes' => 'nullable|string|max:3000', 'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,pps,ppsx,txt,odt,rtf,png,jpg,jpeg,webp,zip|max:51200']);
        $notes = trim((string) ($validated['student_notes'] ?? ''));
        $hasFile = $request->hasFile('attachment') && $request->file('attachment')->isValid();
        if ($notes === '' && ! $hasFile) {
            return response()->json(['message' => 'Validation error', 'errors' => ['student_notes' => ['Provide written notes or attach a file.']]], 422);
        }
        $existing = HomeworkSubmission::where('homework_id', $task->id)->where('student_id', $user->id)->first();
        $path = $existing?->attachment_path;
        $name = $existing?->attachment_name;
        $type = $existing?->attachment_type;
        $size = $existing?->attachment_size;
        if ($hasFile) {
            if ($existing && $path) {
                foreach (['local', 'public'] as $disk) {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }
                }
            }
            $file = $request->file('attachment');
            $path = $file->store('homework/submissions', 'local');
            $name = mb_substr(basename($file->getClientOriginalName()), 0, 255);
            $type = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();
        }
        $submission = HomeworkSubmission::updateOrCreate(['homework_id' => $task->id, 'student_id' => $user->id], ['student_notes' => $notes ?: 'Attached file response.', 'attachment_path' => $path, 'attachment_name' => $name, 'attachment_type' => $type, 'attachment_size' => $size, 'status' => 'submitted', 'teacher_feedback' => null, 'checked_at' => null, 'is_late' => now()->toDateString() > $task->due_date->toDateString(), 'resubmission_count' => (int) ($existing?->resubmission_count ?? 0) + ($existing ? 1 : 0)]);
        if (! $existing) {
            $user->recordDailyActivity(20);
        }

        return response()->json(['message' => $existing ? 'Homework resubmitted successfully.' : 'Homework submitted successfully.', 'submission' => $submission], 200);
    }

    private function canViewTask(User $user, HomeworkTask $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isTeacher()) {
            return (int) $task->teacher_id === (int) $user->id;
        }
        if ($user->isStudent()) {
            return (int) $user->current_class_id === (int) $task->class_id || $user->classEnrollments()->where('class_id', $task->class_id)->whereIn('status', ['active', 'enrolled'])->exists();
        }
        if ($user->isParent()) {
            $children = User::childrenOf($user)->pluck('id');

            return ClassEnrollment::whereIn('student_id', $children)->where('class_id', $task->class_id)->exists();
        }

        return false;
    }
}
