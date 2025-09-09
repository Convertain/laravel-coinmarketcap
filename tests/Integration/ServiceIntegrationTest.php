<?php

namespace Convertain\CoinMarketCap\Tests\Integration;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Services\ExchangeService;
use Convertain\CoinMarketCap\Services\GlobalMetricsService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Credit\CreditManager;
use Convertain\CoinMarketCap\Cache\CoinMarketCapCache;

class ServiceIntegrationTest extends TestCase
{
    private CoinMarketCapClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = $this->app->make(CoinMarketCapClient::class);
    }

    public function test_cryptocurrency_service_integration()
    {
        $service = new CryptocurrencyService($this->client);
        
        $this->assertInstanceOf(CryptocurrencyService::class, $service);
        
        // Test that service methods exist and are callable
        $this->assertTrue(method_exists($service, 'getListings'));
        $this->assertTrue(method_exists($service, 'getQuotes'));
        $this->assertTrue(method_exists($service, 'getInfo'));
        $this->assertTrue(method_exists($service, 'getMap'));
    }

    public function test_exchange_service_integration()
    {
        $service = new ExchangeService($this->client);
        
        $this->assertInstanceOf(ExchangeService::class, $service);
        
        // Test that service methods exist and are callable
        $this->assertTrue(method_exists($service, 'getListings'));
        $this->assertTrue(method_exists($service, 'getQuotes'));
        $this->assertTrue(method_exists($service, 'getInfo'));
        $this->assertTrue(method_exists($service, 'getMap'));
    }

    public function test_global_metrics_service_integration()
    {
        $service = new GlobalMetricsService($this->client);
        
        $this->assertInstanceOf(GlobalMetricsService::class, $service);
        
        // Test that service methods exist and are callable
        $this->assertTrue(method_exists($service, 'getLatest'));
        $this->assertTrue(method_exists($service, 'getHistorical'));
    }

    public function test_credit_manager_integration()
    {
        $config = config('coinmarketcap');
        $creditManager = new CreditManager($config);
        
        $this->assertInstanceOf(CreditManager::class, $creditManager);
        
        // Test credit tracking works
        $result = $creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $this->assertTrue($result);
        
        $stats = $creditManager->getUsageStats();
        $this->assertEquals(1, $stats['used']);
    }

    public function test_cache_integration()
    {
        $config = config('coinmarketcap');
        $cache = new CoinMarketCapCache($config);
        
        $this->assertInstanceOf(CoinMarketCapCache::class, $cache);
        
        // Test cache functionality
        $result = $cache->remember('test_integration', function () {
            return 'cached_data';
        });
        
        $this->assertEquals('cached_data', $result);
    }

    public function test_services_with_credit_manager()
    {
        $config = config('coinmarketcap');
        $creditManager = new CreditManager($config);
        
        // Test that credit manager can track costs for different services
        $cryptoCost = $creditManager->getCreditCost('cryptocurrency_listings_latest');
        $exchangeCost = $creditManager->getCreditCost('exchange_listings_latest');
        $globalCost = $creditManager->getCreditCost('global_metrics_quotes_latest');
        
        $this->assertEquals(1, $cryptoCost);
        $this->assertEquals(1, $exchangeCost);
        $this->assertEquals(1, $globalCost);
    }

    public function test_services_with_cache()
    {
        $config = config('coinmarketcap');
        $cache = new CoinMarketCapCache($config);
        
        // Test different cache TTLs for different service types
        $cryptoTtl = $this->getExpectedTtl($cache, 'cryptocurrency_quotes');
        $globalTtl = $this->getExpectedTtl($cache, 'global_metrics');
        
        // These should have different TTL values based on config
        $this->assertNotEquals($cryptoTtl, $globalTtl);
    }

    public function test_full_service_stack_integration()
    {
        // Test the complete stack: Client -> Service -> Credit -> Cache
        $config = config('coinmarketcap');
        $cache = new CoinMarketCapCache($config);
        $creditManager = new CreditManager($config);
        $service = new CryptocurrencyService($this->client);
        
        // Verify all components work together
        $this->assertInstanceOf(CoinMarketCapClient::class, $this->client);
        $this->assertInstanceOf(CoinMarketCapCache::class, $cache);
        $this->assertInstanceOf(CreditManager::class, $creditManager);
        $this->assertInstanceOf(CryptocurrencyService::class, $service);
        
        // Test credit consumption tracking
        $initialStats = $creditManager->getUsageStats();
        $creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $newStats = $creditManager->getUsageStats();
        
        $this->assertEquals($initialStats['used'] + 1, $newStats['used']);
    }

    public function test_configuration_propagation()
    {
        // Test that configuration is properly propagated through the stack
        $client = $this->app->make(CoinMarketCapClient::class);
        $clientConfig = $client->getConfig();
        $appConfig = config('coinmarketcap');
        
        $this->assertEquals($appConfig['api']['key'], $clientConfig['api']['key']);
        $this->assertEquals($appConfig['api']['base_url'], $clientConfig['api']['base_url']);
        $this->assertEquals($appConfig['api']['timeout'], $clientConfig['api']['timeout']);
    }

    /**
     * Helper method to get expected TTL for cache testing
     */
    private function getExpectedTtl(CoinMarketCapCache $cache, string $keyPattern): int
    {
        // Use reflection to access private method for testing
        $reflection = new \ReflectionClass($cache);
        $method = $reflection->getMethod('getTtl');
        $method->setAccessible(true);
        
        return $method->invoke($cache, $keyPattern);
    }
}