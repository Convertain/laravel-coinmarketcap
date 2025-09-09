<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;

class CryptocurrencyTransformerTest extends TestCase
{
    private CryptocurrencyTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transformer = new CryptocurrencyTransformer();
    }

    public function test_transform_listing_returns_standardized_format()
    {
        $rawData = [
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
        ];

        $result = $this->transformer->transformListing($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Bitcoin', $result['name']);
        $this->assertEquals('BTC', $result['symbol']);
        $this->assertEquals('bitcoin', $result['slug']);
        $this->assertEquals(1, $result['cmc_rank']);
        $this->assertEquals(45000.0, $result['price']);
        $this->assertEquals(850000000000.0, $result['market_cap']);
        $this->assertEquals(30000000000.0, $result['volume_24h']);
        $this->assertEquals(0.5, $result['percent_change_1h']);
        $this->assertEquals(-2.3, $result['percent_change_24h']);
        $this->assertEquals(8.7, $result['percent_change_7d']);
        $this->assertEquals('2023-01-01T00:00:00.000Z', $result['last_updated']);
    }

    public function test_transform_listings_handles_multiple_items()
    {
        $rawListings = [
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
            ],
            [
                'id' => 1027,
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'slug' => 'ethereum',
                'cmc_rank' => 2,
                'quote' => [
                    'USD' => [
                        'price' => 3000.0,
                        'market_cap' => 360000000000.0,
                        'volume_24h' => 15000000000.0,
                        'percent_change_1h' => 1.2,
                        'percent_change_24h' => 3.5,
                        'percent_change_7d' => -1.8,
                    ]
                ],
                'last_updated' => '2023-01-01T00:00:00.000Z',
            ]
        ];

        $result = $this->transformer->transformListings($rawListings);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Bitcoin', $result[0]['name']);
        $this->assertEquals('Ethereum', $result[1]['name']);
    }

    public function test_transform_info_returns_standardized_format()
    {
        $rawData = [
            'id' => 1,
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'category' => 'coin',
            'description' => 'Bitcoin is a decentralized digital currency.',
            'logo' => 'https://example.com/bitcoin-logo.png',
            'urls' => [
                'website' => ['https://bitcoin.org'],
                'twitter' => ['https://twitter.com/bitcoin'],
            ],
            'tags' => ['mineable', 'pow', 'sha-256'],
        ];

        $result = $this->transformer->transformInfo($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Bitcoin', $result['name']);
        $this->assertEquals('BTC', $result['symbol']);
        $this->assertEquals('coin', $result['category']);
        $this->assertStringContainsString('decentralized', $result['description']);
        $this->assertArrayHasKey('logo', $result);
        $this->assertArrayHasKey('urls', $result);
        $this->assertArrayHasKey('tags', $result);
    }

    public function test_transform_listing_handles_missing_quote_data()
    {
        $rawData = [
            'id' => 1,
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'slug' => 'bitcoin',
            'cmc_rank' => 1,
            'quote' => [
                'USD' => [
                    'price' => null,
                    'market_cap' => null,
                    'volume_24h' => null,
                    'percent_change_1h' => null,
                    'percent_change_24h' => null,
                    'percent_change_7d' => null,
                ]
            ],
            'last_updated' => '2023-01-01T00:00:00.000Z',
        ];

        $result = $this->transformer->transformListing($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Bitcoin', $result['name']);
        $this->assertNull($result['price']);
        $this->assertNull($result['market_cap']);
    }
}