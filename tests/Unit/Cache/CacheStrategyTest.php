<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Tests\Unit\Cache;

use Convertain\CoinMarketCap\Cache\CacheStrategy;
use Convertain\CoinMarketCap\Cache\CacheAnalytics;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CacheStrategy functionality.
 */
class CacheStrategyTest extends TestCase
{
    private CacheStrategy $strategy;
    private CacheAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheRepository = new Repository(new ArrayStore());
        $this->analytics = new CacheAnalytics($cacheRepository);
        $this->strategy = new CacheStrategy($this->analytics);
    }

    public function testCalculateTtlForDifferentEndpoints(): void
    {
        // Test static data gets longer TTL
        $staticTtl = $this->strategy->calculateTtl('cryptocurrency_map');
        $this->assertGreaterThan(1000, $staticTtl);

        // Test real-time data gets shorter TTL
        $realTimeTtl = $this->strategy->calculateTtl('cryptocurrency_quotes');
        $this->assertLessThan(300, $realTimeTtl);

        // Historical data should have moderate-to-long TTL
        $historicalTtl = $this->strategy->calculateTtl('historical_quotes');
        $this->assertGreaterThan(1000, $historicalTtl);
    }

    public function testShouldCacheBasedOnCreditCost(): void
    {
        // High credit cost should always cache
        $this->assertTrue($this->strategy->shouldCache('cryptocurrency_quotes', 5));

        // Low credit cost depends on endpoint type
        $this->assertTrue($this->strategy->shouldCache('cryptocurrency_map', 1));
        
        // Test different endpoint types
        $this->assertTrue($this->strategy->shouldCache('cryptocurrency_info', 1));
    }

    public function testGetCachePriority(): void
    {
        // Static data should have highest priority
        $staticPriority = $this->strategy->getCachePriority('cryptocurrency_map', 1);
        $this->assertGreaterThanOrEqual(8, $staticPriority);

        // Real-time data should have lower priority
        $realTimePriority = $this->strategy->getCachePriority('cryptocurrency_quotes', 1);
        $this->assertLessThan($staticPriority, $realTimePriority);

        // Higher credit cost should increase priority
        $highCostPriority = $this->strategy->getCachePriority('cryptocurrency_quotes', 5);
        $this->assertGreaterThan($realTimePriority, $highCostPriority);
    }

    public function testShouldInvalidateBasedOnVolatility(): void
    {
        // High volatility should trigger invalidation for real-time data
        $shouldInvalidate = $this->strategy->shouldInvalidate(
            'cryptocurrency_quotes',
            ['volatility' => 'high']
        );
        $this->assertTrue($shouldInvalidate);

        // Static data should not be invalidated due to volatility
        $shouldNotInvalidate = $this->strategy->shouldInvalidate(
            'cryptocurrency_map',
            ['volatility' => 'high']
        );
        $this->assertFalse($shouldNotInvalidate);
    }

    public function testGetWarmingPriorities(): void
    {
        $priorities = $this->strategy->getWarmingPriorities();

        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('cryptocurrency_map', $priorities);
        $this->assertArrayHasKey('fiat_map', $priorities);

        // Map endpoints should have high priority
        $this->assertGreaterThanOrEqual(9, $priorities['cryptocurrency_map']);
    }

    public function testStrategyChange(): void
    {
        $initialStrategy = $this->strategy->getCurrentStrategy();
        
        // Change to aggressive strategy
        $this->strategy->setStrategy('aggressive');
        $this->assertEquals('aggressive', $this->strategy->getCurrentStrategy());

        // Test invalid strategy throws exception
        $this->expectException(\InvalidArgumentException::class);
        $this->strategy->setStrategy('nonexistent_strategy');
    }

    public function testGetAvailableStrategies(): void
    {
        $strategies = $this->strategy->getAvailableStrategies();

        $this->assertIsArray($strategies);
        $this->assertArrayHasKey('aggressive', $strategies);
        $this->assertArrayHasKey('balanced', $strategies);
        $this->assertArrayHasKey('fresh', $strategies);
        $this->assertArrayHasKey('adaptive', $strategies);

        // Each strategy should have required fields
        foreach ($strategies as $strategy) {
            $this->assertArrayHasKey('ttl_multiplier', $strategy);
            $this->assertArrayHasKey('cache_real_time', $strategy);
            $this->assertArrayHasKey('description', $strategy);
        }
    }

    public function testGetOptimalStrategyAdjustments(): void
    {
        // Test endpoint-specific adjustments
        $staticStrategy = $this->strategy->getOptimalStrategy('cryptocurrency_map');
        $realTimeStrategy = $this->strategy->getOptimalStrategy('cryptocurrency_quotes');

        $this->assertIsArray($staticStrategy);
        $this->assertIsArray($realTimeStrategy);

        // Static data should have higher TTL multiplier
        $this->assertGreaterThanOrEqual($realTimeStrategy['ttl_multiplier'], $staticStrategy['ttl_multiplier']);
    }

    public function testAdaptStrategyBasedOnMetrics(): void
    {
        $initialStrategy = $this->strategy->getCurrentStrategy();

        // Simulate poor hit rate metrics
        $poorMetrics = [
            'hit_rate' => 0.3,
            'credit_efficiency' => 0.4,
            'error_rate' => 0.05,
        ];

        $this->strategy->adaptStrategy($poorMetrics);
        $newStrategy = $this->strategy->getCurrentStrategy();

        // Should adapt to more aggressive caching
        $this->assertNotEquals($initialStrategy, $newStrategy);
    }
}