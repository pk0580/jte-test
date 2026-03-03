<?php

namespace App\Domain\Repository;

/**
 * @template T
 */
readonly class SearchResult
{
    /**
     * @param T[] $items
     * @param int $total
     */
    public function __construct(
        public array $items,
        public int $total
    ) {}
}
