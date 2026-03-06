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
        private readonly CacheInterface $appCache
    ) {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'a', 'p')
            ->leftJoin('o.articles', 'a')
            ->leftJoin('o.payType', 'p')
            ->where('o.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
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
        $this->getEntityManager()->persist($order);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->remove($order);
        $em->flush();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getLastUpdateTimestamp(): ?int
    {
        return $this->appCache->get('order_last_update_timestamp', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $result = $this->createQueryBuilder('o')
                ->select('MAX(o.dates.updateAt)')
                ->getQuery()
                ->getSingleScalarResult();

            if ($result === null) {
                return null;
            }

            if ($result instanceof \DateTimeInterface) {
                return $result->getTimestamp();
            }

            return (new \DateTime($result))->getTimestamp();
        });
    }
}
