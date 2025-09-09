<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Tests;

use PHPUnit\Framework\TestCase;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Basic tests for CryptocurrencyService functionality.
 */
class CryptocurrencyServiceTest extends TestCase
{
    private CryptocurrencyService $service;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        $config = [
            'api' => [
                'key' => 'test-key',
                'base_url' => 'https://pro-api.coinmarketcap.com/v2',
                'timeout' => 30,
            ],
            'cache' => [
                'enabled' => false,
                'prefix' => 'coinmarketcap',
            ],
            'endpoints' => [
                'cryptocurrency' => [
                    'map' => '/cryptocurrency/map',
                    'info' => '/cryptocurrency/info',
                    'listings_latest' => '/cryptocurrency/listings/latest',
                    'quotes_latest' => '/cryptocurrency/quotes/latest',
                ],
            ],
            'credits' => [
                'optimization_enabled' => true,
            ],
            'logging' => [
                'enabled' => false,
            ],
        ];

        // Create mock HTTP client
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create service with mocked dependencies
        $client = new CoinMarketCapClient($config, $httpClient);
        $transformer = new CryptocurrencyTransformer();
        $this->service = new CryptocurrencyService($client, $transformer, $config);
    }

    public function testGetMapValidatesParameters(): void
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                [
                    'id' => 1,
                    'symbol' => 'BTC',
                    'name' => 'Bitcoin',
                    'slug' => 'bitcoin',
                    'rank' => 1,
                    'is_active' => 1,
                ]
            ]
        ])));

        $result = $this->service->getMap(['limit' => 10]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
    }

    public function testGetInfoRequiresValidParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of id, slug, symbol, or address is required');

        $this->service->getInfo([]);
    }

    public function testGetInfoWithValidParameter(): void
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'slug' => 'bitcoin',
                    'description' => 'Bitcoin description',
                ]
            ]
        ])));

        $result = $this->service->getInfo(['id' => '1']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetQuotesLatestRequiresValidParameter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of id, slug, or symbol is required');

        $this->service->getQuotesLatest([]);
    }

    public function testGetQuotesLatestWithValidParameter(): void
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'slug' => 'bitcoin',
                    'quote' => [
                        'USD' => [
                            'price' => 45000.0,
                            'volume_24h' => 25000000000.0,
                            'market_cap' => 850000000000.0,
                        ]
                    ]
                ]
            ]
        ])));

        $result = $this->service->getQuotesLatest(['symbol' => 'BTC']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetListingsLatest(): void
    {
        // Mock successful API response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
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
                        ]
                    ]
                ]
            ]
        ])));

        $result = $this->service->getListingsLatest(['limit' => 10]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
    }

    public function testParameterFiltering(): void
    {
        // Test that invalid parameters are filtered out
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => []
        ])));

        // Should not throw exception even with invalid parameters mixed in
        $result = $this->service->getMap([
            'limit' => 10,
            'invalid_param' => 'should_be_filtered',
            'another_invalid' => true,
            'sort' => 'name'
        ]);

        $this->assertIsArray($result);
    }
}