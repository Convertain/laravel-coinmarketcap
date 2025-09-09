<?php

namespace Convertain\CoinMarketCap\Credit;

/**
 * Credit Manager
 * 
 * Manages CoinMarketCap API credit tracking and limits
 */
class CreditManager
{
    private array $config;
    private int $usedCredits = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Track credit consumption for an endpoint
     */
    public function consumeCredits(string $endpoint, int $credits = 1): bool
    {
        $this->usedCredits += $credits;
        
        // Check if we're approaching limit
        $warningThreshold = $this->config['credits']['warning_threshold'] ?? 0.8;
        $monthlyLimit = $this->config['plan']['credits_per_month'] ?? 10000;
        
        if ($this->usedCredits >= ($monthlyLimit * $warningThreshold)) {
            // Trigger warning event
            return false;
        }
        
        return true;
    }

    /**
     * Get credit usage statistics
     */
    public function getUsageStats(): array
    {
        $monthlyLimit = $this->config['plan']['credits_per_month'] ?? 10000;
        
        return [
            'used' => $this->usedCredits,
            'limit' => $monthlyLimit,
            'remaining' => $monthlyLimit - $this->usedCredits,
            'usage_percentage' => ($this->usedCredits / $monthlyLimit) * 100,
        ];
    }

    /**
     * Get credit cost for an endpoint
     */
    public function getCreditCost(string $endpoint): int
    {
        return $this->config['credits']['costs'][$endpoint] ?? 1;
    }

    /**
     * Check if we have sufficient credits
     */
    public function hasSufficientCredits(string $endpoint): bool
    {
        $cost = $this->getCreditCost($endpoint);
        $monthlyLimit = $this->config['plan']['credits_per_month'] ?? 10000;
        
        return ($this->usedCredits + $cost) <= $monthlyLimit;
    }
}