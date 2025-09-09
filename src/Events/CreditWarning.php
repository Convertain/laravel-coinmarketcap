<?php

namespace Convertain\CoinMarketCap\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when credit usage approaches warning thresholds.
 *
 * Provides early warning system for credit consumption to prevent
 * exceeding plan limits and unexpected service interruptions.
 */
class CreditWarning
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The threshold percentage that triggered the warning.
     */
    public readonly float $thresholdPercent;

    /**
     * Number of credits remaining.
     */
    public readonly int $creditsRemaining;

    /**
     * Estimated days left at current usage rate.
     */
    public readonly int $estimatedDaysLeft;

    /**
     * The subscription plan type.
     */
    public readonly string $planType;

    /**
     * Current usage percentage.
     */
    public readonly float $currentUsagePercent;

    /**
     * Total credits for the plan.
     */
    public readonly int $totalCredits;

    /**
     * Average daily credit consumption.
     */
    public readonly float $averageDailyUsage;

    /**
     * Timestamp when the warning was triggered.
     */
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
     *
     * @param float $thresholdPercent The threshold percentage that triggered the warning
     * @param int $creditsRemaining Number of credits remaining
     * @param int $estimatedDaysLeft Estimated days left at current usage rate
     * @param string $planType The subscription plan type
     * @param float $currentUsagePercent Current usage percentage
     * @param int $totalCredits Total credits for the plan
     * @param float $averageDailyUsage Average daily credit consumption
     */
    public function __construct(
        float $thresholdPercent,
        int $creditsRemaining,
        int $estimatedDaysLeft,
        string $planType,
        float $currentUsagePercent,
        int $totalCredits,
        float $averageDailyUsage
    ) {
        $this->thresholdPercent = $thresholdPercent;
        $this->creditsRemaining = $creditsRemaining;
        $this->estimatedDaysLeft = $estimatedDaysLeft;
        $this->planType = $planType;
        $this->currentUsagePercent = $currentUsagePercent;
        $this->totalCredits = $totalCredits;
        $this->averageDailyUsage = $averageDailyUsage;
        $this->timestamp = new \DateTimeImmutable();
    }
}