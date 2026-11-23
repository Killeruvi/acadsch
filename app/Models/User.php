<?php

namespace App\Models;

use App\Services\BirthdayService;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'batch_id',
        'phone',
        'bio',
        'student_id_number',
        'avatar_url',
        'date_of_birth',
        'address',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'blood_group',
        'card_valid_until',
        'streak_count',
        'last_active_date',
        'xp_points',
        'academic_tier',
        'is_approved',
        'approved_by',
        'approved_at',
        'parent_id',
        'admission_number',
        'current_class_id',
        'roll_number',
        'previous_school_name',
        'previous_school_board',
        'previous_school_year',
        'previous_school_tc_number',
        'previous_school_remarks',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'card_valid_until' => 'date',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function setPhoneAttribute($value): void
    {
        $clean = trim((string) $value);
        $this->attributes['phone'] = $clean !== '' ? $clean : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isAdminOrCashier(): bool
    {
        return in_array($this->role, ['admin', 'cashier'], true);
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    /** Scope student records to a parent account. Legacy email matching is
     * allowed only when the student has no explicit parent_id link. */
    public function scopeChildrenOf($query, self $parent)
    {
        return $query->where('role', 'student')->where(function ($q) use ($parent) {
            $q->where('parent_id', $parent->id);
            if (is_string($parent->email) && trim($parent->email) !== '') {
                $q->orWhere(function ($legacy) use ($parent) {
                    $legacy->whereNull('parent_id')
                        ->whereRaw('LOWER(TRIM(guardian_email)) = ?', [mb_strtolower(trim($parent->email))]);
                });
            }
        });
    }

    public function isGuardianOf(?self $student): bool
    {
        if (! $student || ! $this->isParent() || ! $student->isStudent()) {
            return false;
        }
        if ((int) $student->parent_id === (int) $this->id) {
            return true;
        }

        return $student->parent_id === null
            && is_string($this->email) && trim($this->email) !== ''
            && is_string($student->guardian_email)
            && mb_strtolower(trim($student->guardian_email)) === mb_strtolower(trim($this->email));
    }

    /**
     * Increment streak and award XP points for activity.
     */
    public function recordDailyActivity(int|string $activityOrXp = 50, ?int $explicitXp = null): void
    {
        $isPassiveVisit = in_array($activityOrXp, ['login', 'dashboard', 'visit'], true);
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Prevent unlimited XP glitch: Never award XP multiple times for page visits/reloads on the same day
        if ($this->last_active_date === $today && $isPassiveVisit) {
            return;
        }

        if ($explicitXp !== null) {
            $xpEarned = $explicitXp;
        } elseif (is_numeric($activityOrXp)) {
            $xpEarned = (int) $activityOrXp;
        } else {
            $xpEarned = match ($activityOrXp) {
                'bloom_submission', 'assignment_submit' => 75,
                'fee_payment' => 50,
                'login', 'dashboard', 'visit' => 25,
                default => 30,
            };
        }

        if ($this->last_active_date === $today) {
            // Already checked in today: Only award XP for genuine task completions, never page reloads
            if (! $isPassiveVisit && $xpEarned > 0) {
                $this->increment('xp_points', $xpEarned);
                $this->refreshTier();
                $this->save();
            }

            return;
        }

        // First check-in / activity of the day
        if ($this->last_active_date === $yesterday) {
            $this->increment('streak_count');
        } else {
            $this->streak_count = 1;
        }

        $this->last_active_date = $today;
        $this->xp_points = ($this->xp_points ?? 0) + $xpEarned;
        $this->refreshTier();
        $this->save();
    }

    public function refreshTier(): void
    {
        if ($this->xp_points >= 2500) {
            $this->academic_tier = 'Presidential Fellow';
        } elseif ($this->xp_points >= 1200) {
            $this->academic_tier = 'Distinguished Scholar';
        } elseif ($this->xp_points >= 500) {
            $this->academic_tier = 'Honors Scholar';
        } else {
            $this->academic_tier = 'Junior Scholar';
        }
    }

    public function getXpLevelAttribute(): int
    {
        return max(1, (int) floor(($this->xp_points ?? 0) / 300) + 1);
    }

    /** Return only a persisted institutional ID; never fabricate an identifier for display. */
    public function getStudentIdNumberAttribute($value): string
    {
        return (string) ($value ?: ($this->attributes['admission_number'] ?? ''));
    }

    /**
     * Generate a guaranteed conflict-free admission number.
     * Format: ADM-YYYY-#### (e.g. ADM-2026-0001).
     * Scans users and class_enrollments, finds the maximum numeric sequence,
     * and verifies uniqueness in a loop to completely avoid collisions.
     */
    public static function generateNextAdmissionNumber(?string $year = null): string
    {
        $year = $year ?: date('Y');
        $prefix = "ADM-{$year}-";

        // Collect all taken admission numbers matching this pattern
        $userAdmissions = static::where('admission_number', 'like', "{$prefix}%")->pluck('admission_number');
        $userIds = static::where('student_id_number', 'like', "{$prefix}%")->pluck('student_id_number');
        $enrollmentAdmissions = ClassEnrollment::where('admission_number', 'like', "{$prefix}%")->pluck('admission_number');

        $allTaken = $userAdmissions->merge($userIds)->merge($enrollmentAdmissions)->filter()->unique();

        $maxSeq = 0;
        foreach ($allTaken as $num) {
            if (preg_match('/^ADM-\d{4}-(\d+)$/', $num, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        // Base candidate sequence on maximum existing sequence + 1
        $candidateSeq = $maxSeq + 1;

        // Loop to guarantee no collision across both users and class_enrollments
        do {
            $candidate = sprintf('ADM-%s-%04d', $year, $candidateSeq);
            $conflict = static::where('admission_number', $candidate)
                ->orWhere('student_id_number', $candidate)
                ->exists()
                || ClassEnrollment::where('admission_number', $candidate)->exists();

            if ($conflict) {
                $candidateSeq++;
            }
        } while ($conflict);

        return $candidate;
    }

    /**
     * Courses taught by this user (if teacher).
     */
    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function teachingCourses(): HasMany
    {
        return $this->taughtCourses();
    }

    /**
     * Enrollments for this student.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Courses this student is enrolled in.
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'student_id', 'course_id')
            ->withPivot(['id', 'status', 'enrolled_at', 'grade_letter', 'final_score'])
            ->withTimestamps();
    }

    /**
     * Submissions by this student.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    /**
     * Fee Invoices issued to this student.
     */
    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class, 'student_id');
    }

    /**
     * Fee Payments made by this student.
     */
    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'student_id');
    }

    /**
     * Bloom's Taxonomy assignments assigned to this student.
     */
    public function bloomAssignments(): HasMany
    {
        return $this->hasMany(BloomAssignment::class, 'student_id')->latest('created_at');
    }

    /**
     * Daily lessons conducted by this teacher.
     */
    public function taughtLessons(): HasMany
    {
        return $this->hasMany(DailyLesson::class, 'teacher_id')->latest('lesson_date');
    }

    /**
     * School class enrollments with roll numbers.
     */
    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'student_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class, 'user_id')->latest('created_at');
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class, 'user_id')->latest('created_at');
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class, 'user_id')->where('is_read', false)->latest('created_at');
    }

    public function videoProgress(): HasMany
    {
        return $this->hasMany(UserVideoProgress::class, 'user_id');
    }

    public function readNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class, 'user_id')->where('is_read', true)->latest('created_at');
    }

    public function dppSubmissions(): HasMany
    {
        return $this->hasMany(DppSubmission::class, 'student_id');
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'student_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(StudentLeaveRequest::class, 'student_id')->latest('created_at');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(AcademyNotice::class, 'author_id')->latest('published_at');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(UserCertificate::class, 'user_id')->latest('issued_date');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'user_id')->latest('awarded_at');
    }

    public function projectSubmissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class, 'student_id')->latest('submitted_at');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id')->latest('created_at');
    }

    public function flashcardProgress(): HasMany
    {
        return $this->hasMany(UserFlashcardProgress::class, 'user_id');
    }

    public function isBirthdayToday(): bool
    {
        if (! $this->date_of_birth) {
            return false;
        }

        $today = now();
        $dob = Carbon::parse($this->date_of_birth);

        return $today->month === $dob->month && $today->day === $dob->day;
    }

    public function checkBirthdayCelebration(): ?array
    {
        return BirthdayService::checkAndCelebrate($this);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class, 'current_class_id');
    }

    public function homeroomClass(): HasOne
    {
        return $this->hasOne(AcademicClass::class, 'class_teacher_id');
    }

    public function teachingSubjects(): HasMany
    {
        return $this->hasMany(AcademicSubject::class, 'teacher_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(SchoolReportCard::class, 'student_id');
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(SchoolExamMark::class, 'student_id');
    }

    public function transportAssignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class, 'student_id');
    }

    public function activeTransportAssignment(): HasOne
    {
        return $this->hasOne(StudentTransportAssignment::class, 'student_id')->where('status', 'active')->latestOfMany();
    }
}
