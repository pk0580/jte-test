<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\OrderStats;
use App\Domain\Repository\OrderStatsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderStats>
 */
class DoctrineOrderStatsRepository extends ServiceEntityRepository implements OrderStatsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderStats::class);
    }

    public function incrementStats(string $period, string $groupBy, string $amount): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO order_stats (period, `group_by`, order_count, total_amount)
                VALUES (:period, :group_by, 1, :amount)
                ON DUPLICATE KEY UPDATE
                order_count = order_count + 1,
                total_amount = total_amount + :amount_update";

        $conn->executeStatement($sql, [
            'period' => $period,
            'group_by' => $groupBy,
            'amount' => $amount,
            'amount_update' => $amount,
        ]);
    }

    public function save(OrderStats $stats): void
    {
        $this->getEntityManager()->persist($stats);
        $this->getEntityManager()->flush();
    }
}
