<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Cache strategy manager for optimizing cache policies based on data types,
 * credit costs, and market conditions.
 */
class CacheStrategy
{
    /**
     * Strategy definitions with their characteristics
     */
    private const STRATEGIES = [
        'aggressive' => [
            'description' => 'Maximum caching for cost optimization',
            'ttl_multiplier' => 2.0,
            'min_credit_threshold' => 1,
            'cache_real_time' => true,
        ],
        'balanced' => [
            'description' => 'Balance between freshness and cost',
            'ttl_multiplier' => 1.0,
            'min_credit_threshold' => 1,
            'cache_real_time' => true,
        ],
        'fresh' => [
            'description' => 'Prioritize data freshness',
            'ttl_multiplier' => 0.5,
            'min_credit_threshold' => 2,
            'cache_real_time' => false,
        ],
        'adaptive' => [
            'description' => 'Adapt based on usage patterns and credit consumption',
            'ttl_multiplier' => 1.0,
            'min_credit_threshold' => 1,
            'cache_real_time' => true,
        ],
    ];

    /**
     * Market conditions that affect caching strategy
     */
    private const MARKET_CONDITIONS = [
        'volatile' => ['btc_change_24h' => 5.0, 'volume_spike' => 2.0],
        'stable' => ['btc_change_24h' => 2.0, 'volume_spike' => 1.5],
        'trending' => ['social_mentions' => 1.5, 'search_volume' => 1.3],
    ];

    private array $config;
    private string $currentStrategy;
    private CacheAnalytics $analytics;

    public function __construct(?CacheAnalytics $analytics = null)
    {
        $this->config = Config::get('coinmarketcap', []);
        $this->analytics = $analytics ?? new CacheAnalytics();
        $this->currentStrategy = $this->determineOptimalStrategy();
    }

    /**
     * Get the optimal caching strategy based on current conditions.
     *
     * @param string|null $endpointType Specific endpoint type
     * @param array $context Additional context
     * @return array Strategy configuration
     */
    public function getOptimalStrategy(?string $endpointType = null, array $context = []): array
    {
        $baseStrategy = self::STRATEGIES[$this->currentStrategy] ?? self::STRATEGIES['balanced'];
        
        // Adjust strategy based on endpoint type
        if ($endpointType) {
            $baseStrategy = $this->adjustForEndpoint($baseStrategy, $endpointType);
        }
        
        // Adjust for market conditions
        $baseStrategy = $this->adjustForMarketConditions($baseStrategy, $context);
        
        // Adjust for credit usage
        $baseStrategy = $this->adjustForCreditUsage($baseStrategy);
        
        return $baseStrategy;
    }

    /**
     * Calculate optimal TTL for an endpoint.
     *
     * @param string $endpointType Endpoint type
     * @param array $context Context information
     * @return int TTL in seconds
     */
    public function calculateTtl(string $endpointType, array $context = []): int
    {
        $strategy = $this->getOptimalStrategy($endpointType, $context);
        $baseTtl = $this->getBaseTtl($endpointType);
        
        // Apply strategy multiplier
        $adjustedTtl = (int) ($baseTtl * $strategy['ttl_multiplier']);
        
        // Apply context-specific adjustments
        $adjustedTtl = $this->applyContextAdjustments($adjustedTtl, $context);
        
        // Ensure minimum and maximum bounds
        return $this->enforceTtlBounds($adjustedTtl, $endpointType);
    }

    /**
     * Determine if an endpoint should be cached based on strategy.
     *
     * @param string $endpointType Endpoint type
     * @param int $creditCost Credit cost of the endpoint
     * @param array $context Additional context
     * @return bool
     */
    public function shouldCache(string $endpointType, int $creditCost = 1, array $context = []): bool
    {
        $strategy = $this->getOptimalStrategy($endpointType, $context);
        
        // Check if credit cost meets threshold
        if ($creditCost < $strategy['min_credit_threshold']) {
            return false;
        }
        
        // Check endpoint-specific rules
        return $this->evaluateEndpointCachingRules($endpointType, $strategy, $context);
    }

    /**
     * Get cache priority for an endpoint (higher number = higher priority).
     *
     * @param string $endpointType Endpoint type
     * @param int $creditCost Credit cost
     * @param array $context Context information
     * @return int Priority (1-10)
     */
    public function getCachePriority(string $endpointType, int $creditCost = 1, array $context = []): int
    {
        $basePriority = match ($this->getEndpointCategory($endpointType)) {
            'static' => 10,
            'semi_dynamic' => 8,
            'historical' => 9,
            'market_data' => 6,
            'real_time' => 3,
            default => 5,
        };
        
        // Adjust for credit cost
        $creditMultiplier = min(2.0, 1.0 + ($creditCost - 1) * 0.2);
        $adjustedPriority = (int) ($basePriority * $creditMultiplier);
        
        // Adjust for market volatility
        if (($context['volatility'] ?? 'normal') === 'high') {
            $adjustedPriority -= 1;
        }
        
        return max(1, min(10, $adjustedPriority));
    }

