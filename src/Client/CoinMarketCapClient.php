<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * CoinMarketCap Pro API v2 HTTP client with credit optimization and caching.
 */
class CoinMarketCapClient
{
    public function __construct(
        private array $config,
        private ?Client $httpClient = null
    ) {
        $this->httpClient = $this->httpClient ?? new Client([
            'base_uri' => $this->config['api']['base_url'],
            'timeout' => $this->config['api']['timeout'],
            'headers' => [
                'X-CMC_PRO_API_KEY' => $this->config['api']['key'],
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel CoinMarketCap Client/1.0',
            ],
        ]);
    }

    /**
     * Make a GET request to the CoinMarketCap API with caching and error handling.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $parameters = [], ?int $cacheTtl = null): array
    {
        $cacheKey = $this->getCacheKey($endpoint, $parameters);
        
        if ($this->shouldCache($endpoint) && $this->cacheGet($cacheKey)) {
            if ($this->config['logging']['enabled']) {
                $this->log('info', 'CoinMarketCap API cache hit', ['endpoint' => $endpoint]);
            }
            return $this->cacheGet($cacheKey);
        }

        try {
            $response = $this->httpClient->get($endpoint, [
                'query' => $parameters,
            ]);

            $data = $this->processResponse($response);
            
            // Cache successful responses
            if ($this->shouldCache($endpoint)) {
                $ttl = $cacheTtl ?? $this->getCacheTtl($endpoint);
                $this->cachePut($cacheKey, $data, $ttl);
            }

            // Log credit consumption
            $this->logCreditUsage($endpoint, $parameters);

            return $data;

        } catch (RequestException $e) {
            if ($this->config['logging']['enabled']) {
                $this->log('error', 'CoinMarketCap API request failed', [
                    'endpoint' => $endpoint,
                    'parameters' => $parameters,
                    'error' => $e->getMessage(),
                ]);
            }

            throw new \RuntimeException(
                "CoinMarketCap API request failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Process the API response and extract data.
     *
     * @return array<string, mixed>
     */
    private function processResponse(ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON response from CoinMarketCap API');
        }

        if (isset($data['status']['error_code']) && $data['status']['error_code'] !== 0) {
            throw new \RuntimeException(
                'CoinMarketCap API error: ' . ($data['status']['error_message'] ?? 'Unknown error')
            );
        }

        return $data;
    }

    /**
     * Generate cache key for endpoint and parameters.
     */
    private function getCacheKey(string $endpoint, array $parameters): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'coinmarketcap';
        $paramHash = md5(serialize($parameters));
        
        return "{$prefix}:" . str_replace('/', '_', $endpoint) . ":{$paramHash}";
    }

    /**
     * Check if endpoint should be cached.
     */
    private function shouldCache(string $endpoint): bool
    {
        return $this->config['cache']['enabled'] ?? true;
    }

    /**
     * Get cache TTL for endpoint.
     */
    private function getCacheTtl(string $endpoint): int
    {
        $endpointKey = $this->getEndpointCacheKey($endpoint);
        return $this->config['cache']['ttl'][$endpointKey] ?? 300; // Default 5 minutes
    }

    /**
     * Get cache key for endpoint.
     */
    private function getEndpointCacheKey(string $endpoint): string
    {
        if (str_contains($endpoint, '/cryptocurrency/map')) {
            return 'cryptocurrency_map';
        }
        if (str_contains($endpoint, '/cryptocurrency/info')) {
            return 'cryptocurrency_info';
        }
        if (str_contains($endpoint, '/cryptocurrency/listings')) {
            return 'cryptocurrency_listings';
        }
        if (str_contains($endpoint, '/cryptocurrency/quotes')) {
            return 'cryptocurrency_quotes';
        }
        if (str_contains($endpoint, '/cryptocurrency/market-pairs')) {
            return 'market_pairs';
        }
        if (str_contains($endpoint, '/cryptocurrency/ohlcv')) {
            return 'ohlcv';
        }
        if (str_contains($endpoint, '/cryptocurrency/trending')) {
            return 'trending';
        }
        if (str_contains($endpoint, 'historical')) {
            return 'historical';
        }

        return 'default';
    }

    /**
     * Log credit usage for monitoring.
     *
     * @param array<string, mixed> $parameters
     */
    private function logCreditUsage(string $endpoint, array $parameters): void
    {
        if (!($this->config['logging']['log_credits'] ?? true)) {
            return;
        }

        $credits = $this->calculateCreditCost($endpoint, $parameters);
        
        $this->log('info', 'CoinMarketCap API credit usage', [
            'endpoint' => $endpoint,
            'credits' => $credits,
            'parameters_count' => count($parameters),
        ]);
    }

    /**
     * Calculate credit cost for endpoint call.
     *
     * @param array<string, mixed> $parameters
     */
    private function calculateCreditCost(string $endpoint, array $parameters): int
    {
        $endpointKey = str_replace(['/', '-'], '_', trim($endpoint, '/'));
        $baseCost = $this->config['credits']['costs'][$endpointKey] ?? 1;
        
        // Additional cost for multiple symbols/IDs
        $multiplier = 1;
        if (isset($parameters['symbol'])) {
            $multiplier = max(1, count(explode(',', $parameters['symbol'])));
        } elseif (isset($parameters['id'])) {
            $multiplier = max(1, count(explode(',', $parameters['id'])));
        }
        
        return $baseCost * $multiplier;
    }

    /**
     * Cache get helper method.
     *
     * @return mixed
     */
    private function cacheGet(string $key)
    {
        if (class_exists('Illuminate\Support\Facades\Cache')) {
            return \Illuminate\Support\Facades\Cache::get($key);
        }
        return null;
    }

    /**
     * Cache put helper method.
     *
     * @param mixed $value
     */
    private function cachePut(string $key, $value, int $ttl): void
    {
        if (class_exists('Illuminate\Support\Facades\Cache')) {
            \Illuminate\Support\Facades\Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Logging helper method.
     *
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (!($this->config['logging']['enabled'] ?? true)) {
            return;
        }

        if (class_exists('Illuminate\Support\Facades\Log')) {
            \Illuminate\Support\Facades\Log::{$level}($message, $context);
        } else {
            // Fallback to error_log for non-Laravel environments
            $logMessage = $message . ' ' . json_encode($context);
            error_log("[$level] $logMessage");
        }
    }
}