<?php

namespace Convertain\CoinMarketCap\Client;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * CoinMarketCap API Client
 * 
 * Handles low-level HTTP communication with CoinMarketCap Pro API
 */
class CoinMarketCapClient
{
    private Client $httpClient;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config['api']['base_url'],
            'timeout' => $config['api']['timeout'],
            'headers' => [
                'X-CMC_PRO_API_KEY' => $config['api']['key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a GET request to the API
     */
    public function get(string $endpoint, array $params = []): ResponseInterface
    {
        return $this->httpClient->get($endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Make a POST request to the API
     */
    public function post(string $endpoint, array $data = []): ResponseInterface
    {
        return $this->httpClient->post($endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Get the current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}