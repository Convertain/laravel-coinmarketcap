<?php

namespace Convertain\CoinMarketCap\Transformers;

use Convertain\CoinMarketCap\Contracts\TransformerInterface;
use Carbon\Carbon;

/**
 * Transforms CoinMarketCap exchange API responses into standardized format.
 */
class ExchangeTransformer implements TransformerInterface
{
    /**
     * Transform raw exchange data into standardized format.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return array<string, mixed> Transformed and normalized data
     */
    public function transform(array $data): array
    {
        // Handle both single item and collection structures
        if (isset($data['data']) && is_array($data['data'])) {
            if (isset($data['data'][0]) || !empty($data['data'])) {
                // Collection or single item in 'data' wrapper
                $items = is_array($data['data']) && isset($data['data'][0]) 
                    ? $data['data'] 
                    : [$data['data']];
                
                return [
                    'data' => $this->transformCollection($items),
                    'status' => $this->transformStatus($data['status'] ?? []),
                    'meta' => $this->extractMeta($data)
                ];
            }
        }

        // Single item without 'data' wrapper
        return $this->transformExchange($data);
    }

    /**
     * Transform a collection of exchange items.
     *
     * @param array<int, array<string, mixed>> $items Array of raw exchange items
     * @return array<int, array<string, mixed>> Array of transformed exchange items
     */
    public function transformCollection(array $items): array
    {
        return array_map([$this, 'transformExchange'], $items);
    }

    /**
     * Validate if the raw data structure is compatible with exchange transformer.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return bool True if data is compatible
     */
    public function canTransform(array $data): bool
    {
        // Check for exchange-specific fields
        if (isset($data['data'])) {
            $testData = is_array($data['data']) && isset($data['data'][0]) 
                ? $data['data'][0] 
                : $data['data'];
        } else {
            $testData = $data;
        }

        return is_array($testData) && (
            isset($testData['id']) || 
            isset($testData['name']) || 
            isset($testData['slug']) ||
            isset($testData['spot_volume_usd'])
        );
    }

    /**
     * Transform a single exchange item.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string, mixed> Transformed exchange data
     */
    private function transformExchange(array $exchange): array
    {
        $transformed = [
            'id' => $this->extractId($exchange),
            'name' => $this->extractName($exchange),
            'slug' => $this->extractSlug($exchange),
            'description' => $this->extractDescription($exchange),
            'logo' => $this->extractLogo($exchange),
            'website' => $this->extractWebsite($exchange),
            'blog' => $this->extractBlog($exchange),
            'chat' => $this->extractChat($exchange),
            'fee' => $this->extractFee($exchange),
            'twitter' => $this->extractTwitter($exchange),
            'support' => $this->extractSupport($exchange),
            'notice' => $this->extractNotice($exchange),
            'countries' => $this->extractCountries($exchange),
            'fiats' => $this->extractFiats($exchange),
            'tags' => $this->extractTags($exchange),
            'type' => $this->extractType($exchange),
            'date_launched' => $this->extractDateLaunched($exchange),
            'is_redistributed' => $this->extractIsRedistributed($exchange),
            'maker_fee' => $this->extractMakerFee($exchange),
            'taker_fee' => $this->extractTakerFee($exchange),
            'weekly_visits' => $this->extractWeeklyVisits($exchange),
            'spot_volume_usd' => $this->extractSpotVolumeUsd($exchange),
            'spot_volume_last_updated' => $this->extractSpotVolumeLastUpdated($exchange),
            'derivatives_volume_usd' => $this->extractDerivativesVolumeUsd($exchange),
            'derivatives_volume_last_updated' => $this->extractDerivativesVolumeLastUpdated($exchange),
            'quotes' => $this->extractQuotes($exchange),
            'last_updated' => $this->extractLastUpdated($exchange),
            'num_coins' => $this->extractNumCoins($exchange),
            'num_market_pairs' => $this->extractNumMarketPairs($exchange),
            'market_share' => $this->extractMarketShare($exchange),
            'exchange_score' => $this->extractExchangeScore($exchange),
            'liquidity_score' => $this->extractLiquidityScore($exchange),
            'traffic_score' => $this->extractTrafficScore($exchange),
        ];

        // Remove null values to keep response clean
        return array_filter($transformed, fn($value) => $value !== null);
    }

