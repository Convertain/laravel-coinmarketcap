<?php

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Global Metrics Transformer
 *
 * Transforms raw CoinMarketCap global metrics API responses into standardized,
 * clean data structures with calculated derived metrics and analysis.
 */
class GlobalMetricsTransformer
{
    /**
     * Transform latest global metrics response
     *
     * @param array $response Raw API response
     * @return array Transformed global metrics data
     */
    public function transformLatest(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $data = $response['data'];
        
        $transformedData = [
            'active_cryptocurrencies' => $data['active_cryptocurrencies'] ?? null,
            'total_cryptocurrencies' => $data['total_cryptocurrencies'] ?? null,
            'active_market_pairs' => $data['active_market_pairs'] ?? null,
            'active_exchanges' => $data['active_exchanges'] ?? null,
            'total_exchanges' => $data['total_exchanges'] ?? null,
            'eth_dominance' => $data['eth_dominance'] ?? null,
            'btc_dominance' => $data['btc_dominance'] ?? null,
            'eth_dominance_yesterday' => $data['eth_dominance_yesterday'] ?? null,
            'btc_dominance_yesterday' => $data['btc_dominance_yesterday'] ?? null,
            'eth_dominance_24h_percentage_change' => $data['eth_dominance_24h_percentage_change'] ?? null,
            'btc_dominance_24h_percentage_change' => $data['btc_dominance_24h_percentage_change'] ?? null,
            'defi_volume_24h' => $data['defi_volume_24h'] ?? null,
            'defi_volume_24h_reported' => $data['defi_volume_24h_reported'] ?? null,
            'defi_market_cap' => $data['defi_market_cap'] ?? null,
            'defi_24h_percentage_change' => $data['defi_24h_percentage_change'] ?? null,
            'stablecoin_volume_24h' => $data['stablecoin_volume_24h'] ?? null,
            'stablecoin_volume_24h_reported' => $data['stablecoin_volume_24h_reported'] ?? null,
            'stablecoin_market_cap' => $data['stablecoin_market_cap'] ?? null,
            'stablecoin_24h_percentage_change' => $data['stablecoin_24h_percentage_change'] ?? null,
            'derivatives_volume_24h' => $data['derivatives_volume_24h'] ?? null,
            'derivatives_volume_24h_reported' => $data['derivatives_volume_24h_reported'] ?? null,
            'derivatives_24h_percentage_change' => $data['derivatives_24h_percentage_change'] ?? null,
            'last_updated' => $data['last_updated'] ?? null,
            'quote' => $this->transformQuoteData($data['quote'] ?? []),
        ];
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Transform historical global metrics response
     *
     * @param array $response Raw API response
     * @return array Transformed historical global metrics data
     */
    public function transformHistorical(array $response): array
    {
        if (!isset($response['data']) || !isset($response['data']['quotes'])) {
            return $this->createErrorResponse($response);
        }
        
        $data = $response['data'];
        $transformedData = [
            'name' => $data['name'] ?? 'Global Metrics',
            'symbol' => $data['symbol'] ?? 'GLOBAL',
            'id' => $data['id'] ?? null,
            'quotes' => [],
        ];
        
        foreach ($data['quotes'] as $quote) {
            $transformedData['quotes'][] = [
                'timestamp' => $quote['timestamp'] ?? null,
                'search_interval' => $quote['search_interval'] ?? null,
                'active_cryptocurrencies' => $quote['active_cryptocurrencies'] ?? null,
                'total_cryptocurrencies' => $quote['total_cryptocurrencies'] ?? null,
                'active_market_pairs' => $quote['active_market_pairs'] ?? null,
                'active_exchanges' => $quote['active_exchanges'] ?? null,
                'total_exchanges' => $quote['total_exchanges'] ?? null,
                'eth_dominance' => $quote['eth_dominance'] ?? null,
                'btc_dominance' => $quote['btc_dominance'] ?? null,
                'defi_volume_24h' => $quote['defi_volume_24h'] ?? null,
                'defi_volume_24h_reported' => $quote['defi_volume_24h_reported'] ?? null,
                'defi_market_cap' => $quote['defi_market_cap'] ?? null,
                'stablecoin_volume_24h' => $quote['stablecoin_volume_24h'] ?? null,
                'stablecoin_volume_24h_reported' => $quote['stablecoin_volume_24h_reported'] ?? null,
                'stablecoin_market_cap' => $quote['stablecoin_market_cap'] ?? null,
                'derivatives_volume_24h' => $quote['derivatives_volume_24h'] ?? null,
                'derivatives_volume_24h_reported' => $quote['derivatives_volume_24h_reported'] ?? null,
                'quote' => $this->transformQuoteData($quote['quote'] ?? []),
            ];
        }
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Extract dominance metrics from global data
     *
     * @param array $response Transformed global metrics response
     * @return array Dominance metrics data
     */
    public function extractDominanceMetrics(array $response): array
    {
        if (!isset($response['data'])) {
            return ['dominance' => []];
        }
        
        $data = $response['data'];
        
        return [
            'dominance' => [
                'bitcoin' => [
                    'current' => $data['btc_dominance'] ?? null,
                    'yesterday' => $data['btc_dominance_yesterday'] ?? null,
                    'change_24h' => $data['btc_dominance_24h_percentage_change'] ?? null,
                ],
                'ethereum' => [
                    'current' => $data['eth_dominance'] ?? null,
                    'yesterday' => $data['eth_dominance_yesterday'] ?? null,
                    'change_24h' => $data['eth_dominance_24h_percentage_change'] ?? null,
                ],
                'altcoins' => [
                    'current' => $this->calculateAltcoinDominance($data),
                    'yesterday' => $this->calculateAltcoinDominanceYesterday($data),
                    'change_24h' => $this->calculateAltcoinDominanceChange($data),
                ],
            ],
        ];
    }
    
    /**
     * Extract market cap tiers from global data
     *
     * @param array $response Transformed global metrics response
     * @return array Market cap tiers data
     */
    public function extractMarketCapTiers(array $response): array
    {
        if (!isset($response['data']['quote'])) {
            return ['market_cap_tiers' => []];
        }
        
        $quotes = $response['data']['quote'];
        $usdQuote = $quotes['USD'] ?? [];
        
        $totalMarketCap = $usdQuote['total_market_cap'] ?? 0;
        
        // Estimated breakdown (actual breakdown would require additional API calls)
        return [
            'market_cap_tiers' => [
                'large_cap' => [
                    'threshold' => 10000000000, // $10B+
                    'estimated_percentage' => 60, // Estimated
                    'estimated_market_cap' => $totalMarketCap * 0.6,
                ],
                'mid_cap' => [
                    'threshold' => 1000000000, // $1B - $10B
                    'estimated_percentage' => 30,
                    'estimated_market_cap' => $totalMarketCap * 0.3,
                ],
                'small_cap' => [
                    'threshold' => 0, // <$1B
                    'estimated_percentage' => 10,
                    'estimated_market_cap' => $totalMarketCap * 0.1,
                ],
            ],
            'total_market_cap' => $totalMarketCap,
        ];
    }
    
    /**
     * Extract volume analysis from global data
     *
     * @param array $response Transformed global metrics response
     * @return array Volume analysis data
     */
    public function extractVolumeAnalysis(array $response): array
    {
        if (!isset($response['data'])) {
            return ['volume_analysis' => []];
        }
        
        $data = $response['data'];
        $quotes = $data['quote']['USD'] ?? [];
        
        return [
            'volume_analysis' => [
                'spot_volume' => [
                    'total_24h' => $quotes['total_volume_24h'] ?? null,
                    'reported_24h' => $quotes['total_volume_24h_reported'] ?? null,
                    'change_24h' => $quotes['total_volume_24h_yesterday_percentage_change'] ?? null,
                ],
                'derivatives_volume' => [
                    'total_24h' => $data['derivatives_volume_24h'] ?? null,
                    'reported_24h' => $data['derivatives_volume_24h_reported'] ?? null,
                    'change_24h' => $data['derivatives_24h_percentage_change'] ?? null,
                ],
                'defi_volume' => [
                    'total_24h' => $data['defi_volume_24h'] ?? null,
                    'reported_24h' => $data['defi_volume_24h_reported'] ?? null,
                ],
                'stablecoin_volume' => [
                    'total_24h' => $data['stablecoin_volume_24h'] ?? null,
                    'reported_24h' => $data['stablecoin_volume_24h_reported'] ?? null,
                ],
            ],
        ];
    }
    
    /**
     * Extract DeFi metrics from global data
     *
     * @param array $response Transformed global metrics response
     * @return array DeFi metrics data
     */
    public function extractDeFiMetrics(array $response): array
    {
        if (!isset($response['data'])) {
            return ['defi_metrics' => []];
        }
        
        $data = $response['data'];
        
        return [
            'defi_metrics' => [
                'market_cap' => $data['defi_market_cap'] ?? null,
                'volume_24h' => $data['defi_volume_24h'] ?? null,
                'volume_24h_reported' => $data['defi_volume_24h_reported'] ?? null,
                'change_24h' => $data['defi_24h_percentage_change'] ?? null,
                'last_updated' => $data['last_updated'] ?? null,
            ],
            'stablecoin_metrics' => [
                'market_cap' => $data['stablecoin_market_cap'] ?? null,
                'volume_24h' => $data['stablecoin_volume_24h'] ?? null,
                'volume_24h_reported' => $data['stablecoin_volume_24h_reported'] ?? null,
                'change_24h' => $data['stablecoin_24h_percentage_change'] ?? null,
            ],
        ];
    }
    
    /**
     * Calculate trend indicators from historical data
     *
     * @param array $response Historical global metrics response
     * @return array Trend analysis data
     */
    public function calculateTrendIndicators(array $response): array
    {
        if (!isset($response['data']['quotes']) || empty($response['data']['quotes'])) {
            return ['trend_analysis' => []];
        }
        
        $quotes = $response['data']['quotes'];
        $marketCaps = [];
        $volumes = [];
        
        foreach ($quotes as $quote) {
            if (isset($quote['quote']['USD'])) {
                $usd = $quote['quote']['USD'];
                $marketCaps[] = $usd['total_market_cap'] ?? 0;
                $volumes[] = $usd['total_volume_24h'] ?? 0;
            }
        }
        
        if (empty($marketCaps) || empty($volumes)) {
            return ['trend_analysis' => []];
        }
        
        return [
            'trend_analysis' => [
                'market_cap_trend' => $this->calculateTrend($marketCaps),
                'volume_trend' => $this->calculateTrend($volumes),
                'volatility' => $this->calculateVolatility($marketCaps),
                'momentum' => $this->calculateMomentum($marketCaps),
            ],
        ];
    }
    
    /**
     * Extract sentiment data from global metrics
     *
     * @param array $response Transformed global metrics response
     * @return array Sentiment analysis data
     */
    public function extractSentimentData(array $response): array
    {
        if (!isset($response['data']['quote']['USD'])) {
            return ['sentiment' => []];
        }
        
        $usd = $response['data']['quote']['USD'];
        $marketCapChange = $usd['total_market_cap_yesterday_percentage_change'] ?? 0;
        $volumeChange = $usd['total_volume_24h_yesterday_percentage_change'] ?? 0;
        
        // Simple sentiment calculation based on market changes
        $sentiment = 'neutral';
        if ($marketCapChange > 5) {
            $sentiment = 'very_positive';
        } elseif ($marketCapChange > 2) {
            $sentiment = 'positive';
        } elseif ($marketCapChange < -5) {
            $sentiment = 'very_negative';
        } elseif ($marketCapChange < -2) {
            $sentiment = 'negative';
        }
        
        return [
            'sentiment' => [
                'overall' => $sentiment,
                'market_cap_change_24h' => $marketCapChange,
                'volume_change_24h' => $volumeChange,
                'fear_greed_index' => $this->calculateFearGreedIndex($marketCapChange, $volumeChange),
            ],
        ];
    }
    
    /**
     * Transform quote data structure
     *
     * @param array $quote Raw quote data
     * @return array Transformed quote data
     */
    private function transformQuoteData(array $quote): array
    {
        $transformedQuote = [];
        
        foreach ($quote as $currency => $data) {
            $transformedQuote[$currency] = [
                'total_market_cap' => $data['total_market_cap'] ?? null,
                'total_volume_24h' => $data['total_volume_24h'] ?? null,
                'total_volume_24h_reported' => $data['total_volume_24h_reported'] ?? null,
                'altcoin_volume_24h' => $data['altcoin_volume_24h'] ?? null,
                'altcoin_volume_24h_reported' => $data['altcoin_volume_24h_reported'] ?? null,
                'altcoin_market_cap' => $data['altcoin_market_cap'] ?? null,
                'total_market_cap_yesterday' => $data['total_market_cap_yesterday'] ?? null,
                'total_volume_24h_yesterday' => $data['total_volume_24h_yesterday'] ?? null,
                'total_market_cap_yesterday_percentage_change' => $data['total_market_cap_yesterday_percentage_change'] ?? null,
                'total_volume_24h_yesterday_percentage_change' => $data['total_volume_24h_yesterday_percentage_change'] ?? null,
                'last_updated' => $data['last_updated'] ?? null,
            ];
        }
        
        return $transformedQuote;
    }
    
    /**
     * Calculate altcoin dominance
     */
    private function calculateAltcoinDominance(array $data): ?float
    {
        $btcDominance = $data['btc_dominance'] ?? null;
        $ethDominance = $data['eth_dominance'] ?? null;
        
        if ($btcDominance !== null && $ethDominance !== null) {
            return 100 - $btcDominance - $ethDominance;
        }
        
        return null;
    }
    
    /**
     * Calculate altcoin dominance yesterday
     */
    private function calculateAltcoinDominanceYesterday(array $data): ?float
    {
        $btcDominance = $data['btc_dominance_yesterday'] ?? null;
        $ethDominance = $data['eth_dominance_yesterday'] ?? null;
        
        if ($btcDominance !== null && $ethDominance !== null) {
            return 100 - $btcDominance - $ethDominance;
        }
        
        return null;
    }
    
    /**
     * Calculate altcoin dominance change
     */
    private function calculateAltcoinDominanceChange(array $data): ?float
    {
        $currentAltcoin = $this->calculateAltcoinDominance($data);
        $yesterdayAltcoin = $this->calculateAltcoinDominanceYesterday($data);
        
        if ($currentAltcoin !== null && $yesterdayAltcoin !== null && $yesterdayAltcoin != 0) {
            return (($currentAltcoin - $yesterdayAltcoin) / $yesterdayAltcoin) * 100;
        }
        
        return null;
    }
    
    /**
     * Calculate trend from array of values
     */
    private function calculateTrend(array $values): string
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }
        
        $first = reset($values);
        $last = end($values);
        
        if ($last > $first * 1.05) {
            return 'upward';
        } elseif ($last < $first * 0.95) {
            return 'downward';
        }
        
        return 'sideways';
    }
    
