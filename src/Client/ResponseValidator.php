<?php

namespace Convertain\CoinMarketCap\Client;

use Psr\Http\Message\ResponseInterface;
use Convertain\CoinMarketCap\Exceptions\InvalidResponseException;
use Convertain\CoinMarketCap\Exceptions\ApiAuthenticationException;
use Convertain\CoinMarketCap\Exceptions\RateLimitExceededException;
use Convertain\CoinMarketCap\Exceptions\CreditLimitExceededException;

/**
 * Response Validator for CoinMarketCap API responses
 */
class ResponseValidator
{
    /**
     * Validate API response and throw appropriate exceptions
     */
    public function validate(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        // Try to decode JSON response
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException(
                "Invalid JSON response: " . json_last_error_msg(),
                $statusCode
            );
        }
        
        // Check for HTTP errors first
        $this->checkHttpErrors($statusCode, $data);
        
        // Check for API-specific errors
        $this->checkApiErrors($data, $statusCode);
        
        // Validate response structure
        $this->validateResponseStructure($data);
        
        return $data;
    }
    
    /**
     * Check for HTTP status code errors
     */
    protected function checkHttpErrors(int $statusCode, ?array $data): void
    {
        $errorMessage = $data['status']['error_message'] ?? 'Unknown error';
        $apiCode = $data['status']['error_code'] ?? null;
        
        switch ($statusCode) {
            case 400:
                throw new InvalidResponseException(
                    "Bad Request: {$errorMessage}",
                    $statusCode,
                    null,
                    $apiCode,
                    $errorMessage
                );
                
            case 401:
                throw new ApiAuthenticationException(
                    "Unauthorized: {$errorMessage}",
                    $statusCode
                );
                
            case 402:
                throw new CreditLimitExceededException(
                    "Payment Required: {$errorMessage}",
                    $statusCode
                );
                
            case 403:
                throw new ApiAuthenticationException(
                    "Forbidden: {$errorMessage}",
                    $statusCode
                );
                
            case 429:
                throw new RateLimitExceededException(
                    "Too Many Requests: {$errorMessage}",
                    $statusCode
                );
                
            case 500:
            case 502:
            case 503:
            case 504:
                throw new InvalidResponseException(
                    "Server Error ({$statusCode}): {$errorMessage}",
                    $statusCode,
                    null,
                    $apiCode,
                    $errorMessage
                );
        }
        
        if ($statusCode >= 400) {
            throw new InvalidResponseException(
                "HTTP Error ({$statusCode}): {$errorMessage}",
                $statusCode,
                null,
                $apiCode,
                $errorMessage
            );
        }
    }
    
    /**
     * Check for API-specific errors in response data
     */
    protected function checkApiErrors(?array $data, int $statusCode): void
    {
        if (!$data) {
            throw new InvalidResponseException("Empty response data", $statusCode);
        }
        
        // Check status field
        if (!isset($data['status'])) {
            throw new InvalidResponseException("Response missing status field", $statusCode);
        }
        
        $status = $data['status'];
        
        // Check error code
        if (isset($status['error_code']) && $status['error_code'] !== 0) {
            $errorCode = $status['error_code'];
            $errorMessage = $status['error_message'] ?? 'Unknown API error';
            
            // Map specific error codes to appropriate exceptions
            switch ($errorCode) {
                case 1001: // API key missing
                case 1002: // API key invalid
                    throw new ApiAuthenticationException($errorMessage, $statusCode);
                    
                case 1003: // API key plan rate limit exceeded
                case 1004: // API key daily rate limit exceeded
                case 1005: // API key monthly rate limit exceeded
                    throw new RateLimitExceededException($errorMessage, $statusCode);
                    
                case 1006: // API key monthly credit limit exceeded
                    throw new CreditLimitExceededException($errorMessage, $statusCode);
                    
                default:
                    throw new InvalidResponseException(
                        "API Error ({$errorCode}): {$errorMessage}",
                        $statusCode,
                        null,
                        $errorCode,
                        $errorMessage
                    );
            }
        }
    }
    
    /**
     * Validate basic response structure
     */
    protected function validateResponseStructure(array $data): void
    {
        // Check for required status field
        if (!isset($data['status'])) {
            throw new InvalidResponseException("Response missing required 'status' field");
        }
        
        $status = $data['status'];
        
        // Validate status structure
        if (!isset($status['timestamp'])) {
            throw new InvalidResponseException("Response status missing 'timestamp' field");
        }
        
        if (!isset($status['error_code'])) {
            throw new InvalidResponseException("Response status missing 'error_code' field");
        }
        
        // For successful responses, check credit usage info
        if ($status['error_code'] === 0) {
            if (!isset($status['credit_count'])) {
                throw new InvalidResponseException("Response status missing 'credit_count' field");
            }
        }
    }
    
    /**
     * Extract credit usage information from response
     */
    public function extractCreditUsage(array $data): int
    {
        if (!isset($data['status']['credit_count'])) {
            return 1; // Default fallback
        }
        
        return (int) $data['status']['credit_count'];
    }
    
    /**
     * Check if response indicates success
     */
    public function isSuccessResponse(array $data): bool
    {
        return isset($data['status']['error_code']) && $data['status']['error_code'] === 0;
    }
    
    /**
     * Extract data payload from response
     */
    public function extractData(array $response): array
    {
        if (!isset($response['data'])) {
            return [];
        }
        
        return $response['data'];
    }
    
    /**
     * Extract pagination info from response
     */
    public function extractPaginationInfo(array $response): ?array
    {
        if (!isset($response['status']['pagination'])) {
            return null;
        }
        
        return $response['status']['pagination'];
    }
}