    /**
     * Extract and normalize exchange ID.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return int|null Normalized exchange ID
     */
    private function extractId(array $exchange): ?int
    {
        return isset($exchange['id']) ? (int) $exchange['id'] : null;
    }

    /**
     * Extract and normalize exchange name.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Normalized exchange name
     */
    private function extractName(array $exchange): ?string
    {
        return isset($exchange['name']) ? trim((string) $exchange['name']) : null;
    }

    /**
     * Extract and normalize exchange slug.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Normalized exchange slug
     */
    private function extractSlug(array $exchange): ?string
    {
        return isset($exchange['slug']) ? trim((string) $exchange['slug']) : null;
    }

    /**
     * Extract exchange description.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Exchange description
     */
    private function extractDescription(array $exchange): ?string
    {
        return isset($exchange['description']) ? trim((string) $exchange['description']) : null;
    }

    /**
     * Extract exchange logo URL.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Logo URL
     */
    private function extractLogo(array $exchange): ?string
    {
        return isset($exchange['logo']) ? (string) $exchange['logo'] : null;
    }

    /**
     * Extract website URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Website URLs
     */
    private function extractWebsite(array $exchange): ?array
    {
        if (isset($exchange['urls']['website']) && is_array($exchange['urls']['website'])) {
            return array_filter($exchange['urls']['website']);
        }
        return null;
    }

    /**
     * Extract blog URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Blog URLs
     */
    private function extractBlog(array $exchange): ?array
    {
        if (isset($exchange['urls']['blog']) && is_array($exchange['urls']['blog'])) {
            return array_filter($exchange['urls']['blog']);
        }
        return null;
    }

    /**
     * Extract chat URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Chat URLs
     */
    private function extractChat(array $exchange): ?array
    {
        if (isset($exchange['urls']['chat']) && is_array($exchange['urls']['chat'])) {
            return array_filter($exchange['urls']['chat']);
        }
        return null;
    }

    /**
     * Extract fee URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Fee URLs
     */
    private function extractFee(array $exchange): ?array
    {
        if (isset($exchange['urls']['fee']) && is_array($exchange['urls']['fee'])) {
            return array_filter($exchange['urls']['fee']);
        }
        return null;
    }

    /**
     * Extract Twitter URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Twitter URLs
     */
    private function extractTwitter(array $exchange): ?array
    {
        if (isset($exchange['urls']['twitter']) && is_array($exchange['urls']['twitter'])) {
            return array_filter($exchange['urls']['twitter']);
        }
        return null;
    }

    /**
     * Extract support URLs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Support URLs
     */
    private function extractSupport(array $exchange): ?array
    {
        if (isset($exchange['urls']['support']) && is_array($exchange['urls']['support'])) {
            return array_filter($exchange['urls']['support']);
        }
        return null;
    }

    /**
     * Extract notice.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Notice
     */
    private function extractNotice(array $exchange): ?string
    {
        return isset($exchange['notice']) ? trim((string) $exchange['notice']) : null;
    }

    /**
     * Extract supported countries.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Supported countries
     */
    private function extractCountries(array $exchange): ?array
    {
        if (isset($exchange['countries']) && is_array($exchange['countries'])) {
            return array_filter($exchange['countries']);
        }
        return null;
    }

    /**
     * Extract supported fiat currencies.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Supported fiat currencies
     */
    private function extractFiats(array $exchange): ?array
    {
        if (isset($exchange['fiats']) && is_array($exchange['fiats'])) {
            return array_map('strtoupper', array_filter($exchange['fiats']));
        }
        return null;
    }

