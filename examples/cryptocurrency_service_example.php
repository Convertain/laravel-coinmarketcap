<?php

declare(strict_types=1);

/**
 * CoinMarketCap Cryptocurrency Service Example
 * 
 * This example demonstrates how to use the CryptocurrencyService
 * with both real API calls and mocked responses.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

echo "=== CoinMarketCap Cryptocurrency Service Example ===\n\n";

// Configuration (use your actual API key for real calls)
$config = [
    'api' => [
        'key' => 'your-coinmarketcap-api-key-here', // Replace with your actual API key
        'base_url' => 'https://pro-api.coinmarketcap.com/v2',
        'timeout' => 30,
    ],
    'cache' => [
        'enabled' => false, // Disable caching for this example
        'prefix' => 'coinmarketcap',
    ],
    'endpoints' => [
        'cryptocurrency' => [
            'map' => '/cryptocurrency/map',
            'info' => '/cryptocurrency/info',
            'listings_latest' => '/cryptocurrency/listings/latest',
            'quotes_latest' => '/cryptocurrency/quotes/latest',
        ],
        'limits' => [
            'symbols_per_request' => 100,
        ]
    ],
    'credits' => [
        'optimization_enabled' => true,
    ],
    'logging' => [
        'enabled' => true,
    ],
];

// For this example, we'll use mock responses to avoid needing a real API key
$mockResponses = [
    // Map response
    new Response(200, [], json_encode([
        'status' => ['error_code' => 0, 'error_message' => null],
        'data' => [
            ['id' => 1, 'symbol' => 'BTC', 'name' => 'Bitcoin', 'slug' => 'bitcoin', 'rank' => 1, 'is_active' => 1],
            ['id' => 1027, 'symbol' => 'ETH', 'name' => 'Ethereum', 'slug' => 'ethereum', 'rank' => 2, 'is_active' => 1],
            ['id' => 2010, 'symbol' => 'ADA', 'name' => 'Cardano', 'slug' => 'cardano', 'rank' => 3, 'is_active' => 1],
        ]
    ])),
    
    // Info response
    new Response(200, [], json_encode([
        'status' => ['error_code' => 0, 'error_message' => null],
        'data' => [
            '1' => [
                'id' => 1, 
                'name' => 'Bitcoin', 
                'symbol' => 'BTC', 
                'slug' => 'bitcoin',
                'description' => 'Bitcoin (BTC) is a cryptocurrency, launched in January 2009.',
                'logo' => 'https://s2.coinmarketcap.com/static/img/coins/64x64/1.png',
                'urls' => [
                    'website' => ['https://bitcoin.org/'],
                    'technical_doc' => ['https://bitcoin.org/bitcoin.pdf']
                ]
            ]
        ]
    ])),
    
    // Listings response
    new Response(200, [], json_encode([
        'status' => ['error_code' => 0, 'error_message' => null],
        'data' => [
            [
                'id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC', 'slug' => 'bitcoin', 'cmc_rank' => 1,
                'num_market_pairs' => 500, 'circulating_supply' => 19000000,
                'quote' => [
                    'USD' => [
                        'price' => 45000.50, 'volume_24h' => 25000000000, 'market_cap' => 855000000000,
                        'percent_change_1h' => 0.5, 'percent_change_24h' => 2.1, 'percent_change_7d' => -1.2
                    ]
                ]
            ],
            [
                'id' => 1027, 'name' => 'Ethereum', 'symbol' => 'ETH', 'slug' => 'ethereum', 'cmc_rank' => 2,
                'num_market_pairs' => 400, 'circulating_supply' => 120000000,
                'quote' => [
                    'USD' => [
                        'price' => 2800.75, 'volume_24h' => 15000000000, 'market_cap' => 336000000000,
                        'percent_change_1h' => -0.2, 'percent_change_24h' => 1.8, 'percent_change_7d' => 3.5
                    ]
                ]
            ]
        ]
    ])),
    
    // Quotes response
    new Response(200, [], json_encode([
        'status' => ['error_code' => 0, 'error_message' => null],
        'data' => [
            '1' => [
                'id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC', 'slug' => 'bitcoin',
                'num_market_pairs' => 500, 'cmc_rank' => 1,
                'quote' => [
                    'USD' => [
                        'price' => 45000.50, 'volume_24h' => 25000000000, 'market_cap' => 855000000000,
                        'percent_change_1h' => 0.5, 'percent_change_24h' => 2.1, 'percent_change_7d' => -1.2,
                        'last_updated' => '2024-01-01T12:00:00.000Z'
                    ]
                ]
            ]
        ]
    ]))
];

try {
    // Create mock HTTP client
    $mockHandler = new MockHandler($mockResponses);
    $handlerStack = HandlerStack::create($mockHandler);
    $httpClient = new Client(['handler' => $handlerStack]);

    // Create service
    $client = new CoinMarketCapClient($config, $httpClient);
    $transformer = new CryptocurrencyTransformer();
    $service = new CryptocurrencyService($client, $transformer, $config);

    // Example 1: Get cryptocurrency map
    echo "1. Getting cryptocurrency map (top 3)...\n";
    $mapResult = $service->getMap(['limit' => 3]);
    
    echo "Found " . count($mapResult['data']) . " cryptocurrencies:\n";
    foreach ($mapResult['data'] as $crypto) {
        echo "  - {$crypto['name']} ({$crypto['symbol']}) - Rank #{$crypto['rank']}\n";
    }
    echo "\n";

    // Example 2: Get cryptocurrency info
    echo "2. Getting detailed info for Bitcoin...\n";
    $infoResult = $service->getInfo(['id' => '1']);
    
    if (isset($infoResult['data']['1'])) {
        $bitcoin = $infoResult['data']['1'];
        echo "Bitcoin Information:\n";
        echo "  - Name: {$bitcoin['name']}\n";
        echo "  - Symbol: {$bitcoin['symbol']}\n";
        echo "  - Description: " . substr($bitcoin['description'] ?? 'N/A', 0, 100) . "...\n";
        echo "  - Logo: {$bitcoin['logo']}\n";
    }
    echo "\n";

    // Example 3: Get latest listings
    echo "3. Getting latest cryptocurrency listings...\n";
    $listingsResult = $service->getListingsLatest(['limit' => 2]);
    
    echo "Top cryptocurrencies by market cap:\n";
    foreach ($listingsResult['data'] as $crypto) {
        $price = $crypto['quote']['USD']['price'] ?? 0;
        $marketCap = $crypto['quote']['USD']['market_cap'] ?? 0;
        $change24h = $crypto['quote']['USD']['percent_change_24h'] ?? 0;
        
        echo "  #{$crypto['cmc_rank']} {$crypto['name']} ({$crypto['symbol']})\n";
        echo "    Price: $" . number_format($price, 2) . "\n";
        echo "    Market Cap: $" . number_format($marketCap, 0) . "\n";
        echo "    24h Change: " . number_format($change24h, 2) . "%\n";
        echo "\n";
    }

    // Example 4: Get latest quotes with parameter validation
    echo "4. Getting latest quotes for Bitcoin...\n";
    $quotesResult = $service->getQuotesLatest(['symbol' => 'BTC']);
    
    if (isset($quotesResult['data']['1'])) {
        $bitcoin = $quotesResult['data']['1'];
        $quote = $bitcoin['quote']['USD'];
        
        echo "Bitcoin Latest Quote:\n";
        echo "  - Price: $" . number_format($quote['price'], 2) . "\n";
        echo "  - 24h Volume: $" . number_format($quote['volume_24h'], 0) . "\n";
        echo "  - Market Cap: $" . number_format($quote['market_cap'], 0) . "\n";
        echo "  - 1h Change: " . number_format($quote['percent_change_1h'], 2) . "%\n";
        echo "  - 24h Change: " . number_format($quote['percent_change_24h'], 2) . "%\n";
        echo "  - 7d Change: " . number_format($quote['percent_change_7d'], 2) . "%\n";
    }
    echo "\n";

    // Example 5: Demonstrate parameter validation
    echo "5. Demonstrating parameter validation...\n";
    try {
        $service->getInfo([]); // This should fail
    } catch (InvalidArgumentException $e) {
        echo "✓ Parameter validation working: {$e->getMessage()}\n";
    }
    echo "\n";

    // Example 6: Show credit optimization features
    echo "6. Credit optimization features:\n";
    echo "  - Batch processing: Up to {$config['endpoints']['limits']['symbols_per_request']} symbols per request\n";
    echo "  - Intelligent caching: Different TTL for different data types\n";
    echo "  - Parameter filtering: Invalid parameters automatically removed\n";
    echo "  - Error handling: Proper exception handling for API errors\n";
    echo "\n";

    echo "🎉 Example completed successfully!\n";
    echo "\nTo use with real data:\n";
    echo "1. Get your API key from https://coinmarketcap.com/api/\n";
    echo "2. Replace 'your-coinmarketcap-api-key-here' with your actual API key\n";
    echo "3. Remove the mock HTTP client setup and use the real client\n";

} catch (Exception $e) {
    echo "❌ Example failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}