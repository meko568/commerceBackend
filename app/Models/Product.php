<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Cacheable;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_description',
        'long_description',
        'price',
        'sale_price',
        'current_price',
        'has_sale',
        'stock',
        'main_image',
        'additional_images',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'has_sale' => 'boolean',
        'stock' => 'integer',
        'additional_images' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all active products (cached)
     */
    public static function getActiveProducts()
    {
        return Cache::remember(static::getCollectionCacheKey('active'), 3600, function () {
            return static::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get latest products (cached)
     */
    public static function getLatestProducts($limit = 8)
    {
        return Cache::remember(static::getCollectionCacheKey("latest_{$limit}"), 1800, function () use ($limit) {
            return static::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get product by ID with caching
     */
    public static function getCachedProduct($id)
    {
        return Cache::remember(static::getCollectionCacheKey("item_{$id}"), 3600, function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Get all products for admin (cached)
     */
    public static function getForAdmin()
    {
        return Cache::remember(static::getCollectionCacheKey('admin'), 900, function () {
            return static::orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted sale price
     */
    public function getFormattedSalePriceAttribute()
    {
        return $this->sale_price ? '$' . number_format($this->sale_price, 2) : null;
    }

    /**
     * Get formatted current price
     */
    public function getFormattedCurrentPriceAttribute()
    {
        return '$' . number_format($this->current_price, 2);
    }

    /**
     * Get main image URL
     */
    public function getMainImageUrlAttribute()
    {
        if (!$this->main_image) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->main_image, FILTER_VALIDATE_URL)) {
            return $this->main_image;
        }

        // If it's already a storage path, convert to full URL
        if (str_starts_with($this->main_image, 'storage/')) {
            return url($this->main_image);
        }

        // If it starts with 'products/', it's already in the right format for storage
        if (str_starts_with($this->main_image, 'products/')) {
            return url('storage/' . $this->main_image);
        }

        // If it's just a filename, assume it's in storage/app/public/products/
        return url('storage/products/' . $this->main_image);
    }

    /**
     * Get additional images URLs
     */
    public function getAdditionalImagesUrlsAttribute()
    {
        if (!$this->additional_images) {
            return [];
        }

        return collect($this->additional_images)->map(function ($image) {
            // If it's already a full URL, return as is
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }

            // If it's already a storage path, convert to full URL
            if (str_starts_with($image, 'storage/')) {
                return url($image);
            }

            // If it starts with 'products/', it's already in the right format for storage
            if (str_starts_with($image, 'products/')) {
                return url('storage/' . $image);
            }

            // If it's just a filename, assume it's in storage/app/public/products/
            return url('storage/products/' . $image);
        })->toArray();
    }

    /**
     * Get all images (main + additional)
     */
    public function getAllImagesAttribute()
    {
        $images = [];
        
        if ($this->main_image_url) {
            $images[] = $this->main_image_url;
        }
        
        if (!empty($this->additional_images_urls)) {
            $images = array_merge($images, $this->additional_images_urls);
        }
        
        return $images;
    }

    /**
     * Check if product is in stock
     */
    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusAttribute()
    {
        if ($this->stock > 10) {
            return 'In Stock';
        } elseif ($this->stock > 0) {
            return 'Low Stock (' . $this->stock . ' left)';
        } else {
            return 'Out of Stock';
        }
    }

    /**
     * Scope to get only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get products on sale
     */
    public function scopeOnSale($query)
    {
        return $query->where('has_sale', true);
    }

    /**
     * Scope to get in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Get comments for the product
     */
    public function comments()
    {
        return $this->hasMany(ProductComment::class);
    }

    /**
     * Get approved comments for the product
     */
    public function approvedComments()
    {
        return $this->hasMany(ProductComment::class)->where('is_approved', true);
    }

    /**
     * Get average rating for the product
     */
    public function getAverageRatingAttribute()
    {
        return $this->approvedComments()->avg('rating') ?: 0;
    }
}
