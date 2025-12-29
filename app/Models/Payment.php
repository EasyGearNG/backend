<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'idempotency_key',
        'payment_date',
        'processed_at',
        'gateway_response',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment has already been processed.
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at) && in_array($this->status, ['success', 'failed']);
    }

    /**
     * Mark payment as processed.
     */
    public function markAsProcessed(): void
    {
        if (is_null($this->processed_at)) {
            $this->update(['processed_at' => now()]);
        }
    }

    /**
     * Scope to get unprocessed payments.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('status', 'pending')
                     ->whereNull('processed_at');
    }
}
