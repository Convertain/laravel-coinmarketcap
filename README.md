# Laravel CoinMarketCap Pro API Package

A comprehensive Laravel package for integrating with CoinMarketCap Pro API v2, providing exchange data, global market metrics, and fiat currency support with credit optimization and intelligent caching.

## Features

- ✅ **Complete Exchange API Coverage**: Map, info, listings, quotes, and market pairs
- ✅ **Global Market Metrics**: Latest and historical data with advanced analysis
- ✅ **Fiat Currency Support**: Complete currency mapping with regional groupings
- ✅ **Credit Optimization**: Intelligent tracking and usage optimization
- ✅ **Advanced Caching**: Configurable TTL for different data types
- ✅ **Data Transformation**: Clean, structured data with derived metrics
- ✅ **Error Handling**: Comprehensive error handling and logging
- ✅ **Laravel Integration**: Full Laravel service provider integration
- ✅ **PSR-12 Compliant**: Follows PHP coding standards
- ✅ **Comprehensive Tests**: Unit and feature tests included

## Installation

```bash
composer require convertain/laravel-coinmarketcap
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=coinmarketcap-config
```

Set your environment variables:

```env
COINMARKETCAP_API_KEY=your-api-key-here
COINMARKETCAP_PLAN=basic
COINMARKETCAP_CREDITS_PER_MONTH=10000
COINMARKETCAP_CACHE_ENABLED=true
```

## Basic Usage

### Service Provider Access

```php
use Convertain\CoinMarketCap\CoinMarketCapProvider;

class CryptoController extends Controller
{
    public function __construct(
        private CoinMarketCapProvider $coinMarketCap
    ) {}
    
    public function index()
    {
        // Access different services
        $exchanges = $this->coinMarketCap->exchanges();
        $globalMetrics = $this->coinMarketCap->globalMetrics();
        $fiat = $this->coinMarketCap->fiat();
    }
}
```

## Exchange Services

### Exchange Map

Get exchange reference data:

```php
$exchanges = $coinMarketCap->exchanges();

// Get all exchanges
$exchangeMap = $exchanges->map();

// Get specific exchanges
$exchangeMap = $exchanges->map(
    start: 1,
    limit: 50,
    sort: 'volume_24h',
    slug: ['binance', 'coinbase'],
    aux: ['first_historical_data']
);
```

### Exchange Information

Get detailed exchange metadata:

```php
// By exchange IDs
$exchangeInfo = $exchanges->info(id: [270, 294]);

// By exchange slugs
$exchangeInfo = $exchanges->info(slug: ['binance', 'coinbase']);
```

### Exchange Listings

Get ranked exchange listings:

```php
// Latest listings
$listings = $exchanges->listingsLatest(
    start: 1,
    limit: 100,
    sort: 'volume_24h',
    sortDir: 'desc',
    marketType: 'all',
    category: 'spot'
);

// Historical listings
$historicalListings = $exchanges->listingsHistorical(
    timestamp: '2023-01-01T00:00:00.000Z'
);
```

### Exchange Quotes

Get exchange trading data:

```php
// Latest quotes
$quotes = $exchanges->quotesLatest(
    id: [270, 294],
    convert: 'USD'
);

// Historical quotes
$historicalQuotes = $exchanges->quotesHistorical(
    id: [270],
    timeStart: '2023-01-01T00:00:00.000Z',
    timeEnd: '2023-01-31T23:59:59.000Z',
    interval: '1d'
);
```

### Market Pairs

Get exchange market pair data:

```php
$marketPairs = $exchanges->marketPairsLatest(
    id: [270],
    category: 'spot',
    feeType: 'percentage',
    convert: 'USD'
);
```

## Global Metrics Services

### Latest Global Metrics

```php
$globalMetrics = $coinMarketCap->globalMetrics();

// Get latest metrics
$latest = $globalMetrics->quotesLatest('USD');

// Access data
$totalMarketCap = $latest['data']['quote']['USD']['total_market_cap'];
$btcDominance = $latest['data']['btc_dominance'];
$activeExchanges = $latest['data']['active_exchanges'];
```

### Historical Global Metrics

