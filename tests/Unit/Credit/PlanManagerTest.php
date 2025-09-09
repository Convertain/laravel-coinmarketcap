<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Credit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\Credit\PlanManager;

class PlanManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_resolves_basic_plan_configuration()
    {
        $config = [
            'plan' => [
                'type' => 'basic',
            ],
            'plans' => [
                'basic' => [
                    'credits_per_month' => 10000,
                    'calls_per_day' => 333,
                    'calls_per_minute' => 30,
                ],
            ],
        ];

        $planManager = new PlanManager($config);

        $this->assertEquals('basic', $planManager->getPlanType());
        $this->assertEquals(10000, $planManager->getMonthlyCredits());
        $this->assertEquals(333, $planManager->getDailyCallLimit());
        $this->assertEquals(30, $planManager->getMinuteCallLimit());
    }

    /** @test */
    public function it_overrides_predefined_plan_with_explicit_config()
    {
        $config = [
            'plan' => [
                'type' => 'basic',
                'credits_per_month' => 15000, // Override
                'calls_per_day' => 500, // Override
            ],
            'plans' => [
                'basic' => [
                    'credits_per_month' => 10000,
                    'calls_per_day' => 333,
                    'calls_per_minute' => 30,
                ],
            ],
        ];

        $planManager = new PlanManager($config);

        $this->assertEquals(15000, $planManager->getMonthlyCredits());
        $this->assertEquals(500, $planManager->getDailyCallLimit());
        $this->assertEquals(30, $planManager->getMinuteCallLimit()); // From predefined
    }

    /** @test */
    public function it_provides_default_configuration_for_unknown_plan()
    {
        $config = [
            'plan' => [
                'type' => 'unknown_plan',
            ],
        ];

        $planManager = new PlanManager($config);

        $this->assertEquals('unknown_plan', $planManager->getPlanType());
        $this->assertEquals(10000, $planManager->getMonthlyCredits()); // Default
        $this->assertEquals(333, $planManager->getDailyCallLimit()); // Default
        $this->assertEquals(30, $planManager->getMinuteCallLimit()); // Default
    }

    /** @test */
    public function it_supports_plan_feature_checking()
    {
        $config = [
            'plan' => ['type' => 'professional'],
        ];

        $planManager = new PlanManager($config);

        $this->assertTrue($planManager->supportsFeature('cryptocurrency_data'));
        $this->assertTrue($planManager->supportsFeature('exchange_data'));
        $this->assertTrue($planManager->supportsFeature('historical_data'));
        $this->assertTrue($planManager->supportsFeature('batch_requests'));
        $this->assertFalse($planManager->supportsFeature('custom_limits')); // Enterprise only
    }

    /** @test */
    public function it_calculates_optimal_batch_sizes_by_plan()
    {
        $config = [
            'plan' => ['type' => 'startup'],
            'endpoints' => [
                'limits' => [
                    'cryptocurrency_ids_per_request' => 100,
                    'exchange_ids_per_request' => 50,
                ],
            ],
        ];

        $planManager = new PlanManager($config);

        // Should return plan-specific batch size limited by endpoint limits
        $cryptoBatchSize = $planManager->getOptimalBatchSize('cryptocurrency_quotes_latest');
        $exchangeBatchSize = $planManager->getOptimalBatchSize('exchange_quotes_latest');

        $this->assertEquals(50, $cryptoBatchSize); // Startup plan = 50, endpoint limit = 100
        $this->assertEquals(50, $exchangeBatchSize); // Startup plan = 50, endpoint limit = 50
    }

    /** @test */
    public function it_provides_upgrade_recommendations_based_on_usage()
    {
        $config = [
            'plan' => ['type' => 'basic'],
            'plans' => [
                'basic' => ['credits_per_month' => 10000],
                'hobbyist' => ['credits_per_month' => 40000],
                'startup' => ['credits_per_month' => 120000],
            ],
        ];

        $planManager = new PlanManager($config);

        // Current usage at 90% of basic plan limit
        $recommendations = $planManager->getUpgradeRecommendations(9000, 1.2);

        $this->assertTrue($recommendations['needs_upgrade']);
        $this->assertEquals('basic', $recommendations['current_plan']);
        $this->assertEquals(10000, $recommendations['current_limit']);
        $this->assertEquals(9000, $recommendations['current_usage']);
        $this->assertEquals(10800, $recommendations['projected_usage']); // 9000 * 1.2

        // Should recommend hobbyist plan
        $this->assertNotEmpty($recommendations['recommendations']);
        $this->assertEquals('hobbyist', $recommendations['recommendations'][0]['plan_type']);
    }

    /** @test */
    public function it_calculates_cost_efficiency_metrics()
    {
        $config = [
            'plan' => ['type' => 'hobbyist'],
            'plans' => [
                'hobbyist' => ['credits_per_month' => 40000],
            ],
        ];

        $planManager = new PlanManager($config);

        // 80% utilization
        $metrics = $planManager->getCostEfficiencyMetrics(32000);

        $this->assertEquals('hobbyist', $metrics['plan_type']);
        $this->assertEquals(40000, $metrics['monthly_limit']);
        $this->assertEquals(32000, $metrics['actual_usage']);
        $this->assertEquals(0.8, $metrics['utilization_rate']);
        $this->assertEquals('good', $metrics['efficiency_score']);
        $this->assertStringContainsString('Good utilization', $metrics['recommendation']);
    }

    /** @test */
    public function it_provides_endpoint_recommendations_by_plan()
    {
        $config = [
            'plan' => ['type' => 'startup'],
            'credits' => [
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'cryptocurrency_quotes_latest' => 1,
                    'exchange_listings_latest' => 1,
                ],
            ],
        ];

        $planManager = new PlanManager($config);
        $recommendations = $planManager->getRecommendedEndpoints();

        $this->assertArrayHasKey('cryptocurrency_listings_latest', $recommendations);
        $this->assertArrayHasKey('cryptocurrency_quotes_latest', $recommendations);
        $this->assertArrayHasKey('exchange_listings_latest', $recommendations);

        // Check that recommendations include priority and cost information
        $listing = $recommendations['cryptocurrency_listings_latest'];
        $this->assertArrayHasKey('priority', $listing);
        $this->assertArrayHasKey('cost', $listing);
        $this->assertArrayHasKey('recommended_frequency', $listing);
    }
}