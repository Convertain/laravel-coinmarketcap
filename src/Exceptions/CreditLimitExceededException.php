<?php

namespace Convertain\CoinMarketCap\Exceptions;

/**
 * Exception thrown when credit limit is exceeded
 */
class CreditLimitExceededException extends CoinMarketCapException
{
    /**
     * Create a new credit limit exception
     */
    public function __construct(string $message = "Credit limit exceeded", int $code = 402, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}