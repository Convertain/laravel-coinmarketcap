# Performance Optimization Guide

Advanced strategies for optimizing the performance of your Laravel CoinMarketCap integration while minimizing credit consumption and maximizing response times.

## Table of Contents

- [Performance Overview](#performance-overview)
- [Caching Optimization](#caching-optimization)
- [Request Optimization](#request-optimization)
- [Memory Management](#memory-management)
- [Database Optimization](#database-optimization)
- [Monitoring and Metrics](#monitoring-and-metrics)
- [Scaling Strategies](#scaling-strategies)
- [Production Best Practices](#production-best-practices)

## Performance Overview

### Key Performance Metrics

1. **Response Time**: < 200ms for cached requests, < 2s for fresh API calls
2. **Credit Efficiency**: Minimize API calls through intelligent caching
3. **Memory Usage**: Keep memory footprint under control for large datasets
4. **Throughput**: Handle high concurrent request volumes
5. **Cache Hit Rate**: Target >90% cache hit rate for frequently accessed data

### Performance Optimization Checklist

- [x] Implement multi-tier caching strategy
- [x] Use batch requests for multiple symbols
- [x] Optimize database queries and indexes
- [x] Implement connection pooling
- [x] Monitor credit usage and API performance
- [x] Use background job processing for heavy operations
- [x] Implement circuit breakers for reliability
- [x] Optimize memory usage for large datasets

## Caching Optimization

### Multi-Tier Cache Architecture

```php
<?php

class MultiTierCacheManager
{
    protected $redis;
    protected $memcached;
    protected $database;
    
    public function __construct()
    {
        $this->redis = Redis::connection('cache');
        $this->memcached = Cache::store('memcached');
        $this->database = Cache::store('database');
    }
    
    /**
     * Implement L1 (Redis) -> L2 (Memcached) -> L3 (Database) -> API fallback
     */
    public function getCryptocurrencyWithTiers($symbol, $options = [])
    {
        $cacheKey = $this->generateCacheKey($symbol, $options);
        
        // L1 Cache: Redis (fastest)
        $data = $this->redis->get($cacheKey);
        if ($data) {
            $this->recordCacheHit('L1_redis');
            return unserialize($data);
        }
        
        // L2 Cache: Memcached
        $data = $this->memcached->get($cacheKey);
        if ($data) {
            // Backfill L1 cache
            $this->redis->setex($cacheKey, 300, serialize($data));
            $this->recordCacheHit('L2_memcached');
            return $data;
        }
        
        // L3 Cache: Database
        $data = $this->database->get($cacheKey);
        if ($data) {
            // Backfill L2 and L1
            $this->memcached->put($cacheKey, $data, 600);
            $this->redis->setex($cacheKey, 300, serialize($data));
            $this->recordCacheHit('L3_database');
            return $data;
        }
        
        // Cache miss: Fetch from API
        $data = app(CoinMarketCapProvider::class)->getCryptocurrency($symbol, $options);
        
        // Store in all cache tiers
        $this->database->put($cacheKey, $data, 3600);
        $this->memcached->put($cacheKey, $data, 600);
        $this->redis->setex($cacheKey, 300, serialize($data));
        
        $this->recordCacheMiss();
        
        return $data;
    }
    
    protected function recordCacheHit($tier)
    {
        Redis::hincrby('cache_metrics', "hits_{$tier}", 1);
        Redis::hincrby('cache_metrics', 'total_hits', 1);
    }
    
    protected function recordCacheMiss()
    {
        Redis::hincrby('cache_metrics', 'misses', 1);
        Redis::hincrby('cache_metrics', 'api_calls', 1);
    }
}
```

### Cache Warming Strategy

```php
<?php

class CacheWarmingService
{
    protected $provider;
    
    public function __construct(CoinMarketCapProvider $provider)
    {
        $this->provider = $provider;
    }
    
    /**
     * Intelligent cache warming based on usage patterns
     */
    public function warmCacheIntelligently()
    {
        $popularSymbols = $this->getPopularSymbols();
        $predictedSymbols = $this->predictUpcomingRequests();
        
        $symbolsToWarm = array_unique(array_merge($popularSymbols, $predictedSymbols));
        
        // Warm in batches to optimize credits
        $batches = array_chunk($symbolsToWarm, 100);
        
        foreach ($batches as $batch) {
            Queue::push(new WarmCacheBatchJob($batch));
        }
        
        Log::info('Cache warming scheduled', [
            'symbols_count' => count($symbolsToWarm),
            'batches' => count($batches),
            'estimated_credits' => count($batches)
        ]);
    }
    
    protected function getPopularSymbols()
    {
        // Get most requested symbols from access logs
        return Redis::zrevrange('symbol_popularity', 0, 49); // Top 50
    }
    
    protected function predictUpcomingRequests()
    {
        // Machine learning model or simple time-based prediction
        $hour = now()->hour;
        
        if ($hour >= 8 && $hour <= 10) {
            // Morning: Focus on major cryptocurrencies
            return ['BTC', 'ETH', 'USDT', 'BNB', 'XRP'];
        } elseif ($hour >= 15 && $hour <= 17) {
            // Afternoon: Include DeFi tokens
            return ['UNI', 'LINK', 'AAVE', 'COMP', 'MKR'];
        } elseif ($hour >= 20 && $hour <= 22) {
            // Evening: Trending/meme coins
            return ['DOGE', 'SHIB', 'PEPE', 'FLOKI'];
        }
        
        return [];
    }
}
```

## Request Optimization

### Connection Pooling

```php
<?php

class ConnectionPoolManager
{
    protected static $pool = [];
    protected static $poolSize = 5;
    
    /**
     * Get connection from pool or create new one
     */
    public static function getConnection()
    {
        if (count(self::$pool) > 0) {
            return array_pop(self::$pool);
        }
        
        return self::createConnection();
    }
    
    /**
     * Return connection to pool
     */
    public static function returnConnection($connection)
    {
        if (count(self::$pool) < self::$poolSize) {
            self::$pool[] = $connection;
        }
    }
    
    protected static function createConnection()
    {
        return new GuzzleHttp\Client([
            'base_uri' => config('coinmarketcap.api.base_url'),
            'timeout' => config('coinmarketcap.api.timeout', 30),
            'headers' => [
                'X-CMC_PRO_API_KEY' => config('coinmarketcap.api.key'),
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }
}
```

### Parallel Request Processing

```php
<?php

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ParallelRequestProcessor
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
    }
    
    /**
     * Process multiple different endpoints in parallel
     */
    public function processParallelRequests(array $requests, int $concurrency = 5)
    {
        $requestGenerator = function () use ($requests) {
            foreach ($requests as $key => $requestData) {
                yield $key => new Request(
                    'GET',
                    $requestData['url'],
                    $requestData['headers'] ?? [],
                    $requestData['body'] ?? null
                );
            }
        };
        
        $responses = [];
        
        $pool = new Pool($this->client, $requestGenerator(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $key) use (&$responses) {
                $responses[$key] = json_decode($response->getBody(), true);
            },
            'rejected' => function ($reason, $key) use (&$responses) {
                Log::error("Parallel request failed for key: {$key}", [
                    'reason' => $reason->getMessage()
                ]);
                $responses[$key] = null;
            },
        ]);
        
        $promise = $pool->promise();
        $promise->wait();
        
        return $responses;
    }
}
```

## Memory Management

### Memory-Efficient Data Processing

```php
<?php

class MemoryEfficientProcessor
{
    /**
     * Process large datasets without memory exhaustion
     */
    public function processLargeDataset(array $symbols)
    {
        $batchSize = 100;
        $memoryLimit = ini_get('memory_limit');
        $memoryThreshold = $this->parseMemoryLimit($memoryLimit) * 0.8; // 80% threshold
        
        foreach (array_chunk($symbols, $batchSize) as $batch) {
            // Check memory usage before processing
            if (memory_get_usage(true) > $memoryThreshold) {
                // Force garbage collection
                gc_collect_cycles();
                
                // If still over threshold, reduce batch size
                if (memory_get_usage(true) > $memoryThreshold) {
                    $batchSize = max(10, $batchSize / 2);
                    Log::warning('Reducing batch size due to memory pressure', [
                        'new_batch_size' => $batchSize,
                        'memory_usage' => memory_get_usage(true),
                        'memory_peak' => memory_get_peak_usage(true)
                    ]);
                }
            }
            
            yield from $this->processBatch($batch);
            
            // Clean up after each batch
            unset($batch);
        }
    }
    
    protected function processBatch(array $batch)
    {
        $cryptos = app(CoinMarketCapProvider::class)->getCryptocurrencies($batch);
        
        foreach ($cryptos as $crypto) {
            yield $crypto;
            
            // Free memory immediately after yielding
            unset($crypto);
        }
        
        // Force garbage collection after batch
        unset($cryptos);
        gc_collect_cycles();
    }
    
    protected function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;
        
        switch ($last) {
            case 'g': $limit *= 1024;
            case 'm': $limit *= 1024;
            case 'k': $limit *= 1024;
        }
        
        return $limit;
    }
}
```

## Database Optimization

### Optimized Database Schema

```sql
-- Cryptocurrency cache table with proper indexing
CREATE TABLE cryptocurrency_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    data JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_symbol_expires (symbol, expires_at),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- Credit usage tracking table
CREATE TABLE credit_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(100) NOT NULL,
    credits_used INT NOT NULL,
    response_time_ms INT NULL,
    cache_hit BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_created_at (created_at),
    INDEX idx_endpoint_date (endpoint, created_at),
    INDEX idx_cache_hit (cache_hit, created_at)
) ENGINE=InnoDB;

-- Partition by month for better performance
ALTER TABLE credit_usage 
PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p202401 VALUES LESS THAN (UNIX_TIMESTAMP('2024-02-01')),
    PARTITION p202402 VALUES LESS THAN (UNIX_TIMESTAMP('2024-03-01')),
    -- Add more partitions as needed
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### Database Query Optimization

```php
<?php

class OptimizedDatabaseService
{
    /**
     * Optimized cache retrieval with proper indexing
     */
    public function getCachedData($symbol)
    {
        return DB::table('cryptocurrency_cache')
            ->select('data')
            ->where('symbol', $symbol)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
    }
    
    /**
     * Batch insert for better performance
     */
    public function batchInsertCacheData(array $data)
    {
        $insertData = [];
        
        foreach ($data as $symbol => $cryptoData) {
            $insertData[] = [
                'symbol' => $symbol,
                'data' => json_encode($cryptoData),
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('cryptocurrency_cache')->insert($insertData);
    }
    
    /**
     * Efficient credit usage analytics
     */
    public function getCreditAnalytics($days = 30)
    {
        return DB::table('credit_usage')
            ->select([
                'endpoint',
                DB::raw('SUM(credits_used) as total_credits'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('SUM(CASE WHEN cache_hit THEN 1 ELSE 0 END) as cache_hits'),
                DB::raw('COUNT(*) - SUM(CASE WHEN cache_hit THEN 1 ELSE 0 END) as cache_misses')
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('endpoint')
            ->orderBy('total_credits', 'desc')
            ->get();
    }
}
```

## Monitoring and Metrics

### Performance Monitoring Dashboard

```php
<?php

class PerformanceMonitor
{
    protected $metrics = [];
    
    public function startTimer($operation)
    {
        $this->metrics[$operation] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }
    
    public function endTimer($operation)
    {
        if (!isset($this->metrics[$operation])) {
            return null;
        }
        
        $start = $this->metrics[$operation];
        $duration = (microtime(true) - $start['start_time']) * 1000; // Convert to ms
        $memoryUsed = memory_get_usage(true) - $start['start_memory'];
        
        $metrics = [
            'operation' => $operation,
            'duration_ms' => $duration,
            'memory_used_bytes' => $memoryUsed,
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'timestamp' => now(),
        ];
        
        // Store metrics for analysis
        Redis::lpush('performance_metrics', json_encode($metrics));
        Redis::ltrim('performance_metrics', 0, 9999); // Keep last 10k metrics
        
        // Log slow operations
        if ($duration > 2000) { // > 2 seconds
            Log::warning('Slow operation detected', $metrics);
        }
        
        unset($this->metrics[$operation]);
        
        return $metrics;
    }
    
    /**
     * Generate performance report
     */
    public function generatePerformanceReport($hours = 24)
    {
        $metricsJson = Redis::lrange('performance_metrics', 0, -1);
        $metrics = array_map('json_decode', $metricsJson);
        
        // Filter by time range
        $cutoff = now()->subHours($hours);
        $recentMetrics = array_filter($metrics, function ($metric) use ($cutoff) {
            return Carbon::parse($metric->timestamp)->isAfter($cutoff);
        });
        
        // Group by operation
        $byOperation = [];
        foreach ($recentMetrics as $metric) {
            $op = $metric->operation;
            if (!isset($byOperation[$op])) {
                $byOperation[$op] = [];
            }
            $byOperation[$op][] = $metric;
        }
        
        // Calculate statistics
        $report = [];
        foreach ($byOperation as $operation => $opMetrics) {
            $durations = array_column($opMetrics, 'duration_ms');
            $memoryUsages = array_column($opMetrics, 'memory_used_bytes');
            
            $report[$operation] = [
                'count' => count($opMetrics),
                'avg_duration_ms' => array_sum($durations) / count($durations),
                'max_duration_ms' => max($durations),
                'min_duration_ms' => min($durations),
                'p95_duration_ms' => $this->percentile($durations, 95),
                'avg_memory_bytes' => array_sum($memoryUsages) / count($memoryUsages),
                'max_memory_bytes' => max($memoryUsages),
            ];
        }
        
        return $report;
    }
    
    protected function percentile(array $values, $percentile)
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $fraction = $index - floor($index);
        
        return $lower + ($fraction * ($upper - $lower));
    }
}
```

### Credit Usage Monitoring

```php
<?php

class CreditUsageMonitor
{
    /**
     * Real-time credit usage tracking
     */
    public function trackCreditUsage($endpoint, $creditsUsed, $responseTime, $cacheHit = false)
    {
        // Store detailed usage record
        DB::table('credit_usage')->insert([
            'endpoint' => $endpoint,
            'credits_used' => $creditsUsed,
            'response_time_ms' => $responseTime,
            'cache_hit' => $cacheHit,
            'created_at' => now(),
        ]);
        
        // Update Redis counters for real-time monitoring
        $day = now()->toDateString();
        Redis::hincrby("daily_credits:{$day}", 'total', $creditsUsed);
        Redis::hincrby("daily_credits:{$day}", $endpoint, $creditsUsed);
        Redis::expire("daily_credits:{$day}", 86400 * 7); // Keep for 7 days
        
        // Check thresholds
        $this->checkCreditThresholds();
    }
    
    protected function checkCreditThresholds()
    {
        $monthlyUsage = $this->getMonthlyUsage();
        $monthlyLimit = config('coinmarketcap.plan.credits_per_month');
        $usagePercent = $monthlyUsage / $monthlyLimit;
        
        if ($usagePercent > 0.9) {
            event(new CreditThresholdExceeded($usagePercent, 'critical'));
        } elseif ($usagePercent > 0.8) {
            event(new CreditThresholdExceeded($usagePercent, 'warning'));
        }
    }
    
    /**
     * Generate credit efficiency report
     */
    public function generateEfficiencyReport($days = 7)
    {
        $usage = DB::table('credit_usage')
            ->select([
                'endpoint',
                DB::raw('SUM(credits_used) as total_credits'),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(CASE WHEN cache_hit THEN 1 ELSE 0 END) as cache_hits'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('endpoint')
            ->get();
        
        $report = [];
        foreach ($usage as $endpoint) {
            $cacheHitRate = ($endpoint->cache_hits / $endpoint->total_requests) * 100;
            $creditsPerRequest = $endpoint->total_credits / $endpoint->total_requests;
            
            $report[] = [
                'endpoint' => $endpoint->endpoint,
                'total_credits' => $endpoint->total_credits,
                'total_requests' => $endpoint->total_requests,
                'cache_hit_rate' => round($cacheHitRate, 2),
                'avg_credits_per_request' => round($creditsPerRequest, 3),
                'avg_response_time' => round($endpoint->avg_response_time, 2),
                'efficiency_score' => $this->calculateEfficiencyScore($cacheHitRate, $creditsPerRequest, $endpoint->avg_response_time),
            ];
        }
        
        // Sort by efficiency score
        usort($report, function ($a, $b) {
            return $b['efficiency_score'] <=> $a['efficiency_score'];
        });
        
        return $report;
    }
    
    protected function calculateEfficiencyScore($cacheHitRate, $creditsPerRequest, $avgResponseTime)
    {
        // Higher cache hit rate = better score
        $cacheScore = $cacheHitRate;
        
        // Lower credits per request = better score
        $creditScore = max(0, 100 - ($creditsPerRequest * 20));
        
        // Lower response time = better score
        $responseScore = max(0, 100 - ($avgResponseTime / 50));
        
        // Weighted average
        return ($cacheScore * 0.5) + ($creditScore * 0.3) + ($responseScore * 0.2);
    }
}
```

## Production Best Practices

### Configuration Optimization

```php
// config/coinmarketcap.php - Production optimized settings
return [
    'api' => [
        'timeout' => 15, // Shorter timeout for better user experience
        'retry_times' => 2, // Fewer retries to prevent cascading failures
        'retry_delay' => 500, // Faster retry for better responsiveness
    ],
    
    'cache' => [
        'enabled' => true,
        'store' => 'redis', // Redis for better performance
        'ttl' => [
            // Optimized TTL values for production
            'cryptocurrency_quotes' => 120, // 2 minutes
            'cryptocurrency_listings' => 300, // 5 minutes
            'cryptocurrency_info' => 86400, // 24 hours
            'exchange_listings' => 600, // 10 minutes
            'global_metrics' => 180, // 3 minutes
        ],
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => 'warning', // Only log warnings and errors in production
        'log_requests' => false, // Disable request logging for performance
        'log_responses' => false, // Disable response logging for performance
        'log_credits' => true, // Keep credit logging for monitoring
    ],
    
    'events' => [
        'enabled' => true,
        'dispatch' => [
            'credit_consumed' => true,
            'credit_warning' => true,
            'api_error' => true,
            'rate_limit_hit' => true,
            'api_call_made' => false, // Disable in production for performance
        ],
    ],
];
```

### Health Check Implementation

```php
<?php

class CoinMarketCapHealthCheck
{
    public function check()
    {
        $checks = [
            'api_connectivity' => $this->checkApiConnectivity(),
            'cache_status' => $this->checkCacheStatus(),
            'credit_status' => $this->checkCreditStatus(),
            'performance' => $this->checkPerformance(),
        ];
        
        $overall = array_reduce($checks, function ($carry, $check) {
            return $carry && $check['status'] === 'healthy';
        }, true);
        
        return [
            'status' => $overall ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now(),
        ];
    }
    
    protected function checkApiConnectivity()
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(5)
                ->withHeaders(['X-CMC_PRO_API_KEY' => config('coinmarketcap.api.key')])
                ->get(config('coinmarketcap.api.base_url') . '/fiat/map');
            $duration = (microtime(true) - $start) * 1000;
            
            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time_ms' => round($duration, 2),
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'error' => 'HTTP ' . $response->status(),
                'response_time_ms' => round($duration, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkCacheStatus()
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return ['status' => 'healthy'];
            }
            
            return [
                'status' => 'unhealthy',
                'error' => 'Cache write/read mismatch',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function checkCreditStatus()
    {
        $monthlyUsage = $this->getMonthlyUsage();
        $monthlyLimit = config('coinmarketcap.plan.credits_per_month');
        $usagePercent = $monthlyUsage / $monthlyLimit;
        
        if ($usagePercent > 0.95) {
            return [
                'status' => 'unhealthy',
                'error' => 'Credit usage critical (>95%)',
                'usage_percent' => round($usagePercent * 100, 2),
            ];
        } elseif ($usagePercent > 0.85) {
            return [
                'status' => 'degraded',
                'warning' => 'Credit usage high (>85%)',
                'usage_percent' => round($usagePercent * 100, 2),
            ];
        }
        
        return [
            'status' => 'healthy',
            'usage_percent' => round($usagePercent * 100, 2),
        ];
    }
    
    protected function checkPerformance()
    {
        $metrics = Redis::lrange('performance_metrics', 0, 99); // Last 100 metrics
        
        if (empty($metrics)) {
            return ['status' => 'unknown', 'error' => 'No performance data available'];
        }
        
        $recentMetrics = array_map(function ($json) {
            return json_decode($json, true);
        }, array_slice($metrics, 0, 20)); // Last 20 operations
        
        $avgDuration = array_sum(array_column($recentMetrics, 'duration_ms')) / count($recentMetrics);
        
        if ($avgDuration > 5000) { // 5 seconds
            return [
                'status' => 'unhealthy',
                'error' => 'Average response time too high',
                'avg_duration_ms' => round($avgDuration, 2),
            ];
        } elseif ($avgDuration > 2000) { // 2 seconds
            return [
                'status' => 'degraded',
                'warning' => 'Average response time elevated',
                'avg_duration_ms' => round($avgDuration, 2),
            ];
        }
        
        return [
            'status' => 'healthy',
            'avg_duration_ms' => round($avgDuration, 2),
        ];
    }
}
```

This performance guide provides comprehensive strategies for optimizing your CoinMarketCap integration in production environments while maintaining cost efficiency and reliability.