<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    /**
     * @param int[] $ids
     * @return Order[]
     */
    public function findByIds(array $ids): array;

    public function save(Order $order): void;
    public function remove(Order $order): void;

    /**
     * @return array{items: array<array{period: string, orderCount: int, totalAmount: float}>, total: int}
     */
    public function getStats(string $groupBy, int $page, int $limit): array;
    public function countAll(): int;
    public function findForIndexing(int $offset, int $limit): array;

    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @param int|null $status
     * @return SearchResult<Order>
     */
    public function search(string $query, int $page, int $limit, ?int $lastId = null, ?int $status = null): SearchResult;
}
