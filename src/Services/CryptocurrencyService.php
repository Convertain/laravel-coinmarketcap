<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Cryptocurrency Service
 * 
 * Handles cryptocurrency-related API endpoints
 */
class CryptocurrencyService
{
    private CoinMarketCapClient $client;

    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get cryptocurrency listings
     */
    public function getListings(array $params = []): array
    {
        $response = $this->client->get('/cryptocurrency/listings/latest', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get cryptocurrency quotes
     */
    public function getQuotes(array $params = []): array
    {
        $response = $this->client->get('/cryptocurrency/quotes/latest', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get cryptocurrency info
     */
    public function getInfo(array $params = []): array
    {
        $response = $this->client->get('/cryptocurrency/info', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get cryptocurrency map
     */
    public function getMap(array $params = []): array
    {
        $response = $this->client->get('/cryptocurrency/map', $params);
        return json_decode($response->getBody()->getContents(), true);
    }
}