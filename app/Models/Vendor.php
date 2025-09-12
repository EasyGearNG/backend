<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
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
}
