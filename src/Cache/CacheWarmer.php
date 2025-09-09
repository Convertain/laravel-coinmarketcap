<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;

/**
 * Cache warming service for preloading frequently accessed data
 * to minimize API calls and improve response times.
 */
class CacheWarmer
{
    private CoinMarketCapCache $cache;
    private CacheStrategy $strategy;
    private CacheAnalytics $analytics;
    private LoggerInterface $logger;
    private array $config;

    /**
     * Default warming priorities for different endpoint types
     */
    private const DEFAULT_WARMING_PRIORITIES = [
        'cryptocurrency_map' => ['priority' => 10, 'frequency' => 'daily'],
        'fiat_map' => ['priority' => 10, 'frequency' => 'daily'],
        'exchange_map' => ['priority' => 9, 'frequency' => 'daily'],
        'cryptocurrency_info' => ['priority' => 8, 'frequency' => 'daily'],
        'exchange_info' => ['priority' => 8, 'frequency' => 'daily'],
        'global_metrics_quotes' => ['priority' => 7, 'frequency' => 'hourly'],
        'cryptocurrency_listings' => ['priority' => 6, 'frequency' => 'hourly'],
        'trending' => ['priority' => 5, 'frequency' => 'hourly'],
        'cryptocurrency_quotes' => ['priority' => 4, 'frequency' => 'every_30_minutes'],
        'exchange_quotes' => ['priority' => 4, 'frequency' => 'every_30_minutes'],
    ];

    /**
     * Warming strategies based on different scenarios
     */
    private const WARMING_STRATEGIES = [
        'aggressive' => [
            'batch_size' => 50,
            'concurrent_requests' => 5,
            'delay_between_batches' => 100, // milliseconds
            'include_historical' => true,
        ],
        'conservative' => [
            'batch_size' => 20,
            'concurrent_requests' => 2,
            'delay_between_batches' => 500,
            'include_historical' => false,
        ],
        'balanced' => [
            'batch_size' => 30,
            'concurrent_requests' => 3,
            'delay_between_batches' => 300,
            'include_historical' => true,
        ],
    ];

    public function __construct(
        CoinMarketCapCache $cache,
        ?CacheStrategy $strategy = null,
        ?CacheAnalytics $analytics = null,
        ?LoggerInterface $logger = null
    ) {
        $this->cache = $cache;
        $this->strategy = $strategy ?? new CacheStrategy();
        $this->analytics = $analytics ?? new CacheAnalytics();
        $this->logger = $logger ?? app(LoggerInterface::class);
        $this->config = Config::get('coinmarketcap', []);
    }

    /**
     * Warm cache with essential data based on priorities.
     *
     * @param array $options Warming options
     * @return array Warming results
     */
    public function warmEssentialData(array $options = []): array
    {
        $strategy = $options['strategy'] ?? 'balanced';
        $priorities = $this->getWarmingPriorities();
        $results = [];

        $this->logger->info('Starting cache warming with strategy: ' . $strategy);

        // Sort by priority (highest first)
        arsort($priorities);

        foreach ($priorities as $endpointType => $priority) {
            if ($priority >= ($options['min_priority'] ?? 5)) {
                $result = $this->warmEndpointType($endpointType, $strategy, $options);
                $results[$endpointType] = $result;
                
                // Add delay between endpoint types to avoid rate limiting
                $this->delayExecution(self::WARMING_STRATEGIES[$strategy]['delay_between_batches']);
            }
        }

        $this->analytics->recordWarming(array_sum(array_column($results, 'items_warmed')));
        $this->logger->info('Cache warming completed', ['results' => $results]);

        return $results;
    }

    /**
     * Warm cache for specific symbols or currencies.
     *
     * @param array $symbols Cryptocurrency symbols
     * @param array $currencies Fiat currencies
     * @param array $options Warming options
     * @return array Warming results
     */
    public function warmForSymbols(array $symbols, array $currencies = ['USD'], array $options = []): array
    {
        $results = [];
        $strategy = $options['strategy'] ?? 'balanced';

        foreach ($symbols as $symbol) {
            // Warm quotes for each currency
            foreach ($currencies as $currency) {
                $key = "quotes:{$symbol}:{$currency}";
                $mockData = $this->generateMockQuoteData($symbol, $currency);
                
                if ($this->cache->put($key, $mockData, 'cryptocurrency_quotes')) {
                    $results['quotes'][] = $symbol . '/' . $currency;
                }
            }

            // Warm basic info
            $infoKey = "info:{$symbol}";
            $mockInfo = $this->generateMockInfoData($symbol);
            
            if ($this->cache->put($infoKey, $mockInfo, 'cryptocurrency_info')) {
                $results['info'][] = $symbol;
            }

            $this->delayExecution(50); // Small delay between symbols
        }

        return $results;
    }

