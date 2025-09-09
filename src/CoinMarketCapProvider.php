<?php

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;
use Convertain\CoinMarketCap\Transformers\ExchangeTransformer;
use Convertain\CoinMarketCap\Transformers\GlobalMetricsTransformer;
use Convertain\CoinMarketCap\Credit\CreditManager;
use Convertain\CoinMarketCap\Credit\CreditOptimizer;

/**
 * Main CoinMarketCap provider that implements cryptocurrency data provider interface.
 */
class CoinMarketCapProvider
{
    /**
     * CoinMarketCap API client instance.
     *
     * @var CoinMarketCapClient
     */
    private CoinMarketCapClient $client;

    /**
     * Cryptocurrency data transformer.
     *
     * @var CryptocurrencyTransformer
     */
    private CryptocurrencyTransformer $cryptoTransformer;

    /**
     * Exchange data transformer.
     *
     * @var ExchangeTransformer
     */
    private ExchangeTransformer $exchangeTransformer;

    /**
     * Global metrics transformer.
     *
     * @var GlobalMetricsTransformer
     */
    private GlobalMetricsTransformer $globalMetricsTransformer;

    /**
     * Credit manager instance.
     *
     * @var CreditManager|null
     */
    private ?CreditManager $creditManager = null;

    /**
     * Credit optimizer instance.
     *
     * @var CreditOptimizer|null
     */
    private ?CreditOptimizer $creditOptimizer = null;

