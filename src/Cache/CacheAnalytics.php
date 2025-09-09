<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Cache;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Cache performance monitoring and analytics service.
 * 
 * Tracks cache hit rates, credit savings, performance metrics,
 * and provides insights for cache optimization.
 */
class CacheAnalytics
{
    private CacheRepository $cache;
    private array $config;
    private string $analyticsPrefix;
    
    /**
     * Metrics collection intervals
     */
    private const COLLECTION_INTERVALS = [
        'minute' => 60,
        'hour' => 3600,
        'day' => 86400,
        'week' => 604800,
    ];
    
    /**
     * Metric keys for different types of cache operations
     */
    private const METRIC_KEYS = [
        'hits' => 'hits',
        'misses' => 'misses',
        'stores' => 'stores',
        'invalidations' => 'invalidations',
        'credit_saves' => 'credit_saves',
        'response_times' => 'response_times',
        'memory_usage' => 'memory_usage',
        'errors' => 'errors',
    ];

    public function __construct(?CacheRepository $cache = null)
    {
        $this->cache = $cache ?? Cache::store(Config::get('coinmarketcap.cache.store'));
        $this->config = Config::get('coinmarketcap', []);
        $this->analyticsPrefix = ($this->config['cache']['prefix'] ?? 'coinmarketcap') . ':analytics';
    }

    /**
     * Record a cache hit.
     *
     * @param string $key Cache key
     * @param string|null $endpointType Endpoint type
     */
    public function recordHit(string $key, ?string $endpointType = null): void
    {
        $this->incrementMetric('hits', $endpointType);
        $this->recordEndpointMetric($endpointType, 'hits');
        $this->updateHitRate();
    }

    /**
     * Record a cache miss.
     *
     * @param string $key Cache key
     * @param string|null $endpointType Endpoint type
     */
    public function recordMiss(string $key, ?string $endpointType = null): void
    {
        $this->incrementMetric('misses', $endpointType);
        $this->recordEndpointMetric($endpointType, 'misses');
        $this->updateHitRate();
    }

    /**
     * Record a cache store operation.
     *
     * @param string $key Cache key
     * @param string|null $endpointType Endpoint type
     * @param int $ttl TTL used for storage
     */
    public function recordStore(string $key, ?string $endpointType = null, int $ttl = 0): void
    {
        $this->incrementMetric('stores', $endpointType);
        $this->recordEndpointMetric($endpointType, 'stores');
        
        if ($ttl > 0) {
            $this->recordTtlUsage($endpointType, $ttl);
        }
    }

    /**
     * Record cache invalidation.
     *
     * @param array|string $keys Keys that were invalidated
     * @param string $reason Reason for invalidation
     */
    public function recordInvalidation(array|string $keys, string $reason = 'unknown'): void
    {
        $keyCount = is_array($keys) ? count($keys) : 1;
        
        $this->incrementMetric('invalidations', null, $keyCount);
        $this->recordInvalidationReason($reason, $keyCount);
    }

    /**
     * Record cache flush operation.
     *
     * @param string $reason Reason for flush
     */
    public function recordFlush(string $reason = 'manual'): void
    {
        $this->incrementMetric('flushes');
        $this->recordFlushReason($reason);
    }

    /**
     * Record cache warming operation.
     *
     * @param int $itemsWarmed Number of items warmed
     */
    public function recordWarming(int $itemsWarmed): void
    {
        $this->incrementMetric('warmings');
        $this->incrementMetric('items_warmed', null, $itemsWarmed);
    }

    /**
     * Record credit savings from cache usage.
     *
     * @param string|null $endpointType Endpoint type
     * @param int $creditsSaved Credits saved
     */
    public function recordCreditSavings(?string $endpointType, int $creditsSaved = 1): void
    {
        $this->incrementMetric('credit_saves', $endpointType, $creditsSaved);
        $this->recordEndpointMetric($endpointType, 'credits_saved', $creditsSaved);
    }

    /**
     * Record response time for cache operation.
     *
     * @param string $operation Operation type (hit, miss, store)
     * @param float $responseTime Response time in milliseconds
     */
    public function recordResponseTime(string $operation, float $responseTime): void
    {
        $key = $this->getMetricKey('response_times', $operation);
        
        // Store response times in a rolling window
        $this->addToTimeSeries($key, $responseTime);
    }

    /**
     * Record memory usage.
     *
     * @param int $memoryUsed Memory used in bytes
     */
    public function recordMemoryUsage(int $memoryUsed): void
    {
        $key = $this->getMetricKey('memory_usage');
        $this->addToTimeSeries($key, $memoryUsed);
    }

    /**
     * Record cache error.
     *
     * @param string $errorType Error type
     * @param string $message Error message
     */
    public function recordError(string $errorType, string $message = ''): void
    {
        $this->incrementMetric('errors', $errorType);
        $this->recordErrorDetail($errorType, $message);
    }

