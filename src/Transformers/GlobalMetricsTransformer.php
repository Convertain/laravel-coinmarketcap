<?php

namespace Convertain\CoinMarketCap\Transformers;

use Convertain\CoinMarketCap\Contracts\TransformerInterface;
use Carbon\Carbon;

/**
 * Transforms CoinMarketCap global metrics API responses into standardized format.
 */
class GlobalMetricsTransformer implements TransformerInterface
{
    /**
     * Transform raw global metrics data into standardized format.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return array<string, mixed> Transformed and normalized data
     */
    public function transform(array $data): array
    {
        // Handle both single item and collection structures
        if (isset($data['data']) && is_array($data['data'])) {
            return [
                'data' => $this->transformGlobalMetrics($data['data']),
                'status' => $this->transformStatus($data['status'] ?? []),
                'meta' => $this->extractMeta($data)
            ];
        }

        // Single item without 'data' wrapper
        return $this->transformGlobalMetrics($data);
    }

    /**
     * Transform a collection of global metrics items.
     *
     * @param array<int, array<string, mixed>> $items Array of raw global metrics items
     * @return array<int, array<string, mixed>> Array of transformed global metrics items
     */
    public function transformCollection(array $items): array
    {
        return array_map([$this, 'transformGlobalMetrics'], $items);
    }

    /**
     * Validate if the raw data structure is compatible with global metrics transformer.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return bool True if data is compatible
     */
    public function canTransform(array $data): bool
    {
        // Check for global metrics-specific fields
        if (isset($data['data'])) {
            $testData = $data['data'];
        } else {
            $testData = $data;
        }

        return is_array($testData) && (
            isset($testData['active_cryptocurrencies']) || 
            isset($testData['total_cryptocurrencies']) || 
            isset($testData['active_exchanges']) ||
            isset($testData['quote'])
        );
    }

    /**
     * Transform global metrics data.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return array<string, mixed> Transformed global metrics data
     */
    private function transformGlobalMetrics(array $metrics): array
    {
        $transformed = [
            'active_cryptocurrencies' => $this->extractActiveCryptocurrencies($metrics),
            'total_cryptocurrencies' => $this->extractTotalCryptocurrencies($metrics),
            'active_exchanges' => $this->extractActiveExchanges($metrics),
            'total_exchanges' => $this->extractTotalExchanges($metrics),
            'active_market_pairs' => $this->extractActiveMarketPairs($metrics),
            'btc_dominance' => $this->extractBtcDominance($metrics),
            'eth_dominance' => $this->extractEthDominance($metrics),
            'btc_dominance_yesterday' => $this->extractBtcDominanceYesterday($metrics),
            'btc_dominance_24h_percentage_change' => $this->extractBtcDominance24hPercentageChange($metrics),
            'stablecoin_volume_24h' => $this->extractStablecoinVolume24h($metrics),
            'stablecoin_volume_24h_percentage_change' => $this->extractStablecoinVolume24hPercentageChange($metrics),
            'stablecoin_market_cap' => $this->extractStablecoinMarketCap($metrics),
            'stablecoin_market_cap_percentage_change' => $this->extractStablecoinMarketCapPercentageChange($metrics),
            'defi_volume_24h' => $this->extractDefiVolume24h($metrics),
            'defi_volume_24h_percentage_change' => $this->extractDefiVolume24hPercentageChange($metrics),
            'defi_24h_percentage_change' => $this->extractDefi24hPercentageChange($metrics),
            'defi_market_cap' => $this->extractDefiMarketCap($metrics),
            'derivatives_volume_24h' => $this->extractDerivativesVolume24h($metrics),
            'derivatives_volume_24h_percentage_change' => $this->extractDerivativesVolume24hPercentageChange($metrics),
            'derivatives_24h_percentage_change' => $this->extractDerivatives24hPercentageChange($metrics),
            'total_volume_24h_yesterday' => $this->extractTotalVolume24hYesterday($metrics),
            'total_volume_24h_percentage_change' => $this->extractTotalVolume24hPercentageChange($metrics),
            'total_market_cap_yesterday' => $this->extractTotalMarketCapYesterday($metrics),
            'total_market_cap_yesterday_percentage_change' => $this->extractTotalMarketCapYesterdayPercentageChange($metrics),
            'last_updated' => $this->extractLastUpdated($metrics),
            'quotes' => $this->extractQuotes($metrics),
        ];

        // Remove null values to keep response clean
        return array_filter($transformed, fn($value) => $value !== null);
    }

