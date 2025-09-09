<?php

namespace Convertain\CoinMarketCap\Exceptions;

/**
 * Exception thrown when network request fails
 */
class NetworkException extends CoinMarketCapException
{
    /**
     * Create a new network exception
     */
    public function __construct(string $message = "Network request failed", int $code = 500, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}