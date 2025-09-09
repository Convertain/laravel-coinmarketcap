# CoinMarketCap Test Suite Documentation

This directory contains comprehensive tests for the Laravel CoinMarketCap package, designed to achieve 90%+ code coverage with sophisticated API mocking and credit consumption simulation.

## Test Structure

```
tests/
├── Unit/                           # Isolated unit tests for individual components
│   ├── Services/                   # Service layer testing
│   │   ├── CryptocurrencyServiceTest.php
│   │   ├── ExchangeServiceTest.php
│   │   └── GlobalMetricsServiceTest.php
│   ├── Transformers/               # Data transformation testing
│   │   ├── CryptocurrencyTransformerTest.php
│   │   ├── ExchangeTransformerTest.php
│   │   └── GlobalMetricsTransformerTest.php
│   ├── Credit/                     # Credit management testing
│   │   ├── CreditManagerTest.php
│   │   └── CreditOptimizerTest.php
│   ├── Cache/                      # Cache behavior testing
│   │   └── CoinMarketCapCacheTest.php
│   └── Client/                     # HTTP client testing
│       └── CoinMarketCapClientTest.php
├── Integration/                    # Component integration testing
│   ├── CoinMarketCapProviderTest.php
│   ├── ServiceIntegrationTest.php
│   └── CacheIntegrationTest.php
├── Feature/                        # End-to-end feature testing
│   ├── ApiEndpointsTest.php
│   ├── CreditOptimizationTest.php
│   └── ProviderComplianceTest.php
└── TestCase.php                    # Base test class with shared setup
```

## Test Categories

### Unit Tests (10 Classes)

**Services Testing:**
- Tests all API endpoint methods (getListings, getQuotes, getInfo, getMap)
- Parameter validation and request formatting
- Response parsing and error handling
- Credit consumption tracking integration

**Transformers Testing:**
- Data format standardization validation
- Null value handling and edge cases
- Multiple item transformation consistency
- Currency conversion scenarios

**Credit Management Testing:**
- Usage tracking accuracy and limit enforcement
- Warning threshold system validation
- Optimization strategy effectiveness
- Plan limit compliance across different subscription tiers

**Cache Testing:**
- TTL configuration validation for different data types
- Cache hit/miss scenarios and memory efficiency
- Laravel cache integration compliance
- Cache warming and invalidation strategies

**Client Testing:**
- HTTP request formatting and authentication
- Configuration validation and error handling
- Response parsing and status code handling
- Network error simulation and retry logic

### Integration Tests (3 Classes)

**Provider Integration:**
- Laravel service container registration
- Configuration propagation throughout the stack
- Service provider lifecycle compliance

**Service Integration:**
- Cross-service data flow validation
- Credit manager and cache integration
- Error propagation and handling consistency

**Cache Integration:**
- Laravel cache system integration
- Multi-store configuration support
- Performance optimization validation

### Feature Tests (3 Classes)

**API Endpoints Testing:**
- Complete workflow testing from client to transformer
- Multi-endpoint orchestration scenarios
- Parameter optimization and response consistency
- Error handling across the entire stack

**Credit Optimization Testing:**
- Real-world usage scenario simulation
- Optimization strategy effectiveness validation
- Warning system integration testing
- Plan limit enforcement in production scenarios

**Provider Compliance Testing:**
- Interface contract compliance validation
- Performance benchmarking and memory usage
- Configuration validation across scenarios
- Thread safety and consistency verification

## Mock System Architecture

### API Response Mocking

The test suite includes sophisticated mocking for CoinMarketCap API responses:

```php
// Realistic response structures with complete data models
$mockResponse = new Response(200, [], json_encode([
    'status' => ['error_code' => 0, 'error_message' => ''],
    'data' => [/* Realistic CoinMarketCap data structures */]
]));
```

### Credit Consumption Simulation

Tests simulate various credit usage scenarios:

- **Low Usage (0-50%)**: Normal operation validation
- **Medium Usage (50-80%)**: Optimization trigger testing
- **High Usage (80-100%)**: Warning system validation
- **Quota Exhaustion**: Limit enforcement testing

### Error Scenario Testing

Comprehensive error handling validation:

- Network timeouts and connection failures
- API rate limiting and quota exceeded responses
- Invalid parameter validation and sanitization
- Malformed response handling and recovery

## Test Execution

### Basic Test Run

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suites
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Integration
./vendor/bin/phpunit --testsuite=Feature
```

### Coverage Analysis

```bash
# Generate HTML coverage report
./vendor/bin/phpunit --coverage-html coverage/

# Generate text coverage summary
./vendor/bin/phpunit --coverage-text

# Generate Clover XML for CI/CD
./vendor/bin/phpunit --coverage-clover coverage.xml
```

### Static Analysis

```bash
# Run PHPStan Level 10 analysis
./vendor/bin/phpstan analyze

# Run Laravel Pint code formatting
./vendor/bin/pint

# Validate test structure
php test-validation.php
```

## Coverage Goals

### Target Coverage: 90%+

**High Priority Coverage Areas:**
- All service methods and API endpoint handlers
- Credit tracking and optimization algorithms
- Cache strategy implementations
- Error handling and edge cases

**Medium Priority Coverage Areas:**
- Configuration validation and setup
- Data transformation edge cases
- Integration points and service orchestration

**Documentation Coverage:**
- All public methods have comprehensive test coverage
- Edge cases and error scenarios are tested
- Performance characteristics are validated

## Test Data Management

### Mock Data Generation

Tests use realistic mock data that mirrors actual CoinMarketCap API responses:

```php
// Cryptocurrency listing mock data
$mockCryptocurrency = [
    'id' => 1,
    'name' => 'Bitcoin',
    'symbol' => 'BTC',
    'quote' => ['USD' => ['price' => 45000.0, 'market_cap' => 850000000000.0]]
];
```

### Configuration Scenarios

Tests cover multiple configuration scenarios:
- Different subscription plans (Basic, Hobbyist, Startup, Standard, Professional, Enterprise)
- Various cache store configurations (Redis, Memcached, Database, File)
- Multiple API endpoint configurations and timeout scenarios

## Performance Testing

### Benchmarking

Tests include performance validation for:
- Response time limits (sub-100ms for core operations)
- Memory usage constraints (minimal memory leaks)
- Cache efficiency and hit rates
- Credit optimization effectiveness

### Load Testing Simulation

Feature tests simulate high-load scenarios:
- Concurrent API request handling
- Cache contention and performance under load
- Credit tracking accuracy under concurrent usage
- Error recovery and system stability

## Continuous Integration

### Test Pipeline Configuration

Recommended CI/CD pipeline steps:

1. **Dependency Installation**: `composer install --no-interaction`
2. **Code Formatting Check**: `./vendor/bin/pint --test`
3. **Static Analysis**: `./vendor/bin/phpstan analyze --no-progress`
4. **Unit Test Execution**: `./vendor/bin/phpunit --testsuite=Unit`
5. **Integration Test Execution**: `./vendor/bin/phpunit --testsuite=Integration`
6. **Feature Test Execution**: `./vendor/bin/phpunit --testsuite=Feature`
7. **Coverage Analysis**: `./vendor/bin/phpunit --coverage-clover coverage.xml`
8. **Coverage Validation**: Ensure 90%+ coverage maintained

### Quality Gates

- **Code Coverage**: Minimum 90% line coverage
- **Static Analysis**: PHPStan Level 10 compliance
- **Code Style**: PSR-12 compliance via Laravel Pint
- **Test Quality**: All tests must pass with meaningful assertions

## Test Maintenance

### Adding New Tests

When adding new functionality:

1. **Create Unit Tests**: Test individual methods and classes
2. **Add Integration Tests**: Test component interactions
3. **Include Feature Tests**: Test complete user workflows
4. **Update Mock Data**: Ensure realistic test scenarios
5. **Validate Coverage**: Maintain 90%+ coverage target

### Mock Data Updates

When CoinMarketCap API changes:

1. **Update Response Structures**: Reflect API schema changes
2. **Add New Fields**: Include new data fields in mocks
3. **Update Transformers**: Ensure data transformation compatibility
4. **Validate Backwards Compatibility**: Maintain existing functionality

This comprehensive test suite ensures reliable functionality, maintains high code quality, and provides confidence in the CoinMarketCap integration's robustness and performance characteristics.