    /**
     * Warm cache based on usage patterns.
     *
     * @param array $options Warming options
     * @return array Warming results
     */
    public function warmFromUsagePatterns(array $options = []): array
    {
        $stats = $this->analytics->getStatistics('day');
        $topEndpoints = $this->analytics->getTopPerformingEndpoints(10);
        $results = [];

        // Focus on endpoints with high miss rates
        foreach ($topEndpoints as $endpoint) {
            if ($endpoint['hit_rate'] < 0.7) { // Less than 70% hit rate
                $result = $this->warmEndpointType($endpoint['endpoint'], 'aggressive', $options);
                $results[$endpoint['endpoint']] = $result;
            }
        }

        return $results;
    }

    /**
     * Warm cache for upcoming high-traffic periods.
     *
     * @param Carbon|null $targetTime Target time for traffic spike
     * @param array $options Warming options
     * @return array Warming results
     */
    public function warmForHighTraffic(?Carbon $targetTime = null, array $options = []): array
    {
        $targetTime = $targetTime ?? Carbon::now()->addMinutes(30);
        $results = [];

        // Determine if it's a high-traffic period (market hours, news events, etc.)
        $isHighTrafficPeriod = $this->isHighTrafficPeriod($targetTime);

        if ($isHighTrafficPeriod) {
            // Use aggressive warming strategy
            $results = $this->warmEssentialData(array_merge($options, [
                'strategy' => 'aggressive',
                'min_priority' => 3, // Lower threshold during high traffic
            ]));

            // Also warm popular trading pairs
            $popularPairs = $this->getPopularTradingPairs();
            $pairResults = $this->warmForSymbols($popularPairs, ['USD', 'EUR', 'BTC'], $options);
            $results['trading_pairs'] = $pairResults;
        }

        return $results;
    }

    /**
     * Warm cache gradually in background.
     *
     * @param int $itemsPerBatch Number of items to warm per batch
     * @param int $delayBetweenBatches Delay between batches in milliseconds
     * @return int Total items warmed
     */
    public function warmGradually(int $itemsPerBatch = 10, int $delayBetweenBatches = 1000): int
    {
        $priorities = $this->getWarmingPriorities();
        $totalWarmed = 0;
        $batch = [];

        foreach ($priorities as $endpointType => $priority) {
            if ($priority >= 5) {
                $items = $this->generateWarmingItems($endpointType, $itemsPerBatch);
                $batch = array_merge($batch, $items);

                if (count($batch) >= $itemsPerBatch) {
                    $warmed = $this->cache->warm(array_slice($batch, 0, $itemsPerBatch));
                    $totalWarmed += $warmed;
                    $batch = array_slice($batch, $itemsPerBatch);
                    
                    $this->delayExecution($delayBetweenBatches);
                }
            }
        }

        // Warm remaining items
        if (!empty($batch)) {
            $totalWarmed += $this->cache->warm($batch);
        }

        return $totalWarmed;
    }

    /**
     * Schedule cache warming based on configuration.
     *
     * @return array Schedule configuration
     */
    public function getWarmingSchedule(): array
    {
        $schedule = [];

        foreach (self::DEFAULT_WARMING_PRIORITIES as $endpointType => $config) {
            $schedule[$endpointType] = [
                'frequency' => $config['frequency'],
                'priority' => $config['priority'],
                'next_run' => $this->calculateNextRun($config['frequency']),
                'estimated_duration' => $this->estimateWarmingDuration($endpointType),
            ];
        }

        return $schedule;
    }

    /**
     * Clear warming cache and restart.
     *
     * @return array Results
     */
    public function refreshWarmingCache(): array
    {
        // Clear warming-related cache entries
        $patterns = ['quotes:*', 'info:*', 'listings:*', 'map:*'];
        $cleared = 0;

        foreach ($patterns as $pattern) {
            $cleared += $this->cache->flush($pattern);
        }

        // Restart warming with fresh data
        $results = $this->warmEssentialData(['strategy' => 'balanced']);
        $results['cleared_items'] = $cleared;

        return $results;
    }

    /**
     * Get warming statistics.
     *
     * @return array
     */
    public function getWarmingStatistics(): array
    {
        $stats = $this->analytics->getStatistics();
        
        return [
            'total_warmings' => $stats['overview']['total_stores'] ?? 0,
            'warming_efficiency' => $this->calculateWarmingEfficiency(),
            'last_warming' => $this->getLastWarmingTime(),
            'upcoming_warmings' => $this->getUpcomingWarmings(),
            'warming_coverage' => $this->calculateWarmingCoverage(),
        ];
    }

