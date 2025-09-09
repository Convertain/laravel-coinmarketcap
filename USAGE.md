# CoinMarketCap Cryptocurrency Service Usage

This document provides examples of how to use the CryptocurrencyService to interact with CoinMarketCap Pro API v2 endpoints.

## Service Overview

The `CryptocurrencyService` provides access to all major CoinMarketCap cryptocurrency endpoints with built-in:
- Credit optimization through intelligent batching
- Response caching for improved performance
- Parameter validation
- Data transformation for consistent output
- Error handling with proper exceptions

## Basic Usage

### Laravel Integration

```php
use Convertain\CoinMarketCap\Contracts\CryptocurrencyServiceInterface;

// Inject the service via dependency injection
public function __construct(CryptocurrencyServiceInterface $cryptocurrencyService)
{
    $this->cryptocurrencyService = $cryptocurrencyService;
}

// Or resolve from the container
$service = app(CryptocurrencyServiceInterface::class);
```

### Standalone Usage

```php
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;

$config = [
    'api' => [
        'key' => 'your-api-key',
        'base_url' => 'https://pro-api.coinmarketcap.com/v2',
        'timeout' => 30,
    ],
    // ... other config options
];

$client = new CoinMarketCapClient($config);
$transformer = new CryptocurrencyTransformer();
$service = new CryptocurrencyService($client, $transformer, $config);
```

## Endpoint Examples

### 1. Cryptocurrency Map

Get basic mapping information for all cryptocurrencies.

```php
// Get all cryptocurrencies
$result = $service->getMap();

// Get first 100 active cryptocurrencies
$result = $service->getMap([
    'listing_status' => 'active',
    'limit' => 100
]);

// Get specific symbols
$result = $service->getMap([
    'symbol' => 'BTC,ETH,ADA'
]);
```

**Credit Cost:** 1 per call

### 2. Cryptocurrency Info

Get detailed information about cryptocurrencies.

```php
// Get info by ID
$result = $service->getInfo(['id' => '1,1027,2010']);

// Get info by symbol
$result = $service->getInfo(['symbol' => 'BTC,ETH,ADA']);

// Get info by slug
$result = $service->getInfo(['slug' => 'bitcoin,ethereum,cardano']);

// Get additional data
$result = $service->getInfo([
    'symbol' => 'BTC',
    'aux' => 'urls,logo,description,tags,platform,date_added,notice'
]);
```

**Credit Cost:** 1 per cryptocurrency

### 3. Latest Listings

Get latest market data for cryptocurrencies.

```php
// Get top 100 cryptocurrencies
$result = $service->getListingsLatest(['limit' => 100]);

// Get cryptocurrencies ranked 101-200
$result = $service->getListingsLatest([
    'start' => 101,
    'limit' => 100
]);

// Filter by market cap
$result = $service->getListingsLatest([
    'market_cap_min' => 1000000000,  // $1B minimum
    'limit' => 50
]);

// Sort by volume
$result = $service->getListingsLatest([
    'sort' => 'volume_24h',
    'sort_dir' => 'desc',
    'limit' => 20
]);
```

**Credit Cost:** 1 per 100 results

### 4. Historical Listings

Get historical market data snapshots.

```php
// Get listings for a specific date
$result = $service->getListingsHistorical([
    'timestamp' => '2024-01-01T00:00:00Z',
    'limit' => 100
]);
```

**Credit Cost:** 1 per 100 results

### 5. Latest Quotes

Get latest price and market data.

```php
// Get quotes by symbol (supports batch optimization)
$result = $service->getQuotesLatest(['symbol' => 'BTC,ETH,ADA']);

// Get quotes by ID
$result = $service->getQuotesLatest(['id' => '1,1027,2010']);

// Convert to different currency
$result = $service->getQuotesLatest([
    'symbol' => 'BTC',
    'convert' => 'EUR'
]);

// Get additional data
$result = $service->getQuotesLatest([
    'symbol' => 'BTC',
    'aux' => 'num_market_pairs,cmc_rank,date_added,tags,platform,max_supply,circulating_supply,total_supply'
]);
```

