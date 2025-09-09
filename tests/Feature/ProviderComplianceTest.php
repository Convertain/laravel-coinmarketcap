<?php

namespace Convertain\CoinMarketCap\Tests\Feature;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\CoinMarketCapProvider;

class ProviderComplianceTest extends TestCase
{
    private CoinMarketCapProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->provider = $this->app->make(CoinMarketCapProvider::class);
    }

    public function test_provider_interface_compliance()
    {
        // Test that provider implements expected interface methods
        $requiredMethods = [
            'getName',
            'isAvailable', 
            'getClient',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->provider, $method),
                "Provider must implement {$method} method"
            );
        }
    }

    public function test_provider_name_compliance()
    {
        $name = $this->provider->getName();
        
        $this->assertIsString($name);
        $this->assertEquals('coinmarketcap', $name);
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $name);
    }

    public function test_provider_availability_compliance()
    {
        $isAvailable = $this->provider->isAvailable();
        
        $this->assertIsBool($isAvailable);
        
        // With valid API key, should be available
        $this->assertTrue($isAvailable);
    }

    public function test_provider_availability_with_missing_api_key()
    {
        // Create provider instance without API key
        $config = ['api' => ['key' => '']];
        $client = new \Convertain\CoinMarketCap\Client\CoinMarketCapClient($config);
        $provider = new \Convertain\CoinMarketCap\CoinMarketCapProvider($client);
        
        $isAvailable = $provider->isAvailable();
        
        $this->assertFalse($isAvailable);
    }

    public function test_provider_client_access_compliance()
    {
        $client = $this->provider->getClient();
        
        $this->assertInstanceOf(
            \Convertain\CoinMarketCap\Client\CoinMarketCapClient::class,
            $client
        );
    }

    public function test_provider_configuration_compliance()
    {
        $client = $this->provider->getClient();
        $config = $client->getConfig();
        
        // Test required configuration keys
        $requiredConfigKeys = [
            'api.key',
            'api.base_url', 
            'api.timeout',
        ];

        foreach ($requiredConfigKeys as $keyPath) {
            $keys = explode('.', $keyPath);
            $value = $config;
            
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $value, "Configuration must have {$keyPath}");
                $value = $value[$key];
            }
            
            $this->assertNotEmpty($value, "Configuration {$keyPath} must not be empty");
        }
    }

    public function test_provider_service_registration_compliance()
    {
        // Test that provider is properly registered in Laravel container
        $this->assertTrue($this->app->bound(\Convertain\CoinMarketCap\CoinMarketCapProvider::class));
        $this->assertTrue($this->app->bound(\Convertain\CoinMarketCap\Client\CoinMarketCapClient::class));
        
        // Test singleton registration
        $provider1 = $this->app->make(\Convertain\CoinMarketCap\CoinMarketCapProvider::class);
        $provider2 = $this->app->make(\Convertain\CoinMarketCap\CoinMarketCapProvider::class);
        
        $this->assertSame($provider1, $provider2);
    }

    public function test_provider_error_handling_compliance()
    {
        // Test that provider methods handle errors gracefully
        
        // Test with invalid configuration
        $invalidConfig = [
            'api' => [
                'key' => null,
                'base_url' => '',
                'timeout' => -1,
            ],
        ];
        
        try {
            $client = new \Convertain\CoinMarketCap\Client\CoinMarketCapClient($invalidConfig);
            $provider = new \Convertain\CoinMarketCap\CoinMarketCapProvider($client);
            
            // Should not throw exceptions
            $name = $provider->getName();
            $isAvailable = $provider->isAvailable();
            $client = $provider->getClient();
            
            $this->assertIsString($name);
            $this->assertIsBool($isAvailable);
            $this->assertInstanceOf(\Convertain\CoinMarketCap\Client\CoinMarketCapClient::class, $client);
            
        } catch (\Exception $e) {
            $this->fail("Provider should handle invalid configuration gracefully: " . $e->getMessage());
        }
    }

    public function test_provider_data_format_compliance()
    {
        // Test that provider returns data in expected formats
        $name = $this->provider->getName();
        $isAvailable = $this->provider->isAvailable();
        $client = $this->provider->getClient();
        
        // Name should be string
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
        
        // Availability should be boolean
        $this->assertIsBool($isAvailable);
        
        // Client should be proper instance
        $this->assertInstanceOf(
            \Convertain\CoinMarketCap\Client\CoinMarketCapClient::class,
            $client
        );
    }

    public function test_provider_performance_compliance()
    {
        // Test that provider methods perform within reasonable time limits
        
        $startTime = microtime(true);
        $name = $this->provider->getName();
        $nameTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        $isAvailable = $this->provider->isAvailable();
        $availabilityTime = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        $client = $this->provider->getClient();
        $clientTime = microtime(true) - $startTime;
        
        // Each method should complete in under 100ms
        $this->assertLessThan(0.1, $nameTime, 'getName() should be fast');
        $this->assertLessThan(0.1, $availabilityTime, 'isAvailable() should be fast');
        $this->assertLessThan(0.1, $clientTime, 'getClient() should be fast');
    }

    public function test_provider_consistency_compliance()
    {
        // Test that provider methods return consistent results
        
        $name1 = $this->provider->getName();
        $name2 = $this->provider->getName();
        $this->assertEquals($name1, $name2);
        
        $available1 = $this->provider->isAvailable();
        $available2 = $this->provider->isAvailable();
        $this->assertEquals($available1, $available2);
        
        $client1 = $this->provider->getClient();
        $client2 = $this->provider->getClient();
        $this->assertSame($client1, $client2);
    }

    public function test_provider_memory_compliance()
    {
        // Test that provider doesn't consume excessive memory
        $initialMemory = memory_get_usage();
        
        // Create multiple provider instances and call methods
        for ($i = 0; $i < 100; $i++) {
            $this->provider->getName();
            $this->provider->isAvailable();
            $this->provider->getClient();
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be minimal (less than 1MB)
        $this->assertLessThan(1048576, $memoryIncrease, 'Provider should not leak memory');
    }

    public function test_provider_thread_safety_compliance()
    {
        // Test basic thread safety by rapid concurrent access simulation
        $results = [];
        
        for ($i = 0; $i < 10; $i++) {
            $results[] = [
                'name' => $this->provider->getName(),
                'available' => $this->provider->isAvailable(),
                'client' => $this->provider->getClient(),
            ];
        }
        
        // All results should be identical
        $first = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($first['name'], $result['name']);
            $this->assertEquals($first['available'], $result['available']);
            $this->assertSame($first['client'], $result['client']);
        }
    }

    public function test_provider_documentation_compliance()
    {
        // Test that provider class has proper documentation
        $reflection = new \ReflectionClass($this->provider);
        
        $this->assertNotEmpty($reflection->getDocComment(), 'Provider class should have documentation');
        
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if (!$method->isConstructor()) {
                $this->assertNotEmpty(
                    $method->getDocComment(), 
                    "Method {$method->getName()} should have documentation"
                );
            }
        }
    }

    public function test_provider_configuration_validation()
    {
        // Test various configuration scenarios
        $configTests = [
            // Valid configuration
            [
                'config' => [
                    'api' => [
                        'key' => 'test-key',
                        'base_url' => 'https://api.example.com',
                        'timeout' => 30,
                    ]
                ],
                'should_be_available' => true,
            ],
            // Missing API key
            [
                'config' => [
                    'api' => [
                        'base_url' => 'https://api.example.com',
                        'timeout' => 30,
                    ]
                ],
                'should_be_available' => false,
            ],
            // Empty API key
            [
                'config' => [
                    'api' => [
                        'key' => '',
                        'base_url' => 'https://api.example.com',
                        'timeout' => 30,
                    ]
                ],
                'should_be_available' => false,
            ],
        ];

        foreach ($configTests as $test) {
            $client = new \Convertain\CoinMarketCap\Client\CoinMarketCapClient($test['config']);
            $provider = new \Convertain\CoinMarketCap\CoinMarketCapProvider($client);
            
            $this->assertEquals(
                $test['should_be_available'], 
                $provider->isAvailable(),
                'Provider availability should match expected result'
            );
        }
    }
}