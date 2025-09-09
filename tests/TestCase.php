<?php

namespace Convertain\CoinMarketCap\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Convertain\CoinMarketCap\CoinMarketCapServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        $this->app['config']->set('coinmarketcap', [
            'api' => [
                'key' => 'test-api-key',
                'base_url' => 'https://pro-api.coinmarketcap.com/v2',
                'timeout' => 30,
            ],
            'plan' => [
                'type' => 'basic',
                'credits_per_month' => 10000,
                'calls_per_minute' => 30,
            ],
            'credits' => [
                'tracking_enabled' => true,
                'warning_threshold' => 0.8,
                'optimization_enabled' => true,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'cryptocurrency_quotes_latest' => 1,
                    'exchange_listings_latest' => 1,
                ],
            ],
            'cache' => [
                'enabled' => true,
                'store' => null,
                'prefix' => 'coinmarketcap',
                'ttl' => [
                    'cryptocurrency_quotes' => 60,
                    'exchange_quotes' => 60,
                    'global_metrics' => 300,
                ],
            ],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            CoinMarketCapServiceProvider::class,
        ];
    }
}