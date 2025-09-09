<?php

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * CoinMarketCap Provider - Placeholder implementation
 * This will be fully implemented in a future task (TASK003)
 */
class CoinMarketCapProvider
{
    /**
     * CoinMarketCap API client
     */
    protected CoinMarketCapClient $client;
    
    /**
     * Create a new provider instance
     */
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get the API client
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }
}