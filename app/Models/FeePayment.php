<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'student_id',
        'transaction_reference',
        'payment_method',
        'amount',
        'paid_at',
        'status',
        'receipt_number',
        'transaction_id',
        'payment_gateway',
        'upi_vpa',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment) {
            if (empty($payment->transaction_reference)) {
                $payment->transaction_reference = 'TXN-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function feeInvoice(): BelongsTo
    {
        return $this->invoice();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['completed', 'successful']);
    }

    public function scopeSettled($query)
    {
        return $query->whereIn('status', ['completed', 'successful']);
    }
}
