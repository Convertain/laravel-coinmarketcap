<?php

namespace Convertain\CoinMarketCap\Exceptions;

/**
 * Exception thrown when API authentication fails
 */
class ApiAuthenticationException extends CoinMarketCapException
{
    /**
     * Create a new authentication exception
     */
    public function __construct(string $message = "API authentication failed", int $code = 401, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}