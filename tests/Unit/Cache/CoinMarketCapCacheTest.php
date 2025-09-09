<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Cache;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Cache\CoinMarketCapCache;
use Illuminate\Support\Facades\Cache;

class CoinMarketCapCacheTest extends TestCase
{
    private CoinMarketCapCache $cache;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'cache' => [
                'enabled' => true,
                'store' => null,
                'prefix' => 'coinmarketcap',
                'ttl' => [
                    'cryptocurrency_quotes' => 60,
                    'exchange_quotes' => 60,
                    'global_metrics' => 300,
                    'cryptocurrency_map' => 86400,
                ],
            ],
        ];
        
        $this->cache = new CoinMarketCapCache($this->config);
    }

    public function test_remember_caches_callback_result()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('remember')
            ->with('coinmarketcap:test_key', 300, Mockery::any())
            ->andReturn('cached_value');
        
        $result = $this->cache->remember('test_key', function () {
            return 'fresh_value';
        });
        
        $this->assertEquals('cached_value', $result);
    }

    public function test_remember_executes_callback_when_cache_disabled()
    {
        $config = $this->config;
        $config['cache']['enabled'] = false;
        $cache = new CoinMarketCapCache($config);
        
        $result = $cache->remember('test_key', function () {
            return 'fresh_value';
        });
        
        $this->assertEquals('fresh_value', $result);
    }

    public function test_put_stores_value_in_cache()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('put')
            ->with('coinmarketcap:test_key', 'test_value', 300)
            ->once()
            ->andReturn(true);
        
        $result = $this->cache->put('test_key', 'test_value');
        
        $this->assertTrue($result);
    }

    public function test_put_returns_false_when_cache_disabled()
    {
        $config = $this->config;
        $config['cache']['enabled'] = false;
        $cache = new CoinMarketCapCache($config);
        
        $result = $cache->put('test_key', 'test_value');
        
        $this->assertFalse($result);
    }

    public function test_get_retrieves_value_from_cache()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('get')
            ->with('coinmarketcap:test_key', null)
            ->once()
            ->andReturn('cached_value');
        
        $result = $this->cache->get('test_key');
        
        $this->assertEquals('cached_value', $result);
    }

    public function test_get_returns_default_when_cache_disabled()
    {
        $config = $this->config;
        $config['cache']['enabled'] = false;
        $cache = new CoinMarketCapCache($config);
        
        $result = $cache->get('test_key', 'default_value');
        
        $this->assertEquals('default_value', $result);
    }

    public function test_forget_removes_value_from_cache()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('forget')
            ->with('coinmarketcap:test_key')
            ->once()
            ->andReturn(true);
        
        $result = $this->cache->forget('test_key');
        
        $this->assertTrue($result);
    }

    public function test_flush_clears_all_cache()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);
        
        $result = $this->cache->flush();
        
        $this->assertTrue($result);
    }

    public function test_ttl_selection_based_on_key()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        // Test cryptocurrency_quotes TTL
        Cache::shouldReceive('remember')
            ->with('coinmarketcap:cryptocurrency_quotes_latest', 60, Mockery::any())
            ->once()
            ->andReturn('value');
        
        $this->cache->remember('cryptocurrency_quotes_latest', function () {
            return 'value';
        });
        
        // Test global_metrics TTL
        Cache::shouldReceive('remember')
            ->with('coinmarketcap:global_metrics_test', 300, Mockery::any())
            ->once()
            ->andReturn('value');
        
        $this->cache->remember('global_metrics_test', function () {
            return 'value';
        });
    }

    public function test_custom_ttl_overrides_default()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('remember')
            ->with('coinmarketcap:custom_key', 1800, Mockery::any())
            ->once()
            ->andReturn('value');
        
        $this->cache->remember('custom_key', function () {
            return 'value';
        }, 1800);
    }

    public function test_default_ttl_for_unknown_key()
    {
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('remember')
            ->with('coinmarketcap:unknown_key', 300, Mockery::any())
            ->once()
            ->andReturn('value');
        
        $this->cache->remember('unknown_key', function () {
            return 'value';
        });
    }

    public function test_cache_key_generation_with_custom_prefix()
    {
        $config = $this->config;
        $config['cache']['prefix'] = 'custom_prefix';
        $cache = new CoinMarketCapCache($config);
        
        Cache::shouldReceive('store')
            ->with(null)
            ->andReturnSelf();
        
        Cache::shouldReceive('get')
            ->with('custom_prefix:test_key', null)
            ->once()
            ->andReturn('value');
        
        $cache->get('test_key');
    }
}