<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\OrderStats;
use App\Domain\Repository\OrderStatsProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class OrderStatsProvider implements OrderStatsProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $statsCache
    ) {}

    public function getStats(string $groupBy, int $page, int $limit): array
    {
        $cacheKey = sprintf('stats_%s_%d_%d', $groupBy, $page, $limit);

        return $this->statsCache->get($cacheKey, function (ItemInterface $item) use ($groupBy, $page, $limit) {
            $item->expiresAfter(600); // 10 minutes cache for stats

            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('s')
                ->from(OrderStats::class, 's')
                ->where('s.groupBy = :groupBy')
                ->setParameter('groupBy', $groupBy)
                ->orderBy('s.period', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);

            /** @var OrderStats[] $stats */
            $stats = $qb->getQuery()->getResult();

            $countQb = $this->entityManager->createQueryBuilder();
            $countQb->select('COUNT(s.id)')
                ->from(OrderStats::class, 's')
                ->where('s.groupBy = :groupBy')
                ->setParameter('groupBy', $groupBy);

            $total = (int)$countQb->getQuery()->getSingleScalarResult();

            return [
                'items' => array_map(fn(OrderStats $item) => [
                    'period' => $item->getPeriod(),
                    'orderCount' => $item->getOrderCount(),
                    'totalAmount' => (float)$item->getTotalAmount(),
                ], $stats),
                'total' => $total,
            ];
        });
    }
}
