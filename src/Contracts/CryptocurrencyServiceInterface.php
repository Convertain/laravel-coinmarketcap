<?php

declare(strict_types=1);

namespace Convertain\CoinMarketCap\Contracts;

/**
 * Interface for cryptocurrency service operations.
 */
interface CryptocurrencyServiceInterface
{
    /**
     * Get cryptocurrency map data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getMap(array $parameters = []): array;

    /**
     * Get cryptocurrency info.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getInfo(array $parameters = []): array;

    /**
     * Get latest cryptocurrency listings.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getListingsLatest(array $parameters = []): array;

    /**
     * Get historical cryptocurrency listings.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getListingsHistorical(array $parameters = []): array;

    /**
     * Get latest cryptocurrency quotes.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getQuotesLatest(array $parameters = []): array;

    /**
     * Get historical cryptocurrency quotes.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getQuotesHistorical(array $parameters = []): array;

    /**
     * Get latest market pairs data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getMarketPairsLatest(array $parameters = []): array;

    /**
     * Get latest OHLCV data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getOhlcvLatest(array $parameters = []): array;

    /**
     * Get historical OHLCV data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getOhlcvHistorical(array $parameters = []): array;

    /**
     * Get latest trending cryptocurrencies.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingLatest(array $parameters = []): array;

    /**
     * Get most visited cryptocurrencies.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingMostVisited(array $parameters = []): array;

    /**
     * Get gainers and losers.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getTrendingGainersLosers(array $parameters = []): array;

    /**
     * Get cryptocurrency category data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getCategory(array $parameters = []): array;

    /**
     * Get cryptocurrency airdrop data.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function getAirdrop(array $parameters = []): array;
}