<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Client;

/**
 * CoinMarketCap API client (placeholder implementation).
 * 
 * This is a basic implementation to satisfy service provider requirements.
 * Full implementation would be part of TASK006.
 */
class CoinMarketCapClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get client configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}