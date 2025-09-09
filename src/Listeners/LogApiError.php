<?php

namespace Convertain\CoinMarketCap\Listeners;

use Convertain\CoinMarketCap\Events\ApiError;
use Illuminate\Support\Facades\Log;

/**
 * Listener for API error events.
 *
 * Logs API errors with detailed context for debugging and monitoring.
 */
class LogApiError
{
    /**
     * Handle the API error event.
     *
     * @param ApiError $event The API error event
     */
    public function handle(ApiError $event): void
    {
        $context = [
            'endpoint' => $event->endpoint,
            'method' => $event->method,
            'error_code' => $event->errorCode,
            'error_message' => $event->errorMessage,
            'retry_count' => $event->retryCount,
            'credits_wasted' => $event->creditsWasted,
            'plan_type' => $event->planType,
            'response_time_ms' => $event->responseTime,
            'request_parameters' => $event->requestParameters,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
        ];

        $level = $this->getLogLevel($event->errorCode);
        
        match ($level) {
            'error' => Log::error('CoinMarketCap API error occurred', $context),
            'warning' => Log::warning('CoinMarketCap API warning', $context),
            default => Log::notice('CoinMarketCap API notice', $context),
        };

        // Log credit waste
        if ($event->creditsWasted > 0) {
            Log::warning('CoinMarketCap credits wasted due to error', [
                'credits_wasted' => $event->creditsWasted,
                'endpoint' => $event->endpoint,
                'error_code' => $event->errorCode,
            ]);
        }
    }

    /**
     * Determine log level based on error code.
     *
     * @param int $errorCode The error code
     * @return string Log level
     */
    private function getLogLevel(int $errorCode): string
    {
        return match (true) {
            $errorCode >= 500 => 'error',
            $errorCode >= 400 && $errorCode < 500 => 'warning',
            default => 'notice',
        };
    }
}