<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'category_id', 
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'quantity',
        'weight',
        'dimensions',
        'sku',
        'brand',
        'image_url',
        'size_options',
        'color_options',
        'is_active',
        'status',
        'is_featured',
        'average_rating',
        'total_reviews',
        'total_sales',
        'view_count'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'average_rating' => 'decimal:1',
        'size_options' => 'array',
        'color_options' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
        'view_count' => 'integer',
        'quantity' => 'integer',
    ];

    protected $appends = [
        'formatted_price',
        'is_in_stock',
        'is_low_stock',
        'stock_status',
        'primary_image'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . time();
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the product images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the inventory for this product.
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * Get the reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cart items for this product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for in-stock products
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope for products by category
     */
    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for products by vendor
     */
    public function scopeByVendor(Builder $query, $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope for search
     */
    public function scopeSearch(Builder $query, $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('short_description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for price range
     */
    public function scopePriceRange(Builder $query, $minPrice = null, $maxPrice = null): Builder
    {
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }
        return $query;
    }

    /**
     * Get formatted price attribute
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₦' . number_format($this->price, 2);
    }

    /**
     * Check if product is in stock
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if product has low stock (less than 10)
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity > 0 && $this->quantity <= 10;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->quantity === 0) {
            return 'Out of Stock';
        } elseif ($this->quantity <= 10) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Get primary image
     */
    public function getPrimaryImageAttribute(): ?string
    {
        $primaryImage = $this->images()->orderBy('sort_order')->first();
        if ($primaryImage) {
            return asset('storage/' . $primaryImage->image_path);
        }
        return $this->image_url ?: asset('images/placeholder-product.png');
    }

    /**
     * Update product rating
     */
    public function updateRating(): void
    {
        $this->average_rating = $this->reviews()->avg('rating') ?: 0;
        $this->total_reviews = $this->reviews()->count();
        $this->save();
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment sales count
     */
    public function incrementSales($quantity = 1): void
    {
        $this->increment('total_sales', $quantity);
    }

    /**
     * Decrease stock quantity
     */
    public function decreaseStock($quantity): bool
    {
        if ($this->quantity >= $quantity) {
            $this->decrement('quantity', $quantity);
            return true;
        }
        return false;
    }

    /**
     * Increase stock quantity
     */
    public function increaseStock($quantity): void
    {
        $this->increment('quantity', $quantity);
    }
}
