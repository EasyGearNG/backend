<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletWithdrawal extends Model
{
    protected $fillable = [
        'wallet_id',
        'recipient_type',
        'recipient_id',
        'amount',
        'bank_name',
        'account_number',
        'account_name',
        'reference',
        'status',
        'notes',
        'metadata',
        'processed_at',
        'paystack_transfer_code',
        'paystack_transfer_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns this withdrawal
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the recipient (LogisticsCompany or Vendor)
     */
    public function recipient()
    {
        if ($this->recipient_type === 'logistics_company') {
            return LogisticsCompany::find($this->recipient_id);
        } elseif ($this->recipient_type === 'vendor') {
            return Vendor::find($this->recipient_id);
        }
        return null;
    }
}
