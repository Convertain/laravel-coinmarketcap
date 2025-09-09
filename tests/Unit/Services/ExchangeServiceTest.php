<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\Services\ExchangeService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Exchange Service Test
 *
 * Unit tests for ExchangeService class covering all exchange endpoints
 * and edge cases including error handling and data validation.
 */
class ExchangeServiceTest extends TestCase
{
    private $mockClient;
    private ExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->exchangeService = new ExchangeService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testMapReturnsTransformedExchangeMap(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'is_active' => 1,
                    'is_listed' => 1,
                    'first_historical_data' => '2021-01-01T00:00:00.000Z',
                    'last_historical_data' => '2023-12-31T23:59:59.000Z',
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/map', [
                'start' => 1,
                'limit' => 100,
                'sort' => 'id',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->exchangeService->map();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Test Exchange', $result['data'][0]['name']);
    }

    public function testMapWithCustomParameters(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/map', [
                'start' => 10,
                'limit' => 50,
                'sort' => 'volume_24h',
                'slug' => 'binance,coinbase',
                'aux' => 'first_historical_data',
            ], 86400)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => []]);

        $this->exchangeService->map(
            start: 10,
            limit: 50,
            sort: 'volume_24h',
            slug: ['binance', 'coinbase'],
            aux: ['first_historical_data']
        );
    }

    public function testInfoRequiresIdOrSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either id or slug parameter is required');

        $this->exchangeService->info();
    }

    public function testInfoWithValidParameters(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'description' => 'Test exchange description',
                    'logo' => 'https://example.com/logo.png',
                    'website' => 'https://example.com',
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/info', ['id' => '1,2'], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->exchangeService->info(id: [1, 2]);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('1', $result['data']);
    }

    public function testListingsLatestWithDefaults(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'num_market_pairs' => 100,
                    'exchange_score' => 8.5,
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 1000000,
                            'volume_24h_adjusted' => 950000,
                            'last_updated' => '2023-01-01T00:00:00.000Z',
                        ]
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/listings/latest', [
                'start' => 1,
                'limit' => 100,
                'sort' => 'volume_24h',
                'sort_dir' => 'desc',
                'market_type' => 'all',
                'category' => 'all',
            ], 300)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->exchangeService->listingsLatest();

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('Test Exchange', $result['data'][0]['name']);
        $this->assertArrayHasKey('quote', $result['data'][0]);
    }

    public function testQuotesLatestRequiresIdOrSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either id or slug parameter is required');

        $this->exchangeService->quotesLatest();
    }

    public function testMarketPairsLatestRequiresIdOrSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either id or slug parameter is required');

        $this->exchangeService->marketPairsLatest();
    }

    public function testMarketPairsLatestWithValidParameters(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                'id' => 1,
                'name' => 'Test Exchange',
                'slug' => 'test-exchange',
                'num_market_pairs' => 2,
                'market_pairs' => [
                    [
                        'exchange_id' => 1,
                        'exchange_name' => 'Test Exchange',
                        'market_pair' => 'BTC/USD',
                        'market_pair_base' => [
                            'currency_id' => 1,
                            'currency_symbol' => 'BTC',
                            'currency_type' => 'cryptocurrency',
                            'exchange_symbol' => 'BTC',
                        ],
                        'market_pair_quote' => [
                            'currency_id' => 2781,
                            'currency_symbol' => 'USD',
                            'currency_type' => 'fiat',
                            'exchange_symbol' => 'USD',
                        ],
                        'quote' => [
                            'USD' => [
                                'price' => 50000,
                                'volume_24h_base' => 100,
                                'volume_24h_quote' => 5000000,
                                'last_updated' => '2023-01-01T00:00:00.000Z',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/market-pairs/latest', [
                'id' => '1',
                'start' => 1,
                'limit' => 100,
                'category' => 'spot',
                'fee_type' => 'all',
                'convert' => 'USD',
            ], 180)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->exchangeService->marketPairsLatest(id: [1]);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Test Exchange', $result['data']['name']);
        $this->assertArrayHasKey('market_pairs', $result['data']);
        $this->assertCount(1, $result['data']['market_pairs']);
    }

    public function testQuotesHistoricalRequiresIdOrSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either id or slug parameter is required');

        $this->exchangeService->quotesHistorical();
    }

    public function testListingsHistoricalWithTimestamp(): void
    {
        $timestamp = '2023-01-01T00:00:00.000Z';
        
        $this->mockClient
            ->shouldReceive('get')
            ->with('/exchange/listings/historical', [
                'timestamp' => $timestamp,
                'start' => 1,
                'limit' => 100,
                'sort' => 'volume_24h',
                'sort_dir' => 'desc',
                'market_type' => 'all',
                'category' => 'all',
            ], 3600)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => []]);

        $this->exchangeService->listingsHistorical($timestamp);
    }
}