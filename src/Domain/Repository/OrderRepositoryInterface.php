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

    public function save(Order $order, bool $flush = false): void;
    public function flush(): void;
    public function remove(Order $order): void;

    public function countAll(): int;
    public function getLastUpdateTimestamp(): ?int;
}
