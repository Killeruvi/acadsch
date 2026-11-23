<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionEnquiry extends Model
{
    protected $fillable = [
        'student_name', 'applying_for_class', 'date_of_birth', 'guardian_name',
        'guardian_phone', 'guardian_email', 'preferred_contact_time', 'previous_school',
        'message', 'status', 'admin_notes', 'contacted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'contacted_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