    /**
     * Determine when to invalidate cache based on strategy.
     *
     * @param string $endpointType Endpoint type
     * @param array $context Context information
     * @return bool Whether to invalidate
     */
    public function shouldInvalidate(string $endpointType, array $context = []): bool
    {
        // Always invalidate real-time data during high volatility
        if ($this->isRealTimeEndpoint($endpointType) && 
            ($context['volatility'] ?? 'normal') === 'high') {
            return true;
        }
        
        // Invalidate based on data freshness requirements
        $strategy = $this->getOptimalStrategy($endpointType, $context);
        
        if ($strategy['cache_real_time'] === false && $this->isRealTimeEndpoint($endpointType)) {
            return true;
        }
        
        // Check for stale data
        $lastUpdate = $context['last_update'] ?? null;
        if ($lastUpdate) {
            $stalenessThreshold = $this->calculateTtl($endpointType, $context) * 0.8;
            $age = Carbon::now()->diffInSeconds(Carbon::parse($lastUpdate));
            
            return $age > $stalenessThreshold;
        }
        
        return false;
    }

    /**
     * Get warming priorities for different endpoint types.
     *
     * @return array Warming priorities ordered by importance
     */
    public function getWarmingPriorities(): array
    {
        return [
            'cryptocurrency_map' => 10,
            'fiat_map' => 10,
            'exchange_map' => 9,
            'cryptocurrency_info' => 8,
            'exchange_info' => 8,
            'global_metrics' => 7,
            'cryptocurrency_listings' => 6,
            'trending' => 5,
            'cryptocurrency_quotes' => 4,
            'exchange_quotes' => 4,
            'ohlcv' => 3,
            'market_pairs' => 3,
            'historical' => 2,
        ];
    }

    /**
     * Update strategy based on performance metrics.
     *
     * @param array $metrics Performance metrics
     */
    public function adaptStrategy(array $metrics): void
    {
        $hitRate = $metrics['hit_rate'] ?? 0.0;
        $creditEfficiency = $metrics['credit_efficiency'] ?? 0.0;
        $errorRate = $metrics['error_rate'] ?? 0.0;
        
        // Switch to more aggressive caching if hit rate is low
        if ($hitRate < 0.6 && $this->currentStrategy !== 'aggressive') {
            $this->currentStrategy = 'aggressive';
            return;
        }
        
        // Switch to fresh strategy if error rate is high
        if ($errorRate > 0.1 && $this->currentStrategy !== 'fresh') {
            $this->currentStrategy = 'fresh';
            return;
        }
        
        // Use balanced approach for good performance
        if ($hitRate > 0.8 && $errorRate < 0.05) {
            $this->currentStrategy = 'balanced';
        }
    }

    /**
     * Get current strategy name.
     *
     * @return string
     */
    public function getCurrentStrategy(): string
    {
        return $this->currentStrategy;
    }

    /**
     * Set strategy manually.
     *
     * @param string $strategy Strategy name
     * @throws \InvalidArgumentException
     */
    public function setStrategy(string $strategy): void
    {
        if (!isset(self::STRATEGIES[$strategy])) {
            throw new \InvalidArgumentException("Unknown strategy: {$strategy}");
        }
        
        $this->currentStrategy = $strategy;
    }

    /**
     * Get all available strategies.
     *
     * @return array
     */
    public function getAvailableStrategies(): array
    {
        return self::STRATEGIES;
    }

    /**
     * Determine the optimal strategy based on current conditions.
     *
     * @return string
     */
    private function determineOptimalStrategy(): string
    {
        // Check configuration for explicit strategy
        $configStrategy = $this->config['cache']['strategy'] ?? null;
        if ($configStrategy && isset(self::STRATEGIES[$configStrategy])) {
            return $configStrategy;
        }
        
        // Use analytics to determine best strategy
        $stats = $this->analytics->getStatistics();
        $hitRate = $stats['hit_rate'] ?? 0.0;
        $creditUsage = $stats['credits_saved'] ?? 0;
        
        // Start with adaptive strategy if no clear preference
        if ($hitRate === 0.0) {
            return 'adaptive';
        }
        
        // Choose based on performance
        if ($hitRate > 0.8 && $creditUsage > 100) {
            return 'balanced';
        } elseif ($hitRate < 0.5) {
            return 'aggressive';
        } else {
            return 'adaptive';
        }
    }

