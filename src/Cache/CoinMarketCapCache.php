<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;

/**
 * Credit-optimized caching service for CoinMarketCap API responses.
 * 
 * Implements intelligent caching strategies with different TTL configurations
 * based on data type and credit costs to minimize API calls while maintaining
 * data freshness.
 */
class CoinMarketCapCache
{
    private CacheRepository $cache;
    private CacheAnalytics $analytics;
    private LoggerInterface $logger;
    private array $config;
    private string $cachePrefix;

    /**
     * Cache key patterns for different endpoint types
     */
    private const KEY_PATTERNS = [
        'static' => ['map', 'info', 'fiat'],
        'semi_dynamic' => ['listings', 'trending', 'global_metrics'],
        'real_time' => ['quotes', 'pairs'],
        'market_data' => ['ohlcv', 'market_pairs'],
        'historical' => ['historical'],
    ];

    /**
     * Credit cost mapping for different endpoint types
     */
    private const CREDIT_COSTS = [
        'cryptocurrency_map' => 1,
        'cryptocurrency_info' => 1,
        'cryptocurrency_listings' => 1,
        'cryptocurrency_quotes' => 1,
        'exchange_map' => 1,
        'exchange_info' => 1,
        'exchange_listings' => 1,
        'exchange_quotes' => 1,
        'global_metrics' => 1,
        'fiat_map' => 1,
        'market_pairs' => 1,
        'ohlcv' => 1,
        'historical' => 1,
        'trending' => 1,
    ];

    public function __construct(
        ?CacheRepository $cache = null,
        ?CacheAnalytics $analytics = null,
        ?LoggerInterface $logger = null
    ) {
        $this->cache = $cache ?? Cache::store(Config::get('coinmarketcap.cache.store'));
        $this->analytics = $analytics ?? new CacheAnalytics($this->cache);
        $this->logger = $logger ?? app(LoggerInterface::class);
        $this->config = Config::get('coinmarketcap', []);
        $this->cachePrefix = $this->config['cache']['prefix'] ?? 'coinmarketcap';
    }

