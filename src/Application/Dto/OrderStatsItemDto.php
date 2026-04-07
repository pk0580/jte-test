<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class OrderStatsItemDto
{
    public function __construct(
        public string $period,
        public int $orderCount,
        public float $totalAmount,
    ) {
    }
}
