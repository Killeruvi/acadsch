<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayOrder extends Model
{
    use HasFactory;

    protected $table = 'payment_gateway_orders';

    protected $fillable = [
        'order_reference',
        'invoice_id',
        'student_id',
        'gateway',
        'amount',
        'currency',
        'status',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'receipt_number',
        'webhook_received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'webhook_received_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