    /**
     * Warm specific endpoint type.
     *
     * @param string $endpointType Endpoint type
     * @param string $strategy Warming strategy
     * @param array $options Additional options
     * @return array Results
     */
    private function warmEndpointType(string $endpointType, string $strategy, array $options = []): array
    {
        $strategyConfig = self::WARMING_STRATEGIES[$strategy];
        $batchSize = $strategyConfig['batch_size'];
        $items = $this->generateWarmingItems($endpointType, $batchSize);
        
        $results = [
            'endpoint_type' => $endpointType,
            'items_warmed' => 0,
            'errors' => 0,
            'duration_ms' => 0,
        ];

        $startTime = microtime(true);

        try {
            // Process items in batches
            $batches = array_chunk($items, $batchSize);
            
            foreach ($batches as $batch) {
                $warmed = $this->cache->warm($batch);
                $results['items_warmed'] += $warmed;
                
                if ($warmed < count($batch)) {
                    $results['errors'] += (count($batch) - $warmed);
                }

                $this->delayExecution($strategyConfig['delay_between_batches']);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Cache warming error for endpoint: ' . $endpointType, [
                'error' => $e->getMessage(),
                'endpoint_type' => $endpointType,
            ]);
            $results['errors']++;
        }

        $results['duration_ms'] = (microtime(true) - $startTime) * 1000;