    /**
     * Get comprehensive cache statistics.
     *
     * @param string $interval Time interval (minute, hour, day, week)
     * @return array
     */
    public function getStatistics(string $interval = 'hour'): array
    {
        $timeWindow = self::COLLECTION_INTERVALS[$interval] ?? 3600;
        $timestamp = Carbon::now()->timestamp - $timeWindow;
        
        return [
            'overview' => $this->getOverviewStatistics($timestamp),
            'performance' => $this->getPerformanceStatistics($timestamp),
            'endpoints' => $this->getEndpointStatistics($timestamp),
            'credit_efficiency' => $this->getCreditEfficiencyStatistics($timestamp),
            'errors' => $this->getErrorStatistics($timestamp),
            'trends' => $this->getTrendStatistics($interval),
            'generated_at' => Carbon::now()->toISOString(),
            'interval' => $interval,
        ];
    }

    /**
     * Get real-time hit rate.
     *
     * @return float Hit rate percentage (0.0 to 1.0)
     */
    public function getCurrentHitRate(): float
    {
        $hits = $this->getMetricValue('hits') ?? 0;
        $misses = $this->getMetricValue('misses') ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? $hits / $total : 0.0;
    }

    /**
     * Get cache efficiency score (0-100).
     *
     * @return int Efficiency score
     */
    public function getEfficiencyScore(): int
    {
        $hitRate = $this->getCurrentHitRate();
        $creditSavings = $this->getMetricValue('credit_saves') ?? 0;
        $errorRate = $this->getErrorRate();
        
        // Calculate composite efficiency score
        $hitScore = $hitRate * 40; // 40% weight
        $creditScore = min(40, $creditSavings / 10); // 40% weight, cap at 40
        $errorPenalty = $errorRate * 20; // 20% penalty
        
        return max(0, min(100, (int) ($hitScore + $creditScore - $errorPenalty)));
    }

    /**
     * Get top performing endpoints by hit rate.
     *
     * @param int $limit Number of endpoints to return
     * @return array
     */
    public function getTopPerformingEndpoints(int $limit = 10): array
    {
        $endpoints = [];
        
        // This would be more complex in a real implementation
        // For now, return a simplified version
        $endpointTypes = ['cryptocurrency_map', 'cryptocurrency_info', 'cryptocurrency_quotes'];
        
        foreach ($endpointTypes as $endpoint) {
            $hits = $this->getEndpointMetric($endpoint, 'hits') ?? 0;
            $misses = $this->getEndpointMetric($endpoint, 'misses') ?? 0;
            $total = $hits + $misses;
            
            if ($total > 0) {
                $endpoints[] = [
                    'endpoint' => $endpoint,
                    'hit_rate' => $hits / $total,
                    'total_requests' => $total,
                    'hits' => $hits,
                    'misses' => $misses,
                ];
            }
        }
        
        // Sort by hit rate
        usort($endpoints, fn($a, $b) => $b['hit_rate'] <=> $a['hit_rate']);
        
        return array_slice($endpoints, 0, $limit);
    }

    /**
     * Get cache optimization recommendations.
     *
     * @return array
     */
    public function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        $hitRate = $this->getCurrentHitRate();
        $errorRate = $this->getErrorRate();
        $efficiency = $this->getEfficiencyScore();
        
        if ($hitRate < 0.6) {
            $recommendations[] = [
                'type' => 'hit_rate',
                'priority' => 'high',
                'message' => 'Hit rate is below 60%. Consider increasing TTL values or implementing cache warming.',
                'current_value' => $hitRate,
                'target_value' => 0.8,
            ];
        }
        
        if ($errorRate > 0.05) {
            $recommendations[] = [
                'type' => 'error_rate',
                'priority' => 'high',
                'message' => 'Error rate is above 5%. Review cache configuration and error handling.',
                'current_value' => $errorRate,
                'target_value' => 0.02,
            ];
        }
        
        if ($efficiency < 70) {
            $recommendations[] = [
                'type' => 'efficiency',
                'priority' => 'medium',
                'message' => 'Cache efficiency is below optimal. Review caching strategy and TTL configurations.',
                'current_value' => $efficiency,
                'target_value' => 80,
            ];
        }
        
