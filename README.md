# Laravel CoinMarketCap Pro API Provider

<p align="center">
<a href="https://packagist.org/packages/convertain/laravel-coinmarketcap"><img src="https://img.shields.io/packagist/v/convertain/laravel-coinmarketcap.svg?style=flat-square" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/convertain/laravel-coinmarketcap"><img src="https://img.shields.io/packagist/dt/convertain/laravel-coinmarketcap.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/convertain/laravel-coinmarketcap"><img src="https://img.shields.io/packagist/l/convertain/laravel-coinmarketcap.svg?style=flat-square" alt="License"></a>
</p>

A comprehensive Laravel package for CoinMarketCap Pro API integration with advanced **credit optimization**, intelligent caching, and cost-effective usage strategies. This package implements the CryptoDataProvider interface to deliver cryptocurrency market data, exchange information, and global metrics while minimizing API costs.

## 🎯 Key Features

- **💰 Credit Optimization**: Advanced strategies to minimize API credit consumption
- **📊 Comprehensive Data**: Complete access to CoinMarketCap Pro API endpoints
- **🚀 Smart Caching**: Intelligent caching with endpoint-specific TTL strategies
- **📈 Plan Integration**: Built-in support for all CoinMarketCap subscription plans
- **⚡ Performance**: Optimized for high-performance applications
- **🔔 Monitoring**: Credit usage tracking and threshold alerts
- **🛡️ Reliable**: Automatic retry mechanisms and error handling
- **📚 Well Documented**: Comprehensive documentation with examples

## 💵 Credit Optimization Focus

This package is specifically designed to help you **minimize CoinMarketCap API costs** while maximizing data quality and application performance. With proper configuration, you can reduce credit consumption by up to **80%** through intelligent caching and optimization strategies.

### Quick Credit Savings
- **Static Data**: 24-hour caching for maps and info (saves 95% of credits)
- **Price Data**: Smart 1-5 minute caching based on volatility
- **Batch Requests**: Optimize multiple symbol requests
- **Plan Optimization**: Choose the right plan for your usage patterns

## 🚀 Quick Start

### Installation

```bash
composer require convertain/laravel-coinmarketcap
```

### Configuration

1. **Publish the configuration:**
```bash
php artisan vendor:publish --provider="Convertain\CoinMarketCap\CoinMarketCapServiceProvider" --tag="coinmarketcap-config"
```

2. **Add your API key to `.env`:**
```env
COINMARKETCAP_API_KEY=your_api_key_here
COINMARKETCAP_PLAN=basic
COINMARKETCAP_CREDITS_PER_MONTH=10000
```

3. **Configure credit optimization:**
```env
COINMARKETCAP_CACHE_ENABLED=true
COINMARKETCAP_CREDIT_TRACKING=true
COINMARKETCAP_CREDIT_WARNING=0.8
```

### Basic Usage

```php
use Convertain\CoinMarketCap\CoinMarketCapProvider;

// Get the provider instance
$provider = app(CoinMarketCapProvider::class);

// Fetch Bitcoin price (cached automatically)
$bitcoin = $provider->getCryptocurrency('BTC');
echo $bitcoin->getCurrentPrice('USD'); // Minimal credit usage

// Batch request for multiple cryptocurrencies (optimized)
$cryptos = $provider->getCryptocurrencies(['BTC', 'ETH', 'USDT']);

// Get exchange information
$exchanges = $provider->getExchanges();
```

## 📊 Credit Optimization Guide

### Plan Comparison

| Plan | Monthly Credits | Calls/Minute | Calls/Day | Monthly Cost |
|------|----------------|--------------|-----------|--------------|
| Basic | 10,000 | 30 | 333 | Free |
| Hobbyist | 40,000 | 30 | 1,333 | $29 |
| Startup | 120,000 | 60 | 4,000 | $79 |
| Standard | 500,000 | 60 | 16,667 | $249 |
| Professional | 2,000,000 | 60 | 66,667 | $699 |
| Enterprise | 100,000,000 | 120 | 3,333,333 | Custom |

### Optimization Strategies

#### 1. Smart Caching Configuration
```php
// config/coinmarketcap.php
'cache' => [
    'enabled' => true,
    'ttl' => [
        'cryptocurrency_info' => 86400,    // 24h - Static data
        'cryptocurrency_quotes' => 60,     // 1min - Dynamic prices
        'cryptocurrency_listings' => 300,  // 5min - Market listings
    ],
],
```

