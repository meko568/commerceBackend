<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;

class CacheService
{
    /**
     * Clear all application caches
     */
    public static function clearAllCaches()
    {
        Cache::flush();
        return true;
    }

    /**
     * Clear all product-related caches
     */
    public static function clearProductCaches()
    {
        $keys = [
            'products_active',
            'products_latest_8',
            'products_latest_10',
            'products_admin',
        ];

        // Clear individual product caches
        $products = Product::all();
        foreach ($products as $product) {
            $keys[] = "products_item_{$product->id}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return true;
    }

    /**
     * Clear all order-related caches
     */
    public static function clearOrderCaches()
    {
        $keys = [
            'orders_admin',
            'orders_statistics',
            'orders_recent_10',
            'orders_recent_5',
        ];

        // Clear status-based caches
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        foreach ($statuses as $status) {
            $keys[] = "orders_status_{$status}";
        }

        // Clear individual order caches
        $orders = Order::all();
        foreach ($orders as $order) {
            $keys[] = "orders_item_{$order->id}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return true;
    }

    /**
     * Clear all user-related caches
     */
    public static function clearUserCaches()
    {
        $keys = [];

        // Clear individual user caches
        $users = User::all();
        foreach ($users as $user) {
            $keys[] = "users_item_{$user->id}";
            $keys[] = "users_email_{$user->email}";
            $keys[] = "user_{$user->id}_profile";
            $keys[] = "user_{$user->id}_statistics";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return true;
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats()
    {
        return [
            'products' => [
                'active_count' => Product::getActiveProducts()->count(),
                'total_count' => Product::count(),
                'cache_keys' => [
                    'products_active',
                    'products_latest_8',
                    'products_admin',
                ]
            ],
            'orders' => [
                'total_count' => Order::count(),
                'statistics' => Order::getStatistics(),
                'cache_keys' => [
                    'orders_admin',
                    'orders_statistics',
                    'orders_recent_10',
                ]
            ],
            'users' => [
                'total_count' => User::count(),
                'cache_keys' => [
                    'users_profile',
                    'users_statistics',
                ]
            ]
        ];
    }

    /**
     * Warm up caches for better performance
     */
    public static function warmUpCaches()
    {
        // Warm up product caches
        Product::getActiveProducts();
        Product::getLatestProducts(8);
        Product::getForAdmin();

        // Warm up order caches
        Order::getForAdmin();
        Order::getStatistics();
        Order::getRecentOrders(10);

        // Warm up user caches
        $users = User::all();
        foreach ($users as $user) {
            $user->getCachedProfile();
        }

        return true;
    }

    /**
     * Get cache size information
     */
    public static function getCacheSize()
    {
        $cacheSize = 0;
        $cacheCount = 0;

        // This is a simplified version - in production you might want to use Redis commands
        // or other cache-specific methods to get accurate size information
        
        return [
            'estimated_size' => 'N/A (requires Redis/Memcached specific commands)',
            'estimated_count' => $cacheCount,
            'note' => 'Use Redis INFO or Memcached stats for accurate cache size information'
        ];
    }

    /**
     * Clear expired cache entries
     */
    public static function clearExpiredCaches()
    {
        // This would typically be handled automatically by the cache driver
        // but we can implement custom logic if needed
        
        return true;
    }
}
