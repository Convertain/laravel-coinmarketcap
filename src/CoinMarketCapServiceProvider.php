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

        // Register cache services
        $this->registerCacheServices();

        // Register the API client (placeholder for future implementation)
        $this->app->singleton(CoinMarketCapClient::class, function ($app) {
            return new CoinMarketCapClient(
                $app['config']->get('coinmarketcap')
            );
        });

        // Register the provider (placeholder for future implementation)
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
     * Register cache-related services.
     */
    private function registerCacheServices(): void
    {
        // Register cache analytics
        $this->app->singleton(\Convertain\CoinMarketCap\Cache\CacheAnalytics::class, function ($app) {
            return new \Convertain\CoinMarketCap\Cache\CacheAnalytics();
        });

        // Register cache strategy
        $this->app->singleton(\Convertain\CoinMarketCap\Cache\CacheStrategy::class, function ($app) {
            return new \Convertain\CoinMarketCap\Cache\CacheStrategy(
                $app[\Convertain\CoinMarketCap\Cache\CacheAnalytics::class]
            );
        });

        // Register main cache service
        $this->app->singleton(\Convertain\CoinMarketCap\Cache\CoinMarketCapCache::class, function ($app) {
            return new \Convertain\CoinMarketCap\Cache\CoinMarketCapCache(
                null, // Will use default cache store
                $app[\Convertain\CoinMarketCap\Cache\CacheAnalytics::class],
                $app[\Psr\Log\LoggerInterface::class]
            );
        });

        // Register cache warmer
        $this->app->singleton(\Convertain\CoinMarketCap\Cache\CacheWarmer::class, function ($app) {
            return new \Convertain\CoinMarketCap\Cache\CacheWarmer(
                $app[\Convertain\CoinMarketCap\Cache\CoinMarketCapCache::class],
                $app[\Convertain\CoinMarketCap\Cache\CacheStrategy::class],
                $app[\Convertain\CoinMarketCap\Cache\CacheAnalytics::class],
                $app[\Psr\Log\LoggerInterface::class]
            );
        });
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
            \Convertain\CoinMarketCap\Cache\CacheAnalytics::class,
            \Convertain\CoinMarketCap\Cache\CacheStrategy::class,
            \Convertain\CoinMarketCap\Cache\CoinMarketCapCache::class,
            \Convertain\CoinMarketCap\Cache\CacheWarmer::class,
        ];
    }
}