<?php

namespace Convertain\CoinMarketCap\Exceptions;

use Exception;

/**
 * Base exception for CoinMarketCap API errors
 */
class CoinMarketCapException extends Exception
{
    /**
     * API error code from CoinMarketCap
     */
    protected ?int $apiCode = null;
    
    /**
     * API error message from CoinMarketCap
     */
    protected ?string $apiMessage = null;
    
    /**
     * Create a new exception instance
     */
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, ?int $apiCode = null, ?string $apiMessage = null)
    {
        parent::__construct($message, $code, $previous);
        
        $this->apiCode = $apiCode;
        $this->apiMessage = $apiMessage;
    }
    
    /**
     * Get the API error code
     */
    public function getApiCode(): ?int
    {
        return $this->apiCode;
    }
    
    /**
     * Get the API error message
     */
    public function getApiMessage(): ?string
    {
        return $this->apiMessage;
    }
}