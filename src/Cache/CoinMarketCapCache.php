<?php

namespace Convertain\CoinMarketCap\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * CoinMarketCap Cache Manager
 * 
 * Handles caching for CoinMarketCap API responses to reduce credit consumption
 */
class CoinMarketCapCache
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->config['cache']['enabled']) {
            return $callback();
        }

        $cacheKey = $this->generateKey($key);
        $ttl = $ttl ?? $this->getTtl($key);

        return Cache::store($this->config['cache']['store'])
            ->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Store data in cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->config['cache']['enabled']) {
            return false;
        }

        $cacheKey = $this->generateKey($key);
        $ttl = $ttl ?? $this->getTtl($key);

        return Cache::store($this->config['cache']['store'])
            ->put($cacheKey, $value, $ttl);
    }

    /**
     * Get data from cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->config['cache']['enabled']) {
            return $default;
        }

        $cacheKey = $this->generateKey($key);

        return Cache::store($this->config['cache']['store'])
            ->get($cacheKey, $default);
    }

    /**
     * Remove data from cache
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->generateKey($key);

        return Cache::store($this->config['cache']['store'])
            ->forget($cacheKey);
    }

    /**
     * Clear all cache data
     */
    public function flush(): bool
    {
        return Cache::store($this->config['cache']['store'])
            ->flush();
    }

    /**
     * Generate cache key with prefix
     */
    private function generateKey(string $key): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'coinmarketcap';
        return "{$prefix}:{$key}";
    }

    /**
     * Get TTL for a specific cache key
     */
    private function getTtl(string $key): int
    {
        // Match endpoint to TTL configuration
        foreach ($this->config['cache']['ttl'] as $endpoint => $ttl) {
            if (str_contains($key, $endpoint)) {
                return $ttl;
            }
        }

        // Default TTL
        return 300; // 5 minutes
    }
}