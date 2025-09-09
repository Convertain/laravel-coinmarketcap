<?php

namespace Convertain\CoinMarketCap\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * CoinMarketCap Pro API Client
 *
 * Handles all communication with the CoinMarketCap Pro API including
 * credit management, caching, rate limiting, and error handling.
 */
class CoinMarketCapClient
{
    private Client $httpClient;
    private array $config;
    private int $creditsUsed = 0;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config['api']['base_url'],
            'timeout' => $config['api']['timeout'],
            'headers' => [
                'X-CMC_PRO_API_KEY' => $config['api']['key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    
    /**
     * Make an API request with caching and credit management
     *
     * @param string $endpoint API endpoint
     * @param array $parameters Query parameters
     * @param int|null $cacheTtl Cache TTL in seconds
     * @return array Response data
     * @throws \Exception
     */
    public function get(string $endpoint, array $parameters = [], ?int $cacheTtl = null): array
    {
        $cacheKey = $this->getCacheKey($endpoint, $parameters);
        
        // Check cache first if caching enabled
        if ($this->config['cache']['enabled'] && $cacheTtl !== null) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Check credit limit
        $this->checkCreditLimit();
        
        try {
            $response = $this->httpClient->get($endpoint, [
                'query' => $parameters,
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Track credit usage
            $this->trackCreditUsage($endpoint);
            
            // Cache response if enabled
            if ($this->config['cache']['enabled'] && $cacheTtl !== null) {
                Cache::put($cacheKey, $data, $cacheTtl);
            }
            
            // Log request if enabled
            if ($this->config['logging']['log_requests']) {
                Log::info('CoinMarketCap API Request', [
                    'endpoint' => $endpoint,
                    'parameters' => $parameters,
                ]);
            }
            
            return $data;
            
        } catch (RequestException $e) {
            $this->handleApiError($e, $endpoint, $parameters);
            throw $e;
        }
    }
    
    /**
     * Generate cache key for request
     */
    private function getCacheKey(string $endpoint, array $parameters): string
    {
        $key = $this->config['cache']['prefix'] . ':' . str_replace('/', '_', $endpoint);
        if (!empty($parameters)) {
            $key .= ':' . md5(serialize($parameters));
        }
        return $key;
    }
    
    /**
     * Check if we're approaching credit limits
     */
    private function checkCreditLimit(): void
    {
        if (!$this->config['credits']['tracking_enabled']) {
            return;
        }
        
        $monthlyLimit = $this->config['plan']['credits_per_month'];
        $warningThreshold = $this->config['credits']['warning_threshold'];
        
        if ($this->creditsUsed >= ($monthlyLimit * $warningThreshold)) {
            Log::warning('CoinMarketCap API credit warning threshold reached', [
                'used' => $this->creditsUsed,
                'limit' => $monthlyLimit,
                'threshold' => $warningThreshold,
            ]);
        }
    }
    
    /**
     * Track credit usage for endpoint
     */
    private function trackCreditUsage(string $endpoint): void
    {
        if (!$this->config['credits']['tracking_enabled']) {
            return;
        }
        
        $endpointKey = str_replace(['/', '-'], '_', trim($endpoint, '/'));
        $creditCost = $this->config['credits']['costs'][$endpointKey] ?? 1;
        
        $this->creditsUsed += $creditCost;
        
        if ($this->config['logging']['log_credits']) {
            Log::info('CoinMarketCap API credits consumed', [
                'endpoint' => $endpoint,
                'cost' => $creditCost,
                'total_used' => $this->creditsUsed,
            ]);
        }
    }
    
    /**
     * Handle API errors
     */
    private function handleApiError(RequestException $e, string $endpoint, array $parameters): void
    {
        $context = [
            'endpoint' => $endpoint,
            'parameters' => $parameters,
            'error' => $e->getMessage(),
        ];
        
        if ($e->hasResponse()) {
            $context['status_code'] = $e->getResponse()->getStatusCode();
            $context['response'] = $e->getResponse()->getBody()->getContents();
        }
        
        Log::error('CoinMarketCap API error', $context);
    }
    
    /**
     * Get current credit usage
     */
    public function getCreditsUsed(): int
    {
        return $this->creditsUsed;
    }
    
    /**
     * Reset credit counter (useful for testing)
     */
    public function resetCredits(): void
    {
        $this->creditsUsed = 0;
    }
}