<?php

namespace Convertain\CoinMarketCap\Credit;

use Illuminate\Cache\CacheManager;
use Carbon\Carbon;

/**
 * Implements credit optimization strategies to minimize API costs while maintaining data quality.
 */
class CreditOptimizer
{
    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * Plan manager instance.
     *
     * @var PlanManager
     */
    private PlanManager $planManager;

    /**
     * Credit manager instance.
     *
     * @var CreditManager
     */
    private CreditManager $creditManager;

    /**
     * Configuration array.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a new credit optimizer instance.
     *
     * @param CacheManager $cache Cache manager
     * @param PlanManager $planManager Plan manager
     * @param CreditManager $creditManager Credit manager
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(
        CacheManager $cache,
        PlanManager $planManager,
        CreditManager $creditManager,
        array $config
    ) {
        $this->cache = $cache;
        $this->planManager = $planManager;
        $this->creditManager = $creditManager;
        $this->config = $config;
    }

    /**
     * Check if optimization is enabled.
     *
     * @return bool True if optimization is enabled
     */
    public function isOptimizationEnabled(): bool
    {
        return $this->config['credits']['optimization_enabled'] ?? true;
    }

    /**
     * Optimize API request parameters to reduce credit consumption.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    public function optimizeRequest(string $endpoint, array $params): array
    {
        if (!$this->isOptimizationEnabled()) {
            return $params;
        }

        $optimized = $params;

        // Apply endpoint-specific optimizations
        $optimized = $this->optimizeByEndpoint($endpoint, $optimized);

        // Apply batching optimizations
        $optimized = $this->optimizeBatching($endpoint, $optimized);

        // Apply currency optimization
        $optimized = $this->optimizeCurrencies($optimized);

        // Apply field selection optimization
        $optimized = $this->optimizeFields($endpoint, $optimized);

        return $optimized;
    }

    /**
     * Determine if request should use cache instead of API call.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Cache decision with reasons
     */
    public function shouldUseCache(string $endpoint, array $params): array
    {
        if (!$this->isCacheEnabled()) {
            return ['use_cache' => false, 'reason' => 'cache_disabled'];
        }

        // Check cache freshness requirements
        $cacheAge = $this->getCacheAge($endpoint, $params);
        $maxAge = $this->getMaxCacheAge($endpoint);
        
        if ($cacheAge !== null && $cacheAge < $maxAge) {
            return [
                'use_cache' => true, 
                'reason' => 'cache_fresh',
                'cache_age' => $cacheAge,
                'max_age' => $maxAge,
            ];
        }

        // Check credit availability
        $creditCost = $this->getEndpointCreditCost($endpoint);
        if (!$this->creditManager->canMakeCall($endpoint, $creditCost)) {
            return [
                'use_cache' => true, 
                'reason' => 'credit_limit_reached',
                'credit_cost' => $creditCost,
                'remaining_credits' => $this->creditManager->getRemainingCredits(),
            ];
        }

        // Check rate limits
        if (!$this->canMakeRateLimitedCall()) {
            return [
                'use_cache' => true, 
                'reason' => 'rate_limit_reached',
                'cache_age' => $cacheAge,
            ];
        }

        // Check data freshness requirements vs cost
        return $this->evaluateDataFreshnessCost($endpoint, $cacheAge, $creditCost);
    }

    /**
     * Get recommended request batching for multiple items.
     *
     * @param string $endpoint The API endpoint
     * @param array<mixed> $items Items to batch
     * @return array<array<mixed>> Batched items
     */
    public function getBatchRecommendations(string $endpoint, array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $optimalBatchSize = $this->planManager->getOptimalBatchSize($endpoint);
        $maxBatchSize = $this->getMaxBatchSize($endpoint);
        $batchSize = min($optimalBatchSize, $maxBatchSize);

        return array_chunk($items, $batchSize);
    }

