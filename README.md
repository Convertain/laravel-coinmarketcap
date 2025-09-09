# Laravel CoinMarketCap Pro API Package

A comprehensive Laravel package for integrating with CoinMarketCap Pro API featuring advanced data transformers and intelligent credit management system.

## Features

### 🔄 Data Transformers
- **CryptocurrencyTransformer**: Normalizes cryptocurrency API responses with comprehensive field extraction
- **ExchangeTransformer**: Standardizes exchange data format with proper URL handling and metrics normalization  
- **GlobalMetricsTransformer**: Transforms global market metrics with multi-currency support

### 💳 Credit Management System
- **Real-time Credit Tracking**: Monitor API credit consumption with detailed analytics
- **Intelligent Optimization**: Automatic request parameter optimization to minimize credit usage
- **Plan-based Limits**: Enforce rate limits and credit quotas based on subscription plan
- **Cost-benefit Analysis**: Smart decision making for API calls vs cached data

### 🏗️ Architecture
- PSR-12 compliant code structure
- Comprehensive PHPDoc documentation
- Null-safe data handling with proper type casting
- ISO 8601 datetime normalization
- Dependency injection with Laravel service container

## Quick Start

### Installation

```bash
composer require convertain/laravel-coinmarketcap
```

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=coinmarketcap-config
```

Add your API credentials to `.env`:

```env
COINMARKETCAP_API_KEY=your_api_key_here
COINMARKETCAP_PLAN=startup
COINMARKETCAP_CREDITS_PER_MONTH=120000
```

### Usage

#### Basic Data Retrieval

```php
use Convertain\CoinMarketCap\CoinMarketCapProvider;

$provider = app(CoinMarketCapProvider::class);

// Get cryptocurrency listings with automatic data transformation
$listings = $provider->getCryptocurrencyListings([
    'limit' => 100,
    'convert' => 'USD'
]);

// Get global market metrics
$globalMetrics = $provider->getGlobalMetrics([
    'convert' => ['USD', 'EUR']
]);
```

#### Credit Management

```php
use Convertain\CoinMarketCap\Credit\CreditManager;
use Convertain\CoinMarketCap\Credit\PlanManager;

$creditManager = app(CreditManager::class);
$planManager = app(PlanManager::class);

// Check remaining credits
$remaining = $creditManager->getRemainingCredits();

// Get usage statistics
$stats = $creditManager->getUsageStats();

// Check if call is within limits
$canMakeCall = $creditManager->canMakeCall('cryptocurrency/quotes/latest', 1);

// Get plan recommendations
$recommendations = $planManager->getUpgradeRecommendations($currentUsage, 1.2);
```

#### Credit Optimization

```php
use Convertain\CoinMarketCap\Credit\CreditOptimizer;

$optimizer = app(CreditOptimizer::class);

// Optimize request parameters
$optimizedParams = $optimizer->optimizeRequest('cryptocurrency/quotes/latest', [
    'id' => range(1, 200), // Will be batched optimally
    'convert' => ['USD', 'EUR', 'BTC', 'ETH', 'JPY'] // Will be filtered to supported
]);

// Check if cache should be used instead of API call
$cacheDecision = $optimizer->shouldUseCache('cryptocurrency/quotes/latest', $params);

// Get cost-benefit analysis
$analysis = $optimizer->calculateCostBenefit('cryptocurrency/quotes/latest', $params, 300);
```

## Data Transformation

All API responses are automatically normalized and transformed:

### Input (Raw CoinMarketCap API Response)
```json
{
  "data": {
    "1": {
      "id": 1,
      "symbol": "BTC",
      "quote": {
        "USD": {
          "price": 45000.123456,
          "last_updated": "2024-01-01T12:00:00.000Z"
        }
      }
    }
  }
}
```

### Output (Transformed Response)
```json
{
  "data": [
    {
      "id": 1,
      "symbol": "BTC",
      "name": "Bitcoin",
      "quotes": {
        "USD": {
          "price": 45000.123456,
          "last_updated": "2024-01-01T12:00:00.000Z"
        }
      }
    }
  ],
  "status": {
    "timestamp": "2024-01-01T12:00:00.000Z",
    "credit_count": 1
  },
  "meta": {
    "timestamp": "2024-01-01T12:00:00.000Z",
    "num_cryptocurrencies": 1,
    "credit_count": 1
  }
}
```

## Credit Management Plans

The package supports all CoinMarketCap subscription plans:

| Plan | Monthly Credits | Daily Calls | Per-Minute Calls | Features |
|------|----------------|-------------|------------------|----------|
| Basic | 10,000 | 333 | 30 | Basic endpoints, crypto data |
| Hobbyist | 40,000 | 1,333 | 30 | + Exchange data, limited historical |
| Startup | 120,000 | 4,000 | 60 | + Global metrics, full historical, batch requests |
| Standard | 500,000 | 16,667 | 60 | + Trending data, OHLCV data |
| Professional | 2,000,000 | 66,667 | 60 | + Market pairs, advanced filtering |
| Enterprise | 100,000,000 | 3,333,333 | 120 | + Priority support, custom limits |

## Optimization Features

### Automatic Request Optimization
- **Batch Size Optimization**: Automatically adjusts request batch sizes based on plan limits
- **Currency Filtering**: Filters unsupported currencies from convert parameters
- **Field Selection**: Optimizes aux parameters to reduce response size and cost
- **Cache-First Strategy**: Intelligently uses cached data when credit limits are reached

### Cost-Benefit Analysis
- Real-time evaluation of API call necessity vs cached data freshness
- Credit consumption forecasting
- Plan utilization efficiency scoring
- Upgrade recommendations based on usage patterns

### Rate Limit Management
- Per-minute and daily call tracking
- Intelligent request scheduling
- Automatic backoff when approaching limits
- Rate limit recovery time calculation

## Testing

Run the package tests:

```bash
composer test
```

Run the demo scripts:

```bash
# Test data transformers
php demo/transformer_demo.php

# Test credit management
php demo/credit_demo.php
```

## Configuration Reference

See `config/coinmarketcap.php` for full configuration options including:

- API connection settings
- Subscription plan configuration  
- Credit tracking and optimization settings
- Cache configuration with endpoint-specific TTL
- Logging and event configuration
- Endpoint-specific cost mapping

## Requirements

- PHP 8.3+
- Laravel 12.0+
- GuzzleHTTP 7.0+
- Carbon 3.0+

## License

Proprietary - Copyright © Convertain

## Support

For support and questions, please contact: christian@convertain.com
