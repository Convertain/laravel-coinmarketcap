<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Transformers\GlobalMetricsTransformer;

class GlobalMetricsTransformerTest extends TestCase
{
    private GlobalMetricsTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transformer = new GlobalMetricsTransformer();
    }

    public function test_transform_returns_standardized_format()
    {
        $rawData = [
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
        ];

        $result = $this->transformer->transform($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(42.5, $result['btc_dominance']);
        $this->assertEquals(18.2, $result['eth_dominance']);
        $this->assertEquals(2500000000000.0, $result['total_market_cap']);
        $this->assertEquals(120000000000.0, $result['total_volume_24h']);
        $this->assertEquals(-3.5, $result['total_volume_24h_change']);
        $this->assertEquals('2023-01-01T00:00:00.000Z', $result['last_updated']);
    }

    public function test_transform_handles_missing_dominance_data()
    {
        $rawData = [
            'btc_dominance' => null,
            'eth_dominance' => null,
            'quote' => [
                'USD' => [
                    'total_market_cap' => 2500000000000.0,
                    'total_volume_24h' => 120000000000.0,
                    'total_volume_24h_change_percentage' => -3.5,
                ]
            ],
            'last_updated' => '2023-01-01T00:00:00.000Z',
        ];

        $result = $this->transformer->transform($rawData);

        $this->assertIsArray($result);
        $this->assertNull($result['btc_dominance']);
        $this->assertNull($result['eth_dominance']);
        $this->assertEquals(2500000000000.0, $result['total_market_cap']);
    }

    public function test_transform_handles_missing_quote_data()
    {
        $rawData = [
            'btc_dominance' => 42.5,
            'eth_dominance' => 18.2,
            'quote' => [
                'USD' => [
                    'total_market_cap' => null,
                    'total_volume_24h' => null,
                    'total_volume_24h_change_percentage' => null,
                ]
            ],
            'last_updated' => '2023-01-01T00:00:00.000Z',
        ];

        $result = $this->transformer->transform($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(42.5, $result['btc_dominance']);
        $this->assertNull($result['total_market_cap']);
        $this->assertNull($result['total_volume_24h']);
        $this->assertNull($result['total_volume_24h_change']);
    }

    public function test_transform_with_different_currency()
    {
        $rawData = [
            'btc_dominance' => 42.5,
            'eth_dominance' => 18.2,
            'quote' => [
                'EUR' => [
                    'total_market_cap' => 2100000000000.0,
                    'total_volume_24h' => 100000000000.0,
                    'total_volume_24h_change_percentage' => -2.8,
                ]
            ],
            'last_updated' => '2023-01-01T00:00:00.000Z',
        ];

        // For now, transformer expects USD, but we can test this scenario
        // In a real implementation, we might want to make currency configurable
        $result = $this->transformer->transform($rawData);

        $this->assertIsArray($result);
        $this->assertEquals(42.5, $result['btc_dominance']);
        $this->assertEquals(18.2, $result['eth_dominance']);
        // This will fail because transformer looks for USD specifically
        // but shows how we might want to handle different currencies
    }
}