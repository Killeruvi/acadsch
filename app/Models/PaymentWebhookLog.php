<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookLog extends Model
{
    protected $fillable = [
        'gateway',
        'event',
        'transaction_reference',
        'invoice_id',
        'amount',
        'status',
        'signature_verified',
        'ip_address',
        'headers',
        'payload',
        'response_status',
        'response_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'signature_verified' => 'boolean',
        'headers' => 'array',
        'payload' => 'array',
        'response_status' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }
}
