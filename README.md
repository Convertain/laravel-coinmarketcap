# laravel-coinmarketcap

Laravel CoinMarketCap Pro API provider package implementing CryptoDataProvider interface for comprehensive cryptocurrency market data, exchange information, and global metrics with credit optimization.

## 🎯 Features

- **Complete Event System**: Comprehensive monitoring of API calls, credit usage, and performance
- **Credit Management**: Real-time credit tracking with intelligent warnings and optimization
- **Performance Monitoring**: Response time tracking, cache hit rates, and endpoint analytics
- **Error Handling**: Detailed error tracking with retry monitoring and credit waste prevention
- **Rate Limit Management**: Automatic rate limit detection and handling
- **Laravel Integration**: Full Laravel event system integration with configurable listeners

## 📊 Event System

The package includes a comprehensive event system for complete observability of CoinMarketCap API operations:

### Events Available
- **`ApiCallMade`** - Tracks all API calls with credit consumption and performance metrics
- **`CreditConsumed`** - Monitors credit usage with percentage tracking
- **`CreditWarning`** - Alerts when approaching credit limits with usage predictions
- **`RateLimitHit`** - Tracks rate limiting incidents with reset times
- **`ApiError`** - Comprehensive error tracking with credit waste monitoring
- **`RetryAttempt`** - Detailed retry attempt logging with context

### Event Monitor Service
```php
use Convertain\CoinMarketCap\Monitoring\EventMonitor;

$monitor = app(EventMonitor::class);

// Get comprehensive analytics
$analytics = $monitor->getAnalytics(24); // Last 24 hours

// Fire events programmatically
$monitor->apiCallMade('/cryptocurrency/quotes/latest', 'GET', 1, 250.5, false, 'basic');
```

### Built-in Listeners
- **`LogApiCall`** - Logs API calls with performance warnings
- **`LogCreditWarning`** - Severity-based credit usage logging
- **`LogApiError`** - Detailed error logging with context

## 📚 Documentation

- **[Events System Guide](EVENTS.md)** - Complete events documentation with examples
- **[Configuration Guide](config/coinmarketcap.php)** - Detailed configuration options
- **[Examples](examples/)** - Working code examples and integrations

## 🏗️ Architecture

### Core Components
- **6 Event Classes** - Comprehensive event coverage for all API operations
- **1 Monitor Service** - Centralized event management with analytics
- **3 Event Listeners** - Built-in logging and monitoring capabilities
- **Service Provider** - Laravel integration with automatic registration

### Code Quality
- ✅ PSR-12 compliant code structure
- ✅ Comprehensive PHPDoc documentation
- ✅ Unit tests with 100% event coverage
- ✅ Immutable event objects with readonly properties
- ✅ Zero breaking changes to existing code

## 🚀 Quick Start

1. Install the package via Composer
2. Configure your CoinMarketCap API key and plan
3. Events are automatically enabled - start monitoring immediately!

```php
// Events fire automatically for all API operations
// Configure in config/coinmarketcap.php
'events' => [
    'enabled' => true,
    'dispatch' => [
        'api_call_made' => true,
        'credit_warning' => true,
        'api_error' => true,
    ],
],
```

## 📈 Analytics & Monitoring

The event system provides comprehensive analytics including:
- API call volumes and response times
- Credit usage patterns and predictions
- Cache effectiveness metrics
- Error rates and troubleshooting data
- Endpoint popularity and performance
- Real-time threshold monitoring

## 🔧 Testing

```bash
# Run unit tests
phpunit tests/

# Check syntax
php -l src/Events/*.php
php -l src/Listeners/*.php
php -l src/Monitoring/*.php

# Run demo
php examples/events_demo.php
```

## 📝 License

Proprietary - See package configuration for details.
