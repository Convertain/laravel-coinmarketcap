<?php

namespace Convertain\CoinMarketCap\Tests\Integration;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\CoinMarketCapProvider;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

class CoinMarketCapProviderTest extends TestCase
{
    private CoinMarketCapProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->provider = $this->app->make(CoinMarketCapProvider::class);
    }

    public function test_provider_is_registered_in_container()
    {
        $this->assertInstanceOf(CoinMarketCapProvider::class, $this->provider);
    }

    public function test_provider_has_client_dependency()
    {
        $client = $this->provider->getClient();
        
        $this->assertInstanceOf(CoinMarketCapClient::class, $client);
    }

    public function test_provider_returns_correct_name()
    {
        $name = $this->provider->getName();
        
        $this->assertEquals('coinmarketcap', $name);
    }

    public function test_provider_availability_with_api_key()
    {
        // With API key configured
        $isAvailable = $this->provider->isAvailable();
        
        $this->assertTrue($isAvailable);
    }

    public function test_provider_availability_without_api_key()
    {
        // Create provider without API key
        $config = ['api' => ['key' => '']];
        $client = new CoinMarketCapClient($config);
        $provider = new CoinMarketCapProvider($client);
        
        $isAvailable = $provider->isAvailable();
        
        $this->assertFalse($isAvailable);
    }

    public function test_service_provider_registers_client()
    {
        $client = $this->app->make(CoinMarketCapClient::class);
        
        $this->assertInstanceOf(CoinMarketCapClient::class, $client);
    }

    public function test_service_provider_registers_provider()
    {
        $provider = $this->app->make(CoinMarketCapProvider::class);
        
        $this->assertInstanceOf(CoinMarketCapProvider::class, $provider);
    }

    public function test_client_has_correct_configuration()
    {
        $client = $this->app->make(CoinMarketCapClient::class);
        $config = $client->getConfig();
        
        $this->assertEquals('test-api-key', $config['api']['key']);
        $this->assertEquals('https://pro-api.coinmarketcap.com/v2', $config['api']['base_url']);
        $this->assertEquals(30, $config['api']['timeout']);
    }

    public function test_provider_client_integration()
    {
        $provider = $this->app->make(CoinMarketCapProvider::class);
        $client = $provider->getClient();
        $config = $client->getConfig();
        
        $this->assertEquals('test-api-key', $config['api']['key']);
    }
}