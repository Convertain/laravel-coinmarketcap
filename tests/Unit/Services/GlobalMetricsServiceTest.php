<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\Services\GlobalMetricsService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Global Metrics Service Test
 *
 * Unit tests for GlobalMetricsService class covering global market metrics
 * endpoints and advanced analysis features.
 */
class GlobalMetricsServiceTest extends TestCase
{
    private $mockClient;
    private GlobalMetricsService $globalMetricsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->globalMetricsService = new GlobalMetricsService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testQuotesLatestReturnsTransformedGlobalMetrics(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                'active_cryptocurrencies' => 5000,
                'total_cryptocurrencies' => 6000,
                'active_market_pairs' => 25000,
                'active_exchanges' => 200,
                'total_exchanges' => 250,
                'eth_dominance' => 18.5,
                'btc_dominance' => 42.3,
                'eth_dominance_yesterday' => 18.2,
                'btc_dominance_yesterday' => 42.8,
                'eth_dominance_24h_percentage_change' => 0.3,
                'btc_dominance_24h_percentage_change' => -0.5,
                'defi_volume_24h' => 5000000000,
                'defi_market_cap' => 50000000000,
                'defi_24h_percentage_change' => 2.5,
                'stablecoin_volume_24h' => 20000000000,
                'stablecoin_market_cap' => 150000000000,
                'derivatives_volume_24h' => 100000000000,
                'last_updated' => '2023-01-01T00:00:00.000Z',
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000,
                        'total_volume_24h' => 50000000000,
                        'total_market_cap_yesterday' => 1950000000000,
                        'total_volume_24h_yesterday' => 45000000000,
                        'total_market_cap_yesterday_percentage_change' => 2.56,
                        'total_volume_24h_yesterday_percentage_change' => 11.11,
                        'last_updated' => '2023-01-01T00:00:00.000Z',
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->globalMetricsService->quotesLatest();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        $this->assertEquals(5000, $result['data']['active_cryptocurrencies']);
        $this->assertEquals(42.3, $result['data']['btc_dominance']);
        $this->assertArrayHasKey('quote', $result['data']);
        $this->assertArrayHasKey('USD', $result['data']['quote']);
    }

