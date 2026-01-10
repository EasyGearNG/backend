<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsCompany extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact_email',
        'contact_phone',
        'address',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'paystack_recipient_code',
        'delivery_fee',
        'commission_percentage',
        'is_active',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get order items handled by this logistics company
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the logistics company's wallet
     */
    public function wallet()
    {
        return Wallet::where('owner_type', 'logistics_' . $this->code)
            ->where('owner_id', $this->id)
            ->first();
    }

    /**
     * Get or create wallet for this logistics company
     */
    public function getOrCreateWallet()
    {
        return Wallet::getOrCreate('logistics_' . $this->code, $this->id);
    }
}
