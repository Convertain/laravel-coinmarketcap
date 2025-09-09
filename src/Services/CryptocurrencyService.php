<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Contracts\CryptocurrencyServiceInterface;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;

/**
 * CoinMarketCap cryptocurrency service implementation with credit optimization.
 */
class CryptocurrencyService implements CryptocurrencyServiceInterface
{
    public function __construct(
        private CoinMarketCapClient $client,
        private CryptocurrencyTransformer $transformer,
        private array $config = []
    ) {
        $this->config = $config ?: config('coinmarketcap', []);
    }

    /**
     * Get cryptocurrency map data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getMap(array $parameters = []): array
    {
        $parameters = $this->validateMapParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['map'] ?? '/cryptocurrency/map';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get cryptocurrency info.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getInfo(array $parameters = []): array
    {
        $parameters = $this->validateInfoParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['info'] ?? '/cryptocurrency/info';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get latest cryptocurrency listings.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getListingsLatest(array $parameters = []): array
    {
        $parameters = $this->validateListingsParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['listings_latest'] ?? '/cryptocurrency/listings/latest';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get historical cryptocurrency listings.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getListingsHistorical(array $parameters = []): array
    {
        $parameters = $this->validateListingsHistoricalParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['listings_historical'] ?? '/cryptocurrency/listings/historical';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get latest cryptocurrency quotes.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getQuotesLatest(array $parameters = []): array
    {
        $parameters = $this->validateQuotesParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['quotes_latest'] ?? '/cryptocurrency/quotes/latest';
        
        // Optimize batch requests for credit efficiency
        if ($this->shouldOptimizeBatch($parameters)) {
            return $this->getBatchQuotes($endpoint, $parameters);
        }
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get historical cryptocurrency quotes.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getQuotesHistorical(array $parameters = []): array
    {
        $parameters = $this->validateQuotesHistoricalParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['quotes_historical'] ?? '/cryptocurrency/quotes/historical';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get latest market pairs data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getMarketPairsLatest(array $parameters = []): array
    {
        $parameters = $this->validateMarketPairsParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['market_pairs_latest'] ?? '/cryptocurrency/market-pairs/latest';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get latest OHLCV data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getOhlcvLatest(array $parameters = []): array
    {
        $parameters = $this->validateOhlcvParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['ohlcv_latest'] ?? '/cryptocurrency/ohlcv/latest';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get historical OHLCV data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getOhlcvHistorical(array $parameters = []): array
    {
        $parameters = $this->validateOhlcvHistoricalParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['ohlcv_historical'] ?? '/cryptocurrency/ohlcv/historical';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get latest trending cryptocurrencies.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingLatest(array $parameters = []): array
    {
        $parameters = $this->validateTrendingParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['trending_latest'] ?? '/cryptocurrency/trending/latest';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get most visited cryptocurrencies.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingMostVisited(array $parameters = []): array
    {
        $parameters = $this->validateTrendingParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['trending_most_visited'] ?? '/cryptocurrency/trending/most-visited';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get gainers and losers.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingGainersLosers(array $parameters = []): array
    {
        $parameters = $this->validateTrendingGainersLosersParameters($parameters);
        $endpoint = $this->config['endpoints']['cryptocurrency']['trending_gainers_losers'] ?? '/cryptocurrency/trending/gainers-losers';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get cryptocurrency category data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getCategory(array $parameters = []): array
    {
        $endpoint = '/cryptocurrency/category';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Get cryptocurrency airdrop data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getAirdrop(array $parameters = []): array
    {
        $endpoint = '/cryptocurrency/airdrop';
        
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }

    /**
     * Validate map parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateMapParameters(array $parameters): array
    {
        $validParameters = ['listing_status', 'start', 'limit', 'sort', 'symbol', 'aux'];
        return $this->filterParameters($parameters, $validParameters);
    }

    /**
     * Validate info parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateInfoParameters(array $parameters): array
    {
        $validParameters = ['id', 'slug', 'symbol', 'address', 'aux'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['slug']) && empty($filtered['symbol']) && empty($filtered['address'])) {
            throw new \InvalidArgumentException('At least one of id, slug, symbol, or address is required');
        }

        return $filtered;
    }

    /**
     * Validate listings parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateListingsParameters(array $parameters): array
    {
        $validParameters = ['start', 'limit', 'price_min', 'price_max', 'market_cap_min', 'market_cap_max', 'volume_24h_min', 'volume_24h_max', 'circulating_supply_min', 'circulating_supply_max', 'percent_change_24h_min', 'percent_change_24h_max', 'convert', 'convert_id', 'sort', 'sort_dir', 'cryptocurrency_type', 'tag', 'aux'];
        return $this->filterParameters($parameters, $validParameters);
    }

    /**
     * Validate listings historical parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateListingsHistoricalParameters(array $parameters): array
    {
        $validParameters = ['timestamp', 'start', 'limit', 'convert', 'convert_id', 'sort', 'sort_dir', 'cryptocurrency_type', 'aux'];
        return $this->filterParameters($parameters, $validParameters);
    }

    /**
     * Validate quotes parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateQuotesParameters(array $parameters): array
    {
        $validParameters = ['id', 'slug', 'symbol', 'convert', 'convert_id', 'aux'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['slug']) && empty($filtered['symbol'])) {
            throw new \InvalidArgumentException('At least one of id, slug, or symbol is required');
        }

        return $filtered;
    }

    /**
     * Validate quotes historical parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateQuotesHistoricalParameters(array $parameters): array
    {
        $validParameters = ['id', 'symbol', 'time_start', 'time_end', 'count', 'interval', 'convert', 'convert_id', 'aux'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['symbol'])) {
            throw new \InvalidArgumentException('Either id or symbol is required');
        }

        return $filtered;
    }

    /**
     * Validate market pairs parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateMarketPairsParameters(array $parameters): array
    {
        $validParameters = ['id', 'slug', 'symbol', 'start', 'limit', 'matched_id', 'matched_symbol', 'category', 'fee_type', 'convert', 'convert_id', 'aux'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['slug']) && empty($filtered['symbol'])) {
            throw new \InvalidArgumentException('At least one of id, slug, or symbol is required');
        }

        return $filtered;
    }

    /**
     * Validate OHLCV parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateOhlcvParameters(array $parameters): array
    {
        $validParameters = ['id', 'symbol', 'convert', 'convert_id', 'skip_invalid'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['symbol'])) {
            throw new \InvalidArgumentException('Either id or symbol is required');
        }

        return $filtered;
    }

    /**
     * Validate OHLCV historical parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateOhlcvHistoricalParameters(array $parameters): array
    {
        $validParameters = ['id', 'symbol', 'time_start', 'time_end', 'count', 'interval', 'convert', 'convert_id', 'skip_invalid'];
        $filtered = $this->filterParameters($parameters, $validParameters);
        
        if (empty($filtered['id']) && empty($filtered['symbol'])) {
            throw new \InvalidArgumentException('Either id or symbol is required');
        }

        return $filtered;
    }

    /**
     * Validate trending parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateTrendingParameters(array $parameters): array
    {
        $validParameters = ['start', 'limit', 'time_period', 'convert', 'convert_id'];
        return $this->filterParameters($parameters, $validParameters);
    }

    /**
     * Validate trending gainers/losers parameters.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validateTrendingGainersLosersParameters(array $parameters): array
    {
        $validParameters = ['start', 'limit', 'time_period', 'convert', 'convert_id', 'sort', 'sort_dir'];
        return $this->filterParameters($parameters, $validParameters);
    }

    /**
     * Filter parameters to only include valid ones.
     *
     * @param array<string, mixed> $parameters
     * @param array<string> $validParameters
     * @return array<string, mixed>
     */
    private function filterParameters(array $parameters, array $validParameters): array
    {
        return array_intersect_key($parameters, array_flip($validParameters));
    }

