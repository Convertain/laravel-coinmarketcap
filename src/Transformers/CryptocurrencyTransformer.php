<?php

namespace Convertain\CoinMarketCap\Transformers;

use Convertain\CoinMarketCap\Contracts\TransformerInterface;
use Carbon\Carbon;

/**
 * Transforms CoinMarketCap cryptocurrency API responses into standardized format.
 */
class CryptocurrencyTransformer implements TransformerInterface
{
    /**
     * Transform raw cryptocurrency data into standardized format.
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
        return $this->transformCryptocurrency($data);
    }

    /**
     * Transform a collection of cryptocurrency items.
     *
     * @param array<int, array<string, mixed>> $items Array of raw cryptocurrency items
     * @return array<int, array<string, mixed>> Array of transformed cryptocurrency items
     */
    public function transformCollection(array $items): array
    {
        return array_map([$this, 'transformCryptocurrency'], $items);
    }

    /**
     * Validate if the raw data structure is compatible with cryptocurrency transformer.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return bool True if data is compatible
     */
    public function canTransform(array $data): bool
    {
        // Check for cryptocurrency-specific fields
        if (isset($data['data'])) {
            $testData = is_array($data['data']) && isset($data['data'][0]) 
                ? $data['data'][0] 
                : $data['data'];
        } else {
            $testData = $data;
        }

        return is_array($testData) && (
            isset($testData['id']) || 
            isset($testData['symbol']) || 
            isset($testData['name']) ||
            isset($testData['quote'])
        );
    }

    /**
     * Transform a single cryptocurrency item.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string, mixed> Transformed cryptocurrency data
     */
    private function transformCryptocurrency(array $crypto): array
    {
        $transformed = [
            'id' => $this->extractId($crypto),
            'symbol' => $this->extractSymbol($crypto),
            'name' => $this->extractName($crypto),
            'slug' => $this->extractSlug($crypto),
            'description' => $this->extractDescription($crypto),
            'logo' => $this->extractLogo($crypto),
            'website' => $this->extractWebsite($crypto),
            'technical_doc' => $this->extractTechnicalDoc($crypto),
            'twitter' => $this->extractTwitter($crypto),
            'reddit' => $this->extractReddit($crypto),
            'message_board' => $this->extractMessageBoard($crypto),
            'announcement' => $this->extractAnnouncement($crypto),
            'chat' => $this->extractChat($crypto),
            'explorer' => $this->extractExplorer($crypto),
            'source_code' => $this->extractSourceCode($crypto),
            'tags' => $this->extractTags($crypto),
            'platform' => $this->extractPlatform($crypto),
            'date_added' => $this->extractDateAdded($crypto),
            'date_launched' => $this->extractDateLaunched($crypto),
            'contract_address' => $this->extractContractAddress($crypto),
            'subreddit' => $this->extractSubreddit($crypto),
            'notice' => $this->extractNotice($crypto),
            'category' => $this->extractCategory($crypto),
            'self_reported_circulating_supply' => $this->extractSelfReportedCirculatingSupply($crypto),
            'self_reported_tags' => $this->extractSelfReportedTags($crypto),
            'self_reported_market_cap' => $this->extractSelfReportedMarketCap($crypto),
            'infinite_supply' => $this->extractInfiniteSupply($crypto),
            'cmc_rank' => $this->extractCmcRank($crypto),
            'num_market_pairs' => $this->extractNumMarketPairs($crypto),
            'circulating_supply' => $this->extractCirculatingSupply($crypto),
            'total_supply' => $this->extractTotalSupply($crypto),
            'max_supply' => $this->extractMaxSupply($crypto),
            'market_cap_by_total_supply' => $this->extractMarketCapByTotalSupply($crypto),
            'last_updated' => $this->extractLastUpdated($crypto),
            'quotes' => $this->extractQuotes($crypto),
            'is_active' => $this->extractIsActive($crypto),
            'is_fiat' => $this->extractIsFiat($crypto),
        ];

        // Remove null values to keep response clean
        return array_filter($transformed, fn($value) => $value !== null);
    }

    /**
     * Extract and normalize cryptocurrency ID.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return int|null Normalized cryptocurrency ID
     */
    private function extractId(array $crypto): ?int
    {
        return isset($crypto['id']) ? (int) $crypto['id'] : null;
    }

