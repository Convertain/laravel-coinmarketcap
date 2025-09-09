<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Events;

use Convertain\CoinMarketCap\Events\CreditWarning;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CreditWarning event.
 */
class CreditWarningTest extends TestCase
{
    /**
     * Test that CreditWarning event can be created with valid data.
     */
    public function testCanCreateCreditWarningEvent(): void
    {
        $thresholdPercent = 80.0;
        $creditsRemaining = 2000;
        $estimatedDaysLeft = 5;
        $planType = 'basic';
        $currentUsagePercent = 82.5;
        $totalCredits = 10000;
        $averageDailyUsage = 400.0;

        $event = new CreditWarning(
            $thresholdPercent,
            $creditsRemaining,
            $estimatedDaysLeft,
            $planType,
            $currentUsagePercent,
            $totalCredits,
            $averageDailyUsage
        );

        $this->assertEquals($thresholdPercent, $event->thresholdPercent);
        $this->assertEquals($creditsRemaining, $event->creditsRemaining);
        $this->assertEquals($estimatedDaysLeft, $event->estimatedDaysLeft);
        $this->assertEquals($planType, $event->planType);
        $this->assertEquals($currentUsagePercent, $event->currentUsagePercent);
        $this->assertEquals($totalCredits, $event->totalCredits);
        $this->assertEquals($averageDailyUsage, $event->averageDailyUsage);
        $this->assertInstanceOf(\DateTimeInterface::class, $event->timestamp);
    }

    /**
     * Test critical warning threshold.
     */
    public function testCriticalWarningThreshold(): void
    {
        $event = new CreditWarning(
            95.0,
            500,
            1,
            'professional',
            96.5,
            50000,
            1600.0
        );

        $this->assertEquals(95.0, $event->thresholdPercent);
        $this->assertEquals(96.5, $event->currentUsagePercent);
        $this->assertEquals(1, $event->estimatedDaysLeft);
    }

    /**
     * Test warning for different plan types.
     */
    public function testWarningForDifferentPlans(): void
    {
        $plans = ['basic', 'hobbyist', 'startup', 'standard', 'professional', 'enterprise'];
        $totalCredits = [10000, 40000, 120000, 500000, 2000000, 100000000];

        foreach ($plans as $index => $plan) {
            $event = new CreditWarning(
                80.0,
                $totalCredits[$index] * 0.2, // 20% remaining
                7,
                $plan,
                80.0,
                $totalCredits[$index],
                $totalCredits[$index] / 30 // Monthly credits / 30 days
            );

            $this->assertEquals($plan, $event->planType);
            $this->assertEquals($totalCredits[$index], $event->totalCredits);
        }
    }

    /**
     * Test zero days left warning.
     */
    public function testZeroDaysLeftWarning(): void
    {
        $event = new CreditWarning(
            99.0,
            50,
            0,
            'basic',
            99.5,
            10000,
            500.0
        );

        $this->assertEquals(0, $event->estimatedDaysLeft);
        $this->assertEquals(99.5, $event->currentUsagePercent);
        $this->assertEquals(50, $event->creditsRemaining);
    }
}