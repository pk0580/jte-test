<?php

namespace App\Application\Dto;

readonly class OrderStatsDto
{
    /**
     * @param OrderStatsItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $totalItems,
        public int $page,
        public int $limit,
        public int $totalPages,
    ) {}
}
