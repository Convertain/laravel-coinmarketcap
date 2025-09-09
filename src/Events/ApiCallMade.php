<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an API call is made to CoinMarketCap.
 *
 * Tracks comprehensive API call details including credit consumption,
 * response time, cache effectiveness, and plan information.
 */
class ApiCallMade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The API endpoint called.
     */
    public readonly string $endpoint;

    /**
     * The HTTP method used.
     */
    public readonly string $method;

    /**
     * Number of credits consumed by this call.
     */
    public readonly int $creditsConsumed;

    /**
     * Response time in milliseconds.
     */
    public readonly float $responseTime;

    /**
     * Whether the response was served from cache.
     */
    public readonly bool $cacheHit;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * Additional request parameters.
     */
    public readonly array $parameters;

    /**
     * Timestamp when the API call was made.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
     *
     * @param string $endpoint The API endpoint called
     * @param string $method The HTTP method used
     * @param int $creditsConsumed Number of credits consumed
     * @param float $responseTime Response time in milliseconds
     * @param bool $cacheHit Whether the response was served from cache
     * @param string $planType The subscription plan type
     * @param array $parameters Additional request parameters
     */
    public function __construct(
        string $endpoint,
        string $method,
        int $creditsConsumed,
        float $responseTime,
        bool $cacheHit,
        string $planType,
        array $parameters = []
    ) {
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->creditsConsumed = $creditsConsumed;
        $this->responseTime = $responseTime;
        $this->cacheHit = $cacheHit;
        $this->planType = $planType;
        $this->parameters = $parameters;
        $this->timestamp = new \DateTimeImmutable();
    }
}