    /**
     * Optimize request timing to minimize credit consumption.
     *
     * @param string $endpoint The API endpoint
     * @return array<string, mixed> Timing recommendations
     */
    public function getOptimalTiming(string $endpoint): array
    {
        $now = Carbon::now();
        $usageStats = $this->creditManager->getUsageStats();
        
        // Check if we're near rate limits
        if ($usageStats['minute_calls'] >= $usageStats['minute_limit'] * 0.9) {
            $waitSeconds = 60 - $now->second;
            return [
                'should_delay' => true,
                'delay_seconds' => $waitSeconds,
                'reason' => 'minute_rate_limit_near',
                'optimal_time' => $now->addSeconds($waitSeconds)->toISOString(),
            ];
        }

        if ($usageStats['daily_calls'] >= $usageStats['daily_limit'] * 0.9) {
            $nextDay = $now->addDay()->startOfDay();
            return [
                'should_delay' => true,
                'delay_seconds' => $now->diffInSeconds($nextDay),
                'reason' => 'daily_rate_limit_near',
                'optimal_time' => $nextDay->toISOString(),
            ];
        }

        // Check monthly credit usage
        if ($usageStats['usage_percentage'] > 0.9) {
            $nextMonth = $now->addMonth()->startOfMonth();
            return [
                'should_delay' => true,
                'delay_seconds' => $now->diffInSeconds($nextMonth),
                'reason' => 'monthly_credits_near_limit',
                'optimal_time' => $nextMonth->toISOString(),
            ];
        }

        // Check for optimal timing based on data update frequency
        $optimalWindow = $this->getEndpointOptimalWindow($endpoint);
        
        return [
            'should_delay' => false,
            'optimal_window' => $optimalWindow,
            'current_time' => $now->toISOString(),
        ];
    }

    /**
     * Get credit-saving alternatives for an endpoint.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Alternative suggestions
     */
    public function getCreditSavingAlternatives(string $endpoint, array $params): array
    {
        $alternatives = [];

        // Check for cached alternatives
        if ($this->isCacheEnabled()) {
            $cacheAge = $this->getCacheAge($endpoint, $params);
            if ($cacheAge !== null) {
                $alternatives['cached_data'] = [
                    'description' => 'Use existing cached data',
                    'credit_savings' => $this->getEndpointCreditCost($endpoint),
                    'data_age' => $cacheAge,
                    'freshness' => $this->calculateFreshnessScore($endpoint, $cacheAge),
                ];
            }
        }

        // Check for lower-cost endpoint alternatives
        $lowerCostEndpoints = $this->getLowerCostAlternatives($endpoint);
        if (!empty($lowerCostEndpoints)) {
            $alternatives['lower_cost_endpoints'] = $lowerCostEndpoints;
        }

        // Check for aggregated data alternatives
        $aggregatedAlternatives = $this->getAggregatedAlternatives($endpoint, $params);
        if (!empty($aggregatedAlternatives)) {
            $alternatives['aggregated_data'] = $aggregatedAlternatives;
        }

        // Check for reduced precision alternatives
        $reducedPrecisionAlternatives = $this->getReducedPrecisionAlternatives($endpoint, $params);
        if (!empty($reducedPrecisionAlternatives)) {
            $alternatives['reduced_precision'] = $reducedPrecisionAlternatives;
        }

        return $alternatives;
    }

    /**
     * Calculate cost-benefit score for a request.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @param int $dataMaxAge Maximum acceptable data age in seconds
     * @return array<string, mixed> Cost-benefit analysis
     */
    public function calculateCostBenefit(string $endpoint, array $params, int $dataMaxAge): array
    {
        $creditCost = $this->getEndpointCreditCost($endpoint);
        $cacheAge = $this->getCacheAge($endpoint, $params);
        $freshnessScore = $this->calculateFreshnessScore($endpoint, $cacheAge);
        $remainingCredits = $this->creditManager->getRemainingCredits();
        $usagePercentage = $this->creditManager->getUsagePercentage();

        // Calculate benefit score (0-100)
        $benefitScore = $this->calculateBenefitScore($endpoint, $cacheAge, $dataMaxAge);

        // Calculate cost score (0-100, higher = more expensive)
        $costScore = $this->calculateCostScore($creditCost, $remainingCredits, $usagePercentage);

        // Calculate overall recommendation score
        $recommendationScore = $benefitScore - $costScore;

        return [
            'endpoint' => $endpoint,
            'credit_cost' => $creditCost,
            'remaining_credits' => $remainingCredits,
            'usage_percentage' => $usagePercentage,
            'cache_age' => $cacheAge,
            'freshness_score' => $freshnessScore,
            'benefit_score' => $benefitScore,
            'cost_score' => $costScore,
            'recommendation_score' => $recommendationScore,
            'recommendation' => $this->getRecommendationFromScore($recommendationScore),
            'alternatives' => $this->getCreditSavingAlternatives($endpoint, $params),
        ];
    }

