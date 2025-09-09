<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Events;

use Convertain\CoinMarketCap\Events\ApiCallMade;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ApiCallMade event.
 */
class ApiCallMadeTest extends TestCase
{
    /**
     * Test that ApiCallMade event can be created with valid data.
     */
    public function testCanCreateApiCallMadeEvent(): void
    {
        $endpoint = '/cryptocurrency/quotes/latest';
        $method = 'GET';
        $creditsConsumed = 1;
        $responseTime = 250.5;
        $cacheHit = false;
        $planType = 'basic';
        $parameters = ['symbol' => 'BTC'];

        $event = new ApiCallMade(
            $endpoint,
            $method,
            $creditsConsumed,
            $responseTime,
            $cacheHit,
            $planType,
            $parameters
        );

        $this->assertEquals($endpoint, $event->endpoint);
        $this->assertEquals($method, $event->method);
        $this->assertEquals($creditsConsumed, $event->creditsConsumed);
        $this->assertEquals($responseTime, $event->responseTime);
        $this->assertEquals($cacheHit, $event->cacheHit);
        $this->assertEquals($planType, $event->planType);
        $this->assertEquals($parameters, $event->parameters);
        $this->assertInstanceOf(\DateTimeInterface::class, $event->timestamp);
    }

    /**
     * Test event with cache hit.
     */
    public function testApiCallMadeEventWithCacheHit(): void
    {
        $event = new ApiCallMade(
            '/cryptocurrency/info',
            'GET',
            0, // No credits consumed for cache hit
            50.0, // Faster response time
            true, // Cache hit
            'professional'
        );

        $this->assertTrue($event->cacheHit);
        $this->assertEquals(0, $event->creditsConsumed);
        $this->assertEquals(50.0, $event->responseTime);
    }

    /**
     * Test event with different HTTP methods.
     */
    public function testApiCallMadeEventWithDifferentMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];

        foreach ($methods as $method) {
            $event = new ApiCallMade(
                '/test/endpoint',
                $method,
                1,
                100.0,
                false,
                'basic'
            );

            $this->assertEquals($method, $event->method);
        }
    }

    /**
     * Test event immutability.
     */
    public function testEventPropertiesAreReadonly(): void
    {
        $event = new ApiCallMade(
            '/cryptocurrency/quotes/latest',
            'GET',
            1,
            250.5,
            false,
            'basic'
        );

        // Properties should be readonly
        $this->assertTrue(property_exists($event, 'endpoint'));
        $this->assertTrue(property_exists($event, 'method'));
        $this->assertTrue(property_exists($event, 'creditsConsumed'));
        $this->assertTrue(property_exists($event, 'responseTime'));
        $this->assertTrue(property_exists($event, 'cacheHit'));
        $this->assertTrue(property_exists($event, 'planType'));
        $this->assertTrue(property_exists($event, 'parameters'));
        $this->assertTrue(property_exists($event, 'timestamp'));
    }
}