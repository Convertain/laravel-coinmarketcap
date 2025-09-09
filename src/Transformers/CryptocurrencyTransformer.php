<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Transforms CoinMarketCap API responses into consistent format.
 */
class CryptocurrencyTransformer
{
    /**
     * Transform raw API response data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data, string $endpoint): array
    {
        return match (true) {
            str_contains($endpoint, '/cryptocurrency/map') => $this->transformMap($data),
            str_contains($endpoint, '/cryptocurrency/info') => $this->transformInfo($data),
            str_contains($endpoint, '/cryptocurrency/listings') => $this->transformListings($data),
            str_contains($endpoint, '/cryptocurrency/quotes') => $this->transformQuotes($data),
            str_contains($endpoint, '/cryptocurrency/market-pairs') => $this->transformMarketPairs($data),
            str_contains($endpoint, '/cryptocurrency/ohlcv') => $this->transformOhlcv($data),
            str_contains($endpoint, '/cryptocurrency/trending') => $this->transformTrending($data),
            str_contains($endpoint, '/cryptocurrency/category') => $this->transformCategory($data),
            str_contains($endpoint, '/cryptocurrency/airdrop') => $this->transformAirdrop($data),
            default => $data,
        };
    }

    /**
     * Transform cryptocurrency map data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformMap(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $transformed = [
            'status' => $data['status'] ?? [],
            'data' => [],
            'metadata' => [
                'total_count' => count($data['data']),
                'transformed_at' => date("c"),
            ],
        ];

        foreach ($data['data'] as $crypto) {
            $transformed['data'][] = [
                'id' => $crypto['id'] ?? null,
                'symbol' => $crypto['symbol'] ?? null,
                'name' => $crypto['name'] ?? null,
                'slug' => $crypto['slug'] ?? null,
                'rank' => $crypto['rank'] ?? null,
                'is_active' => $crypto['is_active'] ?? null,
                'first_historical_data' => $crypto['first_historical_data'] ?? null,
                'last_historical_data' => $crypto['last_historical_data'] ?? null,
                'platform' => $crypto['platform'] ?? null,
            ];
        }

        return $transformed;
    }

    /**
     * Transform cryptocurrency info data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformInfo(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $transformed = [
            'status' => $data['status'] ?? [],
            'data' => [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];

        foreach ($data['data'] as $id => $crypto) {
            $transformed['data'][$id] = [
                'id' => $crypto['id'] ?? null,
                'name' => $crypto['name'] ?? null,
                'symbol' => $crypto['symbol'] ?? null,
                'slug' => $crypto['slug'] ?? null,
                'description' => $crypto['description'] ?? null,
                'logo' => $crypto['logo'] ?? null,
                'subreddit' => $crypto['subreddit'] ?? null,
                'notice' => $crypto['notice'] ?? null,
                'tags' => $crypto['tags'] ?? [],
                'tag_names' => $crypto['tag-names'] ?? [],
                'tag_groups' => $crypto['tag-groups'] ?? [],
                'urls' => $crypto['urls'] ?? [],
                'platform' => $crypto['platform'] ?? null,
                'date_added' => $crypto['date_added'] ?? null,
                'twitter_username' => $crypto['twitter_username'] ?? null,
                'is_hidden' => $crypto['is_hidden'] ?? null,
                'date_launched' => $crypto['date_launched'] ?? null,
                'contract_address' => $crypto['contract_address'] ?? [],
                'self_reported_circulating_supply' => $crypto['self_reported_circulating_supply'] ?? null,
                'self_reported_tags' => $crypto['self_reported_tags'] ?? null,
                'self_reported_market_cap' => $crypto['self_reported_market_cap'] ?? null,
                'infinite_supply' => $crypto['infinite_supply'] ?? null,
            ];
        }

        return $transformed;
    }

    /**
     * Transform listings data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformListings(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $transformed = [
            'status' => $data['status'] ?? [],
            'data' => [],
            'metadata' => [
                'total_count' => count($data['data']),
                'transformed_at' => date("c"),
            ],
        ];

        foreach ($data['data'] as $crypto) {
            $transformed['data'][] = [
                'id' => $crypto['id'] ?? null,
                'name' => $crypto['name'] ?? null,
                'symbol' => $crypto['symbol'] ?? null,
                'slug' => $crypto['slug'] ?? null,
                'cmc_rank' => $crypto['cmc_rank'] ?? null,
                'num_market_pairs' => $crypto['num_market_pairs'] ?? null,
                'circulating_supply' => $crypto['circulating_supply'] ?? null,
                'total_supply' => $crypto['total_supply'] ?? null,
                'max_supply' => $crypto['max_supply'] ?? null,
                'infinite_supply' => $crypto['infinite_supply'] ?? null,
                'last_updated' => $crypto['last_updated'] ?? null,
                'date_added' => $crypto['date_added'] ?? null,
                'tags' => $crypto['tags'] ?? [],
                'platform' => $crypto['platform'] ?? null,
                'self_reported_circulating_supply' => $crypto['self_reported_circulating_supply'] ?? null,
                'self_reported_market_cap' => $crypto['self_reported_market_cap'] ?? null,
                'tvl_ratio' => $crypto['tvl_ratio'] ?? null,
                'quote' => $crypto['quote'] ?? [],
            ];
        }

        return $transformed;
    }

    /**
     * Transform quotes data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformQuotes(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $transformed = [
            'status' => $data['status'] ?? [],
            'data' => [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];

        foreach ($data['data'] as $id => $crypto) {
            $transformed['data'][$id] = [
                'id' => $crypto['id'] ?? null,
                'name' => $crypto['name'] ?? null,
                'symbol' => $crypto['symbol'] ?? null,
                'slug' => $crypto['slug'] ?? null,
                'is_active' => $crypto['is_active'] ?? null,
                'is_fiat' => $crypto['is_fiat'] ?? null,
                'circulating_supply' => $crypto['circulating_supply'] ?? null,
                'total_supply' => $crypto['total_supply'] ?? null,
                'max_supply' => $crypto['max_supply'] ?? null,
                'date_added' => $crypto['date_added'] ?? null,
                'num_market_pairs' => $crypto['num_market_pairs'] ?? null,
                'cmc_rank' => $crypto['cmc_rank'] ?? null,
                'last_updated' => $crypto['last_updated'] ?? null,
                'tags' => $crypto['tags'] ?? [],
                'platform' => $crypto['platform'] ?? null,
                'self_reported_circulating_supply' => $crypto['self_reported_circulating_supply'] ?? null,
                'self_reported_market_cap' => $crypto['self_reported_market_cap'] ?? null,
                'tvl_ratio' => $crypto['tvl_ratio'] ?? null,
                'quote' => $crypto['quote'] ?? [],
            ];
        }

        return $transformed;
    }

    /**
     * Transform market pairs data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformMarketPairs(array $data): array
    {
        return [
            'status' => $data['status'] ?? [],
            'data' => $data['data'] ?? [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];
    }

    /**
     * Transform OHLCV data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformOhlcv(array $data): array
    {
        return [
            'status' => $data['status'] ?? [],
            'data' => $data['data'] ?? [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];
    }

    /**
     * Transform trending data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformTrending(array $data): array
    {
        return [
            'status' => $data['status'] ?? [],
            'data' => $data['data'] ?? [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];
    }

    /**
     * Transform category data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformCategory(array $data): array
    {
        return [
            'status' => $data['status'] ?? [],
            'data' => $data['data'] ?? [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];
    }

    /**
     * Transform airdrop data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function transformAirdrop(array $data): array
    {
        return [
            'status' => $data['status'] ?? [],
            'data' => $data['data'] ?? [],
            'metadata' => [
                'transformed_at' => date("c"),
            ],
        ];
    }
}