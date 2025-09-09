<?php

namespace Convertain\CoinMarketCap;

use Illuminate\Support\ServiceProvider;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\CoinMarketCapProvider;
use Convertain\CoinMarketCap\Credit;
use Convertain\CoinMarketCap\Transformers;

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

        // Register credit management components
        $this->registerCreditManagement();

        // Register transformers
        $this->registerTransformers();

        // Register the provider
        $this->app->singleton(CoinMarketCapProvider::class, function ($app) {
            $provider = new CoinMarketCapProvider(
                $app[CoinMarketCapClient::class]
            );

            // Inject credit management if enabled
            if ($app['config']->get('coinmarketcap.credits.tracking_enabled', true)) {
                $provider->setCreditManager($app[Credit\CreditManager::class]);
                $provider->setCreditOptimizer($app[Credit\CreditOptimizer::class]);
            }

            return $provider;
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
     * Register credit management components.
     *
     * @return void
     */
    private function registerCreditManagement(): void
    {
        // Register plan manager
        $this->app->singleton(Credit\PlanManager::class, function ($app) {
            return new Credit\PlanManager(
                $app['config']->get('coinmarketcap')
            );
        });

        // Register credit manager
        $this->app->singleton(Credit\CreditManager::class, function ($app) {
            return new Credit\CreditManager(
                $app['cache'],
                $app['events'],
                $app[Credit\PlanManager::class],
                $app['config']->get('coinmarketcap')
            );
        });

        // Register credit optimizer
        $this->app->singleton(Credit\CreditOptimizer::class, function ($app) {
            return new Credit\CreditOptimizer(
                $app['cache'],
                $app[Credit\PlanManager::class],
                $app[Credit\CreditManager::class],
                $app['config']->get('coinmarketcap')
            );
        });
    }

    /**
     * Register transformer components.
     *
     * @return void
     */
    private function registerTransformers(): void
    {
        $this->app->singleton(Transformers\CryptocurrencyTransformer::class);
        $this->app->singleton(Transformers\ExchangeTransformer::class);
        $this->app->singleton(Transformers\GlobalMetricsTransformer::class);
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
            Credit\CreditManager::class,
            Credit\CreditOptimizer::class,
            Credit\PlanManager::class,
            Transformers\CryptocurrencyTransformer::class,
            Transformers\ExchangeTransformer::class,
            Transformers\GlobalMetricsTransformer::class,
        ];
    }
}