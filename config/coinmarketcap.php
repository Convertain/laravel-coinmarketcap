<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CoinMarketCap Pro API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CoinMarketCap Professional API integration. This package
    | is designed for Pro API plans with comprehensive credit management and
    | optimization features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure the CoinMarketCap Pro API connection settings including API key,
    | base URL, and request parameters.
    |
    */
    'api' => [
        'key' => env('COINMARKETCAP_API_KEY'),
        'base_url' => env('COINMARKETCAP_BASE_URL', 'https://pro-api.coinmarketcap.com/v2'),
        'timeout' => env('COINMARKETCAP_TIMEOUT', 30),
        'retry_times' => env('COINMARKETCAP_RETRY_TIMES', 3),
        'retry_delay' => env('COINMARKETCAP_RETRY_DELAY', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Plan Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your CoinMarketCap subscription plan for proper credit and
    | rate limiting. Plans: basic, hobbyist, startup, standard, professional, enterprise
    |
    */
    'plan' => [
        'type' => env('COINMARKETCAP_PLAN', 'basic'), // basic, hobbyist, startup, standard, professional, enterprise
        'credits_per_month' => env('COINMARKETCAP_CREDITS_PER_MONTH', 10000),
        'calls_per_minute' => env('COINMARKETCAP_CALLS_PER_MINUTE', 30),
        'calls_per_day' => env('COINMARKETCAP_CALLS_PER_DAY', 333),
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Management
    |--------------------------------------------------------------------------
    |
    | Configure credit tracking, optimization, and monitoring to prevent
    | exceeding your plan limits and optimize API usage costs.
    |
    */
    'credits' => [
        'tracking_enabled' => env('COINMARKETCAP_CREDIT_TRACKING', true),
        'warning_threshold' => env('COINMARKETCAP_CREDIT_WARNING', 0.8), // 80% usage warning
        'optimization_enabled' => env('COINMARKETCAP_CREDIT_OPTIMIZATION', true),
        'costs' => [
            // Credit costs per endpoint call
            'cryptocurrency_listings_latest' => 1,
            'cryptocurrency_quotes_latest' => 1,
            'cryptocurrency_quotes_historical' => 1,
            'cryptocurrency_info' => 1,
            'cryptocurrency_map' => 1,
            'cryptocurrency_market_pairs_latest' => 1,
            'cryptocurrency_ohlcv_latest' => 1,
            'cryptocurrency_ohlcv_historical' => 1,
            'cryptocurrency_trending' => 1,
            'exchange_listings_latest' => 1,
            'exchange_quotes_latest' => 1,
            'exchange_info' => 1,
            'exchange_map' => 1,
            'global_metrics_quotes_latest' => 1,
            'global_metrics_quotes_historical' => 1,
            'fiat_map' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure aggressive caching to minimize API calls and credit consumption.
    | Different TTL values for different types of data based on update frequency.
    | Includes cache warming, analytics, and strategy configurations.
    |
    */
    'cache' => [
        'enabled' => env('COINMARKETCAP_CACHE_ENABLED', true),
        'store' => env('COINMARKETCAP_CACHE_STORE'),
        'prefix' => 'coinmarketcap',
        'strategy' => env('COINMARKETCAP_CACHE_STRATEGY', 'balanced'), // aggressive, balanced, fresh, adaptive
        'analytics_enabled' => env('COINMARKETCAP_CACHE_ANALYTICS', true),
        'warming_enabled' => env('COINMARKETCAP_CACHE_WARMING', true),
        'ttl' => [
            // Static/semi-static data - cache longer
            'cryptocurrency_map' => env('COINMARKETCAP_CACHE_MAP_TTL', 86400), // 24 hours
            'cryptocurrency_info' => env('COINMARKETCAP_CACHE_INFO_TTL', 86400), // 24 hours
            'exchange_map' => env('COINMARKETCAP_CACHE_EXCHANGE_MAP_TTL', 86400), // 24 hours
            'exchange_info' => env('COINMARKETCAP_CACHE_EXCHANGE_INFO_TTL', 86400), // 24 hours
            'fiat_map' => env('COINMARKETCAP_CACHE_FIAT_TTL', 86400), // 24 hours
            
            // Dynamic data - moderate caching
            'cryptocurrency_listings' => env('COINMARKETCAP_CACHE_LISTINGS_TTL', 300), // 5 minutes
            'exchange_listings' => env('COINMARKETCAP_CACHE_EXCHANGE_LISTINGS_TTL', 300), // 5 minutes
            'trending' => env('COINMARKETCAP_CACHE_TRENDING_TTL', 1800), // 30 minutes
            
            // Real-time data - short caching
            'cryptocurrency_quotes' => env('COINMARKETCAP_CACHE_QUOTES_TTL', 60), // 1 minute
            'exchange_quotes' => env('COINMARKETCAP_CACHE_EXCHANGE_QUOTES_TTL', 60), // 1 minute
            'global_metrics' => env('COINMARKETCAP_CACHE_GLOBAL_TTL', 300), // 5 minutes
            
            // Market data - very short caching
            'market_pairs' => env('COINMARKETCAP_CACHE_PAIRS_TTL', 180), // 3 minutes
            'ohlcv' => env('COINMARKETCAP_CACHE_OHLCV_TTL', 300), // 5 minutes
            
            // Historical data - long caching
            'historical' => env('COINMARKETCAP_CACHE_HISTORICAL_TTL', 3600), // 1 hour
            
            // Default fallback
            'default' => env('COINMARKETCAP_CACHE_DEFAULT_TTL', 300), // 5 minutes
        ],
        'warming_priorities' => [
            // Override default warming priorities if needed
            // 'cryptocurrency_map' => 10,
            // 'fiat_map' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the CryptoDataProvider implementation including
    | priority and availability settings.
    |
    */
    'provider' => [
        'name' => 'coinmarketcap',
        'priority' => env('COINMARKETCAP_PRIORITY', 2), // Lower number = higher priority
        'enabled' => env('COINMARKETCAP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Cryptocurrencies
    |--------------------------------------------------------------------------
    |
    | Configure which cryptocurrencies to support. Leave empty to support
    | all available cryptocurrencies from CoinMarketCap.
    |
    */
    'supported_cryptocurrencies' => [
        // Leave empty for all, or specify symbols like: ['BTC', 'ETH', 'USDT']
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Configure which fiat currencies are supported for price conversions.
    |
    */
    'supported_currencies' => [
        'usd', 'eur', 'jpy', 'btc', 'eth', 'ltc', 'bch', 'bnb', 'eos', 'xrp',
        'xlm', 'link', 'dot', 'yfi', 'gbp', 'aud', 'cad', 'chf', 'cny', 'hkd',
        'inr', 'krw', 'rub', 'sgd', 'thb', 'try', 'twd', 'zar'
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for API calls, credit usage, and errors.
    |
    */
    'logging' => [
        'enabled' => env('COINMARKETCAP_LOGGING_ENABLED', true),
        'channel' => env('COINMARKETCAP_LOG_CHANNEL', 'stack'),
        'level' => env('COINMARKETCAP_LOG_LEVEL', 'info'),
        'log_requests' => env('COINMARKETCAP_LOG_REQUESTS', false),
        'log_responses' => env('COINMARKETCAP_LOG_RESPONSES', false),
        'log_credits' => env('COINMARKETCAP_LOG_CREDITS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events to dispatch during API operations.
    |
    */
    'events' => [
        'enabled' => env('COINMARKETCAP_EVENTS_ENABLED', true),
        'dispatch' => [
            'api_call_made' => true,
            'credit_consumed' => true,
            'credit_warning' => true,
            'rate_limit_hit' => true,
            'api_error' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints Configuration
    |--------------------------------------------------------------------------
    |
    | Configure specific endpoint settings and parameters.
    |
    */
    'endpoints' => [
        'cryptocurrency' => [
            'map' => '/cryptocurrency/map',
            'info' => '/cryptocurrency/info',
            'listings_latest' => '/cryptocurrency/listings/latest',
            'listings_historical' => '/cryptocurrency/listings/historical',
            'quotes_latest' => '/cryptocurrency/quotes/latest',
            'quotes_historical' => '/cryptocurrency/quotes/historical',
            'market_pairs_latest' => '/cryptocurrency/market-pairs/latest',
            'ohlcv_latest' => '/cryptocurrency/ohlcv/latest',
            'ohlcv_historical' => '/cryptocurrency/ohlcv/historical',
            'trending_latest' => '/cryptocurrency/trending/latest',
            'trending_most_visited' => '/cryptocurrency/trending/most-visited',
            'trending_gainers_losers' => '/cryptocurrency/trending/gainers-losers',
        ],
        'exchange' => [
            'map' => '/exchange/map',
            'info' => '/exchange/info',
            'listings_latest' => '/exchange/listings/latest',
            'listings_historical' => '/exchange/listings/historical',
            'quotes_latest' => '/exchange/quotes/latest',
            'quotes_historical' => '/exchange/quotes/historical',
            'market_pairs_latest' => '/exchange/market-pairs/latest',
        ],
        'global_metrics' => [
            'quotes_latest' => '/global-metrics/quotes/latest',
            'quotes_historical' => '/global-metrics/quotes/historical',
        ],
        'fiat' => [
            'map' => '/fiat/map',
        ],
        
        // Request limits
        'limits' => [
            'cryptocurrency_ids_per_request' => 100,
            'exchange_ids_per_request' => 100,
            'symbols_per_request' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Definitions
    |--------------------------------------------------------------------------
    |
    | Predefined plan configurations for easy setup.
    |
    */
    'plans' => [
        'basic' => [
            'credits_per_month' => 10000,
            'calls_per_minute' => 30,
            'calls_per_day' => 333,
        ],
        'hobbyist' => [
            'credits_per_month' => 40000,
            'calls_per_minute' => 30,
            'calls_per_day' => 1333,
        ],
        'startup' => [
            'credits_per_month' => 120000,
            'calls_per_minute' => 60,
            'calls_per_day' => 4000,
        ],
        'standard' => [
            'credits_per_month' => 500000,
            'calls_per_minute' => 60,
            'calls_per_day' => 16667,
        ],
        'professional' => [
            'credits_per_month' => 2000000,
            'calls_per_minute' => 60,
            'calls_per_day' => 66667,
        ],
        'enterprise' => [
            'credits_per_month' => 100000000,
            'calls_per_minute' => 120,
            'calls_per_day' => 3333333,
        ],
    ],
];