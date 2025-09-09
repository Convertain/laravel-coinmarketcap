<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Services\ExchangeService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Psr7\Response;
use Mockery;

class ExchangeServiceTest extends TestCase
{
    private ExchangeService $service;
    private CoinMarketCapClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->service = new ExchangeService($this->mockClient);
    }

    public function test_get_listings_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 270,
                    'name' => 'Binance',
                    'slug' => 'binance',
                    'num_market_pairs' => 1000,
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 15000000000.0,
                            'volume_24h_change_percentage' => 5.2,
                        ]
                    ]
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/listings/latest', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getListings();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Binance', $result['data'][0]['name']);
    }

    public function test_get_listings_with_parameters()
    {
        $params = ['limit' => 50, 'sort' => 'volume_24h'];
        
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => []
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/listings/latest', $params)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getListings($params);
        $this->assertIsArray($result);
    }

    public function test_get_quotes_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                '270' => [
                    'id' => 270,
                    'name' => 'Binance',
                    'slug' => 'binance',
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 15000000000.0,
                        ]
                    ]
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/quotes/latest', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getQuotes();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_get_info_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                '270' => [
                    'id' => 270,
                    'name' => 'Binance',
                    'slug' => 'binance',
                    'description' => 'Binance is a cryptocurrency exchange.',
                    'logo' => 'https://example.com/binance-logo.png',
                    'urls' => ['website' => 'https://binance.com'],
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/info', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getInfo();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_get_map_returns_array()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 270,
                    'name' => 'Binance',
                    'slug' => 'binance',
                    'is_active' => 1,
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/map', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getMap();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}