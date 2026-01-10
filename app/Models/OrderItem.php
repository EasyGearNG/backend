<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_id',
        'logistics_company_id',
        'logistics_fee',
        'quantity',
        'price_at_purchase',
        'subtotal',
        'tracking_id',
        'shipment_id',
        'fulfillment_status',
        'dispatched_at',
        'delivered_at',
        'confirmed_at',
        'dispatch_notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_purchase' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the order that owns the order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the vendor associated with the order item.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the logistics company handling this order item.
     */
    public function logisticsCompany(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class);
    }

    /**
     * Get the shipment associated with the order item.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the latest shipment update for this order item (via shipment).
     */
    public function latestShipmentUpdate()
    {
        return $this->shipment ? $this->shipment->updates()->latest()->first() : null;
    }
}
