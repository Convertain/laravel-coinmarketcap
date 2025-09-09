<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a rate limit is hit on CoinMarketCap API calls.
 *
 * Tracks rate limiting information to help with request scheduling
 * and avoiding service disruptions.
 */
class RateLimitHit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The type of rate limit hit (per_minute, per_day).
     */
    public readonly string $limitType;

    /**
     * When the rate limit will reset.
     */
    public readonly \DateTimeInterface $resetTime;

    /**
     * The endpoint that hit the rate limit.
     */
    public readonly string $endpoint;

    /**
     * Current rate limit for this type.
     */
    public readonly int $limit;

    /**
     * Number of requests made in current window.
     */
    public readonly int $requestsMade;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * Time window for the rate limit (in seconds).
     */
    public readonly int $windowSeconds;

    /**
     * Timestamp when the rate limit was hit.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
     *
     * @param string $limitType The type of rate limit hit
     * @param \DateTimeInterface $resetTime When the rate limit will reset
     * @param string $endpoint The endpoint that hit the rate limit
     * @param int $limit Current rate limit for this type
     * @param int $requestsMade Number of requests made in current window
     * @param string $planType The subscription plan type
     * @param int $windowSeconds Time window for the rate limit
     */
    public function __construct(
        string $limitType,
        \DateTimeInterface $resetTime,
        string $endpoint,
        int $limit,
        int $requestsMade,
        string $planType,
        int $windowSeconds
    ) {
        $this->limitType = $limitType;
        $this->resetTime = $resetTime;
        $this->endpoint = $endpoint;
        $this->limit = $limit;
        $this->requestsMade = $requestsMade;
        $this->planType = $planType;
        $this->windowSeconds = $windowSeconds;
        $this->timestamp = new \DateTimeImmutable();
    }
}