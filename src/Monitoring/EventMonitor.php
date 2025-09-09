<?php

namespace Convertain\CoinMarketCap\Monitoring;

use Convertain\CoinMarketCap\Events\ApiCallMade;
use Convertain\CoinMarketCap\Events\ApiError;
use Convertain\CoinMarketCap\Events\CreditConsumed;
use Convertain\CoinMarketCap\Events\CreditWarning;
use Convertain\CoinMarketCap\Events\RateLimitHit;
use Convertain\CoinMarketCap\Events\RetryAttempt;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * Event monitoring service for CoinMarketCap API operations.
 *
 * Provides centralized event monitoring, analytics, and alerting
 * for API usage, credit consumption, and performance metrics.
 */
class EventMonitor
{
    /**
     * The event dispatcher instance.
     */
    private Dispatcher $events;

    /**
     * Configuration for event monitoring.
     */
    private array $config;

    /**
     * In-memory event storage for analytics.
     */
    private array $eventStore = [];

    /**
     * Create a new event monitor instance.
     *
     * @param Dispatcher $events The event dispatcher
     * @param array $config Configuration for event monitoring
     */
    public function __construct(Dispatcher $events, array $config = [])
    {
        $this->events = $events;
        $this->config = array_merge([
            'enabled' => true,
            'store_events' => true,
            'max_stored_events' => 1000,
            'analytics_window_hours' => 24,
        ], $config);
    }

    /**
     * Fire an API call made event.
     *
     * @param string $endpoint The API endpoint called
     * @param string $method The HTTP method used
     * @param int $creditsConsumed Number of credits consumed
     * @param float $responseTime Response time in milliseconds
     * @param bool $cacheHit Whether the response was served from cache
     * @param string $planType The subscription plan type
     * @param array $parameters Additional request parameters
     */
    public function apiCallMade(
        string $endpoint,
        string $method,
        int $creditsConsumed,
        float $responseTime,
        bool $cacheHit,
        string $planType,
        array $parameters = []
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new ApiCallMade(
            $endpoint,
            $method,
            $creditsConsumed,
            $responseTime,
            $cacheHit,
            $planType,
            $parameters
        );

        $this->dispatchEvent($event);
    }

    /**
     * Fire a credit consumed event.
     *
     * @param int $creditsUsed Number of credits used
     * @param int $creditsRemaining Number of credits remaining
     * @param float $percentageUsed Percentage of total credits used
     * @param string $planType The subscription plan type
     * @param string $endpoint The endpoint that consumed the credits
     * @param int $totalCredits Total credits available for the plan
     */
    public function creditConsumed(
        int $creditsUsed,
        int $creditsRemaining,
        float $percentageUsed,
        string $planType,
        string $endpoint,
        int $totalCredits
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new CreditConsumed(
            $creditsUsed,
            $creditsRemaining,
            $percentageUsed,
            $planType,
            $endpoint,
            $totalCredits
        );

        $this->dispatchEvent($event);
    }

    /**
     * Fire a credit warning event.
     *
     * @param float $thresholdPercent The threshold percentage that triggered the warning
     * @param int $creditsRemaining Number of credits remaining
     * @param int $estimatedDaysLeft Estimated days left at current usage rate
     * @param string $planType The subscription plan type
     * @param float $currentUsagePercent Current usage percentage
     * @param int $totalCredits Total credits for the plan
     * @param float $averageDailyUsage Average daily credit consumption
     */
    public function creditWarning(
        float $thresholdPercent,
        int $creditsRemaining,
        int $estimatedDaysLeft,
        string $planType,
        float $currentUsagePercent,
        int $totalCredits,
        float $averageDailyUsage
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new CreditWarning(
            $thresholdPercent,
            $creditsRemaining,
            $estimatedDaysLeft,
            $planType,
            $currentUsagePercent,
            $totalCredits,
            $averageDailyUsage
        );

        $this->dispatchEvent($event);
    }

