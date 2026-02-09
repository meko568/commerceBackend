<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Cacheable;

class ProductComment extends Model
{
    use HasFactory, Cacheable;

    protected $fillable = [
        'product_id',
        'user_id',
        'comment',
        'rating',
        'is_approved',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that owns the comment
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get approved comments for a product
     */
    public static function getApprovedComments($productId)
    {
        return static::where('product_id', $productId)
            ->where('is_approved', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all comments for a product (for admin)
     */
    public static function getAllComments($productId)
    {
        return static::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get average rating for a product
     */
    public static function getAverageRating($productId)
    {
        return static::where('product_id', $productId)
            ->where('is_approved', true)
            ->whereNotNull('rating')
            ->avg('rating');
    }
}
