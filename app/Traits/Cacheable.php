<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

trait Cacheable
{
    /**
     * Get cache key for a model
     */
    protected function getCacheKey(string $key = ''): string
    {
        $className = class_basename(static::class);
        $modelKey = $this->getKey();
        return strtolower($className) . ($modelKey ? "_{$modelKey}" : '') . ($key ? "_{$key}" : '');
    }

    /**
     * Get cache key for a collection
     */
    protected static function getCollectionCacheKey(string $key = ''): string
    {
        $className = class_basename(static::class);
        return strtolower($className) . 's' . ($key ? "_{$key}" : '');
    }

    /**
     * Cache a model instance
     */
    protected function cacheModel(string $key = '', int $ttl = 3600): void
    {
        $cacheKey = $this->getCacheKey($key);
        Cache::put($cacheKey, $this->fresh(), $ttl);
    }

    /**
     * Cache a collection
     */
    protected static function cacheCollection(string $key, $data, int $ttl = 3600): void
    {
        $cacheKey = static::getCollectionCacheKey($key);
        Cache::put($cacheKey, $data, $ttl);
    }

    /**
     * Get cached model
     */
    protected static function getCachedModel(int $id, string $key = '')
    {
        $className = class_basename(static::class);
        $cacheKey = strtolower($className) . "_{$id}" . ($key ? "_{$key}" : '');
        return Cache::get($cacheKey);
    }

    /**
     * Get cached collection
     */
    protected static function getCachedCollection(string $key = '')
    {
        $cacheKey = static::getCollectionCacheKey($key);
        return Cache::get($cacheKey);
    }

    /**
     * Forget model cache
     */
    protected function forgetModelCache(string $key = ''): void
    {
        $cacheKey = $this->getCacheKey($key);
        Cache::forget($cacheKey);
    }

    /**
     * Forget collection cache
     */
    protected static function forgetCollectionCache(string $key = ''): void
    {
        $cacheKey = static::getCollectionCacheKey($key);
        Cache::forget($cacheKey);
    }

    /**
     * Forget all related caches
     */
    protected function forgetAllCaches(): void
    {
        // Forget individual model caches
        $this->forgetModelCache();
        
        // Forget collection caches
        static::forgetCollectionCache();
        static::forgetCollectionCache('active');
        static::forgetCollectionCache('latest');
        
        // Forget any other custom keys
        $this->forgetModelCache('with_relations');
        $this->forgetModelCache('for_admin');
    }

    /**
     * Boot the trait
     */
    protected static function bootCacheable()
    {
        static::created(function (Model $model) {
            $model->forgetAllCaches();
        });

        static::updated(function (Model $model) {
            $model->forgetAllCaches();
        });

        static::deleted(function (Model $model) {
            $model->forgetAllCaches();
        });
    }
}
