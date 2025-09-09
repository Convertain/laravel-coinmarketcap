<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when credits are consumed from a CoinMarketCap API call.
 *
 * Tracks detailed credit consumption information including remaining credits,
 * usage percentage, and related endpoint information.
 */
class CreditConsumed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Number of credits used in this consumption.
     */
    public readonly int $creditsUsed;

    /**
     * Number of credits remaining in the current period.
     */
    public readonly int $creditsRemaining;

    /**
     * Percentage of total credits used (0-100).
     */
    public readonly float $percentageUsed;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * The endpoint that consumed the credits.
     */
    public readonly string $endpoint;

    /**
     * Total credits available for the plan.
     */
    public readonly int $totalCredits;

    /**
     * Timestamp when the credits were consumed.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $creditsUsed Number of credits used
     * @param int $creditsRemaining Number of credits remaining
     * @param float $percentageUsed Percentage of total credits used
     * @param string $planType The subscription plan type
     * @param string $endpoint The endpoint that consumed the credits
     * @param int $totalCredits Total credits available for the plan
     */
    public function __construct(
        int $creditsUsed,
        int $creditsRemaining,
        float $percentageUsed,
        string $planType,
        string $endpoint,
        int $totalCredits
    ) {
        $this->creditsUsed = $creditsUsed;
        $this->creditsRemaining = $creditsRemaining;
        $this->percentageUsed = $percentageUsed;
        $this->planType = $planType;
        $this->endpoint = $endpoint;
        $this->totalCredits = $totalCredits;
        $this->timestamp = new \DateTimeImmutable();
    }
}