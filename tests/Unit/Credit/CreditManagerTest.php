<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Credit;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Credit\CreditManager;

class CreditManagerTest extends TestCase
{
    private CreditManager $creditManager;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'plan' => [
                'credits_per_month' => 10000,
            ],
            'credits' => [
                'warning_threshold' => 0.8,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'cryptocurrency_quotes_latest' => 1,
                    'exchange_listings_latest' => 1,
                    'global_metrics_quotes_latest' => 1,
                ],
            ],
        ];
        
        $this->creditManager = new CreditManager($this->config);
    }

    public function test_consume_credits_updates_usage()
    {
        $result = $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 5);
        
        $this->assertTrue($result);
        
        $stats = $this->creditManager->getUsageStats();
        $this->assertEquals(5, $stats['used']);
        $this->assertEquals(9995, $stats['remaining']);
    }

    public function test_consume_credits_returns_false_when_approaching_limit()
    {
        // Consume up to warning threshold
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 8500);
        
        $result = $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        
        $this->assertFalse($result); // Should trigger warning
        
        $stats = $this->creditManager->getUsageStats();
        $this->assertEquals(8501, $stats['used']);
        $this->assertTrue($stats['usage_percentage'] > 80);
    }

    public function test_get_usage_stats_returns_correct_data()
    {
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 2500);
        
        $stats = $this->creditManager->getUsageStats();
        
        $this->assertEquals(2500, $stats['used']);
        $this->assertEquals(10000, $stats['limit']);
        $this->assertEquals(7500, $stats['remaining']);
        $this->assertEquals(25.0, $stats['usage_percentage']);
    }

    public function test_get_credit_cost_returns_configured_cost()
    {
        $cost = $this->creditManager->getCreditCost('cryptocurrency_listings_latest');
        $this->assertEquals(1, $cost);
        
        $cost = $this->creditManager->getCreditCost('exchange_listings_latest');
        $this->assertEquals(1, $cost);
    }

    public function test_get_credit_cost_returns_default_for_unknown_endpoint()
    {
        $cost = $this->creditManager->getCreditCost('unknown_endpoint');
        $this->assertEquals(1, $cost);
    }

    public function test_has_sufficient_credits_returns_true_when_credits_available()
    {
        $result = $this->creditManager->hasSufficientCredits('cryptocurrency_listings_latest');
        $this->assertTrue($result);
        
        // Consume some credits and test again
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 5000);
        $result = $this->creditManager->hasSufficientCredits('cryptocurrency_listings_latest');
        $this->assertTrue($result);
    }

    public function test_has_sufficient_credits_returns_false_when_credits_insufficient()
    {
        // Consume almost all credits
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 9999);
        
        // Try to consume 2 more credits (total would be 10001, over limit of 10000)
        $result = $this->creditManager->hasSufficientCredits('cryptocurrency_listings_latest');
        $this->assertTrue($result); // 1 credit still available
        
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $result = $this->creditManager->hasSufficientCredits('cryptocurrency_listings_latest');
        $this->assertFalse($result); // Now at limit
    }

    public function test_multiple_endpoint_credit_consumption()
    {
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 100);
        $this->creditManager->consumeCredits('exchange_listings_latest', 50);
        $this->creditManager->consumeCredits('global_metrics_quotes_latest', 25);
        
        $stats = $this->creditManager->getUsageStats();
        $this->assertEquals(175, $stats['used']);
        $this->assertEquals(9825, $stats['remaining']);
        $this->assertEquals(1.75, $stats['usage_percentage']);
    }

    public function test_credit_manager_with_different_plan_limits()
    {
        $customConfig = [
            'plan' => [
                'credits_per_month' => 40000, // Hobbyist plan
            ],
            'credits' => [
                'warning_threshold' => 0.9,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                ],
            ],
        ];
        
        $manager = new CreditManager($customConfig);
        
        $manager->consumeCredits('cryptocurrency_listings_latest', 20000);
        $stats = $manager->getUsageStats();
        
        $this->assertEquals(20000, $stats['used']);
        $this->assertEquals(40000, $stats['limit']);
        $this->assertEquals(20000, $stats['remaining']);
        $this->assertEquals(50.0, $stats['usage_percentage']);
    }
}