<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an API error occurs with CoinMarketCap.
 *
 * Tracks detailed error information including error codes, messages,
 * retry attempts, and wasted credits for comprehensive error monitoring.
 */
class ApiError
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The endpoint where the error occurred.
     */
    public readonly string $endpoint;

    /**
     * HTTP status code or CoinMarketCap error code.
     */
    public readonly int $errorCode;

    /**
     * Error message from the API response.
     */
    public readonly string $errorMessage;

    /**
     * Number of retry attempts made.
     */
    public readonly int $retryCount;

    /**
     * Number of credits wasted due to the error.
     */
    public readonly int $creditsWasted;

    /**
     * The HTTP method that was used.
     */
    public readonly string $method;

    /**
     * Request parameters that were sent.
     */
    public readonly array $requestParameters;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * Response time before the error occurred.
     */
    public readonly float $responseTime;

    /**
     * Timestamp when the error occurred.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
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
    public function __construct(
        string $endpoint,
        int $errorCode,
        string $errorMessage,
        int $retryCount,
        int $creditsWasted,
        string $method = 'GET',
        array $requestParameters = [],
        string $planType = 'basic',
        float $responseTime = 0.0
    ) {
        $this->endpoint = $endpoint;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->retryCount = $retryCount;
        $this->creditsWasted = $creditsWasted;
        $this->method = $method;
        $this->requestParameters = $requestParameters;
        $this->planType = $planType;
        $this->responseTime = $responseTime;
        $this->timestamp = new \DateTimeImmutable();
    }
}