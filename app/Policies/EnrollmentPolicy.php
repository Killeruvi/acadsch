<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can view any enrollments.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can view the enrollment.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin() || $user->isTeacher()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->id === $enrollment->student_id;
        }

        if ($user->isParent()) {
            return $enrollment->student && (
                $enrollment->student->parent_id === $user->id ||
                $enrollment->student->guardian_email === $user->email
            );
        }

        return false;
    }

    /**
     * Determine whether the user can create enrollments.
     */
    public function create(User $user, ?int $targetStudentId = null): bool
    {
        if ($user->isAdmin() || $user->isTeacher()) {
            return true;
        }

        if ($user->isStudent()) {
            return $targetStudentId === null || $targetStudentId === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can update the enrollment.
     */
    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the enrollment.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }
}
