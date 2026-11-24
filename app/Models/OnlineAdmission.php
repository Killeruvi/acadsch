<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OnlineAdmission extends Model
{
    use HasFactory;

    protected $table = 'online_admissions';

    protected $fillable = [
        'application_number',
        'academic_year',
        'target_class_id',
        'student_first_name',
        'student_last_name',
        'dob',
        'gender',
        'blood_group',
        'nationality',
        'religion',
        'caste_category',
        'previous_school_name',
        'previous_class',
        'previous_marks_percentage',
        'father_name',
        'father_phone',
        'father_email',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'mother_email',
        'mother_occupation',
        'guardian_address',
        'city',
        'state',
        'pincode',
        'birth_certificate_path',
        'transfer_certificate_path',
        'previous_marksheet_path',
        'student_photo_path',
        'application_status',
        'review_notes',
        'reviewed_by',
        'enrolled_user_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'previous_marks_percentage' => 'decimal:2',
    ];

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'target_class_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function enrolledUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_user_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->student_first_name} {$this->student_last_name}";
    }

    /**
     * Generate unique application number.
     */
    public static function generateApplicationNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(Str::random(5));
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('ADM-%s-%04d-%s', $year, $count, $random);
    }
}
