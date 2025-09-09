<?php

namespace Convertain\CoinMarketCap\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Convertain\CoinMarketCap\Client\CreditManager;
use Convertain\CoinMarketCap\Client\ResponseValidator;
use Convertain\CoinMarketCap\Exceptions\NetworkException;
use Convertain\CoinMarketCap\Exceptions\RateLimitExceededException;

/**
 * CoinMarketCap API Client with multi-endpoint support
 */
class CoinMarketCapClient
{
    /**
     * HTTP client instance
     */
    protected HttpClient $httpClient;
    
    /**
     * Credit manager instance
     */
    protected CreditManager $creditManager;
    
    /**
     * Response validator instance
     */
    protected ResponseValidator $responseValidator;
    
    /**
     * Configuration array
     */
    protected array $config;
    
    /**
     * Base URL for API requests
     */
    protected string $baseUrl;
    
    /**
     * API key for authentication
     */
    protected string $apiKey;
    
    /**
     * Cache store name
     */
    protected ?string $cacheStore;
    
    /**
     * Cache prefix
     */
    protected string $cachePrefix;
    
    /**
     * Create a new client instance
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['api']['base_url'] ?? 'https://pro-api.coinmarketcap.com/v2';
        $this->apiKey = $config['api']['key'] ?? '';
        $this->cacheStore = $config['cache']['store'] ?? null;
        $this->cachePrefix = $config['cache']['prefix'] ?? 'coinmarketcap';
        
        $this->initializeHttpClient();
        $this->creditManager = new CreditManager($config);
        $this->responseValidator = new ResponseValidator();
    }
    
    /**
     * Make a request to a cryptocurrency endpoint
     */
    public function cryptocurrency(string $endpoint, array $parameters = []): array
    {
        return $this->makeRequest("cryptocurrency/{$endpoint}", $parameters);
    }
    
    /**
     * Make a request to an exchange endpoint
     */
    public function exchange(string $endpoint, array $parameters = []): array
    {
        return $this->makeRequest("exchange/{$endpoint}", $parameters);
    }
    
    /**
     * Make a request to a global metrics endpoint
     */
    public function globalMetrics(string $endpoint, array $parameters = []): array
    {
        return $this->makeRequest("global-metrics/{$endpoint}", $parameters);
    }
    
    /**
     * Make a request to a fiat endpoint
     */
    public function fiat(string $endpoint, array $parameters = []): array
    {
        return $this->makeRequest("fiat/{$endpoint}", $parameters);
    }
    
    /**
     * Make a general API request with full endpoint path
     */
    public function request(string $endpoint, array $parameters = []): array
    {
        return $this->makeRequest($endpoint, $parameters);
    }
    
    /**
     * Get cryptocurrency listings (latest)
     */
    public function getCryptocurrencyListings(array $parameters = []): array
    {
        return $this->cryptocurrency('listings/latest', $parameters);
    }
    
    /**
     * Get cryptocurrency quotes (latest)
     */
    public function getCryptocurrencyQuotes(array $parameters = []): array
    {
        return $this->cryptocurrency('quotes/latest', $parameters);
    }
    
    /**
     * Get cryptocurrency info
     */
    public function getCryptocurrencyInfo(array $parameters = []): array
    {
        return $this->cryptocurrency('info', $parameters);
    }
    
    /**
     * Get cryptocurrency map
     */
    public function getCryptocurrencyMap(array $parameters = []): array
    {
        return $this->cryptocurrency('map', $parameters);
    }
    
    /**
     * Get exchange listings (latest)
     */
    public function getExchangeListings(array $parameters = []): array
    {
        return $this->exchange('listings/latest', $parameters);
    }
    
    /**
     * Get exchange quotes (latest)
     */
    public function getExchangeQuotes(array $parameters = []): array
    {
        return $this->exchange('quotes/latest', $parameters);
    }
    
    /**
     * Get global metrics quotes (latest)
     */
    public function getGlobalMetrics(array $parameters = []): array
    {
        return $this->globalMetrics('quotes/latest', $parameters);
    }
    
    /**
     * Make the actual HTTP request with retry logic and caching
     */
    protected function makeRequest(string $endpoint, array $parameters = []): array
    {
        // Check rate limiting
        $this->checkRateLimit();
        
        // Check and consume credits
        $creditCost = $this->creditManager->getCreditCost($endpoint);
        
        // Check cache first
        $cacheKey = $this->getCacheKey($endpoint, $parameters);
        $ttl = $this->getCacheTtl($endpoint);
        
        if ($this->isCacheEnabled() && $ttl > 0) {
            $cached = Cache::store($this->cacheStore)->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Consume credits before making request
        $this->creditManager->consumeCredits($endpoint, $creditCost);
        
        // Make request with retry logic
        $response = $this->makeRequestWithRetry($endpoint, $parameters);
        
        // Validate and parse response
        $data = $this->responseValidator->validate($response);
        
        // Cache the response
        if ($this->isCacheEnabled() && $ttl > 0) {
            Cache::store($this->cacheStore)->put($cacheKey, $data, $ttl);
        }
        
        // Log request if enabled
        if ($this->config['logging']['log_requests'] ?? false) {
            $this->logRequest($endpoint, $parameters, $response->getStatusCode());
        }
        
        // Dispatch event
        if ($this->config['events']['enabled'] ?? true && ($this->config['events']['dispatch']['api_call_made'] ?? true)) {
            Event::dispatch('coinmarketcap.api.call_made', [
                'endpoint' => $endpoint,
                'parameters' => $parameters,
                'credits' => $creditCost,
                'cached' => false,
            ]);
        }
        
        return $data;
    }
    
    /**
     * Make HTTP request with exponential backoff retry logic
     */
    protected function makeRequestWithRetry(string $endpoint, array $parameters): ResponseInterface
    {
        $retryTimes = $this->config['api']['retry_times'] ?? 3;
        $retryDelay = $this->config['api']['retry_delay'] ?? 1000; // milliseconds
        
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $retryTimes; $attempt++) {
            try {
                return $this->makeHttpRequest($endpoint, $parameters);
            } catch (ConnectException $e) {
                $lastException = new NetworkException("Connection failed: " . $e->getMessage(), 0, $e);
            } catch (RequestException $e) {
                // Don't retry on client errors (4xx)
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() < 500) {
                    throw $e;
                }
                $lastException = new NetworkException("Request failed: " . $e->getMessage(), 0, $e);
            } catch (TransferException $e) {
                $lastException = new NetworkException("Transfer failed: " . $e->getMessage(), 0, $e);
            }
            
            // If this was the last attempt, throw the exception
            if ($attempt === $retryTimes) {
                throw $lastException;
            }
            
            // Calculate exponential backoff delay
            $delay = $retryDelay * (2 ** ($attempt - 1));
            usleep($delay * 1000); // Convert to microseconds
        }
        
