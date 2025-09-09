<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Transformers\ExchangeTransformer;
use InvalidArgumentException;

/**
 * Exchange Service
 *
 * Handles all exchange-related endpoints from CoinMarketCap Pro API v2
 * including exchange map, info, listings, quotes, and market pairs.
 */
class ExchangeService
{
    private CoinMarketCapClient $client;
    private ExchangeTransformer $transformer;
    
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
        $this->transformer = new ExchangeTransformer();
    }
    
    /**
     * Get exchange reference data mapping
     *
     * @param int $start Results start offset (default: 1)
     * @param int $limit Number of results to return (default: 100, max: 5000)
     * @param string $sort Sort field: "id", "name", "volume_24h" (default: "id")
     * @param array $slug Filter by exchange slugs
     * @param array $aux Optionally specify a comma-separated list of supplemental data fields
     * @return array Transformed exchange map data
     */
    public function map(
        int $start = 1,
        int $limit = 100,
        string $sort = 'id',
        array $slug = [],
        array $aux = []
    ): array {
        $parameters = [
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
        ];
        
        if (!empty($slug)) {
            $parameters['slug'] = implode(',', $slug);
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/map', $parameters, 86400); // Cache for 24 hours
        
        return $this->transformer->transformMap($response);
    }
    
    /**
     * Get exchange metadata information
     *
     * @param array $id Exchange IDs
     * @param array $slug Exchange slugs
     * @param array $aux Supplemental data fields
     * @return array Transformed exchange info data
     */
    public function info(array $id = [], array $slug = [], array $aux = []): array
    {
        if (empty($id) && empty($slug)) {
            throw new InvalidArgumentException('Either id or slug parameter is required');
        }
        
        $parameters = [];
        
        if (!empty($id)) {
            $parameters['id'] = implode(',', $id);
        }
        
        if (!empty($slug)) {
            $parameters['slug'] = implode(',', $slug);
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/info', $parameters, 86400); // Cache for 24 hours
        
        return $this->transformer->transformInfo($response);
    }
    
    /**
     * Get latest exchange listings
     *
     * @param int $start Results start offset
     * @param int $limit Number of results (max: 5000)
     * @param string $sort Sort field: "name", "volume_24h", "volume_24h_adjusted", "exchange_score"
     * @param string $sortDir Sort direction: "asc", "desc"
     * @param string $marketType Market type filter: "fees", "no_fees", "all"
     * @param string $category Exchange category: "spot", "derivatives", "otc", "all"
     * @param array $aux Supplemental data fields
     * @return array Transformed exchange listings
     */
    public function listingsLatest(
        int $start = 1,
        int $limit = 100,
        string $sort = 'volume_24h',
        string $sortDir = 'desc',
        string $marketType = 'all',
        string $category = 'all',
        array $aux = []
    ): array {
        $parameters = [
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
            'sort_dir' => $sortDir,
            'market_type' => $marketType,
            'category' => $category,
        ];
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/listings/latest', $parameters, 300); // Cache for 5 minutes
        
        return $this->transformer->transformListings($response);
    }
    
    /**
     * Get historical exchange listings
     *
     * @param string $timestamp Historical timestamp (ISO 8601)
     * @param int $start Results start offset
     * @param int $limit Number of results
     * @param string $sort Sort field
     * @param string $sortDir Sort direction
     * @param string $marketType Market type filter
     * @param string $category Exchange category
     * @param array $aux Supplemental data fields
     * @return array Transformed historical exchange listings
     */
    public function listingsHistorical(
        string $timestamp,
        int $start = 1,
        int $limit = 100,
        string $sort = 'volume_24h',
        string $sortDir = 'desc',
        string $marketType = 'all',
        string $category = 'all',
        array $aux = []
    ): array {
        $parameters = [
            'timestamp' => $timestamp,
            'start' => $start,
            'limit' => $limit,
            'sort' => $sort,
            'sort_dir' => $sortDir,
            'market_type' => $marketType,
            'category' => $category,
        ];
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/listings/historical', $parameters, 3600); // Cache for 1 hour
        
        return $this->transformer->transformListings($response);
    }
    
    /**
     * Get latest exchange quotes
     *
     * @param array $id Exchange IDs
     * @param array $slug Exchange slugs
     * @param string $convert Convert to currency (default: USD)
     * @param array $aux Supplemental data fields
     * @return array Transformed exchange quotes
     */
    public function quotesLatest(
        array $id = [],
        array $slug = [],
        string $convert = 'USD',
        array $aux = []
    ): array {
        if (empty($id) && empty($slug)) {
            throw new InvalidArgumentException('Either id or slug parameter is required');
        }
        
        $parameters = [
            'convert' => $convert,
        ];
        
        if (!empty($id)) {
            $parameters['id'] = implode(',', $id);
        }
        
        if (!empty($slug)) {
            $parameters['slug'] = implode(',', $slug);
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/quotes/latest', $parameters, 60); // Cache for 1 minute
        
        return $this->transformer->transformQuotes($response);
    }
    
    /**
     * Get historical exchange quotes
     *
     * @param array $id Exchange IDs
     * @param array $slug Exchange slugs
     * @param string $timeStart Time period start (ISO 8601)
     * @param string|null $timeEnd Time period end (ISO 8601)
     * @param int $count Number of intervals to return
     * @param string $interval Time interval: "5m", "10m", "15m", "30m", "45m", "1h", "2h", "3h", "4h", "6h", "12h", "1d", "2d", "3d", "7d", "14d", "15d", "30d", "60d", "90d", "365d"
     * @param string $convert Convert to currency
     * @param array $aux Supplemental data fields
     * @return array Transformed historical exchange quotes
     */
    public function quotesHistorical(
        array $id = [],
        array $slug = [],
        string $timeStart = '',
        ?string $timeEnd = null,
        int $count = 10,
        string $interval = '1d',
        string $convert = 'USD',
        array $aux = []
    ): array {
        if (empty($id) && empty($slug)) {
            throw new InvalidArgumentException('Either id or slug parameter is required');
        }
        
        $parameters = [
            'count' => $count,
            'interval' => $interval,
            'convert' => $convert,
        ];
        
        if (!empty($id)) {
            $parameters['id'] = implode(',', $id);
        }
        
        if (!empty($slug)) {
            $parameters['slug'] = implode(',', $slug);
        }
        
        if ($timeStart) {
            $parameters['time_start'] = $timeStart;
        }
        
        if ($timeEnd) {
            $parameters['time_end'] = $timeEnd;
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/quotes/historical', $parameters, 3600); // Cache for 1 hour
        
        return $this->transformer->transformQuotes($response);
    }
    
    /**
     * Get market pairs for exchanges
     *
     * @param array $id Exchange IDs
     * @param array $slug Exchange slugs
     * @param int $start Results start offset
     * @param int $limit Number of results
     * @param string $category Market category filter
     * @param string $feeType Fee type filter
     * @param string $convert Convert to currency
     * @param array $aux Supplemental data fields
     * @return array Transformed market pairs data
     */
    public function marketPairsLatest(
        array $id = [],
        array $slug = [],
        int $start = 1,
        int $limit = 100,
        string $category = 'spot',
        string $feeType = 'all',
        string $convert = 'USD',
        array $aux = []
    ): array {
        if (empty($id) && empty($slug)) {
            throw new InvalidArgumentException('Either id or slug parameter is required');
        }
        
        $parameters = [
            'start' => $start,
            'limit' => $limit,
            'category' => $category,
            'fee_type' => $feeType,
            'convert' => $convert,
        ];
        
        if (!empty($id)) {
            $parameters['id'] = implode(',', $id);
        }
        
        if (!empty($slug)) {
            $parameters['slug'] = implode(',', $slug);
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/exchange/market-pairs/latest', $parameters, 180); // Cache for 3 minutes
        
        return $this->transformer->transformMarketPairs($response);
    }
}