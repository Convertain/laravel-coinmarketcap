<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an API retry attempt is made.
 *
 * Tracks retry attempts for failed API calls, providing insight into
 * API reliability and retry strategy effectiveness.
 */
class RetryAttempt
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The endpoint being retried.
     */
    public readonly string $endpoint;

    /**
     * Current attempt number (1-based).
     */
    public readonly int $attemptNumber;

    /**
     * Maximum number of retry attempts configured.
     */
    public readonly int $maxAttempts;

    /**
     * Delay before this retry attempt (in milliseconds).
     */
    public readonly int $delayMs;

    /**
     * The original error code that triggered the retry.
     */
    public readonly int $originalErrorCode;

    /**
     * The original error message that triggered the retry.
     */
    public readonly string $originalErrorMessage;

    /**
     * The HTTP method being retried.
     */
    public readonly string $method;

    /**
     * Request parameters for the retry.
     */
    public readonly array $requestParameters;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * Timestamp when the retry attempt was made.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
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
    public function __construct(
        string $endpoint,
        int $attemptNumber,
        int $maxAttempts,
        int $delayMs,
        int $originalErrorCode,
        string $originalErrorMessage,
        string $method = 'GET',
        array $requestParameters = [],
        string $planType = 'basic'
    ) {
        $this->endpoint = $endpoint;
        $this->attemptNumber = $attemptNumber;
        $this->maxAttempts = $maxAttempts;
        $this->delayMs = $delayMs;
        $this->originalErrorCode = $originalErrorCode;
        $this->originalErrorMessage = $originalErrorMessage;
        $this->method = $method;
        $this->requestParameters = $requestParameters;
        $this->planType = $planType;
        $this->timestamp = new \DateTimeImmutable();
    }
}