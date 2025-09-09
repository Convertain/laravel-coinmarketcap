<?php

namespace Convertain\CoinMarketCap\Listeners;

use Convertain\CoinMarketCap\Events\CreditWarning;
use Illuminate\Support\Facades\Log;

/**
 * Listener for credit warning events.
 *
 * Logs credit warnings and can be extended to send notifications
 * when credit usage approaches limits.
 */
class LogCreditWarning
{
    /**
     * Handle the credit warning event.
     *
     * @param CreditWarning $event The credit warning event
     */
    public function handle(CreditWarning $event): void
    {
        $context = [
            'threshold_percent' => $event->thresholdPercent,
            'credits_remaining' => $event->creditsRemaining,
            'estimated_days_left' => $event->estimatedDaysLeft,
            'current_usage_percent' => $event->currentUsagePercent,
            'plan_type' => $event->planType,
            'total_credits' => $event->totalCredits,
            'average_daily_usage' => $event->averageDailyUsage,
            'timestamp' => $event->timestamp->format('Y-m-d H:i:s'),
        ];

        $severity = $this->getSeverityLevel($event->currentUsagePercent);
        
        match ($severity) {
            'critical' => Log::critical('CoinMarketCap credit usage critical', $context),
            'warning' => Log::warning('CoinMarketCap credit usage warning', $context),
            default => Log::notice('CoinMarketCap credit usage notice', $context),
        };
    }

    /**
     * Determine severity level based on usage percentage.
     *
     * @param float $usagePercent Current usage percentage
     * @return string Severity level
     */
    private function getSeverityLevel(float $usagePercent): string
    {
        return match (true) {
            $usagePercent >= 95 => 'critical',
            $usagePercent >= 80 => 'warning',
            default => 'notice',
        };
    }
}