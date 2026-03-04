<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CacheInterface $statsCache
    ) {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        return $this->find($id);
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->select('o', 'a')
            ->leftJoin('o.articles', 'a')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function save(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->persist($order);
    }

    public function remove(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->remove($order);
        $em->flush();
    }

    public function getStats(string $groupBy, int $page, int $limit): array
    {
        $cacheKey = sprintf('stats_%s_%d_%d', $groupBy, $page, $limit);

        return $this->statsCache->get($cacheKey, function (ItemInterface $item) use ($groupBy, $page, $limit) {
            $item->expiresAfter(600); // 10 minutes cache for stats

            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->select('s')
                ->from(\App\Domain\Entity\OrderStats::class, 's')
                ->where('s.groupBy = :groupBy')
                ->setParameter('groupBy', $groupBy)
                ->orderBy('s.period', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);

            $stats = $qb->getQuery()->getResult();

            $countQb = $this->getEntityManager()->createQueryBuilder();
            $countQb->select('COUNT(s.id)')
                ->from(\App\Domain\Entity\OrderStats::class, 's')
                ->where('s.groupBy = :groupBy')
                ->setParameter('groupBy', $groupBy);

            $total = (int)$countQb->getQuery()->getSingleScalarResult();

            return [
                'items' => array_map(fn(\App\Domain\Entity\OrderStats $item) => [
                    'period' => $item->getPeriod(),
                    'orderCount' => $item->getOrderCount(),
                    'totalAmount' => (float)$item->getTotalAmount(),
                ], $stats),
                'total' => $total,
            ];
        });
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForIndexing(int $offset, int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.number, o.customerInfo.email as email, o.customerInfo.name as clientName, o.customerInfo.surname as clientSurname, o.customerInfo.companyName as companyName, o.description')
            ->orderBy('o.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
