<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\User;
use App\Services\SchoolBrandingService;
use App\Services\StudentIdCardVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VirtualIdCardController extends Controller
{
    public function show(?int $userId = null): View
    {
        $currentUser = Auth::user();
        abort_unless($currentUser, 401);

        $target = $userId === null || $userId === $currentUser->id
            ? $currentUser
            : User::query()->findOrFail($userId);

        if ((int) $target->id !== (int) $currentUser->id) {
            if ($currentUser->isAdmin()) {
                // Administrators may issue/view cards for institutional users.
            } elseif ($currentUser->isTeacher()) {
                abort_unless($target->role === 'student', 403);
                $assignedClassIds = AcademicClass::query()
                    ->where(function ($query) use ($currentUser) {
                        $query->where('class_teacher_id', $currentUser->id)
                            ->orWhereHas('subjects', fn ($q) => $q->where('teacher_id', $currentUser->id))
                            ->orWhereHas('timetables', fn ($q) => $q->where('teacher_id', $currentUser->id));
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                abort_unless($target->current_class_id !== null
                    && in_array((int) $target->current_class_id, $assignedClassIds, true), 403);
            } elseif ($currentUser->isParent()) {
                abort_unless($target->role === 'student'
                    && (int) $target->parent_id === (int) $currentUser->id, 403);
            } else {
                abort(403, 'You may only view your own identity card.');
            }
        }

        $target->loadMissing(['currentClass', 'enrolledCourses', 'taughtCourses']);
        $school = SchoolBrandingService::get();
        $verificationUrl = $target->role === 'student'
            ? app(StudentIdCardVerificationService::class)->verificationUrl($target)
            : null;

        return view('idcard.index', [
            'user' => $target,
            'school' => $school,
            'verificationUrl' => $verificationUrl,
            'cardType' => $target->role === 'student' ? 'Student Identity Card' : 'Staff Identity Card',
        ]);
    }
}