**Credit Cost:** 1 per cryptocurrency (batch optimized)

### 6. Historical Quotes

Get historical price data for specific dates or time ranges.

```php
// Get quotes for a specific date
$result = $service->getQuotesHistorical([
    'symbol' => 'BTC',
    'time_start' => '2024-01-01T00:00:00Z',
    'time_end' => '2024-01-02T00:00:00Z'
]);

// Get daily quotes for the last 30 days
$result = $service->getQuotesHistorical([
    'id' => '1',
    'count' => 30,
    'interval' => 'daily'
]);
```

**Credit Cost:** 1 per time period

### 7. Market Pairs

Get market pairs data for cryptocurrencies.

```php
// Get all market pairs for Bitcoin
$result = $service->getMarketPairsLatest(['symbol' => 'BTC']);

// Get market pairs with specific quote currency
$result = $service->getMarketPairsLatest([
    'symbol' => 'BTC',
    'matched_symbol' => 'USD'
]);

// Limit results
$result = $service->getMarketPairsLatest([
    'symbol' => 'BTC',
    'limit' => 50
]);
```

**Credit Cost:** 1 per cryptocurrency

### 8. OHLCV Data

Get Open, High, Low, Close, Volume data.

```php
// Get latest OHLCV
$result = $service->getOhlcvLatest(['symbol' => 'BTC']);

// Get historical OHLCV
$result = $service->getOhlcvHistorical([
    'symbol' => 'BTC',
    'time_start' => '2024-01-01T00:00:00Z',
    'interval' => 'daily',
    'count' => 30
]);
```

**Credit Cost:** 1 per cryptocurrency per time period

### 9. Trending Data

Get trending cryptocurrencies data.

```php
// Get latest trending cryptocurrencies
$result = $service->getTrendingLatest();

// Get most visited cryptocurrencies
$result = $service->getTrendingMostVisited();

// Get top gainers and losers
$result = $service->getTrendingGainersLosers([
    'time_period' => '24h',
    'limit' => 20
]);
```

**Credit Cost:** 1 per call

### 10. Categories and Airdrops

Get category and airdrop data (plan dependent).

```php
// Get category data
$result = $service->getCategory(['id' => 'defi']);

// Get airdrop data
$result = $service->getAirdrop(['status' => 'ongoing']);
```

## Error Handling

The service provides comprehensive error handling:

```php
try {
    $result = $service->getQuotesLatest(['symbol' => 'BTC']);
} catch (\InvalidArgumentException $e) {
    // Parameter validation errors
    echo "Invalid parameters: " . $e->getMessage();
} catch (\RuntimeException $e) {
    // API errors (network, authentication, etc.)
    echo "API error: " . $e->getMessage();
}
```

## Credit Optimization

The service automatically optimizes credit usage:

1. **Batch Processing**: Multiple symbols in one request when possible
2. **Intelligent Caching**: Responses cached based on data volatility
3. **Parameter Validation**: Prevents invalid requests that waste credits

## Response Format

All methods return a consistent array structure:

```php
[
    'status' => [
        'error_code' => 0,
        'error_message' => null,
        // ... other status fields
    ],
    'data' => [
        // Endpoint-specific data
    ],
    'metadata' => [
        'transformed_at' => '2024-01-01T00:00:00+00:00',
        'total_count' => 100, // When applicable
        'batch_optimized' => true, // When batch optimization was used
        // ... other metadata
    ]
]
```

## Configuration

Key configuration options that affect service behavior:

```php
'credits' => [
    'optimization_enabled' => true, // Enable batch optimization
    'tracking_enabled' => true,     // Enable credit usage tracking
],
'cache' => [
    'enabled' => true,              // Enable response caching
    'ttl' => [
        'cryptocurrency_quotes' => 60,    // Cache quotes for 1 minute
        'cryptocurrency_listings' => 300, // Cache listings for 5 minutes
        'cryptocurrency_map' => 86400,    // Cache map for 24 hours
    ]
],
'endpoints' => [
    'limits' => [
        'symbols_per_request' => 100,     // Max symbols per batch request
    ]
]
```