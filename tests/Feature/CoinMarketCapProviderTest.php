<?php

namespace Convertain\CoinMarketCap\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\CoinMarketCapProvider;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * CoinMarketCap Provider Integration Test
 *
 * Feature tests for the main CoinMarketCapProvider class demonstrating
 * integration between different services and components.
 */
class CoinMarketCapProviderTest extends TestCase
{
    private $mockClient;
    private CoinMarketCapProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->provider = new CoinMarketCapProvider($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testProviderReturnsCorrectServiceInstances(): void
    {
        // Test that all service getters return the correct instances
        $exchangeService = $this->provider->exchanges();
        $globalMetricsService = $this->provider->globalMetrics();
        $fiatService = $this->provider->fiat();

        $this->assertInstanceOf(\Convertain\CoinMarketCap\Services\ExchangeService::class, $exchangeService);
        $this->assertInstanceOf(\Convertain\CoinMarketCap\Services\GlobalMetricsService::class, $globalMetricsService);
        $this->assertInstanceOf(\Convertain\CoinMarketCap\Services\FiatService::class, $fiatService);
    }

    public function testProviderServicesSingleton(): void
    {
        // Test that services are singletons (same instance returned)
        $exchangeService1 = $this->provider->exchanges();
        $exchangeService2 = $this->provider->exchanges();

        $globalMetricsService1 = $this->provider->globalMetrics();
        $globalMetricsService2 = $this->provider->globalMetrics();

        $fiatService1 = $this->provider->fiat();
        $fiatService2 = $this->provider->fiat();

        $this->assertSame($exchangeService1, $exchangeService2);
        $this->assertSame($globalMetricsService1, $globalMetricsService2);
        $this->assertSame($fiatService1, $fiatService2);
    }

    public function testGetClientReturnsCorrectInstance(): void
    {
        $client = $this->provider->getClient();
        
        $this->assertSame($this->mockClient, $client);
    }

    public function testIntegratedExchangeWorkflow(): void
    {
        // Mock responses for a typical exchange workflow
        $mapResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 1, 'name' => 'Test Exchange', 'slug' => 'test-exchange', 'is_active' => 1, 'is_listed' => 1]
            ]
        ];

        $infoResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'description' => 'Test exchange description',
                    'website' => 'https://test-exchange.com',
                    'countries' => ['US'],
                    'fiats' => ['USD'],
                ]
            ]
        ];

        $quotesResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'num_market_pairs' => 100,
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 1000000000,
                            'volume_24h_adjusted' => 950000000,
                            'last_updated' => '2023-01-01T00:00:00.000Z',
                        ]
                    ]
                ]
            ]
        ];

        // Set up client expectations
        $this->mockClient->shouldReceive('get')
            ->with('/exchange/map', Mockery::type('array'), 86400)
            ->once()
            ->andReturn($mapResponse);

        $this->mockClient->shouldReceive('get')
            ->with('/exchange/info', Mockery::type('array'), 86400)
            ->once()
            ->andReturn($infoResponse);

        $this->mockClient->shouldReceive('get')
            ->with('/exchange/quotes/latest', Mockery::type('array'), 60)
            ->once()
            ->andReturn($quotesResponse);

        // Execute workflow
        $exchangeService = $this->provider->exchanges();

        // Get exchange map
        $map = $exchangeService->map();
        $this->assertCount(1, $map['data']);
        $this->assertEquals('Test Exchange', $map['data'][0]['name']);

        // Get exchange info for the first exchange
        $info = $exchangeService->info(id: [1]);
        $this->assertArrayHasKey('1', $info['data']);
        $this->assertEquals('Test exchange description', $info['data']['1']['description']);

        // Get exchange quotes
        $quotes = $exchangeService->quotesLatest(id: [1]);
        $this->assertArrayHasKey('1', $quotes['data']);
        $this->assertEquals(1000000000, $quotes['data']['1']['quote']['USD']['volume_24h']);
    }

    public function testIntegratedGlobalMetricsWorkflow(): void
    {
        // Mock response for global metrics
        $latestResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                'active_cryptocurrencies' => 5000,
                'btc_dominance' => 42.5,
                'eth_dominance' => 18.3,
                'btc_dominance_yesterday' => 42.8,
                'eth_dominance_yesterday' => 18.1,
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000,
                        'total_volume_24h' => 50000000000,
                        'total_market_cap_yesterday_percentage_change' => 2.5,
                        'total_volume_24h_yesterday_percentage_change' => 8.7,
                    ]
                ]
            ]
        ];

        $this->mockClient->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->times(4) // Called multiple times by different analysis methods
            ->andReturn($latestResponse);

        // Execute workflow
        $globalService = $this->provider->globalMetrics();

        // Get latest metrics
        $latest = $globalService->quotesLatest();
        $this->assertEquals(5000, $latest['data']['active_cryptocurrencies']);
        $this->assertEquals(42.5, $latest['data']['btc_dominance']);

        // Get dominance analysis
        $dominance = $globalService->getDominanceMetrics();
        $this->assertArrayHasKey('dominance', $dominance);
        $this->assertEquals(42.5, $dominance['dominance']['bitcoin']['current']);
        $this->assertEquals(18.3, $dominance['dominance']['ethereum']['current']);
        $this->assertEquals(39.2, $dominance['dominance']['altcoins']['current']); // 100 - 42.5 - 18.3

        // Get market cap tiers
        $tiers = $globalService->getMarketCapTiers();
        $this->assertArrayHasKey('market_cap_tiers', $tiers);
        $this->assertEquals(2000000000000, $tiers['total_market_cap']);

        // Get sentiment analysis
        $sentiment = $globalService->getSentimentAnalysis();
        $this->assertArrayHasKey('sentiment', $sentiment);
        $this->assertEquals('positive', $sentiment['sentiment']['overall']); // 2.5% change
    }

    public function testIntegratedFiatWorkflow(): void
    {
        // Mock response for fiat currencies
        $fiatResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
                ['id' => 2792, 'name' => 'Japanese Yen', 'sign' => '¥', 'symbol' => 'JPY'],
                ['id' => 2791, 'name' => 'British Pound Sterling', 'sign' => '£', 'symbol' => 'GBP'],
                ['id' => 2784, 'name' => 'Canadian Dollar', 'sign' => '$', 'symbol' => 'CAD'],
            ]
        ];

        $this->mockClient->shouldReceive('get')
            ->with('/fiat/map', Mockery::type('array'), 86400)
            ->times(5) // Called by different methods
            ->andReturn($fiatResponse);

        // Execute workflow
        $fiatService = $this->provider->fiat();

        // Get all currencies
        $allCurrencies = $fiatService->getAllCurrencies();
        $this->assertCount(5, $allCurrencies['data']);

        // Check if specific currency is supported
        $isUsdSupported = $fiatService->isCurrencySupported('USD');
        $this->assertTrue($isUsdSupported);

        $isXyzSupported = $fiatService->isCurrencySupported('XYZ');
        $this->assertFalse($isXyzSupported);

        // Get currency by symbol
        $usd = $fiatService->getCurrencyBySymbol('USD');
        $this->assertNotNull($usd);
        $this->assertEquals(2781, $usd['id']);
        $this->assertEquals('United States Dollar', $usd['name']);

        // Get major currencies
        $majorCurrencies = $fiatService->getMajorCurrencies();
        $majorSymbols = array_column($majorCurrencies['data'], 'symbol');
        $this->assertContains('USD', $majorSymbols);
        $this->assertContains('EUR', $majorSymbols);
        $this->assertContains('JPY', $majorSymbols);
        $this->assertContains('GBP', $majorSymbols);
        $this->assertContains('CAD', $majorSymbols);

        // Get regional currencies
        $regionalCurrencies = $fiatService->getRegionalCurrencies();
        $this->assertArrayHasKey('North America', $regionalCurrencies['data']);
        $this->assertArrayHasKey('Europe', $regionalCurrencies['data']);
        
        $naSymbols = array_column($regionalCurrencies['data']['North America'], 'symbol');
        $this->assertContains('USD', $naSymbols);
        $this->assertContains('CAD', $naSymbols);
        
        $euSymbols = array_column($regionalCurrencies['data']['Europe'], 'symbol');
        $this->assertContains('EUR', $euSymbols);
        $this->assertContains('GBP', $euSymbols);
    }

    public function testErrorHandlingAcrossServices(): void
    {
        // Mock error response
        $errorResponse = [
            'status' => ['error_code' => 400, 'error_message' => 'Bad request'],
        ];

        $this->mockClient->shouldReceive('get')
            ->andReturn($errorResponse);

        $exchangeService = $this->provider->exchanges();
        $globalService = $this->provider->globalMetrics();
        $fiatService = $this->provider->fiat();

        // All services should handle errors gracefully
        $exchangeMap = $exchangeService->map();
        $this->assertEquals(400, $exchangeMap['status']['error_code']);
        $this->assertEmpty($exchangeMap['data']);

        $globalMetrics = $globalService->quotesLatest();
        $this->assertEquals(400, $globalMetrics['status']['error_code']);

        $fiatMap = $fiatService->map();
        $this->assertEquals(400, $fiatMap['status']['error_code']);
        $this->assertEmpty($fiatMap['data']);
    }
}