<?php

declare(strict_types=1);

/**
 * Simple integration test to verify service instantiation and basic functionality.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Transformers\CryptocurrencyTransformer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

try {
    echo "Testing CoinMarketCap Cryptocurrency Service...\n";

    // Mock configuration
    $config = [
        'api' => [
            'key' => 'test-key',
            'base_url' => 'https://pro-api.coinmarketcap.com/v2',
            'timeout' => 30,
        ],
        'cache' => [
            'enabled' => false,
            'prefix' => 'coinmarketcap',
        ],
        'endpoints' => [
            'cryptocurrency' => [
                'map' => '/cryptocurrency/map',
                'info' => '/cryptocurrency/info',
                'listings_latest' => '/cryptocurrency/listings/latest',
                'quotes_latest' => '/cryptocurrency/quotes/latest',
            ],
        ],
        'credits' => [
            'optimization_enabled' => true,
        ],
        'logging' => [
            'enabled' => false,
        ],
    ];

    // Create mock HTTP client
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                [
                    'id' => 1,
                    'symbol' => 'BTC',
                    'name' => 'Bitcoin',
                    'slug' => 'bitcoin',
                    'rank' => 1,
                    'is_active' => 1,
                ]
            ]
        ])),
        new Response(200, [], json_encode([
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                '1' => [
                    'id' => 1,
                    'name' => 'Bitcoin',
                    'symbol' => 'BTC',
                    'slug' => 'bitcoin',
                    'description' => 'Bitcoin description',
                ]
            ]
        ])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $httpClient = new Client(['handler' => $handlerStack]);

    // Create service components
    echo "Creating CoinMarketCapClient...\n";
    $client = new CoinMarketCapClient($config, $httpClient);

    echo "Creating CryptocurrencyTransformer...\n";
    $transformer = new CryptocurrencyTransformer();

    echo "Creating CryptocurrencyService...\n";
    $service = new CryptocurrencyService($client, $transformer, $config);

    // Test getMap method
    echo "Testing getMap method...\n";
    $mapResult = $service->getMap(['limit' => 10]);
    
    if (!is_array($mapResult)) {
        throw new Exception('getMap should return an array');
    }
    
    if (!isset($mapResult['data'], $mapResult['metadata'])) {
        throw new Exception('getMap result should have data and metadata keys');
    }

    echo "✓ getMap method works correctly\n";

    // Test getInfo method
    echo "Testing getInfo method...\n";
    $infoResult = $service->getInfo(['id' => '1']);
    
    if (!is_array($infoResult)) {
        throw new Exception('getInfo should return an array');
    }
    
    if (!isset($infoResult['data'])) {
        throw new Exception('getInfo result should have data key');
    }

    echo "✓ getInfo method works correctly\n";

    // Test parameter validation
    echo "Testing parameter validation...\n";
    try {
        $service->getInfo([]);
        throw new Exception('getInfo should require parameters');
    } catch (InvalidArgumentException $e) {
        echo "✓ Parameter validation works correctly\n";
    }

    echo "\n🎉 All tests passed! The CryptocurrencyService is working correctly.\n";

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}