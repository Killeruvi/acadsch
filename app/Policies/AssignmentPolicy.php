<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isTeacher()) {
            return (int) $assignment->teacher_id === (int) $user->id;
        }
        if ($user->isStudent()) {
            return $this->studentCanAccess($user, $assignment);
        }
        if ($user->isParent()) {
            $childIds = User::childrenOf($user)->pluck('id');
            if ($childIds->isEmpty()) {
                return false;
            }
            if ($assignment->target_type === 'all_students') {
                return true;
            }
            if ($assignment->target_type === 'specific_student') {
                return $childIds->contains((int) $assignment->student_id);
            }

            return $assignment->course_id !== null
                && Enrollment::whereIn('student_id', $childIds)->where('course_id', $assignment->course_id)->exists();
        }

        return false;
    }

    private function studentCanAccess(?User $student, Assignment $assignment): bool
    {
        if (! $student) {
            return false;
        }
        if ($assignment->target_type === 'specific_student') {
            return (int) $assignment->student_id === (int) $student->id;
        }
        if ($assignment->target_type === 'all_students') {
            return true;
        }

        return $assignment->course_id !== null && $student->enrolledCourses()->where('courses.id', $assignment->course_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && (int) $assignment->teacher_id === (int) $user->id);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function grade(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        return $user->isStudent() && $this->studentCanAccess($user, $assignment);
    }
}
