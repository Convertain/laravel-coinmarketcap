<?php

namespace Convertain\CoinMarketCap\Tests\Unit\Monitoring;

use Convertain\CoinMarketCap\Monitoring\EventMonitor;
use Convertain\CoinMarketCap\Events\ApiCallMade;
use Convertain\CoinMarketCap\Events\ApiError;
use Convertain\CoinMarketCap\Events\CreditWarning;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the EventMonitor service.
 */
class EventMonitorTest extends TestCase
{
    private Dispatcher $eventDispatcher;
    private EventMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = Mockery::mock(Dispatcher::class);
        $this->monitor = new EventMonitor($this->eventDispatcher, [
            'enabled' => true,
            'store_events' => true,
            'max_stored_events' => 100,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test API call made event firing.
     */
    public function testApiCallMadeEventFiring(): void
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ApiCallMade::class));

        $this->monitor->apiCallMade(
            '/cryptocurrency/quotes/latest',
            'GET',
            1,
            250.5,
            false,
            'basic',
            ['symbol' => 'BTC']
        );
    }

    /**
     * Test credit warning event firing.
     */
    public function testCreditWarningEventFiring(): void
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(CreditWarning::class));

        $this->monitor->creditWarning(
            80.0,
            2000,
            5,
            'basic',
            82.5,
            10000,
            400.0
        );
    }

    /**
     * Test API error event firing.
     */
    public function testApiErrorEventFiring(): void
    {
        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ApiError::class));

        $this->monitor->apiError(
            '/cryptocurrency/quotes/latest',
            500,
            'Internal Server Error',
            1,
            1,
            'GET',
            ['symbol' => 'BTC'],
            'basic',
            1500.0
        );
    }

    /**
     * Test event monitoring can be disabled.
     */
    public function testEventMonitoringCanBeDisabled(): void
    {
        $disabledMonitor = new EventMonitor($this->eventDispatcher, [
            'enabled' => false,
        ]);

        // Should not receive any dispatch calls
        $this->eventDispatcher->shouldNotReceive('dispatch');

        $disabledMonitor->apiCallMade(
            '/test',
            'GET',
            1,
            100.0,
            false,
            'basic'
        );
    }

    /**
     * Test analytics data structure.
     */
    public function testAnalyticsDataStructure(): void
    {
        // Mock some events being dispatched
        $this->eventDispatcher->shouldReceive('dispatch')->times(3);

        // Fire some events
        $this->monitor->apiCallMade('/endpoint1', 'GET', 1, 100.0, false, 'basic');
        $this->monitor->apiCallMade('/endpoint1', 'GET', 1, 200.0, true, 'basic');
        $this->monitor->apiError('/endpoint2', 500, 'Error', 0, 1, 'GET', [], 'basic', 300.0);

        $analytics = $this->monitor->getAnalytics(24);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('time_window_hours', $analytics);
        $this->assertArrayHasKey('total_events', $analytics);
        $this->assertArrayHasKey('api_calls', $analytics);
        $this->assertArrayHasKey('credits_consumed', $analytics);
        $this->assertArrayHasKey('errors', $analytics);
        $this->assertArrayHasKey('retries', $analytics);
        $this->assertArrayHasKey('rate_limits', $analytics);
        $this->assertArrayHasKey('warnings', $analytics);
        $this->assertArrayHasKey('average_response_time', $analytics);
        $this->assertArrayHasKey('cache_hit_rate', $analytics);
        $this->assertArrayHasKey('error_rate', $analytics);
        $this->assertArrayHasKey('most_used_endpoints', $analytics);
        $this->assertArrayHasKey('error_breakdown', $analytics);

        $this->assertEquals(24, $analytics['time_window_hours']);
    }

    /**
     * Test clearing stored events.
     */
    public function testClearingStoredEvents(): void
    {
        $this->eventDispatcher->shouldReceive('dispatch')->times(2);

        // Fire some events
        $this->monitor->apiCallMade('/test', 'GET', 1, 100.0, false, 'basic');
        $this->monitor->apiError('/test', 500, 'Error', 0, 1);

        // Clear events
        $this->monitor->clearEvents();

        // Analytics should show no events
        $analytics = $this->monitor->getAnalytics();
        $this->assertEquals(0, $analytics['total_events']);
    }

    /**
     * Test max stored events limit.
     */
    public function testMaxStoredEventsLimit(): void
    {
        $monitorWithLimit = new EventMonitor($this->eventDispatcher, [
            'enabled' => true,
            'store_events' => true,
            'max_stored_events' => 2,
        ]);

        $this->eventDispatcher->shouldReceive('dispatch')->times(3);

        // Fire 3 events (more than limit)
        $monitorWithLimit->apiCallMade('/test1', 'GET', 1, 100.0, false, 'basic');
        $monitorWithLimit->apiCallMade('/test2', 'GET', 1, 100.0, false, 'basic');
        $monitorWithLimit->apiCallMade('/test3', 'GET', 1, 100.0, false, 'basic');

        // Should only store last 2 events
        $analytics = $monitorWithLimit->getAnalytics();
        $this->assertLessThanOrEqual(2, $analytics['total_events']);
    }
}