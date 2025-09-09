<?php

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Exchange Transformer
 * 
 * Transforms raw CoinMarketCap exchange data to standardized format
 */
class ExchangeTransformer
{
    /**
     * Transform exchange listing data
     */
    public function transformListing(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'num_market_pairs' => $data['num_market_pairs'],
            'spot_volume_usd' => $data['quote']['USD']['volume_24h'],
            'volume_change_24h' => $data['quote']['USD']['volume_24h_change_percentage'],
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
     * Transform exchange info
     */
    public function transformInfo(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'logo' => $data['logo'],
            'urls' => $data['urls'],
            'countries' => $data['countries'],
            'type' => $data['type'],
        ];
    }
}