    /**
     * Check if batch optimization should be used.
     *
     * @param array<string, mixed> $parameters
     */
    private function shouldOptimizeBatch(array $parameters): bool
    {
        if (!($this->config['credits']['optimization_enabled'] ?? true)) {
            return false;
        }

        // Check if multiple symbols/IDs are requested
        if (isset($parameters['symbol'])) {
            return count(explode(',', $parameters['symbol'])) > 1;
        }
        
        if (isset($parameters['id'])) {
            return count(explode(',', $parameters['id'])) > 1;
        }

        return false;
    }

    /**
     * Get batch quotes optimized for credit efficiency.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function getBatchQuotes(string $endpoint, array $parameters): array
    {
        $batchSize = $this->config['endpoints']['limits']['symbols_per_request'] ?? 100;
        
        if (isset($parameters['symbol'])) {
            $symbols = explode(',', $parameters['symbol']);
            $batches = array_chunk($symbols, $batchSize);
            
            $results = [];
            foreach ($batches as $batch) {
                $batchParams = $parameters;
                $batchParams['symbol'] = implode(',', $batch);
                
                $data = $this->client->get($endpoint, $batchParams);
                $transformed = $this->transformer->transform($data, $endpoint);
                
                if (isset($transformed['data'])) {
                    $results = array_merge($results, $transformed['data']);
                }
            }
            
            return [
                'status' => ['error_code' => 0, 'error_message' => null],
                'data' => $results,
                'metadata' => [
                    'batch_optimized' => true,
                    'batch_count' => count($batches),
                    'transformed_at' => date("c"),
                ],
            ];
        }

        // Fallback to single request
        $data = $this->client->get($endpoint, $parameters);
        return $this->transformer->transform($data, $endpoint);
    }
}