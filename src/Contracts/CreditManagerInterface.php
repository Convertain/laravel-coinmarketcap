<?php

namespace Convertain\CoinMarketCap\Contracts;

/**
 * Interface for credit management operations.
 */
interface CreditManagerInterface
{
    /**
     * Track credit consumption for an API endpoint.
     *
     * @param string $endpoint The API endpoint being called
     * @param int $credits Number of credits consumed
     * @return void
     */
    public function trackUsage(string $endpoint, int $credits): void;

    /**
     * Get remaining credits for current billing period.
     *
     * @return int Number of credits remaining
     */
    public function getRemainingCredits(): int;

    /**
     * Get total credits used in current billing period.
     *
     * @return int Number of credits used
     */
    public function getUsedCredits(): int;

    /**
     * Check if endpoint call would exceed credit limits.
     *
     * @param string $endpoint The API endpoint to check
     * @param int $credits Number of credits that would be consumed
     * @return bool True if call is within limits
     */
    public function canMakeCall(string $endpoint, int $credits): bool;

    /**
     * Get credit usage percentage (0-1).
     *
     * @return float Usage percentage
     */
    public function getUsagePercentage(): float;

    /**
     * Check if usage has exceeded warning threshold.
     *
     * @return bool True if warning threshold exceeded
     */
    public function hasExceededWarningThreshold(): bool;

    /**
     * Reset credit tracking for new billing period.
     *
     * @return void
     */
    public function resetPeriod(): void;
}