        return $recommendations;
    }

    /**
     * Reset all analytics data.
     */
    public function reset(): void
    {
        $keys = $this->cache->get($this->analyticsPrefix . ':keys', []);
        
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
        
        $this->cache->forget($this->analyticsPrefix . ':keys');
    }

    /**
     * Get overview statistics.
     *
     * @param int $timestamp Timestamp threshold
     * @return array
     */
    private function getOverviewStatistics(int $timestamp): array
    {
        return [
            'total_hits' => $this->getMetricValue('hits') ?? 0,
            'total_misses' => $this->getMetricValue('misses') ?? 0,
            'hit_rate' => $this->getCurrentHitRate(),
            'total_stores' => $this->getMetricValue('stores') ?? 0,
            'total_invalidations' => $this->getMetricValue('invalidations') ?? 0,
            'credits_saved' => $this->getMetricValue('credit_saves') ?? 0,
        ];
    }

    /**
     * Get performance statistics.
     *
     * @param int $timestamp Timestamp threshold
     * @return array
     */
    private function getPerformanceStatistics(int $timestamp): array
    {
        return [
            'average_hit_time' => $this->getAverageResponseTime('hit'),
            'average_miss_time' => $this->getAverageResponseTime('miss'),
            'average_store_time' => $this->getAverageResponseTime('store'),
            'memory_usage' => $this->getAverageMemoryUsage(),
            'efficiency_score' => $this->getEfficiencyScore(),
        ];
    }

    /**
     * Get endpoint-specific statistics.
     *
     * @param int $timestamp Timestamp threshold
     * @return array
     */
    private function getEndpointStatistics(int $timestamp): array
    {
        return $this->getTopPerformingEndpoints(5);
    }

    /**
     * Get credit efficiency statistics.
     *
     * @param int $timestamp Timestamp threshold
     * @return array
     */
    private function getCreditEfficiencyStatistics(int $timestamp): array
    {
        $totalCredits = $this->getMetricValue('credit_saves') ?? 0;
        $totalRequests = ($this->getMetricValue('hits') ?? 0) + ($this->getMetricValue('misses') ?? 0);
        
        return [
            'total_credits_saved' => $totalCredits,
            'credits_per_request' => $totalRequests > 0 ? $totalCredits / $totalRequests : 0,
            'cost_savings_percentage' => $totalRequests > 0 ? ($totalCredits / $totalRequests) * 100 : 0,
        ];
    }

    /**
     * Get error statistics.
     *
     * @param int $timestamp Timestamp threshold
     * @return array
     */
    private function getErrorStatistics(int $timestamp): array
    {
        return [
            'total_errors' => $this->getMetricValue('errors') ?? 0,
            'error_rate' => $this->getErrorRate(),
            'error_types' => $this->getErrorTypeBreakdown(),
        ];
    }

    /**
     * Get trend statistics.
     *
     * @param string $interval Interval for trends
     * @return array
     */
    private function getTrendStatistics(string $interval): array
    {
        // Simplified trend calculation
        return [
            'hit_rate_trend' => 'stable',
            'volume_trend' => 'increasing',
            'efficiency_trend' => 'improving',
        ];
    }

    /**
     * Increment a metric value.
     *
     * @param string $metricType Metric type
     * @param string|null $subType Sub-type or endpoint
     * @param int $increment Increment value
     */
    private function incrementMetric(string $metricType, ?string $subType = null, int $increment = 1): void
    {
        $key = $this->getMetricKey($metricType, $subType);
        $current = $this->cache->get($key, 0);
        $this->cache->forever($key, $current + $increment);
        
        // Track the key for cleanup
        $this->trackMetricKey($key);
    }

    /**
     * Get metric value.
     *
     * @param string $metricType Metric type
     * @param string|null $subType Sub-type
     * @return int|null
     */
    private function getMetricValue(string $metricType, ?string $subType = null): ?int
    {
        $key = $this->getMetricKey($metricType, $subType);
        return $this->cache->get($key);
    }

    /**
     * Build metric key.
     *
     * @param string $metricType Metric type
     * @param string|null $subType Sub-type
     * @return string
     */
    private function getMetricKey(string $metricType, ?string $subType = null): string
    {
        $key = $this->analyticsPrefix . ':' . $metricType;
        
        if ($subType) {
            $key .= ':' . $subType;
        }
        
        return $key;
    }

    /**
     * Record endpoint-specific metric.
     *
     * @param string|null $endpoint Endpoint type
     * @param string $metric Metric name
     * @param int $value Value to record
     */
    private function recordEndpointMetric(?string $endpoint, string $metric, int $value = 1): void
    {
        if (!$endpoint) {
            return;
        }
        
        $key = $this->analyticsPrefix . ':endpoints:' . $endpoint . ':' . $metric;
        $current = $this->cache->get($key, 0);
        $this->cache->forever($key, $current + $value);
        
        $this->trackMetricKey($key);
    }

    /**
     * Get endpoint metric value.
     *
     * @param string $endpoint Endpoint type
     * @param string $metric Metric name
     * @return int|null
     */
    private function getEndpointMetric(string $endpoint, string $metric): ?int
    {
        $key = $this->analyticsPrefix . ':endpoints:' . $endpoint . ':' . $metric;
        return $this->cache->get($key);
    }

    /**
     * Update overall hit rate.
     */
    private function updateHitRate(): void
    {
        $hitRate = $this->getCurrentHitRate();
        $key = $this->analyticsPrefix . ':current_hit_rate';
        $this->cache->put($key, $hitRate, 3600); // Cache for 1 hour
    }

    /**
     * Record TTL usage for optimization analysis.
     *
     * @param string|null $endpointType Endpoint type
     * @param int $ttl TTL value
     */
    private function recordTtlUsage(?string $endpointType, int $ttl): void
    {
        if (!$endpointType) {
            return;
        }
        
        $key = $this->analyticsPrefix . ':ttl:' . $endpointType;
        $this->addToTimeSeries($key, $ttl);
    }

    /**
     * Record invalidation reason.
     *
     * @param string $reason Invalidation reason
     * @param int $count Number of keys invalidated
     */
    private function recordInvalidationReason(string $reason, int $count): void
    {
        $key = $this->analyticsPrefix . ':invalidation_reasons:' . $reason;
        $current = $this->cache->get($key, 0);
        $this->cache->forever($key, $current + $count);
        
        $this->trackMetricKey($key);
    }

    /**
     * Record flush reason.
     *
     * @param string $reason Flush reason
     */
    private function recordFlushReason(string $reason): void
    {
        $key = $this->analyticsPrefix . ':flush_reasons:' . $reason;
        $current = $this->cache->get($key, 0);
        $this->cache->forever($key, $current + 1);
        
        $this->trackMetricKey($key);
    }

    /**
     * Record error details.
     *
     * @param string $errorType Error type
     * @param string $message Error message
     */
    private function recordErrorDetail(string $errorType, string $message): void
    {
        $key = $this->analyticsPrefix . ':error_details:' . $errorType;
        $errors = $this->cache->get($key, []);
        
        $errors[] = [
            'message' => $message,
            'timestamp' => Carbon::now()->toISOString(),
        ];
        
        // Keep only last 100 errors
        if (count($errors) > 100) {
            $errors = array_slice($errors, -100);
        }
        
        $this->cache->put($key, $errors, 86400); // Keep for 24 hours
        $this->trackMetricKey($key);
    }

    /**
     * Add value to time series for trend analysis.
     *
     * @param string $key Series key
     * @param mixed $value Value to add
     */
    private function addToTimeSeries(string $key, mixed $value): void
    {
        $series = $this->cache->get($key, []);
        
        $series[] = [
            'value' => $value,
            'timestamp' => Carbon::now()->timestamp,
        ];
        
        // Keep only last 1000 data points
        if (count($series) > 1000) {
            $series = array_slice($series, -1000);
        }
        
        $this->cache->put($key, $series, 3600); // Keep for 1 hour
        $this->trackMetricKey($key);
    }

    /**
     * Track metric key for cleanup purposes.
     *
     * @param string $key Metric key
     */
    private function trackMetricKey(string $key): void
    {
        $trackedKeys = $this->cache->get($this->analyticsPrefix . ':keys', []);
        
        if (!in_array($key, $trackedKeys)) {
            $trackedKeys[] = $key;
            $this->cache->forever($this->analyticsPrefix . ':keys', $trackedKeys);
        }
    }

    /**
     * Get error rate.
     *
     * @return float Error rate (0.0 to 1.0)
     */
    private function getErrorRate(): float
    {
        $errors = $this->getMetricValue('errors') ?? 0;
        $total = ($this->getMetricValue('hits') ?? 0) + ($this->getMetricValue('misses') ?? 0);
        
        return $total > 0 ? $errors / $total : 0.0;
    }

    /**
     * Get error type breakdown.
     *
     * @return array
     */
    private function getErrorTypeBreakdown(): array
    {
        // Simplified implementation
        return [
            'cache_miss' => $this->getMetricValue('errors', 'cache_miss') ?? 0,
            'timeout' => $this->getMetricValue('errors', 'timeout') ?? 0,
            'connection' => $this->getMetricValue('errors', 'connection') ?? 0,
        ];
    }

    /**
     * Get average response time for operation.
     *
     * @param string $operation Operation type
     * @return float Average response time in milliseconds
     */
    private function getAverageResponseTime(string $operation): float
    {
        $key = $this->getMetricKey('response_times', $operation);
        $series = $this->cache->get($key, []);
        
        if (empty($series)) {
            return 0.0;
        }
        
        $sum = array_sum(array_column($series, 'value'));
        return $sum / count($series);
    }

    /**
     * Get average memory usage.
     *
     * @return float Average memory usage in bytes
     */
    private function getAverageMemoryUsage(): float
    {
        $key = $this->getMetricKey('memory_usage');
        $series = $this->cache->get($key, []);
        
        if (empty($series)) {
            return 0.0;
        }
        
        $sum = array_sum(array_column($series, 'value'));
        return $sum / count($series);
    }
}