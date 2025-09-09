<?php

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;
use Convertain\CoinMarketCap\Services\ExchangeService;
use Convertain\CoinMarketCap\Services\GlobalMetricsService;
use Convertain\CoinMarketCap\Services\FiatService;

/**
 * CoinMarketCap Data Provider
 *
 * Main provider class that orchestrates access to CoinMarketCap Pro API
 * services including exchange, global metrics, and fiat currency data.
 */
class CoinMarketCapProvider
{
    private CoinMarketCapClient $client;
    private ?ExchangeService $exchangeService = null;
    private ?GlobalMetricsService $globalMetricsService = null;
    private ?FiatService $fiatService = null;
    
    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get exchange service instance
     */
    public function exchanges(): ExchangeService
    {
        if ($this->exchangeService === null) {
            $this->exchangeService = new ExchangeService($this->client);
        }
        
        return $this->exchangeService;
    }
    
    /**
     * Get global metrics service instance
     */
    public function globalMetrics(): GlobalMetricsService
    {
        if ($this->globalMetricsService === null) {
            $this->globalMetricsService = new GlobalMetricsService($this->client);
        }
        
        return $this->globalMetricsService;
    }
    
    /**
     * Get fiat service instance
     */
    public function fiat(): FiatService
    {
        if ($this->fiatService === null) {
            $this->fiatService = new FiatService($this->client);
        }
        
        return $this->fiatService;
    }
    
    /**
     * Get the underlying API client
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }
}