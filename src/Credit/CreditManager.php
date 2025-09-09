<?php

namespace Convertain\CoinMarketCap\Credit;

use Convertain\CoinMarketCap\Contracts\CreditManagerInterface;
use Illuminate\Cache\CacheManager;
use Illuminate\Events\Dispatcher;
use Carbon\Carbon;

/**
 * Manages credit consumption and tracking for CoinMarketCap API calls.
 */
class CreditManager implements CreditManagerInterface
{
    /**
     * Cache key for storing credit usage data.
     */
    private const CACHE_KEY = 'coinmarketcap_credit_usage';

    /**
     * Cache key for storing daily call count.
     */
    private const DAILY_CALLS_CACHE_KEY = 'coinmarketcap_daily_calls';

    /**
     * Cache key for storing minute call count.
     */
    private const MINUTE_CALLS_CACHE_KEY = 'coinmarketcap_minute_calls';

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * Event dispatcher instance.
     *
     * @var Dispatcher
     */
    private Dispatcher $events;

    /**
     * Plan manager instance.
     *
     * @var PlanManager
     */
    private PlanManager $planManager;

    /**
     * Configuration array.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a new credit manager instance.
     *
     * @param CacheManager $cache Cache manager
     * @param Dispatcher $events Event dispatcher
     * @param PlanManager $planManager Plan manager
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(
        CacheManager $cache,
        Dispatcher $events,
        PlanManager $planManager,
        array $config
    ) {
        $this->cache = $cache;
        $this->events = $events;
        $this->planManager = $planManager;
        $this->config = $config;
    }

    /**
     * Track credit consumption for an API endpoint.
     *
     * @param string $endpoint The API endpoint being called
     * @param int $credits Number of credits consumed
     * @return void
     */
    public function trackUsage(string $endpoint, int $credits): void
    {
        if (!$this->isTrackingEnabled()) {
            return;
        }

        $usageData = $this->getUsageData();
        $currentPeriod = $this->getCurrentBillingPeriod();

        // Initialize period data if not exists
        if (!isset($usageData[$currentPeriod])) {
            $usageData[$currentPeriod] = [
                'total_credits' => 0,
                'endpoints' => [],
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Track total credits
        $usageData[$currentPeriod]['total_credits'] += $credits;

        // Track endpoint-specific usage
        if (!isset($usageData[$currentPeriod]['endpoints'][$endpoint])) {
            $usageData[$currentPeriod]['endpoints'][$endpoint] = [
                'calls' => 0,
                'credits' => 0,
            ];
        }

        $usageData[$currentPeriod]['endpoints'][$endpoint]['calls']++;
        $usageData[$currentPeriod]['endpoints'][$endpoint]['credits'] += $credits;

        // Store updated usage data
        $this->storeUsageData($usageData);

        // Track rate limiting
        $this->trackRateLimit();

        // Dispatch events
        $this->dispatchCreditEvents($endpoint, $credits);

        // Check for warnings
        $this->checkWarningThreshold();
    }

    /**
     * Get remaining credits for current billing period.
     *
     * @return int Number of credits remaining
     */
    public function getRemainingCredits(): int
    {
        $totalCredits = $this->planManager->getMonthlyCredits();
        $usedCredits = $this->getUsedCredits();

        return max(0, $totalCredits - $usedCredits);
    }

    /**
     * Get total credits used in current billing period.
     *
     * @return int Number of credits used
     */
    public function getUsedCredits(): int
    {
        if (!$this->isTrackingEnabled()) {
            return 0;
        }

        $usageData = $this->getUsageData();
        $currentPeriod = $this->getCurrentBillingPeriod();

        return $usageData[$currentPeriod]['total_credits'] ?? 0;
    }

    /**
     * Check if endpoint call would exceed credit limits.
     *
     * @param string $endpoint The API endpoint to check
     * @param int $credits Number of credits that would be consumed
     * @return bool True if call is within limits
     */
    public function canMakeCall(string $endpoint, int $credits): bool
    {
        // Check monthly credit limit
        if ($this->getRemainingCredits() < $credits) {
            return false;
        }

        // Check daily call limit
        if (!$this->canMakeDailyCall()) {
            return false;
        }

        // Check per-minute call limit
        if (!$this->canMakeMinuteCall()) {
            return false;
        }

        return true;
    }

    /**
     * Get credit usage percentage (0-1).
     *
     * @return float Usage percentage
     */
    public function getUsagePercentage(): float
    {
        $totalCredits = $this->planManager->getMonthlyCredits();
        
        if ($totalCredits <= 0) {
            return 0.0;
        }

        $usedCredits = $this->getUsedCredits();
        
        return min(1.0, $usedCredits / $totalCredits);
    }

    /**
     * Check if usage has exceeded warning threshold.
     *
     * @return bool True if warning threshold exceeded
     */
    public function hasExceededWarningThreshold(): bool
    {
        $threshold = $this->getWarningThreshold();
        $usage = $this->getUsagePercentage();

        return $usage >= $threshold;
    }

    /**
     * Reset credit tracking for new billing period.
     *
     * @return void
     */
    public function resetPeriod(): void
    {
        $usageData = $this->getUsageData();
        $currentPeriod = $this->getCurrentBillingPeriod();

        // Clear current period data
        unset($usageData[$currentPeriod]);

        // Clean up old periods (keep last 3 months for reporting)
        $cutoffDate = Carbon::now()->subMonths(3);
        
        foreach ($usageData as $period => $data) {
            try {
                $periodDate = Carbon::parse($period);
                if ($periodDate->lessThan($cutoffDate)) {
                    unset($usageData[$period]);
                }
            } catch (\Exception) {
                // Remove invalid period entries
                unset($usageData[$period]);
            }
        }

        $this->storeUsageData($usageData);
    }

    /**
     * Get detailed usage statistics.
     *
     * @return array<string, mixed> Usage statistics
     */
    public function getUsageStats(): array
    {
        $usageData = $this->getUsageData();
        $currentPeriod = $this->getCurrentBillingPeriod();
        $periodData = $usageData[$currentPeriod] ?? [];

        return [
            'period' => $currentPeriod,
            'total_credits' => $periodData['total_credits'] ?? 0,
            'remaining_credits' => $this->getRemainingCredits(),
            'monthly_limit' => $this->planManager->getMonthlyCredits(),
            'usage_percentage' => $this->getUsagePercentage(),
            'warning_threshold' => $this->getWarningThreshold(),
            'has_exceeded_warning' => $this->hasExceededWarningThreshold(),
            'endpoints' => $periodData['endpoints'] ?? [],
            'daily_calls' => $this->getDailyCallCount(),
            'daily_limit' => $this->planManager->getDailyCallLimit(),
            'minute_calls' => $this->getMinuteCallCount(),
            'minute_limit' => $this->planManager->getMinuteCallLimit(),
            'plan_type' => $this->planManager->getPlanType(),
        ];
    }

    /**
     * Get endpoint-specific usage for current period.
     *
     * @param string $endpoint The endpoint to get usage for
     * @return array<string, mixed> Endpoint usage data
     */
    public function getEndpointUsage(string $endpoint): array
    {
        $usageData = $this->getUsageData();
        $currentPeriod = $this->getCurrentBillingPeriod();
        
        $endpointData = $usageData[$currentPeriod]['endpoints'][$endpoint] ?? [
            'calls' => 0,
            'credits' => 0,
        ];

        $endpointCost = $this->getEndpointCreditCost($endpoint);

        return [
            'endpoint' => $endpoint,
            'calls' => $endpointData['calls'],
            'credits' => $endpointData['credits'],
            'cost_per_call' => $endpointCost,
            'average_cost' => $endpointData['calls'] > 0 ? $endpointData['credits'] / $endpointData['calls'] : 0,
        ];
    }

    /**
     * Check if credit tracking is enabled.
     *
     * @return bool True if tracking is enabled
     */
    private function isTrackingEnabled(): bool
    {
        return $this->config['credits']['tracking_enabled'] ?? true;
    }

    /**
     * Get credit usage data from cache.
     *
     * @return array<string, mixed> Usage data
     */
    private function getUsageData(): array
    {
        $cacheStore = $this->getCacheStore();
        return $cacheStore->get(self::CACHE_KEY, []);
    }

    /**
     * Store credit usage data to cache.
     *
     * @param array<string, mixed> $usageData Usage data
     * @return void
     */
    private function storeUsageData(array $usageData): void
    {
        $cacheStore = $this->getCacheStore();
        // Store for 60 days to allow for billing period overlap
        $cacheStore->put(self::CACHE_KEY, $usageData, 60 * 24 * 60);
    }

    /**
     * Get current billing period identifier.
     *
     * @return string Billing period (YYYY-MM format)
     */
    private function getCurrentBillingPeriod(): string
    {
        return Carbon::now()->format('Y-m');
    }

    /**
     * Get warning threshold from configuration.
     *
     * @return float Warning threshold (0-1)
     */
    private function getWarningThreshold(): float
    {
        return (float) ($this->config['credits']['warning_threshold'] ?? 0.8);
    }

    /**
     * Get endpoint credit cost from configuration.
     *
     * @param string $endpoint The endpoint
     * @return int Credit cost
     */
    private function getEndpointCreditCost(string $endpoint): int
    {
        $costs = $this->config['credits']['costs'] ?? [];
        
        // Normalize endpoint name
        $normalizedEndpoint = str_replace(['/', '-'], '_', trim($endpoint, '/'));
        
        return $costs[$normalizedEndpoint] ?? 1;
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
     * Track daily rate limit calls.
     *
     * @return void
     */
    private function trackRateLimit(): void
    {
        $cacheStore = $this->getCacheStore();
        $today = Carbon::now()->format('Y-m-d');
        $dailyKey = self::DAILY_CALLS_CACHE_KEY . '_' . $today;
        
        // Increment daily call count
        $currentCount = $cacheStore->get($dailyKey, 0);
        $cacheStore->put($dailyKey, $currentCount + 1, 24 * 60); // Store for 24 hours

        // Track minute calls
        $minute = Carbon::now()->format('Y-m-d_H:i');
        $minuteKey = self::MINUTE_CALLS_CACHE_KEY . '_' . $minute;
        
        $minuteCount = $cacheStore->get($minuteKey, 0);
        $cacheStore->put($minuteKey, $minuteCount + 1, 2); // Store for 2 minutes
    }

    /**
     * Check if daily call limit allows another call.
     *
     * @return bool True if within daily limit
     */
    private function canMakeDailyCall(): bool
    {
        $dailyLimit = $this->planManager->getDailyCallLimit();
        
        if ($dailyLimit <= 0) {
            return true; // No limit
        }

        return $this->getDailyCallCount() < $dailyLimit;
    }

    /**
     * Check if per-minute call limit allows another call.
     *
     * @return bool True if within minute limit
     */
    private function canMakeMinuteCall(): bool
    {
        $minuteLimit = $this->planManager->getMinuteCallLimit();
        
        if ($minuteLimit <= 0) {
            return true; // No limit
        }

        return $this->getMinuteCallCount() < $minuteLimit;
    }

    /**
     * Get current daily call count.
     *
     * @return int Daily call count
     */
    private function getDailyCallCount(): int
    {
        $cacheStore = $this->getCacheStore();
        $today = Carbon::now()->format('Y-m-d');
        $dailyKey = self::DAILY_CALLS_CACHE_KEY . '_' . $today;
        
        return $cacheStore->get($dailyKey, 0);
    }

    /**
     * Get current minute call count.
     *
     * @return int Minute call count
     */
    private function getMinuteCallCount(): int
    {
        $cacheStore = $this->getCacheStore();
        $minute = Carbon::now()->format('Y-m-d_H:i');
        $minuteKey = self::MINUTE_CALLS_CACHE_KEY . '_' . $minute;
        
        return $cacheStore->get($minuteKey, 0);
    }

    /**
     * Dispatch credit-related events.
     *
     * @param string $endpoint The endpoint called
     * @param int $credits Credits consumed
     * @return void
     */
    private function dispatchCreditEvents(string $endpoint, int $credits): void
    {
        if (!($this->config['events']['enabled'] ?? true)) {
            return;
        }

        // Credit consumed event
        if ($this->config['events']['dispatch']['credit_consumed'] ?? true) {
            $this->events->dispatch('coinmarketcap.credit.consumed', [
                'endpoint' => $endpoint,
                'credits' => $credits,
                'total_used' => $this->getUsedCredits(),
                'remaining' => $this->getRemainingCredits(),
                'usage_percentage' => $this->getUsagePercentage(),
                'timestamp' => Carbon::now()->toISOString(),
            ]);
        }

        // Rate limit events
        if ($this->config['events']['dispatch']['rate_limit_hit'] ?? true) {
            if (!$this->canMakeDailyCall()) {
                $this->events->dispatch('coinmarketcap.rate_limit.daily_exceeded', [
                    'daily_calls' => $this->getDailyCallCount(),
                    'daily_limit' => $this->planManager->getDailyCallLimit(),
                    'timestamp' => Carbon::now()->toISOString(),
                ]);
            }

            if (!$this->canMakeMinuteCall()) {
                $this->events->dispatch('coinmarketcap.rate_limit.minute_exceeded', [
                    'minute_calls' => $this->getMinuteCallCount(),
                    'minute_limit' => $this->planManager->getMinuteCallLimit(),
                    'timestamp' => Carbon::now()->toISOString(),
                ]);
            }
        }
    }

    /**
     * Check and dispatch warning threshold events.
     *
     * @return void
     */
    private function checkWarningThreshold(): void
    {
        if (!($this->config['events']['enabled'] ?? true)) {
            return;
        }

        if (!($this->config['events']['dispatch']['credit_warning'] ?? true)) {
            return;
        }

        if ($this->hasExceededWarningThreshold()) {
            $this->events->dispatch('coinmarketcap.credit.warning_threshold_exceeded', [
                'usage_percentage' => $this->getUsagePercentage(),
                'warning_threshold' => $this->getWarningThreshold(),
                'used_credits' => $this->getUsedCredits(),
                'total_credits' => $this->planManager->getMonthlyCredits(),
                'remaining_credits' => $this->getRemainingCredits(),
                'timestamp' => Carbon::now()->toISOString(),
            ]);
        }
    }
}