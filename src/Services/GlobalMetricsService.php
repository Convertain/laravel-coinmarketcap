<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Transformers\GlobalMetricsTransformer;

/**
 * Global Metrics Service
 *
 * Handles global cryptocurrency market metrics endpoints from CoinMarketCap Pro API v2
 * including total market capitalization, dominance percentages, and trading volumes.
 */
class GlobalMetricsService
{
    private CoinMarketCapClient $client;
    private GlobalMetricsTransformer $transformer;
    
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
        $this->transformer = new GlobalMetricsTransformer();
    }
    
    /**
     * Get latest global cryptocurrency market metrics
     *
     * @param string $convert Convert quotes to different currency (default: USD)
     * @param array $convertId Convert quotes to cryptocurrency by CoinMarketCap ID
     * @return array Transformed global metrics data
     */
    public function quotesLatest(string $convert = 'USD', array $convertId = []): array
    {
        $parameters = [
            'convert' => $convert,
        ];
        
        if (!empty($convertId)) {
            $parameters['convert_id'] = implode(',', $convertId);
        }
        
        $response = $this->client->get('/global-metrics/quotes/latest', $parameters, 300); // Cache for 5 minutes
        
        return $this->transformer->transformLatest($response);
    }
    
    /**
     * Get historical global cryptocurrency market metrics
     *
     * @param string $timeStart Time period start (ISO 8601 format)
     * @param string|null $timeEnd Time period end (ISO 8601 format, optional)
     * @param int $count Number of intervals to return (default: 10, max: 10000)
     * @param string $interval Time interval between data points
     *                         Valid values: "5m", "10m", "15m", "30m", "45m", "1h", "2h", "3h", "4h", "6h", "12h", "1d", "2d", "3d", "7d", "14d", "15d", "30d", "60d", "90d", "365d"
     * @param string $convert Convert quotes to different currency (default: USD)
     * @param array $convertId Convert quotes to cryptocurrency by CoinMarketCap ID
     * @param array $aux Optionally specify supplemental data fields to return
     * @return array Transformed historical global metrics data
     */
    public function quotesHistorical(
        string $timeStart,
        ?string $timeEnd = null,
        int $count = 10,
        string $interval = '1d',
        string $convert = 'USD',
        array $convertId = [],
        array $aux = []
    ): array {
        $parameters = [
            'time_start' => $timeStart,
            'count' => $count,
            'interval' => $interval,
            'convert' => $convert,
        ];
        
        if ($timeEnd !== null) {
            $parameters['time_end'] = $timeEnd;
        }
        
        if (!empty($convertId)) {
            $parameters['convert_id'] = implode(',', $convertId);
        }
        
        if (!empty($aux)) {
            $parameters['aux'] = implode(',', $aux);
        }
        
        $response = $this->client->get('/global-metrics/quotes/historical', $parameters, 3600); // Cache for 1 hour
        
        return $this->transformer->transformHistorical($response);
    }
    
    /**
     * Get market dominance metrics for the latest global data
     *
     * Returns cryptocurrency market dominance percentages including Bitcoin dominance,
     * Ethereum dominance, and other major cryptocurrencies.
     *
     * @param string $convert Convert quotes to different currency
     * @return array Transformed dominance data
     */
    public function getDominanceMetrics(string $convert = 'USD'): array
    {
        $response = $this->quotesLatest($convert);
        
        return $this->transformer->extractDominanceMetrics($response);
    }
    
    /**
     * Get market cap tiers breakdown
     *
     * Returns breakdown of market capitalization by different tiers
     * (large cap, mid cap, small cap cryptocurrencies).
     *
     * @param string $convert Convert quotes to different currency
     * @return array Market cap tiers data
     */
    public function getMarketCapTiers(string $convert = 'USD'): array
    {
        $response = $this->quotesLatest($convert);
        
        return $this->transformer->extractMarketCapTiers($response);
    }
    
    /**
     * Get volume analysis metrics
     *
     * Returns detailed volume analysis including spot trading volume,
     * derivatives volume, and other trading metrics.
     *
     * @param string $convert Convert quotes to different currency
     * @return array Volume analysis data
     */
    public function getVolumeAnalysis(string $convert = 'USD'): array
    {
        $response = $this->quotesLatest($convert);
        
        return $this->transformer->extractVolumeAnalysis($response);
    }
    
    /**
     * Get DeFi market metrics
     *
     * Returns DeFi-specific market metrics including total value locked (TVL)
     * and DeFi market cap data where available.
     *
     * @param string $convert Convert quotes to different currency
     * @return array DeFi metrics data
     */
    public function getDeFiMetrics(string $convert = 'USD'): array
    {
        $response = $this->quotesLatest($convert);
        
        return $this->transformer->extractDeFiMetrics($response);
    }
    
    /**
     * Calculate market trend indicators
     *
     * Analyzes historical data to provide trend indicators such as
     * market direction, momentum, and volatility metrics.
     *
     * @param int $days Number of days to analyze (default: 30)
     * @param string $convert Convert quotes to different currency
     * @return array Trend analysis data
     */
    public function getTrendAnalysis(int $days = 30, string $convert = 'USD'): array
    {
        $timeStart = now()->subDays($days)->toISOString();
        $timeEnd = now()->toISOString();
        
        $response = $this->quotesHistorical(
            $timeStart,
            $timeEnd,
            $days,
            '1d',
            $convert
        );
        
        return $this->transformer->calculateTrendIndicators($response);
    }
    
    /**
     * Get fear and greed index (if available in response)
     *
     * Returns market sentiment indicators based on global market data.
     *
     * @param string $convert Convert quotes to different currency
     * @return array Sentiment analysis data
     */
    public function getSentimentAnalysis(string $convert = 'USD'): array
    {
        $response = $this->quotesLatest($convert);
        
        return $this->transformer->extractSentimentData($response);
    }
}