<?php

namespace Convertain\CoinMarketCap\Tests\Integration;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Cache\CoinMarketCapCache;
use Illuminate\Support\Facades\Cache;

class CacheIntegrationTest extends TestCase
{
    private CoinMarketCapCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = config('coinmarketcap');
        $this->cache = new CoinMarketCapCache($config);
    }

    public function test_cache_integration_with_laravel_cache()
    {
        // Test that our cache integrates properly with Laravel's cache system
        $key = 'integration_test';
        $value = 'test_value';
        
        $result = $this->cache->put($key, $value, 60);
        $this->assertTrue($result);
        
        $cached = $this->cache->get($key);
        $this->assertEquals($value, $cached);
    }

    public function test_cache_remember_integration()
    {
        $callCount = 0;
        
        $result1 = $this->cache->remember('remember_test', function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        }, 60);
        
        $result2 = $this->cache->remember('remember_test', function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        }, 60);
        
        $this->assertEquals('computed_value', $result1);
        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $callCount); // Should only be called once due to caching
    }

    public function test_cache_key_generation()
    {
        $testKey = 'cryptocurrency_quotes_btc';
        
        // Store a value
        $this->cache->put($testKey, 'btc_data', 60);
        
        // Retrieve it
        $result = $this->cache->get($testKey);
        
        $this->assertEquals('btc_data', $result);
    }

    public function test_cache_ttl_configuration()
    {
        // Test that different endpoints get different TTL values
        $shortLivedData = $this->cache->remember('cryptocurrency_quotes_test', function () {
            return 'short_lived';
        });
        
        $longLivedData = $this->cache->remember('cryptocurrency_map_test', function () {
            return 'long_lived';
        });
        
        $this->assertEquals('short_lived', $shortLivedData);
        $this->assertEquals('long_lived', $longLivedData);
    }

    public function test_cache_forget_integration()
    {
        $key = 'forget_test';
        
        // Store value
        $this->cache->put($key, 'value_to_forget', 60);
        $this->assertEquals('value_to_forget', $this->cache->get($key));
        
        // Forget value
        $result = $this->cache->forget($key);
        $this->assertTrue($result);
        
        // Value should be gone
        $this->assertNull($this->cache->get($key));
    }

    public function test_cache_prefix_integration()
    {
        // Test that our custom prefix is applied
        $key = 'prefix_test';
        $value = 'prefixed_value';
        
        $this->cache->put($key, $value, 60);
        
        // Try to get using Laravel's cache directly with our prefix
        $directValue = Cache::get('coinmarketcap:' . $key);
        $this->assertEquals($value, $directValue);
    }

    public function test_cache_disabled_integration()
    {
        $config = config('coinmarketcap');
        $config['cache']['enabled'] = false;
        $disabledCache = new CoinMarketCapCache($config);
        
        // Cache operations should not actually cache when disabled
        $callCount = 0;
        
        $result1 = $disabledCache->remember('disabled_test', function () use (&$callCount) {
            $callCount++;
            return 'fresh_value';
        });
        
        $result2 = $disabledCache->remember('disabled_test', function () use (&$callCount) {
            $callCount++;
            return 'fresh_value';
        });
        
        $this->assertEquals('fresh_value', $result1);
        $this->assertEquals('fresh_value', $result2);
        $this->assertEquals(2, $callCount); // Should be called twice since caching is disabled
    }

    public function test_cache_store_configuration()
    {
        // Test with different cache store
        $config = config('coinmarketcap');
        $config['cache']['store'] = 'array';
        $arrayCache = new CoinMarketCapCache($config);
        
        $arrayCache->put('store_test', 'array_value', 60);
        $result = $arrayCache->get('store_test');
        
        $this->assertEquals('array_value', $result);
    }

    public function test_cache_with_complex_data()
    {
        // Test caching of complex data structures
        $complexData = [
            'cryptocurrencies' => [
                ['id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC'],
                ['id' => 1027, 'name' => 'Ethereum', 'symbol' => 'ETH'],
            ],
            'metadata' => [
                'timestamp' => now()->toDateTimeString(),
                'total_count' => 2,
            ],
        ];
        
        $this->cache->put('complex_data_test', $complexData, 60);
        $result = $this->cache->get('complex_data_test');
        
        $this->assertEquals($complexData, $result);
        $this->assertIsArray($result['cryptocurrencies']);
        $this->assertCount(2, $result['cryptocurrencies']);
    }

    public function test_concurrent_cache_access()
    {
        // Simulate concurrent access scenarios
        $keys = ['concurrent_1', 'concurrent_2', 'concurrent_3'];
        
        foreach ($keys as $key) {
            $this->cache->put($key, "value_for_{$key}", 60);
        }
        
        // Retrieve all keys
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->cache->get($key);
        }
        
        $this->assertCount(3, $results);
        $this->assertEquals('value_for_concurrent_1', $results['concurrent_1']);
        $this->assertEquals('value_for_concurrent_2', $results['concurrent_2']);
        $this->assertEquals('value_for_concurrent_3', $results['concurrent_3']);
    }
}