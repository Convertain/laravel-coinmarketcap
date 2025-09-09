<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Transformers\ExchangeTransformer;

class ExchangeTransformerTest extends TestCase
{
    private ExchangeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transformer = new ExchangeTransformer();
    }

    public function test_transform_listing_returns_standardized_format()
    {
        $rawData = [
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
        ];

        $result = $this->transformer->transformListing($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(270, $result['id']);
        $this->assertEquals('Binance', $result['name']);
        $this->assertEquals('binance', $result['slug']);
        $this->assertEquals(1000, $result['num_market_pairs']);
        $this->assertEquals(15000000000.0, $result['spot_volume_usd']);
        $this->assertEquals(5.2, $result['volume_change_24h']);
        $this->assertEquals('2023-01-01T00:00:00.000Z', $result['last_updated']);
    }

    public function test_transform_listings_handles_multiple_items()
    {
        $rawListings = [
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
            ],
            [
                'id' => 89,
                'name' => 'Coinbase Exchange',
                'slug' => 'coinbase-exchange',
                'num_market_pairs' => 500,
                'quote' => [
                    'USD' => [
                        'volume_24h' => 8000000000.0,
                        'volume_24h_change_percentage' => -2.1,
                    ]
                ],
                'last_updated' => '2023-01-01T00:00:00.000Z',
            ]
        ];

        $result = $this->transformer->transformListings($rawListings);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Binance', $result[0]['name']);
        $this->assertEquals('Coinbase Exchange', $result[1]['name']);
        $this->assertEquals(15000000000.0, $result[0]['spot_volume_usd']);
        $this->assertEquals(8000000000.0, $result[1]['spot_volume_usd']);
    }

    public function test_transform_info_returns_standardized_format()
    {
        $rawData = [
            'id' => 270,
            'name' => 'Binance',
            'slug' => 'binance',
            'description' => 'Binance is a cryptocurrency exchange.',
            'logo' => 'https://example.com/binance-logo.png',
            'urls' => [
                'website' => ['https://binance.com'],
                'twitter' => ['https://twitter.com/binance'],
            ],
            'countries' => ['Malta'],
            'type' => 'centralized',
        ];

        $result = $this->transformer->transformInfo($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(270, $result['id']);
        $this->assertEquals('Binance', $result['name']);
        $this->assertEquals('binance', $result['slug']);
        $this->assertStringContainsString('cryptocurrency exchange', $result['description']);
        $this->assertArrayHasKey('logo', $result);
        $this->assertArrayHasKey('urls', $result);
        $this->assertArrayHasKey('countries', $result);
        $this->assertEquals('centralized', $result['type']);
    }

    public function test_transform_listing_handles_missing_volume_data()
    {
        $rawData = [
            'id' => 270,
            'name' => 'Binance',
            'slug' => 'binance',
            'num_market_pairs' => 1000,
            'quote' => [
                'USD' => [
                    'volume_24h' => null,
                    'volume_24h_change_percentage' => null,
                ]
            ],
            'last_updated' => '2023-01-01T00:00:00.000Z',
        ];

        $result = $this->transformer->transformListing($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(270, $result['id']);
        $this->assertEquals('Binance', $result['name']);
        $this->assertNull($result['spot_volume_usd']);
        $this->assertNull($result['volume_change_24h']);
    }

    public function test_transform_listings_handles_empty_array()
    {
        $result = $this->transformer->transformListings([]);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}