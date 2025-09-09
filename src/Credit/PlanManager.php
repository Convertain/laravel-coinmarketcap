<?php

namespace Convertain\CoinMarketCap\Credit;

/**
 * Manages CoinMarketCap subscription plan configurations and limits.
 */
class PlanManager
{
    /**
     * Configuration array.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Current plan configuration.
     *
     * @var array<string, mixed>
     */
    private array $currentPlan;

    /**
     * Create a new plan manager instance.
     *
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->currentPlan = $this->resolvePlanConfiguration();
    }

    /**
     * Get the current plan type.
     *
     * @return string Plan type (basic, hobbyist, startup, standard, professional, enterprise)
     */
    public function getPlanType(): string
    {
        return $this->config['plan']['type'] ?? 'basic';
    }

    /**
     * Get monthly credit limit for current plan.
     *
     * @return int Monthly credit limit
     */
    public function getMonthlyCredits(): int
    {
        return $this->currentPlan['credits_per_month'];
    }

    /**
     * Get daily call limit for current plan.
     *
     * @return int Daily call limit
     */
    public function getDailyCallLimit(): int
    {
        return $this->currentPlan['calls_per_day'];
    }

    /**
     * Get per-minute call limit for current plan.
     *
     * @return int Per-minute call limit
     */
    public function getMinuteCallLimit(): int
    {
        return $this->currentPlan['calls_per_minute'];
    }

    /**
     * Check if the plan supports a specific feature.
     *
     * @param string $feature Feature name
     * @return bool True if feature is supported
     */
    public function supportsFeature(string $feature): bool
    {
        $planFeatures = $this->getPlanFeatures($this->getPlanType());
        return in_array($feature, $planFeatures, true);
    }

    /**
     * Get recommended endpoints for current plan based on credit costs.
     *
     * @return array<string, array<string, mixed>> Recommended endpoints with priorities
     */
    public function getRecommendedEndpoints(): array
    {
        $planType = $this->getPlanType();
        $creditCosts = $this->config['credits']['costs'] ?? [];

        return match ($planType) {
            'basic' => $this->getBasicPlanEndpoints($creditCosts),
            'hobbyist' => $this->getHobbyistPlanEndpoints($creditCosts),
            'startup' => $this->getStartupPlanEndpoints($creditCosts),
            'standard' => $this->getStandardPlanEndpoints($creditCosts),
            'professional' => $this->getProfessionalPlanEndpoints($creditCosts),
            'enterprise' => $this->getEnterprisePlanEndpoints($creditCosts),
            default => $this->getBasicPlanEndpoints($creditCosts),
        };
    }

    /**
     * Calculate optimal batch size for endpoint calls based on plan limits.
     *
     * @param string $endpoint The endpoint to calculate for
     * @return int Optimal batch size
     */
    public function getOptimalBatchSize(string $endpoint): int
    {
        $planType = $this->getPlanType();
        $endpointLimits = $this->config['endpoints']['limits'] ?? [];

        $baseBatchSize = match ($planType) {
            'basic' => 10,
            'hobbyist' => 25,
            'startup' => 50,
            'standard' => 75,
            'professional' => 100,
            'enterprise' => 100,
            default => 10,
        };

        // Apply endpoint-specific limits
        if (str_contains($endpoint, 'cryptocurrency') && isset($endpointLimits['cryptocurrency_ids_per_request'])) {
            return min($baseBatchSize, $endpointLimits['cryptocurrency_ids_per_request']);
        }

        if (str_contains($endpoint, 'exchange') && isset($endpointLimits['exchange_ids_per_request'])) {
            return min($baseBatchSize, $endpointLimits['exchange_ids_per_request']);
        }

        if (isset($endpointLimits['symbols_per_request'])) {
            return min($baseBatchSize, $endpointLimits['symbols_per_request']);
        }

        return $baseBatchSize;
    }