    /**
     * Get data from cache or execute callback if not cached.
     *
     * @param string $key Cache key
     * @param callable $callback Callback to execute if cache miss
     * @param string|null $endpointType Endpoint type for TTL selection
     * @param array $options Additional options
     * @return mixed
     */
    public function remember(
        string $key, 
        callable $callback, 
        ?string $endpointType = null, 
        array $options = []
    ): mixed {
        $fullKey = $this->buildCacheKey($key);
        $ttl = $this->getTtlForEndpoint($endpointType, $options);
        
        // Check if caching is enabled
        if (!$this->isCachingEnabled()) {
            $this->analytics->recordMiss($fullKey, 'caching_disabled');
            return $callback();
        }

        // Try to get from cache
        $cachedValue = $this->cache->get($fullKey);
        
        if ($cachedValue !== null) {
            $this->analytics->recordHit($fullKey, $endpointType);
            $this->logCacheOperation('hit', $fullKey, $endpointType);
            return $cachedValue;
        }

        // Cache miss - execute callback and store result
        $this->analytics->recordMiss($fullKey, $endpointType ?? 'unknown');
        
        try {
            $result = $callback();
            
            if ($result !== null) {
                $this->cache->put($fullKey, $result, $ttl);
                $this->analytics->recordStore($fullKey, $endpointType, $ttl);
                $this->logCacheOperation('store', $fullKey, $endpointType, ['ttl' => $ttl]);
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cache callback execution failed', [
                'key' => $fullKey,
                'endpoint' => $endpointType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Store data in cache with appropriate TTL.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string|null $endpointType Endpoint type for TTL selection
     * @param array $options Additional options
     * @return bool
     */
    public function put(
        string $key, 
        mixed $value, 
        ?string $endpointType = null, 
        array $options = []
    ): bool {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        $fullKey = $this->buildCacheKey($key);
        $ttl = $this->getTtlForEndpoint($endpointType, $options);
        
        $result = $this->cache->put($fullKey, $value, $ttl);
        
        if ($result) {
            $this->analytics->recordStore($fullKey, $endpointType, $ttl);
            $this->logCacheOperation('store', $fullKey, $endpointType, ['ttl' => $ttl]);
        }
        
        return $result;
    }

    /**
     * Get data from cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $fullKey = $this->buildCacheKey($key);
        
        if (!$this->isCachingEnabled()) {
            return $default;
        }
        
        $value = $this->cache->get($fullKey, $default);
        
        if ($value !== $default) {
            $this->analytics->recordHit($fullKey);
        } else {
            $this->analytics->recordMiss($fullKey);
        }
        
        return $value;
    }

    /**
     * Remove item from cache.
     *
     * @param string $key Cache key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->buildCacheKey($key);
        
        $result = $this->cache->forget($fullKey);
        
        if ($result) {
            $this->analytics->recordInvalidation($fullKey, 'manual');
            $this->logCacheOperation('forget', $fullKey);
        }
        
        return $result;
    }

    /**
     * Flush cache entries matching pattern.
     *
     * @param string $pattern Pattern to match keys
     * @return int Number of keys flushed
     */
    public function flush(string $pattern = '*'): int
    {
        $fullPattern = $this->buildCacheKey($pattern);
        
        // Laravel doesn't have a built-in way to flush by pattern
        // This would need to be implemented based on the cache driver
        // For now, we'll implement a basic flush all
        if ($pattern === '*') {
            $this->cache->flush();
            $this->analytics->recordFlush('manual');
            $this->logCacheOperation('flush_all');
            return 1; // Approximation
        }
        
        return 0;
    }

    /**
     * Invalidate cache for real-time data endpoints.
     *
     * @param array $symbols Specific symbols to invalidate
     * @return int Number of keys invalidated
     */
    public function invalidateRealTimeData(array $symbols = []): int
    {
        $patterns = $this->getRealTimePatterns($symbols);
        $count = 0;
        
        foreach ($patterns as $pattern) {
            if ($this->forget($pattern)) {
                $count++;
            }
        }
        
        $this->analytics->recordInvalidation($patterns, 'real_time_invalidation');
        
        return $count;
    }

    /**
     * Warm cache with preloaded data.
     *
     * @param array $warmingData Data to warm cache with
     * @return int Number of items warmed
     */
    public function warm(array $warmingData): int
    {
        $count = 0;
        
        foreach ($warmingData as $item) {
            $key = $item['key'] ?? null;
            $value = $item['value'] ?? null;
            $endpointType = $item['endpoint_type'] ?? null;
            
            if ($key && $value !== null) {
                if ($this->put($key, $value, $endpointType)) {
                    $count++;
                }
            }
        }
        
        $this->analytics->recordWarming($count);
        $this->logCacheOperation('warm', '', null, ['count' => $count]);
        
        return $count;
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->analytics->getStatistics();
    }

    /**
     * Check if an endpoint should use cache based on credit cost.
     *
     * @param string $endpointType Endpoint type
     * @param int $creditCost Credit cost of the endpoint
     * @return bool
     */
    public function shouldCache(string $endpointType, int $creditCost = 1): bool
    {
        if (!$this->isCachingEnabled()) {
            return false;
        }

        // Always cache high-cost endpoints
        if ($creditCost > 1) {
            return true;
        }

        // Cache based on endpoint type priority
        return match ($this->getEndpointCategory($endpointType)) {
            'static' => true,
            'semi_dynamic' => true,
            'real_time' => $this->shouldCacheRealTime(),
            'market_data' => true,
            'historical' => true,
            default => true,
        };
    }

    /**
     * Build full cache key with prefix.
     *
     * @param string $key Base key
     * @return string
     */
    private function buildCacheKey(string $key): string
    {
        return $this->cachePrefix . ':' . $key;
    }

    /**
     * Get TTL for endpoint type.
     *
     * @param string|null $endpointType Endpoint type
     * @param array $options Additional options
     * @return int TTL in seconds
     */
    private function getTtlForEndpoint(?string $endpointType, array $options = []): int
    {
        // Check for explicit TTL in options
        if (isset($options['ttl'])) {
            return (int) $options['ttl'];
        }

        if (!$endpointType) {
            return $this->config['cache']['ttl']['default'] ?? 300; // 5 minutes default
        }

        $ttlConfig = $this->config['cache']['ttl'] ?? [];
        
        // Try exact match first
        if (isset($ttlConfig[$endpointType])) {
            return $ttlConfig[$endpointType];
        }

        // Try category-based matching
        $category = $this->getEndpointCategory($endpointType);
        
        return match ($category) {
            'static' => $ttlConfig['cryptocurrency_map'] ?? 86400, // 24 hours
            'semi_dynamic' => $ttlConfig['cryptocurrency_listings'] ?? 300, // 5 minutes
            'real_time' => $ttlConfig['cryptocurrency_quotes'] ?? 60, // 1 minute
            'market_data' => $ttlConfig['ohlcv'] ?? 300, // 5 minutes
            'historical' => $ttlConfig['historical'] ?? 3600, // 1 hour
            default => 300, // 5 minutes default
        };
    }

    /**
     * Get endpoint category based on patterns.
     *
     * @param string $endpointType Endpoint type
     * @return string
     */
    private function getEndpointCategory(string $endpointType): string
    {
        foreach (self::KEY_PATTERNS as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($endpointType, $pattern) !== false) {
                    return $category;
                }
            }
        }
        
        return 'unknown';
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    private function isCachingEnabled(): bool
    {
        return $this->config['cache']['enabled'] ?? true;
    }

    /**
     * Check if real-time data should be cached.
     *
     * @return bool
     */
    private function shouldCacheRealTime(): bool
    {
        // Cache real-time data only for short periods during high load
        $currentHour = Carbon::now()->hour;
        $isHighTrafficHour = $currentHour >= 8 && $currentHour <= 20; // Business hours
        
        return $isHighTrafficHour;
    }

    /**
     * Get patterns for real-time data invalidation.
     *
     * @param array $symbols Specific symbols
     * @return array
     */
    private function getRealTimePatterns(array $symbols): array
    {
        $patterns = ['quotes:*', 'market_pairs:*'];
        
        if (!empty($symbols)) {
            foreach ($symbols as $symbol) {
                $patterns[] = "quotes:{$symbol}:*";
                $patterns[] = "market_pairs:{$symbol}:*";
            }
        }
        
        return $patterns;
    }

    /**
     * Log cache operation.
     *
     * @param string $operation Operation type
     * @param string $key Cache key
     * @param string|null $endpointType Endpoint type
     * @param array $context Additional context
     */
    private function logCacheOperation(
        string $operation, 
        string $key, 
        ?string $endpointType = null, 
        array $context = []
    ): void {
        if (!($this->config['logging']['enabled'] ?? true)) {
            return;
        }

        $this->logger->info("Cache operation: {$operation}", array_merge([
            'operation' => $operation,
            'key' => $key,
            'endpoint_type' => $endpointType,
            'timestamp' => Carbon::now()->toISOString(),
        ], $context));
    }
}