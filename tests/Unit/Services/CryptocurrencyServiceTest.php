<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Psr7\Response;
use Mockery;

class CryptocurrencyServiceTest extends TestCase
{
    private CryptocurrencyService $service;
    private CoinMarketCapClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->service = new CryptocurrencyService($this->mockClient);
    }

    public function test_get_listings_returns_array()
    {
        // Mock API response
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'slug' => 'bitcoin',
                    'cmc_rank' => 1,
                    'quote' => [
                        'USD' => [
                            'price' => 45000.0,
                            'market_cap' => 850000000000.0,
                            'volume_24h' => 30000000000.0,
                        ]
                    ]
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/listings/latest', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getListings();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Bitcoin', $result['data'][0]['name']);
    }

    public function test_get_listings_with_parameters()
    {
        $params = ['limit' => 10, 'convert' => 'USD'];
        
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => []
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/listings/latest', $params)
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
                'BTC' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'quote' => [
                        'USD' => [
                            'price' => 45000.0,
                            'market_cap' => 850000000000.0,
                        ]
                    ]
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/quotes/latest', [])
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
                'BTC' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'description' => 'Bitcoin is a cryptocurrency.',
                    'logo' => 'https://example.com/logo.png',
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/info', [])
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
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'slug' => 'bitcoin',
                    'is_active' => 1,
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/map', [])
            ->once()
            ->andReturn($mockResponse);

        $result = $this->service->getMap();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}