    /**
     * Extract and normalize tags.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string>|null Normalized tags
     */
    private function extractTags(array $exchange): ?array
    {
        if (isset($exchange['tags']) && is_array($exchange['tags'])) {
            return array_filter($exchange['tags']);
        }
        return null;
    }

    /**
     * Extract exchange type.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Exchange type
     */
    private function extractType(array $exchange): ?string
    {
        return isset($exchange['type']) ? trim((string) $exchange['type']) : null;
    }

    /**
     * Extract and normalize date launched.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Normalized date launched (ISO 8601)
     */
    private function extractDateLaunched(array $exchange): ?string
    {
        if (isset($exchange['date_launched'])) {
            return $this->normalizeDateTime($exchange['date_launched']);
        }
        return null;
    }

    /**
     * Extract is redistributed flag.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return bool|null Is redistributed flag
     */
    private function extractIsRedistributed(array $exchange): ?bool
    {
        if (isset($exchange['is_redistributed'])) {
            return (bool) $exchange['is_redistributed'];
        }
        return null;
    }

    /**
     * Extract maker fee.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Maker fee percentage
     */
    private function extractMakerFee(array $exchange): ?float
    {
        if (isset($exchange['maker_fee'])) {
            return $exchange['maker_fee'] !== null ? (float) $exchange['maker_fee'] : null;
        }
        return null;
    }

    /**
     * Extract taker fee.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Taker fee percentage
     */
    private function extractTakerFee(array $exchange): ?float
    {
        if (isset($exchange['taker_fee'])) {
            return $exchange['taker_fee'] !== null ? (float) $exchange['taker_fee'] : null;
        }
        return null;
    }

    /**
     * Extract weekly visits.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return int|null Weekly visits
     */
    private function extractWeeklyVisits(array $exchange): ?int
    {
        if (isset($exchange['weekly_visits'])) {
            return $exchange['weekly_visits'] !== null ? (int) $exchange['weekly_visits'] : null;
        }
        return null;
    }

    /**
     * Extract spot volume in USD.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Spot volume in USD
     */
    private function extractSpotVolumeUsd(array $exchange): ?float
    {
        if (isset($exchange['spot_volume_usd'])) {
            return $exchange['spot_volume_usd'] !== null ? (float) $exchange['spot_volume_usd'] : null;
        }
        return null;
    }

    /**
     * Extract spot volume last updated.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Spot volume last updated (ISO 8601)
     */
    private function extractSpotVolumeLastUpdated(array $exchange): ?string
    {
        if (isset($exchange['spot_volume_last_updated'])) {
            return $this->normalizeDateTime($exchange['spot_volume_last_updated']);
        }
        return null;
    }

    /**
     * Extract derivatives volume in USD.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Derivatives volume in USD
     */
    private function extractDerivativesVolumeUsd(array $exchange): ?float
    {
        if (isset($exchange['derivatives_volume_usd'])) {
            return $exchange['derivatives_volume_usd'] !== null ? (float) $exchange['derivatives_volume_usd'] : null;
        }
        return null;
    }

    /**
     * Extract derivatives volume last updated.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Derivatives volume last updated (ISO 8601)
     */
    private function extractDerivativesVolumeLastUpdated(array $exchange): ?string
    {
        if (isset($exchange['derivatives_volume_last_updated'])) {
            return $this->normalizeDateTime($exchange['derivatives_volume_last_updated']);
        }
        return null;
    }

