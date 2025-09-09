<?php

namespace Convertain\CoinMarketCap\Exceptions;

/**
 * Exception thrown when API response validation fails
 */
class InvalidResponseException extends CoinMarketCapException
{
    /**
     * Create a new invalid response exception
     */
    public function __construct(string $message = "Invalid API response", int $code = 422, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}