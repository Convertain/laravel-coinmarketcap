<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Credit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\Credit\CreditOptimizer;
use Convertain\CoinMarketCap\Credit\PlanManager;
use Convertain\CoinMarketCap\Credit\CreditManager;
use Illuminate\Cache\CacheManager;

class CreditOptimizerTest extends TestCase
{
    private $mockCache;
    private $mockPlanManager;
    private $mockCreditManager;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCache = Mockery::mock(CacheManager::class);
        $this->mockPlanManager = Mockery::mock(PlanManager::class);
        $this->mockCreditManager = Mockery::mock(CreditManager::class);

        $this->config = [
            'credits' => [
                'optimization_enabled' => true,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'cryptocurrency_quotes_latest' => 1,
                ],
            ],
            'cache' => [
                'enabled' => true,
                'ttl' => [
                    'cryptocurrency_listings' => 300, // 5 minutes
                    'cryptocurrency_quotes' => 60,    // 1 minute
                ],
            ],
            'supported_currencies' => ['usd', 'eur', 'btc'],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_checks_optimization_enabled_status()
    {
        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $this->assertTrue($optimizer->isOptimizationEnabled());

        // Test disabled optimization
        $configDisabled = array_merge($this->config, [
            'credits' => ['optimization_enabled' => false],
        ]);

        $optimizerDisabled = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $configDisabled
        );

        $this->assertFalse($optimizerDisabled->isOptimizationEnabled());
    }

    /** @test */
    public function it_optimizes_request_parameters_when_enabled()
    {
        $this->mockPlanManager
            ->shouldReceive('getOptimalBatchSize')
            ->with('cryptocurrency/quotes/latest')
            ->andReturn(50);

        $this->mockPlanManager
            ->shouldReceive('getPlanType')
            ->andReturn('startup');

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $params = [
            'id' => array_range(1, 100), // Too many IDs
            'convert' => ['usd', 'eur', 'btc', 'eth', 'ltc'], // Too many currencies
        ];

        $optimized = $optimizer->optimizeRequest('cryptocurrency/quotes/latest', $params);

        // Should reduce batch size
        $this->assertCount(50, $optimized['id']);

        // Should limit currencies to supported ones
        $this->assertEquals(['usd', 'eur', 'btc'], $optimized['convert']);
    }

    /** @test */
    public function it_returns_unmodified_params_when_disabled()
    {
        $configDisabled = array_merge($this->config, [
            'credits' => ['optimization_enabled' => false],
        ]);

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $configDisabled
        );

        $params = ['id' => [1, 2, 3]];
        $result = $optimizer->optimizeRequest('test/endpoint', $params);

        $this->assertEquals($params, $result);
    }

    /** @test */
    public function it_provides_batch_recommendations()
    {
        $this->mockPlanManager
            ->shouldReceive('getOptimalBatchSize')
            ->with('cryptocurrency/quotes/latest')
            ->andReturn(25);

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $items = range(1, 100); // 100 items
        $batches = $optimizer->getBatchRecommendations('cryptocurrency/quotes/latest', $items);

        $this->assertCount(4, $batches); // 100 / 25 = 4 batches
        $this->assertCount(25, $batches[0]);
        $this->assertCount(25, $batches[3]);
    }

    /** @test */
    public function it_evaluates_cache_usage_based_on_credit_limits()
    {
        // Mock cache store and data
        $mockCacheStore = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $mockCacheStore->shouldReceive('get')
                      ->andReturn([
                          'cached_at' => now()->subMinutes(2)->toISOString(),
                          'data' => ['test' => 'data'],
                      ]);

        $this->mockCache->shouldReceive('store')
                       ->andReturn($mockCacheStore);

        // Mock credit manager with high usage
        $this->mockCreditManager->shouldReceive('canMakeCall')
                                ->andReturn(true);
        $this->mockCreditManager->shouldReceive('getUsagePercentage')
                                ->andReturn(0.95); // 95% usage

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $decision = $optimizer->shouldUseCache('cryptocurrency/quotes/latest', []);

        $this->assertTrue($decision['use_cache']);
        $this->assertStringContains('high_usage', $decision['reason']);
    }

    /** @test */
    public function it_calculates_cost_benefit_analysis()
    {
        $mockCacheStore = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $mockCacheStore->shouldReceive('get')
                      ->andReturn(null); // No cached data

        $this->mockCache->shouldReceive('store')
                       ->andReturn($mockCacheStore);

        $this->mockCreditManager->shouldReceive('getRemainingCredits')
                                ->andReturn(1000);
        $this->mockCreditManager->shouldReceive('getUsagePercentage')
                                ->andReturn(0.5); // 50% usage

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $analysis = $optimizer->calculateCostBenefit(
            'cryptocurrency/quotes/latest',
            [],
            300 // Max acceptable age 5 minutes
        );

        $this->assertArrayHasKey('benefit_score', $analysis);
        $this->assertArrayHasKey('cost_score', $analysis);
        $this->assertArrayHasKey('recommendation_score', $analysis);
        $this->assertArrayHasKey('recommendation', $analysis);

        // No cached data = maximum benefit
        $this->assertEquals(100, $analysis['benefit_score']);
    }

    /** @test */
    public function it_provides_credit_saving_alternatives()
    {
        $mockCacheStore = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $mockCacheStore->shouldReceive('get')
                      ->andReturn([
                          'cached_at' => now()->subMinutes(10)->toISOString(),
                          'data' => ['test' => 'data'],
                      ]);

        $this->mockCache->shouldReceive('store')
                       ->andReturn($mockCacheStore);

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $alternatives = $optimizer->getCreditSavingAlternatives(
            'cryptocurrency/quotes/latest',
            []
        );

        $this->assertArrayHasKey('cached_data', $alternatives);
        $this->assertEquals(1, $alternatives['cached_data']['credit_savings']);
        $this->assertArrayHasKey('freshness', $alternatives['cached_data']);
    }

    /** @test */
    public function it_provides_optimal_timing_recommendations()
    {
        // Mock credit manager with near rate limits
        $this->mockCreditManager->shouldReceive('getUsageStats')
                                ->andReturn([
                                    'minute_calls' => 25,
                                    'minute_limit' => 30,
                                    'daily_calls' => 100,
                                    'daily_limit' => 333,
                                    'usage_percentage' => 0.5,
                                ]);

        $optimizer = new CreditOptimizer(
            $this->mockCache,
            $this->mockPlanManager,
            $this->mockCreditManager,
            $this->config
        );

        $timing = $optimizer->getOptimalTiming('cryptocurrency/quotes/latest');

        // Should recommend delay due to near minute limit (25/30 = 83%)
        $this->assertTrue($timing['should_delay']);
        $this->assertEquals('minute_rate_limit_near', $timing['reason']);
        $this->assertArrayHasKey('delay_seconds', $timing);
    }
}