<?php

namespace App\Policies;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Assignment;
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
            if ((int) $assignment->teacher_id === (int) $user->id) {
                return true;
            }
            if ($assignment->class_id) {
                return AcademicSubject::where('class_id', $assignment->class_id)->where('teacher_id', $user->id)->exists()
                    || AcademicClass::where('id', $assignment->class_id)->where('class_teacher_id', $user->id)->exists();
            }

            return false;
        }
        if ($user->isStudent()) {
            return $this->studentCanAccess($user, $assignment);
        }
        if ($user->isParent()) {
            $children = User::childrenOf($user)->get();
            if ($children->isEmpty()) {
                return false;
            }
            foreach ($children as $child) {
                if ($this->studentCanAccess($child, $assignment)) {
                    return true;
                }
            }

            return false;
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
        if ($assignment->class_id) {
            $studentClassIds = $student->classEnrollments()
                ->where(function ($q) {
                    $q->whereNull('status')->orWhereIn('status', ['active', 'enrolled']);
                })->pluck('class_id');
            if ($student->current_class_id) {
                $studentClassIds->push($student->current_class_id);
            }

            if (! $studentClassIds->contains((int) $assignment->class_id)) {
                return false;
            }

            if ($assignment->section_id) {
                $cls = $assignment->academicClass;
                if ($cls && $cls->section_id && (int) $cls->section_id !== (int) $assignment->section_id) {
                    return false;
                }
            }

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
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isTeacher()) {
            if ((int) $assignment->teacher_id === (int) $user->id) {
                return true;
            }
            if ($assignment->class_id) {
                return AcademicSubject::where('class_id', $assignment->class_id)->where('teacher_id', $user->id)->exists()
                    || AcademicClass::where('id', $assignment->class_id)->where('class_teacher_id', $user->id)->exists();
            }
        }

        return false;
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
