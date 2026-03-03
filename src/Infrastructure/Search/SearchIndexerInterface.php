<?php

namespace App\Infrastructure\Search;

interface SearchIndexerInterface
{
    public function recreateIndex(): void;

    public function createIndex(string $index): void;

    /**
     * @param string $index
     * @param array<int, array<string, mixed>> $rows
     */
    public function bulkIndexRawToIndex(string $index, array $rows): void;

    public function swapIndex(string $tmp, string $main): void;

    public function ping(): bool;
}
