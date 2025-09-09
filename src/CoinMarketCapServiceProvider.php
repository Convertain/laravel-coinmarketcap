<?php

namespace Convertain\CoinMarketCap;

use Illuminate\Support\ServiceProvider;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\CoinMarketCapProvider;
use Convertain\CoinMarketCap\Monitoring\EventMonitor;
use Convertain\CoinMarketCap\Events\ApiCallMade;
use Convertain\CoinMarketCap\Events\ApiError;
use Convertain\CoinMarketCap\Events\CreditWarning;
use Convertain\CoinMarketCap\Listeners\LogApiCall;
use Convertain\CoinMarketCap\Listeners\LogApiError;
use Convertain\CoinMarketCap\Listeners\LogCreditWarning;

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

        // Register the event monitor
        $this->app->singleton(EventMonitor::class, function ($app) {
            return new EventMonitor(
                $app['events'],
                $app['config']->get('coinmarketcap.events', [])
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

        // Register event listeners if events are enabled
        if (config('coinmarketcap.events.enabled', true)) {
            $this->registerEventListeners();
        }
    }

    /**
     * Register event listeners for CoinMarketCap events.
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];
        $eventsConfig = config('coinmarketcap.events.dispatch', []);

        // Register listeners based on configuration
        if ($eventsConfig['api_call_made'] ?? true) {
            $events->listen(ApiCallMade::class, LogApiCall::class);
        }

        if ($eventsConfig['api_error'] ?? true) {
            $events->listen(ApiError::class, LogApiError::class);
        }

        if ($eventsConfig['credit_warning'] ?? true) {
            $events->listen(CreditWarning::class, LogCreditWarning::class);
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
            EventMonitor::class,
        ];
    }
}