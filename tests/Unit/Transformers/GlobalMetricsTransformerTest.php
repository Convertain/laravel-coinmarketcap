<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Transformers;

use PHPUnit\Framework\TestCase;
use Convertain\CoinMarketCap\Transformers\GlobalMetricsTransformer;

/**
 * Global Metrics Transformer Test
 *
 * Unit tests for GlobalMetricsTransformer class covering data transformation
 * for global metrics API responses and calculated analysis features.
 */
class GlobalMetricsTransformerTest extends TestCase
{
    private GlobalMetricsTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new GlobalMetricsTransformer();
    }

    public function testTransformLatestWithValidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                'active_cryptocurrencies' => 5000,
                'total_cryptocurrencies' => 6000,
                'active_market_pairs' => 25000,
                'active_exchanges' => 200,
                'total_exchanges' => 250,
                'eth_dominance' => 18.5,
                'btc_dominance' => 42.3,
                'eth_dominance_yesterday' => 18.2,
                'btc_dominance_yesterday' => 42.8,
                'eth_dominance_24h_percentage_change' => 0.3,
                'btc_dominance_24h_percentage_change' => -0.5,
                'defi_volume_24h' => 5000000000,
                'defi_volume_24h_reported' => 4800000000,
                'defi_market_cap' => 50000000000,
                'defi_24h_percentage_change' => 2.5,
                'stablecoin_volume_24h' => 20000000000,
                'stablecoin_volume_24h_reported' => 19500000000,
                'stablecoin_market_cap' => 150000000000,
                'stablecoin_24h_percentage_change' => 1.2,
                'derivatives_volume_24h' => 100000000000,
                'derivatives_volume_24h_reported' => 95000000000,
                'derivatives_24h_percentage_change' => 5.8,
                'last_updated' => '2023-01-01T00:00:00.000Z',
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000,
                        'total_volume_24h' => 50000000000,
                        'total_volume_24h_reported' => 48000000000,
                        'altcoin_volume_24h' => 30000000000,
                        'altcoin_volume_24h_reported' => 29000000000,
                        'altcoin_market_cap' => 1200000000000,
                        'total_market_cap_yesterday' => 1950000000000,
                        'total_volume_24h_yesterday' => 45000000000,
                        'total_market_cap_yesterday_percentage_change' => 2.56,
                        'total_volume_24h_yesterday_percentage_change' => 11.11,
                        'last_updated' => '2023-01-01T00:00:00.000Z',
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformLatest($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        
        $data = $result['data'];
        $this->assertEquals(5000, $data['active_cryptocurrencies']);
        $this->assertEquals(6000, $data['total_cryptocurrencies']);
        $this->assertEquals(25000, $data['active_market_pairs']);
        $this->assertEquals(200, $data['active_exchanges']);
        $this->assertEquals(250, $data['total_exchanges']);
        $this->assertEquals(18.5, $data['eth_dominance']);
        $this->assertEquals(42.3, $data['btc_dominance']);
        $this->assertEquals(0.3, $data['eth_dominance_24h_percentage_change']);
        $this->assertEquals(-0.5, $data['btc_dominance_24h_percentage_change']);
        $this->assertEquals(5000000000, $data['defi_volume_24h']);
        $this->assertEquals(50000000000, $data['defi_market_cap']);
        $this->assertEquals(2.5, $data['defi_24h_percentage_change']);
        $this->assertEquals(100000000000, $data['derivatives_volume_24h']);
        $this->assertEquals(5.8, $data['derivatives_24h_percentage_change']);

        $this->assertArrayHasKey('quote', $data);
        $this->assertArrayHasKey('USD', $data['quote']);
        
        $usdQuote = $data['quote']['USD'];
        $this->assertEquals(2000000000000, $usdQuote['total_market_cap']);
        $this->assertEquals(50000000000, $usdQuote['total_volume_24h']);
        $this->assertEquals(2.56, $usdQuote['total_market_cap_yesterday_percentage_change']);
        $this->assertEquals(11.11, $usdQuote['total_volume_24h_yesterday_percentage_change']);
    }

    public function testTransformHistoricalWithValidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                'name' => 'Global Metrics',
                'symbol' => 'GLOBAL',
                'id' => null,
                'quotes' => [
                    [
                        'timestamp' => '2023-01-01T00:00:00.000Z',
                        'search_interval' => null,
                        'active_cryptocurrencies' => 5000,
                        'total_cryptocurrencies' => 6000,
                        'active_market_pairs' => 25000,
                        'active_exchanges' => 200,
                        'btc_dominance' => 42.3,
                        'eth_dominance' => 18.5,
                        'defi_volume_24h' => 5000000000,
                        'defi_market_cap' => 50000000000,
                        'stablecoin_volume_24h' => 20000000000,
                        'stablecoin_market_cap' => 150000000000,
                        'derivatives_volume_24h' => 100000000000,
                        'quote' => [
                            'USD' => [
                                'total_market_cap' => 2000000000000,
                                'total_volume_24h' => 50000000000,
                                'last_updated' => '2023-01-01T00:00:00.000Z',
                            ]
                        ]
                    ],
                    [
                        'timestamp' => '2023-01-02T00:00:00.000Z',
                        'active_cryptocurrencies' => 5005,
                        'btc_dominance' => 42.1,
                        'eth_dominance' => 18.7,
                        'quote' => [
                            'USD' => [
                                'total_market_cap' => 2100000000000,
                                'total_volume_24h' => 52000000000,
                                'last_updated' => '2023-01-02T00:00:00.000Z',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformHistorical($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        
        $data = $result['data'];
        $this->assertEquals('Global Metrics', $data['name']);
        $this->assertEquals('GLOBAL', $data['symbol']);
        $this->assertNull($data['id']);
        $this->assertArrayHasKey('quotes', $data);
        $this->assertCount(2, $data['quotes']);
        
        $firstQuote = $data['quotes'][0];
        $this->assertEquals('2023-01-01T00:00:00.000Z', $firstQuote['timestamp']);
        $this->assertEquals(5000, $firstQuote['active_cryptocurrencies']);
        $this->assertEquals(42.3, $firstQuote['btc_dominance']);
        $this->assertEquals(18.5, $firstQuote['eth_dominance']);
        $this->assertEquals(2000000000000, $firstQuote['quote']['USD']['total_market_cap']);
        
        $secondQuote = $data['quotes'][1];
        $this->assertEquals('2023-01-02T00:00:00.000Z', $secondQuote['timestamp']);
        $this->assertEquals(5005, $secondQuote['active_cryptocurrencies']);
        $this->assertEquals(42.1, $secondQuote['btc_dominance']);
        $this->assertEquals(2100000000000, $secondQuote['quote']['USD']['total_market_cap']);
    }

    public function testExtractDominanceMetrics(): void
    {
        $response = [
            'status' => ['error_code' => 0],
            'data' => [
                'btc_dominance' => 42.3,
                'btc_dominance_yesterday' => 42.8,
                'btc_dominance_24h_percentage_change' => -0.5,
                'eth_dominance' => 18.5,
                'eth_dominance_yesterday' => 18.2,
                'eth_dominance_24h_percentage_change' => 0.3,
            ]
        ];

        $result = $this->transformer->extractDominanceMetrics($response);

        $this->assertArrayHasKey('dominance', $result);
        $dominance = $result['dominance'];
        
        $this->assertArrayHasKey('bitcoin', $dominance);
        $this->assertArrayHasKey('ethereum', $dominance);
        $this->assertArrayHasKey('altcoins', $dominance);
        
        // Bitcoin dominance
        $btc = $dominance['bitcoin'];
        $this->assertEquals(42.3, $btc['current']);
        $this->assertEquals(42.8, $btc['yesterday']);
        $this->assertEquals(-0.5, $btc['change_24h']);
        
        // Ethereum dominance
        $eth = $dominance['ethereum'];
        $this->assertEquals(18.5, $eth['current']);
        $this->assertEquals(18.2, $eth['yesterday']);
        $this->assertEquals(0.3, $eth['change_24h']);
        
        // Altcoin dominance (calculated)
        $altcoin = $dominance['altcoins'];
        $this->assertEquals(39.2, $altcoin['current']); // 100 - 42.3 - 18.5
        $this->assertEquals(39.0, $altcoin['yesterday']); // 100 - 42.8 - 18.2
        $this->assertEqualsWithDelta(0.51, $altcoin['change_24h'], 0.01); // Calculated percentage change
    }

    public function testExtractMarketCapTiers(): void
    {
        $response = [
            'status' => ['error_code' => 0],
            'data' => [
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000, // $2T
                    ]
                ]
            ]
        ];

        $result = $this->transformer->extractMarketCapTiers($response);

        $this->assertArrayHasKey('market_cap_tiers', $result);
        $this->assertArrayHasKey('total_market_cap', $result);
        $this->assertEquals(2000000000000, $result['total_market_cap']);
        
        $tiers = $result['market_cap_tiers'];
        
        // Large cap (>$10B)
        $this->assertArrayHasKey('large_cap', $tiers);
        $largeCap = $tiers['large_cap'];
        $this->assertEquals(10000000000, $largeCap['threshold']);
        $this->assertEquals(60, $largeCap['estimated_percentage']);
        $this->assertEquals(1200000000000, $largeCap['estimated_market_cap']); // 60% of $2T
        
        // Mid cap ($1B - $10B)
        $this->assertArrayHasKey('mid_cap', $tiers);
        $midCap = $tiers['mid_cap'];
        $this->assertEquals(1000000000, $midCap['threshold']);
        $this->assertEquals(30, $midCap['estimated_percentage']);
        $this->assertEquals(600000000000, $midCap['estimated_market_cap']); // 30% of $2T
        
        // Small cap (<$1B)
        $this->assertArrayHasKey('small_cap', $tiers);
        $smallCap = $tiers['small_cap'];
        $this->assertEquals(0, $smallCap['threshold']);
        $this->assertEquals(10, $smallCap['estimated_percentage']);
        $this->assertEquals(200000000000, $smallCap['estimated_market_cap']); // 10% of $2T
    }

    public function testCalculateTrendIndicators(): void
    {
        $response = [
            'status' => ['error_code' => 0],
            'data' => [
                'quotes' => [
                    [
                        'timestamp' => '2023-01-01T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1000000000000, 'total_volume_24h' => 30000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-02T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1100000000000, 'total_volume_24h' => 35000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-03T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1200000000000, 'total_volume_24h' => 40000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-04T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1150000000000, 'total_volume_24h' => 38000000000]]
                    ],
                    [
                        'timestamp' => '2023-01-05T00:00:00.000Z',
                        'quote' => ['USD' => ['total_market_cap' => 1250000000000, 'total_volume_24h' => 42000000000]]
                    ]
                ]
            ]
        ];

        $result = $this->transformer->calculateTrendIndicators($response);

        $this->assertArrayHasKey('trend_analysis', $result);
        $analysis = $result['trend_analysis'];
        
        $this->assertArrayHasKey('market_cap_trend', $analysis);
        $this->assertArrayHasKey('volume_trend', $analysis);
        $this->assertArrayHasKey('volatility', $analysis);
        $this->assertArrayHasKey('momentum', $analysis);
        
        // Market cap went from 1T to 1.25T, so upward trend
        $this->assertEquals('upward', $analysis['market_cap_trend']);
        $this->assertEquals('upward', $analysis['volume_trend']);
        
        // Volatility should be a positive number
        $this->assertGreaterThan(0, $analysis['volatility']);
        
        // Momentum should be positive (recent values higher than initial values)
        $this->assertGreaterThan(0, $analysis['momentum']);
    }

    public function testExtractSentimentData(): void
    {
        $response = [
            'status' => ['error_code' => 0],
            'data' => [
                'quote' => [
                    'USD' => [
                        'total_market_cap_yesterday_percentage_change' => 8.5,
                        'total_volume_24h_yesterday_percentage_change' => 15.2,
                    ]
                ]
            ]
        ];

        $result = $this->transformer->extractSentimentData($response);

        $this->assertArrayHasKey('sentiment', $result);
        $sentiment = $result['sentiment'];
        
        $this->assertArrayHasKey('overall', $sentiment);
        $this->assertArrayHasKey('market_cap_change_24h', $sentiment);
        $this->assertArrayHasKey('volume_change_24h', $sentiment);
        $this->assertArrayHasKey('fear_greed_index', $sentiment);
        
        // 8.5% change > 5%, so should be "very_positive"
        $this->assertEquals('very_positive', $sentiment['overall']);
        $this->assertEquals(8.5, $sentiment['market_cap_change_24h']);
        $this->assertEquals(15.2, $sentiment['volume_change_24h']);
        
        // Fear and greed index should be between 0-100
        $this->assertGreaterThanOrEqual(0, $sentiment['fear_greed_index']);
        $this->assertLessThanOrEqual(100, $sentiment['fear_greed_index']);
        
        // With 8.5% market cap change, should be greedy (>50)
        $this->assertGreaterThan(50, $sentiment['fear_greed_index']);
    }

    public function testExtractVolumeAnalysis(): void
    {
        $response = [
            'status' => ['error_code' => 0],
            'data' => [
                'derivatives_volume_24h' => 100000000000,
                'derivatives_volume_24h_reported' => 95000000000,
                'derivatives_24h_percentage_change' => 5.2,
                'defi_volume_24h' => 5000000000,
                'defi_volume_24h_reported' => 4800000000,
                'stablecoin_volume_24h' => 20000000000,
                'stablecoin_volume_24h_reported' => 19500000000,
                'quote' => [
                    'USD' => [
                        'total_volume_24h' => 50000000000,
                        'total_volume_24h_reported' => 48000000000,
                        'total_volume_24h_yesterday_percentage_change' => 10.5,
                    ]
                ]
            ]
        ];

        $result = $this->transformer->extractVolumeAnalysis($response);

        $this->assertArrayHasKey('volume_analysis', $result);
        $analysis = $result['volume_analysis'];
        
        $this->assertArrayHasKey('spot_volume', $analysis);
        $this->assertArrayHasKey('derivatives_volume', $analysis);
        $this->assertArrayHasKey('defi_volume', $analysis);
        $this->assertArrayHasKey('stablecoin_volume', $analysis);
        
        // Spot volume
        $spot = $analysis['spot_volume'];
        $this->assertEquals(50000000000, $spot['total_24h']);
        $this->assertEquals(48000000000, $spot['reported_24h']);
        $this->assertEquals(10.5, $spot['change_24h']);
        
        // Derivatives volume
        $derivatives = $analysis['derivatives_volume'];
        $this->assertEquals(100000000000, $derivatives['total_24h']);
        $this->assertEquals(95000000000, $derivatives['reported_24h']);
        $this->assertEquals(5.2, $derivatives['change_24h']);
        
        // DeFi volume
        $defi = $analysis['defi_volume'];
        $this->assertEquals(5000000000, $defi['total_24h']);
        $this->assertEquals(4800000000, $defi['reported_24h']);
        
        // Stablecoin volume
        $stablecoin = $analysis['stablecoin_volume'];
        $this->assertEquals(20000000000, $stablecoin['total_24h']);
        $this->assertEquals(19500000000, $stablecoin['reported_24h']);
    }

    public function testTransformWithMissingData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                'active_cryptocurrencies' => 5000,
                // Missing many fields
                'quote' => [
                    'USD' => [
                        'total_market_cap' => 2000000000000,
                        // Missing many quote fields
                    ]
                ]
            ]
        ];

        $result = $this->transformer->transformLatest($rawResponse);

        $this->assertArrayHasKey('data', $result);
        $data = $result['data'];
        
        $this->assertEquals(5000, $data['active_cryptocurrencies']);
        $this->assertNull($data['total_cryptocurrencies']);
        $this->assertNull($data['btc_dominance']);
        $this->assertNull($data['eth_dominance']);
        $this->assertNull($data['defi_volume_24h']);
        
        $usdQuote = $data['quote']['USD'];
        $this->assertEquals(2000000000000, $usdQuote['total_market_cap']);
        $this->assertNull($usdQuote['total_volume_24h']);
        $this->assertNull($usdQuote['total_market_cap_yesterday_percentage_change']);
    }

    public function testTransformWithInvalidData(): void
    {
        $rawResponse = [
            'status' => ['error_code' => 400, 'error_message' => 'Bad request'],
        ];

        $result = $this->transformer->transformLatest($rawResponse);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(400, $result['status']['error_code']);
        $this->assertEmpty($result['data']);
    }
}