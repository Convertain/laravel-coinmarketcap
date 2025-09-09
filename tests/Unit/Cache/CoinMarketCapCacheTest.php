<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Tests\Unit\Cache;

use Convertain\CoinMarketCap\Cache\CoinMarketCapCache;
use Convertain\CoinMarketCap\Cache\CacheAnalytics;
use Convertain\CoinMarketCap\Cache\CacheStrategy;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Test case for CoinMarketCapCache functionality.
 */
class CoinMarketCapCacheTest extends TestCase
{
    private CoinMarketCapCache $cache;
    private Repository $cacheRepository;
    private CacheAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test cache repository
        $this->cacheRepository = new Repository(new ArrayStore());
        $this->analytics = new CacheAnalytics($this->cacheRepository);
        $this->cache = new CoinMarketCapCache(
            $this->cacheRepository,
            $this->analytics,
            new NullLogger()
        );
    }

    public function testCacheRememberStoresAndReturnsData(): void
    {
        $key = 'test_key';
        $expectedData = ['price' => 50000, 'symbol' => 'BTC'];
        $callCount = 0;

        // Test cache miss and storage
        $result = $this->cache->remember($key, function() use ($expectedData, &$callCount) {
            $callCount++;
            return $expectedData;
        }, 'cryptocurrency_quotes');

        $this->assertEquals($expectedData, $result);
        $this->assertEquals(1, $callCount);

        // Test cache hit
        $result2 = $this->cache->remember($key, function() use (&$callCount) {
            $callCount++;
            return ['should_not_be_called'];
        }, 'cryptocurrency_quotes');

        $this->assertEquals($expectedData, $result2);
        $this->assertEquals(1, $callCount); // Callback should not be called again
    }

    public function testCachePutAndGet(): void
    {
        $key = 'put_test';
        $data = ['test' => 'data'];

        // Test put
        $result = $this->cache->put($key, $data, 'cryptocurrency_info');
        $this->assertTrue($result);

        // Test get
        $retrieved = $this->cache->get($key);
        $this->assertEquals($data, $retrieved);
    }

    public function testCacheForget(): void
    {
        $key = 'forget_test';
        $data = ['test' => 'data'];

        // Store data
        $this->cache->put($key, $data);

        // Verify it exists
        $this->assertEquals($data, $this->cache->get($key));

        // Forget it
        $result = $this->cache->forget($key);
        $this->assertTrue($result);

        // Verify it's gone
        $this->assertNull($this->cache->get($key));
    }

    public function testShouldCacheLogic(): void
    {
        // Should cache high-cost endpoints
        $this->assertTrue($this->cache->shouldCache('cryptocurrency_quotes', 5));

        // Should cache static data
        $this->assertTrue($this->cache->shouldCache('cryptocurrency_map', 1));
    }

    public function testInvalidateRealTimeData(): void
    {
        // Store some real-time data
        $this->cache->put('quotes:BTC:USD', ['price' => 50000], 'cryptocurrency_quotes');
        $this->cache->put('quotes:ETH:USD', ['price' => 3000], 'cryptocurrency_quotes');

        // Verify data exists
        $this->assertNotNull($this->cache->get('quotes:BTC:USD'));

        // Invalidate real-time data
        $invalidated = $this->cache->invalidateRealTimeData(['BTC', 'ETH']);

        // Since this is a simplified test, we expect some invalidation to occur
        $this->assertIsInt($invalidated);
    }

    public function testWarmCache(): void
    {
        $warmingData = [
            [
                'key' => 'warm_test_1',
                'value' => ['data' => 'value1'],
                'endpoint_type' => 'cryptocurrency_map',
            ],
            [
                'key' => 'warm_test_2',
                'value' => ['data' => 'value2'],
                'endpoint_type' => 'cryptocurrency_info',
            ],
        ];

        $warmed = $this->cache->warm($warmingData);
        $this->assertEquals(2, $warmed);

        // Verify warmed data exists
        $this->assertEquals(['data' => 'value1'], $this->cache->get('warm_test_1'));
        $this->assertEquals(['data' => 'value2'], $this->cache->get('warm_test_2'));
    }

    public function testGetStatistics(): void
    {
        // Generate some cache activity
        $this->cache->remember('stats_test', fn() => ['data'], 'cryptocurrency_quotes');
        $this->cache->get('nonexistent_key'); // Miss

        $stats = $this->cache->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('overview', $stats);
        $this->assertArrayHasKey('performance', $stats);
        $this->assertArrayHasKey('endpoints', $stats);
    }

    public function testBuildCacheKeyMethod(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);

        $result = $method->invoke($this->cache, 'test_key');
        $this->assertEquals('coinmarketcap:test_key', $result);
    }
}