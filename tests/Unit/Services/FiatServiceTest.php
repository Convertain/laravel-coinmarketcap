<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Mockery;
use Convertain\CoinMarketCap\Services\FiatService;
use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * Fiat Service Test
 *
 * Unit tests for FiatService class covering fiat currency mapping
 * and reference data functionality.
 */
class FiatServiceTest extends TestCase
{
    private $mockClient;
    private FiatService $fiatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(CoinMarketCapClient::class);
        $this->fiatService = new FiatService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testMapReturnsTransformedFiatMap(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0, 'error_message' => null],
            'data' => [
                [
                    'id' => 2781,
                    'name' => 'United States Dollar',
                    'sign' => '$',
                    'symbol' => 'USD',
                ],
                [
                    'id' => 2790,
                    'name' => 'Euro',
                    'sign' => '€',
                    'symbol' => 'EUR',
                ]
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 100,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->map();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        $this->assertCount(2, $result['data']);
        
        $usd = $result['data'][0];
        $this->assertEquals(2781, $usd['id']);
        $this->assertEquals('United States Dollar', $usd['name']);
        $this->assertEquals('$', $usd['sign']);
        $this->assertEquals('USD', $usd['symbol']);
    }

    public function testMapWithCustomParameters(): void
    {
        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 10,
                'limit' => 50,
                'include_metals' => 'true',
            ], 86400)
            ->once()
            ->andReturn(['status' => ['error_code' => 0], 'data' => []]);

        $this->fiatService->map(start: 10, limit: 50, includeMetals: true);
    }

    public function testGetAllCurrencies(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getAllCurrencies();

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testGetCurrencyBySymbolFound(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getCurrencyBySymbol('EUR');

        $this->assertNotNull($result);
        $this->assertEquals(2790, $result['id']);
        $this->assertEquals('Euro', $result['name']);
        $this->assertEquals('EUR', $result['symbol']);
    }

    public function testGetCurrencyBySymbolNotFound(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getCurrencyBySymbol('XYZ');

        $this->assertNull($result);
    }

    public function testGetCurrencyBySymbolCaseInsensitive(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getCurrencyBySymbol('usd');

        $this->assertNotNull($result);
        $this->assertEquals('USD', $result['symbol']);
    }

    public function testGetCurrencyById(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getCurrencyById(2790);

        $this->assertNotNull($result);
        $this->assertEquals(2790, $result['id']);
        $this->assertEquals('Euro', $result['name']);
    }

    public function testIsCurrencySupported(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->twice()
            ->andReturn($mockResponse);

        $this->assertTrue($this->fiatService->isCurrencySupported('USD'));
        $this->assertFalse($this->fiatService->isCurrencySupported('XYZ'));
    }

    public function testGetMajorCurrencies(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
                ['id' => 2792, 'name' => 'Japanese Yen', 'sign' => '¥', 'symbol' => 'JPY'],
                ['id' => 2800, 'name' => 'Some Minor Currency', 'sign' => 'X', 'symbol' => 'XYZ'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getMajorCurrencies();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['status']['error_code']);
        
        // Should only contain major currencies (USD, EUR, JPY in our mock)
        $this->assertCount(3, $result['data']);
        
        $symbols = array_column($result['data'], 'symbol');
        $this->assertContains('USD', $symbols);
        $this->assertContains('EUR', $symbols);
        $this->assertContains('JPY', $symbols);
        $this->assertNotContains('XYZ', $symbols);
    }

    public function testGetRegionalCurrencies(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
                ['id' => 2784, 'name' => 'Canadian Dollar', 'sign' => '$', 'symbol' => 'CAD'],
                ['id' => 2790, 'name' => 'Euro', 'sign' => '€', 'symbol' => 'EUR'],
                ['id' => 2791, 'name' => 'British Pound Sterling', 'sign' => '£', 'symbol' => 'GBP'],
                ['id' => 2792, 'name' => 'Japanese Yen', 'sign' => '¥', 'symbol' => 'JPY'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getRegionalCurrencies();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        
        $this->assertArrayHasKey('North America', $result['data']);
        $this->assertArrayHasKey('Europe', $result['data']);
        $this->assertArrayHasKey('Asia Pacific', $result['data']);
        
        // North America should contain USD and CAD
        $naSymbols = array_column($result['data']['North America'], 'symbol');
        $this->assertContains('USD', $naSymbols);
        $this->assertContains('CAD', $naSymbols);
        
        // Europe should contain EUR and GBP
        $euSymbols = array_column($result['data']['Europe'], 'symbol');
        $this->assertContains('EUR', $euSymbols);
        $this->assertContains('GBP', $euSymbols);
    }

    public function testGetPreciousMetals(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 0],
            'data' => [
                ['id' => 1, 'name' => 'Gold Ounce', 'sign' => 'XAU', 'symbol' => 'XAU'],
                ['id' => 2, 'name' => 'Silver Ounce', 'sign' => 'XAG', 'symbol' => 'XAG'],
                ['id' => 2781, 'name' => 'United States Dollar', 'sign' => '$', 'symbol' => 'USD'],
            ]
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 5000,
                'include_metals' => 'true',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->getPreciousMetals();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        
        // Should only contain precious metals (XAU, XAG)
        $this->assertCount(2, $result['data']);
        
        $symbols = array_column($result['data'], 'symbol');
        $this->assertContains('XAU', $symbols);
        $this->assertContains('XAG', $symbols);
        $this->assertNotContains('USD', $symbols);
    }

    public function testTransformFiatMapWithInvalidResponse(): void
    {
        $mockResponse = [
            'status' => ['error_code' => 400, 'error_message' => 'Bad request'],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->with('/fiat/map', [
                'start' => 1,
                'limit' => 100,
                'include_metals' => 'false',
            ], 86400)
            ->once()
            ->andReturn($mockResponse);

        $result = $this->fiatService->map();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(400, $result['status']['error_code']);
        $this->assertEmpty($result['data']);
    }
}