        throw $lastException;
    }
    
    /**
     * Make the actual HTTP request
     */
    protected function makeHttpRequest(string $endpoint, array $parameters): ResponseInterface
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        
        $options = [
            'headers' => [
                'X-CMC_PRO_API_KEY' => $this->apiKey,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'deflate, gzip',
            ],
            'timeout' => $this->config['api']['timeout'] ?? 30,
            'query' => $parameters,
        ];
        
        return $this->httpClient->get($url, $options);
    }
    
    /**
     * Check rate limiting
     */
    protected function checkRateLimit(): void
    {
        $planType = $this->config['plan']['type'] ?? 'basic';
        $callsPerMinute = $this->config['plans'][$planType]['calls_per_minute'] ?? 
                          $this->config['plan']['calls_per_minute'] ?? 30;
        
        $key = "{$this->cachePrefix}:rate_limit:" . Carbon::now()->format('Y-m-d-H-i');
        $currentCalls = Cache::store($this->cacheStore)->get($key, 0);
        
        if ($currentCalls >= $callsPerMinute) {
            if ($this->config['events']['enabled'] ?? true && ($this->config['events']['dispatch']['rate_limit_hit'] ?? true)) {
                Event::dispatch('coinmarketcap.rate_limit.hit', [
                    'calls_per_minute' => $callsPerMinute,
                    'current_calls' => $currentCalls,
                ]);
            }
            
            throw new RateLimitExceededException(
                "Rate limit exceeded: {$currentCalls}/{$callsPerMinute} calls per minute"
            );
        }
        
        // Increment rate limit counter
        Cache::store($this->cacheStore)->put($key, $currentCalls + 1, 60);
    }
    
    /**
     * Initialize HTTP client
     */
    protected function initializeHttpClient(): void
    {
        $this->httpClient = new HttpClient([
            'timeout' => $this->config['api']['timeout'] ?? 30,
            'connect_timeout' => 10,
            'verify' => true,
        ]);
    }
    
    /**
     * Check if caching is enabled
     */
    protected function isCacheEnabled(): bool
    {
        return $this->config['cache']['enabled'] ?? true;
    }
    
    /**
     * Get cache TTL for endpoint
     */
    protected function getCacheTtl(string $endpoint): int
    {
        $endpointKey = $this->getEndpointCacheKey($endpoint);
        return $this->config['cache']['ttl'][$endpointKey] ?? 300; // Default 5 minutes
    }
    
    /**
     * Generate cache key for request
     */
    protected function getCacheKey(string $endpoint, array $parameters): string
    {
        $paramHash = md5(json_encode($parameters));
        return "{$this->cachePrefix}:response:{$endpoint}:{$paramHash}";
    }
    
    /**
     * Convert endpoint to cache key format
     */
    protected function getEndpointCacheKey(string $endpoint): string
    {
        // Handle different endpoint patterns
        $patterns = [
            'cryptocurrency/listings/latest' => 'cryptocurrency_listings',
            'cryptocurrency/quotes/latest' => 'cryptocurrency_quotes',
            'cryptocurrency/quotes/historical' => 'historical',
            'cryptocurrency/info' => 'cryptocurrency_info',
            'cryptocurrency/map' => 'cryptocurrency_map',
            'cryptocurrency/market-pairs/latest' => 'market_pairs',
            'cryptocurrency/ohlcv/latest' => 'ohlcv',
            'cryptocurrency/ohlcv/historical' => 'ohlcv',
            'cryptocurrency/trending/latest' => 'trending',
            'exchange/listings/latest' => 'exchange_listings',
            'exchange/quotes/latest' => 'exchange_quotes',
            'exchange/info' => 'exchange_info',
            'exchange/map' => 'exchange_map',
            'global-metrics/quotes/latest' => 'global_metrics',
            'global-metrics/quotes/historical' => 'global_metrics',
            'fiat/map' => 'fiat_map',
        ];
        
        return $patterns[$endpoint] ?? 'default';
    }
    
    /**
     * Log API request
     */
    protected function logRequest(string $endpoint, array $parameters, int $statusCode): void
    {
        Log::channel($this->config['logging']['channel'] ?? 'stack')->info(
            "CoinMarketCap API request made",
            [
                'endpoint' => $endpoint,
                'parameters' => $parameters,
                'status_code' => $statusCode,
                'timestamp' => Carbon::now()->toISOString(),
            ]
        );
    }
    
    /**
     * Get credit manager instance
     */
    public function getCreditManager(): CreditManager
    {
        return $this->creditManager;
    }
    
    /**
     * Get response validator instance
     */
    public function getResponseValidator(): ResponseValidator
    {
        return $this->responseValidator;
    }
}