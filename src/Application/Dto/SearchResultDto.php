<?php

namespace App\Application\Dto;

readonly class SearchResultDto
{
    /**
     * @param mixed[] $items
     * @param int $total
     */
    public function __construct(
        public array $items,
        public int $total
    ) {}
}