    /**
     * Fire a rate limit hit event.
     *
     * @param string $limitType The type of rate limit hit
     * @param \DateTimeInterface $resetTime When the rate limit will reset
     * @param string $endpoint The endpoint that hit the rate limit
     * @param int $limit Current rate limit for this type
     * @param int $requestsMade Number of requests made in current window
     * @param string $planType The subscription plan type
     * @param int $windowSeconds Time window for the rate limit
     */
    public function rateLimitHit(
        string $limitType,
        \DateTimeInterface $resetTime,
        string $endpoint,
        int $limit,
        int $requestsMade,
        string $planType,
        int $windowSeconds
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new RateLimitHit(
            $limitType,
            $resetTime,
            $endpoint,
            $limit,
            $requestsMade,
            $planType,
            $windowSeconds
        );

        $this->dispatchEvent($event);
    }

    /**
     * Fire an API error event.
     *
     * @param string $endpoint The endpoint where the error occurred
     * @param int $errorCode HTTP status code or CoinMarketCap error code
     * @param string $errorMessage Error message from the API response
     * @param int $retryCount Number of retry attempts made
     * @param int $creditsWasted Number of credits wasted due to the error
     * @param string $method The HTTP method that was used
     * @param array $requestParameters Request parameters that were sent
     * @param string $planType The subscription plan type
     * @param float $responseTime Response time before the error occurred
     */
    public function apiError(
        string $endpoint,
        int $errorCode,
        string $errorMessage,
        int $retryCount,
        int $creditsWasted,
        string $method = 'GET',
        array $requestParameters = [],
        string $planType = 'basic',
        float $responseTime = 0.0
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new ApiError(
            $endpoint,
            $errorCode,
            $errorMessage,
            $retryCount,
            $creditsWasted,
            $method,
            $requestParameters,
            $planType,
            $responseTime
        );

        $this->dispatchEvent($event);
    }

    /**
     * Fire a retry attempt event.
     *
     * @param string $endpoint The endpoint being retried
     * @param int $attemptNumber Current attempt number
     * @param int $maxAttempts Maximum number of retry attempts configured
     * @param int $delayMs Delay before this retry attempt in milliseconds
     * @param int $originalErrorCode The original error code that triggered the retry
     * @param string $originalErrorMessage The original error message that triggered the retry
     * @param string $method The HTTP method being retried
     * @param array $requestParameters Request parameters for the retry
     * @param string $planType The subscription plan type
     */
    public function retryAttempt(
        string $endpoint,
        int $attemptNumber,
        int $maxAttempts,
        int $delayMs,
        int $originalErrorCode,
        string $originalErrorMessage,
        string $method = 'GET',
        array $requestParameters = [],
        string $planType = 'basic'
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $event = new RetryAttempt(
            $endpoint,
            $attemptNumber,
            $maxAttempts,
            $delayMs,
            $originalErrorCode,
            $originalErrorMessage,
            $method,
            $requestParameters,
            $planType
        );

        $this->dispatchEvent($event);
    }

    /**
     * Get analytics data for the specified time window.
     *
     * @param int $hours Number of hours to look back (default: 24)
     * @return array Analytics data
     */
    public function getAnalytics(int $hours = 24): array
    {
        $cutoff = Carbon::now()->subHours($hours);
        $events = array_filter($this->eventStore, function ($event) use ($cutoff) {
            return $event['timestamp'] >= $cutoff;
        });

        $analytics = [
            'time_window_hours' => $hours,
            'total_events' => count($events),
            'api_calls' => 0,
            'credits_consumed' => 0,
            'errors' => 0,
            'retries' => 0,
            'rate_limits' => 0,
            'warnings' => 0,
            'average_response_time' => 0.0,
            'cache_hit_rate' => 0.0,
            'error_rate' => 0.0,
            'most_used_endpoints' => [],
            'error_breakdown' => [],
        ];

        $responseTimes = [];
        $cacheHits = 0;
        $totalCalls = 0;
        $endpointUsage = [];
        $errorCodes = [];

        foreach ($events as $event) {
            switch ($event['type']) {
                case 'api_call':
                    $analytics['api_calls']++;
                    $analytics['credits_consumed'] += $event['data']['credits_consumed'];
                    $responseTimes[] = $event['data']['response_time'];
                    if ($event['data']['cache_hit']) {
                        $cacheHits++;
                    }
                    $totalCalls++;
                    $endpointUsage[$event['data']['endpoint']] = ($endpointUsage[$event['data']['endpoint']] ?? 0) + 1;
                    break;
                case 'error':
                    $analytics['errors']++;
                    $errorCodes[$event['data']['error_code']] = ($errorCodes[$event['data']['error_code']] ?? 0) + 1;
                    break;
                case 'retry':
                    $analytics['retries']++;
                    break;
                case 'rate_limit':
                    $analytics['rate_limits']++;
                    break;
                case 'warning':
                    $analytics['warnings']++;
                    break;
            }
        }

        if (count($responseTimes) > 0) {
            $analytics['average_response_time'] = array_sum($responseTimes) / count($responseTimes);
        }

        if ($totalCalls > 0) {
            $analytics['cache_hit_rate'] = ($cacheHits / $totalCalls) * 100;
            $analytics['error_rate'] = ($analytics['errors'] / $totalCalls) * 100;
        }

        arsort($endpointUsage);
        $analytics['most_used_endpoints'] = array_slice($endpointUsage, 0, 10, true);

        arsort($errorCodes);
        $analytics['error_breakdown'] = $errorCodes;

        return $analytics;
    }

