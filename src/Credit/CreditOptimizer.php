<?php

namespace Convertain\CoinMarketCap\Credit;

/**
 * Credit Optimizer
 * 
 * Optimizes API calls to minimize credit consumption
 */
class CreditOptimizer
{
    private array $config;
    private CreditManager $creditManager;

    public function __construct(array $config, CreditManager $creditManager)
    {
        $this->config = $config;
        $this->creditManager = $creditManager;
    }

    /**
     * Optimize parameters to reduce credit cost
     */
    public function optimizeParams(string $endpoint, array $params): array
    {
        if (!$this->config['credits']['optimization_enabled']) {
            return $params;
        }

        return match ($endpoint) {
            'cryptocurrency_listings_latest' => $this->optimizeListingsParams($params),
            'cryptocurrency_quotes_latest' => $this->optimizeQuotesParams($params),
            default => $params,
        };
    }

    /**
     * Optimize listing parameters
     */
    private function optimizeListingsParams(array $params): array
    {
        // Limit results if not specified to reduce credit usage
        if (!isset($params['limit'])) {
            $params['limit'] = 100; // Reasonable default
        }

        // Remove expensive aux parameters if credit usage is high
        if ($this->creditManager->getUsageStats()['usage_percentage'] > 70) {
            unset($params['aux']);
        }

        return $params;
    }

    /**
     * Optimize quotes parameters
     */
    private function optimizeQuotesParams(array $params): array
    {
        // Limit convert currencies if credit usage is high
        if ($this->creditManager->getUsageStats()['usage_percentage'] > 80) {
            $params['convert'] = 'USD'; // Use only USD to minimize credits
        }

        return $params;
    }

    /**
     * Suggest alternative endpoints with lower credit costs
     */
    public function suggestAlternatives(string $endpoint): array
    {
        return match ($endpoint) {
            'cryptocurrency_listings_latest' => [
                'alternatives' => ['cryptocurrency_map'],
                'reason' => 'Use map endpoint first, then quotes for specific currencies',
            ],
            'cryptocurrency_quotes_historical' => [
                'alternatives' => ['cryptocurrency_ohlcv_latest'],
                'reason' => 'Use latest OHLCV instead of historical data if suitable',
            ],
            default => [],
        };
    }
}