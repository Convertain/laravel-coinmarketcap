# Troubleshooting Guide

Common issues and solutions for the Laravel CoinMarketCap package, including error diagnosis, performance optimization, and debugging strategies.

## Table of Contents

- [Common Issues](#common-issues)
- [Authentication Problems](#authentication-problems)
- [Rate Limiting Issues](#rate-limiting-issues)
- [Credit Management Problems](#credit-management-problems)
- [Caching Issues](#caching-issues)
- [Performance Problems](#performance-problems)
- [Data Quality Issues](#data-quality-issues)
- [Configuration Problems](#configuration-problems)
- [Debugging Techniques](#debugging-techniques)
- [Error Code Reference](#error-code-reference)

## Common Issues

### Issue: Package Not Working After Installation

**Symptoms:**
- Class not found errors
- Service provider not registered
- Configuration not loading

**Solutions:**
1. **Verify Installation**:
   ```bash
   composer require convertain/laravel-coinmarketcap
   ```

2. **Clear Laravel Caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

3. **Publish Configuration**:
   ```bash
   php artisan vendor:publish --provider="Convertain\CoinMarketCap\CoinMarketCapServiceProvider" --tag="coinmarketcap-config"
   ```

4. **Check Service Provider Registration** (Laravel < 5.5):
   ```php
   // config/app.php
   'providers' => [
       // ...
       Convertain\CoinMarketCap\CoinMarketCapServiceProvider::class,
   ],
   ```

### Issue: API Calls Returning Empty Results

**Symptoms:**
- Empty arrays or null responses
- No error messages
- Successful HTTP status codes

**Diagnosis:**
```php
// Enable request logging
'logging' => [
    'log_requests' => true,
    'log_responses' => true,
],
```

**Solutions:**
1. **Check API Key Configuration**:
   ```env
   COINMARKETCAP_API_KEY=your_actual_api_key_here
   ```

2. **Verify Endpoint Parameters**:
   ```php
   // Check parameter names and values
   $result = $provider->getCryptocurrency('BTC', [
       'convert' => 'USD', // Correct parameter name
   ]);
   ```

3. **Test with Basic Request**:
   ```php
   // Minimal request to test connectivity
   $bitcoin = $provider->getCryptocurrency('BTC');
   dd($bitcoin); // Debug output
   ```

### Issue: Inconsistent Data Between Calls

**Symptoms:**
- Different prices for same cryptocurrency
- Stale data being returned
- Cache-related inconsistencies

**Solutions:**
1. **Check Cache Configuration**:
   ```php
   // Reduce cache TTL for testing
   'cache' => [
       'ttl' => [
           'cryptocurrency_quotes' => 30, // 30 seconds for testing
       ],
   ],
   ```

2. **Clear Specific Cache Keys**:
   ```php
   Cache::forget('coinmarketcap_quotes_BTC');
   ```

3. **Force Fresh Data**:
   ```php
   // Bypass cache temporarily
   $provider = app(CoinMarketCapProvider::class);
   $provider->disableCache();
   $freshData = $provider->getCryptocurrency('BTC');
   $provider->enableCache();
   ```

## Authentication Problems

### Error: "Invalid API Key" (401 Unauthorized)

**Symptoms:**
```json
{
    "status": {
        "error_code": 1001,
        "error_message": "API key missing or invalid"
    }
}
```

**Solutions:**
1. **Verify API Key**:
   ```bash
   # Test API key directly
   curl -H "X-CMC_PRO_API_KEY: your_api_key" \
     "https://pro-api.coinmarketcap.com/v2/cryptocurrency/listings/latest?limit=10"
   ```

2. **Check Environment Configuration**:
   ```php
   // Debug configuration loading
   dd(config('coinmarketcap.api.key'));
   ```

3. **Environment File Issues**:
   ```bash
   # Check .env file syntax
   cat .env | grep COINMARKETCAP
   
   # Ensure no spaces around =
   COINMARKETCAP_API_KEY=abc123  # ✅ Correct
   COINMARKETCAP_API_KEY = abc123  # ❌ Wrong
   ```

4. **Configuration Caching**:
   ```bash
   # Clear configuration cache
   php artisan config:clear
   
   # For production, rebuild config cache
   php artisan config:cache
   ```

### Error: "API Key Suspended" (403 Forbidden)

**Symptoms:**
- Previous API key stops working
- 403 Forbidden responses
- Account suspension notifications

**Solutions:**
1. **Check Account Status**:
   - Log into CoinMarketCap Pro dashboard
   - Verify account standing
   - Check for policy violations

2. **Review Usage Patterns**:
   - Excessive request rates
   - Unusual traffic patterns
   - Terms of service violations

3. **Contact Support**:
   - Submit support ticket with details
   - Provide API key and error logs
   - Request account reinstatement

## Rate Limiting Issues

### Error: "Too Many Requests" (429)

**Symptoms:**
```json
{
    "status": {
        "error_code": 1008,
        "error_message": "You've exceeded your API Key's HTTP request rate limit"
    }
}
```

**Immediate Solutions:**
1. **Implement Backoff Strategy**:
   ```php
   use Illuminate\Support\Facades\Http;
   
   public function makeRequestWithRetry($url, $params, $maxRetries = 3)
   {
       $retryDelay = 1; // Start with 1 second
       
       for ($i = 0; $i < $maxRetries; $i++) {
           try {
               $response = Http::timeout(30)->get($url, $params);
               
               if ($response->status() === 429) {
                   $retryAfter = $response->header('Retry-After', $retryDelay);
                   sleep($retryAfter);
                   $retryDelay *= 2; // Exponential backoff
                   continue;
               }
               
               return $response;
           } catch (Exception $e) {
               if ($i === $maxRetries - 1) {
                   throw $e;
               }
               sleep($retryDelay);
               $retryDelay *= 2;
           }
       }
   }
   ```

2. **Check Current Plan Limits**:
   ```php
   // Verify plan configuration
   $planLimits = config('coinmarketcap.plan');
   
   echo "Calls per minute: " . $planLimits['calls_per_minute'];
   echo "Calls per day: " . $planLimits['calls_per_day'];
   ```

3. **Implement Rate Limiting**:
   ```php
   use Illuminate\Cache\RateLimiter;
   
   class CoinMarketCapRateLimiter
   {
       protected $rateLimiter;
       
       public function __construct(RateLimiter $rateLimiter)
       {
           $this->rateLimiter = $rateLimiter;
       }
       
       public function attemptCall($key = 'coinmarketcap')
       {
           $maxAttempts = config('coinmarketcap.plan.calls_per_minute');
           $decaySeconds = 60;
           
           return $this->rateLimiter->tooManyAttempts($key, $maxAttempts)
               ? false
               : $this->rateLimiter->hit($key, $decaySeconds);
       }
   }
   ```

**Long-term Solutions:**
1. **Optimize Request Patterns**:
   ```php
   // Batch requests instead of individual calls
   $cryptos = $provider->getCryptocurrencies(['BTC', 'ETH', 'ADA']);
   
   // Instead of:
   // $btc = $provider->getCryptocurrency('BTC');
   // $eth = $provider->getCryptocurrency('ETH');  
   // $ada = $provider->getCryptocurrency('ADA');
   ```

2. **Increase Cache TTL**:
   ```php
   'cache' => [
       'ttl' => [
           'cryptocurrency_quotes' => 300, // 5 minutes instead of 1 minute
       ],
   ],
   ```

3. **Consider Plan Upgrade**:
   - Basic/Hobbyist: 30 calls/minute
   - Startup/Standard/Professional: 60 calls/minute
   - Enterprise: 120 calls/minute

## Credit Management Problems

### Error: "Insufficient Credits" (402 Payment Required)

**Symptoms:**
```json
{
    "status": {
        "error_code": 1020,
        "error_message": "You've exceeded your API Key's monthly request volume rate limit"
    }
}
```

**Immediate Solutions:**
1. **Check Credit Usage**:
   ```php
   $creditInfo = $provider->getCreditUsage();
   
   echo "Credits used: " . $creditInfo['credits_used'];
   echo "Credits remaining: " . $creditInfo['credits_remaining'];
   echo "Reset date: " . $creditInfo['reset_date'];
   ```

2. **Enable Cache-Only Mode**:
   ```php
   // Temporarily disable API calls, use cached data only
   class EmergencyCacheOnlyMode
   {
       public function enableCacheOnlyMode()
       {
           config(['coinmarketcap.emergency_cache_only' => true]);
       }
       
       public function getCryptocurrency($symbol)
       {
           $cacheKey = "coinmarketcap_quotes_{$symbol}";
           
           if (config('coinmarketcap.emergency_cache_only')) {
               return Cache::get($cacheKey);
           }
           
           return $this->provider->getCryptocurrency($symbol);
       }
   }
   ```

**Preventive Solutions:**
1. **Implement Credit Monitoring**:
   ```php
   class CreditMonitor
   {
       public function checkCreditThreshold()
       {
           $usage = $this->getCreditUsage();
           $threshold = config('coinmarketcap.credits.warning_threshold', 0.8);
           
           if ($usage['percentage'] > $threshold) {
               // Send alert
               Mail::to(config('alerts.admin_email'))
                   ->send(new CreditThresholdAlert($usage));
               
               // Log warning
               Log::warning('Credit threshold exceeded', $usage);
               
               // Consider reducing cache TTL to preserve credits
               $this->extendCacheTTL();
           }
       }
       
       protected function extendCacheTTL()
       {
           $multiplier = 2; // Double cache times
           
           $currentTTLs = config('coinmarketcap.cache.ttl');
           $extendedTTLs = array_map(fn($ttl) => $ttl * $multiplier, $currentTTLs);
           
           config(['coinmarketcap.cache.ttl' => $extendedTTLs]);
       }
   }
   ```

2. **Implement Credit Budgeting**:
   ```php
   class CreditBudgetManager
   {
       public function allocateDailyBudget()
       {
           $monthlyCredits = config('coinmarketcap.plan.credits_per_month');
           $daysInMonth = now()->daysInMonth;
           $dailyBudget = $monthlyCredits / $daysInMonth;
           
           return [
               'daily_budget' => $dailyBudget,
               'hourly_budget' => $dailyBudget / 24,
               'remaining_today' => $this->getRemainingDailyBudget(),
           ];
       }
       
       public function canMakeRequest($creditCost)
       {
           $budget = $this->allocateDailyBudget();
           return $budget['remaining_today'] >= $creditCost;
       }
   }
   ```

### Issue: Unexpectedly High Credit Usage

**Diagnosis Steps:**
1. **Enable Credit Logging**:
   ```php
   'logging' => [
       'log_credits' => true,
       'level' => 'debug',
   ],
   ```

2. **Analyze Usage Patterns**:
   ```php
   class CreditUsageAnalyzer
   {
       public function analyzeUsage($days = 7)
       {
           $logs = $this->getCreditLogs($days);
           
           return [
               'total_credits' => $logs->sum('credits'),
               'average_per_day' => $logs->sum('credits') / $days,
               'top_endpoints' => $logs->groupBy('endpoint')
                   ->map(fn($group) => $group->sum('credits'))
                   ->sortDesc(),
               'peak_hours' => $logs->groupBy('hour')
                   ->map(fn($group) => $group->sum('credits'))
                   ->sortDesc(),
           ];
       }
   }
   ```

## Caching Issues

### Issue: Stale Data Being Served

**Symptoms:**
- Old prices displayed
- Outdated market information
- Cache not updating

**Solutions:**
1. **Verify Cache Configuration**:
   ```php
   // Check cache store
   dd(config('cache.default'));
   dd(config('coinmarketcap.cache.store'));
   
   // Test cache functionality
   Cache::put('test_key', 'test_value', 60);
   dd(Cache::get('test_key'));
   ```

2. **Clear Specific Cache Keys**:
   ```php
   // Clear all CoinMarketCap cache
   $prefix = config('coinmarketcap.cache.prefix', 'coinmarketcap');
   
   $keys = Cache::getRedis()->keys($prefix . '*');
   foreach ($keys as $key) {
       Cache::forget(str_replace($prefix . ':', '', $key));
   }
   ```

3. **Debug Cache Key Generation**:
   ```php
   class CacheKeyDebugger
   {
       public function generateKey($endpoint, $params)
       {
           $key = 'coinmarketcap_' . $endpoint . '_' . md5(json_encode($params));
           
           Log::debug('Cache key generated', [
               'endpoint' => $endpoint,
               'params' => $params,
               'key' => $key,
               'exists' => Cache::has($key),
               'ttl' => Cache::getStore()->getRedis()->ttl($key),
           ]);
           
           return $key;
       }
   }
   ```

### Issue: Cache Not Working

**Common Causes & Solutions:**

1. **Cache Store Issues**:
   ```php
   // Test different cache stores
   Cache::store('file')->put('test', 'value');
   Cache::store('redis')->put('test', 'value');
   Cache::store('database')->put('test', 'value');
   ```

2. **Permission Issues**:
   ```bash
   # Check storage permissions
   ls -la storage/framework/cache/
   
   # Fix permissions
   chmod -R 775 storage/framework/cache/
   chown -R www-data:www-data storage/framework/cache/
   ```

3. **Redis Connection Issues**:
   ```php
   // Test Redis connection
   try {
       $redis = Redis::connection();
       $redis->ping();
       echo "Redis connected successfully";
   } catch (Exception $e) {
       echo "Redis error: " . $e->getMessage();
   }
   ```

## Performance Problems

### Issue: Slow API Response Times

**Symptoms:**
- Requests taking >5 seconds
- Timeouts in production
- Poor user experience

**Diagnosis:**
1. **Enable Performance Logging**:
   ```php
   class PerformanceLogger
   {
       public function logRequest($endpoint, $duration, $cacheHit = false)
       {
           Log::info('API Performance', [
               'endpoint' => $endpoint,
               'duration_ms' => $duration,
               'cache_hit' => $cacheHit,
               'memory_usage' => memory_get_usage(true),
               'peak_memory' => memory_get_peak_usage(true),
           ]);
       }
   }
   ```

2. **Measure Request Times**:
   ```php
   $start = microtime(true);
   $result = $provider->getCryptocurrency('BTC');
   $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
   
   if ($duration > 2000) { // Alert if > 2 seconds
       Log::warning('Slow API request', [
           'duration' => $duration,
           'endpoint' => 'getCryptocurrency',
       ]);
   }
   ```

**Solutions:**
1. **Optimize Cache Strategy**:
   ```php
   // Implement cache warming
   class CacheWarmingService
   {
       public function warmPopularData()
       {
           $popularSymbols = ['BTC', 'ETH', 'USDT', 'BNB'];
           
           // Warm cache in background job
           dispatch(function () use ($popularSymbols) {
               $provider = app(CoinMarketCapProvider::class);
               $provider->getCryptocurrencies($popularSymbols);
           });
       }
   }
   ```

2. **Use HTTP Client Optimization**:
   ```php
   // config/coinmarketcap.php
   'api' => [
       'timeout' => 15, // Reduce from 30 seconds
       'connect_timeout' => 5,
       'retry_times' => 2, // Reduce retries
   ],
   ```

3. **Implement Connection Pooling**:
   ```php
   use GuzzleHttp\Client;
   use GuzzleHttp\HandlerStack;
   
   $stack = HandlerStack::create();
   $client = new Client([
       'handler' => $stack,
       'timeout' => 15,
       'connect_timeout' => 5,
       'pool_size' => 10, // Connection pool
   ]);
   ```

### Issue: High Memory Usage

**Solutions:**
1. **Optimize Data Processing**:
   ```php
   // Process data in chunks
   public function processLargeDataset($data)
   {
       $chunkSize = 100;
       $chunks = array_chunk($data, $chunkSize);
       
       foreach ($chunks as $chunk) {
           $this->processChunk($chunk);
           
           // Free memory
           unset($chunk);
           
           // Force garbage collection
           if (function_exists('gc_collect_cycles')) {
               gc_collect_cycles();
           }
       }
   }
   ```

2. **Use Generators for Large Results**:
   ```php
   public function getLargeDatasetGenerator($symbols)
   {
       $batchSize = 50;
       $batches = array_chunk($symbols, $batchSize);
       
       foreach ($batches as $batch) {
           $results = $provider->getCryptocurrencies($batch);
           
           foreach ($results as $result) {
               yield $result;
           }
           
           // Free memory between batches
           unset($results);
       }
   }
   ```

## Data Quality Issues

### Issue: Missing or Null Data

**Common Causes:**
1. **Invalid Symbols**: Requesting non-existent cryptocurrencies
2. **Delisted Coins**: Cryptocurrencies removed from CoinMarketCap
3. **API Changes**: Endpoint structure changes

**Solutions:**
1. **Validate Symbols Before Requests**:
   ```php
   class SymbolValidator
   {
       protected $validSymbols;
       
       public function __construct()
       {
           $this->validSymbols = Cache::remember('valid_symbols', 86400, function () {
               return $this->provider->getCryptocurrencyMap()
                   ->pluck('symbol')
                   ->toArray();
           });
       }
       
       public function validateSymbols(array $symbols)
       {
           $invalid = array_diff($symbols, $this->validSymbols);
           
           if (!empty($invalid)) {
               Log::warning('Invalid symbols requested', ['symbols' => $invalid]);
           }
           
           return array_intersect($symbols, $this->validSymbols);
       }
   }
   ```

2. **Handle Missing Data Gracefully**:
   ```php
   public function getCryptocurrencyWithFallback($symbol)
   {
       try {
           $crypto = $provider->getCryptocurrency($symbol);
           
           if (empty($crypto->getCurrentPrice('USD'))) {
               // Try alternative data source or cached data
               return $this->getCachedData($symbol);
           }
           
           return $crypto;
       } catch (Exception $e) {
           Log::warning('Failed to fetch cryptocurrency', [
               'symbol' => $symbol,
               'error' => $e->getMessage(),
           ]);
           
           return $this->getDefaultCryptocurrencyData($symbol);
       }
   }
   ```

### Issue: Price Discrepancies

**Causes:**
- Different update frequencies
- Cache timing issues  
- Market volatility during updates

**Solutions:**
1. **Implement Price Validation**:
   ```php
   class PriceValidator
   {
       public function validatePrice($symbol, $newPrice, $previousPrice)
       {
           if ($previousPrice === null) {
               return true; // First price, accept it
           }
           
           $changePercent = abs(($newPrice - $previousPrice) / $previousPrice) * 100;
           $threshold = 50; // 50% change threshold
           
           if ($changePercent > $threshold) {
               Log::warning('Suspicious price change detected', [
                   'symbol' => $symbol,
                   'old_price' => $previousPrice,
                   'new_price' => $newPrice,
                   'change_percent' => $changePercent,
               ]);
               
               // Don't update price, keep previous value
               return false;
           }
           
           return true;
       }
   }
   ```

## Configuration Problems

### Issue: Environment Variables Not Loading

**Solutions:**
1. **Check .env File Location**:
   ```bash
   # Ensure .env is in Laravel root directory
   ls -la .env
   ```

2. **Validate .env Syntax**:
   ```bash
   # Check for common syntax errors
   grep COINMARKETCAP .env
   
   # Test with simple PHP script
   php -r "
   require 'vendor/autoload.php';
   \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
   \$dotenv->load();
   echo getenv('COINMARKETCAP_API_KEY');
   "
   ```

3. **Clear Configuration Cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Issue: Configuration Not Taking Effect

**Debug Configuration Loading**:
```php
// Create a debug route or command
Route::get('/debug-config', function () {
    return [
        'config_loaded' => config('coinmarketcap') ? 'YES' : 'NO',
        'api_key' => config('coinmarketcap.api.key') ? 'SET' : 'NOT SET',
        'plan' => config('coinmarketcap.plan.type'),
        'cache_enabled' => config('coinmarketcap.cache.enabled') ? 'YES' : 'NO',
        'env_api_key' => env('COINMARKETCAP_API_KEY') ? 'SET' : 'NOT SET',
    ];
});
```

## Debugging Techniques

### Enable Debug Mode

1. **Application Debug**:
   ```env
   APP_DEBUG=true
   APP_LOG_LEVEL=debug
   ```

2. **Package-Specific Debugging**:
   ```php
   'logging' => [
       'enabled' => true,
       'level' => 'debug',
       'log_requests' => true,
       'log_responses' => true,
       'log_credits' => true,
   ],
   ```

### Debug HTTP Requests

```php
use Illuminate\Support\Facades\Http;

// Log all HTTP requests
Http::macro('debugCoinMarketCap', function () {
    return Http::withMiddleware(
        Middleware::mapRequest(function (RequestInterface $request) {
            Log::debug('CoinMarketCap Request', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body' => (string) $request->getBody(),
            ]);
            
            return $request;
        })
    )->withMiddleware(
        Middleware::mapResponse(function (ResponseInterface $response) {
            Log::debug('CoinMarketCap Response', [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => (string) $response->getBody(),
            ]);
            
            return $response;
        })
    );
});
```

### Create Debug Commands

```php
// app/Console/Commands/DebugCoinMarketCap.php
class DebugCoinMarketCapCommand extends Command
{
    protected $signature = 'coinmarketcap:debug {symbol?}';
    protected $description = 'Debug CoinMarketCap integration';
    
    public function handle()
    {
        $symbol = $this->argument('symbol') ?? 'BTC';
        
        $this->info('=== CoinMarketCap Debug Information ===');
        
        // Test configuration
        $this->testConfiguration();
        
        // Test API connectivity
        $this->testApiConnectivity($symbol);
        
        // Test caching
        $this->testCaching();
        
        // Display credit usage
        $this->displayCreditUsage();
    }
    
    protected function testConfiguration()
    {
        $this->info('Configuration Test:');
        $this->line('API Key: ' . (config('coinmarketcap.api.key') ? '✅ Set' : '❌ Not set'));
        $this->line('Plan: ' . config('coinmarketcap.plan.type'));
        $this->line('Cache Enabled: ' . (config('coinmarketcap.cache.enabled') ? '✅ Yes' : '❌ No'));
    }
}
```

## Error Code Reference

### CoinMarketCap API Error Codes

| Code | Message | Cause | Solution |
|------|---------|--------|----------|
| 400 | Bad Request | Invalid parameters | Check request parameters |
| 401 | Unauthorized | Invalid API key | Verify API key configuration |
| 402 | Payment Required | Credit limit exceeded | Upgrade plan or optimize usage |
| 403 | Forbidden | Access denied | Check API key permissions |
| 429 | Too Many Requests | Rate limit exceeded | Implement rate limiting |
| 500 | Internal Server Error | CoinMarketCap server issue | Retry request later |

### Package-Specific Errors

| Error | Cause | Solution |
|--------|--------|----------|
| `CoinMarketCapException` | General package error | Check logs for details |
| `AuthenticationException` | API key issues | Verify API key configuration |
| `RateLimitException` | Rate limiting | Implement backoff strategy |
| `CreditLimitException` | Credit exhaustion | Monitor credit usage |
| `CacheException` | Cache-related errors | Check cache configuration |

### Quick Diagnostic Script

```php
// Create a comprehensive diagnostic script
class CoinMarketCapDiagnostics
{
    public function runDiagnostics()
    {
        $results = [
            'configuration' => $this->testConfiguration(),
            'connectivity' => $this->testConnectivity(),
            'authentication' => $this->testAuthentication(),
            'caching' => $this->testCaching(),
            'performance' => $this->testPerformance(),
        ];
        
        return $results;
    }
    
    protected function testConfiguration()
    {
        return [
            'api_key_set' => !empty(config('coinmarketcap.api.key')),
            'plan_configured' => !empty(config('coinmarketcap.plan.type')),
            'cache_enabled' => config('coinmarketcap.cache.enabled'),
            'logging_enabled' => config('coinmarketcap.logging.enabled'),
        ];
    }
    
    protected function testConnectivity()
    {
        try {
            $response = Http::timeout(10)->get('https://pro-api.coinmarketcap.com');
            return [
                'status' => 'success',
                'response_time' => $response->handlerStats()['total_time'] ?? 'unknown',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

---

**Still Having Issues?**

If you're still experiencing problems after trying these solutions:

1. **Check the logs**: Look for error messages in `storage/logs/laravel.log`
2. **Enable debug mode**: Set `APP_DEBUG=true` for detailed error information
3. **Update the package**: Ensure you're using the latest version
4. **Check documentation**: Review the [API Reference](API.md) for correct usage
5. **Contact support**: Create an issue on GitHub with detailed error information

**Pro Tip**: Always test changes in a development environment before applying them to production!