```php
$historical = $globalMetrics->quotesHistorical(
    timeStart: '2023-01-01T00:00:00.000Z',
    timeEnd: '2023-01-31T23:59:59.000Z',
    interval: '1d',
    convert: 'USD'
);
```

### Advanced Analysis Features

#### Dominance Metrics

```php
$dominance = $globalMetrics->getDominanceMetrics();

$btcDominance = $dominance['dominance']['bitcoin']['current'];
$ethDominance = $dominance['dominance']['ethereum']['current'];
$altcoinDominance = $dominance['dominance']['altcoins']['current'];
```

#### Market Cap Tiers

```php
$tiers = $globalMetrics->getMarketCapTiers();

$largeCap = $tiers['market_cap_tiers']['large_cap']['estimated_market_cap'];
$midCap = $tiers['market_cap_tiers']['mid_cap']['estimated_market_cap'];
$smallCap = $tiers['market_cap_tiers']['small_cap']['estimated_market_cap'];
```

#### Volume Analysis

```php
$volumeAnalysis = $globalMetrics->getVolumeAnalysis();

$spotVolume = $volumeAnalysis['volume_analysis']['spot_volume']['total_24h'];
$derivativesVolume = $volumeAnalysis['volume_analysis']['derivatives_volume']['total_24h'];
$defiVolume = $volumeAnalysis['volume_analysis']['defi_volume']['total_24h'];
```

#### DeFi Metrics

```php
$defiMetrics = $globalMetrics->getDeFiMetrics();

$defiMarketCap = $defiMetrics['defi_metrics']['market_cap'];
$defiVolume = $defiMetrics['defi_metrics']['volume_24h'];
$stablecoinMarketCap = $defiMetrics['stablecoin_metrics']['market_cap'];
```

#### Trend Analysis

```php
$trendAnalysis = $globalMetrics->getTrendAnalysis(days: 30);

$marketCapTrend = $trendAnalysis['trend_analysis']['market_cap_trend']; // 'upward', 'downward', 'sideways'
$volatility = $trendAnalysis['trend_analysis']['volatility'];
$momentum = $trendAnalysis['trend_analysis']['momentum'];
```

#### Sentiment Analysis

```php
$sentiment = $globalMetrics->getSentimentAnalysis();

$overallSentiment = $sentiment['sentiment']['overall']; // 'very_positive', 'positive', 'neutral', 'negative', 'very_negative'
$fearGreedIndex = $sentiment['sentiment']['fear_greed_index']; // 0-100
```

## Fiat Currency Services

### Currency Map

```php
$fiat = $coinMarketCap->fiat();

// Get all currencies
$allCurrencies = $fiat->getAllCurrencies();

// Get currencies with precious metals
$withMetals = $fiat->getAllCurrencies(includeMetals: true);

// Get paginated results
$currencyMap = $fiat->map(start: 1, limit: 100);
```

### Currency Information

```php
// Get currency by symbol
$usd = $fiat->getCurrencyBySymbol('USD');
$euro = $fiat->getCurrencyBySymbol('EUR');

// Get currency by ID
$currency = $fiat->getCurrencyById(2781);

// Check if currency is supported
$isSupported = $fiat->isCurrencySupported('GBP'); // true/false
```

### Specialized Currency Groups

#### Major Currencies

```php
$majorCurrencies = $fiat->getMajorCurrencies();
// Returns USD, EUR, JPY, GBP, AUD, CAD, CHF, CNY, SEK, NZD
```

#### Regional Currencies

```php
$regional = $fiat->getRegionalCurrencies();

$northAmerican = $regional['data']['North America']; // USD, CAD, MXN
$european = $regional['data']['Europe']; // EUR, GBP, CHF, SEK, etc.
$asiaPacific = $regional['data']['Asia Pacific']; // JPY, CNY, KRW, AUD, etc.
```

#### Precious Metals

```php
$metals = $fiat->getPreciousMetals();
// Returns XAU (Gold), XAG (Silver), XPT (Platinum), XPD (Palladium)
```

## Credit Management

### Credit Tracking

The package automatically tracks API credit usage:

```php
$client = $coinMarketCap->getClient();

$creditsUsed = $client->getCreditsUsed();
```

