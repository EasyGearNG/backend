<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStaff extends Model
{
    protected $table = 'vendor_staff';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'role',
        'position',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the vendor that owns this staff member.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user associated with this staff member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
