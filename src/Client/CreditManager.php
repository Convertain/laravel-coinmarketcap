<?php

namespace Convertain\CoinMarketCap\Client;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Convertain\CoinMarketCap\Exceptions\CreditLimitExceededException;

/**
 * Credit Manager for tracking and managing CoinMarketCap API credits
 */
class CreditManager
{
    /**
     * Configuration array
     */
    protected array $config;
    
    /**
     * Cache store name
     */
    protected ?string $cacheStore;
    
    /**
     * Cache prefix for credit tracking
     */
    protected string $cachePrefix;
    
    /**
     * Create a new credit manager instance
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->cacheStore = $config['cache']['store'] ?? null;
        $this->cachePrefix = ($config['cache']['prefix'] ?? 'coinmarketcap') . ':credits';
    }
    
    /**
     * Check if we can make a request with the given credit cost
     */
    public function canMakeRequest(int $credits = 1): bool
    {
        if (!$this->isTrackingEnabled()) {
            return true;
        }
        
        $usage = $this->getCurrentUsage();
        $limit = $this->getMonthlyLimit();
        
        return ($usage + $credits) <= $limit;
    }
    
    /**
     * Consume credits for an API call
     */
    public function consumeCredits(string $endpoint, int $credits = 1): void
    {
        if (!$this->isTrackingEnabled()) {
            return;
        }
        
        // Check if we can make the request
        if (!$this->canMakeRequest($credits)) {
            throw new CreditLimitExceededException(
                "Credit limit would be exceeded. Current usage: {$this->getCurrentUsage()}, " .
                "Monthly limit: {$this->getMonthlyLimit()}, Requested credits: {$credits}"
            );
        }
        
        // Update usage
        $this->incrementUsage($credits);
        
        // Log credit consumption
        if ($this->config['logging']['log_credits'] ?? true) {
            Log::channel($this->config['logging']['channel'] ?? 'stack')->info(
                "CoinMarketCap API credits consumed",
                [
                    'endpoint' => $endpoint,
                    'credits' => $credits,
                    'total_usage' => $this->getCurrentUsage(),
                    'monthly_limit' => $this->getMonthlyLimit(),
                ]
            );
        }
        
        // Dispatch event
        if ($this->config['events']['enabled'] ?? true && ($this->config['events']['dispatch']['credit_consumed'] ?? true)) {
            Event::dispatch('coinmarketcap.credit.consumed', [
                'endpoint' => $endpoint,
                'credits' => $credits,
                'usage' => $this->getCurrentUsage(),
                'limit' => $this->getMonthlyLimit(),
            ]);
        }
        
        // Check warning threshold
        $this->checkWarningThreshold();
    }
    
    /**
     * Get current monthly credit usage
     */
    public function getCurrentUsage(): int
    {
        $key = $this->getCacheKey('usage');
        return Cache::store($this->cacheStore)->get($key, 0);
    }
    
    /**
     * Get monthly credit limit
     */
    public function getMonthlyLimit(): int
    {
        $planType = $this->config['plan']['type'] ?? 'basic';
        
        if (isset($this->config['plans'][$planType]['credits_per_month'])) {
            return $this->config['plans'][$planType]['credits_per_month'];
        }
        
        return $this->config['plan']['credits_per_month'] ?? 10000;
    }
    
    /**
     * Get remaining credits for the month
     */
    public function getRemainingCredits(): int
    {
        return max(0, $this->getMonthlyLimit() - $this->getCurrentUsage());
    }
    
    /**
     * Get credit usage percentage
     */
    public function getUsagePercentage(): float
    {
        $limit = $this->getMonthlyLimit();
        
        if ($limit === 0) {
            return 0.0;
        }
        
        return ($this->getCurrentUsage() / $limit) * 100;
    }
    
    /**
     * Reset credit usage (typically called at the beginning of a new month)
     */
    public function resetUsage(): void
    {
        $key = $this->getCacheKey('usage');
        Cache::store($this->cacheStore)->forget($key);
        
        Log::channel($this->config['logging']['channel'] ?? 'stack')->info(
            "CoinMarketCap credit usage reset for new month"
        );
    }
    
    /**
     * Get credit cost for an endpoint
     */
    public function getCreditCost(string $endpoint): int
    {
        // Remove leading slash for consistency
        $endpoint = ltrim($endpoint, '/');
        
        // Convert endpoint path to config key format
        $endpointKey = str_replace(['/', '-'], '_', $endpoint);
        
        return $this->config['credits']['costs'][$endpointKey] ?? 1;
    }
    
    /**
     * Check if credit tracking is enabled
     */
    protected function isTrackingEnabled(): bool
    {
        return $this->config['credits']['tracking_enabled'] ?? true;
    }
    
    /**
     * Increment credit usage
     */
    protected function incrementUsage(int $credits): void
    {
        $key = $this->getCacheKey('usage');
        $expiresAt = Carbon::now()->endOfMonth();
        
        $currentUsage = Cache::store($this->cacheStore)->get($key, 0);
        Cache::store($this->cacheStore)->put($key, $currentUsage + $credits, $expiresAt);
    }
    
    /**
     * Check warning threshold and dispatch event if needed
     */
    protected function checkWarningThreshold(): void
    {
        $threshold = $this->config['credits']['warning_threshold'] ?? 0.8;
        $usagePercentage = $this->getUsagePercentage() / 100;
        
        if ($usagePercentage >= $threshold) {
            // Only warn once per month at threshold
            $warningKey = $this->getCacheKey('warning_sent');
            
            if (!Cache::store($this->cacheStore)->has($warningKey)) {
                Cache::store($this->cacheStore)->put($warningKey, true, Carbon::now()->endOfMonth());
                
                if ($this->config['events']['enabled'] ?? true && ($this->config['events']['dispatch']['credit_warning'] ?? true)) {
                    Event::dispatch('coinmarketcap.credit.warning', [
                        'usage' => $this->getCurrentUsage(),
                        'limit' => $this->getMonthlyLimit(),
                        'percentage' => $this->getUsagePercentage(),
                        'threshold' => $threshold * 100,
                    ]);
                }
                
                Log::channel($this->config['logging']['channel'] ?? 'stack')->warning(
                    "CoinMarketCap credit usage warning threshold reached",
                    [
                        'usage' => $this->getCurrentUsage(),
                        'limit' => $this->getMonthlyLimit(),
                        'percentage' => $this->getUsagePercentage(),
                        'threshold' => $threshold * 100,
                    ]
                );
            }
        }
    }
    
    /**
     * Generate cache key for credit tracking
     */
    protected function getCacheKey(string $suffix): string
    {
        $month = Carbon::now()->format('Y-m');
        return "{$this->cachePrefix}:{$month}:{$suffix}";
    }
}