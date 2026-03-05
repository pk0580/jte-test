<?php

namespace App\Domain\Repository;

use App\Domain\Entity\OrderStats;

interface OrderStatsRepositoryInterface
{
    /**
     * Атомарный инкремент статистики за указанный период.
     * Если записи нет, она должна быть создана с начальными значениями.
     */
    public function incrementStats(string $period, string $groupBy, string $amount): void;

    /**
     * @return OrderStats|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function save(OrderStats $stats): void;
}
