<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Credit;

use Convertain\CoinMarketCap\Tests\TestCase;
use Convertain\CoinMarketCap\Credit\CreditOptimizer;
use Convertain\CoinMarketCap\Credit\CreditManager;
use Mockery;

class CreditOptimizerTest extends TestCase
{
    private CreditOptimizer $optimizer;
    private CreditManager $mockCreditManager;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'credits' => [
                'optimization_enabled' => true,
            ],
        ];
        
        $this->mockCreditManager = Mockery::mock(CreditManager::class);
        $this->optimizer = new CreditOptimizer($this->config, $this->mockCreditManager);
    }

    public function test_optimize_params_returns_original_when_disabled()
    {
        $config = ['credits' => ['optimization_enabled' => false]];
        $optimizer = new CreditOptimizer($config, $this->mockCreditManager);
        
        $params = ['limit' => 200];
        $result = $optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        $this->assertEquals($params, $result);
    }

    public function test_optimize_listings_params_adds_default_limit()
    {
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 50.0]);
        
        $params = [];
        $result = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        $this->assertEquals(100, $result['limit']);
    }

    public function test_optimize_listings_params_preserves_existing_limit()
    {
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 50.0]);
        
        $params = ['limit' => 50];
        $result = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        $this->assertEquals(50, $result['limit']);
    }

    public function test_optimize_listings_params_removes_aux_when_high_usage()
    {
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 75.0]);
        
        $params = ['aux' => 'platform,tags', 'limit' => 50];
        $result = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        $this->assertArrayNotHasKey('aux', $result);
        $this->assertEquals(50, $result['limit']);
    }

    public function test_optimize_quotes_params_limits_convert_currencies()
    {
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 85.0]);
        
        $params = ['convert' => 'USD,EUR,JPY'];
        $result = $this->optimizer->optimizeParams('cryptocurrency_quotes_latest', $params);
        
        $this->assertEquals('USD', $result['convert']);
    }

    public function test_optimize_quotes_params_preserves_convert_when_low_usage()
    {
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 50.0]);
        
        $params = ['convert' => 'USD,EUR,JPY'];
        $result = $this->optimizer->optimizeParams('cryptocurrency_quotes_latest', $params);
        
        $this->assertEquals('USD,EUR,JPY', $result['convert']);
    }

    public function test_optimize_params_returns_original_for_unknown_endpoint()
    {
        $params = ['custom_param' => 'value'];
        $result = $this->optimizer->optimizeParams('unknown_endpoint', $params);
        
        $this->assertEquals($params, $result);
    }

    public function test_suggest_alternatives_for_listings()
    {
        $alternatives = $this->optimizer->suggestAlternatives('cryptocurrency_listings_latest');
        
        $this->assertIsArray($alternatives);
        $this->assertArrayHasKey('alternatives', $alternatives);
        $this->assertArrayHasKey('reason', $alternatives);
        $this->assertContains('cryptocurrency_map', $alternatives['alternatives']);
    }

    public function test_suggest_alternatives_for_historical_quotes()
    {
        $alternatives = $this->optimizer->suggestAlternatives('cryptocurrency_quotes_historical');
        
        $this->assertIsArray($alternatives);
        $this->assertArrayHasKey('alternatives', $alternatives);
        $this->assertArrayHasKey('reason', $alternatives);
        $this->assertContains('cryptocurrency_ohlcv_latest', $alternatives['alternatives']);
    }

    public function test_suggest_alternatives_returns_empty_for_unknown_endpoint()
    {
        $alternatives = $this->optimizer->suggestAlternatives('unknown_endpoint');
        
        $this->assertIsArray($alternatives);
        $this->assertEmpty($alternatives);
    }

    public function test_optimizer_workflow_integration()
    {
        // Test complete workflow with high usage scenario
        $this->mockCreditManager
            ->shouldReceive('getUsageStats')
            ->andReturn(['usage_percentage' => 90.0]);
        
        // Test listings optimization
        $listingsParams = ['aux' => 'platform,tags,date_added'];
        $optimizedListings = $this->optimizer->optimizeParams('cryptocurrency_listings_latest', $listingsParams);
        
        $this->assertArrayNotHasKey('aux', $optimizedListings);
        $this->assertEquals(100, $optimizedListings['limit']);
        
        // Test quotes optimization
        $quotesParams = ['convert' => 'USD,EUR,JPY,GBP'];
        $optimizedQuotes = $this->optimizer->optimizeParams('cryptocurrency_quotes_latest', $quotesParams);
        
        $this->assertEquals('USD', $optimizedQuotes['convert']);
    }

    public function test_optimizer_with_disabled_optimization()
    {
        $config = ['credits' => ['optimization_enabled' => false]];
        $optimizer = new CreditOptimizer($config, $this->mockCreditManager);
        
        $params = ['convert' => 'USD,EUR,JPY', 'aux' => 'platform'];
        $result = $optimizer->optimizeParams('cryptocurrency_listings_latest', $params);
        
        $this->assertEquals($params, $result);
    }
}