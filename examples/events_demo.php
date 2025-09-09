<?php

/**
 * Simple example demonstrating CoinMarketCap events system integration.
 * 
 * This example shows how to use the EventMonitor service and events in a Laravel application.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Convertain\CoinMarketCap\Events\ApiCallMade;
use Convertain\CoinMarketCap\Events\CreditWarning;
use Convertain\CoinMarketCap\Events\ApiError;
use Convertain\CoinMarketCap\Monitoring\EventMonitor;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

// Simulate Laravel container and event dispatcher
$container = new Container();
$events = new Dispatcher($container);

// Create event monitor
$monitor = new EventMonitor($events, [
    'enabled' => true,
    'store_events' => true,
    'max_stored_events' => 100,
]);

// Example: Simple event listener
$events->listen(ApiCallMade::class, function (ApiCallMade $event) {
    echo "API Call Made: {$event->endpoint} ({$event->method})\n";
    echo "  - Credits: {$event->creditsConsumed}\n";
    echo "  - Response Time: {$event->responseTime}ms\n";
    echo "  - Cache Hit: " . ($event->cacheHit ? 'Yes' : 'No') . "\n";
    echo "  - Plan: {$event->planType}\n\n";
});

$events->listen(CreditWarning::class, function (CreditWarning $event) {
    echo "⚠️  CREDIT WARNING: {$event->currentUsagePercent}% usage!\n";
    echo "  - Credits Remaining: {$event->creditsRemaining}\n";
    echo "  - Days Left: {$event->estimatedDaysLeft}\n\n";
});

$events->listen(ApiError::class, function (ApiError $event) {
    echo "❌ API ERROR: {$event->endpoint}\n";
    echo "  - Error Code: {$event->errorCode}\n";
    echo "  - Error Message: {$event->errorMessage}\n";
    echo "  - Credits Wasted: {$event->creditsWasted}\n\n";
});

// Demonstrate event firing
echo "=== CoinMarketCap Events System Demo ===\n\n";

// Simulate successful API calls
$monitor->apiCallMade(
    '/cryptocurrency/quotes/latest',
    'GET',
    1,
    245.5,
    false,
    'basic',
    ['symbol' => 'BTC']
);

$monitor->apiCallMade(
    '/cryptocurrency/info',
    'GET',
    0, // No credits for cache hit
    45.2,
    true, // Cache hit
    'basic',
    ['id' => '1']
);

// Simulate credit warning
$monitor->creditWarning(
    80.0,  // 80% threshold
    1500,  // Credits remaining
    3,     // 3 days left
    'basic',
    85.5,  // Current usage 85.5%
    10000, // Total credits
    500.0  // Daily average
);

// Simulate API error
$monitor->apiError(
    '/cryptocurrency/quotes/latest',
    500,
    'Internal Server Error',
    2, // Retry count
    2, // Credits wasted
    'GET',
    ['symbol' => 'INVALID'],
    'basic',
    1500.0
);

// Show analytics
echo "=== Analytics Report ===\n";
$analytics = $monitor->getAnalytics(24);
foreach ($analytics as $key => $value) {
    if (is_array($value)) {
        echo "$key: " . json_encode($value) . "\n";
    } else {
        echo "$key: $value\n";
    }
}

echo "\n✅ Events system demonstration completed successfully!\n";
echo "\nThe CoinMarketCap events system provides:\n";
echo "- Real-time API call monitoring\n";
echo "- Credit usage tracking and warnings\n";
echo "- Error tracking with context\n";
echo "- Performance analytics\n";
echo "- Full Laravel event system integration\n";