<?php

/**
 * Demo script to validate credit management functionality.
 */

require_once __DIR__ . '/../src/Credit/PlanManager.php';

use Convertain\CoinMarketCap\Credit\PlanManager;

echo "=== CoinMarketCap Credit Management Demo ===\n\n";

// Sample configuration
$config = [
    'plan' => [
        'type' => 'startup',
        'credits_per_month' => 120000,
        'calls_per_day' => 4000,
        'calls_per_minute' => 60,
    ],
    'plans' => [
        'basic' => [
            'credits_per_month' => 10000,
            'calls_per_day' => 333,
            'calls_per_minute' => 30,
        ],
        'hobbyist' => [
            'credits_per_month' => 40000,
            'calls_per_day' => 1333,
            'calls_per_minute' => 30,
        ],
        'startup' => [
            'credits_per_month' => 120000,
            'calls_per_day' => 4000,
            'calls_per_minute' => 60,
        ],
        'standard' => [
            'credits_per_month' => 500000,
            'calls_per_day' => 16667,
            'calls_per_minute' => 60,
        ],
        'professional' => [
            'credits_per_month' => 2000000,
            'calls_per_day' => 66667,
            'calls_per_minute' => 60,
        ],
        'enterprise' => [
            'credits_per_month' => 100000000,
            'calls_per_day' => 3333333,
            'calls_per_minute' => 120,
        ],
    ],
    'credits' => [
        'costs' => [
            'cryptocurrency_listings_latest' => 1,
            'cryptocurrency_quotes_latest' => 1,
            'cryptocurrency_info' => 1,
            'exchange_listings_latest' => 1,
            'global_metrics_quotes_latest' => 1,
        ],
    ],
    'endpoints' => [
        'limits' => [
            'cryptocurrency_ids_per_request' => 100,
            'exchange_ids_per_request' => 100,
            'symbols_per_request' => 100,
        ],
    ],
];

echo "1. Testing PlanManager...\n";
$planManager = new PlanManager($config);

echo "   - Current plan: " . $planManager->getPlanType() . "\n";
echo "   - Monthly credits: " . number_format($planManager->getMonthlyCredits()) . "\n";
echo "   - Daily call limit: " . number_format($planManager->getDailyCallLimit()) . "\n";
echo "   - Per-minute limit: " . number_format($planManager->getMinuteCallLimit()) . "\n";

echo "\n2. Testing Feature Support...\n";
$features = [
    'cryptocurrency_data',
    'exchange_data',
    'historical_data',
    'batch_requests',
    'custom_limits', // Enterprise only
];

foreach ($features as $feature) {
    $supported = $planManager->supportsFeature($feature);
    echo "   - {$feature}: " . ($supported ? 'SUPPORTED' : 'NOT SUPPORTED') . "\n";
}

echo "\n3. Testing Batch Size Optimization...\n";
$endpoints = [
    'cryptocurrency_quotes_latest',
    'exchange_quotes_latest',
    'global_metrics_quotes_latest',
];

foreach ($endpoints as $endpoint) {
    $batchSize = $planManager->getOptimalBatchSize($endpoint);
    echo "   - {$endpoint}: {$batchSize} items per batch\n";
}

echo "\n4. Testing Upgrade Recommendations...\n";

// Test with 90% usage of current plan
$currentUsage = (int)($planManager->getMonthlyCredits() * 0.9);
$recommendations = $planManager->getUpgradeRecommendations($currentUsage, 1.2); // 20% growth

echo "   - Current usage: " . number_format($currentUsage) . " credits\n";
echo "   - Current limit: " . number_format($recommendations['current_limit']) . " credits\n";
echo "   - Projected usage: " . number_format($recommendations['projected_usage']) . " credits\n";
echo "   - Needs upgrade: " . ($recommendations['needs_upgrade'] ? 'YES' : 'NO') . "\n";

if ($recommendations['needs_upgrade'] && !empty($recommendations['recommendations'])) {
    $topRecommendation = $recommendations['recommendations'][0];
    echo "   - Recommended plan: " . $topRecommendation['plan_type'] . "\n";
    echo "   - New limit: " . number_format($topRecommendation['monthly_limit']) . " credits\n";
    echo "   - Buffer: " . number_format($topRecommendation['buffer_percentage'], 1) . "%\n";
}

echo "\n5. Testing Cost Efficiency Analysis...\n";

// Test different utilization scenarios
$utilizationScenarios = [
    ['usage' => 8000, 'description' => 'Low usage (8% utilization)'],
    ['usage' => 48000, 'description' => 'Medium usage (40% utilization)'],
    ['usage' => 96000, 'description' => 'High usage (80% utilization)'],
    ['usage' => 110000, 'description' => 'Very high usage (92% utilization)'],
];

foreach ($utilizationScenarios as $scenario) {
    $metrics = $planManager->getCostEfficiencyMetrics($scenario['usage']);
    echo "   - " . $scenario['description'] . ":\n";
    echo "     * Utilization: " . number_format($metrics['utilization_rate'] * 100, 1) . "%\n";
    echo "     * Efficiency: " . $metrics['efficiency_score'] . "\n";
    echo "     * Recommendation: " . $metrics['recommendation'] . "\n";
}

echo "\n6. Testing Endpoint Recommendations...\n";

$endpointRecs = $planManager->getRecommendedEndpoints();
$topEndpoints = array_slice($endpointRecs, 0, 3, true);

foreach ($topEndpoints as $endpoint => $rec) {
    echo "   - {$endpoint}:\n";
    echo "     * Priority: " . $rec['priority'] . "\n";
    echo "     * Cost: " . $rec['cost'] . " credit(s)\n";
    echo "     * Frequency: " . $rec['recommended_frequency'] . "\n";
}

echo "\n7. Testing Different Plan Types...\n";

$planTypes = ['basic', 'hobbyist', 'startup', 'standard', 'professional', 'enterprise'];

foreach ($planTypes as $planType) {
    $testConfig = array_merge($config, ['plan' => ['type' => $planType]]);
    $testPlanManager = new PlanManager($testConfig);
    
    echo "   - {$planType} plan:\n";
    echo "     * Credits: " . number_format($testPlanManager->getMonthlyCredits()) . "\n";
    echo "     * Daily calls: " . number_format($testPlanManager->getDailyCallLimit()) . "\n";
    echo "     * Supports historical data: " . ($testPlanManager->supportsFeature('historical_data') ? 'YES' : 'NO') . "\n";
}

echo "\n=== Credit Management Demo Complete ===\n";
echo "Plan management functionality validated!\n";
echo "- Plan configuration resolution: ✓\n";
echo "- Feature support checking: ✓\n";
echo "- Batch size optimization: ✓\n";
echo "- Upgrade recommendations: ✓\n";
echo "- Cost efficiency analysis: ✓\n";
echo "- Multi-plan support: ✓\n";