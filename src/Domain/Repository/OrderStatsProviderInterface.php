<?php

namespace App\Domain\Repository;

interface OrderStatsProviderInterface
{
    /**
     * @return array{items: array<array{period: string, orderCount: int, totalAmount: float}>, total: int}
     */
    public function getStats(string $groupBy, int $page, int $limit): array;
}