    /**
     * Extract and normalize cryptocurrency symbol.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized cryptocurrency symbol
     */
    private function extractSymbol(array $crypto): ?string
    {
        return isset($crypto['symbol']) ? strtoupper(trim((string) $crypto['symbol'])) : null;
    }

    /**
     * Extract and normalize cryptocurrency name.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized cryptocurrency name
     */
    private function extractName(array $crypto): ?string
    {
        return isset($crypto['name']) ? trim((string) $crypto['name']) : null;
    }

    /**
     * Extract and normalize cryptocurrency slug.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized cryptocurrency slug
     */
    private function extractSlug(array $crypto): ?string
    {
        return isset($crypto['slug']) ? trim((string) $crypto['slug']) : null;
    }

    /**
     * Extract cryptocurrency description.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Cryptocurrency description
     */
    private function extractDescription(array $crypto): ?string
    {
        return isset($crypto['description']) ? trim((string) $crypto['description']) : null;
    }

    /**
     * Extract cryptocurrency logo URL.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Logo URL
     */
    private function extractLogo(array $crypto): ?string
    {
        return isset($crypto['logo']) ? (string) $crypto['logo'] : null;
    }

    /**
     * Extract website URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Website URLs
     */
    private function extractWebsite(array $crypto): ?array
    {
        if (isset($crypto['urls']['website']) && is_array($crypto['urls']['website'])) {
            return array_filter($crypto['urls']['website']);
        }
        return null;
    }

    /**
     * Extract technical documentation URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Technical documentation URLs
     */
    private function extractTechnicalDoc(array $crypto): ?array
    {
        if (isset($crypto['urls']['technical_doc']) && is_array($crypto['urls']['technical_doc'])) {
            return array_filter($crypto['urls']['technical_doc']);
        }
        return null;
    }

    /**
     * Extract Twitter URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Twitter URLs
     */
    private function extractTwitter(array $crypto): ?array
    {
        if (isset($crypto['urls']['twitter']) && is_array($crypto['urls']['twitter'])) {
            return array_filter($crypto['urls']['twitter']);
        }
        return null;
    }

    /**
     * Extract Reddit URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Reddit URLs
     */
    private function extractReddit(array $crypto): ?array
    {
        if (isset($crypto['urls']['reddit']) && is_array($crypto['urls']['reddit'])) {
            return array_filter($crypto['urls']['reddit']);
        }
        return null;
    }

    /**
     * Extract message board URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Message board URLs
     */
    private function extractMessageBoard(array $crypto): ?array
    {
        if (isset($crypto['urls']['message_board']) && is_array($crypto['urls']['message_board'])) {
            return array_filter($crypto['urls']['message_board']);
        }
        return null;
    }

    /**
     * Extract announcement URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Announcement URLs
     */
    private function extractAnnouncement(array $crypto): ?array
    {
        if (isset($crypto['urls']['announcement']) && is_array($crypto['urls']['announcement'])) {
            return array_filter($crypto['urls']['announcement']);
        }
        return null;
    }

    /**
     * Extract chat URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Chat URLs
     */
    private function extractChat(array $crypto): ?array
    {
        if (isset($crypto['urls']['chat']) && is_array($crypto['urls']['chat'])) {
            return array_filter($crypto['urls']['chat']);
        }
        return null;
    }

    /**
     * Extract explorer URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Explorer URLs
     */
    private function extractExplorer(array $crypto): ?array
    {
        if (isset($crypto['urls']['explorer']) && is_array($crypto['urls']['explorer'])) {
            return array_filter($crypto['urls']['explorer']);
        }
        return null;
    }

    /**
     * Extract source code URLs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Source code URLs
     */
    private function extractSourceCode(array $crypto): ?array
    {
        if (isset($crypto['urls']['source_code']) && is_array($crypto['urls']['source_code'])) {
            return array_filter($crypto['urls']['source_code']);
        }
        return null;
    }

    /**
     * Extract and normalize tags.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Normalized tags
     */
    private function extractTags(array $crypto): ?array
    {
        if (isset($crypto['tags']) && is_array($crypto['tags'])) {
            return array_filter($crypto['tags']);
        }
        return null;
    }

