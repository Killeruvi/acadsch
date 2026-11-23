<?php

namespace App\Policies;

use App\Models\AcademicClass;
use App\Models\User;

class AcademicClassPolicy
{
    /**
     * Determine whether the user can view any classes.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can view the class.
     */
    public function view(User $user, AcademicClass $class): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can create classes.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the class.
     */
    public function update(User $user, AcademicClass $class): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the class.
     */
    public function delete(User $user, AcademicClass $class): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage subjects for the class.
     */
    public function manageSubjects(User $user, AcademicClass $class): bool
    {
        return $user->isAdmin();
    }
}
