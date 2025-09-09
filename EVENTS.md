# CoinMarketCap Events System

This documentation describes the comprehensive event system implemented for the Laravel CoinMarketCap package, providing complete observability of API usage, credit consumption, and performance metrics.

## Overview

The event system provides real-time monitoring and analytics for all CoinMarketCap API operations through Laravel's event system. It tracks:

- API call details with credit consumption
- Credit usage warnings and limits
- Rate limiting incidents
- API errors and retry attempts
- Performance metrics and analytics

## Events

### Core Events

#### `ApiCallMade`
Fired when an API call is successfully made to CoinMarketCap.

```php
use Convertain\CoinMarketCap\Events\ApiCallMade;

// Event properties
$event->endpoint;         // string - API endpoint called
$event->method;          // string - HTTP method (GET, POST, etc.)
$event->creditsConsumed; // int - Credits consumed by this call
$event->responseTime;    // float - Response time in milliseconds
$event->cacheHit;        // bool - Whether response was from cache
$event->planType;        // string - Subscription plan type
$event->parameters;      // array - Request parameters
$event->timestamp;       // DateTimeInterface - When call was made
```

#### `CreditConsumed`
Fired when credits are consumed from API calls.

```php
use Convertain\CoinMarketCap\Events\CreditConsumed;

// Event properties
$event->creditsUsed;      // int - Credits used in this call
$event->creditsRemaining; // int - Credits remaining
$event->percentageUsed;   // float - Percentage of total credits used
$event->planType;         // string - Subscription plan type
$event->endpoint;         // string - Endpoint that consumed credits
$event->totalCredits;     // int - Total credits available
```

#### `CreditWarning`
Fired when credit usage approaches warning thresholds.

```php
use Convertain\CoinMarketCap\Events\CreditWarning;

// Event properties
$event->thresholdPercent;     // float - Threshold that triggered warning
$event->creditsRemaining;     // int - Credits remaining
$event->estimatedDaysLeft;    // int - Estimated days left at current rate
$event->planType;             // string - Subscription plan type
$event->currentUsagePercent;  // float - Current usage percentage
$event->totalCredits;         // int - Total credits for plan
$event->averageDailyUsage;    // float - Average daily consumption
```

#### `RateLimitHit`
Fired when API rate limits are encountered.

```php
use Convertain\CoinMarketCap\Events\RateLimitHit;

// Event properties
$event->limitType;      // string - Type of limit (per_minute, per_day)
$event->resetTime;      // DateTimeInterface - When limit resets
$event->endpoint;       // string - Endpoint that hit the limit
$event->limit;          // int - Rate limit value
$event->requestsMade;   // int - Requests made in current window
$event->planType;       // string - Subscription plan type
$event->windowSeconds;  // int - Time window for the limit
```

#### `ApiError`
Fired when API errors occur.

```php
use Convertain\CoinMarketCap\Events\ApiError;

// Event properties
$event->endpoint;           // string - Endpoint where error occurred
$event->errorCode;          // int - HTTP or API error code
$event->errorMessage;       // string - Error message
$event->retryCount;         // int - Number of retry attempts
$event->creditsWasted;      // int - Credits wasted due to error
$event->method;             // string - HTTP method
$event->requestParameters;  // array - Request parameters
$event->planType;          // string - Subscription plan type
$event->responseTime;      // float - Response time before error
```

#### `RetryAttempt`
Fired when API retry attempts are made.

```php
use Convertain\CoinMarketCap\Events\RetryAttempt;

// Event properties
$event->endpoint;               // string - Endpoint being retried
$event->attemptNumber;          // int - Current attempt number
$event->maxAttempts;            // int - Maximum retry attempts
$event->delayMs;                // int - Delay before retry (ms)
$event->originalErrorCode;      // int - Original error code
$event->originalErrorMessage;   // string - Original error message
$event->method;                 // string - HTTP method
$event->requestParameters;      // array - Request parameters
$event->planType;              // string - Subscription plan type
```

## Event Monitor Service

The `EventMonitor` service provides centralized event management and analytics.

### Usage

```php
use Convertain\CoinMarketCap\Monitoring\EventMonitor;

// Inject or resolve from container
$monitor = app(EventMonitor::class);

// Fire events programmatically
$monitor->apiCallMade(
    '/cryptocurrency/quotes/latest',
    'GET',
    1, // credits consumed
    250.5, // response time ms
    false, // cache hit
    'basic', // plan type
    ['symbol' => 'BTC'] // parameters
);

// Get analytics
$analytics = $monitor->getAnalytics(24); // Last 24 hours
```

### Analytics Data

The analytics system provides comprehensive metrics:

```php
$analytics = $monitor->getAnalytics();

// Returns array with:
[
    'time_window_hours' => 24,
    'total_events' => 150,
    'api_calls' => 120,
    'credits_consumed' => 180,
    'errors' => 2,
    'retries' => 1,
    'rate_limits' => 0,
    'warnings' => 1,
    'average_response_time' => 245.8,
    'cache_hit_rate' => 65.0,
    'error_rate' => 1.67,
    'most_used_endpoints' => [
        '/cryptocurrency/quotes/latest' => 85,
        '/cryptocurrency/listings/latest' => 35
    ],
    'error_breakdown' => [
        500 => 1,
        429 => 1
    ]
]
```

