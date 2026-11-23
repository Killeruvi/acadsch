<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'course_id',
        'title',
        'category',
        'amount',
        'discount',
        'tax',
        'total_due',
        'paid_amount',
        'fee_breakdown',
        'late_fine_amount',
        'installment_number',
        'total_installments',
        'status',
        'billing_type',
        'billing_month',
        'due_date',
        'notes',
    ];

    public function isMonthlyAuto(): bool
    {
        return $this->billing_type === 'monthly_auto';
    }

    public function isManual(): bool
    {
        return $this->billing_type !== 'monthly_auto';
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_due' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'fee_breakdown' => 'array',
            'late_fine_amount' => 'decimal:2',
            'installment_number' => 'integer',
            'total_installments' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'invoice_id');
    }

    public function getBalanceAttribute(): float
    {
        return max(0.00, (float)$this->total_due - (float)$this->paid_amount);
    }
}
