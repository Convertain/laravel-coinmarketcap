<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Global Metrics Service
 * 
 * Handles global cryptocurrency market metrics endpoints
 */
class GlobalMetricsService
{
    private CoinMarketCapClient $client;

    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get latest global metrics
     */
    public function getLatest(array $params = []): array
    {
        $response = $this->client->get('/global-metrics/quotes/latest', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get historical global metrics
     */
    public function getHistorical(array $params = []): array
    {
        $response = $this->client->get('/global-metrics/quotes/historical', $params);
        return json_decode($response->getBody()->getContents(), true);
    }
}