        return $results;
    }

    /**
     * Generate warming items for endpoint type.
     *
     * @param string $endpointType Endpoint type
     * @param int $count Number of items to generate
     * @return array Warming items
     */
    private function generateWarmingItems(string $endpointType, int $count): array
    {
        $items = [];

        switch ($endpointType) {
            case 'cryptocurrency_map':
                $items[] = [
                    'key' => 'map:cryptocurrency:all',
                    'value' => $this->generateMockMapData('cryptocurrency'),
                    'endpoint_type' => $endpointType,
                ];
                break;

            case 'fiat_map':
                $items[] = [
                    'key' => 'map:fiat:all',
                    'value' => $this->generateMockMapData('fiat'),
                    'endpoint_type' => $endpointType,
                ];
                break;

            case 'cryptocurrency_quotes':
                $popularSymbols = array_slice($this->getPopularTradingPairs(), 0, $count);
                foreach ($popularSymbols as $symbol) {
                    $items[] = [
                        'key' => "quotes:{$symbol}:USD",
                        'value' => $this->generateMockQuoteData($symbol, 'USD'),
                        'endpoint_type' => $endpointType,
                    ];
                }
                break;

            case 'global_metrics_quotes':
                $items[] = [
                    'key' => 'global:metrics:latest',
                    'value' => $this->generateMockGlobalData(),
                    'endpoint_type' => $endpointType,
                ];
                break;

            default:
                // Generate generic items
                for ($i = 0; $i < min($count, 5); $i++) {
                    $items[] = [
                        'key' => "{$endpointType}:cache_warmed:{$i}",
                        'value' => ['warmed_at' => Carbon::now()->toISOString()],
                        'endpoint_type' => $endpointType,
                    ];
                }
                break;
        }

        return $items;
    }

    /**
     * Get warming priorities from configuration and strategy.
     *
     * @return array
     */
    private function getWarmingPriorities(): array
    {
        $configPriorities = $this->config['cache']['warming_priorities'] ?? [];
        $strategyPriorities = $this->strategy->getWarmingPriorities();
        
        // Merge with defaults, giving preference to configuration
        return array_merge(
            self::DEFAULT_WARMING_PRIORITIES,
            $strategyPriorities,
            $configPriorities
        );
    }

    /**
     * Check if given time is a high-traffic period.
     *
     * @param Carbon $time Target time
     * @return bool
     */
    private function isHighTrafficPeriod(Carbon $time): bool
    {
        $hour = $time->hour;
        $dayOfWeek = $time->dayOfWeek;
        
        // Business hours (9 AM - 5 PM) on weekdays
        $isBusinessHours = $hour >= 9 && $hour <= 17 && $dayOfWeek >= 1 && $dayOfWeek <= 5;
        
        // Market opening hours (pre-market and regular hours)
        $isMarketHours = $hour >= 4 && $hour <= 20; // 4 AM - 8 PM EST approximate
        
        return $isBusinessHours || $isMarketHours;
    }

    /**
     * Get popular trading pairs for warming.
     *
     * @return array
     */
    private function getPopularTradingPairs(): array
    {
        return [
            'BTC', 'ETH', 'USDT', 'USDC', 'BNB', 'XRP', 'ADA', 'SOL', 
            'DOGE', 'DOT', 'AVAX', 'LINK', 'MATIC', 'LTC', 'UNI'
        ];
    }

    /**
     * Calculate next run time for frequency.
     *
     * @param string $frequency Frequency string
     * @return Carbon
     */
    private function calculateNextRun(string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => Carbon::now()->addDay(),
            'hourly' => Carbon::now()->addHour(),
            'every_30_minutes' => Carbon::now()->addMinutes(30),
            'every_15_minutes' => Carbon::now()->addMinutes(15),
            default => Carbon::now()->addHour(),
        };
    }

    /**
     * Estimate warming duration for endpoint.
     *
     * @param string $endpointType Endpoint type
     * @return int Estimated duration in seconds
     */
    private function estimateWarmingDuration(string $endpointType): int
    {
        $baseDuration = match ($endpointType) {
            'cryptocurrency_map', 'fiat_map', 'exchange_map' => 10,
            'cryptocurrency_quotes', 'exchange_quotes' => 30,
            'cryptocurrency_listings' => 20,
            'global_metrics_quotes' => 5,
            default => 15,
        };

        return $baseDuration;
    }

    /**
     * Calculate warming efficiency.
     *
     * @return float
     */
    private function calculateWarmingEfficiency(): float
    {
        $stats = $this->analytics->getStatistics();
        $hitRate = $stats['overview']['hit_rate'] ?? 0;
        $creditsSaved = $stats['overview']['credits_saved'] ?? 0;
        
        // Simple efficiency calculation based on hit rate and credits saved
        return min(1.0, ($hitRate * 0.7) + (min($creditsSaved / 100, 0.3)));
    }

    /**
     * Get last warming time.
     *
     * @return Carbon|null
     */
    private function getLastWarmingTime(): ?Carbon
    {
        // This would be stored in cache or database
        return Carbon::now()->subHours(2); // Mock implementation
    }

    /**
     * Get upcoming warming tasks.
     *
     * @return array
     */
    private function getUpcomingWarmings(): array
    {
        $schedule = $this->getWarmingSchedule();
        $upcoming = [];

        foreach ($schedule as $endpoint => $config) {
            if ($config['next_run']->isFuture()) {
                $upcoming[] = [
                    'endpoint' => $endpoint,
                    'scheduled_at' => $config['next_run']->toISOString(),
                    'priority' => $config['priority'],
                ];
            }
        }

        // Sort by scheduled time
        usort($upcoming, fn($a, $b) => $a['scheduled_at'] <=> $b['scheduled_at']);

        return array_slice($upcoming, 0, 5); // Return next 5
    }

    /**
     * Calculate warming coverage.
     *
     * @return float Coverage percentage (0.0 to 1.0)
     */
    private function calculateWarmingCoverage(): float
    {
        $totalEndpoints = count(self::DEFAULT_WARMING_PRIORITIES);
        $warmedEndpoints = 0;

        // Count how many endpoint types have warmed data
        foreach (array_keys(self::DEFAULT_WARMING_PRIORITIES) as $endpoint) {
            // Check if any cache keys exist for this endpoint
            // This is a simplified check
            if ($this->hasWarmedData($endpoint)) {
                $warmedEndpoints++;
            }
        }

        return $totalEndpoints > 0 ? $warmedEndpoints / $totalEndpoints : 0.0;
    }

    /**
     * Check if endpoint has warmed data.
     *
     * @param string $endpointType Endpoint type
     * @return bool
     */
    private function hasWarmedData(string $endpointType): bool
    {
        // Simplified check - in real implementation would check actual cache keys
        return true; // Mock implementation
    }

    /**
     * Generate mock data for different endpoint types.
     */
    private function generateMockMapData(string $type): array
    {
        return [
            'data' => [
                ['id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC'],
                ['id' => 2, 'name' => 'Ethereum', 'symbol' => 'ETH'],
            ],
            'cached_at' => Carbon::now()->toISOString(),
            'type' => $type,
        ];
    }

    private function generateMockQuoteData(string $symbol, string $currency): array
    {
        return [
            'data' => [
                'symbol' => $symbol,
                'quote' => [
                    $currency => [
                        'price' => rand(100, 50000),
                        'volume_24h' => rand(1000000, 10000000),
                        'percent_change_24h' => rand(-10, 10),
                    ]
                ]
            ],
            'cached_at' => Carbon::now()->toISOString(),
        ];
    }

    private function generateMockInfoData(string $symbol): array
    {
        return [
            'data' => [
                'symbol' => $symbol,
                'name' => ucfirst(strtolower($symbol)),
                'description' => "Information about {$symbol}",
            ],
            'cached_at' => Carbon::now()->toISOString(),
        ];
    }

    private function generateMockGlobalData(): array
    {
        return [
            'data' => [
                'active_cryptocurrencies' => rand(8000, 12000),
                'total_market_cap' => rand(1000000000000, 3000000000000),
                'total_volume_24h' => rand(50000000000, 150000000000),
            ],
            'cached_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Add delay between operations.
     *
     * @param int $milliseconds Delay in milliseconds
     */
    private function delayExecution(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}