    /**
     * Extract platform information.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string, mixed>|null Platform information
     */
    private function extractPlatform(array $crypto): ?array
    {
        if (isset($crypto['platform']) && is_array($crypto['platform'])) {
            return [
                'id' => $crypto['platform']['id'] ?? null,
                'name' => $crypto['platform']['name'] ?? null,
                'symbol' => $crypto['platform']['symbol'] ?? null,
                'slug' => $crypto['platform']['slug'] ?? null,
                'token_address' => $crypto['platform']['token_address'] ?? null,
            ];
        }
        return null;
    }

    /**
     * Extract and normalize date added.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized date added (ISO 8601)
     */
    private function extractDateAdded(array $crypto): ?string
    {
        if (isset($crypto['date_added'])) {
            return $this->normalizeDateTime($crypto['date_added']);
        }
        return null;
    }

    /**
     * Extract and normalize date launched.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized date launched (ISO 8601)
     */
    private function extractDateLaunched(array $crypto): ?string
    {
        if (isset($crypto['date_launched'])) {
            return $this->normalizeDateTime($crypto['date_launched']);
        }
        return null;
    }

    /**
     * Extract contract address.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Contract addresses
     */
    private function extractContractAddress(array $crypto): ?array
    {
        if (isset($crypto['contract_address']) && is_array($crypto['contract_address'])) {
            return array_filter($crypto['contract_address']);
        }
        return null;
    }

    /**
     * Extract subreddit.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Subreddit
     */
    private function extractSubreddit(array $crypto): ?string
    {
        return isset($crypto['subreddit']) ? trim((string) $crypto['subreddit']) : null;
    }

    /**
     * Extract notice.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Notice
     */
    private function extractNotice(array $crypto): ?string
    {
        return isset($crypto['notice']) ? trim((string) $crypto['notice']) : null;
    }

    /**
     * Extract category.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Category
     */
    private function extractCategory(array $crypto): ?string
    {
        return isset($crypto['category']) ? trim((string) $crypto['category']) : null;
    }

    /**
     * Extract self reported circulating supply.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Self reported circulating supply
     */
    private function extractSelfReportedCirculatingSupply(array $crypto): ?float
    {
        if (isset($crypto['self_reported_circulating_supply'])) {
            $supply = $crypto['self_reported_circulating_supply'];
            return $supply !== null ? (float) $supply : null;
        }
        return null;
    }

    /**
     * Extract self reported tags.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string>|null Self reported tags
     */
    private function extractSelfReportedTags(array $crypto): ?array
    {
        if (isset($crypto['self_reported_tags']) && is_array($crypto['self_reported_tags'])) {
            return array_filter($crypto['self_reported_tags']);
        }
        return null;
    }

    /**
     * Extract self reported market cap.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Self reported market cap
     */
    private function extractSelfReportedMarketCap(array $crypto): ?float
    {
        if (isset($crypto['self_reported_market_cap'])) {
            $cap = $crypto['self_reported_market_cap'];
            return $cap !== null ? (float) $cap : null;
        }
        return null;
    }

    /**
     * Extract infinite supply flag.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return bool|null Infinite supply flag
     */
    private function extractInfiniteSupply(array $crypto): ?bool
    {
        if (isset($crypto['infinite_supply'])) {
            return (bool) $crypto['infinite_supply'];
        }
        return null;
    }

    /**
     * Extract CMC rank.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return int|null CMC rank
     */
    private function extractCmcRank(array $crypto): ?int
    {
        if (isset($crypto['cmc_rank'])) {
            return $crypto['cmc_rank'] !== null ? (int) $crypto['cmc_rank'] : null;
        }
        return null;
    }

    /**
     * Extract number of market pairs.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return int|null Number of market pairs
     */
    private function extractNumMarketPairs(array $crypto): ?int
    {
        if (isset($crypto['num_market_pairs'])) {
            return $crypto['num_market_pairs'] !== null ? (int) $crypto['num_market_pairs'] : null;
        }
        return null;
    }

