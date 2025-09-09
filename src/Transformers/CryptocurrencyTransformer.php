<?php

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Cryptocurrency Transformer
 * 
 * Transforms raw CoinMarketCap cryptocurrency data to standardized format
 */
class CryptocurrencyTransformer
{
    /**
     * Transform cryptocurrency listing data
     */
    public function transformListing(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'slug' => $data['slug'],
            'cmc_rank' => $data['cmc_rank'],
            'market_cap' => $data['quote']['USD']['market_cap'],
            'price' => $data['quote']['USD']['price'],
            'volume_24h' => $data['quote']['USD']['volume_24h'],
            'percent_change_1h' => $data['quote']['USD']['percent_change_1h'],
            'percent_change_24h' => $data['quote']['USD']['percent_change_24h'],
            'percent_change_7d' => $data['quote']['USD']['percent_change_7d'],
            'last_updated' => $data['last_updated'],
        ];
    }

    /**
     * Transform multiple listings
     */
    public function transformListings(array $listings): array
    {
        return array_map([$this, 'transformListing'], $listings);
    }

    /**
     * Transform cryptocurrency info
     */
    public function transformInfo(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'category' => $data['category'],
            'description' => $data['description'],
            'logo' => $data['logo'],
            'urls' => $data['urls'],
            'tags' => $data['tags'],
        ];
    }
}