<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use PHPUnit\Framework\TestCase;
use Convertain\CoinMarketCap\Transformers\ExchangeTransformer;

/**
 * Exchange Transformer Test
 *
 * Unit tests for ExchangeTransformer class covering data transformation
 * for all exchange-related API responses.
 */
class ExchangeTransformerTest extends TestCase
{
    private ExchangeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new ExchangeTransformer();
    }

    public function testTransformMapWithValidData(): void
    {
        $rawResponse = [
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
                ],
                [
                    'id' => 2,
                    'name' => 'Another Exchange',
                    'slug' => 'another-exchange',
                    'is_active' => 1,
                    'is_listed' => 0,
                    'first_historical_data' => '2022-01-01T00:00:00.000Z',
                    'last_historical_data' => null,
                ]
            ]
        ];

        $result = $this->transformer->transformMap($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        $this->assertCount(2, $result['data']);
        
        $first = $result['data'][0];
        $this->assertEquals(1, $first['id']);
        $this->assertEquals('Test Exchange', $first['name']);
        $this->assertEquals('test-exchange', $first['slug']);
        $this->assertEquals(1, $first['is_active']);
        $this->assertEquals(1, $first['is_listed']);
        
        $second = $result['data'][1];
        $this->assertEquals(2, $second['id']);
        $this->assertEquals('Another Exchange', $second['name']);
        $this->assertNull($second['last_historical_data']);
    }

    public function testTransformMapWithInvalidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 400, 'error_message' => 'Bad request'],
        ];

        $result = $this->transformer->transformMap($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(400, $result['status']['error_code']);
        $this->assertEmpty($result['data']);
    }

    public function testTransformInfoWithValidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'description' => 'A test exchange for crypto trading',
                    'logo' => 'https://example.com/logo.png',
                    'website' => 'https://test-exchange.com',
                    'date_launched' => '2021-01-01T00:00:00.000Z',
                    'notice' => 'This is a test notice',
                    'countries' => ['US', 'CA'],
                    'fiats' => ['USD', 'CAD'],
                    'tags' => ['spot', 'derivatives'],
                    'type' => 'centralized',
                    'is_hidden' => 0,
                    'is_redistributable' => 1,
                    'maker_fee' => 0.1,
                    'taker_fee' => 0.1,
                    'weekly_visits' => 1000000,
                    'spot_volume_usd' => 500000000,
                    'spot_volume_last_updated' => '2023-01-01T00:00:00.000Z',
                ]
            ]
        ];

        $result = $this->transformer->transformInfo($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('1', $result['data']);
        
        $exchange = $result['data']['1'];
        $this->assertEquals(1, $exchange['id']);
        $this->assertEquals('Test Exchange', $exchange['name']);
        $this->assertEquals('A test exchange for crypto trading', $exchange['description']);
        $this->assertEquals(['US', 'CA'], $exchange['countries']);
        $this->assertEquals(['USD', 'CAD'], $exchange['fiats']);
        $this->assertEquals(['spot', 'derivatives'], $exchange['tags']);
        $this->assertEquals(0.1, $exchange['maker_fee']);
        $this->assertEquals(1000000, $exchange['weekly_visits']);
    }

    public function testTransformListingsWithValidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'num_market_pairs' => 150,
                    'exchange_score' => 8.5,
                    'last_updated' => '2023-01-01T00:00:00.000Z',
                    'fiats' => ['USD', 'EUR'],
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 1000000000,
                            'volume_24h_adjusted' => 950000000,
                            'volume_7d' => 7000000000,
                            'volume_30d' => 30000000000,
                            'percent_change_volume_24h' => 5.2,
                            'percent_change_volume_7d' => -2.1,
                            'percent_change_volume_30d' => 15.8,
                            'last_updated' => '2023-01-01T00:00:00.000Z',
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformListings($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        
        $exchange = $result['data'][0];
        $this->assertEquals(1, $exchange['id']);
        $this->assertEquals('Test Exchange', $exchange['name']);
        $this->assertEquals(150, $exchange['num_market_pairs']);
        $this->assertEquals(8.5, $exchange['exchange_score']);
        $this->assertArrayHasKey('quote', $exchange);
        $this->assertArrayHasKey('USD', $exchange['quote']);
        
        $usdQuote = $exchange['quote']['USD'];
        $this->assertEquals(1000000000, $usdQuote['volume_24h']);
        $this->assertEquals(950000000, $usdQuote['volume_24h_adjusted']);
        $this->assertEquals(5.2, $usdQuote['percent_change_volume_24h']);
    }

    public function testTransformQuotesWithValidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    'slug' => 'test-exchange',
                    'num_market_pairs' => 200,
                    'last_updated' => '2023-01-01T00:00:00.000Z',
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 2000000000,
                            'volume_24h_adjusted' => 1900000000,
                            'effective_liquidity_24h' => 1500000000,
                            'derivative_volume_usd' => 500000000,
                            'spot_volume_usd' => 1500000000,
                            'last_updated' => '2023-01-01T00:00:00.000Z',
                        ],
                        'EUR' => [
                            'volume_24h' => 1800000000,
                            'volume_24h_adjusted' => 1700000000,
                            'last_updated' => '2023-01-01T00:00:00.000Z',
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformQuotes($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('1', $result['data']);
        
        $exchange = $result['data']['1'];
        $this->assertEquals(1, $exchange['id']);
        $this->assertEquals(200, $exchange['num_market_pairs']);
        $this->assertArrayHasKey('quote', $exchange);
        
        $usdQuote = $exchange['quote']['USD'];
        $this->assertEquals(2000000000, $usdQuote['volume_24h']);
        $this->assertEquals(1500000000, $usdQuote['effective_liquidity_24h']);
        $this->assertEquals(500000000, $usdQuote['derivative_volume_usd']);
        
        $eurQuote = $exchange['quote']['EUR'];
        $this->assertEquals(1800000000, $eurQuote['volume_24h']);
        $this->assertNull($eurQuote['derivative_volume_usd']); // Not present in EUR quote
    }

    public function testTransformMarketPairsWithValidData(): void
    {
        $rawResponse = [
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
                        'exchange_slug' => 'test-exchange',
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
                        'category' => 'spot',
                        'fee_type' => 'percentage',
                        'market_url' => 'https://test-exchange.com/trade/BTC-USD',
                        'outlier_detected' => 0,
                        'last_updated' => '2023-01-01T00:00:00.000Z',
                        'quote' => [
                            'USD' => [
                                'price' => 50000.0,
                                'volume_24h_base' => 100.0,
                                'volume_24h_quote' => 5000000.0,
                                'last_updated' => '2023-01-01T00:00:00.000Z',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformMarketPairs($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        
        $data = $result['data'];
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('Test Exchange', $data['name']);
        $this->assertEquals(2, $data['num_market_pairs']);
        $this->assertArrayHasKey('market_pairs', $data);
        $this->assertCount(1, $data['market_pairs']);
        
        $pair = $data['market_pairs'][0];
        $this->assertEquals(1, $pair['exchange_id']);
        $this->assertEquals('BTC/USD', $pair['market_pair']);
        $this->assertEquals('spot', $pair['category']);
        $this->assertEquals('percentage', $pair['fee_type']);
        
        $this->assertArrayHasKey('market_pair_base', $pair);
        $this->assertArrayHasKey('market_pair_quote', $pair);
        
        $base = $pair['market_pair_base'];
        $this->assertEquals(1, $base['currency_id']);
        $this->assertEquals('BTC', $base['currency_symbol']);
        $this->assertEquals('cryptocurrency', $base['currency_type']);
        
        $quote = $pair['market_pair_quote'];
        $this->assertEquals(2781, $quote['currency_id']);
        $this->assertEquals('USD', $quote['currency_symbol']);
        $this->assertEquals('fiat', $quote['currency_type']);
        
        $this->assertArrayHasKey('quote', $pair);
        $this->assertArrayHasKey('USD', $pair['quote']);
        $pairQuote = $pair['quote']['USD'];
        $this->assertEquals(50000.0, $pairQuote['price']);
        $this->assertEquals(100.0, $pairQuote['volume_24h_base']);
        $this->assertEquals(5000000.0, $pairQuote['volume_24h_quote']);
    }

    public function testTransformQuoteDataWithMixedData(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('transformQuoteData');
        $method->setAccessible(true);

        $rawQuote = [
            'USD' => [
                'volume_24h' => 1000000,
                'volume_24h_adjusted' => 950000,
                'price' => 50000.0, // Market pair specific
                'volume_24h_base' => 100.0, // Market pair specific
                'last_updated' => '2023-01-01T00:00:00.000Z',
            ],
            'EUR' => [
                'volume_24h' => 900000,
                'effective_liquidity_24h' => 800000,
                'last_updated' => '2023-01-01T00:00:00.000Z',
            ]
        ];

        $result = $method->invoke($this->transformer, $rawQuote);

        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('EUR', $result);
        
        $usd = $result['USD'];
        $this->assertEquals(1000000, $usd['volume_24h']);
        $this->assertEquals(950000, $usd['volume_24h_adjusted']);
        $this->assertEquals(50000.0, $usd['price']);
        $this->assertEquals(100.0, $usd['volume_24h_base']);
        
        $eur = $result['EUR'];
        $this->assertEquals(900000, $eur['volume_24h']);
        $this->assertEquals(800000, $eur['effective_liquidity_24h']);
        $this->assertNull($eur['price']); // Not present in EUR quote
        $this->assertNull($eur['volume_24h_base']); // Not present in EUR quote
    }

    public function testTransformWithMissingDataFields(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Test Exchange',
                    // Missing many optional fields
                    'quote' => [
                        'USD' => [
                            'volume_24h' => 1000000,
                            // Missing many optional quote fields
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformListings($rawResponse);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        
        $exchange = $result['data'][0];
        $this->assertEquals(1, $exchange['id']);
        $this->assertEquals('Test Exchange', $exchange['name']);
        $this->assertNull($exchange['num_market_pairs']);
        $this->assertNull($exchange['exchange_score']);
        $this->assertEmpty($exchange['fiats']);
        
        $usdQuote = $exchange['quote']['USD'];
        $this->assertEquals(1000000, $usdQuote['volume_24h']);
        $this->assertNull($usdQuote['volume_24h_adjusted']);
        $this->assertNull($usdQuote['volume_7d']);
    }
}