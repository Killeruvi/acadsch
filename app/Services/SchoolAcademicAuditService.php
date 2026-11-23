<?php

namespace App\Services;

use App\Models\TeacherAssignmentAudit;
use App\Models\User;
use Illuminate\Http\Request;

class SchoolAcademicAuditService
{
    public function log(User $actor, string $action, string $description, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null, ?int $classId = null, ?int $subjectId = null): TeacherAssignmentAudit
    {
        return TeacherAssignmentAudit::create([
            'admin_id' => $actor->id,
            'action' => $action,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
        ]);
    }
}
