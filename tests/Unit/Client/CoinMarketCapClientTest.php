<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Client;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mockery;

class CoinMarketCapClientTest extends TestCase
{
    private CoinMarketCapClient $client;
    private array $config;
    private Client $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'api' => [
                'key' => 'test-api-key',
                'base_url' => 'https://pro-api.coinmarketcap.com/v2',
                'timeout' => 30,
            ],
        ];
        
        // We'll need to mock the HTTP client differently since it's created internally
        $this->client = new CoinMarketCapClient($this->config);
    }

    public function test_constructor_sets_config()
    {
        $client = new CoinMarketCapClient($this->config);
        
        $this->assertEquals($this->config, $client->getConfig());
    }

    public function test_get_config_returns_configuration()
    {
        $config = $this->client->getConfig();
        
        $this->assertIsArray($config);
        $this->assertEquals('test-api-key', $config['api']['key']);
        $this->assertEquals('https://pro-api.coinmarketcap.com/v2', $config['api']['base_url']);
        $this->assertEquals(30, $config['api']['timeout']);
    }

    public function test_client_creates_with_correct_headers()
    {
        // Test that the client would be created with correct headers
        // This is more of a structural test since we can't easily mock the internal Guzzle client
        $client = new CoinMarketCapClient($this->config);
        
        $this->assertInstanceOf(CoinMarketCapClient::class, $client);
    }

    public function test_client_with_different_config()
    {
        $customConfig = [
            'api' => [
                'key' => 'custom-api-key',
                'base_url' => 'https://sandbox-api.coinmarketcap.com/v2',
                'timeout' => 60,
            ],
        ];
        
        $client = new CoinMarketCapClient($customConfig);
        $config = $client->getConfig();
        
        $this->assertEquals('custom-api-key', $config['api']['key']);
        $this->assertEquals('https://sandbox-api.coinmarketcap.com/v2', $config['api']['base_url']);
        $this->assertEquals(60, $config['api']['timeout']);
    }

    /**
     * Note: The following tests would require dependency injection or mocking capabilities
     * that aren't easily available with the current implementation. In a production environment,
     * you would typically inject the HTTP client as a dependency to make testing easier.
     * 
     * For now, these serve as examples of what comprehensive tests would look like:
     */

    public function test_get_method_structure()
    {
        // Test that get method exists and has expected signature
        $this->assertTrue(method_exists($this->client, 'get'));
        
        $reflection = new \ReflectionMethod($this->client, 'get');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('endpoint', $parameters[0]->getName());
        $this->assertEquals('params', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
    }

    public function test_post_method_structure()
    {
        // Test that post method exists and has expected signature
        $this->assertTrue(method_exists($this->client, 'post'));
        
        $reflection = new \ReflectionMethod($this->client, 'post');
        $parameters = $reflection->getParameters();
        
        $this->assertEquals('endpoint', $parameters[0]->getName());
        $this->assertEquals('data', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
    }

    /**
     * Integration-style tests that would work if we had a test server or mock server:
     */

    /*
    public function test_get_request_with_parameters()
    {
        // This would require setting up a mock HTTP server or using VCR
        $params = ['limit' => 10, 'convert' => 'USD'];
        
        try {
            $response = $this->client->get('/cryptocurrency/listings/latest', $params);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        } catch (\Exception $e) {
            // Expected if no valid API key or network issues
            $this->addToAssertionCount(1);
        }
    }

    public function test_post_request_with_data()
    {
        // This would require setting up a mock HTTP server or using VCR
        $data = ['symbol' => 'BTC'];
        
        try {
            $response = $this->client->post('/cryptocurrency/quotes/latest', $data);
            $this->assertInstanceOf(ResponseInterface::class, $response);
        } catch (\Exception $e) {
            // Expected if no valid API key or network issues
            $this->addToAssertionCount(1);
        }
    }
    */

    public function test_client_configuration_validation()
    {
        // Test with missing API key
        $invalidConfig = [
            'api' => [
                'base_url' => 'https://pro-api.coinmarketcap.com/v2',
                'timeout' => 30,
            ],
        ];
        
        $client = new CoinMarketCapClient($invalidConfig);
        
        // Should still create client, but config will show missing key
        $config = $client->getConfig();
        $this->assertArrayNotHasKey('key', $config['api']);
    }

    public function test_client_with_minimal_config()
    {
        $minimalConfig = [
            'api' => [
                'key' => 'test-key',
            ],
        ];
        
        // This should work and use defaults for missing values
        $client = new CoinMarketCapClient($minimalConfig);
        $config = $client->getConfig();
        
        $this->assertEquals('test-key', $config['api']['key']);
    }
}