    /**
     * Create a new CoinMarketCap provider instance.
     *
     * @param CoinMarketCapClient $client API client
     */
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
        $this->cryptoTransformer = new CryptocurrencyTransformer();
        $this->exchangeTransformer = new ExchangeTransformer();
        $this->globalMetricsTransformer = new GlobalMetricsTransformer();
    }

    /**
     * Set credit manager instance.
     *
     * @param CreditManager $creditManager Credit manager
     * @return void
     */
    public function setCreditManager(CreditManager $creditManager): void
    {
        $this->creditManager = $creditManager;
    }

    /**
     * Set credit optimizer instance.
     *
     * @param CreditOptimizer $creditOptimizer Credit optimizer
     * @return void
     */
    public function setCreditOptimizer(CreditOptimizer $creditOptimizer): void
    {
        $this->creditOptimizer = $creditOptimizer;
    }

    /**
     * Get provider name.
     *
     * @return string Provider name
     */
    public function getName(): string
    {
        return 'coinmarketcap';
    }

    /**
     * Get provider priority.
     *
     * @return int Provider priority (lower number = higher priority)
     */
    public function getPriority(): int
    {
        $config = $this->client->getConfig();
        return $config['provider']['priority'] ?? 2;
    }

    /**
     * Check if provider is enabled.
     *
     * @return bool True if provider is enabled
     */
    public function isEnabled(): bool
    {
        $config = $this->client->getConfig();
        return $config['provider']['enabled'] ?? true;
    }

    /**
     * Get supported cryptocurrencies.
     *
     * @return array<string> Array of supported cryptocurrency symbols
     */
    public function getSupportedCryptocurrencies(): array
    {
        $config = $this->client->getConfig();
        $supported = $config['supported_cryptocurrencies'] ?? [];
        
        // If empty, return all (no restriction)
        return $supported;
    }

    /**
     * Get supported currencies for price conversion.
     *
     * @return array<string> Array of supported currency codes
     */
    public function getSupportedCurrencies(): array
    {
        $config = $this->client->getConfig();
        return $config['supported_currencies'] ?? ['usd'];
    }

    /**
     * Get latest cryptocurrency listings.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed cryptocurrency listings
     */
    public function getCryptocurrencyListings(array $params = []): array
    {
        $endpoint = 'cryptocurrency/listings/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->cryptoTransformer->transform($rawData);
    }

    /**
     * Get cryptocurrency quotes by IDs or symbols.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed cryptocurrency quotes
     */
    public function getCryptocurrencyQuotes(array $params = []): array
    {
        $endpoint = 'cryptocurrency/quotes/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->cryptoTransformer->transform($rawData);
    }

    /**
     * Get cryptocurrency metadata/info.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed cryptocurrency info
     */
    public function getCryptocurrencyInfo(array $params = []): array
    {
        $endpoint = 'cryptocurrency/info';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->cryptoTransformer->transform($rawData);
    }

    /**
     * Get exchange listings.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed exchange listings
     */
    public function getExchangeListings(array $params = []): array
    {
        $endpoint = 'exchange/listings/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->exchangeTransformer->transform($rawData);
    }

    /**
     * Get exchange quotes by IDs.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed exchange quotes
     */
    public function getExchangeQuotes(array $params = []): array
    {
        $endpoint = 'exchange/quotes/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->exchangeTransformer->transform($rawData);
    }

    /**
     * Get exchange metadata/info.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed exchange info
     */
    public function getExchangeInfo(array $params = []): array
    {
        $endpoint = 'exchange/info';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->exchangeTransformer->transform($rawData);
    }

    /**
     * Get global market metrics.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed global metrics
     */
    public function getGlobalMetrics(array $params = []): array
    {
        $endpoint = 'global-metrics/quotes/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->globalMetricsTransformer->transform($rawData);
    }

    /**
     * Get trending cryptocurrencies.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed trending data
     */
    public function getTrendingCryptocurrencies(array $params = []): array
    {
        $endpoint = 'cryptocurrency/trending/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->cryptoTransformer->transform($rawData);
    }

    /**
     * Get OHLCV data for cryptocurrencies.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Transformed OHLCV data
     */
    public function getCryptocurrencyOHLCV(array $params = []): array
    {
        $endpoint = 'cryptocurrency/ohlcv/latest';
        $optimizedParams = $this->optimizeRequest($endpoint, $params);
        
        $rawData = $this->makeRequest($endpoint, $optimizedParams);
        return $this->cryptoTransformer->transform($rawData);
    }

    /**
     * Get credit manager instance.
     *
     * @return CreditManager|null Credit manager
     */
    public function getCreditManager(): ?CreditManager
    {
        return $this->creditManager;
    }

    /**
     * Get credit optimizer instance.
     *
     * @return CreditOptimizer|null Credit optimizer
     */
    public function getCreditOptimizer(): ?CreditOptimizer
    {
        return $this->creditOptimizer;
    }

    /**
     * Get API client instance.
     *
     * @return CoinMarketCapClient API client
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }

    /**
     * Get cryptocurrency transformer.
     *
     * @return CryptocurrencyTransformer Cryptocurrency transformer
     */
    public function getCryptocurrencyTransformer(): CryptocurrencyTransformer
    {
        return $this->cryptoTransformer;
    }

    /**
     * Get exchange transformer.
     *
     * @return ExchangeTransformer Exchange transformer
     */
    public function getExchangeTransformer(): ExchangeTransformer
    {
        return $this->exchangeTransformer;
    }

    /**
     * Get global metrics transformer.
     *
     * @return GlobalMetricsTransformer Global metrics transformer
     */
    public function getGlobalMetricsTransformer(): GlobalMetricsTransformer
    {
        return $this->globalMetricsTransformer;
    }

    /**
     * Make optimized API request with credit tracking.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed> Raw API response data
     */
    private function makeRequest(string $endpoint, array $params): array
    {
        // Track credit consumption
        if ($this->creditManager) {
            $creditCost = $this->getEndpointCreditCost($endpoint);
            
            // Check if we can make the call
            if (!$this->creditManager->canMakeCall($endpoint, $creditCost)) {
                throw new \Exception("Cannot make API call to {$endpoint}: credit or rate limits exceeded");
            }
            
            // Make the request
            $response = $this->client->get($endpoint, $params);
            
            // Track usage after successful call
            $actualCreditCost = $response['status']['credit_count'] ?? $creditCost;
            $this->creditManager->trackUsage($endpoint, $actualCreditCost);
            
            return $response;
        }

        // Make request without credit tracking
        return $this->client->get($endpoint, $params);
    }

    /**
     * Optimize request parameters using credit optimizer.
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $params Request parameters
     * @return array<string, mixed> Optimized parameters
     */
    private function optimizeRequest(string $endpoint, array $params): array
    {
        if ($this->creditOptimizer && $this->creditOptimizer->isOptimizationEnabled()) {
            return $this->creditOptimizer->optimizeRequest($endpoint, $params);
        }

        return $params;
    }

    /**
     * Get credit cost for an endpoint.
     *
     * @param string $endpoint API endpoint
     * @return int Credit cost
     */
    private function getEndpointCreditCost(string $endpoint): int
    {
        $config = $this->client->getConfig();
        $costs = $config['credits']['costs'] ?? [];
        
        // Normalize endpoint name for lookup
        $normalizedEndpoint = str_replace(['/', '-'], '_', trim($endpoint, '/'));
        
        return $costs[$normalizedEndpoint] ?? 1;
    }
}