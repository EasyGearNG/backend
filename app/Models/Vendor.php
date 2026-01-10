<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'contact_email',
        'contact_phone',
        'address',
        'bank_details',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'bank_details' => 'array',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this vendor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for this vendor.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the payment splits for this vendor.
     */
    public function paymentSplits(): HasMany
    {
        return $this->hasMany(PaymentSplit::class);
    }

    /**
     * Get the vendor's wallet.
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }
}
