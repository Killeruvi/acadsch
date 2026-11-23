<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'expense_head',
        'amount',
        'date',
        'payment_method',
        'voucher_number',
        'description',
        'paid_to',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