    /**
     * Clear stored events.
     */
    public function clearEvents(): void
    {
        $this->eventStore = [];
    }

    /**
     * Check if monitoring is enabled.
     */
    private function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Dispatch an event and optionally store it for analytics.
     *
     * @param object $event The event to dispatch
     */
    private function dispatchEvent(object $event): void
    {
        $this->events->dispatch($event);

        if ($this->config['store_events']) {
            $this->storeEvent($event);
        }
    }

    /**
     * Store event for analytics.
     *
     * @param object $event The event to store
     */
    private function storeEvent(object $event): void
    {
        $eventData = [
            'type' => $this->getEventType($event),
            'timestamp' => Carbon::now(),
            'data' => $this->extractEventData($event),
        ];

        $this->eventStore[] = $eventData;

        // Maintain max stored events limit
        $maxEvents = $this->config['max_stored_events'];
        if (count($this->eventStore) > $maxEvents) {
            $this->eventStore = array_slice($this->eventStore, -$maxEvents);
        }
    }

    /**
     * Get event type from event class.
     *
     * @param object $event The event
     * @return string Event type
     */
    private function getEventType(object $event): string
    {
        return match (get_class($event)) {
            ApiCallMade::class => 'api_call',
            CreditConsumed::class => 'credit_consumed',
            CreditWarning::class => 'warning',
            RateLimitHit::class => 'rate_limit',
            ApiError::class => 'error',
            RetryAttempt::class => 'retry',
            default => 'unknown',
        };
    }

    /**
     * Extract relevant data from event for storage.
     *
     * @param object $event The event
     * @return array Event data
     */
    private function extractEventData(object $event): array
    {
        return match (get_class($event)) {
            ApiCallMade::class => [
                'endpoint' => $event->endpoint,
                'method' => $event->method,
                'credits_consumed' => $event->creditsConsumed,
                'response_time' => $event->responseTime,
                'cache_hit' => $event->cacheHit,
                'plan_type' => $event->planType,
            ],
            CreditConsumed::class => [
                'credits_used' => $event->creditsUsed,
                'credits_remaining' => $event->creditsRemaining,
                'percentage_used' => $event->percentageUsed,
                'endpoint' => $event->endpoint,
                'plan_type' => $event->planType,
            ],
            CreditWarning::class => [
                'threshold_percent' => $event->thresholdPercent,
                'credits_remaining' => $event->creditsRemaining,
                'estimated_days_left' => $event->estimatedDaysLeft,
                'plan_type' => $event->planType,
            ],
            RateLimitHit::class => [
                'limit_type' => $event->limitType,
                'endpoint' => $event->endpoint,
                'limit' => $event->limit,
                'requests_made' => $event->requestsMade,
                'plan_type' => $event->planType,
            ],
            ApiError::class => [
                'endpoint' => $event->endpoint,
                'error_code' => $event->errorCode,
                'error_message' => $event->errorMessage,
                'retry_count' => $event->retryCount,
                'credits_wasted' => $event->creditsWasted,
                'plan_type' => $event->planType,
            ],
            RetryAttempt::class => [
                'endpoint' => $event->endpoint,
                'attempt_number' => $event->attemptNumber,
                'max_attempts' => $event->maxAttempts,
                'original_error_code' => $event->originalErrorCode,
                'plan_type' => $event->planType,
            ],
            default => [],
        };
    }
}