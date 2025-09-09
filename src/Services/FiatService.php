<?php

namespace Convertain\CoinMarketCap\Services;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Fiat Service
 *
 * Handles fiat currency mapping and reference data from CoinMarketCap Pro API v2.
 * Provides comprehensive fiat currency support for price conversions.
 */
class FiatService
{
    private CoinMarketCapClient $client;
    
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get fiat currency mapping
     *
     * Returns a mapping of supported fiat currencies with their IDs, names,
     * symbols, and other metadata for use in price conversion endpoints.
     *
     * @param int $start Results start offset (default: 1)
     * @param int $limit Number of results to return (default: 100, max: 5000)
     * @param array $includeMetals Include precious metals (true/false)
     * @return array Transformed fiat currency map data
     */
    public function map(int $start = 1, int $limit = 100, bool $includeMetals = false): array
    {
        $parameters = [
            'start' => $start,
            'limit' => $limit,
            'include_metals' => $includeMetals ? 'true' : 'false',
        ];
        
        $response = $this->client->get('/fiat/map', $parameters, 86400); // Cache for 24 hours
        
        return $this->transformFiatMap($response);
    }
    
    /**
     * Get all supported fiat currencies
     *
     * Returns a complete list of all supported fiat currencies without pagination.
     *
     * @param bool $includeMetals Include precious metals
     * @return array Complete fiat currency list
     */
    public function getAllCurrencies(bool $includeMetals = false): array
    {
        return $this->map(1, 5000, $includeMetals);
    }
    
    /**
     * Get fiat currency by symbol
     *
     * Returns fiat currency data for a specific currency symbol.
     *
     * @param string $symbol Currency symbol (e.g., 'USD', 'EUR')
     * @param bool $includeMetals Include precious metals
     * @return array|null Fiat currency data or null if not found
     */
    public function getCurrencyBySymbol(string $symbol, bool $includeMetals = false): ?array
    {
        $currencies = $this->getAllCurrencies($includeMetals);
        
        foreach ($currencies['data'] as $currency) {
            if (strtoupper($currency['symbol']) === strtoupper($symbol)) {
                return $currency;
            }
        }
        
        return null;
    }
    
    /**
     * Get fiat currency by ID
     *
     * Returns fiat currency data for a specific CoinMarketCap fiat ID.
     *
     * @param int $id CoinMarketCap fiat currency ID
     * @param bool $includeMetals Include precious metals
     * @return array|null Fiat currency data or null if not found
     */
    public function getCurrencyById(int $id, bool $includeMetals = false): ?array
    {
        $currencies = $this->getAllCurrencies($includeMetals);
        
        foreach ($currencies['data'] as $currency) {
            if ($currency['id'] === $id) {
                return $currency;
            }
        }
        
        return null;
    }
    
    /**
     * Check if currency is supported
     *
     * Verifies if a given currency symbol is supported for conversions.
     *
     * @param string $symbol Currency symbol to check
     * @param bool $includeMetals Include precious metals in check
     * @return bool True if supported, false otherwise
     */
    public function isCurrencySupported(string $symbol, bool $includeMetals = false): bool
    {
        return $this->getCurrencyBySymbol($symbol, $includeMetals) !== null;
    }
    
    /**
     * Get major fiat currencies
     *
     * Returns a curated list of major world currencies commonly used
     * in cryptocurrency trading and conversions.
     *
     * @return array Major fiat currencies data
     */
    public function getMajorCurrencies(): array
    {
        $majorSymbols = ['USD', 'EUR', 'JPY', 'GBP', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NZD'];
        $allCurrencies = $this->getAllCurrencies();
        $majorCurrencies = [];
        
        foreach ($allCurrencies['data'] as $currency) {
            if (in_array($currency['symbol'], $majorSymbols)) {
                $majorCurrencies[] = $currency;
            }
        }
        
        return [
            'status' => $allCurrencies['status'],
            'data' => $majorCurrencies,
        ];
    }
    
    /**
     * Get regional currencies
     *
     * Returns fiat currencies grouped by region for easier regional analysis.
     *
     * @return array Regional currency groupings
     */
    public function getRegionalCurrencies(): array
    {
        $regions = [
            'North America' => ['USD', 'CAD', 'MXN'],
            'Europe' => ['EUR', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF'],
            'Asia Pacific' => ['JPY', 'CNY', 'KRW', 'AUD', 'NZD', 'SGD', 'HKD', 'INR', 'THB', 'IDR', 'MYR'],
            'Middle East & Africa' => ['AED', 'SAR', 'ZAR', 'EGP', 'NGN'],
            'South America' => ['BRL', 'ARS', 'CLP', 'COP', 'PEN'],
        ];
        
        $allCurrencies = $this->getAllCurrencies();
        $regionalData = [];
        
        foreach ($regions as $region => $symbols) {
            $regionalData[$region] = [];
            
            foreach ($allCurrencies['data'] as $currency) {
                if (in_array($currency['symbol'], $symbols)) {
                    $regionalData[$region][] = $currency;
                }
            }
        }
        
        return [
            'status' => $allCurrencies['status'],
            'data' => $regionalData,
        ];
    }
    
    /**
     * Get precious metals
     *
     * Returns precious metals that can be used as reference currencies.
     *
     * @return array Precious metals data
     */
    public function getPreciousMetals(): array
    {
        $currencies = $this->getAllCurrencies(true);
        $metals = [];
        
        $metalSymbols = ['XAU', 'XAG', 'XPT', 'XPD']; // Gold, Silver, Platinum, Palladium
        
        foreach ($currencies['data'] as $currency) {
            if (in_array($currency['symbol'], $metalSymbols)) {
                $metals[] = $currency;
            }
        }
        
        return [
            'status' => $currencies['status'],
            'data' => $metals,
        ];
    }
    
    /**
     * Transform fiat map response data
     *
     * @param array $response Raw API response
     * @return array Transformed fiat map data
     */
    private function transformFiatMap(array $response): array
    {
        if (!isset($response['data'])) {
            return [
                'status' => $response['status'] ?? ['error_code' => 400, 'error_message' => 'Invalid response'],
                'data' => [],
            ];
        }
        
        $transformedData = [];
        
        foreach ($response['data'] as $currency) {
            $transformedData[] = [
                'id' => $currency['id'] ?? null,
                'name' => $currency['name'] ?? '',
                'sign' => $currency['sign'] ?? '',
                'symbol' => $currency['symbol'] ?? '',
            ];
        }
        
        return [
            'status' => $response['status'],
            'data' => $transformedData,
        ];
    }
}