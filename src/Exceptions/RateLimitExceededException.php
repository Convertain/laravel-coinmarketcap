<?php

namespace Convertain\CoinMarketCap\Exceptions;

/**
 * Exception thrown when API rate limit is exceeded
 */
class RateLimitExceededException extends CoinMarketCapException
{
    /**
     * Create a new rate limit exception
     */
    public function __construct(string $message = "API rate limit exceeded", int $code = 429, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}