    /**
     * Extract and normalize quotes data.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return array<string, array<string, mixed>>|null Normalized quotes
     */
    private function extractQuotes(array $exchange): ?array
    {
        if (!isset($exchange['quote']) || !is_array($exchange['quote'])) {
            return null;
        }

        $quotes = [];
        foreach ($exchange['quote'] as $currency => $quote) {
            if (!is_array($quote)) {
                continue;
            }

            $quotes[strtoupper($currency)] = [
                'volume_24h' => isset($quote['volume_24h']) ? (float) $quote['volume_24h'] : null,
                'volume_24h_adjusted' => isset($quote['volume_24h_adjusted']) ? (float) $quote['volume_24h_adjusted'] : null,
                'volume_7d' => isset($quote['volume_7d']) ? (float) $quote['volume_7d'] : null,
                'volume_30d' => isset($quote['volume_30d']) ? (float) $quote['volume_30d'] : null,
                'percent_change_volume_24h' => isset($quote['percent_change_volume_24h']) ? (float) $quote['percent_change_volume_24h'] : null,
                'percent_change_volume_7d' => isset($quote['percent_change_volume_7d']) ? (float) $quote['percent_change_volume_7d'] : null,
                'percent_change_volume_30d' => isset($quote['percent_change_volume_30d']) ? (float) $quote['percent_change_volume_30d'] : null,
                'effective_liquidity_24h' => isset($quote['effective_liquidity_24h']) ? (float) $quote['effective_liquidity_24h'] : null,
                'derivative_volume_usd' => isset($quote['derivative_volume_usd']) ? (float) $quote['derivative_volume_usd'] : null,
                'spot_volume_usd' => isset($quote['spot_volume_usd']) ? (float) $quote['spot_volume_usd'] : null,
                'last_updated' => isset($quote['last_updated']) ? $this->normalizeDateTime($quote['last_updated']) : null,
            ];
        }

        return empty($quotes) ? null : $quotes;
    }

    /**
     * Extract and normalize last updated timestamp.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return string|null Normalized last updated (ISO 8601)
     */
    private function extractLastUpdated(array $exchange): ?string
    {
        if (isset($exchange['last_updated'])) {
            return $this->normalizeDateTime($exchange['last_updated']);
        }
        return null;
    }

    /**
     * Extract number of coins.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return int|null Number of coins
     */
    private function extractNumCoins(array $exchange): ?int
    {
        if (isset($exchange['num_coins'])) {
            return $exchange['num_coins'] !== null ? (int) $exchange['num_coins'] : null;
        }
        return null;
    }

    /**
     * Extract number of market pairs.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return int|null Number of market pairs
     */
    private function extractNumMarketPairs(array $exchange): ?int
    {
        if (isset($exchange['num_market_pairs'])) {
            return $exchange['num_market_pairs'] !== null ? (int) $exchange['num_market_pairs'] : null;
        }
        return null;
    }

    /**
     * Extract market share.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Market share percentage
     */
    private function extractMarketShare(array $exchange): ?float
    {
        if (isset($exchange['market_share'])) {
            return $exchange['market_share'] !== null ? (float) $exchange['market_share'] : null;
        }
        return null;
    }

    /**
     * Extract exchange score.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Exchange score
     */
    private function extractExchangeScore(array $exchange): ?float
    {
        if (isset($exchange['exchange_score'])) {
            return $exchange['exchange_score'] !== null ? (float) $exchange['exchange_score'] : null;
        }
        return null;
    }

    /**
     * Extract liquidity score.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Liquidity score
     */
    private function extractLiquidityScore(array $exchange): ?float
    {
        if (isset($exchange['liquidity_score'])) {
            return $exchange['liquidity_score'] !== null ? (float) $exchange['liquidity_score'] : null;
        }
        return null;
    }

    /**
     * Extract traffic score.
     *
     * @param array<string, mixed> $exchange Raw exchange data
     * @return float|null Traffic score
     */
    private function extractTrafficScore(array $exchange): ?float
    {
        if (isset($exchange['traffic_score'])) {
            return $exchange['traffic_score'] !== null ? (float) $exchange['traffic_score'] : null;
        }
        return null;
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
            'num_exchanges' => $data['status']['total_count'] ?? null,
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