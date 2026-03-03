<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Order;

interface OrderSearchInterface
{
    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @param int|null $lastId
     * @return SearchResult<Order>
     */
    public function search(string $query, int $page = 1, int $limit = 10, ?int $lastId = null, ?int $status = null): SearchResult;

    /**
     * @param Order $order
     */
    public function index(Order $order): void;

    /**
     * @param int $orderId
     */
    public function delete(int $orderId): void;

    public function recreateIndex(): void;

    public function createIndex(string $index): void;

    public function bulkIndexRawToIndex(string $index, array $rows): void;

    public function swapIndex(string $tmp, string $main): void;

    /**
     * @return bool
     */
    public function ping(): bool;
}
