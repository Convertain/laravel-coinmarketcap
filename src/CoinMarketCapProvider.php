<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap;

use Convertain\CoinMarketCap\Client\CoinMarketCapClient;

/**
 * CoinMarketCap data provider (placeholder implementation).
 * 
 * This is a basic implementation to satisfy service provider requirements.
 * Full implementation would be part of TASK006.
 */
class CoinMarketCapProvider
{
    private CoinMarketCapClient $client;

    public function __construct(CoinMarketCapClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the client instance.
     *
     * @return CoinMarketCapClient
     */
    public function getClient(): CoinMarketCapClient
    {
        return $this->client;
    }
}