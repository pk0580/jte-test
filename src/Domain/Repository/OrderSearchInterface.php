<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Order;

interface OrderSearchInterface
{
    /**
     * @param string $query
     * @param int $page
     * @param int $limit
     * @return Order[]
     */
    public function search(string $query, int $page = 1, int $limit = 10): array;

    /**
     * @param Order $order
     */
    public function index(Order $order): void;

    /**
     * @param int $orderId
     */
    public function delete(int $orderId): void;

    public function recreateIndex(): void;

    /**
     * @param array $documents Each doc: ['id' => int, 'number' => string, ...]
     */
    public function bulkIndexRaw(array $documents): void;
}
