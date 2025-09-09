<?php

namespace Convertain\CoinMarketCap\Tests\Feature;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Services\ExchangeService;
use Convertain\CoinMarketCap\Services\GlobalMetricsService;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;
use Convertain\CoinMarketCap\Transformers\ExchangeTransformer;
use Convertain\CoinMarketCap\Transformers\GlobalMetricsTransformer;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Psr7\Response;
use Mockery;

class ApiEndpointsTest extends TestCase
{
    private CoinMarketCapClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
    }

    public function test_cryptocurrency_listings_endpoint_flow()
    {
        // Mock API response
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => ''],
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
                            'percent_change_1h' => 0.5,
                            'percent_change_24h' => -2.3,
                            'percent_change_7d' => 8.7,
                        ]
                    ],
                    'last_updated' => '2023-01-01T00:00:00.000Z',
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/listings/latest', ['limit' => 10])
            ->once()
            ->andReturn($mockResponse);

        // Test complete flow: Service -> Transformer
        $service = new CryptocurrencyService($this->mockClient);
        $transformer = new CryptocurrencyTransformer();
        
        $apiResponse = $service->getListings(['limit' => 10]);
        $transformedData = $transformer->transformListings($apiResponse['data']);
        
        $this->assertIsArray($apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertEquals(0, $apiResponse['status']['error_code']);
        
        $this->assertIsArray($transformedData);
        $this->assertCount(1, $transformedData);
        $this->assertEquals('Bitcoin', $transformedData[0]['name']);
        $this->assertEquals(45000.0, $transformedData[0]['price']);
    }

    public function test_exchange_listings_endpoint_flow()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => ''],
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
                    ],
                    'last_updated' => '2023-01-01T00:00:00.000Z',
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/listings/latest', ['sort' => 'volume_24h'])
            ->once()
            ->andReturn($mockResponse);

        $service = new ExchangeService($this->mockClient);
        $transformer = new ExchangeTransformer();
        
        $apiResponse = $service->getListings(['sort' => 'volume_24h']);
        $transformedData = $transformer->transformListings($apiResponse['data']);
        
        $this->assertIsArray($apiResponse);
        $this->assertEquals(0, $apiResponse['status']['error_code']);
        
        $this->assertIsArray($transformedData);
        $this->assertCount(1, $transformedData);
        $this->assertEquals('Binance', $transformedData[0]['name']);
        $this->assertEquals(15000000000.0, $transformedData[0]['spot_volume_usd']);
    }

    public function test_global_metrics_endpoint_flow()
    {
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => ''],
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

        $service = new GlobalMetricsService($this->mockClient);
        $transformer = new GlobalMetricsTransformer();
        
        $apiResponse = $service->getLatest();
        $transformedData = $transformer->transform($apiResponse['data']);
        
        $this->assertIsArray($apiResponse);
        $this->assertEquals(0, $apiResponse['status']['error_code']);
        
        $this->assertIsArray($transformedData);
        $this->assertEquals(42.5, $transformedData['btc_dominance']);
        $this->assertEquals(2500000000000.0, $transformedData['total_market_cap']);
    }

    public function test_api_error_handling()
    {
        $mockResponse = new Response(400, [], json_encode([
            'status' => [
                'error_code' => 400,
                'error_message' => 'Bad Request',
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/listings/latest', [])
            ->once()
            ->andReturn($mockResponse);

        $service = new CryptocurrencyService($this->mockClient);
        $response = $service->getListings();
        
        $this->assertIsArray($response);
        $this->assertEquals(400, $response['status']['error_code']);
        $this->assertEquals('Bad Request', $response['status']['error_message']);
    }

    public function test_multiple_endpoints_workflow()
    {
        // Test a workflow that uses multiple endpoints
        
        // 1. Get cryptocurrency map
        $mapResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC'],
                ['id' => 1027, 'name' => 'Ethereum', 'symbol' => 'ETH'],
            ]
        ]));

        // 2. Get quotes for those cryptocurrencies
        $quotesResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'quote' => ['USD' => ['price' => 45000.0]]
                ],
                '1027' => [
                    'id' => 1027,
                    'name' => 'Ethereum',
                    'symbol' => 'ETH',
                    'quote' => ['USD' => ['price' => 3000.0]]
                ]
            ]
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/map', [])
            ->once()
            ->andReturn($mapResponse);

        $this->mockClient
            ->shouldReceive('get')
            ->with('/cryptocurrency/quotes/latest', ['id' => '1,1027'])
            ->once()
            ->andReturn($quotesResponse);

        $service = new CryptocurrencyService($this->mockClient);
        
        // Step 1: Get map
        $mapData = $service->getMap();
        $this->assertCount(2, $mapData['data']);
        
        // Step 2: Extract IDs and get quotes
        $ids = implode(',', array_column($mapData['data'], 'id'));
        $quotesData = $service->getQuotes(['id' => $ids]);
        
        $this->assertArrayHasKey('1', $quotesData['data']);
        $this->assertArrayHasKey('1027', $quotesData['data']);
        $this->assertEquals(45000.0, $quotesData['data']['1']['quote']['USD']['price']);
    }

    public function test_parameter_validation_scenarios()
    {
        // Test various parameter combinations
        $testCases = [
            ['limit' => 10, 'convert' => 'USD'],
            ['start' => 1, 'limit' => 5000],
            ['symbol' => 'BTC,ETH'],
            ['id' => '1,1027'],
        ];

        foreach ($testCases as $index => $params) {
            $mockResponse = new Response(200, [], json_encode([
                'status' => ['error_code' => 0],
                'data' => []
            ]));

            $this->mockClient
                ->shouldReceive('get')
                ->with('/cryptocurrency/listings/latest', $params)
                ->once()
                ->andReturn($mockResponse);

            $service = new CryptocurrencyService($this->mockClient);
            $response = $service->getListings($params);
            
            $this->assertEquals(0, $response['status']['error_code']);
        }
    }

    public function test_endpoint_response_consistency()
    {
        // Test that all endpoints return consistent response structure
        $endpoints = [
            ['service' => CryptocurrencyService::class, 'method' => 'getListings', 'endpoint' => '/cryptocurrency/listings/latest'],
            ['service' => ExchangeService::class, 'method' => 'getListings', 'endpoint' => '/exchange/listings/latest'],
            ['service' => GlobalMetricsService::class, 'method' => 'getLatest', 'endpoint' => '/global-metrics/quotes/latest'],
        ];

        foreach ($endpoints as $endpointConfig) {
            $mockResponse = new Response(200, [], json_encode([
                'status' => ['error_code' => 0, 'error_message' => ''],
                'data' => []
            ]));

            $this->mockClient
                ->shouldReceive('get')
                ->with($endpointConfig['endpoint'], [])
                ->once()
                ->andReturn($mockResponse);

            $service = new $endpointConfig['service']($this->mockClient);
            $response = $service->{$endpointConfig['method']}();
            
            // All responses should have consistent structure
            $this->assertArrayHasKey('status', $response);
            $this->assertArrayHasKey('data', $response);
            $this->assertArrayHasKey('error_code', $response['status']);
            $this->assertEquals(0, $response['status']['error_code']);
        }
    }
}