<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSplit extends Model
{
    protected $fillable = [
        'payment_id',
        'vendor_id',
        'amount_to_vendor',
        'platform_fee',
        'split_date',
        'status',
    ];

    protected $casts = [
        'amount_to_vendor' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'split_date' => 'datetime',
    ];

    /**
     * Get the payment that owns this split
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the vendor that owns this split
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