## Event Listeners

### Built-in Listeners

#### `LogApiCall`
Logs all API calls with detailed context.

```php
// Automatically registered, logs:
// - API endpoint and method
// - Credits consumed and response time
// - Cache hit status
// - Slow query warnings (>5s)
```

#### `LogCreditWarning`
Logs credit warnings with severity levels.

```php
// Logs based on usage percentage:
// - 95%+ usage: CRITICAL level
// - 80%+ usage: WARNING level
// - Below 80%: NOTICE level
```

#### `LogApiError`
Logs API errors with full context.

```php
// Logs errors with:
// - Error codes and messages
// - Request context
// - Credit waste tracking
// - Retry attempt information
```

### Custom Listeners

Create custom listeners to extend functionality:

```php
<?php

namespace App\Listeners;

use Convertain\CoinMarketCap\Events\CreditWarning;
use App\Services\NotificationService;

class SendCreditWarningNotification
{
    public function __construct(
        private NotificationService $notifications
    ) {}

    public function handle(CreditWarning $event): void
    {
        if ($event->currentUsagePercent >= 90) {
            $this->notifications->sendAlert(
                "CoinMarketCap credits at {$event->currentUsagePercent}% usage"
            );
        }
    }
}
```

Register in your `EventServiceProvider`:

```php
protected $listen = [
    CreditWarning::class => [
        SendCreditWarningNotification::class,
    ],
];
```

## Configuration

Events are configured in `config/coinmarketcap.php`:

```php
'events' => [
    'enabled' => env('COINMARKETCAP_EVENTS_ENABLED', true),
    'dispatch' => [
        'api_call_made' => true,
        'credit_consumed' => true,
        'credit_warning' => true,
        'rate_limit_hit' => true,
        'api_error' => true,
    ],
],
```

## Integration Examples

### Laravel Horizon/Queue Integration

```php
use Convertain\CoinMarketCap\Events\ApiError;

// In a listener
public function handle(ApiError $event): void
{
    // Queue email notification for critical errors
    if ($event->errorCode >= 500) {
        SendApiErrorEmail::dispatch($event);
    }
}
```

### Metrics Collection

```php
use Convertain\CoinMarketCap\Events\ApiCallMade;
use App\Services\MetricsCollector;

class CollectApiMetrics
{
    public function handle(ApiCallMade $event): void
    {
        MetricsCollector::increment('coinmarketcap.api.calls', [
            'endpoint' => $event->endpoint,
            'plan' => $event->planType,
            'cache_hit' => $event->cacheHit ? 'true' : 'false',
        ]);

        MetricsCollector::histogram('coinmarketcap.api.response_time', 
            $event->responseTime
        );
    }
}
```

### Database Logging

```php
use Convertain\CoinMarketCap\Events\CreditConsumed;
use App\Models\CreditUsageLog;

class LogCreditUsage
{
    public function handle(CreditConsumed $event): void
    {
        CreditUsageLog::create([
            'endpoint' => $event->endpoint,
            'credits_used' => $event->creditsUsed,
            'credits_remaining' => $event->creditsRemaining,
            'percentage_used' => $event->percentageUsed,
            'plan_type' => $event->planType,
            'created_at' => $event->timestamp,
        ]);
    }
}
```

## Best Practices

1. **Selective Event Listening**: Only listen to events you need to avoid performance overhead.

2. **Async Processing**: For heavy operations, queue event processing:
   ```php
   class HeavyEventProcessor implements ShouldQueue
   {
       public function handle(ApiCallMade $event): void
       {
           // Heavy processing in background
       }
   }
   ```

3. **Event Storage**: Consider persisting critical events to database for long-term analytics.

4. **Monitoring Alerts**: Set up alerts for critical thresholds:
   - Credit usage > 90%
   - Error rate > 5%
   - Average response time > 2s

5. **Performance Impact**: Monitor the performance impact of event listeners in production.

## Troubleshooting

### Events Not Firing

1. Check that events are enabled in config:
   ```php
   config('coinmarketcap.events.enabled') // Should be true
   ```

2. Verify event listeners are registered in service provider.

3. Check Laravel's event system is working:
   ```php
   Event::fake();
   // Make API call
   Event::assertDispatched(ApiCallMade::class);
   ```

### Missing Analytics Data

1. Ensure `EventMonitor` is storing events:
   ```php
   config('coinmarketcap.events.store_events') // Should be true
   ```

2. Check memory limits for in-memory event storage.

3. Consider implementing persistent storage for analytics.

### Performance Issues

1. Review listener complexity and consider queuing heavy operations.
2. Monitor memory usage with event storage enabled.
3. Adjust `max_stored_events` configuration as needed.

## API Reference

See the individual class documentation for complete API references:

- `ApiCallMade` - API call tracking
- `CreditConsumed` - Credit consumption tracking
- `CreditWarning` - Credit threshold warnings
- `RateLimitHit` - Rate limit monitoring
- `ApiError` - Error tracking
- `RetryAttempt` - Retry attempt logging
- `EventMonitor` - Central monitoring service