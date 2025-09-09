# Credit Optimization Guide

Comprehensive strategies to minimize CoinMarketCap API credit consumption while maximizing data quality and application performance.

## Table of Contents

- [Understanding Credits](#understanding-credits)
- [Credit Cost Breakdown](#credit-cost-breakdown)
- [Optimization Strategies](#optimization-strategies)
- [Caching Strategies](#caching-strategies)
- [Batch Processing](#batch-processing)
- [Data Selection Optimization](#data-selection-optimization)
- [Monitoring and Alerts](#monitoring-and-alerts)
- [Plan-Specific Strategies](#plan-specific-strategies)
- [Real-World Examples](#real-world-examples)
- [Credit Audit and Analysis](#credit-audit-and-analysis)

## Understanding Credits

CoinMarketCap's Pro API uses a **credit-based system** where each API call consumes a certain number of credits based on:

- **Endpoint called**: Different endpoints have different costs
- **Amount of data returned**: More data = more credits
- **Request parameters**: Some parameters increase credit cost

### Credit Reset Schedule

- Credits reset **monthly** on your billing cycle date
- Unused credits do **not** roll over to the next month
- Monitor usage to avoid overages

### Credit vs. Request Limits

**Credits**: Measure data consumption (primary limiting factor)
**Requests**: Measure API call frequency (rate limiting)

You can make many low-cost requests or fewer high-cost requests within your credit budget.

## Credit Cost Breakdown

### Standard Endpoint Costs

| Endpoint | Base Cost | Per Unit Cost | Max Units |
|----------|-----------|---------------|-----------|
| `/cryptocurrency/quotes/latest` | 1 credit | +0 for bulk (up to 100) | 100 symbols |
| `/cryptocurrency/listings/latest` | 1 credit | +1 per 200 results | 5,000 results |
| `/cryptocurrency/info` | 1 credit | +1 per 100 results | 10,000 results |
| `/cryptocurrency/map` | 1 credit | Fixed | All cryptos |
| `/exchange/listings/latest` | 1 credit | +1 per 100 results | 5,000 results |
| `/global-metrics/quotes/latest` | 1 credit | Fixed | Global data |

### Historical Data Costs

| Endpoint | Base Cost | Additional Cost |
|----------|-----------|-----------------|
| `/cryptocurrency/quotes/historical` | 1 credit | +1 per 100 data points |
| `/cryptocurrency/ohlcv/historical` | 1 credit | +1 per 100 data points |

### Conversion Costs

Multiple currency conversions in a single request **do not** increase credit costs:

```php
// Same credit cost (1 credit)
$bitcoin1 = $provider->getCryptocurrency('BTC', ['convert' => 'USD']);
$bitcoin2 = $provider->getCryptocurrency('BTC', ['convert' => 'USD,EUR,JPY,GBP']);
```

## Optimization Strategies

### 1. Intelligent Caching Strategy

Implement **differential caching** based on data volatility:

```php
// config/coinmarketcap.php
'cache' => [
    'ttl' => [
        // Static data - cache for 24 hours (95% credit savings)
        'cryptocurrency_info' => 86400,
        'cryptocurrency_map' => 86400,
        'exchange_info' => 86400,
        
        // Semi-static data - cache for 1 hour (85% credit savings)
        'cryptocurrency_listings' => 3600,
        'exchange_listings' => 3600,
        
        // Dynamic data - cache for 5 minutes (80% credit savings)
        'cryptocurrency_quotes' => 300,
        'global_metrics' => 300,
        
        // High-frequency data - cache for 1 minute (50% credit savings)
        'market_pairs' => 60,
        'ohlcv_latest' => 60,
    ],
],
```

### 2. Batch Request Optimization

**Always use batch requests** when fetching multiple items:

```php
// ❌ BAD: Multiple individual requests (100 credits)
$cryptos = [];
foreach (['BTC', 'ETH', 'ADA', 'DOT', ...] as $symbol) {
    $cryptos[] = $provider->getCryptocurrency($symbol); // 1 credit each
}

// ✅ GOOD: Single batch request (1 credit)
$cryptos = $provider->getCryptocurrencies([
    'BTC', 'ETH', 'ADA', 'DOT', ... // Up to 100 symbols
]);
```

### 3. Strategic Data Fetching

**Minimize auxiliary data** to reduce processing overhead:

```php
// ❌ Requesting all auxiliary data
$listings = $provider->getCryptocurrencyListings([
    'aux' => 'urls,logo,description,tags,platform,date_added,notice,status'
]);

// ✅ Request only necessary data
$listings = $provider->getCryptocurrencyListings([
    'aux' => 'num_market_pairs,cmc_rank' // Only essential data
]);
```

### 4. Filtering and Pagination

**Use filters** to reduce result sets:

```php
// ❌ Fetching all data and filtering in application
$allCryptos = $provider->getCryptocurrencyListings(['limit' => 5000]);
$topCryptos = array_slice($allCryptos, 0, 100);

// ✅ Filter at API level
$topCryptos = $provider->getCryptocurrencyListings([
    'limit' => 100,
    'market_cap_min' => 1000000, // Only cryptos with >$1M market cap
]);
```

### 5. Currency Conversion Optimization

**Request multiple conversions** in single calls:

```php
// ✅ Single request with multiple conversions (1 credit)
$bitcoin = $provider->getCryptocurrency('BTC', [
    'convert' => 'USD,EUR,JPY,GBP,BTC,ETH'
]);

// Access all conversions from one request
$usdPrice = $bitcoin->getCurrentPrice('USD');
$eurPrice = $bitcoin->getCurrentPrice('EUR');
$btcPrice = $bitcoin->getCurrentPrice('BTC');
```

## Caching Strategies

### Tiered Caching System

Implement a **multi-tier caching approach**:

```php
// config/coinmarketcap.php
'cache' => [
    'enabled' => true,
    'store' => 'redis', // Use Redis for better performance
    'prefix' => 'cmc_',
    
    // Tier 1: Static/Reference Data (24+ hours)
    'static_ttl' => 86400,
    
    // Tier 2: Market Data (5-60 minutes)  
    'market_ttl' => 300,
    
    // Tier 3: Price Data (30 seconds - 5 minutes)
    'price_ttl' => 60,
    
    // Tier 4: Real-time Data (10-30 seconds)
    'realtime_ttl' => 30,
];
```

### Cache Invalidation Strategy

```php
// Custom cache invalidation logic
class CoinMarketCapCacheManager
{
    public function invalidateOnVolatility($symbol, $changePercent)
    {
        // Invalidate cache for highly volatile assets
        if (abs($changePercent) > 10) {
            Cache::forget("cmc_quotes_{$symbol}");
        }
    }
    
    public function warmCache($symbols)
    {
        // Pre-warm cache during low-traffic periods
        $this->provider->getCryptocurrencies($symbols);
    }
}
```

### Cache Preloading

```php
// Schedule cache warming during off-peak hours
// app/Console/Commands/WarmCoinMarketCapCache.php

public function handle()
{
    $topSymbols = ['BTC', 'ETH', 'USDT', 'BNB', 'XRP', 'ADA', 'SOL', 'DOGE'];
    
    // Pre-load popular cryptocurrencies
    $this->provider->getCryptocurrencies($topSymbols);
    
    // Pre-load exchange data
    $this->provider->getExchanges(['limit' => 50]);
    
    $this->info('Cache warmed successfully');
}
```

## Batch Processing

### Optimal Batch Sizes

**Cryptocurrency Quotes**: Up to 100 symbols per request
```php
$batchSize = 100;
$symbolBatches = array_chunk($allSymbols, $batchSize);

foreach ($symbolBatches as $batch) {
    $results = $provider->getCryptocurrencies($batch);
    // Process batch results
}
```

**Listings**: Use limit parameter effectively
```php
// Get top 200 cryptocurrencies (1 credit)
$top200 = $provider->getCryptocurrencyListings(['limit' => 200]);

// Get next 200 (1 additional credit)
$next200 = $provider->getCryptocurrencyListings([
    'start' => 201,
    'limit' => 200
]);
```

### Parallel Processing

```php
use Illuminate\Support\Facades\Http;

class OptimizedCoinMarketCapService
{
    public function fetchMultipleEndpoints()
    {
        $responses = Http::pool(fn (Pool $pool) => [
            $pool->as('quotes')->get('/cryptocurrency/quotes/latest', $params1),
            $pool->as('listings')->get('/cryptocurrency/listings/latest', $params2),
            $pool->as('global')->get('/global-metrics/quotes/latest', $params3),
        ]);
        
        return [
            'quotes' => $responses['quotes']->json(),
            'listings' => $responses['listings']->json(),
            'global' => $responses['global']->json(),
        ];
    }
}
```

## Data Selection Optimization

### Minimal Data Requests

**Choose appropriate auxiliary fields**:

```php
// For price tracking (minimal data)
$priceData = $provider->getCryptocurrencies(['BTC', 'ETH'], [
    'convert' => 'USD',
    'aux' => 'cmc_rank' // Only rank info
]);

// For detailed analysis (comprehensive data)
$detailedData = $provider->getCryptocurrencies(['BTC', 'ETH'], [
    'convert' => 'USD,BTC',
    'aux' => 'num_market_pairs,cmc_rank,max_supply,circulating_supply,total_supply'
]);
```

### Conditional Data Fetching

```php
class SmartCoinMarketCapService
{
    public function getCryptocurrencyData($symbol, $detail_level = 'basic')
    {
        $baseOptions = ['convert' => 'USD'];
        
        switch ($detail_level) {
            case 'basic':
                $baseOptions['aux'] = 'cmc_rank';
                break;
                
            case 'standard':
                $baseOptions['aux'] = 'cmc_rank,num_market_pairs,max_supply';
                break;
                
            case 'detailed':
                $baseOptions['aux'] = 'cmc_rank,num_market_pairs,max_supply,circulating_supply,total_supply,market_cap_dominance';
                break;
        }
        
        return $this->provider->getCryptocurrency($symbol, $baseOptions);
    }
}
```

## Monitoring and Alerts

### Credit Usage Tracking

```php
// app/Services/CreditMonitoringService.php
class CreditMonitoringService
{
    public function trackUsage($creditsUsed, $endpoint)
    {
        DB::table('coinmarketcap_usage')->insert([
            'endpoint' => $endpoint,
            'credits_used' => $creditsUsed,
            'timestamp' => now(),
        ]);
        
        $this->checkThresholds();
    }
    
    protected function checkThresholds()
    {
        $monthlyUsage = $this->getMonthlyUsage();
        $monthlyLimit = config('coinmarketcap.plan.credits_per_month');
        $usagePercent = $monthlyUsage / $monthlyLimit;
        
        if ($usagePercent > 0.8) {
            event(new CreditThresholdExceeded($usagePercent));
        }
    }
}
```

### Automated Alerts

```php
// app/Listeners/CreditAlertListener.php
class CreditAlertListener
{
    public function handle(CreditThresholdExceeded $event)
    {
        if ($event->percentage > 0.9) {
            // Critical alert
            Mail::to(config('alerts.admin_email'))
                ->send(new CreditLimitWarning($event->percentage));
        }
        
        // Log to monitoring service
        Log::channel('coinmarketcap')->warning('High credit usage', [
            'percentage' => $event->percentage * 100,
            'timestamp' => now(),
        ]);
    }
}
```

### Usage Analytics Dashboard

```php
// Generate usage reports
class CreditAnalyticsService
{
    public function getDailyUsageReport()
    {
        return DB::table('coinmarketcap_usage')
            ->select(
                DB::raw('DATE(timestamp) as date'),
                DB::raw('SUM(credits_used) as total_credits'),
                DB::raw('COUNT(*) as total_requests')
            )
            ->where('timestamp', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }
    
    public function getEndpointUsageBreakdown()
    {
        return DB::table('coinmarketcap_usage')
            ->select(
                'endpoint',
                DB::raw('SUM(credits_used) as total_credits'),
                DB::raw('COUNT(*) as request_count'),
                DB::raw('AVG(credits_used) as avg_credits_per_request')
            )
            ->where('timestamp', '>=', now()->startOfMonth())
            ->groupBy('endpoint')
            ->orderBy('total_credits', 'desc')
            ->get();
    }
}
```

## Plan-Specific Strategies

### Basic Plan (10,000 credits/month)

**Focus**: Maximum cache efficiency and selective data fetching

```php
'cache' => [
    'ttl' => [
        'cryptocurrency_info' => 86400 * 7,  // 7 days for static data
        'cryptocurrency_quotes' => 1800,     // 30 minutes for prices
        'cryptocurrency_listings' => 7200,   // 2 hours for listings
    ],
],

// Limit to essential data only
'supported_cryptocurrencies' => [
    'BTC', 'ETH', 'USDT', 'BNB', 'XRP' // Top 5 only
],
```

### Hobbyist Plan (40,000 credits/month)

**Focus**: Balanced caching with moderate data fetching

```php
'cache' => [
    'ttl' => [
        'cryptocurrency_info' => 86400,      // 24 hours
        'cryptocurrency_quotes' => 600,      // 10 minutes
        'cryptocurrency_listings' => 1800,   // 30 minutes
    ],
],

'supported_cryptocurrencies' => [
    // Top 20 cryptocurrencies
],
```

### Startup+ Plans (120,000+ credits/month)

**Focus**: Performance optimization with strategic caching

```php
'cache' => [
    'ttl' => [
        'cryptocurrency_info' => 86400,      // 24 hours for static
        'cryptocurrency_quotes' => 300,      // 5 minutes for prices
        'cryptocurrency_listings' => 900,    // 15 minutes for listings
        'exchange_data' => 1800,             // 30 minutes for exchanges
    ],
],

// Enable all features
'features' => [
    'historical_data' => true,
    'exchange_data' => true,
    'global_metrics' => true,
],
```

## Real-World Examples

### Example 1: Portfolio Tracking App

**Requirement**: Track 50 cryptocurrencies with price updates every 5 minutes

**Naive Implementation** (Credits: ~14,400/month):
```php
// Updates every 5 minutes, 50 individual requests
// 50 credits × 288 times/day × 30 days = 432,000 credits ❌
foreach ($portfolio as $symbol) {
    $price = $provider->getCryptocurrency($symbol);
}
```

**Optimized Implementation** (Credits: ~288/month):
```php
// Batch request every 5 minutes, cached for 5 minutes
// 1 credit × 288 times/day × 30 days = 8,640 credits ✅
$portfolioPrices = Cache::remember('portfolio_prices', 300, function () {
    return $provider->getCryptocurrencies($this->portfolioSymbols);
});
```

**Credit Savings**: 99.9% reduction

### Example 2: Market Analysis Dashboard

**Requirement**: Daily market analysis with historical data

**Strategy**:
```php
class MarketAnalysisService
{
    public function generateDailyReport()
    {
        // 1. Fetch top 100 cryptocurrencies (1 credit, cached 4 hours)
        $topCryptos = Cache::remember('top_100_cryptos', 14400, function () {
            return $provider->getCryptocurrencyListings(['limit' => 100]);
        });
        
        // 2. Get global metrics (1 credit, cached 1 hour)
        $globalMetrics = Cache::remember('global_metrics', 3600, function () {
            return $provider->getGlobalMetrics();
        });
        
        // 3. Historical data for trending analysis (fetched once daily)
        $historicalData = Cache::remember('historical_trend', 86400, function () {
            return $provider->getHistoricalQuotes(['BTC', 'ETH'], now()->subDays(7));
        });
        
        return $this->compileReport($topCryptos, $globalMetrics, $historicalData);
    }
}
```

**Monthly Credit Usage**: ~150 credits (vs. 3,000+ without optimization)

### Example 3: Trading Bot

**Requirement**: High-frequency price monitoring for 10 trading pairs

**Implementation**:
```php
class TradingBotService
{
    protected $tradingPairs = ['BTC', 'ETH', 'ADA', 'DOT', 'LINK'];
    
    public function updatePrices()
    {
        // Update every 30 seconds, batch request
        $prices = Cache::remember('trading_pairs_prices', 30, function () {
            return $provider->getCryptocurrencies($this->tradingPairs, [
                'convert' => 'USD,BTC',
                'aux' => 'volume_24h,percent_change_1h' // Only trading-relevant data
            ]);
        });
        
        return $this->processSignals($prices);
    }
}
```

**Daily Credits**: ~2,880 credits
**Monthly Credits**: ~86,400 credits (fits in Startup plan)

## Credit Audit and Analysis

### Monthly Credit Audit

```php
class CreditAuditService
{
    public function generateMonthlyAudit()
    {
        $usage = DB::table('coinmarketcap_usage')
            ->where('timestamp', '>=', now()->startOfMonth())
            ->get();
            
        return [
            'total_credits' => $usage->sum('credits_used'),
            'total_requests' => $usage->count(),
            'avg_credits_per_request' => $usage->avg('credits_used'),
            'top_endpoints' => $this->getTopEndpoints($usage),
            'daily_breakdown' => $this->getDailyBreakdown($usage),
            'optimization_opportunities' => $this->findOptimizations($usage),
        ];
    }
    
    protected function findOptimizations($usage)
    {
        $opportunities = [];
        
        // Find high-frequency, low-cache endpoints
        $highFrequency = $usage->groupBy('endpoint')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'credits' => $group->sum('credits_used')
                ];
            })
            ->sortByDesc('count');
            
        foreach ($highFrequency as $endpoint => $stats) {
            if ($stats['count'] > 100 && $stats['credits'] / $stats['count'] >= 1) {
                $opportunities[] = [
                    'endpoint' => $endpoint,
                    'potential_savings' => $stats['credits'] * 0.8, // 80% cache hit rate
                    'recommendation' => 'Increase cache TTL or implement smarter caching'
                ];
            }
        }
        
        return $opportunities;
    }
}
```

### Performance Benchmarking

```php
class CreditPerformanceBenchmark
{
    public function benchmarkEndpoint($endpoint, $params, $iterations = 100)
    {
        $totalCredits = 0;
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $result = $this->callEndpoint($endpoint, $params);
            $end = microtime(true);
            
            $totalCredits += $result['credit_cost'];
            $totalTime += ($end - $start);
        }
        
        return [
            'avg_credits_per_call' => $totalCredits / $iterations,
            'avg_response_time' => $totalTime / $iterations,
            'credits_per_second' => $totalCredits / $totalTime,
            'recommendation' => $this->getOptimizationRecommendation($totalCredits, $totalTime)
        ];
    }
}
```

## Advanced Optimization Techniques

### 1. Smart Cache Warming

```php
// Predict and pre-load likely requests
class PredictiveCacheService
{
    public function warmPredictedRequests()
    {
        $popularSymbols = $this->getMostRequestedSymbols();
        $upcomingRequests = $this->predictUpcomingRequests();
        
        // Pre-warm cache during low-usage periods (e.g., 3 AM)
        if (now()->hour === 3) {
            $provider->getCryptocurrencies($popularSymbols);
        }
    }
}
```

### 2. Circuit Breaker Pattern

```php
class CreditCircuitBreaker
{
    protected $failureThreshold = 0.9; // 90% credit usage
    protected $recoveryTime = 3600;    // 1 hour
    
    public function canMakeRequest()
    {
        $usage = $this->getCurrentUsagePercent();
        
        if ($usage > $this->failureThreshold) {
            // Switch to cache-only mode
            return false;
        }
        
        return true;
    }
}
```

### 3. Dynamic TTL Adjustment

```php
class DynamicCacheManager
{
    public function getOptimalTTL($endpoint, $dataAge, $volatility)
    {
        $baseTTL = config("coinmarketcap.cache.ttl.{$endpoint}");
        
        // Adjust based on data volatility
        if ($volatility > 15) {
            return $baseTTL * 0.5; // High volatility = shorter cache
        } elseif ($volatility < 2) {
            return $baseTTL * 2;   // Low volatility = longer cache
        }
        
        return $baseTTL;
    }
}
```

---

## Summary

By implementing these credit optimization strategies, you can:

- **Reduce credit consumption by 80-95%** through intelligent caching
- **Lower API costs** by choosing the right plan for your usage
- **Improve application performance** through reduced API latency
- **Scale efficiently** as your application grows

**Key Takeaways**:
1. **Cache aggressively** based on data volatility
2. **Always use batch requests** when possible
3. **Monitor and alert** on credit usage
4. **Request only necessary data** to minimize costs
5. **Choose the right plan** for your usage patterns

For specific implementation examples, see the [Examples Guide](EXAMPLES.md).