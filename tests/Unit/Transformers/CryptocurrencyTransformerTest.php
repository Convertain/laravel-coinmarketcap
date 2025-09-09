<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use PHPUnit\Framework\TestCase;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;

class CryptocurrencyTransformerTest extends TestCase
{
    private CryptocurrencyTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new CryptocurrencyTransformer();
    }

    /** @test */
    public function it_can_validate_cryptocurrency_data_structure()
    {
        $validData = [
            'data' => [
                'id' => 1,
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'quote' => [
                    'USD' => [
                        'price' => 50000.0,
                    ],
                ],
            ],
        ];

        $this->assertTrue($this->transformer->canTransform($validData));
    }

    /** @test */
    public function it_rejects_invalid_data_structure()
    {
        $invalidData = [
            'data' => [
                'unknown_field' => 'value',
            ],
        ];

        $this->assertFalse($this->transformer->canTransform($invalidData));
    }

    /** @test */
    public function it_transforms_single_cryptocurrency_data()
    {
        $rawData = [
            'data' => [
                'id' => 1,
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'slug' => 'bitcoin',
                'cmc_rank' => 1,
                'circulating_supply' => 19000000.0,
                'total_supply' => 21000000.0,
                'max_supply' => 21000000.0,
                'last_updated' => '2024-01-01T12:00:00.000Z',
                'quote' => [
                    'USD' => [
                        'price' => 50000.0,
                        'volume_24h' => 10000000000.0,
                        'percent_change_24h' => 5.0,
                        'market_cap' => 950000000000.0,
                        'last_updated' => '2024-01-01T12:00:00.000Z',
                    ],
                ],
            ],
            'status' => [
                'timestamp' => '2024-01-01T12:00:00.000Z',
                'error_code' => 0,
                'credit_count' => 1,
            ],
        ];

        $result = $this->transformer->transform($rawData);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('meta', $result);

        $transformedData = $result['data'];
        $this->assertEquals(1, $transformedData['id']);
        $this->assertEquals('BTC', $transformedData['symbol']);
        $this->assertEquals('Bitcoin', $transformedData['name']);
        $this->assertEquals('bitcoin', $transformedData['slug']);
        $this->assertEquals(1, $transformedData['cmc_rank']);
        $this->assertEquals(19000000.0, $transformedData['circulating_supply']);

        // Check quotes transformation
        $this->assertArrayHasKey('quotes', $transformedData);
        $this->assertArrayHasKey('USD', $transformedData['quotes']);
        $this->assertEquals(50000.0, $transformedData['quotes']['USD']['price']);
    }

    /** @test */
    public function it_transforms_collection_of_cryptocurrencies()
    {
        $collection = [
            [
                'id' => 1,
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
            ],
            [
                'id' => 1027,
                'symbol' => 'ETH',
                'name' => 'Ethereum',
            ],
        ];

        $result = $this->transformer->transformCollection($collection);

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('BTC', $result[0]['symbol']);
        $this->assertEquals(1027, $result[1]['id']);
        $this->assertEquals('ETH', $result[1]['symbol']);
    }

    /** @test */
    public function it_handles_missing_optional_fields_gracefully()
    {
        $rawData = [
            'id' => 1,
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            // Missing optional fields like description, logo, etc.
        ];

        $result = $this->transformer->transform($rawData);

        // Should contain only non-null values
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('logo', $result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('BTC', $result['symbol']);
    }

    /** @test */
    public function it_normalizes_datetime_fields_to_iso_format()
    {
        $rawData = [
            'id' => 1,
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            'last_updated' => '2024-01-01T12:00:00.000Z',
            'date_added' => '2013-04-28T00:00:00.000Z',
        ];

        $result = $this->transformer->transform($rawData);

        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z/', $result['last_updated']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z/', $result['date_added']);
    }

    /** @test */
    public function it_handles_null_and_empty_values_appropriately()
    {
        $rawData = [
            'id' => 1,
            'symbol' => 'BTC',
            'name' => 'Bitcoin',
            'description' => null,
            'max_supply' => null,
            'website' => [],
            'tags' => [],
        ];

        $result = $this->transformer->transform($rawData);

        // Null and empty values should be filtered out
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('max_supply', $result);
        $this->assertArrayNotHasKey('website', $result);
        $this->assertArrayNotHasKey('tags', $result);
    }
}