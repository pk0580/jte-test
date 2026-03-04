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
     * @param int|null $status
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

    public function ping(): bool;
}
