<?php

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * CoinMarketCap Data Provider
 * 
 * Main provider class that implements CryptoDataProvider interface
 */
class CoinMarketCapProvider
{
    private CoinMarketCapClient $client;

    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the client instance
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'coinmarketcap';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->client->getConfig()['api']['key']);
    }
}