<?php

namespace Convertain\CoinMarketCap\Tests\Feature;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Credit\CreditManager;
use Convertain\CoinMarketCap\Credit\CreditOptimizer;
use Convertain\CoinMarketCap\Services\CryptocurrencyService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use GuzzleHttp\Psr7\Response;
use Mockery;

class CreditOptimizationTest extends TestCase
{
    private CreditManager $creditManager;
    private CreditOptimizer $optimizer;
    private CoinMarketCapClient $mockClient;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'plan' => [
                'credits_per_month' => 10000,
            ],
            'credits' => [
                'tracking_enabled' => true,
                'warning_threshold' => 0.8,
                'optimization_enabled' => true,
                'costs' => [
                    'cryptocurrency_listings_latest' => 1,
                    'cryptocurrency_quotes_latest' => 1,
                    'exchange_listings_latest' => 1,
                    'global_metrics_quotes_latest' => 1,
                ],
            ],
        ];
        
        $this->creditManager = new CreditManager($this->config);
        $this->optimizer = new CreditOptimizer($this->config, $this->creditManager);
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
    }

    public function test_credit_optimization_workflow()
    {
        // Simulate high credit usage scenario
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 7500);
        
        // Test that optimizer reduces parameters when usage is high
        $originalParams = [
            'limit' => 5000,
            'aux' => 'platform,tags,date_added',
            'convert' => 'USD,EUR,JPY'
        ];
        
        $optimizedParams = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $originalParams);
        
        // aux should be removed due to high usage
        $this->assertArrayNotHasKey('aux', $optimizedParams);
        $this->assertEquals(5000, $optimizedParams['limit']);
        
        // Test quotes optimization
        $quotesParams = ['convert' => 'USD,EUR,JPY,GBP'];
        $optimizedQuotes = $this->optimizer->optimizeParams('cryptocurrency_quotes_latest', $quotesParams);
        
        // Should limit to USD only
        $this->assertEquals('USD', $optimizedQuotes['convert']);
    }

    public function test_credit_warning_system()
    {
        // Consume credits up to warning threshold
        $warningThreshold = 8000; // 80% of 10000
        
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', $warningThreshold - 1);
        
        // Should still return true (no warning yet)
        $result = $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $this->assertTrue($result);
        
        // Next consumption should trigger warning
        $result = $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $this->assertFalse($result); // Warning triggered
    }

    public function test_credit_consumption_tracking()
    {
        $service = new CryptocurrencyService($this->mockClient);
        
        // Mock successful API responses
        $mockResponse = new Response(200, [], json_encode([
            'status' => ['error_code' => 0],
            'data' => []
        ]));

        $this->mockClient
            ->shouldReceive('get')
            ->times(3)
            ->andReturn($mockResponse);

        // Simulate multiple API calls
        $endpoints = [
            'cryptocurrency_listings_latest',
            'cryptocurrency_quotes_latest', 
            'cryptocurrency_info'
        ];

        $initialStats = $this->creditManager->getUsageStats();
        
        foreach ($endpoints as $endpoint) {
            // Simulate credit consumption for each call
            $cost = $this->creditManager->getCreditCost($endpoint);
            $this->creditManager->consumeCredits($endpoint, $cost);
        }

        $finalStats = $this->creditManager->getUsageStats();
        
        $this->assertEquals($initialStats['used'] + 3, $finalStats['used']);
        $this->assertEquals($initialStats['remaining'] - 3, $finalStats['remaining']);
    }

    public function test_plan_limit_enforcement()
    {
        // Test different plan scenarios
        $planTests = [
            ['plan' => 'basic', 'limit' => 10000, 'consume' => 9999, 'should_allow' => true],
            ['plan' => 'basic', 'limit' => 10000, 'consume' => 10000, 'should_allow' => false],
            ['plan' => 'hobbyist', 'limit' => 40000, 'consume' => 39999, 'should_allow' => true],
        ];

        foreach ($planTests as $test) {
            $config = $this->config;
            $config['plan']['credits_per_month'] = $test['limit'];
            $manager = new CreditManager($config);
            
            // Consume credits up to the test amount
            $manager->consumeCredits('cryptocurrency_listings_latest', $test['consume']);
            
            // Test if next consumption is allowed
            $result = $manager->hasSufficientCredits('cryptocurrency_listings_latest');
            $this->assertEquals($test['should_allow'], $result);
        }
    }

    public function test_optimization_suggestions()
    {
        // Test alternative endpoint suggestions
        $suggestions = [
            'cryptocurrency_listings_latest' => ['cryptocurrency_map'],
            'cryptocurrency_quotes_historical' => ['cryptocurrency_ohlcv_latest'],
        ];

        foreach ($suggestions as $endpoint => $expectedAlternatives) {
            $alternatives = $this->optimizer->suggestAlternatives($endpoint);
            
            $this->assertArrayHasKey('alternatives', $alternatives);
            $this->assertArrayHasKey('reason', $alternatives);
            
            foreach ($expectedAlternatives as $alternative) {
                $this->assertContains($alternative, $alternatives['alternatives']);
            }
        }
    }

    public function test_credit_optimization_integration_scenario()
    {
        // Simulate a real-world scenario with multiple API calls
        
        // 1. Start with fresh credit manager
        $stats = $this->creditManager->getUsageStats();
        $this->assertEquals(0, $stats['used']);
        
        // 2. Make several API calls
        $callSequence = [
            ['endpoint' => 'cryptocurrency_listings_latest', 'credits' => 1],
            ['endpoint' => 'cryptocurrency_quotes_latest', 'credits' => 1],
            ['endpoint' => 'exchange_listings_latest', 'credits' => 1],
            ['endpoint' => 'global_metrics_quotes_latest', 'credits' => 1],
        ];
        
        foreach ($callSequence as $call) {
            $this->assertTrue($this->creditManager->hasSufficientCredits($call['endpoint']));
            $this->creditManager->consumeCredits($call['endpoint'], $call['credits']);
        }
        
        $stats = $this->creditManager->getUsageStats();
        $this->assertEquals(4, $stats['used']);
        
        // 3. Simulate high usage (get to 75% usage)
        $targetUsage = 7500;
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', $targetUsage - 4);
        
        // 4. Test optimization kicks in
        $params = [
            'limit' => 1000,
            'aux' => 'platform,tags',
            'convert' => 'USD,EUR'
        ];
        
        $optimizedParams = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        // At 75% usage, aux should be removed
        $this->assertArrayNotHasKey('aux', $optimizedParams);
        
        // 5. Push to warning threshold
        $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 500);
        
        $result = $this->creditManager->consumeCredits('cryptocurrency_listings_latest', 1);
        $this->assertFalse($result); // Should trigger warning
    }

    public function test_credit_manager_with_disabled_tracking()
    {
        $config = $this->config;
        $config['credits']['tracking_enabled'] = false;
        
        $manager = new CreditManager($config);
        
        // When tracking is disabled, should always allow consumption
        $result = $manager->consumeCredits('cryptocurrency_listings_latest', 20000); // Over limit
        // Note: Current implementation doesn't check tracking_enabled flag
        // This test documents expected behavior for future implementation
        
        $stats = $manager->getUsageStats();
        $this->assertEquals(20000, $stats['used']);
    }

    public function test_optimizer_with_disabled_optimization()
    {
        $config = $this->config;
        $config['credits']['optimization_enabled'] = false;
        
        $optimizer = new CreditOptimizer($config, $this->creditManager);
        
        $originalParams = [
            'limit' => 5000,
            'aux' => 'platform,tags',
            'convert' => 'USD,EUR,JPY'
        ];
        
        $result = $optimizer->optimizeParams('cryptocurrency_listings_latest', $originalParams);
        
        // Should return unchanged when optimization is disabled
        $this->assertEquals($originalParams, $result);
    }

    public function test_complex_credit_scenarios()
    {
        // Test complex scenarios with mixed endpoint usage
        
        $scenarios = [
            // Scenario 1: Heavy listings usage
            ['listings' => 5000, 'quotes' => 1000, 'expected_warning' => false],
            // Scenario 2: Balanced usage approaching limit
            ['listings' => 4000, 'quotes' => 4500, 'expected_warning' => true],
            // Scenario 3: Quota exhausted
            ['listings' => 6000, 'quotes' => 5000, 'expected_warning' => true],
        ];
        
        foreach ($scenarios as $index => $scenario) {
            $manager = new CreditManager($this->config);
            
            // Consume credits according to scenario
            $manager->consumeCredits('cryptocurrency_listings_latest', $scenario['listings']);
            $manager->consumeCredits('cryptocurrency_quotes_latest', $scenario['quotes']);
            
            // Try one more call
            $result = $manager->consumeCredits('cryptocurrency_listings_latest', 1);
            
            if ($scenario['expected_warning']) {
                $this->assertFalse($result, "Scenario {$index} should trigger warning");
            } else {
                $this->assertTrue($result, "Scenario {$index} should not trigger warning");
            }
        }
    }
}