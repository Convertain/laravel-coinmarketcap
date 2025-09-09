<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * CoinMarketCap Client Test
 *
 * Unit tests for CoinMarketCapClient class covering API communication,
 * caching, credit management, and error handling.
 */
class CoinMarketCapClientTest extends TestCase
{
    private array $config;
    private CoinMarketCapClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'api' => [
                'base_url' => 'https://pro-api.coinmarketcap.com/v2',
                'key' => 'test-api-key',
                'timeout' => 30,
            ],
            'cache' => [
                'enabled' => true,
                'prefix' => 'coinmarketcap',
            ],
            'credits' => [
                'tracking_enabled' => true,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'exchange_map' => 1,
                ],
            ],
            'plan' => [
                'credits_per_month' => 10000,
            ],
            'logging' => [
                'log_requests' => true,
                'log_responses' => false,
                'log_credits' => true,
            ],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testConstructorSetsUpHttpClientCorrectly(): void
    {
        $client = new CoinMarketCapClient($this->config);
        
        $this->assertInstanceOf(CoinMarketCapClient::class, $client);
        $this->assertEquals(0, $client->getCreditsUsed());
    }

    public function testGetCreditsUsed(): void
    {
        $client = new CoinMarketCapClient($this->config);
        
        $this->assertEquals(0, $client->getCreditsUsed());
    }

    public function testResetCredits(): void
    {
        $client = new CoinMarketCapClient($this->config);
        
        // Credits start at 0
        $this->assertEquals(0, $client->getCreditsUsed());
        
        // Reset should keep them at 0
        $client->resetCredits();
        $this->assertEquals(0, $client->getCreditsUsed());
    }

    public function testGetCacheKey(): void
    {
        $client = new CoinMarketCapClient($this->config);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);
        
        $key1 = $method->invoke($client, '/test/endpoint', []);
        $key2 = $method->invoke($client, '/test/endpoint', ['param' => 'value']);
        $key3 = $method->invoke($client, '/test/endpoint', ['param' => 'value']);
        
        $this->assertStringContains('coinmarketcap:', $key1);
        $this->assertStringContains('test_endpoint', $key1);
        $this->assertNotEquals($key1, $key2);
        $this->assertEquals($key2, $key3); // Same parameters should generate same key
    }

    public function testCheckCreditLimitWithTrackingDisabled(): void
    {
        $config = $this->config;
        $config['credits']['tracking_enabled'] = false;
        
        Log::shouldReceive('warning')->never();
        
        $client = new CoinMarketCapClient($config);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('checkCreditLimit');
        $method->setAccessible(true);
        
        $method->invoke($client);
        
        // Should not throw any exceptions or log warnings
        $this->assertTrue(true);
    }

    public function testTrackCreditUsageWithTrackingDisabled(): void
    {
        $config = $this->config;
        $config['credits']['tracking_enabled'] = false;
        
        Log::shouldReceive('info')->never();
        
        $client = new CoinMarketCapClient($config);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('trackCreditUsage');
        $method->setAccessible(true);
        
        $method->invoke($client, '/exchange/map');
        
        $this->assertEquals(0, $client->getCreditsUsed());
    }

    public function testTrackCreditUsageWithTrackingEnabled(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('CoinMarketCap API credits consumed', Mockery::type('array'));
        
        $client = new CoinMarketCapClient($this->config);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('trackCreditUsage');
        $method->setAccessible(true);
        
        $method->invoke($client, 'exchange/map');
        
        $this->assertEquals(1, $client->getCreditsUsed());
    }

    public function testHandleApiError(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('CoinMarketCap API error', Mockery::type('array'));
        
        $client = new CoinMarketCapClient($this->config);
        
        $mockResponse = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn(400);
        $mockResponse->shouldReceive('getBody->getContents')->andReturn('{"error":"Bad request"}');
        
        $exception = Mockery::mock(RequestException::class);
        $exception->shouldReceive('getMessage')->andReturn('HTTP 400 Bad Request');
        $exception->shouldReceive('hasResponse')->andReturn(true);
        $exception->shouldReceive('getResponse')->andReturn($mockResponse);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('handleApiError');
        $method->setAccessible(true);
        
        $method->invoke($client, $exception, '/test/endpoint', ['param' => 'value']);
        
        // Should log the error without throwing
        $this->assertTrue(true);
    }

    /**
     * Test that client properly formats requests and handles responses
     * This is a more integration-style test that would require mocking the HTTP client
     * For now, we'll create a basic structure test
     */
    public function testClientStructureAndMethods(): void
    {
        $client = new CoinMarketCapClient($this->config);
        
        // Test that all required methods exist
        $this->assertTrue(method_exists($client, 'get'));
        $this->assertTrue(method_exists($client, 'getCreditsUsed'));
        $this->assertTrue(method_exists($client, 'resetCredits'));
    }

    /**
     * Test configuration validation
     */
    public function testConfigurationHandling(): void
    {
        // Test with minimal config
        $minimalConfig = [
            'api' => [
                'base_url' => 'https://test.api.com',
                'key' => 'test-key',
                'timeout' => 30,
            ],
            'cache' => ['enabled' => false, 'prefix' => 'test'],
            'credits' => ['tracking_enabled' => false, 'costs' => []],
            'plan' => ['credits_per_month' => 1000],
            'logging' => ['log_requests' => false, 'log_responses' => false, 'log_credits' => false],
        ];
        
        $client = new CoinMarketCapClient($minimalConfig);
        $this->assertInstanceOf(CoinMarketCapClient::class, $client);
    }
}