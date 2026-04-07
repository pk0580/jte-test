<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\OrderStats;
use App\Domain\Repository\OrderStatsProviderInterface;
use App\Infrastructure\Cache\CacheMetricsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Prometheus\CollectorRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareItemInterface;

readonly class OrderStatsProvider implements OrderStatsProviderInterface
{
    use CacheMetricsTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $statsCache,
        private CollectorRegistry $metricsRegistry
    ) {
    }

    public function getStats(string $groupBy, int $page, int $limit): array
    {
        $cacheKey = sprintf('stats_%s_%d_%d', $groupBy, $page, $limit);

        return $this->trackCache($this->statsCache, $cacheKey, function (ItemInterface $item) use ($groupBy, $page, $limit) {
            // @phpstan-ignore-next-line
            if ($item instanceof TagAwareItemInterface) {
                // @phpstan-ignore-next-line
                $item->tag(['stats', 'stats_' . $groupBy]);
            }

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
                'items' => array_map(fn (OrderStats $item) => [
                    'period' => $item->getPeriod(),
                    'orderCount' => $item->getOrderCount(),
                    'totalAmount' => (float)$item->getTotalAmount(),
                ], $stats),
                'total' => $total,
            ];
        }, 600);
    }
}
