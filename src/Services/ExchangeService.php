<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Exchange Service
 * 
 * Handles exchange-related API endpoints
 */
class ExchangeService
{
    private CoinMarketCapClient $client;

    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get exchange listings
     */
    public function getListings(array $params = []): array
    {
        $response = $this->client->get('/exchange/listings/latest', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get exchange quotes
     */
    public function getQuotes(array $params = []): array
    {
        $response = $this->client->get('/exchange/quotes/latest', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get exchange info
     */
    public function getInfo(array $params = []): array
    {
        $response = $this->client->get('/exchange/info', $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get exchange map
     */
    public function getMap(array $params = []): array
    {
        $response = $this->client->get('/exchange/map', $params);
        return json_decode($response->getBody()->getContents(), true);
    }
}