    /**
     * Get plan upgrade recommendations based on current usage.
     *
     * @param int $currentUsage Current credit usage
     * @param float $usageGrowthRate Expected growth rate (e.g., 1.2 for 20% growth)
     * @return array<string, mixed> Upgrade recommendations
     */
    public function getUpgradeRecommendations(int $currentUsage, float $usageGrowthRate = 1.0): array
    {
        $currentPlanType = $this->getPlanType();
        $projectedUsage = (int) ($currentUsage * $usageGrowthRate);
        $currentLimit = $this->getMonthlyCredits();

        $recommendations = [];

        if ($projectedUsage > $currentLimit * 0.8) { // 80% threshold
            $recommendations = $this->findSuitablePlans($projectedUsage);
        }

        return [
            'current_plan' => $currentPlanType,
            'current_limit' => $currentLimit,
            'current_usage' => $currentUsage,
            'projected_usage' => $projectedUsage,
            'needs_upgrade' => !empty($recommendations),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get cost efficiency metrics for current plan.
     *
     * @param int $actualUsage Actual credit usage
     * @return array<string, mixed> Cost efficiency metrics
     */
    public function getCostEfficiencyMetrics(int $actualUsage): array
    {
        $planType = $this->getPlanType();
        $monthlyLimit = $this->getMonthlyCredits();
        $utilizationRate = $monthlyLimit > 0 ? $actualUsage / $monthlyLimit : 0;

        // Estimated costs based on typical CoinMarketCap pricing
        $estimatedMonthlyCost = $this->getEstimatedMonthlyCost($planType);
        $costPerCredit = $monthlyLimit > 0 ? $estimatedMonthlyCost / $monthlyLimit : 0;
        $actualCostPerCredit = $actualUsage > 0 ? $estimatedMonthlyCost / $actualUsage : $costPerCredit;

        return [
            'plan_type' => $planType,
            'monthly_limit' => $monthlyLimit,
            'actual_usage' => $actualUsage,
            'utilization_rate' => $utilizationRate,
            'estimated_monthly_cost' => $estimatedMonthlyCost,
            'cost_per_credit_plan' => $costPerCredit,
            'actual_cost_per_credit' => $actualCostPerCredit,
            'efficiency_score' => $this->calculateEfficiencyScore($utilizationRate),
            'recommendation' => $this->getEfficiencyRecommendation($utilizationRate),
        ];
    }

    /**
     * Resolve plan configuration from config and predefined plans.
     *
     * @return array<string, mixed> Resolved plan configuration
     */
    private function resolvePlanConfiguration(): array
    {
        $planType = $this->getPlanType();
        $predefinedPlans = $this->config['plans'] ?? [];
        
        // Start with predefined plan if available
        $plan = $predefinedPlans[$planType] ?? [];
        
        // Override with explicit configuration
        if (isset($this->config['plan']['credits_per_month'])) {
            $plan['credits_per_month'] = (int) $this->config['plan']['credits_per_month'];
        }
        
        if (isset($this->config['plan']['calls_per_day'])) {
            $plan['calls_per_day'] = (int) $this->config['plan']['calls_per_day'];
        }
        
        if (isset($this->config['plan']['calls_per_minute'])) {
            $plan['calls_per_minute'] = (int) $this->config['plan']['calls_per_minute'];
        }
        
        // Ensure required keys exist
        return [
            'credits_per_month' => $plan['credits_per_month'] ?? 10000,
            'calls_per_day' => $plan['calls_per_day'] ?? 333,
            'calls_per_minute' => $plan['calls_per_minute'] ?? 30,
        ];
    }

    /**
     * Get features supported by each plan.
     *
     * @param string $planType Plan type
     * @return array<string> Supported features
     */
    private function getPlanFeatures(string $planType): array
    {
        return match ($planType) {
            'basic' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'caching',
            ],
            'hobbyist' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'exchange_data',
                'caching',
                'historical_data_limited',
            ],
            'startup' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'exchange_data',
                'global_metrics',
                'caching',
                'historical_data',
                'batch_requests',
            ],
            'standard' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'exchange_data',
                'global_metrics',
                'caching',
                'historical_data',
                'batch_requests',
                'trending_data',
                'ohlcv_data',
            ],
            'professional' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'exchange_data',
                'global_metrics',
                'caching',
                'historical_data',
                'batch_requests',
                'trending_data',
                'ohlcv_data',
                'market_pairs',
                'advanced_filtering',
            ],
            'enterprise' => [
                'basic_endpoints',
                'cryptocurrency_data',
                'exchange_data',
                'global_metrics',
                'caching',
                'historical_data',
                'batch_requests',
                'trending_data',
                'ohlcv_data',
                'market_pairs',
                'advanced_filtering',
                'priority_support',
                'custom_limits',
            ],
            default => ['basic_endpoints', 'cryptocurrency_data'],
        };
    }

    /**
     * Get basic plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getBasicPlanEndpoints(array $creditCosts): array
    {
        return [
            'cryptocurrency_listings_latest' => [
                'priority' => 'high',
                'description' => 'Get latest cryptocurrency listings',
                'cost' => $creditCosts['cryptocurrency_listings_latest'] ?? 1,
                'recommended_frequency' => 'daily',
            ],
            'cryptocurrency_quotes_latest' => [
                'priority' => 'high',
                'description' => 'Get latest price quotes',
                'cost' => $creditCosts['cryptocurrency_quotes_latest'] ?? 1,
                'recommended_frequency' => 'hourly',
            ],
            'global_metrics_quotes_latest' => [
                'priority' => 'medium',
                'description' => 'Get global market metrics',
                'cost' => $creditCosts['global_metrics_quotes_latest'] ?? 1,
                'recommended_frequency' => 'daily',
            ],
        ];
    }

    /**
     * Get hobbyist plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getHobbyistPlanEndpoints(array $creditCosts): array
    {
        $basic = $this->getBasicPlanEndpoints($creditCosts);
        
        return array_merge($basic, [
            'exchange_listings_latest' => [
                'priority' => 'medium',
                'description' => 'Get exchange listings',
                'cost' => $creditCosts['exchange_listings_latest'] ?? 1,
                'recommended_frequency' => 'daily',
            ],
            'cryptocurrency_info' => [
                'priority' => 'low',
                'description' => 'Get cryptocurrency metadata',
                'cost' => $creditCosts['cryptocurrency_info'] ?? 1,
                'recommended_frequency' => 'weekly',
            ],
        ]);
    }

    /**
     * Get startup plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getStartupPlanEndpoints(array $creditCosts): array
    {
        $hobbyist = $this->getHobbyistPlanEndpoints($creditCosts);
        
        return array_merge($hobbyist, [
            'cryptocurrency_ohlcv_latest' => [
                'priority' => 'medium',
                'description' => 'Get OHLCV data',
                'cost' => $creditCosts['cryptocurrency_ohlcv_latest'] ?? 1,
                'recommended_frequency' => '4_hourly',
            ],
            'exchange_quotes_latest' => [
                'priority' => 'medium',
                'description' => 'Get exchange volume data',
                'cost' => $creditCosts['exchange_quotes_latest'] ?? 1,
                'recommended_frequency' => 'daily',
            ],
        ]);
    }

    /**
     * Get standard plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getStandardPlanEndpoints(array $creditCosts): array
    {
        $startup = $this->getStartupPlanEndpoints($creditCosts);
        
        return array_merge($startup, [
            'cryptocurrency_trending' => [
                'priority' => 'medium',
                'description' => 'Get trending cryptocurrencies',
                'cost' => $creditCosts['cryptocurrency_trending'] ?? 1,
                'recommended_frequency' => 'hourly',
            ],
            'cryptocurrency_market_pairs_latest' => [
                'priority' => 'low',
                'description' => 'Get market pairs data',
                'cost' => $creditCosts['cryptocurrency_market_pairs_latest'] ?? 1,
                'recommended_frequency' => 'daily',
            ],
        ]);
    }

    /**
     * Get professional plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getProfessionalPlanEndpoints(array $creditCosts): array
    {
        $standard = $this->getStandardPlanEndpoints($creditCosts);
        
        return array_merge($standard, [
            'cryptocurrency_quotes_historical' => [
                'priority' => 'low',
                'description' => 'Get historical price data',
                'cost' => $creditCosts['cryptocurrency_quotes_historical'] ?? 1,
                'recommended_frequency' => 'as_needed',
            ],
            'cryptocurrency_ohlcv_historical' => [
                'priority' => 'low',
                'description' => 'Get historical OHLCV data',
                'cost' => $creditCosts['cryptocurrency_ohlcv_historical'] ?? 1,
                'recommended_frequency' => 'as_needed',
            ],
        ]);
    }

    /**
     * Get enterprise plan endpoint recommendations.
     *
     * @param array<string, int> $creditCosts Credit costs mapping
     * @return array<string, array<string, mixed>> Endpoint recommendations
     */
    private function getEnterprisePlanEndpoints(array $creditCosts): array
    {
        $professional = $this->getProfessionalPlanEndpoints($creditCosts);
        
        // Enterprise gets access to all endpoints with high frequency
        foreach ($professional as $endpoint => $config) {
            $professional[$endpoint]['priority'] = 'high';
        }
        
        return array_merge($professional, [
            'global_metrics_quotes_historical' => [
                'priority' => 'medium',
                'description' => 'Get historical global metrics',
                'cost' => $creditCosts['global_metrics_quotes_historical'] ?? 1,
                'recommended_frequency' => 'as_needed',
            ],
        ]);
    }

    /**
     * Find suitable plans for projected usage.
     *
     * @param int $projectedUsage Projected credit usage
     * @return array<string, array<string, mixed>> Suitable plans
     */
    private function findSuitablePlans(int $projectedUsage): array
    {
        $plans = $this->config['plans'] ?? [];
        $suitable = [];
        
        foreach ($plans as $planType => $planConfig) {
            $limit = $planConfig['credits_per_month'];
            
            if ($limit >= $projectedUsage * 1.2) { // 20% buffer
                $suitable[] = [
                    'plan_type' => $planType,
                    'monthly_limit' => $limit,
                    'buffer_percentage' => (($limit - $projectedUsage) / $projectedUsage) * 100,
                    'estimated_cost' => $this->getEstimatedMonthlyCost($planType),
                ];
            }
        }
        
        // Sort by limit (ascending)
        usort($suitable, fn($a, $b) => $a['monthly_limit'] <=> $b['monthly_limit']);
        
        return $suitable;
    }

    /**
     * Get estimated monthly cost for a plan.
     *
     * @param string $planType Plan type
     * @return float Estimated monthly cost
     */
    private function getEstimatedMonthlyCost(string $planType): float
    {
        // Estimated costs based on typical CoinMarketCap pricing (as of 2024)
        return match ($planType) {
            'basic' => 0.0, // Free tier
            'hobbyist' => 79.0,
            'startup' => 299.0,
            'standard' => 699.0,
            'professional' => 1999.0,
            'enterprise' => 11999.0,
            default => 0.0,
        };
    }

    /**
     * Calculate efficiency score based on utilization rate.
     *
     * @param float $utilizationRate Utilization rate (0-1)
     * @return string Efficiency score
     */
    private function calculateEfficiencyScore(float $utilizationRate): string
    {
        return match (true) {
            $utilizationRate >= 0.8 => 'excellent',
            $utilizationRate >= 0.6 => 'good',
            $utilizationRate >= 0.4 => 'fair',
            $utilizationRate >= 0.2 => 'poor',
            default => 'very_poor',
        };
    }

    /**
     * Get efficiency recommendation based on utilization rate.
     *
     * @param float $utilizationRate Utilization rate (0-1)
     * @return string Efficiency recommendation
     */
    private function getEfficiencyRecommendation(float $utilizationRate): string
    {
        return match (true) {
            $utilizationRate > 0.9 => 'Consider upgrading to a higher plan for better buffer.',
            $utilizationRate >= 0.8 => 'Good utilization rate, plan is well-suited for your usage.',
            $utilizationRate >= 0.6 => 'Decent utilization, consider optimizing API calls or caching.',
            $utilizationRate >= 0.4 => 'Low utilization, consider downgrading plan or increasing usage.',
            $utilizationRate >= 0.2 => 'Very low utilization, consider downgrading to a lower plan.',
            default => 'Extremely low utilization, consider free tier or pause subscription.',
        };
    }
}