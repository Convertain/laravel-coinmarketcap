<?php

namespace Convertain\CoinMarketCap\Contracts;

/**
 * Interface for data transformers that normalize CoinMarketCap API responses.
 */
interface TransformerInterface
{
    /**
     * Transform raw API response data into standardized format.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return array<string, mixed> Transformed and normalized data
     */
    public function transform(array $data): array;

    /**
     * Transform a collection of items.
     *
     * @param array<int, array<string, mixed>> $items Array of raw items
     * @return array<int, array<string, mixed>> Array of transformed items
     */
    public function transformCollection(array $items): array;

    /**
     * Validate if the raw data structure is compatible with this transformer.
     *
     * @param array<string, mixed> $data Raw API response data
     * @return bool True if data is compatible
     */
    public function canTransform(array $data): bool;
}