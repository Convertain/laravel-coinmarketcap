<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Main CoinMarketCap provider class for cryptocurrency data.
 */
class CoinMarketCapProvider
{
    public function __construct(
        private CoinMarketCapClient $client
    ) {
    }

    /**
     * Get the API client instance.
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'coinmarketcap';
    }

    /**
     * Check if provider is enabled.
     */
    public function isEnabled(): bool
    {
        return config('coinmarketcap.provider.enabled', true);
    }

    /**
     * Get provider priority.
     */
    public function getPriority(): int
    {
        return config('coinmarketcap.provider.priority', 2);
    }
}