<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Services\GlobalMetricsService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Psr7\Response;
use Mockery;

class GlobalMetricsServiceTest extends TestCase
{
    private GlobalMetricsService $service;
    private CoinMarketCapClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->service = new GlobalMetricsService($this->mockClient);
    }

    public function test_get_latest_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                'btc_dominance' => 42.5,
                'eth_dominance' => 18.2,
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2500000000000.0,
                        'total_volume_24h' => 120000000000.0,
                        'total_volume_24h_change_percentage' => -3.5,
                    ]
                ],
                'last_updated' => '2023-01-01T00:00:00.000Z',
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getLatest();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(42.5, $result['data']['btc_dominance']);
    }

    public function test_get_latest_with_parameters()
    {
        $params = ['convert' => 'EUR'];
        
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                'btc_dominance' => 42.5,
                'quote' => [
                    'EUR' => [
                        'total_market_cap' => 2100000000000.0,
                        'total_volume_24h' => 100000000000.0,
                    ]
                ],
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/latest', $params)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getLatest($params);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_get_historical_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                'btc_dominance' => 45.2,
                'eth_dominance' => 16.8,
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2200000000000.0,
                        'total_volume_24h' => 95000000000.0,
                    ]
                ],
                'timestamp' => '2023-01-01T00:00:00.000Z',
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/historical', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getHistorical();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_get_historical_with_parameters()
    {
        $params = [
            'time_start' => '2023-01-01',
            'time_end' => '2023-01-31',
            'convert' => 'USD'
        ];
        
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => []
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/global-metrics/quotes/historical', $params)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getHistorical($params);
        $this->assertIsArray($result);
    }
}