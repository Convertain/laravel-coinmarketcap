<?php

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Global Metrics Transformer
 * 
 * Transforms raw CoinMarketCap global metrics data to standardized format
 */
class GlobalMetricsTransformer
{
    /**
     * Transform global metrics data
     */
    public function transform(array $data): array
    {
        return [
            'btc_dominance' => $data['btc_dominance'],
            'eth_dominance' => $data['eth_dominance'],
            'total_market_cap' => $data['quote']['USD']['total_market_cap'],
            'total_volume_24h' => $data['quote']['USD']['total_volume_24h'],
            'total_volume_24h_change' => $data['quote']['USD']['total_volume_24h_change_percentage'],
            'last_updated' => $data['last_updated'],
        ];
    }
}