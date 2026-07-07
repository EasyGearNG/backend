<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Discount extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'valid_from',
        'valid_to',
        'min_order_amount',
        'max_uses',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'min_order_amount'  => 'decimal:2',
        'max_uses'          => 'integer',
        'times_used'        => 'integer',
        'is_active'         => 'boolean',
        'valid_from'        => 'date',
        'valid_to'          => 'date',
    ];

    /**
     * Validate this code against a subtotal and return an error message, or null if valid.
     */
    public function validate(float $subtotal): ?string
    {
        if (!$this->is_active) {
            return 'This discount code is inactive.';
        }

        $today = Carbon::today();

        if ($today->lt($this->valid_from)) {
            return 'This discount code is not yet valid.';
        }

        if ($today->gt($this->valid_to)) {
            return 'This discount code has expired.';
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return 'This discount code has reached its usage limit.';
        }

        if ($subtotal < $this->min_order_amount) {
            return "A minimum order of ₦" . number_format($this->min_order_amount, 2) . " is required to use this code.";
        }

        return null;
    }

    /**
     * Calculate the discount amount for a given subtotal.
     */
    public function calculate(float $subtotal): float
    {
        if ($this->discount_type === 'percentage') {
            return round($subtotal * ($this->discount_value / 100), 2);
        }

        // Fixed: never discount more than the subtotal
        return min((float) $this->discount_value, $subtotal);
    }

    /**
     * Increment the usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    /**
     * Check if this code has been used up.
     */
    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->times_used >= $this->max_uses;
    }
}
