<?php

namespace Convertain\CoinMarketCap\Transformers;

/**
 * Exchange Transformer
 *
 * Transforms raw CoinMarketCap exchange API responses into standardized,
 * clean data structures optimized for application use.
 */
class ExchangeTransformer
{
    /**
     * Transform exchange map response
     *
     * @param array $response Raw API response
     * @return array Transformed exchange map data
     */
    public function transformMap(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $transformedData = [];
        
        foreach ($response['data'] as $exchange) {
            $transformedData[] = [
                'id' => $exchange['id'] ?? null,
                'name' => $exchange['name'] ?? '',
                'slug' => $exchange['slug'] ?? '',
                'is_active' => $exchange['is_active'] ?? null,
                'is_listed' => $exchange['is_listed'] ?? null,
                'first_historical_data' => $exchange['first_historical_data'] ?? null,
                'last_historical_data' => $exchange['last_historical_data'] ?? null,
            ];
        }
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Transform exchange info response
     *
     * @param array $response Raw API response
     * @return array Transformed exchange info data
     */
    public function transformInfo(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $transformedData = [];
        
        foreach ($response['data'] as $exchangeId => $exchange) {
            $transformedData[$exchangeId] = [
                'id' => $exchange['id'] ?? null,
                'name' => $exchange['name'] ?? '',
                'slug' => $exchange['slug'] ?? '',
                'description' => $exchange['description'] ?? null,
                'logo' => $exchange['logo'] ?? null,
                'website' => $exchange['website'] ?? null,
                'date_launched' => $exchange['date_launched'] ?? null,
                'notice' => $exchange['notice'] ?? null,
                'countries' => $exchange['countries'] ?? [],
                'fiats' => $exchange['fiats'] ?? [],
                'tags' => $exchange['tags'] ?? [],
                'type' => $exchange['type'] ?? null,
                'is_hidden' => $exchange['is_hidden'] ?? null,
                'is_redistributable' => $exchange['is_redistributable'] ?? null,
                'maker_fee' => $exchange['maker_fee'] ?? null,
                'taker_fee' => $exchange['taker_fee'] ?? null,
                'weekly_visits' => $exchange['weekly_visits'] ?? null,
                'spot_volume_usd' => $exchange['spot_volume_usd'] ?? null,
                'spot_volume_last_updated' => $exchange['spot_volume_last_updated'] ?? null,
            ];
        }
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Transform exchange listings response
     *
     * @param array $response Raw API response
     * @return array Transformed exchange listings data
     */
    public function transformListings(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $transformedData = [];
        
        foreach ($response['data'] as $exchange) {
            $transformedData[] = [
                'id' => $exchange['id'] ?? null,
                'name' => $exchange['name'] ?? '',
                'slug' => $exchange['slug'] ?? '',
                'num_market_pairs' => $exchange['num_market_pairs'] ?? null,
                'exchange_score' => $exchange['exchange_score'] ?? null,
                'last_updated' => $exchange['last_updated'] ?? null,
                'fiats' => $exchange['fiats'] ?? [],
                'quote' => $this->transformQuoteData($exchange['quote'] ?? []),
            ];
        }
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Transform exchange quotes response
     *
     * @param array $response Raw API response
     * @return array Transformed exchange quotes data
     */
    public function transformQuotes(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $transformedData = [];
        
        foreach ($response['data'] as $exchangeId => $exchange) {
            $transformedData[$exchangeId] = [
                'id' => $exchange['id'] ?? null,
                'name' => $exchange['name'] ?? '',
                'slug' => $exchange['slug'] ?? '',
                'num_market_pairs' => $exchange['num_market_pairs'] ?? null,
                'last_updated' => $exchange['last_updated'] ?? null,
                'quote' => $this->transformQuoteData($exchange['quote'] ?? []),
            ];
        }
        
        return $this->createSuccessResponse($transformedData, $response);
    }
    
    /**
     * Transform market pairs response
     *
     * @param array $response Raw API response
     * @return array Transformed market pairs data
     */
    public function transformMarketPairs(array $response): array
    {
        if (!isset($response['data'])) {
            return $this->createErrorResponse($response);
        }
        
        $transformedData = [
            'id' => $response['data']['id'] ?? null,
            'name' => $response['data']['name'] ?? '',
            'slug' => $response['data']['slug'] ?? '',
            'num_market_pairs' => $response['data']['num_market_pairs'] ?? null,
            'market_pairs' => [],
        ];
        
        if (isset($response['data']['market_pairs'])) {
            foreach ($response['data']['market_pairs'] as $pair) {
                $transformedData['market_pairs'][] = [
                    'exchange_id' => $pair['exchange_id'] ?? null,
                    'exchange_name' => $pair['exchange_name'] ?? '',
                    'exchange_slug' => $pair['exchange_slug'] ?? '',
                    'market_pair' => $pair['market_pair'] ?? '',
                    'market_pair_base' => [
                        'currency_id' => $pair['market_pair_base']['currency_id'] ?? null,
                        'currency_symbol' => $pair['market_pair_base']['currency_symbol'] ?? '',
                        'currency_type' => $pair['market_pair_base']['currency_type'] ?? '',
                        'exchange_symbol' => $pair['market_pair_base']['exchange_symbol'] ?? '',
                    ],
                    'market_pair_quote' => [
                        'currency_id' => $pair['market_pair_quote']['currency_id'] ?? null,
                        'currency_symbol' => $pair['market_pair_quote']['currency_symbol'] ?? '',
                        'currency_type' => $pair['market_pair_quote']['currency_type'] ?? '',
                        'exchange_symbol' => $pair['market_pair_quote']['exchange_symbol'] ?? '',
                    ],
                    'category' => $pair['category'] ?? '',
                    'fee_type' => $pair['fee_type'] ?? '',
                    'market_url' => $pair['market_url'] ?? '',
                    'outlier_detected' => $pair['outlier_detected'] ?? null,
                    'last_updated' => $pair['last_updated'] ?? null,
                    'quote' => $this->transformQuoteData($pair['quote'] ?? []),
                ];
            }
        }
        
        return $this->createSuccessResponse($transformedData, $response);
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
                'volume_24h' => $data['volume_24h'] ?? null,
                'volume_24h_adjusted' => $data['volume_24h_adjusted'] ?? null,
                'volume_7d' => $data['volume_7d'] ?? null,
                'volume_30d' => $data['volume_30d'] ?? null,
                'percent_change_volume_24h' => $data['percent_change_volume_24h'] ?? null,
                'percent_change_volume_7d' => $data['percent_change_volume_7d'] ?? null,
                'percent_change_volume_30d' => $data['percent_change_volume_30d'] ?? null,
                'effective_liquidity_24h' => $data['effective_liquidity_24h'] ?? null,
                'derivative_volume_usd' => $data['derivative_volume_usd'] ?? null,
                'spot_volume_usd' => $data['spot_volume_usd'] ?? null,
                'last_updated' => $data['last_updated'] ?? null,
            ];
            
            // Add market pair specific quote data if available
            if (isset($data['price'])) {
                $transformedQuote[$currency]['price'] = $data['price'];
                $transformedQuote[$currency]['volume_24h_base'] = $data['volume_24h_base'] ?? null;
                $transformedQuote[$currency]['volume_24h_quote'] = $data['volume_24h_quote'] ?? null;
                $transformedQuote[$currency]['last_updated'] = $data['last_updated'] ?? null;
            }
        }
        
        return $transformedQuote;
    }
    
    /**
     * Create success response structure
     *
     * @param array $data Transformed data
     * @param array $originalResponse Original API response
     * @return array Success response structure
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
     *
     * @param array $response Original API response
     * @return array Error response structure
     */
    private function createErrorResponse(array $response): array
    {
        return [
            'status' => $response['status'] ?? ['error_code' => 400, 'error_message' => 'Invalid response'],
            'data' => [],
        ];
    }
}