    public function testQuotesLatestWithCustomConvert(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'EUR'], 300)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => []]);

        $this->globalMetricsService->quotesLatest('EUR');
    }

    public function testQuotesLatestWithConvertId(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', [
                'convert' => 'USD',
                'convert_id' => '1,1027'
            ], 300)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => []]);

        $this->globalMetricsService->quotesLatest('USD', [1, 1027]);
    }

    public function testQuotesHistoricalWithRequiredParameters(): void
    {
        $timeStart = '2023-01-01T00:00:00.000Z';
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                'name' => 'Global Metrics',
                'symbol' => 'GLOBAL',
                'id' => null,
                'quotes' => [
                    [
                        'timestamp' => $timeStart,
                        'active_cryptocurrencies' => 5000,
                        'btc_dominance' => 42.3,
                        'quote' => [
                            'USD' => [
                                'total_market_cap' => 2000000000000,
                                'total_volume_24h' => 50000000000,
                                'last_updated' => $timeStart,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/historical', [
                'time_start' => $timeStart,
                'count' => 10,
                'interval' => '1d',
                'convert' => 'USD',
            ], 3600)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->globalMetricsService->quotesHistorical($timeStart);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('quotes', $result['data']);
        $this->assertCount(1, $result['data']['quotes']);
    }

    public function testQuotesHistoricalWithAllParameters(): void
    {
        $timeStart = '2023-01-01T00:00:00.000Z';
        $timeEnd = '2023-01-31T23:59:59.000Z';

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/historical', [
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
                'count' => 30,
                'interval' => '1d',
                'convert' => 'EUR',
                'convert_id' => '1',
                'aux' => 'market_cap_by_total_supply',
            ], 3600)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => ['quotes' => []]]);

        $this->globalMetricsService->quotesHistorical(
            timeStart: $timeStart,
            timeEnd: $timeEnd,
            count: 30,
            interval: '1d',
            convert: 'EUR',
            convertId: [1],
            aux: ['market_cap_by_total_supply']
        );
    }

    public function testGetDominanceMetrics(): void
    {
        $mockGlobalData = [
            'status' => ['error_code' => 0],
            'data' => [
                'btc_dominance' => 42.3,
                'btc_dominance_yesterday' => 42.8,
                'btc_dominance_24h_percentage_change' => -0.5,
                'eth_dominance' => 18.5,
                'eth_dominance_yesterday' => 18.2,
                'eth_dominance_24h_percentage_change' => 0.3,
                'quote' => ['USD' => []]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->once()
            ->andReturn($mockGlobalData);

        $result = $this->globalMetricsService->getDominanceMetrics();

        $this->assertArrayHasKey('dominance', $result);
        $this->assertArrayHasKey('bitcoin', $result['dominance']);
        $this->assertArrayHasKey('ethereum', $result['dominance']);
        $this->assertArrayHasKey('altcoins', $result['dominance']);
        
        $this->assertEquals(42.3, $result['dominance']['bitcoin']['current']);
        $this->assertEquals(18.5, $result['dominance']['ethereum']['current']);
        $this->assertEquals(39.2, $result['dominance']['altcoins']['current']); // 100 - 42.3 - 18.5
    }

    public function testGetMarketCapTiers(): void
    {
        $mockGlobalData = [
            'status' => ['error_code' => 0],
            'data' => [
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000, // $2T
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->once()
            ->andReturn($mockGlobalData);

        $result = $this->globalMetricsService->getMarketCapTiers();

        $this->assertArrayHasKey('market_cap_tiers', $result);
        $this->assertArrayHasKey('total_market_cap', $result);
        $this->assertEquals(2000000000000, $result['total_market_cap']);
        
        $tiers = $result['market_cap_tiers'];
        $this->assertArrayHasKey('large_cap', $tiers);
        $this->assertArrayHasKey('mid_cap', $tiers);
        $this->assertArrayHasKey('small_cap', $tiers);
    }

    public function testGetVolumeAnalysis(): void
    {
        $mockGlobalData = [
            'status' => ['error_code' => 0],
            'data' => [
                'derivatives_volume_24h' => 100000000000,
                'derivatives_volume_24h_reported' => 95000000000,
                'derivatives_24h_percentage_change' => 5.2,
                'defi_volume_24h' => 5000000000,
                'defi_volume_24h_reported' => 4800000000,
                'stablecoin_volume_24h' => 20000000000,
                'stablecoin_volume_24h_reported' => 19500000000,
                'quote' => [
                    'USD' => [
                        'total_volume_24h' => 50000000000,
                        'total_volume_24h_reported' => 48000000000,
                        'total_volume_24h_yesterday_percentage_change' => 10.5,
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->once()
            ->andReturn($mockGlobalData);

        $result = $this->globalMetricsService->getVolumeAnalysis();

        $this->assertArrayHasKey('volume_analysis', $result);
        $analysis = $result['volume_analysis'];
        
        $this->assertArrayHasKey('spot_volume', $analysis);
        $this->assertArrayHasKey('derivatives_volume', $analysis);
        $this->assertArrayHasKey('defi_volume', $analysis);
        $this->assertArrayHasKey('stablecoin_volume', $analysis);
        
        $this->assertEquals(100000000000, $analysis['derivatives_volume']['total_24h']);
        $this->assertEquals(5.2, $analysis['derivatives_volume']['change_24h']);
    }

    public function testGetTrendAnalysis(): void
    {
        $mockHistoricalData = [
            'status' => ['error_code' => 0],
            'data' => [
                'quotes' => [
                    [
                        'timestamp' => '2023-01-01T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1000000000000, 'total_volume_24h' => 30000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-02T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1100000000000, 'total_volume_24h' => 35000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-03T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1200000000000, 'total_volume_24h' => 40000000000]]
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/historical', Mockery::type('array'), 3600)
            ->once()
            ->andReturn($mockHistoricalData);

        $result = $this->globalMetricsService->getTrendAnalysis(3);

        $this->assertArrayHasKey('trend_analysis', $result);
        $analysis = $result['trend_analysis'];
        
        $this->assertArrayHasKey('market_cap_trend', $analysis);
        $this->assertArrayHasKey('volume_trend', $analysis);
        $this->assertArrayHasKey('volatility', $analysis);
        $this->assertArrayHasKey('momentum', $analysis);
        
        $this->assertEquals('upward', $analysis['market_cap_trend']);
    }

    public function testGetSentimentAnalysis(): void
    {
        $mockGlobalData = [
            'status' => ['error_code' => 0],
            'data' => [
                'quote' => [
                    'USD' => [
                        'total_market_cap_yesterday_percentage_change' => 8.5,
                        'total_volume_24h_yesterday_percentage_change' => 15.2,
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', ['convert' => 'USD'], 300)
            ->once()
            ->andReturn($mockGlobalData);

        $result = $this->globalMetricsService->getSentimentAnalysis();

        $this->assertArrayHasKey('sentiment', $result);
        $sentiment = $result['sentiment'];
        
        $this->assertArrayHasKey('overall', $sentiment);
        $this->assertArrayHasKey('market_cap_change_24h', $sentiment);
        $this->assertArrayHasKey('volume_change_24h', $sentiment);
        $this->assertArrayHasKey('fear_greed_index', $sentiment);
        
        $this->assertEquals('very_positive', $sentiment['overall']); // 8.5% > 5%
        $this->assertEquals(8.5, $sentiment['market_cap_change_24h']);
        $this->assertIsInt($sentiment['fear_greed_index']);
        $this->assertGreaterThanOrEqual(0, $sentiment['fear_greed_index']);
        $this->assertLessThanOrEqual(100, $sentiment['fear_greed_index']);
    }
}