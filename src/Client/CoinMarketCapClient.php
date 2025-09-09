<?php

namespace Convertain\CoinMarketCap\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client for CoinMarketCap Pro API with credit tracking and error handling.
 */
class CoinMarketCapClient
{
    /**
     * Guzzle HTTP client instance.
     *
     * @var Client
     */
    private Client $client;

    /**
     * Configuration array.
     *
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a new CoinMarketCap client instance.
     *
     * @param array<string, mixed> $config Configuration array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = $this->createHttpClient();
    }

    /**
     * Make a GET request to the CoinMarketCap API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> API response data
     * @throws \Exception When API request fails
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint);
        
        try {
            $response = $this->client->get($url, [
                'query' => $params,
                'timeout' => $this->config['api']['timeout'] ?? 30,
            ]);

            return $this->processResponse($response);

        } catch (GuzzleException $e) {
            $this->logError('API request failed', [
                'endpoint' => $endpoint,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("CoinMarketCap API request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Make a POST request to the CoinMarketCap API.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request data
     * @return array<string, mixed> API response data
     * @throws \Exception When API request fails
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        
        try {
            $response = $this->client->post($url, [
                'json' => $data,
                'timeout' => $this->config['api']['timeout'] ?? 30,
            ]);

            return $this->processResponse($response);

        } catch (GuzzleException $e) {
            $this->logError('API POST request failed', [
                'endpoint' => $endpoint,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("CoinMarketCap API POST request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the current API configuration.
     *
     * @return array<string, mixed> Configuration array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Create HTTP client with proper configuration.
     *
     * @return Client Configured Guzzle client
     */
    private function createHttpClient(): Client
    {
        $baseUrl = $this->config['api']['base_url'] ?? 'https://pro-api.coinmarketcap.com/v2';
        $apiKey = $this->config['api']['key'] ?? '';

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('CoinMarketCap API key is required');
        }

        return new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'headers' => [
                'X-CMC_PRO_API_KEY' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-CoinMarketCap/1.0',
            ],
            'timeout' => $this->config['api']['timeout'] ?? 30,
        ]);
    }

    /**
     * Build full URL for API endpoint.
     *
     * @param string $endpoint API endpoint
     * @return string Full URL
     */
    private function buildUrl(string $endpoint): string
    {
        return ltrim($endpoint, '/');
    }

    /**
     * Process HTTP response and extract JSON data.
     *
     * @param ResponseInterface $response HTTP response
     * @return array<string, mixed> Processed response data
     * @throws \Exception When response processing fails
     */
    private function processResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        
        if ($this->shouldLogResponse()) {
            $this->logInfo('API response received', [
                'status_code' => $response->getStatusCode(),
                'body_length' => strlen($body),
            ]);
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse JSON response: ' . json_last_error_msg());
        }

        // Check for API errors in response
        if (isset($data['status']['error_code']) && $data['status']['error_code'] !== 0) {
            $errorMessage = $data['status']['error_message'] ?? 'Unknown API error';
            $errorCode = $data['status']['error_code'];
            
            $this->logError('API returned error', [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            throw new \Exception("CoinMarketCap API error: {$errorMessage}", $errorCode);
        }

        return $data;
    }

    /**
     * Check if request logging is enabled.
     *
     * @return bool True if should log requests
     */
    private function shouldLogRequest(): bool
    {
        return $this->config['logging']['enabled'] ?? true && 
               $this->config['logging']['log_requests'] ?? false;
    }

    /**
     * Check if response logging is enabled.
     *
     * @return bool True if should log responses
     */
    private function shouldLogResponse(): bool
    {
        return $this->config['logging']['enabled'] ?? true && 
               $this->config['logging']['log_responses'] ?? false;
    }

    /**
     * Log informational message.
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Log context
     * @return void
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->config['logging']['enabled'] ?? true) {
            Log::channel($this->config['logging']['channel'] ?? 'stack')
               ->info($message, $context);
        }
    }

    /**
     * Log error message.
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Log context
     * @return void
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->config['logging']['enabled'] ?? true) {
            Log::channel($this->config['logging']['channel'] ?? 'stack')
               ->error($message, $context);
        }
    }
}