#### 2. Batch Requests
```php
// Instead of multiple single requests (100 credits)
foreach ($symbols as $symbol) {
    $crypto = $provider->getCryptocurrency($symbol); // 1 credit each
}

// Use batch requests (1 credit for up to 100 symbols)
$cryptos = $provider->getCryptocurrencies($symbols); // 1 credit total
```

#### 3. Strategic Data Fetching
```php
// Fetch only necessary data
$options = [
    'convert' => 'USD',           // Single currency conversion
    'aux' => 'num_market_pairs',  // Minimal auxiliary data
];
$listings = $provider->getListings($options);
```

## 🔧 Configuration

### Environment Variables

```env
# API Configuration
COINMARKETCAP_API_KEY=your_api_key
COINMARKETCAP_BASE_URL=https://pro-api.coinmarketcap.com/v2
COINMARKETCAP_TIMEOUT=30

# Plan Configuration
COINMARKETCAP_PLAN=basic
COINMARKETCAP_CREDITS_PER_MONTH=10000
COINMARKETCAP_CALLS_PER_MINUTE=30

# Credit Management
COINMARKETCAP_CREDIT_TRACKING=true
COINMARKETCAP_CREDIT_WARNING=0.8
COINMARKETCAP_CREDIT_OPTIMIZATION=true

# Cache Configuration
COINMARKETCAP_CACHE_ENABLED=true
COINMARKETCAP_CACHE_QUOTES_TTL=60
COINMARKETCAP_CACHE_INFO_TTL=86400

# Logging
COINMARKETCAP_LOGGING_ENABLED=true
COINMARKETCAP_LOG_CREDITS=true
```

### Advanced Configuration

```php
// config/coinmarketcap.php
return [
    'plan' => [
        'type' => env('COINMARKETCAP_PLAN', 'basic'),
        'credits_per_month' => env('COINMARKETCAP_CREDITS_PER_MONTH', 10000),
        'calls_per_minute' => env('COINMARKETCAP_CALLS_PER_MINUTE', 30),
    ],
    
    'credits' => [
        'tracking_enabled' => true,
        'warning_threshold' => 0.8, // Alert at 80% usage
        'optimization_enabled' => true,
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => [
            // Optimize cache duration based on data volatility
            'cryptocurrency_info' => 86400,    // Static: 24 hours
            'cryptocurrency_quotes' => 60,     // Dynamic: 1 minute
            'exchange_listings' => 300,        // Semi-static: 5 minutes
        ],
    ],
];
```

## 📚 Documentation

### Complete Guides
- **[API Reference](docs/API.md)** - Complete method documentation
- **[Credit Optimization](docs/CREDIT_OPTIMIZATION.md)** - Detailed optimization strategies
- **[Plan Selection Guide](docs/PLAN_SELECTION.md)** - Choose the right plan
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues and solutions
- **[Examples](docs/EXAMPLES.md)** - Real-world usage examples
- **[Performance Guide](docs/PERFORMANCE.md)** - Performance optimization

### Quick Links
- [Installation & Setup](#installation)
- [Credit Optimization](#credit-optimization-guide)
- [Usage Examples](docs/EXAMPLES.md)
- [API Reference](docs/API.md)

## 🔔 Credit Monitoring

### Automatic Alerts
```php
// Listen for credit warnings
Event::listen('coinmarketcap.credit.warning', function ($event) {
    Log::warning('CoinMarketCap credit usage at ' . ($event->percentage * 100) . '%');
    
    // Send notification
    Notification::route('slack', config('alerts.slack_webhook'))
        ->notify(new CreditWarningNotification($event));
});
```

### Manual Monitoring
```php
// Check current credit usage
$creditInfo = $provider->getCreditUsage();
echo "Used: {$creditInfo['credits_used']} / {$creditInfo['credits_total']}";
echo "Remaining: {$creditInfo['credits_remaining']}";
```

## 🚀 Performance Tips

1. **Use Appropriate Cache TTL**: Configure cache durations based on data update frequency
2. **Batch Requests**: Group multiple symbol requests together
3. **Selective Fields**: Request only necessary data fields
4. **Plan Optimization**: Choose plans that match your usage patterns
5. **Monitor Usage**: Set up alerts to avoid credit overages

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This package is proprietary software. See the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: Check our [comprehensive docs](docs/)
- **Issues**: [GitHub Issues](https://github.com/Convertain/laravel-coinmarketcap/issues)
- **Questions**: Create a discussion in our [GitHub Discussions](https://github.com/Convertain/laravel-coinmarketcap/discussions)

## 📈 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

---

**💡 Pro Tip**: Start with the `basic` plan and monitor your credit usage patterns. Use our [Plan Selection Guide](docs/PLAN_SELECTION.md) to optimize your subscription based on actual usage data.