    /**
     * Adjust strategy for specific endpoint type.
     *
     * @param array $strategy Base strategy
     * @param string $endpointType Endpoint type
     * @return array Adjusted strategy
     */
    private function adjustForEndpoint(array $strategy, string $endpointType): array
    {
        $category = $this->getEndpointCategory($endpointType);
        
        return match ($category) {
            'static' => array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 1.5,
                'cache_real_time' => true,
            ]),
            'real_time' => array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 0.5,
            ]),
            'historical' => array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 2.0,
                'cache_real_time' => true,
            ]),
            default => $strategy,
        };
    }

    /**
     * Adjust strategy for current market conditions.
     *
     * @param array $strategy Base strategy
     * @param array $context Context with market data
     * @return array Adjusted strategy
     */
    private function adjustForMarketConditions(array $strategy, array $context): array
    {
        $volatility = $context['volatility'] ?? 'normal';
        
        return match ($volatility) {
            'high' => array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 0.7,
                'cache_real_time' => false,
            ]),
            'low' => array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 1.3,
                'cache_real_time' => true,
            ]),
            default => $strategy,
        };
    }

    /**
     * Adjust strategy based on credit usage patterns.
     *
     * @param array $strategy Base strategy
     * @return array Adjusted strategy
     */
    private function adjustForCreditUsage(array $strategy): array
    {
        $stats = $this->analytics->getStatistics();
        $creditEfficiency = $stats['credit_efficiency'] ?? 1.0;
        
        // If credit efficiency is low, be more aggressive with caching
        if ($creditEfficiency < 0.5) {
            return array_merge($strategy, [
                'ttl_multiplier' => $strategy['ttl_multiplier'] * 1.5,
                'min_credit_threshold' => max(1, $strategy['min_credit_threshold'] - 1),
            ]);
        }
        
        return $strategy;
    }

    /**
     * Get base TTL for endpoint type from configuration.
     *
     * @param string $endpointType Endpoint type
     * @return int Base TTL in seconds
     */
    private function getBaseTtl(string $endpointType): int
    {
        $ttlConfig = $this->config['cache']['ttl'] ?? [];
        return $ttlConfig[$endpointType] ?? 300; // 5 minutes default
    }

    /**
     * Apply context-specific TTL adjustments.
     *
     * @param int $ttl Base TTL
     * @param array $context Context information
     * @return int Adjusted TTL
     */
    private function applyContextAdjustments(int $ttl, array $context): int
    {
        // Reduce TTL during high activity periods
        if (($context['high_activity'] ?? false) === true) {
            $ttl = (int) ($ttl * 0.8);
        }
        
        // Increase TTL for bulk requests
        if (($context['bulk_request'] ?? false) === true) {
            $ttl = (int) ($ttl * 1.2);
        }
        
        return $ttl;
    }

    /**
     * Enforce minimum and maximum TTL bounds.
     *
     * @param int $ttl Calculated TTL
     * @param string $endpointType Endpoint type
     * @return int Bounded TTL
     */
    private function enforceTtlBounds(int $ttl, string $endpointType): int
    {
        $category = $this->getEndpointCategory($endpointType);
        
        [$min, $max] = match ($category) {
            'static' => [3600, 86400 * 7], // 1 hour to 1 week
            'semi_dynamic' => [60, 3600], // 1 minute to 1 hour
            'real_time' => [30, 300], // 30 seconds to 5 minutes
            'market_data' => [60, 1800], // 1 minute to 30 minutes
            'historical' => [1800, 86400 * 30], // 30 minutes to 30 days
            default => [60, 3600], // 1 minute to 1 hour
        };
        
        return max($min, min($max, $ttl));
    }

    /**
     * Evaluate endpoint-specific caching rules.
     *
     * @param string $endpointType Endpoint type
     * @param array $strategy Current strategy
     * @param array $context Context information
     * @return bool Should cache
     */
    private function evaluateEndpointCachingRules(string $endpointType, array $strategy, array $context): bool
    {
        // Don't cache real-time data if strategy doesn't allow it
        if ($this->isRealTimeEndpoint($endpointType) && !$strategy['cache_real_time']) {
            return false;
        }
        
        // Always cache static data
        if ($this->getEndpointCategory($endpointType) === 'static') {
            return true;
        }
        
        return true;
    }

    /**
     * Get endpoint category.
     *
     * @param string $endpointType Endpoint type
     * @return string Category
     */
    private function getEndpointCategory(string $endpointType): string
    {
        $staticPatterns = ['map', 'info', 'fiat'];
        $realTimePatterns = ['quotes', 'pairs'];
        $semiDynamicPatterns = ['listings', 'trending', 'global'];
        $historicalPatterns = ['historical'];
        
        foreach ($staticPatterns as $pattern) {
            if (stripos($endpointType, $pattern) !== false) {
                return 'static';
            }
        }
        
        foreach ($realTimePatterns as $pattern) {
            if (stripos($endpointType, $pattern) !== false) {
                return 'real_time';
            }
        }
        
        foreach ($semiDynamicPatterns as $pattern) {
            if (stripos($endpointType, $pattern) !== false) {
                return 'semi_dynamic';
            }
        }
        
        foreach ($historicalPatterns as $pattern) {
            if (stripos($endpointType, $pattern) !== false) {
                return 'historical';
            }
        }
        
        return 'market_data';
    }

    /**
     * Check if endpoint is real-time.
     *
     * @param string $endpointType Endpoint type
     * @return bool
     */
    private function isRealTimeEndpoint(string $endpointType): bool
    {
        return $this->getEndpointCategory($endpointType) === 'real_time';
    }
}