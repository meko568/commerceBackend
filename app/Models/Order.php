<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Cacheable;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_info',
        'items',
        'total_amount',
        'status',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'user_info' => 'array',
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all orders for admin (cached)
     */
    public static function getForAdmin()
    {
        return Cache::remember(static::getCollectionCacheKey('admin'), 900, function () {
            return static::orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Get orders by status (cached)
     */
    public static function getByStatus($status)
    {
        return Cache::remember(static::getCollectionCacheKey("status_{$status}"), 600, function () use ($status) {
            return static::where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get order statistics (cached)
     */
    public static function getStatistics()
    {
        return Cache::remember(static::getCollectionCacheKey('statistics'), 1800, function () {
            return [
                'total_orders' => static::count(),
                'total_revenue' => static::sum('total_amount'),
                'pending_orders' => static::where('status', 'pending')->count(),
                'processing_orders' => static::where('status', 'processing')->count(),
                'shipped_orders' => static::where('status', 'shipped')->count(),
                'delivered_orders' => static::where('status', 'delivered')->count(),
                'paid_orders' => static::where('payment_status', 'paid')->count(),
                'pending_payments' => static::where('payment_status', 'pending')->count(),
            ];
        });
    }

    /**
     * Get recent orders (cached)
     */
    public static function getRecentOrders($limit = 10)
    {
        return Cache::remember(static::getCollectionCacheKey("recent_{$limit}"), 600, function () use ($limit) {
            return static::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get order by ID with caching
     */
    public static function getCachedOrder($id)
    {
        return Cache::remember(static::getCollectionCacheKey("item_{$id}"), 3600, function () use ($id) {
            return static::find($id);
        });
    }

    /**
     * Forget all order-related caches
     */
    protected function forgetAllCaches(): void
    {
        parent::forgetAllCaches();
        
        // Forget order-specific caches
        Cache::forget(static::getCollectionCacheKey('admin'));
        Cache::forget(static::getCollectionCacheKey('statistics'));
        Cache::forget(static::getCollectionCacheKey('recent_10'));
        Cache::forget(static::getCollectionCacheKey('recent_5'));
        
        // Forget status-based caches
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        foreach ($statuses as $status) {
            Cache::forget(static::getCollectionCacheKey("status_{$status}"));
        }
    }

    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Pending</span>',
            'processing' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Processing</span>',
            'shipped' => '<span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Shipped</span>',
            'delivered' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Delivered</span>',
            'cancelled' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Cancelled</span>'
        ];

        return $badges[$this->status] ?? $badges['pending'];
    }

    public function getPaymentStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Pending</span>',
            'paid' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Paid</span>',
            'failed' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Failed</span>',
            'refunded' => '<span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">Refunded</span>'
        ];

        return $badges[$this->payment_status] ?? $badges['pending'];
    }

    public function getCustomerNameAttribute()
    {
        return $this->user_info['fullName'] ?? 'N/A';
    }

    public function getCustomerEmailAttribute()
    {
        return $this->user_info['email'] ?? 'N/A';
    }

    public function getCustomerPhoneAttribute()
    {
        return $this->user_info['phone'] ?? 'N/A';
    }

    public function getCustomerAddressAttribute()
    {
        $userInfo = $this->user_info;
        if (!$userInfo) return 'N/A';
        
        $address = [];
        if (!empty($userInfo['address'])) $address[] = $userInfo['address'];
        if (!empty($userInfo['city'])) $address[] = $userInfo['city'];
        if (!empty($userInfo['postalCode'])) $address[] = $userInfo['postalCode'];
        if (!empty($userInfo['country'])) $address[] = $userInfo['country'];
        
        return implode(', ', $address);
    }

    public function getItemCountAttribute()
    {
        return count($this->items ?? []);
    }
}