    /**
     * Extract active cryptocurrencies count.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return int|null Active cryptocurrencies count
     */
    private function extractActiveCryptocurrencies(array $metrics): ?int
    {
        if (isset($metrics['active_cryptocurrencies'])) {
            return (int) $metrics['active_cryptocurrencies'];
        }
        return null;
    }

    /**
     * Extract total cryptocurrencies count.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return int|null Total cryptocurrencies count
     */
    private function extractTotalCryptocurrencies(array $metrics): ?int
    {
        if (isset($metrics['total_cryptocurrencies'])) {
            return (int) $metrics['total_cryptocurrencies'];
        }
        return null;
    }

    /**
     * Extract active exchanges count.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return int|null Active exchanges count
     */
    private function extractActiveExchanges(array $metrics): ?int
    {
        if (isset($metrics['active_exchanges'])) {
            return (int) $metrics['active_exchanges'];
        }
        return null;
    }

    /**
     * Extract total exchanges count.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return int|null Total exchanges count
     */
    private function extractTotalExchanges(array $metrics): ?int
    {
        if (isset($metrics['total_exchanges'])) {
            return (int) $metrics['total_exchanges'];
        }
        return null;
    }

    /**
     * Extract active market pairs count.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return int|null Active market pairs count
     */
    private function extractActiveMarketPairs(array $metrics): ?int
    {
        if (isset($metrics['active_market_pairs'])) {
            return (int) $metrics['active_market_pairs'];
        }
        return null;
    }

    /**
     * Extract BTC dominance percentage.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null BTC dominance percentage
     */
    private function extractBtcDominance(array $metrics): ?float
    {
        if (isset($metrics['btc_dominance'])) {
            return $metrics['btc_dominance'] !== null ? (float) $metrics['btc_dominance'] : null;
        }
        return null;
    }

    /**
     * Extract ETH dominance percentage.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null ETH dominance percentage
     */
    private function extractEthDominance(array $metrics): ?float
    {
        if (isset($metrics['eth_dominance'])) {
            return $metrics['eth_dominance'] !== null ? (float) $metrics['eth_dominance'] : null;
        }
        return null;
    }

    /**
     * Extract BTC dominance yesterday.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null BTC dominance yesterday
     */
    private function extractBtcDominanceYesterday(array $metrics): ?float
    {
        if (isset($metrics['btc_dominance_yesterday'])) {
            return $metrics['btc_dominance_yesterday'] !== null ? (float) $metrics['btc_dominance_yesterday'] : null;
        }
        return null;
    }

