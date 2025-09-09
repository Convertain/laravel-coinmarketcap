# API Reference

Complete reference documentation for the Laravel CoinMarketCap package API methods, including credit costs, parameters, and return values.

## Table of Contents

- [Client Configuration](#client-configuration)
- [CoinMarketCapProvider Methods](#coinmarketcapprovider-methods)
- [Cryptocurrency Endpoints](#cryptocurrency-endpoints)
- [Exchange Endpoints](#exchange-endpoints)
- [Global Metrics](#global-metrics)
- [Utility Methods](#utility-methods)
- [Credit Information](#credit-information)
- [Error Handling](#error-handling)

## Client Configuration

### CoinMarketCapClient

The API client handles all HTTP communication with the CoinMarketCap Pro API.

```php
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

$client = app(CoinMarketCapClient::class);
```

**Configuration:**
- **Base URL**: `https://pro-api.coinmarketcap.com/v2`
- **Authentication**: API key via `X-CMC_PRO_API_KEY` header
- **Timeout**: Configurable (default: 30 seconds)
- **Retry Logic**: Automatic retry on failures (configurable)

## CoinMarketCapProvider Methods

### getCryptocurrency(string $symbol, array $options = [])

Get information for a single cryptocurrency.

**Parameters:**
- `$symbol` (string): Cryptocurrency symbol (e.g., 'BTC', 'ETH')
- `$options` (array): Optional parameters

**Options:**
```php
[
    'convert' => 'USD',           // Target currency for price conversion
    'convert_id' => null,         // Target cryptocurrency ID for conversion
    'aux' => 'urls,logo,tags',    // Auxiliary data fields
    'skip_invalid' => true        // Skip invalid symbols
]
```

**Returns:** `CryptocurrencyData` object

**Credit Cost:** 1 credit per call

**Cache TTL:** 60 seconds (configurable via `cryptocurrency_quotes`)

**Example:**
```php
$bitcoin = $provider->getCryptocurrency('BTC', [
    'convert' => 'USD,EUR',
    'aux' => 'urls,logo,description'
]);

echo $bitcoin->getName();           // "Bitcoin"
echo $bitcoin->getSymbol();         // "BTC"
echo $bitcoin->getCurrentPrice('USD'); // Current price in USD
```

---

### getCryptocurrencies(array $symbols, array $options = [])

Get information for multiple cryptocurrencies in a single request.

**Parameters:**
- `$symbols` (array): Array of cryptocurrency symbols
- `$options` (array): Optional parameters

**Limits:**
- Maximum 100 symbols per request
- Automatically batches larger requests

**Credit Cost:** 1 credit per request (regardless of number of symbols up to 100)

**Cache TTL:** 60 seconds

**Example:**
```php
$cryptos = $provider->getCryptocurrencies(['BTC', 'ETH', 'USDT'], [
    'convert' => 'USD'
]);

foreach ($cryptos as $crypto) {
    echo "{$crypto->getSymbol()}: ${$crypto->getCurrentPrice('USD')}\n";
}
```

---

### getCryptocurrencyListings(array $options = [])

Get a paginated list of all active cryptocurrencies with market data.

**Parameters:**
- `$options` (array): Filtering and sorting options

**Options:**
```php
[
    'start' => 1,                    // Starting rank
    'limit' => 100,                  // Number of results (max 5000)
    'price_min' => 0.01,             // Minimum price filter
    'price_max' => 100000,           // Maximum price filter
    'market_cap_min' => 1000000,     // Minimum market cap
    'market_cap_max' => null,        // Maximum market cap
    'volume_24h_min' => 10000,       // Minimum 24h volume
    'percent_change_24h_min' => null, // Minimum 24h change %
    'convert' => 'USD',              // Price conversion currency
    'sort' => 'market_cap',          // Sort field
    'sort_dir' => 'desc',            // Sort direction
    'cryptocurrency_type' => 'all',   // Filter by type
    'tag' => 'all',                  // Filter by tag
    'aux' => 'num_market_pairs,cmc_rank'
]
```

**Sort Options:**
- `market_cap` (default)
- `name`
- `symbol`
- `date_added`
- `price`
- `circulating_supply`
- `total_supply`
- `max_supply`
- `num_market_pairs`
- `volume_24h`
- `percent_change_1h`
- `percent_change_24h`
- `percent_change_7d`

**Credit Cost:** 1 credit per 200 cryptocurrencies returned

**Cache TTL:** 300 seconds (5 minutes)

**Example:**
```php
// Get top 50 cryptocurrencies by market cap
$listings = $provider->getCryptocurrencyListings([
    'limit' => 50,
    'convert' => 'USD',
    'sort' => 'market_cap',
    'aux' => 'num_market_pairs,cmc_rank,max_supply'
]);

foreach ($listings as $crypto) {
    echo "#{$crypto->getRank()} {$crypto->getName()} ({$crypto->getSymbol()}): ";
    echo "${$crypto->getCurrentPrice('USD')}\n";
}
```

---

### getCryptocurrencyInfo(array $symbols, array $options = [])

Get static metadata information for cryptocurrencies.

**Parameters:**
- `$symbols` (array): Cryptocurrency symbols
- `$options` (array): Optional parameters

**Options:**
```php
[
    'aux' => 'urls,logo,description,tags,platform,date_added'
]
```

**Credit Cost:** 1 credit per 100 cryptocurrencies

**Cache TTL:** 86400 seconds (24 hours) - Static data

**Example:**
```php
$info = $provider->getCryptocurrencyInfo(['BTC', 'ETH']);

foreach ($info as $crypto) {
    echo "{$crypto->getName()}: {$crypto->getDescription()}\n";
    echo "Website: {$crypto->getWebsiteUrl()}\n";
    echo "Logo: {$crypto->getLogoUrl()}\n";
}
```

---

### getCryptocurrencyMap(array $options = [])

Get a mapping of all cryptocurrencies to their unique CoinMarketCap IDs.

**Options:**
```php
[
    'listing_status' => 'active',    // active, inactive, untracked
    'start' => 1,
    'limit' => 5000,
    'sort' => 'id',                  // id, cmc_rank
    'symbol' => null,                // Filter by specific symbols
    'aux' => 'platform,first_historical_data,last_historical_data'
]
```

**Credit Cost:** 1 credit per call

**Cache TTL:** 86400 seconds (24 hours)

**Example:**
```php
$mapping = $provider->getCryptocurrencyMap([
    'limit' => 100,
    'listing_status' => 'active'
]);

foreach ($mapping as $crypto) {
    echo "ID: {$crypto->getId()}, Symbol: {$crypto->getSymbol()}\n";
}
```

---

### getHistoricalQuotes(array $symbols, \DateTime $timeStart, \DateTime $timeEnd = null, array $options = [])

Get historical price data for cryptocurrencies.

**Parameters:**
- `$symbols` (array): Cryptocurrency symbols
- `$timeStart` (DateTime): Start date for historical data
- `$timeEnd` (DateTime|null): End date (optional, defaults to now)
- `$options` (array): Additional options

**Options:**
```php
[
    'convert' => 'USD',
    'interval' => 'daily',           // hourly, daily, weekly, monthly, yearly
    'count' => 10,                   // Number of data points
    'aux' => 'price,volume,market_cap,circulating_supply'
]
```

**Credit Cost:** 1 credit per 100 historical data points returned

**Cache TTL:** 3600 seconds (1 hour)

**Example:**
```php
$startDate = new \DateTime('-30 days');
$historicalData = $provider->getHistoricalQuotes(['BTC'], $startDate, null, [
    'convert' => 'USD',
    'interval' => 'daily'
]);

foreach ($historicalData as $dataPoint) {
    echo "{$dataPoint->getTimestamp()}: ${$dataPoint->getPrice('USD')}\n";
}
```

---

### getOHLCVLatest(array $symbols, array $options = [])

Get the latest OHLCV (Open, High, Low, Close, Volume) data for cryptocurrencies.

**Options:**
```php
[
    'convert' => 'USD',
    'skip_invalid' => true
]
```

**Credit Cost:** 1 credit per 100 cryptocurrencies

**Cache TTL:** 300 seconds (5 minutes)

**Example:**
```php
$ohlcv = $provider->getOHLCVLatest(['BTC', 'ETH'], [
    'convert' => 'USD'
]);

foreach ($ohlcv as $data) {
    echo "{$data->getSymbol()}: O:{$data->getOpen()} H:{$data->getHigh()} L:{$data->getLow()} C:{$data->getClose()}\n";
}
```

---

## Exchange Endpoints

### getExchanges(array $options = [])

Get a list of active cryptocurrency exchanges.

**Options:**
```php
[
    'start' => 1,
    'limit' => 100,
    'sort' => 'volume_24h',          // name, volume_24h, exchange_score
    'sort_dir' => 'desc',
    'market_type' => 'all',          // all, fees, no_fees
    'category' => 'all',             // all, spot, derivatives
    'aux' => 'num_market_pairs,traffic_score'
]
```

**Credit Cost:** 1 credit per 100 exchanges

**Cache TTL:** 300 seconds

**Example:**
```php
$exchanges = $provider->getExchanges([
    'limit' => 20,
    'sort' => 'volume_24h'
]);

foreach ($exchanges as $exchange) {
    echo "{$exchange->getName()}: Volume: ${$exchange->getVolume24h('USD')}\n";
}
```

---

### getExchangeInfo(array $exchangeIds, array $options = [])

Get detailed information about specific exchanges.

**Credit Cost:** 1 credit per 100 exchanges

**Cache TTL:** 86400 seconds (24 hours)

**Example:**
```php
$exchangeInfo = $provider->getExchangeInfo([270], [ // Binance ID
    'aux' => 'urls,logo,description'
]);
```

---

### getExchangeQuotes(array $exchangeIds, array $options = [])

Get market quotes for specific exchanges.

**Credit Cost:** 1 credit per 100 exchanges

**Cache TTL:** 60 seconds

---

### getExchangeMarketPairs(int $exchangeId, array $options = [])

Get market pairs available on a specific exchange.

**Options:**
```php
[
    'start' => 1,
    'limit' => 100,
    'convert' => 'USD',
    'aux' => 'num_market_pairs,category'
]
```

**Credit Cost:** 1 credit per call

**Cache TTL:** 180 seconds

---

## Global Metrics

### getGlobalMetrics(array $options = [])

Get global cryptocurrency market metrics.

**Options:**
```php
[
    'convert' => 'USD'
]
```

**Credit Cost:** 1 credit per call

**Cache TTL:** 300 seconds

**Example:**
```php
$metrics = $provider->getGlobalMetrics(['convert' => 'USD']);

echo "Total Market Cap: ${$metrics->getTotalMarketCap('USD')}\n";
echo "Total Volume 24h: ${$metrics->getTotalVolume24h('USD')}\n";
echo "Bitcoin Dominance: {$metrics->getBitcoinDominance()}%\n";
echo "Active Cryptocurrencies: {$metrics->getActiveCryptocurrencies()}\n";
```

---

## Utility Methods

### getSupportedFiatCurrencies()

Get list of supported fiat currencies for conversions.

**Credit Cost:** 1 credit per call

**Cache TTL:** 86400 seconds (24 hours)

**Example:**
```php
$fiats = $provider->getSupportedFiatCurrencies();

foreach ($fiats as $fiat) {
    echo "{$fiat->getSymbol()}: {$fiat->getName()}\n";
}
```

---

### getCreditUsage()

Get current API credit usage information.

**Credit Cost:** Free (no credits consumed)

**Returns:** Array with credit usage information

**Example:**
```php
$creditInfo = $provider->getCreditUsage();

echo "Credits Used: {$creditInfo['credits_used']}\n";
echo "Credits Remaining: {$creditInfo['credits_remaining']}\n";
echo "Reset Date: {$creditInfo['reset_date']}\n";
```

---

## Credit Information

### Credit Costs Summary

| Endpoint | Credit Cost | Notes |
|----------|-------------|-------|
| Single cryptocurrency quote | 1 | Per request |
| Batch cryptocurrency quotes (up to 100) | 1 | Per request |
| Cryptocurrency listings | 1 per 200 results | Rounded up |
| Cryptocurrency info | 1 per 100 results | Static data |
| Exchange listings | 1 per 100 results | Market data |
| Global metrics | 1 | Per request |
| Historical data | 1 per 100 data points | Time series |
| OHLCV data | 1 per 100 results | Market data |

### Optimization Tips

1. **Batch Requests**: Always use batch endpoints when fetching multiple items
2. **Cache Static Data**: Info endpoints can be cached for 24+ hours
3. **Strategic Filtering**: Use filters to reduce result sets
4. **Monitor Usage**: Implement credit tracking and alerts

---

## Error Handling

### Exception Types

```php
use Convertain\CoinMarketCap\Exceptions\CoinMarketCapException;
use Convertain\CoinMarketCap\Exceptions\ApiException;
use Convertain\CoinMarketCap\Exceptions\AuthenticationException;
use Convertain\CoinMarketCap\Exceptions\RateLimitException;
use Convertain\CoinMarketCap\Exceptions\CreditLimitException;

try {
    $bitcoin = $provider->getCryptocurrency('BTC');
} catch (AuthenticationException $e) {
    // Invalid API key
    Log::error('CoinMarketCap authentication failed: ' . $e->getMessage());
} catch (RateLimitException $e) {
    // Rate limit exceeded
    Log::warning('Rate limit hit, waiting...');
    sleep($e->getRetryAfter());
} catch (CreditLimitException $e) {
    // Credit limit exceeded
    Log::error('Credit limit exceeded: ' . $e->getMessage());
} catch (ApiException $e) {
    // General API error
    Log::error('API error: ' . $e->getMessage());
} catch (CoinMarketCapException $e) {
    // Package-specific error
    Log::error('Package error: ' . $e->getMessage());
}
```

### HTTP Status Codes

| Code | Description | Exception Type |
|------|-------------|---------------|
| 200 | Success | None |
| 400 | Bad Request | ApiException |
| 401 | Unauthorized | AuthenticationException |
| 402 | Payment Required | CreditLimitException |
| 403 | Forbidden | AuthenticationException |
| 429 | Too Many Requests | RateLimitException |
| 500 | Internal Server Error | ApiException |

### Error Response Format

```json
{
    "status": {
        "timestamp": "2024-01-01T00:00:00.000Z",
        "error_code": 1001,
        "error_message": "API key missing.",
        "elapsed": 0,
        "credit_count": 0
    }
}
```

---

## Rate Limits

### Plan-Based Limits

| Plan | Calls/Minute | Calls/Day | Monthly Credits |
|------|--------------|-----------|-----------------|
| Basic | 30 | 333 | 10,000 |
| Hobbyist | 30 | 1,333 | 40,000 |
| Startup | 60 | 4,000 | 120,000 |
| Standard | 60 | 16,667 | 500,000 |
| Professional | 60 | 66,667 | 2,000,000 |
| Enterprise | 120 | 3,333,333 | 100,000,000 |

### Rate Limit Headers

The package automatically handles rate limiting using response headers:
- `X-CMC-Pro-Api-Plan`: Your current plan
- `X-CMC-Pro-Api-Plan-Credit-Limit-Monthly`: Monthly credit limit
- `X-CMC-Pro-Api-Plan-Credit-Limit-Monthly-Used`: Credits used this month
- `X-CMC-Pro-Api-Plan-Request-Limit-Minute`: Per-minute request limit

---

For more detailed examples and use cases, see the [Examples Guide](EXAMPLES.md).