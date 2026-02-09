<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CacheService;

class CacheController extends Controller
{
    /**
     * Get cache statistics
     */
    public function stats()
    {
        try {
            $stats = CacheService::getCacheStats();
            $cacheSize = CacheService::getCacheSize();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'cache_size' => $cacheSize,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cache statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all caches
     */
    public function clearAll()
    {
        try {
            CacheService::clearAllCaches();

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear all caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear product caches
     */
    public function clearProducts()
    {
        try {
            CacheService::clearProductCaches();

            return response()->json([
                'success' => true,
                'message' => 'Product caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear product caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear order caches
     */
    public function clearOrders()
    {
        try {
            CacheService::clearOrderCaches();

            return response()->json([
                'success' => true,
                'message' => 'Order caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear order caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear user caches
     */
    public function clearUsers()
    {
        try {
            CacheService::clearUserCaches();

            return response()->json([
                'success' => true,
                'message' => 'User caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear user caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm up caches
     */
    public function warmUp()
    {
        try {
            CacheService::warmUpCaches();

            return response()->json([
                'success' => true,
                'message' => 'Cache warm-up completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up caches: ' . $e->getMessage()
            ], 500);
        }
    }
}