### Credit Optimization

Credits are optimized through:

- **Intelligent Caching**: Different TTL for different data types
- **Static Data Caching**: 24-hour cache for reference data (maps, info)
- **Dynamic Data Caching**: Short cache for real-time data (quotes)
- **Request Batching**: Multiple IDs/slugs in single requests

## Caching Configuration

Configure cache TTL for different data types:

```php
// config/coinmarketcap.php
'cache' => [
    'ttl' => [
        // Static data - cache longer
        'exchange_map' => 86400, // 24 hours
        'exchange_info' => 86400, // 24 hours
        'fiat_map' => 86400, // 24 hours
        
        // Dynamic data - moderate caching
        'exchange_listings' => 300, // 5 minutes
        'global_metrics' => 300, // 5 minutes
        
        // Real-time data - short caching
        'exchange_quotes' => 60, // 1 minute
        
        // Market data
        'market_pairs' => 180, // 3 minutes
        
        // Historical data
        'historical' => 3600, // 1 hour
    ],
],
```

## Error Handling

The package provides comprehensive error handling:

```php
try {
    $exchanges = $coinMarketCap->exchanges();
    $result = $exchanges->quotesLatest(id: [999999]); // Non-existent exchange
    
    if ($result['status']['error_code'] !== 0) {
        // Handle API error
        $errorMessage = $result['status']['error_message'];
        Log::warning("CoinMarketCap API Error: {$errorMessage}");
    }
} catch (\Exception $e) {
    // Handle connection or other errors
    Log::error("CoinMarketCap Service Error: " . $e->getMessage());
}
```

## Data Transformation

All responses are transformed into clean, consistent structures:

```php
// Raw API response is transformed to:
[
    'status' => [
        'error_code' => 0,
        'error_message' => null
    ],
    'data' => [
        // Clean, structured data with null coalescing for missing fields
        'id' => 270,
        'name' => 'Binance',
        'slug' => 'binance',
        'quote' => [
            'USD' => [
                'volume_24h' => 1000000000,
                'volume_24h_adjusted' => 950000000,
                'last_updated' => '2023-01-01T00:00:00.000Z'
            ]
        ]
    ]
]
```

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suites
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Feature/

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## Configuration Options

### API Configuration

```php
'api' => [
    'key' => env('COINMARKETCAP_API_KEY'),
    'base_url' => env('COINMARKETCAP_BASE_URL', 'https://pro-api.coinmarketcap.com/v2'),
    'timeout' => env('COINMARKETCAP_TIMEOUT', 30),
    'retry_times' => env('COINMARKETCAP_RETRY_TIMES', 3),
    'retry_delay' => env('COINMARKETCAP_RETRY_DELAY', 1000),
],
```

### Plan Configuration

```php
'plan' => [
    'type' => env('COINMARKETCAP_PLAN', 'basic'),
    'credits_per_month' => env('COINMARKETCAP_CREDITS_PER_MONTH', 10000),
    'calls_per_minute' => env('COINMARKETCAP_CALLS_PER_MINUTE', 30),
    'calls_per_day' => env('COINMARKETCAP_CALLS_PER_DAY', 333),
],
```

### Logging Configuration

```php
'logging' => [
    'enabled' => env('COINMARKETCAP_LOGGING_ENABLED', true),
    'channel' => env('COINMARKETCAP_LOG_CHANNEL', 'stack'),
    'level' => env('COINMARKETCAP_LOG_LEVEL', 'info'),
    'log_requests' => env('COINMARKETCAP_LOG_REQUESTS', false),
    'log_responses' => env('COINMARKETCAP_LOG_RESPONSES', false),
    'log_credits' => env('COINMARKETCAP_LOG_CREDITS', true),
],
```

## Requirements

- PHP 8.3+
- Laravel 12.0+
- CoinMarketCap Pro API Key
- GuzzleHTTP 7.0+

## License

This package is proprietary software. Please see the license file for more information.

## Support

For support, please contact [christian@convertain.com](mailto:christian@convertain.com).

## Credits

- **Christian Rauchenwald** - [christian@convertain.com](mailto:christian@convertain.com)
- **Convertain** - [https://convertain.com](https://convertain.com)