    /**
     * Extract circulating supply.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Circulating supply
     */
    private function extractCirculatingSupply(array $crypto): ?float
    {
        if (isset($crypto['circulating_supply'])) {
            $supply = $crypto['circulating_supply'];
            return $supply !== null ? (float) $supply : null;
        }
        return null;
    }

    /**
     * Extract total supply.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Total supply
     */
    private function extractTotalSupply(array $crypto): ?float
    {
        if (isset($crypto['total_supply'])) {
            $supply = $crypto['total_supply'];
            return $supply !== null ? (float) $supply : null;
        }
        return null;
    }

    /**
     * Extract max supply.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Max supply
     */
    private function extractMaxSupply(array $crypto): ?float
    {
        if (isset($crypto['max_supply'])) {
            $supply = $crypto['max_supply'];
            return $supply !== null ? (float) $supply : null;
        }
        return null;
    }

    /**
     * Extract market cap by total supply.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return float|null Market cap by total supply
     */
    private function extractMarketCapByTotalSupply(array $crypto): ?float
    {
        if (isset($crypto['market_cap_by_total_supply'])) {
            $cap = $crypto['market_cap_by_total_supply'];
            return $cap !== null ? (float) $cap : null;
        }
        return null;
    }

    /**
     * Extract and normalize last updated timestamp.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return string|null Normalized last updated (ISO 8601)
     */
    private function extractLastUpdated(array $crypto): ?string
    {
        if (isset($crypto['last_updated'])) {
            return $this->normalizeDateTime($crypto['last_updated']);
        }
        return null;
    }

    /**
     * Extract and normalize quotes data.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return array<string, array<string, mixed>>|null Normalized quotes
     */
    private function extractQuotes(array $crypto): ?array
    {
        if (!isset($crypto['quote']) || !is_array($crypto['quote'])) {
            return null;
        }

        $quotes = [];
        foreach ($crypto['quote'] as $currency => $quote) {
            if (!is_array($quote)) {
                continue;
            }

            $quotes[strtoupper($currency)] = [
                'price' => isset($quote['price']) ? (float) $quote['price'] : null,
                'volume_24h' => isset($quote['volume_24h']) ? (float) $quote['volume_24h'] : null,
                'volume_change_24h' => isset($quote['volume_change_24h']) ? (float) $quote['volume_change_24h'] : null,
                'percent_change_1h' => isset($quote['percent_change_1h']) ? (float) $quote['percent_change_1h'] : null,
                'percent_change_24h' => isset($quote['percent_change_24h']) ? (float) $quote['percent_change_24h'] : null,
                'percent_change_7d' => isset($quote['percent_change_7d']) ? (float) $quote['percent_change_7d'] : null,
                'percent_change_30d' => isset($quote['percent_change_30d']) ? (float) $quote['percent_change_30d'] : null,
                'percent_change_60d' => isset($quote['percent_change_60d']) ? (float) $quote['percent_change_60d'] : null,
                'percent_change_90d' => isset($quote['percent_change_90d']) ? (float) $quote['percent_change_90d'] : null,
                'market_cap' => isset($quote['market_cap']) ? (float) $quote['market_cap'] : null,
                'market_cap_dominance' => isset($quote['market_cap_dominance']) ? (float) $quote['market_cap_dominance'] : null,
                'fully_diluted_market_cap' => isset($quote['fully_diluted_market_cap']) ? (float) $quote['fully_diluted_market_cap'] : null,
                'last_updated' => isset($quote['last_updated']) ? $this->normalizeDateTime($quote['last_updated']) : null,
            ];
        }

        return empty($quotes) ? null : $quotes;
    }

    /**
     * Extract is active flag.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return int|null Is active flag (1 or 0)
     */
    private function extractIsActive(array $crypto): ?int
    {
        if (isset($crypto['is_active'])) {
            return (int) $crypto['is_active'];
        }
        return null;
    }

    /**
     * Extract is fiat flag.
     *
     * @param array<string, mixed> $crypto Raw cryptocurrency data
     * @return int|null Is fiat flag (1 or 0)
     */
    private function extractIsFiat(array $crypto): ?int
    {
        if (isset($crypto['is_fiat'])) {
            return (int) $crypto['is_fiat'];
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
            'num_cryptocurrencies' => $data['status']['total_count'] ?? null,
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