    /**
     * Calculate volatility from array of values
     */
    private function calculateVolatility(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance = $variance / count($values);
        return sqrt($variance) / $mean * 100; // Coefficient of variation as percentage
    }
    
    /**
     * Calculate momentum from array of values
     */
    private function calculateMomentum(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        
        $recent = array_slice($values, -5); // Last 5 values
        $older = array_slice($values, 0, 5); // First 5 values
        
        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg = array_sum($older) / count($older);
        
        if ($olderAvg == 0) {
            return 0;
        }
        
        return (($recentAvg - $olderAvg) / $olderAvg) * 100;
    }
    
    /**
     * Calculate simple fear and greed index
     */
    private function calculateFearGreedIndex(float $marketCapChange, float $volumeChange): int
    {
        // Simple calculation based on market and volume changes
        // Range: 0-100 (0 = Extreme Fear, 100 = Extreme Greed)
        
        $score = 50; // Neutral starting point
        
        // Adjust based on market cap change
        $score += $marketCapChange * 2;
        
        // Adjust based on volume change
        $score += $volumeChange;
        
        // Ensure score stays within 0-100 range
        return max(0, min(100, intval($score)));
    }
    
    /**
     * Create success response structure
     */
    private function createSuccessResponse(array $data, array $originalResponse): array
    {
        return [
            'status' => $originalResponse['status'] ?? ['error_code' => 0, 'error_message' => null],
            'data' => $data,
        ];
    }
    
    /**
     * Create error response structure
     */
    private function createErrorResponse(array $response): array
    {
        return [
            'status' => $response['status'] ?? ['error_code' => 400, 'error_message' => 'Invalid response'],
            'data' => [],
        ];
    }
}