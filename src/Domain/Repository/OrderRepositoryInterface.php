<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    public function save(Order $order): void;
    public function remove(Order $order): void;

    /**
     * @return array{items: array<array{period: string, orderCount: int, totalAmount: float}>, total: int}
     */
    public function getStats(string $groupBy, int $page, int $limit): array;
}