    /**
     * Optimize request parameters by endpoint type.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeByEndpoint(string $endpoint, array $params): array
    {
        // Cryptocurrency endpoints
        if (str_contains($endpoint, 'cryptocurrency')) {
            return $this->optimizeCryptocurrencyRequest($endpoint, $params);
        }

        // Exchange endpoints
        if (str_contains($endpoint, 'exchange')) {
            return $this->optimizeExchangeRequest($endpoint, $params);
        }

        // Global metrics endpoints
        if (str_contains($endpoint, 'global-metrics')) {
            return $this->optimizeGlobalMetricsRequest($endpoint, $params);
        }

        return $params;
    }

    /**
     * Optimize batching for requests.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeBatching(string $endpoint, array $params): array
    {
        $optimized = $params;

        // Optimize ID batching
        if (isset($params['id']) && is_array($params['id'])) {
            $batchSize = $this->planManager->getOptimalBatchSize($endpoint);
            if (count($params['id']) > $batchSize) {
                // Take only the optimal batch size
                $optimized['id'] = array_slice($params['id'], 0, $batchSize);
            }
        }

        // Optimize symbol batching
        if (isset($params['symbol']) && is_array($params['symbol'])) {
            $batchSize = $this->planManager->getOptimalBatchSize($endpoint);
            if (count($params['symbol']) > $batchSize) {
                $optimized['symbol'] = array_slice($params['symbol'], 0, $batchSize);
            }
        }

        return $optimized;
    }

    /**
     * Optimize currency selection to minimize costs.
     *
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeCurrencies(array $params): array
    {
        if (!isset($params['convert']) || !is_array($params['convert'])) {
            return $params;
        }

        $supportedCurrencies = $this->config['supported_currencies'] ?? ['usd'];
        $requestedCurrencies = $params['convert'];

        // Filter to only supported currencies
        $optimizedCurrencies = array_intersect($requestedCurrencies, $supportedCurrencies);

        // Limit to reasonable number of currencies to avoid extra costs
        $maxCurrencies = $this->getMaxCurrenciesForPlan();
        if (count($optimizedCurrencies) > $maxCurrencies) {
            // Prioritize major currencies
            $priorityCurrencies = ['usd', 'eur', 'btc', 'eth'];
            $priority = array_intersect($priorityCurrencies, $optimizedCurrencies);
            $remaining = array_diff($optimizedCurrencies, $priority);
            
            $optimizedCurrencies = array_merge(
                $priority,
                array_slice($remaining, 0, $maxCurrencies - count($priority))
            );
        }

        return array_merge($params, ['convert' => $optimizedCurrencies]);
    }

    /**
     * Optimize field selection to reduce response size and cost.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeFields(string $endpoint, array $params): array
    {
        // Only optimize if aux parameter is not explicitly set
        if (isset($params['aux'])) {
            return $params;
        }

        $planType = $this->planManager->getPlanType();
        $essentialFields = $this->getEssentialFieldsForPlan($endpoint, $planType);

        if (!empty($essentialFields)) {
            $params['aux'] = implode(',', $essentialFields);
        }

        return $params;
    }

    /**
     * Check if cache is enabled.
     *
     * @return bool True if cache is enabled
     */
    private function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'] ?? true;
    }

    /**
     * Get cache age for endpoint and parameters.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return int|null Cache age in seconds, null if no cache
     */
    private function getCacheAge(string $endpoint, array $params): ?int
    {
        $cacheKey = $this->generateCacheKey($endpoint, $params);
        $cacheStore = $this->getCacheStore();
        
        $cachedData = $cacheStore->get($cacheKey);
        if ($cachedData === null) {
            return null;
        }

        if (isset($cachedData['cached_at'])) {
            $cachedAt = Carbon::parse($cachedData['cached_at']);
            return Carbon::now()->diffInSeconds($cachedAt);
        }

        return null;
    }

    /**
     * Get maximum cache age for endpoint.
     *
     * @param string $endpoint The API endpoint
     * @return int Maximum cache age in seconds
     */
    private function getMaxCacheAge(string $endpoint): int
    {
        $ttlConfig = $this->config['cache']['ttl'] ?? [];
        $endpointKey = $this->normalizeEndpointForConfig($endpoint);
        
        return $ttlConfig[$endpointKey] ?? 300; // Default 5 minutes
    }

    /**
     * Get endpoint credit cost.
     *
     * @param string $endpoint The API endpoint
     * @return int Credit cost
     */
    private function getEndpointCreditCost(string $endpoint): int
    {
        $costs = $this->config['credits']['costs'] ?? [];
        $endpointKey = $this->normalizeEndpointForConfig($endpoint);
        
        return $costs[$endpointKey] ?? 1;
    }

    /**
     * Check if rate-limited call can be made.
     *
     * @return bool True if call can be made
     */
    private function canMakeRateLimitedCall(): bool
    {
        $stats = $this->creditManager->getUsageStats();
        
        // Check minute limit
        if ($stats['minute_calls'] >= $stats['minute_limit']) {
            return false;
        }

        // Check daily limit
        if ($stats['daily_calls'] >= $stats['daily_limit']) {
            return false;
        }

        return true;
    }

    /**
     * Evaluate data freshness vs cost trade-off.
     *
     * @param string $endpoint The API endpoint
     * @param int|null $cacheAge Cache age in seconds
     * @param int $creditCost Credit cost for API call
     * @return array<string, mixed> Evaluation result
     */
    private function evaluateDataFreshnessCost(string $endpoint, ?int $cacheAge, int $creditCost): array
    {
        $usagePercentage = $this->creditManager->getUsagePercentage();
        
        // If we're over 90% usage, prefer cache unless data is very old
        if ($usagePercentage > 0.9) {
            $maxAcceptableAge = $this->getMaxCacheAge($endpoint) * 3; // 3x normal TTL
            
            if ($cacheAge !== null && $cacheAge < $maxAcceptableAge) {
                return [
                    'use_cache' => true,
                    'reason' => 'high_usage_conserve_credits',
                    'cache_age' => $cacheAge,
                    'usage_percentage' => $usagePercentage,
                ];
            }
        }

        // If we're over 70% usage, be more conservative
        if ($usagePercentage > 0.7) {
            $maxAcceptableAge = $this->getMaxCacheAge($endpoint) * 2; // 2x normal TTL
            
            if ($cacheAge !== null && $cacheAge < $maxAcceptableAge) {
                return [
                    'use_cache' => true,
                    'reason' => 'moderate_usage_conserve_credits',
                    'cache_age' => $cacheAge,
                    'usage_percentage' => $usagePercentage,
                ];
            }
        }

        return [
            'use_cache' => false,
            'reason' => 'fresh_data_needed',
            'cache_age' => $cacheAge,
            'credit_cost' => $creditCost,
        ];
    }

    /**
     * Get maximum batch size for endpoint.
     *
     * @param string $endpoint The API endpoint
     * @return int Maximum batch size
     */
    private function getMaxBatchSize(string $endpoint): int
    {
        $limits = $this->config['endpoints']['limits'] ?? [];
        
        if (str_contains($endpoint, 'cryptocurrency')) {
            return $limits['cryptocurrency_ids_per_request'] ?? 100;
        }
        
        if (str_contains($endpoint, 'exchange')) {
            return $limits['exchange_ids_per_request'] ?? 100;
        }
        
        return $limits['symbols_per_request'] ?? 100;
    }

    /**
     * Get optimal time window for endpoint calls.
     *
     * @param string $endpoint The API endpoint
     * @return array<string, mixed> Optimal window information
     */
    private function getEndpointOptimalWindow(string $endpoint): array
    {
        // Static data updates less frequently
        if (str_contains($endpoint, 'map') || str_contains($endpoint, 'info')) {
            return [
                'frequency' => 'daily',
                'optimal_hour' => 9, // 9 AM UTC when markets are active
                'reason' => 'static_data_updates_daily',
            ];
        }

        // Price data updates frequently
        if (str_contains($endpoint, 'quotes') || str_contains($endpoint, 'ohlcv')) {
            return [
                'frequency' => 'hourly',
                'optimal_minutes' => [0, 15, 30, 45], // Top of each quarter hour
                'reason' => 'price_data_updates_frequently',
            ];
        }

        // Default to moderate frequency
        return [
            'frequency' => '4_hourly',
            'optimal_hours' => [0, 6, 12, 18], // Every 6 hours
            'reason' => 'moderate_update_frequency',
        ];
    }

    /**
     * Calculate freshness score (0-100, higher = fresher).
     *
     * @param string $endpoint The API endpoint
     * @param int|null $cacheAge Cache age in seconds
     * @return int Freshness score
     */
    private function calculateFreshnessScore(string $endpoint, ?int $cacheAge): int
    {
        if ($cacheAge === null) {
            return 0; // No cached data
        }

        $maxAge = $this->getMaxCacheAge($endpoint);
        $score = max(0, 100 - (($cacheAge / $maxAge) * 100));
        
        return (int) round($score);
    }

    /**
     * Calculate benefit score for making API call.
     *
     * @param string $endpoint The API endpoint
     * @param int|null $cacheAge Cache age in seconds
     * @param int $dataMaxAge Maximum acceptable data age
     * @return int Benefit score (0-100)
     */
    private function calculateBenefitScore(string $endpoint, ?int $cacheAge, int $dataMaxAge): int
    {
        if ($cacheAge === null) {
            return 100; // Maximum benefit if no cached data
        }

        if ($cacheAge > $dataMaxAge) {
            return 100; // Data is too old, maximum benefit to refresh
        }

        // Score decreases as cache gets fresher
        $freshnessFactor = 1 - ($cacheAge / $dataMaxAge);
        return (int) round($freshnessFactor * 100);
    }

    /**
     * Calculate cost score for making API call.
     *
     * @param int $creditCost Credit cost for the call
     * @param int $remainingCredits Remaining credits
     * @param float $usagePercentage Current usage percentage
     * @return int Cost score (0-100, higher = more expensive)
     */
    private function calculateCostScore(int $creditCost, int $remainingCredits, float $usagePercentage): int
    {
        // Base cost relative to credit cost
        $baseCostScore = min(100, ($creditCost / 10) * 100); // Normalize to 100
        
        // Usage pressure multiplier
        $usageMultiplier = 1 + ($usagePercentage * 2); // 1x to 3x multiplier
        
        // Remaining credits pressure
        $creditsPressure = $remainingCredits < 100 ? 2 : 1;
        
        $costScore = $baseCostScore * $usageMultiplier * $creditsPressure;
        
        return (int) min(100, round($costScore));
    }

    /**
     * Get recommendation from cost-benefit score.
     *
     * @param int $score Recommendation score
     * @return string Recommendation
     */
    private function getRecommendationFromScore(int $score): string
    {
        return match (true) {
            $score >= 50 => 'make_api_call',
            $score >= 0 => 'consider_alternatives',
            $score >= -50 => 'use_cache_preferred',
            default => 'use_cache_strongly_recommended',
        };
    }

    /**
     * Optimize cryptocurrency-specific requests.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeCryptocurrencyRequest(string $endpoint, array $params): array
    {
        $optimized = $params;

        // Optimize limit for listings endpoints
        if (str_contains($endpoint, 'listings')) {
            $planType = $this->planManager->getPlanType();
            $optimalLimit = match ($planType) {
                'basic' => 100,
                'hobbyist' => 200,
                'startup' => 500,
                'standard' => 1000,
                'professional' => 2000,
                'enterprise' => 5000,
                default => 100,
            };
            
            if (!isset($params['limit']) || $params['limit'] > $optimalLimit) {
                $optimized['limit'] = $optimalLimit;
            }
        }

        return $optimized;
    }

    /**
     * Optimize exchange-specific requests.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeExchangeRequest(string $endpoint, array $params): array
    {
        $optimized = $params;

        // Similar optimization logic for exchanges
        if (str_contains($endpoint, 'listings')) {
            $planType = $this->planManager->getPlanType();
            $optimalLimit = match ($planType) {
                'basic' => 50,
                'hobbyist' => 100,
                'startup' => 200,
                'standard' => 300,
                'professional' => 500,
                'enterprise' => 1000,
                default => 50,
            };
            
            if (!isset($params['limit']) || $params['limit'] > $optimalLimit) {
                $optimized['limit'] = $optimalLimit;
            }
        }

        return $optimized;
    }

    /**
     * Optimize global metrics requests.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeGlobalMetricsRequest(string $endpoint, array $params): array
    {
        // Global metrics typically don't need much optimization
        // but we can optimize currency selection
        return $this->optimizeCurrencies($params);
    }

    /**
     * Get maximum currencies for current plan.
     *
     * @return int Maximum number of currencies
     */
    private function getMaxCurrenciesForPlan(): int
    {
        $planType = $this->planManager->getPlanType();
        
        return match ($planType) {
            'basic' => 2,
            'hobbyist' => 3,
            'startup' => 5,
            'standard' => 8,
            'professional' => 12,
            'enterprise' => 20,
            default => 2,
        };
    }

    /**
     * Get essential fields for plan and endpoint.
     *
     * @param string $endpoint The API endpoint
     * @param string $planType Plan type
     * @return array<string> Essential fields
     */
    private function getEssentialFieldsForPlan(string $endpoint, string $planType): array
    {
        // Define essential fields by plan to minimize response size
        $basicFields = ['name', 'symbol', 'slug'];
        $extendedFields = array_merge($basicFields, ['logo', 'description', 'website']);
        $fullFields = array_merge($extendedFields, ['technical_doc', 'twitter', 'reddit']);

        return match ($planType) {
            'basic' => $basicFields,
            'hobbyist', 'startup' => $extendedFields,
            default => $fullFields,
        };
    }

    /**
     * Get lower cost endpoint alternatives.
     *
     * @param string $endpoint The API endpoint
     * @return array<string, array<string, mixed>> Lower cost alternatives
     */
    private function getLowerCostAlternatives(string $endpoint): array
    {
        $alternatives = [];
        $costs = $this->config['credits']['costs'] ?? [];
        $currentCost = $this->getEndpointCreditCost($endpoint);

        // Find endpoints with lower costs that provide similar data
        foreach ($costs as $altEndpoint => $cost) {
            if ($cost < $currentCost && $this->isRelatedEndpoint($endpoint, $altEndpoint)) {
                $alternatives[] = [
                    'endpoint' => $altEndpoint,
                    'cost' => $cost,
                    'savings' => $currentCost - $cost,
                    'data_completeness' => $this->getDataCompletenessScore($endpoint, $altEndpoint),
                ];
            }
        }

        return $alternatives;
    }

    /**
     * Get aggregated data alternatives.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Aggregated alternatives
     */
    private function getAggregatedAlternatives(string $endpoint, array $params): array
    {
        // Implementation depends on specific use case
        // Could suggest using market cap rankings instead of individual quotes
        // Or global metrics instead of individual cryptocurrency data
        return [];
    }

    /**
     * Get reduced precision alternatives.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Reduced precision alternatives
     */
    private function getReducedPrecisionAlternatives(string $endpoint, array $params): array
    {
        $alternatives = [];

        // Suggest reducing the number of decimal places or data points
        if (str_contains($endpoint, 'ohlcv')) {
            $alternatives['reduce_interval'] = [
                'description' => 'Use larger time intervals (hourly instead of minutely)',
                'credit_savings_potential' => '20-50%',
                'data_impact' => 'Lower granularity but same trends',
            ];
        }

        return $alternatives;
    }

    /**
     * Generate cache key for endpoint and parameters.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return string Cache key
     */
    private function generateCacheKey(string $endpoint, array $params): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'coinmarketcap';
        $normalized = $this->normalizeEndpointForConfig($endpoint);
        $paramHash = md5(serialize($params));
        
        return "{$prefix}:{$normalized}:{$paramHash}";
    }

    /**
     * Get cache store instance.
     *
     * @return \Illuminate\Contracts\Cache\Store Cache store
     */
    private function getCacheStore(): \Illuminate\Contracts\Cache\Store
    {
        $store = $this->config['cache']['store'] ?? null;
        return $this->cache->store($store);
    }

    /**
     * Normalize endpoint name for configuration lookup.
     *
     * @param string $endpoint The API endpoint
     * @return string Normalized endpoint name
     */
    private function normalizeEndpointForConfig(string $endpoint): string
    {
        return str_replace(['/', '-'], '_', trim($endpoint, '/'));
    }

    /**
     * Check if two endpoints are related.
     *
     * @param string $endpoint1 First endpoint
     * @param string $endpoint2 Second endpoint
     * @return bool True if endpoints are related
     */
    private function isRelatedEndpoint(string $endpoint1, string $endpoint2): bool
    {
        // Simple check for same category
        $categories1 = explode('_', $endpoint1);
        $categories2 = explode('_', $endpoint2);
        
        return count(array_intersect($categories1, $categories2)) > 0;
    }

    /**
     * Get data completeness score comparing two endpoints.
     *
     * @param string $originalEndpoint Original endpoint
     * @param string $alternativeEndpoint Alternative endpoint
     * @return int Completeness score (0-100)
     */
    private function getDataCompletenessScore(string $originalEndpoint, string $alternativeEndpoint): int
    {
        // Simple heuristic based on endpoint names
        if (str_contains($originalEndpoint, 'latest') && str_contains($alternativeEndpoint, 'latest')) {
            return 95; // Very similar
        }
        
        if (str_contains($originalEndpoint, 'info') && str_contains($alternativeEndpoint, 'map')) {
            return 60; // Map has less info than info
        }
        
        return 80; // Default moderate completeness
    }
}