    /**
     * Extract BTC dominance 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null BTC dominance 24h percentage change
     */
    private function extractBtcDominance24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['btc_dominance_24h_percentage_change'])) {
            return $metrics['btc_dominance_24h_percentage_change'] !== null ? (float) $metrics['btc_dominance_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract stablecoin volume 24h.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Stablecoin volume 24h
     */
    private function extractStablecoinVolume24h(array $metrics): ?float
    {
        if (isset($metrics['stablecoin_volume_24h'])) {
            return $metrics['stablecoin_volume_24h'] !== null ? (float) $metrics['stablecoin_volume_24h'] : null;
        }
        return null;
    }

    /**
     * Extract stablecoin volume 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Stablecoin volume 24h percentage change
     */
    private function extractStablecoinVolume24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['stablecoin_volume_24h_percentage_change'])) {
            return $metrics['stablecoin_volume_24h_percentage_change'] !== null ? (float) $metrics['stablecoin_volume_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract stablecoin market cap.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Stablecoin market cap
     */
    private function extractStablecoinMarketCap(array $metrics): ?float
    {
        if (isset($metrics['stablecoin_market_cap'])) {
            return $metrics['stablecoin_market_cap'] !== null ? (float) $metrics['stablecoin_market_cap'] : null;
        }
        return null;
    }

    /**
     * Extract stablecoin market cap percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Stablecoin market cap percentage change
     */
    private function extractStablecoinMarketCapPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['stablecoin_market_cap_percentage_change'])) {
            return $metrics['stablecoin_market_cap_percentage_change'] !== null ? (float) $metrics['stablecoin_market_cap_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract DeFi volume 24h.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null DeFi volume 24h
     */
    private function extractDefiVolume24h(array $metrics): ?float
    {
        if (isset($metrics['defi_volume_24h'])) {
            return $metrics['defi_volume_24h'] !== null ? (float) $metrics['defi_volume_24h'] : null;
        }
        return null;
    }

    /**
     * Extract DeFi volume 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null DeFi volume 24h percentage change
     */
    private function extractDefiVolume24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['defi_volume_24h_percentage_change'])) {
            return $metrics['defi_volume_24h_percentage_change'] !== null ? (float) $metrics['defi_volume_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract DeFi 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null DeFi 24h percentage change
     */
    private function extractDefi24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['defi_24h_percentage_change'])) {
            return $metrics['defi_24h_percentage_change'] !== null ? (float) $metrics['defi_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract DeFi market cap.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null DeFi market cap
     */
    private function extractDefiMarketCap(array $metrics): ?float
    {
        if (isset($metrics['defi_market_cap'])) {
            return $metrics['defi_market_cap'] !== null ? (float) $metrics['defi_market_cap'] : null;
        }
        return null;
    }

    /**
     * Extract derivatives volume 24h.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Derivatives volume 24h
     */
    private function extractDerivativesVolume24h(array $metrics): ?float
    {
        if (isset($metrics['derivatives_volume_24h'])) {
            return $metrics['derivatives_volume_24h'] !== null ? (float) $metrics['derivatives_volume_24h'] : null;
        }
        return null;
    }

    /**
     * Extract derivatives volume 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Derivatives volume 24h percentage change
     */
    private function extractDerivativesVolume24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['derivatives_volume_24h_percentage_change'])) {
            return $metrics['derivatives_volume_24h_percentage_change'] !== null ? (float) $metrics['derivatives_volume_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract derivatives 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Derivatives 24h percentage change
     */
    private function extractDerivatives24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['derivatives_24h_percentage_change'])) {
            return $metrics['derivatives_24h_percentage_change'] !== null ? (float) $metrics['derivatives_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract total volume 24h yesterday.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Total volume 24h yesterday
     */
    private function extractTotalVolume24hYesterday(array $metrics): ?float
    {
        if (isset($metrics['total_volume_24h_yesterday'])) {
            return $metrics['total_volume_24h_yesterday'] !== null ? (float) $metrics['total_volume_24h_yesterday'] : null;
        }
        return null;
    }

    /**
     * Extract total volume 24h percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Total volume 24h percentage change
     */
    private function extractTotalVolume24hPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['total_volume_24h_percentage_change'])) {
            return $metrics['total_volume_24h_percentage_change'] !== null ? (float) $metrics['total_volume_24h_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract total market cap yesterday.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Total market cap yesterday
     */
    private function extractTotalMarketCapYesterday(array $metrics): ?float
    {
        if (isset($metrics['total_market_cap_yesterday'])) {
            return $metrics['total_market_cap_yesterday'] !== null ? (float) $metrics['total_market_cap_yesterday'] : null;
        }
        return null;
    }

    /**
     * Extract total market cap yesterday percentage change.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return float|null Total market cap yesterday percentage change
     */
    private function extractTotalMarketCapYesterdayPercentageChange(array $metrics): ?float
    {
        if (isset($metrics['total_market_cap_yesterday_percentage_change'])) {
            return $metrics['total_market_cap_yesterday_percentage_change'] !== null ? (float) $metrics['total_market_cap_yesterday_percentage_change'] : null;
        }
        return null;
    }

    /**
     * Extract and normalize last updated timestamp.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return string|null Normalized last updated (ISO 8601)
     */
    private function extractLastUpdated(array $metrics): ?string
    {
        if (isset($metrics['last_updated'])) {
            return $this->normalizeDateTime($metrics['last_updated']);
        }
        return null;
    }

    /**
     * Extract and normalize quotes data.
     *
     * @param array<string, mixed> $metrics Raw global metrics data
     * @return array<string, array<string, mixed>>|null Normalized quotes
     */
    private function extractQuotes(array $metrics): ?array
    {
        if (!isset($metrics['quote']) || !is_array($metrics['quote'])) {
            return null;
        }

        $quotes = [];
        foreach ($metrics['quote'] as $currency => $quote) {
            if (!is_array($quote)) {
                continue;
            }

            $quotes[strtoupper($currency)] = [
                'total_market_cap' => isset($quote['total_market_cap']) ? (float) $quote['total_market_cap'] : null,
                'total_volume_24h' => isset($quote['total_volume_24h']) ? (float) $quote['total_volume_24h'] : null,
                'total_volume_24h_reported' => isset($quote['total_volume_24h_reported']) ? (float) $quote['total_volume_24h_reported'] : null,
                'altcoin_volume_24h' => isset($quote['altcoin_volume_24h']) ? (float) $quote['altcoin_volume_24h'] : null,
                'altcoin_market_cap' => isset($quote['altcoin_market_cap']) ? (float) $quote['altcoin_market_cap'] : null,
                'stablecoin_volume_24h' => isset($quote['stablecoin_volume_24h']) ? (float) $quote['stablecoin_volume_24h'] : null,
                'stablecoin_market_cap' => isset($quote['stablecoin_market_cap']) ? (float) $quote['stablecoin_market_cap'] : null,
                'defi_volume_24h' => isset($quote['defi_volume_24h']) ? (float) $quote['defi_volume_24h'] : null,
                'defi_market_cap' => isset($quote['defi_market_cap']) ? (float) $quote['defi_market_cap'] : null,
                'derivative_volume_24h' => isset($quote['derivative_volume_24h']) ? (float) $quote['derivative_volume_24h'] : null,
                'total_market_cap_yesterday' => isset($quote['total_market_cap_yesterday']) ? (float) $quote['total_market_cap_yesterday'] : null,
                'total_volume_24h_yesterday' => isset($quote['total_volume_24h_yesterday']) ? (float) $quote['total_volume_24h_yesterday'] : null,
                'total_market_cap_yesterday_percentage_change' => isset($quote['total_market_cap_yesterday_percentage_change']) ? (float) $quote['total_market_cap_yesterday_percentage_change'] : null,
                'total_volume_24h_percentage_change' => isset($quote['total_volume_24h_percentage_change']) ? (float) $quote['total_volume_24h_percentage_change'] : null,
                'last_updated' => isset($quote['last_updated']) ? $this->normalizeDateTime($quote['last_updated']) : null,
            ];
        }

        return empty($quotes) ? null : $quotes;
    }

    /**
     * Transform status information from API response.
     *
     * @param array<string, mixed> $status Raw status data
     * @return array<string, mixed> Transformed status data
     */
    private function transformStatus(array $status): array
    {
        return [
            'timestamp' => isset($status['timestamp']) ? $this->normalizeDateTime($status['timestamp']) : null,
            'error_code' => $status['error_code'] ?? null,
            'error_message' => $status['error_message'] ?? null,
            'elapsed' => $status['elapsed'] ?? null,
            'credit_count' => $status['credit_count'] ?? null,
            'notice' => $status['notice'] ?? null,
            'total_count' => $status['total_count'] ?? null,
        ];
    }

    /**
     * Extract metadata from API response.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return array<string, mixed> Metadata
     */
    private function extractMeta(array $data): array
    {
        return [
            'timestamp' => isset($data['status']['timestamp']) ? $this->normalizeDateTime($data['status']['timestamp']) : null,
            'credit_count' => $data['status']['credit_count'] ?? null,
        ];
    }

    /**
     * Normalize datetime string to ISO 8601 format.
     *
     * @param mixed $datetime Raw datetime value
     * @return string|null Normalized datetime string
     */
    private function normalizeDateTime($datetime): ?string
    {
        if ($datetime === null) {
            return null;
        }

        try {
            return Carbon::parse($datetime)->toISOString();
        } catch (\Exception) {
            return null;
        }
    }
}