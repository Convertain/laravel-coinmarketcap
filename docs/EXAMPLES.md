# Usage Examples

Comprehensive examples demonstrating real-world usage of the Laravel CoinMarketCap package with focus on credit optimization and best practices.

## Table of Contents

- [Basic Examples](#basic-examples)
- [Portfolio Management](#portfolio-management)
- [Trading Applications](#trading-applications)
- [Market Analysis](#market-analysis)
- [Credit Optimization Examples](#credit-optimization-examples)
- [Caching Strategies](#caching-strategies)
- [Error Handling](#error-handling)
- [Performance Optimization](#performance-optimization)
- [Real-World Applications](#real-world-applications)

## Basic Examples

### Simple Price Fetching

```php
<?php

use Convertain\CoinMarketCap\CoinMarketCapProvider;

class BasicPriceService
{
    protected $provider;
    
    public function __construct(CoinMarketCapProvider $provider)
    {
        $this->provider = $provider;
    }
    
    /**
     * Get current Bitcoin price
     * Credit Cost: 1 credit
     */
    public function getBitcoinPrice()
    {
        $bitcoin = $this->provider->getCryptocurrency('BTC', [
            'convert' => 'USD'
        ]);
        
        return [
            'symbol' => $bitcoin->getSymbol(),
            'name' => $bitcoin->getName(),
            'price_usd' => $bitcoin->getCurrentPrice('USD'),
            'market_cap' => $bitcoin->getMarketCap('USD'),
            'volume_24h' => $bitcoin->getVolume24h('USD'),
            'last_updated' => $bitcoin->getLastUpdated(),
        ];
    }
    
    /**
     * Get multiple cryptocurrency prices
     * Credit Cost: 1 credit (for up to 100 symbols)
     */
    public function getMultiplePrices(array $symbols)
    {
        $cryptos = $this->provider->getCryptocurrencies($symbols, [
            'convert' => 'USD'
        ]);
        
        $prices = [];
        foreach ($cryptos as $crypto) {
            $prices[$crypto->getSymbol()] = [
                'price' => $crypto->getCurrentPrice('USD'),
                'change_24h' => $crypto->getPercentChange24h(),
                'market_cap' => $crypto->getMarketCap('USD'),
            ];
        }
        
        return $prices;
    }
    
    /**
     * Get price in multiple currencies
     * Credit Cost: 1 credit (same cost regardless of conversion currencies)
     */
    public function getPriceInMultipleCurrencies($symbol)
    {
        $crypto = $this->provider->getCryptocurrency($symbol, [
            'convert' => 'USD,EUR,JPY,GBP,BTC'
        ]);
        
        return [
            'symbol' => $symbol,
            'prices' => [
                'USD' => $crypto->getCurrentPrice('USD'),
                'EUR' => $crypto->getCurrentPrice('EUR'),
                'JPY' => $crypto->getCurrentPrice('JPY'),
                'GBP' => $crypto->getCurrentPrice('GBP'),
                'BTC' => $crypto->getCurrentPrice('BTC'),
            ]
        ];
    }
}
```

For more examples, see the complete [Examples Guide](https://github.com/Convertain/laravel-coinmarketcap/blob/main/docs/EXAMPLES.md).