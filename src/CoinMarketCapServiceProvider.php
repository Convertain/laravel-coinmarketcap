<?php

namespace Convertain\CoinMarketCap;

use Illuminate\Support\ServiceProvider;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\CoinMarketCapProvider;

class CoinMarketCapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/coinmarketcap.php',
            'coinmarketcap'
        );

        // Register the API client
        $this->app->singleton(CoinMarketCapClient::class, function ($app) {
            return new CoinMarketCapClient(
                $app['config']->get('coinmarketcap')
            );
        });

        // Register the provider
        $this->app->singleton(CoinMarketCapProvider::class, function ($app) {
            return new CoinMarketCapProvider(
                $app[CoinMarketCapClient::class]
            );
        });

        // Register provider in cryptocurrency package if available
        if ($this->app->bound('crypto.providers')) {
            $this->app->extend('crypto.providers', function ($providers, $app) {
                $providers->add($app[CoinMarketCapProvider::class]);
                return $providers;
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/coinmarketcap.php' => config_path('coinmarketcap.php'),
            ], 'coinmarketcap-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CoinMarketCapClient::class,
            CoinMarketCapProvider::class,
        ];
    }
}