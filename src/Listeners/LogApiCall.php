<?php

namespace Convertain\CoinMarketCap\Listeners;

use Convertain\CoinMarketCap\Events\ApiCallMade;
use Illuminate\Support\Facades\Log;

/**
 * Listener for API call events.
 *
 * Logs API call information for monitoring and debugging purposes.
 */
class LogApiCall
{
    /**
     * Handle the API call made event.
     *
     * @param ApiCallMade $event The API call event
     */
    public function handle(ApiCallMade $event): void
    {
        $context = [
            'endpoint' => $event->endpoint,
            'method' => $event->method,
            'credits_consumed' => $event->creditsConsumed,
            'response_time_ms' => $event->responseTime,
            'cache_hit' => $event->cacheHit,
            'plan_type' => $event->planType,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
        ];

        if ($event->cacheHit) {
            Log::info('CoinMarketCap API call served from cache', $context);
        } else {
            Log::info('CoinMarketCap API call made', $context);
        }

        // Log slow queries
        if ($event->responseTime > 5000) { // 5 seconds
            Log::warning('CoinMarketCap API slow response', $context);
        }
    }
}